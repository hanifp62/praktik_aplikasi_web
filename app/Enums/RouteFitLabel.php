<?php

namespace App\Enums;

enum RouteFitLabel: string
{
    case COCOK = 'COCOK';
    case PERLU_PERSIAPAN = 'PERLU_PERSIAPAN';
    case KURANG_COCOK = 'KURANG_COCOK';

    /**
     * Kata publik untuk kecocokan yang belum dinilai (R-006, DK-41, spec v3 bagian 7).
     * UNKNOWN bukan label keempat (R-004), jadi ia tidak menjadi case enum ini.
     */
    public const UNRATED_TEXT = 'Belum dinilai';

    /**
     * Teks kecocokan yang dikirim ke halaman: labelnya bila sudah dinilai, "Belum dinilai"
     * bila belum. Satu sumber untuk Jelajah, rincian jalur, banding, trip, dan kesiapan,
     * supaya keadaan UNKNOWN yang sama selalu disebut dengan kata yang sama.
     */
    public static function textFor(?self $label): string
    {
        return $label?->label() ?? self::UNRATED_TEXT;
    }

    public function label(): string
    {
        return match ($this) {
            self::COCOK => 'Cocok',
            self::PERLU_PERSIAPAN => 'Perlu persiapan',
            self::KURANG_COCOK => 'Kurang cocok',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::COCOK => 'Karakteristik jalur secara umum sesuai dengan profil dan rencana Anda.',
            self::PERLU_PERSIAPAN => 'Jalur masih relevan, tetapi ada beberapa hal yang perlu diselesaikan lebih dulu.',
            self::KURANG_COCOK => 'Terdapat perbedaan cukup besar antara jalur ini dengan profil atau rencana Anda.',
        };
    }
}
