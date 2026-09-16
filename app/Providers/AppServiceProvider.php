<?php

namespace App\Providers;

use App\Contracts\RoutingEngine;
use App\Services\AuditLogger;
use App\Services\Routing\CachingRoutingEngine;
use App\Services\Routing\OpenRouteServiceAdapter;
use App\Services\Routing\TomTomRoutingAdapter;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Live Sales Field Operations - Provider Abstraction (Blueprint
        // #64 Architecture Lock, #83): satu titik untuk mengganti Routing
        // Engine. Kode lain (Controller, Service Visit/Customer/Sales)
        // hanya bergantung ke interface RoutingEngine, tidak pernah ke
        // provider tertentu. Dibungkus CachingRoutingEngine supaya
        // caching & request deduplication (Blueprint #76-#77) otomatis
        // berlaku untuk provider apa pun.
        $this->app->bind(RoutingEngine::class, function () {
            $config = config('services.routing');

            $adapter = match ($config['provider']) {
                'openrouteservice' => new OpenRouteServiceAdapter(
                    baseUrl: $config['base_url'],
                    apiKey: $config['api_key'],
                    profile: $config['profile'],
                ),
                // Tambahkan case 'osrm' => new OsrmRoutingAdapter(...) di
                // sini saat proyek pindah ke self-hosted OSRM (VPS).
                default => new TomTomRoutingAdapter(
                    baseUrl: $config['base_url'],
                    apiKey: $config['api_key'],
                    routeType: $config['route_type'],
                    traffic: $config['traffic'],
                ),
            };

            return new CachingRoutingEngine($adapter, (int) $config['cache_ttl_seconds']);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Audit Foundation (Blueprint #45): Login & Logout wajib diaudit.
        Event::listen(function (Login $event) {
            AuditLogger::log(action: 'login', module: 'Authentication');
        });

        Event::listen(function (Logout $event) {
            AuditLogger::log(action: 'logout', module: 'Authentication', userId: $event->user?->id);
        });
    }
}
