<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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

        return \Inertia\Inertia::render('Checkout', [
            'plan' => $subscription->plan,
            'billing_cycle' => $billingCycle,
            'amount' => $billingCycle === 'monthly' ? $subscription->plan->monthly_price : $subscription->plan->yearly_price,
            'currency' => $currency,
        ]);
    }

    public function initiatePayment(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
        ]);

        $user = auth()->user();
        $plan = \App\Models\SubscriptionPlan::findOrFail($request->plan_id);
        $amount = $request->billing_cycle === 'monthly' ? $plan->monthly_price : $plan->yearly_price;

        // 1. FREE PLAN
        if ($amount <= 0) {
            \App\Models\UserSubscription::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'subscription_plan_id' => $plan->id,
                    'status' => 'active',
                    'ends_at' => null, 
                ]
            );
            return redirect()->route('dashboard')->with('flash.banner', 'You represent subscribed to the ' . $plan->name . ' plan!');
        }

        // 2. PAID PLAN & CHIP-IN
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
                        'name' => 'Subscription: ' . $plan->name,
                        'price' => (int) ($amount * 100), // Chip-in typically uses cents
                        'quantity' => 1,
                    ]
                ],
            ],
            'reference' => $transaction->reference_id,
            'success_callback' => route('payments.success', ['ref' => $transaction->reference_id]),
            'redirect_url' => route('payments.success', ['ref' => $transaction->reference_id]),
            'return_url' => route('payments.success', ['ref' => $transaction->reference_id]),
            'callback_url' => route('payments.success', ['ref' => $transaction->reference_id]),
            'error_callback' => route('payments.fail'),
            'cancel_url' => route('payments.fail'),
            'test' => true, 
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
            $transaction = \App\Models\Transaction::where('reference_id', $reference)->first();
            
            if ($transaction && $transaction->status !== 'paid') {
                // Optimistically mark as paid for UX (In production, verify signature or wait for webhook)
                $transaction->update(['status' => 'paid']);
                
                // Activate Subscription
                $subscription = \App\Models\UserSubscription::where('subscription_plan_id', $transaction->subscription_plan_id)
                    ->where('user_id', $transaction->user_id)
                    ->where('status', 'waiting_for_payment')
                    ->latest()
                    ->first();
                    
                if ($subscription) {
                    $subscription->update([
                        'status' => 'active',
                        // 'starts_at' or 'created_at' handles start. 
                        // If we need strict start tracking, we need a column. 
                        // For now, assuming created_at is start, or just activating.
                    ]);
                }
            }
        }

        return redirect()->route('campaigns.index')->with('flash.banner', 'Payment successful! Your subscription is now active.')
                                                  ->with('flash.bannerStyle', 'success');
    }

    public function fail(Request $request)
    {
        return redirect()->route('campaigns.index')->with('flash.banner', 'Payment failed! Please try again or contact support.')
                                                  ->with('flash.bannerStyle', 'danger');
    }

    public function handleWebhook(Request $request)
    {
        // Webhook signature validation should be here

        $payload = $request->all();
        
        // Example logic:
        // $userId = $payload['reference'];
        // $status = $payload['status'];
        
        // if ($status === 'paid') {
        //     UserSubscription::updateOrCreate(...);
        // }

        return response('OK', 200);
    }}
