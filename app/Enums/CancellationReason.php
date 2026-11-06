<?php

namespace App\Enums;

/**
 * Alasan pembatalan trip (DK-50): daftar putih yang sama untuk batal sebelum berangkat dan
 * berbalik di jalur, supaya Progres dapat menulis "Dibatalkan · Cuaca". Opsional: membatalkan
 * adalah keputusan yang benar, dan alasannya tidak pernah diwajibkan.
 */
enum CancellationReason: string
{
    case WEATHER = 'WEATHER';
    case OFFICIAL_STATUS = 'OFFICIAL_STATUS';
    case PHYSICAL_CONDITION = 'PHYSICAL_CONDITION';
    case LOGISTICS = 'LOGISTICS';
    case OTHER = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::WEATHER => 'Cuaca',
            self::OFFICIAL_STATUS => 'Status resmi',
            self::PHYSICAL_CONDITION => 'Kondisi tubuh',
            self::LOGISTICS => 'Logistik',
            self::OTHER => 'Lainnya',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function pilihan(): array
    {
        return array_map(fn (self $alasan) => ['value' => $alasan->value, 'label' => $alasan->label()], self::cases());
    }
}
