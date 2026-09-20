<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Setiap kontrol yang mematikan outline fokus harus menggantinya.
 *
 * `focus:outline-none` menghapus satu-satunya penanda posisi yang dimiliki pengguna
 * papan ketik. Menghapusnya sah selama ada penggantinya; menghapusnya tanpa pengganti
 * membuat seseorang menekan Tab berkali-kali tanpa tahu di mana ia berada (WCAG 2.4.7).
 *
 * Kelas cacat ini lolos ke dalam kode lewat scaffolding Breeze dan bertahan di komponen
 * navigasi, yaitu komponen yang muncul di setiap halaman. Disapu, bukan didaftar per
 * berkas, karena daftar per berkas persis yang membuat dua pelanggaran kontras lolos
 * sebelumnya.
 */
class FocusIndicatorTest extends TestCase
{
    public function test_nothing_removes_the_focus_outline_without_replacing_it(): void
    {
        $pelanggar = [];

        foreach (File::allFiles(resource_path('views')) as $berkas) {
            $isi = $berkas->getContents();
            $posisi = 0;

            while (($posisi = strpos($isi, 'focus:outline-none', $posisi)) !== false) {
                // Penggantinya boleh berada sebelum maupun sesudah dalam daftar kelas
                // yang sama, dan daftar itu kerap dipecah beberapa baris.
                $konteks = substr($isi, max(0, $posisi - 400), 800);

                if (! preg_match('/focus-visible:ring|focus:ring|focus-visible:outline|outline-offset/', $konteks)) {
                    $pelanggar[] = $berkas->getRelativePathname();
                }

                $posisi += 18;
            }
        }

        $this->assertSame(
            [],
            array_values(array_unique($pelanggar)),
            'Outline fokus dimatikan tanpa pengganti di: '.implode(', ', array_unique($pelanggar))
        );
    }

    /**
     * Penanda fokus adalah komponen non-teks dan menuntut 3:1 (WCAG 1.4.11). Batas
     * gray-300 hanya 1.47:1, jadi memakainya sebagai penanda fokus berarti mengganti
     * outline dengan sesuatu yang praktis tidak terlihat.
     */
    public function test_the_replacement_is_not_itself_invisible(): void
    {
        $pelanggar = [];

        foreach (File::allFiles(resource_path('views')) as $berkas) {
            if (preg_match('/focus:border-gray-300|focus-visible:border-gray-300|focus:ring-gray-300/', $berkas->getContents())) {
                $pelanggar[] = $berkas->getRelativePathname();
            }
        }

        $this->assertSame(
            [],
            $pelanggar,
            'Penanda fokus 1.47:1 di: '.implode(', ', $pelanggar)
        );
    }
}
