<?php

namespace App\Enums;

/**
 * Tingkat aktivitas gunung api menurut PVMBG (DK-43): Level I Normal, II Waspada, III Siaga,
 * IV Awas. Bukan status akses dan tidak pernah dipetakan ke Buka atau Tutup; yang menentukan
 * boleh tidaknya pendakian tetap status resmi pengelola.
 */
enum VolcanoLevel: int
{
    case NORMAL = 1;
    case WASPADA = 2;
    case SIAGA = 3;
    case AWAS = 4;

    /** Atribusi wajib setiap tampilan level (spec v3 bagian 10.7). */
    public const ATRIBUSI = 'PVMBG, Badan Geologi, KESDM (MAGMA Indonesia)';

    /**
     * Nama level seperti di laporan PVMBG, dengan kurung; titik tengah hanya untuk baris ukuran
     * yang padat (DESIGN.md bagian Tipografi).
     */
    public function label(): string
    {
        return match ($this) {
            self::NORMAL => 'Level I (Normal)',
            self::WASPADA => 'Level II (Waspada)',
            self::SIAGA => 'Level III (Siaga)',
            self::AWAS => 'Level IV (Awas)',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function pilihan(): array
    {
        return array_map(fn (self $level) => ['value' => (string) $level->value, 'label' => $level->label()], self::cases());
    }
}
