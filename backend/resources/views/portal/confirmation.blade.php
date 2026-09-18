@extends('portal.layout')

@section('title', 'Confirmación de Cita #' . $appointment->appointment_number . ' | Paola Aguilera')

@section('content')
<div class="max-w-xl mx-auto px-4 py-8 sm:py-12">
    
    <!-- Status Header Card -->
    <div class="bg-white rounded-3xl border border-[#e2f0ea] shadow-lg p-6 sm:p-8 text-center relative overflow-hidden">
        <div class="absolute top-0 left-0 right-0 h-2 bg-gradient-to-r from-[#1e3a5f] via-[#0d9488] to-[#14b8a6]"></div>

        @if($appointment->status === \App\Enums\AppointmentStatus::CONFIRMED)
            <div class="w-16 h-16 rounded-full bg-[#e6f7f2] text-[#0d9488] mx-auto flex items-center justify-center mb-3 shadow-inner">
                <i data-lucide="check-circle" class="w-8 h-8"></i>
            </div>
            <span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-[#e6f7f2] text-[#0d9488] uppercase tracking-wider mb-2 border border-[#e2f0ea]">
                Cita Confirmada
            </span>
            <h2 class="text-2xl font-bold text-[#1e3a5f]">¡Tu cita está asegurada!</h2>
            <p class="text-xs text-slate-500 mt-1">Hemos recibido tu abono y reservado tu espacio en la agenda de Paola.</p>
        @elseif($appointment->status === \App\Enums\AppointmentStatus::PENDING_VERIFICATION)
            <div class="w-16 h-16 rounded-full bg-amber-50 text-amber-600 mx-auto flex items-center justify-center mb-3 shadow-inner animate-pulse">
                <i data-lucide="clock" class="w-8 h-8"></i>
            </div>
            <span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 uppercase tracking-wider mb-2 border border-amber-200">
                Comprobante Nequi por Validar
            </span>
            <h2 class="text-2xl font-bold text-[#1e3a5f]">¡Tu comprobante fue recibido!</h2>
            <p class="text-xs text-slate-500 mt-1">Paola validará tu transferencia en breve y te enviará un WhatsApp de confirmación.</p>
        @else
            <div class="w-16 h-16 rounded-full bg-slate-100 text-[#1e3a5f] mx-auto flex items-center justify-center mb-3">
                <i data-lucide="calendar" class="w-8 h-8"></i>
            </div>
            <span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700 uppercase tracking-wider mb-2 border border-slate-200">
                {{ $appointment->status->label() }}
            </span>
            <h2 class="text-2xl font-bold text-[#1e3a5f]">Estado de tu Reserva</h2>
        @endif

        <!-- Appointment Number Badge -->
        <div class="mt-6 py-3 px-4 rounded-2xl bg-[#f3f8f6] border border-[#e2f0ea] flex items-center justify-between">
            <span class="text-xs text-slate-500 font-semibold uppercase">Número de Cita:</span>
            <span class="font-mono font-bold text-base text-[#1e3a5f] tracking-wider">{{ $appointment->appointment_number }}</span>
        </div>

        <!-- Details Card -->
        <div class="mt-6 text-left space-y-3 text-xs border-t border-[#e2f0ea] pt-6">
            <div class="flex justify-between items-center">
                <span class="text-slate-500">Clienta:</span>
                <span class="font-bold text-[#1e3a5f]">{{ $appointment->client_name }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-slate-500">Tratamiento:</span>
                <span class="font-bold text-[#0d9488] text-sm">{{ $appointment->service->name }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-slate-500">Fecha:</span>
                <span class="font-bold text-slate-800">{{ $appointment->appointment_date->translatedFormat('l, d \d\e F \d\e Y') }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-slate-500">Horario:</span>
                <span class="font-bold text-slate-800">{{ substr($appointment->start_time, 0, 5) }} a {{ substr($appointment->end_time, 0, 5) }} ({{ $appointment->service->formatted_duration }})</span>
            </div>
            <div class="flex justify-between items-center pt-2 border-t border-[#e2f0ea]">
                <span class="text-slate-500">Abono Recibido:</span>
                <span class="font-bold text-[#0d9488]">${{ number_format($appointment->deposit_amount, 0, ',', '.') }} COP</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-slate-500">Saldo Pendiente en Local:</span>
                <span class="font-bold text-[#1e3a5f]">${{ number_format($appointment->balance_due, 0, ',', '.') }} COP</span>
            </div>
        </div>

        <!-- Action Buttons: Calendar & WhatsApp -->
        <div class="mt-8 space-y-3">
            <a href="{{ url('/reserva/calendar/' . $appointment->appointment_number . '.ics') }}"
               class="w-full py-3 px-4 rounded-xl text-xs font-bold bg-[#1e3a5f] text-white hover:bg-[#162d4a] shadow-md flex items-center justify-center gap-2 transition-colors">
                <i data-lucide="calendar-plus" class="w-4 h-4"></i>
                <span>Añadir a Google / Apple Calendar</span>
            </a>

            <a href="https://wa.me/573106080402?text=Hola%20Paola,%20tengo%20la%20cita%20%23{{ $appointment->appointment_number }}%20para%20el%20{{ $appointment->appointment_date->toDateString() }}"
               target="_blank"
               class="w-full py-3 px-4 rounded-xl text-xs font-bold bg-[#0d9488] text-white hover:bg-[#0f766e] shadow-md flex items-center justify-center gap-2 transition-colors">
                <i data-lucide="message-circle" class="w-4 h-4"></i>
                <span>Escribir a Paola por WhatsApp</span>
            </a>
        </div>
    </div>
</div>
@endsection
