<?php

namespace Tests\Spatial;

use App\Models\HikingHistory;
use App\Models\TripPlan;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Riwayat dua sumber di PostgreSQL (R-024, ruling W7-R3): kolom asal dan nama jalur, trip dan
 * jalur yang kini boleh kosong, indeks tahun, dan dua CHECK yang hanya ada di PostgreSQL. Suite
 * SQLite tidak membuktikan satu pun dari itu. Migrasinya tidak dijalankan di Supabase sebelum
 * izin pemilik (spec v3 Q2).
 */
class HikingHistorySourceSchemaTest extends SpatialTestCase
{
    public function test_the_columns_index_and_checks_have_their_production_shape(): void
    {
        $kolom = collect(DB::select(
            "SELECT column_name, data_type, is_nullable, column_default, character_maximum_length
             FROM information_schema.columns
             WHERE table_schema = current_schema() AND table_name = 'hiking_history'"
        ))->keyBy('column_name');

        $this->assertSame(['character varying', 'NO', 10], [$kolom['source']->data_type, $kolom['source']->is_nullable, (int) $kolom['source']->character_maximum_length]);
        $this->assertStringContainsString("'TRIP'", (string) $kolom['source']->column_default, 'Baris lama dari trip mendapat asal TRIP.');
        $this->assertSame(['character varying', 'YES', 120], [$kolom['trail_name']->data_type, $kolom['trail_name']->is_nullable, (int) $kolom['trail_name']->character_maximum_length]);
        $this->assertSame('YES', $kolom['trip_plan_id']->is_nullable);
        $this->assertSame('YES', $kolom['trail_id']->is_nullable);

        $cek = collect(DB::select("SELECT conname FROM pg_constraint WHERE conrelid = 'hiking_history'::regclass AND contype = 'c'"))->pluck('conname')->all();

        $this->assertContains('hiking_history_sumber_sah', $cek);
        $this->assertContains('hiking_history_jalur_tercatat', $cek);
        $this->assertNotNull(DB::selectOne("SELECT 1 FROM pg_indexes WHERE tablename = 'hiking_history' AND indexname = 'hiking_history_pengguna_tanggal'"));
    }

    public function test_a_manual_row_that_points_at_a_trip_is_refused(): void
    {
        $trip = TripPlan::factory()->create();

        $this->expectException(QueryException::class);
        HikingHistory::factory()->manual()->create(['user_id' => $trip->user_id, 'trip_plan_id' => $trip->id, 'trail_id' => $trip->trail_id]);
    }

    public function test_a_trip_row_without_its_trip_is_refused(): void
    {
        $this->expectException(QueryException::class);
        HikingHistory::factory()->create(['trip_plan_id' => null]);
    }

    public function test_a_row_without_any_trail_is_refused(): void
    {
        $this->expectException(QueryException::class);
        HikingHistory::factory()->manual()->create(['trail_id' => null, 'trail_name' => null]);
    }
}
