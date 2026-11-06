<?php

namespace Tests\Feature;

use App\Models\Mountain;
use App\Support\Timezone;
use Database\Seeders\MountainSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menjaga data enam belas gunung yang menjadi dasar seluruh jalur.
 *
 * Koordinat dan ketinggian gunung adalah data yang paling mudah salah tanpa terlihat
 * salah: sebuah angka yang meleset satu derajat tetap terbaca wajar, tetapi menempatkan
 * gunungnya ratusan kilometer dari tempatnya. Tiga sumber berbeda tersandung pada
 * "Gunung Prau" saat data ini dikumpulkan.
 */
class MountainSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MountainSeeder::class);
    }

    public function test_it_seeds_every_requested_mountain(): void
    {
        $this->assertSame(16, Mountain::count());
    }

    public function test_running_it_twice_does_not_duplicate(): void
    {
        $this->seed(MountainSeeder::class);

        $this->assertSame(16, Mountain::count());
    }

    /**
     * Jawa dan Lombok seluruhnya berada di kotak ini. Koordinat di luarnya berarti
     * gunung yang salah, bukan sekadar angka yang kurang tepat.
     */
    public function test_every_mountain_sits_inside_java_or_lombok(): void
    {
        $diLuar = Mountain::all()
            ->reject(fn (Mountain $m) => $m->latitude > -9.3 && $m->latitude < -5.7
                && $m->longitude > 105.0 && $m->longitude < 117.0)
            ->pluck('name')
            ->all();

        $this->assertSame([], $diLuar, 'Di luar kotak Jawa-Lombok: '.implode(', ', $diLuar));
    }

    public function test_every_mountain_has_coordinates_and_an_elevation(): void
    {
        $kurang = Mountain::all()
            ->reject(fn (Mountain $m) => $m->hasCoordinates() && $m->elevation_mdpl !== null)
            ->pluck('name')
            ->all();

        $this->assertSame([], $kurang);
    }

    /**
     * Semeru 3676 mdpl adalah puncak tertinggi Jawa dan Rinjani 3726 mdpl tertinggi
     * kedua di Indonesia. Angka di luar rentang ini menandakan satuan atau sumber yang
     * tertukar.
     */
    public function test_elevations_stay_within_a_believable_range(): void
    {
        foreach (Mountain::all() as $gunung) {
            $this->assertGreaterThan(1000, $gunung->elevation_mdpl, $gunung->name);
            $this->assertLessThanOrEqual(3726, $gunung->elevation_mdpl, $gunung->name);
        }
    }

    /**
     * Dua angka yang sempat keliru di sumbernya, dikoreksi lewat Wikipedia Indonesia.
     * Dikunci di sini supaya koreksinya tidak hilang kalau seeder disusun ulang.
     */
    public function test_the_two_corrected_values_stay_corrected(): void
    {
        $prau = Mountain::where('slug', 'gunung-prau')->firstOrFail();
        $raung = Mountain::where('slug', 'gunung-raung')->firstOrFail();

        $this->assertSame(2590, $prau->elevation_mdpl, 'Wikidata menunjuk gunung lain untuk nama ini.');
        $this->assertEqualsWithDelta(-7.18694, $prau->latitude, 0.01, 'Prau ada di Dieng, bukan di Jawa Timur.');

        $this->assertSame(3344, $raung->elevation_mdpl, 'Wikidata mencatat 3260, dan itu keliru.');
    }

    /**
     * Rinjani di Lombok memakai WITA; seluruh gunung Jawa memakai WIB. Zona yang salah
     * menggeser jam prakiraan cuaca satu jam penuh.
     */
    public function test_the_timezone_follows_the_island(): void
    {
        $rinjani = Mountain::where('slug', 'gunung-rinjani')->firstOrFail();

        $this->assertSame('Asia/Makassar', $rinjani->timezone);
        $this->assertSame('WITA', Timezone::label($rinjani->timezone));

        foreach (Mountain::where('province', 'like', 'Jawa%')->get() as $gunung) {
            $this->assertSame('Asia/Jakarta', $gunung->timezone, $gunung->name);
        }
    }

    public function test_every_mountain_records_where_its_data_came_from(): void
    {
        $tanpaSumber = Mountain::whereNull('data_source_id')->pluck('name')->all();

        $this->assertSame([], $tanpaSumber);
    }

    /**
     * Data Wikidata dan Wikipedia adalah data komunitas. Menandainya OFFICIAL akan
     * membuat §60 dan §92 berbohong tentang otoritasnya.
     */
    public function test_the_source_is_marked_as_community_data(): void
    {
        $sumber = Mountain::firstOrFail()->dataSource;

        $this->assertSame('COMMUNITY', $sumber->source_type->value);
        $this->assertNotNull($sumber->retrieved_at);
    }

    public function test_the_three_provinces_and_nusa_tenggara_are_all_represented(): void
    {
        $provinsi = Mountain::query()->distinct()->pluck('province')->sort()->values()->all();

        $this->assertSame(
            ['Jawa Barat', 'Jawa Tengah', 'Jawa Timur', 'Nusa Tenggara Barat'],
            $provinsi
        );
    }
}
