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
 *
 * Sapuan ini juga hanya memeriksa <button>, sehingga seluruh <a> tidak terlihat --
 * padahal responsive-nav-link dan dropdown-link, satu-satunya jalan ke empat dari lima
 * permukaan menu di ponsel (§88, Tugas 7), adalah tautan, bukan tombol. Diperluas ke
 * <a> yang memakai wire:navigate: atribut itu menandai tautan yang sungguh berpindah
 * halaman di dalam aplikasi (kontrol navigasi), berbeda dari tautan konten biasa yang
 * tidak diklaim standar 44px ini. Tautan prosa tetap dikecualikan lewat 'underline',
 * persis seperti sebelumnya.
 *
 * wire:navigate pada komponen tautan yang dapat dipakai ulang (nav-link,
 * responsive-nav-link, dropdown-link) tidak pernah tertulis di berkas komponennya
 * sendiri -- ia diteruskan pemanggil lewat $attributes->merge(), jadi tidak terlihat
 * dari baris manapun di berkas itu. Berkas yang tag <a>-nya sendiri memakai
 * $attributes->merge() diperlakukan sebagai kontrol navigasi tanpa syarat wire:navigate:
 * di seluruh resources/views/components, pola itu hanya dipakai oleh komponen tautan
 * navigasi, tidak pernah oleh tautan konten sebaris.
 */
class TapTargetTest extends TestCase
{
    /**
     * Tinggi minimum dinyatakan, bukan diwariskan dari isinya.
     *
     * Tombol yang tingginya datang dari padding anaknya berubah ukuran ketika anaknya
     * berubah, dan tidak ada yang memberi tahu.
     *
     * p-3/p-4 (padding empat sisi) ikut dihitung di samping py-3/py-4: pada elemen satu
     * baris teks (~20px tinggi baris), p-4 menghasilkan sekitar 16+16+20=52px dan p-3
     * sekitar 12+12+20=44px, keduanya menyentuh atau melewati standarnya lewat aritmetika
     * yang sama dengan py-*, bukan angka baru.
     */
    private const PENANDA = ['min-h-11', 'min-h-[44px]', 'py-3', 'py-4', 'p-3', 'p-4'];

    public function test_every_standalone_button_can_be_hit_by_a_thumb(): void
    {
        $pelanggar = [];

        foreach (File::allFiles(resource_path('views')) as $berkas) {
            $isi = preg_replace('/\{\{--.*?--\}\}/s', ' ', $berkas->getContents());
            $baris = explode("\n", $isi);

            foreach ($baris as $nomor => $satu) {
                $adalahTombol = str_contains($satu, '<button');
                $adalahTautanNavigasi = preg_match('/<a[\s>]/', $satu) === 1;

                if (! $adalahTombol && ! $adalahTautanNavigasi) {
                    continue;
                }

                $penutup = $adalahTombol ? '</button>' : '</a>';

                // Atribut kelas kerap dipecah beberapa baris, dan @class([...]) menaruh
                // kelasnya sampai selusin baris di bawah tag pembukanya.
                $jendela = implode(' ', array_slice($baris, $nomor, 16));
                $jendela = substr($jendela, 0, strpos($jendela, $penutup) ?: strlen($jendela));

                // Komponen tautan yang dapat dipakai ulang meneruskan wire:navigate
                // lewat $attributes->merge() milik pemanggil -- tidak pernah tertulis di
                // berkas komponennya sendiri. Pola $attributes->merge() pada tag <a> di
                // resources/views/components hanya dipakai komponen tautan navigasi,
                // jadi diperlakukan sebagai kontrol navigasi tanpa syarat wire:navigate.
                $adalahKomponenTautan = str_contains($jendela, '$attributes->merge');

                // Tautan yang bukan kontrol navigasi (tidak berpindah halaman di dalam
                // aplikasi lewat wire:navigate) di luar cakupan standar ini -- termasuk
                // tautan eksternal, mailto, dan pemicu non-navigasi lainnya.
                if ($adalahTautanNavigasi && ! $adalahKomponenTautan && ! str_contains($jendela, 'wire:navigate')) {
                    continue;
                }

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
