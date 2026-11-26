<x-filament-panels::page>
    @php
        $pendingCount = $this->pendingTomorrowCount;
        $totalCount = $this->totalTomorrowCount;
        $recentReminders = $this->recentReminders;
    @endphp

    <style>
        .nuvex-rem-wrapper {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            color: #1e293b;
            display: flex;
            flex-direction: column;
            gap: 24px;
            width: 100%;
        }

        .nuvex-card {
            background: #ffffff;
            border-radius: 24px;
            border: 1px solid #e2e8f0;
            padding: 24px;
            box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.04);
        }

        .nuvex-badge-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
        }

        .nuvex-time-chip {
            padding: 8px 16px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            color: #334155;
        }

        .nuvex-time-chip:hover {
            border-color: #0d9488;
            color: #0d9488;
            background: #f0fdfa;
        }

        .nuvex-time-chip.active {
            background: #0d9488;
            border-color: #0d9488;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.25);
        }

        .nuvex-console-output {
            background: #0f172a;
            color: #38bdf8;
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            font-size: 12.5px;
            line-height: 1.6;
            padding: 16px;
            border-radius: 16px;
            overflow-x: auto;
            max-height: 260px;
            white-space: pre-wrap;
        }
    </style>

    <div class="nuvex-rem-wrapper">
        <!-- Banner Superior de Estado Global -->
        <div class="nuvex-card" style="border-left: 5px solid #0d9488; background: linear-gradient(135deg, #ffffff 0%, #f0fdfa 100%);">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div style="width: 48px; height: 48px; border-radius: 16px; background: #ccfbf1; color: #0f766e; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-lg font-bold text-slate-800">Programador de Notificaciones de Citas</h2>
                            @if($isReminderEnabled)
                                <span class="nuvex-badge-pill" style="background: #dcfce7; color: #15803d;">
                                    <span style="width: 6px; height: 6px; border-radius: 50%; background: #22c55e;"></span>
                                    Servicio Activo
                                </span>
                            @else
                                <span class="nuvex-badge-pill" style="background: #fee2e2; color: #b91c1c;">
                                    <span style="width: 6px; height: 6px; border-radius: 50%; background: #ef4444;"></span>
                                    En Pausa
                                </span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Hora actual programada: <strong class="text-teal-700 font-semibold">{{ $reminderTime }} (Hora Colombia)</strong> &bull; Zona horaria: {{ $timezone }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-6 self-start md:self-auto border-t md:border-t-0 pt-3 md:pt-0 border-slate-200">
                    <div class="text-left md:text-right">
                        <span class="text-xs uppercase tracking-wider text-slate-400 font-bold block">Citas de Mañana</span>
                        <span class="text-xl font-extrabold text-slate-800">{{ $totalCount }} confirmadas</span>
                        <span class="text-xs text-amber-600 block font-medium">({{ $pendingCount }} pendientes de aviso)</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cuadrícula Principal (2 Columnas) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Columna 1: Configuración de Horario del Cron -->
            <div class="nuvex-card flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                        <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                            <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Horario Diario de Envío Automático
                        </h3>
                    </div>

                    <form wire:submit.prevent="saveSettings" class="space-y-5">
                        <!-- Interruptor de Activación -->
                        <div class="flex items-center justify-between p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                            <div>
                                <label for="isReminderEnabled" class="text-sm font-semibold text-slate-700 cursor-pointer">
                                    Habilitar ejecución automática
                                </label>
                                <p class="text-xs text-slate-500">Si está inactivo, el cron diario no enviará recordatorios automáticos.</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="isReminderEnabled" wire:model="isReminderEnabled" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-teal-600"></div>
                            </label>
                        </div>

                        <!-- Selector de Hora -->
                        <div>
                            <label class="block text-xs uppercase tracking-wider font-bold text-slate-600 mb-2">
                                Hora de Disparo (Formato 24h — HH:MM)
                            </label>
                            <div class="flex items-center gap-3">
                                <input 
                                    type="time" 
                                    wire:model="reminderTime"
                                    class="w-full text-base font-bold text-slate-800 bg-white border border-slate-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 shadow-sm"
                                    required
                                >
                            </div>
                            @error('reminderTime')
                                <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Botones de Acceso Rápido -->
                        <div>
                            <span class="block text-xs uppercase tracking-wider font-bold text-slate-500 mb-2">
                                Horas frecuentes sugeridas:
                            </span>
                            <div class="flex flex-wrap gap-2">
                                @foreach(['08:00', '09:00', '10:00', '11:00', '14:00', '16:00'] as $preset)
                                    <button 
                                        type="button" 
                                        wire:click="setTimePreset('{{ $preset }}')"
                                        class="nuvex-time-chip {{ $reminderTime === $preset ? 'active' : '' }}"
                                    >
                                        {{ $preset }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <!-- Nota explicativa -->
                        <div class="p-3.5 rounded-xl bg-teal-50 border border-teal-100 text-xs text-teal-800 leading-relaxed">
                            <strong>¿Cómo funciona?</strong> Cada día a las <strong>{{ $reminderTime }}</strong>, el sistema busca las citas confirmadas del día siguiente (24h de anticipación) y envía un mensaje de WhatsApp interactivo con el resumen del servicio y el botón para confirmar en 1 clic.
                        </div>

                        <!-- Botón Guardar -->
                        <div class="pt-2">
                            <button 
                                type="submit"
                                wire:loading.attr="disabled"
                                class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-semibold text-sm transition-all shadow-sm hover:shadow"
                            >
                                <span wire:loading.remove wire:target="saveSettings">Guardar Horario de Recordatorios</span>
                                <span wire:loading wire:target="saveSettings" class="inline-flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                    Guardando configuración...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Columna 2: Panel de Disparo Manual Inmediato -->
            <div class="nuvex-card flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                        <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            Disparo Manual Inmediato (Test & Ejecución)
                        </h3>
                        <span class="nuvex-badge-pill" style="background: #e0e7ff; color: #3730a3;">
                            Bajo Demanda
                        </span>
                    </div>

                    <form wire:submit.prevent="triggerManualExecution" class="space-y-4">
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Permite forzar la ejecución del proceso de recordatorios en este momento sin esperar a la hora automática del cron. Útil para pruebas o envíos anticipados.
                        </p>

                        <!-- Fecha Objetivo -->
                        <div>
                            <label class="block text-xs uppercase tracking-wider font-bold text-slate-600 mb-1.5">
                                Fecha de citas a procesar:
                            </label>
                            <input 
                                type="date" 
                                wire:model="testDate"
                                class="w-full text-sm font-semibold text-slate-800 bg-white border border-slate-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm"
                                required
                            >
                            <span class="text-xs text-slate-400 mt-1 block">Por defecto está seleccionada la fecha de mañana.</span>
                        </div>

                        <!-- Checkbox Forzar Reenvío -->
                        <div class="flex items-center gap-2.5 p-3 rounded-xl bg-slate-50 border border-slate-200">
                            <input 
                                type="checkbox" 
                                id="testForce" 
                                wire:model="testForce"
                                class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4"
                            >
                            <label for="testForce" class="text-xs text-slate-700 cursor-pointer font-medium">
                                Forzar envío incluso a citas que ya recibieron recordatorio previo
                            </label>
                        </div>

                        <!-- Botón Ejecutar Ahora -->
                        <button 
                            type="submit"
                            wire:loading.attr="disabled"
                            class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm transition-all shadow-sm hover:shadow"
                        >
                            <span wire:loading.remove wire:target="triggerManualExecution">
                                <svg class="w-4 h-4 inline-block -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Ejecutar Recordatorios Ahora
                            </span>
                            <span wire:loading wire:target="triggerManualExecution" class="inline-flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                Procesando recordatorios...
                            </span>
                        </button>
                    </form>

                    <!-- Terminal Output -->
                    @if($executionOutput)
                        <div class="mt-4">
                            <span class="block text-xs uppercase tracking-wider font-bold text-slate-500 mb-1.5">
                                Resultado de la última ejecución:
                            </span>
                            <div class="nuvex-console-output">{{ $executionOutput }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Tabla Inferior: Últimos Recordatorios Enviados -->
        <div class="nuvex-card">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
                <div>
                    <h3 class="text-base font-bold text-slate-800">Últimos Recordatorios Notificados</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Historial de citas que recibieron recordatorio de asistencia por WhatsApp y correo.</p>
                </div>
            </div>

            @if($recentReminders->isEmpty())
                <div class="py-10 text-center text-slate-400 text-sm">
                    No hay registros de recordatorios enviados recientemente en la base de datos.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 text-slate-400 uppercase tracking-wider font-bold">
                                <th class="py-3 px-3">Cita</th>
                                <th class="py-3 px-3">Clienta & WhatsApp</th>
                                <th class="py-3 px-3">Salón & Servicio</th>
                                <th class="py-3 px-3">Fecha de Cita</th>
                                <th class="py-3 px-3">Notificado En</th>
                                <th class="py-3 px-3 text-right">Confirmación</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($recentReminders as $apt)
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="py-3 px-3 font-bold text-slate-800">
                                        #{{ $apt->appointment_number }}
                                    </td>
                                    <td class="py-3 px-3">
                                        <div class="font-semibold text-slate-700">{{ $apt->client_name }}</div>
                                        <div class="text-slate-400 text-[11px]">{{ $apt->client_phone ?: 'Sin teléfono' }}</div>
                                    </td>
                                    <td class="py-3 px-3">
                                        <div class="font-medium text-slate-700">{{ $apt->service?->name ?? 'Servicio' }}</div>
                                        <div class="text-slate-400 text-[11px]">{{ $apt->tenant?->name ?? 'Salón' }}</div>
                                    </td>
                                    <td class="py-3 px-3 font-medium text-slate-700">
                                        {{ \Carbon\Carbon::parse($apt->appointment_date)->format('d/m/Y') }} {{ substr($apt->start_time, 0, 5) }}
                                    </td>
                                    <td class="py-3 px-3 text-slate-500">
                                        {{ \Carbon\Carbon::parse($apt->reminder_24h_sent_at)->locale('es')->isoFormat('D MMM, HH:mm') }}
                                    </td>
                                    <td class="py-3 px-3 text-right">
                                        @if($apt->attendance_confirmed_at)
                                            <span class="nuvex-badge-pill" style="background: #dcfce7; color: #166534;">
                                                Confirmada por Clienta
                                            </span>
                                        @else
                                            <span class="nuvex-badge-pill" style="background: #fef3c7; color: #92400e;">
                                                Pendiente de Confirmar
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
