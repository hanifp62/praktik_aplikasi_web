<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\NavigationExperience;
use App\Enums\UserRole;
use App\Models\HikingGoal;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\RouteFitService;
use Database\Seeders\PreparationTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Renders every page in the main user flow so a broken view fails the build.
 */
class PageSmokeTest extends TestCase
{
    use RefreshDatabase;

    private function hiker(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->profile()->create(['experience_level' => ExperienceLevel::INTERMEDIATE->value, 'completed_at' => now()]);
        $user->experience()->create([
            'completed_hikes_count' => 5,
            'terrain_experience' => ['FOREST'],
            'navigation_experience' => NavigationExperience::COMPETENT->value,
        ]);
        $user->preference()->create(['preferred_duration' => 'ONE_DAY', 'preferred_trip_type' => 'CAMPING']);

        return $user->fresh();
    }

    public function test_main_flow_pages_render(): void
    {
        $this->seed(PreparationTemplateSeeder::class);

        $user = $this->hiker();
        $trail = Trail::factory()->create();
        $second = Trail::factory()->easy()->create();
        $trip = TripPlan::factory()->create(['user_id' => $user->id, 'trail_id' => $trail->id]);
        $goal = HikingGoal::factory()->create(['user_id' => $user->id]);
        $run = app(RouteFitService::class)->recommend($user, $goal);

        $pages = [
            route('dashboard'),
            route('onboarding'),
            route('goals.create'),
            route('recommendations.show', $run),
            route('trails.index'),
            route('trails.compare', ['trails' => $trail->id.','.$second->id]),
            route('trails.show', $trail),
            route('trips.index'),
            route('trips.create', ['trail' => $trail->id]),
            route('trips.show', $trip),
            route('trips.preparation', $trip),
            route('trips.readiness', $trip),
            route('trips.hike', $trip),
            route('reports.create', ['trail' => $trail->id]),
            route('history'),
        ];

        foreach ($pages as $url) {
            $this->actingAs($user)->get($url)->assertOk();
        }
    }

    public function test_admin_and_moderation_pages_render(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value, 'email_verified_at' => now()]);
        $trail = Trail::factory()->create();

        $pages = [
            route('admin.mountains'),
            route('admin.trails'),
            route('admin.checkpoints', $trail),
            route('admin.sources'),
            route('admin.statuses'),
            route('admin.audit'),
            route('admin.analytics'),
            route('moderation.queue'),
        ];

        foreach ($pages as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }
}
