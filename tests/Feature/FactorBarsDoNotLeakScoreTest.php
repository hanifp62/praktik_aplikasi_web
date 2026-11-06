<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\NavigationExperience;
use App\Models\HikingGoal;
use App\Models\RecommendationResult;
use App\Models\RecommendationRun;
use App\Models\Trail;
use App\Models\User;
use App\Services\RouteFitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * BR-09 pada halaman yang sungguhan.
 *
 * Halaman hasil kini React: faktor dan penjelasan setiap jalur dikirim di props supaya
 * "Kenapa?" terbuka tanpa menunggu server. Karena itu pintu kebocorannya adalah props
 * itu sendiri, terlihat atau tidak di layar. Yang boleh keluar hanya kategori faktor
 * (kuat/cukup/lemah/tak-diketahui); skor, bobot, dan skor internal berhenti di server.
 */
class FactorBarsDoNotLeakScoreTest extends TestCase
{
    use RefreshDatabase;

    /** Kunci yang tidak boleh ada di mana pun dalam props halaman hasil. */
    private const KUNCI_TERLARANG = ['score', 'internal_score', 'internalScore', 'weight', 'weights'];

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
        Trail::factory()->published()->easy()->create(['elevation_gain_m' => 1600]);
        $goal = HikingGoal::factory()->create(['user_id' => $user->id, 'trip_type' => 'CAMPING', 'region' => null]);

        return [$user, app(RouteFitService::class)->recommend($user, $goal)];
    }

    private function halaman(User $user, RecommendationRun $run): TestResponse
    {
        return $this->actingAs($user)->get(route('recommendations.show', $run))->assertOk();
    }

    /**
     * Panel "Kenapa?" membawa batang faktor, dan batangnya hanya tahu kategori.
     */
    public function test_the_page_ships_the_factor_categories_for_the_panel(): void
    {
        [$user, $run] = $this->runDenganHasil();
        $jumlahFaktor = count(RecommendationResult::firstOrFail()->matched_factors);

        $this->halaman($user, $run)->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Recommendations/Show')
            ->has('eligible.0.faktor', $jumlahFaktor, fn (AssertableInertia $faktor) => $faktor
                ->whereType('label', 'string')
                ->where('kategori', fn (string $k) => in_array($k, ['kuat', 'cukup', 'lemah', 'tak-diketahui'], true))
                ->etc()));

        $this->assertStringContainsString('Penilaian per faktor', File::get(resource_path('js/components/discovery/PanelAlasan.tsx')));
    }

    public function test_no_score_or_weight_key_reaches_the_props(): void
    {
        [$user, $run] = $this->runDenganHasil();

        $props = $this->halaman($user, $run)->viewData('page')['props'];

        $bocor = array_values(array_intersect(self::KUNCI_TERLARANG, $this->semuaKunci($props)));

        $this->assertSame([], $bocor, 'Kunci skor bocor ke props: '.implode(', ', $bocor));
        $this->assertStringNotContainsString('"score"', (string) json_encode($props));
    }

    /**
     * Skor tiap faktor tersimpan dengan empat angka di belakang koma. Kalau satu pun
     * dari angka itu muncul di halaman, skor keseluruhan dapat disusun ulang dari luar.
     */
    public function test_no_stored_factor_score_appears_anywhere_on_the_page(): void
    {
        [$user, $run] = $this->runDenganHasil();
        $hasil = RecommendationResult::firstOrFail();

        $isi = $this->halaman($user, $run)->getContent();

        $diperiksa = 0;

        foreach ($hasil->matched_factors as $faktor) {
            $skor = (string) $faktor['score'];

            // Skor bulat seperti 0 dan 1 dilewati: angka itu muncul di mana-mana pada
            // halaman mana pun dan tidak membocorkan apa-apa.
            if (in_array($skor, ['0', '1', '0.0', '1.0'], true)) {
                continue;
            }

            $diperiksa++;

            $this->assertFalse($this->angkaMuncul($skor, $isi), "Skor faktor {$faktor['factor']} ({$skor}) bocor ke halaman.");
        }

        // Penjaga terhadap test yang lolos tanpa memeriksa apa pun.
        $this->assertGreaterThan(0, $diperiksa, 'Tidak ada satu pun skor berkoma untuk diperiksa, jadi test ini belum membuktikan apa-apa.');
    }

    /**
     * Skor teratas hampir selalu 100, dan "100" muncul di halaman mana pun secara
     * kebetulan. Yang dicari adalah skor berkoma yang tidak mungkin muncul karena
     * kebetulan.
     */
    public function test_the_internal_score_stays_out_of_the_page(): void
    {
        [$user, $run] = $this->runDenganHasil();

        $hasil = RecommendationResult::query()
            ->whereRaw('internal_score <> round(internal_score)')
            ->first() ?? RecommendationResult::firstOrFail();

        $isi = $this->halaman($user, $run)->getContent();

        $this->assertStringNotContainsString('internal_score', $isi);
        $this->assertStringNotContainsString('internalScore', $isi);

        if (fmod((float) $hasil->internal_score, 1.0) !== 0.0) {
            $this->assertFalse($this->angkaMuncul((string) $hasil->internal_score, $isi), 'Skor internal bocor ke halaman.');
        }
    }

    /**
     * Apakah angka itu muncul di halaman sebagai angka yang berdiri sendiri, juga dengan nol di
     * belakangnya ("0.4" atau "0.40"). Pencocokan substring mentah menemukan "0.4" di dalam jam
     * bertitik "00.40 WIB", sehingga test gagal hanya pada menit tertentu.
     */
    private function angkaMuncul(string $angka, string $isi): bool
    {
        return preg_match('/(?<![\d.])'.preg_quote($angka, '/').'0*(?!\d)/', $isi) === 1;
    }

    /**
     * @param  array<mixed>  $data
     * @return array<int, string>
     */
    private function semuaKunci(array $data): array
    {
        $kunci = [];

        foreach ($data as $k => $v) {
            if (is_string($k)) {
                $kunci[] = $k;
            }

            if (is_array($v)) {
                $kunci = [...$kunci, ...$this->semuaKunci($v)];
            }
        }

        return $kunci;
    }
}
