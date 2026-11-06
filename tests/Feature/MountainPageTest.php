<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\ModerationStatus;
use App\Enums\OfficialStatusValue;
use App\Enums\SourceType;
use App\Enums\StatusScope;
use App\Models\DataSource;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\PermitRequirement;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\User;
use App\Services\PermitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Halaman gunung (DK-44, spec v3 bagian 6.3, W3 Tugas 2): hub jalur sebuah gunung untuk
 * pengguna masuk. Gunung membuka jalurnya (R-001), keputusan tetap per jalur (R-002), dan
 * hanya jalur terbit yang tampil (R-022).
 */
class MountainPageTest extends TestCase
{
    use RefreshDatabase;
    use SumberReact;

    private function gunung(array $atribut = []): Mountain
    {
        return Mountain::factory()->create(array_merge(['name' => 'Gunung Uji', 'slug' => 'gunung-uji', 'elevation_mdpl' => 2958], $atribut));
    }

    private function halaman(Mountain $gunung, ?User $pembaca = null): TestResponse
    {
        return $this->actingAs($pembaca ?? User::factory()->create())->get(route('mountains.show', $gunung))->assertOk();
    }

    private function sumber(): string
    {
        return $this->tanpaKomentarJs(File::get(resource_path('js/pages/Mountains/Show.tsx')));
    }

    /** Jalur terbit dengan satu laporan kondisi termoderasi masing-masing (P-6, gerbang perbaikan 1). */
    private function jalurDenganLaporan(Mountain $gunung, int $jumlah): void
    {
        Trail::factory()->count($jumlah)->for($gunung)->published()->create()
            ->each(fn (Trail $t) => TrailConditionReport::factory()->for($t)->approved()->create());
    }

    public function test_a_guest_is_sent_to_login(): void
    {
        $this->get(route('mountains.show', $this->gunung()))->assertRedirect(route('login'));
    }

    public function test_an_archived_mountain_answers_not_found(): void
    {
        $gunung = $this->gunung(['archived_at' => now()]);

        $this->actingAs(User::factory()->create())->get(route('mountains.show', $gunung))->assertNotFound();
    }

    /**
     * R-022: jalur draf tidak pernah tampil sebagai pilihan; ia hanya dihitung sebagai jalur
     * yang menunggu kurator.
     */
    public function test_only_published_trails_are_offered(): void
    {
        $gunung = $this->gunung();
        Trail::factory()->for($gunung)->published()->create(['name' => 'Via Terbit']);
        Trail::factory()->for($gunung)->unpublished()->create(['name' => 'Via Draf']);
        Trail::factory()->for($gunung)->published()->create(['name' => 'Via Arsip', 'archived_at' => now()]);

        $respons = $this->halaman($gunung);

        $respons->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Mountains/Show')
            ->where('title', 'Gunung Uji')
            ->has('jalur', 1)
            ->where('jalur.0.nama', 'Via Terbit')
            ->where('jumlahMenunggu', 1));
        $respons->assertDontSee('Via Draf');

