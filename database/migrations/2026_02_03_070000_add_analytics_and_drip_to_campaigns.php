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
        // Add drip sequence support to campaigns
        Schema::table('campaigns', function (Blueprint $table) {
            $table->boolean('is_drip')->default(false)->after('status');
            $table->integer('drip_delay_minutes')->nullable()->after('is_drip'); // Delay between messages
        });

        // Add analytics tracking to campaign_messages
        Schema::table('campaign_messages', function (Blueprint $table) {
            $table->timestamp('delivered_at')->nullable()->after('sent_at');
            $table->timestamp('read_at')->nullable()->after('delivered_at');
            $table->timestamp('clicked_at')->nullable()->after('read_at');
            $table->integer('sequence_order')->default(1)->after('clicked_at'); // For drip sequences
            $table->integer('delay_minutes')->default(0)->after('sequence_order'); // Delay before this message
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['is_drip', 'drip_delay_minutes']);
        });

        Schema::table('campaign_messages', function (Blueprint $table) {
            $table->dropColumn(['delivered_at', 'read_at', 'clicked_at', 'sequence_order', 'delay_minutes']);
        });
    }
};
