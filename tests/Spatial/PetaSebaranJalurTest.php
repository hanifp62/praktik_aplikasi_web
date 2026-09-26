<?php

namespace Tests\Spatial;

use App\Models\Trail;
use App\Services\Trails\PetaSebaranJalur;
use Illuminate\Support\Facades\DB;

/**
 * Sisi positif peta sebaran Jelajah: garis jalur dari PostGIS, satu query per halaman.
 * Sisi larangannya (tidak pernah memakai koordinat gunung) dijaga TrailIndexMapTest.
 */
class PetaSebaranJalurTest extends SpatialTestCase
{
    public function test_a_trail_with_geometry_comes_back_as_a_line_with_its_start_point(): void
    {
        $trail = $this->jalurBergaris([[112.9500, -7.9970], [112.9505, -7.9975], [112.9510, -7.9980]]);

        $hasil = app(PetaSebaranJalur::class)->untuk(collect([$trail]));

        $this->assertCount(1, $hasil);
        $this->assertSame($trail->id, $hasil[0]['id']);
        $this->assertSame('LineString', $hasil[0]['geometri']['type']);
        $this->assertEqualsWithDelta(112.95, $hasil[0]['awal'][0], 0.000001);
        $this->assertEqualsWithDelta(-7.997, $hasil[0]['awal'][1], 0.000001);
    }

    public function test_a_trail_without_geometry_is_left_off_the_map(): void
    {
        $bergaris = $this->jalurBergaris([[112.9500, -7.9970], [112.9510, -7.9980]]);
        $kosong = Trail::factory()->create();

        $hasil = app(PetaSebaranJalur::class)->untuk(collect([$kosong, $bergaris]));

        $this->assertSame([$bergaris->id], array_column($hasil, 'id'));
    }

    public function test_one_query_serves_a_whole_page(): void
    {
        $sedikit = collect(range(1, 2))->map(fn () => $this->jalurBergaris([[112.95, -7.997], [112.951, -7.998]]));
        $banyak = $sedikit->merge(collect(range(1, 10))->map(fn () => $this->jalurBergaris([[112.95, -7.997], [112.951, -7.998]])));

        DB::enableQueryLog();
        DB::flushQueryLog();
        app(PetaSebaranJalur::class)->untuk($sedikit);
        $querySedikit = count(DB::getQueryLog());

        DB::flushQueryLog();
        app(PetaSebaranJalur::class)->untuk($banyak);
        $queryBanyak = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(1, $querySedikit);
        $this->assertSame($querySedikit, $queryBanyak, 'Jumlah query tumbuh mengikuti jumlah jalur (§96).');
    }

    public function test_the_order_follows_the_list(): void
    {
        $a = $this->jalurBergaris([[112.95, -7.997], [112.951, -7.998]]);
        $b = $this->jalurBergaris([[110.44, -7.45], [110.441, -7.451]]);

        $hasil = app(PetaSebaranJalur::class)->untuk(collect([$b, $a]));

        $this->assertSame([$b->id, $a->id], array_column($hasil, 'id'));
    }

    /**
     * @param  array<int, array{0: float, 1: float}>  $titik
     */
    private function jalurBergaris(array $titik): Trail
    {
        $trail = Trail::factory()->create();
        $trail->writeLineString('geometry', $titik);

        return $trail->fresh();
    }
}
