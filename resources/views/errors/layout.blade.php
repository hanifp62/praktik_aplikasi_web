<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('judul')</title>

        {{--
            Halaman ini dirender justru ketika sesuatu sedang rusak, jadi ia tidak
            menyentuh basis data, sesi, maupun komponen Livewire. Font dari luar pun
            tidak dimuat: kalau jaringannya yang bermasalah, menunggunya hanya menambah
            waktu sebelum pengguna membaca apa yang terjadi.
        --}}
        @vite(['resources/css/app.css'])
    </head>
    <body class="min-h-screen bg-white font-sans text-primary antialiased">
        <main class="mx-auto flex min-h-screen max-w-xl flex-col justify-center px-4 py-12 sm:px-6">
            <p class="text-sm font-medium text-muted">@yield('kode')</p>

            <h1 class="mt-2 text-2xl font-semibold text-primary sm:text-3xl">@yield('judul')</h1>

            <div class="mt-4 space-y-3 text-base text-secondary">
                @yield('penjelasan')
            </div>

            <div class="mt-8 flex flex-wrap gap-3">
                @yield('tindakan')
            </div>
        </main>
    </body>
</html>
