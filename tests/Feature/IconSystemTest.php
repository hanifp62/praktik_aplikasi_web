<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Sistem ikon.
 *
 * Ikon di sini bukan hiasan. Ia hanya dipakai di tempat yang membawa arti, yaitu arah
 * naik dan turun, jenis pos, dan asal keterangan. Ikon di samping judul menambah bita
 * tanpa menambah keterangan, dan itu persis yang membuat antarmuka terbaca ramai
 * sekaligus kosong.
 */
class IconSystemTest extends TestCase
{
    use SumberReact;

    public function test_an_icon_renders_as_inline_svg_without_a_javascript_library(): void
    {
        $html = Blade::render('<x-ui.icon name="arrow-up" />');

        $this->assertStringContainsString('<svg', $html);
        $this->assertStringNotContainsString('<script', $html);
    }

    /**
     * Ikon yang menyertai teks adalah pengulangan bagi pembaca layar, dan membacakannya
     * dua kali memperpanjang tanpa menambah keterangan.
     */
    public function test_a_decorative_icon_is_hidden_from_screen_readers(): void
    {
        $this->assertStringContainsString('aria-hidden="true"', Blade::render('<x-ui.icon name="arrow-up" />'));
    }

    /**
     * Ikon yang berdiri sendiri tanpa teks harus punya namanya sendiri.
     */
    public function test_an_icon_that_stands_alone_carries_a_label(): void
    {
        $html = Blade::render('<x-ui.icon name="arrow-up" label="Naik" />');

        $this->assertStringContainsString('role="img"', $html);
        $this->assertStringContainsString('Naik', $html);
        $this->assertStringNotContainsString('aria-hidden="true"', $html);
    }

    public function test_an_unknown_name_renders_nothing_rather_than_a_broken_box(): void
    {
        $this->assertSame('', trim(Blade::render('<x-ui.icon name="tidak-ada" />')));
    }

    /**
     * Pustaka ikon tidak ditambahkan sebagai dependensi. Yang ditanam hanya ikon yang
     * benar-benar dipakai: menagih bita kepada setiap pengguna untuk ikon yang tidak
     * pernah ia lihat adalah biaya tanpa imbalan.
     */
    public function test_no_icon_library_was_added_as_a_dependency(): void
    {
        $paket = File::get(base_path('package.json'));

        foreach (['lucide', 'feather', 'heroicons', 'phosphor-icons'] as $pustaka) {
            $this->assertStringNotContainsString($pustaka, $paket);
        }
    }

    /**
     * Ikon hanya di tempat yang membawa arti.
     *
     * Aturan ini dijaga di sini supaya ia tidak luntur begitu ikonnya mudah dipakai:
     * komponen judul halaman dan kartu tidak boleh memanggilnya sama sekali, karena
     * ikon di samping judul adalah hiasan menurut definisinya.
     */
    public function test_no_icon_decorates_a_heading(): void
    {
        foreach (['PageHeader', 'Card'] as $komponen) {
            $isi = File::get(resource_path("js/components/ui/{$komponen}.tsx"));

            $this->assertStringNotContainsString('<Icon', $isi, "Komponen {$komponen} memakai ikon sebagai hiasan judul.");
            $this->assertStringNotContainsString('<svg', $isi, "Komponen {$komponen} menggambar hiasan di samping judul.");
        }
    }

