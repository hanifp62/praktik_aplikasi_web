<?php

namespace App\Livewire\Trips;

use App\Enums\AnalyticsEvent;
use App\Enums\CompletionState;
use App\Enums\HikingSessionStatus;
use App\Enums\TripStatus;
use App\Models\HikingHistory;
use App\Models\TripPlan;
use App\Services\AnalyticsRecorder;
use Illuminate\Validation\Rules\Enum;
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

        if (! $this->guardTransition(TripStatus::IN_PROGRESS)) {
            return;
        }

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

        // Kolomnya di-cast ke enum, jadi nilai asing yang lolos validasi akan
        // menghasilkan error 500 alih-alih pesan yang dapat dibaca pengguna.
        $this->validate([
            'completion_state' => ['required', new Enum(CompletionState::class)],
            'personal_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! $this->guardTransition(TripStatus::COMPLETED)) {
            return;
        }

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

        if (! $this->guardTransition(TripStatus::CANCELLED)) {
            return;
        }

        $this->trip->update(['status' => TripStatus::CANCELLED->value]);
        session()->flash('status', 'Trip dibatalkan.');
    }

    /**
     * PRD §35: COMPLETED dan CANCELLED bersifat final. Tanpa penjaga ini, trip yang
     * sudah dibatalkan masih dapat dimulai dan diselesaikan, dan trip yang sudah
     * selesai masih dapat dibatalkan surut.
     */
    private function guardTransition(TripStatus $target): bool
    {
        if ($this->trip->status->canTransitionTo($target)) {
            return true;
        }

        session()->flash('status', sprintf(
            'Tindakan tidak dapat dilakukan: trip berstatus %s.',
            $this->trip->status->label()
        ));

        return false;
    }

    public function render()
    {
        return view('livewire.trips.trip-show', [
            'completionStates' => CompletionState::cases(),
        ])->title($this->trip->name);
    }
}
