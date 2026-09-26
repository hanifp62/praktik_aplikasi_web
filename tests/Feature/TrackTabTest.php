<?php

namespace Tests\Feature;

use App\Enums\TripStatus;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Tab Mendaki (IA v2, DK-25): pintu mode lapangan.
 *
 * Pendaki yang mengetuk tab ini di jalur butuh mode pendakiannya, bukan daftar. Tanpa
 * pendakian berjalan, tab menampilkan trip yang benar-benar dapat dimulai.
 */
class TrackTabTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_hike_in_progress_opens_straight_into_hike_mode(): void
    {
        $trip = TripPlan::factory()->create(['status' => TripStatus::IN_PROGRESS->value]);

        $this->actingAs($trip->user)
            ->get(route('track'))
            ->assertRedirect(route('trips.hike', $trip));
    }

    public function test_only_trips_that_can_still_be_started_are_offered(): void
    {
        $user = User::factory()->create();
        $besok = Carbon::now('Asia/Jakarta')->addDay()->toDateString();
        $siap = TripPlan::factory()->for($user)->create(['name' => 'Siap', 'status' => TripStatus::READY_FOR_DEPARTURE->value, 'planned_date' => $besok]);
        $rencana = TripPlan::factory()->for($user)->create(['name' => 'Rencana', 'status' => TripStatus::PLANNED->value, 'planned_date' => $besok]);
        TripPlan::factory()->for($user)->create(['name' => 'Selesai', 'status' => TripStatus::COMPLETED->value]);
        TripPlan::factory()->for($user)->create(['name' => 'Batal', 'status' => TripStatus::CANCELLED->value, 'planned_date' => $besok]);
        TripPlan::factory()->for($user)->create(['name' => 'Lewat', 'status' => TripStatus::PLANNED->value, 'planned_date' => Carbon::now('Asia/Jakarta')->subDays(10)->toDateString()]);

        $this->actingAs($user)
            ->get(route('track'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Track/Index')
                ->where('title', 'Mendaki')
                ->has('trips', 2)
                ->where('trips.0.id', $siap->id)
                ->where('trips.1.id', $rencana->id)
                ->where('trips.0.urls.start', route('trips.start', $siap)));
    }

    public function test_someone_elses_trips_never_appear(): void
    {
        TripPlan::factory()->create(['status' => TripStatus::IN_PROGRESS->value]);
        TripPlan::factory()->create(['status' => TripStatus::PLANNED->value]);

        $this->actingAs(User::factory()->create())
            ->get(route('track'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->has('trips', 0));
    }

    public function test_guests_are_sent_to_sign_in(): void
    {
        $this->get(route('track'))->assertRedirect(route('login'));
    }
}
