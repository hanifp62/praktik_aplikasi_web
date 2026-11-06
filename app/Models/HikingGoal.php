<?php

namespace App\Models;

use App\Enums\PreferredChallenge;
use App\Enums\TripType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id', 'target_date', 'region', 'trip_type', 'expected_duration_minutes',
    'preferred_challenge', 'max_elevation_gain_m', 'notes',
])]
class HikingGoal extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'target_date' => 'date',
            'trip_type' => TripType::class,
            'preferred_challenge' => PreferredChallenge::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recommendationRuns(): HasMany
    {
        return $this->hasMany(RecommendationRun::class);
    }
}
