<?php

namespace Tests\Feature;

use App\Enums\CompletionState;
use App\Enums\ModerationStatus;
use App\Models\HikingHistory;
use App\Models\ModerationAction;
use App\Models\ReportThank;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\HikerProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
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

    private function sumber(): string
    {
        return File::get(resource_path('js/pages/Community/HikerProgress.tsx'));
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
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('laporan.terbit', 1)
                ->where('laporan.terimaKasih', 2));

        $this->assertStringContainsString('pendaki menyatakan laporan Anda menolong mereka.', $this->sumber());
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

        $this->actingAs($pendaki)->get(route('progress'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('laporan.terbit', 0)
            ->where('laporan.terimaKasih', 0));

        // Nol tidak pernah dirender sebagai angka: kalimat terima kasih hanya muncul di
        // atas nol, dan yang belum pernah melapor mendapat ajakan.
        $sumber = $this->sumber();
        $this->assertStringContainsString('Belum ada laporan kondisi dari Anda.', $sumber);
        $this->assertStringContainsString('laporan.terimaKasih > 0 ?', $sumber);
    }

    /**
     * Ruling AF-3 (audit E2E I-3): laporan yang menunggu moderasi bukan "belum ada laporan".
     * Halaman menerima jumlah laporan pengguna ini yang menunggu, termasuk FLAGGED yang tampil ke
     * pelapornya sebagai menunggu (ruling W6-6c2), dari satu COUNT. Laporan yang ditolak dan
     * laporan pengguna lain tidak terhitung. Selama belum ada yang terbit, kalimatnya menyebut
     * jumlah itu dan ajakan "Tulis laporan kondisi" tidak ditawarkan, supaya tidak terkirim laporan
     * ganda; cabang lain tetap.
     */
    public function test_reports_waiting_for_moderation_are_named_and_no_second_report_is_invited(): void
    {
        $pelapor = User::factory()->create();
        $this->laporan($pelapor, ModerationStatus::PENDING);
        $this->laporan($pelapor, ModerationStatus::FLAGGED);
        $this->laporan($pelapor, ModerationStatus::REJECTED);
        $this->laporan(User::factory()->create(), ModerationStatus::PENDING);

        $this->actingAs($pelapor)->get(route('progress'))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->where('laporan.terbit', 0)
            ->where('laporan.menunggu', 2));

        $kosong = User::factory()->create();
        $this->actingAs($kosong)->get(route('progress'))->assertInertia(fn (AssertableInertia $page) => $page->where('laporan.menunggu', 0));

        $sumber = $this->sumber();
        $mulai = strpos($sumber, '{laporan.terbit === 0 && laporan.menunggu > 0 ? (');
        // DF-10 (tinjauan audit TA-m11) menyisipkan cabang "ditolak" di antara cabang
        // menunggu dan cabang "belum ada laporan", jadi penanda cabang berikutnya kini
        // cabang itu, bukan lagi langsung cabang terbit === 0.
        $cabangLain = strpos($sumber, ') : laporan.terbit === 0 && laporan.menunggu === 0 && laporan.ditolak > 0 ? (');

        $this->assertNotFalse($mulai, 'Cabang laporan menunggu harus diperiksa lebih dulu daripada cabang belum ada laporan.');
        $this->assertNotFalse($cabangLain);
        $this->assertLessThan($cabangLain, $mulai);

        $cabang = substr($sumber, $mulai, $cabangLain - $mulai);
        $this->assertStringContainsString('<span data-angka>{laporan.menunggu}</span> laporan kondisi Anda sedang menunggu moderasi. Laporan tampil untuk pendaki lain sesudah disetujui.', $cabang);
        $this->assertStringNotContainsString('reportsCreate', $cabang);
        $this->assertStringNotContainsString('Tulis laporan kondisi', $cabang);
    }

    /**
     * Ruling DF-10 (tinjauan audit TA-m11, sisa I-3): pelapor yang laporannya hanya
     * ditolak atau dihapus moderator tidak boleh membaca "Belum ada laporan kondisi dari
     * Anda", sama seperti pelapor yang belum pernah menulis laporan sama sekali. Riwayat
     * pendakian untuk laporan yang sama menulis "Ditolak", jadi kontradiksi antarhalaman
     * (sejenis I-3) harus ditutup di sini juga. Ajakan menulis laporan baru tetap ada,
     * berbeda dari cabang "menunggu" yang sengaja tanpa ajakan.
     *
     * Diubah ruling EF-2 dan EF-3 (tinjauan ulang TB-m2, TB-m3): fixture lama hanya berisi satu
     * laporan REJECTED lepas tanpa pendakian, jadi `kosong` true dan halaman merender kartu
     * "Belum ada pendakian yang tercatat", bukan cabang ini. Kini fixture berisi satu riwayat
     * pendakian selesai (`kosong` false), satu laporan REJECTED beralasan yang tertaut ke riwayat
     * itu, satu laporan REJECTED lepas, dan satu laporan REMOVED. `ditolak` 3 hanya benar bila setiap
     * status dihitung tepat sekali: mutasi yang membuang REMOVED atau menghitung REJECTED dua kali
     * memberi 2 atau 4 (TC-m2 tinjauan akhir).
     * Riwayat pendakian juga diperiksa memuat alasan laporan tertaut itu.
     *
     * Diubah ruling EF-1 (TB-m1): alasan moderator opsional, dan Riwayat hanya memuat laporan yang
     * tertaut ke riwayat, jadi kalimatnya tidak lagi menjanjikan alasan untuk setiap laporan.
     */
    public function test_a_reporter_with_only_rejected_reports_is_told_where_the_reason_lives(): void
    {
        $pelapor = User::factory()->create();
        $trail = Trail::factory()->easy()->create();
        $riwayat = HikingHistory::create([
            'user_id' => $pelapor->id,
            'trip_plan_id' => TripPlan::factory()->create(['user_id' => $pelapor->id, 'trail_id' => $trail->id])->id,
            'trail_id' => $trail->id,
            'trip_type' => 'CAMPING',
            'completion_state' => CompletionState::COMPLETED->value,
            'preparation_completion_percent' => 80,
            'completed_at' => now()->subDay(),
        ]);

        $ditolak = $this->laporan($pelapor, ModerationStatus::REJECTED);
        ModerationAction::create([
            'moderator_id' => User::factory()->create()->id,
            'moderatable_type' => $ditolak->getMorphClass(),
            'moderatable_id' => $ditolak->id,
            'action' => 'REJECT',
            'reason' => 'Foto tidak menunjukkan jalurnya.',
        ]);
        $riwayat->update(['trail_condition_report_id' => $ditolak->id]);
        $this->laporan($pelapor, ModerationStatus::REJECTED);
        $this->laporan($pelapor, ModerationStatus::REMOVED);

        $this->actingAs($pelapor)->get(route('progress'))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->where('kosong', false)
            ->where('laporan.terbit', 0)
            ->where('laporan.menunggu', 0)
            ->where('laporan.ditolak', 3));

        $this->actingAs($pelapor)->get(route('history'))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->where('entri.data.0.laporan.status', 'Ditolak')
            ->where('entri.data.0.laporan.alasanDitolak', 'Foto tidak menunjukkan jalurnya.'));

        $sumber = $this->sumber();
        $mulai = strpos($sumber, ') : laporan.terbit === 0 && laporan.menunggu === 0 && laporan.ditolak > 0 ? (');
        $cabangLain = strpos($sumber, ') : laporan.terbit === 0 ? (', $mulai);

        $this->assertNotFalse($mulai, 'Cabang laporan ditolak/dihapus tanpa yang terbit atau menunggu harus ada.');
        $this->assertNotFalse($cabangLain);

        $cabang = substr($sumber, $mulai, $cabangLain - $mulai);
        $this->assertStringContainsString(
            'Belum ada laporan Anda yang terbit. {laporan.ditolak} laporan ditolak atau dihapus moderator. Alasan moderator, bila ada, tercantum di{\' \'}',
            $cabang,
        );
        $this->assertMatchesRegularExpression(
            '/<Tautan href=\{history\.url\(\)\}[^>]*>\s*Riwayat pendakian\s*<\/Tautan>\{\' \'\}\s*untuk laporan yang terhubung ke sebuah pendakian\.\s*<\/p>/',
            $cabang,
        );
        $this->assertStringNotContainsString('alasannya tercantum', $cabang, 'Alasan tidak dijanjikan untuk setiap laporan (EF-1).');
        $this->assertStringContainsString('reportsCreate', $cabang, 'Ajakan menulis laporan baru harus tetap ada di cabang ini.');
        $this->assertStringContainsString('Tulis laporan kondisi', $cabang);
    }
}
