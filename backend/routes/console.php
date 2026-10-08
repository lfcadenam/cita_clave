<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cron programado para enviar recordatorios de citas 24h antes a clientas
Schedule::command('appointments:send-reminders')
    ->dailyAt('08:00')
    ->name('appointments-reminders-24h')
    ->withoutOverlapping();

