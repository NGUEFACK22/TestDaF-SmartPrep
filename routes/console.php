<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Purge RGPD hebdomadaire (médias candidats >365j, logs >180j).
// Requiert le cron Laravel : * * * * * php artisan schedule:run
Schedule::command('testdaf:purge --days=365 --logs-days=180')
    ->weekly()
    ->sundays()
    ->at('03:00')
    ->name('testdaf-purge-rgpd');

// Abandon quotidien des tentatives fantômes (sans activité >7j).
Schedule::command('testdaf:abandon-stale --days=7')
    ->dailyAt('02:30')
    ->name('testdaf-abandon-stale');
