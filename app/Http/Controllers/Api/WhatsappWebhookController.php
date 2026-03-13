<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessAiReplyJob;
use App\Jobs\ProcessMediaReminderJob;
use App\Jobs\ProcessSendoraCommandJob;
use App\Models\WhatsappNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookController extends Controller
{
    public function qrUpdate(Request $request)
    {
        Log::info('QR Update Webhook Called', [
            'user_id' => $request->user_id,
            'phone_number' => $request->phone_number,
            'status' => $request->status,
            'has_qr' => ! empty($request->qr_code),
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
                                   ?? env('WA_SERVER_URL', 'http://127.0.0.1:3005');
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
            Log::error('QR Update Error: '.$e->getMessage());

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

                    // Clean phone number from JID format (e.g. "60148885659:55@s.whatsapp.net" → "60148885659")
                    $rawId = $request->phone_info['id'] ?? null;
                    if ($rawId) {
                        $cleanPhone = preg_replace('/:.*$/', '', $rawId); // strip :device suffix
                        $cleanPhone = str_replace('@s.whatsapp.net', '', $cleanPhone);
                        $cleanPhone = preg_replace('/[^0-9]/', '', $cleanPhone);
                        $updateData['phone_number'] = $cleanPhone;
                    }
                }

                if ($request->status === 'disconnected') {
                    $updateData['qr_code'] = null;
                }

                $number->update($updateData);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Status Update Error: '.$e->getMessage());

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function incomingMessage(Request $request)
    {
        Log::info('Incoming Message Webhook', [
            'user_id' => $request->user_id,
            'from' => $request->from,
            'message' => substr($request->message ?? '', 0, 100),
            'has_media' => ! empty($request->media_base64),
        ]);

        try {
            if (empty($request->user_id) || empty($request->from) || (empty($request->message) && empty($request->media_base64))) {
                return response()->json(['success' => false, 'error' => 'Missing required fields'], 422);
            }

            // Verify the WhatsApp number exists and is connected
            $whatsappNumber = WhatsappNumber::where('user_id', $request->user_id)
                ->where('id', $request->phone_number)
                ->where('status', 'connected')
                ->first();

            if (! $whatsappNumber) {
                Log::warning('Incoming message for unknown/disconnected number', [
                    'user_id' => $request->user_id,
                    'phone_number' => $request->phone_number,
                ]);

                return response()->json(['success' => false, 'error' => 'Number not found or disconnected'], 404);
            }

            // Skip group messages
            if (str_contains($request->from, '@g.us')) {
                return response()->json(['success' => true, 'skipped' => 'group_message']);
            }

            $messageText = (string) ($request->message ?? '');

            // Self-sent media with /sendora caption → extract event
            if ($request->media_base64 && $this->isSelfMessage($request, $whatsappNumber)
                && str_starts_with(strtolower(trim($messageText)), '/sendora')) {
                ProcessMediaReminderJob::dispatch(
                    (int) $request->user_id,
                    (int) $request->phone_number,
                    (string) $request->from,
                    $request->media_base64,
                    $request->media_mimetype ?? 'application/octet-stream',
                    $request->media_filename,
                    $messageText ?: null,
                    $request->message_id ?? null
                );

                return response()->json(['success' => true, 'queued' => true, 'type' => 'media_reminder']);
            }

            // Check for /sendora command
            if (str_starts_with(strtolower(trim($messageText)), '/sendora')) {
                ProcessSendoraCommandJob::dispatch(
                    (int) $request->user_id,
                    (int) $request->phone_number,
                    (string) $request->from,
                    $messageText,
                    $request->message_id ?? null
                );

                return response()->json(['success' => true, 'queued' => true, 'type' => 'sendora']);
            }

            // Dispatch AI reply job to queue
            ProcessAiReplyJob::dispatch(
                (int) $request->user_id,
                (int) $request->phone_number,
                (string) $request->from,
                $messageText,
                $request->message_id ?? null
            );

            return response()->json(['success' => true, 'queued' => true]);
        } catch (\Exception $e) {
            Log::error('Incoming Message Error: '.$e->getMessage());

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    protected function isSelfMessage(Request $request, WhatsappNumber $whatsappNumber): bool
    {
        $fromPhone = preg_replace('/@.*$/', '', $request->from);

        return $fromPhone === $whatsappNumber->phone_number;
    }

    public function messageReceipt(Request $request)
    {
        // Acknowledge receipt — no campaign messages to track anymore
        return response()->json(['success' => true]);
    }
}
