<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class VerifyRecaptcha
{
    private const PROTECTED_PATHS = ['login', 'register', 'forgot-password'];

    private const PROTECTED_ROUTES = ['login.store', 'register.store', 'password.email'];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('POST') || ! $this->isProtected($request)) {
            return $next($request);
        }

        $secret = config('services.recaptcha.secret_key');

        // Not configured yet: do not lock users out.
        if (empty($secret)) {
            return $next($request);
        }

        $token = $request->input('recaptcha_token');

        if (! is_string($token) || $token === '' || ! $this->verify($secret, $token, $request->ip())) {
            throw ValidationException::withMessages([
                'recaptcha_token' => 'Please complete the reCAPTCHA check and try again.',
            ]);
        }

        return $next($request);
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
                ->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => $secret,
                    'response' => $token,
                    'remoteip' => $ip,
                ]);

            return $response->successful() && $response->json('success') === true;
        } catch (\Throwable $e) {
            // Fail closed. Deliberately log nothing that could include the secret or token.
            return false;
        }
    }
}
