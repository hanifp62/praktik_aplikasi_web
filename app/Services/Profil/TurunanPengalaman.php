<?php

namespace App\Services\Profil;

use App\Enums\NavigationComplexity;
use App\Enums\NavigationExperience;
use App\Enums\TerrainCharacter;
use App\Models\Trail;
use App\Services\Trails\CariJalur;
use App\Services\Trails\TrailFigures;

/**
 * Turunan pengalaman dari jalur terberat yang pernah diselesaikan (DK-41; spec v3 bagian 6.16):
 * tanjakan, medan, dan navigasi jalur itu menjadi pengalaman yang dikonfirmasi pendaki. Angka
 * jalur yang belum tercatat tetap tidak diketahui, tidak menjadi nol (R-007). Tingkat
 * pengalaman tidak pernah diturunkan dari sini: tingkat diisi sendiri (BR-11).
 *
 * Navigasi yang diturunkan adalah navigasi yang dituntut jalur itu
 * (NavigationComplexity::expectedNavigationRank), satu-satunya tangga yang sama dengan penilai
 * kecocokan, jadi turunan ini tidak menambah aturan baru ke Route Fit.
 */
final class TurunanPengalaman
{
    /**
     * @return array{tanjakan_m: int|null, durasi_menit: int|null, medan: list<string>, navigasi: string|null}
     */
    public static function dariJalur(Trail $jalur): array
    {
        $durasi = $jalur->estimated_duration_minutes;

        return [
            'tanjakan_m' => $jalur->elevation_gain_m,
            'durasi_menit' => $durasi !== null && $durasi > 0 ? $durasi : null,
            'medan' => array_map(fn (TerrainCharacter $medan) => $medan->value, $jalur->terrainTypes()),
            'navigasi' => self::navigasi($jalur->navigation_complexity)?->value,
        ];
    }

    public static function navigasi(?NavigationComplexity $kompleksitas): ?NavigationExperience
    {
        if ($kompleksitas === null) {
            return null;
        }

        foreach (NavigationExperience::cases() as $navigasi) {
            if ($navigasi->rank() === $kompleksitas->expectedNavigationRank()) {
                return $navigasi;
            }
        }

        return null;
    }

    /**
     * Satu hasil pencarian jalur terberat: baris katalog, nilai turunan mentah (untuk "Ubah
     * nilai"), dan tulisannya untuk dikonfirmasi.
     *
     * @return array<string, mixed>
     */
    public static function baris(Trail $jalur): array
    {
        $turunan = self::dariJalur($jalur);
        $navigasi = self::navigasi($jalur->navigation_complexity);

        return [
            ...CariJalur::baris($jalur),
            'turunan' => $turunan,
            'tulisan' => [
                'tanjakan' => TrailFigures::meter($turunan['tanjakan_m']),
                'durasi' => TrailFigures::durasi($turunan['durasi_menit']),
                'medan' => $turunan['medan'] === [] ? 'Belum tercatat' : TrailFigures::medan($jalur),
                'navigasi' => $navigasi === null ? 'Belum tercatat' : $navigasi->label().': '.lcfirst($navigasi->keterangan()),
            ],
        ];
    }
}
