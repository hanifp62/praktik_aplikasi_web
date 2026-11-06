<?php

namespace Tests\Spatial;

use Illuminate\Support\Facades\DB;

/**
 * Lapis pertama penutupan PostgREST (migrasi 2026_09_20_000100): peran anon dan authenticated tidak memegang hak apa
 * pun atas tabel aplikasi, termasuk TRUNCATE yang tidak tunduk pada RLS. Tabel milik ekstensi tidak dihitung:
 * spatial_ref_sys memberi SELECT kepada PUBLIC dari PostGIS sendiri, dan isinya definisi sistem koordinat.
 *
 * CI membuat kedua peran beserta hak bawaan ala Supabase sebelum suite ini berjalan (langkah "Siapkan peran Supabase"
 * di test.yml), jadi pencabutan di migrasi itu benar-benar diuji. Tanpa kedua peran itu test ini dilewati, dan
 * --fail-on-skipped membuat CI merah.
 */
class PostgrestClosureTest extends SpatialTestCase
{
    public function test_postgrest_roles_hold_no_privilege_on_any_app_table(): void
    {
        $peran = DB::select("SELECT rolname FROM pg_roles WHERE rolname IN ('anon', 'authenticated')");

        if (count($peran) < 2) {
            $this->markTestSkipped('Peran anon dan authenticated tidak ada di basis data uji ini.');
        }

        $bocor = array_map(fn (object $baris) => "{$baris->rolname} pada {$baris->relname}", DB::select(
            "SELECT r.rolname, c.relname
               FROM pg_class c
               JOIN pg_namespace n ON n.oid = c.relnamespace
              CROSS JOIN pg_roles r
              WHERE n.nspname = 'public'
                AND c.relkind = 'r'
                AND c.relowner = (SELECT oid FROM pg_roles WHERE rolname = current_user)
                AND r.rolname IN ('anon', 'authenticated')
                AND NOT EXISTS (
                    SELECT 1 FROM pg_depend d
                     WHERE d.classid = 'pg_class'::regclass AND d.objid = c.oid AND d.deptype = 'e'
                )
                AND has_table_privilege(r.oid, c.oid, 'SELECT,INSERT,UPDATE,DELETE,TRUNCATE,REFERENCES,TRIGGER')
              ORDER BY c.relname, r.rolname"
        ));

        $this->assertSame([], $bocor, 'Peran PostgREST masih memegang hak: '.implode('; ', $bocor));
    }
}
