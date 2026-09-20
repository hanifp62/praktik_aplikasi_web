# Sistem Desain — Rencana Implementasi

> **Untuk pengerjaan agentik:** SUB-SKILL WAJIB: pakai superpowers:subagent-driven-development
> (disarankan) atau superpowers:executing-plans untuk mengerjakannya tugas demi tugas.
> Langkahnya memakai checkbox (`- [ ]`) untuk penanda.

**Tujuan:** Mengganti Tailwind bawaan yang ditempeli satu warna merek dengan sistem
bertingkat tiga, lalu memakai sistem itu untuk mengubah rupa seluruh aplikasi dari satu
tempat.

**Arsitektur:** Lapisan semantik disisipkan di antara primitif warna dan view. Dua tugas
pertama mempertahankan rupa apa adanya sehingga dapat diverifikasi lewat 746 test yang
sudah ada; rupa baru berubah di tugas ketiga, di selusin nilai. Empat tugas sisanya
menutup celah yang ditemukan audit dan berdiri sendiri-sendiri.

**Tumpukan:** Tailwind 3 dengan CSS custom property, Blade, Livewire 3, PHPUnit 12, Pint.

**Spec:** `docs/superpowers/specs/2026-09-20-sistem-desain-design.md`

**Menggantikan:** `docs/superpowers/plans/2026-09-20-fase-4-sistem-visual.md`. Isi rencana
itu diserap Tugas 1 sampai 3 di sini dan tidak dijalankan terpisah.

## Batasan menyeluruh

Disalin dari spec. Setiap tugas tunduk padanya tanpa perlu diulang:

- **WCAG 2.2 AA**: teks normal 4.5:1, komponen non-teks 3:1, dihitung bukan ditaksir.
- **Warna semantik tidak didesaturasi.** Status resmi, peringatan, dan bahaya membawa
  arti keselamatan.
- **§92**: keterangan resmi dan masukan komunitas tetap terbedakan secara visual.
- **§88 mobile-first**: tidak ada yang meluber pada lebar 400px.
- **Ikon hanya di tempat yang membawa arti**, tidak pernah sebagai hiasan.
- **746 test tetap hijau tanpa disunting** pada Tugas 1 dan 2.
- Setiap tugas berakhir: Pint bersih, suite penuh hijau, `npm run build` selesai, commit.

## Struktur berkas

| Berkas | Tanggung jawab | Tugas |
|---|---|---|
| `resources/css/app.css` | Satu-satunya tempat token didefinisikan | 1, 3, 6 |
| `tailwind.config.js` | Memetakan token ke kelas Tailwind dan satu elevasi | 1, 3 |
| `tests/Feature/ColourContrastTest.php` | Mengunci rasio pasangan semantik | 3 |
| ~60 berkas view | Pemakaian kelas semantik | 2 |
| `resources/views/components/ui/card.blade.php` | Permukaan bersama | 3, 6 |
| `resources/views/components/ui/page-header.blade.php` | Skala judul dan lebar baca | 3, 6 |
| `resources/views/components/dropdown.blade.php` | Lapisan melayang | 3 |
| `resources/views/components/modal.blade.php` | Lapisan melayang | 3 |
| `resources/views/layouts/guest.blade.php` | Kartu masuk, sisa scaffolding | 3 |
| `resources/views/components/ui/icon.blade.php` | **Baru.** Ikon sebaris | 4 |
| `resources/views/components/ui/checkpoint-journey.blade.php` | Pemakai pertama ikon arah | 4 |
| `resources/views/layouts/app.blade.php` | Ikon situs | 5 |
| `resources/views/public/trail.blade.php` | Kartu sosial halaman publik | 5 |
| `resources/views/components/ui/skeleton.blade.php` | **Baru.** Bentuk saat memuat | 7 |
| `resources/views/livewire/recommendations/recommendation-results.blade.php` | Pemakai pertama skeleton | 7 |

---

## Tugas 1: Lapisan semantik, tanpa mengubah rupa

**Berkas:**
- Ubah: `resources/css/app.css`, blok `:root`
- Ubah: `tailwind.config.js`, blok `colors`
- Test: `tests/Feature/SemanticTokenTest.php` (baru)

**Menghasilkan** (dipakai Tugas 2, 3, 6, 7): kelas `text-primary`, `text-secondary`,
`text-muted`, `bg-canvas`, `bg-surface`, `bg-sunken`, `border-subtle`.

**Nilai awalnya sengaja identik dengan abu yang digantikan**, sehingga tugas ini tidak
mengubah satu piksel pun. Itu yang membuat Tugas 2 dapat diverifikasi.

- [ ] **Langkah 1: Tulis test yang gagal**

Buat `tests/Feature/SemanticTokenTest.php`:

```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Lapisan semantik.
 *
 * Sebelum ini ada 25 token dan hanya satu yang menyebut peran, sementara 597 abu mentah
 * Tailwind dipakai langsung di view dengan empat abu berbeda untuk teks yang saling
 * tertukar. Tanpa lapisan yang menyebut peran, setiap layar memutuskan sendiri dan
 * keputusannya selalu default.
 */
class SemanticTokenTest extends TestCase
{
    /**
     * @return array<int, string>
     */
    public static function tokenSemantik(): array
    {
        return [
            ['text-primary'], ['text-secondary'], ['text-muted'],
            ['canvas'], ['surface'], ['surface-sunken'],
            ['border-subtle'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('tokenSemantik')]
    public function test_the_semantic_token_is_defined(string $nama): void
    {
        $this->assertMatchesRegularExpression(
            '/--'.preg_quote($nama, '/').':\s*\d+\s+\d+\s+\d+;/',
            File::get(resource_path('css/app.css')),
            "Token --{$nama} belum ada."
        );
    }

    public function test_tailwind_exposes_every_semantic_token(): void
    {
        $config = File::get(base_path('tailwind.config.js'));

        foreach (['primary', 'secondary', 'muted', 'canvas', 'surface', 'sunken', 'subtle'] as $nama) {
            $this->assertStringContainsString($nama, $config, "Tailwind belum memetakan {$nama}.");
        }
    }
}
```

