<?php

namespace Tests\Unit;

use App\Enums\ExperienceLevel;
use App\Enums\NavigationExperience;
use App\Enums\OfficialStatusValue;
use App\Enums\RouteFitLabel;
use App\Enums\StatusScope;
use App\Models\HikingGoal;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TrailSegment;
use App\Models\User;
use App\Services\OfficialStatusService;
use App\Services\RouteFitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PRD §42: status resmi dapat berada pada level MOUNTAIN, TRAIL, SEGMENT, atau AREA.
 *
 * Admin sudah dapat mencatat status ber-scope SEGMENT, tetapi tidak ada satu pun
 * service yang membacanya, sehingga pembatasan segmen tidak terlihat sama sekali
 * oleh pengguna. Realitanya persis kasus Semeru: jalur dibuka, tetapi hanya sampai
 * Ranu Kumbolo, puncaknya ditutup.
 */
class SegmentRestrictionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_closed_segment_is_reported_by_the_status_service(): void
    {
        $trail = Trail::factory()->create();
        $segment = $this->closedSegment($trail, 'Kalimati - Puncak', 'Aktivitas vulkanik');

        $restrictions = app(OfficialStatusService::class)->segmentRestrictionsForTrail($trail);

        $this->assertCount(1, $restrictions);
        $this->assertSame($segment->name, $restrictions[0]['segment']);
        $this->assertSame(OfficialStatusValue::CLOSED, $restrictions[0]['status']);
        $this->assertSame('Aktivitas vulkanik', $restrictions[0]['reason']);
    }

    public function test_a_closed_segment_warns_without_excluding_the_trail(): void
    {
        $trail = Trail::factory()->easy()->create();
        $this->closedSegment($trail, 'Kalimati - Puncak', 'Aktivitas vulkanik');

        $result = app(RouteFitService::class)->evaluate($user = $this->experienced(), $this->goal($user), $trail);

        // PRD §26 memisahkan pembatasan dari pengecualian: jalurnya tetap eligible.
        $this->assertTrue($result->eligible);
        $this->assertStringContainsString('Kalimati - Puncak', implode(' ', $result->warnings));
    }

    public function test_a_closed_segment_caps_the_label(): void
    {
        $trail = Trail::factory()->easy()->create();
        $this->closedSegment($trail, 'Kalimati - Puncak', 'Aktivitas vulkanik');

        $result = app(RouteFitService::class)->evaluate($user = $this->experienced(), $this->goal($user), $trail);

        $this->assertNotSame(
            RouteFitLabel::COCOK,
            $result->label,
            'Jalur dengan segmen tertutup tidak boleh terbaca sepenuhnya cocok.'
        );
    }

    public function test_a_trail_without_segment_restrictions_is_unaffected(): void
    {
        $trail = Trail::factory()->easy()->create();
        TrailSegment::factory()->for($trail)->create(['name' => 'Basecamp - Pos 1']);

        $result = app(RouteFitService::class)->evaluate($user = $this->experienced(), $this->goal($user), $trail);

        $this->assertSame([], app(OfficialStatusService::class)->segmentRestrictionsForTrail($trail));
        $this->assertSame(RouteFitLabel::COCOK, $result->label);
    }

    public function test_a_reopened_segment_no_longer_restricts(): void
    {
        $trail = Trail::factory()->easy()->create();
        $segment = $this->closedSegment($trail, 'Kalimati - Puncak', 'Aktivitas vulkanik');

        // Status yang lebih baru membuka kembali segmen tersebut.
        OfficialStatus::factory()->create([
            'statusable_type' => (new TrailSegment)->getMorphClass(),
            'statusable_id' => $segment->id,
            'scope' => StatusScope::SEGMENT->value,
            'status' => OfficialStatusValue::OPEN->value,
            'effective_at' => now(),
            'published_at' => now(),
        ]);

        $this->assertSame([], app(OfficialStatusService::class)->segmentRestrictionsForTrail($trail->fresh()));
    }

    private function closedSegment(Trail $trail, string $name, string $reason): TrailSegment
    {
        $segment = TrailSegment::factory()->for($trail)->create(['name' => $name]);

        OfficialStatus::factory()->create([
            'statusable_type' => (new TrailSegment)->getMorphClass(),
            'statusable_id' => $segment->id,
            'scope' => StatusScope::SEGMENT->value,
            'status' => OfficialStatusValue::CLOSED->value,
            'reason' => $reason,
            'effective_at' => now()->subDays(2),
            'published_at' => now()->subDays(2),
        ]);

        return $segment;
    }

    private function experienced(): User
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::ADVANCED->value, 'completed_at' => now()]);
        $user->experience()->create([
            'completed_hikes_count' => 25,
            'terrain_experience' => ['FOREST', 'SCREE', 'EXPOSED_RIDGE', 'STEEP_SLOPE', 'ROCKY'],
            'navigation_experience' => NavigationExperience::ADVANCED->value,
            'highest_elevation_gain_m' => 1800,
        ]);

        return $user->fresh();
    }

    private function goal(User $user): HikingGoal
    {
        return HikingGoal::factory()->create(['user_id' => $user->id]);
    }
}
