<?php

namespace Tests\Unit;

use App\Enums\PreparationCategory;
use App\Enums\PreparationStatus;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\PreparationService;
use App\Services\Trips\IsiAwalPerlengkapan;
use Database\Seeders\PreparationTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PreparationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PreparationTemplateSeeder::class);
    }

    public function test_items_start_as_not_confirmed(): void
    {
        $trip = TripPlan::factory()->create();

        $items = app(PreparationService::class)->generateFor($trip);

        $this->assertTrue($items->isNotEmpty());
        $this->assertTrue($items->every(fn ($item) => $item->status === PreparationStatus::NOT_CONFIRMED));
    }

    public function test_camping_items_only_apply_to_trails_with_camping(): void
    {
        $withCamping = TripPlan::factory()->create([
            'trail_id' => Trail::factory()->create(['camping_available' => true]),
        ]);
        $withoutCamping = TripPlan::factory()->create([
            'trail_id' => Trail::factory()->create(['camping_available' => false]),
        ]);

        $service = app(PreparationService::class);

        $this->assertTrue(
            $service->generateFor($withCamping)->contains('label', 'Perlengkapan bermalam (tenda, sleeping bag)')
        );
        $this->assertFalse(
            $service->generateFor($withoutCamping)->contains('label', 'Perlengkapan bermalam (tenda, sleeping bag)')
        );
    }

    public function test_regenerating_keeps_existing_statuses(): void
    {
        $trip = TripPlan::factory()->create();
        $service = app(PreparationService::class);

        $items = $service->generateFor($trip);
        $first = $items->first();
        $service->updateStatus($first, PreparationStatus::CONFIRMED);

        $regenerated = $service->generateFor($trip->fresh());

        $this->assertSame(
            PreparationStatus::CONFIRMED,
            $regenerated->firstWhere('id', $first->id)->status
        );
    }

    public function test_state_reports_outstanding_critical_items(): void
    {
        $trip = TripPlan::factory()->create();
        $service = app(PreparationService::class);
        $service->generateFor($trip);

        $state = $service->state($trip->fresh());

        $this->assertSame(0, $state['completion_percent']);
        $this->assertNotEmpty($state['critical_outstanding']);
    }

    public function test_not_applicable_items_do_not_block_completion(): void
    {
        $trip = TripPlan::factory()->create();
        $service = app(PreparationService::class);
        $items = $service->generateFor($trip);

        foreach ($items as $index => $item) {
            $service->updateStatus($item, $index === 0 ? PreparationStatus::NOT_APPLICABLE : PreparationStatus::CONFIRMED);
        }

        $state = $service->state($trip->fresh());

        $this->assertSame(100, $state['completion_percent']);
        $this->assertSame([], $state['critical_outstanding']);
    }

    /**
     * Keadaan persiapan prototype 09 dan cek kesiapan prototype 10 (W4 Tugas 4): hitungan butir
     * kritis yang berlaku dan nama butir kritis yang belum dikonfirmasi. "Tidak berlaku" tidak
     * dihitung, sama seperti kemajuan persiapan.
     */
    public function test_the_critical_count_names_what_is_still_unconfirmed(): void
    {
        $trip = TripPlan::factory()->create();
        $service = app(PreparationService::class);
        $items = $service->generateFor($trip);
        $kritis = $items->where('is_critical', true)->values();

        $service->updateStatus($kritis[0], PreparationStatus::CONFIRMED);
        $service->updateStatus($kritis[1], PreparationStatus::NOT_APPLICABLE);

        $hitungan = PreparationService::kritis($trip->fresh()->preparationItems);

        $this->assertSame(1, $hitungan['dikonfirmasi']);
        $this->assertSame($kritis->count() - 1, $hitungan['berlaku']);
        $this->assertSame($kritis->slice(2)->pluck('label')->values()->all(), $hitungan['belum']);
    }

    /**
     * Perlengkapan pribadi mengisi awal daftar sebagai butir Perlengkapan yang belum dikonfirmasi
     * dan tidak kritis (ruling W7-R8, BR-10), sehingga tidak menambah hitungan butir kritis.
     */
    public function test_owned_items_prefill_the_list_unconfirmed_and_not_critical(): void
    {
        $user = User::factory()->create();
        $user->ownedItems()->createMany([['name' => 'Trekking pole'], ['name' => 'Matras']]);
        $trip = TripPlan::factory()->create(['user_id' => $user->id]);

        $items = app(PreparationService::class)->generateFor($trip);
        $milik = $items->whereNotNull('user_owned_item_id')->values();

        $this->assertEqualsCanonicalizing(['Trekking pole', 'Matras'], $milik->pluck('label')->all());
        $this->assertTrue($milik->every(fn ($item) => $item->category === PreparationCategory::EQUIPMENT
            && $item->is_critical === false
            && $item->status === PreparationStatus::NOT_CONFIRMED
            && $item->description === IsiAwalPerlengkapan::KETERANGAN));
        $this->assertSame(
            $items->where('is_critical', true)->count(),
            PreparationService::kritis($trip->fresh()->preparationItems)['berlaku'],
        );
    }

    /**
     * Membuat ulang daftar tidak menggandakan barang, mempertahankan statusnya, menambahkan
     * barang yang baru dimiliki, dan melewati barang yang namanya sudah ada di daftar.
     */
    public function test_regenerating_neither_duplicates_owned_items_nor_resets_them(): void
    {
        $user = User::factory()->create();
        $pole = $user->ownedItems()->create(['name' => 'Trekking pole']);
        $user->ownedItems()->create(['name' => 'referensi NAVIGASI jalur']);
        $trip = TripPlan::factory()->create(['user_id' => $user->id]);
        $service = app(PreparationService::class);

        $service->generateFor($trip);
        $service->updateStatus($trip->preparationItems()->where('user_owned_item_id', $pole->id)->sole(), PreparationStatus::CONFIRMED);
        $user->ownedItems()->create(['name' => 'Gaiter']);
        $items = $service->generateFor($trip->fresh());

        $this->assertSame(1, $items->where('user_owned_item_id', $pole->id)->count());
        $this->assertSame(PreparationStatus::CONFIRMED, $items->firstWhere('user_owned_item_id', $pole->id)->status);
        $this->assertSame(1, $items->where('label', 'Gaiter')->count());
        $this->assertSame(0, $items->where('label', 'referensi NAVIGASI jalur')->count(), 'Nama yang sama dengan butir templat tidak ditambahkan dua kali.');
    }

    /**
     * Prakiraan BMKG untuk tanggal trip terbit tiga hari sebelumnya (config
     * hiking.conditions.weather_forecast_days); sebelum itu tanggal terbitnya dikembalikan,
     * sesudahnya null.
     */
    public function test_the_forecast_publication_date_is_known_until_it_arrives(): void
    {
        $this->travelTo(Carbon::parse('2026-09-25 03:00:00', 'UTC'));

        $jauh = TripPlan::factory()->create(['planned_date' => '2026-10-10']);
        $dekat = TripPlan::factory()->create(['planned_date' => '2026-09-27']);

        $this->assertSame('2026-10-07', PreparationService::terbitPrakiraan($jauh)?->toDateString());
        $this->assertNull(PreparationService::terbitPrakiraan($dekat));
    }
}
