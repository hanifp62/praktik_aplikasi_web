<?php

namespace Tests\Unit;

use App\Enums\FreshnessState;
use App\Models\Trail;
use App\Models\WeatherSnapshot;
use App\Services\WeatherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeatherServiceTest extends TestCase
{
    use RefreshDatabase;

    private function bmkgPayload(): array
    {
        return [
            'data' => [[
                'cuaca' => [[
                    [
                        'local_datetime' => now()->addHours(3)->format('Y-m-d H:i:s'),
                        'weather_desc' => 'Hujan Ringan',
                        't' => 18,
                        'hu' => 92,
                        'ws' => 6.5,
                        'wd' => 'W',
                        'tcc' => 88,
                        'vs' => 8000,
                        'analysis_date' => now()->format('Y-m-d H:i:s'),
                    ],
                ]],
            ]],
        ];
    }

    public function test_it_normalizes_and_stores_bmkg_forecast(): void
    {
        Http::fake(['api.bmkg.go.id/*' => Http::response($this->bmkgPayload())]);

        $trail = Trail::factory()->create(['weather_adm4_code' => '33.08.10.2001']);
        $stored = app(WeatherService::class)->refreshForTrail($trail);

        $this->assertSame(1, $stored);

        $snapshot = WeatherSnapshot::first();
        $this->assertSame('Hujan Ringan', $snapshot->weather_description);
        $this->assertSame(92, $snapshot->humidity_percent);
        $this->assertSame('BMKG', $snapshot->source);
    }

    public function test_it_stores_nothing_when_the_api_fails(): void
    {
        Http::fake(['api.bmkg.go.id/*' => Http::response(status: 500)]);

        $trail = Trail::factory()->create(['weather_adm4_code' => '33.08.10.2001']);
        $stored = app(WeatherService::class)->refreshForTrail($trail);

        $this->assertSame(0, $stored);
        $this->assertSame(0, WeatherSnapshot::count());
    }

    public function test_context_reports_unavailable_instead_of_inventing_weather(): void
    {
        $trail = Trail::factory()->create(['weather_adm4_code' => '33.08.10.2001']);

        $context = app(WeatherService::class)->contextForTrail($trail);

        $this->assertFalse($context['available']);
        $this->assertSame('Data cuaca tidak tersedia.', $context['message']);
        $this->assertSame(FreshnessState::UNKNOWN->value, $context['freshness']);
    }

    public function test_stale_data_is_surfaced_with_its_timestamp(): void
    {
        $trail = Trail::factory()->create(['weather_adm4_code' => '33.08.10.2001']);
        WeatherSnapshot::factory()->create([
            'adm4_code' => $trail->weather_adm4_code,
            'forecast_at' => now()->subDays(2),
            'local_datetime' => now()->subDays(2),
            'fetched_at' => now()->subDays(2),
        ]);

        $context = app(WeatherService::class)->contextForTrail($trail);

        $this->assertTrue($context['available']);
        $this->assertSame(FreshnessState::STALE->value, $context['freshness']);
        $this->assertStringContainsString('Data terakhir tersedia', $context['message']);
    }
}
