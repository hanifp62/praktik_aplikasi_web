<?php

namespace Tests\Feature;

use App\Enums\FreshnessState;
use App\Enums\OfficialStatusValue;
use App\Enums\RouteFitLabel;
use App\Enums\SourceType;
use App\Enums\StatusScope;
use App\Models\DataSource;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\User;
use App\Services\ConsiderationService;
use App\Services\DataFreshnessService;
use App\Services\OfficialStatusService;
use App\Services\Trails\StatusJalurRingkas;
use App\Support\Timezone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Tata bahasa kepercayaan (spec v3 bagian 7; W3 Tugas 1): label kecocokan, status akses,
 * dan asal sebuah keterangan terbaca dengan cara yang sama di setiap layar klaster Jelajah.
 *
 * Yang dijaga di sini adalah klaim yang dapat menyesatkan pendaki bila salah: cap "resmi"
 * yang dipinjam keterangan tak resmi (R-009), status yang diwariskan gunung tetapi dikutip
 * dari jalurnya (§42), status kosong yang terbaca Buka (§95), dan dua kata untuk satu
 * keadaan UNKNOWN (R-006).
 */
class TrustGrammarTest extends TestCase
{
    use RefreshDatabase;
    use SumberReact;

    /**
     * Glyph "resmi" hanya untuk keterangan bersumber OFFICIAL (R-009; parkir Tugas 4 W1
     * "ditegakkan di tugas pemakai resmi"). Satu-satunya pemakainya baris OFFICIAL di
     * LabelSumber; registri ikon dan pameran primitif (hanya local) dikecualikan.
     */
    public function test_the_official_seal_marks_only_official_sources(): void
    {
        $label = $this->tanpaKomentarJs(File::get(resource_path('js/components/trails/LabelSumber.tsx')));
        $barisResmi = array_values(array_filter(explode("\n", $label), fn (string $baris) => str_contains($baris, "'resmi'")));

        $this->assertCount(1, $barisResmi, 'Glyph resmi harus tepat satu kali di LabelSumber.');
        $this->assertStringStartsWith('    OFFICIAL: {', $barisResmi[0], 'Glyph resmi hanya boleh milik baris OFFICIAL.');

        $pelanggar = [];
        $kecuali = ['components/trails/LabelSumber.tsx', 'components/ui/Icon.tsx', 'components/ui/ikon/glyphDomain.ts', 'pages/Dev/Primitif.tsx'];

        foreach (File::allFiles(resource_path('js')) as $berkas) {
            $jalur = str_replace('\\', '/', $berkas->getRelativePathname());

            if (in_array($jalur, $kecuali, true) || str_starts_with($jalur, 'routes/') || str_starts_with($jalur, 'actions/') || str_starts_with($jalur, 'wayfinder/')) {
                continue;
            }

            if (preg_match('/(?:name|ikon)=(?:"resmi"|\{\s*\'resmi\'\s*\})|ikon:\s*\'resmi\'/', $this->tanpaKomentarJs($berkas->getContents()))) {
                $pelanggar[] = $jalur;
            }
        }

        $this->assertSame([], $pelanggar, 'Glyph resmi dipakai di luar LabelSumber: '.implode(', ', $pelanggar));
    }

