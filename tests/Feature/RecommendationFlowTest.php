<?php

namespace Tests\Feature;

use App\Enums\AnalyticsEvent;
use App\Enums\ExperienceLevel;
use App\Enums\NavigationExperience;
use App\Enums\PreferredDuration;
use App\Enums\TerrainCharacter;
use App\Enums\TripType;
use App\Models\AnalyticsEvent as AnalyticsEventModel;
use App\Models\HikingGoal;
use App\Models\OfficialStatus;
use App\Models\RecommendationRun;
use App\Models\Trail;
use App\Models\User;
use App\Services\RouteFitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Acceptance criteria for recommendation (PRD §114) and official status (PRD §117).
 *
 * Profil dan rencana kini formulir React yang dikirim ke rute POST bernama; yang diuji
 * adalah permintaan itu dan apa yang tersimpan.
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

    /**
     * @return array<string, mixed>
     */
    private function profilLengkap(): array
    {
        return [
            'experience_level' => ExperienceLevel::BEGINNER->value,
            'region_preference' => '',
            'bio' => '',
            'completed_hikes_count' => 0,
            'terrain_experience' => [],
            'navigation_experience' => NavigationExperience::BASIC->value,
            'longest_hike_duration_minutes' => '',
            'highest_elevation_gain_m' => '',
            'preferred_duration' => 'ONE_DAY',
            'preferred_trip_type' => 'TEKTOK',
            'preferred_challenge' => '',
            'max_elevation_gain_preference_m' => '',
        ];
    }

    public function test_creating_a_goal_runs_the_engine_and_stores_explanations(): void
    {
        $user = $this->userWithProfile();
        Trail::factory()->published()->easy()->create();

        $respons = $this->actingAs($user)->post(route('goals.store'), [
            'trip_type' => 'CAMPING',
            'expected_duration_minutes' => 720,
            'target_date' => now()->addWeek()->toDateString(),
        ]);

        $run = RecommendationRun::firstOrFail();

        $respons->assertSessionHasNoErrors()->assertRedirect(route('recommendations.show', $run));
        $this->assertSame($user->id, $run->user_id);
        $this->assertCount(1, $run->results);
        $this->assertArrayHasKey('why_it_fits', $run->results->first()->explanation);
    }

    public function test_the_goal_form_is_prefilled_from_the_hikers_preferences(): void
    {
        $this->actingAs($this->userWithProfile())
            ->get(route('goals.create'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Goals/Create')
                ->where('title', 'Rencana Pendakian')
                ->where('nilai.trip_type', 'CAMPING')
                ->where('nilai.expected_duration_minutes', PreferredDuration::ONE_DAY->approximateMinutes())
                ->has('pilihan.tripTypes', count(TripType::cases())));
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

        $this->actingAs($user)->post(route('goals.store'), ['trip_type' => 'CAMPING']);

        $run = RecommendationRun::firstOrFail();

        $this->assertTrue($run->results->firstWhere('trail_id', $open->id)->eligible);
        $this->assertFalse($run->results->firstWhere('trail_id', $closed->id)->eligible);
    }

    public function test_unpublished_trails_are_not_offered(): void
    {
        $user = $this->userWithProfile();
        Trail::factory()->easy()->unpublished()->create();

        $this->actingAs($user)->post(route('goals.store'), ['trip_type' => 'CAMPING']);

        $this->assertCount(0, RecommendationRun::firstOrFail()->results);
    }

    public function test_a_goal_cannot_be_created_before_the_profile_is_complete(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('goals.store'), ['trip_type' => 'CAMPING'])
            ->assertRedirect(route('onboarding'))
            ->assertSessionHas('status', 'Lengkapi profil pendaki terlebih dahulu.');

        $this->assertSame(0, RecommendationRun::count());
    }

    public function test_a_goal_without_a_trip_type_is_refused(): void
    {
        $this->actingAs($this->userWithProfile())
            ->post(route('goals.store'), ['trip_type' => ''])
            ->assertSessionHasErrors('trip_type');

        $this->assertSame(0, RecommendationRun::count());
    }

    public function test_completing_the_profile_records_a_funnel_event(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('onboarding.store'), $this->profilLengkap())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('goals.create'))
            ->assertSessionHas('status', 'Profil pendaki tersimpan.');

        $this->assertTrue($user->fresh()->hasCompletedProfile());
        $this->assertDatabaseHas('analytics_events', [
            'user_id' => $user->id,
            'event_name' => AnalyticsEvent::PROFILE_COMPLETED->value,
        ]);
        $this->assertSame(1, AnalyticsEventModel::count());
    }

    /**
     * "Lanjut" memeriksa langkah itu saja, seperti nextStep() dulu: galat langkah
     * pertama dilaporkan, isian langkah berikutnya belum dituntut, dan tidak ada yang
     * tersimpan.
     */
    public function test_checking_a_step_validates_only_that_step_and_saves_nothing(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('onboarding.store'), ['langkah' => 1, 'experience_level' => ''])
            ->assertSessionHasErrors('experience_level')
            ->assertSessionDoesntHaveErrors(['navigation_experience', 'preferred_duration']);

        $this->actingAs($user)
            ->post(route('onboarding.store'), ['langkah' => 1, 'experience_level' => ExperienceLevel::BEGINNER->value])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('onboarding'));

        $this->assertFalse($user->fresh()->hasCompletedProfile());
        $this->assertSame(0, AnalyticsEventModel::count());
    }

    /**
     * Langkah terakhir bukan pemeriksaan: seluruh aturan berlaku sekaligus, jadi isian
     * wajib yang dilewati di langkah awal tetap ditolak saat menyimpan.
     */
    public function test_saving_still_enforces_every_step(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('onboarding.store'), ['langkah' => 3, 'preferred_duration' => 'ONE_DAY', 'preferred_trip_type' => 'TEKTOK'])
            ->assertSessionHasErrors(['experience_level', 'navigation_experience']);

        $this->assertFalse($user->fresh()->hasCompletedProfile());
    }

    /**
     * Halaman onboarding merangkap penyunting profil: pendaki yang profilnya sudah
     * lengkap melihat ringkasannya, dengan yang belum diisi disebut belum diisi.
     */
    public function test_a_completed_profile_is_summarised(): void
    {
        $this->actingAs($this->userWithProfile())
            ->get(route('onboarding'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Onboarding/ProfileSetup')
                ->where('title', 'Profil Pendaki')
                ->where('nilai.experience_level', ExperienceLevel::INTERMEDIATE->value)
                ->where('ringkasan.0.nilai', ExperienceLevel::INTERMEDIATE->label())
                // Label diterjemahkan dari "Elevation gain tertinggi" menjadi "Tanjakan
                // tertinggi" (ruling audit-fix-B BF-7a, M-3 sisa).
                ->where('ringkasan.6.label', 'Tanjakan tertinggi')
                ->where('ringkasan.6.nilai', 'Belum diisi'));
    }

    public function test_an_incomplete_profile_gets_the_form_without_a_summary(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('onboarding'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('ringkasan', null)
                ->has('pilihan.terrainOptions', count(TerrainCharacter::cases())));
    }

    /**
     * Run berisi profil, rencana, dan penilaian seseorang. Pendaki lain tidak boleh
     * membacanya maupun memakainya untuk membuat trip.
     */
    public function test_another_hikers_run_is_refused(): void
    {
        $pemilik = $this->userWithProfile();
        $trail = Trail::factory()->published()->easy()->create();
        $goal = HikingGoal::factory()->create(['user_id' => $pemilik->id, 'trip_type' => 'CAMPING', 'region' => null]);
        $run = app(RouteFitService::class)->recommend($pemilik, $goal);

        $orangLain = $this->userWithProfile();

        $this->actingAs($orangLain)->get(route('recommendations.show', $run))->assertForbidden();
        $this->actingAs($orangLain)->post(route('recommendations.pilih', $run), ['trail' => $trail->id])->assertForbidden();

        $this->assertSame(0, AnalyticsEventModel::where('user_id', $orangLain->id)->count());
    }
}
