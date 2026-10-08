<?php

namespace App\Console\Commands;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Services\AppointmentMailNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendAppointmentRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'appointments:send-reminders {--date= : Fecha específica a recordar (Y-m-d). Por defecto mañana} {--force : Enviar incluso si ya se envió}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía correos recordatorios 24 horas antes a clientas con citas confirmadas para confirmar su asistencia';

    public function __construct(
        protected AppointmentMailNotificationService $mailService,
        protected \App\Services\WhatsAppNotificationService $whatsAppService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $targetDateStr = $this->option('date') ?: Carbon::tomorrow()->toDateString();
        $force = (bool) $this->option('force');

        $this->info("Buscando citas confirmadas para la fecha: {$targetDateStr} (24h de anticipación)...");

        $query = Appointment::withoutGlobalScopes()
            ->with(['tenant', 'service'])
            ->where('status', AppointmentStatus::CONFIRMED)
            ->whereDate('appointment_date', $targetDateStr)
            ->where(function ($q) {
                $q->where(function ($sub) {
                    $sub->whereNotNull('client_email')->where('client_email', '!=', '');
                })->orWhere(function ($sub) {
                    $sub->whereNotNull('client_phone')->where('client_phone', '!=', '');
                });
            });

        if (!$force) {
            $query->whereNull('reminder_24h_sent_at');
        }

        $appointments = $query->get();

        if ($appointments->isEmpty()) {
            $this->info("No se encontraron citas pendientes de recordatorio para {$targetDateStr}.");
            return self::SUCCESS;
        }

        $this->info("Se encontraron {$appointments->count()} citas para procesar.");

        $sentCount = 0;
        foreach ($appointments as $appointment) {
            $this->line("-> Enviando recordatorio para cita #{$appointment->appointment_number} ({$appointment->client_name} - Tel: {$appointment->client_phone})...");
            
            $mailSent = false;
            if (!empty($appointment->client_email)) {
                $mailSent = $this->mailService->sendReminder24hEmail($appointment);
            }

            $waSent = false;
            if (!empty($appointment->client_phone)) {
                $waSent = $this->whatsAppService->sendReminder24h($appointment);
            }

            if ($mailSent || $waSent) {
                $sentCount++;
                $appointment->update(['reminder_24h_sent_at' => now()]);
                $this->info("   [OK] Recordatorio despachado (Email: " . ($mailSent ? 'Sí' : 'No') . " | WhatsApp: " . ($waSent ? 'Sí' : 'No') . ").");
            } else {
                $this->warn("   [ERROR] No se pudo enviar el recordatorio para #{$appointment->appointment_number}.");
            }
        }

        $this->info("Proceso completado. Recordatorios enviados: {$sentCount}/{$appointments->count()}.");

        return self::SUCCESS;
    }
}
