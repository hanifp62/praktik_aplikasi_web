<?php

namespace App\Enums;

/**
 * Jenis bukti di balik sebuah catatan status resmi (spec v3 bagian 10.7).
 *
 * Bukti primer datang dari pengelola sendiri. Berita media adalah bukti sekunder: ia tidak
 * pernah dapat membuka jalur, dan pembatasan yang hanya berbukti berita menunggu verifikasi
 * orang kedua sebelum terbit (ruling W8-R6).
 */
enum EvidenceType: string
{
    case OFFICIAL_LETTER = 'OFFICIAL_LETTER';
    case OFFICIAL_PAGE = 'OFFICIAL_PAGE';
    case OFFICIAL_ACCOUNT = 'OFFICIAL_ACCOUNT';
    case NEWS = 'NEWS';

    public function label(): string
    {
        return match ($this) {
            self::OFFICIAL_LETTER => 'Surat atau dokumen resmi',
            self::OFFICIAL_PAGE => 'Laman resmi pengelola',
            self::OFFICIAL_ACCOUNT => 'Akun media sosial resmi pengelola',
            self::NEWS => 'Berita media',
        };
    }

    public function isPrimary(): bool
    {
        return $this !== self::NEWS;
    }
}
