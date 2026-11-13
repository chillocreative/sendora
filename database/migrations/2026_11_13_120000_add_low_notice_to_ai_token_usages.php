<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_token_usages', function (Blueprint $table) {
            // Set once per user+period when the "running low" email is claimed.
            $table->timestamp('low_notice_sent_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ai_token_usages', function (Blueprint $table) {
            $table->dropColumn('low_notice_sent_at');
        });
    }
};
