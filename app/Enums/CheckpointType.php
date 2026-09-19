<?php

namespace App\Enums;

enum CheckpointType: string
{
    case BASECAMP = 'BASECAMP';
    case POS = 'POS';
    case SHELTER = 'SHELTER';
    case WATER_SOURCE = 'WATER_SOURCE';
    case CAMPGROUND = 'CAMPGROUND';
    case JUNCTION = 'JUNCTION';
    case SUMMIT = 'SUMMIT';
    case DANGER_POINT = 'DANGER_POINT';

    public function label(): string
    {
        return match ($this) {
            self::BASECAMP => 'Basecamp',
            self::POS => 'Pos',
            self::SHELTER => 'Shelter',
            self::WATER_SOURCE => 'Sumber air',
            self::CAMPGROUND => 'Area camping',
            self::JUNCTION => 'Persimpangan',
            self::SUMMIT => 'Puncak',
            self::DANGER_POINT => 'Titik rawan',
        };
    }
}
