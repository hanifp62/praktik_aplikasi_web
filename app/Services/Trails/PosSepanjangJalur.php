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
 */
final class PosSepanjangJalur
{
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
                    ST_LineLocatePoint(t.geometry::geometry, c.location::geometry) * ST_Length(t.geometry) / 1000 AS km
             FROM checkpoints c
             JOIN trails t ON t.id = c.trail_id
             WHERE c.trail_id = ? AND c.location IS NOT NULL AND t.geometry IS NOT NULL',
            [$trail->id]
        );

        $hasil = [];

        foreach ($baris as $b) {
            if ($b->km !== null) {
                $hasil[(int) $b->id] = round((float) $b->km, 1);
            }
        }

        return $hasil;
    }
}
