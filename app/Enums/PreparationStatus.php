<?php

namespace App\Enums;

enum PreparationStatus: string
{
    case CONFIRMED = 'CONFIRMED';
    case NOT_CONFIRMED = 'NOT_CONFIRMED';
    case NOT_APPLICABLE = 'NOT_APPLICABLE';

    /**
     * PRD §37: NOT_CONFIRMED means "belum dikonfirmasi", never "tidak dimiliki".
     */
    public function label(): string
    {
        return match ($this) {
            self::CONFIRMED => 'Sudah dikonfirmasi',
            self::NOT_CONFIRMED => 'Belum dikonfirmasi',
            self::NOT_APPLICABLE => 'Tidak berlaku',
        };
    }
}
