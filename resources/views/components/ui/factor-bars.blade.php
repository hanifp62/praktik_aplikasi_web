@props(['factors' => []])

@php
    $kuat = (float) config('hiking.route_fit.strong_factor_threshold');
    $lemah = (float) config('hiking.route_fit.weak_factor_threshold');

    /*
     * Tiga kategori, bukan persentase.
     *
     * BR-09 melarang skor internal sampai ke pengguna, dan batang selebar 73% dapat
     * dibaca balik menjadi angka sama persis seperti menuliskannya. Tiga kategori tidak
     * dapat, sekaligus justru lebih terbaca: yang ingin diketahui pendaki adalah faktor
     * mana yang menahannya, bukan selisih dua angka desimal.
     *
     * Ambangnya diambil dari config yang sudah dipakai isStrong() dan isWeak() di mesin,
     * bukan angka baru untuk tampilan. Kalau mesin berubah pendapat tentang apa yang
     * disebut kuat, batangnya ikut berubah tanpa ada yang perlu ingat menyesuaikannya.
     */
    $baris = collect($factors)
        ->filter(fn ($f) => isset($f['label']))
        ->map(function ($f) use ($kuat, $lemah) {
            $skor = (float) ($f['score'] ?? 0);

            // §95: faktor yang datanya belum ada tidak digambar sebagai batang pendek.
            // Batang pendek terbaca sebagai "jalur ini buruk pada faktor itu", padahal
            // yang benar adalah belum ada yang tahu.
            $kategori = match (true) {
                (bool) ($f['is_unknown'] ?? false) => 'tak-diketahui',
                $skor >= $kuat => 'kuat',
                $skor >= $lemah => 'cukup',
                default => 'lemah',
            };

            return [
                'label' => $f['label'],
                'detail' => $f['detail'] ?? null,
                'kategori' => $kategori,
                'kata' => match ($kategori) {
                    'kuat' => 'Mendukung',
                    'cukup' => 'Cukup',
                    'lemah' => 'Menahan',
                    default => 'Belum diketahui',
                },
                'lebar' => match ($kategori) {
                    'kuat' => 'w-full',
                    'cukup' => 'w-2/3',
                    'lemah' => 'w-1/3',
                    default => 'w-full',
                },
            ];
        });
@endphp

@if ($baris->isNotEmpty())
    <ul {{ $attributes->merge(['class' => 'space-y-3']) }}>
        @foreach ($baris as $f)
            <li>
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <span class="text-sm font-medium text-primary">{{ $f['label'] }}</span>

                    {{-- Warna tidak pernah menjadi satu-satunya pembawa arti (WCAG 1.4.1),
                         jadi katanya selalu tertulis di sebelah batangnya. --}}
                    <span @class([
                        'text-xs font-medium',
                        'text-brand-900' => $f['kategori'] === 'kuat',
                        'text-secondary' => $f['kategori'] === 'cukup',
                        'text-warn-900' => $f['kategori'] === 'lemah',
                        'text-muted' => $f['kategori'] === 'tak-diketahui',
                    ])>{{ $f['kata'] }}</span>
                </div>

                {{-- Batangnya disembunyikan dari pembaca layar: ia hanya mengulang kata
                     yang sudah terbaca di atasnya, dan membacakannya dua kali menambah
                     panjang tanpa menambah keterangan. --}}
                <div class="mt-1 h-2 w-full overflow-hidden rounded-full bg-subtle" aria-hidden="true">
                    <div @class([
                        'h-full rounded-full',
                        $f['lebar'],
                        'bg-brand-700' => $f['kategori'] === 'kuat',
                        'bg-muted' => $f['kategori'] === 'cukup',
                        'bg-warn-600' => $f['kategori'] === 'lemah',
                        'bg-subtle' => $f['kategori'] === 'tak-diketahui',
                    ])></div>
                </div>

                @if ($f['detail'])
                    <p class="mt-1 text-xs text-secondary">{{ $f['detail'] }}</p>
                @endif
            </li>
        @endforeach
    </ul>
@endif
