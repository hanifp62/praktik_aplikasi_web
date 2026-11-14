<?php

use App\Enums\NavigationComplexity;
use App\Enums\TechnicalDemand;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * R-006 dan R-007 di skema (titipan W1 dan W3; butir izin akhir 6; ruling W8-R22): tuntutan
 * teknis, kerumitan navigasi, dan ketersediaan area kemah tidak lagi berisi nilai bawaan yang
 * menyamar sebagai data. Sebelumnya jalur yang dibuat tanpa nilai itu tercatat Sedang, Sedang,
 * dan tanpa area kemah, lalu tampil "Berkemah: Tidak" di halaman publik /b/. Sesudahnya kolom
 * yang tidak diisi bernilai kosong, dan layar menulis "Belum tersedia".
 *
 * Nilai yang sudah ada tidak diubah: migrasi tidak dapat membedakan nilai kurasi dari nilai
 * bawaan, jadi kurasinya keputusan pemilik (butir izin akhir 6). Segmen jalur ikut, dengan
 * alasan yang sama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trails', function (Blueprint $table) {
            $table->string('technical_demand')->nullable()->default(null)->change();
            $table->string('navigation_complexity')->nullable()->default(null)->change();
            $table->boolean('camping_available')->nullable()->default(null)->change();
        });

        Schema::table('trail_segments', function (Blueprint $table) {
            $table->string('technical_demand')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        DB::table('trails')->whereNull('technical_demand')->update(['technical_demand' => TechnicalDemand::MODERATE->value]);
        DB::table('trails')->whereNull('navigation_complexity')->update(['navigation_complexity' => NavigationComplexity::MODERATE->value]);
        DB::table('trails')->whereNull('camping_available')->update(['camping_available' => false]);
        DB::table('trail_segments')->whereNull('technical_demand')->update(['technical_demand' => TechnicalDemand::MODERATE->value]);

        Schema::table('trails', function (Blueprint $table) {
            $table->string('technical_demand')->nullable(false)->default(TechnicalDemand::MODERATE->value)->change();
            $table->string('navigation_complexity')->nullable(false)->default(NavigationComplexity::MODERATE->value)->change();
            $table->boolean('camping_available')->nullable(false)->default(false)->change();
        });

        Schema::table('trail_segments', function (Blueprint $table) {
            $table->string('technical_demand')->nullable(false)->default(TechnicalDemand::MODERATE->value)->change();
        });
    }
};
