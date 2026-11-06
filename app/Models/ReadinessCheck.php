<?php

namespace App\Models;

use App\Enums\ReadinessState;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'trip_plan_id', 'recommendation_result_id', 'computed_state', 'route_fit_snapshot',
    'preparation_state', 'official_status_snapshot', 'condition_snapshot', 'explanation',
    'pre_departure_confirmed', 'computed_at',
])]
class ReadinessCheck extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'computed_state' => ReadinessState::class,
            'route_fit_snapshot' => 'array',
            'preparation_state' => 'array',
            'official_status_snapshot' => 'array',
            'condition_snapshot' => 'array',
            'explanation' => 'array',
            'pre_departure_confirmed' => 'boolean',
            'computed_at' => 'datetime',
        ];
    }

    public function tripPlan(): BelongsTo
    {
        return $this->belongsTo(TripPlan::class);
    }

    public function recommendationResult(): BelongsTo
    {
        return $this->belongsTo(RecommendationResult::class);
    }
}
