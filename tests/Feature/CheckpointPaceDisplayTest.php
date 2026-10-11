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
 * Waktu tempuh di halaman jalur.
 *
 * Yang dijaga di sini bukan tata letaknya, melainkan kejujuran angkanya: jumlah rekaman
 * harus ikut terbaca, rentangnya harus disebut (DK-31), dan tidak ada apa pun yang muncul
 * sebelum rekamannya cukup. Sejak W3 daftar pos menulis waktu dari awal sebagai rentang,
 * dan jumlah rekaman terkecil per ruas di kepala daftar (waktuDariRekaman).
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

    /**
     * @return array<string, mixed>
     */
    private function props(): array
    {
        return $this->actingAs(User::factory()->create())
            ->get(route('trails.show', $this->trail))
            ->assertOk()
            ->viewData('page')['props'];
    }

    /**
     * Seluruh teks waktu dari awal yang dikirim ke halaman, disambung.
     */
    private function tempo(): string
    {
        return implode(' | ', array_filter(array_column($this->props()['pos'], 'waktu')));
    }

    public function test_the_time_from_the_start_is_a_range_with_its_sample_size(): void
    {
        foreach ([40, 50, 60, 70, 80] as $menit) {
            $this->rekam($menit);
        }

        $this->assertStringContainsString('40 menit sampai 1 jam 20 menit dari awal', $this->tempo());
        $this->assertSame(5, $this->props()['waktuDariRekaman']);
        $this->assertStringContainsString('rekaman per ruas', file_get_contents(resource_path('js/pages/Trails/Show.tsx')));
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

        $this->assertSame(6, $this->props()['waktuDariRekaman']);
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

        $this->assertSame('', $this->tempo());
        $this->assertNull($this->props()['waktuDariRekaman']);
    }

    /**
     * Jalur yang belum punya satu pun rekaman tetap menampilkan pos dan jaraknya.
     */
    public function test_the_journey_still_works_without_any_recordings(): void
    {
        $pos = $this->props()['pos'];

        $this->assertContains('Pos 1', array_column($pos, 'nama'));
        $this->assertContains('Pos 2', array_column($pos, 'nama'));
        $this->assertSame([], array_filter(array_column($pos, 'waktu')));
    }
}
