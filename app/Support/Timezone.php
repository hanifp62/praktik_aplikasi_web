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

    /**
     * Lantai untuk tanggal yang harus berada di masa depan, seperti tanggal rencana.
     *
     * Aturan bawaan Laravel memakai "today" yang diselesaikan menurut zona aplikasi,
     * dan zona itu UTC. Tidak ada pengguna sistem ini yang hidup di UTC: selama tujuh
     * jam setiap hari tanggal UTC masih tanggal kemarin bagi WIB, sembilan jam bagi WIT,
     * dan di jendela itu rencana untuk hari yang sudah lewat lolos validasi.
     *
     * WIB adalah zona Indonesia yang paling akhir berganti hari, jadi tanggalnya selalu
     * yang terkecil di antara ketiganya. Memakainya sebagai lantai membuat aturan ini
     * tidak pernah menolak rencana sah dari zona mana pun, sekaligus menutup jendela
     * tujuh jam tadi.
     */
    public static function earliestDateInIndonesia(): string
    {
        return Carbon::now('Asia/Jakarta')->toDateString();
    }

    /**
     * Langit-langit untuk tanggal yang harus sudah lewat, seperti tanggal pendakian
     * pada laporan kondisi.
     *
     * Kebalikannya: WIT paling dahulu berganti hari, jadi tanggalnya selalu yang
     * terbesar. Tanpa ini, pendaki yang turun dini hari lalu melaporkan kondisi jalur
     * hari itu juga ditolak karena bagi UTC tanggalnya masih besok.
     */
    public static function latestDateInIndonesia(): string
    {
        return Carbon::now('Asia/Jayapura')->toDateString();
    }

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
