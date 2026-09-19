<?php

namespace App\Enums;

enum CompatibilityFactor: string
{
    case EXPERIENCE_MATCH = 'experience_match';
    case DURATION_MATCH = 'duration_match';
    case TERRAIN_MATCH = 'terrain_match';
    case TECHNICAL_MATCH = 'technical_match';
    case ELEVATION_GAIN_MATCH = 'elevation_gain_match';
    case NAVIGATION_MATCH = 'navigation_match';
    case TRIP_PREFERENCE = 'trip_preference';

    public function label(): string
    {
        return match ($this) {
            self::EXPERIENCE_MATCH => 'Kesesuaian pengalaman',
            self::DURATION_MATCH => 'Kesesuaian durasi',
            self::TERRAIN_MATCH => 'Kesesuaian medan',
            self::TECHNICAL_MATCH => 'Kesesuaian tingkat teknis',
            self::ELEVATION_GAIN_MATCH => 'Kesesuaian elevation gain',
            self::NAVIGATION_MATCH => 'Kesesuaian navigasi',
            self::TRIP_PREFERENCE => 'Preferensi perjalanan',
        };
    }

    public function defaultWeight(): float
    {
        return match ($this) {
            self::EXPERIENCE_MATCH => 0.30,
            self::DURATION_MATCH => 0.15,
            self::TERRAIN_MATCH => 0.15,
            self::TECHNICAL_MATCH => 0.15,
            self::ELEVATION_GAIN_MATCH => 0.10,
            self::NAVIGATION_MATCH => 0.10,
            self::TRIP_PREFERENCE => 0.05,
        };
    }
}
