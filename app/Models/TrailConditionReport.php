<?php

namespace App\Models;

use App\Enums\ConditionTag;
use App\Enums\ModerationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'trail_id', 'trail_segment_id', 'user_id', 'hike_date', 'condition_tags',
    'photo_path', 'note', 'moderation_status', 'moderated_by', 'moderated_at',
])]
class TrailConditionReport extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'hike_date' => 'date',
            'condition_tags' => 'array',
            'moderation_status' => ModerationStatus::class,
            'moderated_at' => 'datetime',
        ];
    }

    public function trail(): BelongsTo
    {
        return $this->belongsTo(Trail::class);
    }

    public function segment(): BelongsTo
    {
        return $this->belongsTo(TrailSegment::class, 'trail_segment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function moderatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    public function moderationActions(): MorphMany
    {
        return $this->morphMany(ModerationAction::class, 'moderatable');
    }

    public function scopeVisibleToPublic(Builder $query): Builder
    {
        return $query->where('moderation_status', ModerationStatus::APPROVED->value);
    }

    /**
     * @return array<int, ConditionTag>
     */
    public function tags(): array
    {
        return array_values(array_filter(array_map(
            fn (string $value) => ConditionTag::tryFrom($value),
            $this->condition_tags ?? []
        )));
    }

    /**
     * PRD §51: freshness is expressed relative to the hike date, not just created_at.
     */
    public function daysSinceHike(): int
    {
        return (int) $this->hike_date->diffInDays(now());
    }
}
