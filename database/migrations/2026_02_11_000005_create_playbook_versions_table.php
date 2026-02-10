<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('playbook_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('playbook_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('version_number');
            $table->longText('content');
            $table->string('change_summary', 500)->nullable();
            $table->string('source')->default('manual');
            $table->timestamp('created_at')->nullable();

            $table->index(['playbook_id', 'version_number']);
        });

        // Backfill existing playbooks with version 1
        $playbooks = DB::table('playbooks')->get();
        foreach ($playbooks as $playbook) {
            DB::table('playbook_versions')->insert([
                'playbook_id' => $playbook->id,
                'version_number' => 1,
                'content' => $playbook->content,
                'change_summary' => 'Initial version (backfilled)',
                'source' => 'manual',
                'created_at' => $playbook->created_at,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('playbook_versions');
    }
};
