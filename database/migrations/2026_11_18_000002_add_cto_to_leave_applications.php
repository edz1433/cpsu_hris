<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Compensatory Time-Off (CTO) applications are leave applications with
 * leave_type 16. They are filed in hours on one or more dates, so the dates
 * and hours live in leave_cto_dates, and the application keeps the totals.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_applications', function (Blueprint $table) {
            if (!Schema::hasColumn('leave_applications', 'cto_hours')) {
                // Total hours of CTO requested.
                $table->decimal('cto_hours', 8, 2)->nullable();
            }
            if (!Schema::hasColumn('leave_applications', 'coc_balance')) {
                // Earned COC hours the employee had when filing.
                $table->decimal('coc_balance', 8, 2)->nullable();
            }
        });

        if (!Schema::hasTable('leave_cto_dates')) {
            Schema::create('leave_cto_dates', function (Blueprint $table) {
                $table->id();
                $table->integer('leave_application_id')->index();
                $table->date('cto_date');
                $table->decimal('hours', 5, 2);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_cto_dates');

        Schema::table('leave_applications', function (Blueprint $table) {
            foreach (['cto_hours', 'coc_balance'] as $column) {
                if (Schema::hasColumn('leave_applications', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
