<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('whatsapp_number_id')->constrained()->onDelete('cascade');
            $table->string('contact_phone', 32);
            $table->string('contact_name')->nullable();
            $table->enum('status', ['active', 'escalated', 'closed'])->default('active');
            $table->string('escalation_reason')->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->timestamp('last_customer_message_at')->nullable();
            $table->timestamp('last_ai_reply_at')->nullable();
            $table->unsignedInteger('message_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['whatsapp_number_id', 'contact_phone'], 'conv_number_contact_unique');
            $table->index(['user_id', 'status']);
            $table->index('last_customer_message_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
