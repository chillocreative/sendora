<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function index()
    {
        $plans = \App\Models\SubscriptionPlan::where('is_active', true)->get();
        $currency = \App\Models\Setting::where('key', 'currency')->value('value') ?? 'USD';
        
        $currentPlanId = null;
        if (auth()->check()) {
            $subscription = auth()->user()->activeSubscription;
            $currentPlanId = $subscription ? $subscription->subscription_plan_id : null;
        }

        return \Inertia\Inertia::render('Pricing', [
            'plans' => $plans,
            'currency' => $currency,
            'currentPlanId' => $currentPlanId,
        ]);
    }
}
