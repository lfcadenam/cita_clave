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
    <!-- Fonts: Playfair Display + Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
                        serif: ['"Playfair Display"', 'Georgia', 'serif'],
                    },
                    colors: {
                        cc: {
                            navy:       '#2D2327',
                            'navy-800': '#1F171A',
                            'navy-100': '#F3ECEE',
                            teal:       '#8E4D5E',
                            'teal-700': '#793E4C',
                            'teal-100': '#F7EBEF',
                            'teal-50':  '#FAF2F4',
                            surface:    '#FAF8F5',
                            border:     '#EFEAE6',
                        },
                        gold: {
                            400: '#C8A882',
                            500: '#B89770',
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
        .font-serif { font-family: 'Playfair Display', Georgia, serif !important; }

        /* Glass card utility */
        .cc-glass {
            background: rgba(255,255,255,0.96);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        /* Scrollbar suave */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #FAF8F5; }
        ::-webkit-scrollbar-thumb { background: #DBCFCA; border-radius: 99px; }

        /* Hide scrollbars on pills */
        .no-scrollbar::-webkit-scrollbar { display: none !important; width: 0 !important; height: 0 !important; }
        .no-scrollbar { -ms-overflow-style: none !important; scrollbar-width: none !important; }
    </style>
</head>
<body class="bg-[#FAF8F5] text-[#2D2327] antialiased min-h-screen flex flex-col selection:bg-[#8E4D5E] selection:text-white">

    <!-- ===== NAVBAR ===== -->
    <header class="sticky top-0 z-50 cc-glass border-b border-[#EFEAE6] shadow-xs">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 h-16 sm:h-20 flex items-center justify-between gap-3">

            <!-- Logo / Identidad del salón -->
            <a href="{{ url('/') }}" class="flex items-center gap-3 group min-w-0">
                <img src="{{ asset('images/brand/citaclave-logo.png') }}" 
                     alt="Cita Clave" 
                     class="h-9 sm:h-11 md:h-12 w-auto object-contain shrink-0 transition-transform group-hover:scale-[1.02]">
                <div class="border-l border-stone-200 pl-2.5 hidden sm:block">
                    <span class="font-serif font-bold text-xs sm:text-sm text-[#2D2327] block leading-none">Paola Aguilera</span>
                    <p class="text-[10px] text-[#8E4D5E] font-bold tracking-widest uppercase mt-0.5">Estudio de Uñas & Belleza</p>
                </div>
            </a>

            <!-- Acciones de navbar -->
            <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
                <a href="{{ url('/reserva/consulta') }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold
                          text-stone-700 bg-white hover:bg-[#FAF2F4] hover:text-[#8E4D5E]
                          border border-[#EFEAE6] transition-colors shadow-2xs">
                    <i data-lucide="search" class="w-3.5 h-3.5 shrink-0 text-stone-400"></i>
                    <span class="hidden sm:inline">Consultar Cita</span>
                </a>
            </div>
        </div>
    </header>

    <!-- ===== CONTENIDO PRINCIPAL ===== -->
    <main class="flex-grow">
        @yield('content')
    </main>

    <!-- ===== FOOTER ===== -->
    <footer class="bg-white border-t border-[#EFEAE6] mt-16">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-8">
            <!-- Info del salón -->
            <div class="flex flex-col sm:flex-row items-center sm:items-start gap-4 sm:gap-8">
                <div class="flex items-center gap-3 shrink-0">
                    <div class="w-10 h-10 rounded-xl bg-[#FAF2F4] flex items-center justify-center shadow-xs border border-[#F0DEE3]">
                        <i data-lucide="sparkles" class="w-5 h-5 text-[#8E4D5E]"></i>
                    </div>
                    <div>
                        <p class="font-serif font-bold text-sm text-[#2D2327]">Paola Andrea Aguilera Camacho</p>
                        <p class="text-[11px] text-stone-500">Estudio Especializado de Uñas & Belleza · Bogotá, Colombia</p>
                    </div>
                </div>
                <div class="text-center sm:text-left">
                    <p class="text-[11px] text-stone-600 font-medium">Manicura Rusa · Uñas Polygel · Pedicura Spa · Esmaltado Semipermanente</p>
                    <p class="text-[11px] text-stone-400 mt-0.5">Lunes a Sábado 8:00 AM – 6:00 PM &nbsp;|&nbsp; Almuerzo 1:00 PM – 2:00 PM</p>
                </div>
            </div>

            <!-- Divider + Powered by -->
            <div class="mt-6 pt-5 border-t border-[#EFEAE6] flex flex-col sm:flex-row items-center justify-between gap-2 text-[10px] text-stone-400">
                <span>Agenda · Clientes · Control</span>
                <span>
                    Plataforma
                    <span class="font-bold text-[#8E4D5E]">Cita Clave</span>
                    — desarrollada por
                    <span class="font-bold text-stone-600">Nuvex Tecnología</span>
                    &copy; {{ date('Y') }}
                </span>
            </div>
        </div>
    </footer>

    @php
        $portalWaPhone = isset($appointment) && $appointment->tenant?->whatsapp_number 
            ? preg_replace('/\D/', '', $appointment->tenant->whatsapp_number)
            : (isset($tenant) && $tenant->whatsapp_number ? preg_replace('/\D/', '', $tenant->whatsapp_number) : '573106080402');
        if (strlen($portalWaPhone) === 10 && str_starts_with($portalWaPhone, '3')) {
            $portalWaPhone = '57' . $portalWaPhone;
        }
    @endphp

    <!-- ===== BOTÓN FLOTANTE WHATSAPP (INTERACCIÓN DIRECTA & ASISTENCIA) ===== -->
    <a href="https://wa.me/{{ $portalWaPhone }}?text=Hola%20Paola,%20quisiera%20consultar%20sobre%20tus%20servicios"
       target="_blank"
       rel="noopener noreferrer"
       aria-label="Contactar por WhatsApp a Paola Aguilera"
       title="Chatea con nosotros por WhatsApp"
       class="fixed bottom-6 right-6 z-50 group flex items-center gap-2.5 bg-[#25D366] hover:bg-[#20BD5A] text-white px-4 py-3 rounded-full shadow-[0_8px_24px_rgba(37,211,102,0.30)] hover:shadow-[0_12px_28px_rgba(37,211,102,0.40)] transition-all duration-200 hover:scale-[1.04] active:scale-[0.98]">
        <svg class="w-5 h-5 shrink-0 fill-current" viewBox="0 0 24 24">
            <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.669-.699c.969.542 1.897.82 2.791.82h.001c3.181 0 5.768-2.586 5.768-5.766 0-3.18-2.587-5.766-5.769-5.766zm3.376 8.204c-.149.419-.747.781-1.026.832-.27.05-.62.072-1.808-.419-1.52-.628-2.502-2.18-2.578-2.282-.075-.102-.614-.817-.614-1.558 0-.741.388-1.106.526-1.255.138-.149.3-.186.4-.186.1 0 .2.001.288.006.094.005.218-.035.341.262.126.305.433 1.056.471 1.134.038.077.063.168.013.268-.05.101-.075.163-.15.251-.075.088-.158.197-.225.264-.075.075-.153.156-.066.305.087.149.387.639.83 1.033.57.507 1.051.664 1.2.739.149.075.237.063.325-.038.088-.101.376-.438.476-.588.1-.15.2-.125.338-.075.138.05.877.413 1.027.488.15.075.25.112.288.175.037.063.037.363-.112.782z"/>
        </svg>
        <span class="text-xs font-bold tracking-wide">WhatsApp</span>
    </a>

    <script>lucide.createIcons();</script>
    @stack('scripts')
</body>
</html>

