@props(['checkpoints', 'paces' => []])

@php
    use App\Support\GpxTrack;

    $pos = collect($checkpoints)->sortBy('sequence')->values();

    $tempo = collect($paces)->keyBy(fn ($t) => $t['dari'].'-'.$t['ke']);

    /*
     * Ditulis sebagai cabang eksplisit, bukan rtrim.
     *
     * rtrim memperlakukan argumen keduanya sebagai himpunan karakter, bukan akhiran,
     * sehingga "1 jam 0 menit" terkikis menjadi "1 ja".
     */
    $sebagaiWaktu = function (int $menit) {
        if ($menit < 60) {
            return $menit.' menit';
        }

        $jam = intdiv($menit, 60);
        $sisa = $menit % 60;

        return $sisa === 0 ? $jam.' jam' : $jam.' jam '.$sisa.' menit';
    };

    /*
     * Yang dihitung di sini adalah apa yang terjadi di ANTARA dua pos, karena di situlah
     * pertanyaan pendaki berada: bagian mana yang curam, dan seberapa jauh sampai pos
     * berikutnya. Daftar biasa hanya menjawab "pos ini ada di ketinggian berapa".
     *
     * Keduanya diturunkan dari data yang sudah tersimpan, bukan kolom baru. Jaraknya
     * memakai haversine lewat GpxTrack::lengthKm yang sudah dipakai mode pendakian,
     * supaya tidak ada rumus kedua yang bisa berbeda jawabannya.
     */
    $antara = $pos->map(function ($kini, $i) use ($pos) {
        $sebelum = $i === 0 ? null : $pos[$i - 1];

        if ($sebelum === null) {
            return null;
        }

        $beda = null;

        // Null bukan nol. "Naik 0 m" adalah pernyataan bahwa jalurnya datar, dan itu
        // bukan yang diketahui sistem ketika ketinggiannya memang belum dicatat.
        if ($sebelum->elevation_m !== null && $kini->elevation_m !== null) {
            $beda = $kini->elevation_m - $sebelum->elevation_m;
        }

        $km = null;

        if ($sebelum->latitude !== null && $sebelum->longitude !== null
            && $kini->latitude !== null && $kini->longitude !== null) {
            $km = GpxTrack::lengthKm([
                [(float) $sebelum->longitude, (float) $sebelum->latitude],
                [(float) $kini->longitude, (float) $kini->latitude],
            ]);
        }

        $bagian = [];

        if ($km !== null && $km >= 0.05) {
            $bagian[] = number_format($km, 1, ',', '.').' km';
        }

        if ($beda !== null && $beda !== 0) {
            $bagian[] = ($beda > 0 ? 'naik ' : 'turun ').number_format(abs($beda), 0, ',', '.').' m';
        }

        return $bagian === [] ? null : implode(' · ', $bagian);
    });
@endphp

<ol {{ $attributes }}>
    @foreach ($pos as $i => $titik)
        @if ($antara[$i] !== null)
            <li class="flex items-stretch gap-3" aria-hidden="true">
                {{-- Garis penghubung: hiasan yang mengulang urutan yang sudah terbaca
                     dari nomor pos dan dari struktur daftarnya sendiri. --}}
                <div class="flex w-7 justify-center">
                    <span class="w-px bg-subtle"></span>
                </div>
                <div class="py-1 text-xs">
                    {{-- Arah naik dan turun membawa arti, jadi ia mendapat ikon; ikonnya
                         aria-hidden karena teks di sebelahnya sudah menyebut arahnya, dan
                         seluruh barisnya memang sudah disembunyikan dari pembaca layar. --}}
                    <p class="flex items-center gap-1 text-secondary">
                        @if (str_contains($antara[$i], 'naik'))
                            <x-ui.icon name="arrow-up" class="h-3 w-3" />
                        @elseif (str_contains($antara[$i], 'turun'))
                            <x-ui.icon name="arrow-down" class="h-3 w-3" />
                        @endif
                        {{ $antara[$i] }}
                    </p>

                    {{--
                        Waktu tempuh dari rekaman pendaki, bukan dari rumus.

                        Yang disebut mediannya beserta rentang teramati, dan jumlah
                        rekamannya ikut ditulis supaya pembaca dapat menimbang sendiri
                        seberapa jauh angka itu dipercaya. Rentangnya ditampilkan karena
                        justru rentang itulah yang cakupannya dapat dipertanggungjawabkan
                        pada sampel sekecil ini.
                    --}}
                    @if ($t = $tempo->get($pos[$i - 1]->id.'-'.$pos[$i]->id))
                        <p class="mt-0.5 text-secondary">
                            Biasanya {{ $sebagaiWaktu($t['median_menit']) }},
                            terentang {{ $sebagaiWaktu($t['min_menit']) }} sampai {{ $sebagaiWaktu($t['maks_menit']) }}
                            <span class="text-muted">menurut {{ $t['rekaman'] }} rekaman pendaki</span>
                        </p>
                    @endif
                </div>
            </li>
        @endif

        <li class="flex items-start gap-3">
            <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-surface-sunken text-xs font-medium text-secondary">
                {{ $titik->sequence }}
            </span>

            <div class="min-w-0 text-sm">
                <p>
                    <span class="font-medium text-primary">{{ $titik->name }}</span>
                    <span class="text-secondary">&middot; {{ $titik->checkpoint_type->label() }}</span>
                    @if ($titik->elevation_m)
                        <span class="text-secondary">&middot; {{ number_format($titik->elevation_m, 0, ',', '.') }} mdpl</span>
                    @endif
                </p>

                @if ($titik->notes)
                    <p class="mt-0.5 text-secondary">{{ $titik->notes }}</p>
                @endif

                {{-- Padanan teks untuk pembaca layar, karena barisnya yang menyebut
                     kenaikan disembunyikan bersama garis penghubungnya. --}}
                @if ($antara[$i] !== null)
                    <p class="sr-only">Dari pos sebelumnya: {{ $antara[$i] }}.</p>
                @endif
            </div>
        </li>
    @endforeach
</ol>
