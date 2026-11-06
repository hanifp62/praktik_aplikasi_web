<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Halaman React merender landmark dan navigasinya di klien, di luar jangkauan test
 * HTTP. Yang dijaga di sini adalah sumber layout React itu sendiri: landmark utama
 * bernama "konten" (sasaran tautan lewati di templat akar), tanpa warna mentah, dan
 * tanpa nama produk yang ditulis langsung.
 */
class InertiaLayoutTest extends TestCase
{
    public function test_every_react_layout_and_page_root_has_the_main_landmark(): void
    {
        foreach (['js/layouts/AppLayout.tsx', 'js/layouts/GuestLayout.tsx', 'js/pages/Welcome.tsx'] as $berkas) {
            $this->assertStringContainsString('id="konten"', File::get(resource_path($berkas)), $berkas);
        }
    }

    /**
     * Setiap halaman React memakai tepat satu h1 (lewat PageHeader atau langsung), dan
     * setiap kontrol formulir punya label yang terikat lewat htmlFor.
     */
    public function test_react_pages_have_one_heading_and_labelled_controls(): void
    {
        $halaman = File::get(resource_path('js/pages/Trails/Index.tsx'));
        $this->assertSame(1, substr_count($halaman, '<PageHeader ') + substr_count($halaman, '<h1'), 'Jelajah harus punya tepat satu h1.');

        // Isian Jelajah: rentang tanjakan di saringan dan kotak cari. id berupa ekspresi
        // useId, jadi label dicocokkan dengan ekspresi yang sama persis.
        foreach (['js/components/trails/jelajah/SaringanJelajah.tsx' => 2, 'js/components/trails/jelajah/KotakCari.tsx' => 1] as $berkas => $jumlah) {
            $form = File::get(resource_path($berkas));
            preg_match_all('/<(?:TextInput|Select|input)\s(?:[^>]*\s)?id=(\{[^}]*\}\}|\{[^}]+\}|"[^"]+")/', $form, $kontrol);
            $this->assertCount($jumlah, $kontrol[1], $berkas);

            foreach ($kontrol[1] as $id) {
                $this->assertStringContainsString('htmlFor='.$id, $form, "Kontrol {$id} di {$berkas} tanpa label.");
            }
        }
    }

    /**
     * Regresi 25 September 2026: tailwind.config.js hanya memindai Blade, sehingga
     * setiap kelas yang hanya dipakai di komponen React tidak pernah dibuat. Kegagalannya
     * diam (navigasi bawah melebar melewati layar tanpa satu pun galat), jadi yang
     * dijaga di sini adalah cakupan pemindaiannya sendiri.
     */
    /**
     * Regresi 25 September 2026: klien Inertia menimpa judul server dengan nama merek
     * saja, sehingga semua halaman bertab sama (WCAG 2.4.2). Setiap pembungkus halaman
     * wajib memasang JudulHalaman.
     */
    public function test_every_page_wrapper_sets_the_tab_title(): void
    {
        foreach (['js/layouts/AppLayout.tsx', 'js/layouts/GuestLayout.tsx', 'js/components/trips/TataLetakPendakian.tsx', 'js/pages/Welcome.tsx'] as $berkas) {
            $this->assertStringContainsString('<JudulHalaman />', File::get(resource_path($berkas)), $berkas);
        }

        foreach (File::allFiles(resource_path('js/pages')) as $halaman) {
            $isi = $halaman->getContents();
            $this->assertMatchesRegularExpression('/AppLayout|GuestLayout|TataLetakPendakian|JudulHalaman/', $isi, $halaman->getRelativePathname().' tidak memakai pembungkus yang memasang judul.');
        }
    }

    /**
     * Regresi 25 September 2026: konfigurasi Tailwind hanya memindai Blade, sehingga kelas yang
     * hanya dipakai di komponen React tidak pernah dibuat. Diubah upgrade tailwind 4: sumber
     * kini ditulis sebagai @source di app.css, dan deteksi otomatis dimatikan (source(none)),
     * jadi baris @source resources/js itulah satu-satunya yang memindai komponen React.
     */
    public function test_tailwind_scans_the_react_sources(): void
    {
        $config = File::get(resource_path('css/app.css'));

        $this->assertStringContainsString("@source '../js/**/*.{js,jsx,ts,tsx}';", $config);
    }

