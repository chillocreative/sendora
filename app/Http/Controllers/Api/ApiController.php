<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
}
