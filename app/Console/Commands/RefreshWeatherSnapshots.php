<?php

namespace App\Console\Commands;

use App\Models\Trail;
use App\Services\WeatherService;
use Illuminate\Console\Command;

class RefreshWeatherSnapshots extends Command
{
    protected $signature = 'weather:refresh {--trail= : Refresh only the given trail id}';

    protected $description = 'Fetch BMKG forecasts for the reference area of each published trail';

    public function handle(WeatherService $weather): int
    {
        $trails = Trail::query()
            ->published()
            ->whereNotNull('weather_adm4_code')
            ->when($this->option('trail'), fn ($query, $id) => $query->whereKey($id))
            ->get();

        if ($trails->isEmpty()) {
            $this->info('No published trails with a weather reference area.');

            return self::SUCCESS;
        }

        $failed = 0;

        foreach ($trails as $trail) {
            $stored = $weather->refreshForTrail($trail);

            if ($stored === 0) {
                $failed++;
                $this->warn(sprintf('No forecast stored for %s (adm4 %s).', $trail->name, $trail->weather_adm4_code));

                continue;
            }

            $this->line(sprintf('%s: %d snapshots.', $trail->name, $stored));
        }

        $this->info(sprintf('Done. %d trails processed, %d without new data.', $trails->count(), $failed));

        return self::SUCCESS;
    }
}
