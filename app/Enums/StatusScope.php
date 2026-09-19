<?php

namespace App\Enums;

enum StatusScope: string
{
    case MOUNTAIN = 'MOUNTAIN';
    case TRAIL = 'TRAIL';
    case SEGMENT = 'SEGMENT';
    case AREA = 'AREA';

    public function label(): string
    {
        return match ($this) {
            self::MOUNTAIN => 'Gunung',
            self::TRAIL => 'Jalur',
            self::SEGMENT => 'Segmen',
            self::AREA => 'Area',
        };
    }
}
