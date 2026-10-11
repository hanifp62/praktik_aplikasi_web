<?php

namespace Tests\Feature;

use App\Enums\CompletionState;
use App\Enums\ExperienceLevel;
use App\Enums\HikingSessionStatus;
use App\Enums\PreparationCategory;
use App\Enums\PreparationStatus;
use App\Enums\StatusScope;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Models\Checkpoint;
use App\Models\HikingHistory;
use App\Models\HikingSession;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\PermitRequirement;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Models\User;
use App\Services\PreparationService;
use Database\Seeders\PreparationTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Beranda berfase B0 sampai B4 (spec v3 bagian 6.1, DK-45, rencana induk W5 butir 1).
 *
 * Fase dihitung server dari profil, trip, dan riwayat, dan setiap fase membawa tepat satu aksi
 * utama yang dipilih server. Waktu dibekukan pada Jumat 25 September 2026 pukul 10.00 WIB.
 */
class DashboardPhaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-09-25 03:00:00', 'UTC'));
    }

    private function pendaki(bool $profil = true): User
    {
        $user = User::factory()->create();

        if ($profil) {
            $user->profile()->create(['experience_level' => ExperienceLevel::INTERMEDIATE->value, 'completed_at' => now()]);
        }

        return $user->fresh();
    }

    /**
     * @param  array<string, mixed>  $atribut
     */
    private function trip(User $user, array $atribut = [], string $zona = 'Asia/Jakarta'): TripPlan
    {
        $gunung = Mountain::factory()->create(['name' => 'Gunung Gede', 'timezone' => $zona]);
        $jalur = Trail::factory()->for($gunung)->published()->create(['name' => 'Gede via Cibodas']);

        return TripPlan::factory()->for($user)->for($jalur)->create(array_merge([
            'name' => 'Gede bareng Dimas',
            'planned_date' => '2026-10-10',
            'end_date' => '2026-10-11',
            'start_time' => '07:00',
            'participants_count' => 3,
            'trip_type' => TripType::CAMPING->value,
            'status' => TripStatus::PLANNED->value,
        ], $atribut));
    }

    private function butir(TripPlan $trip, string $label, PreparationStatus $status, bool $kritis = true): TripPreparationItem
    {
        return TripPreparationItem::create([
            'trip_plan_id' => $trip->id,
            'category' => PreparationCategory::EQUIPMENT->value,
            'label' => $label,
            'is_critical' => $kritis,
            'status' => $status->value,
        ]);
    }

    private function izin(TripPlan $trip, array $atribut = []): PermitRequirement
    {
        return PermitRequirement::factory()->create(array_merge([
            'mountain_id' => $trip->trail->mountain_id,
            'authority' => 'Balai Besar TNGGP',
            'booking_url' => 'https://contoh.test/pemesanan',
            'booking_opens_days_before' => 30,
            'booking_closes_days_before' => 3,
        ], $atribut));
    }

    private function selesai(User $user, TripPlan $trip, Carbon $kapan, CompletionState $keadaan = CompletionState::COMPLETED): HikingHistory
    {
        $trip->update(['status' => TripStatus::COMPLETED->value, 'completed_at' => $kapan]);

        return HikingHistory::create([
            'user_id' => $user->id,
            'trip_plan_id' => $trip->id,
            'trail_id' => $trip->trail_id,
            'trip_type' => $trip->trip_type->value,
            'completion_state' => $keadaan->value,
            'completed_at' => $kapan,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function fase(User $user): array
    {
        return $this->actingAs($user)->get(route('dashboard'))->assertOk()->viewData('page')['props']['fase'];
    }

    /**
     * Kartu perjalanan berikutnya (prototype 01): jenis, H-n, tanggal dengan kata "sampai",
     * jam dalam zona gunungnya, peserta, empat langkah, dan hitungan butir kritis dengan nama
     * yang belum dikonfirmasi. Ringkasan di bawah sapaan menyebut sisa hari dan butirnya.
     */
    public function test_the_next_trip_fills_b2_with_its_plan_steps_and_critical_count(): void
    {
        $user = $this->pendaki();
        $trip = $this->trip($user);
        $this->butir($trip, 'P3K', PreparationStatus::CONFIRMED);
        $this->butir($trip, 'Senter', PreparationStatus::CONFIRMED);
        $this->butir($trip, 'Jaket hujan', PreparationStatus::NOT_CONFIRMED);
        $this->butir($trip, 'Tenda', PreparationStatus::NOT_CONFIRMED);
        $this->butir($trip, 'Peta jalur', PreparationStatus::NOT_CONFIRMED);
        $this->butir($trip, 'Kopi', PreparationStatus::NOT_CONFIRMED, kritis: false);

        $fase = $this->fase($user);
        $kartu = $fase['kartu'];

        $this->assertSame('B2', $fase['kunci']);
        $this->assertSame('Gede bareng Dimas tinggal 15 hari lagi. 3 butir kritis belum dikonfirmasi.', $fase['ringkasan']);
        $this->assertSame('perjalanan', $kartu['jenis']);
        $this->assertSame(['Camping', 'H-15', 'Sab, 10 Okt sampai Min, 11 Okt 2026', '07.00 WIB', '3 orang'], [$kartu['jenisTrip'], $kartu['hari'], $kartu['tanggal'], $kartu['jam'], $kartu['peserta']]);
        $this->assertSame(['jalur', 'rencana', 'persiapan', 'kesiapan'], array_column($kartu['langkah'], 'kunci'));
        $this->assertSame(['jumlah' => 5, 'dikonfirmasi' => 2, 'berlaku' => 5, 'belum' => ['Jaket hujan', 'Tenda', 'Peta jalur'], 'sisa' => 0, 'menungguBmkg' => null], $kartu['persiapan']);
        $this->assertSame(route('trips.show', $trip), $kartu['url']);
    }

    /**
     * Ruling W5-FI (M7 tinjauan akhir W5): kartu trip menyebut paling banyak tiga nama butir kritis
     * yang belum dikonfirmasi (RingkasanDasbor::BUTIR_DISEBUT, ruling W5-R11), dalam urutan daftar
     * persiapannya, dan sisanya sebagai hitungan.
     */
    public function test_the_trip_card_names_three_unconfirmed_critical_items_and_counts_the_rest(): void
    {
        $user = $this->pendaki();
        $trip = $this->trip($user);

        foreach (['Tenda', 'Jaket hujan', 'Peta jalur', 'Senter', 'P3K'] as $label) {
            $this->butir($trip, $label, PreparationStatus::NOT_CONFIRMED);
        }

        $persiapan = $this->fase($user)['kartu']['persiapan'];

        $this->assertSame(['Tenda', 'Jaket hujan', 'Peta jalur'], $persiapan['belum']);
        $this->assertSame(2, $persiapan['sisa']);
        $this->assertSame(5, $persiapan['berlaku']);
    }

    public function test_a_hike_in_progress_is_b3_and_names_the_next_checkpoint(): void
    {
        $user = $this->pendaki();
        $trip = $this->trip($user, ['status' => TripStatus::IN_PROGRESS->value, 'planned_date' => '2026-09-25']);
        $pos = Checkpoint::factory()->for($trip->trail)->create(['sequence' => 2, 'name' => 'Kandang Badak']);
        HikingSession::create([
            'trip_plan_id' => $trip->id,
            'user_id' => $user->id,
            'current_checkpoint_id' => $pos->id,
            'status' => HikingSessionStatus::ACTIVE->value,
            'started_at' => now()->subHours(2),
        ]);

        $fase = $this->fase($user);

        $this->assertSame('B3', $fase['kunci']);
        $this->assertSame('Anda sedang mendaki Gede via Cibodas.', $fase['ringkasan']);
        $this->assertSame('Kandang Badak', $fase['kartu']['posBerikutnya']);
        $this->assertSame(['jenis' => 'tautan', 'label' => 'Buka mode lapangan', 'href' => '/trips/'.$trip->id.'/hike', 'keterangan' => null], $fase['kartu']['aksi']);
    }

    /**
     * Pendakian yang selesai pekan ini dan belum dilaporkan mengajak pendakinya melapor (B4).
     * Tautannya membawa jalur dan trip, sehingga laporannya ditautkan ke riwayat ini.
     */
    public function test_a_hike_finished_this_week_and_not_reported_is_b4(): void
    {
        $user = $this->pendaki();
        $trip = $this->trip($user, ['planned_date' => '2026-09-22']);
        $this->selesai($user, $trip, now()->subDays(2));

        $fase = $this->fase($user);

        $this->assertSame('B4', $fase['kunci']);
        $this->assertSame('Pendakian Gede bareng Dimas sudah selesai.', $fase['ringkasan']);
        $this->assertSame('Selesai sampai target', $fase['kartu']['hasil']);
        $this->assertSame('Rab, 23 Sep 2026', $fase['kartu']['selesai']);
        $this->assertSame(route('history.summary', $trip), $fase['kartu']['ringkasanUrl']);
        $this->assertSame('Bagikan laporan kondisi', $fase['kartu']['aksi']['label']);
        $this->assertSame(route('reports.create', ['trail' => $trip->trail_id, 'trip' => $trip->id]), $fase['kartu']['aksi']['href']);
    }

    /**
     * Ruling FF-1 (TC-m1 tinjauan akhir): tanggal selesai kartu B4 dibaca di zona gunung jalurnya,
     * sama dengan ringkasan pendakian dan Riwayat. Pendakian yang selesai 18.30 UTC sudah tanggal
     * berikutnya di WIB; tanggal UTC server tidak boleh tampil.
     */
    public function test_the_b4_finish_date_is_read_in_wib_for_a_wib_mountain(): void
    {
        $user = $this->pendaki();
        $this->selesai($user, $this->trip($user, ['planned_date' => '2026-09-22']), Carbon::parse('2026-09-22 18:30:00', 'UTC'));

        $this->assertSame('Rab, 23 Sep 2026', $this->fase($user)['kartu']['selesai']);
    }

    /**
     * Gunung WITA: 16.30 UTC adalah 00.30 WITA tanggal 23, sedangkan di WIB masih tanggal 22. Jadi
     * yang dipakai zona gunung itu, bukan zona bawaan (TC-m1).
     */
    public function test_the_b4_finish_date_is_read_in_the_mountains_own_timezone(): void
    {
        $user = $this->pendaki();
        $this->selesai($user, $this->trip($user, ['planned_date' => '2026-09-22'], 'Asia/Makassar'), Carbon::parse('2026-09-22 16:30:00', 'UTC'));

        $this->assertSame('Rab, 23 Sep 2026', $this->fase($user)['kartu']['selesai']);
    }

    /**
     * Ruling W5-FK (M10 tinjauan akhir W5): ringkasan B4 searah dengan hasil di kartu di bawahnya.
     * "Sudah selesai" hanya untuk pendakian yang selesai sampai target; yang selesai sebagian atau
     * dibatalkan di jalur "sudah berakhir".
     */
    public function test_the_b4_summary_says_ended_for_a_partial_or_abandoned_hike(): void
    {
        foreach ([CompletionState::PARTIAL, CompletionState::ABANDONED] as $keadaan) {
            $user = $this->pendaki();
            $trip = $this->trip($user, ['planned_date' => '2026-09-22']);
            $this->selesai($user, $trip, now()->subDays(2), $keadaan);

            $fase = $this->fase($user);

            $this->assertSame('B4', $fase['kunci']);
            $this->assertSame('Pendakian Gede bareng Dimas sudah berakhir.', $fase['ringkasan']);
            $this->assertSame($keadaan->label(), $fase['kartu']['hasil']);
        }
    }

    /**
     * Ruling W5-FC (I3 tinjauan akhir W5, P8): pintu B4 membuka formulir laporan dengan tanggal
     * pendakian bawaan = tanggal selesai riwayat pendaki ini, dalam zona gunungnya. Selesai Selasa
     * 22 September 18.00 UTC adalah Rabu 23 September 01.00 WIB; tanggal UTC maupun tanggal hari
     * ini (25 September) akan membuat laporannya tampak lebih segar daripada pendakiannya.
     */
    public function test_the_b4_report_link_opens_the_form_on_the_day_the_hike_ended(): void
    {
        $user = $this->pendaki();
        $trip = $this->trip($user, ['planned_date' => '2026-09-22']);
        $this->selesai($user, $trip, Carbon::parse('2026-09-22 18:00:00', 'UTC'));

        $tautan = $this->fase($user)['kartu']['aksi']['href'];

        $this->actingAs($user)->get($tautan)->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Community/ConditionReport')
            ->where('awal.trail_id', $trip->trail_id)
            ->where('awal.trip_plan_id', $trip->id)
            ->where('awal.hike_date', '2026-09-23'));
    }

    /**
     * Laporan yang sudah dikirim untuk jalur itu sesudah pendakian selesai menutup B4, juga
     * laporan yang tidak ditautkan ke riwayatnya; B4 berakhir sesudah tujuh hari.
     */
    public function test_a_report_already_sent_or_an_older_hike_does_not_ask_again(): void
    {
        $user = $this->pendaki();
        $trip = $this->trip($user, ['planned_date' => '2026-09-22']);
        $this->selesai($user, $trip, now()->subDays(2));
        TrailConditionReport::factory()->for($trip->trail)->create(['user_id' => $user->id, 'created_at' => now()->subDay()]);

        $this->assertSame('B1', $this->fase($user)['kunci']);

        $lama = $this->pendaki();
        $tripLama = $this->trip($lama, ['planned_date' => '2026-09-15']);
        $this->selesai($lama, $tripLama, now()->subDays(8));

        $this->assertSame('B1', $this->fase($lama)['kunci']);
    }

    /**
     * Urutan kepentingan: pendakian berlangsung, trip mendatang, laporan yang ditunggu, lalu
     * profil. Pendaki tanpa profil yang baru turun gunung tetap diajak melapor lebih dulu.
     */
    public function test_the_phases_follow_their_order_of_importance(): void
    {
        $user = $this->pendaki(profil: false);
        $selesai = $this->trip($user, ['planned_date' => '2026-09-22']);
        $this->selesai($user, $selesai, now()->subDay());

        $this->assertSame('B4', $this->fase($user)['kunci']);

        $this->trip($user);

        $this->assertSame('B2', $this->fase($user)['kunci']);

        $this->trip($user, ['status' => TripStatus::IN_PROGRESS->value, 'planned_date' => '2026-09-25']);

        $this->assertSame('B3', $this->fase($user)['kunci']);
    }

    public function test_on_the_day_the_one_action_is_to_start_from_the_hike_tab(): void
    {
        $user = $this->pendaki();
        $this->trip($user, ['planned_date' => '2026-09-25', 'end_date' => null]);

        $fase = $this->fase($user);

        $this->assertSame('Gede bareng Dimas berangkat hari ini.', $fase['ringkasan']);
        $this->assertSame('Hari H', $fase['kartu']['hari']);
        $this->assertSame(['Mulai pendakian', route('track')], [$fase['kartu']['aksi']['label'], $fase['kartu']['aksi']['href']]);
    }

    public function test_from_three_days_before_the_one_action_is_the_readiness_check(): void
    {
        $user = $this->pendaki();
        $trip = $this->trip($user, ['planned_date' => '2026-09-27', 'end_date' => null]);
        $this->butir($trip, 'Tenda', PreparationStatus::NOT_CONFIRMED);

        $aksi = $this->fase($user)['kartu']['aksi'];

        $this->assertSame(['Cek kesiapan', route('trips.readiness', $trip)], [$aksi['label'], $aksi['href']]);
    }

    /**
     * Ruling W5-2b (I2a): urutan fase waktu mengalahkan urutan izin (W5-R6). Trip H-3 dengan
     * izin wajib yang jendelanya terbuka dan belum diurus tetap menghasilkan "Cek kesiapan",
     * bukan "Pesan di sistem resmi": jendela prakiraan BMKG selalu didahulukan.
     */
    public function test_the_readiness_window_outranks_an_open_permit_window(): void
    {
        $user = $this->pendaki();
        $trip = $this->trip($user, ['planned_date' => '2026-09-28', 'end_date' => null]);
        $this->izin($trip);

        $aksi = $this->fase($user)['kartu']['aksi'];

        $this->assertSame(['Cek kesiapan', route('trips.readiness', $trip)], [$aksi['label'], $aksi['href']]);
    }

    /**
     * Izin wajib yang belum dikonfirmasi dengan pemesanan terbuka mengarah ke sistem resmi
     * (tautan keluar); izin yang sudah dikonfirmasi tidak lagi menjadi aksi utama.
     */
    public function test_an_open_window_sends_the_hiker_to_book_on_the_official_system(): void
    {
        $user = $this->pendaki();
        $trip = $this->trip($user);
        $this->izin($trip);
        $this->butir($trip, 'Tenda', PreparationStatus::CONFIRMED);

        $aksi = $this->fase($user)['kartu']['aksi'];

        $this->assertSame(['luar', 'Pesan di sistem resmi', 'https://contoh.test/pemesanan'], [$aksi['jenis'], $aksi['label'], $aksi['href']]);
        $this->assertSame('Sistem resmi Balai Besar TNGGP. Memount menautkan sistem resmi dan tidak menjual izin.', $aksi['keterangan']);

        $this->butir($trip, PreparationService::PERMIT_LABEL, PreparationStatus::CONFIRMED);

        $this->assertSame('Buka ruang kerja', $this->fase($user)['kartu']['aksi']['label']);
    }

    /**
     * Ruling W5-FD (M1 tinjauan akhir W5): "Pesan di sistem resmi" membuka sistem resmi di tab
     * baru, seperti tautan resmi lain di halaman yang sama (TenggatTerdekat, ruling W5-3a). Cabang
     * external Button, yang hanya dipakai AksiUtama, menulis target="_blank" dengan
     * rel="noopener noreferrer" (tab baru tidak dapat mengendalikan tab Memount lewat
     * window.opener) dan kabar sr-only bahwa tab baru dibuka.
     */
    public function test_the_official_booking_action_opens_in_a_new_tab_safely(): void
    {
        $tombol = File::get(resource_path('js/components/ui/Button.tsx'));
        $aksi = File::get(resource_path('js/components/beranda/AksiUtama.tsx'));

        $this->assertMatchesRegularExpression(
            '/return external \? \(\s*<a href=\{href\} target="_blank" rel="noopener noreferrer" className=\{semua\} \{\.\.\.nama\}>\s*\{children\}\s*<span className="sr-only"> \(membuka tab baru\)<\/span>\s*<\/a>/',
            $tombol,
        );
        $this->assertStringContainsString("external={aksi.jenis === 'luar'}", $aksi);
        // Ruling W5-FR (N5): satu petunjuk sr-only saja, dari Button, bukan dua bertumpuk.
        $this->assertStringNotContainsString('membuka situs resmi', $aksi);
    }

    /**
     * Pemesanan yang belum dibuka menawarkan pengingat selama pengingat trip belum menyala;
     * sesudah menyala, hari bukanya sudah terjadwal dan aksinya kembali ke persiapan.
     */
    public function test_a_window_not_yet_open_offers_the_reminder_until_reminders_are_on(): void
    {
        $user = $this->pendaki();
        $trip = $this->trip($user, ['planned_date' => '2026-11-20', 'end_date' => null]);
        $this->izin($trip);
        $this->butir($trip, 'Tenda', PreparationStatus::NOT_CONFIRMED);

        $aksi = $this->fase($user)['kartu']['aksi'];

        $this->assertSame(['pengingat', 'Ingatkan saat dibuka', route('trips.reminders.update', $trip)], [$aksi['jenis'], $aksi['label'], $aksi['href']]);

        $this->actingAs($user)->put($aksi['href'], ['aktif' => true])->assertRedirect(route('trips.show', $trip));

        $this->assertSame('Lanjutkan persiapan', $this->fase($user)['kartu']['aksi']['label']);
        $this->assertSame(1, $trip->reminders()->where('jenis', 'IZIN')->count());
    }

    /**
     * W5-P8: tanggal tutup pemesanan izin tercatat tetapi tanggal buka tidak
     * (PermitService::BOOKING_BUKA_BELUM_TERCATAT). Belum diketahui apakah pemesanannya sudah
     * dibuka, jadi aksi utama bukan "Pesan di sistem resmi" dan bukan "Ingatkan saat dibuka"
     * (R-007); ia jatuh ke aturan berikutnya seperti jalur tanpa aturan izin.
     */
    public function test_an_unrecorded_opening_date_falls_back_to_preparation_or_workspace(): void
    {
        $user = $this->pendaki();
        $trip = $this->trip($user);
        $this->izin($trip, ['booking_opens_days_before' => null, 'booking_closes_days_before' => 3]);
        $butir = $this->butir($trip, 'Tenda', PreparationStatus::NOT_CONFIRMED);

        $aksi = $this->fase($user)['kartu']['aksi'];

        $this->assertSame(['Lanjutkan persiapan', route('trips.preparation', $trip)], [$aksi['label'], $aksi['href']]);

        $butir->update(['status' => PreparationStatus::CONFIRMED]);

        $this->assertSame(['Buka ruang kerja', route('trips.show', $trip)], [$this->fase($user)['kartu']['aksi']['label'], $this->fase($user)['kartu']['aksi']['href']]);
    }

    /**
     * Ruling W5-2a (I1): bila satu-satunya butir kritis yang tersisa menunggu BMKG
     * (PreparationService::menungguBmkg(), templat bawaan: "Memeriksa prakiraan cuaca
     * terbaru"), aksi bukan "Lanjutkan persiapan": server menolak konfirmasi butir itu
     * sebelum prakiraan terbit (PreparationService::penolakanKonfirmasi()). Trip H-10, di
     * luar jendela H-3 sampai H-1, jatuh ke "Buka ruang kerja". Bilah langkah (TripSteps)
     * sudah menunjuk Cek sebagai berikutnya dalam kasus ini, bukan Persiapan, dan aksi tidak
     * boleh bertentangan dengan itu.
     */
    public function test_a_critical_weather_item_waiting_for_bmkg_does_not_block_on_preparation(): void
    {
        $this->seed(PreparationTemplateSeeder::class);

        $user = $this->pendaki();
        $trip = $this->trip($user, ['planned_date' => '2026-10-05', 'end_date' => null]);
        // Seluruh butir BUKAN cuaca dikonfirmasi, kritis maupun tidak: bilah langkah
        // (TripSteps) membaca semua butir, bukan hanya yang kritis, jadi satu butir cuaca
        // nonkritis yang tersisa tetap membuat Persiapan terbaca "ada pekerjaan", bukan
        // "menunggu BMKG" (TripSteps::semuaMenungguBmkg).
        $butir = app(PreparationService::class)->generateFor($trip);
        $butir->where('category', '!=', PreparationCategory::WEATHER)
            ->each(fn ($satu) => $satu->update(['status' => PreparationStatus::CONFIRMED->value]));

        $fase = $this->fase($user);
        $kartu = $fase['kartu'];
        $langkah = collect($kartu['langkah'])->keyBy('kunci');

        $this->assertStringContainsString('menunggu BMKG', $fase['ringkasan']);
        $this->assertStringContainsString('menunggu BMKG', $kartu['persiapan']['menungguBmkg']);
        $this->assertSame('Buka ruang kerja', $kartu['aksi']['label']);
        $this->assertSame(route('trips.show', $trip), $kartu['aksi']['href']);
        $this->assertSame(['belum', 'berikutnya'], [$langkah['persiapan']['keadaan'], $langkah['kesiapan']['keadaan']]);
    }

    public function test_without_a_permit_rule_the_action_follows_critical_preparation(): void
    {
        $user = $this->pendaki();
        $trip = $this->trip($user);

        $this->assertSame(['Lanjutkan persiapan', route('trips.preparation', $trip)], [$this->fase($user)['kartu']['aksi']['label'], $this->fase($user)['kartu']['aksi']['href']]);

        $this->butir($trip, 'Tenda', PreparationStatus::CONFIRMED);

        $this->assertSame(['Buka ruang kerja', route('trips.show', $trip)], [$this->fase($user)['kartu']['aksi']['label'], $this->fase($user)['kartu']['aksi']['href']]);
    }

    /**
     * W4-1c: rencana yang tanggalnya lewat tanpa dimulai tidak pernah menjadi trip mendatang,
     * tetapi disebut dengan tautan ke ruang kerjanya.
     */
    public function test_a_plan_whose_date_passed_without_starting_is_named_but_never_next(): void
    {
        $user = $this->pendaki();
        $lewat = $this->trip($user, ['planned_date' => '2026-09-20', 'end_date' => null]);

        $fase = $this->fase($user);

        $this->assertSame('B1', $fase['kunci']);
        $this->assertSame(['nama' => 'Gede bareng Dimas', 'tanggal' => 'Min, 20 Sep 2026', 'url' => route('trips.show', $lewat)], $fase['tripLewat']);
    }

    /**
     * Ruling W5-2b (M5): rencana yang tanggalnya lewat disebut di B0 dan B4 juga, bukan hanya
     * B1: ketiga fase berbagi kode yang sama (`RingkasanDasbor::konteks()`), dan ini
     * membuktikannya untuk kedua fase lain, bukan hanya mengasumsikannya dari B1.
     */
    public function test_trip_lewat_is_also_named_in_b0_and_b4(): void
    {
        $tanpaProfil = $this->pendaki(profil: false);
        $this->trip($tanpaProfil, ['planned_date' => '2026-09-18', 'end_date' => null]);

        $faseB0 = $this->fase($tanpaProfil);

        $this->assertSame('B0', $faseB0['kunci']);
        $this->assertNotNull($faseB0['tripLewat']);

        $sudahLapor = $this->pendaki();
        $this->trip($sudahLapor, ['planned_date' => '2026-09-18', 'end_date' => null]);
        $selesai = $this->trip($sudahLapor, ['planned_date' => '2026-09-22']);
        $this->selesai($sudahLapor, $selesai, now()->subDay());

        $faseB4 = $this->fase($sudahLapor);

        $this->assertSame('B4', $faseB4['kunci']);
        $this->assertNotNull($faseB4['tripLewat']);
    }

    /**
     * Ruling W5-2b (M5): DRAFT termasuk himpunan "belum dimulai" (TripStatus::belumDimulai()),
     * sama seperti PLANNED dan READY_FOR_DEPARTURE, jadi trip berstatus DRAFT tetap menjadi
     * trip mendatang B2.
     */
    public function test_a_draft_trip_counts_as_upcoming(): void
    {
        $user = $this->pendaki();
        $trip = $this->trip($user, ['status' => TripStatus::DRAFT->value]);

        $fase = $this->fase($user);

        $this->assertSame('B2', $fase['kunci']);
        $this->assertSame($trip->id, $fase['kartu']['id']);
    }

    /**
     * Pukul 23.00 WIB Jumat 25 September sudah Sabtu 26 September di Papua. Trip WIT tanggal 25
     * sudah lewat, sedangkan trip WIB tanggal 25 masih hari H.
     */
    public function test_the_mountains_zone_decides_whether_the_trip_day_has_passed(): void
    {
        $this->travelTo(Carbon::parse('2026-09-25 16:00:00', 'UTC'));
        $papua = $this->pendaki();
        $this->trip($papua, ['planned_date' => '2026-09-25', 'end_date' => null], 'Asia/Jayapura');

        $this->assertSame('B1', $this->fase($papua)['kunci']);
        $this->assertNotNull($this->fase($papua)['tripLewat']);

        $jawa = $this->pendaki();
        $this->trip($jawa, ['planned_date' => '2026-09-25', 'end_date' => null]);

        $this->assertSame('Hari H', $this->fase($jawa)['kartu']['hari']);
    }

    public function test_a_closure_since_planning_is_a_warning_on_the_card(): void
    {
        $user = $this->pendaki();
        $trip = $this->trip($user);
        OfficialStatus::factory()->closed()->create([
            'statusable_type' => (new Trail)->getMorphClass(),
            'statusable_id' => $trip->trail_id,
            'scope' => StatusScope::TRAIL->value,
            'effective_at' => now()->subHour(),
        ]);

        $this->assertSame('Status resmi jalur sekarang: Tutup. Sumber dan waktunya ada di cek kesiapan.', $this->fase($user)['kartu']['peringatan']);
    }

    /**
     * Ruling W5-2b (M5): Dibatasi, bukan hanya Tutup, juga menjadi peringatan pada kartu.
     */
    public function test_a_restriction_since_planning_is_a_warning_on_the_card(): void
    {
        $user = $this->pendaki();
        $trip = $this->trip($user);
        OfficialStatus::factory()->restricted()->create([
            'statusable_type' => (new Trail)->getMorphClass(),
            'statusable_id' => $trip->trail_id,
            'scope' => StatusScope::TRAIL->value,
            'effective_at' => now()->subHour(),
        ]);

        $this->assertSame('Status resmi jalur sekarang: Dibatasi. Sumber dan waktunya ada di cek kesiapan.', $this->fase($user)['kartu']['peringatan']);
    }

    /**
     * Ruling W5-2c (M2): bilah langkah membungkus (flex-wrap), tidak meluap ke padding kartu
     * atau membuat halaman menggulir menyamping di 320 px, termasuk saat keempat langkah
     * selesai (centang menambah lebar label) atau dengan spasi teks WCAG 1.4.12. Diukur lewat
     * sumber kelas pembungkusnya, bukan pratinjau peramban: lihat laporan untuk alasannya.
     */
    public function test_the_step_bar_wraps_instead_of_overflowing_at_320px(): void
    {
        $kartu = File::get(resource_path('js/components/beranda/KartuPerjalanan.tsx'));

        $this->assertMatchesRegularExpression('/<ol[^>]*\bflex-wrap\b[^>]*>/', $kartu);
    }

    /**
     * R-014 dan R-005: trip yang sudah ditinjau tidak berbunyi "Siap berangkat" di Beranda, dan
     * tidak ada skor atau persen di props.
     */
    public function test_no_status_word_score_or_percent_reaches_the_home(): void
    {
        $user = $this->pendaki();
        $this->trip($user, ['status' => TripStatus::READY_FOR_DEPARTURE->value]);

        $json = json_encode($this->fase($user));

        $this->assertStringNotContainsString('Siap berangkat', $json);
        $this->assertStringNotContainsString('%', $json);
        $this->assertDoesNotMatchRegularExpression('/skor|score/i', $json);
    }

    /**
     * Ruling W2-6b: muat ulang parsial lembar Pengingat tidak menghitung Beranda. Kueri riwayat
     * dan sesi pendakian hanya milik Beranda, jadi keduanya tidak boleh berjalan.
     */
    public function test_the_reminder_sheet_reload_does_not_compute_the_home(): void
    {
        $user = $this->pendaki();
        $this->trip($user);
        $respons = $this->actingAs($user)->get(route('dashboard'));
        $kueri = [];
        DB::listen(function ($query) use (&$kueri) {
            $kueri[] = $query->sql;
        });

        $respons->assertInertia(fn (AssertableInertia $page) => $page
            ->reloadOnly('daftarPengingat', fn (AssertableInertia $muatUlang) => $muatUlang->missing('fase')));

        $this->assertSame([], array_values(array_filter($kueri, fn (string $sql) => str_contains($sql, 'hiking_history') || str_contains($sql, 'hiking_sessions'))));
    }

    /**
     * Kalimat dukungan keputusan Beranda sebelum W5 tetap ada (R-012, R-014): Beranda membantu
     * memutuskan, tidak menyatakan gunung atau jalur aman, dan tidak menggantikan informasi resmi.
     */
    public function test_the_home_still_says_it_supports_decisions_and_declares_nothing_safe(): void
    {
        $halaman = File::get(resource_path('js/pages/Dashboard.tsx'));

        $this->assertStringContainsString('Sistem ini membantu pengambilan keputusan pendakian.', $halaman);
        $this->assertStringContainsString('informasi resmi dari pengelola.', $halaman);
    }

    /**
     * Sapaan dihitung di klien dari jam perangkat (spec v3 bagian 6.1); halaman memilih satu
     * kartu menurut fase, dan setiap kartu memakai satu aksi utama dari server.
     */
    public function test_the_greeting_comes_from_the_device_clock_and_each_card_has_one_primary_action(): void
    {
        $halaman = File::get(resource_path('js/pages/Dashboard.tsx'));
        $sapaan = File::get(resource_path('js/components/beranda/sapaan.ts'));
        $kartu = File::get(resource_path('js/components/beranda/KartuFase.tsx'));

        $this->assertStringContainsString('title={`${sapaan(new Date())}, ${auth.user?.name ?? \'\'}`}', $halaman);
        $this->assertStringContainsString('<KartuFase kartu={fase.kartu} />', $halaman);
        $this->assertStringContainsString("return 'Selamat pagi';", $sapaan);
        $this->assertStringContainsString('if (jam >= 15 && jam < 18) {', $sapaan);
        $this->assertSame(4, substr_count($kartu, '<AksiUtama aksi={kartu.aksi} />'));
        $this->assertStringContainsString('<AksiUtama aksi={kartu.aksi} />', File::get(resource_path('js/components/beranda/KartuPerjalanan.tsx')));
    }

    /**
     * I3(a), ruling W5-5b: "Jalur lain untuk Anda" tidak ada di B0 (profil belum terisi, tanpa
     * trip apa pun) maupun B3 (sedang mendaki) -- MJ1 pada tinjauan Tugas 5, gerbang fase tanpa
     * test regresi sebelum ini.
     */
    public function test_jalur_lain_is_absent_in_b0_and_b3(): void
    {
        $tanpaProfil = $this->pendaki(profil: false);
        $propsB0 = $this->actingAs($tanpaProfil)->get(route('dashboard'))->assertOk()->viewData('page')['props'];

        $this->assertSame('B0', $propsB0['fase']['kunci']);
        $this->assertNull($propsB0['jalurLain']);

        $user = $this->pendaki();
        $trip = $this->trip($user, ['status' => TripStatus::IN_PROGRESS->value, 'planned_date' => '2026-09-25']);
        $pos = Checkpoint::factory()->for($trip->trail)->create(['sequence' => 1]);
        HikingSession::create([
            'trip_plan_id' => $trip->id,
            'user_id' => $user->id,
            'current_checkpoint_id' => $pos->id,
            'status' => HikingSessionStatus::ACTIVE->value,
            'started_at' => now()->subHours(1),
        ]);
        $propsB3 = $this->actingAs($user)->get(route('dashboard'))->assertOk()->viewData('page')['props'];

        $this->assertSame('B3', $propsB3['fase']['kunci']);
        $this->assertNull($propsB3['jalurLain']);
    }

    /**
     * I3(b), ruling W5-5b: di B2 dan B4 tanpa profil lengkap, "Jalur lain untuk Anda" mengajak
     * mengisi profil dan tidak menilai jalur apa pun (R-006) -- MJ2 pada tinjauan Tugas 5.
     */
    public function test_jalur_lain_asks_for_a_profile_in_b2_and_b4_without_one(): void
    {
        $user = $this->pendaki(profil: false);
        $this->trip($user);
        $propsB2 = $this->actingAs($user)->get(route('dashboard'))->assertOk()->viewData('page')['props'];

        $this->assertSame('B2', $propsB2['fase']['kunci']);
        $this->assertSame(['tanpaProfil' => true, 'jalur' => []], $propsB2['jalurLain']);

        $lain = $this->pendaki(profil: false);
        $selesai = $this->trip($lain, ['planned_date' => '2026-09-22']);
        $this->selesai($lain, $selesai, now()->subDay());
        $propsB4 = $this->actingAs($lain)->get(route('dashboard'))->assertOk()->viewData('page')['props'];

        $this->assertSame('B4', $propsB4['fase']['kunci']);
        $this->assertSame(['tanpaProfil' => true, 'jalur' => []], $propsB4['jalurLain']);
    }
}
