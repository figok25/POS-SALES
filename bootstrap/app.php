<?php

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Prinsip blueprint (#44 Security): error teknis (SQLSTATE, dsb) tidak
        // boleh ditampilkan langsung ke user. Tampilkan pesan yang ramah,
        // sementara detail asli tetap masuk ke log (laravel.log) untuk developer.
        $exceptions->render(function (QueryException $e, Request $request) {
            if (! $request->expectsJson() && ! config('app.debug')) {
                return response()->view('errors.database', [
                    'message' => 'Data tidak dapat diproses karena terjadi konflik. Silakan periksa kembali data Anda.',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return null;
        });
    })->create();
