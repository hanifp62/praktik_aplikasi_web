<?php

namespace Database\Factories;

use App\Enums\PersonalTargetMetric;
use App\Models\PersonalTarget;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PersonalTarget>
 */
class PersonalTargetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'metric' => PersonalTargetMetric::GAIN->value,
            'monthly_value' => 1000,
        ];
    }
}
