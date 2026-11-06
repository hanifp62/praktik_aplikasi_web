<?php

namespace App\Services\Trails;

use App\Enums\CheckpointType;
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

    /**
     * Daftar pos untuk rincian jalur dan ringkasan pendakian (spec v3 bagian 6.4): nomor,
     * nama, jenis, ketinggian, naik atau turun dari pos sebelumnya, jarak dari awal SEPANJANG
     * garis rute (PosSepanjangJalur), waktu dari awal sebagai perkiraan dari rekaman pendaki,
     * dan catatan pos.
     *
     * Waktu dari awal adalah RENTANG bersumber (DK-31): jumlah waktu tercepat dan terlama yang
     * teramati di setiap ruas sebelumnya, dari rekaman pendaki (CheckpointPaceService, yang
     * sudah menahan ruas dengan rekaman terlalu sedikit). Bila satu ruas saja belum punya
     * rekaman, waktu pos itu dan sesudahnya tidak ditulis: jumlah yang bolong terbaca lebih
     * cepat dari kenyataan (R-007). Jarak dari awal hanya ada bila garis rute dan koordinat pos
     * ada; jarak garis lurus tidak dipakai (R-005).
     *
     * @param  Collection<int, Checkpoint>  $checkpoints
     * @param  array<int, array{dari: int, ke: int, rekaman: int, median_menit: int, min_menit: int, maks_menit: int}>  $paces
     * @param  array<int, float>  $km  km dari awal, berkunci id pos
     * @return array<int, array{id: int, urutan: int, nama: string, jenis: string, aksen: string|null, elevasi: string|null, antara: string|null, km: string|null, waktu: string|null, catatan: string|null}>
     */
    public static function daftar(Collection $checkpoints, array $paces, array $km): array
    {
        $pos = $checkpoints->sortBy('sequence')->values();
        $tempo = collect($paces)->keyBy(fn (array $t) => $t['dari'].'-'.$t['ke']);
        $terakhir = $pos->last();
        $tercepat = 0;
        $terlama = 0;
        $utuh = true;
        $hasil = [];

        foreach ($pos as $i => $kini) {
            if ($i > 0) {
                $t = $tempo->get($pos[$i - 1]->id.'-'.$kini->id);
                $utuh = $utuh && $t !== null;
                $tercepat += $t['min_menit'] ?? 0;
                $terlama += $t['maks_menit'] ?? 0;
            }

            $hasil[] = [
                'id' => $kini->id,
                'urutan' => $kini->sequence,
                'nama' => $kini->name,
                'jenis' => $kini->checkpoint_type->label(),
                'aksen' => self::aksen($kini, $terakhir),
                'elevasi' => $kini->elevation_m !== null ? number_format($kini->elevation_m, 0, ',', '.').' m' : null,
                // Hanya dari ketinggian: jarak garis lurus antarpos tidak dipakai di sini.
                'antara' => $i === 0 ? null : self::beda($pos[$i - 1], $kini),
                'km' => array_key_exists($kini->id, $km) ? number_format($km[$kini->id], 1, ',', '.').' km' : null,
                'waktu' => $i > 0 && $utuh ? self::rentang($tercepat, $terlama).' dari awal' : null,
                'catatan' => $kini->notes,
            ];
        }

        return $hasil;
    }

    /**
     * Jumlah rekaman terkecil di antara ruas yang dipakai daftar() untuk menulis waktu dari
     * awal: ruas berurutan dari pos pertama sampai ruas pertama yang belum punya rekaman. Null
     * bila daftar tidak menulis satu waktu pun (T6-M1 tinjauan akhir W3): ruas sesudah celah
     * tidak tampil, jadi rekamannya tidak boleh ikut menentukan "paling sedikit N rekaman".
     *
     * @param  Collection<int, Checkpoint>  $checkpoints
     * @param  array<int, array{dari: int, ke: int, rekaman: int, median_menit: int, min_menit: int, maks_menit: int}>  $paces
     */
    public static function rekamanTerpakai(Collection $checkpoints, array $paces): ?int
    {
        $pos = $checkpoints->sortBy('sequence')->values();
        $tempo = collect($paces)->keyBy(fn (array $t) => $t['dari'].'-'.$t['ke']);
        $terkecil = null;

        for ($i = 1; $i < $pos->count(); $i++) {
            $t = $tempo->get($pos[$i - 1]->id.'-'.$pos[$i]->id);

            if ($t === null) {
                break;
            }

            $terkecil = $terkecil === null ? (int) $t['rekaman'] : min($terkecil, (int) $t['rekaman']);
        }

        return $terkecil;
    }

    /**
     * Pos yang membawa aksen kuning (DESIGN.md "Satu aksen", ruling W1-12b): area kemah, dan
     * tujuan, yaitu puncak atau pos terakhir. Aksen tidak pernah sendirian: pemanggil selalu
     * menggambarnya bersama bentuk dan label.
     */
    public static function aksen(Checkpoint $pos, ?Checkpoint $terakhir): ?string
    {
        return match (true) {
            $pos->checkpoint_type === CheckpointType::CAMPGROUND => 'kemah',
            $pos->checkpoint_type === CheckpointType::SUMMIT, $terakhir !== null && $pos->is($terakhir) => 'tujuan',
            default => null,
        };
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

        $beda = self::beda($sebelum, $kini);

        if ($beda !== null) {
            $bagian[] = $beda;
        }

        return $bagian === [] ? null : implode(' · ', $bagian);
    }

    private static function rentang(int $tercepat, int $terlama): string
    {
        return $tercepat === $terlama ? Durasi::panjang($tercepat) : Durasi::panjang($tercepat).' sampai '.Durasi::panjang($terlama);
    }

    /**
     * Naik atau turun dari pos sebelumnya. Null bukan nol: ketinggian yang belum dicatat
     * tidak menjadi "naik 0 m" (PRD §91).
     */
    private static function beda(Checkpoint $sebelum, Checkpoint $kini): ?string
    {
        if ($sebelum->elevation_m === null || $kini->elevation_m === null || $sebelum->elevation_m === $kini->elevation_m) {
            return null;
        }

        $beda = $kini->elevation_m - $sebelum->elevation_m;

        return ($beda > 0 ? 'naik ' : 'turun ').number_format(abs($beda), 0, ',', '.').' m';
    }
}
