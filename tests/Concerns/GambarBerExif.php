<?php

namespace Tests\Concerns;

/**
 * JPEG uji yang membawa segmen EXIF (PRD §82).
 *
 * Foto ponsel membawa EXIF berisi koordinat GPS tempat foto diambil. Test pembersih membutuhkan
 * gambar yang benar-benar memuat segmen itu sebelum dibersihkan. Segmennya minimal: satu tag
 * Make bernilai MERK_EXIF_UJI, cukup untuk dibaca exif_read_data dan dicari sebagai bita.
 */
trait GambarBerExif
{
    /**
     * Nilai tag Make pada segmen EXIF uji. Menemukannya di bita berkas berarti EXIF masih ada.
     */
    protected const MERK_EXIF_UJI = 'Pendaki';

    protected function jpegBerExif(int $lebar = 400, int $tinggi = 300): string
    {
        $gambar = imagecreatetruecolor($lebar, $tinggi);
        imagefill($gambar, 0, 0, imagecolorallocate($gambar, 30, 120, 90));

        ob_start();
        imagejpeg($gambar);
        $jpeg = (string) ob_get_clean();

        // Segmen APP1 disisipkan tepat setelah penanda SOI (dua bita pertama).
        return substr($jpeg, 0, 2).$this->segmenExif().substr($jpeg, 2);
    }

    private function segmenExif(): string
    {
        $merk = self::MERK_EXIF_UJI."\0";

        // IFD0 dengan satu entri: Make (0x010F), ASCII, panjang string, offset data.
        $ifd = pack('v', 1)
            .pack('vvVV', 0x010F, 2, strlen($merk), 8 + 2 + 12 + 4)
            .pack('V', 0)
            .$merk;

        $tiff = "II\x2a\x00".pack('V', 8).$ifd;
        $muatan = "Exif\0\0".$tiff;

        return "\xFF\xE1".pack('n', strlen($muatan) + 2).$muatan;
    }
}
