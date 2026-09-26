<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Catatan pribadi pada jalur tersimpan (FR-22, DK-07).
 *
 * Nullable: jalur tanpa catatan menyimpan null, bukan string kosong. Panjangnya dibatasi
 * 500 karakter di kolom dan di validasi sekaligus (TrailConsideration::CATATAN_MAKS).
 * Kolom baru yang boleh kosong pada tabel yang sudah berisi, jadi aman dijalankan pada
 * baris yang ada tanpa pengisian ulang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trail_considerations', function (Blueprint $table) {
            $table->string('note', 500)->nullable();
        });

        // Lapis kedua penutupan PostgREST (migrasi tutup_akses_postgrest_ke_tabel_aplikasi)
        // hanya menyalakan RLS pada tabel yang ada saat itu. Tabel ini lahir sesudahnya
        // dan kini memuat tulisan pribadi. ENABLE pada tabel yang sudah ber-RLS tidak
        // melakukan apa-apa; di luar PostgreSQL langkah ini dilewati.
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE public."trail_considerations" ENABLE ROW LEVEL SECURITY');
        }
    }

    /**
     * Menghapus kolom membuang seluruh catatan pendaki. RLS dibiarkan menyala: mematikannya
     * tidak diperlukan untuk mengembalikan skema dan hanya melemahkan penutupan akses.
     */
    public function down(): void
    {
        Schema::table('trail_considerations', function (Blueprint $table) {
            $table->dropColumn('note');
        });
    }
};
