<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_numbers', function (Blueprint $table) {
            $table->foreignId('playbook_id')->nullable()->after('phone_info')
                  ->constrained('playbooks')->nullOnDelete();
            $table->boolean('ai_reply_enabled')->default(false)->after('playbook_id');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_numbers', function (Blueprint $table) {
            $table->dropForeign(['playbook_id']);
            $table->dropColumn(['playbook_id', 'ai_reply_enabled']);
        });
    }
};
