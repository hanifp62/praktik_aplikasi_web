<?php

namespace App\Models;

use App\Enums\NavigationExperience;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'completed_hikes_count', 'terrain_experience', 'navigation_experience',
    'longest_hike_duration_minutes', 'highest_elevation_gain_m', 'notes',
])]
class UserExperience extends Model
{
    use HasFactory;

    protected $table = 'user_experience';

    protected function casts(): array
    {
        return [
            'terrain_experience' => 'array',
            'navigation_experience' => NavigationExperience::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
