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
            $judulPenuh = ! $judul ? $merek : (str_contains($judul, $merek) ? $judul : $judul.' · '.$merek);
        @endphp
        <x-inertia::head>
            {{-- Format sama dengan callback title di resources/js/app.tsx. --}}
            <title>{{ $judulPenuh }}</title>
            @isset($page['props']['description'])
                <meta name="description" content="{{ $page['props']['description'] }}">
            @endisset
            @if (($page['component'] ?? null) === 'Welcome')
                <link rel="canonical" href="{{ url('/') }}">
                <meta property="og:type" content="website">
                <meta property="og:site_name" content="{{ $merek }}">
                <meta property="og:title" content="{{ $judulPenuh }}">
                @isset($page['props']['description'])
                    <meta property="og:description" content="{{ $page['props']['description'] }}">
                @endisset
                <meta property="og:url" content="{{ url('/') }}">
                <meta property="og:image" content="{{ url('/icons/app-512.png') }}">
                <meta property="og:locale" content="id_ID">
                <meta name="twitter:card" content="summary">
            @endif
        </x-inertia::head>

        <x-brand.head />

        {{-- Modul halaman ikut sebagai entri supaya potongannya dipramuat dari HTML (WP-2); lihat App\Support\PramuatHalaman. --}}
        {{ \App\Support\PramuatHalaman::tag($page['component']) }}
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