- [ ] **Langkah 2: Jalankan dan pastikan gagal**

Jalankan: `php artisan test tests/Feature/SemanticTokenTest.php`
Harapkan: GAGAL, delapan test, pesan "Token --text-primary belum ada."

- [ ] **Langkah 3: Tambahkan token**

Di `resources/css/app.css`, tepat sesudah `--radius-control: 0.375rem;`:

```css
        /*
         * Lapisan semantik.
         *
         * Tingkat di atas ini menyebut warna; tingkat ini menyebut peran. Sebelum
         * lapisan ini ada, view memakai empat abu Tailwind bergantian untuk peran yang
         * sama, dan tidak ada aturan mana berarti apa.
         *
         * Nilai awalnya sengaja identik dengan abu yang digantikan, sehingga
         * penambahan ini tidak mengubah satu piksel pun. Rupa baru berubah setelah
         * seluruh view memakainya.
         *
         * Rasio dihitung terhadap kanvas, bukan terhadap putih: teks dibaca di atas
         * kanvas jauh lebih sering daripada di atas kartu.
         *
         *   text-primary   di atas canvas   16.97:1
         *   text-secondary di atas canvas    9.86:1
         *   text-muted     di atas canvas    4.63:1   margin 0.13 dari ambang
         */
        --text-primary: 17 24 39;
        --text-secondary: 55 65 81;
        --text-muted: 107 114 128;

        --canvas: 243 244 246;
        --surface: 255 255 255;
        --surface-sunken: 249 250 251;

        --border-subtle: 229 231 235;
```

- [ ] **Langkah 4: Petakan ke Tailwind**

Di `tailwind.config.js`, di dalam `colors`, sesudah `control: token('control-border'),`:

```js
                canvas: token('canvas'),
                surface: {
                    DEFAULT: token('surface'),
                    sunken: token('surface-sunken'),
                },
                subtle: token('border-subtle'),
                primary: token('text-primary'),
                secondary: token('text-secondary'),
                muted: token('text-muted'),
```

- [ ] **Langkah 5: Jalankan dan pastikan lulus**

Jalankan: `php artisan test tests/Feature/SemanticTokenTest.php`
Harapkan: LULUS, delapan test.

- [ ] **Langkah 6: Suite penuh, Pint, build, commit**

```bash
./vendor/bin/pint
php artisan test
npm run build
git add resources/css/app.css tailwind.config.js tests/Feature/SemanticTokenTest.php
git commit -m "feat: lapisan token semantik, nilainya identik dengan abu yang digantikan"
```

Harapkan: 754 test hijau. Bila ada yang merah, tokennya salah ketik, bukan rupanya
berubah.

---

## Tugas 2: Migrasi 597 kelas, rupa tetap sama

**Berkas:** ~60 berkas view, `tests/Feature/SemanticTokenTest.php` (tambah satu test)

**Konsumsi:** kelas semantik dari Tugas 1.

Ini penggantian nama murni. Karena nilai tokennya identik, **rupanya wajib tidak
berubah**, dan 746 test yang sudah ada adalah buktinya.

- [ ] **Langkah 1: Tulis sapuan yang gagal**

Tambahkan ke `tests/Feature/SemanticTokenTest.php`:

```php
    /**
     * Abu mentah Tailwind tidak boleh dipakai langsung lagi.
     *
     * Selama ia boleh, layar berikutnya yang ditulis siapa pun akan mengarang abunya
     * sendiri, dan lapisan semantik menjadi lapisan yang dilewati.
     */
    public function test_no_view_reaches_past_the_semantic_layer(): void
    {
        $pelanggar = [];

        foreach (File::allFiles(resource_path('views')) as $berkas) {
            $isi = preg_replace('/\{\{--.*?--\}\}/s', ' ', $berkas->getContents());

            if (preg_match('/\b(text|bg|border)-gray-\d{2,3}\b/', $isi)) {
                $pelanggar[] = $berkas->getRelativePathname();
            }
        }

        $this->assertSame([], $pelanggar, 'Abu mentah masih dipakai di: '.implode(', ', $pelanggar));
    }
```

- [ ] **Langkah 2: Jalankan dan pastikan gagal**

Jalankan: `php artisan test tests/Feature/SemanticTokenTest.php --filter=semantic_layer`
Harapkan: GAGAL, menyebut sekitar 60 berkas.

- [ ] **Langkah 3: Ganti yang dapat diganti mekanis**

```bash
grep -rl 'gray-' resources/views --include=*.blade.php | xargs sed -i \
  -e 's/\btext-gray-900\b/text-primary/g' \
  -e 's/\btext-gray-800\b/text-primary/g' \
  -e 's/\btext-gray-700\b/text-secondary/g' \
  -e 's/\btext-gray-600\b/text-secondary/g' \
  -e 's/\btext-gray-500\b/text-muted/g' \
  -e 's/\bborder-gray-200\b/border-subtle/g' \
  -e 's/\bborder-gray-100\b/border-subtle/g' \
  -e 's/\bbg-gray-50\b/bg-surface-sunken/g'
```

- [ ] **Langkah 4: Tinjau `bg-gray-100` satu per satu**

```bash
grep -rn 'bg-gray-100' resources/views --include=*.blade.php
```

Untuk setiap baris, pilih sasarannya menurut perannya:

