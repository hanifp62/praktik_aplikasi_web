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
 * Karakteristik jalur yang berubah setelah penilaian dibuat.
 *
 * Kartu hasil dahulu membaca jarak, elevation gain, dan durasi langsung dari tabel
 * jalur, yaitu nilai sekarang, sementara skor dan labelnya historis. Keduanya duduk
 * dalam satu kotak tanpa apa pun yang membedakan.
 *
 * Itu lebih menyesatkan daripada kalau dua-duanya basi: pendaki membaca angka yang benar
 * hari ini dan menyimpulkan bahwa label di sebelahnya dihitung dari angka itu.
 *
 * Sejak pintu kontribusi ahli dibuka, keadaan ini berhenti menjadi kasus pinggiran.
 * Sebelumnya karakteristik jalur hanya berubah kalau admin mengoreksi; sekarang ia
 * berubah setiap kali seorang pemandu bersertifikat menyumbang datanya.
 */
class TrailChangedSinceRunTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: RecommendationRun, 2: Trail}
     */
    private function runSatuJalur(): array
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

        $trail = Trail::factory()->published()->easy()->create([
            'distance_km' => 8.50,
            'elevation_gain_m' => 900,
        ]);

        $goal = HikingGoal::factory()->create(['user_id' => $user->id, 'trip_type' => 'CAMPING', 'region' => null]);

        return [$user, app(RouteFitService::class)->recommend($user, $goal), $trail];
    }

    public function test_the_run_records_the_trail_as_it_was(): void
    {
        [, , $trail] = $this->runSatuJalur();

        $snapshot = RecommendationResult::firstOrFail()->trail_snapshot;

        $this->assertSame(900, $snapshot['elevation_gain_m']);
        $this->assertEqualsWithDelta(8.5, (float) $snapshot['distance_km'], 0.001);
        $this->assertSame($trail->technical_demand->value, $snapshot['technical_demand']);
    }

    /**
     * Angka yang ditampilkan harus yang dipakai menilai, supaya kartunya utuh dengan
     * dirinya sendiri.
     */
    public function test_the_card_shows_the_figures_the_label_was_computed_from(): void
    {
        [$user, $run, $trail] = $this->runSatuJalur();

        $trail->update(['elevation_gain_m' => 1800]);

        $page = Livewire::actingAs($user)->test(RecommendationResults::class, ['run' => $run]);

        $page->assertSee('900');
        $page->assertDontSee('1800');
    }

    public function test_it_says_the_trail_data_changed_since(): void
    {
        [$user, $run, $trail] = $this->runSatuJalur();

        $trail->update(['elevation_gain_m' => 1800]);

        Livewire::actingAs($user)
            ->test(RecommendationResults::class, ['run' => $run])
            ->assertSee('Data jalur ini berubah');
    }

    /**
     * Perubahan pada medan yang ikut dinilai tetapi tidak ditampilkan juga membatalkan
     * labelnya, meskipun tidak ada angka di kartu yang terlihat bertentangan.
     */
    public function test_a_change_the_card_never_shows_still_counts(): void
    {
        [$user, $run, $trail] = $this->runSatuJalur();

        $trail->update(['camping_available' => ! $trail->camping_available]);

        Livewire::actingAs($user)
            ->test(RecommendationResults::class, ['run' => $run])
            ->assertSee('Data jalur ini berubah');
    }

    public function test_an_untouched_trail_says_nothing(): void
    {
        [$user, $run] = $this->runSatuJalur();

        Livewire::actingAs($user)
            ->test(RecommendationResults::class, ['run' => $run])
            ->assertDontSee('Data jalur ini berubah');
    }

    /**
     * Baris yang ditulis sebelum kolom ini ada tidak dapat direkayasa ulang secara
     * jujur. Ia jatuh kembali ke nilai sekarang seperti perilaku sebelumnya, dan tidak
     * mengaku tahu yang tidak diketahuinya.
     */
    public function test_a_row_from_before_the_column_existed_still_renders(): void
    {
        [$user, $run, $trail] = $this->runSatuJalur();

        RecommendationResult::query()->update(['trail_snapshot' => null]);
        $trail->update(['elevation_gain_m' => 1800]);

        Livewire::actingAs($user)
            ->test(RecommendationResults::class, ['run' => $run->fresh()])
            ->assertSee('1800')
            ->assertDontSee('Data jalur ini berubah');
    }
}
