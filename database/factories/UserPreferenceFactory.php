<?php

namespace Database\Factories;

use App\Enums\PreferredDuration;
use App\Enums\TripType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserPreferenceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'preferred_duration' => PreferredDuration::ONE_DAY->value,
            'preferred_trip_type' => TripType::CAMPING->value,
        ];
    }
}
