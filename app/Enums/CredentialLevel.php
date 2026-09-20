<?php

namespace App\Enums;

/**
 * Jenjang sertifikasi pemandu wisata gunung menurut SKKNI: Muda, Madya, Ahli.
 *
 * Bukan tingkatan karangan. APGI bersama BNSP mensertifikasi pada tiga jenjang ini, dan
 * standarnya berlaku sejak Kepmenaker 2011.
 *
 * Hanya jenjang tertinggi yang memperoleh hak menyumbang data jalur. Data jalur menjadi
 * dasar rekomendasi yang dibaca pendaki pemula, jadi ambangnya sengaja diletakkan di
 * atas, bukan di tengah.
 */
enum CredentialLevel: string
{
    case MUDA = 'MUDA';
    case MADYA = 'MADYA';
    case AHLI = 'AHLI';

    public function label(): string
    {
        return match ($this) {
            self::MUDA => 'Pemandu Wisata Gunung Muda',
            self::MADYA => 'Pemandu Wisata Gunung Madya',
            self::AHLI => 'Pemandu Wisata Gunung Ahli',
        };
    }

    public function rank(): int
    {
        return match ($this) {
            self::MUDA => 1,
            self::MADYA => 2,
            self::AHLI => 3,
        };
    }

    /**
     * Ambang untuk menyumbang data jalur.
     */
    public function mayContributeTrailData(): bool
    {
        return $this === self::AHLI;
    }
}
