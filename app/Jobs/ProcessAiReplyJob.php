<?php

namespace App\Jobs;

use App\Models\WhatsappNumber;
use App\Services\AiReplyService;
use App\Services\OpenAiService;
use App\Services\WhatsappService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAiReplyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 60;

    public function __construct(
        protected int $userId,
        protected int $whatsappNumberId,
        protected string $contactPhone,
        protected string $messageText,
        protected ?string $waMessageId = null
    ) {
        $this->onQueue('ai-replies');
    }

    public function handle(): void
    {
        $whatsappNumber = WhatsappNumber::where('id', $this->whatsappNumberId)
            ->where('user_id', $this->userId)
            ->first();

        if (!$whatsappNumber) {
            Log::warning('ProcessAiReplyJob: WhatsApp number not found', [
                'user_id' => $this->userId,
                'whatsapp_number_id' => $this->whatsappNumberId,
            ]);
            return;
        }

        $service = new AiReplyService(
            new OpenAiService(),
            new WhatsappService()
        );

        $result = $service->processInboundMessage(
            $whatsappNumber,
            $this->contactPhone,
            $this->messageText,
            $this->waMessageId
        );

        Log::info('ProcessAiReplyJob completed', [
            'user_id' => $this->userId,
            'contact_phone' => $this->contactPhone,
            'result' => $result,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessAiReplyJob failed', [
            'user_id' => $this->userId,
            'contact_phone' => $this->contactPhone,
            'error' => $exception->getMessage(),
        ]);
    }
}
