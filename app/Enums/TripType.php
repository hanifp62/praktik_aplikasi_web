<?php

namespace App\Enums;

enum TripType: string
{
    case TEKTOK = 'TEKTOK';
    case CAMPING = 'CAMPING';
    case MULTI_DAY = 'MULTI_DAY';

    public function label(): string
    {
        return match ($this) {
            self::TEKTOK => 'Tektok (pulang hari)',
            self::CAMPING => 'Camping',
            self::MULTI_DAY => 'Multi-hari',
        };
    }

    public function typicalMaxDurationMinutes(): int
    {
        return match ($this) {
            self::TEKTOK => 16 * 60,
            self::CAMPING => 36 * 60,
            self::MULTI_DAY => 96 * 60,
        };
    }
}
