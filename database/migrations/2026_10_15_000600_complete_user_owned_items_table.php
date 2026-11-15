<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Perlengkapan pribadi (spec v3 bagian 6.15 dan 9.3; rencana induk W7 butir 4): daftar barang
 * milik pendaki yang mengisi awal daftar persiapan setiap trip barunya, tetap dikonfirmasi per
 * trip (spec bagian 6.10). Satu nama sekali per pendaki tanpa memandang huruf besar; hapus akun
 * menghapus daftarnya (cascade, kebijakan data pribadi bagian 2).
 *
 * Migrasi aditif ini mengisi tabel rintisan 2026_09_25_000921 yang hanya berisi id dan
 * timestamps; rintisan itu sengaja tidak disunting (ruling koordinator W7). Rintisan tidak pernah
 * ditulis kode apa pun, jadi tabelnya kosong; pemeriksaannya `SELECT count(*) FROM
 * user_owned_items` sebelum migrasi dijalankan, karena kolom user_id yang wajib gagal dipasang
 * di atas baris tanpa pemilik.
 *
 * Lapis kedua penutupan PostgREST (2026_09_20_000100): RLS tanpa policy menolak REST API,
 * sedangkan Laravel sebagai pemilik tabel tidak terpengaruh. Rintisannya tidak menyalakannya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_owned_items', function (Blueprint $table) {
            $table->foreignId('user_id')->after('id')->constrained()->cascadeOnDelete();
            $table->string('name', 60)->after('user_id');
            $table->index('user_id');
        });

        // Satu nama sekali per pendaki tanpa memandang huruf besar: "Headlamp" dan "headlamp"
        // adalah barang yang sama. Indeks ekspresi ini berlaku di PostgreSQL maupun SQLite.
        DB::statement('CREATE UNIQUE INDEX user_owned_items_unik ON user_owned_items (user_id, lower(name))');

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE public."user_owned_items" ENABLE ROW LEVEL SECURITY');
        }
    }

    public function down(): void
    {
        DB::statement('DROP INDEX user_owned_items_unik');

        Schema::table('user_owned_items', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn('name');
        });
    }
};
