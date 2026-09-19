<?php

namespace App\Models;

use App\Enums\NavigationComplexity;
use App\Enums\TechnicalDemand;
use App\Enums\TerrainCharacter;
use App\Enums\WaterAvailability;
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
    'mountain_id', 'name', 'slug', 'description', 'distance_km', 'elevation_gain_m',
    'elevation_loss_m', 'estimated_duration_minutes', 'technical_demand', 'terrain_character',
    'navigation_complexity', 'water_availability', 'camping_available', 'starting_point',
    'weather_adm4_code', 'weather_reference_area', 'data_source_id', 'is_published', 'archived_at',
])]
#[Hidden(['geometry'])]
class Trail extends Model
{
    use HasFactory;
    use HasSpatialColumns;

    protected function casts(): array
    {
        return [
            'distance_km' => 'decimal:2',
            'technical_demand' => TechnicalDemand::class,
            'navigation_complexity' => NavigationComplexity::class,
            'water_availability' => WaterAvailability::class,
            'terrain_character' => 'array',
            'camping_available' => 'boolean',
            'is_published' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }

    public function mountain(): BelongsTo
    {
        return $this->belongsTo(Mountain::class);
    }

    public function segments(): HasMany
    {
        return $this->hasMany(TrailSegment::class)->orderBy('sequence');
    }

    public function checkpoints(): HasMany
    {
        return $this->hasMany(Checkpoint::class)->orderBy('sequence');
    }

    public function officialStatuses(): MorphMany
    {
        return $this->morphMany(OfficialStatus::class, 'statusable');
    }

    public function conditionReports(): HasMany
    {
        return $this->hasMany(TrailConditionReport::class);
    }

    public function weatherSnapshots(): HasMany
    {
        return $this->hasMany(WeatherSnapshot::class);
    }

    public function dataSource(): BelongsTo
    {
        return $this->belongsTo(DataSource::class);
    }

    public function preparationTemplates(): HasMany
    {
        return $this->hasMany(PreparationTemplate::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->whereNull('archived_at');
    }

    /**
     * @return array<int, TerrainCharacter>
     */
    public function terrainTypes(): array
    {
        return array_values(array_filter(array_map(
            fn (string $value) => TerrainCharacter::tryFrom($value),
            $this->terrain_character ?? []
        )));
    }

    /**
     * PRD §110: a trail is only production-ready with source, geometry, characteristics,
     * checkpoints and status metadata.
     */
    public function meetsPublishingRequirements(): bool
    {
        return $this->data_source_id !== null
            && $this->distance_km !== null
            && $this->elevation_gain_m !== null
            && $this->estimated_duration_minutes !== null
            && $this->checkpoints()->exists()
            && $this->officialStatuses()->exists();
    }
}
