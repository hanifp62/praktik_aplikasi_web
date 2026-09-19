<?php

namespace Database\Factories;

use App\Enums\ConditionTag;
use App\Enums\ModerationStatus;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrailConditionReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'trail_id' => Trail::factory(),
            'user_id' => User::factory(),
            'hike_date' => now()->subDays(2)->toDateString(),
            'condition_tags' => [ConditionTag::MUDDY->value],
            'note' => fake()->sentence(),
            'moderation_status' => ModerationStatus::PENDING->value,
        ];
    }

    public function approved(): static
    {
        return $this->state([
            'moderation_status' => ModerationStatus::APPROVED->value,
            'moderated_at' => now(),
        ]);
    }
}
