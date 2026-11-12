<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\User;
use App\Models\WhatsappNumber;
use App\Services\WhatsappCloudService;
use App\Services\WhatsappService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetaCloudServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function number(array $attributes = []): WhatsappNumber
    {
        return WhatsappNumber::create(array_merge([
            'user_id' => User::factory()->create()->id,
            'phone_number' => '60123456789',
            'status' => 'connected',
            'provider' => 'cloud',
            'phone_number_id' => '1055550001',
            'access_token' => 'EAAG-secret-token',
        ], $attributes));
    }

    protected function conversation(WhatsappNumber $number, ?\DateTimeInterface $lastInbound): Conversation
    {
        return Conversation::create([
            'user_id' => $number->user_id,
            'whatsapp_number_id' => $number->id,
            'contact_phone' => '60111222333',
            'status' => 'active',
            'last_customer_message_at' => $lastInbound,
        ]);
    }

    public function test_access_token_is_encrypted_at_rest_and_hidden(): void
    {
        $number = $this->number();

        $this->assertNotSame('EAAG-secret-token', $number->getRawOriginal('access_token'));
        $this->assertSame('EAAG-secret-token', $number->fresh()->access_token);
        $this->assertArrayNotHasKey('access_token', $number->toArray());
    }

    public function test_send_text_builds_expected_request_and_parses_message_id(): void
    {
        config(['services.meta.graph_version' => 'v21.0']);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.OUT1']]])]);
        $number = $this->number();
        $this->conversation($number, now()->subHour());

        $result = app(WhatsappCloudService::class)->sendText($number, '+60 111-222 333', 'Hello');

        $this->assertTrue($result->successful());
        $this->assertSame('wamid.OUT1', $result->json('message_id'));

        Http::assertSent(fn ($request) => $request->url() === 'https://graph.facebook.com/v21.0/1055550001/messages'
            && $request->hasHeader('Authorization', 'Bearer EAAG-secret-token')
            && $request['messaging_product'] === 'whatsapp'
            && $request['to'] === '60111222333'
            && $request['type'] === 'text'
            && $request['text']['body'] === 'Hello');
    }

    public function test_leading_zero_uses_configured_country_code(): void
    {
        $service = app(WhatsappCloudService::class);

        $this->assertSame('60123456789', $service->normalizePhone('0123456789'));

        config(['services.meta.default_country_code' => '65']);
        $this->assertSame('6591234567', $service->normalizePhone('091234567'));
        $this->assertSame('60111222333', $service->normalizePhone('60111222333@s.whatsapp.net'));
    }

    public function test_outside_24h_window_fails_without_calling_meta(): void
    {
        Http::fake();
        $number = $this->number();
        $this->conversation($number, now()->subHours(25));

        $result = app(WhatsappCloudService::class)->sendText($number, '60111222333', 'Hello');

        $this->assertFalse($result->successful());
        $this->assertSame('outside_24h_window', $result->error());
        Http::assertNothingSent();
    }

    public function test_conversation_without_any_inbound_fails_without_calling_meta(): void
    {
        Http::fake();
        $number = $this->number();
        $this->conversation($number, null);

        $result = app(WhatsappCloudService::class)->sendMedia($number, '60111222333', 'image', 'https://example.com/a.png');

        $this->assertSame('outside_24h_window', $result->error());
        Http::assertNothingSent();
    }

    public function test_send_template_bypasses_window_and_builds_payload(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.T1']]])]);
        $number = $this->number();
        $this->conversation($number, now()->subDays(3));
        $components = [['type' => 'body', 'parameters' => [['type' => 'text', 'text' => 'Aisyah']]]];

        $result = app(WhatsappCloudService::class)->sendTemplate($number, '60111222333', 'appointment_reminder', 'en_US', $components);

        $this->assertTrue($result->successful());
        Http::assertSent(fn ($request) => $request['type'] === 'template'
            && $request['to'] === '60111222333'
            && $request['template']['name'] === 'appointment_reminder'
            && $request['template']['language']['code'] === 'en_US'
            && $request['template']['components'] === $components);
    }

    public function test_send_media_by_link(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.M1']]])]);

        $result = app(WhatsappCloudService::class)->sendMedia($this->number(), '60111222333', 'image/png', 'https://example.com/a.png', null, null, 'Nice');

        $this->assertTrue($result->successful());
        Http::assertSent(fn ($request) => $request['type'] === 'image'
            && $request['image'] === ['link' => 'https://example.com/a.png', 'caption' => 'Nice']);
    }

    public function test_send_media_base64_uploads_first_then_sends_media_id(): void
    {
        Http::fake([
            'graph.facebook.com/*/1055550001/media' => Http::response(['id' => 'MEDIA9']),
            'graph.facebook.com/*/1055550001/messages' => Http::response(['messages' => [['id' => 'wamid.D1']]]),
        ]);

        $result = app(WhatsappCloudService::class)->sendMedia(
            $this->number(), '60111222333', 'application/pdf', null, base64_encode('%PDF-1.4'), 'invoice.pdf', 'Your invoice'
        );

        $this->assertSame('wamid.D1', $result->json('message_id'));
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/1055550001/media')
            && $request->isMultipart());
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/1055550001/messages')
            && $request['type'] === 'document'
            && $request['document'] === ['id' => 'MEDIA9', 'caption' => 'Your invoice', 'filename' => 'invoice.pdf']);
    }

    public function test_api_error_is_surfaced(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['code' => 190, 'message' => 'Invalid OAuth access token']], 401)]);

        $result = app(WhatsappCloudService::class)->sendTemplate($this->number(), '60111222333', 'hello', 'en');

        $this->assertFalse($result->successful());
        $this->assertSame(401, $result->status());
        $this->assertSame('(190) Invalid OAuth access token', $result->error());
    }

    public function test_server_errors_are_retried(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::sequence()
            ->push(['error' => ['message' => 'oops']], 500)
            ->push(['messages' => [['id' => 'wamid.R1']]])]);

        $result = app(WhatsappCloudService::class)->sendTemplate($this->number(), '60111222333', 'hello', 'en');

        $this->assertSame('wamid.R1', $result->json('message_id'));
        Http::assertSentCount(2);
    }

    public function test_client_errors_are_not_retried(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'bad']], 400)]);

        app(WhatsappCloudService::class)->sendTemplate($this->number(), '60111222333', 'hello', 'en');

        Http::assertSentCount(1);
    }

    public function test_download_media_returns_base64_mime_and_filename(): void
    {
        Http::fake([
            'graph.facebook.com/*/MEDIA1' => Http::response(['url' => 'https://lookaside.fbsbx.com/file', 'mime_type' => 'image/png']),
            'lookaside.fbsbx.com/*' => Http::response('png-bytes'),
        ]);

        $media = app(WhatsappCloudService::class)->downloadMedia($this->number(), 'MEDIA1');

        $this->assertSame(base64_encode('png-bytes'), $media['base64']);
        $this->assertSame('image/png', $media['mime']);
        $this->assertSame('MEDIA1.png', $media['filename']);
    }

    public function test_send_message_on_cloud_number_delegates_and_is_caller_compatible(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.C1']]])]);

        $response = (new WhatsappService)->sendMessage($this->number(), '60111222333', 'Hi');

        $this->assertTrue($response && $response->successful());
        $this->assertSame('wamid.C1', $response->json('message_id'));
    }

    public function test_send_message_on_baileys_number_still_hits_node_server(): void
    {
        config(['app.url' => 'http://localhost']);
        Http::fake(['127.0.0.1:3005/*' => Http::response(['message_id' => 'BAILEYS1'])]);
        $number = $this->number(['provider' => 'baileys', 'phone_number_id' => null, 'access_token' => null]);

        $response = (new WhatsappService)->sendMessage($number, '0111222333', 'Hi');

        $this->assertTrue($response->successful());
        $this->assertSame('BAILEYS1', $response->json('message_id'));
        Http::assertSent(fn ($request) => $request->url() === 'http://127.0.0.1:3005/send-message'
            && $request['to'] === '60111222333'
            && $request['phone_number'] === $number->id
            && $request['message'] === 'Hi');
    }
}
