<?php

namespace App\Enums;

enum TechnicalDemand: string
{
    case LOW = 'LOW';
    case MODERATE = 'MODERATE';
    case HIGH = 'HIGH';
    case VERY_HIGH = 'VERY_HIGH';

    public function label(): string
    {
        return match ($this) {
            self::LOW => 'Rendah',
            self::MODERATE => 'Sedang',
            self::HIGH => 'Tinggi',
            self::VERY_HIGH => 'Sangat tinggi',
        };
    }

    public function rank(): int
    {
        return match ($this) {
            self::LOW => 1,
            self::MODERATE => 2,
            self::HIGH => 3,
            self::VERY_HIGH => 4,
        };
    }

    /**
     * Minimum experience level generally expected before a hiker meets this demand unassisted.
     */
    public function expectedExperienceRank(): int
    {
        return match ($this) {
            self::LOW => 1,
            self::MODERATE => 2,
            self::HIGH => 3,
            self::VERY_HIGH => 4,
        };
    }
}
