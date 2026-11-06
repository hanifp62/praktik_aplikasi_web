<?php

namespace App\Services;

use App\Enums\FreshnessState;
use App\Models\Trail;
use App\Models\WeatherSnapshot;
use App\Support\Timezone;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * BMKG public forecast integration (PRD §44-47).
 *
 * The forecast is administrative-area based, so it is always presented as "prakiraan area
 * sekitar jalur", never as summit weather. On failure the service returns nothing and lets the
 * UI fall back to the last stored snapshot - it never invents data.
 */
class WeatherService
{
    public const SOURCE = 'BMKG';

    private const ENDPOINT = 'https://api.bmkg.go.id/publik/prakiraan-cuaca';

    /**
     * @return int number of snapshots stored
     */
    public function refreshForTrail(Trail $trail): int
    {
        if (blank($trail->weather_adm4_code)) {
            return 0;
        }

        $entries = $this->fetch($trail->weather_adm4_code);

        if ($entries === null) {
            return 0;
        }

        $stored = 0;
        $fetchedAt = now();

        foreach ($entries as $entry) {
            $normalized = $this->normalize($entry, $trail, $fetchedAt);

            if ($normalized === null) {
                continue;
            }

            WeatherSnapshot::updateOrCreate(
                ['adm4_code' => $normalized['adm4_code'], 'forecast_at' => $normalized['forecast_at']],
                $normalized
            );

            $stored++;
        }

        return $stored;
    }

    /**
     * @return array<int, array<string, mixed>>|null null means the fetch failed
     */
    public function fetch(string $adm4Code): ?array
    {
        try {
            $response = Http::timeout(15)->get(self::ENDPOINT, ['adm4' => $adm4Code]);

            if ($response->failed()) {
                Log::warning('BMKG forecast fetch failed', ['adm4' => $adm4Code, 'status' => $response->status()]);

                return null;
            }

            return $this->flatten($response->json('data') ?? []);
        } catch (Throwable $exception) {
            Log::warning('BMKG forecast fetch threw', ['adm4' => $adm4Code, 'message' => $exception->getMessage()]);

            return null;
        }
    }

    /**
     * BMKG nests forecasts as data[].cuaca[][] - flatten to a single list of entries.
     *
     * @param  array<int, mixed>  $data
     * @return array<int, array<string, mixed>>
     */
    private function flatten(array $data): array
    {
        $entries = [];

        foreach ($data as $location) {
            foreach ($location['cuaca'] ?? [] as $group) {
                foreach ($group as $entry) {
                    if (is_array($entry)) {
                        $entries[] = $entry;
                    }
                }
            }
        }

        return $entries;
    }

    /**
     * @param  array<string, mixed>  $entry
     * @return array<string, mixed>|null
     */
    private function normalize(array $entry, Trail $trail, Carbon $fetchedAt): ?array
    {
        // `local_datetime` adalah waktu lokal tanpa penanda zona; menyimpannya apa adanya
        // pada aplikasi bertimezone UTC menggeser seluruh jadwal tujuh jam.
        $utcDatetime = $entry['utc_datetime'] ?? null;
        $localDatetime = $entry['local_datetime'] ?? null;

        if (blank($utcDatetime) && blank($localDatetime)) {
            return null;
        }

        $forecastAt = blank($utcDatetime)
            // Tanpa utc_datetime, satu-satunya asumsi yang dapat dipakai adalah zona
            // gunung yang bersangkutan.
            ? Carbon::parse($localDatetime, Timezone::forTrail($trail))->utc()
            : Carbon::parse($utcDatetime, 'UTC');

        return [
            'adm4_code' => $trail->weather_adm4_code,
            'reference_area' => $trail->weather_reference_area,
            'forecast_at' => $forecastAt,
            'local_datetime' => $localDatetime,
            'weather_description' => $entry['weather_desc'] ?? null,
            'temperature_c' => isset($entry['t']) ? (float) $entry['t'] : null,
            'humidity_percent' => isset($entry['hu']) ? (int) $entry['hu'] : null,
            'wind_speed_kmh' => isset($entry['ws']) ? (float) $entry['ws'] : null,
            'wind_direction' => $entry['wd'] ?? null,
            'cloud_cover_percent' => isset($entry['tcc']) ? (int) $entry['tcc'] : null,
            'visibility_m' => isset($entry['vs']) ? (int) $entry['vs'] : null,
            'analysis_date' => isset($entry['analysis_date']) ? Carbon::parse($entry['analysis_date']) : null,
            'source' => self::SOURCE,
            'fetched_at' => $fetchedAt,
        ];
    }

