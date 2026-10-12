<?php

namespace Tests\Unit;

use App\Support\ImageSanitizer;
use RuntimeException;
use Tests\Concerns\GambarBerExif;
use Tests\TestCase;

/**
 * PRD §81 dan §82.
 *
 * Foto ponsel membawa EXIF berisi koordinat GPS tempat foto diambil. Menerbitkannya
 * pada laporan komunitas berarti mempublikasikan lokasi presisi pendaki tanpa ia
 * memilihnya, yang persis dilarang §82.
 */
class ImageSanitizerTest extends TestCase
{
    use GambarBerExif;

    /**
     * @var array<int, string>
     */
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    public function test_metadata_is_removed_from_an_uploaded_photo(): void
    {
        $path = $this->jpegWithExif();

        $this->assertNotFalse(@exif_read_data($path), 'Berkas uji harus membawa EXIF sebelum dibersihkan.');

        ImageSanitizer::sanitize($path, 2000);

        $exif = @exif_read_data($path);

        $this->assertTrue(
            $exif === false || ! isset($exif['GPSLatitude'], $exif['Make']),
            'Metadata pabrikan dan koordinat tidak boleh tersisa.'
        );
    }

    public function test_the_image_is_still_a_valid_image_afterwards(): void
    {
        $path = $this->jpegWithExif();

        ImageSanitizer::sanitize($path, 2000);

        $info = getimagesize($path);

        $this->assertNotFalse($info);
        $this->assertSame(IMAGETYPE_JPEG, $info[2]);
    }

    public function test_an_oversized_image_is_scaled_down(): void
    {
        $path = $this->jpeg(3000, 1500);

        ImageSanitizer::sanitize($path, 2000);

        [$width, $height] = getimagesize($path);

        $this->assertSame(2000, $width);
        $this->assertSame(1000, $height, 'Rasio gambar harus dipertahankan.');
    }

    public function test_an_image_within_the_limit_keeps_its_size(): void
    {
        $path = $this->jpeg(800, 600);

        ImageSanitizer::sanitize($path, 2000);

        [$width, $height] = getimagesize($path);

        $this->assertSame(800, $width);
        $this->assertSame(600, $height);
    }

    public function test_a_file_that_is_not_an_image_is_rejected(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'bukan').'.jpg';
        file_put_contents($path, 'ini hanya teks biasa');
        $this->temporaryFiles[] = $path;

        $this->expectException(RuntimeException::class);

        ImageSanitizer::sanitize($path, 2000);
    }

    public function test_the_forgiving_variant_reports_failure_instead_of_throwing(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'bukan').'.jpg';
        file_put_contents($path, 'ini hanya teks biasa');
        $this->temporaryFiles[] = $path;

        $this->assertFalse(ImageSanitizer::trySanitize($path, 2000));
    }

    private function jpeg(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 30, 120, 90));

        $path = tempnam(sys_get_temp_dir(), 'uji').'.jpg';
        imagejpeg($image, $path);

        $this->temporaryFiles[] = $path;

        return $path;
    }

    /**
     * JPEG ber-EXIF dari GambarBerExif, ditulis ke berkas karena pembersih bekerja pada path.
     */
    private function jpegWithExif(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'uji').'.jpg';
        file_put_contents($path, $this->jpegBerExif());

        $this->temporaryFiles[] = $path;

        return $path;
    }
}
