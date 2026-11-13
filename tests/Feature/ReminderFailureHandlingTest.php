<?php

namespace Tests\Feature;

use App\Mail\ReminderEmailNotification;
use App\Models\Reminder;
use App\Models\User;
use App\Models\WhatsappNumber;
use App\Models\WhatsappTemplate;
use App\Services\ReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ReminderFailureHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    protected function cloudNumber(array $attributes = []): WhatsappNumber
    {
        return WhatsappNumber::create(array_merge([
            'user_id' => User::factory()->create()->id,
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

    protected function template(): WhatsappTemplate
    {
        return WhatsappTemplate::create([
            'waba_id' => 'WABA1',
            'purpose' => 'reminder',
            'name' => 'sendora_reminder',
            'language' => 'en',
            'variables' => ['title', 'datetime', 'location', 'description'],
            'status' => 'approved',
        ]);
    }

    protected function reminder(WhatsappNumber $number, array $attributes = []): Reminder
    {
        return Reminder::create(array_merge([
            'user_id' => $number->user_id,
            'whatsapp_number_id' => $number->id,
            'title' => 'Dentist',
            'reminder_at' => '2026-12-01 09:45:00',
            'event_at' => '2026-12-01 10:00:00',
            'minutes_before' => 15,
            'source' => 'web',
            'status' => 'pending',
        ], $attributes));
    }

    protected function failWithMeta400(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['error' => ['code' => 131047, 'message' => 'Re-engagement message']], 400),
        ]);
    }

    public function test_missing_template_gives_friendly_message_and_technical_log(): void
    {
        Http::fake();
        $reminder = $this->reminder($this->cloudNumber());

        $this->assertFalse(app(ReminderService::class)->sendReminder($reminder));

        $reminder->refresh();
        $this->assertSame('failed', $reminder->status);
        $this->assertSame('No approved WhatsApp template is set up yet. Please contact support.', $reminder->error_message);
        $this->assertStringNotContainsString("purpose 'reminder'", $reminder->error_message);
        $this->assertDatabaseHas('reminder_logs', [
            'reminder_id' => $reminder->id,
            'action' => 'failed',
            'details' => "No approved WhatsApp template for purpose 'reminder'",
        ]);
    }

    public function test_unverified_owner_phone_gives_friendly_message(): void
    {
        Http::fake();
        $this->template();
        $reminder = $this->reminder($this->cloudNumber(['owner_phone_verified_at' => null]));

        $this->assertFalse(app(ReminderService::class)->sendReminder($reminder));

        $reminder->refresh();
        $this->assertSame('Your WhatsApp phone number is not verified. Verify it in WhatsApp settings.', $reminder->error_message);
        $this->assertDatabaseHas('reminder_logs', [
            'reminder_id' => $reminder->id,
            'action' => 'failed',
            'details' => 'Owner phone not verified for this Cloud number',
        ]);
    }

    public function test_meta_error_gives_rejected_message_without_leaking_token_or_json(): void
    {
        $this->failWithMeta400();
        $this->template();
        $reminder = $this->reminder($this->cloudNumber());

        $this->assertFalse(app(ReminderService::class)->sendReminder($reminder));

        $reminder->refresh();
        $this->assertSame('failed', $reminder->status);
        $this->assertSame('WhatsApp rejected this message: (131047) Re-engagement message', $reminder->error_message);
        $this->assertStringNotContainsString('EAAG', $reminder->error_message);
        $this->assertStringNotContainsString('{', $reminder->error_message);
    }

    public function test_server_error_is_reported_as_temporary_outage(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response('Bad gateway', 502)]);
        $this->template();
        $reminder = $this->reminder($this->cloudNumber());

        $this->assertFalse(app(ReminderService::class)->sendReminder($reminder));

        $this->assertSame(
            'WhatsApp is temporarily unavailable. We could not deliver this reminder.',
            $reminder->fresh()->error_message
        );
    }

    public function test_failure_reason_truncates_long_meta_errors_and_handles_null_response(): void
    {
        $service = app(ReminderService::class);

        $long = \App\Services\WhatsappSendResult::failed(str_repeat('x', 400), 400);
        $reason = $service->failureReason($long);
        $this->assertSame('WhatsApp rejected this message: '.str_repeat('x', 150), $reason);

        $this->assertStringContainsString('temporarily unavailable', $service->failureReason(null));
    }

    public function test_failure_queues_failed_email_with_reason_when_notify_email_set(): void
    {
        Mail::fake();
        $this->failWithMeta400();
        $this->template();
        $reminder = $this->reminder($this->cloudNumber(), ['notify_email' => 'owner@example.com']);

        app(ReminderService::class)->sendReminder($reminder);

        Mail::assertQueued(ReminderEmailNotification::class, function (ReminderEmailNotification $mail) use ($reminder) {
            return $mail->type === 'failed'
                && $mail->reminder->is($reminder)
                && $mail->reason === 'WhatsApp rejected this message: (131047) Re-engagement message'
                && $mail->hasTo('owner@example.com');
        });

        $rendered = (new ReminderEmailNotification($reminder->fresh(), 'failed', 'Because reasons'))->render();
        $this->assertStringContainsString('Because reasons', $rendered);
        $this->assertStringContainsString('Dentist', $rendered);
    }

    public function test_failure_without_notify_email_queues_no_mail_and_success_queues_no_failed_mail(): void
    {
        Mail::fake();
        Http::fake([
            'graph.facebook.com/*' => Http::sequence()
                ->push(['error' => ['code' => 131047, 'message' => 'Re-engagement message']], 400)
                ->push(['messages' => [['id' => 'wamid.OK']]], 200),
        ]);
        $this->template();
        $number = $this->cloudNumber();
        app(ReminderService::class)->sendReminder($this->reminder($number));
        Mail::assertNothingQueued();

        $ok = $this->reminder($number, ['notify_email' => 'owner@example.com', 'title' => 'Other']);
        $this->assertTrue(app(ReminderService::class)->sendReminder($ok));

        Mail::assertQueued(ReminderEmailNotification::class, fn ($m) => $m->type === 'due');
        Mail::assertNotQueued(ReminderEmailNotification::class, fn ($m) => $m->type === 'failed');
    }

    public function test_failed_recurring_reminder_still_creates_exactly_one_next_reminder(): void
    {
        $this->failWithMeta400();
        $this->template();
        $reminder = $this->reminder($this->cloudNumber(), ['recurrence_rule' => 'weekly']);
        $service = app(ReminderService::class);

        $service->sendReminder($reminder);
        $service->sendReminder($reminder->fresh());

        $next = Reminder::where('status', 'pending')->where('id', '!=', $reminder->id)->get();
        $this->assertCount(1, $next);
        $this->assertSame('2026-12-08 10:00:00', $next->first()->event_at->format('Y-m-d H:i:s'));
        $this->assertSame('weekly', $next->first()->recurrence_rule);
    }

    public function test_failed_non_recurring_reminder_creates_no_next_reminder(): void
    {
        $this->failWithMeta400();
        $this->template();
        $reminder = $this->reminder($this->cloudNumber());

        app(ReminderService::class)->sendReminder($reminder);

        $this->assertSame(1, Reminder::count());
    }
}
