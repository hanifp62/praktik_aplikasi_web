<?php

namespace App\Enums;

enum TerrainCharacter: string
{
    case FOREST = 'FOREST';
    case SAVANNA = 'SAVANNA';
    case ROCKY = 'ROCKY';
    case SCREE = 'SCREE';
    case SAND = 'SAND';
    case MUD = 'MUD';
    case RIVER_CROSSING = 'RIVER_CROSSING';
    case EXPOSED_RIDGE = 'EXPOSED_RIDGE';
    case STEEP_SLOPE = 'STEEP_SLOPE';

    public function label(): string
    {
        return match ($this) {
            self::FOREST => 'Hutan',
            self::SAVANNA => 'Savana',
            self::ROCKY => 'Berbatu',
            self::SCREE => 'Kerikil lepas',
            self::SAND => 'Pasir',
            self::MUD => 'Berlumpur',
            self::RIVER_CROSSING => 'Penyeberangan sungai',
            self::EXPOSED_RIDGE => 'Punggungan terbuka',
            self::STEEP_SLOPE => 'Tanjakan curam',
        };
    }

    /**
     * Terrain types that generally require prior exposure before being comfortable.
     */
    public function isDemanding(): bool
    {
        return in_array($this, [self::SCREE, self::RIVER_CROSSING, self::EXPOSED_RIDGE, self::STEEP_SLOPE], true);
    }
}
