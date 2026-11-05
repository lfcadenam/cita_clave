<?php

namespace App\Http\Controllers\Api\v1;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IntegrationController extends Controller
{
    /**
     * Endpoint para n8n / automatizaciones externas: obtiene citas próximas para recordatorios.
     */
    public function upcomingAppointments(Request $request): JsonResponse
    {
        $hours = $request->integer('hours', 24);

        $now = Carbon::now();
        $targetDate = $now->copy()->addHours($hours)->toDateString();

        $appointments = Appointment::whereIn('status', [
                AppointmentStatus::CONFIRMED->value,
                AppointmentStatus::PENDING_VERIFICATION->value,
            ])
            ->whereDate('appointment_date', $targetDate)
            ->whereNull('reminder_24h_sent_at')
            ->with(['service:id,name,duration_minutes', 'tenant:id,name,display_name'])
            ->get(['id', 'appointment_number', 'client_name', 'client_phone', 'client_email', 'service_id', 'tenant_id', 'appointment_date', 'start_time', 'end_time', 'balance_due', 'status']);

        return response()->json([
            'success' => true,
            'count' => $appointments->count(),
            'data' => $appointments,
        ]);
    }

    /**
     * n8n confirma asistencia de una cita tras interacción por WhatsApp o webhook.
     */
    public function confirmAttendance(Request $request, string $appointmentNumber): JsonResponse
    {
        $appointment = Appointment::where('appointment_number', trim($appointmentNumber))->first();

        if (! $appointment) {
            return response()->json([
                'success' => false,
                'message' => 'Cita no encontrada.',
            ], 404);
        }

        $appointment->update([
            'attendance_confirmed_at' => now(),
        ]);

        Log::info("Asistencia confirmada vía integración n8n para cita #{$appointment->appointment_number}");

        return response()->json([
            'success' => true,
            'message' => 'Asistencia confirmada exitosamente.',
            'data' => [
                'appointment_number' => $appointment->appointment_number,
                'attendance_confirmed_at' => $appointment->attendance_confirmed_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * n8n notifica que el recordatorio de 24h fue enviado con éxito a la clienta.
     */
    public function markNotified(Request $request, string $appointmentNumber): JsonResponse
    {
        $appointment = Appointment::where('appointment_number', trim($appointmentNumber))->first();

        if (! $appointment) {
            return response()->json([
                'success' => false,
                'message' => 'Cita no encontrada.',
            ], 404);
        }

        $appointment->update([
            'reminder_24h_sent_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Marca de recordatorio registrada correctamente.',
            'data' => [
                'appointment_number' => $appointment->appointment_number,
                'reminder_24h_sent_at' => $appointment->reminder_24h_sent_at->toIso8601String(),
            ],
        ]);
    }
}
