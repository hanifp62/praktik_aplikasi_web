<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id', 'hiking_goal_id', 'engine_version', 'input_snapshot',
    'rules_evaluated', 'warnings', 'generated_at',
])]
class RecommendationRun extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'input_snapshot' => 'array',
            'rules_evaluated' => 'array',
            'warnings' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hikingGoal(): BelongsTo
    {
        return $this->belongsTo(HikingGoal::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(RecommendationResult::class)->orderBy('rank');
    }
}
