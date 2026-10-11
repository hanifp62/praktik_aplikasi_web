<?php

namespace Tests\Feature;

use App\Enums\HikingSessionStatus;
use App\Enums\TripStatus;
use App\Models\HikeTrackPoint;
use App\Models\HikingSession;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\Trips\TripLifecycle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Perekaman jejak pendakian.
 *
 * Titiknya direkam di perangkat selama pendakian dan dikirim berkelompok sesudah pendakian
 * selesai atau dibatalkan, saat ada sinyal (ruling W6-P2). Di hampir seluruh gunung Indonesia
 * tidak ada sinyal, dan jejak yang hanya berisi titik yang kebetulan terkirim akan menggambar
 * garis lurus melintasi lembah yang tidak pernah dilalui siapa pun.
 *
 * Yang dikunci di sini bukan hanya penyimpanannya, melainkan syaratnya: perekaman harus
 * dinyalakan pemiliknya lebih dulu, pendakiannya sudah berakhir tanpa pendakian lain yang
 * berlangsung (janji persetujuan Profil: tidak ada yang dikirim saat pendaki masih di jalur),
 * jejaknya hanya miliknya, dan ia dapat menghapusnya. Pengirim bertanya lebih dulu lewat
 * hike.track.status, dan unggahan memakai predikat yang sama.
 */
class HikeTrackRecordingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Fixture bawaan: trip COMPLETED dengan sesi COMPLETED, keadaan sesudah pendaki menekan
     * Selesai. Diubah sisipan W6-P2 dengan alasan (butir 1): fixture lama memakai trip bawaan
     * pabrik (PLANNED) dengan sesi ACTIVE, keadaan yang kini ditolak server (ruling W6-P2b,
     * daftar putih COMPLETED atau CANCELLED). Keadaan lain dibuat eksplisit di test-nya sendiri.
     *
     * @return array{0: User, 1: HikingSession}
     */
    private function sesi(bool $merekam = true, TripStatus $statusTrip = TripStatus::COMPLETED, HikingSessionStatus $statusSesi = HikingSessionStatus::COMPLETED): array
    {
        $user = User::factory()->create();
        $user->preference()->create([
            'preferred_duration' => 'ONE_DAY',
            'preferred_trip_type' => 'CAMPING',
            'record_track' => $merekam,
        ]);

        $trip = TripPlan::factory()->create([
            'user_id' => $user->id,
            'trail_id' => Trail::factory()->easy()->create()->id,
            'status' => $statusTrip->value,
        ]);

        $sesi = HikingSession::create([
            'trip_plan_id' => $trip->id,
            'user_id' => $user->id,
            'status' => $statusSesi->value,
            'started_at' => Carbon::parse('2026-09-15 04:00:00'),
            'ended_at' => $statusSesi === HikingSessionStatus::COMPLETED ? Carbon::parse('2026-09-15 12:30:00') : null,
        ]);

        return [$user->fresh(), $sesi];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function titik(int $jumlah = 3): array
    {
        $hasil = [];

        foreach (range(0, $jumlah - 1) as $i) {
            $hasil[] = [
                'recorded_at' => Carbon::parse('2026-09-15 04:00:00')->addMinutes($i * 5)->toIso8601String(),
                'latitude' => -7.45 - $i * 0.001,
                'longitude' => 110.44 + $i * 0.001,
                'elevation_m' => 1200 + $i * 40,
                'accuracy_m' => 8,
            ];
        }

        return $hasil;
    }

    private function kirim(User $user, HikingSession $sesi, array $titik)
    {
        return $this->actingAs($user)->postJson(route('hike.track', $sesi), ['points' => $titik]);
    }

    private function tanya(User $user, HikingSession $sesi)
    {
        return $this->actingAs($user)->getJson(route('hike.track.status', $sesi));
    }

    public function test_a_batch_of_points_is_stored(): void
    {
        [$user, $sesi] = $this->sesi();

        $this->kirim($user, $sesi, $this->titik())->assertOk()->assertExactJson(['tersimpan' => 3]);

        $this->assertSame(3, HikeTrackPoint::where('hiking_session_id', $sesi->id)->count());
    }

    /**
     * Waktu perangkat merekam, bukan waktu server menerima. Keduanya dapat berjarak
     * berjam-jam karena jejaknya dikirim setelah turun, dan yang membentuk garisnya
     * adalah yang pertama.
     */
    public function test_the_device_time_is_kept_not_the_arrival_time(): void
    {
        [$user, $sesi] = $this->sesi();

        Carbon::setTestNow(Carbon::parse('2026-09-15 20:00:00'));
        $this->kirim($user, $sesi, $this->titik(1))->assertOk();
        Carbon::setTestNow();

        $this->assertSame(
            '2026-09-15 04:00:00',
            HikeTrackPoint::firstOrFail()->recorded_at->format('Y-m-d H:i:s')
        );
    }

    /**
     * Sinyal dapat putus di tengah unggahan, dan pengiriman ulang potongan yang sama
     * tidak boleh menggandakan jejaknya.
     */
    public function test_sending_the_same_batch_twice_does_not_double_the_track(): void
    {
        [$user, $sesi] = $this->sesi();

        $this->kirim($user, $sesi, $this->titik())->assertOk();
        $this->kirim($user, $sesi, $this->titik())->assertOk();

        $this->assertSame(3, HikeTrackPoint::where('hiking_session_id', $sesi->id)->count());
    }

    /**
     * Janji persetujuan Profil (ruling W6-P2): tidak ada yang dikirim saat pendaki masih di jalur.
     * Klien lama yang masih mengirim selama pendakian ditolak tanpa satu baris pun tersimpan;
     * titiknya tetap di perangkat dan dikirim pengirim baru sesudah pendakian berakhir.
     */
    public function test_points_sent_during_the_hike_are_refused_with_409(): void
    {
        [$user, $sesi] = $this->sesi(statusTrip: TripStatus::IN_PROGRESS, statusSesi: HikingSessionStatus::ACTIVE);

        $this->kirim($user, $sesi, $this->titik())
            ->assertStatus(409)
            ->assertJson(['message' => 'Pendakian ini masih berlangsung. Jejaknya diterima sesudah pendakian selesai atau dibatalkan.']);

        $this->assertSame(0, HikeTrackPoint::count());
    }

    /**
     * Fixture ini membuat sesi langsung (bukan lewat TripLifecycle::cancel()), jadi statusnya
     * tetap ACTIVE walau tripnya CANCELLED: sejak ruling W6-FT, cancel() yang sungguhan menutup
     * sesi (ended_at terisi, status TETAP ACTIVE atau PAUSED, tidak pernah berubah), sehingga
     * kombinasi ini kini defensif, bukan alur normal. Yang menentukan penerimaan tetap status
     * TRIP, bukan status atau ended_at sesi: pendakian yang dibatalkan sudah berakhir, dan
     * jejaknya diterima (ruling W6-P2j), terlepas dari apakah sesi punya ended_at.
     */
    public function test_a_cancelled_trip_whose_session_is_still_active_accepts_its_track(): void
    {
        [$user, $sesi] = $this->sesi(statusTrip: TripStatus::CANCELLED, statusSesi: HikingSessionStatus::ACTIVE);

        $this->tanya($user, $sesi)->assertOk()->assertExactJson(['kirim' => true, 'alasan' => null]);
        $this->kirim($user, $sesi, $this->titik())->assertOk();

        $this->assertSame(3, HikeTrackPoint::where('hiking_session_id', $sesi->id)->count());
    }

    /**
     * Ruling W6-FH (M-5 tinjauan akhir W6): shell luring memakai rekaman sesi di perangkat yang
     * masih berkata pendakian berlangsung sesudah Selesai, jadi perekamnya dapat terus berjalan.
     * Titik yang direkam lebih dari kelonggaran jam perangkat (10 menit) sesudah ended_at 12.30 tidak
     * disimpan; titik di dalam kelonggaran, termasuk tepat di batasnya, tetap tersimpan. Jawabannya
     * tetap 200, jadi perangkat membuang kelompok itu seperti biasa.
     */
    public function test_points_recorded_after_the_hike_was_finished_are_not_kept(): void
    {
        [$user, $sesi] = $this->sesi();
        $titik = fn (string $waktu): array => ['recorded_at' => $waktu, 'latitude' => -7.45, 'longitude' => 110.44, 'elevation_m' => 1200, 'accuracy_m' => 8];

        $this->kirim($user, $sesi, [
            $titik('2026-09-15T12:20:00Z'),
            $titik('2026-09-15T12:35:00Z'),
            $titik('2026-09-15T12:40:00Z'),
            $titik('2026-09-15T12:40:01Z'),
            $titik('2026-09-15T14:00:00Z'),
        ])->assertOk()->assertExactJson(['tersimpan' => 3]);

        $this->assertSame(
            ['2026-09-15 12:20:00', '2026-09-15 12:35:00', '2026-09-15 12:40:00'],
            DB::table('hike_track_points')->where('hiking_session_id', $sesi->id)->orderBy('recorded_at')->pluck('recorded_at')->map(fn ($waktu) => substr((string) $waktu, 0, 19))->all(),
        );
    }

    /**
     * Ruling W6-FH: kiriman yang seluruh titiknya sesudah batas tetap dijawab 200, tanpa titik baru.
     */
    public function test_a_batch_wholly_after_the_hike_is_answered_without_storing(): void
    {
        [$user, $sesi] = $this->sesi();

        $this->kirim($user, $sesi, [['recorded_at' => '2026-09-15T15:00:00Z', 'latitude' => -7.45, 'longitude' => 110.44]])
            ->assertOk()
            ->assertExactJson(['tersimpan' => 0]);
    }

    /**
     * Ruling W6-FT (penutupan OPEN ISSUE W6-FH "jejak sesudah batal"): TripLifecycle::cancel()
     * kini menutup sesi (ended_at terisi, pola complete()), jadi saringan W6-FH yang sudah ada
     * berlaku juga untuk pendakian yang dibatalkan, tanpa PenerimaanJejak::dalamPendakian diubah.
     * Titik lebih dari 10 menit sesudah ended_at tidak tersimpan; titik di dalam kelonggaran,
     * termasuk tepat di batasnya, tetap tersimpan.
     */
    public function test_points_recorded_after_a_cancelled_hike_was_closed_are_not_kept(): void
    {
        $trip = TripPlan::factory()->create(['status' => TripStatus::PLANNED->value]);
        $trip->user->preference()->create(['preferred_duration' => 'ONE_DAY', 'preferred_trip_type' => 'CAMPING', 'record_track' => true]);
        $this->assertNull(app(TripLifecycle::class)->start($trip));

        $this->travelTo(Carbon::parse('2026-09-15 12:30:00', 'UTC'));
        $this->assertNull(app(TripLifecycle::class)->cancel($trip->fresh()));
        $this->assertSame('2026-09-15 12:30:00', $trip->fresh()->hikingSession->ended_at?->utc()->format('Y-m-d H:i:s'));

        $sesi = $trip->fresh()->hikingSession;
        $titik = fn (string $waktu): array => ['recorded_at' => $waktu, 'latitude' => -7.45, 'longitude' => 110.44, 'elevation_m' => 1200, 'accuracy_m' => 8];

        $this->kirim($sesi->user, $sesi, [
            $titik('2026-09-15T12:20:00Z'),
            $titik('2026-09-15T12:40:00Z'),
            $titik('2026-09-15T12:40:01Z'),
        ])->assertOk()->assertExactJson(['tersimpan' => 2]);

        $this->assertSame(
            ['2026-09-15 12:20:00', '2026-09-15 12:40:00'],
            DB::table('hike_track_points')->where('hiking_session_id', $sesi->id)->orderBy('recorded_at')->pluck('recorded_at')->map(fn ($waktu) => substr((string) $waktu, 0, 19))->all(),
        );
    }

    /**
     * Ruling W6-FH: sesi tanpa ended_at tidak disaring (R-007). Sejak ruling W6-FT setiap sesi
     * yang mencapai trip CANCELLED sudah ditutup lewat TripLifecycle::cancel(), jadi fixture ini
     * membuat sesi langsung (bukan lewat cancel()) untuk menjaga cabang defensif ini: baris yang
     * entah bagaimana tidak punya ended_at (data lama, atau diubah langsung di luar
     * TripLifecycle) tetap menerima semua titik, bukan menolaknya sebagai "sesudah batas".
     */
    public function test_a_session_without_an_end_time_is_not_filtered(): void
    {
        [$user, $sesi] = $this->sesi(statusTrip: TripStatus::CANCELLED, statusSesi: HikingSessionStatus::ACTIVE);

        $this->kirim($user, $sesi, [['recorded_at' => '2026-09-15T20:00:00Z', 'latitude' => -7.45, 'longitude' => 110.44]])
            ->assertOk()
            ->assertExactJson(['tersimpan' => 1]);
    }

    /**
     * Pendakian yang sudah berakhir tetap menunggu bila pendaki sedang di jalur pendakian lain:
     * tidak ada titik yang terkirim selama ia mendaki, termasuk titik pendakian lama.
     */
    public function test_a_finished_hike_is_refused_while_another_hike_is_in_progress(): void
    {
        [$user, $sesi] = $this->sesi();
        TripPlan::factory()->create(['user_id' => $user->id, 'status' => TripStatus::IN_PROGRESS->value]);

        $this->kirim($user, $sesi, $this->titik())
            ->assertStatus(409)
            ->assertJson(['message' => 'Pendakian lain masih berlangsung. Jejak diterima sesudah pendakian itu selesai atau dibatalkan.']);

        $this->assertSame(0, HikeTrackPoint::count());
    }

    /**
     * Default perekaman mati. Fitur yang menyala sendiri berarti pendaki menyerahkan
     * riwayat lokasinya tanpa pernah memutuskan.
     */
    public function test_nothing_is_stored_when_the_hiker_never_turned_recording_on(): void
    {
        [$user, $sesi] = $this->sesi(merekam: false);

        $this->kirim($user, $sesi, $this->titik())
            ->assertForbidden()
            ->assertJson(['message' => 'Perekaman jejak sedang mati, jadi jejak tidak diterima.']);

        $this->assertSame(0, HikeTrackPoint::count());
    }

    /**
     * Pertanyaan pengirim per cabang (ruling W6-P2b): jawabannya hanya keputusan dan alasannya,
     * tanpa data lokasi, dengan predikat yang sama dengan unggahan.
     */
    public function test_the_question_answers_each_branch_without_location(): void
    {
        [$user, $sesi] = $this->sesi();
        $this->tanya($user, $sesi)->assertOk()->assertExactJson(['kirim' => true, 'alasan' => null]);

        [$mati, $sesiMati] = $this->sesi(merekam: false);
        $this->tanya($mati, $sesiMati)->assertOk()->assertExactJson(['kirim' => false, 'alasan' => 'rekam-mati']);

        [$diJalur, $sesiDiJalur] = $this->sesi(statusTrip: TripStatus::IN_PROGRESS, statusSesi: HikingSessionStatus::ACTIVE);
        $this->tanya($diJalur, $sesiDiJalur)->assertOk()->assertExactJson(['kirim' => false, 'alasan' => 'belum-berakhir']);

        [$mendakiLagi, $sesiLama] = $this->sesi();
        TripPlan::factory()->create(['user_id' => $mendakiLagi->id, 'status' => TripStatus::IN_PROGRESS->value]);
        $this->tanya($mendakiLagi, $sesiLama)->assertOk()->assertExactJson(['kirim' => false, 'alasan' => 'pendakian-lain']);
    }

    /**
     * Sesi akun lain (titik basis data jejak lama tanpa pemilik, ruling W6-11e) dijawab 403
     * sebelum predikat dibaca, dan sesi yang tidak ada 404.
     */
    public function test_the_question_refuses_other_hikers_and_missing_sessions(): void
    {
        [, $sesi] = $this->sesi();

        $penyusup = User::factory()->create();
        $penyusup->preference()->create(['preferred_duration' => 'ONE_DAY', 'record_track' => true]);

        $this->tanya($penyusup->fresh(), $sesi)->assertForbidden();
        $this->actingAs($penyusup)->getJson(route('hike.track.status', ['session' => $sesi->id + 999]))->assertNotFound();
    }

    /**
     * Pertanyaan yang sudah diizinkan tidak menyimpan apa pun.
     */
    public function test_the_question_never_writes_points(): void
    {
        [$user, $sesi] = $this->sesi();

        $this->tanya($user, $sesi)->assertOk();

        $this->assertSame(0, HikeTrackPoint::count());
    }

    public function test_the_default_preference_is_off(): void
    {
        $user = User::factory()->create();
        $user->preference()->create(['preferred_duration' => 'ONE_DAY']);

        $this->assertFalse((bool) $user->fresh()->preference->record_track);
    }

    public function test_a_hiker_cannot_write_into_someone_elses_track(): void
    {
        [, $sesi] = $this->sesi();

        $penyusup = User::factory()->create();
        $penyusup->preference()->create(['preferred_duration' => 'ONE_DAY', 'record_track' => true]);

        $this->kirim($penyusup->fresh(), $sesi, $this->titik())->assertForbidden();

        $this->assertSame(0, HikeTrackPoint::count());
    }

    /**
     * Koordinat datang dari perangkat dan tidak boleh dipercaya begitu saja.
     */
    public function test_an_impossible_coordinate_is_refused(): void
    {
        [$user, $sesi] = $this->sesi();

        $rusak = $this->titik(1);
        $rusak[0]['latitude'] = 95.0;

        $this->kirim($user, $sesi, $rusak)->assertStatus(422);
        $this->assertSame(0, HikeTrackPoint::count());
    }

    /**
     * Batas akurasi dan ketinggian diterima tepat di batasnya dan ditolak sesudahnya. Pengirim
     * menyaring dengan nilai yang sama (ruling W6-P2e, PengirimJejakTest), supaya satu titik tak
     * sah tidak menahan seluruh jejak sesi itu.
     */
    public function test_accuracy_and_elevation_limits_hold_at_their_edges(): void
    {
        [$user, $sesi] = $this->sesi();

        $tepat = $this->titik(1);
        $tepat[0]['accuracy_m'] = 10000;
        $tepat[0]['elevation_m'] = 9000;
        $this->kirim($user, $sesi, $tepat)->assertOk();

        foreach ([['accuracy_m', 10001], ['elevation_m', 9001], ['elevation_m', -501]] as [$kolom, $nilai]) {
            $lewat = $this->titik(1);
            $lewat[0][$kolom] = $nilai;
            $this->kirim($user, $sesi, $lewat)->assertStatus(422);
        }
    }

    /**
     * Satu unggahan tidak boleh menjadi pintu menulis berkas raksasa ke basis data.
     */
    public function test_an_oversized_batch_is_refused(): void
    {
        [$user, $sesi] = $this->sesi();

        $this->kirim($user, $sesi, $this->titik(1))->assertOk();
        $this->kirim($user, $sesi, $this->titik(5001))->assertStatus(422);
    }

    /**
     * Kunci titik adalah detik UTC (ruling W6-P2c): dua titik dalam satu detik di satu kiriman
     * menjadi satu baris, dan yang terakhir di kiriman menang. Di PostgreSQL, dua baris berkunci
     * sama dalam satu upsert menolak seluruh kiriman (R-024; tests/Spatial/HikeTrackUpsertTest).
     */
    public function test_two_points_within_one_second_become_one_row(): void
    {
        [$user, $sesi] = $this->sesi();

        $this->kirim($user, $sesi, [
            ['recorded_at' => '2026-09-15T04:00:00.100Z', 'latitude' => -7.45, 'longitude' => 110.44, 'elevation_m' => 1200, 'accuracy_m' => 8],
            ['recorded_at' => '2026-09-15T04:00:00.300Z', 'latitude' => -7.46, 'longitude' => 110.45, 'elevation_m' => 1210, 'accuracy_m' => 6],
        ])->assertOk()->assertExactJson(['tersimpan' => 1]);

        $baris = HikeTrackPoint::where('hiking_session_id', $sesi->id)->sole();

        $this->assertSame('2026-09-15 04:00:00', $baris->recorded_at->format('Y-m-d H:i:s'));
        $this->assertEqualsWithDelta(-7.46, (float) $baris->latitude, 0.0000001);
    }

    /**
     * Waktu berzona selain UTC disimpan sebagai instan UTC-nya (butir 6, pola M-6 HikeModeController):
     * tanpa ->utc(), binding PDO memformat jam dinding 11.00 apa adanya.
     */
    public function test_an_offset_time_is_stored_as_its_utc_instant(): void
    {
        [$user, $sesi] = $this->sesi();

        $titik = $this->titik(1);
        $titik[0]['recorded_at'] = '2026-09-15T11:00:00+07:00';

        $this->kirim($user, $sesi, $titik)->assertOk();

        $this->assertSame('2026-09-15 04:00:00', DB::table('hike_track_points')->value('recorded_at'));
    }

    /**
     * Trip yang dihapus sesudah pengikatan rute, sebelum otorisasi (hanya trip batal yang dapat
     * dihapus; sesinya ikut terhapus lewat kaskade), dijawab 404 di ketiga aksi, bukan 403 dari
     * Policy yang menerima trip null (ruling W6-P2r). Penghapusan disisipkan lewat kait retrieved
     * sesi, sesudah model dimuat pengikatan rute.
     *
     * Diubah putaran perbaikan 1 W6-P2 dengan alasan (tinjauan m-3): kait lama hanya menghapus baris
     * sesi, jadi tidak pernah meniru kaskade trip yang nyata, yang dulu menjawab 403.
     */
    public function test_a_trip_deleted_after_route_binding_is_a_404_in_every_action(): void
    {
        [$user, $sesiKirim] = $this->sesi(statusTrip: TripStatus::CANCELLED, statusSesi: HikingSessionStatus::ACTIVE);
        [$penanya, $sesiTanya] = $this->sesi(statusTrip: TripStatus::CANCELLED, statusSesi: HikingSessionStatus::ACTIVE);
        [$penghapus, $sesiHapus] = $this->sesi(statusTrip: TripStatus::CANCELLED, statusSesi: HikingSessionStatus::ACTIVE);

        HikingSession::retrieved(function (HikingSession $dimuat): void {
            DB::table('trip_plans')->where('id', $dimuat->trip_plan_id)->delete();
        });

        $this->kirim($user, $sesiKirim, $this->titik())
            ->assertNotFound()
            ->assertJson(['message' => 'Sesi pendakian ini sudah tidak ada.']);
        $this->tanya($penanya, $sesiTanya)
            ->assertNotFound()
            ->assertJson(['message' => 'Sesi pendakian ini sudah tidak ada.']);
        $this->actingAs($penghapus)->deleteJson(route('hike.track.destroy', $sesiHapus))
            ->assertNotFound()
            ->assertJson(['message' => 'Sesi pendakian ini sudah tidak ada.']);

        $this->assertSame(0, HikingSession::query()->count(), 'Kaskade trip ikut menghapus sesinya.');
        $this->assertSame(0, HikeTrackPoint::count());
    }

    /**
     * Trip yang dihapus sesudah otorisasi, sebelum unggahan menulis, dijawab 404, bukan 500 dari
     * kunci asing (butir 4): baris sesi dibaca ulang dan dikunci di dalam transaksi. Penghapusan
     * disisipkan lewat kait retrieved trip, yang berjalan saat otorisasi memuat trip sesi itu.
     */
    public function test_a_trip_deleted_after_authorisation_is_a_404_not_a_500(): void
    {
        [$user, $sesi] = $this->sesi(statusTrip: TripStatus::CANCELLED, statusSesi: HikingSessionStatus::ACTIVE);

        TripPlan::retrieved(function (TripPlan $dimuat): void {
            DB::table('trip_plans')->where('id', $dimuat->id)->delete();
        });

        $this->kirim($user, $sesi, $this->titik())
            ->assertNotFound()
            ->assertJson(['message' => 'Sesi pendakian ini sudah tidak ada.']);

        $this->assertSame(0, HikeTrackPoint::count());
    }

    /**
     * Pertanyaan punya kunci batas laju sendiri (ruling W6-P2d): menghabiskannya tidak memakan
     * jatah unggahan.
     */
    public function test_the_question_has_its_own_rate_limit(): void
    {
        [$user, $sesi] = $this->sesi();
        $batas = (int) config('hiking.rate_limits.personal_writes_per_minute');

        for ($i = 0; $i < $batas; $i++) {
            $this->tanya($user, $sesi)->assertOk();
        }

        $this->tanya($user, $sesi)->assertStatus(429);
        $this->kirim($user, $sesi, $this->titik(1))->assertOk();
    }

    /**
     * Unggahan dan hapus berbagi kunci batas laju `jejak` (ruling W6-P2d, rencana W9 Tugas 5):
     * sesudah jatahnya habis oleh unggahan, hapus pun ditolak 429.
     */
    public function test_uploads_and_deletions_share_one_rate_limit(): void
    {
        [$user, $sesi] = $this->sesi();
        $batas = (int) config('hiking.rate_limits.personal_writes_per_minute');

        for ($i = 0; $i < $batas; $i++) {
            $this->kirim($user, $sesi, $this->titik(1))->assertOk();
        }

        $this->kirim($user, $sesi, $this->titik(1))->assertStatus(429);
        $this->actingAs($user)->delete(route('hike.track.destroy', $sesi))->assertStatus(429);
        $this->assertSame(1, HikeTrackPoint::where('hiking_session_id', $sesi->id)->count());
    }

    /**
     * Hak menghapus adalah bagian dari fiturnya, bukan tambahan. Jejaknya milik
     * pendakinya, dan ia harus dapat mencabutnya kembali.
     */
    public function test_the_hiker_can_delete_their_own_track(): void
    {
        [$user, $sesi] = $this->sesi();
        $this->kirim($user, $sesi, $this->titik())->assertOk();

        $this->actingAs($user)->delete(route('hike.track.destroy', $sesi))->assertRedirect();

        $this->assertSame(0, HikeTrackPoint::count());
    }

    public function test_one_hiker_cannot_delete_another_hikers_track(): void
    {
        [$user, $sesi] = $this->sesi();
        $this->kirim($user, $sesi, $this->titik())->assertOk();

        $this->actingAs(User::factory()->create())
            ->delete(route('hike.track.destroy', $sesi))
            ->assertForbidden();

        $this->assertSame(3, HikeTrackPoint::count());
    }

    /**
     * Menghapus akun menghapus jejaknya. Cascade sudah dipasang di migrasi, dan test
     * ini yang menjaganya tetap ada.
     */
    public function test_deleting_the_account_takes_the_track_with_it(): void
    {
        [$user, $sesi] = $this->sesi();
        $this->kirim($user, $sesi, $this->titik())->assertOk();

        $user->delete();

        $this->assertSame(0, HikeTrackPoint::count());
    }
}
