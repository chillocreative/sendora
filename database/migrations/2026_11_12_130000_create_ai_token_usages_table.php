<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_token_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->char('period', 7); // YYYY-MM
            $table->unsignedBigInteger('tokens_used')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'period']);
        });

        // Purchased token packs: never expire, consumed only after the monthly allowance.
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('ai_bonus_tokens')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('ai_bonus_tokens');
        });
        Schema::dropIfExists('ai_token_usages');
    }
};
