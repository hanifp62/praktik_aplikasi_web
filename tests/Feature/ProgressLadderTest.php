<?php

namespace Tests\Feature;

use App\Enums\CompletionState;
use App\Models\HikingHistory;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\User;
use App\Services\ProgressLadderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tangga kemajuan.
 *
 * Mekanik Strava adalah perbandingan yang dipersempit ke kelompok acuan yang cukup kecil
 * sehingga menang terasa mungkin. Modifikasinya di sini, dan ini modifikasi terpenting di
 * seluruh rancangan: acuannya diri sendiri, bukan pendaki lain.
 *
 * Papan peringkat tetap ditolak. Memberi hadiah pada jumlah menyerang model kepercayaan
 * data (§60) yang menjadi nilai produk ini; meniru Strava sampai ke papan peringkatnya
 * berarti ikut meniru kerusakannya.
 *
 * Acuannya elevation gain, bukan mdpl puncak (BR-02, §18): mdpl besar dan mudah
 * dibandingkan, tetapi yang membebani pendaki adalah tanjakannya, bukan ketinggian
 * puncak yang dituju.
 */
class ProgressLadderTest extends TestCase
{
    use RefreshDatabase;

    private function jalur(int $tanjakan): Trail
    {
        return Trail::factory()
            ->for(Mountain::factory()->create())
            ->create(['is_published' => true, 'elevation_gain_m' => $tanjakan]);
    }

    /**
     * Riwayat pendakian tidak menyimpan elevation gain sebagai kolom. Ia menyimpan
     * jalurnya, dan elevation gain tinggal di jalur itu (trails.elevation_gain_m).
     * Pembantu ini membuat hubungan itu terlihat di test, bukan tersembunyi di balik
     * factory.
     */
    private function pernahMendaki(User $user, int $tanjakan, CompletionState $keadaan = CompletionState::COMPLETED): void
    {
        HikingHistory::factory()->for($user)->create([
            'trail_id' => $this->jalur($tanjakan)->id,
            'completion_state' => $keadaan->value,
            'completed_at' => now()->subMonth(),
        ]);
    }

    /**
     * Riwayat kosong tidak menghasilkan kalimat karangan.
     *
     * Pendaki yang belum punya riwayat tidak punya acuan, dan mengarang acuan untuknya
     * menghasilkan kalimat yang terlihat pasti dan berdasar ketiadaan (§91).
     */
    public function test_an_empty_history_produces_no_sentence_at_all(): void
    {
        $this->assertNull(
            app(ProgressLadderService::class)->bandingkanDenganRiwayat(
                User::factory()->create(),
                $this->jalur(3000),
            )
        );
    }

    public function test_a_higher_mountain_reads_as_a_step_up(): void
    {
        $user = User::factory()->create();
        $this->pernahMendaki($user, 2000);

        $kalimat = app(ProgressLadderService::class)->bandingkanDenganRiwayat($user, $this->jalur(3000));

        $this->assertNotNull($kalimat);
        $this->assertStringContainsString('di atas', $kalimat);
    }

    public function test_a_comparable_mountain_reads_as_familiar_ground(): void
    {
        $user = User::factory()->create();
        $this->pernahMendaki($user, 3000);

        $kalimat = app(ProgressLadderService::class)->bandingkanDenganRiwayat($user, $this->jalur(3050));

        $this->assertStringContainsString('setara', $kalimat);
    }

    /**
     * Pita, bukan angka (§91).
     */
    public function test_it_never_states_a_number(): void
    {
        $user = User::factory()->create();
        $this->pernahMendaki($user, 2000);

        $kalimat = app(ProgressLadderService::class)->bandingkanDenganRiwayat($user, $this->jalur(3000));

        $this->assertDoesNotMatchRegularExpression('/\d/', $kalimat);
    }

    /**
     * Pendakian yang berbalik di tengah jalan bukan acuan.
     *
     * Berbalik tidak membuktikan tanjakannya tertuntaskan, dan memakainya sebagai acuan
     * akan memberi tahu pendaki bahwa ia sudah pernah menuntaskan tanjakan yang justru
     * membuatnya berbalik. Itu kesalahan yang paling tidak boleh dilakukan produk
     * keselamatan.
     */
    public function test_an_abandoned_hike_is_not_a_rung_on_the_ladder(): void
    {
        $user = User::factory()->create();
        $this->pernahMendaki($user, 3000, CompletionState::ABANDONED);

        $this->assertNull(
            app(ProgressLadderService::class)->bandingkanDenganRiwayat($user, $this->jalur(3100))
        );
    }

    /**
     * Tangga menerangkan, tidak pernah menghalangi.
     *
     * Kalimat yang berbunyi seperti izin mengubah §90 dari penjelasan menjadi penjaga
     * gerbang, dan produk ini membantu keputusan, bukan memberi restu.
     */
    public function test_it_explains_and_never_forbids(): void
    {
        $user = User::factory()->create();
        $this->pernahMendaki($user, 1000);

        $kalimat = app(ProgressLadderService::class)->bandingkanDenganRiwayat($user, $this->jalur(3500));

        foreach (['tidak boleh', 'dilarang', 'jangan', 'terlalu berbahaya'] as $larangan) {
            $this->assertStringNotContainsString($larangan, mb_strtolower($kalimat));
        }
    }

    /**
     * BR-02 dan §18: gunung yang lebih tinggi tidak berarti tanjakannya lebih berat.
     *
     * §18 memakai contoh ini secara harfiah: gunung 3000 mdpl yang tanjakannya ringan
     * lebih ringan daripada gunung yang lebih rendah tapi tanjakannya berat. Tangga ini
     * memakai elevation gain, bukan mdpl, jadi jalur bermdpl tinggi dengan tanjakan
     * ringan tidak boleh terbaca sebagai tingkat baru di atas riwayat pendakian yang
     * tanjakannya justru lebih berat.
     */
    public function test_a_taller_mountain_with_less_climbing_is_not_a_step_up(): void
    {
        $user = User::factory()->create();

        // Riwayat: gunung rendah (2000 mdpl) tapi tanjakannya berat (1400 m).
        $sudahDidaki = Trail::factory()
            ->for(Mountain::factory()->create(['elevation_mdpl' => 2000]))
            ->create(['is_published' => true, 'elevation_gain_m' => 1400]);

        HikingHistory::factory()->for($user)->create([
            'trail_id' => $sudahDidaki->id,
            'completion_state' => CompletionState::COMPLETED->value,
            'completed_at' => now()->subMonth(),
        ]);

        // Kandidat: gunung lebih tinggi (3000 mdpl) tapi tanjakannya ringan (700 m).
        $kandidat = Trail::factory()
            ->for(Mountain::factory()->create(['elevation_mdpl' => 3000]))
            ->create(['is_published' => true, 'elevation_gain_m' => 700]);

        $kalimat = app(ProgressLadderService::class)->bandingkanDenganRiwayat($user, $kandidat);

        $this->assertStringNotContainsString('di atas', $kalimat);
    }
}