- Latar halaman pada `layouts/app.blade.php` → `bg-canvas`
- Chip, lencana, dan nomor pos (mis. `rounded bg-gray-100 px-2`) → `bg-surface-sunken`
- Latar bilah kemajuan → `bg-surface-sunken`

`bg-gray-100` tidak dapat disapu rata karena satu kelas yang sama dipakai untuk kanvas
halaman dan untuk chip kecil, dan keduanya menuju token berbeda.

- [ ] **Langkah 5: Jalankan sapuan dan pastikan lulus**

Jalankan: `php artisan test tests/Feature/SemanticTokenTest.php`
Harapkan: LULUS, sembilan test.

- [ ] **Langkah 6: Buktikan rupanya tidak berubah**

```bash
php artisan test
```

Harapkan: 755 test hijau **tanpa satu pun test disunting**. Test yang merah di sini
berarti `sed` mengenai kelas yang tidak dimaksud, dan harus dikembalikan satu per satu,
bukan testnya yang disesuaikan.

- [ ] **Langkah 7: Pint, build, commit**

```bash
./vendor/bin/pint
npm run build
git add resources/views tests/Feature/SemanticTokenTest.php
git commit -m "refactor: 597 abu mentah diganti kelas semantik, rupa tidak berubah"
```

---

## Tugas 3: Rupa berubah, di selusin nilai

**Berkas:**
- Ubah: `resources/css/app.css`, nilai semantik dan `--canvas`
- Ubah: `tailwind.config.js`, satu elevasi bernama
- Ubah: `resources/views/components/ui/card.blade.php`
- Ubah: `resources/views/components/ui/page-header.blade.php`
- Ubah: berkas berbayang — 48 `shadow-sm`, lalu `dropdown`, `modal`, `layouts/guest`
- Ubah: `tests/Feature/ColourContrastTest.php`, sembilan pasangan semantik
- Test: `tests/Feature/SurfaceSystemTest.php` (baru)

**Konsumsi:** kelas semantik dari Tugas 1, sudah terpakai di seluruh view lewat Tugas 2.

Inilah satu-satunya tugas yang mengubah tampilan, dan ia mengubahnya di seluruh aplikasi
sekaligus.

- [ ] **Langkah 1: Tulis test yang gagal**

Buat `tests/Feature/SurfaceSystemTest.php`:

```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Permukaan dan skala.
 *
 * Kanvas abu dingin bawaan Tailwind dipakai puluhan ribu dasbor, dan empat puluh delapan
 * bayangan membuat setiap blok tampak melayang sehingga halaman tidak punya bidang dasar.
 */
class SurfaceSystemTest extends TestCase
{
    private function css(): string
    {
        static $isi = null;

        return $isi ??= File::get(resource_path('css/app.css'));
    }

    /**
     * Kanvas hangat, netral hangat. Permukaan hangat dengan teks dingin adalah ciri tema
     * yang ditempel di atas default.
     */
    public function test_the_canvas_is_warm(): void
    {
        preg_match('/--canvas:\s*(\d+)\s+(\d+)\s+(\d+);/', $this->css(), $m);

        $this->assertNotEmpty($m, 'Token canvas tidak ditemukan.');
        $this->assertGreaterThan((int) $m[3], (int) $m[1], 'Kanvas harus lebih hangat: merah di atas biru.');
    }

    public function test_no_view_uses_a_drop_shadow_as_a_separator(): void
    {
        $pelanggar = [];

        foreach (File::allFiles(resource_path('views')) as $berkas) {
            $isi = preg_replace('/\{\{--.*?--\}\}/s', ' ', $berkas->getContents());

            if (preg_match('/\bshadow-(sm|md|lg|xl)\b/', $isi)) {
                $pelanggar[] = $berkas->getRelativePathname();
            }
        }

        $this->assertSame([], $pelanggar, 'Bayangan sebagai pemisah di: '.implode(', ', $pelanggar));
    }

    public function test_the_page_title_carries_real_weight(): void
    {
        $isi = File::get(resource_path('views/components/ui/page-header.blade.php'));

        $this->assertMatchesRegularExpression('/text-3xl|text-4xl/', $isi);
        $this->assertStringNotContainsString('text-2xl', $isi);
    }
}
```

- [ ] **Langkah 2: Jalankan dan pastikan gagal**

Jalankan: `php artisan test tests/Feature/SurfaceSystemTest.php`
Harapkan: GAGAL, tiga test.

- [ ] **Langkah 3: Hangatkan netral dan kanvas**

Di `resources/css/app.css`, ganti tujuh nilai yang ditambahkan Tugas 1, **beserta tiga
baris rasio di komentar di atasnya**, menjadi:

```css
         *   text-primary   di atas canvas   16.73:1   sumur 16.02:1
         *   text-secondary di atas canvas    9.83:1   sumur  9.41:1
         *   text-muted     di atas canvas    5.11:1   sumur  4.89:1
         */
        --text-primary: 28 25 23;
        --text-secondary: 68 64 60;
        --text-muted: 112 106 100;

        --canvas: 250 250 248;
        --surface: 255 255 255;
        --surface-sunken: 245 245 243;

        --border-subtle: 233 232 228;
```

Netral hangat menggantikan abu Tailwind yang bersemu biru.

`--text-muted` satu tingkat lebih gelap daripada angka di spec. Tabel spec menghitungnya
terhadap kanvas saja dan mendapat 4,63:1, tetapi label dan metadata juga muncul di atas
`--surface-sunken`, dan nilai spec hanya mencapai **4,40:1** di sana — gagal AA di
permukaan yang tidak ikut dihitung. Nilai baru ini terukur 5,11:1 terhadap kanvas, 5,34:1
terhadap kartu, dan 4,89:1 terhadap sumur. Langkah berikutnya yang menguncinya.

