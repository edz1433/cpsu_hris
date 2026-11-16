<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->boolean('shared_for_viewing')->default(false)->index();
        });
        Schema::table('employees', function (Blueprint $table) {
            $table->boolean('can_view_career_applications')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('can_view_career_applications');
        });
        Schema::table('applications', function (Blueprint $table) {
            $table->dropIndex(['shared_for_viewing']);
            $table->dropColumn('shared_for_viewing');
        });
    }
};
