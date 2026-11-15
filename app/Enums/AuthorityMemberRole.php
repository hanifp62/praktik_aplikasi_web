<?php

namespace App\Enums;

/**
 * Peran seseorang di sebuah badan resmi untuk meja kurator (spec v3 bagian 10.7, Q11; ruling
 * W8-R7). Tahap 1: kurator Memount menyalin dari sumber resmi badan itu. Tahap 2: penerbit mitra,
 * petugas badan itu sendiri, sesudah kemitraan. Keduanya tunduk pada aturan terbit asimetris
 * yang sama dan hanya di kawasan badannya.
 */
enum AuthorityMemberRole: string
{
    case KURATOR = 'KURATOR';
    case PENERBIT_MITRA = 'PENERBIT_MITRA';

    public function label(): string
    {
        return match ($this) {
            self::KURATOR => 'Kurator Memount',
            self::PENERBIT_MITRA => 'Penerbit mitra',
        };
    }
}
