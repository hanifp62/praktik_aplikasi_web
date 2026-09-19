<?php

namespace Tests\Feature;

use App\Enums\AnalyticsEvent as AnalyticsEventType;
use App\Enums\ExperienceLevel;
use App\Enums\NavigationExperience;
use App\Enums\PreparationStatus;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Livewire\Goals\GoalForm;
use App\Livewire\Trips\ReadinessDashboard;
use App\Livewire\Trips\TripForm;
use App\Models\AnalyticsEvent;
use App\Models\DataSource;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\RecommendationRun;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use Database\Seeders\PreparationTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Tests\TestCase;

/**
 * PRD §100: E2E core journey.
 *
 * Register → Profil → Rencana → Rekomendasi → Pilih jalur → Trip → Persiapan → Check.
 *
 * Test-test lain memeriksa tiap langkah secara terpisah. Yang diperiksa di sini adalah
 * bahwa langkah-langkah itu benar-benar tersambung: keluaran satu langkah dapat dipakai
 * langkah berikutnya, dan funnel analitik §62 terisi sepanjang jalan.
 */
class CoreJourneyTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_hiker_can_walk_the_whole_planning_journey(): void
    {
        $this->seed(PreparationTemplateSeeder::class);
        $trail = $this->publishedTrail();

        Volt::test('pages.auth.register')
            ->set('name', 'Rani')
            ->set('email', 'rani@contoh.test')
            ->set('password', 'kata-sandi-rahasia')
            ->set('password_confirmation', 'kata-sandi-rahasia')
            ->call('register')
            ->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'rani@contoh.test')->firstOrFail();
        $this->actingAs($user);

        $user->profile()->create([
            'experience_level' => ExperienceLevel::INTERMEDIATE->value,
            'completed_at' => now(),
        ]);
        $user->experience()->create([
            'completed_hikes_count' => 8,
            'terrain_experience' => ['FOREST'],
            'navigation_experience' => NavigationExperience::BASIC->value,
        ]);

        $this->assertTrue($user->fresh()->hasCompletedProfile(), 'Profil harus terhitung lengkap.');

        // Menyimpan goal menjalankan mesin route fit dan mencatat jejak auditnya.
        Livewire::test(GoalForm::class)
            ->set('trip_type', TripType::CAMPING->value)
            ->set('expected_duration_minutes', 720)
            ->set('target_date', now()->addDays(14)->toDateString())
            ->call('save')
            ->assertHasNoErrors();

        $run = RecommendationRun::where('user_id', $user->id)->latest('id')->firstOrFail();

        $this->get(route('recommendations.show', $run))->assertOk();

        $result = $run->results()->where('eligible', true)->orderBy('rank')->first();

        $this->assertNotNull($result, 'Harus ada kandidat yang lolos untuk dilanjutkan.');
        $this->assertNotNull($result->label, 'Setiap kandidat membawa label publik (PRD §29).');
        $this->assertNotEmpty($result->explanation, 'Setiap rekomendasi membawa penjelasan (PRD §30).');

        Livewire::test(TripForm::class)
            ->set('trail_id', $result->trail_id)
            ->set('name', 'Rencana pertama')
            ->set('planned_date', now()->addDays(14)->toDateString())
            ->set('trip_type', TripType::CAMPING->value)
            ->call('save')
            ->assertHasNoErrors();

        $trip = TripPlan::where('user_id', $user->id)->firstOrFail();

        $this->get(route('trips.preparation', $trip))->assertOk();

        $trip->preparationItems()->update([
            'status' => PreparationStatus::CONFIRMED->value,
            'status_updated_at' => now(),
        ]);

        $this->assertSame(100, $trip->fresh()->preparationCompletionPercent());

        Livewire::test(ReadinessDashboard::class, ['trip' => $trip->fresh()])
            ->call('confirmPreDeparture')
            ->assertSet('preDepartureConfirmed', true);

        $this->assertSame(TripStatus::READY_FOR_DEPARTURE, $trip->fresh()->status);

        // Funnel PRD §62 terisi sepanjang perjalanan.
        // event_name di-cast ke enum, jadi yang dibandingkan instance-nya.
        $recorded = AnalyticsEvent::where('user_id', $user->id)->pluck('event_name')->all();

        foreach ([
            AnalyticsEventType::RECOMMENDATION_VIEWED,
            AnalyticsEventType::TRIP_CREATED,
            AnalyticsEventType::PREPARATION_STARTED,
            AnalyticsEventType::PRE_DEPARTURE_CHECK_COMPLETED,
        ] as $event) {
            $this->assertContains($event, $recorded, "Event {$event->value} tidak tercatat.");
        }
    }

    public function test_the_journey_stops_at_the_profile_when_it_is_incomplete(): void
    {
        $this->publishedTrail();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(GoalForm::class)
            ->set('trip_type', TripType::CAMPING->value)
            ->call('save')
            ->assertRedirect(route('onboarding'));

        $this->assertSame(0, RecommendationRun::count(), 'Mesin tidak berjalan tanpa profil (PRD §104).');
    }

    public function test_a_journey_with_no_eligible_route_explains_itself(): void
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::BEGINNER->value, 'completed_at' => now()]);

        // Satu-satunya jalur yang ada berstatus tutup.
        $trail = $this->publishedTrail();
        OfficialStatus::factory()->closed()->create([
            'statusable_type' => (new Trail)->getMorphClass(),
            'statusable_id' => $trail->id,
            'effective_at' => now(),
            'published_at' => now(),
        ]);

        Livewire::actingAs($user->fresh())
            ->test(GoalForm::class)
            ->set('trip_type', TripType::CAMPING->value)
            ->call('save');

        $run = RecommendationRun::latest('id')->firstOrFail();

        $this->assertSame(0, $run->results()->where('eligible', true)->count());
        $this->assertNotEmpty(
            $run->results()->first()->failed_rules,
            'PRD §104: sistem menjelaskan kenapa tidak ada jalur yang cocok.'
        );
    }

    private function publishedTrail(): Trail
    {
        return Trail::factory()->easy()->create([
            'mountain_id' => Mountain::factory()->create()->id,
            'data_source_id' => DataSource::factory()->create()->id,
            'is_published' => true,
        ]);
    }
}
