# Fase 4: Sistem Visual — Rencana Implementasi

> **Untuk pengerjaan agentik:** SUB-SKILL WAJIB: pakai superpowers:subagent-driven-development
> (disarankan) atau superpowers:executing-plans untuk mengerjakannya tugas demi tugas.
> Langkahnya memakai checkbox (`- [ ]`) untuk penanda.

**Tujuan:** Menghilangkan sebab aplikasi ini terbaca seperti CRUD, yaitu permukaan dan
tipografi yang seragam, tanpa menyentuh satu pun perilakunya.

**Arsitektur:** Seluruhnya perubahan pada design token dan tiga komponen UI bersama.
Ratusan berkas view tidak disentuh: mereka memakai `<x-ui.card>`, `<x-ui.page-header>`,
dan kelas Tailwind yang membaca token, sehingga mengubah tokennya mengubah seluruh
aplikasi. Tugas yang menyentuh banyak berkas hanya satu, yaitu pencabutan bayangan, dan
itu penggantian mekanis yang dijaga sapuan.

**Tumpukan:** Tailwind 3 dengan design token CSS custom property, Blade, PHPUnit 12, Pint.

**Spec:** `docs/superpowers/specs/2026-09-20-pengalaman-pendaki-design.md`

## Diagnosis terukur yang mendasari rencana ini

Diukur pada kode, bukan ditaksir:

| Yang diukur | Nilai sekarang |
|---|---|
| Pemakaian `text-sm` + `text-xs` | 363 dari 426 (85%) |
| Berkas memakai serif atau mono | 1 |
| Kanvas aplikasi | `bg-gray-100`, abu dingin |
| `shadow-sm` | 48 pemakaian di 26 berkas |
| Radius | 71 `rounded-md`, 29 `rounded-lg`, 19 `rounded-control` |
| Kartu | `rounded-lg bg-white p-5 shadow-sm` |
| Judul halaman | `text-2xl font-semibold` |

Huruf sudah diganti pada commit `06f7aae`. Sisanya permukaan, skala, dan kepadatan.

## Batasan menyeluruh

Disalin dari aturan yang sudah berlaku di proyek ini. Setiap tugas tunduk padanya tanpa
perlu diulang:

- **WCAG 2.2 AA**: teks normal 4.5:1, komponen non-teks 3:1. Dihitung `ColourContrastTest`,
  bukan ditaksir mata.
- **Warna semantik tidak didesaturasi.** Status resmi, peringatan, dan bahaya membawa arti
  keselamatan. Yang boleh ditenangkan hanya warna dekoratif.
- **§92**: keterangan resmi dan masukan komunitas tetap terbedakan secara visual.
- **§88 mobile-first**: tidak ada yang meluber pada lebar 400px.
- **Anggaran halaman 120 kB** dijaga `ResultListBudgetTest`; anggaran query dijaga
  `PageQueryBudgetTest` dan `NewPageQueryBudgetTest`.
- **Tidak ada perilaku yang berubah.** Seluruh 746 test harus tetap hijau tanpa disunting,
  kecuali test yang memang menguji nilai visual yang sedang diganti.
- Setiap tugas: test gagal dulu, perbaikan, Pint, suite penuh, commit.

## Struktur berkas

| Berkas | Tanggung jawab | Tugas |
|---|---|---|
| `resources/css/app.css` | Satu-satunya tempat token didefinisikan | 1, 2 |
| `tailwind.config.js` | Memetakan token ke kelas Tailwind | 1 |
| `resources/views/layouts/app.blade.php` | Kanvas aplikasi | 1 |
| `resources/views/components/ui/card.blade.php` | Permukaan bersama | 2 |
| `resources/views/components/ui/page-header.blade.php` | Skala judul | 3 |
| `resources/views/components/ui/empty-state.blade.php` | Skala teks sekunder | 3 |
| 26 berkas view berbayang | Pencabutan `shadow-sm` | 2 |
| `tests/Feature/SurfaceSystemTest.php` | **Baru.** Menjaga token dan sapuan bayangan | 1, 2 |
| `tests/Feature/TypeScaleTest.php` | **Baru.** Menjaga skala judul dan teks baca | 3 |