    /**
     * Jenis sumber dibaca dari data_sources, bukan dari nama tabelnya. Catatan yang disalin
     * admin tetap "Terverifikasi admin", dan catatan tanpa sumber data tercatat tidak diberi
     * jenis sama sekali.
     */
    public function test_the_source_type_comes_from_the_data_source_not_the_table(): void
    {
        $resmi = $this->jalurDenganStatus(OfficialStatusValue::OPEN, SourceType::OFFICIAL);
        $salinan = $this->jalurDenganStatus(OfficialStatusValue::OPEN, SourceType::ADMIN_VERIFIED);
        $tanpaSumber = $this->jalurDenganStatus(OfficialStatusValue::OPEN, null);

        $ringkas = app(StatusJalurRingkas::class)->untuk(Trail::with('mountain')->get());

        $this->assertSame(['jenis' => 'OFFICIAL', 'label' => 'Resmi', 'nama' => 'Balai Uji'], $ringkas[$resmi->id]['sumber']);
        $this->assertSame('ADMIN_VERIFIED', $ringkas[$salinan->id]['sumber']['jenis']);
        $this->assertSame('Terverifikasi admin', $ringkas[$salinan->id]['sumber']['label']);
        $this->assertNull($ringkas[$tanpaSumber->id]['sumber']['jenis']);
        $this->assertNull($ringkas[$tanpaSumber->id]['sumber']['label']);

        $this->actingAs(User::factory()->create())
            ->get(route('trails.show', $salinan))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('statusResmi.jenisSumber', 'ADMIN_VERIFIED'));
    }

    /**
     * §42: pembatasan turun dari gunung ke jalur. Jalur yang tercatat Buka di gunung yang
     * ditutup terbaca Tutup, dan yang dikutip adalah catatan gunungnya.
     */
    public function test_a_mountain_closure_is_quoted_from_the_mountain_record(): void
    {
        $jalur = $this->jalurDenganStatus(OfficialStatusValue::OPEN, SourceType::OFFICIAL);

        OfficialStatus::factory()->create([
            'statusable_type' => (new Mountain)->getMorphClass(),
            'statusable_id' => $jalur->mountain_id,
            'scope' => StatusScope::MOUNTAIN->value,
            'status' => OfficialStatusValue::CLOSED->value,
            'source' => 'Balai Gunung',
            'data_source_id' => DataSource::factory()->create(['source_type' => SourceType::OFFICIAL->value])->id,
        ]);

        $ringkas = app(StatusJalurRingkas::class)->untuk(Trail::with('mountain')->get())[$jalur->id];

        $this->assertSame('CLOSED', $ringkas['status']);
        $this->assertSame('Tutup', $ringkas['label']);
        $this->assertSame('Gunung', $ringkas['cakupan']);
        $this->assertSame('Balai Gunung', $ringkas['sumber']['nama']);
    }

    /**
     * §95, BR-07: tanpa catatan yang berlaku statusnya UNKNOWN, tidak pernah Buka, dan tanpa
     * sumber yang dapat dikutip.
     */
    public function test_no_current_record_reads_unknown_not_open(): void
    {
        $jalur = Trail::factory()->for(Mountain::factory())->create();

        $ringkas = app(StatusJalurRingkas::class)->untuk(Trail::with('mountain')->get())[$jalur->id];

        $this->assertNull($ringkas['status']);
        $this->assertSame('Belum diketahui', $ringkas['label']);
        $this->assertNull($ringkas['sumber']);
        $this->assertNull($ringkas['waktu']);
    }

    /**
     * Dua kueri untuk berapa pun jalurnya (CLAUDE.md bagian 6): daftar Jelajah, halaman
     * gunung, dan banding memanggilnya untuk satu halaman hasil sekaligus.
     */
    public function test_the_summary_costs_the_same_for_two_or_twelve_trails(): void
    {
        foreach (range(1, 2) as $i) {
            $this->jalurDenganStatus(OfficialStatusValue::OPEN, SourceType::OFFICIAL);
        }

        $sedikit = $this->hitungKueri(fn () => app(StatusJalurRingkas::class)->untuk(Trail::with('mountain')->get()));

        foreach (range(1, 10) as $i) {
            $this->jalurDenganStatus(OfficialStatusValue::RESTRICTED, SourceType::ADMIN_VERIFIED);
        }

        $banyak = $this->hitungKueri(fn () => app(StatusJalurRingkas::class)->untuk(Trail::with('mountain')->get()));

        $this->assertSame($sedikit, $banyak, "Ringkasan status tumbuh: {$sedikit} lalu {$banyak} kueri.");
    }

    /**
     * DK-32: empat bentuk untuk empat keadaan, selalu bersama kata; forced-colors menimpa
     * warna, bukan bentuk.
     */
    public function test_the_access_status_is_carried_by_shape_and_word(): void
    {
        $status = $this->tanpaKomentarJs(File::get(resource_path('js/components/trails/StatusAkses.tsx')));

        $this->assertStringContainsString("{nilai === 'OPEN' ? <circle", $status);
        $this->assertStringContainsString("{nilai === 'RESTRICTED' ? <rect x=\"4\" y=\"4\" width=\"8\" height=\"8\" transform=\"rotate(45 8 8)\"", $status);
        $this->assertStringContainsString("{nilai === 'CLOSED' ? <rect", $status);
        $this->assertStringContainsString('{nilai === null ? <circle cx="8" cy="8" r="5" fill="none" strokeWidth="2" strokeDasharray="2.5 2"', $status);
        $this->assertStringContainsString('<svg viewBox="0 0 16 16" aria-hidden="true"', $status);
    }

    /**
     * Label kecocokan selalu ikon dan kata, untuk ketiga label dan keadaan UNKNOWN, dan
     * tidak pernah menggambar angka (R-004, R-005, spec v3 bagian 7).
     */
    public function test_the_fit_label_pairs_an_icon_with_a_word_and_never_a_number(): void
    {
        $label = $this->tanpaKomentarJs(File::get(resource_path('js/components/trails/LabelKecocokan.tsx')));

        foreach (["COCOK: { nada: 'cocok', ikon: 'check' }", "PERLU_PERSIAPAN: { nada: 'perlu', ikon: 'warning' }", "KURANG_COCOK: { nada: 'kurang', ikon: 'x' }", "BELUM: { nada: 'belum', ikon: 'question' }"] as $rupa) {
            $this->assertStringContainsString($rupa, $label);
        }

        $this->assertStringContainsString("const r = rupa[nilai ?? 'BELUM'];", $label);
        $this->assertDoesNotMatchRegularExpression('/%|toFixed|skor|score/', $label);
    }

    /**
     * R-006, M-5 W1: keadaan UNKNOWN punya satu kata dari satu sumber, RouteFitLabel. Tidak
     * ada halaman atau komponen yang menuliskannya sendiri; pameran primitif (hanya local)
     * dikecualikan karena isinya contoh.
     *
     * Perbaikan P-3: ikut memindai berkas .ts, tidak hanya .tsx, supaya kata lama yang
     * lolos ke berkas tipe atau util tidak luput.
     */
    public function test_the_unrated_word_is_never_typed_in_the_client(): void
    {
        $pelanggar = [];

        foreach ($this->berkasTsx('', true) as $berkas) {
            $jalur = str_replace('\\', '/', $berkas->getRelativePathname());

            if ($jalur !== 'pages/Dev/Primitif.tsx' && str_contains($this->tanpaKomentarJs($berkas->getContents()), 'Belum dinilai')) {
                $pelanggar[] = $jalur;
            }
        }

        $this->assertSame([], $pelanggar, '"Belum dinilai" ditulis di klien: '.implode(', ', $pelanggar));
    }

    /**
     * P-1 (Important, R-009): tabel lengkap kaskade §42, 5 keadaan gunung x 5 keadaan
     * jalur. Status dan pemilik catatan penentu ditulis eksplisit per baris, bukan
     * dihitung dari fungsi yang diuji, supaya tabel ini benar-benar independen. Jenis
     * sumber gunung dan jalur dibedakan (ADMIN_VERIFIED vs OFFICIAL) supaya kutipan yang
     * salah tertangkap. `effectiveStatusForTrail()`, `snapshotForTrail()`, dan
     * `StatusJalurRingkas::untuk()` wajib sepakat satu sama lain dan dengan tabel ini,
     * sebab sebelum perbaikan snapshotForTrail() mengutip catatan jalur untuk field
     * deskriptif walau status ditentukan kaskade gunung.
     */
    public function test_the_mountain_cascade_table_agrees_between_detail_and_list(): void
    {
        $OPEN = OfficialStatusValue::OPEN;
        $RESTRICTED = OfficialStatusValue::RESTRICTED;
        $CLOSED = OfficialStatusValue::CLOSED;
        $UNKNOWN = OfficialStatusValue::UNKNOWN;

        // [gunung, jalur, status diharapkan (null = UNKNOWN), pemilik ('gunung'|'jalur'|null)]
        $kasus = [
            [null, null, null, null],
            [null, $OPEN, 'OPEN', 'jalur'],
            [null, $RESTRICTED, 'RESTRICTED', 'jalur'],
            [null, $CLOSED, 'CLOSED', 'jalur'],
            [null, $UNKNOWN, null, 'jalur'],

            [$OPEN, null, null, null],
            [$OPEN, $OPEN, 'OPEN', 'jalur'],
            [$OPEN, $RESTRICTED, 'RESTRICTED', 'jalur'],
            [$OPEN, $CLOSED, 'CLOSED', 'jalur'],
            [$OPEN, $UNKNOWN, null, 'jalur'],

            [$RESTRICTED, null, 'RESTRICTED', 'gunung'],
            [$RESTRICTED, $OPEN, 'RESTRICTED', 'gunung'],
            [$RESTRICTED, $RESTRICTED, 'RESTRICTED', 'gunung'],
            [$RESTRICTED, $CLOSED, 'CLOSED', 'jalur'],
            [$RESTRICTED, $UNKNOWN, 'RESTRICTED', 'gunung'],

            [$CLOSED, null, 'CLOSED', 'gunung'],
            [$CLOSED, $OPEN, 'CLOSED', 'gunung'],
            [$CLOSED, $RESTRICTED, 'CLOSED', 'gunung'],
            [$CLOSED, $CLOSED, 'CLOSED', 'gunung'],
            [$CLOSED, $UNKNOWN, 'CLOSED', 'gunung'],

            [$UNKNOWN, null, null, null],
            [$UNKNOWN, $OPEN, 'OPEN', 'jalur'],
            [$UNKNOWN, $RESTRICTED, 'RESTRICTED', 'jalur'],
            [$UNKNOWN, $CLOSED, 'CLOSED', 'jalur'],
            [$UNKNOWN, $UNKNOWN, null, 'jalur'],
        ];

        $this->assertCount(25, $kasus, 'Tabel §42 harus memuat 5x5 kasus.');

        foreach ($kasus as $i => [$gunungNilai, $jalurNilai, $statusDiharapkan, $pemilik]) {
            $gunung = Mountain::factory()->create();
            $jalur = Trail::factory()->for($gunung)->create();

            $this->catatanUntuk($gunung, StatusScope::MOUNTAIN, $gunungNilai, SourceType::ADMIN_VERIFIED, 'Balai Gunung');
            $this->catatanUntuk($jalur, StatusScope::TRAIL, $jalurNilai, SourceType::OFFICIAL, 'Balai Jalur');

            $label = sprintf(
                'kasus #%d gunung=%s jalur=%s',
                $i,
                $gunungNilai?->value ?? 'TANPA CATATAN',
                $jalurNilai?->value ?? 'TANPA CATATAN'
            );

            $efektif = app(OfficialStatusService::class)->effectiveStatusForTrail($jalur->fresh());
            $this->assertSame($statusDiharapkan ?? 'UNKNOWN', $efektif->value, $label.' (effectiveStatusForTrail)');

            // snapshotForTrail() membawa nilai enum mentah ('OPEN'..'UNKNOWN'), sama
            // dengan effectiveStatusForTrail(); TrailDetailPage::statusResmi() yang
            // memetakan UNKNOWN menjadi null untuk publik.
            $snapshot = app(OfficialStatusService::class)->snapshotForTrail($jalur->fresh());
            $this->assertSame($statusDiharapkan ?? 'UNKNOWN', $snapshot['status'], $label.' (snapshotForTrail status)');

            // snapshotForTrail() mengirim NILAI enum cakupan ('MOUNTAIN'/'TRAIL'); label
            // manusia ("Gunung"/"Jalur") baru dipasang TrailDetailPage. StatusJalurRingkas
            // sudah mengirim labelnya sendiri. Dua representasi, diperiksa terpisah.
            $cakupanMentahDiharapkan = match ($pemilik) {
                'gunung' => StatusScope::MOUNTAIN->value,
                'jalur' => StatusScope::TRAIL->value,
                default => null,
            };
            $cakupanLabelDiharapkan = match ($pemilik) {
                'gunung' => StatusScope::MOUNTAIN->label(),
                'jalur' => StatusScope::TRAIL->label(),
                default => null,
            };
            $sumberNamaDiharapkan = match ($pemilik) {
                'gunung' => 'Balai Gunung',
                'jalur' => 'Balai Jalur',
                default => null,
            };
            $jenisDiharapkan = match ($pemilik) {
                'gunung' => SourceType::ADMIN_VERIFIED->value,
                'jalur' => SourceType::OFFICIAL->value,
                default => null,
            };

            $this->assertSame($cakupanMentahDiharapkan, $snapshot['scope'], $label.' (snapshotForTrail scope)');
            $this->assertSame($sumberNamaDiharapkan, $snapshot['source'], $label.' (snapshotForTrail source)');
            $this->assertSame($jenisDiharapkan, $snapshot['source_type'], $label.' (snapshotForTrail source_type)');

            $ringkas = app(StatusJalurRingkas::class)->untuk(Trail::with('mountain')->where('id', $jalur->id)->get())[$jalur->id];
            $this->assertSame($statusDiharapkan, $ringkas['status'], $label.' (StatusJalurRingkas status)');
            $this->assertSame($cakupanLabelDiharapkan, $ringkas['cakupan'], $label.' (StatusJalurRingkas cakupan)');

            if ($pemilik === null) {
                $this->assertNull($ringkas['sumber'], $label.' (StatusJalurRingkas sumber)');
            } else {
                $this->assertSame($sumberNamaDiharapkan, $ringkas['sumber']['nama'], $label.' (StatusJalurRingkas sumber.nama)');
                $this->assertSame($jenisDiharapkan, $ringkas['sumber']['jenis'], $label.' (StatusJalurRingkas sumber.jenis)');
            }
        }
    }

    /**
     * P-1: rincian jalur mengutip catatan yang benar-benar menentukan status (kaskade
     * §42), bukan selalu catatan jalur. Sebelum perbaikan, snapshotForTrail() memakai
     * currentFor() (catatan jalur) untuk source/source_type/scope walau gunung yang
     * menentukan, sehingga halaman ini akan menampilkan sumber OFFICIAL milik jalur
     * padahal yang berlaku catatan ADMIN_VERIFIED milik gunung.
     */
    public function test_the_trail_detail_page_cites_the_record_that_actually_determines_the_status(): void
    {
        $jalur = $this->jalurDenganStatus(OfficialStatusValue::OPEN, SourceType::OFFICIAL);

        OfficialStatus::factory()->create([
            'statusable_type' => (new Mountain)->getMorphClass(),
            'statusable_id' => $jalur->mountain_id,
            'scope' => StatusScope::MOUNTAIN->value,
            'status' => OfficialStatusValue::CLOSED->value,
            'source' => 'Balai Gunung',
            'data_source_id' => DataSource::factory()->create(['source_type' => SourceType::ADMIN_VERIFIED->value])->id,
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('trails.show', $jalur))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('statusResmi.status', 'CLOSED')
                ->where('statusResmi.scope', StatusScope::MOUNTAIN->label())
                ->where('statusResmi.source', 'Balai Gunung')
                ->where('statusResmi.jenisSumber', 'ADMIN_VERIFIED'));
    }

    /**
     * P-1 kebalikannya: penutupan gunung tetap terkutip walau jalurnya tidak punya
     * catatan yang sedang berlaku (hanya catatan kedaluwarsa yang memenuhi gerbang
     * publikasi R-022).
     */
    public function test_a_mountain_closure_is_cited_even_without_a_currently_effective_trail_record(): void
    {
        $gunung = Mountain::factory()->create();
        $jalur = $this->jalurTerbitTanpaStatusBerlaku($gunung);

        OfficialStatus::factory()->create([
            'statusable_type' => (new Mountain)->getMorphClass(),
            'statusable_id' => $gunung->id,
            'scope' => StatusScope::MOUNTAIN->value,
            'status' => OfficialStatusValue::CLOSED->value,
            'source' => 'Balai Gunung',
            'data_source_id' => DataSource::factory()->create(['source_type' => SourceType::OFFICIAL->value])->id,
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('trails.show', $jalur))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('statusResmi.jenisSumber', 'OFFICIAL')
                ->where('statusResmi.source', 'Balai Gunung'));
    }

    /**
     * P-3: DataFreshnessService::keadaan() dengan waktu dibekukan, termasuk satu kasus
     * tepat di batas hari tinjau (perbandingannya "lt", bukan "lte", jadi tepat di batas
     * masih Terkini).
     */
    public function test_the_freshness_state_matches_its_three_words_including_the_boundary(): void
    {
        $this->travelTo(Carbon::parse('2026-06-15 12:00:00'));

        $hariTinjau = (int) config('hiking.freshness.status_review_days', 90);
        $hariPeringatan = (int) config('hiking.freshness.status_expiry_warning_days', 14);
        $layanan = app(DataFreshnessService::class);

        $kasus = [
            'terkini' => [now(), null, 'Terkini'],
            'belum pernah diverifikasi' => [null, null, 'Perlu ditinjau'],
            'terlalu lama tidak diverifikasi' => [now()->subDays($hariTinjau + 1), null, 'Perlu ditinjau'],
            'segera kedaluwarsa' => [now(), now()->addDays($hariPeringatan - 1), 'Perlu ditinjau'],
            'sudah kedaluwarsa' => [now(), now()->subDay(), 'Kedaluwarsa'],
            'tepat di batas hari tinjau, belum Perlu ditinjau' => [now()->subDays($hariTinjau), null, 'Terkini'],
        ];

        foreach ($kasus as $nama => [$verified, $expires, $diharapkan]) {
            $status = new OfficialStatus(['verified_at' => $verified, 'expires_at' => $expires]);

            $this->assertSame($diharapkan, $layanan->keadaan($status), $nama);
        }
    }

    /**
     * P-3: StatusJalurRingkas::untuk() tidak pernah menaikkan jalur tanpa catatan lewat
     * gunung OPEN (§42), dan catatan jalur yang sudah kedaluwarsa tidak dipakai sebagai
     * penentu (currentlyEffective menolaknya).
     */
    public function test_the_summary_never_upgrades_an_unrecorded_trail_or_uses_an_expired_record(): void
    {
        $gunungTerbuka = Mountain::factory()->create();
        OfficialStatus::factory()->create([
            'statusable_type' => (new Mountain)->getMorphClass(),
            'statusable_id' => $gunungTerbuka->id,
            'scope' => StatusScope::MOUNTAIN->value,
            'status' => OfficialStatusValue::OPEN->value,
            'source' => 'Balai Gunung',
        ]);
        $jalurTanpaCatatan = Trail::factory()->for($gunungTerbuka)->create();

        $jalurKedaluwarsa = Trail::factory()->for(Mountain::factory())->create();
        OfficialStatus::factory()->create([
            'statusable_type' => $jalurKedaluwarsa->getMorphClass(),
            'statusable_id' => $jalurKedaluwarsa->id,
            'scope' => StatusScope::TRAIL->value,
            'status' => OfficialStatusValue::OPEN->value,
            'source' => 'Catatan lama',
            'effective_at' => now()->subYear(),
            'expires_at' => now()->subDay(),
        ]);

        $ringkas = app(StatusJalurRingkas::class)->untuk(
            Trail::with('mountain')->whereIn('id', [$jalurTanpaCatatan->id, $jalurKedaluwarsa->id])->get()
        );

        $this->assertNull($ringkas[$jalurTanpaCatatan->id]['status']);
        $this->assertSame('Belum diketahui', $ringkas[$jalurTanpaCatatan->id]['label']);
        $this->assertNull($ringkas[$jalurKedaluwarsa->id]['status']);
        $this->assertSame('Belum diketahui', $ringkas[$jalurKedaluwarsa->id]['label']);
    }

    /**
     * P-3: waktu dan kesegaran terisi dan terformat untuk catatan yang berlaku (waktu
     * dibekukan supaya formatnya tidak bergantung pada kapan test ini berjalan).
     */
    public function test_the_summary_formats_time_and_freshness_for_a_current_record(): void
    {
        $this->travelTo(Carbon::parse('2026-06-15 09:00:00'));

        $jalur = $this->jalurDenganStatus(OfficialStatusValue::OPEN, SourceType::OFFICIAL);

        $ringkas = app(StatusJalurRingkas::class)->untuk(Trail::with('mountain')->where('id', $jalur->id)->get())[$jalur->id];

        $diharapkan = now()->copy()->setTimezone(Timezone::forTrail($jalur->fresh('mountain')))->translatedFormat('j M');

        $this->assertSame($diharapkan, $ringkas['waktu']);
        $this->assertSame('Terkini', $ringkas['kesegaran']);
    }

    /**
     * P-3: prop kataBelumDinilai di halaman Bandingkan berasal dari
     * RouteFitLabel::UNRATED_TEXT, satu-satunya sumber kata UNKNOWN (lihat
     * test_the_unrated_word_is_never_typed_in_the_client).
     */
    public function test_the_compare_page_receives_the_unrated_word_from_the_server(): void
    {
        $user = User::factory()->create();
        $jalur = Trail::factory()->for(Mountain::factory()->create())->published()->create();
        app(ConsiderationService::class)->toggle($user, $jalur);

        $this->actingAs($user)
            ->get(route('trails.compare'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('kataBelumDinilai', RouteFitLabel::UNRATED_TEXT));
    }

    /**
     * P-4: setiap kata kesegaran yang dapat dikirim server (FreshnessState dan tiga kata
     * DataFreshnessService::keadaan()) punya nada di Kesegaran.tsx, supaya kata yang
     * berubah di server tertangkap di sini, bukan di layar pengguna.
     */
    public function test_every_freshness_word_the_server_can_send_has_a_tone(): void
    {
        $kesegaran = $this->tanpaKomentarJs(File::get(resource_path('js/components/trails/Kesegaran.tsx')));

        $kata = [...array_map(fn (FreshnessState $f) => $f->label(), FreshnessState::cases()), 'Terkini', 'Perlu ditinjau', 'Kedaluwarsa'];

        foreach ($kata as $satu) {
            $kunci = str_contains($satu, ' ') ? "'{$satu}'" : $satu;
            $this->assertStringContainsString("{$kunci}: '", $kesegaran, "Kata \"{$satu}\" tidak punya nada di Kesegaran.tsx.");
        }
    }

    /**
     * P-5: kata jenis sumber di LabelSumber.tsx menyalin SourceType::label(); test ini
     * menangkap kata yang berubah di satu sisi tetapi lupa diikutkan di sisi lain.
     */
    public function test_every_source_type_word_matches_its_enum_label(): void
    {
        $label = $this->tanpaKomentarJs(File::get(resource_path('js/components/trails/LabelSumber.tsx')));

        foreach (SourceType::cases() as $jenis) {
            $this->assertStringContainsString("label: '{$jenis->label()}'", $label, "Kata SourceType::{$jenis->name} tidak sama di LabelSumber.tsx.");
        }
    }

    /**
     * W3-2c dan I1 tinjauan akhir W3: waktu yang dikutip bersama awalannya dari kolom yang
     * benar-benar dipakai. Catatan tanpa verified_at tidak pernah tertulis "Diverifikasi" di
     * daftar, rincian, sumber halaman, halaman gunung, maupun halaman penilaian kecocokan.
     */
    public function test_a_record_without_verification_is_never_called_verified(): void
    {
        $this->travelTo(Carbon::parse('2026-06-15 05:00:00'));

        $diterbitkan = $this->jalurTerbitDenganCatatan([
            'verified_at' => null,
            'published_at' => Carbon::parse('2026-06-10 03:00:00'),
            'effective_at' => Carbon::parse('2026-06-01 03:00:00'),
        ]);
        $berlaku = $this->jalurTerbitDenganCatatan([
            'verified_at' => null,
            'published_at' => null,
            'effective_at' => Carbon::parse('2026-06-01 03:00:00'),
        ]);

        $ringkas = app(StatusJalurRingkas::class)->untuk(Trail::with('mountain')->whereIn('id', [$diterbitkan->id, $berlaku->id])->get());
        $this->assertSame(['10 Jun', 'Diterbitkan'], [$ringkas[$diterbitkan->id]['waktu'], $ringkas[$diterbitkan->id]['awalanWaktu']]);
        $this->assertSame(['1 Jun', 'Berlaku sejak'], [$ringkas[$berlaku->id]['waktu'], $ringkas[$berlaku->id]['awalanWaktu']]);

        $pembaca = User::factory()->create();
        $pembaca->profile()->create(['experience_level' => 'INTERMEDIATE', 'completed_at' => now()]);
        $rinci = $this->actingAs($pembaca->fresh())->get(route('trails.show', $diterbitkan))->viewData('page')['props'];

        $this->assertSame('Diterbitkan', $rinci['statusResmi']['awalanWaktu']);
        $this->assertStringStartsWith('10 Jun 2026', (string) $rinci['statusResmi']['waktu']);
        $this->assertStringStartsWith('Balai Uji, diterbitkan 10 Jun 2026', $rinci['sumber'][0]['isi']);

        $syarat = $this->actingAs($pembaca->fresh())->get(route('trails.fit', $diterbitkan))->viewData('page')['props']['syaratDasar'][0]['teks'];
        $this->assertStringContainsString('Menurut Balai Uji, diterbitkan 10 Jun.', $syarat);

        foreach (['js/components/trails/rinci/Kondisi.tsx', 'js/components/trails/gunung/KartuStatusGunung.tsx'] as $berkas) {
            $sumber = $this->tanpaKomentarJs(File::get(resource_path($berkas)));
            $this->assertStringNotContainsString('awalan="Diverifikasi"', $sumber, "{$berkas} menulis Diverifikasi untuk waktu apa pun.");
            $this->assertStringContainsString('awalan={status.awalanWaktu', $sumber);
        }
    }

    /**
     * I1 tinjauan akhir W3: catatan yang sama menyebut keadaan kesegaran yang sama di kartu
     * Jelajah dan di rincian jalur, tempat keputusan diambil. Rincian menghitungnya dari
     * stempel snapshot (DataFreshnessService::keadaanDari), jadi snapshot bercache tidak
     * membekukan kata kesegarannya.
     */
    public function test_the_detail_page_and_the_jelajah_card_agree_on_freshness(): void
    {
        $hariTinjau = (int) config('hiking.freshness.status_review_days', 90);
        $jalur = $this->jalurTerbitDenganCatatan(['verified_at' => now()->subDays($hariTinjau + 10)]);
        $pembaca = User::factory()->create();

        $kartu = $this->actingAs($pembaca)->get(route('trails.index'))->viewData('page')['props']['trails']['data'][0]['status'];
        $rinci = $this->actingAs($pembaca)->get(route('trails.show', $jalur))->viewData('page')['props']['statusResmi'];

        $this->assertSame('Perlu ditinjau', $kartu['kesegaran']);
        $this->assertSame($kartu['kesegaran'], $rinci['kesegaran']);
        $this->assertSame('Diverifikasi', $rinci['awalanWaktu']);

        $this->assertStringContainsString('keadaan={status.kesegaran}', $this->tanpaKomentarJs(File::get(resource_path('js/components/trails/rinci/Kondisi.tsx'))));

        $snapshot = app(OfficialStatusService::class)->snapshotForTrail($jalur);
        $this->assertArrayHasKey('effective_at', $snapshot);
        $this->assertArrayHasKey('expires_at', $snapshot);
    }

    /**
     * m2 tinjauan akhir W3 (parkir W3-1g): tanggal pendek menulis tahunnya bila bukan tahun
     * berjalan di zona gunung, supaya catatan tahun lalu tidak terbaca tahun ini di halaman
     * penilaian, satu-satunya tempat waktu ringkas tampil tanpa lencana kesegaran.
     */
    public function test_a_status_time_from_another_year_names_its_year(): void
    {
        $this->travelTo(Carbon::parse('2026-06-15 05:00:00'));

        $lama = $this->jalurTerbitDenganCatatan(['verified_at' => Carbon::parse('2025-11-03 05:00:00')]);
        $baru = $this->jalurTerbitDenganCatatan(['verified_at' => Carbon::parse('2026-06-10 05:00:00')]);

        $ringkas = app(StatusJalurRingkas::class)->untuk(Trail::with('mountain')->whereIn('id', [$lama->id, $baru->id])->get());

        $this->assertSame('3 Nov 2025', $ringkas[$lama->id]['waktu']);
        $this->assertSame('10 Jun', $ringkas[$baru->id]['waktu']);
    }

    /**
     * Jalur terbit dengan satu catatan status OPEN yang sedang berlaku, stempel waktunya dari
     * $stempel (bawaan factory untuk yang tidak disebut).
     *
     * @param  array<string, mixed>  $stempel
     */
    private function jalurTerbitDenganCatatan(array $stempel): Trail
    {
        $jalur = Trail::factory()->for(Mountain::factory())->create();

        OfficialStatus::factory()->create([
            'statusable_type' => $jalur->getMorphClass(),
            'statusable_id' => $jalur->id,
            'scope' => StatusScope::TRAIL->value,
            'status' => OfficialStatusValue::OPEN->value,
            'source' => 'Balai Uji',
            ...$stempel,
        ]);

        $jalur->forceFill(['data_source_id' => DataSource::factory()->create()->id])->saveQuietly();
        $jalur->checkpoints()->create(['sequence' => 1, 'name' => 'Pos uji '.$jalur->id]);
        $jalur->refresh()->update(['is_published' => true]);

        return $jalur;
    }

    private function jalurDenganStatus(OfficialStatusValue $nilai, ?SourceType $jenis): Trail
    {
        $jalur = Trail::factory()->for(Mountain::factory())->create();

        OfficialStatus::factory()->create([
            'statusable_type' => $jalur->getMorphClass(),
            'statusable_id' => $jalur->id,
            'scope' => StatusScope::TRAIL->value,
            'status' => $nilai->value,
            'source' => 'Balai Uji',
            'data_source_id' => $jenis === null ? null : DataSource::factory()->create(['source_type' => $jenis->value])->id,
        ]);

        // Rincian jalur butuh jalur terbit; gerbang publikasi dilalui lewat model, bukan kolom.
        $jalur->forceFill(['data_source_id' => DataSource::factory()->create()->id])->saveQuietly();
        $jalur->checkpoints()->create(['sequence' => 1, 'name' => 'Pos uji '.$jalur->id]);
        $jalur->refresh()->update(['is_published' => true]);

        return $jalur;
    }

    private function hitungKueri(callable $kerja): int
    {
        DB::enableQueryLog();
        DB::flushQueryLog();
        $kerja();
        $jumlah = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $jumlah;
    }

    /**
     * Satu catatan status untuk tabel kaskade §42, atau null untuk "tanpa catatan".
     */
    private function catatanUntuk(Mountain|Trail $statusable, StatusScope $scope, ?OfficialStatusValue $nilai, SourceType $jenis, string $nama): ?OfficialStatus
    {
        if ($nilai === null) {
            return null;
        }

        return OfficialStatus::factory()->create([
            'statusable_type' => $statusable->getMorphClass(),
            'statusable_id' => $statusable->getKey(),
            'scope' => $scope->value,
            'status' => $nilai->value,
            'source' => $nama,
            'data_source_id' => DataSource::factory()->create(['source_type' => $jenis->value])->id,
        ]);
    }

    /**
     * Jalur terbit tanpa catatan status yang sedang berlaku: gerbang publikasi (R-022)
     * hanya menghitung baris, bukan yang currently-effective, jadi satu catatan
     * kedaluwarsa memenuhinya tanpa menjadi catatan penentu kaskade §42.
     */
    private function jalurTerbitTanpaStatusBerlaku(Mountain $gunung): Trail
    {
        $jalur = Trail::factory()->for($gunung)->create();

        OfficialStatus::factory()->create([
            'statusable_type' => $jalur->getMorphClass(),
            'statusable_id' => $jalur->id,
            'scope' => StatusScope::TRAIL->value,
            'status' => OfficialStatusValue::OPEN->value,
            'source' => 'Catatan lama',
            'effective_at' => now()->subYear(),
            'expires_at' => now()->subMonth(),
        ]);

        $jalur->forceFill(['data_source_id' => DataSource::factory()->create()->id])->saveQuietly();
        $jalur->checkpoints()->create(['sequence' => 1, 'name' => 'Pos uji '.$jalur->id]);
        $jalur->refresh()->update(['is_published' => true]);

        return $jalur;
    }
}
