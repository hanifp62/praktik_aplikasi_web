<?php

namespace Tests\Spatial;

use App\Models\Trail;
use Illuminate\Support\Facades\DB;

/**
 * Kolom karakteristik jalur di PostgreSQL sesudah migrasi R-006 (R-024; butir izin akhir 6; ruling
 * W8-R22 dan W8-R23): boleh kosong tanpa nilai bawaan, kolom geografi tidak tersentuh perubahan
 * kolom, dan kurasi turunan memakai perbandingan kolom yang sama seperti di SQLite.
 */
class TrailCharacteristicsSchemaTest extends SpatialTestCase
{
    public function test_the_columns_are_nullable_without_a_default(): void
    {
        $kolom = collect(DB::select(
            "SELECT table_name, column_name, is_nullable, column_default
             FROM information_schema.columns
             WHERE table_schema = current_schema()
               AND ((table_name = 'trails' AND column_name IN ('technical_demand', 'navigation_complexity', 'camping_available'))
                 OR (table_name = 'trail_segments' AND column_name = 'technical_demand'))"
        ));

        $this->assertCount(4, $kolom);

        foreach ($kolom as $k) {
            $this->assertSame('YES', $k->is_nullable, "{$k->table_name}.{$k->column_name} harus boleh kosong.");
            $this->assertNull($k->column_default, "{$k->table_name}.{$k->column_name} tidak boleh punya nilai bawaan.");
        }

        // Perubahan kolom tidak menyentuh garis rute.
        $this->assertSame('geography', DB::selectOne("SELECT udt_name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'trails' AND column_name = 'geometry'")->udt_name);
    }

    public function test_a_descent_copied_from_the_climb_is_cleared(): void
    {
        $salinan = Trail::factory()->create(['elevation_gain_m' => 1650, 'elevation_loss_m' => 1650]);
        $ukuran = Trail::factory()->create(['elevation_gain_m' => 1650, 'elevation_loss_m' => 140]);

        (require database_path('migrations/2026_10_15_001600_clear_copied_elevation_loss_on_trails.php'))->up();

        $this->assertNull(DB::table('trails')->where('id', $salinan->id)->value('elevation_loss_m'));
        $this->assertSame(140, (int) DB::table('trails')->where('id', $ukuran->id)->value('elevation_loss_m'));
    }
}
