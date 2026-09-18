@extends('driver.layout')

@section('title', 'Hoja de Ruta | Nuvex Domiciliarios')

@section('content')
<div class="space-y-4">

    <!-- Selector de Filtros Rápido -->
    <div class="bg-white p-2.5 rounded-2xl shadow-xs border border-slate-200">
        <div class="grid grid-cols-3 gap-1.5 mb-2">
            <a href="{{ route('driver.dashboard', ['date' => 'pending']) }}"
               class="text-center py-2 px-1 rounded-xl text-xs font-bold transition-all {{ $selectedDate === 'pending' ? 'bg-rose-600 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                ⚡ Pendientes ({{ $stats['total_pending_all'] }})
            </a>
            <a href="{{ route('driver.dashboard', ['date' => $today]) }}"
               class="text-center py-2 px-1 rounded-xl text-xs font-bold transition-all {{ $selectedDate === $today ? 'bg-rose-600 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                Hoy ({{ now()->format('d M') }})
            </a>
            <a href="{{ route('driver.dashboard', ['date' => $tomorrow]) }}"
               class="text-center py-2 px-1 rounded-xl text-xs font-bold transition-all {{ $selectedDate === $tomorrow ? 'bg-rose-600 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                Mañana ({{ now()->addDay()->format('d M') }})
            </a>
        </div>

        <div class="flex items-center justify-between gap-2 pt-1 border-t border-slate-100">
            <span class="text-[11px] text-slate-500 font-semibold px-1">Buscar otra fecha:</span>
            <form action="{{ route('driver.dashboard') }}" method="GET" class="relative">
                <input type="date" name="date" value="{{ in_array($selectedDate, ['pending', 'all']) ? '' : $selectedDate }}" onchange="this.form.submit()"
                       class="py-1 px-2.5 bg-slate-50 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-rose-500">
            </form>
        </div>
    </div>

    <!-- Barra de Métricas del Domiciliario -->
    <div class="grid grid-cols-3 gap-2">
        <div class="bg-white p-3 rounded-2xl shadow-xs border border-slate-200 text-center">
            <p class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">Mostrando</p>
            <p class="text-xl font-extrabold text-slate-900 mt-0.5">{{ $stats['total'] }}</p>
        </div>
        <div class="bg-amber-50 p-3 rounded-2xl shadow-xs border border-amber-200 text-center">
            <p class="text-[10px] uppercase font-bold text-amber-700 tracking-wider">Por Entregar</p>
            <p class="text-xl font-extrabold text-amber-900 mt-0.5">{{ $stats['pending'] }}</p>
        </div>
        <div class="bg-emerald-50 p-3 rounded-2xl shadow-xs border border-emerald-200 text-center">
            <p class="text-[10px] uppercase font-bold text-emerald-700 tracking-wider">Entregados</p>
            <p class="text-xl font-extrabold text-emerald-900 mt-0.5">{{ $stats['completed'] }}</p>
        </div>
    </div>

    <!-- Listado de Entregas -->
    <div class="space-y-3">
        <div class="flex items-center justify-between px-1">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-600">
                @if($selectedDate === 'pending')
                    📌 Todos tus pedidos pendientes por entregar
                @elseif($selectedDate === 'all')
                    📋 Historial de todos los pedidos asignados
                @else
                    📅 Pedidos del {{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('l, d \d\e F') }}
                @endif
            </h2>
            @if($selectedDate !== 'pending' && $stats['total_pending_all'] > 0)
                <a href="{{ route('driver.dashboard', ['date' => 'pending']) }}" class="text-[11px] text-rose-600 font-bold hover:underline">
                    Ver todos ({{ $stats['total_pending_all'] }})
                </a>
            @endif
        </div>

        @forelse ($orders as $order)
            <div class="bg-white rounded-2xl shadow-xs border border-slate-200 overflow-hidden transition-all {{ $order->status->value === 'DELIVERED' ? 'opacity-80 bg-slate-50' : 'border-l-4 border-l-rose-500' }}">
                <!-- Cabecera de la orden -->
                <div class="p-3.5 border-b border-slate-100 flex items-center justify-between flex-wrap gap-2">
                    <div class="flex items-center space-x-2">
                        <span class="font-extrabold text-slate-900 text-sm">#{{ $order->order_number }}</span>
                        <span class="px-2 py-0.5 bg-rose-50 text-rose-700 font-bold rounded-lg text-[11px] border border-rose-200">
                            📅 {{ \Carbon\Carbon::parse($order->delivery_date)->format('d/m/Y') }}
                        </span>
                    </div>

                    <!-- Estado Badge -->
                    <div>
                        @if ($order->status->value === 'DELIVERED')
                            <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 font-bold rounded-full text-xs flex items-center space-x-1">
                                <span>🎉 Entregado</span>
                            </span>
                        @elseif ($order->status->value === 'IN_TRANSIT')
                            <span class="px-2.5 py-1 bg-rose-100 text-rose-800 font-bold rounded-full text-xs animate-pulse flex items-center space-x-1">
                                <span>🛵 En Ruta</span>
                            </span>
                        @else
                            <span class="px-2.5 py-1 bg-amber-100 text-amber-800 font-bold rounded-full text-xs">
                                <span>📦 Por Despachar</span>
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Franja Horaria -->
                <div class="bg-slate-50 px-3.5 py-1.5 border-b border-slate-100 flex items-center justify-between text-xs">
                    <span class="text-slate-500 font-medium">Franja de Entrega:</span>
                    <strong class="text-rose-700 font-bold">⏰ {{ $order->time_slot_name }}</strong>
                </div>

                <!-- Cuerpo de Información -->
                <div class="p-4 space-y-3">
                    <!-- Destinatario & Teléfono -->
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs text-slate-500 font-medium">Destinatario:</p>
                            <p class="text-base font-bold text-slate-900">{{ $order->recipient_name }}</p>
                        </div>
                        <div class="flex items-center space-x-1.5">
                            @php
                                $cleanPhone = preg_replace('/[^0-9]/', '', $order->recipient_phone);
                                if (!str_starts_with($cleanPhone, '57') && strlen($cleanPhone) === 10) {
                                    $cleanPhone = '57' . $cleanPhone;
                                }
                            @endphp
                            <!-- Botón WhatsApp Directo -->
                            <a href="https://wa.me/{{ $cleanPhone }}?text=Hola%20{{ urlencode($order->recipient_name) }},%20somos%20de%20Nuvex%20Detalles.%20Llevamos%20tu%20sorpresa%20en%20camino!"
                               target="_blank"
                               class="p-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl shadow-xs transition-transform active:scale-95 flex items-center justify-center"
                               title="Escribir por WhatsApp">
                               <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                            </a>
                            <!-- Botón Llamada Telefónica -->
                            <a href="tel:{{ $order->recipient_phone }}"
                               class="p-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-xs transition-transform active:scale-95 flex items-center justify-center"
                               title="Llamar">
                               <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            </a>
                        </div>
                    </div>

                    <!-- Dirección y Zona -->
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200/80 space-y-2">
                        <div class="flex items-start space-x-2">
                            <span class="text-rose-600 text-sm mt-0.5">📍</span>
                            <div class="flex-1">
                                <p class="text-xs font-bold text-slate-900">{{ $order->recipient_address }}</p>
                                @if ($order->recipient_address_details)
                                    <p class="text-xs text-slate-600">{{ $order->recipient_address_details }}</p>
                                @endif
                                <p class="text-[11px] font-semibold text-rose-700 mt-0.5">{{ $order->delivery_zone_name }}</p>
                            </div>
                        </div>

                        <!-- Botones de Navegación GPS -->
                        <div class="grid grid-cols-2 gap-2 pt-1">
                            <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($order->recipient_address . ', Bogotá, Colombia') }}"
                               target="_blank"
                               class="py-2 px-2.5 bg-white border border-slate-300 hover:bg-slate-100 text-slate-800 rounded-lg text-xs font-bold flex items-center justify-center space-x-1.5 shadow-2xs active:scale-95 transition-all">
                                <span>🗺️ Google Maps</span>
                            </a>
                            <a href="https://waze.com/ul?q={{ urlencode($order->recipient_address . ', Bogotá, Colombia') }}&navigate=yes"
                               target="_blank"
                               class="py-2 px-2.5 bg-sky-50 border border-sky-300 hover:bg-sky-100 text-sky-800 rounded-lg text-xs font-bold flex items-center justify-center space-x-1.5 shadow-2xs active:scale-95 transition-all">
                                <span>🚗 Waze</span>
                            </a>
                        </div>
                    </div>

                    <!-- Instrucciones de entrega si existen -->
                    @if ($order->delivery_instructions)
                        <div class="p-2.5 bg-amber-50/70 border border-amber-200/80 rounded-xl text-xs text-amber-900">
                            <span class="font-bold">⚠️ Nota Repartidor:</span> {{ $order->delivery_instructions }}
                        </div>
                    @endif

                    <!-- Insumos incluidos en el paquete -->
                    <div class="text-xs text-slate-600">
                        <span class="font-bold text-slate-700">Contenido:</span>
                        <ul class="list-disc list-inside mt-0.5 text-slate-600">
                            @foreach ($order->items as $item)
                                <li>{{ $item->quantity }}x {{ $item->product_name }}</li>
                            @endforeach
                        </ul>
                    </div>

                    <!-- Acciones Operativas del Repartidor -->
                    @if ($order->status->value !== 'DELIVERED')
                        <div class="pt-2 border-t border-slate-100 space-y-2">
                            @if ($order->status->value !== 'IN_TRANSIT')
                                <form action="{{ route('driver.orders.start', $order) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                            class="w-full py-2.5 bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white font-bold rounded-xl text-xs shadow-xs transition-all flex items-center justify-center space-x-1.5">
                                        <span>🛵 Iniciar Ruta de Entrega</span>
                                    </button>
                                </form>
                            @else
                                <details class="group bg-slate-50 border border-slate-200 rounded-xl overflow-hidden">
                                    <summary class="p-2.5 text-xs font-bold text-slate-800 cursor-pointer flex items-center justify-between bg-slate-100/70">
                                        <span>📝 Finalizar y Registrar Entrega</span>
                                        <span class="text-slate-600 group-open:rotate-180 transition-transform">▼</span>
                                    </summary>
                                    <form action="{{ route('driver.orders.complete', $order) }}" method="POST" class="p-3 space-y-2">
                                        @csrf
                                        <textarea name="notes" rows="2" placeholder="Opcional: Recibido por quién (ej: en portería, vigilancia, o el homenajeado)..."
                                                  class="w-full p-2 text-xs border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500"></textarea>
                                        <button type="submit"
                                                class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-bold rounded-xl text-xs shadow-xs transition-all flex items-center justify-center space-x-1.5">
                                            <span>🎉 Confirmar Entrega Exitosa</span>
                                        </button>
                                    </form>
                                </details>
                            @endif
                        </div>
                    @else
                        <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-emerald-700">
                            <span>Entregado a las {{ $order->delivered_at?->format('h:i A') ?? 'N/A' }}</span>
                            @if ($order->delivery_proof_notes)
                                <span class="italic truncate max-w-[150px]">"{{ $order->delivery_proof_notes }}"</span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl p-8 text-center border border-slate-200">
                <span class="text-4xl">🎉</span>
                <h3 class="font-bold text-slate-800 text-base mt-2">¡No hay pedidos en esta vista!</h3>
                <p class="text-xs text-slate-600 mt-1 mb-3">No hay entregas para los filtros seleccionados.</p>
                @if($stats['total_pending_all'] > 0)
                    <a href="{{ route('driver.dashboard', ['date' => 'pending']) }}" class="inline-block py-2 px-4 bg-rose-600 text-white text-xs font-bold rounded-xl shadow-xs">
                        Ver mis {{ $stats['total_pending_all'] }} pedidos pendientes
                    </a>
                @endif
            </div>
        @endforelse
    </div>

</div>
@endsection
