<?php

namespace Tests\Feature;

use App\Http\Controllers\LeavePresidentAssignmentController;
use App\Http\Controllers\LeaveApplicationController;
use App\Http\Controllers\PendingController;
use App\Http\Controllers\MasterController;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class LeavePresidentAssignmentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.connections.mysql', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        config()->set('database.connections.payroll', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge('mysql');
        DB::purge('payroll');
        (require database_path('migrations/2026_10_08_000001_create_audit_logs_table.php'))->up();
        Schema::connection('mysql')->create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('emp_ID');
            $table->string('fname');
            $table->string('mname')->nullable();
            $table->string('lname');
            $table->string('prefix')->nullable();
        });
        Schema::connection('mysql')->create('leave_applications', function (Blueprint $table) {
            $table->id();
            $table->string('empid');
            $table->string('transnum');
            $table->unsignedBigInteger('president');
            $table->unsignedBigInteger('hr')->nullable();
            $table->string('hr_prefix')->nullable();
            $table->integer('hr_sign')->nullable();
            $table->unsignedBigInteger('supervisor')->nullable();
            $table->integer('emp_esign')->nullable();
            $table->string('pres_prefix')->nullable();
            $table->integer('pres_sign')->nullable();
            $table->integer('status');
            $table->integer('history');
            $table->timestamps();
        });
        foreach (['eligibilities', 'work_experiences', 'learning_devs', 'voluntary_works'] as $tableName) {
            Schema::connection('mysql')->create($tableName, function (Blueprint $table) {
                $table->id();
                $table->integer('status');
            });
        }
        Schema::connection('mysql')->create('settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('suc_pres');
            $table->unsignedBigInteger('hr')->nullable();
            $table->unsignedBigInteger('vpaa')->nullable();
            $table->unsignedBigInteger('vpaf')->nullable();
            $table->integer('te_rstrct_lvl')->nullable();
            $table->string('hr_kiosk')->nullable();
            $table->string('dtr_acct')->nullable();
            $table->string('records_office_email')->nullable();
            $table->string('job_portal_email')->nullable();
            $table->boolean('sync_backups')->default(false);
            $table->boolean('maintenance')->default(false);
            $table->timestamps();
        });
        DB::connection('mysql')->table('employees')->insert([
            ['id' => 1, 'emp_ID' => 'E-1', 'fname' => 'Old', 'lname' => 'President', 'prefix' => 'Dr.'],
            ['id' => 2, 'emp_ID' => 'E-2', 'fname' => 'New', 'lname' => 'President', 'prefix' => 'Prof.'],
        ]);
        DB::connection('mysql')->table('leave_applications')->insert([
            'id' => 5, 'empid' => 'E-1', 'transnum' => 'L-5', 'president' => 1,
            'pres_sign' => null, 'status' => 3, 'history' => 1,
        ]);
        DB::connection('mysql')->table('settings')->insert(['suc_pres' => 1]);
    }

    public function test_administrator_can_change_only_one_unsigned_application(): void
    {
        $this->be($this->user('Administrator'), 'web');
        $application = LeaveApplication::findOrFail(5);
        $request = Request::create('/pending/leave/5/president', 'POST', ['president' => 2]);
        app()->instance('request', $request);

        $response = $this->controller()->update($request, $application);

        $this->assertTrue($response->getData(true)['success']);
        $this->assertDatabaseHas('leave_applications', [
            'id' => 5, 'president' => 2, 'pres_prefix' => 'Prof.',
        ]);
    }

    public function test_signed_application_cannot_be_reassigned(): void
    {
        $this->be($this->user('Administrator'), 'web');
        DB::connection('mysql')->table('leave_applications')->where('id', 5)->update(['pres_sign' => 2]);
        $request = Request::create('/pending/leave/5/president', 'POST', ['president' => 2]);
        app()->instance('request', $request);

        $this->expectException(ValidationException::class);
        $this->controller()->update($request, LeaveApplication::findOrFail(5));
    }

    public function test_non_administrator_cannot_reassign(): void
    {
        $this->be($this->user('HR Administrator'), 'web');
        $request = Request::create('/pending/leave/5/president', 'POST', ['president' => 2]);
        app()->instance('request', $request);

        $this->expectException(HttpException::class);
        $this->controller()->update($request, LeaveApplication::findOrFail(5));
    }

    public function test_previous_president_cannot_approve_after_reassignment(): void
    {
        DB::connection('mysql')->table('leave_applications')->where('id', 5)->update(['president' => 2]);
        $this->be(Employee::findOrFail(1), 'employee');
        $request = Request::create('/leave/approve-pres', 'POST', ['id' => 5, 'by' => 3]);
        app()->instance('request', $request);
        $controller = (new \ReflectionClass(LeaveApplicationController::class))->newInstanceWithoutConstructor();

        $response = $controller->leaveApprovePres($request);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame(2, (int) LeaveApplication::findOrFail(5)->president);
    }

    public function test_president_search_returns_matching_employees_only(): void
    {
        $this->be($this->user('Administrator'), 'web');
        $request = Request::create('/pending/president-options', 'GET', ['q' => 'New']);
        app()->instance('request', $request);

        $results = $this->controller()->options($request)->getData(true)['results'];

        $this->assertCount(1, $results);
        $this->assertSame(2, $results[0]['id']);
    }

    public function test_pending_row_uses_application_president_instead_of_system_default(): void
    {
        DB::connection('mysql')->table('leave_applications')->where('id', 5)->update(['president' => 2]);
        $this->be($this->user('Administrator'), 'web');
        $request = Request::create('/pending/1', 'GET');
        app()->instance('request', $request);
        $controller = (new \ReflectionClass(PendingController::class))->newInstanceWithoutConstructor();

        $row = $controller->readPending($request, 1)->getData()['employees']->first();

        $this->assertSame(2, (int) $row->president);
        $this->assertSame('New', $row->sucpres_fname);
        $this->assertSame(1, (int) DB::connection('mysql')->table('settings')->value('suc_pres'));
    }

    public function test_changing_system_default_does_not_reassign_existing_leave(): void
    {
        $this->be($this->user('Administrator'), 'web');
        $request = Request::create('/settings', 'PATCH', [
            'suc_pres' => 2, 'hr' => 1, 'te_rstrct_lvl' => 2, 'sync_backups' => 0,
        ]);
        app()->instance('request', $request);
        $controller = (new \ReflectionClass(MasterController::class))->newInstanceWithoutConstructor();

        $controller->updateSettings($request);

        $this->assertSame(2, (int) DB::connection('mysql')->table('settings')->value('suc_pres'));
        $this->assertSame(1, (int) LeaveApplication::findOrFail(5)->president);
    }

    private function controller(): LeavePresidentAssignmentController
    {
        return (new \ReflectionClass(LeavePresidentAssignmentController::class))->newInstanceWithoutConstructor();
    }

    private function user(string $role): User
    {
        $user = new User(['role' => $role]);
        $user->id = 9;
        return $user;
    }
}
