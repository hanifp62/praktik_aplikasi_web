# Mode Gelap dan Adaptivitas Perangkat (Mobile-First) untuk Laravel 13 + Livewire 3 + Tailwind — Catatan Riset per 23 September 2026

Label bukti yang dipakai: **[FAKTA-LOKAL]** = diverifikasi langsung di repo/runtime pada sesi ini (path dan baris disebut); **[FAKTA-WEB]** = dari sumber yang dikutip; **[DIHITUNG]** = dihitung di sesi ini dengan rumus WCAG; **[INFERENSI]**; **[REKOMENDASI]**; **[UNVERIFIED]**; **[OPEN ISSUE]**; **[PERLU PERSETUJUAN PEMILIK]**. Sumber bertanda "(tidak dibuka di sesi ini)" dikutip dari URL kanonik tanpa dibuka ulang.

Catatan tata kelola yang membingkai semua rekomendasi: `DESIGN.md:114-115` menyatakan *"Mode gelap. Belum diputuskan pemilik produk. Ia bukan pekerjaan yang ditunda melainkan keputusan yang belum diambil"* [FAKTA-LOKAL]. Karena itu seluruh arsitektur di bawah adalah **opsi untuk keputusan pemilik**, bukan izin implementasi. Perubahan versi framework (R-020), dependensi (aturan Boost: "Do not change the application's dependencies without approval"), dan skema basis data (R-021) memerlukan persetujuan eksplisit.

Versi terpasang yang diverifikasi [FAKTA-LOKAL, `vendor/composer/installed.json`, `node_modules/*/package.json`]: laravel/framework v13.31.0, livewire/livewire v3.8.9, livewire/volt v1.11.2, phpunit/phpunit 12.5.35 (Pest dan Dusk tidak terpasang), tailwindcss 3.4.19, @tailwindcss/vite 4.3.3, @tailwindcss/forms 0.5.11, vite 8.3.0, postcss 8.5.28, autoprefixer 10.6.1, maplibre-gl 4.7.1. PHP 8.5.10 diambil dari brief dan tidak dijalankan ulang di sesi ini.

---

## 1. Apakah kombinasi konfigurasi Tailwind v3 dengan `@tailwindcss/vite` v4 bermasalah, dan jalur mana yang disarankan pada 2026?

### Takeaway
Saat ini **tidak ada konflik aktif**: yang benar-benar mengompilasi CSS adalah Tailwind **3.4.19 lewat PostCSS**, sedangkan `@tailwindcss/vite` 4.3.3 terpasang tetapi **tidak didaftarkan** di `vite.config.js`. Ia dependensi mati yang menjadi jebakan laten. Opsi perubahan paling kecil adalah tetap di v3.4 dan menambah token gelap lewat CSS variables tanpa mengubah versi. Migrasi ke v4 adalah keputusan terpisah dengan risiko regresi visual yang nyata dan memerlukan persetujuan pemilik.

