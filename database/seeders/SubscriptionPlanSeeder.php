<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Basic',
                'monthly_price' => 59,
                'yearly_price' => 590,
                'limits' => [
                    'whatsapp_nos' => 1,
                    'contacts' => 500,
                    'messages' => 1000,
                    'features' => [
                        'text_support' => true,
                        'image_support' => true,
                        'file_support' => true,
                        'scheduling' => true,
                        'pdf_support' => false,
                        'link_preview' => false,
                        'auto_reply' => false,
                        'message_preview' => false,
                        'multi_user' => false,
                        'webhooks' => false,
                        'api_access' => false,
                    ],
                ],
            ],
            [
                'name' => 'Pro',
                'monthly_price' => 129,
                'yearly_price' => 1290,
                'limits' => [
                    'whatsapp_nos' => 2,
                    'contacts' => 2000,
                    'messages' => 5000,
                    'features' => [
                        'text_support' => true,
                        'image_support' => true,
                        'file_support' => true,
                        'scheduling' => true,
                        'pdf_support' => true,
                        'link_preview' => true,
                        'auto_reply' => true,
                        'message_preview' => true,
                        'multi_user' => false,
                        'webhooks' => false,
                        'api_access' => false,
                    ],
                ],
            ],
            [
                'name' => 'Business',
                'monthly_price' => 249,
                'yearly_price' => 2490,
                'limits' => [
                    'whatsapp_nos' => 5,
                    'contacts' => 10000,
                    'messages' => 20000,
                    'features' => [
                        'text_support' => true,
                        'image_support' => true,
                        'file_support' => true,
                        'scheduling' => true,
                        'pdf_support' => true,
                        'link_preview' => true,
                        'auto_reply' => true,
                        'message_preview' => true,
                        'multi_user' => true,
                        'webhooks' => true,
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
                'contacts' => 2000,
                'messages' => 5000,
                'features' => [
                    'text_support' => true,
                    'image_support' => true,
                    'file_support' => true,
                    'scheduling' => true,
                    'pdf_support' => true,
                    'link_preview' => true,
                    'auto_reply' => true,
                    'message_preview' => true,
                    'multi_user' => false,
                    'webhooks' => false,
                    'api_access' => false,
                ],
            ],
        ]);
    }
}
