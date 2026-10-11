<?php

namespace Tests\Feature;

use App\Enums\ReadinessState;
use App\Enums\TripStatus;
use App\Models\ReadinessCheck;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\Trips\PintuMendaki;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use ReflectionClass;
use Tests\TestCase;

/**
 * Tab Mendaki (IA v2, DK-25; spec v3 bagian 6.12; rencana induk W6 butir 1): pintu mode lapangan.
 *
 * Pendaki yang mengetuk tab ini di jalur butuh mode lapangannya, bukan daftar. Tanpa pendakian
 * berlangsung, tab menampilkan pratinjau hari-H trip terdekat yang dapat dimulai (keadaan paket
 * luring, penjelasan izin lokasi, rekam jejak yang mati bawaan) dan trip lain yang dapat dimulai.
 * Status trip "Siap berangkat" dan kata keadaan kesiapan tidak tampil (ruling W4-C4 untuk
 * Mendaki, W6-R9).
 */
class TrackTabTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_hike_in_progress_opens_straight_into_field_mode(): void
    {
        $trip = TripPlan::factory()->create(['status' => TripStatus::IN_PROGRESS->value]);

        $this->actingAs($trip->user)
            ->get(route('track'))
            ->assertRedirect(route('trips.hike', $trip));
    }

    public function test_the_nearest_startable_trip_gets_a_day_preview_and_the_rest_are_listed(): void
    {
        $this->travelTo(Carbon::parse('2026-10-09 04:00:00', 'UTC'));

        $user = User::factory()->create();
        $besok = Carbon::now('Asia/Jakarta')->addDay()->toDateString();
        $siap = TripPlan::factory()->for($user)->create(['name' => 'Siap', 'status' => TripStatus::READY_FOR_DEPARTURE->value, 'planned_date' => $besok]);
        $rencana = TripPlan::factory()->for($user)->create(['name' => 'Rencana', 'status' => TripStatus::PLANNED->value, 'planned_date' => $besok]);
        TripPlan::factory()->for($user)->create(['name' => 'Selesai', 'status' => TripStatus::COMPLETED->value]);
        TripPlan::factory()->for($user)->create(['name' => 'Batal', 'status' => TripStatus::CANCELLED->value, 'planned_date' => $besok]);
        TripPlan::factory()->for($user)->create(['name' => 'Lewat', 'status' => TripStatus::PLANNED->value, 'planned_date' => Carbon::now('Asia/Jakarta')->subDays(10)->toDateString()]);

        $this->actingAs($user)
            ->get(route('track'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Track/Index')
                ->where('title', 'Mendaki')
                ->where('hariH.id', $siap->id)
                ->where('hariH.hari', 'H-1')
                ->where('hariH.cek', 'Cek kesiapan belum dilakukan untuk trip ini.')
                ->where('hariH.urls.mulai', route('trips.start', $siap))
                ->where('hariH.urls.lapangan', route('trips.hike', $siap))
                ->has('lain', 1)
                ->where('lain.0.id', $rencana->id)
                ->where('lain.0.urls.mulai', route('trips.start', $rencana)));
    }

    /**
     * I-3/ruling W6-4d, daftar periksa butir 1 dan 3: fixture disusun berlawanan dengan hasil
     * yang diharapkan. Trip yang lebih jauh dibuat lebih dulu (id lebih kecil), trip terdekat
     * dibuat belakangan (id lebih besar). Tanpa orderBy('planned_date'), urutan baris yang tidak
     * diurutkan akan kebetulan sama dengan urutan id di SQLite (trip pertama yang dibuat tampil
     * pertama), sehingga test lama (trip terdekat sudah dibuat lebih dulu) tidak pernah bisa
     * membuktikan pengurutan ini; fixture ini membalik urutan pembuatan supaya mutasi yang
     * membuang orderBy('planned_date') terbukti merah.
     */
    public function test_the_nearer_trip_wins_even_when_created_after_the_farther_one(): void
    {
        $this->travelTo(Carbon::parse('2026-10-09 04:00:00', 'UTC'));

        $user = User::factory()->create();
        $jauh = TripPlan::factory()->for($user)->create(['status' => TripStatus::PLANNED->value, 'planned_date' => Carbon::now('Asia/Jakarta')->addDays(20)->toDateString()]);
        $dekat = TripPlan::factory()->for($user)->create(['status' => TripStatus::PLANNED->value, 'planned_date' => Carbon::now('Asia/Jakarta')->addDays(2)->toDateString()]);

        $this->assertLessThan($dekat->id, $jauh->id, 'Fixture ini butuh trip yang lebih jauh dibuat lebih dulu (id lebih kecil).');

        $this->actingAs($user)->get(route('track'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('hariH.id', $dekat->id));
    }

    /**
     * Fixture berlawanan yang sama (I-3/ruling W6-4d): trip berstatus rencana dibuat lebih dulu
     * (id lebih kecil), trip siap berangkat belakangan (id lebih besar), tanggal sama. Tanpa
     * orderByRaw prioritas siap berangkat, urutan id menang dan trip rencana tampil di hari-H,
     * bukan trip siap berangkat.
     */
    public function test_ready_for_departure_wins_the_tie_even_when_created_after_planned(): void
    {
        $this->travelTo(Carbon::parse('2026-10-09 04:00:00', 'UTC'));

        $user = User::factory()->create();
        $besok = Carbon::now('Asia/Jakarta')->addDay()->toDateString();
        $rencana = TripPlan::factory()->for($user)->create(['status' => TripStatus::PLANNED->value, 'planned_date' => $besok]);
        $siap = TripPlan::factory()->for($user)->create(['status' => TripStatus::READY_FOR_DEPARTURE->value, 'planned_date' => $besok]);

        $this->assertLessThan($siap->id, $rencana->id, 'Fixture ini butuh trip rencana dibuat lebih dulu (id lebih kecil).');

        $this->actingAs($user)->get(route('track'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('hariH.id', $siap->id));
    }

    /**
     * Pintu Mendaki tidak menyebut status trip ("Siap berangkat", "Direncanakan") maupun
     * vonis kesiapan: cek kesiapan disebut tanggalnya, dan tautannya membuka hasilnya (R-014).
     * Trip kedua (M-2/ruling W6-4g) memastikan `lain` tidak kosong, supaya pemeriksaan ini juga
     * menjaga baris trip lain, bukan hanya hari-H.
     */
    public function test_no_status_word_or_verdict_reaches_the_tab(): void
    {
        $this->travelTo(Carbon::parse('2026-10-06 04:00:00', 'UTC'));

        $user = User::factory()->create();
        $trail = Trail::factory()->create();
        $trail->mountain->update(['timezone' => 'Asia/Makassar']);
        $trip = TripPlan::factory()->for($user)->for($trail)->create(['status' => TripStatus::READY_FOR_DEPARTURE->value, 'planned_date' => Carbon::now('Asia/Makassar')->addDays(3)->toDateString()]);
        TripPlan::factory()->for($user)->create(['status' => TripStatus::PLANNED->value, 'planned_date' => Carbon::now('Asia/Makassar')->addDays(10)->toDateString()]);

        ReadinessCheck::create([
            'trip_plan_id' => $trip->id,
            'computed_state' => ReadinessState::READY->value,
            'computed_at' => Carbon::parse('2026-10-06 18:30:00', 'UTC'),
        ]);

        $respons = $this->actingAs($user)->get(route('track'))->assertOk();

        $respons->assertInertia(fn (AssertableInertia $page) => $page->where('hariH.cek', 'Cek kesiapan terakhir 7 Okt 2026.')->has('lain', 1));

        foreach (['Siap berangkat', 'Direncanakan', ReadinessState::READY->label()] as $kata) {
            $this->assertStringNotContainsString($kata, json_encode($respons->viewData('page')['props'], JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * M-7/ruling W6-4l, probe B tinjauan: lantai tanggal memakai WIB (zona yang paling akhir
     * berganti hari), jadi trip yang tanggalnya sudah lewat di zona gunungnya sendiri (WIT,
     * paling dulu berganti hari) masih lolos saringan tanggal. `lewat` dari KepalaTrip wajib
     * ikut sampai ke tab ini dan ditampilkan seperti halaman trip (ruling W4-1c).
     */
    public function test_a_trip_whose_date_has_passed_in_its_own_timezone_says_so(): void
    {
        $this->travelTo(Carbon::parse('2026-10-09 16:30:00', 'UTC'));

        $user = User::factory()->create();
        $trail = Trail::factory()->create();
        $trail->mountain->update(['timezone' => 'Asia/Jayapura']);
        $trip = TripPlan::factory()->for($user)->for($trail)->create(['status' => TripStatus::PLANNED->value, 'planned_date' => '2026-10-09']);

        $this->actingAs($user)->get(route('track'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('hariH.id', $trip->id)
                ->where('hariH.lewat', 'Tanggal rencana sudah lewat'));
    }

    /**
     * N-2/ruling W6-4v (putaran perbaikan 2): baris trip lain juga menyebut tanggal yang sudah
     * lewat di zona gunungnya sendiri, kalimat yang sama dengan hari-H (M-7/ruling W6-4l). Kedua
     * trip tanggalnya sama (9 Okt) dan berstatus sama (PLANNED) supaya keduanya lolos lantai WIB
     * dan hanya `orderBy('id')` yang memisahkan hari-H dari trip lain; trip pertama (WIB, belum
     * lewat) dibuat lebih dulu sehingga menjadi hari-H, trip kedua (WIT, sudah lewat di zonanya)
     * menjadi baris trip lain pertama.
     */
    public function test_a_trip_further_down_the_list_whose_date_has_passed_in_its_own_timezone_says_so(): void
    {
        $this->travelTo(Carbon::parse('2026-10-09 16:30:00', 'UTC'));

        $user = User::factory()->create();
        $hariH = TripPlan::factory()->for($user)->create(['status' => TripStatus::PLANNED->value, 'planned_date' => '2026-10-09']);

        $trail = Trail::factory()->create();
        $trail->mountain->update(['timezone' => 'Asia/Jayapura']);
        $kedua = TripPlan::factory()->for($user)->for($trail)->create(['status' => TripStatus::PLANNED->value, 'planned_date' => '2026-10-09']);

        $this->actingAs($user)->get(route('track'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('hariH.id', $hariH->id)
                ->where('hariH.lewat', null)
                ->where('lain.0.id', $kedua->id)
                ->where('lain.0.lewat', 'Tanggal rencana sudah lewat'));
    }

    /**
     * M-5/ruling W6-4j, daftar periksa butir 3, R-001: nama jalur tidak unik, jadi baris trip
     * lain menampilkan gunungnya juga, tidak hanya nama jalur.
     */
    public function test_other_trips_show_their_mountain_because_trail_names_are_not_unique(): void
    {
        $this->travelTo(Carbon::parse('2026-10-09 04:00:00', 'UTC'));

        $user = User::factory()->create();
        $besok = Carbon::now('Asia/Jakarta')->addDay()->toDateString();
        TripPlan::factory()->for($user)->create(['status' => TripStatus::READY_FOR_DEPARTURE->value, 'planned_date' => $besok]);
        $rencana = TripPlan::factory()->for($user)->create(['status' => TripStatus::PLANNED->value, 'planned_date' => $besok]);

        $this->actingAs($user)->get(route('track'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('lain.0.gunung', $rencana->trail->mountain->name));
    }

    /**
     * M-1/ruling W6-4f, daftar periksa butir 1: batas 10 trip tidak memotong diam-diam. Tepat
     * 10 trip yang dapat dimulai tidak terpotong dan tidak menampilkan kalimat pemotongan;
     * trip ke-11 membuat halaman terpotong dan mengatakannya jujur dengan tautan ke tab Trip.
     *
     * N-3/ruling W6-4w: angka di kalimat itu dibaca lewat Reflection dari `BATAS_TRIP`
     * (konstanta privat), bukan ditulis mati "10" di test ini. Bila implementasi menulis angka
     * mati yang kebetulan sama (10) ini tidak membedakan; tetapi dikombinasikan dengan mutasi
     * `BATAS_TRIP` (P15/F1 di putaran sebelumnya), angka mati akan tetap "10" sedangkan nilai
     * dari Reflection ikut berubah mengikuti konstanta yang dimutasi, sehingga keduanya
     * berselisih dan test ini merah.
     */
    public function test_the_ten_trip_limit_is_told_truthfully_when_it_truncates(): void
    {
        $this->travelTo(Carbon::parse('2026-10-09 04:00:00', 'UTC'));

        $batas = (new ReflectionClass(PintuMendaki::class))->getConstant('BATAS_TRIP');

        $user = User::factory()->create();
        for ($i = 1; $i <= $batas; $i++) {
            TripPlan::factory()->for($user)->create(['status' => TripStatus::PLANNED->value, 'planned_date' => Carbon::now('Asia/Jakarta')->addDays($i)->toDateString()]);
        }

        $this->actingAs($user)->get(route('track'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('lain', $batas - 1)
                ->where('potong.terpotong', false));

        TripPlan::factory()->for($user)->create(['status' => TripStatus::PLANNED->value, 'planned_date' => Carbon::now('Asia/Jakarta')->addDays($batas + 1)->toDateString()]);

        $this->actingAs($user)->get(route('track'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('lain', $batas - 1)
                ->where('potong.terpotong', true)
                ->where('potong.kalimat', "Menampilkan {$batas} trip terdekat. Trip lainnya ada di tab Trip.")
                ->where('potong.url', route('trips.index')));
    }

    /**
     * S-4/ruling W6-4t (putaran perbaikan 2, mutasi F3): kalimat terpotong hanya dirender bila
     * `potong.terpotong` benar, bukan bila `potong.kalimat` ada (server selalu mengirim
     * kalimatnya, lihat `PintuMendaki::untuk()`). Asersi sumber karena syarat tampilnya murni di
     * klien.
     */
    public function test_the_truncation_sentence_is_gated_by_the_terpotong_flag_not_the_sentence(): void
    {
        $komponen = File::get(resource_path('js/pages/Track/Index.tsx'));

        $this->assertStringContainsString('{potong.terpotong ? (', $komponen);
    }

    /**
     * I-1/ruling W6-4b: lokasi baru diminta sesudah pendakian dimulai (mode lapangan, PRD
     * §145.1), bukan di tab ini; kalimat rekam jejak menyala ikut menyebut kapan titik posisi
     * dikirim, sejalan dengan kalimat "Rekam jejak" di bawahnya, supaya kedua kalimat yang
     * bertetangga di kartu yang sama tidak saling membantah.
     *
     * Diubah sisipan W6-P2 dengan alasan (ruling W6-P2i): kalimat menyala kini menyebut "baru
     * dikirim sesudah pendakian selesai atau dibatalkan", selaras dengan mode lapangan; pendakian
     * yang dibatalkan adalah jalur kiriman yang nyata.
     */
    public function test_location_and_track_recording_are_explained_before_starting(): void
    {
        $user = User::factory()->create();
        TripPlan::factory()->for($user)->create(['status' => TripStatus::PLANNED->value]);

        $this->actingAs($user)->get(route('track'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('lokasi', 'Lokasi baru diminta sesudah pendakian dimulai, dipakai di perangkat ini, dan tidak dikirim ke server.')
                ->where('jejak.menyala', false)
                ->where('jejak.kalimat', 'Rekam jejak mati. Nyalakan di Profil bila Anda ingin menyimpan jejak pendakian ini.')
                ->where('jejak.url', route('profile')));

        $user->preference()->create(['record_track' => true]);

        $this->actingAs($user->fresh())->get(route('track'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('lokasi', 'Lokasi baru diminta sesudah pendakian dimulai dan dipakai di perangkat ini. Rekam jejak menyala: titik posisi disimpan di perangkat dan baru dikirim sesudah pendakian selesai atau dibatalkan, saat ada sinyal.')
                ->where('jejak.menyala', true)
                ->where('jejak.kalimat', 'Rekam jejak menyala. Jejaknya dapat Anda hapus di ringkasan pendakian.'));
    }

    /**
     * Paket lapangan luring dibangun tetapi tidak dibuka sebelum pemilik memutus Q10 dan Q14
     * (ruling W6-R3): tanpa sakelar, tab tidak menawarkan unduhan dan mengatakannya.
     */
    public function test_the_field_pack_is_offered_only_when_switched_on(): void
    {
        $user = User::factory()->create();
        TripPlan::factory()->for($user)->create(['status' => TripStatus::PLANNED->value]);

        $this->actingAs($user)->get(route('track'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('paket.aktif', false)
                ->where('paket.kalimat', 'Paket lapangan luring belum dibuka. Buka mode lapangan selagi ada sinyal dan biarkan tetap terbuka, lalu bawa peta cetak atau perangkat lain sebagai cadangan.'));

        config(['hiking.field_pack.enabled' => true]);

        $this->actingAs($user)->get(route('track'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('paket.aktif', true)
                ->where('paket.kalimat', 'Paket luring membantu mengikuti jalur resmi. Bawa cadangan: peta cetak atau perangkat lain.'));
    }

    public function test_someone_elses_trips_never_appear(): void
    {
        TripPlan::factory()->create(['status' => TripStatus::IN_PROGRESS->value]);
        TripPlan::factory()->create(['status' => TripStatus::PLANNED->value]);

        $this->actingAs(User::factory()->create())
            ->get(route('track'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('hariH', null)->has('lain', 0));
    }

    public function test_the_tab_costs_the_same_with_one_or_ten_trips(): void
    {
        $sedikit = User::factory()->create();
        TripPlan::factory()->for($sedikit)->create(['status' => TripStatus::PLANNED->value]);

        $banyak = User::factory()->create();
        TripPlan::factory()->count(10)->for($banyak)->create(['status' => TripStatus::PLANNED->value]);

        $this->assertSame($this->hitungKueri($sedikit), $this->hitungKueri($banyak));
    }

    /**
     * P6/ruling W6-4p: anggaran hidrasi TripPlan tetap berapa pun jumlah trip, dibuktikan lewat
     * event `retrieved` Eloquent (perilaku, bukan teks SQL - daftar periksa butir 1). Dengan 15
     * trip yang dapat dimulai, `limit(BATAS_TRIP + 1)` di `PintuMendaki::untuk()` membatasi
     * jumlah baris yang dihidrasi sebagai TripPlan dari basis data menjadi paling banyak
     * BATAS_TRIP + 1, terlepas dari 15 baris yang sebenarnya memenuhi saringan status dan
     * tanggal.
     */
    public function test_the_trip_plan_hydration_budget_stays_bounded_with_fifteen_trips(): void
    {
        $this->travelTo(Carbon::parse('2026-10-09 04:00:00', 'UTC'));

        $batas = (new ReflectionClass(PintuMendaki::class))->getConstant('BATAS_TRIP');

        $user = User::factory()->create();
        for ($i = 1; $i <= 15; $i++) {
            TripPlan::factory()->for($user)->create(['status' => TripStatus::PLANNED->value, 'planned_date' => Carbon::now('Asia/Jakarta')->addDays($i)->toDateString()]);
        }

        $dihidrasi = 0;
        TripPlan::retrieved(function () use (&$dihidrasi): void {
            $dihidrasi++;
        });

        $this->actingAs($user)->get(route('track'))->assertOk();

        $this->assertLessThanOrEqual($batas + 1, $dihidrasi, 'Hidrasi TripPlan wajib dibatasi BATAS_TRIP + 1 (limit di PintuMendaki), bukan tumbuh dengan jumlah trip.');
    }

    public function test_guests_are_sent_to_sign_in(): void
    {
        $this->get(route('track'))->assertRedirect(route('login'));
    }

    /**
     * T1/ruling W6-4e dan I-2/ruling W6-4c, daftar periksa butir 1 dan 7 (fokus tidak pernah
     * jatuh ke body). Asersi sumber karena halaman ini dirender React di klien, bukan Blade:
     * PHPUnit tidak dapat menjalankan React, jadi perilaku dijaga lewat teks sumber (pola
     * HikeModeCheckpointTest, W6-3o).
     *
     * - "Mulai pendakian" hanya memanggil buka() (membuka konfirmasi), tidak pernah mulai()
     *   langsung: satu ketukan tidak boleh memulai pendakian yang tidak dapat dikembalikan.
     * - buka() memindah fokus ke tombol konfirmasi; batal() mengembalikannya ke "Mulai
     *   pendakian". Keduanya lewat useFokusSesudahRender (pola ToastUrungkan.tsx), bukan
     *   window.location atau manajemen fokus manual yang mudah lupa dipanggil pada satu cabang.
     * - Peringatan "tidak dapat dikembalikan" dikaitkan ke tombol konfirmasi lewat
     *   aria-describedby, dengan id unik per trip (idPeringatan), supaya pembaca layar
     *   membacakannya saat tombol itu difokus.
     *
     * Putaran perbaikan 2: (d) tombol "Mulai pendakian" sendiri berid idMulai, sasaran fokus
     * batal() (S-3/ruling W6-4s, mutasi C5: id itu dibuang maka sasaran fokus hilang). (e)
     * tombol Batal benar-benar memanggil batal() lewat onClick, bukan closure inline yang
     * melewatinya (S-3, mutasi C4: closure inline `() => setYakin(false)` tetap menutup
     * konfirmasi tetapi tidak memindah fokus). (f) sesudah galat jaringan, onFinish juga
     * memindah fokus kembali ke tombol konfirmasi yang baru aktif lagi (R-1/ruling W6-4q):
     * tombol itu `disabled` selama `memproses`, dan peramban tidak mengembalikan fokus sendiri
     * begitu atributnya lepas, jadi tanpa ini fokus jatuh ke body sesudah galat jaringan.
     */
    public function test_starting_always_goes_through_inline_confirmation_and_keeps_focus_off_body(): void
    {
        $komponen = File::get(resource_path('js/pages/Track/Index.tsx'));

        // (a) satu ketukan "Mulai pendakian" hanya membuka konfirmasi.
        $this->assertStringContainsString('onClick={buka}', $komponen);
        preg_match('/<Button id=\{idKonfirmasi\}[^>]*>/', $komponen, $tombolKonfirmasi);
        $this->assertNotEmpty($tombolKonfirmasi, 'Tombol konfirmasi berid idKonfirmasi tidak ditemukan.');
        $this->assertStringContainsString('onClick={mulai}', $tombolKonfirmasi[0], 'Hanya tombol konfirmasi yang boleh memanggil mulai() langsung.');

        // (b) buka() memindah fokus ke konfirmasi.
        preg_match('/const buka = \(\) => \{.*?\n    \};/s', $komponen, $fnBuka);
        $this->assertNotEmpty($fnBuka, 'const buka tidak ditemukan.');
        $this->assertStringContainsString('fokusKe(idKonfirmasi)', $fnBuka[0]);

        // (b) batal() mengembalikan fokus ke "Mulai pendakian".
        preg_match('/const batal = \(\) => \{.*?\n    \};/s', $komponen, $fnBatal);
        $this->assertNotEmpty($fnBatal, 'const batal tidak ditemukan.');
        $this->assertStringContainsString('fokusKe(idMulai)', $fnBatal[0]);

        // (c) peringatan dikaitkan lewat aria-describedby, id unik per trip.
        $this->assertStringContainsString('aria-describedby={idPeringatan}', $komponen);
        $this->assertStringContainsString('id={idPeringatan}', $komponen);
        $this->assertStringContainsString('const idMulai = `mulai-${id}`;', $komponen);
        $this->assertStringContainsString('const idKonfirmasi = `konfirmasi-${id}`;', $komponen);
        $this->assertStringContainsString('const idPeringatan = `peringatan-${id}`;', $komponen);

        // (d) tombol "Mulai pendakian" berid idMulai, sasaran fokus batal().
        preg_match('/<Button id=\{idMulai\}[^>]*>/', $komponen, $tombolMulai);
        $this->assertNotEmpty($tombolMulai, 'Tombol "Mulai pendakian" berid idMulai tidak ditemukan.');
        $this->assertStringContainsString('onClick={buka}', $tombolMulai[0]);

        // (e) tombol Batal benar-benar memanggil batal(), bukan closure inline yang melewatinya.
        preg_match('/<Button variant="secondary" onClick=\{batal\}[^>]*>/', $komponen, $tombolBatal);
        $this->assertNotEmpty($tombolBatal, 'Tombol Batal wajib memanggil batal() langsung lewat onClick={batal}.');

        // (f) onFinish mengembalikan fokus ke konfirmasi sesudah tombolnya aktif lagi (R-1).
        preg_match('/onFinish: \(\) => \{(.*?)\},/s', $komponen, $fnFinish);
        $this->assertNotEmpty($fnFinish, 'onFinish tidak ditemukan.');
        $this->assertStringContainsString('fokusKe(idKonfirmasi)', $fnFinish[1], 'onFinish wajib mengembalikan fokus ke konfirmasi sesudah galat jaringan, jika tidak fokus jatuh ke body (R-1).');
    }

    /**
     * T2/ruling W6-4e: tombol utama "Mulai pendakian" hari-H setinggi 66 px, label benar-benar
     * berukuran text-bagian (M-8a/ruling W6-4m): label dibungkus span sendiri supaya ukurannya
     * tidak kalah dari kelas dasar Button (kelas() hanya menggabung string, tidak menimpa kelas
     * Tailwind yang sudah ada di Button, jadi text-bagian di className luar tidak bergaransi
     * menang atas text-isi bawaan Button; span di dalam children selalu menang karena ia
     * menata ulang elemen itu sendiri, bukan mewarisi keputusan cascade).
     */
    public function test_the_large_start_button_is_sixty_six_pixels_tall(): void
    {
        $komponen = File::get(resource_path('js/pages/Track/Index.tsx'));

        $this->assertStringContainsString("const ukuran = besar ? 'mt-5 min-h-[66px] w-full py-4' : 'mt-3';", $komponen);
        $this->assertStringContainsString("className={besar ? 'min-h-[66px] flex-1 py-4' : undefined}", $komponen);
        $this->assertStringContainsString('{besar ? <span className="text-bagian">Mulai pendakian</span> : \'Mulai pendakian\'}', $komponen);
        $this->assertStringContainsString('{besar ? <span className="text-bagian">Ya, mulai sekarang</span> : \'Ya, mulai sekarang\'}', $komponen);
        // Ruling W6-FF (M-3 tinjauan akhir W6, mutasi D6): konstanta 66 px benar-benar dipakai
        // tombol "Mulai pendakian", bukan hanya didefinisikan.
        $this->assertStringContainsString("<Button id={idMulai} onClick={buka} className={ukuran} variant={besar ? 'primary' : 'secondary'}>", $komponen);
    }

    /**
     * Ruling W6-FE (M-2 tinjauan akhir W6): galat HTTP tanpa header Inertia saat mulai, misalnya
     * 419 sesudah sesi berakhir, ditulis "belum pasti" di wilayah status dekat tombol lewat
     * onHttpException yang mengembalikan false, bukan dialog galat bawaan Inertia. Berlaku untuk
     * kedua tombol mulai: tab Mendaki dan ruang kerja trip. Ruang kerja mengembalikan fokus ke
     * tombolnya sesudah tombol itu aktif lagi.
     */
    public function test_an_unreadable_server_answer_to_start_is_written_near_the_button(): void
    {
        $kalimat = preg_quote('Belum pasti dimulai: jawaban server tidak dapat dibaca. Muat ulang halaman, lalu periksa apakah pendakian sudah berjalan sebelum mencoba lagi.', '/');
        $tab = File::get(resource_path('js/pages/Track/Index.tsx'));

        $this->assertMatchesRegularExpression("/onHttpException: \\(\\) => \\{\\s*setPesan\\('{$kalimat}'\\);\\s*return false;\\s*\\},/", $tab);

        $ruangKerja = File::get(resource_path('js/pages/Trips/Show.tsx'));

        $this->assertMatchesRegularExpression("/onHttpException: \\(\\) => \\{\\s*gagalHttp = true;\\s*setPesanMulai\\('{$kalimat}'\\);\\s*return false;\\s*\\},/", $ruangKerja);
        $this->assertMatchesRegularExpression("/onFinish: \\(\\) => \\{\\s*setMemulai\\(false\\);\\s*if \\(gagalHttp\\) \\{\\s*fokusKe\\('mulai-ruang-kerja'\\);\\s*\\}\\s*\\},/", $ruangKerja);
        $this->assertStringContainsString('<Button id="mulai-ruang-kerja" onClick={mulai}', $ruangKerja);
        $this->assertStringContainsString("<p role=\"status\" className={kelas('text-kecil font-bold text-warn-900', pesanMulai !== '' && 'mt-3')}>", $ruangKerja, 'Wilayah status mulai selalu terpasang di kartu Tindakan.');
    }

    /**
     * T3/ruling W6-4e dan M-6/ruling W6-4k: cabang galat jaringan sesudah "Ya, mulai sekarang"
     * tidak lagi menyatakan "Belum dimulai" begitu saja, karena server mungkin sudah memproses
     * permintaannya sebelum jawabannya hilang di jaringan (TripLifecycle::start() commit lebih
     * dulu). Kalimat barunya mengajak membuka tab ini lagi, yang langsung mengalihkan ke mode
     * lapangan bila pendakian memang sudah berjalan (TrackController.php).
     */
    public function test_the_network_error_sentence_no_longer_claims_the_hike_did_not_start(): void
    {
        $komponen = File::get(resource_path('js/pages/Track/Index.tsx'));

        preg_match('/onNetworkError: \(\) => \{(.*?)\},/s', $komponen, $gagal);
        $this->assertNotEmpty($gagal, 'onNetworkError tidak ditemukan.');
        $this->assertStringContainsString(
            'Sambungan terputus sebelum jawaban server diterima, jadi belum pasti pendakian sudah dimulai. Buka lagi tab Mendaki saat ada sinyal; bila sudah dimulai, mode lapangan langsung terbuka.',
            $gagal[1],
        );
        $this->assertStringNotContainsString('Belum dimulai', $komponen);
    }

    /**
     * T4/ruling W6-4e, konvensi "wilayah status selalu terpasang" (aturan-eksekusi, pola
     * ReadinessDashboardTest): wilayah role="status" langsung menyusul penutup ternary
     * konfirmasi tanpa syarat tambahan, dan menjadi anak langsung div pembungkus; hanya isinya
     * ({pesan}) yang bersyarat, bukan keberadaan elemennya.
     */
    public function test_the_network_status_region_is_always_mounted(): void
    {
        $komponen = File::get(resource_path('js/pages/Track/Index.tsx'));

        preg_match('/\)\}\s*<p role="status" className="mt-2 text-kecil font-bold text-primary">\s*\{pesan\}\s*<\/p>\s*<\/div>/', $komponen, $blokAkhir);
        $this->assertNotEmpty($blokAkhir, 'Wilayah status role=status wajib langsung menyusul blok konfirmasi tanpa syarat baru, dan jadi anak langsung div pembungkus.');
    }

    /**
     * M-4/ruling W6-4i, daftar periksa butir 7: nama aksesibel keempat tombol "Mulai pendakian"
     * (hari-H dan tiap baris trip lain) serta tombol konfirmasi dan Batal menyebut nama trip
     * lewat sr-only berpemisah tekstual (", {nama}"), supaya beberapa konfirmasi yang terbuka
     * bersamaan tetap dapat dibedakan pembaca layar. Label yang terlihat tidak berubah, dan
     * tanpa sr-only ganda: hanya satu span sr-only per tombol, tidak dipasangkan dengan
     * aria-label yang mengulang info yang sama.
     *
     * N-1/ruling W6-4u (putaran perbaikan 2): nama trip bawaan tidak unik ("Pendakian {jalur}"),
     * jadi dua trip bernama sama di gunung berbeda masih perlu dibedakan; sr-only ikut menyebut
     * gunung, bukan hanya nama trip.
     */
    public function test_button_accessible_names_include_the_trip_name(): void
    {
        $komponen = File::get(resource_path('js/pages/Track/Index.tsx'));

        $this->assertSame(
            3,
            substr_count($komponen, '<span className="sr-only">, {nama}, {gunung}</span>'),
            'Ketiga tombol (mulai, konfirmasi, batal) wajib menyebut nama dan gunung trip lewat sr-only.',
        );
        $this->assertStringNotContainsString('aria-label={`Mulai pendakian', $komponen, 'Tanpa sr-only ganda: konteks trip hanya lewat span sr-only, bukan juga aria-label.');
    }

    /**
     * M-3/ruling W6-4h: nama trip panjang tanpa spasi (sampai 120 karakter, batas validasi
     * ValidatesTripPlanDetails) tidak boleh membuat halaman menggulir menyamping di 320 px.
     * break-words saja tidak cukup di dalam flex (inline-flex Tautan membuat teks children
     * menjadi butir flex anonim yang default min-width-nya min-content, bukan 0), jadi butir
     * teksnya dibungkus span sendiri dengan min-w-0 dan [overflow-wrap:anywhere] (overflow-wrap
     * break-word tidak ikut dihitung saat intrinsic sizing, anywhere dihitung). Asersi sumber;
     * bukti peramban diuji peninjau ulang.
     *
     * Judul hari-H sekaligus menjaga M-8b/ruling W6-4m: `hariH.urls.ruangKerja` dipakai sebagai
     * tautan judulnya, label yang sama dengan baris trip lain (nama trip menuju ruang kerja).
     *
     * N-4/ruling W6-4x (putaran perbaikan 2): `break-words` dibuang dari kedua tempat karena
     * tidak berefek (selalu kalah dari `[overflow-wrap:anywhere]` pada CSS yang sama, dibuktikan
     * peninjau lewat urutan byte CSS build); test ini sekarang menolak kemunculannya kembali.
     */
    public function test_long_unbroken_trip_names_do_not_cause_horizontal_scroll_sources(): void
    {
        $komponen = File::get(resource_path('js/pages/Track/Index.tsx'));

        $this->assertStringContainsString('<span className="min-w-0 [overflow-wrap:anywhere]">{trip.nama}</span>', $komponen);
        $this->assertStringContainsString('<Tautan href={hariH.urls.ruangKerja}', $komponen);
        preg_match_all('/\[overflow-wrap:anywhere\]/', $komponen, $kemunculan);
        $this->assertGreaterThanOrEqual(2, count($kemunculan[0]), 'Judul hari-H dan baris trip lain wajib keduanya memakai overflow-wrap:anywhere.');
        $this->assertStringNotContainsString('break-words', $komponen, 'Kelas break-words dibuang karena tidak berefek (N-4).');
    }

    private function hitungKueri(User $user): int
    {
        $this->actingAs($user)->get(route('track'))->assertOk();

        $kueri = 0;
        DB::listen(function () use (&$kueri) {
            $kueri++;
        });

        $this->actingAs($user)->get(route('track'))->assertOk();
        DB::getEventDispatcher()->forget('Illuminate\Database\Events\QueryExecuted');

        return $kueri;
    }
}
