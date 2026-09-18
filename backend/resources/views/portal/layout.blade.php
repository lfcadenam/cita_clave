<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Paola Aguilera | Cita Clave')</title>
    <meta name="description" content="Reserva en línea tus citas de pestañas, cejas, limpiezas faciales y micropigmentación con Paola Aguilera en Cita Clave.">

    <!-- Favicons Cita Clave -->
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon.png') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}">

    <!-- Plus Jakarta Sans — tipografía unificada Cita Clave -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        cc: {
                            navy:       '#1e3a5f',
                            'navy-800': '#162d4a',
                            'navy-100': '#e8eef5',
                            teal:       '#0d9488',
                            'teal-700': '#0f766e',
                            'teal-100': '#ccfbf1',
                            'teal-50':  '#e6f7f2',
                            surface:    '#f3f8f6',
                            border:     '#e2f0ea',
                        },
                        gold: {
                            400: '#fbbf24',
                            500: '#f59e0b',
                        }
                    }
                }
            }
        }
    </script>
    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Lucide Icons CDN -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        [x-cloak] { display: none !important; }
        * { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; }

        /* Glass card utility */
        .cc-glass {
            background: rgba(255,255,255,0.97);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        /* Scrollbar suave */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f3f8f6; }
        ::-webkit-scrollbar-thumb { background: #b2d8d0; border-radius: 99px; }

        /* Hide scrollbars on pills */
        .no-scrollbar::-webkit-scrollbar { display: none !important; width: 0 !important; height: 0 !important; }
        .no-scrollbar { -ms-overflow-style: none !important; scrollbar-width: none !important; }
    </style>
</head>
<body class="bg-[#f3f8f6] text-slate-800 antialiased min-h-screen flex flex-col selection:bg-[#e6f7f2] selection:text-[#1e3a5f]">

    <!-- ===== NAVBAR ===== -->
    <header class="sticky top-0 z-50 cc-glass border-b border-[#e2f0ea] shadow-xs">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 h-16 sm:h-20 flex items-center justify-between gap-3">

            <!-- Logo / Identidad del salón -->
            <a href="{{ url('/') }}" class="flex items-center gap-3 group min-w-0">
                <img src="{{ asset('images/brand/citaclave-logo.png') }}" 
                     alt="Cita Clave" 
                     class="h-9 sm:h-11 md:h-12 w-auto object-contain shrink-0 transition-transform group-hover:scale-[1.02]">
                <div class="border-l border-slate-200 pl-2.5 hidden sm:block">
                    <span class="font-bold text-xs sm:text-sm text-[#1e3a5f] block leading-none">Paola Aguilera</span>
                    <p class="text-[10px] text-[#0d9488] font-semibold tracking-wide uppercase mt-0.5">Estudio de Belleza</p>
                </div>
            </a>

            <!-- Acciones de navbar -->
            <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
                <a href="{{ url('/reserva/consulta') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold
                          text-slate-600 bg-white hover:bg-[#e6f7f2] hover:text-[#0d9488]
                          border border-[#e2f0ea] transition-colors shadow-xs">
                    <i data-lucide="search" class="w-3.5 h-3.5 shrink-0"></i>
                    <span class="hidden sm:inline">Consultar Cita</span>
                </a>
                <a href="https://wa.me/573106080402?text=Hola%20Paola,%20quisiera%20consultar%20sobre%20tus%20servicios"
                   target="_blank"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold
                          text-white bg-[#0d9488] hover:bg-[#0f766e]
                          shadow-xs transition-colors">
                    <i data-lucide="message-circle" class="w-3.5 h-3.5 shrink-0"></i>
                    <span class="hidden sm:inline">WhatsApp</span>
                </a>
            </div>
        </div>
    </header>

    <!-- ===== CONTENIDO PRINCIPAL ===== -->
    <main class="flex-grow">
        @yield('content')
    </main>

    <!-- ===== FOOTER ===== -->
    <footer class="bg-white border-t border-[#e2f0ea] mt-16">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-8">
            <!-- Info del salón -->
            <div class="flex flex-col sm:flex-row items-center sm:items-start gap-4 sm:gap-8">
                <div class="flex items-center gap-3 shrink-0">
                    <div class="w-10 h-10 rounded-xl bg-[#1e3a5f] flex items-center justify-center shadow-sm">
                        <i data-lucide="calendar-check" class="w-5 h-5 text-[#0d9488]"></i>
                    </div>
                    <div>
                        <p class="font-bold text-sm text-[#1e3a5f]">Paola Andrea Aguilera Camacho</p>
                        <p class="text-[11px] text-slate-500">Estudio Especializado · Bogotá, Colombia</p>
                    </div>
                </div>
                <div class="text-center sm:text-left">
                    <p class="text-[11px] text-slate-500">Pestañas · Cejas · Faciales · Micropigmentación</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Lunes a Sábado 8:00 AM – 6:00 PM &nbsp;|&nbsp; Almuerzo 1:00 PM – 2:00 PM</p>
                </div>
            </div>

            <!-- Divider + Powered by -->
            <div class="mt-6 pt-5 border-t border-[#e2f0ea] flex flex-col sm:flex-row items-center justify-between gap-2 text-[10px] text-slate-400">
                <span>Agenda · Clientes · Control</span>
                <span>
                    Plataforma
                    <span class="font-bold text-[#1e3a5f]">Cita Clave</span>
                    — desarrollada por
                    <span class="font-bold text-slate-500">Nuvex Tecnología</span>
                    &copy; {{ date('Y') }}
                </span>
            </div>
        </div>
    </footer>

    <script>lucide.createIcons();</script>
    @stack('scripts')
</body>
</html>

