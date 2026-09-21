<?php

namespace Tests\Feature;

use App\Models\Mountain;
use App\Models\Profile;
use App\Models\Trail;
use App\Models\User;
use App\Models\UserExperience;
use App\Services\TrailFitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Kecocokan untuk sekumpulan jalur sekaligus.
 *
 * Diferensiator produk ini (§8) adalah menghubungkan karakteristik pendaki dengan
 * karakteristik jalur lalu menjelaskan alasannya. Mesinnya terbangun penuh dan selama ini
 * dipanggil di satu halaman, sehingga di sebelas halaman lain aplikasi ini memang katalog.
 *
 * Yang menghalangi penyebarannya bukan mesinnya melainkan ketiadaan cara memanggilnya
 * untuk banyak jalur tanpa meledakkan anggaran query (§96). Itu yang dibangun di sini.
 */
class TrailFitServiceTest extends TestCase
{
    use RefreshDatabase;

    private function pendaki(): User
    {
        $user = User::factory()->create();

        // Lewat factory, bukan array tangan. Kolomnya tidak bernama seperti dugaan:
        // completed_hikes_count, longest_hike_duration_minutes, highest_elevation_gain_m.
        // ProfileFactory juga sudah mengisi completed_at, yang menjadi syarat
        // hasCompletedProfile(); tanpa itu kecocokan tidak akan pernah dinilai.
        Profile::factory()->for($user)->create();
        UserExperience::factory()->for($user)->create([
            'completed_hikes_count' => 3,
            'highest_elevation_gain_m' => 800,
            'longest_hike_duration_minutes' => 480,
        ]);

        return $user->fresh();
    }

    /**
     * @return Collection<int, Trail>
     */
    private function jalur(int $jumlah): Collection
    {
        $gunung = Mountain::factory()->create();

        return Trail::factory()->count($jumlah)->for($gunung)->published()->create();
    }

    public function test_it_summarises_every_trail_it_is_given(): void
    {
        $trails = $this->jalur(3);

        $ringkasan = app(TrailFitService::class)->forTrails($this->pendaki(), $trails);

        $this->assertCount(3, $ringkasan);

        foreach ($trails as $trail) {
            $this->assertArrayHasKey($trail->id, $ringkasan);
        }
    }

    /**
     * Anggaran query adalah alasan layanan ini ada.
     *
     * Menilai dua belas jalur satu per satu akan memanggil status resmi dan pembatasan
     * segmen dua belas kali. Yang diukur di sini pertumbuhannya, bukan angka mutlaknya:
     * jumlah query untuk dua belas jalur tidak boleh lebih besar daripada untuk tiga.
     */
    public function test_it_costs_the_same_for_twelve_trails_as_for_three(): void
    {
        $user = $this->pendaki();
        $service = app(TrailFitService::class);

        // Pemanasan lebih dulu: pengukuran pertama menghitung cache yang terisi, bukan
        // pertumbuhan. Versi awal test ini mengukur cache yang menghangat dan melihat
        // angkanya menurun.
        $service->forTrails($user, $this->jalur(2));

        $tiga = $this->jalur(3);
        DB::enableQueryLog();
        DB::flushQueryLog();
        $service->forTrails($user, $tiga);
        $untukTiga = count(DB::getQueryLog());

        $duaBelas = $this->jalur(12);
        DB::flushQueryLog();
        $service->forTrails($user, $duaBelas);
        $untukDuaBelas = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertGreaterThan(0, $untukTiga, 'Tidak ada query sama sekali; pengukurannya tidak mengukur apa pun.');
        $this->assertLessThanOrEqual(
            $untukTiga,
            $untukDuaBelas,
            "Dua belas jalur memakai {$untukDuaBelas} query sementara tiga jalur memakai {$untukTiga}. Ada N+1."
        );
    }

    /**
     * Setiap ringkasan membawa satu kalimat alasan.
     *
     * §90 menuntut pengguna dapat memahami mengapa sebuah jalur direkomendasikan tanpa
     * membuka dokumentasi teknis. Label tanpa alasan adalah vonis.
     */
    public function test_every_summary_carries_one_sentence_of_reason(): void
    {
        $ringkasan = app(TrailFitService::class)->forTrails($this->pendaki(), $this->jalur(2));

        foreach ($ringkasan as $satu) {
            $this->assertNotSame('', trim($satu->alasan));
            $this->assertStringNotContainsString('%', $satu->alasan, 'Alasan tidak boleh memuat persentase (§91).');
        }
    }

    /**
     * failedRules menyimpan kunci mesin seperti trail_archived, bukan kalimat siap tampil.
     *
     * §90 melarang kunci mentah sampai ke pengguna. Sebuah test yang hanya memeriksa
     * "alasan tidak kosong" akan tetap lulus walau isinya cuma nama aturan itu sendiri;
     * di sini yang diperiksa adalah bentuknya: kunci mesin berupa huruf kecil dan garis
     * bawah saja, sedangkan kalimat sungguhan memuat huruf besar, spasi, dan beberapa kata.
     */
    public function test_a_disqualified_trail_gets_a_readable_reason_not_a_rule_key(): void
    {
        $gunung = Mountain::factory()->create();
        $jalur = Trail::factory()->count(1)->published()->for($gunung)->create([
            'archived_at' => now(),
        ]);

        $ringkasan = app(TrailFitService::class)->forTrails($this->pendaki(), $jalur);
        $satu = $ringkasan[$jalur->first()->id];

        $this->assertFalse($satu->eligible);
        $this->assertDoesNotMatchRegularExpression('/^[a-z_]+$/', $satu->alasan);
        $this->assertGreaterThan(3, str_word_count($satu->alasan), 'Alasan harus berupa kalimat, bukan satu kunci mesin.');
    }

    /**
     * Tanpa goal, ringkasan menyatakan dirinya kecocokan dasar.
     *
     * Kecocokan tanpa rencana dan kecocokan untuk rencana tertentu adalah dua pernyataan
     * berbeda, dan menyajikan yang pertama seolah yang kedua menyesatkan pembacanya.
     */
    public function test_a_summary_without_a_goal_says_so(): void
    {
        $ringkasan = app(TrailFitService::class)->forTrails($this->pendaki(), $this->jalur(1));

        $this->assertFalse(reset($ringkasan)->denganRencana);
    }

    /**
     * Skor internal tidak pernah ikut keluar (BR-09).
     */
    public function test_the_internal_score_never_leaves_the_engine(): void
    {
        $ringkasan = app(TrailFitService::class)->forTrails($this->pendaki(), $this->jalur(2));

        foreach ($ringkasan as $satu) {
            $this->assertFalse(property_exists($satu, 'internalScore'));
            $this->assertDoesNotMatchRegularExpression('/\d+[.,]\d+/', $satu->alasan);
        }
    }

    public function test_an_empty_collection_costs_nothing_and_returns_nothing(): void
    {
        $this->assertSame([], app(TrailFitService::class)->forTrails($this->pendaki(), collect()));
    }
}
