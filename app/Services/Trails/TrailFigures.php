<?php

namespace App\Services\Trails;

use App\Models\Trail;
use App\Support\Durasi;

/**
 * Angka jalur untuk halaman rinci dan tabel banding, dengan satu cara menulis.
 *
 * Nilai yang tidak ada ditulis "Belum tersedia", bukan "-" atau nol: keterangan jujur
 * bahwa datanya belum dimasukkan, bukan angka yang terbaca seperti nilai (PRD §91,
 * R-007). Pemformatannya sama dengan TrailRowResource (koma desimal, titik ribuan),
 * supaya "8,0 km" di daftar, di rincian, dan di tabel banding adalah angka yang sama.
 */
final class TrailFigures
{
    public const BELUM = 'Belum tersedia';

    public static function jarak(mixed $km): string
    {
        return $km !== null ? number_format((float) $km, 1, ',', '.').' km' : self::BELUM;
    }

    public static function meter(?int $meter): string
    {
        return $meter !== null ? number_format($meter, 0, ',', '.').' m' : self::BELUM;
    }

    /**
     * Nol diperlakukan sebagai belum diketahui, sama seperti Durasi: durasi nol berarti
     * tidak ada yang pernah mengisinya.
     */
    public static function durasi(?int $menit): string
    {
        return ($menit === null || $menit <= 0) ? self::BELUM : Durasi::panjang($menit);
    }

    /**
     * distance_km di-cast 'decimal:2', jadi nilai mentahnya string ("0.00") yang truthy
     * di PHP. Dicast ke float dulu, baru diuji: jalur berjarak nol tidak boleh membagi
     * dengan 0.0 dan menjatuhkan halaman.
     */
    public static function kecuraman(Trail $trail): string
    {
        $jarak = $trail->distance_km !== null ? (float) $trail->distance_km : null;
        $naik = $trail->elevation_gain_m;

        return ($jarak && $naik)
            ? number_format(round($naik / $jarak), 0, ',', '.').' m/km'
            : self::BELUM;
    }

    public static function medan(Trail $trail): string
    {
        return collect($trail->terrainTypes())->map->label()->join(', ') ?: self::BELUM;
    }

    /**
     * Baris ukuran padat untuk daftar ("9,7 km · ↑1.650 m · 7,5 j"): titik tengah hanya di
     * baris ukuran seperti ini (spec v3 bagian 8.8). Angka yang tidak ada tidak ditulis nol,
     * dan baris yang sebagian angkanya belum ada mengakuinya (PRD §95).
     */
    public static function ringkas(Trail $trail): string
    {
        $bagian = array_values(array_filter([
            $trail->distance_km !== null ? number_format((float) $trail->distance_km, 1, ',', '.').' km' : null,
            $trail->elevation_gain_m !== null ? '↑'.number_format($trail->elevation_gain_m, 0, ',', '.').' m' : null,
            ($trail->estimated_duration_minutes ?? 0) > 0 ? Durasi::pendek($trail->estimated_duration_minutes) : null,
        ]));

        if ($bagian === []) {
            return 'Angka jalur belum tersedia';
        }

        return implode(' · ', $bagian).(count($bagian) < 3 ? ' · sebagian angka belum tersedia' : '');
    }
}
