<?php

namespace App\Services;

use App\Models\Reminder;
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

    public function detectCommandType(string $text): string
    {
        $trimmed = trim($text);

        // /sendorahelp (standalone command)
        if (preg_match('/^\/sendorahelp$/i', $trimmed)) {
            return 'help';
        }

        // Strip /sendora prefix and check subcommand
        $body = trim(preg_replace('/^\/sendora\s*/i', '', $trimmed));

        if (preg_match('/^help$/i', $body)) {
            return 'help';
        }
        if (preg_match('/^list$/i', $body)) {
            return 'list';
        }
        if (preg_match('/^cancel(\s|$)/i', $body)) {
            return 'cancel';
        }

        return 'create';
    }

    public function executeCommand(User $user, WhatsappNumber $waNumber, string $text): string
    {
        return match ($this->detectCommandType($text)) {
            'help'   => $this->executeHelp(),
            'list'   => $this->executeList($user),
            'cancel' => $this->executeCancel($user, $waNumber, $text),
            default  => $this->executeCreate($user, $waNumber, $text),
        };
    }

    protected function executeHelp(): string
    {
        return "\xF0\x9F\x93\x96 *Sendora Commands*\n"
            . "\n"
            . "\xE2\x96\xB8 /sendora [description]\n"
            . "  Create a reminder\n"
            . "  _Example: /sendora Meeting tomorrow at 3pm in my office_\n"
            . "\n"
            . "\xE2\x96\xB8 /sendora cancel [description]\n"
            . "  Cancel a reminder\n"
            . "  _Example: /sendora cancel tomorrow's meeting_\n"
            . "\n"
            . "\xE2\x96\xB8 /sendora list\n"
            . "  Show upcoming reminders\n"
            . "\n"
            . "\xE2\x96\xB8 /sendorahelp\n"
            . "  Show this help message";
    }

    protected function executeList(User $user): string
    {
        $reminders = Reminder::where('user_id', $user->id)
            ->where('status', 'pending')
            ->where('reminder_at', '>', now())
            ->orderBy('reminder_at')
            ->limit(10)
            ->get();

        if ($reminders->isEmpty()) {
            return "\xF0\x9F\x93\xAD No upcoming reminders.";
        }

        $lines = ["\xF0\x9F\x93\x8B *Upcoming Reminders*\n"];

        foreach ($reminders as $i => $reminder) {
            $num = $i + 1;
            $dateTime = $reminder->event_at ?? $reminder->reminder_at;
            $lines[] = "{$num}. *{$reminder->title}*";
            $lines[] = "   \xF0\x9F\x93\x85 " . $dateTime->format('D, d M Y g:i A');
            if ($reminder->location) {
                $lines[] = "   \xF0\x9F\x93\x8D {$reminder->location}";
            }
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    protected function executeCancel(User $user, WhatsappNumber $waNumber, string $text): string
    {
        $pendingReminders = Reminder::where('user_id', $user->id)
            ->where('status', 'pending')
            ->where('reminder_at', '>', now())
            ->orderBy('reminder_at')
            ->get();

        if ($pendingReminders->isEmpty()) {
            return "\xF0\x9F\x93\xAD No pending reminders to cancel.";
        }

        // Extract the cancel description
        $body = trim(preg_replace('/^\/sendora\s+cancel\s*/i', '', trim($text)));

        if (empty($body)) {
            return $this->formatReminderListForCancel($pendingReminders);
        }

        // Use AI to match description to a reminder
        $reminderList = $pendingReminders->map(function ($r, $i) {
            $dateTime = $r->event_at ?? $r->reminder_at;
            return [
                'id' => $r->id,
                'index' => $i + 1,
                'title' => $r->title,
                'date' => $dateTime->format('Y-m-d H:i'),
                'location' => $r->location,
            ];
        })->values()->toArray();

        $result = $this->openAi->chatCompletion(
            messages: [
                [
                    'role' => 'system',
                    'content' => "You are matching a user's cancel request to one of their existing reminders. Given the list of reminders and the user's description, return JSON with:\n- matched_id: the id of the best matching reminder, or null if no clear match\n\nReminders:\n" . json_encode($reminderList) . "\n\nRespond ONLY with valid JSON.",
                ],
                [
                    'role' => 'user',
                    'content' => $body,
                ],
            ],
            model: 'gpt-4o',
            temperature: 0.1,
            maxTokens: 100,
            jsonMode: true,
        );

        if (!$result['success']) {
            Log::error('Sendora cancel match failed', ['error' => $result['error']]);
            return "\xE2\x9D\x8C Could not process cancel request. Please try again.";
        }

        $parsed = json_decode($result['content'], true);
        $matchedId = $parsed['matched_id'] ?? null;

        if (!$matchedId) {
            return "\xE2\x9D\x8C Could not find a matching reminder. Here are your pending reminders:\n\n"
                . $this->formatReminderListForCancel($pendingReminders);
        }

        $reminder = $pendingReminders->firstWhere('id', $matchedId);

        if (!$reminder) {
            return "\xE2\x9D\x8C Could not find a matching reminder. Here are your pending reminders:\n\n"
                . $this->formatReminderListForCancel($pendingReminders);
        }

        $hadGoogleEvent = (bool) $reminder->google_event_id;
        $this->reminderService->cancelReminder($reminder);

        $dateTime = $reminder->event_at ?? $reminder->reminder_at;
        $lines = [
            "\xE2\x9C\x85 *Reminder Cancelled*",
            '',
            "\xF0\x9F\x94\x94 *{$reminder->title}*",
            "\xF0\x9F\x93\x85 " . $dateTime->format('l, d M Y \a\t g:i A'),
        ];

        if ($reminder->location) {
            $lines[] = "\xF0\x9F\x93\x8D " . $reminder->location;
        }

        if ($hadGoogleEvent) {
            $lines[] = '';
            $lines[] = "\xF0\x9F\x93\x86 Removed from Google Calendar";
        }

        return implode("\n", $lines);
    }

    protected function formatReminderListForCancel($reminders): string
    {
        $lines = ["\xF0\x9F\x93\x8B *Your Pending Reminders*\n"];

        foreach ($reminders as $i => $reminder) {
            $num = $i + 1;
            $dateTime = $reminder->event_at ?? $reminder->reminder_at;
            $lines[] = "{$num}. *{$reminder->title}*";
            $lines[] = "   \xF0\x9F\x93\x85 " . $dateTime->format('D, d M Y g:i A');
            $lines[] = '';
        }

        $lines[] = "_Reply with: /sendora cancel [description]_";

        return implode("\n", $lines);
    }

    protected function executeCreate(User $user, WhatsappNumber $waNumber, string $text): string
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
