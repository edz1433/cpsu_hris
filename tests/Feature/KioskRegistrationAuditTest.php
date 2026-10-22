<?php

namespace Tests\Feature;

use App\Services\Audit\AuditFormatter;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class KioskRegistrationAuditTest extends TestCase
{
    protected function setUp(): void
    {
        // Explicit opt-in; never use the application's configured database.
        if (!getenv('HRIS_AUDIT_MYSQL_PORT')) {
            $this->markTestSkipped('Requires disposable localhost hris_audit_test database and HRIS_AUDIT_MYSQL_PORT.');
        }
        parent::setUp();
        config()->set('database.default', 'mysql');
        config()->set('logging.default', 'null');
        config()->set('database.connections.mysql', [
            'driver' => 'mysql', 'host' => '127.0.0.1',
            'port' => (int) getenv('HRIS_AUDIT_MYSQL_PORT'), 'database' => 'hris_audit_test',
            'username' => getenv('HRIS_AUDIT_MYSQL_USER') ?: 'root',
            'password' => (string) getenv('HRIS_AUDIT_MYSQL_PASSWORD'), 'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci', 'prefix' => '', 'strict' => true,
        ]);
        config()->set('audit', require config_path('audit.php'));
        DB::purge('mysql');
        foreach (['audit_logs', 'employees', 'emp_hrkiosk_reghist'] as $table) Schema::dropIfExists($table);
        (require database_path('migrations/2026_10_08_000001_create_audit_logs_table.php'))->up();
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('emp_ID');
            $table->string('fname');
            $table->string('mname')->nullable();
            $table->string('lname');
            $table->unsignedBigInteger('camp_id')->nullable();
            // Legacy values are not guaranteed numeric; the trigger must not fail on them.
            $table->string('emp_dept')->nullable();
            foreach (['f1', 'f2', 'f3'] as $finger) $table->binary($finger)->nullable();
        });
        Schema::create('emp_hrkiosk_reghist', function (Blueprint $table) {
            $table->id();
            $table->string('enroller_ID');
            $table->date('date');
            $table->text('enrolled_IDs')->nullable();
            $table->text('enrolled_times')->nullable();
            $table->text('enroller_device_ID')->nullable();
        });
        DB::table('employees')->insert([
            ['id' => 1, 'emp_ID' => 'A-1', 'fname' => 'Ada', 'mname' => '', 'lname' => 'Enroller', 'camp_id' => 1, 'emp_dept' => '4', 'f1' => null],
            ['id' => 8, 'emp_ID' => 'E-8', 'fname' => 'Juan', 'mname' => null, 'lname' => 'Cruz', 'camp_id' => 2, 'emp_dept' => 'Registrar', 'f1' => 'TEMPLATE-OLD-1'],
        ]);
        $this->migration()->up();
    }

    public function test_kiosk_enrollment_sql_is_recorded_without_biometric_data(): void
    {
        $this->enroll('A-1', 'E-8', 2, 'TEMPLATE-A', '7', '08:00:00');
        $this->enroll('A-1', 'E-8', 2, 'TEMPLATE-B', '9', '09:00:00');

        // The kiosk's own registration history is unchanged by the triggers.
        $history = DB::table('emp_hrkiosk_reghist')->first();
        $this->assertSame(['E-8-2,E-8-2', '08:00:00,09:00:00', '7,9'],
            [$history->enrolled_IDs, $history->enrolled_times, $history->enroller_device_ID]);

        $events = DB::table('audit_logs')->orderBy('id')->get();
        $this->assertSame(['fingerprint_enrolled', 'kiosk_registration_recorded', 'fingerprint_replaced', 'kiosk_registration_recorded'],
            $events->pluck('action')->all());
        $this->assertSame(['registration'], $events->pluck('module')->unique()->values()->all());
        $this->assertStringNotContainsString('TEMPLATE', $events->toJson());

        $print = $events[0];
        $this->assertSame(['database', 'employee', '8', 'Juan Cruz', 8, 'E-8', 2, null],
            [$print->actor_type, $print->target_type, $print->target_id, $print->target_name,
                (int) $print->employee_id, $print->employee_code, (int) $print->campus_id, $print->office_id]);
        $details = json_decode($print->details, true);
        $this->assertSame(['database_trigger', 'employees', '2'], [$details['source'], $details['table'], $details['finger']]);
        $this->assertNotEmpty($details['db_account']);
        $this->assertNotNull($print->occurred_at);

        $registration = $events[3];
        $this->assertSame(['employee', 1, 'Ada Enroller', 'E-8', 'Juan Cruz'],
            [$registration->actor_type, (int) $registration->actor_id, $registration->actor_name,
                $registration->employee_code, $registration->target_name]);
        $details = json_decode($registration->details, true);
        $this->assertSame(['append', 'A-1', '09:00:00', '2', '9', (int) $history->id],
            [$details['change'], $details['enroller_code'], $details['registration_time'], $details['finger'],
                $details['kiosk_device_id'], $details['reghist_id']]);
        $this->assertSame('insert', json_decode($events[1]->details, true)['change']);

        $this->assertStringContainsString('Juan Cruz: fingerprint 2 was saved directly to the database', AuditFormatter::describe($print));
        $this->assertStringContainsString('fingerprint 2 was replaced', AuditFormatter::describe($events[2]));
        $this->assertStringContainsString('Ada Enroller registered fingerprint 2 for Juan Cruz on an HR Kiosk', AuditFormatter::describe($registration));
    }

    public function test_unrelated_or_identical_writes_are_not_recorded(): void
    {
        DB::update('UPDATE employees SET f1 = ? WHERE emp_ID = ?', ['TEMPLATE-OLD-1', 'E-8']);
        DB::update('UPDATE employees SET fname = ?, emp_dept = ? WHERE emp_ID = ?', ['Juana', '5', 'E-8']);
        DB::update('UPDATE employees SET f2 = ? WHERE emp_ID = ?', ['', 'A-1']);
        $id = DB::table('emp_hrkiosk_reghist')->insertGetId([
            'enroller_ID' => 'A-1', 'date' => '2026-10-21', 'enrolled_IDs' => 'E-8-1', 'enrolled_times' => '08:00:00', 'enroller_device_ID' => '7',
        ]);
        DB::update('UPDATE emp_hrkiosk_reghist SET enrolled_IDs = enrolled_IDs WHERE id = ?', [$id]);

        $this->assertSame(['kiosk_registration_recorded'], DB::table('audit_logs')->pluck('action')->all());
    }

    public function test_history_edits_deletes_and_fingerprint_removal_are_recorded(): void
    {
        $this->enroll('A-1', 'E-8', 1, 'TEMPLATE-NEW', '7', '08:00:00');
        $id = DB::table('emp_hrkiosk_reghist')->value('id');
        DB::table('audit_logs')->insert(['occurred_at' => now(), 'module' => 'marker', 'action' => 'marker', 'outcome' => 'success']);
        $markerId = DB::table('audit_logs')->max('id');

        DB::update('UPDATE emp_hrkiosk_reghist SET enrolled_IDs = ? WHERE id = ?', ['E-9-1', $id]);
        DB::update('UPDATE emp_hrkiosk_reghist SET enroller_ID = ?, enrolled_IDs = ? WHERE id = ?', ['A-2', 'E-9-1,E-8-3', $id]);
        DB::delete('DELETE FROM emp_hrkiosk_reghist WHERE id = ?', [$id]);
        DB::table('emp_hrkiosk_reghist')->insert([
            'enroller_ID' => 'A-1', 'date' => '2026-10-20', 'enrolled_IDs' => 'E-8-1,E-8-2', 'enrolled_times' => '08:00:00,08:01:00',
        ]);
        DB::update('UPDATE employees SET f1 = NULL WHERE emp_ID = ?', ['E-8']);

        $events = DB::table('audit_logs')->where('id', '>', $markerId)->orderBy('id')->get();
        $this->assertSame(['registration_history_modified', 'registration_history_modified', 'registration_history_deleted',
            'registration_history_modified', 'fingerprint_removed'], $events->pluck('action')->all());
        $edit = json_decode($events[0]->details, true);
        $this->assertSame(['E-8-1', 'E-9-1'], [$edit['old_data']['enrolled_IDs'], $edit['new_data']['enrolled_IDs']]);
        $this->assertSame('A-2', json_decode($events[1]->details, true)['new_data']['enroller_ID']);
        $deleted = json_decode($events[2]->details, true);
        $this->assertSame(['E-9-1,E-8-3', null], [$deleted['old_data']['enrolled_IDs'], $deleted['new_data']]);
        $this->assertSame('registration_history', $events[2]->target_type);
        $this->assertStringContainsString('was deleted directly in the database', AuditFormatter::describe($events[2]));
        $this->assertSame(['1', 'E-8'], [json_decode($events[4]->details, true)['finger'], $events[4]->employee_code]);
    }

    public function test_rolled_back_enrollment_leaves_no_entry_and_down_removes_triggers(): void
    {
        DB::beginTransaction();
        DB::update('UPDATE employees SET f3 = ? WHERE emp_ID = ? LIMIT 1', ['TEMPLATE-X', 'E-8']);
        DB::rollBack();
        $this->assertSame(0, DB::table('audit_logs')->count());

        $this->migration()->down();
        $this->enroll('A-1', 'E-8', 3, 'TEMPLATE-Y', '7', '08:00:00');
        $this->assertSame(0, DB::table('audit_logs')->count());
    }

    private function migration()
    {
        return require database_path('migrations/2026_10_21_000001_add_kiosk_registration_audit_triggers.php');
    }

    // Same statements and order as HR Kiosk 1.2.6/1.2.7 Enroll.cs.
    private function enroll(string $enroller, string $code, int $finger, string $template, string $device, string $time): void
    {
        $date = '2026-10-21';
        DB::transaction(function () use ($enroller, $code, $finger, $template, $device, $time, $date) {
            DB::update("UPDATE employees SET f{$finger}=? WHERE emp_ID=? LIMIT 1", [$template, $code]);
            $entry = $code.'-'.$finger;
            $row = DB::selectOne('SELECT enrolled_IDs, enrolled_times, enroller_device_ID FROM emp_hrkiosk_reghist WHERE enroller_ID=? AND date=? LIMIT 1', [$enroller, $date]);
            if ($row) {
                $append = fn ($current, $value) => trim((string) $current) === '' ? $value : $current.','.$value;
                DB::update('UPDATE emp_hrkiosk_reghist SET enrolled_IDs=?, enrolled_times=?, enroller_device_ID=? WHERE enroller_ID=? AND date=? LIMIT 1', [
                    $append($row->enrolled_IDs, $entry), $append($row->enrolled_times, $time), $append($row->enroller_device_ID, $device), $enroller, $date,
                ]);
            } else {
                DB::insert('INSERT INTO emp_hrkiosk_reghist (enroller_ID, enrolled_IDs, enrolled_times, enroller_device_ID, date) VALUES (?, ?, ?, ?, ?)', [
                    $enroller, $entry, $time, $device, $date,
                ]);
            }
        });
    }
}
