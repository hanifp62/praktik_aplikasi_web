<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Karakteristik jalur pada saat penilaian dibuat.
 *
 * input_snapshot pada run menyimpan profil pendakinya, tetapi tidak dapat menyimpan
 * karakteristik jalur: satu run menilai banyak jalur sekaligus. Akibatnya kartu hasil
 * membaca jarak, elevation gain, dan durasi langsung dari tabel jalur, yaitu nilai
 * sekarang, sementara skor dan labelnya historis. Keduanya duduk dalam satu kotak tanpa
 * apa pun yang membedakan, dan itu lebih menyesatkan daripada kalau dua-duanya basi.
 *
 * Kolomnya boleh kosong, dan memang kosong untuk seluruh baris yang sudah ada. Baris
 * lama itu tidak dapat direkayasa ulang secara jujur, jadi ia jatuh kembali ke nilai
 * sekarang seperti perilaku sebelumnya, tanpa mengaku tahu yang tidak diketahuinya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recommendation_results', function (Blueprint $table) {
            $table->json('trail_snapshot')->nullable()->after('trail_id');
        });
    }

    public function down(): void
    {
        Schema::table('recommendation_results', function (Blueprint $table) {
            $table->dropColumn('trail_snapshot');
        });
    }
};
