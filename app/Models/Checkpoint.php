<?php

namespace App\Models;

use App\Enums\CheckpointType;
use App\Models\Concerns\HasSpatialColumns;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'trail_id', 'trail_segment_id', 'sequence', 'name', 'latitude', 'longitude',
    'checkpoint_type', 'elevation_m', 'notes',
])]
#[Hidden(['location'])]
class Checkpoint extends Model
{
    use HasFactory;
    use HasSpatialColumns;

    protected function casts(): array
    {
        return [
            'checkpoint_type' => CheckpointType::class,
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    /**
     * Menulis koordinat ke kolom biasa dan ke kolom geografi sekaligus.
     *
     * Kolom biasa adalah sumber yang portabel dan terbaca; kolom geografi yang membuat
     * query spasial dan index GIST bekerja. Keduanya harus selalu sejalan, jadi hanya
     * ada satu jalan masuk.
     */
    public function setCoordinates(?float $latitude, ?float $longitude): void
    {
        $this->forceFill(['latitude' => $latitude, 'longitude' => $longitude])->save();

        if ($latitude !== null && $longitude !== null) {
            $this->writePoint('location', $latitude, $longitude);
        }
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function trail(): BelongsTo
    {
        return $this->belongsTo(Trail::class);
    }

    public function segment(): BelongsTo
    {
        return $this->belongsTo(TrailSegment::class, 'trail_segment_id');
    }
}