- [ ] **Langkah 4: Kunci pasangan semantik dengan test yang menghitung**

Di `tests/Feature/ColourContrastTest.php`, tambahkan sembilan baris di akhir `textPairs()`,
sebelum `];`:

```php
            // Pasangan semantik. Sampai lapisan ini ada, yang dihitung hanya pasangan
            // warna merek, dan warna yang membawa hampir seluruh teks aplikasi tidak
            // pernah diperiksa sama sekali.
            //
            // Ketiganya dihitung terhadap tiga permukaan, bukan terhadap putih saja:
            // nilai muted yang ditulis spec lolos di atas kanvas dan gagal di atas
            // sumur, dan kegagalan itu hanya terlihat ketika pasangannya disebut.
            ['text-primary', 'canvas', 'judul di atas latar halaman'],
            ['text-primary', 'surface', 'judul di atas kartu'],
            ['text-secondary', 'canvas', 'badan teks di atas latar halaman'],
            ['text-secondary', 'surface', 'badan teks di atas kartu'],
            ['text-secondary', 'surface-sunken', 'badan teks di atas sumur'],
            ['text-muted', 'canvas', 'metadata di atas latar halaman'],
            ['text-muted', 'surface', 'metadata di atas kartu'],
            ['text-muted', 'surface-sunken', 'metadata di atas sumur'],
            ['text-muted', 'white', 'metadata di atas putih murni'],
```

Jalankan: `php artisan test tests/Feature/ColourContrastTest.php`
Harapkan: LULUS. Kembalikan `--text-muted` ke `120 113 108` sekali untuk melihat baris
"metadata di atas sumur" gagal pada 4,40:1, lalu kembalikan lagi. Test yang tidak pernah
terlihat merah belum terbukti memeriksa apa pun.

- [ ] **Langkah 5: Ganti bayangan kartu dengan garis rambut**

Di `resources/views/components/ui/card.blade.php` baris 3:

```blade
{{--
    Satu garis rambut, bukan bayangan. Empat puluh delapan bayangan membuat setiap blok
    tampak melayang, dan halaman yang seluruh isinya melayang tidak punya bidang dasar.

    Padding naik dari p-5: ruang dalam yang sempit membuat isinya terbaca padat berapa
    pun ukuran hurufnya.
--}}
<section {{ $attributes->merge(['class' => 'rounded-lg border border-subtle bg-surface p-6']) }}>
```

- [ ] **Langkah 6: Cabut 48 bayangan kartu**

```bash
grep -rl 'shadow-sm' resources/views --include=*.blade.php \
  | xargs sed -i 's/ shadow-sm//g; s/shadow-sm //g'
```

- [ ] **Langkah 7: Beri lapisan melayang satu elevasi yang disengaja**

Tiga sisa bayangan bukan kartu: `components/dropdown.blade.php` (`shadow-lg`),
`components/modal.blade.php` (`shadow-xl`), dan `layouts/guest.blade.php` (`shadow-md`).

Dropdown dan modal benar-benar melayang di atas isi halaman, dan garis rambut tidak cukup
memisahkannya. Keduanya mendapat satu elevasi bernama alih-alih tiga tingkat Tailwind yang
dipilih sendiri-sendiri.

Di `tailwind.config.js`, sesudah blok `colors`:

```js
            // Satu elevasi, untuk lapisan yang benar-benar melayang di atas halaman.
            // Tailwind menyediakan lima tingkat dan setiap pemakaian memilih sendiri;
            // hasilnya tiga tingkat berbeda untuk tiga lapisan yang perannya sama.
            // Bayangannya berwarna netral hangat, bukan hitam murni.
            boxShadow: {
                overlay: '0 12px 32px -8px rgb(28 25 23 / 0.18)',
            },
```

Lalu:

```bash
sed -i 's/shadow-lg/shadow-overlay/' resources/views/components/dropdown.blade.php
sed -i 's/shadow-xl/shadow-overlay/' resources/views/components/modal.blade.php
sed -i 's/bg-white shadow-md/bg-surface border border-subtle/' resources/views/layouts/guest.blade.php
```

Kartu masuk pada `layouts/guest.blade.php` tidak melayang — ia satu-satunya blok di
halaman itu, dan bayangannya hanya warisan scaffolding Breeze.

Perbarui `test_no_view_uses_a_drop_shadow_as_a_separator` agar menerima `shadow-overlay`:
polanya `\bshadow-(sm|md|lg|xl)\b` tidak mengenainya, jadi tidak ada yang perlu diubah.
Periksa saja dengan `grep -rn 'shadow-' resources/views` bahwa yang tersisa hanya
`shadow-overlay`.

- [ ] **Langkah 8: Naikkan skala judul**

Ganti seluruh isi `resources/views/components/ui/page-header.blade.php`:

```blade
@props(['title', 'description' => null])

{{--
    Judul memakai serif lewat lapisan base, dan ukurannya naik dua tingkat. text-2xl
    hanya dua tingkat di atas badan teks, sehingga halaman tidak punya titik masuk.

    Deskripsi naik ke text-base dan dibatasi lebar baca: justru kalimat inilah yang
    menentukan apakah pembaca meneruskan.
--}}
<div class="mb-8">
    <h1 class="text-balance text-3xl text-primary sm:text-4xl">{{ $title }}</h1>

    @if ($description)
        <p class="mt-2 max-w-prose text-pretty text-base text-secondary">{{ $description }}</p>
    @endif

    {{ $slot }}
</div>
```

- [ ] **Langkah 9: Naikkan judul kartu**

Di `resources/views/components/ui/card.blade.php`:

```blade
    @if ($title)
        <h2 class="text-lg font-semibold text-primary">{{ $title }}</h2>
    @endif
    @if ($subtitle)
        <p class="mt-1 max-w-prose text-base text-secondary">{{ $subtitle }}</p>
    @endif
```

