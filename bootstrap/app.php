<?php

use App\Http\Middleware\EnsureFeatureEnabled;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeaders;
use App\Support\DisplayPreference;
use App\Support\ThemePreference;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
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
            'fitur' => EnsureFeatureEnabled::class,
        ]);

        // Sakelar fitur diperiksa sebelum batas laju (pra-tinjau Tugas 7 PT7-17): selama mati,
        // kiriman yang melewati batas tetap menjawab 404, bukan 429 yang membocorkan rutenya.
        $middleware->prependToPriorityList(before: ThrottleRequests::class, prepend: EnsureFeatureEnabled::class);

        // Cookie tema dan Tampilan dibaca skrip peramban untuk menyamakan atribut <html>
        // setelah kunjungan Inertia, dan isinya hanya nilai dari daftar putih, jadi tidak
        // dienkripsi. Nilainya tetap diwhitelist di ThemePreference dan DisplayPreference.
        $middleware->encryptCookies(except: [ThemePreference::COOKIE, DisplayPreference::COOKIE_EFEK, DisplayPreference::COOKIE_GERAK]);

        // Proxy tepercaya diterapkan AppServiceProvider dari config('app.trusted_proxies'), bukan env() di sini:
        // sesudah config:cache berkas .env tidak dimuat lagi, jadi env() di luar berkas config tidak dapat diandalkan.
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
