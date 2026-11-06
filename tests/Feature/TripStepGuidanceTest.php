<?php

namespace Tests\Feature;

use App\Enums\PreparationCategory;
use App\Enums\PreparationStatus;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Models\Mountain;
use App\Models\PermitRequirement;
use App\Models\ReadinessCheck;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Models\User;
use App\Services\PreparationService;
use App\Services\Trips\TripSteps;
use Database\Seeders\PreparationTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Kelompok TripSteps gelombang perbaikan akhir W4 (ruling W4-FB): ruang kerja, kartu indeks,
 * dan Cek kesiapan harus memberi jawaban yang sama tentang tinjauan yang tercatat (I1, NEW-2),
 * butir yang menunggu BMKG (I2), rencana yang tanggalnya lewat (W4-1c), dan trip yang selesai
 * atau batal (W4-4d). Probe P2 dan P3 tinjauan akhir dijadikan test apa adanya.
 *
 * Waktu dibekukan 7 Oktober 2026 pukul 10.00 WIB; prakiraan BMKG terbit tiga hari sebelum
 * tanggal trip (config hiking.conditions.weather_forecast_days).
 */
class TripStepGuidanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-07 03:00:00', 'UTC'));
    }

    private function trip(array $atribut = [], ?Trail $jalur = null): TripPlan
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => 'ADVANCED', 'completed_at' => now()]);

        return TripPlan::factory()->for($user)->create(array_merge([
            'trail_id' => ($jalur ?? Trail::factory()->published()->easy()->create())->id,
            'planned_date' => '2026-10-17',
            'trip_type' => TripType::CAMPING->value,
            'status' => TripStatus::PLANNED->value,
        ], $atribut));
    }

    /**
     * Daftar dari templat bawaan, seluruhnya dikonfirmasi kecuali kategori yang disebut.
     */
    private function daftar(TripPlan $trip, ?PreparationCategory $kecuali = null): void
    {
        $this->seed(PreparationTemplateSeeder::class);
        app(PreparationService::class)->generateFor($trip);
        $trip->preparationItems()
            ->when($kecuali, fn ($q) => $q->where('category', '!=', $kecuali->value))
            ->update(['status' => PreparationStatus::CONFIRMED->value]);
    }

    /**
     * @return array<string, mixed>
     */
    private function props(TripPlan $trip, string $rute): array
    {
        $url = $rute === 'trips.index' ? route($rute) : route($rute, $trip);

        return $this->actingAs($trip->user)->get($url)->assertOk()->viewData('page')['props'];
    }

    /**
     * @param  array<string, mixed>  $props
     * @return array<string, mixed>
     */
    private function langkah(array $props, string $kunci): array
    {
        return collect($props['langkah'])->firstWhere('kunci', $kunci);
    }

    /**
     * P2 (I1): sesudah tinjauan tercatat, "Simpan hasil cek ini" menulis baris baru yang belum
     * dikonfirmasi. Ruang kerja, kartu indeks, dan Cek tetap membaca tinjauan yang sama, dengan
     * jam konfirmasinya, bukan jam simpan (NEW-1, mutasi N6).
     */
    public function test_a_recorded_review_reads_the_same_everywhere_after_a_fresh_save(): void
    {
        $trip = $this->trip();
        $this->daftar($trip);

        $this->actingAs($trip->user)->post(route('trips.readiness.confirm', $trip))->assertSessionHas('status');
        $this->travel(2)->hours();
        $this->actingAs($trip->user)->post(route('trips.readiness.recompute', $trip))->assertSessionHas('status');

        $cek = $this->props($trip, 'trips.readiness');
        $ruangKerja = $this->props($trip, 'trips.show');
        $indeks = $this->props($trip, 'trips.index');

        $this->assertSame(2, ReadinessCheck::query()->where('trip_plan_id', $trip->id)->count());
        $this->assertTrue($cek['preDeparture']['dikonfirmasi']);
        $this->assertSame('7 Okt 2026 10.00 WIB', $cek['preDeparture']['dicatat']);
        $this->assertSame(TripSteps::SELESAI, $this->langkah($ruangKerja, 'kesiapan')['keadaan']);
        $this->assertSame('Tinjauan tercatat 7 Okt 2026 10.00 WIB.', $this->langkah($ruangKerja, 'kesiapan')['catatan']);
        $this->assertNull($indeks['utama']['fase']);
        $this->assertSame('Buka ruang kerja', $indeks['utama']['aksi']['label']);
    }

    /**
     * NEW-2: tanda tinjauan tetap terbaca sesudah pendakian dimulai, di Cek dan di ruang kerja.
     */
    public function test_the_review_mark_stays_once_the_hike_has_started(): void
    {
        $trip = $this->trip(['planned_date' => '2026-10-08']);
        $this->daftar($trip);

        $this->actingAs($trip->user)->post(route('trips.readiness.confirm', $trip));
        $this->actingAs($trip->user)->post(route('trips.start', $trip));

        $cek = $this->props($trip, 'trips.readiness');

        $this->assertSame(TripStatus::IN_PROGRESS, $trip->fresh()->status);
        $this->assertTrue($cek['preDeparture']['dikonfirmasi']);
        $this->assertSame('Tinjauan sudah tercatat 7 Okt 2026 10.00 WIB.', $cek['alasanTanpaTinjauan']);
        $this->assertSame(TripSteps::SELESAI, $this->langkah($this->props($trip, 'trips.show'), 'kesiapan')['keadaan']);
    }

    /**
     * NEW-1 (mutasi N6): trip Siap berangkat yang hanya punya baris belum dikonfirmasi tidak
     * terbaca sudah ditinjau. Predikatnya menyaring pre_departure_confirmed.
     */
    public function test_an_unconfirmed_saved_check_is_never_read_as_a_review(): void
    {
        $trip = $this->trip(['status' => TripStatus::READY_FOR_DEPARTURE->value]);
        $this->daftar($trip);
        ReadinessCheck::create([
            'trip_plan_id' => $trip->id,
            'computed_state' => 'NEEDS_PREPARATION',
            'official_status_snapshot' => ['status' => 'UNKNOWN'],
            'pre_departure_confirmed' => false,
            'computed_at' => now(),
        ]);

        $cek = $this->props($trip, 'trips.readiness');

        $this->assertFalse($cek['preDeparture']['dikonfirmasi']);
        $this->assertSame(TripSteps::BERIKUTNYA, $this->langkah($this->props($trip, 'trips.show'), 'kesiapan')['keadaan']);
    }

    /**
     * P3 (I2): trip H-10 dengan seluruh butir dikonfirmasi kecuali dua butir cuaca. Butir itu
     * belum dapat dikerjakan, jadi Persiapan bukan langkah berikutnya: keterangannya menyebut
     * tanggal terbit, dan aksi utama indeks menuju Cek, sama dengan Cek yang tidak menawarkan
     * "Lanjutkan persiapan".
     */
    public function test_weather_items_waiting_for_bmkg_do_not_make_preparation_the_next_step(): void
    {
        $trip = $this->trip();
        $this->daftar($trip, kecuali: PreparationCategory::WEATHER);

        $ruangKerja = $this->props($trip, 'trips.show');
        $indeks = $this->props($trip, 'trips.index');
        $cek = $this->props($trip, 'trips.readiness');
        $persiapan = $this->langkah($ruangKerja, 'persiapan');

        $this->assertSame(TripSteps::BELUM, $persiapan['keadaan']);
        $this->assertMatchesRegularExpression('/^\d+ dari \d+ butir dikonfirmasi; 2 butir cuaca menunggu BMKG, terbit mulai Rab, 14 Okt$/', $persiapan['keterangan']);
        $this->assertSame(TripSteps::BERIKUTNYA, $this->langkah($ruangKerja, 'kesiapan')['keadaan']);
        $this->assertSame('kesiapan', $indeks['utama']['fase']['kunci']);
        $this->assertSame(['label' => 'Cek kesiapan', 'href' => route('trips.readiness', $trip)], $indeks['utama']['aksi']);
        $this->assertFalse($cek['persiapan']['lanjutkan']);
        $this->assertNotNull($cek['persiapan']['semuaMenungguBmkg']);
    }

    /**
     * Arah sebaliknya: satu butir yang dapat dikerjakan sekarang tetap menjadikan Persiapan
     * langkah berikutnya, juga di samping butir cuaca yang menunggu.
     */
    public function test_an_item_that_can_be_done_now_keeps_preparation_next(): void
    {
        $trip = $this->trip();
        $this->daftar($trip, kecuali: PreparationCategory::WEATHER);
        $trip->preparationItems()->where('category', PreparationCategory::EQUIPMENT->value)->limit(1)->update(['status' => PreparationStatus::NOT_CONFIRMED->value]);

        $persiapan = $this->langkah($this->props($trip, 'trips.show'), 'persiapan');

        $this->assertSame(TripSteps::BERIKUTNYA, $persiapan['keadaan']);
        $this->assertStringNotContainsString('menunggu BMKG', $persiapan['keterangan']);
        $this->assertSame('Lanjutkan persiapan', $this->props($trip, 'trips.index')['utama']['aksi']['label']);
    }

    /**
     * Hanya butir cuaca yang BELUM dikonfirmasi yang menunggu (tinjauan Tugas 4 M3 d): butir cuaca
     * yang sudah dikonfirmasi tidak dihitung menunggu, di langkah trip maupun di Persiapan.
     */
    public function test_a_checked_weather_item_is_not_counted_as_waiting(): void
    {
        $trip = $this->trip();
        $this->daftar($trip, kecuali: PreparationCategory::WEATHER);
        $dikonfirmasi = $trip->preparationItems()->where('category', PreparationCategory::WEATHER->value)->where('is_critical', true)->firstOrFail();
        $dikonfirmasi->update(['status' => PreparationStatus::CONFIRMED->value]);

        $persiapan = $this->langkah($this->props($trip, 'trips.show'), 'persiapan');
        $butir = collect($this->props($trip, 'trips.preparation')['kategori'])->firstWhere('kunci', 'WEATHER')['items'];

        $this->assertSame(TripSteps::BELUM, $persiapan['keadaan']);
        $this->assertStringContainsString('; 1 butir cuaca menunggu BMKG', $persiapan['keterangan']);
        $this->assertNull(collect($butir)->firstWhere('id', $dikonfirmasi->id)['menunggu']);
    }

    /**
     * Begitu prakiraan tanggal trip terbit, butir cuaca adalah pekerjaan biasa lagi.
     */
    public function test_weather_items_become_the_next_step_once_the_forecast_is_out(): void
    {
        $trip = $this->trip();
        $this->daftar($trip, kecuali: PreparationCategory::WEATHER);

        $this->travelTo(Carbon::parse('2026-10-14 03:00:00', 'UTC'));

        $this->assertSame(TripSteps::BERIKUTNYA, $this->langkah($this->props($trip, 'trips.show'), 'persiapan')['keadaan']);
    }

    /**
     * W4-1c dan W4-Fpre (a): rencana yang belum dimulai tetapi tanggalnya lewat tetap paling atas
     * di Mendatang tanpa hitung mundur, menyebut dirinya di kepala dan kartu, dan aksi tunggalnya
     * mengubah rencana: tidak ada langkah berikutnya di kartu maupun ruang kerja (W4-FK N2).
     * Statusnya tidak berubah sendiri.
     */
    public function test_a_plan_whose_date_passed_says_so_and_offers_to_change_the_plan(): void
    {
        $lewat = $this->trip(['planned_date' => '2026-10-01']);
        $berikut = TripPlan::factory()->for($lewat->user)->create([
            'trail_id' => Trail::factory()->published()->create()->id,
            'planned_date' => '2026-10-20',
        ]);

        $indeks = $this->props($lewat, 'trips.index');
        $kepala = $this->props($lewat, 'trips.show')['kepala'];
        $kartu = collect($indeks['kelompok'])->firstWhere('kunci', 'mendatang')['trip'][0];

        $this->assertSame($berikut->id, $indeks['utama']['id']);
        $this->assertNull($indeks['utama']['lewat']);
        $this->assertSame($lewat->id, $kartu['id']);
        $this->assertNull($kartu['hari']);
        $this->assertSame('Tanggal rencana sudah lewat', $kartu['lewat']);
        $this->assertSame(['label' => 'Ubah rencana', 'href' => route('trips.edit', $lewat)], $kartu['aksi']);
        $this->assertNull($kartu['fase']);
        $this->assertNotContains(TripSteps::BERIKUTNYA, array_column($this->props($lewat, 'trips.show')['langkah'], 'keadaan'));
        $this->assertNull($kepala['hari']);
        $this->assertSame('Tanggal rencana sudah lewat', $kepala['lewat']);
        $this->assertSame(TripStatus::PLANNED, $lewat->fresh()->status);
    }

    /**
     * Ruling W4-FK (Out-of-Scope 1 tinjauan ulang): rencana yang belum dimulai tetapi tanggalnya
     * sudah lewat tidak dapat ditinjau. Halaman dan POST membaca satu sumber
     * (PreDepartureCheck::alasanPenolakan), jadi kalimatnya sama dan statusnya tidak berubah.
     * Aksi utama halaman Cek untuk rencana itu mengubah rencana.
     */
    public function test_a_review_for_a_plan_whose_date_passed_is_refused_on_the_page_and_on_post(): void
    {
        $lewat = $this->trip(['planned_date' => '2026-10-05']);
        $this->daftar($lewat);
        $kalimat = 'Tinjauan tidak dapat dicatat karena tanggal rencana sudah lewat. Ubah rencana dulu.';

        $cek = $this->props($lewat, 'trips.readiness');

        $this->assertFalse($cek['bolehKonfirmasi']);
        $this->assertSame($kalimat, $cek['alasanTanpaTinjauan']);
        $this->assertSame(route('trips.edit', $lewat), $cek['kepala']['ubah']);
        $this->assertSame('Tanggal rencana sudah lewat', $cek['kepala']['lewat']);

        $this->actingAs($lewat->user)->post(route('trips.readiness.confirm', $lewat))
            ->assertRedirect(route('trips.readiness', $lewat))
            ->assertSessionHas('penolakan-kesiapan', $kalimat);

        $this->assertSame(TripStatus::PLANNED, $lewat->fresh()->status);
        $this->assertSame(0, ReadinessCheck::query()->where('trip_plan_id', $lewat->id)->count());
    }

    /**
     * Tinjauan Tugas 1 M5 (a) dan Tugas 2 M3: "hari ini" dibaca di zona gunungnya. Pukul 01.30
     * WIT tanggal 7 masih 23.30 WIB tanggal 6, jadi rencana tanggal 6 di gunung WIT sudah lewat
     * sedangkan rencana tanggal 6 di gunung WIB jatuh hari ini. Kartu utama memakai hitungan
     * yang sama, bukan lantai WIB.
     */
    public function test_days_left_and_the_lead_card_use_the_mountains_own_zone(): void
    {
        $this->travelTo(Carbon::parse('2026-10-06 16:30:00', 'UTC'));
        $papua = Trail::factory()->for(Mountain::factory()->create(['timezone' => 'Asia/Jayapura']))->published()->create();
        $jawa = Trail::factory()->for(Mountain::factory()->create(['timezone' => 'Asia/Jakarta']))->published()->create();
        $tripPapua = $this->trip(['planned_date' => '2026-10-06'], $papua);
        $tripJawa = TripPlan::factory()->for($tripPapua->user)->create(['trail_id' => $jawa->id, 'planned_date' => '2026-10-06']);

        $indeks = $this->props($tripPapua, 'trips.index');

        $this->assertNull($this->props($tripPapua, 'trips.show')['kepala']['hari']);
        $this->assertSame('Tanggal rencana sudah lewat', $this->props($tripPapua, 'trips.show')['kepala']['lewat']);
        $this->assertSame('Hari H', $this->props($tripJawa, 'trips.show')['kepala']['hari']);
        $this->assertSame($tripJawa->id, $indeks['utama']['id']);
    }

    /**
     * Tinjauan Tugas 1 M5 (b) sampai (d): kepala trip selesai dan batal menyebut statusnya tanpa
     * tautan ubah; trip selesai tanpa riwayat tidak punya aksi; trip berlangsung tidak menunjuk
     * langkah perencanaan.
     */
    public function test_finished_and_started_trips_have_no_planning_step_or_edit_link(): void
    {
        $selesai = $this->trip(['status' => TripStatus::COMPLETED->value]);
        $batal = $this->trip(['status' => TripStatus::CANCELLED->value]);
        $berlangsung = $this->trip(['status' => TripStatus::IN_PROGRESS->value]);
        $berlangsung->load('trail.mountain', 'preparationItems', 'latestReadinessCheck', 'latestConfirmedReadinessCheck', 'history');
        $selesai->load('trail.mountain', 'preparationItems', 'latestReadinessCheck', 'latestConfirmedReadinessCheck', 'history');

        $kepalaSelesai = $this->props($selesai, 'trips.show')['kepala'];
        $kepalaBatal = $this->props($batal, 'trips.show')['kepala'];

        $this->assertSame(['Selesai', null], [$kepalaSelesai['keadaan'], $kepalaSelesai['ubah']]);
        $this->assertSame(['Dibatalkan', null], [$kepalaBatal['keadaan'], $kepalaBatal['ubah']]);
        $this->assertNull(TripSteps::aksi($selesai, app(TripSteps::class)->for($selesai)));
        $this->assertNotContains(TripSteps::BERIKUTNYA, array_column(app(TripSteps::class)->for($berlangsung), 'keadaan'));
    }

    /**
     * Satu predikat "belum dimulai" untuk kepala, langkah, kartu, dan siklus hidup (tinjauan
     * Tugas 1 M2).
     */
    public function test_not_started_means_draft_planned_or_ready(): void
    {
        $belum = array_values(array_filter(TripStatus::cases(), fn (TripStatus $s) => $s->belumDimulai()));

        $this->assertSame([TripStatus::DRAFT, TripStatus::PLANNED, TripStatus::READY_FOR_DEPARTURE], $belum);
    }

    /**
     * W4-4d (R-013): tanggal trip yang pindah mengembalikan butir cuaca yang sudah dikonfirmasi;
     * butir lain dan butir cuaca yang Tidak berlaku tetap. Tanggal yang tidak berubah tidak
     * menyentuh apa pun.
     */
    public function test_moving_the_trip_date_unconfirms_checked_weather_items_only(): void
    {
        $trip = $this->trip(['planned_date' => '2026-10-09']);
        $cuaca = TripPreparationItem::create(['trip_plan_id' => $trip->id, 'category' => 'WEATHER', 'label' => 'Prakiraan', 'is_critical' => true, 'status' => 'CONFIRMED']);
        $cuacaTidakBerlaku = TripPreparationItem::create(['trip_plan_id' => $trip->id, 'category' => 'WEATHER', 'label' => 'Peringatan', 'is_critical' => false, 'status' => 'NOT_APPLICABLE']);
        $alat = TripPreparationItem::create(['trip_plan_id' => $trip->id, 'category' => 'EQUIPMENT', 'label' => 'Headlamp', 'is_critical' => true, 'status' => 'CONFIRMED']);
        $isian = fn (string $tanggal) => ['name' => $trip->name, 'planned_date' => $tanggal, 'trip_type' => TripType::CAMPING->value];

        $this->actingAs($trip->user)->put(route('trips.update', $trip), $isian('2026-10-09'))->assertRedirect(route('trips.show', $trip));
        $this->assertSame(PreparationStatus::CONFIRMED, $cuaca->fresh()->status);

        $this->actingAs($trip->user)->put(route('trips.update', $trip), $isian('2026-10-11'))->assertRedirect(route('trips.show', $trip));

        $this->assertSame(PreparationStatus::NOT_CONFIRMED, $cuaca->fresh()->status);
        $this->assertSame(PreparationStatus::NOT_APPLICABLE, $cuacaTidakBerlaku->fresh()->status);
        $this->assertSame(PreparationStatus::CONFIRMED, $alat->fresh()->status);
    }

    /**
     * W4-4d: daftar trip yang selesai atau batal adalah catatan. Server menolak centang dan
     * "Perbarui daftar", halaman tidak menawarkan keduanya, dan membuka halaman tidak
     * membangkitkan daftar (Declined 5).
     */
    public function test_a_finished_trips_checklist_is_a_record_not_work(): void
    {
        $this->seed(PreparationTemplateSeeder::class);
        $batal = $this->trip(['status' => TripStatus::CANCELLED->value]);
        $butir = TripPreparationItem::create(['trip_plan_id' => $batal->id, 'category' => 'EQUIPMENT', 'label' => 'Headlamp', 'is_critical' => true, 'status' => 'NOT_CONFIRMED']);
        $kosong = $this->trip(['status' => TripStatus::COMPLETED->value]);

        $this->actingAs($batal->user)->post(route('trips.preparation.update', ['trip' => $batal, 'item' => $butir->id]), ['status' => 'CONFIRMED'])
            ->assertSessionHasErrors(['status' => 'Daftar persiapan tidak dapat diubah lagi: trip berstatus Dibatalkan.']);
        $this->actingAs($batal->user)->post(route('trips.preparation.regenerate', $batal))
            ->assertSessionHasErrors(['daftar' => 'Daftar persiapan tidak dapat diubah lagi: trip berstatus Dibatalkan.']);

        $this->assertSame(PreparationStatus::NOT_CONFIRMED, $butir->fresh()->status);
        $this->assertSame(1, $batal->preparationItems()->count());
        $this->assertFalse($this->props($batal, 'trips.preparation')['dapatDiubah']);
        $this->props($kosong, 'trips.preparation');
        $this->assertSame(0, $kosong->preparationItems()->count());
    }

    /**
     * W4-4d: Cek untuk trip batal atau yang tanggalnya lewat tidak menawarkan "Selesaikan" atau
     * "Simpan hasil cek ini" (server menolak hal yang sama), dan prakiraan untuk tanggal lewat
     * tidak berbunyi "belum tersedia dari BMKG". Butir yang tidak ditawari aksi bukan butir yang
     * menunggu BMKG: kalimat menunggu hanya untuk butir cuaca (I2).
     */
    public function test_the_check_page_offers_no_follow_up_for_cancelled_or_past_trips(): void
    {
        $batal = $this->trip(['status' => TripStatus::CANCELLED->value]);
        TripPreparationItem::create(['trip_plan_id' => $batal->id, 'category' => 'EQUIPMENT', 'label' => 'Headlamp', 'is_critical' => true, 'status' => 'NOT_CONFIRMED']);
        TripPreparationItem::create(['trip_plan_id' => $batal->id, 'category' => 'WEATHER', 'label' => 'Prakiraan cuaca', 'is_critical' => true, 'status' => 'NOT_CONFIRMED']);
        $lewat = $this->trip(['planned_date' => '2026-10-01']);
        $mendatang = $this->trip();

        $cekBatal = $this->props($batal, 'trips.readiness');
        $cekLewat = $this->props($lewat, 'trips.readiness');

        $this->assertFalse($cekBatal['tindakLanjut']);
        $this->assertNull($cekBatal['persiapan']['butir'][0]['aksi']);
        $this->assertNull($cekBatal['persiapan']['semuaMenungguBmkg']);
        $this->assertFalse($cekLewat['tindakLanjut']);
        $this->assertTrue($this->props($mendatang, 'trips.readiness')['tindakLanjut']);
        $this->assertSame('lewat', $cekLewat['kondisi']['prakiraan']['keadaan']);
        $this->assertStringNotContainsString('belum tersedia dari BMKG', (string) $cekLewat['kondisi']['prakiraan']['pesan']);

        $this->actingAs($batal->user)->post(route('trips.readiness.recompute', $batal))
            ->assertRedirect(route('trips.readiness', $batal))
            ->assertSessionHas('penolakan-kesiapan');
        $this->assertSame(0, ReadinessCheck::query()->where('trip_plan_id', $batal->id)->count());
    }

    /**
     * W4-FK N3 (a): trip yang batal atau selesai tidak punya butir yang menunggu BMKG, berapa pun
     * tanggalnya, dan kartu prakiraannya menyebut status trip, bukan tanggal terbit. Cek,
     * Persiapan, dan langkah ruang kerja membaca satu gerbang (PreparationService::terbitPrakiraan).
     */
    public function test_a_cancelled_or_finished_trip_has_no_weather_waiting_and_no_forecast_to_check(): void
    {
        $this->seed(PreparationTemplateSeeder::class);

        foreach ([TripStatus::CANCELLED, TripStatus::COMPLETED] as $status) {
            $trip = $this->trip(['status' => $status->value]);
            app(PreparationService::class)->generateFor($trip);
            $trip->preparationItems()->where('category', '!=', PreparationCategory::WEATHER->value)->update(['status' => PreparationStatus::CONFIRMED->value]);

            $cek = $this->props($trip, 'trips.readiness');
            $cuaca = collect($this->props($trip, 'trips.preparation')['kategori'])->firstWhere('kunci', PreparationCategory::WEATHER->value)['items'];
            $langkahPersiapan = $this->langkah($this->props($trip, 'trips.show'), 'persiapan');

            $this->assertNull($cek['persiapan']['semuaMenungguBmkg'], $status->value);
            $this->assertNull($cek['persiapan']['menungguLain'], $status->value);
            $this->assertSame([null], array_unique(array_column($cek['persiapan']['butir'], 'menunggu')), $status->value);
            $this->assertSame('tidakDiperiksa', $cek['kondisi']['prakiraan']['keadaan']);
            $this->assertSame(sprintf('Trip ini berstatus %s, jadi prakiraannya tidak diperiksa lagi.', $status->label()), $cek['kondisi']['prakiraan']['pesan']);
            $this->assertSame([null], array_unique(array_column($cuaca, 'menunggu')), $status->value);
            $this->assertStringNotContainsString('menunggu BMKG', $langkahPersiapan['keterangan']);
        }
    }

    /**
     * W4-FK N3 (b): pendakian multi-hari yang berjalan pada hari kedua tidak dibilang "sudah
     * lewat"; kalimatnya netral dan tanpa prakiraan karangan untuk hari ini (R-013). Rencana yang
     * belum dimulai dengan tanggal lewat tetap "sudah lewat" (lihat test di atas).
     */
    public function test_a_multi_day_hike_in_progress_is_not_told_its_date_has_passed(): void
    {
        $berjalan = $this->trip([
            'planned_date' => '2026-10-06',
            'end_date' => '2026-10-08',
            'status' => TripStatus::IN_PROGRESS->value,
        ]);

        $prakiraan = $this->props($berjalan, 'trips.readiness')['kondisi']['prakiraan'];

        $this->assertSame('berjalan', $prakiraan['keadaan']);
        $this->assertSame('Pendakian ini sedang berjalan. Cek kesiapan menilai persiapan sebelum berangkat, jadi prakiraan untuk tanggal berangkat tidak ditampilkan lagi di sini.', $prakiraan['pesan']);
        $this->assertSame([], $prakiraan['baris']);
        $this->assertStringContainsString("berjalan: 'Pendakian berjalan',", File::get(resource_path('js/pages/Trips/Readiness.tsx')));
    }

    /**
     * W4-4d: trip yang batal tidak ditawari pemesanan izin untuk tanggalnya, di ruang kerja
     * maupun di butir izin Persiapan.
     */
    public function test_a_cancelled_trip_is_not_offered_a_permit_booking(): void
    {
        $jalur = Trail::factory()->published()->create();
        PermitRequirement::factory()->create(['mountain_id' => $jalur->mountain_id, 'trail_id' => $jalur->id, 'booking_url' => 'https://izin.example.test', 'booking_opens_days_before' => 30, 'booking_closes_days_before' => 2]);
        $batal = $this->trip(['status' => TripStatus::CANCELLED->value], $jalur);
        $aktif = $this->trip([], $jalur);
        $tautanIzin = function (TripPlan $trip): ?array {
            TripPreparationItem::create(['trip_plan_id' => $trip->id, 'category' => 'LOGISTICS', 'label' => PreparationService::PERMIT_LABEL, 'is_critical' => true, 'status' => 'NOT_CONFIRMED']);

            return collect($this->props($trip, 'trips.preparation')['kategori'])->flatMap(fn (array $k) => $k['items'])->firstWhere('label', PreparationService::PERMIT_LABEL)['tautan'];
        };

        $this->assertNull($this->props($batal, 'trips.show')['izin']);
        $this->assertNotNull($this->props($aktif, 'trips.show')['izin']);
        $this->assertNull($tautanIzin($batal));
        $this->assertNotNull($tautanIzin($aktif));
    }
}
