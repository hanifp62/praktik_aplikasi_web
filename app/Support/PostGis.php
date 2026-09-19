<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * PostGIS geometry columns are added with raw SQL because Laravel's schema builder has no
 * geography column type. Every call is a no-op on non-Postgres connections so the test suite
 * can still run the same migrations on SQLite (without spatial columns).
 */
class PostGis
{
    public static function supported(): bool
    {
        return DB::connection()->getDriverName() === 'pgsql';
    }

    public static function enableExtension(): void
    {
        if (self::supported()) {
            DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');
        }
    }

    /**
     * @param  string  $type  POINT, LINESTRING or POLYGON
     */
    public static function addGeography(string $table, string $column, string $type): void
    {
        if (! self::supported()) {
            return;
        }

        DB::statement("ALTER TABLE {$table} ADD COLUMN {$column} geography({$type},4326)");
        DB::statement("CREATE INDEX {$table}_{$column}_gix ON {$table} USING GIST ({$column})");
    }
}
