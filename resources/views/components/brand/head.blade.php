@props(['fonts' => true])

{{--
    Kepala dokumen yang sama untuk setiap layout: skema warna, warna bilah peramban,
    ikon, manifest, dan huruf.

    Enam layout sebelumnya menyalin blok ini sendiri-sendiri, dan tiga di antaranya
    sudah berbeda satu sama lain. Satu komponen berarti tema gelap, ikon baru, atau
    pergantian penyedia huruf cukup dilakukan di satu tempat.

    Meta dirender server dari cookie tema, bukan disetel skrip: Livewire mengganti seluruh
    meta pada setiap wire:navigate, jadi mutasi dari JavaScript tertimpa di halaman
    berikutnya. Saat mengikuti sistem, dua theme-color ber-media membiarkan peramban
    memilih; saat tema dipilih eksplisit, satu theme-color tanpa media yang berlaku.
--}}
@php
    $tema = \App\Support\ThemePreference::fromRequest(request());
    $warna = config('brand.theme_color');
@endphp

<meta name="application-name" content="{{ config('app.name') }}">
<meta name="color-scheme" content="{{ $tema ?? 'light dark' }}">
@if ($tema)
    <meta name="theme-color" content="{{ $warna[$tema] }}">
@else
    <meta name="theme-color" content="{{ $warna['light'] }}" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="{{ $warna['dark'] }}" media="(prefers-color-scheme: dark)">
@endif

<link rel="manifest" href="/manifest.webmanifest">
<link rel="icon" href="/favicon.ico" sizes="32x32">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/icons/app-180.png">

@if ($fonts)
    {{-- Satu keluarga huruf dari asal aplikasi sendiri (DK-38). Subset Latin
         dipramuat karena hampir setiap halaman memakainya; subset Latin Extended
         hanya diunduh peramban bila halaman memuat glyph-nya. @font-face dan
         font-display: swap ada di resources/css/app.css, alasan pemilihannya di
         tailwind.config.js. --}}
    <link rel="preload" href="/fonts/plus-jakarta-sans/plus-jakarta-sans-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
@endif
