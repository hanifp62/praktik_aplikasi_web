<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Koordinat checkpoint sebelumnya hanya hidup di dalam kolom geografi PostGIS.
 *
 * Akibatnya data itu tidak dapat dibaca, diuji, atau dipindahkan pada koneksi tanpa
 * PostGIS, dan setiap pembacaan menuntut SQL mentah. Kolom lintang/bujur biasa
 * menjadi sumber yang portabel; kolom `location` tetap ada untuk query dan index
 * spasial, dan keduanya ditulis bersamaan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checkpoints', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('name');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });

        // Pindahkan koordinat yang sudah tersimpan di kolom geografi.
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('UPDATE checkpoints SET latitude = ST_Y(location::geometry), longitude = ST_X(location::geometry) WHERE location IS NOT NULL');
        }
    }

    public function down(): void
    {
        Schema::table('checkpoints', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
