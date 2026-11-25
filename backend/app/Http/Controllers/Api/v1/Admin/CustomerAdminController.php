<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerAdminController extends Controller
{
    /**
     * Búsqueda y listado paginado de clientes.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:100',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $query = Customer::query();

        if (! empty($validated['q'])) {
            $term = '%' . trim($validated['q']) . '%';
            $cleanTerm = preg_replace('/[^0-9]/', '', (string) $validated['q']);

            $query->where(function ($q) use ($term, $cleanTerm) {
                $q->where('name', 'LIKE', $term)
                  ->orWhere('nickname', 'LIKE', $term)
                  ->orWhere('email', 'LIKE', $term);

                if (! empty($cleanTerm)) {
                    $q->orWhere('phone', 'LIKE', '%' . $cleanTerm . '%');
                }
            });
        }

        $query->orderBy('name', 'asc');

        $perPage = (int) ($validated['per_page'] ?? 10);
        $paginated = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => collect($paginated->items())->map(fn ($customer) => $this->formatCustomer($customer)),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    /**
     * Detalle completo del perfil de una clienta y su historial de citas.
     */
    public function show(int $id): JsonResponse
    {
        $customer = Customer::query()->findOrFail($id);

        $appointments = Appointment::query()
            ->where(function ($q) use ($customer) {
                $q->where('customer_id', $customer->id);
                if (! empty($customer->phone)) {
                    $q->orWhere('client_phone', $customer->phone);
                }
            })
            ->with('service:id,name,duration_minutes,base_price')
            ->orderBy('appointment_date', 'desc')
            ->orderBy('start_time', 'desc')
            ->limit(50)
            ->get();

        $data = $this->formatCustomer($customer);
        $data['notes'] = $customer->notes;
        $data['registered_at'] = $customer->registered_at?->toIso8601String();
        $data['appointments'] = $appointments->map(function ($apt) {
            return [
                'id' => $apt->id,
                'appointment_number' => $apt->appointment_number,
                'date' => $apt->appointment_date?->toDateString(),
                'date_formatted' => $apt->appointment_date?->translatedFormat('d M, Y'),
                'time_range' => substr($apt->start_time, 0, 5) . ' – ' . substr($apt->end_time, 0, 5),
                'start_time' => substr($apt->start_time, 0, 5),
                'end_time' => substr($apt->end_time, 0, 5),
                'service_name' => $apt->service?->name ?? 'Servicio Personalizado',
                'status' => $apt->status instanceof \App\Enums\AppointmentStatus ? $apt->status->value : (string) $apt->status,
                'status_label' => $apt->status instanceof \App\Enums\AppointmentStatus ? $apt->status->label() : ($apt->status ?? 'Desconocido'),
                'status_color' => $apt->status instanceof \App\Enums\AppointmentStatus ? $apt->status->color() : 'slate',
                'total_amount' => (float) $apt->total_amount,
                'deposit_paid' => (float) $apt->deposit_paid,
                'balance_due' => (float) $apt->balance_due,
                'admin_notes' => $apt->admin_notes,
                'client_notes' => $apt->client_notes,
                'cancellation_reason' => $apt->cancellation_reason,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Crear un nuevo cliente en el CRM.
     */
    /**
     * Crear un nuevo cliente en el CRM sin duplicar registros.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'nickname' => 'nullable|string|max:100',
            'phone' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'notes' => 'nullable|string|max:1000',
            'is_legacy' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $cleanPhone = preg_replace('/[^0-9]/', '', (string) $validated['phone']);

        // Comprobar si ya existe un cliente con este teléfono en el tenant
        $searchPhones = [$cleanPhone];
        if (strlen($cleanPhone) === 10 && str_starts_with($cleanPhone, '3')) {
            $searchPhones[] = '57' . $cleanPhone;
        } elseif (strlen($cleanPhone) === 12 && str_starts_with($cleanPhone, '57')) {
            $searchPhones[] = substr($cleanPhone, 2);
        }

        $existing = Customer::whereIn('phone', $searchPhones)->first();
        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => "Ya existe una clienta registrada con este número telefónico: {$existing->name}.",
                'data' => $this->formatCustomer($existing),
            ], 422);
        }

        $customer = Customer::create([
            'name' => trim($validated['name']),
            'nickname' => ! empty($validated['nickname']) ? trim($validated['nickname']) : null,
            'phone' => $cleanPhone,
            'email' => ! empty($validated['email']) ? trim($validated['email']) : null,
            'notes' => $validated['notes'] ?? null,
            'is_legacy' => $validated['is_legacy'] ?? false,
            'is_active' => $validated['is_active'] ?? true,
            'registered_at' => now(),
        ]);

        $customer->linkMatchingAppointments();

        return response()->json([
            'success' => true,
            'message' => 'Ficha de clienta creada exitosamente.',
            'data' => $this->formatCustomer($customer),
        ], 201);
    }

    /**
     * Actualizar datos de un cliente existente sin duplicar registros ni alterar fidelidad.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $customer = Customer::query()->findOrFail($id);

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'nickname' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'notes' => 'nullable|string|max:1000',
            'is_legacy' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        if (isset($validated['name']) && ! empty(trim($validated['name']))) {
            $customer->name = trim($validated['name']);
            // Sincronizar nombre en citas existentes de esta clienta
            Appointment::where('customer_id', $customer->id)
                ->update(['client_name' => $customer->name]);
        }

        if (array_key_exists('nickname', $validated)) {
            $customer->nickname = ! empty($validated['nickname']) ? trim($validated['nickname']) : null;
        }

        if (isset($validated['phone'])) {
            $cleanPhone = preg_replace('/[^0-9]/', '', (string) $validated['phone']);
            if (! empty($cleanPhone) && $cleanPhone !== $customer->phone) {
                // Verificar que no pertenezca a otra clienta
                $searchPhones = [$cleanPhone];
                if (strlen($cleanPhone) === 10 && str_starts_with($cleanPhone, '3')) {
                    $searchPhones[] = '57' . $cleanPhone;
                } elseif (strlen($cleanPhone) === 12 && str_starts_with($cleanPhone, '57')) {
                    $searchPhones[] = substr($cleanPhone, 2);
                }

                $conflict = Customer::where('id', '!=', $customer->id)
                    ->whereIn('phone', $searchPhones)
                    ->first();

                if ($conflict) {
                    return response()->json([
                        'success' => false,
                        'message' => "El número de teléfono ya pertenece a otra clienta registrada ({$conflict->name}).",
                    ], 422);
                }

                $customer->phone = $cleanPhone;

                // Sincronizar teléfono en citas existentes de esta clienta
                Appointment::where('customer_id', $customer->id)
                    ->update(['client_phone' => $cleanPhone]);
            }
        }

        if (array_key_exists('email', $validated)) {
            $customer->email = ! empty($validated['email']) ? trim($validated['email']) : null;
        }

        if (array_key_exists('notes', $validated)) {
            $customer->notes = $validated['notes'];
        }

        if (isset($validated['is_legacy'])) {
            $customer->is_legacy = (bool) $validated['is_legacy'];
        }

        if (isset($validated['is_active'])) {
            $customer->is_active = (bool) $validated['is_active'];
        }

        $customer->save();
        $customer->linkMatchingAppointments();

        return response()->json([
            'success' => true,
            'message' => 'Ficha de clienta actualizada correctamente.',
            'data' => $this->formatCustomer($customer),
        ]);
    }

    /**
     * Eliminar o desactivar una clienta según tenga citas en su historial.
     */
    public function destroy(int $id): JsonResponse
    {
        $customer = Customer::query()->findOrFail($id);

        $cleanPhone = $customer->getCleanPhone();
        $searchPhones = [$customer->phone, $cleanPhone];
        if (strlen($cleanPhone) === 10 && str_starts_with($cleanPhone, '3')) {
            $searchPhones[] = '57' . $cleanPhone;
        } elseif (strlen($cleanPhone) === 12 && str_starts_with($cleanPhone, '57')) {
            $searchPhones[] = substr($cleanPhone, 2);
        }

        $totalAppointmentsCount = Appointment::query()
            ->where(function ($q) use ($customer, $searchPhones) {
                $q->where('customer_id', $customer->id)
                  ->orWhereIn('client_phone', $searchPhones);
            })
            ->count();

        // Si tiene citas asociadas, NO eliminar para proteger el historial y la contabilidad. Desactivar.
        if ($totalAppointmentsCount > 0) {
            $customer->is_active = false;
            $customer->save();

            return response()->json([
                'success' => true,
                'action' => 'deactivated',
                'message' => "La clienta cuenta con {$totalAppointmentsCount} cita(s) en su historial. Se ha desactivado del sistema para proteger los registros de agenda y contabilidad.",
                'data' => $this->formatCustomer($customer),
            ]);
        }

        // Si no tiene ninguna cita, eliminar permanentemente
        $customerName = $customer->name;
        $customer->delete();

        return response()->json([
            'success' => true,
            'action' => 'deleted',
            'message' => "La clienta '{$customerName}' ha sido eliminada permanentemente del sistema.",
        ]);
    }

    /**
     * Alternar estado Activa / Inactiva de una clienta.
     */
    public function toggleActive(int $id): JsonResponse
    {
        $customer = Customer::query()->findOrFail($id);
        $customer->is_active = ! ($customer->is_active ?? true);
        $customer->save();

        $statusText = $customer->is_active ? 'reactivada' : 'desactivada';

        return response()->json([
            'success' => true,
            'message' => "La clienta ha sido {$statusText} correctamente.",
            'data' => $this->formatCustomer($customer),
        ]);
    }

    protected function formatCustomer(Customer $customer): array
    {
        $cleanPhone = $customer->getCleanPhone();
        $searchPhones = [$customer->phone, $cleanPhone];
        if (strlen($cleanPhone) === 10 && str_starts_with($cleanPhone, '3')) {
            $searchPhones[] = '57' . $cleanPhone;
        } elseif (strlen($cleanPhone) === 12 && str_starts_with($cleanPhone, '57')) {
            $searchPhones[] = substr($cleanPhone, 2);
        }

        $totalAppts = Appointment::query()
            ->where(function ($q) use ($customer, $searchPhones) {
                $q->where('customer_id', $customer->id)
                  ->orWhereIn('client_phone', $searchPhones);
            })
            ->count();

        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'nickname' => $customer->nickname,
            'notification_name' => $customer->getNotificationName(),
            'phone' => $customer->phone,
            'display_phone' => $customer->getDisplayPhone(),
            'clean_phone' => $cleanPhone,
            'email' => $customer->email,
            'is_legacy' => (bool) $customer->is_legacy,
            'is_active' => (bool) ($customer->is_active ?? true),
            'has_appointments' => $totalAppts > 0,
            'appointments_count' => $totalAppts,
            'notes' => $customer->notes,
            'loyalty_badge' => $customer->getLoyaltyBadge(),
            'completed_appointments_count' => $customer->getCompletedAppointmentsCount(),
            'total_spent' => (float) $customer->total_spent,
            'whatsapp_url' => $customer->getWhatsAppUrl(),
            'registered_at' => $customer->registered_at?->toIso8601String(),
        ];
    }
}
