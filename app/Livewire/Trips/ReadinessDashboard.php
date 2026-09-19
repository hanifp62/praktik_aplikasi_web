<?php

namespace App\Livewire\Trips;

use App\Enums\AnalyticsEvent;
use App\Enums\ReadinessState;
use App\Enums\TripStatus;
use App\Models\ReadinessCheck;
use App\Models\TripPlan;
use App\Services\AnalyticsRecorder;
use App\Services\ReadinessService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * FR-10 readiness: route fit + preparation + current conditions (PRD §38-39).
 * This is decision support, not a safety clearance.
 */
#[Layout('layouts.app')]
#[Title('Kesiapan Pendakian')]
class ReadinessDashboard extends Component
{
    public TripPlan $trip;

    public ?ReadinessCheck $check = null;

    public function mount(TripPlan $trip, ReadinessService $readiness): void
    {
        $this->authorize('view', $trip);

        $this->trip = $trip->load('trail.mountain');
        $this->check = $readiness->evaluate($this->trip);
    }

    public function recompute(ReadinessService $readiness): void
    {
        $this->authorize('update', $this->trip);

        $this->check = $readiness->evaluate($this->trip);
    }

    public function confirmPreDeparture(AnalyticsRecorder $analytics): void
    {
        $this->authorize('update', $this->trip);

        if ($this->check === null || $this->check->computed_state === ReadinessState::NOT_RECOMMENDED) {
            return;
        }

        $this->check->update(['pre_departure_confirmed' => true]);
        $this->trip->update(['status' => TripStatus::READY_FOR_DEPARTURE->value]);

        $analytics->record(AnalyticsEvent::PRE_DEPARTURE_CHECK_COMPLETED, auth()->user(), [
            'trip_plan_id' => $this->trip->id,
        ]);

        session()->flash('status', 'Pre-departure check tercatat.');
    }

    public function render()
    {
        return view('livewire.trips.readiness-dashboard');
    }
}
