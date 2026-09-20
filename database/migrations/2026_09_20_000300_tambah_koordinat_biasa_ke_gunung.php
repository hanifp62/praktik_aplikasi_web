<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Koordinat gunung sebagai kolom biasa, di samping kolom geografi PostGIS.
 *
 * Checkpoint sudah memakai pola ini sejak awal; gunung tertinggal. Akibatnya koordinat
 * gunung hanya terbaca pada koneksi berkemampuan PostGIS, sehingga fitur yang
 * memerlukannya tidak dapat diuji sama sekali di suite yang berjalan di SQLite.
 *
 * Kolom geografi tetap yang dipakai untuk kueri spasial. Kolom biasa ini untuk dibaca.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mountains', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('name');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('UPDATE mountains SET latitude = ST_Y(location::geometry), longitude = ST_X(location::geometry) WHERE location IS NOT NULL');
        }
    }

    public function down(): void
    {
        Schema::table('mountains', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
