<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migrasi data kunci butir izin (ruling W4-C7b; ruling W8-R24): butir tanpa templat berlabel lama
 * "Booking izin pendakian (SIMAKSI)" ditandai jenis PERMIT dan labelnya menjadi "Pesan izin
 * pendakian (SIMAKSI)". Status, catatan, dan waktu konfirmasinya tidak berubah. Dibalik dengan
 * mengembalikan label lama dan mengosongkan jenisnya.
 */
return new class extends Migration
{
    private const LABEL_LAMA = 'Booking izin pendakian (SIMAKSI)';

    private const LABEL_BARU = 'Pesan izin pendakian (SIMAKSI)';

    public function up(): void
    {
        DB::table('trip_preparation_items')
            ->whereNull('preparation_item_id')
            ->where('label', self::LABEL_LAMA)
            ->update(['kind' => 'PERMIT', 'label' => self::LABEL_BARU]);
    }

    public function down(): void
    {
        DB::table('trip_preparation_items')
            ->where('kind', 'PERMIT')
            ->update(['kind' => null, 'label' => self::LABEL_LAMA]);
    }
};
