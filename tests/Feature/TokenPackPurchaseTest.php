<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\SubscriptionPlan;
use App\Models\TokenPack;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\AiUsageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TokenPackPurchaseTest extends TestCase
{
    use RefreshDatabase;

    private function user(int $allowance = 1000): User
    {
        $plan = SubscriptionPlan::create([
            'name' => 'P'.uniqid(), 'monthly_price' => 49, 'yearly_price' => 490,
            'limits' => ['whatsapp_nos' => 1, 'reminders_per_month' => 0, 'ai_tokens_per_month' => $allowance, 'features' => []],
        ]);
        $user = User::factory()->create();
        UserSubscription::create(['user_id' => $user->id, 'subscription_plan_id' => $plan->id, 'status' => 'active', 'ends_at' => now()->addMonth()]);

        return $user;
    }

    private function gateway(): void
    {
        Setting::create(['key' => 'chip_in_brand_id', 'value' => 'brand']);
        Setting::create(['key' => 'chip_in_private_key', 'value' => 'secret']);
    }

    private function pendingPackTransaction(User $user, string $gatewayId = 'pur_1'): Transaction
    {
        $pack = TokenPack::first();

        return Transaction::create([
            'user_id' => $user->id, 'amount' => $pack->price, 'currency' => 'MYR', 'status' => 'pending',
            'payment_method' => 'chip-in', 'reference_id' => 'txn_'.uniqid(), 'gateway_id' => $gatewayId,
            'token_pack_id' => $pack->id, 'token_amount' => $pack->tokens,
        ]);
    }

    private function fakeStatus(Transaction $t, string $status, ?int $total = null, bool $isTest = true): void
    {
        Http::fake(['gate.chip-in.asia/api/v1/purchases/'.$t->gateway_id.'/' => Http::response([
            'id' => $t->gateway_id, 'status' => $status, 'reference' => $t->reference_id, 'is_test' => $isTest,
            'purchase' => ['total' => $total ?? (int) round($t->amount * 100)],
        ])]);
    }

    public function test_initiate_creates_pending_transaction_and_credits_nothing(): void
    {
        $this->gateway();
        Http::fake(['gate.chip-in.asia/api/v1/purchases/' => Http::response(['id' => 'pur_9', 'checkout_url' => 'https://pay.example/checkout'])]);
        $user = $this->user();

        $this->actingAs($user)->post(route('payments.token-pack.initiate'), ['pack_id' => TokenPack::first()->id])
            ->assertRedirect('https://pay.example/checkout');

        $t = Transaction::where('user_id', $user->id)->first();
        $this->assertSame('pending', $t->status);
        $this->assertSame('pur_9', $t->gateway_id);
        $this->assertSame(1_000_000, (int) $t->token_amount);
        $this->assertSame(0, (int) $user->fresh()->ai_bonus_tokens);
    }

    public function test_unlimited_plans_cannot_buy_packs(): void
    {
        $this->gateway();
        Http::fake();
        $user = $this->user(0);

        $this->actingAs($user)->post(route('payments.token-pack.initiate'), ['pack_id' => TokenPack::first()->id])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('transactions', 0);
        Http::assertNothingSent();
    }

    public function test_verified_payment_credits_tokens_exactly_once(): void
    {
        $this->gateway();
        $user = $this->user();
        $t = $this->pendingPackTransaction($user);
        $this->fakeStatus($t, 'paid');

        $this->actingAs($user)->get(route('payments.success', ['ref' => $t->reference_id]))->assertRedirect();
        $this->actingAs($user)->get(route('payments.success', ['ref' => $t->reference_id]))->assertRedirect();
        $this->post('/payments/webhook', ['reference' => $t->reference_id])->assertOk();

        $this->assertSame(1_000_000, (int) $user->fresh()->ai_bonus_tokens);
        $this->assertSame(1_000_000 + 1000, app(AiUsageService::class)->remaining($user));
        $this->assertNotNull($t->fresh()->fulfilled_at);
    }

    public function test_redirect_without_real_payment_credits_nothing(): void
    {
        $this->gateway();
        $user = $this->user();
        $t = $this->pendingPackTransaction($user);
        $this->fakeStatus($t, 'created');

        $this->actingAs($user)->get(route('payments.success', ['ref' => $t->reference_id]));

        $this->assertSame(0, (int) $user->fresh()->ai_bonus_tokens);
        $this->assertSame('pending', $t->fresh()->status);
    }

    public function test_amount_mismatch_is_not_accepted(): void
    {
        $this->gateway();
        $user = $this->user();
        $t = $this->pendingPackTransaction($user);
        $this->fakeStatus($t, 'paid', 100); // paid RM1 instead of RM10

        $this->actingAs($user)->get(route('payments.success', ['ref' => $t->reference_id]));

        $this->assertSame(0, (int) $user->fresh()->ai_bonus_tokens);
    }

    public function test_another_users_reference_cannot_be_completed(): void
    {
        $this->gateway();
        $buyer = $this->user();
        $other = $this->user();
        $t = $this->pendingPackTransaction($buyer);
        $this->fakeStatus($t, 'paid');

        $this->actingAs($other)->get(route('payments.success', ['ref' => $t->reference_id]));

        $this->assertSame(0, (int) $buyer->fresh()->ai_bonus_tokens);
        $this->assertSame(0, (int) $other->fresh()->ai_bonus_tokens);
    }

    public function test_webhook_does_not_trust_its_payload(): void
    {
        $this->gateway();
        $user = $this->user();
        $t = $this->pendingPackTransaction($user);
        $this->fakeStatus($t, 'created');

        $this->post('/payments/webhook', ['reference' => $t->reference_id, 'status' => 'paid'])->assertOk();

        $this->assertSame(0, (int) $user->fresh()->ai_bonus_tokens);
    }

    public function test_subscription_activation_also_requires_verified_payment(): void
    {
        $this->gateway();
        $user = User::factory()->create();
        $plan = SubscriptionPlan::create([
            'name' => 'Paid', 'monthly_price' => 49, 'yearly_price' => 490,
            'limits' => ['whatsapp_nos' => 1, 'reminders_per_month' => 10, 'features' => []],
        ]);
        UserSubscription::create(['user_id' => $user->id, 'subscription_plan_id' => $plan->id, 'status' => 'waiting_for_payment']);
        $t = Transaction::create([
            'user_id' => $user->id, 'subscription_plan_id' => $plan->id, 'amount' => 49, 'currency' => 'MYR',
            'status' => 'pending', 'payment_method' => 'chip-in', 'reference_id' => 'txn_sub', 'gateway_id' => 'pur_s',
        ]);
        $this->fakeStatus($t, 'created');

        $this->actingAs($user)->get(route('payments.success', ['ref' => 'txn_sub']));

        $this->assertSame('waiting_for_payment', $user->subscriptions()->latest('id')->first()->status);
        $this->assertSame('pending', $t->fresh()->status);

        Http::swap(new \Illuminate\Http\Client\Factory); // drop the earlier fake; fakes are first-match
        $this->fakeStatus($t, 'paid');
        $this->actingAs($user)->get(route('payments.success', ['ref' => 'txn_sub']));

        $this->assertSame('active', $user->subscriptions()->latest('id')->first()->status);
    }

    public function test_sandbox_purchase_is_rejected_in_live_mode(): void
    {
        $this->gateway();
        Setting::create(['key' => 'chip_in_test_mode', 'value' => '0']);
        $user = $this->user();
        $t = $this->pendingPackTransaction($user);
        $this->fakeStatus($t, 'paid', null, true); // paid, but only in the sandbox

        $this->actingAs($user)->get(route('payments.success', ['ref' => $t->reference_id]));

        $this->assertSame(0, (int) $user->fresh()->ai_bonus_tokens);
    }

    public function test_live_purchase_is_accepted_in_live_mode(): void
    {
        $this->gateway();
        Setting::create(['key' => 'chip_in_test_mode', 'value' => '0']);
        $user = $this->user();
        $t = $this->pendingPackTransaction($user);
        $this->fakeStatus($t, 'paid', null, false);

        $this->actingAs($user)->get(route('payments.success', ['ref' => $t->reference_id]));

        $this->assertSame(1_000_000, (int) $user->fresh()->ai_bonus_tokens);
    }

    public function test_test_mode_defaults_to_live_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->assertFalse(app(\App\Services\ChipInService::class)->testMode());
    }

    private function pendingSubscription(): array
    {
        $this->gateway();
        $user = User::factory()->create();
        $plan = SubscriptionPlan::create([
            'name' => 'Paid', 'monthly_price' => 49, 'yearly_price' => 490,
            'limits' => ['whatsapp_nos' => 1, 'reminders_per_month' => 10, 'features' => []],
        ]);
        UserSubscription::create(['user_id' => $user->id, 'subscription_plan_id' => $plan->id, 'status' => 'waiting_for_payment']);
        $t = Transaction::create([
            'user_id' => $user->id, 'subscription_plan_id' => $plan->id, 'amount' => 49, 'currency' => 'MYR',
            'status' => 'pending', 'payment_method' => 'chip-in', 'reference_id' => 'txn_w', 'gateway_id' => 'pur_w',
        ]);

        return [$user, $t];
    }

    public function test_webhook_activates_subscription_once_without_the_redirect(): void
    {
        [$user, $t] = $this->pendingSubscription();
        $this->fakeStatus($t, 'paid');

        $this->post('/payments/webhook', ['reference' => 'txn_w'])->assertOk();
        $sub = $user->subscriptions()->latest('id')->first();
        $this->assertSame('active', $sub->status);
        $endsAt = $sub->ends_at;

        // Webhook retry and the buyer's redirect afterwards must not extend it again.
        $this->post('/payments/webhook', ['reference' => 'txn_w'])->assertOk();
        $this->actingAs($user)->get(route('payments.success', ['ref' => 'txn_w']));

        $this->assertSame(1, $user->subscriptions()->count());
        $this->assertEquals($endsAt, $user->subscriptions()->first()->ends_at);
    }

    public function test_webhook_does_not_activate_unpaid_subscription(): void
    {
        [$user, $t] = $this->pendingSubscription();
        $this->fakeStatus($t, 'created');

        $this->post('/payments/webhook', ['reference' => 'txn_w'])->assertOk();

        $this->assertSame('waiting_for_payment', $user->subscriptions()->first()->status);
        $this->assertSame('pending', $t->fresh()->status);
    }

    public function test_redirect_then_webhook_activates_only_once(): void
    {
        [$user, $t] = $this->pendingSubscription();
        $this->fakeStatus($t, 'paid');

        $this->actingAs($user)->get(route('payments.success', ['ref' => 'txn_w']));
        $endsAt = $user->subscriptions()->first()->ends_at;
        $this->post('/payments/webhook', ['reference' => 'txn_w'])->assertOk();

        $this->assertEquals($endsAt, $user->subscriptions()->first()->ends_at);
    }
}
