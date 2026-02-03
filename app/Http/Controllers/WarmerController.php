<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class WarmerController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        // Ensure only Basic, Pro, and Business plans can access this
        // Or specific plans. We will allow all paid plans or check permissions.
        // For now, let's assume all users can see it but only some can enable it if we wanted logic.
        // But the prompt says "build ... for Basic, Pro and Business".
        
        $plan = $user->current_plan;
        $isEligible = in_array($plan->name, ['Basic', 'Pro', 'Business']);

        return Inertia::render('Warmer/Index', [
            'isEnabled' => $user->warmer_enabled,
            'isEligible' => $isEligible,
            'planName' => $plan->name
        ]);
    }

    public function toggle(Request $request)
    {
        $user = auth()->user();
        $plan = $user->current_plan;
        
        if (!in_array($plan->name, ['Basic', 'Pro', 'Business'])) {
            return back()->withErrors(['message' => 'Your plan does not support this feature.']);
        }

        $user->update([
            'warmer_enabled' => !$user->warmer_enabled
        ]);

        return back()->with('flash', [
            'message' => $user->warmer_enabled ? 'Warmer Mode Activated' : 'Warmer Mode Deactivated'
        ]);
    }
}
