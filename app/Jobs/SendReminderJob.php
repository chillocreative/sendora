<?php

namespace App\Jobs;

use App\Models\Reminder;
use App\Services\ReminderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SendReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // sendReminder() records failures itself and never throws, and a retry after a
    // send that Meta already accepted would deliver the reminder twice.
    public int $tries = 1;

    public function __construct(
        protected int $reminderId
    ) {}

    public function handle(ReminderService $reminderService): void
    {
        // The scheduler dispatches every minute, so two jobs for the same reminder can
        // overlap (slow send, queue backlog, several workers). Only one may send.
        $lock = Cache::lock("send-reminder:{$this->reminderId}", 120);

        if (! $lock->get()) {
            return;
        }

        try {
            $reminder = Reminder::with(['user', 'whatsappNumber'])->find($this->reminderId);

            if (! $reminder || $reminder->status !== 'pending') {
                return;
            }

            $reminderService->sendReminder($reminder);
        } finally {
            $lock->release();
        }
    }
}
