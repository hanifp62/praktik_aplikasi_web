<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jalur yang sedang ditimbang seorang pendaki.
 *
 * Tahap antara menjelajah dan memutuskan, yang selama ini tidak ada sehingga pendaki
 * melompat dari daftar langsung ke membuat trip. Riset corong Traveloka menemukan tahap
 * menyimpan ini bermasalah bahkan ketika ia ada; di sini ia belum pernah ada.
 *
 * Disimpan di basis data, bukan di sesi: pendaki menimbang lintas hari dan lintas
 * perangkat, dan timbangan yang hilang ketika tab ditutup bukan timbangan.
 *
 * Kunci uniknya mencegah satu jalur terhitung dua kali, yang akan membuat batas lima
 * bocor tanpa ada yang menyadarinya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trail_considerations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trail_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'trail_id'], 'trail_considerations_unik');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trail_considerations');
    }
};
