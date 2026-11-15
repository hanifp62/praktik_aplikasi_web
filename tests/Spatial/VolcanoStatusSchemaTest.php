<?php

namespace Tests\Spatial;

use App\Enums\VolcanoLevel;
use App\Models\Mountain;
use App\Models\VolcanoStatus;
use App\Services\DataResmi\StatusGunungApi;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Tabel status gunung api di PostgreSQL (R-024; DK-43; ruling W8-R18): level di rentang I sampai IV,
 * RLS menutup REST API, pencatatan ikut terhapus bersama gunungnya, pencatat menjadi kosong, dan
 * kueri "terbit paling akhir per gunung" (subkueri berkorelasi) memilih baris yang sama seperti di
 * SQLite.
 */
class VolcanoStatusSchemaTest extends SpatialTestCase
{
    public function test_the_table_has_its_production_shape(): void
    {
        $this->assertTrue((bool) DB::selectOne("SELECT relrowsecurity FROM pg_class WHERE relname = 'volcano_statuses'")->relrowsecurity, 'RLS lapis kedua harus menyala.');

        $cek = collect(DB::select("SELECT conname FROM pg_constraint WHERE conrelid = 'volcano_statuses'::regclass AND contype = 'c'"))->pluck('conname')->all();
        $this->assertContains('volcano_statuses_level_sah', $cek);

        $hapus = collect(DB::select(
            "SELECT a.attname AS kolom, c.confdeltype AS aksi
             FROM pg_constraint c JOIN pg_attribute a ON a.attrelid = c.conrelid AND a.attnum = ANY (c.conkey)
             WHERE c.conrelid = 'volcano_statuses'::regclass AND c.contype = 'f'"
        ))->pluck('aksi', 'kolom');

        $this->assertSame('c', $hapus['mountain_id']);
        $this->assertSame('n', $hapus['recorded_by']);
    }

    public function test_a_level_outside_one_to_four_is_refused(): void
    {
        $catatan = VolcanoStatus::factory()->create();

        $this->expectException(QueryException::class);
        DB::table('volcano_statuses')->where('id', $catatan->id)->update(['level' => 5]);
    }

    public function test_the_latest_issued_report_per_mountain_matches_sqlite(): void
    {
        $gede = Mountain::factory()->create();
        $merapi = Mountain::factory()->create();
        VolcanoStatus::factory()->for($gede)->create(['level' => VolcanoLevel::SIAGA->value, 'issued_at' => now()->subHour()]);
        VolcanoStatus::factory()->for($gede)->create(['level' => VolcanoLevel::WASPADA->value, 'issued_at' => now()->subDays(2)]);
        VolcanoStatus::factory()->for($merapi)->create(['level' => VolcanoLevel::NORMAL->value, 'issued_at' => now()->subDays(5)]);

        $terkini = app(StatusGunungApi::class)->terkiniUntuk([$gede->id, $merapi->id]);

        $this->assertSame(VolcanoLevel::SIAGA, $terkini[$gede->id]->level);
        $this->assertSame(VolcanoLevel::NORMAL, $terkini[$merapi->id]->level);
    }
}
