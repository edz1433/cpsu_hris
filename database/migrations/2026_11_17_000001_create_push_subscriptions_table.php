<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            // 'user' = admin/HR account (web guard), 'employee' = employee account (employee guard).
            $table->string('subscriber_type', 20);
            $table->unsignedBigInteger('subscriber_id');
            $table->text('endpoint');
            // Endpoints are long URLs, so uniqueness is enforced on their hash.
            $table->char('endpoint_hash', 64)->unique();
            $table->string('public_key');
            $table->string('auth_token');
            $table->string('content_encoding', 20)->default('aes128gcm');
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['subscriber_type', 'subscriber_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
