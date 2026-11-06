<?php

namespace App\Enums;

enum PreparationCategory: string
{
    case EQUIPMENT = 'EQUIPMENT';
    case ROUTE_KNOWLEDGE = 'ROUTE_KNOWLEDGE';
    case LOGISTICS = 'LOGISTICS';
    case WEATHER = 'WEATHER';
    case OFFICIAL_STATUS = 'OFFICIAL_STATUS';

    public function label(): string
    {
        return match ($this) {
            self::EQUIPMENT => 'Perlengkapan',
            self::ROUTE_KNOWLEDGE => 'Pemahaman jalur',
            self::LOGISTICS => 'Logistik',
            self::WEATHER => 'Cuaca',
            self::OFFICIAL_STATUS => 'Status resmi',
        };
    }
}
