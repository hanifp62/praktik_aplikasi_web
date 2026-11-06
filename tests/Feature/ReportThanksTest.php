<?php

namespace Tests\Feature;

use App\Enums\ModerationStatus;
use App\Models\ReportThank;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Ucapan terima kasih pada laporan kondisi.
 *
 * Menggantikan papan peringkat kontribusi yang dibatalkan setelah risetnya: papan
 * peringkat memberi hadiah pada jumlah, dan memberi hadiah pada jumlah laporan
 * menghasilkan laporan bervolume tinggi bermutu rendah, yang menyerang persis model
 * kepercayaan data yang menjadi nilai produk ini.
 *
 * Yang dihargai di sini kegunaannya, dan yang menilainya pendaki lain yang membacanya
 * sebelum berangkat. Karena itu seluruh test di bawah menjaga satu hal yang sama:
 * angkanya tidak boleh dapat dinaikkan sendiri. Aksinya kini rute POST reports.thanks.
 */
class ReportThanksTest extends TestCase
{
    use RefreshDatabase;

    private Trail $trail;

    protected function setUp(): void
    {
        parent::setUp();

        $this->trail = Trail::factory()->easy()->published()->create();
    }

    private function laporan(ModerationStatus $status = ModerationStatus::APPROVED, ?User $pelapor = null, ?Trail $trail = null): TrailConditionReport
    {
        return TrailConditionReport::factory()->for($trail ?? $this->trail)->create([
            'user_id' => ($pelapor ?? User::factory()->create())->id,
            'moderation_status' => $status->value,
            'hike_date' => now()->subDays(2)->toDateString(),
        ]);
    }

    private function terimaKasih(User $sebagai, TrailConditionReport $laporan, bool $ya = true): TestResponse
    {
        return $this->actingAs($sebagai)
            ->from(route('trails.show', $this->trail))
            ->post(route('reports.thanks', $laporan), ['ya' => $ya]);
    }

    /**
     * @return array<string, mixed>
     */
    private function halaman(User $sebagai): array
    {
        return $this->actingAs($sebagai)->get(route('trails.show', $this->trail))->assertOk()->viewData('page')['props'];
    }

    public function test_a_hiker_can_thank_a_report_that_helped_them(): void
    {
        $laporan = $this->laporan();

        $this->terimaKasih(User::factory()->create(), $laporan)->assertRedirect(route('trails.show', $this->trail));

        $this->assertSame(1, ReportThank::where('trail_condition_report_id', $laporan->id)->count());
    }

    /**
     * Angka yang dapat dinaikkan sendirian tidak mengukur apa-apa.
     */
    public function test_thanking_twice_does_not_raise_the_number(): void
    {
        $laporan = $this->laporan();
        $pembaca = User::factory()->create();

        $this->terimaKasih($pembaca, $laporan);
        $this->terimaKasih($pembaca, $laporan);

        $this->assertSame(1, ReportThank::count());
    }

    public function test_it_can_be_taken_back(): void
    {
        $laporan = $this->laporan();
        $pembaca = User::factory()->create();

        $this->terimaKasih($pembaca, $laporan);
        $this->terimaKasih($pembaca, $laporan, false);

        $this->assertSame(0, ReportThank::count());
    }

    /**
     * Berterima kasih pada diri sendiri adalah bentuk paling sederhana dari menaikkan
     * angka sendirian.
     */
    public function test_a_reporter_cannot_thank_their_own_report(): void
    {
        $pelapor = User::factory()->create();
        $laporan = $this->laporan(pelapor: $pelapor);

        $this->terimaKasih($pelapor, $laporan)->assertForbidden();

        $this->assertSame(0, ReportThank::count());
    }

    /**
     * Laporan yang belum lolos moderasi belum terlihat siapa pun, jadi tidak ada yang
     * dapat menyatakan laporan itu menolongnya. 404: keberadaannya tidak dikonfirmasi.
     */
    public function test_an_unmoderated_report_cannot_be_thanked(): void
    {
        $this->terimaKasih(User::factory()->create(), $this->laporan(ModerationStatus::PENDING))->assertNotFound();

        $this->assertSame(0, ReportThank::count());
    }

