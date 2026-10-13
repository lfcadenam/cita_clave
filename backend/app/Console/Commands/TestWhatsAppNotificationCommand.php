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
    protected $signature = 'whatsapp:test {phone : Número de teléfono celular (ej: 3106080402)} {--message= : Mensaje personalizado de prueba}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía un mensaje de prueba de WhatsApp a través del Gateway (Evolution API / Baileys)';

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

        $driver = config('whatsapp.driver');
        $this->info("Iniciando prueba de WhatsApp...");
        $this->info("Driver activo: [{$driver}]");
        $this->info("Destinatario: {$phone}");

        if ($driver === 'evolution') {
            $this->line("Consultando estado de Evolution API...");
            $status = $this->whatsAppService->getEvolutionInstanceStatus();
            $state = $status['instance']['state'] ?? $status['state'] ?? 'desconocido';
            $this->line("Estado de la instancia en Evolution API: [{$state}]");
        }

        $message = $customMessage ?: "✨ *Mensaje de Prueba Nuvex WhatsApp*\n\nHola! Este es un mensaje de prueba enviado desde tu sistema de agendamiento *CitaClave / Paola Aguilera* a través del Gateway Evolution API.\n\nFecha y hora: " . now()->format('d/m/Y H:i:s');

        $this->line("Enviando mensaje...");
        $result = $this->whatsAppService->sendTextMessageWithDetails($phone, $message);

        if ($result['success']) {
            $this->info("✅ {$result['message']}");
            return self::SUCCESS;
        }

        $this->error("❌ {$result['message']}");
        return self::FAILURE;
    }
}
