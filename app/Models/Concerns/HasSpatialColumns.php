<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Read/write helpers for PostGIS geography columns, which Eloquent cannot cast natively.
 * Every method degrades to a no-op/null on connections without PostGIS (the SQLite test DB).
 */
trait HasSpatialColumns
{
    public static function spatialSupported(): bool
    {
        return DB::connection()->getDriverName() === 'pgsql';
    }

    public function writePoint(string $column, float $latitude, float $longitude): void
    {
        if (! static::spatialSupported()) {
            return;
        }

        DB::statement(
            "UPDATE {$this->getTable()} SET {$column} = ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography WHERE id = ?",
            [$longitude, $latitude, $this->getKey()]
        );
    }

    /**
     * @param  array<int, array{0: float, 1: float}>  $coordinates  list of [longitude, latitude] pairs
     */
    public function writeLineString(string $column, array $coordinates): void
    {
        if (! static::spatialSupported() || count($coordinates) < 2) {
            return;
        }

        $points = implode(',', array_map(
            fn (array $pair) => sprintf('%F %F', $pair[0], $pair[1]),
            $coordinates
        ));

        DB::statement(
            "UPDATE {$this->getTable()} SET {$column} = ST_GeogFromText(?) WHERE id = ?",
            ["SRID=4326;LINESTRING({$points})", $this->getKey()]
        );
    }

    /**
     * @return array<string, mixed>|null decoded GeoJSON geometry
     */
    public function readGeoJson(string $column): ?array
    {
        if (! static::spatialSupported()) {
            return null;
        }

        $row = DB::selectOne(
            "SELECT ST_AsGeoJSON({$column}) AS geojson FROM {$this->getTable()} WHERE id = ?",
            [$this->getKey()]
        );

        return $row?->geojson ? json_decode($row->geojson, true) : null;
    }

    /**
     * Rows whose $column lies within $meters of the given coordinate.
     * Requires PostGIS; returns an unmodified query elsewhere.
     */
    public function scopeNearby(Builder $query, string $column, float $latitude, float $longitude, int $meters): Builder
    {
        if (! static::spatialSupported()) {
            return $query;
        }

        return $query->whereRaw(
            "ST_DWithin({$column}, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)",
            [$longitude, $latitude, $meters]
        );
    }
}
