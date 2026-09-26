<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\NavigationExperience;
use App\Models\HikingGoal;
use App\Models\RecommendationRun;
use App\Models\Trail;
use App\Models\User;
use App\Services\RouteFitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Hasil rekomendasi ber-URL permanen, dan halaman onboarding merangkap penyunting
 * profil: ia mengisi dirinya dari profil yang sudah ada. Pendaki karena itu dapat
 * menaikkan tingkat pengalamannya lalu membuka kembali run kemarin.
 *
 * Yang ia baca di sana adalah label kecocokan yang dihitung terhadap profil yang sudah
 * tidak ada, di bawah kalimat yang menyebut "profil Anda" dalam bentuk kini.
 *
 * Run tidak dihitung ulang. Ia catatan tentang apa yang dinilai saat itu, dan
 * menghitungnya ulang di tempat akan menghapus catatan tersebut sekaligus membuat
 * tautan permanen berubah isi diam-diam. Yang ditambahkan hanya pengakuan dan jalan
 * keluarnya.
 */
class ProfileChangedSinceRunTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: RecommendationRun}
     */
    private function pendakiDenganRun(): array
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::BEGINNER->value, 'completed_at' => now()]);
        $user->experience()->create([
            'completed_hikes_count' => 2,
            'terrain_experience' => ['FOREST'],
            'navigation_experience' => NavigationExperience::BASIC->value,
        ]);
        $user->preference()->create(['preferred_duration' => 'ONE_DAY', 'preferred_trip_type' => 'CAMPING']);
        $user = $user->fresh();

        Trail::factory()->easy()->create();
        $goal = HikingGoal::factory()->create(['user_id' => $user->id, 'trip_type' => 'CAMPING', 'region' => null]);

        return [$user, app(RouteFitService::class)->recommend($user, $goal)];
    }

    private function profilBerubah(User $user, RecommendationRun $run): bool
    {
        return $this->halaman($user, $run)->viewData('page')['props']['profilBerubah'];
    }

    private function halaman(User $user, RecommendationRun $run): TestResponse
    {
        return $this->actingAs($user->fresh())->get(route('recommendations.show', $run))->assertOk();
    }

    public function test_it_admits_the_profile_changed_since_the_run(): void
    {
        [$user, $run] = $this->pendakiDenganRun();

        $user->profile->update(['experience_level' => ExperienceLevel::INTERMEDIATE->value]);

        $this->assertTrue($this->profilBerubah($user, $run));
        $this->assertStringContainsString('Profil Anda berubah sejak penilaian ini dibuat', $this->sumber());
    }

    /**
     * Pengalaman lapangan disimpan terpisah dari tingkat pengalaman, dan keduanya
     * sama-sama masuk perhitungan. Menandai satu saja meninggalkan separuh perubahan
     * tanpa pengakuan.
     */
    public function test_a_change_in_field_experience_counts_too(): void
    {
        [$user, $run] = $this->pendakiDenganRun();

        $user->experience->update(['completed_hikes_count' => 20]);

        $this->assertTrue($this->profilBerubah($user, $run));
    }

    /**
     * Pengakuan tanpa jalan keluar hanya memindahkan kebuntuan ke pendaki. Yang ia
     * butuhkan adalah rencana baru, bukan penjelasan mengapa yang lama tidak berlaku.
     */
    public function test_it_offers_a_way_to_run_the_engine_again(): void
    {
        $sumber = $this->sumber();
        $pengakuan = substr($sumber, (int) strpos($sumber, '{profilBerubah ? ('));

        $this->assertMatchesRegularExpression('/^\{profilBerubah \? \(.*?<Tautan href=\{goalsCreate\.url\(\)\}/s', $pengakuan);
        $this->assertStringContainsString("import { create as goalsCreate } from '@/routes/goals';", $sumber);
    }

    /**
     * Penjaga: profil yang tidak disentuh tidak boleh memunculkan penanda apa pun,
     * karena penanda yang selalu menyala berubah menjadi hiasan yang diabaikan.
     */
    public function test_an_untouched_profile_says_nothing(): void
    {
        [$user, $run] = $this->pendakiDenganRun();

        $this->assertFalse($this->profilBerubah($user, $run));
    }

    /**
     * Penjaga kedua: menyunting profil lalu mengembalikannya ke nilai semula bukan
     * perubahan. Yang dibandingkan nilainya, bukan ada tidaknya penyuntingan.
     */
    public function test_editing_a_field_back_to_its_original_value_is_not_a_change(): void
    {
        [$user, $run] = $this->pendakiDenganRun();

        $user->profile->update(['experience_level' => ExperienceLevel::EXPERT->value]);
        $user->profile->update(['experience_level' => ExperienceLevel::BEGINNER->value]);

        $this->assertFalse($this->profilBerubah($user, $run));
    }

    private function sumber(): string
    {
        return File::get(resource_path('js/pages/Recommendations/Show.tsx'));
    }
}