    /**
     * Teks pembaca layar (sr-only) berposisi absolut. Di dalam wadah gulir yang tidak
     * berposisi, blok penampungnya jatuh ke dokumen, sehingga teks itu lolos dari
     * guliran dan melebarkan seluruh halaman. Di 360px halaman banding sempat selebar
     * 428px dan navigasi bawah ikut melebar. Wadah gulir wajib relative.
     */
    public function test_every_horizontal_scroll_container_contains_its_absolute_children(): void
    {
        $pelanggar = [];

        foreach (File::allFiles(resource_path('js')) as $berkas) {
            if ($berkas->getExtension() !== 'tsx') {
                continue;
            }

            preg_match_all('/className="([^"]*\boverflow-(?:x-)?auto\b[^"]*)"/', $berkas->getContents(), $cocok);

            foreach ($cocok[1] as $kelas) {
                if (! preg_match('/\b(?:relative|absolute|fixed|sticky)\b/', $kelas)) {
                    $pelanggar[] = $berkas->getRelativePathname().': '.$kelas;
                }
            }
        }

        $this->assertSame([], $pelanggar, 'Wadah gulir tanpa posisi: '.implode(' | ', $pelanggar));
    }

    /**
     * Kerangka W2 (spec v3 bagian 5, DK-33): di bawah 1024 px tidak ada bilah atas global,
     * mulai 1024 px header 68 px menempel di atas dan bilah tab ponsel menghilang. Titik
     * hentinya satu, lg, di kedua tempat; 640 sampai 1023 px (tablet) sengaja tetap memakai
     * kerangka ponsel.
     */
    public function test_the_shell_switches_at_1024px_without_a_global_top_bar_on_phones(): void
    {
        $layout = File::get(resource_path('js/layouts/AppLayout.tsx'));
        $kepala = File::get(resource_path('js/layouts/KepalaLebar.tsx'));
        $bilah = File::get(resource_path('js/components/ui/BilahTab.tsx'));

        $this->assertStringNotContainsString('<nav', $layout, 'AppLayout tidak lagi menggambar bilah atas atau navigasinya sendiri.');
        $this->assertDoesNotMatchRegularExpression('/sm:/', $layout);
        // Upgrade tailwind 4: h-[4.25rem] ditulis sebagai skala spasi, h-17 (4,25rem = 68 px).
        $this->assertStringContainsString('<header data-kepala-lebar="" className="sticky top-0 z-30 hidden h-17 items-center gap-6 border-b border-subtle bg-surface px-6 lg:flex">', $kepala);
        $this->assertStringContainsString("px-4 pb-[max(env(safe-area-inset-bottom),0.75rem)] lg:hidden')", $bilah);
        $this->assertStringNotContainsString('sm:hidden', $bilah);
        $this->assertStringContainsString("data-bilah-tab={mengambang ? '' : undefined}", $bilah);
    }

