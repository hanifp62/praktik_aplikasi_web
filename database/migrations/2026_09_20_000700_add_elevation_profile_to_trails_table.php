<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Profil elevasi jalur: jarak tempuh terhadap ketinggian.
 *
 * Disimpan di kolom sendiri, bukan diturunkan dari geometri, karena LineString PostGIS
 * yang dipakai aplikasi ini dua dimensi dan ketinggiannya tidak ikut masuk ke sana.
 * Sumbernya tag ele pada berkas GPX yang diunggah, yang sebelumnya dibuang tepat di
 * titik ia masuk.
 *
 * Isinya sudah diencerkan ke seratusan titik. Pengenceran itu sah justru karena gunanya
 * berbeda dari garis jalur: garis dipakai bernavigasi sehingga tikungannya tidak boleh
 * dipotong, sedangkan profil dipakai membaca bentuk tanjakan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trails', function (Blueprint $table) {
            $table->json('elevation_profile')->nullable()->after('elevation_loss_m');
        });
    }

    public function down(): void
    {
        Schema::table('trails', function (Blueprint $table) {
            $table->dropColumn('elevation_profile');
        });
    }
};
