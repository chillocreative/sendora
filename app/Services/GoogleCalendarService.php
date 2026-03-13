<?php

namespace App\Services;

use App\Models\GoogleCalendarConnection;
use App\Models\Reminder;
use App\Models\User;
use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendar;
use Google\Service\Calendar\Event as GoogleEvent;
use Google\Service\Oauth2 as GoogleOauth2;
use Illuminate\Support\Facades\Log;

class GoogleCalendarService
{
    protected function getClient(): GoogleClient
    {
        $client = new GoogleClient();
        $client->setClientId(config('google.client_id'));
        $client->setClientSecret(config('google.client_secret'));
        $client->setRedirectUri(config('google.redirect_uri'));
        $client->setScopes(config('google.scopes'));
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        return $client;
    }

    public function getAuthUrl(): string
    {
        return $this->getClient()->createAuthUrl();
    }

    public function handleCallback(User $user, string $code): GoogleCalendarConnection
    {
        $client = $this->getClient();
        $token = $client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            throw new \RuntimeException('Google OAuth error: ' . ($token['error_description'] ?? $token['error']));
        }

        if (empty($token['refresh_token'])) {
            Log::warning('Google OAuth did not return a refresh_token', ['user_id' => $user->id]);
        }

        $client->setAccessToken($token);

        // Get user email
        $oauth2 = new GoogleOauth2($client);
        $userInfo = $oauth2->userinfo->get();

