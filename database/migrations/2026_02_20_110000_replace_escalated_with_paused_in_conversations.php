<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Add 'paused' alongside 'escalated' so no data is lost
        DB::statement("ALTER TABLE conversations MODIFY COLUMN status ENUM('active', 'escalated', 'paused', 'closed') NOT NULL DEFAULT 'active'");

        // Step 2: Migrate all escalated conversations to paused
        DB::table('conversations')->where('status', 'escalated')->update(['status' => 'paused']);

        // Step 3: Remove 'escalated' from the enum now that no rows use it
        DB::statement("ALTER TABLE conversations MODIFY COLUMN status ENUM('active', 'paused', 'closed') NOT NULL DEFAULT 'active'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE conversations MODIFY COLUMN status ENUM('active', 'escalated', 'paused', 'closed') NOT NULL DEFAULT 'active'");
        DB::table('conversations')->where('status', 'paused')->update(['status' => 'escalated']);
        DB::statement("ALTER TABLE conversations MODIFY COLUMN status ENUM('active', 'escalated', 'closed') NOT NULL DEFAULT 'active'");
    }
};
