<?php

namespace Tests\Feature;

use App\Jobs\SendReminderJob;
use App\Services\ReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class SendReminderJobLockTest extends TestCase
{
    use RefreshDatabase;

    public function test_overlapping_job_for_same_reminder_does_not_send(): void
    {
        $service = Mockery::mock(ReminderService::class);
        $service->shouldNotReceive('sendReminder');

        $held = Cache::lock('send-reminder:42', 120);
        $this->assertTrue($held->get());

        (new SendReminderJob(42))->handle($service);

        $held->release();
    }

    public function test_lock_is_released_after_the_job_finishes(): void
    {
        $service = Mockery::mock(ReminderService::class);
        $service->shouldNotReceive('sendReminder'); // reminder 42 does not exist

        (new SendReminderJob(42))->handle($service);

        $lock = Cache::lock('send-reminder:42', 120);
        $this->assertTrue($lock->get());
        $lock->release();
    }
}
