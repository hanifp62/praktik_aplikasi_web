<?php

namespace Tests\Feature;

use App\Enums\CompletionState;
use App\Enums\PreparationStatus;
use App\Enums\TripStatus;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\PreparationService;
use Database\Seeders\PreparationTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PRD §119: user B opening user A's trip must be rejected. Owning the URL is not owning the trip.
 */
class TripOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_open_their_trip(): void
    {
        $trip = TripPlan::factory()->create();

        $this->actingAs($trip->user)
            ->get(route('trips.show', $trip))
            ->assertOk();
    }

    public function test_another_user_cannot_open_someone_elses_trip(): void
    {
        $trip = TripPlan::factory()->create();
        $intruder = User::factory()->create();

        $this->actingAs($intruder)
            ->get(route('trips.show', $trip))
            ->assertForbidden();
    }

    public function test_another_user_cannot_open_someone_elses_preparation(): void
    {
        $this->seed(PreparationTemplateSeeder::class);

        $trip = TripPlan::factory()->create();
        $intruder = User::factory()->create();

        $this->actingAs($intruder)
            ->get(route('trips.preparation', $trip))
            ->assertForbidden();
    }

    public function test_another_user_cannot_open_someone_elses_readiness_check(): void
    {
        $trip = TripPlan::factory()->create();
        $intruder = User::factory()->create();

        $this->actingAs($intruder)
            ->get(route('trips.readiness', $trip))
            ->assertForbidden();
    }

    public function test_another_user_cannot_open_someone_elses_hike_mode(): void
    {
        $trip = TripPlan::factory()->create();
        $intruder = User::factory()->create();

        $this->actingAs($intruder)
            ->get(route('trips.hike', $trip))
            ->assertForbidden();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $trip = TripPlan::factory()->create();

        $this->get(route('trips.show', $trip))->assertRedirect(route('login'));
    }

    /**
     * Aksi halaman trip kini rute POST biasa. Menebak id trip orang lain tidak boleh
     * mengubah apa pun: setiap aksi memeriksa Policy yang sama dengan halamannya.
     */
    public function test_another_user_cannot_act_on_someone_elses_trip(): void
    {
        $this->seed(PreparationTemplateSeeder::class);

        $trip = TripPlan::factory()->create(['status' => TripStatus::IN_PROGRESS->value]);
        app(PreparationService::class)->generateFor($trip);
        $butir = $trip->preparationItems()->firstOrFail();
        $intruder = User::factory()->create();

        $aksi = [
            route('trips.start', $trip) => [],
            route('trips.cancel', $trip) => [],
            route('trips.complete', $trip) => ['completion_state' => CompletionState::COMPLETED->value],
            route('trips.preparation.regenerate', $trip) => [],
            route('trips.preparation.update', ['trip' => $trip, 'item' => $butir->id]) => ['status' => PreparationStatus::CONFIRMED->value],
            route('trips.readiness.recompute', $trip) => [],
            route('trips.readiness.confirm', $trip) => [],
            // W6: rute posisi lama dihapus; aksi mode lapangan menggantikannya.
            route('trips.hike.arrive', $trip) => ['urutan' => 1],
            route('trips.hike.pause', $trip) => [],
            route('trips.hike.resume', $trip) => [],
        ];

        foreach ($aksi as $url => $isian) {
            $this->actingAs($intruder)->post($url, $isian)->assertForbidden();
        }

        $this->assertSame(TripStatus::IN_PROGRESS, $trip->fresh()->status);
        $this->assertSame(PreparationStatus::NOT_CONFIRMED, $butir->fresh()->status);
    }

    /**
     * Pemilik satu trip tidak dapat mengubah butir persiapan trip lain dengan
     * menyelipkan id butirnya ke URL tripnya sendiri.
     */
    public function test_a_preparation_item_is_only_found_within_its_own_trip(): void
    {
        $this->seed(PreparationTemplateSeeder::class);

        $milikku = TripPlan::factory()->create();
        $milikOrang = TripPlan::factory()->create();
        app(PreparationService::class)->generateFor($milikOrang);
        $butirOrang = $milikOrang->preparationItems()->firstOrFail();

        $this->actingAs($milikku->user)
            ->post(route('trips.preparation.update', ['trip' => $milikku, 'item' => $butirOrang->id]), ['status' => PreparationStatus::CONFIRMED->value])
            ->assertNotFound();

        $this->assertSame(PreparationStatus::NOT_CONFIRMED, $butirOrang->fresh()->status);
    }
}
