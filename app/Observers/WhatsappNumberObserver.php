<?php

namespace App\Observers;

use App\Models\WhatsappNumber;
use App\Models\User;
use App\Services\AdminNotificationService;
use Illuminate\Support\Facades\Log;

class WhatsappNumberObserver
{
    /**
     * Handle the WhatsappNumber "updated" event.
     */
    public function updated(WhatsappNumber $whatsappNumber): void
    {
        // Check if status changed to disconnected and the owner is admin
        if ($whatsappNumber->isDirty('status') && $whatsappNumber->status === 'disconnected') {
            $previousStatus = $whatsappNumber->getOriginal('status');

            // Only notify if it was previously connected (not connecting or already disconnected)
            if ($previousStatus === 'connected') {
                $owner = $whatsappNumber->user;

                // Check if owner is admin
                $admin = User::where('email', 'admin@blaster.com')->first();

                if ($admin && $owner && $owner->id === $admin->id) {
                    try {
                        // For admin's device disconnection, we can't use WhatsApp (it's disconnected!)
                        // So we just queue it and hope another device sends it, or log it prominently
                        Log::critical('ADMIN WHATSAPP DEVICE DISCONNECTED', [
                            'device_id' => $whatsappNumber->id,
                            'phone_number' => $whatsappNumber->phone_number,
                            'user_id' => $owner->id,
                        ]);

                        // Still queue it in case there are multiple devices
                        $notificationService = new AdminNotificationService();
                        $notificationService->queueNotification('whatsapp_disconnected', $owner->id, [
                            'device_id' => $whatsappNumber->id,
                            'phone_number' => $whatsappNumber->phone_number ?? 'Unknown',
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to queue WhatsApp disconnection notification', [
                            'device_id' => $whatsappNumber->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        }
    }
}
