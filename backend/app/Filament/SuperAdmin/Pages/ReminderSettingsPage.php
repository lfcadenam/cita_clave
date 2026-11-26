<?php

namespace App\Filament\SuperAdmin\Pages;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\SystemSetting;
use BackedEnum;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use UnitEnum;

class ReminderSettingsPage extends Page
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-clock';
    protected static string | UnitEnum | null $navigationGroup = 'SISTEMA & PLATAFORMA';
    protected static ?string $title = 'Automatización de Recordatorios';
    protected static ?string $navigationLabel = 'Horario de Recordatorios';
    protected static ?string $slug = 'horario-recordatorios';
    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.super-admin.pages.reminder-settings';

    public string $reminderTime = '10:00';
    public bool $isReminderEnabled = true;
    public string $timezone = 'America/Bogota';

    // Disparo manual
    public string $testDate = '';
    public bool $testForce = false;
    public bool $isExecuting = false;
    public ?string $executionOutput = null;

    public function mount(): void
    {
        $this->reminderTime = SystemSetting::get('appointment_reminder_time', '10:00');
        $rawEnabled = SystemSetting::get('appointment_reminder_enabled', '1');
        $this->isReminderEnabled = filter_var($rawEnabled, FILTER_VALIDATE_BOOLEAN);
        $this->testDate = Carbon::tomorrow('America/Bogota')->toDateString();
    }

    /**
     * Establece una hora rápida sugerida
     */
    public function setTimePreset(string $time): void
    {
        $this->reminderTime = $time;
    }

    /**
     * Guarda la configuración de horario del sistema
     */
    public function saveSettings(): void
    {
        $this->validate([
            'reminderTime' => ['required', 'regex:/^(?:[01]\d|2[0-3]):[0-5]\d$/'],
            'isReminderEnabled' => ['boolean'],
        ], [
            'reminderTime.required' => 'Debes ingresar una hora de ejecución.',
            'reminderTime.regex' => 'El formato de hora debe ser HH:MM (por ejemplo: 10:00 o 08:30).',
        ]);

        SystemSetting::set(
            'appointment_reminder_time',
            $this->reminderTime,
            'Hora diaria de envío de recordatorios de citas',
            'scheduler'
        );

        SystemSetting::set(
            'appointment_reminder_enabled',
            $this->isReminderEnabled ? '1' : '0',
            'Estado activo/inactivo del cron de recordatorios',
            'scheduler'
        );

        Notification::make()
            ->title('Horario actualizado con éxito')
            ->body("El proceso de recordatorios se ejecutará diariamente a las {$this->reminderTime} (Hora Colombia).")
            ->success()
            ->send();
    }

    /**
     * Ejecuta manualmente el proceso de recordatorios para la fecha seleccionada
     */
    public function triggerManualExecution(): void
    {
        $this->validate([
            'testDate' => ['required', 'date_format:Y-m-d'],
        ]);

        $this->isExecuting = true;

        try {
            $params = [
                '--date' => $this->testDate,
            ];

            if ($this->testForce) {
                $params['--force'] = true;
            }

            Artisan::call('appointments:send-reminders', $params);
            $this->executionOutput = Artisan::output();

            Notification::make()
                ->title('Recordatorios procesados correctamente')
                ->body("Ejecución manual completada para la fecha {$this->testDate}.")
                ->success()
                ->send();
        } catch (\Throwable $e) {
            $this->executionOutput = "Error: " . $e->getMessage();

            Notification::make()
                ->title('Error al ejecutar el proceso')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        } finally {
            $this->isExecuting = false;
        }
    }

    /**
     * Cantidad de citas confirmadas para mañana
     */
    public function getPendingTomorrowCountProperty(): int
    {
        $tomorrow = Carbon::tomorrow('America/Bogota')->toDateString();

        return Appointment::withoutGlobalScopes()
            ->where('status', AppointmentStatus::CONFIRMED)
            ->whereDate('appointment_date', $tomorrow)
            ->whereNull('reminder_24h_sent_at')
            ->count();
    }

    /**
     * Total de citas confirmadas para mañana
     */
    public function getTotalTomorrowCountProperty(): int
    {
        $tomorrow = Carbon::tomorrow('America/Bogota')->toDateString();

        return Appointment::withoutGlobalScopes()
            ->where('status', AppointmentStatus::CONFIRMED)
            ->whereDate('appointment_date', $tomorrow)
            ->count();
    }

    /**
     * Últimos recordatorios despachados
     */
    public function getRecentRemindersProperty(): Collection
    {
        return Appointment::withoutGlobalScopes()
            ->with(['tenant', 'service'])
            ->whereNotNull('reminder_24h_sent_at')
            ->latest('reminder_24h_sent_at')
            ->limit(10)
            ->get();
    }
}
