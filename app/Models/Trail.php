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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

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
        return $this->publishabilityReport() === [];
    }

    /**
     * PRD §65-66: area terbatas yang benar-benar memotong geometri jalur ini, ditambah
     * area yang dicatat langsung terhadap jalur atau gunungnya.
     *
     * Pencocokan geometri memerlukan PostGIS. Pada koneksi tanpa PostGIS, termasuk
     * SQLite yang dipakai suite test, bagian spasialnya dilewati dan hanya kaitan
     * eksplisit yang dikembalikan, bukan dianggap tidak ada sama sekali.
     *
     * @return Collection<int, RestrictedArea>
     */
    public function restrictedAreas(): Collection
    {
        $query = RestrictedArea::query()->currentlyEffective();

        if (static::spatialSupported()) {
            return $query
                ->where(function (Builder $group) {
                    $group->where('trail_id', $this->getKey())
                        ->orWhere('mountain_id', $this->mountain_id)
                        ->orWhereRaw(
                            'ST_Intersects(restricted_areas.geometry, (SELECT t.geometry FROM trails t WHERE t.id = ?))',
                            [$this->getKey()]
                        );
                })
                ->get();
        }

        return $query
            ->where(function (Builder $group) {
                $group->where('trail_id', $this->getKey())
                    ->orWhere('mountain_id', $this->mountain_id);
            })
            ->get();
    }

    /**
     * Jumlah baris sebuah relasi, memakai hasil withCount() bila tersedia dan hanya
     * menanyakan basis data ketika tidak.
     */
    private function countFor(string $relation): int
    {
        // withCount() menamai atributnya dari nama relasi apa adanya, tanpa
        // di-singular-kan: officialStatuses menjadi official_statuses_count.
        $attribute = Str::snake($relation).'_count';

        if (array_key_exists($attribute, $this->attributes)) {
            return (int) $this->attributes[$attribute];
        }

        return $this->{$relation}()->count();
    }

    /**
     * Apa yang masih ditunggu, dalam bahasa pendaki.
     *
     * publishabilityReport() memakai bahasa kurator: "Sumber data belum ditetapkan".
     * Pendaki tidak peduli pada nama kolom; ia ingin tahu apa yang tidak dapat ia
     * ketahui dari halaman ini, supaya tahu apa yang harus ia cari di tempat lain.
     *
     * @return array<int, string>
     */
    public function awaitingData(): array
    {
        $menunggu = [];

        if ($this->distance_km === null || $this->elevation_gain_m === null || $this->estimated_duration_minutes === null) {
            $menunggu[] = 'Jarak, elevation gain, dan estimasi durasi';
        }

        if ($this->countFor('checkpoints') === 0) {
            $menunggu[] = 'Daftar pos dan checkpoint';
        }

        if (static::spatialSupported() && $this->readGeoJson('geometry') === null) {
            $menunggu[] = 'Jalur pada peta';
        }

        if ($this->countFor('officialStatuses') === 0) {
            $menunggu[] = 'Keterangan status resmi';
        }

        return $menunggu;
    }

    /**
     * PRD §110: syarat minimum sebelum sebuah jalur boleh dipublikasikan.
     *
     * Mengembalikan daftar yang belum terpenuhi agar kurator tahu persis apa yang
     * harus dilengkapi, bukan sekadar ditolak tanpa penjelasan. Daftar kosong
     * berarti jalur lolos.
     *
     * @return array<int, string>
     */
    public function publishabilityReport(): array
    {
        $missing = [];

        if ($this->data_source_id === null) {
            $missing[] = 'Sumber data belum ditetapkan.';
        }

        if ($this->distance_km === null || $this->elevation_gain_m === null || $this->estimated_duration_minutes === null) {
            $missing[] = 'Karakteristik dasar belum lengkap (jarak, elevation gain, estimasi durasi).';
        }

        // Memakai hasil withCount() bila pemanggil sudah memuatnya. Tanpa ini setiap
        // baris pada daftar admin menambah dua query, dan bebannya tumbuh linear
        // terhadap jumlah jalur.
        if ($this->countFor('checkpoints') === 0) {
            $missing[] = 'Jalur belum memiliki checkpoint.';
        }

        if ($this->countFor('officialStatuses') === 0) {
            $missing[] = 'Status resmi belum pernah dicatat.';
        }

        // Geometri hanya dapat diperiksa pada koneksi berkemampuan PostGIS; pada
        // SQLite kolomnya memang tidak ada sehingga syarat ini dilewati.
        if (static::spatialSupported() && $this->readGeoJson('geometry') === null) {
            $missing[] = 'Geometri jalur belum tersedia.';
        }

        return $missing;
    }
}
