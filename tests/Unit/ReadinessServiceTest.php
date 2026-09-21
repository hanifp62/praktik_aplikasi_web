<?php

namespace Tests\Unit;

use App\Enums\ExperienceLevel;
use App\Enums\NavigationExperience;
use App\Enums\PreparationStatus;
use App\Enums\ReadinessState;
use App\Models\HikingGoal;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\PreparationService;
use App\Services\ReadinessService;
use Database\Seeders\PreparationTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Readiness test matrix from PRD §102.
 */
class ReadinessServiceTest extends TestCase
{
    use RefreshDatabase;

    private function tripFor(Trail $trail): TripPlan
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::ADVANCED->value, 'completed_at' => now()]);
        $user->experience()->create([
            'completed_hikes_count' => 30,
            'terrain_experience' => ['FOREST', 'SCREE', 'EXPOSED_RIDGE', 'STEEP_SLOPE', 'ROCKY'],
            'navigation_experience' => NavigationExperience::ADVANCED->value,
            'highest_elevation_gain_m' => 2000,
        ]);

        $goal = HikingGoal::factory()->create(['user_id' => $user->id]);

        return TripPlan::factory()->create([
            'user_id' => $user->id,
            'trail_id' => $trail->id,
            'hiking_goal_id' => $goal->id,
        ]);
    }

    private function seedPreparation(TripPlan $trip, bool $confirmAll): void
    {
        $this->seed(PreparationTemplateSeeder::class);
        app(PreparationService::class)->generateFor($trip);

        if ($confirmAll) {
            $trip->preparationItems()->update([
                'status' => PreparationStatus::CONFIRMED->value,
                'status_updated_at' => now(),
            ]);
        }
    }

    public function test_case_a_fit_complete_preparation_and_open_status_is_ready(): void
    {
        $trail = Trail::factory()->published()->create();
        OfficialStatus::factory()->create([
            'statusable_id' => $trail->id,
            'statusable_type' => $trail->getMorphClass(),
        ]);

        $trip = $this->tripFor($trail);
        $this->seedPreparation($trip, confirmAll: true);

        $check = app(ReadinessService::class)->evaluate($trip->fresh());

        $this->assertSame(ReadinessState::READY, $check->computed_state);
    }

    public function test_case_b_incomplete_preparation_needs_preparation(): void
    {
        $trail = Trail::factory()->published()->create();
        OfficialStatus::factory()->create([
            'statusable_id' => $trail->id,
            'statusable_type' => $trail->getMorphClass(),
        ]);

        $trip = $this->tripFor($trail);
        $this->seedPreparation($trip, confirmAll: false);

        $check = app(ReadinessService::class)->evaluate($trip->fresh());

        $this->assertSame(ReadinessState::NEEDS_PREPARATION, $check->computed_state);
        $this->assertNotEmpty($check->explanation['outstanding']);
    }

    public function test_case_c_closed_status_is_not_recommended(): void
    {
        $trail = Trail::factory()->published()->create();
        OfficialStatus::factory()->closed()->create([
            'statusable_id' => $trail->id,
            'statusable_type' => $trail->getMorphClass(),
        ]);

        $trip = $this->tripFor($trail);
        $this->seedPreparation($trip, confirmAll: true);

        $check = app(ReadinessService::class)->evaluate($trip->fresh());

        $this->assertSame(ReadinessState::NOT_RECOMMENDED, $check->computed_state);
    }

    public function test_case_d_unknown_status_cannot_be_confidently_verified(): void
    {
        $trail = Trail::factory()->published()->create();

        $trip = $this->tripFor($trail);
        $this->seedPreparation($trip, confirmAll: true);

        $check = app(ReadinessService::class)->evaluate($trip->fresh());

        $this->assertSame(ReadinessState::NEEDS_PREPARATION, $check->computed_state);
        $this->assertSame('UNKNOWN', $check->official_status_snapshot['status']);
    }
}
