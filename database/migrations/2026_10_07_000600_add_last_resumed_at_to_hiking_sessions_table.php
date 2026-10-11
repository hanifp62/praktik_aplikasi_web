<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Waktu lanjut terakhir sesi mode lapangan (ruling W6-10f bagian a, menutup W6-3aa).
 *
 * Antrean aksi perangkat dapat memutar ulang pasangan jeda dan lanjut yang sudah diterima,
 * misalnya dari tab lain yang masih memegangnya di memori. Tanpa kolom ini, jeda yang diputar
 * ulang sesudah lanjut memulai jeda baru dengan waktu lama, dan lanjut berikutnya menjumlahkan
 * lamanya dua kali ke paused_seconds. Lanjut menulis kolom ini, dan jeda yang waktunya tidak lebih
 * baru diabaikan (HikingSession::mulaiJeda). Aditif dan boleh kosong: sesi lama belum pernah
 * dilanjutkan menurut kolom ini, jadi jedanya diterima seperti sebelumnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hiking_sessions', function (Blueprint $table) {
            $table->timestamp('last_resumed_at')->nullable()->after('paused_seconds');
        });
    }

    public function down(): void
    {
        Schema::table('hiking_sessions', function (Blueprint $table) {
            $table->dropColumn('last_resumed_at');
        });
    }
};
