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
