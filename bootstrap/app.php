<?php

use App\Http\Middleware\EnsureAdminHasMfa;
use App\Http\Middleware\EnsurePendaftarTerverifikasi;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'aktif' => EnsureUserIsActive::class,
            'pendaftar' => EnsurePendaftarTerverifikasi::class,
            'mfa' => EnsureAdminHasMfa::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (TooManyRequestsHttpException $exception, Request $request) {
            if ($request->isMethod('post') && $request->routeIs('login')) {
                return back()->withInput($request->only('email'))->withErrors([
                    'email' => 'Terlalu banyak percobaan login. Tunggu satu menit, lalu coba kembali.',
                ])->setStatusCode(429);
            }
        });
    })->create();
