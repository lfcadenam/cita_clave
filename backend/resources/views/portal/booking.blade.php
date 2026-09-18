@extends('portal.layout')

@section('title', 'Reserva tu Cita de Belleza | Cita Clave')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 py-6 sm:py-10" x-data="bookingApp()" x-init="init()">

    <!-- Hero / Presentation Banner -->
    <div class="text-center mb-8">
        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-[#e6f7f2] text-[#0d9488] text-[11px] font-bold uppercase tracking-widest mb-3 border border-[#e2f0ea] shadow-sm">
            <i data-lucide="sparkles" class="w-3.5 h-3.5 text-[#0d9488]"></i>
            <span>Agenda en Línea • Confirmación Inmediata</span>
        </div>
        <h2 class="text-3xl sm:text-4xl lg:text-5xl  font-bold text-slate-900 tracking-tight leading-tight">
            Reserva tu Experiencia de Belleza
        </h2>
        <p class="text-slate-600 text-sm sm:text-base mt-2 max-w-xl mx-auto leading-relaxed">
            Realza tu mirada y cuida tu piel con tratamientos personalizados en un ambiente exclusivo.
        </p>
    </div>

    <!-- Stepper Navigation -->
    <div class="flex items-center justify-between mb-8 px-4 sm:px-12 relative max-w-2xl mx-auto">
        <div class="absolute left-10 right-10 top-1/2 -translate-y-1/2 h-1 bg-[#e6f7f2] -z-0"></div>
        <div class="absolute left-10 top-1/2 -translate-y-1/2 h-1 bg-[#0d9488] -z-0 transition-all duration-300"
             :style="'width: ' + ((step - 1) / 3 * 100) + '%'"></div>

        <!-- Step 1 Indicator -->
        <button @click="if (selectedService) goToStep(1)" class="relative z-10 flex flex-col items-center gap-1.5 group focus:outline-none cursor-pointer">
            <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-xs transition-all duration-200"
                 :class="step >= 1 ? 'bg-[#0d9488] text-white shadow-md shadow-teal-500/30 ring-4 ring-[#e6f7f2]' : 'bg-white text-slate-400 border border-slate-200'">
                <template x-if="step > 1"><i data-lucide="check" class="w-4 h-4"></i></template>
                <template x-if="step <= 1"><span>1</span></template>
            </div>
            <span class="text-[11px] font-bold tracking-wide" :class="step >= 1 ? 'text-[#1e3a5f] font-extrabold' : 'text-slate-400'">Servicio</span>
        </button>

        <!-- Step 2 Indicator -->
        <button @click="if (selectedService && selectedDate) goToStep(2)" class="relative z-10 flex flex-col items-center gap-1.5 group focus:outline-none cursor-pointer">
            <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-xs transition-all duration-200"
                 :class="step >= 2 ? 'bg-[#0d9488] text-white shadow-md shadow-teal-500/30 ring-4 ring-[#e6f7f2]' : 'bg-white text-slate-400 border border-slate-200'">
                <template x-if="step > 2"><i data-lucide="check" class="w-4 h-4"></i></template>
                <template x-if="step <= 2"><span>2</span></template>
            </div>
            <span class="text-[11px] font-bold tracking-wide" :class="step >= 2 ? 'text-[#1e3a5f] font-extrabold' : 'text-slate-400'">Horario</span>
        </button>

        <!-- Step 3 Indicator -->
        <button @click="if (selectedService && selectedSlot) goToStep(3)" class="relative z-10 flex flex-col items-center gap-1.5 group focus:outline-none cursor-pointer">
            <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-xs transition-all duration-200"
                 :class="step >= 3 ? 'bg-[#0d9488] text-white shadow-md shadow-teal-500/30 ring-4 ring-[#e6f7f2]' : 'bg-white text-slate-400 border border-slate-200'">
                <template x-if="step > 3"><i data-lucide="check" class="w-4 h-4"></i></template>
                <template x-if="step <= 3"><span>3</span></template>
            </div>
            <span class="text-[11px] font-bold tracking-wide" :class="step >= 3 ? 'text-[#1e3a5f] font-extrabold' : 'text-slate-400'">Tus Datos</span>
        </button>

        <!-- Step 4 Indicator -->
        <button class="relative z-10 flex flex-col items-center gap-1.5 group focus:outline-none">
            <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-xs transition-all duration-200"
                 :class="step >= 4 ? 'bg-[#0d9488] text-white shadow-md shadow-teal-500/30 ring-4 ring-[#e6f7f2]' : 'bg-white text-slate-400 border border-slate-200'">
                <span>4</span>
            </div>
            <span class="text-[11px] font-bold tracking-wide" :class="step >= 4 ? 'text-[#1e3a5f] font-extrabold' : 'text-slate-400'">Abono</span>
        </button>
    </div>

    <!-- STEP 1: SELECT SERVICE (MODERN BEAUTY CARDS) -->
    <div x-show="step === 1" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
        
        <!-- Filter & Search Controls -->
        <div class="mb-6 space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <h3 class=" font-bold text-xl sm:text-2xl text-slate-900 tracking-tight">1. Selecciona tu Tratamiento</h3>
                
                <!-- Search Input -->
                <div class="relative w-full sm:w-64">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                    <input type="text" x-model="searchQuery" placeholder="Buscar tratamiento..."
                           class="w-full pl-9 pr-3.5 py-1.5 rounded-full border border-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-[#0d9488]/20 focus:border-[#0d9488] bg-white shadow-sm font-medium">
                </div>
            </div>

            <!-- Category Pills -->
            <div class="flex items-center gap-2 overflow-x-auto pb-1.5 no-scrollbar scroll-smooth">
                <button type="button" @click="activeCategory = 'ALL'"
                        class="flex-shrink-0 px-3.5 py-1.5 rounded-full text-xs font-bold transition-all cursor-pointer"
                        :class="activeCategory === 'ALL' ? 'bg-slate-900 text-white shadow-md' : 'bg-white text-slate-600 border border-slate-200/80 hover:bg-slate-50'">
                    Todos
                </button>
                <button type="button" @click="activeCategory = 'PESTANAS_CEJAS'"
                        class="flex-shrink-0 px-3.5 py-1.5 rounded-full text-xs font-bold transition-all cursor-pointer"
                        :class="activeCategory === 'PESTANAS_CEJAS' ? 'bg-[#0d9488] text-white shadow-md shadow-teal-500/20' : 'bg-white text-slate-600 border border-slate-200/80 hover:bg-[#e6f7f2]'">
                    Pestañas & Cejas
                </button>
                <button type="button" @click="activeCategory = 'FACIAL'"
                        class="flex-shrink-0 px-3.5 py-1.5 rounded-full text-xs font-bold transition-all cursor-pointer"
                        :class="activeCategory === 'FACIAL' ? 'bg-[#0d9488] text-white shadow-md shadow-teal-500/20' : 'bg-white text-slate-600 border border-slate-200/80 hover:bg-[#e6f7f2]'">
                    Cuidado Facial
                </button>
                <button type="button" @click="activeCategory = 'LABIOS'"
                        class="flex-shrink-0 px-3.5 py-1.5 rounded-full text-xs font-bold transition-all cursor-pointer"
                        :class="activeCategory === 'LABIOS' ? 'bg-[#0d9488] text-white shadow-md shadow-teal-500/20' : 'bg-white text-slate-600 border border-slate-200/80 hover:bg-[#e6f7f2]'">
                    Labios
                </button>
                <button type="button" @click="activeCategory = 'CORPORAL_MASAJES'"
                        class="flex-shrink-0 px-3.5 py-1.5 rounded-full text-xs font-bold transition-all cursor-pointer"
                        :class="activeCategory === 'CORPORAL_MASAJES' ? 'bg-[#0d9488] text-white shadow-md shadow-teal-500/20' : 'bg-white text-slate-600 border border-slate-200/80 hover:bg-[#e6f7f2]'">
                    Masajes & Spa
                </button>
                <button type="button" @click="activeCategory = 'DEPILACION'"
                        class="flex-shrink-0 px-3.5 py-1.5 rounded-full text-xs font-bold transition-all cursor-pointer"
                        :class="activeCategory === 'DEPILACION' ? 'bg-[#0d9488] text-white shadow-md shadow-teal-500/20' : 'bg-white text-slate-600 border border-slate-200/80 hover:bg-[#e6f7f2]'">
                    Depilación
                </button>
            </div>
        </div>

        <!-- Modern Beauty Cards Grid (3 Columns on Desktop) -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($services as $service)
            <div x-show="matchesFilter('{{ $service->category->value }}', '{{ strtolower($service->name . ' ' . $service->description) }}')"
                 @click="selectService({{ $service->toJson() }})"
                 class="group rounded-3xl border transition-all duration-300 cursor-pointer bg-white overflow-hidden flex flex-col justify-between hover:shadow-xl hover:shadow-teal-500/10 hover:-translate-y-1 relative"
                 :class="selectedService?.id === {{ $service->id }}
                     ? 'border-[#0d9488] ring-2 ring-[#0d9488]/30 shadow-lg shadow-teal-200/60 bg-gradient-to-b from-[#e6f7f2]/40 to-white'
                     : 'border-slate-200/80 hover:border-[#e2f0ea]'">
                
                <!-- Selected Ribbon Badge -->
                <template x-if="selectedService?.id === {{ $service->id }}">
                    <div class="absolute top-3 right-3 z-20 px-3 py-1 rounded-full bg-[#0d9488] text-white text-[10px] font-bold uppercase tracking-wider shadow-md flex items-center gap-1">
                        <i data-lucide="check" class="w-3 h-3"></i>
                        <span>Elegido</span>
                    </div>
                </template>

                <div>
                    <!-- Image with Overlay & Floating Badges -->
                    <div class="relative h-44 sm:h-48 w-full overflow-hidden bg-[#e6f7f2]">
                        <img src="{{ $service->image_url }}" alt="{{ $service->name }}"
                             class="w-full h-full object-cover object-center group-hover:scale-105 transition-transform duration-700 ease-out">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/60 via-transparent to-black/20"></div>

                        <!-- Category Pill (Top Left) -->
                        <span class="absolute top-3 left-3 px-2.5 py-1 rounded-full bg-white/90 backdrop-blur-md text-slate-800 text-[10px] font-extrabold uppercase tracking-wider shadow-sm">
                            {{ $service->category->label() }}
                        </span>

                        <!-- Duration Badge (Bottom Left) -->
                        <span class="absolute bottom-3 left-3 px-2.5 py-1 rounded-full bg-black/60 backdrop-blur-md text-white text-[11px] font-semibold flex items-center gap-1 shadow-sm">
                            <i data-lucide="clock" class="w-3 h-3 text-teal-300"></i>
                            <span>{{ $service->formatted_duration }}</span>
                        </span>
                    </div>

                    <!-- Content -->
                    <div class="p-4 sm:p-5">
                        <h4 class=" font-bold text-slate-900 text-base sm:text-lg leading-snug group-hover:text-[#0d9488] transition-colors">
                            {{ $service->name }}
                        </h4>
                        <p class="text-slate-500 text-xs mt-1.5 leading-relaxed line-clamp-2">
                            {{ $service->description }}
                        </p>

                        <!-- Deposit Breakdown Bar -->
                        <div class="mt-3.5 pt-3 border-t border-slate-100 flex items-center justify-between text-[11px]">
                            <div class="flex items-center gap-1 text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded-md font-bold border border-emerald-100">
                                <i data-lucide="shield-check" class="w-3 h-3 text-emerald-600"></i>
                                <span>Abono: ${{ number_format($service->deposit_amount, 0, ',', '.') }}</span>
                            </div>
                            <span class="text-slate-400 font-medium">
                                Saldo en local: ${{ number_format($service->base_price - $service->deposit_amount, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Price & CTA Footer -->
                <div class="px-4 sm:px-5 pb-4 sm:pb-5 pt-0 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Precio Total</span>
                        <span class="text-xl sm:text-2xl  font-bold text-slate-900 tracking-tight">
                            ${{ number_format($service->base_price, 0, ',', '.') }}
                        </span>
                    </div>

                    <button type="button"
                            class="px-5 py-2.5 rounded-full text-xs font-bold transition-all flex items-center gap-1.5 shadow-sm cursor-pointer"
                            :class="selectedService?.id === {{ $service->id }}
                                ? 'bg-[#0d9488] text-white shadow-teal-500/30'
                                : 'bg-[#e6f7f2] text-[#1e3a5f] hover:bg-[#0d9488] hover:text-white'">
                        <span x-text="selectedService?.id === {{ $service->id }} ? 'Continuar' : 'Agendar Cita'"></span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                    </button>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- STEP 2: SELECT DATE & SINGLE NEXT CONTIGUOUS TIME SLOT -->
    <div x-show="step === 2" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class=" font-bold text-xl sm:text-2xl text-slate-900 tracking-tight">2. Selecciona Fecha</h3>
                <p class="text-xs text-[#1e3a5f] font-semibold mt-0.5" x-text="'Tratamiento: ' + (selectedService ? selectedService.name : '')"></p>
            </div>
            <button @click="goToStep(1)" class="text-xs font-bold text-[#0d9488] hover:underline flex items-center gap-1 cursor-pointer">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                <span>Cambiar servicio</span>
            </button>
        </div>

        <!-- Date Picker Pills -->
        <div class="mb-6">
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2.5">Día de tu Cita</label>
            <div class="flex items-center gap-2.5 overflow-x-auto pb-2.5 scrollbar-none">
                <template x-for="day in availableDays" :key="day.date">
                    <button type="button"
                            @click="selectDate(day.date)"
                            :disabled="!day.is_open"
                            class="flex-shrink-0 w-20 py-3 px-2 rounded-2xl border text-center transition-all duration-200 cursor-pointer"
                            :class="selectedDate === day.date
                                ? 'border-[#0d9488] bg-[#0d9488] text-white shadow-md shadow-teal-500/25 ring-2 ring-[#0d9488]/20'
                                : (day.is_open ? 'border-slate-200 bg-white hover:border-[#99f6e4] text-slate-800 shadow-sm' : 'opacity-40 bg-slate-50 border-slate-200 text-slate-400 cursor-not-allowed')">
                        <span class="block text-[11px] font-bold uppercase tracking-wider" x-text="day.day_short"></span>
                        <span class="block text-xl font-bold leading-tight  my-0.5" x-text="day.day_number"></span>
                        <span class="block text-[9px] font-semibold" :class="selectedDate === day.date ? 'text-teal-100' : 'text-slate-400'" x-text="day.is_open ? 'Abierto' : 'Cerrado'"></span>
                    </button>
                </template>
            </div>
        </div>

        <!-- Single Next Available Slot Presentation -->
        <div>
            <div class="flex items-center justify-between mb-3">
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Horario Disponible Inmediato</label>
                <span class="text-[11px] text-emerald-800 bg-emerald-50 px-2.5 py-0.5 rounded-full font-bold border border-emerald-200">
                    ⏱️ Duración: <span x-text="selectedService?.formatted_duration || (selectedService?.duration_minutes + ' min')"></span>
                </span>
            </div>

            <!-- Loading Spinner -->
            <div x-show="loadingSlots" class="text-center py-10">
                <div class="w-8 h-8 border-3 border-[#0d9488] border-t-transparent rounded-full animate-spin mx-auto mb-2"></div>
                <p class="text-xs text-slate-500 font-medium">Calculando el próximo turno disponible...</p>
            </div>

            <!-- Single Slot Card (Only 1 Next Available Slot) -->
            <div x-show="!loadingSlots && selectedSlot" class="max-w-lg mx-auto my-3">
                <div class="p-6 sm:p-7 rounded-3xl bg-gradient-to-b from-[#e6f7f2]/60 to-white border-2 border-[#0d9488] shadow-xl shadow-teal-200/50 text-center relative overflow-hidden ring-4 ring-[#e6f7f2]">
                    <div class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full text-[10px] font-extrabold bg-[#0d9488] text-white uppercase tracking-wider shadow-sm mb-3">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                        <span>Único Turno Inmediato Disponible</span>
                    </div>

                    <div class="text-3xl sm:text-4xl  font-bold text-slate-900 tracking-tight my-1.5" x-text="selectedSlot?.start_formatted"></div>
                    
                    <div class="flex items-center justify-center gap-2 text-xs text-slate-600 font-medium my-2.5">
                        <span class="bg-[#e6f7f2] text-[#1e3a5f] px-3.5 py-1 rounded-full font-bold border border-[#e2f0ea]">
                            Finaliza a las <span x-text="selectedSlot?.end_formatted"></span>
                        </span>
                    </div>

                    <p class="text-[11px] text-slate-500 mt-2 leading-relaxed max-w-sm mx-auto">
                        Horario habilitado inmediatamente después del último servicio agendado para optimizar la jornada de Paola.
                    </p>
                </div>
            </div>

            <!-- Empty State / Waitlist Offer -->
            <div x-show="!loadingSlots && !selectedSlot" class="text-center py-8 px-4 rounded-3xl bg-[#e6f7f2]/50 border border-[#e2f0ea]">
                <div class="w-11 h-11 rounded-full bg-[#e6f7f2] text-[#0d9488] mx-auto flex items-center justify-center mb-2.5">
                    <i data-lucide="calendar-x" class="w-5 h-5"></i>
                </div>
                <h4 class=" font-bold text-slate-900 text-base">No hay turnos disponibles para esta fecha</h4>
                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto leading-relaxed">La agenda para este día está completa o coincide con el horario de almuerzo de Paola.</p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-2 mt-4">
                    <button type="button" @click="openWaitlistModal()" class="px-5 py-2.5 rounded-full text-xs font-bold bg-[#0d9488] text-white hover:bg-[#0f766e] shadow-md shadow-teal-500/20 transition-all cursor-pointer">
                        🙋‍♀️ Unirme a la Lista de Espera
                    </button>
                </div>
            </div>
        </div>

        <!-- Continue Button -->
        <div class="mt-8 flex justify-end">
            <button type="button"
                    @click="goToStep(3)"
                    :disabled="!selectedSlot"
                    class="px-8 py-3.5 rounded-full text-xs font-bold transition-all duration-200 flex items-center gap-2 tracking-wide cursor-pointer shadow-md"
                    :class="selectedSlot ? 'bg-gradient-to-r from-[#0d9488] to-[#0f766e] text-white hover:from-[#0f766e] hover:to-[#115e59] shadow-teal-500/25 hover:scale-[1.02]' : 'bg-slate-200 text-slate-400 cursor-not-allowed shadow-none'">
                <span>Continuar a Mis Datos</span>
                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
            </button>
        </div>
    </div>

    <!-- STEP 3: ENHANCED CLIENT DETAILS EXPERIENCE (AUTO-LOAD & PERSISTENT PROFILE) -->
    <div x-show="step === 3" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
        
        <!-- Section Header -->
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class=" font-bold text-xl sm:text-2xl text-slate-900 tracking-tight">3. Tus Datos de Contacto</h3>
                <p class="text-xs text-slate-500 font-medium mt-0.5">Ingresa tus datos para apartar tu turno y enviarte la confirmación por WhatsApp.</p>
            </div>
            <button @click="goToStep(2)" class="text-xs font-bold text-[#0d9488] hover:text-[#162d4a] flex items-center gap-1.5 transition-colors cursor-pointer">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                <span>Cambiar horario</span>
            </button>
        </div>

        <!-- Luxury Booking Summary Card -->
        <div class="p-4 sm:p-5 rounded-3xl bg-gradient-to-r from-[#e6f7f2]/90 via-[#e6f7f2]/50 to-white border border-[#e2f0ea] mb-6 shadow-sm relative overflow-hidden">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                
                <!-- Service & Schedule Info -->
                <div class="flex items-start sm:items-center gap-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-white text-[#0d9488] flex-shrink-0 flex items-center justify-center shadow-md border border-[#e2f0ea]">
                        <i data-lucide="sparkles" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h4 class=" font-bold text-slate-900 text-base sm:text-lg leading-tight" x-text="selectedService?.name"></h4>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-[#e6f7f2] text-[#1e3a5f] uppercase tracking-wider"
                                  x-text="selectedService?.formatted_duration || (selectedService?.duration_minutes + ' min')"></span>
                        </div>
                        
                        <!-- Formatted Spanish Date & Time -->
                        <div class="flex items-center gap-2 mt-1.5 text-xs text-slate-600 font-medium flex-wrap">
                            <span class="inline-flex items-center gap-1 font-semibold text-slate-800 bg-white/80 px-2.5 py-1 rounded-lg border border-[#e2f0ea] shadow-xs">
                                <i data-lucide="calendar" class="w-3.5 h-3.5 text-[#0d9488]"></i>
                                <span x-text="formatDisplayDate(selectedDate)"></span>
                            </span>
                            <span class="inline-flex items-center gap-1 font-bold text-[#162d4a] bg-[#e6f7f2] px-2.5 py-1 rounded-lg">
                                <i data-lucide="clock" class="w-3.5 h-3.5 text-[#0d9488]"></i>
                                <span x-text="selectedSlot?.start_formatted + ' a ' + selectedSlot?.end_formatted"></span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Financial Settlement Box -->
                <div class="flex md:flex-col items-center md:items-end justify-between md:justify-center border-t md:border-t-0 md:border-l border-[#e2f0ea] pt-3 md:pt-0 md:pl-5">
                    <div class="text-left md:text-right">
                        <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider block">Abono Requerido</span>
                        <span class=" font-bold text-[#1e3a5f] text-lg sm:text-xl" x-text="'$' + formatPrice(selectedService?.deposit_amount) + ' COP'"></span>
                    </div>
                    <span class="text-[11px] text-slate-500 font-medium block mt-0.5">
                        Saldo en local: <strong class="text-slate-700" x-text="'$' + formatPrice(selectedService?.base_price - selectedService?.deposit_amount) + ' COP'"></strong>
                    </span>
                </div>
            </div>
        </div>

        <!-- Saved Device Profile Banner (Zero-Click Booking Experience) -->
        <div x-show="hasSavedDeviceProfile" x-transition class="mb-5 p-4 rounded-3xl bg-gradient-to-r from-emerald-50 via-emerald-50/60 to-white border border-emerald-200/90 text-emerald-950 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-2xl bg-emerald-600 text-white flex items-center justify-center flex-shrink-0 shadow-sm">
                    <i data-lucide="user-check" class="w-5 h-5"></i>
                </div>
                <div>
                    <div class="flex items-center gap-1.5">
                        <span class="font-bold text-xs">¡Hola de nuevo, <span x-text="clientForm.name.split(' ')[0]"></span>! ✨</span>
                        <span class="text-[10px] font-extrabold uppercase tracking-wider bg-emerald-200/80 text-emerald-900 px-2 py-0.5 rounded-full">Perfil Recordado</span>
                    </div>
                    <p class="text-[11px] text-emerald-700 mt-0.5">Cargamos tus datos automáticamente desde este dispositivo para que agendes al instante.</p>
                </div>
            </div>
            <button type="button" @click="clearSavedProfile()" class="text-[11px] font-bold text-emerald-800 hover:text-emerald-950 underline self-start sm:self-center cursor-pointer flex-shrink-0">
                ¿No eres tú? Cambiar datos
            </button>
        </div>

        <!-- Returning Client Fast Auto-fill Search Bar (When typing phone from another device) -->
        <div x-show="!hasSavedDeviceProfile && !isReturningClient" x-transition class="mb-5 p-3.5 rounded-2xl bg-indigo-50/70 border border-indigo-100 text-indigo-950 text-xs flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2.5">
                <div class="w-6 h-6 rounded-lg bg-indigo-200/70 text-indigo-700 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="zap" class="w-3.5 h-3.5"></i>
                </div>
                <span>¿Ya eres clienta de Paola? Al ingresar tu WhatsApp autocompletaremos tus datos automáticamente.</span>
            </div>
            <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-700 bg-indigo-100/90 px-2 py-0.5 rounded-md flex-shrink-0">Autocompletar</span>
        </div>

        <!-- Main Form Fields Container -->
        <div class="bg-white p-6 sm:p-7 rounded-3xl border border-slate-200/80 shadow-sm space-y-5">
            
            <!-- WhatsApp / Celular (Primary Quick Recognition Field) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                            WhatsApp / Celular <span class="text-[#0d9488]">*</span>
                        </label>
                        <template x-if="cleanPhone(clientForm.phone).length === 10">
                            <span class="text-[11px] font-bold text-emerald-600 flex items-center gap-1">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i> WhatsApp listo
                            </span>
                        </template>
                    </div>
                    <div class="relative flex items-center">
                        <div class="absolute left-3.5 flex items-center gap-1 text-slate-600 pointer-events-none text-xs font-bold pr-2 border-r border-slate-200">
                            <span class="text-base">🇨🇴</span>
                            <span class="tracking-wider">+57</span>
                        </div>
                        <input type="tel"
                               x-model="clientForm.phone"
                               @input="handlePhoneInput($event)"
                               placeholder="310 000 0000"
                               maxlength="12"
                               class="w-full pl-20 pr-4 py-3 rounded-2xl border text-sm font-medium font-mono transition-all duration-200 focus:outline-none focus:ring-3 focus:ring-[#0d9488]/20"
                               :class="cleanPhone(clientForm.phone).length === 10 ? 'border-slate-300 focus:border-[#0d9488] bg-white' : 'border-slate-200 focus:border-[#0d9488] bg-slate-50/30'">
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1">Te enviaremos los recordatorios y la ubicación exacta a este WhatsApp.</p>
                </div>

                <!-- Full Name Input -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                            Nombre Completo <span class="text-[#0d9488]">*</span>
                        </label>
                        <template x-if="clientForm.name.trim().length >= 3">
                            <span class="text-[11px] font-bold text-emerald-600 flex items-center gap-1">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i> Válido
                            </span>
                        </template>
                    </div>
                    <input type="text"
                           x-model="clientForm.name"
                           @input="updateIcons()"
                           placeholder="Ej: Valentina Gómez Restrepo"
                           class="w-full px-4 py-3 rounded-2xl border text-sm font-medium transition-all duration-200 focus:outline-none focus:ring-3 focus:ring-[#0d9488]/20"
                           :class="clientForm.name.trim().length >= 3 ? 'border-slate-300 focus:border-[#0d9488] bg-white' : 'border-slate-200 focus:border-[#0d9488] bg-slate-50/30'">
                    <p class="text-[10px] text-slate-400 mt-1">Tu nombre para personalizar tu atención en cabina.</p>
                </div>
            </div>

            <!-- Optional Email Field -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                        Correo Electrónico <span class="text-slate-400 font-normal">(Opcional)</span>
                    </label>
                    <template x-if="isValidEmail(clientForm.email)">
                        <span class="text-[11px] font-bold text-emerald-600 flex items-center gap-1">
                            <i data-lucide="check" class="w-3.5 h-3.5"></i> Válido
                        </span>
                    </template>
                </div>
                <input type="email"
                       x-model="clientForm.email"
                       @input="updateIcons()"
                       placeholder="tucorreo@gmail.com"
                       class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm font-medium transition-all duration-200 focus:outline-none focus:ring-3 focus:ring-[#0d9488]/20 focus:border-[#0d9488] bg-slate-50/30">
                <p class="text-[10px] text-slate-400 mt-1">Para recibir tu recibo digital y sincronizar con Google Calendar / Apple Calendar.</p>
            </div>

            <!-- Notes / Preferences with Quick Tag Chips -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Notas Especiales / Preferencias <span class="text-slate-400 font-normal">(Opcional)</span>
                </label>
                <textarea x-model="clientForm.notes"
                          rows="2"
                          placeholder="Ej: Tengo piel sensible con tendencia a rojez, primera vez que me realizo pestañas..."
                          class="w-full px-4 py-2.5 rounded-2xl border border-slate-200 text-sm font-medium focus:outline-none focus:ring-3 focus:ring-[#0d9488]/20 focus:border-[#0d9488] bg-slate-50/30"></textarea>
                
                <!-- Quick Suggestion Tag Chips -->
                <div class="mt-2 flex items-center gap-1.5 flex-wrap">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mr-1">Sugerencias rápidas:</span>
                    <button type="button" @click="appendNoteTag('✨ Primera vez con Paola')"
                            class="px-2.5 py-1 rounded-full text-[11px] font-medium bg-[#e6f7f2] text-[#1e3a5f] hover:bg-[#0d9488] hover:text-white transition-all border border-[#e2f0ea] cursor-pointer">
                        ✨ Primera vez
                    </button>
                    <button type="button" @click="appendNoteTag('🌿 Piel sensible / Alergias')"
                            class="px-2.5 py-1 rounded-full text-[11px] font-medium bg-[#e6f7f2] text-[#1e3a5f] hover:bg-[#0d9488] hover:text-white transition-all border border-[#e2f0ea] cursor-pointer">
                        🌿 Piel sensible
                    </button>
                    <button type="button" @click="appendNoteTag('👁️ Retiro de pestañas previas')"
                            class="px-2.5 py-1 rounded-full text-[11px] font-medium bg-[#e6f7f2] text-[#1e3a5f] hover:bg-[#0d9488] hover:text-white transition-all border border-[#e2f0ea] cursor-pointer">
                        👁️ Retiro de pestañas
                    </button>
                    <button type="button" @click="appendNoteTag('⏰ Salir antes de cierta hora')"
                            class="px-2.5 py-1 rounded-full text-[11px] font-medium bg-[#e6f7f2] text-[#1e3a5f] hover:bg-[#0d9488] hover:text-white transition-all border border-[#e2f0ea] cursor-pointer">
                        ⏰ Tiempo justo
                    </button>
                </div>
            </div>

            <!-- Remember Me Switch -->
            <div class="pt-2 border-t border-slate-100 flex items-center gap-2.5">
                <input type="checkbox" id="rememberMe" x-model="rememberMe" class="w-4 h-4 text-[#0d9488] rounded border-slate-300 focus:ring-[#0d9488] cursor-pointer">
                <label for="rememberMe" class="text-xs text-slate-600 font-medium cursor-pointer select-none">
                    Guardar mis datos en este dispositivo para futuras reservas rápidas (1-Click)
                </label>
            </div>
        </div>

        <!-- Trust Badges / Peace of Mind Section -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 my-6">
            <div class="p-3.5 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="shield-check" class="w-4 h-4"></i>
                </div>
                <div>
                    <h5 class="text-xs font-bold text-slate-900 leading-tight">Privacidad 100%</h5>
                    <p class="text-[10px] text-slate-500 mt-0.5">Tus datos están protegidos.</p>
                </div>
            </div>

            <div class="p-3.5 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="message-square" class="w-4 h-4"></i>
                </div>
                <div>
                    <h5 class="text-xs font-bold text-slate-900 leading-tight">Recordatorio WhatsApp</h5>
                    <p class="text-[10px] text-slate-500 mt-0.5">Aviso 24h antes de tu cita.</p>
                </div>
            </div>

            <div class="p-3.5 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="lock" class="w-4 h-4"></i>
                </div>
                <div>
                    <h5 class="text-xs font-bold text-slate-900 leading-tight">Cupo Exclusivo</h5>
                    <p class="text-[10px] text-slate-500 mt-0.5">Horario apartado para ti.</p>
                </div>
            </div>
        </div>

        <!-- Buttons / Navigation Action Bar -->
        <div class="mt-6 flex flex-col-reverse sm:flex-row justify-between items-center gap-3">
            <button type="button" @click="goToStep(2)" class="text-xs font-bold text-slate-500 hover:text-slate-800 flex items-center gap-1.5 py-2.5 px-4 rounded-full hover:bg-slate-100 transition-colors cursor-pointer">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                <span>Volver a Horarios</span>
            </button>
            
            <button type="button"
                    @click="holdSlotAndGoToPayment()"
                    :disabled="!isClientFormValid() || holdingSlot"
                    class="w-full sm:w-auto px-8 py-3.5 rounded-full text-xs font-bold transition-all duration-200 flex items-center justify-center gap-2 tracking-wide cursor-pointer shadow-md"
                    :class="isClientFormValid() && !holdingSlot
                        ? 'bg-gradient-to-r from-[#0d9488] to-[#0f766e] text-white hover:from-[#0f766e] hover:to-[#115e59] shadow-teal-500/30 hover:scale-[1.02]'
                        : 'bg-slate-200 text-slate-400 cursor-not-allowed shadow-none'">
                <template x-if="holdingSlot">
                    <span class="flex items-center gap-2">
                        <div class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                        <span>Apartando cupo seguro...</span>
                    </span>
                </template>
                <template x-if="!holdingSlot">
                    <span class="flex items-center gap-2">
                        <span x-text="isClientFormValid() ? ('Continuar al Pago del Abono ($' + formatPrice(selectedService?.deposit_amount) + ' COP)') : 'Completa tu Nombre y WhatsApp para continuar'"></span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                    </span>
                </template>
            </button>
        </div>
    </div>

    <!-- STEP 4: HYBRID PAYMENT (NEQUI / BOLD) -->
    <div x-show="step === 4" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class=" font-bold text-xl sm:text-2xl text-slate-900 tracking-tight">4. Método de Abono</h3>
                <p class="text-xs text-slate-500 font-medium">Asegura tu cita con el pago del anticipo</p>
            </div>
            <span class="text-[11px] font-bold text-amber-800 bg-amber-50 border border-amber-200 px-3 py-1 rounded-full flex items-center gap-1 shadow-sm">
                <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                <span>Cupo apartado por 15 min</span>
            </span>
        </div>

        <!-- Settlement Breakdown -->
        <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm mb-6">
            <div class="flex justify-between items-center text-xs text-slate-600 pb-2.5 border-b border-slate-100">
                <span class="font-medium">Valor Total del Servicio</span>
                <span class="font-bold text-slate-900  text-base" x-text="'$' + formatPrice(selectedService?.base_price) + ' COP'"></span>
            </div>
            <div class="flex justify-between items-center text-xs text-[#1e3a5f] font-bold py-2.5 border-b border-slate-100">
                <span>Monto de Abono a Pagar Hoy</span>
                <span class=" text-lg text-[#0d9488]" x-text="'$' + formatPrice(selectedService?.deposit_amount) + ' COP'"></span>
            </div>
            <div class="flex justify-between items-center text-xs text-slate-500 pt-2.5">
                <span class="font-medium">Saldo Restante a Pagar en el Local</span>
                <span class="font-bold text-slate-800  text-sm" x-text="'$' + formatPrice(selectedService?.base_price - selectedService?.deposit_amount) + ' COP'"></span>
            </div>
        </div>

        <!-- Payment Method Tabs -->
        <div class="grid grid-cols-2 gap-3 mb-6">
            <button type="button" @click="paymentMethod = 'NEQUI_TRANSFER'"
                    class="p-4 rounded-2xl border text-left transition-all duration-200 cursor-pointer"
                    :class="paymentMethod === 'NEQUI_TRANSFER' ? 'border-[#0d9488] bg-[#e6f7f2]/50 ring-2 ring-[#0d9488]/20 shadow-sm' : 'border-slate-200 bg-white hover:border-slate-300'">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-bold text-slate-900">📲 Nequi / Daviplata</span>
                    <span class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 uppercase tracking-wider">0% Comisión</span>
                </div>
                <p class="text-[11px] text-slate-500 leading-tight">Transferencia directa y subida de comprobante</p>
            </button>

            <button type="button" @click="paymentMethod = 'BOLD_ONLINE'"
                    class="p-4 rounded-2xl border text-left transition-all duration-200 cursor-pointer"
                    :class="paymentMethod === 'BOLD_ONLINE' ? 'border-[#0d9488] bg-[#e6f7f2]/50 ring-2 ring-[#0d9488]/20 shadow-sm' : 'border-slate-200 bg-white hover:border-slate-300'">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-bold text-slate-900">💳 Pago en Línea (Bold)</span>
                </div>
                <p class="text-[11px] text-slate-500 leading-tight">PSE, Tarjetas Débito y Crédito</p>
            </button>
        </div>

        <!-- NEQUI TRANSFER INSTRUCTIONS & RECEIPT UPLOAD -->
        <div x-show="paymentMethod === 'NEQUI_TRANSFER'" class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm space-y-4">
            <div class="p-4 rounded-2xl bg-purple-50/80 border border-purple-100">
                <h5 class="text-xs font-bold text-purple-900 mb-1">Datos para la Transferencia Nequi:</h5>
                <p class="text-xs text-purple-800"><span class="font-semibold">Titular:</span> {{ $nequiConfig['holder_name'] }}</p>
                <div class="flex items-center justify-between mt-2 pt-2 border-t border-purple-200/60">
                    <span class="text-base font-bold text-purple-950 font-mono tracking-widest">{{ $nequiConfig['account_number'] }}</span>
                    <button type="button" @click="copyNequiNumber('{{ $nequiConfig['account_number'] }}')"
                            class="text-[11px] font-bold px-3 py-1 rounded-full bg-purple-200 hover:bg-purple-300 text-purple-900 transition-colors shadow-sm cursor-pointer">
                        <span x-text="copied ? '¡Copiado! ✓' : 'Copiar Número'"></span>
                    </button>
                </div>
            </div>

            <!-- Receipt File Input -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Cargar Comprobante de Transferencia *</label>
                <input type="file" @change="handleFileUpload($event)" accept="image/*"
                       class="w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-[#e6f7f2] file:text-[#1e3a5f] hover:file:bg-[#ccfbf1] cursor-pointer border border-slate-200 rounded-2xl p-2 bg-slate-50/50">
                <p class="text-[10px] text-slate-400 mt-1.5">Adjunta una foto o captura de pantalla donde se vea claramente el valor y número de aprobación.</p>
            </div>

            <button type="button"
                    @click="submitNequiBooking()"
                    :disabled="submittingBooking"
                    class="w-full py-3.5 rounded-full text-xs font-bold bg-[#0d9488] text-white hover:bg-[#0f766e] shadow-md shadow-teal-500/25 transition-all flex items-center justify-center gap-2 tracking-wide cursor-pointer">
                <template x-if="submittingBooking">
                    <span>Registrando y subiendo comprobante...</span>
                </template>
                <template x-if="!submittingBooking">
                    <span>✓ Confirmar Cita con Comprobante</span>
                </template>
            </button>
        </div>

        <!-- BOLD ONLINE PAYMENT BUTTON -->
        <div x-show="paymentMethod === 'BOLD_ONLINE'" class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm text-center space-y-4">
            <div class="w-12 h-12 rounded-full bg-[#e6f7f2] text-[#0d9488] mx-auto flex items-center justify-center shadow-inner">
                <i data-lucide="credit-card" class="w-6 h-6"></i>
            </div>
            <div>
                <h5 class="text-sm font-bold text-slate-900 ">Paga de forma rápida y segura</h5>
                <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1 leading-relaxed">Serás redirigida a la pasarela segura de Bold para pagar con PSE, Nequi o Tarjeta.</p>
            </div>

            <button type="button"
                    @click="submitBoldBooking()"
                    :disabled="submittingBooking"
                    class="w-full py-3.5 rounded-full text-xs font-bold bg-slate-900 text-white hover:bg-slate-800 shadow-md transition-all flex items-center justify-center gap-2 tracking-wide cursor-pointer">
                <template x-if="submittingBooking">
                    <span>Generando pasarela de pago...</span>
                </template>
                <template x-if="!submittingBooking">
                    <span>💳 Pagar en Línea con Bold</span>
                </template>
            </button>
        </div>
    </div>

    <!-- WAITLIST MODAL -->
    <div x-show="showWaitlistModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl relative" @click.outside="showWaitlistModal = false">
            <div class="flex items-center justify-between mb-4">
                <h4 class=" font-bold text-lg text-slate-900 tracking-tight">Lista de Espera Inteligente</h4>
                <button type="button" @click="showWaitlistModal = false" class="text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <p class="text-xs text-slate-500 mb-4 leading-relaxed">Si una clienta cancela o reprograma su cita, te contactaremos de inmediato por WhatsApp.</p>

            <div class="space-y-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Tu Nombre</label>
                    <input type="text" x-model="waitlistForm.name" placeholder="Tu nombre completo" class="w-full px-3.5 py-2.5 border rounded-xl text-xs font-medium">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">WhatsApp</label>
                    <input type="tel" x-model="waitlistForm.phone" placeholder="310 000 0000" class="w-full px-3.5 py-2.5 border rounded-xl text-xs font-medium font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Franja Horaria Preferida</label>
                    <select x-model="waitlistForm.time_range" class="w-full px-3.5 py-2.5 border rounded-xl text-xs font-medium">
                        <option value="anytime">Cualquier momento del día</option>
                        <option value="morning">Mañana (8:00 AM - 1:00 PM)</option>
                        <option value="afternoon">Tarde (2:00 PM - 6:00 PM)</option>
                    </select>
                </div>
                <button type="button" @click="submitWaitlist()" class="w-full py-3 rounded-full text-xs font-bold bg-[#0d9488] text-white mt-4 hover:bg-[#0f766e] shadow-md shadow-teal-500/20 tracking-wide cursor-pointer">
                    Anotarme a la Lista de Espera
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function bookingApp() {
    return {
        step: 1,
        activeCategory: 'ALL',
        searchQuery: '',
        selectedService: null,
        selectedDate: null,
        selectedSlot: null,
        slots: [],
        loadingSlots: false,
        paymentMethod: 'NEQUI_TRANSFER',
        receiptFile: null,
        copied: false,
        holdingSlot: false,
        submittingBooking: false,
        appointmentId: null,
        showWaitlistModal: false,
        availableDays: [],
        isReturningClient: false,
        hasSavedDeviceProfile: false,
        rememberMe: true,

        clientForm: {
            name: '',
            phone: '',
            email: '',
            notes: ''
        },

        waitlistForm: {
            name: '',
            phone: '',
            time_range: 'anytime'
        },

        init() {
            this.generateUpcomingDays();
            this.loadProfileFromLocalStorage();
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        loadProfileFromLocalStorage() {
            try {
                const saved = localStorage.getItem('nuvex_paola_client_profile');
                if (saved) {
                    const data = JSON.parse(saved);
                    if (data.name && data.phone) {
                        this.clientForm.name = data.name;
                        this.clientForm.phone = this.formatPhoneNumber(data.phone);
                        this.clientForm.email = data.email || '';
                        this.hasSavedDeviceProfile = true;
                    }
                }
            } catch (e) {
                console.warn('LocalStorage error', e);
            }
        },

        saveProfileToLocalStorage() {
            if (!this.rememberMe) return;
            try {
                const profile = {
                    name: this.clientForm.name.trim(),
                    phone: this.cleanPhone(this.clientForm.phone),
                    email: this.clientForm.email ? this.clientForm.email.trim() : ''
                };
                localStorage.setItem('nuvex_paola_client_profile', JSON.stringify(profile));
                this.hasSavedDeviceProfile = true;
            } catch (e) {
                console.warn('LocalStorage save error', e);
            }
        },

        clearSavedProfile() {
            try {
                localStorage.removeItem('nuvex_paola_client_profile');
            } catch (e) {}
            this.clientForm.name = '';
            this.clientForm.phone = '';
            this.clientForm.email = '';
            this.clientForm.notes = '';
            this.hasSavedDeviceProfile = false;
            this.isReturningClient = false;
            this.updateIcons();
        },

        goToStep(targetStep) {
            this.step = targetStep;
            this.updateIcons();
        },

        updateIcons() {
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        matchesFilter(category, text) {
            const matchesCat = (this.activeCategory === 'ALL' || this.activeCategory === category);
            const matchesQuery = (!this.searchQuery || text.includes(this.searchQuery.toLowerCase()));
            return matchesCat && matchesQuery;
        },

        generateUpcomingDays() {
            const days = [];
            const dayNames = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
            const today = new Date();

            for (let i = 0; i < 14; i++) {
                const d = new Date();
                d.setDate(today.getDate() + i);
                const isSunday = d.getDay() === 0;
                const year = d.getFullYear();
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const dayNum = String(d.getDate()).padStart(2, '0');
                const dateStr = `${year}-${month}-${dayNum}`;

                days.push({
                    date: dateStr,
                    day_short: dayNames[d.getDay()],
                    day_number: d.getDate(),
                    is_open: !isSunday
                });
            }
            this.availableDays = days;
        },

        selectService(service) {
            this.selectedService = service;
            this.goToStep(2);
            if (!this.selectedDate) {
                const firstOpen = this.availableDays.find(d => d.is_open);
                if (firstOpen) this.selectDate(firstOpen.date);
            } else {
                this.fetchSlots();
            }
        },

        selectDate(date) {
            this.selectedDate = date;
            this.selectedSlot = null;
            this.fetchSlots();
        },

        async fetchSlots() {
            if (!this.selectedService || !this.selectedDate) return;
            this.loadingSlots = true;
            this.selectedSlot = null;
            try {
                const res = await fetch(`/api/v1/availability/slots?service_id=${this.selectedService.id}&date=${this.selectedDate}`);
                const data = await res.json();
                if (data.success && data.data.slots && data.data.slots.length > 0) {
                    // Para el usuario final: SOLO se muestra la única opción siguiente al último servicio
                    const nextImmediateSlot = data.data.slots[0];
                    this.slots = [nextImmediateSlot];
                    this.selectedSlot = nextImmediateSlot;
                } else {
                    this.slots = [];
                    this.selectedSlot = null;
                }
            } catch (err) {
                console.error(err);
                this.slots = [];
                this.selectedSlot = null;
            } finally {
                this.loadingSlots = false;
                this.updateIcons();
            }
        },

        selectSlot(slot) {
            this.selectedSlot = slot;
        },

        cleanPhone(phone) {
            return (phone || '').replace(/[^0-9]/g, '');
        },

        formatPhoneNumber(digits) {
            digits = (digits || '').replace(/[^0-9]/g, '');
            if (digits.length > 10) digits = digits.substring(0, 10);
            if (digits.length > 6) {
                return `${digits.substring(0, 3)} ${digits.substring(3, 6)} ${digits.substring(6)}`;
            } else if (digits.length > 3) {
                return `${digits.substring(0, 3)} ${digits.substring(3)}`;
            }
            return digits;
        },

        handlePhoneInput(event) {
            let digits = event.target.value.replace(/[^0-9]/g, '');
            if (digits.length > 10) digits = digits.substring(0, 10);

            this.clientForm.phone = this.formatPhoneNumber(digits);

            // If 10 digits reached, trigger automatic returning client lookup from server
            if (digits.length === 10) {
                this.lookupClientByPhone(digits);
            }

            this.updateIcons();
        },

        async lookupClientByPhone(phone) {
            try {
                const res = await fetch(`/api/v1/clients/lookup?phone=${phone}`);
                const data = await res.json();
                if (data.success && data.data) {
                    if (!this.clientForm.name) this.clientForm.name = data.data.name;
                    if (!this.clientForm.email && data.data.email) this.clientForm.email = data.data.email;
                    this.isReturningClient = true;
                    this.saveProfileToLocalStorage();
                }
            } catch (e) {
                console.warn('Client lookup skipped', e);
            }
            this.updateIcons();
        },

        appendNoteTag(tag) {
            if (!this.clientForm.notes) {
                this.clientForm.notes = tag;
            } else if (!this.clientForm.notes.includes(tag)) {
                this.clientForm.notes += ' • ' + tag;
            }
        },

        isValidEmail(email) {
            if (!email) return false;
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        },

        isClientFormValid() {
            const hasName = this.clientForm.name && this.clientForm.name.trim().length >= 3;
            const hasPhone = this.cleanPhone(this.clientForm.phone).length === 10;
            return hasName && hasPhone;
        },

        formatDisplayDate(dateStr) {
            if (!dateStr) return '';
            try {
                const parts = dateStr.split('-');
                if (parts.length !== 3) return dateStr;
                const d = new Date(parts[0], parts[1] - 1, parts[2]);
                const dayNames = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
                const monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
                return `${dayNames[d.getDay()]}, ${d.getDate()} de ${monthNames[d.getMonth()]} de ${d.getFullYear()}`;
            } catch (e) {
                return dateStr;
            }
        },

        async holdSlotAndGoToPayment() {
            if (!this.isClientFormValid()) return;

            this.holdingSlot = true;
            this.saveProfileToLocalStorage();

            try {
                const res = await fetch('/api/v1/appointments/hold', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({
                        service_id: this.selectedService.id,
                        date: this.selectedDate,
                        start_time: this.selectedSlot.start_time,
                        client_name: this.clientForm.name.trim(),
                        client_phone: this.cleanPhone(this.clientForm.phone),
                        client_email: this.clientForm.email ? this.clientForm.email.trim() : null
                    })
                });
                const data = await res.json();
                if (data.success) {
                    this.appointmentId = data.data.appointment_id;
                    this.goToStep(4);
                } else {
                    alert(data.message || 'El horario ya no está disponible.');
                    this.fetchSlots();
                }
            } catch (err) {
                console.error(err);
                alert('Error al apartar el cupo.');
            } finally {
                this.holdingSlot = false;
            }
        },

        handleFileUpload(event) {
            this.receiptFile = event.target.files[0];
        },

        copyNequiNumber(number) {
            navigator.clipboard.writeText(number);
            this.copied = true;
            setTimeout(() => this.copied = false, 2500);
        },

        async submitNequiBooking() {
            this.submittingBooking = true;
            this.saveProfileToLocalStorage();

            const formData = new FormData();
            formData.append('appointment_id', this.appointmentId);
            formData.append('client_name', this.clientForm.name.trim());
            formData.append('client_phone', this.cleanPhone(this.clientForm.phone));
            formData.append('client_email', this.clientForm.email ? this.clientForm.email.trim() : '');
            formData.append('client_notes', this.clientForm.notes || '');
            formData.append('payment_method', 'NEQUI_TRANSFER');
            if (this.receiptFile) {
                formData.append('receipt', this.receiptFile);
            }

            try {
                const res = await fetch('/api/v1/appointments/book', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    window.location.href = `/reserva/confirmacion/${data.data.appointment_number}`;
                } else {
                    alert(data.message || 'Error al confirmar la cita.');
                }
            } catch (err) {
                console.error(err);
                alert('Error al enviar la reserva.');
            } finally {
                this.submittingBooking = false;
            }
        },

        async submitBoldBooking() {
            this.submittingBooking = true;
            this.saveProfileToLocalStorage();

            try {
                const res = await fetch('/api/v1/payments/bold/checkout', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ appointment_id: this.appointmentId })
                });
                const data = await res.json();
                if (data.success && data.data.checkout_url) {
                    window.location.href = data.data.checkout_url;
                } else {
                    alert('Error al generar enlace de Bold.');
                }
            } catch (err) {
                console.error(err);
                alert('Error al conectar con Bold.');
            } finally {
                this.submittingBooking = false;
            }
        },

        openWaitlistModal() {
            this.waitlistForm.name = this.clientForm.name;
            this.waitlistForm.phone = this.cleanPhone(this.clientForm.phone);
            this.showWaitlistModal = true;
            this.updateIcons();
        },

        async submitWaitlist() {
            if (!this.waitlistForm.name || !this.waitlistForm.phone) {
                alert('Por favor ingresa tu nombre y celular.');
                return;
            }
            try {
                const res = await fetch('/api/v1/waitlist/join', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({
                        service_id: this.selectedService.id,
                        client_name: this.waitlistForm.name,
                        client_phone: this.cleanPhone(this.waitlistForm.phone),
                        requested_date: this.selectedDate,
                        preferred_time_range: this.waitlistForm.time_range
                    })
                });
                const data = await res.json();
                if (data.success) {
                    alert(data.message);
                    this.showWaitlistModal = false;
                }
            } catch (err) {
                console.error(err);
                alert('Error al unirse a la lista de espera.');
            }
        },

        formatPrice(val) {
            if (!val) return '0';
            return Number(val).toLocaleString('es-CO');
        }
    }
}
</script>
@endpush
@endsection
