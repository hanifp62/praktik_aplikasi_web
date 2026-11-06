<?php

namespace App\Models;

use App\Enums\PreparationCategory;
use App\Enums\PreparationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'trip_plan_id', 'preparation_item_id', 'category', 'label',
    'description', 'is_critical', 'status', 'status_updated_at',
])]
class TripPreparationItem extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'category' => PreparationCategory::class,
            'status' => PreparationStatus::class,
            'is_critical' => 'boolean',
            'status_updated_at' => 'datetime',
        ];
    }

    public function tripPlan(): BelongsTo
    {
        return $this->belongsTo(TripPlan::class);
    }

    public function preparationItem(): BelongsTo
    {
        return $this->belongsTo(PreparationItem::class);
    }
}
