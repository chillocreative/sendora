<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\CampaignMessage;
use App\Services\WhatsappService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $campaign;
    protected $waServerUrl;

    public function __construct(Campaign $campaign)
    {
        $this->campaign = $campaign;
    }

    public function handle()
    {
        $campaign = $this->campaign;

        // Skip if not pending or scheduled (or already processing)
        if (!in_array($campaign->status, ['pending', 'scheduled', 'processing'])) {
            return;
        }

        $campaign->update(['status' => 'processing']);

        $user = $campaign->user;
        
        // Use database setting for APP_URL if it exists (fixes asset() in production)
        $appUrl = \App\Models\Setting::where('key', 'app_url')->value('value') ?? config('app.url');
        config(['app.url' => $appUrl]);

        $whatsappNumber = $user->whatsappNumbers()->where('status', 'connected')->first();

        if (!$whatsappNumber) {
            $campaign->update(['status' => 'failed']);
            Log::error("Campaign {$campaign->id} failed: No connected WhatsApp number found for user {$user->id}.");
            return;
        }

        $whatsappService = new WhatsappService();
        $messages = CampaignMessage::where('campaign_id', $campaign->id)
            ->where('status', 'pending')
            ->orderBy('sequence_order')
            ->with('contact')
            ->get();

        Log::info("Starting campaign: {$campaign->name} (#{$campaign->id}) with " . $messages->count() . " messages.");

        // Determine delay between messages (drip sequence)
        $delaySeconds = $campaign->is_drip ? ($campaign->drip_delay_minutes * 60) : 0.5;

        foreach ($messages as $index => $message) {
            // Check if campaign was cancelled in the meantime
            $campaign->refresh();
            if ($campaign->status === 'cancelled') {
                break;
            }

            // Ensure we use the correct asset URL for media
            $mediaUrl = null;
            if ($campaign->media_path) {
                $path = $campaign->media_path;
                // If it's already a full URL, use it, otherwise use asset()
                $mediaUrl = str_starts_with($path, 'http') ? $path : asset('storage/' . $path);
            }
            
            $mediaType = $campaign->message_type;
            $body = $campaign->body;

            // Link Tracking Logic
            $urlRegex = '/(https?:\/\/[^\s]+)/';
            $body = preg_replace_callback($urlRegex, function($matches) use ($message) {
                $originalUrl = $matches[1];
                $trackingUrl = route('link.track', [
                    'messageId' => $message->id,
                    'u' => base64_encode($originalUrl)
                ]);
                return $trackingUrl;
            }, $body);

            $response = $whatsappService->sendMessage(
                $whatsappNumber,
                $message->contact->phone_number,
                $body,
                $mediaUrl,
                $mediaType
            );

            if ($response && $response->successful()) {
                $waMessageId = $response->json('message_id');
                $message->update([
                    'status' => 'sent',
                    'wa_message_id' => $waMessageId,
                    'sent_at' => now(),
                ]);
            } else {
                $message->update(['status' => 'failed']);
            }

            // Apply delay between messages
            // For drip campaigns, use the configured delay
            // For regular campaigns, use a small delay to prevent rate limiting
            if ($index < $messages->count() - 1) {
                if ($campaign->is_drip) {
                    // For drip campaigns, use full configured delay
                    sleep($delaySeconds);
                } else {
                    // Human-Stagger Technology: Variable delay to mimic natural behavior
                    // If Warmer Mode is enabled, use extended delays (15-30s)
                    if ($user->warmer_enabled) {
                        $randomDelay = rand(15, 30);
                    } else {
                        // Standard variable delay (2-6s)
                        $randomDelay = rand(2, 6);
                    }
                    sleep($randomDelay);
                }
            }
        }

        $campaign->update(['status' => 'completed']);
    }
}
