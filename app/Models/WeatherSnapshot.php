<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'trail_id', 'adm4_code', 'reference_area', 'local_datetime', 'weather_description',
    'temperature_c', 'humidity_percent', 'wind_speed_kmh', 'wind_direction',
    'cloud_cover_percent', 'visibility_m', 'analysis_date', 'source', 'fetched_at',
])]
class WeatherSnapshot extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'local_datetime' => 'datetime',
            'analysis_date' => 'datetime',
            'fetched_at' => 'datetime',
            'temperature_c' => 'decimal:2',
            'wind_speed_kmh' => 'decimal:2',
        ];
    }

    public function trail(): BelongsTo
    {
        return $this->belongsTo(Trail::class);
    }
}
