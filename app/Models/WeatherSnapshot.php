<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Prakiraan untuk satu area referensi (kode adm4 BMKG), bukan untuk satu jalur.
 * Beberapa jalur di kelurahan yang sama berbagi baris yang sama.
 *
 * `forecast_at` adalah instan UTC yang dipakai untuk seluruh penyaringan dan
 * perbandingan; `local_datetime` hanya nilai tampilan asal BMKG.
 */
#[Fillable([
    'adm4_code', 'reference_area', 'forecast_at', 'local_datetime', 'weather_description',
    'temperature_c', 'humidity_percent', 'wind_speed_kmh', 'wind_direction',
    'cloud_cover_percent', 'visibility_m', 'analysis_date', 'source', 'fetched_at',
])]
class WeatherSnapshot extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'forecast_at' => 'datetime',
            'local_datetime' => 'datetime',
            'analysis_date' => 'datetime',
            'fetched_at' => 'datetime',
            'temperature_c' => 'decimal:2',
            'wind_speed_kmh' => 'decimal:2',
        ];
    }
}