---

## Tugas 1: Kanvas hangat dan token permukaan

**Berkas:**
- Ubah: `resources/css/app.css` (blok `:root`, sesudah baris `--radius-control`)
- Ubah: `tailwind.config.js` (blok `colors`, sesudah `control`)
- Ubah: `resources/views/layouts/app.blade.php:33`
- Test: `tests/Feature/SurfaceSystemTest.php` (baru)

**Antarmuka yang dihasilkan** (dipakai Tugas 2 dan 3):
- Kelas Tailwind `bg-canvas` → `rgb(var(--canvas))`
- Kelas Tailwind `bg-surface` → `rgb(var(--surface))`
- Kelas Tailwind `border-hairline` → `rgb(var(--hairline))`

**Mengapa hangat.** Abu dingin `#F3F4F6` adalah warna bawaan Tailwind dan warna yang sama
dipakai puluhan ribu dasbor. Kanvas hangat mendekati kertas membuat permukaan putih di
atasnya terbaca sebagai lembaran, bukan sebagai kotak di atas abu.

- [~] **Langkah 1: Tulis test yang gagal**

Buat `tests/Feature/SurfaceSystemTest.php`:

```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Sistem permukaan.
 *
 * Kanvas bawaan Tailwind dipakai puluhan ribu dasbor, dan bayangan pada setiap kartu
 * membuat halaman terbaca sebagai tumpukan kotak alih-alih satu lembar dokumen.
 */
class SurfaceSystemTest extends TestCase
{
    private function css(): string
    {
        static $isi = null;

        return $isi ??= File::get(resource_path('css/app.css'));
    }

    public function test_the_canvas_is_a_token_not_a_tailwind_default(): void
    {
        $this->assertMatchesRegularExpression('/--canvas:\s*\d+\s+\d+\s+\d+;/', $this->css());
        $this->assertMatchesRegularExpression('/--surface:\s*\d+\s+\d+\s+\d+;/', $this->css());
        $this->assertMatchesRegularExpression('/--hairline:\s*\d+\s+\d+\s+\d+;/', $this->css());
    }

    public function test_the_layout_uses_the_canvas_token(): void
    {
        $layout = File::get(resource_path('views/layouts/app.blade.php'));

        $this->assertStringContainsString('bg-canvas', $layout);
        $this->assertStringNotContainsString('bg-gray-100', $layout);
    }

    public function test_tailwind_exposes_the_surface_tokens(): void
    {
        $config = File::get(base_path('tailwind.config.js'));

        foreach (['canvas', 'surface', 'hairline'] as $nama) {
            $this->assertStringContainsString($nama, $config);
        }
    }
}
```

- [~] **Langkah 2: Jalankan dan pastikan gagal**

Jalankan: `php artisan test tests/Feature/SurfaceSystemTest.php`
Harapkan: GAGAL, tiga test, dengan pesan token tidak ditemukan.

- [~] **Langkah 3: Tambahkan token**

Di `resources/css/app.css`, tepat sesudah baris `--radius-control: 0.375rem;`:

```css
        /*
         * Permukaan.
         *
         * Abu dingin bawaan Tailwind dipakai puluhan ribu dasbor. Kanvas hangat
         * mendekati kertas membuat lembaran putih di atasnya terbaca sebagai lembaran,
         * bukan sebagai kotak di atas abu.
         *
         * hairline menggantikan bayangan sebagai pemisah. Rasionya terhadap surface
         * 1.31:1, dan itu memang tidak memenuhi 1.4.11 karena garis ini dekoratif:
         * pemisah kartu bukan kontrol interaktif. Batas kontrol tetap --control-border.
         */
        --canvas: 250 250 248;
        --surface: 255 255 255;
        --hairline: 233 232 228;
```

- [~] **Langkah 4: Petakan ke Tailwind**

Di `tailwind.config.js`, di dalam `colors`, tepat sesudah baris `control: token('control-border'),`:

```js
                canvas: token('canvas'),
                surface: token('surface'),
                hairline: token('hairline'),
```

- [~] **Langkah 5: Pakai di layout**

