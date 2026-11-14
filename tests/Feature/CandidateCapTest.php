<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\NavigationExperience;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Jelajah dan Beranda pada batas kandidatnya (titipan W3 dan W5-5b M5 untuk W9).
 *
 * Keduanya menilai kecocokan di memori untuk paling banyak 500 jalur per permintaan
 * (hiking.jelajah.batas_kandidat dan hiking.beranda.batas_kandidat). Kuerinya tetap; yang tumbuh
 * bersama katalog adalah ukuran halaman dan waktu hitung, jadi keduanya dijaga di batas itu,
 * bukan di 40 jalur seperti ResultListBudgetTest. Diukur di simulasi W9 (SQLite memori): Jelajah
 * 78 kB dan sekitar 290 ms untuk 500 jalur, Beranda sekitar 170 ms; 520 jalur sama dengan 500.
 */
class CandidateCapTest extends TestCase
{
    use RefreshDatabase;

    /** Jelajah dengan 500 jalur berprofil rapat di simulasi W9: 78 kB. */
    private const ANGGARAN_JELAJAH_DI_BATAS_KB = 100;

    private function pendakiDinilai(): User
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::INTERMEDIATE->value, 'completed_at' => now()]);
        $user->experience()->create([
            'completed_hikes_count' => 8,
            'terrain_experience' => ['FOREST'],
            'navigation_experience' => NavigationExperience::COMPETENT->value,
        ]);
        $user->preference()->create(['preferred_duration' => 'ONE_DAY', 'preferred_trip_type' => 'CAMPING']);

        return $user->fresh();
    }

    /**
     * Jalur terbit berprofil rapat sampai jumlahnya $jumlah. Pabrik jalur memakai nama belakang
     * unik dari Faker yang habis sebelum ratusan jalur, jadi ingatan uniknya dikosongkan per 40
     * jalur, dan nama serta slug ditulis berurutan.
     */
    private function terbitkanSampai(int $jumlah, Mountain $gunung): void
    {
        $profil = array_map(fn (int $i) => ['km' => $i / 20, 'm' => 1200 + (int) round(600 * sin($i / 40))], range(0, 199));

        while (($ada = Trail::count()) < $jumlah) {
            fake()->unique(true);
            Trail::factory()->count(min(40, $jumlah - $ada))
                ->sequence(fn ($urutan) => ['name' => 'Jalur Batas '.($ada + $urutan->index), 'slug' => 'jalur-batas-'.($ada + $urutan->index)])
                ->for($gunung)->easy()->published()->create(['elevation_profile' => $profil]);
        }
    }

    public function test_jelajah_at_the_candidate_cap_stays_within_its_size_budget(): void
    {
        $this->terbitkanSampai(500, Mountain::factory()->create());

        $respons = $this->actingAs($this->pendakiDinilai())->get(route('trails.index'))->assertOk();
        $kb = strlen((string) $respons->getContent()) / 1024;

        $respons->assertInertia(fn (AssertableInertia $page) => $page->has('atribut', 500)->where('ringkasan.terpotong', null));
        $this->assertLessThan(
            self::ANGGARAN_JELAJAH_DI_BATAS_KB,
            $kb,
            sprintf('Jelajah %.0f kB untuk 500 jalur, melewati anggaran %d kB.', $kb, self::ANGGARAN_JELAJAH_DI_BATAS_KB)
        );
    }

    public function test_jelajah_past_the_cap_says_so_and_sends_no_more_candidates(): void
    {
        $this->terbitkanSampai(520, Mountain::factory()->create());

        $this->actingAs($this->pendakiDinilai())->get(route('trails.index'))->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('atribut', 500)
                ->where('ringkasan.terpotong', 500)
                ->where('jumlahTerbit', 520));
    }

    public function test_the_home_scores_no_more_trails_than_its_candidate_cap(): void
    {
        $user = $this->pendakiDinilai();
        $this->terbitkanSampai(3, Mountain::factory()->create());
        $tiga = Trail::query()->orderBy('id')->pluck('id')->all();
        $ditawarkan = fn () => collect($this->actingAs($user)->get(route('dashboard'))->assertOk()->viewData('page')['props']['jalurLain']['jalur'])->pluck('id')->sort()->values()->all();

        $this->assertSame($tiga, $ditawarkan(), 'Tanpa batas yang menyempit, ketiga jalur yang cocok ditawarkan.');

        config(['hiking.beranda.batas_kandidat' => 2]);

        $this->assertSame(array_slice($tiga, 0, 2), $ditawarkan());
    }
}
