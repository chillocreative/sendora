<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

\Illuminate\Support\Facades\Schedule::command('campaigns:dispatch')->everyMinute();
\Illuminate\Support\Facades\Schedule::command('warmer:process-pool')->everyThirtyMinutes();
\Illuminate\Support\Facades\Schedule::command('queue:work --queue=ai-replies,default --stop-when-empty --max-time=55 --timeout=7200')->everyMinute()->withoutOverlapping(10);
\Illuminate\Support\Facades\Schedule::command('admin:process-notifications')->everyFiveMinutes();
\Illuminate\Support\Facades\Schedule::command('subscriptions:check-expiring')->daily();
