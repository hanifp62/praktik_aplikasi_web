<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Status gunung api PVMBG per gunung (DK-43; spec v3 bagian 9.3 dan 10.7; rencana induk W8 butir 7;
 * ruling W8-R18): level I sampai IV, rekomendasi radius, tautan laporan sumber, waktu terbit, dan
 * waktu verifikasi kurator. Disimpan terpisah dari status akses dan tidak pernah dipetakan ke Buka
 * atau Tutup.
 *
 * Riwayat dipertahankan: setiap pencatatan level adalah baris baru, dan yang tampil adalah yang
 * terbit paling akhir. Pencatat menjadi kosong bila akunnya hilang. Di PostgreSQL CHECK menjaga
 * level di rentang I sampai IV, dan RLS menutup REST API (lapis kedua 2026_09_20_000100).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('volcano_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mountain_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('level');
            $table->text('recommendation');
            $table->string('report_url', 500);
            $table->timestamp('issued_at');
            $table->timestamp('verified_at');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['mountain_id', 'issued_at'], 'volcano_statuses_terbaru');
            $table->index('recorded_by');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE volcano_statuses ADD CONSTRAINT volcano_statuses_level_sah CHECK (level BETWEEN 1 AND 4)');
            DB::statement('ALTER TABLE public."volcano_statuses" ENABLE ROW LEVEL SECURITY');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('volcano_statuses');
    }
};
