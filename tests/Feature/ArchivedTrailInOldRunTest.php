<?php

namespace Tests\Feature;

use App\Enums\AnalyticsEvent;
use App\Enums\ExperienceLevel;
use App\Enums\NavigationExperience;
use App\Models\AnalyticsEvent as AnalyticsEventModel;
use App\Models\HikingGoal;
use App\Models\RecommendationRun;
use App\Models\Trail;
use App\Models\User;
use App\Services\RouteFitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
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

    /**
     * @return array{0: User, 1: RecommendationRun, 2: Trail}
     */
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

        $respons = $this->actingAs($user)->get(route('recommendations.show', $run))->assertOk();

        $respons->assertInertia(fn (AssertableInertia $page) => $page
            ->where('eligible.0.url', null)
            ->where('eligible.0.ditarik', true));

        $this->assertNotContains(
            route('trails.show', $trail),
            Arr::flatten($respons->viewData('page')['props']),
            'Halaman jalur terarsip menjawab 404, jadi tautannya tidak boleh tersisa di props mana pun.'
        );
    }

    /**
     * Kartu tanpa URL tidak bertaut, dan tombol "Pilih jalur ini" hanya ada untuk jalur
     * yang belum ditarik; keduanya diputuskan halaman dari props di atas.
     */
    public function test_a_withdrawn_trail_cannot_still_be_chosen(): void
    {
        $kartu = File::get(resource_path('js/components/discovery/KartuHasil.tsx'));

        $this->assertMatchesRegularExpression('/\{!hasil\.ditarik \? \(\s*<Button[^>]*>Pilih jalur ini<\/Button>/s', $kartu);
        $this->assertStringContainsString('Jalur ini ditarik dari katalog setelah penilaian ini dibuat.', $kartu);
        $this->assertMatchesRegularExpression('/\{hasil\.url \? \(\s*<Tautan href=\{hasil\.url\}/s', $kartu);
    }

    /**
     * Barisnya tetap ada. Run adalah catatan tentang apa yang dinilai saat itu, dan
     * menghilangkan jalurnya membuat catatan itu berbohong tentang dirinya sendiri.
     */
    public function test_the_row_itself_is_not_erased_from_the_record(): void
    {
        [$user, $run, $trail] = $this->runDenganSatuJalur();
        $trail->update(['archived_at' => now()]);

        $this->actingAs($user)->get(route('recommendations.show', $run))->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('eligible', 1)
                ->where('eligible.0.name', $trail->name));
    }

    /**
     * Penjaga: jalur yang masih sehat tetap tertaut dan tetap dapat dipilih, dan
     * memilihnya mengantar ke pembuatan trip dengan rencana yang sama.
     */
    public function test_a_healthy_trail_keeps_its_link_and_can_be_chosen(): void
    {
        [$user, $run, $trail] = $this->runDenganSatuJalur();

        $this->actingAs($user)->get(route('recommendations.show', $run))->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('eligible.0.url', route('trails.show', $trail))
                ->where('eligible.0.ditarik', false));

        $this->actingAs($user)
            ->post(route('recommendations.pilih', $run), ['trail' => $trail->id])
            ->assertRedirect(route('trips.create', ['trail' => $trail->id, 'goal' => $run->hiking_goal_id]));

        $this->assertDatabaseHas('analytics_events', [
            'user_id' => $user->id,
            'event_name' => AnalyticsEvent::ROUTE_SELECTED->value,
        ]);
    }

    /**
     * Formulir trip kini halaman React: memilih jalur adalah pengalihan Inertia biasa
     * ke formulir itu, dengan jalur dan rencananya terbawa.
     */
    public function test_choosing_a_trail_leads_to_the_trip_form_with_trail_and_goal(): void
    {
        [$user, $run, $trail] = $this->runDenganSatuJalur();

        $this->actingAs($user)
            ->post(route('recommendations.pilih', $run), ['trail' => $trail->id], ['X-Inertia' => 'true'])
            ->assertRedirect(route('trips.create', ['trail' => $trail->id, 'goal' => $run->hiking_goal_id]));
    }

    /**
     * Pertahanan lapis kedua: tombolnya hilang dari tampilan, tetapi aksinya tetap
     * dapat dikirim langsung oleh klien yang sudah memuat halaman sebelum jalurnya
     * diarsipkan.
     */
    public function test_choosing_it_directly_is_refused_by_the_action(): void
    {
        [$user, $run, $trail] = $this->runDenganSatuJalur();
        $trail->update(['archived_at' => now()]);

        $this->actingAs($user)
            ->from(route('recommendations.show', $run))
            ->post(route('recommendations.pilih', $run), ['trail' => $trail->id])
            ->assertRedirect(route('recommendations.show', $run))
            ->assertSessionHasErrors(['trail' => 'Jalur ini sudah ditarik dari katalog dan tidak dapat dipilih lagi.']);

        $this->assertSame(0, AnalyticsEventModel::where('event_name', AnalyticsEvent::ROUTE_SELECTED->value)->count());
    }
}
