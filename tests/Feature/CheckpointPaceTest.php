<?php

namespace Tests\Feature;

use App\Enums\HikingSessionStatus;
use App\Models\Checkpoint;
use App\Models\HikeTrackPoint;
use App\Models\HikingSession;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\CheckpointPaceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Waktu tempuh khas antarpos.
 *
 * Adaptasi segmen Strava: strukturnya dipakai, perlombaannya tidak. Yang dihasilkan
 * bukan peringkat siapa tercepat, melainkan keterangan yang dibutuhkan saat
 * merencanakan.
 *
 * Angkanya dihitung tangan di test ini. Ambang lima rekaman juga bukan selera: untuk
 * sampel berukuran n, peluang rentang [min, maks] memuat median populasi adalah
 * 1 - 2(1/2)^n, yang pada n=5 bernilai 93,75 persen dan pada n=4 baru 87,5 persen.
 */
class CheckpointPaceTest extends TestCase
{
    use RefreshDatabase;

    private Trail $trail;

    private Checkpoint $pos1;

    private Checkpoint $pos2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->trail = Trail::factory()->easy()->create();

        $this->pos1 = Checkpoint::factory()->for($this->trail)->create([
            'sequence' => 1, 'name' => 'Pos 1', 'latitude' => -7.4500, 'longitude' => 110.4400,
        ]);

