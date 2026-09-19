<?php

namespace App\Livewire\Trips;

use App\Enums\PreparationCategory;
use App\Enums\PreparationStatus;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Services\PreparationService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * FR-09 preparation plan with tri-state items. Unconfirmed never means "not owned" (PRD §37).
 */
#[Layout('layouts.app')]
#[Title('Persiapan Pendakian')]
class PreparationChecklist extends Component
{
    public TripPlan $trip;

    public function mount(TripPlan $trip, PreparationService $preparation): void
    {
        $this->authorize('view', $trip);

        $this->trip = $trip->load('trail.mountain');

        if ($this->trip->preparationItems()->doesntExist()) {
            $preparation->generateFor($this->trip);
        }
    }

    public function setStatus(int $itemId, string $status, PreparationService $preparation): void
    {
        $this->authorize('update', $this->trip);

        $item = TripPreparationItem::where('trip_plan_id', $this->trip->id)->findOrFail($itemId);
        $preparation->updateStatus($item, PreparationStatus::from($status));
    }

    public function regenerate(PreparationService $preparation): void
    {
        $this->authorize('update', $this->trip);

        $preparation->generateFor($this->trip);
        session()->flash('status', 'Daftar persiapan diperbarui mengikuti karakteristik jalur.');
    }

    public function render()
    {
        $items = $this->trip->preparationItems()->get()->groupBy(fn ($item) => $item->category->value);

        return view('livewire.trips.preparation-checklist', [
            'grouped' => $items,
            'categories' => PreparationCategory::cases(),
            'statuses' => PreparationStatus::cases(),
            'completion' => $this->trip->fresh()->loadMissing('preparationItems')->preparationCompletionPercent(),
        ]);
    }
}
