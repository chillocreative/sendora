<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop junction/child tables first (foreign key order)
        Schema::dropIfExists('campaign_messages');
        Schema::dropIfExists('contact_contact_book');
        Schema::dropIfExists('contact_tag');
        Schema::dropIfExists('warmer_logs');
        Schema::dropIfExists('auto_replies');

        // Drop parent tables
        Schema::dropIfExists('campaigns');
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('contact_books');
        Schema::dropIfExists('tags');

        // Remove warmer columns from users
        if (Schema::hasColumn('users', 'warmer_enabled')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('warmer_enabled');
            });
        }

        // Remove warmer columns from whatsapp_numbers
        $warmerColumns = ['is_warmer_pool_enabled', 'warmer_daily_limit', 'warmer_messages_sent_today', 'warmer_last_chatted_at'];
        $existingColumns = [];
        foreach ($warmerColumns as $col) {
            if (Schema::hasColumn('whatsapp_numbers', $col)) {
                $existingColumns[] = $col;
            }
        }
        if (!empty($existingColumns)) {
            Schema::table('whatsapp_numbers', function (Blueprint $table) use ($existingColumns) {
                $table->dropColumn($existingColumns);
            });
        }
    }

    public function down(): void
    {
        // Not reversible - these tables and features are permanently removed
    }
};
