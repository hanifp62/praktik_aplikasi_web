<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Kurasi elevation_loss_m (titipan W3-R29; butir izin akhir 6; ruling W8-R23). Seeder dataset dan
 * data contoh menyalin tanjakan ke kolom turunan, padahal turunan sekali jalan naik dari basecamp
 * ke puncak selalu lebih kecil daripada tanjakannya: nilai yang sama persis adalah salinan, bukan
 * ukuran. Baris itu dikosongkan supaya rincian jalur tidak menampilkan turunan karangan (R-007).
 * Turunan dari GPX (TrailGeometryController::applyElevation) dan isian kurator yang berbeda dari
 * tanjakan tidak tersentuh.
 *
 * Tidak dapat dibalik: nilai salinannya memang tidak boleh kembali.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('trails')
            ->whereNotNull('elevation_loss_m')
            ->whereColumn('elevation_loss_m', 'elevation_gain_m')
            ->update(['elevation_loss_m' => null]);
    }

    public function down(): void
    {
        // Sengaja kosong: salinan tanjakan tidak dipulihkan.
    }
};
