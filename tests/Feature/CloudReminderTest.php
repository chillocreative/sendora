<?php

namespace Tests\Feature;

use App\Jobs\ProcessAiReplyJob;
use App\Models\AdminNotification;
use App\Models\Reminder;
use App\Models\Setting;
use App\Models\User;
use App\Models\WhatsappNumber;
use App\Models\WhatsappTemplate;
use App\Services\AdminNotificationService;
use App\Services\ReminderService;
use App\Services\WhatsappInboundHandler;
use App\Services\WhatsappTemplateSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CloudReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    protected function cloudNumber(array $attributes = [], ?User $user = null): WhatsappNumber
    {
        return WhatsappNumber::create(array_merge([
            'user_id' => ($user ?? User::factory()->create(['name' => 'Aisyah']))->id,
            'phone_number' => '60123456789',
            'status' => 'connected',
            'provider' => 'cloud',
            'waba_id' => 'WABA1',
            'phone_number_id' => '1055550001',
            'access_token' => 'EAAG-secret-token',
            'owner_phone' => '60199887766',
            'owner_phone_verified_at' => now(),
        ], $attributes));
    }

    protected function template(string $purpose = 'reminder', array $attributes = []): WhatsappTemplate
    {
        return WhatsappTemplate::create(array_merge([
            'waba_id' => 'WABA1',
            'purpose' => $purpose,
            'name' => $purpose === 'reminder' ? 'sendora_reminder' : 'sendora_admin_alert',
            'language' => 'en',
            'variables' => $purpose === 'reminder'
                ? ['title', 'datetime', 'location', 'description']
                : ['type', 'message'],
            'status' => 'approved',
        ], $attributes));
    }

    protected function reminder(WhatsappNumber $number, array $attributes = []): Reminder
    {
        return Reminder::create(array_merge([
            'user_id' => $number->user_id,
            'whatsapp_number_id' => $number->id,
            'title' => 'Dentist',
            'description' => "Bring\nX-rays",
            'location' => 'KL Clinic',
            'reminder_at' => now()->addHour(),
            'event_at' => '2026-12-01 10:00:00',
            'status' => 'pending',
        ], $attributes));
    }

    protected function fakeGraph(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.OK1']]], 200),
        ]);
    }

    public function test_cloud_reminder_sends_template_to_owner_phone_not_business_number(): void
    {
        $this->fakeGraph();
        $number = $this->cloudNumber();
        $this->template();
        $reminder = $this->reminder($number);

        $this->assertTrue(app(ReminderService::class)->sendReminder($reminder));

        Http::assertSent(function (Request $request) {
            $data = $request->data();

            return str_contains($request->url(), '/1055550001/messages')
                && $request->hasHeader('Authorization', 'Bearer EAAG-secret-token')
                && $data['type'] === 'template'
                && $data['to'] === '60199887766'
                && $data['template']['name'] === 'sendora_reminder'
                && $data['template']['language']['code'] === 'en'
                && array_column($data['template']['components'][0]['parameters'], 'text') === [
                    'Dentist',
                    'Tuesday, 01 Dec 2026 at 10:00 AM',
                    'KL Clinic',
                    'Bring X-rays',
                ];
        });
        Http::assertNotSent(fn (Request $r) => ($r->data()['to'] ?? null) === '60123456789');

        $reminder->refresh();
        $this->assertSame('sent', $reminder->status);
        $this->assertSame('wamid.OK1', $reminder->wa_message_id);
    }

    public function test_cloud_reminder_fails_when_owner_phone_unverified(): void
    {
        Http::fake();
        $number = $this->cloudNumber(['owner_phone_verified_at' => null]);
        $this->template();
        $reminder = $this->reminder($number);

        $this->assertFalse(app(ReminderService::class)->sendReminder($reminder));

        Http::assertNothingSent();
        $reminder->refresh();
        $this->assertSame('failed', $reminder->status);
        $this->assertSame('Owner phone not verified for this Cloud number', $reminder->error_message);
        $this->assertDatabaseHas('reminder_logs', [
            'reminder_id' => $reminder->id,
            'action' => 'failed',
            'details' => 'Owner phone not verified for this Cloud number',
        ]);
    }

    public function test_cloud_reminder_fails_when_no_approved_template(): void
    {
        Http::fake();
        $number = $this->cloudNumber();
        $this->template('reminder', ['status' => 'pending']);
        $reminder = $this->reminder($number);

        $this->assertFalse(app(ReminderService::class)->sendReminder($reminder));

        Http::assertNothingSent();
        $reminder->refresh();
        $this->assertSame('failed', $reminder->status);
        $this->assertStringContainsString("No approved WhatsApp template for purpose 'reminder'", $reminder->error_message);
    }

    public function test_cloud_reminder_records_meta_error_on_failure(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['error' => ['code' => 131047, 'message' => 'Re-engagement']], 400),
        ]);
        $number = $this->cloudNumber();
        $this->template();
        $reminder = $this->reminder($number);

        $this->assertFalse(app(ReminderService::class)->sendReminder($reminder));

        $reminder->refresh();
        $this->assertSame('failed', $reminder->status);
        $this->assertStringContainsString('131047', $reminder->error_message);
    }

    public function test_baileys_reminder_still_posts_to_node_server_with_business_phone(): void
    {
        Http::fake(['*' => Http::response(['success' => true, 'message_id' => 'bail-1'], 200)]);
        $user = User::factory()->create();
        $number = WhatsappNumber::create([
            'user_id' => $user->id,
            'phone_number' => '60123456789',
            'status' => 'connected',
        ]);
        $reminder = $this->reminder($number);

        $this->assertTrue(app(ReminderService::class)->sendReminder($reminder));

        Http::assertSent(function (Request $request) use ($number) {
            return str_contains($request->url(), '/send-message')
                && ! str_contains($request->url(), 'graph.facebook.com')
                && $request['to'] === '60123456789'
                && $request['phone_number'] === $number->id;
        });
        $this->assertSame('sent', $reminder->refresh()->status);
    }

    public function test_admin_notification_cloud_path_uses_admin_mobile_and_never_device_phone(): void
    {
        $this->fakeGraph();
        $admin = User::factory()->create(['email' => 'admin@blaster.com']);
        $this->cloudNumber(['owner_phone' => null, 'owner_phone_verified_at' => null], $admin);
        $this->template('admin_notification');
        Setting::create(['key' => 'admin_mobile_number', 'value' => '60177000111']);

        $notification = AdminNotification::create([
            'notification_type' => 'custom',
            'user_id' => null,
            'data' => ['a' => 1],
        ]);

        $this->assertTrue(app(AdminNotificationService::class)->sendNotification('custom', null, ['x' => 1]));

        Http::assertSent(fn (Request $r) => $r->data()['to'] === '60177000111'
            && $r->data()['template']['name'] === 'sendora_admin_alert');
        Http::assertNotSent(fn (Request $r) => ($r->data()['to'] ?? null) === '60123456789');
    }

    public function test_admin_notification_cloud_without_recipient_fails_and_does_not_self_send(): void
    {
        Http::fake();
        $admin = User::factory()->create(['email' => 'admin@blaster.com']);
        $this->cloudNumber(['owner_phone' => null, 'owner_phone_verified_at' => null], $admin);
        $this->template('admin_notification');

        $this->assertFalse(app(AdminNotificationService::class)->sendNotification('custom', null, ['x' => 1]));

        Http::assertNothingSent();
        $this->assertSame(
            'No admin mobile number / owner phone for Cloud device',
            AdminNotification::first()->last_error
        );
    }

    public function test_admin_notification_cloud_falls_back_to_verified_owner_phone(): void
    {
        $this->fakeGraph();
        $admin = User::factory()->create(['email' => 'admin@blaster.com']);
        $this->cloudNumber([], $admin);
        $this->template('admin_notification');

        $this->assertTrue(app(AdminNotificationService::class)->sendNotification('custom', null, ['x' => 1]));

        Http::assertSent(fn (Request $r) => $r->data()['to'] === '60199887766');
    }

    public function test_owner_verification_flow(): void
    {
        Queue::fake();
        $number = $this->cloudNumber(['owner_phone' => null, 'owner_phone_verified_at' => null]);
        $code = $number->startOwnerVerification('0199887766');

        $this->assertSame(6, strlen($code));
        $this->assertNotSame($code, $number->fresh()->owner_verification_code);
        $this->assertFalse($number->fresh()->hasVerifiedOwnerPhone());
        $this->assertArrayNotHasKey('owner_verification_code', $number->fresh()->toArray());

        $handler = app(WhatsappInboundHandler::class);

        // wrong code
        $result = $handler->handle($number->fresh(), '60199887766', 'VERIFY ZZZZZZ', 'm1');
        $this->assertSame('owner_verification', $result['type']);
        $this->assertFalse($result['verified']);

        // wrong sender
        $result = $handler->handle($number->fresh(), '60111222333', "verify {$code}", 'm2');
        $this->assertFalse($result['verified']);
        $this->assertFalse($number->fresh()->hasVerifiedOwnerPhone());

        // right code and sender
        $result = $handler->handle($number->fresh(), '60199887766', " verify {$code} ", 'm3');
        $this->assertTrue($result['verified']);

        $fresh = $number->fresh();
        $this->assertTrue($fresh->hasVerifiedOwnerPhone());
        $this->assertNull($fresh->owner_verification_code);
        $this->assertTrue($fresh->isOwnerPhone('60199887766'));
        $this->assertFalse($fresh->isOwnerPhone('60111222333'));

        Queue::assertNotPushed(ProcessAiReplyJob::class);
    }

    public function test_verify_text_on_baileys_number_is_not_intercepted(): void
    {
        Queue::fake();
        $number = WhatsappNumber::create([
            'user_id' => User::factory()->create()->id,
            'phone_number' => '60123456789',
            'status' => 'connected',
        ]);

        $result = app(WhatsappInboundHandler::class)->handle($number, '60111222333', 'VERIFY ABC123', 'b1');

        $this->assertArrayNotHasKey('type', $result);
        Queue::assertPushed(ProcessAiReplyJob::class);
    }

    public function test_body_parameters_are_sanitised_for_meta(): void
    {
        $sender = app(WhatsappTemplateSender::class);

        $this->assertSame('a b c', $sender->sanitize("a\nb\tc"));
        $this->assertSame('a b', $sender->sanitize('a        b'));
        $this->assertSame('-', $sender->sanitize(''));
        $this->assertSame('-', $sender->sanitize(" \n "));
        $this->assertSame('-', $sender->sanitize(null));
        $this->assertSame(900, mb_strlen($sender->sanitize(str_repeat('x', 2000))));
    }
}
