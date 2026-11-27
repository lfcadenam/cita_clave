<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Tenant;
use App\Services\WhatsAppNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WhatsAppAdminController extends Controller
{
    /**
     * Resuelve el Tenant activo para la sesión administrativa.
     */
    protected function getTenant(Request $request): ?Tenant
    {
        if (app()->has('active_tenant')) {
            return app('active_tenant');
        }

        if ($request->user() && $request->user()->tenant_id) {
            return Tenant::find($request->user()->tenant_id);
        }

        if ($request->user() && $request->user()->tenant) {
            return $request->user()->tenant;
        }

        return Tenant::where('is_active', true)->first();
    }

    /**
     * Evalúa el estado de conexión del canal según el driver activo.
     */
    protected function checkConnectionInternal(?Tenant $tenant, ?string $driver = null): array
    {
        $service = app(WhatsAppNotificationService::class);
        $resolvedDriver = $driver ?: ($tenant?->whatsapp_driver ?: 'meta');

        if ($resolvedDriver === 'meta') {
            return $service->getMetaAccountStatus($tenant);
        }

        if ($resolvedDriver === 'evolution') {
            $evoStatus = $service->getEvolutionInstanceStatus();
            $state = $evoStatus['instance']['state'] ?? $evoStatus['state'] ?? 'disconnected';
            $isConnected = in_array(strtolower($state), ['open', 'connected']);

            return [
                'connected' => $isConnected,
                'state' => $state,
                'message' => $isConnected ? 'Sesión QR activa en Evolution API.' : "Estado en Evolution API: [{$state}].",
                'display_phone_number' => $tenant?->whatsapp_number ?? 'Configurado en el celular',
                'verified_name' => 'Instancia Evolution (' . ($tenant?->slug ?? 'paola_estudio') . ')',
                'quality_rating' => $isConnected ? 'CONNECTED' : 'DISCONNECTED',
            ];
        }

        return [
            'connected' => true,
            'state' => 'log',
            'message' => 'Modo Simulación (Log): los mensajes se escriben en storage/logs/laravel.log.',
            'display_phone_number' => 'Simulado',
            'verified_name' => 'Driver Log Local',
            'quality_rating' => 'TESTING',
        ];
    }

    /**
     * Obtener configuración actual del canal de WhatsApp y su estado en vivo.
     */
    public function getSettings(Request $request): JsonResponse
    {
        $tenant = $this->getTenant($request);
        $status = $this->checkConnectionInternal($tenant);

        return response()->json([
            'success' => true,
            'data' => [
                'whatsapp_driver' => $tenant?->whatsapp_driver ?: 'meta',
                'whatsapp_number' => $tenant?->whatsapp_number ?: '',
                'meta_phone_number_id' => $tenant?->meta_phone_number_id ?: '',
                'meta_waba_id' => $tenant?->meta_waba_id ?: '',
                'meta_access_token' => $tenant?->meta_access_token ?: '',
                'meta_verify_token' => $tenant?->meta_verify_token ?: 'nuvex_meta_verify_2026',
                'webhook_url' => url('/api/v1/webhooks/whatsapp'),
                'salon_name' => $tenant?->name ?? 'Cita Clave',
                'is_configured' => ! empty($tenant?->meta_phone_number_id) && ! empty($tenant?->meta_access_token),
                'account_status' => $status,
            ],
        ]);
    }

    /**
     * Verifica en vivo la conectividad con Meta Graph API o Evolution API.
     */
    public function checkStatus(Request $request): JsonResponse
    {
        $tenant = $this->getTenant($request);
        $status = $this->checkConnectionInternal($tenant);

        return response()->json([
            'success' => true,
            'account_status' => $status,
        ]);
    }

    /**
     * Actualizar credenciales y configuración del canal de WhatsApp.
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $tenant = $this->getTenant($request);

        if (! $tenant) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró un salón activo para guardar la configuración.',
            ], 404);
        }

        $validated = $request->validate([
            'whatsapp_driver' => 'required|in:meta,evolution,log',
            'whatsapp_number' => 'nullable|string|max:50',
            'meta_phone_number_id' => 'nullable|string|max:100',
            'meta_waba_id' => 'nullable|string|max:100',
            'meta_access_token' => 'nullable|string',
            'meta_verify_token' => 'nullable|string|max:100',
        ]);

        $tenant->update([
            'whatsapp_driver' => $validated['whatsapp_driver'],
            'whatsapp_number' => trim((string) ($validated['whatsapp_number'] ?? '')),
            'meta_phone_number_id' => trim((string) ($validated['meta_phone_number_id'] ?? '')),
            'meta_waba_id' => trim((string) ($validated['meta_waba_id'] ?? '')),
            'meta_access_token' => trim((string) ($validated['meta_access_token'] ?? '')),
            'meta_verify_token' => trim((string) ($validated['meta_verify_token'] ?? '')) ?: 'nuvex_meta_verify_2026',
        ]);

        $status = $this->checkConnectionInternal($tenant, $validated['whatsapp_driver']);

        return response()->json([
            'success' => true,
            'message' => 'Configuración de WhatsApp guardada exitosamente.',
            'data' => [
                'whatsapp_driver' => $tenant->whatsapp_driver,
                'whatsapp_number' => $tenant->whatsapp_number,
                'meta_phone_number_id' => $tenant->meta_phone_number_id,
                'meta_waba_id' => $tenant->meta_waba_id,
                'meta_access_token' => $tenant->meta_access_token,
                'meta_verify_token' => $tenant->meta_verify_token,
                'webhook_url' => url('/api/v1/webhooks/whatsapp'),
                'is_configured' => ! empty($tenant->meta_phone_number_id) && ! empty($tenant->meta_access_token),
                'account_status' => $status,
            ],
        ]);
    }

    /**
     * Registra y activa el número en Meta WhatsApp Cloud API con un PIN de 6 dígitos.
     * Resuelve el error de Meta (#133010): Account not registered.
     */
    public function registerPin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'meta_pin' => ['required', 'string', 'regex:/^\d{6}$/'],
            'meta_phone_number_id' => 'nullable|string',
            'meta_access_token' => 'nullable|string',
        ], [
            'meta_pin.required' => 'Ingresa un PIN de 6 dígitos numéricos.',
            'meta_pin.regex' => 'El PIN debe contener exactamente 6 dígitos numéricos (ej: 123456).',
        ]);

        $tenant = $this->getTenant($request);
        if ($tenant) {
            if (! empty($validated['meta_phone_number_id'])) {
                $tenant->meta_phone_number_id = trim($validated['meta_phone_number_id']);
            }
            if (! empty($validated['meta_access_token'])) {
                $tenant->meta_access_token = trim($validated['meta_access_token']);
            }
            $tenant->whatsapp_driver = 'meta';
            $tenant->save();
        }

        $service = app(WhatsAppNotificationService::class);
        $res = $service->registerMetaPhoneNumber(trim($validated['meta_pin']), $tenant);
        $status = $this->checkConnectionInternal($tenant, 'meta');

        return response()->json([
            'success' => (bool) ($res['success'] ?? false),
            'message' => $res['message'] ?? 'Respuesta de registro de Meta recibida.',
            'data' => $res,
            'account_status' => $status,
        ], ($res['success'] ?? false) ? 200 : 422);
    }

    /**
     * Consulta las plantillas registradas en la WABA de Meta.
     */
    public function syncTemplates(Request $request): JsonResponse
    {
        $tenant = $this->getTenant($request);
        $service = app(WhatsAppNotificationService::class);
        $res = $service->getMetaTemplates($tenant);

        return response()->json([
            'success' => (bool) ($res['success'] ?? false),
            'message' => $res['message'] ?? null,
            'templates' => $res['templates'] ?? [],
        ], ($res['success'] ?? false) ? 200 : 422);
    }

    /**
     * Consulta el catálogo de plantillas oficiales con su estado en vivo en Meta.
     */
    public function templatesCatalog(Request $request): JsonResponse
    {
        $tenant = $this->getTenant($request);
        $service = app(WhatsAppNotificationService::class);
        $res = $service->getOfficialTemplatesCatalog($tenant);

        return response()->json($res);
    }

    /**
     * Crea una o todas las plantillas oficiales en Meta Cloud API en 1 solo clic.
     */
    public function createTemplate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'template_key' => 'nullable|string|max:100',
            'create_all' => 'nullable|boolean',
        ]);

        $tenant = $this->getTenant($request);
        $service = app(WhatsAppNotificationService::class);

        if (! empty($validated['create_all'])) {
            $result = $service->createAllOfficialTemplates($tenant);
            $catalog = $service->getOfficialTemplatesCatalog($tenant);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => $result,
                'catalog' => $catalog['templates'] ?? [],
            ], $result['success'] ? 200 : 207);
        }

        $templateKey = $validated['template_key'] ?? '';
        if (empty($templateKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Especifica el identificador de la plantilla (template_key) o activa create_all.',
            ], 422);
        }

        $result = $service->createOfficialTemplate($templateKey, $tenant);
        $catalog = $service->getOfficialTemplatesCatalog($tenant);

        return response()->json([
            'success' => $result['success'] ?? false,
            'message' => $result['message'] ?? 'Plantilla procesada en Meta.',
            'data' => $result,
            'catalog' => $catalog['templates'] ?? [],
        ], ($result['success'] ?? false) ? 200 : 422);
    }

    /**
     * Actualiza y personaliza el cuerpo y variables de una plantilla oficial para el Tenant.
     */
    public function updateTemplate(Request $request, string $key): JsonResponse
    {
        $validated = $request->validate([
            'body' => 'required|string|max:1024',
            'samples' => 'nullable|array',
            'samples.*' => 'nullable|string|max:100',
            'variable_keys' => 'nullable|array',
            'sync_to_meta' => 'nullable|boolean',
        ]);

        $tenant = $this->getTenant($request);
        $service = app(WhatsAppNotificationService::class);

        $result = $service->saveTenantCustomTemplate($key, $validated, $tenant);
        $catalog = $service->getOfficialTemplatesCatalog($tenant);

        return response()->json([
            'success' => $result['success'] ?? false,
            'message' => $result['message'] ?? 'Plantilla actualizada correctamente.',
            'data' => $result,
            'catalog' => $catalog['templates'] ?? [],
        ], ($result['success'] ?? false) ? 200 : 422);
    }

    /**
     * Restablece una plantilla oficial a su versión por defecto del sistema.
     */
    public function resetTemplate(Request $request, string $key): JsonResponse
    {
        $tenant = $this->getTenant($request);
        $service = app(WhatsAppNotificationService::class);

        $result = $service->resetTenantCustomTemplate($key, $tenant);
        $catalog = $service->getOfficialTemplatesCatalog($tenant);

        return response()->json([
            'success' => $result['success'] ?? false,
            'message' => $result['message'] ?? 'Plantilla restablecida a la oficial.',
            'data' => $result,
            'catalog' => $catalog['templates'] ?? [],
        ]);
    }

    /**
     * Enviar mensaje de prueba para verificar conectividad con WhatsApp.
     * Soporta modo plantilla (Meta) y texto libre (24h / Evolution / Log).
     */
    public function sendTest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'required|string|min:7|max:35',
            'test_mode' => 'nullable|string|in:template,text',
            'template_name' => 'nullable|string|max:100',
            'template_lang' => 'nullable|string|max:20',
            'message' => 'nullable|string|max:1000',
        ]);

        $tenant = $this->getTenant($request);
        $service = app(WhatsAppNotificationService::class);

        $destClean = $service->sanitizePhoneNumber($validated['phone']);
        $businessPhoneClean = $service->sanitizePhoneNumber((string) ($tenant?->whatsapp_number ?: config('whatsapp.admin_phone', '')));

        // Validación anti auto-envío (Meta Error #100)
        if (! empty($businessPhoneClean) && $destClean === $businessPhoneClean) {
            return response()->json([
                'success' => false,
                'error_code' => 100,
                'has_pin_error' => false,
                'message' => "Meta API Error (#100): El teléfono destinatario (+{$destClean}) es el mismo número registrado como WhatsApp Business de tu salón. Meta no permite que una cuenta comercial se envíe mensajes a sí misma. Por favor ingresa un número de celular diferente (por ejemplo, tu móvil personal).",
            ], 422);
        }

        $driver = $tenant?->whatsapp_driver ?: 'meta';
        $testMode = $validated['test_mode'] ?? 'template';

        if ($driver === 'meta' && $testMode === 'template') {
            $tpl = trim($validated['template_name'] ?? '') ?: 'reserva_confirmada';

            if ($tpl === 'hello_world') {
                return response()->json([
                    'success' => false,
                    'error_code' => 131058,
                    'has_pin_error' => false,
                    'message' => "Meta API Error (#131058): La plantilla 'hello_world' solo está permitida por Meta en números públicos de prueba (sandbox). Al estar usando un número real o Phone Number ID de producción, Meta bloquea 'hello_world'. Debes seleccionar una plantilla propia aprobada en tu WhatsApp Manager (ej: 'reserva_confirmada', 'cita_recibida', 'recordatorio_cita_24h') con idioma 'es_CO' o 'es'.",
                ], 422);
            }

            if (in_array($tpl, ['reserva_confirma', 'eserva_confirmada', 'reserva_confirmad']) || str_starts_with($tpl, 'reserva_confirm')) {
                $tpl = 'reserva_confirmada';
            }

            $lang = trim($validated['template_lang'] ?? '') ?: 'es_CO';
            if ($lang === 'es_ES') {
                $lang = 'es_CO';
            }

            $sampleAppt = Appointment::with('service')->latest()->first();
            $apptNum = $sampleAppt?->appointment_number ?? 'PA-261013-MSO7';
            $clientName = $sampleAppt?->client_name ?? 'Cliente Prueba';
            $serviceName = $sampleAppt?->service?->name ?? 'Servicio Belleza';
            $dateStr = now()->addDay()->format('d/m/Y') . ' a las 10:00 AM';
            $confirmUrl = url("/reserva/confirmacion/{$apptNum}");
            $salonName = $tenant?->name ?? 'Cita Clave';
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
            } elseif (in_array($tpl, ['confirmacion_cita', 'reserva_confirmada', 'confirmacion_cita_salon', 'recordatorio_cita_24h', 'cita_recibida', 'cita_confirmada_abono'])) {
                if ($lang === 'en_US') {
                    $lang = 'es_CO';
                }
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
                        phone: $validated['phone'],
                        templateName: $tpl,
                        bodyParameters: $variant,
                        languageCode: $tryLang,
                        tenant: $tenant,
                        buttonParameters: [$apptNum]
                    );

                    if ($firstLangResult === null) {
                        $firstLangResult = $attempt;
                    }

                    if ($attempt['success'] ?? false) {
                        $varCount = count($variant);
                        $attempt['message'] .= " (Variables adaptadas automáticamente: {$varCount}).";
                        $result = $attempt;
                        break 2;
                    }

                    $result = $attempt;

                    if ((int) ($attempt['error_code'] ?? 0) !== 100) {
                        break;
                    }
                }
            }

            if (! ($result['success'] ?? false) && $lang === 'es_CO' && $firstLangResult) {
                $result = $firstLangResult;
            }

            // Respaldo jaspers si la plantilla sigue en revisión
            if (! ($result['success'] ?? false) && in_array((int) ($result['error_code'] ?? 0), [132000, 132001]) && $tpl !== 'jaspers_market_order_confirmation_v1') {
                $fallbackRes = $service->sendTemplateViaMetaWithDetails(
                    phone: $validated['phone'],
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
                        'message' => "Aviso: La plantilla '{$tpl}' (es_CO) sigue «En revisión» en Meta. Te enviamos el mensaje de respaldo con el link de confirmación usando la plantilla preaprobada (ID: {$fallbackRes['wamid']}).",
                    ];
                }
            }
        } else {
            $msg = trim($validated['message'] ?? '') ?: (
                "Prueba de conexión exitosa desde Cita Clave.\n"
                . 'Salón: ' . ($tenant?->name ?? 'Cita Clave') . "\n"
                . 'Proveedor: ' . strtoupper($driver) . "\n"
                . 'Fecha y hora: ' . now()->format('d/m/Y H:i:s')
            );

            $result = $service->sendTextMessageWithDetails(
                phone: $validated['phone'],
                message: $msg,
                tenant: $tenant
            );
        }

        $isSuccess = (bool) ($result['success'] ?? false);
        $rawMsg = $result['message'] ?? 'Sin respuesta del servicio';
        $hasPinError = str_contains($rawMsg, '133010');
        $status = $this->checkConnectionInternal($tenant, $driver);

        return response()->json([
            'success' => $isSuccess,
            'message' => $rawMsg,
            'error_code' => $result['error_code'] ?? null,
            'has_pin_error' => $hasPinError,
            'account_status' => $status,
        ], $isSuccess ? 200 : 422);
    }

    /**
     * Consulta los registros recientes de eventos y errores de WhatsApp en el sistema.
     */
    public function getLogs(Request $request): JsonResponse
    {
        $logPath = storage_path('logs/laravel.log');
        if (! file_exists($logPath)) {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'No hay registros de log generados aún.',
            ]);
        }

        $lines = @file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $lines = array_slice($lines, -1500);
        $matched = [];

        foreach ($lines as $line) {
            if (stripos($line, 'whatsapp') !== false || stripos($line, 'meta') !== false) {
                $matched[] = $line;
            }
        }

        $recent = array_slice($matched, -100);

        return response()->json([
            'success' => true,
            'total' => count($recent),
            'data' => array_reverse($recent),
        ]);
    }
}
