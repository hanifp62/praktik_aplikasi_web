<?php

namespace Tests\Unit;

use App\Enums\ConditionTag;
use App\Enums\ExperienceLevel;
use App\Enums\ModerationStatus;
use App\Enums\OfficialStatusValue;
use App\Enums\PreparationStatus;
use App\Enums\ReadinessState;
use App\Enums\StatusScope;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\TrailSegment;
use App\Models\TripPlan;
use App\Models\User;
use App\Models\WeatherSnapshot;
use App\Services\PreparationService;
use App\Services\ReadinessService;
use Database\Seeders\PreparationTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F-25.
 *
 * PRD §38 menyebut readiness sebagai gabungan tiga dimensi: route fit, kelengkapan
 * persiapan, dan kondisi terkini. Dimensi ketiga sebelumnya hanya ditempel sebagai
 * teks peringatan dan tidak pernah memengaruhi state, sehingga sebuah trip dapat
 * berbunyi READY meskipun data cuacanya basi, laporan komunitas menyebut kondisi yang
 * perlu diwaspadai, atau sebagian jalurnya dibatasi.
 */
class ReadinessConditionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_fully_prepared_trip_with_good_conditions_is_ready(): void
    {
        $trip = $this->preparedTrip();
        $this->freshWeatherFor($trip->trail);

        $this->assertSame(ReadinessState::READY, app(ReadinessService::class)->compute($trip)->state);
    }

    public function test_stale_weather_is_reported_but_does_not_block(): void
    {
        $trip = $this->preparedTrip();
        $this->staleWeatherFor($trip->trail);

        $assessment = app(ReadinessService::class)->compute($trip);

        // PRD §94: kegagalan sumber eksternal ditangani dengan anggun, tidak memblokir.
        $this->assertSame(ReadinessState::READY, $assessment->state);
        $this->assertStringContainsString('cuaca', implode(' ', $assessment->explanation['outstanding']));
    }

    public function test_missing_weather_is_reported_but_does_not_block(): void
    {
        $trip = $this->preparedTrip();

        $assessment = app(ReadinessService::class)->compute($trip);

        $this->assertSame(ReadinessState::READY, $assessment->state);
        $this->assertStringContainsString('cuaca', implode(' ', $assessment->explanation['outstanding']));
    }

    public function test_a_community_caution_report_prevents_ready(): void
    {
        $trip = $this->preparedTrip();
        $this->freshWeatherFor($trip->trail);

        TrailConditionReport::create([
            'trail_id' => $trip->trail_id,
            'user_id' => User::factory()->create()->id,
            'hike_date' => now()->subDay()->toDateString(),
            'condition_tags' => [ConditionTag::SLIPPERY->value],
            'moderation_status' => ModerationStatus::APPROVED->value,
        ]);

        $this->assertSame(
            ReadinessState::NEEDS_PREPARATION,
            app(ReadinessService::class)->compute($trip->fresh())->state
        );
    }

    public function test_a_restricted_segment_prevents_ready(): void
    {
        $trip = $this->preparedTrip();
        $this->freshWeatherFor($trip->trail);

        $segment = TrailSegment::factory()->for($trip->trail)->create(['name' => 'Kalimati - Puncak']);
        OfficialStatus::factory()->create([
            'statusable_type' => (new TrailSegment)->getMorphClass(),
            'statusable_id' => $segment->id,
            'scope' => StatusScope::SEGMENT->value,
            'status' => OfficialStatusValue::CLOSED->value,
            'effective_at' => now()->subDay(),
            'published_at' => now()->subDay(),
        ]);

        $assessment = app(ReadinessService::class)->compute($trip->fresh());

        $this->assertSame(ReadinessState::NEEDS_PREPARATION, $assessment->state);
        $this->assertStringContainsString(
            'Kalimati - Puncak',
            implode(' ', $assessment->explanation['condition_warnings'])
        );
    }

    public function test_conditions_never_escalate_to_not_recommended(): void
    {
        $trip = $this->preparedTrip();
        $this->staleWeatherFor($trip->trail);

        // PRD §39: NOT_RECOMMENDED hanya untuk penutupan resmi dan inkompatibilitas keras.
        $this->assertNotSame(
            ReadinessState::NOT_RECOMMENDED,
            app(ReadinessService::class)->compute($trip)->state
        );
    }

    private function preparedTrip(): TripPlan
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::ADVANCED->value, 'completed_at' => now()]);

        $mountain = Mountain::factory()->create();
        $trail = Trail::factory()->published()->for($mountain)->easy()->create(['weather_adm4_code' => '35.07.17.2002']);

        OfficialStatus::factory()->create([
            'statusable_type' => (new Trail)->getMorphClass(),
            'statusable_id' => $trail->id,
            'scope' => StatusScope::TRAIL->value,
            'status' => OfficialStatusValue::OPEN->value,
            'effective_at' => now()->subDay(),
            'published_at' => now()->subDay(),
        ]);

        $trip = TripPlan::create([
            'user_id' => $user->id,
            'trail_id' => $trail->id,
            'name' => 'Uji kondisi',
            'planned_date' => now()->addDays(3)->toDateString(),
            'trip_type' => TripType::CAMPING->value,
            'status' => TripStatus::PLANNED->value,
        ]);

        $this->seed(PreparationTemplateSeeder::class);
        app(PreparationService::class)->generateFor($trip);
        $trip->preparationItems()->update([
            'status' => PreparationStatus::CONFIRMED->value,
            'status_updated_at' => now(),
        ]);

        return $trip->fresh();
    }

    private function freshWeatherFor(Trail $trail): void
    {
        WeatherSnapshot::factory()->create([
            'adm4_code' => $trail->weather_adm4_code,
            'forecast_at' => now()->addHours(6),
            'fetched_at' => now()->subHour(),
        ]);
    }

    private function staleWeatherFor(Trail $trail): void
    {
        WeatherSnapshot::factory()->create([
            'adm4_code' => $trail->weather_adm4_code,
            'forecast_at' => now()->subDays(2),
            'fetched_at' => now()->subDays(3),
        ]);
    }
}
