<?php

namespace App\Livewire\Trips;

use App\Enums\AnalyticsEvent;
use App\Enums\CompletionState;
use App\Enums\HikingSessionStatus;
use App\Enums\TripStatus;
use App\Models\HikingHistory;
use App\Models\TripPlan;
use App\Services\AnalyticsRecorder;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class TripShow extends Component
{
    public TripPlan $trip;

    public ?string $completion_state = CompletionState::COMPLETED->value;

    public ?string $personal_notes = null;

    public function mount(TripPlan $trip): void
    {
        $this->authorize('view', $trip);

        $this->trip = $trip->load('trail.mountain', 'preparationItems', 'latestReadinessCheck', 'hikingSession');
    }

    public function startHike(): void
    {
        $this->authorize('update', $this->trip);

        $this->trip->hikingSession()->firstOrCreate([], [
            'user_id' => $this->trip->user_id,
            'status' => HikingSessionStatus::ACTIVE->value,
            'started_at' => now(),
        ]);

        $this->trip->update(['status' => TripStatus::IN_PROGRESS->value]);

        $this->redirectRoute('trips.hike', ['trip' => $this->trip->id], navigate: true);
    }

    public function complete(AnalyticsRecorder $analytics): void
    {
        $this->authorize('update', $this->trip);

        $this->validate([
            'completion_state' => ['required', 'string'],
            'personal_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->trip->update([
            'status' => TripStatus::COMPLETED->value,
            'completed_at' => now(),
        ]);

        $this->trip->hikingSession?->update([
            'status' => HikingSessionStatus::COMPLETED->value,
            'ended_at' => now(),
        ]);

        HikingHistory::updateOrCreate(
            ['trip_plan_id' => $this->trip->id],
            [
                'user_id' => $this->trip->user_id,
                'trail_id' => $this->trip->trail_id,
                'trip_type' => $this->trip->trip_type->value,
                'completion_state' => $this->completion_state,
                'preparation_completion_percent' => $this->trip->preparationCompletionPercent(),
                'personal_notes' => $this->personal_notes,
                'completed_at' => now(),
            ]
        );

        $analytics->record(AnalyticsEvent::TRIP_COMPLETED, auth()->user(), ['trip_plan_id' => $this->trip->id]);

        $this->redirectRoute('reports.create', ['trail' => $this->trip->trail_id, 'trip' => $this->trip->id], navigate: true);
    }

    public function cancel(): void
    {
        $this->authorize('update', $this->trip);

        $this->trip->update(['status' => TripStatus::CANCELLED->value]);
        session()->flash('status', 'Trip dibatalkan.');
    }

    public function render()
    {
        return view('livewire.trips.trip-show', [
            'completionStates' => CompletionState::cases(),
        ])->title($this->trip->name);
    }
}