- [ ] **Langkah 10: Jalankan, Pint, build, commit**

```bash
php artisan test
./vendor/bin/pint
npm run build
git add resources tailwind.config.js tests/Feature/SurfaceSystemTest.php tests/Feature/ColourContrastTest.php
git commit -m "feat: kanvas hangat, garis rambut menggantikan bayangan, skala judul"
```

Harapkan: seluruh test hijau, termasuk `ColourContrastTest` yang menghitung ulang
pasangan barunya.

---

## Tugas 4: Sistem ikon

**Berkas:**
- Buat: `resources/views/components/ui/icon.blade.php`
- Test: `tests/Feature/IconSystemTest.php` (baru)
- Ubah: `resources/views/components/ui/checkpoint-journey.blade.php`

Lima SVG di seluruh aplikasi, tanpa pustaka. Setiap baris adalah dinding kata.

Phosphor, berat `regular`, ditanam sebagai SVG sebaris. Bukan Lucide maupun Heroicons:
keduanya pilihan bawaan hampir semua antarmuka hasil AI, dan memakainya berarti mengulang
tanda tangan yang sedang dihapus.

- [ ] **Langkah 1: Tulis test yang gagal**

Buat `tests/Feature/IconSystemTest.php`:

```php
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
     * benar-benar dipakai.
     */
    public function test_no_icon_library_was_added_as_a_dependency(): void
    {
        $paket = File::get(base_path('package.json'));

        foreach (['lucide', 'feather', 'heroicons', 'phosphor-icons'] as $pustaka) {
            $this->assertStringNotContainsString($pustaka, $paket);
        }
    }
}
```

- [ ] **Langkah 2: Jalankan dan pastikan gagal**

Jalankan: `php artisan test tests/Feature/IconSystemTest.php`
Harapkan: GAGAL, lima test, "Unable to locate a class or view for component [ui.icon]".

- [ ] **Langkah 3: Bangun komponennya**

Buat `resources/views/components/ui/icon.blade.php`:

```blade
@props(['name', 'label' => null, 'class' => 'h-4 w-4'])

@php
    /*
     * Ikon Phosphor berat regular, ditanam sebagai path.
     *
     * Ditanam, bukan diimpor dari pustaka: yang dipakai aplikasi ini segelintir, dan
     * menambah dependensi demi lima bentuk menagih bita kepada setiap pengguna untuk
     * ikon yang tidak pernah ia lihat.
     *
     * Bukan Lucide maupun Heroicons. Keduanya pilihan bawaan hampir semua antarmuka
     * hasil AI, dan memakainya berarti mengulang tanda tangan yang sedang dihapus.
     */
    $bentuk = [
        'arrow-up' => '<path d="M205.66,117.66a8,8,0,0,1-11.32,0L136,59.31V216a8,8,0,0,1-16,0V59.31L61.66,117.66a8,8,0,0,1-11.32-11.32l72-72a8,8,0,0,1,11.32,0l72,72A8,8,0,0,1,205.66,117.66Z"/>',
        'arrow-down' => '<path d="M205.66,149.66l-72,72a8,8,0,0,1-11.32,0l-72-72a8,8,0,0,1,11.32-11.32L120,196.69V40a8,8,0,0,1,16,0V196.69l58.34-58.35a8,8,0,0,1,11.32,11.32Z"/>',
        'path' => '<path d="M216,40H160a8,8,0,0,0,0,16h36.69L152,100.69,131.31,80a16,16,0,0,0-22.62,0l-64,64a8,8,0,0,0,11.31,11.31L120,91.31,140.69,112a16,16,0,0,0,22.62,0L208,67.31V104a8,8,0,0,0,16,0V48A8,8,0,0,0,216,40Z"/>',
        'mountains' => '<path d="M248,208H231.4L172.32,49.51a16,16,0,0,0-30.07,0l-20.9,56.05-27.4-47.46a16,16,0,0,0-27.71,0L12.72,188a16,16,0,0,0,13.86,24H248a8,8,0,0,0,0-16Z"/>',
        'shield-check' => '<path d="M208,40H48A16,16,0,0,0,32,56v58.77c0,89.62,75.82,119.34,91,124.39a15.53,15.53,0,0,0,10,0c15.2-5.05,91-34.77,91-124.39V56A16,16,0,0,0,208,40Zm-32.4,64.16-56,56a8,8,0,0,1-11.32,0l-24-24a8,8,0,0,1,11.32-11.32L114,148.12l50.34-50.35a8,8,0,0,1,11.32,11.32Z"/>',
    ];

    $isi = $bentuk[$name] ?? null;
@endphp

@if ($isi)
    <svg viewBox="0 0 256 256" fill="currentColor" class="{{ $class }} inline-block shrink-0"
        @if ($label) role="img" @else aria-hidden="true" @endif>
        @if ($label)
            <title>{{ $label }}</title>
        @endif
        {!! $isi !!}
    </svg>
@endif
```

- [ ] **Langkah 4: Jalankan dan pastikan lulus**

Jalankan: `php artisan test tests/Feature/IconSystemTest.php`
Harapkan: LULUS, lima test.

- [ ] **Langkah 5: Pakai di tempat yang membawa arti**

Di `resources/views/components/ui/checkpoint-journey.blade.php`, pada baris yang menyebut
naik atau turun antarpos, sisipkan ikon arah sebelum teksnya:

```blade
                    <p class="text-secondary">
                        @if (str_contains($antara[$i], 'naik'))
                            <x-ui.icon name="arrow-up" class="h-3 w-3" />
                        @elseif (str_contains($antara[$i], 'turun'))
                            <x-ui.icon name="arrow-down" class="h-3 w-3" />
                        @endif
                        {{ $antara[$i] }}
                    </p>
```

