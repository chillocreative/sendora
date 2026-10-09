<?php

namespace Tests\Feature;

use App\Jobs\ProcessSendoraCommandJob;
use App\Models\User;
use App\Models\WhatsappNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WhatsappWebhookDedupeTest extends TestCase
{
    use RefreshDatabase;

    public function test_redelivered_message_id_dispatches_job_once(): void
    {
        Queue::fake();
        Cache::flush();

        $user = User::factory()->create();
        $number = WhatsappNumber::create([
            'user_id' => $user->id,
            'phone_number' => '60123456789',
            'status' => 'connected',
        ]);

        $payload = [
            'user_id' => $user->id,
            'phone_number' => $number->id,
            'from' => '60111222333@s.whatsapp.net',
            'message' => '/sendora Meeting tomorrow at 3pm',
            'message_id' => 'ABC123',
        ];

        $this->postJson('/api/whatsapp/incoming-message', $payload)
            ->assertOk()
            ->assertJson(['queued' => true, 'type' => 'sendora']);

        $this->postJson('/api/whatsapp/incoming-message', $payload)
            ->assertOk()
            ->assertJson(['success' => true, 'skipped' => 'duplicate']);

        Queue::assertPushed(ProcessSendoraCommandJob::class, 1);
    }
}
