<?php

namespace App\Livewire\Trips;

use App\Enums\AnalyticsEvent;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Services\AnalyticsRecorder;
use App\Services\PreparationService;
use Illuminate\Validation\Rules\Enum;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * FR-08 trip plan creation. Preparation items are generated from the chosen trail immediately
 * so the plan is never a generic checklist (PRD §36).
 */
#[Layout('layouts.app')]
#[Title('Buat Rencana Trip')]
class TripForm extends Component
{
    public ?int $trail_id = null;

    public ?int $hiking_goal_id = null;

    public string $name = '';

    public ?string $planned_date = null;

    public ?string $start_time = null;

    public ?string $trip_type = null;

    public ?string $notes = null;

    public function mount(?int $trail = null, ?int $goal = null): void
    {
        $this->trail_id = $trail;
        $this->hiking_goal_id = $goal;

        $selected = $trail ? Trail::published()->find($trail) : null;

        if ($selected) {
            $this->name = 'Pendakian '.$selected->name;
        }

        $latestGoal = $goal
            ? auth()->user()->hikingGoals()->find($goal)
            : auth()->user()->hikingGoals()->latest()->first();

        $this->trip_type = $latestGoal?->trip_type?->value ?? auth()->user()->preference?->preferred_trip_type?->value;
        $this->planned_date = $latestGoal?->target_date?->toDateString();
        $this->hiking_goal_id = $latestGoal?->id;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'trail_id' => ['required', 'exists:trails,id'],
            'hiking_goal_id' => ['nullable', 'exists:hiking_goals,id'],
            'name' => ['required', 'string', 'max:120'],
            'planned_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'trip_type' => ['required', new Enum(TripType::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function save(PreparationService $preparation, AnalyticsRecorder $analytics): void
    {
        $this->validate();

        $goalId = $this->hiking_goal_id;

        if ($goalId !== null && ! auth()->user()->hikingGoals()->whereKey($goalId)->exists()) {
            $goalId = null;
        }

        $trip = TripPlan::create([
            'user_id' => auth()->id(),
            'trail_id' => $this->trail_id,
            'hiking_goal_id' => $goalId,
            'name' => $this->name,
            'planned_date' => $this->planned_date,
            'start_time' => $this->start_time,
            'trip_type' => $this->trip_type,
            'notes' => $this->notes,
            'status' => TripStatus::PLANNED->value,
        ]);

        $preparation->generateFor($trip);

        $analytics->record(AnalyticsEvent::TRIP_CREATED, auth()->user(), ['trip_plan_id' => $trip->id]);
        $analytics->record(AnalyticsEvent::PREPARATION_STARTED, auth()->user(), ['trip_plan_id' => $trip->id]);

        $this->redirectRoute('trips.preparation', ['trip' => $trip->id], navigate: true);
    }

    public function render()
    {
        return view('livewire.trips.trip-form', [
            'trails' => Trail::published()->with('mountain')->orderBy('name')->get(),
            'tripTypes' => TripType::cases(),
        ]);
    }
}
