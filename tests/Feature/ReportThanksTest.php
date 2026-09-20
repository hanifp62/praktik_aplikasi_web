<?php

namespace Tests\Feature;

use App\Enums\ModerationStatus;
use App\Livewire\Trails\TrailDetail;
use App\Models\ReportThank;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
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
 * angkanya tidak boleh dapat dinaikkan sendiri.
 */
class ReportThanksTest extends TestCase
{
    use RefreshDatabase;

    private Trail $trail;

    protected function setUp(): void
    {
        parent::setUp();

        $this->trail = Trail::factory()->easy()->create();
    }

    private function laporan(ModerationStatus $status = ModerationStatus::APPROVED, ?User $pelapor = null): TrailConditionReport
    {
        return TrailConditionReport::factory()->for($this->trail)->create([
            'user_id' => ($pelapor ?? User::factory()->create())->id,
            'moderation_status' => $status->value,
            'hike_date' => now()->subDays(2)->toDateString(),
        ]);
    }

    private function halaman(User $sebagai)
    {
        return Livewire::actingAs($sebagai)->test(TrailDetail::class, ['trail' => $this->trail]);
    }

    public function test_a_hiker_can_thank_a_report_that_helped_them(): void
    {
        $laporan = $this->laporan();
        $pembaca = User::factory()->create();

        $this->halaman($pembaca)->call('berterimaKasih', $laporan->id);

        $this->assertSame(1, ReportThank::where('trail_condition_report_id', $laporan->id)->count());
    }

    /**
     * Angka yang dapat dinaikkan sendirian tidak mengukur apa-apa.
     */
    public function test_thanking_twice_does_not_raise_the_number(): void
    {
        $laporan = $this->laporan();
        $pembaca = User::factory()->create();

        $this->halaman($pembaca)->call('berterimaKasih', $laporan->id);
        $this->halaman($pembaca->fresh())->call('berterimaKasih', $laporan->id);

        $this->assertSame(1, ReportThank::count());
    }

    public function test_it_can_be_taken_back(): void
    {
        $laporan = $this->laporan();
        $pembaca = User::factory()->create();

        $this->halaman($pembaca)->call('berterimaKasih', $laporan->id);
        $this->halaman($pembaca->fresh())->call('berterimaKasih', $laporan->id, false);

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

        $this->halaman($pelapor)->call('berterimaKasih', $laporan->id);

        $this->assertSame(0, ReportThank::count());
    }

    /**
     * Laporan yang belum lolos moderasi belum terlihat siapa pun, jadi tidak ada yang
     * dapat menyatakan laporan itu menolongnya.
     */
    public function test_an_unmoderated_report_cannot_be_thanked(): void
    {
        $laporan = $this->laporan(ModerationStatus::PENDING);

        $this->halaman(User::factory()->create())->call('berterimaKasih', $laporan->id);

        $this->assertSame(0, ReportThank::count());
    }

    public function test_a_rejected_report_cannot_be_thanked(): void
    {
        $laporan = $this->laporan(ModerationStatus::REJECTED);

        $this->halaman(User::factory()->create())->call('berterimaKasih', $laporan->id);

        $this->assertSame(0, ReportThank::count());
    }

    /**
     * Laporan di jalur lain tidak dapat disentuh dari halaman ini.
     */
    public function test_a_report_from_another_trail_cannot_be_thanked_from_here(): void
    {
        $lain = TrailConditionReport::factory()->for(Trail::factory()->easy()->create())->create([
            'user_id' => User::factory()->create()->id,
            'moderation_status' => ModerationStatus::APPROVED->value,
        ]);

        $this->halaman(User::factory()->create())->call('berterimaKasih', $lain->id);

        $this->assertSame(0, ReportThank::count());
    }

    public function test_the_count_is_shown_on_the_report(): void
    {
        $laporan = $this->laporan();

        foreach (range(1, 3) as $i) {
            ReportThank::create([
                'trail_condition_report_id' => $laporan->id,
                'user_id' => User::factory()->create()->id,
            ]);
        }

        $this->halaman(User::factory()->create())->assertSee('3');
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

        // Render pemanasan: pemanggilan pertama mengisi cache snapshot status dan cuaca,
        // sehingga tanpa ini yang terukur pemanasannya, bukan pengaruh jumlah laporan.
        $this->halaman($pembaca)->html();

        $sedikit = $this->hitungQuery(fn () => $this->halaman($pembaca)->html());

        foreach (range(1, 8) as $i) {
            $this->laporan();
        }

        $banyak = $this->hitungQuery(fn () => $this->halaman($pembaca)->html());

        $this->assertSame($sedikit, $banyak, "Query tumbuh dari {$sedikit} menjadi {$banyak}.");
    }

    private function hitungQuery(callable $aksi): int
    {
        $jumlah = 0;

        DB::listen(function () use (&$jumlah) {
            $jumlah++;
        });

        $aksi();

        return $jumlah;
    }
}
