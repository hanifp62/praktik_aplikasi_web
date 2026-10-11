<?php

namespace Tests\Spatial;

use App\Models\Mountain;
use App\Models\PermitRequirement;

/**
 * Perbaikan 1 W3, P-3 (Important, R-024): paritas PostgreSQL untuk urutan NULL pada
 * `PermitRequirement::scopeTerverifikasiDulu()`. SQLite menaruh NULL terakhir secara bawaan;
 * PostgreSQL menaruh NULL pertama. Suite utama (SQLite) tidak dapat membuktikan urutan
 * eksplisit ini benar pada PostgreSQL; suite ini membuktikannya di mesin basis data yang
 * sesungguhnya dipakai Supabase.
 *
 * Dilewati dengan jelas tanpa SPATIAL_TEST_DSN (lihat SpatialTestCase); baru benar-benar
 * berjalan di CI atau lokal dengan Postgres terpasang.
 */
class PermitRequirementOrderTest extends SpatialTestCase
{
    public function test_a_verified_rule_wins_over_an_unverified_rule_created_later(): void
    {
        $gunung = Mountain::factory()->create();

        $terverifikasi = PermitRequirement::factory()->create([
            'mountain_id' => $gunung->id,
            'trail_id' => null,
            'authority' => 'Terverifikasi',
            'verified_at' => now()->subDays(5),
        ]);

        PermitRequirement::factory()->create([
            'mountain_id' => $gunung->id,
            'trail_id' => null,
            'authority' => 'Belum terverifikasi',
            'verified_at' => null,
        ]);

        $hasil = PermitRequirement::query()->where('mountain_id', $gunung->id)->terverifikasiDulu()->first();

        $this->assertSame($terverifikasi->id, $hasil->id);
    }

    public function test_between_two_verified_rules_the_most_recently_verified_wins(): void
    {
        $gunung = Mountain::factory()->create();

        PermitRequirement::factory()->create([
            'mountain_id' => $gunung->id, 'trail_id' => null, 'authority' => 'Lama', 'verified_at' => now()->subDays(10),
        ]);
        $terbaru = PermitRequirement::factory()->create([
            'mountain_id' => $gunung->id, 'trail_id' => null, 'authority' => 'Baru', 'verified_at' => now()->subDay(),
        ]);

        $hasil = PermitRequirement::query()->where('mountain_id', $gunung->id)->terverifikasiDulu()->first();

        $this->assertSame($terbaru->id, $hasil->id);
    }
}
