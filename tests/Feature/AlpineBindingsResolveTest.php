<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Pengikatan Alpine menunjuk ke sesuatu yang benar-benar ada.
 *
 * Kelas cacat yang paling sulit terlihat di proyek ini: PHPUnit merender Blade menjadi
 * HTML dan berhenti di sana. Skrip tidak pernah dijalankan, jadi halaman yang melempar
 * galat pada baris pertamanya tetap dilaporkan hijau oleh seluruh suite.
 *
 * Itu benar-benar terjadi. Ketika kode peta dicabut dari mode pendakian ke komponen,
 * metode init() ikut terbawa keluar sementara x-init="init()" tetap tertinggal di
 * markup. Mode pendakian, yaitu satu-satunya layar yang dipakai di lapangan tanpa
 * sinyal, akan melempar galat pada setiap pemuatan. Yang menemukannya adalah pembacaan
 * berkas, bukan satu pun dari enam ratus test.
 *
 * Sapuan ini tidak menggantikan menjalankan peramban. Ia hanya menutup bentuk kegagalan
 * yang paling sering muncul di sini, yaitu pengikatan yang menunjuk ke metode yang sudah
 * tidak ada.
 */
class AlpineBindingsResolveTest extends TestCase
{
    public function test_every_x_init_calls_a_method_its_component_defines(): void
    {
        $putus = [];

        foreach (File::allFiles(resource_path('views')) as $berkas) {
            $isi = $berkas->getContents();

            if (! preg_match('/x-data="([a-zA-Z_][a-zA-Z0-9_]*)\(\)"/', $isi, $komponen)) {
                continue;
            }

            preg_match_all('/x-init="([a-zA-Z_][a-zA-Z0-9_]*)\(\)"/', $isi, $panggilan);

            foreach ($panggilan[1] as $metode) {
                if (! preg_match('/\b'.preg_quote($metode, '/').'\s*\(\s*\)\s*\{/', $isi)) {
                    $putus[] = $berkas->getRelativePathname().': x-init="'.$metode.'()" pada '.$komponen[1].'()';
                }
            }
        }

        $this->assertSame(
            [],
            $putus,
            "Pengikatan Alpine menunjuk ke metode yang tidak ada:\n- ".implode("\n- ", $putus)
        );
    }

    /**
     * x-data menunjuk ke fungsi yang didefinisikan di berkas yang sama. Fungsi yang
     * hilang membuat seluruh pohon Alpine di bawahnya mati diam-diam.
     */
    public function test_every_x_data_factory_exists_in_its_own_file(): void
    {
        $hilang = [];

        foreach (File::allFiles(resource_path('views')) as $berkas) {
            $isi = $berkas->getContents();

            preg_match_all('/x-data="([a-zA-Z_][a-zA-Z0-9_]*)\(\)"/', $isi, $pabrik);

            foreach ($pabrik[1] as $nama) {
                if (! preg_match('/function\s+'.preg_quote($nama, '/').'\s*\(/', $isi)) {
                    $hilang[] = $berkas->getRelativePathname().': '.$nama.'()';
                }
            }
        }

        $this->assertSame(
            [],
            $hilang,
            "x-data menunjuk ke fungsi yang tidak didefinisikan:\n- ".implode("\n- ", $hilang)
        );
    }
}
