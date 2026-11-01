<?php

namespace App\Filament\Pages;

use App\Models\Tenant;
use App\Services\WhatsAppNotificationService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Log;
use UnitEnum;

class WhatsAppSettingsPage extends Page
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static string | UnitEnum | null $navigationGroup = 'Configuración de Salón';
    protected static ?string $title = 'Canal de WhatsApp Oficial';
    protected static ?string $navigationLabel = 'Conexión WhatsApp';
    protected static ?int $navigationSort = 10;
    protected static ?string $slug = 'configuracion-whatsapp';

    protected string $view = 'filament.pages.whatsapp-settings';

    // Form fields
    public string $whatsapp_driver = 'meta';
    public ?string $meta_phone_number_id = '';
    public ?string $meta_waba_id = '';
    public ?string $meta_access_token = '';
    public ?string $meta_verify_token = '';
    public ?string $whatsapp_number = '';

    // Test form
    public string $test_phone = '';
    public string $test_mode = 'template'; // 'template' o 'text'
    public string $test_template_name = 'reserva_confirmada';
    public string $test_template_lang = 'es_CO';
    public ?string $test_custom_message = '';

    // Status state
    public array $accountStatus = [
        'connected' => false,
        'state' => 'checking',
        'message' => 'Consultando estado...',
    ];
    public ?string $lastTestResult = null;
    public ?bool $lastTestSuccess = null;
    public array $wabaTemplates = [];
    public bool $hasSyncedTemplates = false;

    public function mount(): void
    {
        $tenant = $this->getTenant();

        if ($tenant) {
            $this->whatsapp_driver = $tenant->whatsapp_driver ?: (config('whatsapp.driver') ?: 'meta');
            $this->meta_phone_number_id = $tenant->meta_phone_number_id ?: config('whatsapp.meta.phone_number_id', '');
            $this->meta_waba_id = $tenant->meta_waba_id ?: config('whatsapp.meta.waba_id', '');
            $this->meta_access_token = $tenant->meta_access_token ?: config('whatsapp.meta.access_token', '');
            $this->meta_verify_token = $tenant->meta_verify_token ?: config('whatsapp.meta.verify_token', 'nuvex_meta_verify_2026');
            $this->whatsapp_number = $tenant->whatsapp_number ?: ($tenant->phone ?: '');
            $this->test_phone = preg_replace('/\D/', '', (string) ($this->whatsapp_number ?: config('whatsapp.admin_phone', '3106080402')));
        }

        $this->checkConnection();
    }

    public function getTenant(): ?Tenant
    {
        if (app()->has('active_tenant')) {
            return app('active_tenant');
        }

        if (auth()->check() && auth()->user()->tenant_id) {
            return Tenant::find(auth()->user()->tenant_id);
        }

        return Tenant::where('is_active', true)->first();
    }

    /**
     * Guarda la configuración en la base de datos para el salón activo.
     */
    public function saveSettings(): void
    {
        $this->validate([
            'whatsapp_driver' => 'required|in:meta,evolution,log',
            'meta_phone_number_id' => 'nullable|string|max:100',
            'meta_waba_id' => 'nullable|string|max:100',
            'meta_access_token' => 'nullable|string',
            'meta_verify_token' => 'nullable|string|max:150',
            'whatsapp_number' => 'nullable|string|max:30',
        ]);

        $tenant = $this->getTenant();

        if (! $tenant) {
            Notification::make()
                ->title('Error')
                ->body('No se encontró un salón activo para guardar la configuración.')
                ->danger()
                ->send();
            return;
        }

        $tenant->update([
            'whatsapp_driver' => $this->whatsapp_driver,
            'meta_phone_number_id' => trim((string) $this->meta_phone_number_id),
            'meta_waba_id' => trim((string) $this->meta_waba_id),
            'meta_access_token' => trim((string) $this->meta_access_token),
            'meta_verify_token' => trim((string) $this->meta_verify_token) ?: 'nuvex_meta_verify_2026',
            'whatsapp_number' => trim((string) $this->whatsapp_number),
        ]);

        Notification::make()
            ->title('Configuración Guardada')
            ->body('Los parámetros de conexión de WhatsApp se han actualizado correctamente.')
            ->success()
            ->send();

        $this->checkConnection();
    }

    /**
     * Consulta en vivo contra Meta Graph API o Evolution API el estado del canal.
     */
    public function checkConnection(): void
    {
        $service = app(WhatsAppNotificationService::class);
        $tenant = $this->getTenant();

        if ($this->whatsapp_driver === 'meta') {
            $this->accountStatus = $service->getMetaAccountStatus($tenant);
        } elseif ($this->whatsapp_driver === 'evolution') {
            $evoStatus = $service->getEvolutionInstanceStatus();
            $state = $evoStatus['instance']['state'] ?? $evoStatus['state'] ?? 'disconnected';
            $isConnected = in_array(strtolower($state), ['open', 'connected']);

            $this->accountStatus = [
                'connected' => $isConnected,
                'state' => $state,
                'message' => $isConnected ? 'Sesión QR activa en Evolution API.' : "Estado en Evolution API: [{$state}].",
                'display_phone_number' => $tenant?->whatsapp_number ?? 'Configurado en el celular',
                'verified_name' => 'Instancia Evolution (' . ($tenant?->slug ?? 'paola_estudio') . ')',
                'quality_rating' => $isConnected ? 'CONNECTED' : 'DISCONNECTED',
            ];
        } else {
            $this->accountStatus = [
                'connected' => true,
                'state' => 'log',
                'message' => 'Modo Simulación (Log): los mensajes se escriben en storage/logs/laravel.log.',
                'display_phone_number' => 'Simulado',
                'verified_name' => 'Driver Log Local',
                'quality_rating' => 'TESTING',
            ];
        }
    }

    /**
     * Sincroniza en vivo las plantillas registradas en la WABA de Meta.
     */
    public function syncMetaTemplates(): void
    {
        $service = app(WhatsAppNotificationService::class);
        $tenant = $this->getTenant();
        if ($tenant) {
            $tenant->whatsapp_driver = $this->whatsapp_driver;
            if (!empty($this->meta_phone_number_id)) {
                $tenant->meta_phone_number_id = trim((string) $this->meta_phone_number_id);
            }
            if (!empty($this->meta_waba_id)) {
                $tenant->meta_waba_id = trim((string) $this->meta_waba_id);
            }
            if (!empty($this->meta_access_token)) {
                $tenant->meta_access_token = trim((string) $this->meta_access_token);
            }
        }

        $res = $service->getMetaTemplates($tenant);
        $this->hasSyncedTemplates = true;

        if ($res['success']) {
            $this->wabaTemplates = $res['templates'] ?? [];
            $count = count($this->wabaTemplates);
            Notification::make()
                ->title("Plantillas Sincronizadas ({$count})")
                ->body("Se obtuvieron {$count} plantillas oficiales registradas en Meta WhatsApp Manager.")
                ->success()
                ->send();
        } else {
            $this->wabaTemplates = [];
            Notification::make()
                ->title("Error al Consultar Plantillas")
                ->body($res['message'] ?? 'No se pudieron consultar las plantillas de Meta.')
                ->danger()
                ->send();
        }
    }

    public function selectTemplate(string $name, string $language): void
    {
        $this->test_template_name = $name;
        $this->test_template_lang = $language;
    }

    /**
     * Envía un mensaje de prueba en vivo para confirmar la entrega.
     */
    public function sendTestMessage(): void
    {
        $this->validate([
            'test_phone' => 'required|string|min:7|max:25',
        ], [
            'test_phone.required' => 'Ingresa un número de celular de destino.',
        ]);

        $service = app(WhatsAppNotificationService::class);
        $tenant = $this->getTenant();

        // Sincronizar credenciales del formulario en el objeto tenant para que el test use siempre los datos visibles en pantalla
        if ($tenant) {
            $tenant->whatsapp_driver = $this->whatsapp_driver;
            if (!empty($this->meta_phone_number_id)) {
                $tenant->meta_phone_number_id = trim((string) $this->meta_phone_number_id);
            }
            if (!empty($this->meta_waba_id)) {
                $tenant->meta_waba_id = trim((string) $this->meta_waba_id);
            }
            if (!empty($this->meta_access_token)) {
                $tenant->meta_access_token = trim((string) $this->meta_access_token);
            }
            if (!empty($this->meta_verify_token)) {
                $tenant->meta_verify_token = trim((string) $this->meta_verify_token);
            }
            if (!empty($this->whatsapp_number)) {
                $tenant->whatsapp_number = trim((string) $this->whatsapp_number);
            }
        }

        $destClean = $service->sanitizePhoneNumber($this->test_phone);
        $businessPhoneClean = $service->sanitizePhoneNumber((string) ($this->whatsapp_number ?: $tenant?->whatsapp_number ?: config('whatsapp.admin_phone', '')));

        if (!empty($businessPhoneClean) && $destClean === $businessPhoneClean) {
            $this->lastTestSuccess = false;
            $this->lastTestResult = "⚠️ Meta API Error (#100): El teléfono destinatario (+{$destClean}) es el mismo número registrado como WhatsApp Business de tu salón. Meta no permite que una cuenta comercial se envíe mensajes a sí misma. Por favor ingresa un número de celular diferente (por ejemplo, tu móvil personal o el de un cliente/amigo).";
            Notification::make()
                ->title('Número de Destino Inválido')
                ->body('No puedes enviarte un mensaje a la misma línea comercial del salón (Error #100). Ingresa otro número de celular.')
                ->warning()
                ->send();
            return;
        }

        $this->lastTestResult = 'Enviando...';
        $this->lastTestSuccess = null;

        if ($this->whatsapp_driver === 'meta' && $this->test_mode === 'template') {
            $tpl = trim($this->test_template_name) ?: 'reserva_confirmada';
            if ($tpl === 'hello_world') {
                $this->lastTestSuccess = false;
                $this->lastTestResult = "Meta API Error (#131058): La plantilla 'hello_world' solo está permitida por Meta en números públicos de prueba (sandbox). Al estar usando un número real o Phone Number ID de producción, Meta bloquea 'hello_world'. Debes seleccionar una plantilla propia aprobada en tu WhatsApp Manager (ej: 'reserva_confirmada', 'cita_recibida', 'recordatorio_cita_24h') con idioma 'es_CO' o 'es'.";
                Notification::make()
                    ->title('Plantilla hello_world No Permitida en Números Reales')
                    ->body('Meta bloquea hello_world en números reales (Error #131058). Usa una plantilla propia aprobada como "reserva_confirmada" o "cita_recibida".')
                    ->danger()
                    ->send();
                return;
            }
            if (in_array($tpl, ['reserva_confirma', 'eserva_confirmada', 'reserva_confirmad']) || str_starts_with($tpl, 'reserva_confirm')) {
                $tpl = 'reserva_confirmada';
                $this->test_template_name = 'reserva_confirmada';
            }
            $lang = trim($this->test_template_lang) ?: 'es_CO';
            if ($lang === 'es_ES') {
                $lang = 'es_CO';
                $this->test_template_lang = 'es_CO';
            }
            $sampleAppt = \App\Models\Appointment::with('service')->latest()->first();
            $apptNum = $sampleAppt?->appointment_number ?? 'PA-261013-MSO7';
            $clientName = $sampleAppt?->client_name ?? 'Vivian Mohnblatt';
            $serviceName = $sampleAppt?->service?->name ?? 'Limpieza Pedicura';
            $dateStr = now()->addDay()->format('d/m/Y') . ' a las 10:00 AM';
            $confirmUrl = url("/reserva/confirmacion/{$apptNum}");
            $salonName = $tenant?->name ?? 'La Belle Nails';
            $tomorrowDate = now()->addDay()->format('d/m/Y');

            if ($tpl === 'jaspers_market_order_confirmation_v1') {
                $paramVariants = [
                    [
                        $clientName,
                        "#{$apptNum} ({$serviceName})",
                        "{$dateStr} - Confirma tu asistencia en: {$confirmUrl}",
                    ],
                ];
                $langsToTry = [$lang, 'en_US'];
            } elseif (in_array($tpl, ['reserva_confirmada', 'confirmacion_cita_salon', 'recordatorio_cita_24h', 'cita_recibida', 'cita_confirmada_abono'])) {
                if ($lang === 'en_US') {
                    $lang = 'es_CO';
                    $this->test_template_lang = 'es_CO';
                }
                // Variantes adaptativas de parámetros: 5, 4 (guía oficial Nuvex), 3, 2, 1 o 0 variables
                $paramVariants = [
                    [$clientName, $apptNum, $serviceName, $dateStr, $confirmUrl],
                    [$clientName, $salonName, $tomorrowDate, '10:00 AM'],
                    [$clientName, $serviceName, $dateStr, $confirmUrl],
                    [$clientName, $serviceName, $dateStr],
                    [$clientName, $dateStr],
                    [$clientName],
                    [],
                ];
                $langsToTry = array_values(array_unique([$lang, 'es_CO', 'es']));
            } else {
                $paramVariants = [
                    [$clientName, $apptNum, $serviceName, $dateStr, $confirmUrl],
                    [$clientName, $salonName, $tomorrowDate, '10:00 AM'],
                    [],
                ];
                $langsToTry = [$lang];
            }

            $result = ['success' => false, 'message' => ''];
            $firstLangResult = null;

            foreach ($langsToTry as $tryLang) {
                foreach ($paramVariants as $variant) {
                    $attempt = $service->sendTemplateViaMetaWithDetails(
                        phone: $this->test_phone,
                        templateName: $tpl,
                        bodyParameters: $variant,
                        languageCode: $tryLang,
                        tenant: $tenant
                    );

                    if ($firstLangResult === null) {
                        $firstLangResult = $attempt;
                    }

                    if ($attempt['success'] ?? false) {
                        $this->test_template_lang = $tryLang;
                        $varCount = count($variant);
                        $attempt['message'] .= " (Variables adaptadas automáticamente: {$varCount}).";
                        $result = $attempt;
                        break 2;
                    }

                    $result = $attempt;

                    // Si el error no fue 100 (Invalid parameter), no es por número de variables, no insistir en más variantes
                    if ((int) ($attempt['error_code'] ?? 0) !== 100) {
                        break;
                    }
                }
            }

            // Si ambos fallaron y el usuario había pedido es_CO, priorizar mostrar el error del idioma solicitado (es_CO)
            if (!($result['success'] ?? false) && $lang === 'es_CO' && $firstLangResult) {
                $result = $firstLangResult;
            }

            // Si la plantilla en español sigue "En revisión" en Meta (#132001), informar con claridad
            // y enviar el respaldo con la plantilla preaprobada jaspers_market_order_confirmation_v1 para que sí llegue al celular
            if (!($result['success'] ?? false) && in_array((int) ($result['error_code'] ?? 0), [132000, 132001]) && $tpl !== 'jaspers_market_order_confirmation_v1') {
                $fallbackRes = $service->sendTemplateViaMetaWithDetails(
                    phone: $this->test_phone,
                    templateName: 'jaspers_market_order_confirmation_v1',
                    bodyParameters: [
                        $clientName,
                        "#{$apptNum} ({$serviceName})",
                        "{$dateStr} - Confirma tu asistencia en: {$confirmUrl}",
                    ],
                    languageCode: 'en_US',
                    tenant: $tenant
                );

                if ($fallbackRes['success'] ?? false) {
                    $result = [
                        'success' => true,
                        'message' => "Aviso: La plantilla '{$tpl}' (es_CO) sigue «En revisión» en Meta y Meta bloquea su uso hasta aprobarla. Te enviamos el mensaje de respaldo con el link de confirmación usando la plantilla preaprobada (ID: {$fallbackRes['wamid']}).",
                    ];
                }
            }
        } else {
            $msg = trim($this->test_custom_message ?: '') ?: (
                "Prueba de conexión exitosa desde Cita Clave.\n"
                . "Salón: " . ($tenant?->name ?? 'Paola Aguilera') . "\n"
                . "Proveedor: " . strtoupper($this->whatsapp_driver) . "\n"
                . "Fecha y hora: " . now()->format('d/m/Y H:i:s')
            );

            $result = $service->sendTextMessageWithDetails(
                phone: $this->test_phone,
                message: $msg,
                tenant: $tenant
            );
        }

        $this->lastTestSuccess = (bool) ($result['success'] ?? false);
        $this->lastTestResult = $result['message'] ?? 'Sin respuesta';

        if ($this->lastTestSuccess) {
            Notification::make()
                ->title('¡Prueba Exitosa!')
                ->body($this->lastTestResult)
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Fallo al Enviar')
                ->body($this->lastTestResult)
                ->danger()
                ->send();
        }

        $this->checkConnection();
    }
}