    /**
     * Upcoming forecast entries for the trail's reference area.
     *
     * @return Collection<int, WeatherSnapshot>
     */
    public function forecastForTrail(Trail $trail, int $hours = 72): Collection
    {
        if (blank($trail->weather_adm4_code)) {
            return collect();
        }

        return WeatherSnapshot::query()
            ->where('adm4_code', $trail->weather_adm4_code)
            ->whereBetween('forecast_at', [now()->subHours(3), now()->addHours($hours)])
            ->orderBy('forecast_at')
            ->get();
    }

    public function latestForTrail(Trail $trail): ?WeatherSnapshot
    {
        if (blank($trail->weather_adm4_code)) {
            return null;
        }

        return WeatherSnapshot::query()
            ->where('adm4_code', $trail->weather_adm4_code)
            ->orderByDesc('fetched_at')
            ->first();
    }

    /**
     * PRD §47 + §93: always paired with a source, a timestamp and an explicit freshness state.
     * When nothing has ever been fetched the context says so rather than implying good weather.
     *
     * @return array<string, mixed>
     */
    public function contextForTrail(Trail $trail): array
    {
        $latest = $this->latestForTrail($trail);

        if ($latest === null) {
            return [
                'available' => false,
                'source' => self::SOURCE,
                'reference_area' => $trail->weather_reference_area,
                'freshness' => FreshnessState::UNKNOWN->value,
                'message' => 'Data cuaca tidak tersedia.',
                'forecast' => [],
            ];
        }

        $freshness = $this->freshness($latest->fetched_at);
        $timezone = Timezone::forTrail($trail);

        return [
            'available' => true,
            'source' => $latest->source,
            'reference_area' => $latest->reference_area ?? $trail->weather_reference_area,
            'timezone' => $timezone,
            'timezone_label' => Timezone::label($timezone),
            'fetched_at' => $latest->fetched_at?->toIso8601String(),
            // Waktu yang dibaca pengguna selalu dalam zona gunungnya, lengkap dengan
            // penandanya; tanpa itu tidak ada cara tahu jam mana yang dimaksud.
            'fetched_at_display' => Timezone::display($latest->fetched_at, $timezone),
            'analysis_date' => $latest->analysis_date?->toIso8601String(),
            'freshness' => $freshness->value,
            'message' => $freshness === FreshnessState::STALE
                ? 'Data cuaca belum berhasil diperbarui. Data terakhir tersedia pada '
                    .Timezone::display($latest->fetched_at, $timezone).'.'
                : null,
            'forecast' => $this->forecastForTrail($trail)->all(),
        ];
    }

    /**
     * BMKG publishes twice a day, so the thresholds follow that cadence rather than a
     * universal "older than 24h is stale" rule (PRD §59).
     */
    public function freshness(?Carbon $fetchedAt): FreshnessState
    {
        if ($fetchedAt === null) {
            return FreshnessState::UNKNOWN;
        }

        $hours = $fetchedAt->diffInHours(now());

        return match (true) {
            $hours <= 12 => FreshnessState::CURRENT,
            $hours <= 24 => FreshnessState::AGING,
            default => FreshnessState::STALE,
        };
    }
}
