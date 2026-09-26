<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\HtmlString;
use Symfony\Component\HttpFoundation\Cookie as CookieContract;

/**
 * Preferensi Tampilan di Profil: Efek transparan (Otomatis atau Solid) dan Gerak (Ikuti
 * sistem atau Kurangi).
 *
 * Safari dan Firefox belum meneruskan prefers-reduced-transparency, dan sebagian orang
 * tidak tahu letak setelan gerak di sistemnya (riset docs/riset/2026-09-25-uiux/01 §5.2).
 * Sama dengan tema: disimpan di cookie supaya tamu pun dapat memilih, dirender server
 * sebagai atribut <html> supaya halaman pertama sudah benar sebelum skrip berjalan, dan
 * nilainya diwhitelist. Hanya nilai yang bukan bawaan yang disimpan; memilih bawaan
 * menghapus cookie. Tidak dienkripsi karena skrip menyamakan atribut <html> setelah
 * kunjungan Inertia, dan isinya bukan rahasia.
 */
final class DisplayPreference
{
    public const COOKIE_EFEK = 'efek';

    public const COOKIE_GERAK = 'gerak';

    /** Efek: bawaan Otomatis (kaca bila perangkat mampu), atau Solid. */
    public const EFEK_OTOMATIS = 'otomatis';

    public const EFEK_SOLID = 'solid';

    /** Gerak: bawaan mengikuti sistem, atau dikurangi di aplikasi ini saja. */
    public const GERAK_SISTEM = 'sistem';

    public const GERAK_KURANGI = 'kurangi';

    /**
     * @return array<int, string>
     */
    public static function efekChoices(): array
    {
        return [self::EFEK_OTOMATIS, self::EFEK_SOLID];
    }

    /**
     * @return array<int, string>
     */
    public static function gerakChoices(): array
    {
        return [self::GERAK_SISTEM, self::GERAK_KURANGI];
    }

    public static function efek(Request $request): string
    {
        return $request->cookie(self::COOKIE_EFEK) === self::EFEK_SOLID ? self::EFEK_SOLID : self::EFEK_OTOMATIS;
    }

    public static function gerak(Request $request): string
    {
        return $request->cookie(self::COOKIE_GERAK) === self::GERAK_KURANGI ? self::GERAK_KURANGI : self::GERAK_SISTEM;
    }

    /**
     * Atribut untuk tag <html>, hanya untuk nilai yang bukan bawaan.
     */
    public static function htmlAttributes(Request $request): HtmlString
    {
        $atribut = [];

        if (self::efek($request) === self::EFEK_SOLID) {
            $atribut[] = 'data-efek="solid"';
        }

        if (self::gerak($request) === self::GERAK_KURANGI) {
            $atribut[] = 'data-gerak="kurangi"';
        }

        return new HtmlString(implode(' ', $atribut));
    }

    /**
     * Cookie untuk pilihan yang bukan bawaan, atau cookie kedaluwarsa untuk bawaan.
     */
    public static function cookieFor(string $nama, string $pilihan): CookieContract
    {
        $bukanBawaan = match ($nama) {
            self::COOKIE_EFEK => $pilihan === self::EFEK_SOLID,
            self::COOKIE_GERAK => $pilihan === self::GERAK_KURANGI,
            default => false,
        };

        if (! $bukanBawaan) {
            return Cookie::forget($nama);
        }

        return Cookie::make(
            name: $nama,
            value: $pilihan,
            minutes: 60 * 24 * 365,
            path: '/',
            secure: (bool) config('session.secure'),
            httpOnly: false,
            sameSite: 'lax',
        );
    }
}
