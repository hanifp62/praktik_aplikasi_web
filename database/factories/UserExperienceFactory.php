<?php

namespace Database\Factories;

use App\Enums\NavigationExperience;
use App\Models\User;
use App\Models\UserExperience;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserExperienceFactory extends Factory
{
    protected $model = UserExperience::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'completed_hikes_count' => 0,
            'terrain_experience' => [],
            'navigation_experience' => NavigationExperience::NONE->value,
        ];
    }
}
