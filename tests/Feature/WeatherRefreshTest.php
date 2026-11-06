<?php

namespace Tests\Feature;

use App\Models\Mountain;
use App\Models\Trail;
use App\Services\ConditionAggregatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * F-21 dan F-22.
 *
 * Perintah weather:refresh iterasi per jalur, bukan per kode wilayah. Beberapa jalur
 * pada satu kelurahan berbagi kode adm4 yang sama, sehingga satu area dipanggil
 * berkali-kali, memboroskan kuota BMKG yang dibatasi 60 permintaan per menit per IP
 * dan menulis ulang baris yang sama.
 *
 * ConditionAggregatorService juga menghitung konteks cuaca dua kali untuk satu jalur:
 * sekali untuk ditampilkan, sekali lagi di dalam penyusunan peringatan.
 */
class WeatherRefreshTest extends TestCase
{
    use RefreshDatabase;

    public function test_one_api_call_per_reference_area(): void
    {
        Http::fake(['*' => Http::response(['data' => []], 200)]);

        $mountain = Mountain::factory()->create();
        Trail::factory()->published()->count(5)->for($mountain)->create(['weather_adm4_code' => '35.07.17.2002']);

        $this->artisan('weather:refresh')->assertSuccessful();

        Http::assertSentCount(1);
    }

    public function test_distinct_areas_are_each_fetched_once(): void
    {
        Http::fake(['*' => Http::response(['data' => []], 200)]);

        Trail::factory()->published()->count(3)->create(['weather_adm4_code' => '35.07.17.2002']);
        Trail::factory()->published()->count(2)->create(['weather_adm4_code' => '33.08.10.2001']);

        $this->artisan('weather:refresh')->assertSuccessful();

        Http::assertSentCount(2);
    }

    public function test_unpublished_trails_do_not_consume_quota(): void
    {
        Http::fake(['*' => Http::response(['data' => []], 200)]);

        Trail::factory()->unpublished()->create(['weather_adm4_code' => '35.07.17.2002']);

        $this->artisan('weather:refresh')->assertSuccessful();

        Http::assertSentCount(0);
    }

    public function test_condition_aggregation_reads_the_weather_context_once(): void
    {
        $trail = Trail::factory()->published()->create(['weather_adm4_code' => '35.07.17.2002']);

        $weatherQueries = 0;
        DB::listen(function ($query) use (&$weatherQueries) {
            if (str_contains($query->sql, 'weather_snapshots')) {
                $weatherQueries++;
            }
        });

        app(ConditionAggregatorService::class)->forTrail($trail);

        $this->assertLessThanOrEqual(
            2,
            $weatherQueries,
            "Konteks cuaca dihitung lebih dari sekali: {$weatherQueries} query."
        );
    }
}
