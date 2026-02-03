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
        $waServerUrl = env('WA_SERVER_URL', 'http://localhost:3000');
        
        $payload = [
            'user_id' => $whatsappNumber->user_id,
            'phone_number' => $whatsappNumber->id,
            'to' => $to,
            'message' => $message ?? '',
        ];

        if ($mediaUrl) {
            $payload['media_url'] = $mediaUrl;
            $payload['media_type'] = $mediaType;
        }

        try {
            $response = Http::timeout(20)->post("{$waServerUrl}/send-message", $payload);
            return $response;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('WhatsappService send error: ' . $e->getMessage());
            return null;
        }
    }
}
