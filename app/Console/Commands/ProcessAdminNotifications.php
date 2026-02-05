<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AdminNotificationService;

class ProcessAdminNotifications extends Command
{
    protected $signature = 'admin:process-notifications';
    protected $description = 'Process pending admin notifications and send via WhatsApp';

    public function handle()
    {
        $this->info('Processing admin notifications...');

        $service = new AdminNotificationService();
        $result = $service->sendPendingNotifications();

        if ($result['success']) {
            $this->info("Processed: {$result['processed']}, Failed: {$result['failed']}");
        } else {
            $this->warn($result['message']);
        }

        $pending = $service->getPendingCount();
        $failed = $service->getFailedCount();

        $this->info("Remaining - Pending: {$pending}, Failed: {$failed}");

        return 0;
    }
}
