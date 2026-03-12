<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Basic',
                'monthly_price' => 59,
                'yearly_price' => 0,
                'limits' => [
                    'whatsapp_nos' => 1,
                    'reminders_per_month' => 50,
                    'features' => [
                        'google_calendar' => true,
                        'ai_command_parsing' => false,
                        'auto_reply' => false,
                        'api_access' => false,
                    ],
                ],
            ],
            [
                'name' => 'Pro',
                'monthly_price' => 129,
                'yearly_price' => 0,
                'limits' => [
                    'whatsapp_nos' => 2,
                    'reminders_per_month' => 500,
                    'features' => [
                        'google_calendar' => true,
                        'ai_command_parsing' => true,
                        'auto_reply' => true,
                        'api_access' => false,
                    ],
                ],
            ],
            [
                'name' => 'Business',
                'monthly_price' => 249,
                'yearly_price' => 0,
                'limits' => [
                    'whatsapp_nos' => 5,
                    'reminders_per_month' => 0,
                    'features' => [
                        'google_calendar' => true,
                        'ai_command_parsing' => true,
                        'auto_reply' => true,
                        'api_access' => true,
                    ],
                ],
            ],
        ];

        foreach ($plans as $plan) {
            \App\Models\SubscriptionPlan::updateOrCreate(['name' => $plan['name']], $plan);
        }

        // Lifetime plan — only insert if it doesn't already exist
        \App\Models\SubscriptionPlan::firstOrCreate(['name' => 'Lifetime'], [
            'monthly_price' => 1999,
            'yearly_price' => 0,
            'is_lifetime' => true,
            'is_active' => true,
            'limits' => [
                'whatsapp_nos' => 2,
                'reminders_per_month' => 500,
                'features' => [
                    'google_calendar' => true,
                    'ai_command_parsing' => true,
                    'auto_reply' => true,
                    'api_access' => false,
                ],
            ],
        ]);
    }
}