Ikonnya `aria-hidden` karena teks di sebelahnya sudah menyebut arahnya.

- [ ] **Langkah 6: Jalankan, Pint, build, commit**

```bash
php artisan test
./vendor/bin/pint
npm run build
git add resources tests/Feature/IconSystemTest.php
git commit -m "feat: sistem ikon sebaris, hanya di tempat yang membawa arti"
```

---

## Tugas 5: Meta sosial dan ikon situs

**Berkas:**
- Ubah: `resources/views/layouts/app.blade.php`
- Ubah: `resources/views/public/trail.blade.php`
- Test: `tests/Feature/SocialMetaTest.php` (baru)

Nol `og:`, nol `twitter:`, dan tidak ada `rel="icon"` meskipun berkasnya ada. Halaman
jalur publik dibuat khusus untuk dibagikan, lalu setiap tautannya muncul sebagai URL
telanjang.

- [ ] **Langkah 1: Tulis test yang gagal**

Buat `tests/Feature/SocialMetaTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Mountain;
use App\Models\Trail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Meta sosial.
 *
 * Halaman jalur publik dibuat khusus untuk ditemukan dan dibagikan, lalu setiap
 * tautannya muncul sebagai URL telanjang tanpa judul maupun gambar. Cacat pertumbuhan
 * yang diperkenalkan bersama halamannya sendiri.
 */
class SocialMetaTest extends TestCase
{
    use RefreshDatabase;

    private function jalur(): Trail
    {
        return Trail::factory()->easy()->for(
            Mountain::factory()->create(['name' => 'Merbabu'])
        )->create(['name' => 'Jalur Selo']);
    }

    public function test_a_public_trail_page_carries_its_own_social_card(): void
    {
        $halaman = $this->get(route('public.trail', $this->jalur()));

        $halaman->assertSee('og:title', escape: false);
        $halaman->assertSee('og:description', escape: false);
        $halaman->assertSee('og:url', escape: false);
        $halaman->assertSee('og:image', escape: false);
        $halaman->assertSee('twitter:card', escape: false);
    }

    /**
     * Judul sosialnya menyebut jalur dan gunungnya, bukan nama aplikasi berulang-ulang.
     */
    public function test_the_social_title_names_the_trail_not_the_app(): void
    {
        $this->get(route('public.trail', $this->jalur()))
            ->assertSee('content="Jalur Selo', escape: false);
    }

    public function test_every_layout_declares_the_site_icon(): void
    {
        foreach (['layouts/app', 'public/trail'] as $berkas) {
            $isi = \Illuminate\Support\Facades\File::get(resource_path("views/{$berkas}.blade.php"));

            $this->assertStringContainsString('rel="icon"', $isi, "{$berkas} tidak menyatakan ikon situs.");
        }
    }
}
```

- [ ] **Langkah 2: Jalankan dan pastikan gagal**

Jalankan: `php artisan test tests/Feature/SocialMetaTest.php`
Harapkan: GAGAL, tiga test.

- [ ] **Langkah 3: Tambahkan ikon situs ke layout aplikasi**

Di `resources/views/layouts/app.blade.php`, tepat sesudah baris `<meta name="theme-color" ...>`:

```blade
        {{-- Berkasnya sudah ada sejak awal dan tidak pernah dinyatakan, sehingga
             peramban hanya menemukannya lewat konvensi dan peranti Apple tidak
             menemukannya sama sekali. --}}
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="apple-touch-icon" href="/icons/app-192.png">
```

- [ ] **Langkah 4: Tambahkan kartu sosial ke halaman publik**

Di `resources/views/public/trail.blade.php`, tepat sesudah baris `<link rel="canonical" ...>`:

```blade
        {{--
            Kartu sosial. Halaman ini dibuat untuk ditemukan dan dibagikan, dan tautan
            tanpa kartu muncul sebagai URL telanjang di setiap tempat ia ditempel.

            Gambarnya ikon aplikasi, bukan foto jalur: foto jalur berasal dari laporan
            komunitas yang membawa nama pelapornya, dan halaman publik tidak pernah
            menerbitkan data pribadi siapa pun.
        --}}
        <link rel="icon" href="/favicon.ico" sizes="any">
        <meta property="og:type" content="article">
        <meta property="og:title" content="{{ $trail->name }} - {{ $trail->mountain->name }}">
        <meta property="og:description" content="{{ \Illuminate\Support\Str::limit($ringkas, 155) }}">
        <meta property="og:url" content="{{ route('public.trail', $trail) }}">
        <meta property="og:image" content="{{ url('/icons/app-512.png') }}">
        <meta property="og:locale" content="id_ID">
        <meta name="twitter:card" content="summary">
```

- [ ] **Langkah 5: Jalankan, Pint, build, commit**

```bash
php artisan test
./vendor/bin/pint
npm run build
git add resources tests/Feature/SocialMetaTest.php
git commit -m "feat: kartu sosial halaman publik dan ikon situs"
```

---

## Tugas 6: Lebar baca dan penyeimbang judul

**Berkas:**
- Ubah: `resources/css/app.css`
- Test: `tests/Feature/ReadingMeasureTest.php` (baru)

Satu `max-w-prose` di seluruh aplikasi dan nol `text-wrap: balance`. Pada layar lebar,
kalimat membentang penuh dan mata kehilangan awal baris berikutnya; judul dua baris
menyisakan satu kata sendirian.

Dipasang di lapisan base, bukan sebagai kelas baru. Kelas `.prosa` yang harus diingat
setiap penulis view adalah kelas yang akan terlupakan di halaman berikutnya, dan CSS yang
tidak pernah dipakai siapa pun adalah bita mati — persis cacat yang dijaga guard lain.

