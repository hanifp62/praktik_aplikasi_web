<?php

namespace Database\Factories;

use App\Enums\OfficialStatusValue;
use App\Enums\StatusScope;
use App\Models\Trail;
use Illuminate\Database\Eloquent\Factories\Factory;

class OfficialStatusFactory extends Factory
{
    public function definition(): array
    {
        return [
            'statusable_type' => (new Trail)->getMorphClass(),
            'statusable_id' => Trail::factory(),
            'scope' => StatusScope::TRAIL->value,
            'status' => OfficialStatusValue::OPEN->value,
            'source' => 'Pengelola jalur',
            'published_at' => now()->subDay(),
            'fetched_at' => now(),
            'verified_at' => now(),
            'effective_at' => now()->subDay(),
        ];
    }

    public function closed(): static
    {
        return $this->state(['status' => OfficialStatusValue::CLOSED->value]);
    }

    public function restricted(): static
    {
        return $this->state(['status' => OfficialStatusValue::RESTRICTED->value]);
    }
}
