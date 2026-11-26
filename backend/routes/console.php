<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cron programado para enviar recordatorios a clientas a las 10:00 AM del día anterior a su cita (Hora Colombia)
Schedule::command('appointments:send-reminders')
    ->dailyAt('10:00')
    ->timezone('America/Bogota')
    ->name('appointments-reminders-24h')
    ->withoutOverlapping();

