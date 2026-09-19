<?php

namespace Tests\Feature;

use App\Enums\CompletionState;
use App\Enums\HikingSessionStatus;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Livewire\Trips\TripForm;
use App\Livewire\Trips\TripShow;
use App\Models\HikingHistory;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * F-11, F-12, F-13.
 *
 * Pembuatan trip hanya memvalidasi exists:trails,id sehingga jalur yang belum
 * dipublikasikan atau sudah diarsipkan tetap dapat dipakai — padahal mesin route fit
 * menolak jalur seperti itu sebagai hard constraint.
 *
 * Tidak ada penjaga transisi status, sehingga trip yang sudah dibatalkan masih dapat
 * dimulai dan diselesaikan. completion_state divalidasi sebagai string biasa padahal
 * kolomnya di-cast ke enum, sehingga nilai asing menghasilkan error 500.
 */
class TripLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_trip_cannot_be_created_on_an_unpublished_trail(): void
    {
        $hidden = Trail::factory()->unpublished()->create();

        Livewire::actingAs(User::factory()->create())
            ->test(TripForm::class)
            ->set('trail_id', $hidden->id)
            ->set('name', 'Coba jalur tersembunyi')
            ->set('planned_date', now()->addDay()->toDateString())
            ->set('trip_type', TripType::TEKTOK->value)
            ->call('save')
            ->assertHasErrors('trail_id');

        $this->assertSame(0, TripPlan::count());
    }

    public function test_a_trip_cannot_be_created_on_an_archived_trail(): void
    {
        $archived = Trail::factory()->create(['archived_at' => now()]);

        Livewire::actingAs(User::factory()->create())
            ->test(TripForm::class)
            ->set('trail_id', $archived->id)
            ->set('name', 'Coba jalur arsip')
            ->set('planned_date', now()->addDay()->toDateString())
            ->set('trip_type', TripType::TEKTOK->value)
            ->call('save')
            ->assertHasErrors('trail_id');
    }

    public function test_a_trip_can_be_created_on_a_published_trail(): void
    {
        $trail = Trail::factory()->create();

        Livewire::actingAs(User::factory()->create())
            ->test(TripForm::class)
            ->set('trail_id', $trail->id)
            ->set('name', 'Rencana sah')
            ->set('planned_date', now()->addDay()->toDateString())
            ->set('trip_type', TripType::TEKTOK->value)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, TripPlan::count());
    }

    public function test_a_cancelled_trip_cannot_be_started(): void
    {
        $trip = $this->trip(TripStatus::CANCELLED);

        Livewire::actingAs($trip->user)
            ->test(TripShow::class, ['trip' => $trip])
            ->call('startHike');

        $this->assertSame(TripStatus::CANCELLED, $trip->fresh()->status);
    }

    public function test_a_completed_trip_cannot_be_cancelled(): void
    {
        $trip = $this->trip(TripStatus::COMPLETED);

        Livewire::actingAs($trip->user)
            ->test(TripShow::class, ['trip' => $trip])
            ->call('cancel');

        $this->assertSame(TripStatus::COMPLETED, $trip->fresh()->status);
    }

    public function test_a_planned_trip_cannot_be_completed_without_starting(): void
    {
        $trip = $this->trip(TripStatus::PLANNED);

        Livewire::actingAs($trip->user)
            ->test(TripShow::class, ['trip' => $trip])
            ->set('completion_state', CompletionState::COMPLETED->value)
            ->call('complete');

        $this->assertSame(TripStatus::PLANNED, $trip->fresh()->status);
        $this->assertSame(0, HikingHistory::count());
    }

    public function test_an_in_progress_trip_can_be_completed(): void
    {
        $trip = $this->trip(TripStatus::IN_PROGRESS);
        $trip->hikingSession()->create([
            'user_id' => $trip->user_id,
            'status' => HikingSessionStatus::ACTIVE->value,
            'started_at' => now(),
        ]);

        Livewire::actingAs($trip->user)
            ->test(TripShow::class, ['trip' => $trip])
            ->set('completion_state', CompletionState::COMPLETED->value)
            ->call('complete');

        $this->assertSame(TripStatus::COMPLETED, $trip->fresh()->status);
        $this->assertSame(1, HikingHistory::count());
    }

    public function test_an_unknown_completion_state_is_a_validation_error_not_a_crash(): void
    {
        $trip = $this->trip(TripStatus::IN_PROGRESS);

        Livewire::actingAs($trip->user)
            ->test(TripShow::class, ['trip' => $trip])
            ->set('completion_state', 'MENDAKI_SAMBIL_TERBANG')
            ->call('complete')
            ->assertHasErrors('completion_state');

        $this->assertSame(TripStatus::IN_PROGRESS, $trip->fresh()->status);
    }

    public function test_the_transition_table_is_explicit(): void
    {
        $this->assertTrue(TripStatus::PLANNED->canTransitionTo(TripStatus::IN_PROGRESS));
        $this->assertTrue(TripStatus::IN_PROGRESS->canTransitionTo(TripStatus::COMPLETED));
        $this->assertFalse(TripStatus::COMPLETED->canTransitionTo(TripStatus::IN_PROGRESS));
        $this->assertFalse(TripStatus::CANCELLED->canTransitionTo(TripStatus::IN_PROGRESS));
        $this->assertSame([], TripStatus::COMPLETED->allowedTransitions());
    }

    private function trip(TripStatus $status): TripPlan
    {
        $user = User::factory()->create();
        $mountain = Mountain::factory()->create();
        $trail = Trail::factory()->for($mountain)->create();

        return TripPlan::create([
            'user_id' => $user->id,
            'trail_id' => $trail->id,
            'name' => 'Uji siklus hidup',
            'planned_date' => now()->addDay()->toDateString(),
            'trip_type' => TripType::TEKTOK->value,
            'status' => $status->value,
        ])->fresh();
    }
}
