<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name') }}</title>

        <!-- Fonts -->
        <link rel="manifest" href="/manifest.webmanifest">
        <meta name="theme-color" content="#047857">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        {{-- WCAG 2.4.1: pengguna keyboard tidak perlu menekan Tab melewati seluruh
             navigasi di setiap halaman. Tersembunyi sampai difokuskan. --}}
        <a href="#konten"
            class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50
                focus:rounded-control focus:bg-brand-700 focus:px-4 focus:py-2 focus:text-sm
                focus:font-medium focus:text-white">
            Lewati ke konten utama
        </a>

        <main id="konten" tabindex="-1" class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
            <div>
                <a href="/" wire:navigate>
                    <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </main>
    </body>
</html>
