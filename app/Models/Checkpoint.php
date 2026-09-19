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
    'trail_id', 'trail_segment_id', 'sequence', 'name',
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
        ];
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
