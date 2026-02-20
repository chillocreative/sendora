<?php

namespace App\Services;

use App\Http\Middleware\CheckSubscriptionLimits;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\Playbook;
use App\Models\Setting;
use App\Models\WhatsappNumber;
use Illuminate\Support\Facades\Log;

class AiReplyService
{
    protected OpenAiService $openAi;
    protected WhatsappService $whatsapp;

    public function __construct(OpenAiService $openAi, WhatsappService $whatsapp)
    {
        $this->openAi = $openAi;
        $this->whatsapp = $whatsapp;
    }

    /**
     * Process an inbound WhatsApp message and generate an AI reply.
     *
     * @return array{replied: bool, reason: string, conversation_id: int|null}
     */
    public function processInboundMessage(
        WhatsappNumber $whatsappNumber,
        string $contactPhone,
        string $messageText,
        ?string $waMessageId = null
    ): array {
        // -- Guard 1 & 2: Resolve playbook (direct or fallback) --
        $playbook = $whatsappNumber->playbook;

        if (!$playbook || !$playbook->is_active) {
            $fallbackPlaybook = Playbook::where('user_id', $whatsappNumber->user_id)
                ->where('is_active', true)
                ->first();

            if ($fallbackPlaybook) {
                Log::info('AI fallback playbook assigned', [
                    'whatsapp_number_id' => $whatsappNumber->id,
                    'playbook_id' => $fallbackPlaybook->id,
                ]);
                $whatsappNumber->update([
                    'playbook_id' => $fallbackPlaybook->id,
                    'ai_reply_enabled' => true,
                ]);
                $whatsappNumber->refresh();
                $playbook = $fallbackPlaybook;
            } else {
                return ['replied' => false, 'reason' => 'no_active_playbook', 'conversation_id' => null];
            }
        }

        // Auto-enable AI reply if playbook is assigned but AI was off
        if (!$whatsappNumber->ai_reply_enabled) {
            $whatsappNumber->update(['ai_reply_enabled' => true]);
        }

        // -- Guard 3: AI provider configured? --
        if (!$this->openAi->isConfigured($playbook->model ?? 'gpt-4o')) {
            return ['replied' => false, 'reason' => 'ai_not_configured', 'conversation_id' => null];
        }

        // -- Guard 4: Global AI kill switch --
        $globalEnabled = Setting::where('key', 'ai_reply_enabled')->value('value') ?? '1';
        if ($globalEnabled === '0') {
            return ['replied' => false, 'reason' => 'ai_globally_disabled', 'conversation_id' => null];
        }

        // -- Guard 5: Subscription feature check --
        $user = $whatsappNumber->user;
        if (!CheckSubscriptionLimits::hasFeature($user, 'auto_reply')) {
            return ['replied' => false, 'reason' => 'subscription_no_ai_reply', 'conversation_id' => null];
        }

        // -- Step 1: Get or create conversation --
        $cleanPhone = $this->cleanPhone($contactPhone);
        $conversation = Conversation::firstOrCreate(
            [
                'whatsapp_number_id' => $whatsappNumber->id,
                'contact_phone' => $cleanPhone,
            ],
            [
                'user_id' => $whatsappNumber->user_id,
                'status' => 'active',
                'contact_name' => $this->resolveContactName($whatsappNumber->user_id, $cleanPhone),
                'contact_jid' => $contactPhone,
            ]
        );

        // Update JID on existing conversations
        if (!$conversation->wasRecentlyCreated && $conversation->contact_jid !== $contactPhone) {
            $conversation->update(['contact_jid' => $contactPhone]);
        }

        // -- Step 2: Store inbound message (with deduplication) --
        if ($waMessageId) {
            $existingMsg = ConversationMessage::where('wa_message_id', $waMessageId)->first();
            if ($existingMsg) {
                Log::info('Duplicate message skipped', ['wa_message_id' => $waMessageId]);
                return ['replied' => false, 'reason' => 'duplicate_message', 'conversation_id' => $conversation->id];
            }
        }

        ConversationMessage::create([
            'conversation_id' => $conversation->id,
            'direction' => 'inbound',
            'sender_type' => 'customer',
            'body' => $messageText,
            'wa_message_id' => $waMessageId,
        ]);

        $conversation->increment('message_count');
        $conversation->update(['last_customer_message_at' => now()]);

        // -- Step 3: Check conversation status --
        if ($conversation->status === 'paused') {
            return ['replied' => false, 'reason' => 'conversation_paused', 'conversation_id' => $conversation->id];
        }

        // Reactivate closed conversations when customer messages again
        if ($conversation->status === 'closed') {
            $conversation->update(['status' => 'active']);
        }

        // Fetch conversation history for context injection
        $history = $conversation->latestMessages(8);
        $recentAiMsgs = $this->getRecentAiMessages($history, 3);

        if ($this->detectLoop($recentAiMsgs)) {
            Log::warning('Loop detected — context reset will be injected into prompt', [
                'conversation_id' => $conversation->id,
            ]);
        }

        // -- Step 4: Build prompt --
        $messages = $this->buildPrompt($playbook, $conversation);

        // -- Step 5: Call OpenAI --
        $result = $this->openAi->chatCompletion(
            $messages,
            $playbook->model ?? 'gpt-4o',
            (float) ($playbook->temperature ?? 0.7),
            (int) ($playbook->max_tokens ?? 500),
            true // JSON mode
        );

        if (!$result['success']) {
            Log::error('AI Reply failed', [
                'conversation_id' => $conversation->id,
                'error' => $result['error'],
            ]);
            return ['replied' => false, 'reason' => 'openai_error: ' . $result['error'], 'conversation_id' => $conversation->id];
        }

        if (empty($result['content'])) {
            Log::error('AI Reply returned empty content', ['conversation_id' => $conversation->id]);
            return ['replied' => false, 'reason' => 'openai_empty_response', 'conversation_id' => $conversation->id];
        }

        // -- Step 6: Parse response --
        $parsed = $this->parseAiResponse($result['content']);

        // Quality check: log high similarity (informational only — AI always replies)
        $lastAiMsg = $this->getLastAiMessage($history);
        if ($lastAiMsg !== null) {
            similar_text(strtolower($parsed['reply']), strtolower($lastAiMsg), $similarity);
            if ($similarity > 80) {
                Log::warning('High similarity in AI reply — possible repetition', [
                    'conversation_id' => $conversation->id,
                    'similarity' => round($similarity) . '%',
                ]);
            }
        }

        // -- Step 7: Send AI reply (AI always handles everything — no escalation) --
        $sendResult = $this->whatsapp->sendMessage($whatsappNumber, $contactPhone, $parsed['reply']);

        $outboundWaMessageId = null;
        $sendFailed = true;
        if ($sendResult && $sendResult->successful()) {
            $outboundWaMessageId = $sendResult->json('message_id');
            $sendFailed = false;
        } else {
            Log::error('AI reply WhatsApp send failed', [
                'conversation_id' => $conversation->id,
                'contact_phone' => $contactPhone,
            ]);
        }

        ConversationMessage::create([
            'conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'sender_type' => 'ai',
            'body' => $parsed['reply'],
            'wa_message_id' => $outboundWaMessageId,
            'confidence_score' => $parsed['confidence'],
            'reasoning_source' => $parsed['reasoning_source'],
            'prompt_tokens' => $result['usage']['prompt_tokens'] ?? null,
            'completion_tokens' => $result['usage']['completion_tokens'] ?? null,
            'total_tokens' => $result['usage']['total_tokens'] ?? null,
            'latency_ms' => $result['latency_ms'],
        ]);

        $conversation->increment('message_count');
        $conversation->update(['last_ai_reply_at' => now()]);

        if ($sendFailed) {
            return ['replied' => false, 'reason' => 'whatsapp_send_failed', 'conversation_id' => $conversation->id];
        }

        return ['replied' => true, 'reason' => 'ai_reply_sent', 'conversation_id' => $conversation->id];
    }

