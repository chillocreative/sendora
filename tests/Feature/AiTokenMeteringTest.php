<?php

namespace Tests\Feature;

use App\Exceptions\AiTokenLimitReached;
use App\Models\Setting;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\AiUsageService;
use App\Services\OpenAiService;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiTokenMeteringTest extends TestCase
{
    use RefreshDatabase;

    private function userWithAllowance(?int $tokens): User
    {
        $limits = ['whatsapp_nos' => 1, 'reminders_per_month' => 0, 'features' => []];
        if ($tokens !== null) {
            $limits['ai_tokens_per_month'] = $tokens;
        }
        $plan = SubscriptionPlan::create(['name' => 'T'.uniqid(), 'monthly_price' => 1, 'yearly_price' => 1, 'limits' => $limits]);
        $user = User::factory()->create();
        UserSubscription::create(['user_id' => $user->id, 'subscription_plan_id' => $plan->id, 'status' => 'active']);

        return $user;
    }

    public function test_recording_reduces_remaining_and_blocks_at_zero(): void
    {
        $svc = app(AiUsageService::class);
        $user = $this->userWithAllowance(1000);

        $svc->record($user, 400);
        $this->assertSame(600, $svc->remaining($user));

        $svc->record($user, 700); // a call may overshoot; the next one is refused
        $this->assertSame(0, $svc->remaining($user));
        $this->expectException(AiTokenLimitReached::class);
        $svc->assertAvailable($user);
    }

    public function test_missing_or_zero_allowance_is_unlimited_and_not_tracked(): void
    {
        $svc = app(AiUsageService::class);

        foreach ([null, 0] as $allowance) {
            $user = $this->userWithAllowance($allowance);
            $svc->record($user, 5_000_000);
            $this->assertNull($svc->remaining($user));
            $this->assertTrue($svc->hasTokens($user));
        }
        $this->assertDatabaseCount('ai_token_usages', 0);
    }

    public function test_bonus_tokens_are_spent_only_after_the_monthly_allowance(): void
    {
        $svc = app(AiUsageService::class);
        $user = $this->userWithAllowance(1000);
        $user->forceFill(['ai_bonus_tokens' => 500])->save();

        $svc->record($user, 800);
        $this->assertSame(200 + 500, $svc->remaining($user));

        $svc->record($user, 400); // 200 monthly + 200 bonus
        $this->assertSame(300, $user->fresh()->ai_bonus_tokens);
        $this->assertSame(300, $svc->remaining($user));
    }

    public function test_usage_resets_in_a_new_month(): void
    {
        $svc = app(AiUsageService::class);
        $user = $this->userWithAllowance(1000);

        $this->travelTo(now()->subMonth());
        $svc->record($user, 1000);
        $this->assertSame(0, $svc->remaining($user));

        $this->travelBack();
        $this->assertSame(1000, $svc->remaining($user));
    }

    public function test_metered_chat_completion_records_usage_and_refuses_when_exhausted(): void
    {
        Setting::create(['key' => 'openai_api_key', 'value' => 'sk-test']);
        Http::fake(['api.openai.com/*' => Http::response([
            'choices' => [['message' => ['content' => '{}']]],
            'usage' => ['total_tokens' => 900],
        ])]);

        $user = $this->userWithAllowance(1000);
        $ai = app(OpenAiService::class)->forUser($user->id);

        $this->assertTrue($ai->chatCompletion([['role' => 'user', 'content' => 'hi']])['success']);
        $this->assertTrue($ai->chatCompletion([['role' => 'user', 'content' => 'hi']])['success']); // 100 left -> allowed
        $blocked = $ai->chatCompletion([['role' => 'user', 'content' => 'hi']]);

        $this->assertFalse($blocked['success']);
        $this->assertSame('ai_token_limit_reached', $blocked['error']);
        Http::assertSentCount(2);
    }

    public function test_unmetered_service_never_touches_usage(): void
    {
        Setting::create(['key' => 'openai_api_key', 'value' => 'sk-test']);
        Http::fake(['api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => '{}']]], 'usage' => ['total_tokens' => 50]])]);

        app(OpenAiService::class)->chatCompletion([['role' => 'user', 'content' => 'hi']]);

        $this->assertDatabaseCount('ai_token_usages', 0);
    }

    public function test_seeder_retires_same_named_legacy_plans_and_is_idempotent(): void
    {
        $legacy = SubscriptionPlan::create([
            'name' => 'Business', 'monthly_price' => 249, 'yearly_price' => 0,
            'limits' => ['whatsapp_nos' => 5, 'reminders_per_month' => 0, 'features' => []],
        ]);

        $this->seed(SubscriptionPlanSeeder::class);
        $this->seed(SubscriptionPlanSeeder::class);

        $legacy->refresh();
        $this->assertSame('Business (Legacy)', $legacy->name);
        $this->assertFalse($legacy->is_active);
        $this->assertSame(249, (int) $legacy->monthly_price);

        $new = SubscriptionPlan::where('name', 'Business')->get();
        $this->assertCount(1, $new);
        $this->assertSame(149, (int) $new->first()->monthly_price);
        $this->assertSame(1_500_000, $new->first()->limits['ai_tokens_per_month']);
        $this->assertSame(['Starter', 'Growth', 'Business'], SubscriptionPlan::where('is_active', true)->where('is_lifetime', false)->orderBy('monthly_price')->pluck('name')->all());
    }
}
