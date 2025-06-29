<?php

use Illuminate\Console\Scheduling\Schedule;

return function (Schedule $schedule) {
    // Hourly imports
    $schedule->command('property-services:import-properties --provider=vrbo')
        ->hourly()
        ->withoutOverlapping()
        ->runInBackground();

    $schedule->command('property-services:import-properties --provider=owner_reservations')
        ->hourly()
        ->withoutOverlapping()
        ->runInBackground();

    // Weekly cleanup
    $schedule->command('property-services:cleanup-import-data --days=30')
        ->weekly()
        ->sundays()
        ->at('02:00');

    // Daily orphan cleanup
    $schedule->command('property-services:cleanup-import-data --days=7')
        ->daily()
        ->at('03:00');
};