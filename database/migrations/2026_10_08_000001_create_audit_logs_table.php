<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->dateTime('occurred_at', 6)->index();

            // Use stable event keys; the UI can change its wording independently.
            $table->string('module', 60);
            $table->string('action', 60);
            $table->string('outcome', 20);
            $table->string('reason_code', 80)->nullable();

            // Actors may be administrators, employees, system jobs, or unknown.
            $table->string('actor_type', 20)->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name')->nullable();

            // Keep a snapshot so a deleted or renamed target remains identifiable.
            $table->string('target_type', 60)->nullable();
            $table->string('target_id', 100)->nullable();
            $table->string('target_name')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->string('employee_code', 100)->nullable();
            $table->unsignedBigInteger('campus_id')->nullable();
            $table->unsignedBigInteger('office_id')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('http_method', 10)->nullable();
            $table->string('route_name', 150)->nullable();
            $table->uuid('request_id')->nullable();

            // For the readable row and the formatted JSON Details panel.
            $table->text('summary')->nullable();
            $table->json('details')->nullable();

            $table->index(['actor_type', 'actor_id', 'occurred_at'], 'audit_logs_actor_time_idx');
            $table->index(['employee_id', 'occurred_at'], 'audit_logs_employee_time_idx');
            $table->index(['employee_code', 'occurred_at'], 'audit_logs_employee_code_time_idx');
            $table->index(['campus_id', 'occurred_at'], 'audit_logs_campus_time_idx');
            $table->index(['office_id', 'occurred_at'], 'audit_logs_office_time_idx');
            $table->index(['module', 'action', 'occurred_at'], 'audit_logs_event_time_idx');
            $table->index(['outcome', 'reason_code', 'occurred_at'], 'audit_logs_result_time_idx');
            $table->index(['ip_address', 'occurred_at'], 'audit_logs_ip_time_idx');
            $table->index(['target_type', 'target_id', 'occurred_at'], 'audit_logs_target_time_idx');
            $table->index('request_id');
        });

        // Append-only: reject edits and deletes. TRUNCATE and DROP TRIGGER still bypass this.
        if (DB::getDriverName() === 'mysql') {
            foreach (['UPDATE' => 'audit_logs_no_update', 'DELETE' => 'audit_logs_no_delete'] as $event => $name) {
                DB::unprepared("CREATE TRIGGER {$name} BEFORE {$event} ON audit_logs FOR EACH ROW
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'audit_logs is append-only'");
            }
        }
    }

    public function down()
    {
        Schema::dropIfExists('audit_logs');
    }
};
