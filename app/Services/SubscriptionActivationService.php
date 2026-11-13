<?php

namespace App\Services;

use App\Models\SubscriptionPlan;
use App\Models\Transaction;
use App\Models\UserSubscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionActivationService
{
    /**
     * Activate (or extend) the subscription for a paid transaction exactly once, whichever of the
     * success redirect or the Chip-in webhook arrives first. Callers must have verified the payment
     * with ChipInService::isPaid() first. Returns false when it was already applied.
     */
    public function activate(Transaction $transaction): bool
    {
        $plan = null;
        $isLifetime = false;

        $applied = DB::transaction(function () use ($transaction, &$plan, &$isLifetime) {
            $locked = Transaction::lockForUpdate()->find($transaction->id);

            // 'paid' without fulfilled_at = activated by the pre-webhook flow; never apply twice.
            if (! $locked || $locked->token_pack_id || $locked->fulfilled_at || $locked->status === 'paid') {
                return false;
            }

            $locked->update(['status' => 'paid', 'fulfilled_at' => now()]);

            $subscription = UserSubscription::where('user_id', $locked->user_id)
                ->whereIn('status', ['waiting_for_payment', 'active'])
                ->latest()
                ->first()
                ?? UserSubscription::create([
                    'user_id' => $locked->user_id,
                    'subscription_plan_id' => $locked->subscription_plan_id,
                    'status' => 'waiting_for_payment',
                ]);

            $plan = SubscriptionPlan::find($locked->subscription_plan_id);
            $isLifetime = (bool) ($plan && $plan->is_lifetime);

            $otherActive = UserSubscription::where('user_id', $locked->user_id)
                ->where('status', 'active')
                ->where('id', '!=', $subscription->id);

            if ($isLifetime) {
                $otherActive->orderBy('ends_at', 'desc')->first()?->update(['status' => 'cancelled']);

                $subscription->update([
                    'status' => 'active',
                    'subscription_plan_id' => $locked->subscription_plan_id,
                    'ends_at' => null,
                    'cancelled_at' => null,
                ]);

                return true;
            }

            $startDate = now();
            $current = (clone $otherActive)->where('ends_at', '>', now())->orderBy('ends_at', 'desc')->first();

            if ($subscription->status === 'active' && $subscription->ends_at > now()) {
                $startDate = $subscription->ends_at;
            } elseif ($current) {
                $startDate = $current->ends_at;
                $current->update(['status' => 'cancelled']);
            }

            $subscription->update([
                'status' => 'active',
                'subscription_plan_id' => $locked->subscription_plan_id,
                'ends_at' => $startDate->copy()->addMonth(),
                'cancelled_at' => null,
            ]);

            return true;
        });

        if ($applied) {
            try {
                (new AdminNotificationService)->sendNotification('payment', $transaction->user_id, [
                    'transaction_id' => $transaction->id,
                    'plan_name' => $plan->name ?? 'Unknown',
                    'amount' => number_format($transaction->amount, 2),
                    'currency' => $transaction->currency,
                    'billing_cycle' => $isLifetime ? 'Lifetime' : 'Monthly',
                ]);
            } catch (\Throwable $e) {
                Log::error('Failed to queue payment notification', ['transaction_id' => $transaction->id, 'error' => $e->getMessage()]);
            }
        }

        return $applied;
    }
}
