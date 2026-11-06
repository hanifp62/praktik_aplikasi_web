<?php

namespace Tests\Spatial;

use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Models\WeatherSnapshot;
use Illuminate\Support\Carbon;

/**
 * M8 tinjauan akhir W4 (R-024): kueri prakiraan satu hari tanggal trip di Cek kesiapan
 * (HalamanKesiapan::prakiraan) di PostgreSQL. Batas hari zona gunung dikonversi ke UTC lalu
 * dibandingkan dengan kolom timestamp tanpa zona, dan MAX(forecast_at) dibaca kembali sebagai
 * UTC; keduanya hanya teruji di SQLite. Kembarannya di SQLite:
 * ReadinessDashboardTest::test_the_forecast_day_boundary_uses_the_mountain_zone_not_utc.
 *
 * Jalur WITA (UTC+8): hari 10 Oktober setempat adalah 9 Okt 16.00 UTC sampai 10 Okt 15.59.59
 * UTC. Dilewati dengan jelas tanpa SPATIAL_TEST_DSN (lihat SpatialTestCase).
 */
class ReadinessForecastDayTest extends SpatialTestCase
{
    private function tripWita(): TripPlan
    {
        $gunung = Mountain::factory()->create(['timezone' => 'Asia/Makassar']);
        $jalur = Trail::factory()->for($gunung)->create(['weather_adm4_code' => '52.03.11.2001']);

        return TripPlan::create([
            'user_id' => User::factory()->create()->id,
            'trail_id' => $jalur->id,
            'name' => 'Rinjani WITA',
            'planned_date' => '2026-10-10',
            'trip_type' => TripType::CAMPING->value,
            'status' => TripStatus::PLANNED->value,
        ]);
    }

    private function baris(TripPlan $trip, string $waktuUtc, string $label): void
    {
        WeatherSnapshot::factory()->create([
            'adm4_code' => $trip->trail->weather_adm4_code,
            'forecast_at' => Carbon::parse($waktuUtc, 'UTC'),
            'fetched_at' => now(),
            'weather_description' => $label,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function prakiraan(TripPlan $trip): array
    {
        return $this->actingAs($trip->user)->get(route('trips.readiness', $trip))->assertOk()->viewData('page')['props']['kondisi']['prakiraan'];
    }

    public function test_the_trip_day_runs_from_local_midnight_to_local_midnight(): void
    {
        $this->travelTo(Carbon::parse('2026-10-08 01:00:00', 'UTC'));
        $trip = $this->tripWita();

        $this->baris($trip, '2026-10-09 15:59:00', 'Pukul 23.59 hari sebelumnya');
        $this->baris($trip, '2026-10-09 16:00:00', 'Pukul 00.00');
        $this->baris($trip, '2026-10-10 15:59:00', 'Pukul 23.59');
        $this->baris($trip, '2026-10-10 16:00:00', 'Pukul 00.00 hari sesudahnya');

        $prakiraan = $this->prakiraan($trip);

        $this->assertSame('tersedia', $prakiraan['keadaan']);
        $this->assertSame('WITA', $prakiraan['zona']);
        $this->assertSame(['Pukul 00.00', 'Pukul 23.59'], array_column($prakiraan['baris'], 'cuaca'));
        $this->assertSame(['00.00', '23.59'], array_column($prakiraan['baris'], 'waktu'));
        $this->assertNull($prakiraan['sebagian'], 'Data tersimpan menjangkau hari sesudahnya, jadi hari ini penuh.');
    }

    public function test_data_ending_on_the_trip_day_is_said_to_be_partial(): void
    {
        $this->travelTo(Carbon::parse('2026-10-08 01:00:00', 'UTC'));
        $trip = $this->tripWita();

        $this->baris($trip, '2026-10-09 16:00:00', 'Pukul 00.00');
        $this->baris($trip, '2026-10-10 15:59:00', 'Pukul 23.59');

        $prakiraan = $this->prakiraan($trip);

        $this->assertSame('Prakiraan BMKG baru menjangkau sampai pukul 23.59 WITA pada tanggal ini.', $prakiraan['sebagian']);
    }
}