        // P-9: jalur yang belum terbit tidak selalu kurang data; kalimatnya tidak boleh
        // mengklaim kelengkapannya.
        $this->assertStringContainsString('jalur lain belum terbit. Menunggu kurator Memount.', $this->sumber());
        $this->assertStringNotContainsString('datanya belum lengkap', $this->sumber());
    }

    /**
     * BR-02: ketinggian hanya keterangan, bukan ukuran berat-ringan pendakian.
     */
    public function test_the_height_is_context_not_difficulty(): void
    {
        $this->halaman($this->gunung())->assertInertia(fn (AssertableInertia $page) => $page->where('gunung.puncak', '2.958 mdpl'));

        $this->assertStringContainsString('Ketinggian hanya keterangan. Berat-ringannya pendakian dinilai per jalur', $this->sumber());
    }

    /**
     * §42 dan R-012: status tingkat gunung dikutip dari catatannya sendiri, dan Buka di tingkat
     * gunung disertai kalimat bahwa jalurnya belum tentu buka. Kalimat "bukan jaminan
     * keselamatan" (perbaikan 1, P-1) kini satu rumusan tunggal yang selalu dirender, tidak
     * digandakan pada kalimat cakupan Buka.
     */
    public function test_a_mountain_level_status_says_nothing_about_each_trail(): void
    {
        $gunung = $this->gunung();
        Trail::factory()->for($gunung)->published()->create();

        OfficialStatus::factory()->create([
            'statusable_type' => $gunung->getMorphClass(),
            'statusable_id' => $gunung->id,
            'scope' => StatusScope::MOUNTAIN->value,
            'status' => OfficialStatusValue::OPEN->value,
            'source' => 'Balai Uji',
            'data_source_id' => DataSource::factory()->create(['source_type' => SourceType::OFFICIAL->value])->id,
        ]);

        $this->halaman($gunung)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('status.status', 'OPEN')
            ->where('status.sumber.jenis', 'OFFICIAL')
            ->where('status.sumber.nama', 'Balai Uji'));

        $kartu = $this->tanpaKomentarJs(File::get(resource_path('js/components/trails/gunung/KartuStatusGunung.tsx')));
        $this->assertStringContainsString('Status di tingkat gunung tidak berarti semua jalur buka.', $kartu);
        $this->assertStringContainsString('Status resmi menjelaskan ketentuan pengelola, bukan jaminan keselamatan.', $kartu);
        $this->assertStringContainsString('statusnya tidak dianggap buka', $kartu);
    }

    /**
     * Perbaikan 1, P-1 (Important, R-012): kalimat bukan jaminan keselamatan tidak boleh
     * bergantung pada adanya catatan status tingkat gunung. Gunung tanpa catatan sendiri
     * (kasus umum di data contoh dan di produksi) tetap menampilkannya, karena jalur di
     * bawahnya bisa saja berstatus Buka lewat catatannya sendiri.
     */
    public function test_the_safety_disclaimer_does_not_depend_on_a_mountain_status_record(): void
    {
        $gunung = $this->gunung();
        Trail::factory()->for($gunung)->published()->create();

        $this->halaman($gunung)->assertInertia(fn (AssertableInertia $page) => $page->where('status.status', null));

        $kartu = $this->tanpaKomentarJs(File::get(resource_path('js/components/trails/gunung/KartuStatusGunung.tsx')));
        $this->assertStringContainsString('Status resmi menjelaskan ketentuan pengelola, bukan jaminan keselamatan.', $kartu);
    }

    /**
     * Perbaikan 1, P-4 (Minor, §42): kalimat cakupan kartu status gunung mengikuti nilai
     * statusnya. Tutup dan Dibatasi benar-benar berlaku untuk semua jalurnya (kaskade §42),
     * jadi kalimat generik "tidak berarti semua jalur buka" salah untuk keduanya.
     */
    public function test_the_mountain_status_sentence_matches_closed_and_restricted(): void
    {
        $kartu = $this->tanpaKomentarJs(File::get(resource_path('js/components/trails/gunung/KartuStatusGunung.tsx')));

        $this->assertStringContainsString('Penutupan tingkat gunung berlaku untuk semua jalurnya.', $kartu);
        $this->assertStringContainsString('Pembatasan tingkat gunung berlaku untuk semua jalurnya, kecuali jalur yang tutup.', $kartu);
    }

    /**
     * Perbaikan 1, P-2 (Important): BarisStatus dipakai lintas Jelajah, pratinjau peta,
     * tautan bagikan, dan Bandingkan (Tugas 4, 5, 8, 9). Catatan yang kesegarannya bukan
     * "Terkini" tidak boleh terbaca segar di daftar jalur manapun yang memakai baris ini.
     */
    public function test_a_stale_status_is_not_rendered_as_current(): void
    {
        $baris = $this->tanpaKomentarJs(File::get(resource_path('js/components/trails/BarisStatus.tsx')));
        $this->assertStringContainsString("status.kesegaran && status.kesegaran !== 'Terkini'", $baris);
        $this->assertStringContainsString('nadaKesegaran(status.kesegaran)', $baris);

        $kesegaran = $this->tanpaKomentarJs(File::get(resource_path('js/components/trails/Kesegaran.tsx')));
        $this->assertStringContainsString('export function nadaKesegaran(keadaan: string): NadaLencana', $kesegaran);
    }

    /**
     * Perbaikan 1, P-3 (Important, R-024): `latest('verified_at')` menaruh NULL pertama di
     * PostgreSQL dan terakhir di SQLite. Aturan izin yang belum terverifikasi, walau dibuat
     * belakangan (id lebih besar), tidak boleh mengalahkan aturan yang sudah terverifikasi;
     * di antara dua yang terverifikasi, yang verified_at-nya terbaru menang.
     */
    public function test_the_permit_prefers_a_verified_rule_regardless_of_id_order(): void
    {
        $gunung = $this->gunung();
        $jalur = Trail::factory()->for($gunung)->published()->create();

        PermitRequirement::factory()->create([
            'mountain_id' => $gunung->id, 'trail_id' => null,
            'authority' => 'Terverifikasi lama', 'verified_at' => now()->subDays(10),
        ]);
        $terbaruTerverifikasi = PermitRequirement::factory()->create([
            'mountain_id' => $gunung->id, 'trail_id' => null,
            'authority' => 'Terverifikasi', 'verified_at' => now()->subDay(),
        ]);
        // Dibuat belakangan (id lebih besar) dan belum terverifikasi: tidak boleh menang.
        PermitRequirement::factory()->create([
            'mountain_id' => $gunung->id, 'trail_id' => null,
            'authority' => 'Belum terverifikasi', 'verified_at' => null,
        ]);

        $this->halaman($gunung)->assertInertia(fn (AssertableInertia $page) => $page->where('izin.pengelola', 'Terverifikasi'));

        // Penjaga paritas: urutan NULL harus eksplisit dalam SQL, bukan mengandalkan bawaan
        // mesin basis data yang berbeda antara SQLite dan PostgreSQL.
        $sql = strtolower(PermitRequirement::query()->terverifikasiDulu()->toSql());
        $this->assertStringContainsString('verified_at is null', $sql);

        $this->assertSame($terbaruTerverifikasi->id, app(PermitService::class)->requirementFor($jalur)->id);
        $this->assertSame(
            $terbaruTerverifikasi->id,
            app(PermitService::class)->requirementsForTrails(Trail::whereKey($jalur->id)->get())[$jalur->id]->id
        );
    }

    /**
     * Perbaikan 1, P-5 (Minor): kuota kosong tidak berarti aturan itu tidak terverifikasi.
     * Validasi admin (PermitRequirementRequest) mewajibkan kuota minimal 1 atau kosong, jadi
     * kosong berarti aturan itu memang tidak mencatat kuota, bukan kekurangan sumber.
     */
    public function test_the_permit_links_out_and_never_sells(): void
    {
        $gunung = $this->gunung();
        Trail::factory()->for($gunung)->published()->create();
        PermitRequirement::factory()->create([
            'mountain_id' => $gunung->id,
            'trail_id' => null,
            'authority' => 'Balai Uji',
            'booking_url' => 'https://booking.contoh.test',
            'booking_opens_days_before' => 30,
            'booking_closes_days_before' => 3,
            'daily_quota' => null,
        ]);

        $this->halaman($gunung)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('izin.pengelola', 'Balai Uji')
            ->where('izin.jendela', 'Dibuka H-30, ditutup H-3 dari tanggal naik')
            ->where('izin.kuota', null)
            ->where('izin.bookingUrl', 'https://booking.contoh.test'));

        $izin = $this->tanpaKomentarJs(File::get(resource_path('js/components/trails/gunung/KartuIzin.tsx')));
        $this->assertStringContainsString('Memount menautkan sistem resmi dan tidak menjual izin.', $izin);
        $this->assertStringContainsString('Tidak tercatat di aturan ini.', $izin);
        $this->assertStringContainsString('rel="noopener noreferrer"', $izin);
    }

    /**
     * Temuan tangkapan W3 butir 11 (ruling W3-G1 butir c): "Memount menautkan sistem resmi"
     * hanya benar ketika tautan pemesanan sungguh tampil. Tanpa booking_url, kalimatnya
     * berganti ke klaim yang tetap benar tanpa tautan apa pun.
     */
    public function test_the_permit_sentence_does_not_claim_a_link_when_none_exists(): void
    {
        $gunung = $this->gunung();
        Trail::factory()->for($gunung)->published()->create();
        PermitRequirement::factory()->create([
            'mountain_id' => $gunung->id,
            'trail_id' => null,
            'authority' => 'Balai Uji',
            'booking_url' => null,
        ]);

        $this->halaman($gunung)->assertInertia(fn (AssertableInertia $page) => $page->where('izin.bookingUrl', null));

        $izin = $this->tanpaKomentarJs(File::get(resource_path('js/components/trails/gunung/KartuIzin.tsx')));
        $this->assertStringContainsString(
            "{izin.bookingUrl ? 'Memount menautkan sistem resmi dan tidak menjual izin.' : 'Memount tidak memesan atau menjual izin.'}",
            $izin
        );
    }

    /**
     * Perbaikan 1, P-9: izin tingkat gunung yang kosong tidak berarti tidak ada aturan sama
     * sekali; aturan tiap jalur mungkin ada di rinciannya sendiri.
     */
    public function test_a_missing_mountain_permit_does_not_claim_no_rules_exist(): void
    {
        $gunung = $this->gunung();
        Trail::factory()->for($gunung)->published()->create();

        $this->halaman($gunung)->assertInertia(fn (AssertableInertia $page) => $page->where('izin', null));

        $this->assertStringContainsString('Aturan izin tingkat gunung belum tercatat', $this->sumber());
        $this->assertStringContainsString('Aturan tiap jalur ada di rinciannya.', $this->sumber());
    }

    /**
     * Gunung tanpa jalur terbit menyebut siapa yang ditunggu, bukan daftar kosong (PRD §104).
     */
    public function test_a_mountain_without_published_trails_names_who_is_awaited(): void
    {
        $gunung = $this->gunung();
        Trail::factory()->for($gunung)->unpublished()->create();

        $this->halaman($gunung)->assertInertia(fn (AssertableInertia $page) => $page->has('jalur', 0)->where('profil', null));

        $this->assertMatchesRegularExpression('/jalur\.length === 0 \? \(\s*<EmptyState\s+jenis="menunggu"/', $this->sumber());
        $this->assertStringContainsString('menunggu="Menunggu kurator Memount menerbitkan jalur yang datanya lengkap"', $this->sumber());
    }

    /**
     * R-010: laporan komunitas hanya yang lolos moderasi, dari jalur terbit gunung ini, dan
     * ditandai komunitas; kesegarannya dari tanggal pendakian.
     */
    public function test_community_reports_complement_the_official_status(): void
    {
        $gunung = $this->gunung();
        $jalur = Trail::factory()->for($gunung)->published()->create(['name' => 'Via Laporan']);
        $draf = Trail::factory()->for($gunung)->unpublished()->create();

        foreach ([[$jalur, ModerationStatus::APPROVED, 'Licin di pos dua'], [$jalur, ModerationStatus::PENDING, 'Belum dimoderasi'], [$draf, ModerationStatus::APPROVED, 'Dari jalur draf']] as [$t, $moderasi, $catatan]) {
            TrailConditionReport::factory()->for($t)->create([
                'user_id' => User::factory()->create()->id,
                'moderation_status' => $moderasi->value,
                'note' => $catatan,
                'hike_date' => now()->subDays(2)->toDateString(),
            ]);
        }

        $this->halaman($gunung)->assertInertia(fn (AssertableInertia $page) => $page
            ->has('kondisi', 1)
            ->where('kondisi.0.jalur', 'Via Laporan')
            ->where('kondisi.0.catatan', 'Licin di pos dua'));

        $this->assertStringContainsString('Laporan komunitas melengkapi status resmi, tidak menggantikannya.', $this->sumber());
        $this->assertStringContainsString('<LabelKomunitas />', File::get(resource_path('js/components/trails/gunung/KondisiPendaki.tsx')));
    }

    /**
     * BR-09, R-005: kecocokan per jalur hanya label, kata, dan alasan; tidak ada skor.
     */
    public function test_no_score_reaches_the_page(): void
    {
        $gunung = $this->gunung();
        Trail::factory()->for($gunung)->published()->create();
        $pembaca = User::factory()->create();
        $pembaca->profile()->create(['experience_level' => ExperienceLevel::INTERMEDIATE->value, 'completed_at' => now()]);

        $respons = $this->halaman($gunung, $pembaca->fresh());

        $this->assertSame(['label', 'labelText', 'alasan', 'denganRencana'], array_keys($respons->viewData('page')['props']['jalur'][0]['fit']));

        foreach (['internalScore', 'internal_score', '"score"', '"weight"'] as $terlarang) {
            $this->assertStringNotContainsString($terlarang, $respons->getContent());
        }
    }

    /**
     * Perbaikan 1, P-8 (Minor, R-003): alasan kecocokan dan penanda "untuk rencana ini"
     * dikirim server sejak Tugas 2, tetapi tidak dirender. Route Fit adalah differentiator
     * inti (R-003); label tanpa alasan yang terlihat mereduksinya menjadi ranking sederhana.
     */
    public function test_the_fit_reason_and_plan_marker_are_rendered(): void
    {
        $daftar = $this->tanpaKomentarJs(File::get(resource_path('js/components/trails/gunung/DaftarJalurGunung.tsx')));

        $this->assertStringContainsString('j.fit?.alasan', $daftar);
        $this->assertStringContainsString('untuk rencana ini', $daftar);
        $this->assertStringContainsString('j.fit?.denganRencana', $daftar);
    }

    /**
     * Visual data pengganti foto: profil setiap jalur pada skala mutlak yang sama, jadi jalur
     * yang naik lebih tinggi mencapai garis yang lebih tinggi dan jarak yang lebih panjang
     * tergambar lebih panjang.
     *
     * Perbaikan 1, P-9: dengan tiga jalur terbit dan hanya dua yang punya profil, keterangan
     * hero juga menyebut jumlah jalur terbit yang belum punya profil elevasi.
     */
    public function test_the_hero_draws_every_trail_on_one_scale(): void
    {
        $gunung = $this->gunung();
        Trail::factory()->for($gunung)->published()->create(['name' => 'Via Panjang', 'distance_km' => 10, 'elevation_gain_m' => 1000, 'elevation_profile' => [['km' => 0, 'm' => 1000], ['km' => 10, 'm' => 2000]]]);
        Trail::factory()->for($gunung)->published()->create(['name' => 'Via Pendek', 'distance_km' => 5, 'elevation_gain_m' => 500, 'elevation_profile' => [['km' => 0, 'm' => 1500], ['km' => 5, 'm' => 2000]]]);
        Trail::factory()->for($gunung)->published()->create(['name' => 'Via Tanpa Profil', 'elevation_profile' => null]);

        $profil = $this->halaman($gunung)->viewData('page')['props']['profil'];
        $garis = collect($profil['garis'])->keyBy('nama');

        $this->assertCount(2, $garis, 'Jalur tanpa profil tidak digambar sebagai garis karangan.');
        $this->assertSame([[0.0, 0.0], [1.0, 1.0]], $garis['Via Panjang']['titik']);
        $this->assertSame([[0.0, 0.5], [0.5, 1.0]], $garis['Via Pendek']['titik']);
        $this->assertStringContainsString('Via Panjang naik 1.000 m dalam 10,0 km', $profil['keterangan']);
        $this->assertStringContainsString('1 jalur terbit lain belum punya profil elevasi.', $profil['keterangan']);
    }

    /**
     * Anggaran kueri (CLAUDE.md bagian 6): halaman tidak tumbuh bersama jumlah jalur, laporan,
     * dan status. Perbaikan 1, P-6: kasus "banyak" juga menambah laporan komunitas termoderasi
     * per jalur, supaya klaim docblock HalamanGunung tentang laporan benar-benar terbukti.
     */
    public function test_the_page_does_not_grow_with_the_number_of_trails(): void
    {
        $gunung = $this->gunung();
        $pembaca = User::factory()->create();
        $pembaca->profile()->create(['experience_level' => ExperienceLevel::INTERMEDIATE->value, 'completed_at' => now()]);
        $this->actingAs($pembaca->fresh());

        $this->jalurDenganLaporan($gunung, 2);
        $url = route('mountains.show', $gunung);

        $this->get($url)->assertOk();
        $sedikit = $this->hitung($url);

        $this->jalurDenganLaporan($gunung, 10);

        $banyak = $this->hitung($url);

        $this->assertSame($sedikit, $banyak, "Halaman gunung tumbuh: {$sedikit} lalu {$banyak} kueri.");
    }

    /**
     * Perbaikan 1, P-7 (Global Constraints rencana baris 48): setiap rute baru diuji untuk
     * pengguna lain, bukan hanya pemilik sesi. "mengikuti" dan kecocokan dihitung untuk
     * pembaca yang meminta halaman, tidak tertukar dengan pengguna lain.
     */
    public function test_another_users_follow_and_fit_are_not_mixed_up(): void
    {
        $gunung = $this->gunung();
        Trail::factory()->for($gunung)->published()->create();

        $a = User::factory()->create();
        $a->profile()->create(['experience_level' => ExperienceLevel::INTERMEDIATE->value, 'completed_at' => now()]);
        $b = User::factory()->create();

        $this->actingAs($a)->post(route('mountains.follow', $gunung), ['ya' => true])->assertRedirect();

        $responsA = $this->halaman($gunung, $a->fresh());
        $responsA->assertInertia(fn (AssertableInertia $page) => $page->where('mengikuti', true));
        $this->assertNotNull($responsA->viewData('page')['props']['jalur'][0]['fit'], 'Profil A lengkap, fit seharusnya dinilai.');

        $responsB = $this->halaman($gunung, $b->fresh());
        $responsB->assertInertia(fn (AssertableInertia $page) => $page->where('mengikuti', false));
        $this->assertNull($responsB->viewData('page')['props']['jalur'][0]['fit'], 'Profil B belum lengkap, fit seharusnya null, bukan warisan dari A.');
    }

    /**
     * Perbaikan 1, P-10 (Minor, WCAG 1.4.10): nama sumber panjang dan teks bebas lain dari
     * basis data membungkus pada reflow, tidak terpotong di lebar sempit.
     */
    public function test_free_text_from_the_database_wraps(): void
    {
        $berkas = [
            // Upgrade tailwind 4: break-words bernama wrap-break-word; CSS-nya sama (overflow-wrap: break-word).
            'js/components/trails/gunung/KartuStatusGunung.tsx' => ['wrap-break-word text-meta text-muted'],
            'js/components/trails/gunung/KartuIzin.tsx' => ['wrap-break-word text-meta text-muted', 'wrap-break-word text-kecil text-secondary'],
            'js/components/trails/gunung/DaftarJalurGunung.tsx' => ['wrap-break-word text-meta text-secondary'],
            'js/components/trails/gunung/KondisiPendaki.tsx' => ['wrap-break-word text-meta font-bold text-community-900'],
            'js/components/trails/BarisStatus.tsx' => ['wrap-break-word text-meta text-muted'],
            'js/pages/Mountains/Show.tsx' => ['<p className="wrap-break-word">'],
        ];

        foreach ($berkas as $jalur => $potongan) {
            $isi = $this->tanpaKomentarJs(File::get(resource_path($jalur)));

            foreach ($potongan as $satu) {
                $this->assertStringContainsString($satu, $isi, "{$jalur} belum membungkus: {$satu}");
            }
        }
    }

    /**
     * Rincian jalur dan kartu Jelajah menautkan ke halaman ini, jadi halamannya punya kepala
     * turunan ke Jelajah dan bilah tabnya tetap (bukan halaman tugas).
     */
    public function test_the_page_is_a_child_of_jelajah(): void
    {
        $this->assertMatchesRegularExpression('/<PageHeader\s+induk=\{\{ href: trailsIndex\.url\(\), label: \'Jelajah\' \}\}/', $this->sumber());
        $this->assertStringContainsString('Show.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;', $this->sumber());
    }

    private function hitung(string $url): int
    {
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->get($url)->assertOk();
        $jumlah = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $jumlah;
    }
}
