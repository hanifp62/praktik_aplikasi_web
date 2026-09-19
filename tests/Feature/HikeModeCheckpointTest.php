<?php

namespace Tests\Feature;

use App\Enums\HikingSessionStatus;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Livewire\Trips\HikeMode;
use App\Models\Checkpoint;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * F-09 dan F-17.
 *
 * Checkpoint berikutnya sebelumnya ditentukan sebagai "satu setelah yang terdekat".
 * Pendaki yang masih 100 m sebelum Pos 3 paling dekat ke Pos 3, sehingga sistem
 * menunjuk Pos 4 — pos yang sedang dituju dilewati dan jarak yang ditampilkan
 * mengarah ke pos yang salah.
 *
 * Posisi juga diterima tanpa pemeriksaan rentang sama sekali.
 */
class HikeModeCheckpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_next_checkpoint_is_the_one_being_approached(): void
    {
        $trip = $this->tripWithCheckpoints();

        // Posisi tepat sebelum Pos 3, di luar radius kedatangan 75 m.
        $component = Livewire::actingAs($trip->user)
            ->test(HikeMode::class, ['trip' => $trip])
            ->call('updatePosition', -7.9938, 112.9500);

        $this->assertSame(
            3,
            $component->get('nextCheckpoint')['sequence'],
            'Pos yang sedang dituju tidak boleh dilewati.'
        );
    }

    public function test_a_checkpoint_within_the_arrival_radius_counts_as_reached(): void
    {
        $trip = $this->tripWithCheckpoints();

        $component = Livewire::actingAs($trip->user)
            ->test(HikeMode::class, ['trip' => $trip])
            ->call('updatePosition', -7.9930, 112.9500); // tepat di Pos 3

        $this->assertSame(4, $component->get('nextCheckpoint')['sequence']);
        $this->assertSame(3, $trip->hikingSession->fresh()->reached_checkpoint_sequence);
    }

    public function test_progress_is_not_lost_when_moving_backwards(): void
    {
        $trip = $this->tripWithCheckpoints();

        $component = Livewire::actingAs($trip->user)->test(HikeMode::class, ['trip' => $trip]);

        $component->call('updatePosition', -7.9930, 112.9500);   // mencapai Pos 3
        $component->call('updatePosition', -7.9960, 112.9500);   // mundur ke arah Pos 1

        $this->assertSame(
            3,
            $trip->hikingSession->fresh()->reached_checkpoint_sequence,
            'Pos yang sudah dicapai tidak menjadi belum dicapai hanya karena pendaki mundur.'
        );
    }

    public function test_an_out_of_range_position_is_rejected(): void
    {
        $trip = $this->tripWithCheckpoints();

        Livewire::actingAs($trip->user)
            ->test(HikeMode::class, ['trip' => $trip])
            ->call('updatePosition', 999.0, 999.0)
            ->assertHasErrors(['latitude', 'longitude']);
    }

    public function test_a_valid_position_is_accepted(): void
    {
        $trip = $this->tripWithCheckpoints();

        Livewire::actingAs($trip->user)
            ->test(HikeMode::class, ['trip' => $trip])
            ->call('updatePosition', -7.9950, 112.9500)
            ->assertSet('latitude', -7.9950);
    }

    private function tripWithCheckpoints(): TripPlan
    {
        $user = User::factory()->create();
        $mountain = Mountain::factory()->create();
        $trail = Trail::factory()->for($mountain)->create();

        // Empat pos berjajar ke utara, berjarak sekitar 110 m antar pos.
        foreach ([1 => -7.9970, 2 => -7.9950, 3 => -7.9930, 4 => -7.9910] as $sequence => $latitude) {
            Checkpoint::factory()->for($trail)->create([
                'sequence' => $sequence,
                'name' => 'Pos '.$sequence,
                'latitude' => $latitude,
                'longitude' => 112.9500,
            ]);
        }

        $trip = TripPlan::create([
            'user_id' => $user->id,
            'trail_id' => $trail->id,
            'name' => 'Uji hike mode',
            'planned_date' => now()->toDateString(),
            'trip_type' => TripType::TEKTOK->value,
            'status' => TripStatus::IN_PROGRESS->value,
        ]);

        $trip->hikingSession()->create([
            'user_id' => $user->id,
            'status' => HikingSessionStatus::ACTIVE->value,
            'started_at' => now(),
        ]);

        return $trip->fresh();
    }
}
