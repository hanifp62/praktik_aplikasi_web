<?php

namespace App\Livewire\Moderation;

use App\Enums\ModerationAction as ModerationActionType;
use App\Enums\ModerationStatus;
use App\Models\ModerationAction;
use App\Models\TrailConditionReport;
use App\Services\AuditLogService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * FR-17 moderation queue for community reports.
 */
#[Layout('layouts.app')]
#[Title('Moderasi Laporan')]
class ModerationQueue extends Component
{
    use WithPagination;

    #[Url]
    public string $filter = ModerationStatus::PENDING->value;

    /** @var array<int, string> */
    public array $reasons = [];

    public function act(int $reportId, string $action, AuditLogService $audit): void
    {
        $this->authorize('moderate', TrailConditionReport::class);

        $report = TrailConditionReport::findOrFail($reportId);
        $actionType = ModerationActionType::from($action);
        $before = ['moderation_status' => $report->moderation_status->value];

        $report->update([
            'moderation_status' => $actionType->resultingStatus()->value,
            'moderated_by' => auth()->id(),
            'moderated_at' => now(),
        ]);

        ModerationAction::create([
            'moderator_id' => auth()->id(),
            'moderatable_type' => $report->getMorphClass(),
            'moderatable_id' => $report->id,
            'action' => $actionType->value,
            'reason' => $this->reasons[$reportId] ?? null,
        ]);

        $audit->record(auth()->user(), 'report.moderated', $report, $before, [
            'moderation_status' => $report->moderation_status->value,
        ]);

        unset($this->reasons[$reportId]);
    }

    public function render()
    {
        return view('livewire.moderation.moderation-queue', [
            'reports' => TrailConditionReport::query()
                ->when($this->filter, fn ($query, $filter) => $query->where('moderation_status', $filter))
                ->with('trail.mountain', 'user:id,name', 'segment:id,name')
                ->orderByDesc('created_at')
                ->paginate(10),
            'statuses' => ModerationStatus::cases(),
            'actions' => ModerationActionType::cases(),
        ]);
    }
}
