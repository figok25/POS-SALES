<?php

namespace Tests\Feature\LiveSalesFieldOps;

use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Rute /api/sales/* dipakai WebView/browser (sesi + CSRF) DAN Android native
 * (Bearer token). Susunan middleware-nya harus benar:
 *  - kelompok `api` + Sanctum stateful SATU kali (bukan di dalam kelompok
 *    `web`, yang membuat sesi/cookie diproses dua kali dan menolak Bearer
 *    dengan 419 "CSRF token mismatch").
 *
 * Catatan Laravel 13: pengecekan CSRF di kelompok `web` memakai
 * PreventRequestForgery (ValidateCsrfToken hanya turunan lama yang deprecated).
 */
class SalesApiMiddlewareTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    /** @return array<int, mixed> */
    private function resolvedMiddleware(string $routeName): array
    {
        $route = Route::getRoutes()->getByName($routeName);
        $this->assertNotNull($route, "Route {$routeName} tidak ditemukan.");

        return app('router')->resolveMiddleware($route->gatherMiddleware(), $route->excludedMiddleware());
    }

    public function test_sales_api_routes_use_api_group_with_sanctum_stateful_exactly_once(): void
    {
        $routes = [
            'api.sales.location',
            'api.sales.tracking.start',
            'api.sales.tracking.status',
            'api.sales.tasks.documents.download',
            'api.sales.tasks.verify-stock',
            'api.sales.tasks.start-work',
            'api.sales.route.customer',
        ];

        $webOnly = [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            PreventRequestForgery::class,
            ValidateCsrfToken::class,
        ];

        foreach ($routes as $name) {
            $middleware = $this->resolvedMiddleware($name);

            $this->assertSame(
                1,
                count(array_keys($middleware, EnsureFrontendRequestsAreStateful::class, true)),
                "{$name}: middleware stateful Sanctum harus tepat satu kali."
            );

            // Middleware sesi/cookie/CSRF milik kelompok `web` TIDAK boleh ada di
            // tingkat route: Sanctum menambahkannya sendiri hanya untuk permintaan
            // dari domain stateful (WebView/browser), bukan untuk Bearer token.
            foreach ($webOnly as $class) {
                $this->assertNotContains($class, $middleware, "{$name}: tidak boleh memuat {$class} di route.");
            }
        }
    }

    public function test_native_token_route_stays_in_web_group_with_session_and_csrf(): void
    {
        // Dipanggil WebView dengan sesi cookie -> harus tetap kelompok `web`.
        $middleware = $this->resolvedMiddleware('sales.native-token');

        $this->assertContains(StartSession::class, $middleware);
        $this->assertContains(PreventRequestForgery::class, $middleware);
    }

    public function test_native_token_endpoint_issues_a_token(): void
    {
        [$user] = $this->makeSalesUser();

        $this->actingAs($user)
            ->postJson(route('sales.native-token'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['token']]);

        // Tabel personal_access_tokens harus ada lewat migration proyek.
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_bearer_token_authenticates_sales_api_without_cookies(): void
    {
        [$user] = $this->makeSalesUser();
        $token = $user->createToken('sales-app')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson(route('api.sales.tracking.status'))
            ->assertOk();
    }

    public function test_sales_api_rejects_requests_without_credentials(): void
    {
        $this->getJson(route('api.sales.tracking.status'))->assertUnauthorized();
    }

    public function test_session_request_from_stateful_frontend_works(): void
    {
        [$user] = $this->makeSalesUser();

        // Referer dari host yang ada di daftar stateful Sanctum (localhost).
        $this->actingAs($user)
            ->withHeader('Referer', 'http://localhost/sales/task')
            ->getJson(route('api.sales.tracking.status'))
            ->assertOk();
    }
}
