<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Middleware\CheckSubscriptionLimits;
use Illuminate\Support\Facades\Crypt;

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
                    'has_encrypted_token' => !empty($token->encrypted_token),
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

        // Store encrypted token for later retrieval
        try {
            $encrypted = Crypt::encryptString($token->plainTextToken);
            $token->accessToken->encrypted_token = $encrypted;
            $token->accessToken->save();

            \Log::info('Token created with encryption', [
                'token_id' => $token->accessToken->id,
                'has_encrypted' => !empty($token->accessToken->encrypted_token),
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to encrypt token', [
                'error' => $e->getMessage(),
                'token_id' => $token->accessToken->id,
            ]);
        }

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
     * Get decrypted token
     */
    public function show(Request $request, $tokenId)
    {
        $user = $request->user();

        // Check if user has API access
        if (!CheckSubscriptionLimits::hasFeature($user, 'api_access')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $token = $user->tokens()->where('id', $tokenId)->first();

        if (!$token || !$token->encrypted_token) {
            return response()->json(['error' => 'Token not found or not available'], 404);
        }

        try {
            $decryptedToken = Crypt::decryptString($token->encrypted_token);
            return response()->json(['token' => $decryptedToken]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to decrypt token'], 500);
        }
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
