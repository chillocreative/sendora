<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiService
{
    protected $apiKey;
    protected $apiUrl = 'https://api.openai.com/v1/chat/completions';

    public function __construct()
    {
        $this->apiKey = Setting::where('key', 'openai_api_key')->value('value');
    }

    /**
     * Generate a realistic WhatsApp message for warming purposes.
     */
    public function generateWarmerMessage($context = null)
    {
        if (!$this->apiKey) {
            Log::warning('OpenAI API Key not set. Cannot generate warmer message.');
            return "Hey, how are you doing today?"; // Fallback
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl, [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a regular person having a casual conversation on WhatsApp in Malaysia. Use casual English/Malay (Manglish). Keep messages short, realistic, and human-like. Avoid being too formal or too robotic. Do not use hashtags or emojis excessively.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $context ? "Reply to this message naturally: $context" : "Start a casual short conversation starter."
                    ]
                ],
                'max_tokens' => 50,
                'temperature' => 0.8,
            ]);

            if ($response->successful()) {
                return $response->json('choices.0.message.content');
            }

            Log::error('OpenAI Request failed: ' . $response->body());
            return "Hey, what's up?"; // Fallback
        } catch (\Exception $e) {
            Log::error('OpenAI Exception: ' . $e->getMessage());
            return "How's your day going?"; // Fallback
        }
    }
}
