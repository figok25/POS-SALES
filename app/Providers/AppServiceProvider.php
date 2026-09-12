<?php

namespace App\Providers;

use App\Services\AuditLogger;
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
        //
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
