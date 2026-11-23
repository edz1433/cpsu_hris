<?php

namespace Tests\Feature;

use App\Http\Controllers\LeaveApplicationController;
use App\Models\Employee;
use App\Models\LeaveApplication;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

/**
 * Filing a leave needs an e-signature on file and signs the form. HR still
 * enters days with pay, then the application goes straight to HR approval.
 */
class LeaveSignOnFilingTest extends TestCase
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
            $table->string('prefix')->nullable();
            $table->string('position')->nullable();
            $table->text('esign')->nullable();
            $table->integer('supervisor')->nullable();
            $table->integer('emp_dept')->nullable();
            $table->decimal('vl', 8, 3)->default(0);
            $table->decimal('sl', 8, 3)->default(0);
            $table->timestamps();
        });
        Schema::connection('mysql')->create('leave_applications', function (Blueprint $table) {
            $table->id();
            foreach (['transnum', 'empid', 'position', 'salary', 'date_range', 'leave_detail', 'total_vl', 'total_sl',
                'less_vl', 'less_sl', 'sup_prefix', 'pres_prefix', 'hr_prefix', 'date_filing', 'as_of', 'gen_app'] as $column) {
                $table->string($column)->nullable();
            }
            foreach (['leave_type', 'leave_purpose', 'days', 'commutation', 'supervisor', 'president', 'hr', 'department', 'day_wpay', 'holiday', 'hr_sign', 'sup_sign'] as $column) {
                $table->integer($column)->nullable();
            }
            $table->integer('emp_esign')->default(0);
            $table->integer('status')->default(1);
            $table->integer('remarks_stat')->default(0);
            $table->integer('history')->default(1);
            $table->timestamps();
        });

        Schema::connection('mysql')->create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('empid')->nullable();
            $table->unsignedBigInteger('lapp_id')->nullable();
            $table->string('esign_id')->nullable();
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
        Schema::connection('payroll')->create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_no');
            $table->decimal('monthly_salary', 10, 2)->nullable();
        });

        DB::connection('mysql')->table('employees')->insert([
            ['id' => 1, 'emp_ID' => 'E-1', 'fname' => 'Ana', 'lname' => 'Staff', 'esign' => 'signed', 'supervisor' => 3, 'vl' => 5, 'sl' => 5],
            ['id' => 2, 'emp_ID' => 'E-2', 'fname' => 'Pres', 'lname' => 'Ident', 'esign' => null, 'supervisor' => null, 'vl' => 0, 'sl' => 0],
            ['id' => 3, 'emp_ID' => 'E-3', 'fname' => 'Sup', 'lname' => 'Ervisor', 'esign' => null, 'supervisor' => null, 'vl' => 0, 'sl' => 0],
            ['id' => 4, 'emp_ID' => 'E-4', 'fname' => 'Hr', 'lname' => 'Head', 'esign' => null, 'supervisor' => null, 'vl' => 0, 'sl' => 0],
        ]);
        DB::connection('mysql')->table('settings')->insert(['id' => 1, 'suc_pres' => 2, 'hr' => 4]);
    }

    public function test_filing_without_an_e_signature_is_blocked(): void
    {
        Employee::whereKey(1)->update(['esign' => null]);

        $this->file();

        $this->assertStringContainsString('Upload your e-signature first', session('error'));
        $this->assertSame(0, LeaveApplication::count());
    }

    public function test_filing_signs_it_and_hr_days_step_skips_the_second_signature(): void
    {
        $this->file();

        $leave = LeaveApplication::firstOrFail();
        $this->assertSame(2, (int) $leave->emp_esign);
        $this->assertTrue($leave->needsHrDays()); // HR's days-with-pay step is still open.
        $this->assertSame(1, LeaveApplication::awaitingHrDays()->count());
        $this->assertSame(0, LeaveApplication::signedAndVerified()->count());

        // HR can't approve before entering the days, or no credits would be deducted.
        $this->be(Employee::find(4), 'employee');
        $blocked = $this->controller()->leaveApprove(Request::create('/', 'POST', ['id' => $leave->id, 'by' => 1]));
        $this->assertSame(409, $blocked->getStatusCode());
        $this->assertSame(1, (int) $leave->refresh()->status);

        $response = $this->controller()->leaveWpay(Request::create('/', 'POST', ['id' => $leave->id, 'day_wpay' => 0, 'holiday' => 0]));
        $this->assertTrue($response->getData(true)['success']);

        $leave->refresh();
        $this->assertSame(2, (int) $leave->emp_esign);
        $this->assertSame(2.0, (float) $leave->less_vl); // Credit deduction is still computed.
        $this->assertDatabaseHas('notifications', ['lapp_id' => $leave->id, 'category' => 2, 'utype' => 'hr']);
        $this->assertDatabaseMissing('notifications', ['lapp_id' => $leave->id, 'category' => 1, 'utype' => 'employee']);
    }

    public function test_older_unsigned_application_still_waits_for_the_employee(): void
    {
        $leave = LeaveApplication::create(['transnum' => '000001', 'empid' => 'E-1', 'leave_type' => 1, 'days' => 2, 'emp_esign' => 0]);

        $this->controller()->leaveWpay(Request::create('/', 'POST', ['id' => $leave->id, 'day_wpay' => 0, 'holiday' => 0]));

        $this->assertSame(1, (int) $leave->refresh()->emp_esign);
        $this->assertDatabaseHas('notifications', ['lapp_id' => $leave->id, 'category' => 1, 'utype' => 'employee']);
    }

    public function test_returning_to_the_employee_removes_the_filing_signature(): void
    {
        $leave = LeaveApplication::create(['transnum' => '000001', 'empid' => 'E-1', 'leave_type' => 1, 'days' => 2, 'emp_esign' => 2, 'as_of' => '2026-11-23 08:00:00', 'status' => 1]);

        $controller = Mockery::mock(LeaveApplicationController::class)->makePartial();
        $controller->shouldReceive('genApplication')->once();
        $controller->leaveReturn(Request::create('/', 'POST', ['to' => 1]), $leave->id);

        $leave->refresh();
        $this->assertSame(1, (int) $leave->emp_esign);
    }

    private function file(): void
    {
        $this->be(Employee::find(1), 'employee');
        $request = Request::create('/leave/create', 'POST', [
            'empid' => 'E-1', 'date_range' => '2026-12-03 to 2026-12-04', 'days' => 2, 'leave_type' => 1,
            'leave_purpose' => 1, 'leave_detail' => [''], 'date_filing' => '2026-11-23',
        ]);
        $request->setLaravelSession(app('session.store'));
        app()->instance('request', $request);

        $this->controller()->LeaveAppCreate($request);
        auth()->guard('employee')->logout();
    }

    // Skips the base constructor, which loads the notification bell.
    private function controller(): LeaveApplicationController
    {
        return (new \ReflectionClass(LeaveApplicationController::class))->newInstanceWithoutConstructor();
    }
}
