<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    /**
     * Store a new transaction
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|max:3',
            'status' => 'required|in:pending,paid,failed,refunded',
            'reference_id' => 'nullable|string|max:255',
            'payment_method' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $transaction = Transaction::create($request->all());

        return back()->with('success', 'Transaction created successfully!');
    }

    /**
     * Update an existing transaction
     */
    public function update(Request $request, Transaction $transaction)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|max:3',
            'status' => 'required|in:pending,paid,failed,refunded',
            'reference_id' => 'nullable|string|max:255',
            'payment_method' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $transaction->update($request->all());

        return back()->with('success', 'Transaction updated successfully!');
    }

    /**
     * Delete a transaction
     */
    public function destroy(Transaction $transaction)
    {
        $transaction->delete();

        return back()->with('success', 'Transaction deleted successfully!');
    }

    /**
     * Get transaction data for editing
     */
    public function edit(Transaction $transaction)
    {
        return response()->json([
            'transaction' => $transaction->load(['user', 'plan']),
        ]);
    }
}
