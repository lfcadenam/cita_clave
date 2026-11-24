<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Waitlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WaitlistAdminController extends Controller
{
    /**
     * Listado de clientas en lista de espera.
     */
    public function index(): JsonResponse
    {
        $items = Waitlist::query()
            ->with('service:id,name,duration_minutes,base_price')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $items->map(function ($w) {
                return [
                    'id' => $w->id,
                    'client_name' => $w->client_name,
                    'client_phone' => $w->client_phone,
                    'client_email' => $w->client_email,
                    'requested_date' => $w->requested_date?->toDateString(),
                    'preferred_time_range' => $w->preferred_time_range,
                    'status' => $w->status,
                    'notes' => $w->notes,
                    'notified_at' => $w->notified_at?->toIso8601String(),
                    'created_at' => $w->created_at?->toIso8601String(),
                    'service' => $w->service ? [
                        'id' => $w->service->id,
                        'name' => $w->service->name,
                        'duration_minutes' => $w->service->duration_minutes,
                    ] : null,
                ];
            }),
        ]);
    }

    /**
     * Marcar como contactada / notificada o cancelar.
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $item = Waitlist::query()->findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|string|in:PENDING,NOTIFIED,BOOKED,CANCELLED',
        ]);

        $item->status = $validated['status'];
        if ($validated['status'] === 'NOTIFIED') {
            $item->notified_at = now();
        }

        $item->save();

        return response()->json([
            'success' => true,
            'message' => 'Estado de lista de espera actualizado.',
            'data' => $item,
        ]);
    }

    /**
     * Eliminar entrada de la lista de espera.
     */
    public function destroy(int $id): JsonResponse
    {
        $item = Waitlist::query()->findOrFail($id);
        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Entrada eliminada de la lista de espera.',
        ]);
    }
}
