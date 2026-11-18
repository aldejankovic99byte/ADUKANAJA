<?php

use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);

        // Header keamanan dasar pada seluruh respons web
        $middleware->web(append: [SecurityHeaders::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // 419 "Page Expired" (token halaman kedaluwarsa) -> kembali ke halaman sebelumnya dengan pesan jelas
        $exceptions->render(function (HttpException $e, $request) {
            if ($e->getStatusCode() === 419 && ! $request->expectsJson()) {
                return redirect()->back(fallback: '/')
                    ->withInput($request->except(['password', 'password_lama', 'password_confirmation', '_token']))
                    ->with('error', 'Halaman sudah kedaluwarsa. Silakan coba lagi.');
            }
        });
    })->create();
