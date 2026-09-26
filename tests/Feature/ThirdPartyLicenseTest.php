<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Karya pihak ketiga yang ikut dalam aplikasi membawa lisensinya.
 *
 * Path ikon Phosphor ditanam langsung di kode, bukan dipasang sebagai paket, sehingga
 * pemberitahuan hak cipta MIT tidak datang dengan sendirinya dari node_modules. Daftar
 * lisensi pihak ketiga juga harus mengikuti setiap dependensi runtime yang ditambahkan,
 * karena repositori ini publik.
 */
class ThirdPartyLicenseTest extends TestCase
{
    private const LISENSI_PHOSPHOR = 'resources/js/components/ui/LICENSE-phosphor.txt';

    /**
     * Kotak gambar 256 unit milik ikon Phosphor, persis seperti tertulis di Icon.tsx dan
     * icon.blade.php. Path yang disalin tanpa menyebut nama Phosphor tetap tertangkap.
     */
    private const SIDIK_VIEWBOX_PHOSPHOR = 'viewBox="0 0 256 256"';

    public function test_the_phosphor_licence_sits_next_to_the_icon_registry(): void
    {
        $lisensi = File::get(base_path(self::LISENSI_PHOSPHOR));

        $this->assertStringStartsWith('MIT License', trim($lisensi));
        $this->assertStringContainsString('Copyright (c) 2023 Phosphor Icons', $lisensi);
        $this->assertStringContainsString('The above copyright notice and this permission notice shall be included', $lisensi);
    }

    public function test_every_file_that_embeds_phosphor_paths_points_to_the_licence(): void
    {
        $tanpaRujukan = [];
        $terdeteksi = [];

        foreach ([resource_path('js'), resource_path('views')] as $akar) {
            foreach (File::allFiles($akar) as $berkas) {
                if (! in_array($berkas->getExtension(), ['tsx', 'ts', 'js', 'jsx', 'php'], true)) {
                    continue;
                }

                $isi = $berkas->getContents();

                if (stripos($isi, 'phosphor') === false && ! str_contains($isi, self::SIDIK_VIEWBOX_PHOSPHOR)) {
                    continue;
                }

                $relatif = str_replace('\\', '/', $berkas->getRelativePathname());
                $terdeteksi[] = $relatif;

                if (! str_contains($isi, self::LISENSI_PHOSPHOR)) {
                    $tanpaRujukan[] = $relatif;
                }
            }
        }

        foreach (['components/ui/Icon.tsx', 'components/ui/icon.blade.php'] as $wajib) {
            $this->assertContains($wajib, $terdeteksi, "Pemindai tidak mengenali {$wajib} sebagai berkas yang memuat ikon Phosphor.");
        }

        $this->assertSame([], $tanpaRujukan, 'Berkas memuat ikon Phosphor tanpa menunjuk teks lisensinya: '.implode(', ', $tanpaRujukan));
    }

    public function test_the_register_lists_every_runtime_dependency_and_embedded_work(): void
    {
        $daftar = File::get(base_path('docs/lisensi-pihak-ketiga.md'));
        $composer = json_decode(File::get(base_path('composer.json')), true, flags: JSON_THROW_ON_ERROR);
        $npm = json_decode(File::get(base_path('package.json')), true, flags: JSON_THROW_ON_ERROR);

        $paket = array_merge(
            array_filter(array_keys($composer['require']), fn (string $nama): bool => $nama !== 'php' && ! str_starts_with($nama, 'ext-')),
            array_keys($npm['dependencies'])
        );

        $hilang = array_values(array_filter($paket, fn (string $nama): bool => ! str_contains($daftar, "`{$nama}`")));

        $this->assertSame([], $hilang, 'Dependensi runtime tanpa catatan lisensi: '.implode(', ', $hilang));

        foreach (['Phosphor Icons', 'Plus Jakarta Sans', 'OpenStreetMap', 'BMKG', self::LISENSI_PHOSPHOR] as $karya) {
            $this->assertStringContainsString($karya, $daftar);
        }
    }
}
