<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Setting;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Don't check for admin routes, authentication routes, system routes, or if the user is an admin
        if (
            $request->is('admin*') ||
            $request->is('login*') ||
            $request->is('register*') ||
            $request->is('forgot-password*') ||
            $request->is('reset-password*') ||
            $request->is('two-factor-challenge*') ||
            $request->is('two-factor-recovery*') ||
            $request->is('system/*') ||
            ($request->user() && $request->user()->is_admin)
        ) {
            return $next($request);
        }

        $maintenanceMode = Setting::where('key', 'maintenance_mode')->value('value');

        if ($maintenanceMode === '1' || $maintenanceMode === true) {
            // Render the maintenance page via Inertia if it's an Inertia request
            // or just a regular view if not. But since it's a SPA mostly:
            return Inertia::render('Errors/Maintenance')->toResponse($request)->setStatusCode(503);
        }

        return $next($request);
    }
}
