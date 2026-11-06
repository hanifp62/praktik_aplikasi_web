<?php

namespace App\Enums;

/**
 * Keadaan kesiapan sebuah rencana (PRD §39). Kata publiknya mengikuti DK-48 (prototype 09 dan
 * 10): menyebut apa yang dinilai, bukan vonis atas pendakian atau orangnya. Kesiapan adalah
 * dukungan keputusan, bukan izin medis, izin hukum, atau jaminan keselamatan (R-014).
 */
enum ReadinessState: string
{
    case READY = 'READY';
    case NEEDS_PREPARATION = 'NEEDS_PREPARATION';
    case NOT_RECOMMENDED = 'NOT_RECOMMENDED';

    public function label(): string
    {
        return match ($this) {
            self::READY => 'Persiapan kritis lengkap',
            self::NEEDS_PREPARATION => 'Masih perlu persiapan',
            self::NOT_RECOMMENDED => 'Tidak disarankan dengan rencana ini',
        };
    }
}
