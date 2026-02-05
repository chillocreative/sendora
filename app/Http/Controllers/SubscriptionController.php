<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\SubscriptionPlan;
use App\Models\Setting;
use App\Services\AdminNotificationService;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    public function show()
    {
        // EMERGENCY FIX: Force clear route cache on page load to resolve 404s
        try {
            \Illuminate\Support\Facades\Artisan::call('route:clear');
        } catch (\Exception $e) {
            // ignore permission errors
        }

        $user = auth()->user();
        $subscription = $user->activeSubscription()->with('plan')->first() 
            ?? $user->latestSubscription()->with('plan')->first();

        return Inertia::render('Subscription/Show', [
            'subscription' => $subscription,
            'plans' => SubscriptionPlan::all(),
            'currency' => Setting::where('key', 'currency')->value('value') ?? 'MYR',
        ]);
    }

    public function cancel(Request $request)
    {
        $user = auth()->user();
        $subscription = $user->activeSubscription()->first();

        if ($subscription) {
            $subscription->update([
                'cancelled_at' => now(),
            ]);

            // Notify admin of cancellation
            try {
                $notificationService = new AdminNotificationService();
                $notificationService->sendNotification('cancellation', $user->id, [
                    'plan_name' => $subscription->plan->name ?? 'Unknown',
                    'ends_at' => $subscription->ends_at ? $subscription->ends_at->format('M d, Y') : 'Unknown',
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to queue cancellation notification', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return back()->with('flash', [
                'banner' => 'Subscription cancelled. You will retain access until the end of your billing period.',
                'bannerStyle' => 'success',
            ]);
        }

        return back()->with('flash', [
            'banner' => 'No active subscription found.',
            'bannerStyle' => 'danger',
        ]);
    }
}
