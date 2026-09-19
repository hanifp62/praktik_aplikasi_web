<?php

namespace App\Enums;

enum FreshnessState: string
{
    case CURRENT = 'CURRENT';
    case AGING = 'AGING';
    case STALE = 'STALE';
    case UNKNOWN = 'UNKNOWN';

    public function label(): string
    {
        return match ($this) {
            self::CURRENT => 'Terbaru',
            self::AGING => 'Mulai lama',
            self::STALE => 'Sudah lama',
            self::UNKNOWN => 'Tidak diketahui',
        };
    }
}
