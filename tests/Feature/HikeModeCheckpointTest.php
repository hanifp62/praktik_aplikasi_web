<?php

namespace Tests\Feature;

use App\Enums\HikingSessionStatus;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Models\Checkpoint;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * F-09 dan F-17.
 *
 * Checkpoint berikutnya sebelumnya ditentukan sebagai "satu setelah yang terdekat".
 * Pendaki yang masih 100 m sebelum Pos 3 paling dekat ke Pos 3, sehingga sistem
 * menunjuk Pos 4, pos yang sedang dituju dilewati dan jarak yang ditampilkan
 * mengarah ke pos yang salah.
 *
 * Posisi juga diterima tanpa pemeriksaan rentang sama sekali.
 *
 * Mode pendakian kini React: posisi dikirim lewat POST, lalu halaman menampilkan
 * pos berikutnya dari jawaban server.
 */
class HikeModeCheckpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_next_checkpoint_is_the_one_being_approached(): void
    {
        $trip = $this->tripWithCheckpoints();

        // Posisi tepat sebelum Pos 3, di luar radius kedatangan 75 m.
        $this->kirimPosisi($trip, -7.9938, 112.9500)
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Trips/Hike')
                ->where('berikutnya.sequence', 3)
                ->where('berikutnya.name', 'Pos 3')
                ->where('berikutnya.jarak', '89 m'));
    }

    public function test_a_checkpoint_within_the_arrival_radius_counts_as_reached(): void
    {
        $trip = $this->tripWithCheckpoints();

        $this->kirimPosisi($trip, -7.9930, 112.9500) // tepat di Pos 3
            ->assertInertia(fn (AssertableInertia $page) => $page->where('berikutnya.sequence', 4));

        $this->assertSame(3, $trip->hikingSession->fresh()->reached_checkpoint_sequence);
    }

    public function test_progress_is_not_lost_when_moving_backwards(): void
    {
        $trip = $this->tripWithCheckpoints();

        $this->kirimPosisi($trip, -7.9930, 112.9500);   // mencapai Pos 3
        $this->kirimPosisi($trip, -7.9960, 112.9500);   // mundur ke arah Pos 1

        $this->assertSame(
            3,
            $trip->hikingSession->fresh()->reached_checkpoint_sequence,
            'Pos yang sudah dicapai tidak menjadi belum dicapai hanya karena pendaki mundur.'
        );
    }

    /**
     * Setelah dimuat ulang, pos yang tercatat pada sesi tetap ditampilkan, tanpa jarak:
     * posisinya tidak disimpan sebagai riwayat, jadi jaraknya tidak dapat dihitung.
     */
    public function test_a_reload_keeps_the_recorded_next_checkpoint_without_a_distance(): void
    {
        $trip = $this->tripWithCheckpoints();
        $this->kirimPosisi($trip, -7.9938, 112.9500);

        $this->get(route('trips.hike', $trip))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('berikutnya.sequence', 3)
                ->where('berikutnya.jarak', null)
                ->where('posisi', null));
    }

    public function test_an_out_of_range_position_is_rejected(): void
    {
        $trip = $this->tripWithCheckpoints();

        $this->actingAs($trip->user)
            ->post(route('trips.hike.position', $trip), ['latitude' => 999.0, 'longitude' => 999.0])
            ->assertSessionHasErrors(['latitude', 'longitude']);

        $this->assertNull($trip->hikingSession->fresh()->location_updated_at);
    }

    public function test_a_valid_position_is_accepted(): void
    {
        $trip = $this->tripWithCheckpoints();

        $this->kirimPosisi($trip, -7.9950, 112.9500)
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('posisi.lat', -7.995)
                ->where('posisi.koordinat', '-7.99500, 112.95000'));

        $this->assertNotNull($trip->hikingSession->fresh()->location_updated_at);
    }

    /**
     * Tanpa koordinat pos, halaman mengakuinya alih-alih menebak jarak.
     */
    public function test_checkpoints_without_coordinates_are_admitted_not_guessed(): void
    {
        $trip = $this->tripWithCheckpoints(berkoordinat: false);

        $this->kirimPosisi($trip, -7.9950, 112.9500)
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('posBerkoordinat', 0)
                ->where('berikutnya.jarak', null)
                ->has('penanda', 0));
    }

    public function test_another_user_cannot_send_a_position(): void
    {
        $trip = $this->tripWithCheckpoints();

        $this->actingAs(User::factory()->create())
            ->post(route('trips.hike.position', $trip), ['latitude' => -7.9950, 'longitude' => 112.9500])
            ->assertForbidden();

        $this->assertNull($trip->hikingSession->fresh()->location_updated_at);
    }

    private function kirimPosisi(TripPlan $trip, float $lat, float $lng): TestResponse
    {
        return $this->actingAs($trip->user)
            ->followingRedirects()
            ->post(route('trips.hike.position', $trip), ['latitude' => $lat, 'longitude' => $lng])
            ->assertOk();
    }

    private function tripWithCheckpoints(bool $berkoordinat = true): TripPlan
    {
        $user = User::factory()->create();
        $mountain = Mountain::factory()->create();
        $trail = Trail::factory()->for($mountain)->create();

        // Empat pos berjajar ke utara, berjarak sekitar 110 m antar pos.
        foreach ([1 => -7.9970, 2 => -7.9950, 3 => -7.9930, 4 => -7.9910] as $sequence => $latitude) {
            Checkpoint::factory()->for($trail)->create([
                'sequence' => $sequence,
                'name' => 'Pos '.$sequence,
                'latitude' => $berkoordinat ? $latitude : null,
                'longitude' => $berkoordinat ? 112.9500 : null,
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
