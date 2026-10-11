<?php

namespace Tests\Spatial;

use App\Enums\OfficialStatusValue;
use App\Enums\StatusScope;
use App\Enums\TripStatus;
use App\Models\Checkpoint;
use App\Models\DataSource;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\RestrictedArea;
use App\Models\Trail;
use App\Models\TrailSegment;
use App\Models\TripPlan;
use App\Models\User;
use App\Models\WeatherSnapshot;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Ruling W6-8c dan W6-8a (R-024): bagian dinamis paket lapangan di PostgreSQL.
 *
 * - Trail::restrictedAreas() juga mencocokkan area yang memotong geometri jalur lewat
 *   ST_Intersects, cabang yang tidak pernah berjalan di SQLite. Area itu ikut
 *   dinamis.status.areaTerbatas bersama area yang bertaut ke jalur, urut id; area yang jauh
 *   tidak ikut.
 * - Pembatasan segmen urut rute (sequence), bukan urutan baris PostgreSQL atau id (ruling W6-8h).
 * - Cuaca diambil dengan satu kueri rentang forecast_at atas batas hari zona gunung yang
 *   dikonversi ke UTC: 8 Okt 00.00 WIB (7 Okt 17.00 UTC) masuk, 7 Okt 23.59 WIB tidak.
 *
 * Kembarannya di SQLite: FieldPackStalenessTest (segmen dan area, dan pengelompokan cuaca per
 * tanggal lokal). Dilewati dengan jelas tanpa SPATIAL_TEST_DSN (lihat SpatialTestCase).
 */
class FieldPackRestrictedAreaTest extends SpatialTestCase
{
    public function test_the_dynamic_section_reads_crossing_areas_segment_order_and_weather_days(): void
    {
        $this->travelTo(Carbon::parse('2026-10-07 03:00:00', 'UTC'));
        config(['hiking.field_pack.enabled' => true]);

        $trail = Trail::factory()->for(Mountain::factory()->create())->create(['data_source_id' => DataSource::factory()->create()->id, 'weather_adm4_code' => '32.03.13.2001', 'weather_reference_area' => 'Cimacan']);
        $trail->writeLineString('geometry', [[112.9500, -7.9970], [112.9510, -7.9930]]);
        Checkpoint::factory()->for($trail)->create(['sequence' => 1]);
        OfficialStatus::factory()->create(['statusable_type' => $trail->getMorphClass(), 'statusable_id' => $trail->getKey(), 'status' => OfficialStatusValue::OPEN->value]);
        $trail->fresh()->update(['is_published' => true]);

        $memotong = RestrictedArea::create(['name' => 'Zona rawan longsor', 'reason' => 'Aktivitas vulkanik', 'effective_at' => now()->subDay()]);
        $this->poligon($memotong, [[112.9490, -7.9980], [112.9520, -7.9980], [112.9520, -7.9920], [112.9490, -7.9920], [112.9490, -7.9980]]);
        $jauh = RestrictedArea::create(['name' => 'Zona lain', 'effective_at' => now()->subDay()]);
        $this->poligon($jauh, [[113.5000, -8.5000], [113.5100, -8.5000], [113.5100, -8.4900], [113.5000, -8.4900], [113.5000, -8.5000]]);
        $bertaut = RestrictedArea::create(['trail_id' => $trail->id, 'name' => 'Area bertaut', 'effective_at' => now()->subDay()]);

        $puncak = TrailSegment::factory()->for($trail)->create(['sequence' => 2, 'name' => 'Puncak']);
        $kandang = TrailSegment::factory()->for($trail)->create(['sequence' => 1, 'name' => 'Kandang Badak']);

        foreach ([$kandang, $puncak] as $segmen) {
            OfficialStatus::factory()->create(['statusable_type' => $segmen->getMorphClass(), 'statusable_id' => $segmen->id, 'scope' => StatusScope::SEGMENT->value, 'status' => OfficialStatusValue::CLOSED->value]);
        }

        foreach (['2026-10-07 16:59:00' => '7 Okt 23.59 WIB', '2026-10-07 17:00:00' => '8 Okt 00.00 WIB', '2026-10-08 16:59:00' => '8 Okt 23.59 WIB', '2026-10-08 17:00:00' => '9 Okt 00.00 WIB'] as $waktu => $cuaca) {
            WeatherSnapshot::create(['adm4_code' => '32.03.13.2001', 'reference_area' => 'Cimacan', 'forecast_at' => Carbon::parse($waktu, 'UTC'), 'local_datetime' => Carbon::parse($waktu, 'UTC')->addHours(7)->toDateTimeString(), 'weather_description' => $cuaca, 'temperature_c' => 17, 'analysis_date' => Carbon::parse('2026-10-07 00:00:00', 'UTC'), 'source' => 'BMKG', 'fetched_at' => now()]);
        }

        $user = User::factory()->create();
        $trip = TripPlan::factory()->create(['user_id' => $user->id, 'trail_id' => $trail->id, 'status' => TripStatus::PLANNED->value, 'planned_date' => '2026-10-08']);

        $dinamis = json_decode($this->actingAs($user)->get(route('trips.pack.section', [$trip, 'dinamis']))->assertOk()->getContent(), true);

        $this->assertSame([
            ['id' => $memotong->id, 'nama' => 'Zona rawan longsor', 'alasan' => 'Aktivitas vulkanik'],
            ['id' => $bertaut->id, 'nama' => 'Area bertaut', 'alasan' => null],
        ], $dinamis['status']['areaTerbatas']);
        $this->assertSame([$kandang->id, $puncak->id], array_column($dinamis['status']['pembatasan'], 'segmenId'));
        $this->assertSame(['8 Okt 00.00 WIB', '8 Okt 23.59 WIB'], array_column($dinamis['cuaca']['hari'][0]['baris'], 'cuaca'));
    }

    /**
     * @param  array<int, array{0: float, 1: float}>  $cincin
     */
    private function poligon(RestrictedArea $area, array $cincin): void
    {
        $titik = implode(',', array_map(fn (array $pasangan) => sprintf('%F %F', $pasangan[0], $pasangan[1]), $cincin));

        DB::statement('UPDATE restricted_areas SET geometry = ST_GeogFromText(?) WHERE id = ?', ["SRID=4326;POLYGON(({$titik}))", $area->id]);
    }
}
