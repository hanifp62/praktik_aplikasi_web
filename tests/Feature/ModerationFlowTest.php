<?php

namespace Tests\Feature;

use App\Enums\ModerationAction as ModerationActionType;
use App\Enums\ModerationStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\HikingHistory;
use App\Models\ModerationAction;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\TrailSegment;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Moderator adalah pengguna ketiga sistem ini, dan alurnya belum pernah diaudit.
 *
 * Laporan komunitas melewati tangannya sebelum dilihat pendaki lain, sehingga hambatan
 * di sini menahan intel lapangan yang justru paling segar.
 */
class ModerationFlowTest extends TestCase
{
    use RefreshDatabase;

    /** Ruling W6-6q, ditulis persis supaya mutasi kalimat di controller merah. */
    private const KALIMAT_ISI_BERUBAH = 'Isi laporan ini berubah sejak Anda membukanya, jadi tindakan Anda tidak dijalankan. Baca isi terbarunya sebelum memutus.';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('filesystems.report_photos_disk'));
    }

    /**
     * Moderator mengetik alasan, alasannya tersimpan, lalu tidak pernah dibaca siapa pun.
     * Pelapor hanya melihat "Ditolak" tanpa tahu apa yang salah, sehingga ia tidak dapat
     * memperbaiki maupun mengirim ulang dengan benar. Usaha moderator pun terbuang.
     */
    public function test_a_rejected_report_tells_its_author_why(): void
    {
        $laporan = $this->laporan();
        $riwayat = $this->riwayat($laporan);

        // Ruling W6-6c dan W6-6q: setuju dan tolak membawa sidik isi dan status yang moderator baca.
        $this->actingAs($this->moderator())
            ->post(route('moderation.act', $laporan), [
                'action' => ModerationActionType::REJECT->value,
                'reason' => 'Fotonya tidak menunjukkan kondisi jalur.',
                'sidik' => $laporan->sidik(),
                'status_dibaca' => ModerationStatus::PENDING->value,
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($riwayat->user)
            ->get('/history')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('entri.data.0.laporan.alasanDitolak', 'Fotonya tidak menunjukkan kondisi jalur.'));
    }

    public function test_an_approved_report_needs_no_explanation_to_its_author(): void
    {
        $laporan = $this->laporan();
        $riwayat = $this->riwayat($laporan);

        $this->actingAs($this->moderator())
            ->post(route('moderation.act', $laporan), [
                'action' => ModerationActionType::APPROVE->value,
                'reason' => 'Catatan internal moderator.',
                'sidik' => $laporan->sidik(),
                'status_dibaca' => ModerationStatus::PENDING->value,
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($riwayat->user)
            ->get('/history')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('entri.data.0.laporan.status', ModerationStatus::APPROVED->label())
                ->where('entri.data.0.laporan.alasanDitolak', null));
    }

    /**
     * Setiap tindakan admin lain memberi tahu bahwa ia berhasil. Moderasi tidak, sehingga
     * moderator menekan tombol lalu menebak apakah tindakannya tercatat.
     */
    public function test_the_moderator_is_told_the_action_landed(): void
    {
        $laporan = $this->laporan();

        $this->actingAs($this->moderator())
            ->from(route('moderation.queue'))
            ->post(route('moderation.act', $laporan), ['action' => ModerationActionType::APPROVE->value, 'sidik' => $laporan->sidik(), 'status_dibaca' => ModerationStatus::PENDING->value])
            ->assertRedirect(route('moderation.queue'))
            ->assertSessionHas('status', 'Laporan disetujui dan kini tampil untuk pendaki lain.');
    }

    /**
     * Menghapus laporan membuang intel lapangan yang tidak dapat diambil ulang: pendakian
     * itu sudah lewat.
     */
    public function test_a_destructive_action_asks_before_it_runs(): void
    {
        $this->laporan();

        $this->actingAs($this->moderator())
            ->get('/moderation')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Moderation/Queue')
                ->where('actions.1.value', ModerationActionType::REJECT->value)
                ->where('actions.1.konfirmasi', fn (?string $teks) => $teks !== null && str_contains($teks, 'tidak dapat diambil ulang'))
                ->where('actions.3.value', ModerationActionType::REMOVE->value)
                ->where('actions.3.konfirmasi', fn (?string $teks) => $teks !== null));

        $this->assertStringContainsString('window.confirm(t.konfirmasi)', File::get(resource_path('js/pages/Moderation/Queue.tsx')));
    }

    public function test_approving_never_asks_for_confirmation(): void
    {
        $this->laporan();

        $this->actingAs($this->moderator())
            ->get('/moderation')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('actions.0.value', ModerationActionType::APPROVE->value)
                ->where('actions.0.konfirmasi', null)
                ->where('actions.2.value', ModerationActionType::FLAG->value)
                ->where('actions.2.konfirmasi', null));
    }

    /**
     * Kotak isian di antrean ini terlewat ketika batas kontrol diperbaiki, karena kelas
     * gayanya ditulis berbeda dari kotak isian lain.
     */
    public function test_the_reason_field_uses_the_visible_border(): void
    {
        $this->laporan();

        $this->actingAs($this->moderator())->get('/moderation')->assertOk();

        // Kotak alasan memakai TextInput bersama (batas border-control), bukan kelas sendiri.
        $sumber = File::get(resource_path('js/pages/Moderation/Queue.tsx'));
        $this->assertMatchesRegularExpression('/<TextInput\s+id=\{`reason-/', $sumber);
        $this->assertStringNotContainsString('border-gray', $sumber);
    }

    public function test_the_queue_does_not_grow_with_the_number_of_reports(): void
    {
        $this->actingAs($this->moderator());
        $this->laporan();
        $this->get('/moderation');
        $sedikit = $this->hitungQuery();

        for ($i = 0; $i < 9; $i++) {
            $this->laporan();
        }

        $this->assertSame($sedikit, $this->hitungQuery());
    }

    /**
     * Yang dibutuhkan moderator untuk memutuskan sampai ke halaman: jalur, teks,
     * pelapor, umur laporan, dan foto (§67). Tanpa parameter yang tampil adalah yang
     * menunggu; `?filter=` kosong berarti semua.
     */
    public function test_the_queue_shows_the_evidence_and_defaults_to_pending(): void
    {
        $menunggu = $this->laporan();
        $menunggu->update(['note' => 'Licin di pos dua.', 'photo_path' => 'laporan/uji.jpg']);
        $this->laporan()->update(['moderation_status' => ModerationStatus::APPROVED->value]);

        $moderator = $this->moderator();

        $this->actingAs($moderator)->get('/moderation')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('filter', ModerationStatus::PENDING->value)
            ->has('reports.data', 1)
            ->where('reports.data.0.jalur', $menunggu->trail->name)
            ->where('reports.data.0.catatan', 'Licin di pos dua.')
            ->where('reports.data.0.pelapor', $menunggu->user->name)
            ->where('reports.data.0.foto.url', route('reports.photo', $menunggu))
            ->has('reports.data.0.dikirim'));

        $this->actingAs($moderator)->get('/moderation?filter=')->assertInertia(fn (AssertableInertia $page) => $page
            ->has('reports.data', 2));
    }

    /**
     * I-1 (ruling W6-6c), bentuk probe P1 peninjau: moderator membuka kartu (membaca isi A),
     * pelapor mengubah laporannya ke isi B lewat "Ubah sebelum dimoderasi" (masih PENDING),
     * moderator menekan Setujui dengan sidik A yang dibawa kartunya. Sidik yang dihitung ulang
     * sekarang adalah sidik B, berbeda dari yang dikirim, jadi tindakan ditolak: B tidak pernah
     * terbit, dan laporannya (kini berisi B) tetap PENDING.
     *
     * Diperbarui untuk ruling W6-6q: setuju membawa status yang dibaca moderator juga, dan
     * kalimat isinya diuji persis (mutan C4 tinjauan task-6-fix1-review selamat karena dulu hanya
     * assertSessionHasErrors('action')).
     */
    public function test_approving_with_a_stale_fingerprint_is_refused_not_silently_published(): void
    {
        $pelapor = User::factory()->create();
        $laporan = TrailConditionReport::create([
            'trail_id' => Trail::factory()->create()->id,
            'user_id' => $pelapor->id,
            'hike_date' => now()->subDays(2)->toDateString(),
            'condition_tags' => ['MUDDY'],
            'note' => 'ISI-YANG-DIBACA-MODERATOR',
            'moderation_status' => ModerationStatus::PENDING->value,
        ]);
        $sidikDibaca = $laporan->sidik();

        $this->actingAs($pelapor)
            ->put(route('reports.update', $laporan), ['condition_tags' => ['DRY'], 'note' => 'ISI-BARU-TIDAK-PERNAH-DIBACA']);

        $this->actingAs($this->moderator())
            ->post(route('moderation.act', $laporan), ['action' => ModerationActionType::APPROVE->value, 'sidik' => $sidikDibaca, 'status_dibaca' => ModerationStatus::PENDING->value])
            ->assertSessionHasErrors(['action' => self::KALIMAT_ISI_BERUBAH]);

        $laporan->refresh();
        $this->assertSame(ModerationStatus::PENDING, $laporan->moderation_status);
        $this->assertSame('ISI-BARU-TIDAK-PERNAH-DIBACA', $laporan->note);
        $this->assertSame(0, ModerationAction::count());
    }

    /**
     * Tolak membawa sidik juga (ruling W6-6c huruf (d), "tambahkan juga test tolak dengan sidik
     * basi"): pelapor yang beriktikad baik tidak boleh ditolak berdasarkan isi yang sudah tidak
     * ada lagi. Diperbarui untuk ruling W6-6q: status yang dibaca ikut dikirim, dan kalimatnya
     * diuji persis.
     */
    public function test_rejecting_with_a_stale_fingerprint_is_also_refused(): void
    {
        $laporan = $this->laporan();
        $sidikLama = $laporan->sidik();
        $laporan->update(['note' => 'Diubah sesudah kartu dimuat moderator.']);

        $this->actingAs($this->moderator())
            ->post(route('moderation.act', $laporan), ['action' => ModerationActionType::REJECT->value, 'sidik' => $sidikLama, 'status_dibaca' => ModerationStatus::PENDING->value])
            ->assertSessionHasErrors(['action' => self::KALIMAT_ISI_BERUBAH]);

        $this->assertSame(ModerationStatus::PENDING, $laporan->fresh()->moderation_status);
    }

    /**
     * Tandai dan hapus tidak memeriksa sidik maupun status yang dibaca (ruling W6-6q, docblock
     * ModerationQueueController::act()): hapus justru dipakai pada laporan yang SUDAH disetujui.
     * Keduanya dikirim di sini tanpa sidik dan tanpa status_dibaca.
     */
    public function test_flagging_and_removing_do_not_require_a_fresh_fingerprint(): void
    {
        $disetujui = TrailConditionReport::factory()->approved()->create();
        $menunggu = $this->laporan();

        $this->actingAs($this->moderator())
            ->post(route('moderation.act', $disetujui), ['action' => ModerationActionType::REMOVE->value])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->moderator())
            ->post(route('moderation.act', $menunggu), ['action' => ModerationActionType::FLAG->value])
            ->assertSessionHasNoErrors();

        $this->assertSame(ModerationStatus::REMOVED, $disetujui->fresh()->moderation_status);
        $this->assertSame(ModerationStatus::FLAGGED, $menunggu->fresh()->moderation_status);
    }

    /**
     * Ruling W6-6q (B-1): penjagaan "harus PENDING" W6-6c dibuang. Laporan yang ditandai untuk
     * ditinjau ulang harus tetap dapat disetujui dan ditolak; sebelumnya ia buntu, pelapor
     * membaca "menunggu moderasi" tanpa ujung (tinjauan task-6-fix1-review bagian 9, B-1).
     */
    public function test_a_flagged_report_can_still_be_approved_or_rejected(): void
    {
        $disetujui = $this->laporan();
        $ditolak = $this->laporan();
        TrailConditionReport::whereKey([$disetujui->id, $ditolak->id])->update(['moderation_status' => ModerationStatus::FLAGGED->value]);

        $this->actingAs($this->moderator())
            ->post(route('moderation.act', $disetujui), ['action' => ModerationActionType::APPROVE->value, 'sidik' => $disetujui->fresh()->sidik(), 'status_dibaca' => ModerationStatus::FLAGGED->value])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->moderator())
            ->post(route('moderation.act', $ditolak), ['action' => ModerationActionType::REJECT->value, 'reason' => 'Foto bukan jalur ini.', 'sidik' => $ditolak->fresh()->sidik(), 'status_dibaca' => ModerationStatus::FLAGGED->value])
            ->assertSessionHasNoErrors();

        $this->assertSame(ModerationStatus::APPROVED, $disetujui->fresh()->moderation_status);
        $this->assertSame(ModerationStatus::REJECTED, $ditolak->fresh()->moderation_status);
    }

    /**
     * Ruling W6-6q: setuju dan tolak boleh dari status apa pun yang dibaca moderator, termasuk
     * APPROVED (mesin status kembali seperti sebelum W6-6c, R-021).
     */
    public function test_an_approved_report_can_be_rejected_when_that_is_the_status_the_moderator_read(): void
    {
        $laporan = TrailConditionReport::factory()->approved()->create();

        $this->actingAs($this->moderator())
            ->post(route('moderation.act', $laporan), ['action' => ModerationActionType::REJECT->value, 'reason' => 'Ternyata foto lama.', 'sidik' => $laporan->sidik(), 'status_dibaca' => ModerationStatus::APPROVED->value])
            ->assertSessionHasNoErrors();

        $this->assertSame(ModerationStatus::REJECTED, $laporan->fresh()->moderation_status);
    }

    /**
     * Ruling W6-6q: moderator A membaca kartu saat laporan masih PENDING, moderator B
     * menyetujuinya lebih dulu, lalu A menolak dengan status yang ia baca. Isinya tidak berubah
     * (sidik sama), jadi yang menolak tindakan A adalah perbandingan status, dengan kalimat
     * status. Baris, ModerationAction, dan AuditLog tetap seperti sesudah keputusan B.
     */
    public function test_a_status_changed_by_another_moderator_refuses_the_action_with_the_status_sentence(): void
    {
        $laporan = $this->laporan();
        $sidikDibaca = $laporan->sidik();
        $moderatorB = $this->moderator();

        $this->actingAs($moderatorB)
            ->post(route('moderation.act', $laporan), ['action' => ModerationActionType::APPROVE->value, 'sidik' => $sidikDibaca, 'status_dibaca' => ModerationStatus::PENDING->value])
            ->assertSessionHasNoErrors();

        $sesudahB = (array) DB::table('trail_condition_reports')->where('id', $laporan->id)->first();
        $tindakan = ModerationAction::count();
        $jejakAudit = AuditLog::count();

        $this->actingAs($this->moderator())
            ->post(route('moderation.act', $laporan), ['action' => ModerationActionType::REJECT->value, 'reason' => 'Tidak relevan.', 'sidik' => $sidikDibaca, 'status_dibaca' => ModerationStatus::PENDING->value])
            ->assertSessionHasErrors(['action' => 'Status laporan ini sudah berubah sejak Anda membukanya, jadi tindakan Anda tidak dijalankan.']);

        $this->assertSame($sesudahB, (array) DB::table('trail_condition_reports')->where('id', $laporan->id)->first());
        $this->assertSame(ModerationStatus::APPROVED, $laporan->fresh()->moderation_status);
        $this->assertSame($moderatorB->id, $laporan->fresh()->moderated_by);
        $this->assertSame($tindakan, ModerationAction::count());
        $this->assertSame($jejakAudit, AuditLog::count());
    }

    /**
     * Ruling W6-6y (m-2, mutan Q8 tinjauan task-6-fix2-review): status DAN isi sama-sama berubah.
     * Pelapor mengubah isinya, lalu moderator lain menandai laporan itu; moderator yang masih
     * memegang kartu lama menekan Setujui. Status diperiksa lebih dulu, jadi jawabannya kalimat
     * status, karena kalimat isi hanya untuk status yang masih sama dengan yang dibaca (W6-6q).
     */
    public function test_when_status_and_content_both_changed_the_status_sentence_wins(): void
    {
        $pelapor = User::factory()->create();
        $laporan = TrailConditionReport::create([
            'trail_id' => Trail::factory()->create()->id,
            'user_id' => $pelapor->id,
            'hike_date' => now()->subDays(2)->toDateString(),
            'condition_tags' => ['MUDDY'],
            'note' => 'ISI-A-DI-KARTU',
            'moderation_status' => ModerationStatus::PENDING->value,
        ]);
        $sidikDibaca = $laporan->sidik();

        $this->actingAs($pelapor)
            ->put(route('reports.update', $laporan), ['condition_tags' => ['DRY'], 'note' => 'ISI-B-SESUDAH-KARTU'])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->moderator())
            ->post(route('moderation.act', $laporan), ['action' => ModerationActionType::FLAG->value])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->moderator())
            ->post(route('moderation.act', $laporan), ['action' => ModerationActionType::APPROVE->value, 'sidik' => $sidikDibaca, 'status_dibaca' => ModerationStatus::PENDING->value])
            ->assertSessionHasErrors(['action' => 'Status laporan ini sudah berubah sejak Anda membukanya, jadi tindakan Anda tidak dijalankan.']);

        $this->assertNotSame($sidikDibaca, $laporan->fresh()->sidik(), 'Isinya memang berubah juga.');
        $this->assertSame(ModerationStatus::FLAGGED, $laporan->fresh()->moderation_status);
    }

    /**
     * Ruling W6-6q: status_dibaca wajib untuk setuju dan tolak, dan harus salah satu nilai
     * ModerationStatus. Tanpanya, atau dengan nilai karangan, tindakan ditolak sebagai galat
     * isian dan laporannya tidak berubah.
     *
     * Diperluas untuk ruling W6-6y (m-2, mutan Q9 tinjauan task-6-fix2-review): tolak TANPA
     * status_dibaca juga galat isian. Tanpa kewajiban itu untuk tolak, permintaannya lolos
     * validasi lalu dijawab kalimat status "sudah berubah" yang tidak benar.
     */
    public function test_approving_or_rejecting_requires_a_known_status_as_read(): void
    {
        $laporan = $this->laporan();

        $this->actingAs($this->moderator())
            ->post(route('moderation.act', $laporan), ['action' => ModerationActionType::APPROVE->value, 'sidik' => $laporan->sidik()])
            ->assertSessionHasErrors('status_dibaca');

        $this->actingAs($this->moderator())
            ->post(route('moderation.act', $laporan), ['action' => ModerationActionType::REJECT->value, 'reason' => 'Tidak relevan.', 'sidik' => $laporan->sidik()])
            ->assertSessionHasErrors('status_dibaca')
            ->assertSessionDoesntHaveErrors('action');

        $this->actingAs($this->moderator())
            ->post(route('moderation.act', $laporan), ['action' => ModerationActionType::REJECT->value, 'sidik' => $laporan->sidik(), 'status_dibaca' => 'TERBIT'])
            ->assertSessionHasErrors('status_dibaca');

        $this->assertSame(ModerationStatus::PENDING, $laporan->fresh()->moderation_status);
        $this->assertSame(0, ModerationAction::count());
    }

    /**
     * Kontrak antarkomponen W6-6q (tinjauan bagian 2.4, C10 dan C11): kartu antrean membawa
     * sidik dan status yang dibaca moderator, dan halaman antrean mengirim keduanya balik. Bila
     * salah satu putus, setiap setuju dan tolak gagal dengan galat isian.
     */
    public function test_the_queue_card_carries_and_sends_back_what_the_moderator_read(): void
    {
        $laporan = $this->laporan();

        $this->actingAs($this->moderator())->get('/moderation')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('reports.data.0.id', $laporan->id)
            ->where('reports.data.0.sidik', $laporan->sidik())
            ->where('reports.data.0.status', ModerationStatus::PENDING->value));

        $this->assertStringContainsString(
            "{ action: t.value, reason: alasan[id] ?? '', sidik: laporan.sidik, status_dibaca: laporan.status }",
            File::get(resource_path('js/pages/Moderation/Queue.tsx')),
        );
    }

    /**
     * R-c2 (tinjauan task-6-fix1-review bagian 2.3): penolakan tiba di antrean. Sesudah redirect,
     * halaman antrean membawa errors.action, dan kartu yang sama kini berisi isi baru dengan
     * sidik baru; tanpa Alert di halaman, isi baru menggantikan isi lama tanpa tanda.
     */
    public function test_a_refused_action_lands_on_the_queue_with_the_sentence_and_the_new_content(): void
    {
        $laporan = $this->laporan();
        $sidikLama = $laporan->sidik();
        $laporan->update(['note' => 'ISI-BARU-SESUDAH-KARTU-DIMUAT']);

        $this->actingAs($this->moderator())
            ->from(route('moderation.queue'))
            ->followingRedirects()
            ->post(route('moderation.act', $laporan), ['action' => ModerationActionType::APPROVE->value, 'sidik' => $sidikLama, 'status_dibaca' => ModerationStatus::PENDING->value])
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Moderation/Queue')
                ->where('errors.action', self::KALIMAT_ISI_BERUBAH)
                ->where('reports.data.0.id', $laporan->id)
                ->where('reports.data.0.catatan', 'ISI-BARU-SESUDAH-KARTU-DIMUAT')
                ->where('reports.data.0.sidik', $laporan->fresh()->sidik()));
    }

    /**
     * R-c2: Queue.tsx merender errors.action dengan Alert. Bila kartu laporan yang ditindak masih
     * ada di daftar, Alert di kartu itu (paling atas, sebelum isinya); bila tidak, di puncak daftar
     * dengan jalur, gunung, dan tanggal pendakian dari salinan kartu sebelum kunjungan.
     *
     * Diubah untuk ruling W6-6w dan W6-6x (tinjauan task-6-fix2-review, I-1 dan m-1): pemindahan
     * fokus ke Alert kini satu cabang di onFinish dan diuji
     * test_every_queue_action_outcome_moves_focus_to_a_fixed_target; syarat "tindakan di halaman
     * yang sedang terpasang" diuji test_a_refusal_is_shown_only_for_an_action_taken_on_the_mounted_page.
     * Baris puncak kini mensyaratkan terakhir, jadi judulnya tidak lagi bercabang undefined.
     */
    public function test_the_queue_renders_a_refusal_in_the_card_or_at_the_top_and_moves_focus_to_it(): void
    {
        $sumber = File::get(resource_path('js/pages/Moderation/Queue.tsx'));

        $this->assertStringContainsString('const penolakanDiKartu = penolakan !== null && terakhir !== null && reports.data.some((r) => r.id === terakhir.id);', $sumber);

        $kotak = substr($sumber, strpos($sumber, 'const kotakPenolakan = (judul?: string) => ('), 400);
        $this->assertStringContainsString("<div id={ID_PENOLAKAN} tabIndex={-1} className={kelas('mb-4 rounded-kartu', fokusTerlihat)}>", $kotak);
        $this->assertMatchesRegularExpression('/<Alert variant="warning" title=\{judul\} wilayahStatus=\{false\}>\s*\{penolakan\}\s*<\/Alert>/', $kotak);

        $this->assertMatchesRegularExpression('/<Card>\s*\{penolakanDiKartu && terakhir\?\.id === r\.id \? kotakPenolakan\(\) : null\}/', $sumber);
        $this->assertStringContainsString('{penolakan !== null && terakhir !== null && !penolakanDiKartu ? kotakPenolakan(`Laporan ${terakhir.jalur}, ${terakhir.gunung}, pendakian ${terakhir.tanggalPendakian}`) : null}', $sumber);
        $this->assertStringContainsString('setTerakhir(laporan);', $sumber);
    }

    /**
     * Ruling W6-6x (m-1): errors.action dibaca dari props halaman, yang dipulihkan Inertia dari
     * riwayat peramban saat Kembali, sedangkan terakhir adalah keadaan komponen yang mulai lagi dari
     * null. Penolakan hanya tampil untuk tindakan yang diambil di halaman yang sedang terpasang,
     * supaya "laporan ini" tidak pernah tampil tanpa nama di atas kartu lain.
     */
    public function test_a_refusal_is_shown_only_for_an_action_taken_on_the_mounted_page(): void
    {
        $this->assertStringContainsString(
            'const penolakan = terakhir !== null ? (errors.action ?? null) : null;',
            File::get(resource_path('js/pages/Moderation/Queue.tsx')),
        );
    }

    /**
     * Ruling W6-6w (I-1 tinjauan task-6-fix2-review): semua tombol tindakan disabled selama
     * kunjungan, jadi fokus lepas dari tombol yang ditekan. Satu fokusKe di onFinish memilih
     * sasaran tetap untuk setiap hasil:
     * - berhasil: wilayah kabar #kabar-antrean (role=status, selalu terpasang, fokusTerlihat, pola
     *   #kabar-ringkasan), juga saat kartunya hilang dari filter Menunggu; kabar berhasil tidak lagi
     *   Alert yang dipasang bersama isinya;
     * - penolakan errors.action: Alert penolakan (R-c2);
     * - galat alasan: isian alasan kartu itu, yang membawa aria-invalid dan aria-describedby ke
     *   galatnya, jadi galat dibacakan saat fokus tiba;
     * - selain itu, termasuk galat jaringan (kartu dan tombolnya masih terpasang): tombol yang
     *   ditekan, lewat id per tombol.
     * onError hanya mencatat hasilnya dan tidak memindah fokus sendiri.
     */
    public function test_every_queue_action_outcome_moves_focus_to_a_fixed_target(): void
    {
        $sumber = File::get(resource_path('js/pages/Moderation/Queue.tsx'));
        $bagian = function (string $awal, string $akhir) use ($sumber): string {
            $mulai = strpos($sumber, $awal);
            $this->assertNotFalse($mulai, $awal);
            $selesai = strpos($sumber, $akhir, $mulai + strlen($awal));
            $this->assertNotFalse($selesai, $akhir);

            return substr($sumber, $mulai, $selesai - $mulai);
        };

        $this->assertMatchesRegularExpression(
            '/<p id="kabar-antrean" role="status" tabIndex=\{-1\} className=\{kelas\(flash\.status \? \'[^\']+\' : undefined, fokusTerlihat\)\}>\s*\{flash\.status \?\? \'\'\}\s*<\/p>/',
            $sumber,
        );
        $this->assertStringNotContainsString('{flash.status ? <Alert', $sumber);

        $this->assertStringContainsString('id={`tindakan-${r.id}-${t.value}`}', $sumber);

        $tindak = $bagian('const tindak = ', 'return (');
        $this->assertMatchesRegularExpression("/onSuccess: \(\) => \{\s*hasil = 'berhasil';/", $tindak);
        $this->assertStringContainsString("hasil = galat.action ? 'aksi' : galat.reason ? 'alasan' : null;", $tindak);
        $this->assertStringNotContainsString('fokusKe', $bagian('onError: (galat) => {', '},'));
        $this->assertStringContainsString(
            "fokusKe(hasil === 'berhasil' ? 'kabar-antrean' : hasil === 'aksi' ? ID_PENOLAKAN : hasil === 'alasan' ? `reason-\${id}` : `tindakan-\${id}-\${t.value}`);",
            $bagian('onFinish: () => {', '},'),
        );

        $this->assertStringContainsString('aria-describedby={terakhir?.id === r.id && errors.reason ? `reason-${r.id}-hint reason-${r.id}-error` : `reason-${r.id}-hint`}', $sumber);
        $this->assertStringContainsString('aria-invalid={terakhir?.id === r.id && errors.reason ? true : undefined}', $sumber);
    }

    /**
     * Komposisi sidik (ruling W6-6c huruf b, tinjauan bagian 2.4, C6 sampai C8): tag dan segmen
     * masuk sidik, sedangkan urutan tag tidak, karena himpunan kondisi yang sama tidak mengubah
     * isi yang dibaca moderator.
     */
    public function test_the_fingerprint_covers_tags_and_segment_but_not_tag_order(): void
    {
        $laporan = $this->laporan();
        $segmen = TrailSegment::create(['trail_id' => $laporan->trail_id, 'sequence' => 1, 'name' => 'Pos 1 ke Pos 2']);
        $laporan->update(['condition_tags' => ['MUDDY', 'SLIPPERY']]);
        $awal = $laporan->sidik();

        $laporan->condition_tags = ['SLIPPERY', 'MUDDY'];
        $this->assertSame($awal, $laporan->sidik(), 'Urutan tag tidak mengubah sidik.');

        $laporan->condition_tags = ['MUDDY'];
        $this->assertNotSame($awal, $laporan->sidik(), 'Tag masuk sidik.');

        $laporan->condition_tags = ['MUDDY', 'SLIPPERY'];
        $laporan->trail_segment_id = $segmen->id;
        $this->assertNotSame($awal, $laporan->sidik(), 'Segmen masuk sidik.');
    }

    /**
     * Tindakan di luar daftar ditolak sebagai galat isian, dan laporannya tidak
     * berubah (dulu ModerationAction::from() meledak menjadi halaman 500).
     */
    public function test_an_unknown_action_is_refused_and_changes_nothing(): void
    {
        $laporan = $this->laporan();

        $this->actingAs($this->moderator())
            ->post(route('moderation.act', $laporan), ['action' => 'PUBLISH'])
            ->assertSessionHasErrors('action');

        $this->assertSame(ModerationStatus::PENDING, $laporan->fresh()->moderation_status);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'report.moderated']);
    }

    private function hitungQuery(): int
    {
        $n = 0;
        DB::listen(function () use (&$n) {
            $n++;
        });

        $this->get('/moderation');

        DB::getEventDispatcher()->forget('Illuminate\Database\Events\QueryExecuted');

        return $n;
    }

    private function riwayat(TrailConditionReport $laporan): HikingHistory
    {
        $trip = TripPlan::create([
            'user_id' => $laporan->user_id,
            'trail_id' => $laporan->trail_id,
            'name' => 'Trip uji',
            'planned_date' => now()->subWeek()->toDateString(),
            'trip_type' => 'CAMPING',
            'status' => 'COMPLETED',
        ]);

        return HikingHistory::create([
            'user_id' => $laporan->user_id,
            'trip_plan_id' => $trip->id,
            'trail_id' => $laporan->trail_id,
            'trail_condition_report_id' => $laporan->id,
            'completed_at' => now()->subDays(3),
            'trip_type' => 'CAMPING',
            'completion_state' => 'COMPLETED',
            'preparation_completion_percent' => 100,
        ]);
    }

    private function laporan(): TrailConditionReport
    {
        return TrailConditionReport::create([
            'trail_id' => Trail::factory()->create()->id,
            'user_id' => User::factory()->create()->id,
            'hike_date' => now()->subDays(4)->toDateString(),
            'condition_tags' => ['MUDDY'],
            'moderation_status' => ModerationStatus::PENDING->value,
        ]);
    }

    private function moderator(): User
    {
        return User::factory()->create(['role' => UserRole::MODERATOR->value]);
    }
}
