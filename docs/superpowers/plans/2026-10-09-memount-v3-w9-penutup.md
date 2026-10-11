# Memount v3 W9 Penutup Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Kandidat rilis Memount v3 dengan bukti per butir. W9 tidak menambah fitur produk dan tidak menambah migrasi.
Ia menutup titipan W1 sampai W8 yang wajib ("Ke W9"): halaman galat 403, 404, 419, 429, 500, dan 503 berbahasa
Indonesia untuk kunjungan Inertia; jam bertitik berzona dan tahun pada tanggal `'j M'`; kosakata mesin di kalimat
alasan dan satu kalimat status resmi di panel "Kenapa?"; pengumuman tunggal pembaca layar; kontras ruas kosong, target
remah 44 px, dan kolom aksi 1440 px; sesi dan batas laju setiap rute tulis, `trustProxies`, dan kedaluwarsa penanda
pemulihan Simpanan; enkripsi riwayat Inertia dan retensi tautan bagikan di balik sakelar yang mati sampai pemilik
memutus kebijakannya; kunci baris PostgreSQL untuk Persiapan pertama dan untuk batas per pendaki; anggaran Jelajah dan
Beranda pada batas 500 kandidat, `mountain_follows` yang dibaca sekali, dan aturan izin penjadwal pengingat yang dibaca
sekali per putaran; build CSS lokal yang sama dengan build produksi. Sesudah itu W9 menyiapkan klik-lewat (pratinjau untuk setiap halaman React,
daftar periksa, test penjaga), Delivery Gate antislop S1 sampai S8 (audit 002), audit aksesibilitas WCAG 2.2 AA dan
keamanan `CLAUDE.md` bagian 4, dokumen (GR-08 dan log keputusan DK-30 sampai DK-58, drift `PRODUCT.md` dan
`docs/ARCHITECTURE.md`, `README.md`, `CONTRIBUTING.md`, `HANDOVER.md`), dokumen kandidat rilis dengan urutan deploy
migrasi per berkas, materi uji kegunaan Q15, dan satu permintaan izin akhir untuk pemilik.

**Architecture:** Kata halaman galat tinggal di satu kelas, `App\Support\HalamanGalat`, yang dibaca Blade muat penuh
(`resources/views/errors/layout.blade.php`) dan halaman React `Galat` untuk kunjungan Inertia; kaitnya satu callback
`respond()` di `bootstrap/app.php` yang memakai `Inertia\ExceptionResponse` tanpa props bersama kerangka. Format jam dan
tanggal berpusat di `App\Support\Timezone` (`display()` dan `tanggalPendek()`). Perbaikan keamanan memakai mekanisme
Laravel yang sudah ada: middleware `throttle` berkunci, `config/trustedproxy.php`, disk lokal tanpa `serve`, satu kunci
sesi pemulihan berumur. Sakelar privasi baru mengikuti pola W3 P8 dan W6 K3: dibangun lengkap, mati secara bawaan.
Bukti yang tidak dapat dibuat mesin (klik di peramban, uji kegunaan, PostgreSQL, Supabase) dipisah tegas dari test,
dengan test penjaga yang memastikan daftar periksa dan laporan audit tidak tertinggal dari kode.

**Tech Stack:** PHP 8.5.10, Laravel 13.31.0, Inertia Laravel 3.3.4, `@inertiajs/react` 3.7.1, React 19.3.0,
TypeScript 5.9.3, Vite 8.3.0, Tailwind CSS 3.4.19, Wayfinder, PHPUnit 12.5.35, Pint 1.32, SQLite dalam memori untuk test,
PostGIS di CI untuk suite Spatial. Tidak ada dependensi baru.

**Spec:** `docs/superpowers/specs/2026-09-25-memount-v3-prototype-design.md` (bagian 2 ukuran berhasil S1 sampai S8,
bagian 3.6 drift dokumen, bagian 13 verifikasi, bagian 15 keputusan Q1 sampai Q22, bagian 16 dokumen yang berubah,
bagian 17.4 DK-51 sampai DK-58), rencana induk `docs/superpowers/plans/2026-09-26-memount-v3-induk.md` (bagian "W9
Penutup" butir 1 sampai 6 dan "Selesai bila", "Tonggak", dan "Kepemilikan berkas saat eksekusi paralel"), `HANDOVER.md`
bagian 0 (titipan "Ke W9" W1 sampai W4), titipan "Ke W9" rencana W6, W7, dan W8, pelacak program
`.superpowers/sdd/2026-09-26-memount-v3-program.md` (permintaan izin akhir butir 1 sampai 29 dan setiap baris "Titipan
W9"), dan aturan eksekusi di Lampiran A. Keadaan awalnya keadaan akhir simulasi rencana W8
(`2026-10-08-memount-v3-w8-data-resmi.md`); W5, W6, W7, dan W8 selesai di repo nyata sebelum Tugas 1 rencana ini
dimulai (Pra-cek).

## Global Constraints

- Versi dikunci: PHP 8.5, Laravel 13.31, Inertia Laravel 3.3.4, React 19.3, `@inertiajs/react` 3.7.1, Tailwind 3.4, Vite 8, TypeScript 5.9. Tidak ada perubahan versi kerangka (R-020).
- Tidak ada dependensi baru, runtime maupun dev, dan tidak ada `npx` apa pun. Pemeriksaan tipe memakai `node_modules/.bin/tsc --noEmit`. Playwright (butir izin akhir 7) dan Vitest (butir izin akhir 25) tidak dipasang; perilaku klien yang membutuhkannya dicatat sebagai keterbatasan, tidak diklaim lulus.
- Tidak ada commit, push, akses Supabase, atau migrasi di basis data mana pun. Setiap langkah "Commit" berbunyi: "Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah." W9 tidak menambah migrasi. `php artisan migrate` dalam bentuk apa pun, `db:seed`, `db:wipe`, `model:prune` di luar PHPUnit, tinker (termasuk `tinker --execute`), dan `memount:demo` tidak dipakai.
- Setiap perintah Artisan di luar PHPUnit, `npm run build`, dan `php -S` diberi awalan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL=` (Git Bash). Di PowerShell: `$env:DB_CONNECTION="sqlite"; $env:DB_DATABASE=":memory:"; $env:DB_URL="(empty)"; <perintah>`, lalu `Remove-Item Env:DB_CONNECTION, Env:DB_DATABASE, Env:DB_URL`. `php artisan test ...` tidak butuh awalan karena `phpunit.xml` memaksa koneksi uji. Jangan membaca atau mencetak `.env`.
- Port `127.0.0.1:8100` dicadangkan untuk harness pratinjau koordinator (Tugas 16). Tugas 1 sampai 15 tidak menyalakan server apa pun. Server `php artisan serve` pemilik di `127.0.0.1:8000` tidak pernah dipakai atau dihentikan. Port 8180 milik penulis rencana.
- Bahasa Indonesia untuk teks antarmuka dan dokumen, sapaan "Anda", tanpa em dash (U+2014). Tidak ada kata "aman", "sehat", "dijamin", "terjamin", atau "pasti" sebagai klaim (R-012, R-014); empat kalimat penyangkalan yang memakai kata itu disebut di `SafetyClaimWordTest`. "§N" hanya untuk ID PRD; dokumen lain dirujuk "bagian N".
- Jam di layar ditulis bertitik dengan zonanya (`07.00 WIB`) lewat `App\Support\Timezone::display()`; tanggal pendek lewat `Timezone::tanggalPendek()`, yang menyebut tahun bila berbeda dari tahun berjalan. Tidak ada `format('H:i')` atau `'j M'` baru di kode tampilan (`IndonesianTimeFormatTest`).
- Hierarki otoritas OFFICIAL > ADMIN VERIFIED > COMMUNITY > AI (R-009), OPEN bukan jaminan keselamatan (R-012), kesiapan adalah dukungan keputusan (R-014), dan UNKNOWN bukan rendah (R-006). Halaman galat tidak pernah menyatakan keadaan jalur; halaman 500 dan 503 mengarahkan pendaki memastikan status langsung ke pengelola.
- Halaman galat tidak menyentuh basis data, sesi, maupun pengguna: kunjungan Inertia menerima hanya props galat, merek, dan tema (`Inertia::flushShared()` di `HalamanGalat::tanggapiInertia()`), dan alamat halaman untuk permintaan non-GET hanya diambil dari Referer satu asal.
- Peran, pemilik, dan kunci tidak pernah diambil dari isian. Rute tulis baru atau yang diubah memakai middleware `throttle` dengan kunci sendiri (`profil`, `sandi`, `hapus-akun`, `onboarding`, `pilih-rekomendasi`, `jejak`), dan pendaftaran serta permintaan tautan atur ulang sandi dibatasi per alamat IP. Konsol admin, moderasi, dan kurator mengikuti ruling W8 (tanpa kunci laju sendiri) dan tercatat di `WriteRouteGuardTest::TANPA_THROTTLE`.
- Sakelar privasi `INERTIA_ENCRYPT_HISTORY` dan `SHARE_LINK_PRUNE_AFTER_DAYS` mati atau kosong secara bawaan. Kebijakan data pribadi milik `docs/KEBIJAKAN-DATA-PRIBADI.md` (`CLAUDE.md` bagian 4); rencana ini tidak menyuntingnya dan tidak menyalakan sakelar itu.
- Token warna hanya di `resources/css/app.css`; tanpa warna mentah, `bg-white`, `text-black`, atau `dark:` di komponen. Kendali paling sedikit 44 px. Halaman tidak menggulir menyamping di 320 px. Status tidak pernah lewat warna saja (WCAG 2.2 AA).
- Nama props bersama (`auth`, `jumlahSimpanan`, `pendakianAktif`, `daftarPengingat`, `demo`, `flash`, dan lainnya di `HandleInertiaRequests`) tidak dipakai ulang sebagai kunci prop halaman (`ReminderSheetTest`). W9 tidak menambah prop bersama.
- Test yang menyandikan keputusan lama diperbarui menurut niat baru dengan alasan tertulis di docblock atau komentarnya; tidak dihapus dan tidak dilonggarkan diam-diam (spec bagian 14).
- Tugas dikerjakan berurutan, satu implementer satu waktu, dan hanya menyunting berkas di daftar Files tugasnya. Berkas bersama (`routes/web.php`, `routes/auth.php`, `config/hiking.php`, `.env.example`, `bootstrap/app.php`, `app/Support/Timezone.php`, `app/Services/SchedulerHealthService.php`, `resources/js/pages/Galat.tsx`, `tailwind.config.js`, `docs/PRD.md`, `docs/ARCHITECTURE.md`) hanya disunting tugas yang menyebutnya (rencana induk, "Kepemilikan berkas saat eksekusi paralel"). `HANDOVER.md` hanya disunting koordinator di Tugas 16; `CLAUDE.md` dan `docs/KEBIJAKAN-DATA-PRIBADI.md` tidak disunting.
- Berkas baru dibuat seperti W2 sampai W8: test dengan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:test --phpunit --no-interaction <Nama>` (test Spatial dan Pratinjau dibuat langsung), kelas PHP dengan `make:class` (awalan DB dan `--no-interaction`), lalu isinya diganti persis dengan kode rencana. Berkas TypeScript, Markdown, dan konfigurasi dibuat langsung. Setelah menyunting PHP jalankan `vendor/bin/pint --format agent <berkas>`; setelah menyunting TypeScript atau rute jalankan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build` lalu `node_modules/.bin/tsc --noEmit`.
- Blok "Ganti ... dengan ..." diterapkan berurutan dengan alat Edit. Teks lamanya unik di berkasnya pada keadaan dasar ditambah tugas-tugas sebelumnya, sudah diperiksa pada simulasi dan putaran ulang, dan yang jangkarnya berasal dari dasar sudah dicocokkan ke repo nyata dengan Grep atau ditandai bergantung W5 sampai W8 (Pra-cek butir 1). Bila teks lama tidak ditemukan persis, berhenti dan laporkan NEEDS_CONTEXT; jangan mengarang padanannya. "Tulis ulang ... utuh" berarti isi berkas diganti seluruhnya dengan blok kodenya, dan hanya boleh bila berkas di repo nyata sama dengan dasar (Pra-cek butir 2).

## Review Focus

- **Halaman galat yang bocor atau menyesatkan.** Detail pengecualian, jejak tumpukan, atau props kerangka yang membaca
  basis data tampil kepada pendaki; alamat Referer dari situs lain menjadi alamat halaman; aksi 429 yang
  memindahkan pendaki dari halamannya; 419 tanpa jalan masuk ulang; pembaca layar diam sesudah aksi ditolak. Dijaga
  Tugas 1 `InertiaErrorPageTest` dan `ErrorPagesTest`, dan Tugas 15 `ErrorPageFocusTest`.
- **Rute tulis tanpa sesi atau tanpa batas laju, dan alamat IP yang salah di balik proxy.** Rute baru dari paket,
  rute konsol, dan rute yang lupa `throttle`; `X-Forwarded-For` yang dipercaya tanpa proxy terdaftar sehingga satu
  penyerang menembus batas per alamat atau semua pengguna berbagi satu kunci. Dijaga Tugas 5 `WriteRouteGuardTest` dan
  `TrustedProxyTest`.
- **Balapan dan data ganda di PostgreSQL.** Dua kunjungan pertama Persiapan yang menulis daftar dua kali; dua
  permintaan yang sama-sama lolos batas simpanan, batas tautan, atau satu pendakian berlangsung. SQLite tidak
  membuktikan satu pun; dijaga Tugas 7 `PreparationFirstVisitLockTest` dan `UserRowLockTest` di PostGIS CI (syarat
  rilis, Tugas 16).
- **Teks yang menjanjikan atau salah zona.** Kata klaim keselamatan, jam berkolon tanpa zona, tanggal tanpa tahun
  untuk trip tahun depan, kosakata mesin ("Elevation gain", "TUTUP") di kalimat alasan, dan label jadwal penjadwal yang
  menyebut jam UTC sebagai WIB. Dijaga Tugas 2, 3, 9, dan 10 (`IndonesianTimeFormatTest`, `MachineVocabularyTest`,
  `SafetyClaimWordTest`, `ScheduleLabelTest`).
- **Bukti yang tertinggal dari kode.** Halaman React baru tanpa pratinjau atau baris daftar periksa, laporan audit
  yang menyebut test yang sudah berganti nama, migrasi yang lupa dicantumkan di urutan deploy, dan log keputusan yang
  kehilangan nomor. Dijaga Tugas 11 `PreviewCoverageTest`, Tugas 12 `AuditCitationTest`, Tugas 13 `DecisionLogTest`
  dan `DocumentDriftTest`, dan Tugas 14 `ReleaseMaterialsTest`.

---

## Bukti simulasi rencana

Kode di rencana ini dijalankan tugas demi tugas di salinan di scratchpad sesi perencanaan (`w9sim`). Salinan itu dibuat
dengan menyalin utuh `w8sim` (keadaan akhir simulasi rencana W8), termasuk `vendor/` dan `node_modules/` (salinan, bukan
tautan), dan konstanta `ASAL` harness pratinjau diarahkan ke port 8180. Sesudah Tugas 4, dua batas laju persiapan dan satu
kunci `config/hiking.php` diselaraskan dengan repo nyata (penyelarasan S1, W9-R1); Tugas 5 dan sesudahnya berjangkar
pada versi itu. Sesudah rantai selesai, tiga titipan yang ditemukan saat pemeriksaan ulang pelacak dan `HANDOVER.md`
ditambahkan ke tugas pemiliknya (kalimat status ganda panel "Kenapa?" ke Tugas 3, aturan izin penjadwal ke Tugas 8, dan
pemindaian view terkompilasi W2-C10 ke Tugas 14), masing-masing diuji merah lalu hijau di `w9sim`, dan berkasnya
disebarkan ke potret tugas itu dan sesudahnya, karena tidak ada tugas sesudahnya yang menyentuh berkas yang sama. Repo
nyata tidak pernah ditulis dan tidak pernah menjadi direktori kerja perintah; ia dibaca dengan Read, Grep, dan Glob saja.
Setiap tugas diuji merah dengan test tugas itu terhadap kode tugas sebelumnya, lalu hijau, lalu suite Unit dan Feature
penuh, build, `tsc --noEmit`, dan suite Pratinjau. Sesudah dirakit, operasi rencana ini diputar ulang dari salinan dasar
yang bersih (`w9putar2`, dibuat dengan cara yang sama, dengan penyelarasan S1 diterapkan sebelum Tugas 1) memakai
pengurai yang hanya membaca blok "Buat", "Tulis ulang", "Ganti", dan "Hapus berkas"; angka di tabel adalah hasil putaran
ulang itu.

| Tugas | Merah (filter tugas, sebelum implementasi) | Hijau | Suite Unit dan Feature | Pratinjau | Pohon sesudah tugas |
|---|---|---|---|---|---|
| Dasar (keadaan akhir W8) | | | 2.257 test, 2.255 lulus, 2 dilewati | 43 lulus | sama dengan potret dasar |
| 1 Halaman galat | 33 test, 20 lulus, 3 gagal, 10 error | 33 test, 33 lulus | 2.270 test, 2.268 lulus, 2 dilewati | 47 lulus | identik dengan simulasi, kecuali dua berkas penyelarasan S1 |
| 2 Jam dan tanggal | 60 test, 50 lulus, 9 gagal, 1 error | 60 test, 60 lulus | 2.275 test, 2.273 lulus, 2 dilewati | 47 lulus | identik dengan simulasi, kecuali dua berkas penyelarasan S1 |
| 3 Kosakata dan panel "Kenapa?" | 97 test, 84 lulus, 9 gagal, 4 dilewati | 129 test, 125 lulus, 4 dilewati | 2.280 test, 2.278 lulus, 2 dilewati | 47 lulus | identik dengan simulasi, kecuali dua berkas penyelarasan S1 |
| 4 Pengumuman, kontras, target | 27 test, 20 lulus, 7 gagal | 27 test, 27 lulus | 2.288 test, 2.286 lulus, 2 dilewati | 47 lulus | identik dengan simulasi, kecuali dua berkas penyelarasan S1 |
| 5 Sesi, batas laju, proxy | 29 test, 18 lulus, 9 gagal, 2 error | 29 test, 29 lulus | 2.304 test, 2.302 lulus, 2 dilewati | 47 lulus | identik dengan simulasi |
| 6 Riwayat Inertia | 3 test, 2 lulus, 1 gagal | 3 test, 3 lulus | 2.307 test, 2.305 lulus, 2 dilewati | 47 lulus | identik dengan simulasi |
| 7 Kunci PostgreSQL | 5 test, 5 dilewati | 92 test, 92 lulus | 2.307 test, 2.305 lulus, 2 dilewati | 47 lulus | identik dengan simulasi |
| 8 Anggaran kandidat dan kueri | 6 test, 3 lulus, 3 gagal | 164 test, 164 lulus | 2.313 test, 2.311 lulus, 2 dilewati | 47 lulus | identik dengan simulasi |
| 9 Teks klaim dan luring | 4 test, 1 lulus, 3 gagal | 32 test, 32 lulus | 2.317 test, 2.315 lulus, 2 dilewati | 47 lulus | identik dengan simulasi |
| 10 Retensi tautan dan label jadwal | 4 test, 1 lulus, 3 gagal | 70 test, 70 lulus | 2.321 test, 2.319 lulus, 2 dilewati | 47 lulus | identik dengan simulasi |
| 11 Klik-lewat | 2 test, 1 gagal, 1 error | 2 test, 2 lulus | 2.323 test, 2.321 lulus, 2 dilewati | 75 lulus | identik dengan simulasi |
| 12 Audit aksesibilitas dan keamanan | 4 test, 2 lulus, 2 error | 4 test, 4 lulus | 2.327 test, 2.325 lulus, 2 dilewati | 75 lulus | identik dengan simulasi |
| 13 Dokumen | 5 test, 1 lulus, 4 gagal | 38 test, 38 lulus | 2.332 test, 2.330 lulus, 2 dilewati | 75 lulus | identik dengan simulasi |
| 14 Kandidat rilis dan CSS | 15 test, 11 lulus, 2 gagal, 2 error | 34 test, 34 lulus | 2.336 test, 2.334 lulus, 2 dilewati | 75 lulus | identik dengan simulasi |
| 15 Fokus halaman galat | 1 test, 1 gagal | 40 test, 40 lulus | 2.337 test, 2.335 lulus, 2 dilewati | 75 lulus | identik dengan simulasi |
| 16 Verifikasi koordinator | tidak ada | tidak ada | 2.337 test, 2.335 lulus, 2 dilewati | 75 lulus | identik dengan simulasi |
| Akhir | | | 2.337 test, 2.335 lulus, 2 dilewati; bawaan 2.419 test, 2.335 lulus, 84 dilewati (ulangan; jalan pertama 1 gagal karena pergantian menit di test W8 `AuditLogChangeTest`); Spatial 82 test, semuanya dilewati (dasar 77) | 75 test lulus | CSS `app` 71.823 B; `tsc` dan build tanpa galat; Pint tanpa berkas berubah |

Tugas 16 dikerjakan koordinator; operasinya (audit 002 dan subbagian W9 `HANDOVER.md`) ikut diputar, dan filter merahnya
tidak ada. Jalan pertama suite bawaan sesudah Tugas 16 (16.03 WIB) gagal satu test dasar W8,
`AuditLogChangeTest::test_superseding_an_official_status_logs_the_end_it_had_before`: test itu memanggil
`now()->addMonth()->startOfMinute()` saat menyiapkan data dan sekali lagi saat memeriksa, tanpa membekukan waktu, sehingga
gagal bila pergantian menit jatuh di antaranya (selisih tepat satu menit di keluarannya). Test itu sendiri lulus saat
diulang, dan suite bawaan diulang utuh 16.06 sampai 16.08 tanpa kegagalan; angka Akhir memakai ulangan itu. Perbaikannya
milik rencana W8 (bekukan waktu di awal test), dicatat di Pra-cek butir 2.

Merah Tugas 7 adalah lima kasus dari dua test Spatial yang dilewati tanpa `SPATIAL_TEST_DSN`; di PostgreSQL
`PreparationFirstVisitLockTest` diharapkan gagal sebelum implementasi (INFERENCE dari kode, belum pernah dijalankan).

**Putaran ulang.** Putaran penuh di `w9putar2`, 8 Oktober 15.26 sampai 16.06 WIB, dengan pengurai yang hanya
membaca blok rencana. Pengurai membaca 232 operasi (38 "Buat", 5 "Tulis ulang ... utuh", 189 blok "Ganti", tanpa
"Hapus berkas"), dan setiap teks lama blok "Ganti" ditemukan tepat sekali saat diterapkan. Pohon sumber sesudah setiap
tugas (tanpa `vendor`, `node_modules`, keluaran build, keluaran Wayfinder, `storage`, dan `public/_pratinjau`) sama berkas
demi berkas dengan potret simulasi tugas itu: untuk Tugas 1 sampai 4 hanya dua berkas penyelarasan S1 yang berbeda
(potret simulasi Tugas 1 sampai 4 diambil sebelum penyelarasan), untuk Tugas 5 sampai 16 tidak ada beda. Pint dalam mode
perbaikan sesudah Tugas 16 tidak mengubah satu berkas pun, dan pohon akhirnya tetap sama dengan potret Tugas 16. Putaran
pertama (`w9putar`) menjalankan perintahnya lewat `bash.exe` WSL tanpa distribusi, jadi tidak satu test pun berjalan;
operasinya tetap diterapkan dan setiap pohon tugas sama dengan potretnya kecuali dua berkas S1 yang ikut gagal diterapkan.
Putaran itu tidak dipakai untuk angka. Sesudah putaran akhir hanya teks di luar blok yang dibaca pengurai yang disunting;
sidik SHA-256 daftar operasi rencana final sama dengan yang diputar (`604838599ab1adb4...`).

**Uji kering terhadap repo nyata.** Hanya membaca, dengan alat Grep (multiline, `\r?\n` di antara baris), 8 Oktober
15.30 sampai 16.10 WIB, saat gelombang akhir W5 sedang ditinjau. Dari 189 blok "Ganti": 160 berjangkar teks yang sudah
ada sebelum W5, dan 153 di antaranya ditemukan tepat sekali; tujuh tidak ditemukan karena gelombang perbaikan akhir W4
dan gelombang akhir W5 (Pra-cek butir 1b), dan masing-masing punya "Varian repo nyata" yang teks lamanya ditemukan tepat
sekali. 20 blok berjangkar teks rencana W5 sampai W8: 12 sudah ditemukan tepat sekali (W5 sudah dieksekusi), 8 menunggu
W6 sampai W8 (daftar per berkas di Pra-cek butir 1). 9 blok berjangkar teks tugas W9 sebelumnya dan dibuktikan putaran
ulang. Kelima berkas yang ditulis ulang utuh cocok jumlah barisnya dengan dasar. Penjaga baru W9 juga dicocokkan ke repo
nyata: tiga baris `'D, j M'` repo nyata yang tidak ada di dasar diberi varian di Tugas 2; "elevation gain" yang hanya ada di
berkas yang ditulis ulang W7 dan rute `trips.hike.position` yang dicabut W6 dicatat di Pra-cek butir 2. Skrip yang membaca
repo nyata tidak dijalankan, karena pagar penulis hanya mengizinkan Read, Grep, dan Glob terhadap repo nyata.

**Pengukuran di peramban** (Chrome lewat MCP chrome-devtools, konteks terisolasi, pratinjau statis `w9sim` di port 8180
sesudah build akhir; 390 px dengan emulasi seluler, sentuh, dan rasio piksel 2; 1440 px rasio 1; 320 px seluler rasio 2):

| Ukuran | Hasil |
|---|---|
| Reflow 320 px | `scrollWidth` sama dengan `clientWidth` (320) dan tepi kanan elemen terjauh 320 px di `galat-404`, `galat-429`, `galat-500`, dan `galat-429-muat-penuh`: 4 dari 4 |
| Kontras teks | Terang, keempat halaman: terendah 5,08:1 (baris "Galat 404", "Galat 429", "Galat 500"), lainnya 6,91:1 sampai 15,99:1. Gelap, `galat-404` dan `galat-429-muat-penuh`: terendah 7,22:1, lainnya 9,33:1 sampai 16,31:1. Tidak ada teks di bawah 4,5:1 |
| Target sentuh 390 px | Tombol utama 246 x 48 dan 194 x 48, "Telusuri jalur" 136 x 49, lockup 139 x 44 (React) dan 97 x 44 (Blade); tautan "Lewati ke konten utama" 1 x 1 sampai menerima fokus (pola sr-only W2) |
| Fokus | Kunjungan React: `document.activeElement` H1 sesudah halaman dipasang; Tab berikutnya ke "Muat ulang halaman" dengan cincin token 2 px (`rgb(47, 107, 67)`, 5,73:1 terhadap kertas, skrip kontras antislop-human) dan outline transparan untuk `forced-colors`. Blade muat penuh: fokus di BODY. Pada muat pertama tanpa interaksi peramban menggambar cincin bawaan di judul (tangkapan `w9-galat-*-390-*.png`), dicatat audit 002 nomor 3 |
| Tangkapan | 21 berkas `scratchpad/rencana/w9-galat-*.png`: empat halaman galat di 390 dan 1440 px, terang dan gelap (16), 320 px terang (4), dan fokus sesudah Tab (1) |

**Cacat yang ditemukan simulasi dan sudah diperbaiki di kode rencana ini** (dicatat supaya peninjau tahu mengapa
bentuknya begini):

- Halaman galat lewat `handleExceptionsUsing()` memicu `afterResolving` berulang; kini lewat `withExceptions()->respond()`
  dengan `Inertia\ExceptionResponse`.
- `WriteRouteGuardTest` menemukan rute `storage/{path}` bertanda tangan bawaan Laravel (disk lokal ber-`serve`) dan dua
  rute Livewire tanpa sesi; yang pertama dicabut, yang kedua dicatat `PAKET` dan Konflik 2.
- Label jadwal penjadwal menyebut jam UTC berakhiran WIB; ditemukan saat menambah label `model:prune` (W9-R16).
- Panel "Kenapa?" menyebut status Belum diketahui dan Dibatasi dua kali (titipan W9 tinjauan akhir W5): baris status
  `whatToWatch()` dibuang (W9-R23).
- Build lokal memuat 204 kelas dari view terkompilasi vendor dan basi (W2-C10): baris `content` dicabut (W9-R24).
- Nama belakang unik Faker habis sebelum 200 jalur: `CandidateCapTest` membuat jalur per 40 dan mengosongkan ingatan
  uniknya di antaranya.
- `AuditCitationTest` menangkap dua rujukan test yang salah nama di laporan audit; diperbaiki ke nama yang ada.
- Daftar periksa klik-lewat semula memuat nama pratinjau yang tidak ada dan melewatkan satu; kini dijaga
  `PreviewCoverageTest`.
- Baris layanan baru `docs/ARCHITECTURE.md` semula disisipkan sesudah baris kosong sehingga tabelnya putus; kini
  bersambung.
- Pemindaian em dash dokumen audit menangkap audit 001; ia dikecualikan dari gerbang dan dicatat sebagai temuan audit 002
  nomor 4.
- Perakit blok semula menghasilkan blok yang diawali atau diakhiri baris kosong dan satu blok pengganti kosong; kini setiap
  blok bertepi baris berisi.

**Temuan di luar cakupan W9 (OPEN ISSUE):**

- Kendali yang mengirim ke server belum pernah diklik di peramban (audit 002 nomor 1): butuh Q1 atau Playwright.
- Test Spatial W9 dan W4 belum pernah berjalan di PostgreSQL (syarat rilis 1).
- CSP `script-src` lebih longgar dari kebutuhannya (Konflik 2).
- Kepala halaman galat Blade tanpa tanda logo (audit 002 nomor 2, gerbang DK-16) dan cincin fokus bawaan di judul halaman
  galat (audit 002 nomor 3).
- `prefers-reduced-motion` dan `forced-colors` hanya dapat dibuktikan klik-lewat koordinator (Tugas 16 Step 3); halaman
  galat tidak memuat gerak.
- Titipan W8 N2 (aturan izin yang dimajukan atau dihapus) milik W8; bila tidak ditangani W8, masuk permintaan izin akhir.
- Test W8 `AuditLogChangeTest::test_superseding_an_official_status_logs_the_end_it_had_before` rawan pergantian menit
  (FAKTA putaran ulang W9); perbaikannya di rencana W8.

## Keadaan awal yang terukur (FAKTA, 8 Oktober 2026)

Diukur di salinan dasar (`w9sim`, keadaan akhir simulasi W8) dan di salinan putaran ulang sebelum Tugas 1, kecuali baris
yang menyebut repo nyata (dibaca dengan Read, Grep, dan Glob).

| Butir | Keadaan | Bukti |
|---|---|---|
| Versi | PHP 8.5.10, Composer 2.10.3, Laravel 13.31.0; `gd`, `exif`, `fileinfo`, `pdo_pgsql` terpasang | `php -v`, `composer -V`, `php artisan --version` (berawalan DB) di salinan |
| Suite dasar | Unit dan Feature 2.257 test (2.255 lulus, 2 dilewati), sama dengan angka akhir rencana W8; Pratinjau 43 lulus; Spatial 77 test, semuanya dilewati tanpa `SPATIAL_TEST_DSN`; `tsc` tanpa galat; Pint lulus | `php artisan test --compact --testsuite=Unit,Feature`, `--testsuite=Pratinjau`, `--testsuite=Spatial` di `w9sim`, 8 Oktober 10.13 sampai 10.15 |
| Halaman galat | View Blade 403, 404, 419, 500, dan 503 berbahasa Indonesia dengan tombol berkelas sendiri; tidak ada view 429, jadi batas laju menjawab halaman bawaan Laravel berbahasa Inggris; kunjungan Inertia yang gagal menampilkan dialog pengembang berisi HTML di iframe; `withExceptions` hanya mengatur `shouldRenderJsonWhen` | `resources/views/errors/`, `bootstrap/app.php`; di repo nyata keenam berkas view itu sama jumlah barisnya |
| Jam dan tanggal | `Timezone::display()` berbawaan `'d M Y H:i'`; jam berkolon di surel, Kabar, rincian jalur, lembar Pengingat, dan label penjadwal; tanggal `'j M'` dan `'D, j M'` tanpa tahun di belasan layanan halaman | `app/Support/Timezone.php`; `IndonesianTimeFormatTest` merah di putaran ulang |
| Kalimat mesin | "Elevation gain", "TUTUP", dan "Route fit" di kalimat alasan dan pesan validasi; angka `%d m` tanpa titik ribuan; panel "Kenapa?" menyebut status resmi Belum diketahui atau Dibatasi dua kali (peringatan mesin lalu baris status penjelasan) | `ReadinessService`, `CompatibilityScorer`, `RecommendationExplanationService::whatToWatch()`, `lang/id/validation.php` |
| Pengumuman dan target | Menyimpan dari kartu Jelajah diumumkan toast Urungkan dan wilayah status halaman; ruas kosong langkah trip `border-subtle`; tautan remah gunung di rincian jalur 94 x 18 px; kolom aksi rincian jalur di 1440 px membungkus | `TombolSimpan.tsx`, `ConsiderationController`, `pages/Trails/Show.tsx`; pengukuran rencana W8 |
| Rute tulis | Profil, sandi, preferensi rekaman, hapus akun, onboarding, pilih rekomendasi, unggah dan hapus jejak tanpa `throttle`; pendaftaran dan permintaan tautan atur ulang sandi tanpa batas per alamat; `storage/{path}` bertanda tangan terdaftar karena disk lokal ber-`serve`; tanpa `trustProxies`; penanda pemulihan Simpanan satu kunci per jalur tanpa umur | `routes/web.php`, `routes/auth.php`, `config/filesystems.php`, `bootstrap/app.php`, `ConsiderationController` |
| Riwayat Inertia | `config/inertia.php` membaca `INERTIA_ENCRYPT_HISTORY` (bawaan false); keluar tidak memanggil `clearHistory()` | `config/inertia.php`, `AuthenticatedSessionController` |
| Konkurensi | `PreparationService::generateFor()` tanpa kunci baris; kunci baris pengguna untuk batas simpanan, tautan bagikan, dan satu pendakian berlangsung tanpa test PostgreSQL; `PreDepartureConfirmLockTest` (W4) ada di repo nyata, tidak di dasar | `app/Services/PreparationService.php`; Glob repo nyata `tests/Spatial/*Lock*.php` |
| Anggaran | Ukuran Jelajah diuji di 40 jalur; `mountain_follows` dibaca dua kali per muat Beranda dan halaman Kabar; penjadwal membaca aturan izin dua kueri untuk setiap trip yang surel hari bukanya jatuh tempo | `FollowedMountainsOnceTest` dan `PermitRulesReadOncePerRunTest` merah di putaran ulang (kueri aturan 4 untuk 2 trip, 10 untuk 5 trip) |
| Teks klaim | "Ini area aman aplikasi" di konfirmasi sandi dan "agar tetap aman" di petunjuk kata sandi Profil, ditambah dua nilai `lang/id.json` | `ConfirmPassword.tsx`, `FormKataSandi.tsx`, `lang/id.json` (sama di repo nyata) |
| Retensi dan penjadwal | Baris `comparison_shares` yang dicabut atau kedaluwarsa tidak pernah dihapus; label jadwal penjadwal menulis jam ekspresi cron berakhiran WIB padahal zona aplikasi UTC ("06:00 dan 18:00 WIB" untuk tugas yang berjalan 13.00 dan 01.00 WIB) | `ComparisonShare`, `SchedulerHealthService::TUGAS`, `config/app.php` `'timezone' => 'UTC'` (sama di repo nyata) |
| Pratinjau | 27 dari 56 halaman React punya pratinjau | `PreviewCoverageTest` merah (29 halaman) |
| Dokumen | PRD dasar memuat GR-01 sampai GR-04, GR-06, dan GR-07, log keputusan sampai DK-29 ditambah DK-36; repo nyata memuat GR-01 sampai GR-06 (GR-07 datang dari W6); `PRODUCT.md` menulis "layak berangkat" dan "perbandingan maksimum lima"; `docs/ARCHITECTURE.md` masih memetakan komponen Livewire, `resources/js/app.js`, dan menu admin Blade | `docs/PRD.md`, `PRODUCT.md`, `docs/ARCHITECTURE.md` |
| Build CSS | `tailwind.config.js` memindai `storage/framework/views`; di pohon akhir simulasi CSS `app` 85.909 B dengan pemindaian itu dan 71.823 B tanpanya; 204 kelas yang hilang hanya ada di 80 view terkompilasi dari `vendor/laravel/framework` dan 8 salinan basi view galat dan luring | build `w9sim` 8 Oktober 15.03, `alat9/w2c10.py` |
| CSP | `script-src 'self' 'unsafe-inline' 'unsafe-eval'` dengan alasan Alpine dan Livewire, padahal `package.json` tidak memuat Alpine dan tidak ada view Blade ber-`x-data` | `app/Http/Middleware/SecurityHeaders.php` baris 51 sampai 54 (sama di repo nyata) |
| Audit antislop | Audit terakhir `audit-001-2026-09-20.md` beserta tindak lanjutnya; berikutnya audit 002 | Glob repo nyata `docs/audit/**` |

## Ruling rencana (W9-R1 sampai W9-R31)

Setiap ruling ditulis dengan format keputusan, alasan, dan akibat bila salah.

| ID | Keputusan | Alasan | Akibat bila salah |
|---|---|---|---|
| W9-R1 | Dasar rencana adalah keadaan akhir simulasi W8 (`w8sim`), diselaraskan dengan repo nyata pada satu hal (penyelarasan S1): batas laju `persiapan-daftar` dan `persiapan-butir` serta kunci `rate_limits.preparation_toggles_per_minute` 120 dari gelombang perbaikan akhir W4, yang ada di repo nyata tetapi tidak di dasar. Enam belas tugas: halaman galat (1), jam dan tanggal (2), kosakata dan panel "Kenapa?" (3), pengumuman, kontras, dan target (4), sesi, batas laju, dan proxy (5), riwayat Inertia (6), kunci PostgreSQL (7), anggaran kandidat dan kueri (8), teks klaim dan halaman luring (9), retensi tautan dan label jadwal (10), klik-lewat (11), audit aksesibilitas dan keamanan (12), dokumen (13), kandidat rilis, build CSS, dan materi uji kegunaan (14), fokus halaman galat (15), verifikasi dan permintaan izin akhir (16) | Rencana induk W9 butir 1 sampai 6 dan setiap titipan "Ke W9"; perbaikan kode didahulukan supaya audit, daftar periksa, dan dokumen menyebut nama akhir | Jangkar yang tertabrak ditemukan Pra-cek; implementer berhenti NEEDS_CONTEXT |
| W9-R2 | Kata setiap halaman galat (judul, penjelasan, tindakan) tinggal di satu kelas `App\Support\HalamanGalat`, dibaca Blade muat penuh dan halaman React `Galat`; 500 dan 503 mengarahkan pendaki memastikan status langsung ke pengelola | Dua permukaan tidak boleh berbeda kata; R-012 | Perubahan kata wajib lewat satu kelas (dijaga `ErrorPagesTest`, `InertiaErrorPageTest`) |
| W9-R3 | Kunjungan Inertia yang gagal (403, 404, 419, 429, 500, 503) dijawab halaman `Galat` lewat `$exceptions->respond()` dan `Inertia\ExceptionResponse`, tanpa kerangka dan tanpa props bersama (`Inertia::flushShared()`); 500 dalam mode debug tetap dialog pengembang; 422 dan pengalihan validasi tidak disentuh; alamat halaman untuk permintaan non-GET hanya dari Referer satu asal | Dialog pengembang Inertia bukan halaman pengguna; props kerangka membaca basis data padahal halaman galat dirender saat sesuatu rusak | Kunjungan Inertia ke halaman galat tidak membawa hitungan Simpanan atau pengingat; itu disengaja |
| W9-R4 | `Timezone::display()` berbawaan `'j M Y H.i'`; `Timezone::tanggalPendek()` menyebut tahun hanya di luar tahun berjalan; setiap pemanggil tampilan disapu; format mesin (ISO 8601, `Y-m-d`, isian jam) tidak berubah | `DESIGN.md` bagian Tipografi; titipan W3 dan W4 | Tanggal di tahun berjalan tetap tanpa tahun; isian `type=time` tetap berkolon (`IndonesianTimeFormatTest::JAM_ISIAN`) |
| W9-R5 | Kalimat mesin memakai "tanjakan" dan "turunan", label enum ("Tutup"), dan `number_format(..., 0, ',', '.')`; nilai enum di props dan basis data tetap | Titipan W3 dan W4-C3 | Baris `recommendation_results` lama tetap menyimpan kalimat lama (m9 W3) |
| W9-R6 | Klien yang menampilkan toast Urungkan mengirim `dengan_toast`; server mengosongkan kabar halaman untuk aksi itu | WCAG 4.1.3: satu aksi, satu pengumuman | Klien tanpa isian itu tetap mendapat kabar halaman, jadi tidak ada aksi yang bisu |
| W9-R7 | Setiap rute tulis pendaki berbatas laju dengan kunci sendiri; pendaftaran dan permintaan tautan atur ulang sandi 6 per menit per alamat; disk lokal tanpa `serve`, jadi rute `storage/{path}` hilang | `CLAUDE.md` bagian 4 (rate limiting); sapuan `WriteRouteGuardTest` | Pendaki sah di jaringan bersama dapat menerima 429 berbahasa Indonesia (Konflik 1 dan 8) |
| W9-R8 | Proxy tepercaya dibaca dari `TRUSTED_PROXIES` lewat `config/trustedproxy.php`, kosong secara bawaan | Titipan W3 T8-M2 | Nilai produksi keputusan deploy (Konflik 1) |
| W9-R9 | Penanda pemulihan Simpanan menjadi satu kunci sesi `simpanan-pemulihan` berisi pemilik dan waktu per jalur, berumur 600 detik; yang kedaluwarsa dibuang | Titipan C1 tinjauan akhir W3 | Urungkan sesudah 10 menit tidak memulihkan catatan |
| W9-R10 | Lebar 1280 px Jelajah dibaca dari `MULAI_XL` di `resources/js/lib/layar.ts` yang dicocokkan dengan tema Tailwind | Titipan W3-F7 (N6) | Mengubah tema tanpa mengubah konstanta gagal di `BreakpointSourceTest` |
| W9-R11 | `encryptHistory` dan `Inertia::clearHistory()` saat keluar dibangun di balik `INERTIA_ENCRYPT_HISTORY`, mati secara bawaan | Butir izin akhir 9; kebijakan data perangkat milik `docs/KEBIJAKAN-DATA-PRIBADI.md` (Q10) | Konflik 3 |
| W9-R12 | `PreparationService::generateFor()` mengunci baris trip di awal transaksinya; test Spatial membuktikan kunci itu dan kunci baris pengguna empat batas per pendaki | Titipan W3 dan W4; SQLite tidak membuktikan kunci baris (R-024) | Dua kunjungan pertama serentak menunggu sesaat; bukti PostgreSQL menunggu CI (syarat rilis 1) |
| W9-R13 | Batas 500 kandidat Jelajah dan Beranda dikunci test pada batas itu; cara memilih kandidat tidak diubah; id gunung yang diikuti diteruskan ke `TrailNewsService` | Titipan W3, W5-5b M5, dan W5-4a T-2 | Konflik 7 |
| W9-R14 | Sapuan kata klaim memindai teks yang dibaca pendaki (string PHP lewat token, Blade dan TS/TSX tanpa komentar, nilai `lang/id.json`); empat kalimat penyangkalan disebut dengan alasannya; halaman luring memakai `x-ui.button` dan skala tipe v3 | R-012, R-014; titipan W2 dan W1-F2 | Kalimat baru yang memakai kata itu untuk menyangkal harus ditambahkan ke daftar penyangkalan dengan alasan |
| W9-R15 | Pemangkasan tautan bagikan dibangun di balik `SHARE_LINK_PRUNE_AFTER_DAYS` kosong; `model:prune` dijadwalkan setiap hari 20.00 UTC (03.00 WIB) dan dipantau halaman kesehatan | Titipan W3 T8-M8; retensi milik `docs/KEBIJAKAN-DATA-PRIBADI.md` | Konflik 4 |
| W9-R16 | Label jadwal penjadwal menyebut jam WIB yang sebenarnya; jadwalnya sendiri tidak diubah | Temuan audit W9; zona aplikasi UTC | Admin membaca jam yang benar di halaman kesehatan |
| W9-R17 | Pratinjau untuk 29 halaman React yang belum punya; daftar periksa klik-lewat satu baris per halaman; `PreviewCoverageTest` menjaga keduanya; akun admin, moderator, dan ahli hanya di basis data memori test | Induk W9 butir 1; P-R2 | Halaman React baru tanpa pratinjau gagal di suite Feature |
| W9-R18 | Audit aksesibilitas dan keamanan memakai tingkat bukti R-023 per butir; RLS dan suite Spatial PERLU KLARIFIKASI; celah aturan umum mass assignment ditutup test sapuan; rujukan `berkas::test` dijaga `AuditCitationTest` | Induk W9 butir 3; prompt W9 bagian 4.3 | Tidak ada butir yang ditulis lulus tanpa test bernama atau bukti peramban |
| W9-R19 | GR-08 berformat §130 mencatat permukaan Jelajah dan Trip yang sudah dibangun, baris status program §128 dan §143 yang basi, dan log DK-30 sampai DK-35 serta DK-37 sampai DK-58; amandemen paket lapangan tetap GR-07 sampai Q7 diputus | Spec v3 bagian 16; GR-04; Konflik W7 nomor 9 dan W8 nomor 8 | Konflik 9 |
| W9-R20 | Dokumen kandidat rilis memuat gerbang lokal yang diisi koordinator, syarat rilis yang menunggu izin, 27 migrasi berurutan per berkas, dan ringkasan per gelombang; W9 tidak menambah migrasi | Induk W9 butir 5; R-024 | Migrasi yang lupa dicantumkan gagal di `ReleaseMaterialsTest` |
| W9-R21 | Judul halaman `Galat` menerima fokus saat dipasang (`tabIndex={-1}`, `preventScroll`) | WCAG 2.4.3 dan 4.1.3; kunjungan Inertia yang gagal mengganti halaman tanpa muat penuh | Cincin fokus sesudah klik tetikus tidak tampil (heuristik `:focus-visible`), dicatat audit 002 nomor 3 |
| W9-R22 | Penjadwal pengingat memuat aturan izin trip yang surel hari bukanya jatuh tempo sekali per putaran lewat `PermitService::requirementsForTrails()` | Titipan W9 dari gelombang akhir W5 (pelacak program); anggaran kueri `CLAUDE.md` bagian 6 | Cabang penyelarasan ulang di repo nyata tetap membaca aturannya sendiri; jarang berjalan |
| W9-R23 | Kalimat status resmi di panel "Kenapa?" hanya dari `RouteFitService::peringatan()`; baris status sendiri di `whatToWatch()` dibuang dan parameter `$status` yang tidak terpakai dilepas dari `build()` | Titipan W9 dari tinjauan akhir W5; W3-3c (satu pembentuk kalimat) | Ajakan "Periksa ke pengelola" kini hanya di `preparation_gap` ("Periksa prakiraan cuaca dan status resmi terbaru menjelang keberangkatan.") |
| W9-R24 | `tailwind.config.js` tidak memindai `storage/framework/views` | Titipan W2-C10; build lokal harus sama dengan build produksi dari checkout bersih | Kelas yang hanya dipakai view vendor selain paginasi tidak dibuat; tidak ada yang dipakai aplikasi (pengukuran W9) |
| W9-R25 | Refaktor `TrailDetailPage` (11 dependensi), dependensi `TripController::show`, pemecahan `Readiness.tsx`, dan pemakaian ulang hasil `compute()` tidak dikerjakan W9 | Perilaku tidak berubah, berkasnya disentuh W5 sampai W8, dan tanpa harness klien (izin 25) regresi tampilan hanya terlihat di peramban; kandidat rilis bukan tempat refaktor besar | Konflik 5 |
| W9-R26 | Saringan Persiapan di URL (C-12) dan peringatan isian belum tersimpan di Rencanakan (E-6) tidak dikerjakan W9 | Keduanya perilaku klien murni yang hanya dapat dibuktikan harness klien (Playwright izin 7, Vitest izin 25) | Konflik 6 |
| W9-R27 | Test pasangan kunci flash (`FlashKeyPairTest`) ditulis sebagai varian repo nyata di Tugas 4 | Kunci `kabar-aksi` dan prop `flash.kabarAksi` (W4-FK) ada di repo nyata, tidak di dasar | Varian tidak diputar ulang; dua test-nya penjaga yang lulus sejak dibuat |
| W9-R28 | `livewire/livewire` dan `livewire/volt` tidak dicabut; rute paketnya tercatat di `WriteRouteGuardTest::PAKET` | Mencabut paket adalah perubahan dependensi yang butuh izin | Konflik 2 |
| W9-R29 | Konsol admin, moderasi, kurator geometri dan pos, dan kontribusi ahli tidak diberi batas laju sendiri; tercatat di `WriteRouteGuardTest::AWALAN_KONSOL` | Ruling W8; peran tepercaya dengan middleware peran atau Policy | Konflik 8 |
| W9-R30 | Beranda tetap menilai 500 kandidat pertama menurut id tanpa kalimat terpotong | Mengubah cara memilih kandidat adalah keputusan produk (R-003, R-021) | Konflik 7 |
| W9-R31 | Penyederhanaan kueri Beranda lain dari W5 (`PendakianAktif::untuk()` dua kali, `effectiveStatusesForTrails()` dua kali, `requirementFor()` dua kueri) tidak dikerjakan | Jumlahnya tetap terhadap data (`PageQueryBudgetTest`), jadi anggaran kueri terpenuhi; hanya `mountain_follows` yang dititipkan eksplisit | Empat sampai lima kueri tetap per muat Beranda |

### Konflik untuk koordinator

1. **`TRUSTED_PROXIES` produksi (W9-R7, W9-R8).** Bawaannya kosong: tidak ada proxy yang dipercaya, jadi
   `$request->ip()` adalah alamat yang tersambung langsung. Di balik proxy platform, alamat itu milik proxy, sehingga
   semua pendaki berbagi satu kunci batas laju per alamat: pendaftaran dan permintaan tautan atur ulang sandi (6 per
   menit, W9-R7) serta masuk (`LoginRequest`, lima percobaan per email dan alamat) dapat menolak orang sah saat beberapa
   aktif bersamaan. Rekomendasi: saat deploy, isi `TRUSTED_PROXIES` dengan alamat atau CIDR proxy platform; nilai `*`
   hanya bila aplikasi tidak dapat dicapai selain lewat proxy itu (INFERENCE untuk Vercel: tepinya menulis ulang
   `X-Forwarded-For`; belum diverifikasi ke dokumentasi karena WebFetch di luar pagar penulis). Akibat bila salah: `*`
   di platform yang dapat dicapai langsung membiarkan penyerang memalsukan alamat dan menembus batas laju; kosong di
   balik proxy membuat batas laju berlaku untuk semua pengguna sekaligus. Syarat rilis 6.
2. **Livewire dan Volt masih terpasang tanpa komponen (W9-R28).** `composer.json` memuat `livewire/livewire` dan
   `livewire/volt`; `VoltServiceProvider` masih terdaftar; rute `livewire/update` dan `livewire/upload-file` terdaftar
   tanpa sesi. CSP `script-src` masih mengizinkan `'unsafe-eval'` dan `'unsafe-inline'` dengan alasan Alpine dan
   Livewire, padahal Alpine tidak ada di `package.json` dan tidak ada view ber-`x-data` (FAKTA, sama di repo nyata).
   Rekomendasi: cabut kedua paket dan `VoltServiceProvider` dalam satu perubahan dependensi yang diizinkan (suite,
   build, dan `route:list` sebelum dan sesudah), lalu kencangkan `script-src` (tanpa `'unsafe-eval'`; skrip tema inline
   dengan nonce Vite) dengan klik-lewat di bawah CSP sebagai bukti. Akibat bila dibiarkan: permukaan paket yang tidak
   dipakai tetap butuh pembaruan keamanan, dan CSP lebih longgar dari yang dibutuhkan; akibat bila dicabut tanpa uji:
   halaman yang diam-diam masih memakai skrip inline atau `eval` terhenti oleh CSP.
3. **`encryptHistory` dan `clearHistory()` (W9-R11, butir izin akhir 9, Q10).** Dibangun lengkap di balik
   `INERTIA_ENCRYPT_HISTORY=false`. Rekomendasi: nyalakan saat rilis dan tambahkan ke
   `docs/KEBIJAKAN-DATA-PRIBADI.md` kalimat bahwa riwayat halaman di peramban dienkripsi dan dibersihkan saat keluar.
   Akibat bila tetap mati: di perangkat bersama, tombol Kembali sesudah keluar dapat menampilkan halaman pengguna
   sebelumnya dari riwayat peramban. Akibat bila menyala: riwayat yang dibuat sebelum kunci berganti tidak dapat
   dipulihkan dan halaman dimuat ulang dari server; halaman `http` biasa tidak menyediakan Web Crypto, jadi enkripsi
   riwayat butuh produksi `https`.
4. **Retensi tautan bagikan (W9-R15).** `docs/KEBIJAKAN-DATA-PRIBADI.md` bagian 3 menyatakan data tanpa dasar retensi
   terdokumentasi diperlakukan sebagai dihapus, tetapi lama simpan baris `comparison_shares` yang dicabut atau
   kedaluwarsa belum diatur. Pemangkas dibangun di balik `SHARE_LINK_PRUNE_AFTER_DAYS` kosong (tidak ada baris yang
   cocok). Rekomendasi: 30 hari sesudah dicabut atau kedaluwarsa, dengan kalimat kebijakan "Tautan bagikan yang
   dicabut atau kedaluwarsa dihapus 30 hari kemudian; selama itu tautan tidak membuka apa pun dan hanya pemiliknya yang
   melihat barisnya." Akibat bila terlalu pendek: pemilik kehilangan riwayat tautan yang baru ia cabut; bila dibiarkan
   kosong: baris yang mengaitkan akun dengan pilihan jalurnya tersimpan tanpa batas, bertentangan dengan bagian 3.
5. **Refaktor yang ditunda (W9-R25).** `TrailDetailPage`, `TripController::show`, `Readiness.tsx`, dan pemakaian
   ulang `compute()`. Rekomendasi: kerjakan sesudah rilis, sesudah izin harness klien (butir izin akhir 25), satu per
   satu dengan suite dan klik-lewat. Akibat bila salah (seharusnya dikerjakan sekarang): utang struktur tetap, tanpa
   efek bagi pendaki.
6. **C-12 dan E-6 yang ditunda (W9-R26).** Rekomendasi: kerjakan sesudah izin Playwright atau Vitest. Akibat: kembali
   dari Persiapan kehilangan saringan yang dipilih, dan isian Rencanakan yang belum tersimpan hilang tanpa peringatan
   saat pendaki pindah halaman.
7. **Beranda menilai 500 kandidat pertama menurut id (W9-R13, W9-R30).** Jelajah menyebut daftar yang terpotong;
   Beranda tidak, dan dengan lebih dari 500 jalur terbit ia hanya menilai jalur tertua. Rekomendasi: biarkan sampai
   katalog terbit mendekati 400 jalur, lalu putuskan urutan kandidat (misalnya gunung yang diikuti dan wilayah lebih
   dulu). Akibat bila salah: rekomendasi Beranda mengabaikan jalur baru tanpa memberi tahu pendaki.
8. **Perubahan batas laju dan permukaan rute (W9-R7, W9-R29).** Skill security-and-hardening menempatkan perubahan
   batas laju di "Ask First". Batas baru: profil, sandi, preferensi rekaman, hapus akun, onboarding, pilih rekomendasi,
   unggah dan hapus jejak (masing-masing kunci sendiri), pendaftaran dan lupa sandi 6 per menit per alamat; rute
   `storage/{path}` dicabut. Konsol tetap tanpa batas sendiri. Rekomendasi: terima. Akibat bila salah: batas yang
   terlalu ketat menolak pendaki sah (lihat Konflik 1); konsol tanpa batas bergantung pada kepercayaan akun peran.
9. **GR-08 dan log keputusan (W9-R19).** Perubahan PRD lewat governance record, tanpa perubahan perilaku produk.
   Rekomendasi: koordinator menerima; pemilik membaca GR-08 bersama permintaan izin akhir.
10. **Temuan audit 002 yang tidak ditutup W9.** Nomor 2 (lockup Blade tanpa tanda logo) menunggu aset logo gerbang
    DK-16; nomor 4 (em dash di dokumen bukan antarmuka: `AGENTS.md`, `CLAUDE.md`, `HANDOVER.md`, `PRODUCT.md`,
    `docs/AUDIT-PROTOCOL.md`, audit 001) menyentuh berkas milik pemilik. Rekomendasi: pemilik memutus keduanya;
    `CLAUDE.md` tidak disunting agen.
11. **Teks tugas uji kegunaan (Tugas 14).** T1 dan T5 di halaman Studi Kegunaan dan di
    `docs/PROTOKOL-UJI-KEGUNAAN.md` diselaraskan ke permukaan v3 ("Beranda", "Periksa apa yang masih kurang sebelum
    berangkat") dan DK-14. Rekomendasi: terima; pemilik yang menjalankan Q15 memeriksa materinya. Akibat bila salah:
    peserta mencari nama layar yang sudah tidak ada.

### Pra-cek untuk koordinator

Rencana ditulis di atas keadaan akhir simulasi W8. Periksa ulang di repo nyata sesudah W8 selesai, sebelum Tugas 1.
Penulis rencana membaca repo nyata hanya dengan Read, Grep, dan Glob; repo nyata tidak pernah ditulis dan tidak pernah
menjadi direktori kerja perintah.

| Asumsi | Tempat rencana bergantung | Keadaan |
|---|---|---|
| 1. Jangkar setiap blok "Ganti" ada tepat sekali | Semua tugas | Sesuai di dasar dan pada putaran ulang (Bukti simulasi). Di repo nyata (Grep multiline yang mengizinkan `\r?\n`, 8 Oktober 15.30 sampai 16.10 WIB, saat gelombang akhir W5 sedang ditinjau): dari 160 blok yang teks lamanya sudah ada sebelum W5, 153 ditemukan tepat sekali dan 7 tidak (butir 1b); dari 20 blok yang teks lamanya berasal dari rencana W5 sampai W8, 12 sudah ditemukan tepat sekali (W5 sudah dieksekusi) dan 8 menunggu W6 sampai W8: `app/Services/Trips/PaketLapangan.php` (1, Tugas 2, W6), `resources/js/components/admin/FormulirJalur.tsx` (1, Tugas 3, petunjuk turunan W8 Tugas 13), `app/Http/Controllers/Auth/AuthenticatedSessionController.php` (1, Tugas 6, W6-R10), `app/Services/Trips/TripReminderScheduler.php` (1, Tugas 8 blok 9, punya varian repo nyata), `docs/ARCHITECTURE.md` (1, Tugas 13, baris layanan W7), dan `docs/PRD.md` (3, Tugas 13: baris 27 "governance record berikutnya GR-08", akhir GR-07, dan baris DK-36, semuanya W6). Sembilan blok lain berjangkar teks tugas W9 sebelumnya dan dibuktikan putaran ulang. Di berkas yang disentuh rencana W5 sampai W8, 69 blok berjangkar dasar ditemukan sekarang tetapi wajib dicocokkan ulang sesudah W8: `.env.example` (1, T5); `CONTRIBUTING.md` (2, T13); `app/Http/Controllers/Trails/ConsiderationController.php` (5, T4, T5); `app/Models/Trail.php` (2, T3); `app/Services/Admin/TrailDataHealth.php` (1, T3); `app/Services/Community/KabarJalur.php` (2, T2, T8); `app/Services/PreparationService.php` (3, T2, T7); `app/Services/RouteFit/CompatibilityScorer.php` (3, T3); `app/Services/RouteFitService.php` (1, T3); `app/Services/Trails/TrailDetailPage.php` (3, T2); `app/Services/Trips/DaftarPengingat.php` (1, T2); `app/Services/Trips/HalamanKesiapan.php` (6, T2); `app/Services/Trips/HalamanPersiapan.php` (3, T2); `app/Services/Trips/TripReminderScheduler.php` (4, T2, T8); `bootstrap/app.php` (3, T1); `config/filesystems.php` (1, T5); `config/hiking.php` (1, T10); `docs/ARCHITECTURE.md` (6, T13); `docs/PRD.md` (5, T13); `resources/js/pages/Trails/Show.tsx` (4, T4); `resources/js/pages/Trips/Preparation.tsx` (1, T4); `resources/js/pages/Trips/Readiness.tsx` (1, T4); `resources/views/offline.blade.php` (2, T9); `resources/views/public/trail.blade.php` (2, T3); `routes/web.php` (3, T5); `tests/Feature/EmptyStateCoverageTest.php` (1, T1); `tests/Feature/IndonesianInterfaceTest.php` (1, T3); `tests/Feature/PageHeaderContractTest.php` (1, T1). Pola Grep tiap blok ada di `scratchpad/rencana/sim9/pola-repo-pra-w5.json` dan `pola-repo.json` |
| 1a. Berkas yang ditulis ulang utuh belum berubah sejak dasar | Tugas 1 (`resources/views/errors/403`, `404`, `419`, `500`, `503`) | Jumlah baris repo nyata sama dengan dasar (12, 13, 13, 13, 13; `layout.blade.php` 32 sama 32), tidak ada `429.blade.php`, dan tidak satu pun disentuh rencana W5 sampai W8 (Grep, 8 Oktober). Bila berbeda sesudah W8, gabungkan manual; jangan menimpa |
| 1b. Gelombang perbaikan akhir W4 dan gelombang akhir W5 di repo nyata | Tugas 2, 8, 13, 16 | Tujuh blok yang tidak ditemukan dan tiga baris repo nyata yang ditangkap penjaga baru: Tugas 2 (dua blok `HalamanKesiapan.php` beda `$kritisMenunggu` dan `$terbit`; blok jendela izin `HalamanPersiapan.php` dan fungsi `hari()` `RencanaTrip.php` tidak ada karena tanggal jendela izin kini ditulis `PermitService`; `RingkasanDasbor.php` baris 265 dan 319 serta `TripSteps.php` baris 220 menulis `'D, j M'`), Tugas 8 (docblock `prosesTrip()` "bila salah satu pengiriman gagal", dan `prosesIzin()` gelombang akhir W5), Tugas 13 (baris Trip, Persiapan, dan Readiness tabel komponen ARCHITECTURE sudah [React]), Tugas 16 (judul sesudah blok tempel `HANDOVER.md` adalah subbagian gelombang terakhir). Setiap tugas itu memuat "Varian repo nyata" yang teks lamanya ditemukan tepat sekali dengan Grep |
| 2. W6 sampai W8 selesai di repo nyata | Semua tugas; dasar memuat versi simulasi W5 sampai W8 | Sesudah W8, cari ulang dengan Grep teks yang ditangkap penjaga W9 tetapi tidak ada di simulasi. Keadaan 8 Oktober: "elevation gain" di `ProfileSetupController.php` baris 131 dan 135, `Community/HikeSummary.tsx` baris 79, `Community/HikerProgress.tsx` baris 85, `Onboarding/ProfileSetup.tsx` baris 253 dan 297, dan `community/GrafikTanjakan.tsx` baris 34 (semuanya berkas yang ditulis ulang rencana W7; bila masih ada sesudah W7, Tugas 3 mengganti istilahnya dengan "tanjakan" dengan pola yang sama); rute `trips.hike.position` tanpa `throttle` (dicabut rencana W6; bila masih ada sesudah W6, Tugas 5 berhenti NEEDS_CONTEXT, karena batas laju pengiriman posisi adalah keputusan W6). Kata klaim keselamatan dan status berhuruf kapital di repo nyata sama dengan dasar (yang berbeda hanya di komentar, yang tidak dipindai). Test W8 `AuditLogChangeTest::test_superseding_an_official_status_logs_the_end_it_had_before` memanggil `now()` dua kali tanpa membekukan waktu dan gagal bila pergantian menit jatuh di antaranya (FAKTA putaran ulang W9, 16.03 WIB); bila W8 belum membekukannya, koordinator menambahkan `$this->freezeTime();` di awal test itu sebelum Tugas 1, dan Tugas 16 mengulang suite yang gagal karena itu sambil mencatatnya |
| 3. Kunci flash W4-FK, kunci baris W4, dan penyelarasan S1 | Tugas 4 (varian `FlashKeyPairTest`), Tugas 7, W9-R1 | Repo nyata: `HandleInertiaRequests.php` baris 113 `'kabarAksi' => fn () => $request->session()->get('kabar-aksi'),`; hanya `pages/Trips/Preparation.tsx` (baris 525) yang membaca `flash.kabarAksi`; `tests/Spatial/PreDepartureConfirmLockTest.php` ada; `routes/web.php` baris 166 dan 167 memuat `persiapan-daftar` dan `persiapan-butir`; `config/hiking.php` baris 179 `preparation_toggles_per_minute` 120 (Grep, 8 Oktober) |
| 4. Rujukan test di laporan audit | Tugas 12 dan 15 (`AuditCitationTest`) | Nama test yang dirujuk diambil dari dasar, yang memuat test simulasi W5 sampai W8. Bila eksekusi W5 sampai W8 mengganti nama test, `AuditCitationTest` menunjuk rujukannya; perbaiki ke nama yang ada, jangan menghapus barisnya |
| 5. Migrasi sesudah `2026_09_21_000100` | Tugas 14 (`ReleaseMaterialsTest`) | Tabel urutan deploy memuat 27 migrasi dasar. Migrasi tambahan dari perbaikan W5 sampai W8 ditambahkan di posisi urut nama berkas (Tugas 14 Step 4) |
| 6. Butir izin akhir dan syarat deploy di pelacak | Tugas 16, Lampiran B | Pelacak 8 Oktober memuat butir 1 sampai 5 dan (6) sampai (29), syarat deploy W3 dan W4 (termasuk W4-FI), titipan W8 N2 (baris 89), dan dua titipan W9 tambahan (baris 87 dan 88) yang kini Tugas 3 dan 8. Butir yang bertambah sesudah 8 Oktober masuk Lampiran B tanpa diubah |
| 7. Port dan harness | Tugas 11, 15, 16 | `self::ASAL` di `tests/Pratinjau/PratinjauTestCase.php` dipakai apa adanya (repo nyata 8100); salinan simulasi penulis memakai 8180 |
| 8. Versi kerangka | Seluruh rencana | Periksa `php -v`, `composer -V`, `php artisan --version` (berawalan DB), dan `composer show laravel/framework` sebelum Tugas 1 (R-020) |
| 9. Titipan "Ke W9" | Seluruh rencana | Peta di bawah. Tidak ada butir yang sudah ditangani rencana W5 sampai W8 |

Peta titipan "Ke W9" (`HANDOVER.md` bagian 0 subbagian W1 sampai W4, pelacak program, rencana W5 sampai W8, prompt
penulis W9):

| Titipan | Asal | Tugas | Ruling | Test atau catatan |
|---|---|---|---|---|
| Halaman galat 403, 404, 419, 429, 500 untuk kunjungan Inertia | W4-6j | 1, 15 | W9-R2, R3, R21 | `InertiaErrorPageTest`, `ErrorPagesTest`, `ErrorPageFocusTest`, `HalamanGalatPratinjauTest` |
| Tombol dan judul Blade halaman galat dan luring | W1-F2 | 1, 9 | W9-R2, R14 | `ErrorPagesTest`, `BladeFallbackSurfaceTest` |
| Format jam "19:33" lawan "15.06"; sapuan format jam | W3, W4 | 2 | W9-R4 | `IndonesianTimeFormatTest` |
| Tahun pada `'j M'` dan `'D, j M'` | W3, W4 | 2 | W9-R4 | `IndonesianTimeFormatTest::test_a_new_year_trip_names_the_year_it_waits_for` |
| Istilah Inggris dan angka tanpa titik ribuan; kosakata mesin di alasan Cek | W3, W4-C3 | 3 | W9-R5 | `MachineVocabularyTest` |
| Dua kalimat status resmi UNKNOWN di panel "Kenapa?" | Pelacak baris 87 (tinjauan akhir W5) | 3 | W9-R23 | `TrailCardTest::test_the_why_panel_names_an_official_status_once` |
| Pengumuman tunggal pembaca layar | W3 | 4 | W9-R6 | `ScreenReaderAndTargetTest`, `SavedUndoTest` |
| Kontras ruas kosong | W4-6h OOS7 | 4 | | `ScreenReaderAndTargetTest::test_empty_progress_segments_are_outlined_with_the_control_border` |
| Aksi kolom kanan rincian 1440; tautan remah 44 px | W3; W8 titik lanjut | 4 | | `ScreenReaderAndTargetTest` |
| Test pasangan kunci flash | W4 | 4 (varian) | W9-R27 | `FlashKeyPairTest` |
| `trustProxies` | W3 T8-M2 | 5 | W9-R8 | `TrustedProxyTest` |
| Kedaluwarsa penanda pemulihan sesi | W3 C1 | 5 | W9-R9 | `SavedRestoreMarkerTest` |
| Satu sumber lebar layar klien | W3-F7 (N6) | 5 | W9-R10 | `BreakpointSourceTest` |
| `encryptHistory` dan `clearHistory()` | W2, W3, W6 K5, izin akhir 9 | 6 | W9-R11 | `HistoryPrivacyTest`; Konflik 3 |
| Uji konkurensi `lockForUpdate` di PostgreSQL; balapan dua GET pertama Persiapan | W3, W4 | 7 | W9-R12 | `PreparationFirstVisitLockTest`, `UserRowLockTest` (Spatial); `PreDepartureConfirmLockTest` sudah ada |
| Anggaran Jelajah pada batas 500; biaya kecocokan 500 kandidat Beranda | W3, W5-5b M5 | 8 | W9-R13 | `CandidateCapTest`; Konflik 7 |
| `mountain_follows` dua kali | W5-4a T-2 | 8 | W9-R13 | `FollowedMountainsOnceTest` |
| Aturan izin per baris IZIN di penjadwal | Pelacak baris 88 (gelombang akhir W5) | 8 | W9-R22 | `PermitRulesReadOncePerRunTest` |
| Penyederhanaan kueri Beranda lainnya | Rencana W5 (OPEN ISSUE) | | W9-R31 | `PageQueryBudgetTest` (jumlah tetap) |
| Salinan "Ini area aman aplikasi" | W2 | 9 | W9-R14 | `SafetyClaimWordTest` |
| Retensi baris tautan bagikan | W3 T8-M8 | 10 | W9-R15 | `ShareLinkRetentionTest`; Konflik 4 |
| Pemeriksaan `forced-colors` | W1 | 11, 16 | W9-R17 | Daftar periksa klik-lewat; klik-lewat koordinator |
| Ukuran CSS yang ikut view terkompilasi | W2-C10 | 14 | W9-R24 | `InertiaLayoutTest::test_tailwind_does_not_scan_compiled_views` |
| Refaktor `TrailDetailPage`; pemecahan `Readiness.tsx` dan `compute()`; dependensi `TripController` | W3, W4 | | W9-R25 | Konflik 5 |
| Saringan Persiapan di URL; peringatan isian Rencanakan | W4 (C-12, E-6) | | W9-R26 | Konflik 6 |
| Redesain `/kabar` | W5-C9 | | | Sudah dikerjakan rencana W8 Tugas 16 |
| "Sumber lingkungan"; entitas kawasan bila Q12 diputus | W8 titik lanjut | | | Belum ada data yang dapat dikutip (R-007) dan Q12 terbuka; masuk Lampiran B |
| Titipan W8 N2 (aturan izin yang dimajukan atau dihapus sesudah baris IZIN dijadwalkan) | Pelacak baris 89 | | | Milik W8; bila W8 tidak menanganinya, masuk Lampiran B sebagai OPEN ISSUE |

## Perbandingan skill (DK-57)

Urutan wewenang sama dengan W4 sampai W8: arahan pemilik dan aturan kanonis, lalu `DESIGN.md` (arah, dial ENERGY 2 /
RHYTHM 2 / MOTION 2 untuk layar aplikasi) dan prototype, lalu antislop, lalu saran skill. Gabungan skill UI DK-57
dipakai untuk satu permukaan baru W9, halaman galat (Blade muat penuh dan React `Galat`), dan untuk halaman luring yang
diselaraskan. Launcher impeccable tidak dijalankan: ia membutuhkan direktori kerja repo nyata dan dapat mengunduh biner,
keduanya di luar pagar penulis; kritik dikerjakan dengan membaca `PRODUCT.md`, `DESIGN.md`, `reference/critique.md`, dan
`reference/operate.md`, lalu memeriksa tangkapan di peramban (mode degradasi, dicatat jujur). Skrip ui-ux-pro-max
dijalankan dari salinan skill di dalam salinan simulasi (`.agents/skills/ui-ux-pro-max`, direktori kerja `w9sim`;
keluaran di `scratchpad/rencana/sim9/uiux-w9.txt`).

**Pembacaan desain (design-taste-frontend 0.B):** halaman galat adalah layar Operate untuk pendaki yang sedang berbuat
sesuatu lalu ditolak, sering di ponsel dan di jaringan buruk; bahasanya sistem v3 yang sudah ada (kertas, hutan,
Plus Jakarta Sans), tanpa gerak dan tanpa ilustrasi. Halaman menjawab tiga hal secara berurutan: apa yang terjadi, apa
artinya bagi pendaki, dan satu atau dua langkah yang dapat diambil sekarang.

| Skill | Diambil | Ditolak dan alasannya |
|---|---|---|
| antislop (inti) | R-26: setiap tindakan halaman galat dapat dibuka dengan GET (kembali, muat ulang, masuk lagi, Beranda), dibuktikan `ErrorPagesTest` dan `InertiaErrorPageTest`. R-27: keadaan galat menyebut sebab dan jalan keluar, tidak hanya kode. R-02: tanpa em dash (pemindaian Tugas 16). R-25 dan R-32: kontras dan fokus diukur di peramban (Bukti simulasi). Delivery Gate S1 sampai S8 menjadi audit 002 | Tidak ada |
| antislop-human | Fokus pindah ke judul sesudah kunjungan yang ditolak (Tugas 15); status tidak lewat warna saja; reflow 320 px diukur, bukan diklaim | Toast untuk galat: galat halaman adalah pengganti halaman, bukan kabar sementara |
| ui-ux-pro-max | "error page recovery message retry": langkah berikutnya yang jelas (coba lagi dan tautan) dan galat yang diumumkan; "focus management after page change screen reader": pindahkan fokus ke judul atau wadah ber-`tabindex="-1"` sesudah kiriman gagal, sumber W9-R21; "offline state messaging retry": tidak ada kecocokan langsung, tetapi aturan Deep Linking (keadaan tercermin di URL) dicatat untuk C-12 | "rate limit too many requests feedback": tidak ada kecocokan di basis data skill, jadi kalimat 429 mengikuti `DESIGN.md` dan R-014 saja. `role="alert"` pada judul: fokus yang pindah sudah membuat judul dibacakan, dan wilayah alert kedua akan membacakannya dua kali |
| design-taste-frontend | Skill ini menyatakan dirinya bukan untuk produk multi-langkah, jadi hanya bagian umumnya: pembacaan desain satu baris, keadaan galat yang jelas dan kontekstual (4.5), satu tindakan utama (CTA tunggal), tanpa titik tengah dan em dash (9.F, 9.G) | Dial bawaan 8/6/4, hero, gambar, dan pustaka gerak: halaman galat Operate memakai dial `DESIGN.md` |
| impeccable (Operate; `reference/critique.md`, mode degradasi) | Kritik halaman galat terhadap `DESIGN.md`: kepala Blade dan React berbeda rupa (audit 002 nomor 2, menunggu aset logo DK-16); fokus hilang ke `body` sesudah kunjungan Inertia yang gagal (Tugas 15); cincin fokus bawaan peramban di judul (audit 002 nomor 3, dilihat koordinator); kosakata komponen yang ada dipakai ulang (`Button`, `x-ui.button`, `Lockup`, skala `text-judul`, `text-meta`) | Launcher, detektor hook, dan `polish`: di luar pagar penulis; `DESIGN.md` tidak ditulis ulang |
| security-and-hardening | Model ancaman untuk halaman galat dan rute tulis: tanpa jejak tumpukan atau props kerangka di halaman galat (kebocoran), Referer hanya satu asal (pengalihan terbuka), batas laju setiap rute tulis dan proxy tepercaya (penolakan layanan dan pemalsuan alamat), kunci baris PostgreSQL (balapan), retensi dan enkripsi riwayat di balik sakelar (privasi), CSP yang lebih longgar dari kebutuhannya (Konflik 2) | "Ask First" untuk perubahan batas laju dan penghapusan paket tidak dapat ditanyakan sebelum permintaan izin akhir (arahan pemilik); dibawa sebagai Konflik 2 dan 8 |
| laravel-best-practices | Penanganan galat lewat `withExceptions()->respond()` (bukan `handleExceptionsUsing`, yang berputar lewat `afterResolving`), middleware `throttle` berkunci, `config/trustedproxy.php` lewat `trustProxies()`, `MassPrunable` dan `model:prune` terjadwal, `lockForUpdate()` di dalam transaksi, pemuatan batch aturan izin | Antarmuka repository untuk aturan izin: satu implementasi, lapisan dangkal |
| testing-best-practices | Test perilaku lewat HTTP untuk setiap halaman galat dan rute tulis; test penjaga sumber hanya bila perilakunya tidak dapat dijangkau PHPUnit (aturan konten Tailwind, kata klaim, format tanggal), mengikuti konvensi proyek; waktu dibekukan bila tanggal menentukan hasil; hitungan kueri dibandingkan antara dua ukuran data, bukan angka mutlak | Playwright dan Vitest: dependensi baru (butir izin 7 dan 25) |
| superpowers:writing-plans | Kepala rencana, Global Constraints, Review Focus lima mode gagal, blok Files dan Interfaces per tugas, langkah test lebih dulu, tanpa placeholder, tinjauan diri | Langkah commit diganti "Lewati" (keputusan pemilik) |

**Inventaris skill untuk gelombang ini (`CLAUDE.md` bagian 7):**

- INTI (CORE): superpowers:writing-plans, testing-best-practices, laravel-best-practices, security-and-hardening,
  antislop, antislop-human, ui-ux-pro-max, design-taste-frontend (terbatas), impeccable (mode degradasi).
- PENDUKUNG (SUPPORTING): antislop-code (komentar dan nama di kode baru), antislop-copywriting (kalimat halaman galat
  dan dokumen), antislop-layoutmobile (reflow 320 px dan target 44 px), infer-conventions (pola test dan kelas halaman
  yang dipakai ulang).
- LINTAS (CROSS-CUTTING): superpowers:verification-before-completion dan Delivery Gate antislop; chrome-devtools MCP
  untuk pengukuran dan tangkapan.
- N/A:
  - tailwindcss-development: tidak ada token, varian, atau utilitas baru; satu baris `content` dicabut (Tugas 14);
  - livewire-development dan volt-development: tidak ada komponen Livewire; pencabutan paketnya Konflik 2;
  - antislop-ui, design, design-system, ui-styling, brand, banner-design, slides: tidak ada permukaan visual baru selain
    halaman galat, dan aset logo menunggu gerbang DK-16;
  - deploying-to-cloud dan skill deploy lain: tanpa rilis;
  - ngodingpakeai dan skill alur commit: tanpa commit (keputusan pemilik);
  - browser-use: pengukuran memakai chrome-devtools MCP yang sudah dipakai W1 sampai W8.

## File Structure

**Dibuat:**

- Tugas 1: `app/Support/HalamanGalat.php`, `resources/js/pages/Galat.tsx`, `resources/views/errors/429.blade.php`, `tests/Feature/InertiaErrorPageTest.php`, `tests/Pratinjau/HalamanGalatPratinjauTest.php`
- Tugas 2: `tests/Feature/IndonesianTimeFormatTest.php`
- Tugas 3: `tests/Feature/MachineVocabularyTest.php`
- Tugas 4: `tests/Feature/ScreenReaderAndTargetTest.php`
- Tugas 5: `config/trustedproxy.php`, `resources/js/lib/layar.ts`, `tests/Feature/BreakpointSourceTest.php`, `tests/Feature/SavedRestoreMarkerTest.php`, `tests/Feature/TrustedProxyTest.php`, `tests/Feature/WriteRouteGuardTest.php`
- Tugas 6: `tests/Feature/HistoryPrivacyTest.php`
- Tugas 7: `tests/Spatial/PreparationFirstVisitLockTest.php`, `tests/Spatial/UserRowLockTest.php`
- Tugas 8: `tests/Feature/CandidateCapTest.php`, `tests/Feature/FollowedMountainsOnceTest.php`, `tests/Feature/PermitRulesReadOncePerRunTest.php`
- Tugas 9: `tests/Feature/BladeFallbackSurfaceTest.php`, `tests/Feature/SafetyClaimWordTest.php`
- Tugas 10: `tests/Feature/ScheduleLabelTest.php`, `tests/Feature/ShareLinkRetentionTest.php`
- Tugas 11: `docs/audit/klik-lewat/2026-10-09-daftar-periksa-w9.md`, `tests/Feature/PreviewCoverageTest.php`, `tests/Pratinjau/HalamanPenutupPratinjauTest.php`
- Tugas 12: `docs/audit/aksesibilitas-w9.md`, `docs/audit/keamanan-w9.md`, `tests/Feature/AuditCitationTest.php`, `tests/Feature/MassAssignmentSweepTest.php`
- Tugas 13: `tests/Feature/DecisionLogTest.php`, `tests/Feature/DocumentDriftTest.php`
- Tugas 14: `docs/audit/kandidat-rilis-w9.md`, `docs/riset/2026-10-uji-kegunaan/materi-sesi-w9.md`, `tests/Feature/ReleaseMaterialsTest.php`
- Tugas 15: `tests/Feature/ErrorPageFocusTest.php`
- Tugas 16: `docs/audit/anti-slop/audit-002-2026-10-09.md`

**Ditulis ulang utuh:**

- Tugas 1: `resources/views/errors/403.blade.php`, `resources/views/errors/404.blade.php`, `resources/views/errors/419.blade.php`, `resources/views/errors/500.blade.php`, `resources/views/errors/503.blade.php`

**Dihapus:** tidak ada.

**Diubah:**

- Tugas 1: `bootstrap/app.php`, `resources/views/errors/layout.blade.php`, `tests/Feature/EmptyStateCoverageTest.php`, `tests/Feature/ErrorPagesTest.php`, `tests/Feature/PageHeaderContractTest.php`
- Tugas 2: `app/Notifications/PengingatIzinDibuka.php`, `app/Notifications/TripReminderNotification.php`, `app/Services/Community/KabarJalur.php`, `app/Services/Dashboard/TenggatTrip.php`, `app/Services/PermitService.php`, `app/Services/PreparationService.php`, `app/Services/SchedulerHealthService.php`, `app/Services/Trails/TrailDetailPage.php`, `app/Services/Trips/DaftarPengingat.php`, `app/Services/Trips/HalamanKesiapan.php`, `app/Services/Trips/HalamanPersiapan.php`, `app/Services/Trips/PaketLapangan.php`, `app/Services/Trips/RencanaTrip.php`, `app/Services/Trips/TenggatIzin.php`, `app/Services/Trips/TripReminderScheduler.php`, `app/Support/Timezone.php`, `tests/Feature/PermitWindowReminderTest.php`, `tests/Feature/ReminderSheetTest.php`, `tests/Feature/TripReminderTest.php`, `tests/Unit/WeatherTimezoneTest.php`
- Tugas 3: `app/Enums/CompatibilityFactor.php`, `app/Http/Controllers/Admin/TrailGeometryController.php`, `app/Models/Trail.php`, `app/Services/Admin/TrailDataHealth.php`, `app/Services/ReadinessService.php`, `app/Services/RecommendationExplanationService.php`, `app/Services/RouteFit/CompatibilityScorer.php`, `app/Services/RouteFitService.php`, `lang/id/recommendation.php`, `lang/id/validation.php`, `resources/js/components/admin/FormulirJalur.tsx`, `resources/js/components/community/FormKontribusi.tsx`, `resources/js/components/discovery/KartuHasil.tsx`, `resources/js/pages/Admin/TrailGeometry.tsx`, `resources/js/pages/Goals/Create.tsx`, `resources/js/pages/Recommendations/Show.tsx`, `resources/views/public/trail.blade.php`, `tests/Feature/IndonesianInterfaceTest.php`, `tests/Feature/ReadinessDashboardTest.php`, `tests/Feature/RefusalMessagesTest.php`, `tests/Feature/TrailCardTest.php`, `tests/Feature/TrailGeometryImportTest.php`
- Tugas 4: `app/Http/Controllers/Trails/ConsiderationController.php`, `app/Http/Requests/Trails/StoreSavedTrailRequest.php`, `resources/js/components/beranda/KartuPerjalanan.tsx`, `resources/js/components/trails/TombolSimpan.tsx`, `resources/js/pages/Trails/Show.tsx`, `resources/js/pages/Trips/Preparation.tsx`, `resources/js/pages/Trips/Readiness.tsx`, `tests/Feature/SavedUndoTest.php`
- Tugas 5: `.env.example`, `app/Http/Controllers/Trails/ConsiderationController.php`, `config/filesystems.php`, `resources/js/pages/Trails/Index.tsx`, `routes/auth.php`, `routes/web.php`, `tests/Feature/TrailIndexMapTest.php`
- Tugas 6: `.env.example`, `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- Tugas 7: `app/Services/PreparationService.php`
- Tugas 8: `app/Services/Community/KabarJalur.php`, `app/Services/TrailNewsService.php`, `app/Services/Trips/TripReminderScheduler.php`
- Tugas 9: `lang/id.json`, `resources/js/components/akun/FormKataSandi.tsx`, `resources/js/pages/Auth/ConfirmPassword.tsx`, `resources/views/offline.blade.php`
- Tugas 10: `.env.example`, `app/Models/ComparisonShare.php`, `app/Services/SchedulerHealthService.php`, `config/hiking.php`, `routes/console.php`
- Tugas 13: `CONTRIBUTING.md`, `PRODUCT.md`, `README.md`, `docs/ARCHITECTURE.md`, `docs/PRD.md`
- Tugas 14: `app/Services/UsabilityStudyService.php`, `docs/PROTOKOL-UJI-KEGUNAAN.md`, `tailwind.config.js`, `tests/Feature/InertiaLayoutTest.php`
- Tugas 15: `docs/audit/aksesibilitas-w9.md`, `resources/js/pages/Galat.tsx`
- Tugas 16: `HANDOVER.md`

Varian repo nyata (tidak diputar ulang) menambah: `tests/Feature/FlashKeyPairTest.php` (dibuat, Tugas 4), serta
`app/Services/Dashboard/RingkasanDasbor.php` dan `app/Services/Trips/TripSteps.php` (diubah, Tugas 2). Tugas 16
(koordinator) menambah tangkapan `docs/audit/klik-lewat/w9/*.webp`, mengisi kolom Hasil daftar periksa, gerbang lokal,
dan kolom Status audit 002, menambah satu butir di `docs/audit/keamanan-w9.md`, membuat ledger
`.superpowers/sdd/2026-10-09-memount-v3-w9-penutup/` beserta `permintaan-izin-akhir.md`, dan memperbarui
`HANDOVER.md` bagian 0 serta `.superpowers/sdd/2026-09-26-memount-v3-program.md`. Tidak ada migrasi baru.

---

### Task 1: Halaman galat berbahasa Indonesia untuk muat penuh dan kunjungan Inertia

Titipan W4-6j: sampai W8, galat pada kunjungan Inertia (429 dari batas laju, 403 trip orang lain, 404, 419 sesi habis,
500) tampil sebagai dialog pengembang Inertia yang memuat halaman HTML di iframe, dan 429 berbahasa Inggris karena
Laravel tidak punya view 429 proyek. Tugas ini memusatkan kata setiap galat di `App\Support\HalamanGalat` (W9-R2),
menulis ulang keenam view Blade sebagai satu layout bergaya v3 dengan tombol `x-ui.button` (titipan W1-F2), dan
menjawab kunjungan Inertia dengan halaman React `Galat` lewat `respond()` di `bootstrap/app.php` (W9-R3). Halaman
React tidak memakai kerangka dan tidak menerima props bersama, karena props kerangka membaca basis data. Galat 500
dalam mode debug tetap dialog pengembang, supaya pengembang tetap melihat jejaknya. Galat validasi (422 dan
pengalihan kembali) tidak tersentuh.

**Files:**
- Create: `app/Support/HalamanGalat.php`
- Create: `resources/js/pages/Galat.tsx`
- Create: `resources/views/errors/429.blade.php`
- Create: `tests/Feature/InertiaErrorPageTest.php`
- Create: `tests/Pratinjau/HalamanGalatPratinjauTest.php`
- Modify: `bootstrap/app.php`
- Modify: `resources/views/errors/layout.blade.php`
- Modify: `tests/Feature/EmptyStateCoverageTest.php`
- Modify: `tests/Feature/ErrorPagesTest.php`
- Modify: `tests/Feature/PageHeaderContractTest.php`
- Rewrite (Tulis ulang utuh): `resources/views/errors/403.blade.php`
- Rewrite (Tulis ulang utuh): `resources/views/errors/404.blade.php`
- Rewrite (Tulis ulang utuh): `resources/views/errors/419.blade.php`
- Rewrite (Tulis ulang utuh): `resources/views/errors/500.blade.php`
- Rewrite (Tulis ulang utuh): `resources/views/errors/503.blade.php`

**Interfaces:**
- Consumes: `Inertia\ExceptionResponse` (Inertia Laravel 3.3.4), `Inertia::flushShared()`, komponen Blade `x-ui.button`, `x-brand.head`, primitif React `Lockup`, `Button`, `JudulHalaman`.
- Produces: `App\Support\HalamanGalat::KODE` (403, 404, 419, 429, 500, 503); `HalamanGalat::isi(int $kode, ?Throwable $galat, Request $request): array{kode: int, label: string, judul: string, penjelasan: list<string>, tindakan: list<array{label: string, href: string, utama: bool}>}`; `HalamanGalat::props(int $kode, ?Throwable $galat, Request $request): array` (prop `title`, `galat`, `merek`, `demo`, `tema`, dan `tampilan`, tanpa prop bersama kerangka); `HalamanGalat::tanggapiInertia(ExceptionResponse $tanggapan): Response`; halaman React `Galat` dengan `export interface IsiGalat`. Tugas 15 menambah pemindahan fokus ke `Galat.tsx`.

- [ ] **Step 1: Tulis test yang gagal.** Test baru `InertiaErrorPageTest` (dibuat dengan `make:test`), lalu tiga test
lama diperbarui menurut niat baru: `ErrorPagesTest` (layout bersama dan 429 muat penuh), `PageHeaderContractTest` dan
`EmptyStateCoverageTest` (halaman `Galat` sengaja tanpa kerangka dan tanpa daftar, dengan alasan tertulis).

Buat `tests/Feature/InertiaErrorPageTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use RuntimeException;
use Tests\TestCase;

/**
 * Kunjungan Inertia yang berakhir galat (ruling W4-6j, dititipkan ke W9).
 *
 * Sebelum W9, setiap kunjungan Inertia yang dijawab 403, 404, 419, 429, 500, atau 503
 * menampilkan dialog pengembang Inertia: halaman HTML galat di dalam iframe, dan untuk 429
 * halaman bawaan Laravel berbahasa Inggris karena aplikasi tidak punya 429.blade.php.
 * Kini kunjungan itu mendapat halaman React Galat dengan isi yang sama persis dengan
 * halaman Blade untuk muat penuh (App\Support\HalamanGalat), dan halaman itu tidak membaca
 * props bersama yang menyentuh basis data, karena ia dirender justru ketika sesuatu rusak.
 */
class InertiaErrorPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_inertia_visit_to_a_missing_page_gets_the_indonesian_error_page_instead_of_the_dialog(): void
    {
        $respons = $this->kunjunganInertia('GET', '/jalur-yang-tidak-pernah-ada');

        $respons->assertStatus(404)->assertHeader('X-Inertia', 'true');
        $this->assertSame('Galat', $respons->json('component'));
        $this->assertSame(404, $respons->json('props.galat.kode'));
        $this->assertSame('Halaman ini tidak ditemukan', $respons->json('props.galat.judul'));
        $this->assertSame('Halaman ini tidak ditemukan', $respons->json('props.title'));
    }

    public function test_another_hikers_trip_answers_an_inertia_visit_with_the_refusal_page(): void
    {
        $trip = TripPlan::create([
            'user_id' => User::factory()->create()->id,
            'trail_id' => Trail::factory()->create()->id,
            'name' => 'Trip orang lain',
            'planned_date' => now()->addDays(4)->toDateString(),
            'trip_type' => 'CAMPING',
            'status' => 'PLANNED',
        ]);

        $respons = $this->actingAs(User::factory()->create())->kunjunganInertia('GET', '/trips/'.$trip->id);

        $respons->assertStatus(403);
        $this->assertSame('Galat', $respons->json('component'));
        $this->assertSame('Anda tidak punya akses ke halaman ini', $respons->json('props.galat.judul'));
        $this->assertStringNotContainsString('Trip orang lain', $respons->getContent(), 'Halaman penolakan tidak membawa isi trip yang ditolak.');
    }

    /**
     * Halaman galat dirender justru ketika sesuatu rusak. Props bersama kerangka membaca basis
     * data (hitungan Simpanan, pendakian aktif, peran kurator), jadi halaman ini hanya membawa
     * yang dibacanya sendiri: judul, isi galat, merek, penanda data contoh, tema, dan tampilan.
     */
    public function test_the_error_page_carries_only_props_that_never_touch_the_database(): void
    {
        $trip = TripPlan::create([
            'user_id' => User::factory()->create()->id,
            'trail_id' => Trail::factory()->create()->id,
            'name' => 'Trip orang lain',
            'planned_date' => now()->addDays(4)->toDateString(),
            'trip_type' => 'CAMPING',
            'status' => 'PLANNED',
        ]);

        $props = $this->actingAs(User::factory()->create())
            ->kunjunganInertia('GET', '/trips/'.$trip->id)
            ->json('props');

        $kunci = array_keys($props);
        sort($kunci);

        $this->assertSame(['demo', 'galat', 'merek', 'tampilan', 'tema', 'title'], $kunci);
    }

    /**
     * Batas laju untuk aksi (POST) tidak memindah alamat ke rute aksi itu: alamat halaman
     * galat adalah halaman asal (Referer), jadi "Muat ulang halaman" dan tombol Kembali
     * peramban membuka halaman yang memang dapat dibuka dengan GET.
     */
    public function test_a_throttled_inertia_action_says_how_long_to_wait_and_keeps_the_page_it_came_from(): void
    {
        Route::middleware(['web', 'throttle:1,1'])->post('/_uji/aksi-terbatas', fn () => back());

        $this->kunjunganInertia('POST', '/_uji/aksi-terbatas', ['Referer' => url('/trips?saring=semua')])->assertRedirect();
        $respons = $this->kunjunganInertia('POST', '/_uji/aksi-terbatas', ['Referer' => url('/trips?saring=semua')]);

        $respons->assertStatus(429);
        $this->assertSame('Galat', $respons->json('component'));
        $this->assertSame('Terlalu banyak permintaan dalam waktu singkat', $respons->json('props.galat.judul'));
        $this->assertSame('/trips?saring=semua', $respons->json('url'));
        $this->assertMatchesRegularExpression('/Tunggu sekitar \d+ detik, lalu muat ulang halaman\./', implode(' ', $respons->json('props.galat.penjelasan')));
        $this->assertSame([['label' => 'Muat ulang halaman', 'href' => '/trips?saring=semua', 'utama' => true]], $respons->json('props.galat.tindakan'));
    }

    public function test_a_referer_from_another_site_never_becomes_the_page_address(): void
    {
        Route::middleware(['web', 'throttle:1,1'])->post('/_uji/aksi-terbatas', fn () => back());

        $this->kunjunganInertia('POST', '/_uji/aksi-terbatas', ['Referer' => 'https://situs-lain.test/jebakan']);
        $respons = $this->kunjunganInertia('POST', '/_uji/aksi-terbatas', ['Referer' => 'https://situs-lain.test/jebakan']);

        $respons->assertStatus(429);
        $this->assertSame('/', $respons->json('url'));
        $this->assertSame('/', $respons->json('props.galat.tindakan.0.href'));
    }

    public function test_an_expired_session_on_an_inertia_action_offers_to_sign_in_again(): void
    {
        Route::middleware('web')->post('/_uji/sesi-berakhir', fn () => abort(419));

        $respons = $this->kunjunganInertia('POST', '/_uji/sesi-berakhir', ['Referer' => url('/trips')]);

        $respons->assertStatus(419);
        $this->assertSame('Sesi Anda sudah berakhir', $respons->json('props.galat.judul'));
        $this->assertSame(route('login', absolute: false), parse_url((string) $respons->json('props.galat.tindakan.0.href'), PHP_URL_PATH));
        $this->assertSame('/trips', $respons->json('url'));
    }

    /**
     * Rincian galat server tidak pernah sampai ke pendaki. Dengan APP_DEBUG menyala,
     * pengembang tetap mendapat halaman debug di dialog Inertia seperti sebelumnya.
     */
    public function test_a_server_error_hides_its_details_from_hikers_but_not_from_developers(): void
    {
        Route::middleware('web')->get('/_uji/meledak', fn () => throw new RuntimeException('rahasia-internal-basis-data'));

        config(['app.debug' => false]);
        $respons = $this->kunjunganInertia('GET', '/_uji/meledak');

        $respons->assertStatus(500);
        $this->assertSame('Galat', $respons->json('component'));
        $this->assertSame('Ada yang bermasalah di sisi kami', $respons->json('props.galat.judul'));
        $this->assertStringNotContainsString('rahasia-internal-basis-data', $respons->getContent());

        config(['app.debug' => true]);
        $debug = $this->kunjunganInertia('GET', '/_uji/meledak');

        $debug->assertStatus(500)->assertHeaderMissing('X-Inertia');
    }

    public function test_a_validation_error_still_returns_to_the_form(): void
    {
        $respons = $this->actingAs(User::factory()->create())->kunjunganInertia('POST', route('trips.store'), ['Referer' => url('/trips/create')]);

        $respons->assertRedirect('/trips/create');
        $respons->assertSessionHasErrors('trail_id');
    }

    public function test_a_full_page_load_and_an_inertia_visit_read_the_same_words(): void
    {
        $blade = $this->get('/jalur-yang-tidak-pernah-ada')->assertStatus(404)->getContent();
        $inertia = $this->kunjunganInertia('GET', '/jalur-yang-tidak-pernah-ada');

        foreach ([$inertia->json('props.galat.judul'), ...$inertia->json('props.galat.penjelasan')] as $kalimat) {
            $this->assertStringContainsString(e($kalimat), $blade);
        }
    }

    /**
     * Kunjungan Inertia dengan kepala yang sama dengan klien @inertiajs/core 3.7: GET membawa
     * versi aset server, karena versi yang berbeda dijawab 409 sebelum halaman apa pun
     * dirender, dan Accept HTML membuat Laravel tidak menjawab galat sebagai JSON.
     *
     * @param  array<string, string>  $kepala
     */
    private function kunjunganInertia(string $metode, string $alamat, array $kepala = []): TestResponse
    {
        $permintaan = $this->withHeaders([
            'X-Inertia' => 'true',
            'X-Inertia-Version' => (string) app(HandleInertiaRequests::class)->version(Request::create('/')),
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'text/html, application/xhtml+xml',
            ...$kepala,
        ]);

        return $metode === 'GET' ? $permintaan->get($alamat) : $permintaan->post($alamat);
    }
}
```

`tests/Feature/EmptyStateCoverageTest.php`:

1. Ganti:

```php
    private const DAFTAR_TETAP = [
```

dengan:

```php
    private const DAFTAR_TETAP = [
        'pages/Galat.tsx' => [
            'galat.penjelasan' => 'HalamanGalat::isi() menulis satu sampai dua kalimat untuk setiap kode',
            'galat.tindakan' => 'HalamanGalat::isi() menulis satu sampai dua tombol untuk setiap kode',
        ],
```

`tests/Feature/ErrorPagesTest.php`:

2. Ganti:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;
```

dengan:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
```

3. Ganti:

```php
            '419' => ['419'],
```

dengan:

```php
            '419' => ['419'],
            '429' => ['429'],
```

4. Ganti:

```php
            $this->assertStringNotContainsString($terlarang, $sumber);
        }
    }
}
```

dengan:

```php
            $this->assertStringNotContainsString($terlarang, $sumber);
        }
    }

    /**
     * Kata-kata keenam halaman kini tinggal di satu kelas (App\Support\HalamanGalat) yang juga
     * dibaca halaman React Galat untuk kunjungan Inertia (W9). Kelas dan layoutnya tunduk pada
     * larangan yang sama: tanpa basis data, tanpa sesi, tanpa pengguna.
     */
    public function test_the_shared_layout_and_its_words_do_not_depend_on_anything_that_may_be_broken(): void
    {
        foreach ([resource_path('views/errors/layout.blade.php'), app_path('Support/HalamanGalat.php')] as $berkas) {
            $sumber = file_get_contents($berkas);

            foreach (['@livewire', 'auth()', 'DB::', '::query()', 'session(', '->user()'] as $terlarang) {
                $this->assertStringNotContainsString($terlarang, $sumber, basename($berkas));
            }
        }
    }

    /**
     * 429 dari batas laju dulu dijawab halaman bawaan Laravel berbahasa Inggris (ruling
     * W4-6j). Halaman ini menyebut berapa lama menunggu dari kepala Retry-After, bukan
     * angka karangan.
     */
    public function test_a_rate_limited_page_load_says_how_long_to_wait(): void
    {
        Route::middleware(['web', 'throttle:1,1'])->get('/_uji/halaman-terbatas', fn () => 'isi');

        $this->get('/_uji/halaman-terbatas')->assertOk();

        $this->get('/_uji/halaman-terbatas')
            ->assertStatus(429)
            ->assertSee('Terlalu banyak permintaan dalam waktu singkat')
            ->assertSeeText('Muat ulang halaman')
            ->assertDontSee('Too Many Requests');

        $this->assertMatchesRegularExpression('/Tunggu sekitar \d+ detik, lalu muat ulang halaman\./', $this->get('/_uji/halaman-terbatas')->getContent());
    }
}
```

`tests/Feature/PageHeaderContractTest.php`:

5. Ganti:

```php
            // tanpa tombol kembali dan tanpa avatar, jadi di luar kontrak ini.
            if (str_starts_with($nama, 'Auth/') || str_starts_with($nama, 'Public/') || $nama === 'Welcome.tsx') {
```

dengan:

```php
            // tanpa tombol kembali dan tanpa avatar, jadi di luar kontrak ini. Halaman Galat
            // (W9, ruling W4-6j) juga tanpa kerangka: props bersama kerangka membaca basis data,
            // jadi ia hanya membawa judul h1 dan tombol ke halaman yang dapat dibuka dengan GET.
            if (str_starts_with($nama, 'Auth/') || str_starts_with($nama, 'Public/') || $nama === 'Welcome.tsx' || $nama === 'Galat.tsx') {
```

- [ ] **Step 2: Jalankan dan pastikan gagal.**

Run: `php artisan test --compact --filter='InertiaErrorPageTest|ErrorPagesTest|PageHeaderContractTest|EmptyStateCoverageTest'`
Expected: FAIL; pada putaran ulang 33 test, 20 lulus, 3 gagal, 10 error. Yang gagal: kunjungan Inertia ke halaman yang tidak ada masih menerima HTML dialog, 429 muat
penuh masih berbahasa Inggris, dan halaman `Galat` belum ada.

- [ ] **Step 3: Implementasi.** Kelas `HalamanGalat` dibuat dengan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:class Support/HalamanGalat --no-interaction`, view 429 dibuat langsung, halaman React dan test pratinjau dibuat langsung.

Buat `app/Support/HalamanGalat.php`:

```php
<?php

namespace App\Support;

use Illuminate\Http\Request;
use Inertia\ExceptionResponse;
use Inertia\Inertia;
use Inertia\Support\Header;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Isi halaman galat, satu sumber untuk muat penuh (resources/views/errors) dan kunjungan
 * Inertia (halaman React Galat; ruling W4-6j, dititipkan ke W9).
 *
 * Halaman ini dirender justru ketika sesuatu rusak, jadi kelas ini tidak membaca basis
 * data, sesi, atau pengguna: alamat kembali datang dari permintaan itu sendiri, waktu
 * tunggu dari kepala Retry-After batas laju, dan tema dari cookie.
 */
final class HalamanGalat
{
    /** Kode yang punya halaman sendiri. Kode lain jatuh ke penanganan bawaan Laravel. */
    public const KODE = [403, 404, 419, 429, 500, 503];

    /**
     * @return array{kode: int, label: string, judul: string, penjelasan: list<string>, tindakan: list<array{label: string, href: string, utama: bool}>}
     */
    public static function isi(int $kode, ?Throwable $galat, Request $request): array
    {
        $beranda = ['label' => 'Kembali ke halaman utama', 'href' => url('/'), 'utama' => true];
        $ulang = ['label' => 'Muat ulang halaman', 'href' => self::alamatHalaman($request), 'utama' => true];

        [$judul, $penjelasan, $tindakan] = match ($kode) {
            403 => [
                'Anda tidak punya akses ke halaman ini',
                ['Halaman ini milik pengguna lain, atau memerlukan peran yang tidak Anda miliki. Kalau menurut Anda ini keliru, hubungi pengelola aplikasi.'],
                [$beranda],
            ],
            404 => [
                'Halaman ini tidak ditemukan',
                ['Alamatnya mungkin salah ketik, atau halamannya sudah dipindahkan. Jalur yang diarsipkan juga tidak lagi dapat dibuka.'],
                [$beranda, ['label' => 'Telusuri jalur', 'href' => url('/trails'), 'utama' => false]],
            ],
            419 => [
                'Sesi Anda sudah berakhir',
                [
                    'Ini biasa terjadi ketika halaman dibiarkan terbuka lama, misalnya dibuka di rumah lalu dilanjutkan di basecamp. Bukan kesalahan Anda, dan tidak ada data yang hilang selain isian yang belum sempat tersimpan.',
                    'Masuk kembali, lalu ulangi langkah terakhir Anda.',
                ],
                [['label' => 'Masuk kembali', 'href' => route('login'), 'utama' => true]],
            ],
            429 => [
                'Terlalu banyak permintaan dalam waktu singkat',
                [
                    'Aplikasi membatasi jumlah permintaan per menit supaya layanan tetap berjalan untuk semua pendaki. Permintaan terakhir Anda belum dijalankan, dan data yang sudah tersimpan tidak berubah.',
                    self::kalimatTunggu($galat),
                ],
                [$ulang],
            ],
            500 => [
                'Ada yang bermasalah di sisi kami',
                [
                    'Kesalahan ini bukan berasal dari yang Anda lakukan. Kejadiannya sudah tercatat untuk diperiksa.',
                    'Kalau Anda sedang bersiap berangkat, jangan menunggu aplikasi ini: pastikan status jalur langsung ke pengelola atau basecamp.',
                ],
                [$beranda],
            ],
            503 => [
                'Aplikasi sedang dalam perawatan',
                [
                    'Layanan dihentikan sementara dan akan kembali. Coba beberapa saat lagi.',
                    'Kalau Anda sedang bersiap berangkat, pastikan status jalur langsung ke pengelola atau basecamp.',
                ],
                [['label' => 'Coba lagi', 'href' => self::alamatHalaman($request), 'utama' => true]],
            ],
        };

        return ['kode' => $kode, 'label' => 'Galat '.$kode, 'judul' => $judul, 'penjelasan' => $penjelasan, 'tindakan' => $tindakan];
    }

    /**
     * Props halaman React Galat. Props bersama kerangka tidak ikut: hitungan Simpanan,
     * pendakian aktif, dan peran membaca basis data, padahal halaman ini dirender saat basis
     * data mungkin justru yang rusak. Merek, tema, dan tampilan dibaca dari config dan cookie,
     * sama dengan HandleInertiaRequests::share().
     *
     * @return array<string, mixed>
     */
    public static function props(int $kode, ?Throwable $galat, Request $request): array
    {
        $isi = self::isi($kode, $galat, $request);

        return [
            'title' => $isi['judul'],
            'galat' => $isi,
            'merek' => ['nama' => config('app.name'), 'tagline' => config('brand.tagline')],
            'demo' => PenjagaDataContoh::sedangDisajikan(),
            'tema' => ThemePreference::current($request),
            'tampilan' => ['efek' => DisplayPreference::efek($request), 'gerak' => DisplayPreference::gerak($request)],
        ];
    }

    /**
     * Penanganan galat untuk kunjungan Inertia (bootstrap/app.php). Kunjungan Inertia yang
     * berakhir dengan salah satu kode KODE mendapat halaman Galat, bukan dialog pengembang
     * Inertia yang memuat halaman HTML di dalam iframe. Muat penuh tetap dijawab halaman
     * Blade, dan galat server di mesin pengembang (APP_DEBUG) tetap memakai dialog itu supaya
     * rinciannya terbaca.
     */
    public static function tanggapiInertia(ExceptionResponse $tanggapan): Response
    {
        $kode = $tanggapan->statusCode();
        $request = $tanggapan->request;

        if (! $request->header(Header::INERTIA) || ! in_array($kode, self::KODE, true) || ($kode >= 500 && config('app.debug'))) {
            return $tanggapan->response;
        }

        Inertia::flushShared();

        // Alamat aksi (POST, PUT, DELETE) tidak dapat dibuka dengan GET; halaman galatnya
        // memakai alamat halaman asal supaya muat ulang dan tombol Kembali tetap berguna.
        if (! $request->isMethod('GET')) {
            $alamat = self::alamatHalaman($request);
            Inertia::resolveUrlUsing(fn () => $alamat);
        }

        return $tanggapan->render('Galat', self::props($kode, $tanggapan->exception, $request))->toResponse($request);
    }

    /**
     * Halaman yang dapat dibuka ulang dengan GET: alamat permintaan GET itu sendiri, atau
     * halaman asal (Referer) untuk aksi, hanya bila halaman asal ada di situs ini. Hasilnya
     * jalur relatif, sama dengan alamat halaman Inertia.
     */
    private static function alamatHalaman(Request $request): string
    {
        if ($request->isMethod('GET')) {
            return self::jalurRelatif($request->fullUrl(), $request) ?? '/';
        }

        return self::jalurRelatif((string) $request->headers->get('referer'), $request) ?? '/';
    }

    private static function jalurRelatif(string $alamat, Request $request): ?string
    {
        $asal = $request->getSchemeAndHttpHost();

        if ($alamat === '' || ($alamat !== $asal && ! str_starts_with($alamat, $asal.'/') && ! str_starts_with($alamat, $asal.'?'))) {
            return null;
        }

        $jalur = substr($alamat, strlen($asal));

        return $jalur === '' ? '/' : $jalur;
    }

    private static function kalimatTunggu(?Throwable $galat): string
    {
        $detik = $galat instanceof HttpExceptionInterface ? (int) ($galat->getHeaders()['Retry-After'] ?? 0) : 0;

        return $detik > 0
            ? 'Tunggu sekitar '.$detik.' detik, lalu muat ulang halaman.'
            : 'Tunggu sekitar satu menit, lalu muat ulang halaman.';
    }
}
```

Buat `resources/js/pages/Galat.tsx`:

```tsx
import { Lockup } from '@/components/brand/Lockup';
import { Button } from '@/components/ui/Button';
import { JudulHalaman } from '@/components/ui/JudulHalaman';

/** Isi satu halaman galat dari App\Support\HalamanGalat::isi(), sama dengan halaman Blade muat penuh. */
export interface IsiGalat {
    kode: number;
    label: string;
    judul: string;
    penjelasan: string[];
    tindakan: Array<{ label: string; href: string; utama: boolean }>;
}

/**
 * Halaman galat untuk kunjungan Inertia (ruling W4-6j, W9): 403, 404, 419, 429, 500, dan 503
 * yang dulu tampil sebagai dialog pengembang Inertia berisi halaman HTML di iframe, untuk 429
 * bahkan berbahasa Inggris. Tanpa kerangka dan bilah tab: props bersama kerangka membaca
 * basis data, sedangkan halaman ini dirender ketika sesuatu mungkin sedang rusak, jadi
 * server hanya mengirim isi galat, merek, dan tema. Setiap tombol membuka halaman yang
 * dapat dibuka dengan GET.
 */
export default function Galat({ galat }: { galat: IsiGalat }) {
    return (
        <div className="min-h-dvh bg-canvas">
            <JudulHalaman />
            <main id="konten" tabIndex={-1} className="mx-auto flex min-h-dvh max-w-xl flex-col justify-center px-5 py-12">
                <Lockup href="/" className="self-start" />

                <p className="mt-10 text-meta font-bold text-muted">{galat.label}</p>
                <h1 className="mt-2 text-judul text-primary">{galat.judul}</h1>

                <div className="mt-4 space-y-3 text-isi text-secondary">
                    {galat.penjelasan.map((kalimat) => (
                        <p key={kalimat}>{kalimat}</p>
                    ))}
                </div>

                <div className="mt-8 flex flex-wrap gap-3">
                    {galat.tindakan.map((tindakan) => (
                        <Button key={tindakan.label} href={tindakan.href} variant={tindakan.utama ? 'primary' : 'secondary'}>
                            {tindakan.label}
                        </Button>
                    ))}
                </div>
            </main>
        </div>
    );
}
```

Buat `resources/views/errors/429.blade.php`:

```blade
@include('errors.layout', ['kode' => 429])
```

Buat `tests/Pratinjau/HalamanGalatPratinjauTest.php`:

```php
<?php

namespace Tests\Pratinjau;

use App\Support\HalamanGalat;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Inertia\Inertia;

/**
 * Halaman galat W9 (ruling W4-6j) untuk dipotret.
 *
 * Kunjungan Inertia menerima halaman React Galat sebagai JSON, yang tidak dapat ditulis
 * sebagai berkas pratinjau. Rute uji di bawah merender komponen dan props yang sama persis
 * (App\Support\HalamanGalat::props(), tanpa props bersama kerangka) sebagai muat penuh.
 * Halaman Blade 429 dipotret dari batas laju sungguhan.
 */
class HalamanGalatPratinjauTest extends PratinjauTestCase
{
    public function test_galat_404(): void
    {
        $this->tulisPratinjau('galat-404', $this->halamanGalat(404), 'Galat');
    }

    /**
     * 429 dengan Retry-After 42 detik, seperti yang dikirim middleware throttle.
     */
    public function test_galat_429(): void
    {
        $this->tulisPratinjau('galat-429', $this->halamanGalat(429, new ThrottleRequestsException('Too Many Attempts.', null, ['Retry-After' => 42])), 'Galat');
    }

    public function test_galat_500(): void
    {
        $this->tulisPratinjau('galat-500', $this->halamanGalat(500), 'Galat');
    }

    public function test_galat_429_muat_penuh(): void
    {
        Route::middleware(['web', 'throttle:1,1'])->get('/_pratinjau-galat/terbatas', fn () => 'isi');

        $this->get(self::ASAL.'/_pratinjau-galat/terbatas')->assertOk();
        $respons = $this->get(self::ASAL.'/_pratinjau-galat/terbatas')->assertStatus(429)->assertSee('Terlalu banyak permintaan dalam waktu singkat');
        $html = (string) $respons->getContent();

        $this->assertStringContainsString(self::ASAL.'/build/assets/', $html, 'Aset tidak dirujuk dari asal server statis.');

        File::ensureDirectoryExists(public_path('_pratinjau'));
        File::put(public_path('_pratinjau/galat-429-muat-penuh.html'), $html);
    }

    private function halamanGalat(int $kode, ?ThrottleRequestsException $galat = null): TestResponse
    {
        Route::middleware('web')->get('/_pratinjau-galat/{kode}', function (Request $request, int $kode) use ($galat) {
            Inertia::flushShared();

            return Inertia::render('Galat', HalamanGalat::props($kode, $galat, $request));
        });

        return $this->get(self::ASAL.'/_pratinjau-galat/'.$kode);
    }
}
```

`bootstrap/app.php`:

1. Ganti:

```php
use App\Support\ThemePreference;
```

dengan:

```php
use App\Support\HalamanGalat;
use App\Support\ThemePreference;
use Illuminate\Contracts\Http\Kernel;
```

2. Ganti:

```php
use Illuminate\Http\Request;
```

dengan:

```php
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Inertia\ExceptionResponse;
use Symfony\Component\HttpFoundation\Response;
```

3. Ganti:

```php
        );
    })->create();
```

dengan:

```php
        );

        // Kunjungan Inertia yang berakhir 403, 404, 419, 429, 500, atau 503 mendapat halaman
        // React Galat berbahasa Indonesia, bukan dialog pengembang Inertia (ruling W4-6j, W9).
        $exceptions->respond(fn (Response $response, Throwable $galat, Request $request) => HalamanGalat::tanggapiInertia(
            new ExceptionResponse($galat, $request, $response, app(Router::class), app(Kernel::class)),
        ));
    })->create();
```

Tulis ulang `resources/views/errors/403.blade.php` utuh:

```blade
@include('errors.layout', ['kode' => 403])
```

Tulis ulang `resources/views/errors/404.blade.php` utuh:

```blade
@include('errors.layout', ['kode' => 404])
```

Tulis ulang `resources/views/errors/419.blade.php` utuh:

```blade
@include('errors.layout', ['kode' => 419])
```

Tulis ulang `resources/views/errors/500.blade.php` utuh:

```blade
@include('errors.layout', ['kode' => 500])
```

Tulis ulang `resources/views/errors/503.blade.php` utuh:

```blade
@include('errors.layout', ['kode' => 503])
```

`resources/views/errors/layout.blade.php`:

4. Ganti:

```blade
<!DOCTYPE html>
```

dengan:

```blade
@php
    $galat = \App\Support\HalamanGalat::isi($kode, $exception ?? null, request());
@endphp
<!DOCTYPE html>
```

5. Ganti:

```blade
        <title>@yield('judul')</title>
        <x-brand.head :fonts="false" />

        {{--
            Halaman ini dirender justru ketika sesuatu sedang rusak, jadi ia tidak
            menyentuh basis data, sesi, maupun komponen Livewire. Font dari luar pun
            tidak dimuat: kalau jaringannya yang bermasalah, menunggunya hanya menambah
```

dengan:

```blade
        <title>{{ $galat['judul'] }} · {{ config('app.name') }}</title>
        <x-brand.head :fonts="false" />

        {{--
            Halaman ini dirender justru ketika sesuatu sedang rusak, jadi ia tidak menyentuh
            basis data, sesi, maupun pengguna. Kata-katanya dari App\Support\HalamanGalat,
            sumber yang sama dengan halaman React Galat untuk kunjungan Inertia (W9). Huruf
            tidak dipramuat: kalau jaringannya yang bermasalah, menunggunya hanya menambah
```

6. Ganti:

```blade
        <main class="mx-auto flex min-h-dvh max-w-xl flex-col justify-center px-4 py-12 sm:px-6">
            <p class="text-sm font-medium text-muted">@yield('kode')</p>

            <h1 class="mt-2 text-2xl font-semibold text-primary sm:text-3xl">@yield('judul')</h1>

            <div class="mt-4 space-y-3 text-base text-secondary">
                @yield('penjelasan')
            </div>

            <div class="mt-8 flex flex-wrap gap-3">
                @yield('tindakan')
```

dengan:

```blade
        <main id="konten" class="mx-auto flex min-h-dvh max-w-xl flex-col justify-center px-5 py-12">
            <a href="{{ url('/') }}" class="inline-flex min-h-11 items-center self-start rounded-kecil text-[1.0625rem] font-bold uppercase leading-none tracking-[0.06em] text-logo-type focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2">{{ config('app.name') }}</a>

            <p class="mt-10 text-meta font-bold text-muted">{{ $galat['label'] }}</p>
            <h1 class="mt-2 text-judul text-primary">{{ $galat['judul'] }}</h1>

            <div class="mt-4 space-y-3 text-isi text-secondary">
                @foreach ($galat['penjelasan'] as $kalimat)
                    <p>{{ $kalimat }}</p>
                @endforeach
            </div>

            <div class="mt-8 flex flex-wrap gap-3">
                @foreach ($galat['tindakan'] as $tindakan)
                    <x-ui.button :href="$tindakan['href']" :variant="$tindakan['utama'] ? 'primary' : 'secondary'">{{ $tindakan['label'] }}</x-ui.button>
                @endforeach
```

- [ ] **Step 4: Jalankan dan pastikan lulus.**

Run: `php artisan test --compact --filter='InertiaErrorPageTest|ErrorPagesTest|PageHeaderContractTest|EmptyStateCoverageTest'`
Expected: PASS; pada putaran ulang 33 test, 33 lulus.

- [ ] **Step 5: Format, build, suite, dan pratinjau galat.**

Run:
```bash
vendor/bin/pint --format agent app/Support/HalamanGalat.php bootstrap/app.php tests/Feature/InertiaErrorPageTest.php tests/Feature/ErrorPagesTest.php tests/Feature/PageHeaderContractTest.php tests/Feature/EmptyStateCoverageTest.php tests/Pratinjau/HalamanGalatPratinjauTest.php
DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build
node_modules/.bin/tsc --noEmit
php artisan test --compact --testsuite=Unit,Feature
php artisan test --compact --testsuite=Pratinjau --filter=HalamanGalatPratinjauTest
```
Expected: Pint lulus, build sukses, `tsc` tanpa galat, suite 2.270 test, 2.268 lulus, 2 dilewati (sebelum tugas: 2.257), pratinjau galat 4 test lulus dan menulis
`public/_pratinjau/galat-404.html`, `galat-429.html`, `galat-500.html`, dan `galat-429-muat-penuh.html`.

- [ ] **Step 6: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 2: Jam bertitik berzona dan tahun pada tanggal pendek

Titipan W3 ("format jam 19:33 lawan DESIGN 15.06", "tahun pada waktu berformat `'j M'`") dan W4 ("sapuan format jam",
"tahun pada tanggal `'D, j M'`"). DESIGN.md bagian Tipografi menulis jam bertitik; kode tampilan menulis `H:i` di
belasan tempat, dan tanggal `'j M'` tanpa tahun membuat trip Tahun Baru yang direncanakan Desember terbaca seperti
tanggal tahun ini. Tugas ini menjadikan `Timezone::display()` bertitik secara bawaan (`'j M Y H.i'`), menambah
`Timezone::tanggalPendek()` yang menyebut tahun hanya di luar tahun berjalan (W9-R4), lalu menyapu setiap pemanggil
tampilan: layanan halaman, notifikasi surel, penjadwal pengingat, dan label kesehatan penjadwal. Format mesin
(ISO 8601 untuk klien, `Y-m-d` untuk isian) tidak berubah.

**Files:**
- Create: `tests/Feature/IndonesianTimeFormatTest.php`
- Modify: `app/Notifications/PengingatIzinDibuka.php`
- Modify: `app/Notifications/TripReminderNotification.php`
- Modify: `app/Services/Community/KabarJalur.php`
- Modify: `app/Services/Dashboard/TenggatTrip.php`
- Modify: `app/Services/PermitService.php`
- Modify: `app/Services/PreparationService.php`
- Modify: `app/Services/SchedulerHealthService.php`
- Modify: `app/Services/Trails/TrailDetailPage.php`
- Modify: `app/Services/Trips/DaftarPengingat.php`
- Modify: `app/Services/Trips/HalamanKesiapan.php`
- Modify: `app/Services/Trips/HalamanPersiapan.php`
- Modify: `app/Services/Trips/PaketLapangan.php`
- Modify: `app/Services/Trips/RencanaTrip.php`
- Modify: `app/Services/Trips/TenggatIzin.php`
- Modify: `app/Services/Trips/TripReminderScheduler.php`
- Modify: `app/Support/Timezone.php`
- Modify: `tests/Feature/PermitWindowReminderTest.php`
- Modify: `tests/Feature/ReminderSheetTest.php`
- Modify: `tests/Feature/TripReminderTest.php`
- Modify: `tests/Unit/WeatherTimezoneTest.php`
- Modify (hanya varian repo nyata, Step 3): `app/Services/Dashboard/RingkasanDasbor.php`, `app/Services/Trips/TripSteps.php`

**Interfaces:**
- Consumes: `App\Support\Timezone::label()`, `Carbon::translatedFormat()` (lokal `id`).
- Produces: `Timezone::display(?Carbon $instant, string $timezone, string $format = 'j M Y H.i'): ?string`; `Timezone::tanggalPendek(CarbonInterface $tanggal, bool $denganHari = false, string $zona = self::DEFAULT): string`. Tugas 10 memakai label jadwal bertitik dari tugas ini.

- [ ] **Step 1: Tulis test yang gagal.** Test baru `IndonesianTimeFormatTest` (sapuan sumber dan perilaku), dan empat
test lama diperbarui dari harapan jam berkolon ke jam bertitik dengan alasan tertulis.

Buat `tests/Feature/IndonesianTimeFormatTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\PreparationCategory;
use App\Enums\PreparationStatus;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Support\Timezone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Jam dan tanggal di layar mengikuti DESIGN.md bagian Tipografi (W9, sapuan yang ditunda
 * W3-G2 dan W4): jam ditulis "19.33", bukan "19:33", dan tanggal pendek menulis tahunnya bila
 * tanggal itu bukan di tahun berjalan (W3-1g, W4 "'D, j M' tanpa tahun"). Trip Tahun Baru yang
 * direncanakan pada Desember tidak terbaca seperti tanggal dekat di tahun ini.
 *
 * Isian jam (input type=time dan datetime-local) tetap memakai titik dua, karena itu bentuk
 * yang dibaca peramban, bukan teks untuk pendaki.
 */
class IndonesianTimeFormatTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Nilai isian yang memang memakai titik dua. Selain baris ini, format jam untuk ditampilkan
     * di app/ memakai titik. Aturan validasi (date_format) dan pembacaan isian
     * (createFromFormat) tidak menampilkan apa pun, jadi tidak disapu.
     *
     * @var array<string, string>
     */
    private const JAM_ISIAN = [
        "app/Http/Controllers/Trips/TripController.php|'start_time' => \$trip->start_time ? Carbon::parse(\$trip->start_time)->format('H:i') : ''," => 'nilai isian jam mulai (input type=time)',
        "app/Services/Trips/PesanRencana.php|'kembali' => ['tanggal' => \$kembali?->toDateString(), 'jam' => \$kembali?->format('H:i')]," => 'nilai isian jam kembali (input type=time)',
        'app/Services/DataResmi/HalamanCatatanStatus.php|' => 'nilai isian datetime-local',
    ];

    /**
     * Tanggal pendek yang sengaja tanpa tahun: semuanya tanggal yang paling lama sebulan lalu,
     * atau daftar yang sudah dipilah per tahun di kepala halamannya.
     *
     * @var array<string, string>
     */
    private const TANGGAL_TANPA_TAHUN = [
        'app/Services/Community/HubProgres.php' => 'riwayat Progres dipilah per tahun; tahunnya di kepala halaman',
        'app/Services/Mountains/HalamanGunung.php' => 'tanggal pendakian laporan komunitas, paling lama 30 hari (conditions.community_recent_days)',
        'app/Services/Trails/RouteComparisonTable.php' => 'tanggal pendakian laporan komunitas, paling lama 30 hari',
        'app/Services/Trails/TautanBagikan.php' => 'tanggal dibuat tautan bagikan yang masih aktif (share_links.ttl_days)',
        'app/Services/Trips/PaketLapangan.php|$l->hike_date' => 'tanggal pendakian laporan komunitas di paket, paling lama 30 hari',
    ];

    public function test_a_clock_time_is_written_with_a_period_and_its_zone(): void
    {
        $instan = Carbon::parse('2026-09-29 12:33:00', 'UTC');

        $this->assertSame('29 Sep 2026 19.33 WIB', Timezone::display($instan, 'Asia/Jakarta'));
        $this->assertSame('29 Sep 2026 20.33 WITA', Timezone::display($instan, 'Asia/Makassar'));
    }

    public function test_a_short_date_names_its_year_only_outside_the_current_year(): void
    {
        $this->travelTo(Carbon::parse('2026-12-20 03:00:00', 'UTC'));

        $this->assertSame('3 Nov', Timezone::tanggalPendek(Carbon::parse('2026-11-03')));
        $this->assertSame('Jum, 1 Jan 2027', Timezone::tanggalPendek(Carbon::parse('2027-01-01'), denganHari: true));
        $this->assertSame('3 Nov 2025', Timezone::tanggalPendek(Carbon::parse('2025-11-03')));
    }

    /**
     * Trip 10 Januari 2027 yang dibuka pada 20 Desember 2026: butir cuacanya menunggu BMKG
     * sampai 7 Januari 2027, dan tahunnya tertulis.
     */
    public function test_a_new_year_trip_names_the_year_it_waits_for(): void
    {
        $this->travelTo(Carbon::parse('2026-12-20 03:00:00', 'UTC'));
        $trip = TripPlan::factory()->create(['trail_id' => Trail::factory()->published()->create()->id, 'planned_date' => '2027-01-10']);
        TripPreparationItem::create([
            'trip_plan_id' => $trip->id,
            'category' => PreparationCategory::WEATHER->value,
            'label' => 'Memeriksa prakiraan cuaca terbaru',
            'is_critical' => true,
            'status' => PreparationStatus::NOT_CONFIRMED->value,
        ]);

        $props = $this->actingAs($trip->user)->get(route('trips.preparation', $trip))->assertOk()->viewData('page')['props'];
        $cuaca = collect($props['kategori'])->firstWhere('kunci', PreparationCategory::WEATHER->value);

        $this->assertSame('Menunggu BMKG: terbit mulai Kam, 7 Jan 2027', $cuaca['items'][0]['menunggu']);
    }

    public function test_no_screen_writes_a_clock_time_with_a_colon(): void
    {
        $pelanggar = [];

        foreach ($this->barisPhp() as [$jalur, $baris]) {
            $jamTertulis = (bool) preg_match('/pukul (?:%02d|\d{2}):\d{2}/', $baris);

            if (! $jamTertulis && (! preg_match("/'[^']*H:i[^']*'/", $baris) || str_contains($baris, 'H:i:s') || str_contains($baris, 'date_format:') || str_contains($baris, 'createFromFormat('))) {
                continue;
            }

            if (! isset(self::JAM_ISIAN[$jalur.'|'.$baris]) && ! isset(self::JAM_ISIAN[$jalur.'|'])) {
                $pelanggar[] = $jalur.': '.$baris;
            }
        }

        $this->assertSame([], $pelanggar, 'Jam di layar memakai titik ("19.33"), bukan titik dua (DESIGN.md bagian Tipografi).');
    }

    public function test_every_short_date_goes_through_the_one_helper_or_says_why_not(): void
    {
        $pelanggar = [];

        foreach ($this->barisPhp() as [$jalur, $baris]) {
            if (! preg_match("/translatedFormat\\('(?:D, )?j M'\\)/", $baris)) {
                continue;
            }

            $diizinkan = isset(self::TANGGAL_TANPA_TAHUN[$jalur])
                || collect(self::TANGGAL_TANPA_TAHUN)->keys()->contains(fn (string $kunci) => str_contains($kunci, '|') && str_starts_with($kunci, $jalur.'|') && str_contains($baris, explode('|', $kunci)[1]));

            if (! $diizinkan) {
                $pelanggar[] = $jalur.': '.$baris;
            }
        }

        $this->assertSame([], $pelanggar, 'Tanggal pendek ditulis lewat Timezone::tanggalPendek(), yang menulis tahun di luar tahun berjalan.');
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private function barisPhp(): array
    {
        $hasil = [];

        foreach (File::allFiles(app_path()) as $berkas) {
            if ($berkas->getExtension() !== 'php') {
                continue;
            }

            $jalur = 'app/'.str_replace('\\', '/', $berkas->getRelativePathname());

            foreach (preg_split('/\R/', $berkas->getContents()) as $baris) {
                $hasil[] = [$jalur, trim($baris)];
            }
        }

        return $hasil;
    }
}
```

`tests/Feature/PermitWindowReminderTest.php`:

1. Ganti:

```php
    }

    public function test_the_trip_page_lists_the_opening_reminder_with_its_zone(): void
```

dengan:

```php
    }

    /**
     * Jam bertitik dan tanggal tanpa nol di depan sejak W9 (DESIGN.md bagian Tipografi; sapuan
     * format jam yang ditunda W3-G2 dan W4).
     */
    public function test_the_trip_page_lists_the_opening_reminder_with_its_zone(): void
```

2. Ganti:

```php
            ->where('pengingat.daftar.0.waktu', 'Rabu, 21 Okt 2026 07:00 WITA')
            ->where('pengingat.daftar.0.keadaan', 'menunggu')
            ->where('pengingat.jadwal', 'Pengingat dikirim 3 hari dan sehari sebelum tanggal mulai, pukul 07:00 waktu setempat gunungnya (WIB, WITA, atau WIT). Bila aturan izin jalurnya mencatat kapan pemesanan dibuka, satu pengingat juga dikirim pada hari itu.'));
```

dengan:

```php
            ->where('pengingat.daftar.0.waktu', 'Rabu, 21 Okt 2026 07.00 WITA')
            ->where('pengingat.daftar.0.keadaan', 'menunggu')
            ->where('pengingat.jadwal', 'Pengingat dikirim 3 hari dan sehari sebelum tanggal mulai, pukul 07.00 waktu setempat gunungnya (WIB, WITA, atau WIT). Bila aturan izin jalurnya mencatat kapan pemesanan dibuka, satu pengingat juga dikirim pada hari itu.'));
```

`tests/Feature/ReminderSheetTest.php`:

3. Ganti:

```php
    }

    public function test_the_sheet_lists_the_owners_scheduled_reminders_and_permit_deadlines(): void
```

dengan:

```php
    }

    /**
     * Jam bertitik dan tanggal tanpa nol di depan sejak W9 (DESIGN.md bagian Tipografi; sapuan
     * format jam yang ditunda W3-G2 dan W4).
     */
    public function test_the_sheet_lists_the_owners_scheduled_reminders_and_permit_deadlines(): void
```

4. Ganti:

```php
                ->where('daftarPengingat.pengingat.0.waktu', 'Jumat, 09 Okt 2026 07:00 WIB')
```

dengan:

```php
                ->where('daftarPengingat.pengingat.0.waktu', 'Jumat, 9 Okt 2026 07.00 WIB')
```

5. Ganti:

```php
                ->where('daftarPengingat.tenggatIzin.0.kalimat', 'Pemesanan izin ditutup Jumat, 09 Okt 2026 (H-3).')
```

dengan:

```php
                ->where('daftarPengingat.tenggatIzin.0.kalimat', 'Pemesanan izin ditutup Jumat, 9 Okt 2026 (H-3).')
```

`tests/Feature/TripReminderTest.php`:

6. Ganti:

```php
    }

    public function test_the_start_time_is_written_with_its_zone(): void
```

dengan:

```php
    }

    /**
     * Jam di surel ditulis dengan titik, sama dengan layar (W9, DESIGN.md bagian Tipografi).
     */
    public function test_the_start_time_is_written_with_its_zone(): void
```

7. Ganti:

```php
        $this->assertStringContainsString('Sabtu, 10 Oktober 2026, mulai pukul 05:30 WITA', $isi);
```

dengan:

```php
        $this->assertStringContainsString('Sabtu, 10 Oktober 2026, mulai pukul 05.30 WITA', $isi);
```

8. Ganti:

```php
    }

    public function test_the_trip_page_lists_each_reminder_with_its_time_zone_and_status(): void
```

dengan:

```php
    }

    /**
     * Jam bertitik dan tanggal tanpa nol di depan sejak W9 (DESIGN.md bagian Tipografi).
     */
    public function test_the_trip_page_lists_each_reminder_with_its_time_zone_and_status(): void
```

9. Ganti:

```php
            ->where('pengingat.daftar.0.waktu', 'Rabu, 07 Okt 2026 07:00 WITA')
```

dengan:

```php
            ->where('pengingat.daftar.0.waktu', 'Rabu, 7 Okt 2026 07.00 WITA')
```

10. Ganti:

```php
            ->where('pengingat.daftar.0.keadaanTeks', 'Terkirim Rabu, 07 Okt 2026 07:00 WITA')
```

dengan:

```php
            ->where('pengingat.daftar.0.keadaanTeks', 'Terkirim Rabu, 7 Okt 2026 07.00 WITA')
```

`tests/Unit/WeatherTimezoneTest.php`:

11. Ganti:

```php
        // 15:00 UTC = 23:00 WITA
        $this->assertStringContainsString('23:00', $rendered);
```

dengan:

```php
        // 15.00 UTC = 23.00 WITA. Jam ditulis dengan titik sejak W9 (DESIGN.md bagian Tipografi).
        $this->assertStringContainsString('23.00', $rendered);
```

- [ ] **Step 2: Jalankan dan pastikan gagal.**

Run: `php artisan test --compact --filter='IndonesianTimeFormatTest|WeatherTimezoneTest|PermitWindowReminderTest|ReminderSheetTest|TripReminderTest'`
Expected: FAIL; pada putaran ulang 60 test, 50 lulus, 9 gagal, 1 error. Yang gagal: jam masih `07:00`, tanggal tahun depan tanpa tahun, `tanggalPendek()` belum ada.

- [ ] **Step 3: Implementasi.**

`app/Notifications/PengingatIzinDibuka.php`:

1. Ganti:

```php
        $tanggal = $this->trip->planned_date->translatedFormat('l, d F Y');
```

dengan:

```php
        $tanggal = $this->trip->planned_date->translatedFormat('l, j F Y');
```

`app/Notifications/TripReminderNotification.php`:

2. Ganti:

```php
        $tanggal = $this->trip->planned_date->translatedFormat('l, d F Y');

        $waktu = $this->trip->start_time
            ? sprintf('%s, mulai pukul %s %s', $tanggal, Carbon::parse($this->trip->start_time)->format('H:i'), $zona)
```

dengan:

```php
        $tanggal = $this->trip->planned_date->translatedFormat('l, j F Y');

        $waktu = $this->trip->start_time
            ? sprintf('%s, mulai pukul %s %s', $tanggal, Carbon::parse($this->trip->start_time)->format('H.i'), $zona)
```

`app/Services/Community/KabarJalur.php`:

3. Ganti:

```php
        $dasar['waktuPrakiraan'] = 'Prakiraan pukul '.Timezone::display($terdekat->forecast_at, $zona, 'H:i');
```

dengan:

```php
        $dasar['waktuPrakiraan'] = 'Prakiraan pukul '.Timezone::display($terdekat->forecast_at, $zona, 'H.i');
```

`app/Services/Dashboard/TenggatTrip.php`:

4. Ganti:

```php
            'judul' => 'Prakiraan BMKG untuk '.$trip->planned_date->translatedFormat('j M').' mulai terbit',
```

dengan:

```php
            'judul' => 'Prakiraan BMKG untuk '.Timezone::tanggalPendek($trip->planned_date).' mulai terbit',
```

`app/Services/PermitService.php`:

5. Ganti:

```php
        return $tanggal->translatedFormat('D, j M');
```

dengan:

```php
        return Timezone::tanggalPendek($tanggal, denganHari: true);
```

`app/Services/PreparationService.php`:

6. Ganti:

```php
            : 'Prakiraan BMKG untuk tanggal trip ini belum terbit. Butir cuaca dapat dikonfirmasi mulai '.$terbit->translatedFormat('D, j M').'.';
```

dengan:

```php
            : 'Prakiraan BMKG untuk tanggal trip ini belum terbit. Butir cuaca dapat dikonfirmasi mulai '.Timezone::tanggalPendek($terbit, denganHari: true).'.';
```

`app/Services/SchedulerHealthService.php`:

7. Ganti:

```php
            'jadwal' => 'Dua kali sehari, pukul 06:00 dan 18:00 WIB',
```

dengan:

```php
            'jadwal' => 'Dua kali sehari, pukul 06.00 dan 18.00 WIB',
```

8. Ganti:

```php
            'jadwal' => 'Setiap hari pukul 07:00 WIB',
```

dengan:

```php
            'jadwal' => 'Setiap hari pukul 07.00 WIB',
```

`app/Services/Trails/TrailDetailPage.php`:

9. Ganti:

```php
        $tanggal = $trip->planned_date->translatedFormat('j M');
```

dengan:

```php
        $tanggal = Timezone::tanggalPendek($trip->planned_date);
```

10. Ganti:

```php
                    .' hari ke depan; prakiraan tanggal Anda muncul di sini mulai '.$terbit->translatedFormat('j M').'.',
```

dengan:

```php
                    .' hari ke depan; prakiraan tanggal Anda muncul di sini mulai '.Timezone::tanggalPendek($terbit).'.',
```

11. Ganti:

```php
            'waktu' => $s->forecast_at->copy()->setTimezone($zona)->translatedFormat('d M H:i'),
```

dengan:

```php
            'waktu' => $s->forecast_at->copy()->setTimezone($zona)->translatedFormat('j M H.i'),
```

`app/Services/Trips/DaftarPengingat.php`:

12. Ganti:

```php
    private const FORMAT_WAKTU = 'l, d M Y H:i';
```

dengan:

```php
    private const FORMAT_WAKTU = 'l, j M Y H.i';
```

`app/Services/Trips/HalamanKesiapan.php`:

13. Ganti:

```php
                'menunggu' => $menunggu ? 'Menunggu BMKG: terbit mulai '.$terbit->translatedFormat('D, j M') : null,
```

dengan:

```php
                'menunggu' => $menunggu ? 'Menunggu BMKG: terbit mulai '.Timezone::tanggalPendek($terbit, denganHari: true) : null,
```

14. Ganti:

```php
                ? $butir->count().' butir kritis menunggu BMKG (terbit '.$terbit->translatedFormat('D, j M').').'
```

dengan:

```php
                ? $butir->count().' butir kritis menunggu BMKG (terbit '.Timezone::tanggalPendek($terbit, denganHari: true).').'
```

15. Ganti:

```php
                ? $nonKritisMenunggu.' butir lain menunggu BMKG (terbit '.$terbit?->translatedFormat('D, j M').').'
```

dengan:

```php
                ? $nonKritisMenunggu.' butir lain menunggu BMKG (terbit '.($terbit !== null ? Timezone::tanggalPendek($terbit, denganHari: true) : '').').'
```

16. Ganti:

```php
            'judul' => 'Prakiraan untuk '.$trip->planned_date->translatedFormat('D, j M'),
```

dengan:

```php
            'judul' => 'Prakiraan untuk '.Timezone::tanggalPendek($trip->planned_date, denganHari: true),
```

17. Ganti:

```php
            return $dasar + ['keadaan' => 'menunggu', 'pesan' => 'Menunggu BMKG. Terbit mulai '.$terbit->translatedFormat('D, j M').'; Memount tidak menebak cuaca sebelum itu.', 'baris' => [], 'sebagian' => null];
```

dengan:

```php
            return $dasar + ['keadaan' => 'menunggu', 'pesan' => 'Menunggu BMKG. Terbit mulai '.Timezone::tanggalPendek($terbit, denganHari: true).'; Memount tidak menebak cuaca sebelum itu.', 'baris' => [], 'sebagian' => null];
```

18. Ganti:

```php
            'tanggal' => $lalu->computed_at?->copy()->setTimezone($zona)->translatedFormat('j M'),
```

dengan:

```php
            'tanggal' => $lalu->computed_at === null ? null : Timezone::tanggalPendek($lalu->computed_at->copy()->setTimezone($zona)),
```

`app/Services/Trips/HalamanPersiapan.php`:

19. Ganti:

```php
use App\Services\PreparationService;
```

dengan:

```php
use App\Services\PreparationService;
use App\Support\Timezone;
```

20. Ganti:

```php
                ? 'Menunggu BMKG: terbit mulai '.$terbit->translatedFormat('D, j M')
```

dengan:

```php
                ? 'Menunggu BMKG: terbit mulai '.Timezone::tanggalPendek($terbit, denganHari: true)
```

21. Ganti:

```php
            ? $trip->planned_date->copy()->subDays($aturan->booking_closes_days_before)->translatedFormat('D, j M')
            : null;
        $buka = $aturan->booking_opens_days_before !== null
            ? $trip->planned_date->copy()->subDays($aturan->booking_opens_days_before)->translatedFormat('D, j M')
```

dengan:

```php
            ? Timezone::tanggalPendek($trip->planned_date->copy()->subDays($aturan->booking_closes_days_before), denganHari: true)
            : null;
        $buka = $aturan->booking_opens_days_before !== null
            ? Timezone::tanggalPendek($trip->planned_date->copy()->subDays($aturan->booking_opens_days_before), denganHari: true)
```

`app/Services/Trips/PaketLapangan.php`:

22. Ganti:

```php
            return ['kalimat' => 'Prakiraan BMKG untuk '.$trip->planned_date->translatedFormat('j M').' belum terbit saat paket dibuat.', 'baris' => []];
```

dengan:

```php
            return ['kalimat' => 'Prakiraan BMKG untuk '.Timezone::tanggalPendek($trip->planned_date).' belum terbit saat paket dibuat.', 'baris' => []];
```

`app/Services/Trips/RencanaTrip.php`:

23. Ganti:

```php
                ? $tanggal->translatedFormat('j M')
                : $tanggal->translatedFormat($tanggal->month === $selesai->month ? 'j' : 'j M').' sampai '.$selesai->translatedFormat('j M'),
            'lama' => $lama.' hari',
            'prakiraan' => $terbit->toDateString() > $hariIni
                ? 'Prakiraan BMKG untuk '.$tanggal->translatedFormat('D, j M').' terbit mulai '.$terbit->translatedFormat('D, j M').'. Sebelum itu Memount tidak menebak cuacanya.'
                : 'Prakiraan BMKG untuk '.$tanggal->translatedFormat('D, j M').' sudah dapat terbit. Prakiraannya tampil di cek kesiapan begitu tersedia dari BMKG.',
```

dengan:

```php
                ? Timezone::tanggalPendek($tanggal)
                : ($tanggal->isSameMonth($selesai) ? $tanggal->format('j') : Timezone::tanggalPendek($tanggal)).' sampai '.Timezone::tanggalPendek($selesai),
            'lama' => $lama.' hari',
            'prakiraan' => $terbit->toDateString() > $hariIni
                ? 'Prakiraan BMKG untuk '.self::hari($tanggal).' terbit mulai '.self::hari($terbit).'. Sebelum itu Memount tidak menebak cuacanya.'
                : 'Prakiraan BMKG untuk '.self::hari($tanggal).' sudah dapat terbit. Prakiraannya tampil di cek kesiapan begitu tersedia dari BMKG.',
```

24. Ganti:

```php
        return $tanggal->translatedFormat('D, j M');
```

dengan:

```php
        return Timezone::tanggalPendek($tanggal, denganHari: true);
```

`app/Services/Trips/TenggatIzin.php`:

25. Ganti:

```php
    private const FORMAT_TANGGAL = 'l, d M Y';
```

dengan:

```php
    private const FORMAT_TANGGAL = 'l, j M Y';
```

`app/Services/Trips/TripReminderScheduler.php`:

26. Ganti:

```php
    private const FORMAT_WAKTU = 'l, d M Y H:i';
```

dengan:

```php
    private const FORMAT_WAKTU = 'l, j M Y H.i';
```

27. Ganti:

```php
            'Pengingat dikirim %s sebelum tanggal mulai, pukul %02d:00 waktu setempat gunungnya (WIB, WITA, atau WIT). Bila aturan izin jalurnya mencatat kapan pemesanan dibuka, satu pengingat juga dikirim pada hari itu.',
```

dengan:

```php
            'Pengingat dikirim %s sebelum tanggal mulai, pukul %02d.00 waktu setempat gunungnya (WIB, WITA, atau WIT). Bila aturan izin jalurnya mencatat kapan pemesanan dibuka, satu pengingat juga dikirim pada hari itu.',
```

`app/Support/Timezone.php`:

28. Ganti:

```php
use App\Models\Trail;
```

dengan:

```php
use App\Models\Trail;
use Carbon\CarbonInterface;
```

29. Ganti:

```php
     * Tanpa penanda itu pengguna tidak punya cara tahu jam mana yang dimaksud.
     */
    public static function display(?Carbon $instant, string $timezone, string $format = 'd M Y H:i'): ?string
```

dengan:

```php
     * Tanpa penanda itu pengguna tidak punya cara tahu jam mana yang dimaksud. Jam ditulis
     * dengan titik ("19.33"), seperti DESIGN.md bagian Tipografi (W9).
     */
    public static function display(?Carbon $instant, string $timezone, string $format = 'j M Y H.i'): ?string
```

30. Ganti:

```php
        return $instant->copy()->setTimezone($timezone)->translatedFormat($format).' '.self::label($timezone);
    }
}
```

dengan:

```php
        return $instant->copy()->setTimezone($timezone)->translatedFormat($format).' '.self::label($timezone);
    }

    /**
     * Tanggal pendek "3 Nov" atau "Sab, 3 Nov", dengan tahun hanya bila tanggalnya bukan di
     * tahun berjalan (W3-1g, W4): trip Tahun Baru yang direncanakan pada Desember dan catatan
     * dari tahun lalu tidak terbaca seperti tanggal tahun ini. Tanggal dibaca apa adanya;
     * instan diubah ke zona gunungnya lebih dulu oleh pemanggil.
     */
    public static function tanggalPendek(CarbonInterface $tanggal, bool $denganHari = false, string $zona = self::DEFAULT): string
    {
        $format = ($denganHari ? 'D, ' : '').'j M';

        return $tanggal->translatedFormat($tanggal->year === Carbon::now($zona)->year ? $format : $format.' Y');
    }
}
```

**Varian repo nyata untuk Tugas 2** (tidak disimulasikan; Pra-cek butir 1b). Dasar simulasi tidak memuat gelombang
perbaikan akhir W4 dan gelombang akhir W5 yang sudah ada di repo nyata. Uji kering 8 Oktober sore menemukan empat blok
di atas yang teks lamanya tidak ada di repo nyata, dan tiga baris repo nyata yang tidak ada di dasar tetapi ditangkap
`IndonesianTimeFormatTest::test_every_short_date_goes_through_the_one_helper_or_says_why_not`. Terapkan varian berikut
hanya bila teks lama blok dasarnya tidak ditemukan; setiap teks lama varian ditemukan tepat sekali dengan Grep di repo
nyata sebelum W6.

Varian 1, pengganti blok "butir kritis menunggu BMKG" `app/Services/Trips/HalamanKesiapan.php` (repo nyata menghitung
`$kritisMenunggu`). Teks lama:

```php
                ? $kritisMenunggu.' butir kritis menunggu BMKG (terbit '.$terbit->translatedFormat('D, j M').').'
```

menjadi:

```php
                ? $kritisMenunggu.' butir kritis menunggu BMKG (terbit '.Timezone::tanggalPendek($terbit, denganHari: true).').'
```

Varian 2, pengganti blok "butir lain menunggu BMKG" di berkas yang sama (di repo nyata `$terbit` tidak lagi nullable di
baris itu). Teks lama:

```php
                ? $nonKritisMenunggu.' butir lain menunggu BMKG (terbit '.$terbit->translatedFormat('D, j M').').'
```

menjadi:

```php
                ? $nonKritisMenunggu.' butir lain menunggu BMKG (terbit '.Timezone::tanggalPendek($terbit, denganHari: true).').'
```

Varian 3, lewati blok `$tutup` dan `$buka` di `app/Services/Trips/HalamanPersiapan.php` dan blok fungsi `hari()` di
`app/Services/Trips/RencanaTrip.php`. Sesudah perbaikan akhir W4 M1, tanggal jendela izin di repo nyata hanya ditulis
`PermitService` (penentu satu sumber), dan blok `PermitService.php` tugas ini sudah mengubahnya.

Varian 4, tambahan untuk baris repo nyata yang tidak ada di dasar, `app/Services/Dashboard/RingkasanDasbor.php`. Teks
lama:

```php
            return $kapan.' '.$belum.' butir cuaca kritis menunggu BMKG, terbit mulai '.$menunggu['terbit']->translatedFormat('D, j M').'.';
```

menjadi:

```php
            return $kapan.' '.$belum.' butir cuaca kritis menunggu BMKG, terbit mulai '.Timezone::tanggalPendek($menunggu['terbit'], denganHari: true).'.';
```

Varian 5, berkas yang sama. Teks lama:

```php
                    ? sprintf('%d butir cuaca menunggu BMKG, terbit mulai %s', $menunggu['jumlah'], $menunggu['terbit']->translatedFormat('D, j M'))
```

menjadi:

```php
                    ? sprintf('%d butir cuaca menunggu BMKG, terbit mulai %s', $menunggu['jumlah'], Timezone::tanggalPendek($menunggu['terbit'], denganHari: true))
```

Varian 6, `app/Services/Trips/TripSteps.php`. Teks lama:

```php
        return sprintf('%s; %d butir cuaca menunggu BMKG, terbit mulai %s', self::teksPersiapan($persiapan), $menunggu, $terbit->translatedFormat('D, j M'));
```

menjadi:

```php
        return sprintf('%s; %d butir cuaca menunggu BMKG, terbit mulai %s', self::teksPersiapan($persiapan), $menunggu, Timezone::tanggalPendek($terbit, denganHari: true));
```

Kedua berkas varian 4 sampai 6 sudah mengimpor `App\Support\Timezone`. Untuk tanggal di tahun berjalan keluarannya sama
persis dengan `'D, j M'`, jadi test W5 yang dibekukan pada 2026 tidak berubah. Bila sapuan menunjuk baris lain dari W6
sampai W8 yang tidak ada di simulasi, perlakukan dengan pola yang sama (`Timezone::tanggalPendek()`, atau
`Timezone::display()` bertitik untuk jam); tanggal laporan komunitas yang paling lama 30 hari boleh masuk
`TANGGAL_TANPA_TAHUN` dengan alasannya. Selain dua pola itu, laporkan NEEDS_CONTEXT.

- [ ] **Step 4: Jalankan dan pastikan lulus.**

Run: `php artisan test --compact --filter='IndonesianTimeFormatTest|WeatherTimezoneTest|PermitWindowReminderTest|ReminderSheetTest|TripReminderTest'`
Expected: PASS; pada putaran ulang 60 test, 60 lulus.

- [ ] **Step 5: Format dan suite penuh.**

Run:
```bash
vendor/bin/pint --dirty --format agent
php artisan test --compact --testsuite=Unit,Feature
```
Expected: Pint lulus; suite 2.275 test, 2.273 lulus, 2 dilewati (sebelum tugas: 2.270).

- [ ] **Step 6: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 3: Kosakata Indonesia di kalimat mesin, dan satu kalimat status resmi di panel "Kenapa?"

Titipan W3 ("istilah Inggris elevation gain dan angka tanpa titik ribuan di kalimat mesin") dan W4-C3 ("kosakata mesin
di alasan Cek": "TUTUP", "Route fit", "Elevation gain"). Kalimat yang dirakit server (alasan rekomendasi, alasan Cek
kesiapan, penolakan, nama isian di pesan validasi, catatan audit, halaman publik jalur) masih memakai nama kolom
Inggris, nilai enum berhuruf besar, dan angka tanpa pemisah ribuan. Tugas ini mengganti istilahnya dengan kata yang
dipakai antarmuka ("tanjakan", "turunan", "kecocokan jalur"), menulis status lewat label enumnya
(`OfficialStatusValue::CLOSED->label()` menjadi "Tutup", bukan "TUTUP"), dan angka lewat `number_format(..., 0, ',', '.')`
(W9-R5). Nilai enum di props dan basis data tidak berubah. Titipan W9 dari tinjauan akhir W5 (pelacak program, "dua
kalimat status resmi UNKNOWN yang hampir sama di panel 'Kenapa?' Jelajah") ikut di sini karena kalimatnya juga dirakit
mesin: `RecommendationExplanationService::whatToWatch()` menambah baris status sendiri sesudah peringatan
`RouteFitService::peringatan()` yang sudah menyebut status itu, untuk Belum diketahui maupun Dibatasi. Baris itu
dihapus, sehingga kalimat status hanya datang dari pembentuk tunggalnya (W3-3c); ajakan memeriksa status resmi terbaru
tetap ada di `preparation_gap`. Parameter `$status` yang tidak terpakai lagi dilepas dari `build()` (W9-R23).

**Files:**
- Create: `tests/Feature/MachineVocabularyTest.php`
- Modify: `app/Enums/CompatibilityFactor.php`
- Modify: `app/Http/Controllers/Admin/TrailGeometryController.php`
- Modify: `app/Models/Trail.php`
- Modify: `app/Services/Admin/TrailDataHealth.php`
- Modify: `app/Services/ReadinessService.php`
- Modify: `app/Services/RecommendationExplanationService.php`
- Modify: `app/Services/RouteFit/CompatibilityScorer.php`
- Modify: `app/Services/RouteFitService.php`
- Modify: `lang/id/recommendation.php`
- Modify: `lang/id/validation.php`
- Modify: `resources/js/components/admin/FormulirJalur.tsx`
- Modify: `resources/js/components/community/FormKontribusi.tsx`
- Modify: `resources/js/components/discovery/KartuHasil.tsx`
- Modify: `resources/js/pages/Admin/TrailGeometry.tsx`
- Modify: `resources/js/pages/Goals/Create.tsx`
- Modify: `resources/js/pages/Recommendations/Show.tsx`
- Modify: `resources/views/public/trail.blade.php`
- Modify: `tests/Feature/IndonesianInterfaceTest.php`
- Modify: `tests/Feature/ReadinessDashboardTest.php`
- Modify: `tests/Feature/RefusalMessagesTest.php`
- Modify: `tests/Feature/TrailCardTest.php`
- Modify: `tests/Feature/TrailGeometryImportTest.php`

**Interfaces:**
- Consumes: `OfficialStatusValue::label()`, berkas bahasa `lang/id/validation.php` dan `lang/id/recommendation.php`.
- Produces: `RecommendationExplanationService::build(Trail $trail, array $factors, array $warnings): array` (parameter `$status` dilepas; pemanggil satu-satunya `RouteFitService::evaluate()`); teks kalimat mesin berubah (dijaga `MachineVocabularyTest` dan `TrailCardTest::test_the_why_panel_names_an_official_status_once`).

- [ ] **Step 1: Tulis test yang gagal.** Test baru `MachineVocabularyTest`, satu test baru di `TrailCardTest`, dan
empat test lama yang menyandikan kosakata lama diperbarui dengan alasan tertulis.

Buat `tests/Feature/MachineVocabularyTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\CompatibilityFactor;
use App\Enums\ExperienceLevel;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\ReadinessService;
use App\Services\RouteFit\CompatibilityScorer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Kalimat yang disusun mesin berbicara dengan kata layar (W9, sapuan yang ditunda W3-G2 dan
 * W4-C3). Tangkapan W3 memuat "Elevation gain 1650 m lebih besar dari referensi pengalaman
 * Anda (700 m)." dan Cek kesiapan W4 memuat "TUTUP" dan "Route fit": istilah Inggris, angka
 * tanpa titik ribuan, dan nilai enum yang tidak pernah tampil di tempat lain. Di layar, kata
 * itu "Tanjakan", angkanya "1.650 m", dan statusnya "Tutup" (OfficialStatusValue::label()).
 */
class MachineVocabularyTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_elevation_sentence_names_the_climb_and_groups_its_thousands(): void
    {
        $pendaki = User::factory()->create();
        $pendaki->profile()->create(['experience_level' => ExperienceLevel::BEGINNER->value, 'completed_at' => now()]);
        $pendaki->experience()->create(['completed_hikes_count' => 2, 'highest_elevation_gain_m' => 700]);
        $jalur = Trail::factory()->create(['elevation_gain_m' => 1650]);

        $faktor = collect(app(CompatibilityScorer::class)->score($pendaki->fresh(), null, $jalur, []))
            ->firstWhere('factor', CompatibilityFactor::ELEVATION_GAIN_MATCH);

        $this->assertSame('Tanjakan 1.650 m lebih besar dari referensi pengalaman Anda (700 m).', $faktor->detail);
        $this->assertSame('Kesesuaian tanjakan', CompatibilityFactor::ELEVATION_GAIN_MATCH->label());
    }

    public function test_a_closed_trail_is_explained_with_the_word_on_its_status_badge(): void
    {
        $jalur = Trail::factory()->published()->create();
        OfficialStatus::factory()->closed()->create(['statusable_id' => $jalur->id, 'statusable_type' => $jalur->getMorphClass()]);
        $trip = TripPlan::factory()->create(['trail_id' => $jalur->id]);

        $alasan = app(ReadinessService::class)->evaluate($trip->fresh())->explanation['reasons'];

        $this->assertContains('Status resmi jalur ini tercatat Tutup.', $alasan);
        $this->assertSame([], preg_grep('/\bTUTUP\b/', $alasan));
    }

    /**
     * Sapuan teks tampil: string PHP (tanpa komentar), sumber TSX tanpa komentar, view Blade tanpa
     * komentar Blade, dan berkas bahasa Indonesia.
     */
    public function test_no_screen_says_elevation_gain_or_shouts_a_status_value(): void
    {
        $pelanggar = [];

        foreach ($this->teksTampil() as $jalur => $teks) {
            if (preg_match('/elevation (?:gain|loss)/i', $teks)) {
                $pelanggar[] = $jalur.': elevation gain atau loss';
            }

            if (preg_match('/\b(?:TUTUP|BUKA|DIBATASI)\b/', $teks)) {
                $pelanggar[] = $jalur.': status berhuruf kapital';
            }

            if (str_contains($teks, 'Route fit')) {
                $pelanggar[] = $jalur.': Route fit';
            }
        }

        $this->assertSame([], $pelanggar);
    }

    public function test_numbers_in_machine_sentences_group_their_thousands(): void
    {
        $pelanggar = [];

        foreach (File::allFiles(app_path()) as $berkas) {
            if (preg_match_all("/sprintf\\('[^']*%d m\\b/", $berkas->getContents(), $cocok)) {
                $pelanggar[] = $berkas->getRelativePathname();
            }
        }

        $this->assertSame([], $pelanggar, 'Angka meter di kalimat ditulis dengan number_format(..., 0, \',\', \'.\'), bukan %d.');
    }

    /**
     * @return array<string, string>
     */
    private function teksTampil(): array
    {
        $hasil = [];

        foreach ([app_path(), lang_path('id')] as $akar) {
            foreach (File::allFiles($akar) as $berkas) {
                if ($berkas->getExtension() !== 'php') {
                    continue;
                }

                $string = '';

                foreach (token_get_all($berkas->getContents()) as $token) {
                    if (is_array($token) && in_array($token[0], [T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE], true)) {
                        $string .= $token[1]."\n";
                    }
                }

                $hasil[str_replace('\\', '/', $berkas->getRelativePathname())] = $string;
            }
        }

        foreach (File::allFiles(resource_path('js')) as $berkas) {
            if (in_array($berkas->getExtension(), ['ts', 'tsx'], true)) {
                $hasil['js/'.str_replace('\\', '/', $berkas->getRelativePathname())] = (string) preg_replace('#/\*.*?\*/|//[^\n]*|\{/\*.*?\*/\}#s', '', $berkas->getContents());
            }
        }

        foreach (File::allFiles(resource_path('views')) as $berkas) {
            $hasil['views/'.str_replace('\\', '/', $berkas->getRelativePathname())] = (string) preg_replace('#\{\{--.*?--\}\}|/\*.*?\*/#s', '', $berkas->getContents());
        }

        return $hasil;
    }
}
```

`tests/Feature/IndonesianInterfaceTest.php`:

1. Ganti:

```php
        $this->assertSame('Elevation gain (m) harus berupa bilangan bulat.', $pesan);
```

dengan:

```php
        // Nama isian memakai kata layar, "Tanjakan", sejak W9 (sapuan istilah W3-G2).
        $this->assertSame('Tanjakan (m) harus berupa bilangan bulat.', $pesan);
```

`tests/Feature/ReadinessDashboardTest.php`:

2. Ganti:

```php
     * kesiapan rencananya, bukan status administratif trip itu.
```

dengan:

```php
     * kesiapan rencananya, bukan status administratif trip itu. Sejak W9 statusnya ditulis
     * dengan kata lencananya, "Tutup", bukan nilai mesin "TUTUP" (ruling W4-C3).
```

3. Ganti:

```php
        $this->assertStringContainsString('TUTUP', $props['alasanTanpaTinjauan']);
```

dengan:

```php
        $this->assertStringContainsString('tercatat Tutup', $props['alasanTanpaTinjauan']);
```

`tests/Feature/RefusalMessagesTest.php`:

4. Ganti:

```php
    use RefreshDatabase;

    public function test_a_blocked_pre_departure_check_names_its_reasons(): void
```

dengan:

```php
    use RefreshDatabase;

    /**
     * Status ditulis dengan kata lencananya, "Tutup", sejak W9 (ruling W4-C3), bukan nilai
     * mesin "TUTUP".
     */
    public function test_a_blocked_pre_departure_check_names_its_reasons(): void
```

5. Ganti:

```php
                ->where('penolakan', fn (?string $pesan) => str_contains((string) $pesan, 'TUTUP')
```

dengan:

```php
                ->where('penolakan', fn (?string $pesan) => str_contains((string) $pesan, 'tercatat Tutup')
```

`tests/Feature/TrailCardTest.php`:

6. Ganti:

```php
    /**
     * T4-M9 dan T4-M6 tinjauan akhir W3: kartu tahu jalurnya tersimpan dan bercatatan (tombol
```

dengan:

```php
    /**
     * Titipan W9 (tinjauan akhir W5, Declined): panel "Kenapa?" menyebut status resmi yang belum
     * diketahui atau dibatasi dua kali, sekali dari peringatan mesin dan sekali dari baris status
     * penjelasan, dengan kalimat yang hampir sama. Kalimat status kini hanya datang dari
     * RouteFitService::peringatan() (W3-3c), jadi setiap status disebut sekali, di penjelasan
     * mesin maupun di dua kalimat pertama kartu.
     */
    public function test_the_why_panel_names_an_official_status_once(): void
    {
        $gunung = Mountain::factory()->create();
        $belumDiketahui = Trail::factory()->for($gunung)->published()->create();
        $dibatasi = Trail::factory()->for($gunung)->published()->create();
        OfficialStatus::factory()->restricted()->create([
            'statusable_type' => $dibatasi->getMorphClass(),
            'statusable_id' => $dibatasi->id,
            'source' => 'Balai Uji',
        ]);
        $pendaki = $this->pendaki();
        $kartu = collect($this->jelajah($pendaki)->viewData('page')['props']['trails']['data'])->keyBy('id');
        $sebutanStatus = fn (array $baris): int => count(array_filter($baris, fn (string $kalimat) => str_starts_with($kalimat, 'Status resmi jalur ini')));

        foreach ([$belumDiketahui, $dibatasi] as $jalur) {
            $mesin = app(RouteFitService::class)->evaluate($pendaki, null, $jalur->fresh());

            $this->assertSame(1, $sebutanStatus($mesin->explanation['what_to_watch']), $jalur->name);
            $this->assertSame(1, $sebutanStatus($kartu[$jalur->id]['fit']['perluDiperhatikan']), $jalur->name);
        }
    }

    /**
     * T4-M9 dan T4-M6 tinjauan akhir W3: kartu tahu jalurnya tersimpan dan bercatatan (tombol
```

`tests/Feature/TrailGeometryImportTest.php`:

7. Ganti:

```php
     * jejak audit sebagai taksiran, bukan angka resmi.
```

dengan:

```php
     * jejak audit sebagai taksiran, bukan angka resmi. Kabarnya memakai kata layar
     * "tanjakan" dan "turunan" sejak W9 (sapuan istilah W3-G2).
```

8. Ganti:

```php
            ->assertSessionHas('status', 'Elevation gain dan loss diperbarui dari berkas GPX.');
```

dengan:

```php
            ->assertSessionHas('status', 'Tanjakan dan turunan diperbarui dari berkas GPX.');
```

- [ ] **Step 2: Jalankan dan pastikan gagal.**

Run: `php artisan test --compact --filter='MachineVocabularyTest|ReadinessDashboardTest|RefusalMessagesTest|IndonesianInterfaceTest|TrailGeometryImportTest|TrailCardTest'`
Expected: FAIL; pada putaran ulang 97 test, 84 lulus, 9 gagal, 4 dilewati. Yang gagal: "Elevation gain (m)" di pesan validasi, "TUTUP" di penolakan, angka tanjakan
tanpa titik ribuan, dan status resmi yang disebut 2 kali di penjelasan mesin.

- [ ] **Step 3: Implementasi.**

`app/Enums/CompatibilityFactor.php`:

1. Ganti:

```php
            self::ELEVATION_GAIN_MATCH => 'Kesesuaian elevation gain',
```

dengan:

```php
            self::ELEVATION_GAIN_MATCH => 'Kesesuaian tanjakan',
```

`app/Http/Controllers/Admin/TrailGeometryController.php`:

2. Ganti:

```php
        return back(fallback: route('trails.geometry', $trail))->with('status', 'Elevation gain dan loss diperbarui dari berkas GPX.');
```

dengan:

```php
        return back(fallback: route('trails.geometry', $trail))->with('status', 'Tanjakan dan turunan diperbarui dari berkas GPX.');
```

`app/Models/Trail.php`:

3. Ganti:

```php
            $menunggu[] = 'Jarak, elevation gain, dan estimasi durasi';
```

dengan:

```php
            $menunggu[] = 'Jarak, tanjakan, dan estimasi durasi';
```

4. Ganti:

```php
            $missing[] = 'Karakteristik dasar belum lengkap (jarak, elevation gain, estimasi durasi).';
```

dengan:

```php
            $missing[] = 'Karakteristik dasar belum lengkap (jarak, tanjakan, estimasi durasi).';
```

`app/Services/Admin/TrailDataHealth.php`:

5. Ganti:

```php
                'label' => 'Karakteristik dasar belum lengkap (jarak, elevation gain, estimasi durasi)',
```

dengan:

```php
                'label' => 'Karakteristik dasar belum lengkap (jarak, tanjakan, estimasi durasi)',
```

`app/Services/ReadinessService.php`:

6. Ganti:

```php
                'Pada tanggal rencana Anda%s jalur ini tercatat TUTUP, meskipun hari ini tidak.',
                $tanggalRencana ? ' ('.$tanggalRencana->translatedFormat('d F Y').')' : ''
```

dengan:

```php
                'Pada tanggal rencana Anda%s jalur ini tercatat %s, meskipun hari ini tidak.',
                $tanggalRencana ? ' ('.$tanggalRencana->translatedFormat('j F Y').')' : '',
                OfficialStatusValue::CLOSED->label()
```

7. Ganti:

```php
            $reasons[] = 'Status resmi jalur ini tercatat TUTUP.';
```

dengan:

```php
            $reasons[] = 'Status resmi jalur ini tercatat '.OfficialStatusValue::CLOSED->label().'.';
```

8. Ganti:

```php
                    'official_status_closed' => 'Jalur tidak tersedia karena status resmi TUTUP.',
```

dengan:

```php
                    'official_status_closed' => 'Jalur tidak tersedia karena status resminya '.OfficialStatusValue::CLOSED->label().'.',
```

9. Ganti:

```php
                    'elevation_gain_above_goal_limit' => 'Elevation gain jalur melebihi batas yang Anda tentukan.',
```

dengan:

```php
                    'elevation_gain_above_goal_limit' => 'Tanjakan jalur melebihi batas yang Anda tentukan.',
```

10. Ganti:

```php
            $reasons[] = 'Route fit, persiapan, status resmi, dan kondisi terkini saat ini tidak menunjukkan isu yang belum selesai.';
```

dengan:

```php
            $reasons[] = 'Kecocokan jalur, persiapan, status resmi, dan kondisi terkini saat ini tidak menunjukkan isu yang belum selesai.';
```

`app/Services/RecommendationExplanationService.php`:

11. Ganti:

```php
namespace App\Services;

use App\Enums\OfficialStatusValue;
use App\Models\Trail;
```

dengan:

```php
namespace App\Services;

use App\Models\Trail;
```

12. Ganti:

```php
    public function build(Trail $trail, array $factors, array $warnings, OfficialStatusValue $status): array
    {
        return [
            'why_it_fits' => $this->whyItFits($factors),
            'what_to_watch' => $this->whatToWatch($trail, $factors, $warnings, $status),
```

dengan:

```php
    public function build(Trail $trail, array $factors, array $warnings): array
    {
        return [
            'why_it_fits' => $this->whyItFits($factors),
            'what_to_watch' => $this->whatToWatch($trail, $factors, $warnings),
```

13. Ganti:

```php
    }

    /**
     * @param  array<int, FactorScore>  $factors
     * @param  array<int, string>  $warnings
```

dengan:

```php
    }

    /**
     * Kalimat status resmi (belum diketahui, dibatasi) datang dari $warnings, yang dibentuk
     * RouteFitService::peringatan() (W3-3c). Baris status sendiri di sini dulu menyebut status
     * yang sama untuk kedua kalinya di panel "Kenapa?" (titipan W9 dari tinjauan akhir W5);
     * ajakan memeriksa status resmi terbaru tetap ada di preparation_gap.
     *
     * @param  array<int, FactorScore>  $factors
     * @param  array<int, string>  $warnings
```

14. Ganti:

```php
    private function whatToWatch(Trail $trail, array $factors, array $warnings, OfficialStatusValue $status): array
```

dengan:

```php
    private function whatToWatch(Trail $trail, array $factors, array $warnings): array
```

15. Ganti:

```php
        if ($status === OfficialStatusValue::UNKNOWN) {
            $lines[] = 'Status resmi jalur ini belum diketahui. Periksa ke pengelola sebelum berangkat.';
        }

        if ($status === OfficialStatusValue::RESTRICTED) {
            $lines[] = 'Status resmi jalur ini sedang dibatasi. Periksa ketentuan yang berlaku.';
        }

        if (($trail->elevation_gain_m ?? 0) >= 1200) {
            $lines[] = sprintf('Elevation gain jalur ini tinggi (%d m).', $trail->elevation_gain_m);
```

dengan:

```php
        if (($trail->elevation_gain_m ?? 0) >= 1200) {
            $lines[] = 'Tanjakan jalur ini tinggi ('.number_format($trail->elevation_gain_m, 0, ',', '.').' m).';
```

16. Ganti:

```php
                    'elevation_gain_match' => 'Siapkan kondisi fisik untuk elevation gain yang lebih besar dari pengalaman Anda sebelumnya.',
```

dengan:

```php
                    'elevation_gain_match' => 'Siapkan kondisi fisik untuk tanjakan yang lebih besar dari pengalaman Anda sebelumnya.',
```

`app/Services/RouteFit/CompatibilityScorer.php`:

17. Ganti:

```php
            ! $demand['known'] => 'Karakteristik fisik jalur (jarak, elevation gain, estimasi durasi) '
```

dengan:

```php
            ! $demand['known'] => 'Karakteristik fisik jalur (jarak, tanjakan, estimasi durasi) '
```

18. Ganti:

```php
                'Data elevation gain jalur ini belum tersedia.',
```

dengan:

```php
                'Data tanjakan jalur ini belum tersedia.',
```

19. Ganti:

```php
            ? sprintf('Elevation gain %d m masih sebanding dengan pengalaman Anda.', $gain)
            : sprintf('Elevation gain %d m lebih besar dari referensi pengalaman Anda (%d m).', $gain, $reference);
```

dengan:

```php
            ? sprintf('Tanjakan %s m masih sebanding dengan pengalaman Anda.', number_format($gain, 0, ',', '.'))
            : sprintf('Tanjakan %s m lebih besar dari referensi pengalaman Anda (%s m).', number_format($gain, 0, ',', '.'), number_format($reference, 0, ',', '.'));
```

`app/Services/RouteFitService.php`:

20. Ganti:

```php
            explanation: $this->explanations->build($trail, $factors, $warnings, $status),
```

dengan:

```php
            explanation: $this->explanations->build($trail, $factors, $warnings),
```

`lang/id/recommendation.php`:

21. Ganti:

```php
    'official_status_closed' => 'Status resmi jalur ini tercatat TUTUP.',
```

dengan:

```php
    'official_status_closed' => 'Status resmi jalur ini tercatat Tutup.',
```

22. Ganti:

```php
    'elevation_gain_above_goal_limit' => 'Elevation gain jalur melebihi batas yang Anda tentukan.',
```

dengan:

```php
    'elevation_gain_above_goal_limit' => 'Tanjakan jalur melebihi batas yang Anda tentukan.',
```

`lang/id/validation.php`:

23. Ganti:

```php
        'elevation_gain_m' => 'Elevation gain (m)',
        'elevation_loss_m' => 'Elevation loss (m)',
```

dengan:

```php
        'elevation_gain_m' => 'Tanjakan (m)',
        'elevation_loss_m' => 'Turunan (m)',
```

24. Ganti:

```php
        'highest_elevation_gain_m' => 'Elevation gain tertinggi yang pernah ditempuh',
```

dengan:

```php
        'highest_elevation_gain_m' => 'Tanjakan tertinggi yang pernah ditempuh',
```

25. Ganti:

```php
        'max_elevation_gain_m' => 'Elevation gain maksimum',
        'max_elevation_gain_preference_m' => 'Preferensi elevation gain maksimum',
```

dengan:

```php
        'max_elevation_gain_m' => 'Tanjakan maksimum',
        'max_elevation_gain_preference_m' => 'Preferensi tanjakan maksimum',
```

`resources/js/components/admin/FormulirJalur.tsx`:

26. Ganti:

```tsx
                <FormField name="elevation_gain_m" type="number" label="Elevation gain (m)" value={data.elevation_gain_m} onChange={teks('elevation_gain_m')} error={errors.elevation_gain_m} />
                <FormField
                    name="elevation_loss_m"
                    type="number"
                    label="Elevation loss (m)"
```

dengan:

```tsx
                <FormField name="elevation_gain_m" type="number" label="Tanjakan (m)" value={data.elevation_gain_m} onChange={teks('elevation_gain_m')} error={errors.elevation_gain_m} />
                <FormField
                    name="elevation_loss_m"
                    type="number"
                    label="Turunan (m)"
```

`resources/js/components/community/FormKontribusi.tsx`:

27. Ganti:

```tsx
                    <FormField name="elevation_gain_m" type="number" label="Elevation gain (m)" value={form.data.elevation_gain_m} onChange={(e) => form.setData('elevation_gain_m', e.target.value)} error={form.errors.elevation_gain_m} />
                    <FormField name="elevation_loss_m" type="number" label="Elevation loss (m)" value={form.data.elevation_loss_m} onChange={(e) => form.setData('elevation_loss_m', e.target.value)} error={form.errors.elevation_loss_m} />
```

dengan:

```tsx
                    <FormField name="elevation_gain_m" type="number" label="Tanjakan (m)" value={form.data.elevation_gain_m} onChange={(e) => form.setData('elevation_gain_m', e.target.value)} error={form.errors.elevation_gain_m} />
                    <FormField name="elevation_loss_m" type="number" label="Turunan (m)" value={form.data.elevation_loss_m} onChange={(e) => form.setData('elevation_loss_m', e.target.value)} error={form.errors.elevation_loss_m} />
```

`resources/js/components/discovery/KartuHasil.tsx`:

28. Ganti:

```tsx
                    <dt className="text-muted">Elevation gain</dt>
```

dengan:

```tsx
                    <dt className="text-muted">Tanjakan</dt>
```

`resources/js/pages/Admin/TrailGeometry.tsx`:

29. Ganti:

```tsx
                                    konfirmasiLalu('Ganti elevation gain dan loss jalur ini dengan taksiran dari berkas GPX?', () => kirim('tanjakan', elevation.url({ trail: trail.id })))
```

dengan:

```tsx
                                    konfirmasiLalu('Ganti tanjakan dan turunan jalur ini dengan taksiran dari berkas GPX?', () => kirim('tanjakan', elevation.url({ trail: trail.id })))
```

30. Ganti:

```tsx
                            <dt className="text-xs text-muted">Taksiran elevation gain</dt>
```

dengan:

```tsx
                            <dt className="text-xs text-muted">Taksiran tanjakan</dt>
```

31. Ganti:

```tsx
                            <dt className="text-xs text-muted">Taksiran elevation loss</dt>
```

dengan:

```tsx
                            <dt className="text-xs text-muted">Taksiran turunan</dt>
```

32. Ganti:

```tsx
                <p className="text-sm text-secondary">Berkas ini tidak membawa data ketinggian, jadi profil elevasi dan taksiran elevation gain tidak dapat dihitung darinya.</p>
```

dengan:

```tsx
                <p className="text-sm text-secondary">Berkas ini tidak membawa data ketinggian, jadi profil elevasi dan taksiran tanjakan tidak dapat dihitung darinya.</p>
```

`resources/js/pages/Goals/Create.tsx`:

33. Ganti:

```tsx
                        label="Batas elevation gain (meter, opsional)"
                        hint="Jalur dengan elevation gain di atas batas ini tidak akan masuk rekomendasi."
```

dengan:

```tsx
                        label="Batas tanjakan (meter, opsional)"
                        hint="Jalur dengan tanjakan di atas batas ini tidak akan masuk rekomendasi."
```

`resources/js/pages/Recommendations/Show.tsx`:

34. Ganti:

```tsx
                                pihak yang berwenang, jadi tidak ada yang dapat diuji terhadap rencana Anda. Mengubah durasi atau batas elevation gain tidak akan
```

dengan:

```tsx
                                pihak yang berwenang, jadi tidak ada yang dapat diuji terhadap rencana Anda. Mengubah durasi atau batas tanjakan tidak akan
```

35. Ganti:

```tsx
                                Tidak ada jalur yang memenuhi batasan rencana Anda saat ini. Anda dapat melonggarkan target durasi atau batas elevation gain, atau
```

dengan:

```tsx
                                Tidak ada jalur yang memenuhi batasan rencana Anda saat ini. Anda dapat melonggarkan target durasi atau batas tanjakan, atau
```

`resources/views/public/trail.blade.php`:

36. Ganti:

```blade
        .((float) $trail->distance_km ? 'Jarak '.$trail->distance_km.' km. ' : '')
        .($trail->elevation_gain_m ? 'Elevation gain '.number_format($trail->elevation_gain_m, 0, ',', '.').' m. ' : '')
```

dengan:

```blade
        .((float) $trail->distance_km ? 'Jarak '.number_format((float) $trail->distance_km, 1, ',', '.').' km. ' : '')
        .($trail->elevation_gain_m ? 'Tanjakan '.number_format($trail->elevation_gain_m, 0, ',', '.').' m. ' : '')
```

37. Ganti:

```blade
                        <dd class="font-medium text-primary">{{ $trail->distance_km ?? '-' }} km</dd>
                    </div>
                    <div>
                        <dt class="text-muted">Elevation gain</dt>
```

dengan:

```blade
                        <dd class="font-medium text-primary">{{ $trail->distance_km !== null ? number_format((float) $trail->distance_km, 1, ',', '.') : '-' }} km</dd>
                    </div>
                    <div>
                        <dt class="text-muted">Tanjakan</dt>
```

Varian repo nyata (Pra-cek butir 2). Bila `MachineVocabularyTest::test_no_screen_says_elevation_gain_or_shouts_a_status_value`
menunjuk berkas repo nyata yang tidak ada di simulasi (8 Oktober: `ProfileSetupController.php`, `HikeSummary.tsx`,
`HikerProgress.tsx`, `Onboarding/ProfileSetup.tsx`, dan `GrafikTanjakan.tsx`, yang ditulis ulang rencana W7), ganti
istilah di teks yang dibaca pendaki dengan "tanjakan" atau "turunan" seperti blok di atas, tanpa mengubah nama kolom,
kunci isian, atau props. Pola lain dilaporkan NEEDS_CONTEXT.

- [ ] **Step 4: Jalankan dan pastikan lulus.**

Run: `php artisan test --compact --filter='MachineVocabularyTest|ReadinessDashboardTest|RefusalMessagesTest|IndonesianInterfaceTest|TrailGeometryImportTest|TrailCardTest|RouteFitUnknownDataTest|TrailDetailPageTest'`
Expected: PASS; pada putaran ulang 129 test, 125 lulus, 4 dilewati.

- [ ] **Step 5: Format, build, dan suite penuh.**

Run:
```bash
vendor/bin/pint --dirty --format agent
DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build
node_modules/.bin/tsc --noEmit
php artisan test --compact --testsuite=Unit,Feature
```
Expected: Pint lulus, build sukses, `tsc` tanpa galat, suite 2.280 test, 2.278 lulus, 2 dilewati (sebelum tugas: 2.275).

- [ ] **Step 6: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 4: Satu pengumuman per aksi simpan, kontras ruas kosong, target remah, dan kolom aksi 1440 px

Empat titipan W3 dan W4 yang berwujud di layar. (1) Pengumuman tunggal pembaca layar (W3 M7): menyimpan dari kartu
Jelajah diumumkan dua kali, oleh toast Urungkan (`aria-live`) dan oleh wilayah `role="status"` halaman. Klien kini
mengirim `dengan_toast` bila ia sendiri menampilkan toast, dan server mengosongkan kabar halaman untuk aksi itu (W9-R6).
Kabar halaman tetap ada untuk jalur yang sudah tersimpan dan untuk penyimpan tanpa toast. (2) Kontras ruas kosong
langkah trip (W4-6h OOS7): batas ruas kosong memakai `border-control` 3:1, bukan `border-subtle`. (3) Tautan remah
gunung di rincian jalur 94 x 18 px (W3; W8 Tugas 18) kini `min-h-11`. (4) Kolom aksi kanan rincian jalur di 1440 px
membungkus (W3): kini dua kolom dengan "Rencanakan trip" satu baris penuh.

**Files:**
- Create: `tests/Feature/ScreenReaderAndTargetTest.php`
- Modify: `app/Http/Controllers/Trails/ConsiderationController.php`
- Modify: `app/Http/Requests/Trails/StoreSavedTrailRequest.php`
- Modify: `resources/js/components/beranda/KartuPerjalanan.tsx`
- Modify: `resources/js/components/trails/TombolSimpan.tsx`
- Modify: `resources/js/pages/Trails/Show.tsx`
- Modify: `resources/js/pages/Trips/Preparation.tsx`
- Modify: `resources/js/pages/Trips/Readiness.tsx`
- Modify: `tests/Feature/SavedUndoTest.php`
- Create (hanya varian repo nyata, Step 1): `tests/Feature/FlashKeyPairTest.php`

**Interfaces:**
- Consumes: `StoreSavedTrailRequest`, `ConsiderationController::simpan()` dan `hapus()` (W3), `TombolSimpan` (W3).
- Produces: isian opsional `dengan_toast` (boolean) pada `POST` simpan dan `DELETE` hapus simpanan; flash `status` kosong untuk aksi yang diumumkan toast.

- [ ] **Step 1: Tulis test yang gagal.** Test baru `ScreenReaderAndTargetTest`; `SavedUndoTest` diperbarui karena
klien kini mengirim `dengan_toast`.

Buat `tests/Feature/ScreenReaderAndTargetTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Mountain;
use App\Models\Trail;
use App\Models\TrailConsideration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Sapuan aksesibilitas W9 atas butir yang ditunda W3 dan W4.
 *
 * - Pengumuman tunggal (T4-M7, T7-M10 tinjauan akhir W3): menyimpan atau melepas jalur dengan
 *   toast Urungkan diumumkan dua kali, sekali oleh toast (aria-live) dan sekali oleh wilayah
 *   role="status" halaman dari flash `pengumuman`. Kini aksi yang menampilkan toast meminta
 *   server tidak menulis pengumumannya; hasil yang tidak memunculkan toast (sudah ada, sudah
 *   tidak ada, Urungkan) tetap diumumkan halaman. Satu kalimat per kejadian.
 * - Ruas kosong hitungan butir kritis (W4-6h OOS7) memakai garis batas kendali yang 3:1 terhadap
 *   kartu di kedua tema, bukan isian sumur yang hampir tak terlihat.
 * - Tautan gunung di kepala rincian jalur dan aksi kolom kanan 1440 px (W3, W8).
 *
 * Bunyi pembaca layar sendiri butuh telinga manusia (docs/PROTOKOL-UJI-KEGUNAAN.md bagian 4);
 * test ini menjaga sumber kejadiannya.
 */
class ScreenReaderAndTargetTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_save_announced_by_its_undo_toast_is_not_announced_again_by_the_page(): void
    {
        $pendaki = User::factory()->create();
        $jalur = $this->jalur();

        $this->actingAs($pendaki)->post(route('trails.saved.store', $jalur), ['dengan_toast' => true])
            ->assertRedirect()
            ->assertSessionMissing('pengumuman');

        $this->assertDatabaseHas('trail_considerations', ['user_id' => $pendaki->id, 'trail_id' => $jalur->id]);
    }

    public function test_a_save_without_a_toast_is_still_announced_by_the_page(): void
    {
        $pendaki = User::factory()->create();
        $jalur = $this->jalur();

        $this->actingAs($pendaki)->post(route('trails.saved.store', $jalur))
            ->assertSessionHas('pengumuman', 'Gede via Cibodas disimpan.');
    }

    /**
     * Baris yang sudah ada tidak memunculkan toast (ruling W3-4b), jadi halaman yang mengumumkan.
     */
    public function test_an_already_saved_trail_is_announced_by_the_page_even_when_a_toast_was_expected(): void
    {
        $pendaki = User::factory()->create();
        $jalur = $this->jalur();
        TrailConsideration::create(['user_id' => $pendaki->id, 'trail_id' => $jalur->id]);

        $this->actingAs($pendaki)->post(route('trails.saved.store', $jalur), ['dengan_toast' => true])
            ->assertSessionHas('pengumuman', 'Gede via Cibodas sudah ada di simpanan.')
            ->assertSessionHas('simpanan-sudah-ada', true);
    }

    public function test_a_removal_announced_by_its_undo_toast_is_not_announced_again_by_the_page(): void
    {
        $pendaki = User::factory()->create();
        $jalur = $this->jalur();
        TrailConsideration::create(['user_id' => $pendaki->id, 'trail_id' => $jalur->id]);

        $this->actingAs($pendaki)->delete(route('trails.saved.remove', $jalur), ['dengan_toast' => true])
            ->assertSessionMissing('pengumuman')
            ->assertSessionHas('simpanan-terhapus', ['trailId' => $jalur->id, 'nama' => 'Gede via Cibodas', 'catatan' => null]);

        $this->actingAs($pendaki)->delete(route('trails.saved.remove', $jalur), ['dengan_toast' => true])
            ->assertSessionHas('pengumuman', 'Jalur ini sudah tidak ada di simpanan.');
    }

    /**
     * Tombol simpan meminta pengumuman tunggal hanya untuk aksi yang memang menampilkan toast:
     * simpan biasa dan lepas simpanan. Urungkan (simpan dengan urungkan false, dan hapus dari
     * toast simpan) tidak memunculkan toast, jadi halaman yang mengumumkannya.
     */
    public function test_the_save_button_asks_for_a_single_announcer_only_when_it_shows_a_toast(): void
    {
        $sumber = File::get(resource_path('js/components/trails/TombolSimpan.tsx'));

        $this->assertStringContainsString('{ catatan, dengan_toast: urungkan }', $sumber);
        $this->assertStringContainsString("router.delete(remove.url({ trail: jalur.id }), {\n            ...opsi,\n            data: { dengan_toast: true },", $sumber);
        $this->assertStringContainsString('() => router.delete(remove.url({ trail: jalur.id }), opsi)', $sumber, 'Urungkan simpan tidak memunculkan toast, jadi pengumumannya tetap milik halaman.');
    }

    public function test_empty_progress_segments_are_outlined_with_the_control_border(): void
    {
        foreach (['js/components/beranda/KartuPerjalanan.tsx', 'js/pages/Trips/Preparation.tsx', 'js/pages/Trips/Readiness.tsx'] as $berkas) {
            $sumber = File::get(resource_path($berkas));

            $this->assertMatchesRegularExpression("/'h-2 flex-1 rounded-full', i < \\w+\\.dikonfirmasi \\? 'bg-brand-700' : 'border border-control'/", $sumber, $berkas);
            $this->assertStringNotContainsString("'bg-brand-700' : 'bg-surface-sunken'", $sumber, $berkas);
        }
    }

    public function test_the_mountain_link_in_the_trail_header_is_a_full_touch_target(): void
    {
        $sumber = File::get(resource_path('js/pages/Trails/Show.tsx'));

        $this->assertMatchesRegularExpression('/<Tautan href=\{trail\.mountainUrl\} className="inline-flex min-h-11 items-center /', $sumber);
    }

    /**
     * Kolom kanan 22rem (352 px) memuat tiga tombol yang membungkus tanpa sengaja (W3-G2). Kini
     * aksi utama "Rencanakan trip" berdiri sendiri selebar kolom, lalu simpan dan Banding
     * berdampingan, sehingga susunannya sama apa pun panjang labelnya.
     */
    public function test_the_wide_column_puts_the_main_action_on_its_own_row(): void
    {
        $sumber = File::get(resource_path('js/pages/Trails/Show.tsx'));

        $this->assertStringContainsString('<Button href={rencanaUrl} className="col-span-2">', $sumber);
        $this->assertStringContainsString('<div className="grid grid-cols-2 gap-2">{aksiLebar}</div>', $sumber);
    }

    private function jalur(): Trail
    {
        return Trail::factory()->for(Mountain::factory()->create())->published()->create(['name' => 'Gede via Cibodas']);
    }
}
```

`tests/Feature/SavedUndoTest.php`:

1. Ganti:

```php
     * bertanya dulu sebelum melepas simpanan yang bercatatan.
```

dengan:

```php
     * bertanya dulu sebelum melepas simpanan yang bercatatan. Sejak W9 simpan juga mengirim
     * dengan_toast (pengumuman tunggal, ScreenReaderAndTargetTest).
```

2. Ganti:

```php
        $this->assertStringContainsString('router.post(store.url({ trail: jalur.id }), { catatan }', $tombol);
```

dengan:

```php
        $this->assertStringContainsString('router.post(store.url({ trail: jalur.id }), { catatan, dengan_toast: urungkan }', $tombol);
```

Varian repo nyata, tidak disimulasikan: titipan W4 "test pasangan kunci flash" (nit tinjauan residu W4: penjaga tabrakan
prop hanya memindai prop tingkat atas). Kunci `kabar-aksi` dan prop bersama `flash.kabarAksi` dibuat gelombang perbaikan
akhir W4 (ruling W4-FK) dan ada di repo nyata, tetapi tidak ada di dasar simulasi, jadi test di bawah tidak diputar
ulang. Pra-cek butir 3 memastikan kedua jangkarnya. Buat berkasnya dengan
`DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:test --phpunit --no-interaction FlashKeyPairTest`,
lalu ganti isinya persis dengan blok berikut (berkas `tests/Feature/FlashKeyPairTest.php`):

```php
<?php

namespace Tests\Feature;

use App\Models\TripPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Dua kunci flash Persiapan sampai sebagai dua kabar terpisah (titipan W4 untuk W9; ruling W4-FK).
 *
 * Kabar kedatangan ("Rencana trip tersimpan.") memakai kunci status dan dibacakan dekat kepala
 * halaman; kabar aksi di halaman (sakelar pengingat, Perbarui daftar) memakai kunci kabar-aksi dan
 * dibacakan dekat kendalinya. Penjaga tabrakan prop yang ada hanya memindai prop tingkat atas, jadi
 * pasangan di dalam flash dijaga di sini: keduanya sampai terpisah tanpa saling menimpa, dan hanya
 * Persiapan yang membaca kabar aksi, sehingga kabar itu tidak pernah dibacakan dua kali.
 */
class FlashKeyPairTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_arrival_message_and_an_action_message_reach_the_page_apart(): void
    {
        $trip = TripPlan::factory()->create();

        $this->actingAs($trip->user)
            ->withSession(['status' => 'Rencana trip tersimpan.', 'kabar-aksi' => 'Daftar persiapan diperbarui mengikuti karakteristik jalur.'])
            ->get(route('trips.preparation', $trip))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('flash.status', 'Rencana trip tersimpan.')
                ->where('flash.kabarAksi', 'Daftar persiapan diperbarui mengikuti karakteristik jalur.'));
    }

    public function test_only_the_preparation_page_reads_the_action_message(): void
    {
        $pembaca = [];

        foreach (File::allFiles(resource_path('js')) as $berkas) {
            if (str_contains($berkas->getContents(), 'flash.kabarAksi')) {
                $pembaca[] = str_replace('\\', '/', $berkas->getRelativePathname());
            }
        }

        $this->assertSame(['pages/Trips/Preparation.tsx'], $pembaca);
    }
}
```

Run: `php artisan test --compact --filter=FlashKeyPairTest`
Expected di repo nyata: 2 test lulus sejak dibuat (penjaga, bukan perbaikan). Bila `test_only_the_preparation_page_reads_the_action_message`
menemukan pembaca lain yang ditambah W5 sampai W8, tambahkan berkas itu ke daftar hanya bila halamannya memang
membacakan kabar aksi di wilayahnya sendiri dan tidak menampilkan `flash.status` yang sama; bila tidak, laporkan
NEEDS_CONTEXT.

- [ ] **Step 2: Jalankan dan pastikan gagal.**

Run: `php artisan test --compact --filter='ScreenReaderAndTargetTest|SavedUndoTest'`
Expected: FAIL; pada putaran ulang 27 test, 20 lulus, 7 gagal. Yang gagal: kabar halaman masih terisi untuk simpan bertoast, ruas kosong masih `border-subtle`,
tautan remah tanpa `min-h-11`, kolom kanan tanpa baris penuh.

- [ ] **Step 3: Implementasi.**

`app/Http/Controllers/Trails/ConsiderationController.php`:

1. Ganti:

```php
        $nama = Trail::query()->whereKey($trail)->value('name');

        return back()
            ->with('pengumuman', $nama.' '.Str::lcfirst(ConsiderationOutcome::DIHAPUS->label()).'.')
            ->with('simpanan-terhapus', ['trailId' => $trail, 'nama' => $nama, 'catatan' => $catatan]);
```

dengan:

```php
        $nama = Trail::query()->whereKey($trail)->value('name');
        $kembali = back()->with('simpanan-terhapus', ['trailId' => $trail, 'nama' => $nama, 'catatan' => $catatan]);

        // Toast Urungkan klien sudah mengumumkan penghapusan ini; satu kejadian, satu
        // pengumuman (T4-M7 dan T7-M10 tinjauan akhir W3, W9).
        return $request->boolean('dengan_toast')
            ? $kembali
            : $kembali->with('pengumuman', $nama.' '.Str::lcfirst(ConsiderationOutcome::DIHAPUS->label()).'.');
```

2. Ganti:

```php
        return back()->with('pengumuman', $jalur->name.' '.Str::lcfirst(ConsiderationOutcome::DITAMBAHKAN->label()).'.');
```

dengan:

```php
        // Toast Urungkan klien sudah mengumumkan simpanan ini (W9, pengumuman tunggal).
        return $request->boolean('dengan_toast')
            ? back()
            : back()->with('pengumuman', $jalur->name.' '.Str::lcfirst(ConsiderationOutcome::DITAMBAHKAN->label()).'.');
```

`app/Http/Requests/Trails/StoreSavedTrailRequest.php`:

3. Ganti:

```php
 * catatan biasa.
```

dengan:

```php
 * catatan biasa. `dengan_toast` hanya memberi tahu bahwa klien akan menampilkan toast Urungkan,
 * supaya halaman tidak mengumumkan kejadian yang sama untuk kedua kalinya (W9).
```

4. Ganti:

```php
            'catatan' => ['nullable', 'string', 'max:'.TrailConsideration::CATATAN_MAKS],
```

dengan:

```php
            'catatan' => ['nullable', 'string', 'max:'.TrailConsideration::CATATAN_MAKS],
            'dengan_toast' => ['sometimes', 'boolean'],
```

`resources/js/components/beranda/KartuPerjalanan.tsx`:

5. Ganti:

```tsx
                                <span key={i} className={kelas('h-2 flex-1 rounded-full', i < persiapan.dikonfirmasi ? 'bg-brand-700' : 'bg-surface-sunken')} />
```

dengan:

```tsx
                                <span key={i} className={kelas('h-2 flex-1 rounded-full', i < persiapan.dikonfirmasi ? 'bg-brand-700' : 'border border-control')} />
```

`resources/js/components/trails/TombolSimpan.tsx`:

6. Ganti:

```tsx
        router.post(store.url({ trail: jalur.id }), { catatan }, {
```

dengan:

```tsx
        // dengan_toast: toast Urungkan di bawah yang mengumumkan simpanan ini, bukan wilayah
        // role="status" halaman (W9, pengumuman tunggal). Urungkan sendiri tanpa toast.
        router.post(store.url({ trail: jalur.id }), { catatan, dengan_toast: urungkan }, {
```

7. Ganti:

```tsx
        router.delete(remove.url({ trail: jalur.id }), {
            ...opsi,
            onFinish: () => setMemproses(false),
```

dengan:

```tsx
        router.delete(remove.url({ trail: jalur.id }), {
            ...opsi,
            data: { dengan_toast: true },
            onFinish: () => setMemproses(false),
```

`resources/js/pages/Trails/Show.tsx`:

8. Ganti:

```tsx
    const aksiLebar = (
        <>
```

dengan:

```tsx
    // Kolom kanan 352 px: aksi utama berdiri sendiri selebar kolom, lalu simpan dan Banding
    // berdampingan; tiga tombol sebaris membungkus tanpa sengaja di 1440 px (W3-G2, W9).
    const aksiLebar = (
        <>
            <Button href={rencanaUrl} className="col-span-2">
                Rencanakan trip
            </Button>
```

9. Ganti:

```tsx
            ) : null}
            <Button href={rencanaUrl}>Rencanakan trip</Button>
```

dengan:

```tsx
            ) : null}
```

10. Ganti:

```tsx
                            <Tautan href={trail.mountainUrl} className="font-bold text-brand-ink underline underline-offset-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
```

dengan:

```tsx
                            <Tautan href={trail.mountainUrl} className="inline-flex min-h-11 items-center font-bold text-brand-ink underline underline-offset-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
```

11. Ganti:

```tsx
                            <div className="flex flex-wrap gap-2">{aksiLebar}</div>
```

dengan:

```tsx
                            <div className="grid grid-cols-2 gap-2">{aksiLebar}</div>
```

`resources/js/pages/Trips/Preparation.tsx`:

12. Ganti:

```tsx
                                    <span key={i} className={kelas('h-2 flex-1 rounded-full', i < kritis.dikonfirmasi ? 'bg-brand-700' : 'bg-surface-sunken')} />
```

dengan:

```tsx
                                    <span key={i} className={kelas('h-2 flex-1 rounded-full', i < kritis.dikonfirmasi ? 'bg-brand-700' : 'border border-control')} />
```

`resources/js/pages/Trips/Readiness.tsx`:

13. Ganti:

```tsx
                                            <span key={i} className={kelas('h-2 flex-1 rounded-full', i < persiapan.dikonfirmasi ? 'bg-brand-700' : 'bg-surface-sunken')} />
```

dengan:

```tsx
                                            <span key={i} className={kelas('h-2 flex-1 rounded-full', i < persiapan.dikonfirmasi ? 'bg-brand-700' : 'border border-control')} />
```

- [ ] **Step 4: Jalankan dan pastikan lulus.**

Run: `php artisan test --compact --filter='ScreenReaderAndTargetTest|SavedUndoTest'`
Expected: PASS; pada putaran ulang 27 test, 27 lulus.

- [ ] **Step 5: Format, build, dan suite penuh.**

Run:
```bash
vendor/bin/pint --dirty --format agent
DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build
node_modules/.bin/tsc --noEmit
php artisan test --compact --testsuite=Unit,Feature
```
Expected: Pint lulus, build sukses, `tsc` tanpa galat, suite 2.288 test, 2.286 lulus, 2 dilewati (sebelum tugas: 2.280).

- [ ] **Step 6: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 5: Sesi dan batas laju setiap rute tulis, proxy tepercaya, dan sisa titipan keamanan W3

Tinjauan keamanan W9 (`CLAUDE.md` bagian 4, rate limiting dan auth bypass) dan titipan W3: `trustProxies` (T8-M2),
kedaluwarsa penanda pemulihan sesi (C1 tinjauan akhir), dan satu sumber lebar layar klien (N6, W3-F7). Test penjaga
baru menyapu setiap rute tulis: harus di belakang sesi (kecuali pintu tamu yang memang membuka sesi) dan harus berbatas
laju atau tercatat alasannya. Sapuan itu menemukan celah nyata yang ditutup di sini: profil, sandi, preferensi
rekaman, hapus akun, onboarding, pilih rekomendasi, dan unggah serta hapus jejak tanpa batas laju; pendaftaran dan
permintaan tautan atur ulang sandi tanpa batas per alamat; dan rute `storage/{path}` bertanda tangan bawaan Laravel
yang terdaftar karena disk lokal ber-`serve` (W9-R7). Proxy tepercaya dibaca dari `TRUSTED_PROXIES` yang kosong secara
bawaan (W9-R8). Penanda pemulihan Simpanan menjadi satu kunci sesi berumur 600 detik (W9-R9). Kueri media 1280 px di
Jelajah pindah ke `resources/js/lib/layar.ts` yang dicocokkan dengan tema Tailwind (W9-R10).

**Files:**
- Create: `config/trustedproxy.php`
- Create: `resources/js/lib/layar.ts`
- Create: `tests/Feature/BreakpointSourceTest.php`
- Create: `tests/Feature/SavedRestoreMarkerTest.php`
- Create: `tests/Feature/TrustedProxyTest.php`
- Create: `tests/Feature/WriteRouteGuardTest.php`
- Modify: `.env.example`
- Modify: `app/Http/Controllers/Trails/ConsiderationController.php`
- Modify: `config/filesystems.php`
- Modify: `resources/js/pages/Trails/Index.tsx`
- Modify: `routes/auth.php`
- Modify: `routes/web.php`
- Modify: `tests/Feature/TrailIndexMapTest.php`

**Interfaces:**
- Consumes: `config('hiking.rate_limits.personal_writes_per_minute')` dan kunci laju yang sudah ada (`persiapan-butir`, `persiapan-daftar`, `siklus-trip`, `meja-kurator`, dan lainnya); middleware `TrustProxies` Laravel yang membaca `config('trustedproxy.proxies')`.
- Produces: `config/trustedproxy.php` (`proxies`); `.env.example` `TRUSTED_PROXIES=`; `resources/js/lib/layar.ts` `export const MULAI_XL = '(min-width: 1280px)';`; kunci laju baru `profil`, `sandi`, `hapus-akun`, `onboarding`, `pilih-rekomendasi`, `jejak`; `ConsiderationController::KUNCI_PEMULIHAN` dan `UMUR_PEMULIHAN_DETIK`. Tugas 6 menambah sakelar di bawah `TRUSTED_PROXIES=` di `.env.example`.

- [ ] **Step 1: Tulis test yang gagal.** Empat test baru (`WriteRouteGuardTest`, `TrustedProxyTest`,
`SavedRestoreMarkerTest`, `BreakpointSourceTest`) dan `TrailIndexMapTest` diperbarui karena kueri media kini diimpor.

Buat `tests/Feature/BreakpointSourceTest.php`:

```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Satu sumber lebar layar untuk kode klien (N6 tinjauan ulang W3, ruling W3-F7).
 *
 * Jelajah memasang peta dan kolom ketiganya mulai 1280 px, dan kelasnya memakai `xl:` Tailwind.
 * Kueri media di React dulu menyalin angka itu sendiri; bila keduanya berselisih, peta dipasang
 * di layar yang kolomnya belum tampil. Kini kueri itu satu konstanta di resources/js/lib/layar.ts
 * yang diperiksa terhadap tema bawaan Tailwind (tailwind.config.js tidak menimpa `screens`).
 */
class BreakpointSourceTest extends TestCase
{
    public function test_the_client_breakpoint_matches_the_tailwind_xl_screen(): void
    {
        $this->assertDoesNotMatchRegularExpression('/\bscreens\s*:/', File::get(base_path('tailwind.config.js')), 'Tailwind kini menimpa screens; perbarui layar.ts bersamanya.');
        $this->assertMatchesRegularExpression("/xl: '1280px'/", File::get(base_path('node_modules/tailwindcss/stubs/config.full.js')));
        $this->assertStringContainsString("export const MULAI_XL = '(min-width: 1280px)';", File::get(resource_path('js/lib/layar.ts')));
    }

    public function test_no_client_code_writes_its_own_pixel_breakpoint(): void
    {
        $pelanggar = [];

        foreach (File::allFiles(resource_path('js')) as $berkas) {
            if ($berkas->getRelativePathname() === 'lib'.DIRECTORY_SEPARATOR.'layar.ts' || ! in_array($berkas->getExtension(), ['ts', 'tsx'], true)) {
                continue;
            }

            if (preg_match('/\\(min-width: \\d+px\\)/', $berkas->getContents())) {
                $pelanggar[] = $berkas->getRelativePathname();
            }
        }

        $this->assertSame([], $pelanggar);
    }
}
```

Buat `tests/Feature/SavedRestoreMarkerTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Mountain;
use App\Models\Trail;
use App\Models\User;
use App\Services\ConsiderationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Penanda pemulihan Urungkan sesudah "Hapus dari simpanan" (ruling W3-F2), disapu W9 atas N2 dan
 * N3 tinjauan ulang W3 (ruling W3-F7).
 *
 * Sebelum W9 penanda tidak pernah kedaluwarsa (berlaku sepanjang sesi) dan setiap penghapusan
 * menambah satu kunci sesi. Kini semua penanda tinggal di satu kunci sesi, masing-masing berlaku
 * sepuluh menit, cukup untuk toast yang berhenti menghitung selama disentuh atau difokus (WCAG
 * 2.2.1), dan penanda yang kedaluwarsa dibuang setiap kali ada simpan atau hapus.
 */
class SavedRestoreMarkerTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_restore_marker_expires_after_ten_minutes(): void
    {
        $this->freezeTime();
        [$pendaki, $jalur] = $this->jalurDitarikYangDihapus();

        $this->travel(11)->minutes();

        $this->actingAs($pendaki)->post(route('trails.saved.store', $jalur->id), ['catatan' => 'Catatan lama'])->assertNotFound();
        $this->assertDatabaseMissing('trail_considerations', ['user_id' => $pendaki->id, 'trail_id' => $jalur->id]);
    }

    public function test_a_restore_marker_still_works_within_ten_minutes(): void
    {
        $this->freezeTime();
        [$pendaki, $jalur] = $this->jalurDitarikYangDihapus();

        $this->travel(9)->minutes();

        $this->actingAs($pendaki)->post(route('trails.saved.store', $jalur->id), ['catatan' => 'Catatan lama'])->assertRedirect();
        $this->assertDatabaseHas('trail_considerations', ['user_id' => $pendaki->id, 'trail_id' => $jalur->id, 'note' => 'Catatan lama']);
    }

    /**
     * Penanda tidak membuka jalur yang diarsipkan: arsip berarti ditarik dari katalog, bukan
     * sekadar belum terbit (N3; Trail::active()).
     */
    public function test_a_restore_marker_never_reopens_an_archived_trail(): void
    {
        [$pendaki, $jalur] = $this->jalurDitarikYangDihapus();
        $jalur->forceFill(['archived_at' => now()])->saveQuietly();

        $this->actingAs($pendaki)->post(route('trails.saved.store', $jalur->id))->assertNotFound();
    }

    public function test_every_marker_lives_under_one_session_key_and_expired_ones_are_dropped(): void
    {
        $this->freezeTime();
        $pendaki = User::factory()->create();
        $pertama = $this->jalur('Jalur Pertama');
        $kedua = $this->jalur('Jalur Kedua');
        app(ConsiderationService::class)->simpan($pendaki, $pertama, null);
        app(ConsiderationService::class)->simpan($pendaki, $kedua, null);

        $this->actingAs($pendaki)->delete(route('trails.saved.remove', $pertama->id));
        $this->travel(11)->minutes();
        $this->actingAs($pendaki)->delete(route('trails.saved.remove', $kedua->id));

        $this->assertSame([$kedua->id], array_keys(session('simpanan-pemulihan')));
        $this->assertSame([], array_values(array_filter(array_keys(session()->all()), fn (string $kunci) => str_starts_with($kunci, 'simpanan-pulihkan.'))));
    }

    /**
     * @return array{0: User, 1: Trail}
     */
    private function jalurDitarikYangDihapus(): array
    {
        $pendaki = User::factory()->create();
        $jalur = $this->jalur('Jalur Selo');
        app(ConsiderationService::class)->simpan($pendaki, $jalur, 'Catatan lama');
        $jalur->update(['is_published' => false]);

        $this->actingAs($pendaki)->delete(route('trails.saved.remove', $jalur->id))->assertRedirect();

        return [$pendaki, $jalur];
    }

    private function jalur(string $nama): Trail
    {
        return Trail::factory()->for(Mountain::factory()->create())->published()->create(['name' => $nama]);
    }
}
```

Buat `tests/Feature/TrustedProxyTest.php`:

```php
<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Proxy tepercaya (T8-M2 tinjauan akhir W3, dititipkan ke W9).
 *
 * Batas laju per alamat (tautan bagikan, pendaftaran, atur ulang sandi) membaca alamat
 * pengunjung. Di belakang penyeimbang beban, tanpa daftar proxy tepercaya, alamat itu adalah
 * alamat proxy, sehingga semua tamu berbagi satu kuota. Mempercayai X-Forwarded-For dari siapa
 * pun justru membiarkan pengunjung memalsukan alamatnya. Karena itu daftarnya kosong secara
 * bawaan dan diisi lewat TRUSTED_PROXIES (config/trustedproxy.php, dibaca TrustProxies bawaan
 * Laravel) hanya di lingkungan yang memang di belakang proxy; nilainya keputusan deploy
 * (Konflik W9).
 */
class TrustedProxyTest extends TestCase
{
    public function test_a_forwarded_address_is_ignored_while_no_proxy_is_trusted(): void
    {
        config(['trustedproxy.proxies' => null]);

        $this->assertSame('10.0.0.5', $this->alamatTerbaca('10.0.0.5', '203.0.113.7'));
    }

    public function test_a_listed_proxy_passes_on_the_visitor_address(): void
    {
        config(['trustedproxy.proxies' => '10.0.0.5,10.0.0.6']);

        $this->assertSame('203.0.113.7', $this->alamatTerbaca('10.0.0.5', '203.0.113.7'));
        $this->assertSame('10.0.0.9', $this->alamatTerbaca('10.0.0.9', '203.0.113.7'), 'Alamat yang tidak terdaftar tetap tidak dipercaya.');
    }

    /**
     * Penyedia seperti Vercel tidak punya daftar alamat proxy tetap; "*" mempercayai pengirim
     * langsung, yang di sana selalu tepi jaringan penyedia.
     */
    public function test_a_wildcard_trusts_the_sender_in_front_of_the_application(): void
    {
        config(['trustedproxy.proxies' => '*']);

        $this->assertSame('203.0.113.7', $this->alamatTerbaca('10.0.0.5', '203.0.113.7'));
    }

    private function alamatTerbaca(string $pengirim, string $diteruskan): string
    {
        Route::get('/_uji/alamat', fn (Request $request) => (string) $request->ip());

        return (string) $this->withServerVariables(['REMOTE_ADDR' => $pengirim])
            ->withHeaders(['X-Forwarded-For' => $diteruskan])
            ->get('/_uji/alamat')
            ->getContent();
    }
}
```

Buat `tests/Feature/WriteRouteGuardTest.php`:

```php
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RuteLaravel;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Tinjauan keamanan W9 (CLAUDE.md bagian 4: auth bypass dan rate limiting) atas setiap rute
 * tulis, bukan hanya rute satu gelombang.
 *
 * RouteAccessMatrixTest menyapu rute GET; PersonalDataWriteGuardTest menyapu rute tulis W7.
 * Sapuan ini menutup sisanya: setiap rute POST, PUT, PATCH, dan DELETE butuh sesi kecuali pintu
 * masuk akun, dan setiap rute tulis dibatasi laju kecuali yang tercatat beserta alasannya.
 * Sebelum W9 profil, onboarding, pilihan rekomendasi, unggah jejak, pendaftaran, dan permintaan
 * tautan atur ulang sandi tidak dibatasi laju sama sekali.
 */
class WriteRouteGuardTest extends TestCase
{
    use RefreshDatabase;

    /** Rute tulis yang memang dibuka tanpa sesi. */
    private const PINTU = [
        'login.store' => 'masuk',
        'register.store' => 'daftar',
        'password.email' => 'meminta tautan atur ulang sandi',
        'password.store' => 'mengatur ulang sandi dengan token',
        'tema.simpan' => 'cookie tema, bukan data akun',
        'tampilan.simpan' => 'cookie tampilan, bukan data akun',
    ];

    /**
     * Rute paket pihak ketiga. Livewire masih terpasang walau tidak ada komponennya lagi;
     * menghapus paketnya butuh izin dependensi (Konflik W9). Titik akhirnya hanya menerima
     * snapshot komponen bertanda checksum dan unggahan ber-URL bertanda tangan.
     */
    private const PAKET = [
        'default.livewire.update' => 'Livewire 3, tanpa komponen; menunggu keputusan penghapusan paket',
        'livewire.upload-file' => 'Livewire 3, butuh URL bertanda tangan, sudah dibatasi 60 per menit',
    ];

    /** Rute tulis tanpa middleware throttle, dengan alasannya. */
    private const TANPA_THROTTLE = [
        'login.store' => 'LoginRequest::ensureIsNotRateLimited(): lima percobaan per email dan alamat',
        'password.store' => 'butuh token atur ulang sekali pakai; broker sandi Laravel membatasinya',
        'logout' => 'mengakhiri sesi sendiri; mengulangnya tidak menulis apa pun',
        'reports.store' => 'batas per jam di ConditionReportController (RateLimitTest)',
        'goals.store' => 'batas per jam di GoalController (RateLimitTest)',
    ];

    /**
     * Konsol pengelola: admin, moderator, dan pemegang kredensial ahli, peran tepercaya yang setiap
     * aksinya dijaga middleware peran atau Policy. Ruling W8 mencatat rute admin tanpa kunci laju
     * sendiri; batas laju konsol adalah keputusan pemilik (Konflik W9).
     */
    private const AWALAN_KONSOL = ['admin.', 'moderation.', 'trails.geometry.', 'trails.checkpoints.', 'contribute.'];

    public function test_every_write_route_needs_a_session_unless_it_opens_one(): void
    {
        $terbuka = [];

        foreach ($this->ruteTulis() as $nama => $rute) {
            if (! isset(self::PINTU[$nama]) && ! isset(self::PAKET[$nama]) && ! in_array('auth', $rute->gatherMiddleware(), true)) {
                $terbuka[] = $nama;
            }
        }

        $this->assertSame([], $terbuka, 'Rute tulis tanpa sesi: '.implode(', ', $terbuka));
    }

    public function test_every_write_route_is_rate_limited_or_says_why_not(): void
    {
        $tanpaBatas = [];

        foreach ($this->ruteTulis() as $nama => $rute) {
            $berbatas = collect($rute->gatherMiddleware())->contains(fn ($m) => is_string($m) && str_starts_with($m, 'throttle:'));

            if ($berbatas || isset(self::TANPA_THROTTLE[$nama]) || isset(self::PAKET[$nama]) || collect(self::AWALAN_KONSOL)->contains(fn (string $awalan) => str_starts_with($nama, $awalan))) {
                continue;
            }

            $tanpaBatas[] = $nama;
        }

        $this->assertSame([], $tanpaBatas, 'Rute tulis tanpa batas laju: '.implode(', ', $tanpaBatas));

        foreach (array_keys(self::TANPA_THROTTLE) as $nama) {
            $this->assertNotNull(Route::getRoutes()->getByName($nama), "Pengecualian basi: rute {$nama} sudah tidak ada.");
        }
    }

    public function test_registration_is_rate_limited_per_address(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->post(route('register.store'), [])->assertSessionHasErrors();
        }

        $this->post(route('register.store'), [])->assertStatus(429);
    }

    public function test_asking_for_a_password_reset_link_is_rate_limited_per_address(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->post(route('password.email'), ['email' => 'bukan-alamat'])->assertSessionHasErrors('email');
        }

        $this->post(route('password.email'), ['email' => 'bukan-alamat'])->assertStatus(429);
    }

    /**
     * Rute yang dibatasi W9 memakai pola W4 dan W7: batas tulisan pribadi per menit dengan
     * hitungan sendiri per kelompok, jadi menyunting profil tidak menghabiskan jatah unggah jejak.
     */
    public function test_each_newly_limited_hiker_route_counts_on_its_own_key(): void
    {
        $batas = (int) config('hiking.rate_limits.personal_writes_per_minute');

        foreach ([
            'profile.update' => 'profil',
            'profile.password' => 'sandi',
            'profile.preference' => 'profil',
            'profile.destroy' => 'hapus-akun',
            'onboarding.store' => 'onboarding',
            'recommendations.pilih' => 'pilih-rekomendasi',
            'hike.track' => 'jejak',
            'hike.track.destroy' => 'jejak',
        ] as $nama => $kunci) {
            $this->assertContains("throttle:{$batas},1,{$kunci}", Route::getRoutes()->getByName($nama)->gatherMiddleware(), $nama);
        }
    }

    /**
     * Tautan bagikan publik dihitung per alamat dengan awalan sendiri (N5 tinjauan ulang W3), jadi
     * pembaca tautan tidak menghabiskan jatah rute publik lain dari alamat yang sama.
     */
    public function test_the_public_share_link_counts_on_its_own_key(): void
    {
        $batas = (int) config('hiking.share_links.views_per_minute');

        $this->assertContains("throttle:{$batas},1,tautan-buka", Route::getRoutes()->getByName('shares.show')->gatherMiddleware());
    }

    /**
     * Disk lokal tidak lagi menyajikan berkas lewat storage/{path}: berkas pribadi hanya keluar
     * lewat rute yang memeriksa Policy (ReportPhotoAccessTest, EvidenceArchiveTest).
     */
    public function test_private_files_have_no_signed_url_route(): void
    {
        $this->assertFalse(Route::has('storage.local'));
        $this->assertFalse(Route::has('storage.local.upload'));
        $this->get('/storage/report-photos/contoh.jpg')->assertNotFound();
    }

    /**
     * @return array<string, RuteLaravel>
     */
    private function ruteTulis(): array
    {
        $hasil = [];

        foreach (Route::getRoutes() as $rute) {
            $nama = $rute->getName();

            if ($nama === null || array_intersect($rute->methods(), ['POST', 'PUT', 'PATCH', 'DELETE']) === [] || str_starts_with($rute->uri(), '_')) {
                continue;
            }

            $hasil[$nama] = $rute;
        }

        ksort($hasil);

        return $hasil;
    }
}
```

`tests/Feature/TrailIndexMapTest.php`:

1. Ganti:

```php
     * bagian 9.4: MapLibre dimuat sesuai kebutuhan). Ukuran nyatanya diukur di T12.
```

dengan:

```php
     * bagian 9.4: MapLibre dimuat sesuai kebutuhan). Ukuran nyatanya diukur di T12. Sejak W9 kueri
     * 1280 px dibaca dari satu sumber, MULAI_XL di resources/js/lib/layar.ts (BreakpointSourceTest).
```

2. Ganti:

```php
        $this->assertStringContainsString("useCocokMedia('(min-width: 1280px)')", $halaman);
```

dengan:

```php
        $this->assertStringContainsString('useCocokMedia(MULAI_XL)', $halaman);
```

- [ ] **Step 2: Jalankan dan pastikan gagal.**

Run: `php artisan test --compact --filter='WriteRouteGuardTest|TrustedProxyTest|SavedRestoreMarkerTest|BreakpointSourceTest|TrailIndexMapTest'`
Expected: FAIL; pada putaran ulang 29 test, 18 lulus, 9 gagal, 2 error. Yang gagal: daftar rute tulis tanpa batas laju, `X-Forwarded-For` dari proxy terdaftar diabaikan,
rute `storage.local` masih terdaftar, penanda pemulihan tanpa umur, dan `layar.ts` belum ada.

- [ ] **Step 3: Implementasi.**

Buat `config/trustedproxy.php`:

```php
<?php

/*
|--------------------------------------------------------------------------
| Proxy tepercaya (T8-M2 tinjauan akhir W3, W9)
|--------------------------------------------------------------------------
|
| Dibaca Illuminate\Http\Middleware\TrustProxies lewat config('trustedproxy.proxies'). Kosong
| secara bawaan: tidak ada X-Forwarded-For yang dipercaya, jadi pengunjung tidak dapat
| memalsukan alamat yang dihitung batas laju. Diisi hanya di lingkungan yang memang berada di
| belakang proxy: "*" untuk penyedia tanpa daftar alamat tetap (Vercel), atau daftar alamat
| dipisah koma. Nilainya keputusan deploy (Konflik W9).
|
*/

return [

    'proxies' => env('TRUSTED_PROXIES') ?: null,

];
```

Buat `resources/js/lib/layar.ts`:

```ts
/**
 * Lebar layar yang dibaca kode klien, satu sumber (N6 tinjauan ulang W3, W9). Nilainya breakpoint
 * bawaan Tailwind: tailwind.config.js tidak menimpa `screens`, jadi kelas `xl:` dan kueri media ini
 * sama-sama mulai 1280 px. BreakpointSourceTest memeriksa keduanya.
 */
export const MULAI_XL = '(min-width: 1280px)';
```

`.env.example`:

1. Ganti:

```ini
APP_URL=http://localhost:8000
```

dengan:

```ini
APP_URL=http://localhost:8000

# Proxy tepercaya (W9, config/trustedproxy.php): kosong di lokal. Isi "*" hanya bila aplikasi
# berjalan di belakang proxy penyedia tanpa daftar alamat tetap (Vercel), atau daftar alamat
# dipisah koma.
TRUSTED_PROXIES=
```

`app/Http/Controllers/Trails/ConsiderationController.php`:

2. Ganti:

```php
    /** Awalan kunci sesi penanda pemulihan; diikuti id jalur. */
    private const KUNCI_PEMULIHAN = 'simpanan-pulihkan.';
```

dengan:

```php
    /** Kunci sesi penanda pemulihan: id jalur ke pemilik dan batas waktunya. */
    private const KUNCI_PEMULIHAN = 'simpanan-pemulihan';

    /**
     * Umur penanda pemulihan (N2 tinjauan ulang W3, W9): cukup untuk toast Urungkan yang berhenti
     * menghitung selama disentuh atau difokus (WCAG 2.2.1), tetapi tidak sepanjang sesi.
     */
    private const UMUR_PEMULIHAN_DETIK = 600;
```

3. Ganti:

```php
        // Nilainya id pemilik, supaya sesi yang berganti akun tidak mewarisinya.
        $request->session()->put(self::KUNCI_PEMULIHAN.$trail, $request->user()->id);
```

dengan:

```php
        // Nilainya id pemilik, supaya sesi yang berganti akun tidak mewarisinya. Semua penanda
        // tinggal di satu kunci, dan yang kedaluwarsa dibuang di sini (W9).
        $penanda = self::penandaBerlaku($request);
        $penanda[$trail] = ['pengguna' => $request->user()->id, 'sampai' => now()->addSeconds(self::UMUR_PEMULIHAN_DETIK)->getTimestamp()];
        $request->session()->put(self::KUNCI_PEMULIHAN, $penanda);
```

4. Ganti:

```php
        $kunci = self::KUNCI_PEMULIHAN.$trail;
        $pulihkan = $request->session()->get($kunci) === $request->user()->id;

        if ($pulihkan) {
            $request->session()->forget($kunci);
        }
```

dengan:

```php
        $penanda = self::penandaBerlaku($request);
        $pulihkan = ($penanda[$trail]['pengguna'] ?? null) === $request->user()->id;

        if ($pulihkan) {
            unset($penanda[$trail]);
        }

        $request->session()->put(self::KUNCI_PEMULIHAN, $penanda);
```

5. Ganti:

```php
            : back()->with('pengumuman', $jalur->name.' '.Str::lcfirst(ConsiderationOutcome::DITAMBAHKAN->label()).'.');
    }
}
```

dengan:

```php
            : back()->with('pengumuman', $jalur->name.' '.Str::lcfirst(ConsiderationOutcome::DITAMBAHKAN->label()).'.');
    }

    /**
     * Penanda pemulihan yang masih berlaku, berkunci id jalur; yang kedaluwarsa tidak ikut.
     *
     * @return array<int, array{pengguna: int, sampai: int}>
     */
    private static function penandaBerlaku(Request $request): array
    {
        $sekarang = now()->getTimestamp();

        return array_filter(
            (array) $request->session()->get(self::KUNCI_PEMULIHAN, []),
            fn ($satu) => is_array($satu) && ($satu['sampai'] ?? 0) > $sekarang,
        );
    }
}
```

`config/filesystems.php`:

6. Ganti:

```php
            'serve' => true,
```

dengan:

```php
            // Tanpa rute storage/{path} (W9, tinjauan keamanan): foto laporan, foto profil, arsip
            // bukti, dan foto gunung hanya disajikan rute yang memeriksa Policy, bukan URL bertanda
            // tangan yang cukup dibuat dengan APP_KEY. Tidak ada kode yang membuat temporaryUrl.
            'serve' => false,
```

`resources/js/pages/Trails/Index.tsx`:

7. Ganti:

```tsx
import { kelas } from '@/lib/kelas';
```

dengan:

```tsx
import { kelas } from '@/lib/kelas';
import { MULAI_XL } from '@/lib/layar';
```

8. Ganti:

```tsx
    const layarLebar = useCocokMedia('(min-width: 1280px)');
```

dengan:

```tsx
    const layarLebar = useCocokMedia(MULAI_XL);
```

`routes/auth.php`:

9. Ganti:

```php
    Route::post('register', [RegisteredUserController::class, 'store'])->name('register.store');
```

dengan:

```php
    // Dibatasi per alamat seperti kirim ulang verifikasi (W9): tanpa batas, pendaftaran dapat
    // dipakai membuat akun massal dan mengirim surel ke alamat siapa pun.
    Route::post('register', [RegisteredUserController::class, 'store'])->middleware('throttle:6,1')->name('register.store');
```

10. Ganti:

```php
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
```

dengan:

```php
    // Broker sandi membatasi per email; batas per alamat ini mencegah membanjiri banyak email (W9).
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('throttle:6,1')->name('password.email');
```

`routes/web.php`:

11. Ganti:

```php
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'password'])->name('profile.password');
    Route::put('profile/preference', [ProfileController::class, 'preference'])->name('profile.preference');
    Route::delete('profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
```

dengan:

```php
    // Batas tulisan pribadi per kelompok (W9, tinjauan keamanan): sandi dan hapus akun punya
    // hitungan sendiri, jadi menyunting profil tidak menghabiskan jatah keduanya.
    Route::patch('profile', [ProfileController::class, 'update'])->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,profil')->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'password'])->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,sandi')->name('profile.password');
    Route::put('profile/preference', [ProfileController::class, 'preference'])->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,profil')->name('profile.preference');
    Route::delete('profile', [ProfileController::class, 'destroy'])->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,hapus-akun')->name('profile.destroy');
```

12. Ganti:

```php
    Route::post('onboarding', [ProfileSetupController::class, 'store'])->name('onboarding.store');
    Route::get('goals/create', [GoalController::class, 'create'])->name('goals.create');
    Route::post('goals', [GoalController::class, 'store'])->name('goals.store');
    Route::get('recommendations/{run}', [RecommendationController::class, 'show'])->name('recommendations.show');
    Route::post('recommendations/{run}/pilih', [RecommendationController::class, 'pilih'])->name('recommendations.pilih');
```

dengan:

```php
    Route::post('onboarding', [ProfileSetupController::class, 'store'])->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,onboarding')->name('onboarding.store');
    Route::get('goals/create', [GoalController::class, 'create'])->name('goals.create');
    Route::post('goals', [GoalController::class, 'store'])->name('goals.store');
    Route::get('recommendations/{run}', [RecommendationController::class, 'show'])->name('recommendations.show');
    Route::post('recommendations/{run}/pilih', [RecommendationController::class, 'pilih'])->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,pilih-rekomendasi')->name('recommendations.pilih');
```

13. Ganti:

```php
    Route::post('hike/{session}/track', [HikeTrackController::class, 'store'])->name('hike.track');
    Route::delete('hike/{session}/track', [HikeTrackController::class, 'destroy'])->name('hike.track.destroy');
```

dengan:

```php
    // Jejak dikirim sesudah pendakian dalam kelompok 500 titik (resources/js/jejak.js), jadi
    // batas tulisan pribadi per menit cukup longgar untuk pendakian terpanjang (W9).
    Route::post('hike/{session}/track', [HikeTrackController::class, 'store'])->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,jejak')->name('hike.track');
    Route::delete('hike/{session}/track', [HikeTrackController::class, 'destroy'])->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,jejak')->name('hike.track.destroy');
```

Varian repo nyata (Pra-cek butir 2). `WriteRouteGuardTest` membaca rute yang benar-benar terdaftar. Bila ia menunjuk rute
tulis repo nyata yang tidak ada di simulasi: rute pendaki diberi `throttle` berkunci sendiri dengan pola baris di
sekitarnya (`config('hiking.rate_limits.personal_writes_per_minute')`, kunci berbahasa Indonesia yang menamai aksinya);
rute konsol dengan awalan di `AWALAN_KONSOL` tidak perlu apa-apa; rute yang batasnya keputusan gelombang lain (misalnya
`trips.hike.position` bila masih ada sesudah W6) dilaporkan NEEDS_CONTEXT.

- [ ] **Step 4: Jalankan dan pastikan lulus.**

Run: `php artisan test --compact --filter='WriteRouteGuardTest|TrustedProxyTest|SavedRestoreMarkerTest|BreakpointSourceTest|TrailIndexMapTest'`
Expected: PASS; pada putaran ulang 29 test, 29 lulus.

- [ ] **Step 5: Format, build, rute, dan suite penuh.**

Run:
```bash
vendor/bin/pint --dirty --format agent
DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build
node_modules/.bin/tsc --noEmit
DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan route:list --path=storage
php artisan test --compact --testsuite=Unit,Feature
```
Expected: Pint lulus, build sukses, `tsc` tanpa galat, `route:list --path=storage` menjawab "Your application doesn't
have any routes matching the given criteria.", suite 2.304 test, 2.302 lulus, 2 dilewati (sebelum tugas: 2.288).

- [ ] **Step 6: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 6: Enkripsi riwayat Inertia di balik sakelar mati

Butir izin akhir 9 (W2) dan Konflik 5 rencana W6: Inertia menyimpan props setiap halaman di `history.state`. Respons
keluar W6 mengirim `Clear-Site-Data: "storage"`, yang menghapus penyimpanan origin tetapi bukan riwayat peramban; di
perangkat bersama, tombol Kembali sesudah keluar dapat menampilkan halaman pengguna sebelumnya. Pasangan
`encryptHistory` dan `Inertia::clearHistory()` menutupnya, tetapi menyangkut kebijakan data di perangkat
(`docs/KEBIJAKAN-DATA-PRIBADI.md`, Q10), jadi dibangun di balik sakelar `INERTIA_ENCRYPT_HISTORY` yang mati secara
bawaan (W9-R11, Konflik 3). Konfigurasi `inertia.history.encrypt` sudah membaca variabel itu; tugas ini menambah
`clearHistory()` saat keluar bila sakelar menyala, dan mencatat variabelnya di `.env.example`.

**Files:**
- Create: `tests/Feature/HistoryPrivacyTest.php`
- Modify: `.env.example`
- Modify: `app/Http/Controllers/Auth/AuthenticatedSessionController.php`

**Interfaces:**
- Consumes: `config('inertia.history.encrypt')` (env `INERTIA_ENCRYPT_HISTORY`, Inertia Laravel 3.3.4), `Inertia::clearHistory()` yang menulis sesi `Inertia\Support\SessionKey::CLEAR_HISTORY`, respons keluar W6 dengan `Clear-Site-Data`.
- Produces: `.env.example` `INERTIA_ENCRYPT_HISTORY=false` di bawah `TRUSTED_PROXIES=`. Tugas 10 menambah variabel di bawahnya.

- [ ] **Step 1: Tulis test yang gagal.**

Buat `tests/Feature/HistoryPrivacyTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Support\SessionKey;
use Tests\TestCase;

/**
 * Riwayat peramban sesudah keluar (butir izin akhir 9; Konflik 5 rencana W6; T8-M4 tinjauan
 * akhir W3).
 *
 * Inertia menyimpan props setiap halaman di history.state. Clear-Site-Data saat keluar (W6)
 * menghapus penyimpanan origin, tetapi bukan riwayat: di perangkat bersama, tombol Kembali
 * sesudah keluar dapat menampilkan halaman pengguna sebelumnya dari riwayat itu. Pasangan
 * encryptHistory dan clearHistory menutupnya, tetapi menyangkut kebijakan data di perangkat
 * (docs/KEBIJAKAN-DATA-PRIBADI.md, Q10), jadi dibangun di balik sakelar
 * INERTIA_ENCRYPT_HISTORY yang mati secara bawaan (pola W3 P8). Pemilik yang menyalakannya.
 */
class HistoryPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_stays_unencrypted_until_the_owner_turns_the_switch_on(): void
    {
        $this->assertFalse((bool) config('inertia.history.encrypt'));

        $halaman = $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertOk()->viewData('page');

        $this->assertArrayNotHasKey('encryptHistory', $halaman);
    }

    public function test_logging_out_with_the_switch_off_leaves_history_alone(): void
    {
        $this->actingAs(User::factory()->create())->post(route('logout'))->assertRedirect('/');

        $this->assertNull(session(SessionKey::CLEAR_HISTORY));
    }

    public function test_with_the_switch_on_pages_encrypt_their_history_and_logging_out_clears_it(): void
    {
        config(['inertia.history.encrypt' => true]);
        $pendaki = User::factory()->create();

        $halaman = $this->actingAs($pendaki)->get(route('dashboard'))->assertOk()->viewData('page');

        $this->assertTrue($halaman['encryptHistory']);

        $this->actingAs($pendaki)->post(route('logout'))
            ->assertRedirect('/')
            ->assertHeader('Clear-Site-Data', '"storage"');

        $this->assertTrue(session(SessionKey::CLEAR_HISTORY));
        $this->assertTrue($this->get('/')->assertOk()->viewData('page')['clearHistory']);
    }
}
```

- [ ] **Step 2: Jalankan dan pastikan gagal.**

Run: `php artisan test --compact --filter=HistoryPrivacyTest`
Expected: FAIL; pada putaran ulang 3 test, 2 lulus, 1 gagal. Yang gagal: keluar dengan sakelar menyala tidak menandai `clearHistory`.

- [ ] **Step 3: Implementasi.**

`.env.example`:

1. Ganti:

```ini
TRUSTED_PROXIES=
```

dengan:

```ini
TRUSTED_PROXIES=

# Enkripsi riwayat Inertia (butir izin akhir 9, W9): mati sampai pemilik memutus kebijakan data
# di perangkat. Menyala, tombol Kembali sesudah keluar tidak memulihkan halaman pengguna lain.
INERTIA_ENCRYPT_HISTORY=false
```

`app/Http/Controllers/Auth/AuthenticatedSessionController.php`:

2. Ganti:

```php
        $logout();

        // Lapis kedua penghapusan data perangkat (spec v3 bagian 10.6; ruling W6-R10): halaman sudah
```

dengan:

```php
        $logout();

        // Riwayat Inertia (butir izin akhir 9, W9): bila INERTIA_ENCRYPT_HISTORY menyala, kunci
        // enkripsi riwayat di peramban diganti pada kunjungan berikutnya, jadi tombol Kembali
        // sesudah keluar tidak memulihkan halaman pengguna sebelumnya. Sakelarnya mati sampai
        // pemilik memutus kebijakan data di perangkat (docs/KEBIJAKAN-DATA-PRIBADI.md, Q10).
        if (config('inertia.history.encrypt')) {
            Inertia::clearHistory();
        }

        // Lapis kedua penghapusan data perangkat (spec v3 bagian 10.6; ruling W6-R10): halaman sudah
```

- [ ] **Step 4: Jalankan dan pastikan lulus.**

Run: `php artisan test --compact --filter=HistoryPrivacyTest`
Expected: PASS; pada putaran ulang 3 test, 3 lulus.

- [ ] **Step 5: Format dan suite penuh.**

Run:
```bash
vendor/bin/pint --dirty --format agent
php artisan test --compact --testsuite=Unit,Feature
```
Expected: Pint lulus; suite 2.307 test, 2.305 lulus, 2 dilewati (sebelum tugas: 2.304).

- [ ] **Step 6: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 7: Kunci baris PostgreSQL untuk Persiapan pertama dan batas per pendaki

Titipan W4 ("balapan dua GET pertama Persiapan yang membangkitkan daftar ganda, dengan uji konkurensi PG") dan W3
("uji konkurensi `lockForUpdate` di PostgreSQL"). Persiapan membangkitkan daftar saat dibuka bila trip belum punya butir;
dua tab atau muat ulang cepat sama-sama membaca daftar kosong lalu sama-sama menulisnya, dan `trip_preparation_items`
tidak punya indeks unik (trip, butir templat) yang menolak salinan kedua. `PreparationService::generateFor()` kini
mengunci baris trip di awal transaksinya (W9-R12). Batas per pendaki yang diperiksa lalu ditulis (simpanan dan tombol
simpan W3, tautan bagikan W3, satu pendakian berlangsung W6) sudah mengunci baris pengguna, tetapi komentar layanannya
sendiri mengakui suite SQLite tidak membuktikan kunci itu; test Spatial baru membuktikannya di PostgreSQL. Uji kunci
PreDepartureCheck W4 sudah ada di repo sejak gelombang perbaikan akhir W4 (`PreDepartureConfirmLockTest`).

**Files:**
- Create: `tests/Spatial/PreparationFirstVisitLockTest.php`
- Create: `tests/Spatial/UserRowLockTest.php`
- Modify: `app/Services/PreparationService.php`

**Interfaces:**
- Consumes: `Tests\Spatial\SpatialTestCase` (dilewati tanpa `SPATIAL_TEST_DSN`), pola koneksi kedua `pgsql_kedua` dan `lock_timeout` dari `PreDepartureConfirmLockTest`, `ConsiderationService::simpan()` dan `toggle()`, `TautanBagikan::buat()`, `TripLifecycle::start()`.
- Produces: tidak ada antarmuka baru; `generateFor()` menjalankan `select ... for update` atas `trip_plans` sebelum membaca butir yang ada.

- [ ] **Step 1: Tulis test Spatial.** Keduanya dibuat langsung di `tests/Spatial`.

Buat `tests/Spatial/PreparationFirstVisitLockTest.php`:

```php
<?php

namespace Tests\Spatial;

use App\Enums\PreparationCategory;
use App\Models\Mountain;
use App\Models\PreparationItem;
use App\Models\PreparationTemplate;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Models\User;
use App\Services\PreparationService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Dua kunjungan pertama ke Persiapan yang hampir bersamaan (titipan W4 untuk W9; R-024).
 *
 * Persiapan membangkitkan daftar saat dibuka bila trip belum punya butir. Dua tab, atau muat
 * ulang yang cepat, sama-sama membaca daftar kosong lalu sama-sama menulisnya, dan tabel butir
 * trip tidak punya indeks unik (trip, butir templat) yang menolak salinan kedua. Sejak W9
 * generateFor() mengunci baris trip di awal transaksinya, sebelum membaca butir yang sudah ada,
 * jadi kunjungan kedua menunggu yang pertama selesai lalu memperbarui daftar yang sama.
 *
 * SQLite tidak punya kunci baris, jadi buktinya hanya di sini, dengan koneksi kedua yang memegang
 * `FOR UPDATE` atas baris trip (pola PreDepartureConfirmLockTest). Data harus benar-benar
 * tersimpan supaya koneksi kedua melihatnya, jadi test ini tidak memakai transaksi
 * RefreshDatabase dan membersihkan barisnya sendiri. Dilewati tanpa SPATIAL_TEST_DSN.
 */
class PreparationFirstVisitLockTest extends SpatialTestCase
{
    /**
     * @return array<int, string>
     */
    protected function connectionsToTransact(): array
    {
        return [];
    }

    public function test_the_first_list_waits_for_another_visit_and_is_written_once(): void
    {
        $user = User::factory()->create();
        $trail = Trail::factory()->for(Mountain::factory()->create())->create();
        $templat = PreparationTemplate::create(['trail_id' => $trail->id, 'name' => 'Templat uji kunci', 'is_default' => false]);

        foreach (['Jas hujan', 'Senter kepala'] as $urutan => $label) {
            PreparationItem::create([
                'preparation_template_id' => $templat->id,
                'category' => PreparationCategory::EQUIPMENT->value,
                'label' => $label,
                'is_critical' => true,
                'sort_order' => $urutan,
            ]);
        }

        $trip = TripPlan::factory()->for($user)->create(['trail_id' => $trail->id]);
        $hitung = fn (): int => TripPreparationItem::query()->where('trip_plan_id', $trip->id)->count();

        config(['database.connections.pgsql_kedua' => config('database.connections.pgsql')]);
        $kedua = DB::connection('pgsql_kedua');

        try {
            $kedua->beginTransaction();
            $kedua->select('select id from trip_plans where id = ? for update', [$trip->id]);
            DB::statement("set lock_timeout = '500ms'");

            try {
                app(PreparationService::class)->generateFor($trip->fresh());
                $this->fail('generateFor() seharusnya menunggu kunci baris trip yang dipegang kunjungan lain.');
            } catch (QueryException $galat) {
                $this->assertSame('55P03', (string) $galat->getCode());
                $this->assertStringContainsString('for update', strtolower($galat->getSql()));
                $this->assertStringContainsString('trip_plans', $galat->getSql());
            } finally {
                DB::statement('set lock_timeout = 0');
                $kedua->rollBack();
            }

            $this->assertSame(0, $hitung(), 'Kunjungan yang gagal menunggu tidak menulis setengah daftar.');

            $jumlah = app(PreparationService::class)->generateFor($trip->fresh())->count();
            app(PreparationService::class)->generateFor($trip->fresh());

            $this->assertGreaterThanOrEqual(2, $jumlah);
            $this->assertSame($jumlah, $hitung(), 'Kunjungan kedua memperbarui daftar yang sama, bukan menulis salinannya.');
        } finally {
            DB::disconnect('pgsql_kedua');
            User::query()->whereKey($user->id)->delete();
            Mountain::query()->whereKey($trail->mountain_id)->delete();
        }
    }
}
```

Buat `tests/Spatial/UserRowLockTest.php`:

```php
<?php

namespace Tests\Spatial;

use App\Enums\ConsiderationOutcome;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\ConsiderationService;
use App\Services\Trails\TautanBagikan;
use App\Services\Trips\TripLifecycle;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Batas per pendaki yang diperiksa lalu ditulis mengunci baris pengguna lebih dulu (titipan W3
 * dan W6 untuk W9; R-024): simpanan (batas simpanan, W3), tautan bagikan (batas tautan aktif,
 * W3), dan satu pendakian berlangsung per pendaki (W6-R2).
 *
 * Komentar di layanannya sendiri mengakui suite SQLite tidak membuktikan kunci itu. Di sini
 * koneksi kedua memegang `FOR UPDATE` atas baris pengguna: selama dipegang, setiap operasi harus
 * menunggu di kueri `for update` atas users lalu gagal sesudah lock_timeout tanpa menulis
 * apa pun; sesudah dilepas, operasinya berhasil. Trip dibuat sebelum kunci dipegang, karena
 * menyisipkan baris yang menunjuk pengguna terkunci ikut menunggu (FOR KEY SHARE) dan akan
 * mengaburkan kueri mana yang menunggu. Dilewati tanpa SPATIAL_TEST_DSN.
 */
class UserRowLockTest extends SpatialTestCase
{
    /**
     * @return array<int, string>
     */
    protected function connectionsToTransact(): array
    {
        return [];
    }

    /**
     * @return array<string, array{0: Closure(User, Trail, TripPlan): bool}>
     */
    public static function operasiBerbatas(): array
    {
        return [
            'simpan dari kartu' => [fn (User $user, Trail $trail): bool => app(ConsiderationService::class)->simpan($user, $trail) === ConsiderationOutcome::DITAMBAHKAN],
            'tombol simpan bolak-balik' => [fn (User $user, Trail $trail): bool => app(ConsiderationService::class)->toggle($user, $trail) === ConsiderationOutcome::DITAMBAHKAN],
            'tautan bagikan' => [fn (User $user, Trail $trail): bool => app(TautanBagikan::class)->buat($user, collect([$trail])) !== null],
            'mulai pendakian' => [fn (User $user, Trail $trail, TripPlan $trip): bool => app(TripLifecycle::class)->start($trip) === null],
        ];
    }

    /**
     * @param  Closure(User, Trail, TripPlan): bool  $operasi
     */
    #[DataProvider('operasiBerbatas')]
    public function test_a_per_hiker_limit_waits_for_the_hiker_row_lock(Closure $operasi): void
    {
        $user = User::factory()->create();
        $trail = Trail::factory()->for(Mountain::factory()->create())->create();
        $trip = TripPlan::factory()->for($user)->create(['trail_id' => $trail->id]);

        config(['database.connections.pgsql_kedua' => config('database.connections.pgsql')]);
        $kedua = DB::connection('pgsql_kedua');

        try {
            $kedua->beginTransaction();
            $kedua->select('select id from users where id = ? for update', [$user->id]);
            DB::statement("set lock_timeout = '500ms'");

            try {
                $operasi($user, $trail, $trip->fresh());
                $this->fail('Operasi berbatas seharusnya menunggu kunci baris pengguna yang dipegang permintaan lain.');
            } catch (QueryException $galat) {
                $this->assertSame('55P03', (string) $galat->getCode());
                $this->assertStringContainsString('for update', strtolower($galat->getSql()));
                $this->assertStringContainsString('users', $galat->getSql());
            } finally {
                DB::statement('set lock_timeout = 0');
                $kedua->rollBack();
            }

            $this->assertTrue($operasi($user->fresh(), $trail, $trip->fresh()), 'Sesudah kunci dilepas, operasinya berjalan.');
        } finally {
            DB::disconnect('pgsql_kedua');
            DB::table('analytics_events')->where('user_id', $user->id)->delete();
            User::query()->whereKey($user->id)->delete();
            Mountain::query()->whereKey($trail->mountain_id)->delete();
        }
    }
}
```

- [ ] **Step 2: Jalankan.**

Run: `php artisan test --compact --testsuite=Spatial --filter='PreparationFirstVisitLockTest|UserRowLockTest'`
Expected tanpa `SPATIAL_TEST_DSN`: dilewati; pada putaran ulang 5 test, 5 dilewati. Dengan PostGIS, `PreparationFirstVisitLockTest` gagal di baris
`$this->fail(...)` karena `generateFor()` tidak menunggu kunci (INFERENCE dari kode; belum pernah dijalankan di
PostgreSQL), sedangkan keempat kasus `UserRowLockTest` lulus karena kuncinya sudah ada.

- [ ] **Step 3: Implementasi.**

`app/Services/PreparationService.php`:

1. Ganti:

```php
     * from the default template. Existing item statuses are preserved on regeneration.
```

dengan:

```php
     * from the default template. Existing item statuses are preserved on regeneration.
     *
     * Baris trip dikunci sebelum butir yang ada dibaca (titipan W4 untuk W9): dua kunjungan
     * pertama yang hampir bersamaan tidak sama-sama membaca daftar kosong lalu sama-sama
     * menulisnya, karena tabel butir trip tidak punya indeks unik yang menolak salinan kedua.
     * Kuncinya sungguhan di PostgreSQL dan tanpa efek di SQLite; buktinya
     * tests/Spatial/PreparationFirstVisitLockTest.
```

2. Ganti:

```php
        return DB::transaction(function () use ($trip, $items) {
            $existing = $trip->preparationItems()->get()->keyBy('preparation_item_id');
```

dengan:

```php
        return DB::transaction(function () use ($trip, $items) {
            TripPlan::query()->whereKey($trip->getKey())->lockForUpdate()->first();

            $existing = $trip->preparationItems()->get()->keyBy('preparation_item_id');
```

- [ ] **Step 4: Jalankan test layanan persiapan.**

Run: `php artisan test --compact --filter='PreparationServiceTest|PreparationUndoTest|TripReactPagesTest|PermitItemKeyTest|ReadinessDashboardTest'`
Expected: PASS; pada putaran ulang 92 test, 92 lulus. Pembangkitan ulang tetap tidak menggandakan butir dan tidak mengulang status
(`PreparationServiceTest::test_regenerating_keeps_existing_statuses`).

- [ ] **Step 5: Format dan suite penuh.**

Run:
```bash
vendor/bin/pint --dirty --format agent
php artisan test --compact --testsuite=Unit,Feature
php artisan test --compact --testsuite=Spatial
```
Expected: Pint lulus; suite 2.307 test, 2.305 lulus, 2 dilewati (sebelum tugas: 2.307); Spatial 82 test, semuanya dilewati tanpa `SPATIAL_TEST_DSN`. Bukti PostgreSQL menunggu CI (Tugas 16 Step 2).

- [ ] **Step 6: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 8: Anggaran Jelajah dan Beranda pada batas 500 kandidat, gunung yang diikuti dibaca sekali, dan aturan izin penjadwal dibaca sekali

Titipan W3 ("ukuran anggaran Jelajah pada batas 500 kandidat"), W5-5b M5 ("biaya hitung kecocokan hingga 500 kandidat
per muat Beranda"), W5-4a T-2 ("`mountain_follows` yang dikueri dua kali di Beranda"), dan titipan W9 dari gelombang
akhir W5 di pelacak program ("aturan izin dibaca per baris IZIN jatuh tempo di `TripReminderScheduler`, pemuatan batch
lewat `requirementsForTrails()`"). Anggaran ukuran Jelajah sampai sekarang diukur di 40 jalur, padahal atribut saringan
dikirim untuk setiap kandidat sampai batas 500. Pengukuran simulasi W9 (SQLite memori, satu mesin): Jelajah 78,1 kB dan
sekitar 290 ms untuk 500 jalur, Beranda sekitar 170 ms, 520 jalur sama dengan 500, jumlah kueri tetap (FACT simulasi;
waktu di PostgreSQL produksi INFERENCE). Batas kandidat menjaga waktu hitung tetap terbatas, jadi tugas ini menguncinya
dengan test pada batas itu (W9-R13) dan tidak mengubah cara kandidat dipilih (Konflik 7). `KabarJalur` kini meneruskan
id gunung yang sudah dimuatnya ke `TrailNewsService`, sehingga `mountain_follows` dibaca sekali per muat Beranda dan
halaman Kabar. Penjadwal pengingat membaca aturan izin dua kueri per trip yang surel hari bukanya jatuh tempo; kini
aturan semua trip satu putaran dimuat sekali lewat `PermitService::requirementsForTrails()` (W9-R22).

**Files:**
- Create: `tests/Feature/CandidateCapTest.php`
- Create: `tests/Feature/FollowedMountainsOnceTest.php`
- Create: `tests/Feature/PermitRulesReadOncePerRunTest.php`
- Modify: `app/Services/Community/KabarJalur.php`
- Modify: `app/Services/TrailNewsService.php`
- Modify: `app/Services/Trips/TripReminderScheduler.php`

**Interfaces:**
- Consumes: `config('hiking.jelajah.batas_kandidat')`, `config('hiking.beranda.batas_kandidat')`, `HalamanJelajah::props()`, `JalurUntukAnda::untuk()`, `KabarJalur::untuk()` dan `ringkas()` (W5, W8), `PermitService::requirementsForTrails(Illuminate\Database\Eloquent\Collection $trails): array` (berkunci id jalur), perintah `trips:kirim-pengingat`.
- Produces: `TrailNewsService::forUser(User $user, int $limit = 30, ?Collection $gunung = null): Collection` dan `TrailNewsService::terbaru(User $user, int $resmi, int $laporan, ?Collection $gunung = null): array`; parameter `$gunung` opsional berisi id gunung yang diikuti. Metode privat `TripReminderScheduler::prosesTrip()` menerima `array $aturanIzin` berkunci id jalur; antarmuka publik penjadwal tidak berubah.

- [ ] **Step 1: Tulis test yang gagal.** Tiga test baru. `CandidateCapTest` membuat jalur per 40 dan mengosongkan
ingatan unik Faker di antaranya, karena nama belakang unik Faker habis sebelum 200 jalur. `PermitRulesReadOncePerRunTest`
menghitung kueri `permit_requirements` selama satu jalan `trips:kirim-pengingat` untuk 2 trip, lalu untuk 5 trip lain.

Buat `tests/Feature/CandidateCapTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\NavigationExperience;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Jelajah dan Beranda pada batas kandidatnya (titipan W3 dan W5-5b M5 untuk W9).
 *
 * Keduanya menilai kecocokan di memori untuk paling banyak 500 jalur per permintaan
 * (hiking.jelajah.batas_kandidat dan hiking.beranda.batas_kandidat). Kuerinya tetap; yang tumbuh
 * bersama katalog adalah ukuran halaman dan waktu hitung, jadi keduanya dijaga di batas itu,
 * bukan di 40 jalur seperti ResultListBudgetTest. Diukur di simulasi W9 (SQLite memori): Jelajah
 * 78 kB dan sekitar 290 ms untuk 500 jalur, Beranda sekitar 170 ms; 520 jalur sama dengan 500.
 */
class CandidateCapTest extends TestCase
{
    use RefreshDatabase;

    /** Jelajah dengan 500 jalur berprofil rapat di simulasi W9: 78 kB. */
    private const ANGGARAN_JELAJAH_DI_BATAS_KB = 100;

    private function pendakiDinilai(): User
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::INTERMEDIATE->value, 'completed_at' => now()]);
        $user->experience()->create([
            'completed_hikes_count' => 8,
            'terrain_experience' => ['FOREST'],
            'navigation_experience' => NavigationExperience::COMPETENT->value,
        ]);
        $user->preference()->create(['preferred_duration' => 'ONE_DAY', 'preferred_trip_type' => 'CAMPING']);

        return $user->fresh();
    }

    /**
     * Jalur terbit berprofil rapat sampai jumlahnya $jumlah. Pabrik jalur memakai nama belakang
     * unik dari Faker yang habis sebelum ratusan jalur, jadi ingatan uniknya dikosongkan per 40
     * jalur, dan nama serta slug ditulis berurutan.
     */
    private function terbitkanSampai(int $jumlah, Mountain $gunung): void
    {
        $profil = array_map(fn (int $i) => ['km' => $i / 20, 'm' => 1200 + (int) round(600 * sin($i / 40))], range(0, 199));

        while (($ada = Trail::count()) < $jumlah) {
            fake()->unique(true);
            Trail::factory()->count(min(40, $jumlah - $ada))
                ->sequence(fn ($urutan) => ['name' => 'Jalur Batas '.($ada + $urutan->index), 'slug' => 'jalur-batas-'.($ada + $urutan->index)])
                ->for($gunung)->easy()->published()->create(['elevation_profile' => $profil]);
        }
    }

    public function test_jelajah_at_the_candidate_cap_stays_within_its_size_budget(): void
    {
        $this->terbitkanSampai(500, Mountain::factory()->create());

        $respons = $this->actingAs($this->pendakiDinilai())->get(route('trails.index'))->assertOk();
        $kb = strlen((string) $respons->getContent()) / 1024;

        $respons->assertInertia(fn (AssertableInertia $page) => $page->has('atribut', 500)->where('ringkasan.terpotong', null));
        $this->assertLessThan(
            self::ANGGARAN_JELAJAH_DI_BATAS_KB,
            $kb,
            sprintf('Jelajah %.0f kB untuk 500 jalur, melewati anggaran %d kB.', $kb, self::ANGGARAN_JELAJAH_DI_BATAS_KB)
        );
    }

    public function test_jelajah_past_the_cap_says_so_and_sends_no_more_candidates(): void
    {
        $this->terbitkanSampai(520, Mountain::factory()->create());

        $this->actingAs($this->pendakiDinilai())->get(route('trails.index'))->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('atribut', 500)
                ->where('ringkasan.terpotong', 500)
                ->where('jumlahTerbit', 520));
    }

    public function test_the_home_scores_no_more_trails_than_its_candidate_cap(): void
    {
        $user = $this->pendakiDinilai();
        $this->terbitkanSampai(3, Mountain::factory()->create());
        $tiga = Trail::query()->orderBy('id')->pluck('id')->all();
        $ditawarkan = fn () => collect($this->actingAs($user)->get(route('dashboard'))->assertOk()->viewData('page')['props']['jalurLain']['jalur'])->pluck('id')->sort()->values()->all();

        $this->assertSame($tiga, $ditawarkan(), 'Tanpa batas yang menyempit, ketiga jalur yang cocok ditawarkan.');

        config(['hiking.beranda.batas_kandidat' => 2]);

        $this->assertSame(array_slice($tiga, 0, 2), $ditawarkan());
    }
}
```

Buat `tests/Feature/FollowedMountainsOnceTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Models\Mountain;
use App\Models\MountainFollow;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Gunung yang diikuti dibaca sekali per muat (titipan W5-4a T-2 untuk W9).
 *
 * KabarJalur memuat gunung yang diikuti untuk judul dan pintu ke halaman gunung, lalu
 * TrailNewsService membaca mountain_follows lagi untuk cakupan kabarnya. Kuerinya tetap (tidak
 * tumbuh bersama data), jadi PageQueryBudgetTest tidak menangkapnya; yang ditutup di sini adalah
 * pembacaan ganda di Beranda dan halaman Kabar.
 */
class FollowedMountainsOnceTest extends TestCase
{
    use RefreshDatabase;

    private function pendakiYangMengikuti(): User
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::INTERMEDIATE->value, 'completed_at' => now()]);
        $gunung = Mountain::factory()->create();
        $jalur = Trail::factory()->for($gunung)->easy()->published()->create();
        TrailConditionReport::factory()->approved()->for($jalur)->create();
        MountainFollow::create(['user_id' => $user->id, 'mountain_id' => $gunung->id]);

        return $user;
    }

    private function bacaanIkuti(string $url): int
    {
        $this->get($url)->assertOk();

        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->get($url)->assertOk();
        $jumlah = count(array_filter(DB::getQueryLog(), fn (array $kueri): bool => str_contains($kueri['query'], 'mountain_follows')));
        DB::disableQueryLog();

        return $jumlah;
    }

    public function test_the_home_reads_the_followed_mountains_once(): void
    {
        $this->actingAs($this->pendakiYangMengikuti());

        $this->assertSame(1, $this->bacaanIkuti(route('dashboard')));
    }

    public function test_the_news_page_reads_the_followed_mountains_once(): void
    {
        $this->actingAs($this->pendakiYangMengikuti());

        $this->assertSame(1, $this->bacaanIkuti(route('news')));
    }
}
```

Buat `tests/Feature/PermitRulesReadOncePerRunTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\TripStatus;
use App\Models\Mountain;
use App\Models\PermitRequirement;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Notifications\PengingatIzinDibuka;
use App\Services\Trips\TripReminderScheduler;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Titipan W9 (gelombang akhir W5): surel hari pemesanan izin dibuka membaca aturan izin jalurnya
 * per trip, dua kueri untuk setiap trip yang jatuh tempo, sehingga jalan penjadwal pada pagi
 * pemesanan dibuka tumbuh bersama jumlah trip. Aturan kini dimuat sekali per putaran lewat
 * PermitService::requirementsForTrails(), pola yang sama dengan kandidat rekomendasi.
 *
 * Waktu mengikuti PermitWindowReminderTest: trip 20 November 2026 di zona WITA dengan pemesanan
 * dibuka H-30, jadi pengingatnya jatuh tempo 20 Oktober 2026 pukul 23.00 UTC.
 */
class PermitRulesReadOncePerRunTest extends TestCase
{
    use RefreshDatabase;

    private bool $menghitung = false;

    private int $kueriAturan = 0;

    protected function setUp(): void
    {
        parent::setUp();

        DB::listen(function (QueryExecuted $kueri): void {
            if ($this->menghitung && str_contains($kueri->sql, 'permit_requirements')) {
                $this->kueriAturan++;
            }
        });
    }

    /** Trip berpengingat menyala, masing-masing di gunungnya sendiri, dijadwalkan sebelum hari buka. */
    private function tripDenganHariBuka(int $jumlah): void
    {
        $this->travelTo(Carbon::parse('2026-09-25 03:00:00', 'UTC'));

        for ($i = 0; $i < $jumlah; $i++) {
            $gunung = Mountain::factory()->create(['timezone' => 'Asia/Makassar']);
            PermitRequirement::factory()->create([
                'mountain_id' => $gunung->id,
                'booking_opens_days_before' => 30,
                'booking_closes_days_before' => 3,
            ]);
            $trip = TripPlan::factory()->for(Trail::factory()->for($gunung))->create([
                'planned_date' => '2026-11-20',
                'status' => TripStatus::PLANNED->value,
                'reminders_enabled' => true,
            ]);

            app(TripReminderScheduler::class)->sinkronkan($trip);
        }
    }

    private function kueriAturanSatuJalan(): int
    {
        $this->travelTo(Carbon::parse('2026-10-20 23:00:00', 'UTC'));
        $this->kueriAturan = 0;
        $this->menghitung = true;
        $this->artisan('trips:kirim-pengingat')->run();
        $this->menghitung = false;

        return $this->kueriAturan;
    }

    public function test_permit_rules_are_read_once_per_run_whatever_the_number_of_due_trips(): void
    {
        Notification::fake();

        $this->tripDenganHariBuka(2);
        $duaTrip = $this->kueriAturanSatuJalan();

        $this->tripDenganHariBuka(5);
        $limaTrip = $this->kueriAturanSatuJalan();

        Notification::assertSentTimes(PengingatIzinDibuka::class, 7);
        $this->assertSame($duaTrip, $limaTrip, 'Kueri aturan izin tumbuh bersama jumlah trip yang jatuh tempo.');
    }
}
```

- [ ] **Step 2: Jalankan dan pastikan gagal.**

Run: `php artisan test --compact --filter='FollowedMountainsOnceTest|CandidateCapTest|PermitRulesReadOncePerRunTest'`
Expected: FAIL; pada putaran ulang 6 test, 3 lulus, 3 gagal. Yang gagal: `mountain_follows` dibaca 2 kali di Beranda dan di halaman Kabar, dan kueri aturan
izin 4 untuk 2 trip lawan 10 untuk 5 trip. Ketiga test `CandidateCapTest` lulus sejak awal: perilakunya sudah benar dan
test ini mengunci batasnya. Dibuktikan merah dengan mutasi di simulasi: tanpa `->limit(...)` di `JalurUntukAnda` Beranda
menawarkan jalur ketiga, dan tanpa `take($batas)` di `HalamanJelajah` atribut berisi 501 kandidat.

- [ ] **Step 3: Implementasi.**

`app/Services/Community/KabarJalur.php`:

1. Ganti:

```php
        $butir = $this->kabar->forUser($user);
```

dengan:

```php
        $butir = $this->kabar->forUser($user, gunung: $gunung->pluck('id'));
```

2. Ganti:

```php
        $terbaru = $this->kabar->terbaru($user, self::RINGKAS_RESMI, self::RINGKAS_LAPORAN);
```

dengan:

```php
        $terbaru = $this->kabar->terbaru($user, self::RINGKAS_RESMI, self::RINGKAS_LAPORAN, $gunung->pluck('id'));
```

`app/Services/TrailNewsService.php`:

3. Ganti:

```php
     * @return Collection<int, array<string, mixed>>
     */
    public function forUser(User $user, int $limit = 30): Collection
    {
        $cakupan = $this->cakupan($user);
```

dengan:

```php
     * @param  Collection<int, int>|null  $gunung  id gunung yang diikuti bila pemanggil sudah memuatnya
     * @return Collection<int, array<string, mixed>>
     */
    public function forUser(User $user, int $limit = 30, ?Collection $gunung = null): Collection
    {
        $cakupan = $this->cakupan($user, $gunung);
```

4. Ganti:

```php
     * @return array{resmi: Collection<int, array<string, mixed>>, laporan: Collection<int, array<string, mixed>>}
     */
    public function terbaru(User $user, int $resmi, int $laporan): array
    {
        $cakupan = $this->cakupan($user);
```

dengan:

```php
     * @param  Collection<int, int>|null  $gunung  id gunung yang diikuti bila pemanggil sudah memuatnya
     * @return array{resmi: Collection<int, array<string, mixed>>, laporan: Collection<int, array<string, mixed>>}
     */
    public function terbaru(User $user, int $resmi, int $laporan, ?Collection $gunung = null): array
    {
        $cakupan = $this->cakupan($user, $gunung);
```

5. Ganti:

```php
     * @return array{0: Collection<int, int>, 1: Collection<int, int>}|null
     */
    private function cakupan(User $user): ?array
    {
        $gunung = MountainFollow::where('user_id', $user->id)->pluck('mountain_id');
```

dengan:

```php
     * KabarJalur sudah memuat gunung yang diikuti untuk judul dan pintunya, jadi id-nya diteruskan
     * ke sini dan mountain_follows dibaca sekali per muat (titipan W5-4a T-2 untuk W9).
     *
     * @param  Collection<int, int>|null  $gunung
     * @return array{0: Collection<int, int>, 1: Collection<int, int>}|null
     */
    private function cakupan(User $user, ?Collection $gunung = null): ?array
    {
        $gunung ??= MountainFollow::where('user_id', $user->id)->pluck('mountain_id');
```

`app/Services/Trips/TripReminderScheduler.php`:

6. Ganti:

```php
use Carbon\CarbonInterface;
```

dengan:

```php
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
```

7. Ganti:

```php
            foreach ($perTrip as $tripId => $pengingat) {
                if (! $this->prosesTrip($pengingat, $sekarang, $hasil)) {
```

dengan:

```php
            // Aturan izin untuk surel hari buka dimuat sekali per putaran, bukan per trip
            // (titipan W9 dari gelombang akhir W5): pagi pemesanan dibuka tidak lagi menambah
            // dua kueri untuk setiap trip yang jatuh tempo.
            $jalurIzin = $perTrip
                ->filter(fn (Collection $pengingat) => $pengingat->contains('jenis', self::JENIS_IZIN))
                ->map(fn (Collection $pengingat) => $pengingat->first()?->tripPlan?->trail)
                ->filter()
                ->unique('id')
                ->values();
            $aturanIzin = $this->izin->requirementsForTrails(new EloquentCollection($jalurIzin->all()));

            foreach ($perTrip as $tripId => $pengingat) {
                if (! $this->prosesTrip($pengingat, $sekarang, $hasil, $aturanIzin)) {
```

8. Ganti:

```php
     * @return bool false bila pengiriman gagal
     */
    private function prosesTrip(Collection $pengingat, CarbonInterface $sekarang, array &$hasil): bool
```

dengan:

```php
     * @param  array<int, PermitRequirement|null>  $aturanIzin  aturan izin berkunci id jalur, dimuat sekali per putaran
     * @return bool false bila pengiriman gagal
     */
    private function prosesTrip(Collection $pengingat, CarbonInterface $sekarang, array &$hasil, array $aturanIzin): bool
```

9. Ganti:

```php
                ? new PengingatIzinDibuka($trip, $this->izin->requirementFor($trip->trail))
```

dengan:

```php
                ? new PengingatIzinDibuka($trip, $aturanIzin[$trip->trail_id] ?? null)
```

**Varian repo nyata untuk `app/Services/Trips/TripReminderScheduler.php`** (tidak disimulasikan; Pra-cek butir 1b).
Gelombang akhir W5 di repo nyata memecah `prosesTrip()` menjadi `prosesRantai()` dan `prosesIzin()` (ruling W5-FA) dan
menulis docblock `prosesTrip()` sedikit berbeda, jadi blok 8 dan blok 9 di atas tidak ditemukan di sana; blok 6 dan 7
cocok di kedua versi. Terapkan varian berikut hanya bila teks lama blok dasarnya tidak ditemukan. Setiap teks lama
varian ditemukan tepat sekali di repo nyata dengan Grep pada 8 Oktober sore, sebelum W6. Titipan W8 N2 di pelacak
(penyelarasan baris IZIN saat aturan izin ditulis) menyentuh `prosesIzin()` yang sama, jadi koordinator mencocokkannya
ulang sesudah W8.

Varian 1, pengganti blok 8. Teks lama:

```php
     * @return bool false bila salah satu pengiriman gagal
     */
    private function prosesTrip(Collection $pengingat, CarbonInterface $sekarang, array &$hasil): bool
```

menjadi:

```php
     * @param  array<int, PermitRequirement|null>  $aturanIzin  aturan izin berkunci id jalur, dimuat sekali per putaran
     * @return bool false bila salah satu pengiriman gagal
     */
    private function prosesTrip(Collection $pengingat, CarbonInterface $sekarang, array &$hasil, array $aturanIzin): bool
```

Varian 2, pengganti blok 9, bagian pemanggil di `prosesTrip()`. Teks lama:

```php
            $berhasil = $this->prosesIzin($trip, $satu, $sekarang, $hasil) && $berhasil;
```

menjadi:

```php
            $berhasil = $this->prosesIzin($trip, $satu, $sekarang, $hasil, $aturanIzin[$trip->trail_id] ?? null) && $berhasil;
```

Varian 3, pengganti blok 9, bagian `prosesIzin()`. Teks lama:

```php
     * @param  array{terkirim: int, dilewati: int, gagal: int}  $hasil
     * @return bool false bila pengiriman gagal
     */
    private function prosesIzin(TripPlan $trip, TripReminder $pengingat, CarbonInterface $sekarang, array &$hasil): bool
    {
        $aturan = $this->izin->requirementFor($trip->trail);
        $buka = $this->bukaIzin($trip, $aturan);
```

menjadi:

```php
     * @param  array{terkirim: int, dilewati: int, gagal: int}  $hasil
     * @param  PermitRequirement|null  $aturan  aturan izin jalur trip ini, dimuat sekali per putaran
     * @return bool false bila pengiriman gagal
     */
    private function prosesIzin(TripPlan $trip, TripReminder $pengingat, CarbonInterface $sekarang, array &$hasil, ?PermitRequirement $aturan): bool
    {
        $buka = $this->bukaIzin($trip, $aturan);
```

`sinkronkan()` di cabang penyelarasan ulang `prosesIzin()` tetap membaca aturannya sendiri; cabang itu hanya berjalan
bila hari buka bergeser, jadi tidak menambah kueri pada jalan biasa. `PermitRulesReadOncePerRunTest` berlaku sama untuk
kedua versi karena ia hanya memanggil perintah `trips:kirim-pengingat` dan menghitung kueri.

- [ ] **Step 4: Jalankan dan pastikan lulus.**

Run: `php artisan test --compact --filter='FollowedMountainsOnceTest|CandidateCapTest|PermitRulesReadOncePerRunTest|PermitWindowReminderTest|TripReminderTest|TrailNews|KabarJalur|Dashboard|PageQueryBudgetTest|NewPageQueryBudgetTest'`
Expected: PASS; pada putaran ulang 164 test, 164 lulus.

- [ ] **Step 5: Format dan suite penuh.**

Run:
```bash
vendor/bin/pint --dirty --format agent
php artisan test --compact --testsuite=Unit,Feature
```
Expected: Pint lulus; suite 2.313 test, 2.311 lulus, 2 dilewati (sebelum tugas: 2.307).

- [ ] **Step 6: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 9: Teks tanpa klaim keselamatan dan halaman luring bergaya v3

Titipan W2 ("salinan bawaan 'Ini area aman aplikasi' di konfirmasi sandi") dan W1-F2 ("tombol dan judul Blade halaman
galat dan offline"). Sapuan kata klaim baru (W9-R14) memindai teks yang dibaca pendaki, bukan komentar: string PHP di
`app/` lewat `token_get_all`, Blade di luar komentar, nilai `lang/id.json`, dan TS/TSX sesudah komentarnya dibuang. Ia
menemukan tiga tempat: konfirmasi sandi, petunjuk kata sandi di Profil, dan dua nilai `lang/id.json`. Empat kalimat
yang memakai kata itu untuk menyangkal atau mengaku tidak pasti disebut di test dengan alasannya. Halaman luring
(shell service worker) masih menulis kelas tombolnya sendiri (radius, bobot, dan ukuran lama); kini memakai `x-ui.button`, dan judulnya skala
tipe v3.

**Files:**
- Create: `tests/Feature/BladeFallbackSurfaceTest.php`
- Create: `tests/Feature/SafetyClaimWordTest.php`
- Modify: `lang/id.json`
- Modify: `resources/js/components/akun/FormKataSandi.tsx`
- Modify: `resources/js/pages/Auth/ConfirmPassword.tsx`
- Modify: `resources/views/offline.blade.php`

**Interfaces:**
- Consumes: komponen Blade `x-ui.button` (W1-F2, `ButtonBladeParityTest`), skala tipe `text-meta`, `text-judul`.
- Produces: tidak ada antarmuka baru.

- [ ] **Step 1: Tulis test yang gagal.**

Buat `tests/Feature/BladeFallbackSurfaceTest.php`:

```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Tombol dan judul permukaan Blade cadangan memakai bentuk v3 (titipan W1-F2 untuk W9). Halaman
 * galat ditulis ulang di W9 Tugas 1; halaman luring masih menulis kelas tombolnya sendiri dengan
 * radius, bobot, dan ukuran teks lama. Kini satu-satunya tombol Blade adalah komponen x-ui.button,
 * yang dijaga ButtonBladeParityTest sama dengan Button.tsx.
 */
class BladeFallbackSurfaceTest extends TestCase
{
    public function test_no_blade_view_writes_its_own_button(): void
    {
        $pelanggar = [];

        foreach (File::allFiles(resource_path('views')) as $berkas) {
            $jalur = str_replace('\\', '/', $berkas->getRelativePathname());

            if ($jalur !== 'components/ui/button.blade.php' && str_contains($berkas->getContents(), '<button')) {
                $pelanggar[] = $jalur;
            }
        }

        $this->assertSame([], $pelanggar);
    }

    public function test_the_offline_page_reloads_with_the_v3_button_under_a_v3_heading(): void
    {
        $this->get('/offline')->assertOk()
            ->assertSee('<h1 class="mt-2 text-judul text-primary">', false)
            ->assertSee('onclick="location.reload()"', false)
            ->assertSee('rounded-tombol', false)
            ->assertSee('Coba muat ulang');
    }
}
```

Buat `tests/Feature/SafetyClaimWordTest.php`:

```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Teks yang dibaca pendaki tidak memakai kata klaim keselamatan (R-012, R-014; Delivery Gate W9
 * butir Salinan; titipan W2 "Ini area aman aplikasi").
 *
 * Yang disapu hanya teks, bukan komentar: string PHP di app/ (lewat token_get_all), isi Blade di
 * luar komentar, nilai lang/id.json, dan sumber TS/TSX di resources/js sesudah komentarnya
 * dibuang. Kata yang sama boleh muncul hanya di kalimat yang menyangkal atau menyatakan
 * ketidakpastian, dan setiap kalimat seperti itu disebut di PENYANGKALAN dengan alasannya. Huruf
 * besar semua (konstanta seperti SEHAT di pemeriksaan penjadwal) bukan teks dan tidak disapu.
 */
class SafetyClaimWordTest extends TestCase
{
    private const POLA = '/\b(?:[Aa]man|[Ss]ehat|[Dd]ijamin|[Tt]erjamin|[Pp]asti)\b/u';

    /**
     * Potongan kalimat yang memakai kata itu untuk menyangkal klaim atau mengaku tidak pasti.
     *
     * @var array<string, string>
     */
    private const PENYANGKALAN = [
        'Sistem tidak menyatakan bahwa suatu gunung atau jalur aman' => 'Penafian R-012 dan R-014 di Beranda.',
        'jarak dan tiba di pos kurang pasti' => 'Mode lapangan mengaku akurasi GPS rendah (R-005).',
        'memberi kesan pasti' => 'Penilaian kecocokan menjelaskan mengapa tidak ada skor angka (R-005).',
        'batasan yang bersifat pasti' => 'Rekomendasi menyebut jenis batasan yang mengecualikan jalur, bukan keadaan jalur.',
    ];

    public function test_no_text_a_hiker_reads_claims_safety_or_certainty(): void
    {
        $pelanggar = [];

        foreach ($this->teks() as $asal => $isi) {
            foreach (array_keys(self::PENYANGKALAN) as $kalimat) {
                $isi = str_replace($kalimat, '', $isi);
            }

            if (preg_match_all(self::POLA, $isi, $temuan)) {
                $pelanggar[$asal] = array_values(array_unique($temuan[0]));
            }
        }

        $this->assertSame([], $pelanggar);
    }

    public function test_every_allowed_sentence_still_exists(): void
    {
        $semua = implode("\n", $this->teks());

        foreach (array_keys(self::PENYANGKALAN) as $kalimat) {
            $this->assertStringContainsString($kalimat, $semua, 'Kalimat pengecualian sudah tidak ada; hapus dari PENYANGKALAN.');
        }
    }

    /**
     * @return array<string, string> teks per berkas, tanpa komentar
     */
    private function teks(): array
    {
        $teks = [];

        foreach (File::allFiles(app_path()) as $berkas) {
            $string = array_filter(token_get_all($berkas->getContents()), fn ($token) => is_array($token) && in_array($token[0], [T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE], true));
            $teks['app/'.str_replace('\\', '/', $berkas->getRelativePathname())] = implode("\n", array_map(fn (array $token) => $token[1], $string));
        }

        foreach (File::allFiles(resource_path('views')) as $berkas) {
            $teks['views/'.str_replace('\\', '/', $berkas->getRelativePathname())] = (string) preg_replace('/\{\{--.*?--\}\}|<!--.*?-->|\/\*.*?\*\//s', '', $berkas->getContents());
        }

        foreach (File::allFiles(resource_path('js')) as $berkas) {
            if (! in_array($berkas->getExtension(), ['ts', 'tsx'], true)) {
                continue;
            }

            $tanpaKomentar = (string) preg_replace(['/\/\*.*?\*\//s', '/(?<![:\'"\w])\/\/.*$/m'], '', $berkas->getContents());
            $teks['js/'.str_replace('\\', '/', $berkas->getRelativePathname())] = $tanpaKomentar;
        }

        $teks['lang/id.json'] = implode("\n", array_values(json_decode(File::get(lang_path('id.json')), true)));

        return $teks;
    }
}
```

- [ ] **Step 2: Jalankan dan pastikan gagal.**

Run: `php artisan test --compact --filter='SafetyClaimWordTest|BladeFallbackSurfaceTest'`
Expected: FAIL; pada putaran ulang 4 test, 1 lulus, 3 gagal. Yang gagal: "aman" di `FormKataSandi.tsx`, `ConfirmPassword.tsx`, dan `lang/id.json`; halaman
luring masih memakai `<button` sendiri dan judul `text-2xl`.

- [ ] **Step 3: Implementasi.**

`lang/id.json`:

1. Ganti:

```json
    "Ensure your account is using a long, random password to stay secure.": "Pastikan akun Anda memakai kata sandi yang panjang dan acak agar tetap aman.",
```

dengan:

```json
    "Ensure your account is using a long, random password to stay secure.": "Pakai kata sandi yang panjang dan acak supaya akun Anda sulit ditebak.",
```

2. Ganti:

```json
    "This is a secure area of the application. Please confirm your password before continuing.": "Ini area aman aplikasi. Konfirmasi kata sandi Anda sebelum melanjutkan.",
```

dengan:

```json
    "This is a secure area of the application. Please confirm your password before continuing.": "Tindakan ini menyangkut akun Anda. Masukkan kata sandi sekali lagi untuk melanjutkan.",
```

`resources/js/components/akun/FormKataSandi.tsx`:

3. Ganti:

```tsx
        <Card title="Perbarui Kata Sandi" subtitle="Pastikan akun Anda memakai kata sandi yang panjang dan acak agar tetap aman.">
```

dengan:

```tsx
        <Card title="Perbarui Kata Sandi" subtitle="Pakai kata sandi yang panjang dan acak supaya akun Anda sulit ditebak.">
```

`resources/js/pages/Auth/ConfirmPassword.tsx`:

4. Ganti:

```tsx
            <p className="mt-2 text-sm text-secondary">Ini area aman aplikasi. Konfirmasi kata sandi Anda sebelum melanjutkan.</p>
```

dengan:

```tsx
            <p className="mt-2 text-sm text-secondary">Tindakan ini menyangkut akun Anda. Masukkan kata sandi sekali lagi untuk melanjutkan.</p>
```

`resources/views/offline.blade.php`:

5. Ganti:

```blade
            <p class="text-sm font-medium text-muted">Luring</p>

            <h1 class="mt-2 text-2xl font-semibold text-primary sm:text-3xl">
                Tidak ada koneksi saat ini
            </h1>
```

dengan:

```blade
            <p class="text-meta font-bold text-muted">Luring</p>

            <h1 class="mt-2 text-judul text-primary">Tidak ada koneksi saat ini</h1>
```

6. Ganti:

```blade
                <button type="button" onclick="location.reload()"
                    class="inline-flex min-h-11 items-center justify-center rounded-control bg-brand-700 px-4 py-2 text-sm font-medium text-on-brand transition hover:bg-brand-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2">
                    Coba muat ulang
                </button>
```

dengan:

```blade
                <x-ui.button onclick="location.reload()">Coba muat ulang</x-ui.button>
```

- [ ] **Step 4: Jalankan dan pastikan lulus.**

Run: `php artisan test --compact --filter='SafetyClaimWordTest|BladeFallbackSurfaceTest|ProgressiveWebAppTest|OfflineShellTest|ButtonBladeParityTest|AccountPagesSourceTest|PasswordConfirmationTest'`
Expected: PASS; pada putaran ulang 32 test, 32 lulus.

- [ ] **Step 5: Format, build, dan suite penuh.**

Run:
```bash
vendor/bin/pint --dirty --format agent
DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build
node_modules/.bin/tsc --noEmit
php artisan test --compact --testsuite=Unit,Feature
```
Expected: Pint lulus, build sukses, `tsc` tanpa galat, suite 2.317 test, 2.315 lulus, 2 dilewati (sebelum tugas: 2.313).

- [ ] **Step 6: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 10: Retensi tautan bagikan di balik sakelar, dan jam jadwal penjadwal yang benar

Titipan W3 ("retensi baris tautan bagikan", T8-M8 tinjauan W3). Tautan yang dicabut atau kedaluwarsa tidak lagi membuka
apa pun, tetapi barisnya tetap mengaitkan satu akun dengan pilihan jalurnya selamanya. `docs/KEBIJAKAN-DATA-PRIBADI.md`
bagian 3 menyatakan data tanpa dasar retensi terdokumentasi diperlakukan sebagai dihapus, tetapi lama simpan untuk
tautan bagikan belum diatur, dan kebijakan itu milik pemilik. Jadi pemangkasnya dibangun di balik
`SHARE_LINK_PRUNE_AFTER_DAYS` yang kosong secara bawaan (W9-R15, Konflik 4): `ComparisonShare` menjadi `MassPrunable`,
`model:prune` dijadwalkan setiap hari pukul 20.00 UTC (03.00 WIB) dan dipantau halaman kesehatan, dan selama angkanya
kosong tidak ada baris yang cocok. Saat menambah label jadwal itu, audit W9 menemukan label jadwal lama menyebut jam
ekspresi cron dengan akhiran WIB padahal penjadwal membaca zona aplikasi UTC (`config/app.php`): "06.00 dan 18.00 WIB"
untuk tugas yang berjalan 13.00 dan 01.00 WIB. Labelnya diperbaiki (W9-R16) dan dijaga test yang menghitung jam WIB
dari ekspresi setiap tugas harian. Jadwal itu sendiri tidak diubah.

**Files:**
- Create: `tests/Feature/ScheduleLabelTest.php`
- Create: `tests/Feature/ShareLinkRetentionTest.php`
- Modify: `.env.example`
- Modify: `app/Models/ComparisonShare.php`
- Modify: `app/Services/SchedulerHealthService.php`
- Modify: `config/hiking.php`
- Modify: `routes/console.php`

**Interfaces:**
- Consumes: `Illuminate\Database\Eloquent\MassPrunable`, perintah `model:prune`, `SchedulerHealthService::TUGAS` dan pemantauan `RecordScheduledTaskRun`, `ComparisonShareFactory`.
- Produces: `config('hiking.share_links.prune_after_days')` (env `SHARE_LINK_PRUNE_AFTER_DAYS`, kosong); `ComparisonShare::prunable(): Builder`; tugas terjadwal `model:prune --model=App\Models\ComparisonShare` harian pukul 20.00 UTC; entri `SchedulerHealthService::TUGAS['model:prune']`.

- [ ] **Step 1: Tulis test yang gagal.**

Buat `tests/Feature/ScheduleLabelTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Services\SchedulerHealthService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Jam di halaman kesehatan penjadwal adalah jam tugas itu benar-benar berjalan (temuan audit W9).
 *
 * Penjadwal membaca ekspresi cron dalam zona aplikasi, dan config/app.php menetapkan UTC. Label
 * lama menulis jam ekspresinya dengan akhiran WIB, jadi admin membaca 06.00 WIB untuk tugas yang
 * berjalan pukul 13.00 WIB. Label sekarang diperiksa terhadap ekspresi setiap tugas harian yang
 * diubah ke WIB, supaya mengganti jadwal tanpa mengganti labelnya gagal di sini.
 */
class ScheduleLabelTest extends TestCase
{
    public function test_each_daily_task_names_the_wib_hour_it_really_runs(): void
    {
        $diperiksa = 0;

        foreach (app(Schedule::class)->events() as $event) {
            [$menit, $jam] = explode(' ', $event->expression);
            $kunci = collect(array_keys(SchedulerHealthService::TUGAS))->first(fn (string $k) => str_contains((string) $event->command, $k));

            if ($kunci === null || $jam === '*') {
                continue;
            }

            foreach (explode(',', $jam) as $satuJam) {
                $wib = Carbon::createFromTime((int) $satuJam, (int) $menit, 0, $event->timezone ?? config('app.timezone'))
                    ->setTimezone('Asia/Jakarta')
                    ->format('H.i');

                $this->assertStringContainsString($wib, SchedulerHealthService::TUGAS[$kunci]['jadwal'], "Label {$kunci} tidak menyebut {$wib} WIB.");
                $diperiksa++;
            }
        }

        $this->assertGreaterThanOrEqual(4, $diperiksa, 'Prakiraan dua kali, kesegaran status, dan pemangkasan tautan diperiksa.');
    }
}
```

Buat `tests/Feature/ShareLinkRetentionTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\ComparisonShare;
use App\Services\SchedulerHealthService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Baris tautan bagikan yang sudah berakhir (titipan W3 M8 untuk W9).
 *
 * Tautan yang dicabut atau kedaluwarsa tidak lagi membuka apa pun, tetapi barisnya tetap
 * mengaitkan satu akun dengan pilihan jalurnya. docs/KEBIJAKAN-DATA-PRIBADI.md bagian 3 menyebut
 * data tanpa dasar retensi terdokumentasi diperlakukan sebagai dihapus, tetapi lama simpannya
 * keputusan pemilik di berkas itu. Karena itu pemangkasnya dibangun di balik
 * SHARE_LINK_PRUNE_AFTER_DAYS yang kosong secara bawaan: tugas hariannya berjalan dan dipantau,
 * tetapi tidak menghapus apa pun sampai pemilik mengisi angkanya.
 */
class ShareLinkRetentionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{aktif: ComparisonShare, baruBerakhir: ComparisonShare, lamaKedaluwarsa: ComparisonShare, lamaDicabut: ComparisonShare}
     */
    private function tautan(): array
    {
        return [
            'aktif' => ComparisonShare::factory()->create(['expires_at' => now()->addDays(3)]),
            'baruBerakhir' => ComparisonShare::factory()->create(['expires_at' => now()->subDays(5)]),
            'lamaKedaluwarsa' => ComparisonShare::factory()->create(['expires_at' => now()->subDays(40)]),
            'lamaDicabut' => ComparisonShare::factory()->create(['expires_at' => now()->addDays(2), 'revoked_at' => now()->subDays(31)]),
        ];
    }

    public function test_nothing_is_pruned_until_the_owner_sets_a_retention(): void
    {
        $this->assertEmpty(config('hiking.share_links.prune_after_days'));
        $this->tautan();

        $this->artisan('model:prune', ['--model' => [ComparisonShare::class]])->assertSuccessful();

        $this->assertSame(4, ComparisonShare::query()->count());
    }

    public function test_links_that_ended_before_the_retention_are_pruned_and_the_rest_stay(): void
    {
        config(['hiking.share_links.prune_after_days' => 30]);
        $tautan = $this->tautan();

        $this->artisan('model:prune', ['--model' => [ComparisonShare::class]])->assertSuccessful();

        $this->assertEqualsCanonicalizing(
            [$tautan['aktif']->id, $tautan['baruBerakhir']->id],
            ComparisonShare::query()->pluck('id')->all()
        );
    }

    public function test_the_pruning_runs_daily_and_is_monitored(): void
    {
        $tugas = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains((string) $event->command, 'model:prune') && str_contains((string) $event->command, 'ComparisonShare'));

        $this->assertNotNull($tugas, 'Pemangkasan tautan bagikan tidak terjadwal.');
        $this->assertSame('0 20 * * *', $tugas->expression);
        $this->assertArrayHasKey('model:prune', SchedulerHealthService::TUGAS);
    }
}
```

- [ ] **Step 2: Jalankan dan pastikan gagal.**

Run: `php artisan test --compact --filter='ShareLinkRetentionTest|ScheduleLabelTest'`
Expected: FAIL; pada putaran ulang 4 test, 1 lulus, 3 gagal. Yang gagal: tidak ada baris yang dipangkas dengan retensi 30 hari, tugas `model:prune` tidak
terjadwal, dan label "06.00 dan 18.00 WIB" tidak menyebut 13.00. `test_nothing_is_pruned_until_the_owner_sets_a_retention`
lulus sejak awal dan menjaga keadaan bawaan.

- [ ] **Step 3: Implementasi.**

`.env.example`:

1. Ganti:

```ini
INERTIA_ENCRYPT_HISTORY=false
```

dengan:

```ini
INERTIA_ENCRYPT_HISTORY=false

# Hari baris tautan bagikan yang sudah berakhir disimpan sebelum dipangkas tugas harian (W9). Kosong:
# tidak ada yang dipangkas sampai pemilik memutus retensinya di docs/KEBIJAKAN-DATA-PRIBADI.md.
SHARE_LINK_PRUNE_AFTER_DAYS=
```

`app/Models/ComparisonShare.php`:

2. Ganti:

```php
use Illuminate\Database\Eloquent\Factories\HasFactory;
```

dengan:

```php
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
```

3. Ganti:

```php
    use HasFactory;
```

dengan:

```php
    use HasFactory, MassPrunable;
```

4. Ganti:

```php
        return $query->whereNull('revoked_at')->where('expires_at', '>', now());
    }
}
```

dengan:

```php
        return $query->whereNull('revoked_at')->where('expires_at', '>', now());
    }

    /**
     * Baris yang berakhir, dicabut atau kedaluwarsa, lebih dari hiking.share_links.prune_after_days
     * hari lalu; dipangkas `model:prune` setiap hari (routes/console.php). Selama angkanya kosong
     * tidak ada baris yang cocok, karena lama simpannya keputusan pemilik di
     * docs/KEBIJAKAN-DATA-PRIBADI.md bagian 3 (titipan W3 M8 untuk W9).
     *
     * @return Builder<ComparisonShare>
     */
    public function prunable(): Builder
    {
        $hari = config('hiking.share_links.prune_after_days');

        if (! is_numeric($hari)) {
            return static::query()->whereKey([]);
        }

        $batas = now()->subDays((int) $hari);

        return static::query()->where(fn (Builder $query) => $query->where('expires_at', '<', $batas)->orWhere('revoked_at', '<', $batas));
    }
}
```

`app/Services/SchedulerHealthService.php`:

5. Ganti:

```php
     *
     * @var array<string, array{judul: string, jadwal: string, toleransi_jam: int}>
```

dengan:

```php
     *
     * Jam di label ditulis dalam WIB dari ekspresi tugasnya, yang dibaca penjadwal dalam zona aplikasi
     * (UTC, config/app.php); ScheduleLabelTest mencocokkan keduanya (temuan audit W9).
     *
     * @var array<string, array{judul: string, jadwal: string, toleransi_jam: int}>
```

6. Ganti:

```php
            'jadwal' => 'Dua kali sehari, pukul 06.00 dan 18.00 WIB',
```

dengan:

```php
            'jadwal' => 'Dua kali sehari, pukul 13.00 dan 01.00 WIB',
```

7. Ganti:

```php
            'jadwal' => 'Setiap hari pukul 07.00 WIB',
```

dengan:

```php
            'jadwal' => 'Setiap hari pukul 14.00 WIB',
```

8. Ganti:

```php
            'toleransi_jam' => 3,
```

dengan:

```php
            'toleransi_jam' => 3,
        ],
        'model:prune' => [
            'judul' => 'Pemangkasan tautan bagikan yang berakhir',
            'jadwal' => 'Setiap hari pukul 03.00 WIB',
            'toleransi_jam' => 26,
```

`config/hiking.php`:

9. Ganti:

```php
        'views_per_minute' => 60,
```

dengan:

```php
        'views_per_minute' => 60,

        // Berapa hari baris tautan yang sudah berakhir (dicabut atau kedaluwarsa) disimpan sebelum
        // dipangkas tugas harian (titipan W3 M8 untuk W9). Kosong berarti tidak ada yang dipangkas:
        // lama simpan data pribadi diputus pemilik di docs/KEBIJAKAN-DATA-PRIBADI.md bagian 3,
        // bukan di sini (butir izin akhir).
        'prune_after_days' => env('SHARE_LINK_PRUNE_AFTER_DAYS'),
```

`routes/console.php`:

10. Ganti:

```php
<?php

use Illuminate\Foundation\Inspiring;
```

dengan:

```php
<?php

use App\Models\ComparisonShare;
use Illuminate\Foundation\Inspiring;
```

11. Ganti:

```php
Schedule::command('trips:kirim-pengingat')->hourly()->withoutOverlapping(50);
```

dengan:

```php
Schedule::command('trips:kirim-pengingat')->hourly()->withoutOverlapping(50);

// Tautan bagikan yang sudah berakhir dipangkas setiap hari, tetapi hanya sesudah pemilik mengisi
// SHARE_LINK_PRUNE_AFTER_DAYS (ComparisonShare::prunable; docs/KEBIJAKAN-DATA-PRIBADI.md bagian 3).
// Tugasnya tetap terjadwal dan dipantau selama angkanya kosong, supaya menyalakannya tidak butuh deploy.
Schedule::command('model:prune', ['--model' => [ComparisonShare::class]])->dailyAt('20:00')->withoutOverlapping();
```

- [ ] **Step 4: Jalankan dan pastikan lulus.**

Run: `php artisan test --compact --filter='ShareLinkRetentionTest|ScheduleLabelTest|SchedulerHealthTest|SystemHealthPageTest|ComparisonShareTest|TripReminderTest'`
Expected: PASS; pada putaran ulang 70 test, 70 lulus.

- [ ] **Step 5: Format dan suite penuh.**

Run:
```bash
vendor/bin/pint --dirty --format agent
php artisan test --compact --testsuite=Unit,Feature
```
Expected: Pint lulus; suite 2.321 test, 2.319 lulus, 2 dilewati (sebelum tugas: 2.317).

- [ ] **Step 6: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 11: Klik-lewat: pratinjau setiap halaman, daftar periksa, dan penjaganya

Rencana induk W9 butir 1 dan spec v3 S1. Klik-lewat sungguhan dijalankan koordinator di Tugas 16 lewat harness
pratinjau (P-R2), jadi halaman tanpa pratinjau tidak dapat diperiksa sama sekali. Sampai W8, 29 dari 56 halaman React
belum punya pratinjau: halaman tamu dan akun, tujuan baru, rekomendasi, lapor kondisi, riwayat, ubah trip, jalur
menunggu, kontribusi, moderasi, dan konsol admin. Kelas pratinjau baru melengkapinya (W9-R17). Daftar periksa
`docs/audit/klik-lewat/2026-10-09-daftar-periksa-w9.md` memuat cakupan (390 dan 1440 px, terang, gelap, lapangan,
papan ketik, gerak dikurangi, `forced-colors`), cara menjalankan, status hasil (LULUS, MENUNGGU SERVER, GAGAL, TIDAK
BERLAKU), satu baris per halaman, dan keterbatasan tanpa Playwright dan Vitest. `PreviewCoverageTest` di suite Feature
gagal bila halaman React baru tidak masuk keduanya. Pratinjau "admin" membuat akun admin, moderator, dan pemandu
bersertifikat di basis data memori test saja; `DemoSeeder` tidak diubah.

**Files:**
- Create: `docs/audit/klik-lewat/2026-10-09-daftar-periksa-w9.md`
- Create: `tests/Feature/PreviewCoverageTest.php`
- Create: `tests/Pratinjau/HalamanPenutupPratinjauTest.php`

**Interfaces:**
- Consumes: `Tests\Pratinjau\PratinjauTestCase` (`tulisPratinjau()`, `sebagaiBudi()`, `ASAL`), `DemoSeeder::EMAIL_PENDAKI`, slug data contoh `gunung-gede` dan `gede-via-cibodas`, `RouteFitService::recommend()`.
- Produces: 29 berkas pratinjau baru di `public/_pratinjau/` (nama di daftar periksa); daftar periksa klik-lewat yang dibaca Tugas 12, 13, dan 16.

- [ ] **Step 1: Tulis test penjaga yang gagal.**

Buat `tests/Feature/PreviewCoverageTest.php`:

```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Setiap halaman React punya pratinjau (klik-lewat W9; P-R2).
 *
 * Klik-lewat dan tangkapan memakai harness Pratinjau, bukan basis data lokal, jadi halaman yang
 * tidak dirender suite Pratinjau tidak pernah diperiksa di peramban. Suite itu tidak dijalankan
 * CI; test ini berjalan di suite Feature dan gagal ketika halaman baru ditambahkan tanpa
 * pratinjau. Halaman dianggap tercakup bila nama komponennya muncul sebagai string di salah
 * satu berkas tests/Pratinjau, yaitu argumen tulisPratinjau() atau isi penyedia datanya.
 */
class PreviewCoverageTest extends TestCase
{
    public function test_every_react_page_is_rendered_by_the_preview_suite(): void
    {
        $pratinjau = collect(File::files(base_path('tests/Pratinjau')))->map(fn ($berkas) => $berkas->getContents())->implode("\n");
        $tanpaPratinjau = [];

        foreach (File::allFiles(resource_path('js/pages')) as $berkas) {
            $komponen = str_replace('\\', '/', substr($berkas->getRelativePathname(), 0, -strlen('.tsx')));

            if (! str_contains($pratinjau, "'".$komponen."'")) {
                $tanpaPratinjau[] = $komponen;
            }
        }

        $this->assertSame([], $tanpaPratinjau, 'Halaman tanpa pratinjau tidak dapat diklik-lewat.');
    }

    public function test_the_click_through_checklist_names_every_react_page(): void
    {
        $daftar = File::get(base_path('docs/audit/klik-lewat/2026-10-09-daftar-periksa-w9.md'));
        $tidakTercantum = [];

        foreach (File::allFiles(resource_path('js/pages')) as $berkas) {
            $komponen = str_replace('\\', '/', substr($berkas->getRelativePathname(), 0, -strlen('.tsx')));

            if (! str_contains($daftar, '| `'.$komponen.'` |')) {
                $tidakTercantum[] = $komponen;
            }
        }

        $this->assertSame([], $tidakTercantum, 'Halaman tanpa baris di daftar periksa klik-lewat.');
    }
}
```

- [ ] **Step 2: Jalankan dan pastikan gagal.**

Run: `php artisan test --compact --filter=PreviewCoverageTest`
Expected: FAIL; pada putaran ulang 2 test, 1 gagal, 1 error. Yang gagal: 29 halaman tanpa pratinjau, dan berkas daftar periksa belum ada.

- [ ] **Step 3: Implementasi.** Kelas pratinjau dibuat langsung di `tests/Pratinjau`; daftar periksa dibuat langsung.

Buat `docs/audit/klik-lewat/2026-10-09-daftar-periksa-w9.md`:

```markdown
# Klik-lewat W9: daftar periksa

Daftar ini dipakai koordinator di tugas verifikasi akhir W9 untuk ukuran berhasil S1 spec v3 bagian 2: setiap
layar ada dan setiap kontrol yang tampil berfungsi. Daftar ini bukan bukti. Bukti adalah tangkapan, keluaran
peramban, dan nama test yang dicatat di kolom hasil (R-023). Baris yang belum diisi bukti berstatus PERLU
KLARIFIKASI, bukan lulus.

## Cakupan

- Lebar 390 px dan 1440 px. Reflow diukur juga di 320 px untuk setiap halaman pendaki.
- Tema terang dan gelap untuk setiap halaman. Tema lapangan untuk mode lapangan, kartu darurat, dan halaman Mendaki.
- Jalur papan ketik: Tab mengikuti urutan tampilan, Enter atau Spasi mengaktifkan, Escape menutup lembar dan dialog,
  dan fokus selalu terlihat.
- `prefers-reduced-motion: reduce`: lembar, dialog, dan toast tetap muncul tanpa gerak panjang.
- `forced-colors: active`: status resmi tetap terbaca dari bentuk dan kata, dan cincin fokus tetap terlihat.

## Cara menjalankan

Semua perintah dijalankan di akar repo. Awalan basis data wajib, supaya tidak ada yang menyentuh `.env`.

1. `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`
2. `php artisan test --compact --testsuite=Pratinjau` menulis HTML ke `public/_pratinjau/`.
3. `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php -S 127.0.0.1:8100 -t public tests/Pratinjau/statis.php`
4. Buka `http://127.0.0.1:8100/_pratinjau/<nama>.html` untuk setiap nama di kolom Pratinjau.

Setiap halaman diperiksa dengan urutan yang sama:

1. Atur lebar dan skema warna, lalu periksa `window.innerWidth`. Emulasi tanpa lebar mengatur ulang ke 504 px.
2. Ambil pohon aksesibilitas (`take_snapshot`) dan catat setiap tombol, tautan, isian, dan sakelar di kerangka
   dan di `main`.
3. Klik atau aktifkan setiap kontrol dari daftar itu, satu per satu, lalu catat hasilnya dengan salah satu status:
   - **LULUS**: kontrol bekerja di pratinjau statis, misalnya membuka lembar, mengganti langkah, atau menyaring.
   - **MENUNGGU SERVER**: kontrol mengirim permintaan ke Laravel. Pratinjau statis tidak menjalankan `index.php`,
     jadi buktinya nama test Feature yang menguji rute itu.
   - **GAGAL**: kontrol tidak bekerja, terpotong, tertutup, atau fokusnya hilang. Sertakan tangkapannya.
   - **TIDAK BERLAKU**: kontrol tidak tampil pada keadaan data contoh itu.
4. Ukur `document.documentElement.scrollWidth` terhadap `clientWidth` di 320 px.
5. Simpan tangkapan sebagai `docs/audit/klik-lewat/w9/<nama>-<lebar>-<tema>.webp`.

## Matriks halaman

Setiap halaman React tercantum di sini. `PreviewCoverageTest` gagal bila halaman baru tidak masuk suite Pratinjau
atau tidak tercantum di tabel ini.

| Halaman | Pratinjau | Peran | Tema | Catatan pemeriksaan |
|---|---|---|---|---|
| `Welcome` | `tamu-beranda` | tamu | terang, gelap | Tautan masuk dan daftar |
| `Auth/Login` | `masuk` | tamu | terang, gelap | Isian dengan label, galat terikat isian, Masuk = MENUNGGU SERVER |
| `Auth/Register` | `daftar` | tamu | terang, gelap | Sama dengan Masuk |
| `Auth/ForgotPassword` | `lupa-sandi` | tamu | terang, gelap | Kirim tautan = MENUNGGU SERVER |
| `Auth/ResetPassword` | `atur-ulang-sandi` | tamu | terang, gelap | Isian sandi baru dan tombol tampilkan sandi |
| `Auth/VerifyEmail` | `verifikasi-surel` | akun belum terverifikasi | terang, gelap | Kirim ulang dan Keluar = MENUNGGU SERVER |
| `Auth/ConfirmPassword` | `konfirmasi-sandi` | pendaki | terang, gelap | Kalimat tanpa klaim keamanan (W9 Tugas 9) |
| `Dashboard` | `dasbor`, `dasbor-b0`, `dasbor-b1`, `dasbor-b3`, `dasbor-b4` | pendaki | terang, gelap | Satu kartu fase per keadaan, lonceng membuka lembar Pengingat, Escape kembali ke lonceng |
| `Onboarding/ProfileSetup` | `onboarding`, `onboarding-pengalaman`, `onboarding-rencana`, `onboarding-selesai` | pendaki baru | terang, gelap | Langkah maju dan mundur, pilihan tunggal dengan panah |
| `Trails/Index` | `jelajah`, `jelajah-mendaki`, `jelajah-kosong` | pendaki | terang, gelap | Saringan, chip tafsiran, peta hanya di 1440, simpan = MENUNGGU SERVER |
| `Trails/Show` | `detail-jalur` | pendaki | terang, gelap | Remah gunung 44 px, bar aksi tidak menutup isi, Laporkan kesalahan data membuka dialog |
| `Trails/Awaiting` | `jalur-menunggu` | pendaki | terang, gelap | Menyebut data yang ditunggu, tanpa rincian jalur |
| `Trails/Fit` | `kecocokan` | pendaki | terang, gelap | Tiga label publik tanpa angka (R-004, R-005) |
| `Trails/Saved` | `simpanan` | pendaki | terang, gelap | Catatan, baki banding, Bagikan membuka lembar |
| `Trails/Compare` | `bandingkan` | pendaki | terang, gelap | Tanpa pemenang (R-016) |
| `Public/SharedComparison` | `bagikan` | tamu | terang, gelap | Kalimat R-012 di bawah tabel |
| `Mountains/Show` | `gunung`, `gunung-berfoto` | pendaki | terang, gelap | Ikuti gunung = MENUNGGU SERVER, atribusi foto |
| `Community/TrailNews` | `kabar` | pendaki | terang, gelap | Resmi dan komunitas terpisah (R-010) |
| `Community/ConditionReport` | `lapor-kondisi` | pendaki | terang, gelap | Pilih jalur memuat ulang segmen, Kirim = MENUNGGU SERVER |
| `Community/HikingHistory` | `riwayat` | pendaki | terang, gelap | Tautan ke ringkasan setiap pendakian |
| `Community/HikeSummary` | `ringkasan`, `ringkasan-batal` | pendaki | terang, gelap | Ajakan lapor dan alasan batal |
| `Community/HikerProgress` | `progres`, `progres-kosong` | pendaki | terang, gelap | Perbandingan dengan diri sendiri saja (R-015) |
| `Community/TrailContribution` | `kontribusi` | pemandu bersertifikat | terang, gelap | Hanya jalur di kawasan kredensialnya |
| `Goals/Create` | `tujuan-baru` | pendaki | terang, gelap | Isian tujuan, Simpan = MENUNGGU SERVER |
| `Recommendations/Show` | `rekomendasi` | pendaki | terang, gelap | Tampilkan lebih banyak, label publik saja |
| `Trips/Index` | `trip` | pendaki | terang, gelap | Satu aksi utama di kepala, Lihat semua |
| `Trips/Create` | `rencanakan` | pendaki | terang, gelap | Langkah, sakelar pengingat, ganti jenis memuat ulang ringkasan |
| `Trips/Edit` | `ubah-trip` | pendaki | terang, gelap | Sama dengan Rencanakan |
| `Trips/Show` | `ruang-kerja`, `ruang-kerja-keselamatan` | pendaki | terang, gelap | Mulai pendakian primer hanya pada hari keberangkatan |
| `Trips/Preparation` | `persiapan` | pendaki | terang, gelap | Centang = MENUNGGU SERVER, saringan belum tersimpan di URL (Konflik W9) |
| `Trips/Readiness` | `cek-kesiapan` | pendaki | terang, gelap | Tinjauan pra-keberangkatan, kalimat R-012 dan R-014 |
| `Trips/Print` | `cetak` | pendaki | terang | Versi cetak, sketsa rute |
| `Trips/Hike` | `mode-lapangan`, `kartu-darurat` | pendaki di jalur | terang, gelap, lapangan | Tiba di pos dengan konfirmasi, kartu darurat 112 dan 115 |
| `Track/Index` | `mendaki`, `mendaki-paket` | pendaki | terang, gelap, lapangan | Pintu tab Mendaki, paket luring |
| `Profile/Edit` | `profil`, `profil-kosong` | pendaki | terang, gelap | Kalimat kata sandi tanpa klaim keamanan, Hapus akun membuka dialog |
| `Galat` | `galat-404`, `galat-429`, `galat-500`, `galat-429-muat-penuh` | siapa saja | terang, gelap | Tindakan keluar dari galat, waktu tunggu 429 |
| `Curator/Desk` | `meja-kurator` | kurator | terang, gelap | Antrean berkelompok, Tandai sudah dicek membuka dialog |
| `Curator/Status` | `catatan-kurator`, `catatan-kurator-draf` | kurator | terang, gelap | Persoalkan membuka dialog dengan fokus di isian alasan |
| `Curator/StatusForm` | `formulir-kurator` | kurator | terang, gelap | Galat terikat isian |
| `Curator/VolcanoForm` | `formulir-gunung-api` | kurator | terang, gelap | Atribusi PVMBG |
| `Moderation/Queue` | `moderasi` | moderator | terang, gelap | Setujui dan tolak = MENUNGGU SERVER |
| `Admin/Trails` | `admin-jalur` | admin | terang, gelap | Terbitkan dan arsipkan = MENUNGGU SERVER |
| `Admin/Checkpoints` | `admin-pos` | admin | terang, gelap | Urutan pos, pindah dengan papan ketik |
| `Admin/TrailGeometry` | `admin-geometri` | admin | terang, gelap | Unggah GPX = MENUNGGU SERVER |
| `Admin/Mountains` | `admin-gunung` | admin | terang, gelap | Penomoran halaman |
| `Admin/MountainPhoto` | `admin-foto-gunung` | admin | terang, gelap | Isian lisensi wajib |
| `Admin/OfficialStatuses` | `admin-status` | admin | terang, gelap | Antrean tinjau |
| `Admin/Permits` | `admin-izin` | admin | terang, gelap | Aturan izin per jalur atau gunung |
| `Admin/DataSources` | `admin-sumber` | admin | terang, gelap | Verifikasi sumber |
| `Admin/Authorities` | `admin-badan` | admin | terang, gelap | Keanggotaan kurator |
| `Admin/Credentials` | `admin-kredensial` | admin | terang, gelap | Keputusan kredensial |
| `Admin/AuditLogs` | `admin-audit` | admin | terang, gelap | Saringan log |
| `Admin/Analytics` | `admin-analitik` | admin | terang, gelap | Angka berasal dari sistem |
| `Admin/Health` | `admin-kesehatan` | admin | terang, gelap | Jam tugas dalam WIB sesuai jadwal (W9 Tugas 10) |
| `Admin/Usability` | `admin-kegunaan` | admin | terang, gelap | Materi uji kegunaan, hasil tidak dikarang |
| `Dev/Primitif` | `primitif` | pengembang lokal | terang, gelap | Semua primitif, fokus dan target 44 px |

Shell luring `offline` (Blade, pratinjau `luring`) diperiksa dengan cara yang sama: tombol Coba muat ulang memakai
`x-ui.button` (W9 Tugas 9).

## Keterbatasan

Perilaku di bawah ini tidak dapat dibuktikan di pratinjau statis dan tidak ada test klien otomatis, karena
Playwright (butir izin akhir 7, Q17) dan Vitest (butir izin akhir 25) belum dipasang. Statusnya MENUNGGU IZIN,
bukan lulus:

- toast Urungkan sesudah simpan dan fokusnya;
- antrean centang Persiapan dan muat ulang parsial Rencanakan;
- pemasangan service worker, `activate`, dan Clear-Site-Data di peramban sungguhan;
- kiriman POST, PUT, dan DELETE dari peramban, yang dijaga test Feature per rute.

## Hasil

Diisi koordinator. Satu baris per halaman dan lebar, dengan jumlah kontrol yang diklik per status dan tautan
tangkapannya.

| Halaman | Lebar | Tema | LULUS | MENUNGGU SERVER | GAGAL | Reflow 320 px | Bukti |
|---|---|---|---|---|---|---|---|
```

Buat `tests/Pratinjau/HalamanPenutupPratinjauTest.php`:

```php
<?php

namespace Tests\Pratinjau;

use App\Enums\AuthorityType;
use App\Enums\CredentialLevel;
use App\Enums\TripStatus;
use App\Enums\UserRole;
use App\Enums\VerificationStatus;
use App\Models\Authority;
use App\Models\ExpertCredential;
use App\Models\HikingGoal;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\RouteFitService;
use Database\Seeders\DemoSeeder;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Halaman yang belum punya pratinjau sampai W9 (klik-lewat W9, rencana induk W9 butir 1).
 *
 * Klik-lewat koordinator memakai harness ini, bukan basis data lokal (P-R2), jadi halaman
 * tanpa pratinjau tidak dapat diperiksa sama sekali. Kelas ini melengkapi 29 halaman: halaman
 * tamu dan akun, halaman pendaki yang belum dipotret gelombang sebelumnya, antrean moderasi,
 * dan konsol admin. PreviewCoverageTest menjaga setiap halaman React baru ikut masuk.
 */
class HalamanPenutupPratinjauTest extends PratinjauTestCase
{
    /**
     * Halaman tamu dan akun yang dibuka tanpa masuk.
     *
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function halamanTamu(): array
    {
        return [
            'beranda tamu' => ['tamu-beranda', '/', 'Welcome'],
            'masuk' => ['masuk', '/login', 'Auth/Login'],
            'daftar' => ['daftar', '/register', 'Auth/Register'],
            'lupa kata sandi' => ['lupa-sandi', '/forgot-password', 'Auth/ForgotPassword'],
            'atur ulang kata sandi' => ['atur-ulang-sandi', '/reset-password/token-contoh?email=budi%40contoh.test', 'Auth/ResetPassword'],
        ];
    }

    #[DataProvider('halamanTamu')]
    public function test_halaman_tamu(string $nama, string $alamat, string $komponen): void
    {
        $this->tulisPratinjau($nama, $this->get(self::ASAL.$alamat), $komponen);
    }

    public function test_verifikasi_surel(): void
    {
        $this->actingAs(User::factory()->unverified()->create());

        $this->tulisPratinjau('verifikasi-surel', $this->get(self::ASAL.'/verify-email'), 'Auth/VerifyEmail');
    }

    /**
     * Halaman pendaki yang belum dipotret, semuanya dari data contoh Budi.
     *
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function halamanPendaki(): array
    {
        return [
            'konfirmasi kata sandi' => ['konfirmasi-sandi', '/confirm-password', 'Auth/ConfirmPassword'],
            'tujuan baru' => ['tujuan-baru', '/goals/create', 'Goals/Create'],
            'riwayat' => ['riwayat', '/history', 'Community/HikingHistory'],
        ];
    }

    #[DataProvider('halamanPendaki')]
    public function test_halaman_pendaki(string $nama, string $alamat, string $komponen): void
    {
        $this->sebagaiBudi();

        $this->tulisPratinjau($nama, $this->get(self::ASAL.$alamat), $komponen);
    }

    public function test_lapor_kondisi(): void
    {
        $this->sebagaiBudi();
        $jalur = Trail::where('slug', 'gede-via-cibodas')->sole();

        $this->tulisPratinjau('lapor-kondisi', $this->get(self::ASAL.'/reports/create?trail='.$jalur->id), 'Community/ConditionReport');
    }

    public function test_ubah_trip(): void
    {
        $budi = $this->sebagaiBudi();
        $trip = TripPlan::where('user_id', $budi->id)->where('status', TripStatus::PLANNED->value)->sole();

        $this->tulisPratinjau('ubah-trip', $this->get(self::ASAL.'/trips/'.$trip->id.'/edit'), 'Trips/Edit');
    }

    /**
     * Hasil rekomendasi untuk tujuan camping Budi, dinilai atas jalur terbit data contoh.
     */
    public function test_rekomendasi(): void
    {
        $budi = $this->sebagaiBudi();
        $run = app(RouteFitService::class)->recommend($budi, HikingGoal::factory()->create(['user_id' => $budi->id]));

        $this->tulisPratinjau('rekomendasi', $this->get(self::ASAL.'/recommendations/'.$run->id), 'Recommendations/Show');
    }

    /**
     * Jalur yang belum terbit: halamannya menyebut apa yang masih ditunggu, bukan rincian jalur.
     */
    public function test_jalur_menunggu(): void
    {
        $this->sebagaiBudi();
        $jalur = Trail::factory()->for(Mountain::where('slug', 'gunung-gede')->sole())->create(['name' => 'Gede via Gunung Putri (menunggu)', 'slug' => 'gede-via-gunung-putri-menunggu']);

        $this->tulisPratinjau('jalur-menunggu', $this->get(self::ASAL.'/trails/'.$jalur->slug), 'Trails/Awaiting');
    }

    /**
     * Pemandu bersertifikat untuk kawasan Gunung Gede: pintunya hanya terbuka bagi pemegang
     * kredensial yang berlaku (TrailContributionTest), jadi pratinjaunya membuat satu.
     */
    public function test_kontribusi(): void
    {
        $this->seed(DemoSeeder::class);
        $pemandu = User::factory()->create(['name' => 'Pemandu Contoh', 'role' => UserRole::HIKER->value]);
        $penerbit = Authority::create(['name' => 'Asosiasi pemandu contoh', 'slug' => 'asosiasi-pemandu-contoh', 'abbreviation' => 'APGI', 'type' => AuthorityType::PROFESSIONAL_ASSOCIATION->value]);
        ExpertCredential::create([
            'user_id' => $pemandu->id,
            'issuing_authority_id' => $penerbit->id,
            'level' => CredentialLevel::AHLI->value,
            'certificate_number' => 'CONTOH/2026/0001',
            'issued_at' => now()->subYear()->toDateString(),
            'expires_at' => now()->addYears(2)->toDateString(),
            'verification_status' => VerificationStatus::VERIFIED->value,
            'verified_at' => now(),
        ])->mountains()->attach(Mountain::where('slug', 'gunung-gede')->sole()->id);
        $this->actingAs($pemandu);

        $this->tulisPratinjau('kontribusi', $this->get(self::ASAL.'/kontribusi'), 'Community/TrailContribution');
    }

    public function test_moderasi(): void
    {
        $this->seed(DemoSeeder::class);
        $this->actingAs(User::factory()->create(['role' => UserRole::MODERATOR->value]));

        $this->tulisPratinjau('moderasi', $this->get(self::ASAL.'/moderation'), 'Moderation/Queue');
    }

    /**
     * Konsol admin dengan data contoh.
     *
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function halamanAdmin(): array
    {
        return [
            'analitik' => ['admin-analitik', '/admin/analytics', 'Admin/Analytics'],
            'audit' => ['admin-audit', '/admin/audit-logs', 'Admin/AuditLogs'],
            'badan resmi' => ['admin-badan', '/admin/authorities', 'Admin/Authorities'],
            'kredensial' => ['admin-kredensial', '/admin/credentials', 'Admin/Credentials'],
            'kesehatan' => ['admin-kesehatan', '/admin/health', 'Admin/Health'],
            'gunung' => ['admin-gunung', '/admin/mountains', 'Admin/Mountains'],
            'status resmi' => ['admin-status', '/admin/official-statuses', 'Admin/OfficialStatuses'],
            'izin' => ['admin-izin', '/admin/permits', 'Admin/Permits'],
            'sumber' => ['admin-sumber', '/admin/sources', 'Admin/DataSources'],
            'jalur' => ['admin-jalur', '/admin/trails', 'Admin/Trails'],
            'uji kegunaan' => ['admin-kegunaan', '/admin/usability', 'Admin/Usability'],
        ];
    }

    #[DataProvider('halamanAdmin')]
    public function test_halaman_admin(string $nama, string $alamat, string $komponen): void
    {
        $this->sebagaiAdmin();

        $this->tulisPratinjau($nama, $this->get(self::ASAL.$alamat), $komponen);
    }

    public function test_admin_foto_gunung(): void
    {
        $this->sebagaiAdmin();
        $gunung = Mountain::where('slug', 'gunung-gede')->sole();

        $this->tulisPratinjau('admin-foto-gunung', $this->get(self::ASAL.'/admin/mountains/'.$gunung->id.'/foto'), 'Admin/MountainPhoto');
    }

    public function test_admin_pos_dan_geometri(): void
    {
        $this->sebagaiAdmin();
        $jalur = Trail::where('slug', 'gede-via-cibodas')->sole();

        $this->tulisPratinjau('admin-pos', $this->get(self::ASAL.'/jalur/'.$jalur->id.'/pos'), 'Admin/Checkpoints');
        $this->tulisPratinjau('admin-geometri', $this->get(self::ASAL.'/jalur/'.$jalur->id.'/geometri'), 'Admin/TrailGeometry');
    }

    private function sebagaiAdmin(): User
    {
        $this->seed(DemoSeeder::class);
        $admin = User::factory()->create(['name' => 'Admin Contoh', 'role' => UserRole::ADMIN->value]);
        $this->actingAs($admin);

        return $admin;
    }
}
```

- [ ] **Step 4: Jalankan dan pastikan lulus.**

Run:
```bash
php artisan test --compact --filter=PreviewCoverageTest
php artisan test --compact --testsuite=Pratinjau
```
Expected: PASS; pada putaran ulang 2 test, 2 lulus; Pratinjau 75 test lulus. Setiap nama berkas di kolom Pratinjau daftar periksa ada di
`public/_pratinjau/`, dan sebaliknya.

- [ ] **Step 5: Format dan suite penuh.**

Run:
```bash
vendor/bin/pint --dirty --format agent
php artisan test --compact --testsuite=Unit,Feature
```
Expected: Pint lulus; suite 2.323 test, 2.321 lulus, 2 dilewati (sebelum tugas: 2.321).

- [ ] **Step 6: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 12: Audit aksesibilitas WCAG 2.2 AA dan keamanan, dengan rujukan test yang diperiksa

Rencana induk W9 butir 3. Untuk setiap butir daftar test keamanan kritis `CLAUDE.md` bagian 4 (auth bypass, IDOR,
eskalasi hak, mass assignment, unggahan tidak aman, rate limiting, kebocoran secret, RLS allow dan deny, akses lokasi
privat, privasi pada penghapusan akun) dan setiap kriteria WCAG 2.2 AA yang relevan, laporan memetakan test yang sudah
ada dengan nama berkas dan metodenya, tingkat buktinya (R-023), dan celahnya. RLS butuh Supabase dan suite Spatial
belum pernah berjalan, jadi keduanya PERLU KLARIFIKASI, bukan lulus (W9-R18). Pemetaan menemukan satu celah nyata yang
ditutup di sini: aturan umum mass assignment tidak dijaga (test per fitur ada, tetapi tidak ada yang menolak model
berikutnya yang membuka semua kolomnya atau pengendali yang meneruskan seluruh isi permintaan). Peran lewat formulir
profil sudah dijaga W8 (`AuthorityMemberPolicyTest::test_a_hiker_cannot_promote_themselves_through_the_profile`), jadi
tidak digandakan. `AuditCitationTest` menolak rujukan `berkas::test` yang tidak ada, sehingga laporan tidak menyatakan
lulus atas test yang sudah berganti nama.

**Files:**
- Create: `docs/audit/aksesibilitas-w9.md`
- Create: `docs/audit/keamanan-w9.md`
- Create: `tests/Feature/AuditCitationTest.php`
- Create: `tests/Feature/MassAssignmentSweepTest.php`

**Interfaces:**
- Consumes: nama test dari Tugas 1 sampai 11 dan W1 sampai W8 (daftar di laporan).
- Produces: `docs/audit/keamanan-w9.md` dan `docs/audit/aksesibilitas-w9.md` yang dirujuk Tugas 13 (ARCHITECTURE), Tugas 15 (baris 2.4.3), dan Tugas 16 (audit 002).

- [ ] **Step 1: Tulis test yang gagal.**

Buat `tests/Feature/AuditCitationTest.php`:

```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Audit W9 hanya menyebut test yang benar-benar ada (R-023: tanpa bukti, tidak ada PASS).
 *
 * Laporan keamanan dan aksesibilitas menyebut buktinya sebagai `tests/...php::test_nama`. Nama
 * test yang salah ketik atau sudah diganti membuat butir terbaca lulus padahal tidak ada yang
 * menjaganya; test ini menolak rujukan seperti itu dan laporan tanpa rujukan sama sekali.
 */
class AuditCitationTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function laporan(): array
    {
        return [
            'keamanan' => ['docs/audit/keamanan-w9.md'],
            'aksesibilitas' => ['docs/audit/aksesibilitas-w9.md'],
        ];
    }

    #[DataProvider('laporan')]
    public function test_every_cited_test_exists(string $laporan): void
    {
        preg_match_all('/`(tests\/[A-Za-z\/]+\.php)::(test_[a-z0-9_]+)`/', File::get(base_path($laporan)), $rujukan, PREG_SET_ORDER);
        $hilang = [];

        foreach ($rujukan as [, $berkas, $test]) {
            if (! is_file(base_path($berkas)) || ! str_contains(File::get(base_path($berkas)), 'function '.$test.'(')) {
                $hilang[] = $berkas.'::'.$test;
            }
        }

        $this->assertGreaterThanOrEqual(10, count($rujukan), 'Laporan audit tanpa rujukan test.');
        $this->assertSame([], $hilang);
    }
}
```

Buat `tests/Feature/MassAssignmentSweepTest.php`:

```php
<?php

namespace Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use Tests\TestCase;

/**
 * Proteksi mass assignment sebagai aturan umum (CLAUDE.md bagian 4; audit keamanan W9,
 * docs/audit/keamanan-w9.md).
 *
 * Test per fitur sudah menjaga kolom tertentu: asal status resmi, pemilik dan status trip, peran
 * pendaftar, dan peran lewat formulir profil. Yang belum dijaga adalah aturan umumnya, yang
 * menentukan apakah model atau pengendali berikutnya ikut aman: tidak ada model yang membuka
 * semua kolomnya, dan tidak ada kode HTTP yang meneruskan seluruh isi permintaan ke model alih-alih
 * isian yang sudah divalidasi.
 */
class MassAssignmentSweepTest extends TestCase
{
    public function test_no_model_opens_every_column_to_mass_assignment(): void
    {
        $terbuka = [];

        foreach (File::files(app_path('Models')) as $berkas) {
            $kelas = 'App\\Models\\'.$berkas->getFilenameWithoutExtension();

            if (! is_subclass_of($kelas, Model::class) || (new ReflectionClass($kelas))->isAbstract()) {
                continue;
            }

            if ((new $kelas)->getGuarded() === []) {
                $terbuka[] = $kelas;
            }
        }

        $this->assertSame([], $terbuka);
        $this->assertDoesNotMatchRegularExpression('/Model::unguard|->unguard\(/', implode("\n", array_map(fn ($berkas) => $berkas->getContents(), File::allFiles(app_path()))));
    }

    public function test_no_http_code_passes_the_whole_request_on(): void
    {
        $pelanggar = [];

        foreach (File::allFiles(app_path('Http')) as $berkas) {
            if (preg_match('/(\$request|request\(\))->(all|input)\(\)/', $berkas->getContents())) {
                $pelanggar[] = $berkas->getRelativePathname();
            }
        }

        $this->assertSame([], $pelanggar);
    }
}
```

- [ ] **Step 2: Jalankan dan pastikan gagal.**

Run: `php artisan test --compact --filter='MassAssignmentSweepTest|AuditCitationTest'`
Expected: FAIL; pada putaran ulang 4 test, 2 lulus, 2 error. `AuditCitationTest` galat karena kedua laporan belum ada. Kedua test `MassAssignmentSweepTest`
lulus sejak awal (kode sudah benar); dibuktikan merah dengan mutasi di simulasi: `protected $guarded = [];` di
`ComparisonShare` membuat `test_no_model_opens_every_column_to_mass_assignment` gagal.

- [ ] **Step 3: Tulis kedua laporan.**

Buat `docs/audit/aksesibilitas-w9.md`:

```markdown
# Audit aksesibilitas W9 (WCAG 2.2 AA)

Audit ini memetakan kriteria WCAG 2.2 AA yang relevan bagi Memount ke test yang menjaganya dan ke langkah
klik-lewat yang membuktikannya di layar (`CLAUDE.md` bagian 6). Kelas CSS bukan bukti: test sumber menjaga bahwa
aturan ditulis, sedangkan perilaku di layar dibuktikan klik-lewat koordinator di
`docs/audit/klik-lewat/2026-10-09-daftar-periksa-w9.md`. Setiap rujukan `berkas::test` diperiksa `AuditCitationTest`.

Tingkat bukti mengikuti R-023: FACT untuk test yang lulus atau keluaran peramban yang dicatat, PERLU KLARIFIKASI
untuk yang belum diperiksa di layar. Uji dengan pembaca layar dan pengguna nyata mengikuti
`docs/PROTOKOL-UJI-KEGUNAAN.md` dan hasilnya tidak dikarang.

| Kriteria | Bukti otomatis | Bukti di layar | Tingkat |
|---|---|---|---|
| 1.1.1 Konten nontext | `tests/Feature/TrustGrammarTest.php::test_the_fit_label_pairs_an_icon_with_a_word_and_never_a_number` | Ikon dekoratif tidak dibacakan di pohon aksesibilitas | FACT untuk sumber; layar PERLU KLARIFIKASI |
| 1.3.1 Info dan relasi | `tests/Feature/AccessibilityTest.php::test_each_page_has_exactly_one_first_level_heading`, `tests/Feature/KeyboardAndScreenReaderTest.php::test_every_page_has_a_main_landmark`, `tests/Feature/KeyboardAndScreenReaderTest.php::test_every_error_and_hint_in_the_react_ui_is_referenced_by_its_control` | Pohon aksesibilitas tiap pratinjau | FACT untuk sumber |
| 1.4.1 Penggunaan warna | `tests/Feature/AccessibilityTest.php::test_status_is_never_conveyed_by_colour_alone`, `tests/Feature/TrustGrammarTest.php::test_the_access_status_is_carried_by_shape_and_word` | Pratinjau dengan `forced-colors: active` | FACT untuk sumber |
| 1.4.3 Kontras minimum | `tests/Feature/ColourContrastTest.php::test_text_pairings_meet_aa`, `tests/Feature/ColourContrastTest.php::test_field_themes_meet_their_thresholds`, `tests/Feature/ColourContrastTest.php::test_every_pairing_holds_in_each_combination_of_app_and_field_theme` | Teks di atas foto gunung diukur di titik terang | FACT untuk token |
| 1.4.4 dan 1.4.10 Ubah ukuran dan reflow | `tests/Feature/TapTargetTest.php::test_no_grid_forces_three_columns_on_a_narrow_screen` | `scrollWidth` sama dengan `clientWidth` di 320 px pada setiap halaman pendaki | PERLU KLARIFIKASI sampai klik-lewat |
| 1.4.11 Kontras nonteks | `tests/Feature/ColourContrastTest.php::test_non_text_pairings_meet_the_ui_threshold`, `tests/Feature/ColourContrastTest.php::test_the_border_of_an_interactive_control_is_visible`, `tests/Feature/ScreenReaderAndTargetTest.php::test_empty_progress_segments_are_outlined_with_the_control_border` | Ruas kosong langkah trip terlihat di kedua tema | FACT untuk token |
| 1.4.12 Spasi teks | tidak ada test otomatis | Pratinjau dengan bookmarklet spasi teks WCAG: tidak ada teks terpotong | PERLU KLARIFIKASI |
| 2.1.1 Papan ketik | `tests/Feature/KeyboardAndScreenReaderTest.php::test_a_keyboard_user_can_skip_straight_to_the_content` | Jalur papan ketik per halaman di daftar periksa | PERLU KLARIFIKASI sampai klik-lewat |
| 2.3.3 Animasi dari interaksi | `tests/Feature/MotionTest.php::test_reduced_motion_is_honoured_everywhere_at_once`, `tests/Feature/MotionTest.php::test_reducing_motion_shortens_it_rather_than_removing_the_feedback` | Lembar dan toast dengan `prefers-reduced-motion: reduce` | FACT untuk sumber |
| 2.4.3 Urutan fokus | `tests/Feature/ActiveHikeStripTest.php` menjaga strip pendakian dijangkau sesudah isi halaman | Tab mengikuti urutan tampilan | PERLU KLARIFIKASI sampai klik-lewat |
| 2.4.7 Fokus terlihat | `tests/Feature/FocusIndicatorTest.php::test_nothing_removes_the_focus_outline_without_replacing_it`, `tests/Feature/ColourContrastTest.php::test_the_focus_ring_is_distinguishable_from_the_page` | Cincin fokus di tema terang, gelap, dan lapangan | FACT untuk sumber |
| 2.4.11 Fokus tidak tertutup | tidak ada test otomatis; `scroll-padding` menghitung lapisan kerangka (W2) | Isian terakhir Rencanakan tidak tertutup bar aksi saat difokus | PERLU KLARIFIKASI |
| 2.5.8 Ukuran target | `tests/Feature/TapTargetTest.php::test_every_react_button_and_in_app_link_can_be_hit_by_a_thumb`, `tests/Feature/ScreenReaderAndTargetTest.php::test_the_mountain_link_in_the_trail_header_is_a_full_touch_target` | Kendali di `main` paling sedikit 44 x 44 px | FACT untuk sumber |
| 3.1.1 Bahasa halaman | `tests/Feature/AccessibilityTest.php::test_the_language_of_the_document_is_declared` | Atribut `lang="id"` | FACT |
| 3.3.1 dan 3.3.3 Identifikasi dan saran galat | `tests/Feature/KeyboardAndScreenReaderTest.php::test_a_field_error_is_tied_to_the_input_it_belongs_to`, `tests/Feature/InertiaErrorPageTest.php::test_a_validation_error_still_returns_to_the_form` | Galat dibacakan dan fokus pindah ke isian pertama yang salah | FACT untuk sumber |
| 3.3.2 Label atau instruksi | `tests/Feature/AccessibilityTest.php::test_every_form_control_on_the_core_flow_has_a_label`, `tests/Feature/AccountPagesSourceTest.php::test_every_checkbox_on_the_account_pages_has_a_label` | Nama aksesibel setiap isian di pohon aksesibilitas | FACT untuk sumber |
| 4.1.2 Nama, peran, nilai | `tests/Feature/AccessibilityTest.php::test_the_primary_button_carries_a_visible_focus_ring_and_a_touch_target` | Sakelar dan pilihan tunggal membacakan keadaannya | PERLU KLARIFIKASI di layar |
| 4.1.3 Pesan status | `tests/Feature/ScreenReaderAndTargetTest.php::test_a_save_announced_by_its_undo_toast_is_not_announced_again_by_the_page`, `tests/Feature/ScreenReaderAndTargetTest.php::test_a_removal_announced_by_its_undo_toast_is_not_announced_again_by_the_page`, `tests/Feature/InertiaErrorPageTest.php::test_a_throttled_inertia_action_says_how_long_to_wait_and_keeps_the_page_it_came_from` | Satu pengumuman per aksi dengan pembaca layar | FACT untuk props; pembaca layar PERLU KLARIFIKASI |

## Kata yang dibaca pendaki

Bagian ini bukan kriteria WCAG, tetapi menjaga keterbacaan teks yang sama: jam bertitik dan berzona, tahun pada
tanggal di luar tahun berjalan, kosakata Indonesia di kalimat mesin, dan tanpa kata klaim keselamatan.

- `tests/Feature/IndonesianTimeFormatTest.php::test_no_screen_writes_a_clock_time_with_a_colon`
- `tests/Feature/IndonesianTimeFormatTest.php::test_a_short_date_names_its_year_only_outside_the_current_year`
- `tests/Feature/MachineVocabularyTest.php::test_no_screen_says_elevation_gain_or_shouts_a_status_value`
- `tests/Feature/SafetyClaimWordTest.php::test_no_text_a_hiker_reads_claims_safety_or_certainty`
- `tests/Feature/ErrorPagesTest.php::test_the_page_speaks_indonesian_and_offers_a_way_out`

## Yang menunggu

- Uji pembaca layar (TalkBack di Android, NVDA di Windows) dan uji kegunaan 5 sampai 8 pendaki (Q15) dijalankan
  pemilik dengan `docs/PROTOKOL-UJI-KEGUNAAN.md`.
- Perilaku klien yang butuh Playwright atau Vitest (fokus sesudah Urungkan, antrean Persiapan) tercatat sebagai
  keterbatasan di daftar periksa klik-lewat.
```

Buat `docs/audit/keamanan-w9.md`:

```markdown
# Audit keamanan W9

Audit ini memetakan daftar test keamanan kritis `CLAUDE.md` bagian 4 ke test yang ada, menandai tingkat buktinya
(R-023), dan mencatat celah yang ditutup gelombang W9. Setiap rujukan berbentuk `berkas::test` diperiksa
`AuditCitationTest`, jadi test yang dirujuk benar-benar ada.

Tingkat bukti:
- **FACT**: test Unit atau Feature yang lulus di suite SQLite, atau keluaran perintah yang dicatat.
- **INFERENCE**: kesimpulan dari kode yang dibaca, belum diuji langsung.
- **PERLU KLARIFIKASI**: butuh PostgreSQL, Supabase, jaringan, atau izin pemilik. Belum boleh disebut lulus.

Test di `tests/Spatial` hanya berjalan dengan `SPATIAL_TEST_DSN` dan belum pernah berjalan di mesin agen, karena
tidak ada PostgreSQL lokal. Semuanya PERLU KLARIFIKASI sampai CI Spatial lulus sesudah izin push (R-024).

## Daftar kritis

| Butir | Bukti | Tingkat | Celah dan tindakan W9 |
|---|---|---|---|
| Auth bypass | `tests/Feature/RouteAccessMatrixTest.php::test_every_route_is_either_public_by_design_or_closed_to_guests`, `tests/Feature/WriteRouteGuardTest.php::test_every_write_route_needs_a_session_unless_it_opens_one`, `tests/Feature/Auth/AuthenticationTest.php::test_the_sixth_attempt_is_locked_out_even_with_the_right_password`, `tests/Feature/Auth/AuthenticationTest.php::test_a_guest_cannot_call_logout`, `tests/Feature/TripOwnershipTest.php::test_guests_are_redirected_to_login` | FACT | Sapuan rute tulis W9 Tugas 5 menutup celah aturan per rute. Sisa: rute `livewire/update` dan `livewire/upload-file` dari paket Livewire dan Volt yang tidak dipakai komponen apa pun masih terpasang tanpa sesi (Konflik W9) |
| IDOR | `tests/Feature/TripOwnershipTest.php::test_another_user_cannot_open_someone_elses_trip`, `tests/Feature/TripOwnershipTest.php::test_another_user_cannot_act_on_someone_elses_trip`, `tests/Feature/TripOwnershipTest.php::test_a_preparation_item_is_only_found_within_its_own_trip`, `tests/Feature/ReportPhotoAccessTest.php::test_a_stranger_cannot_open_a_pending_report_photo`, `tests/Feature/ProfilePhotoTest.php::test_only_the_owner_is_served_the_photo`, `tests/Feature/ConsiderationTest.php::test_one_hikers_shortlist_is_invisible_to_another`, `tests/Feature/ComparisonShareTest.php::test_only_the_owner_can_revoke_and_others_get_not_found`, `tests/Feature/TripReminderTest.php::test_another_user_cannot_touch_someone_elses_reminders`, `tests/Feature/HikeTrackRecordingTest.php::test_a_hiker_cannot_write_into_someone_elses_track` | FACT | Trip orang lain dijawab 403, bukan 404, sampai pemilik memutus Q5 |
| Eskalasi hak | `tests/Feature/AdminAccessTest.php::test_hikers_cannot_reach_admin_pages`, `tests/Feature/AdminAccessTest.php::test_moderators_cannot_reach_admin_pages`, `tests/Feature/RouteAccessMatrixTest.php::test_an_ordinary_hiker_cannot_reach_any_admin_page`, `tests/Feature/Auth/RegistrationTest.php::test_a_registrant_cannot_make_themselves_an_admin`, `tests/Feature/AuthorityMemberPolicyTest.php::test_a_hiker_cannot_promote_themselves_through_the_profile`, `tests/Feature/AuthorityMemberPolicyTest.php::test_the_recorder_never_verifies_their_own_record`, `tests/Feature/ExpertGeometryAccessTest.php::test_the_credential_does_not_reach_another_mountain` | FACT | Tidak ada celah baru |
| Mass assignment | `tests/Feature/MassAssignmentSweepTest.php::test_no_model_opens_every_column_to_mass_assignment`, `tests/Feature/MassAssignmentSweepTest.php::test_no_http_code_passes_the_whole_request_on`, `tests/Feature/OfficialStatusVisibilityTest.php::test_provenance_controls_are_not_mass_assignable`, `tests/Feature/TripLifecycleTest.php::test_ownership_and_status_cannot_be_smuggled_in` | FACT | W9 menambah dua sapuan umum. Kolom `role` ada di daftar fillable `User`; yang menjaganya adalah aturan validasi formulir, dan test peran di baris ini membuktikannya |
| Unggahan tidak aman | `tests/Feature/ProfilePhotoTest.php::test_malicious_or_oversized_uploads_are_refused_without_a_trace`, `tests/Feature/ProfilePhotoTest.php::test_an_uploaded_photo_is_stored_without_metadata_and_scaled_down`, `tests/Feature/MountainPhotoLicenseTest.php::test_svg_scripts_and_tiny_images_are_refused`, `tests/Feature/EvidenceArchiveTest.php::test_only_real_images_and_pdfs_within_the_limit_are_accepted`, `tests/Feature/ReportPhotoAccessTest.php::test_the_photo_disk_is_private_by_default`, `tests/Feature/WriteRouteGuardTest.php::test_private_files_have_no_signed_url_route` | FACT | W9 Tugas 5 mematikan `serve` disk lokal, sehingga rute `storage/{path}` bertanda tangan bawaan Laravel hilang. Disk produksi bukti, foto gunung, dan foto profil menunggu keputusan pemilik |
| Rate limiting | `tests/Feature/RateLimitTest.php::test_the_limit_is_per_user_not_global`, `tests/Feature/WriteRouteGuardTest.php::test_every_write_route_is_rate_limited_or_says_why_not`, `tests/Feature/WriteRouteGuardTest.php::test_registration_is_rate_limited_per_address`, `tests/Feature/WriteRouteGuardTest.php::test_asking_for_a_password_reset_link_is_rate_limited_per_address`, `tests/Feature/ComparisonShareTest.php::test_opening_links_is_rate_limited_per_address`, `tests/Feature/TrustedProxyTest.php::test_a_forwarded_address_is_ignored_while_no_proxy_is_trusted` | FACT | Batas per alamat IP baru benar di balik proxy bila `TRUSTED_PROXIES` diisi sesuai platform deploy (butir izin akhir). Konsol admin, moderasi, dan kurator tanpa batas laju sesuai ruling W8 dan tercatat di daftar `TANPA_THROTTLE` |
| Kebocoran secret | `tests/Feature/SecretsNotInRepoTest.php::test_no_committable_file_contains_the_pattern`, `tests/Feature/SecretsNotInRepoTest.php::test_the_example_environment_file_carries_no_real_values`, `tests/Feature/InertiaErrorPageTest.php::test_a_server_error_hides_its_details_from_hikers_but_not_from_developers`, `tests/Feature/LocationPrivacyTest.php::test_internal_fit_score_is_never_serialised_to_the_client` | FACT untuk isi repo; PERLU KLARIFIKASI untuk riwayat git yang sudah di-push | Ref proyek Supabase dan Workspace ID sudah terbaca publik (Q21), keduanya pengenal, bukan kredensial |
| RLS allow dan deny | `tests/Spatial/ComparisonShareSchemaTest.php`, `tests/Spatial/AuthorityMemberSchemaTest.php`, `tests/Spatial/DataErrorReportSchemaTest.php`, dan `tests/Spatial/MountainPhotoSchemaTest.php` memeriksa `relrowsecurity` tabel baru | PERLU KLARIFIKASI | Suite Spatial belum pernah berjalan. Uji allow dan deny untuk peran `anon` dan `authenticated` Supabase butuh akses Supabase (Q22). Tabel lama sesudah migrasi penutupan PostgREST belum semuanya menyalakan RLS di migrasinya |
| Akses lokasi privat | `tests/Feature/LocationPrivacyTest.php::test_no_other_client_code_requests_geolocation`, `tests/Feature/LocationPrivacyTest.php::test_hiking_session_location_is_hidden_from_serialisation`, `tests/Feature/HikeTrackRecordingTest.php::test_nothing_is_stored_when_the_hiker_never_turned_recording_on`, `tests/Feature/HikeTrackRecordingTest.php::test_one_hiker_cannot_delete_another_hikers_track`, `tests/Feature/TrackPreferenceTest.php::test_recording_starts_off_and_can_be_turned_on` | FACT | Tidak ada celah baru |
| Privasi pada penghapusan akun | `tests/Feature/PersonalDataDeletionTest.php::test_deleting_the_account_removes_every_w7_personal_record`, `tests/Feature/AccountDeletionRetentionTest.php::test_an_anonymised_report_shows_a_neutral_author_label`, `tests/Feature/ProfilePhotoTest.php::test_deleting_the_account_deletes_the_photo_file`, `tests/Feature/HikeTrackRecordingTest.php::test_deleting_the_account_takes_the_track_with_it`, `tests/Feature/HistoryPrivacyTest.php::test_with_the_switch_on_pages_encrypt_their_history_and_logging_out_clears_it`, `tests/Feature/ShareLinkRetentionTest.php::test_links_that_ended_before_the_retention_are_pruned_and_the_rest_stay` | FACT di SQLite; PERLU KLARIFIKASI untuk cascade di PostgreSQL | Enkripsi riwayat dan pemangkasan tautan bagikan dibangun W9 di balik sakelar yang mati sampai pemilik memutus kebijakannya di `docs/KEBIJAKAN-DATA-PRIBADI.md` |

## Lapisan pertahanan

| Lapisan | Bukti |
|---|---|
| Peramban | `tests/Feature/SecurityHeadersTest.php::test_the_page_cannot_be_framed_by_another_site`, `tests/Feature/SecurityHeadersTest.php::test_an_authenticated_page_carries_the_headers_too` |
| Autentikasi | baris Auth bypass di atas |
| Otorisasi | baris IDOR dan Eskalasi hak di atas |
| Validasi | `tests/Feature/TripLifecycleTest.php::test_an_unknown_completion_state_is_a_validation_error_not_a_crash`, `tests/Feature/HikeTrackRecordingTest.php::test_an_impossible_coordinate_is_refused` |
| Aturan bisnis | `tests/Feature/TripLifecycleTest.php::test_a_trip_cannot_be_created_on_an_unpublished_trail` |
| Basis data dan RLS | baris RLS di atas; kunci baris per pendaki dan per trip di `tests/Spatial/UserRowLockTest.php` dan `tests/Spatial/PreparationFirstVisitLockTest.php` (W9 Tugas 7), serta PreDepartureConfirmLockTest dari gelombang perbaikan akhir W4 |
| Penyimpanan | `tests/Feature/ReportPhotoAccessTest.php::test_guests_are_never_served_a_photo`, `tests/Feature/EvidenceArchiveTest.php::test_a_path_not_written_by_the_archive_is_never_served` |
| Jejak audit | `tests/Feature/AuditLogChangeTest.php::test_deleting_a_permit_rule_logs_what_was_deleted` |

## Yang belum dapat dibuktikan agen

- **Audit dependensi.** `composer audit` dan `npm audit` membutuhkan jaringan ke basis data advisori, yang dilarang
  untuk agen. Statusnya PERLU KLARIFIKASI dan dijalankan workflow `security.yml` sesudah izin push. Temuan terbuka:
  MapLibre 4.7.1 (Q20) dan advisori perkakas dev (butir izin akhir 16).
- **Suite Spatial.** Termasuk test kunci baris W9 dan W4, cascade, indeks unik, dan RLS. Syarat rilis.
- **Status migrasi Supabase.** `php artisan migrate:status` terhadap Supabase butuh izin baca (Q22).
- **Cookie sesi di produksi.** `SESSION_SECURE_COOKIE` diisi di lingkungan produksi. Nilainya tidak dibaca agen.
```

- [ ] **Step 4: Jalankan dan pastikan lulus.**

Run: `php artisan test --compact --filter='MassAssignmentSweepTest|AuditCitationTest'`
Expected: PASS; pada putaran ulang 4 test, 4 lulus. Bila satu rujukan gagal karena test W5 sampai W8 berganti nama di repo nyata, perbaiki rujukan
di laporan ke nama yang ada (Grep di `tests/`), jangan menghapus barisnya.

- [ ] **Step 5: Format dan suite penuh.**

Run:
```bash
vendor/bin/pint --dirty --format agent
php artisan test --compact --testsuite=Unit,Feature
```
Expected: Pint lulus; suite 2.327 test, 2.325 lulus, 2 dilewati (sebelum tugas: 2.323).

- [ ] **Step 6: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 13: Dokumen: GR-08 dan log keputusan, drift PRODUCT dan ARCHITECTURE, README, CONTRIBUTING

Rencana induk W9 butir 4 dan spec v3 bagian 16. (1) `docs/PRD.md`: governance record GR-08 sesuai format tujuh hal
§130 (W9-R19): §145.1 baris Jelajah (halaman gunung `/mountains/{gunung}` sebagai hub, DK-44, dan penilaian kecocokan
`/trails/{jalur}/kecocokan`) dan Trip (indeks trip berkelompok dengan Lihat semua); §128 baris 7, 8, 9, dan 12 yang basi
sesudah W7 dan W8 (Konflik W7 nomor 9 dan W8 nomor 8); §143; dan log keputusan DK-30 sampai DK-35 dan DK-37 sampai
DK-58 (GR-04 menjadwalkannya di W9; DK-36 sudah tercatat lewat GR-07). Amandemen paket lapangan tetap GR-07 karena Q7
belum diputus. (2) `PRODUCT.md`: "layak berangkat" (R-014, DK-14) dan "perbandingan maksimum lima" serta permukaan
"Pertimbangkan" (DK-05, GR-02) (spec v3 bagian 3.6). (3) `docs/ARCHITECTURE.md`: peta halaman React menggantikan
tabel komponen Livewire, kelas halaman W4, W6, dan W9, utilitas `app/Support`, baris "Mau ubah apa" untuk perubahan W9,
dan empat rujukan berkas yang sudah tidak ada (`app/Livewire`, `resources/js/app.js`, menu admin Blade). (4)
`README.md` baris sistem desain dan `docs/audit/`; `CONTRIBUTING.md` aturan yang ditegakkan test W9 dan aturan
pratinjau halaman baru.

**Files:**
- Create: `tests/Feature/DecisionLogTest.php`
- Create: `tests/Feature/DocumentDriftTest.php`
- Modify: `CONTRIBUTING.md`
- Modify: `PRODUCT.md`
- Modify: `README.md`
- Modify: `docs/ARCHITECTURE.md`
- Modify: `docs/PRD.md`

**Interfaces:**
- Consumes: laporan audit Tugas 12, daftar periksa Tugas 11, nama test W9.
- Produces: `docs/PRD.md` dengan GR-08 dan "governance record berikutnya GR-09"; test penjaga `DecisionLogTest` dan `DocumentDriftTest`.

- [ ] **Step 1: Tulis test yang gagal.**

Buat `tests/Feature/DecisionLogTest.php`:

```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Log keputusan PRD memuat setiap keputusan DK (GR-04: keputusan DK-30 sampai DK-58 spec v3
 * masuk log di gelombang W9; GR-08).
 *
 * Keputusan yang tidak tercatat di log tidak dapat dibatalkan pemilik dengan jelas, dan nomor
 * yang tercatat dua kali membuat rujukan "DK-nn" bermakna ganda.
 */
class DecisionLogTest extends TestCase
{
    public function test_every_decision_from_dk01_to_dk58_has_exactly_one_row(): void
    {
        $prd = File::get(base_path('docs/PRD.md'));
        $log = substr($prd, (int) strpos($prd, '### Log keputusan'));
        $salah = [];

        foreach (range(1, 58) as $nomor) {
            $baris = preg_match_all('/^\| DK-'.sprintf('%02d', $nomor).'( \([^)]*\))? \|/m', $log);

            if ($baris !== 1) {
                $salah[] = sprintf('DK-%02d: %d baris', $nomor, $baris);
            }
        }

        $this->assertSame([], $salah);
    }

    public function test_the_next_governance_record_number_follows_the_last_one(): void
    {
        $prd = File::get(base_path('docs/PRD.md'));
        preg_match_all('/^### GR-(\d{2}):/m', $prd, $catatan);
        $terakhir = max(array_map('intval', $catatan[1]));

        $this->assertStringContainsString(sprintf('governance record berikutnya GR-%02d', $terakhir + 1), $prd);
    }
}
```

Buat `tests/Feature/DocumentDriftTest.php`:

```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Dokumen tim tidak lagi memuat drift yang dicatat spec v3 bagian 3.6 (W9).
 *
 * docs/ARCHITECTURE.md dibaca anggota baru untuk tahu berkas mana yang disentuh; sampai W9 ia masih
 * menunjuk komponen Livewire, `resources/js/app.js`, dan menu admin Blade yang sudah tidak ada.
 * PRODUCT.md dibaca alat desain dan masih menjanjikan "layak berangkat" (R-014) serta perbandingan
 * lima jalur (DK-05). Setiap jalur repo di dalam backtick ARCHITECTURE kini harus ada, kecuali yang
 * memang dicatat sebagai belum ada.
 */
class DocumentDriftTest extends TestCase
{
    /**
     * Jalur yang disebut dokumen justru karena belum ada.
     *
     * @var array<string, string>
     */
    private const BELUM_ADA = [
        'tests/e2e' => 'Runner end-to-end menunggu izin Playwright (Q17).',
    ];

    public function test_every_repo_path_in_the_code_map_exists(): void
    {
        preg_match_all('/`([^`\s]+)`/', File::get(base_path('docs/ARCHITECTURE.md')), $temuan);
        $hilang = [];

        foreach (array_unique($temuan[1]) as $jalur) {
            $jalur = rtrim(explode('::', $jalur)[0], '.,;:');

            if (! preg_match('#^(app|resources|config|routes|tests|database|docs|bootstrap|public|lang)/#', $jalur) || preg_match('/[*{}<>]/', $jalur) || array_key_exists($jalur, self::BELUM_ADA)) {
                continue;
            }

            if (! file_exists(base_path($jalur))) {
                $hilang[] = $jalur;
            }
        }

        $this->assertSame([], $hilang);
    }

    public function test_the_code_map_no_longer_describes_a_livewire_frontend(): void
    {
        $dokumen = File::get(base_path('docs/ARCHITECTURE.md'));

        $this->assertStringNotContainsString('sedang dimigrasi', $dokumen);
        $this->assertStringNotContainsString('Setiap komponen Livewire', $dokumen);
    }

    public function test_the_product_summary_keeps_readiness_as_decision_support_and_compares_three(): void
    {
        $produk = File::get(base_path('PRODUCT.md'));

        $this->assertStringNotContainsString('layak berangkat', $produk);
        $this->assertStringNotContainsString('maksimum lima', str_replace("\n", ' ', $produk));
        $this->assertStringNotContainsString('Pertimbangkan', $produk);
    }
}
```

- [ ] **Step 2: Jalankan dan pastikan gagal.**

Run: `php artisan test --compact --filter='DecisionLogTest|DocumentDriftTest'`
Expected: FAIL; pada putaran ulang 5 test, 1 lulus, 4 gagal. Yang gagal: DK-30 sampai DK-35 dan DK-37 sampai DK-58 tanpa baris log; ARCHITECTURE menunjuk
`app/Livewire`, `resources/js/app.js`, dan `admin-nav.blade.php`; PRODUCT masih memuat "layak berangkat".

- [ ] **Step 3: Sunting dokumen.** Jangkar `docs/PRD.md` dan `docs/ARCHITECTURE.md` yang berasal dari rencana W5 sampai
W8 (baris "governance record berikutnya", akhir GR-07, baris DK-36, baris tabel layanan W7) dicocokkan ulang di Pra-cek
butir 1.

`CONTRIBUTING.md`:

1. Ganti:

```markdown
| Tidak ada pola kredensial yang dikenal (daftarnya di `SECURITY.md`) di berkas terlacak dan berkas baru yang tidak diabaikan git | `SecretsNotInRepoTest` |
```

dengan:

```markdown
| Tidak ada pola kredensial yang dikenal (daftarnya di `SECURITY.md`) di berkas terlacak dan berkas baru yang tidak diabaikan git | `SecretsNotInRepoTest` |
| Setiap rute tulis butuh sesi dan batas laju, atau tercatat alasannya | `WriteRouteGuardTest` |
| Tidak ada model yang membuka semua kolomnya, dan tidak ada kode HTTP yang meneruskan seluruh isi permintaan | `MassAssignmentSweepTest` |
| Jam ditulis bertitik dengan zonanya; tanggal pendek menyebut tahun di luar tahun berjalan | `IndonesianTimeFormatTest` |
| Kalimat mesin memakai kosakata Indonesia dan angka berpemisah ribuan | `MachineVocabularyTest` |
| Teks yang dibaca pendaki tidak memakai kata klaim keselamatan atau kepastian | `SafetyClaimWordTest` |
| Setiap halaman React punya pratinjau dan baris di daftar periksa klik-lewat | `PreviewCoverageTest` |
| Laporan audit hanya menyebut test yang ada | `AuditCitationTest` |
```

2. Ganti:

```markdown
  Isi daftarnya dijaga `ReminderSheetTest`.
```

dengan:

```markdown
  Isi daftarnya dijaga `ReminderSheetTest`.
- Halaman React baru wajib punya pratinjau di salah satu kelas `tests/Pratinjau` dan satu baris di daftar periksa
  klik-lewat `docs/audit/klik-lewat/2026-10-09-daftar-periksa-w9.md`; `PreviewCoverageTest` di suite Feature gagal
  bila salah satunya lupa. Cara klik-lewat per halaman, status hasil, dan keterbatasan harness ada di berkas itu.
```

`PRODUCT.md`:

3. Ganti:

```markdown
lalu bersiap sampai benar-benar layak berangkat.
```

dengan:

```markdown
lalu bersiap sampai jelas apa yang masih kurang sebelum berangkat.
```

4. Ganti:

```markdown
memutuskan."* Adaptasinya: permukaan Pertimbangkan, shortlist, perbandingan maksimum
lima, berbagi read-only, dan membuat trip dari jalur terpilih.
```

dengan:

```markdown
memutuskan."* Adaptasinya: Simpanan dengan catatan pribadi, perbandingan maksimal tiga
jalur (DK-05), tautan bagikan read-only, dan membuat trip dari jalur terpilih.
```

`README.md`:

5. Ganti:

```markdown
| Gaya | Tailwind CSS 3.4 dengan token warna CSS variable, tema terang dan gelap |
```

dengan:

```markdown
| Gaya | Tailwind CSS 3.4 dengan token warna CSS variable, tema terang dan gelap |
| Sistem desain | Token di `resources/css/app.css`, primitif React di `resources/js/components/ui/`, huruf Plus Jakarta Sans dihosting sendiri di `public/fonts/`, ikon Phosphor tersalin di registri `resources/js/components/ui/ikon/`; arah di `DESIGN.md`, pratinjau tanpa basis data di `tests/Pratinjau` (`CONTRIBUTING.md`) |
```

6. Ganti:

```markdown
- [`SECURITY.md`](SECURITY.md): pelaporan kerentanan dan kebijakan rahasia.
```

dengan:

```markdown
- [`SECURITY.md`](SECURITY.md): pelaporan kerentanan dan kebijakan rahasia.
- [`docs/audit/`](docs/audit/): audit antislop, keamanan, aksesibilitas, dan daftar periksa klik-lewat beserta buktinya.
```

`docs/ARCHITECTURE.md`:

7. Ganti:

```markdown
Frontend sedang dimigrasi dari Livewire ke **React 19 + Inertia 3** (keputusan pemilik
24 September 2026, jalur C). Kolom komponen di bawah menyebut yang berlaku hari ini;
halaman yang sudah pindah ditandai **[React]**, ledger lengkapnya di `HANDOVER.md` §0.

| Langkah | Komponen | Route |
|---|---|---|
| Profil | `Onboarding\ProfileSetup` | `/onboarding` |
| Rencana | `Goals\GoalForm` | `/goals/create` |
| Route Fit | `Recommendations\RecommendationResults` | `/recommendations/{run}` |
| Jelajah jalur | **[React]** Jelajah, rincian jalur, penilaian kecocokan, Simpanan, dan Bandingkan di `pages/Trails/`; halaman gunung `pages/Mountains/Show.tsx`; tautan bagikan publik `pages/Public/SharedComparison.tsx` | `/trails*`, `/mountains/{mountain:slug}`, `/b/{token}` |
| Trip | `Trips\TripForm`, `Trips\TripShow`, `Trips\TripIndex` | `/trips*` |
| Persiapan | `Trips\PreparationChecklist` | `/trips/{trip}/preparation` |
| Readiness | `Trips\ReadinessDashboard` | `/trips/{trip}/readiness` |
| Hike Mode | `Trips\HikeMode` | `/trips/{trip}/hike` |
| Laporan | `Reports\ConditionReportForm` | `/reports/create` |
| Riwayat | `History\HikingHistoryPage` | `/history` |
```

dengan:

```markdown
Seluruh antarmuka aplikasi adalah **React 19 lewat Inertia 3** (keputusan pemilik 24 September 2026, jalur C).
Blade tinggal untuk templat akar Inertia, halaman publik jalur untuk mesin pencari, halaman galat muat penuh, dan shell
luring. Setiap halaman di bawah adalah satu berkas di `resources/js/pages/` dan punya pratinjau di suite `Pratinjau`
(`PreviewCoverageTest`). Kolom tengah menyebut kelas yang merakit props halaman itu (spec v3 bagian 9.2).

| Langkah | Halaman React | Kelas halaman atau layanan | Route |
|---|---|---|---|
| Profil | `Onboarding/ProfileSetup`, `Profile/Edit` | `Profil\HalamanProfil`, `Profil\TurunanPengalaman` | `/onboarding`, `/profile` |
| Rencana | `Goals/Create` | `GoalController` | `/goals/create` |
| Route Fit | `Recommendations/Show`, `Trails/Fit` | `RouteFitService`, `Trails\PenilaianKecocokan` | `/recommendations/{run}`, `/trails/{trail:slug}/kecocokan` |
| Jelajah | `Trails/Index`, `Trails/Show`, `Trails/Saved`, `Trails/Compare`, `Mountains/Show`, `Public/SharedComparison` | `Trails\HalamanJelajah`, `Trails\TrailDetailPage`, `Trails\RouteComparisonTable`, `Mountains\HalamanGunung`, `Trails\TautanBagikan` | `/trails*`, `/mountains/{mountain:slug}`, `/b/{token}` |
| Trip | `Trips/Index`, `Trips/Create`, `Trips/Edit`, `Trips/Show`, `Trips/Print` | `Trips\IndeksTrip`, `Trips\RencanaTrip`, `Trips\KepalaTrip`, `Trips\TripSteps`, `Trips\CetakTrip` | `/trips*` |
| Persiapan | `Trips/Preparation` | `Trips\HalamanPersiapan`, `PreparationService` | `/trips/{trip}/preparation` |
| Readiness | `Trips/Readiness` | `Trips\HalamanKesiapan`, `Trips\PreDepartureCheck` | `/trips/{trip}/readiness` |
| Mendaki | `Track/Index`, `Trips/Hike` | `Trips\PintuMendaki`, `Trips\HalamanMendaki`, `Trips\PaketLapangan`, `Trips\KartuDarurat` | `/mendaki`, `/trips/{trip}/hike` |
| Laporan | `Community/ConditionReport`, `Community/HikeSummary` | `Community\HasilPendakian` | `/reports/create`, `/history/{trip}` |
| Riwayat dan progres | `Community/HikingHistory`, `Community/HikerProgress` | `Community\HubProgres`, `Community\PencapaianPribadi` | `/history`, `/progres` |
| Kabar | `Community/TrailNews` | `Community\KabarJalur` | `/kabar` |
| Beranda | `Dashboard` | `Dashboard\RingkasanDasbor` dan kelas bagian Beranda | `/dashboard` |
| Galat | `Galat` | `App\Support\HalamanGalat` | jawaban galat setiap kunjungan Inertia |
```

8. Ganti:

```markdown
| Menambah halaman admin baru | Daftarkan route-nya, lalu tambahkan ke `resources/views/components/ui/admin-nav.blade.php` |
```

dengan:

```markdown
| Menambah halaman admin baru | Daftarkan route-nya di grup `role:admin`, tambahkan tautannya ke prop `adminNav` di `app/Http/Middleware/HandleInertiaRequests.php` (digambar `resources/js/components/admin/AdminNav.tsx`), lalu tambahkan pratinjaunya |
| Kata halaman galat 403, 404, 419, 429, 500, dan 503 | `app/Support/HalamanGalat.php`, satu sumber untuk Blade muat penuh (`resources/views/errors/layout.blade.php`) dan halaman React `Galat` bagi kunjungan Inertia; kaitnya `respond()` di `bootstrap/app.php` (W9) |
| Proxy tepercaya di depan aplikasi | `config/trustedproxy.php` atau `TRUSTED_PROXIES`; kosong berarti alamat terusan diabaikan dan batas laju per IP memakai alamat penghubung langsung |
| Enkripsi riwayat Inertia saat keluar | `INERTIA_ENCRYPT_HISTORY`, mati sampai pemilik memutus kebijakan data di perangkat |
| Lama simpan tautan bagikan yang berakhir | `config/hiking.php` → `share_links.prune_after_days` atau `SHARE_LINK_PRUNE_AFTER_DAYS`; kosong berarti tidak dipangkas; tugas hariannya `model:prune` di `routes/console.php` |
| Format jam dan tanggal di layar | `App\Support\Timezone::display()` (jam bertitik dengan WIB, WITA, atau WIT) dan `Timezone::tanggalPendek()` (tahun ditulis bila beda tahun) |
| Batas lebar layar di kode klien | `resources/js/lib/layar.ts`, satu sumber yang dicocokkan `BreakpointSourceTest` dengan tema Tailwind |
```

9. Ganti:

```markdown
| `Trips\IsiAwalPerlengkapan`, `Trips\CatatanButir`, `Trails\CariJalur` | Perlengkapan pribadi yang mengisi awal daftar persiapan sebagai butir belum dikonfirmasi (W7-R8), catatan per butir Perlengkapan dan Logistik (W7-R9), dan pencarian jalur katalog untuk isian satu jalur |

**`app/Support`**: utilitas yang tidak memuat kebijakan produk: `Timezone` (konversi WIB/WITA/WIT), `ImageSanitizer` (pembersih EXIF), `PostGis` (kolom geografi).

**`app/Policies`**: otorisasi tingkat objek. Setiap komponen Livewire yang menerima model dari route **wajib** memanggil `$this->authorize()` di `mount()`.

**`app/Livewire`**: presentasi lama, sedang dimigrasi. Komponen boleh memanggil service; komponen tidak boleh memuat aturan domain. Kalau Anda menulis `if` yang berisi kebijakan produk di komponen, tempatnya salah.
```

dengan:

```markdown
| `Trips\IsiAwalPerlengkapan`, `Trips\CatatanButir`, `Trails\CariJalur` | Perlengkapan pribadi yang mengisi awal daftar persiapan sebagai butir belum dikonfirmasi (W7-R8), catatan per butir Perlengkapan dan Logistik (W7-R9), dan pencarian jalur katalog untuk isian satu jalur |
| `Trips\IndeksTrip`, `Trips\RencanaTrip`, `Trips\HalamanPersiapan`, `Trips\HalamanKesiapan`, `Trips\KepalaTrip`, `Trips\TripSteps` | Kelas halaman Trip (W4): indeks berkelompok, Rencanakan, Persiapan, dan Cek kesiapan, dengan kepala trip dan empat langkah dari satu sumber |
| `Trips\PintuMendaki`, `Trips\HalamanMendaki`, `Trips\PendakianAktif`, `Trips\PaketLapangan`, `Trips\KartuDarurat`, `Trips\CetakTrip`, `Trips\PesanRencana` | Mendaki (W6): pintu tab, mode lapangan yang menghitung di perangkat, strip pendakian berlangsung, paket lapangan luring di balik sakelar, kartu darurat, versi cetak, dan pesan rencana untuk kontak |
| `Community\KabarJalur`, `Community\HasilPendakian` | Kabar berkelompok menurut asal (R-009, R-010) dan ringkasan satu pendakian |

**`app/Support`**: utilitas yang tidak memuat kebijakan produk: `Timezone` (konversi WIB/WITA/WIT dan format jam), `ImageSanitizer` (pembersih EXIF), `PostGis` (kolom geografi), `HalamanGalat` (kata halaman galat, W9), `PenjagaDataContoh` (penolak data contoh di luar SQLite lokal, DK-35), `AsetLuring` dan `KunciPerangkat` (shell luring dan kunci penyimpanan perangkat per pengguna, W6).

**`app/Policies`**: otorisasi tingkat objek. Controller memanggil `authorize()` atau FormRequest-nya memeriksa Policy; klien tidak pernah dipercaya untuk kepemilikan atau peran.

Paket `livewire/livewire` dan `livewire/volt` masih terpasang walaupun tidak ada lagi komponen Livewire; rute `livewire/*` bawaannya tercatat di `WriteRouteGuardTest`. Melepas paket adalah perubahan dependensi yang menunggu izin pemilik (Konflik W9).
```

10. Ganti:

```markdown
- **Dua pintu masuk Vite** selama migrasi: `app.js` (layout Livewire) dan `app.tsx` (Inertia), berbagi `dasar.js` (service worker, tema, pemuat peta). Token warna tetap satu-satunya sumber; komponen React memakai kelas Tailwind yang sama, tanpa CSS-in-JS dan tanpa `dark:`.
```

dengan:

```markdown
- **Dua pintu masuk Vite**: `resources/js/app.tsx` (Inertia) dan `resources/js/luring/shell.tsx` (shell luring), berbagi `resources/js/dasar.js` (service worker, tema, pemuat peta). Token warna tetap satu-satunya sumber; komponen React memakai kelas Tailwind yang sama, tanpa CSS-in-JS dan tanpa `dark:`.
```

11. Ganti:

```markdown
- **Pemeriksaan aksesibilitas manual.** Test hanya menutup hal yang dapat diperiksa mesin: label, struktur judul, bahasa dokumen. Urutan fokus, kebermaknaan teks alternatif, dan kontras pada seluruh kombinasi masih butuh mata manusia.
```

dengan:

```markdown
- **Pemeriksaan aksesibilitas manual.** Test hanya menutup hal yang dapat diperiksa mesin: label, struktur judul, bahasa dokumen, kontras token, target sentuh, dan kata. Peta kriteria WCAG 2.2 AA ke test ada di `docs/audit/aksesibilitas-w9.md`; urutan fokus, pembaca layar, dan reflow di layar dibuktikan klik-lewat (`docs/audit/klik-lewat/2026-10-09-daftar-periksa-w9.md`) dan uji kegunaan pemilik.
```

12. Ganti:

```markdown
Halaman galat ada di `resources/views/errors/`, berbahasa Indonesia, dan **tidak menyentuh basis data, sesi, maupun Livewire**: ia dirender justru ketika sesuatu sedang rusak. Font dari luar juga tidak dimuat, karena kalau jaringannya yang bermasalah, menunggunya hanya menunda pengguna membaca apa yang terjadi. Halaman 500 dan 503 mengarahkan pendaki yang sedang bersiap berangkat untuk memastikan status jalur langsung ke pengelola, bukan menunggu aplikasi pulih.
```

dengan:

```markdown
Halaman galat berbahasa Indonesia dan **tidak menyentuh basis data maupun sesi**: ia dirender justru ketika sesuatu sedang rusak. Muat penuh memakai Blade di `resources/views/errors/`; kunjungan Inertia menerima halaman React `Galat` lewat `respond()` di `bootstrap/app.php`, tanpa props bersama kerangka, dengan kata dari sumber yang sama (`App\Support\HalamanGalat`). Aksi yang ditolak 429 kembali ke halaman asalnya dari Referer satu asal saja, dan detail galat 500 hanya tampil dalam mode debug. Font dari luar juga tidak dimuat, karena kalau jaringannya yang bermasalah, menunggunya hanya menunda pengguna membaca apa yang terjadi. Halaman 500 dan 503 mengarahkan pendaki yang sedang bersiap berangkat untuk memastikan status jalur langsung ke pengelola, bukan menunggu aplikasi pulih.
```

13. Ganti:

```markdown
**Pustaka peta dimuat sesuai kebutuhan.** `resources/js/app.js` hanya memuat sebuah fungsi `window.muatPeta()`; MapLibre yang 787 kB baru diunduh ketika mode pendakian benar-benar menggambar peta. Menambahkan `import 'maplibre-gl'` kembali ke `app.js` akan mengembalikan beban itu ke seluruh halaman.
```

dengan:

```markdown
**Pustaka peta dimuat sesuai kebutuhan.** `resources/js/dasar.js` hanya memuat sebuah fungsi `window.muatPeta()`; MapLibre yang 787 kB baru diunduh ketika sebuah halaman benar-benar menggambar peta (`resources/js/components/peta/usePetaDasar.ts`). Menambahkan `import 'maplibre-gl'` ke pintu masuk akan mengembalikan beban itu ke seluruh halaman.
```

`docs/PRD.md`:

14. Ganti:

```markdown
2. **Tidak ada ID yang dihapus atau dipakai ulang.** Bagian yang dipadatkan ke bagian lain tetap tercatat sebagai alias di Lampiran A, misalnya §137 dipadatkan ke §1. ID baru: bagian mulai §147 (kini terpakai sampai §161), fitur mulai FR-22 (terpakai sampai FR-27), aturan bisnis baru memakai nomor setelah BR-16 (belum ada), governance record berikutnya GR-08.
```

dengan:

```markdown
2. **Tidak ada ID yang dihapus atau dipakai ulang.** Bagian yang dipadatkan ke bagian lain tetap tercatat sebagai alias di Lampiran A, misalnya §137 dipadatkan ke §1. ID baru: bagian mulai §147 (kini terpakai sampai §161), fitur mulai FR-22 (terpakai sampai FR-27), aturan bisnis baru memakai nomor setelah BR-16 (belum ada), governance record berikutnya GR-09.
```

15. Ganti:

```markdown
| **Jelajah** (Explore) | "Apa yang tersedia dan mana yang relevan?" | Peta, daftar jalur, tampilan gunung (gunung membuka jalurnya, R-001), saringan, pencarian kalimat sederhana (FR-27), Route Fit, detail jalur, simpanan dengan catatan pribadi (FR-22) | Jelajah tidak memutuskan; ia menyajikan kandidat beserta alasannya. Peta hanya menggambar jalur yang bergeometri; titik gunung tidak dipakai sebagai pengganti jalur (DK-06, **diputuskan atas delegasi pemilik**) |
| **Trip** (Trips) | "Bagaimana saya memilih, merencanakan, dan bersiap?" | Rekomendasi Route Fit, banding maksimal 3 jalur yang dipilih dari simpanan (DK-05, **diputuskan atas delegasi pemilik**), trip, detail rencana (tanggal selesai, peserta), persiapan, cek kesiapan, pengingat (FR-24), tautan bagikan read-only (FR-23) | Banding tidak menghasilkan pemenang (R-016, §33). Tautan bagikan read-only dan tidak pernah menulis ke simpanan siapa pun. Kesiapan adalah dukungan keputusan, bukan izin medis atau hukum (§40) |
```

dengan:

```markdown
| **Jelajah** (Explore) | "Apa yang tersedia dan mana yang relevan?" | Peta, daftar jalur, halaman gunung `/mountains/{gunung}` sebagai hub jalurnya (gunung membuka jalurnya, R-001; DK-44, GR-08), saringan, pencarian kalimat sederhana (FR-27), Route Fit dengan penilaian kecocokan per jalur beserta alasan per faktor di `/trails/{jalur}/kecocokan`, untuk rencana tertentu lewat `?trip=` (GR-08), detail jalur, simpanan dengan catatan pribadi (FR-22) | Jelajah tidak memutuskan; ia menyajikan kandidat beserta alasannya. Peta hanya menggambar jalur yang bergeometri; titik gunung tidak dipakai sebagai pengganti jalur (DK-06, **diputuskan atas delegasi pemilik**) |
| **Trip** (Trips) | "Bagaimana saya memilih, merencanakan, dan bersiap?" | Rekomendasi Route Fit, banding maksimal 3 jalur yang dipilih dari simpanan (DK-05, **diputuskan atas delegasi pemilik**), indeks trip berkelompok Berlangsung, Mendatang, dan Selesai, dengan tautan Lihat semua untuk trip lama (GR-08), trip, detail rencana (tanggal selesai, peserta), persiapan, cek kesiapan, pengingat (FR-24), tautan bagikan read-only (FR-23) | Banding tidak menghasilkan pemenang (R-016, §33). Tautan bagikan read-only dan tidak pernah menulis ke simpanan siapa pun. Kesiapan adalah dukungan keputusan, bukan izin medis atau hukum (§40) |
```

16. Ganti:

```markdown
Satu tabel yang menyatukan fase PRD v2.0 (1 sampai 8), Fase 1 sampai 4 PRD NgodingPakeAI, peta jalan `ourplan.md` (0 sampai 15), tahap DK-23, dan aliran kerja spec v2. Setiap tahap ditutup dengan gerbang persetujuan pemilik dan bukti (§161). Keadaan per 25 September 2026; tingkat L mengikuti §161.
```

dengan:

```markdown
Satu tabel yang menyatukan fase PRD v2.0 (1 sampai 8), Fase 1 sampai 4 PRD NgodingPakeAI, peta jalan `ourplan.md` (0 sampai 15), tahap DK-23, dan aliran kerja spec v2. Setiap tahap ditutup dengan gerbang persetujuan pemilik dan bukti (§161). Keadaan per 25 September 2026, kecuali baris 7, 8, 9, dan 12 yang diperbarui GR-08 per 9 Oktober 2026; tingkat L mengikuti §161.
```

17. Ganti:

```markdown
| 7. Pertimbangkan, perjalanan, kondisi | Simpan, banding, trip, persiapan, kesiapan, Kabar | Lama 5, 6; NgodingPakeAI Fase 2; ourplan 7 sampai 9, 11 | L2 untuk yang lama; banding maks 3, catatan, tautan bagikan, pengingat, detail rencana belum | `docs/fitur/03`, `04`, `05` |
| 8. Mode pendakian, progres, akun | Mode pendakian, riwayat, progres, akun | Lama 7; NgodingPakeAI Fase 3; ourplan 10, 12 | L2 tanpa data lapangan; target pribadi, catatan manual, Google, dan passkey belum | `docs/fitur/06`, `07` |
| 9. Tata kelola data | Konsol pengelola, moderasi, kontribusi ahli | NgodingPakeAI Fase 4; ourplan 13 | L2; kelola peran pengguna belum | `docs/fitur/08` |
| 10. Fase nol platform | Perbaikan F8, F9, F11 sampai F13, mode gelap, adaptasi perangkat, pencabutan `@tailwindcss/vite`, sketsa logo | DK-23 fase nol | Selesai 24 September 2026, kecuali aset logo final (gerbang DK-16) | HANDOVER.md bagian 0a |
| 11. Migrasi frontend | Semua halaman aplikasi ke React dan Inertia; pensiun Livewire | Keputusan jalur C; aliran A | Halaman aplikasi sudah React; pensiun Blade dan Livewire sedang diselesaikan | HANDOVER.md bagian 0 |
| 12. Memount v2 | Fondasi desain, halaman per tab, fitur DK-07 tanpa dependensi | Spec v2 aliran D sampai F | Direncanakan | Spec v2 bagian 7 |
```

dengan:

```markdown
| 7. Pertimbangkan, perjalanan, kondisi | Simpan, banding, trip, persiapan, kesiapan, Kabar | Lama 5, 6; NgodingPakeAI Fase 2; ourplan 7 sampai 9, 11 | Banding maksimal 3, catatan, tautan bagikan, pengingat, dan detail rencana dibangun W3 dan W4 di pohon kerja, menunggu izin commit dan deploy; L2 tanpa data lapangan | `docs/fitur/03`, `04`, `05`; `HANDOVER.md` bagian 0 |
| 8. Mode pendakian, progres, akun | Mode pendakian, riwayat, progres, akun | Lama 7; NgodingPakeAI Fase 3; ourplan 10, 12 | Mode lapangan dan paket luring (W6, paket dan alat keselamatan di balik sakelar mati), target pribadi dan catatan manual (W7) dibangun di pohon kerja, menunggu izin commit; L2 tanpa data lapangan; Google dan passkey belum, menunggu izin dependensi | `docs/fitur/06`, `07`; `HANDOVER.md` bagian 0 |
| 9. Tata kelola data | Konsol pengelola, moderasi, kontribusi ahli | NgodingPakeAI Fase 4; ourplan 13 | Meja kurator, keanggotaan badan resmi, dan status gunung api (W8) dibangun di pohon kerja, menunggu izin commit dan kurator pertama (Q11); L2; kelola peran pengguna lain belum | `docs/fitur/08`; `HANDOVER.md` bagian 0 |
| 10. Fase nol platform | Perbaikan F8, F9, F11 sampai F13, mode gelap, adaptasi perangkat, pencabutan `@tailwindcss/vite`, sketsa logo | DK-23 fase nol | Selesai 24 September 2026, kecuali aset logo final (gerbang DK-16) | HANDOVER.md bagian 0a |
| 11. Migrasi frontend | Semua halaman aplikasi ke React dan Inertia; pensiun Livewire | Keputusan jalur C; aliran A | Halaman aplikasi sudah React; pensiun Blade dan Livewire sedang diselesaikan | HANDOVER.md bagian 0 |
| 12. Memount v2 | Fondasi desain, halaman per tab, fitur DK-07 tanpa dependensi | Spec v2 aliran D sampai F | Gelombang W1 sampai W9 spec v3 selesai di pohon kerja; menunggu izin commit, push, migrasi, dan deploy (permintaan izin akhir W9) | Spec v3 bagian 12; `HANDOVER.md` bagian 0 |
```

18. Ganti:

```markdown
| Cakupan fitur utama | Diamandemen GR-02 (FR-22 sampai FR-27) |
```

dengan:

```markdown
| Cakupan fitur utama | Diamandemen GR-02 (FR-22 sampai FR-27) dan GR-06 (pengingat hari pemesanan izin dibuka di FR-24) |
```

19. Ganti:

```markdown
| Batas MVP | Diamandemen GR-01 dan GR-02 |
| Arsitektur navigasi | Diamandemen GR-02 (DK-25) |
```

dengan:

```markdown
| Batas MVP | Diamandemen GR-01 dan GR-02; amandemen paket lapangan GR-07 menunggu keputusan pemilik (Q7) |
| Arsitektur navigasi | Diamandemen GR-02 (DK-25); isi tab Jelajah dan Trip dilengkapi GR-08 |
```

20. Ganti:

```markdown
- Pembersihan posisi lama di `hiking_sessions` pada Supabase.

### Log keputusan
```

dengan:

```markdown
- Pembersihan posisi lama di `hiking_sessions` pada Supabase.

### GR-08: Permukaan Jelajah dan Trip yang sudah dibangun, status program, dan log keputusan spec v3 (gelombang W9)

**Requirement changed.**

- §145.1 baris Jelajah menyebut halaman gunung `/mountains/{gunung}` sebagai hub jalurnya (DK-44) dan penilaian
  kecocokan per jalur di `/trails/{jalur}/kecocokan`, termasuk untuk rencana tertentu lewat `?trip=`. Baris Trip
  menyebut indeks trip berkelompok Berlangsung, Mendatang, dan Selesai dengan tautan Lihat semua untuk trip lama.
- §128 baris 7, 8, 9, dan 12 diperbarui ke keadaan per 9 Oktober 2026: dibangun di pohon kerja dan menunggu izin
  commit, bukan "belum".
- §143 mencatat GR-06 di cakupan fitur, GR-07 yang menunggu Q7 di batas MVP, dan GR-08 di arsitektur navigasi.
- Log keputusan menerima DK-30 sampai DK-35 dan DK-37 sampai DK-58 dari spec v3 bagian 4 dan 17.4. DK-36 sudah
  tercatat lewat GR-07.

**Reason.** Spec v3 bagian 16 meminta §145.1 untuk halaman gunung, penilaian kecocokan, dan indeks trip beserta catatan
tata kelola di §146. GR-04 menjadwalkan log DK-30 sampai DK-58 di W9. Rencana W7 (Konflik 9) dan W8 (Konflik 8)
mencatat baris status program yang basi tetapi tidak menyuntingnya, karena PRD hanya berubah lewat governance record.

**Impact.** PRD menggambarkan permukaan yang sudah dibangun W3 dan W4, dan pemilik dapat membatalkan setiap keputusan
spec v3 lewat nomornya. Tidak ada perilaku produk yang berubah.

**Affected modules.** Dokumentasi: `docs/PRD.md` dan `tests/Feature/DecisionLogTest.php`. Tidak ada kode aplikasi,
skema, atau rute.

**Security impact.** Tidak ada permukaan baru.

**Data impact.** Tidak ada.

**Scope impact.** Tidak ada; batas MVP dan MVP+ tetap (§105, §106). Q7 tetap terbuka, jadi GR-07 tetap berstatus
menunggu keputusan pemilik. Keputusan pemilik lain yang terbuka (kategori data pribadi baru W7 dan W8 di
`docs/KEBIJAKAN-DATA-PRIBADI.md`, retensi tautan bagikan, dan enkripsi riwayat) tercatat di permintaan izin akhir W9 dan
tidak diputus di sini.

### Log keputusan
```

21. Ganti:

```markdown
| DK-36 (Q7) | Paket lapangan luring sebagai sistem kedua dan mode lapangan yang dihitung di perangkat; paket dan alat keselamatan dibangun di balik sakelar mati sampai Q10 dan Q14 | Keputusan pemilik tertunda (Q7); ditulis agen menurut spec v3 yang disetujui (pola W3 P8) | 7 Okt 2026 | §13, §53, §55, §121, §145, GR-07 |
```

dengan:

```markdown
| DK-30 | Prototype pemilik menjadi patokan desain; bagian Turunan `DESIGN.md` ditulis ulang darinya, Arah pemilik dan R-015 sampai R-017 tetap | Pemilik | 25 Sep 2026 | `DESIGN.md` |
| DK-31 | Koreksi terukur atas prototype: token kontras, label tab 12 px, tinggi mengikuti isi, waktu tempuh sebagai rentang bersumber, tombol "Jadikan dasar rencana" berbobot setara | Agen | 25 Sep 2026 | `DESIGN.md` |
| DK-32 | Status akses memakai warna, bentuk, dan kata sekaligus, dengan kalimat R-012 di setiap tampilan utama | Agen, mengikuti prototype | 25 Sep 2026 | `DESIGN.md` |
| DK-33 | Layar lebar mulai 1024 px: header 68 px dengan navigasi segmen dan menu avatar, bukan rail kiri | Agen, mengikuti prototype | 25 Sep 2026 | §145.1 (layar lebar) |
| DK-34 | Pengerjaan irisan vertikal: stabilisasi, fondasi, kerangka, lalu klaster tab dikerjakan tuntas | Pemilik | 25 Sep 2026 | §128 baris 12 |
| DK-35 | Data contoh hanya di SQLite lokal, berlabel DATA CONTOH, dan ditolak bila koneksinya bukan SQLite lokal | Pemilik | 25 Sep 2026 | Log saja; spec v3 bagian 11 dan `docs/ARCHITECTURE.md` |
| DK-36 (Q7) | Paket lapangan luring sebagai sistem kedua dan mode lapangan yang dihitung di perangkat; paket dan alat keselamatan dibangun di balik sakelar mati sampai Q10 dan Q14 | Keputusan pemilik tertunda (Q7); ditulis agen menurut spec v3 yang disetujui (pola W3 P8) | 7 Okt 2026 | §13, §53, §55, §121, §145, GR-07 |
| DK-37 | Foto gunung terkurasi berlisensi dengan atribusi wajib; tanpa foto tampil visual dari data; foto komunitas tidak pernah menjadi hero | Pemilik | 25 Sep 2026 | `DESIGN.md` (R-017) |
| DK-38 | Tipografi satu keluarga Plus Jakarta Sans dengan angka tabular, isi 16 px, lantai 12 px | **Delegasi pemilik** kepada riset | 25 Sep 2026 | `DESIGN.md` |
| DK-39 | Ikonografi hibrida: Phosphor untuk kendali umum ditambah glyph domain dari tanda "Jalur Berpos"; tombol tanpa panah hiasan | **Delegasi pemilik** kepada riset | 25 Sep 2026 | `DESIGN.md` |
| DK-40 | Kontrol yang belum berfungsi tidak ditampilkan | Agen | 25 Sep 2026 | Log saja; spec v3 bagian 4 |
| DK-41 | Onboarding menurunkan pengalaman medan dan navigasi dari jalur terberat yang dipilih, lalu meminta konfirmasi; dilewati berarti "Belum dinilai" | Agen | 25 Sep 2026 | Log saja; spec v3 bagian 4 |
| DK-42 | Wilayah minat masuk preferensi pendaki (kolom baru) | Pemilik lewat prototype 00 dan 14 | 25 Sep 2026 | Log saja; spec v3 bagian 4 |
| DK-43 | Status gunung api PVMBG di tabel sendiri, diisi kurator dari sumber resmi, tidak pernah dipetakan ke Buka atau Tutup, tampil hanya bila datanya ada | Keputusan pemilik D10 | 25 Sep 2026 | §41 (amandemen DK-22) |
| DK-44 | Halaman gunung terautentikasi di `/mountains/{gunung}` sebagai hub jalur; halaman publik `/gunung/{slug}` tetap mengikuti rencana bersama `/en/` | Agen | 25 Sep 2026 | §145.1, GR-08 |
| DK-45 | Beranda berfase dengan satu aksi utama per fase | Agen | 25 Sep 2026 | §145.1, GR-06 |
| DK-46 | Gelombang 0 menutup 20 test merah sebelum redesain dimulai | Agen | 25 Sep 2026 | Log saja; spec v3 bagian 4 |
| DK-47 | Prototype dan gambar acuannya pindah ke `docs/desain/prototype/` dan dilacak git | Agen | 25 Sep 2026 | GR-04 |
| DK-48 | Label keadaan kesiapan: "Persiapan kritis lengkap", "Masih perlu persiapan", "Tidak disarankan dengan rencana ini" | Agen, mengikuti prototype | 25 Sep 2026 | §38, GR-05 |
| DK-49 | Mode lapangan mengikuti tema pengguna dengan sakelar sekali ketuk; info kritis paling sedikit 7:1 di kedua tema | Agen | 25 Sep 2026 | GR-07; `DESIGN.md` |
| DK-50 | Alasan pembatalan trip dicatat opsional | Agen, mengikuti prototype 13 | 25 Sep 2026 | Log saja; spec v3 bagian 4 |
| DK-51 | Tetap monolit modular Laravel; prinsip panduan struktur luar diambil lewat pemetaan, bukan pemindahan `app/` | Pemilik (spec v3 bagian 17.4) | 26 Sep 2026 | §69; `docs/ARCHITECTURE.md` aturan 10 |
| DK-52 | Dokumen tim di `docs/`; `DESIGN.md` dan `PRODUCT.md` tetap di root dengan penunjuk di `docs/` | Pemilik (spec v3 bagian 17.4) | 26 Sep 2026 | GR-04 |
| DK-53 | Isi `docs/AGENTS.md`: visi, kesepakatan kerja, alur riset, tingkat bukti, cara audit, izin, definisi selesai, format rekap, matriks skill | Pemilik (spec v3 bagian 17.4) | 26 Sep 2026 | GR-04 |
| DK-54 | CI dipecah menjadi `test.yml`, `security.yml`, dan `deploy.yml` manual | Pemilik (spec v3 bagian 17.4) | 26 Sep 2026 | §126, GR-04 |
| DK-55 | `docker-compose.yml` untuk PostgreSQL dan PostGIS lokal, Docker opsional | Pemilik (spec v3 bagian 17.4) | 26 Sep 2026 | Log saja; `README.md` dan `CONTRIBUTING.md` |
| DK-56 | Root dirapikan per berkas; prototype ke `docs/desain/prototype/`; `ourplan.md` ke `docs/arsip/` | Pemilik (spec v3 bagian 17.4) | 26 Sep 2026 | GR-04 |
| DK-57 | Skill UI/UX digabung dengan urutan wewenang: arahan pemilik dan aturan kanonis, `DESIGN.md`, saringan antislop, lalu saran skill | Pemilik (spec v3 bagian 17.4) | 26 Sep 2026 | Log saja; `docs/AGENTS.md` dan `docs/MATRIKS-SKILL.md` |
| DK-58 | Tonggak M1 "siap push" = W0 + WR; push tetap menunggu izin eksplisit pemilik | Pemilik (spec v3 bagian 17.4) | 26 Sep 2026 | Log saja; `HANDOVER.md` bagian 0 |
```

**Varian repo nyata untuk blok tabel komponen `docs/ARCHITECTURE.md`** (blok yang teks lamanya dimulai
"Frontend sedang dimigrasi dari Livewire"). Di repo nyata 8 Oktober baris Trip, Persiapan, dan Readiness tabel itu
sudah ditandai **[React]** oleh W4, dan rencana W6 sampai W8 dapat mengubah baris lain, jadi teks lama blok dasar tidak
ditemukan (Pra-cek butir 1b). Tabel itu diganti seluruhnya, jadi isi barisnya tidak menentukan hasil: pakai sebagai teks
lama rentang dari baris pertama paragraf "Frontend sedang dimigrasi dari Livewire ke **React 19 + Inertia 3**" sampai
baris tabel terakhir "| Riwayat | ..." persis seperti di repo nyata (8 Oktober: baris 15 sampai 30), dan teks baru blok
itu apa adanya. Blok ARCHITECTURE lain tugas ini tidak berubah.

- [ ] **Step 4: Jalankan dan pastikan lulus.**

Run: `php artisan test --compact --filter='DecisionLogTest|DocumentDriftTest|PrdReferenceIntegrityTest|TeamDocsIndexTest|RepoHygieneTest|BrandIdentityTest'`
Expected: PASS; pada putaran ulang 38 test, 38 lulus.

- [ ] **Step 5: Em dash dan suite penuh.**

Run:
```bash
LC_ALL=C grep -c $'\xE2\x80\x94' docs/PRD.md docs/ARCHITECTURE.md README.md CONTRIBUTING.md
php artisan test --compact --testsuite=Unit,Feature
```
Expected: keempat berkas 0 baris berisi em dash; suite 2.332 test, 2.330 lulus, 2 dilewati (sebelum tugas: 2.327).

- [ ] **Step 6: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 14: Kandidat rilis, build CSS yang sama dengan produksi, dan materi uji kegunaan Q15

Rencana induk W9 butir 5 dan 6. Dokumen kandidat rilis `docs/audit/kandidat-rilis-w9.md` memuat gerbang lokal yang
diisi dari keluaran perintah di Tugas 16, syarat rilis yang menunggu izin (CI Spatial, status migrasi Supabase, audit
dependensi, RLS lapis kedua, pembersihan PII snapshot W4, `TRUSTED_PROXIES`, kurasi status resmi), urutan deploy 27
migrasi sesudah `2026_09_21_000100` per berkas dengan gelombang dan pemeriksaannya, dan ringkasan perubahan per
gelombang (W9-R20). Materi uji kegunaan `docs/riset/2026-10-uji-kegunaan/materi-sesi-w9.md` memetakan tugas T1 sampai
T8 protokol ke permukaan v3 dan menambah uji Q15: pengenalan glyph domain (`pos`, `tanjakan`, `resmi`), keterbacaan
angka dan jam di Android nyata, dan mode lapangan di luar ruangan; ia tidak memuat hasil (Q15 dijalankan pemilik).
Dua teks tugas di halaman Studi Kegunaan dan protokolnya masih memakai nama lama ("dasbor", "siap berangkat",
"Buat Rencana") dan diselaraskan ke permukaan v3 dan DK-14. Titipan W2-C10 ("ukuran CSS yang ikut view terkompilasi")
ditutup di sini karena menyangkut keterulangan build kandidat rilis: `tailwind.config.js` memindai
`storage/framework/views`, sehingga build lokal memuat 204 kelas yang tidak dipakai sumber mana pun (simulasi W9: CSS
85.909 B lawan 71.823 B; kelas itu hanya ada di 80 view terkompilasi dari `vendor/laravel/framework`, terutama halaman
pengecualian, dan di 8 salinan basi view galat dan luring).
Build produksi dari checkout bersih memang tanpa view terkompilasi, jadi pemindaian itu hanya membuat angka lokal
berbeda dari produksi. Baris itu dilepas (W9-R24).

**Files:**
- Create: `docs/audit/kandidat-rilis-w9.md`
- Create: `docs/riset/2026-10-uji-kegunaan/materi-sesi-w9.md`
- Create: `tests/Feature/ReleaseMaterialsTest.php`
- Modify: `app/Services/UsabilityStudyService.php`
- Modify: `docs/PROTOKOL-UJI-KEGUNAAN.md`
- Modify: `tailwind.config.js`
- Modify: `tests/Feature/InertiaLayoutTest.php`

**Interfaces:**
- Consumes: `UsabilityStudyService::TUGAS`, `NamaGlyph` di `resources/js/components/ui/ikon/glyphDomain.ts`, daftar migrasi `database/migrations`.
- Produces: dokumen kandidat rilis yang diisi Tugas 16 Step 2 (termasuk ukuran CSS build bersih); materi sesi yang dipakai pemilik; `content` Tailwind tanpa `storage/framework/views` (dijaga `InertiaLayoutTest::test_tailwind_does_not_scan_compiled_views`).

- [ ] **Step 1: Tulis test yang gagal.** Test baru `ReleaseMaterialsTest` dan satu test baru di `InertiaLayoutTest`.

Buat `tests/Feature/ReleaseMaterialsTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Services\UsabilityStudyService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Bahan rilis W9 lengkap terhadap sumbernya (rencana induk W9 butir 5 dan 6).
 *
 * Urutan deploy migrasi di dokumen kandidat rilis harus memuat setiap migrasi sesudah
 * `2026_09_21_000100`, migrasi terakhir yang tercatat berjalan di Supabase, tepat sekali dan
 * dalam urutan nama berkas; migrasi yang terlewat di daftar adalah kelas insiden R-024 (kode
 * tanpa tabelnya). Materi uji kegunaan harus memuat setiap tugas yang dicatat halaman Studi
 * Kegunaan dan setiap glyph domain yang diuji pengenalannya (Q15), dan tidak memuat hasil.
 */
class ReleaseMaterialsTest extends TestCase
{
    private const MIGRASI_TERAKHIR_DI_SUPABASE = '2026_09_21_000100';

    public function test_the_release_candidate_lists_every_pending_migration_once_in_file_order(): void
    {
        $tertunda = collect(File::files(database_path('migrations')))
            ->map(fn ($berkas) => $berkas->getFilenameWithoutExtension())
            ->filter(fn (string $nama) => strcmp(substr($nama, 0, 17), self::MIGRASI_TERAKHIR_DI_SUPABASE) > 0)
            ->sort()
            ->values()
            ->all();

        preg_match_all('/^\| \d+ \| `(\d{4}_\d{2}_\d{2}_\d{6}_[a-z0-9_]+)` \|/m', File::get(base_path('docs/audit/kandidat-rilis-w9.md')), $tercantum);

        $this->assertNotEmpty($tertunda);
        $this->assertSame($tertunda, $tercantum[1]);
    }

    public function test_the_usability_kit_covers_every_task_and_domain_glyph_and_carries_no_results(): void
    {
        $materi = File::get(base_path('docs/riset/2026-10-uji-kegunaan/materi-sesi-w9.md'));
        preg_match('/export type NamaGlyph = ([^;]+);/', File::get(resource_path('js/components/ui/ikon/glyphDomain.ts')), $glyph);
        preg_match_all("/'([a-z]+)'/", $glyph[1] ?? '', $namaGlyph);

        foreach (array_keys(UsabilityStudyService::TUGAS) as $tugas) {
            $this->assertStringContainsString('**'.$tugas.'.', $materi, "Tugas {$tugas} tidak ada di materi sesi.");
        }

        $this->assertNotEmpty($namaGlyph[1]);

        foreach ($namaGlyph[1] as $nama) {
            $this->assertStringContainsString('`'.$nama.'`', $materi, "Glyph {$nama} tidak diuji pengenalannya.");
        }

        $this->assertDoesNotMatchRegularExpression('/^#+ Hasil/m', $materi, 'Materi tidak memuat hasil; hasil dicatat di /admin/usability.');
    }

    public function test_the_study_page_names_the_v3_surfaces(): void
    {
        $tugas = json_encode(UsabilityStudyService::TUGAS);

        $this->assertStringNotContainsString('dasbor', $tugas);
        $this->assertStringNotContainsString('siap berangkat', $tugas);
    }
}
```

`tests/Feature/InertiaLayoutTest.php`:

1. Ganti:

```php
        $this->assertStringContainsString("'./resources/js/**/*.{js,jsx,ts,tsx}'", $config);
```

dengan:

```php
        $this->assertStringContainsString("'./resources/js/**/*.{js,jsx,ts,tsx}'", $config);
    }

    /**
     * Titipan W2-C10: dengan view terkompilasi ikut dipindai, CSS build lokal memuat kelas dari
     * halaman pengecualian bawaan Laravel dan dari view basi (204 kelas, 85.909 B lawan 71.823 B
     * pada simulasi W9), sedangkan build produksi dari checkout bersih tidak memuatnya. Sumber
     * kelas hanya Blade dan React di resources, ditambah view paginasi yang disebut namanya.
     */
    public function test_tailwind_does_not_scan_compiled_views(): void
    {
        preg_match('/content:\s*\[(.*?)\]/s', File::get(base_path('tailwind.config.js')), $konten);

        $this->assertNotEmpty($konten, 'Daftar content Tailwind tidak ditemukan.');
        $this->assertStringNotContainsString('storage/', $konten[1]);
```

- [ ] **Step 2: Jalankan dan pastikan gagal.**

Run: `php artisan test --compact --filter='ReleaseMaterialsTest|InertiaLayoutTest'`
Expected: FAIL; pada putaran ulang 15 test, 11 lulus, 2 gagal, 2 error. Yang gagal: kedua dokumen belum ada, teks tugas T1 masih menyebut "dasbor", dan `content`
Tailwind masih memuat `./storage/framework/views/*.php`.

- [ ] **Step 3: Implementasi.**

Buat `docs/audit/kandidat-rilis-w9.md`:

```markdown
# Kandidat rilis W9

Pohon kerja sesudah W1 sampai W9 adalah kandidat rilis. Commit, push, migrasi di Supabase, dan deploy tetap menunggu
izin pemilik (R-018, R-019); permintaannya disusun satu kali di tugas terakhir W9. Dokumen ini memuat gerbang lokal
yang dijalankan agen, syarat rilis yang belum dapat dijalankan tanpa izin, urutan deploy migrasi per berkas, dan
ringkasan perubahan per gelombang.

## Gerbang lokal

Diisi dengan keluaran perintah saat W9 dieksekusi, bukan disalin dari simulasi (R-023). Setiap perintah artisan selain
`php artisan test`, juga `npm run build`, berawalan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL=`.

| Gerbang | Perintah | Hasil |
|---|---|---|
| Suite Unit dan Feature | `php artisan test --compact --testsuite=Unit,Feature` | |
| Suite bawaan (Spatial dilewati tanpa DSN) | `php artisan test --compact` | |
| Pratinjau | `php artisan test --compact --testsuite=Pratinjau` | |
| TypeScript | `node_modules/.bin/tsc --noEmit` | |
| Build | `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build` | |
| Ukuran CSS aplikasi, sama dengan build produksi bersih (W2-C10) | ukuran `public/build/assets/app-*.css` sesudah build | |
| Format PHP | `vendor/bin/pint --format agent` | |
| Tanpa em dash di kode dan dokumen antarmuka (audit 001 dicatat sebagai temuan audit 002) | `LC_ALL=C grep -rlP --exclude='audit-001*' '\xE2\x80\x94' app resources tests database config DESIGN.md docs/ARCHITECTURE.md docs/fitur docs/audit` | |

## Syarat rilis yang menunggu izin

Semuanya PERLU KLARIFIKASI sampai dijalankan dengan izin. Tidak satu pun boleh ditulis lulus dari suite SQLite (R-024).

1. **Suite Spatial di CI lulus.** `test.yml` menjalankannya di PostGIS sesudah push yang diizinkan. Belum pernah
   berjalan, termasuk kunci baris W4 dan W9 (`tests/Spatial/UserRowLockTest.php`,
   `tests/Spatial/PreparationFirstVisitLockTest.php`), batas hari prakiraan W4, dan test skema W6 sampai W8.
2. **Status migrasi Supabase.** `php artisan migrate:status` terhadap Supabase atas izin baca (Q22) sebelum deploy, lalu
   setiap migrasi di tabel berikut dipasang per berkas dengan `--path` dan diperiksa lagi sesudahnya. Kode gelombang
   mana pun tidak di-deploy sebelum migrasinya (ruling W3-8b).
3. **Audit dependensi.** `security.yml` menjalankan `composer audit` dan `npm audit`. MapLibre 4.7.1 (Q20) dan advisori
   perkakas dev (butir izin akhir 16) diputus sebelum push.
4. **RLS lapis kedua** untuk tabel lama yang belum menyalakannya di migrasinya (Q22).
5. **Pembersihan PII snapshot kondisi lama** (`memount:bersihkan-snapshot-kondisi`, butir izin akhir 18) sebelum atau
   bersama deploy kode W4.
6. **Proxy tepercaya.** `TRUSTED_PROXIES` diisi sesuai platform deploy; tanpa itu batas laju per alamat IP di balik
   proxy berlaku untuk semua pengguna sekaligus.
7. **Kurasi status resmi lewat meja kurator** sebelum 31 Oktober 2026 (butir izin akhir 14), sesudah kurator pertama
   diberi keanggotaan (Q11).

## Urutan deploy migrasi

Migrasi terakhir yang tercatat berjalan di Supabase adalah `2026_09_21_000100` (INFERENCE dari `HANDOVER.md` bagian 6;
diperiksa dengan `migrate:status`). Semua migrasi sesudahnya dipasang dalam urutan nama berkas. W4, W5, dan W9 tidak
menambah migrasi. `ReleaseMaterialsTest` menjaga daftar ini lengkap.

| # | Berkas | Gelombang | Pemeriksaan sebelum dipasang |
|---|---|---|---|
| 1 | `2026_09_25_000917_add_plan_details_to_trip_plans_table` | M1, dipakai W4 | Wajib sebelum kode W4 (syarat deploy W4) |
| 2 | `2026_09_25_000918_create_trip_reminders_table` | M1, dipakai W4 dan W5 | Wajib sebelum kode W4 (syarat deploy W4) |
| 3 | `2026_09_25_000919_add_source_and_recorded_values_to_hiking_history_table` | Rintisan FR-25, dilengkapi W7 | Dipasang bersama migrasi W7, tidak sendiri |
| 4 | `2026_09_25_000920_create_personal_targets_table` | Rintisan FR-25, dilengkapi W7 | Dipasang bersama migrasi W7, tidak sendiri |
| 5 | `2026_09_25_000921_create_user_owned_items_table` | Rintisan FR-25, dilengkapi W7 | Dipasang bersama migrasi W7, tidak sendiri |
| 6 | `2026_09_25_002314_add_note_to_trail_considerations_table` | M1, dipakai W3 | Wajib sebelum kode W3 |
| 7 | `2026_09_28_000100_create_comparison_shares_table` | W3 | Wajib sebelum kode W3 (Simpanan dan Bandingkan mengueri tabel ini) |
| 8 | `2026_10_07_000100_add_unique_trip_plan_index_to_hiking_sessions_table` | W6 | Kueri duplikat di docblock migrasinya harus kosong |
| 9 | `2026_10_07_000200_add_pause_to_hiking_sessions_table` | W6 | Aditif |
| 10 | `2026_10_07_000300_add_cancellation_reason_to_trip_plans_table` | W6 | Aditif |
| 11 | `2026_10_07_000400_add_recorded_values_to_hiking_history_table` | W6 | Aditif |
| 12 | `2026_10_07_000500_add_return_and_registration_to_trip_plans_table` | W6 | Aditif |
| 13 | `2026_10_08_000100_add_source_to_hiking_history_table` | W7 | Hitungan riwayat tanpa trip harus 0 (Konflik W7 nomor 1) |
| 14 | `2026_10_08_000200_complete_personal_targets_table` | W7 | Hitungan `personal_targets` harus 0 |
| 15 | `2026_10_08_000300_complete_user_owned_items_table` | W7 | Hitungan `user_owned_items` harus 0 |
| 16 | `2026_10_08_000400_add_owned_item_to_trip_preparation_items_table` | W7 | Aditif |
| 17 | `2026_10_08_000500_add_note_to_trip_preparation_items_table` | W7 | Aditif |
| 18 | `2026_10_08_000600_add_interest_regions_to_user_preferences_table` | W7 | Aditif |
| 19 | `2026_10_09_000100_add_provenance_to_official_statuses_table` | W8 | Kueri pemeriksaan Konflik W8 nomor 1 dan 11 |
| 20 | `2026_10_09_000200_create_authority_members_table` | W8 | Aditif, RLS menyala |
| 21 | `2026_10_09_000300_create_data_error_reports_table` | W8 | Aditif, RLS menyala |
| 22 | `2026_10_09_000400_create_volcano_statuses_table` | W8 | Aditif |
| 23 | `2026_10_09_000500_create_mountain_photos_table` | W8 | Aditif, RLS menyala |
| 24 | `2026_10_09_000600_make_trail_characteristics_nullable` | W8 | Nilai lama tidak diubah; kurasi jalur lama butir izin akhir 6 |
| 25 | `2026_10_09_000700_clear_copied_elevation_loss_on_trails` | W8 | Migrasi data: mengosongkan turunan yang disalin dari tanjakan |
| 26 | `2026_10_09_000800_add_kind_to_trip_preparation_items_table` | W8 | Aditif |
| 27 | `2026_10_09_000810_mark_permit_preparation_items` | W8 | Migrasi data: mengganti label butir izin trip yang sudah ada (Konflik W8 nomor 10) |

## Ringkasan perubahan per gelombang

Gelombang dikerjakan di pohon kerja tanpa commit, jadi seri commit per gelombang disusun dari ledger masing-masing
gelombang di `.superpowers/sdd/` saat permintaan izin akhir. Angka simulasi di bawah dihitung dari salinan
scratchpad dan hanya perkiraan bagi repo nyata, karena putaran perbaikan tinjauan mengubah berkasnya (INFERENCE).

| Gelombang | Rencana | Berkas baru, diubah, dihapus di simulasi | Migrasi |
|---|---|---|---|
| W1 Fondasi desain | `docs/superpowers/plans/2026-09-26-memount-v3-w1-fondasi-desain.md` | Ledger W1 | 0 |
| W2 Kerangka | `docs/superpowers/plans/2026-09-27-memount-v3-w2-kerangka.md` | Ledger W2 | 0 |
| W3 Klaster Jelajah | `docs/superpowers/plans/2026-09-27-memount-v3-w3-jelajah.md` | Ledger W3 | 1 |
| W4 Klaster Trip | `docs/superpowers/plans/2026-10-06-memount-v3-w4-trip.md` | 374 entri sejak M1 sampai akhir W4 (ledger W4, Tugas 8) | 0 |
| W5 Beranda | `docs/superpowers/plans/2026-10-07-memount-v3-w5-beranda.md` | 20, 35, 0 | 0 |
| W6 Mendaki | `docs/superpowers/plans/2026-10-07-memount-v3-w6-mendaki.md` | 65, 58, 1 | 5 |
| W7 Progres dan Profil | `docs/superpowers/plans/2026-10-08-memount-v3-w7-progres-profil.md` | 63, 53, 3 | 6 |
| W8 Data resmi | `docs/superpowers/plans/2026-10-08-memount-v3-w8-data-resmi.md` | 92, 97, 1 | 9 |
| W9 Penutup | `docs/superpowers/plans/2026-10-09-memount-v3-w9-penutup.md` | angka akhir dari Tugas 15 | 0 |
```

Buat `docs/riset/2026-10-uji-kegunaan/materi-sesi-w9.md`:

````markdown
# Materi sesi uji kegunaan W9 (Q15)

Materi ini menyiapkan sesi uji kegunaan dengan 5 sampai 8 pendaki yang dijalankan pemilik (spec v3 bagian 15, Q15).
Protokol induknya `docs/PROTOKOL-UJI-KEGUNAAN.md`: heuristik, uji tugas, SUS, dan pembaca layar. Berkas ini hanya
menambahkan pemetaan tugas ke permukaan v3 dan tiga uji Q15. **Berkas ini tidak memuat hasil.** Hasil dicatat di
halaman Admin, Studi Kegunaan (`/admin/usability`), dan tidak pernah dikarang (R-023).

## Prasyarat

- **Data.** Sesi butuh aplikasi yang berjalan dengan data. Pratinjau statis tidak menjalankan kiriman apa pun, jadi
  sesi menunggu salah satu izin: `memount:demo` ke SQLite lokal (Q1) atau lingkungan uji sesudah deploy diizinkan.
- **Perangkat.** Android nyata untuk Q15 (render huruf dan angka), ditambah satu laptop untuk lebar 1440 px.
- **Persetujuan peserta.** Rekam layar dan suara hanya bila peserta setuju, diminta sebelum sesi dimulai. Peserta
  dicatat sebagai P01, P02, dan seterusnya, tanpa nama di catatan sesi.
- **Akun.** Satu akun uji per peserta, dibuat peserta sendiri di T1, dan dihapus sesudah sesi lewat Profil, Hapus
  akun.

## Naskah pembuka fasilitator

Bacakan apa adanya:

> Terima kasih sudah mau mencoba. Yang diuji hari ini aplikasinya, bukan Anda, jadi tidak ada jawaban yang salah.
> Tolong ucapkan apa yang Anda pikirkan sambil memakai aplikasinya, termasuk saat ragu atau bingung. Saya tidak akan
> membantu selama tugas berjalan, karena kami ingin melihat di mana aplikasinya membuat Anda tersendat. Anda boleh
> berhenti kapan saja.

## Tugas pada permukaan v3

Kriteria berhasil sama dengan yang tercatat di halaman Studi Kegunaan. Kolom "Yang diamati" menambah hal yang baru
ada sejak W1 sampai W8.

**T1.** Buat akun dan lengkapi profil pendaki. Mulai dari halaman tamu `/`. Melewati onboarding: pengalaman diturunkan
dari jalur terberat yang dipilih (DK-41). Yang diamati: apakah peserta mengerti mengapa ditanya jalur terberatnya, dan
apakah ia menemukan Beranda sesudahnya.

**T2.** Cari tahu jalur mana yang sesuai. Mulai dari Beranda atau tab Jelajah. Yang diamati: apakah label kecocokan
di kartu terbaca tanpa angka, dan apakah ia membuka "Cocokkah untuk saya?" tanpa dituntun.

**T3.** Jelaskan mengapa satu jalur Kurang cocok. Yang diamati: apakah alasan per faktor dibaca dan diulang dengan
kata-kata peserta sendiri. Ini tugas terpenting.

**T4.** Buat rencana trip dan siapkan perlengkapan. Mulai dari "Rencanakan trip" di rincian jalur. Yang diamati:
apakah butir yang menunggu prakiraan BMKG dikira wajib dicentang sekarang, dan apakah Urungkan ditemukan sesudah
salah centang.

**T5.** Periksa apa yang masih kurang sebelum berangkat. Yang diamati: apakah "Persiapan kritis lengkap" dibaca
sebagai jaminan keselamatan (R-014). Bila ya, catat kalimat peserta persis.

**T6.** Cari status resmi jalur dan sumbernya. Yang diamati: apakah status resmi (garis penuh) dibedakan dari laporan
sesama pendaki (garis putus-putus), dan apakah kalimat "Status resmi menjelaskan ketentuan pengelola, bukan jaminan
keselamatan." terbaca (R-010, R-012).

**T7.** Buka aplikasi tanpa sinyal. Matikan jaringan perangkat lebih dulu. Yang diamati: apakah shell luring dibaca
sebagai keputusan sengaja. Paket lapangan masih di balik sakelar yang mati (Q10, Q14), jadi yang tampil hanya teks.

**T8.** Tolak satu laporan komunitas beserta alasannya, khusus peserta moderator. Yang diamati: apakah moderator tahu
alasannya akan dibaca pelapor.

## Uji Q15

### G1. Pengenalan glyph domain

Tiga glyph pilot di `resources/js/components/ui/ikon/glyphDomain.ts`: `pos`, `tanjakan`, dan `resmi`. Tampilkan
satu per satu tanpa label, di 24 px dan 16 px, pada tema terang lalu gelap, dari halaman pameran primitif
(`/dev/primitif`, hanya di lingkungan lokal).

Pertanyaan, dibacakan sama untuk setiap glyph: "Menurut Anda, tanda ini menandai apa di aplikasi pendakian?"

Berhasil bila peserta menyebut artinya tanpa petunjuk. Catat jawaban persis, bukan tafsiran fasilitator. Set lengkap
8 sampai 12 glyph (DK-39) hanya dilanjutkan bila ketiga pilot dikenali sebagian besar peserta; ambangnya diputus
pemilik sebelum sesi.

### G2. Keterbacaan angka dan jam

Di Android nyata, ponsel sejauh lengan, sekali di dalam ruangan dan sekali di bawah cahaya luar ruangan. Minta
peserta membacakan dari kartu jalur dan ruang kerja trip: jarak (misalnya "8,0 km"), tanjakan dalam meter, rentang
waktu tempuh, dan jam berzona (misalnya "07.00 WIB"). Catat setiap salah baca beserta angkanya.

### G3. Mode lapangan di luar ruangan

Buka mode lapangan pada tema terang lalu gelap di bawah cahaya luar ruangan. Tanyakan pos berikutnya dan sisa jarak,
lalu ukur detik sampai peserta menjawab. Catat bila peserta memiringkan atau menaungi layar.

## Lembar catatan G1 sampai G3

```
Uji      : G__
Peserta  : P__
Tema     : terang / gelap
Cahaya   : dalam ruangan / luar ruangan
Jawaban persis peserta:
Salah baca (G2) atau detik sampai menjawab (G3):
Catatan fasilitator:
```

## Sesudah sesi

- Catat setiap tugas T1 sampai T8 dan setiap lembar SUS di `/admin/usability`. Rata-rata SUS tidak dikutip sebelum
  12 responden, sesuai halaman itu.
- Temuan G1 sampai G3 ditulis ke `DESIGN.md` hanya sesudah dibaca pemilik, karena glyph dan huruf adalah arah desain
  (DK-38, DK-39).
- Setiap temuan yang dapat diuji ditulis sebagai test yang gagal lebih dulu (`docs/PROTOKOL-UJI-KEGUNAAN.md`, Setelah
  selesai).
````

`app/Services/UsabilityStudyService.php`:

1. Ganti:

```php
        'T1' => ['judul' => 'Buat akun dan lengkapi profil pendaki', 'berhasil' => 'Profil tersimpan dan peserta sampai di dasbor.', 'inti' => false],
        'T2' => ['judul' => 'Cari tahu jalur mana yang sesuai', 'berhasil' => 'Peserta melihat daftar hasil kecocokan.', 'inti' => false],
        'T3' => ['judul' => 'Jelaskan mengapa satu jalur tidak direkomendasikan', 'berhasil' => 'Peserta menyebut alasannya dengan kata-katanya sendiri.', 'inti' => true],
        'T4' => ['judul' => 'Buat rencana trip dan siapkan perlengkapan', 'berhasil' => 'Trip terbuat dan lima item persiapan dikonfirmasi.', 'inti' => false],
        'T5' => ['judul' => 'Periksa apakah sudah siap berangkat', 'berhasil' => 'Peserta sampai di halaman kesiapan dan menyebut apa yang kurang.', 'inti' => true],
```

dengan:

```php
        'T1' => ['judul' => 'Buat akun dan lengkapi profil pendaki', 'berhasil' => 'Profil tersimpan dan peserta sampai di Beranda.', 'inti' => false],
        'T2' => ['judul' => 'Cari tahu jalur mana yang sesuai', 'berhasil' => 'Peserta melihat daftar hasil kecocokan.', 'inti' => false],
        'T3' => ['judul' => 'Jelaskan mengapa satu jalur tidak direkomendasikan', 'berhasil' => 'Peserta menyebut alasannya dengan kata-katanya sendiri.', 'inti' => true],
        'T4' => ['judul' => 'Buat rencana trip dan siapkan perlengkapan', 'berhasil' => 'Trip terbuat dan lima item persiapan dikonfirmasi.', 'inti' => false],
        'T5' => ['judul' => 'Periksa apa yang masih kurang sebelum berangkat', 'berhasil' => 'Peserta sampai di halaman kesiapan dan menyebut apa yang kurang.', 'inti' => true],
```

`docs/PROTOKOL-UJI-KEGUNAAN.md`:

2. Ganti:

```markdown
Mulai dari halaman depan. Berhasil bila profil tersimpan dan peserta sampai di dasbor.
```

dengan:

```markdown
Mulai dari halaman depan. Berhasil bila profil tersimpan dan peserta sampai di Beranda.
```

3. Ganti:

```markdown
Yang diamati: apakah ia menemukan "Buat Rencana" tanpa dituntun? Berapa lama?
```

dengan:

```markdown
Yang diamati: apakah ia menemukan "Cocokkah untuk saya?" atau "Rencanakan trip" tanpa dituntun? Berapa lama?
```

4. Ganti:

```markdown
**T5. Periksa apakah Anda sudah siap berangkat.**
Berhasil bila peserta sampai di halaman kesiapan dan dapat menyebutkan apa yang masih kurang.
Yang diamati: apakah ia mengira hasil "siap" berarti aman? Ini yang paling berbahaya kalau salah dipahami.
```

dengan:

```markdown
**T5. Periksa apa yang masih kurang sebelum Anda berangkat.**
Berhasil bila peserta sampai di halaman kesiapan dan dapat menyebutkan apa yang masih kurang.
Yang diamati: apakah ia mengira "Persiapan kritis lengkap" berarti aman? Ini yang paling berbahaya kalau salah dipahami.
```

`tailwind.config.js`:

5. Ganti:

```js
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
```

dengan:

```js
    // View terkompilasi (storage/framework/views) tidak dipindai: isinya bergantung pada
    // view yang pernah dirender di mesin itu, termasuk halaman pengecualian bawaan Laravel
    // dan view basi, sehingga CSS lokal berbeda dari build produksi yang bersih (W2-C10).
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
```

- [ ] **Step 4: Jalankan dan pastikan lulus.**

Run: `php artisan test --compact --filter='ReleaseMaterialsTest|InertiaLayoutTest|UsabilityStudy|AuditCitationTest'`
Expected: PASS; pada putaran ulang 34 test, 34 lulus. Bila repo nyata punya migrasi sesudah `2026_09_21_000100` yang tidak ada di tabel (perbaikan W5
sampai W8), tambahkan barisnya di posisi urut nama berkas dengan gelombang dan pemeriksaannya; jangan menghapus
penjaga.

- [ ] **Step 5: Format dan suite penuh.**

Run:
```bash
vendor/bin/pint --dirty --format agent
DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build
ls -l public/build/assets/app-*.css
php artisan test --compact --testsuite=Unit,Feature
```
Expected: Pint lulus; build sukses dengan CSS `app` 71.823 B pada putaran ulang (angka repo nyata dicatat apa adanya;
yang dijaga adalah build lokal yang sama dengan build bersih, bukan angkanya); suite 2.336 test, 2.334 lulus, 2 dilewati (sebelum tugas: 2.332).

- [ ] **Step 6: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 15: Fokus ke judul halaman galat sesudah aksi yang ditolak

Temuan audit aksesibilitas W9 (WCAG 2.4.3 dan 4.1.3) dari kritik halaman galat gabungan skill DK-57: kunjungan Inertia
yang gagal (429 sesudah Simpan, 419 sesudah sesi habis, 403) mengganti halaman tanpa muat penuh, tombol yang baru
ditekan hilang bersama halaman lama, fokus jatuh ke `body`, dan pembaca layar diam. Judul halaman `Galat` kini menerima
fokus saat dipasang (`tabIndex={-1}`, `preventScroll`), sehingga yang dibacakan pertama adalah apa yang terjadi
(W9-R21). Cincin fokus bawaan peramban tampil bila fokus tiba dengan modalitas papan ketik; sesudah klik tetikus
tidak (heuristik `:focus-visible`, INFERENCE, dicatat sebagai temuan audit 002 nomor 3 untuk dilihat koordinator).
Laporan aksesibilitas Tugas 12 diperbarui di baris 2.4.3.

**Files:**
- Create: `tests/Feature/ErrorPageFocusTest.php`
- Modify: `docs/audit/aksesibilitas-w9.md`
- Modify: `resources/js/pages/Galat.tsx`

**Interfaces:**
- Consumes: `resources/js/pages/Galat.tsx` (Tugas 1), `docs/audit/aksesibilitas-w9.md` (Tugas 12).
- Produces: tidak ada antarmuka baru.

- [ ] **Step 1: Tulis test yang gagal.**

Buat `tests/Feature/ErrorPageFocusTest.php`:

```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Halaman galat Inertia memindah fokus ke judulnya (audit aksesibilitas W9, WCAG 2.4.3 dan 4.1.3).
 *
 * Kunjungan Inertia yang gagal (429 sesudah Simpan, 419 sesudah sesi habis, 403) mengganti
 * halaman tanpa muat penuh. Tombol yang baru ditekan hilang bersama halaman lama, jadi fokus jatuh
 * ke body dan pembaca layar diam: pendaki tidak tahu aksinya ditolak. Judul galat kini menerima
 * fokus saat dipasang, sehingga yang dibacakan pertama adalah apa yang terjadi. Perilaku klien ini
 * dijaga dari sumbernya karena proyek belum punya runner uji klien (butir izin akhir 7 dan 25).
 */
class ErrorPageFocusTest extends TestCase
{
    public function test_the_inertia_error_page_moves_focus_to_its_title(): void
    {
        $halaman = File::get(resource_path('js/pages/Galat.tsx'));

        $this->assertStringContainsString('<h1 ref={judul} tabIndex={-1}', $halaman);
        $this->assertMatchesRegularExpression('/useEffect\(\(\) => \{\s*judul\.current\?\.focus\(\{ preventScroll: true \}\);\s*\}, \[galat\.kode\]\);/', $halaman);
    }
}
```

- [ ] **Step 2: Jalankan dan pastikan gagal.**

Run: `php artisan test --compact --filter=ErrorPageFocusTest`
Expected: FAIL; pada putaran ulang 1 test, 1 gagal.

- [ ] **Step 3: Implementasi.**

`docs/audit/aksesibilitas-w9.md`:

1. Ganti:

```markdown
| 2.4.3 Urutan fokus | `tests/Feature/ActiveHikeStripTest.php` menjaga strip pendakian dijangkau sesudah isi halaman | Tab mengikuti urutan tampilan | PERLU KLARIFIKASI sampai klik-lewat |
```

dengan:

```markdown
| 2.4.3 Urutan fokus | `tests/Feature/ActiveHikeStripTest.php` menjaga strip pendakian dijangkau sesudah isi halaman; `tests/Feature/ErrorPageFocusTest.php::test_the_inertia_error_page_moves_focus_to_its_title` | Tab mengikuti urutan tampilan; sesudah aksi yang ditolak 429, fokus berada di judul galat | PERLU KLARIFIKASI sampai klik-lewat |
```

`resources/js/pages/Galat.tsx`:

2. Ganti:

```tsx
import { Lockup } from '@/components/brand/Lockup';
```

dengan:

```tsx
import { useEffect, useRef } from 'react';
import { Lockup } from '@/components/brand/Lockup';
```

3. Ganti:

```tsx
export default function Galat({ galat }: { galat: IsiGalat }) {
    return (
```

dengan:

```tsx
export default function Galat({ galat }: { galat: IsiGalat }) {
    const judul = useRef<HTMLHeadingElement>(null);

    // Halaman ini menggantikan halaman asal tanpa muat penuh sesudah aksi gagal, dan tombol yang
    // baru ditekan ikut hilang. Fokus dipindah ke judul supaya pembaca layar menyebut apa yang
    // terjadi (audit aksesibilitas W9; ErrorPageFocusTest).
    useEffect(() => {
        judul.current?.focus({ preventScroll: true });
    }, [galat.kode]);

    return (
```

4. Ganti:

```tsx
                <h1 className="mt-2 text-judul text-primary">{galat.judul}</h1>
```

dengan:

```tsx
                <h1 ref={judul} tabIndex={-1} className="mt-2 text-judul text-primary">{galat.judul}</h1>
```

- [ ] **Step 4: Jalankan dan pastikan lulus.**

Run: `php artisan test --compact --filter='ErrorPageFocusTest|InertiaErrorPageTest|ErrorPagesTest|AuditCitationTest|FocusIndicatorTest|KeyboardAndScreenReaderTest'`
Expected: PASS; pada putaran ulang 40 test, 40 lulus.

- [ ] **Step 5: Build, pratinjau galat, dan suite penuh.**

Run:
```bash
DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build
node_modules/.bin/tsc --noEmit
php artisan test --compact --testsuite=Pratinjau --filter=HalamanGalatPratinjauTest
php artisan test --compact --testsuite=Unit,Feature
```
Expected: build sukses, `tsc` tanpa galat, pratinjau galat 4 test lulus, suite 2.337 test, 2.335 lulus, 2 dilewati (sebelum tugas: 2.336).

- [ ] **Step 6: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 16: Verifikasi koordinator dan satu permintaan izin akhir

Dikerjakan koordinator, bukan implementer. Rencana induk W9 "Selesai bila": Delivery Gate antislop S1 sampai S8
dengan bukti, tanpa cacat kritis tersisa. Tugas ini menjalankan gerbang lokal, klik-lewat sungguhan di peramban lewat
harness pratinjau, mengisi audit 002, memperbarui `HANDOVER.md` bagian 0, lalu menyusun satu permintaan izin akhir
untuk pemilik dari templat Lampiran B. Setiap angka yang ditulis berasal dari keluaran perintah atau peramban pada
saat itu (R-023); angka simulasi di rencana ini hanya harapan.

**Files:**
- Create: `docs/audit/anti-slop/audit-002-2026-10-09.md`
- Modify: `HANDOVER.md`
- Modify (koordinator, Step 2 sampai 5): `docs/audit/kandidat-rilis-w9.md`, `docs/audit/klik-lewat/2026-10-09-daftar-periksa-w9.md`, `docs/audit/keamanan-w9.md`, `.superpowers/sdd/2026-09-26-memount-v3-program.md`
- Create (koordinator, Step 3 dan 6): `docs/audit/klik-lewat/w9/*.webp`, `.superpowers/sdd/2026-10-09-memount-v3-w9-penutup/permintaan-izin-akhir.md`

**Interfaces:**
- Consumes: semua keluaran Tugas 1 sampai 15; ledger W5 sampai W9; pelacak program; Lampiran B.
- Produces: audit 002, subbagian W9 di `HANDOVER.md`, isian Hasil daftar periksa klik-lewat dan gerbang lokal kandidat rilis, dan berkas `.superpowers/sdd/2026-10-09-memount-v3-w9-penutup/permintaan-izin-akhir.md`.

- [ ] **Step 1: Pastikan tugas sebelumnya bersih.** Ledger W9 mencatat Tugas 1 sampai 15 selesai dan ditinjau, tanpa
temuan Critical atau Important terbuka.

- [ ] **Step 2: Gerbang lokal.** Jalankan berurutan dan tulis hasilnya di kolom Hasil `docs/audit/kandidat-rilis-w9.md`:

```bash
php artisan test --compact --testsuite=Unit,Feature
php artisan test --compact
php artisan test --compact --testsuite=Pratinjau
node_modules/.bin/tsc --noEmit
DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build
ls -l public/build/assets/app-*.css
vendor/bin/pint --format agent
LC_ALL=C grep -rlP --exclude='audit-001*' '\xE2\x80\x94' app resources tests database config DESIGN.md docs/ARCHITECTURE.md docs/fitur docs/audit
```
Expected (simulasi; tulis angka nyata): Unit dan Feature 2.337 test, 2.335 lulus, 2 dilewati; suite bawaan 2.419 test, 2.335 lulus, 84 dilewati (ulangan; jalan pertama 1 gagal karena pergantian menit di test W8 `AuditLogChangeTest`); Pratinjau
75 test lulus; `tsc` tanpa galat; build sukses dengan CSS `app` 71.823 B; Pint lulus tanpa berkas yang diubah; pemindaian em dash tanpa
keluaran. Spatial 82 test, semuanya dilewati tanpa `SPATIAL_TEST_DSN`; kolom Hasil-nya "menunggu CI PostGIS (syarat rilis 1)".

- [ ] **Step 3: Klik-lewat di peramban.** Ikuti `docs/audit/klik-lewat/2026-10-09-daftar-periksa-w9.md` dari awal sampai
akhir dengan server pratinjau `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php -S 127.0.0.1:8100 -t public
tests/Pratinjau/statis.php` (latar belakang; hentikan sesudahnya). Untuk setiap halaman: 390 px dan 1440 px, terang dan
gelap (tema lapangan untuk mode lapangan, kartu darurat, dan Mendaki), pohon aksesibilitas, klik setiap kontrol, reflow
320 px, jalur papan ketik, `prefers-reduced-motion: reduce`, dan `forced-colors: active`. Simpan tangkapan di
`docs/audit/klik-lewat/w9/<nama>-<lebar>-<tema>.webp` dan isi tabel Hasil, satu baris per halaman dan lebar. Kontrol
yang mengirim ke server berstatus MENUNGGU SERVER dengan nama test Feature rutenya. Sesudah aksi yang ditolak 429 di
pratinjau `galat-429`, lihat cincin fokus di judul dan catat putusan temuan audit 002 nomor 3.

- [ ] **Step 4: Audit 002 dan Delivery Gate.** Buat audit 002, lalu isi kolom Status setiap baris S1 sampai S8 dengan
FACT beserta buktinya, atau PERLU KLARIFIKASI beserta alasannya. Temuan baru dari klik-lewat ditambahkan dengan nomor
berlanjut dan prioritas tingkat aturannya.

Buat `docs/audit/anti-slop/audit-002-2026-10-09.md`:

```markdown
# Audit antislop 002

Tanggal: 9 Oktober 2026
Cakupan: pohon kerja kandidat rilis sesudah W1 sampai W9 (spec v3, rencana induk W9)
Mode: 2 (audit sesudah pekerjaan selesai) dan Delivery Gate S1 sampai S8 (spec v3 bagian 2)

Setiap butir menyebut buktinya. Tanpa bukti, statusnya PERLU KLARIFIKASI, bukan lulus (R-023). Prioritas temuan
mengikuti tingkat aturannya: Hard Gate tinggi, Purpose-Gate sedang, Quality Lock rendah. Tidak ada yang disunting dari
daftar temuan sampai pemilik memilih nomornya; nomor yang tidak disebut tidak disentuh.

---

## Delivery Gate S1 sampai S8

Kolom Status diisi koordinator di tugas verifikasi akhir W9 dari keluaran perintah dan peramban, bukan disalin dari
simulasi.

| Ukuran | Bukti yang dipakai | Status |
|---|---|---|
| S1 Layar ada dan setiap kontrol yang tampil berfungsi | Tabel Hasil di `docs/audit/klik-lewat/2026-10-09-daftar-periksa-w9.md`; `PreviewCoverageTest` (setiap halaman React punya pratinjau dan baris daftar periksa) | |
| S2 Angka, status, dan label dari sistem; data kosong menyebut siapa yang ditunggu | Test props per halaman (`assertInertia`), `EmptyStateCoverageTest`, `TrustGrammarTest` | |
| S3 Suite hijau, `tsc` nol, build, Pint | Gerbang lokal di `docs/audit/kandidat-rilis-w9.md` | |
| S4 Kontras token di tema terang, gelap, dan lapangan | `ColourContrastTest` | |
| S5 Tangkapan 390 px dan 1440 px, terang dan gelap, disandingkan acuan | Folder `docs/desain/tangkapan/w1` sampai `w8`, ditambah tangkapan halaman galat W9 | |
| S6 Anggaran kueri tidak tumbuh bersama data | `PageQueryBudgetTest`, `NewPageQueryBudgetTest`, `ResultListBudgetTest`, `CandidateCapTest`, `FollowedMountainsOnceTest` | |
| S7 Delivery Gate antislop PASS per butir | Bagian Temuan di bawah: tidak ada temuan TINGGI yang terbuka tanpa putusan tertulis | |
| S8 `HANDOVER.md` bagian 0 memuat keadaan nyata dan prompt lanjut | Subbagian W9 di `HANDOVER.md` | |

---

## TINGGI

### 1. R-35 dan R-26: kendali yang mengirim ke server belum pernah diklik di peramban

Pratinjau statis tidak menjalankan `index.php`, jadi setiap kiriman POST, PUT, dan DELETE berstatus MENUNGGU SERVER di
daftar periksa klik-lewat. Buktinya test Feature per rute, bukan klik. Menutupnya butuh server lokal dengan data contoh
(Q1, `memount:demo`) atau runner end-to-end (butir izin akhir 7). Sampai itu, butir ini terbuka dengan putusan tertulis.

---

## SEDANG

### 2. R-09: dua rupa kepala untuk halaman galat yang sama

Halaman galat muat penuh (Blade) memakai wordmark saja, sedangkan halaman galat kunjungan Inertia (React) memakai tanda
dan wordmark lewat `Lockup`. Pendaki yang menemui galat yang sama lewat dua jalan melihat kepala yang berbeda. Perbaikan
yang mungkin: komponen Blade untuk tanda merek dengan path yang sama dengan `resources/js/components/brand/Mark.tsx`.

### 3. R-32: cincin fokus bawaan peramban di judul halaman galat

Sejak W9 Tugas 15 judul halaman galat Inertia menerima fokus saat dipasang. Bila fokus tiba dengan modalitas papan
ketik, peramban menggambar cincin bawaan di sekeliling judul; sesudah klik tetikus tidak (heuristik `:focus-visible`,
INFERENCE). Perlu dilihat koordinator di peramban sesudah aksi yang ditolak 429, lalu diputus: dibiarkan sebagai
penanda posisi, atau diberi gaya fokus token.

---

## RENDAH

### 4. R-02: em dash di dokumen yang bukan teks antarmuka

Baris yang memuat em dash, terukur di simulasi W9: `PRODUCT.md` 10, `docs/AUDIT-PROTOCOL.md` 25, `HANDOVER.md` 17
(bagian riwayat), `AGENTS.md` 49 (sebagian di blok Laravel Boost yang dibangkitkan), `CLAUDE.md` 12 (kontrak pemilik),
dan `docs/audit/anti-slop/audit-001-2026-09-20.md` 10. Teks antarmuka, kode, test, `DESIGN.md`,
`docs/ARCHITECTURE.md`, `docs/fitur`, dan dokumen audit W9 nol. Angka repo nyata diukur ulang saat eksekusi dengan
`LC_ALL=C grep -c $'\xE2\x80\x94' <berkas>`.

### 5. R-27: Beranda memotong kandidat tanpa menyebutnya

"Jalur lain untuk Anda" menilai paling banyak 500 jalur pertama menurut id, tanpa kalimat terpotong, sedangkan Jelajah
menyebut batasnya. Tidak berdampak pada katalog sekarang (puluhan jalur) dan tercatat sebagai keputusan produk di
permintaan izin akhir, karena mengubah cara kandidat dipilih adalah perubahan Route Fit (R-021).

---

## Dipertimbangkan dan dinyatakan lolos

- **R-02 di teks antarmuka.** `SafetyClaimWordTest` dan pemindaian em dash pada kode dan dokumen antarmuka.
- **Kata klaim keselamatan.** "aman", "sehat", "dijamin", "terjamin", dan "pasti" hanya muncul dalam empat kalimat
  penyangkalan yang disebut di `SafetyClaimWordTest`.
- **R-26 (kendali yang tidak berfungsi).** Setiap halaman React punya pratinjau (`PreviewCoverageTest`); halaman galat
  menawarkan tindakan yang dapat dibuka dengan GET saja.
- **R-25 dan R-32.** Kontras token dihitung (`ColourContrastTest`); fokus tidak pernah dihapus tanpa pengganti
  (`FocusIndicatorTest`).
- **Reflow 320 px halaman galat.** `scrollWidth` sama dengan `clientWidth` di keempat halaman galat (simulasi W9).

---

## Yang berikutnya

Tidak ada yang disunting sampai pemilik memilih nomornya. Nomor yang tidak disebut tidak disentuh.
```

`HANDOVER.md`:

1. Ganti:

```markdown
> `php artisan test --compact --testsuite=Unit,Feature`, `npx tsc --noEmit`. Jangan commit, push, mengakses
> Supabase, menjalankan migrasi, atau menambah dependensi tanpa izin saya.

### W3 Klaster Jelajah (selesai 6 Oktober 2026, menunggu izin commit)
```

dengan:

```markdown
> `php artisan test --compact --testsuite=Unit,Feature`, `npx tsc --noEmit`. Jangan commit, push, mengakses
> Supabase, menjalankan migrasi, atau menambah dependensi tanpa izin saya.

### W9 Penutup (selesai 9 Oktober 2026, menunggu permintaan izin akhir)

Rencana `docs/superpowers/plans/2026-10-09-memount-v3-w9-penutup.md`, 16 tugas. Ledger, ruling, dan permintaan izin
akhir ada di `.superpowers/sdd/2026-10-09-memount-v3-w9-penutup/`. W9 tidak menambah fitur produk dan tidak menambah
migrasi: ia menutup titipan W1 sampai W8, menjalankan klik-lewat dan Delivery Gate, mengaudit aksesibilitas dan
keamanan, memperbarui dokumen, dan menyiapkan kandidat rilis.

| Butir | Nilai | Bukti |
|---|---|---|
| Suite Unit dan Feature | 2.334 test, 2.332 lulus, 2 dilewati, 0 gagal | `php artisan test --compact --testsuite=Unit,Feature` |
| Suite Spatial | 82 test, semuanya dilewati tanpa `SPATIAL_TEST_DSN`; menunggu CI PostGIS | `php artisan test --compact --testsuite=Spatial` |
| Pratinjau | 75 test; setiap halaman React punya pratinjau | `php artisan test --compact --testsuite=Pratinjau`, `PreviewCoverageTest` |
| TypeScript, build, Pint | `tsc` 0 galat, build sukses, Pint lulus; CSS lokal sama dengan build produksi bersih (W2-C10) | `docs/audit/kandidat-rilis-w9.md` |
| Klik-lewat | lihat tabel Hasil | `docs/audit/klik-lewat/2026-10-09-daftar-periksa-w9.md` |
| Delivery Gate S1 sampai S8 | lihat audit 002 | `docs/audit/anti-slop/audit-002-2026-10-09.md` |
| Aksesibilitas dan keamanan | peta test per kriteria dan per butir `CLAUDE.md` bagian 4 | `docs/audit/aksesibilitas-w9.md`, `docs/audit/keamanan-w9.md` |

Yang dibangun W9:
- halaman galat 403, 404, 419, 429, 500, dan 503 berbahasa Indonesia untuk muat penuh dan kunjungan Inertia, dari
  satu sumber kata (`App\Support\HalamanGalat`), dengan fokus pindah ke judul;
- jam bertitik berzona dan tahun pada tanggal di luar tahun berjalan; kosakata Indonesia di kalimat mesin dan satu
  kalimat status resmi di panel "Kenapa?"; tanpa kata klaim keselamatan di teks yang dibaca pendaki;
- satu pengumuman per aksi simpan, kontras ruas kosong, target remah 44 px, kolom aksi 1440 px;
- sesi dan batas laju di setiap rute tulis, proxy tepercaya lewat `TRUSTED_PROXIES`, rute berkas bertanda tangan
  dimatikan, penanda pemulihan Simpanan yang kedaluwarsa;
- kunci baris pada daftar Persiapan pertama, test kunci PostgreSQL untuk batas per pendaki;
- anggaran Jelajah dan Beranda pada batas 500 kandidat, gunung yang diikuti dibaca sekali, aturan izin penjadwal
  pengingat dibaca sekali per putaran;
- enkripsi riwayat dan pemangkasan tautan bagikan di balik sakelar yang mati sampai pemilik memutus kebijakannya;
- pratinjau untuk 29 halaman yang belum punya, daftar periksa klik-lewat, audit aksesibilitas dan keamanan, GR-08 dan
  log keputusan DK-30 sampai DK-58, drift `PRODUCT.md` dan `docs/ARCHITECTURE.md`, materi uji kegunaan Q15, dan
  dokumen kandidat rilis dengan urutan deploy 27 migrasi.

Titik lanjut: permintaan izin akhir di ledger W9, diajukan sekali kepada pemilik. Sampai dijawab, tidak ada commit,
push, migrasi Supabase, atau deploy.

### W3 Klaster Jelajah (selesai 6 Oktober 2026, menunggu izin commit)
```

Di repo nyata judul yang mengikuti blok tempel di bagian 0 adalah subbagian gelombang terakhir, bukan W3 (8 Oktober:
`### W4 Klaster Trip (selesai 7 Oktober 2026, menunggu izin commit)`; sesudah W8: judul subbagian W8), dan dua baris
blok tempel itu muncul dua kali di berkas, jadi judul itulah yang membuat teks lama unik. Pakai judul itu di baris
terakhir teks lama dan teks baru blok `HANDOVER.md` di atas; subbagian W9 tetap disisipkan tepat di atasnya.

Temuan keamanan yang sudah diketahui saat rencana ditulis (Konflik 2) ditambahkan sebagai butir terakhir bagian "Yang
belum dapat dibuktikan agen" di `docs/audit/keamanan-w9.md`, dengan teks berikut:

```markdown
- **CSP `script-src`.** `SecurityHeaders` masih mengizinkan `'unsafe-eval'` dan `'unsafe-inline'` dengan alasan Alpine
  dan Livewire, padahal Alpine tidak ada di `package.json` dan tidak ada view Blade ber-`x-data` (FACT, Grep).
  Mengencangkannya menunggu pencabutan Livewire dan klik-lewat di bawah CSP (Konflik 2 rencana W9). PERLU KLARIFIKASI.
```

- [ ] **Step 5: HANDOVER.** Blok `HANDOVER.md` di Step 4 memuat angka simulasi; sebelum disimpan, ganti setiap angka di
tabelnya dengan keluaran Step 2 dan isi tanggal selesai nyata. Perbarui baris "Terakhir diperbarui" di kepala berkas
menjadi tanggal itu dengan "(bagian 0, W9 selesai)". Perbarui baris W9 di `.superpowers/sdd/2026-09-26-memount-v3-program.md`.

- [ ] **Step 6: Satu permintaan izin akhir.** Susun dari templat Lampiran B dan simpan ke
`.superpowers/sdd/2026-10-09-memount-v3-w9-penutup/permintaan-izin-akhir.md`, lalu ajukan sekali kepada pemilik. Sebelum
diajukan, periksa: setiap butir pelacak 1 sampai 29 ada (termasuk yang bertambah sesudah 8 Oktober), setiap syarat
deploy W3, W4, W6, W7, W8, dan W9 ada, setiap keputusan terbuka punya rekomendasi dan akibat bila salah, urutan commit
per gelombang dan urutan migrasi sama dengan `docs/audit/kandidat-rilis-w9.md`.

- [ ] **Step 7: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik dijawab; catat berkas yang berubah.

## Tinjauan diri

### Cakupan induk W9 dan titipan

| Butir | Tugas | Bukti di rencana |
|---|---|---|
| Butir 1: klik-lewat setiap kontrol di 390 px dan 1440 px, terang, gelap, dan lapangan, papan ketik, gerak dikurangi, `forced-colors`; bukti di `docs/audit/` | Tugas 11 (pratinjau, daftar periksa, penjaga), Tugas 16 Step 3 (klik-lewat sungguhan) | `PreviewCoverageTest` (2 test), `HalamanPenutupPratinjauTest` (pratinjau 43 menjadi 75 bersama `HalamanGalatPratinjauTest`), `docs/audit/klik-lewat/2026-10-09-daftar-periksa-w9.md`; keterbatasan tanpa Playwright dan Vitest ditulis di daftar periksa, tidak diklaim lulus |
| Butir 2: Delivery Gate antislop dengan bukti per butir; temuan di `docs/audit/anti-slop/` bernomor berlanjut | Tugas 16 Step 4 | Audit 002 dengan tabel S1 sampai S8 dan lima temuan; kolom Status diisi koordinator |
| Butir 3: audit WCAG 2.2 AA dan tinjauan keamanan daftar `CLAUDE.md` bagian 4 | Tugas 12, 15, 5, 7 | `docs/audit/aksesibilitas-w9.md`, `docs/audit/keamanan-w9.md`, `AuditCitationTest`, `MassAssignmentSweepTest`, `ErrorPageFocusTest`, `WriteRouteGuardTest`, `TrustedProxyTest`, test Spatial kunci baris; RLS PERLU KLARIFIKASI |
| Butir 4: dokumen (GR §146, drift `PRODUCT.md`, `docs/ARCHITECTURE.md`, `README.md`, `CONTRIBUTING.md`, `HANDOVER.md`) | Tugas 13, 16 Step 5 | GR-08 dan log DK-30 sampai DK-58 (`DecisionLogTest`), `DocumentDriftTest`; amandemen paket lapangan tetap GR-07 karena Q7 terbuka |
| Butir 5: kandidat rilis (suite, `tsc`, build, Pint, Spatial di CI, status migrasi nyata, ringkasan diff) | Tugas 14, 16 Step 2 | `docs/audit/kandidat-rilis-w9.md` (`ReleaseMaterialsTest`), syarat rilis 1 sampai 7 menunggu izin; build CSS yang sama dengan produksi (`InertiaLayoutTest`) |
| Butir 6: uji kegunaan 5 sampai 8 pendaki (Q15) dijalankan pemilik | Tugas 14 | `docs/riset/2026-10-uji-kegunaan/materi-sesi-w9.md` tanpa hasil; teks tugas Studi Kegunaan diselaraskan (Konflik 11) |
| Selesai bila: Delivery Gate S1 sampai S8 dengan bukti, tanpa cacat kritis tersisa | Tugas 16 | Step 1 sampai 7; satu permintaan izin akhir dari Lampiran B |
| Titipan "Ke W9" | Tugas 1 sampai 15 | Peta titipan di Pra-cek; yang tidak dikerjakan punya ruling dan Konflik (W9-R25, R26, R31) |

### Pemindaian placeholder

Tidak ada "TBD", "TODO", "implement later", atau "similar to Task N". Setiap langkah kode memuat kodenya: berkas baru
ditulis utuh, tulis ulang memuat isi utuh, dan perubahan kecil memakai blok "Ganti ... dengan ...". Blok-blok itu
dirakit dari rantai simulasi dan diperiksa dua hal: teks lamanya muncul tepat sekali pada keadaan sebelum blok itu, dan
menerapkan semua blok berurutan menghasilkan berkas akhir tugas persis. Tidak ada blok yang diawali atau diakhiri baris
kosong, dan tidak ada blok pengganti yang kosong (penghapusan selalu membawa satu baris konteks). Satu-satunya tanda
isian adalah `[isi: ...]` di templat Lampiran B dan kolom Hasil atau Status dokumen audit, yang memang diisi
koordinator dari keluaran perintah (R-023).

### Konsistensi nama dan tipe

- **Galat (Tugas 1, 15):** `App\Support\HalamanGalat` (isi per kode, `tanggapiInertia()`), komponen halaman `Galat`
  dengan prop `galat` bertipe `IsiGalat`, pratinjau `galat-404`, `galat-429`, `galat-500`, `galat-429-muat-penuh`.
- **Waktu (Tugas 2, 10):** `Timezone::display(?Carbon, string, string = 'j M Y H.i')`,
  `Timezone::tanggalPendek(CarbonInterface, bool $denganHari = false, string $zona = self::DEFAULT)`; label jadwal
  `SchedulerHealthService::TUGAS[...]['jadwal']` bertitik.
- **Penjelasan (Tugas 3):** `RecommendationExplanationService::build(Trail, array $factors, array $warnings)`;
  pemanggil satu-satunya `RouteFitService::evaluate()`.
- **Simpan dan keamanan (Tugas 4, 5):** isian `dengan_toast`, kunci sesi `simpanan-pemulihan`, kunci throttle
  `profil`, `sandi`, `hapus-akun`, `onboarding`, `pilih-rekomendasi`, `jejak`; `MULAI_XL`.
- **Anggaran (Tugas 8):** `TrailNewsService::forUser(User, int $limit = 30, ?Collection $gunung = null)`,
  `terbaru(User, int, int, ?Collection $gunung = null)`; `TripReminderScheduler::prosesTrip(..., array $aturanIzin)`.
- **Sakelar (Tugas 6, 10):** `INERTIA_ENCRYPT_HISTORY` lewat `config('inertia.history.encrypt')`,
  `SHARE_LINK_PRUNE_AFTER_DAYS` lewat `config('hiking.share_links.prune_after_days')`, `ComparisonShare::prunable()`.

`tsc` lulus sesudah setiap tugas pada simulasi dan putaran ulang.

### Review Focus

Kelima baris Review Focus punya test di tugas pemilik kodenya: halaman galat yang bocor atau menyesatkan (Tugas 1 dan
15), rute tulis tanpa sesi atau batas laju dan alamat di balik proxy (Tugas 5), balapan PostgreSQL (Tugas 7, Spatial,
menunggu CI), teks yang menjanjikan atau salah zona (Tugas 2, 3, 9, 10), dan bukti yang tertinggal dari kode (Tugas
11 sampai 14). Semuanya lulus pada simulasi dan putaran ulang, kecuali test Spatial yang menunggu PostGIS.

### Pemeriksaan teks rencana

- Rencana ini tanpa em dash. Tanda "§" hanya muncul saat mengutip ID PRD; dokumen lain dirujuk "bagian N".
- Teks antarmuka baru berbahasa Indonesia dengan sapaan "Anda".
- Tidak ada klaim "aman", "sehat", "dijamin", "terjamin", atau "pasti". Kata itu hanya muncul di aturan rencana ini,
  di kutipan teks lama yang sedang diganti, di nama butir daftar `CLAUDE.md` bagian 4 ("unggahan tidak aman"), di
  uraian Tugas 9 tentang kalimat yang menyangkal atau mengaku tidak pasti, dan di empat kalimat penyangkalan yang
  disebut `SafetyClaimWordTest` (diperiksa dengan pemindaian di luar blok kode).
- Label kecocokan publik, bobot Route Fit, otoritas sumber, dan cara memilih kandidat tidak disentuh; perubahan yang
  menyentuh kebijakan data (riwayat, retensi), dependensi (Livewire), dan batas laju dibangun mati atau ditulis sebagai
  Konflik (R-021).

### Pemeriksaan test (testing-best-practices, `rules/review.md`)

- Test baru menguji perilaku lewat HTTP, perintah Artisan, atau layanan publik: halaman galat (status, komponen, props
  tanpa kerangka, Referer satu asal), batas laju (429 pada percobaan ketujuh), proxy tepercaya, penanda pemulihan yang
  kedaluwarsa, enkripsi riwayat, pemangkasan, kunci baris (Spatial), dan hitungan kueri yang dibandingkan antara dua
  ukuran data.
- Test penjaga sumber dipakai hanya bila perilakunya di luar jangkauan PHPUnit (format tanggal di belasan layanan, kata
  klaim di TSX, konten Tailwind, rujukan audit), mengikuti konvensi proyek (`InertiaLayoutTest`, `ThirdPartyLicenseTest`).
- Waktu dibekukan dengan `travelTo()` bila tanggal menentukan hasil (tahun berjalan, hari buka izin, retensi).
- Test lama yang menyandikan keputusan lama diperbarui dengan alasan di docblock: `ErrorPagesTest`,
  `PageHeaderContractTest`, `EmptyStateCoverageTest`, `PermitWindowReminderTest`, `ReminderSheetTest`,
  `TripReminderTest`, `WeatherTimezoneTest`, `IndonesianInterfaceTest`, `ReadinessDashboardTest`, `RefusalMessagesTest`,
  `TrailGeometryImportTest`, `SavedUndoTest`, dan `TrailIndexMapTest`.

### Paritas PostgreSQL (R-024)

Suite di rencana ini memakai SQLite dalam memori, jadi hijaunya tidak membuktikan perilaku PostgreSQL. W9 tidak
menambah migrasi, CHECK, atau indeks. Yang hanya dapat dibuktikan PostgreSQL adalah kunci baris: `generateFor()`
menunggu kunci trip (`PreparationFirstVisitLockTest`) dan empat batas per pendaki menunggu kunci pengguna
(`UserRowLockTest`), keduanya dengan `lock_timeout` dan SQLSTATE 55P03 di koneksi kedua. Kueri baru W9
(`requirementsForTrails()` di penjadwal, `whereIn` id gunung di Kabar, dan `prunable()` yang membandingkan
`expires_at` atau `revoked_at` dengan satu batas tanggal; pembanding dengan NULL bernilai tidak cocok di kedua
pengemudi) memakai bentuk yang sama di SQLite dan PostgreSQL. Rencana ini tidak menjalankan apa pun terhadap
PostgreSQL; tanpa PostGIS lokal, laporan Tugas 16 mencatat test Spatial W9 sebagai "tertulis, menunggu CI".

## Lampiran A: Aturan eksekusi W9

Salin lampiran ini ke `.superpowers/sdd/2026-10-09-memount-v3-w9-penutup/aturan-eksekusi.md` sebelum Tugas 1. Isinya
aturan eksekusi W8 dengan nama gelombang, rencana, port, dan larangan baru W9.

### Aturan eksekusi W9 (dibaca setiap implementer sebelum mulai)

Proyek: Memount, Laravel 13.31 + Inertia 3 + React 19.3 + TypeScript 5.9, PHP 8.5.10, di Windows dengan Git Bash.
Root repo: `E:/praktik_aplikasi_web`. Branch `hardening`. Commit terakhir `ddc27e8`; pohon kerja memuat perubahan yang
belum di-commit dari W1 sampai W8 dan gelombang ini. Folder `.agents/skills/` yang belum dilacak (keputusan Q19)
jangan disentuh.

#### Larangan mutlak (arahan pemilik dan kontrak proyek)

1. **Jangan commit, push, `git stash`, `git checkout -- <berkas>`, `git reset`, atau `git clean`.** Pemilik
   memutuskan tidak ada commit sampai seluruh program W1 sampai W9 selesai. Lewati setiap langkah "Commit"; catat
   berkas yang berubah di laporan.
2. **Jangan menyentuh Supabase dan jangan membaca atau mencetak isi `.env`.** `.env` menunjuk basis data produksi.
3. **Jangan menjalankan `php artisan migrate` dalam bentuk apa pun, `db:seed`, `db:wipe`, `model:prune`, tinker
   (termasuk `tinker --execute`), atau `php artisan memount:demo`.** Migrasi, seeder, dan pemangkasan hanya berjalan di
   dalam PHPUnit. W9 tidak menambah migrasi; migrasi lama tidak disunting.
4. **Setiap perintah Artisan yang mem-boot aplikasi di luar PHPUnit, `npm run build`, dan server statis `php -S`
   wajib diberi awalan** `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL=`. `php artisan test ...` tidak butuh awalan.
5. **Jangan menambah, mencabut, atau mengubah dependensi** (composer, npm), termasuk `livewire/livewire` dan
   `livewire/volt` (Konflik 2), jangan mengubah versi apa pun, dan jangan menjalankan `npx` apa pun. Pemeriksaan tipe
   memakai `node_modules/.bin/tsc --noEmit`.
6. **Jangan mengisi `TRUSTED_PROXIES`, `INERTIA_ENCRYPT_HISTORY`, atau `SHARE_LINK_PRUNE_AFTER_DAYS` di `.env` mana
   pun.** Nilainya keputusan pemilik (Konflik 1, 3, dan 4); test memakai `config()` di dalam PHPUnit.
7. **Jangan menyunting berkas di luar daftar "Files" tugas Anda**, kecuali brief atau dispatch menyebutnya. Bila perlu
   menyentuh berkas lain, berhenti dan laporkan NEEDS_CONTEXT.
8. **`HANDOVER.md`, `CLAUDE.md`, `AGENTS.md`, dan `docs/KEBIJAKAN-DATA-PRIBADI.md` tidak disunting implementer.**
   `HANDOVER.md` hanya disunting koordinator di Tugas 16. Blok `<laravel-boost-guidelines>` dan blok NgodingPakeAI
   tidak pernah disunting.
9. **Jangan menjalankan subagen.**
10. **Server `php artisan serve` di 127.0.0.1:8000 milik pemilik (memakai Supabase).** Jangan dipakai, dihentikan, atau
    dipanggil. Port 8100 milik koordinator (harness pratinjau Tugas 16), dan port 8180 milik penulis rencana.
    Implementer W9 tidak menyalakan server apa pun.
11. **Jangan mengarang hasil.** Kolom Hasil daftar periksa klik-lewat, gerbang lokal kandidat rilis, Status audit 002,
    dan materi uji kegunaan diisi dari keluaran perintah atau peramban saat itu, atau dibiarkan kosong (R-023).

#### Hook GateGuard

Lingkungan ini memasang hook "Fact-Forcing Gate". Perintah Bash pertama, perintah yang menghapus atau menimpa, dan
suntingan pertama setiap berkas bisa ditolak dengan permintaan fakta. Bila itu terjadi, tulis faktanya di balasan Anda
lalu ulangi panggilan yang sama:
- ringkasan permintaan: "Menjalankan Tugas N rencana W9 (docs/superpowers/plans/2026-10-09-memount-v3-w9-penutup.md) atas arahan pemilik";
- untuk Bash: apa yang diverifikasi atau dihasilkan perintah itu;
- untuk suntingan: berkas yang mengimpor atau merujuk berkas itu (cari dengan Grep), fungsi atau kelas publik yang terdampak, bentuk data bila ada;
- untuk berkas baru: berkas yang akan memanggilnya, bukti tidak ada berkas lain dengan tujuan sama, dan bentuk data;
- untuk penghapusan atau penimpaan: jalur yang terdampak dan cara mengembalikannya;
- instruksi pemilik verbatim: "ya, selesaikan semuanya baru minta izin commit dan integrasi nya jika belum selesai semua jangan minta ke saya, jika anda meminta perizinan artinya semua tugas anda telah selesai. lanjutkan!"

#### Konvensi kerangka dari W2 sampai W8, ditambah W9

- Komponen yang hidup di kerangka persisten memanggil `useKerangka()` untuk toast. Toast tertutup saat pemiliknya
  dilepas. Aksi yang diumumkan toast mengirim `dengan_toast`, dan server tidak menambah kabar halaman untuk aksi itu
  (W9-R6).
- Konfirmasi sesudah navigasi datang dari flash halaman tujuan (`status`); kabar aksi di halaman yang sama memakai
  `kabar-aksi` (W4-FK), dibacakan wilayah `role="status"` yang terpasang sejak halamannya tampil.
- Halaman galat tidak memakai kerangka, tidak menerima props bersama, dan tidak menyentuh basis data atau sesi. Kata
  galat hanya di `App\Support\HalamanGalat` (W9-R2).
- Jam di layar lewat `Timezone::display()` (bertitik, berzona) dan tanggal pendek lewat `Timezone::tanggalPendek()`
  (W9-R4). Isian `type=time` tetap berkolon.
- Rute tulis baru memakai `throttle` dengan kunci sendiri berbahasa Indonesia; konsol mengikuti `AWALAN_KONSOL`.
- Halaman React baru wajib punya pratinjau dan baris daftar periksa klik-lewat (`PreviewCoverageTest`).
- Muat ulang penuh karena versi aset baru hanya lewat `muatPenuhVersiBaru()` atau `muatUlangMilikKerangka()` di
  `resources/js/layouts/Kerangka.tsx`. Jangan memanggil `window.location.reload()` sendiri di halaman React.
- Primitif dan prop baru memakai nama Indonesia (`nilai`, `onUbah`, `label`).

#### Cara kerja

- Isi berkas dan kode di brief adalah isi yang harus ditulis, persis. Bila teks yang harus diganti tidak ditemukan
  persis, periksa dulu apakah tugas Anda memuat "Varian repo nyata" untuk blok itu; bila tidak, jangan menebak:
  laporkan NEEDS_CONTEXT dengan kutipan berkas nyata. Nomor baris di brief adalah petunjuk; yang mengikat adalah
  teksnya.
- Ikuti urutan langkah brief: test gagal dulu (catat keluaran merahnya), implementasi, build bila tugas menyentuh
  TypeScript, rute, atau `tailwind.config.js`, test lulus (catat keluaran hijaunya). Angka "pada simulasi rencana" di
  brief adalah acuan; bila angka nyata berbeda, laporkan apa adanya.
- Pint per berkas PHP tugas: `vendor/bin/pint --format agent <berkas>`. Setelah menyunting TypeScript atau rute: build
  lalu `node_modules/.bin/tsc --noEmit`.
- Dokumen dan teks: bahasa Indonesia, sapaan "Anda", **tanpa karakter em dash**, tanpa kata "aman", "sehat",
  "dijamin", "terjamin", atau "pasti" sebagai klaim. Konvensi rujukan: "§N" hanya untuk ID di PRD; bagian dokumen lain
  ditulis "bagian N".
- Bila keluaran berbeda dari "Expected" di brief, berhenti dan selidiki sebelum lanjut; laporkan apa adanya. Jangan
  menulis angka yang tidak keluar dari perintah.

#### Laporan

Tulis laporan lengkap ke berkas laporan yang disebut di dispatch: yang dikerjakan, perintah dan keluarannya (RED lalu
GREEN), berkas yang berubah, varian repo nyata yang dipakai, temuan tinjauan diri, dan kekhawatiran. Balasan akhir
paling banyak 15 baris: Status (DONE | DONE_WITH_CONCERNS | BLOCKED | NEEDS_CONTEXT), "Commits: tidak ada (keputusan
pemilik)", ringkasan test satu baris, kekhawatiran, dan jalur berkas laporan.

## Lampiran B: Templat permintaan izin akhir

Dipakai koordinator di Tugas 16 Step 6. Salin bagian di bawah garis ke
`.superpowers/sdd/2026-10-09-memount-v3-w9-penutup/permintaan-izin-akhir.md`, isi setiap tanda `[isi: ...]` dari
sumber yang disebut, lalu ajukan sekali kepada pemilik. Rekomendasi yang sudah tertulis di bawah dikutip dari rencana,
spec, atau pelacak yang disebut di kolom Sumber; templat ini tidak menambah keputusan baru. Butir yang bertambah di
pelacak sesudah 8 Oktober dimasukkan tanpa diubah. Sebelum diajukan, setiap butir pelacak, setiap syarat deploy, dan
setiap keputusan terbuka diperiksa ada (Tugas 16 Step 6).

---

### Permintaan izin akhir Memount v3 (W1 sampai W9)

Disusun: [isi: tanggal], koordinator, sesudah Tugas 16 rencana W9. Arahan pemilik 26 September 2026: "ya, selesaikan
semuanya baru minta izin commit dan integrasi nya jika belum selesai semua jangan minta ke saya, jika anda meminta
perizinan artinya semua tugas anda telah selesai. lanjutkan!" Permintaan ini menandai W1 sampai W9 selesai di pohon
kerja; belum ada commit, push, migrasi Supabase, atau deploy.

#### 1. Keadaan kandidat rilis

| Gerbang | Hasil | Bukti |
|---|---|---|
| Suite Unit dan Feature | [isi: angka keluaran] | `docs/audit/kandidat-rilis-w9.md` |
| Suite bawaan dan Spatial | [isi: angka; Spatial dilewati tanpa DSN] | sama |
| Pratinjau, `tsc`, build, ukuran CSS, Pint, em dash | [isi] | sama |
| Klik-lewat | [isi: jumlah LULUS, MENUNGGU SERVER, GAGAL, TIDAK BERLAKU] | `docs/audit/klik-lewat/2026-10-09-daftar-periksa-w9.md` |
| Delivery Gate S1 sampai S8 | [isi: Status per baris] | `docs/audit/anti-slop/audit-002-2026-10-09.md` |
| Aksesibilitas dan keamanan | [isi: butir PERLU KLARIFIKASI] | `docs/audit/aksesibilitas-w9.md`, `docs/audit/keamanan-w9.md` |

#### 2. Butir izin di pelacak program

Sumber: `.superpowers/sdd/2026-09-26-memount-v3-program.md` bagian "Permintaan izin akhir" dan baris "Tambahan".

| No | Butir | Rekomendasi dan sumbernya | Akibat bila salah | Jawaban |
|---|---|---|---|---|
| 1 | Commit seluruh pekerjaan W1 sampai W9 | Bagian 6 di bawah | Bagian 6 | |
| 2 | Migrasi aditif di Supabase per berkas dengan `--path` | Sesudah CI Spatial hijau dan `migrate:status` (Q2, Q22); urutan bagian 7; kode gelombang mana pun tidak di-deploy sebelum migrasinya (ruling W3-8b) | Halaman 500 karena tabel atau kolom belum ada (kelas insiden R-024) | |
| 3 | Q1: `memount:demo` ke `database/demo.sqlite` | Izinkan; satu-satunya jalan untuk mengklik kendali POST di peramban (audit 002 nomor 1). Penjaganya menolak apa pun selain SQLite di `database/` (W1) | Bila ditolak, kendali POST tetap MENUNGGU SERVER | |
| 4 | Push ke `origin/hardening` | Sesudah Q21 (hak foto prototype), private vulnerability reporting, dan Q20 diputus (pelacak butir 4) | Repo publik: foto tanpa hak dan advisori terbuka ikut terbit | |
| 5 | Keputusan lain yang tetap terbuka dari M1 | [isi dari `HANDOVER.md` bagian 0] | | |
| 6 | Kurasi karakteristik jalur lama (R-006, `/b/` "Berkemah: Tidak") | Pemilik atau kurator data jalur meninjau nilai lama per jalur dengan sumbernya (Konflik W8 nomor 9) | Jalur lama tetap terbaca Sedang dan "tanpa area kemah" sampai dikurasi | |
| 7 | Playwright untuk end-to-end | Disetujui lebih dulu dari ESLint dan Prettier (spec Q17) | Fokus, Urungkan, dan uji luring tetap tanpa bukti otomatis | |
| 8 | Satu pendakian berlangsung per pengguna dan indeks unik `hiking_sessions.trip_plan_id` | Masuk bersama migrasi W6, didahului kueri duplikat (Konflik W6 K6) | Indeks unik gagal dipasang bila ada duplikat | |
| 9 | `encryptHistory` dan `clearHistory()` | Konflik W9 nomor 3 | Konflik W9 nomor 3 | |
| 10 | P8 akses tanpa akun `/b/{token}` | Terima bersama migrasi `comparison_shares` (ruling W3-C1) | Bila ditolak, rute pindah ke grup auth dan tautan tidak terbuka bagi penerima tanpa akun | |
| 11 | Q5: 403 atau 404 untuk trip orang lain | Tetap 403 sampai diputus (spec Q5) | 403 memberi tahu bahwa id trip ada | |
| 12 | `MAP_ATTRIBUTION` di `.env` pemilik dapat menimpa atribusi OpenTopoMap | Pemilik memeriksa `.env` sendiri; agen tidak membacanya | Atribusi peta salah sumber | |
| 13 | Pelanggaran penulis rencana W3 | Dibaca; repo nyata terbukti tidak berubah | | |
| 14 | `OfficialStatusSeeder` berakhir paling lambat 31 Oktober 2026 | Tunjuk kurator pertama sebelum 31 Oktober dan catat status terkini dari sumber resmi lewat meja kurator (Konflik W8 nomor 6) | Jalur terbaca Belum diketahui sesudah tanggal itu (benar menurut R-006, tetapi tanpa status) | |
| 15 | `framer-motion` 14.0.0 di dependencies, belum diimpor | [isi: keputusan pemilik] W1 sampai W9 tidak mengimpornya (`DESIGN.md` MOTION 2) | | |
| 16 | Advisori `npm audit` perkakas dev dan MapLibre (Q20) | Putuskan sebelum push; upgrade MapLibre 6.x masih terbuka (spec Q20) | `security.yml` merah | |
| 17 | ECC, plugin, dan graphify dijalankan pemilik sendiri | Dibaca | | |
| 18 | Pembersihan PII `readiness_checks.condition_snapshot` | Jalankan `memount:bersihkan-snapshot-kondisi` di Supabase sebelum atau bersama deploy kode W4 (syarat deploy W4) | Salinan laporan komunitas lama berisi identitas pelapor tetap tersimpan | |
| 19 | W4-6d: pendaki tanpa profil lengkap tidak dinilai label kecocokannya di kesiapan | Terima (R-006, PRD §91); alternatifnya cap "Masih perlu persiapan" | | |
| 20 | W4-6b: tinjauan pra-keberangkatan tercatat tidak dapat diperbarui | Terima untuk rilis | Pendaki yang ingin meninjau ulang tidak dapat | |
| 21 | Kedekatan kata DK-48 "Masih perlu persiapan" dengan "Perlu persiapan" | Dicatat GR-05; pemilik meninjau | | |
| 22 | Trip lama lewat "Lihat semua" di indeks trip | Terima | | |
| 23 | Pelanggaran pagar penulis W5 | Dibaca; baca saja | | |
| 24 | Rencanakan tanpa `?goal=` menautkan tujuan terakhir | Keputusan pemilik (perilaku pra-W4); penilaian memakai jenis dan tanggal trip | | |
| 25 | Harness uji perilaku klien (Vitest dan Testing Library) | Bersama Q17 | C-12, E-6, dan perilaku klien lain tetap dijaga asersi teks sumber | |
| 26, 27, 29 | Catatan pagar penulis rencana W6, W7, dan W8 | Dibaca | | |
| 28 | Artisan tanpa awalan DB oleh implementer W5 Tugas 4 | Periksa log koneksi Supabase pada jendela 8 Oktober pagi sebagai kehati-hatian | | |
| Baru | Catatan pagar penulis rencana W9 | Dibaca (`scratchpad/rencana/catatan-simulasi-w9.md` bagian Pagar) | | |
| Baru | [isi: butir yang bertambah di pelacak sesudah 8 Oktober] | | | |

#### 3. Syarat deploy per gelombang

| Gelombang | Syarat | Sumber |
|---|---|---|
| W3 | Migrasi `2026_09_28_000100_create_comparison_shares_table` sebelum atau bersama kode W3 | ruling W3-8b dan W3-F4 |
| W4 | `migrate:status` mencakup `2026_09_25_000917` dan `2026_09_25_000918`; pembersihan PII snapshot kondisi; CI Spatial lulus untuk `PreDepartureConfirmLockTest` dan `ReadinessForecastDayTest` | pelacak (syarat deploy W4, W4-FI) |
| W5 | Trip lama berpengingat baru mendapat jadwal hari buka izin saat disinkronkan ulang; pengisian mundur menulis ke produksi dan butuh izin | Konflik W5 nomor 7 |
| W6 | Lima migrasi aditif dengan kueri duplikat sebelum indeks unik; pengosongan `last_known_location` lama butuh izin; uji luring manual butuh Q1 | Konflik W6 K4, K6, K7 |
| W7 | Enam migrasi `2026_10_08_000100` sampai `000600` sesudah W6, dengan hitungan pemeriksaan; disk produksi `AVATARS_DISK`; penangan unggahan foto profil (kategori data pribadi baru) | Konflik W7 K1, K11 |
| W8 | Sembilan migrasi `2026_10_09_000100` sampai `000810` sesudah W7, dengan kueri pemeriksaan; kurator pertama; kurasi status sebelum 31 Oktober; `EVIDENCE_DISK` dan `MOUNTAIN_PHOTOS_DISK` produksi; dua penangan unggahan baru; `docs/KEBIJAKAN-DATA-PRIBADI.md` untuk kategori data baru | Konflik W8 nomor 1, 2, 6, 7, 8 |
| W9 | `TRUSTED_PROXIES` sesuai platform; keputusan `INERTIA_ENCRYPT_HISTORY` dan `SHARE_LINK_PRUNE_AFTER_DAYS`; CI Spatial lulus untuk test kunci W9; tidak ada migrasi | Konflik W9 nomor 1, 3, 4; `docs/audit/kandidat-rilis-w9.md` syarat rilis 1 sampai 7 |

#### 4. Keputusan pemilik yang terbuka

Spec v3 bagian 15. Rekomendasi diambil dari kolom Usulan spec, kecuali yang menyebut sumber lain.

| Q | Hal | Keadaan [isi: perbarui dari ledger] | Rekomendasi | Akibat bila salah |
|---|---|---|---|---|
| Q1 | `memount:demo` | Belum dijalankan | Izinkan (butir 3) | Butir 3 |
| Q2 | Migrasi baru di Supabase | Belum | Sesudah CI PostgreSQL hijau, per migrasi | Butir 2 |
| Q3 | Dependensi baru untuk riset luring atau huruf | Tidak ada yang diajukan W1 sampai W9 | Diajukan terpisah dengan ukuran dan lisensi | |
| Q4 | Commit dan push | Bagian 6 | Sesudah tinjauan diff | |
| Q5 | 403 atau 404, jawaban lupa sandi, verifikasi surel | Tetap seperti sekarang | Tetap sampai diputus | Butir 11 |
| Q6 | Notifikasi dorong | Belum | Surel dan daftar pengingat saja | |
| Q7 | Amandemen PRD untuk DK-36 (GR-07) | GR-07 menunggu | Amandemen dengan batas "paket lapangan, bukan navigasi luring penuh" | Paket lapangan W6 tanpa dasar PRD |
| Q8 | Sumber ubin peta luring | Tahap 1 tanpa peta dasar berlabel | Izin tertulis OpenTopoMap atau penyedia lain | |
| Q9 | Ambang turun data dinamis dan jadwal tinjau | Dibangun W6 dan W8 menurut usulan | Terima; naikkan Buka ke 72 jam bila beban kurator berat (Konflik W8 nomor 4) | Status Buka terbaca "Perlu ditinjau" sesudah sehari |
| Q10 | Kebijakan data di perangkat | Sakelar paket lapangan dan riwayat mati | Pemilik menulis `docs/KEBIJAKAN-DATA-PRIBADI.md` | Konflik W9 nomor 3 dan 4 |
| Q11 | Peran kurator dan aturan terbit | Dibangun W8 | Terima; tunjuk kurator pertama (Konflik W8 nomor 2) | Tidak ada yang dapat mencatat status baru |
| Q12 | Kebijakan kawasan terhadap rekomendasi | Hanya peringatan meja | Biarkan sampai diputus (Konflik W8 nomor 3) | Rekomendasi tidak membaca penutupan kawasan |
| Q13 | Tindakan keluar ke PVMBG dan Balai | Dilakukan pemilik | | |
| Q14 | Telaah hukum paket dan fitur keselamatan | Belum | Telaah ahli hukum pilihan pemilik sebelum paket didistribusikan publik | |
| Q15 | Uji glyph dan keterbacaan dengan 5 sampai 8 pendaki | Materi siap (`docs/riset/2026-10-uji-kegunaan/materi-sesi-w9.md`) | Pemilik menjalankan; hasil tidak dikarang | |
| Q16 | Huruf kustom dan wordmark final | Ditunda | Sesudah MVP; gerbang DK-16 | Audit 002 nomor 2 tetap terbuka |
| Q17 | Playwright, ESLint, Prettier | Belum | Playwright lebih dulu | Butir 7 dan 25 |
| Q18 | `LICENSE` dan `CODEOWNERS` | Belum | Keputusan pemilik | |
| Q19 | Folder skill yang dilacak git | `.agents/skills/` belum dilacak | Usulan spec Q19 | |
| Q20 | MapLibre 4.7.1 dan upgrade 6.x | Mitigasi W0 terpasang | Putuskan upgrade sebelum push | Butir 16 |
| Q21 | Repo publik: ref Supabase, Workspace ID, foto prototype | Belum | Samarkan ref di commit berikutnya; pastikan hak foto atau biarkan lokal | Butir 4 |
| Q22 | Izin baca Supabase untuk `migrate:status` dan advisor | Belum | Izinkan baca saja, lalu migrasi RLS lapis kedua | Syarat rilis 2 dan 4 |

Keputusan K gelombang W5 sampai W8 yang oleh koordinator diputus "menunggu pemilik" dan ruling berbiaya: [isi dari
ledger W5 sampai W8, satu baris per butir dengan rekomendasi dan akibat bila salah yang tertulis di konflik rencana
gelombangnya]. Ruling berbiaya W9: W9-R7 (batas laju baru), W9-R13 dan W9-R30 (batas kandidat), W9-R25 dan W9-R26
(yang ditunda), W9-R28 (Livewire tetap terpasang).

#### 5. Konflik W9

Ringkasan Konflik 1 sampai 11 rencana W9 (`docs/superpowers/plans/2026-10-09-memount-v3-w9-penutup.md`, bagian
Ruling rencana), masing-masing dengan rekomendasi dan akibat bila salah yang tertulis di sana, ditambah putusan
koordinator saat pra-cek: [isi].

#### 6. Urutan commit per gelombang

Pilihan A (rekomendasi): satu commit untuk seluruh pohon kerja W1 sampai W9 yang sudah melewati gerbang lokal Tugas 16,
dengan pesan yang merinci gelombang dan ledgernya. Alasan: banyak berkas disentuh beberapa gelombang (misalnya
`routes/web.php`, `config/hiking.php`, `HANDOVER.md`), staging per hunk interaktif tidak didukung lingkungan ini, dan
pohon antara tidak pernah diuji utuh. Akibat bila salah: riwayat kurang rinci; ledger tetap menyimpan rinciannya.

Pilihan B: seri commit W1, W2, W3, W4, W5, W6, W7, W8, W9 dari daftar berkas ledger, berkas bersama masuk commit
gelombang terakhir yang menyentuhnya. Akibat bila salah: commit antara tidak terbukti lulus suite.

Sebelum push (R-018): suite dan Pint di pohon commit, tinjauan diff, pemindaian rahasia (`SecretsNotInRepoTest` dan
`git diff --cached` dicari pola kredensial), verifikasi migrasi (bagian 7), dan butir 4.

#### 7. Migrasi berurutan

Urutan pasang di Supabase sesudah `2026_09_21_000100`, satu per berkas dengan `--path`, masing-masing diperiksa
`migrate:status` sebelum dan sesudah. Sama dengan tabel `docs/audit/kandidat-rilis-w9.md`; migrasi tambahan dari
perbaikan W5 sampai W8 disisipkan menurut urutan nama berkas.

| # | Berkas | Gelombang |
|---|---|---|
| 1 | `2026_09_25_000917_add_plan_details_to_trip_plans_table` | M1, dipakai W4 |
| 2 | `2026_09_25_000918_create_trip_reminders_table` | M1, dipakai W4 dan W5 |
| 3 | `2026_09_25_000919_add_source_and_recorded_values_to_hiking_history_table` | Rintisan FR-25, bersama W7 |
| 4 | `2026_09_25_000920_create_personal_targets_table` | Rintisan FR-25, bersama W7 |
| 5 | `2026_09_25_000921_create_user_owned_items_table` | Rintisan FR-25, bersama W7 |
| 6 | `2026_09_25_002314_add_note_to_trail_considerations_table` | M1, dipakai W3 |
| 7 | `2026_09_28_000100_create_comparison_shares_table` | W3 |
| 8 | `2026_10_07_000100_add_unique_trip_plan_index_to_hiking_sessions_table` | W6 |
| 9 | `2026_10_07_000200_add_pause_to_hiking_sessions_table` | W6 |
| 10 | `2026_10_07_000300_add_cancellation_reason_to_trip_plans_table` | W6 |
| 11 | `2026_10_07_000400_add_recorded_values_to_hiking_history_table` | W6 |
| 12 | `2026_10_07_000500_add_return_and_registration_to_trip_plans_table` | W6 |
| 13 | `2026_10_08_000100_add_source_to_hiking_history_table` | W7 |
| 14 | `2026_10_08_000200_complete_personal_targets_table` | W7 |
| 15 | `2026_10_08_000300_complete_user_owned_items_table` | W7 |
| 16 | `2026_10_08_000400_add_owned_item_to_trip_preparation_items_table` | W7 |
| 17 | `2026_10_08_000500_add_note_to_trip_preparation_items_table` | W7 |
| 18 | `2026_10_08_000600_add_interest_regions_to_user_preferences_table` | W7 |
| 19 | `2026_10_09_000100_add_provenance_to_official_statuses_table` | W8 |
| 20 | `2026_10_09_000200_create_authority_members_table` | W8 |
| 21 | `2026_10_09_000300_create_data_error_reports_table` | W8 |
| 22 | `2026_10_09_000400_create_volcano_statuses_table` | W8 |
| 23 | `2026_10_09_000500_create_mountain_photos_table` | W8 |
| 24 | `2026_10_09_000600_make_trail_characteristics_nullable` | W8 |
| 25 | `2026_10_09_000700_clear_copied_elevation_loss_on_trails` | W8 (migrasi data) |
| 26 | `2026_10_09_000800_add_kind_to_trip_preparation_items_table` | W8 |
| 27 | `2026_10_09_000810_mark_permit_preparation_items` | W8 (migrasi data) |

#### 8. Jawaban pemilik

[isi: jawaban per nomor butir, Q, dan Konflik; yang tidak dijawab tetap ditunda dan tidak dikerjakan]
