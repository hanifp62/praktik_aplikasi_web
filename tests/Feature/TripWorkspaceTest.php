<?php

namespace Tests\Feature;

use App\Enums\CompletionState;
use App\Enums\PreparationCategory;
use App\Enums\PreparationStatus;
use App\Enums\ReadinessState;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Models\HikingHistory;
use App\Models\Mountain;
use App\Models\Profile;
use App\Models\ReadinessCheck;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Models\TripReminder;
use App\Models\User;
use App\Models\UserExperience;
use App\Services\Trips\TripSteps;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Ruang kerja satu trip (spec v3 bagian 6.9, W4 Tugas 1): empat langkah prototype (Jalur,
 * Rencana, Persiapan, Cek) dari TripSteps, kepala trip yang menyebut jenis, sisa hari, dan
 * jam dalam zona gunungnya, serta aksi berikutnya yang dipakai indeks trip.
 *
 * Waktu dibekukan pada 25 September 2026 pukul 10.00 WIB, jadi trip 10 Oktober jatuh pada
 * H-15, sama dengan prototype 09.
 */
class TripWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-09-25 03:00:00', 'UTC'));
    }

    private function trip(array $atribut = [], ?Trail $trail = null): TripPlan
    {
        return TripPlan::factory()->create(array_merge([
            'trail_id' => ($trail ?? Trail::factory()->published()->create())->id,
            'name' => 'Gede bareng Dimas dan Sari',
            'planned_date' => '2026-10-10',
            'end_date' => '2026-10-11',
            'start_time' => '07:00',
            'participants_count' => 3,
            'trip_type' => TripType::CAMPING->value,
            'status' => TripStatus::PLANNED->value,
        ], $atribut));
    }

    private function butir(TripPlan $trip, PreparationStatus $status, bool $kritis = true): TripPreparationItem
    {
        return TripPreparationItem::create([
            'trip_plan_id' => $trip->id,
            'category' => PreparationCategory::EQUIPMENT->value,
            'label' => 'Butir '.fake()->unique()->word(),
            'is_critical' => $kritis,
            'status' => $status->value,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function props(TripPlan $trip): array
    {
        return $this->actingAs($trip->user)->get(route('trips.show', $trip))->assertOk()->viewData('page')['props'];
    }

    public function test_a_guest_is_sent_to_login_and_another_hiker_is_refused(): void
    {
        $trip = $this->trip();

        $this->get(route('trips.show', $trip))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('trips.show', $trip))->assertForbidden();
    }

    /**
     * Prototype 08 sampai 10 memakai empat langkah. "Hike mode" dan "Selesaikan trip" tetap
     * ada sebagai aksi di kartu Tindakan, bukan langkah perencanaan.
     */
    public function test_the_workspace_follows_the_four_prototype_steps(): void
    {
        $props = $this->props($this->trip());

        $this->assertSame(['jalur', 'rencana', 'persiapan', 'kesiapan'], array_column($props['langkah'], 'kunci'));
        $this->assertSame(['Jalur', 'Rencana', 'Persiapan', 'Cek'], array_column($props['langkah'], 'judul'));
    }

    /**
     * Kepala trip prototype 09: jenis, H-n, tanggal dengan kata "sampai", jam dalam zona
     * gunung beserta penandanya, dan peserta. Jam rencana tidak dikonversi: ia rencana
     * pendaki dalam waktu setempat gunungnya.
     */
    public function test_the_header_names_the_type_days_left_and_the_plan_in_the_mountain_zone(): void
    {
        $rinjani = Trail::factory()->for(Mountain::factory()->create(['timezone' => 'Asia/Makassar']))->published()->create();
        $trip = $this->trip([], $rinjani);

        $kepala = $this->props($trip)['kepala'];

        $this->assertSame('Camping', $kepala['jenis']);
        $this->assertSame('H-15', $kepala['hari']);
        $this->assertSame('Sab, 10 Okt sampai Min, 11 Okt 2026', $kepala['tanggal']);
        $this->assertSame('07.00 WITA', $kepala['jam']);
        $this->assertSame('3 orang', $kepala['peserta']);
        $this->assertSame(route('trips.edit', $trip), $kepala['ubah']);
        $this->assertNull($kepala['keadaan']);
    }

    public function test_details_left_empty_stay_empty_in_the_header(): void
    {
        $trip = $this->trip(['end_date' => null, 'start_time' => null, 'participants_count' => null, 'planned_date' => '2026-09-25', 'trip_type' => TripType::TEKTOK->value]);

        $kepala = $this->props($trip)['kepala'];

        $this->assertSame('Tektok', $kepala['jenis']);
        $this->assertSame('Hari H', $kepala['hari']);
        $this->assertSame('Jum, 25 Sep 2026', $kepala['tanggal']);
        $this->assertNull($kepala['jam']);
        $this->assertNull($kepala['peserta']);
    }

    /**
     * R-014: trip yang pre-departure check-nya sudah dikonfirmasi tidak menampilkan kata
     * status "Siap berangkat" di kepala; yang tampil hanya H-n dan rencananya.
     */
    public function test_an_upcoming_trip_never_reads_as_ready_to_go(): void
    {
        $trip = $this->trip(['status' => TripStatus::READY_FOR_DEPARTURE->value]);

        $props = $this->props($trip);

        $this->assertNull($props['kepala']['keadaan']);
        $this->assertStringNotContainsString('Siap berangkat', json_encode($props['kepala']));

        $berlangsung = $this->trip(['status' => TripStatus::IN_PROGRESS->value]);
        $kepala = $this->props($berlangsung)['kepala'];

        $this->assertSame('Sedang berlangsung', $kepala['keadaan']);
        $this->assertNull($kepala['hari'], 'Pendakian yang sudah dimulai tidak menghitung mundur.');
        $this->assertNull($kepala['ubah'], 'Rencana yang sudah dimulai tidak dapat diubah lagi.');
    }

    /**
     * Langkah Jalur menautkan rincian jalurnya selama jalur itu masih terbit; jalur yang
     * ditarik tidak ditautkan ke halaman yang menjawab 404.
     */
    public function test_the_trail_step_links_to_the_trail_only_while_it_is_published(): void
    {
        $trail = Trail::factory()->published()->create();
        $trip = $this->trip([], $trail);

        $this->assertSame(route('trails.show', $trail), $this->props($trip)['langkah'][0]['tautan']['href']);

        $trail->update(['archived_at' => now()]);

        $this->assertNull($this->props($trip)['langkah'][0]['tautan']);
    }

    /**
     * W4-R5: tautan "Lihat penilaian lengkap" hanya ada untuk jalur yang masih terbit; jalur
     * draf atau yang diarsipkan tidak ditautkan ke halaman penilaian yang menjawab 404.
     */
    public function test_the_full_assessment_link_only_exists_while_the_trail_is_published(): void
    {
        $trail = Trail::factory()->published()->create();
        $trip = $this->trip([], $trail);

        $this->assertSame(route('trails.fit', ['trail' => $trail, 'trip' => $trip->id]), $this->props($trip)['penilaian']);

        $draf = $this->trip([], Trail::factory()->create());
        $this->assertNull($this->props($draf)['penilaian']);

        $trail->update(['archived_at' => now()]);
        $this->assertNull($this->props($trip)['penilaian']);
    }

    /**
     * W4-R5: trip lama yang dibuat tanpa hiking_goal_id tetap dinilai, lewat tujuan sementara
     * dari jenis dan tanggal tripnya (TripPlan::tujuanPenilaian()), bukan gagal atau diam-diam
     * dilewati. AmbientFitTest hanya mencakup trip yang punya goal tersimpan.
     */
    public function test_a_trip_without_a_saved_goal_is_still_assessed_with_a_temporary_goal(): void
    {
        $user = User::factory()->create();
        Profile::factory()->for($user)->create();
        UserExperience::factory()->for($user)->create([
            'completed_hikes_count' => 3,
            'highest_elevation_gain_m' => 800,
            'longest_hike_duration_minutes' => 480,
        ]);

        $trip = TripPlan::factory()->for($user->fresh())->create([
            'trail_id' => Trail::factory()->published()->create()->id,
            'trip_type' => TripType::CAMPING->value,
            'planned_date' => '2026-10-10',
        ]);
        $this->assertNull($trip->hiking_goal_id);

        $props = $this->props($trip);

        $this->assertNotNull($props['fit'], 'Trip tanpa goal tersimpan tetap dinilai lewat tujuan sementara, bukan dilewati.');
        $this->assertNotNull($props['penilaian']);
    }

    /**
     * W4-1a: server hanya mengizinkan transisi ke selesai dari trip yang berlangsung
     * (TripStatus::allowedTransitions); kartu "Selesaikan trip" harus mengikuti syarat yang
     * sama, bukan trip.aktif yang masih mencakup trip yang belum dimulai.
     */
    public function test_the_complete_trip_card_only_offers_the_form_once_the_hike_has_started(): void
    {
        $halaman = File::get(resource_path('js/pages/Trips/Show.tsx'));

        preg_match('/\{trip\.(\w+)\s*\?\s*\(\s*<Card title="Selesaikan trip"/', $halaman, $cocok);

        $this->assertSame('berlangsung', $cocok[1] ?? null, 'Kartu Selesaikan trip harus mengikuti trip.berlangsung, bukan trip.aktif.');
    }

    /**
     * Aksi berikutnya (indeks trip, spec v3 bagian 6.9) mengikuti langkah pertama yang belum
     * selesai, atau keadaan pendakiannya.
     */
    public function test_the_next_action_follows_the_first_unfinished_step(): void
    {
        $aksi = function (TripPlan $trip): ?string {
            $trip->load('trail.mountain', 'preparationItems', 'latestReadinessCheck', 'latestConfirmedReadinessCheck', 'history');

            return TripSteps::aksi($trip, app(TripSteps::class)->for($trip))['label'] ?? null;
        };

        $trip = $this->trip();
        $belum = $this->butir($trip, PreparationStatus::NOT_CONFIRMED);
        $this->assertSame('Lanjutkan persiapan', $aksi($trip));

        $belum->update(['status' => PreparationStatus::CONFIRMED->value]);
        $this->assertSame('Cek kesiapan', $aksi($trip->fresh()));

        // Tinjauan tercatat seperti yang ditulis PreDepartureCheck::confirm(): baris
        // terkonfirmasi dan status Siap berangkat (ruling W4-FB, NEW-2).
        ReadinessCheck::create([
            'trip_plan_id' => $trip->id,
            'computed_state' => ReadinessState::READY->value,
            'official_status_snapshot' => ['status' => 'UNKNOWN'],
            'pre_departure_confirmed' => true,
            'computed_at' => now(),
        ]);
        $trip->update(['status' => TripStatus::READY_FOR_DEPARTURE->value]);
        $this->assertSame('Buka ruang kerja', $aksi($trip->fresh()));

        $this->assertSame('Buka mode lapangan', $aksi($this->trip(['status' => TripStatus::IN_PROGRESS->value])));
        $this->assertNull($aksi($this->trip(['status' => TripStatus::CANCELLED->value])));

        $selesai = $this->trip(['status' => TripStatus::COMPLETED->value]);
        HikingHistory::create([
            'user_id' => $selesai->user_id,
            'trip_plan_id' => $selesai->id,
            'trail_id' => $selesai->trail_id,
            'trip_type' => TripType::CAMPING->value,
            'completion_state' => CompletionState::COMPLETED->value,
            'completed_at' => now(),
        ]);
        $this->assertSame('Lihat ringkasan', $aksi($selesai));
    }

    public function test_the_workspace_does_not_grow_with_preparation_items_or_reminders(): void
    {
        $trip = $this->trip();
        $this->actingAs($trip->user);
        $url = route('trips.show', $trip);

        $hitung = function () use ($url): int {
            $jumlah = 0;
            DB::listen(function () use (&$jumlah) {
                $jumlah++;
            });
            $this->get($url)->assertOk();
            DB::getEventDispatcher()->forget('Illuminate\Database\Events\QueryExecuted');

            return $jumlah;
        };

        $this->get($url);
        $sedikit = $hitung();

        for ($i = 0; $i < 12; $i++) {
            $this->butir($trip, $i % 2 ? PreparationStatus::CONFIRMED : PreparationStatus::NOT_CONFIRMED, $i % 3 === 0);
        }
        TripReminder::factory()->for($trip, 'tripPlan')->create(['jenis' => 'H-3']);
        TripReminder::factory()->for($trip, 'tripPlan')->create(['jenis' => 'H-1']);

        $this->assertSame($sedikit, $hitung(), 'Ruang kerja tumbuh bersama jumlah butir atau pengingat.');
    }

    /**
     * Tinjauan Tugas 1 M7: status resmi dibaca sekali untuk kecocokan dan langkah Cek. Vonis
     * tersimpan (yang memicu pemeriksaan vonis basi) tidak menambah kueri status.
     */
    public function test_the_official_status_is_read_once_for_the_fit_and_the_check_step(): void
    {
        $user = User::factory()->create();
        Profile::factory()->for($user)->create();
        $trip = $this->trip(['user_id' => $user->id]);
        $this->actingAs($user);

        $kueriStatus = function () use ($trip): int {
            $jumlah = 0;
            DB::listen(function ($kueri) use (&$jumlah) {
                $jumlah += str_contains($kueri->sql, 'official_statuses') ? 1 : 0;
            });
            $this->get(route('trips.show', $trip))->assertOk();
            DB::getEventDispatcher()->forget('Illuminate\Database\Events\QueryExecuted');

            return $jumlah;
        };

        $tanpaVonis = $kueriStatus();
        ReadinessCheck::create(['trip_plan_id' => $trip->id, 'computed_state' => ReadinessState::NEEDS_PREPARATION->value, 'official_status_snapshot' => ['status' => 'UNKNOWN'], 'computed_at' => now()]);

        $this->assertSame($tanpaVonis, $kueriStatus());
    }

    /**
     * M7 tinjauan akhir W4: kabar dan penolakan aksi ruang kerja dibacakan lewat wilayah yang
     * terpasang sejak halaman dimuat (konvensi DESIGN.md), bukan Alert yang dipasang bersama
     * isinya.
     */
    public function test_the_workspace_feedback_regions_are_always_mounted(): void
    {
        $halaman = File::get(resource_path('js/pages/Trips/Show.tsx'));

        $this->assertStringContainsString("<p role=\"status\" className={flash.status ? 'mb-6", $halaman);
        $this->assertStringContainsString('{flash.status ?? \'\'}', $halaman);
        $this->assertStringContainsString("<p role=\"alert\" className={penolakan ? 'mb-6", $halaman);
        $this->assertStringContainsString('{penolakan ?? \'\'}', $halaman);
        $this->assertStringNotContainsString('<Alert', $halaman);
    }

    /**
     * Ruang kerja dua kolom mulai 1024 px (spec v3 bagian 5.2): langkah di kiri, isi di kanan.
     * Langkah memakai motif garis rute berujung cincin pos (DESIGN.md bagian Motif identitas),
     * bukan panah.
     */
    public function test_the_workspace_page_keeps_the_steps_beside_the_content_on_wide_screens(): void
    {
        $halaman = File::get(resource_path('js/pages/Trips/Show.tsx'));
        $langkah = File::get(resource_path('js/components/trips/LangkahTrip.tsx'));

        $this->assertStringContainsString('lg:grid-cols-[18rem_minmax(0,1fr)]', $halaman);
        $this->assertStringContainsString('<KepalaTrip kepala={kepala} />', $halaman);
        $this->assertStringContainsString('<LangkahTrip langkah={langkah} />', $halaman);
        $this->assertStringNotContainsString('→', $langkah);
        $this->assertStringContainsString("aria-current={l.keadaan === 'berikutnya' ? 'step' : undefined}", $langkah);
    }

    /**
     * Ruling W4-G1 (Delivery Gate G2): "Mulai pendakian" hanya boleh memakai variant primary
     * sejak hari keberangkatan tiba di zona gunung (hari ini di zona gunung >= planned_date).
     * Server menghitungnya lewat Timezone::hariSampai, predikat yang sama dengan
     * KepalaTrip::hariMenuju() dan TripPlan::tanggalRencanaLewat(), bukan logika hari baru.
     *
     * WITA (UTC+8) berganti hari 8 jam lebih awal dari UTC: kasus kedua membuktikan server
     * membaca tanggal zona gunung, bukan tanggal UTC yang belum berganti hari pada jam yang
     * sama.
     */
    public function test_the_departure_day_boolean_follows_the_mountain_zone_not_utc(): void
    {
        $belumTiba = $this->trip();
        $this->assertFalse($this->props($belumTiba)['trip']['hariBerangkat'], 'H-15 bukan hari keberangkatan.');

        // 09 Okt 23.30 UTC = 10 Okt 07.30 WITA: tanggal WITA sudah tanggal rencana meskipun
        // tanggal UTC belum berganti hari.
        $this->travelTo(Carbon::parse('2026-10-09 23:30:00', 'UTC'));
        $rinjani = Trail::factory()->for(Mountain::factory()->create(['timezone' => 'Asia/Makassar']))->published()->create();
        $sudahTiba = $this->trip([], $rinjani);

        $this->assertTrue($this->props($sudahTiba)['trip']['hariBerangkat'], 'Tanggal WITA sudah hari rencana meski UTC belum berganti hari.');
    }

    /**
     * Ruling W4-G1 (Delivery Gate G2): kartu Tindakan dan "Hapus rencana" (terpisah di bawah
     * seperti penghapusan akun) tidak lagi bersaing dengan satu titik fokus. "Mulai pendakian"
     * ganti variant menurut trip.hariBerangkat; "Hapus rencana" bukan lagi tombol danger penuh,
     * dan dialog konfirmasinya tetap memperingatkan bahwa penghapusan tidak dapat dibatalkan.
     */
    public function test_the_start_hike_and_delete_buttons_are_not_second_focal_points(): void
    {
        $halaman = File::get(resource_path('js/pages/Trips/Show.tsx'));
        $this->assertStringContainsString(
            "<Button onClick={mulai} processing={memulai} variant={trip.hariBerangkat ? 'primary' : 'secondary'}>",
            $halaman,
        );

        $hapusRencana = File::get(resource_path('js/components/trips/rencana/HapusRencana.tsx'));
        $this->assertStringContainsString('<Button variant="secondary" className="mt-3" onClick={hapus} processing={menghapus}>', $hapusRencana);
        $this->assertStringContainsString(
            "window.confirm('Hapus rencana trip ini? Daftar persiapan, penilaian kesiapan, dan pengingatnya ikut terhapus dan tidak dapat dikembalikan.')",
            $hapusRencana,
        );
    }
}
