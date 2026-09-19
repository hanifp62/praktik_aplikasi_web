<?php

namespace App\Enums;

enum TripStatus: string
{
    case DRAFT = 'DRAFT';
    case PLANNED = 'PLANNED';
    case READY_FOR_DEPARTURE = 'READY_FOR_DEPARTURE';
    case IN_PROGRESS = 'IN_PROGRESS';
    case COMPLETED = 'COMPLETED';
    case CANCELLED = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PLANNED => 'Direncanakan',
            self::READY_FOR_DEPARTURE => 'Siap berangkat',
            self::IN_PROGRESS => 'Sedang berlangsung',
            self::COMPLETED => 'Selesai',
            self::CANCELLED => 'Dibatalkan',
        };
    }

    public function isActive(): bool
    {
        return ! in_array($this, [self::COMPLETED, self::CANCELLED], true);
    }
}
