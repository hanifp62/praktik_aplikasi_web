<?php

namespace App\Livewire\Trails;

use App\Enums\TechnicalDemand;
use App\Models\Trail;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Manual browse/filter fallback required when recommendation fails (PRD §94).
 */
#[Layout('layouts.app')]
#[Title('Jelajahi Jalur')]
class TrailIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $technical = '';

    #[Url]
    public string $region = '';

    public function updating(string $field): void
    {
        if (in_array($field, ['search', 'technical', 'region'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $trails = Trail::query()
            ->published()
            ->with('mountain')
            ->when($this->search, fn ($query, $search) => $query->where('name', 'like', "%{$search}%")
                ->orWhereHas('mountain', fn ($q) => $q->where('name', 'like', "%{$search}%")))
            ->when($this->technical, fn ($query, $technical) => $query->where('technical_demand', $technical))
            ->when($this->region, fn ($query, $region) => $query->whereHas(
                'mountain',
                fn ($q) => $q->where('region', $region)->orWhere('province', $region)
            ))
            ->orderBy('name')
            ->paginate(12);

        return view('livewire.trails.trail-index', [
            'trails' => $trails,
            'technicalLevels' => TechnicalDemand::cases(),
        ]);
    }
}
