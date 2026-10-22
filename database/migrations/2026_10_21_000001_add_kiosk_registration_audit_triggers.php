<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// HR Kiosk 1.2.6/1.2.7 enroll fingerprints with direct SQL, bypassing Laravel.
// These triggers record those writes; a failed trigger fails the kiosk's statement.
return new class extends Migration
{
    private const TRIGGERS = [
        'employees_fingerprint_audit', 'hrkiosk_reghist_insert_audit',
        'hrkiosk_reghist_update_audit', 'hrkiosk_reghist_delete_audit',
    ];

    public function up()
    {
        if (DB::getDriverName() !== 'mysql') return;

        $dept = fn (string $row) => "IF(CAST({$row}.camp_id AS CHAR) REGEXP '^[0-9]+$', {$row}.camp_id, NULL),
            IF(CAST({$row}.emp_dept AS CHAR) REGEXP '^[0-9]+$', {$row}.emp_dept, NULL)";
        $name = fn (string $row) => "NULLIF(TRIM(CONCAT_WS(' ', NULLIF({$row}.fname, ''), NULLIF({$row}.mname, ''), NULLIF({$row}.lname, ''))), '')";
        $client = "LEFT(SUBSTRING_INDEX(USER(), '@', -1), 45)";
        // Trigger variables and the history table may not share employees.emp_ID's collation (error 1267 would fail the kiosk).
        // --pretend runs no SELECTs, so it prints the SQL with a placeholder collation.
        $empId = DB::pretending() ? (object) ['cs' => 'utf8mb4', 'co' => 'utf8mb4_unicode_ci']
            : DB::selectOne("SELECT CHARACTER_SET_NAME AS cs, COLLATION_NAME AS co FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employees' AND COLUMN_NAME = 'emp_ID'")
            ?? throw new RuntimeException('employees.emp_ID was not found; kiosk triggers were not created.');
        $empIdType = "CHARACTER SET {$empId->cs} COLLATE {$empId->co}";
        $enrollerId = "CONVERT(NEW.enroller_ID USING {$empId->cs}) COLLATE {$empId->co}";
        $columns = 'occurred_at, module, action, outcome, actor_type, actor_id, actor_name, target_type, target_id,
            target_name, employee_id, employee_code, campus_id, office_id, ip_address, details';

        // Templates are never copied; only which finger changed.
        DB::unprepared("CREATE TRIGGER employees_fingerprint_audit AFTER UPDATE ON employees FOR EACH ROW
        BEGIN
            DECLARE finger INT DEFAULT 1;
            DECLARE had_print, has_print INT;
            IF NOT (OLD.f1 <=> NEW.f1 AND OLD.f2 <=> NEW.f2 AND OLD.f3 <=> NEW.f3) THEN
                WHILE finger <= 3 DO
                    SET had_print = ELT(finger, IFNULL(LENGTH(OLD.f1), 0) > 0, IFNULL(LENGTH(OLD.f2), 0) > 0, IFNULL(LENGTH(OLD.f3), 0) > 0);
                    SET has_print = ELT(finger, IFNULL(LENGTH(NEW.f1), 0) > 0, IFNULL(LENGTH(NEW.f2), 0) > 0, IFNULL(LENGTH(NEW.f3), 0) > 0);
                    IF ELT(finger, NOT (OLD.f1 <=> NEW.f1), NOT (OLD.f2 <=> NEW.f2), NOT (OLD.f3 <=> NEW.f3))
                        AND (had_print OR has_print) THEN
                        INSERT INTO audit_logs ({$columns}) VALUES (NOW(6), 'registration',
                            IF(NOT had_print, 'fingerprint_enrolled', IF(NOT has_print, 'fingerprint_removed', 'fingerprint_replaced')),
                            'success', 'database', NULL, NULL, 'employee', NEW.id,
                            LEFT(COALESCE({$name('NEW')}, NEW.emp_ID), 255), NEW.id, LEFT(NEW.emp_ID, 100), {$dept('NEW')}, {$client},
                            JSON_OBJECT('source', 'database_trigger', 'table', 'employees', 'finger', finger, 'db_account', USER()));
                    END IF;
                    SET finger = finger + 1;
                END WHILE;
            END IF;
        END");

        // A kiosk enrollment appends one "code-finger" token, time and device to the enroller's daily row.
        $recorded = fn (string $event) => "
            SET finger = IF(token REGEXP '-[123]$', RIGHT(token, 1), NULL);
            SET code = IF(finger IS NULL, token, LEFT(token, CHAR_LENGTH(token) - 2));
            INSERT INTO audit_logs ({$columns})
            SELECT NOW(6), 'registration', 'kiosk_registration_recorded', 'success', 'employee', enroller.id,
                LEFT(COALESCE({$name('enroller')}, {$enrollerId}), 255), 'employee', LEFT(COALESCE(target.id, code), 100),
                LEFT(COALESCE({$name('target')}, code), 255), target.id, LEFT(code, 100), {$dept('target')}, {$client},
                JSON_OBJECT('source', 'database_trigger', 'table', 'emp_hrkiosk_reghist', 'change', '{$event}',
                    'reghist_id', NEW.id, 'enroller_code', NEW.enroller_ID, 'registration_date', NEW.date,
                    'registration_time', reg_time, 'finger', finger, 'kiosk_device_id', device, 'db_account', USER())
            FROM (SELECT 1) AS one
            LEFT JOIN employees AS enroller ON enroller.emp_ID = {$enrollerId}
            LEFT JOIN employees AS target ON target.emp_ID = code
            LIMIT 1;";
        $history = fn (string $row) => "JSON_OBJECT('enroller_ID', {$row}.enroller_ID, 'date', {$row}.date,
            'enrolled_IDs', {$row}.enrolled_IDs, 'enrolled_times', {$row}.enrolled_times, 'enroller_device_ID', {$row}.enroller_device_ID)";
        // Anything other than the kiosk's single append is evidence of a history edit.
        $changed = fn (string $event, string $row, string $old, string $new) => "
            INSERT INTO audit_logs ({$columns}) VALUES (NOW(6), 'registration', 'registration_history_{$event}', 'success',
                'database', NULL, NULL, 'registration_history', {$row}.id,
                LEFT(CONCAT('Registration history #', {$row}.id, ' (enroller ', IFNULL({$row}.enroller_ID, '?'), ', ', IFNULL({$row}.date, '?'), ')'), 255),
                NULL, NULL, NULL, NULL, {$client},
                JSON_OBJECT('source', 'database_trigger', 'table', 'emp_hrkiosk_reghist', 'reghist_id', {$row}.id,
                    'old_data', {$old}, 'new_data', {$new}, 'db_account', USER()));";
        $declare = "DECLARE token, reg_time, device TEXT; DECLARE code VARCHAR(255) {$empIdType}; DECLARE finger CHAR(1);";
        $appended = fn (string $field) => "IF(IFNULL(OLD.{$field}, '') = '', NEW.{$field},
            IF(LEFT(NEW.{$field}, CHAR_LENGTH(OLD.{$field}) + 1) = CONCAT(OLD.{$field}, ','),
                SUBSTRING(NEW.{$field}, CHAR_LENGTH(OLD.{$field}) + 2), NULL))";

        DB::unprepared("CREATE TRIGGER hrkiosk_reghist_insert_audit AFTER INSERT ON emp_hrkiosk_reghist FOR EACH ROW
        BEGIN
            {$declare}
            SET token = NEW.enrolled_IDs, reg_time = NEW.enrolled_times, device = NEW.enroller_device_ID;
            IF IFNULL(token, '') <> '' AND LOCATE(',', token) = 0 THEN
                {$recorded('insert')}
            ELSE
                {$changed('modified', 'NEW', 'NULL', $history('NEW'))}
            END IF;
        END");

        DB::unprepared("CREATE TRIGGER hrkiosk_reghist_update_audit AFTER UPDATE ON emp_hrkiosk_reghist FOR EACH ROW
        BEGIN
            {$declare}
            IF NOT (OLD.enroller_ID <=> NEW.enroller_ID AND OLD.date <=> NEW.date AND OLD.enrolled_IDs <=> NEW.enrolled_IDs
                AND OLD.enrolled_times <=> NEW.enrolled_times AND OLD.enroller_device_ID <=> NEW.enroller_device_ID) THEN
                SET token = {$appended('enrolled_IDs')}, reg_time = {$appended('enrolled_times')},
                    device = {$appended('enroller_device_ID')};
                IF OLD.enroller_ID <=> NEW.enroller_ID AND OLD.date <=> NEW.date
                    AND IFNULL(token, '') <> '' AND LOCATE(',', token) = 0 THEN
                    {$recorded('append')}
                ELSE
                    {$changed('modified', 'NEW', $history('OLD'), $history('NEW'))}
                END IF;
            END IF;
        END");

        DB::unprepared("CREATE TRIGGER hrkiosk_reghist_delete_audit AFTER DELETE ON emp_hrkiosk_reghist FOR EACH ROW
        BEGIN
            {$changed('deleted', 'OLD', $history('OLD'), 'NULL')}
        END");
    }

    public function down()
    {
        if (DB::getDriverName() !== 'mysql') return;
        foreach (self::TRIGGERS as $trigger) DB::unprepared("DROP TRIGGER IF EXISTS {$trigger}");
    }
};
