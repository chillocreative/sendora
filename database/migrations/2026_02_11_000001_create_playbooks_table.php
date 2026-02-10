<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('playbooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->longText('content');
            $table->boolean('is_active')->default(true);
            $table->string('model')->default('gpt-4o');
            $table->decimal('temperature', 3, 2)->default(0.70);
            $table->unsignedInteger('max_tokens')->default(500);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('playbooks');
    }
};
