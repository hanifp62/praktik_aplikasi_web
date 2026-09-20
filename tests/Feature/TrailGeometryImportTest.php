<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Admin\TrailGeometryImport;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * PRD §65 dan §109.
 *
 * Penulisan geometrinya sendiri butuh PostGIS dan diuji di tests/Spatial. Yang diuji di
 * sini adalah lapisan yang berjalan di mana saja: izin akses, pembacaan berkas, pratinjau
 * sebelum menyimpan, dan penolakan berkas yang keliru.
 */
class TrailGeometryImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_can_open_the_import_page(): void
    {
        $trail = Trail::factory()->create();

        $this->actingAs($this->admin())
            ->get(route('admin.geometry', $trail))
            ->assertOk()
            ->assertSee('Impor Geometri Jalur');
    }

    public function test_a_hiker_cannot_open_the_import_page(): void
    {
        $trail = Trail::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.geometry', $trail))
            ->assertForbidden();
    }

    public function test_a_valid_track_is_previewed_before_anything_is_written(): void
    {
        $component = Livewire::actingAs($this->admin())
            ->test(TrailGeometryImport::class, ['trail' => Trail::factory()->create()])
            ->set('berkas', $this->gpx());

        $component->assertSet('galat', null);
        $this->assertCount(3, $component->get('pratinjau'));
        $this->assertGreaterThan(0, $component->get('panjangKm'));
    }

    public function test_the_preview_keeps_geojson_coordinate_order(): void
    {
        $pratinjau = Livewire::actingAs($this->admin())
            ->test(TrailGeometryImport::class, ['trail' => Trail::factory()->create()])
            ->set('berkas', $this->gpx())
            ->get('pratinjau');

        $this->assertEqualsWithDelta(112.9500, $pratinjau[0][0], 0.0001);
        $this->assertEqualsWithDelta(-7.9970, $pratinjau[0][1], 0.0001);
    }

    public function test_a_file_that_is_not_gpx_is_rejected_with_a_reason(): void
    {
        $component = Livewire::actingAs($this->admin())
            ->test(TrailGeometryImport::class, ['trail' => Trail::factory()->create()])
            ->set('berkas', UploadedFile::fake()->createWithContent('jalur.gpx', 'bukan xml'));

        $this->assertStringContainsString('bukan berkas GPX', (string) $component->get('galat'));
        $this->assertSame([], $component->get('pratinjau'));
    }

    public function test_nothing_is_saved_before_a_file_is_read(): void
    {
        Livewire::actingAs($this->admin())
            ->test(TrailGeometryImport::class, ['trail' => Trail::factory()->create()])
            ->call('simpan')
            ->assertSet('galat', 'Belum ada jejak yang siap disimpan.');
    }

    /**
     * writeLineString() diam saja pada koneksi tanpa PostGIS. Tanpa penjagaan ini kurator
     * melihat pesan berhasil padahal tidak ada yang tersimpan, dan itu kebohongan yang
     * paling mahal: ia mengira jalurnya sudah punya geometri.
     */
    public function test_it_admits_when_the_connection_cannot_store_geometry(): void
    {
        $this->assertFalse(Trail::spatialSupported(), 'Suite ini memang berjalan tanpa PostGIS.');

        $component = Livewire::actingAs($this->admin())
            ->test(TrailGeometryImport::class, ['trail' => Trail::factory()->create()])
            ->set('berkas', $this->gpx())
            ->call('simpan');

        $this->assertStringContainsString('tidak mendukung PostGIS', (string) $component->get('galat'));
        $this->assertNull(session('status'));
    }

    public function test_the_trail_list_links_to_the_importer(): void
    {
        $trail = Trail::factory()->create();

        $this->actingAs($this->admin())
            ->get('/admin/trails')
            ->assertOk()
            ->assertSee(route('admin.geometry', $trail), escape: false);
    }

    private function gpx(): UploadedFile
    {
        $xml = '<?xml version="1.0"?><gpx xmlns="http://www.topografix.com/GPX/1/1" version="1.1">'
            .'<trk><trkseg>'
            .'<trkpt lat="-7.9970" lon="112.9500"/>'
            .'<trkpt lat="-7.9950" lon="112.9505"/>'
            .'<trkpt lat="-7.9930" lon="112.9510"/>'
            .'</trkseg></trk></gpx>';

        return UploadedFile::fake()->createWithContent('jalur.gpx', $xml);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::ADMIN->value]);
    }
}
