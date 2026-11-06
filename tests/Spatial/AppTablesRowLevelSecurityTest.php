<?php

namespace Tests\Spatial;

use Illuminate\Support\Facades\DB;

/**
 * Setiap tabel aplikasi di skema public ber-RLS (lapis kedua penutupan PostgREST, CLAUDE.md
 * bagian 4). Tabel baru dari migrasi mana pun ikut dijaga, jadi tidak ada lagi tabel yang hanya
 * dilindungi default privileges seperti sebelum 2026_10_12_020612.
 */
class AppTablesRowLevelSecurityTest extends SpatialTestCase
{
    public function test_every_app_table_has_row_level_security(): void
    {
        $tanpaRls = array_map(fn (object $baris) => $baris->relname, DB::select(
            "SELECT c.relname
               FROM pg_class c
               JOIN pg_namespace n ON n.oid = c.relnamespace
               JOIN pg_tables t ON t.schemaname = n.nspname AND t.tablename = c.relname
              WHERE n.nspname = 'public'
                AND c.relkind = 'r'
                AND t.tableowner = current_user
                AND NOT c.relrowsecurity
                AND NOT EXISTS (
                    SELECT 1 FROM pg_depend d
                     WHERE d.classid = 'pg_class'::regclass AND d.objid = c.oid AND d.deptype = 'e'
                )
              ORDER BY c.relname"
        ));

        $this->assertSame([], $tanpaRls, 'Tabel aplikasi tanpa RLS: '.implode(', ', $tanpaRls));
    }
}
