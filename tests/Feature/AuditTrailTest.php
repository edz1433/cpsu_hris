<?php

namespace Tests\Feature;

use App\Http\Middleware\RecordAuditRequest;
use App\Http\Controllers\AuditLogController;
use App\Http\Middleware\CaptureAuditSnapshot;
use App\Models\ContractPeriod;
use App\Models\OfficialTime;
use App\Models\User;
use App\Services\Audit\AuditFormatter;
use App\Services\Audit\AuditModuleCatalog;
use App\Services\Audit\AuditScope;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Tests\TestCase;

class AuditTrailTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Cached production view paths may not exist on the Windows test host.
        app('view')->getFinder()->setPaths([resource_path('views')]);
        config()->set('audit', require config_path('audit.php'));

        config()->set('database.default', 'mysql');
        config()->set('database.connections.mysql', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        config()->set('audit.capture_in_console', true);
        config()->set('database.connections.payroll', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge('mysql');
        DB::purge('payroll');

        (require database_path('migrations/2026_10_08_000001_create_audit_logs_table.php'))->up();
        Schema::connection('mysql')->create('contract_periods', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('status');
            $table->string('contract_type');
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_model_changes_write_safe_json_and_keep_wording_configurable(): void
    {
        $this->bindRequest('/contracts', 'POST');
        $period = ContractPeriod::create([
            'title' => 'Sample Period', 'status' => 'open', 'contract_type' => 'cos',
            'start_date' => '2026-10-01', 'end_date' => '2026-12-31',
        ]);
        $period->update(['status' => 'closed']);
        $period->delete();

        $logs = DB::connection('mysql')->table('audit_logs')->orderBy('id')->get();
        $this->assertSame(['created', 'updated', 'deleted'], $logs->pluck('action')->all());
        $this->assertSame('contracts', $logs[0]->module);
        $created = json_decode($logs[0]->details, true);
        $updated = json_decode($logs[1]->details, true);
        $this->assertSame('Sample Period', $created['new_data']['title']);
        $this->assertArrayNotHasKey('contract_type', $created['new_data']);
        $this->assertSame('open', $updated['old_data']['status']);
        $this->assertSame('closed', $updated['new_data']['status']);

        config()->set('audit.messages.deleted', '{actor} removed {target} on {time}.');
        $this->assertStringContainsString('removed Contract Period Sample Period', AuditFormatter::describe($logs[2]));
    }

    public function test_authentication_event_records_a_login_without_credentials(): void
    {
        $this->bindRequest('/post-login', 'POST');
        $user = new User(['fname' => 'Maria', 'lname' => 'Santos', 'username' => 'maria@example.test']);
        $user->id = 7;
        Event::dispatch(new Login('web', $user, false));

        $log = DB::connection('mysql')->table('audit_logs')->first();
        $this->assertSame('login', $log->action);
        $this->assertSame('Maria Santos', $log->actor_name);
        $this->assertStringNotContainsString('password', $log->details);
    }

    public function test_handled_failure_and_dtr_request_are_recorded_separately(): void
    {
        $request = $this->bindRequest('/employees/update', 'POST');
        app(RecordAuditRequest::class)->handle($request, fn () => response()->json(['success' => false, 'error' => 'Failed'], 200));

        $log = DB::connection('mysql')->table('audit_logs')->first();
        $this->assertSame('failure', $log->outcome);
        $this->assertSame('request_rejected', $log->reason_code);

        $dtrRequest = $this->bindRequest('/api/app/log-attendance', 'POST');
        $dtrRequest->merge(['action' => 1, 'embedding' => 'biometric-secret']);
        app(RecordAuditRequest::class)->handle($dtrRequest, fn () => response()->json(['success' => false], 200));
        $this->assertSame(2, DB::connection('mysql')->table('audit_logs')->where('outcome', '!=', 'received')->count());
        $this->assertSame(1, DB::connection('mysql')->table('audit_logs')->where('outcome', 'received')->count());
        $dtrLog = DB::connection('mysql')->table('audit_logs')->orderByDesc('id')->first();
        $this->assertSame('dtr', $dtrLog->module);
        $this->assertSame('time_in', $dtrLog->action);
        $this->assertSame('rejected', $dtrLog->outcome);
        $this->assertNull($dtrLog->user_agent);
        $this->assertStringNotContainsString('biometric-secret', $dtrLog->details);
    }

    public function test_failed_authentication_records_reason_without_verification_code(): void
    {
        $request = $this->bindRequest('/auth/google/callback', 'GET');
        $session = new Store('audit-test', new ArraySessionHandler(120));
        $session->start();
        $session->flash('error', 'Invalid Verification Code');
        $request->setLaravelSession($session);

        app(RecordAuditRequest::class)->handle($request, fn () => redirect('/'));

        $log = DB::connection('mysql')->table('audit_logs')->first();
        $this->assertSame('failed_login', $log->action);
        $this->assertSame('invalid_verification_code', $log->reason_code);
        $this->assertStringNotContainsString('verification_code', $log->details);
    }

    public function test_non_model_write_records_request_without_submitted_secrets(): void
    {
        $request = Request::create('/office/create', 'POST', ['password' => 'never-log-this']);
        app()->instance('request', $request);
        app(RecordAuditRequest::class)->handle($request, fn () => response()->json(['success' => true], 200));

        $log = DB::connection('mysql')->table('audit_logs')->first();
        $this->assertSame('request', $log->action);
        $this->assertSame('success', $log->outcome);
        $this->assertStringNotContainsString('never-log-this', $log->details);
    }

    public function test_rolled_back_model_change_has_no_success_entry(): void
    {
        $this->bindRequest('/contracts', 'POST');
        try {
            DB::connection('mysql')->transaction(function () {
                ContractPeriod::create([
                    'title' => 'Rolled Back', 'status' => 'open', 'contract_type' => 'cos',
                    'start_date' => '2026-10-01', 'end_date' => '2026-12-31',
                ]);
                throw new \RuntimeException('Abort transaction');
            });
        } catch (\RuntimeException $e) {
            // The failed request is logged by middleware when handled through HTTP.
        }

        $this->assertSame(0, DB::connection('mysql')->table('audit_logs')->count());
    }

    public function test_direct_database_write_is_attributed_to_its_module(): void
    {
        DB::connection('mysql')->table('contract_periods')->insert([
            'title' => 'Existing Period', 'status' => 'open', 'contract_type' => 'cos',
            'start_date' => '2026-10-01', 'end_date' => '2026-12-31',
        ]);
        $request = $this->bindRequest('/contracts/1/close', 'POST');
        app(RecordAuditRequest::class)->handle($request, function () {
            DB::connection('mysql')->table('contract_periods')->where('id', 1)->update(['status' => 'closed']);
            return response()->json(['success' => true]);
        });

        $log = DB::connection('mysql')->table('audit_logs')->first();
        $details = json_decode($log->details, true);
        $this->assertSame('contracts', $log->module);
        $this->assertSame('write', $log->action);
        $this->assertSame('contract_periods', $details['database_operations'][0]['table']);
        $this->assertSame('update', $details['database_operations'][0]['operation']);
    }

    public function test_model_write_inside_request_does_not_add_a_duplicate_database_entry(): void
    {
        $request = $this->bindRequest('/contracts', 'POST');
        app(RecordAuditRequest::class)->handle($request, function () {
            ContractPeriod::create([
                'title' => 'One Period', 'status' => 'open', 'contract_type' => 'cos',
                'start_date' => '2026-10-01', 'end_date' => '2026-12-31',
            ]);
            return response()->json(['success' => true]);
        });

        $logs = DB::connection('mysql')->table('audit_logs')->get();
        $this->assertCount(1, $logs);
        $this->assertSame('created', $logs[0]->action);
    }

    public function test_web_official_time_failure_is_audited_under_employees(): void
    {
        $request = $this->bindRequest('/employees/official-time/update', 'POST');
        app(RecordAuditRequest::class)->handle($request, fn () => response()->json(['success' => false], 200));

        $log = DB::connection('mysql')->table('audit_logs')->first();
        $this->assertSame('employees', $log->module);
        $this->assertSame('failure', $log->outcome);
    }

    public function test_official_time_change_records_only_changed_schedule_fields(): void
    {
        $this->prepareOfficialTime(['morn_mon' => '08:00:00-12:00:00', 'aft_mon' => '13:00:00-17:00:00']);
        $this->saveOfficialTime(['morn_mon' => '07:30:00-12:00:00', 'aft_mon' => '13:00:00-17:00:00']);

        $logs = DB::connection('mysql')->table('audit_logs')->get();
        $this->assertCount(1, $logs);
        $log = $logs[0];
        $this->assertSame(['employees', 'updated', 'success', 'official_time', 'Juan Cruz', 'E-8'],
            [$log->module, $log->action, $log->outcome, $log->target_type, $log->target_name, $log->employee_code]);
        $details = json_decode($log->details, true);
        $this->assertSame(['morn_mon' => '08:00:00-12:00:00'], $details['old_data']);
        $this->assertSame(['morn_mon' => '07:30:00-12:00:00'], $details['new_data']);
        $this->assertStringContainsString('updated Official Time Juan Cruz', AuditFormatter::describe($log));
    }

    public function test_official_time_saved_with_same_values_is_recorded_as_unchanged(): void
    {
        $times = ['morn_mon' => '08:00:00-12:00:00', 'aft_mon' => '13:00:00-17:00:00'];
        $this->prepareOfficialTime($times);
        $this->saveOfficialTime($times);

        $logs = DB::connection('mysql')->table('audit_logs')->get();
        $this->assertCount(1, $logs);
        $this->assertSame(['no_change', 'unchanged', 'no_changes'], [$logs[0]->action, $logs[0]->outcome, $logs[0]->reason_code]);
        $this->assertSame(['old_data' => [], 'new_data' => []], json_decode($logs[0]->details, true));
        $this->assertStringContainsString('saved Official Time Juan Cruz with no changes', AuditFormatter::describe($logs[0]));
    }

    public function test_first_official_time_is_recorded_as_created(): void
    {
        $this->prepareOfficialTime(null);
        $this->saveOfficialTime(['morn_mon' => '08:00:00-12:00:00']);

        $logs = DB::connection('mysql')->table('audit_logs')->get();
        $this->assertCount(1, $logs);
        $this->assertSame('created', $logs[0]->action);
        $this->assertSame('08:00:00-12:00:00', json_decode($logs[0]->details, true)['new_data']['morn_mon']);
    }

    public function test_signed_out_web_requests_are_recorded_as_unauthenticated_failures(): void
    {
        foreach (['POST', 'GET'] as $method) {
            $request = $this->bindRequest('/leave/store', $method);
            $session = new Store('audit-test', new ArraySessionHandler(120));
            $session->start();
            // LoginAuth's redirect for a visitor without a session.
            $session->flash('error1', 'You have to sign in first to access this page');
            $request->setLaravelSession($session);
            app(RecordAuditRequest::class)->handle($request, fn () => redirect('/'));
        }

        $logs = DB::connection('mysql')->table('audit_logs')->get();
        $this->assertCount(2, $logs);
        foreach ($logs as $log) {
            $this->assertSame(['leave', 'failure', 'unauthenticated'], [$log->module, $log->outcome, $log->reason_code]);
        }
    }

    public function test_all_three_attendance_actions_and_result_branches_are_audited(): void
    {
        $this->prepareAttendanceTables();
        $cases = [
            [1, ['success' => true, 'updated' => true, 'allowed' => true], 200, 'success', null],
            [2, ['success' => true, 'updated' => true, 'allowed' => true], 200, 'success', null],
            [3, ['success' => true, 'updated' => true, 'allowed' => true], 200, 'success', null],
            [1, ['success' => false, 'updated' => false, 'allowed' => true], 202, 'unchanged', 'duplicate_or_unchanged'],
            [2, ['success' => false, 'updated' => false, 'allowed' => true], 202, 'unchanged', 'duplicate_or_unchanged'],
            [3, ['success' => false, 'updated' => false, 'allowed' => true], 202, 'unchanged', 'duplicate_or_unchanged'],
            [1, ['success' => false, 'updated' => false, 'allowed' => false, 'wait_seconds' => 37], 429, 'rejected', 'time_in_after_time_out_60_seconds'],
            [2, ['error' => 'Unknown emp_ID'], 404, 'rejected', 'identity_not_found'],
            [3, ['error' => 'Server error', 'message' => 'database-secret'], 500, 'error', 'server_error'],
            [1, ['match' => false, 'message' => 'Face did not match.'], 200, 'rejected', 'face_mismatch'],
        ];

        foreach ($cases as [$number, $payload, $status, $outcome, $reason]) {
            $request = Request::create('/api/app/log-attendance', 'POST', [
                'action' => $number, 'emp_id' => 'MISSING', 'embedding' => 'never-store-embedding',
            ]);
            app()->instance('request', $request);
            app(RecordAuditRequest::class)->handle($request, fn () => response()->json($payload, $status));

            $log = DB::connection('mysql')->table('audit_logs')->orderByDesc('id')->first();
            $this->assertSame([1 => 'time_in', 2 => 'time_out', 3 => 'overtime'][$number], $log->action);
            $this->assertSame($outcome, $log->outcome);
            $this->assertSame($reason, $log->reason_code);
            $this->assertStringNotContainsString('never-store-embedding', $log->details);
            $this->assertStringNotContainsString('database-secret', $log->details);
        }

        $this->assertSame(count($cases), DB::connection('mysql')->table('audit_logs')->where('outcome', '!=', 'received')->count());
        $this->assertSame(count($cases), DB::connection('mysql')->table('audit_logs')->where('outcome', 'received')->count());
        $gap = DB::connection('mysql')->table('audit_logs')->where('reason_code', 'time_in_after_time_out_60_seconds')->first();
        $this->assertSame(37, json_decode($gap->details, true)['seconds_remaining']);
        $this->assertStringContainsString('37 seconds remaining', AuditFormatter::describe($gap));
    }

    public function test_dtr_validation_exception_and_http_200_device_failure_are_audited(): void
    {
        $request = $this->bindRequest('/api/app/log-attendance', 'POST');
        $request->merge(['action' => 1]);
        try {
            app(RecordAuditRequest::class)->handle($request, function () {
                throw \Illuminate\Validation\ValidationException::withMessages(['zone_id' => 'Required']);
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
        }

        $request = $this->bindRequest('/api/app/admin-pass-verify', 'POST');
        $request->merge(['password' => 'secret-password']);
        app(RecordAuditRequest::class)->handle($request, fn () => response()->json(['ok' => false, 'message' => 'Incorrect password.'], 200));

        $logs = DB::connection('mysql')->table('audit_logs')->where('outcome', '!=', 'received')->orderBy('id')->get();
        $this->assertSame('validation_failed', $logs[0]->reason_code);
        $this->assertSame('error', $logs[0]->outcome);
        $this->assertSame('invalid_credentials', $logs[1]->reason_code);
        $this->assertSame('rejected', $logs[1]->outcome);
        $this->assertStringNotContainsString('secret-password', $logs[1]->details);
    }

    public function test_device_link_results_are_recorded_without_the_android_identifier(): void
    {
        foreach (['0' => ['rejected', 'device_already_linked'],
                  '1' => ['success', null], '2' => ['unchanged', 'device_already_linked']] as $body => [$outcome, $reason]) {
            $request = Request::create('/api/app/authcheck', 'GET', [
                'androidid' => 'private-device-id', 'empid' => 'E-8',
            ]);
            app()->instance('request', $request);
            app(RecordAuditRequest::class)->handle($request, fn () => response($body, 200));
            $log = DB::connection('mysql')->table('audit_logs')->orderByDesc('id')->first();
            $this->assertSame($outcome, $log->outcome);
            $this->assertSame($reason, $log->reason_code);
            $this->assertStringNotContainsString('private-device-id', $log->details);
        }
    }

    public function test_actual_attendance_endpoint_records_its_caught_validation_failure(): void
    {
        $request = $this->bindRequest('/api/app/log-attendance', 'POST');
        $controller = (new \ReflectionClass(\App\Http\Controllers\Api\TimeEntryController::class))->newInstanceWithoutConstructor();
        $response = app(RecordAuditRequest::class)->handle($request, fn () =>
            $controller->logAttendance($request));
        $this->assertSame(500, $response->getStatusCode());

        $log = DB::connection('mysql')->table('audit_logs')->where('outcome', '!=', 'received')->first();
        $this->assertSame('dtr', $log->module);
        $this->assertSame('attendance_attempt', $log->action);
        $this->assertSame('rejected', $log->outcome);
        $this->assertSame('validation_failed', $log->reason_code);
        $this->assertStringNotContainsString('field is required', $log->details);
    }

    public function test_actual_attendance_time_in_gap_and_all_three_writes_are_recorded(): void
    {
        $this->prepareAttendanceTables();
        $controller = (new \ReflectionClass(\App\Http\Controllers\Api\TimeEntryController::class))->newInstanceWithoutConstructor();
        \Carbon\Carbon::setTestNow('2026-10-08 13:00:00');
        try {
            foreach ([1, 2, 3] as $number) {
                $request = Request::create('/api/app/log-attendance', 'POST', [
                    'action' => $number, 'emp_id' => 'E-8', 'zone_id' => '5',
                ]);
                app()->instance('request', $request);
                $response = app(RecordAuditRequest::class)->handle($request, fn () => $controller->logAttendance($request));
                $this->assertSame(200, $response->getStatusCode());
            }

            DB::connection('mysql')->table('dtrs')->where('emp_ID', 'E-8')->update(['time_out' => '10:00:00']);
            $request = Request::create('/api/app/log-attendance', 'POST', [
                'action' => 1, 'emp_id' => 'E-8', 'zone_id' => '5',
            ]);
            app()->instance('request', $request);
            $response = app(RecordAuditRequest::class)->handle($request, fn () => $controller->logAttendance($request));
            $this->assertSame(202, $response->getStatusCode());

            DB::connection('mysql')->table('dtrs')->where('emp_ID', 'E-8')->update(['time_out' => '12:59:30']);
            $request = Request::create('/api/app/log-attendance', 'POST', [
                'action' => 1, 'emp_id' => 'E-8', 'zone_id' => '5',
            ]);
            app()->instance('request', $request);
            $response = app(RecordAuditRequest::class)->handle($request, fn () => $controller->logAttendance($request));
            $this->assertSame(429, $response->getStatusCode());
        } finally {
            \Carbon\Carbon::setTestNow();
        }

        $logs = DB::connection('mysql')->table('audit_logs')->where('outcome', '!=', 'received')->orderBy('id')->get();
        $this->assertSame(['time_in', 'time_out', 'overtime', 'time_in', 'time_in'], $logs->pluck('action')->all());
        $this->assertSame(['success', 'success', 'success', 'unchanged', 'rejected'], $logs->pluck('outcome')->all());
        $receipts = DB::connection('mysql')->table('audit_logs')->where('outcome', 'received')->orderBy('id')->get();
        $this->assertCount(5, $receipts);
        $this->assertSame($receipts[0]->request_id, $logs[0]->request_id);
        $this->assertStringContainsString('completion is recorded separately', AuditFormatter::describe($receipts[0]));
        $details = json_decode($logs[0]->details, true);
        $this->assertSame('2026-10-08 13:00:00', $details['attendance_at']);
        $this->assertSame((int) DB::connection('mysql')->table('dtrs')->value('id'), $details['dtr_id']);
        $this->assertSame(8, $logs[4]->employee_id);
        $this->assertSame(30, json_decode($logs[4]->details, true)['seconds_remaining']);
    }

    public function test_each_non_dtr_mutating_route_has_a_module_mapping(): void
    {
        $catalog = app(AuditModuleCatalog::class);
        $unmapped = [];
        foreach (app('router')->getRoutes() as $route) {
            if (!array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
                continue;
            }
            $request = Request::create('/'.$route->uri(), 'POST');
            $request->setRouteResolver(fn () => $route);
            if (AuditScope::excludes($request) || AuditScope::isDtr($request) || str_starts_with($route->uri(), '_')) {
                continue;
            }
            if (!$catalog->isMapped($request)) {
                $unmapped[] = $route->uri();
            }
        }
        $this->assertSame([], $unmapped);
    }

    public function test_office_bulk_update_gets_safe_before_and_after_values(): void
    {
        Schema::connection('payroll')->create('offices', function (Blueprint $table) {
            $table->id();
            $table->string('office_name');
            $table->string('office_abbr')->nullable();
            $table->unsignedBigInteger('office_head_id')->nullable();
            $table->unsignedBigInteger('oic_id')->nullable();
        });
        DB::connection('payroll')->table('offices')->insert(['id' => 3, 'office_name' => 'Old Office']);
        $request = Request::create('/office/update', 'POST', ['oid' => 3]);
        $route = new Route('POST', 'office/update', fn () => null);
        $route->name('officeUpdate');
        $request->setRouteResolver(fn () => $route);
        app()->instance('request', $request);

        app(CaptureAuditSnapshot::class)->handle($request, function () {
            DB::connection('payroll')->table('offices')->where('id', 3)->update(['office_name' => 'New Office']);
            return response()->json(['success' => true]);
        });

        $log = DB::connection('mysql')->table('audit_logs')->first();
        $details = json_decode($log->details, true);
        $this->assertSame('updated', $log->action);
        $this->assertSame('offices', $log->module);
        $this->assertSame('Old Office', $details['old_data']['office_name']);
        $this->assertSame('New Office', $details['new_data']['office_name']);
    }

    public function test_http_200_with_failure_status_is_recorded_as_failed(): void
    {
        $request = $this->bindRequest('/career/delete', 'POST');
        app(RecordAuditRequest::class)->handle($request, fn () => response()->json(['status' => 404, 'message' => 'Job not found'], 200));

        $log = DB::connection('mysql')->table('audit_logs')->first();
        $this->assertSame('careers', $log->module);
        $this->assertSame('failure', $log->outcome);
        $this->assertSame('not_found', $log->reason_code);
        $this->assertStringNotContainsString('Job not found', $log->details);
    }

    public function test_leave_request_entry_keeps_affected_employee_filters(): void
    {
        Schema::connection('mysql')->create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('emp_ID');
            $table->string('fname');
            $table->string('mname')->nullable();
            $table->string('lname');
            $table->unsignedBigInteger('camp_id')->nullable();
            $table->unsignedBigInteger('emp_dept')->nullable();
        });
        DB::connection('mysql')->table('employees')->insert([
            'id' => 9, 'emp_ID' => 'EMP-9', 'fname' => 'Juan', 'lname' => 'Cruz',
            'camp_id' => 2, 'emp_dept' => 4,
        ]);
        $request = Request::create('/leaves/leaves-deduct', 'POST', ['empid' => 9]);
        app()->instance('request', $request);
        app(RecordAuditRequest::class)->handle($request, fn () => response()->json(['success' => true]));

        $log = DB::connection('mysql')->table('audit_logs')->first();
        $this->assertSame('Juan Cruz', $log->target_name);
        $this->assertSame(9, $log->employee_id);
        $this->assertSame('EMP-9', $log->employee_code);
        $this->assertSame(2, $log->campus_id);
        $this->assertSame(4, $log->office_id);
    }

    public function test_sensitive_report_access_is_recorded_without_report_contents(): void
    {
        $request = Request::create('/contracts/1/download-all', 'GET');
        $route = new Route('GET', 'contracts/{period}/download-all', fn () => null);
        $route->name('contracts.downloadAll');
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);
        app()->instance('request', $request);

        app(RecordAuditRequest::class)->handle($request, fn () => response('contract file bytes', 200));

        $log = DB::connection('mysql')->table('audit_logs')->first();
        $this->assertSame('accessed', $log->action);
        $this->assertSame('contracts', $log->module);
        $this->assertStringNotContainsString('contract file bytes', $log->details);
    }

    public function test_date_preset_filters_without_loading_every_actor_or_employee(): void
    {
        Schema::connection('payroll')->create('campuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        Schema::connection('payroll')->create('offices', function (Blueprint $table) {
            $table->id();
            $table->string('office_name');
        });
        $admin = new User(['fname' => 'Admin', 'lname' => 'User', 'role' => 'Administrator']);
        $admin->id = 1;
        $this->be($admin, 'web');
        $today = \Carbon\Carbon::today(config('app.timezone'));
        foreach ([$today->copy(), $today->copy()->subDays(3), $today->copy()->subDays(40)] as $day) {
            DB::connection('mysql')->table('audit_logs')->insert([
                'occurred_at' => $day->format('Y-m-d H:i:s'),
                'module' => 'employees', 'action' => 'updated', 'outcome' => 'success',
                'actor_name' => 'Maria Santos', 'employee_code' => 'E-8',
                'target_type' => 'employee', 'target_name' => 'Juan Cruz',
                'reason_code' => 'validation_failed', 'ip_address' => '127.0.0.1',
                'details' => '{}',
            ]);
        }

        $request = Request::create('/audit-logs', 'GET', [
            'actor' => 'Maria Santos', 'person' => 'E-8', 'date_range' => 'last_7_days',
        ]);
        app()->instance('request', $request);
        $controller = (new \ReflectionClass(AuditLogController::class))->newInstanceWithoutConstructor();
        $view = $controller->index($request);
        $data = $view->getData();

        $this->assertSame(2, $data['logs']->count());
        $this->assertArrayNotHasKey('actors', $data);
        $this->assertArrayNotHasKey('people', $data);
        $this->assertContains('tardiness', $data['modules']);
        $this->assertContains('dtr', $data['modules']);
        $this->assertArrayNotHasKey('reasonCodes', $data);
    }

    public function test_module_filter_separates_dtr_and_tardiness_including_older_entries(): void
    {
        Schema::connection('payroll')->create('campuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        Schema::connection('payroll')->create('offices', function (Blueprint $table) {
            $table->id();
            $table->string('office_name');
        });
        $admin = new User(['role' => 'Administrator']);
        $admin->id = 1;
        $this->be($admin, 'web');
        foreach ([
            ['module' => 'dtr', 'route_name' => 'OfficialTimeRead'],
            ['module' => 'dtr', 'route_name' => 'readTiredness'],
            ['module' => 'tardiness', 'route_name' => 'tirednessSearch'],
            ['module' => 'employees', 'route_name' => 'empUpdate'],
        ] as $row) {
            DB::connection('mysql')->table('audit_logs')->insert($row + [
                'occurred_at' => '2026-10-08 12:00:00', 'action' => 'request',
                'outcome' => 'success', 'details' => '{}',
            ]);
        }
        $controller = (new \ReflectionClass(AuditLogController::class))->newInstanceWithoutConstructor();
        foreach (['dtr' => [1], 'tardiness' => [3, 2], 'employees' => [4]] as $module => $ids) {
            $request = Request::create('/audit-logs', 'GET', ['module' => $module]);
            app()->instance('request', $request);
            $logs = $controller->index($request)->getData()['logs'];
            $this->assertSame($ids, collect($logs->items())->pluck('id')->all());
        }
    }

    public function test_tardiness_attempt_is_recorded_under_its_own_module(): void
    {
        $request = Request::create('/tardiness/data', 'POST');
        app()->instance('request', $request);
        app(RecordAuditRequest::class)->handle($request, fn () => response('ok', 200));

        $log = DB::connection('mysql')->table('audit_logs')->first();
        $this->assertSame('tardiness', $log->module);
        $this->assertStringContainsString('completed', AuditFormatter::describe($log));
    }

    public function test_recent_activity_loads_only_the_next_batch_without_counting_all_rows(): void
    {
        Schema::connection('payroll')->create('campuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        Schema::connection('payroll')->create('offices', function (Blueprint $table) {
            $table->id();
            $table->string('office_name');
        });
        $admin = new User(['fname' => 'Admin', 'lname' => 'User', 'role' => 'Administrator']);
        $admin->id = 1;
        $this->be($admin, 'web');
        $rows = [];
        for ($index = 1; $index <= 60; $index++) {
            $rows[] = [
                'occurred_at' => '2026-10-08 12:00:00', 'module' => 'employees',
                'action' => 'updated', 'outcome' => 'success', 'details' => '{}',
            ];
        }
        DB::connection('mysql')->table('audit_logs')->insert($rows);
        $queries = [];
        DB::connection('mysql')->listen(function ($query) use (&$queries) {
            $queries[] = strtolower($query->sql);
        });
        $controller = (new \ReflectionClass(AuditLogController::class))->newInstanceWithoutConstructor();

        $request = Request::create('/audit-logs', 'GET', ['per_page' => '25']);
        app()->instance('request', $request);
        $first = $controller->index($request)->getData()['logs'];
        $this->assertSame(25, $first->count());
        $this->assertTrue($first->hasMorePages());
        $this->assertStringContainsString('cursor=', $first->nextPageUrl());

        parse_str((string) parse_url($first->nextPageUrl(), PHP_URL_QUERY), $nextQuery);
        $request = Request::create('/audit-logs', 'GET', $nextQuery);
        app()->instance('request', $request);
        $second = $controller->index($request)->getData()['logs'];
        $this->assertSame(25, $second->count());
        $this->assertNotSame($first->items()[0]->id, $second->items()[0]->id);
        $this->assertNotNull($second->previousPageUrl());
        $this->assertFalse(collect($queries)->contains(fn ($sql) => str_contains($sql, 'count(*)') && str_contains($sql, 'audit_logs')));
    }

    public function test_filter_options_search_returns_only_small_authorized_batches(): void
    {
        Schema::connection('mysql')->create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('emp_ID');
            $table->string('fname');
            $table->string('mname')->nullable();
            $table->string('lname');
        });
        DB::connection('mysql')->table('employees')->insert([
            'emp_ID' => 'E-8', 'fname' => 'Juan', 'lname' => 'Cruz',
        ]);
        DB::connection('mysql')->table('audit_logs')->insert([
            'occurred_at' => '2026-10-08 12:00:00', 'module' => 'employees',
            'action' => 'updated', 'outcome' => 'success', 'details' => '{}',
            'actor_name' => 'Maria Santos', 'employee_code' => 'E-8',
        ]);
        $admin = new User(['role' => 'Administrator']);
        $admin->id = 1;
        $this->be($admin, 'web');
        $controller = (new \ReflectionClass(AuditLogController::class))->newInstanceWithoutConstructor();

        $actors = $controller->options(Request::create('/audit-logs/options', 'GET', [
            'type' => 'actor', 'q' => 'Mar',
        ]))->getData(true)['results'];
        $people = $controller->options(Request::create('/audit-logs/options', 'GET', [
            'type' => 'person', 'q' => 'Jua',
        ]))->getData(true)['results'];

        $this->assertSame('Maria Santos', $actors[0]['text']);
        $this->assertSame('E-8', $people[0]['id']);
        $this->assertCount(1, $people);
    }

    public function test_filter_options_are_restricted_to_administrators(): void
    {
        $controller = (new \ReflectionClass(AuditLogController::class))->newInstanceWithoutConstructor();
        $request = Request::create('/audit-logs/options', 'GET', ['type' => 'actor', 'q' => 'Mar']);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $controller->options($request);
    }


    public function test_attendance_audit_failure_rolls_back_create_and_update_but_keeps_receipt(): void
    {
        $this->prepareAttendanceTables();
        DB::connection('mysql')->statement("CREATE TRIGGER reject_success_audit BEFORE INSERT ON audit_logs
            WHEN NEW.outcome = 'success' BEGIN SELECT RAISE(ABORT, 'audit unavailable'); END");
        $controller = (new \ReflectionClass(\App\Http\Controllers\Api\TimeEntryController::class))->newInstanceWithoutConstructor();
        foreach ([false, true] as $existing) {
            if ($existing) {
                DB::connection('mysql')->table('dtrs')->insert([
                    'emp_ID' => 'E-8', 'date' => now()->toDateString(), 'time_in' => '00:00:00',
                ]);
            }
            $request = Request::create('/api/app/log-attendance', 'POST', [
                'action' => 2, 'emp_id' => 'E-8', 'zone_id' => '5',
            ]);
            app()->instance('request', $request);
            $response = app(RecordAuditRequest::class)->handle($request, fn () => $controller->logAttendance($request));
            $this->assertSame(500, $response->getStatusCode());
            $this->assertSame($existing ? 1 : 0, DB::connection('mysql')->table('dtrs')->count());
            if ($existing) $this->assertNull(DB::connection('mysql')->table('dtrs')->value('time_out'));
            $events = DB::connection('mysql')->table('audit_logs')->where('request_id', $request->attributes->get('audit_request_id'))->orderBy('id')->get();
            $this->assertSame(['received', 'error'], $events->pluck('outcome')->all());
            $this->assertArrayNotHasKey('dtr_id', json_decode($events[1]->details, true));
        }
        $this->assertSame(0, DB::connection('mysql')->table('audit_logs')->where('outcome', 'success')->count());
    }

    public function test_verification_actions_are_stable_with_and_without_cached_route_names(): void
    {
        $this->prepareAttendanceTables();
        foreach ([
            'validate-qr' => 'QR Validation',
            'face-claim' => 'Face Verification',
            'admin-face-claim' => 'Admin Face Verification',
        ] as $endpoint => $label) {
            foreach ([null, 'generated::randomCacheName'] as $routeName) {
                foreach ([true, false] as $success) {
                    $request = Request::create('/api/app/'.$endpoint, 'POST', ['qr' => 'private-qr']);
                    $route = new Route('POST', 'api/app/'.$endpoint, fn () => null);
                    if ($routeName) $route->name($routeName);
                    $route->bind($request);
                    $request->setRouteResolver(fn () => $route);
                    app()->instance('request', $request);
                    app(RecordAuditRequest::class)->handle($request, fn () => response()->json($success
                        ? ['match' => true, 'emp_id' => 'E-8', 'is_admin' => $endpoint === 'admin-face-claim']
                        : ['match' => false, 'error' => 'Face did not match'], $success ? 200 : 403));
                    $event = DB::table('audit_logs')->orderByDesc('id')->first();
                    $this->assertSame('dtr_'.str_replace('-', '_', $endpoint), $event->action);
                    $this->assertSame($label, AuditFormatter::actionLabel($event));
                    $description = AuditFormatter::describe($event);
                    $this->assertStringContainsString($label.($success ? ' succeeded' : ' was rejected'), $description);
                    $this->assertStringNotContainsString('The system completed', $description);
                    $this->assertStringNotContainsString('private-qr', $event->details);
                    $this->assertSame($success ? 'success' : 'rejected', $event->outcome);
                    // Historical generated names must render correctly without rewriting evidence.
                    $event->action = 'dtr_generated_oldrandomname';
                    $this->assertSame($label, AuditFormatter::actionLabel($event));
                    $this->assertSame($description, AuditFormatter::describe($event));
                }
            }
        }
        $this->assertSame(12, DB::table('audit_logs')->count());
    }

    public function test_resolved_face_identity_is_attributed_without_logging_qr_or_embedding(): void
    {
        $this->prepareAttendanceTables();
        $request = Request::create('/api/app/face-claim', 'POST', ['qr' => 'private-qr', 'embedding' => 'private-biometric']);
        app()->instance('request', $request);
        app(RecordAuditRequest::class)->handle($request, fn () => response()->json(['match' => true, 'emp_id' => 'E-8']));
        $event = DB::connection('mysql')->table('audit_logs')->first();
        $this->assertSame('E-8', $event->employee_code);
        $this->assertSame(8, $event->employee_id);
        $this->assertSame('success', $event->outcome);
        $this->assertStringNotContainsString('private-qr', $event->details);
        $this->assertStringNotContainsString('private-biometric', $event->details);
    }


    public function test_missing_receipt_prevents_attendance_save(): void
    {
        $this->prepareAttendanceTables();
        DB::connection('mysql')->statement("CREATE TRIGGER reject_receipt BEFORE INSERT ON audit_logs
            WHEN NEW.outcome = 'received' BEGIN SELECT RAISE(ABORT, 'audit unavailable'); END");
        $controller = (new \ReflectionClass(\App\Http\Controllers\Api\TimeEntryController::class))->newInstanceWithoutConstructor();
        $request = Request::create('/api/app/log-attendance', 'POST', ['action' => 1, 'emp_id' => 'E-8', 'zone_id' => '5']);
        app()->instance('request', $request);
        $response = app(RecordAuditRequest::class)->handle($request, fn () => $controller->logAttendance($request));
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame(0, DB::connection('mysql')->table('dtrs')->count());
        $this->assertSame(['error'], DB::connection('mysql')->table('audit_logs')->pluck('outcome')->all());
    }

    public function test_unknown_numeric_attendance_code_is_not_attributed_to_database_id(): void
    {
        $this->prepareAttendanceTables();
        $controller = (new \ReflectionClass(\App\Http\Controllers\Api\TimeEntryController::class))->newInstanceWithoutConstructor();
        $request = Request::create('/api/app/log-attendance', 'POST', ['action' => 1, 'emp_id' => '8', 'zone_id' => '5']);
        app()->instance('request', $request);
        $response = app(RecordAuditRequest::class)->handle($request, fn () => $controller->logAttendance($request));
        $this->assertSame(404, $response->getStatusCode());
        $events = DB::connection('mysql')->table('audit_logs')->orderBy('id')->get();
        $this->assertSame(['received', 'rejected'], $events->pluck('outcome')->all());
        foreach ($events as $event) {
            $this->assertNull($event->employee_id);
            $this->assertNull($event->employee_code);
        }
    }


    public function test_attendance_requires_audit_storage_even_with_legacy_opt_out(): void
    {
        $this->prepareAttendanceTables();
        config()->set('audit.require_attendance_evidence', false);
        Schema::connection('mysql')->drop('audit_logs');
        $controller = (new \ReflectionClass(\App\Http\Controllers\Api\TimeEntryController::class))->newInstanceWithoutConstructor();
        $request = Request::create('/api/app/log-attendance', 'POST', ['action' => 1, 'emp_id' => 'E-8', 'zone_id' => '5']);
        app()->instance('request', $request);
        $response = app(RecordAuditRequest::class)->handle($request, fn () => $controller->logAttendance($request));
        $this->assertSame(500, $response->getStatusCode());
        $this->assertArrayNotHasKey('updated', $response->getData(true));
        $this->assertSame(0, DB::connection('mysql')->table('dtrs')->count());
    }


    public function test_restored_attendance_controller_updates_only_the_newest_dtr_with_matching_audit(): void
    {
        $this->prepareAttendanceTables();
        $date = now()->toDateString();
        $olderId = DB::connection('mysql')->table('dtrs')->insertGetId([
            'emp_ID' => 'E-8', 'date' => $date, 'time_in' => '00:00:00',
        ]);
        $newestId = DB::connection('mysql')->table('dtrs')->insertGetId([
            'emp_ID' => 'E-8', 'date' => $date, 'time_in' => '01:00:00',
        ]);
        $queries = [];
        DB::connection('mysql')->listen(function ($query) use (&$queries) {
            $queries[] = strtolower($query->sql);
        });
        $controller = (new \ReflectionClass(\App\Http\Controllers\Api\TimeEntryController::class))->newInstanceWithoutConstructor();
        $request = Request::create('/api/app/log-attendance', 'POST', [
            'action' => 2, 'emp_id' => 'E-8', 'zone_id' => '5',
        ]);
        app()->instance('request', $request);
        $response = app(RecordAuditRequest::class)->handle($request, fn () => $controller->logAttendance($request));

        $this->assertSame(200, $response->getStatusCode());
        $employeeQueries = array_values(array_filter($queries, fn ($sql) => str_starts_with($sql, 'select') && str_contains($sql, 'from "employees"')));
        // Audit metadata/lock plus the unchanged controller identity lookup.
        $this->assertCount(3, $employeeQueries);
        foreach ($employeeQueries as $sql) $this->assertStringNotContainsString('select *', $sql);
        $dtrQueries = array_values(array_filter($queries, fn ($sql) => str_starts_with($sql, 'select') && str_contains($sql, 'from "dtrs"')));
        $this->assertCount(1, $dtrQueries);
        // The trusted controller still locks/loads all candidate rows, newest first.
        $this->assertStringContainsString('order by', $dtrQueries[0]);
        $this->assertStringNotContainsString('limit 1', $dtrQueries[0]);
        $this->assertNull(DB::connection('mysql')->table('dtrs')->where('id', $olderId)->value('time_out'));
        $this->assertNotNull(DB::connection('mysql')->table('dtrs')->where('id', $newestId)->value('time_out'));
        $events = DB::connection('mysql')->table('audit_logs')->orderBy('id')->get();
        $this->assertSame(['received', 'success'], $events->pluck('outcome')->all());
        $this->assertSame(['E-8', 'E-8'], $events->pluck('employee_code')->all());
        $this->assertSame($newestId, json_decode($events[1]->details, true)['dtr_id']);
    }

    public function test_failed_attendance_write_keeps_receipt_without_success_audit(): void
    {
        $this->prepareAttendanceTables();
        DB::connection('mysql')->statement("CREATE TRIGGER reject_attendance BEFORE INSERT ON dtrs
            BEGIN SELECT RAISE(ABORT, 'attendance unavailable'); END");
        $controller = (new \ReflectionClass(\App\Http\Controllers\Api\TimeEntryController::class))->newInstanceWithoutConstructor();
        $request = Request::create('/api/app/log-attendance', 'POST', [
            'action' => 1, 'emp_id' => 'E-8', 'zone_id' => '5',
        ]);
        app()->instance('request', $request);
        $response = app(RecordAuditRequest::class)->handle($request, fn () => $controller->logAttendance($request));

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame(0, DB::connection('mysql')->table('dtrs')->count());
        $this->assertSame(['received', 'error'], DB::connection('mysql')->table('audit_logs')->orderBy('id')->pluck('outcome')->all());
    }

    public function test_middleware_rolls_back_caught_failure_responses_and_uncaught_errors_after_a_write(): void
    {
        $this->prepareAttendanceTables();
        foreach ([false, true] as $throws) {
            $request = Request::create('/api/app/log-attendance', 'POST', [
                'action' => 1, 'emp_id' => 'E-8', 'zone_id' => '5',
            ]);
            app()->instance('request', $request);
            $failure = response()->json(['error' => 'Server error', 'message' => 'Original controller failure'], 500);
            try {
                $response = app(RecordAuditRequest::class)->handle($request, function () use ($throws, $failure) {
                    \App\Models\Dtr::create([
                        'emp_ID' => 'E-8', 'date' => now()->toDateString(),
                        'time_in' => now()->format('H:i:s'), 'device_id_in' => '5',
                    ]);
                    if ($throws) throw new \RuntimeException('Original uncaught failure');
                    return $failure;
                });
                $this->assertFalse($throws, 'An uncaught downstream error must propagate.');
                $this->assertSame($failure, $response);
            } catch (\RuntimeException $e) {
                $this->assertTrue($throws);
                $this->assertSame('Original uncaught failure', $e->getMessage());
            }
            $this->assertSame(0, DB::table('dtrs')->count());
            $this->assertSame(0, DB::transactionLevel());
            $this->assertFalse($request->attributes->has('audit_attendance_active'));
            $events = DB::table('audit_logs')->where('request_id', $request->attributes->get('audit_request_id'))->orderBy('id')->get();
            $this->assertSame(['received', 'error'], $events->pluck('outcome')->all());
            $this->assertArrayNotHasKey('dtr_id', json_decode($events[1]->details, true));
            $this->assertArrayNotHasKey('attendance_at', json_decode($events[1]->details, true));
        }
    }

    public function test_saved_attendance_time_comes_from_the_row_when_request_crosses_midnight(): void
    {
        $this->prepareAttendanceTables();
        $controller = (new \ReflectionClass(\App\Http\Controllers\Api\TimeEntryController::class))->newInstanceWithoutConstructor();
        $request = Request::create('/api/app/log-attendance', 'POST', ['action' => 1, 'emp_id' => 'E-8', 'zone_id' => '5']);
        app()->instance('request', $request);
        \Carbon\Carbon::setTestNow('2026-10-08 23:59:59');
        try {
            $response = app(RecordAuditRequest::class)->handle($request, function () use ($controller, $request) {
                \Carbon\Carbon::setTestNow('2026-10-09 00:00:00');
                return $controller->logAttendance($request);
            });
            $this->assertSame(200, $response->getStatusCode());
            $receipt = DB::table('audit_logs')->where('outcome', 'received')->first();
            $saved = DB::table('audit_logs')->where('outcome', 'success')->first();
            $this->assertStringStartsWith('2026-10-08', $receipt->occurred_at);
            $this->assertSame('2026-10-09 00:00:00', json_decode($saved->details, true)['attendance_at']);
            $this->assertSame('2026-10-09', DB::table('dtrs')->value('date'));
            $this->assertFalse($request->attributes->has('audit_attendance_active'));
        } finally {
            \Carbon\Carbon::setTestNow();
        }
    }

    public function test_routine_dtr_requests_do_not_add_noise_but_attendance_failures_remain(): void
    {
        foreach (['health', 'check-restriction-level', 'fetch-license', 'fetch-logzones-with-campuses',
            'fetch-logzones', 'fetch-latest-logs', 'dtrlogs'] as $path) {
            foreach ([200] as $status) {
                $request = $this->bindRequest('/api/app/'.$path, 'POST');
                app(RecordAuditRequest::class)->handle($request, fn () => response()->json([], $status));
            }
        }
        $probe = $this->bindRequest('/api/dtrs', 'HEAD');
        app(RecordAuditRequest::class)->handle($probe, fn () => response('', 405));
        $this->assertSame(0, DB::table('audit_logs')->count());
        foreach ([200, 202, 429, 500] as $status) {
            $request = $this->bindRequest('/api/app/log-attendance', 'POST');
            $request->merge(['action' => 1]);
            app(RecordAuditRequest::class)->handle($request, fn () => response()->json([], $status));
        }
        $this->assertSame(['success', 'unchanged', 'rejected', 'error'],
            DB::table('audit_logs')->where('outcome', '!=', 'received')->orderBy('id')->pluck('outcome')->all());
        $this->assertSame(4, DB::table('audit_logs')->where('outcome', 'received')->count());
    }

    public function test_attendance_date_filter_finds_late_uploads_and_keeps_other_modules_on_event_date(): void
    {
        $this->prepareAuditFilterTables();
        foreach ([
            ['module' => 'dtr', 'action' => 'time_in', 'details' => '{"source":"desktop_sync","attendance_at":"2026-10-05 08:00:00"}'],
            ['module' => 'dtr', 'action' => 'time_in', 'details' => '{"source":"desktop_sync","attendance_at":"2026-10-05 09:00:00"}', 'outcome' => 'error'],
            ['module' => 'dtr', 'action' => 'time_in', 'details' => '{"attendance_at":"2026-10-06 08:00:00"}'],
            ['module' => 'employees', 'action' => 'updated', 'details' => '{}'],
        ] as $row) {
            DB::table('audit_logs')->insert($row + [
                'occurred_at' => '2026-10-06 12:00:00', 'outcome' => 'success', 'employee_code' => 'E-8',
            ]);
        }
        $controller = (new \ReflectionClass(AuditLogController::class))->newInstanceWithoutConstructor();
        $request = Request::create('/audit-logs', 'GET', ['person' => 'E-8', 'date_from' => '2026-10-05', 'date_to' => '2026-10-05']);
        app()->instance('request', $request);
        $this->assertSame([2, 1], collect($controller->index($request)->getData()['logs']->items())->pluck('id')->all());
        $request = Request::create('/audit-logs', 'GET', ['date_from' => '2026-10-06', 'date_to' => '2026-10-06']);
        app()->instance('request', $request);
        $this->assertSame([4, 3], collect($controller->index($request)->getData()['logs']->items())->pluck('id')->all());
        \Carbon\Carbon::setTestNow('2026-10-06 12:00:00');
        try {
            $request = Request::create('/audit-logs', 'GET', ['date_range' => 'yesterday']);
            app()->instance('request', $request);
            $this->assertSame([2, 1], collect($controller->index($request)->getData()['logs']->items())->pluck('id')->all());
        } finally {
            \Carbon\Carbon::setTestNow();
        }
    }

    public function test_completed_receipts_and_successful_utility_logs_are_hidden_but_failures_and_unmatched_receipts_remain(): void
    {
        $this->prepareAuditFilterTables();
        foreach ([
            ['action' => 'time_in', 'outcome' => 'received', 'request_id' => 'completed-request'],
            ['action' => 'time_in', 'outcome' => 'success', 'request_id' => 'completed-request'],
            ['action' => 'time_out', 'outcome' => 'received', 'request_id' => 'interrupted-request'],
            ['action' => 'dtr_health', 'outcome' => 'success'],
            ['action' => 'dtr_fetch_latest_logs', 'outcome' => 'error'],
        ] as $row) {
            DB::table('audit_logs')->insert($row + ['module' => 'dtr', 'occurred_at' => '2026-10-06 12:00:00', 'details' => '{}']);
        }
        $request = Request::create('/audit-logs', 'GET', ['module' => 'dtr']);
        app()->instance('request', $request);
        $controller = (new \ReflectionClass(AuditLogController::class))->newInstanceWithoutConstructor();
        $logs = $controller->index($request)->getData()['logs'];
        $this->assertSame([5, 3, 2], collect($logs->items())->pluck('id')->all());
        $this->assertSame(5, DB::table('audit_logs')->count());
    }

    public function test_formatter_distinguishes_kiosk_claim_time_and_server_save_time(): void
    {
        $entry = (object) [
            'module' => 'dtr', 'action' => 'time_in', 'outcome' => 'success', 'reason_code' => null,
            'target_type' => 'employee', 'target_name' => 'Juan Cruz', 'actor_name' => null,
            'occurred_at' => '2026-10-06 12:00:05',
            'details' => '{"source":"desktop_sync","attendance_at":"2026-10-05 08:00:01"}',
        ];
        $text = AuditFormatter::describe($entry);
        $this->assertStringContainsString('8:00:01 AM on October 5, 2026', $text);
        $this->assertStringContainsString('12:00:05 PM on October 6, 2026', $text);
        $this->assertStringContainsString('was saved', $text);
        $entry->outcome = 'error';
        $this->assertStringContainsString('failed', AuditFormatter::describe($entry));
        $this->assertStringNotContainsString('was saved', AuditFormatter::describe($entry));
        $messages = config('audit.messages');
        foreach (['success', 'unchanged', 'rejected', 'error'] as $outcome) unset($messages['desktop_punch_'.$outcome]);
        config()->set('audit.messages', $messages);
        $entry->outcome = 'success';
        $this->assertStringContainsString('was saved', AuditFormatter::describe($entry));
        $this->assertStringContainsString('8:00:01 AM on October 5, 2026', AuditFormatter::describe($entry));
    }

    public function test_hr_kiosk_filter_uses_submitted_registry_id_and_keeps_time_entry_separate(): void
    {
        $this->prepareKioskAuditTables();
        DB::table('f_devices')->insert([
            ['id' => 2, 'device_id' => 'reader-north', 'label' => 'North kiosk'],
            ['id' => 3, 'device_id' => 'reader-south', 'label' => 'South kiosk'],
        ]);
        foreach ([
            ['source' => 'desktop_sync', 'submitted_device_id' => '2', 'device_id' => '3'],
            ['source' => 'desktop_sync', 'submitted_device_id' => '3', 'device_id' => '2'],
            ['zone_id' => 2, 'submitted_device_id' => '2'],
            ['source' => 'desktop_sync', 'device_id' => '2'],
        ] as $details) {
            DB::table('audit_logs')->insert([
                'module' => 'dtr', 'action' => 'time_in', 'outcome' => 'unchanged',
                'occurred_at' => '2026-10-06 12:00:00', 'ip_address' => '192.0.2.10',
                'employee_code' => 'E-8', 'target_name' => 'Juan Cruz',
                'details' => json_encode($details + ['attendance_at' => '2026-10-05 08:00:00']),
            ]);
        }
        DB::table('audit_logs')->insert([
            'module' => 'employees', 'action' => 'time_in', 'outcome' => 'success',
            'occurred_at' => '2026-10-07 12:00:00',
            'details' => '{"source":"desktop_sync","submitted_device_id":"2"}',
        ]);
        $data = $this->kioskAuditPage(['kiosk_id' => '2', 'person' => 'E-8',
            'date_from' => '2026-10-05', 'date_to' => '2026-10-05'])->getData();
        $this->assertSame([1], collect($data['logs']->items())->pluck('id')->all());
        $this->assertSame(1, $data['latestKioskUpload']->id);
        $this->assertStringContainsString('North kiosk', $data['kioskLabels']->get('2'));
        $this->assertStringContainsString('South kiosk', $data['kioskLabels']->get('3'));
        $this->assertSame('North kiosk', $data['kioskLabels']->get('2'));
        $this->assertSame(5, DB::table('audit_logs')->count());
        $html = $this->renderKioskAuditPage($data);
        $this->assertStringContainsString('HR Kiosk Upload History', $html);
        $this->assertStringContainsString('Stored punch: South kiosk', $html);
        $this->assertStringContainsString('Kiosk-reported time', $html);
        $this->assertStringContainsString('Server event:', $html);
        $this->assertStringContainsString('does not establish connection or disconnection', $html);

        $other = $this->kioskAuditPage(['kiosk_id' => '3'])->getData();
        $this->assertSame([2], collect($other['logs']->items())->pluck('id')->all());
        $all = $this->kioskAuditPage()->getData();
        $this->assertSame(5, $all['logs']->count());
        $this->assertNull($all['latestKioskUpload']);
    }

    public function test_hr_kiosk_latest_upload_uses_server_time_across_filters_and_handles_missing_devices(): void
    {
        $this->prepareKioskAuditTables();
        foreach ([
            ['occurred_at' => '2026-10-06 12:00:00', 'employee_code' => 'E-8', 'outcome' => 'success', 'time' => '2026-10-05 08:00:00'],
            ['occurred_at' => '2026-10-07 12:00:00', 'employee_code' => 'E-9', 'outcome' => 'unchanged', 'time' => '2026-10-04 08:00:00'],
            ['occurred_at' => '2026-10-07 12:00:00', 'employee_code' => 'E-9', 'outcome' => 'error', 'time' => '2026-10-04 08:00:00'],
        ] as $row) {
            $time = $row['time'];
            unset($row['time']);
            DB::table('audit_logs')->insert($row + [
                'module' => 'dtr', 'action' => 'time_out', 'reason_code' => 'sync_failed',
                'details' => json_encode(['source' => 'desktop_sync', 'submitted_device_id' => '99', 'attendance_at' => $time,
                    'upload_counts' => ['submitted_rows' => 1]]),
            ]);
        }
        $data = $this->kioskAuditPage(['kiosk_id' => '99', 'person' => 'E-8',
            'date_from' => '2026-10-05', 'date_to' => '2026-10-05'])->getData();
        $this->assertSame([1], collect($data['logs']->items())->pluck('id')->all());
        $this->assertSame(3, $data['latestKioskUpload']->id);
        $this->assertSame('error', $data['latestKioskUpload']->outcome);
        $this->assertSame('2026-10-07 12:00:00', $data['latestKioskUpload']->occurred_at);
        $this->assertTrue($data['kioskLabels']->isEmpty());
        $html = $this->renderKioskAuditPage($data);
        $this->assertStringContainsString('HR Kiosk (name unavailable)', $html);
        $this->assertStringNotContainsString('HR Kiosk #99', $html);
        $this->assertStringContainsString('sync failed', $html);
        $this->assertStringContainsString('Across all dates and employees', $html);
        $this->assertStringContainsString('not rejected by sync', $html);
        $empty = $this->kioskAuditPage(['kiosk_id' => '100'])->getData();
        $this->assertNull($empty['latestKioskUpload']);
        $this->assertSame(0, $empty['logs']->count());
        $this->assertStringContainsString('No recorded uploads for this HR Kiosk', $this->renderKioskAuditPage($empty));
    }

    public function test_hr_kiosk_options_search_labels_ids_and_removed_devices_in_small_admin_only_batches(): void
    {
        $this->prepareKioskAuditTables();
        for ($id = 1; $id <= 30; $id++) {
            DB::table('f_devices')->insert(['id' => $id, 'device_id' => 'reader-'.$id, 'label' => 'North kiosk '.$id]);
        }
        DB::table('f_devices')->insert(['id' => 40, 'device_id' => 'reader-40', 'label' => 'South kiosk']);
        $controller = (new \ReflectionClass(AuditLogController::class))->newInstanceWithoutConstructor();
        $request = $this->bindRequest('/audit-logs/options', 'GET');
        $request->merge(['type' => 'kiosk', 'q' => 'North']);
        $results = $controller->options($request)->getData(true)['results'];
        $this->assertCount(20, $results);
        foreach ($results as $result) $this->assertStringContainsString('North kiosk', $result['text']);
        $request->merge(['q' => '40']);
        $this->assertSame([['id' => '40', 'text' => 'South kiosk']], $controller->options($request)->getData(true)['results']);
        $request->merge(['q' => '99']);
        $this->assertSame([['id' => '99', 'text' => 'HR Kiosk (name unavailable)']], $controller->options($request)->getData(true)['results']);
        $request->merge(['q' => 'North%']);
        $this->assertSame([], $controller->options($request)->getData(true)['results']);
        $user = new User(['role' => 'HR Administrator']);
        $user->id = 2;
        $this->be($user, 'web');
        try {
            $controller->options($request);
            $this->fail('Kiosk options must remain administrator-only.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function test_time_entry_zone_names_use_signed_registry_ids_and_preserve_audit_details(): void
    {
        $this->prepareKioskAuditTables();
        Schema::create('logzones', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('label')->nullable();
        });
        DB::table('f_devices')->insert([
            ['id' => 2, 'device_id' => 'reader-north', 'label' => 'North kiosk'],
            ['id' => 3, 'device_id' => 'reader-blank', 'label' => ''],
            ['id' => 5, 'device_id' => 'reader-five', 'label' => 'Different positive device'],
        ]);
        DB::table('logzones')->insert([
            ['id' => -5, 'label' => 'Library <East>'],
            ['id' => 2, 'label' => 'Different positive zone'],
            ['id' => -6, 'label' => 'Unused zone'],
        ]);
        foreach ([
            ['source' => 'desktop_sync', 'submitted_device_id' => '2', 'device_id' => '2', 'zone_id' => -5],
            ['zone_id' => 2], ['zone_id' => -5], ['zone_id' => -99], ['zone_id' => 3],
            ['zone_id' => ['invalid']],
        ] as $details) {
            DB::table('audit_logs')->insert([
                'module' => 'dtr', 'action' => 'time_in', 'outcome' => 'success',
                'occurred_at' => '2026-10-06 12:00:00', 'details' => json_encode($details),
            ]);
        }
        $before = DB::table('audit_logs')->orderBy('id')->get()->toJson();
        $data = $this->kioskAuditPage()->getData();
        $this->assertStringContainsString('North kiosk', $data['timeEntryLabels']->get('2'));
        $this->assertStringContainsString('Library <East>', $data['timeEntryLabels']->get('-5'));
        $this->assertSame('Time Entry zone', $data['timeEntryLabels']->get('3'));
        $this->assertFalse($data['timeEntryLabels']->has('-6'));
        $html = $this->renderKioskAuditPage($data);
        $this->assertStringContainsString('<th>Kiosk / Zone</th>', $html);
        $this->assertStringContainsString('Library &lt;East&gt;', $html);
        $this->assertStringContainsString('Time Entry zone (name unavailable)', $html);
        $this->assertStringNotContainsString('Time Entry zone #-99', $html);
        foreach (['zone_id', 'submitted_device_id', 'device_id'] as $key) {
            $this->assertStringNotContainsString('&quot;'.$key.'&quot;', $html);
        }
        $this->assertStringContainsString('North kiosk', $html);
        $this->assertStringNotContainsString('Different positive zone', $html);
        $this->assertStringNotContainsString('Different positive device', $html);
        $this->assertStringNotContainsString('Unused zone', $html);
        $this->assertSame(4, substr_count($html, '<div class="audit-meta">Time Entry</div>'));
        $this->assertSame($before, DB::table('audit_logs')->orderBy('id')->get()->toJson());
    }

    public function test_invalid_sync_payload_has_unknown_counts_and_no_invented_receipt_time(): void
    {
        $request = Request::create('/api/dtrs', 'POST', [], [], [], [], 'invalid json');
        app()->instance('request', $request);
        app(\App\Services\Audit\AuditDtrSyncRecorder::class)->failed(
            $request, response()->json(['error' => 'Invalid JSON data'], 400), null);
        $event = DB::table('audit_logs')->first();
        $details = json_decode($event->details, true);
        $this->assertSame('rejected', $event->outcome);
        $this->assertNull($details['upload_counts']);
        $this->assertNull($details['submitted_rows']);
        $this->assertNull($details['server_received_at']);
        $this->assertSame('api/dtrs', $details['endpoint']);
    }

    public function test_desktop_audit_parser_counts_csv_slots_and_duplicates_without_changing_punches(): void
    {
        $data = [
            ['emp_ID' => 'E-8', 'date' => '2026-10-12', 'time_in' => '08:00:00,08:00:00,bad,', 'device_id_in' => '2,3'],
            ['emp_ID' => 'e-8', 'date' => '2026-10-12', 'time_in' => '08:00:00', 'time_out' => '17:00:00'],
            ['emp_ID' => 'E-8', 'date' => '2026-02-30', 'time_in' => '09:00:00'],
            ['emp_ID' => 'E-8'], 'ignored',
            ['emp_ID' => 'E-8', 'date' => '2026-10-12', 'time_in' => ['not-scalar']],
        ];
        $sync = app(\App\Services\Audit\AuditDtrSyncRecorder::class);
        $punches = $sync->punches($data, $counts);
        $this->assertSame($sync->punches($data), $punches);
        $this->assertSame([
            'submitted_rows' => 6, 'audit_valid_rows' => 2, 'audit_skipped_rows' => 4,
            'submitted_punches' => 7, 'audit_valid_punches' => 4, 'audit_skipped_punches' => 3,
            'unique_punches' => 2, 'duplicate_submitted_punches' => 2,
        ], $counts);
        $this->assertSame('2', array_values($punches)[0]['device_id']);
    }

    public function test_desktop_completion_counts_and_receipt_time_enrich_existing_entries(): void
    {
        $this->prepareAttendanceTables();
        $data = [['emp_ID' => 'E-8', 'date' => '2026-10-12',
            'time_in' => '08:00:00,08:00:00,09:00:00,10:00:00', 'device_id_in' => '2,2,2,2']];
        $request = Request::create('/api/dtrs?qr=private-query', 'POST', [], [], [], [], json_encode($data));
        app()->instance('request', $request);
        $sync = app(\App\Services\Audit\AuditDtrSyncRecorder::class);
        \Carbon\Carbon::setTestNow('2026-10-12 11:00:00');
        try {
            $sync->receipt($request, $data);
            $punches = $sync->punches($data);
            DB::table('dtrs')->insert(['emp_ID' => 'E-8', 'date' => '2026-10-12', 'time_in' => '08:00:00', 'device_id_in' => '1']);
            $before = $sync->snapshot($punches);
            DB::table('dtrs')->update(['time_in' => '08:00:00,09:00:00', 'device_id_in' => '1,2']);
            \Carbon\Carbon::setTestNow('2026-10-12 11:00:30');
            $sync->committed($request, $punches, $before, count($data));
            $events = DB::table('audit_logs')->orderBy('id')->get();
            $this->assertCount(4, $events); // Existing receipt + one entry per unique punch.
            $this->assertSame(['received', 'unchanged', 'success', 'rejected'], $events->pluck('outcome')->all());
            $receipt = json_decode($events[0]->details, true);
            $this->assertArrayNotHasKey('newly_saved_punches', $receipt['upload_counts']);
            foreach ($events->skip(1) as $event) {
                $details = json_decode($event->details, true);
                $this->assertSame(1, $details['upload_counts']['newly_saved_punches']);
                $this->assertSame(1, $details['upload_counts']['already_stored_punches']);
                $this->assertSame(1, $details['upload_counts']['not_saved_punches']);
                $this->assertSame(1, $details['upload_counts']['duplicate_submitted_punches']);
                $this->assertSame('api/dtrs', $details['endpoint']);
                $this->assertSame('2026-10-12 11:00:00.000000', $details['server_received_at']);
                $this->assertSame('2026-10-12 11:00:30', substr($event->occurred_at, 0, 19));
                // Shown by the Details panel instead of stored on every row.
                $this->assertArrayNotHasKey('count_basis', $details);
                $this->assertStringNotContainsString('private-query', $event->details);
            }
            $this->assertSame(10800, json_decode($events[1]->details, true)['upload_lag_seconds']);
            // Failure after completion evidence must not retain counts from a rolled-back transaction.
            $sync->failed($request, response()->json(['error' => 'DTR sync failed'], 500), null);
            foreach (DB::table('audit_logs')->where('outcome', 'error')->get() as $event) {
                $this->assertArrayNotHasKey('newly_saved_punches', json_decode($event->details, true)['upload_counts']);
                $this->assertArrayNotHasKey('dtr_id', json_decode($event->details, true));
            }
        } finally {
            \Carbon\Carbon::setTestNow();
        }
    }

    public function test_desktop_timing_flags_use_receipt_time_with_configurable_strict_boundaries(): void
    {
        $this->prepareAttendanceTables();
        config()->set('audit.desktop_sync.future_tolerance_seconds', 300);
        config()->set('audit.desktop_sync.late_upload_seconds', 86400);
        $data = [
            ['emp_ID' => 'E-8', 'date' => '2026-10-12', 'time_in' => '08:05:00,08:05:01'],
            ['emp_ID' => 'E-8', 'date' => '2026-10-11', 'time_in' => '08:00:00,07:59:59'],
        ];
        $request = Request::create('/api/dtrs', 'POST', [], [], [], [], json_encode($data));
        app()->instance('request', $request);
        $sync = app(\App\Services\Audit\AuditDtrSyncRecorder::class);
        \Carbon\Carbon::setTestNow('2026-10-12 08:00:00');
        try {
            $sync->receipt($request, $data);
            \Carbon\Carbon::setTestNow('2026-10-12 08:10:00');
            $sync->failed($request, response()->json(['error' => 'DTR sync failed'], 500), null);
            $events = DB::table('audit_logs')->where('outcome', 'error')->orderBy('id')->get();
            $details = $events->map(fn ($event) => json_decode($event->details, true));
            $this->assertSame([[], ['future_dated'], [], ['late_upload']], $details->pluck('timing_flags')->all());
            $this->assertSame([-300, -301, 86400, 86401], $details->pluck('upload_lag_seconds')->all());
            foreach ($details as $detail) $this->assertTrue($detail['timing_flags_informational']);
            $this->assertStringContainsString('Informational timing flag', AuditFormatter::describe($events[1]));
            $this->assertStringContainsString('configured delay threshold', AuditFormatter::describe($events[3]));
            config()->set('audit.desktop_sync.future_tolerance_seconds', 301);
            config()->set('audit.desktop_sync.late_upload_seconds', 86401);
            $sync->failed($request, response()->json(['error' => 'DTR sync failed'], 500), null);
            foreach (DB::table('audit_logs')->orderByDesc('id')->limit(4)->get() as $event) {
                $this->assertSame([], json_decode($event->details, true)['timing_flags']);
            }
        } finally {
            \Carbon\Carbon::setTestNow();
        }
    }

    public function test_real_face_mismatches_are_visible_for_the_qr_employee_without_verifying_identity(): void
    {
        $this->prepareAttendanceTables();
        Schema::table('employees', function (Blueprint $table) {
            $table->integer('stat_1')->default(1);
            $table->text('face_embeddings')->nullable();
        });
        $stored = array_fill(0, 128, 0.0);
        $stored[0] = 1.0;
        $probe = array_fill(0, 128, 0.0);
        $probe[1] = 1.0;
        DB::table('employees')->where('emp_ID', 'E-8')->update([
            'face_embeddings' => json_encode(['vecs' => [$stored], 'centroid' => $stored]),
        ]);
        $controller = (new \ReflectionClass(\App\Http\Controllers\Api\TimeEntryController::class))->newInstanceWithoutConstructor();
        foreach (['face-claim' => 'faceClaim', 'admin-face-claim' => 'adminFaceClaim'] as $endpoint => $method) {
            $request = Request::create('/api/app/'.$endpoint, 'POST', [
                'qr' => shortEncrypt('E-8'), 'embedding' => $probe, 'emp_id' => 'UNRELATED',
            ]);
            $route = new Route('POST', 'api/app/'.$endpoint, fn () => null);
            $route->bind($request);
            $request->setRouteResolver(fn () => $route);
            app()->instance('request', $request);
            $response = app(RecordAuditRequest::class)->handle($request, fn () => $controller->$method($request));
            $this->assertSame(200, $response->getStatusCode());
            $this->assertFalse($response->getData(true)['match']);
            $this->assertSame('Face did not match.', $response->getData(true)['message']);
            $this->assertArrayNotHasKey('emp_id', $response->getData(true));
            $event = DB::table('audit_logs')->orderByDesc('id')->first();
            $this->assertSame('E-8', $event->employee_code);
            $this->assertSame('rejected', $event->outcome);
            $this->assertSame('face_mismatch', $event->reason_code);
            $details = json_decode($event->details, true);
            $this->assertSame('qr_claim_not_face_verified', $details['identity_basis']);
            $this->assertArrayNotHasKey('embedding', $details);
            $this->assertArrayNotHasKey('qr', $details);
            $this->assertStringContainsString('QR claim; face not verified', AuditFormatter::describe($event));
            $this->assertStringContainsString('Face did not match.', AuditFormatter::describe($event));
        }
        // The rejection must survive the same person filter that hid unassigned entries.
        $this->prepareAuditFilterTables();
        $request = Request::create('/audit-logs', 'GET', ['person' => 'E-8']);
        app()->instance('request', $request);
        $page = (new \ReflectionClass(AuditLogController::class))->newInstanceWithoutConstructor()->index($request);
        $this->assertCount(2, $page->getData()['logs']);
        $html = $this->renderKioskAuditPage($page->getData());
        $this->assertStringContainsString('Face did not match.', $html);
        $this->assertStringContainsString('Juan Cruz (QR claim; face not verified)', $html);
        $this->assertSame(0, DB::table('dtrs')->count());
    }

    public function test_failed_qr_and_face_identity_requires_controller_evidence_and_metadata_is_safe(): void
    {
        $this->prepareAttendanceTables();
        foreach (['validate-qr', 'face-claim', 'admin-face-claim', 'face-verify', 'admin-face-verify'] as $endpoint) {
            $request = Request::create('/api/app/'.$endpoint.'?secret=private-query', 'POST', [
                'emp_id' => 'E-8', 'qr' => 'private-qr', 'embedding' => 'private-biometric',
            ]);
            $route = new Route('POST', 'api/app/'.$endpoint, fn () => null);
            $request->setRouteResolver(fn () => $route);
            app()->instance('request', $request);
            app(RecordAuditRequest::class)->handle($request, fn () => response()->json(['match' => false, 'message' => 'Face did not match.']));
            $event = DB::table('audit_logs')->orderByDesc('id')->first();
            $this->assertNull($event->employee_id);
            $this->assertNull($event->employee_code);
            $this->assertSame('rejected', $event->outcome);
            $details = json_decode($event->details, true);
            $this->assertSame('time_entry_api', $details['source']);
            $this->assertSame('api/app/'.$endpoint, $details['endpoint']);
            $this->assertStringNotContainsString('private-', $event->details);
        }
        // A safely exposed controller identity can be used even with a failed check.
        app(RecordAuditRequest::class)->handle($request, fn () => response()->json([
            'match' => false, 'emp_id' => 'E-8', 'message' => 'Face did not match.',
        ]));
        $event = DB::table('audit_logs')->orderByDesc('id')->first();
        $this->assertSame('E-8', $event->employee_code);
        $this->assertSame(8, $event->employee_id);
        $this->assertSame('rejected', $event->outcome);
    }

    private function prepareKioskAuditTables(): void
    {
        $this->prepareAuditFilterTables();
        Schema::create('f_devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_id');
            $table->string('label')->nullable();
        });
    }

    private function kioskAuditPage(array $filters = [])
    {
        $request = Request::create('/audit-logs', 'GET', $filters);
        app()->instance('request', $request);
        $controller = (new \ReflectionClass(AuditLogController::class))->newInstanceWithoutConstructor();
        return $controller->index($request);
    }

    private function renderKioskAuditPage(array $data): string
    {
        // Render the actual page body without the unrelated deployment layout queries.
        app('view')->setDispatcher(new \Illuminate\Events\Dispatcher(app()));
        $compiledPath = sys_get_temp_dir().'/hris-kiosk-test-views';
        if (!is_dir($compiledPath)) mkdir($compiledPath, 0777, true);
        config()->set('view.compiled', $compiledPath);
        app()->forgetInstance('blade.compiler');
        \Illuminate\Support\Facades\Blade::clearResolvedInstance('blade.compiler');
        app('view.engine.resolver')->forget('blade');
        $body = str_replace(["@extends('layouts.master')", "@section('body')", '@endsection'], '',
            file_get_contents(resource_path('views/audit-logs/index.blade.php')));
        return \Illuminate\Support\Facades\Blade::render($body,
            $data + ['errors' => new \Illuminate\Support\ViewErrorBag()]);
    }

    public function test_android_utility_failures_are_recorded_visible_and_successful_reads_stay_quiet(): void
    {
        $paths = ['health', 'check-restriction-level', 'fetch-license', 'fetch-logzones-with-campuses',
            'fetch-logzones', 'fetch-latest-logs', 'dtrlogs'];
        foreach ($paths as $path) {
            foreach ([
                [200, ['ok' => true], null],
                [400, ['error' => 'Missing empId'], 'rejected'],
                [429, ['error' => 'Too many requests'], 'rejected'],
                [500, ['error' => 'Server error'], 'error'],
            ] as [$status, $body, $outcome]) {
                $before = DB::table('audit_logs')->count();
                $request = $this->androidAuditRequest($path);
                app(RecordAuditRequest::class)->handle($request, fn () => response()->json($body, $status));
                $this->assertSame($before + ($outcome ? 1 : 0), DB::table('audit_logs')->count());
                if ($outcome) $this->assertSame($outcome, DB::table('audit_logs')->orderByDesc('id')->value('outcome'));
            }
        }
        foreach ([
            ['check-restriction-level', ['allowed' => false, 'level' => 2], 200, 'attendance_disabled'],
            ['check-restriction-level', ['allowed' => false, 'level' => 1], 200, 'outside_attendance_window'],
            ['fetch-license', ['ok' => false, 'message' => 'License not configured'], 500, 'license_not_configured'],
        ] as [$path, $body, $status, $reason]) {
            $request = $this->androidAuditRequest($path);
            app(RecordAuditRequest::class)->handle($request, fn () => response()->json($body, $status));
            $this->assertSame($reason, DB::table('audit_logs')->orderByDesc('id')->value('reason_code'));
        }
        $this->prepareAuditFilterTables();
        $page = $this->kioskAuditPage(['module' => 'dtr'])->getData();
        // Pagination is bounded; every shown entry must be a non-success utility result.
        $this->assertSame(24, $page['logs']->count());
        $html = $this->renderKioskAuditPage($page);
        $this->assertStringContainsString('Attendance Availability', $html);
        $this->assertStringContainsString('License Check', $html);
        $this->assertStringContainsString('Latest Logs', $html);
        $this->assertStringNotContainsString('The system completed', $html);
        $this->assertSame(24, DB::table('audit_logs')->count());
    }

    public function test_real_admin_face_authorization_and_password_denials_have_correct_outcomes(): void
    {
        $this->prepareAndroidIdentityTables();
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->text('hr_kiosk')->nullable();
            $table->text('hrk_pw')->nullable();
        });
        DB::table('settings')->insert(['hr_kiosk' => 'SOMEONE-ELSE',
            'hrk_pw' => \Illuminate\Support\Facades\Hash::make('test-only-password')]);
        $controller = (new \ReflectionClass(\App\Http\Controllers\Api\TimeEntryController::class))->newInstanceWithoutConstructor();
        $embedding = array_fill(0, 128, 0.0);
        $embedding[0] = 1.0;
        $request = $this->androidAuditRequest('admin-face-claim', ['qr' => shortEncrypt('E-8'), 'embedding' => $embedding]);
        $response = app(RecordAuditRequest::class)->handle($request, fn () => $controller->adminFaceClaim($request));
        $this->assertTrue($response->getData(true)['match']);
        $this->assertFalse($response->getData(true)['is_admin']);
        $event = DB::table('audit_logs')->orderByDesc('id')->first();
        $this->assertSame('rejected', $event->outcome);
        $this->assertSame('access_denied', $event->reason_code);
        $this->assertSame('E-8', $event->employee_code);
        $this->assertStringContainsString('was rejected', AuditFormatter::describe($event));
        // is_admin=false is expected on a normal employee QR; it is not an admin denial.
        $request = $this->androidAuditRequest('validate-qr', ['qr' => shortEncrypt('E-8'), 'mode' => 'emp']);
        $response = app(RecordAuditRequest::class)->handle($request, fn () => $controller->validateQr($request));
        $this->assertTrue($response->getData(true)['valid']);
        $this->assertSame('success', DB::table('audit_logs')->orderByDesc('id')->value('outcome'));
        foreach ([
            ['wrong', 'rejected', 'invalid_credentials'],
            [null, 'error', 'password_not_configured'],
        ] as [$password, $outcome, $reason]) {
            if ($password === null) DB::table('settings')->update(['hrk_pw' => null]);
            $request = $this->androidAuditRequest('admin-pass-verify', ['password' => $password ?? 'test-only-password']);
            app(RecordAuditRequest::class)->handle($request, fn () => $controller->adminPassVerify($request));
            $event = DB::table('audit_logs')->orderByDesc('id')->first();
            $this->assertSame($outcome, $event->outcome);
            $this->assertSame($reason, $event->reason_code);
            $this->assertStringNotContainsString('test-only-password', $event->details);
            $this->assertStringNotContainsString('"password"', $event->details);
        }
    }

    public function test_qr_rejections_and_encrypted_dtr_views_keep_only_controller_resolved_subjects(): void
    {
        $this->prepareAndroidIdentityTables();
        Schema::create('settings', function (Blueprint $table) { $table->text('hr_kiosk')->nullable(); });
        DB::table('settings')->insert(['hr_kiosk' => 'SOMEONE-ELSE']);
        $controller = (new \ReflectionClass(\App\Http\Controllers\Api\TimeEntryController::class))->newInstanceWithoutConstructor();
        foreach ([
            ['admin', ['stat_1' => 1], 'access_denied'],
            ['emp', ['face_embeddings' => null], 'face_not_registered'],
            ['emp', ['stat_1' => 0], 'identity_not_found_or_inactive'],
        ] as [$mode, $update, $reason]) {
            DB::table('employees')->where('emp_ID', 'E-8')->update($update);
            $qr = shortEncrypt('E-8');
            $request = $this->androidAuditRequest('validate-qr', ['qr' => $qr, 'mode' => $mode, 'emp_id' => 'UNRELATED']);
            app(RecordAuditRequest::class)->handle($request, fn () => $controller->validateQr($request));
            $event = DB::table('audit_logs')->orderByDesc('id')->first();
            $this->assertSame('E-8', $event->employee_code);
            $this->assertSame('rejected', $event->outcome);
            $this->assertSame($reason, $event->reason_code);
            $this->assertStringNotContainsString($qr, $event->details);
            $this->assertSame('qr_claim_not_face_verified', json_decode($event->details, true)['identity_basis']);
        }
        // A decrypted employee reference must work for failed downloads without storing it.
        $request = $this->androidAuditRequest('download-dtr', ['e' => shortEncrypt('E-8')]);
        app(RecordAuditRequest::class)->handle($request, fn () => $controller->downloadDtr($request));
        $event = DB::table('audit_logs')->orderByDesc('id')->first();
        $this->assertSame('E-8', $event->employee_code);
        $this->assertSame('identity_not_found_or_inactive', $event->reason_code);
        $this->assertSame('encrypted_employee_reference', json_decode($event->details, true)['identity_basis']);
        $request = $this->androidAuditRequest('validate-qr', ['qr' => shortEncrypt('DOES-NOT-EXIST'), 'emp_id' => 'E-8']);
        app(RecordAuditRequest::class)->handle($request, fn () => $controller->validateQr($request));
        $this->assertNull(DB::table('audit_logs')->orderByDesc('id')->value('employee_code'));
        // DTR reader resolves the encrypted route parameter before building its view.
        $reader = (new \ReflectionClass(\App\Http\Controllers\Api\TimeEntryDtrController::class))->newInstanceWithoutConstructor();
        $request = Request::create('/api/app-dtr/'.shortEncrypt('E-8'), 'POST');
        $route = new Route('POST', 'api/app-dtr/{empid}', fn () => null);
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);
        app()->instance('request', $request);
        app(RecordAuditRequest::class)->handle($request, function () use ($reader) {
            $view = $reader->dtrRead(shortEncrypt('E-8'));
            $this->assertSame('E-8', $view->getData()['empdata']->emp_ID);
            // The HTTP router converts the controller view to a response.
            return response('', 200);
        });
        $this->assertSame('E-8', DB::table('audit_logs')->orderByDesc('id')->value('employee_code'));
    }

    public function test_early_android_http_errors_are_audited_by_the_real_kernel_without_request_data(): void
    {
        config()->set('cache.default', 'array');
        config()->set('cache.limiter', 'array');
        config()->set('logging.default', 'null');
        $apiLimiter = app(\Illuminate\Cache\RateLimiter::class)->limiter('api');
        $limiter = new \Illuminate\Cache\RateLimiter(app('cache')->store('array'));
        $limiter->for('api', $apiLimiter);
        app()->instance(\Illuminate\Cache\RateLimiter::class, $limiter);
        $kernel = app(\Illuminate\Contracts\Http\Kernel::class);
        foreach ([
            ['CONTENT_LENGTH' => (string) (PHP_INT_MAX - 1)],
            [],
        ] as $server) {
            $request = Request::create('/api/app/admin-pass-verify', 'POST', [], [], [], $server + ['HTTP_ACCEPT' => 'application/json']);
            $response = $kernel->handle($request);
            $this->assertSame($server ? 413 : 422, $response->getStatusCode());
            $event = DB::table('audit_logs')->orderByDesc('id')->first();
            $this->assertNotNull($event);
            $this->assertSame('rejected', $event->outcome);
            $this->assertSame($server ? 'request_too_large' : 'validation_failed', $event->reason_code);
            $this->assertStringNotContainsString('"password"', $event->details);
        }
        $this->assertSame(2, DB::table('audit_logs')->count());
    }


    public function test_android_registration_directory_and_dtr_failures_preserve_safe_audit_evidence(): void
    {
        $this->prepareAndroidIdentityTables();
        $controller = (new \ReflectionClass(\App\Http\Controllers\Api\TimeEntryController::class))->newInstanceWithoutConstructor();
        foreach ([
            [[], 'validation_failed', null],
            [['emp_ID' => 'UNKNOWN', 'enroller_ID' => 'E-8'], 'identity_not_found', null],
            [['emp_ID' => 'E-8', 'enroller_ID' => 'UNKNOWN'], 'invalid_enroller', 'E-8'],
            [['emp_ID' => 'E-8', 'enroller_ID' => 'E-8', 'embedding' => [1]], 'biometric_validation_failed', 'E-8'],
            [['emp_ID' => 'E-8', 'enroller_ID' => 'E-8', 'embeddings' => []], 'biometric_validation_failed', 'E-8'],
        ] as [$input, $reason, $subject]) {
            $request = $this->androidAuditRequest('face-register', $input);
            $response = app(RecordAuditRequest::class)->handle($request, fn () => $controller->faceRegister($request));
            $this->assertGreaterThanOrEqual(400, $response->getStatusCode());
            $event = DB::table('audit_logs')->orderByDesc('id')->first();
            $this->assertSame('rejected', $event->outcome);
            $this->assertSame($reason, $event->reason_code);
            $this->assertSame($subject, $event->employee_code);
            $this->assertArrayNotHasKey('embedding', json_decode($event->details, true));
            $this->assertArrayNotHasKey('embeddings', json_decode($event->details, true));
            $this->assertStringContainsString('Face Registration was rejected', AuditFormatter::describe($event));
        }
        DB::table('employees')->update(['stat_1' => 0]);
        $request = $this->androidAuditRequest('fetch-employees');
        $response = app(RecordAuditRequest::class)->handle($request, fn () => $controller->fetchEmployees($request));
        $this->assertSame([], $response->getData(true));
        $event = DB::table('audit_logs')->orderByDesc('id')->first();
        $this->assertSame('unchanged', $event->outcome);
        $this->assertSame('no_employees_available', $event->reason_code);

        foreach ([[], ['e' => 'invalid-reference']] as $input) {
            $request = $this->androidAuditRequest('download-dtr', $input + ['emp_id' => 'E-8']);
            app(RecordAuditRequest::class)->handle($request, fn () => $controller->downloadDtr($request));
            $event = DB::table('audit_logs')->orderByDesc('id')->first();
            $this->assertSame('rejected', $event->outcome);
            $this->assertSame('invalid_employee_reference', $event->reason_code);
            $this->assertNull($event->employee_code);
            $this->assertStringNotContainsString('invalid-reference', $event->details);
        }
        foreach ([
            [new \Symfony\Component\HttpKernel\Exception\HttpException(503), 'error', 'service_unavailable', 503],
            [new \Illuminate\Database\Eloquent\ModelNotFoundException(), 'rejected', 'not_found', 404],
        ] as [$exception, $outcome, $reason, $status]) {
            $request = $this->androidAuditRequest('fetch-license');
            try {
                app(RecordAuditRequest::class)->handle($request, function () use ($exception) { throw $exception; });
                $this->fail('The downstream exception must be rethrown.');
            } catch (\Throwable $caught) {
                $this->assertSame($exception, $caught);
            }
            $event = DB::table('audit_logs')->orderByDesc('id')->first();
            $this->assertSame($outcome, $event->outcome);
            $this->assertSame($reason, $event->reason_code);
            $this->assertSame($status, json_decode($event->details, true)['http_status']);
        }
        $request = Request::create('/api/dtrs', 'HEAD');
        app()->instance('request', $request);
        app(RecordAuditRequest::class)->handle($request, fn () => response('', 500));
        $this->assertSame('error', DB::table('audit_logs')->orderByDesc('id')->value('outcome'));
    }

    public function test_early_encrypted_dtr_rejection_cannot_leak_or_spoof_its_employee_reference(): void
    {
        $this->prepareAndroidIdentityTables();
        $reference = shortEncrypt('E-8');
        $request = Request::create('/api/app-dtr/'.$reference, 'POST', ['emp_id' => 'E-8']);
        app()->instance('request', $request);
        // No route/controller has run when the global request-size guard rejects a request.
        app(RecordAuditRequest::class)->handle($request, fn () => response('', 413));
        $event = DB::table('audit_logs')->orderByDesc('id')->first();
        $this->assertSame('dtr_app_dtr_read', $event->action);
        $this->assertSame('request_too_large', $event->reason_code);
        $this->assertNull($event->employee_code);
        $this->assertStringNotContainsString($reference, json_encode($event));
    }


    public function test_desktop_http_rejections_keep_status_reason_and_safe_per_punch_evidence(): void
    {
        $this->prepareAttendanceTables();
        foreach ([
            [400, 'rejected', 'invalid_sync_payload'],
            [403, 'rejected', 'access_denied'],
            [413, 'rejected', 'request_too_large'],
            [429, 'rejected', 'rate_limited'],
            [500, 'error', 'sync_failed'],
            [503, 'error', 'service_unavailable'],
        ] as [$status, $outcome, $reason]) {
            foreach ([[], [['emp_ID' => 'E-8', 'date' => '2026-10-05', 'time_in' => '08:00:00', 'device_id_in' => '2']]] as $payload) {
                $request = Request::create('/api/dtrs', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload));
                app()->instance('request', $request);
                $response = app(RecordAuditRequest::class)->handle($request, fn () => response()->json(['error' => 'test failure'], $status));
                $this->assertSame($status, $response->getStatusCode());
                $event = DB::table('audit_logs')->orderByDesc('id')->first();
                $this->assertSame($outcome, $event->outcome);
                $this->assertSame($reason, $event->reason_code);
                $this->assertSame($status, json_decode($event->details, true)['http_status']);
                $this->assertArrayNotHasKey('dtr_id', json_decode($event->details, true));
            }
        }
        // Invalid JSON must not bypass auditing when downstream middleware throws.
        $request = Request::create('/api/dtrs', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid json');
        app()->instance('request', $request);
        $exception = new \Symfony\Component\HttpKernel\Exception\HttpException(413, 'private-error-details');
        $before = DB::table('audit_logs')->count();
        try {
            app(RecordAuditRequest::class)->handle($request, function () use ($exception) { throw $exception; });
            $this->fail('HTTP exception must be preserved.');
        } catch (\Throwable $caught) {
            $this->assertSame($exception, $caught);
        }
        $this->assertSame($before + 1, DB::table('audit_logs')->count());
        $event = DB::table('audit_logs')->orderByDesc('id')->first();
        $this->assertSame('rejected', $event->outcome);
        $this->assertSame('request_too_large', $event->reason_code);
        $this->assertSame('HttpException', json_decode($event->details, true)['exception_type']);
        $this->assertStringNotContainsString('private-error-details', $event->details);
    }

    public function test_duplicate_desktop_punch_keeps_every_submitted_kiosk_visible_without_id_exposure(): void
    {
        $this->prepareAttendanceTables();
        $this->prepareKioskAuditTables();
        DB::table('f_devices')->insert([
            ['id' => 2, 'device_id' => 'reader-north', 'label' => 'North kiosk'],
            ['id' => 3, 'device_id' => 'reader-south', 'label' => 'South kiosk'],
        ]);
        $payload = [
            ['emp_ID' => 'E-8', 'date' => '2026-10-05', 'time_in' => '08:00:00,08:00:00', 'device_id_in' => '2,3'],
            ['emp_ID' => 'e-8', 'date' => '2026-10-05', 'time_in' => '08:00:00', 'device_id_in' => '3'],
        ];
        $request = Request::create('/api/dtrs', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload));
        app()->instance('request', $request);
        app(RecordAuditRequest::class)->handle($request, fn () => response()->json(['error' => 'Too many requests'], 429));
        $event = DB::table('audit_logs')->where('outcome', '!=', 'received')->first();
        $details = json_decode($event->details, true);
        $this->assertSame(['2', '3'], $details['submitted_device_ids']);
        $this->assertSame('2', $details['submitted_device_id']);
        $this->assertSame(1, $details['upload_counts']['unique_punches']);
        $this->assertSame(2, $details['upload_counts']['duplicate_submitted_punches']);
        $this->assertSame(1, DB::table('audit_logs')->where('outcome', '!=', 'received')->count());
        foreach (['2', '3'] as $kiosk) {
            $page = $this->kioskAuditPage(['kiosk_id' => $kiosk, 'person' => 'E-8'])->getData();
            $this->assertSame([$event->id], collect($page['logs']->items())->pluck('id')->all());
            $this->assertSame($event->id, $page['latestKioskUpload']->id);
            $html = $this->renderKioskAuditPage($page);
            $this->assertStringContainsString('North kiosk, South kiosk', $html);
            $this->assertStringNotContainsString('submitted_device_ids', $html);
            $this->assertStringNotContainsString('submitted_device_id&quot;', $html);
        }
    }

    private function androidAuditRequest(string $endpoint, array $input = []): Request
    {
        $request = Request::create('/api/app/'.$endpoint, 'POST', $input);
        $route = new Route('POST', 'api/app/'.$endpoint, fn () => null);
        $route->name('generated::auditGapTest');
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);
        app()->instance('request', $request);
        return $request;
    }

    private function prepareAndroidIdentityTables(): void
    {
        $this->prepareAttendanceTables();
        Schema::table('employees', function (Blueprint $table) {
            $table->integer('stat_1')->default(1);
            $table->text('face_embeddings')->nullable();
        });
        // SQLite has JSON_EXTRACT/JSON_VALID, but the production QR query also uses JSON_LENGTH.
        DB::connection()->getPdo()->sqliteCreateFunction('JSON_LENGTH', fn ($value) =>
            is_array($decoded = json_decode((string) $value, true)) ? count($decoded) : ($value === null ? null : 1));
        $embedding = array_fill(0, 128, 0.0);
        $embedding[0] = 1.0;
        DB::table('employees')->where('emp_ID', 'E-8')->update([
            'face_embeddings' => json_encode(['vecs' => [$embedding], 'centroid' => $embedding]),
        ]);
    }

    private function prepareAuditFilterTables(): void
    {
        Schema::connection('payroll')->create('campuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        Schema::connection('payroll')->create('offices', function (Blueprint $table) {
            $table->id();
            $table->string('office_name');
        });
        $admin = new User(['role' => 'Administrator']);
        $admin->id = 1;
        $this->be($admin, 'web');
    }

    private function prepareAttendanceTables(): void
    {
        Schema::connection('mysql')->create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('emp_ID');
            $table->string('fname');
            $table->string('mname')->nullable();
            $table->string('lname');
            $table->unsignedBigInteger('camp_id')->nullable();
            $table->unsignedBigInteger('emp_dept')->nullable();
        });
        Schema::connection('mysql')->create('dtrs', function (Blueprint $table) {
            $table->id();
            $table->string('emp_ID');
            $table->date('date');
            $table->text('time_in')->nullable();
            $table->text('time_out')->nullable();
            $table->text('time_over')->nullable();
            $table->text('device_id_in')->nullable();
            $table->text('device_id_out')->nullable();
            $table->text('device_id_over')->nullable();
        });
        DB::connection('mysql')->table('employees')->insert([
            'id' => 8, 'emp_ID' => 'E-8', 'fname' => 'Juan', 'lname' => 'Cruz',
            'camp_id' => 2, 'emp_dept' => 4,
        ]);
    }

    private function prepareOfficialTime(?array $existing): void
    {
        Schema::connection('mysql')->create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('emp_ID');
            $table->string('fname');
            $table->string('mname')->nullable();
            $table->string('lname');
        });
        Schema::connection('mysql')->create('official_times', function (Blueprint $table) {
            $table->id();
            $table->string('empid');
            foreach (['mon', 'tue', 'wed', 'thu', 'fri'] as $day) {
                $table->string('morn_'.$day)->nullable();
                $table->string('aft_'.$day)->nullable();
            }
            $table->timestamps();
        });
        DB::connection('mysql')->table('employees')->insert(['id' => 8, 'emp_ID' => 'E-8', 'fname' => 'Juan', 'lname' => 'Cruz']);
        if ($existing !== null) {
            DB::connection('mysql')->table('official_times')->insert($existing + ['empid' => 'E-8']);
        }
    }

    // Same Eloquent save path as EmployeeController::OfficialTimeCreate, through both audit middlewares.
    private function saveOfficialTime(array $times): void
    {
        $request = Request::create('/employees/official-time-create', 'POST', ['empid' => 'E-8']);
        $route = new Route('POST', 'employees/official-time-create', fn () => null);
        $route->name('OfficialTimeCreate');
        $request->setRouteResolver(fn () => $route);
        app()->instance('request', $request);
        app(RecordAuditRequest::class)->handle($request, fn ($request) => app(CaptureAuditSnapshot::class)->handle($request,
            function () use ($times) {
                OfficialTime::firstOrNew(['empid' => 'E-8'])->fill($times)->save();
                return response('saved');
            }));
    }

    private function bindRequest(string $path, string $method): Request
    {
        $request = Request::create($path, $method);
        app()->instance('request', $request);
        return $request;
    }
}
