<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WhatsappNumber;
use App\Services\WhatsappService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class ApiController extends Controller
{
    /**
     * Get user profile and subscription info
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        $subscription = $user->activeSubscription?->load('plan');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'subscription' => $subscription ? [
                    'plan' => $subscription->plan->name,
                    'status' => $subscription->status,
                    'limits' => $subscription->plan->limits,
                ] : null,
            ],
        ]);
    }

    /**
     * Get all WhatsApp devices
     */
    public function devices(Request $request)
    {
        $user = $request->user();
        $devices = $user->whatsappNumbers()->get();

        return response()->json([
            'success' => true,
            'data' => $devices,
        ]);
    }

    /**
     * Get subscription usage stats
     */
    public function usage(Request $request)
    {
        $user = $request->user();
        $subscription = $user->activeSubscription?->load('plan');

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription.',
            ], 404);
        }

        $limits = $subscription->plan->limits;

        return response()->json([
            'success' => true,
            'data' => [
                'devices' => [
                    'used' => $user->whatsappNumbers()->count(),
                    'limit' => $limits['whatsapp_nos'] ?? 1,
                ],
                'reminders' => [
                    'used' => $user->reminders()->whereMonth('created_at', now()->month)->count(),
                    'limit' => $limits['reminders_per_month'] ?? 50,
                ],
                'features' => $limits['features'] ?? [],
            ],
        ]);
    }

    /**
     * Send a WhatsApp text message
     */
    public function sendMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|integer',
            'to' => 'required|string',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $device = WhatsappNumber::where('id', $request->device_id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Device not found or not owned by you.',
            ], 404);
        }

        if ($device->status !== 'connected') {
            return response()->json([
                'success' => false,
                'message' => 'Device is not connected. Current status: ' . $device->status,
            ], 422);
        }

        $service = new WhatsappService();
        $response = $service->sendMessage($device, $request->to, $request->message);

        if ($response && $response->successful()) {
            return response()->json([
                'success' => true,
                'message_id' => $response->json('message_id'),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to send message.',
            'error' => $response ? $response->body() : 'Service unavailable',
        ], 500);
    }

    /**
     * Send a WhatsApp message with a file attachment (base64)
     */
    public function sendFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|integer',
            'to' => 'required|string',
            'message' => 'nullable|string',
            'file_base64' => 'required|string',
            'filename' => 'nullable|string',
            'mimetype' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $device = WhatsappNumber::where('id', $request->device_id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Device not found or not owned by you.',
            ], 404);
        }

        if ($device->status !== 'connected') {
            return response()->json([
                'success' => false,
                'message' => 'Device is not connected. Current status: ' . $device->status,
            ], 422);
        }

        $service = new WhatsappService();
        $response = $service->sendMessage(
            $device,
            $request->to,
            $request->message ?? '',
            null, // mediaUrl
            $request->mimetype ?? 'application/pdf',
            $request->file_base64,
            $request->filename ?? 'document.pdf'
        );

        if ($response && $response->successful()) {
            return response()->json([
                'success' => true,
                'message_id' => $response->json('message_id'),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to send file.',
            'error' => $response ? $response->body() : 'Service unavailable',
        ], 500);
    }
}
