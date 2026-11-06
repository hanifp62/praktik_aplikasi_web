<?php

namespace Tests\Pratinjau;

/**
 * Penentu berkas yang boleh disajikan server statis pratinjau (W1-K8, W1-10a).
 *
 * `php -S -t public` tanpa router meneruskan setiap alamat yang bukan berkas ke
 * public/index.php, yang mem-boot Laravel dengan .env, dan .env repo ini menunjuk Supabase
 * produksi. Versi awal kelas ini menafsirkan URI sendiri lalu menyuruh router
 * "biarkan php -S yang menyajikan" untuk alamat yang lolos; peninjauan W1-10a membuktikan
 * live bahwa php -S menafsirkan ulang URI mentah dengan caranya sendiri, berbeda dari
 * tafsiran kelas ini (host semu "//x/...", jalur yang jatuh ke index.php, ".." yang
 * ditangani php -S sendiri), sehingga bisa berakhir menjalankan public/index.php walau
 * kelas ini menilai alamatnya sah. Sejak perbaikan itu, berkasUntuk() mengembalikan jalur
 * berkas yang sudah divalidasi penuh, dan pemanggilnya (statis.php) membaca serta mengirim
 * isi berkas itu sendiri: php -S tidak pernah diberi kesempatan menafsirkan URI mentah,
 * baik untuk alamat yang lolos maupun yang ditolak.
 */
final class PenyajiStatis
{
    /**
     * Ekstensi yang boleh disajikan, daftar tertutup: bukan sekadar "asal bukan php", supaya
     * jenis berkas yang tidak pernah dimaksudkan untuk pratinjau tidak ikut lolos begitu
     * saja hanya karena kebetulan bukan .php.
     *
     * @var list<string>
     */
    private const EKSTENSI_DIIZINKAN = [
        'html', 'js', 'css', 'woff2', 'svg', 'png', 'webp', 'jpg', 'ico', 'json', 'webmanifest', 'txt', 'map',
    ];

    public static function bolehDisajikan(string $uri, string $akarPublik): bool
    {
        return self::berkasUntuk($uri, $akarPublik) !== null;
    }

    /**
     * Mengembalikan realpath berkas yang boleh disajikan, atau null bila ditolak.
     *
     * Jalur URI ditafsirkan sepenuhnya di sini dari bagian sebelum "?", bukan lewat
     * parse_url (yang membaca host semu pada URI berawalan "//"). Alamat yang memuat byte
     * kosong, backslash, garis miring ganda, atau segmen "." dan ".." ditolak sebelum
     * realpath dipanggil sama sekali, supaya byte kosong tidak membuat fungsi filesystem
     * PHP melempar ValueError (Minor 2). Pemanggil wajib membaca dan mengirim sendiri isi
     * berkas yang dikembalikan; tidak boleh ada jalan yang menyerahkan keputusan penyajian
     * kembali ke php -S.
     */
    public static function berkasUntuk(string $uri, string $akarPublik): ?string
    {
        $jalur = rawurldecode(explode('?', $uri, 2)[0]);

        if (str_contains($jalur, "\0") || str_contains($jalur, '\\') || str_contains($jalur, '//')) {
            return null;
        }

        $segmen = explode('/', $jalur);

        if (in_array('.', $segmen, true) || in_array('..', $segmen, true)) {
            return null;
        }

        $akar = realpath($akarPublik);
        $berkas = realpath($akarPublik.DIRECTORY_SEPARATOR.ltrim($jalur, '/'));

        if ($akar === false || $berkas === false || ! is_file($berkas)) {
            return null;
        }

        if (! str_starts_with($berkas, $akar.DIRECTORY_SEPARATOR)) {
            return null;
        }

        if (! in_array(strtolower(pathinfo($berkas, PATHINFO_EXTENSION)), self::EKSTENSI_DIIZINKAN, true)) {
            return null;
        }

        return $berkas;
    }
}
