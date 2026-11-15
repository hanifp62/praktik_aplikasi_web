<?php

namespace App\Enums;

/**
 * Keadaan laporan kesalahan data (ruling W8-R17): menunggu cek ulang kurator, atau sudah dicek
 * beserta hasilnya. Tidak ada keadaan "diterima" yang mengubah data: perubahan status tetap
 * lewat catatan baru di meja kurator (R-010).
 */
enum DataErrorState: string
{
    case OPEN = 'OPEN';
    case CHECKED = 'CHECKED';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Menunggu cek ulang',
            self::CHECKED => 'Sudah dicek',
        };
    }
}
