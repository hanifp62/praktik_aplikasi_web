<?php

namespace Tests\Spatial;

use App\Enums\HikingSessionStatus;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Models\Checkpoint;
use App\Models\HikingSession;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Jeda dan lanjut (ruling W6-R7; M-8/ruling W6-3h) di PostgreSQL: paritas sintaks untuk UPDATE
 * bersyarat (whereNull, compare-and-swap where('paused_at', ...)) dan DB::raw('paused_seconds
 * + N') (R-024). Suite SQLite menulis berurutan dan tidak membuktikan sintaks ini valid atau
 * berperilaku sama di PostgreSQL. Konkurensi dua koneksi/proses nyata tetap titipan W9, sama
 * seperti HikingSessionUniqueTest; test ini hanya menjalankan jeda-lanjut sungguhan satu
 * koneksi di Postgres.
 *
 * Test kedua menjaga kolom last_known_location (Q-5/ruling W6-3t), yang hanya ada di PostgreSQL.
 */
class HikingSessionPauseResumeTest extends SpatialTestCase
{
    public function test_pause_and_resume_apply_through_postgres_syntax(): void
    {
        $this->travelTo(Carbon::parse('2026-10-10 00:00:00', 'UTC'));

        $user = User::factory()->create();
        $trail = Trail::factory()->create();
        Checkpoint::factory()->for($trail)->create(['sequence' => 1]);

        $trip = TripPlan::create([
            'user_id' => $user->id,
            'trail_id' => $trail->id,
            'name' => 'Uji Spatial jeda',
            'planned_date' => now()->toDateString(),
            'trip_type' => TripType::TEKTOK->value,
            'status' => TripStatus::IN_PROGRESS->value,
        ]);
        $trip->hikingSession()->create(['user_id' => $user->id, 'status' => HikingSessionStatus::ACTIVE->value, 'started_at' => now()]);

        $this->travelTo(Carbon::parse('2026-10-10 01:00:00', 'UTC'));
        $this->actingAs($user)->postJson(route('trips.hike.pause', $trip))->assertNoContent();
        $this->assertSame(HikingSessionStatus::PAUSED, $trip->hikingSession->fresh()->status);

        $this->travelTo(Carbon::parse('2026-10-10 01:30:00', 'UTC'));
        $this->actingAs($user)->postJson(route('trips.hike.resume', $trip))->assertNoContent();

        $sesi = $trip->hikingSession->fresh();
        $this->assertSame(HikingSessionStatus::ACTIVE, $sesi->status);
        $this->assertNull($sesi->paused_at);
        $this->assertSame(1800, $sesi->paused_seconds);

        // Jeda kedua ditambahkan ke paused_seconds yang sudah ada (DB::raw('paused_seconds +
        // N')): jika sintaksnya tidak dimengerti PostgreSQL, baris ini tidak pernah tercapai.
        $this->travelTo(Carbon::parse('2026-10-10 02:00:00', 'UTC'));
        $this->actingAs($user)->postJson(route('trips.hike.pause', $trip))->assertNoContent();
        $this->travelTo(Carbon::parse('2026-10-10 02:10:00', 'UTC'));
        $this->actingAs($user)->postJson(route('trips.hike.resume', $trip))->assertNoContent();

        $this->assertSame(2400, $trip->hikingSession->fresh()->paused_seconds);
    }

    /**
     * Pasangan jeda dan lanjut yang diputar ulang (ruling W6-10f bagian a) di PostgreSQL:
     * perbandingan `last_resumed_at < ?` atas kolom timestamp tanpa zona dengan binding Carbon,
     * dan penulisan last_resumed_at di UPDATE compare-and-swap yang sama (R-024). SQLite
     * membandingkan teks 'Y-m-d H:i:s', jadi suite Feature tidak membuktikan perbandingan jenis
     * timestamp ini berperilaku sama.
     */
    public function test_a_replayed_pause_and_resume_pair_is_ignored_through_postgres_syntax(): void
    {
        $this->travelTo(Carbon::parse('2026-10-10 00:00:00', 'UTC'));

        $user = User::factory()->create();
        $trail = Trail::factory()->create();
        Checkpoint::factory()->for($trail)->create(['sequence' => 1]);

        $trip = TripPlan::create([
            'user_id' => $user->id,
            'trail_id' => $trail->id,
            'name' => 'Uji Spatial putar ulang jeda',
            'planned_date' => now()->toDateString(),
            'trip_type' => TripType::TEKTOK->value,
            'status' => TripStatus::IN_PROGRESS->value,
        ]);
        $trip->hikingSession()->create(['user_id' => $user->id, 'status' => HikingSessionStatus::ACTIVE->value, 'started_at' => now()]);

        $this->travelTo(Carbon::parse('2026-10-10 03:00:00', 'UTC'));

        foreach ([1, 2] as $putaran) {
            $this->actingAs($user)->postJson(route('trips.hike.pause', $trip), ['pada' => '2026-10-10T01:00:00.000Z'])->assertNoContent();
            $this->actingAs($user)->postJson(route('trips.hike.resume', $trip), ['pada' => '2026-10-10T01:30:00.000Z'])->assertNoContent();
        }

        $this->assertSame(1800, $trip->hikingSession->fresh()->paused_seconds);

        $this->actingAs($user)->postJson(route('trips.hike.pause', $trip), ['pada' => '2026-10-10T01:30:01.000Z'])->assertNoContent();
        $this->assertSame('2026-10-10 01:30:01', $trip->hikingSession->fresh()->paused_at->utc()->format('Y-m-d H:i:s'));
    }

    /**
     * Lanjut yang diputar ulang tidak menutup jeda yang lebih baru (ruling W6-10h) di PostgreSQL:
     * perbandingan `last_resumed_at < ?` di UPDATE compare-and-swap lanjutkanJeda, urutan PROBE-D
     * peninjau (J1 01.00, L1 01.30, J2 02.00, J1 dan L1 diputar ulang, lalu L2 02.30).
     */
    public function test_a_replayed_old_resume_keeps_a_newer_pause_through_postgres_syntax(): void
    {
        $this->travelTo(Carbon::parse('2026-10-10 00:00:00', 'UTC'));

        $user = User::factory()->create();
        $trail = Trail::factory()->create();
        Checkpoint::factory()->for($trail)->create(['sequence' => 1]);

        $trip = TripPlan::create([
            'user_id' => $user->id,
            'trail_id' => $trail->id,
            'name' => 'Uji Spatial lanjut basi',
            'planned_date' => now()->toDateString(),
            'trip_type' => TripType::TEKTOK->value,
            'status' => TripStatus::IN_PROGRESS->value,
        ]);
        $trip->hikingSession()->create(['user_id' => $user->id, 'status' => HikingSessionStatus::ACTIVE->value, 'started_at' => now()]);

        $this->travelTo(Carbon::parse('2026-10-10 03:00:00', 'UTC'));

        foreach ([['pause', '01:00'], ['resume', '01:30'], ['pause', '02:00'], ['pause', '01:00'], ['resume', '01:30']] as [$aksi, $jam]) {
            $this->actingAs($user)->postJson(route('trips.hike.'.$aksi, $trip), ['pada' => "2026-10-10T{$jam}:00.000Z"])->assertNoContent();
        }

        $this->assertSame('2026-10-10 02:00:00', $trip->hikingSession->fresh()->paused_at?->utc()->format('Y-m-d H:i:s'));

        $this->actingAs($user)->postJson(route('trips.hike.resume', $trip), ['pada' => '2026-10-10T02:30:00.000Z'])->assertNoContent();

        $sesi = $trip->hikingSession->fresh();
        $this->assertSame(3600, $sesi->paused_seconds);
        $this->assertSame(7200, $sesi->detikBergerak());
    }

    /**
     * Tiba, jeda, dan lanjut tidak mengubah last_known_location (Q-5/ruling W6-3t, residu W6-3j).
     * Kolomnya hanya dibuat di PostgreSQL (PostGis::addGeography tidak berbuat apa-apa di SQLite),
     * jadi suite SQLite hanya dapat menjaga location_updated_at (HikeModeCheckpointTest). Posisi
     * lama dipasang lebih dulu, karena sesi dari sebelum W6 dapat masih membawanya (ruling W6-K7:
     * kolom tidak lagi ditulis, dan pengosongannya hanya atas izin pemilik). Nilainya dibandingkan
     * apa adanya sesudah setiap aksi: aksi yang menulis titik pos ke kolom ini, atau
     * mengosongkannya, merah di sini.
     */
    public function test_field_actions_leave_the_last_known_location_untouched(): void
    {
        $this->travelTo(Carbon::parse('2026-10-10 00:00:00', 'UTC'));

        $user = User::factory()->create();
        $trail = Trail::factory()->create();
        Checkpoint::factory()->for($trail)->create(['sequence' => 1, 'latitude' => -7.9950, 'longitude' => 112.9500]);

        $trip = TripPlan::create([
            'user_id' => $user->id,
            'trail_id' => $trail->id,
            'name' => 'Uji Spatial lokasi terakhir',
            'planned_date' => now()->toDateString(),
            'trip_type' => TripType::TEKTOK->value,
            'status' => TripStatus::IN_PROGRESS->value,
        ]);
        $sesi = $trip->hikingSession()->create(['user_id' => $user->id, 'status' => HikingSessionStatus::ACTIVE->value, 'started_at' => now()]);
        $sesi->writePoint('last_known_location', -7.5000, 110.5000);

        $sebelum = $this->lokasiTerakhir($sesi);
        $this->assertNotNull($sebelum, 'Posisi lama tidak terpasang; test ini tidak membuktikan apa pun.');

        $this->travelTo(Carbon::parse('2026-10-10 01:00:00', 'UTC'));
        $this->actingAs($user)->postJson(route('trips.hike.arrive', $trip), ['urutan' => 1])->assertNoContent();
        $this->assertSame($sebelum, $this->lokasiTerakhir($sesi), 'Tiba di pos mengubah last_known_location.');

        $this->actingAs($user)->postJson(route('trips.hike.pause', $trip))->assertNoContent();
        $this->assertSame($sebelum, $this->lokasiTerakhir($sesi), 'Jeda mengubah last_known_location.');

        $this->travelTo(Carbon::parse('2026-10-10 01:30:00', 'UTC'));
        $this->actingAs($user)->postJson(route('trips.hike.resume', $trip))->assertNoContent();
        $this->assertSame($sebelum, $this->lokasiTerakhir($sesi), 'Lanjut mengubah last_known_location.');
    }

    private function lokasiTerakhir(HikingSession $sesi): ?string
    {
        return DB::selectOne('SELECT ST_AsText(last_known_location) AS titik FROM hiking_sessions WHERE id = ?', [$sesi->id])?->titik;
    }
}
