<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ucapan terima kasih pada laporan kondisi.
 *
 * Menggantikan papan peringkat kontribusi yang sempat direncanakan dan dibatalkan
 * setelah risetnya. Papan peringkat memberi hadiah pada jumlah, dan memberi hadiah pada
 * jumlah laporan menghasilkan laporan bervolume tinggi bermutu rendah, yang menyerang
 * persis model kepercayaan data yang menjadi nilai produk ini.
 *
 * Yang dihargai di sini kegunaannya, dan yang menilai kegunaan itu pendaki lain yang
 * membacanya sebelum berangkat.
 *
 * Kunci uniknya bukan kerapian belaka: tanpa itu, satu orang dapat menaikkan angka
 * sebuah laporan sendirian, dan angka yang dapat dinaikkan sendirian tidak mengukur
 * apa-apa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_thanks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trail_condition_report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['trail_condition_report_id', 'user_id'], 'report_thanks_unik');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_thanks');
    }
};
