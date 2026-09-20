<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu titik pada jejak pendakian.
 *
 * recorded_at adalah waktu perangkat merekamnya, bukan waktu server menerimanya. Kedua
 * waktu itu dapat berjarak berjam-jam karena jejaknya dikirim setelah pendaki turun dan
 * mendapat sinyal, dan yang membentuk garisnya adalah yang pertama.
 */
#[Fillable([
    'hiking_session_id', 'recorded_at', 'latitude', 'longitude', 'elevation_m', 'accuracy_m',
])]
class HikeTrackPoint extends Model
{
    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(HikingSession::class, 'hiking_session_id');
    }
}
