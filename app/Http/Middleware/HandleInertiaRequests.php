<?php

namespace App\Http\Middleware;

use App\Models\TicketReply;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'appName' => config('app.name'),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'is_admin' => $request->user()->is_admin,
                    'profile_photo_url' => $request->user()->profile_photo_url,
                    'active_subscription' => $request->user()->activeSubscription()
                        ->with('plan')
                        ->first(),
                    'current_plan' => $request->user()->current_plan, // Fallback to Starter
                    'unread_tickets' => TicketReply::whereHas('ticket', function ($q) use ($request) {
                        $q->where('user_id', $request->user()->id);
                    })->where('is_admin', true)->whereNull('read_at')->count(),
                ] : null,
            ],
        ];
    }
}
