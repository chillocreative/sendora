<?php

namespace App\Services;

use App\Exceptions\AiTokenLimitReached;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Monthly AI token metering. The allowance comes from plan limits['ai_tokens_per_month'];
 * a missing key or 0 means unlimited (so plans created before metering are unaffected).
 * Purchased packs (users.ai_bonus_tokens) are spent only after the monthly allowance.
 */
class AiUsageService
{
    public function allowance(User $user): int
    {
        return (int) ($user->current_plan?->limits['ai_tokens_per_month'] ?? 0);
    }

    public function usedThisMonth(User $user): int
    {
        return (int) DB::table('ai_token_usages')
            ->where('user_id', $user->id)
            ->where('period', $this->period())
            ->value('tokens_used');
    }

    /** Tokens left this month plus purchased packs, or null when unlimited. */
    public function remaining(User $user): ?int
    {
        $allowance = $this->allowance($user);

        if ($allowance <= 0) {
            return null;
        }

        return max(0, $allowance - $this->usedThisMonth($user)) + (int) $user->fresh()->ai_bonus_tokens;
    }

    public function hasTokens(User $user): bool
    {
        $remaining = $this->remaining($user);

        return $remaining === null || $remaining > 0;
    }

    /** @throws AiTokenLimitReached */
    public function assertAvailable(User $user): void
    {
        if (! $this->hasTokens($user)) {
            throw new AiTokenLimitReached;
        }
    }

    /**
     * Record tokens actually consumed by a completed call. A call may overshoot the allowance
     * slightly (its size is only known afterwards); the next call is then refused.
     */
    public function record(User $user, int $tokens): void
    {
        if ($tokens <= 0 || $this->allowance($user) <= 0) {
            return;
        }

        $allowance = $this->allowance($user);

        DB::transaction(function () use ($user, $tokens, $allowance) {
            DB::table('ai_token_usages')->insertOrIgnore([
                'user_id' => $user->id,
                'period' => $this->period(),
                'tokens_used' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $used = (int) DB::table('ai_token_usages')
                ->where('user_id', $user->id)
                ->where('period', $this->period())
                ->lockForUpdate()
                ->value('tokens_used');

            $monthlyLeft = max(0, $allowance - $used);
            $fromMonthly = min($tokens, $monthlyLeft);
            $fromBonus = $tokens - $fromMonthly;

            DB::table('ai_token_usages')
                ->where('user_id', $user->id)
                ->where('period', $this->period())
                ->update(['tokens_used' => $used + $tokens, 'updated_at' => now()]);

            if ($fromBonus > 0) {
                $bonus = (int) DB::table('users')->where('id', $user->id)->lockForUpdate()->value('ai_bonus_tokens');
                DB::table('users')->where('id', $user->id)
                    ->update(['ai_bonus_tokens' => max(0, $bonus - $fromBonus)]);
            }
        });
    }

    protected function period(): string
    {
        return now()->format('Y-m');
    }
}
