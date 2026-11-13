<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ChipInService
{
    protected const BASE = 'https://gate.chip-in.asia/api/v1';

    public function configured(): bool
    {
        return $this->brandId() && $this->privateKey();
    }

    /**
     * Create a purchase and remember its gateway id on the transaction so the payment can
     * later be verified with Chip-in instead of trusting a redirect URL.
     *
     * @return string checkout URL
     *
     * @throws RuntimeException
     */
    public function createPurchase(User $user, Transaction $transaction, string $productName): string
    {
        $successUrl = url('/payments/success?ref='.$transaction->reference_id);
        $failureUrl = url('/payments/fail');

        $response = Http::withHeaders(['Authorization' => 'Bearer '.$this->privateKey()])
            ->post(self::BASE.'/purchases/', [
                'brand_id' => $this->brandId(),
                'client' => ['email' => $user->email, 'full_name' => $user->name],
                'purchase' => [
                    'currency' => $transaction->currency,
                    'products' => [['name' => $productName, 'price' => (int) round($transaction->amount * 100), 'quantity' => 1]],
                ],
                'reference' => $transaction->reference_id,
                'success_redirect' => $successUrl,
                'success_callback' => url('/payments/webhook'),
                'failure_redirect' => $failureUrl,
                'cancel_redirect' => $failureUrl,
                'test' => $this->testMode(),
            ]);

        $checkoutUrl = $response->json('checkout_url');
        $gatewayId = $response->json('id');

        if (! $response->successful() || ! $checkoutUrl || ! $gatewayId) {
            Log::error('Chip-in purchase creation failed', ['status' => $response->status(), 'body' => $response->body()]);
            throw new RuntimeException('Unable to start payment.');
        }

        $transaction->update(['gateway_id' => $gatewayId]);

        return $checkoutUrl;
    }

    /**
     * Ask Chip-in whether this transaction's purchase is really paid, for the right
     * reference and amount. Anything unverifiable counts as not paid.
     */
    public function isPaid(Transaction $transaction): bool
    {
        if (! $transaction->gateway_id || ! $this->configured()) {
            return false;
        }

        try {
            $response = Http::withHeaders(['Authorization' => 'Bearer '.$this->privateKey()])
                ->get(self::BASE.'/purchases/'.$transaction->gateway_id.'/');
        } catch (\Throwable $e) {
            Log::warning('Chip-in verification request failed', ['message' => $e->getMessage()]);

            return false;
        }

        if (! $response->successful() || $response->json('status') !== 'paid') {
            return false;
        }

        if ($response->json('reference') !== $transaction->reference_id) {
            return false;
        }

        // A sandbox purchase reports status "paid" without any money moving, so it must never
        // be accepted in live mode (and a live one is not expected in test mode).
        if ((bool) $response->json('is_test') !== $this->testMode()) {
            Log::warning('Chip-in purchase mode mismatch', ['gateway_id' => $transaction->gateway_id]);

            return false;
        }

        $total = $response->json('purchase.total');

        return $total === null || (int) $total === (int) round($transaction->amount * 100);
    }

    protected function brandId(): ?string
    {
        return Setting::where('key', 'chip_in_brand_id')->value('value');
    }

    protected function privateKey(): ?string
    {
        return Setting::where('key', 'chip_in_private_key')->value('value');
    }

    /**
     * Live in production, sandbox elsewhere, unless the chip_in_test_mode setting
     * ('1' or '0') says otherwise.
     */
    public function testMode(): bool
    {
        $setting = Setting::where('key', 'chip_in_test_mode')->value('value');

        return $setting === null || $setting === '' ? ! app()->isProduction() : $setting === '1';
    }
}
