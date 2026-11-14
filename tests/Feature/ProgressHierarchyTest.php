<?php

namespace Tests\Feature;

use App\Enums\CompletionState;
use App\Models\HikingHistory;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Hierarki halaman Progres (spec v3 bagian 6.14, prototype 13).
 *
 * Diperbarui W7: keputusan lama menaruh peta gunung paling depan sebagai muatan emosional
 * halaman. Prototype pemilik dan spec v3 menjawab "Bagaimana perkembangan saya?" lebih dulu
 * dengan ringkasan tahun, grafik per pendakian, dan riwayat; peta tetap ada sebagai bagian
 * sesudah riwayat (ruling W7-R6), jadi tidak ada yang dibuang. Niatnya tetap: urutan dokumen
 * adalah urutan yang dibaca mata dan pembaca layar, angka berbagi satu kelompok, grafik hanya
 * membandingkan pendaki dengan dirinya sendiri (R-015), dan satu pendakian mendapat kalimat
 * jujur alih-alih grafik.
 */
class ProgressHierarchyTest extends TestCase
{
    use RefreshDatabase;
    use SumberReact;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-08 09:00:00', 'Asia/Jakarta'));
    }

    private function riwayat(User $user, string $nama, int $naik, string $tanggal): void
    {
        $trail = Trail::factory()->easy()->create(['name' => $nama, 'elevation_gain_m' => $naik]);
        $trip = TripPlan::factory()->create(['user_id' => $user->id, 'trail_id' => $trail->id, 'planned_date' => $tanggal]);

        HikingHistory::create([
            'user_id' => $user->id,
            'trip_plan_id' => $trip->id,
            'trail_id' => $trail->id,
            'trip_type' => $trip->trip_type->value,
            'completion_state' => CompletionState::COMPLETED->value,
            'preparation_completion_percent' => 90,
            'completed_at' => Carbon::parse($tanggal.' 16:00:00', 'Asia/Jakarta')->utc(),
        ]);
    }

    private function halaman(): string
    {
        return $this->tanpaKomentarJs(File::get(resource_path('js/pages/Community/HikerProgress.tsx')));
    }

    public function test_the_year_summary_leads_and_the_map_follows_the_history(): void
    {
        $sumber = $this->halaman();
        $urutan = array_map(fn (string $tag) => strpos($sumber, $tag), ['<RingkasanTahun', '<GrafikPendakian', '<TargetPribadi', '<Pencapaian', '<RiwayatProgres', 'id="progres-laporan"', '<PetaGaris']);

        $this->assertNotContains(false, $urutan, 'Setiap bagian Progres harus ada.');
        $this->assertSame($urutan, array_values(collect($urutan)->sort()->all()), 'Ringkasan tahun, grafik, target, pencapaian, riwayat, laporan, lalu peta (prototype 13).');
    }

    /**
     * Tiga petak setara tetap satu daftar, bukan tiga klaim yang bersaing.
     */
    public function test_the_year_figures_are_one_group(): void
    {
        $ringkasan = File::get(resource_path('js/components/progres/RingkasanTahun.tsx'));

        $this->assertMatchesRegularExpression('/<dl className="[^"]*grid[^"]*">.*<dt.*<dd.*<\/dl>/s', $ringkasan);
    }

    /**
     * R-015: grafiknya membandingkan pendaki dengan pendakiannya sendiri, dari yang paling awal.
     * Pendakian orang lain tidak ikut, jadi deretnya tepat dua butir.
     */
    public function test_the_chart_compares_the_hiker_with_their_own_earlier_hikes(): void
    {
        $user = User::factory()->create();
        $this->riwayat($user, 'Jalur Kedua', 1500, '2026-08-20');
        $this->riwayat($user, 'Jalur Pertama', 900, '2026-02-14');
        $this->riwayat(User::factory()->create(), 'Jalur Orang Lain', 3000, '2026-05-01');

        $this->actingAs($user)->get(route('progress'))->assertInertia(fn (AssertableInertia $page) => $page
            ->has('grafik.batang', 2)
            ->where('grafik.batang.0.nama', 'Jalur Pertama')
            ->where('grafik.batang.0.tanjakan', ['nilai' => 900, 'teks' => '900 m'])
            ->where('grafik.batang.1.nama', 'Jalur Kedua')
            ->where('grafik.batang.1.tanjakan.teks', '1.500 m'));
    }

    /**
     * Satu pendakian belum dapat dibandingkan dengan apa pun: kalimat jujur, bukan grafik satu
     * batang.
     */
    public function test_a_single_hike_gets_an_honest_note_instead_of_a_chart(): void
    {
        $user = User::factory()->create();
        $this->riwayat($user, 'Satu-satunya', 900, '2026-03-01');

        $this->actingAs($user)->get(route('progress'))->assertInertia(fn (AssertableInertia $page) => $page->where('grafik', null)->has('riwayat', 1));

        $this->assertStringContainsString('Grafik per pendakian muncul setelah ada dua pendakian', $this->halaman());
    }

    /**
     * Grafik dengan nilai tertulis: batang disembunyikan dari pembaca layar, tabel membawa angka
     * kedua ukuran, dan keterangannya kalimat. Tidak ada informasi yang hanya dibawa warna atau
     * garis putus-putus.
     */
    public function test_the_chart_writes_its_values_instead_of_only_drawing_them(): void
    {
        $grafik = $this->tanpaKomentarJs(File::get(resource_path('js/components/progres/GrafikPendakian.tsx')));

        $this->assertStringContainsString('<ol aria-hidden="true"', $grafik);
        // Tabelnya di dalam pembungkus sr-only: tabel ber-sr-only melebarkan halaman sempit.
        $this->assertStringContainsString('<div className="sr-only">', $grafik);
        $this->assertStringContainsString('<table>', $grafik);
        $this->assertStringContainsString('<td>{b.tanjakan.teks}</td>', $grafik);
        $this->assertStringContainsString('<td>{b.jarak.teks}</td>', $grafik);
        $this->assertStringContainsString('{angka.teks}', $grafik);
        $this->assertStringContainsString('<figcaption', $grafik);
    }

    /**
     * Tanpa papan peringkat, rentetan, atau teguran (R-015, induk W7 butir 2): kata-kata itu tidak
     * pernah muncul di halaman maupun komponennya.
     */
    public function test_no_ranking_streak_or_reprimand_words_reach_the_page(): void
    {
        $sumber = $this->halaman();

        foreach (File::files(resource_path('js/components/progres')) as $berkas) {
            $sumber .= $this->tanpaKomentarJs($berkas->getContents());
        }

        foreach (['streak', 'rentetan', 'beruntun', 'peringkat Anda', 'juara', 'kalah', 'tertinggal', 'gagal mencapai', 'ayo'] as $kata) {
            $this->assertDoesNotMatchRegularExpression('/ '.preg_quote($kata, '/').' /iu', $sumber, "Kata \"{$kata}\" tidak boleh ada di Progres.");
        }
    }
}
