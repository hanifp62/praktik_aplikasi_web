<?php

namespace Database\Factories;

use App\Enums\ExperienceLevel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'experience_level' => ExperienceLevel::BEGINNER->value,
            'completed_at' => now(),
        ];
    }
}
