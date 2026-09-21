<?php

namespace Tests\Unit;

use App\Enums\ExperienceLevel;
use App\Enums\NavigationExperience;
use App\Enums\RouteFitLabel;
use App\Models\HikingGoal;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\User;
use App\Services\RouteFitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Route Fit test matrix from PRD §101.
 */
class RouteFitServiceTest extends TestCase
{
    use RefreshDatabase;

    private function beginner(): User
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::BEGINNER->value, 'completed_at' => now()]);
        $user->experience()->create([
            'completed_hikes_count' => 1,
            'terrain_experience' => ['FOREST'],
            'navigation_experience' => NavigationExperience::BASIC->value,
        ]);

        return $user->fresh();
    }

    private function experienced(): User
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::ADVANCED->value, 'completed_at' => now()]);
        $user->experience()->create([
            'completed_hikes_count' => 25,
            'terrain_experience' => ['FOREST', 'SCREE', 'EXPOSED_RIDGE', 'STEEP_SLOPE', 'ROCKY'],
            'navigation_experience' => NavigationExperience::ADVANCED->value,
            'highest_elevation_gain_m' => 1800,
        ]);

        return $user->fresh();
    }

    private function goal(User $user, array $attributes = []): HikingGoal
    {
        return HikingGoal::factory()->create(array_merge(['user_id' => $user->id], $attributes));
    }

    public function test_beginner_on_easy_route_is_labelled_cocok(): void
    {
        $user = $this->beginner();
        $trail = Trail::factory()->published()->easy()->create();

        $result = app(RouteFitService::class)->evaluate($user, $this->goal($user), $trail);

        $this->assertTrue($result->eligible);
        $this->assertSame(RouteFitLabel::COCOK, $result->label);
    }

    public function test_beginner_on_moderate_route_needs_preparation(): void
    {
        $user = $this->beginner();
        $trail = Trail::factory()->published()->create([
            'elevation_gain_m' => 1300,
            'distance_km' => 12,
            'estimated_duration_minutes' => 600,
            'terrain_character' => ['FOREST', 'STEEP_SLOPE'],
        ]);

        $result = app(RouteFitService::class)->evaluate($user, $this->goal($user), $trail);

        $this->assertSame(RouteFitLabel::PERLU_PERSIAPAN, $result->label);
    }

    public function test_beginner_on_highly_technical_route_is_kurang_cocok(): void
    {
        $user = $this->beginner();
        $trail = Trail::factory()->published()->highlyTechnical()->create();

        $result = app(RouteFitService::class)->evaluate($user, $this->goal($user, [
            'expected_duration_minutes' => 2880,
        ]), $trail);

        $this->assertSame(RouteFitLabel::KURANG_COCOK, $result->label);
    }

    public function test_experienced_hiker_on_moderate_route_is_cocok(): void
    {
        $user = $this->experienced();
        $trail = Trail::factory()->published()->create();

        $result = app(RouteFitService::class)->evaluate($user, $this->goal($user), $trail);

        $this->assertSame(RouteFitLabel::COCOK, $result->label);
    }

    public function test_closed_trail_is_excluded(): void
    {
        $user = $this->experienced();
        $trail = Trail::factory()->published()->create();
        OfficialStatus::factory()->closed()->create([
            'statusable_id' => $trail->id,
            'statusable_type' => $trail->getMorphClass(),
        ]);

        $result = app(RouteFitService::class)->evaluate($user, $this->goal($user), $trail);

        $this->assertFalse($result->eligible);
        $this->assertNull($result->label);
        $this->assertContains('official_status_closed', $result->failedRules);
    }

    public function test_restricted_trail_stays_eligible_but_warns(): void
    {
        $user = $this->experienced();
        $trail = Trail::factory()->published()->create();
        OfficialStatus::factory()->restricted()->create([
            'statusable_id' => $trail->id,
            'statusable_type' => $trail->getMorphClass(),
        ]);

        $result = app(RouteFitService::class)->evaluate($user, $this->goal($user), $trail);

        $this->assertTrue($result->eligible);
        $this->assertNotEmpty($result->warnings);
    }

    public function test_multi_day_route_is_excluded_from_a_day_trip_goal(): void
    {
        $user = $this->experienced();
        $trail = Trail::factory()->published()->create(['estimated_duration_minutes' => 30 * 60]);

        $result = app(RouteFitService::class)->evaluate($user, $this->goal($user, [
            'trip_type' => 'TEKTOK',
        ]), $trail);

        $this->assertFalse($result->eligible);
        $this->assertContains('duration_requires_overnight', $result->failedRules);
    }

    public function test_recommendation_run_is_stored_for_audit(): void
    {
        $user = $this->beginner();
        $goal = $this->goal($user);
        Trail::factory()->published()->easy()->create();
        Trail::factory()->published()->highlyTechnical()->create();

        $run = app(RouteFitService::class)->recommend($user, $goal);

        $this->assertSame(RouteFitService::ENGINE_VERSION, $run->engine_version);
        $this->assertNotEmpty($run->input_snapshot);
        $this->assertCount(2, $run->results);
        $this->assertNotNull($run->results->first()->explanation);
    }
}
