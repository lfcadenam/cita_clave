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
        $appointment = null;

        if (! empty($search)) {
            $appointment = Appointment::with('service')
                ->where('appointment_number', trim($search))
                ->orWhere('client_phone', trim($search))
                ->latest()
                ->first();
        }

        return view('portal.lookup', compact('appointment', 'search'));
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
}