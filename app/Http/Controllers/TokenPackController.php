<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\TokenPack;
use App\Models\Transaction;
use App\Services\AiUsageService;
use App\Services\ChipInService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TokenPackController extends Controller
{
    public function initiate(Request $request, ChipInService $chipIn, AiUsageService $usage)
    {
        $request->validate(['pack_id' => 'required|integer']);

        $user = $request->user();
        $pack = TokenPack::where('is_active', true)->find($request->pack_id);

        if (! $pack) {
            return back()->with('error', 'This token pack is not available.');
        }

        if ($usage->allowance($user) <= 0) {
            return back()->with('error', 'Your plan has no AI token limit, so there is nothing to top up.');
        }

        if (! $chipIn->configured()) {
            return back()->with('error', 'Payment gateway not configured.');
        }

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'amount' => $pack->price,
            'currency' => Setting::where('key', 'currency')->value('value') ?? 'MYR',
            'status' => 'pending',
            'payment_method' => 'chip-in',
            'reference_id' => 'txn_'.uniqid(),
            'token_pack_id' => $pack->id,
            'token_amount' => $pack->tokens,
        ]);

        try {
            $checkoutUrl = $chipIn->createPurchase($user, $transaction, 'AI Tokens: '.$pack->name);
        } catch (\RuntimeException $e) {
            $transaction->update(['status' => 'failed']);

            return back()->with('error', $e->getMessage());
        }

        return Inertia::location($checkoutUrl);
    }
}
