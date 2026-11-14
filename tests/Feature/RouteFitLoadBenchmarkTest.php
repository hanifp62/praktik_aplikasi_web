<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\NavigationExperience;
use App\Enums\PreferredDuration;
use App\Enums\TripType;
use App\Models\Checkpoint;
use App\Models\DataSource;
use App\Models\HikingGoal;
use App\Models\Mountain;
use App\Models\PermitRequirement;
use App\Models\Trail;
use App\Models\User;
use App\Services\RouteFitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Uji beban evaluasi 300 jalur terbit pada RouteFitService (GAP-22, PRD §96).
 *
 * Memastikan evaluasi ratusan kandidat tetap memakai jumlah kueri konstan O(1)
 * dan selesai dalam batas waktu yang terkendali.
 */
class RouteFitLoadBenchmarkTest extends TestCase
{
    use RefreshDatabase;

    private const BATAS_WAKTU_MS = 2500;

    public function test_route_fit_evaluates_three_hundred_published_trails_in_constant_queries_and_bounded_time(): void
    {
        config(['hiking.route_fit.candidate_limit' => 300]);

        $user = User::factory()->create();
        $user->profile()->create([
            'experience_level' => ExperienceLevel::INTERMEDIATE->value,
            'completed_at' => now(),
        ]);
        $user->experience()->create([
            'completed_hikes_count' => 6,
            'terrain_experience' => ['FOREST', 'OPEN_SLOPE'],
            'navigation_experience' => NavigationExperience::COMPETENT->value,
        ]);
        $user->preference()->create([
            'preferred_duration' => PreferredDuration::TWO_DAY->value,
            'preferred_trip_type' => TripType::CAMPING->value,
        ]);

        $goal = HikingGoal::create([
            'user_id' => $user->id,
            'trip_type' => TripType::CAMPING->value,
            'target_date' => now()->addDays(14)->toDateString(),
        ]);

        $sumber = DataSource::factory()->create();
        $gunung = Mountain::factory()->count(5)->create();
        foreach ($gunung as $g) {
            PermitRequirement::factory()->create(['mountain_id' => $g->id]);
        }

        $this->tambahJalurLengkap(10, $gunung->all(), $sumber->id);

        $layanan = app(RouteFitService::class);
        $layanan->recommend($user, $goal); // pemanasan

        $kueriSepuluh = $this->hitungKueri(fn () => $layanan->recommend($user, $goal));

        $this->tambahJalurLengkap(290, $gunung->all(), $sumber->id);

        $mulai = hrtime(true);
        $run = null;
        $kueriTigaRatus = $this->hitungKueri(function () use ($layanan, $user, $goal, &$run): void {
            $run = $layanan->recommend($user, $goal);
        });
        $durasiMs = (hrtime(true) - $mulai) / 1_000_000;

        $this->assertSame(
            $kueriSepuluh,
            $kueriTigaRatus,
            "Kueri RouteFitService tumbuh dari {$kueriSepuluh} (10 jalur) ke {$kueriTigaRatus} (300 jalur)."
        );
        $this->assertCount(300, $run->results);
        $this->assertLessThan(self::BATAS_WAKTU_MS, $durasiMs, sprintf('Evaluasi 300 jalur memakan %.0f ms.', $durasiMs));
    }

    /**
     * @param  array<int, Mountain>  $daftarGunung
     */
    private function tambahJalurLengkap(int $jumlah, array $daftarGunung, int $sumberId): void
    {
        $awal = Trail::count();
        $sisa = $jumlah;

        while ($sisa > 0) {
            $potong = min(50, $sisa);
            fake()->unique(true);

            $baru = Trail::factory()
                ->count($potong)
                ->sequence(fn ($s) => [
                    'mountain_id' => $daftarGunung[($awal + $s->index) % count($daftarGunung)]->id,
                    'data_source_id' => $sumberId,
                    'name' => 'Jalur Beban '.($awal + $s->index),
                    'slug' => 'jalur-beban-'.($awal + $s->index),
                ])
                ->easy()
                ->published()
                ->create();

            $barisPos = [];
            $kini = now();
            foreach ($baru as $jalur) {
                $barisPos[] = [
                    'trail_id' => $jalur->id,
                    'name' => 'Basecamp',
                    'checkpoint_type' => 'BASECAMP',
                    'sequence' => 1,
                    'elevation_m' => 1400,
                    'created_at' => $kini,
                    'updated_at' => $kini,
                ];
            }
            Checkpoint::insert($barisPos);

            $awal += $potong;
            $sisa -= $potong;
        }
    }

    private function hitungKueri(callable $aksi): int
    {
        $jumlah = 0;
        DB::listen(function () use (&$jumlah): void {
            $jumlah++;
        });

        $aksi();

        DB::getEventDispatcher()->forget('Illuminate\Database\Events\QueryExecuted');

        return $jumlah;
    }
}
