<?php

namespace App\Enums;

enum SourceType: string
{
    case OFFICIAL = 'OFFICIAL';
    case ADMIN_VERIFIED = 'ADMIN_VERIFIED';
    case COMMUNITY = 'COMMUNITY';
    case AI_INTERPRETATION = 'AI_INTERPRETATION';

    public function label(): string
    {
        return match ($this) {
            self::OFFICIAL => 'Resmi',
            self::ADMIN_VERIFIED => 'Terverifikasi admin',
            self::COMMUNITY => 'Komunitas',
            self::AI_INTERPRETATION => 'Interpretasi sistem',
        };
    }

    public function authorityRank(): int
    {
        return match ($this) {
            self::OFFICIAL => 1,
            self::ADMIN_VERIFIED => 2,
            self::COMMUNITY => 3,
            self::AI_INTERPRETATION => 4,
        };
    }
}
