<?php

namespace Tests\Feature;

use App\Http\Controllers\CtoApplicationController;
use App\Http\Controllers\LeaveApplicationController;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveCtoDate;
use App\Models\Notification;
use App\Models\User;
use App\Services\LeaveCardLedger;
use App\Services\LeaveNotice;
use App\Services\WebPush\NotificationPush;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Compensatory Time-Off (CTO): filed in hours per date against employees.coc_leave.
 * Its own chain: employee (signed on filing) -> immediate supervisor -> HR (final).
 */
class CtoApplicationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        foreach (['mysql', 'payroll'] as $connection) {
            config()->set("database.connections.$connection", [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
            ]);
            DB::purge($connection);
        }
        (require database_path('migrations/2026_10_08_000001_create_audit_logs_table.php'))->up();

        Schema::connection('mysql')->create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('emp_ID');
            $table->string('fname');
            $table->string('lname');
            $table->string('mname')->nullable();
            $table->string('suffix')->nullable();
            $table->string('prefix')->nullable();
            $table->string('position')->nullable();
            $table->text('esign')->nullable();
            $table->integer('supervisor')->nullable();
            $table->integer('emp_dept')->nullable();
            $table->decimal('vl', 8, 3)->nullable();
            $table->decimal('sl', 8, 3)->nullable();
            $table->decimal('special_pl', 8, 3)->default(0);
            $table->decimal('servcred_leave', 8, 3)->default(0);
            $table->decimal('well_leave', 8, 3)->default(0);
            $table->decimal('coc_leave', 8, 3)->default(0);
            $table->timestamps();
        });
        Schema::connection('mysql')->create('leave_applications', function (Blueprint $table) {
            $table->id();
            foreach (['transnum', 'empid', 'position', 'salary', 'date_range', 'leave_detail', 'total_vl', 'total_sl',
                'less_vl', 'less_sl', 'sup_prefix', 'pres_prefix', 'hr_prefix', 'date_filing', 'as_of', 'gen_app',
                'sup_sdate', 'hr_sdate', 'pres_sdate', 'emp_esign', 'remarks_details', 'remarks_details1', 'remarks_details2'] as $column) {
                $table->string($column)->nullable();
            }
            foreach (['leave_type', 'leave_purpose', 'days', 'commutation', 'supervisor', 'oic', 'sup_sign', 'president',
                'pres_sign', 'hr', 'hr_sign', 'remarks_stat', 'department', 'day_wpay', 'status', 'holiday'] as $column) {
                $table->integer($column)->nullable();
            }
            $table->integer('history')->default(1);
            $table->timestamps();
        });
        (require database_path('migrations/2026_11_18_000002_add_cto_to_leave_applications.php'))->up();

        Schema::connection('mysql')->create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('empid')->nullable();
            $table->unsignedBigInteger('lapp_id')->nullable();
            $table->string('esign_id')->nullable(); // varchar in the real table
            $table->integer('category')->nullable();
            $table->string('utype')->nullable();
            $table->string('module')->nullable();
            $table->integer('status')->default(0);
            $table->timestamps();
        });
        Schema::connection('mysql')->create('settings', function (Blueprint $table) {
            $table->id();
            $table->integer('suc_pres')->nullable();
            $table->integer('hr')->nullable();
            $table->timestamps();
        });
        Schema::connection('mysql')->create('offices', function (Blueprint $table) {
            $table->id();
            $table->integer('oic_id')->nullable();
            $table->timestamps();
        });
        Schema::connection('mysql')->create('leave_credits', function (Blueprint $table) {
            $table->id();
            $table->string('empid');
            $table->string('date')->nullable();
            $table->decimal('days', 8, 3)->default(0);
            $table->decimal('earn_vl', 8, 3)->default(0);
            $table->decimal('earn_sl', 8, 3)->default(0);
            $table->integer('stat')->default(1);
            $table->string('remarks')->nullable();
            $table->timestamps();
        });
        Schema::connection('payroll')->create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_no');
            $table->decimal('monthly_salary', 10, 2)->nullable();
        });

        DB::connection('mysql')->table('employees')->insert([
            ['id' => 1, 'emp_ID' => 'E-1', 'fname' => 'Ana', 'lname' => 'Staff', 'position' => 'Clerk', 'esign' => 'signed', 'supervisor' => 3, 'vl' => 5, 'sl' => 5, 'coc_leave' => 16],
            ['id' => 2, 'emp_ID' => 'E-2', 'fname' => 'Pres', 'lname' => 'Ident', 'position' => null, 'esign' => null, 'supervisor' => null, 'vl' => 0, 'sl' => 0, 'coc_leave' => 0],
            ['id' => 3, 'emp_ID' => 'E-3', 'fname' => 'Sup', 'lname' => 'Ervisor', 'position' => null, 'esign' => 'signed', 'supervisor' => null, 'vl' => 0, 'sl' => 0, 'coc_leave' => 0],
            ['id' => 4, 'emp_ID' => 'E-4', 'fname' => 'Hr', 'lname' => 'Head', 'position' => null, 'esign' => null, 'supervisor' => null, 'vl' => 0, 'sl' => 0, 'coc_leave' => 0],
            ['id' => 5, 'emp_ID' => 'E-5', 'fname' => 'Other', 'lname' => 'Person', 'position' => null, 'esign' => null, 'supervisor' => null, 'vl' => 0, 'sl' => 0, 'coc_leave' => 0],
        ]);
        DB::connection('mysql')->table('settings')->insert(['id' => 1, 'suc_pres' => 2, 'hr' => 4]);
    }

    public function test_employee_files_cto_already_signed_and_it_goes_to_the_supervisor(): void
    {
        $this->fileCto([['date' => '2026-12-04', 'hours' => 4], ['date' => '2026-12-03', 'hours' => 8]]);

        $cto = LeaveApplication::where('empid', 'E-1')->firstOrFail();
        $this->assertSame(LeaveApplication::TYPE_CTO, (int) $cto->leave_type);
        $this->assertSame(2, (int) $cto->emp_esign);
        $this->assertSame(CtoApplicationController::WAITING_SUPERVISOR, (int) $cto->status);
        $this->assertSame(12.0, (float) $cto->cto_hours);
        $this->assertSame(16.0, (float) $cto->coc_balance);
        $this->assertSame('2026-12-03 to 2026-12-04', $cto->date_range);
        $this->assertSame(['2026-12-03' => 8.0, '2026-12-04' => 4.0],
            $cto->ctoDates->mapWithKeys(fn ($d) => [$d->cto_date->format('Y-m-d') => $d->hours])->all());
        // First stop is the supervisor, not HR.
        $this->assertDatabaseHas('notifications', ['lapp_id' => $cto->id, 'category' => 3, 'utype' => 'supervisor']);
        $this->assertDatabaseMissing('notifications', ['lapp_id' => $cto->id, 'utype' => 'hr']);
        $this->assertSame(16.0, (float) Employee::find(1)->coc_leave);
    }

    public function test_filing_needs_an_e_signature(): void
    {
        Employee::whereKey(1)->update(['esign' => null]);

        $this->fileCto([['date' => '2026-12-03', 'hours' => 8]]);

        $this->assertStringContainsString('Upload your e-signature first', session('error'));
        $this->assertSame(0, LeaveApplication::count());
    }

    public function test_cto_over_the_coc_balance_is_rejected(): void
    {
        $this->fileCto([['date' => '2026-12-03', 'hours' => 8], ['date' => '2026-12-04', 'hours' => 8], ['date' => '2026-12-05', 'hours' => 1]]);

        $this->assertStringContainsString('Insufficient Compensatory Overtime Credits', session('error'));
        $this->assertSame('cto', session('leave_tab'));
        $this->assertSame(0, LeaveApplication::count());
    }

    public function test_same_date_twice_and_more_than_10_hours_are_rejected(): void
    {
        $this->fileCto([['date' => '2026-12-03', 'hours' => 2], ['date' => '2026-12-03', 'hours' => 2]]);
        $this->assertSame('Each date can only be listed once.', session('error'));

        $this->fileCto([['date' => '2026-12-03', 'hours' => 10.5]]);
        $this->assertSame('Hours per date cannot be more than 10.', session('error'));

        $this->assertSame(0, LeaveApplication::count());
    }

    public function test_supervisor_then_hr_approve_and_hours_come_off_at_hr(): void
    {
        $cto = $this->filedCto();

        $this->assertTrue($this->decide($cto, 'supervisor', 'approve', Employee::find(3))->getData(true)['success']);
        $cto->refresh();
        $this->assertSame(CtoApplicationController::WAITING_HR, (int) $cto->status);
        $this->assertSame(2, (int) $cto->sup_sign);
        $this->assertSame(16.0, (float) Employee::find(1)->coc_leave);
        $this->assertDatabaseHas('notifications', ['lapp_id' => $cto->id, 'category' => 3, 'utype' => 'supervisor', 'status' => 1]);
        $this->assertDatabaseHas('notifications', ['lapp_id' => $cto->id, 'category' => 2, 'utype' => 'hr', 'status' => 0]);

        $this->assertTrue($this->decide($cto, 'hr', 'approve', $this->hrUser())->getData(true)['success']);
        $cto->refresh();
        $this->assertSame(CtoApplicationController::APPROVED, (int) $cto->status);
        $this->assertSame(2, (int) $cto->history);
        $this->assertSame(2, (int) $cto->hr_sign);
        $this->assertNull($cto->pres_sign);
        $this->assertSame(4.0, (float) Employee::find(1)->coc_leave);

        // HR can still cancel it afterwards, and the hours come back.
        $request = Request::create('/leave/cancel-approved', 'POST', ['id' => $cto->id, 'remarks' => 'Reported for work.']);
        app()->instance('request', $request);
        $this->assertTrue($this->leaveController()->leaveCancelApproved($request)->getData(true)['success']);
        $this->assertSame(16.0, (float) Employee::find(1)->coc_leave);
    }

    public function test_supervisor_or_hr_can_decline_with_a_reason(): void
    {
        $cto = $this->filedCto();
        $this->decide($cto, 'supervisor', 'decline', Employee::find(3), 'Busy season.');
        $cto->refresh();
        $this->assertSame(2, (int) $cto->remarks_stat);
        $this->assertSame('Busy season.', $cto->remarks_details);
        $this->assertSame(2, (int) $cto->history);

        LeaveApplication::query()->delete();
        $cto = $this->filedCto();
        $this->decide($cto, 'supervisor', 'approve', Employee::find(3));
        $this->decide($cto, 'hr', 'decline', $this->hrUser(), 'No COC record.');
        $cto->refresh();
        $this->assertSame(1, (int) $cto->remarks_stat);
        $this->assertSame(16.0, (float) Employee::find(1)->coc_leave);

        // A decline without a reason is refused.
        LeaveApplication::query()->delete();
        $cto = $this->filedCto();
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->decide($cto, 'supervisor', 'decline', Employee::find(3), '');
    }

    public function test_steps_are_guarded(): void
    {
        $cto = $this->filedCto();

        // Not this employee's supervisor.
        $this->assertSame(403, $this->decide($cto, 'supervisor', 'approve', Employee::find(5))->getStatusCode());
        // HR can't go before the supervisor.
        $this->assertSame(409, $this->decide($cto, 'hr', 'approve', $this->hrUser())->getStatusCode());
        // HR account without Leave access.
        $this->decide($cto, 'supervisor', 'approve', Employee::find(3));
        $this->assertSame(403, $this->decide($cto, 'hr', 'approve', $this->hrUser('0,0,0,0,0,0,0,0'))->getStatusCode());
        // Hours used up meanwhile: HR approval refused, nothing changes.
        Employee::whereKey(1)->update(['coc_leave' => 6]);
        $this->assertSame(422, $this->decide($cto, 'hr', 'approve', $this->hrUser())->getStatusCode());
        $this->assertSame(6.0, (float) Employee::find(1)->coc_leave);
        $this->assertSame(CtoApplicationController::WAITING_HR, (int) $cto->fresh()->status);
    }

    public function test_leave_steps_are_not_used_by_cto(): void
    {
        $cto = $this->filedCto();
        $this->decide($cto, 'supervisor', 'approve', Employee::find(3));
        $this->decide($cto, 'hr', 'approve', $this->hrUser());

        // The leave controller has no CTO deduction left: only CtoApplicationController takes hours.
        $source = file_get_contents(app_path('Http/Controllers/LeaveApplicationController.php'));
        $this->assertStringNotContainsString('coc_leave -', $source);
        $this->assertStringNotContainsString('ctoCreate', $source);
        $this->assertSame(4.0, (float) Employee::find(1)->coc_leave);
    }

    public function test_employee_is_notified_at_every_cto_step(): void
    {
        $cto = $this->filedCto();
        $this->decide($cto, 'supervisor', 'approve', Employee::find(3));
        $this->decide($cto, 'hr', 'approve', $this->hrUser());

        $this->assertSame([LeaveNotice::SUPERVISOR_APPROVED, LeaveNotice::APPROVED],
            Notification::where('utype', 'employee')->where('lapp_id', $cto->id)->orderBy('id')->pluck('category')->map(fn ($c) => (int) $c)->all());

        $messages = Notification::where('utype', 'employee')->where('lapp_id', $cto->id)->orderBy('id')->get()
            ->map(fn ($n) => (new NotificationPush($n))->message());
        $this->assertStringContainsString('forwarded to HR for final approval', $messages[0]['body']);
        $this->assertSame('CTO application approved', $messages[1]['title']);

        // Declined by the supervisor: the push says who.
        LeaveApplication::query()->delete();
        Employee::whereKey(1)->update(['coc_leave' => 16]);
        $cto = $this->filedCto();
        $this->decide($cto, 'supervisor', 'decline', Employee::find(3), 'Busy season.');
        $last = Notification::where('utype', 'employee')->where('lapp_id', $cto->id)->latest('id')->first();
        $message = (new NotificationPush($last))->message();
        $this->assertSame('CTO application disapproved', $message['title']);
        $this->assertStringContainsString('was disapproved by your immediate supervisor', $message['body']);
    }

    public function test_employee_is_told_when_hr_removes_a_pending_cto(): void
    {
        $cto = $this->filedCto();
        $this->be($this->hrUser(), 'web');

        $this->leaveController()->cancelLeave($cto->id);

        $notice = Notification::where('utype', 'employee')->where('lapp_id', $cto->id)->firstOrFail();
        $message = (new NotificationPush($notice))->message();
        $this->assertSame('Application removed', $message['title']);
        $this->assertStringContainsString('#' . $cto->transnum, $message['body']);
        $this->assertSame(0, LeaveCtoDate::count());
    }

    public function test_leave_card_lists_approved_cto_without_touching_vl_or_sl(): void
    {
        $cto = $this->filedCto();
        $this->decide($cto, 'supervisor', 'approve', Employee::find(3));
        $this->decide($cto, 'hr', 'approve', $this->hrUser());

        $card = (new LeaveCardLedger())->build('E-1', '2026-12', '2026-12');
        $row = collect($card['rows'])->firstWhere('particulars', '3,4 (CTO 12 hrs)');

        $this->assertNotNull($row);
        $this->assertSame(0.0, $row['vl_less']);
        $this->assertSame(0.0, $row['sl_less']);
    }

    public function test_cto_form_pdf_lists_each_date_with_running_balance(): void
    {
        $cto = $this->filedCto();
        $leaveApplication = LeaveApplication::query()
            ->join('employees', 'leave_applications.empid', '=', 'employees.emp_ID')
            ->select('leave_applications.*', 'leave_applications.id as lid', 'employees.fname', 'employees.lname', 'employees.mname', 'employees.suffix')
            ->where('leave_applications.id', $cto->id)
            ->first();

        $html = view('leaves.generate-cto', compact('leaveApplication'))->render();

        $this->assertStringContainsString('COMPENSATORY TIME OFF FORM', $html);
        $this->assertStringContainsString('ANA STAFF', $html);
        // 16 earned - 8 on Dec 3 = 8 left, then - 4 on Dec 4 = 4 left.
        $this->assertMatchesRegularExpression('/<td>16<\/td>\s*<td>12\/03\/2026<\/td>\s*<td>8<\/td>\s*<td>8<\/td>/', $html);
        $this->assertMatchesRegularExpression('/<td>8<\/td>\s*<td>12\/04\/2026<\/td>\s*<td>4<\/td>\s*<td>4<\/td>/', $html);
    }

    private function fileCto(array $rows)
    {
        $this->be(Employee::find(1), 'employee');
        $request = Request::create('/leave/cto/create', 'POST', ['cto' => $rows]);
        $request->setLaravelSession(app('session.store'));
        app()->instance('request', $request);

        $response = (new \ReflectionClass(CtoApplicationController::class))->newInstanceWithoutConstructor()->create($request);
        auth()->guard('employee')->logout();

        return $response;
    }

    private function filedCto(): LeaveApplication
    {
        $this->fileCto([['date' => '2026-12-03', 'hours' => 8], ['date' => '2026-12-04', 'hours' => 4]]);

        return LeaveApplication::where('empid', 'E-1')->latest('id')->firstOrFail();
    }

    private function decide(LeaveApplication $cto, string $step, string $action, $actor, ?string $remarks = null)
    {
        auth()->guard('web')->logout();
        auth()->guard('employee')->logout();
        $this->be($actor, $actor instanceof User ? 'web' : 'employee');

        $request = Request::create("/leave/cto/{$cto->id}/decide", 'POST', array_filter([
            'step' => $step, 'action' => $action, 'remarks' => $remarks,
        ], fn ($v) => $v !== null));
        app()->instance('request', $request);

        return (new \ReflectionClass(CtoApplicationController::class))->newInstanceWithoutConstructor()
            ->decide($request, $cto->fresh());
    }

    private function leaveController(): LeaveApplicationController
    {
        return (new \ReflectionClass(LeaveApplicationController::class))->newInstanceWithoutConstructor();
    }

    private function hrUser(string $access = '0,0,0,0,0,0,0,1'): User
    {
        $user = new User(['role' => 'HR Administrator', 'access' => $access]);
        $user->id = 9;

        return $user;
    }
}
