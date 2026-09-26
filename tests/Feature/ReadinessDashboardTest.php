<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\PreparationStatus;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Models\Mountain;
use App\Models\ReadinessCheck;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\PreparationService;
use Database\Seeders\PreparationTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
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
 * kehilangannya bukan cacat kosmetik. Halaman kini React; aksinya rute POST.
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

        $this->actingAs($trip->user)
            ->post(route('trips.readiness.recompute', $trip))
            ->assertRedirect(route('trips.readiness', $trip))
            ->assertSessionHas('status', 'Kesiapan dihitung ulang.');

        $this->assertSame(1, ReadinessCheck::count());
    }

    public function test_pre_departure_confirmation_survives_a_reload(): void
    {
        $trip = $this->trip();

        $this->actingAs($trip->user)
            ->post(route('trips.readiness.confirm', $trip))
            ->assertSessionHas('status', 'Pre-departure check tercatat.');

        // Memuat ulang halaman: konfirmasinya harus tetap terbaca.
        $this->get(route('trips.readiness', $trip))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Trips/Readiness')
                ->where('preDeparture.dikonfirmasi', true)
                ->where('preDeparture.dicatat', fn (?string $v) => $v !== null && str_contains($v, 'WIB')));
    }

    public function test_confirming_moves_the_trip_to_ready_for_departure(): void
    {
        $trip = $this->trip();

        $this->actingAs($trip->user)->post(route('trips.readiness.confirm', $trip));

        $this->assertSame(TripStatus::READY_FOR_DEPARTURE, $trip->fresh()->status);
    }

    public function test_the_assessment_is_still_shown_without_writing(): void
    {
        $trip = $this->trip();

        $this->actingAs($trip->user)
            ->get(route('trips.readiness', $trip))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Trips/Readiness')
                ->where('title', 'Kesiapan Pendakian')
                ->where('preDeparture.dikonfirmasi', false)
                ->where('check.label', fn (string $v) => in_array($v, ['Siap', 'Perlu persiapan', 'Tidak direkomendasikan'], true))
                ->where('bolehKonfirmasi', true)
                ->has('check.penjelasan'));

        $this->assertSame(0, ReadinessCheck::count());
    }

    /**
     * BR-09 dan R-005: skor internal dan angka kecocokan tidak pernah sampai ke props.
     * Persiapan dikirim sebagai hitungan, bukan persentase.
     */
    public function test_no_internal_score_or_percentage_reaches_the_page(): void
    {
        $this->seed(PreparationTemplateSeeder::class);
        $trip = $this->trip();

        $halaman = $this->actingAs($trip->user)->get(route('trips.readiness', $trip));
        $props = json_encode($halaman->viewData('page')['props']);

        $this->assertStringNotContainsString('internal_score', $props);
        $this->assertStringNotContainsString('score', strtolower($props));
        $halaman->assertInertia(fn (AssertableInertia $page) => $page
            ->where('check.persiapan', fn (string $v) => (bool) preg_match('/^\d+ dari \d+ item dikonfirmasi$/', $v)));
    }

    public function test_another_user_cannot_recompute_or_confirm(): void
    {
        $trip = $this->trip();
        $penyusup = User::factory()->create();

        $this->actingAs($penyusup)->post(route('trips.readiness.recompute', $trip))->assertForbidden();
        $this->actingAs($penyusup)->post(route('trips.readiness.confirm', $trip))->assertForbidden();

        $this->assertSame(0, ReadinessCheck::count());
        $this->assertSame(TripStatus::PLANNED, $trip->fresh()->status);
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
