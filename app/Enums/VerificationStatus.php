<?php

namespace App\Enums;

enum VerificationStatus: string
{
    case VERIFIED = 'VERIFIED';
    case UNVERIFIED = 'UNVERIFIED';
    case DISPUTED = 'DISPUTED';

    public function label(): string
    {
        return match ($this) {
            self::VERIFIED => 'Terverifikasi',
            self::UNVERIFIED => 'Belum diverifikasi',
            self::DISPUTED => 'Diperdebatkan',
        };
    }
}
