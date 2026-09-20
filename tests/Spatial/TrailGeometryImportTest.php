<?php

namespace Tests\Spatial;

use App\Enums\UserRole;
use App\Livewire\Admin\TrailGeometryImport;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

/**
 * Jalur tulis impor GPX, satu-satunya bagian yang butuh PostGIS sungguhan.
 */
class TrailGeometryImportTest extends SpatialTestCase
{
    public function test_an_imported_track_becomes_readable_geometry(): void
    {
        $trail = Trail::factory()->create();

        Livewire::actingAs($this->admin())
            ->test(TrailGeometryImport::class, ['trail' => $trail])
            ->set('berkas', $this->gpx())
            ->call('simpan');

        $geojson = $trail->fresh()->readGeoJson('geometry');

        $this->assertSame('LineString', $geojson['type']);
        $this->assertCount(3, $geojson['coordinates']);
        $this->assertEqualsWithDelta(112.9500, $geojson['coordinates'][0][0], 0.0001);
        $this->assertEqualsWithDelta(-7.9970, $geojson['coordinates'][0][1], 0.0001);
    }

    public function test_an_imported_track_satisfies_the_publishing_gate(): void
    {
        $trail = Trail::factory()->create();

        $this->assertContains('Geometri jalur belum tersedia.', $trail->publishabilityReport());

        Livewire::actingAs($this->admin())
            ->test(TrailGeometryImport::class, ['trail' => $trail])
            ->set('berkas', $this->gpx())
            ->call('simpan');

        $this->assertNotContains('Geometri jalur belum tersedia.', $trail->fresh()->publishabilityReport());
    }

    public function test_importing_again_replaces_the_previous_track(): void
    {
        $trail = Trail::factory()->create();
        $trail->writeLineString('geometry', [[112.0, -7.0], [112.1, -7.1]]);

        Livewire::actingAs($this->admin())
            ->test(TrailGeometryImport::class, ['trail' => $trail])
            ->set('berkas', $this->gpx())
            ->call('simpan');

        $this->assertCount(3, $trail->fresh()->readGeoJson('geometry')['coordinates']);
    }

    public function test_the_import_is_recorded_in_the_audit_log(): void
    {
        $trail = Trail::factory()->create();
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(TrailGeometryImport::class, ['trail' => $trail])
            ->set('berkas', $this->gpx())
            ->call('simpan');

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'trail.geometry_imported',
        ]);
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
