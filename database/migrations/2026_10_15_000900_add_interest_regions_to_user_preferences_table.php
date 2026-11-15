<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wilayah minat (DK-42; spec v3 bagian 9.3): larik JSON kode App\Enums\InterestRegion di
 * preferensi pendaki. Daftar putihnya ditegakkan validasi; kolomnya aditif dan boleh kosong,
 * karena kosong berarti pendaki belum memilih wilayah, bukan semua atau tidak satu pun.
 * Kolom teks bebas region_preference yang lama tidak disentuh.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_preferences', function (Blueprint $table) {
            $table->json('interest_regions')->nullable()->after('region_preference');
        });
    }

    public function down(): void
    {
        Schema::table('user_preferences', function (Blueprint $table) {
            $table->dropColumn('interest_regions');
        });
    }
};
