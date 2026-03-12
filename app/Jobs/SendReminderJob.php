<?php

namespace App\Jobs;

use App\Models\Reminder;
use App\Services\ReminderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        protected int $reminderId
    ) {}

    public function handle(ReminderService $reminderService): void
    {
        $reminder = Reminder::with(['user', 'whatsappNumber'])->find($this->reminderId);

        if (!$reminder || $reminder->status !== 'pending') {
            return;
        }

        $reminderService->sendReminder($reminder);
    }
}
