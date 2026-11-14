<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Provenans status resmi untuk meja kurator (spec v3 bagian 10.7; rencana induk W8 butir 1;
 * ruling W8-R4).
 *
 * Penerbit (`authority_id`), nomor dan tanggal surat, jenis dan arsip bukti beserta SHA-256 dan
 * waktu tangkapnya, cara berakhir, batas pembukaan parsial, golongan alasan, dasar hukum,
 * kutipan resmi, ringkasan bahasa sederhana, pemeriksa kedua, keadaan verifikasi, rantai
 * penggantian, dan jadwal tinjau. Semua kolom boleh kosong: catatan lama dari sebelum meja kurator
 * tidak punya keadaan verifikasi dan tetap dibaca pendaki seperti sekarang, jadi migrasi ini tidak
 * mengubah satu pun status yang tampil.
 *
 * Di PostgreSQL tiga CHECK menjaga bentuk baris di lapisan basis data: keadaan verifikasi dari
 * daftar tertutup, pemeriksa kedua tidak pernah sama dengan pencatat (aturan dua orang), dan
 * SHA-256 berupa 64 karakter heksadesimal kecil. Kunci asing ke otoritas, pemeriksa, dan catatan
 * pengganti menjadi kosong bila rujukannya hilang, sehingga menghapus akun kurator tidak menghapus
 * status resmi (kebijakan data pribadi bagian 2: data tata kelola dipertahankan seminimal
 * mungkin). official_statuses sudah ber-RLS sejak 2026_09_20_000100.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('official_statuses', function (Blueprint $table) {
            $table->foreignId('authority_id')->nullable()->constrained()->nullOnDelete();
            $table->string('document_number', 120)->nullable();
            $table->date('document_date')->nullable();
            $table->string('evidence_type', 20)->nullable();
            $table->string('evidence_archive_path')->nullable();
            $table->char('evidence_sha256', 64)->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->string('end_condition', 20)->nullable();
            $table->string('limit_description', 200)->nullable();
            $table->string('reason_category', 20)->nullable();
            $table->string('legal_basis')->nullable();
            $table->text('official_excerpt')->nullable();
            $table->text('plain_summary')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('verification_state', 20)->nullable();
            $table->boolean('visible_before_verification')->default(false);
            $table->foreignId('superseded_by_id')->nullable()->constrained('official_statuses')->nullOnDelete();
            $table->timestamp('next_review_at')->nullable();

            $table->index('authority_id');
            $table->index('verified_by');
            $table->index('superseded_by_id');
            // Antrean meja kurator membaca keadaan dan jadwal tinjau.
            $table->index(['verification_state', 'next_review_at'], 'official_statuses_antrean');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE official_statuses ADD CONSTRAINT official_statuses_keadaan_sah CHECK (verification_state IS NULL OR verification_state IN ('DRAFT', 'PENDING', 'VERIFIED', 'DISPUTED'))");
            DB::statement('ALTER TABLE official_statuses ADD CONSTRAINT official_statuses_dua_orang CHECK (verified_by IS NULL OR recorded_by IS NULL OR verified_by <> recorded_by)');
            DB::statement("ALTER TABLE official_statuses ADD CONSTRAINT official_statuses_sha256_sah CHECK (evidence_sha256 IS NULL OR evidence_sha256 ~ '^[0-9a-f]{64}$')");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE official_statuses DROP CONSTRAINT IF EXISTS official_statuses_keadaan_sah');
            DB::statement('ALTER TABLE official_statuses DROP CONSTRAINT IF EXISTS official_statuses_dua_orang');
            DB::statement('ALTER TABLE official_statuses DROP CONSTRAINT IF EXISTS official_statuses_sha256_sah');
        }

        Schema::table('official_statuses', function (Blueprint $table) {
            $table->dropIndex('official_statuses_antrean');
            $table->dropIndex(['authority_id']);
            $table->dropIndex(['verified_by']);
            $table->dropIndex(['superseded_by_id']);
            $table->dropConstrainedForeignId('authority_id');
            $table->dropConstrainedForeignId('verified_by');
            $table->dropConstrainedForeignId('superseded_by_id');
            $table->dropColumn([
                'document_number', 'document_date', 'evidence_type', 'evidence_archive_path', 'evidence_sha256',
                'captured_at', 'end_condition', 'limit_description', 'reason_category', 'legal_basis',
                'official_excerpt', 'plain_summary', 'verification_state', 'visible_before_verification',
                'next_review_at',
            ]);
        });
    }
};
