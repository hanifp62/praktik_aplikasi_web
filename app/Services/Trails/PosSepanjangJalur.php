<?php

namespace App\Services\Trails;

use App\Models\Trail;
use Illuminate\Support\Facades\DB;

/**
 * Jarak setiap pos dari awal jalur, diukur SEPANJANG garis rute, untuk daftar pos dan penanda
 * di profil elevasi (spec v3 bagian 6.4). Satu kueri untuk satu jalur.
 *
 * Posisi pos diproyeksikan ke garis rutenya (ST_LineLocatePoint), lalu pecahannya dikali
 * panjang geodesik garis itu. Jarak garis lurus antarpos tidak dipakai: di jalur berkelok ia
 * jauh lebih pendek dari yang ditempuh, dan angka yang tampak pasti tetapi keliru lebih buruk
 * daripada tanpa angka (R-005, R-007). Pos tanpa koordinat, jalur tanpa geometri, dan basis
 * data tanpa PostGIS (suite SQLite) tidak menghasilkan angka apa pun.
 *
 * Dua penjaga (titipan W3 T6-M8 dan m11; ruling W8-R21): pos yang lebih jauh dari garis
 * daripada hiking.map.batas_jarak_pos_m tidak diberi km, karena proyeksinya ke garis tidak
 * bermakna; dan bila km pos mundur menurut urutan pos, seluruh daftar tidak diberi km, karena
 * garis atau koordinat pos keliru dan tidak diketahui yang mana.
 */
final class PosSepanjangJalur
{
    /** Kelonggaran proyeksi dan pembulatan sebelum km dianggap mundur. */
    public const TOLERANSI_KM = 0.05;

    /**
     * @return array<int, float> km dari awal, berkunci id pos, satu desimal
     */
    public function km(Trail $trail): array
    {
        if (! Trail::spatialSupported()) {
            return [];
        }

        $baris = DB::select(
            'SELECT c.id,
                    ST_LineLocatePoint(t.geometry::geometry, c.location::geometry) * ST_Length(t.geometry) / 1000 AS km,
                    ST_Distance(t.geometry, c.location) AS jarak_m
             FROM checkpoints c
             JOIN trails t ON t.id = c.trail_id
             WHERE c.trail_id = ? AND c.location IS NOT NULL AND t.geometry IS NOT NULL
             ORDER BY c.sequence, c.id',
            [$trail->id]
        );

        return self::saring($baris, (float) config('hiking.map.batas_jarak_pos_m'));
    }

    /**
     * Penjaga jarak dan kemonotonan atas baris yang sudah urut menurut urutan pos.
     *
     * @param  array<int, object>  $baris  setiap baris berisi id, km, dan jarak_m (meter dari garis)
     * @return array<int, float>
     */
    public static function saring(array $baris, float $batasJarakMeter): array
    {
        $hasil = [];
        $terjauh = null;

        foreach ($baris as $b) {
            if ($b->km === null || $b->jarak_m === null || (float) $b->jarak_m > $batasJarakMeter) {
                continue;
            }

            $km = (float) $b->km;

            if ($terjauh !== null && $km + self::TOLERANSI_KM < $terjauh) {
                return [];
            }

            $terjauh = max($terjauh ?? $km, $km);
            $hasil[(int) $b->id] = round($km, 1);
        }

        return $hasil;
    }
}
