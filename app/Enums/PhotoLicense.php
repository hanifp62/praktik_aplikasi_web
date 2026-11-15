<?php

namespace App\Enums;

/**
 * Lisensi foto gunung yang diterima (spec v3 bagian 8.7; DK-37). Foto tanpa salah satu lisensi ini
 * tidak diunggah; tanpa foto, halaman gunung memakai visual data.
 */
enum PhotoLicense: string
{
    case CC_BY = 'CC_BY';
    case CC_BY_SA = 'CC_BY_SA';
    case PUBLIC_DOMAIN = 'PUBLIC_DOMAIN';
    case OWN = 'OWN';

    public function label(): string
    {
        return match ($this) {
            self::CC_BY => 'CC BY',
            self::CC_BY_SA => 'CC BY-SA',
            self::PUBLIC_DOMAIN => 'Domain publik',
            self::OWN => 'Milik Memount',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function pilihan(): array
    {
        return array_map(fn (self $lisensi) => ['value' => $lisensi->value, 'label' => $lisensi->label()], self::cases());
    }
}
