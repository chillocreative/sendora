<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\WhatsappNumber;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mime\MimeTypes;

class WhatsappCloudService
{
    public function sendText(WhatsappNumber $number, string $to, string $text): WhatsappSendResult
    {
        $to = $this->normalizePhone($to);

        if ($blocked = $this->guardWindow($number, $to)) {
            return $blocked;
        }

        return $this->postMessage($number, [
            'to' => $to,
            'type' => 'text',
            'text' => ['body' => $text, 'preview_url' => false],
        ]);
    }

    /**
     * Send media by public link ($mediaUrl) or by uploading $mediaBase64 first.
     * $type is image|video|audio|document, or a mime type to derive it from.
     */
    public function sendMedia(
        WhatsappNumber $number,
        string $to,
        ?string $type,
        ?string $mediaUrl = null,
        ?string $mediaBase64 = null,
        ?string $filename = null,
        ?string $caption = null,
    ): WhatsappSendResult {
        $to = $this->normalizePhone($to);

        if ($blocked = $this->guardWindow($number, $to)) {
            return $blocked;
        }

        $mime = $type && str_contains($type, '/') ? $type : null;
        $kind = $this->mediaKind($type);

        if ($mediaBase64) {
            $upload = $this->uploadMedia($number, $mediaBase64, $mime ?? 'application/octet-stream', $filename ?? 'file');
            if (! $upload->successful()) {
                return $upload;
            }
            $media = ['id' => $upload->json('id')];
        } elseif ($mediaUrl) {
            $media = ['link' => $mediaUrl];
        } else {
            return WhatsappSendResult::failed('missing_media');
        }

        if ($caption !== null && $caption !== '' && $kind !== 'audio') {
            $media['caption'] = $caption;
        }
        if ($kind === 'document' && $filename) {
            $media['filename'] = $filename;
        }

        return $this->postMessage($number, [
            'to' => $to,
            'type' => $kind,
            $kind => $media,
        ]);
    }

    /**
     * Templates are allowed outside the 24h customer-service window.
     */
    public function sendTemplate(WhatsappNumber $number, string $to, string $name, string $language, array $components = []): WhatsappSendResult
    {
        $template = ['name' => $name, 'language' => ['code' => $language]];
        if ($components !== []) {
            $template['components'] = $components;
        }

        return $this->postMessage($number, [
            'to' => $this->normalizePhone($to),
            'type' => 'template',
            'template' => $template,
        ]);
    }

    public function markAsRead(WhatsappNumber $number, string $waMessageId): WhatsappSendResult
    {
        return $this->postMessage($number, [
            'status' => 'read',
            'message_id' => $waMessageId,
        ]);
    }

