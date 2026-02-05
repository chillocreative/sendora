<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Contact;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use App\Models\WhatsappNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

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
                'whatsapp_nos' => 10,
                'contacts' => 10000,
                'messages' => 50000,
                'features' => [
                    'api_access' => true,
                    'auto_reply' => true,
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
            'messages_used_this_month' => 0,
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
                    'contacts' => ['used', 'limit'],
                    'messages' => ['used', 'limit'],
                    'features',
                ],
            ]);
    }

    /** @test */
    public function test_create_contact_via_api()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/contacts', [
                'name' => 'John Doe',
                'phone' => '60123456789',
                'email' => 'john@example.com',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Contact created successfully.',
            ]);

        $this->assertDatabaseHas('contacts', [
            'user_id' => $this->user->id,
            'name' => 'John Doe',
            'phone' => '60123456789',
        ]);
    }

    /** @test */
    public function test_get_contacts_list()
    {
        // Create test contacts
        Contact::factory()->count(5)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/contacts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data', // pagination data
                ],
            ]);
    }

    /** @test */
    public function test_get_devices_list()
    {
        // Create a connected device
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
    public function test_send_message_requires_connected_device()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/messages/send', [
                'phone' => '60123456789',
                'message' => 'Test message',
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'No connected WhatsApp device found.',
            ]);
    }

    /** @test */
    public function test_contact_limit_enforcement()
    {
        // Create contacts up to limit
        $limit = $this->businessPlan->limits['contacts'];
        Contact::factory()->count($limit)->create([
            'user_id' => $this->user->id,
        ]);

        // Try to create one more
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/contacts', [
                'name' => 'Over Limit',
                'phone' => '60123456789',
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Contact limit reached for your subscription plan.',
            ]);
    }

    /** @test */
    public function test_api_access_denied_for_non_business_plans()
    {
        // Create Free plan without API access
        $freePlan = SubscriptionPlan::create([
            'name' => 'Free',
            'price' => 0,
            'limits' => [
                'whatsapp_nos' => 1,
                'contacts' => 50,
                'messages' => 100,
                'features' => [
                    'api_access' => false,
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

    /** @test */
    public function test_search_contacts()
    {
        Contact::create([
            'user_id' => $this->user->id,
            'name' => 'Alice Smith',
            'phone' => '60111111111',
        ]);

        Contact::create([
            'user_id' => $this->user->id,
            'name' => 'Bob Jones',
            'phone' => '60222222222',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/contacts?search=Alice');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Alice Smith']);
    }
}
