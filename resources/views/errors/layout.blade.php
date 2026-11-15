@php
    $galat = \App\Support\HalamanGalat::isi($kode, $exception ?? null, request());
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" {{ \App\Support\ThemePreference::htmlAttribute(request()) }}>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $galat['judul'] }} · {{ config('app.name') }}</title>
        <x-brand.head :fonts="false" />

        {{--
            Halaman ini dirender justru ketika sesuatu sedang rusak, jadi ia tidak menyentuh
            basis data, sesi, maupun pengguna. Kata-katanya dari App\Support\HalamanGalat,
            sumber yang sama dengan halaman React Galat untuk kunjungan Inertia (W9). Huruf
            tidak dipramuat: kalau jaringannya yang bermasalah, menunggunya hanya menambah
            waktu sebelum pengguna membaca apa yang terjadi.
        --}}
        @vite(['resources/css/app.css'])
    </head>
    <body class="min-h-dvh bg-canvas font-sans text-primary antialiased">
        <main id="konten" class="mx-auto flex min-h-dvh max-w-xl flex-col justify-center px-5 py-12">
            <a href="{{ url('/') }}" class="inline-flex min-h-11 items-center self-start rounded-kecil text-[1.0625rem] font-bold uppercase leading-none tracking-[0.06em] text-logo-type focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2">{{ config('app.name') }}</a>

            <p class="mt-10 text-meta font-bold text-muted">{{ $galat['label'] }}</p>
            <h1 class="mt-2 text-judul text-primary">{{ $galat['judul'] }}</h1>

            <div class="mt-4 space-y-3 text-isi text-secondary">
                @foreach ($galat['penjelasan'] as $kalimat)
                    <p>{{ $kalimat }}</p>
                @endforeach
            </div>

            <div class="mt-8 flex flex-wrap gap-3">
                @foreach ($galat['tindakan'] as $tindakan)
                    <x-ui.button :href="$tindakan['href']" :variant="$tindakan['utama'] ? 'primary' : 'secondary'">{{ $tindakan['label'] }}</x-ui.button>
                @endforeach
            </div>
        </main>
    </body>
</html>
