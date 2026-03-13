<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\WhatsappNumber;
use App\Services\SendoraCommandService;
use App\Services\WhatsappService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessSendoraCommandJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 60;

    public function __construct(
        protected int $userId,
        protected int $whatsappNumberId,
        protected string $contactPhone,
        protected string $messageText,
        protected ?string $waMessageId = null,
    ) {}

    public function handle(SendoraCommandService $commandService, WhatsappService $whatsappService): void
    {
        $user = User::find($this->userId);
        $waNumber = WhatsappNumber::find($this->whatsappNumberId);

        if (! $user || ! $waNumber) {
            Log::warning('ProcessSendoraCommandJob: user or WA number not found', [
                'user_id' => $this->userId,
                'wa_number_id' => $this->whatsappNumberId,
            ]);

            return;
        }

        $commandType = $commandService->detectCommandType($this->messageText);

        // Only enforce AI feature flag for commands that use AI (create, cancel)
        if (in_array($commandType, ['create', 'cancel', 'edit'])) {
            $plan = $user->current_plan;
            $features = $plan?->limits['features'] ?? [];
            if (! ($features['ai_command_parsing'] ?? false)) {
                $whatsappService->sendMessage(
                    $waNumber,
                    $this->contactPhone,
                    "\xE2\x9D\x8C AI command parsing is not available on your current plan. Please upgrade to use /sendora commands."
                );

                return;
            }
        }

        $reply = $commandService->executeCommand($user, $waNumber, $this->messageText);

        $whatsappService->sendMessage($waNumber, $this->contactPhone, $reply);
    }
}
