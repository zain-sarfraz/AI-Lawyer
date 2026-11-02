<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('appointments:send-reminders')->everyFiveMinutes();

// Disabled by default — enable with CASE_LAW_FETCH_SCHEDULE_ENABLED=true
if (config('case_law.fetch.enabled')) {
    Schedule::command('cases:fetch PK-SD --limit=25')->dailyAt('02:15');
    Schedule::command('cases:fetch PK-FED --limit=25')->dailyAt('02:30');
    Schedule::command('cases:fetch GB-EAW --limit=25')->dailyAt('02:45');
    Schedule::command('cases:fetch CA-FED --limit=25')->dailyAt('03:00');
    Schedule::command('cases:fetch CA-ON --limit=25')->dailyAt('03:15');
}

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
