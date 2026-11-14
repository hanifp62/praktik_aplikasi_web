<?php

namespace App\Enums;

enum UserRole: string
{
    case HIKER = 'hiker';
    case MODERATOR = 'moderator';
    case ADMIN = 'admin';

    // Kurator data resmi (spec v3 bagian 10.7, ruling W8-R7): terpisah dari moderator dan admin.
    // Peran ini hanya diberikan admin lewat keanggotaan badan resmi (AuthorityMemberController),
    // dan meja kurator tetap menuntut keanggotaan aktif, jadi peran saja tidak membuka apa pun.
    case CURATOR = 'curator';

    public function label(): string
    {
        return match ($this) {
            self::HIKER => 'Pendaki',
            self::MODERATOR => 'Moderator',
            self::ADMIN => 'Admin',
            self::CURATOR => 'Kurator',
        };
    }
}
