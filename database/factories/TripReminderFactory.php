<?php

namespace Database\Factories;

use App\Models\TripPlan;
use App\Models\TripReminder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TripReminder>
 */
class TripReminderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trip_plan_id' => TripPlan::factory(),
            'jenis' => 'H-1',
            'dijadwalkan_untuk' => now()->addDays(6),
        ];
    }

    public function terkirim(): static
    {
        return $this->state(fn () => ['sent_at' => now()]);
    }
}
