<?php

namespace Tests\Feature;

use App\Models\Mountain;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Peta sebaran Jelajah digerbang data (keputusan F2, features/01).
 *
 * Yang digambar adalah bentuk jalur, bukan koordinat gunungnya. Suite ini berjalan di
 * SQLite tempat kolom geometri tidak ada, jadi di sini yang dapat dibuktikan adalah
 * sisi larangannya: koordinat gunung tidak pernah lagi menjadi penanda. Sisi
 * positifnya (garis jalur dari PostGIS) dijaga tests/Spatial/PetaSebaranJalurTest.
 */
class TrailIndexMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_map_never_falls_back_to_mountain_coordinates(): void
    {
        $gunung = Mountain::factory()->create(['name' => 'Gunung Uji', 'latitude' => -7.45, 'longitude' => 110.44]);
        Trail::factory()->published()->for($gunung)->create(['name' => 'Jalur A']);

        $this->actingAs(User::factory()->create())
            ->get(route('trails.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Trails/Index')
                ->has('petaSebaran', 0)
                ->where('trails.data.0.name', 'Jalur A'));
    }

    public function test_drafts_stay_in_their_own_section_and_off_the_map(): void
    {
        $gunung = Mountain::factory()->create(['latitude' => -7.45, 'longitude' => 110.44]);
        Trail::factory()->unpublished()->for($gunung)->create(['name' => 'Jalur Draft Berkoordinat']);

        $this->actingAs(User::factory()->create())
            ->get(route('trails.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('petaSebaran', 0)
                ->has('trails.data', 0)
                ->where('menunggu.0.name', 'Jalur Draft Berkoordinat'));
    }

    /**
     * Konfigurasi peta datang dari props bersama, dari config yang sama dengan CSP:
     * penyedia tile yang diganti operator ikut berubah di keduanya.
     */
    public function test_map_configuration_is_shared_from_config(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('trails.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('peta.rasterTiles', array_values(array_filter((array) config('hiking.map.raster_tiles'))))
                ->where('peta.maxZoom', (int) config('hiking.map.max_zoom', 17)));
    }

    /**
     * Komponen peta menggambar garis jalur dengan warna dari token, dan labelnya selalu
     * tertulis di ringkasan, bukan warna saja (WCAG 1.4.1). Dirender di klien, jadi
     * dijaga dari sumbernya.
     */
    public function test_the_map_component_draws_trail_lines_coloured_by_tokens_with_text_labels(): void
    {
        $sumber = File::get(resource_path('js/components/trails/PetaJelajah.tsx'));

        $this->assertStringContainsString("type: 'line'", $sumber);
        $this->assertStringContainsString('--brand-700', $sumber);
        $this->assertStringContainsString('fit.labelText', $sumber, 'Ringkasan harus menyebut label kecocokan dengan kata.');
        $this->assertStringNotContainsString('mountain.latitude', $sumber);
    }
}
