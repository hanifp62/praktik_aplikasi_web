<?php

namespace App\Enums;

enum TripType: string
{
    case TEKTOK = 'TEKTOK';
    case CAMPING = 'CAMPING';
    case MULTI_DAY = 'MULTI_DAY';

    public function label(): string
    {
        return match ($this) {
            self::TEKTOK => 'Tektok (pulang hari)',
            self::CAMPING => 'Camping',
            self::MULTI_DAY => 'Multi-hari',
        };
    }

    /**
     * Nama pendek untuk kontrol segmen dan kepala trip (prototype 08 dan 09); lamanya ditulis
     * terpisah oleh lamaHari(), jadi "pulang hari" tidak diulang di samping "1 hari".
     */
    public function labelPendek(): string
    {
        return match ($this) {
            self::TEKTOK => 'Tektok',
            self::CAMPING => 'Camping',
            self::MULTI_DAY => 'Multi-hari',
        };
    }

    /**
     * Lama bawaan dalam hari (spec v3 bagian 6.8): mengisi tanggal selesai begitu tanggal
     * naik dipilih. Multi-hari boleh lebih panjang; pendaki memilih tanggal turunnya sendiri.
     */
    public function lamaHari(): int
    {
        return match ($this) {
            self::TEKTOK => 1,
            self::CAMPING => 2,
            self::MULTI_DAY => 3,
        };
    }

    public function typicalMaxDurationMinutes(): int
    {
        return match ($this) {
            self::TEKTOK => 16 * 60,
            self::CAMPING => 36 * 60,
            self::MULTI_DAY => 96 * 60,
        };
    }
}
