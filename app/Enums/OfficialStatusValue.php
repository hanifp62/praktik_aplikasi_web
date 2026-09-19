<?php

namespace App\Enums;

enum OfficialStatusValue: string
{
    case OPEN = 'OPEN';
    case RESTRICTED = 'RESTRICTED';
    case CLOSED = 'CLOSED';
    case UNKNOWN = 'UNKNOWN';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Buka',
            self::RESTRICTED => 'Dibatasi',
            self::CLOSED => 'Tutup',
            self::UNKNOWN => 'Belum diketahui',
        };
    }

    /**
     * BR-04: an officially closed route never becomes an active recommendation.
     */
    public function excludesFromRecommendation(): bool
    {
        return $this === self::CLOSED;
    }
}
