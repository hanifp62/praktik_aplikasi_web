@php
    // distance_km di-cast 'decimal:2', jadi nilai mentahnya string ("0.00"), dan
    // (bool) "0.00" adalah true di PHP -- hanya "" dan "0" yang falsy. Truthiness mentah
    // di sini berarti jalur berjarak nol tetap menulis "Jarak 0.00 km" di ringkasan
    // publik, seolah itu ukuran sungguhan. Dicast ke float dulu, baru diuji, sama seperti
    // pola yang dipakai trail-row.blade.php dan App\Http\Resources\TrailRowResource.
    $ringkas = $trail->name.' di '.$trail->mountain->name.', '.$trail->mountain->province.'. '
        .((float) $trail->distance_km ? 'Jarak '.$trail->distance_km.' km. ' : '')
        .($trail->elevation_gain_m ? 'Tanjakan '.number_format($trail->elevation_gain_m, 0, ',', '.').' m. ' : '')
        .'Karakteristik jalur, daftar pos, dan profil elevasi.';
@endphp

<!DOCTYPE html>
<html lang="id" {{ \App\Support\ThemePreference::htmlAttribute(request()) }}>
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

        {{--
            Kartu sosial. Halaman ini dibuat untuk ditemukan dan dibagikan, dan tautan
            tanpa kartu muncul sebagai URL telanjang di setiap tempat ia ditempel:
            grup pendakian, pesan langsung, papan rencana perjalanan.

            Gambarnya ikon aplikasi, bukan foto jalur: foto jalur berasal dari laporan
            komunitas yang membawa nama pelapornya, dan halaman publik tidak pernah
            menerbitkan data pribadi siapa pun.

            Yang berumur pendek tetap tidak ikut, sama seperti isi halamannya: status
            resmi dan prakiraan cuaca hanya ada di dalam aplikasi (§94, §95). Cuplikan
            pencarian hidup lebih lama daripada isinya, dan kartu sosial hidup lebih lama
            lagi, karena ia tersimpan di percakapan orang.
        --}}
        <x-brand.head :fonts="false" />
        <meta property="og:type" content="article">
        <meta property="og:site_name" content="{{ config('app.name') }}">
        <meta property="og:title" content="{{ $trail->name }} - {{ $trail->mountain->name }}">
        <meta property="og:description" content="{{ \Illuminate\Support\Str::limit($ringkas, 155) }}">
        <meta property="og:url" content="{{ route('public.trail', $trail) }}">
        <meta property="og:image" content="{{ url('/icons/app-512.png') }}">
        <meta property="og:locale" content="id_ID">
        <meta name="twitter:card" content="summary">

        @vite(['resources/css/app.css'])
    </head>
    <body class="min-h-dvh bg-canvas font-sans text-primary antialiased">
        <main class="mx-auto max-w-3xl space-y-8 px-4 py-10 sm:px-6">
            <header>
                <p class="text-kecil font-bold text-secondary">{{ $trail->mountain->name }}, {{ $trail->mountain->province }}</p>

                {{--
                    Data contoh (DK-35, tinjauan W1-9a #4): halaman publik tidak boleh
                    membuat data contoh terbaca sebagai data sungguhan, sama seperti
                    lencana React di LencanaDataContoh.tsx. Kelas dan token yang dipakai
                    persis recipe nada="netral" garis="putus" di Lencana.tsx.
                --}}
                @if (\App\Support\PenjagaDataContoh::sedangDisajikan())
                    <span class="mt-2 inline-flex items-center gap-1 rounded-lencana border border-dashed border-control bg-surface-sunken px-2 py-0.5 text-meta font-bold text-secondary">
                        Data contoh
                        <span class="sr-only">: isi halaman ini bukan data sungguhan</span>
                    </span>
                @endif

                {{-- Judul v3, sama dengan h1 PageHeader.tsx (W1-F2). --}}
                <h1 class="mt-1 text-balance text-judul text-primary">{{ $trail->name }}</h1>

                @if ($trail->description)
                    <p class="mt-3 text-isi text-secondary">{{ $trail->description }}</p>
                @endif
            </header>

            {{--
                Yang dipublikasikan hanya keterangan yang berumur panjang. Jarak dan
                elevation gain sebuah jalur berubah ketika datanya dikoreksi, bukan
                ketika keadaannya berubah, jadi cuplikan lama tidak pernah menjadi
                pernyataan yang keliru tentang hari ini.
            --}}
            <section>
                <h2 class="text-bagian text-primary">Karakteristik jalur</h2>

                <dl class="mt-4 grid grid-cols-2 gap-4 text-sm sm:grid-cols-3">
                    <div>
                        <dt class="text-muted">Jarak</dt>
                        <dd class="font-medium text-primary">{{ $trail->distance_km ?? '-' }} km</dd>
                    </div>
                    <div>
                        <dt class="text-muted">Tanjakan</dt>
                        <dd class="font-medium text-primary">
                            {{ $trail->elevation_gain_m ? number_format($trail->elevation_gain_m, 0, ',', '.') : '-' }} m
                        </dd>
                    </div>
                    <div>
                        <dt class="text-muted">Estimasi durasi</dt>
                        <dd class="font-medium text-primary">
                            {{ $trail->estimated_duration_minutes ? round($trail->estimated_duration_minutes / 60, 1).' jam' : '-' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-muted">Tingkat teknis</dt>
                        <dd class="font-medium text-primary">{{ $trail->technical_demand->label() }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted">Navigasi</dt>
                        <dd class="font-medium text-primary">{{ $trail->navigation_complexity->label() }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted">Ketinggian puncak</dt>
                        <dd class="font-medium text-primary">{{ $trail->mountain->elevation_mdpl }} mdpl</dd>
                    </div>
                </dl>
            </section>

            @if ($trail->elevation_profile)
                <section>
                    <h2 class="text-bagian text-primary">Profil elevasi</h2>
                    <x-ui.elevation-profile :profile="$trail->elevation_profile" class="mt-3" />
                </section>
            @endif

            @if ($trail->checkpoints->isNotEmpty())
                <section>
                    <h2 class="text-bagian text-primary">Pos</h2>
                    <x-ui.checkpoint-journey :checkpoints="$trail->checkpoints" class="mt-4 space-y-1" />
                </section>
            @endif

            {{--
                Ketiadaan status di halaman ini tidak boleh terbaca sebagai jalurnya
                terbuka. Yang disebut bukan permintaan mendaftar, melainkan ke mana
                status terkini dicari dan mengapa ia tidak ada di sini.
            --}}
            <section class="rounded-control border border-warn-300 bg-warn-50 p-4">
                <h2 class="text-subjudul text-warn-900">Sebelum berangkat</h2>
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
                <x-ui.button :href="route('login')" class="mt-3">Lihat status terkini di aplikasi</x-ui.button>
            </section>
        </main>
    </body>
</html>
