<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Penjaga integritas skema untuk butir persiapan dan metrik fisik (S2.1: GAP-07 & GAP-08):
 * 1. Indeks unik parsial pada `trip_preparation_items (trip_plan_id, preparation_item_id)`
 *    ketika `preparation_item_id IS NOT NULL`, agar duplikasi ganda tertutup di basis data
 *    sambil tetap mengizinkan butir lepas tanpa templat (mis. SIMAKSI);
 * 2. Constraint `CHECK >= 0` pada PostgreSQL untuk jarak, tanjakan, turunan, durasi jalur,
 *    serta ketinggian gunung.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'DELETE FROM trip_preparation_items
             WHERE preparation_item_id IS NOT NULL
               AND id NOT IN (
                   SELECT MIN(id)
                   FROM trip_preparation_items
                   WHERE preparation_item_id IS NOT NULL
                   GROUP BY trip_plan_id, preparation_item_id
               )'
        );

        DB::statement(
            'CREATE UNIQUE INDEX IF NOT EXISTS trip_preparation_items_trip_item_unique
             ON trip_preparation_items (trip_plan_id, preparation_item_id)
             WHERE preparation_item_id IS NOT NULL'
        );

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement(
                'ALTER TABLE trails ADD CONSTRAINT trails_non_negative_metrics_chk
                 CHECK (
                     (distance_km IS NULL OR distance_km >= 0)
                     AND (elevation_gain_m IS NULL OR elevation_gain_m >= 0)
                     AND (elevation_loss_m IS NULL OR elevation_loss_m >= 0)
                     AND (estimated_duration_minutes IS NULL OR estimated_duration_minutes >= 0)
                 )'
            );

            DB::statement(
                'ALTER TABLE mountains ADD CONSTRAINT mountains_non_negative_elevation_chk
                 CHECK (elevation_mdpl IS NULL OR elevation_mdpl >= 0)'
            );
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE mountains DROP CONSTRAINT IF EXISTS mountains_non_negative_elevation_chk');
            DB::statement('ALTER TABLE trails DROP CONSTRAINT IF EXISTS trails_non_negative_metrics_chk');
        }

        DB::statement('DROP INDEX IF EXISTS trip_preparation_items_trip_item_unique');
    }
};
