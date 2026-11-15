<?php

namespace App\Enums;

/**
 * Cara sebuah status resmi berakhir (spec v3 bagian 10.7). Memisahkan dua arti `expires_at`
 * kosong: pengelola menyebut "sampai pemberitahuan lebih lanjut", atau pengelola tidak menyebut
 * apa pun (R-007).
 */
enum StatusEndCondition: string
{
    case ON_DATE = 'ON_DATE';
    case UNTIL_NOTICE = 'UNTIL_NOTICE';
    case NOT_STATED = 'NOT_STATED';

    public function label(): string
    {
        return match ($this) {
            self::ON_DATE => 'Sampai tanggal tertentu',
            self::UNTIL_NOTICE => 'Sampai pemberitahuan lebih lanjut',
            self::NOT_STATED => 'Tidak disebut pengelola',
        };
    }
}
