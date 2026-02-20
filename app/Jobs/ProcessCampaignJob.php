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
use Illuminate\Support\Facades\URL;

class ProcessCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $campaign;
    protected $waServerUrl;

    /**
     * The number of seconds the job can run before timing out.
     * Campaigns with many contacts and sleep delays can take hours.
     */
    public $timeout = 7200;

    /**
     * The number of times the job may be attempted.
     * Set to 1 to prevent duplicate message sends on retry.
     */
    public $tries = 1;

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
        
        // Use database setting for APP_URL if it exists.
        // We MUST call URL::forceRootUrl() because the UrlGenerator singleton caches
        // its root at queue-worker startup (usually http://localhost), so config() alone
        // does not fix asset() / route() URL generation in queue context.
        $appUrl = \App\Models\Setting::where('key', 'app_url')->value('value') ?? config('app.url');
        $appUrl = rtrim($appUrl, '/');
        config(['app.url' => $appUrl]);
        URL::forceRootUrl($appUrl);

        // Use campaign's assigned number if available
        if ($campaign->whatsapp_number_id) {
            $whatsappNumber = $user->whatsappNumbers()
                ->where('id', $campaign->whatsapp_number_id)
                ->where('status', 'connected')
                ->first();
        } else {
            $whatsappNumber = $user->whatsappNumbers()->where('status', 'connected')->first();
        }

        if (!$whatsappNumber) {
            $campaign->update(['status' => 'failed']);
            Log::error("Campaign {$campaign->id} failed: No connected WhatsApp number found for user {$user->id}. Selected Number ID: " . ($campaign->whatsapp_number_id ?? 'None'));
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

            // Build the absolute media URL directly from $appUrl.
            // Do NOT use asset() here — the UrlGenerator caches its root URL at worker
            // startup and asset() would generate http://localhost/storage/... even after
            // URL::forceRootUrl(). Direct string construction is always correct.
            $mediaUrl = null;
            if ($campaign->media_path) {
                $path = $campaign->media_path;
                if (str_starts_with($path, 'http')) {
                    $mediaUrl = $path;
                } else {
                    // URL-encode each path segment so filenames with spaces or special
                    // characters produce a valid HTTP URL that Node.js can download.
                    $segments = array_map('rawurlencode', explode('/', ltrim($path, '/')));
                    $mediaUrl = $appUrl . '/storage/' . implode('/', $segments);
                }
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
                $errorCode = $response ? $response->status() : 'TIMEOUT';
                $errorMsg = $response ? $response->body() : 'No response from WhatsApp server';
                Log::warning("Campaign {$campaign->id} message to {$message->contact->phone_number} failed: [{$errorCode}] {$errorMsg}");
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

        // Only mark as completed if not cancelled during processing
        $campaign->refresh();
        if ($campaign->status === 'processing') {
            $campaign->update(['status' => 'completed']);
        }
    }
}
