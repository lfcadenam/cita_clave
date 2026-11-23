@props([
    'after' => null,
    'heading' => null,
    'subheading' => null,
])

@php
    use Filament\Livewire\SimpleUserMenu;
    use Filament\Support\Enums\Width;
    use Filament\Support\Facades\FilamentView;
    use Filament\View\PanelsRenderHook;

    $livewire ??= null;

    $renderHookScopes = $livewire?->getRenderHookScopes();
    $maxContentWidth ??= (filament()->getSimplePageMaxContentWidth() ?? Width::Large);

    if (is_string($maxContentWidth)) {
        $maxContentWidth = Width::tryFrom($maxContentWidth) ?? $maxContentWidth;
    }

    $isSuperAdmin = request()->is('superadmin*') || (filament()->getId() === 'superadmin');
@endphp

<x-filament-panels::layout.base :livewire="$livewire">
    <div class="fi-simple-layout nuvex-split-login-canvas">
        
        {{-- Elementos Arquitectónicos Ambientales de Fondo (Geometric Vector Depth) --}}
        <div class="nuvex-bg-ambient-layer" aria-hidden="true">
            <div class="nuvex-glow-orb nuvex-glow-top-left"></div>
            <div class="nuvex-glow-orb nuvex-glow-bottom-right"></div>
            <svg class="nuvex-bg-contour-svg" viewBox="0 0 1440 900" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="120" cy="180" r="320" stroke="rgba(255,255,255,0.03)" stroke-width="1.5" stroke-dasharray="8 8" />
                <circle cx="120" cy="180" r="480" stroke="rgba(255,255,255,0.02)" stroke-width="1.5" />
                <circle cx="1320" cy="780" r="420" stroke="rgba(255,255,255,0.03)" stroke-width="1.5" stroke-dasharray="10 10" />
                <circle cx="1320" cy="780" r="600" stroke="rgba(255,255,255,0.02)" stroke-width="1.5" />
            </svg>
        </div>

        @if (($hasTopbar ?? true) && filament()->auth()->check())
            <a href="#fi-main-content" class="fi-skip-link fi-sr-only">
                {{ __('filament-panels::layout.skip_to_content.label') }}
            </a>
        @endif

        {{ FilamentView::renderHook(PanelsRenderHook::SIMPLE_LAYOUT_START, scopes: $renderHookScopes) }}

        @if (($hasTopbar ?? true) && filament()->auth()->check())
            <div class="fi-simple-layout-header">
                @if (filament()->hasDatabaseNotifications())
                    @livewire(filament()->getDatabaseNotificationsLivewireComponent(), [
                        'lazy' => filament()->hasLazyLoadedDatabaseNotifications(),
                        'position' => \Filament\Enums\DatabaseNotificationsPosition::Topbar,
                    ])
                @endif

                @if (filament()->hasUserMenu())
                    @livewire(SimpleUserMenu::class)
                @endif
            </div>
        @endif

        <div class="fi-simple-main-ctn nuvex-split-outer-container">
            <div class="nuvex-split-card">
                
                {{-- COLUMNA IZQUIERDA: Panel Visual Ejecutivo & Formal --}}
                <div class="nuvex-split-visual-panel">
                    <div class="nuvex-visual-image-wrapper">
                        <img src="{{ asset('images/login-studio-art.jpg') }}" alt="Cita Clave Studio" class="nuvex-visual-img" />
                        <div class="nuvex-visual-dark-overlay"></div>
                    </div>

                    <div class="nuvex-visual-content">
                        <div class="nuvex-visual-badge-top">
                            <span class="nuvex-visual-dot-pulse"></span>
                            <span>{{ $isSuperAdmin ? 'NUVEX SAAS CORE • CONTROL GLOBAL' : 'CITA CLAVE • SALON & BEAUTY SAAS' }}</span>
                        </div>

                        <h2 class="nuvex-visual-title">
                            {{ $isSuperAdmin ? 'Centro de Control y Supervisión Multi-Tenant' : 'Gestión Inteligente de Citas y Pagos en Tiempo Real' }}
                        </h2>

                        <p class="nuvex-visual-subtitle">
                            {{ $isSuperAdmin 
                                ? 'Monitoreo continuo de infraestructura, pasarelas de pago y actividad de salones en tiempo real.' 
                                : 'Control ágil de agenda, clientes y notificaciones automáticas vía WhatsApp Cloud API.' }}
                        </p>

                        {{-- Lista de Características de Confianza & Seguridad --}}
                        <div class="nuvex-visual-features">
                            <div class="nuvex-vf-item">
                                <div class="nuvex-vf-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                    </svg>
                                </div>
                                <div class="nuvex-vf-text">
                                    <strong>Aislamiento & Seguridad Bancaria</strong>
                                    <span>Cifrado TLS 1.3 con almacenamiento segmentado.</span>
                                </div>
                            </div>

                            <div class="nuvex-vf-item">
                                <div class="nuvex-vf-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                </div>
                                <div class="nuvex-vf-text">
                                    <strong>99.9% Uptime en la Nube</strong>
                                    <span>Arquitectura SaaS de baja latencia siempre operativa.</span>
                                </div>
                            </div>

                            <div class="nuvex-vf-item">
                                <div class="nuvex-vf-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                    </svg>
                                </div>
                                <div class="nuvex-vf-text">
                                    <strong>Meta WhatsApp Cloud API</strong>
                                    <span>Plantillas oficiales y recordatorios 24h automatizados.</span>
                                </div>
                            </div>
                        </div>

                        {{-- Pill Frosted Glass Inferior --}}
                        <div class="nuvex-visual-status-pill">
                            <div class="nuvex-vsp-left">
                                <span class="nuvex-vsp-dot"></span>
                                <span>Servidor Nuvex Cloud Activo</span>
                            </div>
                            <span class="nuvex-vsp-version">v2.4 Enterprise</span>
                        </div>
                    </div>
                </div>

                {{-- COLUMNA DERECHA: Formulario Minimalista Blanco --}}
                <div class="nuvex-split-form-panel">
                    <main id="fi-main-content" tabindex="-1" class="fi-simple-main">
                        {{ $slot }}
                    </main>

                    <div class="nuvex-login-form-foot">
                        <div class="nuvex-lff-divider"></div>
                        <p class="nuvex-lff-text">
                            Plataforma desarrollada y protegida por <strong>Nuvex Tecnología</strong> · © 2026
                        </p>
                    </div>
                </div>

            </div>
        </div>

        {{ FilamentView::renderHook(PanelsRenderHook::FOOTER, scopes: $renderHookScopes) }}

        {{ FilamentView::renderHook(PanelsRenderHook::SIMPLE_LAYOUT_END, scopes: $renderHookScopes) }}
    </div>
</x-filament-panels::layout.base>
