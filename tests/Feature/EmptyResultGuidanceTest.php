<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\NavigationExperience;
use App\Models\HikingGoal;
use App\Models\Mountain;
use App\Models\RecommendationRun;
use App\Models\Trail;
use App\Models\User;
use App\Services\RouteFitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Layar tanpa hasil adalah keadaan yang dijamin terjadi pada sistem ini, bukan kasus
 * pinggiran: sebagian besar jalur yang dikenali memang belum berdata, dan penyaring
 * wilayah bekerja di tingkat query sehingga jalur yang tersaring tidak pernah muncul
 * sebagai kandidat maupun sebagai jalur tersingkir.
 *
 * Saran yang salah pada layar kosong lebih buruk daripada tidak ada saran: pendaki
 * mengubah hal yang tidak mungkin menolongnya, gagal lagi, lalu menyimpulkan
 * sistemnya rusak.
 *
 * Server menentukan sebabnya (`penyebabKosong`); halaman React memilih kalimatnya dari
 * sebab itu, jadi keduanya diuji: sebabnya lewat HTTP, kalimatnya dari sumber halaman.
 */
class EmptyResultGuidanceTest extends TestCase
{
    use RefreshDatabase;

    private function userWithProfile(): User
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
     * @param  array<string, mixed>  $atribut
     */
    private function runFor(User $user, array $atribut): RecommendationRun
    {
        $goal = HikingGoal::factory()->create(['user_id' => $user->id] + $atribut);

        return app(RouteFitService::class)->recommend($user, $goal);
    }

    /**
     * Wilayah yang seluruh jalurnya belum berdata menghasilkan nol kandidat. Pesan
     * lama menyuruh melonggarkan durasi dan batas elevation gain, padahal tidak satu
     * pun jalur pernah diuji terhadap dua batas itu.
     */
    public function test_a_region_with_no_published_trail_is_named_as_the_reason(): void
    {
        $user = $this->userWithProfile();
        Trail::factory()->published()->easy()->for(Mountain::factory()->create(['province' => 'Jawa Tengah']))->create();

        $run = $this->runFor($user, ['region' => 'Jawa Timur', 'trip_type' => 'CAMPING']);

        $this->actingAs($user)->get(route('recommendations.show', $run))->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('penyebabKosong', 'wilayah')
                ->where('wilayah', 'Jawa Timur')
                ->has('eligible', 0));

        $this->assertStringNotContainsString('melonggarkan', $this->kalimatSebab("penyebabKosong === 'wilayah'"));
        $this->assertStringContainsString('{wilayah}', $this->kalimatSebab("penyebabKosong === 'wilayah'"));
    }

    /**
     * Ketika kandidatnya memang ada dan semuanya tersingkir oleh batasan rencana,
     * saran melonggarkan batas justru benar dan harus tetap muncul.
     */
    public function test_loosening_the_limits_is_still_suggested_when_candidates_were_actually_tested(): void
    {
        $user = $this->userWithProfile();
        Trail::factory()->published()->easy()->create(['elevation_gain_m' => 2000]);

        $run = $this->runFor($user, [
            'region' => null,
            'trip_type' => 'CAMPING',
            'max_elevation_gain_m' => 100,
        ]);

        // Hasilnya tidak kosong (jalurnya tersingkir, bukan tidak pernah diuji), jadi
        // halaman jatuh ke cabang terakhir: saran melonggarkan batas.
        $this->actingAs($user)->get(route('recommendations.show', $run))->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('penyebabKosong', null)
                ->has('eligible', 0)
                ->has('excluded', 1));

        $sumber = File::get(resource_path('js/pages/Recommendations/Show.tsx'));
        $cabangTerakhir = substr($sumber, (int) strpos($sumber, ') : (', (int) strpos($sumber, "penyebabKosong === 'wilayah'")));

        $this->assertStringContainsString('melonggarkan', $cabangTerakhir);
    }

    /**
     * Sistem tanpa satu pun jalur terbit adalah keadaan awal yang sebenarnya, dan
     * penyebabnya bukan batasan pengguna sama sekali.
     */
    public function test_an_empty_catalogue_says_the_data_is_not_in_yet(): void
    {
        $user = $this->userWithProfile();

        $run = $this->runFor($user, ['region' => null, 'trip_type' => 'CAMPING']);

        $this->actingAs($user)->get(route('recommendations.show', $run))->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('penyebabKosong', 'katalog'));

        $kalimat = $this->kalimatSebab("penyebabKosong === 'katalog'");

        $this->assertStringContainsString('belum dimasukkan', $kalimat);
        $this->assertStringNotContainsString('melonggarkan', $kalimat);
    }

    /**
     * Kalimat pada cabang sebab tertentu di halaman React: dari penanda cabangnya
     * sampai cabang berikutnya.
     */
    private function kalimatSebab(string $penanda): string
    {
        $sumber = File::get(resource_path('js/pages/Recommendations/Show.tsx'));
        $mulai = strpos($sumber, $penanda);
        $this->assertNotFalse($mulai, "Cabang {$penanda} tidak ada di halaman hasil.");

        $akhir = strpos($sumber, ') : ', $mulai);

        return substr($sumber, $mulai, $akhir - $mulai);
    }
}
