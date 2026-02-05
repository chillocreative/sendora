<?php

namespace App\Services;

use App\Models\AdminNotification;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class AdminNotificationService
{
    private WhatsappService $whatsappService;

    public function __construct()
    {
        $this->whatsappService = new WhatsappService();
    }

    /**
     * Queue a notification for admin
     */
    public function queueNotification(string $type, ?int $userId, array $data): AdminNotification
    {
        return AdminNotification::create([
            'notification_type' => $type,
            'user_id' => $userId,
            'data' => $data,
        ]);
    }

    /**
     * Send a notification immediately or queue if WhatsApp is unavailable
     */
    public function sendNotification(string $type, ?int $userId, array $data): bool
    {
        // Queue the notification first
        $notification = $this->queueNotification($type, $userId, $data);

        // Try to send immediately
        return $this->processSingleNotification($notification);
    }

    /**
     * Process all pending notifications
     */
    public function sendPendingNotifications(): array
    {
        $device = $this->getAdminWhatsappDevice();

        if (!$device) {
            Log::info('Admin notification: No connected WhatsApp device, keeping notifications queued');
            return [
                'success' => false,
                'message' => 'No connected WhatsApp device',
                'processed' => 0,
            ];
        }

        $notifications = AdminNotification::pending()
            ->orderBy('created_at')
            ->limit(50) // Process in batches
            ->get();

        $processed = 0;
        $failed = 0;

        foreach ($notifications as $notification) {
            if ($this->processSingleNotification($notification)) {
                $processed++;
            } else {
                $failed++;
            }
        }

        Log::info('Admin notifications processed', [
            'processed' => $processed,
            'failed' => $failed,
        ]);

        return [
            'success' => true,
            'processed' => $processed,
            'failed' => $failed,
        ];
    }

    /**
     * Process a single notification
     */
    private function processSingleNotification(AdminNotification $notification): bool
    {
        try {
            $device = $this->getAdminWhatsappDevice();

            if (!$device) {
                Log::warning('Admin notification queued: No connected device', [
                    'type' => $notification->notification_type,
                    'id' => $notification->id,
                ]);
                return false;
            }

            $adminMobile = $this->getAdminMobileNumber($device);

            if (!$adminMobile) {
                $notification->incrementFailure('No admin mobile number available');
                return false;
            }

            $message = $this->formatMessage($notification);

            $response = $this->whatsappService->sendMessage($device, $adminMobile, $message);

            if ($response) {
                $notification->markAsSent();
                Log::info('Admin notification sent', [
                    'type' => $notification->notification_type,
                    'id' => $notification->id,
                    'to' => $adminMobile,
                ]);
                return true;
            } else {
                $notification->incrementFailure('WhatsApp send failed');
                return false;
            }
        } catch (\Exception $e) {
            $notification->incrementFailure($e->getMessage());
            Log::error('Failed to send admin notification', [
                'id' => $notification->id,
                'type' => $notification->notification_type,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Format notification message based on type
     */
    private function formatMessage(AdminNotification $notification): string
    {
        $data = $notification->data;
        $user = $notification->user;

        switch ($notification->notification_type) {
            case 'payment':
                return "[SENDORA PAYMENT]\n\n"
                    . "💰 New payment received!\n"
                    . "Customer: {$user->name}\n"
                    . "Plan: {$data['plan_name']}\n"
                    . "Amount: {$data['currency']} {$data['amount']}\n"
                    . "Billing: {$data['billing_cycle']}\n"
                    . "Transaction ID: {$data['transaction_id']}";

            case 'ticket':
                return "[SENDORA TICKET #{$data['ticket_id']}]\n\n"
                    . "🎫 New support ticket\n"
                    . "From: {$user->name}\n"
                    . "Subject: {$data['subject']}\n"
                    . "Priority: {$data['priority']}\n\n"
                    . substr($data['description'], 0, 200)
                    . (strlen($data['description']) > 200 ? '...' : '');

            case 'cancellation':
                return "[SENDORA CANCELLATION]\n\n"
                    . "❌ Subscription cancelled\n"
                    . "Customer: {$user->name}\n"
                    . "Plan: {$data['plan_name']}\n"
                    . "Access until: {$data['ends_at']}";

            case 'registration':
                return "[SENDORA REGISTRATION]\n\n"
                    . "👤 New user registered\n"
                    . "Name: {$user->name}\n"
                    . "Email: {$user->email}\n"
                    . "Plan: {$data['plan_name']}";

            case 'subscription_expiring':
                return "[SENDORA EXPIRY WARNING]\n\n"
                    . "⚠️ Subscription expiring soon\n"
                    . "Customer: {$user->name}\n"
                    . "Plan: {$data['plan_name']}\n"
                    . "Expires: {$data['ends_at']}\n"
                    . "Days remaining: {$data['days_remaining']}";

            case 'whatsapp_disconnected':
                return "[SENDORA ALERT]\n\n"
                    . "🔴 WhatsApp device disconnected\n"
                    . "User: {$user->name}\n"
                    . "Device ID: {$data['device_id']}\n"
                    . "Phone: {$data['phone_number']}";

            default:
                return "[SENDORA NOTIFICATION]\n\n"
                    . "Type: {$notification->notification_type}\n"
                    . "User: " . ($user ? $user->name : 'System') . "\n"
                    . json_encode($data, JSON_PRETTY_PRINT);
        }
    }

    /**
     * Get admin's WhatsApp device
     */
    private function getAdminWhatsappDevice()
    {
        $admin = User::where('email', 'admin@blaster.com')->first();

        if (!$admin) {
            return null;
        }

        return $admin->whatsappNumbers()->where('status', 'connected')->first();
    }

    /**
     * Get admin mobile number
     */
    private function getAdminMobileNumber($device): ?string
    {
        $adminMobile = Setting::where('key', 'admin_mobile_number')->value('value');

        if (!$adminMobile && $device->phone_number) {
            // Extract numeric part from device phone_number (self-send)
            $adminMobile = preg_replace('/[^0-9].*/', '', $device->phone_number);
        }

        return $adminMobile ?: null;
    }

    /**
     * Get pending notification count
     */
    public function getPendingCount(): int
    {
        return AdminNotification::pending()->count();
    }

    /**
     * Get failed notification count
     */
    public function getFailedCount(): int
    {
        return AdminNotification::failed()->count();
    }
}
