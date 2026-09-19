<?php

namespace Database\Factories;

use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TripPlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'trail_id' => Trail::factory(),
            'name' => 'Trip '.fake()->word(),
            'planned_date' => now()->addWeek()->toDateString(),
            'trip_type' => TripType::CAMPING->value,
            'status' => TripStatus::PLANNED->value,
        ];
    }
}
