<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" {{ \App\Support\ThemePreference::htmlAttribute(request()) }} {{ \App\Support\DisplayPreference::htmlAttributes(request()) }}>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{--
            Templat akar untuk halaman React (Inertia). Kepala dokumen, tema, dan ikon
            datang dari komponen yang sama dengan layout Livewire, jadi kedua dunia tidak
            bisa berbeda selama migrasi.

            Judul: setiap halaman mengirim prop `title`; tanpa SSR, isi slot ini yang
            dibaca peramban, tab, riwayat, dan mesin pencari sebelum React memasang
            judulnya sendiri.
        --}}
        @php
            $judul = $page['props']['title'] ?? null;
            $merek = config('app.name');
        @endphp
        <x-inertia::head>
            {{-- Format sama dengan callback title di resources/js/app.tsx. --}}
            <title>{{ ! $judul ? $merek : (str_contains($judul, $merek) ? $judul : $judul.' · '.$merek) }}</title>
            @isset($page['props']['description'])
                <meta name="description" content="{{ $page['props']['description'] }}">
            @endisset
        </x-inertia::head>

        <x-brand.head />

        @vite(['resources/css/app.css', 'resources/js/app.tsx'])
    </head>
    <body class="font-sans text-primary antialiased">
        {{-- WCAG 2.4.1, di luar React supaya sudah ada sebelum skrip berjalan. --}}
        <a href="#konten"
            class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50
                focus:rounded-control focus:bg-brand-700 focus:px-4 focus:py-2 focus:text-sm
                focus:font-medium focus:text-on-brand">
            Lewati ke konten utama
        </a>

        <x-inertia::app />
    </body>
</html>
