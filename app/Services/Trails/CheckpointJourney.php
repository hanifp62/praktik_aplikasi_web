<?php

namespace App\Services\Trails;

use App\Models\Checkpoint;
use App\Support\Durasi;
use App\Support\GpxTrack;
use Illuminate\Support\Collection;

/**
 * Pos sebagai perjalanan, bukan daftar: apa yang terjadi di ANTARA dua pos, karena di
 * situlah pertanyaan pendaki berada (bagian mana yang curam, seberapa jauh sampai pos
 * berikutnya). Dipindahkan dari komponen Blade checkpoint-journey supaya halaman React
 * menerima bentuk yang sudah jadi; klien hanya menggambar.
 *
 * Jarak memakai haversine lewat GpxTrack::lengthKm yang sudah dipakai mode pendakian,
 * supaya tidak ada rumus kedua yang bisa berbeda jawabannya. Null bukan nol: "naik 0 m"
 * adalah pernyataan bahwa jalurnya datar, dan itu bukan yang diketahui sistem ketika
 * ketinggiannya belum dicatat (PRD §91).
 */
class CheckpointJourney
{
    /**
     * @param  Collection<int, Checkpoint>  $checkpoints
     * @param  array<int, array{dari: int, ke: int, rekaman: int, median_menit: int, min_menit: int, maks_menit: int}>  $paces
     * @return array<int, array{id: int, sequence: int, name: string, typeLabel: string, elevasi: ?string, notes: ?string, antara: ?string, tempo: ?string}>
     */
    public static function susun(Collection $checkpoints, array $paces): array
    {
        $pos = $checkpoints->sortBy('sequence')->values();
        $tempo = collect($paces)->keyBy(fn (array $t) => $t['dari'].'-'.$t['ke']);

        return $pos->map(function (Checkpoint $kini, int $i) use ($pos, $tempo) {
            $sebelum = $i === 0 ? null : $pos[$i - 1];
            $antara = $sebelum === null ? null : self::antara($sebelum, $kini);
            $t = $sebelum === null ? null : $tempo->get($sebelum->id.'-'.$kini->id);

            return [
                'id' => $kini->id,
                'sequence' => $kini->sequence,
                'name' => $kini->name,
                'typeLabel' => $kini->checkpoint_type->label(),
                'elevasi' => $kini->elevation_m ? number_format($kini->elevation_m, 0, ',', '.').' mdpl' : null,
                'notes' => $kini->notes,
                'antara' => $antara,
                // Median beserta rentang teramati dan jumlah rekamannya, supaya pembaca
                // dapat menimbang sendiri seberapa jauh angka itu dipercaya.
                'tempo' => ($antara !== null && $t !== null)
                    ? 'Biasanya '.Durasi::panjang($t['median_menit']).', terentang '.Durasi::panjang($t['min_menit'])
                        .' sampai '.Durasi::panjang($t['maks_menit']).' menurut '.$t['rekaman'].' rekaman pendaki'
                    : null,
            ];
        })->all();
    }

    private static function antara(Checkpoint $sebelum, Checkpoint $kini): ?string
    {
        $bagian = [];

        if ($sebelum->latitude !== null && $sebelum->longitude !== null
            && $kini->latitude !== null && $kini->longitude !== null) {
            $km = GpxTrack::lengthKm([
                [(float) $sebelum->longitude, (float) $sebelum->latitude],
                [(float) $kini->longitude, (float) $kini->latitude],
            ]);

            if ($km >= 0.05) {
                $bagian[] = number_format($km, 1, ',', '.').' km';
            }
        }

        if ($sebelum->elevation_m !== null && $kini->elevation_m !== null) {
            $beda = $kini->elevation_m - $sebelum->elevation_m;

            if ($beda !== 0) {
                $bagian[] = ($beda > 0 ? 'naik ' : 'turun ').number_format(abs($beda), 0, ',', '.').' m';
            }
        }

        return $bagian === [] ? null : implode(' · ', $bagian);
    }
}
