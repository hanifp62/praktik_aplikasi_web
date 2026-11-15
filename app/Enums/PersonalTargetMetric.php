<?php

namespace App\Enums;

/**
 * Ukuran target pribadi bulanan (FR-25, PRD §150): tanjakan dalam meter, jarak dalam kilometer,
 * atau jumlah pendakian. Setiap ukuran dibandingkan hanya dengan pendakian pendaki itu sendiri
 * pada bulan berjalan (R-015).
 */
enum PersonalTargetMetric: string
{
    case GAIN = 'GAIN';
    case DISTANCE = 'DISTANCE';
    case HIKES = 'HIKES';

    public function label(): string
    {
        return match ($this) {
            self::GAIN => 'Tanjakan per bulan',
            self::DISTANCE => 'Jarak per bulan',
            self::HIKES => 'Pendakian per bulan',
        };
    }

    /** Satuan yang ditulis di belakang angka; pendakian ditulis tanpa satuan. */
    public function satuan(): string
    {
        return match ($this) {
            self::GAIN => 'm',
            self::DISTANCE => 'km',
            self::HIKES => '',
        };
    }

    /** Nama isian formulir target. */
    public function isian(): string
    {
        return match ($this) {
            self::GAIN => 'tanjakan_m',
            self::DISTANCE => 'jarak_km',
            self::HIKES => 'pendakian',
        };
    }

    /** Batas atas yang masuk akal untuk satu bulan, bukan penilaian. */
    public function maksimum(): int
    {
        return match ($this) {
            self::GAIN => 20000,
            self::DISTANCE => 1000,
            self::HIKES => 31,
        };
    }
}
