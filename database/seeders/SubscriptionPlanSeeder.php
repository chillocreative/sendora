<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Meta's per-message template fees are billed by Meta to the customer's own WhatsApp
     * Business account (Embedded Signup), so plan prices cover the platform only.
     *
     * ai_tokens_per_month: 0 or missing means unlimited (see AiUsageService).
     * reminders_per_month: 0 means unlimited.
     */
    public static function plans(): array
    {
        return [
            [
                'name' => 'Starter',
                'monthly_price' => 49,
                'yearly_price' => 490,
                'limits' => [
                    'whatsapp_nos' => 1,
                    'reminders_per_month' => 300,
                    'ai_tokens_per_month' => 200_000,
                    'features' => [
                        'google_calendar' => true,
                        'ai_command_parsing' => false,
                        'auto_reply' => false,
                        'api_access' => false,
                    ],
                ],
            ],
            [
                'name' => 'Growth',
                'monthly_price' => 89,
                'yearly_price' => 890,
                'limits' => [
                    'whatsapp_nos' => 2,
                    'reminders_per_month' => 1500,
                    'ai_tokens_per_month' => 500_000,
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
                'monthly_price' => 149,
                'yearly_price' => 1490,
                'limits' => [
                    'whatsapp_nos' => 5,
                    'reminders_per_month' => 0,
                    'ai_tokens_per_month' => 1_500_000,
                    'features' => [
                        'google_calendar' => true,
                        'ai_command_parsing' => true,
                        'auto_reply' => true,
                        'api_access' => true,
                    ],
                ],
            ],
        ];
    }

    public function run(): void
    {
        // Retire every other sellable plan (Basic, Pro, ...). They keep their old terms for
        // existing subscribers, but are hidden and cannot be bought, so legacy plans without
        // an AI token allowance (unlimited) cannot be chosen by new customers.
        $current = array_column(self::plans(), 'name');
        SubscriptionPlan::where('is_lifetime', false)
            ->where('is_active', true)
            ->whereNotIn('name', $current)
            ->get()
            ->each(fn ($old) => $old->update([
                'name' => str_ends_with($old->name, '(Legacy)') ? $old->name : $old->name.' (Legacy)',
                'is_active' => false,
            ]));

        foreach (self::plans() as $plan) {
            // Existing subscribers keep their old terms: a same-named plan with a different
            // price is retired (renamed, hidden from pricing) rather than overwritten.
            $legacy = SubscriptionPlan::where('name', $plan['name'])
                ->where('is_lifetime', false)
                ->where('monthly_price', '!=', $plan['monthly_price'])
                ->first();

            if ($legacy) {
                $legacy->update(['name' => $plan['name'].' (Legacy)', 'is_active' => false]);
            }

            SubscriptionPlan::updateOrCreate(['name' => $plan['name']], $plan + ['is_active' => true]);
        }

        // Lifetime plan — only insert if it doesn't already exist
        SubscriptionPlan::firstOrCreate(['name' => 'Lifetime'], [
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
