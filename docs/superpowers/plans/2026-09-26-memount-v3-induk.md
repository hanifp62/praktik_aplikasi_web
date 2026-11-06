# Memount v3 Implementation Plan (rencana induk)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Mewujudkan prototype pemilik sebagai aplikasi Memount yang berfungsi penuh (16 layar dan keadaan yang
dibutuhkan kenyataan), repo yang rapi dan siap push, dokumen tim yang menyatukan visi dan kesepakatan, serta paket
lapangan luring dan meja kerja data resmi, dikerjakan per gelombang yang masing-masing menghasilkan perangkat lunak
teruji.

**Architecture:** Laravel 13 monolit modular (server memegang data dan otorisasi) dengan Inertia 3 dan React 19
(klien memegang interaksi). Satu sumber token di `resources/css/app.css`, primitif desain bebas domain di satu folder
komponen (spec §9.1 menyebut `components/ds/`; rumahnya dikunci di rencana rinci W1, lihat W1 tugas 4), satu kelas
halaman per layar di `app/Services/**` dengan Resource untuk bentuk props, dan service worker tulisan tangan untuk
shell dan paket luring.

**Tech Stack:** PHP 8.5.10, Laravel 13.31.0, Inertia Laravel 3.3.4, `@inertiajs/react` 3.7.1, React 19.3,
TypeScript 5.9, Vite 8, Tailwind CSS 3.4, MapLibre GL JS 4.7.x, PHPUnit, Pint, PostgreSQL + PostGIS (Supabase),
SQLite in-memory untuk test.

**Spec:** `docs/superpowers/specs/2026-09-25-memount-v3-prototype-design.md` (disetujui pemilik 26 September 2026,
termasuk addendum §17). Laporan pendukung: `docs/riset/2026-09-25-luring/reports/Paket luring dan input data resmi.md`
dan `docs/riset/2026-09-25-merek/reports/Tipografi dan ikon merek Memount.md`.

## Global Constraints

- Versi dikunci: PHP 8.5, Laravel 13.31, React 19.3, `@inertiajs/react` 3.7.1, Tailwind 3.4, Vite 8, MapLibre 4.7.x,
  TypeScript 5.9. Tidak ada perubahan versi framework (R-020).
- Tidak ada dependensi runtime baru tanpa izin pemilik (spec §15 Q3). Dependensi dev baru hanya setelah Q17.
- Tidak ada migrasi yang dijalankan di Supabase dan tidak ada akses Supabase tanpa izin (R-019, DK-23). Migrasi
  diuji di SQLite dan PostgreSQL CI. `php artisan migrate` ditolak aturan proyek; data contoh lokal lewat Q1.
- Tidak ada commit dan push tanpa izin pemilik (HANDOVER §0, R-018).
- Bahasa Indonesia, sapaan "Anda", tanpa em dash (antislop R-02), tanpa "aman" dan "layak berangkat" (R-012, R-014).
- Label kecocokan publik hanya "Cocok", "Perlu persiapan", "Kurang cocok" (R-004); "Belum dinilai" adalah keadaan
  UNKNOWN. Tidak ada skor atau persen untuk kecocokan dan kesiapan (R-005, BR-09).
- Huruf: Plus Jakarta Sans variable 400 sampai 800 dihosting sendiri; `tabular-nums` untuk semua ukuran; isi 16 px;
  lantai 12 px; tanpa Geist, Geist Mono, Newsreader (DK-38).
- Ikon: Phosphor dengan bobot per ukuran (Regular ≥20 px, Bold 16 sampai 19 px, tidak ada ikon informatif di bawah
  16 px); tombol tanpa panah hiasan (DK-39).
- Token hanya di `app.css`; tidak ada warna mentah di komponen (`ColourContrastTest`). Kontras teks ≥4,5:1, non-teks
  ≥3:1, info kritis mode lapangan ≥7:1.
- Target sentuh kontrol utama ≥44 px; kontrol mode lapangan 48 sampai 66 px.
- Gerak dial 2 dengan daftar tertutup spec §8.6; `prefers-reduced-motion` dan `data-gerak="kurangi"` dihormati.
- Kaca paling banyak dua elemen per layar lewat `.chrome`; tidak pernah di atas peta, kartu, status resmi, atau
  laporan komunitas.
- Luring: hanya data stabil; data dinamis tampil "terakhir diketahui per waktu cek"; service worker tidak pernah
  menyajikan isi server dari cache; cache `pack-*` tidak pernah dihapus saat `activate`.
- Data resmi: pembatasan boleh terbit satu kurator dengan bukti primer, pembukaan wajib diverifikasi orang kedua;
  `authority_id`, `recorded_by`, `verified_by` dari sesi dan Policy.
- Test berbasis sumber yang bertentangan dengan keputusan baru diperbarui menurut niatnya, dengan alasan tertulis di
  docblock test; tidak dihapus dan tidak dilonggarkan diam-diam (spec §14).
- Setiap gelombang ditutup dengan: suite Unit dan Feature hijau, `npx tsc --noEmit` nol galat, `npm run build`
  sukses, Pint bersih, tangkapan 390 px dan 1440 px disandingkan dengan gambar acuan prototype, klik-lewat setiap
  kontrol, Delivery Gate antislop, dan `HANDOVER.md` §0 diperbarui.
- Urutan wewenang skill: arahan pemilik dan aturan kanonis, lalu `DESIGN.md`, lalu antislop, lalu saran skill
  (DK-57). Setiap gelombang UI menjalankan dan membandingkan ui-ux-pro-max, design-taste-frontend, impeccable, dan
  antislop sebelum menulis komponen.

## Review Focus

- **Data lapangan kosong** (0 geometri, 0 pos berkoordinat, status UNKNOWN di basis data pemilik): setiap halaman
  menampilkan keadaan kosong yang menyebut siapa yang ditunggu, tidak pernah halaman kosong atau galat. Setiap
  kelas halaman baru diuji dengan pabrik tanpa geometri, tanpa pos, dan tanpa status.
- **Status basi atau kedaluwarsa** (`verified_at` lama, `expires_at` lewat, "sampai pemberitahuan lebih lanjut"):
  tidak pernah tampil sebagai terkini atau Buka. Diuji di W3 (detail, gunung), W4 (cek kesiapan), W6 (paket luring),
  W8 (meja kurator).
- **Teks panjang dan angka Indonesia di 360 px** (nama jalur panjang, "Pemberitahuan", "1.650 m", spasi teks WCAG
  1.4.12): tidak meluber. Setiap primitif dan kartu baru dijaga test tata letak teks panjang.
