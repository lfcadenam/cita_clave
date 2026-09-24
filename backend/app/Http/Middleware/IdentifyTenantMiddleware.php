<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenantMiddleware
{
    /**
     * Resuelve e identifica dinámicamente el Tenant (Salón) activo a partir del dominio HTTP,
     * subdominio, cabecera personalizada o el salón principal activo de la plataforma.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->has('active_tenant_id')) {
            $host = $request->getHost();
            $cleanHost = preg_replace('/^www\./i', '', strtolower($host));

            // 1. Búsqueda por coincidencia exacta de dominio asignado
            $tenant = Tenant::where('is_active', true)
                ->where(function ($query) use ($host, $cleanHost) {
                    $query->where('domain', $host)
                        ->orWhere('domain', $cleanHost)
                        ->orWhere('domain', 'http://' . $cleanHost)
                        ->orWhere('domain', 'https://' . $cleanHost);
                })
                ->first();

            // 2. Búsqueda por subdominio o slug en cabecera
            if (! $tenant && $request->hasHeader('X-Tenant-Slug')) {
                $tenant = Tenant::where('is_active', true)
                    ->where('slug', $request->header('X-Tenant-Slug'))
                    ->first();
            }

            // 3. Fallback inteligente: Salón principal por defecto si no hay coincidencia
            if (! $tenant) {
                $tenant = Tenant::where('is_active', true)->first();
            }

            // 4. Inyección en el contenedor de dependencias de Laravel
            if ($tenant) {
                app()->instance('active_tenant', $tenant);
                app()->instance('active_tenant_id', $tenant->id);

                if (function_exists('view')) {
                    view()->share('activeTenant', $tenant);
                }
            }
        }

        return $next($request);
    }
}
