<?php

namespace App\Enums;

/**
 * Bagian data yang dilaporkan keliru oleh pendaki (ruling W8-R17). Daftar tertutup supaya
 * antrean kurator dapat dipilah tanpa membaca isi laporan lebih dulu.
 */
enum DataErrorCategory: string
{
    case STATUS = 'STATUS';
    case PERMIT = 'PERMIT';
    case ROUTE = 'ROUTE';
    case OTHER = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::STATUS => 'Status akses',
            self::PERMIT => 'Perizinan',
            self::ROUTE => 'Data jalur: jarak, pos, atau peta',
            self::OTHER => 'Lainnya',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function pilihan(): array
    {
        return array_map(fn (self $bagian) => ['value' => $bagian->value, 'label' => $bagian->label()], self::cases());
    }
}
