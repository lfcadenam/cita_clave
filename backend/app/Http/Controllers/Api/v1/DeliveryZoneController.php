<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\DeliveryZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class DeliveryZoneController extends Controller
{
    public function index(): JsonResponse
    {
        // Caché de 1 hora para zonas y tarifas de Bogotá
        $zones = Cache::remember('api_delivery_zones_list', 3600, function () {
            return DeliveryZone::where('is_active', true)
                ->orderBy('delivery_fee', 'asc')
                ->get();
        });

        return response()->json([
            'success' => true,
            'data' => $zones,
        ]);
    }
}
