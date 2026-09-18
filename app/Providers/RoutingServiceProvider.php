<?php

namespace App\Providers;

use App\Services\Routing\RoutingService;
use App\Services\Routing\TomTomClient;
use Illuminate\Support\ServiceProvider;

/**
 * Daftarkan provider ini di bootstrap/providers.php (Laravel 11+) atau
 * config/app.php 'providers' array (Laravel <=10):
 *
 *     App\Providers\RoutingServiceProvider::class,
 */
class RoutingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TomTomClient::class, function () {
            return new TomTomClient(
                apiKey: config('routing.tomtom.api_key'),
                baseUrl: config('routing.tomtom.base_url'),
                timeoutSeconds: config('routing.tomtom.timeout_seconds'),
            );
        });

        $this->app->singleton(RoutingService::class, function ($app) {
            return new RoutingService(
                client: $app->make(TomTomClient::class),
                monthlyHardBudget: config('routing.monthly_hard_budget'),
                rerouteCooldownSeconds: config('routing.reroute_cooldown_seconds'),
            );
        });
    }
}
