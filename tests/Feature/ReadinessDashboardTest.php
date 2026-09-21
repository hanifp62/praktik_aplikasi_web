<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\PreparationStatus;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Livewire\Trips\ReadinessDashboard;
use App\Models\Mountain;
use App\Models\ReadinessCheck;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\PreparationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * F-07 dan F-08.
 *
 * ReadinessService::evaluate() selalu membuat baris baru, dan komponennya
 * memanggilnya dari mount(). Akibatnya sekadar membuka halaman menulis ke basis
 * data, tiga kali buka menghasilkan tiga baris, dan penanda
 * pre_departure_confirmed yang ditulis pada satu baris langsung digantikan baris
 * baru pada pemuatan berikutnya, sehingga konfirmasi pengguna hilang.
 *
 * Konfirmasi pre-departure adalah langkah inti North Star Metric (PRD §63), jadi
 * kehilangannya bukan cacat kosmetik.
 */
class ReadinessDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewing_readiness_does_not_write_rows(): void
    {
        $trip = $this->trip();

        $this->actingAs($trip->user);
        $this->get(route('trips.readiness', $trip))->assertOk();
        $this->get(route('trips.readiness', $trip))->assertOk();
        $this->get(route('trips.readiness', $trip))->assertOk();

        $this->assertSame(
            0,
            ReadinessCheck::count(),
            'Membuka halaman bukan peristiwa yang layak dicatat.'
        );
    }

    public function test_recomputing_records_exactly_one_row(): void
    {
        $trip = $this->trip();

        Livewire::actingAs($trip->user)
            ->test(ReadinessDashboard::class, ['trip' => $trip])
            ->call('recompute');

        $this->assertSame(1, ReadinessCheck::count());
    }

    public function test_pre_departure_confirmation_survives_a_reload(): void
    {
        $trip = $this->trip();

        Livewire::actingAs($trip->user)
            ->test(ReadinessDashboard::class, ['trip' => $trip])
            ->call('confirmPreDeparture')
            ->assertSet('preDepartureConfirmed', true);

        // Memuat ulang halaman: konfirmasinya harus tetap terbaca.
        Livewire::actingAs($trip->user)
            ->test(ReadinessDashboard::class, ['trip' => $trip])
            ->assertSet('preDepartureConfirmed', true);
    }

    public function test_confirming_moves_the_trip_to_ready_for_departure(): void
    {
        $trip = $this->trip();

        Livewire::actingAs($trip->user)
            ->test(ReadinessDashboard::class, ['trip' => $trip])
            ->call('confirmPreDeparture');

        $this->assertSame(TripStatus::READY_FOR_DEPARTURE, $trip->fresh()->status);
    }

    public function test_the_assessment_is_still_shown_without_writing(): void
    {
        $trip = $this->trip();

        Livewire::actingAs($trip->user)
            ->test(ReadinessDashboard::class, ['trip' => $trip])
            ->assertSet('preDepartureConfirmed', false)
            ->assertStatus(200);

        $this->assertSame(0, ReadinessCheck::count());
    }

    private function trip(): TripPlan
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::ADVANCED->value, 'completed_at' => now()]);

        $mountain = Mountain::factory()->create();
        $trail = Trail::factory()->published()->for($mountain)->easy()->create();

        $trip = TripPlan::create([
            'user_id' => $user->id,
            'trail_id' => $trail->id,
            'name' => 'Uji kesiapan',
            'planned_date' => now()->addDays(5)->toDateString(),
            'trip_type' => TripType::CAMPING->value,
            'status' => TripStatus::PLANNED->value,
        ]);

        // Seluruh item persiapan dikonfirmasi agar state tidak tertahan di sana.
        app(PreparationService::class)->generateFor($trip);
        $trip->preparationItems()->update([
            'status' => PreparationStatus::CONFIRMED->value,
            'status_updated_at' => now(),
        ]);

        return $trip->fresh();
    }
}
