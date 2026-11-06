<?php

namespace App\Enums;

enum PreferredChallenge: string
{
    case RELAXED = 'RELAXED';
    case MODERATE = 'MODERATE';
    case CHALLENGING = 'CHALLENGING';

    public function label(): string
    {
        return match ($this) {
            self::RELAXED => 'Santai',
            self::MODERATE => 'Sedang',
            self::CHALLENGING => 'Menantang',
        };
    }

    public function rank(): int
    {
        return match ($this) {
            self::RELAXED => 1,
            self::MODERATE => 2,
            self::CHALLENGING => 3,
        };
    }
}
