<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendWhatsappMessages extends Command
{
    protected $signature = 'whatsapp:send-messages';
    protected $description = 'Send pending WhatsApp messages from the campaign queue';

    public function handle()
    {
        $messages = \App\Models\CampaignMessage::where('status', 'pending')
            ->whereHas('campaign', function ($query) {
                $query->where('scheduled_at', '<=', now())
                      ->orWhereNull('scheduled_at');
            })
            ->with(['campaign', 'contact'])
            ->limit(30)
            ->get();

        if ($messages->isEmpty()) {
            $this->info('No pending messages to send.');
            return 0;
        }

        foreach ($messages as $message) {
            $this->info("Sending message to {$message->contact->phone_number}...");

            // Logic to send via Baileys will go here
            // For now, we simulate success
            
            $message->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            // Increment usage in subscription
            $subscription = $message->campaign->user->activeSubscription;
            if ($subscription) {
                $subscription->increment('messages_used_this_month');
            }

            $delay = rand(5, 15);
            $this->info("Waiting {$delay} seconds to prevent ban...");
            sleep($delay);
        }

        $this->info('Batch completed.');
        return 0;
    }
}
