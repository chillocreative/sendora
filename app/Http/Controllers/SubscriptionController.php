<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\SubscriptionPlan;
use App\Models\Setting;

class SubscriptionController extends Controller
{
    public function show()
    {
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
