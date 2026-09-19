<?php

namespace App\Enums;

enum ReadinessState: string
{
    case READY = 'READY';
    case NEEDS_PREPARATION = 'NEEDS_PREPARATION';
    case NOT_RECOMMENDED = 'NOT_RECOMMENDED';

    public function label(): string
    {
        return match ($this) {
            self::READY => 'Siap',
            self::NEEDS_PREPARATION => 'Perlu persiapan',
            self::NOT_RECOMMENDED => 'Tidak direkomendasikan',
        };
    }
}