- **Objek milik pengguna lain** (trip, catatan simpanan, tautan bagikan, manifest paket luring, status draf kurator):
  ditolak di server sesuai Policy yang berlaku. Setiap rute baru punya test akses pengguna lain dan tamu.
- **Jaringan putus di tengah aksi** (UI optimis dengan urungkan, `networkError` Inertia, antrean luring): tindakan
  dikembalikan dengan pesan jujur saat server menolak; cache paket bertahan setelah service worker baru aktif.

---

## Tonggak

| Tonggak | Gelombang | Arti |
|---|---|---|
| M1 Siap push | W0, WR | Suite hijau, repo rapi, dokumen tim lengkap, CI dipecah; push menunggu izin pemilik (DK-58) |
| M2 Desain baru terlihat | W1, W2, W3 | Token, huruf, ikon, kerangka, dan klaster Jelajah dengan rupa prototype |
| M3 Lingkaran trip | W4, W5 | Trip, persiapan, cek kesiapan, dan Beranda berfase |
| M4 Lapangan | W6 | Mode Mendaki, strip pendakian aktif, paket luring, ringkasan dan laporan |
| M5 Lengkap | W7, W8, W9 | Progres, Profil, onboarding, meja data resmi, verifikasi penuh |

## Rencana per gelombang

Rencana ini punya dua tingkat. **Tingkat gelombang** (bagian di bawah) berlaku untuk W0 sampai W9: hasil, keputusan
pemilik yang ditunggu, tugas bernomor dengan berkas dan test pemiliknya, dan syarat selesai. Ini kontrak cakupan
yang harus ditutup rencana rinci. **Tingkat langkah** (format `writing-plans`: langkah 2 sampai 5 menit, test lebih
dulu, kode lengkap) sudah ditulis untuk W0 dan WR. Untuk W1 sampai W9 tingkat langkah ditulis tepat sebelum
gelombangnya dimulai, karena kodenya bergantung pada berkas yang diubah gelombang sebelumnya; kode yang ditulis
sekarang akan menebak antarmuka dan nomor baris yang belum ada. Tinjauan diri setiap rencana rinci mencocokkannya
dengan daftar tugas gelombangnya di sini.

| Gelombang | Rencana tingkat langkah | Tergantung pada |
|---|---|---|
| W0 Stabilisasi | `docs/superpowers/plans/2026-09-26-memount-v3-w0-stabilisasi.md` | Tidak ada |
| WR Struktur repo dan dokumen tim | `docs/superpowers/plans/2026-09-26-memount-v3-wr-struktur-repo.md` | W0 |
| W1 Fondasi desain | ditulis sebelum W1 | WR |
| W2 Kerangka | ditulis sebelum W2 | W1 |
| W3 Klaster Jelajah | ditulis sebelum W3 | W2 |
| W4 Klaster Trip | ditulis sebelum W4 | W3 |
| W5 Beranda | ditulis sebelum W5 | W4 |
| W6 Mendaki dan paket luring | ditulis sebelum W6 | W2, W4 |
| W7 Progres, Profil, onboarding | ditulis sebelum W7 | W3, W4 |
| W8 Data resmi | ditulis sebelum W8 | W1 |
| W9 Penutup | ditulis sebelum W9 | Semua |

### W0 Stabilisasi (spec §3.5, DK-46)

**Hasil:** 20 test merah tertutup dengan niat test-nya, bukan dengan melonggarkan test, ditambah dua cacat runtime
yang ditemukan saat menyusun rencana rinci, dan penutup celah MapLibre yang ditambahkan atas keputusan pemilik.
Rencana tingkat langkah (9 tugas; tugas 1 sampai 7 dan 9 diuji kering di salinan proyek):
`docs/superpowers/plans/2026-09-26-memount-v3-w0-stabilisasi.md`.

1. `SchedulerHealthService::TUGAS` memuat `trips:kirim-pengingat` (toleransi 3 jam, usulan agen); dua fixture lama
   diperluas agar menyapu `TUGAS`.
2. Label navigasi di `AuthenticationTest` mengikuti lima tab (Beranda, Jelajah, Trip, Mendaki, Progres), karena
   test lama bertentangan dengan DK-25.
3. Catatan simpanan FR-22 di server: rute `trails.saved.note.update` dan `trails.saved.note.destroy`, Form Request
   (≤500 karakter, kosong berarti hapus, kolom wajib hadir), batas laju, props `catatan`.
4. Halaman Simpanan dengan `KartuSimpanan.tsx` dan `AksiPilihan.tsx` (`SavedRoutesTest`), termasuk editor catatan
   dasar (dimajukan dari W3 supaya rute FR-22 tidak tanpa pemakai) dan perbaikan cacat runtime: impor Wayfinder
   basi membuat "Hapus" mengirim DELETE ke `/trails/compare/{id}` yang tidak ada (FAKTA: `php artisan route:list`
   tidak memuat rute itu).
5. Halaman Banding memakai `pilihan`, `jumlahTersimpan`, dan `pesan`, dan memperbaiki cacat runtime kedua:
   "Hapus dari perbandingan" memanggil `router.delete(undefined)` karena server mengirim untaian URL
   (`RouteComparisonController.php:151`) sedangkan komponen membaca `{metode, url}` (`TabelBanding.tsx:17`).
6. Kartu pengingat FR-24 di halaman trip memakai prop `pengingat`; rute `trips.reminders.update` akhirnya punya
   kontrol di antarmuka.
7. Delapan spesifikasi `docs/fitur/0[1-8]-*.md` dengan sembilan heading `TEMPLAT_FITUR`, memetakan task NgodingPakeAI
   ke kode.
8. Penutup celah XSS atribusi MapLibre CVE-2026-85061 (keputusan pemilik 26 September 2026, Q20):
   `attributionControl: false` di satu-satunya pembuat peta `usePetaDasar.ts` dengan test regresi; atribusi tetap
   tampil sebagai teks lewat `WadahPeta`; versi `maplibre-gl` tetap 4.7.1.
9. Verifikasi gelombang dan `HANDOVER.md` §0.

**Selesai bila:** suite penuh hijau (harapan 1.269 test, 1.268 lulus, 1 dilewati), `tsc` nol terhadap keluaran
Wayfinder yang dibangkitkan ulang, build, Pint. Klik-lewat dan tangkapan menunggu Q1 atau Q2 dan dicatat sebagai
sisa, tidak diklaim. Tidak ada commit sampai M1 (keputusan pemilik 26 September 2026).

