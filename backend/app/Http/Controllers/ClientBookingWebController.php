<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ClientBookingWebController extends Controller
{
    /**
     * Render the main booking portal (SPA / Multi-step).
     */
    public function index()
    {
        $services = Service::where('is_active', true)->orderBy('sort_order')->get();
        $categories = Service::where('is_active', true)->pluck('category')->unique()->values();
        $nequiConfig = config('payment.nequi');

        return view('portal.booking', compact('services', 'categories', 'nequiConfig'));
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
}