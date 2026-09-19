<?php

namespace App\Support;

use RuntimeException;
use Throwable;

/**
 * Menulis ulang gambar unggahan tanpa metadata (PRD §81, §82).
 *
 * Foto yang diambil dengan ponsel membawa EXIF, termasuk koordinat GPS presisi tempat
 * foto itu diambil. Menerbitkannya pada laporan komunitas berarti mempublikasikan
 * lokasi pendaki tanpa ia memilihnya — persis yang dilarang §82.
 *
 * Re-encode lewat GD tidak membawa segmen EXIF apa pun, sehingga pembersihan terjadi
 * sebagai akibat dari penulisan ulang, bukan dengan menghapus tag satu per satu.
 * Sekaligus ukurannya dibatasi agar berkas raksasa tidak membebani penyimpanan.
 */
class ImageSanitizer
{
    /**
     * @throws RuntimeException bila berkas tidak dapat dibaca sebagai gambar
     */
    public static function sanitize(string $absolutePath, int $maxDimension): void
    {
        if (! is_readable($absolutePath)) {
            throw new RuntimeException("Berkas gambar tidak dapat dibaca: {$absolutePath}");
        }

        $info = @getimagesize($absolutePath);

        if ($info === false) {
            throw new RuntimeException('Berkas bukan gambar yang dikenali.');
        }

        $image = @imagecreatefromstring(file_get_contents($absolutePath));

        if ($image === false) {
            throw new RuntimeException('Gambar tidak dapat didekode.');
        }

        try {
            $image = self::constrain($image, $maxDimension);

            self::write($image, $absolutePath, $info[2]);
        } finally {
            if ($image instanceof \GdImage) {
                imagedestroy($image);
            }
        }
    }

    /**
     * Versi yang tidak melempar; dipakai pada alur unggah agar foto bermasalah tidak
     * menggagalkan laporan yang isinya tetap berguna.
     */
    public static function trySanitize(string $absolutePath, int $maxDimension): bool
    {
        try {
            self::sanitize($absolutePath, $maxDimension);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private static function constrain(\GdImage $image, int $maxDimension): \GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $longest = max($width, $height);

        if ($longest <= $maxDimension) {
            return $image;
        }

        $scaled = imagescale($image, (int) round($width * $maxDimension / $longest));

        if ($scaled === false) {
            return $image;
        }

        imagedestroy($image);

        return $scaled;
    }

    private static function write(\GdImage $image, string $path, int $type): void
    {
        $written = match ($type) {
            IMAGETYPE_PNG => imagepng($image, $path),
            IMAGETYPE_WEBP => imagewebp($image, $path),
            default => imagejpeg($image, $path, 85),
        };

        if ($written === false) {
            throw new RuntimeException('Gambar gagal ditulis ulang.');
        }
    }
}