### WR Struktur repo dan dokumen tim (spec §17, DK-51 sampai DK-58)

**Hasil:** tonggak M1 "siap push". Rencana tingkat langkah (prasyarat P1 sampai P4 dan 10 tugas, 127 langkah;
dikerjakan berurutan karena tugas-tugasnya berbagi `README.md`, `CONTRIBUTING.md`, `.gitignore`, dan konstanta
test): `docs/superpowers/plans/2026-09-26-memount-v3-wr-struktur-repo.md`.

1. `PRD.md` dipindah ke `docs/PRD.md` dengan semua rujukan jalur di dokumen hidup diperbarui dan dijaga
   `PrdReferenceIntegrityTest`; suntingan PRD dicatat sebagai governance record GR-04 (PRD §130, §146).
2. Inventaris root dengan keputusan per entri (`RepoHygieneTest`); prototype dan 16 gambar acuan ke
   `docs/desain/prototype/` (hash dijaga); `ourplan.md` ke `docs/arsip/`; `.gitignore` diperketat (ruang kerja alat,
   berkas lokal, dump SQL, `database/demo.sqlite`); `jelajah-gunung.zip` hanya atas keputusan pemilik.
3. CI: `test.yml`, `security.yml`, `deploy.yml` manual (DK-54), dengan mayor action yang berjalan di Node 24 (FAKTA:
   GitHub melepas Node 20 dari runner pada 23 September 2026, GitHub Changelog 19 September 2025); pemindai rahasia
   ikut membaca berkas baru yang belum dilacak (`CiWorkflowsTest`, `SecretsNotInRepoTest`).
4. `docker-compose.yml` PostgreSQL + PostGIS (DK-55), paritas dengan layanan CI (`LocalPostgisComposeTest`).
5. `SECURITY.md`, `database/README.md`, templat PR dan isu (`RepoPolicyFilesTest`).
6. Tata kelola skill: `docs/MATRIKS-SKILL.md` diperbarui (DK-57, `SkillGovernanceTest`).
7. Teks lisensi MIT Phosphor dan `docs/lisensi-pihak-ketiga.md` (`ThirdPartyLicenseTest`).
8. `docs/AGENTS.md` kesepakatan kerja lengkap (DK-53) dengan kutipan visi, misi, prioritas, dan kata-kata pemilik
   yang dijaga identik dengan PRD; `docs/README.md` indeks; `docs/DESIGN.md` dan `docs/PRODUCT.md` penunjuk; bagian
   Memount di `AGENTS.md` root di luar blok Boost; `CLAUDE.md` menunjuk `docs/AGENTS.md` (`TeamDocsIndexTest`).
9. `tests/e2e` dengan Playwright bila Q17 disetujui.
10. Verifikasi M1 dan `HANDOVER.md` §0.

**Selesai bila:** suite, `tsc`, build, Pint, pemindai rahasia bersih, ringkasan diff siap ditinjau pemilik. Dua butir
M1 bergantung pada keputusan pemilik dan dicatat PERLU KLARIFIKASI sampai itu: status migrasi Supabase (Q22) dan
`npm audit` yang merah karena advisori MapLibre (Q20).

### W1 Fondasi desain (spec §8, §9.1, §11; DK-30, DK-31, DK-35, DK-38, DK-39)

**Hasil:** setiap halaman yang ada langsung memakai token, huruf, dan ikon v3, dan primitif siap disusun W2 sampai
W8.

**Menunggu keputusan:** Q1 untuk menjalankan `memount:demo` (tugas 7; kodenya tetap dibuat dan diuji tanpa
dijalankan); Q15 untuk set glyph lengkap (pilot tidak menunggu).

1. **Token v3.** `resources/css/app.css` blok terang, gelap, dan dua tema lapangan (DK-49) dari spec §8.1 dengan
   koreksi DK-31; `tailwind.config.js` memetakan peran. Test: `ColourContrastTest` mendapat pasangan batas kontrol
   `#8C8579` di `#FFFFFF` (≥3:1), garis komunitas `#5F82B8` di `#F2F6FC` (≥3:1), redup `#626965` di `#EEEAE1`
   (≥4,5:1), dan info kritis tema lapangan (≥7:1); `SemanticTokenTest` memuat daftar token baru;
   `SurfaceSystemTest::test_the_canvas_is_warm` tetap lulus.
