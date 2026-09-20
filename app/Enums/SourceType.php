<?php

namespace App\Enums;

enum SourceType: string
{
    case OFFICIAL = 'OFFICIAL';
    case ADMIN_VERIFIED = 'ADMIN_VERIFIED';
    // Disumbang pemandu bersertifikat jenjang Ahli yang disahkan badan resmi.
    // Berada di antara ADMIN_VERIFIED dan COMMUNITY: bukan pernyataan pengelola,
    // tetapi juga bukan masukan anonim (PRD §43).
    case ACCREDITED_EXPERT = 'ACCREDITED_EXPERT';

    case COMMUNITY = 'COMMUNITY';
    case AI_INTERPRETATION = 'AI_INTERPRETATION';

    public function label(): string
    {
        return match ($this) {
            self::OFFICIAL => 'Resmi',
            self::ADMIN_VERIFIED => 'Terverifikasi admin',
            self::ACCREDITED_EXPERT => 'Ahli bersertifikat',
            self::COMMUNITY => 'Komunitas',
            self::AI_INTERPRETATION => 'Interpretasi sistem',
        };
    }

    public function authorityRank(): int
    {
        return match ($this) {
            self::OFFICIAL => 1,
            self::ADMIN_VERIFIED => 2,
            self::ACCREDITED_EXPERT => 3,
            self::COMMUNITY => 4,
            self::AI_INTERPRETATION => 5,
        };
    }
}
