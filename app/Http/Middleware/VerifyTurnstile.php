<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cloudflare Turnstile check for the public auth forms. Runs before Fortify looks at credentials.
 */
class VerifyTurnstile
{
    public const FIELD = 'cf-turnstile-response';

    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    private const PROTECTED_PATHS = ['login', 'register', 'forgot-password'];

    private const PROTECTED_ROUTES = ['login.store', 'register.store', 'password.email'];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('POST') || ! $this->isProtected($request)) {
            return $next($request);
        }

        $secret = config('services.turnstile.secret_key');

        if (empty($secret)) {
            // Local/test environments without keys keep working; production never runs unprotected.
            if (app()->isProduction()) {
                Log::error('Turnstile: TURNSTILE_SECRET_KEY is not set; rejecting protected form submission.');
                $this->fail();
            }

            return $next($request);
        }

        $token = $request->input(self::FIELD);

        if (! is_string($token) || $token === '' || ! $this->verify($secret, $token, $request->ip())) {
            $this->fail();
        }

        return $next($request);
    }

    private function fail(): never
    {
        throw ValidationException::withMessages([
            self::FIELD => 'Please complete the security check and try again.',
        ]);
    }

    /**
     * Decide on the matched route name and on the DECODED path. The router matches the
     * decoded path, so comparing the raw path would let "/%6cogin" skip the check.
     */
    private function isProtected(Request $request): bool
    {
        return $request->routeIs(...self::PROTECTED_ROUTES)
            || in_array(trim($request->decodedPath(), '/'), self::PROTECTED_PATHS, true);
    }

    private function verify(string $secret, string $token, ?string $ip): bool
    {
        try {
            $response = Http::asForm()
                ->timeout(5)
                ->post(self::VERIFY_URL, array_filter([
                    'secret' => $secret,
                    'response' => $token,
                    'remoteip' => $ip,
                ]));

            if ($response->successful() && $response->json('success') === true) {
                return true;
            }

            Log::warning('Turnstile verification rejected', [
                'status' => $response->status(),
                'error_codes' => $response->json('error-codes'),
            ]);
        } catch (\Throwable $e) {
            // Fail closed. Never log the secret or the token.
            Log::error('Turnstile verification request failed', ['error' => $e->getMessage()]);
        }

        return false;
    }
}