- [ ] **Langkah 1: Tulis test yang gagal**

Buat `tests/Feature/ReadingMeasureTest.php`:

```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Lebar baca dan pemenggalan.
 *
 * Mata kehilangan awal baris berikutnya ketika satu baris melebihi sekitar 75 karakter,
 * dan judul yang menyisakan satu kata sendirian di baris kedua terbaca sebagai kelalaian
 * tata letak.
 */
class ReadingMeasureTest extends TestCase
{
    private function css(): string
    {
        static $isi = null;

        return $isi ??= File::get(resource_path('css/app.css'));
    }

    public function test_headings_are_balanced_across_their_lines(): void
    {
        $this->assertMatchesRegularExpression(
            '/h1[^{]*\{[^}]*text-wrap:\s*balance/s',
            $this->css(),
            'Judul harus diseimbangkan, bukan dipenggal seadanya.'
        );
    }

    /**
     * Paragraf memakai pretty, bukan balance: balance menyeimbangkan seluruh blok dan
     * berhenti bekerja setelah beberapa baris, sedangkan pretty hanya mencegah baris
     * terakhir tersisa satu kata. Itu yang dibutuhkan prosa.
     */
    public function test_paragraphs_never_end_on_an_orphan(): void
    {
        $this->assertMatchesRegularExpression(
            '/\bp\s*\{[^}]*text-wrap:\s*pretty/s',
            $this->css(),
            'Paragraf harus memakai text-wrap: pretty.'
        );
    }

    /**
     * Pembatas lebar baca dipasang di komponen bersama, bukan diingat satu per satu.
     *
     * Sebelum ini hanya ada satu max-w-prose di seluruh aplikasi, dan satu-satunya
     * alasan angkanya satu adalah karena tidak ada tempat yang memasangnya untuk semua.
     */
    public function test_the_shared_components_bound_their_prose(): void
    {
        foreach (['page-header', 'card'] as $komponen) {
            $this->assertStringContainsString(
                'max-w-prose',
                File::get(resource_path("views/components/ui/{$komponen}.blade.php")),
                "Komponen {$komponen} membiarkan teksnya membentang penuh."
            );
        }
    }
}
```

- [ ] **Langkah 2: Jalankan dan pastikan gagal**

Jalankan: `php artisan test tests/Feature/ReadingMeasureTest.php`
Harapkan: GAGAL pada dua test pertama. Yang ketiga sudah lulus karena Tugas 3 memasang
`max-w-prose` di kedua komponen; ia ada di sini untuk menahannya tetap terpasang, bukan
untuk memperkenalkannya.

- [ ] **Langkah 3: Tambahkan aturannya**

Di `resources/css/app.css`, di dalam blok `h1` yang sudah ada, tambahkan satu baris:

```css
        /* Judul dua baris kerap menyisakan satu kata sendirian di baris kedua, dan itu
           terbaca sebagai kelalaian tata letak, bukan sebagai pilihan. */
        text-wrap: balance;
```

Lalu tepat sesudah blok `h1`:

```css
    /*
     * Paragraf memakai pretty, bukan balance.
     *
     * balance menyeimbangkan seluruh blok dan peramban berhenti menerapkannya setelah
     * beberapa baris, jadi ia pantas untuk judul dan sia-sia untuk prosa. pretty hanya
     * menjaga baris terakhir tidak tersisa satu kata, dan itu yang dibutuhkan paragraf.
     *
     * Di lapisan base, bukan sebagai kelas yang harus diingat: kelas yang harus diingat
     * akan terlupakan di halaman berikutnya.
     */
    p {
        text-wrap: pretty;
    }
```

- [ ] **Langkah 4: Jalankan, Pint, build, commit**

```bash
php artisan test
./vendor/bin/pint
npm run build
git add resources/css/app.css tests/Feature/ReadingMeasureTest.php
git commit -m "feat: judul diseimbangkan dan paragraf tidak berakhir satu kata"
```

---

## Tugas 7: Bentuk saat memuat

**Berkas:**
- Buat: `resources/views/components/ui/skeleton.blade.php`
- Ubah: `resources/views/livewire/recommendations/recommendation-results.blade.php`
- Test: `tests/Feature/SkeletonTest.php` (baru)

Empat `wire:loading` di seluruh aplikasi, tanpa satu pun skeleton. Halaman yang
menjalankan mesin rekomendasi diam sampai isinya tiba.

- [ ] **Langkah 1: Tulis test yang gagal**

Buat `tests/Feature/SkeletonTest.php`:

```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Bentuk saat memuat.
 *
 * Bentuk yang sudah terlihat memberi tahu apa yang sedang ditunggu, sedangkan pemutar
 * lingkaran hanya memberi tahu bahwa sesuatu sedang terjadi. Perbedaannya paling terasa
 * di jaringan lambat, dan jaringan lambat adalah keadaan normal bagi pengguna ini.
 */
class SkeletonTest extends TestCase
{
    public function test_a_skeleton_takes_the_shape_of_what_is_coming(): void
    {
        $html = Blade::render('<x-ui.skeleton rows="3" />');

        $this->assertSame(3, substr_count($html, 'data-skeleton-row'));
    }

    /**
     * Pembaca layar tidak boleh membacakan kotak kosong. Yang diumumkan keadaannya,
     * bukan bentuknya.
     */
    public function test_the_skeleton_announces_state_not_shape(): void
    {
        $html = Blade::render('<x-ui.skeleton rows="2" />');

        $this->assertStringContainsString('aria-hidden="true"', $html);
        $this->assertStringContainsString('aria-live="polite"', $html);
        $this->assertStringContainsString('Memuat', $html);
    }

    /**
     * Denyutnya berhenti ketika pengguna meminta gerak dikurangi. Aturan menyeluruh
     * sudah ada di stylesheet, dan test ini menjaganya tetap berlaku di sini.
     */
    public function test_the_pulse_respects_reduced_motion(): void
    {
        $css = \Illuminate\Support\Facades\File::get(resource_path('css/app.css'));

        $this->assertMatchesRegularExpression('/prefers-reduced-motion:\s*reduce/', $css);
    }
}
```

