# Memount v3 W1 Fondasi Desain Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Setiap halaman yang ada langsung memakai token warna, huruf, dan ikon v3 dari prototype pemilik, primitif
desain siap disusun W2 sampai W8, data contoh berlabel DATA CONTOH siap dijalankan pemilik, dan tangkapan 390 px serta
1440 px dapat dibuat tanpa basis data lokal.

**Architecture:** Nilai desain tetap satu sumber di `resources/css/app.css` (token RGB per peran untuk tema terang,
gelap, dan dua tema lapangan) yang dipetakan `tailwind.config.js` ke kelas bernama. Primitif tinggal di
`resources/js/components/ui/` (ruling W1-R1) dan dijaga test PHPUnit yang membaca sumber, karena proyek belum punya
test runner JavaScript dan dependensi baru tidak diizinkan. Tangkapan visual dibuat suite PHPUnit terpisah `Pratinjau`
yang menulis HTML halaman berisi data contoh SQLite dalam memori ke `public/_pratinjau/`, lalu disajikan statis.

**Tech Stack:** PHP 8.5.10, Laravel 13.31.0, Inertia Laravel 3.3.4, `@inertiajs/react` 3.7.1, React 19.3.0,
TypeScript 5.9.3, Vite 8.3.0 (Rolldown), Tailwind CSS 3.4.19, `@tailwindcss/forms` 0.5.11, PHPUnit 12.5.35, Pint,
SQLite dalam memori untuk test, Phosphor Icons core 2.1.1 (path ditanam, MIT), Plus Jakarta Sans variable (OFL 1.1,
dihosting sendiri).

**Spec:** `docs/superpowers/specs/2026-09-25-memount-v3-prototype-design.md` (bagian 8, 9.1, 11, 14, 15; DK-30,
DK-31, DK-35, DK-38, DK-39), bersama rencana induk `docs/superpowers/plans/2026-09-26-memount-v3-induk.md` (Global
Constraints, Review Focus, bagian W1 butir 1 sampai 8) dan laporan riset
docs/riset/2026-09-25-merek/reports/Tipografi dan ikon merek Memount.md. Ruling koordinator W1-R1 sampai W1-R6 dan
aturan eksekusi `.superpowers/sdd/2026-09-26-memount-v3-wr-struktur-repo/aturan-eksekusi.md` ikut mengikat.

## Global Constraints

- Versi dikunci: PHP 8.5, Laravel 13.31, React 19.3, `@inertiajs/react` 3.7.1, Tailwind 3.4, Vite 8, MapLibre 4.7.x, TypeScript 5.9; tidak ada perubahan versi kerangka (R-020).
- Tidak ada dependensi baru, runtime maupun dev (DK-24, spec bagian 15 Q3 dan Q17). Unduhan hanya yang disebut rencana ini dengan URL, lisensi, dan pemeriksaannya: dua berkas WOFF2 dan teks OFL Plus Jakarta Sans dari `cdn.jsdelivr.net/npm/@fontsource-variable/plus-jakarta-sans@5.3.0` (Tugas 2), dan pemeriksaan ulang path SVG Phosphor dari `cdn.jsdelivr.net/npm/@phosphor-icons/core@2.1.1` (Tugas 3). Tidak memasang alat subset huruf atau pengoptimal SVG.
- Tidak ada commit, push, akses Supabase, atau migrasi di basis data nyata. Setiap langkah "Commit" berbunyi: "Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah."
- `php artisan migrate` dalam bentuk apa pun, `db:seed`, `db:wipe`, dan tinker ditolak `.claude/settings.json` dan tidak dipakai. Migrasi dan seeder hanya berjalan di dalam PHPUnit (SQLite dalam memori). `memount:demo` ditulis dan diuji penjaganya, tetapi tidak ada langkah yang menjalankannya (W1-R2, Q1).
- Setiap perintah Artisan yang mem-boot aplikasi di luar PHPUnit, `npm run build`, dan server statis `php -S` diberi awalan `DB_CONNECTION=sqlite DB_DATABASE=:memory:`; `.env` menunjuk Supabase produksi. `php artisan test ...` tidak butuh awalan. Jangan membaca atau mencetak `.env`.
- Bahasa Indonesia untuk teks antarmuka dan dokumen, sapaan "Anda", tanpa em dash (U+2014) di berkas mana pun, tanpa kata "aman" dan "layak berangkat" sebagai klaim (R-012, R-014). "§N" hanya untuk ID PRD; dokumen lain dirujuk "bagian N".
- Label kecocokan publik hanya "Cocok", "Perlu persiapan", "Kurang cocok"; "Belum dinilai" adalah keadaan UNKNOWN (R-004, R-006). Tidak ada skor atau persen untuk kecocokan dan kesiapan (R-005, BR-09).
- Token hanya di `resources/css/app.css`; tidak ada warna mentah, `bg-white`, `text-black`, atau `dark:` di komponen. Teks ≥4,5:1, non-teks ≥3:1, informasi kritis tema lapangan ≥7:1.
- Huruf: satu keluarga Plus Jakarta Sans variable 200 sampai 800 (dipakai 400 sampai 800), dihosting sendiri, WOFF2 yang dimuat untuk teks Latin ≤100 KB, isi 16 px, lantai 12 px, `tabular-nums` untuk angka ukur; tanpa Geist, Geist Mono, Newsreader, dan tanpa host huruf luar.
- Ikon: Phosphor dengan bobot per ukuran (Bold 16 sampai 19 px, Regular mulai 20 px, isi untuk tab aktif), tidak ada ikon informatif di bawah 16 px, label tombol tanpa panah hiasan. Hanya tiga glyph domain pilot (W1-R3, Q15 belum diberikan).
- Target sentuh kontrol utama ≥44 px; chip berbentuk 36 px di dalam target 44 px; kontrol lapangan 48 sampai 66 px.
- Gerak hanya dari daftar tertutup spec bagian 8.6 dengan token durasi; `prefers-reduced-motion` dan `data-gerak="kurangi"` memangkasnya menjadi 1 ms.
- Kaca paling banyak dua elemen per layar lewat `.chrome`; bayangan hanya `shadow-overlay` untuk lapisan melayang.
- Test yang menyandikan keputusan lama diperbarui menurut niat baru dengan alasan tertulis di docblock-nya; tidak dihapus dan tidak dilonggarkan diam-diam (spec bagian 14).
- `docs/lisensi-pihak-ketiga.md` tetap akurat; `ThirdPartyLicenseTest` mewajibkan nama Phosphor Icons, Plus Jakarta Sans, OpenStreetMap, BMKG, dan jalur lisensi Phosphor.
- Di dokumen hidup, setiap jalur `.md` di antara backtick harus ada di repo (`PrdReferenceIntegrityTest`); jalur yang mengandung spasi ditulis tanpa backtick.
- Tugas dikerjakan berurutan, satu implementer satu waktu. Berkas bersama koordinator (`resources/css/app.css`, `tailwind.config.js`, `resources/js/layouts/AppLayout.tsx`, `resources/js/layouts/NavLinks.tsx`, `resources/js/types/index.d.ts`, `routes/web.php`, `app/Http/Middleware/HandleInertiaRequests.php`) hanya disunting tugas yang menyebutnya di daftar Files; `HANDOVER.md` dan `CLAUDE.md` hanya disunting koordinator di Tugas 12.
- Test dibuat dengan `DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan make:test --phpunit --no-interaction <Nama>` lalu isinya diganti; setelah menyunting PHP jalankan `vendor/bin/pint --format agent <berkas>` untuk berkas tugas itu. Setelah menyunting TypeScript jalankan `npx tsc --noEmit`.

## Review Focus

- **Data lapangan kosong** (basis data pemilik: 0 jalur bergeometri, sehingga 0 jalur terbit di PostgreSQL): Jelajah tanpa jalur terbit dan tanpa saringan harus menampilkan keadaan kosong jenis "menunggu" yang menyebut siapa yang ditunggu, bukan "Tidak ada jalur yang cocok" seolah saringan penyebabnya. Dijaga Tugas 5 `DesignPrimitivesTest::test_jelajah_names_who_is_awaited_when_nothing_is_published` dan dipotret Tugas 10 (`jelajah-kosong.html`).
- **Teks Indonesia panjang di 360 px dan spasi teks WCAG 1.4.12** (nama jalur panjang, tafsiran kalimat, "Pemberitahuan ...", status resmi): label chip, segmen, lencana, tombol, sakelar, kotak centang, toast, bar aksi, dan bilah tab membungkus, tidak dipotong. Dijaga `LongInputLayoutTest::test_a_primitive_label_wraps_instead_of_being_cut` (Tugas 5, 6, 7) dan `test_the_showcase_exercises_long_indonesian_labels_at_360px` (Tugas 8), dilihat di tangkapan 390 px (Tugas 12).
- **Pengguna keyboard dan pembaca layar di lembar dan dialog**: Escape menutup, fokus kembali ke pemicunya, dan bila pemicunya sudah hilang dari DOM fokus jatuh ke isi utama, bukan ke `<body>`. Dijaga Tugas 7 `DesignPrimitivesTest::test_the_sheet_and_dialog_use_the_native_modal_and_return_focus` dan klik-lewat Tugas 12.
- **Toast urungkan yang hilang sebelum sempat dibaca atau ditekan** (WCAG 2.2.1): minimal 8 detik, berhenti saat disentuh penunjuk atau fokus, dan dapat ditutup. Dijaga Tugas 7 `DesignPrimitivesTest::test_the_undo_toast_waits_for_the_reader`.
- **Huruf gagal dimuat atau jaringan lambat**: berkas yang dirujuk `@font-face` benar-benar ada, cadangan bermetrik mencegah lompatan tata letak, dan tidak ada host luar yang dapat diblokir CSP. Dijaga Tugas 2 `TypographySystemTest::test_every_font_face_points_to_a_file_that_exists_and_offers_weight_800`, `test_the_fallback_metrics_keep_the_layout_from_jumping`, dan `test_the_fonts_come_only_from_our_own_origin`.

---

## Bukti simulasi rencana

Kode di rencana ini sudah dijalankan tugas demi tugas di salinan repo di scratchpad sesi perencanaan: tanpa `.env`,
tanpa riwayat git asli, basis data hanya SQLite dalam memori milik PHPUnit, dan tanpa menjalankan `memount:demo`.
Setiap tugas diuji merah terhadap keadaan tugas sebelumnya lalu hijau, disusul suite Unit dan Feature penuh, `npx tsc
--noEmit`, Pint, dan build. Angka yang ditulis "pada simulasi rencana" di tiap tugas berasal dari sana. Hasil akhirnya:
1.522 test (1.521 lulus, 1 dilewati yang sudah ada), tsc bersih, Pint bersih, 104 berkas berubah atau baru. Halaman
pratinjau disajikan lewat router statis dan diperiksa di Chrome 390 px terang dan gelap: lembar dan dialog menutup
dengan Escape dan mengembalikan fokus ke pemicunya, toast Urungkan mengembalikan aksinya, tab aktif menyala, dan
Jelajah tanpa jalur terbit menampilkan keadaan kosong "menunggu".

## Keadaan awal yang terukur (FAKTA, 26 September 2026)

| Butir | Keadaan | Bukti |
|---|---|---|
| Versi terpasang | Tailwind 3.4.19, `@tailwindcss/forms` 0.5.11, React 19.3.0, `@inertiajs/react` 3.7.1, TypeScript 5.9.3, Vite 8.3.0, PHPUnit 12.5.35 | `node_modules/*/package.json`, `PHPUnit\Runner\Version::id()` |
| Token | `resources/css/app.css` masih skala bawaan Tailwind (emerald `4 120 87`, rose, sky); kanvas `250 250 248` | `resources/css/app.css` baris 29 sampai 145 |
| Huruf | Plus Jakarta Sans 400 sampai 700 dan Newsreader 400 sampai 600 dari `fonts.bunny.net`; bobot 800 tidak termuat; `h1` memakai serif | `resources/views/components/brand/head.blade.php:40`, `resources/css/app.css:371` |
| Rilis resmi Tokotype 2.7.1 | Zip 1.018.646 B tanpa WOFF2; variable TTF 182.180 B (di atas anggaran, konversi butuh alat baru) | GitHub `tokotype/PlusJakartaSans` rilis 2.7.1 |
| Subset Fontsource 5.3.0 | Latin wght 27.348 B (SHA-256 `153fc85b70298beeb1d61a5f723331649e7f23bb77302a66e61cb3e2fbdb5e79`), Latin Extended wght 21.728 B (`38e3b8fd8045048eb311d90170a4429ed2c8f405852dc3d91b5af8452758703f`), LICENSE berupa teks OFL 4.534 B (`e07fd1167c2aaaa6fb965dd66e1e73b13c9c95b76b2d648b6e938780f30b37af`); sumber `google/fonts`, hash cocok dengan daftar berkas jsDelivr | `data.jsdelivr.com/v1/packages/npm/@fontsource-variable/plus-jakarta-sans@5.3.0` |
| Ikon | `Icon.tsx` 18 path bobot regular dan fill, ukuran bawaan kelas `h-4 w-4`; path regular dan fill identik dengan `@phosphor-icons/core` 2.1.1; enam nama terdaftar tidak dipakai di mana pun (`path`, `mountains`, `shield-check`, `user-circle`, `scales`, `newspaper`) | Perbandingan path dengan jsDelivr; grep |
| Pemakaian ikon | Tiga `<Icon` di React (`CheckpointJourney.tsx:31-32` pada 12 px, `NavLinks.tsx:102`), dua `<x-ui.icon` di Blade (`checkpoint-journey.blade.php:90,92` pada 12 px) | grep |
| Komentar lisensi di bundel | Vite 8.3 membuang `/*!` saat meminifikasi: `public/build/assets/*.js` tidak memuat kata "Phosphor". `build.rolldownOptions.output.comments = { legal: true, annotation: false, jsdoc: false }` mempertahankannya (uji build di scratchpad) | HANDOVER bagian 0 butir 17 |
| Ukuran teks di bawah 12 px | Satu: `text-[0.65rem]` (10,4 px) label tab di `NavLinks.tsx:97` | grep `text-\[` |
| Teks putih di atas isian merek | 14 tempat di 13 berkas; di tema gelap v3 isian merek hijau muda `#8FD3A0`, dan putih di atasnya 1,75:1 | grep `text-white` |
| Warna cadangan peta | `warnaToken()` di lima berkas peta masih membawa heksa palet lama (`#047857`, `rgb(4, 120, 87)`, `#d97706`, `#e11d48`, `#44403c`) sebagai cadangan | grep `warnaToken(` |
| Palet lama | Garis komunitas lama (sky-500 di atas sky-50) hanya 2,60:1; tidak tertangkap karena pasangan non-teks hanya dihitung di tema gelap | Rumus WCAG 2.x atas `app.css` |
| Komponen domain di `components/ui/` | Pengimpor: CheckpointJourney 2, ElevationProfile 2, FactorBars 4, FitBadge 6, StatusBadge 7, Freshness 3 (24 impor), ditambah jalur di 5 test | grep impor dan `tests/Feature` |
| Pemakai `EmptyState` | 6 halaman | grep `<EmptyState` |
| Inertia 3 | Data halaman tertanam di `<script data-page="app" type="application/json">` lalu `<div id="app">` | `vendor/inertiajs/inertia-laravel/src/View/Components/App.php:29` |
| PHPUnit | Atribut `defaultTestSuite` dipecah koma (`Configuration::includeTestSuites()`) | `vendor/phpunit/phpunit/src/TextUI/Configuration/Configuration.php:1436` |
| Geometri di SQLite | `HasSpatialColumns::spatialSupported()` hanya `pgsql`; di SQLite kolom geografi tidak ditulis dan gerbang publikasi melewati syarat geometri | `app/Models/Concerns/HasSpatialColumns.php:14`, `app/Models/Trail.php:147` |
| Server bawaan PHP | `php -S -t public` tanpa router mengarahkan URI yang bukan berkas ke `public/index.php`, yang mem-boot Laravel dengan `.env` (Supabase) | Manual PHP "Built-in web server" |

## Keputusan rencana ini

| ID | Keputusan | Dasar |
|---|---|---|
| W1-K1 | Komponen domain (CheckpointJourney, ElevationProfile, FactorBars, FitBadge, StatusBadge, Freshness) tetap di `components/ui/` di W1 dan dipindah W3 bersama penulisan ulangnya. Memindah sekarang menyentuh 24 impor dan 5 test lalu W3 menulis ulang berkas yang sama | W1-R1; import terukur |
| W1-K2 | Huruf dari subset Latin dan Latin Extended Fontsource 5.3.0 (turunan `google/fonts` dari rilis Tokotype), karena rilis resmi tidak memuat WOFF2 dan TTF-nya 182 KB | Batasan anggaran ≤100 KB; larangan alat baru |
| W1-K3 | Tema gelap memakai isian tombol `#8FD3A0` dengan teks gelap sesuai spec bagian 8.1, lewat token baru `on-brand`; 14 pemakaian `text-white` di atas isian merek diganti `text-on-brand`, dan tanda centang serta titik radio bawaan `@tailwindcss/forms` digelapkan di tema gelap | Spec bagian 8.1; kontras 1,75:1 bila dibiarkan |
| W1-K4 | Status belum diketahui `#808682`, bukan `#8A8F8B`: nilai spec hanya 2,97:1 di kanvas (3,35:1 setelah koreksi). Koreksi terukur sejenis DK-31 | Rumus WCAG 2.x |
| W1-K5 | Chip berbentuk 36 px di dalam tombol 44 px: memenuhi spec bagian 8.4 (chip ≥36 px) dan dispatch (target ≥44 px) sekaligus, dan lolos `TapTargetTest` | Spec bagian 8.4; WCAG 2.5.8 |
| W1-K6 | Lembar bawah dan dialog memakai `<dialog>` asli dengan `showModal()`; hanya gerak buka yang dianimasikan (280 ms), tutup seketika. Daftar spec bagian 8.6 adalah daftar yang diizinkan, bukan yang diwajibkan | Keandalan; antislop R-32 |
| W1-K7 | Toast urungkan 8 detik dengan jeda saat disentuh atau difokus; saran ui-ux-pro-max "3 sampai 5 detik" ditolak untuk toast yang membawa aksi | WCAG 2.2.1 |
| W1-K8 | Harness pratinjau memakai router `tests/Pratinjau/statis.php` untuk `php -S`: tanpanya URI yang bukan berkas mem-boot aplikasi dengan `.env` Supabase. Perintahnya tetap `php -S 127.0.0.1:8100 -t public`, ditambah jalur router | W1-R4; temuan server bawaan PHP |
| W1-K9 | `phpunit.xml` mendapat `defaultTestSuite="Unit,Feature,Spatial"` supaya `php artisan test` tanpa `--testsuite` tidak ikut menulis pratinjau | W1-R4 ("CI dan gerbang tidak pernah menjalankannya") |
| W1-K10 | DemoSeeder tidak mengisi geometri: kolom geografi hanya ada di PostGIS, sedangkan data contoh wajib SQLite. Peta data contoh menampilkan keadaan "belum bergeometri" yang sama dengan produksi | DK-35; FAKTA geometri SQLite |
| W1-K11 | Pratinjau memakai DemoSeeder di SQLite dalam memori, sehingga isi contohnya satu sumber dengan data contoh lokal. Menjalankan seeder di dalam PHPUnit bukan menjalankan `memount:demo` | W1-R2, W1-R4 |
| W1-K12 | Registri ikon hanya memuat ikon yang dipakai W1 (14 Phosphor, 3 glyph); gelombang berikutnya menambah ikon dengan prosedur di docblock registri | YAGNI; spec bagian 8.3 |
| W1-K13 | Lencana memakai huruf kalimat tebal 13 px ("Data contoh", "Kritis"), bukan kapital bertracking lebar seperti di prototype; teks data contoh di basis data tetap diawali penanda "DATA CONTOH" | Spec bagian 8.2 (label kecil); ciri klaster "tampilan AI" |
| W1-K14 | HTML pratinjau disisipi satu skrip `history.replaceState` ke URL rute halamannya, supaya layout membaca alamat yang benar dan tab aktif tampil seperti di aplikasi berjalan; akibatnya tautan dan muat ulang di pratinjau berujung 404 dan dicatat di `CONTRIBUTING.md` | Terukur di simulasi: tanpa skrip itu tidak ada tab yang aktif di tangkapan |
| W1-K15 | Perintah penyaji data contoh yang dicetak `memount:demo` mengarahkan sesi, cache, antrean, berkas, dan surel ke driver lokal, bukan hanya `DB_CONNECTION` | `.env` menunjuk Supabase; hanya basis data yang diisolasi tidak cukup |
| W1-K16 | Warna cadangan `warnaToken()` di lima berkas peta ikut nilai v3 di Tugas 1 | Cadangan dipakai bila CSS belum terbaca; nilai lama menampilkan palet emerald |

### Konflik yang perlu ruling koordinator

1. **Spec bagian 11 menulis "sekitar sepuluh jalur dengan geometri yang disederhanakan" untuk data contoh SQLite.** Tidak
   mungkin tanpa mengubah arsitektur penyimpanan geometri (R-021). Rekomendasi: terima W1-K10 untuk W1; bila peta
   berisi dibutuhkan untuk peninjauan W3, putuskan di W3 antara kolom GeoJSON cadangan (butuh izin pemilik) atau
   peninjauan peta di PostGIS lokal Docker.
2. **Induk W1 butir 2 menyebut "dari rilis resmi Tokotype".** Rilis 2.7.1 tidak memuat WOFF2. Rekomendasi: W1-K2
   (dibolehkan dispatch sebagai cadangan), dicatat asal-usulnya di `docs/lisensi-pihak-ketiga.md`.
3. **Ruling W1-R4 menulis `php -S 127.0.0.1:8100 -t public` tanpa router.** Rekomendasi: W1-K8, karena tanpa router
   satu permintaan ke `/dashboard` atau `/sw.js` dapat membuka koneksi ke Supabase produksi.
4. **Dial `DESIGN.md` berubah dari ENERGY 1, MOTION 1 menjadi ENERGY 2, MOTION 2** mengikuti spec bagian 8.9. Bagian
   Turunan milik agen dan DK-30 mengizinkan penulisan ulangnya; dicatat di catatan tata kelola `DESIGN.md` (Tugas 11).

## Perbandingan skill (DK-57)

Urutan wewenang: arahan pemilik dan aturan kanonis, lalu `DESIGN.md` (arah), lalu antislop, lalu saran skill.
antislop berlaku mode 1 (selama pekerjaan) atas arahan pemilik yang sudah berdiri (memori "skill UI wajib digabung");
tidak ada pertanyaan baru kepada pemilik (ruling program P-R1).

| Skill | Diambil | Ditolak dan alasannya |
|---|---|---|
| antislop dan antislop-ui | Delivery Gate empat blok; R-02 tanpa em dash; R-08 tanpa panah hiasan; R-09 lencana hanya untuk keadaan nyata ("Data contoh", "Kritis", berhuruf kalimat); R-10 kaca ≤2; R-12 satu elevasi; R-23 avatar inisial untuk data contoh; R-26 setiap kontrol pameran berfungsi; R-27 keadaan kosong pertama kali, tersaring, dan menunggu dibedakan; R-32 Escape dan fokus terlihat; R-38 data contoh diberi label | Tidak ada; antislop adalah saringan di atas `DESIGN.md` |
| ui-ux-pro-max (`search.py` dijalankan untuk pembanding) | Phosphor sebagai pustaka ikon utama; aria-hidden untuk ikon di samping label; nama aksesibel pada tombol ikon; target ≥44 px; umpan balik tekan 80 sampai 150 ms; token semantik per tema; ukuran ikon sebagai token | Keluaran `--design-system` (Aurora UI, gradien, jingga `#EA580C`, Roboto, Lucide, animasi 8 sampai 12 detik) bertentangan dengan DK-30, DK-38, DK-39; paket `@phosphor-icons/react` (DK-24; path ditanam); "Persist Design System" ke folder `design-system/` (DK-57 mengarahkannya ke `DESIGN.md`); toast 3 sampai 5 detik (WCAG 2.2.1, W1-K7) |
| design-taste-frontend | Hanya transform dan opacity yang dianimasikan; reduced motion wajib; label di atas isian, tanpa placeholder sebagai label; tombol tanpa label terbungkus; skala z-index terdokumentasi; satu aturan radius yang ditulis; cadangan huruf bermetrik untuk CLS; bayangan berwarna netral hangat | Tailwind v4 dan Motion (R-020, DK-24); "jangan menggambar ikon sendiri" (DK-39 memilih glyph domain dari tanda merek); Geist dan Outfit (DK-38; Geist ditandai antislop R-06); larangan palet krem premium (kanvas `#F6F3EC` pilihan pemilik dengan alasan tertulis, spec bagian 8.1) |
| impeccable (`.claude/skills/impeccable/reference/operate.md`, `.claude/skills/impeccable/reference/craft-floor.md`) | Mode Operate: kosakata komponen konsisten, satu keluarga huruf cukup, gerak 150 sampai 250 ms hanya untuk keadaan; permukaan peramban (seleksi, kursor ketik, batang gulir, cincin fokus) bertema; lapisan melayang lewat `<dialog>`; setiap komponen punya keadaan fokus, aktif, nonaktif; tanpa eyebrow dan tanpa garis tepi kiri berwarna | Radius kartu 12 sampai 16 px (spec bagian 8.4 memakai 18 dan 22 px dari prototype); peluncur `impeccable context` tidak dijalankan karena mengunduh biner pada pemakaian pertama (unduhan di luar daftar rencana); dipakai jalur cadangan resminya, membaca `PRODUCT.md` dan `DESIGN.md` langsung |

**Pembacaan desain (antislop bagian 3):** Reading this as: fondasi sistem desain aplikasi perencanaan pendakian untuk
pendaki pemula Indonesia di ponsel, bahasa visual editorial utilitarian yang hangat (kertas, hijau hutan, data sebagai
visual), dial ENERGY 2 / RHYTHM 2 / MOTION 2; mode lapangan ENERGY 1 / MOTION 1.

**Inventaris skill untuk gelombang ini (CLAUDE.md bagian 7):** INTI: antislop (dengan antislop-ui, antislop-human,
antislop-layoutmobile, antislop-copywriting), impeccable, ui-ux-pro-max, design-taste-frontend, tailwindcss-development,
testing-best-practices, laravel-best-practices (perintah, seeder, konfigurasi), superpowers:test-driven-development.
PENDUKUNG: apple-design (lembar bawah dan tekan), emil-design-eng (umpan balik tekan), mobile-native (safe area, `dvh`),
modern-web-guidance (`<dialog>`, `:has()`), ecc:frontend-a11y. LINTAS: superpowers:verification-before-completion,
antislop Delivery Gate. N/A: slides, banner-design, brand dan brandkit (aset logo menunggu gerbang DK-16),
livewire-development dan volt-development (Livewire sudah dipensiunkan), deploying-to-cloud, write-swift.

## File Structure

**Dibuat:**
- `public/fonts/plus-jakarta-sans/plus-jakarta-sans-latin-wght-normal.woff2`, `plus-jakarta-sans-latin-ext-wght-normal.woff2`, `OFL.txt` (Tugas 2)
- `resources/js/components/ui/ikon/phosphor.ts` registri path per bobot (Tugas 3); `resources/js/components/ui/ikon/glyphDomain.ts` tiga glyph pilot (Tugas 4)
- `resources/js/components/ui/gaya.ts` kelas fokus dan tekan bersama; `TombolIkon.tsx`, `Chip.tsx`, `Lencana.tsx`, `Avatar.tsx` (Tugas 5); `KontrolSegmen.tsx`, `Sakelar.tsx`, `KotakCentang.tsx`, `Stepper.tsx` (Tugas 6); `Lembar.tsx`, `ToastUrungkan.tsx`, `BarAksi.tsx`, `BilahTab.tsx` (Tugas 7); `LencanaDataContoh.tsx` (Tugas 9)
- `routes/dev.php`, `resources/js/pages/Dev/Primitif.tsx` (Tugas 8)
- `app/Exceptions/DataContohDitolak.php`, `app/Support/PenjagaDataContoh.php`, `app/Console/Commands/MemountDemo.php`, `database/seeders/DemoSeeder.php` (Tugas 9)
- `tests/Pratinjau/PratinjauTestCase.php`, `tests/Pratinjau/HalamanPratinjauTest.php`, `tests/Pratinjau/PenyajiStatis.php`, `tests/Pratinjau/statis.php` (Tugas 10)
- Test baru: `TypeFloorTest` (Tugas 2), `DesignPrimitivesTest` (Tugas 5), `DevPrimitivesRouteTest` (Tugas 8), `DemoCommandGuardTest`, `DemoSeederTest`, `DemoBadgeTest` (Tugas 9), `PratinjauHarnessTest` (Tugas 10)
- `docs/desain/tangkapan/w1/` tangkapan WebP dan `README.md` perbandingannya (Tugas 12)

**Diubah:**
- Token dan konfigurasi: `resources/css/app.css`, `tailwind.config.js`, `config/brand.php`, `public/favicon.svg` (Tugas 1); `resources/views/components/brand/head.blade.php`, `app/Http/Middleware/SecurityHeaders.php` (Tugas 2); `vite.config.js` (Tugas 3); `config/database.php`, `config/app.php`, `.env.example`, `app/Http/Middleware/HandleInertiaRequests.php`, `resources/js/types/index.d.ts` (Tugas 9); `phpunit.xml`, `.gitignore` (Tugas 10)
- Komponen: `Button.tsx` (Tugas 1 warna, Tugas 5 bentuk), `PageHeader.tsx` (Tugas 2), `Icon.tsx` (Tugas 3 dan 4), `CheckpointJourney.tsx`, `icon.blade.php`, `checkpoint-journey.blade.php` (Tugas 3), `TataLetakPendakian.tsx` (Tugas 3 dan 9), `NavLinks.tsx` (Tugas 2 dan 3), `Card.tsx`, `Field.tsx`, `EmptyState.tsx` (Tugas 5), `AppLayout.tsx` (Tugas 9)
- Halaman yang hanya berganti token atau jenis keadaan kosong: `LangkahTrip.tsx`, `Admin/TrailGeometry.tsx`, `Trails/Index.tsx`, `Trips/Preparation.tsx`, `Welcome.tsx`, lima `resources/views/errors/*.blade.php`, `inertia.blade.php`, `offline.blade.php`, `public/trail.blade.php`, dan warna cadangan peta di `peta/warnaToken.ts`, `trails/PetaJelajah.tsx`, `trails/rinci/PetaJalur.tsx`, `community/PetaGaris.tsx`, `trips/PetaPendakian.tsx` (Tugas 1); `Admin/Health.tsx`, `Admin/Permits.tsx`, `Community/HikingHistory.tsx`, `Track/Index.tsx`, `Trails/Saved.tsx`, `Trails/Index.tsx` (Tugas 5); `routes/web.php` (Tugas 8)
- Test yang diperbarui menurut niat baru: `ColourContrastTest`, `SemanticTokenTest`, `BrandIdentityTest` (Tugas 1); `TypographySystemTest`, `SecurityHeadersTest`, `LandingPageTest`, `SurfaceSystemTest`, `ThirdPartyLicenseTest`, `RepoHygieneTest` (Tugas 2); `IconSystemTest`, `ThirdPartyLicenseTest` (Tugas 3 dan 4); `LongInputLayoutTest` (Tugas 5 sampai 8); `MotionTest` (Tugas 7); `RepoHygieneTest` (Tugas 10); `DesignDirectionTest` (Tugas 11)
- Dokumen: `docs/lisensi-pihak-ketiga.md` (Tugas 2 dan 3), `docs/ARCHITECTURE.md` (Tugas 5 dan 8), `CONTRIBUTING.md` (Tugas 8, 10, 11), `database/README.md` (Tugas 9), `DESIGN.md` (Tugas 11), `HANDOVER.md` dan `docs/README.md` (Tugas 12, koordinator)

---

### Task 1: Token warna v3 (terang, gelap, dua tema lapangan) dan pemetaan Tailwind

Induk W1 butir 1. Begitu tugas ini selesai, setiap halaman berganti rasa tanpa disunting satu per satu, karena halaman
sudah memakai kelas token (`bg-brand-700`, `text-secondary`, `rounded-lg`, `shadow-overlay`) yang kini membaca nilai
prototype.

**Files:**
- Modify (tulis ulang penuh): `resources/css/app.css`, `tailwind.config.js`, `public/favicon.svg`
- Modify: `config/brand.php` (blok `theme_color` dan `background_color` beserta komentarnya)
- Modify (hanya `text-white` di atas isian merek menjadi `text-on-brand`): `resources/js/components/trips/LangkahTrip.tsx:6`, `resources/js/components/ui/Button.tsx:37`, `resources/js/pages/Admin/TrailGeometry.tsx:159`, `resources/js/pages/Trails/Index.tsx:159`, `resources/js/pages/Trips/Preparation.tsx:154`, `resources/js/pages/Welcome.tsx:70`, `resources/views/errors/403.blade.php:11`, `resources/views/errors/404.blade.php:11`, `resources/views/errors/419.blade.php:12`, `resources/views/errors/500.blade.php:12`, `resources/views/errors/503.blade.php:12`, `resources/views/inertia.blade.php:38`, `resources/views/offline.blade.php:42`, `resources/views/public/trail.blade.php:138`
- Modify (warna cadangan peta ke nilai v3): `resources/js/components/peta/warnaToken.ts`, `resources/js/components/trails/PetaJelajah.tsx`, `resources/js/components/trails/rinci/PetaJalur.tsx`, `resources/js/components/community/PetaGaris.tsx`, `resources/js/components/trips/PetaPendakian.tsx`
- Test: `tests/Feature/ColourContrastTest.php` (tulis ulang penuh), `tests/Feature/SemanticTokenTest.php` (tulis ulang penuh), `tests/Feature/BrandIdentityTest.php` (satu test baru)

**Interfaces:**
- Consumes: kelas token yang sudah dipakai halaman (`brand-*`, `warn-*`, `danger-*`, `community-*`, `canvas`, `surface`, `surface-sunken`, `subtle`, `primary`, `secondary`, `muted`, `control`, `rounded-control`, `rounded-lg`, `shadow-overlay`, `duration-*`, `ease-*`).
- Produces, dipakai Tugas 2 sampai 12 dan W2 sampai W8:
  - Warna Tailwind baru: `on-brand` (`text-on-brand`), `surface.inverse` (`bg-surface-inverse`), `inverse` (`text-inverse`, `ring-inverse`), `fit.{cocok,perlu,kurang,belum}` beserta `.teks` (`bg-fit-cocok`, `text-fit-cocok-teks`, `border-fit-cocok-teks`, dan seterusnya), `status.{buka,dibatasi,tutup,belum}` (`bg-status-buka`, dan seterusnya), `aksen.pos` (`bg-aksen-pos`).
  - Radius: `rounded-kecil` 10 px, `rounded-control` 12 px, `rounded-tombol` 16 px, `rounded-lg` dan `rounded-kartu` 18 px, `rounded-utama` 22 px, `rounded-lembar` 26 px, `rounded-lencana` 6 px, semuanya dari token `--radius-*`. `--radius-luar` dihapus (nol pemakaian, diukur).
  - Token CSS: `--on-brand`, `--surface-inverse`, `--text-inverse`, `--fit-*`, `--status-*`, `--accent-pos`, `--scrim`, `--bayangan`, dan blok `[data-tema-lapangan='terang']` serta `[data-tema-lapangan='gelap']` untuk mode lapangan W6.

- [ ] **Step 1: Tulis test kontras yang gagal.** Ganti seluruh isi `tests/Feature/ColourContrastTest.php` dengan:

```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * WCAG 2.2 AA: teks normal butuh 4.5:1, teks besar dan komponen non-teks 3:1.
 *
 * Rasio kontras tidak dapat ditaksir dengan mata, mata melebih-lebihkan kontras pada
 * pasangan yang berdekatan. Sebelum test ini ada, palet aplikasi membawa empat klaim
 * rasio yang ditulis tanpa pernah dihitung, dan tiga di antaranya keliru. Yang lolos
 * dari pemeriksaan itu adalah tombol primer: putih di atas brand-600 hanya 3.77:1.
 *
 * Rumusnya diterapkan di sini, bukan memanggil skrip luar, supaya berjalan di mana pun
 * suite berjalan termasuk CI tanpa Python.
 *
 * Diperbarui gelombang 1 v3 (spec v3 bagian 8.1; DK-30, DK-31, DK-49). Palet kini palet
 * prototype pemilik. Tema gelapnya memakai isian tombol hijau muda dengan teks gelap, jadi
 * "putih di atas isian merek" tidak lagi berlaku di kedua tema: pasangannya menjadi token
 * on-brand di atas brand-700, dan satu test baru menolak text-white di atas isian merek.
 * Pasangan non-teks kini dihitung di kedua tema (sebelumnya hanya tema gelap), dan
 * pasangan baru mencakup label kecocokan, status akses, garis komunitas, teks kebalikan
 * untuk toast, serta dua tema lapangan dengan ambang 7:1 untuk informasi kritis. Pasangan
 * "brand-700 di atas putih" dilepas karena perannya kini dibawa brand-ink, yang dihitung
 * di tiga permukaan.
 */
class ColourContrastTest extends TestCase
{
    use SumberReact;

    private const NORMAL_TEXT = 4.5;

    private const LARGE_TEXT_AND_UI = 3.0;

    private const INFO_KRITIS_LAPANGAN = 7.0;

    /**
     * Pasangan teks yang benar-benar dipakai UI: [depan, belakang, keterangan].
     *
     * @return array<int, array{0: string, 1: string, 2: string}>
     */
    public static function textPairs(): array
    {
        return [
            ['on-brand', 'brand-700', 'teks tombol primer'],
            ['on-brand', 'brand-800', 'teks tombol primer saat ditekan'],
            ['white', 'danger-600', 'teks tombol hapus'],
            ['white', 'danger-700', 'teks tombol hapus saat hover'],
            ['brand-900', 'brand-100', 'lencana merek, tab aktif, chip terpilih'],
            ['brand-900', 'brand-50', 'baris terpilih'],
            ['warn-900', 'warn-100', 'lencana peringatan'],
            ['warn-900', 'warn-50', 'blok peringatan'],
            ['danger-900', 'danger-100', 'lencana bahaya dan alert bahaya'],
            ['danger-900', 'danger-50', 'blok bahaya'],
            // Label sumber "Komunitas" (PRD §92): pembeda keterangan komunitas dari
            // keterangan resmi.
            ['community-900', 'community-50', 'label sumber komunitas'],
            // Tiga label kecocokan publik (R-004) dan keadaan Belum dinilai (R-006).
            ['fit-cocok-teks', 'fit-cocok', 'label Cocok'],
            ['fit-perlu-teks', 'fit-perlu', 'label Perlu persiapan'],
            ['fit-kurang-teks', 'fit-kurang', 'label Kurang cocok'],
            ['fit-belum-teks', 'fit-belum', 'keadaan Belum dinilai'],
            ['text-inverse', 'surface-inverse', 'toast urungkan'],
            // Pasangan semantik, dihitung terhadap tiga permukaan, bukan terhadap putih
            // saja: nilai redup yang lolos di kanvas dapat gagal di sumur.
            ['text-primary', 'canvas', 'judul di atas latar halaman'],
            ['text-primary', 'surface', 'judul di atas kartu'],
            ['text-primary', 'surface-sunken', 'segmen terpilih di atas sumur'],
            ['text-secondary', 'canvas', 'badan teks di atas latar halaman'],
            ['text-secondary', 'surface', 'badan teks di atas kartu'],
            ['text-secondary', 'surface-sunken', 'badan teks di atas sumur'],
            ['text-secondary', 'brand-50', 'keterangan di baris terpilih'],
            ['text-muted', 'canvas', 'metadata di atas latar halaman'],
            ['text-muted', 'surface', 'metadata di atas kartu'],
            ['text-muted', 'surface-sunken', 'metadata di atas sumur'],
            // Hijau sebagai latar depan: tautan, penekanan, garis profil elevasi.
            ['brand-ink', 'canvas', 'tautan di atas latar halaman'],
            ['brand-ink', 'surface', 'tautan di atas kartu'],
            ['brand-ink', 'surface-sunken', 'tautan di atas sumur'],
            ['logo-type', 'canvas', 'wordmark di atas latar halaman'],
            ['logo-type', 'surface', 'wordmark di atas bilah navigasi'],
        ];
    }

    /**
     * Setiap pasangan dihitung di kedua tema. Tema gelap bukan varian yang ditaksir
     * dari tema terang: setiap tokennya nilai sendiri, dan setiap pasangan gagal
     * dengan caranya sendiri.
     *
     * @return array<string, array{0: string, 1: string, 2: string, 3: string}>
     */
    public static function textPairsInBothSchemes(): array
    {
        $kasus = [];

        foreach (self::textPairs() as [$depan, $belakang, $keterangan]) {
            foreach (['light', 'dark'] as $skema) {
                $kasus["{$skema}: {$keterangan}"] = [$skema, $depan, $belakang, $keterangan];
            }
        }

        return $kasus;
    }

    #[DataProvider('textPairsInBothSchemes')]
    public function test_text_pairings_meet_aa(string $scheme, string $foreground, string $background, string $usage): void
    {
        $ratio = $this->ratio($this->token($foreground, $scheme), $this->token($background, $scheme));

        $this->assertGreaterThanOrEqual(
            self::NORMAL_TEXT,
            $ratio,
            sprintf('[%s] %s: %s di atas %s hanya %.2f:1.', $scheme, $usage, $foreground, $background, $ratio)
        );
    }

    /**
     * Komponen non-teks (WCAG 1.4.11): batas kontrol, cincin fokus, garis grafik, garis
     * putus-putus komunitas, dan penanda status akses butuh 3:1 terhadap permukaan
     * tempatnya berdiri. Versi sebelumnya hanya menghitung tema gelap; kini keduanya.
     *
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function nonTextPairs(): array
    {
        $pasangan = [
            'batas kontrol di atas kartu' => ['control-border', 'surface'],
            'batas kontrol di atas latar' => ['control-border', 'canvas'],
            'segmen terpilih bergaris di atas sumur' => ['control-border', 'surface-sunken'],
            'garis putus-putus komunitas' => ['community-500', 'community-50'],
            'cincin fokus di atas latar' => ['brand-600', 'canvas'],
            'cincin fokus di atas kartu' => ['brand-600', 'surface'],
            'isian tombol primer di atas latar' => ['brand-700', 'canvas'],
            'garis profil elevasi' => ['brand-ink', 'surface'],
            'tanda logo di atas latar' => ['logo-mark', 'canvas'],
            'tanda logo di atas bilah' => ['logo-mark', 'surface'],
            'status Buka di atas kartu' => ['status-buka', 'surface'],
            'status Dibatasi di atas kartu' => ['status-dibatasi', 'surface'],
            'status Tutup di atas kartu' => ['status-tutup', 'surface'],
            'status Belum diketahui di atas kartu' => ['status-belum', 'surface'],
            'status Buka di atas latar' => ['status-buka', 'canvas'],
            'status Dibatasi di atas latar' => ['status-dibatasi', 'canvas'],
            'status Tutup di atas latar' => ['status-tutup', 'canvas'],
            'status Belum diketahui di atas latar' => ['status-belum', 'canvas'],
        ];

        $kasus = [];

        foreach ($pasangan as $keterangan => [$depan, $belakang]) {
            foreach (['light', 'dark'] as $skema) {
                $kasus["{$skema}: {$keterangan}"] = [$skema, $depan, $belakang];
            }
        }

        return $kasus;
    }

    #[DataProvider('nonTextPairs')]
    public function test_non_text_pairings_meet_the_ui_threshold(string $scheme, string $foreground, string $background): void
    {
        $ratio = $this->ratio($this->token($foreground, $scheme), $this->token($background, $scheme));

        $this->assertGreaterThanOrEqual(
            self::LARGE_TEXT_AND_UI,
            $ratio,
            sprintf('[%s] %s di atas %s hanya %.2f:1.', $scheme, $foreground, $background, $ratio)
        );
    }

    /**
     * Dua tema lapangan (DK-49): nama pos, angka jarak, dan tombol Jeda atau Selesai
     * dibaca sambil berjalan, di bawah matahari atau dengan senter. Ambangnya 7:1, bukan
     * 4,5:1 (spec v3 bagian 8.1).
     *
     * @return array<string, array{0: string, 1: string, 2: string, 3: float}>
     */
    public static function fieldPairs(): array
    {
        $pasangan = [
            ['text-primary', 'canvas', self::INFO_KRITIS_LAPANGAN, 'nama pos dan angka jarak di latar'],
            ['text-primary', 'surface', self::INFO_KRITIS_LAPANGAN, 'nama pos dan angka jarak di kartu'],
            ['text-secondary', 'canvas', self::INFO_KRITIS_LAPANGAN, 'keterangan pendukung di latar'],
            ['text-secondary', 'surface', self::INFO_KRITIS_LAPANGAN, 'keterangan pendukung di kartu'],
            ['on-brand', 'brand-700', self::INFO_KRITIS_LAPANGAN, 'tombol Jeda, Lanjut, Selesai'],
            ['on-brand', 'brand-800', self::INFO_KRITIS_LAPANGAN, 'tombol lapangan saat ditekan'],
            ['text-muted', 'surface', self::NORMAL_TEXT, 'teks redup'],
            ['brand-ink', 'surface', self::NORMAL_TEXT, 'tautan di kartu'],
            ['brand-ink', 'canvas', self::NORMAL_TEXT, 'tautan di latar'],
            ['control-border', 'surface', self::LARGE_TEXT_AND_UI, 'batas kendali lapangan'],
            ['brand-600', 'canvas', self::LARGE_TEXT_AND_UI, 'cincin fokus di latar'],
            ['brand-600', 'surface', self::LARGE_TEXT_AND_UI, 'cincin fokus di kartu'],
        ];

        $kasus = [];

        foreach (['lapangan-terang', 'lapangan-gelap'] as $skema) {
            foreach ($pasangan as [$depan, $belakang, $minimum, $keterangan]) {
                $kasus["{$skema}: {$keterangan}"] = [$skema, $depan, $belakang, $minimum];
            }
        }

        return $kasus;
    }

    #[DataProvider('fieldPairs')]
    public function test_field_themes_meet_their_thresholds(string $scheme, string $foreground, string $background, float $minimum): void
    {
        $ratio = $this->ratio($this->token($foreground, $scheme), $this->token($background, $scheme));

        $this->assertGreaterThanOrEqual(
            $minimum,
            $ratio,
            sprintf('[%s] %s di atas %s hanya %.2f:1, butuh %.1f:1.', $scheme, $foreground, $background, $ratio, $minimum)
        );
    }

    /**
     * Blok tema lapangan hanya menimpa token yang dibutuhkan mode lapangan; token yang
     * terlewat akan diam-diam memakai nilai tema aplikasi di bawahnya.
     */
    public function test_both_field_themes_define_every_critical_token(): void
    {
        $wajib = [
            'canvas', 'surface', 'surface-sunken', 'border-subtle', 'control-border',
            'text-primary', 'text-secondary', 'text-muted',
            'brand-600', 'brand-700', 'brand-800', 'brand-ink', 'on-brand',
        ];

        foreach (['lapangan-terang', 'lapangan-gelap'] as $skema) {
            $this->assertSame([], array_values(array_diff($wajib, array_keys($this->tokens($skema)))), "Blok {$skema} belum lengkap.");
        }
    }

    /**
     * Koreksi terukur atas prototype (DK-31, spec v3 bagian 3.4) ditambah satu koreksi
     * gelombang 1 (status belum diketahui 2,97:1 di kanvas). Nilainya dipaku di sini
     * supaya tidak kembali ke nilai prototype yang gagal.
     */
    public function test_the_measured_corrections_to_the_prototype_hold(): void
    {
        $terang = $this->tokens('light');

        $this->assertSame([140, 133, 121], $terang['control-border'] ?? null, 'Batas kontrol DK-31 #8C8579.');
        $this->assertSame([95, 130, 184], $terang['community-500'] ?? null, 'Garis komunitas DK-31 #5F82B8.');
        $this->assertSame([98, 105, 101], $terang['text-muted'] ?? null, 'Teks redup DK-31 #626965.');
        $this->assertSame([128, 134, 130], $terang['status-belum'] ?? null, 'Status belum diketahui #808682; #8A8F8B hanya 2,97:1 di kanvas.');
        $this->assertSame([246, 243, 236], $terang['canvas'] ?? null, 'Kanvas kertas prototype #F6F3EC.');
    }

    /**
     * Isian merek di tema gelap berwarna hijau muda, jadi teks di atasnya wajib token
     * on-brand. text-white di atas isian merek lolos di tema terang dan gagal 1,75:1 di
     * tema gelap; kegagalan itu tidak terlihat sampai seseorang membuka tema gelap.
     */
    public function test_text_on_a_brand_fill_uses_the_on_brand_token(): void
    {
        $pelanggar = [];

        foreach ($this->berkasTsx() as $berkas) {
            foreach (explode("\n", $this->tanpaKomentarJs($berkas->getContents())) as $nomor => $baris) {
                if (preg_match('/\btext-white\b/', $baris) && preg_match('/\bbg-brand-\d{2,3}\b/', $baris)) {
                    $pelanggar[] = $berkas->getRelativePathname().':'.($nomor + 1);
                }
            }
        }

        foreach (File::allFiles(resource_path('views')) as $berkas) {
            $isi = preg_replace('/\{\{--.*?--\}\}/s', ' ', $berkas->getContents());

            preg_match_all('/class="([^"]*)"/s', $isi, $kelas);

            foreach ($kelas[1] as $satu) {
                if (preg_match('/\btext-white\b/', $satu) && preg_match('/\bbg-brand-\d{2,3}\b/', $satu)) {
                    $pelanggar[] = $berkas->getRelativePathname();
                }
            }
        }

        $this->assertSame([], $pelanggar, 'Teks putih di atas isian merek (pakai text-on-brand): '.implode(', ', $pelanggar));
    }

    /**
     * Tema gelap ditulis dua kali di app.css (blok media dan blok atribut) karena
     * format token "R G B" tidak dapat memakai light-dark(). Dua blok yang identik
     * hari ini akan melenceng besok tanpa penjaga ini, dan tema yang dipilih pengguna
     * diam-diam berbeda dari tema yang mengikuti sistem.
     */
    public function test_both_dark_blocks_define_identical_tokens(): void
    {
        $this->assertSame(
            $this->tokens('dark-media'),
            $this->tokens('dark'),
            'Blok gelap prefers-color-scheme dan blok data-theme=dark berbeda.'
        );
    }

    /**
     * Setiap token terang yang menyebut warna punya padanan gelap. Token yang
     * terlewat memakai nilai terangnya di tema gelap, dan itu persis kegagalan
     * yang tidak terlihat sampai seseorang membuka halaman yang memakainya.
     */
    public function test_every_colour_token_has_a_dark_counterpart(): void
    {
        $terang = array_keys($this->tokens('light'));
        $gelap = array_keys($this->tokens('dark'));

        $this->assertSame([], array_values(array_diff($terang, $gelap)), 'Token tanpa padanan gelap.');
    }

    /**
     * WCAG 1.4.11 menuntut 3:1 untuk batas komponen antarmuka, bukan hanya untuk teks.
     * Batas input bawaan Breeze memakai gray-300 yang hanya 1.47:1 di atas putih: bagi
     * mata dengan penglihatan rendah, kotak isiannya praktis tidak berbatas.
     *
     * Yang dituntut hanya komponen interaktif. Garis dekoratif pada kartu dan pemisah
     * tidak termasuk, dan tidak perlu digelapkan.
     */
    public function test_the_border_of_an_interactive_control_is_visible(): void
    {
        $ratio = $this->ratio($this->token('control-border'), $this->token('white'));

        $this->assertGreaterThanOrEqual(
            self::LARGE_TEXT_AND_UI,
            $ratio,
            sprintf('Batas kontrol hanya %.2f:1 di atas putih.', $ratio)
        );
    }

    /**
     * Komponen kontrol bersama halaman React: isian teks, pilihan, area teks, dan kotak
     * centang di Field.tsx, serta seluruh varian tombol di Button.tsx.
     *
     * @return array<int, array{0: string}>
     */
    public static function interactiveControls(): array
    {
        return [
            ['js/components/ui/Field.tsx'],
            ['js/components/ui/Button.tsx'],
        ];
    }

    #[DataProvider('interactiveControls')]
    public function test_an_interactive_control_does_not_use_the_invisible_border(string $path): void
    {
        $this->assertStringNotContainsString(
            'border-gray-300',
            File::get(resource_path($path)),
            $path.': gray-300 hanya 1.47:1 dan gagal WCAG 1.4.11.'
        );
    }

    /**
     * Penyapuan menyeluruh, bukan daftar empat berkas.
     *
     * Versi di atas hanya memeriksa komponen yang disebut namanya, dan dua pelanggaran
     * nyata lolos lewat celah itu selama berbulan: radio di pengelola perizinan dan
     * tombol status di daftar persiapan, keduanya ditulis sebagai markup sebaris
     * alih-alih memakai komponen. Disapu di view Blade dan sumber React sekaligus.
     *
     * Yang dicari hanya kontrol interaktif. Garis pada kartu, lencana, dan panel tidak
     * termasuk, karena 1.4.11 memang tidak mengaturnya.
     */
    public function test_no_interactive_control_anywhere_uses_the_invisible_border(): void
    {
        $pelanggar = [];

        foreach (File::allFiles(resource_path('views')) as $berkas) {
            $baris = explode("\n", $berkas->getContents());

            foreach ($baris as $nomor => $isi) {
                if (! str_contains($isi, 'border-gray-300')) {
                    continue;
                }

                // Atribut kelas kerap dipecah beberapa baris, dan @class([...]) pada
                // tombol Blade menaruh warnanya lima sampai enam baris di bawah tag
                // pembukanya. Jendela dua baris melewatkan persis kasus itu.
                $konteks = implode(' ', array_slice($baris, max(0, $nomor - 8), 9));

                if (preg_match('/<button|<input|<select|<textarea|wire:click/', $konteks)) {
                    $pelanggar[] = $berkas->getRelativePathname().':'.($nomor + 1);
                }
            }
        }

        foreach ($this->berkasTsx() as $berkas) {
            $baris = explode("\n", $this->tanpaKomentarJs($berkas->getContents()));

            foreach ($baris as $nomor => $isi) {
                if (! str_contains($isi, 'border-gray-300')) {
                    continue;
                }

                $konteks = implode(' ', array_slice($baris, max(0, $nomor - 8), 9));

                if (preg_match('/<(?:button|input|select|textarea|Button|TextInput|Select|Textarea|Checkbox)\b|onClick=/', $konteks)) {
                    $pelanggar[] = $berkas->getRelativePathname().':'.($nomor + 1);
                }
            }
        }

        $this->assertSame(
            [],
            $pelanggar,
            'gray-300 hanya 1.47:1 di atas putih dan gagal WCAG 1.4.11 pada kontrol di: '
                .implode(', ', $pelanggar)
        );
    }

    /**
     * Merek aplikasi berwarna hijau, tetapi input bawaan Breeze dahulu menyorot indigo.
     * Setiap isian pada halaman masuk dan daftar berkedip warna yang bukan warna produk.
     * Kelas fokusnya kini hidup di satu konstanta Field.tsx yang dipakai isian teks,
     * pilihan, dan area teks.
     */
    public function test_focus_colours_follow_the_brand(): void
    {
        $isian = File::get(resource_path('js/components/ui/Field.tsx'));

        $this->assertStringNotContainsString('indigo', $isian);
        $this->assertMatchesRegularExpression("/const kontrol = '[^']*\bfocus:border-brand-600\b[^']*\bfocus:ring-brand-600\b/", $isian);
        $this->assertMatchesRegularExpression("/type=\"checkbox\" className=\\{kelas\\('[^']*\\bfocus:ring-brand-600\\b/", $isian);
    }

    /**
     * Sisa Breeze memakai hover yang MENERANG: bg-red-600 menjadi bg-red-500. Putih di
     * atas red-500 hanya 3.76:1, jadi tombol hapus paling sulit dibaca tepat saat jari
     * menunjuknya. Token danger menggelap saat hover.
     */
    public function test_the_danger_button_darkens_on_hover_instead_of_lightening(): void
    {
        $isi = File::get(resource_path('js/components/ui/Button.tsx'));

        $this->assertMatchesRegularExpression("/danger: '[^']*\bbg-danger-600\b[^']*\bhover:bg-danger-700\b/", $isi);

        $normal = $this->ratio($this->token('danger-600'), $this->token('white'));
        $hover = $this->ratio($this->token('danger-700'), $this->token('white'));

        $this->assertGreaterThanOrEqual(self::NORMAL_TEXT, $normal);
        $this->assertGreaterThan($normal, $hover, 'Hover harus menggelap, bukan menerang.');
    }

    /**
     * Palet mentah Tailwind tidak boleh masuk view: warnanya tidak pernah dihitung
     * kontrasnya dan tidak ikut berubah ketika palet aplikasi disesuaikan. Abu dibiarkan
     * karena memang palet netral yang tidak ditokenkan.
     */
    public function test_no_view_uses_a_raw_semantic_colour(): void
    {
        $pelanggar = [];

        foreach (File::allFiles(resource_path('views')) as $berkas) {
            // Komentar Blade dilewati: nama warna lama sering disebut di sana justru
            // untuk menjelaskan mengapa ia diganti.
            $isi = preg_replace('/\{\{--.*?--\}\}/s', ' ', $berkas->getContents());

            if (preg_match('/\b(?:text|bg|border|ring)-(?:red|rose|emerald|green|amber|yellow|indigo|blue|sky)-\d{2,3}\b/', $isi)) {
                $pelanggar[] = $berkas->getRelativePathname();
            }
        }

        $this->assertSame([], $pelanggar, 'Warna mentah di: '.implode(', ', $pelanggar));
    }

    /**
     * Putih dan hitam yang ditulis langsung tidak berganti di tema gelap. Tema gelap
     * hidup sepenuhnya di token, jadi view tidak boleh menyebut keduanya, dan tidak
     * boleh memakai varian dark: yang berarti ada adaptasi di luar token.
     */
    public function test_no_view_hardcodes_white_black_or_a_dark_variant(): void
    {
        $pelanggar = [];

        foreach (File::allFiles(resource_path('views')) as $berkas) {
            $isi = preg_replace('/\{\{--.*?--\}\}/s', ' ', $berkas->getContents());

            if (preg_match('/\b(?:bg-white|text-black|bg-black|ring-black|dark:[a-z])/', $isi)) {
                $pelanggar[] = $berkas->getRelativePathname();
            }
        }

        $this->assertSame([], $pelanggar, 'Warna tanpa token di: '.implode(', ', $pelanggar));
    }

    public function test_the_focus_ring_is_distinguishable_from_the_page(): void
    {
        // Focus ring adalah komponen non-teks, jadi ambangnya 3:1 (WCAG 1.4.11).
        $ratio = $this->ratio($this->token('brand-600'), $this->token('white'));

        $this->assertGreaterThanOrEqual(self::LARGE_TEXT_AND_UI, $ratio);
    }

    /**
     * Diperbarui gelombang 1 v3: niatnya tetap, yaitu tombol primer memakai isian yang
     * rasionya dihitung. Teksnya kini token on-brand, karena isian gelapnya hijau muda.
     */
    public function test_the_primary_button_does_not_use_the_shade_that_fails(): void
    {
        $button = File::get(resource_path('js/components/ui/Button.tsx'));

        // brand-600 pernah menjadi dasar tombol dan gagal AA pada 3.77:1. Yang dihitung
        // lolos di atas adalah on-brand di atas brand-700, jadi itu yang wajib dipakai.
        $this->assertStringNotContainsString(
            'bg-brand-600',
            $button,
            'brand-600 tidak lolos AA sebagai latar teks.'
        );
        $this->assertMatchesRegularExpression("/primary: '[^']*\bbg-brand-700\b[^']*\btext-on-brand\b/", $button);
    }

    public function test_every_token_referenced_here_exists_in_the_stylesheet(): void
    {
        foreach (self::textPairs() as [$foreground, $background]) {
            foreach ([$foreground, $background] as $name) {
                $this->assertNotNull(
                    $this->token($name),
                    "Token {$name} tidak ada di resources/css/app.css."
                );
            }
        }
    }

    /**
     * Membaca nilai RGB langsung dari design token, bukan dari salinan di test:
     * mengubah token harus langsung terlihat di sini.
     *
     * @return array{0: int, 1: int, 2: int}|null
     */
    private function token(string $name, string $scheme = 'light'): ?array
    {
        if ($name === 'white') {
            return [255, 255, 255];
        }

        return $this->tokens($scheme)[$name] ?? null;
    }

    /**
     * Seluruh token satu skema, dibaca dari bloknya sendiri di app.css.
     *
     * Versi sebelumnya memakai preg_match atas seluruh berkas, sehingga hanya
     * kemunculan pertama tiap token yang terbaca dan blok gelap tidak pernah diperiksa
     * (F13). Blok dipilih dari selektornya, lalu token diambil dari dalam blok itu saja.
     * Dua tema lapangan (DK-49) dibaca dari blok atributnya sendiri.
     *
     * @return array<string, array{0: int, 1: int, 2: int}>
     */
    private function tokens(string $scheme): array
    {
        static $cache = [];

        if (isset($cache[$scheme])) {
            return $cache[$scheme];
        }

        $css = File::get(resource_path('css/app.css'));

        $pembuka = match ($scheme) {
            'light' => '/:root\s*\{/',
            'dark' => "/:root\[data-theme='dark'\]\s*\{/",
            'dark-media' => "/@media \(prefers-color-scheme: dark\)\s*\{\s*:root:not\(\[data-theme='light'\]\)\s*\{/",
            'lapangan-terang' => "/\[data-tema-lapangan='terang'\]\s*\{/",
            'lapangan-gelap' => "/\[data-tema-lapangan='gelap'\]\s*\{/",
        };

        $this->assertSame(1, preg_match($pembuka, $css, $m, PREG_OFFSET_CAPTURE), "Blok {$scheme} tidak ditemukan.");

        $mulai = $m[0][1] + strlen($m[0][0]);
        $blok = substr($css, $mulai, strpos($css, '}', $mulai) - $mulai);

        preg_match_all('/--([a-z0-9-]+):\s*(\d+)\s+(\d+)\s+(\d+);/', $blok, $semua, PREG_SET_ORDER);

        $token = [];

        foreach ($semua as $t) {
            $token[$t[1]] = [(int) $t[2], (int) $t[3], (int) $t[4]];
        }

        return $cache[$scheme] = $token;
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $foreground
     * @param  array{0: int, 1: int, 2: int}  $background
     */
    private function ratio(array $foreground, array $background): float
    {
        $lighter = max($this->luminance($foreground), $this->luminance($background));
        $darker = min($this->luminance($foreground), $this->luminance($background));

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    /**
     * Luminansi relatif WCAG 2.x.
     *
     * @param  array{0: int, 1: int, 2: int}  $rgb
     */
    private function luminance(array $rgb): float
    {
        $channels = array_map(function (int $value): float {
            $srgb = $value / 255;

            return $srgb <= 0.03928
                ? $srgb / 12.92
                : (($srgb + 0.055) / 1.055) ** 2.4;
        }, $rgb);

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }
}
```

- [ ] **Step 2: Tulis test token semantik yang gagal.** Ganti seluruh isi `tests/Feature/SemanticTokenTest.php` dengan:

```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Lapisan semantik.
 *
 * Sebelum ini ada 25 token dan hanya satu yang menyebut peran, sementara 597 abu mentah
 * Tailwind dipakai langsung di view dengan empat abu berbeda untuk teks yang saling
 * tertukar. Tanpa lapisan yang menyebut peran, setiap layar memutuskan sendiri dan
 * keputusannya selalu default.
 *
 * Diperluas gelombang 1 v3 (spec v3 bagian 8.1 dan 8.4): token peran baru untuk teks di
 * atas isian merek, permukaan kebalikan, label kecocokan, status akses, aksen pos, tirai,
 * dan bayangan, serta skala radius yang dibaca dari token. Pemeriksaan Tailwind kini
 * mencari pemetaan token('...') per peran, bukan sekadar kata yang mirip di berkasnya.
 */
class SemanticTokenTest extends TestCase
{
    /**
     * @return array<int, array{0: string}>
     */
    public static function tokenSemantik(): array
    {
        return [
            ['text-primary'], ['text-secondary'], ['text-muted'], ['text-inverse'],
            ['canvas'], ['surface'], ['surface-sunken'], ['surface-inverse'],
            ['border-subtle'], ['control-border'],
            ['brand-ink'], ['on-brand'],
            ['fit-cocok'], ['fit-cocok-teks'], ['fit-perlu'], ['fit-perlu-teks'],
            ['fit-kurang'], ['fit-kurang-teks'], ['fit-belum'], ['fit-belum-teks'],
            ['status-buka'], ['status-dibatasi'], ['status-tutup'], ['status-belum'],
            ['accent-pos'], ['scrim'], ['bayangan'],
        ];
    }

    #[DataProvider('tokenSemantik')]
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

        foreach ([
            'text-primary', 'text-secondary', 'text-muted', 'text-inverse',
            'canvas', 'surface', 'surface-sunken', 'surface-inverse',
            'border-subtle', 'control-border', 'brand-ink', 'on-brand',
            'fit-cocok', 'fit-cocok-teks', 'fit-perlu', 'fit-perlu-teks',
            'fit-kurang', 'fit-kurang-teks', 'fit-belum', 'fit-belum-teks',
            'status-buka', 'status-dibatasi', 'status-tutup', 'status-belum', 'accent-pos',
        ] as $nama) {
            $this->assertStringContainsString("token('{$nama}')", $config, "Tailwind belum memetakan {$nama}.");
        }

        $this->assertStringContainsString('rgb(var(--bayangan) / 0.18)', $config, 'shadow-overlay harus membaca token bayangan.');
    }

    /**
     * Skala radius spec v3 bagian 8.4. rounded-lg dipetakan ke token kartu karena peran
     * rounded-lg di DESIGN.md adalah kartu dan panel; kartu yang ada langsung mendapat
     * radius prototype tanpa disunting satu per satu.
     */
    public function test_the_radius_scale_follows_the_spec(): void
    {
        $css = File::get(resource_path('css/app.css'));
        $config = File::get(base_path('tailwind.config.js'));

        foreach ([
            'kecil' => '0.625rem',
            'control' => '0.75rem',
            'tombol' => '1rem',
            'kartu' => '1.125rem',
            'utama' => '1.375rem',
            'lembar' => '1.625rem',
            'lencana' => '0.375rem',
        ] as $nama => $nilai) {
            $this->assertMatchesRegularExpression('/--radius-'.$nama.':\s*'.preg_quote($nilai, '/').';/', $css, "Radius {$nama} bukan {$nilai}.");
            $this->assertMatchesRegularExpression('/\b'.$nama.":\s*'var\(--radius-".$nama."\)'/", $config, "Tailwind belum memetakan rounded-{$nama}.");
        }

        $this->assertMatchesRegularExpression("/\blg:\s*'var\(--radius-kartu\)'/", $config, 'rounded-lg adalah peran kartu.');
    }

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
            // Komentar Blade dilewati: nama kelas lama sering disebut di sana justru
            // untuk menjelaskan mengapa ia diganti.
            $isi = preg_replace('/\{\{--.*?--\}\}/s', ' ', $berkas->getContents());

            if (preg_match('/\b(?:text|bg|border)-gray-\d{2,3}\b/', $isi)) {
                $pelanggar[] = $berkas->getRelativePathname();
            }
        }

        $this->assertSame([], $pelanggar, 'Abu mentah masih dipakai di: '.implode(', ', $pelanggar));
    }
}
```

- [ ] **Step 3: Tambahkan test favikon.** Di `tests/Feature/BrandIdentityTest.php`, sisipkan metode berikut tepat sebelum baris
`    public function test_the_icons_and_favicons_referenced_by_the_head_exist(): void`:

```php
    /**
     * Favikon mengikuti token merek v3 (rencana W1 tugas 1). Garisnya ditulis sebagai
     * heksa di SVG karena favikon dimuat di luar CSS aplikasi; test ini menjaga kedua
     * warnanya sama dengan isian merek terang dan merek latar depan gelap.
     */
    public function test_the_favicon_follows_the_brand_tokens(): void
    {
        $svg = File::get(public_path('favicon.svg'));
        $css = File::get(resource_path('css/app.css'));

        preg_match("/:root\[data-theme='dark'\]\s*\{.*?--brand-ink:\s*(\d+)\s+(\d+)\s+(\d+);/s", $css, $gelap);

        $this->assertCount(4, $gelap, 'Token brand-ink gelap tidak ditemukan.');
        $this->assertStringContainsString('stroke="'.config('brand.theme_color.light').'"', $svg);
        $this->assertStringContainsString(sprintf('stroke: #%02x%02x%02x;', $gelap[1], $gelap[2], $gelap[3]), $svg);
    }
```

- [ ] **Step 4: Jalankan dan pastikan gagal.**

Run: `php artisan test --compact --filter='ColourContrastTest|SemanticTokenTest|BrandIdentityTest'`
Expected: FAIL. Pada simulasi rencana: 178 test, 97 lulus, 51 gagal, 30 error. Di antaranya `Blok lapangan-terang tidak
ditemukan.`, `Token --on-brand belum ada.`, `Tailwind belum memetakan text-inverse.`, `Batas kontrol DK-31 #8C8579.`,
`Radius kecil bukan 0.625rem.`, `[light] community-500 di atas community-50 hanya 2.60:1.` (cacat palet lama yang kini
tertangkap karena pasangan non-teks dihitung di kedua tema), dan daftar 14 tempat teks putih di atas isian merek.

- [ ] **Step 5: Tulis ulang `resources/css/app.css`.** Ganti seluruh isinya dengan berkas di bawah. Bagian `body` dan `h1`
sengaja belum berubah; Tugas 2 yang menggantinya.

```css
@tailwind base;
@tailwind components;
@tailwind utilities;

/*
| Design token v3
|
| Satu-satunya tempat palet dan bentuk aplikasi didefinisikan (spec v3 bagian 8.1 dan
| 8.4; DK-30, DK-31, DK-49). Token menyebut peran; komponen hanya memakai kelas Tailwind
| yang membaca token ini, jadi mengubah rasa seluruh aplikasi cukup menyentuh nilai di
| sini.
|
| Nilai ditulis sebagai komponen RGB tanpa fungsi rgb() agar Tailwind dapat menerapkan
| opacity lewat sintaks <alpha-value> (mis. bg-brand-600/50).
|
| Palet berasal dari prototype pemilik (docs/desain/prototype/), dengan koreksi terukur
| DK-31: batas kontrol #8C8579, garis komunitas #5F82B8, teks redup #626965. Satu koreksi
| lagi ditemukan di gelombang 1: status belum diketahui #8A8F8B hanya 2,97:1 di kanvas,
| jadi menjadi #808682 (3,35:1).
|
| Rasio dihitung dengan rumus WCAG 2.x, bukan ditaksir, dan dijaga
| tests/Feature/ColourContrastTest.php untuk tema terang, gelap, dan kedua tema lapangan.
|
|   on-brand di atas brand-700           9,39:1   teks tombol primer
|   text-primary di atas canvas         14,43:1
|   text-secondary di atas canvas        6,91:1
|   text-muted di atas surface-sunken    4,69:1
|   control-border di atas surface       3,65:1   batas kontrol (WCAG 1.4.11)
|   community-500 di atas community-50   3,60:1   garis putus-putus komunitas
*/
@layer base {
    :root {
        /* Merek. 700 isian tombol, 800 saat ditekan, 600 cincin fokus dan garis aktif,
           100 dan 50 latar lembut (tab aktif, chip terpilih, baris terpilih), 900 teks
           di atas latar lembut. */
        --brand-50: 238 244 239;
        --brand-100: 228 238 230;
        --brand-200: 200 220 205;
        --brand-300: 157 191 166;
        --brand-500: 63 122 82;
        --brand-600: 47 107 67;
        --brand-700: 35 79 50;
        --brand-800: 22 54 31;
        --brand-900: 30 94 53;

        /* Teks di atas isian merek. Putih di tema terang; di tema gelap isiannya hijau
           muda, jadi teksnya gelap (spec v3 bagian 8.1). */
        --on-brand: 255 255 255;

        /* Peringatan: kondisi yang perlu diperhatikan, bukan kegagalan. */
        --warn-50: 251 241 222;
        --warn-100: 251 235 207;
        --warn-300: 226 192 138;
        --warn-600: 183 121 31;
        --warn-900: 122 78 11;

        /* Komunitas: membedakan masukan pendaki lain dari keterangan resmi (PRD §92). */
        --community-50: 242 246 252;
        --community-500: 95 130 184;
        --community-900: 36 80 140;

        /* Bahaya: penutupan resmi dan tindakan merusak, tidak pernah hiasan. */
        --danger-50: 251 237 234;
        --danger-100: 247 223 218;
        --danger-300: 224 168 158;
        --danger-600: 163 59 43;
        --danger-700: 142 47 34;
        --danger-900: 127 40 28;

        /* Label kecocokan publik (R-004) dan keadaan Belum dinilai (R-006). */
        --fit-cocok: 221 238 226;
        --fit-cocok-teks: 30 94 53;
        --fit-perlu: 251 235 207;
        --fit-perlu-teks: 122 78 11;
        --fit-kurang: 247 223 218;
        --fit-kurang-teks: 142 47 34;
        --fit-belum: 236 233 227;
        --fit-belum-teks: 78 85 81;

        /* Status akses (DK-32): penanda non-teks, selalu bersama bentuk dan kata. */
        --status-buka: 47 122 72;
        --status-dibatasi: 183 121 31;
        --status-tutup: 142 47 34;
        --status-belum: 128 134 130;

        /* Satu aksen: kuning pos. 1,76:1 di kanvas, jadi tidak pernah membawa arti
           sendirian (spec v3 bagian 8.1). */
        --accent-pos: 232 176 75;

        /*
         * Batas kontrol interaktif. WCAG 1.4.11 menuntut 3:1 terhadap latarnya: 3,65:1 di
         * atas kartu dan 3,30:1 di atas kanvas. Juga isian sakelar mati dan batas kotak
         * centang (DK-31). Garis dekoratif kartu dan pemisah tetap border-subtle, karena
         * 1.4.11 memang tidak mengaturnya.
         */
        --control-border: 140 133 121;

        /*
         * Bentuk (spec v3 bagian 8.4): kontrol kecil 10 px, kontrol 12 px, tombol dan
         * isian 16 px, kartu 18 px, kartu utama dan peta 22 px, lembar bawah 26 px, lencana
         * 6 px. Yang bersarang di dalam lapisan lain memakai radius luar dikurangi padding
         * supaya sudutnya sejajar. Chip, bilah tab, dan tombol ikon memakai rounded-full.
         */
        --radius-kecil: 0.625rem;
        --radius-control: 0.75rem;
        --radius-tombol: 1rem;
        --radius-kartu: 1.125rem;
        --radius-utama: 1.375rem;
        --radius-lembar: 1.625rem;
        --radius-lencana: 0.375rem;

        /*
         * Gerak (DK-26). Hanya transform dan opacity yang dianimasikan. Kurva pegas
         * dihitung dari osilator teredam, bukan ditebak: redaman 0,9 untuk perpindahan
         * posisi (lonjakan 0,14%), 1,0 untuk warna dan opasitas (tanpa lonjakan), 0,75
         * hanya sesudah gestur seperti melepas lembar bawah (lonjakan 2,8%). Durasi
         * pendek karena pengguna sedang bekerja, bukan menonton (mode Operate).
         */
        --dur-sentuh: 120ms;
        --dur-cepat: 200ms;
        --dur-sedang: 280ms;
        --dur-lambat: 420ms;
        --ease-gerak: linear(0, 0.034, 0.115, 0.22, 0.331, 0.44, 0.541, 0.63, 0.707, 0.771, 0.824, 0.867, 0.901, 0.928, 0.948, 0.964, 0.975, 0.984, 0.99, 0.994, 0.997, 0.999, 1, 1.001, 1);
        --ease-pudar: linear(0, 0.066, 0.202, 0.354, 0.494, 0.614, 0.711, 0.786, 0.844, 0.887, 0.919, 0.942, 0.959, 0.971, 0.98, 0.986, 0.99, 0.993, 0.995, 0.997, 0.998, 0.998, 0.999, 0.999, 1);
        --ease-lentur: linear(0, 0.059, 0.195, 0.359, 0.521, 0.665, 0.783, 0.873, 0.938, 0.981, 1.008, 1.022, 1.028, 1.028, 1.025, 1.021, 1.016, 1.012, 1.008, 1.005, 1.003, 1.001, 1, 1, 1);

        /*
         * Lapisan semantik.
         *
         * Tingkat di atas ini menyebut warna; tingkat ini menyebut peran. Netral hangat,
         * bukan abu Tailwind yang bersemu biru: kertas peta dan buku catatan lapangan
         * (spec v3 bagian 8.1). Tinta hampir hitam bersemu hijau, selaras dengan merek.
         *
         *   text-primary   di atas canvas   14,43:1   sumur 13,32:1
         *   text-secondary di atas canvas    6,91:1   sumur  6,38:1
         *   text-muted     di atas canvas    5,08:1   sumur  4,69:1
         */
        --text-primary: 29 35 32;
        --text-secondary: 78 85 81;
        --text-muted: 98 105 101;
        --text-inverse: 255 255 255;

        --canvas: 246 243 236;
        --surface: 255 255 255;
        --surface-sunken: 238 234 225;
        --surface-inverse: 29 35 32;

        --border-subtle: 226 221 210;

        /*
         * Hijau merek sebagai LATAR DEPAN: tautan, teks penekanan, garis grafik.
         *
         * Dipisahkan dari brand-700 yang menjadi ISIAN tombol karena keduanya berpisah
         * jalan di tema gelap. Di tema terang nilainya sama.
         */
        --brand-ink: 35 79 50;

        /* Tanda dan wordmark logo, mengikuti aturan yang sama dengan brand-ink. */
        --logo-mark: 35 79 50;
        --logo-type: 29 35 32;

        /* Tirai di belakang lembar bawah dan dialog, dan warna bayangan satu elevasi. */
        --scrim: 15 19 17;
        --bayangan: 29 35 32;

        --scrollbar-thumb: 181 175 163;
        --scrollbar-thumb-hover: 140 133 121;

        color-scheme: light;
    }

    /*
     * Tema gelap (DK-17, keputusan pemilik D5; nilai v3 dari palet lapangan prototype,
     * spec v3 bagian 8.1).
     *
     * Dua blok yang isinya identik, dan itu disengaja: blok pertama berlaku ketika
     * sistem gelap dan pengguna tidak memaksa terang; blok kedua ketika pengguna
     * memilih gelap lewat cookie yang dirender server sebagai data-theme. Format token
     * "R G B" tidak dapat memakai light-dark(), jadi duplikasinya tidak terhindarkan.
     * ColourContrastTest menjaga keduanya tetap sama dan menghitung ulang setiap
     * pasangan di tema ini.
     *
     * Kanvas #0F1311, bukan hitam: bayangan dan elevasi tetap terbaca. Beda permukaan dan
     * kanvas kecil, jadi kartu dan lapisan melayang mengandalkan garis border-subtle.
     *
     * Skala warna dipetakan ulang menurut PERAN, bukan nilainya: bg-brand-100 tetap
     * "latar lembut" dan text-brand-900 tetap "teks pekat di atasnya". Isian tombol
     * merek menjadi hijau muda #8FD3A0 dengan teks on-brand gelap (9,33:1); isian bahaya
     * tetap merah bata dengan teks putih (4,92:1).
     */
    @media (prefers-color-scheme: dark) {
        :root:not([data-theme='light']) {
            --brand-50: 22 38 28;
            --brand-100: 29 51 38;
            --brand-200: 40 69 47;
            --brand-300: 59 101 71;
            --brand-500: 111 191 132;
            --brand-600: 143 211 160;
            --brand-700: 143 211 160;
            --brand-800: 166 221 179;
            --brand-900: 181 228 193;

            --on-brand: 15 36 24;

            --warn-50: 42 33 18;
            --warn-100: 58 44 18;
            --warn-300: 122 90 34;
            --warn-600: 240 194 122;
            --warn-900: 240 194 122;

            --community-50: 24 34 47;
            --community-500: 111 147 201;
            --community-900: 157 187 230;

            --danger-50: 43 21 18;
            --danger-100: 61 27 22;
            --danger-300: 122 51 38;
            --danger-600: 192 74 51;
            --danger-700: 168 58 42;
            --danger-900: 242 165 151;

            --fit-cocok: 29 51 38;
            --fit-cocok-teks: 181 228 193;
            --fit-perlu: 58 44 18;
            --fit-perlu-teks: 240 194 122;
            --fit-kurang: 61 27 22;
            --fit-kurang-teks: 242 165 151;
            --fit-belum: 37 45 40;
            --fit-belum-teks: 183 191 185;

            --status-buka: 111 191 132;
            --status-dibatasi: 240 194 122;
            --status-tutup: 224 128 108;
            --status-belum: 138 149 142;

            --accent-pos: 232 176 75;

            --control-border: 127 139 132;

            --text-primary: 242 239 232;
            --text-secondary: 183 191 185;
            --text-muted: 154 163 157;
            --text-inverse: 15 19 17;

            --canvas: 15 19 17;
            --surface: 27 34 30;
            --surface-sunken: 21 26 23;
            --surface-inverse: 242 239 232;

            --border-subtle: 45 55 49;

            --brand-ink: 143 211 160;
            --logo-mark: 143 211 160;
            --logo-type: 242 239 232;

            --scrim: 5 7 6;
            --bayangan: 5 7 6;

            --scrollbar-thumb: 58 69 63;
            --scrollbar-thumb-hover: 86 99 91;

            color-scheme: dark;
        }
    }

    :root[data-theme='dark'] {
        --brand-50: 22 38 28;
        --brand-100: 29 51 38;
        --brand-200: 40 69 47;
        --brand-300: 59 101 71;
        --brand-500: 111 191 132;
        --brand-600: 143 211 160;
        --brand-700: 143 211 160;
        --brand-800: 166 221 179;
        --brand-900: 181 228 193;

        --on-brand: 15 36 24;

        --warn-50: 42 33 18;
        --warn-100: 58 44 18;
        --warn-300: 122 90 34;
        --warn-600: 240 194 122;
        --warn-900: 240 194 122;

        --community-50: 24 34 47;
        --community-500: 111 147 201;
        --community-900: 157 187 230;

        --danger-50: 43 21 18;
        --danger-100: 61 27 22;
        --danger-300: 122 51 38;
        --danger-600: 192 74 51;
        --danger-700: 168 58 42;
        --danger-900: 242 165 151;

        --fit-cocok: 29 51 38;
        --fit-cocok-teks: 181 228 193;
        --fit-perlu: 58 44 18;
        --fit-perlu-teks: 240 194 122;
        --fit-kurang: 61 27 22;
        --fit-kurang-teks: 242 165 151;
        --fit-belum: 37 45 40;
        --fit-belum-teks: 183 191 185;

        --status-buka: 111 191 132;
        --status-dibatasi: 240 194 122;
        --status-tutup: 224 128 108;
        --status-belum: 138 149 142;

        --accent-pos: 232 176 75;

        --control-border: 127 139 132;

        --text-primary: 242 239 232;
        --text-secondary: 183 191 185;
        --text-muted: 154 163 157;
        --text-inverse: 15 19 17;

        --canvas: 15 19 17;
        --surface: 27 34 30;
        --surface-sunken: 21 26 23;
        --surface-inverse: 242 239 232;

        --border-subtle: 45 55 49;

        --brand-ink: 143 211 160;
        --logo-mark: 143 211 160;
        --logo-type: 242 239 232;

        --scrim: 5 7 6;
        --bayangan: 5 7 6;

        --scrollbar-thumb: 58 69 63;
        --scrollbar-thumb-hover: 86 99 91;

        color-scheme: dark;
    }

    /*
     * Tema lapangan (DK-49): mode Mendaki mengikuti tema pengguna, dan akar mode
     * lapangan (W6) memasang data-tema-lapangan="terang" atau "gelap". Kedua blok hanya
     * menimpa token yang dibaca di jalur. Informasi kritis (nama pos, angka jarak,
     * tombol Jeda dan Selesai) paling sedikit 7:1; di lapangan gelap cincin fokus memakai
     * kuning pos (9,58:1), satu-satunya tempat kuning pos membawa arti karena berdiri di
     * atas gelap.
     */
    [data-tema-lapangan='terang'] {
        --canvas: 255 255 255;
        --surface: 255 255 255;
        --surface-sunken: 238 234 225;
        --border-subtle: 201 194 180;
        --control-border: 94 87 76;
        --text-primary: 15 19 17;
        --text-secondary: 47 56 51;
        --text-muted: 67 76 71;
        --brand-600: 27 77 44;
        --brand-700: 27 77 44;
        --brand-800: 16 51 29;
        --brand-ink: 27 77 44;
        --on-brand: 255 255 255;
        --accent-pos: 232 176 75;

        color-scheme: light;
    }

    [data-tema-lapangan='gelap'] {
        --canvas: 15 19 17;
        --surface: 27 34 30;
        --surface-sunken: 21 26 23;
        --border-subtle: 45 55 49;
        --control-border: 142 154 147;
        --text-primary: 242 239 232;
        --text-secondary: 201 207 202;
        --text-muted: 174 183 177;
        --brand-600: 232 176 75;
        --brand-700: 143 211 160;
        --brand-800: 166 221 179;
        --brand-ink: 143 211 160;
        --on-brand: 15 36 24;
        --accent-pos: 232 176 75;

        color-scheme: dark;
    }

    /*
     * Latar dan warna teks dipasang pada html dan body, bukan hanya pada wadah dalam.
     * Safari mewarnai bilah alat dari latar html, dan area di luar wadah (overscroll,
     * ruang di bawah halaman pendek) mengambil warna html; tanpa ini tema gelap
     * menampilkan pita putih di tepi layar.
     */
    html,
    body {
        background-color: rgb(var(--canvas));
        color: rgb(var(--text-primary));
    }

    /*
     * Offset cincin fokus mengikuti kanvas. Bawaan Tailwind adalah putih, dan cincin
     * fokus berhalo putih di atas kanvas gelap terbaca sebagai kesalahan render.
     */
    *,
    ::before,
    ::after {
        --tw-ring-offset-color: rgb(var(--canvas));
    }

    /*
     * Kontrol formulir dari @tailwindcss/forms membawa latar putih dan teks hitam yang
     * ditulis langsung, bukan lewat token. Di sini keduanya diarahkan ke permukaan dan
     * teks tema supaya isian tidak menjadi kotak putih di halaman gelap.
     */
    input:not([type]),
    [type='text'],
    [type='email'],
    [type='url'],
    [type='password'],
    [type='number'],
    [type='date'],
    [type='datetime-local'],
    [type='month'],
    [type='search'],
    [type='tel'],
    [type='time'],
    [type='week'],
    [multiple],
    textarea,
    select {
        background-color: rgb(var(--surface));
        color: rgb(var(--text-primary));
        border-color: rgb(var(--control-border));
    }

    [type='checkbox'],
    [type='radio'] {
        background-color: rgb(var(--surface));
        border-color: rgb(var(--control-border));
    }

    /*
     * Tanda centang dan titik radio @tailwindcss/forms digambar putih. Di tema gelap isian
     * merek berwarna hijau muda, dan putih di atasnya hanya 1,75:1; tandanya diganti warna
     * on-brand gelap #0F2418 (9,33:1). Data SVG tidak dapat membaca var(), jadi warnanya
     * ditulis di sini, satu-satunya berkas tempat nilai warna boleh ditulis.
     */
    @media (prefers-color-scheme: dark) {
        :root:not([data-theme='light']) [type='checkbox']:checked {
            background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 16 16' fill='%230f2418' xmlns='http://www.w3.org/2000/svg'%3e%3cpath d='M12.207 4.793a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0l-2-2a1 1 0 011.414-1.414L6.5 9.086l4.293-4.293a1 1 0 011.414 0z'/%3e%3c/svg%3e");
        }

        :root:not([data-theme='light']) [type='radio']:checked {
            background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 16 16' fill='%230f2418' xmlns='http://www.w3.org/2000/svg'%3e%3ccircle cx='8' cy='8' r='3'/%3e%3c/svg%3e");
        }
    }

    :root[data-theme='dark'] [type='checkbox']:checked,
    [data-tema-lapangan='gelap'] [type='checkbox']:checked {
        background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 16 16' fill='%230f2418' xmlns='http://www.w3.org/2000/svg'%3e%3cpath d='M12.207 4.793a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0l-2-2a1 1 0 011.414-1.414L6.5 9.086l4.293-4.293a1 1 0 011.414 0z'/%3e%3c/svg%3e");
    }

    :root[data-theme='dark'] [type='radio']:checked,
    [data-tema-lapangan='gelap'] [type='radio']:checked {
        background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 16 16' fill='%230f2418' xmlns='http://www.w3.org/2000/svg'%3e%3ccircle cx='8' cy='8' r='3'/%3e%3c/svg%3e");
    }

    ::placeholder {
        color: rgb(var(--text-muted));
    }

    /*
     * Teks bebas dari pengguna mencapai 1000 karakter, dan tidak ada yang memaksanya
     * berisi spasi. Satu URL panjang atau tempelan tanpa spasi pada catatan trip cukup
     * untuk meluber keluar kotaknya: overflow-wrap bawaan tidak pernah memotong kata,
     * jadi untaian itu mendorong lebar halaman dan seluruh layar tergeser menyamping
     * di telepon. Yang rusak bukan satu paragraf, melainkan tata letak halamannya.
     *
     * Aturan ini hanya bekerja ketika sebuah kata memang tidak muat. Teks normal tidak
     * berubah sama sekali, jadi biayanya nol dan cakupannya seluruh aplikasi, termasuk
     * halaman yang belum ditulis.
     */
    body {
        overflow-wrap: break-word;

        /*
         * Ukuran baca dasar ditetapkan, bukan diwarisi.
         *
         * Aplikasi ini memakai text-sm untuk hampir segalanya: 363 dari 426 pemakaian
         * ukuran teks adalah text-sm atau text-xs. Ukuran itu pantas untuk tabel admin
         * dan tidak pantas untuk keterangan yang dibaca pendaki di bawah matahari
         * sambil berdiri di jalur.
         *
         * Menaikkan akarnya menggeser seluruh skala Tailwind sekaligus, sehingga
         * text-sm menjadi ukuran yang masih terbaca dan text-base menjadi nyaman,
         * tanpa menyentuh satu pun kelas di ratusan berkas view.
         */
        font-size: 16.5px;
        line-height: 1.6;

        /* Huruf modern membawa fitur yang mati secara bawaan. Keduanya merapikan
           pasangan huruf tanpa mengubah satu pun ukuran. */
        font-feature-settings: 'kern' 1, 'liga' 1;
        -webkit-font-smoothing: antialiased;
    }

    /*
     * Judul halaman memakai serif.
     *
     * Serif memberi bobot editorial yang tidak dapat dicapai ketebalan huruf sans, dan
     * membedakan judul dari antarmuka tanpa membesarkannya sampai berteriak. Dipasang
     * pada h1 di lapisan base supaya setiap halaman mendapatnya, termasuk yang ditulis
     * besok.
     */
    h1 {
        font-family: theme('fontFamily.serif');
        font-weight: 500;
        letter-spacing: -0.02em;
        line-height: 1.15;

        /* Judul dua baris kerap menyisakan satu kata sendirian di baris kedua, dan itu
           terbaca sebagai kelalaian tata letak, bukan sebagai pilihan. */
        text-wrap: balance;
    }

    /*
     * Paragraf memakai pretty, bukan balance.
     *
     * balance menyeimbangkan seluruh blok dan peramban berhenti menerapkannya setelah
     * beberapa baris, jadi ia pantas untuk judul dan sia-sia untuk prosa. pretty hanya
     * menjaga baris terakhir tidak tersisa satu kata, dan itu yang dibutuhkan paragraf.
     *
     * Di lapisan base, bukan sebagai kelas yang harus diingat setiap penulis view: kelas
     * yang harus diingat akan terlupakan di halaman berikutnya, dan CSS yang tidak pernah
     * dipakai siapa pun adalah bita mati.
     */
    p {
        text-wrap: pretty;
    }

    /*
     * Permukaan yang digambar peramban, bukan oleh kita.
     *
     * Sorotan teks, kursor ketik, batang gulir, jarak garis bawah, dan angka pada data
     * semuanya punya bawaan yang bukan milik palet mana pun. Selama dibiarkan, tiap
     * halaman membawa sepotong tampilan yang bukan tampilan produk ini.
     */

    /* Pasangan warna yang rasionya sudah dihitung dan dijaga ColourContrastTest,
       bukan pasangan baru yang ditebak enak dipandang. */
    ::selection {
        background-color: rgb(var(--brand-100));
        color: rgb(var(--brand-900));
    }

    :root {
        caret-color: rgb(var(--brand-700));

        /* Kotak centang dan radio bawaan berwarna biru sistem operasi. */
        accent-color: rgb(var(--brand-700));

        /* Firefox. Ibu jari lalu alurnya. */
        scrollbar-color: rgb(var(--scrollbar-thumb)) transparent;
    }

    ::-webkit-scrollbar {
        width: 10px;
        height: 10px;
    }

    ::-webkit-scrollbar-track {
        background: transparent;
    }

    ::-webkit-scrollbar-thumb {
        background-color: rgb(var(--scrollbar-thumb));
        border: 3px solid rgb(var(--canvas));
        border-radius: 9999px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background-color: rgb(var(--scrollbar-thumb-hover));
    }

    /*
     * Garis bawah pada jarak bawaan memotong ekor huruf g, j, dan y. Bahasa Indonesia
     * penuh dengan ketiganya: "jalur", "gunung", "belum diketahui".
     */
    a {
        text-underline-offset: 0.2em;
    }

    /*
     * Pengukuran memakai huruf berlebar tetap dan keluarga mono.
     *
     * Jarak, elevation gain, durasi, dan koordinat adalah data, bukan prosa. Huruf
     * proporsional membuat kolom angka bergoyang saat nilainya berganti, dan mata
     * membaca deret angka jauh lebih cepat ketika lebarnya tetap.
     */
    table,
    dd,
    time,
    [data-angka] {
        font-variant-numeric: tabular-nums;
        font-feature-settings: 'tnum' 1;
    }

    /*
     * Kurangi gerak, dihormati sekali untuk seluruh aplikasi.
     *
     * Sebagian orang mual karena gerak di layar, sebagian lagi menyetelnya karena
     * perangkatnya lambat, dan keduanya menyatakannya lewat satu setelan yang sama
     * (WCAG 2.3.3).
     *
     * Dipangkas menjadi hampir nol, bukan dimatikan. Durasi satu milidetik tetap
     * menyampaikan bahwa sesuatu berubah dan tetap memicu event transisi yang mungkin
     * ditunggu kode lain; menghapusnya sama sekali menghilangkan umpan baliknya, bukan
     * hanya perjalanannya.
     *
     * Di sini, bukan sebagai motion-reduce: pada tiap elemen: ada dua puluh pemakaian
     * transition yang sudah ada sebelum aturan ini, dan yang ditulis besok tidak akan
     * ingat menambahkannya.
     */
    @media (prefers-reduced-motion: reduce) {
        *,
        *::before,
        *::after {
            animation-duration: 1ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 1ms !important;
            scroll-behavior: auto !important;
        }
    }
}

/*
| Material v2 (DK-26; riset docs/riset/2026-09-25-uiux/01 §4.1)
|
| Kanvas dan isi selalu solid. "Chrome" adalah lapisan yang melayang di atas isi:
| bilah tab, bilah atas, strip pendakian aktif, kendali peta. Chrome solid sebagai
| bawaan. Kaca hanya peningkatan bersyarat: pengguna tidak memilih Solid, perangkat
| lolos petunjuk kemampuan (dasar.js memasang data-efek="penuh"), peramban mendukung
| backdrop-filter, dan elemennya tidak berada di atas peta (kanvas WebGL membuat blur
| dihitung ulang setiap bingkai). Paling banyak dua elemen kaca per layar; tidak pernah
| pada kartu, status resmi, laporan komunitas, atau dialog. Aturan penurun ditulis
| terakhir supaya selalu menang.
*/
@layer components {
    .chrome {
        background-color: rgb(var(--surface));
        border-color: rgb(var(--border-subtle));
    }

    @supports ((backdrop-filter: blur(1px)) or (-webkit-backdrop-filter: blur(1px))) {
        :where(:root[data-efek='penuh']) .chrome:where(:not([data-di-atas-peta])) {
            background-color: rgb(var(--surface) / 0.8);
            -webkit-backdrop-filter: blur(16px) saturate(1.2);
            backdrop-filter: blur(16px) saturate(1.2);
        }
    }

    @media (prefers-reduced-transparency: reduce), (prefers-contrast: more), (forced-colors: active) {
        .chrome {
            background-color: rgb(var(--surface));
            -webkit-backdrop-filter: none;
            backdrop-filter: none;
        }
    }

    @media (prefers-contrast: more) {
        .chrome {
            border-color: rgb(var(--text-primary));
        }
    }
}

/*
| Kurangi gerak dari pengaturan aplikasi (Profil, Tampilan), bukan hanya dari sistem.
| Sebagian pengguna tidak tahu letak setelan sistemnya, dan peramban lama tidak
| meneruskannya. Nilainya dirender server sebagai data-gerak di <html>, sama dengan tema.
*/
:root[data-gerak='kurangi'] *,
:root[data-gerak='kurangi'] *::before,
:root[data-gerak='kurangi'] *::after {
    animation-duration: 1ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 1ms !important;
    scroll-behavior: auto !important;
}

/*
 * Kontras tinggi: batas yang tadinya hampir tak terlihat menjadi tegas. `:root:root`
 * (spesifisitas dua kelas) sengaja: blok tema gelap memakai :root[data-theme="dark"]
 * dan :root:not([data-theme="light"]), dan aturan ini harus menang di kedua tema.
 */
@media (prefers-contrast: more) {
    :root:root {
        --border-subtle: var(--control-border);
    }
}
```

- [ ] **Step 6: Tulis ulang `tailwind.config.js`.** Blok `fontFamily` dan komentarnya tetap; Tugas 2 yang menggantinya.

```js
import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/**
 * Warna merek dibaca dari design token di resources/css/app.css.
 * Untuk mengubah palet aplikasi, sunting token di sana, jangan menambah
 * warna mentah di sini atau di view.
 */
const token = (name) => `rgb(var(--${name}) / <alpha-value>)`;

/** @type {import('tailwindcss').Config} */
export default {
    // Halaman React (resources/js) wajib ikut dipindai. Tanpa baris itu, setiap kelas
    // yang hanya dipakai di komponen .tsx tidak pernah dibuat, dan kegagalannya diam:
    // navigasi bawah kehilangan inset-x-0 dan melebar melewati layar, tanpa galat
    // apa pun di build maupun di peramban (ditemukan lewat pengukuran 25 Sep 2026).
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.{js,jsx,ts,tsx}',
    ],

    theme: {
        extend: {
            /*
             * Tiga suara, bukan satu.
             *
             * Diukur sebelum diubah: 363 dari 426 pemakaian ukuran teks adalah text-sm
             * atau text-xs, dan fontnya Figtree bawaan scaffolding. Satu huruf generik
             * pada satu ukuran kecil membuat setiap layar terbaca seperti sel tabel,
             * berapa pun kecerdasan yang ada di belakangnya.
             *
             * Plus Jakarta Sans dirancang Tokotype untuk identitas kota Jakarta.
             * Dipilih bukan karena tampak mahal, melainkan karena ia huruf Indonesia
             * untuk produk tentang gunung-gunung Indonesia; alasan itu bertahan ketika
             * seleranya berubah, sedangkan "sedang populer" tidak.
             *
             * Newsreader dipakai untuk judul halaman. Serif memberi bobot editorial yang
             * tidak dapat dicapai ketebalan huruf sans, dan membedakan judul dari
             * antarmuka tanpa membesarkannya sampai berteriak.
             *
             * Huruf mono memakai tumpukan sistem, tanpa unduhan.
             *
             * Komentar sebelumnya di sini menyebut JetBrains Mono "khusus pengukuran:
             * jarak, elevation gain, durasi, dan koordinat". Ketika akhirnya diukur,
             * tidak satu pun pengukuran memakainya: semuanya memakai tabular-nums pada
             * huruf sans, dan font-mono muncul tepat dua kali, keduanya pada kunci tugas
             * penjadwal di satu halaman admin.
             *
             * Jadi seluruh pengguna mengunduh dua bobot huruf untuk dua baris yang tidak
             * pernah mereka lihat. Pertanyaan R-06 "mengapa huruf ini" ternyata punya
             * jawaban yang lebih sederhana daripada mencari penggantinya: tidak perlu
             * huruf mono sama sekali. Kunci penjadwal seperti weather:refresh justru
             * persis yang pantas memakai huruf mono bawaan sistem.
             */
            fontFamily: {
                sans: ['Plus Jakarta Sans', ...defaultTheme.fontFamily.sans],
                serif: ['Newsreader', ...defaultTheme.fontFamily.serif],
                mono: defaultTheme.fontFamily.mono,
            },

            colors: {
                // Batas kontrol interaktif, dipisahkan dari abu dekoratif karena
                // WCAG 1.4.11 hanya mengatur yang interaktif. Juga isian sakelar mati.
                control: token('control-border'),

                /*
                 * Lapisan semantik. Namanya menyebut peran, bukan warna: text-primary
                 * bukan text-gray-900. View tidak lagi memilih abu sendiri-sendiri, dan
                 * mengubah rasa seluruh aplikasi cukup menyentuh nilainya di app.css.
                 */
                canvas: token('canvas'),
                surface: {
                    DEFAULT: token('surface'),
                    sunken: token('surface-sunken'),
                    // Permukaan kebalikan: toast urungkan.
                    inverse: token('surface-inverse'),
                },
                subtle: token('border-subtle'),
                primary: token('text-primary'),
                secondary: token('text-secondary'),
                muted: token('text-muted'),
                inverse: token('text-inverse'),

                // Teks di atas isian merek (tombol primer, langkah selesai): putih di
                // tema terang, gelap di tema gelap (spec v3 bagian 8.1).
                'on-brand': token('on-brand'),

                brand: {
                    // Hijau sebagai latar depan (tautan, penekanan, garis grafik).
                    // Terpisah dari 700 yang menjadi isian tombol, karena keduanya
                    // berpisah jalan di tema gelap; alasannya di app.css.
                    ink: token('brand-ink'),
                    50: token('brand-50'),
                    100: token('brand-100'),
                    200: token('brand-200'),
                    300: token('brand-300'),
                    500: token('brand-500'),
                    600: token('brand-600'),
                    700: token('brand-700'),
                    800: token('brand-800'),
                    900: token('brand-900'),
                },
                community: {
                    50: token('community-50'),
                    500: token('community-500'),
                    900: token('community-900'),
                },
                warn: {
                    50: token('warn-50'),
                    100: token('warn-100'),
                    300: token('warn-300'),
                    600: token('warn-600'),
                    900: token('warn-900'),
                },
                danger: {
                    50: token('danger-50'),
                    100: token('danger-100'),
                    300: token('danger-300'),
                    600: token('danger-600'),
                    700: token('danger-700'),
                    900: token('danger-900'),
                },

                // Tiga label kecocokan publik (R-004) dan keadaan Belum dinilai (R-006):
                // latar dan teksnya dihitung berpasangan di ColourContrastTest.
                fit: {
                    cocok: { DEFAULT: token('fit-cocok'), teks: token('fit-cocok-teks') },
                    perlu: { DEFAULT: token('fit-perlu'), teks: token('fit-perlu-teks') },
                    kurang: { DEFAULT: token('fit-kurang'), teks: token('fit-kurang-teks') },
                    belum: { DEFAULT: token('fit-belum'), teks: token('fit-belum-teks') },
                },

                // Status akses (DK-32): penanda non-teks, selalu bersama bentuk dan kata.
                status: {
                    buka: token('status-buka'),
                    dibatasi: token('status-dibatasi'),
                    tutup: token('status-tutup'),
                    belum: token('status-belum'),
                },

                // Satu aksen: kuning pos. Tidak pernah membawa arti sendirian.
                aksen: {
                    pos: token('accent-pos'),
                },

                logo: {
                    mark: token('logo-mark'),
                    type: token('logo-type'),
                },
            },

            /*
             * Satu elevasi, untuk lapisan yang benar-benar melayang di atas halaman:
             * bilah tab, bar aksi, lembar bawah, dialog, toast, kartu pratinjau peta.
             * Warnanya token bayangan (tinta hangat), bukan hitam murni: bayangan hitam di
             * atas kanvas hangat terbaca kelabu dan bukan seperti bayangan.
             */
            boxShadow: {
                overlay: '0 12px 32px -8px rgb(var(--bayangan) / 0.18)',
            },

            // Skala radius spec v3 bagian 8.4. rounded-lg adalah peran kartu di DESIGN.md,
            // jadi ia membaca token kartu dan 34 kartu yang ada ikut tanpa disunting.
            borderRadius: {
                kecil: 'var(--radius-kecil)',
                control: 'var(--radius-control)',
                tombol: 'var(--radius-tombol)',
                lg: 'var(--radius-kartu)',
                kartu: 'var(--radius-kartu)',
                utama: 'var(--radius-utama)',
                lembar: 'var(--radius-lembar)',
                lencana: 'var(--radius-lencana)',
            },

            // Token gerak dari app.css (DK-26): durasi dan kurva pegas yang dihitung.
            transitionDuration: {
                sentuh: 'var(--dur-sentuh)',
                cepat: 'var(--dur-cepat)',
                sedang: 'var(--dur-sedang)',
                lambat: 'var(--dur-lambat)',
            },
            transitionTimingFunction: {
                gerak: 'var(--ease-gerak)',
                pudar: 'var(--ease-pudar)',
                lentur: 'var(--ease-lentur)',
            },
        },
    },

    plugins: [forms],
};
```

- [ ] **Step 7: Samakan warna bilah peramban.** Di `config/brand.php`, ganti blok dari baris
`     * Warna bilah peramban dan jendela PWA per tema. Terang memakai hijau merek yang` sampai penutup blok
`'background_color' => [...]` dengan:

```php
    /*
     * Warna bilah peramban dan jendela PWA per tema. Terang memakai isian merek v3
     * (brand-700, #234F32); gelap memakai kanvas gelap v3 (#0F1311) supaya bilah
     * menyatu dengan halaman. Nilai heksa di sini harus sama dengan token --brand-700
     * terang dan --canvas gelap di resources/css/app.css; BrandIdentityTest yang
     * menjaganya.
     */
    'theme_color' => [
        'light' => '#234f32',
        'dark' => '#0f1311',
    ],

    'background_color' => [
        'light' => '#f6f3ec',
        'dark' => '#0f1311',
    ],
```

- [ ] **Step 8: Warnai ulang favikon.** Ganti seluruh isi `public/favicon.svg` dengan berkas di bawah. Ikon PNG di
`public/icons/` tidak diubah: aset logo menunggu gerbang DK-16, dan Tugas 12 mencatatnya sebagai sisa.

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="none" stroke="#234f32" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
  <style>@media (prefers-color-scheme: dark) { svg { stroke: #8fd3a0; } }</style>
  <path d="M2.5 13.5 L9 10.5 L5 7.5 L10 5"/>
  <circle cx="12" cy="4" r="2.25"/>
</svg>
```

- [ ] **Step 9: Ganti teks putih di atas isian merek.** Di 14 tempat pada daftar Files baris kedua, ganti `text-white`
menjadi `text-on-brand`, dengan varian yang sama: `file:text-white` menjadi `file:text-on-brand` di
`Admin/TrailGeometry.tsx:159`, `focus:text-white` menjadi `focus:text-on-brand` di `inertia.blade.php:38`. Tidak ada
perubahan lain. `text-white` pada varian `danger` di `Button.tsx:39` tetap, karena isian bahaya tetap gelap di kedua tema
(4,92:1 di tema gelap).

Run: `grep -rn "text-white" resources/js resources/views --include=*.tsx --include=*.php | grep -v "resources/js/actions\|resources/js/routes\|resources/js/wayfinder"`
Expected: tepat satu baris, `resources/js/components/ui/Button.tsx:39:        danger: 'bg-danger-600 text-white hover:bg-danger-700 focus-visible:ring-danger-600',`

- [ ] **Step 10: Samakan warna cadangan peta.** MapLibre membaca warna lewat `warnaToken(nama, cadangan)`; cadangan hanya
dipakai bila CSS belum terbaca, tetapi nilainya masih palet lama. Ganti tepat literal berikut, tanpa perubahan lain:
- `resources/js/components/peta/warnaToken.ts` baris 2 dan 3 komentar: `"4 120 87" menjadi` menjadi `"35 79 50" menjadi`, dan `"rgb(4, 120, 87)".` menjadi `"rgb(35, 79, 50)".`
- `resources/js/components/trails/PetaJelajah.tsx`: `'#047857'` menjadi `'#234f32'`, `'#d97706'` menjadi `'#b7791f'`, `'#e11d48'` menjadi `'#a33b2b'`, `'#44403c'` menjadi `'#4e5551'`.
- `resources/js/components/trails/rinci/PetaJalur.tsx`, `resources/js/components/community/PetaGaris.tsx`, `resources/js/components/trips/PetaPendakian.tsx`: `'rgb(4, 120, 87)'` menjadi `'rgb(35, 79, 50)'`.

Run: `grep -rn "4, 120, 87\|047857\|d97706\|e11d48\|44403c" resources/js | grep -v "resources/js/actions\|resources/js/routes\|resources/js/wayfinder"`
Expected: tidak ada keluaran.

- [ ] **Step 11: Jalankan test tugas ini sampai lulus.**

Run: `php artisan test --compact --filter='ColourContrastTest|SemanticTokenTest|BrandIdentityTest'`
Expected: PASS, 178 test.

- [ ] **Step 12: Jalankan test tetangga yang membaca stylesheet dan token.**

Run: `php artisan test --compact --filter='SurfaceSystemTest|DesignSystemTest|InertiaLayoutTest|ThemePreferenceTest|MotionTest|BrowserSurfacesTest|ReadingMeasureTest|LongInputLayoutTest|TypographySystemTest|DesignDirectionTest|TapTargetTest|ProgressiveWebAppTest'`
Expected: PASS. `SurfaceSystemTest::test_the_canvas_is_warm` tetap lulus (merah 246 lebih besar dari biru 236).

- [ ] **Step 13: Gerbang tugas.**

Run: `vendor/bin/pint --dirty --format agent`
Expected: `{"tool":"pint","result":"passed"}` atau berkas dirapikan tanpa perubahan makna.

Run: `npx tsc --noEmit`
Expected: keluar tanpa keluaran galat.

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal. Pada simulasi rencana: 1.427 test, 1.426 lulus, 1 dilewati (dasar sebelum W1: 1.338).

- [ ] **Step 14: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 2: Huruf Plus Jakarta Sans dihosting sendiri, skala tipe, dan lantai 12 px

Induk W1 butir 2 (DK-38). Serif judul dan Bunny Fonts dilepas; satu keluarga variabel dari asal sendiri, skala tipe bernama,
dan lantai 12 px yang dijaga test.

**Files:**
- Create: `public/fonts/plus-jakarta-sans/plus-jakarta-sans-latin-wght-normal.woff2`, `public/fonts/plus-jakarta-sans/plus-jakarta-sans-latin-ext-wght-normal.woff2`, `public/fonts/plus-jakarta-sans/OFL.txt` (diunduh, bukan ditulis)
- Create: `tests/Feature/TypeFloorTest.php`
- Modify: `resources/css/app.css` (empat suntingan), `tailwind.config.js` (blok `fontFamily` diganti, blok `fontSize` baru), `resources/views/components/brand/head.blade.php`, `app/Http/Middleware/SecurityHeaders.php`, `resources/js/layouts/NavLinks.tsx:97`, `resources/js/components/ui/PageHeader.tsx` (tulis ulang), `docs/lisensi-pihak-ketiga.md` (bagian Huruf)
- Test: `tests/Feature/TypographySystemTest.php` (tulis ulang), `tests/Feature/SecurityHeadersTest.php`, `tests/Feature/LandingPageTest.php`, `tests/Feature/SurfaceSystemTest.php`, `tests/Feature/ThirdPartyLicenseTest.php`, `tests/Feature/RepoHygieneTest.php`

**Interfaces:**
- Consumes: token dan radius Tugas 1; komentar asal-usul di `docs/lisensi-pihak-ketiga.md`.
- Produces:
  - `font-sans` = `'Plus Jakarta Sans', 'PJS Cadangan Roboto', 'PJS Cadangan Arial', ...defaultTheme.fontFamily.sans`; `font-serif` tidak ada lagi.
  - Skala tipe: `text-tampilan` 30/800, `text-judul` 28/800, `text-judul-kartu` 23/800, `text-bagian` 18/700, `text-subjudul` 17/700, `text-isi` 16, `text-kecil` 14, `text-meta` 13, `text-label` 12 (px/bobot). Kelas Tailwind bawaan (`text-sm`, `text-xs`) tetap tersedia untuk halaman lama; halaman baru W2 sampai W8 memakai nama peran.
  - `h1` di lapisan base: bobot 800, tanpa `font-family`; `output` ikut angka berlebar tetap (dipakai `Stepper` Tugas 6).

- [ ] **Step 1: Tulis test tipografi yang gagal.** Ganti seluruh isi `tests/Feature/TypographySystemTest.php` dengan:

```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Sistem tipografi.
 *
 * Diukur sebelum diubah, dan hasilnya menjelaskan keluhan "masih terlihat seperti CRUD"
 * lebih baik daripada daftar fitur mana pun: 363 dari 426 pemakaian ukuran teks adalah
 * text-sm atau text-xs, hanya satu berkas memakai serif atau mono, dan fontnya Figtree,
 * bawaan scaffolding Breeze.
 *
 * Diperbarui gelombang 1 v3 (spec v3 bagian 8.2, DK-38). Keputusan lama "tiga suara"
 * (sans antarmuka, serif judul, mono sistem) yang dimuat dari Bunny Fonts diganti satu
 * keluarga yang dihosting sendiri: prototype pemilik membawa hierarki lewat bobot dan
 * ukuran satu keluarga, dan huruf dari asal sendiri tidak bergantung pada layanan luar
 * di jaringan yang lemah. Tiga test lama berubah niat, alasannya di docblock penggantinya:
 * test_there_is_a_display_face_and_a_measurement_face menjadi
 * test_one_family_carries_headings_and_measurements,
 * test_the_fonts_come_from_a_host_the_policy_already_allows menjadi
 * test_the_fonts_come_only_from_our_own_origin, dan
 * test_no_webfont_is_loaded_for_a_handful_of_lines masuk ke
 * test_the_self_hosted_files_are_the_recorded_subsets_within_budget.
 */
class TypographySystemTest extends TestCase
{
    use SumberReact;

    /**
     * Berkas huruf yang dihosting beserta ukuran dan SHA-256-nya saat diunduh 26 September
     * 2026 dari @fontsource-variable/plus-jakarta-sans 5.3.0 (docs/lisensi-pihak-ketiga.md,
     * bagian Huruf).
     *
     * @var array<string, array{0: int, 1: string}>
     */
    private const BERKAS_HURUF = [
        'plus-jakarta-sans-latin-wght-normal.woff2' => [27348, '153fc85b70298beeb1d61a5f723331649e7f23bb77302a66e61cb3e2fbdb5e79'],
        'plus-jakarta-sans-latin-ext-wght-normal.woff2' => [21728, '38e3b8fd8045048eb311d90170a4429ed2c8f405852dc3d91b5af8452758703f'],
    ];

    /** Anggaran huruf untuk teks Latin: 100 KB (rencana induk W1, butir 2). */
    private const ANGGARAN_BITA = 102400;

    private function tailwind(): string
    {
        static $isi = null;

        return $isi ??= File::get(base_path('tailwind.config.js'));
    }

    private function css(): string
    {
        static $isi = null;

        return $isi ??= File::get(resource_path('css/app.css'));
    }

    /**
     * Isi blok fontFamily di tailwind.config.js, tanpa komentar di atasnya: komentar yang
     * menjelaskan mengapa sebuah huruf ditinggalkan justru layak disimpan.
     */
    private function blokFontFamily(): string
    {
        $this->assertSame(1, preg_match('/fontFamily:\s*\{(.*?)\n\s*\},/s', $this->tailwind(), $blok), 'Blok fontFamily tidak ditemukan.');

        return $blok[1];
    }

    /**
     * Plus Jakarta Sans dirancang Tokotype untuk identitas kota Jakarta.
     *
     * Dipilih bukan karena tampak mahal, melainkan karena ia huruf Indonesia untuk
     * produk tentang gunung-gunung Indonesia. Alasan itu bertahan ketika seleranya
     * berubah, sedangkan "sedang populer" tidak.
     */
    public function test_the_interface_face_is_not_the_scaffolding_default(): void
    {
        $this->assertStringContainsString('Plus Jakarta Sans', $this->tailwind());

        // Diperiksa pada deklarasinya, bukan pada seluruh berkas: komentar yang
        // menjelaskan mengapa huruf lamanya ditinggalkan justru layak disimpan, dan
        // versi pertama test ini menolaknya.
        $this->assertDoesNotMatchRegularExpression('/sans:\s*\[[^\]]*Figtree/i', $this->tailwind());
    }

    /**
     * Setiap layout memuat huruf yang sama.
     *
     * Halaman depan memakai layoutnya sendiri dan tertinggal memuat huruf lama, sehingga
     * orang yang pertama kali datang melihat huruf yang berbeda dari seluruh aplikasi.
     * Ditemukan test ini, bukan oleh membaca ulang.
     *
     * Diperbarui gelombang 1 v3: huruf tidak lagi dimuat dari Bunny Fonts. Yang dijaga
     * kini tiga hal: tidak satu view pun memuat huruf dari layanan luar, kepala dokumen
     * bersama memuat lebih awal subset Latin dari asal sendiri, dan templat akar React
     * memanggil kepala dokumen itu.
     */
    public function test_every_layout_loads_the_same_faces(): void
    {
        $luar = [];

        foreach (File::allFiles(resource_path('views')) as $berkas) {
            if (preg_match('/fonts\.(?:bunny\.net|googleapis\.com|gstatic\.com)/', $berkas->getContents())) {
                $luar[] = $berkas->getRelativePathname();
            }
        }

        $this->assertSame([], $luar, 'Memuat huruf dari layanan luar: '.implode(', ', $luar));

        $this->assertStringContainsString(
            '<link rel="preload" href="/fonts/plus-jakarta-sans/plus-jakarta-sans-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>',
            File::get(resource_path('views/components/brand/head.blade.php'))
        );
        $this->assertStringContainsString('<x-brand.head />', File::get(resource_path('views/inertia.blade.php')), 'Templat akar React harus memuat huruf lewat kepala dokumen bersama.');
    }

    /**
     * Satu keluarga membawa judul, antarmuka, dan angka.
     *
     * Menggantikan test_there_is_a_display_face_and_a_measurement_face, yang menuntut
     * keluarga serif untuk judul. Keputusan itu diganti DK-38 dan spec v3 bagian 8.2:
     * judul memakai Plus Jakarta Sans bobot 800, angka memakai tabular-nums pada keluarga
     * yang sama, dan huruf mono tetap tumpukan sistem tanpa unduhan. Newsreader dan Geist
     * hanya hidup di bundel prototype, bukan di aplikasi.
     */
    public function test_one_family_carries_headings_and_measurements(): void
    {
        $keluarga = $this->blokFontFamily();

        $this->assertDoesNotMatchRegularExpression('/\bserif:\s*\[/', $keluarga, 'Keluarga serif judul sudah dilepas (DK-38).');
        $this->assertDoesNotMatchRegularExpression('/Newsreader|Geist/', $keluarga);
        $this->assertMatchesRegularExpression('/mono:\s*defaultTheme\.fontFamily\.mono/', $keluarga, 'Huruf mono tetap tumpukan sistem.');

        $pemakaiSerif = [];

        foreach ($this->berkasTsx() as $berkas) {
            if (str_contains($this->tanpaKomentarJs($berkas->getContents()), 'font-serif')) {
                $pemakaiSerif[] = $berkas->getRelativePathname();
            }
        }

        foreach (File::allFiles(resource_path('views')) as $berkas) {
            if (str_contains($berkas->getContents(), 'font-serif')) {
                $pemakaiSerif[] = $berkas->getRelativePathname();
            }
        }

        $this->assertSame([], $pemakaiSerif, 'Masih memakai font-serif: '.implode(', ', $pemakaiSerif));
    }

    /**
     * Huruf hanya datang dari asal aplikasi sendiri.
     *
     * Menggantikan test_the_fonts_come_from_a_host_the_policy_already_allows. Niat lamanya,
     * huruf tidak diam-diam diblokir CSP, tetap dengan cara yang lebih ketat: tidak ada host
     * huruf luar sama sekali, CSP font-src dan style-src hanya mengizinkan asal sendiri, dan
     * setiap @font-face memuat berkas dari /fonts/.
     */
    public function test_the_fonts_come_only_from_our_own_origin(): void
    {
        $csp = File::get(app_path('Http/Middleware/SecurityHeaders.php'));

        $this->assertStringNotContainsString('fonts.bunny.net', $csp);
        $this->assertStringContainsString("\"font-src 'self' data:\"", $csp);
        $this->assertStringContainsString("\"style-src 'self' 'unsafe-inline'\"", $csp);

        preg_match_all('/src:\s*url\(([^)]+)\)/', $this->css(), $sumber);

        $this->assertNotEmpty($sumber[1], 'Tidak ada @font-face yang memuat berkas.');

        foreach ($sumber[1] as $url) {
            $this->assertStringStartsWith("'/fonts/", $url, "Huruf dimuat dari luar asal sendiri: {$url}");
        }
    }

    /**
     * Angka pengukuran memakai angka berlebar tetap.
     *
     * Jarak, elevation gain, dan durasi adalah pengukuran, bukan prosa. Angka
     * proporsional membuat kolom angka bergoyang, dan mata membaca deret angka jauh
     * lebih cepat ketika lebarnya tetap. Diperluas gelombang 1 v3: output, tempat nilai
     * stepper berganti, ikut dalam aturan yang sama.
     */
    public function test_measurements_are_set_in_the_measurement_face(): void
    {
        $this->assertStringContainsString('tabular-nums', $this->css());
        $this->assertMatchesRegularExpression('/time,\s*output,\s*\[data-angka\]\s*\{\s*font-variant-numeric:\s*tabular-nums;/', $this->css());
    }

    /**
     * Ukuran teks bawaan bukan yang terkecil.
     *
     * Diperbarui gelombang 1 v3: nilainya kini dipaku, 16 px dengan tinggi baris 1,5, sama
     * dengan text-isi (spec v3 bagian 8.2). Versi lama hanya menuntut font-size ada di
     * body, dan 16,5 px yang dipasang di sana tidak pernah menggeser skala Tailwind seperti
     * yang diklaim komentarnya, karena rem dihitung dari html.
     */
    public function test_the_default_reading_size_is_set_deliberately(): void
    {
        $this->assertMatchesRegularExpression(
            '/body\s*\{[^}]*font-size:\s*1rem;[^}]*line-height:\s*1\.5;/s',
            $this->css(),
            'Ukuran baca dasar harus 16 px dengan tinggi baris 1,5.'
        );
    }

    /**
     * Berkas huruf yang dihosting adalah subset yang tercatat, dalam anggaran.
     *
     * Ukuran dan hash dipaku supaya penggantian berkas tanpa memperbarui catatan asal-usul
     * di docs/lisensi-pihak-ketiga.md ketahuan. Anggaran 100 KB untuk teks Latin; subset
     * Latin dan Latin Extended bersama 49.076 bita.
     *
     * Menggantikan test_no_webfont_is_loaded_for_a_handful_of_lines. Niatnya, tidak ada
     * huruf yang diunduh untuk segelintir baris, kini dijaga dengan menolak berkas huruf
     * lain di public/fonts, termasuk huruf mono.
     */
    public function test_the_self_hosted_files_are_the_recorded_subsets_within_budget(): void
    {
        $total = 0;

        foreach (self::BERKAS_HURUF as $nama => [$ukuran, $sha]) {
            $jalur = public_path('fonts/plus-jakarta-sans/'.$nama);

            $this->assertFileExists($jalur);
            $this->assertSame('wOF2', (string) file_get_contents($jalur, false, null, 0, 4), "{$nama} bukan WOFF2.");
            $this->assertSame($ukuran, filesize($jalur), "{$nama} berubah ukuran.");
            $this->assertSame($sha, hash_file('sha256', $jalur), "{$nama} berbeda dari berkas yang tercatat asal-usulnya.");

            $total += $ukuran;
        }

        $this->assertLessThanOrEqual(self::ANGGARAN_BITA, $total, "Huruf Latin {$total} bita melampaui anggaran 100 KB.");

        $semua = array_map(
            fn ($berkas): string => str_replace('\\', '/', $berkas->getRelativePathname()),
            File::allFiles(public_path('fonts'))
        );
        sort($semua);

        $this->assertSame(
            [
                'plus-jakarta-sans/OFL.txt',
                'plus-jakarta-sans/plus-jakarta-sans-latin-ext-wght-normal.woff2',
                'plus-jakarta-sans/plus-jakarta-sans-latin-wght-normal.woff2',
            ],
            $semua,
            'Hanya huruf yang tercatat yang boleh dihosting.'
        );
    }

    /**
     * Setiap @font-face Plus Jakarta Sans menunjuk berkas yang ada, menawarkan bobot 800
     * untuk judul, dan memakai font-display: swap supaya teks terbaca sebelum huruf tiba.
     * Berkas yang hilang tidak memunculkan galat di mana pun; halaman hanya diam-diam
     * jatuh ke huruf cadangan.
     */
    public function test_every_font_face_points_to_a_file_that_exists_and_offers_weight_800(): void
    {
        preg_match_all("/@font-face\s*\{[^}]*font-family:\s*'Plus Jakarta Sans';[^}]*\}/s", $this->css(), $wajah);

        $this->assertCount(2, $wajah[0], 'Dua subset: Latin dan Latin Extended.');

        foreach ($wajah[0] as $blok) {
            $this->assertMatchesRegularExpression('/font-weight:\s*200 800;/', $blok);
            $this->assertStringContainsString('font-display: swap;', $blok);
            $this->assertMatchesRegularExpression('/unicode-range:\s*U\+/', $blok);
            $this->assertSame(1, preg_match("/url\('([^']+)'\) format\('woff2'\)/", $blok, $url));
            $this->assertFileExists(public_path(ltrim($url[1], '/')), "Berkas {$url[1]} tidak ada.");
        }
    }

    /**
     * Huruf cadangan bermetrik sama.
     *
     * Tanpa penyesuaian metrik, teks berpindah baris ketika Plus Jakarta Sans tiba, dan di
     * sinyal lemah itu terjadi berdetik-detik sesudah pengguna mulai membaca. Bila hurufnya
     * gagal dimuat sama sekali, cadangan inilah yang dibaca sepanjang sesi.
     */
    public function test_the_fallback_metrics_keep_the_layout_from_jumping(): void
    {
        foreach (['PJS Cadangan Roboto' => 'Roboto', 'PJS Cadangan Arial' => 'Arial'] as $cadangan => $lokal) {
            $this->assertSame(1, preg_match("/@font-face\s*\{[^}]*font-family:\s*'{$cadangan}';[^}]*\}/s", $this->css(), $blok), "Wajah {$cadangan} belum ada.");

            foreach (["src: local('{$lokal}');", 'size-adjust:', 'ascent-override:', 'descent-override:', 'line-gap-override: 0%;'] as $sifat) {
                $this->assertStringContainsString($sifat, $blok[0], "{$cadangan} tanpa {$sifat}");
            }
        }

        $this->assertStringContainsString(
            "sans: ['Plus Jakarta Sans', 'PJS Cadangan Roboto', 'PJS Cadangan Arial', ...defaultTheme.fontFamily.sans]",
            $this->tailwind()
        );
    }

    /**
     * Judul memakai keluarga antarmuka pada bobot 800, bukan serif (DK-38).
     */
    public function test_headings_use_the_interface_family_at_weight_800(): void
    {
        $this->assertSame(1, preg_match('/\n    h1\s*\{([^}]*)\}/', $this->css(), $h1), 'Aturan h1 tidak ditemukan.');
        $this->assertStringContainsString('font-weight: 800;', $h1[1]);
        $this->assertStringNotContainsString('font-family', $h1[1], 'Judul mewarisi keluarga antarmuka.');
    }

    /**
     * Skala tipe spec v3 bagian 8.2: nama menyebut peran, nilainya dari prototype.
     */
    public function test_the_type_scale_follows_the_spec(): void
    {
        foreach ([
            "tampilan: ['1.875rem', { lineHeight: '1.15', letterSpacing: '-0.025em', fontWeight: '800' }]",
            "judul: ['1.75rem', { lineHeight: '1.2', letterSpacing: '-0.02em', fontWeight: '800' }]",
            "'judul-kartu': ['1.4375rem', { lineHeight: '1.25', letterSpacing: '-0.015em', fontWeight: '800' }]",
            "bagian: ['1.125rem', { lineHeight: '1.35', fontWeight: '700' }]",
            "subjudul: ['1.0625rem', { lineHeight: '1.4', fontWeight: '700' }]",
            "isi: ['1rem', { lineHeight: '1.5' }]",
            "kecil: ['0.875rem', { lineHeight: '1.45' }]",
            "meta: ['0.8125rem', { lineHeight: '1.4' }]",
            "label: ['0.75rem', { lineHeight: '1.35' }]",
        ] as $baris) {
            $this->assertStringContainsString($baris, $this->tailwind());
        }
    }
}
```

- [ ] **Step 2: Buat test lantai huruf.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan make:test --phpunit TypeFloorTest --no-interaction`
Expected: `INFO  Test [E:\praktik_aplikasi_web	ests\Feature\TypeFloorTest.php] created successfully.`

Ganti seluruh isinya dengan:

```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Lantai ukuran huruf 12 px (spec v3 bagian 8.2, rencana induk W1 butir 2).
 *
 * Teks di bawah 12 px tidak terbaca di layar ponsel di bawah matahari, dan tidak ada
 * keterangan yang cukup tidak penting untuk dicetak lebih kecil dari itu. Label tab bawah
 * dahulu 10,4 px (text-[0.65rem]), satu-satunya pelanggaran yang terukur saat lantai ini
 * ditetapkan; test ini yang menjaga ia tidak kembali di tempat lain.
 */
class TypeFloorTest extends TestCase
{
    use SumberReact;

    private const LANTAI_PX = 12.0;

    public function test_no_arbitrary_text_size_falls_below_the_floor(): void
    {
        $sumber = [];

        foreach ($this->berkasTsx() as $berkas) {
            $sumber[$berkas->getRelativePathname()] = $this->tanpaKomentarJs($berkas->getContents());
        }

        foreach (File::allFiles(resource_path('views')) as $berkas) {
            $sumber[$berkas->getRelativePathname()] = (string) preg_replace('/\{\{--.*?--\}\}/s', ' ', $berkas->getContents());
        }

        $pelanggar = [];

        foreach ($sumber as $nama => $isi) {
            preg_match_all('/\btext-\[(\d*\.?\d+)(px|rem|em)\]/', $isi, $cocok, PREG_SET_ORDER);

            foreach ($cocok as [$kelas, $angka, $satuan]) {
                if ($this->kePiksel((float) $angka, $satuan) < self::LANTAI_PX) {
                    $pelanggar[] = "{$nama} ({$kelas})";
                }
            }
        }

        $this->assertSame([], $pelanggar, 'Teks di bawah 12 px: '.implode(', ', $pelanggar));
    }

    /**
     * Ukuran yang ditulis sebagai gaya sebaris atau sebagai tata letak simbol MapLibre
     * lolos dari pemindai kelas di atas, jadi keduanya disapu terpisah.
     */
    public function test_no_inline_or_map_text_size_falls_below_the_floor(): void
    {
        $pelanggar = [];

        foreach ($this->berkasTsx() as $berkas) {
            preg_match_all("/(?:\bfontSize:\s*|'text-size':\s*)(\d+(?:\.\d+)?)/", $this->tanpaKomentarJs($berkas->getContents()), $angka);

            foreach ($angka[1] as $nilai) {
                if ((float) $nilai < self::LANTAI_PX) {
                    $pelanggar[] = "{$berkas->getRelativePathname()} ({$nilai})";
                }
            }
        }

        $this->assertSame([], $pelanggar, 'Ukuran sebaris di bawah 12 px: '.implode(', ', $pelanggar));
    }

    public function test_the_named_type_scale_stops_at_the_floor(): void
    {
        $this->assertSame(1, preg_match('/fontSize:\s*\{(.*?)\n\s*\},/s', File::get(base_path('tailwind.config.js')), $skala), 'Skala tipe bernama tidak ditemukan.');

        preg_match_all("/\['(\d*\.?\d+)rem'/", $skala[1], $ukuran);

        $this->assertCount(9, $ukuran[1], 'Skala tipe bernama berisi sembilan tingkat.');

        foreach ($ukuran[1] as $rem) {
            $this->assertGreaterThanOrEqual(self::LANTAI_PX, (float) $rem * 16, "Ukuran {$rem}rem di bawah lantai.");
        }
    }

    public function test_the_stylesheet_sets_no_size_below_the_floor(): void
    {
        preg_match_all('/font-size:\s*(\d*\.?\d+)(px|rem|em)/', File::get(resource_path('css/app.css')), $cocok, PREG_SET_ORDER);

        $this->assertNotEmpty($cocok);

        foreach ($cocok as [$deklarasi, $angka, $satuan]) {
            $this->assertGreaterThanOrEqual(self::LANTAI_PX, $this->kePiksel((float) $angka, $satuan), $deklarasi);
        }
    }

    private function kePiksel(float $angka, string $satuan): float
    {
        return $satuan === 'px' ? $angka : $angka * 16;
    }
}
```

- [ ] **Step 3: Perbarui test yang menyandikan keputusan huruf lama.**

Di `tests/Feature/SecurityHeadersTest.php`, ganti metode ini seutuhnya:

```php
    /**
     * Kedua layout memuat Figtree dari Bunny Fonts. CSP yang hanya mengizinkan 'self'
     * memblokirnya tanpa pesan apa pun, dan aplikasi diam-diam jatuh ke font sistem.
     */
    public function test_the_font_host_the_layouts_actually_use_is_allowed(): void
    {
        $csp = $this->get('/login')->assertOk()->headers->get('Content-Security-Policy');

        $this->assertStringContainsString('style-src', $csp);
        $this->assertMatchesRegularExpression('/style-src[^;]*fonts\.bunny\.net/', $csp);
        $this->assertMatchesRegularExpression('/font-src[^;]*fonts\.bunny\.net/', $csp);
    }
```

dengan:

```php
    /**
     * Huruf dimuat dari asal aplikasi sendiri (DK-38).
     *
     * Menggantikan test_the_font_host_the_layouts_actually_use_is_allowed, yang menuntut
     * CSP mengizinkan Bunny Fonts. Sejak gelombang 1 v3 huruf dihosting sendiri, jadi
     * niatnya dibalik: CSP tidak mengizinkan asal huruf luar mana pun, sehingga huruf yang
     * ditambahkan dari layanan lain diblokir alih-alih diam-diam menjadi ketergantungan.
     */
    public function test_fonts_load_only_from_our_own_origin(): void
    {
        $csp = $this->get('/login')->assertOk()->headers->get('Content-Security-Policy');

        $this->assertMatchesRegularExpression("/font-src 'self' data:(;|$)/", $csp);
        $this->assertMatchesRegularExpression("/style-src 'self' 'unsafe-inline'(;|$)/", $csp);
        $this->assertStringNotContainsString('fonts.bunny.net', $csp);
    }
```

Di `tests/Feature/LandingPageTest.php`, ganti blok ini:

```php
    /**
     * CSP menolak gambar dari luar asal sendiri.
     */
    public function test_nothing_is_loaded_from_an_outside_host(): void
    {
        $isi = $this->get('/')->assertOk()->getContent();

        preg_match_all('/(?:src|href)="(https?:\/\/[^"]+)"/', $isi, $cocok);

        $luar = array_filter(
            $cocok[1],
            fn (string $url) => ! str_contains($url, 'localhost')
                && ! str_contains($url, '127.0.0.1')
                && ! str_contains($url, 'fonts.bunny.net')
        );
```

dengan:

```php
    /**
     * CSP menolak aset dari luar asal sendiri.
     *
     * Diperbarui gelombang 1 v3: pengecualian Bunny Fonts dicabut karena huruf kini
     * dihosting sendiri (DK-38), jadi halaman depan tidak memuat apa pun dari luar.
     */
    public function test_nothing_is_loaded_from_an_outside_host(): void
    {
        $isi = $this->get('/')->assertOk()->getContent();

        preg_match_all('/(?:src|href)="(https?:\/\/[^"]+)"/', $isi, $cocok);

        $luar = array_filter(
            $cocok[1],
            fn (string $url) => ! str_contains($url, 'localhost')
                && ! str_contains($url, '127.0.0.1')
        );
```

Di `tests/Feature/SurfaceSystemTest.php`, ganti metode ini seutuhnya:

```php
    public function test_the_page_title_carries_real_weight(): void
    {
        // Komentar dilewati: penjelasan mengapa text-2xl ditinggalkan justru layak
        // disimpan, dan versi pertama test ini menolak berkasnya karena alasan itu.
        $isi = $this->tanpaKomentarJs(File::get(resource_path('js/components/ui/PageHeader.tsx')));

        $this->assertMatchesRegularExpression('/<h1 className="[^"]*\btext-(?:3xl|4xl)\b/', $isi);
        $this->assertStringNotContainsString('text-2xl', $isi);
    }
```

dengan:

```php
    /**
     * Diperbarui gelombang 1 v3: judul halaman kini memakai skala bernama text-judul
     * (28 px, bobot 800, spec v3 bagian 8.2), bukan text-3xl dan text-4xl. Niatnya tetap:
     * judul membawa bobot yang nyata, dan text-2xl tidak kembali.
     */
    public function test_the_page_title_carries_real_weight(): void
    {
        // Komentar dilewati: penjelasan mengapa text-2xl ditinggalkan justru layak
        // disimpan, dan versi pertama test ini menolak berkasnya karena alasan itu.
        $isi = $this->tanpaKomentarJs(File::get(resource_path('js/components/ui/PageHeader.tsx')));

        $this->assertMatchesRegularExpression('/<h1 className="[^"]*\btext-(?:judul|tampilan)\b/', $isi);
        $this->assertStringNotContainsString('text-2xl', $isi);
        $this->assertMatchesRegularExpression("/judul: \['1\.75rem', \{[^}]*fontWeight: '800'/", File::get(base_path('tailwind.config.js')));
    }
```

Di `tests/Feature/ThirdPartyLicenseTest.php`, tambahkan metode berikut sesudah metode terakhir
`test_the_register_lists_every_runtime_dependency_and_embedded_work`, sebelum `}` penutup kelas:

```php

    /**
     * Huruf yang dihosting sendiri membawa teks OFL-nya: SIL OFL 1.1 syarat 2 mewajibkan
     * pemberitahuan hak cipta dan lisensinya ikut di setiap salinan huruf.
     */
    public function test_the_self_hosted_font_carries_its_ofl_text(): void
    {
        $ofl = File::get(public_path('fonts/plus-jakarta-sans/OFL.txt'));
        $daftar = File::get(base_path('docs/lisensi-pihak-ketiga.md'));

        $this->assertStringContainsString('Copyright 2020 The Plus Jakarta Sans Project Authors', $ofl);
        $this->assertStringContainsString('SIL OPEN FONT LICENSE Version 1.1', $ofl);
        $this->assertStringContainsString('public/fonts/plus-jakarta-sans/OFL.txt', $daftar);
        $this->assertStringNotContainsString('Dimuat dari Bunny Fonts', $daftar, 'Daftar lisensi masih menyebut Bunny Fonts sebagai sumber huruf.');
    }
```

Di `tests/Feature/RepoHygieneTest.php`, ganti akhir konstanta `TIDAK_BOLEH_DIABAIKAN`:

```php
        '.claude/skills/antislop-ui/berkas-baru.md',
    ];
```

dengan:

```php
        '.claude/skills/antislop-ui/berkas-baru.md',
        'public/fonts/plus-jakarta-sans/OFL.txt',
        'public/fonts/plus-jakarta-sans/plus-jakarta-sans-latin-wght-normal.woff2',
    ];
```

- [ ] **Step 4: Jalankan dan pastikan gagal.**

Run: `php artisan test --compact --filter='TypographySystemTest|TypeFloorTest|SecurityHeadersTest|LandingPageTest|SurfaceSystemTest|ThirdPartyLicenseTest|RepoHygieneTest'`
Expected: FAIL. Pada simulasi rencana: 48 test, 32 lulus, 15 gagal, 1 error. Di antaranya
`Teks di bawah 12 px: layouts\NavLinks.tsx (text-[0.65rem])`, `Memuat huruf dari layanan luar: components\brand\head.blade.php`,
`Keluarga serif judul sudah dilepas (DK-38).`, `Wajah PJS Cadangan Roboto belum ada.`, `Ukuran baca dasar harus 16 px dengan
tinggi baris 1,5.`, dan berkas `plus-jakarta-sans-latin-wght-normal.woff2` yang belum ada.

- [ ] **Step 5: Unduh berkas huruf dan teks OFL.** Tiga URL ini saja; tidak ada perkakas huruf, tidak ada dependensi baru.
Sumbernya paket npm `@fontsource-variable/plus-jakarta-sans` 5.3.0 (Fontsource membangunnya dari google/fonts, desain
Tokotype, lisensi SIL OFL 1.1).

```bash
mkdir -p public/fonts/plus-jakarta-sans
curl -fsSL -o public/fonts/plus-jakarta-sans/plus-jakarta-sans-latin-wght-normal.woff2 https://cdn.jsdelivr.net/npm/@fontsource-variable/plus-jakarta-sans@5.3.0/files/plus-jakarta-sans-latin-wght-normal.woff2
curl -fsSL -o public/fonts/plus-jakarta-sans/plus-jakarta-sans-latin-ext-wght-normal.woff2 https://cdn.jsdelivr.net/npm/@fontsource-variable/plus-jakarta-sans@5.3.0/files/plus-jakarta-sans-latin-ext-wght-normal.woff2
curl -fsSL -o public/fonts/plus-jakarta-sans/OFL.txt https://cdn.jsdelivr.net/npm/@fontsource-variable/plus-jakarta-sans@5.3.0/LICENSE
```

Run: `for f in public/fonts/plus-jakarta-sans/*; do echo "$(stat -c %s "$f") $(sha256sum "$f" | cut -d' ' -f1) $(head -c 4 "$f" | od -An -c | tr -d ' ') $f"; done`
Expected (tiga baris, urutan boleh berbeda):

```
4534 e07fd1167c2aaaa6fb965dd66e1e73b13c9c95b76b2d648b6e938780f30b37af Copy public/fonts/plus-jakarta-sans/OFL.txt
21728 38e3b8fd8045048eb311d90170a4429ed2c8f405852dc3d91b5af8452758703f wOF2 public/fonts/plus-jakarta-sans/plus-jakarta-sans-latin-ext-wght-normal.woff2
27348 153fc85b70298beeb1d61a5f723331649e7f23bb77302a66e61cb3e2fbdb5e79 wOF2 public/fonts/plus-jakarta-sans/plus-jakarta-sans-latin-wght-normal.woff2
```

Bila satu hash berbeda, berhenti dan laporkan ke koordinator: berkas di CDN berubah, dan catatan asal-usul tidak boleh
ditulis untuk berkas yang tidak diperiksa. Total huruf Latin 49.076 bita, di bawah anggaran 102.400 bita.

- [ ] **Step 6: Sunting stylesheet.** Empat suntingan di `resources/css/app.css`.

(a) Sisipkan blok berikut tepat sesudah baris `@tailwind utilities;` (satu baris kosong di antaranya sudah termasuk di awal blok):

```css

/*
| Huruf antarmuka dihosting sendiri (DK-38, spec v3 bagian 8.2).
|
| Plus Jakarta Sans variabel (bobot 200 sampai 800) dalam dua subset: Latin (27.348 bita)
| dan Latin Extended (21.728 bita). Peramban hanya mengunduh subset yang glyph-nya muncul
| di halaman, jadi teks Indonesia cukup mengunduh subset Latin. Asal berkas, hash, dan
| lisensinya tercatat di docs/lisensi-pihak-ketiga.md; teks OFL ikut di folder berkasnya.
|
| Dua wajah cadangan menyamakan metrik Roboto (Android) dan Arial (Windows, macOS, iOS)
| dengan Plus Jakarta Sans, supaya tata letak tidak melompat ketika huruf tiba atau
| ketika ia gagal dimuat sama sekali. Nilainya dihitung dari lebar rata-rata glyph dan
| metrik vertikal ketiga huruf, bukan ditaksir.
*/
@font-face {
    font-family: 'Plus Jakarta Sans';
    font-style: normal;
    font-weight: 200 800;
    font-display: swap;
    src: url('/fonts/plus-jakarta-sans/plus-jakarta-sans-latin-wght-normal.woff2') format('woff2');
    unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
}

@font-face {
    font-family: 'Plus Jakarta Sans';
    font-style: normal;
    font-weight: 200 800;
    font-display: swap;
    src: url('/fonts/plus-jakarta-sans/plus-jakarta-sans-latin-ext-wght-normal.woff2') format('woff2');
    unicode-range: U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF;
}

@font-face {
    font-family: 'PJS Cadangan Roboto';
    src: local('Roboto');
    size-adjust: 104.6%;
    ascent-override: 99.2%;
    descent-override: 21.2%;
    line-gap-override: 0%;
}

@font-face {
    font-family: 'PJS Cadangan Arial';
    src: local('Arial');
    size-adjust: 104.3%;
    ascent-override: 99.5%;
    descent-override: 21.3%;
    line-gap-override: 0%;
}
```

(b) Di dalam aturan `body`, ganti:

```css
        /*
         * Ukuran baca dasar ditetapkan, bukan diwarisi.
         *
         * Aplikasi ini memakai text-sm untuk hampir segalanya: 363 dari 426 pemakaian
         * ukuran teks adalah text-sm atau text-xs. Ukuran itu pantas untuk tabel admin
         * dan tidak pantas untuk keterangan yang dibaca pendaki di bawah matahari
         * sambil berdiri di jalur.
         *
         * Menaikkan akarnya menggeser seluruh skala Tailwind sekaligus, sehingga
         * text-sm menjadi ukuran yang masih terbaca dan text-base menjadi nyaman,
         * tanpa menyentuh satu pun kelas di ratusan berkas view.
         */
        font-size: 16.5px;
        line-height: 1.6;
```

dengan:

```css
        /*
         * Ukuran baca dasar ditetapkan, bukan diwarisi: 16 px dengan tinggi baris 1,5,
         * sama dengan text-isi (spec v3 bagian 8.2). Teks yang tidak menyebut ukurannya
         * mendapat ukuran isi, bukan ukuran bawaan peramban.
         *
         * Sebelumnya 16,5 px dengan klaim bahwa nilai itu menggeser seluruh skala
         * Tailwind. Klaim itu keliru: rem dihitung dari html, bukan dari body, jadi yang
         * berubah hanya teks tanpa kelas ukuran. Skala kini bernama, dari tampilan sampai
         * label, dan lantainya 12 px.
         */
        font-size: 1rem;
        line-height: 1.5;
```

(c) Ganti komentar dan awal aturan `h1`:

```css
    /*
     * Judul halaman memakai serif.
     *
     * Serif memberi bobot editorial yang tidak dapat dicapai ketebalan huruf sans, dan
     * membedakan judul dari antarmuka tanpa membesarkannya sampai berteriak. Dipasang
     * pada h1 di lapisan base supaya setiap halaman mendapatnya, termasuk yang ditulis
     * besok.
     */
    h1 {
        font-family: theme('fontFamily.serif');
        font-weight: 500;
```

dengan:

```css
    /*
     * Judul halaman memakai huruf antarmuka yang sama pada bobot 800 (spec v3 bagian
     * 8.2, DK-38).
     *
     * Serif judul dilepas di gelombang 1 v3: prototype pemilik membawa hierarki lewat
     * bobot dan ukuran satu keluarga, dan satu keluarga berarti satu unduhan huruf.
     * Dipasang pada h1 di lapisan base supaya setiap halaman mendapatnya, termasuk yang
     * ditulis besok.
     */
    h1 {
        font-weight: 800;
```

(d) Ganti komentar dan selektor angka berlebar tetap:

```css
    /*
     * Pengukuran memakai huruf berlebar tetap dan keluarga mono.
     *
     * Jarak, elevation gain, durasi, dan koordinat adalah data, bukan prosa. Huruf
     * proporsional membuat kolom angka bergoyang saat nilainya berganti, dan mata
     * membaca deret angka jauh lebih cepat ketika lebarnya tetap.
     */
    table,
    dd,
    time,
    [data-angka] {
```

dengan:

```css
    /*
     * Pengukuran memakai angka berlebar tetap.
     *
     * Jarak, elevation gain, durasi, dan koordinat adalah data, bukan prosa. Angka
     * proporsional membuat kolom bergoyang saat nilainya berganti, dan mata membaca
     * deret angka jauh lebih cepat ketika lebarnya tetap. output ikut karena nilai
     * stepper berganti di tempat.
     */
    table,
    dd,
    time,
    output,
    [data-angka] {
```

- [ ] **Step 7: Ganti keluarga huruf dan tambahkan skala tipe.** Di `tailwind.config.js`, ganti blok ini seutuhnya:

```js
            /*
             * Tiga suara, bukan satu.
             *
             * Diukur sebelum diubah: 363 dari 426 pemakaian ukuran teks adalah text-sm
             * atau text-xs, dan fontnya Figtree bawaan scaffolding. Satu huruf generik
             * pada satu ukuran kecil membuat setiap layar terbaca seperti sel tabel,
             * berapa pun kecerdasan yang ada di belakangnya.
             *
             * Plus Jakarta Sans dirancang Tokotype untuk identitas kota Jakarta.
             * Dipilih bukan karena tampak mahal, melainkan karena ia huruf Indonesia
             * untuk produk tentang gunung-gunung Indonesia; alasan itu bertahan ketika
             * seleranya berubah, sedangkan "sedang populer" tidak.
             *
             * Newsreader dipakai untuk judul halaman. Serif memberi bobot editorial yang
             * tidak dapat dicapai ketebalan huruf sans, dan membedakan judul dari
             * antarmuka tanpa membesarkannya sampai berteriak.
             *
             * Huruf mono memakai tumpukan sistem, tanpa unduhan.
             *
             * Komentar sebelumnya di sini menyebut JetBrains Mono "khusus pengukuran:
             * jarak, elevation gain, durasi, dan koordinat". Ketika akhirnya diukur,
             * tidak satu pun pengukuran memakainya: semuanya memakai tabular-nums pada
             * huruf sans, dan font-mono muncul tepat dua kali, keduanya pada kunci tugas
             * penjadwal di satu halaman admin.
             *
             * Jadi seluruh pengguna mengunduh dua bobot huruf untuk dua baris yang tidak
             * pernah mereka lihat. Pertanyaan R-06 "mengapa huruf ini" ternyata punya
             * jawaban yang lebih sederhana daripada mencari penggantinya: tidak perlu
             * huruf mono sama sekali. Kunci penjadwal seperti weather:refresh justru
             * persis yang pantas memakai huruf mono bawaan sistem.
             */
            fontFamily: {
                sans: ['Plus Jakarta Sans', ...defaultTheme.fontFamily.sans],
                serif: ['Newsreader', ...defaultTheme.fontFamily.serif],
                mono: defaultTheme.fontFamily.mono,
            },
```

dengan:

```js
            /*
             * Satu keluarga huruf, dihosting sendiri (spec v3 bagian 8.2, DK-38).
             *
             * Diukur sebelum diubah: 363 dari 426 pemakaian ukuran teks adalah text-sm
             * atau text-xs, dan fontnya Figtree bawaan scaffolding. Satu huruf generik
             * pada satu ukuran kecil membuat setiap layar terbaca seperti sel tabel,
             * berapa pun kecerdasan yang ada di belakangnya.
             *
             * Plus Jakarta Sans dirancang Tokotype untuk identitas kota Jakarta.
             * Dipilih bukan karena tampak mahal, melainkan karena ia huruf Indonesia
             * untuk produk tentang gunung-gunung Indonesia; alasan itu bertahan ketika
             * seleranya berubah, sedangkan "sedang populer" tidak. Sejak gelombang 1 v3
             * ia juga membawa judul pada bobot 800: serif judul (Newsreader) dan Bunny
             * Fonts dilepas, sehingga seluruh huruf datang dari asal aplikasi sendiri.
             *
             * Dua nama berikutnya adalah Roboto dan Arial yang metriknya disamakan di
             * app.css, supaya tata letak tidak melompat ketika huruf tiba.
             *
             * Huruf mono memakai tumpukan sistem, tanpa unduhan: font-mono hanya dipakai
             * halaman Kesehatan admin untuk nama migrasi dan kunci tugas penjadwal.
             */
            fontFamily: {
                sans: ['Plus Jakarta Sans', 'PJS Cadangan Roboto', 'PJS Cadangan Arial', ...defaultTheme.fontFamily.sans],
                mono: defaultTheme.fontFamily.mono,
            },

            /*
             * Skala tipe bernama (spec v3 bagian 8.2). Nama menyebut peran, bukan ukuran:
             * text-judul untuk judul halaman, text-isi untuk badan teks. Lantainya 12 px
             * (text-label); tidak ada teks informatif di bawahnya (TypeFloorTest).
             */
            fontSize: {
                tampilan: ['1.875rem', { lineHeight: '1.15', letterSpacing: '-0.025em', fontWeight: '800' }],
                judul: ['1.75rem', { lineHeight: '1.2', letterSpacing: '-0.02em', fontWeight: '800' }],
                'judul-kartu': ['1.4375rem', { lineHeight: '1.25', letterSpacing: '-0.015em', fontWeight: '800' }],
                bagian: ['1.125rem', { lineHeight: '1.35', fontWeight: '700' }],
                subjudul: ['1.0625rem', { lineHeight: '1.4', fontWeight: '700' }],
                isi: ['1rem', { lineHeight: '1.5' }],
                kecil: ['0.875rem', { lineHeight: '1.45' }],
                meta: ['0.8125rem', { lineHeight: '1.4' }],
                label: ['0.75rem', { lineHeight: '1.35' }],
            },
```

- [ ] **Step 8: Muat huruf dari asal sendiri.** Di `resources/views/components/brand/head.blade.php`, ganti:

```blade
@if ($fonts)
    <link rel="preconnect" href="https://fonts.bunny.net">
    {{-- Tiga suara: antarmuka, judul, dan pengukuran. Alasan pemilihannya tertulis
         di tailwind.config.js. display=swap supaya teks terbaca sejak sebelum
         hurufnya tiba, karena jaringan lambat adalah keadaan normal di sini. --}}
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700|newsreader:400,500,600&display=swap" rel="stylesheet" />
@endif
```

dengan:

```blade
@if ($fonts)
    {{-- Satu keluarga huruf dari asal aplikasi sendiri (DK-38). Subset Latin
         dipramuat karena hampir setiap halaman memakainya; subset Latin Extended
         hanya diunduh peramban bila halaman memuat glyph-nya. @font-face dan
         font-display: swap ada di resources/css/app.css, alasan pemilihannya di
         tailwind.config.js. --}}
    <link rel="preload" href="/fonts/plus-jakarta-sans/plus-jakarta-sans-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
@endif
```

Di `app/Http/Middleware/SecurityHeaders.php`, ganti:

```php
            // Kedua layout memuat Figtree dari Bunny Fonts. Tanpa asal ini di style-src
            // dan font-src, CSP memblokirnya diam-diam dan seluruh aplikasi jatuh ke
            // font sistem tanpa satu pun pesan kesalahan yang terlihat.
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net",
            "font-src 'self' data: https://fonts.bunny.net",
```

dengan:

```php
            // Huruf dihosting sendiri sejak gelombang 1 v3 (DK-38): style-src dan
            // font-src tidak lagi mengizinkan asal luar, jadi huruf yang ditambahkan
            // dari layanan lain diblokir CSP alih-alih diam-diam menjadi ketergantungan.
            "style-src 'self' 'unsafe-inline'",
            "font-src 'self' data:",
```

- [ ] **Step 9: Naikkan label tab bawah ke lantai.** Di `resources/js/layouts/NavLinks.tsx:97`, pada string kelas `TabBawah`,
ganti `text-[0.65rem] leading-tight tracking-tight` dengan `text-xs leading-tight`. Tidak ada perubahan lain; Tugas 3 yang
mengganti ikonnya.

- [ ] **Step 10: Judul halaman memakai skala bernama.** Ganti seluruh isi `resources/js/components/ui/PageHeader.tsx` dengan:

```tsx
import type { ReactNode } from 'react';

interface PageHeaderProps {
    title: string;
    description?: string;
    children?: ReactNode;
}

/**
 * Judul halaman: text-judul (28 px, bobot 800, satu keluarga dengan antarmuka) lewat
 * skala tipe bernama; deskripsi dibatasi lebar baca.
 */
export function PageHeader({ title, description, children }: PageHeaderProps) {
    return (
        <div className="mb-6">
            <h1 className="text-balance text-judul text-primary">{title}</h1>
            {description ? <p className="mt-2 max-w-prose text-isi text-secondary">{description}</p> : null}
            {children}
        </div>
    );
}
```

- [ ] **Step 11: Perbarui daftar lisensi.** Di `docs/lisensi-pihak-ketiga.md`, ganti bagian ini seutuhnya:

```markdown
## Huruf

| Karya | Dipakai di | Lisensi | Catatan |
|---|---|---|---|
| Plus Jakarta Sans | Dimuat dari Bunny Fonts di `resources/views/components/brand/head.blade.php`; dihosting sendiri mulai W1 (DK-38) | SIL Open Font License 1.1 | Teks OFL wajib ikut bersama berkas huruf saat dihosting sendiri |
| Newsreader | Dimuat dari Bunny Fonts; dilepas di W1 (DK-38) | SIL Open Font License 1.1 | |
```

dengan:

```markdown
## Huruf

| Karya | Dipakai di | Lisensi | Catatan |
|---|---|---|---|
| Plus Jakarta Sans, variabel (bobot 200 sampai 800), subset Latin dan Latin Extended | Dihosting sendiri di `public/fonts/plus-jakarta-sans/`, dimuat `@font-face` di `resources/css/app.css`, subset Latin dipramuat `resources/views/components/brand/head.blade.php` | SIL Open Font License 1.1, Copyright 2020 The Plus Jakarta Sans Project Authors | Teks lisensi `public/fonts/plus-jakarta-sans/OFL.txt`. Asal: paket npm `@fontsource-variable/plus-jakarta-sans` 5.3.0 lewat cdn.jsdelivr.net, dibangun Fontsource dari google/fonts (desain Tokotype), diunduh 26 September 2026; ukuran dan SHA-256 kedua berkas dipaku `TypographySystemTest`. Bukan rilis GitHub tokotype/PlusJakartaSans 2.7.1 karena rilis itu hanya berisi TTF (variabel tegak 182.180 bita) tanpa woff2, dan rencana W1 melarang perkakas huruf. |

Newsreader dan Bunny Fonts tidak dipakai lagi sejak gelombang 1 v3 (DK-38): judul memakai Plus Jakarta Sans bobot 800, dan
tidak ada huruf yang dimuat dari layanan luar.
```

- [ ] **Step 12: Jalankan test tugas ini sampai lulus.**

Run: `php artisan test --compact --filter='TypographySystemTest|TypeFloorTest|SecurityHeadersTest|LandingPageTest|SurfaceSystemTest|ThirdPartyLicenseTest|RepoHygieneTest|ReadingMeasureTest|BrowserSurfacesTest|IconSystemTest|TapTargetTest|NavigationConsistencyTest'`
Expected: PASS, 76 test.

- [ ] **Step 13: Build dan periksa huruf di CSS hasil build.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: npm run build`
Expected: `✓ built`, dengan dua peringatan yang memang diharapkan karena berkas huruf disajikan dari `public/` saat runtime:
`/fonts/plus-jakarta-sans/plus-jakarta-sans-latin-wght-normal.woff2 referenced in ... didn't resolve at build time, it will remain unchanged to be resolved at runtime`
dan baris yang sama untuk subset `latin-ext`. Peringatan ukuran potongan maplibre-gl sudah ada sebelum W1.

Run: `grep -o "@font-face{font-family:Plus Jakarta Sans[^}]*}" public/build/assets/app-*.css | wc -l`
Expected: `2`

- [ ] **Step 14: Gerbang tugas.**

Run: `vendor/bin/pint --dirty --format agent`
Expected: `{"tool":"pint","result":"passed"}` atau berkas dirapikan tanpa perubahan makna.

Run: `npx tsc --noEmit`
Expected: keluar tanpa keluaran galat.

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal. Pada simulasi rencana: 1.436 test, 1.435 lulus, 1 dilewati.

- [ ] **Step 15: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 3: Registri ikon berbobot per ukuran dan komentar lisensi di bundel

Induk W1 butir 3 (DK-39). Satu registri path Phosphor per bobot untuk React, komponen Blade yang memilih bobot dengan
aturan yang sama, lantai 16 px, dan pemberitahuan lisensi yang ikut ke bundel produksi.

**Files:**
- Create: `resources/js/components/ui/ikon/phosphor.ts`
- Modify (tulis ulang): `resources/js/components/ui/Icon.tsx`, `resources/views/components/ui/icon.blade.php`
- Modify: `resources/js/components/ui/CheckpointJourney.tsx:31-32`, `resources/views/components/ui/checkpoint-journey.blade.php:90,92`, `resources/js/layouts/NavLinks.tsx`, `resources/js/components/trips/TataLetakPendakian.tsx`, `vite.config.js`, `docs/lisensi-pihak-ketiga.md` (baris Phosphor)
- Test: `tests/Feature/IconSystemTest.php`, `tests/Feature/ThirdPartyLicenseTest.php`

**Interfaces:**
- Consumes: `kelas()` dari `resources/js/lib/kelas.ts`; `LICENSE-phosphor.txt` yang sudah ada.
- Produces, dipakai Tugas 4 sampai 8 dan W2 sampai W8:
  - `import { Icon, bobotUntuk, type NamaIkon, type UkuranIkon } from '@/components/ui/Icon'`
  - `<Icon name: NamaIkon, size?: 16 | 20 | 22 | 24 | 28 | 32 (bawaan 16), isi?: boolean, label?: string, className?: string />`. Ukuran hanya lewat `size`, tidak lewat kelas `h-*`, `w-*`, atau `size-*`.
  - `bobotUntuk(ukuran): 'bold' | 'regular'`, Bold di bawah 20 px.
  - `NamaPhosphor` (14 nama: arrow-down, arrow-left, arrow-up, backpack, bell, chart-line-up, check, house, list, map-trifold, minus, person-simple-hike, plus, x) dan `VarianPhosphor { regular; bold; fill? }` dari `@/components/ui/ikon/phosphor`.
  - Blade: `<x-ui.icon name="arrow-up|arrow-down" :size="16|20|24" label="..." />`; ukuran lain melempar `InvalidArgumentException`.

- [ ] **Step 1: Tulis test ikon yang gagal.** Di `tests/Feature/IconSystemTest.php`, pasang trait pembaca sumber React. Ganti:

```php
class IconSystemTest extends TestCase
{
    public function test_an_icon_renders_as_inline_svg_without_a_javascript_library(): void
```

dengan:

```php
class IconSystemTest extends TestCase
{
    use SumberReact;

    public function test_an_icon_renders_as_inline_svg_without_a_javascript_library(): void
```

Lalu ganti metode terakhir kelas itu seutuhnya:

```php
    /**
     * Ikon React mengikuti aturan yang sama dengan ikon Blade di halaman publik: yang
     * menyertai teks disembunyikan dari pembaca layar, yang berdiri sendiri membawa
     * namanya, dan nama yang tidak dikenal tidak menggambar apa pun. Navigasi bawah
     * ponsel memakainya di setiap halaman, selalu bersama labelnya.
     */
    public function test_the_react_icon_follows_the_same_rules(): void
    {
        $ikon = File::get(resource_path('js/components/ui/Icon.tsx'));

        $this->assertStringContainsString("role={label ? 'img' : undefined}", $ikon);
        $this->assertStringContainsString('aria-hidden={label ? undefined : true}', $ikon);
        $this->assertStringContainsString('{label ? <title>{label}</title> : null}', $ikon);
        $this->assertMatchesRegularExpression('/if \(!d\) \{\s*return null;\s*\}/', $ikon);

        $tab = File::get(resource_path('js/layouts/NavLinks.tsx'));
        $this->assertMatchesRegularExpression('/<Icon name=\{ikon\} className="[^"]*" \/>\s*<span[^>]*>\{children\}<\/span>/', $tab, 'Ikon navigasi bawah harus berdampingan dengan labelnya.');
    }
```

dengan metode yang diperbarui ditambah empat test baru:

```php
    /**
     * Ikon React mengikuti aturan yang sama dengan ikon Blade di halaman publik: yang
     * menyertai teks disembunyikan dari pembaca layar, yang berdiri sendiri membawa
     * namanya, dan nama yang tidak dikenal tidak menggambar apa pun. Navigasi bawah
     * ponsel memakainya di setiap halaman, selalu bersama labelnya.
     *
     * Diperbarui gelombang 1 v3: ikon tab kini ditulis dengan ukuran bernama 22 px dan
     * varian isi untuk tab aktif (spec v3 bagian 8.3), bukan kelas h-6 w-6.
     */
    public function test_the_react_icon_follows_the_same_rules(): void
    {
        $ikon = File::get(resource_path('js/components/ui/Icon.tsx'));

        $this->assertStringContainsString("role={label ? 'img' : undefined}", $ikon);
        $this->assertStringContainsString('aria-hidden={label ? undefined : true}', $ikon);
        $this->assertStringContainsString('{label ? <title>{label}</title> : null}', $ikon);
        $this->assertMatchesRegularExpression('/if \(!d\) \{\s*return null;\s*\}/', $ikon);

        $tab = File::get(resource_path('js/layouts/NavLinks.tsx'));
        $this->assertMatchesRegularExpression('/<Icon name=\{ikon\} size=\{22\} isi=\{aktif\} \/>\s*<span[^>]*>\{children\}<\/span>/', $tab, 'Ikon navigasi bawah harus berdampingan dengan labelnya.');
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
```

- [ ] **Step 2: Tulis test lisensi yang gagal.** Di `tests/Feature/ThirdPartyLicenseTest.php`, ganti baris:

```php
        foreach (['components/ui/Icon.tsx', 'components/ui/icon.blade.php'] as $wajib) {
```

dengan:

```php
        foreach (['components/ui/Icon.tsx', 'components/ui/icon.blade.php', 'components/ui/ikon/phosphor.ts'] as $wajib) {
```

dan tambahkan metode berikut sesudah `test_the_self_hosted_font_carries_its_ofl_text` (Tugas 2), sebelum `}` penutup kelas:

```php

    /**
     * Pemberitahuan lisensi ikut ke bundel produksi (HANDOVER bagian 0 butir 17). Minifier
     * Vite 8 membuang komentar lisensi berawalan /*! dan @license kecuali diminta
     * mempertahankannya; sebelum gelombang 1 v3 bundel tidak memuat pemberitahuan Phosphor
     * (MIT) maupun MapLibre (BSD-3-Clause) sama sekali.
     */
    public function test_the_production_build_keeps_the_licence_notices(): void
    {
        $this->assertMatchesRegularExpression('/comments:\s*\{\s*legal:\s*true,/', File::get(base_path('vite.config.js')));

        $bundel = glob(public_path('build/assets/*.js')) ?: [];

        $this->assertNotEmpty($bundel, 'Bundel belum dibangun: jalankan build sebelum suite ini.');

        $isi = implode("\n", array_map(fn (string $jalur): string => (string) file_get_contents($jalur), $bundel));

        $this->assertStringContainsString('Copyright (c) 2023 Phosphor Icons', $isi, 'Pemberitahuan MIT Phosphor hilang dari bundel produksi.');
        $this->assertStringContainsString('@license 3-Clause BSD', $isi, 'Pemberitahuan lisensi MapLibre hilang dari bundel produksi.');
    }
```

- [ ] **Step 3: Jalankan dan pastikan gagal.**

Run: `php artisan test --compact --filter='IconSystemTest|ThirdPartyLicenseTest'`
Expected: FAIL. Pada simulasi rencana: 16 test, 9 lulus, 6 gagal, 1 error (registri belum ada). Di antaranya
`16 px memakai bobot Bold.`, `Ikon Blade 12 px seharusnya ditolak.`, `Panah hiasan di label tombol:
components\trips\TataLetakPendakian.tsx:17`, dan `Pemindai tidak mengenali components/ui/ikon/phosphor.ts`.

- [ ] **Step 4: Buat registri path.** Buat `resources/js/components/ui/ikon/phosphor.ts`. Path di bawah disalin dari
`@phosphor-icons/core` 2.1.1; Step 5 memeriksanya ulang terhadap sumbernya.

```ts
/*! Ikon Phosphor (phosphor-icons/core 2.1.1), MIT License, Copyright (c) 2023 Phosphor Icons. Teks lisensi: resources/js/components/ui/LICENSE-phosphor.txt */
/**
 * Registri path ikon Phosphor per bobot, ditanam sebagai data (bukan paket npm, DK-24).
 *
 * Bobot dipilih menurut ukuran render (spec v3 bagian 8.3, DK-39): Bold di bawah 20 px
 * supaya garis tidak menipis menjadi rambut, Regular mulai 20 px, dan Fill hanya untuk tab
 * aktif. Hanya ikon yang dipakai yang ditanam; menagih bita untuk ikon yang tidak pernah
 * dilihat pengguna adalah biaya tanpa imbalan.
 *
 * Menambah ikon: salin atribut d dari assets/regular/<nama>.svg dan assets/bold/<nama>-bold.svg
 * (serta assets/fill/<nama>-fill.svg bila ikon itu menjadi tab) di paket @phosphor-icons/core
 * versi yang sama, lewat https://cdn.jsdelivr.net/npm/@phosphor-icons/core@2.1.1/assets/,
 * tambahkan namanya ke NamaPhosphor, lalu jalankan IconSystemTest.
 */
export interface VarianPhosphor {
    regular: string;
    bold: string;
    fill?: string;
}

export type NamaPhosphor =
    | 'arrow-down'
    | 'arrow-left'
    | 'arrow-up'
    | 'backpack'
    | 'bell'
    | 'chart-line-up'
    | 'check'
    | 'house'
    | 'list'
    | 'map-trifold'
    | 'minus'
    | 'person-simple-hike'
    | 'plus'
    | 'x';

export const phosphor: Record<NamaPhosphor, VarianPhosphor> = {
    'arrow-down': {
        regular: 'M205.66,149.66l-72,72a8,8,0,0,1-11.32,0l-72-72a8,8,0,0,1,11.32-11.32L120,196.69V40a8,8,0,0,1,16,0V196.69l58.34-58.35a8,8,0,0,1,11.32,11.32Z',
        bold: 'M208.49,152.49l-72,72a12,12,0,0,1-17,0l-72-72a12,12,0,0,1,17-17L116,187V40a12,12,0,0,1,24,0V187l51.51-51.52a12,12,0,0,1,17,17Z',
    },
    'arrow-left': {
        regular: 'M224,128a8,8,0,0,1-8,8H59.31l58.35,58.34a8,8,0,0,1-11.32,11.32l-72-72a8,8,0,0,1,0-11.32l72-72a8,8,0,0,1,11.32,11.32L59.31,120H216A8,8,0,0,1,224,128Z',
        bold: 'M228,128a12,12,0,0,1-12,12H69l51.52,51.51a12,12,0,0,1-17,17l-72-72a12,12,0,0,1,0-17l72-72a12,12,0,0,1,17,17L69,116H216A12,12,0,0,1,228,128Z',
    },
    'arrow-up': {
        regular: 'M205.66,117.66a8,8,0,0,1-11.32,0L136,59.31V216a8,8,0,0,1-16,0V59.31L61.66,117.66a8,8,0,0,1-11.32-11.32l72-72a8,8,0,0,1,11.32,0l72,72A8,8,0,0,1,205.66,117.66Z',
        bold: 'M208.49,120.49a12,12,0,0,1-17,0L140,69V216a12,12,0,0,1-24,0V69L64.49,120.49a12,12,0,0,1-17-17l72-72a12,12,0,0,1,17,0l72,72A12,12,0,0,1,208.49,120.49Z',
    },
    'backpack': {
        regular: 'M168,40.58V32A24,24,0,0,0,144,8H112A24,24,0,0,0,88,32v8.58A56.09,56.09,0,0,0,40,96V216a16,16,0,0,0,16,16H200a16,16,0,0,0,16-16V96A56.09,56.09,0,0,0,168,40.58ZM112,24h32a8,8,0,0,1,8,8v8H104V32A8,8,0,0,1,112,24Zm56,136H88v-8a8,8,0,0,1,8-8h64a8,8,0,0,1,8,8ZM88,176h48v8a8,8,0,0,0,16,0v-8h16v40H88Zm112,40H184V152a24,24,0,0,0-24-24H96a24,24,0,0,0-24,24v64H56V96A40,40,0,0,1,96,56h64a40,40,0,0,1,40,40V216ZM152,88a8,8,0,0,1-8,8H112a8,8,0,0,1,0-16h32A8,8,0,0,1,152,88Z',
        bold: 'M172,48.55V36A28,28,0,0,0,144,8H112A28,28,0,0,0,84,36V48.55A60.08,60.08,0,0,0,32,108V220a20,20,0,0,0,20,20H204a20,20,0,0,0,20-20V108A60.08,60.08,0,0,0,172,48.55ZM112,32h32a4,4,0,0,1,4,4V48H108V36A4,4,0,0,1,112,32Zm48,128H96v-8a4,4,0,0,1,4-4h56a4,4,0,0,1,4,4ZM96,184h64v32H96Zm104,32H184V152a28,28,0,0,0-28-28H100a28,28,0,0,0-28,28v64H56V108A36,36,0,0,1,92,72h72a36,36,0,0,1,36,36ZM160,100a12,12,0,0,1-12,12H108a12,12,0,0,1,0-24h40A12,12,0,0,1,160,100Z',
        fill: 'M168,40.58V32A24,24,0,0,0,144,8H112A24,24,0,0,0,88,32v8.58A56.09,56.09,0,0,0,40,96V216a16,16,0,0,0,16,16H200a16,16,0,0,0,16-16V96A56.09,56.09,0,0,0,168,40.58ZM104,32a8,8,0,0,1,8-8h32a8,8,0,0,1,8,8v8H104Zm8,40h32a8,8,0,0,1,0,16H112a8,8,0,0,1,0-16Zm64,144H80V176h56v8a8,8,0,0,0,16,0v-8h24Zm0-56H80v-8a16,16,0,0,1,16-16h64a16,16,0,0,1,16,16Z',
    },
    'bell': {
        regular: 'M221.8,175.94C216.25,166.38,208,139.33,208,104a80,80,0,1,0-160,0c0,35.34-8.26,62.38-13.81,71.94A16,16,0,0,0,48,200H88.81a40,40,0,0,0,78.38,0H208a16,16,0,0,0,13.8-24.06ZM128,216a24,24,0,0,1-22.62-16h45.24A24,24,0,0,1,128,216ZM48,184c7.7-13.24,16-43.92,16-80a64,64,0,1,1,128,0c0,36.05,8.28,66.73,16,80Z',
        bold: 'M225.29,165.93C216.61,151,212,129.57,212,104a84,84,0,0,0-168,0c0,25.58-4.59,47-13.27,61.93A20.08,20.08,0,0,0,30.66,186,19.77,19.77,0,0,0,48,196H84.18a44,44,0,0,0,87.64,0H208a19.77,19.77,0,0,0,17.31-10A20.08,20.08,0,0,0,225.29,165.93ZM128,212a20,20,0,0,1-19.6-16h39.2A20,20,0,0,1,128,212ZM54.66,172C63.51,154,68,131.14,68,104a60,60,0,0,1,120,0c0,27.13,4.48,50,13.33,68Z',
    },
    'chart-line-up': {
        regular: 'M232,208a8,8,0,0,1-8,8H32a8,8,0,0,1-8-8V48a8,8,0,0,1,16,0V156.69l50.34-50.35a8,8,0,0,1,11.32,0L128,132.69,180.69,80H160a8,8,0,0,1,0-16h40a8,8,0,0,1,8,8v40a8,8,0,0,1-16,0V91.31l-58.34,58.35a8,8,0,0,1-11.32,0L96,123.31l-56,56V200H224A8,8,0,0,1,232,208Z',
        bold: 'M236,208a12,12,0,0,1-12,12H32a12,12,0,0,1-12-12V48a12,12,0,0,1,24,0v99l43.51-43.52a12,12,0,0,1,17,0L128,127l43-43H160a12,12,0,0,1,0-24h40a12,12,0,0,1,12,12v40a12,12,0,0,1-24,0V101l-51.51,51.52a12,12,0,0,1-17,0L96,129,44,181v15H224A12,12,0,0,1,236,208Z',
        fill: 'M216,40H40A16,16,0,0,0,24,56V200a16,16,0,0,0,16,16H216a16,16,0,0,0,16-16V56A16,16,0,0,0,216,40ZM200,192H56a8,8,0,0,1-8-8V72a8,8,0,0,1,16,0v76.69l34.34-34.35a8,8,0,0,1,11.32,0L128,132.69,172.69,88H144a8,8,0,0,1,0-16h48a8,8,0,0,1,8,8v48a8,8,0,0,1-16,0V99.31l-50.34,50.35a8,8,0,0,1-11.32,0L104,131.31l-40,40V176H200a8,8,0,0,1,0,16Z',
    },
    'check': {
        regular: 'M229.66,77.66l-128,128a8,8,0,0,1-11.32,0l-56-56a8,8,0,0,1,11.32-11.32L96,188.69,218.34,66.34a8,8,0,0,1,11.32,11.32Z',
        bold: 'M232.49,80.49l-128,128a12,12,0,0,1-17,0l-56-56a12,12,0,1,1,17-17L96,183,215.51,63.51a12,12,0,0,1,17,17Z',
    },
    'house': {
        regular: 'M219.31,108.68l-80-80a16,16,0,0,0-22.62,0l-80,80A15.87,15.87,0,0,0,32,120v96a8,8,0,0,0,8,8h64a8,8,0,0,0,8-8V160h32v56a8,8,0,0,0,8,8h64a8,8,0,0,0,8-8V120A15.87,15.87,0,0,0,219.31,108.68ZM208,208H160V152a8,8,0,0,0-8-8H104a8,8,0,0,0-8,8v56H48V120l80-80,80,80Z',
        bold: 'M222.14,105.85l-80-80a20,20,0,0,0-28.28,0l-80,80A19.86,19.86,0,0,0,28,120v96a12,12,0,0,0,12,12h64a12,12,0,0,0,12-12V164h24v52a12,12,0,0,0,12,12h64a12,12,0,0,0,12-12V120A19.86,19.86,0,0,0,222.14,105.85ZM204,204H164V152a12,12,0,0,0-12-12H104a12,12,0,0,0-12,12v52H52V121.65l76-76,76,76Z',
        fill: 'M224,120v96a8,8,0,0,1-8,8H160a8,8,0,0,1-8-8V164a4,4,0,0,0-4-4H108a4,4,0,0,0-4,4v52a8,8,0,0,1-8,8H40a8,8,0,0,1-8-8V120a16,16,0,0,1,4.69-11.31l80-80a16,16,0,0,1,22.62,0l80,80A16,16,0,0,1,224,120Z',
    },
    'list': {
        regular: 'M224,128a8,8,0,0,1-8,8H40a8,8,0,0,1,0-16H216A8,8,0,0,1,224,128ZM40,72H216a8,8,0,0,0,0-16H40a8,8,0,0,0,0,16ZM216,184H40a8,8,0,0,0,0,16H216a8,8,0,0,0,0-16Z',
        bold: 'M228,128a12,12,0,0,1-12,12H40a12,12,0,0,1,0-24H216A12,12,0,0,1,228,128ZM40,76H216a12,12,0,0,0,0-24H40a12,12,0,0,0,0,24ZM216,180H40a12,12,0,0,0,0,24H216a12,12,0,0,0,0-24Z',
    },
    'map-trifold': {
        regular: 'M228.92,49.69a8,8,0,0,0-6.86-1.45L160.93,63.52,99.58,32.84a8,8,0,0,0-5.52-.6l-64,16A8,8,0,0,0,24,56V200a8,8,0,0,0,9.94,7.76l61.13-15.28,61.35,30.68A8.15,8.15,0,0,0,160,224a8,8,0,0,0,1.94-.24l64-16A8,8,0,0,0,232,200V56A8,8,0,0,0,228.92,49.69ZM104,52.94l48,24V203.06l-48-24ZM40,62.25l48-12v127.5l-48,12Zm176,131.5-48,12V78.25l48-12Z',
        bold: 'M231.38,46.54a12,12,0,0,0-10.29-2.18L161.4,59.28l-60-30a12,12,0,0,0-8.28-.91l-64,16A12,12,0,0,0,20,56V200a12,12,0,0,0,14.91,11.64L94.6,196.72l60,30a12,12,0,0,0,8.28.91l64-16A12,12,0,0,0,236,200V56A12,12,0,0,0,231.38,46.54ZM108,59.42l40,20V196.58l-40-20Zm-64,6,40-10V174.63l-40,10ZM212,190.63l-40,10V81.37l40-10Z',
        fill: 'M228.92,49.69a8,8,0,0,0-6.86-1.45L160.93,63.52,99.58,32.84a8,8,0,0,0-5.52-.6l-64,16A8,8,0,0,0,24,56V200a8,8,0,0,0,9.94,7.76l61.13-15.28,61.35,30.68A8.15,8.15,0,0,0,160,224a8,8,0,0,0,1.94-.24l64-16A8,8,0,0,0,232,200V56A8,8,0,0,0,228.92,49.69ZM96,176a8,8,0,0,0-1.94.24L40,189.75V62.25L95.07,48.48l.93.46Zm120,17.75-55.07,13.77-.93-.46V80a8,8,0,0,0,1.94-.23L216,66.25Z',
    },
    'minus': {
        regular: 'M224,128a8,8,0,0,1-8,8H40a8,8,0,0,1,0-16H216A8,8,0,0,1,224,128Z',
        bold: 'M228,128a12,12,0,0,1-12,12H40a12,12,0,0,1,0-24H216A12,12,0,0,1,228,128Z',
    },
    'person-simple-hike': {
        regular: 'M152,80a32,32,0,1,0-32-32A32,32,0,0,0,152,80Zm0-48a16,16,0,1,1-16,16A16,16,0,0,1,152,32Zm48,112v88a8,8,0,0,1-16,0V151.66c-25.75-2.25-34.35-15.52-42-27.36-2.85-4.39-5.56-8.57-9.13-12.19l-13.4,30.81,37.2,26.57A8,8,0,0,1,160,176v56a8,8,0,0,1-16,0V180.12l-31.07-22.2L79.34,235.19A8,8,0,0,1,72,240a7.84,7.84,0,0,1-3.19-.67,8,8,0,0,1-4.14-10.52L122.19,96.5a8,8,0,0,1,11-3.92,40.92,40.92,0,0,1,8,5.47c6.37,5.52,10.51,11.91,14.16,17.55,7.68,11.84,13.22,20.4,36.6,20.4A8,8,0,0,1,200,144ZM72,152a8,8,0,0,0,7.35-4.85l24-56a8,8,0,0,0-4.2-10.5l-28-12a8,8,0,0,0-10.5,4.2l-24,56a8,8,0,0,0,4.2,10.5l28,12A8,8,0,0,0,72,152ZM54.51,127.8,72.2,86.5l13.3,5.7L67.8,133.49Z',
        bold: 'M152,84a36,36,0,1,0-36-36A36,36,0,0,0,152,84Zm0-48a12,12,0,1,1-12,12A12,12,0,0,1,152,36Zm52,108v88a12,12,0,0,1-24,0V155.24c-24.92-3.37-33.94-17.29-41.38-28.76-1.55-2.39-3.05-4.71-4.67-6.88l-9.54,22L159,166.23a12,12,0,0,1,5,9.77v56a12,12,0,0,1-24,0V182.17l-25.37-18.12L83,236.78a12,12,0,1,1-22-9.57L118.52,94.9A12,12,0,0,1,135,89a45.53,45.53,0,0,1,8.84,6c6.78,5.89,11.09,12.53,14.89,18.39C166.27,125,170.8,132,192,132A12,12,0,0,1,204,144ZM64.6,153.88,39.27,143A12,12,0,0,1,33,127.27l24-56A12,12,0,0,1,72.73,65L98.34,76A12,12,0,1,1,88.89,98L74.3,91.76,59.76,125.7l14.29,6.12a12,12,0,1,1-9.45,22.06Z',
        fill: 'M120,48a32,32,0,1,1,32,32A32,32,0,0,1,120,48Zm72,88c-23.37,0-28.92-8.56-36.6-20.4-3.65-5.64-7.79-12-14.16-17.55a40.92,40.92,0,0,0-8-5.47,8,8,0,0,0-11,3.92L64.66,228.81a8,8,0,0,0,4.15,10.52A7.84,7.84,0,0,0,72,240a8,8,0,0,0,7.34-4.81l33.59-77.27L144,180.12V232a8,8,0,0,0,16,0V176a8,8,0,0,0-3.35-6.51l-37.2-26.57,13.4-30.81c3.57,3.62,6.28,7.8,9.13,12.19,7.67,11.84,16.27,25.11,42,27.36V232a8,8,0,0,0,16,0V144A8,8,0,0,0,192,136ZM72,152a8,8,0,0,0,7.36-4.85l24-56a8,8,0,0,0-4.2-10.5l-28-12a8,8,0,0,0-10.5,4.2l-24,56a8,8,0,0,0,4.2,10.5l28,12A8,8,0,0,0,72,152Z',
    },
    'plus': {
        regular: 'M224,128a8,8,0,0,1-8,8H136v80a8,8,0,0,1-16,0V136H40a8,8,0,0,1,0-16h80V40a8,8,0,0,1,16,0v80h80A8,8,0,0,1,224,128Z',
        bold: 'M228,128a12,12,0,0,1-12,12H140v76a12,12,0,0,1-24,0V140H40a12,12,0,0,1,0-24h76V40a12,12,0,0,1,24,0v76h76A12,12,0,0,1,228,128Z',
    },
    'x': {
        regular: 'M205.66,194.34a8,8,0,0,1-11.32,11.32L128,139.31,61.66,205.66a8,8,0,0,1-11.32-11.32L116.69,128,50.34,61.66A8,8,0,0,1,61.66,50.34L128,116.69l66.34-66.35a8,8,0,0,1,11.32,11.32L139.31,128Z',
        bold: 'M208.49,191.51a12,12,0,0,1-17,17L128,145,64.49,208.49a12,12,0,0,1-17-17L111,128,47.51,64.49a12,12,0,0,1,17-17L128,111l63.51-63.52a12,12,0,0,1,17,17L145,128Z',
    },
};
```

- [ ] **Step 5: Periksa asal-usul setiap path.** Satu-satunya unduhan tugas ini: berkas SVG Phosphor 2.1.1 yang dibaca
lalu dibuang, tidak disimpan.

```bash
BASIS=https://cdn.jsdelivr.net/npm/@phosphor-icons/core@2.1.1/assets
REGISTRI=resources/js/components/ui/ikon/phosphor.ts
gagal=0
for nama in arrow-down arrow-left arrow-up backpack bell chart-line-up check house list map-trifold minus person-simple-hike plus x; do
    for varian in "regular/$nama.svg" "bold/$nama-bold.svg"; do
        d=$(curl -fsSL "$BASIS/$varian" | grep -o ' d="[^"]*"' | head -1 | cut -d'"' -f2)
        if [ -z "$d" ] || ! grep -qF "'$d'" "$REGISTRI"; then echo "TIDAK COCOK: $varian"; gagal=1; fi
    done
done
for nama in backpack chart-line-up house map-trifold person-simple-hike; do
    d=$(curl -fsSL "$BASIS/fill/$nama-fill.svg" | grep -o ' d="[^"]*"' | head -1 | cut -d'"' -f2)
    if [ -z "$d" ] || ! grep -qF "'$d'" "$REGISTRI"; then echo "TIDAK COCOK: fill/$nama-fill.svg"; gagal=1; fi
done
[ "$gagal" = 0 ] && echo "33 path cocok dengan phosphor-icons/core 2.1.1"
```

Expected: satu baris `33 path cocok dengan phosphor-icons/core 2.1.1`, tanpa baris `TIDAK COCOK`.

- [ ] **Step 6: Tulis ulang komponen ikon React.** Ganti seluruh isi `resources/js/components/ui/Icon.tsx` dengan:

```tsx
/*! Ikon Phosphor (phosphor-icons/core 2.1.1), MIT License, Copyright (c) 2023 Phosphor Icons. Teks lisensi: resources/js/components/ui/LICENSE-phosphor.txt */
import { kelas } from '@/lib/kelas';
import { phosphor, type NamaPhosphor, type VarianPhosphor } from './ikon/phosphor';

export type NamaIkon = NamaPhosphor;

/** Ukuran render dalam px. Tidak ada ikon informatif di bawah 16 px (DK-39). */
export type UkuranIkon = 16 | 20 | 22 | 24 | 28 | 32;

const kelasUkuran: Record<UkuranIkon, string> = {
    16: 'size-4',
    20: 'size-5',
    22: 'size-[22px]',
    24: 'size-6',
    28: 'size-7',
    32: 'size-8',
};

/**
 * Bobot garis menurut ukuran render (spec v3 bagian 8.3): Bold di bawah 20 px supaya garis
 * tidak menipis menjadi rambut di layar ponsel, Regular mulai 20 px.
 */
export function bobotUntuk(ukuran: UkuranIkon): 'bold' | 'regular' {
    return ukuran < 20 ? 'bold' : 'regular';
}

interface IconProps {
    name: NamaIkon;
    size?: UkuranIkon;
    /** Varian isi, hanya untuk tab aktif: yang berubah adalah bentuk, bukan warna saja (WCAG 1.4.1). */
    isi?: boolean;
    /** Nama untuk ikon yang berdiri sendiri; tanpa label ikon disembunyikan dari pembaca layar. */
    label?: string;
    className?: string;
}

/**
 * Ikon Phosphor ditanam sebagai path, hanya di tempat yang membawa arti: arah naik dan
 * turun, penanda tab, dan aksi yang namanya sudah dibawa label. Nama yang tidak dikenal
 * menghasilkan kekosongan, bukan kotak rusak.
 */
export function Icon({ name, size = 16, isi = false, label, className }: IconProps) {
    const varian: VarianPhosphor | undefined = phosphor[name];
    const d = varian ? (isi && varian.fill ? varian.fill : varian[bobotUntuk(size)]) : undefined;

    if (!d) {
        return null;
    }

    return (
        <svg
            viewBox="0 0 256 256"
            fill="currentColor"
            className={kelas(kelasUkuran[size], 'inline-block shrink-0', className)}
            role={label ? 'img' : undefined}
            aria-hidden={label ? undefined : true}
        >
            {label ? <title>{label}</title> : null}
            <path d={d} />
        </svg>
    );
}
```

- [ ] **Step 7: Tulis ulang komponen ikon Blade.** Ganti seluruh isi `resources/views/components/ui/icon.blade.php` dengan:

```blade
@props(['name', 'label' => null, 'size' => 16, 'class' => ''])

@php
    /*
     * Ikon Phosphor (phosphor-icons/core 2.1.1), ditanam sebagai path.
     * Lisensi: MIT, Copyright (c) 2023 Phosphor Icons; teks lengkap di resources/js/components/ui/LICENSE-phosphor.txt.
     *
     * Ditanam, bukan diimpor dari pustaka: halaman Blade publik hanya memakai panah naik
     * dan turun, dan menambah dependensi demi dua bentuk menagih bita kepada setiap
     * pengguna untuk ikon yang tidak pernah ia lihat. Registri lengkap untuk halaman React
     * ada di resources/js/components/ui/ikon/phosphor.ts.
     *
     * Bobot mengikuti ukuran render seperti komponen React (spec v3 bagian 8.3, DK-39): Bold
     * di bawah 20 px, Regular mulai 20 px. Ukuran di bawah 16 px ditolak, karena ikon
     * informatif yang lebih kecil dari itu tidak terbaca.
     *
     * Nama yang tidak dikenal menghasilkan kekosongan, bukan kotak rusak: ikon tidak
     * pernah menjadi satu-satunya pembawa arti di sini, jadi hilangnya satu bentuk tidak
     * boleh merusak barisnya.
     */
    $ukuran = (int) $size;

    if (! in_array($ukuran, [16, 20, 24], true)) {
        throw new \InvalidArgumentException("Ukuran ikon {$ukuran} px tidak didukung: ikon informatif paling kecil 16 px, dan ukuran yang tersedia 16, 20, atau 24 (DK-39).");
    }

    $bentuk = [
        'arrow-up' => [
            'regular' => 'M205.66,117.66a8,8,0,0,1-11.32,0L136,59.31V216a8,8,0,0,1-16,0V59.31L61.66,117.66a8,8,0,0,1-11.32-11.32l72-72a8,8,0,0,1,11.32,0l72,72A8,8,0,0,1,205.66,117.66Z',
            'bold' => 'M208.49,120.49a12,12,0,0,1-17,0L140,69V216a12,12,0,0,1-24,0V69L64.49,120.49a12,12,0,0,1-17-17l72-72a12,12,0,0,1,17,0l72,72A12,12,0,0,1,208.49,120.49Z',
        ],
        'arrow-down' => [
            'regular' => 'M205.66,149.66l-72,72a8,8,0,0,1-11.32,0l-72-72a8,8,0,0,1,11.32-11.32L120,196.69V40a8,8,0,0,1,16,0V196.69l58.34-58.35a8,8,0,0,1,11.32,11.32Z',
            'bold' => 'M208.49,152.49l-72,72a12,12,0,0,1-17,0l-72-72a12,12,0,0,1,17-17L116,187V40a12,12,0,0,1,24,0V187l51.51-51.52a12,12,0,0,1,17,17Z',
        ],
    ];

    $isi = $bentuk[$name][$ukuran < 20 ? 'bold' : 'regular'] ?? null;
    $kelasUkuran = [16 => 'size-4', 20 => 'size-5', 24 => 'size-6'][$ukuran];
@endphp

@if ($isi)
    <svg viewBox="0 0 256 256" fill="currentColor" class="{{ trim($kelasUkuran.' inline-block shrink-0 '.$class) }}"
        @if ($label) role="img" @else aria-hidden="true" @endif>
        @if ($label)
            <title>{{ $label }}</title>
        @endif
        <path d="{{ $isi }}"/>
    </svg>
@endif
```

- [ ] **Step 8: Naikkan panah daftar pos ke 16 px Bold.**
- `resources/js/components/ui/CheckpointJourney.tsx:31-32`: `<Icon name="arrow-up" className="h-3 w-3" />` menjadi `<Icon name="arrow-up" size={16} />`, dan `<Icon name="arrow-down" className="h-3 w-3" />` menjadi `<Icon name="arrow-down" size={16} />`.
- `resources/views/components/ui/checkpoint-journey.blade.php:90,92`: `<x-ui.icon name="arrow-up" class="h-3 w-3" />` menjadi `<x-ui.icon name="arrow-up" />`, dan `<x-ui.icon name="arrow-down" class="h-3 w-3" />` menjadi `<x-ui.icon name="arrow-down" />`.

- [ ] **Step 9: Tab bawah memakai ukuran bernama dan varian isi.** Di `resources/js/layouts/NavLinks.tsx`:
- baris 1: `import { Icon } from '@/components/ui/Icon';` menjadi `import { Icon, type NamaIkon } from '@/components/ui/Icon';`
- di `permukaan`, lima nilai `ikon`: `'house'`, `'map-trifold'`, `'backpack'`, `'person-simple-hike'`, `'chart-line-up'` masing-masing diberi akhiran ` as const` (misalnya `ikon: 'house' as const,`). Jangan memakai `satisfies` pada larik: `NavigationConsistencyTest` membaca literal `export const permukaan = [` sampai `\n];`.
- docblock `TabBawah`: kalimat `Tab aktif ditandai tiga cara sekaligus, bukan warna saja:\n * garis di atas, tebal huruf, dan aria-current (WCAG 1.4.1).` menjadi `Tab aktif ditandai empat cara sekaligus, bukan warna saja:\n * ikon varian isi, garis di atas, tebal huruf, dan aria-current (WCAG 1.4.1).`
- tanda tangan `TabBawah`: `ikon: string;` menjadi `ikon: NamaIkon;`
- `<Icon name={ikon} className="h-6 w-6" />` menjadi `<Icon name={ikon} size={22} isi={aktif} />`

- [ ] **Step 10: Arah kembali memakai ikon, bukan panah teks.** Di `resources/js/components/trips/TataLetakPendakian.tsx`,
tambahkan `import { Icon } from '@/components/ui/Icon';` sesudah baris impor `Button`, lalu ganti
`<span aria-hidden="true">←</span>` dengan `<Icon name="arrow-left" size={20} />`.

- [ ] **Step 11: Pertahankan komentar lisensi di bundel.** Di `vite.config.js`, ganti akhir berkas:

```js
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
});
```

dengan:

```js
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    build: {
        rolldownOptions: {
            output: {
                // Komentar lisensi /*! ... */ ikut ke bundel produksi (HANDOVER bagian 0
                // butir 17): path ikon Phosphor (MIT) ditanam di kode dan MapLibre
                // (BSD-3-Clause) membawa pemberitahuannya sendiri; keduanya wajib ikut di
                // setiap salinan. Tanpa opsi ini minifier Vite 8 membuangnya. Komentar
                // anotasi dan JSDoc tetap dibuang.
                comments: { legal: true, annotation: false, jsdoc: false },
            },
        },
    },
});
```

- [ ] **Step 12: Perbarui baris Phosphor di daftar lisensi.** Di `docs/lisensi-pihak-ketiga.md`, ganti baris:

```markdown
| Phosphor Icons (path bobot regular dan fill, repositori phosphor-icons/core) | `resources/js/components/ui/Icon.tsx`, `resources/views/components/ui/icon.blade.php` | MIT, Copyright (c) 2023 Phosphor Icons | `resources/js/components/ui/LICENSE-phosphor.txt` |
```

dengan:

```markdown
| Phosphor Icons (path bobot regular, bold, dan fill; repositori phosphor-icons/core 2.1.1) | `resources/js/components/ui/ikon/phosphor.ts` (registri), `resources/js/components/ui/Icon.tsx`, `resources/views/components/ui/icon.blade.php`; pemberitahuannya ikut ke bundel produksi lewat `build.rolldownOptions.output.comments` di `vite.config.js` | MIT, Copyright (c) 2023 Phosphor Icons | `resources/js/components/ui/LICENSE-phosphor.txt` |
```

- [ ] **Step 13: Build ulang, lalu jalankan test tugas ini sampai lulus.** `test_the_production_build_keeps_the_licence_notices`
membaca bundel, jadi build dulu.

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: npm run build`
Expected: `✓ built`, dengan dua peringatan huruf `/fonts/...` yang sama seperti Tugas 2.

Run: `grep -l "Copyright (c) 2023 Phosphor Icons" public/build/assets/*.js | wc -l; grep -l "@license 3-Clause BSD" public/build/assets/*.js | wc -l`
Expected: dua baris, masing-masing `1` atau lebih (sebelum tugas ini keduanya `0`).

Run: `php artisan test --compact --filter='IconSystemTest|ThirdPartyLicenseTest|NavigationConsistencyTest|TapTargetTest|CheckpointJourney|HikeMode|TrailDetail'`
Expected: PASS, 59 test (1 dilewati sudah ada sebelum W1 di `TrailDetailMapTest`).

- [ ] **Step 14: Gerbang tugas.**

Run: `vendor/bin/pint --dirty --format agent`
Expected: `{"tool":"pint","result":"passed"}` atau berkas dirapikan tanpa perubahan makna.

Run: `npx tsc --noEmit`
Expected: keluar tanpa keluaran galat. Bila ada galat `Type 'string' is not assignable to type 'NamaIkon'`, sebuah
pemanggil masih mengirim nama ikon sebagai string bebas; ketikkan sebagai `NamaIkon`.

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal. Pada simulasi rencana: 1.441 test, 1.440 lulus, 1 dilewati.

- [ ] **Step 15: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 4: Pilot tiga glyph domain Jalur Berpos

Induk W1 butir 8 (DK-39, ruling W1-R3): tiga glyph pilot saja, `pos`, `tanjakan`, dan `resmi`, digambar dari tata bahasa
tanda merek. Set lengkap menunggu uji pengenalan Q15. Jam kerja pilot diukur, bukan ditaksir.

**Files:**
- Create: `resources/js/components/ui/ikon/glyphDomain.ts`
- Modify (tulis ulang): `resources/js/components/ui/Icon.tsx`
- Test: `tests/Feature/IconSystemTest.php` (tiga test baru)

**Interfaces:**
- Consumes: `Icon`, `bobotUntuk`, `NamaPhosphor` dari Tugas 3.
- Produces: `NamaIkon = NamaPhosphor | NamaGlyph`, sehingga `<Icon name="pos" | "tanjakan" | "resmi" size={...} />` berlaku
  di mana pun `<Icon>` dipakai; `NamaGlyph`, `GlyphDomain`, dan `glyphDomain` dari `@/components/ui/ikon/glyphDomain`.
  Glyph digambar sebagai goresan: tebal 24 unit di bawah 20 px, 16 unit mulai 20 px, ujung dan sambungan bulat.
  Aturan pakai: `resmi` hanya untuk keterangan bersumber OFFICIAL (R-009).

- [ ] **Step 1: Catat waktu mulai.**

Run: `date '+%Y-%m-%d %H:%M'`
Expected: satu cap waktu. Tulis di laporan tugas sebagai "Pilot glyph mulai". Tugas 11 menyalin selisih waktu mulai dan
selesai ke `DESIGN.md`; bila salah satu cap waktu tidak tercatat, tulis "belum terukur", jangan menaksir.

- [ ] **Step 2: Tulis test glyph yang gagal.** Di `tests/Feature/IconSystemTest.php`, tambahkan tiga metode berikut sesudah
`test_no_button_label_carries_a_decorative_arrow` (Tugas 3), sebelum `}` penutup kelas:

```php

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
```

- [ ] **Step 3: Jalankan dan pastikan gagal.**

Run: `php artisan test --compact --filter=IconSystemTest`
Expected: FAIL. Pada simulasi rencana: 14 test, 11 lulus, 3 error (`resources/js/components/ui/ikon/glyphDomain.ts` belum
ada).

- [ ] **Step 4: Buat registri glyph.** Buat `resources/js/components/ui/ikon/glyphDomain.ts`. Berkas ini tidak boleh
menyebut kata "phosphor" (`ThirdPartyLicenseTest` menganggap berkas yang menyebutnya memuat path Phosphor):

```ts
/**
 * Glyph domain pilot dari tanda merek "Jalur Berpos" (spec v3 bagian 8.3, DK-39, keputusan
 * pemilik D4).
 *
 * Tata bahasanya sama dengan tanda logo: garis rute monoline berujung bulat, belokan
 * bersudut, dan cincin pos. Digambar sebagai goresan pada kisi 256 unit yang sama dengan
 * ikon lain, dengan titik di dalam area hidup 32 sampai 224, sehingga tebalnya mengikuti
 * bobot per ukuran: goresan 24 unit di bawah 20 px, 16 unit mulai 20 px.
 *
 * Hanya tiga glyph pilot (ruling W1-R3). Set lengkap 8 sampai 12 glyph menunggu uji
 * pengenalan dengan 5 sampai 8 pendaki (spec v3 bagian 15, Q15).
 *
 * - pos: jalur datar, tanjakan 60 derajat, cincin pos di ujungnya.
 * - tanjakan: jalur datar lalu naik 60 derajat, mata panah di ujung tanjakan.
 * - resmi: cap bergagang di atas alasnya, pengganti perisai bercentang. Hanya untuk
 *   keterangan bersumber OFFICIAL (R-009), tidak pernah untuk ADMIN VERIFIED atau komunitas.
 */
export interface GlyphDomain {
    garis: readonly string[];
    cincin: ReadonlyArray<{ cx: number; cy: number; r: number }>;
}

export type NamaGlyph = 'pos' | 'tanjakan' | 'resmi';

export const glyphDomain: Record<NamaGlyph, GlyphDomain> = {
    pos: {
        garis: ['M40 208 L100 208 L152 118.6'],
        cincin: [{ cx: 172, cy: 84, r: 40 }],
    },
    tanjakan: {
        garis: ['M40 200 L96 200 L168 76', 'M126.4 100 L168 76 L168 124'],
        cincin: [],
    },
    resmi: {
        garis: ['M112 108.2 L104 148', 'M144 108.2 L152 148', 'M48 148 L208 148 L208 184 L48 184 Z', 'M64 216 L192 216'],
        cincin: [{ cx: 128, cy: 76, r: 36 }],
    },
};
```

- [ ] **Step 5: Gambar glyph di komponen ikon.** Ganti seluruh isi `resources/js/components/ui/Icon.tsx` dengan:

```tsx
/*! Ikon Phosphor (phosphor-icons/core 2.1.1), MIT License, Copyright (c) 2023 Phosphor Icons. Teks lisensi: resources/js/components/ui/LICENSE-phosphor.txt */
import { kelas } from '@/lib/kelas';
import { glyphDomain, type NamaGlyph } from './ikon/glyphDomain';
import { phosphor, type NamaPhosphor, type VarianPhosphor } from './ikon/phosphor';

export type NamaIkon = NamaPhosphor | NamaGlyph;

/** Ukuran render dalam px. Tidak ada ikon informatif di bawah 16 px (DK-39). */
export type UkuranIkon = 16 | 20 | 22 | 24 | 28 | 32;

const kelasUkuran: Record<UkuranIkon, string> = {
    16: 'size-4',
    20: 'size-5',
    22: 'size-[22px]',
    24: 'size-6',
    28: 'size-7',
    32: 'size-8',
};

/**
 * Bobot garis menurut ukuran render (spec v3 bagian 8.3): Bold di bawah 20 px supaya garis
 * tidak menipis menjadi rambut di layar ponsel, Regular mulai 20 px.
 */
export function bobotUntuk(ukuran: UkuranIkon): 'bold' | 'regular' {
    return ukuran < 20 ? 'bold' : 'regular';
}

function adalahGlyph(nama: NamaIkon): nama is NamaGlyph {
    return nama in glyphDomain;
}

interface IconProps {
    name: NamaIkon;
    size?: UkuranIkon;
    /** Varian isi, hanya untuk tab aktif: yang berubah adalah bentuk, bukan warna saja (WCAG 1.4.1). */
    isi?: boolean;
    /** Nama untuk ikon yang berdiri sendiri; tanpa label ikon disembunyikan dari pembaca layar. */
    label?: string;
    className?: string;
}

/**
 * Ikon Phosphor dan glyph domain, hanya di tempat yang membawa arti: arah naik dan turun,
 * penanda tab, jenis pos, dan aksi yang namanya sudah dibawa label. Glyph domain digambar
 * sebagai goresan dengan tebal Phosphor pada bobot yang sama (24 unit Bold, 16 unit
 * Regular). Nama yang tidak dikenal menghasilkan kekosongan, bukan kotak rusak.
 */
export function Icon({ name, size = 16, isi = false, label, className }: IconProps) {
    const kelasSvg = kelas(kelasUkuran[size], 'inline-block shrink-0', className);

    if (adalahGlyph(name)) {
        const glyph = glyphDomain[name];

        return (
            <svg
                viewBox="0 0 256 256"
                fill="none"
                stroke="currentColor"
                strokeWidth={bobotUntuk(size) === 'bold' ? 24 : 16}
                strokeLinecap="round"
                strokeLinejoin="round"
                className={kelasSvg}
                role={label ? 'img' : undefined}
                aria-hidden={label ? undefined : true}
            >
                {label ? <title>{label}</title> : null}
                {glyph.garis.map((d) => (
                    <path key={d} d={d} />
                ))}
                {glyph.cincin.map((c) => (
                    <circle key={`${c.cx}-${c.cy}`} cx={c.cx} cy={c.cy} r={c.r} />
                ))}
            </svg>
        );
    }

    const varian: VarianPhosphor | undefined = phosphor[name];
    const d = varian ? (isi && varian.fill ? varian.fill : varian[bobotUntuk(size)]) : undefined;

    if (!d) {
        return null;
    }

    return (
        <svg
            viewBox="0 0 256 256"
            fill="currentColor"
            className={kelasSvg}
            role={label ? 'img' : undefined}
            aria-hidden={label ? undefined : true}
        >
            {label ? <title>{label}</title> : null}
            <path d={d} />
        </svg>
    );
}
```

- [ ] **Step 6: Jalankan test ikon sampai lulus.**

Run: `php artisan test --compact --filter='IconSystemTest|ThirdPartyLicenseTest'`
Expected: PASS, 19 test.

- [ ] **Step 7: Periksa bentuknya di layar.** Tulis berkas sementara `glyph-pilot.html` di folder scratchpad sesi Anda,
bukan di repo, berisi:

```html
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Pratinjau glyph pilot</title>
<style>
    body { margin: 16px; font: 14px system-ui, sans-serif; background: #f6f3ec; color: #1d2320; }
    .tema { padding: 12px; border-radius: 12px; }
    .gelap { background: #0f1311; color: #f2efe8; margin-top: 12px; }
    .baris { display: flex; gap: 20px; align-items: center; margin: 8px 0; }
    .sel { display: flex; flex-direction: column; align-items: center; gap: 4px; font-size: 12px; }
    b { width: 80px; }
</style>
</head>
<body>
<div id="isi"></div>
<script>
    // Disalin dari resources/js/components/ui/ikon/glyphDomain.ts; berkas ini hanya untuk pemeriksaan mata.
    const glyph = {
        pos: { garis: ['M40 208 L100 208 L152 118.6'], cincin: [{ cx: 172, cy: 84, r: 40 }] },
        tanjakan: { garis: ['M40 200 L96 200 L168 76', 'M126.4 100 L168 76 L168 124'], cincin: [] },
        resmi: { garis: ['M112 108.2 L104 148', 'M144 108.2 L152 148', 'M48 148 L208 148 L208 184 L48 184 Z', 'M64 216 L192 216'], cincin: [{ cx: 128, cy: 76, r: 36 }] },
    };
    const gambar = (g, ukuran) => {
        const tebal = ukuran < 20 ? 24 : 16;
        const garis = g.garis.map((d) => `<path d="${d}"/>`).join('');
        const cincin = g.cincin.map((c) => `<circle cx="${c.cx}" cy="${c.cy}" r="${c.r}"/>`).join('');
        return `<svg width="${ukuran}" height="${ukuran}" viewBox="0 0 256 256" fill="none" stroke="currentColor" stroke-width="${tebal}" stroke-linecap="round" stroke-linejoin="round">${garis}${cincin}</svg>`;
    };
    let html = '';
    for (const tema of ['', 'gelap']) {
        html += `<div class="tema ${tema}">`;
        for (const [nama, g] of Object.entries(glyph)) {
            html += `<div class="baris"><b>${nama}</b>` + [16, 20, 24, 32].map((u) => `<div class="sel">${gambar(g, u)}<span>${u}</span></div>`).join('') + '</div>';
        }
        html += '</div>';
    }
    document.getElementById('isi').innerHTML = html;
</script>
</body>
</html>
```

Buka lewat MCP chrome-devtools (`new_page` dengan URL `file:///` ke berkas itu), lalu `take_screenshot`. Periksa di 16,
20, 24, dan 32 px pada latar terang dan gelap: `pos` terbaca sebagai jalur yang naik ke cincin pos, bukan kaca pembesar;
`tanjakan` sebagai jalur datar yang menanjak dengan mata panah; `resmi` sebagai cap bergagang di atas alasnya; tidak ada
goresan yang terpotong tepi kotak di 16 px. Catat temuan di laporan tugas. Bila salah satu tidak terbaca, laporkan ke
koordinator; jangan mengubah geometri tanpa memperbarui test area hidup.

- [ ] **Step 8: Gerbang tugas dan waktu selesai.**

Run: `vendor/bin/pint --dirty --format agent`
Expected: `{"tool":"pint","result":"passed"}` atau berkas dirapikan tanpa perubahan makna.

Run: `npx tsc --noEmit`
Expected: keluar tanpa keluaran galat.

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal. Pada simulasi rencana: 1.444 test, 1.443 lulus, 1 dilewati.

Run: `date '+%Y-%m-%d %H:%M'`
Expected: satu cap waktu. Tulis di laporan tugas sebagai "Pilot glyph selesai", beserta selisihnya dalam jam dan menit.

- [ ] **Step 9: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 5: Primitif dasar dan rumah primitif

Induk W1 butir 4 bagian pertama, dengan keputusan rumah primitif (ruling W1-R1). Primitif tinggal di
`resources/js/components/ui/`. Enam komponen domain yang juga tinggal di sana (CheckpointJourney 2 pengimpor,
ElevationProfile 2, FactorBars 4, FitBadge 6, StatusBadge 7, Freshness 3; total 24 impor ditambah jalur di 5 test) tidak
dipindah di W1: W3 menulis ulang berkas yang sama, jadi pemindahan sekarang dikerjakan dua kali (W1-K1). Tugas ini
membuat kelas bersama fokus dan tekan, menyelaraskan Button, Card, Field, dan EmptyState dengan bentuk v3, lalu
menambah Lencana, Avatar, TombolIkon, dan Chip.

**Files:**
- Create: `resources/js/components/ui/gaya.ts`, `resources/js/components/ui/Lencana.tsx`, `resources/js/components/ui/Avatar.tsx`, `resources/js/components/ui/TombolIkon.tsx`, `resources/js/components/ui/Chip.tsx`, `tests/Feature/DesignPrimitivesTest.php`
- Modify (tulis ulang): `resources/js/components/ui/Button.tsx`, `resources/js/components/ui/Card.tsx`, `resources/js/components/ui/Field.tsx`, `resources/js/components/ui/EmptyState.tsx`
- Modify (jenis keadaan kosong): `resources/js/pages/Admin/Health.tsx:169`, `resources/js/pages/Admin/Permits.tsx:228`, `resources/js/pages/Community/HikingHistory.tsx:52`, `resources/js/pages/Track/Index.tsx:43`, `resources/js/pages/Trails/Saved.tsx:57`, `resources/js/pages/Trails/Index.tsx:96-99`
- Modify: `docs/ARCHITECTURE.md` (tiga baris tabel)
- Test: `tests/Feature/LongInputLayoutTest.php`

**Interfaces:**
- Consumes: token Tugas 1, skala tipe Tugas 2, `Icon` dan `NamaIkon` Tugas 3 dan 4, `kelas()`.
- Produces, dipakai Tugas 6 sampai 9 dan W2 sampai W8:
  - `gaya.ts`: `fokusTerlihat`, `fokusKelompok`, `fokusDiDalam`, `umpanTekan`, `umpanTekanKelompok` (untaian kelas).
  - `Button` tanpa perubahan API (`variant`, `size`, `href`, `external`, `processing`); bentuk v3.
  - `EmptyState` sekarang mewajibkan `jenis: 'pertama' | 'tersaring' | 'menunggu'`; `jenis="menunggu"` mewajibkan `menunggu: string` (siapa yang ditunggu). `title`, `description?`, `children?` tetap.
  - `Lencana({ nada?: NadaLencana, bentuk?: 'pil' | 'kotak', garis?: 'tanpa' | 'padat' | 'putus', ikon?: NamaIkon, className?, children })`, `NadaLencana = 'netral' | 'merek' | 'peringatan' | 'bahaya' | 'komunitas' | 'gelap' | 'cocok' | 'perlu' | 'kurang' | 'belum'`.
  - `Avatar({ nama, foto?, ukuran?: 32 | 44 | 64, hiasan?: boolean, className? })` dan `inisial(nama): string`.
  - `TombolIkon({ ikon: NamaIkon, label: string, varian?: 'permukaan' | 'sumur' | 'polos', bentuk?: 'bulat' | 'kotak', besar?: boolean, ...atribut button kecuali children dan aria-label })`.
  - `ChipPilihan({ dipilih, onUbah(dipilih), className?, children })` dan `ChipLepas({ onLepas, className?, children })`.

- [ ] **Step 1: Buat test primitif yang gagal.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan make:test --phpunit DesignPrimitivesTest --no-interaction`
Expected: `INFO  Test [E:\praktik_aplikasi_web	ests\Feature\DesignPrimitivesTest.php] created successfully.`

Ganti seluruh isinya dengan:

```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Primitif desain v3 (spec v3 bagian 8.4 sampai 8.8; rencana induk W1 butir 4).
 *
 * Proyek belum punya test runner JavaScript dan dependensi baru tidak diizinkan (DK-24),
 * jadi kontrak setiap primitif dijaga dari sumbernya: fokus terlihat, target sentuh
 * 44 px, nama aksesibel, keadaan yang tidak dibawa warna saja, dan tiga jenis keadaan
 * kosong. Perilaku yang hanya terlihat di layar diperiksa di pameran primitif dan
 * tangkapan pratinjau.
 */
class DesignPrimitivesTest extends TestCase
{
    use SumberReact;

    /** Penanda fokus terlihat yang diakui: kelas bersama gaya.ts, cincin langsung, atau primitif yang membawanya. */
    private const POLA_FOKUS = '/\bfokusTerlihat\b|\bfokusKelompok\b|\bfokusDiDalam\b|\bfocus-visible:ring-2\b|\bfocus:ring-brand-600\b|<Checkbox\b|<TombolIkon\b/';

    /** Penanda target sentuh paling sedikit 44 px. */
    private const POLA_TARGET = '/(?<![\w:-])(?:min-h-11|min-h-12|h-1[1-6])(?![\w-])|<TombolIkon\b/';

    /**
     * Primitif yang menerima fokus atau sentuhan.
     *
     * @return array<string, array{0: string}>
     */
    public static function primitifInteraktif(): array
    {
        return [
            'tombol' => ['components/ui/Button.tsx'],
            'tombol ikon' => ['components/ui/TombolIkon.tsx'],
            'chip' => ['components/ui/Chip.tsx'],
        ];
    }

    #[DataProvider('primitifInteraktif')]
    public function test_every_interactive_primitive_shows_a_visible_focus(string $berkas): void
    {
        $this->assertMatchesRegularExpression(self::POLA_FOKUS, $this->sumber($berkas), "{$berkas} tanpa fokus terlihat.");
    }

    #[DataProvider('primitifInteraktif')]
    public function test_every_interactive_primitive_offers_a_44px_target(string $berkas): void
    {
        $this->assertMatchesRegularExpression(self::POLA_TARGET, $this->sumber($berkas), "{$berkas} tanpa target sentuh 44 px.");
    }

    /**
     * Kelas bersama membawa cincin fokus merek dan tekan 0,98 selama 120 ms lewat token
     * gerak, sehingga setelan kurangi gerak di app.css memangkasnya tanpa cabang sendiri.
     */
    public function test_the_shared_classes_carry_focus_press_and_reduced_motion(): void
    {
        $gaya = $this->sumber('components/ui/gaya.ts');

        $this->assertStringContainsString("fokusTerlihat = 'focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2'", $gaya);
        $this->assertStringContainsString('active:scale-[0.98]', $gaya);
        $this->assertStringContainsString('group-active:scale-[0.98]', $gaya);
        $this->assertSame(2, substr_count($gaya, 'duration-sentuh ease-gerak'), 'Tekan memakai token durasi dan kurva gerak.');
        $this->assertDoesNotMatchRegularExpression('/\banimate-|\bduration-\d/', $gaya, 'Gerak primitif hanya lewat token bernama.');
    }

    /**
     * Tombol ikon selalu bernama (WCAG 4.1.2): prop label wajib dan dirender sebagai teks
     * sr-only, dan setiap pemakaiannya menyebut label.
     */
    public function test_an_icon_button_always_carries_a_readable_name(): void
    {
        $tombol = $this->sumber('components/ui/TombolIkon.tsx');

        $this->assertMatchesRegularExpression('/\n    label: string;/', $tombol, 'Label tombol ikon wajib, bukan opsional.');
        $this->assertStringContainsString("Omit<ButtonHTMLAttributes<HTMLButtonElement>, 'children' | 'aria-label'>", $tombol);
        $this->assertStringContainsString('<span className="sr-only">{label}</span>', $tombol);

        $tanpaLabel = [];

        foreach ($this->berkasTsx() as $berkas) {
            foreach ($this->tagBernama($this->tanpaKomentarJs($berkas->getContents()), 'TombolIkon') as [$tag, $baris]) {
                if (! preg_match('/\blabel=(?:"[^"]*\S[^"]*"|\{)/', $tag)) {
                    $tanpaLabel[] = $berkas->getRelativePathname().':'.$baris;
                }
            }
        }

        $this->assertSame([], $tanpaLabel, 'Tombol ikon tanpa label: '.implode(', ', $tanpaLabel));
    }

    /**
     * Chip terpilih ditandai centang, tebal huruf, garis, dan aria-pressed, bukan warna
     * saja (WCAG 1.4.1); chip saringan aktif menyebut "Lepas saringan" kepada pembaca layar.
     */
    public function test_a_chip_states_its_selection_without_colour_alone(): void
    {
        $chip = $this->sumber('components/ui/Chip.tsx');

        $this->assertStringContainsString('aria-pressed={dipilih}', $chip);
        $this->assertStringContainsString('{dipilih ? <Icon name="check" size={16} /> : null}', $chip);
        $this->assertMatchesRegularExpression("/dipilih \? '[^']*\bfont-bold\b[^']*' : '[^']*\bfont-semibold\b/", $chip);
        $this->assertStringContainsString('<span className="sr-only">Lepas saringan </span>', $chip);
    }

    /**
     * Tiga jenis keadaan kosong (PRD §104, antislop R-27): pertama kali, tersaring, dan
     * menunggu. Jenis menunggu wajib menyebut siapa yang ditunggu, dan setiap pemakaian
     * menyebut jenisnya.
     */
    public function test_the_empty_state_has_three_kinds_and_names_who_is_awaited(): void
    {
        $kosong = $this->sumber('components/ui/EmptyState.tsx');

        $this->assertStringContainsString("(IsiKosong & { jenis: 'pertama' | 'tersaring' }) | (IsiKosong & { jenis: 'menunggu'; menunggu: string })", $kosong);
        $this->assertStringContainsString("{props.jenis === 'menunggu' ? <p", $kosong);
        $this->assertStringContainsString('{props.menunggu}</p>', $kosong);
        $this->assertStringContainsString('data-kosong={jenis}', $kosong);

        $tanpaJenis = [];

        foreach ($this->berkasTsx() as $berkas) {
            foreach ($this->tagBernama($this->tanpaKomentarJs($berkas->getContents()), 'EmptyState') as [$tag, $baris]) {
                if (! preg_match('/\bjenis="(?:pertama|tersaring|menunggu)"/', $tag)) {
                    $tanpaJenis[] = $berkas->getRelativePathname().':'.$baris;
                }
            }
        }

        $this->assertSame([], $tanpaJenis, 'Keadaan kosong tanpa jenis: '.implode(', ', $tanpaJenis));
    }

    /**
     * Basis data pemilik belum punya jalur terbit (0 jalur bergeometri). Jelajah tanpa
     * saringan yang kosong harus menyebut siapa yang ditunggu, bukan "Tidak ada jalur yang
     * cocok" seolah saringan penyebabnya; saringan yang kosong menawarkan pelepasnya.
     */
    public function test_jelajah_names_who_is_awaited_when_nothing_is_published(): void
    {
        $jelajah = $this->tanpaKomentarJs($this->sumber('pages/Trails/Index.tsx'));

        $this->assertStringContainsString('{trails.data.length === 0 && adaSaringan ? (', $jelajah);
        $this->assertMatchesRegularExpression('/jenis="tersaring"[\s\S]*?Hapus semua saringan/', $jelajah);
        $this->assertMatchesRegularExpression('/\) : trails\.data\.length === 0 \? \(\s*<EmptyState\s+jenis="menunggu"/', $jelajah);
        $this->assertStringContainsString('menunggu="Menunggu kurator Memount menerbitkan jalur yang datanya lengkap"', $jelajah);
        $this->assertStringNotContainsString('Tidak ada jalur yang cocok', $jelajah);
    }

    /**
     * Lencana berbicara dengan huruf kalimat tebal 13 px, bukan kapital bertracking lebar
     * (spec v3 bagian 8.2; ciri klaster "tampilan AI"). Tidak ada kapital yang diketik di
     * dalam lencana mana pun.
     */
    public function test_a_badge_speaks_in_sentence_case(): void
    {
        $lencana = $this->tanpaKomentarJs($this->sumber('components/ui/Lencana.tsx'));

        $this->assertStringContainsString('text-meta font-bold', $lencana);
        $this->assertDoesNotMatchRegularExpression('/\buppercase\b|\btracking-/', $lencana);

        $kapital = [];

        foreach ($this->berkasTsx() as $berkas) {
            preg_match_all('/<Lencana\b[^>]*>\s*([^<{]+?)\s*</', $this->tanpaKomentarJs($berkas->getContents()), $isi);

            foreach ($isi[1] as $teks) {
                if (preg_match('/^[A-Z][A-Z ]{2,}$/', $teks)) {
                    $kapital[] = $berkas->getRelativePathname().': '.$teks;
                }
            }
        }

        $this->assertSame([], $kapital, 'Lencana berhuruf kapital: '.implode(', ', $kapital));
    }

    /**
     * Avatar berinisial untuk data contoh (antislop R-23). Di samping nama tertulis ia
     * disembunyikan dari pembaca layar; berdiri sendiri, ia bernama.
     */
    public function test_an_avatar_draws_initials_and_hides_beside_a_name(): void
    {
        $avatar = $this->sumber('components/ui/Avatar.tsx');

        $this->assertStringContainsString("toLocaleUpperCase('id-ID')", $avatar);
        $this->assertStringContainsString('hiasan = true', $avatar);
        $this->assertStringContainsString('<span aria-hidden="true" className={dasar}>', $avatar);
        $this->assertStringContainsString('<span role="img" aria-label={nama} className={dasar}>', $avatar);
        $this->assertStringContainsString("alt={hiasan ? '' : nama}", $avatar);
    }

    /**
     * Tombol v3: radius tombol 16 px, label tebal yang membungkus di tengah, tekan 0,98, dan
     * teks on-brand di atas isian merek (spec v3 bagian 8.1, 8.4, dan 8.6).
     */
    public function test_the_button_follows_the_v3_shape_weight_and_press(): void
    {
        $tombol = $this->sumber('components/ui/Button.tsx');

        $this->assertMatchesRegularExpression("/const semua = kelas\(\s*'[^']*\brounded-tombol\b[^']*\btext-center\b[^']*\bfont-bold\b[^']*',/", $tombol);
        $this->assertMatchesRegularExpression('/\bumpanTekan,\n/', $tombol);
        $this->assertStringContainsString("primary: 'bg-brand-700 text-on-brand hover:bg-brand-800", $tombol);
    }

    private function sumber(string $berkas): string
    {
        return File::get(resource_path('js/'.$berkas));
    }
}
```

- [ ] **Step 2: Tambahkan test label yang membungkus.** Di `tests/Feature/LongInputLayoutTest.php`, ganti impor:

```php
use Illuminate\Support\Facades\File;
use Tests\TestCase;
```

dengan:

```php
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
```

lalu tambahkan metode berikut sesudah `test_free_text_fields_accept_input_long_enough_to_matter`, sebelum `}` penutup kelas:

```php

    /**
     * Label primitif membungkus, tidak dipotong (spec v3, Review Focus W1: teks Indonesia
     * panjang di 360 px dan WCAG 1.4.12). Nama jalur, tafsiran kalimat, dan status resmi
     * sering lebih panjang dari ruangnya; kalimat yang terpotong elipsis kehilangan
     * keterangan tepat di bagian yang membedakan satu pilihan dari yang lain.
     *
     * @return array<string, array{0: string}>
     */
    public static function primitifBerlabel(): array
    {
        return [
            'tombol' => ['components/ui/Button.tsx'],
            'chip' => ['components/ui/Chip.tsx'],
            'lencana' => ['components/ui/Lencana.tsx'],
            'keadaan kosong' => ['components/ui/EmptyState.tsx'],
        ];
    }

    #[DataProvider('primitifBerlabel')]
    public function test_a_primitive_label_wraps_instead_of_being_cut(string $berkas): void
    {
        $isi = (string) preg_replace('#/\*.*?\*/#s', ' ', File::get(resource_path('js/'.$berkas)));

        $this->assertDoesNotMatchRegularExpression('/\b(?:truncate|whitespace-nowrap|text-ellipsis|line-clamp-\d)\b/', $isi, "{$berkas} memotong labelnya.");
    }
```

- [ ] **Step 3: Jalankan dan pastikan gagal.**

Run: `php artisan test --compact --filter='DesignPrimitivesTest|LongInputLayoutTest'`
Expected: FAIL. Pada simulasi rencana: 20 test, 6 lulus, 3 gagal, 11 error (berkas `gaya.ts`, `TombolIkon.tsx`, `Chip.tsx`,
`Lencana.tsx`, `Avatar.tsx` belum ada; EmptyState, Jelajah, dan Button masih bentuk lama).

- [ ] **Step 4: Buat kelas bersama fokus dan tekan.** Buat `resources/js/components/ui/gaya.ts`:

```ts
/**
 * Kelas bersama primitif: fokus terlihat dan umpan balik tekan (spec v3 bagian 8.4 dan 8.6).
 *
 * Satu sumber supaya setiap kontrol menandai fokus dengan cincin yang sama (brand-600,
 * paling sedikit 3:1 terhadap kanvas dan kartu di kedua tema, ColourContrastTest) dan
 * menekan dengan gerak yang sama: menyusut ke 0,98 selama 120 ms. Setelan Gerak dan
 * prefers-reduced-motion memangkas durasinya lewat app.css, jadi di sini tidak ada cabang
 * gerak sendiri.
 */

/** Cincin fokus pada elemen yang difokus itu sendiri. */
export const fokusTerlihat = 'focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2';

/** Cincin fokus pada anak di dalam elemen berkelas group: bentuk visual lebih kecil dari target sentuhnya. */
export const fokusKelompok = 'group-focus-visible:ring-2 group-focus-visible:ring-brand-600 group-focus-visible:ring-offset-2';

/** Cincin fokus pada label yang membungkus input tersembunyi (kontrol segmen). */
export const fokusDiDalam = 'has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-brand-600 has-[:focus-visible]:ring-offset-2';

/** Umpan balik tekan: hanya transform dan warna yang berpindah, tanpa gerak ketika nonaktif. */
export const umpanTekan = 'transition-[transform,background-color,border-color,color] duration-sentuh ease-gerak active:scale-[0.98] disabled:active:scale-100';

/** Umpan balik tekan untuk anak di dalam elemen berkelas group. */
export const umpanTekanKelompok = 'transition-[transform,background-color,border-color,color] duration-sentuh ease-gerak group-active:scale-[0.98]';
```

- [ ] **Step 5: Tombol bentuk v3.** Ganti seluruh isi `resources/js/components/ui/Button.tsx` dengan berkas di bawah. Markup
`processing` dan cabang `href` tidak berubah (`ButtonFeedbackTest`), `min-h-11` tetap di untaian pertama `kelas(` dan
cabang `ukuran` tidak menyebut tinggi (`DesignSystemTest`), dan `focus-visible:ring-2` tetap tertulis di berkas ini.

```tsx
import { Tautan } from './Tautan';
import type { ButtonHTMLAttributes, ReactNode } from 'react';
import { kelas } from '@/lib/kelas';
import { umpanTekan } from './gaya';

type Varian = 'primary' | 'secondary' | 'danger' | 'ghost';

interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    variant?: Varian;
    size?: 'md' | 'sm';
    href?: string;
    external?: boolean;
    processing?: boolean;
    children: ReactNode;
}

/**
 * Satu-satunya definisi tombol aplikasi: min-h-11 di kelas dasar untuk target sentuh
 * (PRD §87), focus-visible:ring untuk fokus terlihat, dan varian yang rasionya sudah
 * dihitung ColourContrastTest. brand-700 sebagai isian dengan teks on-brand, bukan
 * brand-600 yang gagal AA.
 *
 * Bentuk v3 (spec v3 bagian 8.4 dan 8.6): radius tombol 16 px, label tebal 700 yang
 * membungkus di tengah bila panjang alih-alih terpotong, dan tekan yang menyusut ke 0,98
 * selama 120 ms. Label tanpa panah hiasan (spec v3 bagian 8.8).
 */
export function Button({
    variant = 'primary',
    size = 'md',
    href,
    external = false,
    processing = false,
    className,
    children,
    type = 'button',
    disabled,
    ...rest
}: ButtonProps) {
    const ukuran = size === 'sm' ? 'px-4 py-2 text-kecil' : 'px-5 py-3 text-isi';

    const gaya: Record<Varian, string> = {
        primary: 'bg-brand-700 text-on-brand hover:bg-brand-800 focus-visible:ring-brand-600',
        secondary: 'border border-control bg-surface text-primary hover:bg-surface-sunken focus-visible:ring-brand-600',
        danger: 'bg-danger-600 text-white hover:bg-danger-700 focus-visible:ring-danger-600',
        ghost: 'text-brand-ink underline underline-offset-2 hover:text-brand-900 focus-visible:ring-brand-600',
    };

    const semua = kelas(
        'inline-flex items-center justify-center gap-2 rounded-tombol min-h-11 text-center font-bold',
        'focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
        umpanTekan,
        ukuran,
        gaya[variant],
        className,
    );

    if (href) {
        // Atribut tombol lain tidak berlaku pada tautan, tetapi nama aksesibel harus
        // ikut: "Checkpoint" yang berulang di setiap baris tabel perlu nama jalurnya.
        const nama = { 'aria-label': rest['aria-label'], 'aria-describedby': rest['aria-describedby'], title: rest.title };

        return external ? (
            <a href={href} className={semua} {...nama}>
                {children}
            </a>
        ) : (
            <Tautan href={href} className={semua} {...nama}>
                {children}
            </Tautan>
        );
    }

    return (
        <button type={type} className={semua} disabled={disabled || processing} aria-busy={processing || undefined} {...rest}>
            {processing ? (
                <span className="inline-flex items-center gap-2">
                    <svg className="size-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z" />
                    </svg>
                    <span className="sr-only">Memproses</span>
                </span>
            ) : null}
            {children}
        </button>
    );
}
```

- [ ] **Step 6: Kartu dan isian bentuk v3.** Ganti seluruh isi `resources/js/components/ui/Card.tsx` dengan:

```tsx
import type { ReactNode } from 'react';
import { kelas } from '@/lib/kelas';

interface CardProps {
    title?: string;
    subtitle?: string;
    className?: string;
    children?: ReactNode;
}

/**
 * Satu garis rambut, bukan bayangan: kartu memisahkan tanpa mengangkat (spec v3 bagian
 * 8.5). Radius kartu 18 px lewat rounded-lg, judul memakai skala bagian.
 */
export function Card({ title, subtitle, className, children }: CardProps) {
    return (
        <section className={kelas('rounded-lg border border-subtle bg-surface p-5 sm:p-6', className)}>
            {title ? <h2 className="text-bagian text-primary">{title}</h2> : null}
            {subtitle ? <p className="mt-1 max-w-prose text-isi text-secondary">{subtitle}</p> : null}
            <div className={kelas((title || subtitle) && 'mt-3')}>{children}</div>
        </section>
    );
}
```

Ganti seluruh isi `resources/js/components/ui/Field.tsx` dengan berkas di bawah. Badan `FormField` tidak berubah selain
kelas petunjuknya (`KeyboardAndScreenReaderTest`); konstanta `kontrol` dan kelas kotak centang tetap memuat
`focus:border-brand-600` dan `focus:ring-brand-600` (`ColourContrastTest`).

```tsx
import type { InputHTMLAttributes, LabelHTMLAttributes, SelectHTMLAttributes, TextareaHTMLAttributes } from 'react';
import { kelas } from '@/lib/kelas';

/** Label di atas isian, tebal dan berwarna teks utama: bukan placeholder yang hilang saat mengetik. */
export function Label({ className, ...rest }: LabelHTMLAttributes<HTMLLabelElement>) {
    return <label className={kelas('block text-kecil font-bold text-primary', className)} {...rest} />;
}

const kontrol = 'mt-1 block min-h-12 w-full rounded-tombol border-control pl-4 text-isi text-primary focus:border-brand-600 focus:ring-brand-600';

/**
 * Isian teks v3 (spec v3 bagian 8.4): tinggi 48 px, radius tombol 16 px, huruf isi 16 px
 * (di bawah 16 px Safari iOS memperbesar halaman saat isian difokus), batas control
 * (3,65:1 di atas kartu, WCAG 1.4.11), fokus hijau merek.
 */
export function TextInput({ className, type = 'text', ...rest }: InputHTMLAttributes<HTMLInputElement>) {
    return <input type={type} className={kelas(kontrol, 'pr-4', className)} {...rest} />;
}

export function Select({ className, ...rest }: SelectHTMLAttributes<HTMLSelectElement>) {
    return <select className={kelas(kontrol, 'pr-10', className)} {...rest} />;
}

export function Textarea({ className, ...rest }: TextareaHTMLAttributes<HTMLTextAreaElement>) {
    return <textarea className={kelas(kontrol, 'py-3 pr-4', className)} {...rest} />;
}

/** Galat isian: daftar kalimat, warna danger dengan teks (bukan warna saja). */
export function InputError({ messages, id, className }: { messages?: string | string[] | null; id?: string; className?: string }) {
    const daftar = Array.isArray(messages) ? messages : messages ? [messages] : [];

    if (daftar.length === 0) {
        return null;
    }

    return (
        <ul id={id} className={kelas('space-y-1 text-kecil text-danger-900', className)}>
            {daftar.map((m) => (
                <li key={m}>{m}</li>
            ))}
        </ul>
    );
}

interface FormFieldProps extends InputHTMLAttributes<HTMLInputElement> {
    name: string;
    label: string;
    hint?: string;
    error?: string | string[] | null;
}

/**
 * Isian berlabel dengan petunjuk dan galat yang terikat lewat aria-describedby
 * (WCAG 1.3.1, 3.3.1). aria-invalid hanya dipasang ketika memang ada galat.
 */
export function FormField({ name, label, hint, error, className, ...rest }: FormFieldProps) {
    const penjelas = [hint ? `${name}-hint` : null, error ? `${name}-error` : null].filter(Boolean).join(' ') || undefined;

    return (
        <div className={className}>
            <Label htmlFor={name}>{label}</Label>
            <TextInput id={name} name={name} aria-describedby={penjelas} aria-invalid={error ? true : undefined} {...rest} />
            {hint ? <p id={`${name}-hint`} className="mt-1 text-kecil text-muted">{hint}</p> : null}
            <InputError id={`${name}-error`} messages={error} className="mt-2" />
        </div>
    );
}

/**
 * Kotak centang v3: 28 px, sudut kontrol kecil 10 px, garis 2 px berkontras 3:1, dan isian
 * brand-700 ketika tercentang. Tanda centangnya digelapkan di tema gelap (app.css).
 */
export function Checkbox({ className, ...rest }: InputHTMLAttributes<HTMLInputElement>) {
    return <input type="checkbox" className={kelas('size-7 shrink-0 rounded-kecil border-2 border-control text-brand-700 focus:ring-brand-600', className)} {...rest} />;
}
```

- [ ] **Step 7: Tiga jenis keadaan kosong.** Ganti seluruh isi `resources/js/components/ui/EmptyState.tsx` dengan:

```tsx
import type { ReactNode } from 'react';
import { kelas } from '@/lib/kelas';

interface IsiKosong {
    title: string;
    description?: string;
    children?: ReactNode;
}

/**
 * Tiga jenis keadaan kosong (PRD §104; spec v3 bagian 8.8; antislop R-27). Masing-masing
 * menjawab pertanyaan yang berbeda, jadi jenisnya wajib disebut pemanggil:
 * - pertama: pengguna belum pernah membuat apa pun di sini; tawarkan langkah pertama.
 * - tersaring: datanya ada, saringan yang menyembunyikannya; tawarkan cara melepasnya.
 * - menunggu: datanya belum ada karena pihak lain; sebut siapa yang ditunggu, misalnya
 *   "Menunggu BMKG" atau "Menunggu kurator Memount".
 */
type EmptyStateProps = (IsiKosong & { jenis: 'pertama' | 'tersaring' }) | (IsiKosong & { jenis: 'menunggu'; menunggu: string });

const bingkai: Record<EmptyStateProps['jenis'], string> = {
    pertama: 'border border-subtle bg-surface',
    tersaring: 'border border-subtle bg-surface-sunken',
    menunggu: 'border-2 border-dashed border-control bg-surface',
};

export function EmptyState(props: EmptyStateProps) {
    const { jenis, title, description, children } = props;

    return (
        <div data-kosong={jenis} className={kelas('rounded-lg p-6 text-center sm:p-8', bingkai[jenis])}>
            <p className="text-bagian text-primary">{title}</p>
            {props.jenis === 'menunggu' ? <p className="mx-auto mt-2 max-w-prose text-kecil font-bold text-secondary">{props.menunggu}</p> : null}
            {description ? <p className="mx-auto mt-2 max-w-prose text-isi text-secondary">{description}</p> : null}
            {children ? <div className="mt-4 flex flex-wrap justify-center gap-2">{children}</div> : null}
        </div>
    );
}
```

- [ ] **Step 8: Lencana, avatar, tombol ikon, dan chip.** Buat empat berkas.

`resources/js/components/ui/Lencana.tsx`:

```tsx
import type { ReactNode } from 'react';
import { kelas } from '@/lib/kelas';
import { Icon, type NamaIkon } from './Icon';

export type NadaLencana = 'netral' | 'merek' | 'peringatan' | 'bahaya' | 'komunitas' | 'gelap' | 'cocok' | 'perlu' | 'kurang' | 'belum';

/** Isian dan teks setiap nada, berpasangan dengan rasio yang dihitung ColourContrastTest; garisnya terpisah. */
const nada: Record<NadaLencana, { isi: string; garis: string }> = {
    netral: { isi: 'bg-surface-sunken text-secondary', garis: 'border-control' },
    merek: { isi: 'bg-brand-100 text-brand-900', garis: 'border-brand-300' },
    peringatan: { isi: 'bg-warn-100 text-warn-900', garis: 'border-warn-300' },
    bahaya: { isi: 'bg-danger-100 text-danger-900', garis: 'border-danger-300' },
    komunitas: { isi: 'bg-community-50 text-community-900', garis: 'border-community-500' },
    gelap: { isi: 'bg-surface-inverse text-inverse', garis: 'border-surface-inverse' },
    cocok: { isi: 'bg-fit-cocok text-fit-cocok-teks', garis: 'border-fit-cocok-teks' },
    perlu: { isi: 'bg-fit-perlu text-fit-perlu-teks', garis: 'border-fit-perlu-teks' },
    kurang: { isi: 'bg-fit-kurang text-fit-kurang-teks', garis: 'border-fit-kurang-teks' },
    belum: { isi: 'bg-fit-belum text-fit-belum-teks', garis: 'border-fit-belum-teks' },
};

interface LencanaProps {
    nada?: NadaLencana;
    bentuk?: 'pil' | 'kotak';
    /** Garis putus-putus menandai asal komunitas atau data contoh, bukan hiasan. */
    garis?: 'tanpa' | 'padat' | 'putus';
    ikon?: NamaIkon;
    className?: string;
    children: ReactNode;
}

/**
 * Lencana: keadaan nyata yang pendek (kecocokan, status, sumber, Data contoh), bukan hiasan
 * (antislop R-09). Huruf kalimat tebal 13 px, bukan kapital bertracking lebar (spec v3
 * bagian 8.2). Arti tidak pernah dibawa warna saja: kata di dalamnya selalu menyebut
 * keadaannya. Label panjang membungkus, tidak dipotong.
 */
export function Lencana({ nada: n = 'netral', bentuk = 'kotak', garis = 'tanpa', ikon, className, children }: LencanaProps) {
    return (
        <span
            className={kelas(
                'inline-flex max-w-full items-center gap-1 border text-meta font-bold',
                bentuk === 'pil' ? 'rounded-full px-2.5 py-0.5' : 'rounded-lencana px-2 py-0.5',
                garis === 'tanpa' ? 'border-transparent' : nada[n].garis,
                garis === 'putus' && 'border-dashed',
                nada[n].isi,
                className,
            )}
        >
            {ikon ? <Icon name={ikon} size={16} /> : null}
            <span className="min-w-0 break-words">{children}</span>
        </span>
    );
}
```

`resources/js/components/ui/Avatar.tsx`:

```tsx
import { kelas } from '@/lib/kelas';

/** Inisial dari huruf pertama kata pertama dan kata terakhir: "Budi Setiawan" menjadi "BS". */
export function inisial(nama: string): string {
    const kata = nama.trim().split(/\s+/).filter(Boolean);
    const pertama = kata[0] ?? '';
    const terakhir = kata.length > 1 ? (kata[kata.length - 1] ?? '') : '';

    return (pertama.charAt(0) + terakhir.charAt(0)).toLocaleUpperCase('id-ID');
}

const kelasUkuran = {
    32: 'size-8 text-label',
    44: 'size-11 text-kecil',
    64: 'size-16 text-bagian',
} as const;

interface AvatarProps {
    nama: string;
    foto?: string | null;
    ukuran?: keyof typeof kelasUkuran;
    /** Bawaan true: avatar di samping nama yang tertulis adalah pengulangan bagi pembaca layar. */
    hiasan?: boolean;
    className?: string;
}

/**
 * Avatar: foto unggahan pengguna atau inisial (spec v3 bagian 8.7). Data contoh memakai
 * inisial, tidak pernah wajah stok (antislop R-23).
 */
export function Avatar({ nama, foto, ukuran = 44, hiasan = true, className }: AvatarProps) {
    const dasar = kelas('inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full bg-brand-100 font-bold text-brand-900', kelasUkuran[ukuran], className);

    if (foto) {
        return <img src={foto} alt={hiasan ? '' : nama} width={ukuran} height={ukuran} className={kelas(dasar, 'object-cover')} />;
    }

    return hiasan ? (
        <span aria-hidden="true" className={dasar}>
            {inisial(nama)}
        </span>
    ) : (
        <span role="img" aria-label={nama} className={dasar}>
            {inisial(nama)}
        </span>
    );
}
```

`resources/js/components/ui/TombolIkon.tsx`:

```tsx
import type { ButtonHTMLAttributes } from 'react';
import { kelas } from '@/lib/kelas';
import { fokusTerlihat, umpanTekan } from './gaya';
import { Icon, type NamaIkon } from './Icon';

interface TombolIkonProps extends Omit<ButtonHTMLAttributes<HTMLButtonElement>, 'children' | 'aria-label'> {
    ikon: NamaIkon;
    /** Nama aksesibel, wajib: tombol ikon tanpa nama tidak terbaca pembaca layar (WCAG 4.1.2). */
    label: string;
    varian?: 'permukaan' | 'sumur' | 'polos';
    bentuk?: 'bulat' | 'kotak';
    /** 48 px untuk kendali utama; bawaan 44 px. */
    besar?: boolean;
}

const gaya = {
    permukaan: 'border border-subtle bg-surface text-primary hover:bg-surface-sunken',
    sumur: 'bg-surface-sunken text-primary hover:bg-brand-50',
    polos: 'text-secondary hover:bg-surface-sunken hover:text-primary',
};

/**
 * Tombol berisi satu ikon: 44 px (48 px bila besar), namanya teks sr-only dari prop label
 * yang wajib, bukan aria-label yang mudah terlupa dan tidak ikut diterjemahkan.
 */
export function TombolIkon({ ikon, label, varian = 'permukaan', bentuk = 'bulat', besar = false, type = 'button', className, ...rest }: TombolIkonProps) {
    return (
        <button
            type={type}
            className={kelas(
                'inline-flex shrink-0 items-center justify-center disabled:cursor-not-allowed disabled:opacity-50',
                besar ? 'h-12 w-12' : 'h-11 w-11',
                bentuk === 'bulat' ? 'rounded-full' : 'rounded-control',
                gaya[varian],
                fokusTerlihat,
                umpanTekan,
                className,
            )}
            {...rest}
        >
            <Icon name={ikon} size={besar ? 24 : 22} />
            <span className="sr-only">{label}</span>
        </button>
    );
}
```

`resources/js/components/ui/Chip.tsx`:

```tsx
import type { ReactNode } from 'react';
import { kelas } from '@/lib/kelas';
import { fokusKelompok, umpanTekanKelompok } from './gaya';
import { Icon } from './Icon';

const pembungkus = 'group inline-flex min-h-11 max-w-full items-center rounded-full focus:outline-none';
const bentuk = 'inline-flex min-h-9 max-w-full items-center gap-1.5 rounded-full border px-3.5 py-1 text-left text-kecil';

interface ChipPilihanProps {
    dipilih: boolean;
    onUbah: (dipilih: boolean) => void;
    className?: string;
    children: ReactNode;
}

/**
 * Chip pilihan: bentuk 36 px di dalam target sentuh 44 px (spec v3 bagian 8.4, W1-K5).
 * Terpilih ditandai empat cara, bukan warna saja: centang, tebal huruf, garis merek, dan
 * aria-pressed (WCAG 1.4.1). Label panjang membungkus, tidak dipotong.
 */
export function ChipPilihan({ dipilih, onUbah, className, children }: ChipPilihanProps) {
    return (
        <button type="button" aria-pressed={dipilih} onClick={() => onUbah(!dipilih)} className={kelas(pembungkus, className)}>
            <span
                className={kelas(
                    bentuk,
                    fokusKelompok,
                    umpanTekanKelompok,
                    dipilih ? 'border-brand-700 bg-brand-100 font-bold text-brand-900' : 'border-control bg-surface font-semibold text-primary group-hover:bg-surface-sunken',
                )}
            >
                {dipilih ? <Icon name="check" size={16} /> : null}
                <span className="min-w-0 break-words">{children}</span>
            </span>
        </button>
    );
}

interface ChipLepasProps {
    onLepas: () => void;
    className?: string;
    children: ReactNode;
}

/** Saringan aktif yang dapat dilepas; pembaca layar mendengar "Lepas saringan" lalu labelnya. */
export function ChipLepas({ onLepas, className, children }: ChipLepasProps) {
    return (
        <button type="button" onClick={onLepas} className={kelas(pembungkus, className)}>
            <span className={kelas(bentuk, fokusKelompok, umpanTekanKelompok, 'border-brand-700 bg-brand-100 font-bold text-brand-900')}>
                <span className="sr-only">Lepas saringan </span>
                <span className="min-w-0 break-words">{children}</span>
                <Icon name="x" size={16} />
            </span>
        </button>
    );
}
```

- [ ] **Step 9: Setiap keadaan kosong menyebut jenisnya.** Sisipkan atribut sebagai baris pertama sesudah `<EmptyState`:
- `resources/js/pages/Admin/Health.tsx:169`: `jenis="menunggu"`, dan sesudah baris `title=...` tambahkan `menunggu="Menunggu pengembang mendaftarkan tugas terjadwal di kode aplikasi"`.
- `resources/js/pages/Admin/Permits.tsx:228`, `resources/js/pages/Community/HikingHistory.tsx:52`, `resources/js/pages/Track/Index.tsx:43`, `resources/js/pages/Trails/Saved.tsx:57`: `jenis="pertama"`.

Di `resources/js/pages/Trails/Index.tsx`, ganti cabang keadaan kosong daftar jalur:

```tsx
                        {trails.data.length === 0 ? (
                            <EmptyState title="Tidak ada jalur yang cocok" description="Saringan yang sedang aktif tidak menemukan jalur. Melonggarkan tingkat teknis atau mengosongkan wilayah biasanya menolong lebih cepat daripada mengubah kata pencarian.">
                                {adaSaringan ? <Button variant="secondary" size="sm" onClick={() => setIsian(kosong)}>Hapus semua saringan</Button> : null}
                            </EmptyState>
                        ) : (
```

dengan dua cabang: saringan yang kosong menawarkan pelepasnya, dan Jelajah tanpa saringan yang kosong menyebut siapa yang
ditunggu. Syarat datanya disalin dari `Trail::scopeDataComplete()` (`app/Models/Trail.php`).

```tsx
                        {trails.data.length === 0 && adaSaringan ? (
                            <EmptyState
                                jenis="tersaring"
                                title="Tidak ada jalur dengan saringan ini"
                                description="Saringan yang sedang aktif tidak menemukan jalur. Melonggarkan tingkat teknis atau mengosongkan wilayah biasanya menolong lebih cepat daripada mengubah kata pencarian."
                            >
                                <Button variant="secondary" size="sm" onClick={() => setIsian(kosong)}>Hapus semua saringan</Button>
                            </EmptyState>
                        ) : trails.data.length === 0 ? (
                            <EmptyState
                                jenis="menunggu"
                                title="Belum ada jalur yang terbit"
                                menunggu="Menunggu kurator Memount menerbitkan jalur yang datanya lengkap"
                                description="Jalur tampil di sini setelah datanya lengkap: sumber, jarak, tanjakan, durasi, pos, status resmi, dan garis rute di peta."
                            />
                        ) : (
```

- [ ] **Step 10: Catat rumah primitif di peta arsitektur.** Di `docs/ARCHITECTURE.md`, ganti tiga baris tabel.

```markdown
| Warna, radius, gaya tombol | `resources/css/app.css` (token) dan `resources/views/components/ui/` |
```

menjadi:

```markdown
| Warna, radius, huruf, gaya tombol | Token di `resources/css/app.css`, pemetaan kelas di `tailwind.config.js`, primitif React di `resources/js/components/ui/`, komponen Blade halaman publik di `resources/views/components/ui/` |
```

```markdown
| `apps/frontend/src` (components, core, features, views) | Komponen fitur di `resources/js/components`, inti di `resources/js/lib`, halaman di `resources/js/pages`; primitif desain menyusul di W1 |
```

menjadi:

```markdown
| `apps/frontend/src` (components, core, features, views) | Komponen fitur di `resources/js/components`, inti di `resources/js/lib`, halaman di `resources/js/pages`; primitif desain di `resources/js/components/ui/` (komponen domain yang masih di sana dipindah W3) |
```

```markdown
| `packages/ui-design-system` | Token `resources/css/app.css` dan primitif desain (W1) |
```

menjadi:

```markdown
| `packages/ui-design-system` | Token `resources/css/app.css`, pemetaan `tailwind.config.js`, dan primitif `resources/js/components/ui/` (W1) |
```

- [ ] **Step 11: Jalankan test tugas ini dan tetangganya sampai lulus.**

Run: `php artisan test --compact --filter='DesignPrimitivesTest|LongInputLayoutTest|DesignSystemTest|TapTargetTest|ButtonFeedbackTest|ColourContrastTest|ReadingMeasureTest|EmptyStateCoverageTest|KeyboardAndScreenReaderTest|AccountPagesSourceTest|SavedRoutesTest|InertiaLayoutTest|SurfaceSystemTest|IconSystemTest|TeamDocsIndexTest|PrdReferenceIntegrityTest|AccessibilityTest'`
Expected: PASS, 276 test.

- [ ] **Step 12: Gerbang tugas.**

Run: `vendor/bin/pint --dirty --format agent`
Expected: `{"tool":"pint","result":"passed"}` atau berkas dirapikan tanpa perubahan makna.

Run: `npx tsc --noEmit`
Expected: keluar tanpa keluaran galat. Proyek memakai `noUncheckedIndexedAccess`, jadi akses larik di `inisial()` sudah
memakai `?? ''`; jangan melepasnya.

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal. Pada simulasi rencana: 1.462 test, 1.461 lulus, 1 dilewati.

- [ ] **Step 13: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 6: Kontrol berkeadaan (KontrolSegmen, Sakelar, KotakCentang, Stepper)

Induk W1 butir 4 bagian kedua: kontrol yang menyimpan keadaan. Semuanya memakai elemen asli (radio, button berperan
switch, checkbox, output) supaya papan ketik dan pembaca layar bekerja tanpa ARIA buatan.

**Files:**
- Create: `resources/js/components/ui/KontrolSegmen.tsx`, `resources/js/components/ui/Sakelar.tsx`, `resources/js/components/ui/KotakCentang.tsx`, `resources/js/components/ui/Stepper.tsx`
- Test: `tests/Feature/DesignPrimitivesTest.php`, `tests/Feature/LongInputLayoutTest.php`

**Interfaces:**
- Consumes: `fokusDiDalam`, `fokusKelompok` (Tugas 5), `Icon` dan `NamaIkon`, `Checkbox` dari `Field.tsx`, `TombolIkon`.
- Produces, dipakai Tugas 8 dan W3 sampai W7:
  - `KontrolSegmen<T extends string>({ nama, label, pilihan: ReadonlyArray<PilihanSegmen<T>>, nilai: T, onUbah(nilai: T), className? })` dan `PilihanSegmen<T> { nilai: T; label: string; keterangan?: string; ikon?: NamaIkon }`.
  - `Sakelar({ judul, keterangan?, nyala, onUbah(nyala), disabled?, className? })`.
  - `KotakCentang({ label, keterangan?, lencana?: ReactNode, ...atribut input kecuali type })`; `checked`, `onChange`, `id`, dan `name` diteruskan ke kotaknya.
  - `Stepper({ label, keterangan?, nilai, min, max, onUbah(nilai), className? })`.

- [ ] **Step 1: Perluas test primitif.** Di `tests/Feature/DesignPrimitivesTest.php`, ganti akhir penyedia `primitifInteraktif()`:

```php
            'tombol' => ['components/ui/Button.tsx'],
            'tombol ikon' => ['components/ui/TombolIkon.tsx'],
            'chip' => ['components/ui/Chip.tsx'],
        ];
```

dengan:

```php
            'tombol' => ['components/ui/Button.tsx'],
            'tombol ikon' => ['components/ui/TombolIkon.tsx'],
            'chip' => ['components/ui/Chip.tsx'],
            'kontrol segmen' => ['components/ui/KontrolSegmen.tsx'],
            'sakelar' => ['components/ui/Sakelar.tsx'],
            'kotak centang' => ['components/ui/KotakCentang.tsx'],
            'stepper' => ['components/ui/Stepper.tsx'],
        ];
```

lalu tambahkan empat metode berikut sesudah `test_the_button_follows_the_v3_shape_weight_and_press`, sebelum metode
privat `sumber()`:

```php

    /**
     * Kontrol segmen adalah grup radio asli: panah papan ketik berpindah pilihan dan pembaca
     * layar menyebut posisinya tanpa ARIA buatan. Terpilih ditandai permukaan, garis, dan
     * tebal huruf.
     */
    public function test_the_segmented_control_is_a_native_radio_group(): void
    {
        $segmen = $this->sumber('components/ui/KontrolSegmen.tsx');

        $this->assertStringContainsString('<fieldset className={className}>', $segmen);
        $this->assertStringContainsString('<legend className="sr-only">{label}</legend>', $segmen);
        $this->assertStringContainsString('type="radio" name={nama} value={p.nilai} checked={nilai === p.nilai}', $segmen);
        $this->assertStringContainsString('has-[:checked]:border-control has-[:checked]:bg-surface has-[:checked]:font-bold', $segmen);
        $this->assertStringNotContainsString('role="tab', $segmen);
    }

    /**
     * Sakelar menyebut keadaannya (aria-checked) dan dinamai judulnya; kenop berpindah
     * posisi dan jalurnya berganti isian, jadi keadaan tidak dibawa warna saja.
     */
    public function test_the_switch_announces_its_state(): void
    {
        $sakelar = $this->sumber('components/ui/Sakelar.tsx');

        $this->assertStringContainsString('role="switch"', $sakelar);
        $this->assertStringContainsString('aria-checked={nyala}', $sakelar);
        $this->assertStringContainsString('aria-labelledby={`${id}-judul`}', $sakelar);
        $this->assertStringContainsString("nyala ? 'border-brand-700 bg-brand-700' : 'border-control bg-control'", $sakelar);
        $this->assertStringContainsString("nyala ? 'translate-x-[1.375rem]' : 'translate-x-0.5'", $sakelar);
    }

    /**
     * Seluruh baris kotak centang adalah labelnya: menyentuh teks ikut mencentang. Kotaknya
     * 28 px bergaris control 2 px (3,65:1 di atas kartu).
     */
    public function test_the_checkbox_row_ties_its_label_to_the_box(): void
    {
        $baris = $this->sumber('components/ui/KotakCentang.tsx');

        $this->assertStringContainsString('<label htmlFor={idKotak}', $baris);
        $this->assertStringContainsString('<Checkbox id={idKotak}', $baris);
        $this->assertMatchesRegularExpression('/type="checkbox" className=\{kelas\(\'[^\']*\bsize-7\b[^\']*\bborder-control\b/', $this->sumber('components/ui/Field.tsx'));
    }

    /**
     * Kedua tombol stepper bernama menurut labelnya, nonaktif di batas rentang, dan nilai
     * yang berubah diumumkan pembaca layar.
     */
    public function test_the_stepper_names_both_buttons_and_announces_the_value(): void
    {
        $stepper = $this->sumber('components/ui/Stepper.tsx');

        $this->assertStringContainsString('role="group" aria-labelledby={`${id}-label`}', $stepper);
        $this->assertStringContainsString('label={`Kurangi ${nama}`}', $stepper);
        $this->assertStringContainsString('label={`Tambah ${nama}`}', $stepper);
        $this->assertStringContainsString('disabled={nilai <= min}', $stepper);
        $this->assertStringContainsString('disabled={nilai >= max}', $stepper);
        $this->assertStringContainsString('<output aria-live="polite"', $stepper);
    }
```

Di `tests/Feature/LongInputLayoutTest.php`, ganti akhir penyedia `primitifBerlabel()`:

```php
            'tombol' => ['components/ui/Button.tsx'],
            'chip' => ['components/ui/Chip.tsx'],
            'lencana' => ['components/ui/Lencana.tsx'],
            'keadaan kosong' => ['components/ui/EmptyState.tsx'],
        ];
```

dengan:

```php
            'tombol' => ['components/ui/Button.tsx'],
            'chip' => ['components/ui/Chip.tsx'],
            'lencana' => ['components/ui/Lencana.tsx'],
            'keadaan kosong' => ['components/ui/EmptyState.tsx'],
            'kontrol segmen' => ['components/ui/KontrolSegmen.tsx'],
            'sakelar' => ['components/ui/Sakelar.tsx'],
            'kotak centang' => ['components/ui/KotakCentang.tsx'],
            'stepper' => ['components/ui/Stepper.tsx'],
        ];
```

- [ ] **Step 2: Jalankan dan pastikan gagal.**

Run: `php artisan test --compact --filter='DesignPrimitivesTest|LongInputLayoutTest'`
Expected: FAIL. Pada simulasi rencana: 36 test, 20 lulus, 16 error (keempat berkas belum ada).

- [ ] **Step 3: Kontrol segmen.** Buat `resources/js/components/ui/KontrolSegmen.tsx`:

```tsx
import { kelas } from '@/lib/kelas';
import { fokusDiDalam } from './gaya';
import { Icon, type NamaIkon } from './Icon';

export interface PilihanSegmen<T extends string> {
    nilai: T;
    label: string;
    keterangan?: string;
    ikon?: NamaIkon;
}

interface KontrolSegmenProps<T extends string> {
    /** Nama grup radio, unik di satu halaman. */
    nama: string;
    /** Nama grup bagi pembaca layar (legend). */
    label: string;
    pilihan: ReadonlyArray<PilihanSegmen<T>>;
    nilai: T;
    onUbah: (nilai: T) => void;
    className?: string;
}

/**
 * Kontrol segmen: grup radio asli di dalam fieldset, bukan tombol berperan tab. Tombol
 * panah berpindah pilihan tanpa kode tambahan dan pembaca layar menyebut posisinya. Segmen
 * terpilih ditandai permukaan terangkat, garis, dan tebal huruf, bukan warna saja; label
 * dan keterangan panjang membungkus di dalam segmennya.
 */
export function KontrolSegmen<T extends string>({ nama, label, pilihan, nilai, onUbah, className }: KontrolSegmenProps<T>) {
    return (
        <fieldset className={className}>
            <legend className="sr-only">{label}</legend>
            <div className="flex gap-1 rounded-tombol bg-surface-sunken p-1">
                {pilihan.map((p) => (
                    <label
                        key={p.nilai}
                        className={kelas(
                            'flex min-h-11 min-w-0 flex-1 cursor-pointer flex-col items-center justify-center rounded-control border border-transparent px-3 py-1.5 text-center text-kecil font-semibold text-secondary',
                            'has-[:checked]:border-control has-[:checked]:bg-surface has-[:checked]:font-bold has-[:checked]:text-primary',
                            fokusDiDalam,
                        )}
                    >
                        <input type="radio" name={nama} value={p.nilai} checked={nilai === p.nilai} onChange={() => onUbah(p.nilai)} className="sr-only" />
                        <span className="inline-flex max-w-full items-center gap-1.5">
                            {p.ikon ? <Icon name={p.ikon} size={20} /> : null}
                            <span className="min-w-0 break-words">{p.label}</span>
                        </span>
                        {p.keterangan ? <span className="text-meta font-medium text-muted">{p.keterangan}</span> : null}
                    </label>
                ))}
            </div>
        </fieldset>
    );
}
```

- [ ] **Step 4: Sakelar.** Buat `resources/js/components/ui/Sakelar.tsx`. Kenop dan jalurnya memakai `duration-sentuh`
(120 ms): pergeseran kenop adalah umpan balik tekan sakelar itu sendiri, satu-satunya jenis gerak kontrol di daftar
tertutup spec v3 bagian 8.6.

```tsx
import { useId } from 'react';
import { kelas } from '@/lib/kelas';
import { fokusKelompok } from './gaya';

interface SakelarProps {
    judul: string;
    keterangan?: string;
    nyala: boolean;
    onUbah: (nyala: boolean) => void;
    disabled?: boolean;
    className?: string;
}

/**
 * Sakelar: tombol berperan switch yang menyebut keadaannya (aria-checked) dan dinamai
 * judulnya. Keadaan dibawa posisi kenop dan isian jalurnya, tidak warna saja; target sentuh
 * 44 px walau jalurnya 28 px. Geser kenop 120 ms adalah umpan balik tekan sakelar itu
 * sendiri (daftar gerak spec v3 bagian 8.6). Judul dan keterangan membungkus bila panjang.
 */
export function Sakelar({ judul, keterangan, nyala, onUbah, disabled = false, className }: SakelarProps) {
    const id = useId();

    return (
        <div className={kelas('flex items-start justify-between gap-4', className)}>
            <div className="min-w-0 py-2">
                <p id={`${id}-judul`} className="text-isi font-semibold text-primary">
                    {judul}
                </p>
                {keterangan ? (
                    <p id={`${id}-keterangan`} className="mt-0.5 text-kecil text-secondary">
                        {keterangan}
                    </p>
                ) : null}
            </div>
            <button
                type="button"
                role="switch"
                aria-checked={nyala}
                aria-labelledby={`${id}-judul`}
                aria-describedby={keterangan ? `${id}-keterangan` : undefined}
                disabled={disabled}
                onClick={() => onUbah(!nyala)}
                className="group inline-flex h-11 w-14 shrink-0 items-center justify-center rounded-full focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
            >
                <span
                    aria-hidden="true"
                    className={kelas(
                        'relative inline-flex h-7 w-12 items-center rounded-full border-2 transition-colors duration-sentuh ease-gerak',
                        fokusKelompok,
                        nyala ? 'border-brand-700 bg-brand-700' : 'border-control bg-control',
                    )}
                >
                    <span className={kelas('inline-block size-5 rounded-full border border-subtle bg-surface transition-transform duration-sentuh ease-gerak', nyala ? 'translate-x-[1.375rem]' : 'translate-x-0.5')} />
                </span>
            </button>
        </div>
    );
}
```

- [ ] **Step 5: Baris kotak centang.** Buat `resources/js/components/ui/KotakCentang.tsx`:

```tsx
import { useId, type InputHTMLAttributes, type ReactNode } from 'react';
import { kelas } from '@/lib/kelas';
import { Checkbox } from './Field';

interface KotakCentangProps extends Omit<InputHTMLAttributes<HTMLInputElement>, 'type'> {
    label: string;
    keterangan?: string;
    /** Lencana keadaan di samping label, misalnya "Kritis". */
    lencana?: ReactNode;
}

/**
 * Baris kotak centang: seluruh baris adalah label yang terikat ke kotaknya, jadi menyentuh
 * teksnya ikut mencentang dan targetnya 44 px walau kotaknya 28 px. Keterangan ikut
 * dibacakan lewat aria-describedby.
 */
export function KotakCentang({ label, keterangan, lencana, id, className, ...rest }: KotakCentangProps) {
    const idBawaan = useId();
    const idKotak = id ?? idBawaan;

    return (
        <label htmlFor={idKotak} className={kelas('flex min-h-11 cursor-pointer items-start gap-3 py-2', className)}>
            <Checkbox id={idKotak} aria-describedby={keterangan ? `${idKotak}-keterangan` : undefined} className="mt-0.5" {...rest} />
            <span className="min-w-0">
                <span className="flex flex-wrap items-center gap-2">
                    <span className="min-w-0 break-words text-isi font-semibold text-primary">{label}</span>
                    {lencana}
                </span>
                {keterangan ? (
                    <span id={`${idKotak}-keterangan`} className="mt-0.5 block text-kecil text-secondary">
                        {keterangan}
                    </span>
                ) : null}
            </span>
        </label>
    );
}
```

- [ ] **Step 6: Stepper.** Buat `resources/js/components/ui/Stepper.tsx`:

```tsx
import { useId } from 'react';
import { kelas } from '@/lib/kelas';
import { TombolIkon } from './TombolIkon';

interface StepperProps {
    label: string;
    keterangan?: string;
    nilai: number;
    min: number;
    max: number;
    onUbah: (nilai: number) => void;
    className?: string;
}

/**
 * Stepper angka kecil (jumlah peserta, jumlah hari): dua tombol ikon bernama dan nilai yang
 * diumumkan pembaca layar ketika berubah. Tombol nonaktif di batasnya, jadi nilainya tidak
 * pernah keluar rentang.
 */
export function Stepper({ label, keterangan, nilai, min, max, onUbah, className }: StepperProps) {
    const id = useId();
    const nama = label.toLocaleLowerCase('id-ID');

    return (
        <div role="group" aria-labelledby={`${id}-label`} className={kelas('flex items-center justify-between gap-4', className)}>
            <div className="min-w-0">
                <p id={`${id}-label`} className="text-isi font-semibold text-primary">
                    {label}
                </p>
                {keterangan ? <p className="mt-0.5 text-kecil text-secondary">{keterangan}</p> : null}
            </div>
            <div className="flex shrink-0 items-center gap-2">
                <TombolIkon ikon="minus" label={`Kurangi ${nama}`} varian="sumur" bentuk="kotak" disabled={nilai <= min} onClick={() => onUbah(Math.max(min, nilai - 1))} />
                <output aria-live="polite" className="min-w-8 text-center text-bagian font-extrabold text-primary">
                    {nilai}
                </output>
                <TombolIkon ikon="plus" label={`Tambah ${nama}`} varian="sumur" bentuk="kotak" disabled={nilai >= max} onClick={() => onUbah(Math.min(max, nilai + 1))} />
            </div>
        </div>
    );
}
```

- [ ] **Step 7: Jalankan test tugas ini dan tetangganya sampai lulus.**

Run: `php artisan test --compact --filter='DesignPrimitivesTest|LongInputLayoutTest|TapTargetTest|DesignSystemTest|KeyboardAndScreenReaderTest|AccountPagesSourceTest|ColourContrastTest'`
Expected: PASS, 201 test.

- [ ] **Step 8: Gerbang tugas.**

Run: `vendor/bin/pint --dirty --format agent`
Expected: `{"tool":"pint","result":"passed"}` atau berkas dirapikan tanpa perubahan makna.

Run: `npx tsc --noEmit`
Expected: keluar tanpa keluaran galat.

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal. Pada simulasi rencana: 1.478 test, 1.477 lulus, 1 dilewati.

- [ ] **Step 9: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 7: Lapisan melayang (LembarBawah dan Dialog, toast urungkan, BarAksi, BilahTab)

Induk W1 butir 4 bagian ketiga: lapisan yang melayang di atas halaman. Lembar dan dialog memakai `<dialog>` asli
(W1-K6); toast urungkan menunggu pembacanya (W1-K7); gerak hanya dari daftar tertutup spec v3 bagian 8.6 lewat token.
`BilahTab` baru dipakai pameran di W1; W2 memasangnya di `AppLayout`.

**Files:**
- Create: `resources/js/components/ui/Lembar.tsx`, `resources/js/components/ui/ToastUrungkan.tsx`, `resources/js/components/ui/BarAksi.tsx`, `resources/js/components/ui/BilahTab.tsx`
- Modify: `resources/css/app.css` (satu blok baru sebelum komentar "Kurangi gerak dari pengaturan aplikasi")
- Test: `tests/Feature/DesignPrimitivesTest.php`, `tests/Feature/MotionTest.php`, `tests/Feature/LongInputLayoutTest.php`

**Interfaces:**
- Consumes: `TombolIkon`, `Button`, `Icon`, `Tautan`, `fokusTerlihat`; token `--dur-sedang`, `--dur-cepat`, `--ease-gerak`, `--ease-pudar`, `--scrim`; variabel `--nav-bawah` dari `AppLayout`.
- Produces, dipakai Tugas 8 dan W2 sampai W7:
  - `LembarBawah({ terbuka, onTutup, judul, keterangan?, aksi?: ReactNode, children })` dan `Dialog({ ...sama })`. Pemanggil memegang `terbuka`; `onTutup` dipanggil untuk setiap cara menutup (Escape, tirai, tombol Tutup, `terbuka=false`).
  - `useToastUrungkan(): { toast: DataToast | null, tampilkan(pesan, onUrungkan), tutup() }`, `WadahToast({ toast, onTutup, durasiMs? = 8000 })`, `DataToast { id; pesan; onUrungkan }`.
  - `BarAksi({ label, ringkasan?: ReactNode, children })`.
  - `BilahTab({ item: ReadonlyArray<ItemTab>, label? = 'Navigasi utama', mengambang? = true })`, `ItemTab { label; href; ikon: NamaIkon; aktif }`.
  - Skala z: bar aksi dan bilah tab `z-30`, toast `z-40`, lembar dan dialog di top layer peramban.

- [ ] **Step 1: Tulis test yang gagal.** Di `tests/Feature/DesignPrimitivesTest.php`, ganti akhir penyedia
`primitifInteraktif()`:

```php
            'tombol' => ['components/ui/Button.tsx'],
            'tombol ikon' => ['components/ui/TombolIkon.tsx'],
            'chip' => ['components/ui/Chip.tsx'],
            'kontrol segmen' => ['components/ui/KontrolSegmen.tsx'],
            'sakelar' => ['components/ui/Sakelar.tsx'],
            'kotak centang' => ['components/ui/KotakCentang.tsx'],
            'stepper' => ['components/ui/Stepper.tsx'],
        ];
```

dengan:

```php
            'tombol' => ['components/ui/Button.tsx'],
            'tombol ikon' => ['components/ui/TombolIkon.tsx'],
            'chip' => ['components/ui/Chip.tsx'],
            'kontrol segmen' => ['components/ui/KontrolSegmen.tsx'],
            'sakelar' => ['components/ui/Sakelar.tsx'],
            'kotak centang' => ['components/ui/KotakCentang.tsx'],
            'stepper' => ['components/ui/Stepper.tsx'],
            'lembar dan dialog' => ['components/ui/Lembar.tsx'],
            'toast urungkan' => ['components/ui/ToastUrungkan.tsx'],
            'bilah tab' => ['components/ui/BilahTab.tsx'],
        ];
```

lalu tambahkan empat metode berikut sesudah `test_the_stepper_names_both_buttons_and_announces_the_value` (Tugas 6):

```php

    /**
     * Lembar bawah dan dialog memakai <dialog> asli dengan showModal(): fokus terkurung,
     * latar inert, dan Escape menutup tanpa kode papan ketik buatan. Setiap cara menutup
     * memicu event close, dan di sanalah fokus dikembalikan ke pemicunya, atau ke #konten
     * bila pemicunya sudah hilang dari DOM (Review Focus W1).
     */
    public function test_the_sheet_and_dialog_use_the_native_modal_and_return_focus(): void
    {
        $lembar = $this->sumber('components/ui/Lembar.tsx');

        $this->assertStringContainsString('dialog.showModal();', $lembar);
        $this->assertStringContainsString("dialog.addEventListener('close', saatTutup);", $lembar);
        $this->assertStringContainsString("const tujuan = pemicu.current?.isConnected ? pemicu.current : document.getElementById('konten');", $lembar);
        $this->assertStringContainsString('tujuan?.focus();', $lembar);
        $this->assertSame(2, substr_count($lembar, '<dialog'), 'Lembar bawah dan dialog tengah.');
        $this->assertSame(2, substr_count($lembar, 'aria-labelledby={`${id}-judul`}'));
        $this->assertStringContainsString('<TombolIkon ikon="x" label="Tutup"', $lembar);
        $this->assertStringContainsString('if (e.target === e.currentTarget) {', $lembar);
        $this->assertStringNotContainsString("'Escape'", $lembar, 'Escape ditangani dialog asli, bukan kode sendiri.');
    }

    /**
     * Toast urungkan menunggu pembacanya (WCAG 2.2.1): paling sedikit 8 detik, berhenti
     * selama disentuh penunjuk atau difokus, dan dapat ditutup dengan tombol bernama.
     */
    public function test_the_undo_toast_waits_for_the_reader(): void
    {
        $toast = $this->sumber('components/ui/ToastUrungkan.tsx');

        $this->assertSame(1, preg_match('/durasiMs = (\d+)/', $toast, $durasi));
        $this->assertGreaterThanOrEqual(8000, (int) $durasi[1]);

        foreach (['onMouseEnter={() => setJeda(true)}', 'onMouseLeave={() => setJeda(false)}', 'onFocus={() => setJeda(true)}', 'onBlur={() => setJeda(false)}'] as $jeda) {
            $this->assertStringContainsString($jeda, $toast);
        }

        $this->assertMatchesRegularExpression('/>\s*Urungkan\s*<\/button>/', $toast);
        $this->assertStringContainsString('<span className="sr-only">Tutup pemberitahuan</span>', $toast);
        $this->assertStringContainsString('<div aria-live="polite" aria-atomic="true"', $toast);
    }

    /**
     * Bar aksi melayang pada satu elevasi (shadow-overlay) dengan material chrome, di atas
     * navigasi bawah, dengan pengganjal supaya isi terakhir halaman tidak tertutup.
     */
    public function test_the_action_bar_floats_on_the_single_elevation(): void
    {
        $bar = $this->sumber('components/ui/BarAksi.tsx');

        $this->assertStringContainsString('<div aria-hidden="true" className="h-32" />', $bar);
        $this->assertStringContainsString('<section aria-label={label}', $bar);
        $this->assertStringContainsString('bottom-[calc(var(--nav-bawah,env(safe-area-inset-bottom))+0.75rem)] z-30', $bar);
        $this->assertMatchesRegularExpression('/className="chrome [^"]*\bshadow-overlay\b/', $bar);
    }

    /**
     * Tab aktif ditandai empat cara: ikon varian isi, isian pil, tebal huruf, dan
     * aria-current (WCAG 1.4.1); labelnya tidak pernah dipotong.
     */
    public function test_the_tab_bar_marks_the_active_tab_by_shape_weight_and_aria(): void
    {
        $bilah = $this->sumber('components/ui/BilahTab.tsx');

        $this->assertStringContainsString("aria-current={t.aktif ? 'page' : undefined}", $bilah);
        $this->assertStringContainsString('<Icon name={t.ikon} size={22} isi={t.aktif} />', $bilah);
        $this->assertStringContainsString("t.aktif ? 'bg-brand-100 font-bold text-brand-900' : 'font-medium text-secondary", $bilah);
        $this->assertStringContainsString('text-label', $bilah);
    }
```

Di `tests/Feature/LongInputLayoutTest.php`, ganti akhir penyedia `primitifBerlabel()`:

```php
            'tombol' => ['components/ui/Button.tsx'],
            'chip' => ['components/ui/Chip.tsx'],
            'lencana' => ['components/ui/Lencana.tsx'],
            'keadaan kosong' => ['components/ui/EmptyState.tsx'],
            'kontrol segmen' => ['components/ui/KontrolSegmen.tsx'],
            'sakelar' => ['components/ui/Sakelar.tsx'],
            'kotak centang' => ['components/ui/KotakCentang.tsx'],
            'stepper' => ['components/ui/Stepper.tsx'],
        ];
```

dengan:

```php
            'tombol' => ['components/ui/Button.tsx'],
            'chip' => ['components/ui/Chip.tsx'],
            'lencana' => ['components/ui/Lencana.tsx'],
            'keadaan kosong' => ['components/ui/EmptyState.tsx'],
            'kontrol segmen' => ['components/ui/KontrolSegmen.tsx'],
            'sakelar' => ['components/ui/Sakelar.tsx'],
            'kotak centang' => ['components/ui/KotakCentang.tsx'],
            'stepper' => ['components/ui/Stepper.tsx'],
            'lembar dan dialog' => ['components/ui/Lembar.tsx'],
            'toast urungkan' => ['components/ui/ToastUrungkan.tsx'],
            'bar aksi' => ['components/ui/BarAksi.tsx'],
            'bilah tab' => ['components/ui/BilahTab.tsx'],
        ];
```

Di `tests/Feature/MotionTest.php`, tambahkan metode berikut sesudah `test_nothing_starts_invisible_waiting_for_a_script`,
sebelum `}` penutup kelas:

```php

    /**
     * Gerak lapisan melayang hanya dari daftar tertutup spec v3 bagian 8.6 dan hanya lewat
     * token: lembar naik 280 ms dengan kurva gerak, dialog tengah dan toast 200 ms. Durasi
     * yang ditulis sebagai angka lolos dari satu sumber waktu gerak aplikasi.
     */
    public function test_the_sheet_and_toast_motion_use_the_duration_tokens(): void
    {
        $css = $this->css();

        $this->assertStringContainsString('animation: lembar-naik var(--dur-sedang) var(--ease-gerak);', $css);
        $this->assertStringContainsString('animation: pudar-masuk var(--dur-cepat) var(--ease-pudar);', $css);
        $this->assertStringContainsString('animation: toast-naik var(--dur-cepat) var(--ease-gerak);', $css);
        $this->assertDoesNotMatchRegularExpression('/\banimation:[^;]*\d+m?s\b/', $css, 'Durasi animasi ditulis sebagai angka, bukan token.');
    }
```

- [ ] **Step 2: Jalankan dan pastikan gagal.**

Run: `php artisan test --compact --filter='DesignPrimitivesTest|LongInputLayoutTest|MotionTest'`
Expected: FAIL. Pada simulasi rencana: 54 test, 39 lulus, 1 gagal (`MotionTest`), 14 error (empat berkas belum ada).

- [ ] **Step 3: Lembar bawah dan dialog.** Buat `resources/js/components/ui/Lembar.tsx`:

```tsx
import { useEffect, useId, useRef, type MouseEvent, type ReactNode } from 'react';
import { Button } from './Button';
import { TombolIkon } from './TombolIkon';

/**
 * Dialog asli dengan showModal() (spec v3 bagian 8.5, W1-K6): fokus terkurung di dalam,
 * halaman di belakangnya inert, dan Escape menutup, semuanya bawaan peramban. Ketika
 * tertutup dengan cara apa pun, fokus kembali ke pemicunya; bila pemicu sudah hilang dari
 * DOM, fokus jatuh ke isi utama #konten, bukan ke body.
 */
function useDialogAsli(terbuka: boolean, onTutup: () => void) {
    const ref = useRef<HTMLDialogElement>(null);
    const pemicu = useRef<HTMLElement | null>(null);
    const onTutupTerakhir = useRef(onTutup);

    useEffect(() => {
        onTutupTerakhir.current = onTutup;
    }, [onTutup]);

    useEffect(() => {
        const dialog = ref.current;

        if (!dialog) {
            return;
        }

        if (terbuka && !dialog.open) {
            pemicu.current = document.activeElement instanceof HTMLElement ? document.activeElement : null;
            dialog.showModal();
        } else if (!terbuka && dialog.open) {
            dialog.close();
        }
    }, [terbuka]);

    useEffect(() => {
        const dialog = ref.current;

        if (!dialog) {
            return;
        }

        const saatTutup = () => {
            const tujuan = pemicu.current?.isConnected ? pemicu.current : document.getElementById('konten');
            tujuan?.focus();
            onTutupTerakhir.current();
        };

        dialog.addEventListener('close', saatTutup);

        return () => dialog.removeEventListener('close', saatTutup);
    }, []);

    return ref;
}

/** Klik pada tirai (di luar isi dialog) menutupnya, seperti yang diharapkan pengguna ponsel. */
function tutupBilaTirai(e: MouseEvent<HTMLDialogElement>) {
    if (e.target === e.currentTarget) {
        e.currentTarget.close();
    }
}

interface LapisanProps {
    terbuka: boolean;
    onTutup: () => void;
    judul: string;
    keterangan?: string;
    /** Tombol aksi di kaki lapisan, misalnya "Terapkan" atau "Hapus". */
    aksi?: ReactNode;
    children: ReactNode;
}

/**
 * Lembar bawah: naik dari tepi bawah di ponsel (sudut atas 26 px, pegangan hiasan), menjadi
 * panel tengah mulai sm. Isi yang panjang bergulir di dalam lembar; kaki aksi tetap terlihat.
 */
export function LembarBawah({ terbuka, onTutup, judul, keterangan, aksi, children }: LapisanProps) {
    const ref = useDialogAsli(terbuka, onTutup);
    const id = useId();

    return (
        <dialog
            ref={ref}
            aria-labelledby={`${id}-judul`}
            aria-describedby={keterangan ? `${id}-keterangan` : undefined}
            onClick={tutupBilaTirai}
            className="lembar mb-0 mt-auto max-h-[90dvh] w-full max-w-none rounded-t-lembar border border-subtle bg-surface p-0 text-primary shadow-overlay sm:mb-auto sm:max-w-lg sm:rounded-lembar"
        >
            <div className="flex max-h-[90dvh] flex-col">
                <div aria-hidden="true" className="mx-auto mt-2 h-1 w-10 shrink-0 rounded-full bg-control sm:hidden" />
                <div className="flex items-start justify-between gap-3 px-5 pt-3">
                    <div className="min-w-0 pt-2">
                        <h2 id={`${id}-judul`} className="text-judul-kartu text-primary">
                            {judul}
                        </h2>
                        {keterangan ? (
                            <p id={`${id}-keterangan`} className="mt-1 text-kecil text-secondary">
                                {keterangan}
                            </p>
                        ) : null}
                    </div>
                    <TombolIkon ikon="x" label="Tutup" varian="polos" onClick={() => ref.current?.close()} />
                </div>
                <div className="relative min-h-0 flex-1 overflow-y-auto px-5 py-4">{children}</div>
                {aksi ? <div className="flex flex-wrap justify-end gap-2 border-t border-subtle px-5 pb-[max(env(safe-area-inset-bottom),0.75rem)] pt-3">{aksi}</div> : null}
            </div>
        </dialog>
    );
}

/**
 * Dialog tengah untuk keputusan singkat, misalnya konfirmasi yang menyebut akibatnya. Tanpa
 * aksi dari pemanggil, kakinya berisi tombol Tutup.
 */
export function Dialog({ terbuka, onTutup, judul, keterangan, aksi, children }: LapisanProps) {
    const ref = useDialogAsli(terbuka, onTutup);
    const id = useId();

    return (
        <dialog
            ref={ref}
            aria-labelledby={`${id}-judul`}
            aria-describedby={keterangan ? `${id}-keterangan` : undefined}
            onClick={tutupBilaTirai}
            className="dialog-tengah m-auto w-[calc(100%-2rem)] max-w-md rounded-utama border border-subtle bg-surface p-0 text-primary shadow-overlay"
        >
            <div className="px-5 pt-5">
                <h2 id={`${id}-judul`} className="text-judul-kartu text-primary">
                    {judul}
                </h2>
                {keterangan ? (
                    <p id={`${id}-keterangan`} className="mt-1 text-isi text-secondary">
                        {keterangan}
                    </p>
                ) : null}
            </div>
            <div className="px-5 py-4">{children}</div>
            <div className="flex flex-wrap justify-end gap-2 border-t border-subtle px-5 py-3">
                {aksi ?? (
                    <Button variant="secondary" onClick={() => ref.current?.close()}>
                        Tutup
                    </Button>
                )}
            </div>
        </dialog>
    );
}
```

- [ ] **Step 4: Toast urungkan.** Buat `resources/js/components/ui/ToastUrungkan.tsx`:

```tsx
import { useCallback, useEffect, useState } from 'react';
import { Icon } from './Icon';

export interface DataToast {
    id: number;
    pesan: string;
    onUrungkan: () => void;
}

/** Keadaan satu toast urungkan; toast baru menggantikan yang lama. */
export function useToastUrungkan() {
    const [toast, setToast] = useState<DataToast | null>(null);
    const tampilkan = useCallback((pesan: string, onUrungkan: () => void) => setToast({ id: Date.now(), pesan, onUrungkan }), []);
    const tutup = useCallback(() => setToast(null), []);

    return { toast, tampilkan, tutup };
}

interface WadahToastProps {
    toast: DataToast | null;
    onTutup: () => void;
    /** Paling sedikit 8000 ms (WCAG 2.2.1); hitungannya berhenti selama disentuh penunjuk atau difokus. */
    durasiMs?: number;
}

/**
 * Wadah toast urungkan (spec v3 bagian 8.6 dan 8.8, W1-K7). Wilayah aria-live ada sejak
 * halaman dimuat supaya pesan yang masuk diumumkan. Toast bertahan 8 detik, berhenti
 * menghitung selama disentuh penunjuk atau difokus papan ketik, dan dapat ditutup. Urungkan
 * membatalkan aksinya lalu menutup toast. Melayang di atas navigasi bawah, di bawah dialog.
 */
export function WadahToast({ toast, onTutup, durasiMs = 8000 }: WadahToastProps) {
    return (
        <div aria-live="polite" aria-atomic="true" className="pointer-events-none fixed inset-x-0 bottom-[calc(var(--nav-bawah,env(safe-area-inset-bottom))+1rem)] z-40 flex justify-center px-4">
            {toast ? <Toast key={toast.id} toast={toast} onTutup={onTutup} durasiMs={durasiMs} /> : null}
        </div>
    );
}

function Toast({ toast, onTutup, durasiMs }: { toast: DataToast; onTutup: () => void; durasiMs: number }) {
    const [jeda, setJeda] = useState(false);

    useEffect(() => {
        if (jeda) {
            return;
        }

        const pewaktu = window.setTimeout(onTutup, durasiMs);

        return () => window.clearTimeout(pewaktu);
    }, [jeda, durasiMs, onTutup]);

    return (
        <div
            onMouseEnter={() => setJeda(true)}
            onMouseLeave={() => setJeda(false)}
            onFocus={() => setJeda(true)}
            onBlur={() => setJeda(false)}
            className="toast-masuk pointer-events-auto flex w-full max-w-md items-center gap-2 rounded-tombol bg-surface-inverse py-2 pl-4 pr-2 text-inverse shadow-overlay"
        >
            <p className="min-w-0 flex-1 text-kecil font-semibold">{toast.pesan}</p>
            <button
                type="button"
                onClick={() => {
                    toast.onUrungkan();
                    onTutup();
                }}
                className="min-h-11 shrink-0 rounded-control px-3 text-kecil font-bold underline underline-offset-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-inverse"
            >
                Urungkan
            </button>
            <button type="button" onClick={onTutup} className="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-inverse">
                <Icon name="x" size={20} />
                <span className="sr-only">Tutup pemberitahuan</span>
            </button>
        </div>
    );
}
```

- [ ] **Step 5: Bar aksi dan bilah tab.** Buat `resources/js/components/ui/BarAksi.tsx`:

```tsx
import type { ReactNode } from 'react';

interface BarAksiProps {
    /** Nama wilayah bagi pembaca layar, misalnya "Aksi rencana trip". */
    label: string;
    /** Ringkasan keadaan yang berubah, misalnya "2 jalur ditimbang"; diumumkan ketika berubah. */
    ringkasan?: ReactNode;
    children: ReactNode;
}

/**
 * Bar aksi melayang di atas navigasi bawah (spec v3 bagian 8.5): satu elevasi
 * shadow-overlay dan material chrome, dengan pengganjal setinggi bar supaya isi terakhir
 * halaman tidak tertutup. Tombolnya membungkus ke baris baru di layar sempit.
 */
export function BarAksi({ label, ringkasan, children }: BarAksiProps) {
    return (
        <>
            <div aria-hidden="true" className="h-32" />
            <section aria-label={label} className="pointer-events-none fixed inset-x-0 bottom-[calc(var(--nav-bawah,env(safe-area-inset-bottom))+0.75rem)] z-30 px-4">
                <div className="chrome pointer-events-auto mx-auto flex max-w-xl flex-col gap-3 rounded-utama border p-3 shadow-overlay sm:flex-row sm:items-center">
                    {ringkasan ? (
                        <div aria-live="polite" className="min-w-0 flex-1 text-kecil text-secondary">
                            {ringkasan}
                        </div>
                    ) : null}
                    <div className="flex flex-wrap gap-2 sm:justify-end">{children}</div>
                </div>
            </section>
        </>
    );
}
```

Buat `resources/js/components/ui/BilahTab.tsx`:

```tsx
import { kelas } from '@/lib/kelas';
import { fokusTerlihat } from './gaya';
import { Icon, type NamaIkon } from './Icon';
import { Tautan } from './Tautan';

export interface ItemTab {
    label: string;
    href: string;
    ikon: NamaIkon;
    aktif: boolean;
}

interface BilahTabProps {
    item: ReadonlyArray<ItemTab>;
    /** Nama navigasi bagi pembaca layar. */
    label?: string;
    /** Bawaan true: melayang tetap di bawah layar ponsel. false untuk pameran primitif. */
    mengambang?: boolean;
}

/**
 * Bilah tab v3 (spec v3 bagian 5 dan 8.3): pil melayang berisi lima tab. Tab aktif
 * ditandai ikon varian isi, isian pil, tebal huruf, dan aria-current, bukan warna saja.
 * Label membungkus bila panjang dan tidak pernah di bawah 12 px. W2 memasangnya di
 * AppLayout menggantikan navigasi bawah lama.
 */
export function BilahTab({ item, label = 'Navigasi utama', mengambang = true }: BilahTabProps) {
    return (
        <nav aria-label={label} className={kelas(mengambang && 'fixed inset-x-0 bottom-0 z-30 px-4 pb-[max(env(safe-area-inset-bottom),0.75rem)] sm:hidden')}>
            <ul className="chrome mx-auto flex max-w-md items-stretch gap-1 rounded-full border p-1.5 shadow-overlay">
                {item.map((t) => (
                    <li key={t.label} className="min-w-0 flex-1">
                        <Tautan
                            href={t.href}
                            aria-current={t.aktif ? 'page' : undefined}
                            className={kelas(
                                'flex h-14 flex-col items-center justify-center gap-0.5 rounded-full px-1 text-center text-label leading-tight',
                                fokusTerlihat,
                                t.aktif ? 'bg-brand-100 font-bold text-brand-900' : 'font-medium text-secondary hover:bg-surface-sunken',
                            )}
                        >
                            <Icon name={t.ikon} size={22} isi={t.aktif} />
                            <span className="max-w-full break-words">{t.label}</span>
                        </Tautan>
                    </li>
                ))}
            </ul>
        </nav>
    );
}
```

- [ ] **Step 6: Gerak buka dan tirai.** Di `resources/css/app.css`, sisipkan blok berikut tepat sebelum komentar yang dimulai
`/*\n| Kurangi gerak dari pengaturan aplikasi (Profil, Tampilan), bukan hanya dari sistem.` Blok ini berada di luar
`@layer base`, jadi aturan kurangi gerak (`!important`) tetap menang:

```css
/*
| Lapisan melayang (spec v3 bagian 8.5 dan 8.6). Lembar bawah dan dialog adalah <dialog>
| yang dibuka showModal(), jadi keduanya tinggal di top layer peramban dan tidak butuh
| z-index. Skala z sisanya: bar aksi dan bilah tab z-30, toast z-40. Hanya gerak buka yang
| dianimasikan (W1-K6): lembar naik 280 ms dengan kurva gerak, dialog tengah memudar
| 200 ms, toast naik 200 ms; tutup seketika. Setelan kurangi gerak di atas memangkas
| semuanya menjadi 1 ms. Tirai memakai cadangan nilai karena ::backdrop di peramban lama
| tidak mewarisi variabel dari :root.
*/
@layer components {
    .lembar[open] {
        animation: lembar-naik var(--dur-sedang) var(--ease-gerak);
    }

    .dialog-tengah[open] {
        animation: pudar-masuk var(--dur-cepat) var(--ease-pudar);
    }

    .lembar::backdrop,
    .dialog-tengah::backdrop {
        background-color: rgb(var(--scrim, 15 19 17) / 0.5);
    }

    .toast-masuk {
        animation: toast-naik var(--dur-cepat) var(--ease-gerak);
    }
}

@keyframes lembar-naik {
    from {
        transform: translateY(100%);
    }
}

@keyframes pudar-masuk {
    from {
        opacity: 0;
    }
}

@keyframes toast-naik {
    from {
        opacity: 0;
        transform: translateY(0.75rem);
    }
}

/* Halaman di belakang dialog modal tidak ikut bergulir. */
html:has(dialog[open]) {
    overflow: hidden;
}
```

- [ ] **Step 7: Jalankan test tugas ini dan tetangganya sampai lulus.**

Run: `php artisan test --compact --filter='DesignPrimitivesTest|LongInputLayoutTest|MotionTest|TapTargetTest|DesignSystemTest|InertiaLayoutTest|ColourContrastTest|BrowserSurfacesTest|SurfaceSystemTest'`
Expected: PASS, 225 test.

- [ ] **Step 8: Gerbang tugas.**

Run: `vendor/bin/pint --dirty --format agent`
Expected: `{"tool":"pint","result":"passed"}` atau berkas dirapikan tanpa perubahan makna.

Run: `npx tsc --noEmit`
Expected: keluar tanpa keluaran galat.

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal. Pada simulasi rencana: 1.493 test, 1.492 lulus, 1 dilewati.

- [ ] **Step 9: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 8: Halaman pameran primitif (lokal saja)

Induk W1 butir 5, ruling W1-R5: `/dev/primitif` hanya terdaftar di `APP_ENV=local`, meminta login, tidak masuk sitemap,
dan meminta mesin pencari tidak mengindeksnya. Setiap kontrol di halaman ini berfungsi (antislop R-26), dan satu blok
selebar 360 px menaruh untaian Indonesia panjang di setiap primitif berlabel.

**Files:**
- Create: `routes/dev.php`, `resources/js/pages/Dev/Primitif.tsx`, `tests/Feature/DevPrimitivesRouteTest.php`
- Modify: `routes/web.php` (blok di akhir berkas), `CONTRIBUTING.md` (bagian "UI dan UX"), `docs/ARCHITECTURE.md` (baris `packages/ui-design-system`)
- Test: `tests/Feature/LongInputLayoutTest.php`

**Interfaces:**
- Consumes: semua primitif Tugas 3 sampai 7; `permukaan` dari `resources/js/layouts/NavLinks.tsx`; rute Wayfinder `@/routes/trails`.
- Produces: rute bernama `dev.primitif` (GET `/dev/primitif`, middleware `auth`, komponen Inertia `Dev/Primitif`, prop `title` "Pameran primitif"), hanya ketika `app()->environment('local')`. Tugas 10 memuat `routes/dev.php` langsung di test untuk memotretnya.

- [ ] **Step 1: Tulis test rute yang gagal.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan make:test --phpunit DevPrimitivesRouteTest --no-interaction`
Expected: `INFO  Test [E:\praktik_aplikasi_web	ests\Feature\DevPrimitivesRouteTest.php] created successfully.`

Ganti seluruh isinya dengan:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Pameran primitif hanya ada di APP_ENV=local (ruling W1-R5).
 *
 * Halamannya untuk pengembang dan peninjau desain, bukan untuk pendaki. Di luar local
 * rutenya tidak terdaftar sama sekali (404, bukan 403 yang membocorkan keberadaannya),
 * tidak masuk sitemap, dan halamannya meminta mesin pencari tidak mengindeksnya.
 */
class DevPrimitivesRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_showcase_does_not_exist_outside_local(): void
    {
        $this->assertFalse(Route::has('dev.primitif'));

        $this->actingAs(User::factory()->create())->get('/dev/primitif')->assertNotFound();
    }

    public function test_the_routes_file_registers_the_showcase_only_in_local(): void
    {
        $this->assertMatchesRegularExpression(
            "/if \(app\(\)->environment\('local'\)\) \{\s*require __DIR__\.'\/dev\.php';\s*\}/",
            File::get(base_path('routes/web.php'))
        );
    }

    /**
     * Rutenya dimuat langsung di sini karena suite berjalan di APP_ENV=testing.
     */
    public function test_the_showcase_renders_its_page_when_its_routes_are_loaded(): void
    {
        Route::middleware('web')->group(base_path('routes/dev.php'));

        $this->actingAs(User::factory()->create())
            ->get('/dev/primitif')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Dev/Primitif')->where('title', 'Pameran primitif'));
    }

    public function test_a_guest_is_sent_to_login(): void
    {
        Route::middleware('web')->group(base_path('routes/dev.php'));

        $this->get('/dev/primitif')->assertRedirect('/login');
    }

    public function test_the_showcase_is_not_in_the_sitemap(): void
    {
        $this->get('/sitemap.xml')->assertOk()->assertDontSee('dev/primitif');
    }

    public function test_the_showcase_asks_search_engines_not_to_index_it(): void
    {
        $this->assertStringContainsString('<meta name="robots" content="noindex" />', File::get(resource_path('js/pages/Dev/Primitif.tsx')));
    }
}
```

Di `tests/Feature/LongInputLayoutTest.php`, tambahkan metode berikut sesudah `test_a_primitive_label_wraps_instead_of_being_cut`:

```php

    /**
     * Pameran primitif memuat satu blok selebar 360 px yang menaruh untaian Indonesia
     * panjang di setiap primitif berlabel, supaya pembungkusan dilihat di tangkapan 390 px
     * (Review Focus W1), bukan diasumsikan dari kelas.
     */
    public function test_the_showcase_exercises_long_indonesian_labels_at_360px(): void
    {
        $pameran = File::get(resource_path('js/pages/Dev/Primitif.tsx'));

        $this->assertStringContainsString("const teksPanjang = 'Pemberitahuan penutupan sementara jalur pendakian Selabintana karena perbaikan jembatan';", $pameran);
        $this->assertSame(1, preg_match('/<section aria-labelledby="bagian-teks-panjang" data-uji="teks-panjang" className="max-w-\[360px\][^"]*">(.*?)<\/section>/s', $pameran, $blok), 'Blok teks panjang 360 px tidak ditemukan.');

        foreach (['<Button', '<ChipPilihan', '<Lencana', '<KontrolSegmen', '<Sakelar', '<KotakCentang', '<EmptyState'] as $primitif) {
            $this->assertStringContainsString($primitif, $blok[1], "Blok teks panjang tidak memuat {$primitif}.");
        }

        $this->assertGreaterThanOrEqual(6, substr_count($blok[1], 'teksPanjang'), 'Setiap primitif berlabel memakai untaian panjang.');
    }
```

- [ ] **Step 2: Jalankan dan pastikan gagal.**

Run: `php artisan test --compact --filter='DevPrimitivesRouteTest|LongInputLayoutTest'`
Expected: FAIL. Pada simulasi rencana: 21 test, 16 lulus, 1 gagal, 4 error (`routes/dev.php` dan halaman belum ada).

- [ ] **Step 3: Rute pengembangan.** Buat `routes/dev.php`:

```php
<?php

use Illuminate\Support\Facades\Route;

/*
 * Rute pengembangan, dimuat routes/web.php hanya ketika APP_ENV=local (ruling W1-R5).
 *
 * Pameran primitif menampilkan setiap primitif desain di satu halaman, berfungsi dan
 * dengan teks Indonesia yang panjang, supaya perubahan token atau komponen dapat dilihat
 * di 390 px dan 1440 px sebelum dipakai halaman sungguhan. Di luar local rutenya tidak
 * terdaftar sama sekali, jadi jawabannya 404, dan halamannya tidak masuk sitemap.
 */
Route::middleware('auth')->group(function (): void {
    Route::inertia('dev/primitif', 'Dev/Primitif', ['title' => 'Pameran primitif'])->name('dev.primitif');
});
```

Tambahkan di akhir `routes/web.php`, sesudah baris `Route::view('offline', 'offline')->name('pwa.offline');`:

```php

/*
 * Pameran primitif desain (ruling W1-R5): hanya di APP_ENV=local, tidak pernah di staging
 * atau produksi, dan tidak masuk sitemap. Isinya di routes/dev.php.
 */
if (app()->environment('local')) {
    require __DIR__.'/dev.php';
}
```

- [ ] **Step 4: Halaman pameran.** Buat `resources/js/pages/Dev/Primitif.tsx`. Aturan sapuan yang berlaku untuk setiap halaman
sudah dipenuhi berkas ini: setiap `.map(` yang menggambar elemen memetakan larik harfiah berkas ini atau dijaga
`x.length === 0 ?` (`EmptyStateCoverageTest`), setiap `EmptyState` membawa `jenis` dan `description`, setiap tombol
memakai `Button`, `TombolIkon`, atau `Chip` (`TapTargetTest`), tanpa `grid-cols-3` tanpa titik henti, tanpa kata klaim
keselamatan, dan tanpa kata "phosphor" (`ThirdPartyLicenseTest`).

```tsx
import { Head } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import { Avatar } from '@/components/ui/Avatar';
import { BarAksi } from '@/components/ui/BarAksi';
import { BilahTab, type ItemTab } from '@/components/ui/BilahTab';
import { Button } from '@/components/ui/Button';
import { ChipLepas, ChipPilihan } from '@/components/ui/Chip';
import { EmptyState } from '@/components/ui/EmptyState';
import { Icon, bobotUntuk } from '@/components/ui/Icon';
import { KontrolSegmen, type PilihanSegmen } from '@/components/ui/KontrolSegmen';
import { KotakCentang } from '@/components/ui/KotakCentang';
import { Dialog, LembarBawah } from '@/components/ui/Lembar';
import { Lencana } from '@/components/ui/Lencana';
import { PageHeader } from '@/components/ui/PageHeader';
import { Sakelar } from '@/components/ui/Sakelar';
import { Stepper } from '@/components/ui/Stepper';
import { TombolIkon } from '@/components/ui/TombolIkon';
import { WadahToast, useToastUrungkan } from '@/components/ui/ToastUrungkan';
import { AppLayout } from '@/layouts/AppLayout';
import { permukaan } from '@/layouts/NavLinks';
import { index as trailsIndex } from '@/routes/trails';

const teksPanjang = 'Pemberitahuan penutupan sementara jalur pendakian Selabintana karena perbaikan jembatan';

const pilihanChip = ['Tektok', 'Camping', 'Ada sumber air', 'Ramah pemula'];
const tafsiranAwal = ['Wilayah: Bogor dan sekitarnya', 'Tuntutan teknis: rendah', 'Durasi: sampai 8 jam'];
const simpananAwal = ['Gede via Cibodas', 'Gede via Gunung Putri', 'Papandayan via Camp David'];

const pilihanTampilan: ReadonlyArray<PilihanSegmen<'daftar' | 'peta'>> = [
    { nilai: 'daftar', label: 'Daftar', ikon: 'list' },
    { nilai: 'peta', label: 'Peta', ikon: 'map-trifold' },
];

const pilihanJenisTrip: ReadonlyArray<PilihanSegmen<'tektok' | 'camping' | 'multi'>> = [
    { nilai: 'tektok', label: 'Tektok', keterangan: '1 hari' },
    { nilai: 'camping', label: 'Camping', keterangan: '2 hari' },
    { nilai: 'multi', label: 'Multi-hari', keterangan: '3 hari atau lebih' },
];

const pilihanSegmenPanjang: ReadonlyArray<PilihanSegmen<'a' | 'b'>> = [
    { nilai: 'a', label: teksPanjang },
    { nilai: 'b', label: 'Jalur alternatif lewat Gunung Putri' },
];

const ukuranIkon = [16, 20, 24, 32] as const;
const contohIkon = ['arrow-up', 'check', 'bell', 'x'] as const;
const ikonTab = ['house', 'map-trifold', 'backpack', 'person-simple-hike', 'chart-line-up'] as const;
const glyphPilot = ['pos', 'tanjakan', 'resmi'] as const;

const skalaTipe = [
    { kelas: 'text-tampilan', nama: 'Tampilan, 30 px, 800', contoh: 'Selamat sore, Budi.' },
    { kelas: 'text-judul', nama: 'Judul halaman, 28 px, 800', contoh: 'Gede via Cibodas' },
    { kelas: 'text-judul-kartu', nama: 'Judul kartu utama, 23 px, 800', contoh: 'Gede via Cibodas tinggal 15 hari lagi' },
    { kelas: 'text-bagian', nama: 'Judul bagian, 18 px, 700', contoh: 'Yang masih perlu disiapkan' },
    { kelas: 'text-subjudul', nama: 'Subjudul, 17 px, 700', contoh: 'Pos 5 Kandang Badak' },
    { kelas: 'text-isi', nama: 'Isi, 16 px', contoh: 'Tanjakannya lebih dari dua kali riwayat tertinggi Anda.' },
    { kelas: 'text-kecil', nama: 'Isi kecil, 14 px', contoh: 'Diperbarui dua jam lalu dari BMKG.' },
    { kelas: 'text-meta', nama: 'Meta, 13 px', contoh: 'Cianjur, Jawa Barat' },
    { kelas: 'text-label', nama: 'Label, 12 px, lantai', contoh: 'Jelajah' },
] as const;

const contohTab: ItemTab[] = permukaan.map((p) => ({ label: p.label, href: p.href, ikon: p.ikon, aktif: p.label === 'Jelajah' }));

function Bagian({ id, judul, children }: { id: string; judul: string; children: ReactNode }) {
    return (
        <section aria-labelledby={id} className="space-y-4 border-t border-subtle pt-6">
            <h2 id={id} className="text-bagian text-primary">
                {judul}
            </h2>
            {children}
        </section>
    );
}

/**
 * Pameran primitif (ruling W1-R5): hanya ada di APP_ENV=local. Setiap kontrol di sini
 * berfungsi (antislop R-26) dengan contoh isi yang mirip data sungguhan, supaya perubahan
 * token atau komponen dapat diperiksa di 390 px dan 1440 px, terang dan gelap, sebelum
 * dipakai halaman lain. Blok teks panjang menguji pembungkusan di lebar 360 px.
 */
export default function Primitif() {
    const [dipilihChip, setDipilihChip] = useState<string[]>(['Camping']);
    const [tafsiran, setTafsiran] = useState<string[]>(tafsiranAwal);
    const [simpanan, setSimpanan] = useState<string[]>(simpananAwal);
    const [tampilan, setTampilan] = useState<'daftar' | 'peta'>('daftar');
    const [jenisTrip, setJenisTrip] = useState<'tektok' | 'camping' | 'multi'>('camping');
    const [segmenPanjang, setSegmenPanjang] = useState<'a' | 'b'>('a');
    const [pengingat, setPengingat] = useState(true);
    const [barTampil, setBarTampil] = useState(false);
    const [sakelarPanjang, setSakelarPanjang] = useState(false);
    const [headlamp, setHeadlamp] = useState(false);
    const [jasHujan, setJasHujan] = useState(true);
    const [kotakPanjang, setKotakPanjang] = useState(false);
    const [chipPanjang, setChipPanjang] = useState(false);
    const [peserta, setPeserta] = useState(3);
    const [tekanPanjang, setTekanPanjang] = useState(0);
    const [lembarTerbuka, setLembarTerbuka] = useState(false);
    const [dialogTerbuka, setDialogTerbuka] = useState(false);
    const { toast, tampilkan, tutup } = useToastUrungkan();
    const simpananPertama = simpanan[0];

    const gantiChip = (label: string, dipilih: boolean) => setDipilihChip((daftar) => (dipilih ? [...daftar, label] : daftar.filter((l) => l !== label)));

    const lepasTafsiran = (label: string) => {
        const sebelum = tafsiran;
        setTafsiran(tafsiran.filter((t) => t !== label));
        tampilkan(`Saringan "${label}" dilepas.`, () => setTafsiran(sebelum));
    };

    const hapusSimpanan = (nama: string) => {
        const sebelum = simpanan;
        setSimpanan(simpanan.filter((s) => s !== nama));
        tampilkan(`${nama} dihapus dari simpanan.`, () => setSimpanan(sebelum));
    };

    return (
        <div className="py-6 sm:py-8">
            <Head>
                <meta name="robots" content="noindex" />
            </Head>
            <div className="mx-auto max-w-5xl space-y-8 px-4 sm:px-6 lg:px-8">
                <PageHeader
                    title="Pameran primitif"
                    description="Setiap primitif desain v3 dalam satu halaman, berfungsi dan berisi contoh. Hanya ada di lingkungan lokal; lihat di 390 px dan 1440 px, tema terang dan gelap."
                />

                <Bagian id="bagian-tombol" judul="Tombol">
                    <div className="flex flex-wrap items-center gap-3">
                        <Button onClick={() => setTekanPanjang((n) => n + 1)}>Rencanakan trip</Button>
                        <Button variant="secondary" onClick={() => setTekanPanjang((n) => n + 1)}>
                            Simpan jalur
                        </Button>
                        <Button variant="danger" onClick={() => setDialogTerbuka(true)}>
                            Hapus trip
                        </Button>
                        <Button variant="ghost" onClick={() => setTekanPanjang((n) => n + 1)}>
                            Lihat alasannya
                        </Button>
                        <Button size="sm" variant="secondary" onClick={() => setTekanPanjang((n) => n + 1)}>
                            Pesan di sistem resmi
                        </Button>
                        <Button processing>Menyimpan rencana</Button>
                        <Button variant="secondary" disabled>
                            Mulai mendaki
                        </Button>
                    </div>
                    <p aria-live="polite" className="text-kecil text-muted">
                        Tombol contoh ditekan {tekanPanjang} kali.
                    </p>
                </Bagian>

                <Bagian id="bagian-tombol-ikon" judul="Tombol ikon">
                    <div className="flex flex-wrap items-center gap-3">
                        <TombolIkon ikon="bell" label="Pemberitahuan" onClick={() => setPengingat((v) => !v)} />
                        <TombolIkon ikon="list" label="Tampilkan daftar" varian="sumur" onClick={() => setTampilan('daftar')} />
                        <TombolIkon ikon="map-trifold" label="Tampilkan peta" varian="sumur" bentuk="kotak" onClick={() => setTampilan('peta')} />
                        <TombolIkon ikon="plus" label="Tambah peserta" besar onClick={() => setPeserta((n) => Math.min(12, n + 1))} />
                        <TombolIkon ikon="x" label="Tutup contoh" varian="polos" onClick={() => setBarTampil(false)} />
                    </div>
                </Bagian>

                <Bagian id="bagian-chip" judul="Chip">
                    <div className="flex flex-wrap gap-2">
                        {pilihanChip.map((label) => (
                            <ChipPilihan key={label} dipilih={dipilihChip.includes(label)} onUbah={(dipilih) => gantiChip(label, dipilih)}>
                                {label}
                            </ChipPilihan>
                        ))}
                    </div>
                    <p className="text-kecil text-secondary">Tafsiran kalimat pencarian, dapat dilepas satu per satu:</p>
                    {tafsiran.length === 0 ? (
                        <Button variant="secondary" size="sm" onClick={() => setTafsiran(tafsiranAwal)}>
                            Kembalikan tafsiran kalimat
                        </Button>
                    ) : (
                        <div className="flex flex-wrap gap-2">
                            {tafsiran.map((label) => (
                                <ChipLepas key={label} onLepas={() => lepasTafsiran(label)}>
                                    {label}
                                </ChipLepas>
                            ))}
                        </div>
                    )}
                </Bagian>

                <Bagian id="bagian-segmen" judul="Kontrol segmen">
                    <KontrolSegmen nama="tampilan" label="Tampilan hasil" pilihan={pilihanTampilan} nilai={tampilan} onUbah={setTampilan} className="max-w-xs" />
                    <KontrolSegmen nama="jenis-trip" label="Jenis trip" pilihan={pilihanJenisTrip} nilai={jenisTrip} onUbah={setJenisTrip} className="max-w-md" />
                </Bagian>

                <Bagian id="bagian-sakelar" judul="Sakelar, kotak centang, dan stepper">
                    <div className="max-w-md space-y-2">
                        <Sakelar judul="Ingatkan saya" keterangan="Pengingat dikirim tiga hari sebelum berangkat." nyala={pengingat} onUbah={setPengingat} />
                        <Sakelar judul="Tampilkan bar aksi" keterangan="Bar melayang di atas navigasi bawah." nyala={barTampil} onUbah={setBarTampil} />
                        <KotakCentang
                            label="Headlamp dan baterai cadangan"
                            keterangan="Jalur Cibodas masuk hutan lebat, dan gelap turun sekitar pukul 18.00."
                            lencana={<Lencana nada="bahaya">Kritis</Lencana>}
                            checked={headlamp}
                            onChange={(e) => setHeadlamp(e.target.checked)}
                        />
                        <KotakCentang label="Jas hujan" checked={jasHujan} onChange={(e) => setJasHujan(e.target.checked)} />
                        <Stepper label="Peserta" keterangan="Termasuk Anda." nilai={peserta} min={1} max={12} onUbah={setPeserta} />
                    </div>
                </Bagian>

                <Bagian id="bagian-lencana" judul="Lencana dan avatar">
                    <div className="flex flex-wrap items-center gap-2">
                        <Lencana nada="cocok" bentuk="pil">
                            Cocok
                        </Lencana>
                        <Lencana nada="perlu" bentuk="pil">
                            Perlu persiapan
                        </Lencana>
                        <Lencana nada="kurang" bentuk="pil">
                            Kurang cocok
                        </Lencana>
                        <Lencana nada="belum" bentuk="pil">
                            Belum dinilai
                        </Lencana>
                        <Lencana nada="merek">Terkini</Lencana>
                        <Lencana nada="peringatan">Dibatasi</Lencana>
                        <Lencana nada="bahaya">Kritis</Lencana>
                        <Lencana nada="komunitas" garis="putus">
                            Lolos moderasi
                        </Lencana>
                        <Lencana nada="netral" garis="putus">
                            Data contoh
                        </Lencana>
                    </div>
                    <div className="flex flex-wrap items-center gap-4">
                        <Avatar nama="Budi Setiawan" ukuran={64} hiasan={false} />
                        <span className="inline-flex items-center gap-2 text-isi text-primary">
                            <Avatar nama="Budi Setiawan" />
                            Budi Setiawan
                        </span>
                        <span className="inline-flex items-center gap-2 text-kecil text-secondary">
                            <Avatar nama="Sari Wulandari" ukuran={32} />
                            Sari W.
                        </span>
                    </div>
                </Bagian>

                <Bagian id="bagian-kosong" judul="Keadaan kosong">
                    <div className="grid gap-4 lg:grid-cols-3">
                        <EmptyState
                            jenis="pertama"
                            title="Belum ada rute tersimpan"
                            description="Tekan Simpan pada jalur di Jelajah. Jalur yang Anda simpan terkumpul di sini dan dapat dibandingkan."
                        >
                            <Button href={trailsIndex.url()}>Buka Jelajah</Button>
                        </EmptyState>
                        <EmptyState
                            jenis="tersaring"
                            title="Tidak ada jalur dengan saringan ini"
                            description="Melonggarkan tuntutan teknis atau mengosongkan wilayah biasanya menolong lebih cepat daripada mengubah kata pencarian."
                        >
                            <Button variant="secondary" size="sm" onClick={() => setTafsiran(tafsiranAwal)}>
                                Hapus semua saringan
                            </Button>
                        </EmptyState>
                        <EmptyState
                            jenis="menunggu"
                            title="Prakiraan cuaca belum tersedia"
                            menunggu="Menunggu BMKG memperbarui prakiraan area Cibodas"
                            description="Prakiraan terakhir berumur lebih dari 12 jam, jadi tidak ditampilkan sebagai kondisi kini."
                        />
                    </div>
                </Bagian>

                <Bagian id="bagian-lapisan" judul="Lembar, dialog, dan toast urungkan">
                    <div className="flex flex-wrap gap-3">
                        <Button variant="secondary" onClick={() => setLembarTerbuka(true)}>
                            Buka saringan
                        </Button>
                        <Button variant="secondary" onClick={() => setDialogTerbuka(true)}>
                            Buka dialog konfirmasi
                        </Button>
                    </div>
                    {simpanan.length === 0 ? (
                        <EmptyState
                            jenis="pertama"
                            title="Simpanan contoh kosong"
                            description="Semua jalur contoh sudah dihapus. Kembalikan untuk mencoba toast urungkan lagi."
                        >
                            <Button variant="secondary" size="sm" onClick={() => setSimpanan(simpananAwal)}>
                                Kembalikan simpanan contoh
                            </Button>
                        </EmptyState>
                    ) : (
                        <ul className="max-w-md divide-y divide-subtle rounded-lg border border-subtle bg-surface">
                            {simpanan.map((nama) => (
                                <li key={nama} className="flex items-center justify-between gap-3 py-1 pl-4 pr-1">
                                    <span className="min-w-0 break-words text-isi text-primary">{nama}</span>
                                    <TombolIkon ikon="x" label={`Hapus ${nama} dari simpanan`} varian="polos" onClick={() => hapusSimpanan(nama)} />
                                </li>
                            ))}
                        </ul>
                    )}
                </Bagian>

                <Bagian id="bagian-tab" judul="Bilah tab">
                    <BilahTab item={contohTab} label="Contoh bilah tab" mengambang={false} />
                </Bagian>

                <Bagian id="bagian-ikon" judul="Ikon, bobot per ukuran, dan glyph pilot">
                    <table className="text-kecil text-secondary">
                        <caption className="sr-only">Ikon pada empat ukuran render beserta bobotnya</caption>
                        <thead>
                            <tr>
                                <th scope="col" className="pb-2 pr-4 text-left font-semibold">
                                    Nama
                                </th>
                                {ukuranIkon.map((u) => (
                                    <th key={u} scope="col" className="pb-2 pr-4 text-left font-semibold">
                                        {u} px, {bobotUntuk(u) === 'bold' ? 'Bold' : 'Regular'}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {contohIkon.map((nama) => (
                                <tr key={nama}>
                                    <th scope="row" className="py-1 pr-4 text-left font-medium">
                                        {nama}
                                    </th>
                                    {ukuranIkon.map((u) => (
                                        <td key={u} className="py-1 pr-4 text-primary">
                                            <Icon name={nama} size={u} />
                                        </td>
                                    ))}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    <p className="text-kecil text-secondary">Ikon tab, garis lalu isi untuk tab aktif:</p>
                    <div className="flex flex-wrap gap-4 text-primary">
                        {ikonTab.map((nama) => (
                            <span key={nama} className="inline-flex items-center gap-1">
                                <Icon name={nama} size={22} />
                                <Icon name={nama} size={22} isi />
                            </span>
                        ))}
                    </div>
                    <p className="text-kecil text-secondary">Glyph domain pilot: pos, tanjakan, resmi.</p>
                    <div className="flex flex-wrap items-end gap-6 text-primary">
                        {glyphPilot.map((nama) => (
                            <span key={nama} className="inline-flex items-end gap-2">
                                {ukuranIkon.map((u) => (
                                    <Icon key={u} name={nama} size={u} label={u === 32 ? `Glyph ${nama}` : undefined} />
                                ))}
                            </span>
                        ))}
                    </div>
                </Bagian>

                <Bagian id="bagian-tipe" judul="Skala tipe dan angka">
                    <div className="space-y-3">
                        {skalaTipe.map((s) => (
                            <div key={s.kelas}>
                                <p className={`${s.kelas} text-primary`}>{s.contoh}</p>
                                <p className="text-meta text-muted">{s.nama}</p>
                            </div>
                        ))}
                    </div>
                    <dl className="flex flex-wrap gap-x-8 gap-y-3">
                        <div>
                            <dt className="text-meta text-muted">Jarak</dt>
                            <dd className="text-bagian text-primary">9,7 km</dd>
                        </div>
                        <div>
                            <dt className="text-meta text-muted">Tanjakan</dt>
                            <dd className="inline-flex items-center gap-1 text-bagian text-primary">
                                <Icon name="arrow-up" size={20} />
                                <span className="sr-only">naik </span>1.650 m
                            </dd>
                        </div>
                        <div>
                            <dt className="text-meta text-muted">Waktu tempuh</dt>
                            <dd className="text-bagian text-primary">7–8 j</dd>
                        </div>
                    </dl>
                </Bagian>

                <section aria-labelledby="bagian-teks-panjang" data-uji="teks-panjang" className="max-w-[360px] space-y-4 rounded-lg border border-dashed border-control p-4">
                    <h2 id="bagian-teks-panjang" className="text-bagian text-primary">
                        Teks panjang di 360 px
                    </h2>
                    <Button variant="secondary" onClick={() => setTekanPanjang((n) => n + 1)}>
                        {teksPanjang}
                    </Button>
                    <ChipPilihan dipilih={chipPanjang} onUbah={setChipPanjang}>
                        {teksPanjang}
                    </ChipPilihan>
                    <Lencana nada="peringatan">{teksPanjang}</Lencana>
                    <KontrolSegmen nama="segmen-panjang" label="Contoh segmen berlabel panjang" pilihan={pilihanSegmenPanjang} nilai={segmenPanjang} onUbah={setSegmenPanjang} />
                    <Sakelar judul={teksPanjang} nyala={sakelarPanjang} onUbah={setSakelarPanjang} />
                    <KotakCentang label={teksPanjang} checked={kotakPanjang} onChange={(e) => setKotakPanjang(e.target.checked)} />
                    <EmptyState jenis="menunggu" title={teksPanjang} menunggu="Menunggu pengelola Taman Nasional Gunung Gede Pangrango" description="Status resmi terakhir tercatat delapan hari lalu." />
                </section>
            </div>

            {barTampil ? (
                <BarAksi label="Aksi contoh" ringkasan={`${simpanan.length} jalur di simpanan contoh`}>
                    <Button onClick={() => setDialogTerbuka(true)}>Hapus yang dipilih</Button>
                    <Button variant="secondary" onClick={() => setBarTampil(false)}>
                        Sembunyikan bar
                    </Button>
                </BarAksi>
            ) : null}

            <LembarBawah
                terbuka={lembarTerbuka}
                onTutup={() => setLembarTerbuka(false)}
                judul="Saring jalur"
                keterangan="Saringan berlaku untuk daftar dan peta."
                aksi={
                    <>
                        <Button variant="secondary" onClick={() => setLembarTerbuka(false)}>
                            Batal
                        </Button>
                        <Button onClick={() => setLembarTerbuka(false)}>Terapkan</Button>
                    </>
                }
            >
                <div className="space-y-4">
                    <KontrolSegmen nama="jenis-trip-lembar" label="Jenis trip" pilihan={pilihanJenisTrip} nilai={jenisTrip} onUbah={setJenisTrip} />
                    <KotakCentang label="Hanya jalur dengan sumber air" checked={jasHujan} onChange={(e) => setJasHujan(e.target.checked)} />
                    <Stepper label="Peserta" nilai={peserta} min={1} max={12} onUbah={setPeserta} />
                </div>
            </LembarBawah>

            <Dialog
                terbuka={dialogTerbuka}
                onTutup={() => setDialogTerbuka(false)}
                judul={simpananPertama ? `Hapus ${simpananPertama} dari simpanan contoh?` : 'Simpanan contoh sudah kosong'}
                keterangan="Catatan pribadi untuk jalur ini ikut terhapus."
                aksi={
                    <>
                        <Button variant="secondary" onClick={() => setDialogTerbuka(false)}>
                            Batal
                        </Button>
                        <Button
                            variant="danger"
                            disabled={!simpananPertama}
                            onClick={() => {
                                setDialogTerbuka(false);
                                if (simpananPertama) {
                                    hapusSimpanan(simpananPertama);
                                }
                            }}
                        >
                            Hapus
                        </Button>
                    </>
                }
            >
                <p className="text-isi text-secondary">Sesudah penghapusan muncul toast urungkan; menekan Urungkan mengembalikan jalurnya.</p>
            </Dialog>
            <WadahToast toast={toast} onTutup={tutup} />
        </div>
    );
}

Primitif.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
```

- [ ] **Step 5: Catat pameran di panduan dan peta arsitektur.** Di `CONTRIBUTING.md` bagian "UI dan UX", ganti butir terakhir:

```markdown
- Keadaan kosong menyebut tahapnya dan langkah berikutnya; data yang belum ada tidak
  dikarang.
```

dengan:

```markdown
- Keadaan kosong menyebut tahapnya dan langkah berikutnya; data yang belum ada tidak
  dikarang.
- Primitif desain tinggal di `resources/js/components/ui/` dan dipakai lebih dulu sebelum
  menulis markup sendiri. Pameran primitif di `/dev/primitif` (hanya `APP_ENV=local`,
  sesudah masuk) menampilkan semuanya dengan contoh isi; periksa perubahan token atau
  komponen di sana pada 390 px dan 1440 px, tema terang dan gelap.
```

Di `docs/ARCHITECTURE.md`, ganti baris yang ditulis Tugas 5:

```markdown
| `packages/ui-design-system` | Token `resources/css/app.css`, pemetaan `tailwind.config.js`, dan primitif `resources/js/components/ui/` (W1) |
```

dengan:

```markdown
| `packages/ui-design-system` | Token `resources/css/app.css`, pemetaan `tailwind.config.js`, primitif `resources/js/components/ui/` (W1), dan pameran primitif `/dev/primitif` yang hanya terdaftar di `APP_ENV=local` (`routes/dev.php`) |
```

- [ ] **Step 6: Jalankan test tugas ini dan semua sapuan yang membaca halaman sampai lulus.**

Run: `php artisan test --compact --filter='DevPrimitivesRouteTest|LongInputLayoutTest|TapTargetTest|EmptyStateCoverageTest|InertiaLayoutTest|DesignSystemTest|IconSystemTest|TypeFloorTest|DesignPrimitivesTest|ColourContrastTest|KeyboardAndScreenReaderTest|ButtonFeedbackTest|NoDeadViewDataTest|PageTitleTest|EveryRouteRendersTest|RouteAccessMatrixTest|InertiaLinkRegistryTest|ThirdPartyLicenseTest|TeamDocsIndexTest|PrdReferenceIntegrityTest'`
Expected: PASS, 291 test.

- [ ] **Step 7: Gerbang tugas.**

Run: `vendor/bin/pint --dirty --format agent`
Expected: `{"tool":"pint","result":"passed"}` atau berkas dirapikan tanpa perubahan makna.

Run: `npx tsc --noEmit`
Expected: keluar tanpa keluaran galat.

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal. Pada simulasi rencana: 1.500 test, 1.499 lulus, 1 dilewati.

Pemeriksaan di peramban tidak dilakukan di tugas ini: rute hanya ada di `APP_ENV=local`, dan agen tidak menjalankan
aplikasi dengan `.env` (Supabase). Tugas 10 memotret halaman ini lewat harness pratinjau, dan Tugas 12 mengklik setiap
kontrolnya.

- [ ] **Step 8: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 9: Data contoh DK-35 (koneksi demo, penjaga, DemoSeeder, memount:demo, lencana Data contoh)

Induk W1 butir 7, ruling W1-R2: Q1 belum diberikan, jadi `memount:demo` dan `DemoSeeder` ditulis dan diuji penjaganya,
tetapi tidak ada langkah yang menjalankan perintahnya. `DemoSeeder` hanya berjalan di dalam PHPUnit (SQLite dalam
memori) di tugas ini dan di Tugas 10. Geometri tidak diisi (W1-K10).

**Files:**
- Create: `app/Exceptions/DataContohDitolak.php`, `app/Support/PenjagaDataContoh.php`, `app/Console/Commands/MemountDemo.php`, `database/seeders/DemoSeeder.php`, `resources/js/components/ui/LencanaDataContoh.tsx`
- Create (test): `tests/Feature/DemoCommandGuardTest.php`, `tests/Feature/DemoSeederTest.php`, `tests/Feature/DemoBadgeTest.php`
- Modify: `config/database.php` (koneksi `demo`), `config/app.php` (kunci `demo`), `.env.example` (`APP_DEMO=false`), `app/Http/Middleware/HandleInertiaRequests.php` (prop `demo`), `resources/js/types/index.d.ts` (`demo: boolean`), `resources/js/layouts/AppLayout.tsx`, `resources/js/components/trips/TataLetakPendakian.tsx`, `database/README.md` (satu baris)

**Interfaces:**
- Consumes: model `User`, `Mountain`, `Trail`, `Checkpoint`, `OfficialStatus`, `DataSource`, `TripPlan`, `HikingHistory`; gerbang publikasi `Trail::booted()`; `RecommendationRuleSeeder`, `PreparationTemplateSeeder`; `Timezone::earliestDateInIndonesia()`; `Lencana` (Tugas 5).
- Produces, dipakai Tugas 10 dan W2 sampai W8:
  - Koneksi `database.connections.demo` (SQLite `database/demo.sqlite`, `url` null) dan `config('app.demo')` dari `APP_DEMO` (bawaan false).
  - `App\Support\PenjagaDataContoh::periksa(): string` melempar `App\Exceptions\DataContohDitolak`.
  - Perintah `memount:demo` (tidak dijalankan di W1).
  - `Database\Seeders\DemoSeeder` dengan `EMAIL_PENDAKI = 'budi@contoh.test'` dan `PENANDA = 'DATA CONTOH'`: Budi Setiawan (profil lengkap, pemula, tanjakan tertinggi 700 m), 8 gunung, 11 jalur (10 terbit, 1 menunggu), trip "Gede via Cibodas" H+15 berstatus PLANNED, 3 pendakian selesai, 1 trip batal.
  - Prop Inertia bersama `demo: boolean` dan komponen `<LencanaDataContoh />` (tidak menggambar apa pun bila `demo` salah).

- [ ] **Step 1: Tulis test yang gagal.** Buat ketiga test:

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan make:test --phpunit DemoCommandGuardTest --no-interaction && DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan make:test --phpunit DemoSeederTest --no-interaction && DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan make:test --phpunit DemoBadgeTest --no-interaction`
Expected: tiga baris `INFO  Test [E:\praktik_aplikasi_web	ests\Feature\<Nama>Test.php] created successfully.`, satu untuk setiap test

Ganti seluruh isi `tests/Feature/DemoCommandGuardTest.php` dengan:

```php
<?php

namespace Tests\Feature;

use App\Support\PenjagaDataContoh;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Penjaga `php artisan memount:demo` (DK-35, ruling W1-R2).
 *
 * Perintah ini mengosongkan lalu mengisi basis data, dan `.env` repo menunjuk Supabase
 * produksi. Setiap penolakan di sini terjadi sebelum satu tabel pun disentuh; tidak ada
 * test yang menjalankan perintahnya sampai selesai, karena menjalankannya adalah keputusan
 * pemilik (Q1).
 */
class DemoCommandGuardTest extends TestCase
{
    private string $berkasUji;

    protected function setUp(): void
    {
        parent::setUp();

        $this->berkasUji = database_path('demo-penjaga-uji.sqlite');
        config(['database.connections.demo.database' => $this->berkasUji]);
    }

    protected function tearDown(): void
    {
        if (is_file($this->berkasUji)) {
            unlink($this->berkasUji);
        }

        parent::tearDown();
    }

    public function test_it_refuses_to_run_outside_local(): void
    {
        $this->artisan('memount:demo')->expectsOutputToContain('APP_ENV=local')->assertFailed();

        $this->assertFileDoesNotExist($this->berkasUji);
    }

    public function test_it_refuses_a_connection_that_is_not_sqlite(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        config(['database.connections.demo.driver' => 'pgsql']);

        $this->artisan('memount:demo')->expectsOutputToContain('bukan SQLite')->assertFailed();
    }

    /**
     * URL mengalahkan setiap kunci lain di konfigurasi koneksi, termasuk pengemudinya.
     * Itulah sebabnya koneksi demo tidak membaca DB_URL sama sekali.
     */
    public function test_it_refuses_when_a_url_turns_the_connection_into_postgres(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        config(['database.connections.demo.url' => 'pgsql://contoh.test/produksi']);

        $this->artisan('memount:demo')->expectsOutputToContain('bukan SQLite')->assertFailed();
    }

    public function test_it_refuses_a_file_outside_the_database_folder(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        config(['database.connections.demo.database' => sys_get_temp_dir().DIRECTORY_SEPARATOR.'demo.sqlite']);

        $this->artisan('memount:demo')->expectsOutputToContain('di luar folder database/')->assertFailed();
    }

    public function test_it_refuses_an_in_memory_database(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        config(['database.connections.demo.database' => ':memory:']);

        $this->artisan('memount:demo')->expectsOutputToContain('di luar folder database/')->assertFailed();
    }

    public function test_it_refuses_a_path_that_climbs_out_of_the_database_folder(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        config(['database.connections.demo.database' => database_path('..'.DIRECTORY_SEPARATOR.'demo.sqlite')]);

        $this->artisan('memount:demo')->expectsOutputToContain('di luar folder database/')->assertFailed();
    }

    /**
     * Koneksi demo ditulis tetap di config/database.php: tidak ada kunci yang membaca .env,
     * karena DB_URL dan DB_DATABASE di sana menunjuk basis data sungguhan.
     */
    public function test_the_demo_connection_never_reads_the_environment(): void
    {
        $this->assertSame(1, preg_match("/'demo' => \[(.*?)\n        \],/s", File::get(config_path('database.php')), $blok));
        $this->assertStringContainsString("'driver' => 'sqlite',", $blok[1]);
        $this->assertStringContainsString("'url' => null,", $blok[1]);
        $this->assertStringContainsString("'database' => database_path('demo.sqlite'),", $blok[1]);
        $this->assertStringNotContainsString('env(', $blok[1], 'Koneksi demo tidak membaca .env sama sekali.');
    }

    public function test_the_guard_accepts_only_a_local_sqlite_file_inside_the_database_folder(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        $this->assertSame($this->berkasUji, (new PenjagaDataContoh)->periksa());
        $this->assertFileDoesNotExist($this->berkasUji, 'Penjaga hanya membaca; ia tidak membuat berkas.');
    }
}
```

Ganti seluruh isi `tests/Feature/DemoSeederTest.php` dengan:

```php
<?php

namespace Tests\Feature;

use App\Enums\TripStatus;
use App\Exceptions\DataContohDitolak;
use App\Models\DataSource;
use App\Models\HikingHistory;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Support\Timezone;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Data contoh berlabel (DK-35). Dijalankan di SQLite dalam memori milik suite, tidak di
 * berkas atau basis data mana pun.
 */
class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Isinya meniru cerita di prototype pemilik: Budi, trip "Gede via Cibodas" lima belas
     * hari lagi, tiga pendakian selesai, satu jalur menunggu data.
     */
    public function test_the_sample_tells_the_prototype_story(): void
    {
        $this->seed(DemoSeeder::class);

        $budi = User::where('email', DemoSeeder::EMAIL_PENDAKI)->firstOrFail();
        $trip = TripPlan::where('user_id', $budi->id)->where('status', TripStatus::PLANNED->value)->sole();

        $this->assertSame('Budi Setiawan', $budi->name);
        $this->assertTrue($budi->hasCompletedProfile());
        $this->assertSame('Gede via Cibodas', $trip->name);
        $this->assertSame(Carbon::parse(Timezone::earliestDateInIndonesia())->addDays(15)->toDateString(), $trip->planned_date->toDateString());
        $this->assertSame(8, Mountain::count());
        $this->assertSame(10, Trail::where('is_published', true)->count());
        $this->assertSame(1, Trail::where('is_published', false)->count());
        $this->assertSame(1, TripPlan::where('status', TripStatus::CANCELLED->value)->count());
        $this->assertSame(3, HikingHistory::where('user_id', $budi->id)->count());
    }

    /**
     * Setiap teks yang dapat terbaca sebagai fakta membawa penanda DATA CONTOH.
     */
    public function test_every_text_that_reads_as_fact_is_marked(): void
    {
        $this->seed(DemoSeeder::class);

        $tanpaPenanda = [
            ...DataSource::pluck('source_name')->reject(fn (string $t): bool => str_starts_with($t, DemoSeeder::PENANDA))->all(),
            ...Mountain::pluck('description')->reject(fn (string $t): bool => str_starts_with($t, DemoSeeder::PENANDA.'.'))->all(),
            ...Trail::pluck('description')->reject(fn (string $t): bool => str_starts_with($t, DemoSeeder::PENANDA.'.'))->all(),
            ...OfficialStatus::pluck('source')->reject(fn (string $t): bool => str_starts_with($t, DemoSeeder::PENANDA))->all(),
            ...TripPlan::whereNotNull('notes')->pluck('notes')->reject(fn (string $t): bool => str_starts_with($t, DemoSeeder::PENANDA.'.'))->all(),
        ];

        $this->assertSame([], $tanpaPenanda);
    }

    public function test_it_refuses_a_database_that_is_not_sqlite(): void
    {
        $bawaan = DB::getDefaultConnection();
        DB::setDefaultConnection('pgsql');

        try {
            $this->expectException(DataContohDitolak::class);
            $this->expectExceptionMessage('SQLite');

            $this->app->make(DemoSeeder::class)->run();
        } finally {
            DB::setDefaultConnection($bawaan);
        }
    }

    public function test_it_refuses_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->expectException(DataContohDitolak::class);
        $this->expectExceptionMessage('produksi');

        $this->app->make(DemoSeeder::class)->run();
    }

    public function test_the_regular_seeder_never_plants_sample_data(): void
    {
        $this->assertStringNotContainsString('DemoSeeder', File::get(database_path('seeders/DatabaseSeeder.php')));
    }

    /**
     * Kata sandi pendaki contoh diacak setiap kali dan hanya dicetak ke konsol; tidak ada
     * kata sandi yang tertulis di repo (DatabaseSeeder lama memakai "password", HANDOVER
     * bagian 0 butir 10).
     */
    public function test_the_sample_password_is_random_and_never_written_down(): void
    {
        $this->seed(DemoSeeder::class);

        $budi = User::where('email', DemoSeeder::EMAIL_PENDAKI)->firstOrFail();
        $sumber = File::get(database_path('seeders/DemoSeeder.php'));

        $this->assertFalse(Hash::check('password', $budi->password));
        $this->assertStringContainsString('Str::password(20, symbols: false)', $sumber);
        $this->assertDoesNotMatchRegularExpression("/'password' => '[^']+'/", $sumber);
    }
}
```

Ganti seluruh isi `tests/Feature/DemoBadgeTest.php` dengan:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Lencana "Data contoh" (DK-35, antislop R-38): data contoh tidak pernah terbaca sebagai
 * data sungguhan. Mode ini mati kecuali diminta lewat APP_DEMO.
 */
class DemoBadgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_badge_prop_follows_the_demo_setting(): void
    {
        $budi = User::factory()->create();
        $budi->profile()->create(['experience_level' => 'BEGINNER', 'completed_at' => now()]);

        config(['app.demo' => false]);
        $this->actingAs($budi)->get('/dashboard')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->where('demo', false));

        config(['app.demo' => true]);
        $this->actingAs($budi)->get('/dashboard')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->where('demo', true));
    }

    public function test_every_app_layout_mounts_the_badge(): void
    {
        foreach (['js/layouts/AppLayout.tsx', 'js/components/trips/TataLetakPendakian.tsx'] as $berkas) {
            $isi = File::get(resource_path($berkas));

            $this->assertStringContainsString("import { LencanaDataContoh } from '@/components/ui/LencanaDataContoh';", $isi, $berkas);
            $this->assertStringContainsString('<LencanaDataContoh />', $isi, $berkas);
        }

        $lencana = File::get(resource_path('js/components/ui/LencanaDataContoh.tsx'));

        $this->assertMatchesRegularExpression('/if \(!demo\) \{\s*return null;\s*\}/', $lencana);
        $this->assertStringContainsString('<Lencana nada="netral" garis="putus">', $lencana);
        $this->assertStringContainsString('<span className="sr-only">: isi halaman ini bukan data sungguhan</span>', $lencana);
    }

    public function test_the_setting_is_off_unless_asked_for(): void
    {
        $this->assertMatchesRegularExpression('/^APP_DEMO=false$/m', File::get(base_path('.env.example')));
        $this->assertStringContainsString("'demo' => (bool) env('APP_DEMO', false),", File::get(config_path('app.php')));
    }
}
```

- [ ] **Step 2: Jalankan dan pastikan gagal.**

Run: `php artisan test --compact --filter='DemoCommandGuardTest|DemoSeederTest|DemoBadgeTest'`
Expected: FAIL. Pada simulasi rencana: 17 test, 1 lulus, 4 gagal, 12 error (perintah, penjaga, seeder, dan lencana belum
ada).

- [ ] **Step 3: Koneksi dan saklar data contoh.** Di `config/database.php`, ganti akhir koneksi `sqlite` dan awal `mysql`:

```php
            'transaction_mode' => 'DEFERRED',
        ],

        'mysql' => [
```

dengan:

```php
            'transaction_mode' => 'DEFERRED',
        ],

        /*
         * Data contoh lokal (DK-35): berkas SQLite database/demo.sqlite yang hanya diisi
         * `php artisan memount:demo` atas keputusan pemilik (Q1). Jalurnya ditulis tetap
         * dan 'url' sengaja null, bukan env('DB_URL'): DB_URL di .env menunjuk basis data
         * sungguhan, dan URL mengalahkan setiap kunci lain di koneksi ini, termasuk
         * pengemudinya. PenjagaDataContoh memeriksa ulang pengemudi dan jalurnya sebelum
         * apa pun ditulis.
         */
        'demo' => [
            'driver' => 'sqlite',
            'url' => null,
            'database' => database_path('demo.sqlite'),
            'prefix' => '',
            'foreign_key_constraints' => true,
            'busy_timeout' => null,
            'journal_mode' => null,
            'synchronous' => null,
            'transaction_mode' => 'DEFERRED',
        ],

        'mysql' => [
```

Di `config/app.php`, ganti:

```php
    'env' => env('APP_ENV', 'production'),
```

dengan:

```php
    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Mode Data Contoh
    |--------------------------------------------------------------------------
    |
    | Benar hanya ketika aplikasi lokal menyajikan data contoh database/demo.sqlite
    | (DK-35). Setiap layout memasang lencana "Data contoh" dari nilai ini supaya data
    | contoh tidak pernah terbaca sebagai data sungguhan. Bawaannya false.
    |
    */

    'demo' => (bool) env('APP_DEMO', false),
```

Di `.env.example`, ganti:

```dotenv
APP_URL=http://localhost:8000
```

dengan:

```dotenv
APP_URL=http://localhost:8000

# Data contoh lokal (DK-35): true hanya saat menyajikan database/demo.sqlite dengan
# DB_CONNECTION=demo. Setiap halaman lalu memasang lencana "Data contoh".
APP_DEMO=false
```

- [ ] **Step 4: Pengecualian dan penjaga.** Buat `app/Exceptions/DataContohDitolak.php`:

```php
<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Data contoh (DK-35) menolak ditulis ke mana pun selain berkas SQLite lokal di folder
 * database/. Dilempar PenjagaDataContoh dan DemoSeeder sebelum satu baris pun ditulis.
 */
final class DataContohDitolak extends RuntimeException {}
```

Buat `app/Support/PenjagaDataContoh.php`:

```php
<?php

namespace App\Support;

use App\Exceptions\DataContohDitolak;
use Illuminate\Support\Facades\DB;

/**
 * Penjaga data contoh (DK-35, ruling W1-R2).
 *
 * `.env` repo ini menunjuk Supabase produksi, jadi perintah yang mengosongkan lalu mengisi
 * basis data wajib membuktikan dulu bahwa sasarannya berkas SQLite lokal. Tiga syarat
 * diperiksa berurutan, sebelum apa pun ditulis:
 *
 * 1. APP_ENV=local, bukan testing, staging, atau produksi;
 * 2. koneksi demo benar-benar SQLite, tidak diubah URL atau pengemudi lain;
 * 3. berkasnya di dalam folder database/ dan berakhiran .sqlite, bukan :memory: dan bukan
 *    jalur yang memanjat keluar lewat "..".
 *
 * Penjaga hanya membaca konfigurasi; ia tidak membuat berkas dan tidak membuka koneksi.
 */
final class PenjagaDataContoh
{
    public const KONEKSI = 'demo';

    /**
     * @return string Jalur berkas SQLite data contoh.
     *
     * @throws DataContohDitolak
     */
    public function periksa(): string
    {
        if (! app()->environment('local')) {
            throw new DataContohDitolak('Data contoh hanya boleh diisi di APP_ENV=local; lingkungan sekarang '.app()->environment().'.');
        }

        DB::purge(self::KONEKSI);

        $koneksi = DB::connection(self::KONEKSI);
        $pengemudi = $koneksi->getDriverName();

        if ($pengemudi !== 'sqlite') {
            throw new DataContohDitolak("Koneksi demo bukan SQLite ({$pengemudi}); data contoh tidak ditulis ke basis data lain.");
        }

        $berkas = (string) $koneksi->getConfig('database');
        $folder = realpath(database_path());
        $induk = $berkas === '' || $berkas === ':memory:' ? false : realpath(dirname($berkas));

        if ($folder === false || $induk !== $folder || ! str_ends_with($berkas, '.sqlite')) {
            throw new DataContohDitolak("Berkas demo di luar folder database/ ditolak: {$berkas}.");
        }

        return $berkas;
    }
}
```

- [ ] **Step 5: Perintah `memount:demo`.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan make:command MemountDemo --no-interaction`
Expected: `INFO  Console command [E:\praktik_aplikasi_web\app\Console\Commands\MemountDemo.php] created successfully.`

Ganti seluruh isinya dengan berkas di bawah. Jangan menjalankan perintah ini: menjalankannya adalah keputusan pemilik
(Q1). Perintah yang dicetaknya menyajikan data contoh tanpa menyentuh basis data, sesi, cache, antrean, berkas, atau
surel yang ditunjuk `.env`.

```php
<?php

namespace App\Console\Commands;

use App\Exceptions\DataContohDitolak;
use App\Support\PenjagaDataContoh;
use Database\Seeders\DemoSeeder;
use Illuminate\Console\Command;

/**
 * Mengosongkan lalu mengisi database/demo.sqlite dengan data contoh berlabel (DK-35).
 *
 * Dijalankan pemilik produk atas keputusan Q1, tidak oleh agen: rencana W1 menulis dan
 * menguji penjaganya tanpa menjalankannya. Penjaga menolak apa pun selain berkas SQLite
 * lokal di database/ pada APP_ENV=local, sebelum satu tabel pun disentuh.
 */
class MemountDemo extends Command
{
    protected $signature = 'memount:demo';

    protected $description = 'Mengosongkan lalu mengisi database/demo.sqlite dengan data contoh berlabel (hanya APP_ENV=local)';

    public function handle(PenjagaDataContoh $penjaga): int
    {
        try {
            $berkas = $penjaga->periksa();
        } catch (DataContohDitolak $galat) {
            $this->error($galat->getMessage());

            return self::FAILURE;
        }

        if (! is_file($berkas)) {
            touch($berkas);
        }

        $this->call('migrate:fresh', ['--database' => PenjagaDataContoh::KONEKSI, '--force' => true]);
        $this->call('db:seed', ['--database' => PenjagaDataContoh::KONEKSI, '--class' => DemoSeeder::class, '--force' => true]);

        $this->newLine();
        $this->info("Data contoh siap di {$berkas}.");
        $this->line('Sajikan tanpa menyentuh basis data, sesi, cache, antrean, berkas, atau surel di .env. Dari Git Bash:');
        $this->line('  DB_CONNECTION=demo APP_DEMO=true SESSION_DRIVER=file CACHE_STORE=file QUEUE_CONNECTION=sync FILESYSTEM_DISK=local MAIL_MAILER=log php artisan serve');
        $this->line('Dari PowerShell:');
        $this->line('  $env:DB_CONNECTION="demo"; $env:APP_DEMO="true"; $env:SESSION_DRIVER="file"; $env:CACHE_STORE="file"; $env:QUEUE_CONNECTION="sync"; $env:FILESYSTEM_DISK="local"; $env:MAIL_MAILER="log"; php artisan serve');

        return self::SUCCESS;
    }
}
```

- [ ] **Step 6: Seeder data contoh.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan make:seeder DemoSeeder --no-interaction`
Expected: `INFO  Seeder [E:\praktik_aplikasi_web\database\seeders\DemoSeeder.php] created successfully.`

Ganti seluruh isinya dengan berkas di bawah. Jalur diterbitkan lewat `update(['is_published' => true])` sesudah pos dan
status resminya ada, sehingga gerbang publikasi model yang memeriksanya; kolomnya tidak ditulis langsung saat membuat.

```php
<?php

namespace Database\Seeders;

use App\Enums\CheckpointType;
use App\Enums\CompletionState;
use App\Enums\ExperienceLevel;
use App\Enums\NavigationComplexity;
use App\Enums\NavigationExperience;
use App\Enums\OfficialStatusValue;
use App\Enums\PreferredChallenge;
use App\Enums\PreferredDuration;
use App\Enums\SourceType;
use App\Enums\StatusScope;
use App\Enums\TechnicalDemand;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Enums\VerificationStatus;
use App\Enums\WaterAvailability;
use App\Exceptions\DataContohDitolak;
use App\Models\Checkpoint;
use App\Models\DataSource;
use App\Models\HikingHistory;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Support\Timezone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Data contoh berlabel (DK-35, spec v3 bagian 11).
 *
 * Satu pendaki pemula, Budi Setiawan; delapan gunung; sebelas jalur (sepuluh terbit, satu
 * menunggu data); trip "Gede via Cibodas" lima belas hari lagi; tiga pendakian selesai; satu
 * trip batal. Isinya meniru contoh di prototype pemilik supaya tangkapan pratinjau dapat
 * dibandingkan dengan gambar acuannya: tanjakan tertinggi Budi 700 m dari Prau, sehingga
 * Cibodas (1.650 m) menjadi tanjakan terbesarnya.
 *
 * Setiap teks yang dapat terbaca sebagai fakta diawali "DATA CONTOH", dan layout memasang
 * lencana "Data contoh" lewat APP_DEMO. Geometri jalur tidak diisi: kolom geografi hanya
 * ada di PostGIS, sedangkan data contoh wajib SQLite (W1-K10). Kata sandi pendaki contoh
 * diacak setiap kali dan hanya dicetak ke konsol.
 *
 * Jalan masuknya `php artisan memount:demo` (penjaga berkas di PenjagaDataContoh) atau
 * suite PHPUnit Pratinjau (SQLite dalam memori). DatabaseSeeder tidak memanggilnya.
 */
class DemoSeeder extends Seeder
{
    public const EMAIL_PENDAKI = 'budi@contoh.test';

    public const PENANDA = 'DATA CONTOH';

    /**
     * Gunung: nama, provinsi, zona waktu, ketinggian, lintang, bujur. Koordinat Gede,
     * Pangrango, Papandayan, Prau, dan Rinjani sama dengan MountainSeeder; Batu tanpa
     * koordinat.
     *
     * @var array<string, array{0: string, 1: string, 2: string, 3: int, 4: float|null, 5: float|null}>
     */
    private const GUNUNG = [
        'gede' => ['Gunung Gede', 'Jawa Barat', 'Asia/Jakarta', 2958, -6.78833, 106.98167],
        'pangrango' => ['Gunung Pangrango', 'Jawa Barat', 'Asia/Jakarta', 3019, -6.76667, 106.95000],
        'papandayan' => ['Gunung Papandayan', 'Jawa Barat', 'Asia/Jakarta', 2665, -7.32897, 107.71570],
        'batu' => ['Gunung Batu', 'Jawa Barat', 'Asia/Jakarta', 875, null, null],
        'prau' => ['Gunung Prau', 'Jawa Tengah', 'Asia/Jakarta', 2590, -7.18694, 109.92278],
        'andong' => ['Gunung Andong', 'Jawa Tengah', 'Asia/Jakarta', 1726, -7.39040, 110.37060],
        'batur' => ['Gunung Batur', 'Bali', 'Asia/Makassar', 1717, -8.24190, 115.37530],
        'rinjani' => ['Gunung Rinjani', 'Nusa Tenggara Barat', 'Asia/Makassar', 3726, -8.41667, 116.46667],
    ];

    public function run(): void
    {
        $pengemudi = DB::connection()->getDriverName();

        if ($pengemudi !== 'sqlite') {
            throw new DataContohDitolak("Data contoh hanya ditulis ke SQLite; koneksi bawaan sekarang {$pengemudi}.");
        }

        if (app()->environment('production')) {
            throw new DataContohDitolak('Data contoh tidak pernah ditulis di lingkungan produksi.');
        }

        $this->call([RecommendationRuleSeeder::class, PreparationTemplateSeeder::class]);

        $sumber = DataSource::create([
            'source_name' => self::PENANDA.', bukan sumber resmi',
            'source_type' => SourceType::ADMIN_VERIFIED->value,
            'source_owner' => 'Tim Memount',
            'retrieved_at' => now(),
            'verified_at' => now(),
            'freshness_policy' => 'Data contoh untuk peninjauan tampilan lokal; tidak pernah dipakai di produksi.',
            'verification_status' => VerificationStatus::UNVERIFIED->value,
            'notes' => self::PENANDA.'. Nilai jalur, pos, dan status dibuat untuk peninjauan, bukan diambil dari pengelola.',
        ]);

        $budi = $this->pendaki();
        $jalur = $this->jalur($sumber);
        $this->trip($budi, $jalur);
    }

    private function pendaki(): User
    {
        $kataSandi = Str::password(20, symbols: false);

        $budi = User::create(['name' => 'Budi Setiawan', 'email' => self::EMAIL_PENDAKI, 'password' => $kataSandi]);
        $budi->forceFill(['email_verified_at' => now()])->save();

        $budi->profile()->create([
            'experience_level' => ExperienceLevel::BEGINNER->value,
            'region_preference' => 'Jawa Barat',
            'completed_at' => now(),
        ]);

        $budi->experience()->create([
            'completed_hikes_count' => 3,
            'terrain_experience' => ['FOREST', 'ROCKY'],
            'navigation_experience' => NavigationExperience::BASIC->value,
            'longest_hike_duration_minutes' => 300,
            'highest_elevation_gain_m' => 700,
        ]);

        $budi->preference()->create([
            'preferred_duration' => PreferredDuration::TWO_DAY->value,
            'preferred_trip_type' => TripType::CAMPING->value,
            'preferred_challenge' => PreferredChallenge::MODERATE->value,
            'region_preference' => 'Jawa Barat, Jawa Tengah',
            'record_track' => false,
        ]);

        $this->command?->info('Pendaki contoh: '.self::EMAIL_PENDAKI." dengan kata sandi {$kataSandi} (hanya untuk berkas lokal ini).");

        return $budi;
    }

    /**
     * @return array<string, Trail>
     */
    private function jalur(DataSource $sumber): array
    {
        $gunung = [];

        foreach (self::GUNUNG as $kunci => [$nama, $provinsi, $zona, $tinggi, $lintang, $bujur]) {
            $gunung[$kunci] = Mountain::create([
                'name' => $nama,
                'slug' => Str::slug($nama),
                'province' => $provinsi,
                'region' => $provinsi,
                'timezone' => $zona,
                'elevation_mdpl' => $tinggi,
                'latitude' => $lintang,
                'longitude' => $bujur,
                'description' => self::PENANDA.". {$nama} di {$provinsi}, dipakai untuk meninjau tampilan.",
                'data_source_id' => $sumber->id,
            ]);
        }

        $jalur = [];

        foreach ($this->dataJalur() as $slug => $data) {
            $trail = Trail::create([
                'mountain_id' => $gunung[$data['gunung']]->id,
                'name' => $data['nama'],
                'slug' => $slug,
                'description' => self::PENANDA.'. '.$data['keterangan'],
                'distance_km' => $data['jarak'],
                'elevation_gain_m' => $data['naik'],
                'elevation_loss_m' => $data['naik'],
                'elevation_profile' => $data['profil'] ?? null,
                'estimated_duration_minutes' => $data['menit'],
                'technical_demand' => $data['teknis']->value,
                'navigation_complexity' => $data['navigasi']->value,
                'water_availability' => $data['air']->value,
                'terrain_character' => $data['medan'],
                'camping_available' => $data['kemah'],
                'starting_point' => $data['awal'],
                'data_source_id' => $sumber->id,
            ]);

            $jalur[$slug] = $trail;

            if ($data['status'] === null) {
                continue;
            }

            foreach ($data['pos'] as $urutan => [$nama, $jenis, $tinggi]) {
                Checkpoint::create([
                    'trail_id' => $trail->id,
                    'sequence' => $urutan + 1,
                    'name' => $nama,
                    'checkpoint_type' => $jenis->value,
                    'elevation_m' => $tinggi,
                ]);
            }

            OfficialStatus::create([
                'statusable_type' => $trail->getMorphClass(),
                'statusable_id' => $trail->id,
                'scope' => StatusScope::TRAIL->value,
                'status' => $data['status']->value,
                'data_source_id' => $sumber->id,
                'source' => self::PENANDA.', bukan status resmi',
                'published_at' => now()->subDays(8),
                'fetched_at' => now()->subDays(8),
                'effective_at' => now()->subDays(8),
                'verified_at' => now()->subDays(7),
                'reason' => $data['alasan'] ?? null,
            ]);

            // Menerbitkan lewat gerbang publikasi model, bukan menulis kolomnya langsung.
            $trail->refresh()->update(['is_published' => true]);
        }

        return $jalur;
    }

    /**
     * @param  array<string, Trail>  $jalur
     */
    private function trip(User $budi, array $jalur): void
    {
        $hariIni = Carbon::parse(Timezone::earliestDateInIndonesia());

        TripPlan::create([
            'user_id' => $budi->id,
            'trail_id' => $jalur['gede-via-cibodas']->id,
            'name' => 'Gede via Cibodas',
            'planned_date' => $hariIni->copy()->addDays(15)->toDateString(),
            'end_date' => $hariIni->copy()->addDays(16)->toDateString(),
            'start_time' => '07:00',
            'participants_count' => 3,
            'trip_type' => TripType::CAMPING->value,
            'notes' => self::PENANDA.'. Berangkat bersama Dimas dan Sari; kumpul di Cibodas Jumat malam.',
            'reminders_enabled' => true,
            'status' => TripStatus::PLANNED->value,
        ]);

        foreach ([['andong-via-sawit', 120, TripType::TEKTOK], ['batur-via-toya-bungkah', 75, TripType::TEKTOK], ['prau-via-wates', 40, TripType::CAMPING]] as [$slug, $hariLalu, $jenis]) {
            $tanggal = $hariIni->copy()->subDays($hariLalu);
            $selesai = $tanggal->copy()->setTime(16, 0);

            $trip = TripPlan::create([
                'user_id' => $budi->id,
                'trail_id' => $jalur[$slug]->id,
                'name' => $jalur[$slug]->name,
                'planned_date' => $tanggal->toDateString(),
                'trip_type' => $jenis->value,
                'status' => TripStatus::COMPLETED->value,
                'completed_at' => $selesai,
            ]);

            HikingHistory::create([
                'user_id' => $budi->id,
                'trip_plan_id' => $trip->id,
                'trail_id' => $jalur[$slug]->id,
                'trip_type' => $jenis->value,
                'completion_state' => CompletionState::COMPLETED->value,
                'completed_at' => $selesai,
            ]);
        }

        TripPlan::create([
            'user_id' => $budi->id,
            'trail_id' => $jalur['pangrango-via-cibodas']->id,
            'name' => 'Pangrango akhir pekan',
            'planned_date' => $hariIni->copy()->subDays(20)->toDateString(),
            'trip_type' => TripType::CAMPING->value,
            'notes' => self::PENANDA.'. Dibatalkan karena cuaca.',
            'status' => TripStatus::CANCELLED->value,
        ]);
    }

    /**
     * Sebelas jalur. Jalur dengan status null belum punya pos dan status resmi, jadi tetap
     * tidak terbit dan tampil sebagai jalur yang datanya belum tersedia.
     *
     * @return array<string, array<string, mixed>>
     */
    private function dataJalur(): array
    {
        return [
            'gede-via-cibodas' => [
                'gunung' => 'gede', 'nama' => 'Gede via Cibodas', 'jarak' => 9.7, 'naik' => 1650, 'menit' => 450,
                'teknis' => TechnicalDemand::MODERATE, 'navigasi' => NavigationComplexity::MODERATE, 'air' => WaterAvailability::LIMITED,
                'medan' => ['FOREST', 'ROCKY', 'STEEP_SLOPE'], 'kemah' => true, 'awal' => 'Pintu Cibodas',
                'keterangan' => 'Jalur lewat Telaga Biru, aliran air panas, dan Kandang Badak.',
                'status' => OfficialStatusValue::OPEN,
                'pos' => [
                    ['Pintu Cibodas', CheckpointType::BASECAMP, 1340],
                    ['Telaga Biru', CheckpointType::POS, 1575],
                    ['Panyancangan', CheckpointType::JUNCTION, 1620],
                    ['Air Panas', CheckpointType::POS, 2150],
                    ['Kandang Badak', CheckpointType::CAMPGROUND, 2395],
                    ['Puncak Gede', CheckpointType::SUMMIT, 2958],
                ],
                'profil' => [
                    ['km' => 0, 'm' => 1340], ['km' => 1.5, 'm' => 1575], ['km' => 2.8, 'm' => 1620],
                    ['km' => 5.3, 'm' => 2150], ['km' => 7.2, 'm' => 2395], ['km' => 9.7, 'm' => 2958],
                ],
            ],
            'gede-via-gunung-putri' => [
                'gunung' => 'gede', 'nama' => 'Gede via Gunung Putri', 'jarak' => 7.2, 'naik' => 1720, 'menit' => 390,
                'teknis' => TechnicalDemand::MODERATE, 'navigasi' => NavigationComplexity::LOW, 'air' => WaterAvailability::SCARCE,
                'medan' => ['FOREST', 'STEEP_SLOPE'], 'kemah' => true, 'awal' => 'Basecamp Gunung Putri',
                'keterangan' => 'Jalur pendek yang menanjak terus sampai Alun-alun Surya Kencana.',
                'status' => OfficialStatusValue::OPEN,
                'pos' => [
                    ['Basecamp Gunung Putri', CheckpointType::BASECAMP, 1450],
                    ['Alun-alun Surya Kencana', CheckpointType::CAMPGROUND, 2750],
                    ['Puncak Gede', CheckpointType::SUMMIT, 2958],
                ],
            ],
            'gede-via-selabintana' => [
                'gunung' => 'gede', 'nama' => 'Gede via Selabintana', 'jarak' => 11.8, 'naik' => 1900, 'menit' => 660,
                'teknis' => TechnicalDemand::HIGH, 'navigasi' => NavigationComplexity::HIGH, 'air' => WaterAvailability::LIMITED,
                'medan' => ['FOREST', 'MUD', 'STEEP_SLOPE'], 'kemah' => true, 'awal' => 'Pintu Selabintana',
                'keterangan' => 'Jalur terpanjang ke Gede dari sisi Sukabumi.',
                'status' => OfficialStatusValue::RESTRICTED, 'alasan' => 'Perbaikan jembatan',
                'pos' => [
                    ['Pintu Selabintana', CheckpointType::BASECAMP, 1100],
                    ['Alun-alun Surya Kencana', CheckpointType::CAMPGROUND, 2750],
                    ['Puncak Gede', CheckpointType::SUMMIT, 2958],
                ],
            ],
            'pangrango-via-cibodas' => [
                'gunung' => 'pangrango', 'nama' => 'Pangrango via Cibodas', 'jarak' => 11.2, 'naik' => 1680, 'menit' => 510,
                'teknis' => TechnicalDemand::HIGH, 'navigasi' => NavigationComplexity::MODERATE, 'air' => WaterAvailability::LIMITED,
                'medan' => ['FOREST', 'STEEP_SLOPE'], 'kemah' => true, 'awal' => 'Pintu Cibodas',
                'keterangan' => 'Berpisah dari jalur Gede di Kandang Badak, lalu hutan rapat sampai puncak.',
                'status' => OfficialStatusValue::OPEN,
                'pos' => [
                    ['Pintu Cibodas', CheckpointType::BASECAMP, 1340],
                    ['Kandang Badak', CheckpointType::CAMPGROUND, 2395],
                    ['Puncak Pangrango', CheckpointType::SUMMIT, 3019],
                ],
            ],
            'papandayan-via-camp-david' => [
                'gunung' => 'papandayan', 'nama' => 'Papandayan via Camp David', 'jarak' => 7.4, 'naik' => 610, 'menit' => 270,
                'teknis' => TechnicalDemand::LOW, 'navigasi' => NavigationComplexity::LOW, 'air' => WaterAvailability::ABUNDANT,
                'medan' => ['ROCKY', 'FOREST'], 'kemah' => true, 'awal' => 'Camp David',
                'keterangan' => 'Melewati kawah lalu padang Pondok Saladah.',
                'status' => OfficialStatusValue::OPEN,
                'pos' => [
                    ['Camp David', CheckpointType::BASECAMP, 2050],
                    ['Pondok Saladah', CheckpointType::CAMPGROUND, 2288],
                    ['Tegal Alun', CheckpointType::POS, 2600],
                ],
            ],
            'batu-via-jonggol' => [
                'gunung' => 'batu', 'nama' => 'Batu via Jonggol', 'jarak' => 1.6, 'naik' => 280, 'menit' => 90,
                'teknis' => TechnicalDemand::LOW, 'navigasi' => NavigationComplexity::LOW, 'air' => WaterAvailability::SCARCE,
                'medan' => ['ROCKY'], 'kemah' => false, 'awal' => 'Basecamp Gunung Batu',
                'keterangan' => 'Pendakian singkat ke puncak batu di Bogor timur.',
                'status' => OfficialStatusValue::OPEN,
                'pos' => [
                    ['Basecamp Gunung Batu', CheckpointType::BASECAMP, 600],
                    ['Puncak Gunung Batu', CheckpointType::SUMMIT, 875],
                ],
            ],
            'prau-via-wates' => [
                'gunung' => 'prau', 'nama' => 'Prau via Wates', 'jarak' => 6.1, 'naik' => 700, 'menit' => 270,
                'teknis' => TechnicalDemand::LOW, 'navigasi' => NavigationComplexity::MODERATE, 'air' => WaterAvailability::LIMITED,
                'medan' => ['FOREST', 'SAVANNA'], 'kemah' => true, 'awal' => 'Basecamp Wates',
                'keterangan' => 'Jalur hutan yang lebih sepi menuju sabana Prau.',
                'status' => OfficialStatusValue::OPEN,
                'pos' => [
                    ['Basecamp Wates', CheckpointType::BASECAMP, 1890],
                    ['Puncak Prau', CheckpointType::SUMMIT, 2590],
                ],
            ],
            'andong-via-sawit' => [
                'gunung' => 'andong', 'nama' => 'Andong via Sawit', 'jarak' => 2.8, 'naik' => 380, 'menit' => 120,
                'teknis' => TechnicalDemand::LOW, 'navigasi' => NavigationComplexity::LOW, 'air' => WaterAvailability::SCARCE,
                'medan' => ['FOREST', 'EXPOSED_RIDGE'], 'kemah' => true, 'awal' => 'Basecamp Sawit',
                'keterangan' => 'Pendakian pendek ke punggungan terbuka.',
                'status' => OfficialStatusValue::OPEN,
                'pos' => [
                    ['Basecamp Sawit', CheckpointType::BASECAMP, 1340],
                    ['Puncak Andong', CheckpointType::SUMMIT, 1726],
                ],
            ],
            'batur-via-toya-bungkah' => [
                'gunung' => 'batur', 'nama' => 'Batur via Toya Bungkah', 'jarak' => 4.6, 'naik' => 600, 'menit' => 150,
                'teknis' => TechnicalDemand::LOW, 'navigasi' => NavigationComplexity::LOW, 'air' => WaterAvailability::SCARCE,
                'medan' => ['SAND', 'ROCKY'], 'kemah' => false, 'awal' => 'Toya Bungkah',
                'keterangan' => 'Pendakian dini hari di pasir vulkanik.',
                'status' => OfficialStatusValue::OPEN,
                'pos' => [
                    ['Toya Bungkah', CheckpointType::BASECAMP, 1100],
                    ['Puncak Batur', CheckpointType::SUMMIT, 1717],
                ],
            ],
            'rinjani-via-sembalun' => [
                'gunung' => 'rinjani', 'nama' => 'Rinjani via Sembalun', 'jarak' => 22.5, 'naik' => 2600, 'menit' => 1500,
                'teknis' => TechnicalDemand::HIGH, 'navigasi' => NavigationComplexity::MODERATE, 'air' => WaterAvailability::LIMITED,
                'medan' => ['SAVANNA', 'SCREE', 'EXPOSED_RIDGE'], 'kemah' => true, 'awal' => 'Pintu Sembalun',
                'keterangan' => 'Sabana panjang lalu pasir berbatu ke puncak.',
                'status' => OfficialStatusValue::CLOSED, 'alasan' => 'Penutupan musiman',
                'pos' => [
                    ['Pintu Sembalun', CheckpointType::BASECAMP, 1150],
                    ['Plawangan Sembalun', CheckpointType::CAMPGROUND, 2639],
                    ['Puncak Rinjani', CheckpointType::SUMMIT, 3726],
                ],
            ],
            'rinjani-via-senaru' => [
                'gunung' => 'rinjani', 'nama' => 'Rinjani via Senaru', 'jarak' => null, 'naik' => null, 'menit' => null,
                'teknis' => TechnicalDemand::MODERATE, 'navigasi' => NavigationComplexity::MODERATE, 'air' => WaterAvailability::UNKNOWN,
                'medan' => null, 'kemah' => false, 'awal' => null,
                'keterangan' => 'Jalur yang dikenali sistem tetapi datanya belum dimasukkan pengelola.',
                'status' => null,
            ],
        ];
    }
}
```

- [ ] **Step 7: Lencana Data contoh di setiap layout aplikasi.** Di `app/Http/Middleware/HandleInertiaRequests.php`, ganti:

```php
            'merek' => [
                'nama' => config('app.name'),
                'tagline' => config('brand.tagline'),
            ],
```

dengan:

```php
            'merek' => [
                'nama' => config('app.name'),
                'tagline' => config('brand.tagline'),
            ],

            // Data contoh (DK-35): layout memasang lencana "Data contoh" bila benar.
            'demo' => (bool) config('app.demo'),
```

Di `resources/js/types/index.d.ts`, ganti `    merek: { nama: string; tagline: string };` dengan:

```ts
    merek: { nama: string; tagline: string };
    demo: boolean;
```

Buat `resources/js/components/ui/LencanaDataContoh.tsx`:

```tsx
import { usePage } from '@inertiajs/react';
import type { PropsBersama } from '@/types';
import { Lencana } from './Lencana';

/**
 * Lencana "Data contoh" (DK-35, antislop R-38): tampil di setiap halaman ketika aplikasi
 * menyajikan data contoh (APP_DEMO=true), supaya data contoh tidak pernah terbaca sebagai
 * data sungguhan. Tanpa mode data contoh ia tidak menggambar apa pun.
 */
export function LencanaDataContoh() {
    const { demo } = usePage<PropsBersama>().props;

    if (!demo) {
        return null;
    }

    return (
        <Lencana nada="netral" garis="putus">
            Data contoh
            <span className="sr-only">: isi halaman ini bukan data sungguhan</span>
        </Lencana>
    );
}
```

Di `resources/js/layouts/AppLayout.tsx`, tambahkan `import { LencanaDataContoh } from '@/components/ui/LencanaDataContoh';`
sesudah baris impor `JudulHalaman`, lalu ganti:

```tsx
                            <div className="flex shrink-0 items-center">
                                <Lockup href={dashboard.url()} />
                            </div>
```

dengan:

```tsx
                            <div className="flex shrink-0 items-center gap-3">
                                <Lockup href={dashboard.url()} />
                                <LencanaDataContoh />
                            </div>
```

Di `resources/js/components/trips/TataLetakPendakian.tsx`, tambahkan impor yang sama sesudah baris impor `JudulHalaman`,
lalu ganti:

```tsx
                <div className="mx-auto flex max-w-3xl items-center px-4 py-2 sm:px-6 lg:px-8">
                    <Button href={kembali} variant="secondary">
                        <Icon name="arrow-left" size={20} />
                        Kembali ke detail trip
                    </Button>
                </div>
```

dengan:

```tsx
                <div className="mx-auto flex max-w-3xl items-center justify-between gap-3 px-4 py-2 sm:px-6 lg:px-8">
                    <Button href={kembali} variant="secondary">
                        <Icon name="arrow-left" size={20} />
                        Kembali ke detail trip
                    </Button>
                    <LencanaDataContoh />
                </div>
```

- [ ] **Step 8: Catat data contoh di panduan basis data.** Di `database/README.md`, ganti baris:

```markdown
- `db:seed` hanya ke basis data lokal. Data contoh berlabel DATA CONTOH untuk peninjauan lokal menyusul di W1 (DK-35, izin Q1).
```

dengan:

```markdown
- `db:seed` hanya ke basis data lokal. Data contoh berlabel DATA CONTOH (DK-35) ada di `DemoSeeder` dan diisi `php artisan memount:demo` ke berkas `database/demo.sqlite`, oleh pemilik atas keputusan Q1, bukan oleh agen. Perintah itu menolak berjalan di luar `APP_ENV=local`, menolak koneksi yang bukan SQLite, dan menolak berkas di luar folder `database/`, lalu mencetak cara menyajikannya tanpa menyentuh basis data, sesi, cache, antrean, berkas, atau surel yang ditunjuk `.env`. `DatabaseSeeder` tidak memanggil `DemoSeeder`.
```

- [ ] **Step 9: Jalankan test tugas ini sampai lulus, dan pastikan tidak ada berkas SQLite yang tertinggal.**

Run: `php artisan test --compact --filter='DemoCommandGuardTest|DemoSeederTest|DemoBadgeTest'`
Expected: PASS, 17 test.

Run: `ls database/*.sqlite 2>/dev/null | grep -c "demo"`
Expected: `0` (penjaga hanya membaca; tidak ada `demo.sqlite` maupun `demo-penjaga-uji.sqlite`).

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan list | grep memount`
Expected: `  memount:demo   Mengosongkan lalu mengisi database/demo.sqlite dengan data contoh berlabel (hanya APP_ENV=local)` (hanya
terdaftar; tidak dijalankan).

- [ ] **Step 10: Gerbang tugas.**

Run: `vendor/bin/pint --dirty --format agent`
Expected: `{"tool":"pint","result":"passed"}` atau berkas dirapikan tanpa perubahan makna.

Run: `npx tsc --noEmit`
Expected: keluar tanpa keluaran galat.

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal. Pada simulasi rencana: 1.517 test, 1.516 lulus, 1 dilewati.

- [ ] **Step 11: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 10: Harness pratinjau visual

Ruling W1-R4 (dan W1-K8, W1-K9, W1-K11): suite PHPUnit terpisah `Pratinjau` merender halaman sungguhan berisi data contoh
di SQLite dalam memori dan menulis HTML-nya ke `public/_pratinjau/`, yang disajikan statis untuk dipotret Tugas 12. Tanpa
basis data lokal, tanpa Supabase, dan tidak dijalankan CI.

**Files:**
- Create: `tests/Pratinjau/PratinjauTestCase.php`, `tests/Pratinjau/HalamanPratinjauTest.php`, `tests/Pratinjau/PenyajiStatis.php`, `tests/Pratinjau/statis.php`, `tests/Feature/PratinjauHarnessTest.php`
- Modify: `phpunit.xml` (atribut `defaultTestSuite`, suite `Pratinjau`), `.gitignore`, `CONTRIBUTING.md` (subbagian baru di "UI dan UX")
- Test: `tests/Feature/RepoHygieneTest.php`

**Interfaces:**
- Consumes: `DemoSeeder` (Tugas 9), `routes/dev.php` (Tugas 8), rute `/dashboard` dan `/trails`, build Vite di `public/build`.
- Produces, dipakai Tugas 12 dan gelombang berikutnya:
  - `Tests\Pratinjau\PratinjauTestCase` dengan `ASAL = 'http://127.0.0.1:8100'`, `sebagaiBudi(): User`, dan `tulisPratinjau(string $nama, TestResponse $respons, string $komponen): void`. Gelombang berikutnya menambah halaman dengan satu metode test baru di `HalamanPratinjauTest`.
  - Berkas `public/_pratinjau/primitif.html`, `dasbor.html`, `jelajah.html`, `jelajah-kosong.html`.
  - `Tests\Pratinjau\PenyajiStatis::bolehDisajikan(string $uri, string $akarPublik): bool` dan router `tests/Pratinjau/statis.php` untuk `php -S`.
  - `php artisan test` tanpa `--testsuite` menjalankan Unit, Feature, dan Spatial saja.

- [ ] **Step 1: Tulis test harness yang gagal.** Buat test fitur:

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan make:test --phpunit PratinjauHarnessTest --no-interaction`
Expected: `INFO  Test [E:\praktik_aplikasi_web\tests\Feature\PratinjauHarnessTest.php] created successfully.`

Ganti seluruh isinya dengan:

```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\Pratinjau\PenyajiStatis;
use Tests\TestCase;

/**
 * Harness pratinjau visual (ruling W1-R4) dijaga dari suite biasa, karena suite
 * Pratinjau sendiri tidak pernah dijalankan CI dan tidak ikut suite bawaan.
 */
class PratinjauHarnessTest extends TestCase
{
    /**
     * `php artisan test` tanpa --testsuite memakai suite bawaan, dan suite bawaan tidak
     * memuat Pratinjau: menulis berkas ke public/ bukan efek samping yang pantas bagi
     * orang yang hanya ingin menjalankan test.
     */
    public function test_the_preview_suite_is_separate_and_never_runs_by_default(): void
    {
        $konfigurasi = simplexml_load_file(base_path('phpunit.xml'));

        $this->assertNotFalse($konfigurasi);
        $this->assertSame('Unit,Feature,Spatial', (string) $konfigurasi['defaultTestSuite']);

        $suite = [];

        foreach ($konfigurasi->testsuites->testsuite as $satu) {
            $suite[(string) $satu['name']] = (string) $satu->directory;
        }

        $this->assertSame(['Unit', 'Feature', 'Spatial', 'Pratinjau'], array_keys($suite));
        $this->assertSame('tests/Pratinjau', $suite['Pratinjau']);
    }

    /**
     * Server statis hanya menyajikan berkas nyata di public/ yang bukan PHP; rute aplikasi,
     * folder, dan alamat yang memanjat keluar ditolak, sehingga Laravel dan .env tidak
     * pernah tersentuh (W1-K8).
     */
    public function test_the_static_router_serves_built_files_only(): void
    {
        $publik = public_path();

        $this->assertTrue(PenyajiStatis::bolehDisajikan('/favicon.svg', $publik));
        $this->assertTrue(PenyajiStatis::bolehDisajikan('/favicon.svg?v=1', $publik));

        foreach (['/', '/index.php', '/INDEX.PHP', '/dashboard', '/../.env', '/%2e%2e/.env', '/build/../../.env', '/fonts/'] as $uri) {
            $this->assertFalse(PenyajiStatis::bolehDisajikan($uri, $publik), "{$uri} tidak boleh disajikan.");
        }
    }

    public function test_the_router_script_never_boots_the_application(): void
    {
        $router = File::get(base_path('tests/Pratinjau/statis.php'));

        $this->assertStringContainsString("require __DIR__.'/PenyajiStatis.php';", $router);
        $this->assertDoesNotMatchRegularExpression('/(?:require|include)[^;]*(?:vendor\/autoload|bootstrap\/app|index\.php)/', $router);
        $this->assertStringContainsString('return false;', $router);
        $this->assertStringContainsString('http_response_code(404);', $router);
    }
}
```

Di `tests/Feature/RepoHygieneTest.php`, ganti akhir konstanta `HARUS_DIABAIKAN`:

```php
        'database/demo.sqlite',
    ];
```

dengan:

```php
        'database/demo.sqlite',
        'public/_pratinjau/dasbor.html',
    ];
```

dan akhir konstanta `TIDAK_BOLEH_DIABAIKAN` (yang ditambah Tugas 2):

```php
        'public/fonts/plus-jakarta-sans/plus-jakarta-sans-latin-wght-normal.woff2',
    ];
```

dengan:

```php
        'public/fonts/plus-jakarta-sans/plus-jakarta-sans-latin-wght-normal.woff2',
        'tests/Pratinjau/statis.php',
    ];
```

- [ ] **Step 2: Jalankan dan pastikan gagal.**

Run: `php artisan test --compact --filter='PratinjauHarnessTest|RepoHygieneTest'`
Expected: FAIL. Pada simulasi rencana: 8 test, 5 lulus, 2 gagal, 1 error (`defaultTestSuite` dan suite `Pratinjau` belum
ada, `Tests\Pratinjau\PenyajiStatis` belum ada, `public/_pratinjau/` belum diabaikan git).

- [ ] **Step 3: Suite terpisah.** Di `phpunit.xml`, ganti:

```xml
         bootstrap="vendor/autoload.php"
         colors="true"
>
```

dengan:

```xml
         bootstrap="vendor/autoload.php"
         colors="true"
         defaultTestSuite="Unit,Feature,Spatial"
>
```

lalu ganti suite `Spatial`:

```xml
        <testsuite name="Spatial">
            <directory>tests/Spatial</directory>
        </testsuite>
```

dengan (komentar XML tidak boleh memuat dua tanda hubung berurutan, jadi opsi baris perintah ditulis dengan kata):

```xml
        <testsuite name="Spatial">
            <directory>tests/Spatial</directory>
        </testsuite>
        <!--
            Harness pratinjau visual (ruling W1-R4): menulis HTML halaman berisi data
            contoh ke public/_pratinjau/ untuk dipotret. Tidak termasuk suite bawaan
            (defaultTestSuite di atas) dan tidak dijalankan CI; jalankan dengan
            testsuite Pratinjau sesudah build aset. Caranya di CONTRIBUTING.md, bagian
            "Pratinjau visual tanpa basis data".
        -->
        <testsuite name="Pratinjau">
            <directory>tests/Pratinjau</directory>
        </testsuite>
```

Di `.gitignore`, ganti baris `/public/storage` dengan:

```gitignore
/public/storage
# Keluaran harness pratinjau visual (suite PHPUnit Pratinjau): HTML halaman berisi data
# contoh, ditulis ulang setiap kali suite berjalan.
/public/_pratinjau/
```

- [ ] **Step 4: Penyaji statis dan routernya.** Buat folder `tests/Pratinjau/` dan dua berkas berikut dengan menulisnya
langsung (`make:test` tidak membuat berkas di luar Unit dan Feature). Namespace `Tests\Pratinjau` sudah tercakup
autoload `Tests\` di `composer.json`.

`tests/Pratinjau/PenyajiStatis.php`:

```php
<?php

namespace Tests\Pratinjau;

/**
 * Penentu berkas yang boleh disajikan server statis pratinjau (W1-K8).
 *
 * `php -S -t public` tanpa router meneruskan setiap alamat yang bukan berkas ke
 * public/index.php, yang mem-boot Laravel dengan .env, dan .env repo ini menunjuk Supabase
 * produksi. Penentu ini hanya meloloskan berkas nyata di dalam public/ yang bukan skrip
 * PHP; alamat yang memanjat keluar lewat ".." atau "%2e%2e", folder, dan rute aplikasi
 * ditolak.
 */
final class PenyajiStatis
{
    public static function bolehDisajikan(string $uri, string $akarPublik): bool
    {
        $jalur = rawurldecode((string) parse_url($uri, PHP_URL_PATH));
        $akar = realpath($akarPublik);
        $berkas = realpath($akarPublik.DIRECTORY_SEPARATOR.ltrim($jalur, '/'));

        if ($akar === false || $berkas === false || ! is_file($berkas)) {
            return false;
        }

        if (! str_starts_with($berkas, $akar.DIRECTORY_SEPARATOR)) {
            return false;
        }

        return strtolower(pathinfo($berkas, PATHINFO_EXTENSION)) !== 'php';
    }
}
```

`tests/Pratinjau/statis.php`:

```php
<?php

use Tests\Pratinjau\PenyajiStatis;

/*
 * Router `php -S` untuk harness pratinjau (ruling W1-R4, W1-K8).
 *
 * Hanya berkas nyata di public/ yang disajikan; alamat lain dijawab 404 di sini, sehingga
 * aplikasi Laravel tidak pernah di-boot dan .env tidak pernah dibaca. Pemakaian dari root
 * repo:
 *
 *   DB_CONNECTION=sqlite DB_DATABASE=:memory: php -S 127.0.0.1:8100 -t public tests/Pratinjau/statis.php
 */

require __DIR__.'/PenyajiStatis.php';

if (PenyajiStatis::bolehDisajikan($_SERVER['REQUEST_URI'] ?? '/', $_SERVER['DOCUMENT_ROOT'] ?? __DIR__.'/../../public')) {
    return false;
}

http_response_code(404);
header('Content-Type: text/plain; charset=utf-8');
echo 'Tidak ada di pratinjau statis.';
```

- [ ] **Step 5: Kelas dasar dan halaman pratinjau.**

`tests/Pratinjau/PratinjauTestCase.php`:

```php
<?php

namespace Tests\Pratinjau;

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Harness pratinjau visual (ruling W1-R4).
 *
 * Setiap test merender satu halaman sungguhan lewat HTTP di SQLite dalam memori, berisi
 * data contoh DemoSeeder, lalu menulis HTML lengkapnya ke public/_pratinjau/<nama>.html.
 * Berkas itu disajikan statis oleh `php -S` dengan router tests/Pratinjau/statis.php,
 * sehingga halaman dapat dipotret di peramban tanpa basis data lokal dan tanpa menyentuh
 * Supabase. Suite ini terpisah dari Unit, Feature, dan Spatial, dan tidak dijalankan CI.
 *
 * Halaman diminta dengan asal http://127.0.0.1:8100, jadi aset di HTML dirujuk dari
 * server statis itu dan hanya tampil utuh bila dibuka dari sana, sesudah build aset.
 */
abstract class PratinjauTestCase extends TestCase
{
    use RefreshDatabase;

    protected const ASAL = 'http://127.0.0.1:8100';

    protected function setUp(): void
    {
        parent::setUp();

        if (! is_file(public_path('build/manifest.json'))) {
            $this->fail('Bangun aset dulu: DB_CONNECTION=sqlite DB_DATABASE=:memory: npm run build');
        }

        if (is_file(public_path('hot'))) {
            $this->fail('Berkas public/hot menandakan npm run dev berjalan atau pernah berjalan; hentikan, hapus berkasnya, lalu build ulang.');
        }

        config(['app.demo' => true]);
    }

    protected function sebagaiBudi(): User
    {
        $this->seed(DemoSeeder::class);

        $budi = User::where('email', DemoSeeder::EMAIL_PENDAKI)->firstOrFail();
        $this->actingAs($budi);

        return $budi;
    }

    /**
     * Menulis HTML halaman ke public/_pratinjau/<nama>.html.
     *
     * Satu skrip kecil disisipkan di awal <head>: ia mengganti alamat di bilah peramban ke
     * URL rute halaman itu sebelum React menggambar. Tanpa itu layout membaca alamat
     * /_pratinjau/<nama>.html dan tidak ada tab navigasi yang tampil aktif, sehingga
     * tangkapan berbeda dari halaman yang sama di aplikasi berjalan.
     */
    protected function tulisPratinjau(string $nama, TestResponse $respons, string $komponen): void
    {
        $respons->assertOk()->assertInertia(fn (AssertableInertia $halaman) => $halaman->component($komponen)->where('demo', true));

        $html = (string) $respons->getContent();

        $this->assertSame(1, preg_match('/<script data-page="app" type="application\/json">(.*?)<\/script>/s', $html, $data), 'Data halaman Inertia 3 tidak tertanam di HTML.');
        $this->assertStringContainsString(self::ASAL.'/build/assets/', $html, 'Aset tidak dirujuk dari asal server statis.');

        $url = (string) (json_decode($data[1], true)['url'] ?? '');

        $this->assertStringStartsWith('/', $url, 'URL halaman Inertia tidak terbaca.');

        $kepala = strpos($html, '<head>');

        $this->assertNotFalse($kepala, 'Tag <head> tidak ditemukan.');

        $alamat = '<script>history.replaceState(null, "", '.json_encode($url, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES).');</script>';

        File::ensureDirectoryExists(public_path('_pratinjau'));
        File::put(public_path("_pratinjau/{$nama}.html"), substr_replace($html, '<head>'.$alamat, $kepala, strlen('<head>')));
    }
}
```

`tests/Pratinjau/HalamanPratinjauTest.php`:

```php
<?php

namespace Tests\Pratinjau;

use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Halaman yang dipotret di akhir gelombang 1 (ruling W1-R4 dan W1-R6): pameran primitif,
 * Dasbor, dan Jelajah berisi data contoh, ditambah Jelajah tanpa jalur terbit, yaitu
 * keadaan basis data pemilik hari ini (Review Focus W1).
 */
class HalamanPratinjauTest extends PratinjauTestCase
{
    public function test_pameran_primitif(): void
    {
        Route::middleware('web')->group(base_path('routes/dev.php'));
        $this->sebagaiBudi();

        $this->tulisPratinjau('primitif', $this->get(self::ASAL.'/dev/primitif'), 'Dev/Primitif');
    }

    public function test_dasbor(): void
    {
        $this->sebagaiBudi();

        $this->tulisPratinjau('dasbor', $this->get(self::ASAL.'/dashboard'), 'Dashboard');
    }

    public function test_jelajah(): void
    {
        $this->sebagaiBudi();

        $this->tulisPratinjau('jelajah', $this->get(self::ASAL.'/trails'), 'Trails/Index');
    }

    public function test_jelajah_tanpa_jalur_terbit(): void
    {
        $pendaki = User::factory()->create(['name' => 'Budi Setiawan']);
        $pendaki->profile()->create(['experience_level' => 'BEGINNER', 'completed_at' => now()]);
        $this->actingAs($pendaki);

        $this->tulisPratinjau('jelajah-kosong', $this->get(self::ASAL.'/trails'), 'Trails/Index');
    }
}
```

- [ ] **Step 6: Catat caranya di panduan kontribusi.** Di `CONTRIBUTING.md`, sisipkan subbagian berikut tepat sebelum baris
`## Yang tidak boleh masuk` (satu baris kosong sebelum judul berikutnya):

````markdown
### Pratinjau visual tanpa basis data

Tangkapan 390 px dan 1440 px tidak butuh basis data lokal dan tidak menyentuh Supabase. Suite PHPUnit `Pratinjau`
merender halaman sungguhan di SQLite dalam memori berisi data contoh `DemoSeeder`, lalu menulis HTML-nya ke
`public/_pratinjau/` (diabaikan git). Suite ini tidak ikut `php artisan test` tanpa `--testsuite` dan tidak dijalankan
CI. Dari Git Bash:

```sh
DB_CONNECTION=sqlite DB_DATABASE=:memory: npm run build
php artisan test --testsuite=Pratinjau
DB_CONNECTION=sqlite DB_DATABASE=:memory: php -S 127.0.0.1:8100 -t public tests/Pratinjau/statis.php
```

Dari PowerShell:

```powershell
$env:DB_CONNECTION="sqlite"; $env:DB_DATABASE=":memory:"; npm run build
php artisan test --testsuite=Pratinjau
$env:DB_CONNECTION="sqlite"; $env:DB_DATABASE=":memory:"; php -S 127.0.0.1:8100 -t public tests/Pratinjau/statis.php
```

Lalu buka `http://127.0.0.1:8100/_pratinjau/primitif.html`, `dasbor.html`, `jelajah.html`, atau `jelajah-kosong.html`.

- Router `tests/Pratinjau/statis.php` hanya menyajikan berkas yang ada di `public/`. Tanpa router, `php -S` meneruskan
  alamat lain ke `public/index.php` dan mem-boot aplikasi dengan `.env`.
- Halaman pratinjau mengganti alamat di bilah peramban ke rute aslinya (misalnya `/dashboard`) supaya tab navigasi
  yang aktif benar. Karena itu memuat ulang halaman dan menekan tautan berujung 404: buka lagi berkas `.html`-nya.
- Jalankan ulang suite sesudah setiap build, karena nama berkas aset berganti. Hentikan `npm run dev` lebih dulu:
  berkas `public/hot` membuat halaman memuat aset dari server Vite.
- Tema gelap: jalankan `document.cookie = 'tema=dark; path=/'` di konsol peramban, lalu buka lagi berkasnya. Kembali
  ke terang dengan `tema=light`, atau hapus cookie itu untuk mengikuti sistem.
````

- [ ] **Step 7: Jalankan test harness dan pastikan suite bawaan tidak memuat Pratinjau.**

Run: `php artisan test --compact --filter='PratinjauHarnessTest|RepoHygieneTest|LocalPostgisComposeTest|CiWorkflowsTest|TeamDocsIndexTest|PrdReferenceIntegrityTest|SecretsNotInRepoTest'`
Expected: PASS, 56 test.

Run: `vendor/bin/phpunit --list-suites`
Expected: hanya `Feature`, `Spatial`, dan `Unit`: suite bawaan tidak memuat Pratinjau.

Run: `vendor/bin/phpunit --testsuite=Pratinjau --list-tests`
Expected: empat test, `HalamanPratinjauTest::test_pameran_primitif`, `test_dasbor`, `test_jelajah`, dan
`test_jelajah_tanpa_jalur_terbit`.

- [ ] **Step 8: Build, jalankan suite Pratinjau, dan sajikan hasilnya.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: npm run build`
Expected: `✓ built`.

Run: `php artisan test --compact --testsuite=Pratinjau`
Expected: PASS, 4 test.

Run: `ls public/_pratinjau/`
Expected: empat berkas, `dasbor.html`, `jelajah.html`, `jelajah-kosong.html`, dan `primitif.html`.

Jalankan server statis di latar belakang (alat Bash dengan `run_in_background`):
`DB_CONNECTION=sqlite DB_DATABASE=:memory: php -S 127.0.0.1:8100 -t public tests/Pratinjau/statis.php`

Run: `for u in /_pratinjau/dasbor.html /fonts/plus-jakarta-sans/plus-jakarta-sans-latin-wght-normal.woff2 /dashboard /index.php "/%2e%2e/.env"; do printf '%s ' "$u"; curl -s -o /dev/null -w '%{http_code} %{content_type}\n' "http://127.0.0.1:8100$u"; done`
Expected (pada simulasi rencana):

```
/_pratinjau/dasbor.html 200 text/html; charset=UTF-8
/fonts/plus-jakarta-sans/plus-jakarta-sans-latin-wght-normal.woff2 200 font/woff2
/dashboard 404 text/plain; charset=utf-8
/index.php 404 text/plain; charset=utf-8
/%2e%2e/.env 404 text/plain; charset=utf-8
```

Buka `http://127.0.0.1:8100/_pratinjau/dasbor.html` lewat MCP chrome-devtools (`new_page`), `resize_page` 390 x 844, lalu
`take_screenshot`. Expected: kartu trip "Gede via Cibodas" dengan "15 hari lagi", lencana "Data contoh" di samping
lockup, dan tab Beranda aktif (ikon isi dan huruf tebal). Hentikan server sesudahnya; Tugas 12 menjalankannya lagi.

- [ ] **Step 9: Gerbang tugas.**

Run: `vendor/bin/pint --dirty --format agent`
Expected: `{"tool":"pint","result":"passed"}` atau berkas dirapikan tanpa perubahan makna. Pint mengubah `statis.php`
ke bentuk `use Tests\Pratinjau\PenyajiStatis;` bila berkasnya ditulis dengan nama kelas lengkap; bentuk di Step 4 sudah
bentuk akhirnya.

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal. Pada simulasi rencana: 1.520 test, 1.519 lulus, 1 dilewati.

Run: `php artisan test --compact`
Expected: 0 gagal. Pada simulasi rencana: 1.540 test, 1.519 lulus, 21 dilewati (20 test Spatial melewati dirinya tanpa
`SPATIAL_TEST_DSN`, ditambah 1 yang sudah ada). Suite Pratinjau tidak ikut, jadi berkas di `public/_pratinjau/` tidak
berubah waktunya.

- [ ] **Step 10: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 11: DESIGN.md v3

Induk W1 butir 6 (DK-30): bagian **Turunan** `DESIGN.md` ditulis ulang mengikuti bahasa desain v3 yang sudah dibangun
Tugas 1 sampai 10. Kepala berkas, bagian **Arah (dari pemilik produk)**, bagian **Yang tidak dikerjakan**, dan aturan
canonical R-015 sampai R-017 disalin byte demi byte; dua di antaranya dipaku hash oleh test baru.

**Files:**
- Modify: `DESIGN.md` (hanya bagian `## Turunan (ditarik agen, silakan dibetulkan)`), `CONTRIBUTING.md` (tabel "Aturan yang ditegakkan test")
- Test: `tests/Feature/DesignDirectionTest.php`

**Interfaces:**
- Consumes: kelas, token, berkas, dan test Tugas 1 sampai 10; laporan Tugas 4 (cap waktu pilot glyph).
- Produces: `DESIGN.md` bagian Turunan v3 yang dibaca sebelum pekerjaan UI W2 sampai W8 (CLAUDE.md bagian 2), dan dua
  penjaga isi berkas itu.

- [ ] **Step 1: Tulis test yang gagal.** Di `tests/Feature/DesignDirectionTest.php`, tambahkan dua metode berikut sesudah
metode terakhir `test_no_em_dash_survives_in_the_code_or_the_direction`, sebelum `}` penutup kelas:

```php

    /**
     * Bagian Arah (kata pemilik produk) dan aturan canonical R-015 sampai R-017 tidak
     * disunting agen. Gelombang 1 v3 menulis ulang bagian Turunan saja; kedua bagian lain
     * dipaku hash isinya, dengan akhir baris dinormalkan ke LF seperti yang disimpan git.
     */
    public function test_the_owner_direction_and_canonical_rules_are_untouched(): void
    {
        $isi = str_replace("\r\n", "\n", $this->arah());
        $arah = strpos($isi, '## Arah (dari pemilik produk)');
        $turunan = strpos($isi, '## Turunan (ditarik agen, silakan dibetulkan)');
        $kanon = strpos($isi, '# Aturan canonical (R-015');

        $this->assertNotFalse($arah);
        $this->assertNotFalse($turunan);
        $this->assertNotFalse($kanon);
        $this->assertSame('37d00315fb2842eb56b3931cd05d8c5ad294ccc127497cf039e8c7bd8a8d8981', hash('sha256', substr($isi, $arah, $turunan - $arah)), 'Bagian Arah dari pemilik berubah.');
        $this->assertSame('501349773091c87e61658a779f64319add43dcddd899e2066e423b968a0fa079', hash('sha256', substr($isi, $kanon)), 'Aturan canonical berubah.');
    }

    /**
     * Bagian Turunan mengikuti bahasa desain v3 dari prototype pemilik (spec v3 bagian 8):
     * dial 2/2/2, satu keluarga huruf, token on-brand, koreksi kontras, lantai huruf, tema
     * lapangan, dan pameran primitif. Keputusan v2 yang sudah diganti tidak tertinggal.
     */
    public function test_the_derived_section_follows_the_v3_prototype(): void
    {
        $isi = $this->arah();
        $mulai = strpos($isi, '## Turunan');
        $turunan = substr($isi, $mulai, strpos($isi, '## Yang tidak dikerjakan') - $mulai);

        foreach (['| **ENERGY** | **2** |', '| **RHYTHM** | **2** |', '| **MOTION** | **2** |', 'Plus Jakarta Sans', 'text-on-brand', '#F6F3EC', '#808682', 'Lantai 12 px', 'Data contoh', 'data-tema-lapangan', '/dev/primitif', 'glyphDomain.ts'] as $wajib) {
            $this->assertStringContainsString($wajib, $turunan, "Bagian Turunan belum menyebut {$wajib}.");
        }

        $this->assertStringNotContainsString('wire:navigate', $turunan, 'Livewire sudah dipensiunkan; tema disamakan dasar.js.');
        $this->assertDoesNotMatchRegularExpression('/\| Judul halaman \| Newsreader/', $turunan, 'Serif judul sudah dilepas (DK-38).');
    }
```

- [ ] **Step 2: Jalankan dan pastikan gagal.**

Run: `php artisan test --compact --filter=DesignDirectionTest`
Expected: FAIL. Pada simulasi rencana: 7 test, 6 lulus, 1 gagal,
`Bagian Turunan belum menyebut | **ENERGY** | **2** |.` Test hash lulus sejak awal karena bagian Arah dan canonical
belum disentuh; ia penjaga untuk langkah berikutnya.

- [ ] **Step 3: Tulis ulang bagian Turunan.** Di `DESIGN.md`, ganti seluruh teks dari baris
`## Turunan (ditarik agen, silakan dibetulkan)` sampai tepat sebelum baris `---` yang mendahului
`## Yang tidak dikerjakan, dan alasannya` dengan isi di bawah. Baris `---` itu dan semua yang sesudahnya tidak berubah;
begitu pula baris 1 sampai 34 (kepala berkas dan bagian Arah).

Sebelum menyimpan, ganti penanda `[JAM TERUKUR TUGAS 4]` dengan nilai dari laporan Tugas 4 dalam bentuk
`X jam Y menit (mulai 2026-09-27 HH.MM, selesai HH.MM)`, memakai cap waktu yang tercatat di sana. Bila salah satu cap
waktu tidak tercatat, tulis `belum terukur`. Jangan menaksir.

````markdown
## Turunan (ditarik agen, silakan dibetulkan)

Bahasa desain v3 dari prototype pemilik (`docs/desain/prototype/memount-prototype.html`, gambar acuan di
`docs/desain/prototype/acuan/`), diturunkan spec v3 bagian 8 dan dibangun di gelombang 1. Nilainya tinggal di token dan
kode; bagian ini menjelaskan peran dan alasannya, beserta test yang menjaganya.

### Pembacaan desain dan tiga dial

Reading this as: aplikasi perencanaan pendakian untuk pendaki pemula Indonesia di ponsel, bahasa visual editorial
utilitarian yang hangat (kertas, hijau hutan, data sebagai visual).

| Dial | Nilai | Dasarnya |
|---|---|---|
| **ENERGY** | **2** | Mode Operate: halaman membantu merencanakan, bukan membujuk. Satu aksen dan hierarki lewat bobot huruf 800 memberi cukup suara tanpa sapaan keras. |
| **RHYTHM** | **2** | Komposisi mengikuti isinya: status, angka, dan narasi tidak berbentuk sama, tetapi variasi tidak dikejar. |
| **MOTION** | **2** | Gerak hanya dari daftar tertutup di bagian Gerak: tekan, buka lembar dan dialog, toast, dan umpan balik keadaan. Tidak ada animasi masuk dan tidak ada gerak berulang. |

Mode lapangan (Mendaki, W6): ENERGY 1, MOTION 1; gerak hanya untuk perubahan status.

ENERGY dan MOTION naik dari 1 ke 2 mengikuti spec v3 bagian 8.9: prototype pemilik memakai tekan yang menyusut, lembar
bawah yang naik, dan toast urungkan, dan ketiganya membawa umpan balik yang tidak dibawa teks.

### Motif identitas

Garis rute berujung cincin pos, dari tanda "Jalur Berpos" (konsep A, keputusan pemilik D4): satu garis monoline yang
mendaki lewat belokan dan berakhir pada cincin pos. Motif ini berulang sebagai profil elevasi, garis jalur di peta,
bilah langkah, penanda pos bernomor, dan tiga glyph domain pilot. Tidak ada gunung pada tanda: Trail adalah unit
keputusan (R-001, R-002).

Tanda dan wordmark ada di `resources/js/components/brand/` (`Mark`, `Lockup`), memakai `currentColor` dengan token
`logo-mark` dan `logo-type`. Status aset: sketsa yang sudah tampil di produk, belum aset final; gerbang DK-16 (audit
kemiripan, penelusuran PDKI, uji pemahaman 5 sampai 8 pendaki, persetujuan pemilik) belum dilewati.

### Palet dan token

Token menyebut peran, nilainya tinggal di `resources/css/app.css`, dan `tailwind.config.js` memetakannya ke kelas
bernama. Tidak ada warna mentah, `bg-white`, `text-black`, atau `dark:` di komponen (`InertiaLayoutTest`,
`SemanticTokenTest`).

| Peran | Kelas | Terang | Gelap |
|---|---|---|---|
| Kanvas | `bg-canvas` | `#F6F3EC` | `#0F1311` |
| Permukaan (kartu, lembar, bilah) | `bg-surface` | `#FFFFFF` | `#1B221E` |
| Sumur (kontrol segmen, stepper) | `bg-surface-sunken` | `#EEEAE1` | `#151A17` |
| Permukaan kebalikan (toast) | `bg-surface-inverse`, `text-inverse` | `#1D2320` / `#FFFFFF` | `#F2EFE8` / `#0F1311` |
| Garis dekoratif | `border-subtle` | `#E2DDD2` | `#2D3731` |
| Batas kontrol, sakelar mati | `border-control`, `bg-control` | `#8C8579` | `#7F8B84` |
| Teks utama, kedua, redup | `text-primary`, `text-secondary`, `text-muted` | `#1D2320`, `#4E5551`, `#626965` | `#F2EFE8`, `#B7BFB9`, `#9AA39D` |
| Isian tombol merek, tekan | `bg-brand-700`, `hover:bg-brand-800` | `#234F32`, `#16361F` | `#8FD3A0`, `#A6DDB3` |
| Teks di atas isian merek | `text-on-brand` | `#FFFFFF` | `#0F2418` |
| Merek sebagai latar depan | `text-brand-ink` | `#234F32` | `#8FD3A0` |
| Latar lembut merek (tab aktif, chip) | `bg-brand-100`, `text-brand-900` | `#E4EEE6`, `#1E5E35` | `#1D3326`, `#B5E4C1` |
| Label Cocok | `bg-fit-cocok`, `text-fit-cocok-teks` | `#DDEEE2` / `#1E5E35` | `#1D3326` / `#B5E4C1` |
| Label Perlu persiapan | `bg-fit-perlu`, `text-fit-perlu-teks` | `#FBEBCF` / `#7A4E0B` | `#3A2C12` / `#F0C27A` |
| Label Kurang cocok | `bg-fit-kurang`, `text-fit-kurang-teks` | `#F7DFDA` / `#8E2F22` | `#3D1B16` / `#F2A597` |
| Belum dinilai (UNKNOWN) | `bg-fit-belum`, `text-fit-belum-teks` | `#ECE9E3` / `#4E5551` | `#252D28` / `#B7BFB9` |
| Status Buka, Dibatasi, Tutup, Belum diketahui | `bg-status-buka` dan seterusnya | `#2F7A48`, `#B7791F`, `#8E2F22`, `#808682` | `#6FBF84`, `#F0C27A`, `#E0806C`, `#8A958E` |
| Komunitas | `community-50`, `-500`, `-900` | `#F2F6FC`, `#5F82B8`, `#24508C` | `#18222F`, `#6F93C9`, `#9DBBE6` |
| Aksen pos | `bg-aksen-pos` | `#E8B04B` | `#E8B04B` |

Koreksi terukur atas prototype (DK-31, ditambah satu di gelombang 1): batas kontrol `#8C8579`, garis komunitas
`#5F82B8`, teks redup `#626965`, dan status belum diketahui `#808682`, karena `#8A8F8B` hanya 2,97:1 di kanvas. Rasio
dihitung dengan rumus WCAG 2.x, bukan ditaksir: teks 4,5:1 dan non-teks 3:1 di kedua tema (`ColourContrastTest`).

**Satu aksen.** Kuning pos hanya 1,76:1 di kanvas, jadi tidak pernah membawa arti sendirian: ia selalu menemani bentuk
dan label (cincin logo, pos berkemah, tujuan).

**Warna semantik tidak pernah didesaturasi demi keselarasan.** Peringatan, bahaya, dan status membawa arti keselamatan.

### Tema gelap

Tiga pilihan (DK-17, keputusan pemilik D5): **Ikuti sistem** (bawaan), **Terang**, **Gelap**. Pilihan disimpan di
cookie `tema`, dirender server sebagai `data-theme` pada `<html>` saat muat penuh, dan disamakan lagi oleh
`terapkanTema()` di `resources/js/dasar.js` pada setiap kunjungan Inertia, karena kunjungan Inertia mengganti
komponen halaman, bukan `<html>`. Nilai v3 diturunkan dari palet lapangan prototype.

Skala warna dipetakan ulang menurut **peran**: `bg-brand-100 text-brand-900` tetap berarti "latar lembut, teks
pekat" di kedua tema. Isian tombol merek di tema gelap hijau muda `#8FD3A0`, jadi teks di atasnya token `on-brand`
gelap `#0F2418` (9,33:1), dan tanda centang serta titik radio bawaan formulir digelapkan di `app.css`; isian bahaya
tetap merah bata dengan teks putih (4,92:1). Peta topografi tetap terang dan hanya diredupkan: kontur adalah informasi
keselamatan. Dua blok gelap di `app.css` identik dan dijaga `ColourContrastTest`.

### Tema lapangan

Mode Mendaki (W6) memasang `data-tema-lapangan="terang"` atau `"gelap"` pada akarnya; kedua blok di `app.css` hanya
menimpa token yang dibaca di jalur (DK-49). Informasi kritis (nama pos, angka jarak, tombol Jeda dan Selesai) paling
sedikit 7:1: tinta `#0F1311` di atas putih pada lapangan terang, `#F2EFE8` di atas `#0F1311` pada lapangan gelap. Di
lapangan gelap cincin fokus memakai kuning pos (9,58:1), satu-satunya tempat kuning pos membawa arti karena berdiri di
atas gelap. Kontrol lapangan 48 sampai 66 px. `ColourContrastTest` menghitung kedua tema dengan ambangnya sendiri.

### Tipografi

**Satu keluarga: Plus Jakarta Sans variabel** untuk antarmuka, judul, dan angka (DK-38). Alasannya tertulis di laporan
riset merek: dirancang Tokotype (Indonesia) untuk identitas +Jakarta, memiliki angka tabular, glyph ↑, dan bobot 800
yang dibutuhkan skala judul prototype. Kelangkaan bukan alasannya; keunikan merek dipikul motif dan tata bahasa data.
Geist, Geist Mono, dan Newsreader hanya hidup di bundel prototype.

| Peran | Kelas | Ukuran, tinggi baris, bobot |
|---|---|---|
| Tampilan (sapaan Beranda) | `text-tampilan` | 30 px, 1,15, 800, tracking -0,025em |
| Judul halaman (`PageHeader`, h1) | `text-judul` | 28 px, 1,2, 800, tracking -0,02em |
| Judul kartu utama | `text-judul-kartu` | 23 px, 1,25, 800 |
| Judul bagian | `text-bagian` | 18 px, 1,35, 700 |
| Subjudul | `text-subjudul` | 17 px, 1,4, 700 |
| Isi | `text-isi` | 16 px, 1,5 |
| Isi kecil | `text-kecil` | 14 px, 1,45 |
| Meta | `text-meta` | 13 px, 1,4 |
| Label (lantai) | `text-label` | 12 px, 1,35 |

**Lantai 12 px** untuk teks apa pun, termasuk label tab dan label peta (`TypeFloorTest`). Isi 16 px, juga di isian
formulir: di bawah 16 px Safari iOS memperbesar halaman saat isian difokus.

**Angka:** `tabular-nums` dari keluarga yang sama untuk jarak, tanjakan, durasi, tanggal, jam, dan hitungan (`table`,
`dd`, `time`, `output`, `[data-angka]`). Format lewat lokal `id-ID`: koma desimal, titik ribuan, jam "15.06". Rentang
di data memakai en dash ("7–8 j"); di dalam kalimat memakai "sampai". Titik tengah hanya pada baris ukuran yang padat
("9,7 km · ↑1.650 m · 7–8 j"), tidak sebagai pemisah meta di setiap kartu.

**Label kecil** memakai huruf kalimat tebal 13 px, bukan kapital bertracking lebar: "Data contoh", "Kritis", "Perlu
persiapan" (`DesignPrimitivesTest`). Label kecocokan publik hanya "Cocok", "Perlu persiapan", "Kurang cocok", dengan
"Belum dinilai" untuk UNKNOWN (R-004, R-006).

**Hosting:** dua subset WOFF2 dihosting sendiri di `public/fonts/plus-jakarta-sans/` bersama teks OFL: Latin 27.348 bita
(dipramuat) dan Latin Extended 21.728 bita (diunduh hanya bila halaman memuat glyph-nya), jumlahnya 49.076 bita dari
anggaran 100 KB. `font-display: swap` dengan dua wajah cadangan bermetrik (Roboto dan Arial) supaya tata letak tidak
melompat. Asal berkas, hash, dan lisensinya ada di `docs/lisensi-pihak-ketiga.md`; ukuran dan hash dipaku
`TypographySystemTest`. Mengganti berkas huruf wajib memakai nama berkas baru: service worker menyimpan huruf
cache-first, dan versinya hanya berganti ketika manifest build berganti.

**Mono:** tumpukan sistem, tanpa unduhan, hanya untuk kode alfanumerik seperti nama migrasi dan kunci tugas penjadwal
di halaman Kesehatan admin; tidak untuk ukuran.

### Ikon

Ikon hanya di tempat yang membawa arti: arah naik dan turun, penanda tab, jenis pos, asal keterangan, dan aksi yang
namanya sudah dibawa label. Tidak pernah sebagai hiasan di samping judul (`IconSystemTest` menjaga `PageHeader` dan
`Card` tetap bebas darinya).

**Phosphor** (MIT), path ditanam per bobot di `resources/js/components/ui/ikon/phosphor.ts`, tanpa paket npm. Cakupan
bukan pembeda antarpustaka; identitas datang dari glyph domain. Bukan Lucide maupun Heroicons.

| Ukuran render | Bobot | Contoh |
|---|---|---|
| 16 sampai 19 px | Bold | Panah naik dan turun di daftar pos, centang chip |
| 20 px ke atas | Regular | Tombol ikon dan tab (22 px) |
| Tab aktif | Isi | Beranda, Jelajah, Trip, Mendaki, Progres |

Tidak ada ikon informatif di bawah 16 px. API: `<Icon name size isi label />` dengan `size` 16, 20, 22, 24, 28, atau
32; ukuran tidak ditulis sebagai kelas. Ikon di samping label `aria-hidden`; ikon yang berdiri sendiri membawa `label`;
tombol ikon (`TombolIkon`) wajib bernama. Status dibawa bentuk dan kata, karena mode `forced-colors` menimpa `fill` dan
`stroke`.

**Tombol aksi tanpa panah hiasan** (ciri klaster "tampilan AI"). Arah kembali memakai ikon `arrow-left`; chevron hanya
pada baris yang membuka halaman lain dan pada pembuka yang dapat dilipat; ikon tautan keluar hanya pada tautan ke situs
lain.

**Glyph domain pilot** (DK-39, ruling W1-R3): `pos`, `tanjakan`, dan `resmi`, digambar dari tata bahasa tanda "Jalur
Berpos" (garis monoline, belokan bersudut, cincin pos) sebagai goresan setebal Phosphor pada bobot yang sama, di
`resources/js/components/ui/ikon/glyphDomain.ts`. `resmi` hanya untuk keterangan bersumber OFFICIAL (R-009). Jam kerja
pilot: rancangan geometrinya dibuat di sesi perencanaan W1 tanpa pencatat waktu (belum terukur); implementasi dan
pemeriksaan visual di Tugas 4: [JAM TERUKUR TUGAS 4]. Set lengkap 8 sampai 12 glyph menunggu uji pengenalan dengan 5 sampai 8
pendaki (spec v3 bagian 15, Q15).

**Lisensi:** teks MIT di `resources/js/components/ui/LICENSE-phosphor.txt`; setiap berkas yang memuat path Phosphor
menunjuknya, dan pemberitahuannya ikut ke bundel produksi (`ThirdPartyLicenseTest`).

### Bentuk, ruang, dan target

| Kelas | Radius | Untuk |
|---|---|---|
| `rounded-kecil` | 10 px | Kotak centang, kontrol kecil |
| `rounded-control` | 12 px | Segmen di dalam sumur, tombol ikon kotak |
| `rounded-tombol` | 16 px | Tombol, isian, sumur segmen, toast |
| `rounded-lg`, `rounded-kartu` | 18 px | Kartu, panel, keadaan kosong |
| `rounded-utama` | 22 px | Kartu utama, peta, bar aksi, dialog |
| `rounded-lembar` | 26 px | Lembar bawah (sudut atas di ponsel) |
| `rounded-lencana` | 6 px | Lencana kotak |
| `rounded-full` | pil | Chip, bilah tab, tombol ikon bulat, avatar, sakelar |

Radius dalam sama dengan radius luar dikurangi padding. Nilainya tinggal di token `--radius-*` (`SemanticTokenTest`);
`rounded-md`, `rounded-xl`, dan sejenisnya tidak dipakai di view (`DesignDirectionTest`).

Ruang memakai kelipatan dari skala 4, 6, 8, 10, 12, 14, 16, 20, 24, 28 px; gutter halaman 20 px, 16 px di layar padat
seperti Jelajah (halaman lama tetap 16 px sampai W2). Target: kontrol utama paling sedikit 44 px (`TapTargetTest`,
`DesignPrimitivesTest`); chip berbentuk 36 px di dalam target 44 px (W1-K5); kontrol lapangan 48 sampai 66 px.

### Material, elevasi, dan lapisan

Kartu datar bergaris rambut (`border-subtle`), tanpa bayangan (`SurfaceSystemTest`). Satu elevasi, `shadow-overlay`
berwarna token `bayangan`, hanya untuk lapisan yang melayang: bilah tab, bar aksi, lembar bawah, dialog, toast, dan
kartu pratinjau peta. Kaca paling banyak dua elemen per layar lewat `.chrome`, bersyarat dan bukan bawaan, tidak pernah
di atas peta, kartu, status resmi, laporan komunitas, atau dialog.

Skala lapisan: bar aksi dan bilah tab `z-30`, toast `z-40`. Lembar bawah dan dialog adalah `<dialog>` asli yang dibuka
`showModal()`, jadi tinggal di top layer peramban tanpa z-index, dengan tirai token `scrim`; halaman di belakangnya
berhenti bergulir, Escape menutup, dan fokus kembali ke pemicunya (W1-K6).

### Gerak

Daftar tertutup (spec v3 bagian 8.6); hanya transform, opacity, dan warna yang berpindah, dan hanya lewat token.

| Gerak | Durasi dan kurva | Di mana |
|---|---|---|
| Tekan | `duration-sentuh` (120 ms), `ease-gerak`, skala 0,98 | `umpanTekan` di `gaya.ts`: tombol, tombol ikon, chip |
| Geser kenop sakelar | 120 ms, `ease-gerak` | `Sakelar`, sebagai umpan balik tekannya |
| Lembar bawah naik | `--dur-sedang` (280 ms), `--ease-gerak` | `.lembar[open]` |
| Dialog tengah muncul | `--dur-cepat` (200 ms), `--ease-pudar` | `.dialog-tengah[open]` |
| Toast urungkan naik | `--dur-cepat` (200 ms), `--ease-gerak` | `.toast-masuk` |
| Centang persiapan, tiba di pos, pembuka "Kenapa?", kartu jalur ke detail | menurut spec v3 bagian 8.6 | W3, W4, W6 |

Tutup seketika (W1-K6). Tidak ada animasi masuk dan tidak ada gerak berulang. `prefers-reduced-motion` dan setelan Gerak
di Profil (`data-gerak="kurangi"`) memangkas semuanya menjadi 1 ms (`MotionTest`). Mode lapangan tanpa gerak selain
status.

### Media dan foto

Foto gunung diunggah admin dengan kolom wajib pembuat, lisensi, URL sumber, teks atribusi, dan teks alternatif, lalu
tampil dengan atribusinya. Tanpa foto, hero memakai visual data (bentuk rute di atas pola kontur). Foto komunitas tidak
pernah menjadi hero (R-017). Avatar berupa unggahan pengguna atau inisial (`Avatar`); data contoh memakai inisial,
tidak pernah wajah stok (antislop R-23).

### Nada dan teks

Sapaan "Anda". Tagline "Pilih jalur yang sesuai. Pahami alasannya." (D2). Tanpa em dash. Tidak pernah menyatakan jalur
atau gunung "aman" dan tidak pernah "layak berangkat" (R-012, R-014): kalimat publik menyebut apa yang masih kurang
sebelum berangkat. Keadaan kosong memakai tiga jenis `EmptyState`, pertama kali, tersaring, dan menunggu, dan yang
menunggu menyebut siapa yang ditunggu ("Menunggu BMKG", "Menunggu kurator Memount") (PRD §104). Aksi spesifik
("Lanjutkan persiapan", "Pesan di sistem resmi", "Rencanakan trip"). Data contoh selalu berlabel "Data contoh" (DK-35).

### Primitif

Primitif desain tinggal di `resources/js/components/ui/` (ruling W1-R1; spec v3 bagian 9.1 menyebut `components/ds/`,
dan ruling itu yang berlaku).

| Primitif | Berkas |
|---|---|
| Tombol, tombol ikon | `Button.tsx`, `TombolIkon.tsx` |
| Chip pilihan dan chip saringan | `Chip.tsx` |
| Kontrol segmen, sakelar, kotak centang, stepper | `KontrolSegmen.tsx`, `Sakelar.tsx`, `KotakCentang.tsx`, `Stepper.tsx` |
| Lembar bawah dan dialog | `Lembar.tsx` |
| Toast urungkan, bar aksi, bilah tab | `ToastUrungkan.tsx`, `BarAksi.tsx`, `BilahTab.tsx` |
| Keadaan kosong tiga jenis, lencana, avatar | `EmptyState.tsx`, `Lencana.tsx`, `Avatar.tsx` |
| Isian, kartu, judul halaman, peringatan | `Field.tsx`, `Card.tsx`, `PageHeader.tsx`, `Alert.tsx` |
| Kelas fokus dan tekan bersama | `gaya.ts` |

Setiap primitif interaktif punya fokus terlihat, target 44 px, dan nama aksesibel, dan labelnya membungkus alih-alih
terpotong (`DesignPrimitivesTest`, `LongInputLayoutTest`). Enam komponen domain yang masih di folder itu
(CheckpointJourney, ElevationProfile, FactorBars, FitBadge, StatusBadge, Freshness; 24 impor dan 5 test) dipindah W3
bersama penulisan ulangnya.

**Pameran** `/dev/primitif` (hanya `APP_ENV=local`) menampilkan setiap primitif dengan contoh isi yang berfungsi.
**Pratinjau** tanpa basis data memakai suite PHPUnit `Pratinjau` dan server statis; caranya di `CONTRIBUTING.md`, bagian
"Pratinjau visual tanpa basis data".

### Navigasi

Lima tab Beranda, Jelajah, Trip, Mendaki, Progres (DK-25). Kerangka v3 (bilah tab pil mengambang di ponsel, header
dengan navigasi segmen di layar lebar, halaman tugas yang menyembunyikan bilah tab, strip pendakian aktif) ditetapkan
spec v3 bagian 5 dan dibangun W2 dengan `BilahTab`. Sampai W2, navigasi bawah lama (`NavLinks.tsx`) tetap dipakai
dengan ikon tab 22 px dan varian isi untuk tab aktif. Jelajah berbasis peta tetap mengikuti keputusan F2 (peta
menggambar bentuk jalur, bukan titik gunung) sampai W3 menulis ulang klasternya.

### Catatan tata kelola

Test yang menyandikan keputusan lama diperbarui menurut niat baru di gelombang 1, dengan alasan di docblock-nya (spec v3
bagian 14):

| Test atau berkas | Keputusan lama | Niat baru |
|---|---|---|
| `ColourContrastTest` | Putih di atas isian merek; non-teks hanya dihitung di tema gelap | `on-brand` di atas `brand-700`; non-teks di kedua tema; label kecocokan, status, toast, dan dua tema lapangan 7:1 |
| `SemanticTokenTest` | Kata peran ada di konfigurasi | Setiap peran dipetakan `token('...')`; skala radius v3 |
| `TypographySystemTest` | Tiga suara, serif judul, Bunny Fonts | Satu keluarga dihosting sendiri, skala bernama, cadangan bermetrik, anggaran 100 KB |
| `SecurityHeadersTest`, `LandingPageTest` | CSP mengizinkan Bunny Fonts | Tanpa host huruf luar |
| `SurfaceSystemTest` | Judul `text-3xl` atau `text-4xl` | Judul `text-judul` |
| `IconSystemTest` | Ikon tab berkelas `h-6 w-6` | Ukuran bernama, bobot per ukuran, varian isi |
| Dial di berkas ini | ENERGY 1, MOTION 1 | ENERGY 2, RHYTHM 2, MOTION 2 (spec v3 bagian 8.9); mode lapangan 1/1 |
````

Run: `php -r '$i=file_get_contents("DESIGN.md"); $a=strpos($i,"## Arah (dari pemilik produk)"); $t=strpos($i,"## Turunan (ditarik agen"); $y=strpos($i,"## Yang tidak dikerjakan"); $c=strpos($i,"# Aturan canonical (R-015"); echo hash("sha256",substr($i,$a,$t-$a)),"\n",hash("sha256",substr($i,$y,$c-$y)),"\n",hash("sha256",substr($i,$c)),"\n";'`
Expected (tiga baris, sama dengan sebelum penyuntingan):

```
37d00315fb2842eb56b3931cd05d8c5ad294ccc127497cf039e8c7bd8a8d8981
ffacb3e25001c09e2a852f8108a03a4787d0a6b3c49bbc93c7290706c71a0065
501349773091c87e61658a779f64319add43dcddd899e2066e423b968a0fa079
```

Run: `grep -c "JAM TERUKUR" DESIGN.md`
Expected: `0`.

- [ ] **Step 4: Daftarkan penjaga baru di panduan kontribusi.** Di `CONTRIBUTING.md` bagian "Aturan yang ditegakkan test",
ganti baris:

```markdown
| Kontras warna dihitung di kedua tema | `ColourContrastTest` |
```

dengan:

```markdown
| Kontras warna dihitung di kedua tema | `ColourContrastTest` |
| Kontras dua tema lapangan paling sedikit 7:1 untuk informasi kritis | `ColourContrastTest` |
| Teks tidak di bawah 12 px; ukuran lewat skala tipe bernama | `TypeFloorTest`, `TypographySystemTest` |
| Ikon berbobot per ukuran, tidak di bawah 16 px, dan tidak ada panah hiasan di label tombol | `IconSystemTest` |
| Primitif: fokus terlihat, target 44 px, nama aksesibel, label membungkus, tiga jenis keadaan kosong | `DesignPrimitivesTest`, `LongInputLayoutTest` |
```

- [ ] **Step 5: Jalankan test tugas ini dan penjaga dokumen sampai lulus.**

Run: `php artisan test --compact --filter='DesignDirectionTest|PrdReferenceIntegrityTest|TeamDocsIndexTest|SecretsNotInRepoTest'`
Expected: PASS, 45 test. `PrdReferenceIntegrityTest` memeriksa bahwa `DESIGN.md`, dokumen hidup, hanya menyebut jalur
`.md` yang ada dan hanya memakai tanda pasal untuk ID PRD.

- [ ] **Step 6: Gerbang tugas.**

Run: `vendor/bin/pint --dirty --format agent`
Expected: `{"tool":"pint","result":"passed"}` atau berkas dirapikan tanpa perubahan makna.

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal. Pada simulasi rencana: 1.522 test, 1.521 lulus, 1 dilewati.

- [ ] **Step 7: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 12: Verifikasi gelombang (koordinator)

Ruling W1-R6. Dikerjakan koordinator sesudah Tugas 1 sampai 11 selesai dan lolos tinjauan per tugas. Setiap klaim di
laporan disertai keluaran perintah, jalur berkas, atau tangkapan (R-023); angka "pada simulasi rencana" di bawah adalah
hasil menjalankan kode rencana ini di salinan repo di scratchpad, bukan hasil di repo, dan menjadi pembanding.

**Files:**
- Create: `docs/desain/tangkapan/w1/*.webp` (tangkapan), `docs/desain/tangkapan/w1/README.md` (perbandingan)
- Modify: `docs/README.md` (satu baris indeks), `HANDOVER.md` (bagian 0), `.superpowers/sdd/2026-09-26-memount-v3-program.md` (baris W1; berkas ini diabaikan git)

**Interfaces:**
- Consumes: seluruh keluaran Tugas 1 sampai 11, harness pratinjau Tugas 10, gambar acuan `docs/desain/prototype/acuan/`.
- Produces: bukti akhir W1 dan titik lanjut untuk W2.

- [ ] **Step 1: Catat keadaan kerja.**

Run: `git status --short | wc -l; git diff --stat | tail -1`
Expected: daftar berkas cocok dengan gabungan daftar Files Tugas 1 sampai 11, ditambah perubahan yang sudah ada
sebelum W1 (`HANDOVER.md`, `.agents/skills/*`, rencana ini). Pada simulasi rencana: 104 berkas berubah atau baru di luar
keadaan awal.

- [ ] **Step 2: Suite penuh, tipe, format.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 1.522 test, 1.521 lulus, 1 dilewati (dasar sebelum W1: 1.338 test).

Run: `npx tsc --noEmit`
Expected: keluar tanpa keluaran galat.

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

- [ ] **Step 3: Build dan ukuran bundel.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: npm run build`
Expected: `✓ built`, dua peringatan `/fonts/plus-jakarta-sans/... didn't resolve at build time` yang memang diharapkan,
dan peringatan ukuran potongan maplibre-gl yang sudah ada sebelum W1. Catat ukuran `app-*.css` dan `app-*.js`. Pada
simulasi rencana: CSS 76.536 bita (sebelum W1 62.652), JS 355.096 bita (sebelum W1 353.916), potongan malas
`Primitif-*.js` 26.636 bita yang hanya dimuat di `/dev/primitif`.

Run: `grep -l "Copyright (c) 2023 Phosphor Icons" public/build/assets/*.js | wc -l; grep -l "@license 3-Clause BSD" public/build/assets/*.js | wc -l`
Expected: dua baris, masing-masing paling sedikit `1`.

- [ ] **Step 4: Suite pratinjau dan server statis.**

Run: `php artisan test --compact --testsuite=Pratinjau`
Expected: PASS, 4 test; `public/_pratinjau/` berisi `primitif.html`, `dasbor.html`, `jelajah.html`, `jelajah-kosong.html`.

Jalankan di latar belakang: `DB_CONNECTION=sqlite DB_DATABASE=:memory: php -S 127.0.0.1:8100 -t public tests/Pratinjau/statis.php`

- [ ] **Step 5: Tangkapan 390 px dan 1440 px, terang dan gelap.** Lewat MCP chrome-devtools. Untuk setiap tema `light` dan
`dark`: buka `http://127.0.0.1:8100/_pratinjau/dasbor.html`, jalankan `evaluate_script` dengan
`() => { document.cookie = 'tema=<tema>; path=/'; }`, lalu untuk setiap halaman `primitif`, `dasbor`, `jelajah` dan
setiap ukuran 390 x 844 dan 1440 x 900: `navigate_page` ke `http://127.0.0.1:8100/_pratinjau/<halaman>.html`,
`resize_page`, lalu `take_screenshot` dengan `fullPage: true`, `format: "webp"`, `quality: 80`, dan `filePath`
`E:\praktik_aplikasi_web\docs\desain\tangkapan\w1\<halaman>-<lebar>-<tema>.webp`. Tambahkan `jelajah-kosong` pada
390 px di kedua tema. Hasilnya 14 berkas.

Bila `take_screenshot` halaman penuh berhenti karena batas waktu protokol (terjadi sekali di simulasi rencana pada
Jelajah), ulangi sekali; bila gagal lagi, ambil tangkapan viewport dan tulis itu di README tangkapan.

Run: `ls docs/desain/tangkapan/w1/*.webp | wc -l`
Expected: `14`

- [ ] **Step 6: Anggaran huruf yang benar-benar dimuat.** Buka `http://127.0.0.1:8100/_pratinjau/dasbor.html` di tab baru
(`new_page`, konteks terpisah `isolatedContext: "huruf"` supaya cache kosong), lalu `evaluate_script`:

```js
() => {
    const huruf = performance.getEntriesByType('resource').filter((e) => e.name.endsWith('.woff2'));
    return { berkas: huruf.map((e) => ({ nama: e.name.split('/').pop(), bita: e.encodedBodySize })), jumlah: huruf.reduce((n, e) => n + e.encodedBodySize, 0) };
}
```

Expected: `jumlah` ≤ 102400. Halaman berteks Indonesia hanya memuat subset Latin (27.348 bita); subset Latin Extended
hanya muncul bila halaman memuat glyph-nya.

Run: `stat -c '%s %n' public/fonts/plus-jakarta-sans/*.woff2`
Expected: `21728 ...latin-ext-wght-normal.woff2` dan `27348 ...latin-wght-normal.woff2` (jumlah 49.076, anggaran 102.400).

- [ ] **Step 7: Klik-lewat pameran primitif (antislop R-26, R-32, R-35).** Di
`http://127.0.0.1:8100/_pratinjau/primitif.html`, 390 px, periksa dan catat hasil setiap butir:
- setiap tombol, tombol ikon, chip, segmen, sakelar, kotak centang, dan stepper mengubah keadaan yang terlihat;
  hitungan "Tombol contoh ditekan" bertambah; stepper berhenti di 1 dan 12;
- chip tafsiran: melepas satu memunculkan toast; "Urungkan" mengembalikannya; melepas semuanya memunculkan "Kembalikan
  tafsiran kalimat";
- "Buka saringan": lembar naik; `press_key` Escape menutupnya; `evaluate_script`
  `() => document.activeElement.textContent.trim()` mengembalikan `Buka saringan`. Ulangi untuk "Buka dialog konfirmasi"
  (fokus pertama di dalam dialog "Batal"; Escape menutup; fokus kembali ke pemicunya);
- selama lembar terbuka, `getComputedStyle(document.documentElement).overflow` bernilai `hidden`;
- toast urungkan: arahkan penunjuk ke toast (`hover`), tunggu lebih dari 8 detik, toast masih ada; lepas penunjuk,
  toast hilang sekitar 8 detik kemudian;
- `evaluate_script` `() => { document.documentElement.dataset.gerak = 'kurangi'; }`, buka lembar lagi, lalu
  `getComputedStyle(document.querySelector('dialog[open]')).animationDuration` bernilai `0.001s`;
- tekan Tab dari awal halaman sampai chip pertama: cincin fokus terlihat di setiap kontrol (tangkapan viewport sebagai
  bukti);
- blok "Teks panjang di 360 px": tidak ada teks yang terpotong atau meluber (tangkapan 390 px dari Step 5).

Pada simulasi rencana, lembar dan dialog, Escape, pengembalian fokus, kunci gulir, toast dengan Urungkan, dan
`animationDuration` `0.001s` sudah terbukti. Mode `forced-colors` tidak diperiksa di W1; catat sebagai sisa untuk W9.

- [ ] **Step 8: Tulis perbandingan tangkapan.** Buat `docs/desain/tangkapan/w1/README.md` berisi judul "# Tangkapan W1",
satu paragraf asal-usul (harness pratinjau, data contoh DemoSeeder, tanggal pengambilan, peramban dan versinya dari
`navigator.userAgent`), lalu tabel:

```markdown
| Tangkapan | Gambar acuan | Yang sudah sesuai | Yang berubah di W2 atau W3 |
|---|---|---|---|
| `dasbor-390-light.webp`, `dasbor-390-dark.webp`, `dasbor-1440-*.webp` | `../../prototype/acuan/01-beranda.png` | ... | ... |
| `jelajah-390-*.webp`, `jelajah-1440-*.webp` | `../../prototype/acuan/02-jelajah.png`, `15-jelajah-lebar.png` | ... | ... |
| `jelajah-kosong-390-*.webp` | `../../prototype/acuan/02-jelajah.png` | ... | ... |
| `primitif-390-*.webp`, `primitif-1440-*.webp` | `../../prototype/acuan/06-simpanan.png`, `08-rencanakan.png`, `09-persiapan.png`, `14-profil.png` | ... | ... |
```

Isi kolom ketiga dan keempat hanya dengan yang terlihat di tangkapan, bukan dengan niat: warna token, huruf dan skala,
ikon, radius, tombol, lencana "Data contoh", keadaan kosong, bilah tab. Pada simulasi rencana terlihat, sebagai
pembanding: kanvas kertas, tombol hijau hutan dan teks on-brand, huruf Plus Jakarta Sans bobot 800 pada judul, lencana
"Data contoh" bergaris putus, tab aktif berikon isi, dan keadaan kosong "menunggu" di Jelajah tanpa jalur terbit; yang
belum sesuai adalah susunan halamannya sendiri (Beranda berfase W5, kartu Jelajah dengan sparkline dan peta W3, kerangka
header dan bilah tab pil W2), karena W1 hanya mengganti fondasi. Jangan menulis "sesuai" tanpa gambar pembandingnya.

Tambahkan baris indeks di `docs/README.md` sesudah baris `[desain/prototype/](desain/prototype/)`:

```markdown
| [desain/tangkapan/](desain/tangkapan/) | Tangkapan pratinjau per gelombang dan perbandingannya dengan gambar acuan prototype |
```

Run: `php artisan test --compact --filter='TeamDocsIndexTest|PrdReferenceIntegrityTest|RepoHygieneTest|SecretsNotInRepoTest'`
Expected: PASS.

- [ ] **Step 9: Delivery Gate antislop, empat blok.** Jalankan gerbang di `.claude/skills/antislop/SKILL.md` bagian
"Delivery Gate (Mandatory)" atas pameran primitif, Dasbor, dan Jelajah, dan tulis laporan PASS atau FAIL per butir
dengan bukti: Blok 1 Hard Gate (R-02, R-03, R-17, R-18, R-23 sampai R-28, R-32 sampai R-38), Blok 2 Purpose-Gate
(R-01, R-04, R-06 sampai R-10, R-12 sampai R-14, R-19, R-22), Blok 3 Liveliness (dial 2/2/2 dan Design Read yang
tertulis di DESIGN.md, satu titik fokus per layar, satu aksen kuning pos, motif garis rute berujung cincin pos), Blok 4
Craftsmanship (C-1 sampai C-5 dan R-05, R-11, R-15, R-16, R-20, R-21, R-29, R-30, R-31). Butir yang FAIL diperbaiki
lewat tugas susulan sebelum W1 dinyatakan selesai.

Run: `grep -rln $'\xe2\x80\x94' resources app tests routes config database DESIGN.md CONTRIBUTING.md docs/lisensi-pihak-ketiga.md docs/ARCHITECTURE.md tailwind.config.js vite.config.js phpunit.xml 2>/dev/null | wc -l`
Expected: `0` (tanpa em dash).

Run: `{ git diff -- resources app database routes config tests | grep '^+'; git ls-files --others --exclude-standard -- resources app database routes config tests | xargs cat; } | grep -c -i -w "aman"`
Expected: `0`: W1 tidak menambah kata "aman" di kode (pada simulasi rencana 0). Sepuluh baris yang sudah ada sebelum W1
adalah penyangkalan atau makna teknis, misalnya kaki Dasbor "tidak menyatakan bahwa suatu gunung atau jalur aman".

- [ ] **Step 10: Perbarui HANDOVER bagian 0.** Tambahkan subbagian "W1 Fondasi desain" di awal `HANDOVER.md` bagian 0,
berisi hanya nilai terukur dari langkah di atas: jumlah test Step 2, ukuran bundel Step 3, bita huruf Step 6, jalur
tangkapan Step 5, hasil Delivery Gate Step 9, jam pilot glyph dari laporan Tugas 4. Sertakan sisa yang tercatat
selama W1: ikon PNG di `public/icons/` belum berganti warna (aset logo menunggu gerbang DK-16); pemeriksaan
`forced-colors` ke W9; pemindahan enam komponen domain ke W3; bilah tab pil dan kerangka ke W2; `memount:demo` belum
dijalankan (Q1); set lengkap glyph menunggu Q15; butir 17 bagian 0 (pemberitahuan lisensi di bundel) sudah tertutup
oleh Tugas 3.

- [ ] **Step 11: Perbarui pelacak program.** Di `.superpowers/sdd/2026-09-26-memount-v3-program.md`, baris W1 kolom
Status menjadi "Selesai, menunggu izin commit" beserta tanggal dan jumlah test Step 2.

- [ ] **Step 12: Rekap.** Laporan akhir W1 mengikuti format rekap `docs/AGENTS.md` bagian "Format rekap": progres dalam
persen dengan metode hitungnya (misalnya butir induk W1 yang selesai dibagi delapan, ditambah ruling W1-R1 sampai
W1-R6), yang selesai, bukti, masalah, akar masalah, solusi, sisa, risiko, pelajaran, dan rencana W2.

- [ ] **Step 13: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

---

## Tinjauan diri

### Cakupan induk W1 dan ruling

| Butir | Isi | Tugas |
|---|---|---|
| Induk W1 butir 1 | Token warna terang, gelap, dua tema lapangan, koreksi kontras | Tugas 1 |
| Induk W1 butir 2 | Plus Jakarta Sans dihosting sendiri, skala tipe, lantai 12 px, anggaran 100 KB | Tugas 2 |
| Induk W1 butir 3 | Registri ikon berbobot per ukuran, lantai 16 px, lisensi di bundel | Tugas 3 |
| Induk W1 butir 4 | Primitif desain | Tugas 5, 6, 7 |
| Induk W1 butir 5 | Pameran primitif | Tugas 8 |
| Induk W1 butir 6 | `DESIGN.md` v3 | Tugas 11 |
| Induk W1 butir 7 | Data contoh DK-35 | Tugas 9 |
| Induk W1 butir 8 | Pilot glyph domain | Tugas 4 |
| W1-R1 | Rumah primitif `components/ui/`; komponen domain dipindah W3 menurut hitungan impor | Tugas 5 (W1-K1), Tugas 11 bagian Primitif |
| W1-R2 | `memount:demo` dan DemoSeeder ditulis dan diuji penjaganya, tidak dijalankan | Tugas 9 |
| W1-R3 | Hanya tiga glyph pilot | Tugas 4 (`test_the_three_pilot_domain_glyphs_are_registered`) |
| W1-R4 | Harness pratinjau: suite terpisah, data contoh SQLite, HTML di `public/_pratinjau/`, `HARUS_DIABAIKAN`, `php -S`, tiga halaman, `CONTRIBUTING.md` | Tugas 10 |
| W1-R5 | Pameran hanya di `APP_ENV=local`, 404 di luar, tidak di sitemap | Tugas 8 (`DevPrimitivesRouteTest`) |
| W1-R6 | Verifikasi koordinator: suite, tsc, build, Pint, Pratinjau, tangkapan, anggaran huruf, Delivery Gate, HANDOVER | Tugas 12 |

### Cakupan tambahan dari dispatch

| Permintaan | Tempat |
|---|---|
| `ColourContrastTest` tambahan | Tugas 1: pasangan non-teks kedua tema, `fieldPairs`, `test_both_field_themes_define_every_critical_token`, `test_the_measured_corrections_to_the_prototype_hold`, `test_text_on_a_brand_fill_uses_the_on_brand_token` |
| `SemanticTokenTest` | Tugas 1 |
| `TypographySystemTest`, `TypeFloorTest` | Tugas 2 |
| Kasus `IconSystemTest` | Tugas 3 (bobot, Blade 16/20 px, lantai 16 px, tanpa panah hiasan) dan Tugas 4 (tiga glyph) |
| `DesignPrimitivesTest` | Tugas 5, diperluas Tugas 6 dan 7 |
| `LongInputLayoutTest` | Tugas 5, 6, 7 (label membungkus) dan Tugas 8 (blok 360 px) |
| `DevPrimitivesRouteTest` | Tugas 8 |
| `DemoCommandGuardTest`, `DemoBadgeTest` (ditambah `DemoSeederTest`) | Tugas 9 |
| Test harness | Tugas 10 (`PratinjauHarnessTest`, suite `Pratinjau`) |
| Tombol ikon, chip, kontrol segmen, sakelar, kotak centang, stepper | Tugas 5 (`TombolIkon`, `Chip`), Tugas 6 |
| Lembar bawah dan dialog (Escape menutup, fokus kembali ke pemicu) | Tugas 7 (`Lembar.tsx`), dibuktikan klik-lewat Tugas 12 |
| Toast urungkan, bar aksi, bilah tab | Tugas 7 |
| Keadaan kosong tiga jenis | Tugas 5 (`EmptyState`) |
| Avatar, lencana | Tugas 5 |
| Fokus terlihat, target 44 px, nama aksesibel | `DesignPrimitivesTest` penyedia `primitifInteraktif`, `TapTargetTest` |
| Gerak menurut spec bagian 8.6 dan reduced motion | Tugas 5 (`gaya.ts`), Tugas 6 (sakelar), Tugas 7 (`MotionTest`), `DESIGN.md` bagian Gerak |
| Catatan saran skill yang diambil dan ditolak (DK-57) | Bagian "Perbandingan skill (DK-57)" |
| Anggaran huruf Latin ≤100 KB dengan asal dan lisensi | Tugas 2 (hash dan ukuran dipaku), Tugas 12 Step 6 (terukur di peramban) |
| `docs/lisensi-pihak-ketiga.md` akurat, Newsreader dan Bunny dilepas | Tugas 2 dan 3 (`ThirdPartyLicenseTest`) |

### Pemindaian placeholder

Tidak ada TBD, TODO, "tambahkan penanganan galat", atau "mirip Tugas N". Satu penanda sengaja, `[JAM TERUKUR TUGAS 4]`
di Tugas 11, diisi dari cap waktu laporan Tugas 4 dengan aturan tertulis ("belum terukur" bila tidak ada), karena
nilainya hanya ada saat eksekusi. Kolom perbandingan tangkapan di Tugas 12 Step 8 sengaja diisi dari pengamatan, dengan
pembanding dari simulasi.

### Konsistensi nama dan tipe

Nama yang dipakai lintas tugas sama di setiap tempat, dan kecocokannya dibuktikan `npx tsc --noEmit` serta suite penuh di
simulasi: `Icon` (`size: UkuranIkon`, `isi`, `label`), `NamaIkon = NamaPhosphor | NamaGlyph`, `bobotUntuk`,
`EmptyState` (`jenis`, `menunggu`), `Lencana` (`nada`, `bentuk`, `garis`), `PilihanSegmen<T>`, `ItemTab`, `DataToast`,
`useToastUrungkan`, `PenjagaDataContoh::periksa()`, `DemoSeeder::EMAIL_PENDAKI` dan `PENANDA`,
`PratinjauTestCase::tulisPratinjau()`, `PenyajiStatis::bolehDisajikan()`, prop Inertia `demo`, dan kelas bersama
`fokusTerlihat`, `fokusKelompok`, `fokusDiDalam`, `umpanTekan`, `umpanTekanKelompok`.

### Review Focus

| Titik tinjau | Dijaga |
|---|---|
| Data lapangan kosong | `DesignPrimitivesTest::test_jelajah_names_who_is_awaited_when_nothing_is_published` (Tugas 5), `jelajah-kosong.html` (Tugas 10), tangkapan Tugas 12 |
| Teks Indonesia panjang di 360 px | `LongInputLayoutTest::test_a_primitive_label_wraps_instead_of_being_cut` (Tugas 5 sampai 7), `test_the_showcase_exercises_long_indonesian_labels_at_360px` (Tugas 8), tangkapan 390 px |
| Keyboard dan pembaca layar di lembar dan dialog | `DesignPrimitivesTest::test_the_sheet_and_dialog_use_the_native_modal_and_return_focus` (Tugas 7), klik-lewat Tugas 12 Step 7 |
| Toast yang hilang sebelum terbaca | `DesignPrimitivesTest::test_the_undo_toast_waits_for_the_reader` (Tugas 7), Tugas 12 Step 7 |
| Huruf gagal dimuat atau jaringan lambat | `TypographySystemTest::test_every_font_face_points_to_a_file_that_exists_and_offers_weight_800`, `test_the_fallback_metrics_keep_the_layout_from_jumping`, `test_the_fonts_come_only_from_our_own_origin` (Tugas 2) |

### Pemeriksaan teks rencana

Diperiksa pada teks rencana yang sudah dirender, 27 September 2026:

- Em dash (U+2014): 0.
- Kata "aman" dan "layak berangkat": hanya muncul sebagai larangan, perintah pemeriksaan, atau kutipan kalimat
  penyangkalan yang sudah ada di Dasbor.
- Tanda pasal yang ditulis rencana ini hanya untuk ID PRD yang ada: §87 Aksesibilitas, §92 Resmi dan komunitas
  terbedakan, §104 Kasus tepi. Satu `§4.1` berada di komentar `resources/css/app.css` yang sudah ada dan disalin apa
  adanya ke blok kode Tugas 1.
- Jalur `.md` di antara backtick: semuanya ada di repo, kecuali `docs/desain/tangkapan/w1/README.md` yang dibuat
  Tugas 12.
- Pola rahasia (JWT, kunci layanan, URL basis data berkredensial, alamat surel pemilik): 0. Kata sandi pendaki contoh
  dibuat `Str::password(20, symbols: false)` saat seeder berjalan dan tidak tertulis di berkas mana pun.
