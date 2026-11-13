<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class TokenPackService
{
    /**
     * Credit a paid pack exactly once, however many times the redirect and webhook arrive.
     * Callers must have verified the payment with ChipInService::isPaid() first.
     */
    public function fulfil(Transaction $transaction): bool
    {
        return DB::transaction(function () use ($transaction) {
            $locked = Transaction::lockForUpdate()->find($transaction->id);

            if (! $locked || ! $locked->token_pack_id || $locked->fulfilled_at) {
                return false;
            }

            DB::table('users')->where('id', $locked->user_id)
                ->increment('ai_bonus_tokens', (int) $locked->token_amount);

            $locked->update(['status' => 'paid', 'fulfilled_at' => now()]);

            return true;
        });
    }
}