    /**
     * Ikon React mengikuti aturan yang sama dengan ikon Blade di halaman publik: yang
     * menyertai teks disembunyikan dari pembaca layar, yang berdiri sendiri membawa
     * namanya, dan nama yang tidak dikenal tidak menggambar apa pun. Navigasi bawah
     * ponsel memakainya di setiap halaman, selalu bersama labelnya.
     *
     * Diperbarui gelombang 1 v3: ikon tab kini ditulis dengan ukuran bernama 22 px dan
     * varian isi untuk tab aktif (spec v3 bagian 8.3), bukan kelas h-6 w-6.
     *
     * Diperbarui W2: tab tidak lagi digambar di NavLinks.tsx, melainkan oleh bilah tab ponsel
     * (BilahTab, 22 px) dan navigasi segmen layar lebar (KepalaLebar, 20 px). Aturannya sama:
     * ikon selalu berdampingan dengan labelnya dan memakai varian isi untuk tab aktif.
     */
    public function test_the_react_icon_follows_the_same_rules(): void
    {
        $ikon = File::get(resource_path('js/components/ui/Icon.tsx'));

        $this->assertStringContainsString("role={label ? 'img' : undefined}", $ikon);
        $this->assertStringContainsString('aria-hidden={label ? undefined : true}', $ikon);
        $this->assertStringContainsString('{label ? <title>{label}</title> : null}', $ikon);
        $this->assertMatchesRegularExpression('/if \(!d\) \{\s*return null;\s*\}/', $ikon);

        $bilah = File::get(resource_path('js/components/ui/BilahTab.tsx'));
        $this->assertMatchesRegularExpression('/<Icon name=\{t\.ikon\} size=\{22\} isi=\{t\.aktif\} \/>\s*<span[^>]*>\{t\.label\}<\/span>/', $bilah, 'Ikon bilah tab ponsel harus berdampingan dengan labelnya.');

        $segmen = File::get(resource_path('js/layouts/KepalaLebar.tsx'));
        $this->assertMatchesRegularExpression('/<Icon name=\{t\.ikon\} size=\{20\} isi=\{t\.aktif\} \/>\s*<span[^>]*>\{t\.label\}<\/span>/', $segmen, 'Ikon navigasi segmen harus berdampingan dengan labelnya.');
    }

    /**
     * Bobot mengikuti ukuran render (spec v3 bagian 8.3, DK-39): Bold di bawah 20 px,
     * Regular mulai 20 px, isi hanya untuk tab aktif. Setiap ikon di registri membawa kedua
     * bobot garis, dan setiap ikon tab membawa varian isinya.
     */
    public function test_the_weight_follows_the_render_size(): void
    {
        $ikon = File::get(resource_path('js/components/ui/Icon.tsx'));
        $registri = File::get(resource_path('js/components/ui/ikon/phosphor.ts'));

        $this->assertMatchesRegularExpression("/return ukuran < 20 \? 'bold' : 'regular';/", $ikon);
        $this->assertStringContainsString('isi && varian.fill ? varian.fill : varian[bobotUntuk(size)]', $ikon);

        preg_match_all("/^    '([a-z-]+)': \{\n(.*?)\n    \},/ms", $registri, $entri, PREG_SET_ORDER);

        $this->assertNotEmpty($entri, 'Registri ikon tidak terbaca.');

        foreach ($entri as [, $nama, $isi]) {
            $this->assertMatchesRegularExpression("/\bregular: 'M[^']+'/", $isi, "{$nama} tanpa bobot regular.");
            $this->assertMatchesRegularExpression("/\bbold: 'M[^']+'/", $isi, "{$nama} tanpa bobot bold.");
        }

        preg_match_all("/ikon: '([a-z-]+)' as const/", File::get(resource_path('js/layouts/NavLinks.tsx')), $tab);

        $this->assertCount(5, $tab[1], 'Lima tab navigasi utama.');

        foreach ($tab[1] as $nama) {
            $this->assertMatchesRegularExpression("/^    '".preg_quote($nama, '/')."': \{\n[^}]*\bfill: 'M/m", $registri, "Ikon tab {$nama} tanpa varian isi.");
        }
    }

    public function test_the_blade_icon_picks_bold_at_16px_and_regular_from_20px(): void
    {
        $kecil = Blade::render('<x-ui.icon name="arrow-up" />');
        $besar = Blade::render('<x-ui.icon name="arrow-up" :size="20" />');

        $this->assertStringContainsString('d="M208.49,120.49', $kecil, '16 px memakai bobot Bold.');
        $this->assertStringContainsString('class="size-4 ', $kecil);
        $this->assertStringContainsString('d="M205.66,117.66', $besar, '20 px memakai bobot Regular.');
        $this->assertStringContainsString('class="size-5 ', $besar);
    }

