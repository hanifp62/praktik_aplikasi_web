<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\PreparationCategory;
use App\Enums\PreparationStatus;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Models\HikingGoal;
use App\Models\PermitRequirement;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\PreparationService;
use App\Services\RouteFitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Aturan perizinan hanya berguna bila sampai ke pendaki pada saat ia masih dapat
 * berbuat sesuatu: ketika memilih jalur, dan ketika menyusun persiapan.
 */
class PermitVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_trail_detail_page_shows_the_permit_rules(): void
    {
        $trail = Trail::factory()->create();
        PermitRequirement::factory()->create([
            'trail_id' => $trail->id,
            'authority' => 'TN Bromo Tengger Semeru',
            'daily_quota' => 200,
            'guide_required' => true,
        ]);

        $html = $this->actingAs(User::factory()->create())
            ->get(route('trails.show', $trail))
            ->getContent();

        $this->assertStringContainsString('Perizinan pendakian', $html);
        $this->assertStringContainsString('TN Bromo Tengger Semeru', $html);
        $this->assertStringContainsString('Wajib pemandu terdaftar', $html);
    }

    public function test_a_trail_without_permit_rules_shows_no_permit_section(): void
    {
        $trail = Trail::factory()->create();

        $html = $this->actingAs(User::factory()->create())
            ->get(route('trails.show', $trail))
            ->getContent();

        $this->assertStringNotContainsString('Perizinan pendakian', $html);
    }

    public function test_a_recommendation_warns_when_the_booking_window_has_closed(): void
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::INTERMEDIATE->value, 'completed_at' => now()]);

        $trail = Trail::factory()->create();
        PermitRequirement::factory()->create([
            'trail_id' => $trail->id,
            'authority' => 'TN Bromo Tengger Semeru',
            'booking_closes_days_before' => 2,
        ]);

        $goal = HikingGoal::factory()->create([
            'user_id' => $user->id,
            'target_date' => now()->addDay()->toDateString(),
        ]);

        $run = app(RouteFitService::class)->recommend($user->fresh(), $goal);

        $this->assertStringContainsString('H-2', implode(' ', $run->warnings));
    }

    public function test_the_preparation_checklist_gains_a_critical_permit_item(): void
    {
        $trail = Trail::factory()->create();
        PermitRequirement::factory()->create([
            'trail_id' => $trail->id,
            'authority' => 'TN Bromo Tengger Semeru',
            'booking_url' => 'https://bookingsemeru.bromotenggersemeru.org',
        ]);

        $trip = $this->tripFor($trail);
        app(PreparationService::class)->generateFor($trip);

        $item = $trip->preparationItems()->where('label', 'Booking izin pendakian (SIMAKSI)')->first();

        $this->assertNotNull($item, 'Checklist harus memuat item perizinan.');
        $this->assertTrue($item->is_critical, 'Izin tidak dapat diurus di basecamp pada hari keberangkatan.');
        $this->assertSame(PreparationCategory::LOGISTICS, $item->category);
        $this->assertStringContainsString('bookingsemeru', $item->description);
    }

    public function test_a_trail_without_permit_rules_gains_no_permit_item(): void
    {
        $trip = $this->tripFor(Trail::factory()->create());
        app(PreparationService::class)->generateFor($trip);

        $this->assertNull(
            $trip->preparationItems()->where('label', 'Booking izin pendakian (SIMAKSI)')->first()
        );
    }

    public function test_regenerating_keeps_the_permit_item_status(): void
    {
        $trail = Trail::factory()->create();
        PermitRequirement::factory()->create(['trail_id' => $trail->id]);

        $trip = $this->tripFor($trail);
        $service = app(PreparationService::class);

        $service->generateFor($trip);
        $trip->preparationItems()
            ->where('label', 'Booking izin pendakian (SIMAKSI)')
            ->update(['status' => PreparationStatus::CONFIRMED->value]);

        $service->generateFor($trip->fresh());

        $this->assertSame(
            PreparationStatus::CONFIRMED,
            $trip->preparationItems()->where('label', 'Booking izin pendakian (SIMAKSI)')->first()->status
        );
    }

    private function tripFor(Trail $trail): TripPlan
    {
        return TripPlan::create([
            'user_id' => User::factory()->create()->id,
            'trail_id' => $trail->id,
            'name' => 'Uji perizinan',
            'planned_date' => now()->addDays(5)->toDateString(),
            'trip_type' => TripType::CAMPING->value,
            'status' => TripStatus::PLANNED->value,
        ])->fresh();
    }
}