- [ ] **Langkah 2: Jalankan dan pastikan gagal**

Jalankan: `php artisan test tests/Feature/SkeletonTest.php`
Harapkan: GAGAL, tiga test.

- [ ] **Langkah 3: Bangun komponennya**

Buat `resources/views/components/ui/skeleton.blade.php`:

```blade
@props(['rows' => 3])

{{--
    Bentuk yang sudah terlihat memberi tahu apa yang sedang ditunggu; pemutar lingkaran
    hanya memberi tahu bahwa sesuatu sedang terjadi.

    Bentuknya disembunyikan dari pembaca layar dan keadaannya diumumkan sebagai teks:
    membacakan lima kotak kosong memperpanjang tanpa menyampaikan apa pun.
--}}
<div {{ $attributes }}>
    <div aria-hidden="true" class="space-y-3">
        @for ($i = 0; $i < (int) $rows; $i++)
            <div data-skeleton-row class="h-4 animate-pulse rounded bg-surface-sunken"
                style="width: {{ [100, 85, 92][$i % 3] }}%"></div>
        @endfor
    </div>

    <p aria-live="polite" class="sr-only">Memuat isi halaman.</p>
</div>
```

- [ ] **Langkah 4: Pakai di halaman hasil rekomendasi**

Di `resources/views/livewire/recommendations/recommendation-results.blade.php`, tepat
sebelum `<ul class="space-y-4">`:

```blade
        <x-ui.skeleton :rows="4" wire:loading wire:target="tampilkanLagi" class="mb-4" />
```

- [ ] **Langkah 5: Jalankan, Pint, build, commit**

```bash
php artisan test
./vendor/bin/pint
npm run build
git add resources tests/Feature/SkeletonTest.php
git commit -m "feat: skeleton mengikuti bentuk isinya, bukan pemutar lingkaran"
```

---

## Tinjauan mandiri

**1. Cakupan spec.** Bagian 2 arsitektur token → Tugas 1. Bagian 3 peta migrasi → Tugas 2.
Bagian 4 urutan → Tugas 1 sampai 3. Bagian 5 lima celah audit → Tugas 4 sampai 7, satu
tugas per celah kecuali 5.3 dan 5.4 yang digabung ke Tugas 6 karena keduanya satu berkas
dan satu siklus test. Bagian 6 penolakan tidak menghasilkan tugas, sesuai maksudnya.
Bagian 8 ukuran keberhasilan: nomor 1 dijaga
`test_no_view_reaches_past_the_semantic_layer`, nomor 2 oleh test yang sama, nomor 3 oleh
sembilan pasangan baru di `ColourContrastTest`, nomor 4 oleh `SocialMetaTest`, nomor 5
oleh suite penuh di akhir setiap tugas.

**2. Pemindaian placeholder.** Tidak ada TBD, tidak ada "tangani kasus tepi", tidak ada
"serupa Tugas N". Setiap langkah kode memuat kode yang sebenarnya, termasuk path SVG
Phosphor yang ditanam utuh.

**3. Konsistensi tipe.** Tujuh nama token didefinisikan di Tugas 1 dan dipakai identik di
Tugas 2 (`text-primary`, `text-secondary`, `text-muted`, `border-subtle`,
`bg-surface-sunken`), Tugas 3 (`bg-surface`, `border-subtle`, `text-primary`,
`text-secondary`, `shadow-overlay`), dan Tugas 7 (`bg-surface-sunken`). Komponen
`<x-ui.icon>` dideklarasikan di Tugas 4 dengan prop `name`, `label`, `class` dan dipakai
dengan prop yang sama di langkahnya sendiri. Komponen `<x-ui.skeleton>` dideklarasikan di
Tugas 7 dengan prop `rows` dan dipakai dengan prop itu.

**4. Urutan ketergantungan.** Tugas 2 membutuhkan Tugas 1. Tugas 3 membutuhkan Tugas 2,
karena mengubah nilai sebelum view memakainya tidak akan terlihat. Tugas 6 langkah 1
memeriksa `max-w-prose` yang dipasang Tugas 3, jadi ia mengikuti Tugas 3. Tugas 4, 5, dan
7 saling bebas dan dapat dikerjakan dalam urutan apa pun sesudahnya.

**5. Yang diperiksa terhadap kode, bukan diingat.** Sebelum rencana ini disimpan, setiap
angka dan setiap jangkar di dalamnya diukur: 48 `shadow-sm` ditambah `shadow-lg` di
dropdown, `shadow-xl` di modal, `shadow-md` di layout tamu; `public/favicon.ico`,
`icons/app-192.png`, dan `icons/app-512.png` ada; `public/trail.blade.php` sudah memuat
`$ringkas` dan `rel="canonical"` pada rute bernama `public.trail`;
`recommendation-results.blade.php` memuat `<ul class="space-y-4">` dan komponennya
benar-benar punya `tampilkanLagi()`; `checkpoint-journey.blade.php` memuat `$antara[$i]`.

Dua hal berubah karena pengukuran itu. Rencana semula menyebut `shadow-xl` ada di
dropdown — ia ada di modal. Dan nilai `--text-muted` di spec, yang hanya dihitung terhadap
kanvas, gagal AA di atas `--surface-sunken` pada 4,40:1; nilai di Tugas 3 digelapkan satu
tingkat dan ketiga permukaannya ikut dihitung.