    /**
     * Build the OpenAI messages array from playbook + conversation history.
     */
    protected function buildPrompt(Playbook $playbook, Conversation $conversation): array
    {
        $systemPrompt = $this->buildSystemPrompt($playbook);

        $history = $conversation->latestMessages(8);

        Log::debug('AI buildPrompt', [
            'conversation_id' => $conversation->id,
            'history_count' => $history->count(),
        ]);

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        // Loop detection: inject a context reset if AI is repeating itself
        $recentAiMessages = $this->getRecentAiMessages($history, 3);
        $isLooping = $this->detectLoop($recentAiMessages);

        // Extract questions already asked and information already provided
        $askedQuestions = $this->extractAskedQuestions($history);
        $customerInfo = $this->extractCustomerInfo($history);

        if (!empty($askedQuestions) || !empty($customerInfo) || $isLooping) {
            $reminder = "🚨 ANTI-REPETITION CHECK 🚨\n\n";

            if ($isLooping) {
                $reminder .= "🔴🔴🔴 LOOP DETECTED — CHANGE YOUR APPROACH NOW! 🔴🔴🔴\n";
                $reminder .= "YOU HAVE BEEN ASKING THE SAME QUESTION REPEATEDLY!\n";
                $reminder .= "THE CUSTOMER ALREADY RESPONDED — STOP REPEATING YOURSELF!\n\n";
                $reminder .= "YOU MUST DO ONE OF THESE RIGHT NOW:\n";
                $reminder .= "  1. Acknowledge what the customer said and MOVE TO THE NEXT STEP in the playbook\n";
                $reminder .= "  2. Ask a completely DIFFERENT question to progress the conversation forward\n";
                $reminder .= "  3. Provide helpful information and invite the customer to share what they need\n\n";
                $reminder .= "DO NOT REPEAT YOURSELF. DO NOT ASK THE SAME QUESTION. MOVE FORWARD!\n\n";
            }

            if (!empty($askedQuestions)) {
                $reminder .= "❌ FORBIDDEN — DO NOT ASK THESE AGAIN:\n";
                foreach ($askedQuestions as $q) {
                    $reminder .= "   ✗ {$q}\n";
                }
                $reminder .= "\n";
            }

            if (!empty($customerInfo)) {
                $reminder .= "✅ CUSTOMER ALREADY TOLD YOU:\n";
                foreach ($customerInfo as $key => $value) {
                    $reminder .= "   • {$key}: {$value}\n";
                }
                $reminder .= "\n";
            }

            $reminder .= "➡️ ACTION: Acknowledge their answer and ask something NEW. Move the conversation forward!\n";

            $messages[] = ['role' => 'system', 'content' => $reminder];
        }

        // Add conversation history
        foreach ($history as $msg) {
            if ($msg->direction === 'inbound') {
                $messages[] = ['role' => 'user', 'content' => $msg->body];
            } else {
                $messages[] = ['role' => 'assistant', 'content' => $msg->body];
            }
        }

        return $messages;
    }

