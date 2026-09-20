@props(['profile'])

@php
    $titik = collect($profile)->filter(fn ($t) => isset($t['km'], $t['m']))->values();
@endphp

@if ($titik->count() >= 2)
    @php
        $kmMax = max($titik->max('km'), 0.001);
        $mMin = $titik->min('m');
        $mMax = $titik->max('m');
        $rentang = max($mMax - $mMin, 1);

        // Digambar sebagai SVG sebaris, bukan lewat pustaka grafik. Satu jalur kira-kira
        // seratus titik, dan memuat pustaka puluhan kilobyte untuk menggambar satu garis
        // membebani telepon di daerah bersinyal tipis justru untuk hiasan.
        $lebar = 1000;
        $tinggi = 220;

        $koordinat = $titik->map(function ($t) use ($kmMax, $mMin, $rentang, $lebar, $tinggi) {
            $x = ($t['km'] / $kmMax) * $lebar;
            $y = $tinggi - (($t['m'] - $mMin) / $rentang) * ($tinggi - 20) - 10;

            return round($x, 1).','.round($y, 1);
        })->implode(' ');

        $area = '0,'.$tinggi.' '.$koordinat.' '.$lebar.','.$tinggi;
    @endphp

    <figure {{ $attributes }}>
        <svg viewBox="0 0 {{ $lebar }} {{ $tinggi }}" preserveAspectRatio="none" role="img"
            class="h-40 w-full" aria-labelledby="profil-elevasi-judul">
            {{-- Grafik adalah gambar, dan pembaca layar membacakan judul serta rangkuman
                 di bawah ini alih-alih mencoba menelusuri seratus titiknya. --}}
            <title id="profil-elevasi-judul">
                Profil elevasi: dari {{ $mMin }} sampai {{ $mMax }} mdpl sepanjang
                {{ number_format($kmMax, 1, ',', '.') }} kilometer.
            </title>
            <polygon points="{{ $area }}" fill="rgb(var(--brand-100))" />
            <polyline points="{{ $koordinat }}" fill="none" stroke="rgb(var(--brand-700))"
                stroke-width="3" vector-effect="non-scaling-stroke" />
        </svg>

        <figcaption class="mt-2 flex flex-wrap justify-between gap-2 text-xs text-secondary">
            <span>{{ number_format($mMin, 0, ',', '.') }} mdpl terendah</span>
            <span>{{ number_format($mMax, 0, ',', '.') }} mdpl tertinggi</span>
            <span>{{ number_format($kmMax, 1, ',', '.') }} km</span>
        </figcaption>
    </figure>
@endif
