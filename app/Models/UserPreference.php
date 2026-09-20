<?php

namespace App\Models;

use App\Enums\PreferredChallenge;
use App\Enums\PreferredDuration;
use App\Enums\TripType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'preferred_duration', 'preferred_trip_type', 'preferred_challenge',
    'max_elevation_gain_preference_m', 'region_preference', 'record_track',
])]
class UserPreference extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'preferred_duration' => PreferredDuration::class,
            'preferred_trip_type' => TripType::class,
            'preferred_challenge' => PreferredChallenge::class,
            'record_track' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
