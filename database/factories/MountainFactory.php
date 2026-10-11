<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MountainFactory extends Factory
{
    public function definition(): array
    {
        // Tanpa apostrof, alasan yang sama dengan TrailFactory.
        $name = 'Gunung '.str_replace("'", '', fake()->unique()->firstName());

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'province' => 'Jawa Tengah',
            'region' => 'Jawa Tengah',
            'elevation_mdpl' => fake()->numberBetween(1500, 3700),
            'description' => fake()->sentence(),
        ];
    }
}
