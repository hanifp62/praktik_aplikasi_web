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
 * Hasil rekomendasi punya URL permanen, jadi run lama dapat dibuka kembali kapan saja.
 *
 * Jalur yang diarsipkan sesudah run itu dibuat tetap tampil di sana lengkap dengan
 * tautan ke halamannya, padahal halaman itu sekarang menjawab 404, dan lengkap dengan
 * tombol "Pilih jalur ini" yang akan ditolak validasi pembuatan trip.
 *
 * Barisnya sendiri tidak dihapus: run adalah catatan tentang apa yang dinilai pada saat
 * itu, dan menghapus barisnya akan memalsukan catatan tersebut. Yang dicabut hanya dua
 * ajakan bertindak yang sudah tidak dapat ditindaklanjuti.
 */
class ArchivedTrailInOldRunTest extends TestCase
{
    use RefreshDatabase;

    private function runDenganSatuJalur(): array
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

        $trail = Trail::factory()->published()->easy()->create();
        $goal = HikingGoal::factory()->create(['user_id' => $user->id, 'trip_type' => 'CAMPING', 'region' => null]);

        return [$user, app(RouteFitService::class)->recommend($user, $goal), $trail];
    }

    public function test_a_withdrawn_trail_is_no_longer_linked_to_a_page_that_returns_404(): void
    {
        [$user, $run, $trail] = $this->runDenganSatuJalur();
        $trail->update(['archived_at' => now()]);

        $html = Livewire::actingAs($user)->test(RecommendationResults::class, ['run' => $run])->html();

        $this->assertStringNotContainsString(
            route('trails.show', $trail),
            $html,
            'Halaman jalur terarsip menjawab 404, jadi tautannya tidak boleh tersisa.'
        );
    }

    public function test_a_withdrawn_trail_cannot_still_be_chosen(): void
    {
        [$user, $run, $trail] = $this->runDenganSatuJalur();
        $trail->update(['archived_at' => now()]);

        $page = Livewire::actingAs($user)->test(RecommendationResults::class, ['run' => $run]);

        $page->assertDontSee('Pilih jalur ini');
        $page->assertSee('ditarik dari katalog');
    }

    /**
     * Barisnya tetap ada. Run adalah catatan tentang apa yang dinilai saat itu, dan
     * menghilangkan jalurnya membuat catatan itu berbohong tentang dirinya sendiri.
     */
    public function test_the_row_itself_is_not_erased_from_the_record(): void
    {
        [$user, $run, $trail] = $this->runDenganSatuJalur();
        $trail->update(['archived_at' => now()]);

        Livewire::actingAs($user)
            ->test(RecommendationResults::class, ['run' => $run])
            ->assertSee($trail->name);
    }

    /**
     * Penjaga: jalur yang masih sehat tetap tertaut dan tetap dapat dipilih.
     */
    public function test_a_healthy_trail_keeps_its_link_and_its_button(): void
    {
        [$user, $run, $trail] = $this->runDenganSatuJalur();

        $html = Livewire::actingAs($user)->test(RecommendationResults::class, ['run' => $run])->html();

        $this->assertStringContainsString(route('trails.show', $trail), $html);
        $this->assertStringContainsString('Pilih jalur ini', $html);
    }

    /**
     * Pertahanan lapis kedua: tombolnya hilang dari tampilan, tetapi aksinya tetap
     * dapat dipanggil langsung oleh klien yang sudah memuat halaman sebelum jalurnya
     * diarsipkan.
     */
    public function test_choosing_it_directly_is_refused_by_the_action(): void
    {
        [$user, $run, $trail] = $this->runDenganSatuJalur();
        $trail->update(['archived_at' => now()]);

        Livewire::actingAs($user)
            ->test(RecommendationResults::class, ['run' => $run])
            ->call('selectTrail', $trail->id)
            ->assertNoRedirect();
    }
}
