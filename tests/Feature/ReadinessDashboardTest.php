<?php

namespace Tests\Feature;

use App\Enums\AnalyticsEvent;
use App\Enums\ConditionTag;
use App\Enums\ExperienceLevel;
use App\Enums\OfficialStatusValue;
use App\Enums\PreparationCategory;
use App\Enums\PreparationStatus;
use App\Enums\ReadinessState;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Models\AnalyticsEvent as AnalyticsEventModel;
use App\Models\Mountain;
use App\Models\PermitRequirement;
use App\Models\ReadinessCheck;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\TripPlan;
use App\Models\User;
use App\Models\WeatherSnapshot;
use App\Services\PreparationService;
use App\Services\Readiness\ReadinessAssessment;
use App\Services\ReadinessService;
use App\Services\Trips\PreDepartureCheck;
use Database\Seeders\PreparationTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * F-07 dan F-08.
 *
 * ReadinessService::evaluate() selalu membuat baris baru, dan komponennya
 * memanggilnya dari mount(). Akibatnya sekadar membuka halaman menulis ke basis
 * data, tiga kali buka menghasilkan tiga baris, dan penanda
 * pre_departure_confirmed yang ditulis pada satu baris langsung digantikan baris
 * baru pada pemuatan berikutnya, sehingga konfirmasi pengguna hilang.
 *
 * Konfirmasi pre-departure adalah langkah inti North Star Metric (PRD §63), jadi
 * kehilangannya bukan cacat kosmetik. Halaman kini React; aksinya rute POST.
 */
class ReadinessDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewing_readiness_does_not_write_rows(): void
    {
        $trip = $this->trip();

        $this->actingAs($trip->user);
        $this->get(route('trips.readiness', $trip))->assertOk();
        $this->get(route('trips.readiness', $trip))->assertOk();
        $this->get(route('trips.readiness', $trip))->assertOk();

        $this->assertSame(
            0,
            ReadinessCheck::count(),
            'Membuka halaman bukan peristiwa yang layak dicatat.'
        );
    }

    public function test_recomputing_records_exactly_one_row(): void
    {
        $trip = $this->trip();

        // W4 Tugas 6: hasil cek yang disimpan menjadi pembanding "Sejak cek terakhir", jadi
        // kalimatnya menyebut itu; niatnya tetap satu baris per permintaan.
        $this->actingAs($trip->user)
            ->post(route('trips.readiness.recompute', $trip))
            ->assertRedirect(route('trips.readiness', $trip))
            ->assertSessionHas('status', 'Hasil cek disimpan. Cek berikutnya dibandingkan dengan hasil ini.');

        $this->assertSame(1, ReadinessCheck::count());
    }

    public function test_pre_departure_confirmation_survives_a_reload(): void
    {
        $trip = $this->trip();

        // Aksinya kini "Saya sudah meninjau semuanya" (prototype 10), jadi kalimat konfirmasinya
        // berbahasa yang sama; yang dicatat tetap pre_departure_confirmed.
        $this->actingAs($trip->user)
            ->post(route('trips.readiness.confirm', $trip))
            ->assertSessionHas('status', 'Tinjauan Anda tercatat.');

        // Memuat ulang halaman: konfirmasinya harus tetap terbaca.
        $this->get(route('trips.readiness', $trip))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Trips/Readiness')
                ->where('preDeparture.dikonfirmasi', true)
                ->where('preDeparture.dicatat', fn (?string $v) => $v !== null && str_contains($v, 'WIB')));
    }

    public function test_confirming_moves_the_trip_to_ready_for_departure(): void
    {
        $trip = $this->trip();

        $this->actingAs($trip->user)->post(route('trips.readiness.confirm', $trip));

        $this->assertSame(TripStatus::READY_FOR_DEPARTURE, $trip->fresh()->status);
    }

    public function test_the_assessment_is_still_shown_without_writing(): void
    {
        $trip = $this->trip();

        $this->actingAs($trip->user)
            ->get(route('trips.readiness', $trip))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Trips/Readiness')
                ->where('title', 'Kesiapan Pendakian')
                ->where('preDeparture.dikonfirmasi', false)
                ->where('keadaan.label', fn (string $v) => in_array($v, ['Persiapan kritis lengkap', 'Masih perlu persiapan', 'Tidak disarankan dengan rencana ini'], true))
                ->where('bolehKonfirmasi', true)
                ->has('kondisi'));

        $this->assertSame(0, ReadinessCheck::count());
    }

    /**
     * BR-09 dan R-005: skor internal dan angka kecocokan tidak pernah sampai ke props.
     * Persiapan dikirim sebagai hitungan, bukan persentase.
     */
    public function test_no_internal_score_or_percentage_reaches_the_page(): void
    {
        $this->seed(PreparationTemplateSeeder::class);
        $trip = $this->trip();

        $halaman = $this->actingAs($trip->user)->get(route('trips.readiness', $trip));
        $props = json_encode($halaman->viewData('page')['props']);

        $this->assertStringNotContainsString('internal_score', $props);
        $this->assertStringNotContainsString('score', strtolower($props));
        $halaman->assertInertia(fn (AssertableInertia $page) => $page
            ->where('persiapan.teks', fn (string $v) => (bool) preg_match('/^\d+ dari \d+ butir dikonfirmasi$/', $v)));
    }

    /**
     * DK-48 (spec v3 bagian 4, prototype 09 dan 10, W4 Tugas 5): kata keadaan kesiapan
     * menyebut apa yang dinilai, bukan vonis atas pendakiannya. "Siap" terbaca sebagai izin
     * berangkat dan "Tidak direkomendasikan" sebagai penilaian atas orangnya (R-014, §39).
     * "Masih perlu persiapan" sengaja berbeda dari label kecocokan "Perlu persiapan" (R-004),
     * supaya kesiapan rencana tidak terbaca sebagai label Route Fit.
     */
    public function test_the_readiness_states_speak_in_the_dk48_words(): void
    {
        $this->assertSame(
            ['READY' => 'Persiapan kritis lengkap', 'NEEDS_PREPARATION' => 'Masih perlu persiapan', 'NOT_RECOMMENDED' => 'Tidak disarankan dengan rencana ini'],
            collect(ReadinessState::cases())->mapWithKeys(fn (ReadinessState $s) => [$s->value => $s->label()])->all(),
        );

        foreach (ReadinessState::cases() as $keadaan) {
            $this->assertDoesNotMatchRegularExpression('/\bsiap\b|\baman\b|\blayak\b|direkomendasikan/i', $keadaan->label());
        }
    }

    /**
     * Prakiraan untuk tanggal trip (R-013, spec v3 bagian 6.11): sebelum H-3 "Belum terbit"
     * dengan tanggal terbitnya; sesudahnya baris untuk tanggal itu saja beserta sumber dan
     * area referensinya; sumber hidup yang belum mencapai tanggal itu disebut belum tersedia;
     * tanpa data sama sekali, pesan kegagalannya, bukan cuaca karangan.
     */
    public function test_the_forecast_is_for_the_trip_date_and_never_invented(): void
    {
        $this->travelTo(Carbon::parse('2026-09-25 03:00:00', 'UTC'));
        $trip = $this->trip();
        $trip->update(['planned_date' => '2026-10-10']);
        $prakiraan = fn () => $this->actingAs($trip->user)->get(route('trips.readiness', $trip))->viewData('page')['props']['kondisi']['prakiraan'];

        $menunggu = $prakiraan();
        $this->assertSame('menunggu', $menunggu['keadaan']);
        $this->assertSame('Prakiraan untuk Sab, 10 Okt', $menunggu['judul']);
        $this->assertSame('Menunggu BMKG. Terbit mulai Rab, 7 Okt; Memount tidak menebak cuaca sebelum itu.', $menunggu['pesan']);

        $this->travelTo(Carbon::parse('2026-10-08 01:00:00', 'UTC'));
        $this->assertSame(['gagal', 'Data cuaca tidak tersedia.'], [$prakiraan()['keadaan'], $prakiraan()['pesan']]);

        WeatherSnapshot::factory()->create(['adm4_code' => $trip->trail->weather_adm4_code, 'forecast_at' => now()->addHours(6), 'fetched_at' => now()]);
        $this->assertSame('belumTersedia', $prakiraan()['keadaan']);

        WeatherSnapshot::factory()->create(['adm4_code' => $trip->trail->weather_adm4_code, 'forecast_at' => Carbon::parse('2026-10-10 00:00:00', 'UTC'), 'fetched_at' => now(), 'weather_description' => 'Hujan ringan']);
        $tersedia = $prakiraan();
        $this->assertSame('tersedia', $tersedia['keadaan']);
        $this->assertSame([['waktu' => '07.00', 'cuaca' => 'Hujan ringan', 'suhu' => '20,0 °C']], $tersedia['baris']);
        $this->assertSame('BMKG', $tersedia['sumber']);
        $this->assertSame('Area basecamp', $tersedia['area']);
        // m3: kolom jam membawa penanda zona gunungnya sendiri, bukan tersirat UTC.
        $this->assertSame('WIB', $tersedia['zona']);

        // m5 (P12): BMKG menerbitkan lebih dari satu baris untuk tanggal trip (07.00 dan
        // 16.00 WIB); baris sore ini jauh di luar jendela 72 jam dari "sekarang" (08 Okt),
        // jadi tanpa kueri satu-hari di prakiraan() baris ini akan hilang diam-diam meskipun
        // datanya sudah tersimpan.
        WeatherSnapshot::factory()->create(['adm4_code' => $trip->trail->weather_adm4_code, 'forecast_at' => Carbon::parse('2026-10-10 09:00:00', 'UTC'), 'fetched_at' => now(), 'weather_description' => 'Berawan tebal']);
        $duaBaris = $prakiraan();
        $this->assertSame('tersedia', $duaBaris['keadaan']);
        $this->assertSame(
            [['waktu' => '07.00', 'cuaca' => 'Hujan ringan', 'suhu' => '20,0 °C'], ['waktu' => '16.00', 'cuaca' => 'Berawan tebal', 'suhu' => '20,0 °C']],
            $duaBaris['baris']
        );
    }

    /**
     * Tiga hal prototype 10: kecocokan untuk rencana, persiapan dengan butir kritis yang kurang
     * beserta aksinya, dan kondisi terkini. Keadaan menyebut berapa butir kritis yang belum,
     * pesan hari keberangkatan menyebut pos registrasinya, dan tidak ada skor atau persen.
     */
    public function test_the_page_shows_the_three_checks_with_their_next_actions(): void
    {
        $this->seed(PreparationTemplateSeeder::class);
        // M3 (putaran perbaikan 2): trip pembuka dibuang supaya id trip nyata dan id butir
        // nyata BERBEDA. Tanpa ini, fixture kosong membuat keduanya sama-sama 1, dan asersi
        // href #butir-{id} di bawah kebetulan benar meski kodenya memakai id TRIP, bukan id
        // butir (review task-6-fix1-review.md: "trip=1 butir=[1,2]").
        $this->trip();
        $trip = $this->trip();
        $trip->preparationItems()->where('is_critical', true)->orderBy('id')->get()->take(2)->each->update(['status' => PreparationStatus::NOT_CONFIRMED->value]);

        $props = $this->actingAs($trip->user)->get(route('trips.readiness', $trip))->viewData('page')['props'];

        $this->assertSame('NEEDS_PREPARATION', $props['keadaan']['nilai']);
        // Putaran perbaikan 2 (W4-6a): SEMUA sebab yang berlaku disebut, bukan hanya yang
        // pertama. Trail ini published() (status resmi UNKNOWN), jadi sebab itu juga berlaku
        // bersamaan dengan butir kritis yang belum, dan harus ikut tersebut.
        // MIN-3/X3 (putaran perbaikan 3): assertStringContainsString dengan dua substring lepas
        // tidak cukup -- mutasi yang membuang `elseif` pada cabang "butir lain"
        // (ReadinessService.php:354, sekarang elseif setelah `$kritisBelum > 0`) lolos test
        // lama karena kedua substring yang diperiksa tetap ada, padahal kalimatnya jadi
        // membantah dirinya sendiri: "Butir kritis sudah dikonfirmasi; 2 butir lain belum
        // dikonfirmasi." muncul BERSAMA "2 butir kritis belum dikonfirmasi." (dua butir yang
        // SAMA diklaim sudah dikonfirmasi dan belum dikonfirmasi sekaligus). assertSame pada
        // kalimat PENUH menolak sebab tambahan apa pun selain dua yang benar.
        $this->assertSame(
            '2 butir kritis belum dikonfirmasi. Status resmi jalur ini belum diketahui.',
            $props['keadaan']['kalimat']
        );
        $this->assertCount(2, $props['persiapan']['butir']);
        $this->assertSame('Konfirmasi', $props['persiapan']['butir'][0]['aksi']['label']);
        // m7/M3: href-nya persis menyebut id butir itu sendiri, bukan hanya berawalan sama
        // dengan butir lain (mutasi yang mengambil id trip malah lolos dari pola berawalan).
        $this->assertSame(
            route('trips.preparation', $trip).'#butir-'.$props['persiapan']['butir'][0]['id'],
            $props['persiapan']['butir'][0]['aksi']['href']
        );
        $this->assertSame(true, $props['kecocokan']['denganRencana']);
        $this->assertSame(route('trails.fit', ['trail' => $trip->trail, 'trip' => $trip->id]), $props['kecocokan']['tautan']);
        $this->assertSame('Pastikan status langsung di pos registrasi Basecamp. Memount tidak menampilkan status dari simpanan lama, karena status "Buka" yang basi bisa menyesatkan.', $props['hariKeberangkatan']);
        $this->assertDoesNotMatchRegularExpression('/score|\d+\s?%/i', json_encode($props));

        $halaman = File::get(resource_path('js/pages/Trips/Readiness.tsx'));
        // Putaran perbaikan 1 (m1): hitungan "Selesaikan" dipindah ke persiapan.siapDiselesaikan,
        // yang mengecualikan butir yang masih menunggu BMKG dari tombolnya (P9).
        foreach (['Ini dukungan keputusan, bukan izin medis, izin hukum, atau jaminan keselamatan.', 'Status resmi menjelaskan ketentuan pengelola, bukan jaminan keselamatan.', 'Saya sudah meninjau semuanya', 'Selesaikan {persiapan.siapDiselesaikan} butir', '<LangkahRingkas kini="kesiapan"', 'Sejak cek terakhir', 'Di hari keberangkatan'] as $penanda) {
            $this->assertStringContainsString($penanda, $halaman);
        }

        // m4: lencana kesegaran prakiraan memakai nada semantiknya, tidak selalu hijau.
        $this->assertStringContainsString('nadaKesegaran(prakiraan.kesegaran)', $halaman);

        // m7: jangkar butir kritis di Preparation.tsx adalah satu-satunya yang menyambung
        // tautan "Konfirmasi" di atas; tanpa test ini menghapusnya tidak pernah memerahkan apa pun.
        $persiapanTsx = File::get(resource_path('js/pages/Trips/Preparation.tsx'));
        $this->assertStringContainsString('id={`butir-${item.id}`}', $persiapanTsx);
    }

    /**
     * I1 (P1, putaran perbaikan 1): seluruh butir kritis dikonfirmasi tetapi satu butir
     * non-kritis belum, jadi keadaannya tetap NEEDS_PREPARATION. Kalimatnya harus menyebut
     * itu, bukan ringkasan kosong yang menunjuk daftar yang kebetulan sudah beres, dan
     * halaman harus menawarkan jalan ke sisanya.
     */
    public function test_needs_preparation_without_outstanding_critical_items_still_explains_why(): void
    {
        $this->seed(PreparationTemplateSeeder::class);
        $trip = $this->trip();
        $nonKritis = $trip->preparationItems()->where('is_critical', false)->firstOrFail();
        $nonKritis->update(['status' => PreparationStatus::NOT_CONFIRMED->value]);

        $props = $this->actingAs($trip->user)->get(route('trips.readiness', $trip))->viewData('page')['props'];

        $this->assertSame('NEEDS_PREPARATION', $props['keadaan']['nilai']);
        // Putaran perbaikan 2 (W4-6a): trail ini juga published() (status resmi UNKNOWN), jadi
        // kedua sebab berlaku bersamaan dan keduanya harus tersebut (probe R4-A/B).
        // X3 (putaran perbaikan 3): assertSame pada kalimat PENUH -- lihat alasan lengkapnya di
        // test_the_page_shows_the_three_checks_with_their_next_actions di atas.
        $this->assertSame(
            'Butir kritis sudah dikonfirmasi; 1 butir lain belum dikonfirmasi. Status resmi jalur ini belum diketahui.',
            $props['keadaan']['kalimat']
        );
        $this->assertCount(0, $props['persiapan']['butir']);
        $this->assertTrue($props['persiapan']['lanjutkan']);
        $this->assertMatchesRegularExpression('/^\d+ dari \d+ butir dikonfirmasi$/', $props['persiapan']['teks']);

        $halaman = File::get(resource_path('js/pages/Trips/Readiness.tsx'));
        $this->assertStringContainsString('Lanjutkan persiapan', $halaman);
        $this->assertStringContainsString('Seluruh daftar: {persiapan.teks}', $halaman);
    }

    /**
     * I2 (P2/P3, putaran perbaikan 1): sesudah tinjauan tercatat, tombolnya tidak ditawarkan
     * lagi, dan "Simpan hasil cek ini" (yang menulis baris BARU yang belum dikonfirmasi)
     * tidak boleh menghapus tanda tinjauan itu dari halaman.
     */
    public function test_a_recorded_review_is_not_offered_again_and_a_fresh_check_does_not_erase_it(): void
    {
        $trip = $this->trip();

        $this->actingAs($trip->user)->post(route('trips.readiness.confirm', $trip));
        $this->assertSame(TripStatus::READY_FOR_DEPARTURE, $trip->fresh()->status);

        $awal = $this->actingAs($trip->user)->get(route('trips.readiness', $trip))->viewData('page')['props'];
        $this->assertTrue($awal['preDeparture']['dikonfirmasi']);
        $this->assertFalse($awal['bolehKonfirmasi']);

        $this->actingAs($trip->user)->post(route('trips.readiness.recompute', $trip));
        $this->assertSame(2, ReadinessCheck::count());

        $sesudah = $this->actingAs($trip->user)->get(route('trips.readiness', $trip))->viewData('page')['props'];
        $this->assertTrue($sesudah['preDeparture']['dikonfirmasi']);
        $this->assertNotNull($sesudah['preDeparture']['dicatat']);
        $this->assertFalse($sesudah['bolehKonfirmasi']);

        $halaman = File::get(resource_path('js/pages/Trips/Readiness.tsx'));
        $this->assertStringContainsString('Tinjauan tercatat {preDeparture.dicatat}', $halaman);
    }

    /**
     * I2: tinjauan juga tidak ditawarkan ketika status trip tidak lagi dapat bertransisi ke
     * Siap berangkat, terlepas dari apakah sudah pernah dikonfirmasi.
     */
    public function test_a_trip_that_cannot_transition_to_ready_for_departure_is_not_offered_the_review_button(): void
    {
        $trip = $this->trip();

        $trip->update(['status' => TripStatus::READY_FOR_DEPARTURE->value]);
        $this->assertFalse($this->actingAs($trip->user)->get(route('trips.readiness', $trip))->viewData('page')['props']['bolehKonfirmasi']);

        $trip->update(['status' => TripStatus::CANCELLED->value]);
        $this->assertFalse($this->actingAs($trip->user)->get(route('trips.readiness', $trip))->viewData('page')['props']['bolehKonfirmasi']);
    }

    /**
     * I3: dua wilayah umpan balik dekat grup aksi SELALU terpasang (isinya bersyarat, bukan
     * wilayahnya), dan alert kondisional yang dulu di puncak halaman untuk flash dan
     * penolakan sudah dibuang supaya tidak terbaca dua kali dan tidak luput dari gulir.
     */
    public function test_the_action_feedback_regions_are_always_mounted_near_the_actions(): void
    {
        $halaman = File::get(resource_path('js/pages/Trips/Readiness.tsx'));

        // Putaran perbaikan 2 (n-1): empty:!mt-0 ditambahkan supaya wilayah kosong tidak
        // menyumbang margin space-y, tanpa menyembunyikan wilayahnya (masih role="status").
        // Upgrade tailwind 4: penanda penting kini akhiran (empty:mt-0!), bukan awalan (empty:!mt-0); niatnya sama.
        $this->assertStringContainsString('<p role="status" className="empty:mt-0! text-kecil text-secondary">', $halaman);
        $this->assertStringContainsString("{flash.status ?? ''}", $halaman);
        $this->assertStringContainsString('<p role="alert"', $halaman);
        $this->assertStringContainsString("{penolakan ?? ''}", $halaman);

        $this->assertStringNotContainsString('{penolakan ? <Alert variant="warning">', $halaman);
        $this->assertStringNotContainsString('{flash.status ? <Alert variant="success">', $halaman);
    }

    /**
     * I4 (P11): pendaki tanpa profil lengkap tidak dinilai (R-006); kartunya memotong ke
     * "Belum dinilai" dengan tautan ke onboarding, bukan ke penilaian lengkap yang tidak ada
     * apa-apanya untuk dilihat.
     */
    public function test_a_hiker_without_a_completed_profile_sees_unrated_fit_with_an_onboarding_link(): void
    {
        $trip = $this->trip();
        $trip->user->profile->update(['completed_at' => null]);

        $props = $this->actingAs($trip->user)->get(route('trips.readiness', $trip))->viewData('page')['props'];

        $this->assertNull($props['kecocokan']['label']);
        $this->assertSame('Belum dinilai', $props['kecocokan']['labelText']);
        $this->assertFalse($props['kecocokan']['profilLengkap']);
        $this->assertNull($props['kecocokan']['tautan']);
        $this->assertSame(route('onboarding'), $props['kecocokan']['tautanOnboarding']);

        $halaman = File::get(resource_path('js/pages/Trips/Readiness.tsx'));
        $this->assertStringContainsString('Isi profil pendaki', $halaman);
        $this->assertStringContainsString('kecocokan.tautanOnboarding', $halaman);
    }

    /**
     * I4: profil belum lengkap tidak membebaskan rencana dari syarat dasar. hardConstraintFailures()
     * tidak memakai data pengguna (fakta terverifikasi koordinator, RouteFitService.php:72), jadi
     * NOT_RECOMMENDED karena durasi tetap berlaku meskipun labelnya sendiri tidak pernah dinilai.
     */
    public function test_an_incomplete_profile_does_not_bypass_the_hard_constraint(): void
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::BEGINNER->value, 'completed_at' => null]);
        $trail = Trail::factory()->published()->create(['estimated_duration_minutes' => 1200]);

        $trip = TripPlan::create([
            'user_id' => $user->id,
            'trail_id' => $trail->id,
            'name' => 'Tektok tanpa profil',
            'planned_date' => now()->addDays(5)->toDateString(),
            'trip_type' => TripType::TEKTOK->value,
            'status' => TripStatus::PLANNED->value,
        ]);

        $props = $this->actingAs($user)->get(route('trips.readiness', $trip))->viewData('page')['props'];

        $this->assertSame('NOT_RECOMMENDED', $props['keadaan']['nilai']);
        $this->assertContains('Durasi jalur tidak sesuai dengan rencana pulang hari.', $props['keadaan']['alasan']);
        $this->assertFalse($props['bolehKonfirmasi']);
        $this->assertFalse($props['kecocokan']['profilLengkap']);
    }

    /**
     * I5(a), M5 (task-6-review.md bagian 7): compute() wajib memakai TripPlan::tujuanPenilaian()
     * (jenis dan tanggal trip sebagai tujuan sementara untuk trip yang belum punya hikingGoal
     * tersimpan), bukan $trip->hikingGoal mentah. Mutasi yang mengembalikannya ke hikingGoal
     * lolos dari seluruh suite Unit+Feature karena tidak ada test yang menjaganya secara
     * langsung; test ini membunuhnya (P13).
     */
    public function test_a_plan_is_checked_against_its_own_type_and_date_even_without_a_saved_goal(): void
    {
        $trip = $this->trip();
        $trip->trail->update(['estimated_duration_minutes' => 1200]);
        $trip->update(['trip_type' => TripType::TEKTOK->value]);
        $this->assertNull($trip->fresh()->hikingGoal);

        $props = $this->actingAs($trip->user)->get(route('trips.readiness', $trip))->viewData('page')['props'];

        $this->assertSame('NOT_RECOMMENDED', $props['keadaan']['nilai']);
        $this->assertContains('Durasi jalur tidak sesuai dengan rencana pulang hari.', $props['keadaan']['alasan']);
        $this->assertFalse($props['bolehKonfirmasi']);
        // m6: rencana yang bisa diubah (trip masih PLANNED) menawarkan "Ubah rencana" sebagai
        // aksi utama, bukan "Selesaikan butir" yang tidak menolong masalah durasinya.
        $this->assertNotNull($props['kepala']['ubah']);
    }

    /**
     * m6: NOT_RECOMMENDED tidak ditolong dengan menyelesaikan butir persiapan -- aksi utamanya
     * mengubah rencana atau melihat penilaian lengkap, dan "Selesaikan" turun jadi sekunder.
     */
    public function test_a_not_recommended_plan_offers_changing_the_plan_before_finishing_items(): void
    {
        $halaman = File::get(resource_path('js/pages/Trips/Readiness.tsx'));

        $this->assertStringContainsString('tidakDisarankan || kepala.lewat ? (', $halaman);
        $this->assertStringContainsString('Ubah rencana', $halaman);
        $this->assertStringContainsString('Lihat penilaian lengkap', $halaman);
        $this->assertStringContainsString('variant="secondary" href={persiapan.tautan}', $halaman);
    }

    /**
     * m1/m7 (M13): butir cuaca sebelum prakiraan tanggal trip terbit tidak punya aksi --
     * mengonfirmasinya lebih awal ditolak server, jadi tombolnya tidak boleh menjanjikannya.
     * Hitungan "Selesaikan" mengecualikan butir ini (P9), dan halaman menawarkan kalimat
     * penunggu sebagai gantinya.
     */
    public function test_a_weather_item_before_the_forecast_window_waits_and_is_excluded_from_the_finish_count(): void
    {
        $this->seed(PreparationTemplateSeeder::class);
        $trip = $this->trip();
        $cuaca = $trip->preparationItems()->where('category', PreparationCategory::WEATHER->value)->where('is_critical', true)->sole();
        $cuaca->update(['status' => PreparationStatus::NOT_CONFIRMED->value]);

        $props = $this->actingAs($trip->user)->get(route('trips.readiness', $trip))->viewData('page')['props'];

        $this->assertCount(1, $props['persiapan']['butir']);
        $this->assertNull($props['persiapan']['butir'][0]['aksi']);
        $this->assertStringContainsString('Menunggu BMKG', $props['persiapan']['butir'][0]['menunggu']);
        $this->assertSame(0, $props['persiapan']['siapDiselesaikan']);
        $this->assertStringContainsString('menunggu BMKG', $props['persiapan']['semuaMenungguBmkg']);

        $halaman = File::get(resource_path('js/pages/Trips/Readiness.tsx'));
        $this->assertStringContainsString('persiapan.semuaMenungguBmkg', $halaman);
    }

    /**
     * m7 (M12): butir izin dengan aturan yang punya booking_url menawarkan aksi "Pesan" ke
     * sistem resmi, bukan "Konfirmasi" di daftar persiapan yang tidak dapat memesan apa pun.
     */
    public function test_a_permit_item_with_a_booking_url_offers_an_external_booking_action(): void
    {
        $trip = $this->trip();
        PermitRequirement::factory()->create(['trail_id' => $trip->trail_id, 'booking_url' => 'https://contoh.test/booking']);
        app(PreparationService::class)->generateFor($trip);
        $trip->preparationItems()
            ->whereNull('preparation_item_id')
            ->where('label', PreparationService::PERMIT_LABEL)
            ->update(['status' => PreparationStatus::NOT_CONFIRMED->value]);

        $props = $this->actingAs($trip->user)->get(route('trips.readiness', $trip))->viewData('page')['props'];
        $butirIzin = collect($props['persiapan']['butir'])->firstWhere('label', PreparationService::PERMIT_LABEL);

        $this->assertNotNull($butirIzin);
        $this->assertSame(['label' => 'Pesan', 'href' => 'https://contoh.test/booking', 'luar' => true], $butirIzin['aksi']);
    }

    /**
     * m10: "Data cuaca tidak tersedia" tidak ditulis dua kali -- kartu prakiraan sudah
     * mengatakannya, baris peringatan generik yang sama tidak perlu mengulanginya.
     */
    public function test_the_weather_unavailable_warning_is_not_duplicated(): void
    {
        $trip = $this->trip();
        $trip->update(['planned_date' => now()->addDay()->toDateString()]);

        $props = $this->actingAs($trip->user)->get(route('trips.readiness', $trip))->viewData('page')['props'];

        $this->assertSame('gagal', $props['kondisi']['prakiraan']['keadaan']);
        $this->assertSame('Data cuaca tidak tersedia.', $props['kondisi']['prakiraan']['pesan']);
        $this->assertStringNotContainsString(
            'Data cuaca tidak tersedia untuk area referensi jalur ini.',
            implode(' ', $props['kondisi']['peringatan'])
        );
    }

    /**
     * W4-6g butir 1 (P10): readiness_checks.condition_snapshot hanya boleh menyimpan kunci
     * minimal per laporan komunitas. Identitas pelapor (user_id, nama, catatan, foto,
     * moderator) tidak boleh ikut tersalin ke baris yang disimpan setiap kali pendaki
     * menekan "Simpan hasil cek ini" atau menandai tinjauan
     * (docs/KEBIJAKAN-DATA-PRIBADI.md bagian 2, 3, 5).
     */
    public function test_the_saved_snapshot_keeps_only_minimal_report_fields(): void
    {
        $trip = $this->trip();
        $pelapor = User::factory()->create(['name' => 'Nama Rahasia Pelapor']);
        TrailConditionReport::factory()->approved()->create([
            'trail_id' => $trip->trail_id,
            'user_id' => $pelapor->id,
            'note' => 'Catatan pribadi pelapor',
            'photo_path' => 'reports/contoh.jpg',
        ]);

        $check = app(ReadinessService::class)->record($trip)->fresh();
        $laporan = $check->condition_snapshot['community']['reports'][0] ?? null;

        $this->assertNotNull($laporan);
        $this->assertSame(['id', 'created_at', 'hike_date', 'condition_tags', 'segment'], array_keys($laporan));

        $mentah = json_encode($check->condition_snapshot);
        $this->assertStringNotContainsString('Nama Rahasia Pelapor', $mentah);
        $this->assertStringNotContainsString('Catatan pribadi pelapor', $mentah);
        $this->assertStringNotContainsString('reports/contoh.jpg', $mentah);
        $this->assertStringNotContainsString('user_id', $mentah);
        $this->assertStringNotContainsString('moderated_by', $mentah);
    }

    /**
     * X9 (putaran perbaikan 3, n-3 putaran 2): jalur GAGAL-TERTUTUP ReadinessAssessment::minimalReport()
     * (ReadinessAssessment.php:83-109) untuk entri yang BUKAN model TrailConditionReport --
     * array mentah. Jalur normal (ReadinessService::compute()) selalu mengisi community.reports
     * dengan model asli, jadi test di atas (test_the_saved_snapshot_keeps_only_minimal_report_fields)
     * tidak pernah melewati cabang array ini; ReadinessAssessment dibangun langsung di sini
     * supaya cabang array-nya sendiri teruji, bukan menunggu pemanggil lain yang kelak mengisi
     * array mentah untuk membuktikan privasinya (R-023, docs/KEBIJAKAN-DATA-PRIBADI.md bagian
     * 2, 3, 5). 'segment' bersarang juga disaring (NIT-4): sebelumnya entri array yang membawa
     * segmen lengkap meneruskan isinya apa adanya.
     */
    public function test_a_raw_array_community_report_entry_is_still_minimized(): void
    {
        $assessment = new ReadinessAssessment(
            state: ReadinessState::READY,
            routeFitSnapshot: [],
            preparationState: [],
            officialStatusSnapshot: [],
            conditionSnapshot: [
                'community' => [
                    'reports' => [[
                        'id' => 99,
                        'created_at' => '2026-09-01T00:00:00Z',
                        'hike_date' => '2026-08-30',
                        'condition_tags' => ['SLIPPERY'],
                        'segment' => ['id' => 5, 'name' => 'Segmen Puncak', 'rahasia' => 'bocor'],
                        'user_id' => 12345,
                        'note' => 'Catatan pribadi pelapor',
                        'photo_path' => 'private/foto.jpg',
                        'moderated_by' => 7,
                    ]],
                ],
            ],
            explanation: [],
        );

        $disimpan = $assessment->toAttributes()['condition_snapshot']['community']['reports'][0];

        $this->assertSame(['id', 'created_at', 'hike_date', 'condition_tags', 'segment'], array_keys($disimpan));
        $this->assertSame(['id', 'name'], array_keys($disimpan['segment']));
        $this->assertArrayNotHasKey('user_id', $disimpan);
        $this->assertArrayNotHasKey('note', $disimpan);
        $this->assertArrayNotHasKey('photo_path', $disimpan);
        $this->assertArrayNotHasKey('moderated_by', $disimpan);
        $this->assertArrayNotHasKey('rahasia', $disimpan['segment']);
    }

    /**
     * M5 tinjauan akhir W4: confirm() membaca keadaan trip dari baris yang dikuncinya, bukan dari
     * model yang dibawa permintaan. Permintaan kedua yang memuat trip sebelum permintaan pertama
     * selesai (dua tab, kirim ulang jaringan) ditolak, dan hanya satu baris terkonfirmasi dan
     * satu event yang tercatat. Kuncinya sendiri dibuktikan di PostgreSQL
     * (tests/Spatial/PreDepartureConfirmLockTest), karena SQLite tidak punya kunci baris.
     */
    public function test_a_second_confirmation_carrying_a_stale_trip_is_refused(): void
    {
        $this->travelTo(Carbon::parse('2026-10-07 03:00:00', 'UTC'));
        $trip = $this->trip();
        $basi = TripPlan::with('trail.mountain')->findOrFail($trip->id);
        $layanan = app(PreDepartureCheck::class);

        $this->assertNull($layanan->confirm($trip->load('trail.mountain'), $trip->user));
        $penolakan = $layanan->confirm($basi, $trip->user);

        $this->assertSame('Tinjauan sudah tercatat 7 Okt 2026 10.00 WIB.', $penolakan);
        $this->assertSame(TripStatus::PLANNED, $basi->status, 'Model yang dibawa permintaan memang basi.');
        $this->assertSame(1, ReadinessCheck::query()->where('trip_plan_id', $trip->id)->count());
        $this->assertSame(1, ReadinessCheck::query()->where('trip_plan_id', $trip->id)->where('pre_departure_confirmed', true)->count());
        $this->assertSame(1, AnalyticsEventModel::query()->where('event_name', AnalyticsEvent::PRE_DEPARTURE_CHECK_COMPLETED->value)->count());
    }

    /**
     * M1 tinjauan akhir W4: butir izin di Cek memakai penentu ajakan pemesanan yang sama dengan
     * Persiapan dan Rencanakan. Jendela terbuka menautkan "Pesan"; jendela yang sudah tutup
     * menulis keadaannya dan menautkan barisnya di Persiapan, bukan "Pesan".
     */
    public function test_the_permit_item_on_the_check_page_follows_the_booking_window(): void
    {
        $this->travelTo(Carbon::parse('2026-10-07 03:00:00', 'UTC'));
        $butirIzin = function (string $tanggal): array {
            $trip = $this->trip();
            $trip->update(['planned_date' => $tanggal]);
            PermitRequirement::factory()->create(['trail_id' => $trip->trail_id, 'authority' => 'Balai Uji', 'booking_url' => 'https://izin.example.test', 'booking_opens_days_before' => 30, 'booking_closes_days_before' => 3]);
            $trip->preparationItems()->create(['category' => PreparationCategory::LOGISTICS->value, 'label' => PreparationService::PERMIT_LABEL, 'is_critical' => true, 'status' => PreparationStatus::NOT_CONFIRMED->value]);

            return collect($this->actingAs($trip->user)->get(route('trips.readiness', $trip))->viewData('page')['props']['persiapan']['butir'])->firstWhere('label', PreparationService::PERMIT_LABEL);
        };

        $terbuka = $butirIzin('2026-10-20');
        $tutup = $butirIzin('2026-10-09');

        $this->assertSame(['label' => 'Pesan', 'href' => 'https://izin.example.test', 'luar' => true], $terbuka['aksi']);
        $this->assertNull($terbuka['keterangan']);
        $this->assertSame('Konfirmasi', $tutup['aksi']['label']);
        $this->assertFalse($tutup['aksi']['luar']);
        $this->assertStringEndsWith('Pemesanan di sistem resmi sudah tutup, hari terakhirnya Sel, 6 Okt.', $tutup['keterangan']);
    }

    /**
     * Nit E W4-6l: cabang larik ReadinessAssessment::laporanMinimal() gagal-tertutup per kunci.
     * Segmen berupa teks, id bersarang, dan tanda kondisi yang bukan teks dibuang, bukan
     * diteruskan apa adanya.
     */
    public function test_a_raw_array_report_drops_values_of_the_wrong_shape(): void
    {
        $disimpan = ReadinessAssessment::laporanMinimal([
            'id' => ['bersarang' => 'Nama Pelapor Rahasia'],
            'created_at' => ['tanggal' => 'x'],
            'hike_date' => '2026-08-30',
            'condition_tags' => ['SLIPPERY', ['nama' => 'Nama Pelapor Rahasia']],
            'segment' => 'Nama Pelapor Rahasia',
        ]);

        $this->assertSame(['hike_date' => '2026-08-30', 'condition_tags' => ['SLIPPERY']], $disimpan);
        $this->assertSame([], ReadinessAssessment::laporanMinimal('Nama Pelapor Rahasia'));
        $this->assertSame(['segment' => ['name' => 'Puncak']], ReadinessAssessment::laporanMinimal(['segment' => ['id' => '5', 'name' => 'Puncak', 'pelapor' => ['nama' => 'x']]]));
    }

    /**
     * NEW-3 W4-6l (mutasi Y8 dan Y9): "butir lain menunggu BMKG" hanya untuk butir CUACA non-kritis
     * yang belum dikonfirmasi SELAMA prakiraannya belum terbit. Butir non-kritis lain sebelum
     * terbit, dan butir cuaca non-kritis sesudah terbit, tidak disebut menunggu.
     */
    public function test_other_items_are_only_said_to_wait_for_bmkg_when_they_really_do(): void
    {
        $this->travelTo(Carbon::parse('2026-10-07 03:00:00', 'UTC'));
        $this->seed(PreparationTemplateSeeder::class);
        $trip = $this->trip();
        $trip->update(['planned_date' => '2026-10-17']);
        $trip->preparationItems()->where('is_critical', false)->where('category', '!=', PreparationCategory::WEATHER->value)->limit(1)->update(['status' => PreparationStatus::NOT_CONFIRMED->value]);

        $sebelumTerbit = $this->actingAs($trip->user)->get(route('trips.readiness', $trip))->viewData('page')['props']['persiapan'];
        $trip->preparationItems()->update(['status' => PreparationStatus::CONFIRMED->value]);
        $trip->preparationItems()->where('is_critical', false)->where('category', PreparationCategory::WEATHER->value)->update(['status' => PreparationStatus::NOT_CONFIRMED->value]);
        $this->travelTo(Carbon::parse('2026-10-14 03:00:00', 'UTC'));
        $sesudahTerbit = $this->actingAs($trip->user)->get(route('trips.readiness', $trip))->viewData('page')['props']['persiapan'];

        $this->assertNull($sebelumTerbit['menungguLain']);
        $this->assertTrue($sebelumTerbit['lanjutkan']);
        $this->assertNull($sesudahTerbit['menungguLain']);
        $this->assertTrue($sesudahTerbit['lanjutkan']);
    }

    /**
     * R2 W4-6l: tanpa daftar persiapan, "Buat daftar persiapan" adalah aksi utama grup aksi dan
     * tombol tinjauan turun menjadi sekunder.
     */
    public function test_an_empty_checklist_makes_building_it_the_main_action(): void
    {
        $trip = $this->trip();
        $trip->preparationItems()->delete();

        $persiapan = $this->actingAs($trip->user)->get(route('trips.readiness', $trip))->viewData('page')['props']['persiapan'];
        $halaman = File::get(resource_path('js/pages/Trips/Readiness.tsx'));

        $this->assertTrue($persiapan['daftarBelumAda']);
        $this->assertSame('Belum ada butir persiapan', $persiapan['teks']);
        $this->assertStringContainsString(") : tindakLanjut && persiapan.daftarBelumAda ? (\n                        // R2 W4-6l: tanpa daftar, membuat daftar adalah aksi utama; tinjauan sekunder.\n                        <Button href={persiapan.tautan} className=\"w-full\">\n                            Buat daftar persiapan", $halaman);
        $this->assertStringContainsString("persiapan.butir.length > 0 || persiapan.menungguLain !== null || persiapan.daftarBelumAda ? 'secondary' : 'primary'", $halaman);
        // NIT-B: daftar kosong dan daftar Tidak berlaku tidak disebut dua kali di kartu Persiapan.
        $this->assertStringNotContainsString("'Daftar persiapan belum ada.'", $halaman);
        $this->assertStringNotContainsString("'Seluruh butir persiapan ditandai Tidak berlaku.'", $halaman);
    }

    /**
     * Nit A W4-6l: baris tinjauan terkonfirmasi dibaca sekali per muat halaman, bukan empat kali.
     */
    public function test_the_confirmed_review_row_is_read_once_per_page_load(): void
    {
        $trip = $this->trip();
        $this->actingAs($trip->user)->post(route('trips.readiness.confirm', $trip));
        $jumlah = 0;
        DB::listen(function ($kueri) use (&$jumlah) {
            $jumlah += str_contains($kueri->sql, '"pre_departure_confirmed" = ?') ? 1 : 0;
        });

        $this->actingAs($trip->user)->get(route('trips.readiness', $trip))->assertOk();

        $this->assertSame(1, $jumlah);
    }

    /**
     * Nit D W4-6l (mutasi Y13 dan Y16): POST tinjauan untuk trip yang sudah ditinjau menyebut
     * waktunya, dengan format jam yang sama dengan halaman (M3); dan jalur Tutup menyebut status
     * resminya sekali, bukan dua kalimat untuk fakta yang sama.
     */
    public function test_a_second_review_post_names_its_time_and_closed_status_is_said_once(): void
    {
        $this->travelTo(Carbon::parse('2026-10-07 03:00:00', 'UTC'));
        $trip = $this->trip();
        $this->actingAs($trip->user)->post(route('trips.readiness.confirm', $trip));

        $this->actingAs($trip->user)->post(route('trips.readiness.confirm', $trip))
            ->assertSessionHas('penolakan-kesiapan', 'Tinjauan sudah tercatat 7 Okt 2026 10.00 WIB.');
        $this->assertSame('7 Okt 2026 10.00 WIB', $this->actingAs($trip->user)->get(route('trips.readiness', $trip))->viewData('page')['props']['keadaan']['dihitung']);

        $tutup = $this->trip();
        $tutup->trail->officialStatuses()->update(['status' => OfficialStatusValue::CLOSED->value]);
        $alasan = $this->actingAs($tutup->user)->get(route('trips.readiness', $tutup))->viewData('page')['props']['keadaan']['alasan'];

        $this->assertContains('Status resmi jalur ini tercatat TUTUP.', $alasan);
        $this->assertNotContains('Jalur tidak tersedia karena status resmi TUTUP.', $alasan);
    }

    /**
     * M2 tinjauan akhir W4: pendaki tanpa profil lengkap tidak diberi label, tetapi syarat dasar
     * tetap dihitung untuknya di Rencanakan dan Cek. Tektok di jalur bermalam disebut tidak
     * memenuhi syarat dasar, sejalan dengan keadaan "tidak disarankan".
     */
    public function test_a_hiker_without_a_profile_is_still_told_about_the_base_conditions(): void
    {
        $user = User::factory()->create();
        $jalur = Trail::factory()->published()->highlyTechnical()->create();
        $trip = TripPlan::factory()->for($user)->create(['trail_id' => $jalur->id, 'trip_type' => TripType::TEKTOK->value, 'planned_date' => now()->addDays(5)->toDateString()]);

        $cek = $this->actingAs($user)->get(route('trips.readiness', $trip))->viewData('page')['props'];
        $rencana = $this->actingAs($user)->get(route('trips.create', ['trail' => $jalur->id, 'jenis' => 'TEKTOK']))->viewData('page')['props']['kecocokan'];

        $this->assertSame('NOT_RECOMMENDED', $cek['keadaan']['nilai']);
        $this->assertNull($cek['kecocokan']['label']);
        $this->assertFalse($cek['kecocokan']['layak']);
        $this->assertSame(__('recommendation.duration_requires_overnight'), $cek['kecocokan']['alasan']);
        $this->assertNull($rencana['label']);
        $this->assertFalse($rencana['layak']);
        $this->assertSame(__('recommendation.duration_requires_overnight'), $rencana['alasan']);
    }

    /**
     * W4-6g butir 2: tulisan pribadi yang sama seperti rute trip lain, jadi dibatasi laju
     * yang sama dengan awalan kuncinya sendiri (routes/web.php).
     */
    public function test_recomputing_is_rate_limited(): void
    {
        $trip = $this->trip();
        $batas = (int) config('hiking.rate_limits.personal_writes_per_minute');

        for ($i = 0; $i < $batas; $i++) {
            $this->actingAs($trip->user)->post(route('trips.readiness.recompute', $trip))->assertRedirect();
        }

        $this->actingAs($trip->user)->post(route('trips.readiness.recompute', $trip))->assertStatus(429);
    }

    /**
     * I-1 (New Breakage putaran perbaikan 1, diperbaiki putaran perbaikan 2, probe R2): trip
     * yang statusnya tidak dapat bertransisi ke Siap berangkat tidak lagi dituduh "rencana
     * ini tidak disarankan" -- kalimatnya menyebut STATUS trip itu sendiri, persis yang akan
     * diberikan PreDepartureCheck::confirm() bila benar-benar ditekan (satu sumber,
     * alasanPenolakan()). Satu test per status, sesuai ruling.
     */
    public function test_a_draft_trip_names_its_own_status_not_the_plan(): void
    {
        $trip = $this->trip();
        $trip->update(['status' => TripStatus::DRAFT->value]);

        $props = $this->actingAs($trip->user)->get(route('trips.readiness', $trip))->viewData('page')['props'];

        $this->assertFalse($props['bolehKonfirmasi']);
        $this->assertSame('Tinjauan tidak dapat dicatat karena trip ini berstatus Draft.', $props['alasanTanpaTinjauan']);
    }

    public function test_an_in_progress_trip_names_its_own_status_not_the_plan(): void
    {
        $trip = $this->trip();
        $trip->update(['status' => TripStatus::IN_PROGRESS->value]);

        $props = $this->actingAs($trip->user)->get(route('trips.readiness', $trip))->viewData('page')['props'];

        $this->assertFalse($props['bolehKonfirmasi']);
        $this->assertSame('Tinjauan tidak dapat dicatat karena trip ini berstatus Sedang berlangsung.', $props['alasanTanpaTinjauan']);
    }

    public function test_a_completed_trip_names_its_own_status_not_the_plan(): void
    {
        $trip = $this->trip();
        $trip->update(['status' => TripStatus::COMPLETED->value]);

        $props = $this->actingAs($trip->user)->get(route('trips.readiness', $trip))->viewData('page')['props'];

        $this->assertFalse($props['bolehKonfirmasi']);
        $this->assertSame('Tinjauan tidak dapat dicatat karena trip ini berstatus Selesai.', $props['alasanTanpaTinjauan']);
    }

    public function test_a_cancelled_trip_names_its_own_status_not_the_plan(): void
    {
        $trip = $this->trip();
        $trip->update(['status' => TripStatus::CANCELLED->value]);

        $props = $this->actingAs($trip->user)->get(route('trips.readiness', $trip))->viewData('page')['props'];

        $this->assertFalse($props['bolehKonfirmasi']);
        $this->assertSame('Tinjauan tidak dapat dicatat karena trip ini berstatus Dibatalkan.', $props['alasanTanpaTinjauan']);
    }

    /**
     * READY_FOR_DEPARTURE tanpa baris yang benar-benar terkonfirmasi: tombol tidak
     * ditawarkan (tidak dapat bertransisi ke dirinya sendiri), dan alasannya menyebut
     * status itu, bukan "sudah ditinjau" yang palsu.
     */
    public function test_a_ready_for_departure_trip_without_a_recorded_review_names_its_own_status(): void
    {
        $trip = $this->trip();
        $trip->update(['status' => TripStatus::READY_FOR_DEPARTURE->value]);

        $props = $this->actingAs($trip->user)->get(route('trips.readiness', $trip))->viewData('page')['props'];

        $this->assertFalse($props['preDeparture']['dikonfirmasi']);
        $this->assertFalse($props['bolehKonfirmasi']);
        $this->assertSame('Tinjauan tidak dapat dicatat karena trip ini berstatus Siap berangkat.', $props['alasanTanpaTinjauan']);
    }

    /**
     * NOT_RECOMMENDED tetap memakai kalimat "rencana ini tidak disarankan" -- kelas
     * penolakan yang berbeda dari status trip, karena penyebabnya memang penilaian
     * kesiapan rencananya, bukan status administratif trip itu.
     */
    public function test_a_not_recommended_plan_names_the_plan_not_the_status(): void
    {
        $trip = $this->trip();
        $trip->trail->officialStatuses()->update(['status' => OfficialStatusValue::CLOSED->value]);

        $props = $this->actingAs($trip->user)->get(route('trips.readiness', $trip))->viewData('page')['props'];

        $this->assertSame('NOT_RECOMMENDED', $props['keadaan']['nilai']);
        $this->assertFalse($props['bolehKonfirmasi']);
        $this->assertStringStartsWith('Tinjauan tidak dapat dicatat karena rencana ini tidak disarankan.', $props['alasanTanpaTinjauan']);
        $this->assertStringContainsString('TUTUP', $props['alasanTanpaTinjauan']);
    }

    /**
     * W4-6a (probe R4-A/B, putaran perbaikan 2): setiap cabang determineState() yang
     * terpenuhi sendiri sudah cukup menahan NEEDS_PREPARATION, jadi SEMUANYA harus disebut
     * bersama. Sebelumnya pendaki yang menyelesaikan sebab pertama (persiapan) menemukan
     * sebab baru yang sudah ada sejak awal tetapi tidak pernah disebut.
     */
    public function test_needs_preparation_names_every_cause_that_applies_at_once(): void
    {
        $this->seed(PreparationTemplateSeeder::class);

        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::BEGINNER->value, 'completed_at' => now()]);
        $trail = Trail::factory()->published()->highlyTechnical()->create();

        $trip = TripPlan::create([
            'user_id' => $user->id,
            'trail_id' => $trail->id,
            'name' => 'Sebab ganda',
            'planned_date' => now()->addDays(5)->toDateString(),
            'trip_type' => TripType::CAMPING->value,
            'status' => TripStatus::PLANNED->value,
        ]);

        app(PreparationService::class)->generateFor($trip);
        $trip->preparationItems()->update(['status' => PreparationStatus::CONFIRMED->value, 'status_updated_at' => now()]);
        $nonKritis = $trip->preparationItems()->where('is_critical', false)->firstOrFail();
        $nonKritis->update(['status' => PreparationStatus::NOT_CONFIRMED->value]);

        TrailConditionReport::factory()->approved()->create(['trail_id' => $trail->id, 'condition_tags' => [ConditionTag::SLIPPERY->value]]);

        $props = $this->actingAs($user)->get(route('trips.readiness', $trip))->viewData('page')['props'];

        $this->assertSame('NEEDS_PREPARATION', $props['keadaan']['nilai']);
        $kalimat = $props['keadaan']['kalimat'];
        // X15 (putaran perbaikan 3): assertSame pada kalimat PENUH dan urutannya, bukan empat
        // assertStringContainsString lepas -- mutasi yang menukar urutan sebab (X15) lolos
        // test lama karena tiap substring tetap ada di mana pun posisinya ditaruh. Urutan di
        // bawah mengikuti cabang needsPreparationCauses() (ReadinessService.php:341-370):
        // persiapan, kecocokan, status, peringatan kondisi -- sengaja SAMA dengan urutan
        // cabang determineState() (lihat docblock method itu).
        $this->assertSame(
            'Butir kritis sudah dikonfirmasi; 1 butir lain belum dikonfirmasi. '
            .'Kecocokan jalur untuk rencana ini Kurang cocok. '
            .'Status resmi jalur ini belum diketahui. '
            .'Ada peringatan kondisi yang perlu ditinjau.',
            $kalimat
        );
    }

    /**
     * MIN-1 (putaran perbaikan 3, probe R2 "kosong | cocok | OPEN"): trip tanpa satu pun
     * butir persiapan SAMA SEKALI -- bukan seluruhnya Tidak berlaku (lihat
     * test_all_items_marked_not_applicable_has_its_own_sentence_and_action untuk itu), benar-
     * benar belum ada baris TripPreparationItem sama sekali (PreparationTemplateSeeder
     * sengaja TIDAK diseed di sini). Sebelum perbaikan ini kasusnya jatuh diam-diam ke
     * kalimat cadangan "Masih ada yang perlu diperiksa sebelum berangkat." tanpa aksi apa
     * pun; sekarang punya sebab dan aksinya sendiri menuju halaman Persiapan, yang
     * membangkitkan daftar begitu dibuka kosong (fakta terverifikasi: PreparationController::show()
     * memanggil PreparationService::generateFor() ketika preparationItems kosong).
     */
    public function test_a_trip_with_no_preparation_items_at_all_gets_its_own_cause_and_action(): void
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::ADVANCED->value, 'completed_at' => now()]);
        $trail = Trail::factory()->published()->easy()->create();
        $trail->officialStatuses()->update(['status' => OfficialStatusValue::OPEN->value]);

        $trip = TripPlan::create([
            'user_id' => $user->id,
            'trail_id' => $trail->id,
            'name' => 'MIN-1',
            'planned_date' => now()->addDays(5)->toDateString(),
            'trip_type' => TripType::CAMPING->value,
            'status' => TripStatus::PLANNED->value,
        ]);

        $props = $this->actingAs($user)->get(route('trips.readiness', $trip))->viewData('page')['props'];

        $this->assertSame('NEEDS_PREPARATION', $props['keadaan']['nilai']);
        $this->assertSame('Daftar persiapan belum ada.', $props['keadaan']['kalimat']);
        $this->assertTrue($props['persiapan']['daftarBelumAda']);
        $this->assertFalse($props['persiapan']['semuaTidakBerlaku']);
        $this->assertCount(0, $props['persiapan']['butir']);

        $halaman = File::get(resource_path('js/pages/Trips/Readiness.tsx'));
        $this->assertStringContainsString('Buat daftar persiapan', $halaman);
    }

    /**
     * Sebab UNKNOWN sendirian, tanpa sebab lain (kecocokan dan persiapan sudah beres).
     *
     * MIN-1 (putaran perbaikan 3): seed PreparationTemplateSeeder DITAMBAHKAN di sini --
     * sebelumnya test ini (dan test_needs_preparation_names_condition_warnings_alone... di
     * bawah) memanggil trip() TANPA menyeed dulu. PreparationService::applicableItems()
     * membaca tabel template PreparationItem, yang kosong tanpa seed, sehingga
     * generateFor() di dalam trip() membuat NOL butir -- trip()-nya "beres" secara
     * kebetulan (preparationItems kosong, bukan karena seluruhnya dikonfirmasi). Ini cacat
     * laten: begitu MIN-1 menambah cabang `$totalItems === 0` ke needsPreparationCauses(),
     * fixture ini akan ikut memicu "Daftar persiapan belum ada." dan merusak klaim
     * "sendirian" test ini. Dengan seed, trip() benar-benar menghasilkan butir dari
     * template lalu mengonfirmasi semuanya, jadi `totalItems > 0` dan tidak ada cabang
     * persiapan yang menyala.
     */
    public function test_needs_preparation_names_unknown_status_alone_when_it_is_the_only_cause(): void
    {
        $this->seed(PreparationTemplateSeeder::class);
        $trip = $this->trip();

        $props = $this->actingAs($trip->user)->get(route('trips.readiness', $trip))->viewData('page')['props'];

        $this->assertSame('NEEDS_PREPARATION', $props['keadaan']['nilai']);
        $this->assertSame('Status resmi jalur ini belum diketahui.', $props['keadaan']['kalimat']);
    }

    /** Sebab Kurang cocok sendirian, tanpa sebab lain (status Buka, persiapan beres). */
    public function test_needs_preparation_names_kurang_cocok_alone_when_it_is_the_only_cause(): void
    {
        $this->seed(PreparationTemplateSeeder::class);
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::BEGINNER->value, 'completed_at' => now()]);
        $trail = Trail::factory()->published()->highlyTechnical()->create();
        $trail->officialStatuses()->update(['status' => OfficialStatusValue::OPEN->value]);

        $trip = TripPlan::create([
            'user_id' => $user->id,
            'trail_id' => $trail->id,
            'name' => 'N21',
            'planned_date' => now()->addDays(5)->toDateString(),
            'trip_type' => TripType::CAMPING->value,
            'status' => TripStatus::PLANNED->value,
        ]);
        app(PreparationService::class)->generateFor($trip);
        $trip->preparationItems()->update(['status' => PreparationStatus::CONFIRMED->value, 'status_updated_at' => now()]);

        $props = $this->actingAs($user)->get(route('trips.readiness', $trip))->viewData('page')['props'];

        $this->assertSame('NEEDS_PREPARATION', $props['keadaan']['nilai']);
        $this->assertSame('Kecocokan jalur untuk rencana ini Kurang cocok.', $props['keadaan']['kalimat']);
    }

    /**
     * X14 (putaran perbaikan 3, R-006). determineState() menggerbangi cabang KURANG_COCOK
     * dengan $profilLengkap (ReadinessService.php:174, diuji N19/N19b), tetapi
     * needsPreparationCauses() menyimpan SALINAN gerbang yang sama (:360) karena $fit->label
     * yang diterimanya masih mentah, belum dinolkan seperti routeFitSnapshot (I4). Tanpa
     * status UNKNOWN di sini, profil tidak lengkap membuat determineState() pulang ke READY
     * (gerbangnya sendiri benar) dan needsPreparationCauses() tidak pernah dipanggil sama
     * sekali (dipanggil hanya saat state NEEDS_PREPARATION) -- sehingga salinan gerbangnya
     * tidak pernah diuji terpisah dari gerbang determineState(). Status UNKNOWN di sini
     * membuat NEEDS_PREPARATION tercapai lewat DISJUNCT LAIN pada baris yang sama
     * (ReadinessService.php:174: "... || $status === UNKNOWN"), sehingga needsPreparationCauses()
     * benar-benar berjalan sambil profil tetap tidak lengkap -- kalau salinan gerbangnya
     * dibuang, kalimat ini akan ikut menyebut "Kurang cocok" padahal kartu kecocokannya
     * sendiri membaca "Belum dinilai" (pelanggaran R-006: label mentah/UNKNOWN diperlakukan
     * seolah penilaian pasti).
     */
    public function test_an_incomplete_profile_does_not_name_kurang_cocok_even_when_unknown_status_triggers_preparation(): void
    {
        $this->seed(PreparationTemplateSeeder::class);
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::BEGINNER->value, 'completed_at' => null]);
        $trail = Trail::factory()->published()->highlyTechnical()->create();

        $trip = TripPlan::create([
            'user_id' => $user->id,
            'trail_id' => $trail->id,
            'name' => 'X14',
            'planned_date' => now()->addDays(5)->toDateString(),
            'trip_type' => TripType::CAMPING->value,
            'status' => TripStatus::PLANNED->value,
        ]);
        app(PreparationService::class)->generateFor($trip);
        $trip->preparationItems()->update(['status' => PreparationStatus::CONFIRMED->value, 'status_updated_at' => now()]);

        $props = $this->actingAs($user)->get(route('trips.readiness', $trip))->viewData('page')['props'];

        $this->assertSame('NEEDS_PREPARATION', $props['keadaan']['nilai']);
        $this->assertSame('Status resmi jalur ini belum diketahui.', $props['keadaan']['kalimat']);
        $this->assertStringNotContainsString('Kurang cocok', $props['keadaan']['kalimat']);
        $this->assertFalse($props['kecocokan']['profilLengkap']);
        $this->assertNull($props['kecocokan']['label']);
        $this->assertSame('Belum dinilai', $props['kecocokan']['labelText']);
    }

    /**
     * Sebab peringatan kondisi sendirian, tanpa sebab lain (status Buka, kecocokan dan
     * persiapan beres). MIN-1 (putaran perbaikan 3): seed ditambahkan -- lihat docblock
     * test_needs_preparation_names_unknown_status_alone_when_it_is_the_only_cause di atas
     * untuk alasan lengkapnya; cacat latennya sama (trip() tanpa seed menghasilkan NOL
     * butir, bukan butir yang seluruhnya beres).
     */
    public function test_needs_preparation_names_condition_warnings_alone_when_it_is_the_only_cause(): void
    {
        $this->seed(PreparationTemplateSeeder::class);
        $trip = $this->trip();
        $trip->trail->officialStatuses()->update(['status' => OfficialStatusValue::OPEN->value]);
        TrailConditionReport::factory()->approved()->create(['trail_id' => $trip->trail_id, 'condition_tags' => [ConditionTag::SLIPPERY->value]]);

        $props = $this->actingAs($trip->user)->get(route('trips.readiness', $trip))->viewData('page')['props'];

        $this->assertSame('NEEDS_PREPARATION', $props['keadaan']['nilai']);
        $this->assertSame('Ada peringatan kondisi yang perlu ditinjau.', $props['keadaan']['kalimat']);
    }

    /**
     * W4-6a (probe R4-D): bila seluruh butir ditandai Tidak berlaku, completion_percent
     * jatuh ke 0 tanpa satu pun butir kritis atau butir lain yang "belum", jadi kasus ini
     * perlu kalimat dan aksinya sendiri, bukan jatuh ke kalimat cadangan lama tanpa aksi
     * apa pun.
     */
    public function test_all_items_marked_not_applicable_has_its_own_sentence_and_action(): void
    {
        $this->seed(PreparationTemplateSeeder::class);
        $trip = $this->trip();
        $trip->preparationItems()->update(['status' => PreparationStatus::NOT_APPLICABLE->value]);

        $props = $this->actingAs($trip->user)->get(route('trips.readiness', $trip))->viewData('page')['props'];

        $this->assertSame('NEEDS_PREPARATION', $props['keadaan']['nilai']);
        $this->assertStringContainsString('Seluruh butir persiapan ditandai Tidak berlaku.', $props['keadaan']['kalimat']);
        $this->assertTrue($props['persiapan']['semuaTidakBerlaku']);
        // MIN-2: daftar yang butirnya ada tetapi seluruhnya Tidak berlaku tidak ditulis "belum
        // ada", dan kalimatnya sama dengan ruang kerja (TripSteps::teksPersiapan()).
        $this->assertSame(
            'Semua '.$trip->preparationItems()->count().' butir ditandai Tidak berlaku',
            $props['persiapan']['teks']
        );

        $halaman = File::get(resource_path('js/pages/Trips/Readiness.tsx'));
        $this->assertStringContainsString('Tinjau daftar persiapan', $halaman);
    }

    /**
     * W4-6d (R1, putaran perbaikan 2): membunuh N1 (determineState tanpa gerbang profil),
     * N2 (label snapshot tidak dinolkan, di memori maupun baris tersimpan), N3 (baris Sejak
     * tanpa gerbang profil), dan N19 (cabang KURANG_COCOK dibuang untuk semua orang, bukan
     * hanya digerbangi profil).
     */
    public function test_an_incomplete_profile_gates_the_fit_label_everywhere_it_is_read(): void
    {
        $this->seed(PreparationTemplateSeeder::class);
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::BEGINNER->value, 'completed_at' => null]);
        $trail = Trail::factory()->published()->highlyTechnical()->create();
        $trail->officialStatuses()->update(['status' => OfficialStatusValue::OPEN->value]);

        $trip = TripPlan::create([
            'user_id' => $user->id,
            'trail_id' => $trail->id,
            'name' => 'I4 gerbang',
            'planned_date' => now()->addDays(5)->toDateString(),
            'trip_type' => TripType::CAMPING->value,
            'status' => TripStatus::PLANNED->value,
        ]);
        app(PreparationService::class)->generateFor($trip);
        $trip->preparationItems()->update(['status' => PreparationStatus::CONFIRMED->value, 'status_updated_at' => now()]);

        $assessment = app(ReadinessService::class)->compute($trip->fresh());
        // N1: tanpa gerbang, label mentah Kurang cocok akan menurunkan keadaan ini sendiri.
        $this->assertSame('READY', $assessment->state->value);
        // N2 (memori): label snapshot, bukan hanya keadaan, harus null.
        $this->assertNull($assessment->routeFitSnapshot['label']);

        $terakhir = app(ReadinessService::class)->record($trip);
        // N2 (baris tersimpan): label di baris DB juga null, bukan hanya di memori.
        $this->assertNull($terakhir->fresh()->route_fit_snapshot['label']);

        $sejak = $this->actingAs($user)->get(route('trips.readiness', $trip))->viewData('page')['props']['sejakTerakhir'];
        // N3: baris Sejak memakai gerbang yang sama, bukan membandingkan label mentah.
        $this->assertSame(
            ['jenis' => 'sama', 'teks' => 'Label kecocokan belum dinilai karena profil pendaki belum lengkap.'],
            $sejak['butir'][3]
        );

        $user->profile->update(['completed_at' => now()]);

        $assessmentSesudah = app(ReadinessService::class)->compute($trip->fresh());
        // N19: cabang KURANG_COCOK hanya berlaku bila profil memang lengkap; pendaki
        // BERPROFIL tetap diturunkan ke NEEDS_PREPARATION (tidak dibuang untuk semua orang).
        $this->assertSame('NEEDS_PREPARATION', $assessmentSesudah->state->value);
        $this->assertSame(['Kecocokan jalur untuk rencana ini Kurang cocok.'], $assessmentSesudah->explanation['preparation_causes']);
    }

    /**
     * m5 "sebagian" (R8, putaran perbaikan 2): akhir data prakiraan tersimpan jatuh pada
     * tanggal trip sendiri (bukan menjangkau sampai hari sesudahnya) adalah sinyal yang
     * berdasar, tanpa ambang jumlah slot yang diasumsikan.
     */
    public function test_the_forecast_says_how_far_the_stored_data_reaches_on_the_trip_date(): void
    {
        $this->travelTo(Carbon::parse('2026-10-08 01:00:00', 'UTC'));
        $trip = $this->trip();
        $trip->update(['planned_date' => '2026-10-10']);

        WeatherSnapshot::factory()->create(['adm4_code' => $trip->trail->weather_adm4_code, 'forecast_at' => Carbon::parse('2026-10-10 00:00:00', 'UTC'), 'fetched_at' => now(), 'weather_description' => 'Cerah']);
        WeatherSnapshot::factory()->create(['adm4_code' => $trip->trail->weather_adm4_code, 'forecast_at' => Carbon::parse('2026-10-10 09:00:00', 'UTC'), 'fetched_at' => now(), 'weather_description' => 'Berawan']);

        $prakiraan = $this->actingAs($trip->user)->get(route('trips.readiness', $trip))->viewData('page')['props']['kondisi']['prakiraan'];

        $this->assertSame('tersedia', $prakiraan['keadaan']);
        $this->assertSame('Prakiraan BMKG baru menjangkau sampai pukul 16.00 WIB pada tanggal ini.', $prakiraan['sebagian']);
    }

    /**
     * X6 (putaran perbaikan 3): MAX(forecast_at) di HalamanKesiapan::prakiraan()
     * (HalamanKesiapan.php:330) WAJIB dibatasi ke adm4_code milik jalur trip ini sendiri.
     * Area KEDUA dengan kode BERBEDA diberi data yang lebih BARU, pada tanggal yang SAMA --
     * bila batas area dibuang, MAX lintas area membaca akhir data milik area lain itu, dan
     * kalimat "sebagian" salah menyebut jam yang tidak relevan dengan jalur trip ini.
     */
    public function test_the_partial_forecast_signal_is_scoped_to_the_trip_s_own_area(): void
    {
        $this->travelTo(Carbon::parse('2026-10-08 01:00:00', 'UTC'));
        $trip = $this->trip();
        $trip->update(['planned_date' => '2026-10-10']);

        WeatherSnapshot::factory()->create(['adm4_code' => $trip->trail->weather_adm4_code, 'forecast_at' => Carbon::parse('2026-10-10 00:00:00', 'UTC'), 'fetched_at' => now(), 'weather_description' => 'Cerah']);
        WeatherSnapshot::factory()->create(['adm4_code' => $trip->trail->weather_adm4_code, 'forecast_at' => Carbon::parse('2026-10-10 09:00:00', 'UTC'), 'fetched_at' => now(), 'weather_description' => 'Berawan']);

        // Area LAIN, kode berbeda, akhir data lebih BARU (21.00 WIB > 16.00 WIB) pada tanggal
        // yang sama -- harus diabaikan sepenuhnya oleh MAX milik area trip ini.
        WeatherSnapshot::factory()->create([
            'adm4_code' => $trip->trail->weather_adm4_code.'-area-lain',
            'forecast_at' => Carbon::parse('2026-10-10 14:00:00', 'UTC'),
            'fetched_at' => now(),
            'weather_description' => 'Hujan',
        ]);

        $prakiraan = $this->actingAs($trip->user)->get(route('trips.readiness', $trip))->viewData('page')['props']['kondisi']['prakiraan'];

        $this->assertSame('tersedia', $prakiraan['keadaan']);
        $this->assertSame('Prakiraan BMKG baru menjangkau sampai pukul 16.00 WIB pada tanggal ini.', $prakiraan['sebagian']);
    }

    /**
     * m-3 (N12, N18, putaran perbaikan 2): batas hari prakiraan memakai zona GUNUNG, bukan
     * UTC, dan penanda zonanya bukan selalu "WIB" -- test m3/m5 yang sudah ada hanya
     * memakai WIB, jadi mutasi yang menukarnya ke UTC atau mengunci zona ke WIB lolos tanpa
     * test ini.
     */
    public function test_the_forecast_day_boundary_uses_the_mountain_zone_not_utc(): void
    {
        $this->travelTo(Carbon::parse('2026-10-08 01:00:00', 'UTC'));
        $mountain = Mountain::factory()->create(['timezone' => 'Asia/Makassar']);
        $trail = Trail::factory()->published()->for($mountain)->easy()->create();
        $trip = TripPlan::create([
            'user_id' => User::factory()->create()->id,
            'trail_id' => $trail->id,
            'name' => 'N12',
            'planned_date' => '2026-10-10',
            'trip_type' => TripType::CAMPING->value,
            'status' => TripStatus::PLANNED->value,
        ]);

        // Batas hari 10 Okt WITA (UTC+8): 9 Okt 16.00 UTC sampai 10 Okt 15.59.59 UTC.
        foreach ([
            ['2026-10-09 15:00:00', 'Sebelum hari'],
            ['2026-10-09 16:00:00', 'Awal hari'],
            ['2026-10-10 14:00:00', 'Tengah hari'],
            ['2026-10-10 16:00:00', 'Sesudah hari'],
        ] as [$waktuUtc, $label]) {
            WeatherSnapshot::factory()->create([
                'adm4_code' => $trail->weather_adm4_code,
                'forecast_at' => Carbon::parse($waktuUtc, 'UTC'),
                'fetched_at' => now(),
                'weather_description' => $label,
            ]);
        }

        $prakiraan = $this->actingAs($trip->user)->get(route('trips.readiness', $trip))->viewData('page')['props']['kondisi']['prakiraan'];

        $this->assertSame('WITA', $prakiraan['zona']);
        $this->assertSame(['Awal hari', 'Tengah hari'], array_column($prakiraan['baris'], 'cuaca'));
    }

    /**
     * m-3 (N6, putaran perbaikan 2): latestConfirmed() wajib menyaring
     * pre_departure_confirmed -- tanpa saringan itu, "Simpan hasil cek ini" (yang TIDAK
     * mengonfirmasi apa pun) akan terbaca sebagai tinjauan yang sudah tercatat.
     */
    public function test_a_saved_but_unconfirmed_check_does_not_read_as_reviewed(): void
    {
        $trip = $this->trip();
        $this->actingAs($trip->user)->post(route('trips.readiness.recompute', $trip));

        $props = $this->actingAs($trip->user)->get(route('trips.readiness', $trip))->viewData('page')['props'];

        $this->assertFalse($props['preDeparture']['dikonfirmasi']);
        $this->assertNull($props['preDeparture']['dicatat']);
        $this->assertTrue($props['bolehKonfirmasi']);
    }

    /**
     * m-3: rute confirm dibatasi laju yang sama dengan recompute. Throttle pada kedua rute
     * sudah dikerjakan putaran 1, tetapi hanya recompute yang diuji; rute confirm luput.
     */
    public function test_confirm_route_has_the_same_throttle_as_recompute(): void
    {
        $middleware = Route::getRoutes()->getByName('trips.readiness.confirm')->gatherMiddleware();

        $this->assertContains('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,cek-kesiapan', $middleware);
    }

    /**
     * m-1 (Minor baru, putaran perbaikan 2, probe R9-A): tombol tinjauan bukan aksi utama
     * selama masih ada butir kritis yang tersisa, termasuk yang menunggu BMKG. Memakai
     * persiapan.butir.length (bukan siapDiselesaikan) supaya pendaki tidak didorong
     * mencatat tinjauannya sebelum prakiraan terbit, padahal tinjauan itu tidak dapat
     * diulang (W4-6b).
     *
     * NIT-6/MIN-4 (putaran perbaikan 3): string yang dikunci diperbarui. `!tidakDisarankan &&`
     * dibuang -- mati sejak awal, karena bolehKonfirmasi sudah false untuk NOT_RECOMMENDED
     * (alasanPenolakan menolaknya lebih dulu), jadi cabang tombol ini tidak pernah tercapai
     * selagi tidakDisarankan benar. `|| persiapan.menungguLain !== null` ditambah supaya butir
     * cuaca NON-KRITIS yang menunggu BMKG (tidak pernah masuk persiapan.butir yang khusus
     * kritis) juga menahan tombol ini sebagai sekunder, bukan hanya butir kritis.
     */
    public function test_the_review_button_is_not_primary_while_critical_items_remain(): void
    {
        $halaman = File::get(resource_path('js/pages/Trips/Readiness.tsx'));

        $this->assertStringContainsString(
            "persiapan.butir.length > 0 || persiapan.menungguLain !== null || persiapan.daftarBelumAda ? 'secondary' : 'primary'",
            $halaman
        );
        $this->assertStringNotContainsString('!tidakDisarankan && persiapan.butir.length', $halaman);
    }

    /**
     * m-2 (Minor baru, putaran perbaikan 2, probe R9-B): "Lanjutkan persiapan" tidak boleh
     * menawarkan tautan ke butir yang akan ditolak server (butir cuaca non-kritis yang
     * masih menunggu BMKG bukan "lanjutan" yang benar-benar dapat dikerjakan sekarang).
     *
     * Diperluas MIN-4 (putaran perbaikan 3, probe M2b): butir cuaca non-kritis yang menunggu
     * ini sebelumnya hilang begitu saja dari kartu -- tidak masuk persiapan.butir (khusus
     * kritis), tidak dihitung di mana pun selain "Seluruh daftar". menungguLain sekarang
     * menyebutnya dengan tanggal terbitnya (syarat (c) MIN-4), dan tombol tinjauan tetap
     * sekunder selama butir ini menunggu (lihat test_the_review_button_is_not_primary_while_critical_items_remain).
     */
    public function test_lanjutkan_persiapan_excludes_items_waiting_for_bmkg(): void
    {
        $this->seed(PreparationTemplateSeeder::class);
        $trip = $this->trip();
        $cuaca = $trip->preparationItems()->where('category', PreparationCategory::WEATHER->value)->where('is_critical', false)->firstOrFail();
        $cuaca->update(['status' => PreparationStatus::NOT_CONFIRMED->value]);

        $props = $this->actingAs($trip->user)->get(route('trips.readiness', $trip))->viewData('page')['props'];

        $this->assertCount(0, $props['persiapan']['butir']);
        $this->assertFalse($props['persiapan']['lanjutkan']);
        // MIN-4(c): kalimatnya menyebut tanggal terbit, bukan hanya "menunggu BMKG" kosong.
        $this->assertSame(1, substr_count($props['persiapan']['menungguLain'] ?? '', 'menunggu BMKG (terbit '));
        $this->assertMatchesRegularExpression('/^1 butir lain menunggu BMKG \(terbit .+\)\.$/', $props['persiapan']['menungguLain']);
    }

    /**
     * MIN-4 (putaran perbaikan 3, probe M2b): seluruh butir KRITIS menunggu BMKG (bukan
     * dikonfirmasi atau kosong), TETAPI satu butir non-kritis NON-cuaca tetap benar-benar
     * dapat dikerjakan sekarang. Sebelum putaran ini, lanjutkan disyaratkan persiapan.butir
     * KOSONG (HalamanKesiapan.php, sebelum diperluas ke siapDiselesaikan === 0) -- kombinasi
     * ini (butir kritis ADA tetapi seluruhnya menunggu) tidak pernah bisa true, sehingga
     * pendaki terjebak tanpa jalan ke butir non-kritis yang sebenarnya bisa diselesaikan.
     */
    public function test_lanjutkan_persiapan_is_offered_when_all_critical_items_are_waiting_for_bmkg(): void
    {
        $this->seed(PreparationTemplateSeeder::class);
        $trip = $this->trip();
        $trip->preparationItems()->where('category', PreparationCategory::WEATHER->value)->where('is_critical', true)->sole()
            ->update(['status' => PreparationStatus::NOT_CONFIRMED->value]);
        $trip->preparationItems()->where('label', 'Referensi navigasi jalur')->sole()
            ->update(['status' => PreparationStatus::NOT_CONFIRMED->value]);

        $props = $this->actingAs($trip->user)->get(route('trips.readiness', $trip))->viewData('page')['props'];

        $this->assertCount(1, $props['persiapan']['butir']);
        $this->assertSame(0, $props['persiapan']['siapDiselesaikan']);
        $this->assertNotNull($props['persiapan']['semuaMenungguBmkg']);
        $this->assertTrue($props['persiapan']['lanjutkan']);

        $halaman = File::get(resource_path('js/pages/Trips/Readiness.tsx'));
        $this->assertStringContainsString('persiapan.lanjutkan ? (', $halaman);
    }

    /**
     * MIN-5 (putaran perbaikan 3). Fakta terverifikasi koordinator: tidak ada jalur aplikasi
     * SAAT INI yang membalikkan status dari Siap berangkat ke Direncanakan, tetapi
     * TripStatus::READY_FOR_DEPARTURE->allowedTransitions() (TripStatus.php:44) mengizinkannya
     * -- cacat laten yang ditutup SEBELUM ada fitur yang memakai transisi itu, bukan sesudah
     * insiden. Baris terkonfirmasi LAMA pada trip yang sudah dikembalikan ke PLANNED tidak
     * boleh membuat halaman mengaku "sudah ditinjau" selamanya; TripPlan::tinjauanTercatat()
     * mensyaratkan status yang tidak dapat lagi bertransisi ke Siap berangkat, bukan hanya baris
     * itu ada (NEW-2). Halaman dan POST confirm() harus sepakat: keduanya membaca
     * PreDepartureCheck::alasanPenolakan().
     */
    public function test_a_planned_trip_with_an_old_confirmed_row_is_offered_the_button_and_post_accepts(): void
    {
        $trip = $this->trip();

        $this->actingAs($trip->user)->post(route('trips.readiness.confirm', $trip))->assertRedirect(route('trips.readiness', $trip));
        $this->assertSame(TripStatus::READY_FOR_DEPARTURE, $trip->fresh()->status);
        $this->assertSame(1, ReadinessCheck::where('trip_plan_id', $trip->id)->where('pre_departure_confirmed', true)->count());

        // Status dikembalikan ke PLANNED secara langsung (bukan lewat jalur aplikasi mana pun
        // saat ini) untuk menutup celah sebelum ada fitur yang memakainya -- lihat docblock di
        // atas. refresh() DULU: $trip di memori masih membawa status PLANNED dari sebelum
        // post(...) confirm (confirm() memperbarui baris lewat instance model LAIN di dalam
        // controller/service, bukan $trip ini). Tanpa refresh(), update(['status' => 'PLANNED'])
        // tidak dianggap dirty (nilai baru == nilai lama DI MEMORI) dan Eloquent tidak pernah
        // mengirim query UPDATE -- baris DB tetap READY_FOR_DEPARTURE dari confirm() di atas,
        // dan test ini gagal menguji apa pun (cacat test, bukan cacat PreDepartureCheck).
        $trip->refresh()->update(['status' => TripStatus::PLANNED->value]);

        $props = $this->actingAs($trip->user)->get(route('trips.readiness', $trip))->viewData('page')['props'];
        $this->assertFalse($props['preDeparture']['dikonfirmasi']);
        $this->assertNull($props['preDeparture']['dicatat']);
        $this->assertTrue($props['bolehKonfirmasi']);
        $this->assertNull($props['alasanTanpaTinjauan']);

        $this->actingAs($trip->user)->post(route('trips.readiness.confirm', $trip))->assertRedirect(route('trips.readiness', $trip))->assertSessionHas('status');
        $this->assertSame(TripStatus::READY_FOR_DEPARTURE, $trip->fresh()->status);
        $this->assertSame(2, ReadinessCheck::where('trip_plan_id', $trip->id)->where('pre_departure_confirmed', true)->count());
    }

    /**
     * NIT-3 (putaran perbaikan 3): status yang tidak dapat bertransisi didahulukan atas
     * NOT_RECOMMENDED di PreDepartureCheck::alasanPenolakan() (sebelumnya NOT_RECOMMENDED
     * diperiksa lebih dulu, probe "tutup_IN_PROGRESS" task-6-fix2-review.md). Trip yang
     * sedang berlangsung memang tidak akan pernah bisa ditinjau SEKARANG terlepas dari
     * penilaian kesiapannya; menyalahkan "rencana ini tidak disarankan" pada jalur yang
     * kebetulan juga Tutup kurang tepat dibanding menyebut status trip itu sendiri.
     */
    public function test_a_terminal_status_names_itself_even_when_the_plan_is_also_not_recommended(): void
    {
        $trip = $this->trip();
        $trip->trail->officialStatuses()->update(['status' => OfficialStatusValue::CLOSED->value]);
        $trip->update(['status' => TripStatus::IN_PROGRESS->value]);

        $props = $this->actingAs($trip->user)->get(route('trips.readiness', $trip))->viewData('page')['props'];

        $this->assertSame(
            'Tinjauan tidak dapat dicatat karena trip ini berstatus '.TripStatus::IN_PROGRESS->label().'.',
            $props['alasanTanpaTinjauan']
        );
        $this->assertStringNotContainsString('tidak disarankan', $props['alasanTanpaTinjauan']);
    }

    public function test_another_user_cannot_recompute_or_confirm(): void
    {
        $trip = $this->trip();
        $penyusup = User::factory()->create();

        $this->actingAs($penyusup)->post(route('trips.readiness.recompute', $trip))->assertForbidden();
        $this->actingAs($penyusup)->post(route('trips.readiness.confirm', $trip))->assertForbidden();

        $this->assertSame(0, ReadinessCheck::count());
        $this->assertSame(TripStatus::PLANNED, $trip->fresh()->status);
    }

    private function trip(): TripPlan
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::ADVANCED->value, 'completed_at' => now()]);

        $mountain = Mountain::factory()->create();
        $trail = Trail::factory()->published()->for($mountain)->easy()->create();

        $trip = TripPlan::create([
            'user_id' => $user->id,
            'trail_id' => $trail->id,
            'name' => 'Uji kesiapan',
            'planned_date' => now()->addDays(5)->toDateString(),
            'trip_type' => TripType::CAMPING->value,
            'status' => TripStatus::PLANNED->value,
        ]);

        // Seluruh item persiapan dikonfirmasi agar state tidak tertahan di sana.
        app(PreparationService::class)->generateFor($trip);
        $trip->preparationItems()->update([
            'status' => PreparationStatus::CONFIRMED->value,
            'status_updated_at' => now(),
        ]);

        return $trip->fresh();
    }
}
