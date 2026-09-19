<?php

namespace App\Livewire\Trails;

use App\Models\Trail;
use App\Services\OfficialStatusService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * FR-06 route comparison. The system never declares a winner (PRD §33); it only lays out
 * the differences so the user can decide.
 */
#[Layout('layouts.app')]
#[Title('Bandingkan Jalur')]
class RouteComparison extends Component
{
    // Query string stays ?trails=1,2 while the property avoids colliding with the view's $trails.
    #[Url(as: 'trails')]
    public string $selection = '';

    public function removeTrail(int $trailId): void
    {
        $ids = array_values(array_diff($this->trailIds(), [$trailId]));
        $this->selection = implode(',', $ids);
    }

    /**
     * @return array<int, int>
     */
    private function trailIds(): array
    {
        return array_values(array_filter(array_map('intval', explode(',', $this->selection))));
    }

    public function render(OfficialStatusService $officialStatus)
    {
        $trails = Trail::query()
            ->published()
            ->whereIn('id', $this->trailIds())
            ->with('mountain')
            ->withCount(['conditionReports' => fn ($query) => $query->visibleToPublic()
                ->where('hike_date', '>=', now()->subDays(30)->toDateString())])
            ->get();

        return view('livewire.trails.route-comparison', [
            'trails' => $trails,
            'statuses' => $trails->mapWithKeys(fn (Trail $trail) => [
                $trail->id => $officialStatus->effectiveStatusForTrail($trail),
            ]),
        ]);
    }
}
