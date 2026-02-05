<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UserSubscription;
use App\Services\AdminNotificationService;
use Illuminate\Support\Facades\Log;

class CheckExpiringSubscriptions extends Command
{
    protected $signature = 'subscriptions:check-expiring';
    protected $description = 'Check for subscriptions expiring soon and notify admin';

    public function handle()
    {
        $this->info('Checking for expiring subscriptions...');

        // Find subscriptions expiring in 7 days
        $expiringSubscriptions = UserSubscription::with(['user', 'plan'])
            ->where('status', 'active')
            ->whereNotNull('ends_at')
            ->whereBetween('ends_at', [now()->addDays(7), now()->addDays(8)])
            ->get();

        $notificationService = new AdminNotificationService();
        $notified = 0;

        foreach ($expiringSubscriptions as $subscription) {
            try {
                $daysRemaining = now()->diffInDays($subscription->ends_at);

                $notificationService->sendNotification('subscription_expiring', $subscription->user_id, [
                    'plan_name' => $subscription->plan->name ?? 'Unknown',
                    'ends_at' => $subscription->ends_at->format('M d, Y'),
                    'days_remaining' => $daysRemaining,
                ]);

                $notified++;
            } catch (\Exception $e) {
                Log::error('Failed to send expiring subscription notification', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Notified about {$notified} expiring subscriptions.");

        return 0;
    }
}
