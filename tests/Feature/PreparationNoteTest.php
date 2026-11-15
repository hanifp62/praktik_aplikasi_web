<?php

namespace Tests\Feature;

use App\Enums\PreparationCategory;
use App\Enums\PreparationStatus;
use App\Enums\TripStatus;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Models\User;
use App\Services\PreparationService;
use Database\Seeders\PreparationTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Catatan per butir persiapan (titipan W4 ke W7; prototype 09 "Dibawa Dimas"; ruling W7-R9).
 * Hanya pemilik trip, hanya butir Perlengkapan dan Logistik, hanya selama trip aktif, dan hanya
 * kolom catatan yang berubah.
 */
class PreparationNoteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PreparationTemplateSeeder::class);
    }

    /**
     * Trip milik seorang pendaki dengan daftar persiapan yang sudah dibangkitkan.
     *
     * @return array{0: User, 1: TripPlan}
     */
    private function tripPendaki(): array
    {
        $pemilik = User::factory()->create();
        $trip = TripPlan::factory()->create(['user_id' => $pemilik->id]);
        app(PreparationService::class)->generateFor($trip);

        return [$pemilik, $trip];
    }

    private function butir(TripPlan $trip, PreparationCategory $kategori): TripPreparationItem
    {
        return $trip->preparationItems()->where('category', $kategori->value)->orderBy('id')->firstOrFail();
    }

    public function test_the_owner_writes_tidies_and_clears_a_note(): void
    {
        [$pemilik, $trip] = $this->tripPendaki();
        $tenda = $this->butir($trip, PreparationCategory::EQUIPMENT);

        $this->actingAs($pemilik)->put(route('trips.preparation.note', [$trip, $tenda->id]), ['catatan' => "  Dibawa   Dimas\n"])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('trips.preparation', $trip));
        $this->assertSame('Dibawa Dimas', $tenda->fresh()->note);

        $catatan = $this->actingAs($pemilik)->get(route('trips.preparation', $trip))->assertOk()->viewData('page')['props']['catatanButir'];
        $this->assertSame('Dibawa Dimas', $catatan['isi'][$tenda->id]);
        $this->assertTrue($catatan['dapatDiubah']);
        $this->assertSame(80, $catatan['batas']);

        $this->actingAs($pemilik)->put(route('trips.preparation.note', [$trip, $tenda->id]), ['catatan' => '   '])->assertSessionHasNoErrors();
        $this->assertNull($tenda->fresh()->note, 'Isian kosong menghapus catatan.');
    }

    /**
     * Hanya kolom catatan yang berubah: status, label, kritis, dan kategori dari isian diabaikan,
     * dan catatan tidak dihitung sebagai konfirmasi (BR-10).
     */
    public function test_a_note_changes_nothing_but_the_note(): void
    {
        [$pemilik, $trip] = $this->tripPendaki();
        $butir = $this->butir($trip, PreparationCategory::LOGISTICS);
        $sebelum = $butir->only(['label', 'status', 'is_critical', 'category']);

        $this->actingAs($pemilik)->put(route('trips.preparation.note', [$trip, $butir->id]), [
            'catatan' => 'Travel dari Bogor jam 04.00',
            'status' => PreparationStatus::CONFIRMED->value,
            'label' => 'Diganti',
            'is_critical' => false,
            'category' => PreparationCategory::WEATHER->value,
        ])->assertSessionHasNoErrors();

        $this->assertSame($sebelum, $butir->fresh()->only(['label', 'status', 'is_critical', 'category']));
        $this->assertSame('Travel dari Bogor jam 04.00', $butir->fresh()->note);
    }

    public function test_long_notes_other_categories_and_closed_trips_are_refused(): void
    {
        [$pemilik, $trip] = $this->tripPendaki();
        $perlengkapan = $this->butir($trip, PreparationCategory::EQUIPMENT);
        $cuaca = $this->butir($trip, PreparationCategory::WEATHER);

        $this->actingAs($pemilik)->put(route('trips.preparation.note', [$trip, $perlengkapan->id]), ['catatan' => str_repeat('a', 81)])
            ->assertSessionHasErrors(['catatan' => 'Catatan paling banyak 80 karakter.']);
        $this->actingAs($pemilik)->put(route('trips.preparation.note', [$trip, $perlengkapan->id]), [])
            ->assertSessionHasErrors(['catatan' => 'Isian catatan tidak terkirim. Muat ulang halaman lalu coba lagi.']);
        $this->actingAs($pemilik)->put(route('trips.preparation.note', [$trip, $cuaca->id]), ['catatan' => 'Cek Jumat malam'])
            ->assertSessionHasErrors(['catatan' => 'Catatan hanya untuk butir Perlengkapan dan Logistik.']);

        $trip->update(['status' => TripStatus::CANCELLED->value]);
        $this->actingAs($pemilik)->put(route('trips.preparation.note', [$trip, $perlengkapan->id]), ['catatan' => 'Dibawa Dimas'])
            ->assertSessionHasErrors(['catatan' => 'Catatan butir tidak dapat diubah lagi: trip berstatus Dibatalkan.']);

        $this->assertSame(0, TripPreparationItem::whereNotNull('note')->count());
        $catatan = $this->actingAs($pemilik)->get(route('trips.preparation', $trip))->viewData('page')['props']['catatanButir'];
        $this->assertFalse($catatan['dapatDiubah']);
    }

    /**
     * Hanya butir Perlengkapan dan Logistik yang ditawari catatan di halaman.
     */
    public function test_the_page_offers_notes_on_equipment_and_logistics_items_only(): void
    {
        [$pemilik, $trip] = $this->tripPendaki();

        $catatan = $this->actingAs($pemilik)->get(route('trips.preparation', $trip))->viewData('page')['props']['catatanButir'];

        $this->assertEqualsCanonicalizing(
            $trip->preparationItems()->whereIn('category', [PreparationCategory::EQUIPMENT->value, PreparationCategory::LOGISTICS->value])->pluck('id')->all(),
            $catatan['bisa'],
        );
        $this->assertSame([], $catatan['isi']);
    }

    /**
     * Trip orang lain menjawab 403 seperti rute trip lain (keputusan 403 atau 404 menunggu spec v3
     * Q5); butir milik trip lain menjawab 404 walau id-nya benar (IDOR); tamu diarahkan masuk.
     */
    public function test_only_the_trip_owner_can_write_notes_on_its_own_items(): void
    {
        [, $trip] = $this->tripPendaki();
        $butir = $this->butir($trip, PreparationCategory::EQUIPMENT);
        $orangLain = User::factory()->create();
        $tripLain = TripPlan::factory()->create(['user_id' => $orangLain->id]);

        $this->actingAs($orangLain)->put(route('trips.preparation.note', [$trip, $butir->id]), ['catatan' => 'Diambil'])->assertForbidden();
        $this->actingAs($orangLain)->put(route('trips.preparation.note', [$tripLain, $butir->id]), ['catatan' => 'Diambil'])->assertNotFound();

        auth()->logout();
        $this->put(route('trips.preparation.note', [$trip, $butir->id]), ['catatan' => 'Tamu'])->assertRedirect(route('login'));

        $this->assertNull($butir->fresh()->note);
    }
}
