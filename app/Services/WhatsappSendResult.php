<?php

namespace App\Services;

use Illuminate\Http\Client\Response;

/**
 * Outcome of a WhatsApp Cloud API call. Mirrors the subset of Http\Client\Response
 * that WhatsappService callers rely on (successful, json, status, body).
 */
class WhatsappSendResult
{
    public function __construct(
        protected bool $successful,
        protected int $status = 0,
        protected array $data = [],
        protected ?string $error = null,
    ) {}

    public static function fromResponse(Response $response): self
    {
        $data = $response->json() ?? [];
        $error = null;

        if (! $response->successful()) {
            $message = data_get($data, 'error.message', 'HTTP '.$response->status());
            $code = data_get($data, 'error.code');
            $error = $code ? "({$code}) {$message}" : $message;
        }

        return new self($response->successful(), $response->status(), $data, $error);
    }

    public static function failed(string $error, int $status = 0): self
    {
        return new self(false, $status, [], $error);
    }

    public function successful(): bool
    {
        return $this->successful;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function error(): ?string
    {
        return $this->error;
    }

    /**
     * 'message_id' maps to messages[0].id; any other key is read with dot notation.
     */
    public function json(?string $key = null): mixed
    {
        if ($key === null) {
            return $this->data;
        }

        if ($key === 'message_id') {
            return data_get($this->data, 'messages.0.id');
        }

        return data_get($this->data, $key);
    }

    public function body(): string
    {
        return $this->error ?? (string) json_encode($this->data);
    }
}
