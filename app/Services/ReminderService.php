<?php

namespace App\Services;

use App\Mail\ReminderEmailNotification;
use App\Models\Reminder;
use App\Models\ReminderLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class ReminderService
{
    protected WhatsappService $whatsapp;

    protected GoogleCalendarService $calendarService;

    public function __construct(WhatsappService $whatsapp, GoogleCalendarService $calendarService)
    {
        $this->whatsapp = $whatsapp;
        $this->calendarService = $calendarService;
    }

    public function createReminder(User $user, array $data): Reminder
    {
        $eventAt = Carbon::parse($data['event_at'] ?? $data['reminder_at']);
        $minutesBefore = (int) ($data['minutes_before'] ?? 15);
        $reminderAt = isset($data['event_at'])
            ? $eventAt->copy()->subMinutes($minutesBefore)
            : Carbon::parse($data['reminder_at']);

        $reminder = Reminder::create([
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
                ]);

                if ($googleEventId) {
                    $reminder->update([
                        'google_event_id' => $googleEventId,
                        'google_calendar_connection_id' => $conn->id,
                    ]);
                }
            }
        }

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
            $reminder->update([
                'status' => 'failed',
                'error_message' => 'No connected WhatsApp number available',
            ]);
            ReminderLog::create([
                'reminder_id' => $reminder->id,
                'action' => 'failed',
                'details' => 'No connected WhatsApp number',
            ]);

            return false;
        }

        $message = $this->formatMessage($reminder);

        // Send to the user's own phone number
        $to = $waNumber->phone_number;
        $response = $this->whatsapp->sendMessage($waNumber, $to, $message);

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

        $errorMsg = $response ? $response->body() : 'WhatsApp service unavailable';
        $reminder->update([
            'status' => 'failed',
            'error_message' => $errorMsg,
        ]);
        ReminderLog::create([
            'reminder_id' => $reminder->id,
            'action' => 'failed',
            'details' => $errorMsg,
        ]);

        return false;
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
