<?php

namespace App\Enums;

/**
 * Asal satu baris riwayat pendakian (FR-25, ruling W7-R3): TRIP bila tercatat lewat siklus trip,
 * MANUAL bila pendaki mencatat sendiri pendakian di luar trip. Catatan manual ditandai diisi
 * sendiri, dapat diperbaiki dan dihapus, dan tidak pernah menaikkan tingkat pengalaman (BR-11).
 */
enum HikeRecordSource: string
{
    case TRIP = 'TRIP';
    case MANUAL = 'MANUAL';

    public function label(): string
    {
        return match ($this) {
            self::TRIP => 'Dari trip',
            self::MANUAL => 'Dicatat manual',
        };
    }
}
