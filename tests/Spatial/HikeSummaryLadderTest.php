<?php

namespace Tests\Spatial;

use App\Enums\CompletionState;
use App\Enums\TripStatus;
use App\Models\HikingHistory;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\Community\HasilPendakian;
use Illuminate\Support\Carbon;

/**
 * K-1 (tinjauan W6 Tugas 6, R-024): paritas PostgreSQL untuk kueri tangga
 * HasilPendakian::tangga(), yaitu join trails dan mountains, groupBy empat kolom (termasuk
 * alias mountains.name as gunung), orderByDesc('trails.elevation_gain_m') dengan pemecah seri
 * orderBy('trails.id'), limit, dan perbandingan completed_at <. Pola yang sama dengan
 * Dashboard/TanggaTanjakan.php, yang juga tidak punya test Spatial (ruling W6-6p).
 *
 * Suite utama (SQLite) tidak dapat membuktikan determinisme pemecah serinya: pada kueri
 * ber-ORDER BY, SQLite memecah seri dengan rowid naik berapa pun urutan pembuatan atau id
 * eksplisit (batas R-024 yang sudah tercatat, W6-4a2), sehingga dua jalur bertanjakan sama
 * yang dibuat dengan id A < B akan TETAP terurut A dulu di SQLite walau orderBy('trails.id')
 * dibuang sepenuhnya dari kueri. Suite ini membuktikan urutannya di mesin basis data yang
 * sesungguhnya dipakai Supabase.
 *
 * Dilewati dengan jelas tanpa SPATIAL_TEST_DSN (lihat SpatialTestCase); baru benar-benar
 * berjalan di CI atau lokal dengan Postgres terpasang.
 */
class HikeSummaryLadderTest extends SpatialTestCase
{
    /**
     * Dua jalur bernama sama, bertanjakan sama persis, di gunung berbeda (skenario ruling
     * W6-6a). Keduanya harus tetap sebagai dua anak tangga terpisah dengan id dan gunung yang
     * benar, terurut oleh id (pemecah seri) ketika tanjakannya seri.
     *
     * Fixture disusun BERLAWANAN dengan urutan yang diharapkan (ruling W6-6p, daftar periksa
     * butir 1), supaya urutan fisik baris tidak kebetulan sama dengan hasil pemecah seri: jalur
     * yang diharapkan kedua (id 9002) dibuat lebih dulu, begitu juga trip dan riwayatnya (id lebih
     * kecil), dan completed_at-nya lebih awal. Yang diharapkan pertama (id 9001) dibuat terakhir
     * dengan completed_at terbaru. Id jalur sendiri tidak dapat dibalik karena id itulah kunci
     * pemecah serinya, jadi id eksplisit dipakai untuk memisahkannya dari urutan pembuatan.
     */
    public function test_two_trails_with_the_same_gain_keep_a_deterministic_tie_break_order(): void
    {
        $budi = User::factory()->create();
        $gunungB = Mountain::factory()->create(['name' => 'Gunung Dua']);
        $gunungA = Mountain::factory()->create(['name' => 'Gunung Satu']);
        $jalurB = Trail::factory()->create(['id' => 9002, 'name' => 'Jalur Sama', 'mountain_id' => $gunungB->id, 'elevation_gain_m' => 900]);
        $jalurA = Trail::factory()->create(['id' => 9001, 'name' => 'Jalur Sama', 'mountain_id' => $gunungA->id, 'elevation_gain_m' => 900]);

        foreach ([[$jalurB, '2026-07-01 09:00:00'], [$jalurA, '2026-07-02 09:00:00']] as [$jalur, $selesai]) {
            $trip = TripPlan::factory()->create(['user_id' => $budi->id, 'trail_id' => $jalur->id, 'status' => TripStatus::COMPLETED->value]);

            HikingHistory::create([
                'user_id' => $budi->id,
                'trip_plan_id' => $trip->id,
                'trail_id' => $jalur->id,
                'trip_type' => $trip->trip_type->value,
                'completion_state' => CompletionState::COMPLETED->value,
                'completed_at' => Carbon::parse($selesai, 'UTC'),
            ]);
        }

        $trailIni = Trail::factory()->create(['name' => 'Gede', 'elevation_gain_m' => 1660]);
        $tripIni = TripPlan::factory()->create(['user_id' => $budi->id, 'trail_id' => $trailIni->id, 'status' => TripStatus::COMPLETED->value]);

        HikingHistory::create([
            'user_id' => $budi->id,
            'trip_plan_id' => $tripIni->id,
            'trail_id' => $trailIni->id,
            'trip_type' => $tripIni->trip_type->value,
            'completion_state' => CompletionState::COMPLETED->value,
            'completed_at' => Carbon::parse('2026-09-15 09:00:00', 'UTC'),
        ]);

        $props = app(HasilPendakian::class)->untuk($tripIni->fresh(), $budi);

        $this->assertCount(3, $props['tangga']['baris']);
        $this->assertSame($jalurA->id, $props['tangga']['baris'][0]['id']);
        $this->assertSame('Gunung Satu', $props['tangga']['baris'][0]['gunung']);
        $this->assertSame($jalurB->id, $props['tangga']['baris'][1]['id']);
        $this->assertSame('Gunung Dua', $props['tangga']['baris'][1]['gunung']);
        $this->assertSame($trailIni->id, $props['tangga']['baris'][2]['id']);
    }
}
