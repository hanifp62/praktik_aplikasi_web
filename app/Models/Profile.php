<?php

namespace App\Models;

use App\Enums\ExperienceLevel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'experience_level', 'region_preference', 'bio', 'avatar_path', 'completed_at'])]
class Profile extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'experience_level' => ExperienceLevel::class,
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
