<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Satu sesi mode lapangan per trip (permintaan izin akhir butir 8, ruling W6-R2).
 *
 * TripLifecycle::start() sudah memakai firstOrCreate, tetapi dua permintaan "Mulai" yang tiba
 * bersamaan dapat sama-sama tidak menemukan sesi lalu sama-sama membuatnya. Indeks unik
 * membuat basis data menolak sesi kedua. Aditif: indeks indeks_foreign_key yang sudah ada
 * pada kolom ini tidak disentuh.
 *
 * Sebelum dijalankan di Supabase (atas izin pemilik), pastikan tidak ada trip dengan dua sesi:
 * SELECT trip_plan_id, count(*) FROM hiking_sessions GROUP BY trip_plan_id HAVING count(*) > 1;
 * Tanpa baris hasil, migrasi ini aman; dengan baris hasil, migrasi gagal dan tidak mengubah apa pun.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hiking_sessions', function (Blueprint $table) {
            $table->unique('trip_plan_id', 'hiking_sessions_trip_plan_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('hiking_sessions', function (Blueprint $table) {
            $table->dropUnique('hiking_sessions_trip_plan_id_unique');
        });
    }
};
