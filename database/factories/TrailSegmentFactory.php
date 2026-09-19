<?php

namespace Database\Factories;

use App\Enums\TechnicalDemand;
use App\Models\Trail;
use App\Models\TrailSegment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrailSegment>
 */
class TrailSegmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'trail_id' => Trail::factory(),
            'sequence' => 1,
            'name' => 'Segmen '.fake()->unique()->numberBetween(1, 9999),
            'description' => null,
            'distance_km' => 2.5,
            'elevation_gain_m' => 300,
            'technical_demand' => TechnicalDemand::MODERATE->value,
            'terrain_character' => ['FOREST'],
        ];
    }
}
