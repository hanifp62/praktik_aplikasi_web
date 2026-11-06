<?php

namespace App\Enums;

enum HikingSessionStatus: string
{
    case ACTIVE = 'ACTIVE';
    case PAUSED = 'PAUSED';
    case COMPLETED = 'COMPLETED';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Berlangsung',
            self::PAUSED => 'Dijeda',
            self::COMPLETED => 'Selesai',
        };
    }
}
