<?php

namespace App\Services;

use App\Models\User;
use App\Models\WhatsappNumber;
use Illuminate\Support\Facades\Log;

class SendoraCommandService
{
    protected OpenAiService $openAi;
    protected ReminderService $reminderService;

    public function __construct(OpenAiService $openAi, ReminderService $reminderService)
    {
        $this->openAi = $openAi;
        $this->reminderService = $reminderService;
    }

    public function parseCommand(string $text): ?array
    {
        // Strip the /sendora prefix
        $text = preg_replace('/^\/sendora\s*/i', '', $text);

        if (empty(trim($text))) {
            return null;
        }

        $today = now()->format('Y-m-d (l)');
        $timezone = config('app.timezone', 'Asia/Kuala_Lumpur');

        $result = $this->openAi->chatCompletion(
            messages: [
                [
                    'role' => 'system',
                    'content' => "You are a command parser for a reminder system. Today is {$today}, timezone: {$timezone}. Parse the user's message into a JSON object with these fields:
- action: always \"create_reminder\"
- title: short title for the event/reminder
- date: YYYY-MM-DD format
- time: HH:MM format (24h)
- location: string or null
- description: any extra details or null
- minutes_before: how many minutes before to remind (default 15)
- recurrence_rule: \"daily\", \"weekly\", \"monthly\", \"yearly\", or null

Respond ONLY with valid JSON, no other text.",
                ],
                [
                    'role' => 'user',
                    'content' => $text,
                ],
            ],
            model: 'gpt-4o',
            temperature: 0.1,
            maxTokens: 300,
            jsonMode: true,
        );

        if (!$result['success']) {
            Log::error('Sendora command parse failed', ['error' => $result['error']]);
            return null;
        }

        $parsed = json_decode($result['content'], true);
        if (!$parsed || empty($parsed['title']) || empty($parsed['date'])) {
            return null;
        }

        return $parsed;
    }

    public function executeCommand(User $user, WhatsappNumber $waNumber, string $text): string
    {
        $parsed = $this->parseCommand($text);

        if (!$parsed) {
            return "\xE2\x9D\x8C Could not understand the command. Try:\n/sendora Meeting tomorrow at 3pm in my office";
        }

        $eventAt = $parsed['date'] . ' ' . ($parsed['time'] ?? '09:00');

        $reminder = $this->reminderService->createReminder($user, [
            'title' => $parsed['title'],
            'description' => $parsed['description'] ?? null,
            'event_at' => $eventAt,
            'minutes_before' => $parsed['minutes_before'] ?? 15,
            'location' => $parsed['location'] ?? null,
            'recurrence_rule' => $parsed['recurrence_rule'] ?? null,
            'whatsapp_number_id' => $waNumber->id,
            'source' => 'whatsapp_command',
            'add_to_calendar' => $user->googleCalendarConnection !== null,
        ]);

        return $this->formatConfirmation($reminder);
    }

    protected function formatConfirmation($reminder): string
    {
        $dateTime = $reminder->event_at ?? $reminder->reminder_at;
        $lines = [];
        $lines[] = "\xE2\x9C\x85 *Reminder Set!*";
        $lines[] = '';
        $lines[] = "\xF0\x9F\x94\x94 *{$reminder->title}*";
        $lines[] = "\xF0\x9F\x93\x85 " . $dateTime->format('l, d M Y \a\t g:i A');

        if ($reminder->location) {
            $lines[] = "\xF0\x9F\x93\x8D " . $reminder->location;
        }
        if ($reminder->description) {
            $lines[] = "\xF0\x9F\x93\x9D " . $reminder->description;
        }

        $lines[] = '';
        $lines[] = "\xE2\x8F\xB0 Reminder: {$reminder->minutes_before} min before";

        if ($reminder->google_event_id) {
            $lines[] = "\xF0\x9F\x93\x86 Added to Google Calendar";
        }

        if ($reminder->recurrence_rule) {
            $lines[] = "\xF0\x9F\x94\x81 Repeats: " . ucfirst($reminder->recurrence_rule);
        }

        return implode("\n", $lines);
    }
}