        return GoogleCalendarConnection::updateOrCreate(
            ['user_id' => $user->id],
            [
                'google_email' => $userInfo->email,
                'access_token' => $token['access_token'],
                'refresh_token' => $token['refresh_token'] ?? '',
                'token_expires_at' => now()->addSeconds($token['expires_in'] ?? 3600),
                'sync_enabled' => true,
            ]
        );
    }

    public function refreshTokenIfNeeded(GoogleCalendarConnection $conn): GoogleClient
    {
        $client = $this->getClient();

        if ($conn->isTokenExpired()) {
            $newToken = $client->fetchAccessTokenWithRefreshToken($conn->refresh_token);

            if (isset($newToken['error'])) {
                Log::error('Google token refresh failed', ['error' => $newToken['error_description'] ?? $newToken['error']]);
                throw new \RuntimeException('Failed to refresh Google token: ' . ($newToken['error_description'] ?? $newToken['error']));
            }

            $conn->update([
                'access_token' => $newToken['access_token'],
                'token_expires_at' => now()->addSeconds($newToken['expires_in'] ?? 3600),
                'refresh_token' => $newToken['refresh_token'] ?? $conn->refresh_token,
            ]);
            $client->setAccessToken($newToken);
        } else {
            $client->setAccessToken([
                'access_token' => $conn->access_token,
                'refresh_token' => $conn->refresh_token,
                'created' => $conn->token_expires_at->subSeconds(3600)->timestamp,
                'expires_in' => 3600,
            ]);
        }

        return $client;
    }

    /**
     * Sync upcoming events from Google Calendar into reminders.
     *
     * Uses a time-bounded full query each time (singleEvents=true) rather than
     * incremental sync tokens, because syncToken is incompatible with
     * singleEvents/orderBy/timeMin/timeMax per Google's API docs.
     * Events are matched by google_event_id to avoid duplicates.
     */
    public function syncEvents(GoogleCalendarConnection $conn): int
    {
        $client = $this->refreshTokenIfNeeded($conn);
        $calendar = new GoogleCalendar($client);

        $syncedCount = 0;
        $pageToken = null;

        try {
            do {
                $params = [
                    'singleEvents' => true,
                    'orderBy' => 'startTime',
                    'timeMin' => now()->toRfc3339String(),
                    'timeMax' => now()->addMonths(3)->toRfc3339String(),
                    'maxResults' => 250,
                ];

                if ($pageToken) {
                    $params['pageToken'] = $pageToken;
                }

                $events = $calendar->events->listEvents($conn->calendar_id, $params);

                foreach ($events->getItems() as $event) {
                    if ($event->getStatus() === 'cancelled') {
                        Reminder::where('google_event_id', $event->getId())
                            ->where('user_id', $conn->user_id)
                            ->where('status', 'pending')
                            ->update(['status' => 'cancelled']);
                        continue;
                    }

                    $startDateTime = $event->getStart()->getDateTime()
                        ?? $event->getStart()->getDate() . 'T09:00:00';

                    $eventAt = \Carbon\Carbon::parse($startDateTime);

                    $existing = Reminder::where('google_event_id', $event->getId())
                        ->where('user_id', $conn->user_id)
                        ->first();

                    if ($existing) {
                        $existing->update([
                            'title' => $event->getSummary() ?? 'Untitled Event',
                            'description' => $event->getDescription(),
                            'location' => $event->getLocation(),
                            'event_at' => $eventAt,
                            'reminder_at' => $eventAt->copy()->subMinutes($existing->minutes_before),
                        ]);
                    } else {
                        $waNumber = $conn->user->whatsappNumbers()
                            ->where('status', 'connected')
                            ->first();

                        Reminder::create([
                            'user_id' => $conn->user_id,
                            'whatsapp_number_id' => $waNumber?->id,
                            'google_event_id' => $event->getId(),
                            'google_calendar_connection_id' => $conn->id,
                            'title' => $event->getSummary() ?? 'Untitled Event',
                            'description' => $event->getDescription(),
                            'location' => $event->getLocation(),
                            'event_at' => $eventAt,
                            'reminder_at' => $eventAt->copy()->subMinutes(15),
                            'minutes_before' => 15,
                            'source' => 'google_calendar',
                            'status' => $eventAt->copy()->subMinutes(15)->isPast() ? 'cancelled' : 'pending',
                        ]);
                        $syncedCount++;
                    }
                }

                $pageToken = $events->getNextPageToken();
            } while ($pageToken);

            $conn->update(['last_synced_at' => now()]);
        } catch (\Google\Service\Exception $e) {
            Log::error('Google Calendar sync error', [
                'user_id' => $conn->user_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $syncedCount;
    }

    /**
     * Fetch upcoming birthdays from the user's Google birthday calendar.
     * Returns empty array on any failure (best-effort).
     */
    public function getUpcomingBirthdays(GoogleCalendarConnection $conn, int $days = 7): array
    {
        try {
            $client = $this->refreshTokenIfNeeded($conn);
            $calendar = new GoogleCalendar($client);

            $events = $calendar->events->listEvents(
                '#contacts@group.v.calendar.google.com',
                [
                    'singleEvents' => true,
                    'timeMin' => now()->startOfDay()->toRfc3339String(),
                    'timeMax' => now()->addDays($days)->endOfDay()->toRfc3339String(),
                    'orderBy' => 'startTime',
                    'maxResults' => 50,
                ]
            );

            $birthdays = [];
            foreach ($events->getItems() as $event) {
                $dateStr = $event->getStart()->getDate();
                if (!$dateStr) {
                    continue;
                }
                $birthdays[] = [
                    'name' => $event->getSummary() ?? 'Birthday',
                    'date' => \Carbon\Carbon::parse($dateStr),
                ];
            }

            return $birthdays;
        } catch (\Exception $e) {
            Log::debug('Birthday calendar fetch skipped', ['error' => $e->getMessage()]);

            return [];
        }
    }

    public function createEvent(GoogleCalendarConnection $conn, array $data): ?string
    {
        $client = $this->refreshTokenIfNeeded($conn);
        $calendar = new GoogleCalendar($client);

        $event = new GoogleEvent([
            'summary' => $data['title'],
            'description' => $data['description'] ?? null,
            'location' => $data['location'] ?? null,
            'start' => [
                'dateTime' => \Carbon\Carbon::parse($data['event_at'])->toRfc3339String(),
                'timeZone' => config('app.timezone', 'Asia/Kuala_Lumpur'),
            ],
            'end' => [
                'dateTime' => \Carbon\Carbon::parse($data['event_at'])->addHour()->toRfc3339String(),
                'timeZone' => config('app.timezone', 'Asia/Kuala_Lumpur'),
            ],
            'reminders' => [
                'useDefault' => false,
                'overrides' => [
                    ['method' => 'popup', 'minutes' => $data['minutes_before'] ?? 15],
                ],
            ],
        ]);

        try {
            $created = $calendar->events->insert($conn->calendar_id, $event);
            return $created->getId();
        } catch (\Exception $e) {
            Log::error('Google Calendar create event error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function deleteEvent(GoogleCalendarConnection $conn, string $googleEventId): bool
    {
        try {
            $client = $this->refreshTokenIfNeeded($conn);
            $calendar = new GoogleCalendar($client);
            $calendar->events->delete($conn->calendar_id, $googleEventId);

            return true;
        } catch (\Google\Service\Exception $e) {
            // 404/410 = event already deleted, treat as success
            if (in_array($e->getCode(), [404, 410])) {
                return true;
            }

            Log::error('Google Calendar delete event error', [
                'event_id' => $googleEventId,
                'error' => $e->getMessage(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Google Calendar delete event error', [
                'event_id' => $googleEventId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function disconnect(GoogleCalendarConnection $conn): void
    {
        try {
            $client = $this->getClient();
            $client->setAccessToken(['access_token' => $conn->access_token]);
            $client->revokeToken();
        } catch (\Exception $e) {
            Log::warning('Failed to revoke Google token', ['error' => $e->getMessage()]);
        }

        // Cancel any pending reminders from this calendar
        Reminder::where('google_calendar_connection_id', $conn->id)
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);

        $conn->delete();
    }
}
