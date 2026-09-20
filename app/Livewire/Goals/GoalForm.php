<?php

namespace App\Livewire\Goals;

use App\Enums\AnalyticsEvent;
use App\Enums\PreferredChallenge;
use App\Enums\TripType;
use App\Models\HikingGoal;
use App\Services\AnalyticsRecorder;
use App\Services\RouteFitService;
use App\Support\Timezone;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Enum;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * FR-03 hiking goal: the trip context fed into the Route Fit Engine.
 */
#[Layout('layouts.app')]
#[Title('Rencana Pendakian')]
class GoalForm extends Component
{
    public ?string $target_date = null;

    public ?string $region = null;

    public ?string $trip_type = null;

    public ?int $expected_duration_minutes = null;

    public ?string $preferred_challenge = null;

    public ?int $max_elevation_gain_m = null;

    public ?string $notes = null;

    public function mount(): void
    {
        $preference = auth()->user()->preference;

        $this->trip_type = $preference?->preferred_trip_type?->value;
        $this->region = $preference?->region_preference;
        $this->expected_duration_minutes = $preference?->preferred_duration?->approximateMinutes();
        $this->preferred_challenge = $preference?->preferred_challenge?->value;
        $this->max_elevation_gain_m = $preference?->max_elevation_gain_preference_m;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'target_date' => ['nullable', 'date', 'after_or_equal:'.Timezone::earliestDateInIndonesia()],
            'region' => ['nullable', 'string', 'max:100'],
            'trip_type' => ['required', new Enum(TripType::class)],
            'expected_duration_minutes' => ['nullable', 'integer', 'min:60', 'max:20160'],
            'preferred_challenge' => ['nullable', new Enum(PreferredChallenge::class)],
            'max_elevation_gain_m' => ['nullable', 'integer', 'min:0', 'max:9000'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function save(RouteFitService $routeFit, AnalyticsRecorder $analytics): void
    {
        $this->validate();
        $user = auth()->user();

        if (! $user->hasCompletedProfile()) {
            session()->flash('status', 'Lengkapi profil pendaki terlebih dahulu.');
            $this->redirectRoute('onboarding', navigate: true);

            return;
        }

        // PRD §100. Satu run mengevaluasi seluruh jalur kandidat dan menyimpan jejak
        // auditnya, jadi biayanya sebanding dengan ukuran dataset.
        $key = 'recommendation-run:'.$user->id;
        $limit = (int) config('hiking.rate_limits.recommendation_runs_per_hour');

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            $this->addError('form', sprintf(
                'Anda sudah membuat %d rencana dalam satu jam terakhir. Coba lagi dalam %d menit.',
                $limit,
                (int) ceil(RateLimiter::availableIn($key) / 60)
            ));

            return;
        }

        RateLimiter::hit($key, 3600);

        $goal = HikingGoal::create([
            'user_id' => $user->id,
            'target_date' => $this->target_date,
            'region' => $this->region,
            'trip_type' => $this->trip_type,
            'expected_duration_minutes' => $this->expected_duration_minutes,
            'preferred_challenge' => $this->preferred_challenge,
            'max_elevation_gain_m' => $this->max_elevation_gain_m,
            'notes' => $this->notes,
        ]);

        $run = $routeFit->recommend($user, $goal);
        $analytics->record(AnalyticsEvent::RECOMMENDATION_VIEWED, $user, ['recommendation_run_id' => $run->id]);

        $this->redirectRoute('recommendations.show', ['run' => $run->id], navigate: true);
    }

    public function render()
    {
        return view('livewire.goals.goal-form', [
            'tripTypes' => TripType::cases(),
            'challenges' => PreferredChallenge::cases(),
        ]);
    }
}
