<?php

namespace Tests\Feature;

use App\Models\RecommendationResult;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PRD §120 and BR-13: geolocation is only requested inside Hike Mode, and precise location
 * is never serialised out of the model.
 */
class LocationPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_browsing_trails_does_not_request_geolocation(): void
    {
        $user = User::factory()->create();
        $trail = Trail::factory()->create();

        $this->actingAs($user)
            ->get(route('trails.show', $trail))
            ->assertOk()
            ->assertDontSee('navigator.geolocation', escape: false);
    }

    public function test_hike_mode_is_where_geolocation_is_requested(): void
    {
        $trip = TripPlan::factory()->create();

        $this->actingAs($trip->user)
            ->get(route('trips.hike', $trip))
            ->assertOk()
            ->assertSee('navigator.geolocation', escape: false);
    }

    public function test_hiking_session_location_is_hidden_from_serialisation(): void
    {
        $trip = TripPlan::factory()->create();
        $session = $trip->hikingSession()->create([
            'user_id' => $trip->user_id,
            'status' => 'ACTIVE',
            'started_at' => now(),
        ]);

        $this->assertArrayNotHasKey('last_known_location', $session->fresh()->toArray());
    }

    public function test_internal_fit_score_is_never_serialised_to_the_client(): void
    {
        $result = new RecommendationResult([
            'internal_score' => 82.4,
            'label' => 'COCOK',
        ]);

        $this->assertArrayNotHasKey('internal_score', $result->toArray());
    }
}
