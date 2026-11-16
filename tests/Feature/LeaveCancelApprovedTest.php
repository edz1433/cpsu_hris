<?php

namespace Tests\Feature;

use App\Http\Controllers\LeaveApplicationController;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

// HRMO/admin cancellation of a leave application that was already approved.
class LeaveCancelApprovedTest extends TestCase
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
            $table->decimal('vl', 8, 3)->nullable();
            $table->decimal('sl', 8, 3)->nullable();
            $table->decimal('special_pl', 8, 3)->default(0);
            $table->decimal('servcred_leave', 8, 3)->default(0);
            $table->decimal('well_leave', 8, 3)->default(0);
            $table->timestamps();
        });
        Schema::connection('mysql')->create('leave_applications', function (Blueprint $table) {
            $table->id();
            $table->string('empid');
            $table->string('transnum')->nullable();
            $table->integer('leave_type');
            $table->decimal('days', 8, 3);
            $table->decimal('day_wpay', 8, 3)->default(0);
            $table->decimal('less_vl', 8, 3)->nullable();
            $table->decimal('less_sl', 8, 3)->nullable();
            $table->unsignedBigInteger('president')->nullable();
            $table->integer('pres_sign')->nullable();
            $table->integer('status');
            $table->integer('history');
            $table->integer('remarks_stat')->default(0);
            $table->text('remarks_details')->nullable();
            $table->text('remarks_details2')->nullable();
            $table->string('gen_app')->nullable();
            $table->timestamps();
        });
        Schema::connection('mysql')->create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('empid')->nullable();
            $table->unsignedBigInteger('lapp_id')->nullable();
            $table->unsignedBigInteger('esign_id')->nullable();
            $table->decimal('category', 4, 1)->nullable();
            $table->string('utype')->nullable();
            $table->string('module')->nullable();
            $table->integer('status')->default(0);
            $table->timestamps();
        });

        DB::connection('mysql')->table('employees')->insert([
            ['id' => 1, 'emp_ID' => 'E-1', 'fname' => 'Ana', 'lname' => 'Staff', 'vl' => 7, 'sl' => 10],
            ['id' => 2, 'emp_ID' => 'E-2', 'fname' => 'Pres', 'lname' => 'Ident', 'vl' => 0, 'sl' => 0],
        ]);
        // 3 days vacation leave, already deducted (vl was 10), approved by the President.
        DB::connection('mysql')->table('leave_applications')->insert([
            ['id' => 5, 'empid' => 'E-1', 'leave_type' => 1, 'days' => 3, 'less_vl' => 3, 'less_sl' => 0,
             'president' => 2, 'pres_sign' => 2, 'status' => 4, 'history' => 2, 'remarks_stat' => 0],
            // Supervisor-approved, still waiting for the President.
            ['id' => 6, 'empid' => 'E-1', 'leave_type' => 3, 'days' => 2, 'less_vl' => 0, 'less_sl' => 2,
             'president' => 2, 'pres_sign' => null, 'status' => 3, 'history' => 1, 'remarks_stat' => 0],
            // Disapproved by the supervisor.
            ['id' => 7, 'empid' => 'E-1', 'leave_type' => 1, 'days' => 1, 'less_vl' => 1, 'less_sl' => 0,
             'president' => 2, 'pres_sign' => null, 'status' => 3, 'history' => 2, 'remarks_stat' => 2],
        ]);
        DB::connection('mysql')->table('notifications')->insert([
            'empid' => 'E-1', 'lapp_id' => 6, 'category' => 4, 'utype' => 'president', 'module' => 'leave', 'status' => 0,
        ]);
    }

    public function test_hrmo_cancels_president_approved_leave_and_credits_are_restored(): void
    {
        $this->be($this->hrUser(), 'web');

        $response = $this->cancel(5, 'Employee reported for work.');

        $this->assertTrue($response->getData(true)['success']);
        $this->assertSame(10.0, (float) Employee::find(1)->vl);
        $leave = LeaveApplication::find(5);
        $this->assertSame(4, (int) $leave->remarks_stat);
        $this->assertSame(2, (int) $leave->history);
        $this->assertSame('Employee reported for work.', $leave->remarks_details2);
        $this->assertDatabaseHas('notifications', ['lapp_id' => 5, 'category' => 5, 'utype' => 'employee', 'module' => 'leave']);
    }

    public function test_cancelling_twice_does_not_refund_twice(): void
    {
        $this->be($this->hrUser(), 'web');
        $this->cancel(5, 'first');

        $response = $this->cancel(5, 'second');

        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame(10.0, (float) Employee::find(1)->vl);
    }

    public function test_cancel_while_awaiting_president_blocks_president_approval(): void
    {
        $this->be($this->hrUser(), 'web');
        $this->cancel(6, 'Wrong dates.');

        $this->assertSame(12.0, (float) Employee::find(1)->sl);
        $this->assertDatabaseHas('notifications', ['lapp_id' => 6, 'utype' => 'president', 'status' => 1]);

        auth()->guard('web')->logout();
        $this->be(Employee::find(2), 'employee');
        $request = Request::create('/leave/approve-pres', 'POST', ['id' => 6, 'by' => 3]);
        app()->instance('request', $request);

        $this->assertSame(403, $this->controller()->leaveApprovePres($request)->getStatusCode());
        $this->assertSame(3, (int) LeaveApplication::find(6)->status);
    }

    public function test_disapproved_leave_cannot_be_cancelled(): void
    {
        $this->be($this->hrUser(), 'web');

        $this->assertSame(422, $this->cancel(7, 'x')->getStatusCode());
        $this->assertSame(7.0, (float) Employee::find(1)->vl);
    }

    public function test_account_without_leave_access_cannot_cancel(): void
    {
        $this->be($this->hrUser('0,0,0,0,0,0,0,0'), 'web');

        $this->assertSame(403, $this->cancel(5, 'x')->getStatusCode());
        $this->assertSame(0, (int) LeaveApplication::find(5)->remarks_stat);
    }

    public function test_employee_cannot_cancel_even_through_old_disapprove_route(): void
    {
        $this->be(Employee::find(1), 'employee');
        $request = Request::create('/leave/dis-approve', 'POST', ['id' => 5, 'by' => 4, 'remarks' => 'refund me']);
        app()->instance('request', $request);

        $response = $this->controller()->leaveDisapprove($request);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame(7.0, (float) Employee::find(1)->vl);
        $this->assertSame(0, (int) LeaveApplication::find(5)->remarks_stat);
    }

    private function cancel(int $id, string $remarks)
    {
        $request = Request::create('/leave/cancel-approved', 'POST', ['id' => $id, 'remarks' => $remarks]);
        app()->instance('request', $request);

        return $this->controller()->leaveCancelApproved($request);
    }

    private function controller(): LeaveApplicationController
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
