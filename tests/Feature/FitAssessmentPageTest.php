<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\OfficialStatusValue;
use App\Enums\PreferredDuration;
use App\Enums\PreparationCategory;
use App\Enums\PreparationStatus;
use App\Enums\StatusScope;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Models\DataSource;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\RecommendationRule;
use App\Models\Trail;
use App\Models\TrailSegment;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Models\User;
use App\Services\ConsiderationService;
use App\Services\RouteFitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Halaman penilaian kecocokan (spec v3 bagian 6.5 dan 9.3, W3 Tugas 3). Pola penjaganya sama
 * dengan FactorBarsTest dan TrailDetailPageTest: skor dan bobot berhenti di server (BR-09,
 * R-005), faktor yang datanya belum ada tidak terbaca rendah (R-006), dan trip di URL hanya
 * boleh milik pembacanya (PRD §77).
 */
class FitAssessmentPageTest extends TestCase
{
    use RefreshDatabase;
    use SumberReact;

    private function pendaki(): User
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::BEGINNER->value, 'completed_at' => now()]);
        $user->experience()->create(['completed_hikes_count' => 3, 'terrain_experience' => ['FOREST'], 'highest_elevation_gain_m' => 700]);

        return $user->fresh();
    }

    private function jalur(array $atribut = []): Trail
    {
        return Trail::factory()->for(Mountain::factory()->create())->published()->create($atribut);
    }

    /**
     * Jalur terbit dengan satu catatan status resmi yang sedang berlaku, tanpa jenis
     * sumber (tidak relevan untuk test perbaikan putaran 1; pola lengkapnya ada di
     * TrustGrammarTest::jalurDenganStatus()).
     *
     * Dibuat lewat factory, bukan OfficialStatus::create() polos: published() (dipakai
     * jalur()) sudah menulis satu catatan UNKNOWN bawaan ketika jalur belum punya catatan
     * apa pun, dan catatan tanpa effective_at (create() polos) kalah urutan dari catatan
     * bawaan itu (effective_at terisi lewat factory) pada "ORDER BY effective_at DESC" di
     * StatusJalurRingkas: NULL tersusun paling akhir pada DESC di SQLite.
     */
    private function jalurDenganStatus(OfficialStatusValue $nilai, string $sumber = 'Balai Uji'): Trail
    {
        $trail = $this->jalur();

        OfficialStatus::factory()->create([
            'statusable_type' => $trail->getMorphClass(),
            'statusable_id' => $trail->id,
            'scope' => StatusScope::TRAIL->value,
            'status' => $nilai->value,
            'source' => $sumber,
        ]);

        return $trail->fresh();
    }

    /**
     * Jalur terbit tanpa satu pun catatan status resmi yang SEDANG BERLAKU. published()
     * (dipakai jalur()) selalu menulis satu catatan UNKNOWN bawaan ketika jalur belum
     * punya catatan apa pun, jadi "tanpa catatan" dibangun tanpa published(); tetapi
     * gerbang publikasi sendiri menolak jalur yang belum pernah dicatat status resminya
     * sama sekali ("Status resmi belum pernah dicatat."), jadi satu catatan kedaluwarsa
     * tetap dibuat, mengikuti pola TrustGrammarTest::jalurTerbitTanpaStatusBerlaku().
     */
    private function jalurTanpaCatatanStatus(): Trail
    {
        $trail = Trail::factory()->for(Mountain::factory()->create())->create();

        OfficialStatus::factory()->create([
            'statusable_type' => $trail->getMorphClass(),
            'statusable_id' => $trail->id,
            'scope' => StatusScope::TRAIL->value,
            'status' => OfficialStatusValue::OPEN->value,
            'source' => 'Catatan lama',
            'effective_at' => now()->subYear(),
            'expires_at' => now()->subMonth(),
        ]);

        $trail->forceFill(['data_source_id' => DataSource::factory()->create()->id])->saveQuietly();
        $trail->checkpoints()->create(['sequence' => 1, 'name' => 'Pos uji '.$trail->id]);
        $trail->refresh()->update(['is_published' => true]);

        return $trail;
    }

    private function trip(User $user, Trail $trail): TripPlan
    {
        return TripPlan::create([
            'user_id' => $user->id,
            'trail_id' => $trail->id,
            'name' => 'Rencana uji',
            'planned_date' => now()->addDays(15)->toDateString(),
            'end_date' => now()->addDays(16)->toDateString(),
            'participants_count' => 3,
            'trip_type' => TripType::CAMPING->value,
            'status' => TripStatus::PLANNED->value,
        ]);
    }

    private function halaman(User $user, Trail $trail, array $query = []): TestResponse
    {
        return $this->actingAs($user)->get(route('trails.fit', ['trail' => $trail->slug] + $query));
    }

    public function test_a_guest_is_sent_to_login(): void
    {
        $this->get(route('trails.fit', $this->jalur()))->assertRedirect(route('login'));
    }

    /**
     * R-022: jalur draf dan arsip tidak dinilai di halaman ini.
     */
    public function test_only_a_published_trail_is_assessed(): void
    {
        $draf = Trail::factory()->for(Mountain::factory()->create())->unpublished()->create();
        $arsip = $this->jalur(['archived_at' => now()]);

        $this->halaman($this->pendaki(), $draf)->assertNotFound();
        $this->halaman($this->pendaki(), $arsip)->assertNotFound();
    }

    /**
     * BR-09, R-005: tidak ada skor, bobot, atau angka pecahan di props; bobot hanya sampai
     * sebagai kata pengaruh.
     */
    public function test_no_score_or_weight_reaches_the_page(): void
    {
        $respons = $this->halaman($this->pendaki(), $this->jalur())->assertOk();
        $props = $respons->viewData('page')['props'];

        $this->assertCount(7, $props['faktor']);

        foreach ($props['faktor'] as $faktor) {
            $this->assertSame(['kunci', 'label', 'detail', 'kategori', 'pengaruh', 'jalurIni', 'anda'], array_keys($faktor));
            $this->assertContains($faktor['pengaruh'], ['besar', 'sedang', 'kecil']);
        }

        array_walk_recursive($props, fn ($nilai, $kunci) => $this->assertIsNotFloat($nilai, "Nilai pecahan di props ({$kunci})."));

        foreach (['internalScore', 'internal_score', '"score"', '"weight"', 'probab'] as $terlarang) {
            $this->assertStringNotContainsString($terlarang, $respons->getContent());
        }
    }

    /**
     * Tujuh faktor diurut menurut bobot dari recommendation_rules, seri diputus urutan enum;
     * kata pengaruh besar ≥0,25, sedang ≥0,12, kecil di bawahnya (spec v3 bagian 6.5).
     */
    public function test_the_factors_follow_the_weights_in_the_rules_table(): void
    {
        $user = $this->pendaki();
        $trail = $this->jalur();

        $bawaan = collect($this->halaman($user, $trail)->viewData('page')['props']['faktor']);

        $this->assertSame(['Pengalaman', 'Durasi', 'Medan', 'Tuntutan teknis', 'Tanjakan', 'Navigasi', 'Jenis trip'], $bawaan->pluck('label')->all());
        $this->assertSame(['besar', 'sedang', 'sedang', 'sedang', 'kecil', 'kecil', 'kecil'], $bawaan->pluck('pengaruh')->all());

        foreach (['experience_match' => 0.10, 'navigation_match' => 0.30] as $kunci => $bobot) {
            RecommendationRule::create(['key' => $kunci, 'category' => 'compatibility', 'weight' => $bobot, 'active' => true, 'engine_version' => RouteFitService::ENGINE_VERSION]);
        }

        $diubah = collect($this->halaman($user, $trail)->viewData('page')['props']['faktor']);

        $this->assertSame('Navigasi', $diubah->first()['label']);
        $this->assertSame('besar', $diubah->first()['pengaruh']);
    }

    /**
     * R-006: faktor yang datanya belum ada tertulis "Belum diketahui", tidak pernah perlu
     * perhatian dan tidak pernah sesuai.
     */
    public function test_an_unknown_factor_reads_unknown_not_weak(): void
    {
        $props = $this->halaman($this->pendaki(), $this->jalur(['terrain_character' => null]))->viewData('page')['props'];
        $medan = collect($props['faktor'])->firstWhere('label', 'Medan');

        $this->assertSame('tak-diketahui', $medan['kategori']);
        $this->assertSame('Belum diketahui', $medan['jalurIni']);
        $this->assertGreaterThanOrEqual(1, $props['hitungan']['belumDiketahui']);

        $baris = $this->tanpaKomentarJs(File::get(resource_path('js/components/trails/FaktorKecocokan.tsx')));
        $this->assertStringContainsString("'tak-diketahui': { kata: 'Belum diketahui', ikon: 'question'", $baris);
    }

    public function test_another_hikers_trip_is_refused(): void
    {
        $trail = $this->jalur();
        $orangLain = $this->trip(User::factory()->create(), $trail);

        $this->halaman($this->pendaki(), $trail, ['trip' => $orangLain->id])->assertForbidden();
    }

    public function test_a_trip_for_another_trail_is_not_found(): void
    {
        $user = $this->pendaki();
        $trip = $this->trip($user, $this->jalur());

        $this->halaman($user, $this->jalur(), ['trip' => $trip->id])->assertNotFound();
    }

    /**
     * Dengan trip milik sendiri penilaiannya untuk rencana itu: bahan rencananya disebut,
     * butir persiapan kritis yang belum dikonfirmasi menjadi langkah, dan aksi utamanya
     * melanjutkan persiapan.
     */
    public function test_the_own_trip_is_assessed_for_its_plan(): void
    {
        $user = $this->pendaki();
        $trail = $this->jalur();
        $trip = $this->trip($user, $trail);

        TripPreparationItem::create(['trip_plan_id' => $trip->id, 'category' => PreparationCategory::EQUIPMENT->value, 'label' => 'Headlamp cadangan', 'is_critical' => true, 'status' => PreparationStatus::NOT_CONFIRMED->value]);
        TripPreparationItem::create(['trip_plan_id' => $trip->id, 'category' => PreparationCategory::EQUIPMENT->value, 'label' => 'Jas hujan', 'is_critical' => true, 'status' => PreparationStatus::CONFIRMED->value]);

        $this->halaman($user, $trail, ['trip' => $trip->id])->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Trails/Fit')
            ->where('penilaian.denganRencana', true)
            ->where('dinilaiDari.rencana', fn (string $rencana) => str_contains($rencana, 'Camping') && str_contains($rencana, '3 orang'))
            ->where('yangBisaDilakukan.0.judul', 'Selesaikan 1 butir persiapan kritis')
            ->where('yangBisaDilakukan.0.keterangan', 'Headlamp cadangan')
            ->where('aksi.label', 'Lanjutkan persiapan'));
    }

    /**
     * Pembaca tanpa profil tidak dinilai diam-diam; yang ditawarkan jalan mengisi profil.
     */
    public function test_a_reader_without_a_profile_is_invited_not_judged(): void
    {
        $this->halaman(User::factory()->create(), $this->jalur())->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->where('penilaian', null)
            ->missing('faktor'));

        $this->assertStringContainsString('Isi profil pendaki', File::get(resource_path('js/pages/Trails/Fit.tsx')));
    }

    /**
     * "Kenapa bukan Cocok?" disusun dari keadaan faktor dengan aturan tertulis: jumlah faktor
     * yang perlu perhatian, faktor berpengaruh besar di antaranya, dan akibatnya pada label.
     */
    public function test_why_not_cocok_is_written_from_the_factors(): void
    {
        $props = $this->halaman($this->pendaki(), $this->jalur(['elevation_gain_m' => 1650, 'distance_km' => 9.7, 'estimated_duration_minutes' => 450]))->viewData('page')['props'];

        $this->assertNotSame('COCOK', $props['penilaian']['label']);
        $this->assertStringContainsString('faktor perlu perhatian', $props['kenapaBukanCocok']);
        $this->assertMatchesRegularExpression('/(Kurang cocok, berapa pun faktor lainnya|tetap relevan asal Anda bersiap|belum cukup untuk Perlu persiapan)\.$/', $props['kenapaBukanCocok']);
    }

    /**
     * Anggaran kueri: halaman tidak tumbuh bersama simpanan dan butir persiapan pembacanya.
     */
    public function test_the_page_does_not_grow_with_saved_trails_or_preparation_items(): void
    {
        $user = $this->pendaki();
        $trail = $this->jalur();
        $trip = $this->trip($user, $trail);
        $this->actingAs($user);
        $url = route('trails.fit', ['trail' => $trail->slug, 'trip' => $trip->id]);

        // Satu simpanan dan satu butir lebih dulu: relasi yang dimuat di muka baru menagih
        // kuerinya ketika barisnya ada, jadi nol lawan enam bukan ukuran pertumbuhan.
        app(ConsiderationService::class)->toggle($user, $this->jalur());
        TripPreparationItem::create(['trip_plan_id' => $trip->id, 'category' => PreparationCategory::EQUIPMENT->value, 'label' => 'Butir awal', 'is_critical' => true, 'status' => PreparationStatus::NOT_CONFIRMED->value]);

        $this->get($url)->assertOk();
        $sedikit = $this->hitung($url);

        foreach (range(1, 6) as $i) {
            app(ConsiderationService::class)->toggle($user, $this->jalur());
            TripPreparationItem::create(['trip_plan_id' => $trip->id, 'category' => PreparationCategory::EQUIPMENT->value, 'label' => 'Butir '.$i, 'is_critical' => true, 'status' => PreparationStatus::NOT_CONFIRMED->value]);
        }

        $banyak = $this->hitung($url);

        $this->assertSame($sedikit, $banyak, "Halaman penilaian tumbuh: {$sedikit} lalu {$banyak} kueri.");
    }

    /**
     * Halaman tugas dengan bar aksi (spec v3 bagian 5.1): bilah tab disembunyikan, tombol
     * kembali menuju jalurnya, dan BR-11 tertulis.
     */
    public function test_the_page_is_a_task_page_that_returns_to_its_trail(): void
    {
        $halaman = $this->tanpaKomentarJs(File::get(resource_path('js/pages/Trails/Fit.tsx')));

        $this->assertStringContainsString('Fit.layout = (page: ReactNode) => <AppLayout bilahTab={false}>{page}</AppLayout>;', $halaman);
        $this->assertStringContainsString('<PageHeader induk={{ href: jalur.url, label: jalur.nama }} title="Penilaian kecocokan">', $halaman);
        $this->assertStringContainsString('<BarAksi label="Aksi penilaian kecocokan">', $halaman);
        $this->assertStringContainsString('Label tidak naik hanya karena Anda menyelesaikan pendakian.', $halaman);
    }

    /**
     * P-1 (Important, R-006): status resmi UNKNOWN bukan syarat yang terpenuhi. Sebelum
     * perbaikan, OfficialStatusValue::excludesFromRecommendation() hanya benar untuk
     * CLOSED, sehingga UNKNOWN ikut tertandai terpenuhi dan pembaca layar mendengar
     * "Terpenuhi" untuk sesuatu yang justru belum diketahui siapa pun. Kalimatnya juga
     * menyebut sumber dan waktu catatan penentu bila ada, atau mengaku belum ada catatan.
     */
    public function test_an_unknown_official_status_is_not_read_as_a_fulfilled_condition(): void
    {
        $takDiketahui = $this->jalurDenganStatus(OfficialStatusValue::UNKNOWN, 'Pos Pemantau');
        $tutup = $this->jalurDenganStatus(OfficialStatusValue::CLOSED, 'Balai Tutup');
        $dibatasi = $this->jalurDenganStatus(OfficialStatusValue::RESTRICTED);
        $tanpaCatatan = $this->jalurTanpaCatatanStatus();

        $user = $this->pendaki();

        $syaratTakDiketahui = $this->halaman($user, $takDiketahui)->viewData('page')['props']['syaratDasar'][0];
        $syaratTutup = $this->halaman($user, $tutup)->viewData('page')['props']['syaratDasar'][0];
        $syaratDibatasi = $this->halaman($user, $dibatasi)->viewData('page')['props']['syaratDasar'][0];
        $syaratTanpaCatatan = $this->halaman($user, $tanpaCatatan)->viewData('page')['props']['syaratDasar'][0];

        $this->assertSame('tak-diketahui', $syaratTakDiketahui['keadaan']);
        $this->assertSame('gagal', $syaratTutup['keadaan']);
        $this->assertSame('terpenuhi', $syaratDibatasi['keadaan']);
        $this->assertSame('tak-diketahui', $syaratTanpaCatatan['keadaan']);

        $this->assertStringContainsString('Balai Tutup', $syaratTutup['teks']);
        $this->assertStringContainsString('Pos Pemantau', $syaratTakDiketahui['teks']);
        $this->assertStringContainsString('Belum ada catatan pengelola yang berlaku.', $syaratTanpaCatatan['teks']);
    }

    /**
     * P-1: keadaan belum diketahui memakai ikon question dan awalan pembaca layar
     * "Belum diketahui:", tidak pernah tanda centang.
     */
    public function test_the_condition_list_never_marks_an_unknown_state_with_a_checkmark(): void
    {
        $syarat = $this->tanpaKomentarJs(File::get(resource_path('js/components/trails/kecocokan/SyaratDasar.tsx')));

        $this->assertStringContainsString("'tak-diketahui': { ikon: 'question'", $syarat);
        $this->assertStringContainsString('Belum diketahui: ', $syarat);
        $this->assertStringNotContainsString("'tak-diketahui': { ikon: 'check'", $syarat);
    }

    /**
     * P-2 (Important, spec v3 bagian 6.5): pembatasan segmen yang menurunkan Cocok ke
     * Perlu persiapan disebut di "Kenapa bukan Cocok?", bukan disembunyikan di balik
     * kalimat generik "Tidak ada syarat dasar yang gagal". Ketujuh faktor dibuat kuat
     * (trail easy(), pendaki pemula dengan preferensi yang seluruhnya di atas kebutuhan
     * jalur) supaya pembatasan segmen adalah satu-satunya sebab labelnya bukan Cocok.
     */
    public function test_a_segment_restriction_that_demotes_a_would_be_cocok_trail_is_named(): void
    {
        $user = $this->pendaki();
        $user->preference()->create(['preferred_duration' => PreferredDuration::THREE_PLUS_DAY->value, 'preferred_trip_type' => TripType::TEKTOK->value]);
        $user = $user->fresh();

        $trail = Trail::factory()->for(Mountain::factory()->create())->published()->easy()->create();
        $segmen = TrailSegment::factory()->create(['trail_id' => $trail->id, 'sequence' => 1, 'name' => 'Puncak']);
        OfficialStatus::create([
            'statusable_type' => $segmen->getMorphClass(),
            'statusable_id' => $segmen->id,
            'scope' => StatusScope::SEGMENT->value,
            'status' => OfficialStatusValue::RESTRICTED->value,
            'source' => 'Balai Uji',
        ]);

        $props = $this->halaman($user, $trail)->viewData('page')['props'];

        $this->assertSame('PERLU_PERSIAPAN', $props['penilaian']['label']);
        $this->assertStringContainsString('Puncak', $props['kenapaBukanCocok']);

        $mentah = (string) json_encode($props);

        foreach (['RESTRICTED', 'OPEN', 'CLOSED', 'UNKNOWN'] as $kata) {
            $this->assertStringNotContainsString($kata, $mentah, "Nilai enum mentah '{$kata}' bocor ke props halaman.");
        }
    }

    /**
     * P-3 (Minor): jalur yang gagal syarat dasar tidak diberi hitungan atau daftar
     * keadaan faktor. Mesin tetap menghitung ketujuh faktor untuk peringkat kandidat
     * lain walau labelnya null, tetapi menampilkannya di sini membuat pembaca menyangka
     * jalur ini sudah dinilai padahal penilaiannya berhenti di lapis 1.
     */
    public function test_an_ineligible_trail_sends_no_factor_counts(): void
    {
        $trail = $this->jalurDenganStatus(OfficialStatusValue::CLOSED);
        $props = $this->halaman($this->pendaki(), $trail)->viewData('page')['props'];

        $this->assertFalse($props['penilaian']['layak']);
        $this->assertArrayNotHasKey('faktor', $props);
        $this->assertArrayNotHasKey('hitungan', $props);

        $halaman = $this->tanpaKomentarJs(File::get(resource_path('js/pages/Trails/Fit.tsx')));
        $this->assertStringContainsString('Faktor kecocokan tidak dinilai karena syarat dasar belum terpenuhi.', $halaman);
    }

    /**
     * P-6 (Minor): 'kunci' dikirim server tapi sebelumnya tidak ada di tipe FaktorBaris
     * dan tidak dipakai sebagai key React, sehingga daftar faktor memakai label sebagai
     * key padahal server sudah menyediakan kunci yang lebih stabil.
     */
    public function test_each_factor_row_uses_its_stable_key_from_the_server(): void
    {
        $faktor = $this->tanpaKomentarJs(File::get(resource_path('js/components/trails/FaktorKecocokan.tsx')));

        $this->assertStringContainsString('kunci?:', $faktor);
        $this->assertMatchesRegularExpression('/key=\{f\.kunci/', $faktor);
    }

    /**
     * P-7 (Minor): trip yang sudah selesai atau dibatalkan tidak lagi ditawari
     * "Lanjutkan persiapan", karena persiapannya sudah tidak relevan untuk dilanjutkan.
     */
    public function test_a_completed_or_cancelled_trip_does_not_offer_to_continue_preparation(): void
    {
        $user = $this->pendaki();
        $trail = $this->jalur();

        foreach ([TripStatus::COMPLETED, TripStatus::CANCELLED] as $status) {
            $trip = $this->trip($user, $trail);
            $trip->update(['status' => $status->value]);

            $props = $this->halaman($user, $trail, ['trip' => $trip->id])->viewData('page')['props'];

            $this->assertSame('Rencanakan trip', $props['aksi']['label'], "Trip berstatus {$status->value} seharusnya tidak menawarkan Lanjutkan persiapan.");
        }
    }

    /**
     * "Bandingkan dengan jalur lain" hanya menyebut simpanan yang masih terbit
     * (Trail::terbit(), predikat satu sumber tinjauan Tugas 1 M3): jalur yang kembali menjadi
     * draf sesudah disimpan tidak dibandingkan (R-022), jadi tidak ditawarkan. Jalur arsip sudah
     * tersaring lebih awal oleh daftar simpanan aktif (ConsiderationService::simpanan()).
     */
    public function test_the_compare_step_only_names_saved_trails_that_are_still_published(): void
    {
        $user = $this->pendaki();
        $trail = $this->jalur();
        $terbit = $this->jalur(['name' => 'Jalur Terbit Uji']);
        $draf = $this->jalur(['name' => 'Jalur Draf Uji']);
        app(ConsiderationService::class)->toggle($user, $terbit);
        app(ConsiderationService::class)->toggle($user, $draf);
        $draf->forceFill(['is_published' => false])->saveQuietly();

        $langkah = collect($this->halaman($user, $trail)->assertOk()->viewData('page')['props']['yangBisaDilakukan'])
            ->firstWhere('judul', 'Bandingkan dengan jalur lain');

        $this->assertSame('Jalur Terbit Uji ada di simpanan Anda.', $langkah['keterangan']);
    }

    /**
     * P-8 (Minor): baris dalam kelompok "Perlu perhatian" di panel alasan rekomendasi
     * tidak mengulang kata judul kelompoknya; kata itu sudah terbaca sekali di heading.
     */
    public function test_the_reason_panel_does_not_repeat_the_group_heading_on_every_row(): void
    {
        $panel = $this->tanpaKomentarJs(File::get(resource_path('js/components/discovery/PanelAlasan.tsx')));

        $this->assertStringContainsString('tampilkanKata={false}', $panel);
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
