<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Compensatory Overtime Credits balance (shown under "Other leave credits").
    public function up(): void
    {
        if (!Schema::hasColumn('employees', 'coc_leave')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->decimal('coc_leave', 8, 3)->default(0);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('employees', 'coc_leave')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropColumn('coc_leave');
            });
        }
    }
};
