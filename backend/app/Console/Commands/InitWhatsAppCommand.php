<?php

namespace App\Console\Commands;

use App\Services\WhatsAppNotificationService;
use Illuminate\Console\Command;

class InitWhatsAppCommand extends Command
{
    protected $signature = 'whatsapp:init';
    protected $description = 'Inicializa la instancia en Evolution API y genera la imagen del código QR para vincular WhatsApp';

    public function __construct(
        protected WhatsAppNotificationService $whatsAppService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info("=== Inicializando Instancia WhatsApp (Evolution API) ===");
        
        $this->line("1. Solicitando creación de instancia 'paola_estudio'...");
        $result = $this->whatsAppService->createEvolutionInstance();

        $this->line("2. Obteniendo código QR de conexión...");
        $base64 = $this->whatsAppService->getEvolutionQrCode();

        if (empty($base64) && isset($result['qrcode']['base64'])) {
            $base64 = $result['qrcode']['base64'];
        }

        if (!empty($base64)) {
            // Limpiar prefijo data:image/png;base64, si viene
            $cleanBase64 = preg_replace('/^data:image\/\w+;base64,/', '', $base64);
            $imageData = base64_decode($cleanBase64);

            if ($imageData) {
                $publicPath = public_path('whatsapp-qr.png');
                file_put_contents($publicPath, $imageData);
                $this->info("✅ Código QR guardado exitosamente en: {$publicPath}");
            }
        }

        $this->info("");
        $this->info("🎉 ¡Instancia 'paola_estudio' creada y lista para vincular!");
        $this->line("");
        $this->line("📱 Para escanear el Código QR con tu WhatsApp:");
        $this->line("👉 Si abres la web pública: https://labellenailsbog.com/whatsapp-qr.png");
        $this->line("👉 O directamente vía IP:  http://77.237.239.179:8085/instance/connect/paola_estudio");
        $this->line("");
        $this->line("Pasos en tu celular:");
        $this->line("1. Abre WhatsApp -> Dispositivos vinculados -> Vincular un dispositivo.");
        $this->line("2. Escanea el código QR.");
        $this->line("3. Luego ejecuta: php artisan whatsapp:test 3106080402");
        $this->line("");

        return self::SUCCESS;
    }
}
