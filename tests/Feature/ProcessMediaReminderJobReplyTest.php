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
}
