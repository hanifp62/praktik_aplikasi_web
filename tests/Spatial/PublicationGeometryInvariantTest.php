<?php

namespace Tests\Spatial;

use App\Exceptions\PublicationGateViolation;
use App\Models\Checkpoint;
use App\Models\DataSource;
use App\Models\OfficialStatus;
use App\Models\Trail;

/**
 * R-008/R-022: geometri termasuk data kritis publikasi (PRD §110).
 *
 * Syarat ini tidak dapat diuji di suite utama. Kolom geometri hanya ada pada koneksi
 * berkemampuan PostGIS, sehingga `publishabilityReport()` melewatinya di SQLite dan
 * seluruh suite tetap hijau meski invariantnya dilanggar. Itulah persis yang terjadi:
 * tujuh jalur terbit tanpa geometri, dan 863 test tidak menyadarinya.
 *
 * Berkas ini menutup celah itu pada satu-satunya tempat yang dapat menutupnya.
 */
class PublicationGeometryInvariantTest extends SpatialTestCase
{
    public function test_a_trail_without_geometry_cannot_be_published(): void
    {
        $trail = $this->draftWithEverythingButGeometry();

        $this->assertContains('Geometri jalur belum tersedia.', $trail->publishabilityReport());

        try {
            $trail->update(['is_published' => true]);
        } catch (PublicationGateViolation $e) {
            $this->assertStringContainsString('Geometri jalur belum tersedia.', $e->getMessage());
            $this->assertFalse($trail->fresh()->is_published);

            return;
        }

        $this->fail('Jalur tanpa geometri seharusnya ditolak gerbang publikasi.');
    }

    public function test_geometry_is_the_only_thing_standing_between_this_draft_and_publication(): void
    {
        $trail = $this->draftWithEverythingButGeometry();

        $this->assertSame(
            ['Geometri jalur belum tersedia.'],
            $trail->publishabilityReport(),
            'Draf ini sengaja lengkap kecuali geometrinya.'
        );
    }

    public function test_a_trail_with_geometry_passes_the_gate(): void
    {
        $trail = $this->draftWithEverythingButGeometry();

        $trail->writeLineString('geometry', [
            [112.9500, -7.9970],
            [112.9505, -7.9975],
            [112.9510, -7.9980],
        ]);

        $segar = $trail->fresh();

        $this->assertSame([], $segar->publishabilityReport());

        $segar->update(['is_published' => true]);

        $this->assertTrue($segar->fresh()->is_published);
    }

    private function draftWithEverythingButGeometry(): Trail
    {
        $trail = Trail::factory()->create([
            'data_source_id' => DataSource::factory()->create()->id,
            'distance_km' => 9.4,
            'elevation_gain_m' => 1100,
            'estimated_duration_minutes' => 600,
        ]);

        Checkpoint::factory()->for($trail)->create(['sequence' => 1]);
        OfficialStatus::factory()->create([
            'statusable_type' => $trail->getMorphClass(),
            'statusable_id' => $trail->getKey(),
        ]);

        return $trail->fresh();
    }
}
