<?php

namespace App\Enums;

enum NavigationExperience: string
{
    case NONE = 'NONE';
    case BASIC = 'BASIC';
    case COMPETENT = 'COMPETENT';
    case ADVANCED = 'ADVANCED';

    public function label(): string
    {
        return match ($this) {
            self::NONE => 'Belum ada',
            self::BASIC => 'Dasar',
            self::COMPETENT => 'Cukup mahir',
            self::ADVANCED => 'Mahir',
        };
    }

    /**
     * Penjelasan pengalaman navigasi di onboarding dan Profil, dengan tingkatan yang sama dengan
     * tuntutan navigasi jalur (NavigationComplexity::expectedNavigationRank).
     */
    public function keterangan(): string
    {
        return match ($this) {
            self::NONE => 'Mengikuti jalur yang jelas atau rombongan.',
            self::BASIC => 'Membaca papan penunjuk dan jalur bertanda.',
            self::COMPETENT => 'Membaca peta dan memilih arah di percabangan sendiri.',
            self::ADVANCED => 'Navigasi mandiri di jalur yang jarang dilalui.',
        };
    }

    public function rank(): int
    {
        return match ($this) {
            self::NONE => 0,
            self::BASIC => 1,
            self::COMPETENT => 2,
            self::ADVANCED => 3,
        };
    }
}
