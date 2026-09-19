<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Models\HikingGoal;
use App\Models\Mountain;
use App\Models\RecommendationResult;
use App\Models\Trail;
use App\Models\User;
use App\Services\RouteFitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * F-20.
 *
 * Diukur sebelum perbaikan: 58 query untuk 12 jalur. Penyebabnya bobot faktor
 * diambil ulang dari basis data pada setiap evaluate(), status resmi jalur dan
 * gunungnya masing-masing satu query per jalur, lalu hasilnya ditulis baris per baris.
 *
 * Terhadap basis data jarak jauh dengan RTT sekitar 30 ms, 58 query saja sudah
 * melewati target p95 satu detik pada PRD §96: dan jumlahnya tumbuh linear terhadap
 * jumlah jalur. Anggaran query di sini menahan agar regresinya tidak kembali diam-diam.
 */
class RecommendationQueryBudgetTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Lantai sebenarnya adalah 15 query konstan: memuat pengguna beserta tiga relasi
     * profilnya, kandidat beserta gunungnya, bobot, status resmi jalur dan gunung,
     * segmen, dua insert, lalu memuat ulang hasilnya untuk pemanggil. Batas ini diberi
     * sedikit ruang; yang benar-benar dijaga adalah sifat konstannya pada test berikut.
     */
    private const BUDGET = 20;

    public function test_a_recommendation_run_stays_within_the_query_budget(): void
    {
        [$user, $goal] = $this->hikerWithGoal();
        Trail::factory()->count(12)->create();

        $queries = $this->countQueries(fn () => app(RouteFitService::class)->recommend($user, $goal));

        $this->assertLessThan(
            self::BUDGET,
            $queries,
            "Anggaran query terlampaui: {$queries} query."
        );
    }

    public function test_the_query_count_does_not_grow_with_the_number_of_trails(): void
    {
        [$user, $goal] = $this->hikerWithGoal();
        $service = app(RouteFitService::class);

        Trail::factory()->count(3)->create();

        // Run pemanasan agar relasi pengguna sudah termuat pada kedua pengukuran,
        // sehingga yang dibandingkan benar-benar hanya pengaruh jumlah jalur.
        $service->recommend($user, $goal);
        $small = $this->countQueries(fn () => $service->recommend($user, $goal));

        Trail::factory()->count(30)->create();
        $large = $this->countQueries(fn () => $service->recommend($user, $goal));

        $this->assertSame(
            $small,
            $large,
            "Jumlah query harus tetap: {$small} untuk 3 jalur, {$large} untuk 33 jalur."
        );
    }

    public function test_every_candidate_still_produces_a_stored_result(): void
    {
        [$user, $goal] = $this->hikerWithGoal();
        Trail::factory()->count(12)->create();

        $run = app(RouteFitService::class)->recommend($user, $goal);

        $this->assertSame(12, RecommendationResult::where('recommendation_run_id', $run->id)->count());
    }

    public function test_results_keep_their_ranking_and_audit_fields(): void
    {
        [$user, $goal] = $this->hikerWithGoal();
        Trail::factory()->count(5)->create();

        $run = app(RouteFitService::class)->recommend($user, $goal);

        $results = RecommendationResult::where('recommendation_run_id', $run->id)
            ->orderBy('rank')
            ->get();

        $this->assertSame([1, 2, 3, 4, 5], $results->pluck('rank')->all());
        $this->assertNotEmpty($results->first()->matched_factors);
        $this->assertNotNull($results->first()->explanation);
        $this->assertNotNull($results->first()->created_at, 'Bulk insert tetap wajib mengisi timestamp.');
    }

    private function countQueries(callable $callback): int
    {
        $count = 0;
        DB::listen(function () use (&$count) {
            $count++;
        });

        $callback();

        DB::getEventDispatcher()->forget('Illuminate\Database\Events\QueryExecuted');

        return $count;
    }

    /**
     * @return array{0: User, 1: HikingGoal}
     */
    private function hikerWithGoal(): array
    {
        Mountain::factory()->create();

        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::INTERMEDIATE->value, 'completed_at' => now()]);

        $goal = HikingGoal::factory()->create(['user_id' => $user->id]);

        return [$user->fresh(), $goal];
    }
}
