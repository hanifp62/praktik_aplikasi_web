<?php

namespace App\Livewire\Onboarding;

use App\Enums\AnalyticsEvent;
use App\Enums\ExperienceLevel;
use App\Enums\NavigationExperience;
use App\Enums\PreferredChallenge;
use App\Enums\PreferredDuration;
use App\Enums\TerrainCharacter;
use App\Enums\TripType;
use App\Services\AnalyticsRecorder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * FR-02 hiker profile. Self-reported experience is an input, never a certification (PRD §22).
 */
#[Layout('layouts.app')]
#[Title('Profil Pendaki')]
class ProfileSetup extends Component
{
    public int $step = 1;

    public ?string $experience_level = null;

    public ?string $region_preference = null;

    public ?string $bio = null;

    public int $completed_hikes_count = 0;

    /** @var array<int, string> */
    public array $terrain_experience = [];

    public ?string $navigation_experience = null;

    public ?int $longest_hike_duration_minutes = null;

    public ?int $highest_elevation_gain_m = null;

    public ?string $preferred_duration = null;

    public ?string $preferred_trip_type = null;

    public ?string $preferred_challenge = null;

    public ?int $max_elevation_gain_preference_m = null;

    public function mount(): void
    {
        $user = auth()->user();

        $this->experience_level = $user->profile?->experience_level?->value;
        $this->region_preference = $user->profile?->region_preference;
        $this->bio = $user->profile?->bio;

        $this->completed_hikes_count = $user->experience?->completed_hikes_count ?? 0;
        $this->terrain_experience = $user->experience?->terrain_experience ?? [];
        $this->navigation_experience = $user->experience?->navigation_experience?->value;
        $this->longest_hike_duration_minutes = $user->experience?->longest_hike_duration_minutes;
        $this->highest_elevation_gain_m = $user->experience?->highest_elevation_gain_m;

        $this->preferred_duration = $user->preference?->preferred_duration?->value;
        $this->preferred_trip_type = $user->preference?->preferred_trip_type?->value;
        $this->preferred_challenge = $user->preference?->preferred_challenge?->value;
        $this->max_elevation_gain_preference_m = $user->preference?->max_elevation_gain_preference_m;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'experience_level' => ['required', new Enum(ExperienceLevel::class)],
            'region_preference' => ['nullable', 'string', 'max:100'],
            'bio' => ['nullable', 'string', 'max:500'],
            'completed_hikes_count' => ['required', 'integer', 'min:0', 'max:2000'],
            'terrain_experience' => ['array'],
            'terrain_experience.*' => [Rule::enum(TerrainCharacter::class)],
            'navigation_experience' => ['required', new Enum(NavigationExperience::class)],
            'longest_hike_duration_minutes' => ['nullable', 'integer', 'min:0', 'max:20160'],
            'highest_elevation_gain_m' => ['nullable', 'integer', 'min:0', 'max:9000'],
            'preferred_duration' => ['required', new Enum(PreferredDuration::class)],
            'preferred_trip_type' => ['required', new Enum(TripType::class)],
            'preferred_challenge' => ['nullable', new Enum(PreferredChallenge::class)],
            'max_elevation_gain_preference_m' => ['nullable', 'integer', 'min:0', 'max:9000'],
        ];
    }

    public function nextStep(): void
    {
        $this->validate(match ($this->step) {
            1 => array_intersect_key($this->rules(), array_flip(['experience_level', 'region_preference', 'bio'])),
            2 => array_intersect_key($this->rules(), array_flip([
                'completed_hikes_count', 'terrain_experience', 'terrain_experience.*',
                'navigation_experience', 'longest_hike_duration_minutes', 'highest_elevation_gain_m',
            ])),
            default => [],
        });

        $this->step = min(3, $this->step + 1);
    }

    public function previousStep(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function save(AnalyticsRecorder $analytics): void
    {
        $this->validate();
        $user = auth()->user();

        $user->profile()->updateOrCreate([], [
            'experience_level' => $this->experience_level,
            'region_preference' => $this->region_preference,
            'bio' => $this->bio,
            'completed_at' => now(),
        ]);

        $user->experience()->updateOrCreate([], [
            'completed_hikes_count' => $this->completed_hikes_count,
            'terrain_experience' => $this->terrain_experience,
            'navigation_experience' => $this->navigation_experience,
            'longest_hike_duration_minutes' => $this->longest_hike_duration_minutes,
            'highest_elevation_gain_m' => $this->highest_elevation_gain_m,
        ]);

        $user->preference()->updateOrCreate([], [
            'preferred_duration' => $this->preferred_duration,
            'preferred_trip_type' => $this->preferred_trip_type,
            'preferred_challenge' => $this->preferred_challenge,
            'max_elevation_gain_preference_m' => $this->max_elevation_gain_preference_m,
            'region_preference' => $this->region_preference,
        ]);

        $analytics->record(AnalyticsEvent::PROFILE_COMPLETED, $user);

        session()->flash('status', 'Profil pendaki tersimpan.');
        $this->redirectRoute('goals.create', navigate: true);
    }

    public function render()
    {
        return view('livewire.onboarding.profile-setup', [
            'experienceLevels' => ExperienceLevel::cases(),
            'navigationLevels' => NavigationExperience::cases(),
            'terrainOptions' => TerrainCharacter::cases(),
            'durations' => PreferredDuration::cases(),
            'tripTypes' => TripType::cases(),
            'challenges' => PreferredChallenge::cases(),
        ]);
    }
}
