<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Ukuran target sentuh.
 *
 * WCAG 2.2 AA menuntut 24x24 (2.5.8), dan aplikasi ini memilih 44px lewat min-h-11 di
 * komponen Button. Angka 44 bukan kutipan WCAG melainkan standar produk ini sendiri, dan
 * alasannya ada di penggunanya: tombol ditekan jempol, di luar ruangan, kadang dengan
 * sarung tangan, oleh orang yang sedang berdiri di jalur.
 *
 * Standar itu hanya berlaku pada tombol yang memakai komponennya. Markup <button>
 * sebaris tidak melewatinya sama sekali, dan di situlah target yang terlalu kecil
 * bersembunyi: justru menu hamburger, yang merupakan kontrol paling sering disentuh di
 * telepon, dan tombol status daftar persiapan, yang komentarnya sendiri menyebutnya
 * "interaksi yang paling sering diulang".
 *
 * Tautan navigasi ikut diperiksa, bukan hanya <button>: di ponsel, tautan menu adalah
 * satu-satunya jalan ke permukaan aplikasi (§88). Tautan prosa di dalam kalimat
 * dikecualikan lewat 'underline', karena WCAG 2.5.8 sendiri mengecualikannya.
 *
 * Dua dunia disapu. Di Blade yang masih dilayani, tautan navigasi ditandai
 * wire:navigate atau $attributes->merge() pada komponen tautan. Di React, setiap
 * <Tautan> dan <Link> adalah tautan di dalam aplikasi.
 */
class TapTargetTest extends TestCase
{
    use SumberReact;

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
     * Sapuan yang sama di sumber React: setiap <button>, <Tautan>, dan <Link>.
     *
     * Kelas kerap tidak tertulis di tag itu sendiri, melainkan di konstanta seperti
     * `tombolMenu` atau `tautanTeks`, kadang di berkas lain. Nama yang disebut di
     * className dicari definisinya, lebih dulu di berkas yang sama, dan isinya ikut
     * diperiksa. Tautan.tsx dilewati: ia pembungkus <Link> yang kelasnya datang dari
     * pemanggil, dan pemanggilnyalah yang diperiksa.
     */
    public function test_every_react_button_and_in_app_link_can_be_hit_by_a_thumb(): void
    {
        $pelanggar = [];
        $diperiksa = 0;

        foreach ($this->berkasTsx() as $berkas) {
            $nama = str_replace('\\', '/', $berkas->getRelativePathname());

            if ($nama === 'components/ui/Tautan.tsx') {
                continue;
            }

            $isi = $this->tanpaKomentarJs($berkas->getContents());

            foreach (['button', 'Tautan', 'Link'] as $elemen) {
                foreach ($this->tagBernama($isi, $elemen) as [$tag, $baris]) {
                    $diperiksa++;
                    $jendela = $tag.' '.$this->definisiKelas($tag, $isi);

                    // Tautan teks di dalam kalimat dikecualikan WCAG 2.5.8 sendiri.
                    if (str_contains($jendela, 'underline')) {
                        continue;
                    }

                    if (! preg_match('/(?<![\w:-])(?:min-h-11|min-h-12|min-h-\[44px\]|h-1[1-6]|py-[34]|p-[34])(?![\w-])/', $jendela)) {
                        $pelanggar[] = $nama.':'.$baris;
                    }
                }
            }
        }

        $this->assertGreaterThan(30, $diperiksa, 'Sapuan tidak menemukan kontrol untuk diperiksa.');
        $this->assertSame([], $pelanggar, 'Target sentuh di bawah 44px pada: '.implode(', ', $pelanggar));
    }

    /**
     * Isi definisi setiap nama yang disebut di className sebuah tag.
     */
    private function definisiKelas(string $tag, string $isiBerkas): string
    {
        $mulai = strpos($tag, 'className={');

        if ($mulai === false) {
            return '';
        }

        // Hanya nama, bukan kata di dalam untaian kelas: untaian dibuang, dan dari
        // template literal yang tersisa hanya bagian ${...}.
        $ekspresi = (string) preg_replace('/\'[^\']*\'|"[^"]*"/', ' ', substr($tag, $mulai + 11));
        $ekspresi = (string) preg_replace_callback(
            '/`([^`]*)`/',
            fn (array $m) => preg_match_all('/\$\{([^}]*)\}/', $m[1], $bagian) ? implode(' ', $bagian[1]) : ' ',
            $ekspresi
        );

        preg_match_all('/\b[A-Za-z_]\w*\b/', $ekspresi, $nama);

        $definisi = '';

        foreach (array_unique($nama[0]) as $satu) {
            $pola = '/\b(?:const|let)\s+'.preg_quote($satu, '/').'\b\s*(?::[^=]+)?=\s*([^;]+);/';

            if (preg_match($pola, $isiBerkas, $cocok)) {
                $definisi .= ' '.$cocok[1];

                continue;
            }

            foreach ($this->sumberSemua() as $isiLain) {
                if (preg_match($pola, $isiLain, $cocok)) {
                    $definisi .= ' '.$cocok[1];

                    break;
                }
            }
        }

        return $definisi;
    }

    /**
     * Seluruh sumber .ts dan .tsx, untuk konstanta kelas yang diimpor dari berkas lain.
     *
     * @return array<int, string>
     */
    private function sumberSemua(): array
    {
        static $isi = null;

        return $isi ??= array_map(
            fn ($berkas) => $this->tanpaKomentarJs($berkas->getContents()),
            array_values(array_filter(
                File::allFiles(resource_path('js')),
                fn ($berkas) => in_array($berkas->getExtension(), ['ts', 'tsx'], true)
            ))
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

        foreach ($this->berkasTsx() as $berkas) {
            if (preg_match_all('/(?<![a-z:-])grid-cols-([3-9]|1[0-2])\b/', $this->tanpaKomentarJs($berkas->getContents()), $m)) {
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
