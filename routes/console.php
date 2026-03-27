<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('trainings:notify-expiring-certificates --days=30')->dailyAt('07:00');
Schedule::command('reports:run-scheduled-exports --limit=100')->everyTenMinutes();
Schedule::command('reports:cleanup-exports --days=30')->dailyAt('03:00');
Schedule::command('audit:cleanup-logs --days=180')->dailyAt('03:30');
