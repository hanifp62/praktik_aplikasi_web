<?php

namespace App\Enums;

/**
 * Hasil satu aksi `toggle()` atau `simpan()` pada daftar pertimbangan.
 *
 * Bool lama menyamakan "dihapus" dengan "ditolak karena penuh" -- dua akibat pengguna
 * yang sangat berbeda dibungkus jadi satu `false`. Halaman yang menampilkan "dihapus"
 * kepada pendaki yang sebenarnya ditolak adalah persis kelas kegagalan diam yang coba
 * dicegah batas lima ini; enum ini membuat pemanggil wajib membedakannya.
 *
 * SUDAH_ADA hanya dikembalikan `simpan()`, supaya klien tidak menawarkan Urungkan
 * (yang berupa hapus) untuk baris yang tidak dibuatnya sendiri.
 */
enum ConsiderationOutcome: string
{
    case DITAMBAHKAN = 'DITAMBAHKAN';
    case DIHAPUS = 'DIHAPUS';
    case DITOLAK = 'DITOLAK';
    case SUDAH_ADA = 'SUDAH_ADA';

    public function label(): string
    {
        return match ($this) {
            self::DITAMBAHKAN => 'Disimpan',
            self::DIHAPUS => 'Dihapus dari simpanan',
            self::DITOLAK => 'Simpanan sudah penuh',
            self::SUDAH_ADA => 'Sudah ada di simpanan',
        };
    }
}
