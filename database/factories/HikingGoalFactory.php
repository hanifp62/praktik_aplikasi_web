<?php

namespace Database\Factories;

use App\Enums\PreferredChallenge;
use App\Enums\TripType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class HikingGoalFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'target_date' => now()->addWeek()->toDateString(),
            'region' => null,
            'trip_type' => TripType::CAMPING->value,
            'expected_duration_minutes' => 720,
            'preferred_challenge' => PreferredChallenge::MODERATE->value,
        ];
    }

    public function tektok(): static
    {
        return $this->state([
            'trip_type' => TripType::TEKTOK->value,
            'expected_duration_minutes' => 600,
        ]);
    }
}
