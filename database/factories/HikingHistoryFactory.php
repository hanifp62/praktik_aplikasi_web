<?php

namespace Database\Factories;

use App\Enums\CompletionState;
use App\Enums\TripType;
use App\Models\HikingHistory;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HikingHistory>
 */
class HikingHistoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'trip_plan_id' => TripPlan::factory(),
            'trail_id' => Trail::factory(),
            'trip_type' => TripType::TEKTOK->value,
            'completion_state' => CompletionState::COMPLETED->value,
            'preparation_completion_percent' => 100,
            'completed_at' => now()->subMonth(),
        ];
    }

    /**
     * Pendakian yang berbalik di tengah jalan. Dipakai untuk membuktikan bahwa tangga
     * kemajuan tidak memakainya sebagai acuan: berbalik tidak membuktikan puncaknya
     * tercapai.
     */
    public function abandoned(): static
    {
        return $this->state(fn () => ['completion_state' => CompletionState::ABANDONED->value]);
    }
}