2. **Huruf DK-38.** Plus Jakarta Sans variable dari rilis resmi Tokotype ke `public/fonts/plus-jakarta-sans/`
   beserta `OFL.txt`; `@font-face` dengan metrik fallback spec §8.2 di `app.css`; preload subset Latin di
   `resources/views/components/brand/head.blade.php`; `fonts.bunny.net` dilepas dari kepala dokumen dan dari CSP
   `app/Http/Middleware/SecurityHeaders.php`; `tailwind.config.js` tanpa `serif` Newsreader dan `mono` berupa
   tumpukan sistem; isi 16 px; lantai 12 px. Test: `TypographySystemTest` diperbarui menurut niat DK-38 (satu
   keluarga dihosting sendiri, tanpa host luar, bobot 800 tersedia, `tabular-nums`, `body` 16 px; test "display face
   dan measurement face" berganti menjadi "satu keluarga dengan angka tabular") dan test baru `TypeFloorTest` yang
   menolak ukuran di bawah 12 px (`text-[10px]`, `text-[11px]`, dan sejenisnya) di `resources/js`.
3. **Ikon DK-39.** `resources/js/components/ui/Icon.tsx` saat ini hanya punya varian regular dan fill, dengan ukuran
   bawaan 16 px. Tambah prop `size` (16, 20, 22, 24) yang memilih varian Bold untuk 16 sampai 19 px dan Regular
   untuk ≥20 px, varian isi untuk tab aktif, dan penolakan ikon informatif di bawah 16 px; path Bold dari
   `phosphor-icons/core` (MIT). Panah 12 px di `resources/js/components/ui/CheckpointJourney.tsx` menjadi 16 px Bold.
   Test: `IconSystemTest` mendapat kasus bobot per ukuran, lantai 16 px, dan "tidak ada ikon panah di label tombol".
4. **Rumah primitif dan primitif baru.** Spec §9.1 menulis `components/ds/`, sedangkan `components/ui/` sudah berisi
   primitif (Button, Card, EmptyState, Field, PageHeader, Skeleton, Icon, Alert) bercampur komponen domain
   (CheckpointJourney, ElevationProfile, FactorBars, FitBadge, StatusBadge, Freshness). Rekomendasi untuk rencana
   rinci: `ui/` tetap rumah primitif dan komponen domain pindah ke folder domainnya, karena lebih sedikit impor
   berubah dan mengikuti pedoman Boost "stick to existing directory structure"; bila pemilik setuju, spec §9.1 diberi
   catatan. Primitif yang dilengkapi: tombol ikon, chip, kontrol segmen, sakelar, kotak centang, stepper, lembar bawah
   dan dialog, toast urungkan, bar aksi, bilah tab, keadaan kosong tiga jenis (pertama kali, tersaring habis,
   menunggu data), avatar, lencana. Test: `DesignPrimitivesTest` (fokus terlihat, target ≥44 px, nama aksesibel,
   atribut `aria-*` per primitif, lembar tertutup dengan Escape dan fokus kembali ke pemicunya) dan
   `LongInputLayoutTest` diperluas dengan teks Indonesia panjang di 360 px.
5. **Halaman pameran primitif** hanya untuk `APP_ENV=local` (`resources/js/pages/Dev/Primitif.tsx`, rute terdaftar
   bersyarat) sebagai tempat tangkapan 390 px dan 1440 px dan klik-lewat primitif. Test: `DevPrimitivesRouteTest`
   (404 di luar `local`, tidak masuk peta situs).
6. **`DESIGN.md` v3.** Bagian Turunan ditulis ulang dari spec §8; Arah pemilik dan R-015 sampai R-017 tidak diubah;
   catatan tata kelola untuk test yang niatnya berubah. Test: `DesignDirectionTest` tetap lulus.
7. **Data contoh DK-35.** `database/seeders/DemoSeeder.php`, perintah `memount:demo`
   (`app/Console/Commands/MemountDemo.php`), koneksi `demo` di `config/database.php`, `APP_DEMO` bawaan `false`,
   lencana DATA CONTOH lewat props bersama. Test: `DemoCommandGuardTest` (menolak driver selain `sqlite`, berkas di
   luar `database/`, `APP_ENV` selain `local`) dan `DemoBadgeTest`.
8. **Pilot tiga glyph domain** (pos bernomor, tanjakan, resmi) di registri ikon dengan grid 256 dan tebal goresan
   tanda "Jalur Berpos"; jam kerjanya dicatat untuk keputusan set lengkap. Test: glyph terdaftar dan lulus aturan
   `IconSystemTest`.

**Selesai bila:** aturan penutup gelombang terpenuhi; `EveryRouteRendersTest` dan `PageSmokeTest` tetap hijau;
anggaran huruf ≤100 KB diukur di panel Network.

### W2 Kerangka (spec §5; DK-33, DK-40)

**Hasil:** satu kerangka ponsel dan layar lebar untuk semua halaman yang ada, sehingga W3 sampai W8 hanya mengisi
halaman.

**Menunggu keputusan:** tidak ada (notifikasi dorong tetap di luar cakupan sampai Q6).

1. **Header per halaman.** `resources/js/layouts/AppLayout.tsx` ponsel tanpa bilah atas global: akar tab memakai h1,
   aksi kontekstual, dan avatar 44 px ke Profil; halaman turunan memakai tombol kembali 44 px ke induk logis dari
   prop `induk`, bukan riwayat peramban. Semua halaman yang ada memberi `judul` dan `induk`. Test: `InertiaLayoutTest`
   dan test baru `PageHeaderContractTest` (setiap halaman di `resources/js/pages` selain Auth dan Welcome memberi
   judul; halaman turunan memberi induk).
2. **Bilah tab mengambang.** `resources/js/layouts/NavLinks.tsx` menjadi satu sumber daftar tab untuk bilah tab ponsel
   dan navigasi segmen layar lebar: lima tab, tinggi ≥52 px, label 12 px, ikon 22 px, varian isi dan
   `aria-current="page"` pada tab aktif, `env(safe-area-inset-bottom)`, disembunyikan pada halaman tugas yang punya
   bar aksi (detail jalur, penilaian kecocokan, rencanakan). Test: `NavigationConsistencyTest` diperbarui (Profil dari
   avatar, bukan tab) dan `TapTargetTest`.
3. **Layar lebar ≥1024 px (DK-33).** Header 68 px, lockup di kiri, navigasi segmen di tengah, tautan Simpanan dengan
   hitungan, menu avatar (Profil; Moderasi dan Admin sesuai peran; Keluar). Hitungan simpanan dikirim sebagai props
   bersama dengan satu kueri terindeks; dampaknya pada `PageQueryBudgetTest` diukur dan dicatat. Test: menu peran
   diuji per peran; `NavigationConsistencyTest::test_a_hiker_never_sees_the_admin_entry` tetap lulus.
4. **Strip pendakian aktif.** Props bersama `pendakianAktif` dari `app/Http/Middleware/HandleInertiaRequests.php`
   (trip milik pengguna berstatus berlangsung: nama jalur, pos berikutnya, waktu mulai); strip 48 px di atas bilah tab
   di semua halaman kecuali mode lapangan. Test: `ActiveHikeStripTest` (tampil untuk pemilik, tidak untuk pengguna
   lain, tidak di `/trips/{trip}/hike`, anggaran kueri).
5. **Lembar Pengingat** dari lonceng Beranda: pengingat trip terjadwal (`trip_reminders`) dan tenggat izin milik
   pengguna. Test: `ReminderSheetTest` (hanya milik sendiri; keadaan kosong menyebut apa yang akan muncul di sana).
6. **Wadah toast dan lembar** dipasang sekali di layout; `router.on('networkError')` menampilkan pesan jujur
   (penawaran "Buka paket luring" menyusul di W6).

**Selesai bila:** tangkapan 390 px dan 1440 px setiap akar tab disandingkan dengan prototype 01, 02, 13, dan 15;
jalur keyboard melewati header, isi, dan bilah tab dengan fokus terlihat.

### W3 Klaster Jelajah (spec §6.2 sampai §6.7, §9.2, §9.3; DK-31, DK-40, DK-44)

**Hasil:** Jelajah, detail jalur, penilaian kecocokan, halaman gunung, Simpanan, dan Bandingkan berfungsi dengan rupa
prototype 02 sampai 07 dan 15.

**Menunggu keputusan:** tidak ada untuk pengembangan. Migrasi tabel tautan bagikan diuji di SQLite dan PostgreSQL CI
dan baru dijalankan di Supabase setelah Q2.

1. **Jelajah daftar** (`resources/js/pages/Trails/Index.tsx`, `app/Http/Controllers/Trails/TrailIndexController.php`,
   `app/Services/Trails/TrailSearch.php`): chip tafsiran kalimat yang dapat dilepas dan "Kembalikan tafsiran
   kalimat"; lembar saringan dengan hitungan langsung "Tampilkan N jalur"; kartu jalur (label ikon dan kata, simpan
   44 px dengan urungkan, tiga angka, garis profil berskala vertikal sama, satu alasan, status resmi bersumber,
   "Kenapa?"); kartu "Belum bisa dinilai" bergaris putus-putus yang tidak dihitung; "N jalur disembunyikan saringan"
   dengan "Tampilkan juga"; keadaan pertama kali, tersaring habis, dan memuat. Test: `TrailIndexSearchTest` dan
   `ResultListBudgetTest` diperluas; `TrailCardTest` (tanpa skor di props, skala profil sama di semua kartu, jalur
   menunggu data tidak dihitung dan tidak direkomendasikan).
