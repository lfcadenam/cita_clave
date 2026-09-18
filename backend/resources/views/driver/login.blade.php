@extends('driver.layout')

@section('title', 'Ingreso Domiciliarios | Nuvex')

@section('content')
<div class="mt-8 flex flex-col items-center">
    <div class="w-16 h-16 bg-rose-100 text-rose-600 rounded-2xl flex items-center justify-center text-3xl shadow-inner mb-4">
        🛵
    </div>
    
    <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Portal Domiciliarios</h2>
    <p class="text-sm text-slate-500 mt-1 mb-8 text-center">Ingresa con tus credenciales asignadas para ver tu hoja de ruta del día</p>

    <div class="w-full bg-white p-6 rounded-2xl shadow-sm border border-slate-200/80">
        <form action="{{ route('driver.login.submit') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Correo Electrónico</label>
                <div class="relative">
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-sm focus:outline-hidden focus:ring-2 focus:ring-rose-500 focus:bg-white transition-all"
                        placeholder="tu.nombre@nuvex.com">
                </div>
            </div>

            <div>
                <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Contraseña</label>
                <input type="password" id="password" name="password" required
                    class="w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-sm focus:outline-hidden focus:ring-2 focus:ring-rose-500 focus:bg-white transition-all"
                    placeholder="••••••••">
            </div>

            <div class="flex items-center justify-between text-sm py-1">
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" name="remember" class="w-4 h-4 text-rose-600 rounded border-slate-300 focus:ring-rose-500">
                    <span class="text-xs text-slate-600">Recordarme</span>
                </label>
            </div>

            <button type="submit"
                class="w-full py-3.5 bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white font-bold rounded-xl shadow-md shadow-rose-200 transition-all flex items-center justify-center space-x-2 text-base">
                <span>Ingresar a Ruta</span>
                <span>➜</span>
            </button>
        </form>
    </div>

    <p class="mt-8 text-xs text-center text-slate-400">Nuvex Logistics © {{ date('Y') }} • Bogotá D.C.</p>
</div>
@endsection
