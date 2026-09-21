<?php

namespace Tests\Feature;

use App\Enums\HikingSessionStatus;
use App\Models\Checkpoint;
use App\Models\HikeTrackPoint;
use App\Models\HikingSession;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Waktu tempuh antarpos di halaman jalur.
 *
 * Yang dijaga di sini bukan tata letaknya, melainkan kejujuran angkanya: jumlah rekaman
 * harus ikut terbaca, rentangnya harus disebut, dan tidak ada apa pun yang muncul
 * sebelum rekamannya cukup.
 */
class CheckpointPaceDisplayTest extends TestCase
{
    use RefreshDatabase;

    private Trail $trail;

    private Checkpoint $pos1;

    private Checkpoint $pos2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->trail = Trail::factory()->published()->easy()->create();

        $this->pos1 = Checkpoint::factory()->for($this->trail)->create([
            'sequence' => 1, 'name' => 'Pos 1', 'latitude' => -7.4500, 'longitude' => 110.4400,
        ]);

        $this->pos2 = Checkpoint::factory()->for($this->trail)->create([
            'sequence' => 2, 'name' => 'Pos 2', 'latitude' => -7.4600, 'longitude' => 110.4400,
        ]);
    }

    private function rekam(int $menit): void
    {
        $user = User::factory()->create();
        $trip = TripPlan::factory()->create(['user_id' => $user->id, 'trail_id' => $this->trail->id]);

        $sesi = HikingSession::create([
            'trip_plan_id' => $trip->id,
            'user_id' => $user->id,
            'status' => HikingSessionStatus::COMPLETED->value,
            'started_at' => Carbon::parse('2026-09-15 04:00:00'),
        ]);

        foreach ([[0, $this->pos1], [$menit, $this->pos2]] as [$geser, $pos]) {
            HikeTrackPoint::create([
                'hiking_session_id' => $sesi->id,
                'recorded_at' => Carbon::parse('2026-09-15 05:00:00')->addMinutes($geser),
                'latitude' => $pos->latitude,
                'longitude' => $pos->longitude,
            ]);
        }
    }

    private function buka()
    {
        return $this->actingAs(User::factory()->create())->get(route('trails.show', $this->trail));
    }

    public function test_the_typical_time_is_shown_with_its_range_and_sample_size(): void
    {
        foreach ([40, 50, 60, 70, 80] as $menit) {
            $this->rekam($menit);
        }

        $halaman = $this->buka();

        $halaman->assertSee('Biasanya 1 jam');
        $halaman->assertSee('terentang 40 menit sampai 1 jam 20 menit');
        $halaman->assertSee('menurut 5 rekaman pendaki');
    }

    /**
     * Jumlah rekamannya bukan hiasan. Tanpa angka itu pembaca tidak dapat menimbang
     * seberapa jauh angka di sebelahnya layak dipercaya, dan lima rekaman terbaca sama
     * meyakinkannya dengan lima ratus.
     */
    public function test_the_sample_size_is_never_hidden(): void
    {
        foreach ([40, 50, 60, 70, 80, 90] as $menit) {
            $this->rekam($menit);
        }

        $this->buka()->assertSee('menurut 6 rekaman pendaki');
    }

    /**
     * Di bawah ambang, tidak ada angka sama sekali. Menampilkan "biasanya 50 menit"
     * dari dua rekaman adalah mengaku tahu sesuatu yang belum diketahui.
     */
    public function test_nothing_appears_below_the_threshold(): void
    {
        foreach ([40, 50, 60, 70] as $menit) {
            $this->rekam($menit);
        }

        $halaman = $this->buka();

        $halaman->assertOk();
        $halaman->assertDontSee('Biasanya');
        $halaman->assertDontSee('rekaman pendaki');
    }

    /**
     * Jalur yang belum punya satu pun rekaman tetap menampilkan pos dan jaraknya.
     */
    public function test_the_journey_still_works_without_any_recordings(): void
    {
        $this->buka()
            ->assertOk()
            ->assertSee('Pos 1')
            ->assertSee('Pos 2')
            ->assertDontSee('rekaman pendaki');
    }
}
