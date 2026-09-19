<?php

namespace App\Models;

use App\Enums\RouteFitLabel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'recommendation_run_id', 'trail_id', 'eligible', 'label', 'internal_score',
    'matched_factors', 'failed_rules', 'warnings', 'explanation', 'rank',
])]
// BR-09: internal_score is for ranking and audit only and must never reach the user as a score.
#[Hidden(['internal_score'])]
class RecommendationResult extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'eligible' => 'boolean',
            'label' => RouteFitLabel::class,
            'internal_score' => 'float',
            'matched_factors' => 'array',
            'failed_rules' => 'array',
            'warnings' => 'array',
            'explanation' => 'array',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(RecommendationRun::class, 'recommendation_run_id');
    }

    public function trail(): BelongsTo
    {
        return $this->belongsTo(Trail::class);
    }

    public function scopeEligible(Builder $query): Builder
    {
        return $query->where('eligible', true);
    }
}
