<?php

namespace App\Models;

use App\Models\Concerns\HasSpatialColumns;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

#[Fillable([
    'mountain_id', 'trail_id', 'name', 'reason',
    'data_source_id', 'effective_at', 'expires_at',
])]
#[Hidden(['geometry'])]
class RestrictedArea extends Model
{
    use HasFactory;
    use HasSpatialColumns;

    protected function casts(): array
    {
        return [
            'effective_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function mountain(): BelongsTo
    {
        return $this->belongsTo(Mountain::class);
    }

    public function trail(): BelongsTo
    {
        return $this->belongsTo(Trail::class);
    }

    public function officialStatuses(): MorphMany
    {
        return $this->morphMany(OfficialStatus::class, 'statusable');
    }

    public function scopeCurrentlyEffective(Builder $query): Builder
    {
        $now = Carbon::now();

        return $query->where(fn (Builder $q) => $q->whereNull('effective_at')->orWhere('effective_at', '<=', $now))
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', $now));
    }
}
