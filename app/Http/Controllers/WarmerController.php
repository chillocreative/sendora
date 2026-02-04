<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class WarmerController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $plan = $user->current_plan;
        $isEligible = in_array($plan->name, ['Basic', 'Pro', 'Business']);

        $numbers = $user->whatsappNumbers()->where('status', 'connected')->get();

        return Inertia::render('Warmer/Index', [
            'isEnabled' => $user->warmer_enabled,
            'isEligible' => $isEligible,
            'planName' => $plan->name,
            'numbers' => $numbers
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

    /**
     * Join/Leave the AI Warmer Pool for a specific number
     */
    public function togglePool(Request $request, $id)
    {
        $user = auth()->user();
        $number = $user->whatsappNumbers()->findOrFail($id);

        $number->update([
            'is_warmer_pool_enabled' => !$number->is_warmer_pool_enabled
        ]);

        return back()->with('flash', [
            'message' => $number->is_warmer_pool_enabled ? 'Number joined the AI Warmer Pool!' : 'Number removed from the pool.'
        ]);
    }
}