2. **Peta Jelajah** (`resources/js/components/trails/PetaJelajah.tsx`, `resources/js/components/peta/`,
   `app/Services/Trails/PetaSebaranJalur.php`): pola garis per label (utuh, putus panjang, titik-titik) dengan
   legenda, jalur terpilih lebih tebal, chip pemilih, kartu pratinjau, kalimat jumlah jalur tanpa geometri, atribusi
   OpenTopoMap termasuk SRTM. Test: `TrailIndexMapTest`, `MapConfigTest` (rumusan atribusi),
   `tests/Spatial/PetaSebaranJalurTest.php`.
3. **Detail jalur** (`resources/js/pages/Trails/Show.tsx`, `resources/js/components/trails/rinci/`,
   `app/Services/Trails/TrailDetailPage.php`): urutan PRD §89 dan isi spec §6.4, bar aksi tetap (Simpan, Banding,
   Rencanakan trip), ruas tercuram diarsir dengan keterangan kalimat, bagian gunung api tampil hanya bila datanya ada
   (tabelnya dibuat W8). Test: `TrailDetailPageTest`, `ElevationProfileDisplayTest`, dan `TrailDetailMapTest`
   diperluas.
4. **Penilaian kecocokan** `GET /trails/{trail:slug}/kecocokan` dengan `?trip=` opsional: kelas halaman
   `app/Services/Trails/PenilaianKecocokan.php`, controller `app/Http/Controllers/Trails/TrailFitAssessmentController.php`,
   halaman `resources/js/pages/Trails/Fit.tsx`; tujuh faktor diurut bobot, bobot dalam kata dari
   `recommendation_rules`, keadaan per faktor termasuk "Belum diketahui", "Kenapa bukan Cocok?", "Yang bisa Anda
   lakukan", versi aturan. Test: `FitAssessmentPageTest` (tidak ada angka skor atau bobot mentah di props, pola
   `FactorBarsDoNotLeakScoreTest`; trip milik orang lain ditolak; faktor UNKNOWN tidak tampil sebagai rendah; anggaran
   kueri).
5. **Halaman gunung** `GET /mountains/{mountain:slug}` (DK-44): kelas halaman
   `app/Services/Mountains/HalamanGunung.php`, controller `app/Http/Controllers/Trails/MountainPageController.php`,
   halaman `resources/js/pages/Mountains/Show.tsx`; hero visual data sampai foto W8 ada; kalimat BR-02 dan R-012;
   hanya jalur terbit; izin dengan "Memount menautkan sistem resmi dan tidak menjual izin"; kondisi komunitas;
   provenans. Test: `MountainPageTest` (jalur draf tidak tampil, tamu dialihkan ke masuk, keadaan kosong, anggaran
   kueri).
6. **Simpanan dan tautan bagikan FR-23** (`resources/js/pages/Trails/Saved.tsx`): editor catatan dasar dan hitungan
   "Dipilih N dari 3" sudah dipasang W0; W3 menyelaraskan rupanya dengan prototype 06 memakai primitif W1 dan menambah
   simpan dengan urungkan. Tabel token (hash token, pemilik, daftar jalur, `expires_at` 7 hari,
   `revoked_at`), `POST` buat, `DELETE` cabut, halaman publik `GET /b/{token}`
   (`resources/js/pages/Public/SharedComparison.tsx`) tanpa catatan dan label kecocokan, `noindex`, batas laju. Tombol
   bagikan baru tampil setelah rute ini ada (DK-40). Test: `ComparisonShareTest` (token ≥128 bit disimpan sebagai
   hash, kedaluwarsa dan dicabut tidak dapat dibuka, pengguna lain tidak dapat mencabut, `noindex`, props publik tanpa
   catatan dan label, batas laju).
7. **Bandingkan** (`resources/js/pages/Trails/Compare.tsx`, `app/Services/Trails/RouteComparisonTable.php`): "Jadikan
   dasar rencana" berbobot setara per kolom (DK-31), sakelar "Sorot yang berbeda", bilah dan profil berskala sama,
   bagikan. Test: `RouteComparisonTest` dan `ComparisonAsCostTest` diperluas (tidak ada pemenang, R-016).

**Selesai bila:** aturan penutup gelombang terpenuhi untuk tujuh layar ini, termasuk keadaan kosong dengan data
pemilik yang masih tanpa geometri.

### W4 Klaster Trip (spec §6.8 sampai §6.11; DK-48)

**Hasil:** lingkaran rencanakan, persiapan, dan cek kesiapan dengan rupa prototype 08 sampai 10, ditambah indeks trip
yang tidak ada di prototype.

**Menunggu keputusan:** tidak ada.

1. **Indeks trip** `/trips`: kelas halaman `app/Services/Trips/IndeksTrip.php`, `resources/js/pages/Trips/Index.tsx`;
   kartu trip terdekat dengan fase dan aksi berikutnya, kelompok Berlangsung, Mendatang, Selesai; keadaan kosong spec
   §6.9. Test: `TripIndexPageTest` (hanya milik sendiri, pengelompokan, aksi per fase, anggaran kueri).
