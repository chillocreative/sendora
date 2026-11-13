<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_numbers', function (Blueprint $table) {
            $table->string('provider')->default('baileys'); // baileys | cloud
            $table->string('waba_id')->nullable();
            $table->string('phone_number_id')->nullable()->unique();
            $table->string('display_phone_number')->nullable();
            $table->text('access_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->string('quality_rating')->nullable();
            $table->string('messaging_limit')->nullable();
            $table->timestamp('webhook_subscribed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_numbers', function (Blueprint $table) {
            $table->dropUnique(['phone_number_id']);
            $table->dropColumn([
                'provider',
                'waba_id',
                'phone_number_id',
                'display_phone_number',
                'access_token',
                'token_expires_at',
                'quality_rating',
                'messaging_limit',
                'webhook_subscribed_at',
            ]);
        });
    }
};
