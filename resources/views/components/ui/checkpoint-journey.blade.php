@props(['checkpoints'])

@php
    use App\Support\GpxTrack;

    $pos = collect($checkpoints)->sortBy('sequence')->values();

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
                    <span class="w-px bg-gray-300"></span>
                </div>
                <p class="py-1 text-xs text-gray-600">{{ $antara[$i] }}</p>
            </li>
        @endif

        <li class="flex items-start gap-3">
            <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-medium text-gray-700">
                {{ $titik->sequence }}
            </span>

            <div class="min-w-0 text-sm">
                <p>
                    <span class="font-medium text-gray-900">{{ $titik->name }}</span>
                    <span class="text-gray-600">&middot; {{ $titik->checkpoint_type->label() }}</span>
                    @if ($titik->elevation_m)
                        <span class="text-gray-600">&middot; {{ number_format($titik->elevation_m, 0, ',', '.') }} mdpl</span>
                    @endif
                </p>

                @if ($titik->notes)
                    <p class="mt-0.5 text-gray-600">{{ $titik->notes }}</p>
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
