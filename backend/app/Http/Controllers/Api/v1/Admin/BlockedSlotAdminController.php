<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockedSlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlockedSlotAdminController extends Controller
{
    /**
     * Listado de franjas horarias bloqueadas (Almuerzo, descansos, vacaciones).
     */
    public function index(Request $request): JsonResponse
    {
        $query = BlockedSlot::query()->orderBy('blocked_date', 'asc')->orderBy('start_time', 'asc');

        if ($request->has('upcoming')) {
            $query->where(function ($q) {
                $q->where('is_recurring', true)
                  ->orWhere('blocked_date', '>=', now()->toDateString());
            });
        }

        $slots = $query->get();

        return response()->json([
            'success' => true,
            'data' => $slots->map(function ($slot) {
                return [
                    'id' => $slot->id,
                    'title' => $slot->title,
                    'blocked_date' => $slot->blocked_date?->toDateString(),
                    'start_time' => substr($slot->start_time, 0, 5),
                    'end_time' => substr($slot->end_time, 0, 5),
                    'time_range' => substr($slot->start_time, 0, 5) . ' – ' . substr($slot->end_time, 0, 5),
                    'is_recurring' => (bool) $slot->is_recurring,
                    'reason' => $slot->reason,
                ];
            }),
        ]);
    }

    /**
     * Crear un nuevo bloqueo o pausa.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'blocked_date' => 'nullable|date_format:Y-m-d',
            'start_time' => 'required|string',
            'end_time' => 'required|string',
            'is_recurring' => 'nullable|boolean',
            'reason' => 'nullable|string|max:500',
        ]);

        $slot = BlockedSlot::create([
            'title' => trim($validated['title']),
            'blocked_date' => $validated['blocked_date'] ?? null,
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'is_recurring' => $validated['is_recurring'] ?? false,
            'reason' => $validated['reason'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Franja bloqueada creada exitosamente.',
            'data' => [
                'id' => $slot->id,
                'title' => $slot->title,
                'blocked_date' => $slot->blocked_date?->toDateString(),
                'start_time' => substr($slot->start_time, 0, 5),
                'end_time' => substr($slot->end_time, 0, 5),
                'time_range' => substr($slot->start_time, 0, 5) . ' – ' . substr($slot->end_time, 0, 5),
                'is_recurring' => (bool) $slot->is_recurring,
                'reason' => $slot->reason,
            ],
        ], 201);
    }

    /**
     * Actualizar una franja bloqueada existente.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $slot = BlockedSlot::query()->findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'blocked_date' => 'nullable|date_format:Y-m-d',
            'start_time' => 'required|string',
            'end_time' => 'required|string',
            'is_recurring' => 'nullable|boolean',
            'reason' => 'nullable|string|max:500',
        ]);

        $slot->update([
            'title' => trim($validated['title']),
            'blocked_date' => !empty($validated['is_recurring']) ? null : ($validated['blocked_date'] ?? null),
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'is_recurring' => $validated['is_recurring'] ?? false,
            'reason' => $validated['reason'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Franja bloqueada actualizada exitosamente.',
            'data' => [
                'id' => $slot->id,
                'title' => $slot->title,
                'blocked_date' => $slot->blocked_date?->toDateString(),
                'start_time' => substr($slot->start_time, 0, 5),
                'end_time' => substr($slot->end_time, 0, 5),
                'time_range' => substr($slot->start_time, 0, 5) . ' – ' . substr($slot->end_time, 0, 5),
                'is_recurring' => (bool) $slot->is_recurring,
                'reason' => $slot->reason,
            ],
        ]);
    }

    /**
     * Eliminar un bloqueo existente.
     */
    public function destroy(int $id): JsonResponse
    {
        $slot = BlockedSlot::query()->findOrFail($id);
        $slot->delete();

        return response()->json([
            'success' => true,
            'message' => 'Franja bloqueada eliminada correctamente.',
        ]);
    }
}
