<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
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
        // Rate limiter para endpoints sensibles (10 peticiones/minuto por IP)
        RateLimiter::for('api-sensitive', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // Rate limiter para endpoints públicos del catálogo (60 peticiones/minuto por IP)
        RateLimiter::for('api-public', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        // Rate limiter para integraciones automatizadas (30 peticiones/minuto)
        RateLimiter::for('api-integrations', function (Request $request) {
            return Limit::perMinute(30)->by($request->header('X-API-Key') ?: $request->ip());
        });
    }
}
