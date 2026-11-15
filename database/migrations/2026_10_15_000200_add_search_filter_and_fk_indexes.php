<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Penutup indeks performa dan pencarian katalog jalur (S2.1: GAP-07 & GAP-08):
 * 1. Indeks B-tree kolom awal untuk 11 foreign key pasca-September yang belum terindeks;
 * 2. Indeks komposit saringan katalog pada `trails`;
 * 3. Indeks GIN `pg_trgm` PostgreSQL untuk pencarian tidak peka huruf besar pada nama,
 *    titik awal, provinsi, dan wilayah gunung.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->kolomForeignKey() as $tabel => $kolomKolom) {
            Schema::table($tabel, function (Blueprint $table) use ($kolomKolom): void {
                foreach ($kolomKolom as $kolom) {
                    $table->index($kolom);
                }
            });
        }

        Schema::table('trails', function (Blueprint $table): void {
            $table->index(
                ['is_published', 'archived_at', 'technical_demand', 'camping_available'],
                'trails_published_technical_camping_idx',
            );
            $table->index(
                ['is_published', 'archived_at', 'elevation_gain_m', 'estimated_duration_minutes'],
                'trails_published_gain_duration_idx',
            );
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
            DB::statement('CREATE INDEX IF NOT EXISTS trails_name_trgm_idx ON trails USING gin (lower(name) gin_trgm_ops)');
            DB::statement("CREATE INDEX IF NOT EXISTS trails_starting_point_trgm_idx ON trails USING gin (lower(coalesce(starting_point, '')) gin_trgm_ops)");
            DB::statement('CREATE INDEX IF NOT EXISTS mountains_name_trgm_idx ON mountains USING gin (lower(name) gin_trgm_ops)');
            DB::statement("CREATE INDEX IF NOT EXISTS mountains_province_trgm_idx ON mountains USING gin (lower(coalesce(province, '')) gin_trgm_ops)");
            DB::statement("CREATE INDEX IF NOT EXISTS mountains_region_trgm_idx ON mountains USING gin (lower(coalesce(region, '')) gin_trgm_ops)");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS mountains_region_trgm_idx');
            DB::statement('DROP INDEX IF EXISTS mountains_province_trgm_idx');
            DB::statement('DROP INDEX IF EXISTS mountains_name_trgm_idx');
            DB::statement('DROP INDEX IF EXISTS trails_starting_point_trgm_idx');
            DB::statement('DROP INDEX IF EXISTS trails_name_trgm_idx');
        }

        Schema::table('trails', function (Blueprint $table): void {
            $table->dropIndex('trails_published_gain_duration_idx');
            $table->dropIndex('trails_published_technical_camping_idx');
        });

        foreach ($this->kolomForeignKey() as $tabel => $kolomKolom) {
            Schema::table($tabel, function (Blueprint $table) use ($tabel, $kolomKolom): void {
                foreach ($kolomKolom as $kolom) {
                    $table->dropIndex("{$tabel}_{$kolom}_index");
                }
            });
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function kolomForeignKey(): array
    {
        return [
            'authority_mountain' => ['mountain_id'],
            'credential_mountain' => ['mountain_id'],
            'expert_credentials' => ['issuing_authority_id', 'endorsing_authority_id', 'verified_by'],
            'mountain_follows' => ['mountain_id'],
            'report_thanks' => ['user_id'],
            'trail_considerations' => ['trail_id'],
            'trails' => ['contributed_by', 'contributed_credential_id'],
            'usability_sessions' => ['facilitator_id'],
        ];
    }
};
