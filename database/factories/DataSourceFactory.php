<?php

namespace Database\Factories;

use App\Enums\SourceType;
use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class DataSourceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'source_name' => 'Pengelola '.fake()->unique()->company(),
            'source_type' => SourceType::OFFICIAL->value,
            'source_url' => fake()->url(),
            'source_owner' => 'Pengelola jalur',
            'retrieved_at' => now(),
            'verified_at' => now(),
            'freshness_policy' => 'Mengikuti pembaruan pengelola',
            'verification_status' => VerificationStatus::VERIFIED->value,
        ];
    }
}
