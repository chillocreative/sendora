<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscriptionLimits
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $feature = null): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $subscription = $user->activeSubscription;

        if (!$subscription) {
            // Check if there's a free plan or redirect to pricing
            return $next($request);
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

        if ($feature === 'contacts') {
            if ($user->contacts()->count() >= $limits['contacts']) {
                return back()->with('error', 'You have reached the contacts limit for your plan.');
            }
        }

        if ($feature === 'messages') {
            if ($subscription->messages_used_this_month >= $limits['messages']) {
                return back()->with('error', 'You have reached the messages limit for your plan this month.');
            }
        }

        // Check feature flags
        if ($feature === 'scheduling') {
            if (!($features['scheduling'] ?? false)) {
                return back()->with('error', 'Campaign scheduling is available on all paid plans. Please upgrade.');
            }
        }

        if ($feature === 'auto_reply') {
            if (!($features['auto_reply'] ?? false)) {
                return back()->with('error', 'Auto-reply is available on Pro and Business plans. Please upgrade.');
            }
        }

        if ($feature === 'message_preview') {
            if (!($features['message_preview'] ?? false)) {
                return back()->with('error', 'Message preview is available on Pro and Business plans. Please upgrade.');
            }
        }

        if ($feature === 'pdf_support') {
            if (!($features['pdf_support'] ?? false)) {
                return back()->with('error', 'PDF support is available on Pro and Business plans. Please upgrade.');
            }
        }

        if ($feature === 'link_preview') {
            if (!($features['link_preview'] ?? false)) {
                return back()->with('error', 'Link preview is available on Pro and Business plans. Please upgrade.');
            }
        }

        if ($feature === 'webhooks') {
            if (!($features['webhooks'] ?? false)) {
                return back()->with('error', 'Webhooks are available on Business plan only. Please upgrade.');
            }
        }

        if ($feature === 'api_access') {
            if (!($features['api_access'] ?? false)) {
                return back()->with('error', 'API access is available on Business plan only. Please upgrade.');
            }
        }

        return $next($request);
    }

    /**
     * Helper method to check if a feature is available
     */
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
