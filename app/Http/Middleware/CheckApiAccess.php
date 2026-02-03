<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckApiAccess
{
    /**
     * Handle an incoming request.
     * Ensures only Business plan users can access the API.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Please provide a valid API token.',
            ], 401);
        }

        $subscription = $user->activeSubscription;

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found.',
            ], 403);
        }

        $features = $subscription->plan->limits['features'] ?? [];

        if (!($features['api_access'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => 'API access is only available on the Business plan. Please upgrade your subscription.',
            ], 403);
        }

        return $next($request);
    }
}
