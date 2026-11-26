<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\BroadcastCampaign;
use App\Models\BroadcastRecipient;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BroadcastCampaignService
{
    public function __construct(
        protected WhatsAppNotificationService $whatsAppService
    ) {}

    /**
     * Resuelve y retorna la audiencia segmentada de clientas de un tenant.
     */
    public function getAudienceQuery(Tenant $tenant, string $segment, array $filters = []): Builder
    {
        $query = Customer::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->whereNotNull('phone')
            ->where('phone', '!=', '');

        return match ($segment) {
            'recurring' => $query->where(function ($q) {
                $q->where('is_legacy', true)
                  ->orWhereHas('appointments', function ($sq) {
                      $sq->whereIn('status', [AppointmentStatus::COMPLETED->value, AppointmentStatus::CONFIRMED->value]);
                  }, '>=', 2);
            }),

            'new' => $query->where('is_legacy', false)
                ->whereHas('appointments', null, '<=', 1),

            'inactive_30' => $query->where(function ($q) {
                // Clientas sin citas en los últimos 30 días
                $q->whereDoesntHave('appointments', function ($sq) {
                    $sq->where('appointment_date', '>=', now()->subDays(30)->toDateString());
                });
            }),

            'custom' => !empty($filters['customer_ids'])
                ? $query->whereIn('id', (array) $filters['customer_ids'])
                : $query,

            default => $query, // 'all'
        };
    }

    /**
     * Retorna el conteo y una muestra de clientas de un segmento.
     */
    public function previewAudience(Tenant $tenant, string $segment, array $filters = []): array
    {
        $query = $this->getAudienceQuery($tenant, $segment, $filters);
        $totalCount = $query->count();

        $sample = $query->select(['id', 'name', 'nickname', 'phone', 'is_legacy', 'registered_at'])
            ->withCount(['appointments as total_appointments' => function ($q) {
                $q->whereIn('status', [AppointmentStatus::COMPLETED->value, AppointmentStatus::CONFIRMED->value]);
            }])
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(function (Customer $customer) {
                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'nickname' => $customer->nickname,
                    'display_name' => $customer->getNotificationName(),
                    'phone' => $customer->phone,
                    'is_legacy' => (bool) $customer->is_legacy,
                    'total_appointments' => (int) $customer->total_appointments,
                ];
            });

        return [
            'segment' => $segment,
            'total_count' => $totalCount,
            'sample' => $sample,
        ];
    }

    /**
     * Crea y registra una nueva campaña de difusión con sus destinatarios iniciales.
     */
    public function createCampaign(Tenant $tenant, array $data, ?User $user = null): BroadcastCampaign
    {
        $segment = $data['segment'] ?? 'all';
        $templateKey = !empty($data['template_key']) ? trim($data['template_key']) : null;
        $messageBody = trim($data['message_body'] ?? '');
        $campaignName = trim($data['name'] ?? 'Difusión ' . now()->locale('es')->isoFormat('D [de] MMMM'));

        $campaign = BroadcastCampaign::create([
            'tenant_id' => $tenant->id,
            'name' => $campaignName,
            'channel' => 'whatsapp',
            'template_key' => $templateKey,
            'segment' => $segment,
            'message_body' => $messageBody,
            'status' => 'draft',
            'metadata' => [
                'promo_text' => $data['promo_text'] ?? null,
                'target_url' => $data['target_url'] ?? url('/'),
                'customer_ids' => $data['customer_ids'] ?? [],
            ],
            'created_by_user_id' => $user?->id,
        ]);

        // Poblar destinatarios
        $audience = $this->getAudienceQuery($tenant, $segment, ['customer_ids' => $data['customer_ids'] ?? []])
            ->select(['id', 'name', 'nickname', 'phone'])
            ->get();

        $recipientsData = [];
        $now = now();

        foreach ($audience as $cust) {
            $recipientsData[] = [
                'broadcast_campaign_id' => $campaign->id,
                'customer_id' => $cust->id,
                'phone' => (string) $cust->phone,
                'client_name' => (string) $cust->name,
                'client_nickname' => $cust->nickname,
                'status' => 'pending',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($recipientsData)) {
            // Inserción en bloques de 200
            foreach (array_chunk($recipientsData, 200) as $chunk) {
                BroadcastRecipient::insert($chunk);
            }
        }

        $campaign->update([
            'total_recipients' => count($recipientsData),
        ]);

        return $campaign->fresh(['recipients']);
    }

    /**
     * Envía un mensaje de prueba a un número de teléfono específico (ej. el admin).
     */
    public function sendTestMessage(Tenant $tenant, array $data, string $testPhone): array
    {
        $sanitizedPhone = $this->whatsAppService->sanitizePhoneNumber($testPhone);
        $templateKey = $data['template_key'] ?? null;
        $promoText = $data['promo_text'] ?? '15% de descuento en todos los servicios de cejas y pestañas este fin de semana';
        $targetUrl = $data['target_url'] ?? url('/');
        $salonName = $tenant->name ?: 'La Belle Nails';
        $testNickname = 'Cami (Prueba)';

        // Si tiene plantilla de Meta
        if (!empty($templateKey) && $templateKey !== 'custom') {
            $effectiveDef = $this->whatsAppService->getEffectiveTemplateDefinition($templateKey, $tenant);
            if ($effectiveDef) {
                $params = match ($templateKey) {
                    'promocion_salon' => [
                        $testNickname,
                        $salonName,
                        $promoText,
                        $targetUrl,
                    ],
                    'reactivacion_clientas' => [
                        $testNickname,
                        $salonName,
                        $targetUrl,
                    ],
                    default => [
                        $testNickname,
                        $salonName,
                        $promoText,
                        $targetUrl,
                    ]
                };

                return $this->whatsAppService->sendTemplateViaMetaWithDetails(
                    phone: $sanitizedPhone,
                    templateName: $effectiveDef['name'] ?? $templateKey,
                    bodyParameters: $params,
                    languageCode: $effectiveDef['language'] ?? 'es_CO',
                    tenant: $tenant
                );
            }
        }

        // Mensaje de texto personalizado directo
        $body = $data['message_body'] ?? '';
        $body = str_replace(['{{1}}', '{{apodo}}'], $testNickname, $body);
        $body = str_replace(['{{salon}}'], $salonName, $body);
        $body = str_replace(['{{link_portal}}'], $targetUrl, $body);
        $body = str_replace(['{{mensaje_promo}}'], $promoText, $body);

        $sent = $this->whatsAppService->sendViaMetaWithDetails($sanitizedPhone, $body, $tenant);
        if (!$sent['success']) {
            $this->whatsAppService->sendViaLog($sanitizedPhone, $body);
        }

        return $sent;
    }

    /**
     * Ejecuta el despacho real de la campaña a todos sus destinatarios.
     */
    public function executeCampaign(BroadcastCampaign $campaign): array
    {
        $campaign->loadMissing(['recipients', 'tenant']);
        $tenant = $campaign->tenant;
        $salonName = $tenant?->name ?: 'La Belle Nails';
        $targetUrl = $campaign->metadata['target_url'] ?? url('/');
        $promoText = $campaign->metadata['promo_text'] ?? 'Promoción especial del mes';

        $campaign->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        $sentCount = 0;
        $failedCount = 0;
        $recipients = $campaign->recipients()->where('status', 'pending')->get();

        foreach ($recipients as $recipient) {
            $phone = $this->whatsAppService->sanitizePhoneNumber($recipient->phone);
            if (empty($phone)) {
                $recipient->update([
                    'status' => 'failed',
                    'error_message' => 'Teléfono inválido o vacío.',
                    'sent_at' => now(),
                ]);
                $failedCount++;
                continue;
            }

            // Apodo o primer nombre de confianza
            $nickname = !empty($recipient->client_nickname)
                ? trim($recipient->client_nickname)
                : explode(' ', trim((string) ($recipient->client_name ?: 'Clienta')))[0];

            $success = false;
            $errorMsg = null;

            try {
                if (!empty($campaign->template_key) && $campaign->template_key !== 'custom') {
                    $effectiveDef = $this->whatsAppService->getEffectiveTemplateDefinition($campaign->template_key, $tenant);
                    $params = match ($campaign->template_key) {
                        'promocion_salon' => [
                            $nickname,
                            $salonName,
                            $promoText,
                            $targetUrl,
                        ],
                        'reactivacion_clientas' => [
                            $nickname,
                            $salonName,
                            $targetUrl,
                        ],
                        default => [
                            $nickname,
                            $salonName,
                            $promoText,
                            $targetUrl,
                        ]
                    };

                    $res = $this->whatsAppService->sendTemplateViaMetaWithDetails(
                        phone: $phone,
                        templateName: $effectiveDef['name'] ?? $campaign->template_key,
                        bodyParameters: $params,
                        languageCode: $effectiveDef['language'] ?? 'es_CO',
                        tenant: $tenant
                    );

                    $success = (bool) ($res['success'] ?? false);
                    $errorMsg = $success ? null : ($res['message'] ?? 'Fallo de entrega Meta');
                } else {
                    // Texto libre personalizado
                    $body = $campaign->message_body;
                    $body = str_replace(['{{1}}', '{{apodo}}'], $nickname, $body);
                    $body = str_replace(['{{salon}}'], $salonName, $body);
                    $body = str_replace(['{{link_portal}}'], $targetUrl, $body);
                    $body = str_replace(['{{mensaje_promo}}'], $promoText, $body);

                    $res = $this->whatsAppService->sendViaMetaWithDetails($phone, $body, $tenant);
                    $success = (bool) ($res['success'] ?? false);
                    if (!$success) {
                        $this->whatsAppService->sendViaLog($phone, $body);
                        // En entorno de log se considera enviado con éxito para pruebas locales
                        if (config('whatsapp.driver') === 'log') {
                            $success = true;
                        } else {
                            $errorMsg = $res['message'] ?? 'Fallo de entrega';
                        }
                    }
                }
            } catch (\Throwable $e) {
                $success = false;
                $errorMsg = $e->getMessage();
            }

            if ($success) {
                $recipient->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'error_message' => null,
                ]);
                $sentCount++;
            } else {
                $recipient->update([
                    'status' => 'failed',
                    'sent_at' => now(),
                    'error_message' => mb_substr($errorMsg ?: 'Error desconocido', 0, 500),
                ]);
                $failedCount++;
            }
        }

        $campaign->update([
            'status' => 'completed',
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
            'completed_at' => now(),
        ]);

        return [
            'success' => true,
            'campaign_id' => $campaign->id,
            'total_recipients' => $campaign->total_recipients,
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
        ];
    }
}
