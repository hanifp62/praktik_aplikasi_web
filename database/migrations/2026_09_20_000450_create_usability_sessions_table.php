<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tempat menampung satu-satunya jenis bukti yang tidak dapat dihasilkan sistem sendiri:
 * pengamatan terhadap manusia yang memakainya.
 *
 * Protokolnya sudah ada sebagai dokumen, dan dokumen tidak menghitung apa pun. Ia tidak
 * tahu sudah berapa peserta, tidak menjumlahkan SUS, dan tidak memberi tahu berapa
 * peserta lagi yang dibutuhkan agar angkanya berarti. Tabel ini yang membuat protokol
 * itu dapat dijalankan dan dibaca hasilnya.
 *
 * Peserta disimpan sebagai kode, bukan nama. Yang dibutuhkan analisis hanyalah cara
 * membedakan satu peserta dari peserta lain.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usability_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('participant_code', 20);
            $table->string('kind', 20)->index();
            $table->string('experience_level', 20)->nullable();
            $table->foreignId('facilitator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('conducted_at');

            // Satu baris per sesi, hasil tugasnya di dalam json: jumlah tugasnya tetap
            // delapan dan tidak pernah dikueri satu per satu, jadi tabel anak hanya
            // menambah sambungan tanpa menambah kemampuan.
            $table->json('task_results')->nullable();
            $table->json('sus_answers')->nullable();

            // Disimpan meski dapat dihitung ulang: skor inilah yang dirujuk laporan,
            // dan rumus yang berubah di kemudian hari tidak boleh diam-diam menulis
            // ulang angka yang sudah pernah dilaporkan.
            $table->decimal('sus_score', 4, 1)->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('conducted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usability_sessions');
    }
};
