<?php

namespace App\Http\Controllers\Api\v1\SuperAdmin;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\SystemSetting;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuperAdminController extends Controller
{
    /**
     * Verificar que el usuario tenga rol SUPER_ADMIN.
     */
    protected function ensureSuperAdmin(Request $request): void
    {
        if ($request->user()->role !== UserRole::SUPER_ADMIN) {
            abort(403, 'Acceso denegado. Se requieren permisos de Super Administrador Nuvex.');
        }
    }

    /**
     * Analíticas globales de toda la plataforma Nuvex.
     */
    public function analytics(Request $request): JsonResponse
    {
        $this->ensureSuperAdmin($request);

        $tenantsCount = Tenant::count();
        $activeTenantsCount = Tenant::where('is_active', true)->count();
        $usersCount = User::count();
        $totalAppointments = Appointment::withoutGlobalScopes()->count();
        $totalRevenue = (float) Appointment::withoutGlobalScopes()
            ->whereNotIn('status', ['CANCELLED'])
            ->sum('total_amount');

        return response()->json([
            'success' => true,
            'data' => [
                'tenants_count' => $tenantsCount,
                'active_tenants_count' => $activeTenantsCount,
                'users_count' => $usersCount,
                'total_appointments' => $totalAppointments,
                'total_revenue' => $totalRevenue,
            ],
        ]);
    }

