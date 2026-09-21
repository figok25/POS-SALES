<?php

namespace App\Providers;

use App\Services\Routing\RoutingService;
use App\Services\Routing\TomTomRoutingAdapter;
use Illuminate\Support\ServiceProvider;

/**
 * Daftarkan provider ini di bootstrap/providers.php (Laravel 11+) atau
 * config/app.php 'providers' array (Laravel <=10):
 *
 *     App\Providers\RoutingServiceProvider::class,
 *
 * PERBAIKAN AUDIT: RoutingService sekarang bergantung ke TomTomRoutingAdapter
 * (Orbis v3) untuk daily multi-stop route, BUKAN TomTomClient lama (sudah
 * dihapus). TomTomRoutingAdapter untuk single origin->destination (dipakai
 * Sales\RouteController@calculate) sudah dibind terpisah di AppServiceProvider
 * lewat interface RoutingEngine -- di sini kita bind instance TomTomRoutingAdapter
 * konkret untuk dipakai RoutingService::calculateMultiStopRoute().
 */
class RoutingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TomTomRoutingAdapter::class, function () {
            return new TomTomRoutingAdapter(
                baseUrl: config('routing.tomtom.base_url'),
                apiKey: config('routing.tomtom.api_key'),
                routeType: config('routing.tomtom.route_type', 'fastest'),
                traffic: config('routing.tomtom.traffic', 'true'),
            );
        });

        $this->app->singleton(RoutingService::class, function ($app) {
            return new RoutingService(
                adapter: $app->make(TomTomRoutingAdapter::class),
                monthlyHardBudget: config('routing.monthly_hard_budget'),
                rerouteCooldownSeconds: config('routing.reroute_cooldown_seconds'),
            );
        });
    }
}
