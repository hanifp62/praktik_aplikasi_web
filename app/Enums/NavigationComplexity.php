<?php

namespace App\Enums;

enum NavigationComplexity: string
{
    case LOW = 'LOW';
    case MODERATE = 'MODERATE';
    case HIGH = 'HIGH';

    public function label(): string
    {
        return match ($this) {
            self::LOW => 'Rendah',
            self::MODERATE => 'Sedang',
            self::HIGH => 'Tinggi',
        };
    }

    public function rank(): int
    {
        return match ($this) {
            self::LOW => 1,
            self::MODERATE => 2,
            self::HIGH => 3,
        };
    }

    public function expectedNavigationRank(): int
    {
        return match ($this) {
            self::LOW => 0,
            self::MODERATE => 1,
            self::HIGH => 2,
        };
    }
}
