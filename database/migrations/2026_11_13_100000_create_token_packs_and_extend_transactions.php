<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('token_packs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('tokens');
            $table->decimal('price', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('token_packs')->insert([
            'name' => '1M AI tokens',
            'tokens' => 1_000_000,
            'price' => 10,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('transactions', function (Blueprint $table) {
            $table->string('gateway_id')->nullable()->index(); // Chip-in purchase id, used to verify payment server-side
            $table->foreignId('token_pack_id')->nullable()->constrained('token_packs')->nullOnDelete();
            $table->unsignedBigInteger('token_amount')->nullable(); // snapshot of tokens bought
            $table->timestamp('fulfilled_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('token_pack_id');
            $table->dropColumn(['gateway_id', 'token_amount', 'fulfilled_at']);
        });
        Schema::dropIfExists('token_packs');
    }
};
