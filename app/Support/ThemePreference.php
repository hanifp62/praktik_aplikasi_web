<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\HtmlString;
use Symfony\Component\HttpFoundation\Cookie as CookieContract;

/**
 * Preferensi tema tampilan: Ikuti sistem, Terang, atau Gelap.
 *
 * Disimpan di cookie, bukan di basis data, supaya tamu yang belum masuk pun dapat
 * memilih dan supaya halaman pertama sudah dirender dengan tema yang benar tanpa
 * menunggu JavaScript. Livewire wire:navigate mengganti atribut <html> pada setiap
 * perpindahan halaman, jadi atribut data-theme harus datang dari server di tiap
 * respons; itu sebabnya nilainya dibaca dari cookie di sini, bukan disetel skrip.
 *
 * Nilai cookie tidak pernah dipercaya mentah-mentah: hanya "light" dan "dark" yang
 * dikenal, selain itu dianggap tidak ada (Ikuti sistem). Cookie ini tidak dienkripsi
 * karena skrip peramban perlu membacanya saat memulihkan snapshot riwayat, dan isinya
 * bukan rahasia.
 */
final class ThemePreference
{
    public const COOKIE = 'tema';

    public const SYSTEM = 'system';

    public const LIGHT = 'light';

    public const DARK = 'dark';

    /**
     * Nilai yang diterima dari pengguna, termasuk "system" yang berarti hapus cookie.
     *
     * @return array<int, string>
     */
    public static function choices(): array
    {
        return [self::SYSTEM, self::LIGHT, self::DARK];
    }

    /**
     * Tema eksplisit dari cookie, atau null bila mengikuti sistem.
     */
    public static function fromRequest(Request $request): ?string
    {
        $nilai = $request->cookie(self::COOKIE);

        return is_string($nilai) && in_array($nilai, [self::LIGHT, self::DARK], true)
            ? $nilai
            : null;
    }

    /**
     * Pilihan yang sedang berlaku, untuk menandai radio di pemilih tema.
     */
    public static function current(Request $request): string
    {
        return self::fromRequest($request) ?? self::SYSTEM;
    }

    /**
     * Atribut untuk tag <html>. Kosong saat mengikuti sistem, sehingga CSS
     * prefers-color-scheme yang menentukan.
     */
    public static function htmlAttribute(Request $request): HtmlString
    {
        $tema = self::fromRequest($request);

        return new HtmlString($tema === null ? '' : 'data-theme="'.$tema.'"');
    }

    /**
     * Cookie untuk menyimpan pilihan, atau cookie kedaluwarsa untuk "system".
     */
    public static function cookieFor(string $pilihan): CookieContract
    {
        if (! in_array($pilihan, [self::LIGHT, self::DARK], true)) {
            return Cookie::forget(self::COOKIE);
        }

        return Cookie::make(
            name: self::COOKIE,
            value: $pilihan,
            minutes: 60 * 24 * 365,
            path: '/',
            secure: (bool) config('session.secure'),
            httpOnly: false,
            sameSite: 'lax',
        );
    }
}
