<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Ukuran target sentuh.
 *
 * WCAG 2.2 AA menuntut 24x24 (2.5.8), dan aplikasi ini memilih 44px lewat min-h-11 di
 * x-ui.button. Angka 44 bukan kutipan WCAG melainkan standar produk ini sendiri, dan
 * alasannya ada di penggunanya: tombol ditekan jempol, di luar ruangan, kadang dengan
 * sarung tangan, oleh orang yang sedang berdiri di jalur.
 *
 * Standar itu hanya berlaku pada tombol yang memakai komponennya. Markup <button>
 * sebaris tidak melewatinya sama sekali, dan di situlah target yang terlalu kecil
 * bersembunyi: justru menu hamburger, yang merupakan kontrol paling sering disentuh di
 * telepon, dan tombol status daftar persiapan, yang komentarnya sendiri menyebutnya
 * "interaksi yang paling sering diulang".
 */
class TapTargetTest extends TestCase
{
    /**
     * Tinggi minimum dinyatakan, bukan diwariskan dari isinya.
     *
     * Tombol yang tingginya datang dari padding anaknya berubah ukuran ketika anaknya
     * berubah, dan tidak ada yang memberi tahu.
     */
    private const PENANDA = ['min-h-11', 'min-h-[44px]', 'py-3', 'py-4'];

    public function test_every_standalone_button_can_be_hit_by_a_thumb(): void
    {
        $pelanggar = [];

        foreach (File::allFiles(resource_path('views')) as $berkas) {
            $isi = preg_replace('/\{\{--.*?--\}\}/s', ' ', $berkas->getContents());
            $baris = explode("\n", $isi);

            foreach ($baris as $nomor => $satu) {
                if (! str_contains($satu, '<button')) {
                    continue;
                }

                // Atribut kelas kerap dipecah beberapa baris, dan @class([...]) menaruh
                // kelasnya sampai selusin baris di bawah tag pembukanya.
                $jendela = implode(' ', array_slice($baris, $nomor, 16));
                $jendela = substr($jendela, 0, strpos($jendela, '</button>') ?: strlen($jendela));

                // Kelas yang dihitung di blok @php di atas tagnya tidak terlihat dari
                // jendela ini. Yang diperiksa lalu seluruh berkasnya, karena variabelnya
                // memang tinggal di berkas yang sama.
                if (preg_match('/\{\{\s*\$(?:attributes|classes)/', $jendela)) {
                    $jendela .= ' '.$isi;
                }

                // Tautan teks di dalam kalimat dikecualikan WCAG 2.5.8 sendiri:
                // memperbesarnya justru merusak baris kalimat yang memuatnya.
                if (str_contains($jendela, 'underline')) {
                    continue;
                }

                foreach (self::PENANDA as $penanda) {
                    if (str_contains($jendela, $penanda)) {
                        continue 2;
                    }
                }

                $pelanggar[] = $berkas->getRelativePathname().':'.($nomor + 1);
            }
        }

        $this->assertSame(
            [],
            $pelanggar,
            'Target sentuh di bawah 44px pada: '.implode(', ', $pelanggar)
        );
    }

    /**
     * Baris metrik tidak memaksa tiga kolom di layar sempit.
     *
     * Tiga kolom di dalam kartu ber-padding pada lebar 400px menyisakan sekitar seratus
     * piksel per kolom, dan label seperti "Tanjakan" beserta angkanya tidak muat di sana
     * tanpa terpotong atau membungkus jadi dua baris yang tidak sejajar.
     */
    public function test_no_grid_forces_three_columns_on_a_narrow_screen(): void
    {
        $pelanggar = [];

        foreach (File::allFiles(resource_path('views')) as $berkas) {
            $isi = preg_replace('/\{\{--.*?--\}\}/s', ' ', $berkas->getContents());

            if (preg_match_all('/(?<![a-z:-])grid-cols-([3-9]|1[0-2])\b/', $isi, $m)) {
                $pelanggar[] = $berkas->getRelativePathname().' ('.implode(', ', $m[0]).')';
            }
        }

        $this->assertSame(
            [],
            $pelanggar,
            'Grid tanpa titik henti responsif di: '.implode(', ', $pelanggar)
        );
    }
}