    public function test_a_rejected_report_cannot_be_thanked(): void
    {
        $this->terimaKasih(User::factory()->create(), $this->laporan(ModerationStatus::REJECTED))->assertNotFound();

        $this->assertSame(0, ReportThank::count());
    }

    /**
     * Jalur terarsip ditarik dari peredaran (halamannya 404), jadi laporannya juga tidak
     * lagi terlihat untuk diberi terima kasih.
     */
    public function test_a_report_on_an_archived_trail_cannot_be_thanked(): void
    {
        $terarsip = Trail::factory()->easy()->published()->create(['archived_at' => now()]);

        $this->terimaKasih(User::factory()->create(), $this->laporan(trail: $terarsip))->assertNotFound();

        $this->assertSame(0, ReportThank::count());
    }

    public function test_a_guest_cannot_thank_anything(): void
    {
        $this->post(route('reports.thanks', $this->laporan()), ['ya' => true])->assertRedirect(route('login'));

        $this->assertSame(0, ReportThank::count());
    }

    public function test_the_intent_must_be_stated(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('reports.thanks', $this->laporan()), [])
            ->assertSessionHasErrors('ya');

        $this->assertSame(0, ReportThank::count());
    }

    public function test_the_count_and_the_readers_own_state_reach_the_page(): void
    {
        $laporan = $this->laporan();
        $pembaca = User::factory()->create();

        foreach (range(1, 3) as $i) {
            ReportThank::create([
                'trail_condition_report_id' => $laporan->id,
                'user_id' => User::factory()->create()->id,
            ]);
        }

        $this->terimaKasih($pembaca, $laporan);

        $laporanDiHalaman = $this->halaman($pembaca)['komunitas']['reports'][0];

        $this->assertSame(4, $laporanDiHalaman['thanksCount']);
        $this->assertTrue($laporanDiHalaman['sudahTerimaKasih']);
        $this->assertFalse($laporanDiHalaman['milikSaya']);
    }

    /**
     * Pelapor tidak ditawari tombolnya pada laporannya sendiri.
     */
    public function test_the_reporter_sees_their_own_report_marked_as_theirs(): void
    {
        $pelapor = User::factory()->create();
        $this->laporan(pelapor: $pelapor);

        $this->assertTrue($this->halaman($pelapor)['komunitas']['reports'][0]['milikSaya']);
        $this->assertStringContainsString('!r.milikSaya', file_get_contents(resource_path('js/components/trails/rinci/LaporanKomunitas.tsx')));
    }

    /**
     * Anggaran query tidak boleh naik seiring jumlah laporan: hitungan dan status
     * sudah-berterima-kasih dimuat sekali untuk seluruh daftar, bukan satu per laporan.
     */
    public function test_the_query_count_does_not_grow_with_the_number_of_reports(): void
    {
        $pembaca = User::factory()->create();

        foreach (range(1, 2) as $i) {
            $this->laporan();
        }

        // Render pemanasan: pemanggilan pertama mengisi cache snapshot status dan cuaca.
        $this->halaman($pembaca);

        $sedikit = $this->hitungQuery(fn () => $this->halaman($pembaca));

        foreach (range(1, 8) as $i) {
            $this->laporan();
        }

        $banyak = $this->hitungQuery(fn () => $this->halaman($pembaca));

        $this->assertGreaterThan(0, $sedikit);
        $this->assertSame($sedikit, $banyak, "Query tumbuh dari {$sedikit} menjadi {$banyak}.");
    }

    private function hitungQuery(callable $aksi): int
    {
        $jumlah = 0;

        DB::listen(function () use (&$jumlah) {
            $jumlah++;
        });

        $aksi();

        DB::getEventDispatcher()->forget('Illuminate\Database\Events\QueryExecuted');

        return $jumlah;
    }
}