Di `resources/views/layouts/app.blade.php` baris 33, ganti:

```blade
        <div class="min-h-screen bg-gray-100">
```

menjadi:

```blade
        <div class="min-h-screen bg-canvas">
```

- [~] **Langkah 6: Jalankan dan pastikan lulus**

Jalankan: `php artisan test tests/Feature/SurfaceSystemTest.php`
Harapkan: LULUS, tiga test.

- [~] **Langkah 7: Suite penuh, Pint, build**

```bash
./vendor/bin/pint
php artisan test
npm run build
```

Harapkan: 749 test hijau, Pint bersih, build selesai.

- [~] **Langkah 8: Commit**

```bash
git add resources/css/app.css tailwind.config.js resources/views/layouts/app.blade.php tests/Feature/SurfaceSystemTest.php
git commit -m "feat: kanvas hangat dan token permukaan"
```

---

## Tugas 2: Bayangan diganti hairline

**Berkas:**
- Ubah: `resources/views/components/ui/card.blade.php:3`
- Ubah: 26 berkas view yang memuat `shadow-sm`
- Ubah: `tests/Feature/SurfaceSystemTest.php` (tambah satu test)

**Konsumsi:** `bg-surface` dan `border-hairline` dari Tugas 1.

**Mengapa.** Empat puluh delapan bayangan membuat setiap blok tampak melayang, dan
halaman yang seluruh isinya melayang tidak punya bidang dasar. Satu garis rambut memisah
dengan tegas tanpa menyatakan ketinggian yang tidak ada artinya.

- [~] **Langkah 1: Tulis test yang gagal**

Tambahkan ke `tests/Feature/SurfaceSystemTest.php`:

```php
    /**
     * Disapu, bukan didaftar per berkas. Daftar yang ditulis tangan berhenti lengkap
     * pada hari ia ditulis, dan kelas cacat itu sudah muncul tiga kali di proyek ini.
     */
    public function test_no_view_uses_a_drop_shadow_as_a_separator(): void
    {
        $pelanggar = [];

        foreach (File::allFiles(resource_path('views')) as $berkas) {
            $isi = preg_replace('/\{\{--.*?--\}\}/s', ' ', $berkas->getContents());

            if (preg_match('/\bshadow-(sm|md|lg|xl)\b/', $isi)) {
                $pelanggar[] = $berkas->getRelativePathname();
            }
        }

        $this->assertSame([], $pelanggar, 'Bayangan dipakai sebagai pemisah di: '.implode(', ', $pelanggar));
    }
```

- [~] **Langkah 2: Jalankan dan pastikan gagal**

Jalankan: `php artisan test tests/Feature/SurfaceSystemTest.php --filter=drop_shadow`
Harapkan: GAGAL, menyebut 26 berkas.

- [~] **Langkah 3: Ubah komponen kartu**

Di `resources/views/components/ui/card.blade.php` baris 3, ganti:

```blade
<section {{ $attributes->merge(['class' => 'rounded-lg bg-white p-5 shadow-sm']) }}>
```

menjadi:

```blade
{{--
    Satu garis rambut, bukan bayangan. Empat puluh delapan bayangan membuat setiap blok
    tampak melayang, dan halaman yang seluruh isinya melayang tidak punya bidang dasar.

    Padding dinaikkan dari p-5: ruang di dalam kartu yang sempit membuat isinya terbaca
    padat berapa pun ukuran hurufnya.
--}}
<section {{ $attributes->merge(['class' => 'rounded-lg border border-hairline bg-surface p-6']) }}>
```

- [~] **Langkah 4: Cabut bayangan dari 26 berkas**

Jalankan penggantian mekanis, lalu periksa hasilnya:

```bash
grep -rl 'shadow-sm' resources/views --include=*.blade.php \
  | xargs sed -i 's/ shadow-sm//g; s/shadow-sm //g'
grep -rn 'shadow-' resources/views --include=*.blade.php
```

Harapkan pada perintah kedua: hanya `shadow-xl` pada `components/dropdown.blade.php` dan
`shadow-md` bila ada. Keduanya ikut dicabut pada langkah berikutnya.

- [~] **Langkah 5: Ganti bayangan dropdown dengan hairline**

