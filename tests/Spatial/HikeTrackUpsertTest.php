<?php

namespace Tests\Spatial;

use App\Enums\HikingSessionStatus;
use App\Enums\TripStatus;
use App\Models\HikeTrackPoint;
use App\Models\HikingSession;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Unggahan jejak di PostgreSQL (ruling W6-P2c, R-024). Dua pola yang tidak dapat dibuktikan suite
 * SQLite:
 * - satu upsert yang memuat dua titik pada detik yang sama ditolak PostgreSQL seluruhnya
 *   ("ON CONFLICT DO UPDATE command cannot affect row a second time", SQLSTATE 21000), jadi
 *   PenerimaanJejak::titikPerDetik wajib membuang gandanya sebelum upsert;
 * - baris sesi dikunci (SELECT ... FOR UPDATE) di dalam transaksi unggahan; SQLite mengabaikan
 *   FOR UPDATE.
 */
class HikeTrackUpsertTest extends SpatialTestCase
{
    /**
     * @return array{0: User, 1: HikingSession}
     */
    private function sesiSelesai(): array
    {
        $user = User::factory()->create();
        $user->preference()->create(['preferred_duration' => 'ONE_DAY', 'record_track' => true]);
        $trip = TripPlan::factory()->for($user)->create(['status' => TripStatus::COMPLETED->value]);
        $sesi = HikingSession::create([
            'trip_plan_id' => $trip->id,
            'user_id' => $user->id,
            'status' => HikingSessionStatus::COMPLETED->value,
            'started_at' => Carbon::parse('2026-09-15 04:00:00'),
            'ended_at' => Carbon::parse('2026-09-15 12:30:00'),
        ]);

        return [$user->fresh(), $sesi];
    }

    public function test_points_in_the_same_second_upload_as_one_row_on_postgres(): void
    {
        [$user, $sesi] = $this->sesiSelesai();

        $this->actingAs($user)->postJson(route('hike.track', $sesi), ['points' => [
            ['recorded_at' => '2026-09-15T04:00:00.100Z', 'latitude' => -7.45, 'longitude' => 110.44, 'elevation_m' => 1200, 'accuracy_m' => 8],
            ['recorded_at' => '2026-09-15T04:00:00.700Z', 'latitude' => -7.46, 'longitude' => 110.45, 'elevation_m' => 1210, 'accuracy_m' => 6],
            ['recorded_at' => '2026-09-15T11:00:05+07:00', 'latitude' => -7.47, 'longitude' => 110.46, 'elevation_m' => 1220, 'accuracy_m' => 6],
        ]])->assertOk()->assertExactJson(['tersimpan' => 2]);

        $this->assertSame(2, HikeTrackPoint::where('hiking_session_id', $sesi->id)->count());
        $this->assertSame(['2026-09-15 04:00:00', '2026-09-15 04:00:05'], DB::table('hike_track_points')->orderBy('recorded_at')->pluck('recorded_at')->map(fn ($w) => substr((string) $w, 0, 19))->all());
    }

    /**
     * Jalur kunci baris sesi berjalan di PostgreSQL, dan kiriman ulang menimpa baris yang sama.
     * Dua koneksi bersamaan tidak dibangun di sini; itu titipan uji konkurensi W9.
     */
    public function test_the_session_lock_path_runs_and_a_resend_overwrites_on_postgres(): void
    {
        [$user, $sesi] = $this->sesiSelesai();
        $titik = [['recorded_at' => '2026-09-15T04:00:00Z', 'latitude' => -7.45, 'longitude' => 110.44, 'elevation_m' => 1200, 'accuracy_m' => 8]];

        $this->actingAs($user)->postJson(route('hike.track', $sesi), ['points' => $titik])->assertOk();
        $titik[0]['accuracy_m'] = 4;
        $this->actingAs($user)->postJson(route('hike.track', $sesi), ['points' => $titik])->assertOk()->assertExactJson(['tersimpan' => 1]);

        $this->assertSame(4, (int) HikeTrackPoint::where('hiking_session_id', $sesi->id)->value('accuracy_m'));
    }
}
