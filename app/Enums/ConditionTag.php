<?php

namespace App\Enums;

enum ConditionTag: string
{
    case MUDDY = 'MUDDY';
    case WET = 'WET';
    case SLIPPERY = 'SLIPPERY';
    case DRY = 'DRY';
    case CROWDED = 'CROWDED';
    case QUIET = 'QUIET';
    case LOW_VISIBILITY = 'LOW_VISIBILITY';
    case GOOD_MARKING = 'GOOD_MARKING';
    case POOR_MARKING = 'POOR_MARKING';
    case WATER_AVAILABLE = 'WATER_AVAILABLE';
    case WATER_SCARCE = 'WATER_SCARCE';

    public function label(): string
    {
        return match ($this) {
            self::MUDDY => 'Berlumpur',
            self::WET => 'Basah',
            self::SLIPPERY => 'Licin',
            self::DRY => 'Kering',
            self::CROWDED => 'Ramai',
            self::QUIET => 'Sepi',
            self::LOW_VISIBILITY => 'Jarak pandang terbatas',
            self::GOOD_MARKING => 'Penanda jalur jelas',
            self::POOR_MARKING => 'Penanda jalur kurang jelas',
            self::WATER_AVAILABLE => 'Air tersedia',
            self::WATER_SCARCE => 'Air sulit didapat',
        };
    }

    public function isCaution(): bool
    {
        return in_array($this, [
            self::MUDDY, self::SLIPPERY, self::LOW_VISIBILITY, self::POOR_MARKING, self::WATER_SCARCE,
        ], true);
    }
}
