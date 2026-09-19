<?php

namespace App\Services\RouteFit;

use App\Enums\CompatibilityFactor;

/**
 * One compatibility factor evaluated for a single trail.
 * $score is normalised 0..1; $weight comes from the recommendation_rules table.
 */
readonly class FactorScore
{
    public function __construct(
        public CompatibilityFactor $factor,
        public float $score,
        public float $weight,
        public string $detail,
    ) {}

    public function weighted(): float
    {
        return $this->score * $this->weight;
    }

    public function isStrong(): bool
    {
        return $this->score >= 0.75;
    }

    public function isWeak(): bool
    {
        return $this->score < 0.5;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'factor' => $this->factor->value,
            'label' => $this->factor->label(),
            'score' => round($this->score, 4),
            'weight' => $this->weight,
            'detail' => $this->detail,
        ];
    }
}