### Cited Findings
- [FAKTA-LOKAL] `package.json` memuat `"tailwindcss": "^3.1.0"`, `"@tailwindcss/vite": "^4.0.0"`, `"vite": "^8.0.0"`, `"@tailwindcss/forms": "^0.5.2"`, `"postcss"`, `"autoprefixer"`. `vite.config.js` hanya memuat plugin `laravel-vite-plugin`, tanpa `tailwindcss()`. `postcss.config.js` memuat `tailwindcss: {}` dan `autoprefixer: {}`. `resources/css/app.css:2-4` memakai direktif v3 `@tailwind base; @tailwind components; @tailwind utilities;`. `@tailwindcss/vite` membawa salinan Tailwind v4 sendiri (`node_modules/@tailwindcss/vite/node_modules/tailwindcss` 4.3.3, `@tailwindcss/node` 4.3.3, `@tailwindcss/oxide`). Build terakhir (`public/build/manifest.json`, 2026-09-23 14:42) sukses dengan jalur v3.
- [FAKTA-LOKAL] `tailwind.config.js` **tidak** mendefinisikan `darkMode`, sehingga v3 memakai default `media` (mengikuti `prefers-color-scheme`). Satu-satunya pemakaian `dark:` di view adalah `resources/views/livewire/welcome/navigation.blade.php:5,12,20` (`dark:text-white`, `dark:hover:text-white/80`, `dark:focus-visible:ring-white`), sisa scaffolding Breeze yang tidak dirujuk dari view atau route mana pun (grep `welcome.navigation|livewire:welcome` kosong). Saat ini dorman. Bila suatu hari dirender di perangkat ber-OS gelap, hasilnya teks putih di atas kanvas terang.
- [FAKTA-WEB] v3: strategi default adalah `prefers-color-scheme`. Strategi `selector` diperkenalkan di **v3.4.1**, menggantikan strategi lama `class`. Selektor kustom ditulis `darkMode: ['selector', '[data-mode="dark"]']`, dan Tailwind membungkus selektor kustom dengan `:where()` supaya spesifisitas tetap — [Tailwind v3 Dark Mode](https://v3.tailwindcss.com/docs/dark-mode)
- [FAKTA-WEB] v4: default juga `prefers-color-scheme`. Toggle manual ditulis `@custom-variant dark (&:where(.dark, .dark *));` atau berbasis atribut `@custom-variant dark (&:where([data-theme=dark], [data-theme=dark] *));` — [Tailwind v4 Dark Mode](https://tailwindcss.com/docs/dark-mode)
- [FAKTA-WEB] v4 menuntut **Safari 16.4+, Chrome 111+, Firefox 128+** dan bergantung pada `@property` serta `color-mix()`. Kutipannya: *"If you need to support older browsers, stick with v3.4 until your browser support requirements change."* — [Tailwind Upgrade Guide](https://tailwindcss.com/docs/upgrade-guide)
- [FAKTA-WEB] Alat upgrade `npx @tailwindcss/upgrade` membutuhkan Node.js 20+ dan dianjurkan dijalankan di branch baru dengan peninjauan diff serta uji di peramban. Di v4 plugin PostCSS pindah ke `@tailwindcss/postcss` (`postcss-import` dan `autoprefixer` dibuang), Vite plugin direkomendasikan, dan konfigurasi JS tidak lagi terdeteksi otomatis sehingga perlu `@config "../../tailwind.config.js";`. Opsi JS `corePlugins`, `safelist`, dan `separator` tidak didukung — [Tailwind Upgrade Guide](https://tailwindcss.com/docs/upgrade-guide)
- [FAKTA-WEB] Perubahan default yang merusak di v4 [Tailwind Upgrade Guide](https://tailwindcss.com/docs/upgrade-guide):
  - warna border default berubah dari `gray-200` ke `currentColor`
  - `ring` berubah dari 3px ke 1px, dan warnanya dari `blue-500` ke `currentColor`
  - placeholder berubah dari `gray-400` ke warna teks 50%
  - kursor tombol berubah dari `pointer` ke `default`
  - selektor `space-y-*` berubah
  - skala diganti nama: `shadow-sm→shadow-xs`, `shadow→shadow-sm`, `rounded→rounded-sm`, `blur→blur-sm`, `outline-none→outline-hidden`, `ring→ring-3`
  - urutan stacking varian menjadi kiri ke kanan
  - `hover:` dibungkus `@media (hover: hover)`
  - modifier `!` pindah ke akhir
  - `theme()` deprecated demi `var(--…)`
  - `bg-[--x]` menjadi `bg-(--x)`
  - `@layer utilities` menjadi `@utility`
- [FAKTA-LOKAL] Pemakaian yang terdampak bila migrasi: `resources/css/app.css` memakai `font-family: theme('fontFamily.serif')` pada `h1` (masih berjalan di v4 tetapi deprecated). Token warna ditulis sebagai kanal `R G B` dan dikonsumsi lewat `rgb(var(--nama) / <alpha-value>)` di `tailwind.config.js` (helper `token()`). `tests/Feature/ColourContrastTest.php:250-262` mem-parse token dengan regex `--nama:\s*(\d+)\s+(\d+)\s+(\d+);`, jadi format `@theme` v4 yang lazim (`--color-*: oklch(...)`) akan mematahkan parser test.
- [FAKTA-WEB] Di v4 opacity modifier dihasilkan dengan `color-mix(in oklab, var(--color-foo) 50%, transparent)`, dan `<alpha-value>` tidak lagi diperlukan untuk warna CSS-first. Nasib `<alpha-value>` di konfigurasi JS lama yang dimuat lewat `@config` masih diperdebatkan di [diskusi #16667](https://github.com/tailwindlabs/tailwindcss/discussions/16667) — [Functions and directives](https://tailwindcss.com/docs/functions-and-directives) (ringkasan mesin pencari) → **[UNVERIFIED]** apakah `rgb(var(--x) / <alpha-value>)` berperilaku identik di v4.

### Inferences
- [INFERENSI] Bahaya laten: bila seseorang menambahkan `tailwindcss()` dari `@tailwindcss/vite` ke `vite.config.js` sementara `postcss.config.js` masih memanggil Tailwind v3 dan `app.css` masih memakai `@tailwind …`, CSS akan diproses dua mesin, dan v4 tidak membaca `tailwind.config.js` tanpa `@config`. Hasilnya utilitas token (`text-primary`, `bg-surface`, `brand-*`) hilang diam-diam. Dependensi yang tidak dipakai juga menambah waktu `npm ci` dan permukaan rantai pasok.
- [REKOMENDASI] **Opsi A (minimal, tanpa ubah versi):** tetap di Tailwind 3.4.19 lewat PostCSS. Mode gelap dibangun di lapisan token `app.css` (lihat Q2), ditambah `darkMode` eksplisit di `tailwind.config.js` supaya `dark:` tidak diam-diam mengikuti OS. Tidak ada dependensi yang berubah. `@tailwindcss/vite` dicatat sebagai **[OPEN ISSUE / PERLU PERSETUJUAN PEMILIK]**: hapus, atau jadikan bagian dari Opsi B.
- [REKOMENDASI] **Opsi B (migrasi v4 terencana, terpisah dari fitur mode gelap):** branch spike, `npx @tailwindcss/upgrade`, pindah ke `@tailwindcss/vite` dan buang Tailwind dari `postcss.config.js`, token ke `@theme`, gelap lewat `@custom-variant`, forms lewat `@plugin "@tailwindcss/forms"` (kompatibilitas **[UNVERIFIED]**). Parser `ColourContrastTest` harus ditulis ulang. Wajib ada audit visual per halaman karena perubahan default border, ring, shadow, dan `space-y`. Batas peramban naik ke Safari 16.4 dan Chrome 111. **[PERLU PERSETUJUAN PEMILIK]** (R-020 dan R-021: perubahan versi framework).
- [INFERENSI] **Opsi C (v4 memakai `@config` JS lama)** menanggung seluruh risiko breaking default v4 tanpa manfaat token CSS-first, dan perilaku `<alpha-value>`-nya belum terverifikasi. Tidak disarankan sebagai "jalan tengah".
- [INFERENSI] Mode gelap **tidak membutuhkan** v4. Mekanisme yang dibutuhkan (redefinisi CSS variables di bawah selektor atau media query) bekerja identik di v3.4.

### Gaps
- Jumlah pemakaian kelas yang berubah makna di v4 (`shadow`, `rounded`, `ring`, `border` tanpa warna, `space-y`) di view belum dihitung.
- Distribusi versi peramban di Indonesia (pangsa Chrome di bawah 111 dan Safari di bawah 16.4, WebView lama, Samsung Internet) tidak ditemukan dari sumber terbuka.
- Kompatibilitas `@tailwindcss/forms` 0.5.x di bawah v4 `@plugin` tidak diverifikasi.

---

## 2. Arsitektur mode gelap berbasis CSS variables: penamaan token, semantik vs primitif, nilai gelap, `prefers-color-scheme` plus override pengguna, persistensi, dan pencegahan kilatan tema dengan `wire:navigate`

### Takeaway
Arsitektur token yang ada (primitif `brand/warn/danger/community` ditambah lapisan semantik `canvas/surface/text-*/border-subtle/control-border` dalam format kanal `R G B`) sudah menjadi fondasi yang benar. Mode gelap cukup berupa **redefinisi variabel yang sama** di bawah media query dan atribut. Temuan kritis ada di **Livewire 3.8.9**: setiap swap `wire:navigate` **menghapus atribut `<html>` yang tidak ada di HTML server** dan mengganti semua `<meta>` di `<head>` dengan versi server. Karena itu desain paling tahan banting adalah: mode *system* ditangani murni oleh CSS media query tanpa JS, sedangkan override eksplisit dirender **server-side** ke `<html data-theme>` dari cookie. Callback `onSwap` dipasang sebagai jaring pengaman.

### Cited Findings
- [FAKTA-LOKAL] **Struktur token** `resources/css/app.css:30-103`:
  - primitif: `--brand-50…900` (tanpa 400), `--warn-50/100/300/600/900`, `--community-50/500/900`, `--danger-50/100/300/600/700/900`
  - `--control-border`, `--radius-control`
  - semantik: `--text-primary/secondary/muted`, `--canvas`, `--surface`, `--surface-sunken`, `--border-subtle`
  
  [DIHITUNG] Primitifnya **identik dengan palet default Tailwind v3**: brand = emerald, warn = amber, community = sky, danger = rose, `text-primary` = stone-900, `text-secondary` = stone-700. Verifikasinya membandingkan nilai dengan `node_modules/tailwindcss/colors.js`.
- [FAKTA-LOKAL] **Warna yang tidak akan ikut token:**
  - view (neutral palette, 36 berkas): 33× `bg-white`, 21× `text-white`, 6× `text-black`, 3× `ring-white`, 2× `bg-red-*`, 1× `text-slate-*`, 1× `ring-black`
  - hex mentah: `#047857` di `<meta name="theme-color">` (`resources/views/layouts/app.blade.php:12`, `guest.blade.php:12`); `#FF2D20` hanya di view welcome yang tak dipakai
  - `app.css` sekitar baris 196-213: warna scrollbar ditulis mentah (`rgb(156 163 175)`, `rgb(249 250 251)`, `rgb(107 114 128)`)
  - `tailwind.config.js`: `boxShadow.overlay: '0 12px 32px -8px rgb(28 25 23 / 0.18)'`
  - `app/Http/Controllers/ProgressiveWebAppController.php:28-29`: `background_color '#ffffff'`, `theme_color '#047857'`
- [FAKTA-LOKAL] `@tailwindcss/forms` 0.5.11 menulis mati warna input di `node_modules/@tailwindcss/forms/src/index.js`: `background-color: '#fff'` (baris 49, 205), `--tw-ring-offset-color: '#fff'` (68, 236), border dan placeholder `gray-500` (50-51, 87, 206-207). Tanpa override, semua input tetap putih di mode gelap.
- [FAKTA-LOKAL] `<html>` hanya membawa `lang` (`app.blade.php:2`). `<body class="font-sans antialiased">` tidak berlatar. `bg-canvas` dipasang pada `<div class="min-h-screen bg-canvas">` (`app.blade.php:38`) dan pada `<main … bg-canvas>` (`guest.blade.php:38`). Tidak ada `color-scheme` di mana pun.
- [FAKTA-LOKAL] **Livewire 3.8.9** `vendor/livewire/livewire/dist/livewire.esm.js`:
  - `swapCurrentPageWithNewHtml()` (baris 10044-10063) memanggil `replaceHtmlAttributes(newHtml)` (10074-10087). Fungsi ini menyalin setiap atribut `<html>` halaman baru **dan menghapus atribut `<html>` sekarang yang tidak ada di halaman baru**.
  - `mergeNewHead()` (10089-10127): aset (`<link rel=stylesheet>`, `<style>`, `<script>`, lihat `isAsset` 10165-10167) yang sudah ada tidak dijalankan ulang, sedangkan **semua anak `<head>` non-aset (termasuk `<meta>`) dihapus lalu diganti** dengan milik halaman baru.
  - Callback `onSwap` tersedia di event navigasi (10248 dan 10309, jalur navigasi biasa dan jalur riwayat), dengan nama publik `livewire:navigating` (11318).
  - Skrip `<body>` dijalankan ulang tiap navigasi kecuali yang ber-`data-navigate-once`.
- [FAKTA-WEB] Dokumentasi Livewire: navigate mengganti URL, `<title>`, dan isi `<body>`. Skrip `<head>` yang sama hanya jalan pada kunjungan pertama. Event `livewire:navigating` punya `e.detail.onSwap()`, yang menurut dokumen *"is a good place to apply critical styles such as dark mode to prevent flickering"*. `livewire:navigated` juga dipicu saat page load menggantikan `DOMContentLoaded` — [Livewire Navigate](https://livewire.laravel.com/docs/navigate). **Catatan:** situs dokumentasi mungkin sudah menampilkan Livewire 4, tetapi perilaku di atas dikonfirmasi pada source 3.8.9.
- [FAKTA-WEB] Pola resmi Tailwind untuk tiga status (terang/gelap/sistem): skrip inline di `<head>` yang memeriksa `localStorage.theme` lalu jatuh ke `matchMedia('(prefers-color-scheme: dark)')`. Komentar dokumen: *"best to add inline in `head` to avoid FOUC"* — [Tailwind v4 Dark Mode](https://tailwindcss.com/docs/dark-mode); [Tailwind v3 Dark Mode](https://v3.tailwindcss.com/docs/dark-mode)
- [FAKTA-WEB] `<meta name="color-scheme" content="light dark">` diproses **sebelum CSS dimuat**, sehingga latar kanvas, kontrol form, scrollbar, dan system colors (`Canvas`, `CanvasText`, `ButtonFace`) langsung memakai skema yang benar. Properti CSS `color-scheme` baru berlaku setelah stylesheet diurai. Nilai pertama adalah preferensi penulis. Dukungan: Chrome/Edge 81+, Firefox 96+, Safari 13+ — [web.dev, Thomas Steiner, April 2020](https://web.dev/articles/color-scheme)
- [FAKTA-WEB] `light-dark(<warna-terang>, <warna-gelap>)` berstatus Baseline 2024 (Mei 2024) dan **mensyaratkan** `color-scheme: light dark` pada `:root`. Argumennya berupa `<color>` atau image — [MDN light-dark()](https://developer.mozilla.org/en-US/docs/Web/CSS/color_value/light-dark)
- [FAKTA-WEB] Pola warna CSS variables v3 dengan `<alpha-value>` (format kanal) — [Tailwind v3 Customizing Colors](https://v3.tailwindcss.com/docs/customizing-colors#using-css-variables) (tidak dibuka di sesi ini)
- [FAKTA-LOKAL] Alpine yang dibundel Livewire 3.8.9 sudah membawa plugin **persist**, collapse, focus, intersect, resize, anchor, morph, dan mask (`livewire.esm.js` sekitar baris 3947-9464). `$persist` menyimpan ke `localStorage` dengan kunci `_x_<nama>` atau alias `.as()`, dan jatuh ke penyimpanan sementara disertai peringatan konsol bila `localStorage` tidak tersedia — [Alpine Persist](https://alpinejs.dev/plugins/persist) (tidak dibuka di sesi ini; perilaku dibaca dari source lokal).
- [FAKTA-LOKAL] Laravel 13.31.0 menyediakan `Middleware::encryptCookies(array $except = [])` (`vendor/laravel/framework/src/Illuminate/Foundation/Configuration/Middleware.php:590`). `bootstrap/app.php` belum memanggilnya. Cookie yang ditulis JS di peramban tidak terenkripsi, sehingga tidak terbaca di server kecuali dikecualikan.
- [FAKTA-LOKAL] CSP di `app/Http/Middleware/SecurityHeaders.php:50` adalah `script-src 'self' 'unsafe-inline' 'unsafe-eval'`, sehingga skrip inline di `<head>` saat ini lolos tanpa nonce.
- [FAKTA-WEB] Apple HIG: *"Avoid offering an app-specific appearance setting… they may think your app is broken because it doesn't respond to their systemwide appearance choice"* dan *"Avoid using hard-coded color values or colors that don't adapt."* — [Apple HIG Dark Mode](https://developer.apple.com/design/human-interface-guidelines/dark-mode) (isi dibaca via [endpoint JSON](https://developer.apple.com/tutorials/data/design/human-interface-guidelines/dark-mode.json)). Sebaliknya, NN/g menyarankan desainer *"allow users to switch to dark mode if they want to"* — [NN/g, Raluca Budiu, 2 Feb 2020](https://www.nngroup.com/articles/dark-mode/)

### Inferences
- [INFERENSI] **Desain B (pola Tailwind klasik: JS menyetel `class="dark"` di `<html>`) rapuh di stack ini.** Server tidak merender atribut itu, sehingga setiap swap `wire:navigate` **menghapusnya** (`replaceHtmlAttributes`). Skrip inline di `<head>` tidak jalan ulang karena aset head yang identik dibuang. Tema bisa terlempar kembali ke terang sampai ada kode yang memasangnya lagi. `onSwap` menutup celah itu, tetapi apakah callback-nya berjalan sebelum paint pertama pasca-swap belum diuji di peramban **[UNVERIFIED]**.
- [REKOMENDASI] **Desain A (CSS-first, atribut hanya untuk override eksplisit), cocok dengan v3.4 tanpa ubah versi:**
  ```css
  @layer base {
    :root { color-scheme: light; /* token terang seperti sekarang */ }
    @media (prefers-color-scheme: dark) {
      :root:not([data-theme='light']) { color-scheme: dark; /* token gelap */ }
    }
    :root[data-theme='dark'] { color-scheme: dark; /* token gelap, blok identik */ }
    html, body { background-color: rgb(var(--canvas)); color: rgb(var(--text-primary)); }
  }
  ```
  Mode *system* tidak membutuhkan JS sama sekali dan otomatis mengikuti perubahan OS di tengah sesi. Apple HIG mencatat mode "Auto" dapat berganti *"while your app is running"*. Duplikasi blok gelap tidak terhindarkan di format kanal. `light-dark()` hanya menerima `<color>`, bukan daftar kanal `R G B`, jadi tidak dapat dipakai untuk token `<alpha-value>` [INFERENSI dari sintaks MDN]. Drift antarblok dijaga oleh test paritas (Q7).
- [REKOMENDASI] **Persistensi tiga status (`system` | `light` | `dark`):**
  1. Cookie `tema` bernilai `light` atau `dark`, dihapus untuk `system`, dengan `Path=/; SameSite=Lax; Secure; Max-Age=31536000`. Daftarkan `$middleware->encryptCookies(except: ['tema'])`.
  2. Server membaca cookie dengan **whitelist** (`in_array($v, ['light','dark'], true)`) dan tidak pernah mencetak nilai mentah, lalu merender `<html … @if($tema) data-theme="{{ $tema }}" @endif>`. Karena setiap respons navigate membawa atribut yang sama, `replaceHtmlAttributes` tidak menghapusnya.
  3. Cermin di `localStorage` untuk halaman yang disajikan tanpa konteks server (shell service worker).
  4. Opsional: kolom preferensi di tabel `users` supaya sinkron antarperangkat. Ini perubahan skema dan **[PERLU PERSETUJUAN PEMILIK]** (R-021), serta wajib diuji di PostgreSQL (R-024).
- [REKOMENDASI] **Meta di `<head>` dirender server sesuai cookie**, karena `mergeNewHead` mengganti semua `<meta>` pada setiap navigate. Mutasi meta dari JS akan tertimpa pada halaman berikutnya.
  ```blade
  <meta name="color-scheme" content="{{ $tema ?? 'light dark' }}">
  @if ($tema)
      <meta name="theme-color" content="{{ $tema === 'dark' ? '#1c1917' : '#047857' }}">
  @else
      <meta name="theme-color" content="#047857" media="(prefers-color-scheme: light)">
      <meta name="theme-color" content="#1c1917" media="(prefers-color-scheme: dark)">
  @endif
  ```
  Nilai `#1c1917` hanya kandidat; lihat Q3.
- [REKOMENDASI] **Jaring pengaman JS** di `resources/js/app.js`, yang dimuat sekali lewat `@vite`:
  ```js
  const terapkanTema = () => {
      const t = document.cookie.match(/(?:^|; )tema=(light|dark)/)?.[1]
          ?? (() => { try { return localStorage.getItem('tema'); } catch { return null; } })();
      if (t === 'light' || t === 'dark') document.documentElement.dataset.theme = t;
      else delete document.documentElement.dataset.theme;
  };
  document.addEventListener('livewire:navigating', (e) => e.detail.onSwap(terapkanTema));
  ```
  Kode ini menangani pemulihan snapshot riwayat (back/forward) yang membawa atribut lama setelah pengguna mengganti tema.
- [REKOMENDASI] **Kontrol UI:** `<fieldset>` dengan `<legend>Tema</legend>` dan tiga radio berlabel teks "Ikuti sistem", "Terang", dan "Gelap", bukan ikon saja. Semantik native membuat status terbaca TalkBack dan VoiceOver. Target sentuh 44×44 px. Letakkan di halaman pengaturan dan akses cepat di mode pendakian (Q4). Jangan animasikan transisi warna global saat berganti tema. Transisi pada `*` membebani GPU ponsel kelas bawah, dan app sudah memangkas gerak lewat `prefers-reduced-motion` (`app.css` sekitar baris 257-266).
- [REKOMENDASI] **Penamaan token:** pertahankan dua tingkat. Untuk dark, tersedia dua opsi.
  - **T1**, remap primitif berdasarkan peran. Contoh: di mode gelap `--brand-100` menjadi latar gelap berwarna dan `--brand-900` menjadi teks terang, sehingga pasangan `bg-brand-100 text-brand-900` tetap berfungsi tanpa menyentuh view. Konsekuensinya nama "100" menyesatkan di mode gelap.
  - **T2**, token peran baru seperti `--on-brand`, `--focus-ring`, `--status-warn-bg/fg`, `--status-danger-bg/fg`, dan `--overlay-border`, dimigrasikan bertahap.
  
  Opsi termurah adalah T1 untuk pasangan yang ada, ditambah T2 untuk kasus yang tidak bisa diremap: `text-white` di atas tombol brand, `ring-white`, focus ring, dan shadow.
- [REKOMENDASI] Ganti 33 `bg-white` menjadi `bg-surface` dan 6 `text-black` menjadi `text-primary`. Timpa basis `@tailwindcss/forms` di `@layer base`: `background-color: rgb(var(--surface)); border-color: rgb(var(--control-border)); color: rgb(var(--text-primary)); --tw-ring-offset-color: rgb(var(--canvas));` dan `::placeholder { color: rgb(var(--text-muted)); }`. Panah `<select>` bawaan plugin memakai SVG ber-stroke `gray-500`; kontrasnya di permukaan gelap perlu dihitung **[UNVERIFIED]**. `accent-color`, `caret-color`, `::selection`, dan scrollbar dijadikan token. Test serupa `test_no_view_uses_a_raw_semantic_colour` dapat melarang `dark:` di view supaya semua adaptasi lewat token, lalu hapus view welcome sisa Breeze.

### Gaps
- Belum diuji di peramban nyata apakah `onSwap` berjalan sebelum paint. Perlu rekaman Performance panel dengan throttling CPU 4× dan jaringan "Slow 4G".
- Service worker hanya menyimpan shell (komentar `resources/js/app.js`). Cara shell offline menampilkan tema belum diperiksa.
- Perbedaan perilaku navigate Livewire 4 dibanding 3.8.9 tidak diteliti. Relevan hanya bila ada upgrade, yang juga memerlukan persetujuan.

---

## 3. Aturan desain mode gelap: kontras, hindari hitam murni, elevasi, warna semantik jenuh, gambar/logo, focus ring, peta (basemap gelap dan lisensinya), dan grafik

### Takeaway
Gunakan netral hangat gelap (bukan `#000`), elevasi lewat permukaan yang lebih terang (bayangan tidak terbaca), dan tone yang lebih terang serta kurang jenuh untuk brand, warn, danger, dan community di atas latar gelap. Jaga 4.5:1 untuk teks dan 3:1 untuk batas kontrol, focus ring, dan garis grafik di **setiap** tingkat permukaan. Kandidat palet dari keluarga Tailwind yang sudah dipakai lulus AA dengan margin lebar, kecuali `control-border` yang tipis (3.16:1). Garis profil elevasi `brand-700` **gagal 3:1** di permukaan gelap bila tidak diremap. Untuk peta, OpenTopoMap raster tidak punya varian gelap. Opsinya meredupkan via `raster-brightness-max` (tersedia di MapLibre 4.7.1) atau style vektor gelap (OpenFreeMap Dark: MIT/ODbL, tanpa kunci) yang **tidak punya kontur**.

### Cited Findings
- [FAKTA-WEB] WCAG 1.4.3: teks normal minimal 4.5:1, teks besar 3:1 — [W3C Understanding 1.4.3](https://www.w3.org/WAI/WCAG22/Understanding/contrast-minimum.html) (tidak dibuka di sesi ini). WCAG 1.4.11: 3:1 untuk batas atau status komponen UI dan objek grafis — [W3C Understanding 1.4.11](https://www.w3.org/WAI/WCAG22/Understanding/non-text-contrast.html) (tidak dibuka di sesi ini).
- [FAKTA-WEB] Apple HIG: *"At a minimum, make sure the contrast ratio between colors is no lower than 4.5:1. For custom foreground and background colors, strive for a contrast ratio of 7:1, especially in small text."* Latar **base** lebih redup dan **elevated** lebih terang, supaya antarmuka depan tampak maju. Buat ikon terpisah untuk terang dan gelap bila perlu. Uji dengan Increase Contrast dan Reduce Transparency, karena Increase Contrast di Dark Mode dapat *menurunkan* kontras teks gelap di latar gelap — [Apple HIG Dark Mode](https://developer.apple.com/design/human-interface-guidelines/dark-mode)
- [FAKTA-WEB] Material (halaman dirender JS; isi dari cuplikan mesin pencari, jadi sebagian **[UNVERIFIED]** verbatim): permukaan dasar `#121212`, abu gelap alih-alih hitam karena bayangan lebih terlihat dan ketegangan mata berkurang. Teks-latar minimal 15.8:1 supaya teks tubuh tetap ≥4.5:1 di elevasi tertinggi. Warna jenuh dihindari demi varian desaturasi. Overlay putih semi-transparan naik seiring elevasi — [Material Dark theme](https://m2.material.io/design/color/dark-theme.html). Dokumentasi Material Components mengonfirmasi *"dark grey instead of black, which increases visibility for shadows and also reduces eye strain for light text"* dan overlay yang alpha-nya naik seiring elevasi — [MDC Android Dark.md](https://raw.githubusercontent.com/material-components/material-components-android/master/docs/theming/Dark.md)
- [FAKTA-LOKAL] Rasio mode terang yang sudah dikunci (komentar `app.css` dan `tests/Feature/ColourContrastTest.php`, `NORMAL_TEXT = 4.5` baris 22, `LARGE_TEXT_AND_UI = 3.0` baris 24):

  | Pasangan | Rasio |
  |---|---|
  | putih / brand-700 | 5.48 |
  | putih / danger-600 | 4.70 |
  | brand-900 / brand-100 | 8.57 |
  | warn-900 / warn-100 | 8.15 |
  | danger-900 / danger-100 | 7.97 |
  | text-primary / canvas | 16.73 |
  | text-secondary / canvas | 9.83 |
  | text-muted / canvas | 5.11 |
  | text-muted / sumur | 4.89 |
  | control-border / putih | 3.52 |
  | focus ring brand-600 / putih | 3.77 |
- [DIHITUNG] **Kandidat palet gelap** (bukan keputusan desain; semua dari keluarga Tailwind yang sudah dipakai):

  | Pasangan kandidat | Rasio | Status |
  |---|---|---|
  | text-primary stone-100 `#f5f5f4` / canvas stone-900 `#1c1917` | 16.03 | lulus |
  | text-primary / surface stone-800 `#292524` | 13.90 | lulus |
  | text-primary / sunken `#161412` | 16.84 | lulus |
  | text-secondary stone-300 / canvas · surface | 11.74 · 10.18 | lulus |
  | text-muted stone-400 `#a8a29e` / canvas · surface · sunken | 6.93 · 6.01 · 7.29 | lulus |
  | control-border stone-500 `#78716c` / surface · canvas | **3.16** · 3.65 | lulus tipis |
  | control-border stone-400 / surface | 6.01 | lulus |
  | focus ring emerald-400 `#34d399` / canvas · surface | 9.10 · 7.89 | lulus |
  | link emerald-300 / canvas | 11.47 | lulus |
  | tombol primer putih / emerald-700 (tetap) | 5.48 | lulus |
  | alternatif stone-950 / emerald-400 | 10.28 | lulus |
  | isi emerald-700 vs canvas (batas tombol) | 3.19 | lulus tipis |
  | badge emerald-200 / emerald-950 `#022c22` | 11.81 | lulus |
  | warn amber-200 / amber-950 `#451a03` | 12.03 | lulus |
  | danger rose-200 / rose-950 `#4c0519` | 11.08 | lulus |
  | tombol hapus putih / rose-600 (tetap) | 4.70 | lulus |
  | community sky-200 / sky-950 `#082f49` | 10.46 | lulus |
  | garis profil elevasi emerald-400 / surface | 7.89 | lulus |
  | **garis profil elevasi emerald-700 / surface (bila tidak diremap)** | **2.77** | **gagal 1.4.11** |
  | border-subtle stone-700 / surface (dekoratif) | 1.48 | tidak diatur 1.4.11 |
  | surface vs canvas (beda elevasi) | 1.15 | dekoratif |
  | pembanding: putih / `#121212` | 18.73 | — |
- [FAKTA-LOKAL] `resources/views/components/ui/elevation-profile.blade.php:39-41` sudah memakai token (`fill="rgb(var(--brand-100))"`, `stroke="rgb(var(--brand-700))"`), sehingga ikut berubah otomatis. Tanpa remap, `--brand-700` di permukaan gelap hanya 2.77:1 [DIHITUNG].
- [FAKTA-LOKAL] Peta: default raster OpenTopoMap `https://tile.opentopomap.org/{z}/{x}/{y}.png` (`config/hiking.php:109`, env `MAP_TILE_URL`). Konfigurasi juga mendukung `style_url` (env `MAP_STYLE_URL`, `hiking.php:108`), atribusi (`:110`), dan `max_zoom` 17 (`:111`). `resources/views/components/ui/map.blade.php:83-95` membuat source `raster` dan layer `basemap`. CSP `SecurityHeaders::hostPeta()` menurunkan host dari `raster_tiles` **dan** `style_url`, sehingga mengganti style URL otomatis memperbarui `img-src` dan `connect-src`.
- [FAKTA-LOKAL] Bundel MapLibre 4.7.1 memuat paint property `raster-brightness-min`, `raster-brightness-max`, `raster-contrast`, `raster-saturation`, `raster-hue-rotate`, `raster-opacity`, serta `setPaintProperty` (grep `node_modules/maplibre-gl/dist/maplibre-gl.js`) — spesifikasi: [MapLibre Style Spec, layer raster](https://maplibre.org/maplibre-style-spec/layers/) (tidak dibuka di sesi ini).
- [FAKTA-WEB] OpenFreeMap menyediakan style Positron, Bright, Liberty, **Dark**, dan Fiord. Instans publiknya *"completely free: there are no limits on the number of map views or requests"*, tanpa registrasi, API key, atau cookie. Perangkat lunaknya berlisensi MIT, data dari OpenStreetMap. Atribusi wajib *"OpenFreeMap © OpenMapTiles Data from OpenStreetMap"*. Self-host didukung dengan unduhan planet mingguan (Btrfs/MBTiles). Keberlanjutannya bergantung donasi — [openfreemap.org](https://openfreemap.org/)
- [FAKTA-LOKAL, runtime 2026-09-23] `https://tiles.openfreemap.org/styles/dark` dan `/styles/liberty` mengembalikan HTTP 200 `application/json`. Style **dark** berisi 47 layer dengan source `ne2_shaded` (Natural Earth shaded relief) dan `openmaptiles`. Satu-satunya layer jalur adalah `highway_path`, dan **tidak ada layer kontur atau hillshade skala detail**. Liberty berisi 111 layer, source sama, dengan layer path/track tetapi juga tanpa kontur.
- [FAKTA-WEB] Protomaps punya flavor `light`, `dark`, `white`, `grayscale`, dan `black` via `namedFlavor("dark")` dari `@protomaps/basemaps`. Kode berlisensi BSD dan tiles ODbL. Self-host lewat PMTiles di AWS, Cloudflare, GCP, Azure, atau server biasa — [Protomaps Flavors](https://docs.protomaps.com/basemaps/flavors). Syarat API hosted (kunci, komersial) **[UNVERIFIED]**.
- [FAKTA-WEB] Issue OpenTopoMap #382 (dibuka 19 Nov 2025 oleh sletuffe): *"The tile server at https://opentopomap.org/ is going to survival mode and soon no tiles over 13 should be served"*, *"its data is from January 2023"*. Pengganti yang ditawarkan adalah openmaps.fr (gaya OpenTopoMap, gratis, tanpa kunci, CC-BY-SA) — [GitHub der-stefan/OpenTopoMap#382](https://github.com/der-stefan/OpenTopoMap/issues/382). Kebijakan openmaps.fr: kurang dari 400 ribu tile per bulan dianggap volume rendah — [openmaps.fr Tile Usage Policy](https://openmaps.fr/tile-usage-policy.html) (via cuplikan pencarian).
- [FAKTA-LOKAL, runtime 2026-09-23] `tile.opentopomap.org` masih mengembalikan HTTP 200 PNG untuk z13, z14, z15, dan z17 di sekitar Gunung Gede (−6.79, 106.98). Batas z13 yang diumumkan belum berlaku di sana, tetapi datanya tetap snapshot Januari 2023 menurut issue di atas.
- [FAKTA-WEB] Chrome Auto Dark Theme (Chrome 96, Android) menerapkan tema gelap buatan algoritme pada situs terang bila pengguna memilih tema gelap di OS. Opt-out lewat `<meta name="color-scheme" content="only light">` atau `color-scheme: only light` per elemen, dan meta dianggap lebih baik karena mencegah *"flash of darkened content"* — [Chrome for Developers, 21 Okt 2021](https://developer.chrome.com/blog/auto-dark-theme)

### Inferences
- [REKOMENDASI] Kanvas gelap berbasis stone-900 `#1c1917` sejalan dengan prinsip "bukan hitam murni" dan tetap hangat (text-primary terang sudah stone-900, sehingga kanvas gelap menjadi pembalikan alami). Elevasi naik ke stone-800. Karena beda surface dan canvas hanya 1.15:1, kartu dan overlay di mode gelap butuh **border** `border-subtle`, bukan bayangan. `shadow-overlay` (`rgb(28 25 23 / .18)`) praktis tak terlihat di latar gelap.
- [REKOMENDASI] `control-border` gelap sebaiknya stone-400 (6.01:1) atau setidaknya dicek di ketiga permukaan. Stone-500 hanya 3.16:1 di surface, margin terlalu tipis untuk layar yang tersiram cahaya luar.
- [REKOMENDASI] Warna status di mode gelap memakai pasangan cermin: teks tone 200/300 di atas latar tone 950. Ikon dan label teks tetap wajib karena status tidak boleh hanya lewat warna (CLAUDE.md §6). Kuning `warn` jenuh (amber-600) tidak dipakai sebagai teks di latar gelap; gunakan amber-200/300.
- [REKOMENDASI] Focus ring gelap memakai emerald-400 (9.10:1 di canvas) sebagai token `--focus-ring`, bukan `brand-600` tetap. Garis profil elevasi memakai tone terang (emerald-400, 7.89:1). Area isian cukup tone gelap dekoratif (emerald-950).
- [REKOMENDASI] Foto komunitas dan gambar tidak diinversi (R-017 tetap berlaku). Logo dan ikon dibuat SVG `currentColor` supaya mengikuti token. Atribusi peta harus tetap terbaca di kedua tema; kontras kontrol atribusi MapLibre di atas UI gelap belum dihitung.
- [REKOMENDASI] **Opsi peta:**
  - **M1 (minimal):** basemap topografi tetap terang di UI gelap. Opsional peredupan `map.setPaintProperty('basemap', 'raster-brightness-max', 0.75)` dan `raster-saturation` negatif saat tema gelap aktif. Tidak ada dependensi atau CSP baru.
  - **M2:** OpenFreeMap Dark (`MAP_STYLE_URL=https://tiles.openfreemap.org/styles/dark`) hanya untuk peta perencanaan tempat kontur tidak penting. Mode pendakian tetap topo.
  - **M3:** self-host PMTiles Protomaps.
  
  [INFERENSI] Bagi pendaki, kehilangan kontur adalah regresi keselamatan-informasi, jadi M1 paling aman untuk mode pendakian.
- [OPEN ISSUE] Risiko ketersediaan dan kesegaran OpenTopoMap (mode bertahan, data 2023) **terlepas dari mode gelap**, tetapi memengaruhi pilihan basemap. Perlu keputusan pemilik soal penyedia utama dan cadangan. openmaps.fr adalah kandidat dengan gaya yang sama.
- [REKOMENDASI] Bila pemilik **menolak** mode gelap, pasang `<meta name="color-scheme" content="only light">` supaya Chrome Auto Dark Theme tidak membalik warna status (warn/danger) secara algoritmik. Bila **menyetujui**, gunakan `light dark` sesuai Q2.

### Gaps
- Nilai final palet gelap membutuhkan keputusan desain dan uji layar nyata. Angka di atas hanya kandidat yang dihitung.
- Kontras panah `<select>` bawaan forms plugin dan kontrol atribusi MapLibre di UI gelap belum dihitung.
- Apakah OpenFreeMap atau Protomaps punya jalur kontur (DEM atau plugin kontur) yang layak untuk Indonesia tidak diteliti.
- Perilaku Samsung Internet (mode gelap paksa pada situs) tidak ditemukan sumbernya.

---

## 4. Konteks lapangan: apakah mode gelap membantu atau merugikan di bawah matahari langsung? Default dan kekhususan mode pendakian

### Takeaway
Bukti riset tidak menunjukkan mode gelap lebih terbaca di terik matahari. Polaritas positif (teks gelap di latar terang) umumnya sama atau lebih baik, terutama untuk teks kecil. Di cahaya terang perbedaannya mungkin nol, dan penghematan baterai mode gelap nyata hanya pada OLED dan terutama pada kecerahan tinggi. Karena itu default **"Ikuti sistem"**, tema **terang menjadi baseline sinar matahari** berkontras tinggi, dan mode pendakian perlu **override sekali ketuk**. Jangan memaksa mode gelap di malam hari demi keterbacaan. Alasan yang sah untuk gelap di malam hari adalah silau dan kenyamanan, bukan kecepatan membaca.

### Cited Findings
- [FAKTA-WEB] NN/g (Raluca Budiu, 2 Feb 2020): untuk penglihatan normal, *"light mode leads to better performance most of the time"*.
  - Piepenbrock dkk. 2013 (*Ergonomics*): polaritas positif lebih baik untuk ketajaman visual dan proofreading di semua usia.
  - Piepenbrock dkk. 2013 (*Human Factors*): keunggulan membesar saat font mengecil.
  - Dobres dkk. 2017 (*Applied Ergonomics*): *"during daytime, there was no significant effect of contrast polarity, but during nighttime, light mode led to better performance than dark mode"* dan *"during nighttime it was much harder for people to read small-font text in dark mode than in light mode"*.
  - *"Dobres and his colleagues argue that the amount of ambient light may affect the positive-polarity advantage, with bright light leading to zero difference."* Orang yang lebih tua lebih rentan silau di cahaya terang.
  - Pengguna dengan katarak (Legge 1985) membaca lebih baik dengan mode gelap. Rekomendasinya mengizinkan pengguna beralih.
  
  Sumber: [NN/g Dark Mode vs. Light Mode](https://www.nngroup.com/articles/dark-mode/)
- [FAKTA-WEB] Purdue (Pranab Dash & Charlie Hu, 2021): pada kecerahan **100%**, beralih ke mode gelap menghemat rata-rata **39%–47%** daya baterai (OLED). Mode terang pada kecerahan rendah menarik daya sama dengan mode gelap pada kecerahan lebih tinggi; contohnya Google News di Pixel 5, terang 20% ≈ gelap 50%. Judul rilisnya: *"Dark mode may not save your phone's battery life as much as you think"* — [Purdue University News, 2021](https://www.purdue.edu/newsroom/archive/releases/2021/Q3/dark-mode-may-not-save-your-phones-battery-life-as-much-as-you-think,-but-there-are-a-few-silver-linings.html)
- [FAKTA-WEB] Apple HIG: pengguna dapat memakai tampilan *Auto* yang berganti terang/gelap *"as conditions change throughout the day, potentially while your app is running"* — [Apple HIG Dark Mode](https://developer.apple.com/design/human-interface-guidelines/dark-mode)
- [FAKTA-LOKAL] `PRD.md:387` menetapkan Tailwind dengan *"kontras tinggi agar nyaman dibaca di bawah sinar matahari"*. Komentar `resources/css/app.css` sekitar baris 118-131 menaikkan ukuran baca untuk pendaki *"di bawah matahari"*.
- [FAKTA-LOKAL] **Cacat asumsi ukuran teks, relevan untuk keterbacaan lapangan di kedua tema.** CSS terkompilasi `public/build/assets/app-CGOpcC_9.css` menunjukkan `html,:host{…line-height:1.5}` **tanpa** `font-size`, `body{…font-size:16.5px;line-height:1.6}`, `.text-sm{font-size:.875rem;line-height:1.25rem}`, dan `.text-xs{font-size:.75rem;line-height:1rem}`. Unit `rem` relatif terhadap font-size elemen **root (`html`)**, bukan `body` — [MDN CSS length / rem](https://developer.mozilla.org/en-US/docs/Web/CSS/length) (tidak dibuka di sesi ini). Jadi `text-sm` tetap 14px dan `text-xs` 12px pada setelan peramban default, bertentangan dengan komentar `app.css` bahwa menaikkan ukuran di body "menggeser seluruh skala Tailwind". Status: [INFERENSI kuat dari CSS terkompilasi]; **PERLU VERIFIKASI RUNTIME** lewat `getComputedStyle` pada elemen `.text-sm`.

### Inferences
- [INFERENSI] Di terik matahari, pantulan cahaya menambah luminans pada teks dan latar sehingga kontras efektif turun di kedua polaritas. Bukti yang ada (Dobres) menunjukkan perbedaan polaritas bisa hilang di cahaya terang, bukan berbalik menguntungkan mode gelap. Tuas yang terbukti adalah **ukuran teks dan kontras**, bukan polaritas.
- [INFERENSI] Ketegangan antara keterbacaan dan baterai: di luar ruangan ponsel cenderung berjalan pada kecerahan tinggi, tepat di mana mode gelap OLED paling hemat (Purdue). Di sisi lain, riset polaritas tidak mendukung gelap untuk keterbacaan. Keputusan ini sebaiknya ada di tangan pengguna, bukan dipaksakan.
- [REKOMENDASI] **Default: "Ikuti sistem"**, sejalan dengan Apple HIG dan membuat tidak ada yang "rusak" bagi pengguna OS gelap. Override tersedia di pengaturan sesuai saran NN/g. Ada ketegangan dengan saran HIG untuk menghindari setelan tema per-aplikasi; konteks lapangan (silau, jadwal gelap otomatis saat senja) membenarkan override di mode pendakian.
- [REKOMENDASI] **Mode pendakian:**
  - Sediakan tombol cepat Terang/Gelap (≥44×44 px) yang berlaku sampai pengguna mengubahnya. Ponsel yang otomatis gelap saat matahari terbenam bisa membuat UI gelap padahal pendaki masih di punggungan terbuka yang terang.
  - Tema terang dipakai sebagai baseline sinar matahari. Untuk info kritis (status resmi, checkpoint berikutnya, waktu balik) targetkan ≥7:1 (setara AAA 1.4.6, sesuai anjuran HIG "strive for 7:1"). Teks kecil diperbesar di kedua tema, terutama di tema gelap, karena teks kecil paling dirugikan polaritas negatif (Piepenbrock, Dobres).
  - Di malam hari (summit push dini hari), tema gelap berguna untuk mengurangi silau ke mata sendiri dan rombongan. Jangan menjanjikan manfaat membaca.
  - **Jangan mencantumkan klaim kuantitatif hemat baterai di UI.** Penghematan bergantung panel (OLED vs LCD) dan kecerahan; klaim angka melanggar semangat anti false precision (R-005) dan kejujuran sumber.
- [HIPOTESIS] Banyak ponsel entry-level memakai panel LCD, sehingga mode gelap hampir tidak menghemat daya. Hipotesis ini belum didukung data pangsa panel di Indonesia (lihat Gaps).

### Gaps
- Tidak ditemukan studi lapangan spesifik pendaki, ponsel, dan sinar matahari tropis.
- Pangsa OLED vs LCD pada basis terpasang di Indonesia tidak ditemukan.
- Bukti adaptasi gelap (night vision) dan cahaya layar untuk pendakian malam tidak dicari; jangan diklaim tanpa sumber.
- Preferensi pendaki Indonesia terhadap tema perlu diuji lewat `docs/PROTOKOL-UJI-KEGUNAAN.md`. Hasil riset pengguna tidak boleh dikarang.

---

## 5. Adaptivitas perangkat: breakpoint vs container query, safe area, kekhasan iOS Safari dan Android Chrome, target sentuh, reduced motion, orientasi, foldable, keyboard/pembaca layar, anggaran performa Android kelas bawah, ketahanan jaringan, PWA dan theme-color

### Takeaway
Rancang dari lebar **360 CSS px** (resolusi terbanyak di Indonesia) dan uji di 320 px untuk reflow. Pakai `min-h-dvh` (sudah tersedia di build v3.4) alih-alih `min-h-screen`/100vh. Jaga input ≥16px agar iOS tidak auto-zoom (saat ini aman). Minimum WCAG 24 px, standar proyek 44 px untuk kontrol lapangan. **Buang kunci orientasi `portrait` di manifest** karena termasuk failure F97 WCAG 1.3.4. Pakai `theme-color` ber-`media`, dan karena Safari 26 tidak lagi memakai `theme-color`, beri `html`/`body` latar token. Container query didukung semua mesin sejak Feb 2023, tetapi di Tailwind v3 butuh plugin resmi, sebuah dependensi baru. Anggaran JS saat ini sehat (sekitar 58 KB gzip di jalur kritis); mode pendakian menambah sekitar 208 KB gzip MapLibre.

### Cited Findings
- [FAKTA-WEB] Resolusi layar seluler Indonesia, Agustus 2026: 360×800 9.75%, 414×896 6.01%, 393×873 4.66%, 390×844 4.24%, 385×854 3.24%, 360×806 3.19% — [StatCounter Screen Resolution Indonesia](https://gs.statcounter.com/screen-resolution-stats/mobile/indonesia)
- [FAKTA-WEB] WCAG 1.4.10 Reflow: tanpa gulir dua dimensi pada lebar setara 320 CSS px — [W3C Understanding 1.4.10](https://www.w3.org/WAI/WCAG22/Understanding/reflow.html) (tidak dibuka di sesi ini).
- [FAKTA-WEB] Size container queries dan unit container query dirilis di Chrome 105, Edge 105, Firefox 110, dan Safari 16 (posting 14 Feb 2023) — [web.dev: Container queries land in stable browsers](https://web.dev/blog/cq-stable). Tailwind v3 tidak punya varian `@container` di core; tersedia plugin resmi `@tailwindcss/container-queries` — [GitHub tailwindlabs/tailwindcss-container-queries](https://github.com/tailwindlabs/tailwindcss-container-queries) (tidak dibuka di sesi ini).
- [FAKTA-WEB] Unit viewport `svh`/`lvh`/`dvh`: `100vh` *"will bleed out of the viewport"* saat toolbar dinamis terlihat. `dv*` berada di antara `sv*` dan `lv*`. Dukungan: Chrome 108, Firefox 101, Safari 15.4 (posting 29 Nov 2022) — [web.dev: viewport units](https://web.dev/blog/viewport-units)
- [FAKTA-LOKAL] CSS terkompilasi memuat `.min-h-dvh{min-height:100dvh}` dan `.min-h-screen{min-height:100vh}`. Layout utama masih `min-h-screen` (`app.blade.php:38`, `guest.blade.php:38`).
- [FAKTA-WEB] Sejak Chrome 108 di Android, keyboard virtual hanya mengecilkan *visual viewport*, bukan layout viewport (menyamai iOS). Kunci `interactive-widget` pada meta viewport punya nilai `resizes-visual` | `resizes-content` | `overlays-content`, misalnya `<meta name="viewport" content="width=device-width, initial-scale=1.0, interactive-widget=resizes-content">` — [Chrome for Developers: viewport resize behavior](https://developer.chrome.com/blog/viewport-resize-behavior)
- [FAKTA-WEB] iOS Safari melakukan zoom otomatis saat fokus pada input ber-font di bawah 16px. Solusinya font input ≥16px; `maximum-scale=1` tidak dianjurkan karena merugikan aksesibilitas — [CSS-Tricks: 16px or Larger Text Prevents iOS Form Zoom](https://css-tricks.com/16px-or-larger-text-prevents-ios-form-zoom/); [Defensive CSS: Input zoom on iOS Safari](https://defensivecss.dev/tip/input-zoom-safari/)
- [FAKTA-LOKAL] `@tailwindcss/forms` menetapkan `font-size` input ke ukuran base 1rem (`src/index.js:5, 60`). Grep menemukan 0 tag `<input|select|textarea>` satu-baris ber-`text-xs/sm`, sehingga saat ini aman dari auto-zoom. Tag multi-baris tidak tercakup grep.
- [FAKTA-WEB] WCAG 2.5.8 Target Size (Minimum), AA: *"at least 24 by 24 CSS pixels"*, dengan pengecualian Spacing, Equivalent, Inline, User Agent Control, dan Essential — [W3C Understanding 2.5.8](https://www.w3.org/WAI/WCAG22/Understanding/target-size-minimum.html). 44×44 adalah 2.5.5 (AAA) — [W3C Understanding 2.5.5](https://www.w3.org/WAI/WCAG22/Understanding/target-size-enhanced.html) (tidak dibuka di sesi ini). Standar proyek memakai sekitar 44×44 px untuk kontrol utama lapangan (CLAUDE.md §6). Contoh yang sudah ada: `min-h-11` di `guest.blade.php:43`.
- [FAKTA-WEB] WCAG 1.3.4 Orientation (AA): *"Content does not restrict its view and operation to a single display orientation, such as portrait or landscape, unless a specific display orientation is essential."* Failure **F97: locking the orientation to landscape or portrait view** — [W3C Understanding 1.3.4](https://www.w3.org/WAI/WCAG22/Understanding/orientation.html)
- [FAKTA-LOKAL] Manifest `'orientation' => 'portrait'` dan `'display' => 'standalone'` (`app/Http/Controllers/ProgressiveWebAppController.php:21-22`). Aplikasi terpasang terkunci potret; untuk tablet, foldable, dan dudukan landscape ini berisiko F97.
- [FAKTA-WEB] `theme-color` mendukung atribut `media`, misalnya `<meta name="theme-color" content="…" media="(prefers-color-scheme: dark)">` — [MDN theme-color](https://developer.mozilla.org/en-US/docs/Web/HTML/Reference/Elements/meta/name/theme-color). Chrome menghormati atribut `media` untuk PWA terpasang di desktop dan untuk semua situs di Android (sejak sekitar Chrome 93) — [blink-dev Intent to Ship](https://groups.google.com/a/chromium.org/g/blink-dev/c/6I-I3lZWy5k/m/kjqDF1ARBQAJ); [CSS-Tricks: Meta Theme Color and Trickery](https://css-tricks.com/meta-theme-color-and-trickery/)
- [FAKTA-WEB] **Safari 26 (iOS 26) tidak lagi memakai `theme-color`** untuk mewarnai toolbar. Safari memindai elemen `position: fixed/sticky` di tepi viewport (dalam 4 px dari atas atau 3 px dari bawah di iOS, lebar ≥80% di iOS, tinggi ≥3 px) dan membaca `background-color`/`backdrop-filter`-nya, lalu jatuh ke latar `body`, kemudian `html` — [jahir.dev, 2 Mar 2026](https://jahir.dev/blog/safari-toolbar); [Ben Frain](https://benfrain.com/ios26-safari-theme-color-tab-tinting-with-fixed-position-elements/); [1ar.io](https://1ar.io/updates/safari-26-liquid-glass-web/). Sumber primer WebKit tidak ditemukan, jadi detail ambang **[UNVERIFIED]**.
- [FAKTA-WEB] Manifest web app hanya punya satu `theme_color` dan satu `background_color`. Ada proposal untuk skema gelap: [w3c/manifest#975](https://github.com/w3c/manifest/issues/975), PR `color_scheme_dark` [#1205, diskusi Jan 2026](https://lists.w3.org/Archives/Public/public-webapps-github/2026Jan/0495.html), dan [blink-dev: Intent to Prototype dark mode for web apps](https://groups.google.com/a/chromium.org/g/blink-dev/c/Y6zNtG0f-6A/m/cOhkfVGSBAAJ). Status rilis di peramban **[UNVERIFIED]**.
- [FAKTA-WEB] INP baik ≤200 ms, perlu perbaikan >200–≤500 ms, buruk >500 ms, diukur pada persentil ke-75 kunjungan lapangan dan disegmentasi seluler/desktop — [web.dev INP](https://web.dev/articles/inp)
- [FAKTA-WEB] *The Performance Inequality Gap, 2026* (24 Nov 2025, diperbarui 28 Nov 2025):
  - perangkat referensi P75: **Samsung Galaxy A24 4G** (MediaTek Helio G99)
  - jaringan referensi: **9 Mbps turun / 3 Mbps naik / RTT 100 ms**
  - anggaran jalur kritis:

    | Target | JS-light total | JS-light JS | JS-heavy total | JS-heavy JS |
    |---|---|---|---|---|
    | 3 detik | 2.0 MiB | **0.3 MiB** | 1.2 MiB | 0.62 MiB |
    | 5 detik | 3.7 MiB | 0.57 MiB | 2.3 MiB | 1.15 MiB |

  - kinerja single-core kelas bawah *"still 9x slower than contemporary iPhones"*, kelas menengah lebih dari 3,5× lebih lambat
  
  Sumber: [infrequently.org](https://infrequently.org/2025/11/performance-inequality-gap-2026/). Ringkasan mesin pencari membingkainya sebagai "~150KiB HTML/CSS/fonts dan ~300–350KiB JS"; angka tabel artikel yang dipakai di sini.
- [FAKTA-LOKAL, diukur dari `public/build/assets`, build 2026-09-23 14:42]:

  | Aset | Raw | Gzip | Catatan |
  |---|---|---|---|
  | `app-*.css` | 55,593 B | 10,249 B | |
  | `app-*.js` | 2,332 B | 1,243 B | |
  | `jejak-*.js` | — | 880 B | lazy |
  | `maplibre-gl-*.js` | 786,570 B | **207,716 B** | lazy, hanya mode pendakian |
  | CSS MapLibre | — | 9,220 B | |
  | `livewire.min.js` | 166,959 B | **56,841 B** | diinjeksi otomatis, `livewire.inject_assets = true` |

- [FAKTA-WEB] Tailwind v4 membungkus `hover:` dalam `@media (hover: hover)` agar tidak "lengket" di perangkat sentuh — [Tailwind Upgrade Guide](https://tailwindcss.com/docs/upgrade-guide). Di v3 hover tetap berlaku saat disentuh.
- [FAKTA-LOKAL] Reduced motion sudah ditangani global (`app.css` sekitar baris 257-266). Service worker hanya menyimpan shell, sedangkan halaman selalu diambil dari jaringan (komentar `resources/js/app.js`), konsisten dengan R-013.

### Inferences
- [REKOMENDASI] **Breakpoint** (`sm` 640, `md` 768, `lg` 1024) dipakai untuk tata letak halaman. **Container query** cocok untuk komponen yang tampil di lebar berbeda (mis. `trail-row` di daftar, perbandingan, dan tray), tetapi di v3 itu berarti menambah plugin **[PERLU PERSETUJUAN PEMILIK]**. Tanpa plugin, CSS `@container` murni di `app.css` tetap bisa dipakai untuk kasus terbatas [INFERENSI].
- [REKOMENDASI] Ganti `min-h-screen` dengan `min-h-dvh` di kedua layout. Untuk bar aksi tetap di mode pendakian, tambahkan `viewport-fit=cover` ke meta viewport dan `padding-bottom: max(1rem, env(safe-area-inset-bottom))` — [MDN env()](https://developer.mozilla.org/en-US/docs/Web/CSS/env); [WebKit: Designing Websites for iPhone X](https://webkit.org/blog/7929/designing-websites-for-iphone-x/) (keduanya tidak dibuka di sesi ini). Pastikan bar tetap tidak menutupi elemen yang difokus — [WCAG 2.4.11 Focus Not Obscured](https://www.w3.org/WAI/WCAG22/Understanding/focus-not-obscured-minimum.html) (tidak dibuka di sesi ini).
- [REKOMENDASI] Peta layar penuh di mode pendakian: `overscroll-behavior: contain` pada kontainer peta supaya gestur geser tidak memicu pull-to-refresh atau scroll halaman [INFERENSI; perlu uji di Android Chrome dan iOS Safari].
- [REKOMENDASI] Ubah `orientation` manifest dari `portrait` ke `any` (atau hapus) **[PERLU PERSETUJUAN PEMILIK]**, karena ini mengubah perilaku aplikasi terpasang. Foldable dan tablet ditangani dengan tata letak cair dari 360 sampai sekitar 900 px, bukan deteksi perangkat.
- [REKOMENDASI] **Safari 26:** beri `html, body { background-color: rgb(var(--canvas)) }`. Saat ini latar hanya ada di div/main dalam, sehingga Safari mengambil warna default. Header sticky atau bar bawah tetap memakai token permukaan supaya pewarnaan toolbar mengikuti tema. **Android:** dua `theme-color` ber-`media` untuk mode sistem, satu meta tanpa `media` untuk override eksplisit (Q2). **Manifest:** tetap satu warna sampai proposal `color_scheme_dark` dirilis.
- [INFERENSI] Anggaran: jalur kritis JS saat ini sekitar 58 KB gzip (Livewire+Alpine+app), jauh di bawah 0.3 MiB. Mode pendakian menjadi sekitar 266 KB gzip, mendekati batas JS 3 detik. Biaya parse/compile MapLibre pada Helio G99 ke bawah adalah risiko INP, jadi ukur INP lapangan di perangkat kelas A0x/A1x. Mode gelap sendiri menambah biaya yang dapat diabaikan: skrip inline beberapa ratus byte dan sekitar 40 baris variabel CSS.
- [REKOMENDASI] Hindari `backdrop-filter`/blur dan bayangan besar sebagai penanda elevasi gelap. Keduanya mahal dicat di GPU kelas bawah [INFERENSI]; border lebih murah.
- [REKOMENDASI] Pembaca layar di ponsel: kontrol tema berupa radio native berlabel (Q2). Uji di TalkBack dan VoiceOver sesuai `docs/PROTOKOL-UJI-KEGUNAAN.md`. Bila `interactive-widget=resizes-content` dipakai untuk form panjang, uji ulang posisi bar tetap saat keyboard terbuka.

### Gaps
- Prevalensi foldable dan tablet di antara pengguna Indonesia tidak ditemukan.
- Detail primer WebKit untuk algoritma pewarnaan toolbar Safari 26 dan perilakunya di mode standalone (Layar Utama) tidak ditemukan.
- Pangsa Samsung Internet dan versi WebView di Indonesia tidak ditemukan.
- INP lapangan aplikasi ini belum pernah diukur. Belum ada data RUM di repo, dan tidak dicari.

---

## 6. Lanskap perangkat dan jaringan Indonesia 2025–2026

### Takeaway
Android dominan, tetapi iOS menyumbang lebih dari seperlima **lalu lintas web** (StatCounter Agustus 2026: Android 77.07%, iOS 22.88%). Viewport terbanyak adalah 360×800. Pengapalan dipimpin merek bernilai (Xiaomi, Transsion, Samsung, OPPO, vivo, masing-masing sekitar 15–19% pada 2025), dengan pergeseran dari kelas di bawah US$200 ke kelas menengah. Median unduhan seluler sekitar 45 Mbps (Ookla, Agustus 2025), tetapi median menyembunyikan wilayah pegunungan dan pinggiran jangkauan. Rancang untuk Android kelas menengah-bawah dan uji serius di iOS Safari.

### Cited Findings
- [FAKTA-WEB] Pangsa OS seluler Indonesia, Agustus 2026: **Android 77.07%, iOS 22.88%**, Linux 0.04%, Windows 0.01%, BlackBerry 0.01% — [StatCounter Mobile OS Indonesia](https://gs.statcounter.com/os-market-share/mobile/indonesia). Metodologi StatCounter berbasis page view, bukan basis terpasang.
- [FAKTA-WEB] Vendor seluler (lalu lintas) Indonesia, Agustus 2026: Unknown 23.3%, Apple 22.87%, Samsung 13.34%, Oppo 12.14%, Xiaomi 10.01%, Vivo 8.66% — [StatCounter Vendor Indonesia](https://gs.statcounter.com/vendor-market-share/mobile/indonesia)
- [FAKTA-WEB] Resolusi terbanyak: 360×800 (9.75%), 414×896, 393×873, 390×844, 385×854, 360×806 — [StatCounter Screen Resolution Indonesia](https://gs.statcounter.com/screen-resolution-stats/mobile/indonesia)
- [FAKTA-WEB] DataReportal *Digital 2026: Indonesia* [sumber](https://datareportal.com/reports/digital-2026-indonesia):
  - 230 juta pengguna internet (Oktober 2025), penetrasi 80.5%, +18 juta (+8.7%)
  - 331 juta koneksi seluler (116% populasi), **96.6% broadband** (3G/4G/5G)
  - median unduhan **seluler 45.01 Mbps** (Ookla, Agustus 2025; +15.61 Mbps, +53.1% YoY)
  - median unduhan tetap 39.88 Mbps
- [FAKTA-WEB] IDC: pertumbuhan 2024 digerakkan segmen ultra-murah (<US$100) yang dipimpin Transsion. Segmen <US$200 tumbuh 17.8% pada Q1 2024 — [IDC press release 2024](https://my.idc.com/getdoc.jsp?containerId=prAP53189225); [I-Connect007, IDC Q1 2024](https://iconnect007.com/article/140783/idc-indonesias-smartphone-market-starts-the-year-with-a-strong-274-growth-in-1q24/140780/smt) (via cuplikan pencarian).
- [FAKTA-WEB] Tahun 2025: pengapalan di bawah US$200 turun 5.7% YoY karena pergeseran ke kelas menengah (ringkasan mesin pencari atas data IDC; **[UNVERIFIED]** terhadap rilis primer). Pangsa vendor 2025: Xiaomi 19%, Transsion 18%, Samsung 17%, OPPO 16%, vivo 15%. Transsion menguasai lebih dari 18.3% lewat 5G ultra-terjangkau di bawah Rp3.000.000 — [TelecomLead, Indonesia Smartphone Market Share 2025](https://telecomlead.com/smart-phone/indonesia-smartphone-market-share-2025-xiaomi-transsion-samsung-oppo-and-vivo-battle-in-value-first-era-amid-pricing-pressures-124688) (via cuplikan pencarian).
- [FAKTA-WEB] Q2 2026: *"Transsion Leads with 26% as Xiaomi, Samsung, OPPO and vivo Battle Rising Prices"* — [TelecomLead, Q2 2026](https://telecomlead.com/smart-phone/indonesia-smartphone-market-q2-2026-transsion-leads-with-26-as-xiaomi-samsung-oppo-and-vivo-battle-rising-prices-127494) (judul; isi tidak dibuka).
- [FAKTA-WEB] Opensignal *Indonesia Mobile Network Experience*, Juli 2026 (data 1 April–29 Juni 2026) mencakup Indosat, Telkomsel, dan XL. XL memenangkan kecepatan unduh dan unggah, keseluruhan dan 5G. Skor 5G Download XL naik dari 48.2 ke 135.4 Mbps, kecepatan unduh keseluruhan XL naik sekitar 43.5%, dan Reliability XL 901/1000, delapan poin di atas Indosat — [Opensignal, Juli 2026](https://insights.opensignal.com/reports/2026/07/indonesia/mobile-network-experience) (halaman 403 bagi fetcher; angka dari cuplikan pencarian).
- [FAKTA-WEB] Global: *"Most phones — indeed, most computers — are 24+ month old Androids."* iOS tidak pernah melebihi 20% pengapalan perangkat baru walau menyumbang lebih dari 40% lalu lintas seluler lewat pasar rekondisi — [infrequently.org, 2026](https://infrequently.org/2025/11/performance-inequality-gap-2026/)

### Inferences
- [INFERENSI] Pangsa iOS di lalu lintas web Indonesia (sekitar 23%) jauh lebih tinggi daripada pangsanya di pengapalan. Pengguna iOS terwakili berlebih di web, jadi iOS Safari 26 (pewarnaan toolbar, 100vh, zoom input) wajib ada di matriks uji.
- [INFERENSI] **[UNVERIFIED]** Porsi "Unknown" 23.3% di data vendor kemungkinan perangkat Android yang model-nya tersembunyi akibat reduksi User-Agent Chrome.
- [REKOMENDASI] Perangkat acuan desain dan uji: Android kelas Samsung Galaxy A-series, Redmi, atau Infinix dengan SoC kelas Helio G, lebar 360 px, 4G dengan latensi bervariasi. Profil jaringan uji mengikuti referensi P75 Russell (9/3 Mbps, 100 ms). Median nasional 45 Mbps tidak mewakili basecamp atau jalur.
- [INFERENSI] Di jalur pendakian sinyal sering hilang, sehingga keandalan tema tidak boleh bergantung pada jaringan. Desain A (Q2) memenuhi ini karena CSS dan skrip inline, tanpa permintaan tambahan.

### Gaps
- Angka nasional Opensignal (kecepatan per operator selain XL, ketersediaan 4G/5G) tidak dapat dibuka (403).
- Data cakupan sinyal di kawasan gunung atau taman nasional tidak ditemukan.
- Distribusi RAM, SoC, dan umur perangkat khusus Indonesia tidak ditemukan. Acuan P75 bersifat global.
- Pangsa panel OLED vs LCD dan pangsa peramban (Chrome, Samsung Internet, WebView in-app) di Indonesia tidak ditemukan.

---

## 7. Strategi pengujian: memperluas test kontras ke token gelap, pemeriksaan peramban, dan opsi visual regression yang kompatibel dengan stack

### Takeaway
`ColourContrastTest` saat ini **hanya membaca definisi token pertama** di `app.css`. Begitu blok gelap ditambahkan, test akan tetap hijau tanpa pernah memeriksa nilai gelap: sebuah kelengkapan semu. Parser perlu dibuat sadar-blok, setiap pasangan diuji di kedua skema, dan ditambah test paritas antarblok. Pemeriksaan runtime wajib dilakukan di peramban dengan emulasi `prefers-color-scheme` dan navigasi `wire:navigate` nyata. Visual regression tersedia lewat Pest 4 Browser atau Playwright; keduanya **dependensi baru** yang memerlukan persetujuan karena proyek memakai PHPUnit 12.5.35 tanpa Pest atau Dusk.

### Cited Findings
- [FAKTA-LOKAL] `tests/Feature/ColourContrastTest.php`:
  - `token()` (baris 250-262) memakai `preg_match` dengan pola `/--nama:\s*(\d+)\s+(\d+)\s+(\d+);/` terhadap seluruh `app.css`. `preg_match` hanya mengembalikan **kecocokan pertama**.
  - Konstanta `NORMAL_TEXT = 4.5` (22) dan `LARGE_TEXT_AND_UI = 3.0` (24); data provider `textPairs()` (31).
  - Test yang ada: batas kontrol vs putih (84), focus ring `brand-600` vs putih (212), larangan warna semantik mentah di view (195), dan keberadaan token (232).
- [FAKTA-WEB] Pest Browser Testing:
  - pemasangan: `composer require pestphp/pest-plugin-browser --dev`, `npm install playwright@latest`, `npx playwright install`
  - emulasi: `visit('/')->inDarkMode()`, `->on()->mobile()`, `->on()->iPhone14Pro()`, `->resize(1280, 720)`
  - asersi: `->assertScreenshotMatches()` (baseline di `tests/Browser/Screenshots`), `->assertNoAccessibilityIssues()`, `->assertNoSmoke()`, `->assertNoJavaScriptErrors()`, `->assertNoConsoleLogs()`
  
  Dokumen tidak menyebut kompatibilitas dengan proyek PHPUnit murni atau Livewire secara eksplisit — [Pest Browser Testing](https://pestphp.com/docs/browser-testing)
- [FAKTA-WEB] Playwright: `test.use({ colorScheme: 'dark' })`, `await page.emulateMedia({ colorScheme: 'dark' })`, descriptor perangkat `devices['iPhone 13']`, `viewport: { width, height }`, `offline: true` — [Playwright Emulation](https://playwright.dev/docs/emulation). Perbandingan screenshot dengan `expect(page).toHaveScreenshot()` — [Playwright Visual comparisons](https://playwright.dev/docs/test-snapshots) (tidak dibuka di sesi ini).
- [FAKTA-WEB] Chrome DevTools Rendering dapat mengemulasi `prefers-color-scheme`, `prefers-reduced-motion`, `forced-colors`, dan defisiensi penglihatan — [Chrome DevTools: Emulate CSS media features](https://developer.chrome.com/docs/devtools/rendering/emulate-css) (tidak dibuka di sesi ini).
- [FAKTA-WEB] Apple HIG meminta pengujian di kedua tampilan dengan Increase Contrast dan Reduce Transparency, sendiri-sendiri maupun bersamaan — [Apple HIG Dark Mode](https://developer.apple.com/design/human-interface-guidelines/dark-mode)
- [FAKTA-LOKAL] Kontrak proyek: *"Uji layar yang sebenarnya; kelas CSS bukan bukti"* (CLAUDE.md §6) dan *"Tanpa bukti, tidak ada PASS"* (§5).

### Inferences
- [REKOMENDASI] **Perluasan `ColourContrastTest`, tanpa dependensi baru:**
  1. Parser blok: blok terang dari `:root {` pertama; blok gelap dari `:root[data-theme='dark'] {`; blok media dari `@media (prefers-color-scheme: dark) { :root:not([data-theme='light']) {`. Kembalikan peta token per skema.
  2. Test paritas: himpunan nama token di blok gelap-atribut **identik** dengan blok gelap-media dan nilai-nilainya sama, sehingga duplikasi Desain A tidak bisa melenceng. Setiap token terang punya padanan gelap.
  3. Data provider `textPairs() × {light, dark}`. Ambang tetap 4.5 dan 3.0. Untuk teks info kritis mode pendakian, tambahkan pasangan ≥7.0 bila pemilik menyetujui target itu.
  4. Pasangan non-teks gelap baru: `control-border` vs canvas, surface, dan sunken; `--focus-ring` vs canvas dan surface; garis profil elevasi vs surface (menangkap kegagalan 2.77:1 bila `brand-700` tidak diremap); `--on-brand` vs `brand-700`.
  5. Test "tidak ada `dark:` di view" dan "tidak ada `bg-white`/`text-black` mentah" dengan pola yang sama seperti `test_no_view_uses_a_raw_semantic_colour`.
- [REKOMENDASI] **Feature test HTTP (PHPUnit, tanpa dependensi):**
  - layout merender `<meta name="color-scheme" content="light dark">` dan dua `theme-color` ber-`media` saat tanpa cookie
  - dengan cookie `tema=dark`, respons memuat `data-theme="dark"` dan satu `theme-color` tanpa `media`
  - cookie bernilai liar (mis. `"><script>`) diabaikan (whitelist, uji keamanan)
  - cookie `tema` tidak dienkripsi (`encryptCookies(except:)`)
  - manifest tidak lagi mengunci orientasi bila perubahan itu disetujui
- [REKOMENDASI] **Pemeriksaan runtime (manual atau via MCP Chrome DevTools yang tersedia di lingkungan agen):**
  - emulasi terang dan gelap, lalu navigasi antarhalaman lewat `wire:navigate` dan tombol back/forward dengan throttling CPU 4× dan "Slow 4G"; rekam filmstrip Performance untuk membuktikan tidak ada kilatan
  - input form (latar tidak putih)
  - peta dengan atribusi terbaca
  - Android Chrome dengan "auto-darken" aktif
  - iOS Safari 26: pewarnaan toolbar, `dvh`, keyboard
  - TalkBack dan VoiceOver pada kontrol tema
  - Lighthouse aksesibilitas di kedua skema
- [REKOMENDASI] **Visual regression, pilih satu [PERLU PERSETUJUAN PEMILIK, dependensi baru]:**
  - Pest 4 Browser: PHP-native, terintegrasi dengan factory dan DB Laravel, punya `inDarkMode()` dan `assertNoAccessibilityIssues()`. Proyek ini PHPUnit murni, jadi menambah Pest adalah perubahan toolchain test.
  - Playwright Test (Node): butuh server berjalan dan data seed.
  
  Matriks baseline minimal: {terang, gelap} × {360×800, 390×844, 768×1024, 1280×800} untuk halaman daftar jalur, detail jalur, perbandingan, mode pendakian (peta), form masuk, dan pengaturan tema.
- [INFERENSI] R-024 (paritas PostgreSQL) hanya relevan bila kolom preferensi tema di tabel `users` disetujui. Migrasinya wajib diuji di PostgreSQL, bukan hanya SQLite.

### Gaps
- Kompatibilitas plugin browser Pest dengan proyek yang hanya memakai PHPUnit, serta dengan Livewire `wire:navigate`, tidak dinyatakan di dokumentasinya.
- Apakah Laravel Dusk punya emulasi `prefers-color-scheme` bawaan tidak diteliti. Dusk juga tidak terpasang.
- Belum ada data apakah `onSwap` mencegah kilatan pada perangkat kelas bawah nyata; ini harus dibuktikan dengan rekaman runtime, bukan diasumsikan.
