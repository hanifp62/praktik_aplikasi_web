<?php

namespace App\Enums;

enum WaterAvailability: string
{
    case ABUNDANT = 'ABUNDANT';
    case LIMITED = 'LIMITED';
    case SCARCE = 'SCARCE';
    case UNKNOWN = 'UNKNOWN';

    public function label(): string
    {
        return match ($this) {
            self::ABUNDANT => 'Banyak sumber air',
            self::LIMITED => 'Sumber air terbatas',
            self::SCARCE => 'Sumber air sangat sedikit',
            self::UNKNOWN => 'Belum diketahui',
        };
    }
}
