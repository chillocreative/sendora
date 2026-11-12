<?php

namespace App\Services;

use App\Mail\ReminderEmailNotification;
use App\Models\Reminder;
use App\Models\ReminderLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class ReminderService
{
    public const ERROR_OWNER_PHONE_UNVERIFIED = 'Owner phone not verified for this Cloud number';

    public const ERROR_NO_CONNECTED_NUMBER = 'No connected WhatsApp number available';

    protected WhatsappService $whatsapp;

    protected GoogleCalendarService $calendarService;

    public function __construct(WhatsappService $whatsapp, GoogleCalendarService $calendarService)
    {
        $this->whatsapp = $whatsapp;
        $this->calendarService = $calendarService;
    }

    /**
     * The plan's monthly cap applies to every creation path (web, WhatsApp command, media
     * upload), not just the web form. A limit of 0 means unlimited.
     *
     * @throws \App\Exceptions\ReminderLimitReached
     */
    public function assertWithinMonthlyLimit(User $user): void
    {
        $limit = (int) ($user->current_plan?->limits['reminders_per_month'] ?? 0);

        if ($limit <= 0) {
            return;
        }

        $used = Reminder::where('user_id', $user->id)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        if ($used >= $limit) {
            throw new \App\Exceptions\ReminderLimitReached($limit);
        }
    }

    public function createReminder(User $user, array $data, ?string $idempotencyKey = null): Reminder
    {
        $eventAt = Carbon::parse($data['event_at'] ?? $data['reminder_at']);
        $minutesBefore = (int) ($data['minutes_before'] ?? 15);
        $reminderAt = isset($data['event_at'])
            ? $eventAt->copy()->subMinutes($minutesBefore)
            : Carbon::parse($data['reminder_at']);

        // Guard against duplicates: same user/title/start created in the last 10 minutes
        $existing = Reminder::where('user_id', $user->id)
            ->where('title', $data['title'])
            ->where('reminder_at', $reminderAt)
            ->where('created_at', '>=', now()->subMinutes(10))
            ->latest('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        // Serialise the count and the insert per user so concurrent creations (web, WhatsApp,
        // media jobs) cannot all pass the limit check before any of them is saved.
        $reminder = Cache::lock("reminder-create:{$user->id}", 10)->block(5, function () use ($user, $data, $reminderAt, $minutesBefore) {
            $this->assertWithinMonthlyLimit($user);

            return Reminder::create([
                'user_id' => $user->id,
                'whatsapp_number_id' => $data['whatsapp_number_id'] ?? null,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'reminder_at' => $reminderAt,
                'event_at' => $data['event_at'] ?? null,
                'minutes_before' => $minutesBefore,
                'location' => $data['location'] ?? null,
                'recurrence_rule' => $data['recurrence_rule'] ?? null,
                'source' => $data['source'] ?? 'web',
                'status' => 'pending',
            ]);
        });

        // Sync to Google Calendar if requested and connected
        if (! empty($data['add_to_calendar'])) {
            $conn = $user->googleCalendarConnection;
            if ($conn) {
                $googleEventId = $this->calendarService->createEvent($conn, [
                    'title' => $reminder->title,
                    'description' => $reminder->description,
                    'location' => $reminder->location,
                    'event_at' => $reminder->event_at ?? $reminder->reminder_at,
                    'minutes_before' => $reminder->minutes_before,
                ], $idempotencyKey);

                if ($googleEventId) {
                    $reminder->update([
                        'google_event_id' => $googleEventId,
                        'google_calendar_connection_id' => $conn->id,
                    ]);
                }
            }
        }

        $reminder->refresh();

        ReminderLog::create([
            'reminder_id' => $reminder->id,
            'action' => 'created',
            'details' => "Reminder created via {$reminder->source}",
        ]);

        // Send email notification on creation
        if (! empty($data['notify_email'])) {
            $reminder->update(['notify_email' => $data['notify_email']]);
            Mail::to($data['notify_email'])->queue(new ReminderEmailNotification($reminder, 'created'));
        }

        return $reminder;
    }

    public function cancelReminder(Reminder $reminder): bool
    {
        if ($reminder->status !== 'pending') {
            return false;
        }

        // Delete from Google Calendar if linked
        if ($reminder->google_event_id && $reminder->google_calendar_connection_id) {
            $conn = $reminder->googleCalendarConnection;
            if ($conn) {
                $this->calendarService->deleteEvent($conn, $reminder->google_event_id);
            }
        }

        $reminder->update(['status' => 'cancelled']);

        ReminderLog::create([
            'reminder_id' => $reminder->id,
            'action' => 'cancelled',
            'details' => 'Reminder cancelled',
        ]);

        return true;
    }

    public function updateReminder(Reminder $reminder, array $data): Reminder
    {
        // Recalculate reminder_at if event_at or minutes_before changed
        if (isset($data['event_at']) || isset($data['minutes_before'])) {
            $eventAt = Carbon::parse($data['event_at'] ?? $reminder->event_at ?? $reminder->reminder_at);
            $minutesBefore = (int) ($data['minutes_before'] ?? $reminder->minutes_before ?? 15);
            $data['reminder_at'] = $eventAt->copy()->subMinutes($minutesBefore);
            $data['event_at'] = $eventAt;
            $data['minutes_before'] = $minutesBefore;
        }

        $reminder->update($data);

        // Sync to Google Calendar if linked
        if ($reminder->google_event_id && $reminder->google_calendar_connection_id) {
            $conn = $reminder->googleCalendarConnection;
            if ($conn) {
                $this->calendarService->updateEvent($conn, $reminder->google_event_id, [
                    'title' => $reminder->title,
                    'description' => $reminder->description,
                    'location' => $reminder->location,
                    'event_at' => $reminder->event_at ?? $reminder->reminder_at,
                    'minutes_before' => $reminder->minutes_before,
                ]);
            }
        }

        ReminderLog::create([
            'reminder_id' => $reminder->id,
            'action' => 'updated',
            'details' => 'Reminder updated: '.implode(', ', array_keys($data)),
        ]);

        return $reminder->refresh();
    }

    public function sendReminder(Reminder $reminder): bool
    {
        $reminder->loadMissing(['user', 'whatsappNumber']);

        $waNumber = $reminder->whatsappNumber
            ?? $reminder->user->whatsappNumbers()->where('status', 'connected')->first();

        if (! $waNumber || $waNumber->status !== 'connected') {
            return $this->markFailed($reminder, null, self::ERROR_NO_CONNECTED_NUMBER);
        }

        if ($waNumber->isCloud()) {
            // Cloud: the business number cannot message itself, and reminders are
            // business-initiated, so they go to the verified owner phone via a template.
            if (! $waNumber->hasVerifiedOwnerPhone()) {
                $response = WhatsappSendResult::failed(self::ERROR_OWNER_PHONE_UNVERIFIED);
            } else {
                $dateTime = $reminder->event_at ?? $reminder->reminder_at;
                $response = app(WhatsappTemplateSender::class)->send($waNumber, 'reminder', $waNumber->owner_phone, [
                    'title' => $reminder->title,
                    'datetime' => $dateTime->format('l, d M Y \a\t g:i A'),
                    'location' => $reminder->location,
                    'description' => $reminder->description,
                    'owner' => $reminder->user?->name,
                ]);
            }
        } else {
            $message = $this->formatMessage($reminder);

            // Send to the user's own phone number
            $to = $waNumber->phone_number;
            $response = $this->whatsapp->sendMessage($waNumber, $to, $message);
        }

        if ($response && $response->successful()) {
            $reminder->update([
                'status' => 'sent',
                'sent_at' => now(),
                'wa_message_id' => $response->json('message_id'),
                'whatsapp_number_id' => $waNumber->id,
            ]);
            ReminderLog::create([
                'reminder_id' => $reminder->id,
                'action' => 'sent',
                'details' => "Sent via WA #{$waNumber->id}",
            ]);

            // Send email notification when reminder fires
            if ($reminder->notify_email) {
                Mail::to($reminder->notify_email)->queue(new ReminderEmailNotification($reminder, 'due'));
            }

            // Generate next occurrence if recurring
            if ($reminder->recurrence_rule) {
                $this->generateNextRecurrence($reminder);
            }

            return true;
        }

        return $this->markFailed($reminder, $response);
    }

    /**
     * Record a failed send: friendly reason on the reminder, technical detail in the log,
     * notify the owner by email and keep a recurring series alive.
     */
    protected function markFailed(Reminder $reminder, ?object $response, ?string $technical = null): bool
    {
        $reason = $this->failureReason($response, $technical);
        $technical ??= $this->technicalDetail($response);

        $reminder->update([
            'status' => 'failed',
            'error_message' => mb_substr($reason, 0, 255),
        ]);
        ReminderLog::create([
            'reminder_id' => $reminder->id,
            'action' => 'failed',
            'details' => $technical,
        ]);

        if ($reminder->notify_email) {
            Mail::to($reminder->notify_email)->queue(new ReminderEmailNotification($reminder, 'failed', $reason));
        }

        if ($reminder->recurrence_rule) {
            $this->generateNextRecurrence($reminder);
        }

        return false;
    }

    /**
     * Map a failed send to a short, user-facing sentence. Never exposes tokens or raw bodies.
     *
     * @param  object|null  $response  WhatsappSendResult, Http response, or null (service unreachable)
     * @param  string|null  $errorKey  A known error string when there is no response object
     */
    public function failureReason(?object $response, ?string $errorKey = null): string
    {
        if ($errorKey === self::ERROR_NO_CONNECTED_NUMBER) {
            return 'No WhatsApp number is connected. Connect one in WhatsApp settings.';
        }

        if ($response === null) {
            return 'WhatsApp is temporarily unavailable. We could not deliver this reminder.';
        }

        $error = $response instanceof WhatsappSendResult ? (string) $response->error() : '';

        if (str_starts_with($error, WhatsappTemplateSender::ERROR_NO_TEMPLATE_PREFIX)) {
            return 'No approved WhatsApp template is set up yet. Please contact support.';
        }

        if ($error === self::ERROR_OWNER_PHONE_UNVERIFIED) {
            return 'Your WhatsApp phone number is not verified. Verify it in WhatsApp settings.';
        }

        $status = method_exists($response, 'status') ? (int) $response->status() : 0;

        if ($error === 'connection_error' || $status >= 500) {
            return 'WhatsApp is temporarily unavailable. We could not deliver this reminder.';
        }

        if ($response instanceof WhatsappSendResult && $error !== '') {
            return 'WhatsApp rejected this message: '.mb_substr($this->redact($error), 0, 150);
        }

        $message = (string) data_get($response->json(), 'error.message', '');
        $code = data_get($response->json(), 'error.code');
        if ($message !== '') {
            $text = $code ? "({$code}) {$message}" : $message;

            return 'WhatsApp rejected this message: '.mb_substr($this->redact($text), 0, 150);
        }

        return 'WhatsApp could not deliver this reminder. Please try again later.';
    }

    protected function technicalDetail(?object $response): string
    {
        if ($response === null) {
            return 'WhatsApp service unavailable (no response)';
        }

        $status = method_exists($response, 'status') ? (int) $response->status() : 0;
        $body = $this->redact((string) $response->body());

        return mb_substr($status > 0 ? "HTTP {$status}: {$body}" : $body, 0, 500);
    }

    protected function redact(string $text): string
    {
        $text = preg_replace('/Bearer\s+[A-Za-z0-9._~+\/=-]+/i', 'Bearer [redacted]', $text);

        return preg_replace('/\bEAA[A-Za-z0-9_-]{8,}/', '[redacted]', $text);
    }

    public function formatMessage(Reminder $reminder): string
    {
        $lines = [];
        $lines[] = "\xF0\x9F\x94\x94 *{$reminder->title}*";

        $dateTime = $reminder->event_at ?? $reminder->reminder_at;
        $lines[] = "\xF0\x9F\x93\x85 ".$dateTime->format('l, d M Y \a\t g:i A');

        if ($reminder->location) {
            $lines[] = "\xF0\x9F\x93\x8D ".$reminder->location;
        }

        if ($reminder->description) {
            $lines[] = "\xF0\x9F\x93\x9D ".$reminder->description;
        }

        return implode("\n", $lines);
    }

    public function generateNextRecurrence(Reminder $reminder): ?Reminder
    {
        $rule = $reminder->recurrence_rule;
        $baseDate = $reminder->event_at ?? $reminder->reminder_at;

        $nextDate = match ($rule) {
            'daily' => $baseDate->copy()->addDay(),
            'weekly' => $baseDate->copy()->addWeek(),
            'monthly' => $baseDate->copy()->addMonth(),
            'yearly' => $baseDate->copy()->addYear(),
            default => null,
        };

        if (! $nextDate) {
            return null;
        }

        $nextReminderAt = $reminder->event_at
            ? $nextDate->copy()->subMinutes($reminder->minutes_before)
            : $nextDate;

        // Idempotent: never create the same occurrence twice for one series.
        $existing = Reminder::where('user_id', $reminder->user_id)
            ->where('title', $reminder->title)
            ->where('recurrence_rule', $reminder->recurrence_rule)
            ->where('reminder_at', $nextReminderAt)
            ->where('id', '!=', $reminder->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $nextReminder = Reminder::create([
            'user_id' => $reminder->user_id,
            'whatsapp_number_id' => $reminder->whatsapp_number_id,
            'google_event_id' => $reminder->google_event_id,
            'google_calendar_connection_id' => $reminder->google_calendar_connection_id,
            'title' => $reminder->title,
            'description' => $reminder->description,
            'event_at' => $reminder->event_at ? $nextDate : null,
            'reminder_at' => $reminder->event_at
                ? $nextDate->copy()->subMinutes($reminder->minutes_before)
                : $nextDate,
            'minutes_before' => $reminder->minutes_before,
            'location' => $reminder->location,
            'recurrence_rule' => $reminder->recurrence_rule,
            'source' => $reminder->source,
            'status' => 'pending',
        ]);

        ReminderLog::create([
            'reminder_id' => $nextReminder->id,
            'action' => 'created',
            'details' => "Recurring ({$rule}) from reminder #{$reminder->id}",
        ]);

        return $nextReminder;
    }
}
