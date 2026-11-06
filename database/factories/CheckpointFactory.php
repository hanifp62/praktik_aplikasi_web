<?php

namespace Database\Factories;

use App\Enums\CheckpointType;
use App\Models\Trail;
use Illuminate\Database\Eloquent\Factories\Factory;

class CheckpointFactory extends Factory
{
    public function definition(): array
    {
        return [
            'trail_id' => Trail::factory(),
            'sequence' => fake()->unique()->numberBetween(1, 200),
            'name' => 'Pos '.fake()->numberBetween(1, 5),
            'checkpoint_type' => CheckpointType::POS->value,
            'elevation_m' => fake()->numberBetween(1000, 3000),
        ];
    }
}
