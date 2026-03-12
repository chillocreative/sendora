<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscriptionLimits
{
    public function handle(Request $request, Closure $next, string $feature = null): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $subscription = $user->activeSubscription;

        if (!$subscription) {
            return redirect()->route('pricing')->with('error', 'Please subscribe to a plan to access this feature.');
        }

        $plan = $subscription->plan;
        $limits = $plan->limits;
        $features = $limits['features'] ?? [];

        // Check numeric limits
        if ($feature === 'whatsapp_nos') {
            if ($user->whatsappNumbers()->count() >= $limits['whatsapp_nos']) {
                return back()->with('error', 'You have reached the WhatsApp numbers limit for your plan.');
            }
        }

        if ($feature === 'reminders') {
            $limit = $limits['reminders_per_month'] ?? 0;
            if ($limit > 0) {
                $usedThisMonth = $user->reminders()
                    ->whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month)
                    ->count();
                if ($usedThisMonth >= $limit) {
                    return back()->with('error', "You've reached your monthly reminder limit ({$limit}). Please upgrade.");
                }
            }
        }

        // Check feature flags
        if ($feature === 'google_calendar') {
            if (!($features['google_calendar'] ?? false)) {
                return back()->with('error', 'Google Calendar integration requires a paid plan. Please upgrade.');
            }
        }

        if ($feature === 'ai_command_parsing') {
            if (!($features['ai_command_parsing'] ?? false)) {
                return back()->with('error', 'AI command parsing is available on Pro and Business plans. Please upgrade.');
            }
        }

        if ($feature === 'auto_reply') {
            if (!($features['auto_reply'] ?? false)) {
                return back()->with('error', 'AI Playbooks are available on Pro and Business plans. Please upgrade.');
            }
        }

        if ($feature === 'api_access') {
            if (!($features['api_access'] ?? false)) {
                return back()->with('error', 'API access is available on Business plan only. Please upgrade.');
            }
        }

        return $next($request);
    }

    public static function hasFeature($user, string $feature): bool
    {
        $subscription = $user->activeSubscription;

        if (!$subscription) {
            return false;
        }

        $features = $subscription->plan->limits['features'] ?? [];
        return $features[$feature] ?? false;
    }
}
