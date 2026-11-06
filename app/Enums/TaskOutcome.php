<?php

namespace App\Enums;

/**
 * Tiga tingkat hasil tugas, persis seperti lembar catatan protokol.
 *
 * Nilai numeriknya dipakai menghitung tingkat keberhasilan. "Berhasil dengan kesulitan"
 * diberi setengah, bukan satu: tugas yang selesai setelah peserta tersesat dua kali
 * bukan tugas yang berhasil, dan membulatkannya menjadi berhasil menghapus persis
 * masalah yang sedang dicari.
 */
enum TaskOutcome: string
{
    case SUCCESS = 'SUCCESS';
    case STRUGGLED = 'STRUGGLED';
    case FAILED = 'FAILED';

    public function label(): string
    {
        return match ($this) {
            self::SUCCESS => 'Berhasil',
            self::STRUGGLED => 'Berhasil dengan kesulitan',
            self::FAILED => 'Gagal',
        };
    }

    public function credit(): float
    {
        return match ($this) {
            self::SUCCESS => 1.0,
            self::STRUGGLED => 0.5,
            self::FAILED => 0.0,
        };
    }
}