2. **Rencanakan** `/trips/create` (`resources/js/pages/Trips/Create.tsx`, `resources/js/components/trips/rencana/`,
   `app/Http/Controllers/Trips/TripController.php`): kecocokan untuk rencana dihitung server lewat muat ulang parsial
   Inertia saat jenis trip atau tanggal berubah; jendela izin dari `permit_requirements`; kalimat terbitnya prakiraan
   BMKG; jam dalam zona gunung; peserta 1 sampai 12; kuota belum terverifikasi; pengingat H-3 dan H-1; ringkasan
   tetap; jalur berstatus Tutup dapat direncanakan untuk tanggal mendatang dengan peringatan. Test:
   `TripPlanFitReloadTest` (props parsial hanya label dan alasan, tanpa skor), `TripPermitWindowTest` diperluas,
   validasi peserta.
3. **Ruang kerja dan persiapan** (`resources/js/pages/Trips/Show.tsx`, `resources/js/pages/Trips/Preparation.tsx`,
   `app/Services/Trips/TripSteps.php`, `app/Services/PreparationService.php`): empat langkah, hitungan N dari M butir
   kritis, kelompok `PreparationCategory` dengan tanda KRITIS, butir menunggu ("Menunggu BMKG: terbit mulai ..."),
   centang optimis dengan urungkan, daftar pengingat. Test: `PreparationServiceTest` diperluas dan
   `PreparationUndoTest` (urungkan memulihkan keadaan; butir trip orang lain ditolak).
4. **Cek kesiapan** (`resources/js/pages/Trips/Readiness.tsx`, `app/Services/Readiness/ReadinessAssessment.php`):
   label DK-48 pada `app/Enums/ReadinessState.php`, kalimat R-014, tiga hal, "Sejak cek terakhir" dari perbandingan
   snapshot `readiness_checks`, pesan hari keberangkatan, "Saya sudah meninjau semuanya" mencatat
   `pre_departure_confirmed`. Test: `ReadinessDashboardTest` diperbarui ke label DK-48, `ReadinessSinceLastCheckTest`,
   dan `StaleReadinessTest`. Catatan tata kelola PRD §146 untuk label DK-48.

**Selesai bila:** aturan penutup gelombang terpenuhi untuk lima layar ini.

### W5 Beranda (spec §6.1; DK-45)

**Hasil:** Beranda berfase B0 sampai B4 dengan satu aksi utama per fase, rupa prototype 01.

**Menunggu keputusan:** tidak ada.

1. **Fase.** `app/Services/Dashboard/RingkasanDasbor.php` diperluas dari empat keadaan menjadi fase B0 sampai B4 dengan
   aksi fase B2 menurut keadaan trip (izin, pengingat jendela, persiapan, cek kesiapan, mulai pendakian). Test:
   `DashboardPhaseTest` (setiap fase dari pabrik, tepat satu aksi utama, tanpa skor) dan `DashboardRelevanceTest`
   diperbarui.
2. **Tenggat:** jendela izin tutup, tanggal terbit prakiraan BMKG (tanggal trip dikurangi tiga hari), pengingat
   terjadwal; waktu dalam zona gunung dengan WIB, WITA, WIT. Test: `DashboardDeadlineTest` (urutan dan zona);
   `TimestampZoneTest` tetap lulus.
3. **Kabar gunung yang diikuti** dengan pemisahan resmi dan komunitas (R-010) dari
   `app/Services/Community/KabarJalur.php`. Test: `TrailNewsTest` diperluas.
4. **"Jalur lain untuk Anda"** paling banyak tiga dari `TrailFitService`, tanpa jalur yang sedang direncanakan dan
   tanpa jalur tidak lengkap (R-008), masing-masing dengan label, garis profil, dan satu alasan; sapaan waktu dihitung
   di klien. Test: `RecommendationExclusionTest` diperluas; `PageQueryBudgetTest` untuk Beranda.
5. **Susunan** `resources/js/pages/Dashboard.tsx` dengan kartu yang tingginya mengikuti isi (DK-31) dan tangga
   tanjakan bila ada riwayat.

**Selesai bila:** aturan penutup gelombang terpenuhi untuk kelima fase, masing-masing dengan tangkapan.

### W6 Mendaki dan paket luring (spec §6.12, §6.13, §10; DK-36, DK-49, DK-50)

**Hasil:** mode lapangan yang dapat dipakai di jalur, paket lapangan luring yang terverifikasi, dan ringkasan serta
laporan pendakian, rupa prototype 11 dan 12.

**Menunggu keputusan:** Q7 (amandemen PRD paket lapangan) sebelum tugas 3 sampai 7; Q9 (ambang turun status Buka)
sebelum tugas 6; Q10 (kebijakan data di perangkat) sebelum paket dibuka untuk pengguna; Q14 (telaah hukum) sebelum
paket didistribusikan publik; Q17 (Playwright) untuk uji luring otomatis, tanpa itu uji luring manual di Chrome
DevTools dengan bukti tangkapan.

1. **Pintu tab** `/mendaki` (`app/Http/Controllers/Trips/TrackController.php`, `resources/js/pages/Track/Index.tsx`):
   pratinjau hari-H trip terdekat (keadaan paket, penjelasan izin lokasi, rekam jejak mati bawaan) dan "Mulai
   pendakian" 66 px. Test: `TrackTabTest` diperluas.
2. **Mode lapangan** (`resources/js/pages/Trips/Hike.tsx`, `resources/js/components/trips/`,
   `app/Services/Trips/HikeCheckpointResolver.php`): isi spec §6.12, tema lapangan DK-49, kecilkan ke strip, tiba di
   pos otomatis bila jarak ditambah akurasi ≤75 m dan bertanya bila tidak, pembulatan jarak (50 m di bawah 1 km, 0,1
   km di atasnya), naik lagi dari profil elevasi, sakelar layar tetap menyala mati bawaan. Test:
   `HikeModeCheckpointTest` diperluas dan `FieldModeFiguresTest` (pembulatan, naik dari profil, rentang waktu atau
   "belum ada data waktu tempuh").
3. **Service worker** (`resources/views/sw.blade.php`, `resources/views/offline.blade.php`, entri Vite baru
   `resources/js/luring/shell.tsx`): `activate` hanya menghapus cache `shell-` usang, navigasi jaringan lebih dulu
   dengan shell luring sebagai cadangan, endpoint dinamis hanya jaringan. Test: `ProgressiveWebAppTest` mendapat
   regresi `test_activate_never_deletes_pack_caches` dan `test_the_offline_shell_never_serves_server_content`.
