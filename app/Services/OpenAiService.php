<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiService
{
    protected string $apiKey;
    protected string $apiUrl = 'https://api.openai.com/v1/chat/completions';

    public function __construct()
    {
        $this->apiKey = Setting::where('key', 'openai_api_key')->value('value') ?? '';
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
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
        int $maxTokens = 500
    ): array {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'content' => null,
                'usage' => null,
                'error' => 'OpenAI API key not configured',
                'latency_ms' => 0,
            ];
        }

        $startTime = microtime(true);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($this->apiUrl, [
                'model' => $model,
                'messages' => $messages,
                'max_tokens' => $maxTokens,
                'temperature' => $temperature,
            ]);

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

            Log::error('OpenAI API error', [
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
            Log::error('OpenAI exception', ['message' => $e->getMessage()]);

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
