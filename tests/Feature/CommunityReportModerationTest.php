<?php

namespace Tests\Feature;

use App\Enums\ModerationStatus;
use App\Enums\UserRole;
use App\Livewire\Moderation\ModerationQueue;
use App\Livewire\Reports\ConditionReportForm;
use App\Models\ModerationAction;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\User;
use App\Services\ConditionAggregatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Community report matrix from PRD §103 plus the moderation acceptance criteria in §118.
 */
class CommunityReportModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_report_enters_moderation_and_is_not_public_yet(): void
    {
        $trail = Trail::factory()->create();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ConditionReportForm::class, ['trail' => $trail->id])
            ->set('hike_date', now()->subDay()->toDateString())
            ->set('condition_tags', ['MUDDY'])
            ->set('note', 'Licin setelah hujan.')
            ->call('save')
            ->assertHasNoErrors();

        $report = TrailConditionReport::firstOrFail();

        $this->assertSame(ModerationStatus::PENDING, $report->moderation_status);
        $this->assertSame(0, TrailConditionReport::visibleToPublic()->count());
    }

    public function test_moderator_approval_makes_a_report_public_and_is_logged(): void
    {
        $report = TrailConditionReport::factory()->create();
        $moderator = User::factory()->create(['role' => UserRole::MODERATOR->value]);

        Livewire::actingAs($moderator)
            ->test(ModerationQueue::class)
            ->call('act', $report->id, 'APPROVE');

        $report->refresh();

        $this->assertSame(ModerationStatus::APPROVED, $report->moderation_status);
        $this->assertSame($moderator->id, $report->moderated_by);
        $this->assertSame(1, ModerationAction::count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'report.moderated']);
    }

    public function test_hikers_cannot_moderate(): void
    {
        $report = TrailConditionReport::factory()->create();

        Livewire::actingAs(User::factory()->create())
            ->test(ModerationQueue::class)
            ->call('act', $report->id, 'APPROVE')
            ->assertForbidden();
    }

    public function test_case_a_open_status_with_community_caution_keeps_status_and_adds_warning(): void
    {
        $trail = Trail::factory()->create();
        OfficialStatus::factory()->create([
            'statusable_id' => $trail->id,
            'statusable_type' => $trail->getMorphClass(),
        ]);
        TrailConditionReport::factory()->approved()->create(['trail_id' => $trail->id]);

        $conditions = app(ConditionAggregatorService::class)->forTrail($trail);

        $this->assertSame('OPEN', $conditions['official_status']['status']);
        $this->assertTrue($conditions['community_context']['available']);
        $this->assertNotEmpty($conditions['warnings']);
    }

    public function test_case_b_closed_status_remains_authoritative_over_community_reports(): void
    {
        $trail = Trail::factory()->create();
        OfficialStatus::factory()->closed()->create([
            'statusable_id' => $trail->id,
            'statusable_type' => $trail->getMorphClass(),
        ]);
        TrailConditionReport::factory()->approved()->create([
            'trail_id' => $trail->id,
            'condition_tags' => ['DRY', 'GOOD_MARKING'],
        ]);

        $conditions = app(ConditionAggregatorService::class)->forTrail($trail);

        $this->assertSame('CLOSED', $conditions['official_status']['status']);
    }

    public function test_case_c_no_community_report_says_so_explicitly(): void
    {
        $trail = Trail::factory()->create();

        $conditions = app(ConditionAggregatorService::class)->forTrail($trail);

        $this->assertFalse($conditions['community_context']['available']);
        $this->assertSame(
            'Belum ada laporan kondisi terbaru dari komunitas.',
            $conditions['community_context']['message']
        );
    }
}