4. **Manifest paket** `GET /trips/{trip}/paket` (kelas `app/Services/Trips/PaketLapangan.php`, Policy pemilik trip,
   `Cache-Control: no-store` untuk bagian pribadi): versi hash isi, bagian dengan ukuran dan SHA-256, potret dinamis
   dengan waktu pengetahuan, atribusi; hanya jalur terbit; tanpa GPX komunitas, keluaran AI, skor, dan peserta lain.
   Test: `FieldPackManifestTest` (pemilik saja, tamu dan pengguna lain ditolak, jalur draf dikecualikan, tidak ada
   kunci skor, waktu potret sama dengan `verified_at`).
5. **Unduh dan verifikasi di klien** (`resources/js/luring/paket.ts`, `resources/js/luring/simpan.ts`): IndexedDB per
   pengguna `memount-luring-<hash id>`, cek ruang, `persist()` pada ketukan, lanjut per bagian, "Siap luring" hanya
   setelah setiap bagian dibaca ulang dan cocok dengan manifest, versi lama bertahan sampai versi baru lolos, sakelar
   "Pakai paket saja". Bukti: uji di peramban dengan jaringan dimatikan (Playwright bila Q17, manual bila tidak).
6. **Data dinamis luring** (spec §10.3): label "terakhir diketahui" dengan ikon jam; Buka turun menjadi "Status belum
   dapat dipastikan sejak ..." setelah ambang Q9; cuaca "belum terbit saat paket dibuat". Ambang dihitung server dan
   dikirim di manifest. Test: `FieldPackStalenessTest`.
7. **Keluar dan pergantian akun:** periksa jejak belum terkirim, hapus basis data paket, cache `pack-*`, dan OPFS;
   respons logout mengirim `Clear-Site-Data: "storage"`; basis data `resources/js/jejak.js` diikat ke pengguna. Test:
   `LogoutClearsDeviceDataTest` dan test sumber untuk nama basis data `jejak.js`.
8. **Keselamatan** (Q14 butir kedua): kartu darurat (112 dan 115 sebagai tautan telepon, kontak pengelola bila resmi
   diketahui, "tetap di tempat setelah menelepon", blok lokasi), versi cetak `/trips/{trip}/cetak`, pesan rencana
   perjalanan WhatsApp atau SMS dengan eskalasi dan pernyataan bahwa Memount tidak memantau perjalanan. Test:
   `EmergencyCardTest` dan `TripPlanMessageTest` (jam kembali ditambah satu jam, tanpa kata "aman").
9. **Ringkasan dan laporan** (`resources/js/pages/Community/HikeSummary.tsx`,
   `app/Services/Community/HasilPendakian.php`): varian selesai dan dibatalkan, alasan pembatalan DK-50 (kolom enum
   opsional di `trip_plans` dengan daftar putih), angka dengan "Ubah angka", dibanding diri sendiri (R-015), formulir
   laporan dengan foto tanpa GPS, "Ubah sebelum dimoderasi". Test: `HikeSummaryTest` diperluas dan
   `TripCancellationReasonTest`.

**Selesai bila:** aturan penutup gelombang terpenuhi, ditambah uji luring di peramban: paket terunduh, jaringan
dimatikan, mode lapangan terbuka dari paket, service worker versi baru aktif tanpa menghapus paket.

### W7 Progres, Profil, onboarding (spec §6.14 sampai §6.16; DK-41, DK-42)

**Hasil:** Progres, Profil, dan onboarding dengan rupa prototype 00, 13, dan 14; tiga stub test (target, perlengkapan,
catat manual) menjadi fitur nyata.

**Menunggu keputusan:** tidak ada untuk pengembangan; kolom baru di Supabase setelah Q2.

**Catatan skema (FAKTA, 26 September 2026):** migrasi `2026_09_25_000920_create_personal_targets_table` dan
`2026_09_25_000921_create_user_owned_items_table` hanya berisi `id` dan `timestamps`, dan
`2026_09_25_000919_add_source_and_recorded_values_to_hiking_history_table` kosong; ketiganya belum dilacak git.
Tugas 2 sampai 4 mengisi skemanya lebih dulu (kolom, kunci asing, indeks, RLS pada PostgreSQL seperti migrasi
saudaranya). Bila salah satu sempat dijalankan di Supabase sebelum W7, skemanya ditambahkan lewat migrasi baru, bukan
dengan menyunting berkas yang sudah berjalan. Empat test rintisan (`PersonalTargetTest`, `OwnedEquipmentTest`,
`ManualHikeRecordTest`, `PersonalDataWriteGuardTest`) hanya membuka `/` dan diganti test nyata di gelombang ini.

1. **Hub progres** (`app/Services/HikerProgressService.php` atau kelas halaman baru `app/Services/HubProgres.php`,
   `resources/js/pages/Community/HikerProgress.tsx`): ringkasan tahun, grafik per pendakian dengan pilihan Tanjakan
   atau Jarak, pendakian batal digambar putus-putus, keterangan kalimat, riwayat dengan status laporan. Test:
   `HikerProgressTest` dan `ProgressHierarchyTest` diperluas (tanpa pembanding pendaki lain, R-015).
2. **Target pribadi FR-25:** rute tulis di bawah `/progres`, Form Request, Policy pemilik, tanpa streak dan tanpa
   teguran. Test: stub `PersonalTargetTest` diganti test nyata (pemilik saja, validasi, teks tanpa kata menghukum).
3. **Catat pendakian di luar trip FR-25:** `hiking_history.source = manual`, validasi tanggal tidak di masa depan dan
   rentang angka. Test: stub `ManualHikeRecordTest` diganti test nyata.
4. **Perlengkapan pribadi:** CRUD di Profil atas `user_owned_items`, mengisi awal daftar persiapan yang tetap
   dikonfirmasi per trip. Test: stub `OwnedEquipmentTest` diganti test nyata; `PreparationServiceTest` untuk isi awal.
5. **Hub Profil** (`resources/js/pages/Profile/Edit.tsx`): identitas dengan avatar unggahan (EXIF dibuang, ukuran
   dibatasi) atau inisial, pengalaman, preferensi termasuk wilayah minat DK-42 (kolom JSON dengan daftar putih kode
   provinsi), tampilan (pilihan bahasa tetap disembunyikan, DK-40), privasi, pintu konsol sesuai peran, keluar. Test:
   `ProfileTest` diperluas dan `InterestRegionTest`.
6. **Onboarding DK-41** (`resources/js/pages/Onboarding/ProfileSetup.tsx`,
   `app/Http/Controllers/Onboarding/ProfileSetupController.php`): turunan medan dan navigasi dari jalur terberat
   dengan konfirmasi, isian manual, "Belum pernah mendaki", akibat langsung "N jalur berlabel Cocok" dihitung server,
   "Nanti saja" ke Jelajah dengan "Belum dinilai". Test: `OnboardingDerivationTest` (turunan benar; dilewati tetap
   UNKNOWN, bukan rendah, R-006).