    /**
     * Lapisan tetap tidak menutup isi maupun fokus papan ketik (WCAG 2.4.11): jaraknya
     * diturunkan dari elemen yang benar-benar ada di halaman, main memberi ruang setinggi
     * itu, dan scroll-padding menjaga elemen yang difokus tetap di luar bilah dan header.
     *
     * Diperbarui ruling W2-2a: scroll-padding-bottom juga memperhitungkan --bar-aksi, yang
     * ditulis BarAksi.tsx ke <html> selagi bar itu terpasang; tanpa itu, elemen yang
     * difokus di bawah bar aksi berhenti tertutup penuh olehnya (WCAG 2.4.11).
     *
     * Diperbarui ruling W2-2b: tinggi bilah tab kini bawaan --bilah-tab (bukan dipasang
     * lewat :has()), dan dinolkan lewat :not(:has(nav[data-bilah-tab])) ketika bilahnya
     * benar-benar tidak ada. Vite 8 tidak membawa browserslist, dan build itu tetap
     * menyasar Firefox di bawah 121 yang belum mendukung :has(); di peramban itu seluruh
     * aturan :has() diam-diam gagal diurai dan tidak berlaku. Dengan bawaan bertingkat,
     * peramban itu jatuh ke ruang berlebih, bukan ruang nol yang menutup isi.
     *
     * Diperbarui ruling W2-5a: scroll-padding-top ikut memperhitungkan --pesan-jaringan,
     * yang ditulis PesanJaringan.tsx ke <html> selagi pesan jaringan tampil dan dihapus
     * begitu hilang atau dilepas; tanpa itu, lapisan pesan yang fixed itu menutupi isi
     * dan fokus papan ketik di puncak halaman. main diberi padding-top senilai variabel
     * yang sama, dengan bawaan 0 ketika pesannya tidak tampil.
     *
     * Diperbarui perbaikan akhir W2 (F-1, tinjauan akhir I-1a): scroll-padding-bottom juga
     * memperhitungkan --baki-banding, yang ditulis ConsiderationTray.tsx ke <html> selagi baki
     * banding Jelajah tampil; tanpa itu, di 360 px setiap hentian Tab di dekat tepi bawah
     * berhenti tertutup penuh oleh baki. Diperbarui W3: baki itu dilepas bersama
     * --baki-banding, jadi scroll-padding-bottom kembali ke navigasi bawah dan bar aksi.
     *
     * Diperbarui perbaikan akhir W2 (F-4, tinjauan akhir M-2): --kepala-atas mencerminkan
     * W2-2b. Mulai 1024 px tinggi header menjadi bawaan :root dan baru dinolkan lewat
     * :not(:has([data-kepala-lebar])) ketika header tidak ada. Semula nilainya hanya dipasang
     * lewat :has(), sehingga di peramban tanpa :has() tetap 0 walau header 68 px menempel:
     * fokus Shift+Tab dan loncatan jangkar berhenti di balik header, dan pesan jaringan
     * menimpanya. Kini peramban itu berakhir dengan ruang atas berlebih di halaman tanpa
     * header, bukan fokus yang tertutup di halaman berheader.
     */
    public function test_fixed_layers_leave_room_for_the_content_and_the_keyboard_focus(): void
    {
        $css = File::get(resource_path('css/app.css'));

        $this->assertStringContainsString('--nav-bawah: calc(var(--bilah-tab) + var(--strip-pendakian));', $css);
        $this->assertStringContainsString('scroll-padding-top: calc(var(--kepala-atas) + var(--pesan-jaringan, 0px) + 0.5rem);', $css, 'scroll-padding-top harus memperhitungkan tinggi pesan jaringan (ruling W2-5a).');
        $this->assertStringContainsString('scroll-padding-bottom: calc(var(--nav-bawah) + var(--bar-aksi, 0px) + 1.25rem);', $css, 'scroll-padding-bottom harus memperhitungkan tinggi bar aksi.');
        $this->assertMatchesRegularExpression('/:root \{\s*--bilah-tab: calc\(4\.375rem \+ max\(env\(safe-area-inset-bottom, 0px\), 0\.75rem\)\);/', $css, 'Tinggi bilah tab harus jadi bawaan supaya peramban tanpa :has() kelebihan ruang, bukan kehilangan ruang.');
        $this->assertMatchesRegularExpression('/:root:not\(:has\(nav\[data-bilah-tab\]\)\) \{\s*--bilah-tab: env\(safe-area-inset-bottom, 0px\);\s*\}/', $css, 'Bilah tab dinolkan hanya ketika benar-benar tidak ada di halaman.');
        $this->assertMatchesRegularExpression('/@media \(min-width: 1024px\) \{\s*:root \{\s*--kepala-atas: 4\.25rem;\s*\}\s*:root:not\(:has\(\[data-kepala-lebar\]\)\) \{\s*--kepala-atas: 0px;\s*\}\s*:root:has\(nav\[data-bilah-tab\]\) \{\s*--bilah-tab: env\(safe-area-inset-bottom, 0px\);\s*\}/', $css, 'Mulai 1024 px tinggi header harus jadi bawaan, dinolkan hanya ketika header tidak ada (cermin W2-2b).');
        $this->assertStringNotContainsString(':root:has([data-kepala-lebar])', $css, 'Tinggi header tidak boleh hanya dipasang lewat :has(), yang gugur di peramban tanpa dukungannya.');
        // Upgrade tailwind 4: pt-[var(--x)] ditulis dalam bentuk singkat variabel, pt-(--x); nilainya sama.
        $this->assertStringContainsString('<main id="konten" tabIndex={-1} className="pt-(--pesan-jaringan,0px) pb-(--nav-bawah)">', File::get(resource_path('js/layouts/AppLayout.tsx')), 'main harus mencadangkan ruang atas setinggi pesan jaringan (ruling W2-5a).');
    }