    /**
     * Get recent AI messages for loop detection.
     */
    protected function getRecentAiMessages($history, int $count = 3): array
    {
        $aiMessages = [];
        foreach ($history as $msg) {
            if ($msg->direction === 'outbound') {
                $aiMessages[] = $msg->body;
            }
        }
        return array_slice($aiMessages, -$count);
    }

    /**
     * Get the body of the most recent outbound (AI) message from history.
     */
    protected function getLastAiMessage($history): ?string
    {
        $last = null;
        foreach ($history as $msg) {
            if ($msg->direction === 'outbound') {
                $last = $msg->body;
            }
        }
        return $last;
    }

    /**
     * Detect if AI is stuck in a loop (asking same/similar questions repeatedly).
     */
    protected function detectLoop($recentMessages): bool
    {
        if (count($recentMessages) < 2) {
            return false;
        }

        $last = strtolower($recentMessages[count($recentMessages) - 1]);
        $secondLast = strtolower($recentMessages[count($recentMessages) - 2]);

        if ($last === $secondLast) {
            Log::warning('Loop detected: exact duplicate message');
            return true;
        }

        similar_text($last, $secondLast, $percent);
        if ($percent > 55) {
            Log::warning('Loop detected: messages are ' . round($percent) . '% similar');
            return true;
        }

        // Topic-based loop: 2+ messages asking about the same topic
        $allTopics = [];
        foreach ($recentMessages as $msg) {
            foreach ($this->extractQuestionTopics($msg) as $topic) {
                $allTopics[] = $topic;
            }
        }

        if (count($allTopics) >= 2) {
            $topicCounts = array_count_values($allTopics);
            foreach ($topicCounts as $topic => $count) {
                if ($count >= 2) {
                    Log::warning('Loop detected: repeated topic — ' . $topic);
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Extract question topics from an AI message.
     */
    protected function extractQuestionTopics(string $message): array
    {
        $topics = [];
        $lower = strtolower($message);

        if (preg_match('/pernah.*(marketing|iklan|promosi|ads)|marketing.*sebelum|buat iklan|cuba.*marketing|pengalaman.*marketing/i', $lower)) {
            $topics[] = 'marketing_experience';
        }
        if (preg_match('/jenis bisnes|what business|bisnes apa|type.*business|perniagaan apa|bisnes.*apa/i', $lower)) {
            $topics[] = 'business_type';
        }
        if (preg_match('/online.*offline|offline.*online|cara.*jual|lebih kepada online|channel|platform|jual.*mana/i', $lower)) {
            $topics[] = 'business_model';
        }
        if (preg_match('/nama.*apa|what.*name|boleh.*tahu.*nama|siapa nama|boleh saya tahu nama/i', $lower)) {
            $topics[] = 'name';
        }
        if (preg_match('/cabaran|challenge|masalah|problem|kesukaran/i', $lower)) {
            $topics[] = 'challenges';
        }
        if (preg_match('/bajet|budget|berapa.*belanja|modal|kos/i', $lower)) {
            $topics[] = 'budget';
        }

        return $topics;
    }

    /**
     * Get topics that were asked by AI AND answered by the customer.
     */
    protected function getAnsweredTopics($history): array
    {
        $answeredTopics = [];
        $pendingTopics = [];

        foreach ($history as $msg) {
            if ($msg->direction === 'outbound') {
                $pendingTopics = $this->extractQuestionTopics($msg->body);
            } elseif ($msg->direction === 'inbound' && !empty($pendingTopics)) {
                $answeredTopics = array_merge($answeredTopics, $pendingTopics);
                $pendingTopics = [];
            }
        }

        return array_unique($answeredTopics);
    }

    /**
     * Extract questions AI has already asked.
     */
    protected function extractAskedQuestions($history): array
    {
        $questions = [];
        $topicLabels = [
            'marketing_experience' => 'Have you done marketing before?',
            'business_type' => 'What business do you do?',
            'business_model' => 'Is your business online or offline?',
            'name' => 'What is your name?',
            'challenges' => 'What challenges do you face?',
            'budget' => 'What is your budget?',
        ];

        $seenTopics = [];
        foreach ($history as $msg) {
            if ($msg->direction === 'outbound') {
                foreach ($this->extractQuestionTopics($msg->body) as $topic) {
                    if (!isset($seenTopics[$topic]) && isset($topicLabels[$topic])) {
                        $questions[] = $topicLabels[$topic];
                        $seenTopics[$topic] = true;
                    }
                }
            }
        }

        return $questions;
    }

    /**
     * Extract information the customer has already provided (context-aware).
     */
    protected function extractCustomerInfo($history): array
    {
        $info = [];
        $topicToInfoKey = [
            'marketing_experience' => 'Marketing History',
            'business_type' => 'Business/Product',
            'business_model' => 'Business Model',
            'name' => 'Name',
            'challenges' => 'Challenges',
            'budget' => 'Budget',
        ];

        $lastAiTopics = [];

        foreach ($history as $msg) {
            if ($msg->direction === 'outbound') {
                $lastAiTopics = $this->extractQuestionTopics($msg->body);
            } elseif ($msg->direction === 'inbound') {
                $text = $msg->body;
                $textLower = strtolower($text);

                foreach ($lastAiTopics as $topic) {
                    $infoKey = $topicToInfoKey[$topic] ?? null;
                    if ($infoKey && !isset($info[$infoKey])) {
                        $info[$infoKey] = $text;
                    }
                }
                $lastAiTopics = [];

                if (preg_match('/bisnes|business|jual|sell|kedai|shop|produk|product|kuih|cake|makanan|food/i', $textLower)) {
                    if (!isset($info['Business/Product'])) {
                        $info['Business/Product'] = $text;
                    }
                }

                if (preg_match('/^(online|offline)$/i', trim($textLower))) {
                    if (!isset($info['Business Model'])) {
                        $info['Business Model'] = $text;
                    }
                }
            }
        }

        return $info;
    }

    /**
     * Build the system prompt from playbook content.
     */
    protected function buildSystemPrompt(Playbook $playbook): string
    {
        $sanitizedContent = PlaybookSanitizer::sanitize($playbook->content);

        return <<<PROMPT
You are an AI assistant for a WhatsApp business account. You must follow the playbook below exactly.

=== PLAYBOOK START ===
{$sanitizedContent}
=== PLAYBOOK END ===

=== RESPONSE FORMAT ===
You MUST respond in the following JSON format only. Do NOT include any text outside the JSON:
{
  "reply": "Your message to the customer",
  "confidence": 0.95,
  "reasoning_source": "Section name from playbook you referenced",
  "escalate": false,
  "escalation_reason": null
}

⚠️ CRITICAL RESPONSE LENGTH RULES:
- Keep replies SHORT — maximum 2-3 short sentences
- This is WhatsApp, not email — be concise!
- One question per message maximum
- Avoid long explanations
- Get to the point quickly

GOOD (short):
"Baik Tuan 😊 Boleh tahu jenis bisnes?"

BAD (too long):
"Baik Tuan/Puan 😊 Untuk makluman, team kami biasanya bantu bisnes dari segi lead & sistem follow-up supaya lebih konsisten. Tapi setiap bisnes lain keperluan dia, sebab tu kami suka faham dulu. Boleh saya tahu jenis bisnes yang Tuan/Puan jalankan supaya kami boleh cadangkan yang terbaik?"

=== CONVERSATION PROGRESSION RULES ===
🚨 CRITICAL: You MUST read and acknowledge ALL customer answers. Never ask a question the customer already answered.

BEFORE every reply, review the FULL conversation history and identify:
1. What information has the customer ALREADY provided?
2. What questions have you ALREADY asked?
3. What is the customer's LATEST message asking or saying?

PROGRESSION RULES:
✅ DO: Acknowledge customer's answer → Progress to next step
❌ DON'T: Repeat questions → Ignore customer's answers → Loop back

EXAMPLES OF WHAT NOT TO DO:
- Customer says "I sell cookies" → You ask "What business do you do?" ❌ WRONG!
- Customer says "Yes" → You ask the same question again ❌ WRONG!
- Customer gives their name → You ask for their name ❌ WRONG!

EXAMPLES OF CORRECT BEHAVIOR:
- Customer says "I sell cookies" → "Great! Cookie business. How long have you been selling?" ✅ CORRECT!
- Customer says "Yes" → Continue to the next step in the playbook ✅ CORRECT!
- Customer gives answer → Acknowledge it and move forward ✅ CORRECT!

⚠️ IF YOU CATCH YOURSELF ASKING THE SAME QUESTION TWICE, STOP! The customer already answered. Move forward.

=== RULES ===
1. "reply" is the actual text message to send to the customer via WhatsApp. Keep it natural, concise, and conversational.
2. "confidence" is a float from 0.0 to 1.0 indicating how confident you are that your reply correctly follows the playbook.
   - 0.9-1.0: Answer directly addressed in the playbook
   - 0.7-0.9: Answer can be inferred from playbook context
   - 0.5-0.7: Answer requires minor extrapolation
   - Below 0.5: Ask a clarifying question to better understand the customer's need
3. "reasoning_source" is the specific section title or area of the playbook you used to craft this response.
4. "escalate" should always be false. You handle ALL situations yourself. You are always available to the customer.
5. If a customer asks to speak to a human, acknowledge them warmly and continue helping: empathize and redirect the conversation naturally. Never stop helping.
6. "escalation_reason" should always be null.
7. Never make up information not in the playbook. If unsure, ask the customer a clarifying question.
8. Never share pricing, promotions, or policies not explicitly stated in the playbook.
9. If you are unsure about something, ask the customer for clarification to better serve them. Keep the conversation going.
10. Keep replies WhatsApp-appropriate: short paragraphs, no markdown formatting, no bullet points unless the customer asked for a list.
11. This is a multi-turn conversation. NEVER repeat greetings or questions you've already asked. Read the full conversation history and respond contextually.
PROMPT;
    }

    /**
     * Parse the structured JSON AI response. Falls back gracefully on parse failure.
     */
    protected function parseAiResponse(string $rawContent): array
    {
        $default = [
            'reply' => '',
            'confidence' => 0.0,
            'reasoning_source' => 'unknown',
            'escalate' => false,
            'escalation_reason' => null,
        ];

        $cleaned = trim($rawContent);

        if (str_starts_with($cleaned, '```')) {
            $cleaned = preg_replace('/^```(?:json)?\n?/', '', $cleaned);
            $cleaned = preg_replace('/\n?```$/', '', $cleaned);
        }

        $parsed = json_decode($cleaned, true);

        if (!is_array($parsed) || empty($parsed['reply'])) {
            Log::warning('AI response JSON parse failed, using raw content', [
                'raw' => substr($rawContent, 0, 500),
            ]);
            return array_merge($default, [
                'reply' => $rawContent,
                'confidence' => 0.3,
                'reasoning_source' => 'parse_fallback',
            ]);
        }

        return [
            'reply' => $parsed['reply'],
            'confidence' => (float) ($parsed['confidence'] ?? 0.5),
            'reasoning_source' => $parsed['reasoning_source'] ?? 'not_specified',
            'escalate' => false, // Always false — AI never escalates
            'escalation_reason' => null,
        ];
    }

    /**
     * Clean phone number: strip WhatsApp JID suffix and non-digit chars.
     */
    protected function cleanPhone(string $phone): string
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

    /**
     * Try to resolve a contact name from the contacts table.
     */
    protected function resolveContactName(int $userId, string $phone): ?string
    {
        return Contact::where('user_id', $userId)
            ->where('phone_number', $phone)
            ->value('name');
    }
}
