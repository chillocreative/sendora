<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiService
{
    protected string $openAiKey;
    protected string $deepSeekKey;

    protected const PROVIDER_URLS = [
        'openai' => 'https://api.openai.com/v1/chat/completions',
        'deepseek' => 'https://api.deepseek.com/chat/completions',
    ];

    public function __construct()
    {
        $this->openAiKey = Setting::where('key', 'openai_api_key')->value('value') ?? '';
        $this->deepSeekKey = Setting::where('key', 'deepseek_api_key')->value('value') ?? '';
    }

    /**
     * Resolve which provider to use based on the model name.
     */
    protected function resolveProvider(string $model): string
    {
        return str_starts_with($model, 'deepseek-') ? 'deepseek' : 'openai';
    }

    /**
     * Get the API key for a given provider.
     */
    protected function apiKeyFor(string $provider): string
    {
        return $provider === 'deepseek' ? $this->deepSeekKey : $this->openAiKey;
    }

    public function isConfigured(?string $model = null): bool
    {
        $provider = $model ? $this->resolveProvider($model) : 'openai';
        return !empty($this->apiKeyFor($provider));
    }

    /**
     * Send a Chat Completions request to OpenAI.
     *
     * @return array{success: bool, content: string|null, usage: array|null, error: string|null, latency_ms: int}
     */
    public function chatCompletion(
        array $messages,
        string $model = 'gpt-4o',
        float $temperature = 0.7,
        int $maxTokens = 500,
        bool $jsonMode = false
    ): array {
        $provider = $this->resolveProvider($model);
        $apiKey = $this->apiKeyFor($provider);
        $apiUrl = self::PROVIDER_URLS[$provider];

        if (empty($apiKey)) {
            return [
                'success' => false,
                'content' => null,
                'usage' => null,
                'error' => ucfirst($provider) . ' API key not configured',
                'latency_ms' => 0,
            ];
        }

        // deepseek-reasoner (R1) does not support temperature, response_format, or top_p
        $isReasonerModel = $model === 'deepseek-reasoner';

        $startTime = microtime(true);

        try {
            $payload = [
                'model' => $model,
                'messages' => $messages,
                'max_tokens' => $maxTokens,
            ];

            if (!$isReasonerModel) {
                $payload['temperature'] = $temperature;
                if ($jsonMode) {
                    $payload['response_format'] = ['type' => 'json_object'];
                }
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout($isReasonerModel ? 60 : 30)->post($apiUrl, $payload);

            $latencyMs = (int) round((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'content' => $data['choices'][0]['message']['content'] ?? null,
                    'usage' => $data['usage'] ?? null,
                    'error' => null,
                    'latency_ms' => $latencyMs,
                ];
            }

            Log::error($provider . ' API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'content' => null,
                'usage' => null,
                'error' => 'API returned status ' . $response->status(),
                'latency_ms' => $latencyMs,
            ];
        } catch (\Exception $e) {
            $latencyMs = (int) round((microtime(true) - $startTime) * 1000);
            Log::error($provider . ' exception', ['message' => $e->getMessage()]);

            return [
                'success' => false,
                'content' => null,
                'usage' => null,
                'error' => $e->getMessage(),
                'latency_ms' => $latencyMs,
            ];
        }
    }

    /**
     * Generate a realistic WhatsApp message for warming purposes.
     */
    public function generateWarmerMessage($context = null)
    {
        $messages = [
            [
                'role' => 'system',
                'content' => 'You are a regular person having a casual conversation on WhatsApp in Malaysia. Use casual English/Malay (Manglish). Keep messages short, realistic, and human-like. Avoid being too formal or too robotic. Do not use hashtags or emojis excessively.',
            ],
            [
                'role' => 'user',
                'content' => $context
                    ? "Reply to this message naturally: $context"
                    : 'Start a casual short conversation starter.',
            ],
        ];

        $result = $this->chatCompletion($messages, 'gpt-3.5-turbo', 0.8, 50);

        return $result['success']
            ? $result['content']
            : 'Hey, how are you doing today?';
    }
}
