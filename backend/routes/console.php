<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Hora dinámica configurada por el Super Administrador en la plataforma (por defecto 10:00 AM Colombia)
$reminderTime = '10:00';
$isReminderEnabled = true;

try {
    if (\Illuminate\Support\Facades\Schema::hasTable('system_settings')) {
        $reminderTime = \App\Models\SystemSetting::get('appointment_reminder_time', '10:00');
        $rawEnabled = \App\Models\SystemSetting::get('appointment_reminder_enabled', '1');
        $isReminderEnabled = filter_var($rawEnabled, FILTER_VALIDATE_BOOLEAN);
    }
} catch (\Throwable $e) {
    $reminderTime = '10:00';
    $isReminderEnabled = true;
}

// Programador diario de recordatorios (ejecutado por el contenedor scheduler o cron)
if ($isReminderEnabled && !empty($reminderTime)) {
    Schedule::command('appointments:send-reminders')
        ->dailyAt($reminderTime)
        ->timezone('America/Bogota')
        ->name('appointments-reminders-24h')
        ->withoutOverlapping();
}

