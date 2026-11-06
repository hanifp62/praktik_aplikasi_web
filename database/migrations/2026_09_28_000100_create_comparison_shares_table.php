<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tautan bagikan perbandingan (FR-23, spec v3 bagian 9.3).
 *
 * Tokennya tidak pernah disimpan: yang disimpan hanya SHA-256-nya (64 karakter heksa, unik),
 * jadi salinan basis data tidak cukup untuk membuka tautan siapa pun. Daftar jalur disimpan
 * sebagai larik id dalam urutan kolom. Tautan berumur (expires_at) dan dapat dicabut
 * (revoked_at); keduanya diperiksa setiap kali dibuka. Akun yang dihapus menghapus tautannya
 * (cascade), karena tautan menunjuk ke pilihan pemilik akun itu.
 *
 * Diuji di SQLite dan PostgreSQL CI; tidak dijalankan di Supabase sebelum izin pemilik
 * (R-019, spec v3 Q2).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comparison_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->json('trail_ids');
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'expires_at']);
        });

        // Lapis kedua penutupan PostgREST (2026_09_20_000100): RLS tanpa policy menolak
        // REST API, sedangkan Laravel sebagai pemilik tabel tidak terpengaruh.
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE public."comparison_shares" ENABLE ROW LEVEL SECURITY');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('comparison_shares');
    }
};
