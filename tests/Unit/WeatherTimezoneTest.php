<?php

namespace Tests\Unit;

use App\Models\Mountain;
use App\Models\Trail;
use App\Models\WeatherSnapshot;
use App\Services\WeatherService;
use App\Support\Timezone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * PRD §44-46.
 *
 * BMKG mengirim `local_datetime` dalam waktu lokal Indonesia. Karena timezone
 * aplikasi adalah UTC, nilai itu sebelumnya tersimpan seolah-olah UTC sehingga
 * seluruh jadwal prakiraan bergeser tujuh jam. Untuk pendaki yang menentukan jam
 * summit push, pergeseran itu salah secara material.
 *
 * Perbaikannya memakai `utc_datetime` milik BMKG yang tidak ambigu.
 */
class WeatherTimezoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_forecast_instants_are_stored_in_utc(): void
    {
        $this->fakeBmkg();
        $trail = $this->trail();

        app(WeatherService::class)->refreshForTrail($trail);

        $snapshot = WeatherSnapshot::first();

        $this->assertSame(
            '2026-09-19 15:00:00',
            $snapshot->forecast_at->utc()->format('Y-m-d H:i:s'),
            'forecast_at harus merupakan instan UTC dari utc_datetime milik BMKG.'
        );
    }

    public function test_the_local_reading_from_bmkg_is_preserved(): void
    {
        $this->fakeBmkg();
        app(WeatherService::class)->refreshForTrail($this->trail());

        $snapshot = WeatherSnapshot::first();

        $this->assertNotNull($snapshot->local_datetime, 'Nilai lokal asal BMKG tetap disimpan untuk rujukan.');
    }

    public function test_an_instant_renders_in_the_mountain_timezone_with_its_label(): void
    {
        $trail = $this->trail('Asia/Makassar');

        $rendered = Timezone::display(
            Carbon::parse('2026-09-19 15:00:00', 'UTC'),
            Timezone::forTrail($trail)
        );

        // 15:00 UTC = 23:00 WITA
        $this->assertStringContainsString('23:00', $rendered);
        $this->assertStringContainsString('WITA', $rendered);
    }

    public function test_a_trail_defaults_to_wib(): void
    {
        $trail = $this->trail();

        $this->assertSame('Asia/Jakarta', Timezone::forTrail($trail));
        $this->assertSame('WIB', Timezone::label(Timezone::forTrail($trail)));
    }

    public function test_the_forecast_window_is_compared_against_utc_instants(): void
    {
        $trail = $this->trail();

        WeatherSnapshot::create([
            'adm4_code' => $trail->weather_adm4_code,
            'forecast_at' => now()->addHours(6),
            'local_datetime' => now()->addHours(13),
            'source' => 'BMKG',
            'fetched_at' => now(),
        ]);

        $this->assertCount(1, app(WeatherService::class)->forecastForTrail($trail));
    }

    private function fakeBmkg(): void
    {
        Http::fake([
            '*' => Http::response([
                'data' => [[
                    'cuaca' => [[[
                        'utc_datetime' => '2026-09-19 15:00:00',
                        'local_datetime' => '2026-09-19 22:00:00',
                        't' => 27,
                        'hu' => 88,
                        'ws' => 3.6,
                        'wd' => 'S',
                        'tcc' => 20,
                        'weather_desc' => 'Cerah',
                        'analysis_date' => '2026-09-19T12:00:00',
                    ]]],
                ]],
            ], 200),
        ]);
    }

    private function trail(string $timezone = 'Asia/Jakarta'): Trail
    {
        $mountain = Mountain::factory()->create(['timezone' => $timezone]);

        return Trail::factory()->for($mountain)->create([
            'weather_adm4_code' => '31.71.03.1001',
            'weather_reference_area' => 'Area sekitar jalur',
        ]);
    }
}
