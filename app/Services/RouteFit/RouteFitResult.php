<?php

namespace App\Services\RouteFit;

use App\Enums\RouteFitLabel;
use App\Models\Trail;

/**
 * Output of the Route Fit Engine for one trail (PRD §72).
 * internal_score exists for ranking/audit only and must never be rendered as a public score (BR-09).
 */
readonly class RouteFitResult
{
    /**
     * @param  array<int, FactorScore>  $factors
     * @param  array<int, string>  $failedRules
     * @param  array<int, string>  $warnings
     * @param  array<string, array<int, string>>  $explanation
     */
    public function __construct(
        public Trail $trail,
        public bool $eligible,
        public ?RouteFitLabel $label,
        public float $internalScore,
        public array $factors,
        public array $failedRules,
        public array $warnings,
        public array $explanation,
        public string $engineVersion,
    ) {}

    /**
     * Apakah ada faktor yang tidak dapat dinilai karena datanya belum tersedia.
     * Dipakai untuk menahan label agar tidak naik atas dasar ketiadaan data (PRD §95).
     */
    public function hasUnknownFactors(): bool
    {
        foreach ($this->factors as $factor) {
            if ($factor->isUnknown) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, FactorScore>
     */
    public function unknownFactors(): array
    {
        return array_values(array_filter($this->factors, fn (FactorScore $f) => $f->isUnknown));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function factorsToArray(): array
    {
        return array_map(fn (FactorScore $factor) => $factor->toArray(), $this->factors);
    }

    /**
     * @return array<int, FactorScore>
     */
    public function strongFactors(): array
    {
        return array_values(array_filter($this->factors, fn (FactorScore $f) => $f->isStrong()));
    }

    /**
     * @return array<int, FactorScore>
     */
    public function weakFactors(): array
    {
        return array_values(array_filter($this->factors, fn (FactorScore $f) => $f->isWeak()));
    }
}
