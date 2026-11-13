<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AdminNotificationService;
use App\Services\ChipInService;
use App\Services\TokenPackService;

class PaymentController extends Controller
{
    public function checkout(Request $request)
    {
        $user = auth()->user();
        $subscription = $user->latestSubscription()->with('plan')->first();

        // If no pending subscription, redirect to dashboard
        if (!$subscription || $subscription->status !== 'waiting_for_payment') {
            return redirect()->route('dashboard');
        }

        $billingCycle = session('registration_billing_cycle', 'monthly');
        $currency = \App\Models\Setting::where('key', 'currency')->value('value') ?? 'MYR';
        $plan = $subscription->plan;

        if ($plan->is_lifetime) {
            $amount = $plan->monthly_price;
        } else {
            $amount = $plan->monthly_price;
        }

        return \Inertia\Inertia::render('Checkout', [
            'plan' => $plan,
            'billing_cycle' => $billingCycle,
            'amount' => $amount,
            'currency' => $currency,
            'is_lifetime' => $plan->is_lifetime,
        ]);
    }

    public function initiatePayment(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'billing_cycle' => 'required|in:monthly,lifetime',
        ]);

        $user = auth()->user();
        $plan = \App\Models\SubscriptionPlan::findOrFail($request->plan_id);

        // Retired plans stay available only to people who already subscribed to them (renewals).
        if (! $plan->is_active && ! $user->subscriptions()->where('subscription_plan_id', $plan->id)->exists()) {
            abort(422, 'This plan is no longer available.');
        }

        if ($plan->is_lifetime) {
            $amount = $plan->monthly_price; // one-time price stored in monthly_price
        } else {
            $amount = $plan->monthly_price;
        }

        // Create or update pending subscription
        $subscription = \App\Models\UserSubscription::updateOrCreate(
            ['user_id' => $user->id, 'status' => 'waiting_for_payment'],
            [
                'subscription_plan_id' => $plan->id,
                'status' => 'waiting_for_payment',
                // specific ends_at will be set on success
            ]
        );

        // PAID PLAN & CHIP-IN
        $brandId = \App\Models\Setting::where('key', 'chip_in_brand_id')->value('value');
        $privateKey = \App\Models\Setting::where('key', 'chip_in_private_key')->value('value');

        if (!$brandId || !$privateKey) {
            return back()->with('error', 'Payment gateway not configured.');
        }

        $transaction = \App\Models\Transaction::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'amount' => $amount,
            'currency' => \App\Models\Setting::where('key', 'currency')->value('value') ?? 'MYR',
            'status' => 'pending',
            'payment_method' => 'chip-in',
            'reference_id' => 'txn_' . uniqid(),
        ]);

        // Generate absolute URLs for payment gateway callbacks
        $successUrl = url('/payments/success?ref=' . $transaction->reference_id);
        $failureUrl = url('/payments/fail');

        $params = [
            'brand_id' => $brandId,
            'client' => [
                'email' => $user->email,
                'full_name' => $user->name,
            ],
            'purchase' => [
                'currency' => $transaction->currency,
                'products' => [
                    [
                        'name' => $plan->is_lifetime ? 'Lifetime Access: ' . $plan->name : 'Subscription: ' . $plan->name . ' (' . ucfirst($request->billing_cycle) . ')',
                        'price' => (int) ($amount * 100), // Chip-in typically uses cents
                        'quantity' => 1,
                    ]
                ],
            ],
            'reference' => $transaction->reference_id,
            'success_redirect' => $successUrl,
            'success_callback' => $successUrl,
            'failure_redirect' => $failureUrl,
            'cancel_redirect' => $failureUrl,
            'test' => app(ChipInService::class)->testMode(),
        ];

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $privateKey,
            ])->post('https://gate.chip-in.asia/api/v1/purchases/', $params);

            \Illuminate\Support\Facades\Log::info('Chip-in Init', [
                'params' => $params,
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            if ($response->successful()) {
                $checkoutUrl = $response->json()['checkout_url'] ?? null;
                
                if (!$checkoutUrl) {
                     \Illuminate\Support\Facades\Log::error('Chip-in Error: No checkout URL', ['body' => $response->body()]);
                     return back()->with('error', 'Payment provider returned an invalid response.');
                }
                
                // Needed later to verify with Chip-in that this purchase was really paid.
                $transaction->update(['gateway_id' => $response->json('id')]);

                return \Inertia\Inertia::location($checkoutUrl);
            } else {
                \Illuminate\Support\Facades\Log::error('Chip-in API Error', ['status' => $response->status(), 'body' => $response->body()]);
                return back()->with('error', 'Unable to initiate payment: ' . $response->body());
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Chip-in Connection Error', ['message' => $e->getMessage()]);
            return back()->with('error', 'Connection to payment gateway failed.');
        }
    }

    public function success(Request $request)
    {
        $reference = $request->query('ref');
        
        if ($reference) {
            // Only the buyer can complete their own transaction.
            $transaction = \App\Models\Transaction::where('reference_id', $reference)
                ->where('user_id', auth()->id())
                ->first();

            if ($transaction && $transaction->status !== 'paid') {
                // The redirect URL proves nothing: confirm with Chip-in that it is really paid.
                if (! app(ChipInService::class)->isPaid($transaction)) {
                    return redirect()->route('subscription.show')
                        ->with('flash.banner', 'We could not confirm your payment yet. If you were charged it will be applied shortly; otherwise please contact support.')
                        ->with('flash.bannerStyle', 'danger');
                }

                if ($transaction->token_pack_id) {
                    app(TokenPackService::class)->fulfil($transaction);

                    return redirect()->route('subscription.show')
                        ->with('flash.banner', 'Payment successful! Your AI tokens have been added.')
                        ->with('flash.bannerStyle', 'success');
                }

                $transaction->update(['status' => 'paid']);
                
                // Activate Subscription
                $subscription = \App\Models\UserSubscription::where('user_id', $transaction->user_id)
                    ->where(function($query) {
                        $query->where('status', 'waiting_for_payment')
                              ->orWhere('status', 'active'); // Allow renewing active subs
                    })
                    ->latest()
                    ->first();

                if (!$subscription) {
                    // Create if not exists (fallback)
                    $subscription = \App\Models\UserSubscription::create([
                        'user_id' => $transaction->user_id,
                        'subscription_plan_id' => $transaction->subscription_plan_id,
                        'status' => 'waiting_for_payment',
                    ]);
                }
                    
                // Calculate Expiry based on Amount vs Plan Price
                $plan = \App\Models\SubscriptionPlan::find($transaction->subscription_plan_id);
                $isLifetime = $plan && $plan->is_lifetime;

                if ($isLifetime) {
                    // Cancel any existing active subscription and set permanent access
                    $currentActive = \App\Models\UserSubscription::where('user_id', $transaction->user_id)
                        ->where('status', 'active')
                        ->where('id', '!=', $subscription->id)
                        ->orderBy('ends_at', 'desc')
                        ->first();

                    if ($currentActive) {
                        $currentActive->update(['status' => 'cancelled']);
                    }

                    $subscription->update([
                        'status' => 'active',
                        'subscription_plan_id' => $transaction->subscription_plan_id,
                        'ends_at' => null, // Permanent — no expiry
                        'cancelled_at' => null,
                    ]);
                } else {
                    // Determine start date: extend if current active, otherwise start now
                    $currentActive = \App\Models\UserSubscription::where('user_id', $transaction->user_id)
                        ->where('status', 'active')
                        ->where('id', '!=', $subscription->id)
                        ->where('ends_at', '>', now())
                        ->orderBy('ends_at', 'desc')
                        ->first();

                    $startDate = now();
                    if ($subscription->status === 'active' && $subscription->ends_at > now()) {
                        $startDate = $subscription->ends_at;
                    } elseif ($currentActive) {
                        $startDate = $currentActive->ends_at;
                        $currentActive->update(['status' => 'cancelled']);
                        $subscription->subscription_plan_id = $transaction->subscription_plan_id;
                    }

                    $endsAt = $startDate->copy()->addMonth();

                    $subscription->update([
                        'status' => 'active',
                        'subscription_plan_id' => $transaction->subscription_plan_id,
                        'ends_at' => $endsAt,
                        'cancelled_at' => null,
                    ]);
                }

                // Notify admin of successful payment
                try {
                    $notificationService = new AdminNotificationService();
                    $notificationService->sendNotification('payment', $transaction->user_id, [
                        'transaction_id' => $transaction->id,
                        'plan_name' => $plan->name ?? 'Unknown',
                        'amount' => number_format($transaction->amount, 2),
                        'currency' => $transaction->currency,
                        'billing_cycle' => $isLifetime ? 'Lifetime' : 'Monthly',
                    ]);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to queue payment notification', [
                        'transaction_id' => $transaction->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return redirect()->route('subscription.show')->with('flash.banner', 'Payment successful! Your subscription is now active.')
                                                  ->with('flash.bannerStyle', 'success');
    }

    public function fail(Request $request)
    {
        return redirect()->route('subscription.show')->with('flash.banner', 'Payment failed! Please try again or contact support.')
                                                  ->with('flash.bannerStyle', 'danger');
    }

    public function handleWebhook(Request $request)
    {
        // The payload is never trusted: it only tells us which transaction to re-check with Chip-in.
        $reference = $request->input('reference');

        $transaction = $reference
            ? \App\Models\Transaction::where('reference_id', $reference)->first()
            : null;

        if ($transaction && $transaction->token_pack_id && ! $transaction->fulfilled_at
            && app(ChipInService::class)->isPaid($transaction)) {
            app(TokenPackService::class)->fulfil($transaction);
        }

        return response('OK', 200);
    }
}
