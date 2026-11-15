<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Target pribadi bulanan (FR-25, PRD §150; rencana induk W7 butir 2): satu baris per pendaki per
 * ukuran (tanjakan, jarak, atau jumlah pendakian per bulan). Dibandingkan hanya dengan pendakian
 * pendaki itu sendiri (R-015); tidak mengubah label kecocokan dan tidak menaikkan tingkat
 * pengalaman (BR-11). Hapus akun menghapus targetnya (cascade, kebijakan data pribadi bagian 2).
 *
 * Migrasi aditif ini mengisi tabel rintisan 2026_09_25_000920 yang hanya berisi id dan
 * timestamps; rintisan itu sengaja tidak disunting (ruling koordinator W7), jadi berlaku sama
 * baik ia sudah berjalan di Supabase maupun belum. Rintisan tidak pernah ditulis kode apa pun, jadi
 * tabelnya kosong; kolom user_id yang wajib akan gagal bila ternyata ada baris tanpa pemilik,
 * dan pemeriksaannya `SELECT count(*) FROM personal_targets` sebelum migrasi dijalankan.
 *
 * Lapis kedua penutupan PostgREST (2026_09_20_000100): RLS tanpa policy menolak REST API,
 * sedangkan Laravel sebagai pemilik tabel tidak terpengaruh. Rintisannya tidak menyalakannya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_targets', function (Blueprint $table) {
            $table->foreignId('user_id')->after('id')->constrained()->cascadeOnDelete();
            $table->string('metric', 20)->after('user_id');
            $table->unsignedInteger('monthly_value')->after('metric');

            $table->unique(['user_id', 'metric'], 'personal_targets_unik');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE public."personal_targets" ENABLE ROW LEVEL SECURITY');
        }
    }

    public function down(): void
    {
        Schema::table('personal_targets', function (Blueprint $table) {
            $table->dropUnique('personal_targets_unik');
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn(['metric', 'monthly_value']);
        });
    }
};
