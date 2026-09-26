<?php

namespace App\Services\Trails;

use App\Models\Trail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Bentuk jalur untuk peta sebaran Jelajah, satu query untuk satu halaman hasil.
 *
 * Keputusan F2 (HANDOVER): peta digerbang data. Yang digambar adalah bentuk jalur itu
 * sendiri, bukan koordinat gunungnya. Titik gunung membuat semua jalur di satu gunung
 * bertumpuk di satu titik dan terbaca seolah jalurnya berada di sana. Jalur tanpa
 * geometri tidak tampil di peta sama sekali (features/01, "Hanya jalur yang punya
 * bentuk jalur yang tampil di peta").
 *
 * Geometri disederhanakan di basis data (toleransi sekitar 11 m) dan presisinya
 * dipotong enam desimal, supaya satu halaman hasil tetap ringan dikirim ke ponsel
 * bersinyal tipis. Di luar PostgreSQL (suite SQLite) hasilnya kosong: tidak ada
 * geometri yang dapat dibaca, dan mengarang titik justru yang dilarang (R-007).
 */
final class PetaSebaranJalur
{
    /** Toleransi penyederhanaan dalam derajat, sekitar 11 m di khatulistiwa. */
    private const TOLERANSI = 0.0001;

    /**
     * @param  Collection<int, Trail>  $trails
     * @return array<int, array{id: int, geometri: array<string, mixed>, awal: array{0: float, 1: float}}>
     */
    public function untuk(Collection $trails): array
    {
        if ($trails->isEmpty() || ! Trail::spatialSupported()) {
            return [];
        }

        $ids = $trails->pluck('id')->map(fn ($id) => (int) $id)->all();
        $tanda = implode(',', array_fill(0, count($ids), '?'));

        $baris = collect(DB::select(
            "SELECT id,
                    ST_AsGeoJSON(ST_Simplify(geometry::geometry, ?), 6) AS geojson,
                    ST_X(ST_StartPoint(geometry::geometry)) AS lng,
                    ST_Y(ST_StartPoint(geometry::geometry)) AS lat
             FROM trails
             WHERE id IN ({$tanda}) AND geometry IS NOT NULL",
            [self::TOLERANSI, ...$ids]
        ))->keyBy('id');

        // Urutan mengikuti daftar, supaya peta dan daftar menyebut jalur yang sama
        // dalam urutan yang sama.
        return collect($ids)
            ->map(fn (int $id) => $baris->get($id))
            ->filter(fn ($b) => $b !== null && $b->geojson !== null && $b->lng !== null && $b->lat !== null)
            ->map(fn ($b) => [
                'id' => (int) $b->id,
                'geometri' => json_decode($b->geojson, true),
                'awal' => [(float) $b->lng, (float) $b->lat],
            ])
            ->values()
            ->all();
    }
}
