<?php

namespace App\Enums;

enum RouteFitLabel: string
{
    case COCOK = 'COCOK';
    case PERLU_PERSIAPAN = 'PERLU_PERSIAPAN';
    case KURANG_COCOK = 'KURANG_COCOK';

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
