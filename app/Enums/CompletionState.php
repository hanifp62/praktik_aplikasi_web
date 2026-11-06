<?php

namespace App\Enums;

enum CompletionState: string
{
    case COMPLETED = 'COMPLETED';
    case PARTIAL = 'PARTIAL';
    case ABANDONED = 'ABANDONED';

    public function label(): string
    {
        return match ($this) {
            self::COMPLETED => 'Selesai sampai target',
            self::PARTIAL => 'Selesai sebagian',
            self::ABANDONED => 'Dibatalkan di jalur',
        };
    }
}
