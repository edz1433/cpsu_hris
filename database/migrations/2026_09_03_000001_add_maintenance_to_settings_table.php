<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('settings', 'maintenance')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->boolean('maintenance')->default(false);
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('settings', 'maintenance')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropColumn('maintenance');
            });
        }
    }
};
