@php
    $ringkas = $trail->name.' di '.$trail->mountain->name.', '.$trail->mountain->province.'. '
        .($trail->distance_km ? 'Jarak '.$trail->distance_km.' km. ' : '')
        .($trail->elevation_gain_m ? 'Elevation gain '.number_format($trail->elevation_gain_m, 0, ',', '.').' m. ' : '')
        .'Karakteristik jalur, daftar pos, dan profil elevasi.';
@endphp

<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{--
            Judul dan ringkasan sendiri. Mesin pencari membacanya sebelum apa pun yang
            lain, dan halaman tanpa keduanya muncul sebagai nama aplikasi berulang-ulang
            di hasil pencarian.
        --}}
        <title>{{ $trail->name }} - {{ $trail->mountain->name }}</title>
        <meta name="description" content="{{ \Illuminate\Support\Str::limit($ringkas, 155) }}">
        <link rel="canonical" href="{{ route('public.trail', $trail) }}">

        @vite(['resources/css/app.css'])
    </head>
    <body class="min-h-screen bg-white font-sans text-gray-800 antialiased">
        <main class="mx-auto max-w-3xl space-y-8 px-4 py-10 sm:px-6">
            <header>
                <p class="text-sm font-medium text-gray-500">{{ $trail->mountain->name }} &middot; {{ $trail->mountain->province }}</p>
                <h1 class="mt-1 text-3xl font-semibold text-gray-900">{{ $trail->name }}</h1>

                @if ($trail->description)
                    <p class="mt-3 text-base text-gray-700">{{ $trail->description }}</p>
                @endif
            </header>

            {{--
                Yang dipublikasikan hanya keterangan yang berumur panjang. Jarak dan
                elevation gain sebuah jalur berubah ketika datanya dikoreksi, bukan
                ketika keadaannya berubah, jadi cuplikan lama tidak pernah menjadi
                pernyataan yang keliru tentang hari ini.
            --}}
            <section>
                <h2 class="text-lg font-semibold text-gray-900">Karakteristik jalur</h2>

                <dl class="mt-4 grid grid-cols-2 gap-4 text-sm sm:grid-cols-3">
                    <div>
                        <dt class="text-gray-500">Jarak</dt>
                        <dd class="font-medium text-gray-900">{{ $trail->distance_km ?? '-' }} km</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Elevation gain</dt>
                        <dd class="font-medium text-gray-900">
                            {{ $trail->elevation_gain_m ? number_format($trail->elevation_gain_m, 0, ',', '.') : '-' }} m
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Estimasi durasi</dt>
                        <dd class="font-medium text-gray-900">
                            {{ $trail->estimated_duration_minutes ? round($trail->estimated_duration_minutes / 60, 1).' jam' : '-' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Tingkat teknis</dt>
                        <dd class="font-medium text-gray-900">{{ $trail->technical_demand->label() }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Navigasi</dt>
                        <dd class="font-medium text-gray-900">{{ $trail->navigation_complexity->label() }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Ketinggian puncak</dt>
                        <dd class="font-medium text-gray-900">{{ $trail->mountain->elevation_mdpl }} mdpl</dd>
                    </div>
                </dl>
            </section>

            @if ($trail->elevation_profile)
                <section>
                    <h2 class="text-lg font-semibold text-gray-900">Profil elevasi</h2>
                    <x-ui.elevation-profile :profile="$trail->elevation_profile" class="mt-3" />
                </section>
            @endif

            @if ($trail->checkpoints->isNotEmpty())
                <section>
                    <h2 class="text-lg font-semibold text-gray-900">Pos</h2>
                    <x-ui.checkpoint-journey :checkpoints="$trail->checkpoints" class="mt-4 space-y-1" />
                </section>
            @endif

            {{--
                Ketiadaan status di halaman ini tidak boleh terbaca sebagai jalurnya
                terbuka. Yang disebut bukan permintaan mendaftar, melainkan ke mana
                status terkini dicari dan mengapa ia tidak ada di sini.
            --}}
            <section class="rounded-md border border-warn-300 bg-warn-50 p-4">
                <h2 class="text-base font-semibold text-warn-900">Sebelum berangkat</h2>
                <p class="mt-2 text-sm text-warn-900">
                    Halaman ini sengaja tidak memuat <strong>status resmi terkini</strong> maupun
                    prakiraan cuaca. Keduanya berubah dari hari ke hari, sedangkan halaman yang
                    tersimpan di mesin pencari tidak ikut berubah, dan status lama yang terbaca
                    seperti keadaan sekarang lebih berbahaya daripada tidak ada status sama sekali.
                </p>
                <p class="mt-2 text-sm text-warn-900">
                    Periksa status resmi dan prakiraan terbaru di dalam aplikasi, atau tanyakan
                    langsung kepada pengelola kawasan dan basecamp.
                </p>
                <a href="{{ route('login') }}"
                    class="mt-3 inline-flex min-h-11 items-center rounded-control bg-brand-700 px-4 py-2 text-sm font-medium text-white hover:bg-brand-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2">
                    Lihat status terkini di aplikasi
                </a>
            </section>
        </main>
    </body>
</html>
