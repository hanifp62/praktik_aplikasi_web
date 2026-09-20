<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Admin\TrailGeometryImport;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
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
            ->get(route('trails.geometry', $trail))
            ->assertOk()
            ->assertSee('Impor Geometri Jalur');
    }

    public function test_a_hiker_cannot_open_the_import_page(): void
    {
        $trail = Trail::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('trails.geometry', $trail))
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
            ->assertSee(route('trails.geometry', $trail), escape: false);
    }

    /**
     * Kandidat dari OpenStreetMap masuk ke pratinjau yang sama dengan berkas GPX, jadi
     * perbandingan panjang dan penjagaan PostGIS tetap berlaku untuk keduanya.
     */
    public function test_a_curator_can_search_openstreetmap_and_preview_a_candidate(): void
    {
        Http::fake(['*' => Http::response(['elements' => [[
            'type' => 'way',
            'id' => 555,
            'tags' => ['highway' => 'path', 'name' => 'Jalur Pendakian Uji'],
            'geometry' => $this->simpul(30),
        ]]])]);

        $component = Livewire::actingAs($this->admin())
            ->test(TrailGeometryImport::class, ['trail' => $this->trailBerkoordinat()])
            ->call('cariDiOsm');

        $this->assertCount(1, $component->get('kandidat'));

        $component->call('pilihKandidat', 555);

        $this->assertCount(30, $component->get('pratinjau'));
        $this->assertStringContainsString('OpenStreetMap way 555', (string) $component->get('asal'));
    }

    /**
     * Tanpa koordinat gunung, pencarian tidak punya titik pusat. Menebaknya dari nama
     * berbahaya: Nominatim mengembalikan bukit di Ponorogo untuk "Gunung Prau".
     */
    public function test_the_search_refuses_when_the_mountain_has_no_coordinates(): void
    {
        Livewire::actingAs($this->admin())
            ->test(TrailGeometryImport::class, ['trail' => Trail::factory()->create()])
            ->call('cariDiOsm')
            ->assertSet('kandidat', [])
            ->assertSee('belum dicatat');
    }

    /**
     * §94: sumber luar yang mati tidak boleh menutup jalan. Unggah GPX tetap tersedia.
     */
    public function test_a_failing_search_points_at_the_other_way_in(): void
    {
        Http::fake(['*' => Http::response('gagal', 503)]);

        $component = Livewire::actingAs($this->admin())
            ->test(TrailGeometryImport::class, ['trail' => $this->trailBerkoordinat()])
            ->call('cariDiOsm');

        $this->assertStringContainsString('GPX', (string) $component->get('galat'));
    }

    /**
     * @return array<int, array{lat: float, lon: float}>
     */
    private function simpul(int $jumlah): array
    {
        $titik = [];

        for ($i = 0; $i < $jumlah; $i++) {
            $titik[] = ['lat' => -7.45 - $i * 0.001, 'lon' => 110.44 + $i * 0.001];
        }

        return $titik;
    }

    private function trailBerkoordinat(): Trail
    {
        $mountain = Mountain::factory()->create();
        $mountain->setCoordinates(-7.4549, 110.4406);

        return Trail::factory()->for($mountain)->create();
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
