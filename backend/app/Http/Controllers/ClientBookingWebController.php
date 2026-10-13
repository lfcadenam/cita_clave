<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Service;
use App\Services\BookingAvailabilityService;
use App\Services\WhatsAppNotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class ClientBookingWebController extends Controller
{
    public function __construct(
        protected ?BookingAvailabilityService $availabilityService = null,
        protected ?WhatsAppNotificationService $whatsAppService = null
    ) {
        $this->availabilityService = $availabilityService ?? app(BookingAvailabilityService::class);
        $this->whatsAppService = $whatsAppService ?? app(WhatsAppNotificationService::class);
    }
    /**
     * Render the main booking portal (SPA / Multi-step).
     */
    public function index()
    {
        $services = Service::where('is_active', true)->orderBy('sort_order')->get();
        $categories = Service::where('is_active', true)->pluck('category')->unique()->values();
        $nequiConfig = config('payment.nequi');

        $tenant = app()->has('active_tenant') ? app('active_tenant') : \App\Models\Tenant::where('is_active', true)->first();
        $boldConfigured = $tenant ? $tenant->isBoldConfigured() : false;

        return view('portal.booking', compact('services', 'categories', 'nequiConfig', 'boldConfigured'));
    }

    /**
     * Render confirmation voucher page after booking.
     */
    public function confirmation(string $appointmentNumber)
    {
        $appointment = Appointment::with('service')
            ->where('appointment_number', $appointmentNumber)
            ->firstOrFail();

        $nequiConfig = config('payment.nequi');

        return view('portal.confirmation', compact('appointment', 'nequiConfig'));
    }

    /**
     * Render appointment lookup page for clients.
     */
    public function lookup(Request $request)
    {
        $search = $request->input('search');
        $appointments = collect();

        if (! empty($search)) {
            $cleanSearch = trim($search);
            $cleanPhone = preg_replace('/\D/', '', $cleanSearch);

            $query = Appointment::with('service');

            if (strlen($cleanPhone) >= 7) {
                $query->where(function ($q) use ($cleanSearch, $cleanPhone) {
                    $q->where('appointment_number', $cleanSearch)
                      ->orWhere('client_phone', 'LIKE', "%{$cleanPhone}%");
                });
            } else {
                $query->where('appointment_number', $cleanSearch);
            }

            $appointments = $query->orderBy('appointment_date', 'desc')
                ->orderBy('start_time', 'desc')
                ->limit(3)
                ->get();
        }

        $appointment = $appointments->first();

        return view('portal.lookup', compact('appointments', 'appointment', 'search'));
    }

    /**
     * Download iCalendar (.ics) file for Google / Apple Calendar.
     */
    public function downloadCalendar(string $appointmentNumber): Response
    {
        $appointment = Appointment::with('service')
            ->where('appointment_number', $appointmentNumber)
            ->firstOrFail();

        $startDateTime = Carbon::parse($appointment->appointment_date->toDateString() . ' ' . $appointment->start_time);
        $endDateTime = Carbon::parse($appointment->appointment_date->toDateString() . ' ' . $appointment->end_time);

        $dtStart = $startDateTime->format('Ymd\THis');
        $dtEnd = $endDateTime->format('Ymd\THis');
        $summary = "Cita de Belleza: " . $appointment->service->name . " — Paola Aguilera";
        $description = "Cita agendada para {$appointment->client_name}. Servicio: {$appointment->service->name}. Saldo pendiente en local: $" . number_format($appointment->balance_due, 0, ',', '.') . " COP.";
        $location = "Estudio Paola Andrea Aguilera Camacho, Bogotá, Colombia";

        $ics = "BEGIN:VCALENDAR\r\n";
        $ics .= "VERSION:2.0\r\n";
        $ics .= "PRODID:-//Nuvex Tecnologia//Paola Aguilera Beauty Booking//ES\r\n";
        $ics .= "CALSCALE:GREGORIAN\r\n";
        $ics .= "METHOD:PUBLISH\r\n";
        $ics .= "BEGIN:VEVENT\r\n";
        $ics .= "UID:appointment-{$appointment->id}-nuvex\r\n";
        $ics .= "DTSTAMP:" . now()->format('Ymd\THis\Z') . "\r\n";
        $ics .= "DTSTART:{$dtStart}\r\n";
        $ics .= "DTEND:{$dtEnd}\r\n";
        $ics .= "SUMMARY:{$summary}\r\n";
        $ics .= "DESCRIPTION:{$description}\r\n";
        $ics .= "LOCATION:{$location}\r\n";
        $ics .= "STATUS:CONFIRMED\r\n";
        $ics .= "END:VEVENT\r\n";
        $ics .= "END:VCALENDAR\r\n";

        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"cita-paola-{$appointment->appointment_number}.ics\"",
        ]);
    }

    /**
     * Permite a la clienta confirmar manualmente su asistencia a la cita desde el portal web.
     */
    public function confirmAttendance(string $appointmentNumber)
    {
        $appointment = Appointment::where('appointment_number', $appointmentNumber)->firstOrFail();

        $appointment->update([
            'attendance_confirmed_at' => now(),
        ]);

        return redirect()->back()->with('status_message', '¡Muchas gracias! Has confirmado tu asistencia para esta cita. Te esperamos puntualmente en nuestro estudio.');
    }

    /**
     * Permite a la clienta autocancelar su cita bajo las reglas:
     * 1. Observación / motivo de cancelación es obligatorio.
     * 2. Debe faltar más de 24 horas para el inicio de la cita.
     */
    public function cancelAppointment(Request $request, string $appointmentNumber)
    {
        $appointment = Appointment::where('appointment_number', trim($appointmentNumber))->firstOrFail();

        $validated = $request->validate([
            'cancellation_reason' => 'required|string|min:5|max:500',
        ], [
            'cancellation_reason.required' => 'Debes ingresar el motivo de la cancelación.',
            'cancellation_reason.min' => 'El motivo debe tener al menos 5 caracteres.',
            'cancellation_reason.max' => 'El motivo no puede exceder 500 caracteres.',
        ]);

        if (! $appointment->canBeCancelledByClient()) {
            return redirect()->back()->withErrors([
                'cancellation_error' => 'No es posible autocancelar la cita. Por políticas del salón, si faltan menos de 24 horas para el inicio, la cancelación solo puede ser gestionada directamente por la administración.',
            ]);
        }

        $appointment->update([
            'status' => AppointmentStatus::CANCELLED,
            'cancellation_reason' => strip_tags(trim($validated['cancellation_reason'])),
            'cancelled_at' => now(),
        ]);

        return redirect()->back()->with('status_message', 'Tu cita ha sido cancelada exitosamente.');
    }

    /**
     * Muestra la vista interactiva para reagendar la cita.
     */
    public function showRescheduleForm(string $appointmentNumber)
    {
        $appointment = Appointment::with('service')
            ->where('appointment_number', trim($appointmentNumber))
            ->firstOrFail();

        if (! $appointment->canBeRescheduledByClient()) {
            return redirect()->route('portal.confirmation', $appointment->appointment_number)
                ->withErrors([
                    'cancellation_error' => 'No es posible reagendar la cita automáticamente. Por políticas del salón, si faltan menos de 24 horas para el inicio, cualquier cambio debe ser coordinado directamente con Paola vía WhatsApp.',
                ]);
        }

        $minDate = now()->addDay()->toDateString();
        $maxDate = now()->addDays(35)->toDateString();

        return view('portal.reschedule', compact('appointment', 'minDate', 'maxDate'));
    }

    /**
     * Procesa el reagendamiento a una nueva fecha y hora.
     */
    public function rescheduleAppointment(Request $request, string $appointmentNumber)
    {
        $appointment = Appointment::with('service')
            ->where('appointment_number', trim($appointmentNumber))
            ->firstOrFail();

        if (! $appointment->canBeRescheduledByClient()) {
            return redirect()->route('portal.confirmation', $appointment->appointment_number)
                ->withErrors([
                    'cancellation_error' => 'No es posible reagendar la cita automáticamente porque faltan menos de 24 horas para su inicio.',
                ]);
        }

        $validated = $request->validate([
            'new_date' => 'required|date_format:Y-m-d|after_or_equal:today',
            'new_time' => 'required|date_format:H:i',
            'reason' => 'nullable|string|max:500',
        ], [
            'new_date.required' => 'Debes seleccionar la nueva fecha para tu cita.',
            'new_date.after_or_equal' => 'La nueva fecha debe ser igual o posterior a hoy.',
            'new_time.required' => 'Debes seleccionar el nuevo horario disponible.',
        ]);

        $service = $appointment->service;
        $newDate = Carbon::parse($validated['new_date']);
        $newStartTime = $validated['new_time'];
        $newEndTime = Carbon::parse($newDate->toDateString() . ' ' . $newStartTime)
            ->addMinutes($service->duration_minutes)
            ->format('H:i:s');

        // Verificar conflicto excluyendo la cita actual
        $conflict = Appointment::hasConflict(
            $newDate,
            $newStartTime,
            $newEndTime,
            excludeAppointmentId: $appointment->id,
            tenantId: $appointment->tenant_id
        );

        if ($conflict) {
            return redirect()->back()->withErrors([
                'slot_error' => "El horario seleccionado ({$newStartTime}) ya fue ocupado por otra cita. Por favor elige otro horario.",
            ])->withInput();
        }

        $oldDate = $appointment->appointment_date ? $appointment->appointment_date->toDateString() : '';
        $oldDateFormatted = $appointment->appointment_date ? $appointment->appointment_date->locale('es')->isoFormat('dddd D [de] MMMM') : '';
        $oldTime = substr($appointment->start_time, 0, 5);

        // Actualizar datos de fecha y hora
        $appointment->appointment_date = $newDate->toDateString();
        $appointment->start_time = strlen($newStartTime) === 5 ? $newStartTime . ':00' : $newStartTime;
        $appointment->end_time = $newEndTime;

        // Si estaba cancelada, restaurar su estado activo
        if ($appointment->status === AppointmentStatus::CANCELLED) {
            $appointment->status = $appointment->deposit_paid > 0
                ? AppointmentStatus::CONFIRMED
                : AppointmentStatus::PENDING_VERIFICATION;
        }

        $appointment->cancelled_at = null;
        $appointment->cancellation_reason = null;
        $appointment->attendance_confirmed_at = null;
        $appointment->reminder_24h_sent_at = null;
        $appointment->save();

        // Ajustar almuerzo flexible para la fecha anterior y la nueva
        try {
            if ($oldDate && $oldDate !== $newDate->toDateString()) {
                $this->availabilityService->recalculateLunchForDate($oldDate, $appointment->tenant_id);
            }
            $this->availabilityService->adjustLunchForAppointment($appointment);
        } catch (\Throwable $e) {
            Log::warning("Error recalculando almuerzo al reagendar #{$appointment->appointment_number}: " . $e->getMessage());
        }

        // Notificación WhatsApp
        try {
            $this->whatsAppService->sendAppointmentRescheduled($appointment, $oldDateFormatted, $oldTime);
        } catch (\Throwable $e) {
            Log::warning("Error enviando WhatsApp de reagendamiento #{$appointment->appointment_number}: " . $e->getMessage());
        }

        $newDateReadable = $newDate->locale('es')->isoFormat('dddd D [de] MMMM');
        $successMsg = "✨ ¡Tu cita ha sido reagendada exitosamente para el {$newDateReadable} a las {$newStartTime}!";

        return redirect()->route('portal.confirmation', $appointment->appointment_number)
            ->with('status_message', $successMsg);
    }
}