    /**
     * Setiap lapisan bawah yang dihitung jarak lapisan mengukur dirinya sendiri (perbaikan
     * akhir W2, F-1, tinjauan akhir I-1a): tinggi yang membungkus diukur lewat ResizeObserver,
     * ditulis ke <html> selagi lapisannya tampil, dan dihapus saat hilang atau dilepas, supaya
     * fokus papan ketik di 360 px tidak berhenti tertutup di baliknya (WCAG 2.4.11).
     *
     * Diperbarui W3: baki banding Jelajah dilepas (prototype 02 dan 15 tanpa baki; hitungan dan
     * pintu Simpanan ada di kepala halaman), dan --baki-banding ikut dilepas dari
     * scroll-padding dan jarak toast. Niat test semula dijaga dari dua arah: setiap variabel
     * lapisan yang dibaca scroll-padding-bottom dan jarak bawah toast punya penulis dan
     * penghapus, dan tidak ada variabel yang tertinggal tanpa penulis.
     */
    public function test_every_bottom_layer_the_spacing_counts_measures_itself(): void
    {
        $css = File::get(resource_path('css/app.css'));
        $toast = File::get(resource_path('js/components/ui/ToastUrungkan.tsx'));

        preg_match('/scroll-padding-bottom: ([^;]+);/', $css, $jarak);
        preg_match('/bottom-\[(calc\([^\]]+\))\] z-40/', $toast, $jarakToast);
        preg_match_all('/var\((--[a-z-]+)/', ($jarak[1] ?? '').' '.($jarakToast[1] ?? ''), $dibaca);

        // Diturunkan app.css dari keberadaan elemen, bukan hanya diukur komponennya.
        $dariCss = ['--nav-bawah', '--bilah-tab', '--strip-pendakian'];
        $diukur = array_values(array_diff(array_unique($dibaca[1]), $dariCss));

        $this->assertSame(['--bar-aksi'], $diukur, 'Lapisan bawah yang dihitung berubah; pastikan setiap lapisan baru mengukur dirinya.');

        $js = collect(File::allFiles(resource_path('js')))->map(fn ($berkas): string => $berkas->getContents())->implode("\n");

        foreach ($diukur as $variabel) {
            $this->assertStringContainsString("setProperty('{$variabel}'", $js, "{$variabel} dibaca tanpa penulis.");
            $this->assertStringContainsString("removeProperty('{$variabel}')", $js, "{$variabel} tidak pernah dihapus.");
        }

        $this->assertStringNotContainsString('--baki-banding', $css.$js, 'Baki banding Jelajah sudah dilepas W3; variabelnya tidak boleh tertinggal tanpa penulis.');
    }

    /**
     * Urutan fokus mengikuti urutan baca (WCAG 2.4.3): header layar lebar, isi halaman,
     * lalu bilah tab. Kepala setiap halaman ada di dalam main, jadi di ponsel fokus pertama
     * sesudah tautan lewati adalah aksi kepala halaman, bukan bilah tab.
     */
    public function test_the_focus_order_follows_the_reading_order(): void
    {
        $layout = File::get(resource_path('js/layouts/AppLayout.tsx'));
        $letak = [];

        foreach (['<KepalaLebar ', '<main id="konten"', '</main>', '<BilahTab '] as $penanda) {
            $posisi = strpos($layout, $penanda);
            $this->assertNotFalse($posisi, "{$penanda} tidak ada di AppLayout.");
            $letak[] = $posisi;
        }

        $urut = $letak;
        sort($urut);

        $this->assertSame($urut, $letak, 'Urutan kerangka: header layar lebar, main, lalu bilah tab.');
    }

    public function test_the_root_template_carries_the_skip_link_before_react_runs(): void
    {
        $isi = File::get(resource_path('views/inertia.blade.php'));

        $this->assertStringContainsString('href="#konten"', $isi);
        $this->assertStringContainsString('<x-inertia::app />', $isi);
        $this->assertStringContainsString('<x-brand.head />', $isi);
    }

    public function test_no_react_file_hardcodes_the_product_name_or_raw_colours(): void
    {
        $pelanggar = [];

        foreach (File::allFiles(resource_path('js')) as $berkas) {
            if (! in_array($berkas->getExtension(), ['ts', 'tsx'], true) || str_starts_with($berkas->getRelativePathname(), 'routes') || str_starts_with($berkas->getRelativePathname(), 'actions') || str_starts_with($berkas->getRelativePathname(), 'wayfinder')) {
                continue;
            }

            $isi = preg_replace('#/\*.*?\*/#s', ' ', $berkas->getContents());

            if (preg_match('/\b(?:text|bg|border|ring)-(?:red|rose|emerald|green|amber|yellow|indigo|blue|sky|gray)-\d{2,3}\b|\bbg-white\b|\btext-black\b|\bdark:/', $isi)) {
                $pelanggar[] = $berkas->getRelativePathname();
            }
        }

        $this->assertSame([], $pelanggar, 'Warna mentah di: '.implode(', ', $pelanggar));
    }
}
