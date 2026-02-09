<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

/**
 * Schedule monthly interest application on the 1st of every month at 00:00
 */
Schedule::command('app:apply-monthly-interest')
    ->monthlyOn(1, '00:00')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping()
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('Monthly interest application command failed');
    })
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('Monthly interest application command executed successfully');
    });

