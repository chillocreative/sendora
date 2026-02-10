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
        // -- Guard 1: AI reply enabled on this number? --
        if (!$whatsappNumber->ai_reply_enabled) {
            return ['replied' => false, 'reason' => 'ai_reply_disabled', 'conversation_id' => null];
        }

        // -- Guard 2: Playbook assigned and active? --
        $playbook = $whatsappNumber->playbook;
        if (!$playbook || !$playbook->is_active) {
            return ['replied' => false, 'reason' => 'no_active_playbook', 'conversation_id' => null];
        }

        // -- Guard 3: OpenAI configured? --
        if (!$this->openAi->isConfigured()) {
            return ['replied' => false, 'reason' => 'openai_not_configured', 'conversation_id' => null];
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

        // Update JID on existing conversations (in case format changed)
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
        if ($conversation->status === 'escalated') {
            return ['replied' => false, 'reason' => 'conversation_escalated', 'conversation_id' => $conversation->id];
        }

        // Reactivate closed conversations when customer messages again
        if ($conversation->status === 'closed') {
            $conversation->update([
                'status' => 'active',
                'escalation_reason' => null,
                'escalated_at' => null,
            ]);
        }

        // -- Step 4: Build prompt --
        $messages = $this->buildPrompt($playbook, $conversation);

        // -- Step 5: Call OpenAI (with JSON mode for reliable parsing) --
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

        // Handle null/empty content from OpenAI
        if (empty($result['content'])) {
            Log::error('AI Reply returned empty content', ['conversation_id' => $conversation->id]);
            return ['replied' => false, 'reason' => 'openai_empty_response', 'conversation_id' => $conversation->id];
        }

        // -- Step 6: Parse response --
        $parsed = $this->parseAiResponse($result['content']);

        // -- Step 7: Handle escalation --
        if ($parsed['escalate']) {
            $conversation->update([
                'status' => 'escalated',
                'escalation_reason' => $parsed['escalation_reason'],
                'escalated_at' => now(),
            ]);

            ConversationMessage::create([
                'conversation_id' => $conversation->id,
                'direction' => 'outbound',
                'sender_type' => 'ai',
                'body' => $parsed['reply'],
                'confidence_score' => $parsed['confidence'],
                'reasoning_source' => $parsed['reasoning_source'],
                'escalation_reason' => $parsed['escalation_reason'],
                'prompt_tokens' => $result['usage']['prompt_tokens'] ?? null,
                'completion_tokens' => $result['usage']['completion_tokens'] ?? null,
                'total_tokens' => $result['usage']['total_tokens'] ?? null,
                'latency_ms' => $result['latency_ms'],
            ]);

            // Send the handoff message if the AI produced one (use original JID for @lid support)
            if (!empty($parsed['reply'])) {
                $this->whatsapp->sendMessage($whatsappNumber, $contactPhone, $parsed['reply']);
            }

            return ['replied' => true, 'reason' => 'escalated: ' . $parsed['escalation_reason'], 'conversation_id' => $conversation->id];
        }

        // -- Step 8: Send AI reply (use original JID to preserve @lid format) --
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

        $history = $conversation->latestMessages(20);

        // Add explicit conversation state to help AI understand context
        if ($history->count() > 1) {
            $lastAiMessage = $history->where('direction', 'outbound')->last();
            $lastUserMessage = $history->where('direction', 'inbound')->last();

            $systemPrompt .= "\n\n=== CONVERSATION STATE ===\n";
            $systemPrompt .= "IMPORTANT: This conversation already has {$history->count()} messages.\n";
            $systemPrompt .= "You have ALREADY greeted this customer.\n";
            if ($lastAiMessage) {
                $systemPrompt .= "Your last message: \"" . substr($lastAiMessage->body, 0, 100) . "\"\n";
            }
            if ($lastUserMessage) {
                $systemPrompt .= "Customer's latest message: \"" . substr($lastUserMessage->body, 0, 100) . "\"\n";
            }
            $systemPrompt .= "\nDO NOT send another greeting. Respond directly to the customer's latest message based on the conversation history below.";
        }

        Log::debug('AI buildPrompt', [
            'conversation_id' => $conversation->id,
            'history_count' => $history->count(),
        ]);

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        // Add conversation history as PLAIN TEXT (not JSON wrapped)
        foreach ($history as $msg) {
            $role = ($msg->direction === 'inbound') ? 'user' : 'assistant';
            $messages[] = ['role' => $role, 'content' => $msg->body];
        }

        return $messages;
    }

    /**
     * Build the system prompt from playbook content.
     */
    protected function buildSystemPrompt(Playbook $playbook): string
    {
        // Defense-in-depth: sanitize again at injection time
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

=== CONVERSATION PROGRESSION RULES ===
CRITICAL: The playbook shows a conversation FLOW, not a rigid script. You must adapt based on what information you've ALREADY collected.

Before asking ANY question, CHECK THE CONVERSATION HISTORY:
- If customer already provided information → DO NOT ask again, use what they gave you
- If customer already answered a question → MOVE FORWARD to the next logical step
- If customer asked something specific → Answer it directly, don't redirect to a script

PROGRESSION LOGIC:
1. If this is the FIRST message (no history) → Follow the playbook's opening/greeting flow
2. For subsequent messages → Read what the customer JUST said and respond based on the playbook
3. Always progress the conversation forward — never loop back to earlier steps
4. If the playbook defines escalation triggers → Follow them

NEVER REPEAT QUESTIONS. Read the conversation history carefully and respond to what the customer JUST said.

=== RULES ===
1. "reply" is the actual text message to send to the customer via WhatsApp. Keep it natural, concise, and conversational.
2. "confidence" is a float from 0.0 to 1.0 indicating how confident you are that your reply correctly follows the playbook.
   - 0.9-1.0: Answer directly addressed in the playbook
   - 0.7-0.9: Answer can be inferred from playbook context
   - 0.5-0.7: Answer requires minor extrapolation
   - Below 0.5: Must escalate
3. "reasoning_source" is the specific section title or area of the playbook you used to craft this response.
4. Set "escalate" to true when:
   - The customer explicitly asks to speak to a human, agent, or manager
   - The topic is outside the playbook scope
   - You detect a forbidden topic or action from the playbook
   - Your confidence would be below 0.5
   - The customer appears frustrated after 3+ exchanges without resolution
   - Budget threshold exceeded (per playbook escalation rules)
5. When escalating, "reply" should contain a polite handoff message (e.g. "Terima kasih, saya sambungkan anda dengan team kami sebentar ya.").
6. "escalation_reason" should explain why you are escalating (only when escalate=true).
7. Never make up information not in the playbook.
8. Never share pricing, promotions, or policies not explicitly stated in the playbook.
9. If unsure, escalate rather than guess.
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

        // Strip markdown code fences if present
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
            'escalate' => (bool) ($parsed['escalate'] ?? false),
            'escalation_reason' => $parsed['escalation_reason'] ?? null,
        ];
    }

    /**
     * Clean phone number: strip WhatsApp JID suffix and non-digit chars.
     * Handles @lid JIDs by using them as-is (they are opaque identifiers).
     */
    protected function cleanPhone(string $phone): string
    {
        // @lid JIDs are opaque identifiers - store the part before @lid as the key
        if (str_contains($phone, '@lid')) {
            return str_replace('@lid', '', $phone);
        }

        $phone = str_replace('@s.whatsapp.net', '', $phone);
        $phone = str_replace('@g.us', '', $phone);
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
