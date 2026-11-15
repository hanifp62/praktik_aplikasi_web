<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Anggota badan resmi untuk meja kurator (spec v3 bagian 10.7; rencana induk W8 butir 2; ruling
 * W8-R7): pengguna, badan, peran (kurator Memount atau penerbit mitra), surat penunjukan, masa
 * berlaku, dan pencabutan. Yurisdiksi seorang kurator adalah kawasan badan tempat ia menjadi
 * anggota aktif (authority_mountain).
 *
 * Keanggotaan dicabut, tidak dihapus, supaya catatan status yang pernah dibuatnya tetap dapat
 * ditelusuri; satu keanggotaan aktif per pengguna per badan dijaga indeks unik parsial (SQLite dan
 * PostgreSQL sama-sama mendukungnya). Hapus akun menghapus keanggotaannya (kebijakan data pribadi
 * bagian 2); pemberi dan pencabut menjadi kosong bila akunnya hilang. Di PostgreSQL dua CHECK
 * menjaga peran dan masa berlaku, dan RLS menutup REST API (lapis kedua 2026_09_20_000100).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authority_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('authority_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20);
            $table->string('appointment_reference', 160);
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('authority_id');
            $table->index(['user_id', 'revoked_at']);
            $table->index('granted_by');
            $table->index('revoked_by');
        });

        DB::statement('CREATE UNIQUE INDEX authority_members_aktif_unik ON authority_members (user_id, authority_id) WHERE revoked_at IS NULL');

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE authority_members ADD CONSTRAINT authority_members_peran_sah CHECK (role IN ('KURATOR', 'PENERBIT_MITRA'))");
            DB::statement('ALTER TABLE authority_members ADD CONSTRAINT authority_members_masa_sah CHECK (valid_until IS NULL OR valid_until >= valid_from)');
            DB::statement('ALTER TABLE public."authority_members" ENABLE ROW LEVEL SECURITY');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('authority_members');
    }
};
