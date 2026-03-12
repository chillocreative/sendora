<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('whatsapp_number_id')->nullable()->constrained()->nullOnDelete();
            $table->string('google_event_id')->nullable();
            $table->foreignId('google_calendar_connection_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('reminder_at');
            $table->dateTime('event_at')->nullable();
            $table->unsignedSmallInteger('minutes_before')->default(15);
            $table->string('location')->nullable();
            $table->string('recurrence_rule')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed', 'cancelled'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->string('wa_message_id')->nullable();
            $table->text('error_message')->nullable();
            $table->enum('source', ['web', 'whatsapp_command', 'google_calendar'])->default('web');
            $table->timestamps();

            $table->index(['user_id', 'status', 'reminder_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminders');
    }
};
