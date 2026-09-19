<?php

namespace App\Support;

use App\Models\Trail;
use Illuminate\Support\Carbon;

/**
 * Konversi zona waktu Indonesia.
 *
 * Seluruh instan disimpan dalam UTC. Zona untuk menampilkannya kembali melekat pada
 * gunung, bukan pada pengguna, prakiraan cuaca sebuah jalur di Rinjani tetap WITA
 * meskipun dibaca pendaki yang sedang berada di Jakarta.
 */
class Timezone
{
    /**
     * @var array<string, string>
     */
    public const SUPPORTED = [
        'Asia/Jakarta' => 'WIB',
        'Asia/Makassar' => 'WITA',
        'Asia/Jayapura' => 'WIT',
    ];

    public const DEFAULT = 'Asia/Jakarta';

    public static function forTrail(Trail $trail): string
    {
        $timezone = $trail->relationLoaded('mountain')
            ? $trail->mountain?->timezone
            : $trail->mountain()->value('timezone');

        return self::isSupported($timezone) ? $timezone : self::DEFAULT;
    }

    public static function label(?string $timezone): string
    {
        return self::SUPPORTED[$timezone] ?? self::SUPPORTED[self::DEFAULT];
    }

    public static function isSupported(?string $timezone): bool
    {
        return $timezone !== null && array_key_exists($timezone, self::SUPPORTED);
    }

    /**
     * Instan UTC menjadi teks siap tampil, lengkap dengan penanda zonanya.
     * Tanpa penanda itu pengguna tidak punya cara tahu jam mana yang dimaksud.
     */
    public static function display(?Carbon $instant, string $timezone, string $format = 'd M Y H:i'): ?string
    {
        if ($instant === null) {
            return null;
        }

        return $instant->copy()->setTimezone($timezone)->translatedFormat($format).' '.self::label($timezone);
    }
}
