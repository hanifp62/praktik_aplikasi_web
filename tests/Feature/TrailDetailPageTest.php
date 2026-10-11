<?php

namespace Tests\Feature;

use App\Enums\CheckpointType;
use App\Enums\PreparationCategory;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Models\Checkpoint;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\PermitRequirement;
use App\Models\PreparationItem;
use App\Models\PreparationTemplate;
use App\Models\Profile;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Models\UserExperience;
use App\Models\WeatherSnapshot;
use App\Services\ConsiderationService;
use App\Services\PreparationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Halaman rinci jalur (React): kontrak server yang tidak dijaga test lain.
 */
class TrailDetailPageTest extends TestCase
{
    use RefreshDatabase;

    private function jalur(array $atribut = []): Trail
    {
        return Trail::factory()->for(Mountain::factory()->create(['name' => 'Gunung Uji']))->published()->create($atribut);
    }

    private function pendakiBerprofil(): User
    {
        $user = User::factory()->create();
        Profile::factory()->for($user)->create();
        UserExperience::factory()->for($user)->create(['completed_hikes_count' => 3]);

        return $user->fresh();
    }

    public function test_an_archived_trail_answers_not_found(): void
    {
        $jalur = $this->jalur(['archived_at' => now()]);

        $this->actingAs(User::factory()->create())->get(route('trails.show', $jalur))->assertNotFound();
    }

