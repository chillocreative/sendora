<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use App\Models\WhatsappNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;
    protected $businessPlan;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Business plan with API access
        $this->businessPlan = SubscriptionPlan::create([
            'name' => 'Business',
            'price' => 199,
            'limits' => [
                'whatsapp_nos' => 5,
                'reminders_per_month' => 0,
                'features' => [
                    'api_access' => true,
                    'auto_reply' => true,
                    'google_calendar' => true,
                    'ai_command_parsing' => true,
                ],
            ],
        ]);

        // Create user with Business subscription
        $this->user = User::factory()->create([
            'name' => 'API Test User',
            'email' => 'api@test.com',
        ]);

        UserSubscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->businessPlan->id,
            'status' => 'active',
        ]);

        // Create API token
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    /** @test */
    public function test_api_requires_authentication()
    {
        $response = $this->getJson('/api/v1/profile');

        $response->assertStatus(401);
    }

    /** @test */
    public function test_get_user_profile()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/profile');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'API Test User',
                    'email' => 'api@test.com',
                ],
            ]);
    }

    /** @test */
    public function test_get_usage_stats()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/usage');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'devices' => ['used', 'limit'],
                    'reminders' => ['used', 'limit'],
                    'features',
                ],
            ]);
    }

    /** @test */
    public function test_get_devices_list()
    {
        WhatsappNumber::create([
            'user_id' => $this->user->id,
            'status' => 'connected',
            'phone_number' => '60123456789',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/devices');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'status', 'phone_number'],
                ],
            ]);
    }

    /** @test */
    public function test_api_access_denied_for_non_business_plans()
    {
        $freePlan = SubscriptionPlan::create([
            'name' => 'Basic',
            'price' => 0,
            'limits' => [
                'whatsapp_nos' => 1,
                'reminders_per_month' => 50,
                'features' => [
                    'api_access' => false,
                    'google_calendar' => true,
                ],
            ],
        ]);

        $freeUser = User::factory()->create();
        UserSubscription::create([
            'user_id' => $freeUser->id,
            'plan_id' => $freePlan->id,
            'status' => 'active',
        ]);

        $freeToken = $freeUser->createToken('free-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $freeToken)
            ->getJson('/api/v1/profile');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'API access is only available on the Business plan. Please upgrade your subscription.',
            ]);
    }
}
