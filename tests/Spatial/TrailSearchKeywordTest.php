<?php

namespace Tests\Spatial;

use App\Models\Checkpoint;
use App\Models\DataSource;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Services\Trails\TrailSearch;

/**
 * I2 tinjauan akhir W3 (R-024): pencarian kata kunci Jelajah di PostgreSQL. `LIKE` peka huruf
 * besar di PostgreSQL tetapi tidak di SQLite, jadi pembaca yang mengetik "rinjani" tidak
 * menemukan "Rinjani" di Supabase walau suite SQLite hijau. Kembaran test ini di SQLite:
 * TrailIndexSearchTest::test_keyword_search_ignores_letter_case_and_treats_wildcards_literally.
 *
 * Dilewati dengan jelas tanpa SPATIAL_TEST_DSN (lihat SpatialTestCase); berjalan di CI.
 */
class TrailSearchKeywordTest extends SpatialTestCase
{
    /**
     * @return array<int, string>
     */
    private function nama(string $kataKunci): array
    {
        return TrailSearch::dariInput(['search' => $kataKunci])->kandidat(10)->pluck('name')->all();
    }

    /**
     * Menulis geometri sebelum menerbitkan, pola `jalurBergaris` di PetaSebaranJalurTest
     * (ruling W3-F6): pencarian kata kunci atas jalur terbit harus mencari jalur yang
     * sungguh dapat diterbitkan di PostgreSQL. `Trail::factory()->published()` menyalakan
     * `is_published` lewat `update()` tanpa menulis geometri, sehingga `hasGeometry()`
     * gagal dan gerbang publikasi (Trail.php:72-81) melempar PublicationGateViolation.
     */
    private function jalurTerbit(Mountain $gunung, string $nama): Trail
    {
        $trail = Trail::factory()->for($gunung)->create([
            'name' => $nama,
            'data_source_id' => DataSource::factory()->create()->id,
        ]);

        $trail->writeLineString('geometry', [[112.9500, -7.9970], [112.9505, -7.9975], [112.9510, -7.9980]]);

        Checkpoint::factory()->for($trail)->create(['sequence' => 1]);
        OfficialStatus::factory()->create([
            'statusable_type' => $trail->getMorphClass(),
            'statusable_id' => $trail->getKey(),
        ]);

        $trail->fresh()->update(['is_published' => true]);

        return $trail->fresh();
    }

    public function test_keyword_search_ignores_letter_case_and_treats_wildcards_literally(): void
    {
        $this->jalurTerbit(Mountain::factory()->create(['name' => 'Rinjani']), 'Jalur Sembalun');
        $this->jalurTerbit(Mountain::factory()->create(['name' => 'Gunung Gede']), 'Jalur Cibodas');
        $this->jalurTerbit(Mountain::factory()->create(['name' => 'Gunung Uji']), 'Jalur 100%_Uji');

        $this->assertSame(['Jalur Sembalun'], $this->nama('rinjani'));
        $this->assertSame(['Jalur Cibodas'], $this->nama('GEDE'));
        $this->assertSame(['Jalur Cibodas'], $this->nama('cibodas'));
        $this->assertSame(['Jalur 100%_Uji'], $this->nama('%'));
        $this->assertSame(['Jalur 100%_Uji'], $this->nama('_'));
    }

    public function test_candidates_with_the_same_name_keep_one_order(): void
    {
        $gunung = Mountain::factory()->create();
        $pertama = $this->jalurTerbit($gunung, 'Jalur Kembar');
        $kedua = $this->jalurTerbit($gunung, 'Jalur Kembar');

        $this->assertSame([$pertama->id, $kedua->id], TrailSearch::dariInput([])->kandidat(10)->pluck('id')->all());
    }
}
