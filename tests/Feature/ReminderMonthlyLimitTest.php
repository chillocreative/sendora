<?php

namespace Tests\Feature;

use App\Exceptions\ReminderLimitReached;
use App\Models\Reminder;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\ReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReminderMonthlyLimitTest extends TestCase
{
    use RefreshDatabase;

    private function userWithLimit(int $limit): User
    {
        $plan = SubscriptionPlan::create([
            'name' => 'P'.$limit, 'monthly_price' => 1, 'yearly_price' => 1,
            'limits' => ['whatsapp_nos' => 1, 'reminders_per_month' => $limit, 'features' => []],
        ]);
        $user = User::factory()->create();
        UserSubscription::create(['user_id' => $user->id, 'subscription_plan_id' => $plan->id, 'status' => 'active']);

        return $user;
    }

    private function make(User $user, string $title): Reminder
    {
        return app(ReminderService::class)->createReminder($user, [
            'title' => $title,
            'event_at' => now()->addDays(5)->format('Y-m-d H:i'),
            'source' => 'web',
        ]);
    }

    public function test_creation_beyond_the_monthly_limit_is_refused(): void
    {
        $user = $this->userWithLimit(2);
        $this->make($user, 'one');
        $this->make($user, 'two');

        $this->expectException(ReminderLimitReached::class);
        $this->make($user, 'three');
    }

    public function test_zero_means_unlimited(): void
    {
        $user = $this->userWithLimit(0);
        foreach (['a', 'b', 'c', 'd'] as $t) {
            $this->make($user, $t);
        }

        $this->assertSame(4, Reminder::where('user_id', $user->id)->count());
    }

    public function test_duplicate_of_existing_reminder_is_not_blocked_at_the_limit(): void
    {
        $user = $this->userWithLimit(1);
        $first = $this->make($user, 'same');

        $this->assertSame($first->id, $this->make($user, 'same')->id);
    }

    public function test_last_months_reminders_do_not_count(): void
    {
        $user = $this->userWithLimit(1);
        $old = $this->make($user, 'old');
        $old->forceFill(['created_at' => now()->subMonth()->startOfMonth()])->save();

        $this->make($user, 'new');
        $this->assertSame(2, Reminder::where('user_id', $user->id)->count());
    }
}
