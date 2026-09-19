<?php

namespace App\Models;

use App\Enums\CompletionState;
use App\Enums\TripType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'trip_plan_id', 'trail_id', 'trail_condition_report_id', 'trip_type',
    'completion_state', 'preparation_completion_percent', 'personal_notes', 'completed_at',
])]
class HikingHistory extends Model
{
    use HasFactory;

    protected $table = 'hiking_history';

    protected function casts(): array
    {
        return [
            'trip_type' => TripType::class,
            'completion_state' => CompletionState::class,
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tripPlan(): BelongsTo
    {
        return $this->belongsTo(TripPlan::class);
    }

    public function trail(): BelongsTo
    {
        return $this->belongsTo(Trail::class);
    }

    public function conditionReport(): BelongsTo
    {
        return $this->belongsTo(TrailConditionReport::class, 'trail_condition_report_id');
    }
}
