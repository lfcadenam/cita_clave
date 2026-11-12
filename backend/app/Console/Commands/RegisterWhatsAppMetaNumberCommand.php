<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\WhatsAppNotificationService;
use Illuminate\Console\Command;

class RegisterWhatsAppMetaNumberCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:register {pin=123456 : PIN de 6 dígitos numéricos para registro en Meta (por defecto 123456)} {--tenant= : ID o slug del salón / tenant a registrar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Registra y activa el número de teléfono en Meta WhatsApp Cloud API con un PIN de 6 dígitos (resuelve error #133010: Account not registered)';

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
        $pin = (string) $this->argument('pin');
        $tenantIdentifier = $this->option('tenant');

        if (!preg_match('/^\d{6}$/', $pin)) {
            $this->error("❌ El PIN debe componerse exactamente de 6 dígitos numéricos (ej: 123456).");
            return self::FAILURE;
        }

        $tenant = null;
        if (!empty($tenantIdentifier)) {
            $tenant = is_numeric($tenantIdentifier)
                ? Tenant::find($tenantIdentifier)
                : Tenant::where('slug', $tenantIdentifier)->first();

            if (!$tenant) {
                $this->error("❌ No se encontró el salón/tenant con identificador [{$tenantIdentifier}].");
                return self::FAILURE;
            }
        } else {
            $tenant = Tenant::where('is_active', true)->first();
        }

        $creds = $this->whatsAppService->resolveMetaCredentials(null, $tenant);
        $phoneId = $creds['phone_number_id'] ?? '';

        if (empty($phoneId)) {
            $this->error("❌ Faltan credenciales de Meta (Phone Number ID o Access Token) para este salón o en .env.");
            return self::FAILURE;
        }

        $this->info("=== Registrando Número en Meta WhatsApp Cloud API ===");
        $this->line("Salón: " . ($tenant?->name ?? 'Configuración Global'));
        $this->line("Phone Number ID: {$phoneId}");
        $this->line("PIN de autenticación: {$pin}");
        $this->line("Invocando endpoint POST /v21.0/{$phoneId}/register ...");

        $res = $this->whatsAppService->registerMetaPhoneNumber($pin, $tenant);

        if ($res['success'] ?? false) {
            $this->newLine();
            $this->info("✅ ¡Número registrado y activado exitosamente en Meta Cloud API!");
            $this->info("Mensaje de Meta: " . ($res['message'] ?? 'OK'));
            $this->line("");
            $this->line("👉 Ahora puedes ejecutar una prueba de envío con:");
            $this->line("   php artisan whatsapp:test <numero_celular> --template=reserva_confirmada --lang=es_CO");
            return self::SUCCESS;
        }

        $this->newLine();
        $this->error("❌ Error al registrar el número en Meta:");
        $this->error($res['message'] ?? 'Error desconocido');

        if (str_contains(strtolower($res['message'] ?? ''), 'pin')) {
            $this->warn("💡 Nota sobre el PIN: Si en WhatsApp Manager ya habías definido un PIN de verificación en 2 pasos para este número, debes usar ese mismo PIN aquí: php artisan whatsapp:register <tu_pin_existente>");
        }

        return self::FAILURE;
    }
}
