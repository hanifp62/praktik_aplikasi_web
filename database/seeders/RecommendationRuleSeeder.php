<?php

namespace Database\Seeders;

use App\Enums\CompatibilityFactor;
use App\Models\RecommendationRule;
use App\Services\RouteFitService;
use Illuminate\Database\Seeder;

/**
 * V1 compatibility weights (PRD §27). These are configuration, not fixed law - they are meant
 * to be recalibrated after usability testing.
 */
class RecommendationRuleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (CompatibilityFactor::cases() as $factor) {
            RecommendationRule::updateOrCreate(
                ['key' => $factor->value],
                [
                    'category' => 'compatibility',
                    'description' => $factor->label(),
                    'weight' => $factor->defaultWeight(),
                    'active' => true,
                    'engine_version' => RouteFitService::ENGINE_VERSION,
                ]
            );
        }
    }
}
