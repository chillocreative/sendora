<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\WhatsappNumber;
use App\Models\WhatsappTemplate;
use App\Services\WhatsappCloudService;
use App\Services\WhatsappInboundHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MetaWebhookController extends Controller
{
    private const MEDIA_TYPES = ['image', 'document', 'audio', 'video'];

    public function __construct(
        protected WhatsappInboundHandler $inbound,
        protected WhatsappCloudService $cloud,
    ) {}

    /**
     * Meta subscription handshake. PHP rewrites the dots in hub.* query keys to underscores.
     */
    public function verify(Request $request)
    {
        $expected = (string) config('services.meta.webhook_verify_token');

        if ($request->query('hub_mode') === 'subscribe'
            && $expected !== ''
            && hash_equals($expected, (string) $request->query('hub_verify_token'))) {
            return response((string) $request->query('hub_challenge'), 200)
                ->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    public function handle(Request $request)
    {
        if (! $this->hasValidSignature($request)) {
            return response('Invalid signature', 403);
        }

        foreach ((array) $request->input('entry', []) as $entry) {
            foreach ((array) ($entry['changes'] ?? []) as $change) {
                $field = $change['field'] ?? null;
                $value = (array) ($change['value'] ?? []);

                try {
                    match ($field) {
                        'messages' => $this->handleMessagesChange($value),
                        'phone_number_quality_update', 'account_update' => $this->handleAccountChange($entry['id'] ?? null, $value),
                        'message_template_status_update' => $this->handleTemplateStatusUpdate($entry['id'] ?? null, $value),
                        default => null,
                    };
                } catch (\Throwable $e) {
                    // Always acknowledge: Meta retries non-200 responses and the message-id dedupe makes that noisy.
                    Log::error('Meta webhook change failed', ['field' => $field, 'error' => $e->getMessage()]);
                }
            }
        }

        return response('OK', 200);
    }

    protected function hasValidSignature(Request $request): bool
    {
        $secret = (string) config('services.meta.app_secret');
        $header = (string) $request->header('X-Hub-Signature-256');

        if ($secret === '' || $header === '') {
            return false;
        }

        return hash_equals('sha256='.hash_hmac('sha256', $request->getContent(), $secret), $header);
    }

    protected function handleMessagesChange(array $value): void
    {
        $phoneNumberId = $value['metadata']['phone_number_id'] ?? null;

        $number = $phoneNumberId
            ? WhatsappNumber::where('provider', 'cloud')->where('phone_number_id', $phoneNumberId)->first()
            : null;

        if (! $number) {
            Log::warning('Meta webhook for unknown phone_number_id', ['phone_number_id' => $phoneNumberId]);

            return;
        }

        $names = [];
        foreach ((array) ($value['contacts'] ?? []) as $contact) {
            $names[$contact['wa_id'] ?? ''] = $contact['profile']['name'] ?? null;
        }

        foreach ((array) ($value['messages'] ?? []) as $message) {
            $this->handleMessage($number, $message, $names[$message['from'] ?? ''] ?? null);
        }

        foreach ((array) ($value['statuses'] ?? []) as $status) {
            $this->logStatus($number, $status);
        }
    }

    protected function handleMessage(WhatsappNumber $number, array $message, ?string $profileName): void
    {
        $from = (string) ($message['from'] ?? '');
        $messageId = $message['id'] ?? null;
        $type = $message['type'] ?? null;

        if ($from === '' || ! $messageId) {
            return;
        }

        $text = null;
        $base64 = $mime = $filename = null;

        if ($type === 'text') {
            $text = $message['text']['body'] ?? null;
        } elseif (in_array($type, self::MEDIA_TYPES, true)) {
            $media = (array) ($message[$type] ?? []);
            $text = $media['caption'] ?? null;

            $downloaded = ! empty($media['id']) ? $this->cloud->downloadMedia($number, $media['id']) : null;
            if ($downloaded) {
                $base64 = $downloaded['base64'];
                $mime = $downloaded['mime'];
                $filename = $media['filename'] ?? $downloaded['filename'];
            }
        } else {
            Log::info('Meta webhook: unsupported message type ignored', ['type' => $type, 'number_id' => $number->id]);

            return;
        }

        if (($text === null || $text === '') && ! $base64) {
            return;
        }

        $result = $this->inbound->handle($number, $from, $text, $messageId, $base64, $mime, $filename);

        if (($result['skipped'] ?? null) === 'duplicate') {
            return;
        }

        $this->recordInbound($number, $from, $profileName);

        try {
            $this->cloud->markAsRead($number, $messageId);
        } catch (\Throwable $e) {
            Log::debug('Meta markAsRead failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Keeps the 24h window (and contact name) current for conversations that already exist
     * or were just created by a command; the AI reply job manages the rest.
     */
    protected function recordInbound(WhatsappNumber $number, string $from, ?string $profileName): void
    {
        $conversation = Conversation::where('whatsapp_number_id', $number->id)
            ->where('contact_phone', $from)
            ->first();

        if (! $conversation) {
            return;
        }

        $conversation->last_customer_message_at = now();
        if ($profileName && ! $conversation->contact_name) {
            $conversation->contact_name = $profileName;
        }
        $conversation->save();
    }

    protected function logStatus(WhatsappNumber $number, array $status): void
    {
        $context = [
            'number_id' => $number->id,
            'message_id' => $status['id'] ?? null,
            'status' => $status['status'] ?? null,
        ];

        if (($status['status'] ?? null) === 'failed') {
            $error = $status['errors'][0] ?? [];
            Log::warning('Meta message delivery failed', $context + [
                'code' => $error['code'] ?? null,
                'title' => $error['title'] ?? null,
            ]);

            return;
        }

        Log::debug('Meta message status', $context);
    }

    protected function handleAccountChange(?string $wabaId, array $value): void
    {
        $query = WhatsappNumber::where('provider', 'cloud');

        if (! empty($value['metadata']['phone_number_id'])) {
            $query->where('phone_number_id', $value['metadata']['phone_number_id']);
        } elseif ($wabaId) {
            $query->where('waba_id', $wabaId);
        } else {
            return;
        }

        $display = preg_replace('/\D/', '', (string) ($value['display_phone_number'] ?? $value['phone_number'] ?? ''));

        $number = $query->get()->first(
            fn (WhatsappNumber $n) => $display === '' || preg_replace('/\D/', '', (string) $n->display_phone_number) === $display
        );

        if (! $number) {
            return;
        }

        $updates = array_filter([
            'quality_rating' => $value['quality_rating'] ?? null,
            'messaging_limit' => $value['current_limit'] ?? null,
        ]);

        if ($updates !== []) {
            $number->update($updates);
        }
    }

    protected function handleTemplateStatusUpdate(?string $wabaId, array $value): void
    {
        $name = $value['message_template_name'] ?? null;
        $language = $value['message_template_language'] ?? null;
        $metaId = isset($value['message_template_id']) ? (string) $value['message_template_id'] : null;

        $template = null;
        if ($wabaId && $name && $language) {
            $template = WhatsappTemplate::where('waba_id', $wabaId)
                ->where('name', $name)
                ->where('language', $language)
                ->first();
        }
        if (! $template && $metaId) {
            $template = WhatsappTemplate::where('meta_template_id', $metaId)->first();
        }

        if (! $template) {
            Log::info('Meta template status update for unknown template', [
                'waba_id' => $wabaId,
                'name' => $name,
                'language' => $language,
            ]);

            return;
        }

        $event = strtoupper((string) ($value['event'] ?? ''));
        if ($event === '') {
            return;
        }

        $status = $event === 'FLAGGED' ? 'paused' : strtolower($event);
        $reason = $value['reason'] ?? null;

        $template->update([
            'status' => $status,
            'meta_template_id' => $metaId ?: $template->meta_template_id,
            'rejection_reason' => ($reason === null || $reason === '' || strtoupper((string) $reason) === 'NONE') ? null : (string) $reason,
        ]);
    }
}
