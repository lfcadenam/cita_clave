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
        @elseif($appointment->status === \App\Enums\AppointmentStatus::CANCELLED)
            <div class="w-16 h-16 rounded-full bg-rose-50 text-rose-600 mx-auto flex items-center justify-center mb-3 shadow-inner">
                <i data-lucide="x-circle" class="w-8 h-8"></i>
            </div>
            <span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 uppercase tracking-wider mb-2 border border-rose-200">
                Cita Cancelada
            </span>
            <h2 class="text-2xl font-bold text-[#1e3a5f]">Esta cita ha sido cancelada</h2>
            <p class="text-xs text-slate-500 mt-1">El cupo previamente reservado ha sido liberado en la agenda.</p>
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

        @if(session('status_message'))
            <div class="mt-4 p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-800 font-semibold text-center">
                {{ session('status_message') }}
            </div>
        @endif

        @if($errors->has('cancellation_error'))
            <div class="mt-4 p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-xs text-rose-800 font-semibold text-center">
                {{ $errors->first('cancellation_error') }}
            </div>
        @endif

        @if($errors->has('cancellation_reason'))
            <div class="mt-4 p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-xs text-rose-800 font-semibold text-center">
                {{ $errors->first('cancellation_reason') }}
            </div>
        @endif

        <!-- Cancelled State Details -->
        @if($appointment->status === \App\Enums\AppointmentStatus::CANCELLED)
            <div class="mt-6 p-4 rounded-2xl bg-rose-50/70 border border-rose-200 text-left">
                <div class="text-xs font-bold text-rose-800 uppercase tracking-wider">
                    Motivo de la Cancelación
                </div>
                <p class="text-xs text-rose-900 mt-1.5 leading-relaxed font-medium">
                    "{{ $appointment->cancellation_reason ?: 'Cancelada por el cliente.' }}"
                </p>
                @if($appointment->cancelled_at)
                    <p class="text-[11px] text-rose-600/80 mt-2">
                        Fecha: {{ $appointment->cancelled_at->translatedFormat('d/m/Y \a \l\a\s h:i A') }}
                    </p>
                @endif
            </div>

            <div class="mt-6">
                <a href="{{ route('portal.booking') }}"
                   class="w-full py-3 px-4 rounded-xl text-xs font-bold bg-[#1e3a5f] text-white hover:bg-[#162d4a] shadow-md flex items-center justify-center gap-2 transition-colors">
                    <i data-lucide="calendar-plus" class="w-4 h-4"></i>
                    <span>Agendar una Nueva Cita</span>
                </a>
            </div>
        @else
            <!-- Active Appointment Actions -->

            <!-- Manual Attendance Confirmation Section -->
            @if($appointment->attendance_confirmed_at)
                <div class="mt-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-center">
                    <div class="text-xs font-bold text-emerald-800 uppercase tracking-wider flex items-center justify-center gap-1.5">
                        <i data-lucide="check-check" class="w-4 h-4 text-emerald-600"></i>
                        <span>Asistencia Confirmada</span>
                    </div>
                    <p class="text-xs text-emerald-700 mt-1">
                        Has confirmado tu asistencia para esta cita el {{ $appointment->attendance_confirmed_at->format('d/m/Y \a \l\a\s h:i A') }}. ¡Te esperamos con gusto!
                    </p>
                </div>
            @elseif($appointment->status === \App\Enums\AppointmentStatus::CONFIRMED)
                <div class="mt-6 p-4 rounded-2xl bg-[#f0fdf4] border border-[#86efac] text-center">
                    <div class="text-xs font-bold text-[#166534] uppercase tracking-wider mb-1">
                        ¿Confirmas tu asistencia para el día de tu cita?
                    </div>
                    <p class="text-xs text-[#15803d] mb-3">
                        Ayúdanos a preparar tu espacio confirmando tu asistencia de forma manual:
                    </p>
                    <form action="{{ route('portal.confirm-attendance', $appointment->appointment_number) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full py-3 px-4 rounded-xl text-xs font-bold bg-[#0d9488] text-white hover:bg-[#0f766e] shadow-md flex items-center justify-center gap-2 transition-all cursor-pointer">
                            <i data-lucide="calendar-check" class="w-4 h-4"></i>
                            <span>Confirmar Mi Asistencia en Línea</span>
                        </button>
                    </form>
                </div>
            @endif

            <!-- Action Buttons: WhatsApp -->
            <div class="mt-6 space-y-3">
                @php
                    $ownerPhone = preg_replace('/\D/', '', $appointment->tenant?->whatsapp_number ?: ($appointment->tenant?->phone ?: '3103248385'));
                    $waMsg = urlencode("Hola Paola, confirmo mi asistencia a mi cita #{$appointment->appointment_number} de {$appointment->service->name} para el día " . ($appointment->appointment_date ? $appointment->appointment_date->format('d/m/Y') : '') . " a las " . substr($appointment->start_time, 0, 5) . ". Mi nombre es {$appointment->client_name}.");
                @endphp
                <a href="https://wa.me/57{{ $ownerPhone }}?text={{ $waMsg }}"
                   target="_blank"
                   class="w-full py-3 px-4 rounded-xl text-xs font-bold bg-[#25d366] text-white hover:bg-[#20bd5a] shadow-md flex items-center justify-center gap-2 transition-colors">
                    <i data-lucide="message-circle" class="w-4 h-4"></i>
                    <span>Confirmar Asistencia por WhatsApp a Paola</span>
                </a>
            </div>

            <!-- Client Cancellation Module -->
            @if($appointment->canBeCancelledByClient())
                <!-- More than 24 hours remaining: Self-cancellation is permitted with mandatory observation -->
                <div x-data="{ openCancelModal: false, reason: '' }" class="mt-6 pt-5 border-t border-[#e2f0ea]">
                    <div x-show="!openCancelModal" class="text-center">
                        <button type="button" 
                                @click="openCancelModal = true"
                                class="text-xs text-slate-400 hover:text-rose-600 font-medium transition-colors inline-flex items-center gap-1.5 cursor-pointer">
                            <i data-lucide="calendar-x" class="w-3.5 h-3.5"></i>
                            <span>¿No podrás asistir? Cancelar cita</span>
                        </button>
                    </div>

                    <!-- Collapsible Cancellation Box -->
                    <div x-show="openCancelModal" x-cloak class="p-5 rounded-2xl bg-rose-50/60 border border-rose-200 text-left">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-rose-800 uppercase tracking-wider">
                                Cancelar Mi Cita
                            </span>
                            <button type="button" @click="openCancelModal = false" class="text-xs text-slate-400 hover:text-slate-600 font-medium">
                                Cerrar
                            </button>
                        </div>
                        <p class="text-xs text-slate-600 mb-3 leading-relaxed">
                            Para confirmar la cancelación de tu cita, es obligatorio ingresar el motivo. Al enviar este formulario, tu turno será liberado de inmediato.
                        </p>

                        <form action="{{ route('portal.cancel-appointment', $appointment->appointment_number) }}" method="POST" class="space-y-3">
                            @csrf
                            <div>
                                <label for="cancellation_reason" class="block text-xs font-semibold text-slate-700 mb-1">
                                    Motivo u observación de cancelación <span class="text-rose-600">*</span>
                                </label>
                                <textarea id="cancellation_reason"
                                          name="cancellation_reason"
                                          x-model="reason"
                                          required
                                          minlength="5"
                                          maxlength="500"
                                          rows="3"
                                          placeholder="Ej: Se me presentó un inconveniente personal de fuerza mayor..."
                                          class="w-full text-xs p-3 rounded-xl border border-rose-200 bg-white focus:ring-2 focus:ring-rose-400 focus:border-rose-400 outline-none transition-all placeholder:text-slate-400"></textarea>
                                <div class="flex justify-between items-center text-[11px] text-slate-400 mt-1">
                                    <span>Campo obligatorio (mín. 5 caracteres)</span>
                                    <span x-text="reason.length + '/500'"></span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 pt-1">
                                <button type="submit"
                                        :disabled="reason.trim().length < 5"
                                        :class="reason.trim().length < 5 ? 'opacity-50 cursor-not-allowed bg-rose-300' : 'bg-rose-600 hover:bg-rose-700 cursor-pointer'"
                                        class="flex-1 py-2.5 px-4 rounded-xl text-xs font-bold text-white shadow-sm transition-all text-center">
                                    Confirmar Cancelación
                                </button>
                                <button type="button"
                                        @click="openCancelModal = false"
                                        class="py-2.5 px-4 rounded-xl text-xs font-semibold bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">
                                    Conservar Cita
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @else
                <!-- Less than 24 hours remaining: Self-cancellation disabled, admin contact required -->
                <div class="mt-6 pt-5 border-t border-[#e2f0ea]">
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-left">
                        <div class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                            <i data-lucide="clock" class="w-3.5 h-3.5 text-slate-500"></i>
                            <span>Cancelación de Cita (Menos de 24 horas)</span>
                        </div>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Faltan menos de 24 horas para el inicio de tu cita. Por políticas del estudio, la autocancelación web no está disponible en este lapso y solo puede ser gestionada directamente por la administración.
                        </p>
                        @php
                            $cancelAdminMsg = urlencode("Hola Paola, necesito solicitar la cancelación de mi cita #{$appointment->appointment_number} de {$appointment->service->name} para el día " . ($appointment->appointment_date ? $appointment->appointment_date->format('d/m/Y') : '') . " a las " . substr($appointment->start_time, 0, 5) . ".");
                        @endphp
                        <div class="mt-3">
                            <a href="https://wa.me/57{{ $ownerPhone }}?text={{ $cancelAdminMsg }}"
                               target="_blank"
                               class="inline-flex items-center justify-center w-full py-2.5 px-3 rounded-xl text-xs font-semibold bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 transition-colors gap-1.5">
                                <i data-lucide="message-circle" class="w-3.5 h-3.5 text-emerald-600"></i>
                                <span>Solicitar Cancelación a Paola vía WhatsApp</span>
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>
@endsection

