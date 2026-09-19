<?php

namespace App\Livewire\Trails;

use App\Models\Trail;
use App\Services\ConditionAggregatorService;
use App\Services\PermitService;
use App\Services\RouteFitService;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * FR-07 trail detail: overview, route, checkpoints, preparation context, official status,
 * weather and community conditions, each with its own source (PRD §34, §60).
 */
#[Layout('layouts.app')]
class TrailDetail extends Component
{
    public Trail $trail;

    public function mount(Trail $trail): void
    {
        abort_if($trail->archived_at !== null, 404);

        $this->trail = $trail->load('mountain', 'segments', 'checkpoints', 'dataSource');
    }

    public function createTrip(): void
    {
        $this->redirectRoute('trips.create', ['trail' => $this->trail->id], navigate: true);
    }

    public function render(ConditionAggregatorService $conditions, RouteFitService $routeFit)
    {
        $user = auth()->user();
        $fit = $user?->hasCompletedProfile()
            ? $routeFit->evaluate($user, $user->hikingGoals()->latest()->first(), $this->trail)
            : null;

        return view('livewire.trails.trail-detail', [
            'conditions' => $conditions->forTrail($this->trail),
            'fit' => $fit,
            'geometry' => $this->trail->readGeoJson('geometry'),
            'permit' => app(PermitService::class)->requirementFor($this->trail),
        ])->title($this->trail->name.' - '.$this->trail->mountain->name);
    }
}