    public function test_the_page_is_titled_after_the_trail_and_its_mountain(): void
    {
        $jalur = $this->jalur(['name' => 'Jalur Selo']);

        $this->actingAs(User::factory()->create())
            ->get(route('trails.show', $jalur))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Trails/Show')
                ->where('title', 'Jalur Selo - Gunung Uji'));
    }

    /**
     * BR-09, R-005: skor, bobot, dan probabilitas tidak pernah sampai ke klien; faktor
     * hanya membawa kategorinya.
     */
    public function test_no_score_or_weight_reaches_the_page(): void
    {
        $isi = $this->actingAs($this->pendakiBerprofil())->get(route('trails.show', $this->jalur()))->assertOk();

        $kecocokan = $isi->viewData('page')['props']['kecocokan'];
        $this->assertSame(['label', 'labelText', 'deskripsi', 'layak', 'rencana', 'alasan', 'mengapaSesuai', 'perluDiperhatikan', 'celahPersiapan', 'tangga', 'penilaianUrl'], array_keys($kecocokan));
        array_walk_recursive($kecocokan, fn ($nilai, $kunci) => $this->assertIsNotFloat($nilai, "Nilai pecahan di kecocokan ({$kunci})."));

        foreach (['internal_score', 'internalScore', '"score"', '"weight"', 'probab'] as $terlarang) {
            $this->assertStringNotContainsString($terlarang, $isi->getContent());
        }
    }

    /**
     * Setiap status dan kondisi menyebut sumber dan waktunya (§92, §93). Status kosong
     * bukan OPEN, dan OPEN bukan jaminan keselamatan (R-012).
     *
     * Sejak tinjauan akhir W3 (W3-2c, I1) waktu status resmi dikirim sebagai `waktu` bersama
     * `awalanWaktu` dan `kesegaran`, bukan `diverifikasi`: nama lama menyebut setiap waktu
     * "diverifikasi", padahal waktunya bisa tanggal terbit atau mulai berlaku.
     */
    public function test_each_condition_names_its_source_and_time(): void
    {
        $props = $this->actingAs(User::factory()->create())
            ->get(route('trails.show', $this->jalur()))
            ->viewData('page')['props'];

        $this->assertNotSame('', $props['statusResmi']['source']);
        $this->assertArrayHasKey('waktu', $props['statusResmi']);
        $this->assertArrayHasKey('awalanWaktu', $props['statusResmi']);
        $this->assertArrayHasKey('kesegaran', $props['statusResmi']);
        $this->assertNotNull($props['cuaca']['source']);

        // T6-M4 tinjauan akhir W3: larik dipastikan tidak kosong dulu, kalau tidak foreach di
        // bawah lulus tanpa memeriksa apa pun.
        $this->assertNotEmpty($props['sumber']);

        foreach ($props['sumber'] as $baris) {
            $this->assertSame(['bagian', 'jenis', 'isi', 'url'], array_keys($baris));
        }

        // Sejak W3 label sumbernya mengikuti jenis sumber catatan (R-009), bukan "Resmi"
        // untuk setiap catatan di tabel status resmi. Nilainya yang ditegaskan, bukan keberadaan
        // kuncinya (T6-M4): catatan bawaan factory tidak punya sumber data, jadi jenisnya null,
        // tidak pernah jatuh ke OFFICIAL.
        $this->assertNull($props['statusResmi']['jenisSumber']);
        $this->assertNull($props['sumber'][0]['jenis']);

        $kondisi = File::get(resource_path('js/components/trails/rinci/Kondisi.tsx'));
        $this->assertStringContainsString('Status resmi menjelaskan ketentuan pengelola, bukan jaminan keselamatan.', $kondisi);
        $this->assertStringContainsString('<LabelSumber jenis={status.jenisSumber} />', $kondisi);
    }

    /**
     * Regresi BF-7c (ruling audit-fix-B BF-5, M-1 sisa): jam prakiraan cuaca di rincian
     * jalur ditulis bertitik (07.00), bukan berkolon, kaidah bahasa Indonesia untuk jam.
     * Diuji dengan snapshot sungguhan lewat permintaan HTTP nyata, bukan sekadar sumber.
     */
    public function test_the_forecast_time_is_written_with_a_dot(): void
    {
        $jalur = $this->jalur();

        WeatherSnapshot::factory()->create([
            'adm4_code' => $jalur->weather_adm4_code,
            'forecast_at' => now()->addHour(),
            'fetched_at' => now(),
        ]);

        $cuaca = $this->actingAs(User::factory()->create())
            ->get(route('trails.show', $jalur))
            ->viewData('page')['props']['cuaca'];

        $this->assertTrue($cuaca['available']);
        $waktu = $cuaca['forecast'][0]['waktu'];

        $this->assertMatchesRegularExpression('/^\d{2} [^\s0-9]+ \d{2}\.\d{2}$/u', $waktu, "Jam prakiraan tidak berbentuk \"dd Bulan HH.ii\": {$waktu}");
        $this->assertStringNotContainsString(':', $waktu);
    }

    /**
     * R-013: kegagalan sumber cuaca tidak pernah menghasilkan cuaca karangan.
     */
    public function test_missing_weather_is_reported_not_invented(): void
    {
        $cuaca = $this->actingAs(User::factory()->create())
            ->get(route('trails.show', $this->jalur()))
            ->viewData('page')['props']['cuaca'];

        $this->assertFalse($cuaca['available']);
        $this->assertSame('Data cuaca tidak tersedia.', $cuaca['message']);
        $this->assertArrayNotHasKey('forecast', $cuaca);
    }

    /**
     * Tanpa profil tidak ada penilaian diam-diam; yang ditawarkan jalan melengkapinya.
     */
    public function test_a_reader_without_a_profile_is_invited_not_judged(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('trails.show', $this->jalur()))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('kecocokan', null)
                ->where('auth.user.hasCompletedProfile', false));

        $this->assertStringContainsString('Isi profil sekarang', File::get(resource_path('js/components/trails/rinci/CocokkahUntukSaya.tsx')));
        $this->assertStringContainsString('<CocokkahUntukSaya kecocokan={kecocokan}', File::get(resource_path('js/pages/Trails/Show.tsx')));
    }

    /**
     * Simpan dan Banding dari halaman rinci: keadaannya dari simpanan, dan Banding hanya
     * ditawarkan ketika ada simpanan lain yang terbit untuk dibandingkan (DK-05, DK-40).
     */
    public function test_the_saved_state_and_the_compare_offer_follow_the_shortlist(): void
    {
        $user = User::factory()->create();
        $jalur = $this->jalur();

        $this->actingAs($user)->get(route('trails.show', $jalur))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('ditimbang', false)->where('adaCatatan', false)->where('bandingUrl', null));

        $this->actingAs($user)->from(route('trails.show', $jalur))->post(route('trails.saved.store', $jalur->id))
            ->assertRedirect(route('trails.show', $jalur))
            ->assertSessionHas('pengumuman');

        $this->actingAs($user)->get(route('trails.show', $jalur))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('ditimbang', true)->where('bandingUrl', null));

        $lain = $this->jalur();
        app(ConsiderationService::class)->toggle($user, $lain);

        $this->actingAs($user)->get(route('trails.show', $jalur))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('bandingUrl', route('trails.compare', ['trails' => $jalur->id.','.$lain->id])));

        $this->assertStringContainsString('{bandingUrl ? (', File::get(resource_path('js/pages/Trails/Show.tsx')));
    }

    /**
     * Halaman React merender markupnya di klien; struktur judulnya dijaga dari sumber:
     * tepat satu h1 per halaman (lewat PageHeader), dan isian salin-manual berlabel.
     */
    public function test_each_trail_page_has_exactly_one_first_level_heading(): void
    {
        foreach (['Show', 'Awaiting', 'Compare', 'Saved'] as $halaman) {
            $isi = File::get(resource_path("js/pages/Trails/{$halaman}.tsx"));

            $this->assertSame(1, substr_count($isi, '<PageHeader') + substr_count($isi, '<h1'), "{$halaman} harus punya tepat satu h1.");
        }

        $salin = File::get(resource_path('js/components/ui/TombolSalin.tsx'));
        $this->assertStringContainsString('<Label htmlFor={id}>', $salin);
        $this->assertStringContainsString('<TextInput id={id}', $salin);
        $this->assertStringContainsString('role="status"', $salin);
    }

    /**
     * Angka sekali jalan naik: jarak, tanjakan, dan lama naik. Turunan tidak ditulis karena
     * data pemilik menyimpan turunan pulang (ruling W3). Angka kosong ditulis apa adanya.
     */
    public function test_missing_figures_are_stated_honestly(): void
    {
        $jalur = $this->jalur(['elevation_loss_m' => 800]);
        $jalur->forceFill(['estimated_duration_minutes' => null])->saveQuietly();

        $angka = collect($this->actingAs(User::factory()->create())
            ->get(route('trails.show', $jalur))
            ->viewData('page')['props']['angkaNaik'])->keyBy('label');

        $this->assertSame(['Jarak', 'Tanjakan', 'Naik'], $angka->keys()->all());
        $this->assertSame(['label' => 'Naik', 'nilai' => 'Belum tersedia', 'ada' => false], $angka['Naik']);
    }

    /**
     * Spec v3 bagian 6.4: "untuk rencana ini" bila pembaca punya trip terencana yang belum
     * lewat di jalur ini; tanpa itu kecocokan dasar. Trip lewat atau di jalur lain tidak dipakai.
     */
    public function test_the_fit_is_for_the_readers_plan_when_one_exists(): void
    {
        $user = $this->pendakiBerprofil();
        $jalur = $this->jalur();

        TripPlan::factory()->for($user)->for($jalur)->create(['planned_date' => now()->subDays(3)->toDateString(), 'status' => TripStatus::PLANNED->value]);
        TripPlan::factory()->for($user)->for($this->jalur())->create(['planned_date' => now()->addDays(5)->toDateString()]);

        $this->actingAs($user)->get(route('trails.show', $jalur))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('kecocokan.rencana', null)
            ->where('kecocokan.penilaianUrl', route('trails.fit', ['trail' => $jalur->slug])));

        $trip = TripPlan::factory()->for($user)->for($jalur)->create([
            'planned_date' => now()->addDays(20)->toDateString(),
            'end_date' => now()->addDays(21)->toDateString(),
            'trip_type' => TripType::CAMPING->value,
        ]);

        $this->actingAs($user)->get(route('trails.show', $jalur))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('kecocokan.rencana', $trip->fresh()->ringkasan())
            ->where('kecocokan.penilaianUrl', route('trails.fit', ['trail' => $jalur->slug, 'trip' => $trip->id])));

        // Ruling W3-6a: begitu PreDepartureCheck mengubah status trip menjadi "Siap
        // berangkat" beberapa hari sebelum berangkat, trip itu tetap trip terdekat, bukan
        // turun diam-diam ke kecocokan dasar. Direncanakan lebih dekat dari trip CAMPING
        // di atas, jadi ia yang jadi trip terdekat berikutnya.
        $siap = TripPlan::factory()->for($user)->for($jalur)->create([
            'planned_date' => now()->addDays(2)->toDateString(),
            'status' => TripStatus::READY_FOR_DEPARTURE->value,
        ]);

        $this->actingAs($user)->get(route('trails.show', $jalur))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('kecocokan.rencana', $siap->fresh()->ringkasan())
            ->where('kecocokan.penilaianUrl', route('trails.fit', ['trail' => $jalur->slug, 'trip' => $siap->id])));
    }

    /**
     * Keamanan (ruling W3-6a): trip pengguna lain di jalur yang sama tidak pernah menjadi
     * rencana rincian pembaca. Pembaca tanpa trip sendiri tetap melihat kecocokan dasar,
     * dan penilaianUrl tidak membawa id trip orang lain.
     */
    public function test_another_users_trip_on_the_same_trail_is_ignored(): void
    {
        $pembaca = $this->pendakiBerprofil();
        $jalur = $this->jalur();
        $lain = TripPlan::factory()->for(User::factory())->for($jalur)->create(['planned_date' => now()->addDays(5)->toDateString()]);

        // Kecocokan dasar dan penilaianUrl TANPA parameter trip membuktikan $lain tidak
        // terhitung sebagai trip terdekat pembaca; andai terhitung, penilaianUrl akan
        // membawa ['trip' => $lain->id] seperti pada kasus trip milik sendiri di atas.
        $this->actingAs($pembaca)->get(route('trails.show', $jalur))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('kecocokan.rencana', null)
            ->where('kecocokan.penilaianUrl', route('trails.fit', ['trail' => $jalur->slug])));
    }

    /**
     * Temuan tangkapan W3 butir 6 (ruling W3-G1 butir d): alasan() mengambil detail faktor
     * lemah pertama sebagai kalimat utama "Cocokkah untuk saya?", dan what_to_watch menulis
     * detail faktor lemah yang sama lagi di dalam daftarnya. Jalur dan profil di sini dipilih
     * supaya experience_match persis menjadi faktor lemah itu (fisik jauh di atas pengalaman
     * pemula), dengan dua faktor tidak diketahui di depannya (durasi dan tipe perjalanan)
     * sehingga tanpa perbaikan, duplikatnya jatuh tepat di tiga butir pertama yang tampil.
     */
    public function test_the_headline_reason_is_not_repeated_as_the_first_watch_item(): void
    {
        $pembaca = $this->pendakiBerprofil();
        $jalur = $this->jalur([
            'elevation_gain_m' => 3000,
            'distance_km' => 40,
            'estimated_duration_minutes' => 2000,
        ]);
        OfficialStatus::factory()->create(['statusable_type' => Trail::class, 'statusable_id' => $jalur->id]);

        $props = $this->actingAs($pembaca)->get(route('trails.show', $jalur))->viewData('page')['props'];

        $this->assertSame('Beban fisik jalur ini lebih berat dibandingkan tingkat pengalaman yang Anda isi.', $props['kecocokan']['alasan']);
        $this->assertNotContains($props['kecocokan']['alasan'], $props['kecocokan']['perluDiperhatikan']);
    }

    /**
     * R-006: tuntutan fisik yang tidak diketahui tidak diberi ruas terendah; katanya menyebut
     * belum tersedia. Keadaan lingkungan tidak dikarang karena belum ada datanya (R-007).
     */
    public function test_the_challenge_rows_never_turn_unknown_into_low(): void
    {
        $jalur = $this->jalur();
        $jalur->forceFill(['distance_km' => null, 'elevation_gain_m' => null, 'estimated_duration_minutes' => null])->saveQuietly();

        $tantangan = collect($this->actingAs(User::factory()->create())
            ->get(route('trails.show', $jalur))
            ->viewData('page')['props']['tantangan'])->keyBy('judul');

        $this->assertSame(['Tuntutan fisik', 'Tuntutan teknis', 'Navigasi', 'Medan', 'Sumber air', 'Area kemah'], $tantangan->keys()->all());
        $this->assertSame(['judul' => 'Tuntutan fisik', 'kata' => 'Belum tersedia', 'ruas' => null], $tantangan['Tuntutan fisik']);
        $this->assertSame(2, $tantangan['Tuntutan teknis']['ruas']);
    }

    /**
     * Profil elevasi membawa keterangan kalimat dan bagian tercuram yang diarsir (spec v3
     * bagian 6.4). Jalur yang tidak naik tidak diberi bagian tercuram; kurang dari dua
     * titik bukan profil.
     *
     * Diperbarui untuk ruling W6-6h (m-2): angka dan satuannya, juga "km" dengan angka posisi
     * sesudahnya, kini diikat spasi tak terputus U+00A0; di 320 px "546" sempat terpisah dari
     * "m dalam 2,4 km". Kata dan angkanya tidak berubah.
     */
    public function test_the_profile_names_its_steepest_part(): void
    {
        $gede = $this->jalur(['elevation_profile' => [
            ['km' => 0, 'm' => 1340], ['km' => 1.5, 'm' => 1575], ['km' => 2.8, 'm' => 1620],
            ['km' => 5.3, 'm' => 2150], ['km' => 7.2, 'm' => 2395], ['km' => 9.7, 'm' => 2958],
        ]]);
        $datar = $this->jalur(['elevation_profile' => [['km' => 0, 'm' => 900], ['km' => 4, 'm' => 900]]]);
        $satuTitik = $this->jalur(['elevation_profile' => [['km' => 0, 'm' => 900]]]);
        $pembaca = User::factory()->create();

        $profil = $this->actingAs($pembaca)->get(route('trails.show', $gede))->viewData('page')['props']['profilElevasi'];

        $this->assertSame(['dariKm' => 7.3, 'sampaiKm' => 9.7, 'naik' => 546], $profil['tercuram']);
        $this->assertSame("Dari 1.340\u{a0}m sampai titik tertinggi 2.958\u{a0}m dalam 9,7\u{a0}km. Bagian tercuram, km\u{a0}7,3 sampai 9,7, naik 546\u{a0}m dalam 2,4\u{a0}km (diarsir).", $profil['keterangan']);
        $this->assertSame([], $profil['pos'], 'Tanpa garis rute tidak ada jarak pos, jadi tidak ada penanda tebakan.');

        $this->assertNull($this->actingAs($pembaca)->get(route('trails.show', $datar))->viewData('page')['props']['profilElevasi']['tercuram']);
        $this->assertNull($this->actingAs($pembaca)->get(route('trails.show', $satuTitik))->viewData('page')['props']['profilElevasi']);
    }

    /**
     * Pos berkemah dan tujuan membawa aksen (DESIGN.md, satu aksen). Tanpa garis rute dan
     * rekaman pendaki, jarak dan waktu dari awal tidak ditulis, bukan dikarang.
     */
    public function test_posts_carry_the_accent_for_camp_and_destination(): void
    {
        $jalur = Trail::factory()->for(Mountain::factory())->create();

        foreach ([['Basecamp', CheckpointType::BASECAMP], ['Pos 1', CheckpointType::POS], ['Kandang', CheckpointType::CAMPGROUND], ['Puncak', CheckpointType::SUMMIT]] as $i => [$nama, $jenis]) {
            Checkpoint::factory()->for($jalur)->create(['sequence' => $i + 1, 'name' => $nama, 'checkpoint_type' => $jenis->value, 'elevation_m' => 1000 + $i * 300]);
        }

        $jalur->refresh();
        $jalur->officialStatuses()->create(['scope' => 'TRAIL', 'status' => 'OPEN', 'source' => 'Uji']);
        $jalur->forceFill(['is_published' => true])->saveQuietly();

        $pos = $this->actingAs(User::factory()->create())->get(route('trails.show', $jalur))->viewData('page')['props']['pos'];

        $this->assertSame([null, null, 'kemah', 'tujuan'], array_column($pos, 'aksen'));
        $this->assertSame(['1.000 m', '1.300 m', '1.600 m', '1.900 m'], array_column($pos, 'elevasi'));
        $this->assertSame([null, null, null, null], array_column($pos, 'km'));
        $this->assertSame([null, null, null, null], array_column($pos, 'waktu'));
    }

    /**
     * R-013: prakiraan untuk tanggal trip yang belum terbit tidak diganti prakiraan hari ini;
     * halaman menyebut kapan ia mulai terbit (ruling W3-6b, kasus "menunggu", sebelum H-3).
     */
    public function test_the_forecast_note_waits_for_the_trip_date(): void
    {
        $user = $this->pendakiBerprofil();
        $jalur = $this->jalur();
        $tanggal = now('Asia/Jakarta')->addDays(15);
        TripPlan::factory()->for($user)->for($jalur)->create(['planned_date' => $tanggal->toDateString()]);

        $this->actingAs($user)->get(route('trails.show', $jalur))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('cuaca.untukTrip.status', 'menunggu')
            ->where('cuaca.untukTrip.pesan', 'Prakiraan untuk '.$tanggal->translatedFormat('j M').' belum terbit. BMKG menerbitkan 3 hari ke depan; prakiraan tanggal Anda muncul di sini mulai '.$tanggal->copy()->subDays(3)->translatedFormat('j M').'.'));
    }

    /**
     * m3 tinjauan akhir W3: ringkasan rencana yang melewati batas bulan atau tahun menulis
     * bulan (dan tahun) tanggal mulainya; "30 sampai 2 Okt 2026" terbaca seperti 30 Oktober.
     */
    public function test_a_plan_summary_names_the_month_and_year_when_they_change(): void
    {
        $rencana = fn (string $mulai, ?string $selesai) => (new TripPlan)->forceFill([
            'trip_type' => TripType::CAMPING->value,
            'planned_date' => $mulai,
            'end_date' => $selesai,
            'participants_count' => null,
        ])->ringkasan();

        $this->assertSame('Camping, 3 sampai 4 Okt 2026', $rencana('2026-10-03', '2026-10-04'));
        $this->assertSame('Camping, 30 Sep sampai 2 Okt 2026', $rencana('2026-09-30', '2026-10-02'));
        $this->assertSame('Camping, 31 Des 2026 sampai 1 Jan 2027', $rencana('2026-12-31', '2027-01-01'));
        $this->assertSame('Camping, 5 Okt 2026', $rencana('2026-10-05', null));
    }

    /**
     * T6-M2 tinjauan akhir W3: catatan prakiraan untuk tanggal trip (R-013) tidak bergantung
     * pada profil pendaki. Hanya penilaian kecocokan yang butuh profil lengkap; trip terdekat
     * tetap dicari untuk pembaca tanpa profil, dan kecocokannya tetap tidak dikirim.
     */
    public function test_the_forecast_note_does_not_need_a_completed_profile(): void
    {
        $user = User::factory()->create();
        $jalur = $this->jalur();
        $tanggal = now('Asia/Jakarta')->addDays(15);
        TripPlan::factory()->for($user)->for($jalur)->create(['planned_date' => $tanggal->toDateString()]);

        $this->assertFalse($user->hasCompletedProfile());
        $this->actingAs($user)->get(route('trails.show', $jalur))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('cuaca.untukTrip.status', 'menunggu')
            ->where('kecocokan', null));
    }

    /**
     * Ruling W3-6b kasus (b) dan W3-6c: jendela terbit BMKG sudah tercapai (trip 3 hari
     * lagi), sumber cuaca hidup (available true, latestForTrail menemukan snapshotnya, dan
     * kartunya menggambar tabel prakiraan), tetapi datanya baru mencakup sekitar 24 jam ke
     * depan, belum sampai ke tanggal trip. Kalimat "prakiraan yang tampil di bawah" di sini
     * jujur: tabelnya memang digambar, hanya belum mencakup tanggal trip. Jam dibekukan
     * agar jendela 72 jam forecastForTrail() dan tanggal trip selalu sejalan.
     */
    public function test_the_forecast_note_admits_when_the_trip_date_is_not_covered_yet(): void
    {
        $this->travelTo(Carbon::parse('2026-09-15 08:00:00', 'Asia/Jakarta'));

        $user = $this->pendakiBerprofil();
        $jalur = $this->jalur(['weather_adm4_code' => '33.08.10.2001']);
        $tanggal = now('Asia/Jakarta')->addDays(3);
        TripPlan::factory()->for($user)->for($jalur)->create(['planned_date' => $tanggal->toDateString()]);

        // Data baru mencakup sekitar 24 jam ke depan, jauh dari tanggal trip 3 hari lagi.
        WeatherSnapshot::factory()->create([
            'adm4_code' => '33.08.10.2001',
            'forecast_at' => now()->addHours(20),
            'fetched_at' => now(),
        ]);

        $this->actingAs($user)->get(route('trails.show', $jalur))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('cuaca.untukTrip.status', 'tidakTercakup')
            ->where('cuaca.untukTrip.pesan', 'Prakiraan untuk '.$tanggal->translatedFormat('j M').' belum tersedia dari BMKG. Prakiraan yang tampil di bawah adalah yang terdekat.'));
    }

    /**
     * Ruling W3-6c: sumber cuaca gagal total (tidak ada satu pun snapshot untuk jalur ini,
     * jadi available false) tidak boleh mendapat kalimat yang menyebut "prakiraan yang
     * tampil di bawah", karena kartu cuaca tidak menggambar tabel apa pun saat gagal
     * (Kondisi.tsx hanya merender cuaca.message). Pesan galat kartu cuaca yang sudah ada
     * menjadi satu-satunya kabar (R-013), bukan kalimat yang menjanjikan baris yang tidak
     * pernah tampil.
     */
    public function test_the_forecast_note_stays_silent_when_weather_fails_entirely(): void
    {
        $user = $this->pendakiBerprofil();
        $jalur = $this->jalur();
        TripPlan::factory()->for($user)->for($jalur)->create(['planned_date' => now()->addDays(2)->toDateString()]);

        $isi = $this->actingAs($user)->get(route('trails.show', $jalur))->assertOk();

        $this->assertFalse($isi->viewData('page')['props']['cuaca']['available']);
        $this->assertNull($isi->viewData('page')['props']['cuaca']['untukTrip']);
        $this->assertStringNotContainsString('yang tampil di bawah', $isi->getContent());
    }

    /**
     * Ruling W3-6b kasus (a): jendela terbit tercapai DAN datanya sudah mencakup tanggal
     * trip. Baris prakiraan tanggal itu sendiri harus tampil, disaring dari forecast penuh
     * (bukan delapan baris pertama kartu cuaca, yang pada interval tiga jam BMKG hanya
     * mencakup sekitar sehari, jauh lebih sempit dari trip dua atau tiga hari lagi). Jam
     * dibekukan agar jendela 72 jam forecastForTrail() dan tanggal trip selalu sejalan.
     */
    public function test_the_forecast_note_shows_the_trip_dates_own_rows_when_covered(): void
    {
        $this->travelTo(Carbon::parse('2026-09-15 08:00:00', 'Asia/Jakarta'));

        $user = $this->pendakiBerprofil();
        $jalur = $this->jalur(['weather_adm4_code' => '33.08.10.2001']);
        $tanggal = now('Asia/Jakarta')->addDays(2);
        TripPlan::factory()->for($user)->for($jalur)->create(['planned_date' => $tanggal->toDateString()]);

        $milikTanggalTrip = WeatherSnapshot::factory()->create([
            'adm4_code' => '33.08.10.2001',
            'forecast_at' => $tanggal->copy()->setTime(12, 0),
            'fetched_at' => now(),
        ]);
        // Hari lain di jendela 72 jam yang sama tidak boleh tercampur ke baris tanggal trip.
        WeatherSnapshot::factory()->create([
            'adm4_code' => '33.08.10.2001',
            'forecast_at' => now()->addHours(6),
            'fetched_at' => now(),
        ]);

        $cuaca = $this->actingAs($user)->get(route('trails.show', $jalur))->viewData('page')['props']['cuaca'];

        $this->assertSame('tersedia', $cuaca['untukTrip']['status']);
        $this->assertSame('Prakiraan untuk '.$tanggal->translatedFormat('j M'), $cuaca['untukTrip']['judul']);
        $this->assertSame([$milikTanggalTrip->id], array_column($cuaca['untukTrip']['forecast'], 'id'));
    }

    /**
     * "Yang perlu disiapkan": izin bila ada aturannya, lalu butir templat khusus jalur ini
     * yang berlaku untuknya; templat bawaan tidak ikut karena bukan pembeda jalur ini.
     */
    public function test_preparation_lists_the_permit_and_this_trails_own_items(): void
    {
        $jalur = $this->jalur(['camping_available' => true]);
        PermitRequirement::factory()->create(['trail_id' => $jalur->id, 'authority' => 'Balai Uji']);
        $khusus = PreparationTemplate::create(['trail_id' => $jalur->id, 'name' => 'Khusus', 'is_default' => false]);
        $bawaan = PreparationTemplate::create(['name' => 'Bawaan', 'is_default' => true]);
        PreparationItem::create(['preparation_template_id' => $khusus->id, 'category' => PreparationCategory::EQUIPMENT->value, 'label' => 'Headlamp cadangan', 'is_critical' => true]);
        PreparationItem::create(['preparation_template_id' => $khusus->id, 'category' => PreparationCategory::EQUIPMENT->value, 'label' => 'Tenda', 'is_critical' => false, 'applies_when' => ['camping_available' => false]]);
        PreparationItem::create(['preparation_template_id' => $bawaan->id, 'category' => PreparationCategory::EQUIPMENT->value, 'label' => 'Air minum', 'is_critical' => true]);

        $persiapan = $this->actingAs(User::factory()->create())->get(route('trails.show', $jalur))->viewData('page')['props']['persiapan'];

        $this->assertSame([PreparationService::PERMIT_LABEL, 'Headlamp cadangan'], array_column($persiapan, 'judul'));
        $this->assertSame([true, true], array_column($persiapan, 'wajib'));

        // T6-M11 tinjauan akhir W3: dua butir berjudul sama (dari dua templat) tidak boleh
        // berbagi kunci React; kuncinya gabungan indeks dan judul.
        $this->assertStringContainsString('<li key={`${i}-${b.judul}`}', File::get(resource_path('js/components/trails/rinci/Persiapan.tsx')));
    }

    /**
     * Anggaran kueri (keputusan koordinator W3 nomor 12): pembaca berprofil dengan trip di
     * jalur ini; jumlah kueri tidak tumbuh bersama jumlah pos dan simpanannya.
     */
    public function test_the_page_does_not_grow_with_posts_or_saved_trails_for_a_rated_reader(): void
    {
        $user = $this->pendakiBerprofil();
        $jalur = $this->jalur();
        TripPlan::factory()->for($user)->for($jalur)->create(['planned_date' => now()->addDays(10)->toDateString()]);
        app(ConsiderationService::class)->toggle($user, $jalur);

        $this->actingAs($user)->get(route('trails.show', $jalur))->assertOk();
        $sedikit = $this->hitungKueri(route('trails.show', $jalur));

        foreach (range(2, 12) as $i) {
            Checkpoint::factory()->for($jalur)->create(['sequence' => $i, 'checkpoint_type' => CheckpointType::POS->value]);
            app(ConsiderationService::class)->toggle($user, $this->jalur());
        }

        $banyak = $this->hitungKueri(route('trails.show', $jalur));

        $this->assertSame($sedikit, $banyak, "Beban tumbuh: {$sedikit} kueri lalu {$banyak}.");
    }

    private function hitungKueri(string $url): int
    {
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->get($url)->assertOk();
        $jumlah = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $jumlah;
    }

    /**
     * Sumber di halaman ini mengikuti urutan kepercayaan (R-009): status akses lebih dulu,
     * label kecocokan terakhir sebagai interpretasi sistem.
     */
    public function test_the_sources_follow_the_authority_order(): void
    {
        $jalur = $this->jalur();

        $sumber = $this->actingAs($this->pendakiBerprofil())->get(route('trails.show', $jalur))->viewData('page')['props']['sumber'];

        $this->assertSame(['Status akses', 'Cuaca', 'Angka, pos, dan garis rute', 'Label kecocokan'], array_column($sumber, 'bagian'));
        $this->assertSame('AI_INTERPRETATION', end($sumber)['jenis']);
    }

    /**
     * Bar aksi ponsel tetap satu baris (tinjauan akhir W2, W2-F5; prototype 04). Di 360 x 740,
     * bar yang membungkus menjadi 163 px menyisakan isi 120 px ketika pesan jaringan, toast,
     * strip, dan bar tampil bersamaan. Simpan menjadi tombol ikon 44 px yang bernama dan
     * membawa aria-pressed, Banding dan Rencanakan trip berukuran kecil, dan Rencanakan trip
     * mengisi sisa baris; labelnya membungkus di dalam tombolnya sendiri, bukan memindah
     * tombol ke baris baru. Kolom kanan layar lebar yang lengket juga menyisakan lapisan bawah.
     */
    public function test_the_phone_action_bar_stays_one_row(): void
    {
        $halaman = File::get(resource_path('js/pages/Trails/Show.tsx'));
        $tombol = File::get(resource_path('js/components/trails/TombolSimpan.tsx'));

        preg_match('/<BarAksi label="Aksi jalur">([\s\S]*?)<\/BarAksi>/', $halaman, $bar);

        $this->assertNotEmpty($bar, 'Bar aksi rincian jalur tidak ditemukan.');
        $this->assertStringContainsString('<div className="flex w-full items-center gap-2">', $bar[1]);
        $this->assertStringContainsString('bentuk="bar"', $bar[1]);
        $this->assertStringContainsString('<Button size="sm" href={rencanaUrl} className="min-w-0 flex-1">', $bar[1]);
        $this->assertDoesNotMatchRegularExpression('/grid-cols|col-span|flex-wrap/', $bar[1], 'Bar aksi ponsel tidak boleh membungkus menjadi dua baris.');

        $this->assertMatchesRegularExpression('/bentuk === \'bar\' \? \(\s*<TombolIkon[\s\S]*?label=\{`Simpan \$\{jalur\.name\}`\}\s*aria-pressed=\{jalur\.ditimbang\}/', $tombol);

        $this->assertStringContainsString(
            'max-h-[calc(100dvh-var(--kepala-atas)-var(--pesan-jaringan,0px)-var(--nav-bawah,0px)-3rem)]',
            $halaman,
            'Kolom kanan yang lengket harus menyisakan lapisan bawah.'
        );
    }

    /**
     * Keadaan menunggu menyebut siapa yang ditunggu (Delivery Gate W3-G1b).
     * Empty checkpoint list menjelaskan bahwa curators/Memount yang akan melengkapinya.
     */
    public function test_empty_checkpoint_list_states_its_waiting_for_curator(): void
    {
        $halaman = File::get(resource_path('js/pages/Trails/Show.tsx'));

        $this->assertStringContainsString('Daftar pos jalur ini belum dimasukkan. Menunggu kurator Memount melengkapinya.', $halaman);
    }

    /**
     * Keadaan menunggu menyebut siapa yang ditunggu (Delivery Gate W3-G1b).
     * Empty figure display for AngkaRingkas states it is waiting for Memount curator.
     */
    public function test_empty_trail_figures_state_theyre_waiting_for_curator(): void
    {
        $angka = File::get(resource_path('js/components/trails/AngkaRingkas.tsx'));

        $this->assertStringContainsString('Jarak, tanjakan, dan lama naik belum tersedia. Menunggu kurator Memount melengkapinya.', $angka);
    }
}
