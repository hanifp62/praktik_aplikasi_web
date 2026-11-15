<?php

namespace App\Enums;

enum ExperienceLevel: string
{
    case BEGINNER = 'BEGINNER';
    case INTERMEDIATE = 'INTERMEDIATE';
    case ADVANCED = 'ADVANCED';
    case EXPERT = 'EXPERT';

    public function label(): string
    {
        return match ($this) {
            self::BEGINNER => 'Pemula',
            self::INTERMEDIATE => 'Menengah',
            self::ADVANCED => 'Berpengalaman',
            self::EXPERT => 'Ahli',
        };
    }

    /**
     * Penjelasan tingkat di onboarding dan Profil (prototype 00). Tingkat diisi sendiri dan
     * tidak naik otomatis karena riwayat (BR-11).
     */
    public function keterangan(): string
    {
        return match ($this) {
            self::BEGINNER => 'Baru beberapa kali, di jalur yang ramai dan bertanda.',
            self::INTERMEDIATE => 'Sudah beberapa kali, termasuk bermalam di gunung.',
            self::ADVANCED => 'Rutin mendaki jalur panjang dan membaca medan sendiri.',
            self::EXPERT => 'Navigasi mandiri, termasuk di jalur yang jarang dilalui.',
        };
    }

    /**
     * Ordinal position used for comparing hiker capability against route demand.
     */
    public function rank(): int
    {
        return match ($this) {
            self::BEGINNER => 1,
            self::INTERMEDIATE => 2,
            self::ADVANCED => 3,
            self::EXPERT => 4,
        };
    }
}
