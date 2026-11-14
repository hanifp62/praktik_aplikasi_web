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
use App\Services\Community\HasilPendakian;
use App\Services\Community\PencapaianPribadi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use ReflectionClassConstant;
use Tests\TestCase;

/**
 * Pencapaian turunan (spec v3 bagian 6.14; rencana induk W7 butir 7): diturunkan dari riwayat
 * pendaki sendiri, tanpa kompetisi. Data pendaki lain tidak pernah dipakai (R-015), pendakian
 * yang berbalik tidak membuka tonggak, dan angka yang tidak diketahui tidak dianggap nol.
 */
class PersonalAchievementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-15 09:00:00', 'Asia/Jakarta'));
    }

    private function pendakian(User $user, string $nama, ?int $naik, string $tanggal, CompletionState $hasil = CompletionState::COMPLETED): HikingHistory
    {
        $jalur = Trail::factory()->easy()->create(['name' => $nama, 'elevation_gain_m' => $naik]);
        $trip = TripPlan::factory()->create(['user_id' => $user->id, 'trail_id' => $jalur->id, 'planned_date' => $tanggal]);

        return HikingHistory::create([
            'user_id' => $user->id,
            'trip_plan_id' => $trip->id,
            'trail_id' => $jalur->id,
            'trip_type' => $trip->trip_type->value,
            'completion_state' => $hasil->value,
            'completed_at' => Carbon::parse($tanggal.' 16:00:00', 'Asia/Jakarta')->utc(),
        ]);
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function pencapaian(User $user): array
    {
        return $this->actingAs($user)->get(route('progress'))->assertOk()->viewData('page')['props']['pencapaian'];
    }

    public function test_each_milestone_names_the_first_completed_hike_that_crossed_it(): void
    {
        $user = User::factory()->create();
        $this->pendakian($user, 'Andong via Sawit', 380, '2026-02-14');
        $this->pendakian($user, 'Prau via Patak Banteng', 700, '2026-07-19');
        $this->pendakian($user, 'Gede via Cibodas', 1660, '2026-10-10');
        $this->pendakian($user, 'Merbabu via Selo', 1500, '2026-10-12');

        $this->assertSame([
            ['kunci' => 'tanjakan-1000', 'jenis' => 'tanjakan', 'judul' => 'Tanjakan pertama di atas 1.000 m', 'keterangan' => 'Gede via Cibodas · Okt 2026'],
            ['kunci' => 'tanjakan-500', 'jenis' => 'tanjakan', 'judul' => 'Tanjakan pertama di atas 500 m', 'keterangan' => 'Prau via Patak Banteng · Jul 2026'],
        ], $this->pencapaian($user));
    }

    /**
     * Berbalik di jalur tidak membuktikan tanjakannya tertuntaskan, dan jalur tanpa angka tidak
     * dianggap nol maupun tinggi. Angka yang ditulis pendaki sendiri ikut (aturan Progres).
     */
    public function test_abandoned_and_unknown_hikes_open_no_milestone_but_written_numbers_do(): void
    {
        $user = User::factory()->create();
        $this->pendakian($user, 'Rinjani via Sembalun', 2600, '2026-05-01', CompletionState::ABANDONED);
        $this->pendakian($user, 'Jalur Tanpa Angka', null, '2026-06-01');

        $this->assertSame([], $this->pencapaian($user));

        HikingHistory::factory()->manual()->for($user)->create(['trail_id' => null, 'trail_name' => 'Lawu via Cemoro Sewu', 'recorded_gain_m' => 1200, 'completed_at' => Carbon::parse('2026-08-17 05:00:00')]);

        $this->assertSame(['Tanjakan pertama di atas 1.000 m', 'Tanjakan pertama di atas 500 m'], array_column($this->pencapaian($user), 'judul'));
        $this->assertSame('Lawu via Cemoro Sewu · Agt 2026', $this->pencapaian($user)[0]['keterangan']);
    }

    /**
     * Laporan kondisi pertama yang lolos moderasi, dengan terima kasih yang diterimanya; laporan
     * yang masih menunggu belum terbit.
     */
    public function test_the_first_published_report_is_an_achievement_and_a_pending_one_is_not(): void
    {
        $user = User::factory()->create();
        $menunggu = TrailConditionReport::factory()->for(Trail::factory()->easy()->create(['name' => 'Jalur Menunggu']))->create(['user_id' => $user->id, 'moderation_status' => ModerationStatus::PENDING->value]);

        $this->assertSame([], $this->pencapaian($user));

        $terbit = TrailConditionReport::factory()->for(Trail::factory()->easy()->create(['name' => 'Prau via Patak Banteng']))->create(['user_id' => $user->id, 'moderation_status' => ModerationStatus::APPROVED->value]);

        foreach (range(1, 2) as $i) {
            ReportThank::create(['trail_condition_report_id' => $terbit->id, 'user_id' => User::factory()->create()->id]);
        }

        $this->assertSame([['kunci' => 'laporan-pertama', 'jenis' => 'laporan', 'judul' => 'Laporan kondisi pertama terbit', 'keterangan' => 'Prau via Patak Banteng · 2 terima kasih']], $this->pencapaian($user));
        $this->assertNotNull($menunggu->fresh());
    }

    /**
     * R-015: pencapaian tidak pernah memakai data pengguna lain, dan tidak membawa peringkat,
     * persentil, atau pembanding.
     */
    public function test_achievements_never_use_other_hikers_data(): void
    {
        $saya = User::factory()->create();
        $orangLain = User::factory()->create();
        $this->pendakian($saya, 'Andong via Sawit', 600, '2026-03-01');
        $this->pendakian($orangLain, 'Semeru via Ranu Pane', 3100, '2026-02-01');
        TrailConditionReport::factory()->for(Trail::factory()->easy()->create())->create(['user_id' => $orangLain->id, 'moderation_status' => ModerationStatus::APPROVED->value]);

        $pencapaian = $this->pencapaian($saya);

        $this->assertSame(['Tanjakan pertama di atas 500 m'], array_column($pencapaian, 'judul'));

        foreach (['peringkat', 'persentil', 'rank', 'pendaki lain', '%'] as $kata) {
            $this->assertStringNotContainsStringIgnoringCase($kata, json_encode($pencapaian, JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * Tonggak di Progres dan pencapaian di ringkasan satu pendakian (W6) memakai daftar yang sama.
     */
    public function test_progress_and_the_hike_summary_share_the_same_milestones(): void
    {
        $this->assertSame((new ReflectionClassConstant(HasilPendakian::class, 'TONGGAK_TANJAKAN'))->getValue(), PencapaianPribadi::TONGGAK);
    }
}
