<?php

namespace App\Enums;

/**
 * Wilayah minat pendaki (DK-42; spec v3 bagian 9.3, "kode provinsi atau kelompok wilayah"):
 * daftar putih kelompok provinsi yang menutup seluruh Indonesia, supaya tidak ada pendaki yang
 * wilayahnya tidak dapat dipilih. Disimpan sebagai larik JSON di user_preferences.interest_regions.
 * Wilayah minat tidak ikut menilai kecocokan jalur (Route Fit tidak berubah, R-021).
 */
enum InterestRegion: string
{
    case JAWA_BARAT_BANTEN = 'JAWA_BARAT_BANTEN';
    case JAWA_TENGAH_DIY = 'JAWA_TENGAH_DIY';
    case JAWA_TIMUR = 'JAWA_TIMUR';
    case BALI_NUSA_TENGGARA = 'BALI_NUSA_TENGGARA';
    case SUMATERA = 'SUMATERA';
    case KALIMANTAN = 'KALIMANTAN';
    case SULAWESI = 'SULAWESI';
    case MALUKU_PAPUA = 'MALUKU_PAPUA';

    public function label(): string
    {
        return match ($this) {
            self::JAWA_BARAT_BANTEN => 'Jawa Barat dan Banten',
            self::JAWA_TENGAH_DIY => 'Jawa Tengah dan DI Yogyakarta',
            self::JAWA_TIMUR => 'Jawa Timur',
            self::BALI_NUSA_TENGGARA => 'Bali dan Nusa Tenggara',
            self::SUMATERA => 'Sumatera',
            self::KALIMANTAN => 'Kalimantan',
            self::SULAWESI => 'Sulawesi',
            self::MALUKU_PAPUA => 'Maluku dan Papua',
        };
    }

    /**
     * Nilai tersimpan menjadi kasus enum dalam urutan daftar ini; nilai yang tidak dikenal
     * dilewati, bukan menjatuhkan halaman.
     *
     * @param  array<int, mixed>|null  $nilai
     * @return list<self>
     */
    public static function dari(?array $nilai): array
    {
        $dikenal = array_map('strval', array_filter($nilai ?? [], 'is_string'));

        return array_values(array_filter(self::cases(), fn (self $wilayah) => in_array($wilayah->value, $dikenal, true)));
    }
}
