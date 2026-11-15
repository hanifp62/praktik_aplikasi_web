<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Laporan kesalahan data dari layar pendaki ke antrean kurator (spec v3 bagian 10.7 langkah 7
 * "konflik"; rencana induk W8 butir 9; ruling W8-R17). Laporan tidak pernah mengubah status
 * (R-010): kurator mendapat tugas cek ulang ke sumber resminya, lalu menandainya sudah dicek
 * beserta hasilnya.
 *
 * Sasarannya selalu gunung, dan jalur bila laporan dikirim dari halaman jalur, supaya antrean
 * kurator disaring per kawasan dengan satu kolom. `official_status_id` adalah catatan yang
 * sedang terbaca pendaki saat ia melapor, diisi server, tidak pernah dari isian.
 *
 * Laporan ikut terhapus bersama akun pelapornya: kebijakan data pribadi bagian 3 memperlakukan
 * kategori yang tidak punya dasar retensi terdokumentasi sebagai dihapus. Kurator pemeriksa
 * menjadi kosong bila akunnya hilang. Di PostgreSQL dua CHECK menjaga keadaan dan bagian, dan
 * RLS menutup REST API (lapis kedua 2026_09_20_000100).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_error_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mountain_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trail_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('official_status_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category', 20);
            $table->text('note');
            $table->string('state', 20)->default('OPEN');
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('checked_at')->nullable();
            $table->string('resolution', 500)->nullable();
            $table->timestamps();

            // Antrean meja kurator: laporan terbuka per gunung, terlama dulu.
            $table->index(['state', 'mountain_id', 'created_at'], 'data_error_reports_antrean');
            $table->index('user_id');
            $table->index('mountain_id');
            $table->index('trail_id');
            $table->index('official_status_id');
            $table->index('checked_by');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE data_error_reports ADD CONSTRAINT data_error_reports_keadaan_sah CHECK (state IN ('OPEN', 'CHECKED'))");
            DB::statement("ALTER TABLE data_error_reports ADD CONSTRAINT data_error_reports_bagian_sah CHECK (category IN ('STATUS', 'PERMIT', 'ROUTE', 'OTHER'))");
            DB::statement('ALTER TABLE public."data_error_reports" ENABLE ROW LEVEL SECURITY');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('data_error_reports');
    }
};
