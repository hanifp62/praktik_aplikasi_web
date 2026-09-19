<?php

namespace App\Models;

use App\Models\Concerns\HasSpatialColumns;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'name', 'slug', 'province', 'region', 'timezone', 'elevation_mdpl', 'description',
    'data_source_id', 'archived_at',
])]
#[Hidden(['location'])]
class Mountain extends Model
{
    use HasFactory;
    use HasSpatialColumns;

    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
        ];
    }

    public function trails(): HasMany
    {
        return $this->hasMany(Trail::class);
    }

    public function dataSource(): BelongsTo
    {
        return $this->belongsTo(DataSource::class);
    }

    public function officialStatuses(): MorphMany
    {
        return $this->morphMany(OfficialStatus::class, 'statusable');
    }

    public function restrictedAreas(): HasMany
    {
        return $this->hasMany(RestrictedArea::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }
}
