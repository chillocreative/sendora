<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return; // MODIFY COLUMN is MySQL-only (sqlite is used by the test suite)
        }

        DB::statement("ALTER TABLE reminders MODIFY COLUMN source ENUM('web','whatsapp_command','google_calendar','whatsapp_media') DEFAULT 'web'");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE reminders MODIFY COLUMN source ENUM('web','whatsapp_command','google_calendar') DEFAULT 'web'");
    }
};
