<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('notification_type'); // 'payment', 'ticket', 'cancellation', 'registration', etc.
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->json('data'); // Flexible data payload for each notification type
            $table->timestamp('sent_at')->nullable();
            $table->integer('failed_attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['notification_type', 'sent_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notifications');
    }
};
