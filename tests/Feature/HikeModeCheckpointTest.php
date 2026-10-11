<?php

namespace Tests\Feature;

use App\Enums\HikingSessionStatus;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Models\Checkpoint;
use App\Models\HikeTrackPoint;
use App\Models\HikingSession;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\Trips\HalamanMendaki;
use App\Services\Trips\TripLifecycle;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Mode lapangan di server (spec v3 bagian 6.12; ruling W6-R5 sampai W6-R7).
 *
 * Sejak W6 posisi pendaki tidak dikirim ke server (PRD §82, §145.1): pos berikutnya, jarak,
 * dan tiba di pos dihitung di perangkat (FieldModeFiguresTest). Niat test lama tetap dijaga di
 * sini pada bentuk barunya: pos yang dicapai tercatat dan tidak pernah mundur (F-09, F-17),
 * masukan di luar jalur ditolak, pos tanpa koordinat diakui apa adanya, dan pengguna lain
 * ditolak. Rute posisi lama trips.hike.position dihapus beserta permintaannya.
 */
class HikeModeCheckpointTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Alamat aksi kini relatif (ruling W6-8l, tinjauan Tugas 8 m-2): bentuk yang sama disimpan
     * paket lapangan, jadi versi paket tidak bergantung pada skema dan host. Asersi lama
     * membandingkannya dengan route() absolut.
     */
    public function test_the_page_carries_the_trail_data_and_no_position(): void
    {
        $trip = $this->tripWithCheckpoints();

        $this->actingAs($trip->user)->get(route('trips.hike', $trip))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Trips/Hike')
                ->where('title', 'Mode lapangan')
                ->where('trip.berlangsung', true)
                ->where('trip.jedaDetik', 0)
                ->has('pos', 4)
                ->where('pos.2.urutan', 3)
                ->where('pos.2.nama', 'Pos 3')
                ->where('pos.2.lat', -7.993)
                ->where('tercapai', 0)
                ->where('radiusTiba', 75)
                ->where('sumber', 'daring')
                ->where('urls', [
                    'tiba' => route('trips.hike.arrive', $trip, absolute: false),
                    'jeda' => route('trips.hike.pause', $trip, absolute: false),
                    'lanjut' => route('trips.hike.resume', $trip, absolute: false),
                    'selesai' => route('trips.complete', $trip, absolute: false),
                    'ruangKerja' => route('trips.show', $trip, absolute: false),
                    'jejak' => route('hike.track', $trip->hikingSession, absolute: false),
                ])
                ->where('urls.tiba', '/trips/'.$trip->id.'/hike/tiba')
                ->missing('posisi')
                ->missing('berikutnya'));
    }

    public function test_arriving_records_the_checkpoint_and_aims_at_the_next(): void
    {
        $trip = $this->tripWithCheckpoints();

        $this->tiba($trip, 3)->assertNoContent();

        $sesi = $trip->hikingSession->fresh();

        $this->assertSame(3, $sesi->reached_checkpoint_sequence);
        $this->assertSame('Pos 4', $sesi->currentCheckpoint->name);
    }

    /**
     * Antrean perangkat dapat memutar ulang tiba di pos yang lebih awal sesudah yang lebih
     * akhir tercatat. Pos yang sudah dicapai tidak menjadi belum dicapai.
     */
    public function test_progress_is_not_lost_when_an_earlier_arrival_is_replayed(): void
    {
        $trip = $this->tripWithCheckpoints();

        $this->tiba($trip, 3);
        $this->tiba($trip, 1)->assertNoContent();

        $this->assertSame(3, $trip->hikingSession->fresh()->reached_checkpoint_sequence);
    }

    public function test_a_reload_keeps_the_recorded_progress(): void
    {
        $trip = $this->tripWithCheckpoints();
        $this->tiba($trip, 2);

        $this->get(route('trips.hike', $trip))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('tercapai', 2));
    }

    public function test_a_checkpoint_outside_the_trail_is_rejected(): void
    {
        $trip = $this->tripWithCheckpoints();
        $lain = Checkpoint::factory()->create(['sequence' => 9]);

        $this->tiba($trip, 9)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['urutan' => 'Pos itu tidak ada di jalur trip ini.']);

        $this->assertNull($trip->hikingSession->fresh()->reached_checkpoint_sequence);
        $this->assertNotSame($trip->trail_id, $lain->trail_id);
    }

    /**
     * Tanpa koordinat pos, data apa adanya: koordinatnya null dan perangkat tidak menebak
     * jarak (hitung.ts menulis "pos tanpa koordinat"). Tiba tetap dapat ditandai sendiri.
     */
    public function test_checkpoints_without_coordinates_are_admitted_not_guessed(): void
    {
        $trip = $this->tripWithCheckpoints(berkoordinat: false);

        $this->actingAs($trip->user)->get(route('trips.hike', $trip))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('pos.0.lat', null)
                ->where('pos.0.lng', null)
                ->where('garis', null));

        $this->tiba($trip, 1)->assertNoContent();
    }

    public function test_another_user_cannot_record_an_arrival(): void
    {
        $trip = $this->tripWithCheckpoints();

        $this->actingAs(User::factory()->create())
            ->postJson(route('trips.hike.arrive', $trip), ['urutan' => 2])
            ->assertForbidden();

        $this->assertNull($trip->hikingSession->fresh()->reached_checkpoint_sequence);
    }

    /**
     * Posisi tidak pernah sampai ke server lagi: rutenya tidak ada, dan tiba di pos menolak
     * payload yang membawa koordinat alih-alih menerimanya dan diam-diam mengabaikannya
     * (I-3/ruling W6-3c, pertahanan berlapis W6-R5). Klien yang patuh tidak pernah mengirim
     * kunci ini (lihat test_arrival_payload_structurally_excludes_position di
     * LocationPrivacyTest); 422 di sini menutup klien lama atau yang diubah.
     */
    public function test_no_position_reaches_the_server(): void
    {
        $trip = $this->tripWithCheckpoints();

        $this->assertFalse(Route::has('trips.hike.position'));

        $this->actingAs($trip->user)
            ->postJson(route('trips.hike.arrive', $trip), ['urutan' => 2, 'latitude' => -7.99, 'longitude' => 112.95])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['latitude']);

        $this->assertNull($trip->hikingSession->fresh()->location_updated_at);
    }

    /**
     * Padanan asersi lama yang hilang saat test di atas diubah ekspektasinya ke 422
     * (N-1/ruling W6-3j): tiba yang SAH (tanpa kunci posisi) tidak menyentuh kolom lokasi
     * sesi. Mutan yang kembali menulis location_updated_at saat tiba sah harus merah di sini,
     * karena test di atas tidak lagi menjalankan controller sama sekali untuk payload itu.
     */
    public function test_a_valid_arrival_does_not_touch_the_session_location_columns(): void
    {
        $trip = $this->tripWithCheckpoints();

        $this->tiba($trip, 2)->assertNoContent();

        $this->assertNull($trip->hikingSession->fresh()->location_updated_at);
    }

    /**
     * Daftar putih kunci (N-2/ruling W6-3k): hanya `urutan` diterima pada tiba. Kunci lain
     * apa pun ditolak 422, termasuk yang bersarang, berbahasa Indonesia, berhuruf besar, atau
     * bernilai 0 (bukan kosong). `latitude: null` dan `lat` kosong tidak membawa data, jadi
     * tanpa dampak pada R-005/R-007, dan tetap diterima.
     *
     * @param  array<string, mixed>  $tambahan
     */
    #[DataProvider('kunciTambahanTiba')]
    public function test_arrival_rejects_every_key_outside_the_whitelist(array $tambahan, int $harapan): void
    {
        $trip = $this->tripWithCheckpoints();

        $this->actingAs($trip->user)
            ->postJson(route('trips.hike.arrive', $trip), ['urutan' => 2, ...$tambahan])
            ->assertStatus($harapan);

        $this->assertNull($trip->hikingSession->fresh()->location_updated_at);
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: int}>
     */
    public static function kunciTambahanTiba(): array
    {
        return [
            'latitude' => [['latitude' => -7.99], 422],
            'lat' => [['lat' => -7.99], 422],
            'accuracy' => [['accuracy' => 12], 422],
            'latitude nol (bukan kosong)' => [['latitude' => 0], 422],
            'posisi bersarang' => [['posisi' => ['lat' => -7.99, 'lng' => 112.95]], 422],
            'akurasi nama Indonesia' => [['akurasi' => 12], 422],
            'koordinat' => [['koordinat' => [-7.99, 112.95]], 422],
            'coords bersarang' => [['coords' => ['latitude' => -7.99, 'longitude' => 112.95]], 422],
            'LATITUDE huruf besar' => [['LATITUDE' => -7.99], 422],
            'latitude null (tanpa data)' => [['latitude' => null], 204],
        ];
    }

    /**
     * Daftar putih pada jeda juga menolak lat/lng di query string (N-2/ruling W6-3k):
     * Illuminate\Http\Request::all() menggabungkan badan permintaan dan query string.
     */
    public function test_pause_rejects_a_key_in_the_query_string(): void
    {
        $trip = $this->tripWithCheckpoints();

        $this->actingAs($trip->user)->postJson(route('trips.hike.pause', $trip).'?lat=-7.99')->assertUnprocessable();
        $this->assertNull($trip->hikingSession->fresh()->paused_at);
    }

    /**
     * Daftar putih di setiap aksi lapangan (Q-4/ruling W6-3s): kunci bertitik seperti
     * `posisi.lat` di tiba, jeda, dan lanjut, serta kunci di BADAN permintaan pada jeda dan
     * lanjut (test di atas hanya memakai query string), ditolak 422 dengan pesan berbahasa
     * Indonesia, dan sesi tidak berubah. Sebelum W6-3s kunci bertitik lolos 204, karena
     * validator membaca titik di nama aturan `prohibited` sebagai jalur bersarang, dan daftar
     * putih jeda dapat kembali menjadi daftar hitam lima kunci tanpa test merah (WL_JEDA_HITAM).
     *
     * @param  array<string, mixed>  $isi
     */
    #[DataProvider('kunciTambahanAksi')]
    public function test_field_actions_reject_keys_outside_the_whitelist_in_indonesian(string $rute, array $isi, string $kunci, string $pesan): void
    {
        $this->travelTo(Carbon::parse('2026-10-10 00:00:00', 'UTC'));
        $trip = $this->tripWithCheckpoints();
        $this->travelTo(Carbon::parse('2026-10-10 01:00:00', 'UTC'));

        if ($rute === 'trips.hike.resume') {
            $trip->hikingSession->update(['paused_at' => now()->subMinutes(30), 'status' => HikingSessionStatus::PAUSED->value]);
        }

        $kolom = ['reached_checkpoint_sequence', 'paused_at', 'paused_seconds', 'status', 'location_updated_at'];
        $sebelum = $trip->hikingSession->fresh()->only($kolom);

        $this->actingAs($trip->user)
            ->postJson(route($rute, $trip), $isi)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$kunci => $pesan]);

        $this->assertEquals($sebelum, $trip->hikingSession->fresh()->only($kolom), 'Aksi yang ditolak tidak boleh mengubah sesi.');
    }

    /**
     * @return array<string, array{0: string, 1: array<string, mixed>, 2: string, 3: string}>
     */
    public static function kunciTambahanAksi(): array
    {
        $tiba = 'Isian ini tidak diterima. Tiba di pos hanya membawa urutan pos.';
        $jeda = 'Isian ini tidak diterima. Jeda dan lanjut hanya membawa waktu ketukan.';

        return [
            'tiba: kunci bertitik posisi.lat' => ['trips.hike.arrive', ['urutan' => 2, 'posisi.lat' => -7.99, 'posisi.lng' => 112.95], 'posisi.lat', $tiba],
            'jeda: kunci bertitik posisi.lat' => ['trips.hike.pause', ['posisi.lat' => -7.99], 'posisi.lat', $jeda],
            'jeda: kunci badan akurasi' => ['trips.hike.pause', ['pada' => '2026-10-10T00:30:00Z', 'akurasi' => 12], 'akurasi', $jeda],
            'lanjut: kunci bertitik posisi.lat' => ['trips.hike.resume', ['posisi.lat' => -7.99], 'posisi.lat', $jeda],
            'lanjut: kunci badan posisi bersarang' => ['trips.hike.resume', ['pada' => '2026-10-10T01:00:00Z', 'posisi' => ['lat' => -7.99]], 'posisi', $jeda],
            'lanjut: kunci badan akurasi' => ['trips.hike.resume', ['akurasi' => 12], 'akurasi', $jeda],
        ];
    }

    /**
     * Aksi lapangan hanya untuk pendakian yang berlangsung; antrean perangkat membuang aksi
     * yang ditolak 409 dan menyebut alasannya.
     */
    public function test_field_actions_only_apply_to_a_hike_in_progress(): void
    {
        $trip = $this->tripWithCheckpoints();
        $trip->update(['status' => TripStatus::COMPLETED->value]);

        $this->tiba($trip, 2)->assertStatus(409);
        $this->actingAs($trip->user)->postJson(route('trips.hike.pause', $trip))->assertStatus(409);
    }

    public function test_a_finished_trip_opens_its_summary_or_workspace_instead(): void
    {
        $selesai = $this->tripWithCheckpoints();
        $selesai->update(['status' => TripStatus::COMPLETED->value]);
        $batal = TripPlan::factory()->for($selesai->user)->create(['status' => TripStatus::CANCELLED->value]);

        $this->actingAs($selesai->user)->get(route('trips.hike', $selesai))->assertRedirect(route('history.summary', $selesai));
        $this->actingAs($selesai->user)->get(route('trips.hike', $batal))->assertRedirect(route('trips.show', $batal));
    }

    /**
     * Waktu bergerak (ruling W6-R7): jeda 30 menit tidak dihitung, dan menyelesaikan trip saat
     * jeda masih berjalan menutup jedanya.
     */
    public function test_pausing_and_resuming_count_only_moving_time(): void
    {
        $this->travelTo(Carbon::parse('2026-10-10 00:00:00', 'UTC'));
        $trip = $this->tripWithCheckpoints();

        $this->travelTo(Carbon::parse('2026-10-10 01:00:00', 'UTC'));
        $this->actingAs($trip->user)->postJson(route('trips.hike.pause', $trip))->assertNoContent();
        $this->assertSame(HikingSessionStatus::PAUSED, $trip->hikingSession->fresh()->status);

        $this->travelTo(Carbon::parse('2026-10-10 01:30:00', 'UTC'));
        $this->actingAs($trip->user)->postJson(route('trips.hike.resume', $trip))->assertNoContent();
        $this->assertSame(1800, $trip->hikingSession->fresh()->paused_seconds);

        $this->travelTo(Carbon::parse('2026-10-10 02:00:00', 'UTC'));
        $this->actingAs($trip->user)->postJson(route('trips.hike.pause', $trip))->assertNoContent();

        $this->travelTo(Carbon::parse('2026-10-10 02:10:00', 'UTC'));
        $this->actingAs($trip->user)->post(route('trips.complete', $trip), ['completion_state' => 'COMPLETED']);

        $sesi = $trip->hikingSession->fresh();

        $this->assertNull($sesi->paused_at);
        $this->assertSame(2400, $sesi->paused_seconds);
        $this->assertSame(5400, $sesi->detikBergerak());
    }

    /**
     * Jeda yang diputar ulang antrean memakai waktu ketukan di perangkat, dijepit di antara
     * mulai sesi dan sekarang; jeda yang sudah berjalan tidak dimulai ulang.
     */
    public function test_a_replayed_pause_uses_the_device_time_and_is_not_restarted(): void
    {
        $this->travelTo(Carbon::parse('2026-10-10 00:00:00', 'UTC'));
        $trip = $this->tripWithCheckpoints();
        $this->travelTo(Carbon::parse('2026-10-10 03:00:00', 'UTC'));

        $this->actingAs($trip->user)->postJson(route('trips.hike.pause', $trip), ['pada' => '2026-10-10T01:00:00Z'])->assertNoContent();
        $this->actingAs($trip->user)->postJson(route('trips.hike.pause', $trip), ['pada' => '2026-10-10T02:00:00Z'])->assertNoContent();
        $this->assertSame('2026-10-10 01:00:00', $trip->hikingSession->fresh()->paused_at->utc()->format('Y-m-d H:i:s'));

        $this->actingAs($trip->user)->postJson(route('trips.hike.resume', $trip), ['pada' => '2026-10-11T00:00:00Z'])->assertNoContent();
        $this->assertSame(7200, $trip->hikingSession->fresh()->paused_seconds, 'Waktu di masa depan dijepit ke sekarang.');

        $this->actingAs($trip->user)->postJson(route('trips.hike.pause', $trip), ['pada' => 'bukan-waktu'])->assertUnprocessable();
    }

    /**
     * Pasangan jeda dan lanjut yang diputar ulang sesudah pasangan yang sama diterima (ruling
     * W6-10f bagian a, menutup W6-3aa; dasar probe peninjau ProbeRv10PutarUlangTest): tab lain
     * yang masih memegang pasangan itu di memorinya mengirimnya lagi. Jeda dengan waktu yang tidak
     * lebih baru dari lanjut terakhir diabaikan, dan lanjut yang diputar ulang tidak berbuat
     * apa-apa, jadi lama jeda tidak terhitung dua kali. Keduanya dijawab 204 seperti aksi yang
     * diterapkan, sehingga antrean membuangnya tanpa pesan.
     */
    public function test_a_replayed_pause_and_resume_pair_does_not_double_the_pause(): void
    {
        $this->travelTo(Carbon::parse('2026-10-10 00:00:00', 'UTC'));
        $trip = $this->tripWithCheckpoints();
        $this->travelTo(Carbon::parse('2026-10-10 03:00:00', 'UTC'));
        $jeda = ['pada' => '2026-10-10T01:00:00.000Z'];
        $lanjut = ['pada' => '2026-10-10T01:30:00.000Z'];

        foreach ([1, 2] as $putaran) {
            $this->actingAs($trip->user)->postJson(route('trips.hike.pause', $trip), $jeda)->assertNoContent();
            $this->actingAs($trip->user)->postJson(route('trips.hike.resume', $trip), $lanjut)->assertNoContent();
        }

        // Jeda dengan waktu SAMA dengan lanjut terakhir juga tidak lebih baru, jadi diabaikan.
        $this->actingAs($trip->user)->postJson(route('trips.hike.pause', $trip), $lanjut)->assertNoContent();

        $sesi = $trip->hikingSession->fresh();

        $this->assertSame(1800, $sesi->paused_seconds, 'Pasangan yang diputar ulang menggandakan lama jeda.');
        $this->assertNull($sesi->paused_at);
        $this->assertSame(HikingSessionStatus::ACTIVE, $sesi->status);
        $this->assertSame(9000, $sesi->detikBergerak());
        $this->assertSame('2026-10-10 01:30:00', $sesi->last_resumed_at->utc()->format('Y-m-d H:i:s'));
    }

    /**
     * Lanjut yang diputar ulang tidak menutup jeda yang LEBIH BARU (ruling W6-10h, tinjauan Tugas 10
     * I-B; dasar PROBE-D peninjau, ProbeRv10f1ServerTest): J1 01.00, L1 01.30, J2 02.00 berjalan, lalu
     * tab basi memutar ulang J1 dan L1. J2 tetap berjalan, dan lanjut sahnya kelak menghitungnya.
     * Lanjut di detik yang sama dengan lanjut terakhir juga diabaikan (batas ketelitian detik). Semua
     * dijawab 204, jadi antrean membuangnya tanpa pesan.
     */
    public function test_a_replayed_old_resume_does_not_close_a_newer_pause(): void
    {
        $this->travelTo(Carbon::parse('2026-10-10 00:00:00', 'UTC'));
        $trip = $this->tripWithCheckpoints();
        $this->travelTo(Carbon::parse('2026-10-10 03:00:00', 'UTC'));
        $kirim = fn (string $aksi, string $pada) => $this->actingAs($trip->user)->postJson(route('trips.hike.'.$aksi, $trip), ['pada' => $pada])->assertNoContent();

        $kirim('pause', '2026-10-10T01:00:00Z');
        $kirim('resume', '2026-10-10T01:30:00Z');
        $kirim('pause', '2026-10-10T02:00:00Z');

        $kirim('pause', '2026-10-10T01:00:00Z');
        $kirim('resume', '2026-10-10T01:30:00Z');

        $sesi = $trip->hikingSession->fresh();
        $this->assertSame('2026-10-10 02:00:00', $sesi->paused_at?->utc()->format('Y-m-d H:i:s'), 'Lanjut yang diputar ulang menutup jeda yang lebih baru.');
        $this->assertSame(HikingSessionStatus::PAUSED, $sesi->status);
        $this->assertSame(1800, $sesi->paused_seconds);
        $this->assertSame('2026-10-10 01:30:00', $sesi->last_resumed_at->utc()->format('Y-m-d H:i:s'));

        $kirim('resume', '2026-10-10T02:30:00Z');
        $kirim('pause', '2026-10-10T02:40:00Z');
        $kirim('resume', '2026-10-10T02:30:00Z');

        $this->assertSame('2026-10-10 02:40:00', $trip->hikingSession->fresh()->paused_at?->utc()->format('Y-m-d H:i:s'), 'Lanjut di detik yang sama dengan lanjut terakhir menutup jeda.');

        $kirim('resume', '2026-10-10T02:50:00Z');

        $sesi = $trip->hikingSession->fresh();
        $this->assertSame(3600 + 600, $sesi->paused_seconds);
        $this->assertNull($sesi->paused_at);
        $this->assertSame(HikingSessionStatus::ACTIVE, $sesi->status);
        $this->assertSame(10800 - 4200, $sesi->detikBergerak());
    }

    /**
     * Urutan PROBE-D persis seperti peninjau (ruling W6-10h): J1 01.00, L1 01.30, J2 02.00, J1 dan L1
     * diputar ulang, lalu L2 02.30. Akhirnya 3.600 detik jeda dan 7.200 detik bergerak sampai 03.00
     * (sebelum perbaikan 1.800 dan 9.000).
     */
    public function test_the_reviewer_probe_d_sequence_counts_both_pauses(): void
    {
        $this->travelTo(Carbon::parse('2026-10-10 00:00:00', 'UTC'));
        $trip = $this->tripWithCheckpoints();
        $this->travelTo(Carbon::parse('2026-10-10 03:00:00', 'UTC'));

        foreach ([['pause', '01:00'], ['resume', '01:30'], ['pause', '02:00'], ['pause', '01:00'], ['resume', '01:30'], ['resume', '02:30']] as [$aksi, $jam]) {
            $this->actingAs($trip->user)->postJson(route('trips.hike.'.$aksi, $trip), ['pada' => "2026-10-10T{$jam}:00Z"])->assertNoContent();
        }

        $sesi = $trip->hikingSession->fresh();
        $this->assertSame(3600, $sesi->paused_seconds);
        $this->assertSame(7200, $sesi->detikBergerak());
    }

    /**
     * Jeda baru yang sah sesudah lanjut tetap diterima (ruling W6-10f bagian a): hanya jeda yang
     * waktunya tidak lebih baru dari lanjut terakhir yang diabaikan, termasuk jeda tanpa `pada`
     * (waktu server) dan jeda di detik sesudah lanjut.
     */
    public function test_a_new_pause_after_the_last_resume_is_still_accepted(): void
    {
        $this->travelTo(Carbon::parse('2026-10-10 00:00:00', 'UTC'));
        $trip = $this->tripWithCheckpoints();
        $this->travelTo(Carbon::parse('2026-10-10 03:00:00', 'UTC'));

        $this->actingAs($trip->user)->postJson(route('trips.hike.pause', $trip), ['pada' => '2026-10-10T01:00:00Z'])->assertNoContent();
        $this->actingAs($trip->user)->postJson(route('trips.hike.resume', $trip), ['pada' => '2026-10-10T01:30:00Z'])->assertNoContent();
        $this->actingAs($trip->user)->postJson(route('trips.hike.pause', $trip), ['pada' => '2026-10-10T01:30:01Z'])->assertNoContent();

        $this->assertSame('2026-10-10 01:30:01', $trip->hikingSession->fresh()->paused_at->utc()->format('Y-m-d H:i:s'));

        $this->actingAs($trip->user)->postJson(route('trips.hike.resume', $trip), ['pada' => '2026-10-10T02:00:00Z'])->assertNoContent();
        $this->actingAs($trip->user)->postJson(route('trips.hike.pause', $trip))->assertNoContent();

        $sesi = $trip->hikingSession->fresh();
        $this->assertSame(1800 + 1799, $sesi->paused_seconds);
        $this->assertSame('2026-10-10 03:00:00', $sesi->paused_at->utc()->format('Y-m-d H:i:s'), 'Jeda tanpa pada (waktu server) sesudah lanjut diterima.');
    }

    /**
     * Batas bawah penjepit (M2b/ruling W6-3d): jeda yang diputar ulang dengan waktu SEBELUM
     * mulai sesi tetap dijepit ke mulai sesi. Tanpa batas bawah ini, mutan yang membuang
     * `->max($sesi->started_at)` lolos seluruh test lain.
     */
    public function test_a_pause_before_the_session_started_is_clamped_to_the_start(): void
    {
        $this->travelTo(Carbon::parse('2026-10-10 02:00:00', 'UTC'));
        $trip = $this->tripWithCheckpoints();
        $this->travelTo(Carbon::parse('2026-10-10 03:00:00', 'UTC'));

        $this->actingAs($trip->user)->postJson(route('trips.hike.pause', $trip), ['pada' => '2026-10-10T00:00:00Z'])->assertNoContent();

        $this->assertSame('2026-10-10 02:00:00', $trip->hikingSession->fresh()->paused_at->utc()->format('Y-m-d H:i:s'));
    }

    /**
     * paused_seconds tidak pernah negatif (M4b/ruling W6-3d): lanjut dengan waktu lebih awal
     * dari jeda tidak mengurangi paused_seconds di bawah nol. Tanpa max(0, ...) di sini,
     * mutannya menghasilkan -3600 pada SQLite tanpa satu pun test lain yang merah.
     */
    public function test_resuming_earlier_than_the_pause_does_not_go_negative(): void
    {
        $this->travelTo(Carbon::parse('2026-10-10 00:00:00', 'UTC'));
        $trip = $this->tripWithCheckpoints();
        $this->travelTo(Carbon::parse('2026-10-10 03:00:00', 'UTC'));

        $this->actingAs($trip->user)->postJson(route('trips.hike.pause', $trip), ['pada' => '2026-10-10T02:00:00Z'])->assertNoContent();
        $this->actingAs($trip->user)->postJson(route('trips.hike.resume', $trip), ['pada' => '2026-10-10T01:00:00Z'])->assertNoContent();

        $this->assertSame(0, $trip->hikingSession->fresh()->paused_seconds);
    }

    /**
     * `pada` berzona waktu selain UTC disimpan sebagai instan yang sama, bukan jam dinding
     * (M-6/ruling W6-3g): CarbonImmutable::parse() sendirian mempertahankan offset, dan binding
     * PDO memformatnya tanpa konversi. +07:00 08:00 menunjuk instan 01:00 UTC yang sama dengan
     * yang dikirim klien lewat toISOString(); keduanya wajib tersimpan identik.
     */
    public function test_a_timezone_offset_is_stored_as_the_same_instant_in_utc(): void
    {
        $this->travelTo(Carbon::parse('2026-10-10 00:00:00', 'UTC'));
        $trip = $this->tripWithCheckpoints();
        $this->travelTo(Carbon::parse('2026-10-10 03:00:00', 'UTC'));

        $this->actingAs($trip->user)->postJson(route('trips.hike.pause', $trip), ['pada' => '2026-10-10T08:00:00+07:00'])->assertNoContent();

        $this->assertSame('2026-10-10 01:00:00', $trip->hikingSession->fresh()->paused_at->utc()->format('Y-m-d H:i:s'));
    }

    /**
     * CAS jeda, jalur normal (W6-3m): model segar menutup jedanya sendiri.
     */
    public function test_a_fresh_model_closes_its_own_pause_via_cas(): void
    {
        $this->travelTo(Carbon::parse('2026-10-10 00:00:00', 'UTC'));
        $trip = $this->tripWithCheckpoints();
        $sesi = $trip->hikingSession;
        $sesi->update(['paused_at' => now(), 'status' => HikingSessionStatus::PAUSED->value]);

        $this->travelTo(Carbon::parse('2026-10-10 00:30:00', 'UTC'));
        $diterapkan = $sesi->fresh()->lanjutkanJeda(CarbonImmutable::now());

        $this->assertTrue($diterapkan);
        $this->assertSame(1800, $trip->hikingSession->fresh()->paused_seconds);
        $this->assertSame(HikingSessionStatus::ACTIVE, $trip->hikingSession->fresh()->status);
    }

    /**
     * CAS jeda, model basi (M-7/ruling W6-3m, residu N-8): dipanggil langsung dengan model
     * yang paused_at-nya sudah tidak sama dengan baris sungguhan (tab lain melanjutkan lalu
     * menjeda lagi di antara pembacaan model ini dan pemanggilan lanjutkanJeda). UPDATE tidak
     * boleh mengenai baris, dan jeda baru di baris sungguhan tidak boleh ikut ditutup atau
     * dihitung dua kali. Dipanggil langsung pada model, tanpa HTTP dan tanpa listener Eloquent,
     * sehingga staleness-nya deterministik, bukan bergantung urutan event.
     */
    public function test_a_stale_model_cannot_close_a_newer_pause_via_cas(): void
    {
        $this->travelTo(Carbon::parse('2026-10-10 00:00:00', 'UTC'));
        $trip = $this->tripWithCheckpoints();
        $sesi = $trip->hikingSession;

        $this->travelTo(Carbon::parse('2026-10-10 01:00:00', 'UTC'));
        $sesi->update(['paused_at' => now(), 'status' => HikingSessionStatus::PAUSED->value]);
        $basi = $sesi->fresh(); // membaca paused_at 01:00:00, sebelum tab lain mengubahnya.

        // Tab lain: lanjut (menutup 01:00:00) lalu jeda lagi, di antara pembacaan $basi dan
        // panggilan lanjutkanJeda() di bawah.
        $this->travelTo(Carbon::parse('2026-10-10 02:00:00', 'UTC'));
        $sesi->fresh()->update(['paused_at' => Carbon::parse('2026-10-10 01:45:00', 'UTC'), 'paused_seconds' => 1800]);

        $this->travelTo(Carbon::parse('2026-10-10 02:10:00', 'UTC'));
        $diterapkan = $basi->lanjutkanJeda(CarbonImmutable::now());

        $this->assertFalse($diterapkan, 'Model basi tidak semestinya mengubah baris apa pun.');
        $baris = $trip->hikingSession->fresh();
        $this->assertSame('2026-10-10 01:45:00', $baris->paused_at->utc()->format('Y-m-d H:i:s'), 'Jeda baru dari tab lain tertutup oleh lanjut yang basi.');
        $this->assertSame(1800, $baris->paused_seconds, 'Durasi jeda lama terhitung dua kali.');
    }

    /**
     * CAS lanjut lewat HTTP (Q-6/ruling W6-3u): test di atas memanggil lanjutkanJeda() langsung,
     * jadi controller yang kembali ke UPDATE whereNotNull lama tanpa metode itu (CTL_TANPA_CAS)
     * lolos seluruh suite. Di sini tab lain melanjutkan lalu menjeda lagi tepat sesudah resume()
     * membaca sesi: pendengar retrieved mengubah baris sekali, pada pembacaan pertama, jadi model
     * yang dipegang controller basi secara deterministik. Lanjut yang basi tidak boleh menutup
     * jeda baru itu atau menambah paused_seconds. Dasar: probe peninjau
     * test_probe_cas_lewat_http_dengan_pembacaan_basi (task-3-fix2-review.md bagian 2.3).
     */
    public function test_a_stale_resume_over_http_does_not_close_a_newer_pause(): void
    {
        $this->travelTo(Carbon::parse('2026-10-10 00:00:00', 'UTC'));
        $trip = $this->tripWithCheckpoints();
        $this->travelTo(Carbon::parse('2026-10-10 01:00:00', 'UTC'));
        $this->actingAs($trip->user)->postJson(route('trips.hike.pause', $trip))->assertNoContent();
        $this->travelTo(Carbon::parse('2026-10-10 02:00:00', 'UTC'));

        $tabLain = false;
        HikingSession::retrieved(function (HikingSession $sesi) use (&$tabLain): void {
            if ($tabLain) {
                return;
            }

            $tabLain = true;
            DB::table('hiking_sessions')->where('id', $sesi->id)->update(['paused_at' => '2026-10-10 01:45:00', 'paused_seconds' => 2700]);
        });

        $this->actingAs($trip->user)->postJson(route('trips.hike.resume', $trip))->assertNoContent();

        $this->assertTrue($tabLain, 'Pendengar retrieved tidak terpanggil, jadi pembacaan controller tidak basi dan test ini tidak membuktikan apa pun.');
        $baris = DB::table('hiking_sessions')->where('trip_plan_id', $trip->id)->first();
        $this->assertSame('2026-10-10 01:45:00', $baris->paused_at, 'Lanjut basi lewat HTTP menutup jeda baru dari tab lain.');
        $this->assertSame(2700, (int) $baris->paused_seconds, 'Lanjut basi lewat HTTP menambah durasi jeda lama ke paused_seconds.');
    }

    /**
     * Ruling W6-FB (Declined to judge butir 3 tinjauan akhir W6): jeda yang tiba sesudah pendakian
     * diselesaikan tidak membalik sesi COMPLETED menjadi PAUSED. Tab lain menyelesaikan pendakian
     * tepat sesudah jeda ini memuat trip dan sesinya, jadi penjaga 409 controller membaca trip
     * yang basi; syarat ended_at di UPDATE jeda yang menolak.
     */
    public function test_a_pause_arriving_after_the_hike_was_finished_does_not_reopen_the_session(): void
    {
        $this->travelTo(Carbon::parse('2026-10-10 00:00:00', 'UTC'));
        $trip = $this->tripWithCheckpoints();
        $this->travelTo(Carbon::parse('2026-10-10 01:00:00', 'UTC'));

        $tabLain = false;
        HikingSession::retrieved(function (HikingSession $sesi) use ($trip, &$tabLain): void {
            if ($tabLain) {
                return;
            }

            $tabLain = true;
            TripPlan::query()->whereKey($trip->id)->update(['status' => TripStatus::COMPLETED->value]);
            DB::table('hiking_sessions')->where('id', $sesi->id)->update(['status' => HikingSessionStatus::COMPLETED->value, 'ended_at' => '2026-10-10 00:59:00', 'paused_at' => null]);
        });

        $this->actingAs($trip->user)->postJson(route('trips.hike.pause', $trip), ['pada' => '2026-10-10T01:00:00Z'])->assertNoContent();

        $this->assertTrue($tabLain, 'Pendengar retrieved tidak terpanggil, jadi pembacaan controller tidak basi dan test ini tidak membuktikan apa pun.');
        $baris = $trip->hikingSession->fresh();
        $this->assertSame(HikingSessionStatus::COMPLETED, $baris->status, 'Jeda sesudah selesai membalik status sesi.');
        $this->assertNull($baris->paused_at);
    }

    /**
     * Ruling W6-FB, lanjut: sesi yang sudah berakhir tidak dilanjutkan. Selama complete()
     * mengosongkan paused_at, compare-and-swap lanjutkanJeda() sudah menolak lanjut basi; test ini
     * menjaga kontrak metodenya sendiri untuk baris berakhir yang paused_at-nya masih terisi,
     * dengan model basi (pola daftar periksa butir 4).
     */
    public function test_an_ended_session_is_never_resumed_from_a_stale_model(): void
    {
        $this->travelTo(Carbon::parse('2026-10-10 00:00:00', 'UTC'));
        $trip = $this->tripWithCheckpoints();
        $sesi = $trip->hikingSession;
        $this->travelTo(Carbon::parse('2026-10-10 01:00:00', 'UTC'));
        $sesi->update(['paused_at' => now(), 'status' => HikingSessionStatus::PAUSED->value]);
        $basi = $sesi->fresh();

        DB::table('hiking_sessions')->where('id', $sesi->id)->update(['status' => HikingSessionStatus::COMPLETED->value, 'ended_at' => '2026-10-10 01:30:00']);

        $this->travelTo(Carbon::parse('2026-10-10 02:00:00', 'UTC'));

        $this->assertFalse($basi->lanjutkanJeda(CarbonImmutable::now()), 'Sesi yang sudah berakhir tidak boleh dilanjutkan.');
        $baris = $trip->hikingSession->fresh();
        $this->assertSame(HikingSessionStatus::COMPLETED, $baris->status);
        $this->assertSame(0, $baris->paused_seconds);
        $this->assertNull($baris->last_resumed_at);
    }

    /**
     * Ruling W6-FT (penutupan OPEN ISSUE W6-FH): cancel() kini mengisi ended_at seperti
     * complete() (TripLifecycle::tutupSesi). Jeda yang tiba sesudah batal, dipanggil langsung
     * pada baris sesi (bukan lewat HTTP, yang sudah 409 lebih dulu di sesiBerlangsung() karena
     * status trip bukan IN_PROGRESS), tetap ditolak syarat ended_at di mulaiJeda() (ruling
     * W6-FB) dan tidak mengubah sesi.
     */
    public function test_a_pause_arriving_after_the_hike_was_cancelled_does_not_reopen_the_session(): void
    {
        $this->travelTo(Carbon::parse('2026-10-10 00:00:00', 'UTC'));
        $trip = $this->tripWithCheckpoints();
        $sesi = $trip->hikingSession;
        $this->travelTo(Carbon::parse('2026-10-10 01:00:00', 'UTC'));

        $this->assertNull(app(TripLifecycle::class)->cancel($trip->fresh()));

        $this->assertFalse($sesi->fresh()->mulaiJeda(CarbonImmutable::now()), 'Sesi yang ditutup batal tidak boleh dijeda.');
        $baris = $trip->hikingSession->fresh();
        $this->assertSame(HikingSessionStatus::ACTIVE, $baris->status, 'Status sesi tidak diubah oleh batal maupun oleh jeda yang ditolak.');
        $this->assertNull($baris->paused_at);
    }

    /**
     * Ruling W6-FT, pola W6-FB (lanjutkanJeda): model basi yang masih memegang paused_at dari
     * sebelum batal menutup jedanya tetap ditolak syarat ended_at, sehingga lanjut basi tidak
     * membuka kembali sesi yang sudah ditutup batal atau menghitung jeda yang sama dua kali.
     */
    public function test_a_resume_arriving_after_the_hike_was_cancelled_does_not_reopen_the_session(): void
    {
        $this->travelTo(Carbon::parse('2026-10-10 00:00:00', 'UTC'));
        $trip = $this->tripWithCheckpoints();
        $sesi = $trip->hikingSession;
        $this->travelTo(Carbon::parse('2026-10-10 01:00:00', 'UTC'));
        $this->assertTrue($sesi->mulaiJeda(CarbonImmutable::now()));
        $basi = $sesi->fresh();

        $this->travelTo(Carbon::parse('2026-10-10 01:15:00', 'UTC'));
        $this->assertNull(app(TripLifecycle::class)->cancel($trip->fresh()));

        $ditutup = $trip->hikingSession->fresh();
        $this->assertNull($ditutup->paused_at);
        $this->assertSame(900, $ditutup->paused_seconds, 'cancel() wajib menutup jeda 15 menit yang masih berjalan.');

        $this->travelTo(Carbon::parse('2026-10-10 02:00:00', 'UTC'));
        $this->assertFalse($basi->lanjutkanJeda(CarbonImmutable::now()), 'Sesi yang ditutup batal tidak boleh dilanjutkan dari model basi.');

        $akhir = $trip->hikingSession->fresh();
        $this->assertSame(900, $akhir->paused_seconds, 'Lanjut basi tidak boleh mengubah sesi yang sudah ditutup batal.');
        $this->assertNull($akhir->last_resumed_at);
    }

    /**
     * Kunci tambahan yang kosong pada jeda dan lanjut diterima (ruling W6-3s2), kini dengan data
     * set sendiri (ruling W6-FF, M-3 tinjauan akhir W6: mutasi D2 yang menolak kunci kosong selamat
     * karena data set kunci kosong hanya ada untuk tiba). Jawabannya 204, aksinya berlaku seperti
     * tanpa kunci itu, dan kolom lokasi sesi tidak berubah: kunci kosong tidak membawa data posisi.
     *
     * @param  array<string, mixed>  $tambahan
     */
    #[DataProvider('kunciKosongJedaLanjut')]
    public function test_pause_and_resume_accept_empty_extra_keys(string $rute, array $tambahan): void
    {
        $this->travelTo(Carbon::parse('2026-10-10 00:00:00', 'UTC'));
        $trip = $this->tripWithCheckpoints();
        $this->travelTo(Carbon::parse('2026-10-10 01:00:00', 'UTC'));

        if ($rute === 'trips.hike.resume') {
            $trip->hikingSession->update(['paused_at' => Carbon::parse('2026-10-10 00:30:00', 'UTC'), 'status' => HikingSessionStatus::PAUSED->value]);
        }

        $this->actingAs($trip->user)
            ->postJson(route($rute, $trip), ['pada' => '2026-10-10T01:00:00Z', ...$tambahan])
            ->assertNoContent();

        $baris = $trip->hikingSession->fresh();
        $this->assertNull($baris->location_updated_at, 'Kunci kosong tidak boleh menulis lokasi.');

        if ($rute === 'trips.hike.pause') {
            $this->assertSame('2026-10-10 01:00:00', $baris->paused_at?->utc()->format('Y-m-d H:i:s'));
        } else {
            $this->assertNull($baris->paused_at);
            $this->assertSame(1800, $baris->paused_seconds);
        }
    }

    /**
     * @return array<string, array{0: string, 1: array<string, mixed>}>
     */
    public static function kunciKosongJedaLanjut(): array
    {
        return [
            'jeda: latitude null' => ['trips.hike.pause', ['latitude' => null]],
            'jeda: lat string kosong' => ['trips.hike.pause', ['lat' => '']],
            'lanjut: posisi larik kosong' => ['trips.hike.resume', ['posisi' => []]],
            'lanjut: akurasi null' => ['trips.hike.resume', ['akurasi' => null]],
        ];
    }

    /**
     * Niat lama "pendaki dapat membuka mode lapangan di tengah jalur; pos sebelum yang terdekat
     * dianggap terlewati" dipulihkan sebagai pertanyaan, bukan otomatis (I-6/ruling W6-3f):
     * pendaki yang mulai di Pos 3 karena naik ojek, atau yang melewatkan tiba otomatis di satu
     * pos, ditanya "Tandai Pos … sampai … sudah dilewati?" alih-alih tersandera jarak garis
     * lurus ke pos yang sudah jauh di belakangnya. hitung.ts diuji di Node (FieldModeFiguresTest);
     * test ini hanya menjaga halaman benar-benar memanggilnya dan menampilkan kartunya.
     *
     * Diubah putaran perbaikan 4, dengan alasan per asersi lama:
     * - `posTerlampaui(` dan `kartuLapangan(` tidak lagi dicari di hook: Q-1/ruling W6-3p
     *   memindahkan perakitan masukannya ke keadaanKartu di hitung.ts, dan hook hanya memanggil
     *   fungsi itu. Penggantinya lebih ketat: keadaanKartu wajib memanggil keputusanTiba,
     *   posTerlampaui, dan kartuLapangan (di sini), dan panggilan hook dijaga regex argumen
     *   mentah di test_the_hook_hands_raw_values_to_one_card_function.
     * - `sudah dilewati?` dan kedua templat judul kartu lewat tidak lagi dicari di komponen:
     *   Q-7/ruling W6-3v memindahkan kalimatnya ke kalimatKartuLewat di hitung.ts, yang diuji Node
     *   dalam bentuk tunggal dan rentang (FieldModeFiguresTest). Di sini komponen wajib memakai
     *   fungsi itu untuk judul dan status, dan tidak menulis templatnya sendiri.
     * - Regex gerbang Alert (P2b) kini menuntut KartuJagaFokus di antara gerbang dan Alert
     *   (Q-3/ruling W6-3r); gerbang kartuTiba.jenis dan kartuLewat tetap langsung di kondisinya.
     * - Hitungan lima panggilan pindahFokusKeJudul diganti satu regex per penangan (Q-9/ruling
     *   W6-3x): hitungan tetap lima walau panggilan dipindah antarpenangan (F_TUKAR).
     */
    public function test_the_page_offers_to_mark_passed_checkpoints_as_a_question(): void
    {
        $komponen = File::get(resource_path('js/components/mendaki/ModeLapangan.tsx'));
        $hitung = File::get(resource_path('js/components/mendaki/hitung.ts'));

        $this->assertStringContainsString('export function posTerlampaui(', $hitung);
        $this->assertStringContainsString('export function kartuLapangan(', $hitung);
        $this->assertStringContainsString('export function kalimatKartuLewat(', $hitung);

        preg_match('/export function keadaanKartu\(.*?\n\}\n/s', $hitung, $keadaanKartu);
        $this->assertNotEmpty($keadaanKartu, 'keadaanKartu tidak ditemukan di hitung.ts.');

        foreach (['keputusanTiba(', 'posTerlampaui(', 'kartuLapangan('] as $panggilan) {
            $this->assertStringContainsString($panggilan, $keadaanKartu[0], "keadaanKartu wajib memanggil {$panggilan}...) sendiri (W6-3p).");
        }

        // W6-3m: komponen hanya merender keputusan kartu, bukan menghitung ulang gerbang akurasi
        // atau penundaan "Belum". Nama field lama (bertanya/akurasiKurang sebagai boolean lepas)
        // tidak lagi ada di komponen.
        $this->assertStringNotContainsString('mode.bertanya', $komponen);
        $this->assertStringNotContainsString('mode.akurasiKurang', $komponen);

        // Pertanyaan dibuang di komponen (P2b) harus merah di sini: kartu Alert wajib digerbangi
        // kartuTiba/kartuLewat langsung di kondisinya, bukan sekadar disebut di tempat lain
        // pada berkas (misalnya hanya di pengumuman sr-only) selagi Alert-nya sendiri dilepas
        // atau digerbangi kondisi lain. Di antara gerbang dan Alert hanya KartuJagaFokus (W6-3r).
        $this->assertMatchesRegularExpression("/\{kartuTiba\?\.jenis === 'tanya' \? \(\s*<KartuJagaFokus onLepas=\{pindahFokusKeJudul\}>\s*<Alert\b/", $komponen, 'Kartu tiba wajib digerbangi kartuTiba.jenis langsung.');
        $this->assertMatchesRegularExpression('/\{kartuLewat \? \(\s*<KartuJagaFokus onLepas=\{pindahFokusKeJudul\}>\s*<Alert\b/', $komponen, 'Kartu lewat wajib digerbangi kartuLewat langsung.');

        // Daftar periksa mutu butir 2: kalimat benar di setiap cabang. Kalimat kartu lewat hanya
        // dari kalimatKartuLewat (W6-3v), untuk status dan judul, tanpa templat sendiri di
        // komponen; tidak ada klaim "dekat" (R-005, N-8) pada kalimat kartu mana pun di berkas ini.
        $this->assertStringContainsString("{kartuLewat ? kalimatKartuLewat(kartuLewat.dari, kartuLewat.sampai).status : ''}", $komponen);
        $this->assertStringContainsString('title={kalimatKartuLewat(kartuLewat.dari, kartuLewat.sampai).judul}', $komponen);
        $this->assertStringNotContainsString('sudah dilewati?', $komponen);
        $this->assertStringNotContainsString('tampak sudah dilewati', $komponen);
        $this->assertStringNotContainsString('dekat', $komponen);

        // Daftar periksa mutu butir 7, fokus tidak pernah jatuh ke body (ruling W6-3o): satu h2
        // #judul-pos-berikutnya, selalu terpasang, dengan ref dan tabIndex={-1} untuk dituju.
        preg_match_all('/<h2 id="judul-pos-berikutnya"[^>]*>/', $komponen, $h2);
        $this->assertCount(1, $h2[0], 'Harus ada tepat satu h2 #judul-pos-berikutnya (bukan satu per cabang tujuan).');
        $this->assertStringContainsString('ref={judulPosBerikutnya}', $h2[0][0] ?? '');
        $this->assertStringContainsString('tabIndex={-1}', $h2[0][0] ?? '');
        $this->assertStringContainsString('const pindahFokusKeJudul = () => judulPosBerikutnya.current?.focus();', $komponen);

        // Satu regex per penangan (W6-3x): Ya dan Belum di kartu tiba, Ya dan Belum di kartu
        // lewat (W6-3o), dan "Saya sudah di {nama}" (W6-3o2). Masing-masing menuntut
        // pindahFokusKeJudul() di dalam penangan itu, sesudah aksinya, sebagai pernyataan
        // terakhirnya. "Ya" di kartu lewat mengirim `sampai`, rentang penuh yang disebut kartunya
        // (Q-8/ruling W6-3w).
        $penangan = [
            'Ya di kartu tiba' => 'mode\.tiba\(kartuTiba\.urutan\);',
            'Belum di kartu tiba' => 'mode\.tolakTiba\(\);',
            'Ya di kartu lewat, mengirim sampai' => 'mode\.tiba\(kartuLewat\.sampai\);',
            'Belum di kartu lewat' => 'mode\.tolakLewat\(\);',
            'Saya sudah di {nama}' => 'mode\.tiba\(tujuan\.urutan\);',
        ];

        foreach ($penangan as $nama => $aksi) {
            $this->assertMatchesRegularExpression(
                '/onClick=\{\(\) => \{\s*'.$aksi.'\s*pindahFokusKeJudul\(\);\s*\}\}/',
                $komponen,
                "Penangan {$nama} wajib memanggil pindahFokusKeJudul() di dalam penangannya sendiri, sesudah aksinya.",
            );
        }
    }

    /**
     * Hook memberikan nilai mentah ke satu fungsi kartu (Q-1/ruling W6-3p). Sebelum ini hook
     * merakit sendiri jarak, keputusanTiba, posTerlampaui, dan argumen kartuLapangan, dan lima
     * mutan masukan di hook selamat dari seluruh suite (K_HOOK_*, task-3-fix2-review.md bagian
     * 2.2). Satu regex atas blok panggilan menuntut nama argumen mentahnya persis, jadi mengganti
     * satu argumen (misalnya `posisi: null`, akurasi 0, `pos: []`, atau penolakan `null`) merah di
     * sini. Gerbang di dalam keadaanKartu sendiri diuji Node satu kasus per gerbang
     * (FieldModeFiguresTest::test_the_card_state_has_one_case_per_gate).
     */
    public function test_the_hook_hands_raw_values_to_one_card_function(): void
    {
        $hook = File::get(resource_path('js/components/mendaki/useModeLapangan.ts'));

        $this->assertSame(1, substr_count($hook, 'keadaanKartu('), 'Hook memanggil keadaanKartu tepat sekali.');
        $this->assertMatchesRegularExpression(
            '/const \{ keputusan, kartu \} = keadaanKartu\(\{\s*'
                .'posisi,\s*'
                .'tujuan,\s*'
                .'dijeda,\s*'
                .'berlangsung: data\.trip\.berlangsung,\s*'
                .'radiusTiba: data\.radiusTiba,\s*'
                .'pos: data\.pos\.map\(\(p\) => \(\{ urutan: p\.urutan, sepanjangM: sepanjang\.get\(p\.urutan\) \?\? null \}\)\),\s*'
                .'tercapai,\s*'
                .'letak,\s*'
                .'tolakTiba: ditanyaTolak\.current,\s*'
                .'tolakLewatSampai: ditanyaLewatTolak\.current,\s*'
                .'sekarang,\s*'
                .'\}\);/',
            $hook,
            'Panggilan keadaanKartu di hook wajib membawa nilai mentah dengan nama persis (W6-3p).',
        );

        foreach (['kartuLapangan(', 'posTerlampaui(', 'keputusanTiba(', 'jarakMeter(', 'bolehTanyaTiba('] as $rakitan) {
            $this->assertStringNotContainsString($rakitan, $hook, "Hook tidak lagi merakit {$rakitan}...) sendiri (W6-3p).");
        }
    }

    /**
     * Kartu yang lepas karena perubahan keadaan, bukan karena tombolnya (Q-3/ruling W6-3r):
     * pembaruan posisi, tiba otomatis, atau jeda. Sebelum ini fokus di tombol kartu jatuh ke body
     * (peramban B-8a dan B-8b, task-3-fix2-review.md bagian 2.5). KartuJagaFokus membungkus kedua
     * kartu, dan cleanup efek tata letaknya, yang dijalankan React sebelum simpul DOM kartu
     * dicabut, memindahkan fokus ke judul pos berikutnya HANYA bila fokus masih di dalam kartu;
     * fokus di luar kartu tidak dicuri. Ini asersi sumber atas pembungkus, syarat, dan efeknya;
     * perilakunya di peramban (B-8a, B-8b) diuji peninjau ulang.
     */
    public function test_a_card_dropped_by_a_state_change_moves_focus_only_from_inside_it(): void
    {
        $komponen = File::get(resource_path('js/components/mendaki/ModeLapangan.tsx'));

        // Pembungkus: elemen yang diperiksa fokusnya membungkus seluruh isi kartu.
        $this->assertMatchesRegularExpression(
            '/function KartuJagaFokus\(\{ onLepas, children \}: \{ onLepas: \(\) => void; children: ReactNode \}\) \{\s*const pembungkus = useRef<HTMLDivElement>\(null\);/',
            $komponen,
        );
        $this->assertStringContainsString('return <div ref={pembungkus}>{children}</div>;', $komponen);

        // Efek dan syaratnya: cleanup efek TATA LETAK (efek biasa berjalan sesudah simpul dicabut,
        // saat fokus sudah di body), dependensi kosong (efek yang dipasang ulang di setiap render
        // menjalankan cleanup selagi kartu masih tampil dan mencuri fokus dari tombolnya), dan
        // onLepas hanya bila fokus di dalam kartu.
        $this->assertMatchesRegularExpression(
            '/useLayoutEffect\(\(\) => \{\s*const kartu = pembungkus\.current;\s*return \(\) => \{\s*if \(kartu !== null && kartu\.contains\(document\.activeElement\)\) \{\s*onLepas\(\);\s*\}\s*\};\s*\}, \[\]\);/',
            $komponen,
            'KartuJagaFokus wajib memindahkan fokus hanya bila fokus di dalam kartu, di cleanup efek tata letak berdependensi kosong.',
        );

        // Kedua kartu dibungkus, dengan judul pos berikutnya sebagai tujuan fokus.
        $this->assertSame(2, substr_count($komponen, '<KartuJagaFokus onLepas={pindahFokusKeJudul}>'), 'Kartu tiba dan kartu lewat wajib dibungkus KartuJagaFokus.');
    }

    public function test_field_actions_are_rate_limited(): void
    {
        $trip = $this->tripWithCheckpoints();
        $batas = (int) config('hiking.rate_limits.personal_writes_per_minute');

        foreach (range(1, $batas) as $i) {
            $this->tiba($trip, 1)->assertNoContent();
        }

        $this->tiba($trip, 1)->assertStatus(429);
    }

    /**
     * Perkiraan per ruas adalah rentang teramati dari paling sedikit lima rekaman (DK-31);
     * tanpa itu null, dan perangkat menulis "belum ada data waktu tempuh".
     */
    public function test_the_segment_estimate_is_a_sourced_range_or_names_its_absence(): void
    {
        $trip = $this->tripWithCheckpoints();
        [$pos1, $pos2] = $trip->trail->checkpoints->sortBy('sequence')->values()->all();

        foreach ([40, 50, 60, 70, 80] as $menit) {
            $this->rekamRuas($trip->trail, $pos1, $pos2, $menit);
        }

        $this->actingAs($trip->user)->get(route('trips.hike', $trip))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('ruas.0', ['ke' => 2, 'perkiraan' => '40–80 menit', 'rekaman' => 5])
                ->where('ruas.1', ['ke' => 3, 'perkiraan' => null, 'rekaman' => null]));

        $this->assertSame(['45 menit', '1,5–2,5 jam', '2,5–3 jam', '3 jam'], [
            HalamanMendaki::perkiraan(45, 45),
            HalamanMendaki::perkiraan(90, 150),
            HalamanMendaki::perkiraan(150, 180),
            HalamanMendaki::perkiraan(170, 190),
        ]);
    }

    private function tiba(TripPlan $trip, int $urutan): TestResponse
    {
        return $this->actingAs($trip->user)->postJson(route('trips.hike.arrive', $trip), ['urutan' => $urutan]);
    }

    /**
     * Satu pendakian pendaki lain yang lewat $dari lalu $ke sekian menit kemudian
     * (pola CheckpointPaceTest).
     */
    private function rekamRuas(Trail $jalur, Checkpoint $dari, Checkpoint $ke, int $menit): void
    {
        $trip = TripPlan::factory()->for($jalur)->create();
        $sesi = HikingSession::create(['trip_plan_id' => $trip->id, 'user_id' => $trip->user_id, 'status' => HikingSessionStatus::COMPLETED->value, 'started_at' => Carbon::parse('2026-09-15 04:00:00')]);
        $mulai = Carbon::parse('2026-09-15 05:00:00');

        HikeTrackPoint::create(['hiking_session_id' => $sesi->id, 'recorded_at' => $mulai, 'latitude' => $dari->latitude, 'longitude' => $dari->longitude]);
        HikeTrackPoint::create(['hiking_session_id' => $sesi->id, 'recorded_at' => $mulai->copy()->addMinutes($menit), 'latitude' => $ke->latitude, 'longitude' => $ke->longitude]);
    }

    private function tripWithCheckpoints(bool $berkoordinat = true): TripPlan
    {
        $user = User::factory()->create();
        $mountain = Mountain::factory()->create();
        $trail = Trail::factory()->for($mountain)->create();

        // Empat pos berjajar ke utara, berjarak sekitar 220 m antar pos.
        foreach ([1 => -7.9970, 2 => -7.9950, 3 => -7.9930, 4 => -7.9910] as $sequence => $latitude) {
            Checkpoint::factory()->for($trail)->create([
                'sequence' => $sequence,
                'name' => 'Pos '.$sequence,
                'latitude' => $berkoordinat ? $latitude : null,
                'longitude' => $berkoordinat ? 112.9500 : null,
            ]);
        }

        $trip = TripPlan::create([
            'user_id' => $user->id,
            'trail_id' => $trail->id,
            'name' => 'Uji mode lapangan',
            'planned_date' => now()->toDateString(),
            'trip_type' => TripType::TEKTOK->value,
            'status' => TripStatus::IN_PROGRESS->value,
        ]);

        $trip->hikingSession()->create([
            'user_id' => $user->id,
            'status' => HikingSessionStatus::ACTIVE->value,
            'started_at' => now(),
        ]);

        return $trip->fresh();
    }
}
