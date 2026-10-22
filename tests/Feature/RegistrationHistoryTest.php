<?php

namespace Tests\Feature;

use App\Http\Controllers\AuditLogController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class RegistrationHistoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        app('view')->getFinder()->setPaths([resource_path('views')]);
        config()->set('audit', require config_path('audit.php'));
        foreach (['mysql', 'payroll'] as $connection) {
            config()->set('database.connections.'.$connection, [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            ]);
            DB::purge($connection);
        }
        config()->set('database.default', 'mysql');
        (require database_path('migrations/2026_10_08_000001_create_audit_logs_table.php'))->up();
        foreach (['campuses' => 'name', 'offices' => 'office_name'] as $table => $field) {
            Schema::connection('payroll')->create($table, function (Blueprint $table) use ($field) {
                $table->id();
                $table->string($field);
            });
        }
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('emp_ID');
            $table->string('fname');
            $table->string('mname')->nullable();
            $table->string('lname');
            // Must never select biometric or credential columns for this display.
            $table->text('face_embeddings')->nullable();
            $table->text('f1')->nullable();
            $table->text('password')->nullable();
        });
        Schema::create('f_devices', function (Blueprint $table) {
            $table->id();
            $table->string('label')->nullable();
            $table->string('device_id');
        });
        foreach (['emp_hrkiosk_reghist', 'emp_timeentry_reghist'] as $name) {
            Schema::create($name, function (Blueprint $table) use ($name) {
                $table->id();
                $table->string('enroller_ID');
                $table->date('date');
                $table->text('enrolled_IDs')->nullable();
                $table->text('enrolled_times')->nullable();
                if ($name === 'emp_hrkiosk_reghist') $table->text('enroller_device_ID')->nullable();
            });
        }
        DB::table('employees')->insert([
            ['emp_ID' => 'A-1', 'fname' => 'Admin', 'lname' => 'Reported', 'f1' => 'private-fingerprint', 'face_embeddings' => 'private-embedding', 'password' => 'private-password'],
            ['emp_ID' => 'E-8', 'fname' => 'Juan', 'lname' => 'Cruz', 'f1' => null, 'face_embeddings' => null, 'password' => null],
            ['emp_ID' => 'E-80', 'fname' => 'Ana <East>', 'lname' => 'Santos', 'f1' => null, 'face_embeddings' => null, 'password' => null],
        ]);
        DB::table('f_devices')->insert(['id' => 2, 'label' => 'North <Kiosk>', 'device_id' => 'private-reader-serial']);
        $admin = new User(['role' => 'Administrator']);
        $admin->id = 1;
        $this->be($admin, 'web');
    }

    public function test_history_pairs_each_registration_with_its_time_and_device_without_writes(): void
    {
        $this->desktop('E-8-1,E-80-2,DELETED-CODE-3', '08:00:00,,10:00:00', '2,,99');
        $this->android('E-8,E-80', '11:00:00,invalid');
        $before = [];
        foreach (['audit_logs', 'employees', 'f_devices', 'emp_hrkiosk_reghist', 'emp_timeentry_reghist'] as $table) {
            $before[$table] = DB::table($table)->orderBy('id')->get()->toJson();
        }
        DB::enableQueryLog();
        $data = $this->page(['module' => 'dtr'])->getData();
        $history = $data['registrationHistory'];
        $this->assertSame(2, $history['batches']->count());
        $desktop = collect($history['batches']->items())->firstWhere('source', 'desktop');
        $this->assertSame([
            ['code' => 'E-8', 'finger' => '1', 'device' => '2', 'time' => '08:00:00'],
            ['code' => 'E-80', 'finger' => '2', 'device' => null, 'time' => null],
            ['code' => 'DELETED-CODE', 'finger' => '3', 'device' => '99', 'time' => '10:00:00'],
        ], $desktop->registrations);
        $android = collect($history['batches']->items())->firstWhere('source', 'android');
        $this->assertNull($android->registrations[1]['time']);
        $this->assertNull($android->registrations[0]['device']);
        foreach (DB::getQueryLog() as $query) {
            if (str_contains($query['query'], '"employees"')) {
                $this->assertStringNotContainsString('*', $query['query']);
                $this->assertStringNotContainsString('face_embeddings', $query['query']);
                $this->assertStringNotContainsString('password', $query['query']);
                $this->assertStringNotContainsString('f1', $query['query']);
            }
        }
        $html = $this->renderPage($data);
        foreach (['Registration History', 'Desktop HR Kiosk', 'Android Time Entry', 'Admin Reported', 'Juan Cruz',
            'Ana &lt;East&gt;', 'North &lt;Kiosk&gt;', 'Finger 2', 'Face registration', 'Time not recorded', 'Device not recorded', 'DELETED-CODE', 'HR Kiosk (name unavailable)'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
        $this->assertStringNotContainsString('private-', $html);
        $this->assertStringNotContainsString('#99', $html);
        $this->assertStringNotContainsString('HR Kiosk ID (desktop)', $html);
        foreach ($before as $table => $rows) $this->assertSame($rows, DB::table($table)->orderBy('id')->get()->toJson());
    }

    public function test_history_filters_match_exact_employees_and_keep_device_positions(): void
    {
        $this->desktop('E-8-1,E-80-2,E-8-3', '08:00:00,09:00:00,10:00:00', '2,99,99');
        $this->android('E-8', '11:00:00');
        $filters = ['registration_person' => 'E-8', 'registration_enroller' => 'A-1',
            'registration_from' => '2026-10-10', 'registration_to' => '2026-10-10', 'registration_kiosk' => '2'];
        $batches = $this->page($filters)->getData()['registrationHistory']['batches'];
        $this->assertSame(1, $batches->count());
        $this->assertSame([['code' => 'E-8', 'finger' => '1', 'device' => '2', 'time' => '08:00:00']], $batches->items()[0]->registrations);
        $crossed = $this->page(['registration_person' => 'E-80', 'registration_kiosk' => '2'])->getData();
        $this->assertSame(0, $crossed['registrationHistory']['batches']->count());
        foreach ([['registration_enroller' => 'missing'], ['registration_from' => '2026-10-11'],
            ['registration_to' => '2026-10-09']] as $filters) {
            $this->assertSame(0, $this->page($filters)->getData()['registrationHistory']['batches']->count());
        }
        $android = $this->page(['registration_source' => 'android', 'registration_person' => 'E-8'])->getData();
        $this->assertSame('android', $android['registrationHistory']['batches']->items()[0]->source);
    }

    public function test_literal_employee_filter_does_not_treat_percent_or_underscore_as_wildcards(): void
    {
        $this->desktop('E_%\'8-1,E-80-2', '08:00:00,09:00:00', '2,2');
        $this->desktop('EXY8-1', '10:00:00', '2');
        $data = $this->page(['registration_person' => "E_%'8"])->getData();
        $this->assertSame(1, $data['registrationHistory']['batches']->count());
        $this->assertSame("E_%'8", $data['registrationHistory']['batches']->items()[0]->registrations[0]['code']);
    }

    public function test_history_pagination_is_bounded_and_independent_of_activity_filters(): void
    {
        for ($index = 0; $index < 7; $index++) {
            $this->desktop('E-8-1', '08:00:00', '2', '2026-10-'.sprintf('%02d', 10 - $index));
            $this->android('E-8', '09:00:00', '2026-10-'.sprintf('%02d', 10 - $index));
        }
        DB::table('audit_logs')->insert(['module' => 'employees', 'action' => 'updated', 'outcome' => 'success', 'occurred_at' => '2026-10-12 08:00:00']);
        DB::enableQueryLog();
        $first = $this->page(['registration_from' => '2026-10-04'])->getData();
        $this->assertSame(1, $first['logs']->count());
        $this->assertSame(10, $first['registrationHistory']['batches']->count());
        $this->assertTrue($first['registrationHistory']['batches']->hasMorePages());
        $this->assertStringContainsString('registration_page=2', $first['registrationHistory']['batches']->nextPageUrl());
        $this->assertStringContainsString('#registration-history', $first['registrationHistory']['batches']->nextPageUrl());
        foreach (DB::getQueryLog() as $query) {
            $this->assertStringNotContainsString('count(*)', strtolower($query['query']));
        }
        $second = $this->page(['registration_from' => '2026-10-04', 'registration_page' => 2, 'module' => 'dtr'])->getData();
        $this->assertSame(0, $second['logs']->count());
        $this->assertSame(4, $second['registrationHistory']['batches']->count());
        $keys = collect($first['registrationHistory']['batches']->items())->merge($second['registrationHistory']['batches']->items())
            ->map(fn ($batch) => $batch->source.':'.$batch->id);
        $this->assertSame(14, $keys->unique()->count());
    }

    public function test_unavailable_history_tables_do_not_break_the_audit_page(): void
    {
        $this->desktop('E-8-1', '08:00:00', '2');
        Schema::drop('emp_timeentry_reghist');
        $data = $this->page()->getData();
        $this->assertSame(['Android'], $data['registrationHistory']['unavailable']);
        $this->assertSame(1, $data['registrationHistory']['batches']->count());
        $this->assertStringContainsString('Android registration history is unavailable.', $this->renderPage($data));
        Schema::drop('emp_hrkiosk_reghist');
        $data = $this->page()->getData();
        $this->assertNull($data['registrationHistory']['batches']);
        $this->assertStringContainsString('No registration records', $this->renderPage($data));
    }

    public function test_history_access_stays_admin_only_and_reversed_dates_are_rejected(): void
    {
        try {
            $this->page(['registration_from' => '2026-10-11', 'registration_to' => '2026-10-10']);
            $this->fail('Reversed registration dates were accepted.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('registration_to', $e->errors());
        }
        $user = new User(['role' => 'Employee']);
        $user->id = 2;
        $this->be($user, 'web');
        DB::enableQueryLog();
        try {
            $this->page();
            $this->fail('Non-administrator could read history.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
        $this->assertSame([], DB::getQueryLog());
    }

    public function test_existing_generated_verification_actions_render_as_clear_labels(): void
    {
        foreach ([
            'validate-qr' => 'QR Validation',
            'face-claim' => 'Face Verification',
        ] as $endpoint => $label) {
            DB::table('audit_logs')->insert([
                'module' => 'dtr', 'action' => 'dtr_generated_randomname', 'outcome' => 'success',
                'occurred_at' => '2026-10-12 08:00:00', 'target_name' => 'Juan Cruz',
                'target_type' => 'employee', 'employee_code' => 'E-8',
                'details' => json_encode(['endpoint' => 'api/app/'.$endpoint]),
            ]);
        }
        $html = $this->renderPage($this->page()->getData());
        $this->assertStringContainsString('QR Validation', $html);
        $this->assertStringContainsString('Face Verification', $html);
        $this->assertStringContainsString('QR Validation succeeded', $html);
        $this->assertStringContainsString('Face Verification succeeded', $html);
        $this->assertStringNotContainsString('dtr generated randomname', $html);
        $this->assertStringNotContainsString('The system completed Juan Cruz', $html);
        $this->assertSame(2, DB::table('audit_logs')->where('action', 'dtr_generated_randomname')->count());
    }

    private function desktop(string $codes, string $times, string $devices, string $date = '2026-10-10'): void
    {
        DB::table('emp_hrkiosk_reghist')->insert(['enroller_ID' => 'A-1', 'enrolled_IDs' => $codes,
            'enrolled_times' => $times, 'enroller_device_ID' => $devices, 'date' => $date]);
    }

    private function android(string $codes, string $times, string $date = '2026-10-10'): void
    {
        DB::table('emp_timeentry_reghist')->insert(['enroller_ID' => 'A-1', 'enrolled_IDs' => $codes,
            'enrolled_times' => $times, 'date' => $date]);
    }

    private function page(array $filters = [])
    {
        $request = Request::create('/audit-logs', 'GET', $filters);
        app()->instance('request', $request);
        return (new \ReflectionClass(AuditLogController::class))->newInstanceWithoutConstructor()->index($request);
    }

    private function renderPage(array $data): string
    {
        app('view')->setDispatcher(new \Illuminate\Events\Dispatcher(app()));
        $compiledPath = sys_get_temp_dir().'/hris-registration-test-views';
        if (!is_dir($compiledPath)) mkdir($compiledPath, 0777, true);
        config()->set('view.compiled', $compiledPath);
        app()->forgetInstance('blade.compiler');
        \Illuminate\Support\Facades\Blade::clearResolvedInstance('blade.compiler');
        app('view.engine.resolver')->forget('blade');
        $body = str_replace(["@extends('layouts.master')", "@section('body')", '@endsection'], '',
            file_get_contents(resource_path('views/audit-logs/index.blade.php')));
        return \Illuminate\Support\Facades\Blade::render($body, $data + ['errors' => new \Illuminate\Support\ViewErrorBag()]);
    }
}
