<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Capture equipment snapshot every 30 minutes
Schedule::command('equipment:capture-snapshot')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// Clean up old snapshots daily at 2 AM
Schedule::command('equipment:cleanup-snapshots')
    ->dailyAt('02:00')
    ->withoutOverlapping();

// Import MES WIP data from Excel files every hour
Schedule::command('import:mes-wip --archive')
    ->hourly()
    ->withoutOverlapping()
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('MES WIP import completed successfully');
    })
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('MES WIP import failed');
    });
