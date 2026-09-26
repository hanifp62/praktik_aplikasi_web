<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\User;
use App\Services\Admin\GeometryDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * PRD §65 dan §109.
 *
 * Penulisan geometrinya sendiri butuh PostGIS dan diuji di tests/Spatial. Yang diuji di
 * sini adalah lapisan yang berjalan di mana saja: izin akses, pembacaan berkas, pratinjau
 * sebelum menyimpan, dan penolakan berkas yang keliru. Halamannya kini React: setiap
 * langkah adalah rute POST, dan drafnya tinggal di server di antara langkah-langkahnya.
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
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/TrailGeometry')
                ->where('title', 'Impor Geometri Jalur')
                ->where('trail.id', $trail->id)
                ->where('pratinjau', null));
    }

    public function test_a_hiker_cannot_open_the_import_page(): void
    {
        $trail = Trail::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('trails.geometry', $trail))
            ->assertForbidden();
    }

    /**
     * Pintu GET dijaga ExpertGeometryAccessTest; aksi tulisnya dijaga di sini. Setiap
     * langkah memanggil authorize('update', $trail) sendiri, bukan mengandalkan halaman.
     */
    public function test_a_hiker_cannot_run_any_import_step(): void
    {
        $trail = Trail::factory()->create();
        $this->actingAs(User::factory()->create());

        $this->post(route('trails.geometry.preview', $trail), ['berkas' => $this->gpx()])->assertForbidden();
        $this->post(route('trails.geometry.search', $trail))->assertForbidden();
        $this->post(route('trails.geometry.choose', [$trail, 555]))->assertForbidden();
        $this->post(route('trails.geometry.elevation', $trail))->assertForbidden();
        $this->post(route('trails.geometry.store', $trail))->assertForbidden();
    }

    public function test_a_valid_track_is_previewed_before_anything_is_written(): void
    {
        $admin = $this->admin();
        $trail = Trail::factory()->create();

        $this->pratinjau($admin, $trail, $this->gpx())
            ->assertRedirect(route('trails.geometry', $trail))
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)->get(route('trails.geometry', $trail))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('pratinjau.titik', '3')
            ->where('pratinjau.panjang', fn (string $panjang) => $panjang !== '0,00 km' && str_ends_with($panjang, ' km'))
            ->where('pratinjau.asal', null));

        $this->assertNull($trail->fresh()->elevation_profile);
        $this->assertSame(0, AuditLog::where('action', 'trail.geometry_imported')->count());
    }

    public function test_the_preview_keeps_geojson_coordinate_order(): void
    {
        $admin = $this->admin();
        $trail = Trail::factory()->create();

        $this->pratinjau($admin, $trail, $this->gpx());

        $pratinjau = GeometryDraft::untuk($admin, $trail)->baca()['pratinjau'];

        $this->assertEqualsWithDelta(112.9500, $pratinjau[0][0], 0.0001);
        $this->assertEqualsWithDelta(-7.9970, $pratinjau[0][1], 0.0001);
    }

    /**
     * Berkas yang gagal dibaca juga membuang pratinjau sebelumnya, supaya yang tersimpan
     * nanti tidak pernah jejak dari berkas lain.
     */
    public function test_a_file_that_is_not_gpx_is_rejected_with_a_reason(): void
    {
        $admin = $this->admin();
        $trail = Trail::factory()->create();
        $this->pratinjau($admin, $trail, $this->gpx());

        $this->pratinjau($admin, $trail, UploadedFile::fake()->createWithContent('jalur.gpx', 'bukan xml'))
            ->assertRedirect(route('trails.geometry', $trail))
            ->assertSessionHasErrors('berkas');

        $this->assertStringContainsString('bukan berkas GPX', session('errors')->first('berkas'));
        $this->assertSame([], GeometryDraft::untuk($admin, $trail)->baca()['pratinjau']);
    }

    public function test_nothing_is_saved_before_a_file_is_read(): void
    {
        $trail = Trail::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('trails.geometry.store', $trail))
            ->assertRedirect()
            ->assertSessionHas('galat', 'Belum ada jejak yang siap disimpan.');
    }

    /**
     * writeLineString() diam saja pada koneksi tanpa PostGIS. Tanpa penjagaan ini kurator
     * melihat pesan berhasil padahal tidak ada yang tersimpan, dan itu kebohongan yang
     * paling mahal: ia mengira jalurnya sudah punya geometri.
     */
    public function test_it_admits_when_the_connection_cannot_store_geometry(): void
    {
        $this->assertFalse(Trail::spatialSupported(), 'Suite ini memang berjalan tanpa PostGIS.');

        $admin = $this->admin();
        $trail = Trail::factory()->create();
        $this->pratinjau($admin, $trail, $this->gpx());

        $this->actingAs($admin)
            ->post(route('trails.geometry.store', $trail))
            ->assertSessionHas('galat', fn (string $galat) => str_contains($galat, 'tidak mendukung PostGIS'))
            ->assertSessionMissing('status');

        $this->assertSame(0, AuditLog::where('action', 'trail.geometry_imported')->count());
    }

    public function test_the_trail_list_links_to_the_importer(): void
    {
        $trail = Trail::factory()->create();

        $this->actingAs($this->admin())
            ->get('/admin/trails')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('trails.data.0.tautan.geometri', route('trails.geometry', $trail)));
    }

    /**
     * Kandidat dari OpenStreetMap masuk ke pratinjau yang sama dengan berkas GPX, jadi
     * perbandingan panjang dan penjagaan PostGIS tetap berlaku untuk keduanya.
     */
    public function test_a_curator_can_search_openstreetmap_and_preview_a_candidate(): void
    {
        $this->osmMenjawab();
        $admin = $this->admin();
        $trail = $this->trailBerkoordinat();

        $this->actingAs($admin)->post(route('trails.geometry.search', $trail))->assertRedirect(route('trails.geometry', $trail));

        $this->actingAs($admin)->get(route('trails.geometry', $trail))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('kandidat.jumlah', 1)
            ->where('kandidat.tampil.0.id', 555)
            ->where('pratinjau', null));

        $this->actingAs($admin)->post(route('trails.geometry.choose', [$trail, 555]))->assertRedirect();

        $this->actingAs($admin)->get(route('trails.geometry', $trail))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('pratinjau.titik', '30')
            ->where('pratinjau.asal', 'OpenStreetMap way 555 (Jalur Pendakian Uji)'));
    }

    public function test_the_candidate_list_can_be_narrowed_by_name(): void
    {
        $this->osmMenjawab();
        $admin = $this->admin();
        $trail = $this->trailBerkoordinat();
        $this->actingAs($admin)->post(route('trails.geometry.search', $trail));

        $this->actingAs($admin)->get(route('trails.geometry', ['trail' => $trail, 'saringan' => 'pendakian']))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('kandidat.tampil', 1));

        $this->actingAs($admin)->get(route('trails.geometry', ['trail' => $trail, 'saringan' => 'Kledung']))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('kandidat.tampil', 0)->where('kandidat.jumlah', 1));
    }

    /**
     * Komponen lama tidak mengosongkan ketinggian ketika kandidat OSM dipilih, sehingga
     * profil elevasi dari berkas GPX dapat tersimpan bersama geometri OSM. OSM tidak
     * membawa ketinggian; taksirannya harus ikut hilang bersama berkasnya.
     */
    public function test_choosing_a_candidate_drops_elevation_read_from_an_earlier_file(): void
    {
        $this->osmMenjawab();
        $admin = $this->admin();
        $trail = $this->trailBerkoordinat();

        $this->actingAs($admin)->post(route('trails.geometry.search', $trail));
        $this->pratinjau($admin, $trail, $this->gpxBerketinggian());
        $this->assertNotNull(GeometryDraft::untuk($admin, $trail)->baca()['tanjakan']);

        $this->actingAs($admin)->post(route('trails.geometry.choose', [$trail, 555]));

        $draf = GeometryDraft::untuk($admin, $trail)->baca();
        $this->assertNull($draf['tanjakan']);
        $this->assertSame([], $draf['profil']);
        $this->assertNull($draf['berkas']);
    }

    /**
     * Tanpa koordinat gunung, pencarian tidak punya titik pusat. Menebaknya dari nama
     * berbahaya: Nominatim mengembalikan bukit di Ponorogo untuk "Gunung Prau".
     */
    public function test_the_search_refuses_when_the_mountain_has_no_coordinates(): void
    {
        $admin = $this->admin();
        $trail = Trail::factory()->create();

        $this->actingAs($admin)
            ->post(route('trails.geometry.search', $trail))
            ->assertSessionHas('galatOsm', fn (string $galat) => str_contains($galat, 'belum dicatat'));

        $this->assertSame([], GeometryDraft::untuk($admin, $trail)->baca()['kandidat']);
    }

    /**
     * §94: sumber luar yang mati tidak boleh menutup jalan. Unggah GPX tetap tersedia.
     */
    public function test_a_failing_search_points_at_the_other_way_in(): void
    {
        Http::fake(['*' => Http::response('gagal', 503)]);

        $this->actingAs($this->admin())
            ->post(route('trails.geometry.search', $this->trailBerkoordinat()))
            ->assertSessionHas('galatOsm', fn (string $galat) => str_contains($galat, 'GPX'));
    }

    /**
     * Taksiran tanjakan ditulis hanya atas persetujuan kurator, dan asalnya tercatat di
     * jejak audit sebagai taksiran, bukan angka resmi.
     */
    public function test_applying_the_elevation_estimate_is_audited(): void
    {
        $admin = $this->admin();
        $trail = Trail::factory()->create(['elevation_gain_m' => null, 'elevation_loss_m' => null]);
        $this->pratinjau($admin, $trail, $this->gpxBerketinggian());

        $this->actingAs($admin)
            ->post(route('trails.geometry.elevation', $trail))
            ->assertSessionHas('status', 'Elevation gain dan loss diperbarui dari berkas GPX.');

        $this->assertSame(150, $trail->fresh()->elevation_gain_m);
        $this->assertSame(0, $trail->fresh()->elevation_loss_m);

        $jejak = AuditLog::where('action', 'trail.elevation_applied')->sole();
        $this->assertSame('taksiran dari berkas GPX', $jejak->after['sumber']);
        $this->assertSame($admin->id, $jejak->actor_id);
    }

    private function pratinjau(User $admin, Trail $trail, UploadedFile $berkas): TestResponse
    {
        return $this->actingAs($admin)
            ->from(route('trails.geometry', $trail))
            ->post(route('trails.geometry.preview', $trail), ['berkas' => $berkas]);
    }

    private function osmMenjawab(): void
    {
        Http::fake(['*' => Http::response(['elements' => [[
            'type' => 'way',
            'id' => 555,
            'tags' => ['highway' => 'path', 'name' => 'Jalur Pendakian Uji'],
            'geometry' => $this->simpul(30),
        ]]])]);
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

    private function gpxBerketinggian(): UploadedFile
    {
        $xml = '<?xml version="1.0"?><gpx xmlns="http://www.topografix.com/GPX/1/1" version="1.1">'
            .'<trk><trkseg>'
            .'<trkpt lat="-7.9970" lon="112.9500"><ele>2000</ele></trkpt>'
            .'<trkpt lat="-7.9950" lon="112.9505"><ele>2100</ele></trkpt>'
            .'<trkpt lat="-7.9930" lon="112.9510"><ele>2150</ele></trkpt>'
            .'</trkseg></trk></gpx>';

        return UploadedFile::fake()->createWithContent('jalur-berketinggian.gpx', $xml);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::ADMIN->value]);
    }
}
