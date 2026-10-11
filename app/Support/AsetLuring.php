<?php

namespace App\Support;

/**
 * Daftar berkas yang disimpan service worker untuk shell luring (spec v3 bagian 10.4; rencana
 * induk W6 butir 3; ruling W6-R9, diamandemen W6-9g di sisipan T9b).
 *
 * Dua daftar, disimpan pada saat yang berbeda:
 * - daftar(): shell kecil yang disimpan setiap pemasangan service worker, sama di kedua keadaan
 *   sakelar paket: halaman luring, dua ikon, dan app.css. Sebelum W6 hanya halaman luring dan
 *   ikon; app.css ditambah W6 supaya halaman luring tergambar walau stylesheet-nya belum pernah
 *   tersimpan lewat kunjungan lain (tinjauan m-5).
 * - daftarPaket(): shell paket, hanya selama paket lapangan dibuka (hiking.field_pack.enabled):
 *   entri shell luring beserta graf impornya, MapLibre js dan css, dan perekam jejak. Daftar ini
 *   tidak ikut pemasangan. Service worker menyimpannya saat pendaki mengunduh paket (pesan
 *   simpan-shell-paket dari Tugas 10), lalu setiap versi berikutnya membawanya lagi saat dipasang
 *   bila penanda shell paket ada di cache shell versi lain. Dengan begitu pemasangan service
 *   worker tidak lagi menagih sekitar 0,4 MB gzip (MapLibre, graf shell, dan huruf) kepada
 *   pengunjung yang tidak pernah mengunduh paket, termasuk tamu di halaman masuk (prinsip
 *   resources/js/dasar.js:1-12).
 *
 * Huruf ada di daftar HURUF tersendiri: service worker menyimpannya satu per satu bersama shell
 * paket, sehingga huruf yang gagal diambil tidak menggagalkan addAll yang semua-atau-tidak
 * (pra-tinjau PT9-16 bagian c). Kedua daftar hanya memuat aset berversi; isi dari server tidak
 * pernah masuk.
 */
final class AsetLuring
{
    public const ENTRI_SHELL = 'resources/js/luring/shell.tsx';

    public const PEREKAM_JEJAK = 'resources/js/jejak.js';

    /**
     * Akhiran `src` berkas MapLibre di manifest build. Dicocokkan sebagai akhiran, bukan kunci
     * utuh: bila node_modules bertaut simbolik atau junction, Rolldown menulis kunci dan `src`
     * absolut (tinjauan m-3). CSS-nya disebut sendiri karena tidak pernah terjangkau
     * dynamicImports (laporan Tugas 9 kekhawatiran 1).
     */
    public const MAPLIBRE = [
        'node_modules/maplibre-gl/dist/maplibre-gl.js',
        'node_modules/maplibre-gl/dist/maplibre-gl.css',
    ];

    public const HURUF = [
        '/fonts/plus-jakarta-sans/plus-jakarta-sans-latin-wght-normal.woff2',
        '/fonts/plus-jakarta-sans/plus-jakarta-sans-latin-ext-wght-normal.woff2',
    ];

    /**
     * Shell kecil yang disimpan setiap pemasangan service worker, juga saat paket dibuka.
     *
     * @param  array<string, array<string, mixed>>  $manifest  isi public/build/manifest.json
     * @return list<string>
     */
    public static function daftar(array $manifest): array
    {
        $berkas = ['/offline', '/icons/app-192.png', '/icons/app-512.png'];

        if (isset($manifest['resources/css/app.css']['file'])) {
            $berkas[] = '/build/'.$manifest['resources/css/app.css']['file'];
        }

        return $berkas;
    }

    /**
     * Shell paket: kosong selama paket mati atau bila manifest build belum memuat entri shell.
     * Selain itu impor statis transitif entri shell; impor dinamis yang diikuti hanya milik entri
     * shell dan potongan bukan-entri (misalnya runtime bersama `dist-*.js`, yang memuat MapLibre
     * dan perekam jejak secara malas); ditambah kunci MapLibre js dan css dan perekam jejak yang
     * disebut sendiri bila ada di manifest. Impor dinamis entri lain tidak pernah diikuti: bila
     * build menaruh runtime bersama di entri app, berkas entri itu ikut, tetapi seluruh halaman
     * yang dimuat malasnya tidak (pra-tinjau PT9-15, ruling W6-9g).
     *
     * @param  array<string, array<string, mixed>>  $manifest  isi public/build/manifest.json
     * @return list<string>
     */
    public static function daftarPaket(array $manifest, bool $paketAktif): array
    {
        if (! $paketAktif || ! isset($manifest[self::ENTRI_SHELL])) {
            return [];
        }

        $antre = [self::ENTRI_SHELL, ...self::kunciMapLibre($manifest), self::PEREKAM_JEJAK];
        $dikunjungi = [];
        $berkas = [];

        while ($antre !== []) {
            $kunci = array_shift($antre);

            if (isset($dikunjungi[$kunci]) || ! isset($manifest[$kunci]['file'])) {
                continue;
            }

            $dikunjungi[$kunci] = true;
            $entri = $manifest[$kunci];
            $berkas[] = '/build/'.$entri['file'];

            foreach ($entri['css'] ?? [] as $css) {
                $berkas[] = '/build/'.$css;
            }

            array_push($antre, ...($entri['imports'] ?? []));

            if ($kunci === self::ENTRI_SHELL || ! ($entri['isEntry'] ?? false)) {
                array_push($antre, ...($entri['dynamicImports'] ?? []));
            }
        }

        return array_values(array_unique($berkas));
    }

    /**
     * Path entri shell luring di build, untuk @vite dan penanda shell di halaman luring (ruling
     * W6-9b). Null selama paket lapangan mati atau bila manifest build belum memuat entrinya
     * (build lama atau gagal sebagian): halaman luring lalu jatuh ke app.css saja, tanpa
     * penanda, alih-alih 500 dari @vite (pra-tinjau PT9-16 bagian d).
     */
    public static function entriShell(): ?string
    {
        if (! config('hiking.field_pack.enabled')) {
            return null;
        }

        $berkas = self::manifest()[self::ENTRI_SHELL]['file'] ?? null;

        return is_string($berkas) ? '/build/'.$berkas : null;
    }

    /**
     * Isi manifest build Vite; larik kosong bila belum ada build atau isinya tidak terbaca.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function manifest(): array
    {
        $jalan = public_path('build/manifest.json');
        $isi = is_file($jalan) ? json_decode((string) file_get_contents($jalan), true) : null;

        return is_array($isi) ? $isi : [];
    }

    /**
     * Kunci manifest yang `src`-nya (atau kuncinya sendiri, bila tanpa `src`) berakhir dengan
     * salah satu akhiran MAPLIBRE, dengan garis miring terbalik dinormalkan.
     *
     * @param  array<string, array<string, mixed>>  $manifest
     * @return list<string>
     */
    private static function kunciMapLibre(array $manifest): array
    {
        $kunci = [];

        foreach ($manifest as $nama => $entri) {
            $sumber = str_replace('\\', '/', (string) ($entri['src'] ?? $nama));

            foreach (self::MAPLIBRE as $akhiran) {
                if (str_ends_with($sumber, $akhiran)) {
                    $kunci[] = (string) $nama;
                }
            }
        }

        return $kunci;
    }
}