    /**
     * Tidak ada ikon informatif di bawah 16 px (DK-39). Panah naik dan turun di daftar pos
     * dahulu 12 px lewat kelas h-3 w-3; ukuran kini hanya lewat prop size, dan komponen
     * Blade menolak ukuran di bawah lantai.
     */
    public function test_no_informative_icon_is_drawn_below_16px(): void
    {
        $pesan = null;

        try {
            Blade::render('<x-ui.icon name="arrow-up" :size="12" />');
        } catch (\Throwable $galat) {
            $pesan = $galat->getMessage();
        }

        $this->assertNotNull($pesan, 'Ikon Blade 12 px seharusnya ditolak.');
        $this->assertStringContainsString('paling kecil 16 px', $pesan);

        $pelanggar = [];

        foreach ($this->berkasTsx() as $berkas) {
            $isi = $this->tanpaKomentarJs($berkas->getContents());

            foreach ($this->tagBernama($isi, 'Icon') as [$tag, $baris]) {
                $lokasi = $berkas->getRelativePathname().':'.$baris;

                if (preg_match('/className="[^"]*\b(?:h|w|size)-/', $tag)) {
                    $pelanggar[] = "{$lokasi} mengatur ukuran lewat kelas";
                }

                if (preg_match('/\bsize=\{(\d+)\}/', $tag, $ukuran) && ! in_array((int) $ukuran[1], [16, 20, 22, 24, 28, 32], true)) {
                    $pelanggar[] = "{$lokasi} berukuran {$ukuran[1]} px";
                }
            }
        }

        foreach (File::allFiles(resource_path('views')) as $berkas) {
            if (preg_match('/<x-ui\.icon\b[^>]*\bclass="[^"]*\b(?:h|w|size)-/', $berkas->getContents())) {
                $pelanggar[] = $berkas->getRelativePathname().' mengatur ukuran ikon lewat kelas';
            }
        }

        $this->assertSame([], $pelanggar, implode("\n", $pelanggar));
    }

    /**
     * Label tombol tanpa panah hiasan (spec v3 bagian 8.3 dan 8.8, antislop R-08). Panah
     * maju pada tombol aksi adalah ciri klaster "tampilan AI", dan panah teks datang dari
     * huruf, bukan dari sistem ikon. Arah kembali memakai ikon arrow-left dari registri;
     * chevron hanya untuk baris yang membuka halaman lain, tidak untuk tombol.
     */
    public function test_no_button_label_carries_a_decorative_arrow(): void
    {
        $pelanggar = [];

        foreach ($this->berkasTsx() as $berkas) {
            $isi = $this->tanpaKomentarJs($berkas->getContents());

            foreach (['Button', 'button'] as $elemen) {
                preg_match_all('/<'.$elemen.'(?=[\s>])/', $isi, $cocok, PREG_OFFSET_CAPTURE);

                foreach ($cocok[0] as [, $mulai]) {
                    $tag = $this->tagPembuka($isi, $mulai);

                    if (str_ends_with($tag, '/>')) {
                        continue;
                    }

                    $awalLabel = $mulai + strlen($tag);
                    $akhir = strpos($isi, '</'.$elemen.'>', $awalLabel);
                    $label = $akhir === false ? '' : substr($isi, $awalLabel, $akhir - $awalLabel);

                    if (preg_match('/[→↗➔➜⇒›»←]|&rarr;|&larr;|&raquo;|&#8594;/u', $label)
                        || preg_match('/<Icon name="(?:arrow-right|arrow-up-right|caret-right|caret-double-right)"/', $label)) {
                        $pelanggar[] = $berkas->getRelativePathname().':'.(substr_count(substr($isi, 0, $mulai), "\n") + 1);
                    }
                }
            }
        }

        $this->assertSame([], $pelanggar, 'Panah hiasan di label tombol: '.implode(', ', $pelanggar));
    }

