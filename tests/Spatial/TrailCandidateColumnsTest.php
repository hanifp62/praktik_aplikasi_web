<?php

namespace Tests\Spatial;

use App\Enums\ExperienceLevel;
use App\Models\Checkpoint;
use App\Models\DataSource;
use App\Models\HikingGoal;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TrailConsideration;
use App\Models\User;
use App\Services\ConsiderationService;
use App\Services\RouteFitService;
use App\Services\Trails\TrailSearch;
use Illuminate\Support\Facades\Schema;

/**
 * Kandidat jalur tanpa garis rute di PostgreSQL (R-024; titipan W3 I7 dan T4-M5; ruling W8-R20):
 * kolom geografi benar-benar tidak terbawa ke Jelajah, rekomendasi, dan simpanan, sedangkan
 * penanda has_geometry tetap ada untuk gerbang kelengkapan.
 */
class TrailCandidateColumnsTest extends SpatialTestCase
{
    public function test_the_column_list_is_the_schema_without_geometry(): void
    {
        $kolom = Schema::getColumnListing('trails');

        $this->assertContains('geometry', $kolom);
        $this->assertEqualsCanonicalizing(array_values(array_diff($kolom, ['geometry'])), Trail::KOLOM_TANPA_GEOMETRI);
    }

    public function test_candidates_carry_the_flag_but_not_the_line(): void
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::INTERMEDIATE->value, 'completed_at' => now()]);
        $goal = HikingGoal::factory()->create(['user_id' => $user->id]);
        // Geometri ditulis sebelum terbit, pola jalurTerbit di TrailSearchKeywordTest (ruling W3-F6).
        $jalur = Trail::factory()->easy()->create(['data_source_id' => DataSource::factory()->create()->id]);
        $jalur->writeLineString('geometry', [[106.98, -6.78], [106.99, -6.79]]);
        Checkpoint::factory()->for($jalur)->create(['sequence' => 1]);
        OfficialStatus::factory()->create(['statusable_type' => $jalur->getMorphClass(), 'statusable_id' => $jalur->getKey()]);
        $jalur->fresh()->update(['is_published' => true]);
        TrailConsideration::create(['user_id' => $user->id, 'trail_id' => $jalur->id]);

        $kandidat = TrailSearch::dariInput([])->kandidat(10)->firstWhere('id', $jalur->id);
        $this->assertNotNull($kandidat);
        $this->assertArrayNotHasKey('geometry', $kandidat->getAttributes());
        $this->assertTrue($kandidat->hasGeometry());

        $run = app(RouteFitService::class)->recommend($user->fresh(), $goal);
        foreach ($run->results as $hasil) {
            $this->assertArrayNotHasKey('geometry', $hasil->trail->getAttributes());
        }

        $simpanan = app(ConsiderationService::class)->simpanan($user)->first();
        $this->assertArrayNotHasKey('geometry', $simpanan->trail->getAttributes());
        $this->assertArrayNotHasKey('geometry', app(ConsiderationService::class)->forUser($user)->first()->getAttributes());
    }
}
