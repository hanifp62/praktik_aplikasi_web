<?php

namespace App\Enums;

/**
 * Golongan alasan sebuah status resmi (spec v3 bagian 10.7). Kalimat alasan dari pengelola tetap
 * di kolom `reason`; golongan ini membuat alasan dapat dibaca dan disaring dengan kata yang sama.
 */
enum StatusReasonCategory: string
{
    case FIRE = 'FIRE';
    case ERUPTION = 'ERUPTION';
    case WEATHER = 'WEATHER';
    case MAINTENANCE = 'MAINTENANCE';
    case EVENT = 'EVENT';
    case ACCIDENT = 'ACCIDENT';
    case OTHER = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::FIRE => 'Kebakaran',
            self::ERUPTION => 'Erupsi',
            self::WEATHER => 'Cuaca',
            self::MAINTENANCE => 'Pemeliharaan',
            self::EVENT => 'Acara',
            self::ACCIDENT => 'Kecelakaan',
            self::OTHER => 'Lainnya',
        };
    }
}
