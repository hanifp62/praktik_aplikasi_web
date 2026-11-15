<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Models\WeatherSnapshot;
use App\Services\PreparationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Kesegaran prakiraan di Cek kesiapan (titipan W4, tinjauan akhir W4 butir 8; ruling W8-R26):
 * waktu ambil dan kesegarannya menyebut baris tanggal trip yang tampil, bukan konteks 72 jam
 * WeatherService. Area yang sama dapat menyimpan baris dari jalan refresh yang berbeda; prakiraan
 * yang diambil lebih dari sehari lalu tidak boleh terbaca "Terbaru" hanya karena baris lain di
 * jendela 72 jam baru diambil (R-013).
 */
class ReadinessForecastFreshnessTest extends TestCase
{
    use RefreshDatabase;

    private function trip(): TripPlan
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::ADVANCED->value, 'completed_at' => now()]);
        $trail = Trail::factory()->published()->for(Mountain::factory()->create(['timezone' => 'Asia/Jakarta']))->easy()->create();

        $trip = TripPlan::create([
            'user_id' => $user->id,
            'trail_id' => $trail->id,
            'name' => 'Uji kesegaran prakiraan',
            'planned_date' => '2026-10-10',
            'trip_type' => TripType::CAMPING->value,
            'status' => TripStatus::PLANNED->value,
        ]);
        app(PreparationService::class)->generateFor($trip);

        return $trip->fresh();
    }

    public function test_the_forecast_freshness_describes_the_trip_date_rows(): void
    {
        $this->travelTo(Carbon::parse('2026-10-08 01:00:00', 'UTC'));
        $trip = $this->trip();
        $kode = $trip->trail->weather_adm4_code;

        // Baris tanggal trip dari jalan refresh 30 jam lalu.
        WeatherSnapshot::factory()->create(['adm4_code' => $kode, 'forecast_at' => Carbon::parse('2026-10-10 00:00:00', 'UTC'), 'fetched_at' => now()->subHours(30), 'weather_description' => 'Hujan ringan']);
        // Baris lain di jendela 72 jam, baru diambil sejam lalu.
        WeatherSnapshot::factory()->create(['adm4_code' => $kode, 'forecast_at' => now()->addHours(3), 'fetched_at' => now()->subHour()]);

        $prakiraan = $this->actingAs($trip->user)->get(route('trips.readiness', $trip))->viewData('page')['props']['kondisi']['prakiraan'];

        $this->assertSame('tersedia', $prakiraan['keadaan']);
        $this->assertSame('Sudah lama', $prakiraan['kesegaran']);
        $this->assertSame('7 Okt 02.00 WIB', $prakiraan['diperbarui']);
    }

    public function test_fresh_trip_date_rows_read_fresh(): void
    {
        $this->travelTo(Carbon::parse('2026-10-08 01:00:00', 'UTC'));
        $trip = $this->trip();

        WeatherSnapshot::factory()->create(['adm4_code' => $trip->trail->weather_adm4_code, 'forecast_at' => Carbon::parse('2026-10-10 00:00:00', 'UTC'), 'fetched_at' => now()->subHours(2)]);

        $prakiraan = $this->actingAs($trip->user)->get(route('trips.readiness', $trip))->viewData('page')['props']['kondisi']['prakiraan'];

        $this->assertSame('Terbaru', $prakiraan['kesegaran']);
        $this->assertSame('8 Okt 06.00 WIB', $prakiraan['diperbarui']);
    }
}
