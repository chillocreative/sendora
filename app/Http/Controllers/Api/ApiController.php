<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Campaign;
use App\Models\WhatsappNumber;
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
                    'messages_used' => $subscription->messages_used_this_month ?? 0,
                ] : null,
            ],
        ]);
    }

    /**
     * Get all contacts
     */
    public function contacts(Request $request)
    {
        $user = $request->user();
        $contacts = $user->contacts()
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('phone', 'like', "%{$request->search}%"))
            ->paginate($request->per_page ?? 50);

        return response()->json([
            'success' => true,
            'data' => $contacts,
        ]);
    }

    /**
     * Create a new contact
     */
    public function storeContact(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'tags' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check contact limit
        $user = $request->user();
        $subscription = $user->activeSubscription;
        $limit = $subscription?->plan?->limits['contacts'] ?? 50;

        if ($user->contacts()->count() >= $limit) {
            return response()->json([
                'success' => false,
                'message' => 'Contact limit reached for your subscription plan.',
            ], 403);
        }

        $contact = $user->contacts()->create($request->all());

        return response()->json([
            'success' => true,
            'data' => $contact,
            'message' => 'Contact created successfully.',
        ], 201);
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
     * Send a WhatsApp message
     */
    public function sendMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'message' => 'required|string|max:4096',
            'device_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $subscription = $user->activeSubscription;

        // Check message limit
        $limit = $subscription?->plan?->limits['messages'] ?? 50;
        $used = $subscription?->messages_used_this_month ?? 0;

        if ($used >= $limit) {
            return response()->json([
                'success' => false,
                'message' => 'Message limit reached for this month.',
            ], 403);
        }

        // Get device
        $device = $request->device_id 
            ? $user->whatsappNumbers()->find($request->device_id)
            : $user->whatsappNumbers()->where('status', 'connected')->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'No connected WhatsApp device found.',
            ], 404);
        }

        // Send via WhatsApp server
        try {
            $response = Http::post(config('app.url') . ':3000/send-message', [
                'userId' => $user->id,
                'numberId' => $device->id,
                'to' => $request->phone,
                'message' => $request->message,
            ]);

            if ($response->successful()) {
                // Increment message count
                if ($subscription) {
                    $subscription->increment('messages_used_this_month');
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Message sent successfully.',
                    'data' => [
                        'phone' => $request->phone,
                        'device_id' => $device->id,
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to send message.',
                'error' => $response->json()['error'] ?? 'Unknown error',
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'WhatsApp server connection failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get campaigns list
     */
    public function campaigns(Request $request)
    {
        $user = $request->user();
        $campaigns = $user->campaigns()
            ->with('whatsappNumber')
            ->latest()
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $campaigns,
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
                    'limit' => $limits['whatsapp_nos'],
                ],
                'contacts' => [
                    'used' => $user->contacts()->count(),
                    'limit' => $limits['contacts'],
                ],
                'messages' => [
                    'used' => $subscription->messages_used_this_month ?? 0,
                    'limit' => $limits['messages'],
                ],
                'features' => $limits['features'] ?? [],
            ],
        ]);
    }
}
