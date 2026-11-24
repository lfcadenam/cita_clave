<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Protección estricta en producción: Prohíbe migrate:fresh, migrate:reset o db:wipe
        DB::prohibitDestructiveCommands($this->app->isProduction());

        // Rate limiter para endpoints sensibles (60 peticiones/minuto por IP para navegación fluida)
        RateLimiter::for('api-sensitive', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        // Rate limiter para endpoints públicos del catálogo (120 peticiones/minuto por IP)
        RateLimiter::for('api-public', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip());
        });

        // Rate limiter para integraciones automatizadas (60 peticiones/minuto)
        RateLimiter::for('api-integrations', function (Request $request) {
            return Limit::perMinute(60)->by($request->header('X-API-Key') ?: $request->ip());
        });
    }
}
