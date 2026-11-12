<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->string('waba_id', 64)->index();
            $table->string('purpose'); // reminder | admin_notification
            $table->string('name', 128); // Meta limit: 512 chars, lowercase/underscore names are short in practice
            $table->string('language', 16); // e.g. en, ms
            $table->json('variables'); // ordered list of variable keys
            $table->string('status')->default('pending'); // pending|approved|rejected|paused|disabled
            $table->string('meta_template_id')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->unique(['waba_id', 'name', 'language']);
        });

        Schema::table('whatsapp_numbers', function (Blueprint $table) {
            $table->string('owner_phone')->nullable();
            $table->timestamp('owner_phone_verified_at')->nullable();
            $table->string('owner_verification_code')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_numbers', function (Blueprint $table) {
            $table->dropColumn(['owner_phone', 'owner_phone_verified_at', 'owner_verification_code']);
        });

        Schema::dropIfExists('whatsapp_templates');
    }
};
