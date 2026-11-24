<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\WorkingSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkingScheduleAdminController extends Controller
{
    /**
     * Listado de las jornadas semanales de atención (Lunes a Domingo).
     */
    public function index(): JsonResponse
    {
        $schedules = WorkingSchedule::query()->orderBy('day_of_week', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => $schedules->map(function ($ws) {
                return [
                    'id' => $ws->id,
                    'day_of_week' => (int) $ws->day_of_week,
                    'day_name' => $ws->day_name,
                    'is_working_day' => (bool) $ws->is_working_day,
                    'open_time' => substr($ws->open_time, 0, 5),
                    'close_time' => substr($ws->close_time, 0, 5),
                    'slot_interval_minutes' => (int) $ws->slot_interval_minutes,
                ];
            }),
        ]);
    }

    /**
     * Actualizar una jornada específica.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $schedule = WorkingSchedule::query()->findOrFail($id);

        $validated = $request->validate([
            'is_working_day' => 'required|boolean',
            'open_time' => 'required|string',
            'close_time' => 'required|string',
            'slot_interval_minutes' => 'nullable|integer|min:5|max:120',
        ]);

        $schedule->update([
            'is_working_day' => $validated['is_working_day'],
            'open_time' => $validated['open_time'],
            'close_time' => $validated['close_time'],
            'slot_interval_minutes' => $validated['slot_interval_minutes'] ?? 15,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Horario de {$schedule->day_name} actualizado exitosamente.",
            'data' => $schedule,
        ]);
    }

    /**
     * Actualizar todas las jornadas de la semana en lote.
     */
    public function batchUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'schedules' => 'required|array|min:1',
            'schedules.*.id' => 'required|integer|exists:working_schedules,id',
            'schedules.*.is_working_day' => 'required|boolean',
            'schedules.*.open_time' => 'required|string',
            'schedules.*.close_time' => 'required|string',
            'schedules.*.slot_interval_minutes' => 'nullable|integer',
        ]);

        foreach ($validated['schedules'] as $item) {
            WorkingSchedule::query()->where('id', $item['id'])->update([
                'is_working_day' => $item['is_working_day'],
                'open_time' => $item['open_time'],
                'close_time' => $item['close_time'],
                'slot_interval_minutes' => $item['slot_interval_minutes'] ?? 15,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Jornadas de atención semanales actualizadas correctamente.',
        ]);
    }
}
