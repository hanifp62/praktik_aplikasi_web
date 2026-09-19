<?php

namespace App\Enums;

enum PreferredDuration: string
{
    case HALF_DAY = 'HALF_DAY';
    case ONE_DAY = 'ONE_DAY';
    case TWO_DAY = 'TWO_DAY';
    case THREE_PLUS_DAY = 'THREE_PLUS_DAY';

    public function label(): string
    {
        return match ($this) {
            self::HALF_DAY => 'Setengah hari',
            self::ONE_DAY => '1 hari',
            self::TWO_DAY => '2 hari',
            self::THREE_PLUS_DAY => '3 hari atau lebih',
        };
    }

    public function approximateMinutes(): int
    {
        return match ($this) {
            self::HALF_DAY => 6 * 60,
            self::ONE_DAY => 12 * 60,
            self::TWO_DAY => 30 * 60,
            self::THREE_PLUS_DAY => 60 * 60,
        };
    }
}
