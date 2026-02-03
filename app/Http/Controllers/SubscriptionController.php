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
}
