<?php

namespace Tests\Spatial;

use App\Enums\PreparationCategory;
use App\Enums\PreparationStatus;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use Illuminate\Support\Facades\DB;

/**
 * Kunci jenis butir persiapan di PostgreSQL (R-024; ruling W4-C7b dan W8-R24): kolom boleh kosong,
 * terindeks bersama trip, dan migrasi datanya memindahkan butir izin berlabel lama.
 */
class TripPreparationKindSchemaTest extends SpatialTestCase
{
    public function test_the_kind_column_is_nullable_and_indexed(): void
    {
        $kolom = DB::selectOne("SELECT is_nullable FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'trip_preparation_items' AND column_name = 'kind'");
        $this->assertSame('YES', $kolom->is_nullable);

        $indeks = collect(DB::select("SELECT indexdef FROM pg_indexes WHERE tablename = 'trip_preparation_items'"))->pluck('indexdef')->implode("\n");
        $this->assertStringContainsString('(trip_plan_id, kind)', $indeks);
    }

    public function test_the_data_migration_marks_old_permit_items(): void
    {
        $trip = TripPlan::factory()->create();
        $lama = TripPreparationItem::create([
            'trip_plan_id' => $trip->id,
            'category' => PreparationCategory::LOGISTICS->value,
            'label' => 'Booking izin pendakian (SIMAKSI)',
            'is_critical' => true,
            'status' => PreparationStatus::CONFIRMED->value,
        ]);

        (require database_path('migrations/2026_10_15_001800_mark_permit_preparation_items.php'))->up();

        $baris = DB::table('trip_preparation_items')->where('id', $lama->id)->first();
        $this->assertSame('PERMIT', $baris->kind);
        $this->assertSame('Pesan izin pendakian (SIMAKSI)', $baris->label);
    }
}
