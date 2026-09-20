<?php

namespace App\Livewire\Trails;

use App\Models\Trail;
use App\Services\CheckpointPaceService;
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
        // Jalur yang belum terbit tidak punya cukup data untuk dinilai maupun
        // direncanakan. Halamannya tetap dapat dibuka, tetapi yang ditampilkan adalah
        // keadaan menunggu, bukan rincian setengah jadi yang terbaca seperti rincian utuh.
        if (! $this->trail->is_published) {
            return view('livewire.trails.trail-awaiting', [
                'menunggu' => $this->trail->awaitingData(),
                'badan' => $this->trail->mountain->responsibleAuthority(),
            ])->title($this->trail->name.' - '.$this->trail->mountain->name);
        }

        $user = auth()->user();
        $fit = $user?->hasCompletedProfile()
            ? $routeFit->evaluate($user, $user->hikingGoals()->latest()->first(), $this->trail)
            : null;

        return view('livewire.trails.trail-detail', [
            'conditions' => $conditions->forTrail($this->trail),
            'fit' => $fit,
            'geometry' => $this->trail->readGeoJson('geometry'),
            'permit' => app(PermitService::class)->requirementFor($this->trail),
            // Waktu tempuh antarpos dari rekaman pendaki. Hasilnya di-cache mengikuti
            // TTL publik, jadi halaman ini tidak menghitung ulang tiap kali dibuka.
            'tempoPos' => app(CheckpointPaceService::class)->forTrail($this->trail),
        ])->title($this->trail->name.' - '.$this->trail->mountain->name);
    }
}
