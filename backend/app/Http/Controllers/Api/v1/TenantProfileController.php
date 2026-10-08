<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantProfileController extends Controller
{
    /**
     * Retorna la identidad de marca comercial del Tenant activo.
     */
    public function show(Request $request): JsonResponse
    {
        $tenant = app()->has('active_tenant') 
            ? app('active_tenant') 
            : Tenant::where('is_active', true)->first();

        if (! $tenant) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró un salón activo.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'short_name' => $tenant->short_name,
                'display_name' => $tenant->display_name,
                'business_type' => $tenant->business_type ?: 'ESTUDIO DE BELLEZA',
                'slug' => $tenant->slug,
                'city' => $tenant->city ?: 'Bogotá, Colombia',
                'address' => $tenant->address,
                'phone' => $tenant->phone,
                'whatsapp_number' => $tenant->whatsapp_number ?: $tenant->phone ?: '3103248385',
                'whatsapp_url' => $tenant->whatsapp_url,
                'email' => $tenant->email,
                'specialties' => $tenant->specialties ?: 'Pestañas · Cejas · Faciales · Micropigmentación',
                'schedule_summary' => $tenant->schedule_summary ?: 'Lunes a Sábado 8:00 AM - 6:00 PM | Almuerzo 1:00 PM - 2:00 PM',
                'primary_color' => $tenant->primary_color ?: '#0d9488',
                'logo_path' => $tenant->logo_path ? asset('storage/' . $tenant->logo_path) : null,
                'is_bold_enabled' => $tenant->isBoldConfigured(),
            ],
        ]);
    }
}
