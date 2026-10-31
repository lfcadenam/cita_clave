<?php

namespace App\Console\Commands;

use App\Services\WhatsAppNotificationService;
use Illuminate\Console\Command;

class TestWhatsAppNotificationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:test {phone : Número de teléfono celular (ej: 3106080402)} {--message= : Mensaje personalizado de prueba} {--template= : Nombre de plantilla Meta a enviar (ej: reserva_confirmada)} {--lang= : Código de idioma de la plantilla (ej: es_CO, es)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía un mensaje de prueba de WhatsApp vía el driver activo (Meta Cloud API oficial o Evolution API)';

    public function __construct(
        protected WhatsAppNotificationService $whatsAppService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $phone = $this->argument('phone');
        $customMessage = $this->option('message');
        $template = $this->option('template');

        $driver = $this->whatsAppService->resolveDriver();
        $this->info("Iniciando prueba de WhatsApp...");
        $this->info("Driver activo: [{$driver}]");
        $this->info("Destinatario: {$phone}");

        if ($driver === 'meta') {
            $this->line("Consultando estado de la cuenta en Meta Graph API...");
            $status = $this->whatsAppService->getMetaAccountStatus();

            if (! $status['connected']) {
                $this->error("❌ {$status['message']}");
                return self::FAILURE;
            }

            $this->info("✅ Número: {$status['display_phone_number']} | Nombre verificado: {$status['verified_name']} | Calidad: {$status['quality_rating']}");

            if ($template) {
                $this->line("Enviando plantilla '{$template}'...");
                $result = $this->whatsAppService->sendTemplateViaMetaWithDetails($phone, $template, [], $this->option('lang'));
                return $this->report($result);
            }
        }

        if ($driver === 'evolution') {
            $this->line("Consultando estado de Evolution API...");
            $attempts = 0;
            $state = 'desconocido';

            do {
                $status = $this->whatsAppService->getEvolutionInstanceStatus();
                $state = $status['instance']['state'] ?? $status['state'] ?? 'desconocido';

                if (in_array(strtolower($state), ['open', 'connected'])) {
                    break;
                }

                if (strtolower($state) === 'connecting') {
                    $this->line("⏳ La sesión se está estableciendo con WhatsApp (Estado: connecting)... esperando 3s (intento " . ($attempts + 1) . "/6)...");
                    sleep(3);
                }

                $attempts++;
            } while ($attempts < 6 && strtolower($state) === 'connecting');

            $this->line("Estado de la instancia en Evolution API: [{$state}]");

            if (!in_array(strtolower($state), ['open', 'connected'])) {
                $this->error("❌ La sesión de WhatsApp no está activa (Estado: [{$state}]). Si dice 'connecting', espera unos segundos más. Si dice 'close' o 'disconnected', vincula de nuevo el QR.");
                return self::FAILURE;
            }
        }

        $message = $customMessage ?: "Mensaje de prueba del sistema de agendamiento CitaClave enviado vía [{$driver}].\n\nFecha y hora: " . now()->format('d/m/Y H:i:s');

        $this->line("Enviando mensaje de texto...");
        $result = $this->whatsAppService->sendTextMessageWithDetails($phone, $message);

        return $this->report($result);
    }

    protected function report(array $result): int
    {
        if ($result['success']) {
            $this->info("✅ {$result['message']}");
            return self::SUCCESS;
        }

        $this->error("❌ {$result['message']}");
        return self::FAILURE;
    }
}
