@extends('portal.layout')

@section('title', 'Consultar mi Cita | Paola Aguilera')

@section('content')
<div class="max-w-md mx-auto px-4 py-10">
    <div class="text-center mb-6">
        <div class="w-12 h-12 rounded-2xl bg-[#e6f7f2] text-[#0d9488] mx-auto flex items-center justify-center mb-2 shadow-xs border border-[#e2f0ea]">
            <i data-lucide="search" class="w-6 h-6"></i>
        </div>
        <h2 class="text-2xl font-bold text-[#1e3a5f]">Consulta tu Reserva</h2>
        <p class="text-xs text-slate-500 mt-1">Ingresa tu número de cita (ej: PA-2609...) o número de celular</p>
    </div>

    <!-- Search Form -->
    <form action="{{ url('/reserva/consulta') }}" method="GET" class="bg-white p-5 rounded-2xl border border-[#e2f0ea] shadow-sm mb-6">
        <div>
            <label class="block text-xs font-bold text-[#1e3a5f] uppercase mb-1">N° Cita o Celular</label>
            <div class="relative">
                <input type="text" name="search" value="{{ $search ?? '' }}" required placeholder="PA-2609... o 310..."
                       class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#0d9488]/20 focus:border-[#0d9488]">
            </div>
        </div>
        <button type="submit" class="w-full mt-4 py-2.5 rounded-xl text-xs font-bold bg-[#0d9488] text-white hover:bg-[#0f766e] transition-colors shadow-md shadow-teal-500/20">
            Buscar Cita
        </button>
    </form>

    @if(!empty($search))
        @if($appointment)
            <div class="bg-white p-5 rounded-2xl border border-[#e2f0ea] shadow-md">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <span class="text-xs font-bold font-mono text-[#1e3a5f]">{{ $appointment->appointment_number }}</span>
                    <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-[#e6f7f2] text-[#0d9488] border border-[#e2f0ea]">
                        {{ $appointment->status->label() }}
                    </span>
                </div>
                <div class="py-3 space-y-2 text-xs">
                    <p><span class="text-slate-400">Servicio:</span> <span class="font-bold text-slate-800">{{ $appointment->service->name }}</span></p>
                    <p><span class="text-slate-400">Fecha:</span> <span class="font-semibold text-slate-800">{{ $appointment->appointment_date->toDateString() }} ({{ substr($appointment->start_time, 0, 5) }} - {{ substr($appointment->end_time, 0, 5) }})</span></p>
                    <p><span class="text-slate-400">Saldo en local:</span> <span class="font-bold text-[#1e3a5f]">${{ number_format($appointment->balance_due, 0, ',', '.') }} COP</span></p>
                </div>
                <a href="{{ url('/reserva/confirmacion/' . $appointment->appointment_number) }}"
                   class="block text-center mt-3 py-2.5 rounded-xl text-xs font-bold bg-[#e6f7f2] hover:bg-[#ccfbf1] text-[#1e3a5f] border border-[#e2f0ea] transition-colors">
                    Ver Comprobante Digital Completo →
                </a>
            </div>
        @else
            <div class="text-center py-6 bg-white rounded-2xl border border-[#e2f0ea] text-xs text-slate-500">
                No encontramos ninguna cita con el criterio: <strong>{{ $search }}</strong>
            </div>
        @endif
    @endif
</div>
@endsection
