<?php

namespace Tests\Feature;

use App\Jobs\ProcessAiReplyJob;
use App\Jobs\ProcessSendoraCommandJob;
use App\Models\User;
use App\Models\WhatsappNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MetaWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.meta.app_secret' => 'test-secret',
            'services.meta.webhook_verify_token' => 'verify-me',
        ]);
        Cache::flush();
    }

    protected function cloudNumber(array $attributes = []): WhatsappNumber
    {
        return WhatsappNumber::create(array_merge([
            'user_id' => User::factory()->create()->id,
            'phone_number' => '60123456789',
            'status' => 'connected',
            'provider' => 'cloud',
            'phone_number_id' => '1055550001',
            'access_token' => 'EAAG-secret-token',
            'ai_reply_enabled' => true,
        ], $attributes));
    }

    protected function payload(array $message, string $phoneNumberId = '1055550001'): array
    {
        return [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'id' => 'WABA1',
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'metadata' => ['display_phone_number' => '60123456789', 'phone_number_id' => $phoneNumberId],
                        'contacts' => [['profile' => ['name' => 'Aisyah'], 'wa_id' => '60111222333']],
                        'messages' => [$message],
                    ],
                ]],
            ]],
        ];
    }

    protected function signedPost(array $payload, ?string $signature = null)
    {
        $body = json_encode($payload);
        $signature ??= 'sha256='.hash_hmac('sha256', $body, 'test-secret');

        return $this->call('POST', '/api/meta/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => $signature,
        ], $body);
    }

    public function test_verification_echoes_challenge_with_valid_token(): void
    {
        $this->get('/api/meta/webhook?hub.mode=subscribe&hub.verify_token=verify-me&hub.challenge=12345')
            ->assertOk()
            ->assertSee('12345', false)
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function test_verification_rejects_wrong_token_or_mode(): void
    {
        $this->get('/api/meta/webhook?hub.mode=subscribe&hub.verify_token=nope&hub.challenge=12345')->assertForbidden();
        $this->get('/api/meta/webhook?hub.mode=unsubscribe&hub.verify_token=verify-me&hub.challenge=12345')->assertForbidden();
        $this->get('/api/meta/webhook')->assertForbidden();
    }

    public function test_post_rejects_missing_or_bad_signature(): void
    {
        Queue::fake();
        $this->cloudNumber();
        $payload = $this->payload(['from' => '60111222333', 'id' => 'wamid.1', 'type' => 'text', 'text' => ['body' => 'Hi']]);

        $this->postJson('/api/meta/webhook', $payload)->assertForbidden();
        $this->signedPost($payload, 'sha256='.str_repeat('0', 64))->assertForbidden();

        Queue::assertNothingPushed();
    }

    public function test_post_rejects_when_app_secret_not_configured(): void
    {
        config(['services.meta.app_secret' => null]);
        $payload = $this->payload(['from' => '60111222333', 'id' => 'wamid.1', 'type' => 'text', 'text' => ['body' => 'Hi']]);

        $this->signedPost($payload, 'sha256='.hash_hmac('sha256', json_encode($payload), ''))->assertForbidden();
    }

    public function test_text_message_dispatches_ai_reply_job_once_for_redelivery(): void
    {
        Queue::fake();
        Http::fake();
        $number = $this->cloudNumber();
        $payload = $this->payload(['from' => '60111222333', 'id' => 'wamid.ABC', 'type' => 'text', 'text' => ['body' => 'Hello there']]);

        $this->signedPost($payload)->assertOk();
        $this->signedPost($payload)->assertOk();

        Queue::assertPushed(ProcessAiReplyJob::class, 1);
        Queue::assertPushed(ProcessAiReplyJob::class, function (ProcessAiReplyJob $job) use ($number) {
            return $this->jobProperty($job, 'whatsappNumberId') === $number->id
                && $this->jobProperty($job, 'contactPhone') === '60111222333'
                && $this->jobProperty($job, 'messageText') === 'Hello there'
                && $this->jobProperty($job, 'waMessageId') === 'wamid.ABC';
        });

        // Read receipt is sent with the decrypted bearer token
        Http::assertSent(fn ($request) => $request['status'] === 'read'
            && $request['message_id'] === 'wamid.ABC'
            && $request->hasHeader('Authorization', 'Bearer EAAG-secret-token')
            && str_ends_with($request->url(), '/1055550001/messages'));
    }

    public function test_unknown_phone_number_id_returns_ok_without_dispatch(): void
    {
        Queue::fake();
        Http::fake();
        $this->cloudNumber();
        $payload = $this->payload(['from' => '60111222333', 'id' => 'wamid.X', 'type' => 'text', 'text' => ['body' => 'Hi']], 'unknown-id');

        $this->signedPost($payload)->assertOk();

        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_baileys_number_with_same_phone_number_id_is_ignored(): void
    {
        Queue::fake();
        $this->cloudNumber(['provider' => 'baileys']);
        $payload = $this->payload(['from' => '60111222333', 'id' => 'wamid.Y', 'type' => 'text', 'text' => ['body' => 'Hi']]);

        $this->signedPost($payload)->assertOk();

        Queue::assertNothingPushed();
    }

    public function test_image_message_is_downloaded_and_forwarded_with_caption(): void
    {
        Queue::fake();
        Http::fake([
            'graph.facebook.com/*/MEDIA1' => Http::response(['url' => 'https://lookaside.fbsbx.com/file', 'mime_type' => 'image/jpeg']),
            'lookaside.fbsbx.com/*' => Http::response('binary-bytes'),
            'graph.facebook.com/*' => Http::response(['success' => true]),
        ]);
        $this->cloudNumber();
        $payload = $this->payload([
            'from' => '60111222333', 'id' => 'wamid.IMG', 'type' => 'image',
            'image' => ['id' => 'MEDIA1', 'mime_type' => 'image/jpeg', 'caption' => 'Look at this'],
        ]);

        $this->signedPost($payload)->assertOk();

        Queue::assertPushed(ProcessAiReplyJob::class, fn (ProcessAiReplyJob $job) => $this->jobProperty($job, 'messageText') === 'Look at this');
        Http::assertSent(fn ($request) => $request->url() === 'https://lookaside.fbsbx.com/file'
            && $request->hasHeader('Authorization', 'Bearer EAAG-secret-token'));
    }

    public function test_sendora_command_from_customer_on_cloud_number_is_not_executed(): void
    {
        Queue::fake();
        Http::fake();
        $this->cloudNumber();
        $payload = $this->payload(['from' => '60111222333', 'id' => 'wamid.CMD', 'type' => 'text', 'text' => ['body' => '/sendora meeting tomorrow 3pm']]);

        $this->signedPost($payload)->assertOk();

        Queue::assertNotPushed(ProcessSendoraCommandJob::class);
    }

    public function test_baileys_incoming_endpoint_rejects_cloud_numbers(): void
    {
        Queue::fake();
        $number = $this->cloudNumber();
        $number->update(['status' => 'connected']);

        $this->postJson('/api/whatsapp/incoming-message', [
            'user_id' => $number->user_id,
            'phone_number' => $number->id,
            'from' => '60111222333@s.whatsapp.net',
            'message' => '/sendora x',
            'message_id' => 'm1',
        ])->assertNotFound();

        Queue::assertNothingPushed();
    }

    public function test_unsupported_message_type_is_ignored(): void
    {
        Queue::fake();
        $this->cloudNumber();
        $payload = $this->payload(['from' => '60111222333', 'id' => 'wamid.S', 'type' => 'sticker', 'sticker' => ['id' => 'S1']]);

        $this->signedPost($payload)->assertOk();

        Queue::assertNothingPushed();
    }

    public function test_quality_update_updates_number(): void
    {
        $number = $this->cloudNumber(['waba_id' => 'WABA1', 'display_phone_number' => '+60 12-345 6789']);
        $payload = ['entry' => [[
            'id' => 'WABA1',
            'changes' => [[
                'field' => 'phone_number_quality_update',
                'value' => ['display_phone_number' => '60123456789', 'event' => 'UPGRADE', 'current_limit' => 'TIER_10K', 'quality_rating' => 'GREEN'],
            ]],
        ]]];

        $this->signedPost($payload)->assertOk();

        $this->assertSame('TIER_10K', $number->fresh()->messaging_limit);
        $this->assertSame('GREEN', $number->fresh()->quality_rating);
    }

    public function test_failed_status_is_acknowledged(): void
    {
        $this->cloudNumber();
        $payload = $this->payload([]);
        $payload['entry'][0]['changes'][0]['value'] = [
            'metadata' => ['phone_number_id' => '1055550001'],
            'statuses' => [['id' => 'wamid.F', 'status' => 'failed', 'errors' => [['code' => 131047, 'title' => 'Re-engagement message']]]],
        ];

        $this->signedPost($payload)->assertOk();
    }

    private function jobProperty(object $job, string $property): mixed
    {
        return (fn () => $this->{$property})->call($job);
    }
}
