<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\BroadcastCampaign;
use App\Models\Tenant;
use App\Services\BroadcastCampaignService;
use App\Services\WhatsAppNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WhatsAppBroadcastAdminController extends Controller
{
    public function __construct(
        protected BroadcastCampaignService $broadcastService,
        protected WhatsAppNotificationService $whatsAppService
    ) {}

    protected function resolveTenant(Request $request): ?Tenant
    {
        return $request->user()?->tenant ?: Tenant::first();
    }

    /**
     * Retorna el conteo y desglose de audiencia según el segmento seleccionado.
     */
    public function audience(Request $request): JsonResponse
    {
        $tenant = $this->resolveTenant($request);
        if (!$tenant) {
            return response()->json(['error' => 'Tenant no identificado'], 404);
        }

        $segment = (string) $request->query('segment', 'all');
        $preview = $this->broadcastService->previewAudience($tenant, $segment, [
            'customer_ids' => $request->query('customer_ids', []),
        ]);

        // Conteos rápidos de todos los segmentos principales
        $counts = [
            'all' => $this->broadcastService->getAudienceQuery($tenant, 'all')->count(),
            'recurring' => $this->broadcastService->getAudienceQuery($tenant, 'recurring')->count(),
            'inactive_30' => $this->broadcastService->getAudienceQuery($tenant, 'inactive_30')->count(),
            'new' => $this->broadcastService->getAudienceQuery($tenant, 'new')->count(),
        ];

        return response()->json([
            'success' => true,
            'current_segment' => $segment,
            'counts' => $counts,
            'preview' => $preview,
        ]);
    }

    /**
     * Lista el historial de campañas de difusión del tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $tenant = $this->resolveTenant($request);
        if (!$tenant) {
            return response()->json(['error' => 'Tenant no identificado'], 404);
        }

        $campaigns = BroadcastCampaign::query()
            ->where('tenant_id', $tenant->id)
            ->latest('id')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'campaigns' => $campaigns,
        ]);
    }

    /**
     * Envía una prueba inmediata de la difusión al teléfono del usuario/administrador.
     */
    public function sendTest(Request $request): JsonResponse
    {
        $tenant = $this->resolveTenant($request);
        if (!$tenant) {
            return response()->json(['error' => 'Tenant no identificado'], 404);
        }

        $validated = $request->validate([
            'phone' => 'required|string|min:7|max:20',
            'template_key' => 'nullable|string',
            'message_body' => 'nullable|string',
            'promo_text' => 'nullable|string|max:300',
            'target_url' => 'nullable|url',
        ]);

        $res = $this->broadcastService->sendTestMessage($tenant, $validated, $validated['phone']);

        return response()->json([
            'success' => (bool) ($res['success'] ?? false),
            'message' => $res['message'] ?? ($res['success'] ? 'Mensaje de prueba enviado con éxito.' : 'No se pudo enviar el mensaje de prueba.'),
            'meta_details' => $res,
        ]);
    }

    /**
     * Crea y ejecuta de inmediato una campaña de difusión.
     */
    public function store(Request $request): JsonResponse
    {
        $tenant = $this->resolveTenant($request);
        if (!$tenant) {
            return response()->json(['error' => 'Tenant no identificado'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'segment' => 'required|string|in:all,recurring,inactive_30,new,custom',
            'template_key' => 'nullable|string',
            'message_body' => 'required|string|min:5',
            'promo_text' => 'nullable|string|max:300',
            'target_url' => 'nullable|url',
            'customer_ids' => 'nullable|array',
            'customer_ids.*' => 'integer',
        ]);

        $campaign = $this->broadcastService->createCampaign($tenant, $validated, $request->user());

        if ($campaign->total_recipients === 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontraron clientas activas con teléfono válido para el segmento seleccionado.',
                'campaign' => $campaign,
            ], 422);
        }

        // Ejecutar despacho
        $execResult = $this->broadcastService->executeCampaign($campaign);

        return response()->json([
            'success' => true,
            'message' => "Campaña '{$campaign->name}' completada: {$execResult['sent_count']} enviados, {$execResult['failed_count']} fallidos.",
            'campaign' => $campaign->fresh(['recipients']),
            'summary' => $execResult,
        ]);
    }

    /**
     * Detalle de una campaña con sus destinatarios.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $tenant = $this->resolveTenant($request);
        if (!$tenant) {
            return response()->json(['error' => 'Tenant no identificado'], 404);
        }

        $campaign = BroadcastCampaign::query()
            ->where('tenant_id', $tenant->id)
            ->with(['recipients' => function ($q) {
                $q->latest('id')->limit(100);
            }])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'campaign' => $campaign,
        ]);
    }
}
