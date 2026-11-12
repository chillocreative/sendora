<?php

namespace Tests\Feature;

use App\Jobs\ProcessMediaReminderJob;
use App\Models\User;
use App\Models\WhatsappNumber;
use App\Services\MediaParserService;
use App\Services\ReminderService;
use App\Services\SendoraCommandService;
use App\Services\WhatsappSendResult;
use App\Services\WhatsappService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class ProcessMediaReminderJobReplyTest extends TestCase
{
    use RefreshDatabase;

    private function run_job(?object $sendResult): void
    {
        $user = User::factory()->create();
        $number = WhatsappNumber::create([
            'user_id' => $user->id,
            'status' => 'connected',
            'phone_number' => '60123456789',
        ]);

        $wa = Mockery::mock(WhatsappService::class);
        $wa->shouldReceive('sendMessage')->once()->andReturn($sendResult);

        $parser = Mockery::mock(MediaParserService::class);
        $parser->shouldReceive('parseMediaForEvent')->never();

        // No plan => the AI-feature guard sends a reply and returns.
        (new ProcessMediaReminderJob($user->id, $number->id, '60111222333', 'x', 'image/png', null, null, 'wamid.1'))
            ->handle($parser, Mockery::mock(ReminderService::class), Mockery::mock(SendoraCommandService::class), $wa);
    }

    public function test_failed_reply_is_logged_and_does_not_throw(): void
    {
        Log::spy();

        $this->run_job(WhatsappSendResult::failed('outside_24h_window'));

        Log::shouldHaveReceived('warning')->withArgs(
            fn ($msg, $ctx) => str_contains($msg, 'reply could not be delivered')
                && $ctx['error'] === 'outside_24h_window'
                && $ctx['wa_message_id'] === 'wamid.1'
        )->once();
    }

    public function test_null_response_is_logged_and_does_not_throw(): void
    {
        Log::spy();

        $this->run_job(null);

        Log::shouldHaveReceived('warning')->withArgs(
            fn ($msg) => str_contains($msg, 'reply could not be delivered')
        )->once();
    }

    public function test_successful_reply_logs_nothing(): void
    {
        Log::spy();

        $this->run_job(new WhatsappSendResult(true, 200));

        Log::shouldNotHaveReceived('warning');
    }

    public function test_retry_after_partial_failure_does_not_duplicate_reminders(): void
    {
        $plan = \App\Models\SubscriptionPlan::create([
            'name' => 'Pro', 'monthly_price' => 1, 'yearly_price' => 1,
            'limits' => ['whatsapp_nos' => 1, 'reminders_per_month' => 0, 'features' => ['ai_command_parsing' => true]],
        ]);
        $user = User::factory()->create();
        \App\Models\UserSubscription::create(['user_id' => $user->id, 'subscription_plan_id' => $plan->id, 'status' => 'active']);
        $number = WhatsappNumber::create(['user_id' => $user->id, 'status' => 'connected', 'phone_number' => '60123456789']);

        $events = [
            ['title' => 'Event A', 'date' => now()->addDays(3)->format('Y-m-d'), 'time' => '10:00'],
            ['title' => 'Event B', 'date' => now()->addDays(4)->format('Y-m-d'), 'time' => '11:00'],
        ];

        $parser = Mockery::mock(MediaParserService::class);
        $parser->shouldReceive('parseMediaForEvent')->andReturn(['events' => $events]);

        $wa = Mockery::mock(WhatsappService::class);
        $wa->shouldReceive('sendMessage')->andReturn(new WhatsappSendResult(true, 200));

        $real = app(ReminderService::class);
        $calls = 0;
        $service = Mockery::mock(ReminderService::class);
        $service->shouldReceive('createReminder')->andReturnUsing(function ($u, $d, $k = null) use ($real, &$calls) {
            if (++$calls === 2) {
                throw new \RuntimeException('boom'); // first attempt dies on event B
            }

            // sqlite's reminders.source CHECK predates 'whatsapp_media' (MySQL-only migration)
            $d['source'] = 'web';

            return $real->createReminder($u, $d, $k);
        });

        $job = new ProcessMediaReminderJob($user->id, $number->id, '60111222333', 'x', 'image/png', null, null, 'wamid.9');
        $commands = app(SendoraCommandService::class);

        try {
            $job->handle($parser, $service, $commands, $wa);
        } catch (\RuntimeException) {
        }

        $job->handle($parser, $service, $commands, $wa); // the retry

        $this->assertSame(2, \App\Models\Reminder::where('user_id', $user->id)->count());
    }

    public function test_limit_reached_mid_upload_replies_with_partial_count(): void
    {
        $plan = \App\Models\SubscriptionPlan::create([
            'name' => 'Tiny', 'monthly_price' => 1, 'yearly_price' => 1,
            'limits' => ['whatsapp_nos' => 1, 'reminders_per_month' => 1, 'features' => ['ai_command_parsing' => true]],
        ]);
        $user = User::factory()->create();
        \App\Models\UserSubscription::create(['user_id' => $user->id, 'subscription_plan_id' => $plan->id, 'status' => 'active']);
        $number = WhatsappNumber::create(['user_id' => $user->id, 'status' => 'connected', 'phone_number' => '60123456789']);

        $parser = Mockery::mock(MediaParserService::class);
        $parser->shouldReceive('parseMediaForEvent')->andReturn(['events' => [
            ['title' => 'A', 'date' => now()->addDays(3)->format('Y-m-d'), 'time' => '10:00'],
            ['title' => 'B', 'date' => now()->addDays(4)->format('Y-m-d'), 'time' => '11:00'],
        ]]);

        $sent = null;
        $wa = Mockery::mock(WhatsappService::class);
        $wa->shouldReceive('sendMessage')->once()->andReturnUsing(function ($n, $to, $msg) use (&$sent) {
            $sent = $msg;

            return new WhatsappSendResult(true, 200);
        });

        $real = app(ReminderService::class);
        $service = Mockery::mock(ReminderService::class);
        $service->shouldReceive('createReminder')->andReturnUsing(function ($u, $d, $k = null) use ($real) {
            $d['source'] = 'web'; // sqlite CHECK predates 'whatsapp_media'

            return $real->createReminder($u, $d, $k);
        });

        (new ProcessMediaReminderJob($user->id, $number->id, '60111222333', 'x', 'image/png', null, null, 'wamid.7'))
            ->handle($parser, $service, app(SendoraCommandService::class), $wa);

        $this->assertStringContainsString('monthly reminder limit (1)', $sent);
        $this->assertStringContainsString('1 of 2 events', $sent);
        $this->assertSame(1, \App\Models\Reminder::where('user_id', $user->id)->count());
    }
}
