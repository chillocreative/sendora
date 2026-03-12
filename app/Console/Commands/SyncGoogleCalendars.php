<?php

namespace App\Console\Commands;

use App\Models\GoogleCalendarConnection;
use App\Services\GoogleCalendarService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncGoogleCalendars extends Command
{
    protected $signature = 'calendars:sync';
    protected $description = 'Sync events from all connected Google Calendars';

    public function handle(GoogleCalendarService $calendarService): int
    {
        $connections = GoogleCalendarConnection::where('sync_enabled', true)->get();

        $totalSynced = 0;

        foreach ($connections as $conn) {
            try {
                $count = $calendarService->syncEvents($conn);
                $totalSynced += $count;
            } catch (\Exception $e) {
                Log::error("Calendar sync failed for user {$conn->user_id}", [
                    'error' => $e->getMessage(),
                ]);
                $this->error("Failed sync for user #{$conn->user_id}: {$e->getMessage()}");
            }
        }

        if ($connections->count() > 0) {
            $this->info("Synced {$totalSynced} new event(s) across {$connections->count()} calendar(s).");
        }

        return self::SUCCESS;
    }
}
