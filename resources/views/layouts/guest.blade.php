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
        {{-- Berkasnya sudah ada di public/ sejak awal dan tidak pernah dinyatakan,
             sehingga peramban hanya menemukannya lewat konvensi dan peranti Apple tidak
             menemukannya sama sekali. --}}
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="apple-touch-icon" href="/icons/app-192.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        {{-- Tiga suara: antarmuka, judul, dan pengukuran. Alasan pemilihannya tertulis
             di tailwind.config.js. display=swap supaya teks terbaca sejak sebelum
             hurufnya tiba, karena jaringan lambat adalah keadaan normal di sini. --}}
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700|newsreader:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-primary antialiased">
        {{-- WCAG 2.4.1: pengguna keyboard tidak perlu menekan Tab melewati seluruh
             navigasi di setiap halaman. Tersembunyi sampai difokuskan. --}}
        <a href="#konten"
            class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50
                focus:rounded-control focus:bg-brand-700 focus:px-4 focus:py-2 focus:text-sm
                focus:font-medium focus:text-white">
            Lewati ke konten utama
        </a>

        <main id="konten" tabindex="-1" class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-canvas">
            <div>
                {{-- Logonya sendiri 80x80, jauh di atas 44px; min-h-11 dinyatakan di sini
                     supaya penjaga target sentuh membaca ukuran sungguhannya, bukan
                     menyimpulkannya dari ukuran gambar di dalamnya. --}}
                <a href="/" wire:navigate class="inline-flex min-h-11 items-center">
                    <x-application-logo class="w-20 h-20 fill-current text-muted" />
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-surface border border-subtle overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </main>
    </body>
</html>
