<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vinculación Oficial WhatsApp — {{ $tenant?->name ?? 'La Belle Nails Bogotá' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="h-full text-slate-800 antialiased flex flex-col justify-between selection:bg-teal-500 selection:text-white">

    <!-- Navbar Minimalista -->
    <header class="border-b border-slate-200/80 bg-white/90 backdrop-blur-md sticky top-0 z-50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-teal-50 border border-teal-100 flex items-center justify-center text-teal-600 font-bold">
                    <svg class="w-5 h-5 text-teal-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.771-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.312.045-.694.073-2.127-.518-1.741-.718-2.854-2.493-2.942-2.61-.088-.117-.704-.937-.704-1.787 0-.85.443-1.267.601-1.441.157-.175.343-.219.458-.219.114 0 .229.001.328.006.105.006.246-.04.385.293.144.346.49 1.196.533 1.283.043.088.072.19.014.305-.058.117-.087.19-.173.293-.086.102-.181.229-.258.308-.087.087-.177.182-.076.355.101.173.45 1.05 1.285 1.792.835.742 1.488.989 1.701 1.077.213.088.337.073.464-.073.127-.146.545-.635.69-1.071.144-.436.319-.364.603-.262.285.102 1.808.852 2.118 1.006.31.154.517.229.592.355.075.127.075.735-.069 1.14z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-sm font-bold text-slate-900 leading-none">{{ $tenant?->name ?? 'La Belle Nails Bogotá' }}</h1>
                    <span class="text-[11px] text-slate-500 font-medium">Gateway Oficial de WhatsApp</span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ url('/admin/appointments') }}" class="text-xs font-semibold text-slate-600 hover:text-teal-700 bg-slate-100 hover:bg-slate-200/80 px-4 py-2 rounded-full transition-colors">
                    Ir al Panel Admin
                </a>
            </div>
        </div>
    </header>

    <!-- Contenedor Principal -->
    <main class="flex-1 max-w-4xl w-full mx-auto px-4 py-8 sm:py-12 flex flex-col items-center justify-center">

        <!-- Card de Estado y Vinculación -->
        <div class="w-full max-w-lg bg-white border border-slate-200/80 rounded-[32px] p-6 sm:p-10 shadow-xl shadow-slate-200/50 text-center relative overflow-hidden">

            <!-- Estado Conectado -->
            <div id="stateConnected" class="{{ $isConnected ? '' : 'hidden' }} space-y-6">
                <div class="w-16 h-16 rounded-full bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center mx-auto shadow-sm">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>

                <div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 mb-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Sesión Activa
                    </span>
                    <h2 class="text-xl font-bold text-slate-900">WhatsApp Vinculado con Éxito</h2>
                    <p class="text-xs text-slate-500 mt-1 max-w-xs mx-auto">
                        Tu número ya está sincronizado. Todas las nuevas citas agendadas y recordatorios se enviarán automáticamente desde este canal.
                    </p>
                </div>

                <!-- Prueba de Envío Rápido -->
                <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200/60 text-left space-y-3">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Enviar Mensaje de Prueba</label>
                    <div class="flex gap-2">
                        <input type="text" id="testPhone" value="{{ $adminPhone }}" placeholder="Ej: 3106080402"
                               class="flex-1 bg-white border border-slate-200 rounded-xl px-3.5 py-2 text-xs text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500">
                        <button onclick="sendTestMessage()" id="btnTestMsg"
                                class="bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs px-4 py-2 rounded-xl transition-all shadow-sm flex items-center gap-1.5 cursor-pointer">
                            <span>Enviar</span>
                        </button>
                    </div>
                    <p id="testResult" class="text-[11px] text-slate-500 hidden"></p>
                </div>

                <!-- Botón para forzar desvinculación y nuevo QR -->
                <div class="pt-2 border-t border-slate-100">
                    <button onclick="resetWhatsAppInstance()" id="btnResetInstance"
                            class="text-xs font-semibold text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 px-4 py-2 rounded-xl transition-all cursor-pointer inline-flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span>Desvincular y Generar Nuevo Código QR</span>
                    </button>
                </div>
            </div>

            <!-- Estado Desconectado (Mostrar QR / Pairing Code) -->
            <div id="stateDisconnected" class="{{ $isConnected ? 'hidden' : '' }} space-y-6">
                <div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200 mb-2">
                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span> Esperando Vinculación
                    </span>
                    <h2 class="text-xl font-bold text-slate-900">Vincular WhatsApp del Salón</h2>
                    <p class="text-xs text-slate-500 mt-1">Escanea el código QR desde tu celular para habilitar las alertas automáticas de citas.</p>
                </div>

                <!-- Recuadro del Código QR -->
                <div class="bg-slate-50 p-6 rounded-3xl border border-slate-200/80 inline-block shadow-inner relative">
                    <div class="w-64 h-64 mx-auto bg-white rounded-2xl p-3 border border-slate-200/60 flex items-center justify-center shadow-sm">
                        <img id="qrImage" src="{{ $qrBase64 ?? '' }}" alt="Código QR WhatsApp" class="{{ !empty($qrBase64) ? '' : 'hidden' }} w-full h-full object-contain">
                        <div id="qrLoading" class="{{ !empty($qrBase64) ? 'hidden' : '' }} text-xs text-slate-400 font-medium flex flex-col items-center gap-2">
                            <svg class="w-6 h-6 animate-spin text-teal-600" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span>Generando código QR...</span>
                        </div>
                    </div>

                    <div class="mt-3 flex items-center justify-center gap-2 text-[11px] text-slate-400">
                        <span class="w-1.5 h-1.5 rounded-full bg-teal-500 animate-pulse"></span>
                        <span>Se actualiza automáticamente en vivo</span>
                    </div>
                </div>

                <!-- Instrucciones Claras y Sobrias -->
                <div class="bg-teal-50/50 border border-teal-100 rounded-2xl p-4 text-left text-xs space-y-2 text-slate-600">
                    <h4 class="font-bold text-teal-900 text-xs">Pasos en tu teléfono celular:</h4>
                    <ol class="list-decimal list-inside space-y-1 text-slate-600 leading-relaxed text-[11px]">
                        <li>Abre <strong>WhatsApp</strong> en el teléfono que recibirá las citas.</li>
                        <li>Toca los tres puntos <strong>⋮</strong> (o Ajustes) → <strong>Dispositivos vinculados</strong>.</li>
                        <li>Presiona <strong>Vincular un dispositivo</strong> y apunta tu cámara al recuadro de arriba.</li>
                    </ol>
                </div>

                <!-- Opción Alternativa: Código Numérico de 8 Dígitos -->
                <div class="pt-2 border-t border-slate-100 text-left">
                    <button type="button" onclick="togglePairingCode()" class="text-xs font-bold text-teal-700 hover:text-teal-800 transition-colors flex items-center gap-1 cursor-pointer">
                        <span>¿Prefieres vincular con código de 8 dígitos sin usar cámara?</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div id="pairingCodeSection" class="hidden mt-3 bg-slate-50 rounded-2xl p-4 border border-slate-200/60 space-y-3">
                        <p class="text-[11px] text-slate-500 leading-relaxed">
                            Ingresa tu celular. WhatsApp te pedirá digitar un código de 8 caracteres en:  
                            <em>Dispositivos vinculados → Vincular con número de teléfono</em>.
                        </p>
                        <form method="GET" action="{{ url('/admin/whatsapp') }}" class="flex gap-2">
                            <input type="text" name="phone" value="{{ request('phone', $adminPhone) }}" placeholder="Ej: 3106080402"
                                   class="flex-1 bg-white border border-slate-200 rounded-xl px-3.5 py-2 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500">
                            <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs px-4 py-2 rounded-xl transition-all shadow-sm cursor-pointer">
                                Solicitar Código
                            </button>
                        </form>

                        @if(!empty($pairingCode))
                        <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-3 text-center">
                            <span class="text-[10px] font-bold uppercase text-emerald-800 tracking-wider">Tu Código de Vinculación:</span>
                            <div class="text-2xl font-extrabold text-emerald-700 tracking-widest my-1 font-mono select-all">
                                {{ $pairingCode }}
                            </div>
                            <span class="text-[10px] text-emerald-600 font-medium">Escribe este código en tu WhatsApp ahora</span>
                        </div>
                        @endif
                    </div>
                </div>

            </div>

        </div>

    </main>

    <!-- Footer Discreto -->
    <footer class="py-6 text-center text-xs text-slate-400 border-t border-slate-200/60">
        <span>CitaClave &copy; {{ date('Y') }} — Nuvex Tecnología</span>
    </footer>

    <!-- Script de Sincronización en Tiempo Real -->
    <script>
        const statusUrl = "{{ url('/admin/whatsapp/status') }}";
        const testUrl = "{{ url('/admin/whatsapp/test') }}";
        let isChecking = false;

        async function pollStatus() {
            if (isChecking) return;
            isChecking = true;

            try {
                const res = await fetch(statusUrl);
                const data = await res.json();

                if (data.connected) {
                    document.getElementById('stateConnected').classList.remove('hidden');
                    document.getElementById('stateDisconnected').classList.add('hidden');
                } else {
                    document.getElementById('stateConnected').classList.add('hidden');
                    document.getElementById('stateDisconnected').classList.remove('hidden');

                    if (data.qr) {
                        const img = document.getElementById('qrImage');
                        img.src = data.qr.startsWith('data:') ? data.qr : `data:image/png;base64,${data.qr}`;
                        img.classList.remove('hidden');
                        document.getElementById('qrLoading').classList.add('hidden');
                    }
                }
            } catch (e) {
                // Siguiente ciclo
            } finally {
                isChecking = false;
            }
        }

        // Consultar cada 4 segundos para actualización instantánea
        setInterval(pollStatus, 4000);

        function togglePairingCode() {
            const sec = document.getElementById('pairingCodeSection');
            sec.classList.toggle('hidden');
        }

        async function sendTestMessage() {
            const phone = document.getElementById('testPhone').value.trim();
            const btn = document.getElementById('btnTestMsg');
            const resEl = document.getElementById('testResult');

            if (!phone) {
                alert('Ingresa un número de celular.');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = 'Enviando...';
            resEl.classList.add('hidden');

            try {
                const res = await fetch(testUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ phone })
                });
                const data = await res.json();

                resEl.innerText = data.message;
                resEl.className = data.success ? 'text-[11px] text-emerald-600 font-bold block' : 'text-[11px] text-rose-600 font-bold block';
            } catch (err) {
                resEl.innerText = 'Error al enviar el mensaje de prueba.';
                resEl.className = 'text-[11px] text-rose-600 font-bold block';
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'Enviar';
            }
        }

        async function resetWhatsAppInstance() {
            if (!confirm('¿Deseas desvincular la sesión actual y generar un nuevo código QR limpio?')) {
                return;
            }
            const btn = document.getElementById('btnResetInstance');
            btn.disabled = true;
            btn.innerHTML = 'Reiniciando instancia...';

            try {
                const res = await fetch("{{ route('admin.whatsapp.reset') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await res.json();
                if (data.qr) {
                    document.getElementById('qrImage').src = data.qr;
                    document.getElementById('qrImage').classList.remove('hidden');
                    document.getElementById('qrLoading').classList.add('hidden');
                }
                document.getElementById('stateConnected').classList.add('hidden');
                document.getElementById('stateDisconnected').classList.remove('hidden');
            } catch (err) {
                alert('Error al reiniciar la instancia. Intenta de nuevo.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'Desvincular y Generar Nuevo Código QR';
            }
        }
    </script>
</body>
</html>
