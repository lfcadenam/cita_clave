<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>@yield('title', 'Nuvex Domicilios Bogotá')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }
    </style>
</head>
<body class="h-full flex flex-col text-slate-800 antialiased selection:bg-rose-500 selection:text-white">

    <!-- Navbar superior fijo -->
    <header class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-slate-200 px-4 py-3 shadow-xs">
        <div class="max-w-md mx-auto flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <span class="text-xl">🛵</span>
                <div>
                    <h1 class="font-extrabold text-slate-900 leading-none text-base">Nuvex <span class="text-rose-600">Ruta</span></h1>
                    <p class="text-[10px] text-slate-600 font-medium">Logística Bogotá</p>
                </div>
            </div>

            @auth
                <div class="flex items-center space-x-3">
                    <span class="text-xs font-semibold text-slate-600 truncate max-w-[120px]">{{ Auth::user()->name }}</span>
                    <form action="{{ route('driver.logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="p-1.5 text-slate-600 hover:text-rose-600 rounded-lg hover:bg-slate-100 transition-colors" title="Cerrar Sesión">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </button>
                    </form>
                </div>
            @endauth
        </div>
    </header>

    <!-- Contenido Principal -->
    <main class="flex-1 max-w-md w-full mx-auto p-4 pb-20">
        @if (session('success'))
            <div class="mb-4 p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-sm flex items-center space-x-2 shadow-xs animate-fade-in">
                <span class="text-base">✅</span>
                <span class="font-medium">{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error') || $errors->any())
            <div class="mb-4 p-3.5 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-sm flex items-center space-x-2 shadow-xs">
                <span class="text-base">⚠️</span>
                <span class="font-medium">{{ session('error') ?? $errors->first() }}</span>
            </div>
        @endif

        @yield('content')
    </main>

</body>
</html>
