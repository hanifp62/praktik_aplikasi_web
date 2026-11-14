<?php

namespace Tests\Spatial;

use App\Models\PersonalTarget;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Tabel target pribadi di PostgreSQL (R-024, ruling W7-R2): kolom yang diisi migrasi aditif di
 * atas rintisan 2026_09_25_000920, kunci unik per pendaki per ukuran, kunci asing yang ikut
 * terhapus bersama akun, dan RLS yang menutup REST API. Suite SQLite tidak membuktikan satu pun
 * dari itu. Migrasinya tidak dijalankan di Supabase sebelum izin pemilik (spec v3 Q2).
 */
class PersonalTargetSchemaTest extends SpatialTestCase
{
    public function test_the_table_has_its_production_shape(): void
    {
        $kolom = collect(DB::select(
            "SELECT column_name, data_type, is_nullable, character_maximum_length
             FROM information_schema.columns
             WHERE table_schema = current_schema() AND table_name = 'personal_targets'"
        ))->keyBy('column_name');

        $this->assertSame(['bigint', 'NO'], [$kolom['user_id']->data_type, $kolom['user_id']->is_nullable]);
        $this->assertSame(['character varying', 'NO', 20], [$kolom['metric']->data_type, $kolom['metric']->is_nullable, (int) $kolom['metric']->character_maximum_length]);
        $this->assertSame(['integer', 'NO'], [$kolom['monthly_value']->data_type, $kolom['monthly_value']->is_nullable]);

        $this->assertTrue((bool) DB::selectOne("SELECT relrowsecurity FROM pg_class WHERE relname = 'personal_targets'")->relrowsecurity, 'RLS lapis kedua harus menyala.');
        $this->assertSame('c', DB::selectOne("SELECT confdeltype FROM pg_constraint WHERE conrelid = 'personal_targets'::regclass AND contype = 'f'")->confdeltype, 'Target ikut terhapus bersama akunnya.');
    }

    public function test_one_target_per_hiker_per_metric(): void
    {
        $user = User::factory()->create();
        PersonalTarget::factory()->for($user)->create(['metric' => 'GAIN']);

        $this->expectException(UniqueConstraintViolationException::class);
        PersonalTarget::factory()->for($user)->create(['metric' => 'GAIN']);
    }
}
