<?php

namespace App\Services\RouteFit;

use App\Enums\CompatibilityFactor;

/**
 * One compatibility factor evaluated for a single trail.
 * $score is normalised 0..1; $weight comes from the recommendation_rules table.
 */
readonly class FactorScore
{
    /**
     * @param  bool  $isUnknown  true ketika data yang dibutuhkan faktor ini belum tersedia.
     *                           Skornya tetap dihitung untuk peringkat, tetapi label publik
     *                           tidak boleh naik atas dasar data yang tidak ada (PRD §95).
     */
    public function __construct(
        public CompatibilityFactor $factor,
        public float $score,
        public float $weight,
        public string $detail,
        public bool $isUnknown = false,
    ) {}

    public function weighted(): float
    {
        return $this->score * $this->weight;
    }

    public function isStrong(): bool
    {
        return $this->score >= (float) config('hiking.route_fit.strong_factor_threshold');
    }

    public function isWeak(): bool
    {
        return $this->score < (float) config('hiking.route_fit.weak_factor_threshold');
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
            'is_unknown' => $this->isUnknown,
        ];
    }
}