7. **Pencapaian turunan** dari riwayat sendiri, tanpa kompetisi. Test: pencapaian tidak pernah memakai data pengguna
   lain.

**Selesai bila:** aturan penutup gelombang terpenuhi untuk tiga layar ini dan tiga stub test berisi perilaku nyata.

### W8 Data resmi (spec §10.7; DK-37, DK-43)

**Hasil:** meja kerja kurator yang menyalin status dari sumber resmi dengan provenans lengkap, status gunung api, dan
foto gunung berlisensi.

**Menunggu keputusan:** Q11 (peran kurator dan aturan terbit asimetris) sebelum tugas mana pun; Q12 (kebijakan
tingkat kawasan terhadap rekomendasi) sebelum pemeriksaan konsistensi kawasan; Q9 untuk jadwal tinjau per jenis
status. Q13 adalah tindakan keluar pemilik dan tidak menahan gelombang ini.

1. **Provenans status resmi.** Migrasi kolom spec §10.7 pada `official_statuses` dan enum pendukungnya; skema aktual
   diperiksa lebih dulu, dan enum yang sudah ada (`VerificationStatus`, `SourceType`, `StatusScope`) dipakai ulang
   bila artinya sama. Test: migrasi di SQLite dan PostgreSQL CI; `OfficialStatusPageTest` tetap lulus.
2. **`authority_members` dan peran `curator`** (`app/Enums/UserRole.php`) terpisah dari moderator dan admin, dengan
   Policy yurisdiksi. Test: `AuthorityMemberPolicyTest`.
3. **Layanan terbit asimetris:** pembatasan oleh satu kurator dengan bukti primer bertanda menunggu verifikasi kedua;
   pembukaan wajib orang kedua; berita saja tidak pernah membuka; rantai penggantian; pembersihan cache; audit
   sebelum-sesudah; `recorded_by`, `verified_by`, `authority_id` dari sesi. Test: `AsymmetricPublishRuleTest`,
   `OfficialStatusSupersedeTest`, `StatusCacheInvalidationTest` dan `AuditLogChangeTest` diperluas, uji mass
   assignment.
4. **Pemeriksaan konsistensi otomatis** (tanggal terbalik, lingkup di luar yurisdiksi, dua status aktif bertentangan,
   jalur Buka di kawasan tertutup, bukti hanya berita). Test: `OfficialStatusConsistencyTest`.
5. **Tinjau berkala:** kedaluwarsa menjadi Belum diketahui dan masuk antrean, tidak pernah otomatis Buka; jadwal per
   jenis status menggantikan ambang 90 hari; tugas terjadwal terdaftar di `SchedulerHealthService::TUGAS`. Test:
   `DataFreshnessReviewTest` dan `SchedulerHealthTest`.
6. **Arsip bukti:** unggahan divalidasi, EXIF dibuang, SHA-256, disk privat. Test: `EvidenceArchiveTest`.
7. **Status gunung api DK-43:** tabel per gunung (level I sampai IV, rekomendasi radius, sumber, waktu terbit dan
   verifikasi), atribusi "PVMBG, Badan Geologi, KESDM (MAGMA Indonesia)" dan tautan, tidak pernah dipetakan ke Buka
   atau Tutup, tampil hanya bila ada. Test: `VolcanoStatusTest`.
8. **Foto gunung berlisensi DK-37:** tabel media gunung dengan kolom wajib spec §8.7, unggahan admin, atribusi di pojok
   foto, visual data bila tanpa foto, foto komunitas tidak pernah hero (R-017). Test: `MountainPhotoLicenseTest`.
9. **UI meja kurator** dengan alur tujuh langkah dan tautan "Laporkan kesalahan data" dari layar pendaki ke antrean
   kurator. Test: `CuratorDeskTest` (akses per peran, draf tidak pernah terlihat pendaki).

**Selesai bila:** aturan penutup gelombang terpenuhi; test paritas PostgreSQL untuk setiap migrasi hijau di CI.

### W9 Penutup (spec §13, §16)

**Hasil:** kandidat rilis dengan bukti per butir; push dan rilis menunggu izin pemilik (R-018).

1. Klik-lewat setiap kontrol di 390 px dan 1440 px, tema terang, gelap, dan lapangan, jalur keyboard, gerak
   dikurangi, `forced-colors`; bukti di `docs/audit/`.
2. Delivery Gate antislop dengan bukti per butir; temuan dicatat di `docs/audit/anti-slop/` dengan nomor berlanjut.
3. Audit aksesibilitas WCAG 2.2 AA dan tinjauan keamanan daftar CLAUDE.md §4 (auth bypass, IDOR, eskalasi hak, mass
   assignment, unggahan, batas laju, kebocoran rahasia, RLS bila data Supabase terekspos, akses lokasi privat,
   penghapusan akun).
4. Dokumen: catatan tata kelola PRD §146 (halaman gunung, penilaian kecocokan, indeks trip, amandemen paket lapangan
   bila Q7 disetujui), drift `PRODUCT.md`, `docs/ARCHITECTURE.md` (peta halaman React, kelas halaman baru, paket
   luring, data contoh), `README.md`, `CONTRIBUTING.md`, dan `HANDOVER.md`.
5. Kandidat rilis: suite penuh, `tsc`, build, Pint, test spasial PostgreSQL di CI, status migrasi nyata diverifikasi
   (R-024), ringkasan diff.
6. Uji kegunaan dengan 5 sampai 8 pendaki menurut `docs/PROTOKOL-UJI-KEGUNAAN.md` (Q15), dijalankan pemilik; hasilnya
   tidak dikarang.

**Selesai bila:** Delivery Gate antislop S1 sampai S8 dengan bukti, tanpa cacat kritis tersisa.

## Kepemilikan berkas saat eksekusi paralel

Berkas bersama dipegang koordinator dan tidak disunting subagen tanpa penugasan eksplisit: `resources/css/app.css`,
`tailwind.config.js`, `resources/js/layouts/AppLayout.tsx`, `resources/js/layouts/NavLinks.tsx`,
`resources/js/types/index.d.ts`, `routes/web.php`, `app/Http/Middleware/HandleInertiaRequests.php`, `HANDOVER.md`,
`CLAUDE.md`. Setiap tugas dalam rencana rinci menyebut berkas miliknya.
