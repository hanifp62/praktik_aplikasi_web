<?php

namespace Tests\Feature;

use App\Enums\CompletionState;
use App\Enums\ModerationStatus;
use App\Models\HikingHistory;
use App\Models\ReportThank;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\HikerProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Umpan balik dampak bagi pelapor.
 *
 * Pasangan dari ucapan terima kasih: yang pertama mengumpulkan sinyalnya, yang ini
 * mengembalikannya kepada orang yang menulis laporannya.
 *
 * Tanpa ini, menulis laporan kondisi terasa seperti mengisi formulir lalu menekan
 * kirim. Pelapor tidak pernah tahu laporannya terbit, dibaca, atau menolong siapa pun,
 * dan perilaku yang tidak pernah mendapat umpan balik berhenti dengan sendirinya.
 *
 * Angkanya milik pemiliknya sendiri dan tidak pernah dibandingkan dengan siapa pun.
 */
class ReporterImpactTest extends TestCase
{
    use RefreshDatabase;

    private function laporan(User $pelapor, ModerationStatus $status, int $terimaKasih = 0): TrailConditionReport
    {
        $laporan = TrailConditionReport::factory()->for(Trail::factory()->easy()->create())->create([
            'user_id' => $pelapor->id,
            'moderation_status' => $status->value,
        ]);

        foreach (range(1, $terimaKasih) as $i) {
            if ($terimaKasih === 0) {
                break;
            }

            ReportThank::create([
                'trail_condition_report_id' => $laporan->id,
                'user_id' => User::factory()->create()->id,
            ]);
        }

        return $laporan;
    }

    private function progres(User $user): array
    {
        return app(HikerProgressService::class)->forUser($user);
    }

    public function test_it_counts_published_reports_and_the_thanks_they_received(): void
    {
        $pelapor = User::factory()->create();

        $this->laporan($pelapor, ModerationStatus::APPROVED, terimaKasih: 3);
        $this->laporan($pelapor, ModerationStatus::APPROVED, terimaKasih: 1);

        $progres = $this->progres($pelapor);

        $this->assertSame(2, $progres['laporan_terbit']);
        $this->assertSame(4, $progres['terima_kasih']);
    }

    /**
     * Laporan yang masih menunggu moderasi belum terbit, dan menghitungnya sebagai
     * terbit membuat pelapor mengira sesuatu sudah menolong orang padahal belum
     * terlihat siapa pun.
     */
    public function test_a_report_still_waiting_for_moderation_is_not_counted_as_published(): void
    {
        $pelapor = User::factory()->create();

        $this->laporan($pelapor, ModerationStatus::APPROVED);
        $this->laporan($pelapor, ModerationStatus::PENDING);

        $this->assertSame(1, $this->progres($pelapor)['laporan_terbit']);
    }

    public function test_a_rejected_report_is_not_counted(): void
    {
        $pelapor = User::factory()->create();

        $this->laporan($pelapor, ModerationStatus::REJECTED, terimaKasih: 2);

        $progres = $this->progres($pelapor);

        $this->assertSame(0, $progres['laporan_terbit']);
        $this->assertSame(0, $progres['terima_kasih']);
    }

    public function test_the_numbers_belong_to_their_owner_alone(): void
    {
        $saya = User::factory()->create();
        $orangLain = User::factory()->create();

        $this->laporan($saya, ModerationStatus::APPROVED, terimaKasih: 1);
        $this->laporan($orangLain, ModerationStatus::APPROVED, terimaKasih: 9);

        $progres = $this->progres($saya);

        $this->assertSame(1, $progres['laporan_terbit']);
        $this->assertSame(1, $progres['terima_kasih']);
    }

    public function test_a_hiker_who_never_reported_sees_zero_without_error(): void
    {
        $progres = $this->progres(User::factory()->create());

        $this->assertSame(0, $progres['laporan_terbit']);
        $this->assertSame(0, $progres['terima_kasih']);
    }

    /**
     * Terlihat di halaman progresnya sendiri, dan dikalimatkan sebagai dampak pada
     * orang lain, bukan sebagai skor.
     */
    public function test_the_reporter_sees_it_on_their_progress_page(): void
    {
        $pelapor = User::factory()->create();
        $this->laporan($pelapor, ModerationStatus::APPROVED, terimaKasih: 2);

        $this->actingAs($pelapor)
            ->get(route('progress'))
            ->assertOk()
            ->assertSee('Laporan kondisi')
            ->assertSee('2 pendaki menyatakan laporan Anda menolong');
    }

    /**
     * Pendaki yang belum pernah melaporkan tidak diberi angka nol yang terbaca seperti
     * kekurangan. Yang ditampilkan ajakan, bukan penilaian.
     */
    public function test_someone_who_never_reported_is_invited_rather_than_scored(): void
    {
        $pendaki = User::factory()->create();

        // Satu pendakian supaya halamannya tidak berada di keadaan awal seluruhnya.
        HikingHistory::create([
            'user_id' => $pendaki->id,
            'trip_plan_id' => TripPlan::factory()->create([
                'user_id' => $pendaki->id,
                'trail_id' => Trail::factory()->easy()->create()->id,
            ])->id,
            'trail_id' => Trail::factory()->easy()->create()->id,
            'trip_type' => 'CAMPING',
            'completion_state' => CompletionState::COMPLETED->value,
            'preparation_completion_percent' => 80,
            'completed_at' => now()->subDay(),
        ]);

        $halaman = $this->actingAs($pendaki)->get(route('progress'));

        $halaman->assertSee('Belum ada laporan kondisi');
        $halaman->assertDontSee('0 pendaki menyatakan');
    }
}
