<?php

namespace App\Enums;

enum ModerationStatus: string
{
    case PENDING = 'PENDING';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';
    case FLAGGED = 'FLAGGED';
    case REMOVED = 'REMOVED';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Menunggu moderasi',
            self::APPROVED => 'Disetujui',
            self::REJECTED => 'Ditolak',
            self::FLAGGED => 'Ditandai',
            self::REMOVED => 'Dihapus',
        };
    }

    public function isPubliclyVisible(): bool
    {
        return $this === self::APPROVED;
    }
}