    /**
     * Tiga glyph domain pilot, tidak lebih (ruling W1-R3). Set lengkap menunggu uji
     * pengenalan dengan pendaki (spec v3 bagian 15, Q15); glyph keempat yang muncul sebelum
     * itu adalah keputusan yang dilompati.
     */
    public function test_the_three_pilot_domain_glyphs_are_registered(): void
    {
        $glyph = File::get(resource_path('js/components/ui/ikon/glyphDomain.ts'));

        $this->assertStringContainsString("export type NamaGlyph = 'pos' | 'tanjakan' | 'resmi';", $glyph);
        $this->assertSame(3, preg_match_all('/^    [a-z]+: \{$/m', $glyph), 'Registri glyph berisi tepat tiga entri.');
        $this->assertStringContainsString('export type NamaIkon = NamaPhosphor | NamaGlyph;', File::get(resource_path('js/components/ui/Icon.tsx')));
    }

    /**
     * Glyph mengikuti tata bahasa tanda "Jalur Berpos": garis lurus monoline dengan belokan
     * bersudut (hanya perintah M, L, dan Z), ujung dan sambungan bulat, cincin pos sebagai
     * lingkaran, digambar sebagai goresan dengan tebal Phosphor per bobot. Glyph bukan
     * turunan Phosphor, jadi berkasnya tidak menyebut pustaka itu.
     */
    public function test_the_pilot_glyphs_follow_the_jalur_berpos_grammar(): void
    {
        $glyph = File::get(resource_path('js/components/ui/ikon/glyphDomain.ts'));
        $ikon = File::get(resource_path('js/components/ui/Icon.tsx'));

        preg_match_all("/'(M[^']*)'/", $glyph, $jalur);

        $this->assertNotEmpty($jalur[1]);

        foreach ($jalur[1] as $d) {
            $this->assertMatchesRegularExpression('/^M[\d. ]+(?: L[\d. ]+)+(?: Z)?$/', $d, "Glyph memakai perintah selain M, L, dan Z: {$d}");
        }

        $this->assertMatchesRegularExpression("/pos: \{\n\s*garis: \[[^\]]+\],\n\s*cincin: \[\{ cx: \d+, cy: \d+, r: \d+ \}\],/", $glyph, 'Glyph pos membawa satu cincin pos.');
        $this->assertStringNotContainsStringIgnoringCase('phosphor', $glyph);

        foreach (['fill="none"', 'stroke="currentColor"', "strokeWidth={bobotUntuk(size) === 'bold' ? 24 : 16}", 'strokeLinecap="round"', 'strokeLinejoin="round"'] as $atribut) {
            $this->assertStringContainsString($atribut, $ikon);
        }
    }

    /**
     * Setiap titik glyph berada di area hidup 32 sampai 224 pada kisi 256, termasuk tepi
     * cincin, supaya goresan Bold 24 unit tidak terpotong tepi kotak gambar.
     */
    public function test_every_pilot_glyph_stays_inside_the_live_area(): void
    {
        $glyph = File::get(resource_path('js/components/ui/ikon/glyphDomain.ts'));

        preg_match_all("/'(M[^']*)'/", $glyph, $jalur);

        foreach ($jalur[1] as $d) {
            preg_match_all('/\d+(?:\.\d+)?/', $d, $angka);

            foreach ($angka[0] as $nilai) {
                $this->assertGreaterThanOrEqual(32, (float) $nilai, "Titik di luar area hidup: {$d}");
                $this->assertLessThanOrEqual(224, (float) $nilai, "Titik di luar area hidup: {$d}");
            }
        }

        preg_match_all('/\{ cx: (\d+), cy: (\d+), r: (\d+) \}/', $glyph, $cincin, PREG_SET_ORDER);

        $this->assertNotEmpty($cincin);

        foreach ($cincin as [, $cx, $cy, $r]) {
            foreach ([(int) $cx - (int) $r, (int) $cx + (int) $r, (int) $cy - (int) $r, (int) $cy + (int) $r] as $tepi) {
                $this->assertGreaterThanOrEqual(32, $tepi, "Cincin {$cx},{$cy} keluar area hidup.");
                $this->assertLessThanOrEqual(224, $tepi, "Cincin {$cx},{$cy} keluar area hidup.");
            }
        }
    }
}
