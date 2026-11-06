<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Menutup akses PostgREST ke seluruh tabel aplikasi.
 *
 * Supabase memberi peran `anon` dan `authenticated` hak penuh atas tabel yang dibuat
 * di schema public, dan mengeksposnya lewat REST API dengan kunci publishable yang
 * memang dirancang untuk dipublikasikan. Tanpa RLS, siapa pun yang memegang kunci itu
 * dapat membaca users beserta hash kata sandinya, membaca sessions, menulis
 * password_reset_tokens, lalu masuk sebagai pengguna mana pun.
 *
 * Dua lapis, karena masing-masing menutup lubang yang berbeda:
 *
 * 1. REVOKE mencabut haknya. TRUNCATE tidak tunduk pada RLS sama sekali, sehingga tanpa
 *    pencabutan ini `anon` tetap dapat mengosongkan tabel, termasuk audit_logs yang
 *    justru menjadi bukti §61.
 * 2. RLS menolak baris apa pun karena tidak ada satu pun policy. Ini jaring pengaman
 *    bila suatu saat ada yang memberikan GRANT lagi.
 *
 * Laravel tidak terpengaruh: ia terhubung sebagai `postgres`, pemilik tabelnya, dan
 * pemilik melewati RLS selama FORCE ROW LEVEL SECURITY tidak dipasang.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        $peran = $this->peranPostgrest();

        if ($peran !== '') {
            DB::statement("REVOKE ALL ON ALL TABLES IN SCHEMA public FROM {$peran}");
            DB::statement("REVOKE ALL ON ALL SEQUENCES IN SCHEMA public FROM {$peran}");

            // Fungsi PostGIS dimiliki supabase_admin, bukan kita, sehingga pencabutannya
            // boleh gagal. Itu bukan alasan membatalkan penutupan akses tabel.
            try {
                DB::statement("REVOKE ALL ON ALL ROUTINES IN SCHEMA public FROM {$peran}");
            } catch (Throwable $e) {
                report($e);
            }

            // Tanpa ini, tabel yang dibuat migrasi berikutnya akan kembali mendapat hak penuh.
            DB::statement("ALTER DEFAULT PRIVILEGES IN SCHEMA public REVOKE ALL ON TABLES FROM {$peran}");
            DB::statement("ALTER DEFAULT PRIVILEGES IN SCHEMA public REVOKE ALL ON SEQUENCES FROM {$peran}");
        }

        foreach ($this->tabelAplikasi() as $tabel) {
            DB::statement("ALTER TABLE public.\"{$tabel}\" ENABLE ROW LEVEL SECURITY");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->tabelAplikasi() as $tabel) {
            DB::statement("ALTER TABLE public.\"{$tabel}\" DISABLE ROW LEVEL SECURITY");
        }

        $peran = $this->peranPostgrest();

        if ($peran === '') {
            return;
        }

        DB::statement("GRANT ALL ON ALL TABLES IN SCHEMA public TO {$peran}");
        DB::statement("GRANT ALL ON ALL SEQUENCES IN SCHEMA public TO {$peran}");
        DB::statement("ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO {$peran}");
        DB::statement("ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO {$peran}");
    }

    /**
     * Peran PostgREST yang ada di basis data ini, siap disisipkan ke GRANT dan REVOKE. Supabase selalu
     * memiliki keduanya. PostgreSQL biasa (CI, Docker lokal) tidak, dan REVOKE ke peran yang tidak ada
     * menggagalkan migrasi; sampai 12 Oktober 2026 hal itu membuat seluruh suite Spatial galat sebelum
     * satu test pun berjalan. Nama peran berasal dari daftar tetap di query ini, bukan dari masukan.
     */
    private function peranPostgrest(): string
    {
        $ada = array_map(
            fn (object $baris) => $baris->rolname,
            DB::select("SELECT rolname FROM pg_roles WHERE rolname IN ('anon', 'authenticated') ORDER BY rolname")
        );

        return implode(', ', $ada);
    }

    /**
     * Hanya tabel milik peran aplikasi, di luar tabel milik ekstensi. spatial_ref_sys berisi definisi sistem
     * koordinat PostGIS, bukan data pengguna. Di Supabase pemiliknya supabase_admin; di PostgreSQL tempat peran
     * aplikasi sendiri memasang PostGIS (CI, Docker lokal) pemiliknya peran aplikasi, jadi penyaring pemilik saja
     * tidak cukup.
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
