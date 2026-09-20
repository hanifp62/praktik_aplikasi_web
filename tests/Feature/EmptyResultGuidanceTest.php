<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\NavigationExperience;
use App\Livewire\Recommendations\RecommendationResults;
use App\Models\HikingGoal;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\User;
use App\Services\RouteFitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

    private function runFor(User $user, array $atribut)
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
        Trail::factory()->easy()->for(Mountain::factory()->create(['province' => 'Jawa Tengah']))->create();

        $run = $this->runFor($user, ['region' => 'Jawa Timur', 'trip_type' => 'CAMPING']);

        $page = Livewire::actingAs($user)->test(RecommendationResults::class, ['run' => $run]);

        $page->assertSee('Jawa Timur');
        $page->assertDontSee('melonggarkan');
    }

    /**
     * Ketika kandidatnya memang ada dan semuanya tersingkir oleh batasan rencana,
     * saran melonggarkan batas justru benar dan harus tetap muncul.
     */
    public function test_loosening_the_limits_is_still_suggested_when_candidates_were_actually_tested(): void
    {
        $user = $this->userWithProfile();
        Trail::factory()->easy()->create(['elevation_gain_m' => 2000]);

        $run = $this->runFor($user, [
            'region' => null,
            'trip_type' => 'CAMPING',
            'max_elevation_gain_m' => 100,
        ]);

        $page = Livewire::actingAs($user)->test(RecommendationResults::class, ['run' => $run]);

        $page->assertSee('melonggarkan');
    }

    /**
     * Sistem tanpa satu pun jalur terbit adalah keadaan awal yang sebenarnya, dan
     * penyebabnya bukan batasan pengguna sama sekali.
     */
    public function test_an_empty_catalogue_says_the_data_is_not_in_yet(): void
    {
        $user = $this->userWithProfile();

        $run = $this->runFor($user, ['region' => null, 'trip_type' => 'CAMPING']);

        $page = Livewire::actingAs($user)->test(RecommendationResults::class, ['run' => $run]);

        $page->assertSee('belum dimasukkan');
        $page->assertDontSee('melonggarkan');
    }
}
