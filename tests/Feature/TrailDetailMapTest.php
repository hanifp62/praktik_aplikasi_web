<?php

namespace Tests\Feature;

use App\Models\Checkpoint;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Halaman jalur menggambar geometrinya.
 *
 * Variabel $geometry sempat dioper ke view dan tidak pernah dipakai: pendaki membaca
 * deretan angka tanpa pernah melihat bentuk jalurnya. Kini geometri dan penanda pos
 * dikirim sebagai props petaJalur dan digambar PetaJalur.tsx (garis, titik awal dan
 * akhir, penanda pos).
 */
class TrailDetailMapTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{geometry: array<string, mixed>|null, penanda: array<int, array<string, mixed>>}
     */
    private function peta(Trail $trail): array
    {
        return $this->actingAs(User::factory()->create())
            ->get(route('trails.show', $trail))
            ->assertOk()
            ->viewData('page')['props']['petaJalur'];
    }

    private function sumberPeta(): string
    {
        return File::get(resource_path('js/components/trails/rinci/PetaJalur.tsx'));
    }

    public function test_a_trail_with_geometry_is_drawn(): void
    {
        $trail = Trail::factory()->published()->easy()->create();

        if (! Trail::spatialSupported()) {
            $this->markTestSkipped('Butuh PostGIS untuk menulis geometri.');
        }

        $trail->writeLineString('geometry', [[110.44, -7.45], [110.45, -7.46]]);

        $peta = $this->peta($trail->fresh());

        $this->assertSame('LineString', $peta['geometry']['type']);
        $this->assertCount(2, $peta['geometry']['coordinates']);
        $this->assertStringContainsString("addLayer({ id: 'garis-jalur'", $this->sumberPeta());
        $this->assertStringContainsString("'Awal jalur'", $this->sumberPeta());
        $this->assertStringContainsString("'Akhir jalur'", $this->sumberPeta());
    }

    /**
     * Pos punya koordinat meski garis jalurnya belum ada, dan titik-titik itu sendiri
     * sudah cukup untuk menggambarkan jalurnya secara kasar. Sejak tinjauan akhir W3 (T6-M3,
     * spec v3 bagian 6.4) peta seperti itu tetap menyebut bahwa garis rute belum dimasukkan,
     * jadi penanda pos tidak terbaca sebagai rute yang sudah tergambar.
     */
    public function test_checkpoints_alone_already_put_the_trail_on_a_map(): void
    {
        $trail = Trail::factory()->published()->easy()->create();

        Checkpoint::factory()->for($trail)->create([
            'name' => 'Pos Bayangan',
            'sequence' => 1,
            'latitude' => -7.45,
            'longitude' => 110.44,
        ]);

        $peta = $this->peta($trail);

        $this->assertSame([['lng' => 110.44, 'lat' => -7.45, 'label' => '1. Pos Bayangan', 'urutan' => 1, 'aksen' => null]], $peta['penanda']);
        $this->assertStringContainsString('petaJalur.penanda.length > 0', File::get(resource_path('js/pages/Trails/Show.tsx')));
        $this->assertMatchesRegularExpression('/\{data\.geometry \? null : \(\s*<p[^>]*>\s*<span className="font-bold text-primary">Garis rute belum dimasukkan\.<\/span>/', $this->sumberPeta());
    }

    /**
     * Peta adalah wilayah interaktif, bukan gambar: role="img" menyembunyikan isinya dari
     * pembaca layar dan menuntut teks alternatif yang mustahil untuk peta yang digeser.
     */
    public function test_the_map_is_an_interactive_region(): void
    {
        $wadah = File::get(resource_path('js/components/peta/WadahPeta.tsx'));

        // Perannya dipegang WadahPeta, jadi peta jalur wajib menggambar lewat sana.
        $this->assertStringContainsString('<WadahPeta', $this->sumberPeta());
        $this->assertStringNotContainsString('role="img"', $this->sumberPeta());
        $this->assertStringContainsString('role="region"', $wadah);
        $this->assertStringNotContainsString('role="img"', $wadah);
    }

    /**
     * Wadah peta kosong terbaca sebagai peta yang gagal dimuat. Kekosongan ini tahapan,
     * dan yang ditunggu disebut namanya.
     */
    public function test_a_trail_without_any_coordinates_says_so_instead_of_showing_an_empty_box(): void
    {
        $trail = Trail::factory()->published()->easy()->create();

        $peta = $this->peta($trail);

        $this->assertSame([], $peta['penanda']);

        if (! Trail::spatialSupported()) {
            $this->assertNull($peta['geometry']);
        }

        $this->assertStringContainsString('<span className="font-bold text-primary">Garis rute belum dimasukkan.</span>', File::get(resource_path('js/pages/Trails/Show.tsx')));
    }

    /**
     * Padanan non-visual peta: nama pos terbaca sebagai teks (WCAG 1.1.1), baik di
     * daftar pos maupun di daftar penanda sr-only yang menyertai peta.
     */
    public function test_the_checkpoint_names_are_readable_without_the_map(): void
    {
        $trail = Trail::factory()->published()->easy()->create();

        foreach ([['Pos 1 Watu Gede', 1], ['Pos 2 Sabana', 2]] as [$nama, $urutan]) {
            Checkpoint::factory()->for($trail)->create([
                'name' => $nama,
                'sequence' => $urutan,
                'latitude' => -7.45 - $urutan * 0.01,
                'longitude' => 110.44,
            ]);
        }

        $props = $this->actingAs(User::factory()->create())->get(route('trails.show', $trail))->viewData('page')['props'];

        $this->assertContains('Pos 1 Watu Gede', array_column($props['pos'], 'nama'));
        $this->assertContains('Pos 2 Sabana', array_column($props['pos'], 'nama'));
        $this->assertStringContainsString('<span className="sr-only">{penanda.map((p) => p.label)', $this->sumberPeta());
    }
}
