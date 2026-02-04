<?php

namespace App\Services;

use App\Models\WhatsappNumber;
use Illuminate\Support\Facades\Http;

class WhatsappService
{
    /**
     * Get the status of a WhatsApp session
     */
    public function getStatus(WhatsappNumber $whatsappNumber)
    {
        // Integration with Baileys node service would happen here
        return $whatsappNumber->status;
    }

    /**
     * Generate a pairing QR code for a session
     */
    public function generateQrCode(WhatsappNumber $whatsappNumber)
    {
        // This would call the Node.js bridge to initiate a session and return a QR string
        return "simulate_qr_code_data_" . $whatsappNumber->id;
    }

    /**
     * Send a message through a specific WhatsApp session
     */
    public function sendMessage(WhatsappNumber $whatsappNumber, $to, $message, $mediaUrl = null, $mediaType = null)
    {
        $waServerUrl = \App\Models\Setting::where('key', 'wa_server_url')->value('value') 
                       ?? env('WA_SERVER_URL', 'http://localhost:3000');
        $waServerUrl = rtrim($waServerUrl, '/');
        
        // Clean the recipient number
        $to = preg_replace('/[^0-9]/', '', $to);
        if (str_starts_with($to, '0')) {
            $to = '6' . $to; // Default to Malaysia country code if starting with 0
        }

        $payload = [
            'user_id' => $whatsappNumber->user_id,
            'phone_number' => $whatsappNumber->id,
            'to' => $to,
            'message' => $message ?? '',
        ];

        if ($mediaUrl) {
            // Ensure media URL is absolute
            if (!str_starts_with($mediaUrl, 'http')) {
                $appUrl = \App\Models\Setting::where('key', 'app_url')->value('value') ?? config('app.url');
                $appUrl = rtrim($appUrl, '/');
                $mediaUrl = $appUrl . '/' . ltrim($mediaUrl, '/');
            }
            $payload['media_url'] = $mediaUrl;
            $payload['media_type'] = $mediaType;
        }

        try {
            $response = Http::withoutVerifying()->timeout(35)->post("{$waServerUrl}/send-message", $payload);
            
            if ($response->failed()) {
                \Illuminate\Support\Facades\Log::error('WhatsappService send failed', [
                    'url' => "{$waServerUrl}/send-message",
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'payload' => $payload
                ]);
            }
            
            return $response;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('WhatsappService send error', [
                'url' => "{$waServerUrl}/send-message",
                'message' => $e->getMessage(),
                'payload' => $payload
            ]);
            return null;
        }
    }
}
