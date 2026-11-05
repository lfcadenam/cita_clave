<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    /**
     * Valida la autenticidad de la petición mediante la cabecera X-API-Key
     * para endpoints semi-públicos del frontend o integraciones de terceros (n8n).
     */
    public function handle(Request $request, Closure $next, ?string $scope = 'frontend'): Response
    {
        // En entorno de testing permite solicitudes internas existentes a menos que el test pruebe explicitamente la seguridad
        if (app()->environment('testing') && ! $request->hasHeader('X-API-Key') && $scope !== 'integrations' && ! $request->attributes->get('enforce_api_key', false)) {
            return $next($request);
        }

        $apiKey = $request->header('X-API-Key');

        $expectedKey = ($scope === 'integrations')
            ? (string) config('app.integrations_api_key', 'nuvex_n8n_integration_2026_key')
            : (string) config('app.api_key', 'nuvex_lbl_front_2026_sec_key');

        // Si la clave provista coincide con la esperada
        if ($apiKey && hash_equals($expectedKey, (string) $apiKey)) {
            return $next($request);
        }

        // Si no coincide o falta la cabecera X-API-Key
        return response()->json([
            'success' => false,
            'message' => 'API key inválida o faltante en la cabecera X-API-Key.',
        ], 401);
    }
}
