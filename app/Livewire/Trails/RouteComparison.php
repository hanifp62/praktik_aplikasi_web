<?php

namespace App\Livewire\Trails;

use App\Models\Trail;
use App\Services\OfficialStatusService;
use App\Services\TrailFitService;
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
            ->get();

        // Kecocokan dinilai hanya untuk yang profilnya cukup, sama seperti halaman
        // jelajah: menilai tanpa profil menghasilkan label yang terlihat pasti dan
        // berdasar ketiadaan, dan itu persis yang dilarang §91.
        $ringkasanFit = auth()->user()?->hasCompletedProfile()
            ? app(TrailFitService::class)->forTrails(auth()->user(), $trails)
            : [];

        return view('livewire.trails.route-comparison', [
            'trails' => $trails,
            'statuses' => $trails->mapWithKeys(fn (Trail $trail) => [
                $trail->id => $officialStatus->effectiveStatusForTrail($trail),
            ]),
            'ringkasanFit' => $ringkasanFit,
        ]);
    }
}
