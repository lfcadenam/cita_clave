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
@endphp

<x-filament-panels::layout.base :livewire="$livewire">
    <div class="fi-simple-layout nuvex-split-login-canvas">
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
                <!-- Columna Izquierda: Visual Studio 3D Art (Figma Inspired) -->
                <div class="nuvex-split-visual-panel">
                    <div class="nuvex-visual-image-wrapper">
                        <img src="{{ asset('images/login-studio-art.jpg') }}" alt="Cita Clave Studio" class="nuvex-visual-img" />
                    </div>
                    <!-- Badge Frosted Glass inferior -->
                    <div class="nuvex-visual-frosted-capsule">
                        <div class="nuvex-capsule-content">
                            <h4 class="nuvex-capsule-title">Bienvenida a tu espacio</h4>
                            <p class="nuvex-capsule-desc">Ingresa para explorar tu gestión inteligente</p>
                            <div class="nuvex-capsule-dots">
                                <span class="nuvex-dot active"></span>
                                <span class="nuvex-dot"></span>
                                <span class="nuvex-dot"></span>
                                <span class="nuvex-dot"></span>
                                <span class="nuvex-dot"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Formulario Limpio & Minimalista -->
                <div class="nuvex-split-form-panel">
                    <main
                        id="fi-main-content"
                        tabindex="-1"
                        class="fi-simple-main"
                    >
                        {{ $slot }}
                    </main>
                </div>
            </div>
        </div>

        {{ FilamentView::renderHook(PanelsRenderHook::FOOTER, scopes: $renderHookScopes) }}

        {{ FilamentView::renderHook(PanelsRenderHook::SIMPLE_LAYOUT_END, scopes: $renderHookScopes) }}
    </div>
</x-filament-panels::layout.base>
