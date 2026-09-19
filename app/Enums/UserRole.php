<?php

namespace App\Enums;

enum UserRole: string
{
    case HIKER = 'hiker';
    case MODERATOR = 'moderator';
    case ADMIN = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::HIKER => 'Pendaki',
            self::MODERATOR => 'Moderator',
            self::ADMIN => 'Admin',
        };
    }
}
