<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Programmazione reset automatico tenant demo
Schedule::command('demo:reset demo.local')
    ->daily()
    ->at('03:00')
    ->timezone('Europe/Rome')
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('Demo tenant reset scheduled task completed successfully');
    })
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('Demo tenant reset scheduled task failed');
    });

// Programmazione pulizia log demo (opzionale)
Schedule::command('log:clear')
    ->weekly()
    ->sundays()
    ->at('04:00')
    ->timezone('Europe/Rome');
