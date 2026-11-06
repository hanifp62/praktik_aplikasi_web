<?php

namespace Tests\Spatial;

use App\Models\Checkpoint;
use App\Models\Mountain;
use App\Models\RestrictedArea;
use App\Models\Trail;
use Illuminate\Support\Facades\DB;

/**
 * F-29: kode spasial yang selama ini tidak pernah dijalankan test mana pun.
 */
class TrailGeometryTest extends SpatialTestCase
{
    public function test_a_linestring_survives_a_write_and_read(): void
    {
        $trail = Trail::factory()->create();

        $coordinates = [[112.9500, -7.9970], [112.9505, -7.9950], [112.9510, -7.9930]];
        $trail->writeLineString('geometry', $coordinates);

        $geojson = $trail->readGeoJson('geometry');

        $this->assertSame('LineString', $geojson['type']);
        $this->assertCount(3, $geojson['coordinates']);
        $this->assertEqualsWithDelta(112.9500, $geojson['coordinates'][0][0], 0.0001);
        $this->assertEqualsWithDelta(-7.9970, $geojson['coordinates'][0][1], 0.0001);
    }

    public function test_a_point_survives_a_write_and_read(): void
    {
        $mountain = Mountain::factory()->create();

        $mountain->writePoint('location', -7.9930, 112.9510);

        $geojson = $mountain->readGeoJson('location');

        $this->assertSame('Point', $geojson['type']);
        $this->assertEqualsWithDelta(112.9510, $geojson['coordinates'][0], 0.0001);
        $this->assertEqualsWithDelta(-7.9930, $geojson['coordinates'][1], 0.0001);
    }

    public function test_nearby_actually_filters(): void
    {
        $trail = Trail::factory()->create();

        $near = Checkpoint::factory()->for($trail)->create(['sequence' => 1, 'name' => 'Dekat']);
        $far = Checkpoint::factory()->for($trail)->create(['sequence' => 2, 'name' => 'Jauh']);

        $near->setCoordinates(-7.9930, 112.9510);
        $far->setCoordinates(-8.5000, 113.5000);   // sekitar 90 km

        $found = Checkpoint::query()
            ->nearby('location', -7.9930, 112.9510, 1000)
            ->pluck('name')
            ->all();

        // Pada SQLite scope ini mengembalikan seluruh baris tanpa menyaring apa pun,
        // sehingga kesalahan logikanya tidak pernah terlihat di suite utama.
        $this->assertContains('Dekat', $found);
        $this->assertNotContains('Jauh', $found);
    }

    public function test_setting_coordinates_keeps_the_plain_columns_and_the_geography_in_step(): void
    {
        $checkpoint = Checkpoint::factory()->create(['sequence' => 1]);

        $checkpoint->setCoordinates(-7.9930, 112.9510);

        $this->assertEqualsWithDelta(-7.9930, $checkpoint->fresh()->latitude, 0.0001);

        $geojson = $checkpoint->fresh()->readGeoJson('location');
        $this->assertEqualsWithDelta(112.9510, $geojson['coordinates'][0], 0.0001);
    }

    public function test_a_restricted_area_that_crosses_the_trail_is_found(): void
    {
        $trail = Trail::factory()->create();
        $trail->writeLineString('geometry', [[112.9500, -7.9970], [112.9510, -7.9930]]);

        $crossing = RestrictedArea::create([
            'name' => 'Zona rawan longsor',
            'reason' => 'Aktivitas vulkanik',
            'effective_at' => now()->subDay(),
        ]);
        $this->writePolygon($crossing, [
            [112.9490, -7.9980], [112.9520, -7.9980],
            [112.9520, -7.9920], [112.9490, -7.9920], [112.9490, -7.9980],
        ]);

        $elsewhere = RestrictedArea::create([
            'name' => 'Zona lain',
            'effective_at' => now()->subDay(),
        ]);
        $this->writePolygon($elsewhere, [
            [113.5000, -8.5000], [113.5100, -8.5000],
            [113.5100, -8.4900], [113.5000, -8.4900], [113.5000, -8.5000],
        ]);

        $names = $trail->fresh()->restrictedAreas()->pluck('name')->all();

        $this->assertContains('Zona rawan longsor', $names);
        $this->assertNotContains('Zona lain', $names);
    }

    public function test_the_gist_index_exists_on_every_geography_column(): void
    {
        $expected = [
            'trails' => 'geometry',
            'trail_segments' => 'geometry',
            'checkpoints' => 'location',
            'mountains' => 'location',
            'restricted_areas' => 'geometry',
            'hiking_sessions' => 'last_known_location',
        ];

        foreach ($expected as $table => $column) {
            $indexes = DB::select(
                "SELECT indexdef FROM pg_indexes WHERE tablename = ? AND indexdef ILIKE '%USING gist%'",
                [$table]
            );

            $this->assertNotEmpty(
                $indexes,
                "PRD §66 menuntut index spasial; {$table}.{$column} tidak punya index GIST."
            );
        }
    }

    /**
     * @param  array<int, array{0: float, 1: float}>  $ring
     */
    private function writePolygon(RestrictedArea $area, array $ring): void
    {
        $points = implode(',', array_map(fn ($pair) => sprintf('%F %F', $pair[0], $pair[1]), $ring));

        DB::statement(
            'UPDATE restricted_areas SET geometry = ST_GeogFromText(?) WHERE id = ?',
            ["SRID=4326;POLYGON(({$points}))", $area->id]
        );
    }
}
