<?php

namespace Tests\Feature;

use App\Models\Reminder;
use App\Models\User;
use App\Models\WhatsappNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetaReminderDeliveryStatusWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.meta.app_secret' => 'test-secret']);
    }

    protected function number(string $phoneNumberId = 'PNID_A'): WhatsappNumber
    {
        return WhatsappNumber::create([
            'user_id' => User::factory()->create()->id,
            'phone_number' => '6012'.random_int(1000000, 9999999),
            'status' => 'connected',
            'provider' => 'cloud',
            'phone_number_id' => $phoneNumberId,
            'access_token' => 'EAAG-token',
        ]);
    }

    protected function reminder(WhatsappNumber $number, string $waId = 'wamid.R1'): Reminder
    {
        return Reminder::create([
            'user_id' => $number->user_id,
            'whatsapp_number_id' => $number->id,
            'title' => 'Meeting',
            'reminder_at' => now()->subHour(),
            'status' => 'sent',
            'sent_at' => now()->subHour(),
            'wa_message_id' => $waId,
        ]);
    }

    protected function postStatus(string $phoneNumberId, array $status)
    {
        $body = json_encode([
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'id' => 'WABA1',
                'changes' => [['field' => 'messages', 'value' => [
                    'messaging_product' => 'whatsapp',
                    'metadata' => ['phone_number_id' => $phoneNumberId],
                    'statuses' => [$status],
                ]]],
            ]],
        ]);

        return $this->call('POST', '/api/meta/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $body, 'test-secret'),
        ], $body);
    }

    public function test_delivered_sets_delivered_at(): void
    {
        $r = $this->reminder($this->number());
        $ts = now()->subMinutes(5)->timestamp;

        $this->postStatus('PNID_A', ['id' => 'wamid.R1', 'status' => 'delivered', 'timestamp' => (string) $ts])->assertOk();

        $r->refresh();
        $this->assertSame($ts, $r->delivered_at->timestamp);
        $this->assertNull($r->read_at);
        $this->assertSame('sent', $r->status);
    }

    public function test_read_before_delivered_sets_both(): void
    {
        $r = $this->reminder($this->number());

        $this->postStatus('PNID_A', ['id' => 'wamid.R1', 'status' => 'read', 'timestamp' => (string) now()->timestamp])->assertOk();
        $r->refresh();
        $this->assertNotNull($r->read_at);
        $this->assertNotNull($r->delivered_at);

        $readAt = $r->read_at->timestamp;
        $deliveredAt = $r->delivered_at->timestamp;

        // A late "delivered" must not move timestamps.
        $this->postStatus('PNID_A', ['id' => 'wamid.R1', 'status' => 'delivered', 'timestamp' => (string) now()->addHour()->timestamp])->assertOk();
        $r->refresh();
        $this->assertSame($readAt, $r->read_at->timestamp);
        $this->assertSame($deliveredAt, $r->delivered_at->timestamp);
    }

    public function test_failed_stores_truncated_error(): void
    {
        $r = $this->reminder($this->number());

        $this->postStatus('PNID_A', ['id' => 'wamid.R1', 'status' => 'failed', 'errors' => [['code' => 131026, 'title' => 'Message undeliverable']]])->assertOk();
        $this->assertSame('(131026) Message undeliverable', $r->refresh()->delivery_error);

        $this->postStatus('PNID_A', ['id' => 'wamid.R1', 'status' => 'failed', 'errors' => [['code' => 1, 'title' => str_repeat('x', 400)]]])->assertOk();
        $this->assertSame(255, strlen($r->refresh()->delivery_error));
    }

    public function test_redelivered_status_is_a_noop(): void
    {
        $r = $this->reminder($this->number());

        $this->postStatus('PNID_A', ['id' => 'wamid.R1', 'status' => 'delivered', 'timestamp' => '1700000000'])->assertOk();
        $first = $r->refresh()->delivered_at->timestamp;

        $this->postStatus('PNID_A', ['id' => 'wamid.R1', 'status' => 'delivered', 'timestamp' => '1800000000'])->assertOk();
        $this->assertSame($first, $r->refresh()->delivered_at->timestamp);
    }

    public function test_unknown_id_is_ignored(): void
    {
        $r = $this->reminder($this->number());

        $this->postStatus('PNID_A', ['id' => 'wamid.NOPE', 'status' => 'delivered'])->assertOk();

        $this->assertNull($r->refresh()->delivered_at);
    }

    public function test_status_for_another_number_does_not_touch_reminder(): void
    {
        $a = $this->number('PNID_A');
        $b = $this->number('PNID_B');
        $reminderA = $this->reminder($a, 'wamid.SHARED');

        $this->postStatus('PNID_B', ['id' => 'wamid.SHARED', 'status' => 'read'])->assertOk();
        $this->postStatus('PNID_B', ['id' => 'wamid.SHARED', 'status' => 'failed', 'errors' => [['code' => 1, 'title' => 'x']]])->assertOk();

        $reminderA->refresh();
        $this->assertNull($reminderA->delivered_at);
        $this->assertNull($reminderA->read_at);
        $this->assertNull($reminderA->delivery_error);
    }
}
