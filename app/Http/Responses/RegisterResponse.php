<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        $user = auth()->user();
        if ($user) {
            $subscription = $user->latestSubscription;
            if ($subscription && $subscription->status === 'waiting_for_payment') {
                return redirect()->route('checkout');
            }
        }

        return redirect()->intended(config('fortify.home'));
    }
}
