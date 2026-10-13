@extends('portal.layout')

@section('title', 'Reagendar Cita #' . $appointment->appointment_number . ' | Paola Aguilera')

@section('content')
<div class="max-w-xl mx-auto px-4 py-8 sm:py-12"
     x-data="rescheduleApp({
         serviceId: {{ $appointment->service_id }},
         initialDate: '{{ $minDate }}',
         appointmentNumber: '{{ $appointment->appointment_number }}'
     })">

    <!-- Header Card -->
    <div class="bg-white rounded-3xl border border-[#e2f0ea] shadow-lg p-6 sm:p-8 text-center relative overflow-hidden">
        <div class="absolute top-0 left-0 right-0 h-2 bg-gradient-to-r from-[#1e3a5f] via-[#0d9488] to-[#00C975]"></div>

        <div class="w-16 h-16 rounded-full bg-[#e6f7f2] text-[#0d9488] mx-auto flex items-center justify-center mb-3 shadow-inner">
            <i data-lucide="calendar-sync" class="w-8 h-8"></i>
        </div>

        <span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-[#e6f7f2] text-[#0d9488] uppercase tracking-wider mb-2 border border-[#e2f0ea]">
            Reprogramación de Cita
        </span>
        <h2 class="text-2xl font-bold text-[#1e3a5f]">Reagenda tu Turno</h2>
        <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
            Elige una nueva fecha y horario disponible. Tu abono y tu reserva para este servicio se mantendrán totalmente intactos.
        </p>

        <!-- Current Appointment Summary -->
        <div class="mt-6 p-4 rounded-2xl bg-slate-50 border border-slate-200 text-left text-xs space-y-2">
            <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                <span class="font-bold text-slate-500 uppercase tracking-wider">Cita Actual:</span>
                <span class="font-mono font-bold text-[#1e3a5f]">#{{ $appointment->appointment_number }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-slate-500">Servicio:</span>
                <span class="font-bold text-[#0d9488]">{{ $appointment->service->name }} ({{ $appointment->service->duration_minutes }} min)</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-slate-500">Horario actual:</span>
                <span class="font-semibold text-slate-700">
                    {{ $appointment->appointment_date ? $appointment->appointment_date->translatedFormat('d M Y') : '' }} a las {{ substr($appointment->start_time, 0, 5) }}
                </span>
            </div>
            <div class="flex items-center justify-between pt-1 border-t border-slate-200 text-emerald-700">
                <span class="font-medium">Abono Protegido:</span>
                <span class="font-bold">${{ number_format($appointment->deposit_paid ?: $appointment->deposit_amount, 0, ',', '.') }} COP</span>
            </div>
        </div>

        @if($errors->has('slot_error'))
            <div class="mt-4 p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-xs text-rose-800 font-semibold text-center">
                {{ $errors->first('slot_error') }}
            </div>
        @endif

        @if($errors->has('cancellation_error'))
            <div class="mt-4 p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-xs text-rose-800 font-semibold text-center">
                {{ $errors->first('cancellation_error') }}
            </div>
        @endif

        <!-- Formulario de Reagendamiento -->
        <form action="{{ route('portal.reschedule-appointment', $appointment->appointment_number) }}" method="POST" class="mt-6 text-left space-y-5">
            @csrf

            <!-- 1. Selección de Nueva Fecha -->
            <div>
                <label for="new_date" class="block text-xs font-bold text-[#1e3a5f] uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                    <i data-lucide="calendar" class="w-4 h-4 text-[#0d9488]"></i>
                    <span>1. Selecciona la Nueva Fecha</span>
                </label>
                <input type="date"
                       id="new_date"
                       name="new_date"
                       x-model="selectedDate"
                       @change="fetchSlots()"
                       min="{{ $minDate }}"
                       max="{{ $maxDate }}"
                       required
                       class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#0d9488]/30 focus:border-[#0d9488] bg-white transition-all shadow-xs cursor-pointer">
                <p class="text-[11px] text-slate-400 mt-1">
                    Puedes agendar para cualquier día hábil dentro de los próximos 35 días.
                </p>
            </div>

            <!-- 2. Selección de Horario Disponible -->
            <div>
                <label class="block text-xs font-bold text-[#1e3a5f] uppercase tracking-wider mb-2 flex items-center justify-between">
                    <span class="flex items-center gap-1.5">
                        <i data-lucide="clock" class="w-4 h-4 text-[#0d9488]"></i>
                        <span>2. Turnos Disponibles</span>
                    </span>
                    <span x-show="loading" class="text-[11px] text-[#0d9488] font-normal animate-pulse flex items-center gap-1">
                        <i data-lucide="loader-2" class="w-3 h-3 animate-spin"></i> Consultando turnos...
                    </span>
                </label>

                <!-- Hidden Input para enviar el horario seleccionado -->
                <input type="hidden" name="new_time" x-model="selectedTime" required>

                <!-- Loading State -->
                <div x-show="loading" class="py-8 text-center bg-slate-50 rounded-2xl border border-slate-100">
                    <div class="inline-block w-6 h-6 border-2 border-[#0d9488] border-t-transparent rounded-full animate-spin"></div>
                    <p class="text-xs text-slate-500 mt-2">Buscando turnos libres para esta fecha...</p>
                </div>

                <!-- Error o Día Cerrado -->
                <div x-show="!loading && slots.length === 0" x-cloak class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-center">
                    <p class="text-xs font-semibold text-amber-800">
                        No hay turnos disponibles para la fecha seleccionada.
                    </p>
                    <p class="text-[11px] text-amber-700 mt-0.5">
                        Puede ser día de descanso (domingos) o encontrarse con agenda completa. Por favor intenta seleccionando otra fecha.
                    </p>
                </div>

                <!-- Grid de Turnos Disponibles -->
                <div x-show="!loading && slots.length > 0" x-cloak class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                    <template x-for="slot in slots" :key="slot.start_time">
                        <button type="button"
                                @click="selectedTime = slot.start_time"
                                :class="selectedTime === slot.start_time
                                    ? 'bg-[#00C975] text-white border-[#00C975] shadow-md shadow-emerald-500/25 scale-[1.02]'
                                    : 'bg-white text-slate-700 border-slate-200 hover:border-[#0d9488] hover:bg-emerald-50/40'"
                                class="py-2.5 px-3 rounded-xl border text-xs font-bold transition-all text-center flex flex-col items-center justify-center cursor-pointer">
                            <span x-text="slot.start_formatted"></span>
                            <span class="text-[10px] opacity-75 font-normal" x-text="'a ' + slot.end_formatted"></span>
                        </button>
                    </template>
                </div>
            </div>

            <!-- 3. Motivo Opcional -->
            <div>
                <label for="reason" class="block text-xs font-semibold text-slate-700 mb-1">
                    Motivo de la reprogramación (Opcional)
                </label>
                <textarea id="reason"
                          name="reason"
                          rows="2"
                          placeholder="Ej: Inconveniente laboral, cambio de turno..."
                          class="w-full text-xs p-3 rounded-xl border border-slate-200 bg-white focus:ring-2 focus:ring-[#0d9488]/30 focus:border-[#0d9488] outline-none transition-all placeholder:text-slate-400"></textarea>
            </div>

            <!-- Resumen de Selección y Acciones -->
            <div class="pt-2 border-t border-[#e2f0ea] space-y-3">
                <div x-show="selectedTime" x-cloak class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-800 font-semibold flex items-center justify-between">
                    <span>Nuevo turno seleccionado:</span>
                    <span class="font-bold" x-text="selectedDate + ' a las ' + selectedTime"></span>
                </div>

                <button type="submit"
                        :disabled="!selectedDate || !selectedTime"
                        :class="(!selectedDate || !selectedTime) ? 'opacity-50 cursor-not-allowed bg-slate-300' : 'bg-[#00C975] hover:bg-[#00A862] cursor-pointer shadow-md shadow-emerald-500/20'"
                        class="w-full py-3 px-4 rounded-xl text-xs font-bold text-white transition-all text-center flex items-center justify-center gap-2">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Confirmar Nuevo Horario</span>
                </button>

                <a href="{{ route('portal.confirmation', $appointment->appointment_number) }}"
                   class="block text-center py-2.5 px-4 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors">
                    Volver a mi cita sin cambios
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function rescheduleApp(config) {
    return {
        serviceId: config.serviceId,
        selectedDate: config.initialDate,
        selectedTime: null,
        slots: [],
        loading: false,

        init() {
            this.fetchSlots();
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        async fetchSlots() {
            if (!this.selectedDate) return;
            this.loading = true;
            this.selectedTime = null;
            this.slots = [];

            try {
                const response = await fetch(`/api/v1/availability/slots?service_id=${this.serviceId}&date=${this.selectedDate}&strict_anti_gaps=false`);
                const data = await response.json();
                if (data.success && data.data && data.data.slots) {
                    this.slots = data.data.slots;
                } else {
                    this.slots = [];
                }
            } catch (e) {
                console.error("Error al cargar turnos disponibles:", e);
                this.slots = [];
            } finally {
                this.loading = false;
                this.$nextTick(() => {
                    if (window.lucide) window.lucide.createIcons();
                });
            }
        }
    }
}
</script>
@endsection
