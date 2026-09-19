<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class WeatherSnapshotFactory extends Factory
{
    public function definition(): array
    {
        return [
            'adm4_code' => '33.08.10.2001',
            'reference_area' => 'Area basecamp',
            'forecast_at' => now(),
            'local_datetime' => now(),
            'weather_description' => 'Berawan',
            'temperature_c' => 20,
            'humidity_percent' => 80,
            'wind_speed_kmh' => 5,
            'wind_direction' => 'W',
            'cloud_cover_percent' => 70,
            'visibility_m' => 10000,
            'analysis_date' => now(),
            'source' => 'BMKG',
            'fetched_at' => now(),
        ];
    }
}
