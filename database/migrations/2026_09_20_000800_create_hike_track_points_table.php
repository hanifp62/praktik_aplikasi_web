<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jejak pendakian: titik demi titik, bukan satu posisi terakhir.
 *
 * hiking_sessions menyimpan last_known_location sebagai satu titik yang ditimpa setiap
 * pembaruan, sehingga tidak pernah ada jejak yang dapat digambar. Tabel ini yang
 * menyimpannya.
 *
 * Titiknya direkam di perangkat selama pendakian dan dikirim berkelompok setelah turun,
 * bukan dikirim satu per satu saat berjalan. Di hampir seluruh gunung Indonesia tidak
 * ada sinyal, dan jejak yang hanya berisi titik-titik yang kebetulan terkirim akan
 * menggambar garis lurus melintasi lembah yang tidak pernah dilalui siapa pun.
 *
 * recorded_at adalah waktu perangkat merekamnya, bukan waktu server menerimanya. Kedua
 * waktu itu dapat berjarak berjam-jam, dan yang membentuk jejak adalah yang pertama.
 *
 * Perekamannya bersifat pilihan dan dapat dihapus pemiliknya. Riwayat lokasi adalah data
 * paling pribadi yang disimpan aplikasi ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hike_track_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hiking_session_id')->constrained()->cascadeOnDelete();
            $table->timestamp('recorded_at');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->integer('elevation_m')->nullable();
            $table->unsignedSmallInteger('accuracy_m')->nullable();
            $table->timestamps();

            // Pengiriman susulan dapat terjadi dua kali untuk potongan yang sama ketika
            // sinyal putus di tengah unggahan. Kunci unik ini yang membuat pengiriman
            // ulang tidak menggandakan jejaknya.
            $table->unique(['hiking_session_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hike_track_points');
    }
};
