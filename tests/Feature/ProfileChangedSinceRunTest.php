<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\NavigationExperience;
use App\Livewire\Recommendations\RecommendationResults;
use App\Models\HikingGoal;
use App\Models\Trail;
use App\Models\User;
use App\Services\RouteFitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Hasil rekomendasi ber-URL permanen, dan halaman onboarding merangkap penyunting
 * profil: ia mengisi dirinya dari profil yang sudah ada. Pendaki karena itu dapat
 * menaikkan tingkat pengalamannya lalu membuka kembali run kemarin.
 *
 * Yang ia baca di sana adalah label kecocokan yang dihitung terhadap profil yang sudah
 * tidak ada, di bawah kalimat yang menyebut "profil Anda" dalam bentuk kini. Pendaki
 * yang baru saja menaikkan dirinya dari pemula ke menengah membaca "KURANG COCOK" yang
 * dihitung untuk orang yang bukan dirinya lagi, dan tidak ada apa pun di halaman itu
 * yang memberitahunya.
 *
 * Run tidak dihitung ulang. Ia catatan tentang apa yang dinilai saat itu, dan
 * menghitungnya ulang di tempat akan menghapus catatan tersebut sekaligus membuat
 * tautan permanen berubah isi diam-diam. Yang ditambahkan hanya pengakuan dan jalan
 * keluarnya.
 */
class ProfileChangedSinceRunTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_it_admits_the_profile_changed_since_the_run(): void
    {
        [$user, $run] = $this->pendakiDenganRun();

        $user->profile->update(['experience_level' => ExperienceLevel::INTERMEDIATE->value]);

        Livewire::actingAs($user->fresh())
            ->test(RecommendationResults::class, ['run' => $run])
            ->assertSee('Profil Anda berubah');
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

        Livewire::actingAs($user->fresh())
            ->test(RecommendationResults::class, ['run' => $run])
            ->assertSee('Profil Anda berubah');
    }

    /**
     * Pengakuan tanpa jalan keluar hanya memindahkan kebuntuan ke pendaki. Yang ia
     * butuhkan adalah rencana baru, bukan penjelasan mengapa yang lama tidak berlaku.
     */
    public function test_it_offers_a_way_to_run_the_engine_again(): void
    {
        [$user, $run] = $this->pendakiDenganRun();

        $user->profile->update(['experience_level' => ExperienceLevel::ADVANCED->value]);

        Livewire::actingAs($user->fresh())
            ->test(RecommendationResults::class, ['run' => $run])
            ->assertSee(route('goals.create'));
    }

    /**
     * Penjaga: profil yang tidak disentuh tidak boleh memunculkan penanda apa pun,
     * karena penanda yang selalu menyala berubah menjadi hiasan yang diabaikan.
     */
    public function test_an_untouched_profile_says_nothing(): void
    {
        [$user, $run] = $this->pendakiDenganRun();

        Livewire::actingAs($user)
            ->test(RecommendationResults::class, ['run' => $run])
            ->assertDontSee('Profil Anda berubah');
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

        Livewire::actingAs($user->fresh())
            ->test(RecommendationResults::class, ['run' => $run])
            ->assertDontSee('Profil Anda berubah');
    }
}
