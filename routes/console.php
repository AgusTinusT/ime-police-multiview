<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Schedule;
use App\Jobs\SyncOfficerStreamsJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('officer:sync')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->runInBackground();

// Fetch subscriber counts every hour
Schedule::command('officer:sync-subscribers')->hourly();

// Automated monthly duty reset on the 1st day of every month at 00:00 AM
Schedule::command('officer:reset-monthly')->monthlyOn(1, '00:00');

// Automated daily database backup at 02:00 AM
Schedule::command('db:backup')->dailyAt('02:00');

// Prune video clips older than 1 hour (runs every 15 minutes)
Schedule::command('clips:prune --hours=1')->everyFifteenMinutes();

// Tactical Bio-Link: Flush cached page views every 5 minutes (T-08)
Schedule::command('biolinks:flush-views')->everyFiveMinutes()->runInBackground();

// Tactical Bio-Link: Prune raw link clicks older than 90 days daily at 03:00 AM (T-09)
Schedule::command('biolinks:prune-clicks --days=90')->dailyAt('03:00');

// Momen Aksi archive (no-op while MOMEN_ARCHIVE_ENABLED=false): queue pending and
// retryable moments, then drop archive files past MOMEN_ARCHIVE_DAYS (data kept).
Schedule::command('momen:archive')->everyFiveMinutes()->withoutOverlapping(10);
Schedule::command('momen:prune-archives')->dailyAt('03:30');

// YouTube WebSub: Subscribe new channels & renew expiring leases every 6 hours
Schedule::command('websub:sync')->everySixHours()->withoutOverlapping(15);

