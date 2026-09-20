<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\NavigationExperience;
use App\Livewire\Recommendations\RecommendationResults;
use App\Models\HikingGoal;
use App\Models\RecommendationResult;
use App\Models\RecommendationRun;
use App\Models\Trail;
use App\Models\User;
use App\Services\RouteFitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * BR-09 pada halaman yang sungguhan, dengan panel penjelasannya terbuka.
 *
 * Uji komponen terisolasi tidak cukup di sini. Batang faktor membawa matched_factors ke
 * dalam halaman, dan kebocoran skor dapat terjadi lewat muatan Livewire tanpa satu pun
 * angka tampak di teks yang terbaca mata.
 */
class FactorBarsDoNotLeakScoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: RecommendationRun}
     */
    private function runDenganHasil(): array
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::INTERMEDIATE->value, 'completed_at' => now()]);
        $user->experience()->create([
            'completed_hikes_count' => 8,
            'terrain_experience' => ['FOREST'],
            'navigation_experience' => NavigationExperience::COMPETENT->value,
        ]);
        $user->preference()->create(['preferred_duration' => 'ONE_DAY', 'preferred_trip_type' => 'CAMPING']);
        $user = $user->fresh();

        // Elevation gain jauh di atas pengalaman yang diisi memaksa mesin menghasilkan
        // skor berkoma. Tanpa itu semua faktor bernilai 0 atau 1, seluruh perulangan
        // pemeriksaan dilewati, dan testnya lolos tanpa memeriksa apa pun.
        Trail::factory()->easy()->create(['elevation_gain_m' => 1600]);
        $goal = HikingGoal::factory()->create(['user_id' => $user->id, 'trip_type' => 'CAMPING', 'region' => null]);

        return [$user, app(RouteFitService::class)->recommend($user, $goal)];
    }

    public function test_opening_the_panel_shows_the_bars(): void
    {
        [$user, $run] = $this->runDenganHasil();
        $hasil = RecommendationResult::firstOrFail();

        Livewire::actingAs($user)
            ->test(RecommendationResults::class, ['run' => $run])
            ->call('toggleExplanation', $hasil->id)
            ->assertSee('Penilaian per faktor');
    }

    /**
     * Skor tiap faktor tersimpan dengan empat angka di belakang koma. Kalau satu pun
     * dari angka itu muncul di halaman, skor keseluruhan dapat disusun ulang dari luar.
     */
    public function test_no_stored_factor_score_appears_anywhere_on_the_page(): void
    {
        [$user, $run] = $this->runDenganHasil();
        $hasil = RecommendationResult::firstOrFail();

        $html = Livewire::actingAs($user)
            ->test(RecommendationResults::class, ['run' => $run])
            ->call('toggleExplanation', $hasil->id)
            ->html();

        $diperiksa = 0;

        foreach ($hasil->matched_factors as $faktor) {
            $skor = (string) $faktor['score'];

            // Skor bulat seperti 0 dan 1 dilewati: angka itu muncul di mana-mana pada
            // halaman mana pun dan tidak membocorkan apa-apa.
            if (in_array($skor, ['0', '1', '0.0', '1.0'], true)) {
                continue;
            }

            $diperiksa++;

            $this->assertStringNotContainsString(
                $skor,
                $html,
                "Skor faktor {$faktor['factor']} ({$skor}) bocor ke halaman."
            );
        }

        // Penjaga terhadap test yang lolos tanpa memeriksa apa pun. Kalau seluruh skor
        // kebetulan bulat, perulangan di atas dilewati seluruhnya dan testnya hijau
        // tanpa pernah menyentuh halaman.
        $this->assertGreaterThan(
            0,
            $diperiksa,
            'Tidak ada satu pun skor berkoma untuk diperiksa, jadi test ini belum membuktikan apa-apa.'
        );
    }

    /**
     * Hasil yang skornya bulat sengaja dilewati.
     *
     * Skor teratas hampir selalu 100, dan "100" muncul di HALAMAN mana pun secara
     * kebetulan, misalnya di dalam "1000 m" atau nama kelas. Versi pertama test ini
     * memeriksanya apa adanya lalu menyala merah untuk kebocoran yang tidak pernah ada.
     * Yang dicari adalah skor berkoma yang tidak mungkin muncul karena kebetulan.
     */
    public function test_the_internal_score_and_weights_stay_out_of_the_page(): void
    {
        [$user, $run] = $this->runDenganHasil();

        $hasil = RecommendationResult::query()
            ->whereRaw('internal_score <> round(internal_score)')
            ->first() ?? RecommendationResult::firstOrFail();

        $html = Livewire::actingAs($user)
            ->test(RecommendationResults::class, ['run' => $run])
            ->call('toggleExplanation', $hasil->id)
            ->html();

        $this->assertStringNotContainsString('internal_score', $html);

        if (fmod((float) $hasil->internal_score, 1.0) !== 0.0) {
            $this->assertStringNotContainsString((string) $hasil->internal_score, $html);
        }
    }
}