Dropdown melayang di atas isi halaman dan memang butuh pemisah lebih kuat daripada kartu.
Di `resources/views/components/dropdown.blade.php`, ganti `shadow-xl` menjadi:

```blade
ring-1 ring-black/5
```

- [~] **Langkah 6: Jalankan dan pastikan lulus**

Jalankan: `php artisan test tests/Feature/SurfaceSystemTest.php`
Harapkan: LULUS, empat test.

- [~] **Langkah 7: Suite penuh, Pint, build**

```bash
./vendor/bin/pint
php artisan test
npm run build
```

Harapkan: seluruh test hijau. Bila `ColourContrastTest` gagal, hairline terlalu terang
terhadap surface; itu bukan pelanggaran karena garis dekoratif tidak diatur 1.4.11, dan
testnya memang tidak memeriksanya. Kegagalan lain berarti ada kelas lain yang ikut
tercabut `sed`, dan harus dikembalikan satu per satu.

- [~] **Langkah 8: Commit**

```bash
git add resources/views tests/Feature/SurfaceSystemTest.php
git commit -m "feat: bayangan diganti garis rambut"
```

---

## Tugas 3: Skala tipe diterapkan

**Berkas:**
- Ubah: `resources/views/components/ui/page-header.blade.php`
- Ubah: `resources/views/components/ui/card.blade.php`
- Ubah: `resources/views/components/ui/empty-state.blade.php:16`
- Test: `tests/Feature/TypeScaleTest.php` (baru)

**Konsumsi:** keluarga `font-serif` dan `font-mono` dari commit `06f7aae`.

**Mengapa.** Judul halaman `text-2xl` hanya dua tingkat di atas badan teks, sehingga
halaman tidak punya titik masuk. Deskripsi `text-sm` memaksa kalimat penjelas dibaca pada
ukuran terkecil, padahal justru kalimat itu yang menentukan apakah pembaca meneruskan.

- [~] **Langkah 1: Tulis test yang gagal**

Buat `tests/Feature/TypeScaleTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Skala tipe.
 *
 * Judul halaman sebelumnya text-2xl, hanya dua tingkat di atas badan teks, sehingga
 * halaman tidak punya titik masuk. Deskripsi memakai text-sm, memaksa kalimat penjelas
 * dibaca pada ukuran terkecil padahal justru kalimat itu yang menentukan apakah pembaca
 * meneruskan.
 */
class TypeScaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_page_title_carries_real_weight(): void
    {
        $isi = File::get(resource_path('views/components/ui/page-header.blade.php'));

        $this->assertMatchesRegularExpression('/text-3xl|text-4xl/', $isi);
        $this->assertStringNotContainsString('text-2xl', $isi);
    }

    public function test_the_page_description_is_read_at_reading_size(): void
    {
        $isi = File::get(resource_path('views/components/ui/page-header.blade.php'));

        $this->assertMatchesRegularExpression('/description.*?text-base/s', $isi);
    }

    /**
     * Judul kartu tetap lebih kecil daripada judul halaman. Hierarki yang dua-duanya
     * besar sama saja dengan hierarki yang dua-duanya kecil.
     */
    public function test_a_card_title_stays_below_the_page_title(): void
    {
        $kartu = File::get(resource_path('views/components/ui/card.blade.php'));

        $this->assertStringContainsString('text-lg', $kartu);
        $this->assertStringNotContainsString('text-3xl', $kartu);
    }

    public function test_the_rendered_page_actually_shows_the_larger_title(): void
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => 'INTERMEDIATE', 'completed_at' => now()]);

        $this->actingAs($user)
            ->get(route('progress'))
            ->assertSee('text-3xl', escape: false);
    }
}
```

- [~] **Langkah 2: Jalankan dan pastikan gagal**

Jalankan: `php artisan test tests/Feature/TypeScaleTest.php`
Harapkan: GAGAL, empat test.

- [~] **Langkah 3: Naikkan skala judul halaman**

Ganti seluruh isi `resources/views/components/ui/page-header.blade.php`:

