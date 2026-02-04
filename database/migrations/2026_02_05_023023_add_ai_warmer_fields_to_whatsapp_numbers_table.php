<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('whatsapp_numbers', function (Blueprint $table) {
            $table->boolean('is_warmer_pool_enabled')->default(false);
            $table->integer('warmer_daily_limit')->default(50);
            $table->integer('warmer_messages_sent_today')->default(0);
            $table->timestamp('warmer_last_chatted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_numbers', function (Blueprint $table) {
            $table->dropColumn(['is_warmer_pool_enabled', 'warmer_daily_limit', 'warmer_messages_sent_today', 'warmer_last_chatted_at']);
        });
    }
};
