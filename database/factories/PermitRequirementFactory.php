<?php

namespace Database\Factories;

use App\Models\PermitRequirement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PermitRequirement>
 */
class PermitRequirementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'trail_id' => null,
            'mountain_id' => null,
            'authority' => 'Balai Besar Taman Nasional',
            'booking_url' => 'https://contoh.test/booking',
            'daily_quota' => 200,
            'booking_opens_days_before' => 30,
            'booking_closes_days_before' => 2,
            'guide_required' => false,
            'max_duration_days' => 2,
            'notes' => null,
            'source' => 'Situs resmi pengelola',
            'source_url' => 'https://contoh.test',
            'verified_at' => now()->subWeek(),
        ];
    }
}
