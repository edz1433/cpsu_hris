<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\DtrController;
use App\Http\Middleware\RecordAuditRequest;
use App\Services\Audit\AuditFormatter;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DesktopDtrAuditTest extends TestCase
{
    protected function setUp(): void
    {
        // Explicit opt-in; never use the application's configured database.
        if (!getenv('HRIS_AUDIT_MYSQL_PORT')) {
            $this->markTestSkipped('Requires disposable localhost hris_audit_test database and HRIS_AUDIT_MYSQL_PORT.');
        }
        parent::setUp();
        config()->set('database.default', 'mysql');
        // Override deployment caches/sinks for an isolated HTTP-kernel test.
        config()->set('cache.default', 'array');
        config()->set('cache.limiter', 'array');
        config()->set('logging.default', 'null');
        // The limiter was resolved during bootstrap before these overrides.
        $apiLimiter = app(\Illuminate\Cache\RateLimiter::class)->limiter('api');
        $limiter = new \Illuminate\Cache\RateLimiter(app('cache')->store('array'));
        $limiter->for('api', $apiLimiter);
        app()->instance(\Illuminate\Cache\RateLimiter::class, $limiter);
        config()->set('database.connections.mysql', [
            'driver' => 'mysql', 'host' => '127.0.0.1',
            'port' => (int) getenv('HRIS_AUDIT_MYSQL_PORT'), 'database' => 'hris_audit_test',
            'username' => 'root', 'password' => '', 'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci', 'prefix' => '', 'strict' => true,
        ]);
        config()->set('audit', require config_path('audit.php'));
        config()->set('audit.capture_in_console', true);
        DB::purge('mysql');
        foreach (['audit_logs', 'dtrs', 'employees', 'f_devices'] as $table) Schema::dropIfExists($table);
        (require database_path('migrations/2026_10_08_000001_create_audit_logs_table.php'))->up();
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('emp_ID');
            $table->string('fname');
            $table->string('mname')->nullable();
            $table->string('lname');
            $table->unsignedBigInteger('camp_id')->nullable();
            $table->unsignedBigInteger('emp_dept')->nullable();
        });
        Schema::create('f_devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_id');
            $table->string('label')->nullable();
        });
        DB::table('f_devices')->insert(['id' => 2, 'device_id' => 'reader-north', 'label' => 'North kiosk']);
        Schema::create('dtrs', function (Blueprint $table) {
            $table->id();
            // Match the legacy schema: id-only upserts rely on these defaults.
            $table->string('emp_ID')->nullable();
            $table->date('date')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            foreach (['time_in', 'time_out', 'time_over', 'device_id_in', 'device_id_out', 'device_id_over'] as $field) {
                $table->text($field)->nullable();
            }
        });
        DB::table('employees')->insert([
            ['id' => 8, 'emp_ID' => 'E-8', 'fname' => 'Juan', 'lname' => 'Cruz', 'camp_id' => 2, 'emp_dept' => 4],
            ['id' => 9, 'emp_ID' => 'E-9', 'fname' => 'Maria', 'lname' => 'Santos', 'camp_id' => 3, 'emp_dept' => 5],
        ]);
    }

    public function test_sync_preserves_response_merge_dedup_and_records_each_submitted_punch(): void
    {
        $oldId = DB::table('dtrs')->insertGetId([
            'emp_ID' => 'E-8', 'date' => '2026-10-05', 'time_in' => '08:00:00', 'device_id_in' => '1',
        ]);
        DB::table('dtrs')->insert(['emp_ID' => 'E-8', 'date' => '2026-10-04', 'time_in' => '07:00:00', 'device_id_in' => '4']);
        $payload = [
            ['emp_ID' => 'E-8', 'date' => '2026-10-05', 'time_in' => '08:00:00,09:00:00,09:00:00',
                'device_id_in' => '2,3,99', 'time_out' => '17:00:00', 'device_id_out' => '3'],
            ['emp_ID' => 'E-9', 'date' => '2026-10-05', 'time_over' => '18:00:00', 'device_id_over' => '7'],
            ['emp_ID' => 'E-8', 'date' => '2026-10-05', 'time_in' => '09:00:00', 'device_id_in' => '88'],
        ];
        $response = $this->sync(json_encode($payload));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['message' => 'DTR Sync Complete'], $response->getData(true));
        $rows = DB::table('dtrs')->where('date', '2026-10-05')->get()->keyBy('emp_ID');
        $this->assertCount(2, $rows);
        $this->assertGreaterThan($oldId, $rows['E-8']->id);
        $this->assertSame('08:00:00,09:00:00', $rows['E-8']->time_in);
        $this->assertSame('1,3', $rows['E-8']->device_id_in);
        $this->assertSame('17:00:00', $rows['E-8']->time_out);
        $this->assertSame('3', $rows['E-8']->device_id_out);
        $this->assertSame('18:00:00', $rows['E-9']->time_over);
        $this->assertSame('7', $rows['E-9']->device_id_over);
        $this->assertSame('07:00:00', DB::table('dtrs')->where('date', '2026-10-04')->value('time_in'));
        $events = DB::table('audit_logs')->where('outcome', '!=', 'received')->orderBy('id')->get();
        $this->assertCount(4, $events);
        $this->assertSame(['unchanged', 'success', 'success', 'success'], $events->pluck('outcome')->all());
        $details = json_decode($events[0]->details, true);
        $this->assertSame('2026-10-05 08:00:00', $details['attendance_at']);
        $this->assertSame('2', $details['submitted_device_id']);
        $this->assertSame('1', $details['device_id']);
        $this->assertSame([
            'submitted_rows' => 3, 'audit_valid_rows' => 3, 'audit_skipped_rows' => 0,
            'submitted_punches' => 6, 'audit_valid_punches' => 6, 'audit_skipped_punches' => 0,
            'unique_punches' => 4, 'duplicate_submitted_punches' => 2,
            'newly_saved_punches' => 3, 'already_stored_punches' => 1, 'not_saved_punches' => 0,
        ], $details['upload_counts']);
        $this->assertSame('E-8', $events[0]->employee_code);
        $this->assertSame(2, $events[0]->campus_id);
        $this->assertNotSame(substr($events[0]->occurred_at, 0, 10), substr($details['attendance_at'], 0, 10));
        $this->assertStringContainsString('already stored', AuditFormatter::describe($events[0]));
        $this->assertStringContainsString('kiosk-reported', AuditFormatter::describe($events[1]));

        $retry = $this->sync(json_encode($payload));
        $this->assertSame(['message' => 'DTR Sync Complete'], $retry->getData(true));
        $retryEvents = DB::table('audit_logs')->where('request_id', request()->attributes->get('audit_request_id'))
            ->where('outcome', '!=', 'received')->get();
        $this->assertCount(4, $retryEvents);
        $this->assertSame(['unchanged'], $retryEvents->pluck('outcome')->unique()->values()->all());
        $this->assertSame('1,3', DB::table('dtrs')->where('emp_ID', 'E-8')->where('date', '2026-10-05')->value('device_id_in'));
    }

    public function test_existing_missing_device_behavior_is_preserved_without_false_saved_evidence(): void
    {
        $response = $this->sync(json_encode([
            ['emp_ID' => 'E-8', 'date' => '2026-10-05', 'time_in' => '08:00:00'],
        ]));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('', DB::table('dtrs')->value('time_in'));
        $event = DB::table('audit_logs')->where('outcome', '!=', 'received')->first();
        $this->assertSame('rejected', $event->outcome);
        $this->assertSame('punch_not_saved', $event->reason_code);
        $this->assertArrayNotHasKey('dtr_id', json_decode($event->details, true));
    }

    public function test_audit_failure_rolls_back_the_entire_sync_and_keeps_failure_evidence(): void
    {
        DB::table('dtrs')->insert(['emp_ID' => 'E-8', 'date' => '2026-10-05', 'time_in' => '07:00:00', 'device_id_in' => '1']);
        $before = DB::table('dtrs')->get()->toJson();
        DB::unprepared("CREATE TRIGGER reject_success_audit BEFORE INSERT ON audit_logs FOR EACH ROW
            BEGIN IF NEW.outcome = 'success' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'audit unavailable'; END IF; END");
        $response = $this->sync(json_encode([
            ['emp_ID' => 'E-8', 'date' => '2026-10-05', 'time_in' => '07:00:00', 'device_id_in' => '1'],
            ['emp_ID' => 'E-8', 'date' => '2026-10-05', 'time_in' => '08:00:00', 'device_id_in' => '2'],
            ['emp_ID' => 'E-9', 'date' => '2026-10-05', 'time_out' => '17:00:00', 'device_id_out' => '3'],
        ]));
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame(['error' => 'DTR sync failed'], $response->getData(true));
        $this->assertSame($before, DB::table('dtrs')->get()->toJson());
        $this->assertSame(['received', 'error', 'error', 'error'], DB::table('audit_logs')->orderBy('id')->pluck('outcome')->all());
        $lockName = 'hris:dtr-sync:'.substr(hash('sha256', DB::connection()->getDatabaseName()), 0, 40);
        $this->assertSame(1, (int) DB::selectOne('SELECT IS_FREE_LOCK(?) AS available', [$lockName], false)->available);
        foreach (DB::table('audit_logs')->where('outcome', 'error')->get() as $event) {
            $this->assertArrayNotHasKey('dtr_id', json_decode($event->details, true));
            $this->assertSame('sync_failed', $event->reason_code);
        }
    }

    public function test_failed_dtr_write_keeps_legacy_error_response_and_no_success_log(): void
    {
        DB::unprepared("CREATE TRIGGER reject_dtr BEFORE INSERT ON dtrs FOR EACH ROW
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'attendance unavailable'");
        $response = $this->sync(json_encode([
            ['emp_ID' => 'E-8', 'date' => '2026-10-05', 'time_in' => '08:00:00', 'device_id_in' => '2'],
        ]));
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame(['error' => 'DTR sync failed'], $response->getData(true));
        $this->assertSame(0, DB::table('dtrs')->count());
        $this->assertSame(['received', 'error'], DB::table('audit_logs')->orderBy('id')->pluck('outcome')->all());
    }

    public function test_missing_audit_storage_prevents_sync_without_changing_failure_response(): void
    {
        Schema::drop('audit_logs');
        $response = $this->sync(json_encode([
            ['emp_ID' => 'E-8', 'date' => '2026-10-05', 'time_in' => '08:00:00', 'device_id_in' => '2'],
        ]));
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame(['error' => 'DTR sync failed'], $response->getData(true));
        $this->assertSame(0, DB::table('dtrs')->count());
    }

    public function test_invalid_empty_and_skipped_payload_responses_remain_compatible(): void
    {
        $invalid = $this->sync('invalid json');
        $this->assertSame(400, $invalid->getStatusCode());
        $this->assertSame(['error' => 'Invalid JSON data'], $invalid->getData(true));
        foreach ([[], [['emp_ID' => 'E-8']], ['ignored']] as $data) {
            $response = $this->sync(json_encode($data));
            $this->assertSame(200, $response->getStatusCode());
            $this->assertSame(['message' => 'DTR Sync Complete'], $response->getData(true));
            $event = DB::table('audit_logs')->where('request_id', request()->attributes->get('audit_request_id'))
                ->where('outcome', '!=', 'received')->first();
            $this->assertSame('unchanged', $event->outcome);
            $this->assertSame('no_valid_punches_supplied', $event->reason_code);
        }
        $this->assertSame(0, DB::table('dtrs')->count());
    }

    public function test_sync_across_the_existing_200_row_boundary(): void
    {
        $rows = [];
        for ($index = 0; $index < 201; $index++) {
            $rows[] = ['emp_ID' => 'E-8', 'date' => '2026-10-05',
                'time_in' => sprintf('08:%02d:%02d', intdiv($index, 60), $index % 60),
                'device_id_in' => '2'];
        }
        $response = $this->sync(json_encode($rows));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, DB::table('dtrs')->count());
        $this->assertCount(201, explode(',', DB::table('dtrs')->value('time_in')));
        $this->assertSame(201, DB::table('audit_logs')->where('outcome', 'success')->count());
    }

    public function test_case_insensitive_employee_merge_does_not_create_false_new_punch_evidence(): void
    {
        DB::table('dtrs')->insert(['emp_ID' => 'E-8', 'date' => '2026-10-05', 'time_in' => '08:00:00', 'device_id_in' => '1']);
        $response = $this->sync(json_encode([
            ['emp_ID' => 'e-8', 'date' => '2026-10-05', 'time_in' => '08:00:00', 'device_id_in' => '2',
                'password' => 'never-store-password', 'embedding' => 'never-store-biometric'],
            ['emp_ID' => 'E-8', 'date' => '2026-10-05', 'time_in' => '08:00:00', 'device_id_in' => '3'],
        ]));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, DB::table('dtrs')->count());
        $this->assertSame('1', DB::table('dtrs')->value('device_id_in'));
        $events = DB::table('audit_logs')->where('outcome', '!=', 'received')->get();
        $this->assertCount(1, $events);
        $this->assertSame('unchanged', $events[0]->outcome);
        $this->assertSame('E-8', $events[0]->employee_code);
        foreach (DB::table('audit_logs')->get() as $event) {
            $this->assertStringNotContainsString('never-store', $event->details);
        }
    }

    public function test_mysql_date_search_finds_late_success_and_failure_without_completed_receipts(): void
    {
        config()->set('database.connections.payroll', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        DB::purge('payroll');
        foreach (['campuses' => 'name', 'offices' => 'office_name'] as $table => $field) {
            Schema::connection('payroll')->create($table, function (Blueprint $table) use ($field) {
                $table->id();
                $table->string($field);
            });
        }
        app('view')->getFinder()->setPaths([resource_path('views')]);
        $success = $this->sync(json_encode([
            ['emp_ID' => 'E-8', 'date' => '2026-10-05', 'time_in' => '08:00:00', 'device_id_in' => '2'],
        ]));
        $this->assertSame(200, $success->getStatusCode());
        DB::unprepared("CREATE TRIGGER reject_dtr BEFORE INSERT ON dtrs FOR EACH ROW
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'attendance unavailable'");
        $failure = $this->sync(json_encode([
            ['emp_ID' => 'E-8', 'date' => '2026-10-06', 'time_out' => '17:00:00', 'device_id_out' => '2'],
        ]));
        $this->assertSame(500, $failure->getStatusCode());
        $admin = new \App\Models\User(['role' => 'Administrator']);
        $admin->id = 1;
        $this->be($admin, 'web');
        $controller = (new \ReflectionClass(\App\Http\Controllers\AuditLogController::class))->newInstanceWithoutConstructor();
        foreach (['2026-10-05' => 'success', '2026-10-06' => 'error'] as $date => $outcome) {
            $request = Request::create('/audit-logs', 'GET', [
                'module' => 'dtr', 'kiosk_id' => '2', 'person' => 'E-8', 'date_from' => $date, 'date_to' => $date,
            ]);
            app()->instance('request', $request);
            $data = $controller->index($request)->getData();
            $this->assertSame([$outcome], collect($data['logs']->items())->pluck('outcome')->all());
            $this->assertSame('error', $data['latestKioskUpload']->outcome);
            $this->assertStringContainsString('North kiosk', $data['kioskLabels']->get('2'));
            $this->assertStringNotContainsString('reader-north', $data['kioskLabels']->get('2'));
        }
    }

    public function test_restored_android_controller_keeps_responses_outcomes_and_atomic_audit_on_mysql(): void
    {
        $controller = (new \ReflectionClass(\App\Http\Controllers\Api\TimeEntryController::class))->newInstanceWithoutConstructor();
        \Carbon\Carbon::setTestNow('2026-10-08 13:00:00');
        try {
            foreach ([1, 2, 3, 2, 1] as $index => $action) {
                $request = Request::create('/api/app/log-attendance', 'POST', ['action' => $action, 'emp_id' => 'e-8', 'zone_id' => '5']);
                app()->instance('request', $request);
                $response = app(RecordAuditRequest::class)->handle($request, fn () => $controller->logAttendance($request));
                $expectedStatus = $index < 3 ? 200 : ($action === 1 ? 429 : 202);
                $this->assertSame($expectedStatus, $response->getStatusCode());
                $this->assertSame([
                    'success' => $index < 3, 'updated' => $index < 3, 'allowed' => $expectedStatus !== 429,
                    'wait_seconds' => $expectedStatus === 429 ? 60 : 0, 'time' => '01:00:00 PM',
                    'type' => [1 => 'TIME IN', 2 => 'TIME OUT', 3 => 'OVERTIME'][$action],
                    'emp_id' => 'E-8', 'zone_id' => '5',
                ], $response->getData(true));
                $this->assertSame(0, DB::transactionLevel());
            }
            $events = DB::table('audit_logs')->where('outcome', '!=', 'received')->orderBy('id')->get();
            $this->assertSame(['success', 'success', 'success', 'unchanged', 'rejected'], $events->pluck('outcome')->all());
            $this->assertSame(['E-8'], $events->pluck('employee_code')->unique()->values()->all());
            foreach ($events->take(3) as $event) {
                $details = json_decode($event->details, true);
                $this->assertSame('2026-10-08 13:00:00', $details['attendance_at']);
                $this->assertSame((int) DB::table('dtrs')->value('id'), $details['dtr_id']);
            }
            DB::unprepared("CREATE TRIGGER reject_android_audit BEFORE INSERT ON audit_logs FOR EACH ROW
                BEGIN IF NEW.outcome = 'success' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'audit unavailable'; END IF; END");
            $before = DB::table('dtrs')->get()->toJson();
            \Carbon\Carbon::setTestNow('2026-10-08 13:01:00');
            $request = Request::create('/api/app/log-attendance', 'POST', ['action' => 2, 'emp_id' => 'E-8', 'zone_id' => '5']);
            app()->instance('request', $request);
            $response = app(RecordAuditRequest::class)->handle($request, fn () => $controller->logAttendance($request));
            $this->assertSame(['error' => 'Server error', 'message' => 'Attendance audit could not be stored.'], $response->getData(true));
            $this->assertSame(500, $response->getStatusCode());
            $this->assertSame($before, DB::table('dtrs')->get()->toJson());
            $this->assertSame(['received', 'error'], DB::table('audit_logs')->where('request_id', $request->attributes->get('audit_request_id'))->orderBy('id')->pluck('outcome')->all());
            $this->assertSame(0, DB::transactionLevel());
        } finally {
            \Carbon\Carbon::setTestNow();
        }
    }

    public function test_desktop_audit_is_applied_through_the_real_http_kernel(): void
    {
        $this->withoutExceptionHandling();
        // Avoid the production APP_URL subdirectory when constructing the test URL.
        $response = $this->postJson('http://localhost/api/dtrs', [
            ['emp_ID' => 'E-8', 'date' => '2026-10-05', 'time_in' => '08:00:00', 'device_id_in' => '2'],
        ]);
        $response->assertStatus(200)->assertExactJson(['message' => 'DTR Sync Complete']);
        $events = DB::table('audit_logs')->orderBy('id')->get();
        $this->assertSame(['received', 'success'], $events->pluck('outcome')->all());
        $this->assertSame('E-8', $events[1]->employee_code);
        $this->assertSame('08:00:00', DB::table('dtrs')->value('time_in'));
    }

    public function test_android_audit_and_rollback_are_applied_through_the_real_http_kernel(): void
    {
        $this->withoutExceptionHandling();
        \Carbon\Carbon::setTestNow('2026-10-08 08:00:00');
        try {
            $payload = ['action' => 1, 'emp_id' => 'E-8', 'zone_id' => '5'];
            $this->postJson('http://localhost/api/app/log-attendance', $payload)
                ->assertStatus(200)->assertJson(['updated' => true, 'allowed' => true]);
            $this->postJson('http://localhost/api/app/log-attendance', $payload)
                ->assertStatus(202)->assertJson(['updated' => false, 'allowed' => true]);
            $before = DB::table('dtrs')->get()->toJson();
            DB::unprepared("CREATE TRIGGER reject_kernel_audit BEFORE INSERT ON audit_logs FOR EACH ROW
                BEGIN IF NEW.outcome = 'success' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'audit unavailable'; END IF; END");
            $payload['action'] = 2;
            $this->postJson('http://localhost/api/app/log-attendance', $payload)
                ->assertStatus(500)->assertExactJson(['error' => 'Server error', 'message' => 'Attendance audit could not be stored.']);
            $this->assertSame($before, DB::table('dtrs')->get()->toJson());
            $events = DB::table('audit_logs')->where('outcome', '!=', 'received')->orderBy('id')->get();
            $this->assertSame(['success', 'unchanged', 'error'], $events->pluck('outcome')->all());
            $this->assertSame('2026-10-08 08:00:00', json_decode($events[0]->details, true)['attendance_at']);
            $this->assertArrayNotHasKey('dtr_id', json_decode($events[2]->details, true));
            $this->assertSame(3, DB::table('audit_logs')->where('outcome', 'received')->count());
            $this->assertSame(0, DB::transactionLevel());
        } finally {
            \Carbon\Carbon::setTestNow();
        }
    }

    public function test_overlapping_desktop_uploads_are_serialized_before_the_audit_snapshot(): void
    {
        $releaseFile = tempnam(sys_get_temp_dir(), 'hris-sync-');
        unlink($releaseFile);
        $workerCode = <<<'PHP'
require $argv[1].'/vendor/autoload.php';
$app = require $argv[1].'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config()->set('database.default', 'mysql');
config()->set('database.connections.mysql', [
    'driver' => 'mysql', 'host' => '127.0.0.1', 'port' => (int) $argv[2],
    'database' => 'hris_audit_test', 'username' => 'root', 'password' => '',
    'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci', 'prefix' => '', 'strict' => true,
]);
config()->set('logging.default', 'null');
config()->set('audit', require $argv[1].'/config/audit.php');
config()->set('audit.capture_in_console', true);
\Illuminate\Support\Facades\DB::purge('mysql');
$payload = [['emp_ID' => 'E-8', 'date' => '2026-10-05', 'time_in' => '08:00:00', 'device_id_in' => '2']];
$request = \Illuminate\Http\Request::create('/api/dtrs', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload));
$app->instance('request', $request);
$controller = (new ReflectionClass(\App\Http\Controllers\Api\DtrController::class))->newInstanceWithoutConstructor();
$response = $app->make(\App\Http\Middleware\RecordAuditRequest::class)->handle($request, function ($request) use ($controller, $argv) {
    // Hold a real upload after its before-snapshot while the other connection probes the lock.
    fwrite(STDOUT, "snapshot-ready\n");
    fflush(STDOUT);
    $deadline = microtime(true) + 20;
    while (!file_exists($argv[3])) {
        if (microtime(true) > $deadline) throw new RuntimeException('Parent did not release upload.');
        usleep(10000);
    }
    return $controller->syncDtr($request);
});
if ($response->getStatusCode() !== 200) throw new RuntimeException('Worker sync failed.');
PHP;
        // Use a file: Windows cannot reliably launch multiline PHP through -r.
        $workerFile = tempnam(sys_get_temp_dir(), 'hris-sync-worker-');
        file_put_contents($workerFile, "<?php\n".$workerCode);
        $worker = new \Symfony\Component\Process\Process([
            PHP_BINARY, $workerFile, base_path(), (string) getenv('HRIS_AUDIT_MYSQL_PORT'), $releaseFile,
        ], sys_get_temp_dir(), null, null, 30);
        $lockName = 'hris:dtr-sync:'.substr(hash('sha256', DB::connection()->getDatabaseName()), 0, 40);
        $probeAcquired = false;
        try {
            $worker->start();
            $output = '';
            $ready = $worker->waitUntil(function ($type, $buffer) use (&$output) {
                $output .= $buffer;
                return str_contains($output, 'snapshot-ready');
            });
            $this->assertTrue($ready, $worker->getErrorOutput());
            $probeAcquired = (int) DB::selectOne('SELECT GET_LOCK(?, 0) AS acquired', [$lockName], false)->acquired === 1;
            $this->assertFalse($probeAcquired, 'A second upload must not enter while the first snapshot is active.');
            file_put_contents($releaseFile, 'continue');
            $worker->wait();
            $this->assertTrue($worker->isSuccessful(), $worker->getErrorOutput());
            $this->assertSame(1, (int) DB::selectOne('SELECT IS_FREE_LOCK(?) AS available', [$lockName], false)->available);

            $response = $this->sync(json_encode([
                ['emp_ID' => 'E-8', 'date' => '2026-10-05', 'time_in' => '08:00:00', 'device_id_in' => '2'],
            ]));
            $this->assertSame(200, $response->getStatusCode());
            $events = DB::table('audit_logs')->where('outcome', '!=', 'received')->orderBy('id')->get();
            $this->assertSame(['success', 'unchanged'], $events->pluck('outcome')->all());
            $counts = json_decode($events[1]->details, true)['upload_counts'];
            $this->assertSame(0, $counts['newly_saved_punches']);
            $this->assertSame(1, $counts['already_stored_punches']);
            $this->assertSame(1, DB::table('dtrs')->count());
            $this->assertSame('08:00:00', DB::table('dtrs')->value('time_in'));
            $this->assertSame(1, (int) DB::selectOne('SELECT IS_FREE_LOCK(?) AS available', [$lockName], false)->available);
        } finally {
            if ($probeAcquired) DB::selectOne('SELECT RELEASE_LOCK(?)', [$lockName], false);
            file_put_contents($releaseFile, 'continue');
            if ($worker->isStarted()) $worker->wait();
            unlink($releaseFile);
            unlink($workerFile);
        }
    }
    public function test_future_and_offline_punches_remain_accepted_with_informational_flags(): void
    {
        \Carbon\Carbon::setTestNow('2026-10-12 08:00:00');
        try {
            $response = $this->sync(json_encode([
                ['emp_ID' => 'E-8', 'date' => '2026-10-13', 'time_in' => '08:00:00', 'device_id_in' => '2'],
                ['emp_ID' => 'E-8', 'date' => '2026-10-01', 'time_in' => '08:00:00', 'device_id_in' => '2'],
            ]));
            $this->assertSame(200, $response->getStatusCode());
            $this->assertSame(['message' => 'DTR Sync Complete'], $response->getData(true));
            $this->assertSame(2, DB::table('dtrs')->count());
            $events = DB::table('audit_logs')->where('outcome', '!=', 'received')->orderBy('id')->get();
            $this->assertSame(['success', 'success'], $events->pluck('outcome')->all());
            $this->assertSame([['future_dated'], ['late_upload']], $events->map(
                fn ($event) => json_decode($event->details, true)['timing_flags'])->all());
            foreach ($events as $event) {
                $this->assertSame(2, json_decode($event->details, true)['upload_counts']['newly_saved_punches']);
            }
        } finally {
            \Carbon\Carbon::setTestNow();
        }
    }


    public function test_duplicate_punch_sources_are_filterable_on_mysql_without_changing_saved_punches(): void
    {
        $response = $this->sync(json_encode([
            ['emp_ID' => 'E-8', 'date' => '2026-10-05', 'time_in' => '08:00:00,08:00:00', 'device_id_in' => '2,3'],
            ['emp_ID' => 'e-8', 'date' => '2026-10-05', 'time_in' => '08:00:00', 'device_id_in' => '99'],
        ]));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('08:00:00', DB::table('dtrs')->value('time_in'));
        $this->assertSame('2', DB::table('dtrs')->value('device_id_in'));
        $events = DB::table('audit_logs')->where('outcome', '!=', 'received')->get();
        $this->assertCount(1, $events);
        $this->assertSame(['2', '3', '99'], json_decode($events[0]->details, true)['submitted_device_ids']);
        $controller = (new \ReflectionClass(\App\Http\Controllers\AuditLogController::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod($controller, 'filterKiosk');
        $method->setAccessible(true);
        foreach (['2', '3', '99'] as $kiosk) {
            $query = DB::table('audit_logs');
            $method->invoke($controller, $query, $kiosk);
            $this->assertSame([$events[0]->id], $query->pluck('id')->all());
        }
        $query = DB::table('audit_logs');
        $method->invoke($controller, $query, '9');
        $this->assertSame(0, $query->count());
    }

    public function test_desktop_request_size_rejection_is_recorded_through_the_real_kernel(): void
    {
        $request = Request::create('http://localhost/api/dtrs', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json',
            'CONTENT_LENGTH' => (string) (PHP_INT_MAX - 1),
        ], json_encode([['emp_ID' => 'E-8', 'date' => '2026-10-05', 'time_in' => '08:00:00', 'device_id_in' => '2']]));
        $response = app(\Illuminate\Contracts\Http\Kernel::class)->handle($request);
        $this->assertSame(413, $response->getStatusCode());
        $events = DB::table('audit_logs')->orderBy('id')->get();
        $this->assertSame(['received', 'rejected'], $events->pluck('outcome')->all());
        $this->assertSame('request_too_large', $events[1]->reason_code);
        $this->assertSame(413, json_decode($events[1]->details, true)['http_status']);
        $this->assertSame(0, DB::table('dtrs')->count());
    }

    private function sync(string $json)
    {
        $request = Request::create('/api/dtrs', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'], $json);
        $route = new Route('POST', 'api/dtrs', [DtrController::class, 'syncDtr']);
        $route->name('api.syncDtr');
        $request->setRouteResolver(fn () => $route);
        app()->instance('request', $request);
        return app(RecordAuditRequest::class)->handle($request, fn () => app(DtrController::class)->syncDtr($request));
    }
}
