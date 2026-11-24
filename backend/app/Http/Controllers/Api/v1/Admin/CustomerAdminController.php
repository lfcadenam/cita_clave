<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
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
        $customer = Customer::query()
            ->with(['appointments' => function ($q) {
                $q->with('service:id,name,duration_minutes,base_price')
                  ->orderBy('appointment_date', 'desc')
                  ->orderBy('start_time', 'desc')
                  ->limit(30);
            }])
            ->findOrFail($id);

        $data = $this->formatCustomer($customer);
        $data['notes'] = $customer->notes;
        $data['registered_at'] = $customer->registered_at?->toIso8601String();
        $data['appointments'] = $customer->appointments->map(function ($apt) {
            return [
                'id' => $apt->id,
                'appointment_number' => $apt->appointment_number,
                'date' => $apt->appointment_date?->toDateString(),
                'date_formatted' => $apt->appointment_date?->translatedFormat('d M, Y'),
                'time_range' => substr($apt->start_time, 0, 5) . ' – ' . substr($apt->end_time, 0, 5),
                'service_name' => $apt->service?->name,
                'status' => $apt->status?->value,
                'status_label' => $apt->status?->label(),
                'status_color' => $apt->status?->color(),
                'total_amount' => (float) $apt->total_amount,
                'deposit_paid' => (float) $apt->deposit_paid,
                'balance_due' => (float) $apt->balance_due,
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
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'notes' => 'nullable|string|max:1000',
            'is_legacy' => 'nullable|boolean',
        ]);

        $cleanPhone = preg_replace('/[^0-9]/', '', (string) $validated['phone']);

        $customer = Customer::create([
            'name' => trim($validated['name']),
            'phone' => $cleanPhone,
            'email' => ! empty($validated['email']) ? trim($validated['email']) : null,
            'notes' => $validated['notes'] ?? null,
            'is_legacy' => $validated['is_legacy'] ?? false,
            'registered_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ficha de clienta creada exitosamente.',
            'data' => $this->formatCustomer($customer),
        ], 201);
    }

    /**
     * Actualizar datos de un cliente existente.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $customer = Customer::query()->findOrFail($id);

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'notes' => 'nullable|string|max:1000',
            'is_legacy' => 'nullable|boolean',
        ]);

        if (isset($validated['name'])) {
            $customer->name = trim($validated['name']);
        }
        if (isset($validated['phone'])) {
            $customer->phone = preg_replace('/[^0-9]/', '', (string) $validated['phone']);
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

        $customer->save();

        return response()->json([
            'success' => true,
            'message' => 'Ficha de clienta actualizada correctamente.',
            'data' => $this->formatCustomer($customer),
        ]);
    }

    protected function formatCustomer(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'display_phone' => $customer->getDisplayPhone(),
            'clean_phone' => $customer->getCleanPhone(),
            'email' => $customer->email,
            'is_legacy' => (bool) $customer->is_legacy,
            'loyalty_badge' => $customer->getLoyaltyBadge(),
            'completed_appointments_count' => $customer->getCompletedAppointmentsCount(),
            'total_spent' => (float) $customer->total_spent,
            'whatsapp_url' => $customer->getWhatsAppUrl(),
        ];
    }
}