    /**
     * @return array{base64: string, mime: string, filename: string}|null
     */
    public function downloadMedia(WhatsappNumber $number, string $mediaId): ?array
    {
        try {
            $meta = $this->client($number)->get($this->graphUrl($mediaId));
            if (! $meta->successful() || ! $meta->json('url')) {
                $this->logFailure('media lookup', $number, WhatsappSendResult::fromResponse($meta));

                return null;
            }

            $file = $this->client($number)->timeout(60)->get($meta->json('url'));
            if (! $file->successful()) {
                $this->logFailure('media download', $number, WhatsappSendResult::fromResponse($file));

                return null;
            }
        } catch (ConnectionException $e) {
            Log::warning('WhatsappCloudService media download connection error', [
                'number_id' => $number->id,
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        $mime = $meta->json('mime_type') ?: 'application/octet-stream';
        $extension = MimeTypes::getDefault()->getExtensions($mime)[0] ?? 'bin';

        return [
            'base64' => base64_encode($file->body()),
            'mime' => $mime,
            'filename' => "{$mediaId}.{$extension}",
        ];
    }

    /**
     * Look up a phone number on the Graph API to verify credentials.
     * Returns the decoded payload, or null when Meta rejects the request.
     * The access token is never logged.
     */
    public function fetchPhoneNumber(string $phoneNumberId, string $accessToken): ?array
    {
        try {
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->timeout(30)
                ->get($this->graphUrl($phoneNumberId), [
                    'fields' => 'display_phone_number,verified_name,quality_rating,messaging_limit_tier',
                ]);
        } catch (ConnectionException $e) {
            Log::warning('WhatsappCloudService phone number lookup connection error', [
                'phone_number_id' => $phoneNumberId,
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('WhatsappCloudService phone number lookup failed', [
                'phone_number_id' => $phoneNumberId,
                'status' => $response->status(),
                'error' => $response->json('error.message'),
            ]);

            return null;
        }

        $data = $response->json();

        return is_array($data) ? $data : null;
    }

    /**
     * Digits only; a leading 0 is replaced by the configured default country code.
     */
    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', preg_replace('/@.*$/', '', $phone));

        if (str_starts_with($digits, '0')) {
            $digits = config('services.meta.default_country_code', '60').substr($digits, 1);
        }

        return $digits;
    }

    /**
     * Free-form messages are only deliverable inside 24h of the customer's last message.
     * Unknown recipients (no conversation yet) are left for Meta to judge.
     */
    protected function guardWindow(WhatsappNumber $number, string $to): ?WhatsappSendResult
    {
        $conversation = Conversation::where('whatsapp_number_id', $number->id)
            ->where('contact_phone', $to)
            ->first();

        if ($conversation && ! $conversation->isWithin24HourWindow()) {
            return WhatsappSendResult::failed('outside_24h_window');
        }

        return null;
    }

    protected function uploadMedia(WhatsappNumber $number, string $base64, string $mime, string $filename): WhatsappSendResult
    {
        if (! $this->isConfigured($number)) {
            return WhatsappSendResult::failed('cloud_number_not_configured');
        }

        try {
            $response = $this->client($number)
                ->attach('file', base64_decode($base64), $filename, ['Content-Type' => $mime])
                ->post($this->graphUrl("{$number->phone_number_id}/media"), [
                    'messaging_product' => 'whatsapp',
                    'type' => $mime,
                ]);
        } catch (ConnectionException $e) {
            return $this->connectionFailure('media upload', $number, $e);
        }

        $result = WhatsappSendResult::fromResponse($response);
        if (! $result->successful()) {
            $this->logFailure('media upload', $number, $result);
        }

        return $result;
    }

    protected function postMessage(WhatsappNumber $number, array $payload): WhatsappSendResult
    {
        if (! $this->isConfigured($number)) {
            return WhatsappSendResult::failed('cloud_number_not_configured');
        }

        $payload = ['messaging_product' => 'whatsapp', ...$payload];

        try {
            $response = $this->client($number)->post($this->graphUrl("{$number->phone_number_id}/messages"), $payload);
        } catch (ConnectionException $e) {
            return $this->connectionFailure('send', $number, $e);
        }

        $result = WhatsappSendResult::fromResponse($response);
        if (! $result->successful()) {
            $this->logFailure('send', $number, $result);
        }

        return $result;
    }

    protected function isConfigured(WhatsappNumber $number): bool
    {
        return $number->phone_number_id && $number->access_token;
    }

    /**
     * Retries only on 5xx responses.
     */
    protected function client(WhatsappNumber $number): PendingRequest
    {
        return Http::withToken($number->access_token)
            ->acceptJson()
            ->timeout(30)
            ->retry(
                2,
                500,
                fn ($exception) => $exception instanceof RequestException && $exception->response->serverError(),
                throw: false,
            );
    }

    protected function graphUrl(string $path): string
    {
        $base = rtrim(config('services.meta.graph_base_url'), '/');
        $version = config('services.meta.graph_version');

        return "{$base}/{$version}/{$path}";
    }

    protected function mediaKind(?string $type): string
    {
        $type = strtolower((string) $type);

        return match (true) {
            in_array($type, ['image', 'video', 'audio', 'document'], true) => $type,
            str_starts_with($type, 'image/') => 'image',
            str_starts_with($type, 'video/') => 'video',
            str_starts_with($type, 'audio/') => 'audio',
            default => 'document',
        };
    }

    protected function connectionFailure(string $action, WhatsappNumber $number, ConnectionException $e): WhatsappSendResult
    {
        Log::error("WhatsappCloudService {$action} connection error", [
            'number_id' => $number->id,
            'message' => $e->getMessage(),
        ]);

        return WhatsappSendResult::failed('connection_error');
    }

    protected function logFailure(string $action, WhatsappNumber $number, WhatsappSendResult $result): void
    {
        Log::error("WhatsappCloudService {$action} failed", [
            'number_id' => $number->id,
            'status' => $result->status(),
            'error' => $result->error(),
        ]);
    }
}