    /**
     * Listado de todos los salones / tenants registrados.
     */
    public function tenants(Request $request): JsonResponse
    {
        $this->ensureSuperAdmin($request);

        $tenants = Tenant::orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $tenants,
        ]);
    }

    /**
     * Crear un nuevo salón / tenant.
     */
    public function createTenant(Request $request): JsonResponse
    {
        $this->ensureSuperAdmin($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:100|unique:tenants,slug',
            'business_type' => 'nullable|string|max:100',
            'domain' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'city' => 'nullable|string|max:100',
            'plan_name' => 'nullable|string|max:100',
            'subscription_status' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
        ]);

        $tenant = Tenant::create([
            'name' => trim($validated['name']),
            'slug' => ! empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['name']),
            'business_type' => $validated['business_type'] ?? 'ESTUDIO DE BELLEZA',
            'domain' => $validated['domain'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'city' => $validated['city'] ?? 'Bogotá',
            'plan_name' => $validated['plan_name'] ?? 'SaaS Enterprise Nuvex',
            'subscription_status' => $validated['subscription_status'] ?? 'active',
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Salón creado exitosamente.',
            'data' => $tenant,
        ], 201);
    }

    /**
     * Actualizar datos de un salón.
     */
    public function updateTenant(Request $request, int $id): JsonResponse
    {
        $this->ensureSuperAdmin($request);
        $tenant = Tenant::findOrFail($id);

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'business_type' => 'nullable|string|max:100',
            'domain' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'city' => 'nullable|string|max:100',
            'plan_name' => 'nullable|string|max:100',
            'subscription_status' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
        ]);

        $tenant->update(array_filter($validated, fn ($val) => $val !== null));

        return response()->json([
            'success' => true,
            'message' => 'Salón actualizado exitosamente.',
            'data' => $tenant,
        ]);
    }

    /**
     * Listado de usuarios de plataforma.
     */
    public function users(Request $request): JsonResponse
    {
        $this->ensureSuperAdmin($request);

        $users = User::with('tenant:id,name,slug')->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $users->map(function ($u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'role' => $u->role->value,
                    'role_label' => $u->role->label(),
                    'phone' => $u->phone,
                    'is_active' => (bool) $u->is_active,
                    'tenant' => $u->tenant,
                    'created_at' => $u->created_at?->toIso8601String(),
                ];
            }),
        ]);
    }

    /**
     * Crear un usuario administrativo.
     */
    public function createUser(Request $request): JsonResponse
    {
        $this->ensureSuperAdmin($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|string|in:SUPER_ADMIN,ADMIN,CLIENT',
            'tenant_id' => 'nullable|integer|exists:tenants,id',
            'phone' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
        ]);

        $user = User::create([
            'name' => trim($validated['name']),
            'email' => trim($validated['email']),
            'password' => Hash::make($validated['password']),
            'role' => UserRole::from($validated['role']),
            'tenant_id' => $validated['tenant_id'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Usuario creado exitosamente.',
            'data' => $user,
        ], 201);
    }

    /**
     * Actualizar usuario.
     */
    public function updateUser(Request $request, int $id): JsonResponse
    {
        $this->ensureSuperAdmin($request);
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => "nullable|email|unique:users,email,{$id}",
            'password' => 'nullable|string|min:8',
            'role' => 'nullable|string|in:SUPER_ADMIN,ADMIN,CLIENT',
            'tenant_id' => 'nullable|integer|exists:tenants,id',
            'phone' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
        ]);

        if (! empty($validated['name'])) $user->name = trim($validated['name']);
        if (! empty($validated['email'])) $user->email = trim($validated['email']);
        if (! empty($validated['password'])) $user->password = Hash::make($validated['password']);
        if (! empty($validated['role'])) $user->role = UserRole::from($validated['role']);
        if (array_key_exists('tenant_id', $validated)) $user->tenant_id = $validated['tenant_id'];
        if (array_key_exists('phone', $validated)) $user->phone = $validated['phone'];
        if (isset($validated['is_active'])) $user->is_active = (bool) $validated['is_active'];

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Usuario actualizado correctamente.',
            'data' => $user,
        ]);
    }

    /**
     * Eliminar usuario.
     */
    public function deleteUser(Request $request, int $id): JsonResponse
    {
        $this->ensureSuperAdmin($request);
        $user = User::findOrFail($id);

        if ($user->id === $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'No puedes eliminar tu propia cuenta.'], 422);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Usuario eliminado correctamente.',
        ]);
    }

    /**
     * Obtener configuración actual del programador de recordatorios.
     */
    public function getReminderSettings(Request $request): JsonResponse
    {
        $this->ensureSuperAdmin($request);

        $reminderTime = SystemSetting::get('appointment_reminder_time', '10:00');
        $rawEnabled = SystemSetting::get('appointment_reminder_enabled', '1');
        $isEnabled = filter_var($rawEnabled, FILTER_VALIDATE_BOOLEAN);
        $timezone = 'America/Bogota';

        $tomorrowStr = Carbon::tomorrow($timezone)->toDateString();

        $pendingTomorrowCount = Appointment::withoutGlobalScopes()
            ->where('status', AppointmentStatus::CONFIRMED)
            ->whereDate('appointment_date', $tomorrowStr)
            ->whereNull('reminder_24h_sent_at')
            ->count();

        $totalTomorrowCount = Appointment::withoutGlobalScopes()
            ->where('status', AppointmentStatus::CONFIRMED)
            ->whereDate('appointment_date', $tomorrowStr)
            ->count();

        $recentReminders = Appointment::withoutGlobalScopes()
            ->with(['tenant:id,name', 'service:id,name'])
            ->whereNotNull('reminder_24h_sent_at')
            ->latest('reminder_24h_sent_at')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'reminder_time' => $reminderTime,
                'is_enabled' => $isEnabled,
                'timezone' => $timezone,
                'pending_tomorrow_count' => $pendingTomorrowCount,
                'total_tomorrow_count' => $totalTomorrowCount,
                'recent_reminders' => $recentReminders,
            ],
        ]);
    }

    /**
     * Guardar nueva hora y estado de los recordatorios.
     */
    public function updateReminderSettings(Request $request): JsonResponse
    {
        $this->ensureSuperAdmin($request);

        $validated = $request->validate([
            'reminder_time' => ['required', 'regex:/^(?:[01]\d|2[0-3]):[0-5]\d$/'],
            'is_enabled' => ['required', 'boolean'],
        ], [
            'reminder_time.required' => 'Debes ingresar una hora de ejecución.',
            'reminder_time.regex' => 'El formato debe ser HH:mm (por ejemplo 10:00 o 08:30).',
        ]);

        SystemSetting::set('appointment_reminder_time', $validated['reminder_time'], 'Hora diaria de recordatorios', 'scheduler');
        SystemSetting::set('appointment_reminder_enabled', $validated['is_enabled'] ? '1' : '0', 'Estado del cron de recordatorios', 'scheduler');

        return response()->json([
            'success' => true,
            'message' => "Horario de recordatorios actualizado a las {$validated['reminder_time']} (Hora Colombia).",
            'data' => [
                'reminder_time' => $validated['reminder_time'],
                'is_enabled' => (bool) $validated['is_enabled'],
            ],
        ]);
    }

    /**
     * Disparar ejecución manual de recordatorios bajo demanda.
     */
    public function triggerManualReminders(Request $request): JsonResponse
    {
        $this->ensureSuperAdmin($request);

        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'force' => ['nullable', 'boolean'],
        ]);

        $timezone = 'America/Bogota';
        $targetDate = !empty($validated['date']) ? $validated['date'] : Carbon::tomorrow($timezone)->toDateString();
        $force = !empty($validated['force']);

        try {
            $params = [
                '--date' => $targetDate,
            ];

            if ($force) {
                $params['--force'] = true;
            }

            Artisan::call('appointments:send-reminders', $params);
            $output = Artisan::output();

            return response()->json([
                'success' => true,
                'message' => "Ejecución completada para la fecha {$targetDate}.",
                'output' => $output,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al ejecutar recordatorios: ' . $e->getMessage(),
            ], 500);
        }
    }
}
