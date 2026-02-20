<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_contact_book', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_book_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['contact_id', 'contact_book_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_contact_book');
    }
};
