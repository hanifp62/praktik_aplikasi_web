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
 * Halaman hasil adalah satu-satunya daftar di aplikasi ini yang tidak dibatasi.
 *
 * Diukur sebelum perbaikan, dan pertumbuhannya linear sempurna: 10 jalur menghasilkan
 * 64 kB HTML, 20 jalur 126 kB, 40 jalur 251 kB. Biayanya 6,3 kB per baris, sebagian
 * besar dari untaian kelas pada tiga tombol yang diulang di setiap kartu.
 *
 * Empat puluh bukan angka hipotetis: itu skala sistem ini sekarang, 16 gunung dengan
 * dua sampai tiga jalur masing-masing. Pada cakupan nasional angkanya menjadi megabyte,
 * dikirim ke telepon di daerah yang sinyalnya justru paling tipis.
 *
 * Alasan keduanya bertemu di titik yang sama. Daftar berperingkat berisi empat puluh
 * kartu bukan rekomendasi: pendaki tidak dapat memilih di antara empat puluh, dan
 * jalur terbaik yang ditemukan mesin tenggelam di antara jalur yang hanya lumayan.
 *
 * PRD tidak mengatur jumlah yang ditampilkan, jadi batas ini keputusan rancangan,
 * bukan pemenuhan pasal.
 */
class ResultListBudgetTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Diukur pada 40 jalur. Sebelum perbaikan angkanya 251 kB. Ambang ini memberi
     * ruang untuk peringatan dan penjelasan yang panjang, tetapi menutup kembalinya
     * daftar tak terbatas.
     */
    private const ANGGARAN_KB = 120;

    private function hikerWithTrails(int $jumlah): array
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

        Trail::factory()->published()->count($jumlah)->easy()->create();

        $goal = HikingGoal::factory()->create([
            'user_id' => $user->id,
            'trip_type' => 'CAMPING',
            'region' => null,
        ]);

        return [$user, app(RouteFitService::class)->recommend($user, $goal)];
    }

    private function barisTerender(string $html): int
    {
        return substr_count($html, 'Pilih jalur ini');
    }

    public function test_the_result_page_stays_within_its_size_budget(): void
    {
        [$user, $run] = $this->hikerWithTrails(40);

        $html = Livewire::actingAs($user)->test(RecommendationResults::class, ['run' => $run])->html();
        $kb = strlen($html) / 1024;

        $this->assertLessThan(
            self::ANGGARAN_KB,
            $kb,
            sprintf('Halaman hasil %.0f kB untuk 40 jalur, melewati anggaran %d kB.', $kb, self::ANGGARAN_KB)
        );
    }

    public function test_only_the_strongest_matches_are_rendered_at_first(): void
    {
        [$user, $run] = $this->hikerWithTrails(40);

        $html = Livewire::actingAs($user)->test(RecommendationResults::class, ['run' => $run])->html();

        $this->assertSame(
            10,
            $this->barisTerender($html),
            'Sepuluh jalur teratas yang dirender lebih dulu, bukan seluruh daftar.'
        );
    }

    /**
     * Sisanya tetap dapat dijangkau. Membatasi daftar tidak boleh berarti
     * menyembunyikan jalur yang lolos penilaian.
     */
    public function test_the_rest_remain_reachable(): void
    {
        [$user, $run] = $this->hikerWithTrails(40);

        $page = Livewire::actingAs($user)->test(RecommendationResults::class, ['run' => $run]);

        $page->assertSee('30 jalur lainnya');

        $page->call('tampilkanLagi');

        $this->assertSame(
            20,
            $this->barisTerender($page->html()),
            'Setiap penekanan menambah sepuluh jalur berikutnya.'
        );
    }

    /**
     * Ketika hasilnya memang sedikit, tidak ada yang perlu disembunyikan dan tidak
     * ada tombol yang perlu muncul.
     */
    public function test_a_short_list_is_shown_whole_without_a_reveal_control(): void
    {
        [$user, $run] = $this->hikerWithTrails(4);

        $page = Livewire::actingAs($user)->test(RecommendationResults::class, ['run' => $run]);

        $this->assertSame(4, $this->barisTerender($page->html()));
        $page->assertDontSee('jalur lainnya');
    }
}
