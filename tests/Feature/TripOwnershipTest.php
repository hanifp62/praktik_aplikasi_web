<?php

namespace Tests\Feature;

use App\Models\TripPlan;
use App\Models\User;
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
}
