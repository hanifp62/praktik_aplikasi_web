<?php

namespace App\Enums;

/**
 * Keadaan verifikasi catatan status resmi dari meja kurator (spec v3 bagian 10.7; ruling W8-R4).
 *
 * Catatan lama dari sebelum meja kurator tidak punya keadaan (kolom kosong) dan tetap dibaca
 * pendaki. Draf tidak pernah terlihat. Menunggu verifikasi kedua dan dipersoalkan hanya terlihat
 * bila layanan terbit menandainya boleh terbit lebih dulu (pembatasan dengan bukti primer).
 */
enum StatusVerificationState: string
{
    case DRAFT = 'DRAFT';
    case PENDING = 'PENDING';
    case VERIFIED = 'VERIFIED';
    case DISPUTED = 'DISPUTED';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draf',
            self::PENDING => 'Menunggu verifikasi kedua',
            self::VERIFIED => 'Terverifikasi',
            self::DISPUTED => 'Dipersoalkan',
        };
    }
}
