<?php

namespace App\Services;

use App\Models\GoogleCalendarConnection;
use App\Models\Reminder;
use App\Models\User;
use App\Models\WhatsappNumber;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SendoraCommandService
{
    protected OpenAiService $openAi;

    protected ReminderService $reminderService;

    protected GoogleCalendarService $calendarService;

    public function __construct(OpenAiService $openAi, ReminderService $reminderService, GoogleCalendarService $calendarService)
    {
        $this->openAi = $openAi;
        $this->reminderService = $reminderService;
        $this->calendarService = $calendarService;
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
        if (preg_match('/^(edit|reschedule|update|change)(\s|$)/i', $body)) {
            return 'edit';
        }

        return 'create';
    }

    public function executeCommand(User $user, WhatsappNumber $waNumber, string $text): string
    {
        return match ($this->detectCommandType($text)) {
            'help' => $this->executeHelp(),
            'list' => $this->executeList($user),
            'cancel' => $this->executeCancel($user, $waNumber, $text),
            'edit' => $this->executeEdit($user, $waNumber, $text),
            default => $this->executeCreate($user, $waNumber, $text),
        };
    }

    protected function executeHelp(): string
    {
        return "\xF0\x9F\x93\x96 *Sendora Commands*\n"
            ."\n"
            ."\xE2\x96\xB8 /sendora [description]\n"
            ."  Create a reminder\n"
            ."  _Example: /sendora Meeting tomorrow at 3pm in my office_\n"
            ."\n"
            ."\xE2\x96\xB8 /sendora cancel [description]\n"
            ."  Cancel a reminder\n"
            ."  _Example: /sendora cancel tomorrow's meeting_\n"
            ."\n"
            ."\xE2\x96\xB8 /sendora edit [description]\n"
            ."  Edit or reschedule a reminder\n"
            ."  _Example: /sendora reschedule meeting to next Friday 3pm_\n"
            ."\n"
            ."\xE2\x96\xB8 /sendora list\n"
            ."  Show your week ahead (events, reminders & birthdays)\n"
            ."\n"
            ."\xE2\x96\xB8 /sendorahelp\n"
            .'  Show this help message';
    }

    protected function executeList(User $user): string
    {
        $days = 7;
        $startDate = now();
        $endDate = now()->addDays($days);

        // Fetch pending reminders for the next 7 days (any source)
        $reminders = Reminder::where('user_id', $user->id)
            ->where('status', 'pending')
            ->whereRaw('COALESCE(event_at, reminder_at) > ?', [$startDate])
            ->whereRaw('COALESCE(event_at, reminder_at) <= ?', [$endDate])
            ->orderByRaw('COALESCE(event_at, reminder_at)')
            ->limit(30)
            ->get();

        // Fetch birthdays if Google Calendar is connected
        $birthdays = [];
        $calConn = $user->googleCalendarConnection;

        if ($calConn) {
            $birthdays = $this->calendarService->getUpcomingBirthdays($calConn, $days);
        }

        if ($reminders->isEmpty() && empty($birthdays)) {
            $msg = "\xF0\x9F\x93\xAD Nothing scheduled for the next 7 days.";
            if (! $calConn) {
                $msg .= "\n\n_Connect Google Calendar to see events & birthdays._";
            }

            return $msg;
        }

        return $this->formatWeekView($reminders, $birthdays, $startDate, $endDate, $calConn);
    }

    protected function formatWeekView(Collection $reminders, array $birthdays, $startDate, $endDate, ?GoogleCalendarConnection $calConn): string
    {
        $startLabel = $startDate->format('d M');
        $endLabel = $endDate->format('d M');
        $lines = ["\xF0\x9F\x93\x8B *Your Week Ahead* ({$startLabel} - {$endLabel})\n"];

        // Group reminders by date
        $remindersByDate = $reminders->groupBy(function ($r) {
            $dt = $r->event_at ?? $r->reminder_at;

            return $dt->format('Y-m-d');
        });

        // Group birthdays by date
        $birthdaysByDate = [];
        foreach ($birthdays as $b) {
            $key = $b['date']->format('Y-m-d');
            $birthdaysByDate[$key][] = $b;
        }

        // Collect all dates that have items
        $allDates = collect(array_unique(array_merge(
            $remindersByDate->keys()->toArray(),
            array_keys($birthdaysByDate),
        )))->sort()->values();

        $emptyDays = [];
        $period = new \DatePeriod(
            $startDate->copy()->startOfDay()->toDateTimeImmutable(),
            new \DateInterval('P1D'),
            $endDate->copy()->endOfDay()->toDateTimeImmutable(),
        );

        foreach ($period as $day) {
            $key = $day->format('Y-m-d');
            if (! $allDates->contains($key)) {
                $emptyDays[] = \Carbon\Carbon::parse($key)->format('D');
            }
        }

        foreach ($allDates as $dateKey) {
            $dayLabel = \Carbon\Carbon::parse($dateKey)->format('D, d M');
            $lines[] = "\xE2\x94\x81\xE2\x94\x81 *{$dayLabel}* \xE2\x94\x81\xE2\x94\x81";

            // Reminders/events for this day
            $dayReminders = $remindersByDate->get($dateKey, collect());
            foreach ($dayReminders as $reminder) {
                $isCalendar = $reminder->source === 'google_calendar';
                $emoji = $isCalendar ? "\xF0\x9F\x93\x86" : "\xF0\x9F\x94\x94";
                $lines[] = "{$emoji} {$reminder->title}";

                $dateTime = $reminder->event_at ?? $reminder->reminder_at;
                $timePart = "   \xF0\x9F\x93\x85 ".$dateTime->format('g:i A');
                if ($reminder->location) {
                    $timePart .= " \xC2\xB7 \xF0\x9F\x93\x8D {$reminder->location}";
                }
                $lines[] = $timePart;
            }

            // Birthdays for this day
            $dayBirthdays = $birthdaysByDate[$dateKey] ?? [];
            foreach ($dayBirthdays as $b) {
                $lines[] = "\xF0\x9F\x8E\x82 {$b['name']}";
            }

            $lines[] = '';
        }

        // Show empty days summary
        if (! empty($emptyDays)) {
            $lines[] = "\xF0\x9F\x93\xAD Nothing on ".implode(', ', $emptyDays);
        }

        if (! $calConn) {
            $lines[] = '';
            $lines[] = '_Connect Google Calendar to see events & birthdays._';
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
                    'content' => "You are matching a user's cancel request to one of their existing reminders. Given the list of reminders and the user's description, return JSON with:\n- matched_id: the id of the best matching reminder, or null if no clear match\n\nReminders:\n".json_encode($reminderList)."\n\nRespond ONLY with valid JSON.",
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

        if (! $result['success']) {
            Log::error('Sendora cancel match failed', ['error' => $result['error']]);

            return "\xE2\x9D\x8C Could not process cancel request. Please try again.";
        }

        $parsed = json_decode($result['content'], true);
        $matchedId = $parsed['matched_id'] ?? null;

        if (! $matchedId) {
            return "\xE2\x9D\x8C Could not find a matching reminder. Here are your pending reminders:\n\n"
                .$this->formatReminderListForCancel($pendingReminders);
        }

        $reminder = $pendingReminders->firstWhere('id', $matchedId);

        if (! $reminder) {
            return "\xE2\x9D\x8C Could not find a matching reminder. Here are your pending reminders:\n\n"
                .$this->formatReminderListForCancel($pendingReminders);
        }

        $hadGoogleEvent = (bool) $reminder->google_event_id;
        $this->reminderService->cancelReminder($reminder);

        $dateTime = $reminder->event_at ?? $reminder->reminder_at;
        $lines = [
            "\xE2\x9C\x85 *Reminder Cancelled*",
            '',
            "\xF0\x9F\x94\x94 *{$reminder->title}*",
            "\xF0\x9F\x93\x85 ".$dateTime->format('l, d M Y \a\t g:i A'),
        ];

        if ($reminder->location) {
            $lines[] = "\xF0\x9F\x93\x8D ".$reminder->location;
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
            $lines[] = "   \xF0\x9F\x93\x85 ".$dateTime->format('D, d M Y g:i A');
            $lines[] = '';
        }

        $lines[] = '_Reply with: /sendora cancel [description]_';

        return implode("\n", $lines);
    }

    protected function executeEdit(User $user, WhatsappNumber $waNumber, string $text): string
    {
        $pendingReminders = Reminder::where('user_id', $user->id)
            ->where('status', 'pending')
            ->where('reminder_at', '>', now())
            ->orderBy('reminder_at')
            ->get();

        if ($pendingReminders->isEmpty()) {
            return "\xF0\x9F\x93\xAD No pending reminders to edit.";
        }

        // Extract the edit description
        $body = trim(preg_replace('/^\/sendora\s+(edit|reschedule|update|change)\s*/i', '', trim($text)));

        if (empty($body)) {
            return $this->formatReminderListForEdit($pendingReminders);
        }

        // Build reminder list for AI matching
        $reminderList = $pendingReminders->map(function ($r, $i) {
            $dateTime = $r->event_at ?? $r->reminder_at;

            return [
                'id' => $r->id,
                'index' => $i + 1,
                'title' => $r->title,
                'date' => $dateTime->format('Y-m-d H:i'),
                'location' => $r->location,
                'minutes_before' => $r->minutes_before,
            ];
        })->values()->toArray();

        $today = now()->format('Y-m-d (l)');
        $timezone = config('app.timezone', 'Asia/Kuala_Lumpur');

        $result = $this->openAi->chatCompletion(
            messages: [
                [
                    'role' => 'system',
                    'content' => "You are matching a user's edit/reschedule request to one of their existing reminders. Today is {$today}, timezone: {$timezone}. Given the list of reminders and the user's description, return JSON with:\n- matched_id: the id of the best matching reminder, or null if no clear match\n- changes: object with only the fields that should change. Possible fields: title (string), date (YYYY-MM-DD), time (HH:MM 24h), location (string), minutes_before (integer)\n\nOnly include fields in changes that the user explicitly wants to change. If the user only mentions a new time, only include time. If they mention a new date and time, include both.\n\nReminders:\n".json_encode($reminderList)."\n\nRespond ONLY with valid JSON.",
                ],
                [
                    'role' => 'user',
                    'content' => $body,
                ],
            ],
            model: 'gpt-4o',
            temperature: 0.1,
            maxTokens: 300,
            jsonMode: true,
        );

        if (! $result['success']) {
            Log::error('Sendora edit match failed', ['error' => $result['error']]);

            return "\xE2\x9D\x8C Could not process edit request. Please try again.";
        }

        $parsed = json_decode($result['content'], true);
        $matchedId = $parsed['matched_id'] ?? null;
        $changes = $parsed['changes'] ?? [];

        if (! $matchedId) {
            return "\xE2\x9D\x8C Could not find a matching reminder. Here are your pending reminders:\n\n"
                .$this->formatReminderListForEdit($pendingReminders);
        }

        $reminder = $pendingReminders->firstWhere('id', $matchedId);

        if (! $reminder) {
            return "\xE2\x9D\x8C Could not find a matching reminder. Here are your pending reminders:\n\n"
                .$this->formatReminderListForEdit($pendingReminders);
        }

        if (empty($changes)) {
            return "\xE2\x9D\x8C No changes detected. Please specify what you'd like to change.\n_Example: /sendora edit meeting to 3pm_";
        }

        // Build update data
        $updateData = [];

        if (isset($changes['title'])) {
            $updateData['title'] = $changes['title'];
        }
        if (isset($changes['location'])) {
            $updateData['location'] = $changes['location'];
        }
        if (isset($changes['minutes_before'])) {
            $updateData['minutes_before'] = (int) $changes['minutes_before'];
        }

        // Handle date/time changes
        $currentDateTime = $reminder->event_at ?? $reminder->reminder_at;
        if (isset($changes['date']) || isset($changes['time'])) {
            $date = $changes['date'] ?? $currentDateTime->format('Y-m-d');
            $time = $changes['time'] ?? $currentDateTime->format('H:i');
            $updateData['event_at'] = $date.' '.$time;
        }

        $hadGoogleEvent = (bool) $reminder->google_event_id;
        $this->reminderService->updateReminder($reminder, $updateData);
        $reminder->refresh();

        // Format confirmation
        $dateTime = $reminder->event_at ?? $reminder->reminder_at;
        $lines = [
            "\xE2\x9C\x85 *Reminder Updated!*",
            '',
            "\xF0\x9F\x94\x94 *{$reminder->title}*",
            "\xF0\x9F\x93\x85 ".$dateTime->format('l, d M Y \a\t g:i A'),
        ];

        if ($reminder->location) {
            $lines[] = "\xF0\x9F\x93\x8D ".$reminder->location;
        }

        $changedFields = [];
        if (isset($changes['title'])) {
            $changedFields[] = 'title';
        }
        if (isset($changes['date']) || isset($changes['time'])) {
            $changedFields[] = 'date/time';
        }
        if (isset($changes['location'])) {
            $changedFields[] = 'location';
        }
        if (isset($changes['minutes_before'])) {
            $changedFields[] = 'reminder time';
        }
        $lines[] = '';
        $lines[] = "\xF0\x9F\x93\x9D Changed: ".implode(', ', $changedFields);

        if ($hadGoogleEvent) {
            $lines[] = "\xF0\x9F\x93\x86 Google Calendar updated";
        }

        return implode("\n", $lines);
    }

    protected function formatReminderListForEdit($reminders): string
    {
        $lines = ["\xF0\x9F\x93\x8B *Your Pending Reminders*\n"];

        foreach ($reminders as $i => $reminder) {
            $num = $i + 1;
            $dateTime = $reminder->event_at ?? $reminder->reminder_at;
            $lines[] = "{$num}. *{$reminder->title}*";
            $lines[] = "   \xF0\x9F\x93\x85 ".$dateTime->format('D, d M Y g:i A');
            $lines[] = '';
        }

        $lines[] = '_Reply with: /sendora edit [what to change]_';
        $lines[] = '_Example: /sendora reschedule meeting to Friday 3pm_';

        return implode("\n", $lines);
    }

    protected function executeCreate(User $user, WhatsappNumber $waNumber, string $text): string
    {
        $parsed = $this->parseCommand($text);

        if (! $parsed) {
            return "\xE2\x9D\x8C Could not understand the command. Try:\n/sendora Meeting tomorrow at 3pm in my office";
        }

        $eventAt = $parsed['date'].' '.($parsed['time'] ?? '09:00');

        $reminder = $this->reminderService->createReminder($user, [
            'title' => $parsed['title'],
            'description' => $parsed['description'] ?? null,
            'event_at' => $eventAt,
            'minutes_before' => $parsed['minutes_before'] ?? 15,
            'location' => $parsed['location'] ?? null,
            'recurrence_rule' => $parsed['recurrence_rule'] ?? null,
            'notify_email' => $parsed['notify_email'] ?? null,
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
- notify_email: email address to notify, or null

If the user mentions inviting or notifying someone via email (e.g., 'invite john@email.com', 'notify sarah@company.com'), extract the email address into notify_email. Do not include the email part in the title or description.

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

        if (! $result['success']) {
            Log::error('Sendora command parse failed', ['error' => $result['error']]);

            return null;
        }

        $parsed = json_decode($result['content'], true);
        if (! $parsed || empty($parsed['title']) || empty($parsed['date'])) {
            return null;
        }

        return $parsed;
    }

    public function formatConfirmation($reminder): string
    {
        $dateTime = $reminder->event_at ?? $reminder->reminder_at;
        $lines = [];
        $lines[] = "\xE2\x9C\x85 *Reminder Set!*";
        $lines[] = '';
        $lines[] = "\xF0\x9F\x94\x94 *{$reminder->title}*";
        $lines[] = "\xF0\x9F\x93\x85 ".$dateTime->format('l, d M Y \a\t g:i A');

        if ($reminder->location) {
            $lines[] = "\xF0\x9F\x93\x8D ".$reminder->location;
        }
        if ($reminder->description) {
            $lines[] = "\xF0\x9F\x93\x9D ".$reminder->description;
        }

        $lines[] = '';
        $lines[] = "\xE2\x8F\xB0 Reminder: {$reminder->minutes_before} min before";

        if ($reminder->google_event_id) {
            $lines[] = "\xF0\x9F\x93\x86 Added to Google Calendar";
        }

        if ($reminder->recurrence_rule) {
            $lines[] = "\xF0\x9F\x94\x81 Repeats: ".ucfirst($reminder->recurrence_rule);
        }

        if ($reminder->notify_email) {
            $lines[] = "\xF0\x9F\x93\xA7 Email notification sent to {$reminder->notify_email}";
        }

        return implode("\n", $lines);
    }
}
