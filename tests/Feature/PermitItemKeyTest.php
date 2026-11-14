<?php

namespace Tests\Feature;

use App\Enums\PreparationCategory;
use App\Enums\PreparationStatus;
use App\Models\PermitRequirement;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Services\PreparationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Kunci stabil butir izin di daftar persiapan (titipan W8, ruling W4-C7b; ruling W8-R24): butir
 * izin dikenali lewat kolom jenis, labelnya "Pesan izin pendakian (SIMAKSI)", dan butir lama
 * berlabel "Booking izin pendakian (SIMAKSI)" dipindahkan migrasi data tanpa kehilangan statusnya.
 */
class PermitItemKeyTest extends TestCase
{
    use RefreshDatabase;

    private function tripBerizin(): TripPlan
    {
        $jalur = Trail::factory()->published()->create();
        PermitRequirement::factory()->create(['trail_id' => $jalur->id]);

        return TripPlan::factory()->for($jalur)->create();
    }

    public function test_the_permit_item_carries_its_kind_and_the_new_label(): void
    {
        $trip = $this->tripBerizin();

        app(PreparationService::class)->generateFor($trip);

        $butir = $trip->preparationItems()->where('kind', TripPreparationItem::JENIS_IZIN)->sole();
        $this->assertSame('Pesan izin pendakian (SIMAKSI)', $butir->label);
        $this->assertNull($butir->preparation_item_id);
        $this->assertTrue($butir->is_critical);
    }

    /**
     * Membangkitkan ulang menemukan butir yang sama lewat jenisnya, walau labelnya masih label
     * lama, dan statusnya tidak hilang.
     */
    public function test_regenerating_keeps_one_permit_item_and_its_status(): void
    {
        $trip = $this->tripBerizin();
        (new TripPreparationItem)->forceFill([
            'trip_plan_id' => $trip->id,
            'kind' => TripPreparationItem::JENIS_IZIN,
            'category' => PreparationCategory::LOGISTICS->value,
            'label' => 'Booking izin pendakian (SIMAKSI)',
            'is_critical' => true,
            'status' => PreparationStatus::CONFIRMED->value,
        ])->save();

        app(PreparationService::class)->generateFor($trip->fresh());

        $butir = $trip->preparationItems()->where('kind', TripPreparationItem::JENIS_IZIN)->sole();
        $this->assertSame(PreparationStatus::CONFIRMED, $butir->status);
        $this->assertSame(PreparationService::PERMIT_LABEL, $butir->label);
    }

    /**
     * Butir lain yang kebetulan berlabel sama, misalnya barang pribadi, bukan butir izin.
     */
    public function test_a_lookalike_label_is_not_the_permit_item(): void
    {
        $trip = $this->tripBerizin();
        TripPreparationItem::create([
            'trip_plan_id' => $trip->id,
            'category' => PreparationCategory::LOGISTICS->value,
            'label' => PreparationService::PERMIT_LABEL,
            'is_critical' => true,
            'status' => PreparationStatus::CONFIRMED->value,
        ]);

        $this->assertFalse(PreparationService::izinDiurus($trip->fresh()->load('preparationItems')));
        $this->assertFalse(PreparationService::butirIzin($trip->preparationItems()->sole()));
    }

    public function test_the_data_migration_moves_old_permit_items_to_the_key(): void
    {
        $trip = $this->tripBerizin();
        $lama = TripPreparationItem::create([
            'trip_plan_id' => $trip->id,
            'category' => PreparationCategory::LOGISTICS->value,
            'label' => 'Booking izin pendakian (SIMAKSI)',
            'is_critical' => true,
            'status' => PreparationStatus::CONFIRMED->value,
            'note' => 'Sudah dibayar lewat bank.',
        ]);
        $templat = TripPreparationItem::create([
            'trip_plan_id' => $trip->id,
            'category' => PreparationCategory::LOGISTICS->value,
            'label' => 'Senter',
            'is_critical' => false,
            'status' => PreparationStatus::NOT_CONFIRMED->value,
        ]);

        (require database_path('migrations/2026_10_15_001800_mark_permit_preparation_items.php'))->up();

        $baris = DB::table('trip_preparation_items')->where('id', $lama->id)->first();
        $this->assertSame('PERMIT', $baris->kind);
        $this->assertSame('Pesan izin pendakian (SIMAKSI)', $baris->label);
        $this->assertSame(PreparationStatus::CONFIRMED->value, $baris->status);
        $this->assertSame('Sudah dibayar lewat bank.', $baris->note);
        $this->assertNull(DB::table('trip_preparation_items')->where('id', $templat->id)->value('kind'));
    }
}
