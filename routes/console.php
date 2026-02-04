<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

\Illuminate\Support\Facades\Schedule::command('campaigns:dispatch')->everyMinute();
\Illuminate\Support\Facades\Schedule::command('warmer:process-pool')->everyThirtyMinutes();
\Illuminate\Support\Facades\Schedule::command('queue:work --stop-when-empty')->everyMinute();
