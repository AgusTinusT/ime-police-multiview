<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Schedule;
use App\Jobs\SyncOfficerStreamsJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(SyncOfficerStreamsJob::class)->everyMinute();

// Fetch subscriber counts every hour
Schedule::command('officer:sync-subscribers')->hourly();

// Automated daily database backup at 02:00 AM
Schedule::command('db:backup')->dailyAt('02:00');

// Prune video clips older than 1 hour (runs every 15 minutes)
Schedule::command('clips:prune --hours=1')->everyFifteenMinutes();
