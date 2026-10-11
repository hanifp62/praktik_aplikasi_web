<?php

namespace App\Services\Trails;

use App\Models\Checkpoint;
use Illuminate\Support\Collection;

/**
 * Profil elevasi satu jalur untuk rinciannya (spec v3 bagian 6.4, prototype 04): titik yang
 * dijarangkan, bagian tercuram yang diarsir, penanda pos yang jaraknya diketahui, dan satu
 * kalimat keterangan. Kalimatnya dibentuk di server supaya grafik punya padanan teks yang
 * sama untuk semua pembaca (WCAG 1.1.1), bukan hanya "tertinggi" dan "terendah".
 *
 * Tanpa dua titik sah tidak ada profil (R-007). Penanda pos hanya untuk pos yang jaraknya
 * dari awal diukur sepanjang garis rute dan berada di dalam rentang profil; pos lain tidak
 * ditaruh di tempat tebakan.
 */
final class ProfilElevasiJalur
{
    /** Profil diambil ulang di 20 jarak yang sama untuk mencari bagian tercuram. */
    private const SAMPEL = 20;

    /** Lebar bagian tercuram: seperempat panjang jalur (5 dari 20 sampel). */
    private const JENDELA = 5;

    /**
     * @param  array<int, mixed>|null  $profil
     * @param  Collection<int, Checkpoint>  $checkpoints
     * @param  array<int, float>  $posKm  km dari awal per id pos (PosSepanjangJalur)
     * @return array{titik: array<int, array{0: float, 1: float}>, tercuram: array{dariKm: float, sampaiKm: float, naik: int}|null, pos: array<int, array{urutan: int, nama: string, km: float, aksen: string|null}>, keterangan: string}|null
     */
    public static function untuk(?array $profil, Collection $checkpoints, array $posKm): ?array
    {
        $titik = SkalaProfil::titik($profil);

        if ($titik === null) {
            return null;
        }

        $tercuram = self::tercuram($titik);
        $urut = $checkpoints->sortBy('sequence')->values();
        $terakhir = $urut->last();
        $kmAwal = $titik[0][0];
        $kmAkhir = end($titik)[0];

        return [
            'titik' => SkalaProfil::jarangkan(array_map(fn (array $t) => [round($t[0], 2), round($t[1])], $titik)),
            'tercuram' => $tercuram,
            // Penanda di luar rentang profil tidak digambar di luar grafik (T6-M9, R-005): garis
            // rute dan profil bisa berasal dari rekaman yang panjangnya berbeda.
            'pos' => $urut
                ->filter(fn (Checkpoint $p) => array_key_exists($p->id, $posKm) && $posKm[$p->id] >= $kmAwal && $posKm[$p->id] <= $kmAkhir)
                ->map(fn (Checkpoint $p) => ['urutan' => $p->sequence, 'nama' => $p->name, 'km' => $posKm[$p->id], 'aksen' => CheckpointJourney::aksen($p, $terakhir)])
                ->values()
                ->all(),
            'keterangan' => self::keterangan($titik, $tercuram),
        ];
    }

    /**
     * Bagian tercuram: jendela seperempat panjang jalur dengan kenaikan terbesar, dari profil
     * yang diambil ulang di jarak yang sama. Jendela, bukan satu ruas rekaman, karena satu ruas
     * GPS yang pendek dapat tampak curam tanpa arti bagi pendaki. Tanpa kenaikan, tidak ada.
     *
     * @param  array<int, array{0: float, 1: float}>  $titik
     * @return array{dariKm: float, sampaiKm: float, naik: int}|null
     */
    public static function tercuram(array $titik): ?array
    {
        $awal = $titik[0][0];
        $panjang = end($titik)[0] - $awal;
        $sampel = [];

        for ($i = 0; $i <= self::SAMPEL; $i++) {
            $km = $awal + $panjang * $i / self::SAMPEL;
            $sampel[] = [$km, self::tinggiPada($titik, $km)];
        }

        $terbaik = null;

        for ($i = 0; $i + self::JENDELA <= self::SAMPEL; $i++) {
            $naik = $sampel[$i + self::JENDELA][1] - $sampel[$i][1];

            if ($terbaik === null || $naik > $terbaik[1]) {
                $terbaik = [$i, $naik];
            }
        }

        if ($terbaik === null || $terbaik[1] < 1) {
            return null;
        }

        return [
            'dariKm' => round($sampel[$terbaik[0]][0], 1),
            'sampaiKm' => round($sampel[$terbaik[0] + self::JENDELA][0], 1),
            'naik' => (int) round($terbaik[1]),
        ];
    }

    /**
     * @param  array<int, array{0: float, 1: float}>  $titik
     */
    private static function tinggiPada(array $titik, float $km): float
    {
        foreach ($titik as $i => [$x, $y]) {
            if ($x >= $km) {
                if ($i === 0) {
                    return $y;
                }

                [$x0, $y0] = $titik[$i - 1];

                return $x === $x0 ? $y : $y0 + ($y - $y0) * ($km - $x0) / ($x - $x0);
            }
        }

        return end($titik)[1];
    }

    /**
     * @param  array<int, array{0: float, 1: float}>  $titik
     * @param  array{dariKm: float, sampaiKm: float, naik: int}|null  $tercuram
     */
    private static function keterangan(array $titik, ?array $tercuram): string
    {
        $angka = fn (float $n, int $desimal = 0) => number_format($n, $desimal, ',', '.');
        $panjang = end($titik)[0] - $titik[0][0];
        $tertinggi = max(array_column($titik, 1));
        $kalimat = 'Dari '.$angka($titik[0][1]).' m sampai titik tertinggi '.$angka($tertinggi).' m dalam '.$angka($panjang, 1).' km.';

        if ($tercuram !== null) {
            $kalimat .= ' Bagian tercuram, km '.$angka($tercuram['dariKm'], 1).' sampai '.$angka($tercuram['sampaiKm'], 1)
                .', naik '.$angka($tercuram['naik']).' m dalam '.$angka($tercuram['sampaiKm'] - $tercuram['dariKm'], 1).' km (diarsir).';
        }

        return $kalimat;
    }
}
