<?php

namespace App\Providers\Filament;

use App\Models\Tenant;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

use Filament\View\PanelsRenderHook;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->tenant(Tenant::class, slugAttribute: 'slug')
            ->brandName('Cita Clave')
            ->brandLogo(fn () => asset('images/brand/citaclave-logo-light.png'))
            ->brandLogoHeight('3.8rem')
            ->favicon(fn () => asset('favicon.png'))
            ->font('Plus Jakarta Sans')
            ->darkMode(false)
            ->sidebarWidth('17.5rem')
            ->maxContentWidth(Width::Full)
            ->colors([
                'primary' => Color::hex('#0d9488'),
                'danger' => Color::Red,
                'gray' => Color::Slate,
                'info' => Color::hex('#1e3a5f'),
                'success' => Color::hex('#0d9488'),
                'warning' => Color::Amber,
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => '<link rel="stylesheet" href="' . asset('css/nuvex-admin-theme.css') . '?v=' . (file_exists(public_path('css/nuvex-admin-theme.css')) ? filemtime(public_path('css/nuvex-admin-theme.css')) : time()) . '">'
            )
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('OPERATIVA'),
                NavigationGroup::make()
                    ->label('GESTIÓN & CATÁLOGO'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                \App\Filament\Widgets\SalonSpotlightWidget::class,
                \App\Filament\Widgets\StatsOverviewWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                ValidateCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
