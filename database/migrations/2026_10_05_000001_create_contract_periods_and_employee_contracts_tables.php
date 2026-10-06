<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Contract of Services module.
 * Creates two NEW tables only. No existing table is altered and there is no
 * foreign key to any existing table (employees / campuses are referenced by id only).
 *
 * Run only this file:
 *   php artisan migrate --path=database/migrations/2026_10_05_000001_create_contract_periods_and_employee_contracts_tables.php
 */
return new class extends Migration
{
    protected $connection = 'mysql';

    public function up()
    {
        if (!Schema::connection($this->connection)->hasTable('contract_periods')) {
            Schema::connection($this->connection)->create('contract_periods', function (Blueprint $table) {
                $table->id();
                $table->string('contract_type', 50)->index();
                $table->string('title');
                $table->date('start_date');
                $table->date('end_date');
                $table->string('status', 20)->default('open')->index();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::connection($this->connection)->hasTable('employee_contracts')) {
            Schema::connection($this->connection)->create('employee_contracts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('contract_period_id')->constrained('contract_periods')->cascadeOnDelete();
                $table->unsignedBigInteger('employee_id')->index();
                $table->string('reference_no', 50)->unique();
                $table->unsignedBigInteger('campus_id')->nullable()->index();
                $table->string('employee_name');
                $table->string('position');
                $table->decimal('monthly_rate', 12, 2);
                $table->decimal('daily_deduction', 12, 2);
                $table->string('status', 20)->default('active')->index();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['contract_period_id', 'employee_id'], 'employee_contracts_period_employee_unique');
            });
        }
    }

    public function down()
    {
        Schema::connection($this->connection)->dropIfExists('employee_contracts');
        Schema::connection($this->connection)->dropIfExists('contract_periods');
    }
};
