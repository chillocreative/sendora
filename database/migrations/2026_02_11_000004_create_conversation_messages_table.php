<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversation_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            $table->enum('direction', ['inbound', 'outbound']);
            $table->enum('sender_type', ['customer', 'ai', 'human']);
            $table->text('body');
            $table->string('wa_message_id')->nullable();
            $table->decimal('confidence_score', 4, 3)->nullable();
            $table->string('reasoning_source')->nullable();
            $table->string('escalation_reason')->nullable();
            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('completion_tokens')->nullable();
            $table->unsignedInteger('total_tokens')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->json('ai_metadata')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
            $table->index('wa_message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_messages');
    }
};