```blade
@props(['title', 'description' => null])

{{--
    Judul halaman memakai serif lewat lapisan base, dan ukurannya dinaikkan dua tingkat.
    text-2xl hanya dua tingkat di atas badan teks, sehingga halaman tidak punya titik
    masuk dan mata tidak tahu harus mendarat di mana.

    Deskripsi naik ke text-base: justru kalimat inilah yang menentukan apakah pembaca
    meneruskan, dan memaksanya dibaca pada ukuran terkecil adalah kebalikan dari yang
    dibutuhkan.
--}}
<div class="mb-8">
    <h1 class="text-3xl text-gray-900 sm:text-4xl">{{ $title }}</h1>

    @if ($description)
        <p class="mt-2 max-w-prose text-base text-gray-600">{{ $description }}</p>
    @endif

    {{ $slot }}
</div>
```

- [~] **Langkah 4: Naikkan judul kartu**

Di `resources/views/components/ui/card.blade.php`, ganti baris judul dan subjudul:

```blade
    @if ($title)
        <h2 class="text-lg font-semibold text-gray-900">{{ $title }}</h2>
    @endif
    @if ($subtitle)
        <p class="mt-1 text-base text-gray-600">{{ $subtitle }}</p>
    @endif
```

- [~] **Langkah 5: Naikkan deskripsi keadaan kosong**

Di `resources/views/components/ui/empty-state.blade.php` baris 16, ganti `text-sm`
menjadi `text-base`.

- [~] **Langkah 6: Jalankan dan pastikan lulus**

Jalankan: `php artisan test tests/Feature/TypeScaleTest.php`
Harapkan: LULUS, empat test.

- [~] **Langkah 7: Suite penuh, Pint, build**

```bash
./vendor/bin/pint
php artisan test
npm run build
```

Harapkan: seluruh test hijau. `LandingPageTest` dan test yang mengasersi teks judul tetap
lulus karena yang berubah ukurannya, bukan isinya.

- [~] **Langkah 8: Commit**

```bash
git add resources/views/components/ui tests/Feature/TypeScaleTest.php
git commit -m "feat: skala tipe diterapkan pada judul dan teks penjelas"
```

---

## Tinjauan mandiri

**1. Cakupan.** Diagnosis terukur memuat tujuh baris. Huruf sudah selesai di `06f7aae`;
kanvas, bayangan, kartu, judul halaman, dan ukuran teks tercakup Tugas 1 sampai 3. Radius
sengaja tidak diseragamkan: `rounded-md` pada kontrol dan `rounded-lg` pada kartu adalah
perbedaan yang bermakna, dan menyeragamkannya menghapus perbedaan itu tanpa imbalan.

**2. Pemindaian placeholder.** Tidak ada TBD, tidak ada "tangani kasus tepi", tidak ada
"serupa Tugas N". Setiap langkah kode memuat kode yang sebenarnya.

**3. Konsistensi tipe.** Tiga nama token `canvas`, `surface`, `hairline` dipakai identik
di Tugas 1 (definisi), Tugas 2 (`bg-surface`, `border-hairline`), dan struktur berkas.
Kelas `font-serif` dan `font-mono` merujuk keluarga yang sudah ada di `tailwind.config.js`.

**Yang tidak dikerjakan rencana ini dan alasannya.** Bento grid, kartu bertingkat
double-bezel, dan animasi masuk bertahap disebut skill `high-end-visual-design`, dan
ketiganya ditolak di sini: skill itu menyasar mode Persuade, yaitu halaman yang tugasnya
membujuk, sedangkan seluruh aplikasi ini mode Operate. Pada mode Operate, kemudahan
memindai dan ekspektasi native mengalahkan ekspresi, dan animasi masuk pada halaman
perencanaan keselamatan menunda isi demi hiasan.

---

## Hasil

**Rencana ini tidak dijalankan.** Isinya diserap
`docs/superpowers/plans/2026-09-20-sistem-desain.md`, yang menggantikannya seluruhnya.

Kotak centangnya ditandai `[~]`, bukan `[x]`: langkahnya memang tidak pernah dikerjakan
dalam bentuk ini, dan menandainya selesai akan menyatakan sesuatu yang tidak terjadi.
