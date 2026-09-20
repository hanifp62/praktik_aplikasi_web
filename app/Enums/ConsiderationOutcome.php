<?php

namespace App\Enums;

/**
 * Hasil satu aksi `toggle()` pada daftar pertimbangan.
 *
 * Bool lama menyamakan "dihapus" dengan "ditolak karena penuh" -- dua akibat pengguna
 * yang sangat berbeda dibungkus jadi satu `false`. Halaman yang menampilkan "dihapus"
 * kepada pendaki yang sebenarnya ditolak adalah persis kelas kegagalan diam yang coba
 * dicegah batas lima ini; enum ini membuat pemanggil wajib membedakannya.
 */
enum ConsiderationOutcome: string
{
    case DITAMBAHKAN = 'DITAMBAHKAN';
    case DIHAPUS = 'DIHAPUS';
    case DITOLAK = 'DITOLAK';

    public function label(): string
    {
        return match ($this) {
            self::DITAMBAHKAN => 'Ditambahkan ke pertimbangan',
            self::DIHAPUS => 'Dihapus dari pertimbangan',
            self::DITOLAK => 'Ditolak, daftar pertimbangan sudah penuh',
        };
    }
}
