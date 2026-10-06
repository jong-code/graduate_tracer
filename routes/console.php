<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// See App\Console\Commands\SendSurveyReminders - emails graduates who
// registered 3+ days ago but haven't submitted their tracer survey yet.
// Runs once daily; requires a host-level cron job hitting
// `php artisan schedule:run` every minute for this to actually fire (see
// the deployment note left with this command).
Schedule::command('gts:send-survey-reminders')->daily();
