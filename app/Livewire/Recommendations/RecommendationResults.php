<?php

namespace App\Livewire\Recommendations;

use App\Enums\AnalyticsEvent;
use App\Models\RecommendationRun;
use App\Services\AnalyticsRecorder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * FR-05 recommendation result page with per-route explainability (PRD §30, §32).
 */
#[Layout('layouts.app')]
#[Title('Rekomendasi Jalur')]
class RecommendationResults extends Component
{
    public RecommendationRun $run;

    public ?int $expandedResultId = null;

    /** @var array<int, int> */
    public array $comparison = [];

    public function mount(RecommendationRun $run): void
    {
        abort_unless($run->user_id === auth()->id(), 403);

        $this->run = $run->load('results.trail.mountain', 'hikingGoal');
    }

    public function toggleExplanation(int $resultId): void
    {
        $this->expandedResultId = $this->expandedResultId === $resultId ? null : $resultId;
    }

    public function toggleComparison(int $trailId): void
    {
        if (in_array($trailId, $this->comparison, true)) {
            $this->comparison = array_values(array_diff($this->comparison, [$trailId]));

            return;
        }

        if (count($this->comparison) < 3) {
            $this->comparison[] = $trailId;
        }
    }

    public function compare(): void
    {
        if (count($this->comparison) < 2) {
            return;
        }

        $this->redirectRoute('trails.compare', ['trails' => implode(',', $this->comparison)], navigate: true);
    }

    public function selectTrail(int $trailId, AnalyticsRecorder $analytics): void
    {
        $analytics->record(AnalyticsEvent::ROUTE_SELECTED, auth()->user(), ['trail_id' => $trailId]);

        $this->redirectRoute('trips.create', ['trail' => $trailId, 'goal' => $this->run->hiking_goal_id], navigate: true);
    }

    public function render()
    {
        $results = $this->run->results;

        return view('livewire.recommendations.recommendation-results', [
            'eligible' => $results->where('eligible', true),
            'excluded' => $results->where('eligible', false),
        ]);
    }
}
