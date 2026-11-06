<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\NavigationExperience;
use App\Models\HikingGoal;
use App\Models\RecommendationRun;
use App\Models\Trail;
use App\Models\User;
use App\Services\RouteFitService;
use App\Services\Trails\TrailSearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Halaman hasil adalah satu-satunya daftar di aplikasi ini yang tidak dibatasi.
 *
 * Diukur sebelum perbaikan, dan pertumbuhannya linear sempurna: 10 jalur menghasilkan
 * 64 kB HTML, 20 jalur 126 kB, 40 jalur 251 kB.
 *
 * Empat puluh bukan angka hipotetis: itu skala sistem ini sekarang. Pada cakupan
 * nasional angkanya menjadi megabyte, dikirim ke telepon di daerah yang sinyalnya justru
 * paling tipis. Daftar berperingkat berisi empat puluh kartu juga bukan rekomendasi:
 * jalur terbaik yang ditemukan mesin tenggelam di antara jalur yang hanya lumayan.
 *
 * Di halaman React yang dikirim adalah props: sepuluh jalur teratas, lalu sepuluh
 * berikutnya lewat `?tampil=` atas permintaan.
 */
class ResultListBudgetTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Sebelum perbaikan halaman 40 jalur berukuran 251 kB. Ambang ini memberi ruang
     * untuk peringatan dan penjelasan yang panjang, tetapi menutup kembalinya daftar
     * tak terbatas.
     */
    private const ANGGARAN_KB = 120;

    /**
     * Jelajah untuk 40 jalur berprofil rapat, diukur di simulasi W3 Tugas 4: 29 kB untuk satu
     * halaman kartu ditambah atribut semua kandidat, 75 kB bila semua kandidat dikirim
     * sebagai kartu penuh. Ambangnya di antara keduanya.
     */
    private const ANGGARAN_JELAJAH_KB = 45;

    /**
     * @return array{0: User, 1: RecommendationRun}
     */
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

    public function test_the_result_page_stays_within_its_size_budget(): void
    {
        [$user, $run] = $this->hikerWithTrails(40);

        $kb = strlen($this->actingAs($user)->get(route('recommendations.show', $run))->assertOk()->getContent()) / 1024;

        $this->assertLessThan(
            self::ANGGARAN_KB,
            $kb,
            sprintf('Halaman hasil %.0f kB untuk 40 jalur, melewati anggaran %d kB.', $kb, self::ANGGARAN_KB)
        );
    }

    public function test_only_the_strongest_matches_are_sent_at_first(): void
    {
        [$user, $run] = $this->hikerWithTrails(40);

        $this->actingAs($user)->get(route('recommendations.show', $run))->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('eligible', 10)
                ->where('sisaEligible', 30));
    }

    /**
     * Sisanya tetap dapat dijangkau. Membatasi daftar tidak boleh berarti
     * menyembunyikan jalur yang lolos penilaian.
     */
    public function test_the_rest_remain_reachable(): void
    {
        [$user, $run] = $this->hikerWithTrails(40);

        $this->actingAs($user)->get(route('recommendations.show', ['run' => $run, 'tampil' => 20]))->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('eligible', 20)
                ->where('sisaEligible', 20)
                ->where('tampil', 20));
    }

    /**
     * Angka yang dikirim klien hanya menambah, tidak pernah mengurangi di bawah sepuluh
     * atau melewati jumlah yang ada.
     */
    public function test_the_reveal_count_is_clamped(): void
    {
        [$user, $run] = $this->hikerWithTrails(12);

        $this->actingAs($user)->get(route('recommendations.show', ['run' => $run, 'tampil' => -5]))->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->has('eligible', 10)->where('sisaEligible', 2));

        $this->actingAs($user)->get(route('recommendations.show', ['run' => $run, 'tampil' => 999]))->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->has('eligible', 12)->where('sisaEligible', 0));
    }

    /**
     * Jelajah (W3 Tugas 4): kartu dikirim satu halaman (TrailSearch::PER_HALAMAN), dan untuk
     * seluruh kandidat hanya atribut saringan (enam nilai pendek per jalur) supaya "Tampilkan
     * N jalur" dihitung di klien tanpa kunjungan ke server. Anggaran ini menutup kembalinya
     * kartu penuh untuk seluruh kandidat.
     */
    public function test_jelajah_stays_within_its_size_budget(): void
    {
        [$user] = $this->hikerWithTrails(40);
        // Profil rekaman GPS yang rapat, seperti data sungguhan: garis kartu dijarangkan ke
        // SkalaProfil::MAKS_TITIK, jadi resolusi rekaman tidak menambah ukuran halaman.
        $profil = array_map(fn (int $i) => ['km' => $i / 20, 'm' => 1200 + (int) round(600 * sin($i / 40))], range(0, 199));
        Trail::query()->update(['elevation_profile' => json_encode($profil)]);

        $kb = strlen($this->actingAs($user)->get(route('trails.index'))->assertOk()->getContent()) / 1024;

        $this->assertLessThan(
            self::ANGGARAN_JELAJAH_KB,
            $kb,
            sprintf('Jelajah %.0f kB untuk 40 jalur, melewati anggaran %d kB.', $kb, self::ANGGARAN_JELAJAH_KB)
        );
    }

    public function test_jelajah_sends_one_page_of_cards_and_attributes_for_every_candidate(): void
    {
        [$user] = $this->hikerWithTrails(40);

        $this->actingAs($user)->get(route('trails.index'))->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('trails.data', TrailSearch::PER_HALAMAN)
                ->where('trails.total', 40)
                ->has('atribut', 40)
                ->has('atribut.0', 6));
    }

    /**
     * Pembaca yang dinilai memicu penilaian kecocokan dan status untuk seluruh kandidat;
     * keduanya batch, jadi jumlah kueri tidak tumbuh bersama jumlah jalur (PageQueryBudgetTest
     * menjaga jalur pembaca tanpa profil).
     */
    public function test_jelajah_queries_do_not_grow_with_the_number_of_trails_for_a_rated_reader(): void
    {
        [$user] = $this->hikerWithTrails(4);
        $this->actingAs($user)->get(route('trails.index'))->assertOk();

        $sedikit = $this->hitungKueri(route('trails.index'));
        Trail::factory()->published()->count(20)->create();
        $banyak = $this->hitungKueri(route('trails.index'));

        $this->assertSame($sedikit, $banyak, "Beban tumbuh: {$sedikit} kueri untuk 4 jalur, {$banyak} untuk 24.");
    }

    private function hitungKueri(string $url): int
    {
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->get($url)->assertOk();
        $jumlah = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $jumlah;
    }

    /**
     * Ketika hasilnya memang sedikit, tidak ada yang perlu disembunyikan dan tidak
     * ada tombol yang perlu muncul.
     */
    public function test_a_short_list_is_shown_whole_without_a_reveal_control(): void
    {
        [$user, $run] = $this->hikerWithTrails(4);

        $this->actingAs($user)->get(route('recommendations.show', $run))->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('eligible', 4)
                ->where('sisaEligible', 0));
    }
}
