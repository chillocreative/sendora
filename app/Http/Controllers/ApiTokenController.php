<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Middleware\CheckSubscriptionLimits;

class ApiTokenController extends Controller
{
    /**
     * Show the API tokens page
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $hasApiAccess = CheckSubscriptionLimits::hasFeature($user, 'api_access');

        return Inertia::render('ApiTokens', [
            'tokens' => $user->tokens()->latest()->get()->map(function ($token) {
                return [
                    'id' => $token->id,
                    'name' => $token->name,
                    'abilities' => $token->abilities,
                    'last_used_at' => $token->last_used_at,
                    'created_at' => $token->created_at,
                ];
            }),
            'hasApiAccess' => $hasApiAccess,
        ]);
    }

    /**
     * Create a new API token
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        // Check if user has API access
        if (!CheckSubscriptionLimits::hasFeature($user, 'api_access')) {
            return back()->with('error', 'API access is only available on the Business plan.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'abilities' => 'required|array',
            'abilities.*' => 'string',
        ]);

        $token = $user->createToken($request->name, $request->abilities);

        return back()->with([
            'success' => 'API token created successfully.',
            'token' => $token->plainTextToken,
        ]);
    }

    /**
     * Delete an API token
     */
    public function destroy(Request $request, $tokenId)
    {
        $request->user()->tokens()->where('id', $tokenId)->delete();

        return back()->with('success', 'API token revoked successfully.');
    }

    /**
     * Show API documentation
     */
    public function docs(Request $request)
    {
        $user = $request->user();
        $hasApiAccess = CheckSubscriptionLimits::hasFeature($user, 'api_access');

        return Inertia::render('ApiDocs', [
            'hasApiAccess' => $hasApiAccess,
            'baseUrl' => config('app.url'),
        ]);
    }
}
