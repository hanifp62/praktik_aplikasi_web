<?php

namespace Tests\Spatial;

use App\Models\Checkpoint;
use App\Models\Trail;
use App\Services\Trails\PosSepanjangJalur;
use Illuminate\Support\Facades\DB;

/**
 * Jarak pos dari awal diukur sepanjang garis rute (W3 Tugas 6), bukan garis lurus. Hanya
 * PostGIS yang dapat membuktikannya; di suite SQLite layanan ini sengaja tidak menghasilkan
 * angka apa pun (TrailDetailPageTest menjaga sisi itu).
 */
class PosSepanjangJalurTest extends SpatialTestCase
{
    public function test_a_post_is_measured_along_the_route_not_in_a_straight_line(): void
    {
        // Garis berbentuk U: tiga sisi masing-masing sekitar 1,1 km. Pos di ujung kanan atas
        // hanya sekitar 1,1 km garis lurus dari awal, tetapi sekitar 3,3 km sepanjang rute.
        $trail = Trail::factory()->create();
        $trail->writeLineString('geometry', [[110.0000, -7.0000], [110.0000, -7.0100], [110.0100, -7.0100], [110.0100, -7.0000]]);
        $awal = Checkpoint::factory()->for($trail)->create(['sequence' => 1]);
        $awal->setCoordinates(-7.0000, 110.0000);
        $akhir = Checkpoint::factory()->for($trail)->create(['sequence' => 2]);
        $akhir->setCoordinates(-7.0000, 110.0100);

        $km = app(PosSepanjangJalur::class)->km($trail->fresh());

        $this->assertEqualsWithDelta(0.0, $km[$awal->id], 0.05);
        $this->assertEqualsWithDelta(3.3, $km[$akhir->id], 0.15);
    }

    public function test_a_post_without_coordinates_or_a_trail_without_a_line_gets_no_distance(): void
    {
        $bergaris = Trail::factory()->create();
        $bergaris->writeLineString('geometry', [[110.0, -7.0], [110.0, -7.01]]);
        Checkpoint::factory()->for($bergaris)->create(['sequence' => 1]);

        $tanpaGaris = Trail::factory()->create();
        $pos = Checkpoint::factory()->for($tanpaGaris)->create(['sequence' => 1]);
        $pos->setCoordinates(-7.0, 110.0);

        $this->assertSame([], app(PosSepanjangJalur::class)->km($bergaris->fresh()));
        $this->assertSame([], app(PosSepanjangJalur::class)->km($tanpaGaris->fresh()));
    }

    public function test_one_query_serves_every_post(): void
    {
        $trail = Trail::factory()->create();
        $trail->writeLineString('geometry', [[110.0, -7.0], [110.0, -7.05]]);

        foreach (range(1, 12) as $i) {
            Checkpoint::factory()->for($trail)->create(['sequence' => $i])->setCoordinates(-7.0 - $i * 0.004, 110.0);
        }

        DB::enableQueryLog();
        DB::flushQueryLog();
        $km = app(PosSepanjangJalur::class)->km($trail->fresh());
        $kueri = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertCount(12, $km);
        $this->assertSame(1, $kueri);
    }
}
