<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('warmer_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('from_number_id');
            $table->unsignedBigInteger('to_number_id');
            $table->text('message');
            $table->string('role')->default('starter'); // starter, replier
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warmer_logs');
    }
};
