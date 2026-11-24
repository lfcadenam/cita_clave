<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Autenticación de usuarios administrativos (Admin y SuperAdmin).
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'tenant_slug' => 'nullable|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales inválidas. Verifica tu correo y contraseña.',
            ], 401);
        }

        if (! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Tu cuenta se encuentra inactiva. Contacta al soporte de Nuvex.',
            ], 403);
        }

        if ($user->role === UserRole::CLIENT) {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. Este portal es exclusivo para el equipo de administración.',
            ], 403);
        }

        // Determinar el salón / tenant activo
        $tenant = null;
        if ($user->role === UserRole::SUPER_ADMIN && ! empty($validated['tenant_slug'])) {
            $tenant = Tenant::where('is_active', true)->where('slug', $validated['tenant_slug'])->first();
        }

        if (! $tenant) {
            $tenant = $user->tenant ?: Tenant::where('is_active', true)->first();
        }

        // Generar Token Sanctum con habilidades basadas en rol
        $token = $user->createToken('admin-spa', [$user->role->value])->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Autenticación exitosa',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->value,
                    'role_label' => $user->role->label(),
                    'phone' => $user->phone,
                ],
                'tenant' => $tenant ? [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                    'logo_url' => $tenant->logo_url,
                    'domain' => $tenant->domain,
                    'phone' => $tenant->phone,
                    'address' => $tenant->address,
                ] : null,
            ],
        ]);
    }

    /**
     * Retorna la información del usuario autenticado y su contexto de tenant.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenant = app()->has('active_tenant') ? app('active_tenant') : $user->tenant;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->value,
                    'role_label' => $user->role->label(),
                    'phone' => $user->phone,
                ],
                'tenant' => $tenant ? [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                    'logo_url' => $tenant->logo_url,
                    'domain' => $tenant->domain,
                    'phone' => $tenant->phone,
                    'address' => $tenant->address,
                ] : null,
            ],
        ]);
    }

    /**
     * Cierra la sesión revocando el token de acceso actual.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada exitosamente.',
        ]);
    }
}
