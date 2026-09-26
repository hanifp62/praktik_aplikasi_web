<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeaders;
use App\Support\DisplayPreference;
use App\Support\ThemePreference;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            SecurityHeaders::class,
        ]);

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);

        // Cookie tema dan Tampilan dibaca skrip peramban untuk menyamakan atribut <html>
        // setelah kunjungan Inertia, dan isinya hanya nilai dari daftar putih, jadi tidak
        // dienkripsi. Nilainya tetap diwhitelist di ThemePreference dan DisplayPreference.
        $middleware->encryptCookies(except: [ThemePreference::COOKIE, DisplayPreference::COOKIE_EFEK, DisplayPreference::COOKIE_GERAK]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
