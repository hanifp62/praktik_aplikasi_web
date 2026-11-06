<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Lapis kedua penutupan PostgREST untuk SEMUA tabel aplikasi, bukan hanya yang ada saat
 * 2026_09_20_000100 dijalankan. Migrasi itu menyalakan RLS pada tabel yang sudah ada waktu itu;
 * tabel sesudahnya (kredensial ahli, sesi uji, jejak pendakian, simpanan, target, barang milik,
 * dan seterusnya) hanya dijaga lapis pertama, yaitu default privileges yang mencabut hak anon
 * dan authenticated. Di basis data baru urutan nama berkas juga membuat beberapa tabel
 * (misalnya permit_requirements) tercipta sesudah penutupan itu.
 *
 * RLS tanpa policy menolak semua akses PostgREST; aplikasi tidak terdampak karena terhubung
 * sebagai pemilik tabel, yang tidak tunduk pada RLS tanpa FORCE. Idempoten: tabel yang sudah
 * ber-RLS tidak berubah. Hanya PostgreSQL; SQLite (test dan data contoh) tidak punya RLS.
 * Dijaga AppTablesRowLevelSecurityTest di suite Spatial.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->tabelAplikasi() as $tabel) {
            DB::statement("ALTER TABLE public.\"{$tabel}\" ENABLE ROW LEVEL SECURITY");
        }
    }

    /**
     * Tidak dibalik. Mematikan RLS membuka kembali lapis kedua, dan rollback skema tidak boleh
     * melemahkan keamanan.
     */
    public function down(): void
    {
        //
    }

    /**
     * Tabel di skema public milik pengguna koneksi ini, sama dengan 2026_09_20_000100. Tabel milik
     * ekstensi (misalnya spatial_ref_sys milik PostGIS) tidak termasuk, juga bila peran aplikasi
     * sendiri yang memasang ekstensinya.
     *
     * @return array<int, string>
     */
    private function tabelAplikasi(): array
    {
        return array_map(
            fn (object $baris) => $baris->tablename,
            DB::select(
                "SELECT c.relname AS tablename
                   FROM pg_class c
                   JOIN pg_namespace n ON n.oid = c.relnamespace
                  WHERE n.nspname = 'public'
                    AND c.relkind IN ('r', 'p')
                    AND pg_get_userbyid(c.relowner) = current_user
                    AND NOT EXISTS (
                        SELECT 1 FROM pg_depend d
                         WHERE d.classid = 'pg_class'::regclass AND d.objid = c.oid AND d.deptype = 'e'
                    )"
            )
        );
    }
};
