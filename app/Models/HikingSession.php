<?php

namespace App\Models;

use App\Enums\HikingSessionStatus;
use App\Models\Concerns\HasSpatialColumns;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'trip_plan_id', 'user_id', 'current_checkpoint_id', 'reached_checkpoint_sequence', 'status',
    'started_at', 'ended_at', 'location_updated_at',
])]
// BR-13: precise location stays private by default and is never serialised with the model.
#[Hidden(['last_known_location'])]
class HikingSession extends Model
{
    use HasFactory;
    use HasSpatialColumns;

    protected function casts(): array
    {
        return [
            'status' => HikingSessionStatus::class,
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'location_updated_at' => 'datetime',
        ];
    }

    public function tripPlan(): BelongsTo
    {
        return $this->belongsTo(TripPlan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function currentCheckpoint(): BelongsTo
    {
        return $this->belongsTo(Checkpoint::class, 'current_checkpoint_id');
    }
}
