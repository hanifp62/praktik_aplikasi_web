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
        {{-- Tiga suara: antarmuka, judul, dan pengukuran. Alasan pemilihannya tertulis
             di tailwind.config.js. display=swap supaya teks terbaca sejak sebelum
             hurufnya tiba, karena jaringan lambat adalah keadaan normal di sini. --}}
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700|newsreader:400,500,600|jetbrains-mono:400,500&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        {{-- WCAG 2.4.1: pengguna keyboard tidak perlu menekan Tab melewati seluruh
             navigasi di setiap halaman. Tersembunyi sampai difokuskan. --}}
        <a href="#konten"
            class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50
                focus:rounded-control focus:bg-brand-700 focus:px-4 focus:py-2 focus:text-sm
                focus:font-medium focus:text-white">
            Lewati ke konten utama
        </a>

        <div class="min-h-screen bg-canvas">
            <livewire:layout.navigation />

            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main id="konten" tabindex="-1">
                {{ $slot }}
            </main>
        </div>

        @stack('scripts')
    </body>
</html>
