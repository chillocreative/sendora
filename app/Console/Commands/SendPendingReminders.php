<?php

namespace App\Console\Commands;

use App\Jobs\SendReminderJob;
use App\Models\Reminder;
use Illuminate\Console\Command;

class SendPendingReminders extends Command
{
    protected $signature = 'reminders:send';
    protected $description = 'Dispatch jobs for all due reminders';

    public function handle(): int
    {
        $reminders = Reminder::dueNow()->pluck('id');

        foreach ($reminders as $reminderId) {
            SendReminderJob::dispatch($reminderId);
        }

        if ($reminders->count() > 0) {
            $this->info("Dispatched {$reminders->count()} reminder(s).");
        }

        return self::SUCCESS;
    }
}
