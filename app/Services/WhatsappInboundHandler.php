<?php

namespace App\Services;

use App\Jobs\ProcessAiReplyJob;
use App\Jobs\ProcessMediaReminderJob;
use App\Jobs\ProcessSendoraCommandJob;
use App\Models\Conversation;
use App\Models\WhatsappNumber;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Provider-agnostic routing of an inbound WhatsApp message (Baileys or Cloud API).
 */
class WhatsappInboundHandler
{
    /**
     * @return array<string, mixed> JSON-serialisable result
     */
    public function handle(
        WhatsappNumber $whatsappNumber,
        string $from,
        ?string $message,
        ?string $messageId,
        ?string $mediaBase64 = null,
        ?string $mime = null,
        ?string $filename = null,
        bool $fromMe = false,
    ): array {
        // Skip group messages
        if (str_contains($from, '@g.us')) {
            return ['success' => true, 'skipped' => 'group_message'];
        }

        $messageText = (string) ($message ?? '');
        $userId = (int) $whatsappNumber->user_id;
        $numberId = (int) $whatsappNumber->id;

        // Self-sent media with /sendora caption → extract event
        if ($mediaBase64 && $fromMe && str_starts_with(strtolower(trim($messageText)), '/sendora')) {
            if ($this->isDuplicateMessage($messageId, $whatsappNumber)) {
                return ['success' => true, 'skipped' => 'duplicate'];
            }

            ProcessMediaReminderJob::dispatch(
                $userId,
                $numberId,
                $from,
                $mediaBase64,
                $mime ?? 'application/octet-stream',
                $filename,
                $this->cleanSendoraCaption($messageText),
                $messageId
            );

            return ['success' => true, 'queued' => true, 'type' => 'media_reminder'];
        }

        // Check for /stopchat and /startchat commands (AI toggle per conversation)
        $lowerMessage = strtolower(trim($messageText));
        if (in_array($lowerMessage, ['/stopchat', '/startchat'])) {
            return $this->handleAiChatToggle($whatsappNumber, $from, $lowerMessage === '/startchat');
        }

        // Check for /sendora command
        if (str_starts_with(strtolower(trim($messageText)), '/sendora')) {
            if ($this->isDuplicateMessage($messageId, $whatsappNumber)) {
                return ['success' => true, 'skipped' => 'duplicate'];
            }

            ProcessSendoraCommandJob::dispatch($userId, $numberId, $from, $messageText, $messageId);

            return ['success' => true, 'queued' => true, 'type' => 'sendora'];
        }

        // Dispatch AI reply job to queue
        if ($this->isDuplicateMessage($messageId, $whatsappNumber)) {
            return ['success' => true, 'skipped' => 'duplicate'];
        }

        ProcessAiReplyJob::dispatch($userId, $numberId, $from, $messageText, $messageId);

        return ['success' => true, 'queued' => true];
    }

    /**
     * Returns true if this WhatsApp message_id was already accepted (redelivery).
     */
    protected function isDuplicateMessage(?string $messageId, WhatsappNumber $whatsappNumber): bool
    {
        if (empty($messageId)) {
            return false;
        }

        return ! Cache::add(
            "wa_msg:{$whatsappNumber->id}:{$messageId}",
            1,
            now()->addDays(2)
        );
    }

    protected function cleanSendoraCaption(string $text): ?string
    {
        $cleaned = preg_replace('/^\/sendora\s*/i', '', trim($text));

        return $cleaned !== '' ? $cleaned : null;
    }

    protected function handleAiChatToggle(WhatsappNumber $whatsappNumber, string $contactJid, bool $enable): array
    {
        $cleanPhone = $this->cleanPhoneForConversation($contactJid);

        $conversation = Conversation::firstOrCreate(
            [
                'whatsapp_number_id' => $whatsappNumber->id,
                'contact_phone' => $cleanPhone,
            ],
            [
                'user_id' => $whatsappNumber->user_id,
                'status' => 'active',
                'contact_jid' => $contactJid,
            ]
        );

        $newStatus = $enable ? 'active' : 'paused';
        $conversation->update(['status' => $newStatus]);

        Log::info('AI chat toggled via command', [
            'conversation_id' => $conversation->id,
            'contact_phone' => $cleanPhone,
            'whatsapp_number_id' => $whatsappNumber->id,
            'new_status' => $newStatus,
        ]);

        // Send confirmation after response to avoid blocking the webhook
        dispatch(function () use ($whatsappNumber, $contactJid, $enable) {
            $message = $enable
                ? "AI assistant has been resumed for this conversation.\n\nSend /stopchat to pause again."
                : "AI assistant has been paused for this conversation.\n\nSend /startchat to resume.";

            (new WhatsappService)->sendMessage($whatsappNumber, $contactJid, $message);
        })->afterResponse();

        return [
            'success' => true,
            'type' => 'ai_toggle',
            'status' => $newStatus,
        ];
    }

    protected function cleanPhoneForConversation(string $phone): string
    {
        if (str_contains($phone, '@lid')) {
            $phone = str_replace('@lid', '', $phone);
            $phone = preg_replace('/:.*$/', '', $phone);

            return $phone;
        }

        $phone = str_replace('@s.whatsapp.net', '', $phone);
        $phone = str_replace('@g.us', '', $phone);
        $phone = preg_replace('/:.*$/', '', $phone);
        $phone = preg_replace('/[^0-9]/', '', $phone);

        return $phone;
    }
}
