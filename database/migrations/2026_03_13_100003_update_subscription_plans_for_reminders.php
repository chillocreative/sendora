<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\SubscriptionPlan;

return new class extends Migration
{
    public function up(): void
    {
        $planUpdates = [
            'Basic' => [
                'whatsapp_nos' => 1,
                'reminders_per_month' => 50,
                'features' => [
                    'google_calendar' => true,
                    'ai_command_parsing' => false,
                    'auto_reply' => false,
                    'api_access' => false,
                ],
            ],
            'Pro' => [
                'whatsapp_nos' => 2,
                'reminders_per_month' => 500,
                'features' => [
                    'google_calendar' => true,
                    'ai_command_parsing' => true,
                    'auto_reply' => true,
                    'api_access' => false,
                ],
            ],
            'Business' => [
                'whatsapp_nos' => 5,
                'reminders_per_month' => 0, // unlimited
                'features' => [
                    'google_calendar' => true,
                    'ai_command_parsing' => true,
                    'auto_reply' => true,
                    'api_access' => true,
                ],
            ],
            'Lifetime' => [
                'whatsapp_nos' => 2,
                'reminders_per_month' => 500,
                'features' => [
                    'google_calendar' => true,
                    'ai_command_parsing' => true,
                    'auto_reply' => true,
                    'api_access' => false,
                ],
            ],
        ];

        foreach ($planUpdates as $name => $limits) {
            SubscriptionPlan::where('name', $name)->update(['limits' => $limits]);
        }
    }

    public function down(): void
    {
        // Reverting plan limits is not practical since the old campaign features are being removed
    }
};
