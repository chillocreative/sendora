<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WhatsappNumber;
use App\Models\AutoReply;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookController extends Controller
{
    public function qrUpdate(Request $request)
    {
        Log::info('QR Update Webhook Called', [
            'user_id' => $request->user_id,
            'phone_number' => $request->phone_number,
            'status' => $request->status,
            'has_qr' => !empty($request->qr_code),
        ]);

        try {
            $number = WhatsappNumber::where('user_id', $request->user_id)
                ->where('id', $request->phone_number)
                ->first();

            if ($number) {
                $number->update([
                    'qr_code' => $request->qr_code,
                    'status' => $request->status,
                ]);
                Log::info('QR Code Updated Successfully', ['number_id' => $number->id]);
            } else {
                Log::warning('WhatsApp Number Not Found - Killing Rogue Session', [
                    'user_id' => $request->user_id,
                    'phone_number' => $request->phone_number,
                ]);

                // KILL ROGUE SESSION: Tell the node server to stop this connection
                try {
                    $waServerUrl = \App\Models\Setting::where('key', 'wa_server_url')->value('value') 
                                   ?? env('WA_SERVER_URL', 'http://localhost:3000');
                    $waServerUrl = rtrim($waServerUrl, '/');
                    \Illuminate\Support\Facades\Http::post("{$waServerUrl}/disconnect", [
                        'user_id' => $request->user_id,
                        'phone_number' => $request->phone_number,
                    ]);
                } catch (\Exception $e) {
                    // ignore
                }
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('QR Update Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function statusUpdate(Request $request)
    {
        try {
            $number = WhatsappNumber::where('user_id', $request->user_id)
                ->where('id', $request->phone_number)
                ->first();

            if ($number) {
                $updateData = [
                    'status' => $request->status,
                ];

                if ($request->status === 'connected' && $request->phone_info) {
                    $updateData['phone_info'] = $request->phone_info;
                    $updateData['phone_number'] = $request->phone_info['id'] ?? null;
                }

                if ($request->status === 'disconnected') {
                    $updateData['qr_code'] = null;
                }

                $number->update($updateData);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Status Update Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function incomingMessage(Request $request)
    {
        Log::info('Incoming Message Webhook Called', [
            'user_id' => $request->user_id,
            'from' => $request->from,
            'message' => $request->message,
        ]);

        try {
            // Check for auto-replies with match type logic
            $message = strtolower(trim($request->message));

            $autoReply = AutoReply::where('user_id', $request->user_id)
                ->where('is_active', true)
                ->get()
                ->first(function ($reply) use ($message) {
                    $keyword = strtolower($reply->keyword);

                    if ($reply->match_type === 'exact') {
                        // Exact match
                        return $message === $keyword;
                    } else {
                        // Contains match (default)
                        return str_contains($message, $keyword);
                    }
                });

            if ($autoReply) {
                Log::info('Auto-reply match found!', ['reply' => $autoReply->reply_message]);
                
                // Send auto-reply via WhatsApp server
                $waServerUrl = \App\Models\Setting::where('key', 'wa_server_url')->value('value') 
                               ?? env('WA_SERVER_URL', 'http://localhost:3000');
                $waServerUrl = rtrim($waServerUrl, '/');
                
                $response = \Illuminate\Support\Facades\Http::post("{$waServerUrl}/send-message", [
                    'user_id' => $request->user_id,
                    'phone_number' => $request->phone_number,
                    'to' => $request->from,
                    'message' => $autoReply->reply_message,
                ]);

                if (!$response->successful()) {
                    Log::error('Failed to send auto-reply', ['error' => $response->body()]);
                }
            } else {
                Log::info('No auto-reply match found for keyword: ' . $request->message);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Incoming Message Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function messageReceipt(Request $request)
    {
        // status can be: 2 (delivered), 3 (read)
        $waMessageId = $request->message_id;
        $status = $request->status;

        try {
            $message = \App\Models\CampaignMessage::where('wa_message_id', $waMessageId)->first();

            if ($message) {
                if ($status == 2) { // Delivered
                    $message->update([
                        'status' => 'delivered',
                        'delivered_at' => now(),
                    ]);
                } elseif ($status == 3) { // Read
                    $message->update([
                        'status' => 'read',
                        'read_at' => now(),
                    ]);
                }
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Message Receipt Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
