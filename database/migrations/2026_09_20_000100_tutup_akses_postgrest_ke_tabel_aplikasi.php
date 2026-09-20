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

        DB::statement('REVOKE ALL ON ALL TABLES IN SCHEMA public FROM anon, authenticated');
        DB::statement('REVOKE ALL ON ALL SEQUENCES IN SCHEMA public FROM anon, authenticated');

        // Fungsi PostGIS dimiliki supabase_admin, bukan kita, sehingga pencabutannya
        // boleh gagal. Itu bukan alasan membatalkan penutupan akses tabel.
        try {
            DB::statement('REVOKE ALL ON ALL ROUTINES IN SCHEMA public FROM anon, authenticated');
        } catch (Throwable $e) {
            report($e);
        }

        // Tanpa ini, tabel yang dibuat migrasi berikutnya akan kembali mendapat hak penuh.
        DB::statement('ALTER DEFAULT PRIVILEGES IN SCHEMA public REVOKE ALL ON TABLES FROM anon, authenticated');
        DB::statement('ALTER DEFAULT PRIVILEGES IN SCHEMA public REVOKE ALL ON SEQUENCES FROM anon, authenticated');

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

        DB::statement('GRANT ALL ON ALL TABLES IN SCHEMA public TO anon, authenticated');
        DB::statement('GRANT ALL ON ALL SEQUENCES IN SCHEMA public TO anon, authenticated');
        DB::statement('ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO anon, authenticated');
        DB::statement('ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO anon, authenticated');
    }

    /**
     * Hanya tabel milik peran aplikasi. spatial_ref_sys milik supabase_admin, berisi
     * definisi sistem koordinat PostGIS, bukan data pengguna, dan bukan milik kita.
     *
     * @return array<int, string>
     */
    private function tabelAplikasi(): array
    {
        return array_map(
            fn (object $baris) => $baris->tablename,
            DB::select(
                'SELECT tablename FROM pg_tables WHERE schemaname = ? AND tableowner = current_user',
                ['public']
            )
        );
    }
};
