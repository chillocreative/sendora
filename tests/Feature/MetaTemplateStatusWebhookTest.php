<?php

namespace Tests\Feature;

use App\Models\WhatsappTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetaTemplateStatusWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.meta.app_secret' => 'test-secret']);
    }

    protected function template(): WhatsappTemplate
    {
        return WhatsappTemplate::create([
            'waba_id' => 'WABA1',
            'purpose' => 'reminder',
            'name' => 'sendora_reminder',
            'language' => 'en',
            'variables' => ['title'],
            'status' => 'pending',
        ]);
    }

    protected function postWebhook(array $value, ?string $signature = null, string $wabaId = 'WABA1')
    {
        $body = json_encode([
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'id' => $wabaId,
                'changes' => [['field' => 'message_template_status_update', 'value' => $value]],
            ]],
        ]);
        $signature ??= 'sha256='.hash_hmac('sha256', $body, 'test-secret');

        return $this->call('POST', '/api/meta/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => $signature,
        ], $body);
    }

    protected function value(string $event, array $extra = []): array
    {
        return array_merge([
            'event' => $event,
            'message_template_id' => 998877,
            'message_template_name' => 'sendora_reminder',
            'message_template_language' => 'en',
            'reason' => 'NONE',
        ], $extra);
    }

    public function test_approved_updates_status_and_meta_id(): void
    {
        $template = $this->template();

        $this->postWebhook($this->value('APPROVED'))->assertOk();

        $template->refresh();
        $this->assertSame('approved', $template->status);
        $this->assertSame('998877', $template->meta_template_id);
        $this->assertNull($template->rejection_reason);
    }

    public function test_rejected_stores_reason(): void
    {
        $template = $this->template();

        $this->postWebhook($this->value('REJECTED', ['reason' => 'INVALID_FORMAT']))->assertOk();

        $template->refresh();
        $this->assertSame('rejected', $template->status);
        $this->assertSame('INVALID_FORMAT', $template->rejection_reason);
    }

    public function test_flagged_and_paused_map_to_paused(): void
    {
        $template = $this->template();

        $this->postWebhook($this->value('FLAGGED'))->assertOk();
        $this->assertSame('paused', $template->refresh()->status);

        $this->postWebhook($this->value('APPROVED'))->assertOk();
        $this->postWebhook($this->value('PAUSED'))->assertOk();
        $this->assertSame('paused', $template->refresh()->status);
    }

    public function test_falls_back_to_meta_template_id(): void
    {
        $template = $this->template();
        $template->update(['meta_template_id' => '998877']);

        $this->postWebhook($this->value('APPROVED', ['message_template_name' => 'renamed']))->assertOk();

        $this->assertSame('approved', $template->refresh()->status);
    }

    public function test_unknown_template_is_ignored_with_200(): void
    {
        $template = $this->template();

        $this->postWebhook($this->value('APPROVED', ['message_template_name' => 'other', 'message_template_id' => 1]))
            ->assertOk();

        $this->assertSame('pending', $template->refresh()->status);
    }

    public function test_bad_signature_is_rejected(): void
    {
        $template = $this->template();

        $this->postWebhook($this->value('APPROVED'), 'sha256=bad')->assertForbidden();

        $this->assertSame('pending', $template->refresh()->status);
    }
}
