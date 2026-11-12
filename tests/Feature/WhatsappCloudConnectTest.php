<?php

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use App\Models\WhatsappNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsappCloudConnectTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'EAAG-plain-test-token-0123456789';

    protected function userWithLimit(int $limit = 3): User
    {
        $plan = SubscriptionPlan::create([
            'name' => 'Test',
            'monthly_price' => 10, 'yearly_price' => 100,
            'limits' => ['whatsapp_nos' => $limit, 'reminders_per_month' => 0, 'features' => []],
        ]);
        $user = User::factory()->create();
        UserSubscription::create(['user_id' => $user->id, 'subscription_plan_id' => $plan->id, 'status' => 'active']);

        return $user;
    }

    protected function cloudNumber(User $user, array $attrs = []): WhatsappNumber
    {
        return WhatsappNumber::create(array_merge([
            'user_id' => $user->id,
            'status' => 'connected',
            'provider' => 'cloud',
            'phone_number_id' => '1055550001',
            'access_token' => self::TOKEN,
            'display_phone_number' => '+60 12-345 6789',
        ], $attrs));
    }

    protected function payload(array $over = []): array
    {
        return array_merge([
            'waba_id' => '9990001',
            'phone_number_id' => '1055550001',
            'access_token' => self::TOKEN,
        ], $over);
    }

    protected function fakeMeta(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'id' => '1055550001',
                'display_phone_number' => '+60 12-345 6789',
                'verified_name' => 'Acme',
                'quality_rating' => 'GREEN',
                'messaging_limit_tier' => 'TIER_1K',
            ]),
        ]);
    }

    public function test_connect_success_creates_encrypted_cloud_number(): void
    {
        $this->fakeMeta();
        $user = $this->userWithLimit();

        $response = $this->actingAs($user)->post(route('whatsapp.store-cloud'), $this->payload());

        $number = WhatsappNumber::first();
        $response->assertRedirect(route('whatsapp.show', $number->id));
        $this->assertSame($user->id, $number->user_id);
        $this->assertSame('cloud', $number->provider);
        $this->assertSame('connected', $number->status);
        $this->assertSame('60123456789', $number->phone_number);
        $this->assertSame('GREEN', $number->quality_rating);
        $this->assertSame('TIER_1K', $number->messaging_limit);
        $this->assertNull($number->qr_code);
        $this->assertSame(self::TOKEN, $number->access_token);

        $raw = DB::table('whatsapp_numbers')->where('id', $number->id)->value('access_token');
        $this->assertNotSame(self::TOKEN, $raw);
        $this->assertStringNotContainsString(self::TOKEN, $raw);

        Http::assertSent(fn ($r) => $r->hasHeader('Authorization', 'Bearer '.self::TOKEN)
            && str_contains($r->url(), '/1055550001'));
    }

    public function test_meta_rejection_creates_nothing(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'Invalid OAuth access token']], 401)]);
        $user = $this->userWithLimit();

        $this->actingAs($user)->post(route('whatsapp.store-cloud'), $this->payload())
            ->assertSessionHasErrors('access_token');

        $this->assertSame(0, WhatsappNumber::count());
    }

    public function test_device_limit_blocks_connect(): void
    {
        $this->fakeMeta();
        $user = $this->userWithLimit(1);
        $this->cloudNumber($user, ['phone_number_id' => '777']);

        $this->actingAs($user)->post(route('whatsapp.store-cloud'), $this->payload())
            ->assertSessionHas('error');

        $this->assertSame(1, WhatsappNumber::count());
        Http::assertNothingSent();
    }

    public function test_duplicate_phone_number_id_rejected(): void
    {
        $this->fakeMeta();
        $user = $this->userWithLimit();
        $this->cloudNumber(User::factory()->create());

        $this->actingAs($user)->post(route('whatsapp.store-cloud'), $this->payload())
            ->assertSessionHasErrors('phone_number_id');

        $this->assertSame(1, WhatsappNumber::count());
    }

    public function test_show_cloud_number_makes_no_node_calls_and_hides_token(): void
    {
        Http::fake();
        $user = $this->userWithLimit();
        $number = $this->cloudNumber($user, ['status' => 'disconnected']);

        $this->actingAs($user)->get(route('whatsapp.show', $number->id))
            ->assertOk()
            ->assertDontSee(self::TOKEN, false);

        Http::assertNothingSent();
        $this->assertSame('disconnected', $number->fresh()->status);
    }

    public function test_refresh_qr_on_cloud_number_makes_no_node_calls(): void
    {
        Http::fake();
        $user = $this->userWithLimit();
        $number = $this->cloudNumber($user);

        $this->actingAs($user)->getJson(route('whatsapp.refresh-qr', $number->id))
            ->assertOk()
            ->assertJsonPath('provider', 'cloud')
            ->assertJsonMissingPath('access_token');

        Http::assertNothingSent();
    }

    public function test_destroy_cloud_number_makes_no_node_calls(): void
    {
        Http::fake();
        $user = $this->userWithLimit();
        $number = $this->cloudNumber($user);

        $this->actingAs($user)->delete(route('whatsapp.destroy', $number->id))->assertRedirect();

        Http::assertNothingSent();
        $this->assertNull(WhatsappNumber::find($number->id));
    }

    public function test_cross_user_access_returns_404(): void
    {
        Http::fake();
        $owner = $this->userWithLimit();
        $number = $this->cloudNumber($owner);
        $other = $this->userWithLimit();

        $this->actingAs($other)->get(route('whatsapp.show', $number->id))->assertNotFound();
        $this->actingAs($other)->getJson(route('whatsapp.refresh-qr', $number->id))->assertNotFound();
        $this->actingAs($other)->delete(route('whatsapp.destroy', $number->id))->assertNotFound();

        $this->assertNotNull(WhatsappNumber::find($number->id));
    }

    public function test_node_callbacks_do_not_overwrite_cloud_numbers(): void
    {
        $user = User::factory()->create();
        $number = WhatsappNumber::create([
            'user_id' => $user->id,
            'provider' => 'cloud',
            'status' => 'connected',
            'phone_number' => '60123456789',
            'phone_number_id' => '999111',
        ]);

        $this->postJson('/api/whatsapp/status-update', [
            'user_id' => $user->id,
            'phone_number' => $number->id,
            'status' => 'disconnected',
        ])->assertOk();

        $this->postJson('/api/whatsapp/qr-update', [
            'user_id' => $user->id,
            'phone_number' => $number->id,
            'qr_code' => 'QR',
        ])->assertOk();

        $number->refresh();
        $this->assertSame('connected', $number->status);
        $this->assertSame('60123456789', $number->phone_number);
        $this->assertNull($number->qr_code);
    }
}
