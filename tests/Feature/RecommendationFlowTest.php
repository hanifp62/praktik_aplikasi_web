<?php

namespace Tests\Feature;

use App\Enums\AnalyticsEvent;
use App\Enums\ExperienceLevel;
use App\Enums\NavigationExperience;
use App\Livewire\Goals\GoalForm;
use App\Livewire\Onboarding\ProfileSetup;
use App\Models\AnalyticsEvent as AnalyticsEventModel;
use App\Models\OfficialStatus;
use App\Models\RecommendationRun;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Acceptance criteria for recommendation (PRD §114) and official status (PRD §117).
 */
class RecommendationFlowTest extends TestCase
{
    use RefreshDatabase;

    private function userWithProfile(): User
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::INTERMEDIATE->value, 'completed_at' => now()]);
        $user->experience()->create([
            'completed_hikes_count' => 8,
            'terrain_experience' => ['FOREST', 'STEEP_SLOPE'],
            'navigation_experience' => NavigationExperience::COMPETENT->value,
        ]);
        $user->preference()->create(['preferred_duration' => 'ONE_DAY', 'preferred_trip_type' => 'CAMPING']);

        return $user->fresh();
    }

    public function test_creating_a_goal_runs_the_engine_and_stores_explanations(): void
    {
        $user = $this->userWithProfile();
        Trail::factory()->published()->easy()->create();

        Livewire::actingAs($user)
            ->test(GoalForm::class)
            ->set('trip_type', 'CAMPING')
            ->set('expected_duration_minutes', 720)
            ->set('target_date', now()->addWeek()->toDateString())
            ->call('save')
            ->assertHasNoErrors();

        $run = RecommendationRun::firstOrFail();

        $this->assertSame($user->id, $run->user_id);
        $this->assertCount(1, $run->results);
        $this->assertArrayHasKey('why_it_fits', $run->results->first()->explanation);
    }

    public function test_a_closed_trail_never_becomes_an_active_recommendation(): void
    {
        $user = $this->userWithProfile();
        $open = Trail::factory()->published()->easy()->create();
        $closed = Trail::factory()->published()->easy()->create();
        OfficialStatus::factory()->closed()->create([
            'statusable_id' => $closed->id,
            'statusable_type' => $closed->getMorphClass(),
        ]);

        Livewire::actingAs($user)
            ->test(GoalForm::class)
            ->set('trip_type', 'CAMPING')
            ->call('save');

        $run = RecommendationRun::firstOrFail();

        $this->assertTrue($run->results->firstWhere('trail_id', $open->id)->eligible);
        $this->assertFalse($run->results->firstWhere('trail_id', $closed->id)->eligible);
    }

    public function test_unpublished_trails_are_not_offered(): void
    {
        $user = $this->userWithProfile();
        Trail::factory()->easy()->unpublished()->create();

        Livewire::actingAs($user)
            ->test(GoalForm::class)
            ->set('trip_type', 'CAMPING')
            ->call('save');

        $this->assertCount(0, RecommendationRun::firstOrFail()->results);
    }

    public function test_a_goal_cannot_be_created_before_the_profile_is_complete(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(GoalForm::class)
            ->set('trip_type', 'CAMPING')
            ->call('save')
            ->assertRedirect(route('onboarding'));

        $this->assertSame(0, RecommendationRun::count());
    }

    public function test_completing_the_profile_records_a_funnel_event(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ProfileSetup::class)
            ->set('experience_level', ExperienceLevel::BEGINNER->value)
            ->set('navigation_experience', NavigationExperience::BASIC->value)
            ->set('preferred_duration', 'ONE_DAY')
            ->set('preferred_trip_type', 'TEKTOK')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue($user->fresh()->hasCompletedProfile());
        $this->assertDatabaseHas('analytics_events', [
            'user_id' => $user->id,
            'event_name' => AnalyticsEvent::PROFILE_COMPLETED->value,
        ]);
        $this->assertSame(1, AnalyticsEventModel::count());
    }
}
