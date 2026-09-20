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
    'data_source_id', 'archived_at', 'latitude', 'longitude',
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
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    /**
     * Menulis koordinat ke kolom biasa sekaligus ke kolom geografi, mengikuti pola yang
     * sudah dipakai Checkpoint.
     *
     * Kolom geografi dipakai kueri spasial dan diam pada koneksi tanpa PostGIS; kolom
     * biasa yang dibaca aplikasi, sehingga koordinat tetap tersedia di mana pun.
     */
    public function setCoordinates(?float $latitude, ?float $longitude): void
    {
        $this->forceFill(['latitude' => $latitude, 'longitude' => $longitude])->save();

        if ($latitude !== null && $longitude !== null) {
            $this->writePoint('location', $latitude, $longitude);
        }
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
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
