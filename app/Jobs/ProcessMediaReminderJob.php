<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\WhatsappNumber;
use App\Services\MediaParserService;
use App\Services\ReminderService;
use App\Services\SendoraCommandService;
use App\Services\WhatsappService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessMediaReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(
        protected int $userId,
        protected int $whatsappNumberId,
        protected string $contactPhone,
        protected string $mediaBase64,
        protected string $mediaMimetype,
        protected ?string $mediaFilename,
        protected ?string $caption,
        protected ?string $waMessageId = null,
    ) {}

    public function handle(
        MediaParserService $mediaParser,
        ReminderService $reminderService,
        SendoraCommandService $commandService,
        WhatsappService $whatsappService,
    ): void {
        $user = User::find($this->userId);
        $waNumber = WhatsappNumber::find($this->whatsappNumberId);

        if (! $user || ! $waNumber) {
            Log::warning('ProcessMediaReminderJob: user or WA number not found', [
                'user_id' => $this->userId,
                'wa_number_id' => $this->whatsappNumberId,
            ]);

            return;
        }

        // Check AI feature flag
        $plan = $user->current_plan;
        $features = $plan?->limits['features'] ?? [];
        if (! ($features['ai_command_parsing'] ?? false)) {
            $whatsappService->sendMessage(
                $waNumber,
                $this->contactPhone,
                "\xE2\x9D\x8C AI features are not available on your current plan. Please upgrade to extract events from files and images."
            );

            return;
        }

        $result = $mediaParser->parseMediaForEvent(
            $this->mediaBase64,
            $this->mediaMimetype,
            $this->mediaFilename,
            $this->caption,
        );

        if (! $result) {
            $whatsappService->sendMessage(
                $waNumber,
                $this->contactPhone,
                "\xF0\x9F\x93\x84 Could not find event details in this file/image. Try sending an event poster, invitation, or document with date/time information."
            );

            return;
        }

        if (isset($result['error'])) {
            $message = match ($result['error']) {
                'unsupported_format' => "\xE2\x9D\x8C Unsupported file format. Please send as .docx or .pptx instead of legacy .doc/.ppt formats.",
                'unsupported_type' => "\xE2\x9D\x8C Unsupported file type. Supported formats: images (JPG, PNG, WebP), PDF, DOCX, PPTX, TXT.",
                default => "\xE2\x9D\x8C Could not process this file.",
            };

            $whatsappService->sendMessage($waNumber, $this->contactPhone, $message);

            return;
        }

        $eventAt = $result['date'].' '.($result['time'] ?? '09:00');

        $reminder = $reminderService->createReminder($user, [
            'title' => $result['title'],
            'description' => $result['description'] ?? null,
            'event_at' => $eventAt,
            'minutes_before' => 15,
            'location' => $result['location'] ?? null,
            'whatsapp_number_id' => $waNumber->id,
            'source' => 'whatsapp_media',
            'add_to_calendar' => $user->googleCalendarConnection !== null,
        ]);

        $reply = $commandService->formatConfirmation($reminder);
        $whatsappService->sendMessage($waNumber, $this->contactPhone, $reply);
    }
}
