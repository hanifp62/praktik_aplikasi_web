<?php

namespace Tests\Feature;

use App\Enums\CompletionState;
use App\Enums\PreparationCategory;
use App\Enums\PreparationStatus;
use App\Enums\ReadinessState;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Models\HikingHistory;
use App\Models\ReadinessCheck;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Models\User;
use App\Services\Trips\IndeksTrip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Indeks trip (spec v3 bagian 6.9, rencana induk W4 butir 1, W4 Tugas 2): kartu utama trip
 * terdekat dengan fase dan aksi berikutnya, lalu kelompok Berlangsung, Mendatang, dan Selesai
 * (ke Progres). Hanya trip milik pembacanya; tanpa skor; kueri tidak tumbuh bersama jumlah
 * trip.
 *
 * Waktu dibekukan pada 25 September 2026 pukul 10.00 WIB.
 */
class TripIndexPageTest extends TestCase
{
    use RefreshDatabase;

    private User $pendaki;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-09-25 03:00:00', 'UTC'));
        $this->pendaki = User::factory()->create();
    }

    private function trip(string $tanggal, TripStatus $status = TripStatus::PLANNED, ?User $pemilik = null): TripPlan
    {
        return TripPlan::factory()->create([
            'user_id' => ($pemilik ?? $this->pendaki)->id,
            'trail_id' => Trail::factory()->published()->create()->id,
            'name' => 'Trip '.$tanggal.' '.$status->value,
            'planned_date' => $tanggal,
            'trip_type' => TripType::CAMPING->value,
            'status' => $status->value,
        ]);
    }

    private function butir(TripPlan $trip, PreparationStatus $status): void
    {
        TripPreparationItem::create([
            'trip_plan_id' => $trip->id,
            'category' => PreparationCategory::EQUIPMENT->value,
            'label' => 'Headlamp '.fake()->unique()->word(),
            'is_critical' => true,
            'status' => $status->value,
        ]);
    }

    private function riwayat(TripPlan $trip): void
    {
        HikingHistory::create([
            'user_id' => $trip->user_id,
            'trip_plan_id' => $trip->id,
            'trail_id' => $trip->trail_id,
            'trip_type' => TripType::CAMPING->value,
            'completion_state' => CompletionState::COMPLETED->value,
            'completed_at' => $trip->planned_date,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function props(): array
    {
        return $this->actingAs($this->pendaki)->get(route('trips.index'))->assertOk()->viewData('page')['props'];
    }

    /**
     * @param  array<string, mixed>  $props
     * @return array<string, array<int, int>>
     */
    private function idPerKelompok(array $props): array
    {
        return collect($props['kelompok'])->mapWithKeys(fn (array $k) => [$k['kunci'] => array_column($k['trip'], 'id')])->all();
    }

    public function test_a_guest_is_sent_to_login(): void
    {
        $this->get(route('trips.index'))->assertRedirect(route('login'));
    }

    /**
     * PRD §77: indeks hanya memuat trip milik pembacanya.
     */
    public function test_only_the_readers_own_trips_appear(): void
    {
        $milikSendiri = $this->trip('2026-10-10');
        $milikOrangLain = $this->trip('2026-10-01', TripStatus::PLANNED, User::factory()->create());

        $props = $this->props();
        $semua = array_merge([$props['utama']['id']], ...array_values($this->idPerKelompok($props)));

        $this->assertSame([$milikSendiri->id], $semua);
        $this->assertStringNotContainsString($milikOrangLain->name, json_encode($props));
    }

    /**
     * Trip berlangsung memimpin; trip terdekat yang belum dimulai menyusul. Kartu utama tidak
     * diulang di kelompoknya, dan kelompok tanpa trip tidak dikirim.
     */
    public function test_trips_are_grouped_into_in_progress_upcoming_and_finished(): void
    {
        $berlangsung = $this->trip('2026-09-24', TripStatus::IN_PROGRESS);
        $jauh = $this->trip('2026-11-20');
        $dekat = $this->trip('2026-10-10', TripStatus::READY_FOR_DEPARTURE);
        $selesaiLama = $this->trip('2026-06-01', TripStatus::COMPLETED);
        $batal = $this->trip('2026-08-15', TripStatus::CANCELLED);

        $props = $this->props();

        $this->assertSame($berlangsung->id, $props['utama']['id']);
        $this->assertSame([
            'mendatang' => [$dekat->id, $jauh->id],
            'selesai' => [$batal->id, $selesaiLama->id],
        ], $this->idPerKelompok($props));
        $this->assertSame(['Mendatang', 'Selesai'], array_column($props['kelompok'], 'judul'));
    }

    /**
     * Tanpa pendakian berlangsung, kartu utama adalah trip terdekat yang tanggalnya belum lewat.
     * Rencana yang tanggalnya sudah lewat tanpa pernah dimulai tetap di Mendatang dan tidak
     * menghitung mundur.
     */
    public function test_the_nearest_upcoming_trip_leads_when_nothing_is_in_progress(): void
    {
        $terlewat = $this->trip('2026-09-20');
        $dekat = $this->trip('2026-10-10');

        $props = $this->props();

        $this->assertSame($dekat->id, $props['utama']['id']);
        $this->assertSame('H-15', $props['utama']['hari']);
        $this->assertSame([$terlewat->id], $this->idPerKelompok($props)['mendatang']);
        $this->assertNull($props['kelompok'][0]['trip'][0]['hari']);
    }

    /**
     * Aksi berikutnya mengikuti fase: persiapan yang belum lengkap, cek kesiapan, mode
     * lapangan, atau ringkasan pendakian yang selesai.
     */
    public function test_each_phase_offers_its_own_next_action(): void
    {
        $persiapan = $this->trip('2026-10-10');
        $this->butir($persiapan, PreparationStatus::NOT_CONFIRMED);
        $cek = $this->trip('2026-10-20');
        $this->butir($cek, PreparationStatus::CONFIRMED);
        $selesai = $this->trip('2026-07-01', TripStatus::COMPLETED);
        $this->riwayat($selesai);
        $batal = $this->trip('2026-08-01', TripStatus::CANCELLED);

        $props = $this->props();
        $kartu = collect($props['kelompok'])->flatMap(fn (array $k) => $k['trip'])->keyBy('id');

        $this->assertSame(['label' => 'Lanjutkan persiapan', 'href' => route('trips.preparation', $persiapan)], $props['utama']['aksi']);
        $this->assertSame('Persiapan', $props['utama']['fase']['judul']);
        $this->assertSame('Cek kesiapan', $kartu[$cek->id]['aksi']['label']);
        $this->assertSame('Cek', $kartu[$cek->id]['fase']['judul']);
        $this->assertSame(route('history.summary', $selesai), $kartu[$selesai->id]['url']);
        $this->assertSame('Selesai', $kartu[$selesai->id]['keadaan']);
        $this->assertNull($kartu[$batal->id]['aksi']);
        $this->assertSame(route('trips.show', $batal), $kartu[$batal->id]['url']);
        $this->assertSame('Dibatalkan', $kartu[$batal->id]['keadaan']);

        $berlangsung = $this->trip('2026-09-25', TripStatus::IN_PROGRESS);
        $utama = $this->props()['utama'];

        $this->assertSame($berlangsung->id, $utama['id']);
        $this->assertSame(['label' => 'Buka mode lapangan', 'href' => route('trips.hike', $berlangsung)], $utama['aksi']);
        $this->assertSame('Sedang berlangsung', $utama['keadaan']);
    }

    /**
     * Kelompok Selesai menampilkan beberapa yang terbaru, menunjuk "Lihat semua" untuk sisanya
     * (ruling W4-Fpre b), dan Progres untuk riwayat pendakian.
     */
    public function test_the_finished_group_is_capped_and_points_to_the_full_list_and_progress(): void
    {
        for ($i = 1; $i <= 7; $i++) {
            $this->trip('2026-0'.$i.'-10', TripStatus::COMPLETED);
        }

        $props = $this->props();
        $selesai = collect($props['kelompok'])->firstWhere('kunci', 'selesai');

        $this->assertCount(5, $selesai['trip']);
        $this->assertSame(['jumlah' => 7, 'semua' => route('trips.index', ['selesai' => 'semua']), 'progres' => route('progress')], $props['selesai']);
    }

    /**
     * Tinjauan Tugas 2 M8: "Lihat semua" hanya bila ada yang tersembunyi, dan Progres hanya bila
     * ada pendakian selesai (Progres tidak memuat trip batal, M5).
     */
    public function test_the_finished_links_only_appear_when_they_lead_somewhere(): void
    {
        $this->trip('2026-08-15', TripStatus::CANCELLED);
        $this->trip('2026-08-20', TripStatus::CANCELLED);

        $this->assertSame(['jumlah' => 2, 'semua' => null, 'progres' => null], $this->props()['selesai']);
    }

    /**
     * R-014: trip yang pre-departure check-nya sudah dikonfirmasi tidak membawa kata status
     * "Siap berangkat"; R-005 dan BR-09: tanpa skor atau persen kecocokan dan kesiapan.
     */
    public function test_no_status_reads_as_ready_and_no_score_reaches_the_index(): void
    {
        $siap = $this->trip('2026-10-10', TripStatus::READY_FOR_DEPARTURE);
        $this->butir($siap, PreparationStatus::CONFIRMED);
        ReadinessCheck::create([
            'trip_plan_id' => $siap->id,
            'computed_state' => ReadinessState::READY->value,
            'official_status_snapshot' => ['status' => 'UNKNOWN'],
            'pre_departure_confirmed' => true,
            'computed_at' => now(),
        ]);

        $json = json_encode($this->props());

        $this->assertStringNotContainsString('Siap berangkat', $json);
        // Tinjauan Tugas 2 M12: vonis READY di kartu memakai kata DK-48, bukan "Kesiapan: Siap".
        $this->assertStringContainsString('Kesiapan: Persiapan kritis lengkap', $json);
        $this->assertStringNotContainsString('Kesiapan: Siap', $json);
        $this->assertStringNotContainsString('score', strtolower($json));
        $this->assertDoesNotMatchRegularExpression('/\d+\s?%/', $json);
    }

    public function test_the_index_does_not_grow_with_the_number_of_trips(): void
    {
        $tambah = function (int $jumlah): void {
            for ($i = 0; $i < $jumlah; $i++) {
                $trip = $this->trip('2026-1'.($i % 3).'-1'.($i % 9), $i % 4 === 0 ? TripStatus::COMPLETED : TripStatus::PLANNED);
                $this->butir($trip, $i % 2 ? PreparationStatus::CONFIRMED : PreparationStatus::NOT_CONFIRMED);
                ReadinessCheck::create(['trip_plan_id' => $trip->id, 'computed_state' => ReadinessState::NEEDS_PREPARATION->value, 'official_status_snapshot' => ['status' => 'UNKNOWN'], 'computed_at' => now()]);

                if ($i % 4 === 0) {
                    $this->riwayat($trip);
                }
            }
        };

        $hitung = function (): int {
            $jumlah = 0;
            DB::listen(function () use (&$jumlah) {
                $jumlah++;
            });
            $this->actingAs($this->pendaki)->get(route('trips.index'))->assertOk();
            DB::getEventDispatcher()->forget('Illuminate\Database\Events\QueryExecuted');

            return $jumlah;
        };

        $tambah(2);
        $this->actingAs($this->pendaki)->get(route('trips.index'));
        $sedikit = $hitung();

        $tambah(10);

        $this->assertSame($sedikit, $hitung(), 'Indeks trip tumbuh bersama jumlah trip.');
    }

    /**
     * Tinjauan Tugas 2 M1: ketiga kueri indeks (belum selesai, selesai terbaru, hitungan) dibatasi
     * pada pembaca, juga untuk trip selesai dan batal milik orang lain.
     */
    public function test_another_hikers_finished_and_cancelled_trips_never_reach_the_index(): void
    {
        $orangLain = User::factory()->create();
        $this->trip('2026-08-01', TripStatus::COMPLETED, $orangLain);
        $this->trip('2026-08-02', TripStatus::CANCELLED, $orangLain);

        $props = $this->props();

        $this->assertNull($props['selesai']);
        $this->assertSame([], $props['kelompok']);
        $this->assertNull($props['utama']);
    }

    /**
     * Tinjauan Tugas 2 M4: kelompok Selesai diurutkan menurut kapan trip berakhir, bukan tanggal
     * rencananya. Rencana Desember yang dibatalkan awal bulan tidak mendahului pendakian yang
     * selesai sesudahnya.
     */
    public function test_finished_trips_are_ordered_by_when_they_ended(): void
    {
        $this->travelTo(Carbon::parse('2026-09-10 03:00:00', 'UTC'));
        $batalDulu = $this->trip('2026-12-20');
        $batalDulu->update(['status' => TripStatus::CANCELLED->value]);
        $this->travelTo(Carbon::parse('2026-09-20 03:00:00', 'UTC'));
        $selesaiBaru = $this->trip('2026-09-19', TripStatus::IN_PROGRESS);
        $selesaiBaru->update(['status' => TripStatus::COMPLETED->value, 'completed_at' => now()]);
        $this->travelTo(Carbon::parse('2026-09-25 03:00:00', 'UTC'));

        $this->assertSame([$selesaiBaru->id, $batalDulu->id], $this->idPerKelompok($this->props())['selesai']);
    }

    /**
     * Tinjauan Tugas 2 M6: indeks tidak memuat trip yang belum selesai tanpa batas; bila
     * terpotong, halaman menyebutnya.
     */
    public function test_the_unfinished_trips_are_capped_and_the_cap_is_stated(): void
    {
        $jalur = Trail::factory()->published()->create();
        TripPlan::factory()->count(IndeksTrip::BATAS_BERJALAN + 1)->create([
            'user_id' => $this->pendaki->id,
            'trail_id' => $jalur->id,
            'planned_date' => '2026-10-10',
        ]);

        $props = $this->props();
        $tampil = 1 + count(collect($props['kelompok'])->firstWhere('kunci', 'mendatang')['trip']);

        $this->assertSame(IndeksTrip::BATAS_BERJALAN, $tampil);
        $this->assertNotNull($props['terpotong']);
    }

    /**
     * Tinjauan Tugas 2 M7: peringatan kesiapan kartu dibaca dari langkah Cek yang sama dengan
     * ruang kerja, bukan diturunkan ulang.
     */
    public function test_the_card_readiness_reads_the_check_step(): void
    {
        $trip = $this->trip('2026-10-10');
        ReadinessCheck::create(['trip_plan_id' => $trip->id, 'computed_state' => ReadinessState::NEEDS_PREPARATION->value, 'official_status_snapshot' => ['status' => 'UNKNOWN'], 'computed_at' => now()]);
        $basi = $this->trip('2026-10-20');
        ReadinessCheck::create(['trip_plan_id' => $basi->id, 'computed_state' => ReadinessState::READY->value, 'official_status_snapshot' => ['status' => 'OPEN'], 'computed_at' => now()]);

        $props = $this->props();
        $kartuBasi = collect($props['kelompok'])->firstWhere('kunci', 'mendatang')['trip'][0];

        $this->assertSame(['teks' => 'Kesiapan: '.ReadinessState::NEEDS_PREPARATION->label(), 'peringatan' => false], $props['utama']['kesiapan']);
        $this->assertSame(['teks' => 'Status jalur berubah, perlu dinilai ulang', 'peringatan' => true], $kartuBasi['kesiapan']);
    }

    /**
     * Tinjauan Tugas 2 M9 dan M10: wilayah kartu utama diberi label menurut kasusnya, judul
     * kartu utama h2 tepat di bawah h1 halaman, dan radius kartu satu kelas per bentuk.
     */
    public function test_the_lead_card_is_labelled_by_its_case_and_sits_at_the_right_heading_level(): void
    {
        $this->trip('2026-10-10');
        $this->assertSame('Trip berikutnya', $this->props()['utamaLabel']);

        $this->trip('2026-09-24', TripStatus::IN_PROGRESS);
        $this->assertSame('Trip berlangsung', $this->props()['utamaLabel']);

        $kartu = File::get(resource_path('js/components/trips/KartuTrip.tsx'));
        $this->assertStringContainsString("const Judul = utama ? 'h2' : 'h3';", $kartu);
        $this->assertStringContainsString("utama ? 'rounded-utama p-5 sm:p-6' : 'rounded-lg p-4'", $kartu);
        $this->assertStringContainsString('aria-label={utamaLabel ?? undefined}', File::get(resource_path('js/pages/Trips/Index.tsx')));
    }

    /**
     * Tinjauan Tugas 2 M11: dua trip berlangsung sama-sama terlihat, dua rencana bertanggal sama
     * diurutkan stabil, dan trip selesai tidak membawa kesiapan maupun kecocokan.
     */
    public function test_two_hikes_in_progress_same_dates_and_finished_cards_without_fit(): void
    {
        $this->pendaki->profile()->create(['experience_level' => 'ADVANCED', 'completed_at' => now()]);
        $satu = $this->trip('2026-09-24', TripStatus::IN_PROGRESS);
        $dua = $this->trip('2026-09-24', TripStatus::IN_PROGRESS);
        $a = $this->trip('2026-10-10');
        $b = $this->trip('2026-10-10');
        $selesai = $this->trip('2026-07-01', TripStatus::COMPLETED);
        ReadinessCheck::create(['trip_plan_id' => $selesai->id, 'computed_state' => ReadinessState::READY->value, 'official_status_snapshot' => ['status' => 'UNKNOWN'], 'computed_at' => now()]);

        $props = $this->props();
        $kartuSelesai = collect($props['kelompok'])->firstWhere('kunci', 'selesai')['trip'][0];

        $this->assertSame($satu->id, $props['utama']['id']);
        $this->assertSame(['berlangsung' => [$dua->id], 'mendatang' => [$a->id, $b->id], 'selesai' => [$selesai->id]], $this->idPerKelompok($props));
        $this->assertNotNull($props['utama']['fit']);
        $this->assertNull($kartuSelesai['kesiapan']);
        $this->assertNull($kartuSelesai['fit']);
    }

    /**
     * Tinjauan Tugas 2 M11: anggaran kueri juga untuk pendaki berprofil dengan trip berlangsung,
     * batal, dan jalur arsip.
     */
    public function test_the_index_does_not_grow_for_a_profiled_hiker_with_every_kind_of_trip(): void
    {
        $this->pendaki->profile()->create(['experience_level' => 'ADVANCED', 'completed_at' => now()]);
        $tambah = function (int $jumlah): void {
            for ($i = 0; $i < $jumlah; $i++) {
                $status = [TripStatus::PLANNED, TripStatus::IN_PROGRESS, TripStatus::CANCELLED, TripStatus::READY_FOR_DEPARTURE][$i % 4];
                $trip = $this->trip('2026-10-1'.($i % 9), $status);
                $this->butir($trip, PreparationStatus::NOT_CONFIRMED);
                ReadinessCheck::create(['trip_plan_id' => $trip->id, 'computed_state' => ReadinessState::NEEDS_PREPARATION->value, 'official_status_snapshot' => ['status' => 'UNKNOWN'], 'pre_departure_confirmed' => $i % 2 === 0, 'computed_at' => now()]);

                if ($i % 3 === 0) {
                    $trip->trail->update(['archived_at' => now()]);
                }
            }
        };
        $hitung = function (): int {
            $jumlah = 0;
            DB::listen(function () use (&$jumlah) {
                $jumlah++;
            });
            $this->actingAs($this->pendaki)->get(route('trips.index'))->assertOk();
            DB::getEventDispatcher()->forget('Illuminate\Database\Events\QueryExecuted');

            return $jumlah;
        };

        $tambah(4);
        $this->actingAs($this->pendaki)->get(route('trips.index'));
        $sedikit = $hitung();
        $tambah(12);

        $this->assertSame($sedikit, $hitung(), 'Indeks trip tumbuh bersama jumlah trip.');
    }

    /**
     * Ruling W4-Fpre (b): "Lihat semua" memuat seluruh trip selesai dan batal milik pembaca saja,
     * 20 per halaman, dan trip batal lama tetap dapat dibuka dan dihapus pemiliknya.
     */
    public function test_the_full_finished_list_is_paged_own_only_and_keeps_old_cancelled_trips_reachable(): void
    {
        $jalur = Trail::factory()->published()->create();
        $batalLama = TripPlan::factory()->create(['user_id' => $this->pendaki->id, 'trail_id' => $jalur->id, 'planned_date' => '2026-01-10', 'status' => TripStatus::CANCELLED->value]);
        $this->travel(1)->minutes();
        TripPlan::factory()->count(21)->create(['user_id' => $this->pendaki->id, 'trail_id' => $jalur->id, 'planned_date' => '2026-08-10', 'status' => TripStatus::CANCELLED->value]);
        $milikLain = TripPlan::factory()->create(['trail_id' => $jalur->id, 'planned_date' => '2026-08-10', 'status' => TripStatus::CANCELLED->value]);

        $halaman1 = $this->actingAs($this->pendaki)->get(route('trips.index', ['selesai' => 'semua']))->assertOk()->viewData('page')['props']['arsip'];
        $halaman2 = $this->actingAs($this->pendaki)->get(route('trips.index', ['selesai' => 'semua', 'page' => 2]))->assertOk()->viewData('page')['props']['arsip'];
        $diLuar = $this->actingAs($this->pendaki)->get(route('trips.index', ['selesai' => 'semua', 'page' => 9]))->assertOk()->viewData('page')['props']['arsip'];
        $semuaId = array_merge(array_column($halaman1['data'], 'id'), array_column($halaman2['data'], 'id'));

        // Halaman di luar penomoran tidak menulis "belum ada" untuk daftar yang ada.
        $this->assertSame([22, []], [$diLuar['total'], $diLuar['data']]);
        $indeks = File::get(resource_path('js/pages/Trips/Index.tsx'));
        $this->assertStringContainsString('{arsip.total === 0 ? (', $indeks);
        $this->assertStringContainsString("indeksTrip.url({ query: { selesai: 'semua' } })", $indeks);
        $this->assertSame(22, $halaman1['total']);
        $this->assertCount(IndeksTrip::PER_HALAMAN_SELESAI, $halaman1['data']);
        $this->assertCount(2, $halaman2['data']);
        $this->assertNotContains($milikLain->id, $semuaId);
        $this->assertSame($batalLama->id, end($halaman2['data'])['id']);
        $this->assertSame(route('trips.show', $batalLama), end($halaman2['data'])['url']);

        $this->actingAs($this->pendaki)->get(route('trips.show', $batalLama))->assertOk();
        $this->actingAs($this->pendaki)->delete(route('trips.destroy', $batalLama))->assertRedirect(route('trips.index'));
        $this->assertModelMissing($batalLama);
    }

    /**
     * Keadaan kosong spec v3 bagian 6.9: menyebut dari mana memulai dan satu aksi utama.
     */
    public function test_an_empty_index_offers_one_way_to_start(): void
    {
        $props = $this->props();

        $this->assertNull($props['utama']);
        $this->assertSame([], $props['kelompok']);

        $halaman = File::get(resource_path('js/pages/Trips/Index.tsx'));

        $this->assertStringContainsString('title="Belum ada trip"', $halaman);
        $this->assertStringContainsString('description="Mulai dari jalur yang Anda simpan atau dari Jelajah."', $halaman);
        $this->assertStringContainsString('Rencanakan trip', $halaman);
    }

    /**
     * Ruling W4-G1 (Delivery Gate G1): kartu utama punya tombol penuh sebagai satu-satunya
     * titik fokus indeks; tombol kepala "Rencanakan trip" tidak lagi bersaing dengannya. Tombol
     * yang sama di keadaan kosong (tanpa kartu utama) tidak diubah: di sana ia satu-satunya aksi.
     */
    public function test_the_header_action_does_not_compete_with_the_lead_cards_focal_button(): void
    {
        $halaman = File::get(resource_path('js/pages/Trips/Index.tsx'));

        $this->assertStringContainsString('<Button href={create.url()} size="sm" variant="secondary">', $halaman);
        $this->assertStringContainsString('<Button href={create.url()}>Rencanakan trip</Button>', $halaman);
    }
}
