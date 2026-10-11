<?php

namespace Tests\Feature;

use App\Enums\CompletionState;
use App\Enums\ConditionTag;
use App\Enums\HikingSessionStatus;
use App\Enums\ModerationStatus;
use App\Enums\TripStatus;
use App\Enums\UserRole;
use App\Http\Requests\UpdateConditionReportRequest;
use App\Models\HikingHistory;
use App\Models\ModerationAction;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\TrailSegment;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Laporan kondisi dari ringkasan pendakian (spec v3 bagian 5.3 dan 6.13, prototype 12): formulir
 * laporan pindah ke ringkasan, menyebut apa yang terbit dan tidak, dan laporan yang belum
 * dimoderasi dapat diubah pemiliknya. Laporan tetap masuk moderasi dan tidak pernah menjadi
 * status resmi (R-010).
 */
class HikeSummaryReportTest extends TestCase
{
    use RefreshDatabase;

    /** Ruling W6-6r huruf (a), ditulis persis supaya mutasi kalimat di controller merah. */
    private const KALIMAT_SUDAH_DITINJAU = 'Moderator sudah mulai meninjau laporan ini, jadi laporan tidak dapat diubah lagi. Perubahan Anda tidak tersimpan.';

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-09-20 08:00:00', 'UTC'));
    }

    /**
     * @return array{0: User, 1: TripPlan, 2: TrailSegment}
     */
    private function selesai(): array
    {
        $user = User::factory()->create();
        $trail = Trail::factory()->create(['name' => 'Gede via Cibodas']);
        $segmen = TrailSegment::create(['trail_id' => $trail->id, 'sequence' => 1, 'name' => 'Air Panas ke Kandang Badak']);
        $trip = TripPlan::factory()->for($user)->for($trail)->create(['status' => TripStatus::COMPLETED->value]);
        $trip->hikingSession()->create(['user_id' => $user->id, 'status' => HikingSessionStatus::COMPLETED->value, 'started_at' => now()->subDays(2), 'ended_at' => now()->subDays(2)->addHours(9)]);

        HikingHistory::create([
            'user_id' => $user->id,
            'trip_plan_id' => $trip->id,
            'trail_id' => $trail->id,
            'trip_type' => $trip->trip_type->value,
            'completion_state' => CompletionState::COMPLETED->value,
            'completed_at' => Carbon::parse('2026-09-18 09:00:00', 'UTC'),
        ]);

        return [$user, $trip->fresh(), $segmen];
    }

    public function test_the_summary_offers_the_report_form_and_says_what_is_published(): void
    {
        [$user, $trip, $segmen] = $this->selesai();

        $this->actingAs($user)->get(route('history.summary', $trip))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('laporan.keadaan', 'belum')
                ->where('laporan.formulir.tanggal', '2026-09-18')
                ->where('laporan.formulir.segmen', [['value' => $segmen->id, 'label' => '1. Air Panas ke Kandang Badak']])
                ->has('laporan.formulir.tags', count(ConditionTag::cases()))
                ->where('laporan.formulir.kirim', route('reports.store'))
                ->where('laporan.privasi', 'Yang terbit: tanggal, kondisi, segmen, catatan, foto, dan nama tampilan Anda. Tidak terbit: jejak, titik awal, dan titik akhir Anda.'));

        // Kabar GPS foto tinggal di isian foto, tempat keputusannya diambil (EXIF dibuang server, PRD §82).
        $this->assertStringContainsString('Lokasi GPS di dalam foto dihapus sebelum foto disimpan.', File::get(resource_path('js/components/community/LaporanPendakian.tsx')));
    }

    public function test_a_report_sent_from_the_summary_returns_to_it_and_waits_for_moderation(): void
    {
        [$user, $trip, $segmen] = $this->selesai();

        $this->actingAs($user)->post(route('reports.store'), [
            'trail_id' => $trip->trail_id,
            'trip_plan_id' => $trip->id,
            'trail_segment_id' => $segmen->id,
            'hike_date' => '2026-09-18',
            'condition_tags' => [ConditionTag::SLIPPERY->value, ConditionTag::CROWDED->value],
            'note' => 'Batu di aliran air panas licin.',
        ])->assertRedirect(route('history.summary', $trip));

        $laporan = TrailConditionReport::sole();

        $this->assertSame($laporan->id, $trip->history->fresh()->trail_condition_report_id);
        $this->assertSame(ModerationStatus::PENDING, $laporan->moderation_status);

        $this->actingAs($user)->get(route('history.summary', $trip))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('flash.status', 'Laporan terkirim dan menunggu moderasi sebelum tampil untuk pengguna lain.')
                ->where('laporan.keadaan', 'menunggu')
                ->where('laporan.formulir.ubah', route('reports.update', $laporan))
                ->where('laporan.formulir.awal', ['condition_tags' => ['SLIPPERY', 'CROWDED'], 'trail_segment_id' => $segmen->id, 'note' => 'Batu di aliran air panas licin.']));

        // Kabar dan penolakan dibacakan dari wilayah yang terpasang sejak halaman dimuat, bukan
        // Alert yang dipasang bersama isinya (DESIGN.md, umpan balik aksi; pola ReadinessDashboardTest).
        $halaman = File::get(resource_path('js/pages/Community/HikeSummary.tsx'));
        $this->assertStringContainsString("{flash.status ?? ''}", $halaman);
        $this->assertStringContainsString("{peringatanFoto ?? ''}", $halaman);
        $this->assertStringNotContainsString('{flash.status ? <Alert', $halaman);
        $this->assertStringContainsString("{galat.form ?? ''}", File::get(resource_path('js/components/community/LaporanPendakian.tsx')));
    }

    public function test_a_pending_report_can_be_changed_before_moderation(): void
    {
        [$user, $trip] = $this->selesai();
        $laporan = $this->laporanTertaut($user, $trip, ModerationStatus::PENDING);

        $this->actingAs($user)
            ->put(route('reports.update', $laporan), ['condition_tags' => [ConditionTag::DRY->value], 'trail_segment_id' => null, 'note' => 'Kering sepanjang jalur.'])
            ->assertRedirect(route('history.summary', $trip))
            ->assertSessionHas('status', 'Laporan diperbarui dan tetap menunggu moderasi.');

        $this->assertSame([ConditionTag::DRY->value], $laporan->fresh()->condition_tags);
        $this->assertSame('Kering sepanjang jalur.', $laporan->fresh()->note);
    }

    /**
     * Ruling W6-6m (M-8): mode ubah punya jalan batal yang mengembalikan isian ke isi
     * tersimpan dan memindah fokus ke "Ubah sebelum dimoderasi", bukan hanya "Simpan perubahan
     * laporan" sebagai satu-satunya jalan keluar.
     */
    public function test_the_edit_mode_has_a_cancel_button_that_restores_the_stored_values(): void
    {
        $sumber = File::get(resource_path('js/components/community/LaporanPendakian.tsx'));

        $this->assertStringContainsString('Batal ubah', $sumber);
        $this->assertStringContainsString('onClick={batalUbah}', $sumber);

        // Badan fungsi batalUbah() harus mengembalikan isian ke formulir.awal (isi tersimpan,
        // bukan string kosong) dan memindah fokus ke "Ubah sebelum dimoderasi"; diperiksa hanya
        // di DALAM badan fungsinya (dibatasi sampai deklarasi const berikutnya), supaya
        // kemunculan di tempat lain (nilai awal useForm, penanganan kirim) tidak ikut membuat
        // mutasi yang mengosongkan badan fungsi ini selamat palsu.
        $mulai = strpos($sumber, 'const batalUbah = () => {');
        $akhir = strpos($sumber, 'const kirim = (e: FormEvent)');
        $this->assertNotFalse($mulai);
        $this->assertNotFalse($akhir);
        $badanBatalUbah = substr($sumber, $mulai, $akhir - $mulai);

        $this->assertStringContainsString('formulir.awal?.condition_tags', $badanBatalUbah);
        $this->assertStringContainsString("fokusKe('laporan-ubah-tombol')", $badanBatalUbah);
    }

    /**
     * Ruling W6-6e (I-3): setiap aksi laporan di halaman yang sama memindah fokus dengan sengaja,
     * satu asersi per penangan, di callback kunjungan (komponen tidak dipasang ulang):
     * - "Ubah sebelum dimoderasi" ke legend formulir ubah;
     * - simpan perubahan (PUT) ke kalimat keadaan bila berhasil, selain itu ke tombol kirim, atau
     *   ke kalimat penolakan bila formulirnya tertutup (ruling W6-6r huruf c);
     * - kirim laporan (POST) ke kalimat keadaan bila berhasil, selain itu ke tombol kirim.
     * Galat jaringan tidak memanggil onSuccess, jadi `berhasil` tetap false dan onFinish, yang
     * selalu terpanggil, mengembalikan fokus ke tombol kirim yang sudah aktif lagi. Kedua sasaran
     * membawa fokusTerlihat, dan kalimat keadaan tidak lagi berperan role=status (kabar halaman
     * sudah mengumumkannya).
     */
    public function test_each_report_action_moves_focus_deliberately(): void
    {
        $sumber = File::get(resource_path('js/components/community/LaporanPendakian.tsx'));
        $bagian = function (string $awal, string $akhir) use ($sumber): string {
            $mulai = strpos($sumber, $awal);
            $this->assertNotFalse($mulai, $awal);
            $selesai = strpos($sumber, $akhir, $mulai + strlen($awal));
            $this->assertNotFalse($selesai, $akhir);

            return substr($sumber, $mulai, $selesai - $mulai);
        };

        $this->assertStringContainsString("fokusKe('laporan-ubah-legend');", $bagian('const bukaUbah = () => {', 'const batalUbah'));

        $simpan = $bagian('form.put(formulir.ubah, {', 'return;');
        $this->assertStringContainsString('berhasil = true;', $bagian('onSuccess: () => {', '},'));
        $this->assertStringContainsString("onFinish: () => (berhasil ? fokusKe('laporan-keadaan') : fokusPertama(['laporan-kirim-tombol', 'laporan-penolakan'])),", $simpan);

        $kirimBaru = $bagian('form.post(formulir.kirim, {', '});');
        $this->assertStringContainsString("onFinish: () => fokusKe(berhasil ? 'laporan-keadaan' : 'laporan-kirim-tombol'),", $kirimBaru);
        $this->assertStringNotContainsString('onError', $kirimBaru);

        $this->assertStringContainsString("<p id=\"laporan-keadaan\" tabIndex={-1} className={kelas('rounded-kecil text-isi font-bold text-community-900', fokusTerlihat)}>", $sumber);
        $this->assertStringContainsString("<legend id=\"laporan-ubah-legend\" tabIndex={-1} className={kelas('rounded-kecil text-isi font-bold text-primary', fokusTerlihat)}>", $sumber);
        $this->assertStringContainsString('<Button id="laporan-kirim-tombol"', preg_replace('/\s+/', ' ', $sumber));
        $this->assertDoesNotMatchRegularExpression('/id="laporan-keadaan"[^>]*role="status"/', $sumber);
    }

    /**
     * Ruling W6-6r huruf (c): formulir ubah hanya terbuka selama server masih menawarkan tautan
     * ubah. Simpan yang ditolak mengembalikan tautan ubah null, jadi formulirnya tertutup dan
     * kotak keadaan menampilkan kalimat penolakan (errors.form) sebagai Alert yang menjadi sasaran
     * fokus; useFokusPertamaSesudahRender memilih sasaran yang masih terpasang SESUDAH render.
     */
    public function test_a_refused_change_closes_the_edit_form_and_shows_the_sentence_where_it_was(): void
    {
        $sumber = File::get(resource_path('js/components/community/LaporanPendakian.tsx'));

        $this->assertStringContainsString('const modeUbah = mengubah && formulir.ubah !== null;', $sumber);
        $this->assertStringContainsString("{laporan.keadaan !== 'belum' && !modeUbah ? (", $sumber);

        $kotak = substr($sumber, strpos($sumber, "{laporan.keadaan !== 'belum' && !modeUbah ? ("), 1200);
        $this->assertStringContainsString("<div id=\"laporan-penolakan\" tabIndex={-1} className={kelas('mb-3 rounded-kartu', fokusTerlihat)}>", $kotak);
        $this->assertMatchesRegularExpression('/<Alert variant="danger" wilayahStatus=\{false\}>\s*\{galat\.form\}\s*<\/Alert>/', $kotak);
        $this->assertLessThan(strpos($kotak, 'id="laporan-keadaan"'), strpos($kotak, 'id="laporan-penolakan"'));

        $hook = substr($sumber, strpos($sumber, 'function useFokusPertamaSesudahRender() {'), 500);
        $this->assertStringContainsString('ids.map((id) => document.getElementById(id)).find((unsur) => unsur !== null)?.focus();', $hook);
    }

    /**
     * Sesudah dimoderasi laporan tidak dapat diubah lagi: yang sudah terbit dibaca pendaki lain
     * apa adanya, dan yang ditolak menyebut alasannya.
     *
     * Diubah untuk ruling W6-6r (R-c1): penolakannya kini kalimat di formulir, bukan 403. 403
     * HTML tanpa X-Inertia membuka dialog galat bawaan Inertia yang menyebut "tidak punya akses"
     * untuk laporan milik pelapor sendiri (tinjauan task-6-fix1-review bagian 2.2). Status
     * diperiksa UPDATE bersyarat di ConditionReportController::update(), jadi yang dijaga test ini
     * sekarang kalimatnya, basis data yang tidak berubah sama sekali (termasuk updated_at), dan
     * formulir ubah yang tidak lagi ditawarkan.
     */
    public function test_a_moderated_report_can_no_longer_be_changed(): void
    {
        [$user, $trip] = $this->selesai();
        $laporan = $this->laporanTertaut($user, $trip, ModerationStatus::APPROVED);
        $sebelum = (array) DB::table('trail_condition_reports')->where('id', $laporan->id)->first();
        $this->travel(5)->minutes();

        $this->actingAs($user)
            ->from(route('history.summary', $trip))
            ->put(route('reports.update', $laporan), ['condition_tags' => [ConditionTag::DRY->value], 'note' => 'Isi yang tidak boleh terbit.'])
            ->assertRedirect(route('history.summary', $trip))
            ->assertSessionHasErrors(['form' => self::KALIMAT_SUDAH_DITINJAU])
            ->assertSessionMissing('status');

        $this->assertSame($sebelum, (array) DB::table('trail_condition_reports')->where('id', $laporan->id)->first());

        $this->actingAs($user)->get(route('history.summary', $trip))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('laporan.keadaan', 'terbit')->where('laporan.formulir.ubah', null));
    }

    /**
     * Ruling W6-6r (R-c1): laporan FLAGGED tampil ke pelapor sebagai "menunggu moderasi", jadi
     * kalimat penolakannya tidak boleh menyebut "sudah dimoderasi" (ruling W6-6c2). Kalimatnya
     * sama dengan APPROVED, bukan 403, dan basis data tidak berubah.
     */
    public function test_a_flagged_report_changed_by_its_owner_is_refused_with_the_sentence(): void
    {
        [$user, $trip] = $this->selesai();
        $laporan = $this->laporanTertaut($user, $trip, ModerationStatus::FLAGGED);
        $sebelum = (array) DB::table('trail_condition_reports')->where('id', $laporan->id)->first();

        $this->actingAs($user)
            ->from(route('history.summary', $trip))
            ->put(route('reports.update', $laporan), ['condition_tags' => [ConditionTag::DRY->value]])
            ->assertRedirect(route('history.summary', $trip))
            ->assertSessionHasErrors(['form' => self::KALIMAT_SUDAH_DITINJAU]);

        $this->assertSame($sebelum, (array) DB::table('trail_condition_reports')->where('id', $laporan->id)->first());
    }

    /**
     * Ruling W6-6r (R-c1), bentuk probe P1-balapan tinjauan bagian 2.2: pelapor lolos otorisasi
     * dan validasi saat laporan masih PENDING, lalu moderator menyetujui isi A di antara validasi
     * dan tulisan pelapor. Persetujuan disisipkan lewat afterResolving, yang berjalan sesudah
     * validateResolved() milik FormRequestServiceProvider. UPDATE bersyarat mengenai 0 baris:
     * isi B tidak pernah terbit, status tetap keputusan moderator, dan pelapor menerima kalimat
     * penolakan, bukan "Laporan diperbarui dan tetap menunggu moderasi.".
     */
    public function test_a_change_racing_a_moderators_approval_never_publishes_unread_content(): void
    {
        [$user, $trip] = $this->selesai();
        $laporan = $this->laporanTertaut($user, $trip, ModerationStatus::PENDING);
        $laporan->update(['note' => 'ISI-A-DIBACA-MODERATOR']);
        $moderator = User::factory()->create(['role' => UserRole::MODERATOR->value]);

        $this->app->afterResolving(UpdateConditionReportRequest::class, function () use ($laporan, $moderator) {
            TrailConditionReport::whereKey($laporan->id)->update([
                'moderation_status' => ModerationStatus::APPROVED->value,
                'moderated_by' => $moderator->id,
                'moderated_at' => now(),
            ]);
        });

        $this->actingAs($user)
            ->from(route('history.summary', $trip))
            ->put(route('reports.update', $laporan), ['condition_tags' => [ConditionTag::DRY->value], 'note' => 'ISI-B-TIDAK-PERNAH-DIBACA'])
            ->assertRedirect(route('history.summary', $trip))
            ->assertSessionHasErrors(['form' => self::KALIMAT_SUDAH_DITINJAU])
            ->assertSessionMissing('status');

        $terbit = $laporan->fresh();
        $this->assertSame(ModerationStatus::APPROVED, $terbit->moderation_status);
        $this->assertSame($moderator->id, $terbit->moderated_by);
        $this->assertSame('ISI-A-DIBACA-MODERATOR', $terbit->note);
        $this->assertSame([ConditionTag::SLIPPERY->value], $terbit->condition_tags);
        $this->assertSame(0, TrailConditionReport::visibleToPublic()->where('note', 'ISI-B-TIDAK-PERNAH-DIBACA')->count());
    }

    /**
     * Ruling W6-6r huruf (a): Builder::update() tidak menerapkan cast model, jadi isian yang
     * ditulis UPDATE bersyarat dibandingkan langsung, byte demi byte di basis data, dengan
     * laporan kembar yang diubah lewat Eloquent update() seperti sebelum ruling ini. Waktu
     * digeser sesudah keduanya dibuat, supaya updated_at yang tidak ikut ditulis kelihatan.
     */
    public function test_a_conditional_change_stores_exactly_what_an_eloquent_update_stored(): void
    {
        [$user, $trip, $segmen] = $this->selesai();
        $laporan = $this->laporanTertaut($user, $trip, ModerationStatus::PENDING);
        $kembar = TrailConditionReport::factory()->for($trip->trail)->create([
            'user_id' => $user->id,
            'moderation_status' => ModerationStatus::PENDING->value,
            'condition_tags' => [ConditionTag::SLIPPERY->value],
            'hike_date' => '2026-09-18',
        ]);
        $isian = ['condition_tags' => [ConditionTag::DRY->value, ConditionTag::CROWDED->value], 'trail_segment_id' => $segmen->id, 'note' => 'Kering, ramai di Kandang Badak.'];
        $this->travel(7)->minutes();

        $this->actingAs($user)->put(route('reports.update', $laporan), $isian)->assertSessionHasNoErrors();
        $kembar->update($isian);

        $kolom = ['condition_tags', 'trail_segment_id', 'note', 'updated_at'];
        $baris = (array) DB::table('trail_condition_reports')->where('id', $laporan->id)->first($kolom);
        $baca = $laporan->fresh();

        $this->assertSame((array) DB::table('trail_condition_reports')->where('id', $kembar->id)->first($kolom), $baris);
        $this->assertSame([ConditionTag::DRY->value, ConditionTag::CROWDED->value], $baca->condition_tags);
        $this->assertSame($segmen->id, $baca->trail_segment_id);
        $this->assertSame('Kering, ramai di Kandang Badak.', $baca->note);
        $this->assertTrue($baca->updated_at->equalTo(now()));
        $this->assertFalse($baca->updated_at->equalTo($baca->created_at));
    }

    public function test_another_hiker_cannot_change_a_report(): void
    {
        [$user, $trip] = $this->selesai();
        $laporan = $this->laporanTertaut($user, $trip, ModerationStatus::PENDING);

        $this->actingAs(User::factory()->create())
            ->put(route('reports.update', $laporan), ['condition_tags' => [ConditionTag::DRY->value]])
            ->assertForbidden();
    }

    /**
     * Ruling W6-6r huruf (b): kepemilikan diperiksa Policy di authorize(), status diperiksa UPDATE
     * bersyarat. Pengguna lain tetap 403 untuk laporan yang sudah dimoderasi juga, bukan kalimat
     * pelapor yang menyebut isi laporan orang lain.
     */
    public function test_another_hiker_cannot_change_a_moderated_report_either(): void
    {
        [$user, $trip] = $this->selesai();
        $laporan = $this->laporanTertaut($user, $trip, ModerationStatus::APPROVED);

        $this->actingAs(User::factory()->create())
            ->put(route('reports.update', $laporan), ['condition_tags' => [ConditionTag::DRY->value]])
            ->assertForbidden();
    }

    public function test_a_changed_report_is_validated_like_a_new_one(): void
    {
        [$user, $trip] = $this->selesai();
        $laporan = $this->laporanTertaut($user, $trip, ModerationStatus::PENDING);

        $this->actingAs($user)
            ->put(route('reports.update', $laporan), ['condition_tags' => [], 'note' => str_repeat('a', 1001)])
            ->assertSessionHasErrors(['condition_tags', 'note']);
    }

    /**
     * Ruling W6-6j (M03): segmen yang dipilih harus milik jalur laporan itu sendiri, bukan
     * segmen jalur lain yang kebetulan ada di basis data.
     */
    public function test_a_changed_reports_segment_must_belong_to_its_trail(): void
    {
        [$user, $trip] = $this->selesai();
        $laporan = $this->laporanTertaut($user, $trip, ModerationStatus::PENDING);
        $segmenJalurLain = TrailSegment::create(['trail_id' => Trail::factory()->create()->id, 'sequence' => 1, 'name' => 'Segmen Jalur Lain']);

        $this->actingAs($user)
            ->put(route('reports.update', $laporan), ['condition_tags' => [ConditionTag::DRY->value], 'trail_segment_id' => $segmenJalurLain->id])
            ->assertSessionHasErrors('trail_segment_id');
    }

    /**
     * Ruling W6-6j (M04): tag kondisi harus salah satu nilai enum yang dikenal.
     */
    public function test_a_changed_reports_condition_tags_must_be_known_values(): void
    {
        [$user, $trip] = $this->selesai();
        $laporan = $this->laporanTertaut($user, $trip, ModerationStatus::PENDING);

        $this->actingAs($user)
            ->put(route('reports.update', $laporan), ['condition_tags' => ['BUKAN_TAG_YANG_DIKENAL']])
            ->assertSessionHasErrors('condition_tags.0');
    }

    /**
     * Ruling W6-6j (M21): laporan yang ditolak menyebut keadaannya sendiri ("ditolak"), bukan
     * ikut tertangkap cabang "menunggu" atau "terbit".
     */
    public function test_a_rejected_report_is_named_with_its_reason(): void
    {
        [$user, $trip] = $this->selesai();
        $laporan = $this->laporanTertaut($user, $trip, ModerationStatus::REJECTED);
        ModerationAction::create([
            'moderator_id' => User::factory()->create()->id,
            'moderatable_type' => $laporan->getMorphClass(),
            'moderatable_id' => $laporan->id,
            'action' => 'REJECT',
            'reason' => 'Foto tidak jelas.',
        ]);

        $this->actingAs($user)->get(route('history.summary', $trip))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('laporan.keadaan', 'ditolak')
                ->where('laporan.alasanTolak', 'Foto tidak jelas.')
                ->where('laporan.formulir.ubah', null));
    }

    /**
     * Ruling W6-6j (M23, M31): laporan yang ditandai (FLAGGED) tetap menunggu di mata pelapor,
     * tetapi tidak dapat diubah lagi lewat "Ubah sebelum dimoderasi" (hanya PENDING yang bisa;
     * tautan ubah tidak boleh ikut terbuka untuk FLAGGED).
     */
    public function test_a_flagged_report_waits_but_cannot_be_edited(): void
    {
        [$user, $trip] = $this->selesai();
        $this->laporanTertaut($user, $trip, ModerationStatus::FLAGGED);

        $this->actingAs($user)->get(route('history.summary', $trip))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('laporan.keadaan', 'menunggu')
                ->where('laporan.formulir.ubah', null));
    }

    /**
     * Ruling W6-6j (M24): laporan yang tidak tertaut ke riwayat mana pun (trip_plan_id tidak
     * pernah dikirim, atau tidak pernah tersambung) kembali ke /history, bukan ke ringkasan
     * trip yang tidak ada.
     */
    public function test_updating_an_unlinked_report_returns_to_history_not_the_summary(): void
    {
        $user = User::factory()->create();
        $laporan = TrailConditionReport::factory()->create([
            'user_id' => $user->id,
            'moderation_status' => ModerationStatus::PENDING->value,
        ]);

        $this->actingAs($user)
            ->put(route('reports.update', $laporan), ['condition_tags' => [ConditionTag::DRY->value]])
            ->assertRedirect(route('history'));
    }

    /**
     * Ruling W6-6j (M22, daftar periksa butir 6): formulir.tanggal memakai zona gunung, sama
     * seperti 'tanggal' tingkat atas (W6-6b). 20.00 UTC adalah 03.00 keesokan harinya di WIB.
     */
    public function test_the_report_forms_default_date_uses_the_mountains_timezone(): void
    {
        $user = User::factory()->create();
        $trail = Trail::factory()->create(['name' => 'Jalur Malam']);
        $trip = TripPlan::factory()->for($user)->for($trail)->create(['status' => TripStatus::COMPLETED->value]);
        $trip->hikingSession()->create(['user_id' => $user->id, 'status' => HikingSessionStatus::COMPLETED->value, 'started_at' => now()->subDays(2), 'ended_at' => now()->subDays(2)->addHours(9)]);

        HikingHistory::create([
            'user_id' => $user->id,
            'trip_plan_id' => $trip->id,
            'trail_id' => $trail->id,
            'trip_type' => $trip->trip_type->value,
            'completion_state' => CompletionState::COMPLETED->value,
            'completed_at' => Carbon::parse('2026-09-18 20:00:00', 'UTC'),
        ]);

        $this->actingAs($user)->get(route('history.summary', $trip))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('laporan.formulir.tanggal', '2026-09-19'));
    }

    /**
     * Ruling W6-6j (M25, daftar periksa butir 8): rute ini dibatasi laju seperti disebut
     * docblock HasilPendakian.php. Melewati batasnya menolak permintaan ke-31 dalam satu menit,
     * bukan melayaninya seolah tidak ada batas.
     */
    public function test_report_updates_are_rate_limited(): void
    {
        [$user, $trip] = $this->selesai();
        $laporan = $this->laporanTertaut($user, $trip, ModerationStatus::PENDING);
        $batas = (int) config('hiking.rate_limits.personal_writes_per_minute');

        for ($i = 0; $i < $batas; $i++) {
            $this->actingAs($user)->put(route('reports.update', $laporan), ['condition_tags' => [ConditionTag::DRY->value]]);
        }

        $this->actingAs($user)
            ->put(route('reports.update', $laporan), ['condition_tags' => [ConditionTag::DRY->value]])
            ->assertStatus(429);
    }

    /**
     * Menyelesaikan pendakian membuka ringkasannya, tempat formulir laporan sekarang berada.
     */
    public function test_finishing_a_hike_opens_its_summary(): void
    {
        $trip = TripPlan::factory()->create(['status' => TripStatus::IN_PROGRESS->value]);
        $trip->hikingSession()->create(['user_id' => $trip->user_id, 'status' => HikingSessionStatus::ACTIVE->value, 'started_at' => now()]);

        $this->actingAs($trip->user)
            ->post(route('trips.complete', $trip), ['completion_state' => CompletionState::COMPLETED->value])
            ->assertRedirect(route('history.summary', $trip));
    }

    private function laporanTertaut(User $user, TripPlan $trip, ModerationStatus $status): TrailConditionReport
    {
        $laporan = TrailConditionReport::factory()->for($trip->trail)->create([
            'user_id' => $user->id,
            'moderation_status' => $status->value,
            'condition_tags' => [ConditionTag::SLIPPERY->value],
            'hike_date' => '2026-09-18',
        ]);

        $trip->history->update(['trail_condition_report_id' => $laporan->id]);

        return $laporan;
    }
}