        $this->pos2 = Checkpoint::factory()->for($this->trail)->create([
            'sequence' => 2, 'name' => 'Pos 2', 'latitude' => -7.4600, 'longitude' => 110.4400,
        ]);
    }

    /**
     * Satu pendakian yang lewat Pos 1 lalu Pos 2 sekian menit kemudian.
     */
    private function rekamPendakian(int $menit, bool $sampaiPos2 = true): void
    {
        $user = User::factory()->create();
        $trip = TripPlan::factory()->create(['user_id' => $user->id, 'trail_id' => $this->trail->id]);

        $sesi = HikingSession::create([
            'trip_plan_id' => $trip->id,
            'user_id' => $user->id,
            'status' => HikingSessionStatus::COMPLETED->value,
            'started_at' => Carbon::parse('2026-09-15 04:00:00'),
        ]);

        $mulai = Carbon::parse('2026-09-15 05:00:00');

        HikeTrackPoint::create([
            'hiking_session_id' => $sesi->id,
            'recorded_at' => $mulai,
            'latitude' => $this->pos1->latitude,
            'longitude' => $this->pos1->longitude,
        ]);

        if ($sampaiPos2) {
            HikeTrackPoint::create([
                'hiking_session_id' => $sesi->id,
                'recorded_at' => $mulai->copy()->addMinutes($menit),
                'latitude' => $this->pos2->latitude,
                'longitude' => $this->pos2->longitude,
            ]);
        }
    }

    private function hitung(): array
    {
        return app(CheckpointPaceService::class)->forTrail($this->trail->fresh()->load('checkpoints'));
    }

    /**
     * Empat rekaman belum cukup. Pada n=4 peluang rentangnya memuat median sebenarnya
     * baru 87,5 persen, dan angka yang ditampilkan di bawah itu mengaku tahu lebih
     * banyak daripada yang diketahuinya.
     */
    public function test_four_recordings_are_not_enough_to_say_anything(): void
    {
        foreach ([40, 50, 60, 70] as $menit) {
            $this->rekamPendakian($menit);
        }

        $this->assertSame([], $this->hitung());
    }

    /**
     * Lima rekaman, median dihitung tangan dari 40, 50, 60, 70, 80 adalah 60.
     */
    public function test_five_recordings_produce_a_median_and_a_range(): void
    {
        foreach ([40, 50, 60, 70, 80] as $menit) {
            $this->rekamPendakian($menit);
        }

        $hasil = $this->hitung();

        $this->assertCount(1, $hasil);
        $this->assertSame(5, $hasil[0]['rekaman']);
        $this->assertSame(60, $hasil[0]['median_menit']);
        $this->assertSame(40, $hasil[0]['min_menit']);
        $this->assertSame(80, $hasil[0]['maks_menit']);
    }

    /**
     * Waktu tempuh pendaki miring ke kanan: satu orang yang jauh lebih lambat menarik
     * rata-rata ke atas, dan median tidak ikut tertarik. Dari 40, 45, 50, 55, dan 300,
     * rata-ratanya 98 sedangkan medannya 50.
     */
    public function test_one_very_slow_hiker_does_not_drag_the_typical_time(): void
    {
        foreach ([40, 45, 50, 55, 300] as $menit) {
            $this->rekamPendakian($menit);
        }

        $hasil = $this->hitung();

        $this->assertSame(50, $hasil[0]['median_menit'], 'Median menahan ekor panjangnya.');
        $this->assertSame(300, $hasil[0]['maks_menit'], 'Rentangnya tetap menyebut yang terlama apa adanya.');
    }

    public function test_an_even_number_of_recordings_averages_the_two_middle_values(): void
    {
        foreach ([40, 50, 60, 70, 80, 90] as $menit) {
            $this->rekamPendakian($menit);
        }

        // Dua nilai tengah dari enam: 60 dan 70.
        $this->assertSame(65, $this->hitung()[0]['median_menit']);
    }

    /**
     * Pendakian yang berhenti sebelum pos berikutnya bukan rekaman waktu tempuh, dan
     * menghitungnya sebagai nol akan menyatakan orang itu sampai seketika.
     */
    public function test_a_hike_that_turned_back_contributes_nothing(): void
    {
        foreach ([40, 50, 60, 70, 80] as $menit) {
            $this->rekamPendakian($menit);
        }

        foreach (range(1, 3) as $i) {
            $this->rekamPendakian(0, sampaiPos2: false);
        }

        $this->assertSame(5, $this->hitung()[0]['rekaman'], 'Yang berbalik tidak ikut terhitung.');
    }

    /**
     * Jejak yang menuruni jalur melewati pos yang sama dalam urutan terbalik, dan itu
     * perjalanan yang berbeda dari yang dibicarakan halaman ini.
     */
    public function test_a_descending_track_is_not_counted_as_an_ascent(): void
    {
        foreach ([40, 50, 60, 70, 80] as $menit) {
            $this->rekamPendakian($menit);
        }

        $user = User::factory()->create();
        $trip = TripPlan::factory()->create(['user_id' => $user->id, 'trail_id' => $this->trail->id]);
        $sesi = HikingSession::create([
            'trip_plan_id' => $trip->id,
            'user_id' => $user->id,
            'status' => HikingSessionStatus::COMPLETED->value,
            'started_at' => Carbon::parse('2026-09-16 04:00:00'),
        ]);

        // Pos 2 lebih dulu, Pos 1 kemudian.
        HikeTrackPoint::create([
            'hiking_session_id' => $sesi->id,
            'recorded_at' => Carbon::parse('2026-09-16 05:00:00'),
            'latitude' => $this->pos2->latitude, 'longitude' => $this->pos2->longitude,
        ]);
        HikeTrackPoint::create([
            'hiking_session_id' => $sesi->id,
            'recorded_at' => Carbon::parse('2026-09-16 05:30:00'),
            'latitude' => $this->pos1->latitude, 'longitude' => $this->pos1->longitude,
        ]);

        $this->assertSame(5, $this->hitung()[0]['rekaman']);
    }

    /**
     * Jejak yang lewat jauh dari posnya bukan bukti ia melewati pos itu.
     */
    public function test_a_track_passing_far_from_the_post_is_not_an_arrival(): void
    {
        foreach ([40, 50, 60, 70] as $menit) {
            $this->rekamPendakian($menit);
        }

        $user = User::factory()->create();
        $trip = TripPlan::factory()->create(['user_id' => $user->id, 'trail_id' => $this->trail->id]);
        $sesi = HikingSession::create([
            'trip_plan_id' => $trip->id,
            'user_id' => $user->id,
            'status' => HikingSessionStatus::COMPLETED->value,
            'started_at' => Carbon::parse('2026-09-17 04:00:00'),
        ]);

        // Sekitar satu kilometer dari kedua pos.
        foreach ([0, 50] as $offset) {
            HikeTrackPoint::create([
                'hiking_session_id' => $sesi->id,
                'recorded_at' => Carbon::parse('2026-09-17 05:00:00')->addMinutes($offset),
                'latitude' => -7.4700, 'longitude' => 110.4500,
            ]);
        }

        $this->assertSame([], $this->hitung(), 'Empat rekaman sah ditambah satu yang lewat jauh tetap empat.');
    }

    public function test_a_trail_without_recordings_says_nothing(): void
    {
        $this->assertSame([], $this->hitung());
    }
}
