<?php

namespace App\Models;

use App\Enums\OfficialStatusValue;
use App\Enums\StatusScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

#[Fillable([
    'statusable_type', 'statusable_id', 'scope', 'status', 'data_source_id', 'source', 'source_url',
    'published_at', 'fetched_at', 'verified_at', 'effective_at', 'expires_at', 'reason', 'notes', 'recorded_by',
])]
class OfficialStatus extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => OfficialStatusValue::class,
            'scope' => StatusScope::class,
            'published_at' => 'datetime',
            'fetched_at' => 'datetime',
            'verified_at' => 'datetime',
            'effective_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function statusable(): MorphTo
    {
        return $this->morphTo();
    }

    public function dataSource(): BelongsTo
    {
        return $this->belongsTo(DataSource::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function scopeCurrentlyEffective(Builder $query): Builder
    {
        $now = Carbon::now();

        return $query->where(fn (Builder $q) => $q->whereNull('effective_at')->orWhere('effective_at', '<=', $now))
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', $now));
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
