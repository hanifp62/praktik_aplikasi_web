<?php

namespace App\Enums;

enum ExperienceLevel: string
{
    case BEGINNER = 'BEGINNER';
    case INTERMEDIATE = 'INTERMEDIATE';
    case ADVANCED = 'ADVANCED';
    case EXPERT = 'EXPERT';

    public function label(): string
    {
        return match ($this) {
            self::BEGINNER => 'Pemula',
            self::INTERMEDIATE => 'Menengah',
            self::ADVANCED => 'Berpengalaman',
            self::EXPERT => 'Ahli',
        };
    }

    /**
     * Ordinal position used for comparing hiker capability against route demand.
     */
    public function rank(): int
    {
        return match ($this) {
            self::BEGINNER => 1,
            self::INTERMEDIATE => 2,
            self::ADVANCED => 3,
            self::EXPERT => 4,
        };
    }
}
