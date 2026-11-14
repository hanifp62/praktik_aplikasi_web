<?php

namespace Tests\Spatial;

use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Kolom catatan butir persiapan di PostgreSQL (R-024, ruling W7-R9). PostgreSQL menegakkan
 * panjang varchar(80), sedangkan SQLite menerima teks berapa pun panjangnya; suite SQLite tidak
 * dapat membuktikan batas kolom ini.
 */
class PreparationItemNoteSchemaTest extends SpatialTestCase
{
    public function test_the_note_column_is_optional_and_bounded(): void
    {
        $kolom = DB::selectOne(
            "SELECT data_type, is_nullable, character_maximum_length
             FROM information_schema.columns
             WHERE table_schema = current_schema() AND table_name = 'trip_preparation_items' AND column_name = 'note'"
        );

        $this->assertSame(['character varying', 'YES', 80], [$kolom->data_type, $kolom->is_nullable, (int) $kolom->character_maximum_length]);

        $butir = TripPreparationItem::create([
            'trip_plan_id' => TripPlan::factory()->create()->id,
            'category' => 'EQUIPMENT',
            'label' => 'Tenda',
            'is_critical' => false,
            'status' => 'NOT_CONFIRMED',
        ]);

        $this->expectException(QueryException::class);
        $butir->update(['note' => str_repeat('a', 81)]);
    }
}
