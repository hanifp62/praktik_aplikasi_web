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
        // Prakiraan BMKG melekat pada kode wilayah, bukan pada jalur. Beberapa jalur di
        // satu kelurahan berbagi kode yang sama, jadi mengambil per jalur berarti
        // memanggil area yang sama berulang kali — memboroskan kuota 60 permintaan per
        // menit per IP dan menulis ulang baris yang sama.
        $areas = Trail::query()
            ->published()
            ->whereNotNull('weather_adm4_code')
            ->when($this->option('trail'), fn ($query, $id) => $query->whereKey($id))
            ->get()
            ->unique('weather_adm4_code')
            ->values();

        if ($areas->isEmpty()) {
            $this->info('Tidak ada jalur terpublikasi dengan area referensi cuaca.');

            return self::SUCCESS;
        }

        $failed = 0;

        foreach ($areas as $trail) {
            $stored = $weather->refreshForTrail($trail);

            if ($stored === 0) {
                $failed++;
                $this->warn(sprintf('Tidak ada prakiraan tersimpan untuk area %s.', $trail->weather_adm4_code));

                continue;
            }

            $this->line(sprintf('%s: %d prakiraan.', $trail->weather_adm4_code, $stored));
        }

        $this->info(sprintf(
            'Selesai. %d area diproses, %d tanpa data baru.',
            $areas->count(),
            $failed
        ));

        return self::SUCCESS;
    }
}
