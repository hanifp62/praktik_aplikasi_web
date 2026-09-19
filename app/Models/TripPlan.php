<?php

namespace App\Models;

use App\Enums\PreparationStatus;
use App\Enums\TripStatus;
use App\Enums\TripType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'user_id', 'trail_id', 'hiking_goal_id', 'name', 'planned_date',
    'start_time', 'trip_type', 'notes', 'status', 'completed_at',
])]
class TripPlan extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'planned_date' => 'date',
            'trip_type' => TripType::class,
            'status' => TripStatus::class,
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trail(): BelongsTo
    {
        return $this->belongsTo(Trail::class);
    }

    public function hikingGoal(): BelongsTo
    {
        return $this->belongsTo(HikingGoal::class);
    }

    public function preparationItems(): HasMany
    {
        return $this->hasMany(TripPreparationItem::class);
    }

    public function readinessChecks(): HasMany
    {
        return $this->hasMany(ReadinessCheck::class);
    }

    public function latestReadinessCheck(): HasOne
    {
        return $this->hasOne(ReadinessCheck::class)->latestOfMany();
    }

    public function hikingSession(): HasOne
    {
        return $this->hasOne(HikingSession::class);
    }

    public function history(): HasOne
    {
        return $this->hasOne(HikingHistory::class);
    }

    public function preparationCompletionPercent(): int
    {
        $items = $this->preparationItems;
        $applicable = $items->where('status', '!=', PreparationStatus::NOT_APPLICABLE);

        if ($applicable->isEmpty()) {
            return 0;
        }

        $confirmed = $applicable->where('status', PreparationStatus::CONFIRMED)->count();

        return (int) round($confirmed / $applicable->count() * 100);
    }

    public function hasUnconfirmedCriticalItems(): bool
    {
        return $this->preparationItems
            ->where('is_critical', true)
            ->contains(fn (TripPreparationItem $item) => $item->status === PreparationStatus::NOT_CONFIRMED);
    }
}
