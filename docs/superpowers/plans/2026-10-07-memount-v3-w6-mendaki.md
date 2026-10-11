# Memount v3 W6 Mendaki dan Paket Luring Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Mode lapangan yang dapat dipakai di jalur dengan rupa prototype 11 (pos berikutnya, sisa jarak, naik lagi,
perkiraan, tiba di pos, jeda, selesai) yang dihitung di perangkat tanpa mengirim posisi ke server; pintu tab Mendaki
dengan pratinjau hari-H dan "Mulai pendakian" 66 px; ringkasan pendakian rupa prototype 12 (varian selesai dan
dibatalkan, angka yang dapat diubah, banding diri sendiri, laporan kondisi dengan "Ubah sebelum dimoderasi"); satu
pendakian berlangsung per pengguna; dan paket lapangan luring yang terverifikasi, kartu darurat, versi cetak, serta
pesan rencana untuk kontak yang dibangun dan diuji tetapi tidak dibuka untuk pengguna sampai pemilik memutus Q10 dan
Q14.

**Architecture:** Server mengirim data jalur yang stabil dan keadaan sesi (`App\Services\Trips\HalamanMendaki`);
perangkat menghitung posisi, sisa jarak, naik lagi dari profil elevasi, dan tiba di pos di modul murni
`resources/js/components/mendaki/hitung.ts`, dan mengirim hanya urutan pos dan waktu jeda lewat antrean yang aman
diputar ulang. Siklus trip menjaga satu pendakian berlangsung per pengguna dengan kunci baris pengguna dan indeks unik.
Ringkasan (`App\Services\Community\HasilPendakian`) menulis angka dengan sumbernya dan membandingkan hanya dengan
riwayat pendaki itu. Paket lapangan (`App\Services\Trips\PaketLapangan`) adalah tiga bagian JSON yang di-hash, diunduh
dan diverifikasi oleh halaman ke IndexedDB per pengguna, lalu dibuka shell luring statis (`/offline`, entri Vite
`resources/js/luring/shell.tsx`) dengan halaman `Trips/Hike` yang sama. Service worker hanya menyimpan shell dan aset
berversi, dan versi barunya tidak pernah menghapus cache paket. Rute paket dan alat keselamatan dijaga middleware
`fitur` dan menjawab 404 selama sakelarnya mati.

**Tech Stack:** PHP 8.5.10, Laravel 13.31.0, Inertia Laravel 3.3.4, `@inertiajs/react` 3.7.1, React 19.3.0,
TypeScript 5.9, Vite 8, Tailwind CSS 3.4, MapLibre GL 4.7.1, PHPUnit 12.5, Pint, SQLite dalam memori untuk test, Node
22.6 atau lebih baru untuk test modul murni (sudah dibutuhkan build). Tidak ada dependensi baru: tanpa Workbox, tanpa
idb, tanpa framer-motion, tanpa Playwright (Q17).

**Spec:** `docs/superpowers/specs/2026-09-25-memount-v3-prototype-design.md` (bagian 6.12, 6.13, 10 seluruhnya, 4 untuk
DK-36, DK-49, DK-50, bagian 7, 8, 9, 12, 13, dan 15 untuk Q7, Q9, Q10, Q14, Q17), rencana induk
`docs/superpowers/plans/2026-09-26-memount-v3-induk.md` (bagian "W6 Mendaki dan paket luring" butir 1 sampai 9 dan
"Selesai bila"), pelacak program `.superpowers/sdd/2026-09-26-memount-v3-program.md` (titipan W6: izin akhir 8 dan 9,
W4-C4, Konflik W5 nomor 8), acuan `docs/desain/prototype/acuan/11-mendaki.png` dan `12-ringkasan.png`, dan aturan
eksekusi di Lampiran A. Keadaan awalnya keadaan akhir simulasi rencana W5 (`2026-10-07-memount-v3-w5-beranda.md`),
yang sendiri berpijak pada tree `b7e54014`; W5 dan gelombang perbaikan akhir W4 selesai di repo nyata sebelum Tugas 1
rencana ini dimulai (Pra-cek).

## Global Constraints

- Versi dikunci: PHP 8.5, Laravel 13.31, Inertia Laravel 3.3.4, React 19.3, `@inertiajs/react` 3.7.1, Tailwind 3.4, Vite 8, TypeScript 5.9, MapLibre 4.7.x. Tidak ada perubahan versi kerangka (R-020).
- Tidak ada dependensi baru, runtime maupun dev: tanpa Workbox, `idb`, framer-motion, `@playwright/test` (Q17), atau `npx shadcn init`. Satu ikon ditambah ke registri Phosphor (`clock`, path dari `@phosphor-icons/core` 2.1.1, ruling W6-R16), tanpa paket.
- Tidak ada commit, push, akses Supabase, atau migrasi di basis data mana pun. Setiap langkah "Commit" berbunyi: "Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah."
- Lima migrasi aditif baru (`2026_10_07_000100` sampai `000500`) hanya berjalan di dalam PHPUnit (SQLite dalam memori) dan suite Spatial di PostGIS CI. `php artisan migrate` dalam bentuk apa pun, `db:seed`, `db:wipe`, tinker (termasuk `tinker --execute`), dan `memount:demo` tidak dipakai.
- Setiap perintah Artisan di luar PHPUnit, `npm run build`, dan `php -S` diberi awalan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL=` (Git Bash). Di PowerShell: `$env:DB_CONNECTION="sqlite"; $env:DB_DATABASE=":memory:"; $env:DB_URL="(empty)"; <perintah>`, lalu `Remove-Item Env:DB_CONNECTION, Env:DB_DATABASE, Env:DB_URL`. `php artisan test ...` tidak butuh awalan karena `phpunit.xml` memaksa koneksi uji. Jangan membaca atau mencetak `.env`.
- Port `127.0.0.1:8100` dicadangkan untuk harness pratinjau koordinator (Tugas 13). Tugas 1 sampai 12 tidak menyalakan server apa pun. Server `php artisan serve` pemilik di `127.0.0.1:8000` tidak pernah dipakai atau dihentikan. Port 8150 milik penulis rencana.
- Bahasa Indonesia untuk teks antarmuka dan dokumen, sapaan "Anda", tanpa em dash (U+2014). Tidak ada kata "aman", "sehat", "dijamin", "terjamin", atau "pasti" sebagai klaim di mode lapangan, kartu darurat, pesan rencana, versi cetak, paket, maupun ringkasan (R-012, R-014). "§N" hanya untuk ID PRD; dokumen lain dirujuk "bagian N".
- Posisi presisi pendaki tidak pernah dikirim ke server di luar rekam jejak yang opsional (mati bawaan): mode lapangan menghitung di perangkat, dan rute `trips.hike.position` dihapus (ruling W6-R5). Lokasi hanya diminta di halaman mode lapangan (`LocationPrivacyTest`).
- Tidak ada skor, persen, atau bobot angka kecocokan maupun kesiapan di props, paket, atau layar (R-004, R-005); ringkasan tidak lagi menulis "Persiapan 80%". Pembanding progres hanya riwayat pendaki itu (R-015), dan riwayat tidak mengubah tingkat pengalaman (BR-11).
- Data dinamis di paket selalu "terakhir diketahui" dengan waktu Memount terakhir mencocokkannya dengan sumber (R-013): status Buka turun menjadi "belum dapat dipastikan" sesudah ambang Q9, cuaca yang belum terbit disebut begitu, dan tidak ada cuaca karangan. Laporan komunitas dan status resmi tetap terpisah (R-010). Paket hanya untuk jalur terbit (R-008, R-022) dan tidak memuat skor, keluaran AI, GPX komunitas, nama pelapor, atau rencana orang lain.
- Paket lapangan dan alat keselamatan dibangun di balik `hiking.field_pack.enabled` dan `hiking.field_safety.enabled`, keduanya `false` bawaan (ruling W6-R3, W6-R4). Selama mati, rutenya menjawab 404 lewat middleware `fitur` (sebelum otorisasi dan validasi), tab Mendaki menulis kalimat jujur tanpa tombol unduh, dan mode lapangan serta ruang kerja tidak menampilkan kartu darurat, versi cetak, atau pesan rencana. Test mencakup kedua keadaan sakelar.
- Setiap rute baru memakai Policy pemilik trip (`view` atau `update`) dan rute tulis dibatasi `throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,<kunci>'` dengan kunci sendiri. Validasi di Form Request; alasan pembatalan dari daftar putih enum DK-50.
- Token warna hanya di `resources/css/app.css`; tanpa warna mentah, `bg-white`, `text-black`, atau `dark:` di komponen. Kendali utama di jalur 66 px untuk "Mulai pendakian" dan tombol telepon kartu darurat, 60 px untuk Jeda, Lanjut, Selesai, dan Kartu darurat, kontrol peta 48 px, kendali lain paling sedikit 44 px. Halaman tidak menggulir menyamping di 320 px. Status tidak pernah lewat warna saja. Gerak hanya `denyut-tiba` dan ikut dipangkas `prefers-reduced-motion` serta setelan Gerak.
- Nama props bersama (`jumlahSimpanan`, `pendakianAktif`, `daftarPengingat`, `demo`, `perangkat`, dan lainnya di `HandleInertiaRequests`) tidak dipakai ulang sebagai kunci prop halaman atau isi paket (`ReminderSheetTest`).
- Test yang menyandikan keputusan lama diperbarui menurut niat baru dengan alasan tertulis di docblock atau komentarnya; tidak dihapus dan tidak dilonggarkan diam-diam (spec bagian 14).
- Tugas dikerjakan berurutan, satu implementer satu waktu, dan hanya menyunting berkas di daftar Files tugasnya. Berkas bersama (`routes/web.php`, `config/hiking.php`, `bootstrap/app.php`, `app/Http/Middleware/HandleInertiaRequests.php`, `resources/js/layouts/AppLayout.tsx`, `resources/js/types/index.d.ts`, `resources/js/pages/Trips/Show.tsx`, `resources/js/pages/Trips/Hike.tsx`, `tests/Pratinjau/HalamanPratinjauTest.php`) hanya disunting tugas yang menyebutnya. `HANDOVER.md` dan `CLAUDE.md` hanya disunting koordinator di Tugas 13.
- Berkas baru dibuat seperti W2 sampai W5: test dengan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:test --phpunit --no-interaction <Nama>`, migrasi dengan `make:migration`, kelas PHP dengan `make:class`, Form Request dengan `make:request`, controller dengan `make:controller`, enum dengan `make:enum`, middleware dengan `make:middleware` (semuanya `--no-interaction` dan awalan DB), lalu isinya diganti persis dengan kode rencana. Nama berkas migrasi memakai nama di rencana (ganti nama berkas hasil `make:migration`). Berkas TypeScript dibuat langsung. Setelah menyunting PHP jalankan `vendor/bin/pint --format agent <berkas>`; setelah menyunting TypeScript jalankan `npm run build` (membangkitkan ulang keluaran Wayfinder untuk rute baru) lalu `npx tsc --noEmit`.
- Blok "Ganti ... dengan ..." diterapkan berurutan dengan alat Edit. Teks lamanya unik di berkasnya pada keadaan dasar ditambah tugas-tugas sebelumnya, dan sudah diperiksa pada simulasi dan putaran ulang. Bila teks lama tidak ditemukan persis, berhenti dan laporkan NEEDS_CONTEXT; jangan mengarang padanannya. "Tulis ulang ... utuh" berarti isi berkas diganti seluruhnya dengan blok kodenya.

## Review Focus

- **Dua pendakian sekaligus dan aksi yang diputar ulang.** Pendaki memulai trip kedua saat trip pertama masih
  berlangsung (dua tab, dua perangkat), atau antrean memutar ulang "tiba di Pos 3" sesudah "tiba di Pos 4", atau jeda
  yang dikirim dua kali. Dijaga Tugas 1 `SingleActiveHikeTest::test_a_second_hike_cannot_start_while_another_is_in_progress`,
  `test_a_trip_cannot_hold_two_field_sessions` dan `HikingSessionUniqueTest` (PostgreSQL), Tugas 3
  `HikeModeCheckpointTest::test_progress_is_not_lost_when_an_earlier_arrival_is_replayed`,
  `test_a_replayed_pause_uses_the_device_time_and_is_not_restarted`, dan `test_pausing_and_resuming_count_only_moving_time`.
- **Angka lapangan yang terlalu pasti atau keliru.** Jarak di bawah 50 m ditulis presisi, jarak garis lurus disebut
  "menyusuri jalur", naik lagi dari ketinggian GPS, atau tiba di pos otomatis padahal akurasi lemah. Dijaga Tugas 2
  `FieldModeFiguresTest` (pembulatan, garis lurus, profil, keputusan tiba) dan Tugas 7
  `FieldModeFiguresTest::test_the_emergency_location_block_reads_as_it_is_spoken`.
- **Data luring yang terbaca terkini.** Status Buka di paket yang ditampilkan tanpa umur sesudah ambang Q9, Tutup yang
  ikut turun, cuaca karangan, atau laporan "kemarin" yang basi esok hari. Dijaga Tugas 8
  `FieldPackStalenessTest::test_the_device_downgrades_an_open_status_after_its_threshold`,
  `test_closed_and_unknown_statuses_never_expire_into_something_else`, `test_weather_that_is_not_yet_published_is_named_as_such`,
  dan `test_community_reports_are_dated_and_bounded`.
- **Paket dan data perangkat yang bocor atau hilang.** Paket yang dihapus deploy, paket yang dibuka akun lain di
  peramban yang sama, paket berisi rencana orang lain, atau rute paket yang terbuka saat sakelar mati. Dijaga Tugas 8
  `FieldPackManifestTest` (pemilik saja, sakelar, jalur terbit, tanpa skor dan orang lain), Tugas 9
  `ProgressiveWebAppTest::test_activate_never_deletes_pack_caches` dan
  `test_the_offline_shell_never_serves_server_content`, Tugas 10 `FieldPackDeviceTest`, Tugas 11
  `LogoutClearsDeviceDataTest`.
- **Janji keselamatan dan eskalasi yang keliru.** Kartu darurat atau pesan rencana yang berbunyi "aman", jam eskalasi
  yang tidak ditambah satu jam atau dibaca di zona yang salah, kontak pengelola yang ditebak. Dijaga Tugas 7
  `EmergencyCardTest` (sakelar, 112 dan 115, kontak hanya bila resmi diketahui) dan `TripPlanMessageTest` (jam kembali
  ditambah satu jam, zona gunung, tanpa "aman").

---

## Bukti simulasi rencana

Kode di rencana ini dijalankan tugas demi tugas di salinan di scratchpad sesi perencanaan (`w6sim`). Salinan itu dibuat
dengan menyalin utuh `w5sim` (keadaan akhir simulasi rencana W5, yaitu tree `b7e54014` ditambah Tugas 1 sampai 8 W5),
termasuk `vendor/` dan `node_modules/` (salinan, bukan tautan), dengan `git init` tanpa add dan tanpa commit, dan
konstanta `ASAL` harness pratinjau diarahkan ke port 8150. Repo nyata tidak pernah ditulis. Beberapa perintah baca dan
pengelolaan proses sempat berjalan dengan direktori kerja bawaan sesi, yaitu repo nyata; rinciannya di catatan
simulasi penulis (bagian Pagar). Setiap tugas diuji merah dengan test tugas itu terhadap kode tugas
sebelumnya, lalu hijau, lalu suite Unit dan Feature penuh, build, `npx tsc --noEmit`, Pint, dan suite Pratinjau.
Sesudah dirakit, teks rencana ini diputar ulang dari salinan dasar yang bersih (`w6putar`, dibuat dengan cara yang
sama) memakai pengurai yang hanya membaca blok "Buat", "Tulis ulang", "Ganti", dan "Hapus berkas" di rencana ini; angka
di tabel adalah hasil putaran ulang itu.

| Tugas | Merah (filter tugas, sebelum implementasi) | Hijau | Suite Unit dan Feature | Pratinjau | Pohon sesudah tugas |
|---|---|---|---|---|---|
| Dasar (keadaan akhir W5) | | | 1.970 test, 1.968 lulus, 2 dilewati | 22 lulus | dasar putaran ulang |
| 1 Satu pendakian | 33 test: 30 lulus, 3 gagal | 33 lulus | 1.976 test, 1.974 lulus, 2 dilewati | tidak dijalankan (tanpa layar baru) | identik dengan simulasi |
| 2 Hitungan | 8 test: 0 lulus, 8 gagal | 8 lulus | 1.984 test, 1.982 lulus, 2 dilewati | tidak dijalankan (tanpa layar baru) | identik dengan simulasi |
| 3 Mode lapangan | 56 test: 35 lulus, 10 gagal, 11 error | 56 lulus | 1.992 test, 1.990 lulus, 2 dilewati | 23 lulus | identik dengan simulasi |
| 4 Pintu tab | 8 test: 3 lulus, 5 gagal | 8 lulus | 1.996 test, 1.994 lulus, 2 dilewati | 23 lulus | identik dengan simulasi |
| 5 Alasan batal | 21 test: 13 lulus, 2 gagal, 6 error | 21 lulus | 2.004 test, 2.002 lulus, 2 dilewati | 23 lulus | identik dengan simulasi |
| 6 Ringkasan | 55 test: 33 lulus, 16 gagal, 6 error | 55 lulus | 2.018 test, 2.016 lulus, 2 dilewati | 25 lulus | identik dengan simulasi |
| 7 Keselamatan | 24 test: 9 lulus, 6 gagal, 9 error | 24 lulus | 2.033 test, 2.031 lulus, 2 dilewati | 27 lulus | identik dengan simulasi |
| 8 Manifest paket | 13 test: 1 lulus, 1 gagal, 11 error | 13 lulus | 2.045 test, 2.043 lulus, 2 dilewati | tidak dijalankan (tanpa layar baru) | identik dengan simulasi |
| 9 Service worker dan shell | 13 test: 7 lulus, 4 gagal, 2 error | 43 lulus | 2.051 test, 2.049 lulus, 2 dilewati | 28 lulus | identik dengan simulasi |
| 10 Unduh dan verifikasi | 14 test: 10 lulus, 2 gagal, 2 error | 38 lulus | 2.054 test, 2.052 lulus, 2 dilewati | 29 lulus | identik dengan simulasi |
| 11 Keluar | 9 test: 4 lulus, 3 gagal, 2 error | 23 lulus | 2.058 test, 2.056 lulus, 2 dilewati | 29 lulus | identik dengan simulasi |
| 12 Dokumen | tidak ada test baru (31 test penjaga dokumen lulus sebelum dan sesudah) | 31 lulus | 2.058 test, 2.056 lulus, 2 dilewati | tidak dijalankan (tanpa layar baru) | identik dengan simulasi |

Sesudah setiap tugas `npx tsc --noEmit` keluar tanpa galat, `vendor/bin/pint --test --format agent` lulus, dan
`npm run build` lulus. Pratinjau dijalankan untuk tugas yang menambah atau mengubah layar (3 sampai 7, 9 sampai 11).
Hijau Tugas 9 sampai 11 memakai filter merah ditambah test yang ikut disentuh tugas itu (Step 5 masing-masing).

Angka di atas memakai SQLite dalam memori. Suite bawaan `php artisan test --compact` (Unit, Feature, dan Spatial
menurut `defaultTestSuite`) sesudah Tugas 12: 2.092 test, 2.056 lulus, 36 dilewati (34 test Spatial
melewati dirinya tanpa `SPATIAL_TEST_DSN`, ditambah 2 test yang sudah dilewati di dasar). Test Spatial baru W6
(`HikingSessionUniqueTest`, `FieldModeColumnsSchemaTest`) termasuk yang dilewati; paritas PostgreSQL ada di Tinjauan diri.

**Putaran ulang.** Putaran penuh pada teks final, 7 Oktober 21.00 sampai 21.30 WIB, di salinan dasar `w6putar` (dibuat dengan cara yang sama dengan `w6sim`, `ASAL` port 8150; sesudah dua putaran sebelumnya dikembalikan ke potret dasar dengan 0 berkas beda lalu dibangun ulang) dengan pengurai yang hanya membaca blok rencana. Pengurai membaca 278 operasi (65 "Buat", 13 "Tulis ulang ... utuh", 199 blok "Ganti", 1 "Hapus berkas"), dan setiap teks lama blok "Ganti" ditemukan tepat sekali saat diterapkan. Pohon sumber sesudah setiap tugas (tanpa `vendor`, `node_modules`, keluaran build, keluaran Wayfinder, dan `public/_pratinjau`) sama berkas demi berkas dengan potret simulasi tugas itu: 0 berkas baru, berubah, atau hilang untuk Tugas 1 sampai 12. Akhir putaran: Unit dan Feature 2.058 test (2.056 lulus, 2 dilewati), suite bawaan 2.092 test (2.056 lulus, 36 dilewati), `npx tsc --noEmit` tanpa galat, Pint lulus, build lulus, dan Pratinjau 29 lulus. Terhadap dasar, Tugas 1 sampai 12 menambah 65 berkas, mengubah 58, dan menghapus 1. Dua putaran sebelumnya: putaran pertama (Tugas 1 sampai 12) lulus dan identik pada teks sebelum tiga perbaikan yang ditemukan sesudahnya (wilayah kabar ringkasan, peta yang mengikuti tema lapangan, tabel pos cetak di 320 px; lihat Cacat di bawah), dan putaran kedua (Tugas 3 sampai 5 sesudah perbaikan) dihentikan untuk putaran penuh ini. Sesudah putaran hanya teks di luar blok kode yang disunting (angka, Bukti, Pra-cek, Konflik); sidik SHA-256 daftar operasi rencana final sama dengan yang diputar (`0ef3cefb2941d883...`).

**Uji kering terhadap repo nyata.** Hanya membaca, dengan alat Grep, 7 Oktober 2026 malam, sesudah residu perbaikan akhir W4 (paket `8db4fb3..45b9a27`) dan sebelum W5 dieksekusi di repo nyata. Skrip yang membaca repo nyata tidak dijalankan, karena pagar penulis hanya mengizinkan Read, Grep, dan Glob terhadap repo nyata; alatnya (`alat6/uji_kering6.py`, diuji terhadap tree `b7e54014` di scratchpad: 0 masalah jangkar W5 dan W6) diserahkan ke Pra-cek. Judul berkas ketiga paket diff W4 sesudah tree `b7e54014` (Tugas 7, perbaikan akhir, dan residu; 72 berkas) memuat 13 berkas yang juga disentuh W6, lima di antaranya juga disentuh W5. Ke-68 blok "Ganti" W6 pada ketiga belas berkas itu ditambah `tests/Pratinjau/HalamanPratinjauTest.php` (milik W5) dicocokkan dengan Grep multiline di pohon kerja repo nyata. Dari 53 blok yang jangkarnya sudah ada di tree `b7e54014`, 50 ditemukan tepat sekali dan 3 tertabrak perbaikan akhir W4 (Pra-cek butir 13: blok rute dan blok impor Tugas 1 sudah dipenuhi repo nyata, tanda tangan `TripController::show()` Tugas 7 bergeser). Lima blok berjangkar teks W5 dan sepuluh blok berjangkar teks tugas W6 sebelumnya baru dapat dicocokkan sesudah W5. Keempat puluh lima berkas lama lain yang disentuh W6, termasuk ketiga belas berkas yang ditulis ulang utuh, tidak muncul di ketiga paket diff itu. Dari diff yang sama terbaca dua konvensi baru repo nyata: wilayah kabar yang selalu terpasang (kini diikuti ringkasan W6) dan pemisahan flash `status` lawan `kabar-aksi` (Konflik 13).

**Pengukuran di peramban** (Chrome lewat MCP chrome-devtools, pratinjau statis salinan di port 8150, emulasi seluler
dengan sentuh dan rasio piksel 1 untuk 320 dan 390 px, desktop 1440 px rasio 1; reflow kesepuluh halaman diukur di
iframe selebar 320 dan 390 px di dalam emulasi seluler itu; posisi untuk mode lapangan dari skrip awal yang mengganti
`navigator.geolocation`; kueri dihitung dengan test sementara di salinan yang dihapus sesudahnya):

- Reflow: `document.documentElement.scrollWidth` sama dengan `clientWidth` untuk `mode-lapangan`, `kartu-darurat`
  (lembar terbuka), `ringkasan`, `ringkasan-batal`, `mendaki`, `mendaki-paket`, `cetak`, `luring`, `jelajah-mendaki`,
  dan `ruang-kerja-keselamatan` di 320 x 568 maupun 390 x 844. Sebelum perbaikan, `cetak` di 320 px 378 lawan 320:
  tabel pos lima kolom 362 px mendorong halaman; kini tabel bergulir di wadahnya sendiri.
- Target sentuh (390 px): "Mulai pendakian" 66 px; tombol telepon 112 dan 115 di kartu darurat 70 px (paling kecil
  66); Jeda, Selesai, dan "Kartu darurat" 60 px; perbesar, perkecil, "Posisi saya", tombol tema, dan tombol kembali mode
  lapangan 48 px; "Saya sudah di Telaga Biru" 49 px; "Siapkan paket luring" 44 px.
- Tema lapangan (DK-49): dengan tema sistem terang akar mode lapangan `data-tema-lapangan="terang"` dan tombolnya
  menulis "Gelap"; dengan tema sistem gelap `gelap` dan "Terang". Sesudah dibalik ke gelap di bawah tema aplikasi terang,
  peta dasar ikut diredupkan dan kanvas polos paket luring ikut gelap (sebelum perbaikan peta tetap terang). Kombinasi
  warna kedua tema lapangan dijaga `ColourContrastTest` (7:1 untuk informasi kritis).
- Kontras tombol telepon (putih di atas `danger-600`, pasangan yang sama dengan tombol bahaya dan diuji
  `ColourContrastTest`): 6,53:1 di tema terang dan lapangan terang, 4,92:1 di tema gelap dan lapangan gelap. Angka 112
  dan 115 (28 px tebal) lolos ambang AAA teks besar; label "Darurat" dan "Basarnas" (14 px tebal) lolos AA tetapi di bawah
  ambang lapangan 7:1 (OPEN ISSUE).
- Wilayah kabar ringkasan: `role="status"` dan `role="alert"` di kepala halaman ada sejak dimuat dengan tinggi 0 saat
  kosong; wilayah galat formulir laporan bermargin 0 saat kosong.
- Kueri per permintaan, tetap untuk dua ukuran data (6 pos dan 2 laporan lawan 14 pos dan 10 laporan; 3 lawan 12
  riwayat): mode lapangan 7, manifest paket 14, bagian `lapangan` 7, `rencana` 8, `dinamis` 8, ruang kerja dengan
  sakelar keselamatan menyala 12, versi cetak 9, ringkasan 12. Tab Mendaki dijaga `TrackTabTest` (satu lawan sepuluh
  trip).
- Bundel (build salinan): entri kedua `shell` membuat Vite memisahkan kode bersama, jadi entri `app` kini `app` 10,70 kB
  ditambah potongan bersama `dist` 346,41 kB (gzip 3,18 dan 108,56), jumlah 357,11 kB lawan 355,97 kB di dasar (gzip
  111,74 lawan 111,90). CSS 72,47 kB (gzip 14,22; dasar 69,69 dan 13,70). Potongan `Hike` 29,31 kB (gzip 10,00; dasar
  8,00 dan 3,16), `HikeSummary` 15,87 kB (gzip 5,08; dasar 3,62 dan 1,39), `Print` 7,60 kB (gzip 2,44), entri `shell`
  4,64 kB (gzip 2,00). Tanpa paket baru.
- Simpanan pemasangan service worker: paket mati 4 berkas (halaman luring, dua ikon, CSS; 81 KiB); paket nyala 22
  berkas, 1.315 KiB tanpa halaman luring (termasuk MapLibre 787 kB dan dua berkas huruf).
- Tangkapan (scratchpad sesi perencanaan, `rencana/w6-*.png`, bukan di repo): mode lapangan 390 dan 1440 terang dan
  gelap ditambah tema lapangan gelap lewat tombol; strip pendakian 390 dan 1440 terang dan gelap; kartu darurat 390 dan
  1440 terang dan gelap; ringkasan 390 dan 1440 terang dan gelap; ringkasan batal 390 terang dan gelap; `mendaki`,
  `mendaki-paket`, `cetak`, `ruang-kerja-keselamatan`, dan luring (daftar paket, mode lapangan dari paket terang dan
  gelap) 390. Tangkapan resmi diambil ulang di Tugas 13.

**Cacat yang ditemukan simulasi dan sudah diperbaiki di kode rencana ini** (dicatat supaya peninjau tahu mengapa
bentuknya begini):

- Kontrol bawaan MapLibre di peta lapangan bertumpuk dengan tombol 48 px; peta lapangan kini memakai
  `usePetaDasar({ kendaliBawaan: false })` dan tombolnya sendiri.
- Tanpa basemap (luring, atau tanpa `MAP_TILE_URL`) peta semula gagal; gaya latar polos dengan label "Peta dasar tidak
  tersedia luring" kini dipakai, dan warna latarnya dibaca dari akar tema lapangan, bukan `<html>`.
- Kepala mode lapangan sesak di 390 px; lencana Data contoh pindah ke bawah judul dan tombol tema menulis tema
  tujuannya.
- Tangga ringkasan semula meletakkan batang dengan nama jalur tiga baris lebih tinggi dari batang lain; nilai dan batang
  kini di wadah bertinggi tetap sehingga semua batang berdiri di garis dasar yang sama.
- Kalimat GPS foto tertulis dua kali (isian foto dan kotak privasi); kini hanya di isian foto, tempat keputusannya
  diambil.
- Ringkasan semula menulis jarak data jalur sekali jalan; prototype 12 menulis jarak pulang-pergi (9,7 km menjadi
  19,4 km). Untuk pendakian yang berbalik tanpa jejak, angka jalur penuh melebih-lebihkan, jadi kosong (W6-R13).
- Tangga ringkasan semula memakai angka ubahan pendaki, sehingga Beranda dan ringkasan menggambar dua tangga berbeda
  untuk riwayat yang sama; kini keduanya memakai tanjakan jalur (W6-R13).
- Versi cetak semula memakai `print:text-black` (ditolak `InertiaLayoutTest`, warna mentah); kertas kini dipaksa tema
  terang lewat `beforeprint` dan `afterprint`.
- Kunci `demo` di bagian paket ditolak `ReminderSheetTest` (nama prop bersama); kini `dataContoh`.
- Atribut `hidden` pada teks pengakuan luring kalah oleh kelas `flex`; shell kini membuang elemennya sebelum mode
  lapangan dari paket dipasang.
- Kalimat isi paket semula menyebut kartu darurat walau sakelar keselamatan mati; kini isi yang disebut hanya yang
  selalu dibawa paket.
- Rute paket semula tidak terdaftar di `bukanHalamanReact.json` (`InertiaLinkRegistryTest`).
- Sesudah tema lapangan dibalik ke gelap di bawah tema aplikasi terang, peta dasar tetap terang dan kanvas polos paket
  luring tetap berwarna terang: peredupan dan warna lapisan hanya dibaca saat peta dimuat. Kini `ikutkanTemaPetaDasar`
  dipakai saat dimuat dan saat atribut `data-tema-lapangan` berubah (`MapConfigTest`).
- Tabel pos versi cetak (lima kolom, 362 px) mendorong halaman 320 px melebar; kini tabel bergulir di wadahnya sendiri.
- Ringkasan semula memasang `Alert` kabar, peringatan foto, dan galat formulir laporan bersama isinya, sehingga tidak
  andal dibacakan pembaca layar; kini ketiganya wilayah yang terpasang sejak halaman atau formulir tampil, sesuai
  konvensi "Umpan balik aksi di wilayah yang selalu terpasang" yang ditemukan di `DESIGN.md` repo nyata saat uji kering
  (asersi sumber di `HikeSummaryReportTest`, pola `ReadinessDashboardTest`).

**Temuan di luar cakupan W6 (OPEN ISSUE):**

- Data contoh tanpa geometri jalur dan tanpa koordinat pos; pratinjau memberi koordinat pos di dalam test saja, jadi
  garis yang dilalui dan sisa jarak menyusuri jalur belum pernah terlihat di peramban (kurasi data W8).
- Penguncian baris pengguna di `TripLifecycle::start()` belum diuji dengan dua koneksi PostgreSQL bersamaan; test
  SQLite tidak menangkap penghapusannya (mutasi diperiksa), dan indeks unik hanya menjaga satu sesi per trip.
- Mode lapangan dari paket tidak dapat memulai pendakian tanpa sinyal: memulai butuh server. Paket menampilkan
  "belum dimulai" sampai mode lapangan daring sempat menyimpan keadaan sesi ke perangkat.
- Daftar persiapan di bagian `rencana` paket hanya dibaca; centang saat luring yang masuk antrean (spec bagian 10.2)
  belum dibangun.
- Status gunung api (DK-43) belum ada modelnya, jadi paket belum membawanya (W8).
- Ikon orang, gunung, tenda, dan kamera prototype 12 tidak ditambahkan.
- Halaman yang sudah terbuka sebelum keluar akun tetap tersimpan di riwayat peramban (state Inertia); menutupnya butuh
  `encryptHistory` dan `clearHistory` (izin akhir 9, Konflik 5).
- Label "Darurat" dan "Basarnas" di tombol telepon (putih di atas `danger-600`, 14 px tebal) 4,92:1 di tema gelap dan
  lapangan gelap: lolos AA, di bawah ambang lapangan 7:1. Menaikkannya butuh token bahaya baru untuk tema gelap, jadi
  keputusan desain (`DESIGN.md`), bukan W6.

## Keadaan awal yang terukur (FAKTA, 7 Oktober 2026)

| Butir | Keadaan | Bukti |
|---|---|---|
| Suite dasar | Unit dan Feature 1.970 test (1.968 lulus, 2 dilewati), sama dengan angka akhir rencana W5 | `php artisan test --compact --testsuite=Unit,Feature` di `w6sim` dan `w6putar` |
| Pratinjau dasar | 22 halaman | `php artisan test --compact --testsuite=Pratinjau` di salinan dasar |
| Mode lapangan | Posisi dikirim atas tombol ke `POST trips.hike.position`; server mencari pos terdekat (`HikeCheckpointResolver`, haversine) dan menyimpan `hiking_sessions.last_known_location`; tanpa jeda, perkiraan bersumber, tiba dengan konfirmasi, atau tema lapangan | `app/Http/Controllers/Trips/HikeModeController.php` (`position()`), `resources/js/components/trips/usePendakian.ts` (`usePosisiPendakian`) |
| Siklus trip | `TripLifecycle::start()` membuat sesi dengan `firstOrCreate` tanpa memeriksa pendakian lain milik pengguna; `hiking_sessions` tanpa indeks unik per trip; mulai, selesai, dan batal tanpa batas laju | `app/Services/Trips/TripLifecycle.php`, `routes/web.php` |
| Tab Mendaki | Daftar trip yang dapat dimulai dengan kartu "Mulai mendaki"; tanpa pratinjau hari-H, keadaan paket, atau penjelasan lokasi | `app/Http/Controllers/Trips/TrackController.php`, `resources/js/pages/Track/Index.tsx` |
| Ringkasan | Angka "Lama pendakian", "Pos terjauh", "Elevation gain jalur", dan "Persiapan terkonfirmasi" dengan persen (`80%`); tanpa varian dibatalkan, banding diri sendiri, atau formulir laporan | `app/Services/Community/HasilPendakian.php`, `resources/js/pages/Community/HikeSummary.tsx` |
| Alasan pembatalan | Tidak ada kolom atau pilihan; batal lewat `window.confirm` | `resources/js/pages/Trips/Show.tsx` |
| Service worker | `activate` menghapus setiap cache selain shell versi berjalan; semua permintaan gaya, skrip, huruf, dan gambar disimpan, termasuk foto laporan | `resources/views/sw.blade.php` baris 27-33 dan 52-63 |
| Halaman luring | Teks statis tanpa skrip | `resources/views/offline.blade.php` |
| Perekam jejak | Basis data `jejak-pendakian` dipakai siapa pun yang masuk di peramban itu | `resources/js/jejak.js` |
| Ikon | Registri Phosphor tanpa jam | `resources/js/components/ui/ikon/phosphor.ts` |
| Data contoh | Trip Budi "Gede via Cibodas" PLANNED 15 hari lagi tanpa koordinat pos dan tanpa geometri jalur; status Gede Buka; badan resmi tanpa kontak; tiga pendakian tuntas (Andong 380 m, Batur 600 m, Prau via Wates 700 m); trip Pangrango dibatalkan | `database/seeders/DemoSeeder.php`, `database/seeders/AuthoritySeeder.php` |

## Ruling rencana (W6-R1 sampai W6-R19)

Setiap ruling ditulis dengan format keputusan, alasan, dan akibat bila salah.

| ID | Keputusan | Alasan | Akibat bila salah |
|---|---|---|---|
| W6-R1 | Dasar rencana adalah keadaan akhir simulasi W5 (tree `b7e54014` ditambah W5). W5 belum dieksekusi di repo nyata; perbaikan akhir W4 dan residunya sudah ada di pohon kerja repo nyata (paket `bdccf73..8db4fb3` dan `8db4fb3..45b9a27`), dan tiga jangkar yang tertabrak olehnya dicatat di Pra-cek butir 13. Jangkar "Ganti" di berkas yang disentuh W5 dibuat sependek mungkin; berkas yang W5 tulis ulang tidak dijadikan jangkar panjang. Tiga belas tugas: satu pendakian (1), hitungan (2), mode lapangan (3), pintu tab (4), alasan batal (5), ringkasan (6), keselamatan (7), manifest (8), service worker dan shell (9), unduh dan verifikasi (10), keluar (11), dokumen (12), verifikasi koordinator (13) | Brief koordinator; satu tugas satu unit tinjauan, urutan mengikuti ketergantungan (paket butuh mode lapangan, shell butuh bentuk paket) | Jangkar yang tertabrak perubahan W5 atau perbaikan akhir W4 ditemukan Pra-cek; implementer berhenti NEEDS_CONTEXT |
| W6-R2 | Satu pendakian berlangsung per pengguna: `start()` mengunci baris pengguna (`lockForUpdate`) di transaksi, memeriksa trip IN_PROGRESS lain, dan menolak dengan kalimat yang menyebut trip itu; indeks unik `hiking_sessions.trip_plan_id` lapis kedua. `PendakianAktif` tetap satu-satunya pemilih (tab, strip, Beranda B3), urut `planned_date` lalu `id`. Mulai, selesai, dan batal dibatasi laju (`siklus-trip`) | Titipan izin akhir 8 dan W2-4b; dua tab atau perangkat dapat memulai bersamaan | Pendaki dengan data lama yang sudah punya dua pendakian berlangsung tetap melihat yang terawal; indeks unik gagal dipasang bila ada duplikat (kueri pemeriksaan di docblock migrasi) |
| W6-R3 | Paket lapangan luring dibangun dan diuji tetapi tidak dibuka: `hiking.field_pack.enabled` (`FIELD_PACK_ENABLED`) bawaan `false`. Selama mati: rute manifest 404, tab Mendaki menulis "Paket lapangan luring belum dibuka. Buka mode lapangan selagi ada sinyal dan biarkan tetap terbuka, lalu bawa peta cetak atau perangkat lain sebagai cadangan." tanpa tombol unduh, shell luring tidak dimuat dan tidak disimpan service worker | Q10 (kebijakan data di perangkat) dan Q14 (telaah hukum) belum diputus; pola keputusan W3 P8 | Paket tetap tertutup bagi pengguna sampai pemilik menyalakan; tidak ada data paket di perangkat siapa pun |
| W6-R4 | Kartu darurat, versi cetak, dan pesan rencana di balik `hiking.field_safety.enabled` (`FIELD_SAFETY_ENABLED`) bawaan `false`, terpisah dari sakelar paket. Middleware `fitur` menjawab 404 sebelum otorisasi dan validasi | Spec bagian 15 Q14 menyebut fitur keselamatan tambahan dan kolom nomor registrasi dan jam kembali; induk butir 8 menandainya "Q14 butir kedua" | Pendaki tidak melihat nomor darurat di aplikasi sampai sakelar menyala (Konflik 3 merekomendasikan membuka kartu darurat lebih dulu) |
| W6-R5 | Posisi dihitung di perangkat dan tidak dikirim: pos berikutnya, sisa jarak, naik lagi, dan tiba di pos di `hitung.ts`; rute `trips.hike.position` dan `UpdateHikePositionRequest` dihapus; server hanya menerima urutan pos (`trips.hike.arrive`). `last_known_location` tidak lagi ditulis | §82 minimisasi lokasi; mode lapangan harus bekerja tanpa sinyal; satu sumber hitungan untuk daring dan paket | Posisi lama di `hiking_sessions` tetap tersimpan sampai pemilik memutus pembersihannya (Konflik 7) |
| W6-R6 | Tiba, jeda, dan lanjut dicatat di perangkat lalu dikirim lewat antrean (`antrean.ts`) yang diputar ulang saat `online`, saat aksi berikutnya, dan tiap 30 detik; token XSRF dibaca saat kirim. Ketiga aksi UPDATE bersyarat (tiba hanya memajukan, jeda hanya bila belum jeda, lanjut hanya bila sedang jeda), jadi tanpa kunci idempoten. 401/419 menyimpan aksi dan meminta masuk lagi; 429 dan 5xx menyimpan; galat lain membuang aksi dengan pesan | Spec bagian 10.4 (antrean diputar halaman); induk butir 2 | Aksi yang ditolak validasi hilang dengan pesan, bukan diulang terus |
| W6-R7 | Jeda dan Lanjut mencatat `paused_at` dan `paused_seconds`; waktu bergerak = mulai sampai selesai dikurangi jeda, dan `pada` dari antrean dijepit antara mulai dan sekarang. Selesai menutup jeda yang masih berjalan | Spec bagian 6.12 ("waktu berjalan atau dijeda") dan 6.13 ("waktu bergerak") | Malam di tenda yang tidak dijeda terhitung bergerak |
| W6-R8 | Q9: status Buka di paket turun menjadi "Status belum dapat dipastikan sejak ... Pastikan di pos registrasi." sesudah `hiking.field_pack.open_status_stale_after_hours` (bawaan 24) dari `verified_at`. Server menghitung `turunSejak` dan mengirimnya; perangkat membandingkan dengan jamnya sendiri. Tutup dan Dibatasi tidak turun, Belum diketahui tetap | Usul spec bagian 15 Q9 ("24 jam, diuji dulu"), angka yang sama dengan ambang usang cuaca `conditions.weather_aging_hours` | Ambang lain cukup mengubah satu nilai config atau `FIELD_PACK_OPEN_STALE_HOURS` (Konflik 2) |
| W6-R9 | Service worker hanya menyimpan shell dan aset berversi (`/build/`, `/icons/`, `/fonts/`); dokumen jaringan lebih dulu dengan `/offline`; `activate` hanya membuang `shell-*` lama. Shell luring adalah halaman `Trips/Hike` yang sama, dirender dengan halaman Inertia yang dirakit di perangkat (`createInertiaApp({ page, resolve })`); service worker tidak pernah memalsukan respons Inertia. Daftar simpanan pemasangan ikut sakelar paket. Pesan jaringan menawarkan "Buka paket luring" sebagai tautan dokumen ke `/offline` | Spec bagian 10.4 butir 1 sampai 4; temuan spec bagian 10.1 (deploy menghapus cache paket) | Foto laporan tidak lagi tersimpan untuk dibuka luring (memang isi server) |
| W6-R10 | Penyimpanan perangkat per pengguna: kunci HMAC id pengguna (`KunciPerangkat`, prop bersama `perangkat`) untuk `memount-luring-<kunci>` dan `jejak-pendakian-<kunci>`; kunci disimpan di localStorage supaya shell luring tahu basis datanya. Keluar: tanya bila ada titik jejak belum terkirim, hapus paket, jejak (termasuk basis data lama tanpa pemilik), `pack-*`, OPFS, lalu logout dengan `Clear-Site-Data: "storage"`. Halaman pertama akun lain menghapus data akun sebelumnya | Spec bagian 10.6; celah `jejak.js` | Titik jejak lama di basis data tanpa pemilik dihapus saat keluar sesudah pendaki ditanya; tidak dikirim karena pemiliknya tidak diketahui |
| W6-R11 | Alasan pembatalan DK-50: kolom string nullable dengan daftar putih enum lima alasan; dicatat untuk CANCELLED dan ABANDONED saja, tidak dapat ditulis lewat formulir rencana, opsional | DK-50 ("dicatat opsional"); R-007 (tanpa alasan bukan "Lainnya") | Alasan lain cukup menambah case enum dan labelnya |
| W6-R12 | Laporan kondisi dari ringkasan memakai rute `reports.store` yang ada (kembali ke ringkasan bila bertaut) dan rute baru `reports.update` untuk "Ubah sebelum dimoderasi" (pemilik dan PENDING saja; jalur, tanggal, dan foto tetap). Menyelesaikan trip membuka ringkasannya | Spec bagian 6.13 dan 5.3 | Laporan yang sudah dimoderasi tidak dapat diubah, sesuai kebijakan moderasi |
| W6-R13 | Angka ringkasan: angka pendaki (`recorded_*`, `numbers_edited_at`) bila diubah, rekaman jejak bila ada, data jalur dengan jarak pulang-pergi bila tuntas, kosong bila berbalik tanpa jejak. Tangga memakai ukuran Beranda (tanjakan jalur, satu anak tangga per jalur); pencapaian "Tanjakan pertama di atas N m" untuk tonggak 500, 1.000, 2.000, 3.000 m yang baru dilewati. "Camping pertama di atas 2.000 m" tidak dibangun | Prototype 12 (19,4 km dari 9,7 km; "di atas 1.000 m" sesudah 700 m); satu ukuran tangga di seluruh produk; lokasi berkemah tidak tercatat (R-007) | W7 dapat memutuskan tangga memakai angka ubahan di tiga tempat sekaligus |
| W6-R14 | Kata "Siap berangkat" (W4-C4) tidak tampil di permukaan W6: pintu Mendaki tanpa kata status atau vonis (`TrackTabTest`); `TripStatus::label()` tidak diubah karena dipakai riwayat dan admin; riwayat diserahkan ke W7 | Ruling W4-C4 | Label itu tetap di riwayat sampai W7 |
| W6-R15 | Konflik W5 nomor 8 (strip pendakian aktif dan kartu B3 sama-sama menuju mode lapangan): tidak ada perubahan kode | Spec bagian 5.1 menetapkan strip di setiap halaman kerangka selama pendakian berlangsung; kartu B3 adalah aksi utama Beranda | Dua jalan ke tujuan yang sama di Beranda |
| W6-R16 | Satu ikon baru `clock` di registri Phosphor (path `@phosphor-icons/core` 2.1.1 lewat jsDelivr, sesuai prosedur docblock registri), untuk label "terakhir diketahui" yang diminta spec bagian 10.3 dan induk butir 6. Ikon lain prototype 12 tidak ditambahkan | Ikon jam disebut eksplisit; ikon lain hiasan | Registri tumbuh satu entri; `IconSystemTest` menjaga bobotnya |
| W6-R17 | Logika murni peramban (`hitung.ts`, `kesegaran.ts`, `rakit.ts`, `periksa.ts`, service worker) diuji perilakunya dari PHPUnit lewat Node (`--experimental-strip-types`), tanpa dependensi uji JavaScript | Q17 belum diputus; test sumber saja tidak membuktikan pembulatan atau turunnya status | Node 22.6 atau lebih baru wajib di mesin implementer dan CI (`test.yml` memakai Node 22) |
| W6-R18 | Gerak: satu keyframe CSS `denyut-tiba` (`--dur-sedang`, `--ease-gerak`, skala 1,03) saat tiba di pos, dipangkas global oleh `prefers-reduced-motion` dan setelan Gerak. Tanpa framer-motion dan tanpa View Transition | Mode lapangan MOTION 1 (`DESIGN.md`); perbandingan biaya di bagian skill | Tanpa animasi masuk lembar baru selain yang sudah ada |
| W6-R19 | Paket memuat tiga bagian (`lapangan`, `rencana`, `dinamis`) tanpa peta dasar (Q8), tanpa status gunung api (belum ada modelnya), dan tanpa centang persiapan luring. Versi paket adalah hash isi, tanpa waktu unduh dan tanpa keadaan sesi | Spec bagian 10.2 dan 10.5 tahap 1; versi stabil supaya tidak diunduh ulang setiap tiba di pos | Bagian baru menambah satu nama di `PaketLapangan::BAGIAN` dan satu test |

### Konflik untuk koordinator

1. **Q7, amandemen PRD paket lapangan (Tugas 12, GR-07).** Spec menyetujui paket lapangan, PRD §13, §53, §55, §121, dan
   §145.1 masih menyebut navigasi luring di luar MVP. Rekomendasi: terima GR-07 sebagai keputusan pemilik menurut pola
   W3 P8, dengan batas tertulis "paket lapangan, bukan navigasi offline penuh". Bila salah: GR-07 dibatalkan dengan satu
   catatan log; kodenya tetap di balik sakelar yang mati, jadi tidak ada yang terbuka untuk pengguna.
2. **Q9, ambang turun status Buka (W6-R8).** Rekomendasi: 24 jam dari `verified_at`, dihitung server. Bila salah: status
   Buka di paket tampil lebih lama atau lebih cepat dari yang pemilik mau; diubah lewat `FIELD_PACK_OPEN_STALE_HOURS`
   tanpa kode.
3. **Q10 dan Q14, sakelar paket dan keselamatan (W6-R3, W6-R4).** Rekomendasi: biarkan keduanya mati sampai pemilik
   menulis kebijakan data di perangkat di `docs/KEBIJAKAN-DATA-PRIBADI.md` dan memutus telaah hukum; bila ingin membuka
   bertahap, kartu darurat (nomor 112 dan 115 publik, tanpa data pihak ketiga) adalah kandidat pertama. Bila salah:
   pendaki tidak mendapat paket dan kartu darurat sampai sakelar menyala; tidak ada data yang hilang.
4. **Q17, uji luring tanpa Playwright (Tugas 13 Step 9).** Uji manual butuh server Laravel lokal dengan basis data, yang
   hanya ada sesudah izin Q1 (`memount:demo`). Rekomendasi: minta izin Q1 bersama permintaan izin akhir, atau putuskan
   Q17; sampai itu, bukti luring adalah pratinjau `luring` dan test Node service worker. Bila salah: perilaku service
   worker di peramban sungguhan (pemasangan, `activate`, Clear-Site-Data) belum terbukti di W6.
5. **Izin akhir 9, `encryptHistory` dan `clearHistory`.** Tidak dibangun: butir 7 menghapus penyimpanan perangkat, tetapi
   state halaman Inertia di riwayat peramban (tombol Kembali sesudah keluar) bukan penyimpanan yang dijangkau
   Clear-Site-Data. Rekomendasi: putuskan di W9 bersama telaah keamanan. Bila salah: di perangkat bersama, tombol
   Kembali sesudah keluar dapat menampilkan halaman terakhir pengguna sebelumnya dari cache riwayat.
6. **Lima migrasi aditif W6.** Indeks unik, jeda, alasan batal, angka ubahan, jam kembali dan nomor registrasi. Diuji
   SQLite dan ditulis test Spatial PostgreSQL; tidak dijalankan di Supabase. Rekomendasi: masukkan ke daftar izin akhir
   dengan pemeriksaan duplikat sebelum indeks unik.
7. **Posisi lama di `hiking_sessions.last_known_location` (W6-R5).** Kolomnya tidak lagi ditulis, tetapi isinya di
   Supabase tetap ada. Rekomendasi: migrasi data sekali jalan yang mengosongkan kedua kolom, sesudah izin pemilik
   (mengubah data produksi). Bila salah: posisi terakhir pendaki lama tetap tersimpan tanpa kegunaan.
8. **Ikon jam dari jsDelivr (W6-R16).** Prosedur registri meminta path disalin dari paket di jsDelivr; diambil 7 Oktober
   2026 lewat pembaca web, tanpa memasang paket. Rekomendasi: koordinator membandingkan dua path di Tugas 9 dengan
   `assets/regular/clock.svg` dan `assets/bold/clock-bold.svg` versi 2.1.1 sebelum menerima.
9. **"Kecilkan ke strip" (spec bagian 6.12).** Tidak ada tombol kecilkan terpisah: tombol kembali di kepala mode lapangan
   berlabel "Kembali ke detail trip. Pendakian tetap berjalan." dan strip W2 membuka lagi mode lapangan dari halaman
   mana pun. Rekomendasi: terima. Bila salah: satu tombol tambahan di `TataLetakPendakian`.
10. **Angka ringkasan dan tangga (W6-R13).** Jarak pulang-pergi dari data jalur untuk pendakian tuntas (prototype 12),
    kosong untuk yang berbalik; tangga memakai tanjakan jalur seperti Beranda. Rekomendasi: terima. Bila salah: W7
    menyatukan sumber angka di Beranda, ringkasan, dan Progres.
11. **Konflik W5 nomor 8 (W6-R15).** Rekomendasi: tetap keduanya, sesuai spec bagian 5.1.
12. **Test perilaku lewat Node (W6-R17).** Rekomendasi: terima; tanpa dependensi baru. Bila Q17 kelak menyetujui
    Playwright, test Node tetap berguna sebagai test unit.
13. **Kabar aksi di ringkasan sesudah W4-FK N1.** Repo nyata kini membedakan flash `status` (kabar yang datang bersama
    perpindahan halaman, dibaca dekat kepala halaman tujuan) dan `kabar-aksi` (aksi di halaman yang sama, dibaca dekat
    aksinya). Dasar rencana belum punya `kabarAksi`, dan menambahkannya di W6 bertabrakan dengan residu W4, jadi angka
    pendakian, laporan, dan jam kembali memakai `status` di wilayah selalu terpasang dekat kepala, sama dengan sakelar
    pengingat di ruang kerja trip repo nyata. Rekomendasi: terima untuk W6; perubahan di tempat tetap terlihat (formulir
    angka tertutup, kotak "Laporan Anda terkirim dan menunggu moderasi." menggantikan formulir). Bila salah: pindah ke
    `kabar-aksi` adalah tiga controller (`HikeNumbersController`, `ConditionReportController::update`,
    `TripSafetyController`) dan tiga komponen yang membaca `flash.kabarAksi` dekat tombolnya.

### Pra-cek untuk koordinator

Rencana ditulis di atas keadaan akhir simulasi W5. Periksa ulang di repo nyata sesudah W5 selesai, sebelum Tugas 1.
Alat uji kering untuk itu ada di scratchpad sesi perencanaan: `python alat6/uji_kering6.py` menerapkan blok rencana W5
lalu W6 di memori di atas pohon kerja repo nyata tanpa menulis apa pun. Sesudah W5 dieksekusi, jalankan dengan
`--tanpa-w5` supaya hanya blok W6 yang diterapkan. Penulis rencana tidak menjalankannya, karena pagar penulis hanya
mengizinkan Read, Grep, dan Glob terhadap repo nyata.

| Asumsi | Tempat rencana bergantung | Keadaan di dasar |
|---|---|---|
| 1. Jangkar setiap blok "Ganti" ada tepat sekali | Semua tugas | Sesuai di dasar dan pada putaran ulang. Di pohon kerja repo nyata (Grep multiline, 7 Oktober malam, sesudah residu W4 `45b9a27`, sebelum W5): 50 dari 53 blok yang jangkarnya sudah ada di tree `b7e54014` ditemukan tepat sekali, tiga sisanya di butir 13; 5 blok berjangkar teks W5 dan 10 blok berjangkar teks tugas W6 sebelumnya baru dapat diperiksa sesudah W5 (`uji_kering6.py --tanpa-w5`) |
| 1a. Berkas yang ditulis ulang utuh belum berubah sejak dasar kecuali oleh W5 | Tugas 3 (`HikeModeController.php`, `HikeCheckpointResolver.php`, `PetaPendakian.tsx`, `TataLetakPendakian.tsx`, `Hike.tsx`, `HikeModeCheckpointTest.php`), Tugas 4 (`TrackController.php`, `Track/Index.tsx`), Tugas 6 (`HasilPendakian.php`, `HikeSummaryController.php`, `HikeSummary.tsx`, `HikeSummaryTest.php`), Tugas 9 (`sw.blade.php`) | Blob dasar (12 karakter pertama `git hash-object`, sama dengan tree `b7e54014` karena W5 tidak menyentuhnya): `HikeModeController.php` 3b2b25b82f4f, `HikeCheckpointResolver.php` 7ea5d1762132, `PetaPendakian.tsx` d3cd6dccaf73, `TataLetakPendakian.tsx` 3a88a4072a8e, `Hike.tsx` 1fbc8aee39f1, `HikeModeCheckpointTest.php` d4bba665760a, `TrackController.php` 57544ec2e263, `Track/Index.tsx` 9f2857218b30, `HasilPendakian.php` 6f4492bdd39b, `HikeSummaryController.php` bcc6a8992d82, `HikeSummary.tsx` 905954d79de4, `HikeSummaryTest.php` 81fd00e5c938, `sw.blade.php` 3f9cfa143c50. Tidak satu pun muncul di paket diff W4 sesudah `b7e54014` (Tugas 7, perbaikan akhir, residu). Bila blob di repo nyata berbeda, perubahan itu digabung manual; jangan menimpa |
| 2. `PendakianAktif::untuk()` dipakai strip, Beranda B3 (W5), dan kini tab Mendaki | Tugas 1, 4 | Sesuai di dasar |
| 3. `TripLifecycle::refusal()`, `complete()`, `cancel()` dengan tanda tangan dasar; W4 akhir tidak mengubah `start()` | Tugas 1, 3, 5 | Fakta paket `bdccf73..8db4fb3`: perbaikan akhir W4 menambah impor `Illuminate\Support\Facades\DB`, membungkus `update()` dalam transaksi, dan mengganti konstanta `BELUM_DIMULAI` dengan `TripStatus::belumDimulai()`; `start()`, `complete()`, dan `cancel()` tidak berubah. Jangkar Tugas 3 dan 5 ditemukan di repo nyata; satu blok Tugas 1 di butir 13 |
| 4. `KepalaTrip::untuk()` mengembalikan `id`, `nama`, `jalur`, `gunung`, `jenis`, `hari`, `tanggal`, `jam`, `peserta` | Tugas 4 | Sesuai di dasar |
| 5. `CheckpointPaceService::forTrail()` mengembalikan `dari`, `ke`, `rekaman`, `min_menit`, `maks_menit` | Tugas 3, 7 | Sesuai di dasar |
| 6. `OfficialStatusService::snapshotForTrail()` dan `WeatherService::contextForTrail()` dengan kunci di Interfaces Tugas 8 | Tugas 8 | Sesuai di dasar |
| 7. `Trips/Show.tsx` sesudah W5 masih memuat `window.confirm` batal dan kartu "Selesaikan trip" W4 | Tugas 5 | Sesuai di keadaan akhir W5 |
| 8. `tests/Pratinjau/HalamanPratinjauTest.php` memuat pratinjau W5 (`dasbor-b4`, `jelajah-mendaki`) dan `self::ASAL` | Tugas 3, 6, 7, 9, 10 | Sesuai; rencana memakai `self::ASAL` apa adanya |
| 9. `resources/js/lib/bukanHalamanReact.json` diakhiri pola `"^/b/[^/]+$"` | Tugas 8 | Sesuai di dasar |
| 10. `vite.config.js` dengan satu baris `input` | Tugas 9 | Sesuai di dasar |
| 11. Jangkar dokumen Tugas 12 (kalimat penunjuk "governance record berikutnya GR-07." sesudah GR-06 W5, kalimat §13, §53, §55 butir "Tema gelap", §121 butir 8, baris Mendaki §145.1, judul "### Log keputusan", baris log DK-29, paragraf "Tema lapangan" di `DESIGN.md`, dua baris `docs/ARCHITECTURE.md`, enam baris `docs/fitur/06-pendakian-progres.md`) | Tugas 12 | Di repo nyata (Grep): kesepuluh jangkar di `DESIGN.md`, `docs/ARCHITECTURE.md`, dan `docs/PRD.md` yang sudah ada di tree `b7e54014` ditemukan tepat sekali; paragraf kalimat penunjuk GR dan baris sebelum "### Log keputusan" memuat teks W5, jadi baru dapat diperiksa sesudah W5; `docs/fitur/06-pendakian-progres.md` tidak disentuh W4 maupun W5. Bila W5 di repo nyata memakai nomor GR lain, GR-07 rencana ini diganti nomor berikutnya di judul, kalimat penunjuk, dan baris log |
| 12. Node 22.6 atau lebih baru di mesin implementer | Tugas 2 sampai 10 | `node --version`; simulasi memakai Node 24 dan CI memakai Node 22 |
| 13. Tiga blok yang tertabrak perbaikan akhir W4 dan dua perubahan konvensi (Grep repo nyata 7 Oktober malam, sesudah residu `45b9a27`) | Tugas 1 Step 3, Tugas 6, Tugas 7 Step 3 | (a) `routes/web.php`: `trips.start`, `trips.complete`, dan `trips.cancel` sudah memakai `throttle:...,1,siklus-trip` (W4-Fpre c, diuji `TripLifecycleTest::test_lifecycle_and_checklist_writes_are_rate_limited_with_their_own_keys`). Lewati blok rute Tugas 1 Step 3; merah Tugas 1 di repo nyata 2 gagal, bukan 3, karena `test_starting_completing_and_cancelling_are_rate_limited` sudah lulus. (b) `TripLifecycle.php` sudah mengimpor `Illuminate\Support\Facades\DB`: lewati blok impor Tugas 1 Step 3. (c) Tanda tangan `TripController::show()` kini memuat `OfficialStatusService $statusResmi` sesudah `TrailFitService $trailFit`: Tugas 7 Step 3 menambah `, PesanRencana $pesan` sesudah `KepalaTrip $kepala` pada tanda tangan repo nyata. (d) `flash.kabarAksi` dan konvensi kabar W4-FK N1 sudah ada; rencana tetap memakai `status` (Konflik 13), dan wilayah kabar ringkasan sudah mengikuti konvensi "selalu terpasang" (Tugas 6). (e) `Trail::terbit()` dan `TripStatus::belumDimulai()` sudah ada; rencana tidak memakainya dan tidak bertabrakan |

## Perbandingan skill (DK-57)

Urutan wewenang sama dengan W4 dan W5: arahan pemilik dan aturan kanonis, lalu `DESIGN.md` (arah, termasuk tema
lapangan DK-49 dan dial mode lapangan 1/1) dan prototype 11 dan 12 (patokan), lalu antislop, lalu saran skill. antislop
berlaku mode 1 (selama pekerjaan) atas arahan pemilik yang sudah berdiri; tidak ada pertanyaan baru kepada pemilik.
Launcher impeccable tidak dijalankan karena dapat menulis berkas ke `.claude/skills/` repo nyata; konteks dibaca dari
`DESIGN.md` dan acuan. Skrip ui-ux-pro-max dijalankan dengan direktori kerja salinan, bukan repo.

| Skill | Diambil | Ditolak dan alasannya |
|---|---|---|
| antislop (inti) | R-26: setiap kendali bekerja (tiba, jeda, lanjut, selesai, batalkan dengan alasan, ubah angka, kirim dan ubah laporan, telepon, simpan jam kembali, kirim WhatsApp atau SMS, salin, cetak, siapkan dan unduh paket, pakai paket saja, buka paket luring, keluar); R-27: keadaan pertama kali (belum ada trip, belum dimulai), kosong ("Belum ada" pada petak, laporan, pos tanpa koordinat), menunggu (posisi, prakiraan belum terbit), memproses (unduh paket, kirim laporan), luring (antrean); R-17 dan R-36: tanpa angka, kontak, atau cuaca karangan; satu aksi penuh per layar lapangan | Tidak ada; antislop adalah saringan di atas `DESIGN.md` |
| antislop-copywriting | Kalimat yang menyebut siapa yang ditunggu ("Kontak pengelola jalur ini belum tercatat di Memount.", "Prakiraan BMKG untuk 8 Okt belum terbit saat paket dibuat."); aksi spesifik ("Saya sudah di Telaga Biru", "Kirim untuk dimoderasi", "Simpan jam kembali", "Siapkan paket luring"); tanpa em dash dan tanpa "aman" | Kalimat prototype "Rekam jejak mati, jadi angka ini diisi dari data jalur" diganti "Tanpa rekaman jejak, ..." karena rekam jejak dapat menyala tanpa titik terekam |
| antislop-ui, antislop-layoutmobile, antislop-human | Target 66, 60, dan 48 px di jalur; kebocoran gulir menyamping diukur dengan `scrollWidth` di 320 dan 390 px; status tidak lewat warna saja (pos dicapai berisi, berikutnya bergaris kuning dengan nomor; "terakhir diketahui" dengan kata, ikon, garis putus-putus; batang tangga pendakian ini dengan huruf tebal dan teks pembaca layar) | Kaca pada kepala mode lapangan (DESIGN.md: lapangan solid dan kontras) |
| ui-ux-pro-max | Dua pencarian `--domain ux` (direktori kerja salinan; kueri status luring dan antrean, serta target sentuh dan konfirmasi): satu pesan status atomik (`role="status" aria-atomic`) untuk tiba di pos, bukan wilayah live yang bersaing; umpan balik sesudah kirim (memproses lalu hasil, termasuk antrean); cincin fokus juga di dalam lembar; konfirmasi sebelum aksi yang tidak dapat dikembalikan (batal, selesai, keluar dengan jejak belum terkirim); target sentuh web menurut WCAG, bukan angka iOS atau Android; `aria-current="step"` untuk pos berikutnya di daftar pos | Keluaran `--design-system` (gradien, Lucide, kartu melayang) seperti W1 sampai W5; skor kesiapan dalam persen (R-005) |
| design-taste-frontend | Dial dibaca dari brief (mode lapangan VARIANCE rendah, MOTION 1, DENSITY sedang); 4.5 keadaan interaktif lengkap (memproses, nonaktif dengan alasan tertulis, galat terikat isian); 4.11 kunci tema per halaman (versi cetak dipaksa terang saat mencetak); 6.B gerak dikurangi wajib | Pola gerak 5.A sampai 5.C (sticky-stack, horizontal-pan, scroll-reveal) tidak cocok untuk mode Operate di jalur; skill ini menyatakan dirinya untuk halaman pemasaran |
| impeccable (Operate, `reference/craft-floor.md`) | Mode Operate: kebiasaan peramban dan kebutuhan adegan pakai (sarung tangan, layar silau, satu tangan) lebih dulu dari ekspresi; kosakata komponen yang sudah ada dipakai ulang (`LembarBawah`, `Dialog`, `ChipPilihan`, `KontrolSegmen`, `Sakelar`, `Lencana`, `Tautan`, `Button`, `PetaGaris`, `ProfilElevasi`, `DaftarPos`); tanpa eyebrow di pintu Mendaki | Ilustrasi dan foto hero prototype 12 (bukan data pendaki) |
| frontend-ui-engineering | Komposisi lewat slot (`ModeLapangan` menerima `selesai` dan `pengaturan`), keadaan kosong dan galat per bagian, fokus kembali ke pemicu sesudah lembar ditutup, diperiksa di 320, 390, dan 1440 px | Skeleton memuat: halaman dirender server lewat Inertia; satu-satunya pengambilan data klien adalah unduhan paket yang kemajuannya ditulis per bagian |
| vercel-react-best-practices | `bundle-conditional` (shell luring dan MapLibre hanya dimuat bila dipakai; daftar simpanan shell ikut sakelar), `rerender-no-inline-components` (`Lapangan`, `Isian`, `Petak` di tingkat modul), `rerender-lazy-state-init` (tema lapangan), `rendering-conditional-render` (ternary), `async-parallel` (bagian paket dibaca bersamaan; pembersihan saat keluar dengan `Promise.allSettled`), `client-localstorage-schema` (satu kunci, dibungkus `try`) | Aturan khusus Next.js dan RSC (`next/dynamic`, `React.cache`, `after()`) tidak berlaku untuk Inertia |
| vercel-composition-patterns | `patterns-explicit-variants` (varian ringkasan selesai dan batal dari server, bukan boolean di klien), `patterns-children-over-render-props` (slot sebagai `ReactNode`), `architecture-avoid-boolean-props` (`KartuDarurat` dan `SelesaiLapangan` sebagai komponen sendiri, bukan prop `denganDarurat`) | Context provider: keadaan mode lapangan milik satu kait (`useModeLapangan`) di satu halaman |
| web-design-guidelines | Cincin fokus terlihat di kedua tema lapangan, `tabular-nums` untuk angka, tautan telepon dengan nama aksesibel yang menyebut nomor dan tujuannya, label isian yang menyebut satuannya ("Jarak (km)") | Panduan daring tidak diambil ulang di sesi ini; yang dipakai butir yang sudah dibaca di W5 |
| shadcn (rujukan saja, tanpa `npx shadcn init`) | Pola Sheet dan Dialog dicocokkan dengan `LembarBawah` dan `Dialog` proyek | Memasang komponen shadcn (dependensi dan kosakata kedua) |
| security-and-hardening (butir 4, 5, 7) | Model ancaman per batas: manifest dan bagian paket (IDOR: Policy pemilik; kebocoran cache: `private, no-store`; isi minimal tanpa token dan tanpa orang lain), unduhan di klien (integritas: SHA-256 per bagian dibaca ulang dari penyimpanan; tidak ada `eval` atau `innerHTML` dari isi paket), keluar akun (data perangkat dihapus, `Clear-Site-Data`, kunci HMAC bukan id); rute fitur yang mati menjawab 404 sebelum otorisasi | Enkripsi paket di peramban dengan kunci di peramban yang sama (spec bagian 10.6: pengaman nyata adalah minimisasi dan kunci layar) |
| laravel-best-practices | Form Request untuk setiap tulisan (`RecordArrivalRequest`, `PauseHikeRequest`, `CancelTripRequest`, `UpdateHikeNumbersRequest`, `UpdateConditionReportRequest`, `UpdateTripReturnRequest`), Policy di setiap rute, migrasi aditif dengan `down()`, UPDATE bersyarat dan transaksi dengan `lockForUpdate`, kueri berbatas dan tanpa N+1 (`TrackTabTest::test_the_tab_costs_the_same_with_one_or_ten_trips`), middleware beralias untuk sakelar fitur | Antarmuka repository untuk paket: satu implementasi, lapisan dangkal |
| testing-best-practices | Test perilaku lewat HTTP dan Node; tamu, pengguna lain, validasi, dan batas laju untuk setiap rute baru; waktu dibekukan; test lama diperbarui menurut niat dengan alasan tertulis | Test peramban sungguhan (`laravel/dusk`, Playwright) tidak dipasang (Q17) |
| dataviz (tangga ringkasan, profil) | Satu seri dengan skala sama, nilai tertulis di setiap batang, tanpa legenda, satu garis dasar | Palet kategorikal: satu warna merek dengan dua tingkat |
| Gerak: CSS lawan framer-motion lawan vercel-react-view-transitions | CSS: satu keyframe `denyut-tiba` (beberapa puluh bita CSS) dengan token `--dur-sedang` dan `--ease-gerak`, dijepit global oleh `prefers-reduced-motion` dan `data-gerak="kurangi"` | framer-motion 14 (terpasang, tidak diimpor): biaya terukur W5 123,5 kB mentah atau 39,2 kB gzip untuk paket lengkap, 74,7 kB atau 25,6 kB gzip untuk `m` dengan `domAnimation`, untuk satu denyut di jalur dan bundel shell luring yang disimpan di perangkat. View Transition: tanpa biaya bundel (React 19.3 dan Inertia 3.7 sudah membawanya), dukungan Chromium 125+, Safari 18.2+, Firefox 144+, tetapi mode lapangan tidak berpindah halaman sesudah dibuka (W6-R18) |
| codebase-design | Modul dalam: `hitung.ts` (satu antarmuka kecil untuk semua angka lapangan), `PaketLapangan` (tiga bagian, satu versi), `simpan.ts` (satu pemilik IndexedDB), `keluar.ts` (satu jalan pembersihan) | Adapter penyimpanan generik untuk IndexedDB dan memori: antrean sudah punya `PenyimpananAntrean` sebagai seam satu-satunya |

**Pembacaan desain (antislop bagian 3):** mode lapangan dan ringkasan aplikasi perencanaan pendakian untuk pendaki
pemula Indonesia di ponsel, mode Operate, dial mode lapangan ENERGY 1 / MOTION 1 dan ringkasan ENERGY 2 / RHYTHM 2 /
MOTION 2 (`DESIGN.md`). Di jalur, satu keputusan per layar: ke mana pos berikutnya, seberapa jauh, apakah sudah tiba,
dan apa yang dilakukan bila darurat; sesudah turun, apa yang dicapai dibanding diri sendiri dan apa yang dapat dibagikan
untuk pendaki berikutnya. Motif identitas: pos bernomor, garis jalur, dan tangga tanjakan.

**Inventaris skill untuk gelombang ini (CLAUDE.md bagian 7):**

- INTI: superpowers:writing-plans, testing-best-practices, laravel-best-practices, security-and-hardening (butir 4, 5,
  dan 7), antislop, antislop-copywriting, antislop-ui, antislop-layoutmobile, antislop-human, impeccable,
  ui-ux-pro-max, design-taste-frontend (terbatas, lihat tabel).
- PENDUKUNG: frontend-ui-engineering, vercel-react-best-practices, vercel-composition-patterns, web-design-guidelines,
  dataviz, codebase-design, tailwindcss-development (varian `print:`, `has-[...]`), shadcn (rujukan pola saja),
  vercel-react-view-transitions dan modern-web-guidance (dibandingkan untuk gerak dan API peramban: Service Worker,
  Cache Storage, IndexedDB, `navigator.storage.persist()`, Wake Lock, Clear-Site-Data; tidak menambah kode).
- LINTAS: superpowers:verification-before-completion dan Delivery Gate antislop; chrome-devtools MCP untuk pengukuran
  dan tangkapan.
- N/A:
  - archify: rencana tanpa diagram; alur paket ditulis sebagai tabel di GR-07 dan `docs/ARCHITECTURE.md`;
  - slides, banner-design, imagegen-frontend-web, imagegen-frontend-mobile, image-to-code: bukan artefak halaman;
  - brand dan brandkit: aset logo menunggu gerbang DK-16;
  - animate, improve-animations, emil-design-eng, find-animation-opportunities: satu gerak status saja (W6-R18);
  - livewire-development dan volt-development: Livewire sudah dipensiunkan;
  - deploying-to-cloud dan deploy-to-vercel: tanpa rilis;
  - mobile-native dan write-swift: bukan aplikasi native; target sentuh diperiksa lewat antislop-layoutmobile;
  - skill caveman, ngodingpakeai, dan alur commit: tanpa commit (keputusan pemilik).

## File Structure

**Dibuat:**

- Tugas 1: `database/migrations/2026_10_07_000100_add_unique_trip_plan_index_to_hiking_sessions_table.php`, `tests/Feature/SingleActiveHikeTest.php`, `tests/Spatial/HikingSessionUniqueTest.php`.
- Tugas 2: `resources/js/components/mendaki/hitung.ts`, `tests/Feature/FieldModeFiguresTest.php`.
- Tugas 3: `app/Services/Trips/HalamanMendaki.php`, `app/Http/Requests/Trips/RecordArrivalRequest.php`, `app/Http/Requests/Trips/PauseHikeRequest.php`, `database/migrations/2026_10_07_000200_add_pause_to_hiking_sessions_table.php`, `resources/js/components/mendaki/` (`types.ts`, `antrean.ts`, `usePosisiLapangan.ts`, `useLayarMenyala.ts`, `useModeLapangan.ts`, `ModeLapangan.tsx`).
- Tugas 4: `app/Services/Trips/PintuMendaki.php`.
- Tugas 5: `app/Enums/CancellationReason.php`, `app/Http/Requests/Trips/CancelTripRequest.php`, `database/migrations/2026_10_07_000300_add_cancellation_reason_to_trip_plans_table.php`, `resources/js/components/trips/FormulirSelesai.tsx`, `resources/js/components/trips/BatalkanTrip.tsx`, `resources/js/components/mendaki/SelesaiLapangan.tsx`, `tests/Feature/TripCancellationReasonTest.php`.
- Tugas 6: `app/Http/Controllers/Community/HikeNumbersController.php`, `app/Http/Requests/Community/UpdateHikeNumbersRequest.php`, `app/Http/Requests/UpdateConditionReportRequest.php`, `database/migrations/2026_10_07_000400_add_recorded_values_to_hiking_history_table.php`, `resources/js/components/community/` (`ringkasan.ts`, `AngkaPendakian.tsx`, `BandingDiriSendiri.tsx`, `LaporanPendakian.tsx`), `tests/Feature/HikeSummaryReportTest.php`.
- Tugas 7: `app/Http/Middleware/EnsureFeatureEnabled.php`, `app/Services/Trips/KartuDarurat.php`, `app/Services/Trips/PesanRencana.php`, `app/Services/Trips/CetakTrip.php`, `app/Http/Controllers/Trips/TripSafetyController.php`, `app/Http/Requests/Trips/UpdateTripReturnRequest.php`, `database/migrations/2026_10_07_000500_add_return_and_registration_to_trip_plans_table.php`, `resources/js/components/mendaki/KartuDarurat.tsx`, `resources/js/components/trips/KeselamatanTrip.tsx`, `resources/js/pages/Trips/Print.tsx`, `tests/Feature/EmergencyCardTest.php`, `tests/Feature/TripPlanMessageTest.php`, `tests/Spatial/FieldModeColumnsSchemaTest.php`.
- Tugas 8: `app/Services/Trips/PaketLapangan.php`, `app/Http/Controllers/Trips/FieldPackController.php`, `resources/js/luring/kesegaran.ts`, `tests/Feature/FieldPackManifestTest.php`, `tests/Feature/FieldPackStalenessTest.php`.
- Tugas 9: `app/Support/AsetLuring.php`, `resources/js/luring/` (`simpan.ts`, `rakit.ts`, `DaftarPaket.tsx`, `shell.tsx`), `tests/Feature/OfflineShellTest.php`.
- Tugas 10: `app/Support/KunciPerangkat.php`, `resources/js/luring/` (`periksa.ts`, `paket.ts`, `baca.ts`, `useLapanganPaket.ts`), `resources/js/components/mendaki/PintuPaket.tsx`, `tests/Feature/FieldPackDeviceTest.php`.
- Tugas 11: `resources/js/luring/keluar.ts`, `tests/Feature/LogoutClearsDeviceDataTest.php`.

**Dihapus:** `app/Http/Requests/Trips/UpdateHikePositionRequest.php` (Tugas 3) dan rute `trips.hike.position`.

**Diubah:**

- Tugas 1: `app/Services/Trips/TripLifecycle.php`, `app/Http/Controllers/Trips/TripLifecycleController.php`, `app/Http/Controllers/Trips/TrackController.php`, `app/Services/Trips/PendakianAktif.php`, `routes/web.php`.
- Tugas 3: `app/Http/Controllers/Trips/HikeModeController.php`, `app/Services/Trips/HikeCheckpointResolver.php`, `app/Models/HikingSession.php`, `app/Services/Trips/TripLifecycle.php`, `routes/web.php`, `resources/css/app.css`, `resources/js/components/mendaki/hitung.ts`, `resources/js/components/peta/usePetaDasar.ts`, `resources/js/components/peta/warnaToken.ts`, `resources/js/components/trips/PetaPendakian.tsx`, `resources/js/components/trips/TataLetakPendakian.tsx`, `resources/js/components/trips/usePendakian.ts`, `resources/js/pages/Trips/Hike.tsx`, `tests/Feature/HikeModeCheckpointTest.php`, `tests/Feature/FieldModeFiguresTest.php`, `tests/Feature/LocationPrivacyTest.php`, `tests/Feature/MapConfigTest.php`, `tests/Feature/TripOwnershipTest.php`, `tests/Feature/TripReactPagesTest.php`, `tests/Pratinjau/HalamanPratinjauTest.php`.
- Tugas 4: `app/Http/Controllers/Trips/TrackController.php`, `config/hiking.php`, `resources/js/pages/Track/Index.tsx`, `tests/Feature/TrackTabTest.php`.
- Tugas 5: `app/Http/Controllers/Trips/TripController.php`, `app/Http/Controllers/Trips/TripLifecycleController.php`, `app/Http/Requests/Trips/CompleteTripRequest.php`, `app/Models/TripPlan.php`, `app/Services/Trips/HalamanMendaki.php`, `app/Services/Trips/TripLifecycle.php`, `resources/js/components/mendaki/types.ts`, `resources/js/pages/Trips/Hike.tsx`, `resources/js/pages/Trips/Show.tsx`, `tests/Feature/ButtonFeedbackTest.php`.
- Tugas 6: `app/Services/Community/HasilPendakian.php`, `app/Http/Controllers/Community/HikeSummaryController.php`, `app/Http/Controllers/Community/ConditionReportController.php`, `app/Http/Controllers/Trips/TripLifecycleController.php`, `app/Models/HikingHistory.php`, `routes/web.php`, `resources/js/pages/Community/HikeSummary.tsx`, `tests/Feature/HikeSummaryTest.php`, `tests/Feature/TripLifecycleTest.php`, `tests/Pratinjau/HalamanPratinjauTest.php`.
- Tugas 7: `.env.example`, `bootstrap/app.php`, `config/hiking.php`, `routes/web.php`, `app/Models/TripPlan.php`, `app/Services/Trips/HalamanMendaki.php`, `app/Http/Controllers/Trips/TripController.php`, `resources/js/components/mendaki/hitung.ts`, `resources/js/components/mendaki/types.ts`, `resources/js/components/mendaki/ModeLapangan.tsx`, `resources/js/pages/Trips/Show.tsx`, `tests/Feature/FieldModeFiguresTest.php`, `tests/Pratinjau/HalamanPratinjauTest.php`.
- Tugas 8: `config/hiking.php`, `routes/web.php`, `resources/js/lib/bukanHalamanReact.json`.
- Tugas 9: `resources/views/sw.blade.php`, `resources/views/offline.blade.php`, `vite.config.js`, `app/Services/Trips/PaketLapangan.php`, `resources/js/components/ui/ikon/phosphor.ts`, `tests/Feature/ProgressiveWebAppTest.php`, `tests/Pratinjau/HalamanPratinjauTest.php`.
- Tugas 10: `app/Http/Middleware/HandleInertiaRequests.php`, `resources/js/types/index.d.ts`, `resources/js/luring/shell.tsx`, `resources/js/components/mendaki/useModeLapangan.ts`, `resources/js/components/mendaki/ModeLapangan.tsx`, `resources/js/pages/Trips/Hike.tsx`, `resources/js/pages/Track/Index.tsx`, `resources/js/pages/Trips/Show.tsx`, `resources/js/layouts/PesanJaringan.tsx`, `tests/Feature/ShellFeedbackTest.php`, `tests/Pratinjau/HalamanPratinjauTest.php`.
- Tugas 11: `app/Http/Controllers/Auth/AuthenticatedSessionController.php`, `resources/js/jejak.js`, `resources/js/components/trips/usePendakian.ts`, `resources/js/pages/Trips/Hike.tsx`, `resources/js/layouts/MenuAkun.tsx`, `resources/js/components/akun/PintuAkun.tsx`, `resources/js/layouts/AppLayout.tsx`, `resources/js/components/trips/TataLetakPendakian.tsx`, `tests/Feature/PageHeaderContractTest.php`.
- Tugas 12: `docs/PRD.md`, `DESIGN.md`, `docs/ARCHITECTURE.md`, `docs/fitur/06-pendakian-progres.md`.

Tugas 13 (koordinator) membuat `docs/desain/tangkapan/w6/*.webp` dan `docs/desain/tangkapan/w6/README.md`, dan
memperbarui `HANDOVER.md` bagian 0 serta `.superpowers/sdd/2026-09-26-memount-v3-program.md`.

---

### Task 1: Satu pendakian berlangsung per pengguna

Titipan izin akhir butir 8 (pelacak program) dan W2-4b; ruling W6-R2. `TripLifecycle::start()` menolak memulai trip
bila pengguna yang sama masih punya trip berlangsung, di dalam transaksi yang mengunci baris pengguna, sehingga dua
permintaan bersamaan dari dua tab atau dua perangkat tidak lolos berdua. Indeks unik `hiking_sessions.trip_plan_id`
menjadi lapis kedua. Pemilih pendakian aktif tetap satu (`PendakianAktif`), dan tab Mendaki memakainya untuk membuka
mode lapangan. Mulai, selesai, dan batal kini dibatasi laju dengan kunci `siklus-trip`.

**Files:**
- Create: `database/migrations/2026_10_07_000100_add_unique_trip_plan_index_to_hiking_sessions_table.php`
- Modify: `app/Services/Trips/TripLifecycle.php`, `app/Http/Controllers/Trips/TripLifecycleController.php`, `app/Http/Controllers/Trips/TrackController.php`, `app/Services/Trips/PendakianAktif.php` (docblock), `routes/web.php`
- Test: `tests/Feature/SingleActiveHikeTest.php` (baru), `tests/Spatial/HikingSessionUniqueTest.php` (baru, PostgreSQL)

**Interfaces:**
- Consumes: `TripLifecycle::refusal(TripPlan, TripStatus): ?string`; `PendakianAktif::untuk(User): ?array{id, jalur, posBerikutnya, mulai, url}` (W2, dipakai strip kerangka dan Beranda B3 W5); `config('hiking.rate_limits.personal_writes_per_minute')`; `Tests\Spatial\SpatialTestCase`.
- Produces: `TripLifecycle::start(TripPlan $trip): ?string` (null bila dimulai, kalimat penolakan bila pengguna masih punya pendakian berlangsung: `Pendakian "{nama}" masih berlangsung. Selesaikan atau batalkan dulu sebelum memulai trip ini.`); indeks `hiking_sessions_trip_plan_id_unique`; rute `trips.start`, `trips.complete`, `trips.cancel` berbatas laju `siklus-trip`.

- [ ] **Step 1: Tulis test yang gagal.** Buat berkas test baru dengan `make:test` (Global Constraints); test Spatial
dibuat langsung di `tests/Spatial/`. Ganti isinya persis.

Buat `tests/Feature/SingleActiveHikeTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\HikingSessionStatus;
use App\Enums\TripStatus;
use App\Models\HikingSession;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Satu pendakian berlangsung per pendaki (permintaan izin akhir butir 8, ruling W6-R2) dan
 * satu pemilih pendakian aktif untuk tab Mendaki, strip, dan Beranda (titipan W2-4b).
 *
 * Dua pendakian berlangsung bersamaan membuat tab Mendaki, strip, dan Beranda dapat menunjuk
 * trip yang berbeda, dan mode lapangan mencatat kemajuan pada trip yang tidak sedang didaki.
 */
class SingleActiveHikeTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_second_hike_cannot_start_while_another_is_in_progress(): void
    {
        $budi = User::factory()->create();
        TripPlan::factory()->for($budi)->create(['name' => 'Gede via Cibodas', 'status' => TripStatus::IN_PROGRESS->value]);
        $berikutnya = TripPlan::factory()->for($budi)->create(['name' => 'Papandayan', 'status' => TripStatus::PLANNED->value]);

        $this->actingAs($budi)
            ->post(route('trips.start', $berikutnya))
            ->assertRedirect(route('trips.show', $berikutnya))
            ->assertSessionHas('penolakan-trip', 'Pendakian "Gede via Cibodas" masih berlangsung. Selesaikan atau batalkan dulu sebelum memulai trip ini.');

        $this->assertSame(TripStatus::PLANNED, $berikutnya->fresh()->status);
        $this->assertNull($berikutnya->fresh()->hikingSession);
    }

    public function test_the_next_hike_starts_once_the_previous_one_has_ended(): void
    {
        $budi = User::factory()->create();
        TripPlan::factory()->for($budi)->create(['status' => TripStatus::COMPLETED->value]);
        TripPlan::factory()->for($budi)->create(['status' => TripStatus::CANCELLED->value]);
        $berikutnya = TripPlan::factory()->for($budi)->create(['status' => TripStatus::PLANNED->value]);

        $this->actingAs($budi)
            ->post(route('trips.start', $berikutnya))
            ->assertRedirect(route('trips.hike', $berikutnya));

        $this->assertSame(TripStatus::IN_PROGRESS, $berikutnya->fresh()->status);
        $this->assertSame(HikingSessionStatus::ACTIVE, $berikutnya->fresh()->hikingSession->status);
    }

    /**
     * Pendakian berlangsung milik pendaki lain tidak menahan siapa pun.
     */
    public function test_another_hikers_hike_does_not_block_a_start(): void
    {
        TripPlan::factory()->create(['status' => TripStatus::IN_PROGRESS->value]);
        $trip = TripPlan::factory()->create(['status' => TripStatus::PLANNED->value]);

        $this->actingAs($trip->user)
            ->post(route('trips.start', $trip))
            ->assertRedirect(route('trips.hike', $trip));
    }

    /**
     * Satu trip, satu sesi mode lapangan: indeks unik hiking_sessions.trip_plan_id.
     */
    public function test_a_trip_cannot_hold_two_field_sessions(): void
    {
        $trip = TripPlan::factory()->create(['status' => TripStatus::IN_PROGRESS->value]);
        $sesi = ['trip_plan_id' => $trip->id, 'user_id' => $trip->user_id, 'status' => HikingSessionStatus::ACTIVE->value, 'started_at' => now()];

        HikingSession::create($sesi);

        $this->expectException(UniqueConstraintViolationException::class);
        HikingSession::create($sesi);
    }

    /**
     * Data lama dapat masih memuat dua pendakian berlangsung. Tab Mendaki, strip, dan
     * Beranda memilih yang sama: tanggal rencana paling awal, lalu id terkecil
     * (PendakianAktif).
     */
    public function test_the_tab_the_strip_and_the_home_pick_the_same_hike(): void
    {
        $budi = User::factory()->create();
        $lebihAwal = TripPlan::factory()->for($budi)->create(['status' => TripStatus::IN_PROGRESS->value, 'planned_date' => now()->subDays(2)->toDateString()]);
        TripPlan::factory()->for($budi)->create(['status' => TripStatus::IN_PROGRESS->value, 'planned_date' => now()->subDay()->toDateString()]);

        $this->actingAs($budi)->get(route('track'))->assertRedirect(route('trips.hike', $lebihAwal));

        $this->actingAs($budi)->get('/trails')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('pendakianAktif.id', $lebihAwal->id));

        $this->actingAs($budi)->get('/dashboard')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('fase.kartu.aksi.href', route('trips.hike', $lebihAwal, false)));
    }

    /**
     * Ketiga aksi siklus trip ditulis sekali per ketukan, jadi dibatasi laju seperti tulisan
     * pribadi lain (routes/web.php, awalan kunci siklus-trip).
     */
    public function test_starting_completing_and_cancelling_are_rate_limited(): void
    {
        $trip = TripPlan::factory()->create(['status' => TripStatus::CANCELLED->value]);
        $batas = (int) config('hiking.rate_limits.personal_writes_per_minute');

        foreach (range(1, $batas) as $i) {
            $this->actingAs($trip->user)->post(route('trips.start', $trip))->assertRedirect();
        }

        $this->actingAs($trip->user)->post(route('trips.start', $trip))->assertStatus(429);
        $this->actingAs($trip->user)->post(route('trips.cancel', $trip))->assertStatus(429);
        $this->actingAs($trip->user)->post(route('trips.complete', $trip))->assertStatus(429);
    }
}
```

Buat `tests/Spatial/HikingSessionUniqueTest.php`:

```php
<?php

namespace Tests\Spatial;

use App\Enums\HikingSessionStatus;
use App\Enums\TripStatus;
use App\Models\HikingSession;
use App\Models\TripPlan;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Indeks unik hiking_sessions.trip_plan_id di PostgreSQL (R-024, ruling W6-R2): suite SQLite
 * tidak membuktikan nama, keunikan, dan jenis galat indeks seperti di produksi. Migrasinya
 * tidak dijalankan di Supabase sebelum izin pemilik (permintaan izin akhir butir 8).
 */
class HikingSessionUniqueTest extends SpatialTestCase
{
    public function test_the_unique_index_exists_and_refuses_a_second_session(): void
    {
        $indeks = DB::selectOne("SELECT indexdef FROM pg_indexes WHERE tablename = 'hiking_sessions' AND indexname = 'hiking_sessions_trip_plan_id_unique'");

        $this->assertNotNull($indeks);
        $this->assertStringContainsString('UNIQUE', $indeks->indexdef);

        $trip = TripPlan::factory()->create(['status' => TripStatus::IN_PROGRESS->value]);
        $sesi = ['trip_plan_id' => $trip->id, 'user_id' => $trip->user_id, 'status' => HikingSessionStatus::ACTIVE->value, 'started_at' => now()];

        HikingSession::create($sesi);

        $this->expectException(UniqueConstraintViolationException::class);
        HikingSession::create($sesi);
    }
}
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='SingleActiveHike|TripLifecycle|ActiveHikeStrip|TrackTab'`
Expected: FAIL. Pada simulasi rencana: 33 test, 30 lulus, 3 gagal. Yang merah: tiga test `SingleActiveHikeTest` (pendakian kedua masih dapat dimulai dan membuka mode lapangannya; trip yang sama masih dapat menyimpan dua sesi lapangan; mulai, selesai, dan batal belum dibatasi laju). Di repo nyata yang ketiga sudah lulus sebelum implementasi, karena perbaikan akhir W4 sudah membatasi ketiga rute itu (Pra-cek butir 13).

- [ ] **Step 3: Tulis implementasinya.** Migrasi dibuat dengan
`DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:migration --no-interaction add_unique_trip_plan_index_to_hiking_sessions_table`,
lalu berkasnya diganti nama menjadi `2026_10_07_000100_add_unique_trip_plan_index_to_hiking_sessions_table.php` dan
isinya diganti persis.

Buat `database/migrations/2026_10_07_000100_add_unique_trip_plan_index_to_hiking_sessions_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Satu sesi mode lapangan per trip (permintaan izin akhir butir 8, ruling W6-R2).
 *
 * TripLifecycle::start() sudah memakai firstOrCreate, tetapi dua permintaan "Mulai" yang tiba
 * bersamaan dapat sama-sama tidak menemukan sesi lalu sama-sama membuatnya. Indeks unik
 * membuat basis data menolak sesi kedua. Aditif: indeks indeks_foreign_key yang sudah ada
 * pada kolom ini tidak disentuh.
 *
 * Sebelum dijalankan di Supabase (atas izin pemilik), pastikan tidak ada trip dengan dua sesi:
 * SELECT trip_plan_id, count(*) FROM hiking_sessions GROUP BY trip_plan_id HAVING count(*) > 1;
 * Tanpa baris hasil, migrasi ini aman; dengan baris hasil, migrasi gagal dan tidak mengubah apa pun.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hiking_sessions', function (Blueprint $table) {
            $table->unique('trip_plan_id', 'hiking_sessions_trip_plan_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('hiking_sessions', function (Blueprint $table) {
            $table->dropUnique('hiking_sessions_trip_plan_id_unique');
        });
    }
};
```

`app/Http/Controllers/Trips/TrackController.php`:

1. Ganti:

```php
use App\Models\TripPlan;
use App\Support\Timezone;
```

dengan:

```php
use App\Models\TripPlan;
use App\Services\Trips\PendakianAktif;
use App\Support\Timezone;
```

2. Ganti:

```php
        $user = $request->user();

        $berjalan = TripPlan::query()
            ->whereBelongsTo($user)
            ->where('status', TripStatus::IN_PROGRESS->value)
            ->orderBy('planned_date')
            ->first();

        if ($berjalan !== null) {
            return redirect()->route('trips.hike', $berjalan);
        }
```

dengan:

```php
        $user = $request->user();

        // Pemilih yang sama dengan strip dan Beranda (titipan W2-4b, ruling W6-R2).
        $berjalan = app(PendakianAktif::class)->untuk($user);

        if ($berjalan !== null) {
            return redirect()->route('trips.hike', $berjalan['id']);
        }
```

`app/Http/Controllers/Trips/TripLifecycleController.php`:

1. Ganti:

```php
        $this->authorize('update', $trip);

        if ($penolakan = $lifecycle->refusal($trip, TripStatus::IN_PROGRESS)) {
            return redirect()->route('trips.show', $trip)->with('penolakan-trip', $penolakan);
        }

        $lifecycle->start($trip);

        return redirect()->route('trips.hike', $trip);
```

dengan:

```php
        $this->authorize('update', $trip);

        if ($penolakan = $lifecycle->refusal($trip, TripStatus::IN_PROGRESS) ?? $lifecycle->start($trip)) {
            return redirect()->route('trips.show', $trip)->with('penolakan-trip', $penolakan);
        }

        return redirect()->route('trips.hike', $trip);
```

`app/Services/Trips/PendakianAktif.php`:

1. Ganti:

```php
 * dihitung peramban, sehingga tidak ada zona waktu yang ditebak di sini.
 */
```

dengan:

```php
 * dihitung peramban, sehingga tidak ada zona waktu yang ditebak di sini.
 *
 * Satu-satunya pemilih pendakian aktif (titipan W2-4b, ruling W6-R2): tab Mendaki, strip, dan
 * Beranda memanggil kelas ini. Sejak W6 hanya satu trip per pendaki yang dapat berlangsung
 * (TripLifecycle::start); urutan tanggal rencana lalu id menjaga data lama yang masih memuat
 * dua pendakian berlangsung tetap terpilih sama di ketiga tempat.
 */
```

`app/Services/Trips/TripLifecycle.php`:

1. Ganti:

```php
use App\Services\PreparationService;

/**
```

dengan:

```php
use App\Services\PreparationService;
use Illuminate\Support\Facades\DB;

/**
```

2. Ganti:

```php
    }

    public function start(TripPlan $trip): void
    {
        $trip->hikingSession()->firstOrCreate([], [
            'user_id' => $trip->user_id,
            'status' => HikingSessionStatus::ACTIVE->value,
            'started_at' => now(),
        ]);

        $trip->update(['status' => TripStatus::IN_PROGRESS->value]);
        $this->reminders->sinkronkan($trip);
    }
```

dengan:

```php
    }

    /**
     * Memulai pendakian, satu pendakian berlangsung per pendaki (permintaan izin akhir butir
     * 8, ruling W6-R2). Pemeriksaan dan perubahan status berjalan di satu transaksi yang
     * lebih dulu mengunci baris pengguna, sehingga dua ketukan "Mulai" pada dua trip berbeda
     * tidak lolos bersamaan di PostgreSQL; SQLite menulis berurutan, jadi kuncinya tanpa efek
     * di sana. Sesi kedua untuk trip yang sama ditolak indeks unik hiking_sessions.trip_plan_id.
     *
     * @return string|null kalimat penolakan, atau null bila pendakian dimulai
     */
    public function start(TripPlan $trip): ?string
    {
        return DB::transaction(function () use ($trip): ?string {
            User::query()->whereKey($trip->user_id)->lockForUpdate()->first();

            $lain = TripPlan::query()
                ->where('user_id', $trip->user_id)
                ->where('status', TripStatus::IN_PROGRESS->value)
                ->whereKeyNot($trip->id)
                ->orderBy('planned_date')
                ->orderBy('id')
                ->first(['id', 'name']);

            if ($lain !== null) {
                return sprintf('Pendakian "%s" masih berlangsung. Selesaikan atau batalkan dulu sebelum memulai trip ini.', $lain->name);
            }

            $trip->hikingSession()->firstOrCreate([], [
                'user_id' => $trip->user_id,
                'status' => HikingSessionStatus::ACTIVE->value,
                'started_at' => now(),
            ]);

            $trip->update(['status' => TripStatus::IN_PROGRESS->value]);
            $this->reminders->sinkronkan($trip);

            return null;
        });
    }
```

`routes/web.php`:

1. Ganti:

```php
    Route::put('trips/{trip}/pengingat', [TripReminderController::class, 'update'])->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,pengingat-trip')->name('trips.reminders.update');
    Route::post('trips/{trip}/start', [TripLifecycleController::class, 'start'])->name('trips.start');
    Route::post('trips/{trip}/complete', [TripLifecycleController::class, 'complete'])->name('trips.complete');
    Route::post('trips/{trip}/cancel', [TripLifecycleController::class, 'cancel'])->name('trips.cancel');
    Route::get('trips/{trip}/preparation', [PreparationController::class, 'show'])->name('trips.preparation');
```

dengan:

```php
    Route::put('trips/{trip}/pengingat', [TripReminderController::class, 'update'])->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,pengingat-trip')->name('trips.reminders.update');
    // Siklus trip (W6): tulisan pribadi yang sama, dengan hitungan siklus-trip sendiri.
    Route::post('trips/{trip}/start', [TripLifecycleController::class, 'start'])->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,siklus-trip')->name('trips.start');
    Route::post('trips/{trip}/complete', [TripLifecycleController::class, 'complete'])->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,siklus-trip')->name('trips.complete');
    Route::post('trips/{trip}/cancel', [TripLifecycleController::class, 'cancel'])->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,siklus-trip')->name('trips.cancel');
    Route::get('trips/{trip}/preparation', [PreparationController::class, 'show'])->name('trips.preparation');
```

- [ ] **Step 4: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Services/Trips/TripLifecycle.php app/Http/Controllers/Trips/TripLifecycleController.php app/Http/Controllers/Trips/TrackController.php app/Services/Trips/PendakianAktif.php routes/web.php database/migrations/2026_10_07_000100_add_unique_trip_plan_index_to_hiking_sessions_table.php tests/Feature/SingleActiveHikeTest.php tests/Spatial/HikingSessionUniqueTest.php`, lalu `php artisan test --compact --filter='SingleActiveHike|TripLifecycle|ActiveHikeStrip|TrackTab'`
Expected: PASS. Pada simulasi rencana: 33 test lulus.

- [ ] **Step 5: Suite, tipe, dan format.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 1.976 test, 1.974 lulus, 2 dilewati (sebelum tugas: 1.970).

Run: `npx tsc --noEmit`
Expected: keluar tanpa keluaran galat (tugas ini tidak menyentuh TypeScript).

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Pratinjau tidak dijalankan: tidak ada perubahan layar. Test Spatial melewati dirinya tanpa `SPATIAL_TEST_DSN`; ia
berjalan di PostGIS CI (Tugas 13 Step 2).

- [ ] **Step 6: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 2: Angka mode lapangan di perangkat

Spec bagian 6.12 (pembulatan jarak, naik lagi dari profil, tiba di pos ≤75 m, pos berikutnya di perangkat); induk W6
butir 2 (`FieldModeFiguresTest`); ruling W6-R5 dan W6-R17. Modul murni tanpa impor
`resources/js/components/mendaki/hitung.ts` memegang seluruh hitungan mode lapangan, sehingga halaman daring dan paket
luring memakai satu sumber, dan test PHPUnit menjalankannya di Node sebagai perilaku, bukan teks sumber. Ketinggian
GPS tidak dipakai di mana pun.

**Files:**
- Create: `resources/js/components/mendaki/hitung.ts`
- Test: `tests/Feature/FieldModeFiguresTest.php` (baru)

**Interfaces:**
- Consumes: Node 22.6 atau lebih baru di PATH (`node --experimental-strip-types`), `Symfony\Component\Process\Process`.
- Produces (diekspor `hitung.ts`): tipe `Titik` ([bujur, lintang]), `TitikProfil` ([km, meter]), `KeputusanTiba`
  (`'tiba' | 'tanya' | 'belum'`); konstanta `BATAS_MENYIMPANG_M` (150); `jarakMeter(a, b)`, `panjangGaris(garis)`,
  `proyeksikan(garis, posisi)` (`{ sepanjangM, menyimpangM } | null`), `teksJarak(meter)` ("< 50 m", kelipatan 50 m di
  bawah 1 km, 0,1 km di atasnya), `teksNaik(meter)` ("↑1.650 m"), `teksAkurasi(meter)` ("±15 m"), `teksUmur(detik)`,
  `teksLama(detik)` ("4 jam 12 menit"), `detikBergerak(mulaiMs, jedaDetik, jedaSejakMs, sekarangMs)`,
  `naikDiantara(profil, dariKm, sampaiKm)` (null tanpa profil), `kmPadaProfil(sepanjangM, panjangGarisM, profil)`,
  `keputusanTiba(jarakM, akurasiM, radiusM)`, `posBerikutnya(pos, tercapai)`, `sepanjangPos(garis, lng, lat)`,
  `sisaJarak(garis, posisi, tujuan)` (`{ meter, cara: 'jalur' | 'garis lurus' } | null`).

- [ ] **Step 1: Tulis test yang gagal.** Buat berkas test baru dengan `make:test`, lalu ganti isinya persis.

Buat `tests/Feature/FieldModeFiguresTest.php`:

```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * Angka mode lapangan (spec v3 bagian 6.12; rencana induk W6 butir 2): pembulatan jarak, sisa
 * jarak menyusuri jalur atau garis lurus, naik lagi dari profil elevasi, tiba di pos, dan pos
 * berikutnya.
 *
 * Hitungannya berjalan di perangkat (resources/js/components/mendaki/hitung.ts) supaya mode
 * lapangan tetap bekerja tanpa sinyal dan posisi tidak dikirim ke server. Test ini menjalankan
 * modul itu di Node lewat proses terpisah, jadi yang diuji perilakunya, bukan teks sumbernya.
 * Node 22.6 atau lebih baru dibutuhkan untuk membuang anotasi tipe; Node yang sama sudah
 * dibutuhkan build aset.
 */
class FieldModeFiguresTest extends TestCase
{
    public function test_distances_are_rounded_to_fifty_metres_below_a_kilometre_and_a_tenth_above(): void
    {
        $this->assertSame(
            ['< 50 m', '50 m', '950 m', '1,0 km', '1,3 km', '1,9 km', '12,3 km'],
            $this->hitung([['teksJarak', [30]], ['teksJarak', [74]], ['teksJarak', [940]], ['teksJarak', [975]], ['teksJarak', [1260]], ['teksJarak', [1949]], ['teksJarak', [12345]]])
        );
    }

    /**
     * Garis jalur berbentuk L: 1 km ke utara lalu 1 km ke timur, pos tujuan di ujungnya. Dari
     * awal garis, sisa menyusuri jalur 2,0 km; garis lurusnya hanya sekitar 1,4 km dan hanya
     * dipakai, dengan namanya, bila garis jalur tidak ada.
     */
    public function test_the_remaining_distance_follows_the_trail_and_names_a_straight_line_when_it_cannot(): void
    {
        $garis = [[110.0, -7.0], [110.0, -6.99100679], [110.00906, -6.99100679]];
        $ujung = [110.00906, -6.99100679];

        [$sepanjang, $menyusuri, $lurus] = $this->hitung([
            ['sepanjangPos', [$garis, $ujung[0], $ujung[1]]],
            ['sisaJarak', [$garis, [110.0, -7.0], ['lng' => $ujung[0], 'lat' => $ujung[1], 'sepanjangM' => 2000.0]]],
            ['sisaJarak', [null, [110.0, -7.0], ['lng' => $ujung[0], 'lat' => $ujung[1], 'sepanjangM' => null]]],
        ]);

        $this->assertEqualsWithDelta(2000, $sepanjang, 5);
        $this->assertSame('jalur', $menyusuri['cara']);
        $this->assertEqualsWithDelta(2000, $menyusuri['meter'], 1);
        $this->assertSame('garis lurus', $lurus['cara']);
        $this->assertSame(['2,0 km', '1,4 km'], $this->hitung([['teksJarak', [$menyusuri['meter']]], ['teksJarak', [$lurus['meter']]]]));
    }

    /**
     * Posisi 300 m di samping garis tidak diukur menyusuri jalur: jaraknya garis lurus, dan
     * caranya disebut.
     */
    public function test_a_position_far_from_the_line_is_measured_in_a_straight_line(): void
    {
        $garis = [[110.0, -7.0], [110.0, -6.99]];

        $hasil = $this->hitung([['sisaJarak', [$garis, [110.0027, -6.995], ['lng' => 110.0, 'lat' => -6.99, 'sepanjangM' => 1112.0]]]])[0];

        $this->assertSame('garis lurus', $hasil['cara']);
    }

    /**
     * Naik lagi dibaca dari profil elevasi, bukan dari ketinggian GPS: kenaikan dijumlahkan dan
     * turunan tidak mengurangi. Tanpa profil, naik tidak ditebak.
     */
    public function test_the_remaining_climb_comes_from_the_elevation_profile(): void
    {
        $profil = [[0, 1500], [1, 1800], [1.5, 1750], [2, 2000]];

        $this->assertSame(
            [400, 550, null, '↑1.650 m'],
            $this->hitung([['naikDiantara', [$profil, 0.5, 2]], ['naikDiantara', [$profil, 0, 2]], ['naikDiantara', [[], 0, 2]], ['teksNaik', [1650]]])
        );
        $this->assertStringNotContainsString('altitude', File::get(resource_path('js/components/mendaki/hitung.ts')));
    }

    /**
     * Garis rute dan profil dapat berasal dari rekaman yang panjangnya berbeda: letak pada garis
     * dipetakan ke profil menurut perbandingan panjang.
     */
    public function test_a_position_on_the_line_maps_onto_the_profile_by_proportion(): void
    {
        $this->assertSame([5, 2.5, null], $this->hitung([
            ['kmPadaProfil', [1000, 2000, [[0, 1500], [10, 2500]]]],
            ['kmPadaProfil', [500, 2000, [[0, 1500], [10, 2500]]]],
            ['kmPadaProfil', [500, 0, [[0, 1500], [10, 2500]]]],
        ]));
    }

    /**
     * Tiba di pos: otomatis bila jarak ditambah akurasi paling jauh 75 m; ditanya bila hanya
     * jarak dikurangi akurasi yang masuk; selain itu belum.
     */
    public function test_arrival_is_automatic_only_when_distance_plus_accuracy_is_within_the_radius(): void
    {
        $this->assertSame(
            ['tiba', 'tiba', 'tanya', 'belum'],
            $this->hitung([['keputusanTiba', [40, 20, 75]], ['keputusanTiba', [70, 5, 75]], ['keputusanTiba', [60, 20, 75]], ['keputusanTiba', [120, 20, 75]]])
        );
    }

    /**
     * Pos berikutnya adalah pos pertama menurut urutan yang belum tercapai, bukan "satu setelah
     * yang terdekat" (F-09): pendaki yang sudah mencapai Pos 2 menuju Pos 3.
     */
    public function test_the_next_checkpoint_is_the_first_one_not_yet_reached(): void
    {
        $pos = [['urutan' => 3, 'nama' => 'Pos 3'], ['urutan' => 1, 'nama' => 'Pos 1'], ['urutan' => 2, 'nama' => 'Pos 2']];

        $this->assertSame(
            [['urutan' => 1, 'nama' => 'Pos 1'], ['urutan' => 3, 'nama' => 'Pos 3'], null],
            $this->hitung([['posBerikutnya', [$pos, 0]], ['posBerikutnya', [$pos, 2]], ['posBerikutnya', [$pos, 3]]])
        );
    }

    public function test_time_and_accuracy_read_as_people_say_them(): void
    {
        $mulai = 1_000_000;

        $this->assertSame(
            ['4 jam 12 menit', '45 menit', 'baru saja', '30 detik lalu', '2 menit lalu', '±15 m', 3600],
            $this->hitung([
                ['teksLama', [15120]],
                ['teksLama', [2700]],
                ['teksUmur', [4]],
                ['teksUmur', [30]],
                ['teksUmur', [150]],
                ['teksAkurasi', [14.6]],
                ['detikBergerak', [$mulai, 1800, $mulai + 5_400_000, $mulai + 7_200_000]],
            ])
        );
    }

    /**
     * Memanggil fungsi hitung.ts di Node dan mengembalikan hasilnya berurutan.
     *
     * @param  array<int, array{0: string, 1: array<int, mixed>}>  $panggilan
     * @return array<int, mixed>
     */
    private function hitung(array $panggilan): array
    {
        $skrip = 'import { pathToFileURL } from "node:url";'
            .'const modul = await import(pathToFileURL('.json_encode(resource_path('js/components/mendaki/hitung.ts')).').href);'
            .'const panggilan = '.json_encode($panggilan).';'
            .'process.stdout.write(JSON.stringify(panggilan.map(([nama, argumen]) => modul[nama](...argumen))));';

        $proses = new Process(['node', '--experimental-strip-types', '--input-type=module', '-'], base_path(), null, $skrip, 60);
        $proses->run();

        $this->assertTrue($proses->isSuccessful(), 'Node gagal menjalankan hitung.ts: '.mb_substr($proses->getErrorOutput(), 0, 600));

        return json_decode($proses->getOutput(), true, flags: JSON_THROW_ON_ERROR);
    }
}
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='FieldModeFigures'`
Expected: FAIL (Node tidak menemukan `hitung.ts`). Pada simulasi rencana: 8 test, 0 lulus, 8 gagal. Yang merah: kedelapan test `FieldModeFiguresTest`, karena Node tidak menemukan `resources/js/components/mendaki/hitung.ts`.

- [ ] **Step 3: Tulis implementasinya.**

Buat `resources/js/components/mendaki/hitung.ts`:

```ts
/**
 * Hitungan mode lapangan di perangkat (spec v3 bagian 6.12): pos berikutnya, sisa jarak, naik
 * lagi, tiba di pos, dan teks angka. Dihitung di sini, bukan di server, karena mode lapangan
 * harus tetap berjalan tanpa sinyal dan posisi pendaki tidak dikirim ke mana pun (PRD §82,
 * §145.1). Satu sumber untuk halaman daring dan paket luring.
 *
 * Modul ini sengaja tanpa impor: FieldModeFiguresTest menjalankannya langsung di Node dari
 * PHPUnit, jadi aturan pembulatan dan tiba di pos diuji sebagai perilaku, bukan sebagai teks
 * sumber. Ketinggian GPS tidak dipakai di mana pun: naik lagi dibaca dari profil elevasi
 * jalur, karena ketinggian GPS ponsel berderau puluhan meter.
 */

/** Satu titik [bujur, lintang], urutan GeoJSON. */
export type Titik = [number, number];

/** Satu titik profil elevasi [km dari awal profil, meter]. */
export type TitikProfil = [number, number];

export type KeputusanTiba = 'tiba' | 'tanya' | 'belum';

const RADIUS_BUMI_M = 6_371_000;

/**
 * Posisi yang menyimpang lebih jauh dari ini dari garis jalur tidak diukur menyusuri jalur:
 * garisnya mungkin kurang teliti atau pendaki di luar garis, dan jarak menyusuri jalur dari
 * titik yang salah lebih menyesatkan daripada jarak garis lurus yang disebut apa adanya.
 */
export const BATAS_MENYIMPANG_M = 150;

const angkaBulat = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 });
const angkaSatuDesimal = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 });

const radian = (derajat: number) => (derajat * Math.PI) / 180;

/** Jarak permukaan bumi antara dua titik, haversine, dalam meter. */
export function jarakMeter(a: Titik, b: Titik): number {
    const dLat = radian(b[1] - a[1]);
    const dLng = radian(b[0] - a[0]);
    const h = Math.sin(dLat / 2) ** 2 + Math.cos(radian(a[1])) * Math.cos(radian(b[1])) * Math.sin(dLng / 2) ** 2;

    return RADIUS_BUMI_M * 2 * Math.atan2(Math.sqrt(h), Math.sqrt(1 - h));
}

/** Panjang garis menyusuri titik-titiknya, dalam meter. */
export function panjangGaris(garis: Titik[]): number {
    let panjang = 0;

    for (let i = 1; i < garis.length; i++) {
        panjang += jarakMeter(garis[i - 1] as Titik, garis[i] as Titik);
    }

    return panjang;
}

/**
 * Letak sebuah posisi pada garis jalur: jarak dari awal garis sampai titik terdekat di garis
 * (sepanjangM) dan jarak posisi dari garis itu (menyimpangM). Tiap ruas garis diproyeksikan di
 * bidang datar lokal; untuk ruas sepanjang jalur pendakian galatnya jauh di bawah akurasi GPS.
 */
export function proyeksikan(garis: Titik[], posisi: Titik): { sepanjangM: number; menyimpangM: number } | null {
    if (garis.length < 2) {
        return null;
    }

    let terbaik: { sepanjangM: number; menyimpangM: number } | null = null;
    let sebelumnya = 0;

    for (let i = 1; i < garis.length; i++) {
        const a = garis[i - 1] as Titik;
        const b = garis[i] as Titik;
        const skalaX = Math.cos(radian((a[1] + b[1]) / 2)) * radian(1) * RADIUS_BUMI_M;
        const skalaY = radian(1) * RADIUS_BUMI_M;
        const bx = (b[0] - a[0]) * skalaX;
        const by = (b[1] - a[1]) * skalaY;
        const px = (posisi[0] - a[0]) * skalaX;
        const py = (posisi[1] - a[1]) * skalaY;
        const kuadrat = bx * bx + by * by;
        const t = kuadrat === 0 ? 0 : Math.min(1, Math.max(0, (px * bx + py * by) / kuadrat));
        const menyimpangM = Math.hypot(px - t * bx, py - t * by);
        const ruasM = jarakMeter(a, b);

        if (terbaik === null || menyimpangM < terbaik.menyimpangM) {
            terbaik = { sepanjangM: sebelumnya + t * ruasM, menyimpangM };
        }

        sebelumnya += ruasM;
    }

    return terbaik;
}

/**
 * Jarak dibulatkan supaya tidak terbaca lebih pasti dari GPS-nya (R-005): kelipatan 50 m di
 * bawah 1 km, 0,1 km mulai 1 km (spec v3 bagian 6.12). Di bawah 50 m tidak ada angka.
 */
export function teksJarak(meter: number): string {
    if (meter < 50) {
        return '< 50 m';
    }

    const dibulatkan = Math.round(meter / 50) * 50;

    if (dibulatkan < 1000) {
        return `${angkaBulat.format(dibulatkan)} m`;
    }

    return `${angkaSatuDesimal.format(Math.round(meter / 100) / 10)} km`;
}

/** Naik dalam meter dengan tanda panah, angka Indonesia ("↑1.650 m"). */
export function teksNaik(meter: number): string {
    return `↑${angkaBulat.format(Math.round(meter))} m`;
}

/** Akurasi posisi ("±15 m"). */
export function teksAkurasi(meter: number): string {
    return `±${angkaBulat.format(Math.round(meter))} m`;
}

/** Umur posisi terakhir ("30 detik lalu"). */
export function teksUmur(detik: number): string {
    const utuh = Math.max(0, Math.floor(detik));

    if (utuh < 10) {
        return 'baru saja';
    }

    if (utuh < 60) {
        return `${utuh} detik lalu`;
    }

    if (utuh < 3600) {
        return `${Math.floor(utuh / 60)} menit lalu`;
    }

    return `${Math.floor(utuh / 3600)} jam lalu`;
}

/** Lama dalam jam dan menit, bentuk Durasi::panjang di server ("4 jam 12 menit"). */
export function teksLama(detik: number): string {
    const menit = Math.max(0, Math.floor(detik / 60));
    const jam = Math.floor(menit / 60);
    const sisa = menit % 60;

    if (jam === 0) {
        return `${sisa} menit`;
    }

    return sisa === 0 ? `${jam} jam` : `${jam} jam ${sisa} menit`;
}

/**
 * Detik bergerak sejak mulai: waktu sejak sesi dimulai, dikurangi jeda yang sudah selesai dan
 * jeda yang sedang berjalan. Jam perangkat yang tertinggal tidak menghasilkan waktu negatif.
 */
export function detikBergerak(mulaiMs: number, jedaDetik: number, jedaSejakMs: number | null, sekarangMs: number): number {
    const jedaBerjalan = jedaSejakMs === null ? 0 : Math.max(0, (sekarangMs - jedaSejakMs) / 1000);

    return Math.max(0, (sekarangMs - mulaiMs) / 1000 - jedaDetik - jedaBerjalan);
}

function tinggiPada(profil: TitikProfil[], km: number): number {
    for (let i = 0; i < profil.length; i++) {
        const [x, y] = profil[i] as TitikProfil;

        if (x >= km) {
            if (i === 0) {
                return y;
            }

            const [x0, y0] = profil[i - 1] as TitikProfil;

            return x === x0 ? y : y0 + ((y - y0) * (km - x0)) / (x - x0);
        }
    }

    return (profil[profil.length - 1] as TitikProfil)[1];
}

/**
 * Naik yang tersisa dari satu jarak ke jarak lain pada profil elevasi: jumlah kenaikan saja,
 * turunan tidak mengurangi. Jarak di luar rentang profil dijepit ke ujungnya. Null bila profil
 * tidak dapat dibaca (R-007): tanpa profil, naik tidak ditebak.
 */
export function naikDiantara(profil: TitikProfil[], dariKm: number, sampaiKm: number): number | null {
    if (profil.length < 2) {
        return null;
    }

    const awal = (profil[0] as TitikProfil)[0];
    const akhir = (profil[profil.length - 1] as TitikProfil)[0];
    const jepit = (km: number) => Math.min(akhir, Math.max(awal, km));
    const dari = jepit(dariKm);
    const sampai = jepit(sampaiKm);
    const maju = sampai >= dari;
    const tengah = profil.filter(([km]) => (maju ? km > dari && km < sampai : km < dari && km > sampai)).map(([, m]) => m);
    const tinggi = [tinggiPada(profil, dari), ...(maju ? tengah : tengah.reverse()), tinggiPada(profil, sampai)];
    let naik = 0;

    for (let i = 1; i < tinggi.length; i++) {
        naik += Math.max(0, (tinggi[i] as number) - (tinggi[i - 1] as number));
    }

    return Math.round(naik);
}

/**
 * Jarak menyusuri garis jalur dipetakan ke sumbu profil elevasi menurut perbandingan panjangnya:
 * garis rute dan profil dapat berasal dari rekaman yang panjangnya berbeda (T6-M9).
 */
export function kmPadaProfil(sepanjangM: number, panjangGarisM: number, profil: TitikProfil[]): number | null {
    if (panjangGarisM <= 0 || profil.length < 2) {
        return null;
    }

    const awal = (profil[0] as TitikProfil)[0];
    const akhir = (profil[profil.length - 1] as TitikProfil)[0];

    return awal + (Math.min(sepanjangM, panjangGarisM) / panjangGarisM) * (akhir - awal);
}

/**
 * Tiba di pos (spec v3 bagian 6.12): otomatis bila jarak ditambah akurasi masih di dalam radius;
 * bila hanya jarak dikurangi akurasi yang masuk radius, posisinya mungkin sudah di pos dan
 * pendaki ditanya; selain itu belum.
 */
export function keputusanTiba(jarakM: number, akurasiM: number, radiusM: number): KeputusanTiba {
    if (jarakM + akurasiM <= radiusM) {
        return 'tiba';
    }

    return jarakM - akurasiM <= radiusM ? 'tanya' : 'belum';
}

/** Pos berikutnya: pos pertama menurut urutan yang belum tercapai; null bila semua tercapai. */
export function posBerikutnya<T extends { urutan: number }>(pos: T[], tercapai: number): T | null {
    return [...pos].sort((a, b) => a.urutan - b.urutan).find((p) => p.urutan > tercapai) ?? null;
}

/**
 * Jarak menyusuri jalur setiap pos dari awal garis, dalam meter, bila posnya berkoordinat dan
 * cukup dekat dengan garis; null bila tidak.
 */
export function sepanjangPos(garis: Titik[] | null, lng: number | null, lat: number | null): number | null {
    if (garis === null || lng === null || lat === null) {
        return null;
    }

    const letak = proyeksikan(garis, [lng, lat]);

    return letak !== null && letak.menyimpangM <= BATAS_MENYIMPANG_M ? letak.sepanjangM : null;
}

/**
 * Sisa jarak ke pos tujuan: menyusuri jalur bila garis dan letak pos di garis diketahui dan
 * posisi berada dekat garis, selain itu garis lurus, dan caranya selalu disebut.
 */
export function sisaJarak(
    garis: Titik[] | null,
    posisi: Titik,
    tujuan: { lng: number | null; lat: number | null; sepanjangM: number | null },
): { meter: number; cara: 'jalur' | 'garis lurus' } | null {
    if (garis !== null && tujuan.sepanjangM !== null) {
        const letak = proyeksikan(garis, posisi);

        if (letak !== null && letak.menyimpangM <= BATAS_MENYIMPANG_M && tujuan.sepanjangM >= letak.sepanjangM) {
            return { meter: tujuan.sepanjangM - letak.sepanjangM, cara: 'jalur' };
        }
    }

    if (tujuan.lng !== null && tujuan.lat !== null) {
        return { meter: jarakMeter(posisi, [tujuan.lng, tujuan.lat]), cara: 'garis lurus' };
    }

    return null;
}
```

- [ ] **Step 4: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent tests/Feature/FieldModeFiguresTest.php`, lalu `php artisan test --compact --filter='FieldModeFigures'`
Expected: PASS. Pada simulasi rencana: 8 test lulus.

- [ ] **Step 5: Suite, tipe, dan format.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 1.984 test, 1.982 lulus, 2 dilewati (sebelum tugas: 1.976).

Run: `npx tsc --noEmit`
Expected: keluar tanpa keluaran galat.

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Pratinjau tidak dijalankan: modul belum dipakai halaman mana pun sampai Tugas 3.

- [ ] **Step 6: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 3: Mode lapangan

Spec bagian 6.12 (prototype 11), DK-49; induk W6 butir 2; ruling W6-R5 sampai W6-R7 dan W6-R18. Server mengirim data
jalur yang stabil (`HalamanMendaki`: pos berurutan dengan koordinat dan elevasi, garis jalur, profil yang dijarangkan,
perkiraan per ruas dari rekaman pendaki, laporan komunitas per segmen, keadaan sesi) dan menerima tiga aksi tanpa
posisi: tiba di pos (urutan), jeda, dan lanjut. Ketiganya UPDATE bersyarat, jadi antrean perangkat dapat memutarnya
ulang tanpa memundurkan kemajuan. Rute posisi dihapus. Halaman React ditulis ulang: peta tanpa kontrol bawaan dengan
garis yang sudah dilalui dan sisa, pos bernomor, lingkaran akurasi; nama pos berikutnya besar; tiga petak (sisa jarak,
naik lagi, perkiraan) yang menulis "Belum ada" bila tidak diketahui; akurasi dan umur posisi dengan peringatan di atas
50 m; tiba otomatis bila jarak ditambah akurasi ≤ radius dan bertanya bila tidak; laporan komunitas segmen ini; sakelar
layar menyala (mati bawaan); keadaan luring dan antrean; Jeda atau Lanjut 60 px. Tema lapangan dipasang di akar halaman,
dan peredupan peta dasar serta warna garis peta ikut berganti saat tema lapangan dibalik.

**Files:**
- Create: `app/Services/Trips/HalamanMendaki.php`, `app/Http/Requests/Trips/RecordArrivalRequest.php`, `app/Http/Requests/Trips/PauseHikeRequest.php`, `database/migrations/2026_10_07_000200_add_pause_to_hiking_sessions_table.php`, `resources/js/components/mendaki/types.ts`, `resources/js/components/mendaki/antrean.ts`, `resources/js/components/mendaki/usePosisiLapangan.ts`, `resources/js/components/mendaki/useLayarMenyala.ts`, `resources/js/components/mendaki/useModeLapangan.ts`, `resources/js/components/mendaki/ModeLapangan.tsx`
- Modify: `app/Http/Controllers/Trips/HikeModeController.php` (ditulis ulang utuh), `app/Services/Trips/HikeCheckpointResolver.php` (ditulis ulang utuh), `app/Models/HikingSession.php`, `app/Services/Trips/TripLifecycle.php`, `routes/web.php`, `resources/css/app.css`, `resources/js/components/mendaki/hitung.ts`, `resources/js/components/peta/usePetaDasar.ts`, `resources/js/components/peta/warnaToken.ts`, `resources/js/components/trips/PetaPendakian.tsx` (ditulis ulang utuh), `resources/js/components/trips/TataLetakPendakian.tsx` (ditulis ulang utuh), `resources/js/components/trips/usePendakian.ts`, `resources/js/pages/Trips/Hike.tsx` (ditulis ulang utuh)
- Delete: `app/Http/Requests/Trips/UpdateHikePositionRequest.php`
- Test: `tests/Feature/HikeModeCheckpointTest.php` (ditulis ulang utuh), `tests/Feature/FieldModeFiguresTest.php`, `tests/Feature/LocationPrivacyTest.php`, `tests/Feature/MapConfigTest.php`, `tests/Feature/TripOwnershipTest.php`, `tests/Feature/TripReactPagesTest.php`, `tests/Pratinjau/HalamanPratinjauTest.php`

**Interfaces:**
- Consumes: `hitung.ts` (Tugas 2); `CheckpointPaceService::forTrail(Trail): array` (rekaman per ruas `dari`, `ke`, `rekaman`, `min_menit`, `maks_menit`); `CheckpointJourney::aksen()`; `SkalaProfil::titik()`; `TrailConditionReport` scope `visibleToPublic()`, `dalamJendelaTerbaru()`, `tags()`, `daysSinceHike()`; `Trail::readGeoJson('geometry')`; `usePetaDasar` dan `warnaToken`; primitif `Sakelar`, `Icon`, `Tautan`, `LencanaDataContoh`, `JudulHalaman`.
- Produces:
  - `HalamanMendaki::untuk(TripPlan $trip, User $pengguna): array` dengan kunci `trip` (`id`, `nama`, `jalur`, `gunung`, `berlangsung`, `mulai`, `jedaSejak`, `jedaDetik`), `pos`, `garis`, `profil`, `ruas`, `tercapai`, `radiusTiba`, `laporan`, `rekamJejak`, `sesiId`, `sumber` (`'daring'`), `urls` (`tiba`, `jeda`, `lanjut`, `ruangKerja`, `jejak`); `HalamanMendaki::perkiraan(int $tercepat, int $terlama): string`;
  - `HikeCheckpointResolver::checkpoints(Trail): array`, `current(?HikingSession, array): ?array`, `tiba(HikingSession, array, int $urutan): void`;
  - `HikingSession::detikBergerak(?CarbonInterface $sekarang = null): ?int`; kolom `paused_at`, `paused_seconds`;
  - rute `trips.hike.arrive` (POST `trips/{trip}/hike/tiba`, `urutan`), `trips.hike.pause` (POST `trips/{trip}/hike/jeda`, `pada` opsional), `trips.hike.resume` (POST `trips/{trip}/hike/lanjut`), ketiganya 204 dan berbatas laju `mode-lapangan`; rute `trips.hike.position` dihapus;
  - TypeScript `DataLapangan`, `PosLapangan`, `RuasLapangan`, `LaporanLapangan`, `PosisiPerangkat` (`types.ts`); `kirimAksi()`, `putarAntrean()`, `dengarAntrean()`, `pakaiPenyimpanan()`, `AksiAntrean`, `PenyimpananAntrean` (`antrean.ts`); `usePosisiLapangan(aktif)`, `useLayarMenyala()`, `useModeLapangan(data): ModeLapangan`; komponen `ModeLapangan({ data, mode, selesai })`, `PetaPendakian`, `TataLetakPendakian({ kembali, judul, keterangan, children })`; `ikutkanTemaPetaDasar(peta, gelap)` (`usePetaDasar.ts`, dipakai saat peta dimuat dan saat tema lapangan dibalik); `useJejakPendakian(aktif, sesiId, urlKirim)`; kelas `denyut-tiba` di `app.css`.

- [ ] **Step 1: Tulis test yang gagal.** Test lama yang diperbarui membawa alasannya: `HikeModeCheckpointTest` ditulis
ulang karena posisi tidak lagi dikirim (niatnya tetap: kemajuan tercatat, pos di luar jalur ditolak, pos tanpa koordinat
diakui, pengguna lain ditolak); `LocationPrivacyTest`, `MapConfigTest`, `TripOwnershipTest`, dan `TripReactPagesTest`
mengikuti nama kait dan rute baru. `MapConfigTest` mendapat satu test baru: peta lapangan mengikuti tema lapangan
yang dibalik, bukan hanya tema aplikasi saat peta dimuat.

`tests/Feature/FieldModeFiguresTest.php`:

1. Ganti:

```php
    }

    public function test_time_and_accuracy_read_as_people_say_them(): void
```

dengan:

```php
    }

    /**
     * Peta mode lapangan membelah garis pada letak yang sudah dilalui: bagian dilalui berakhir
     * dan bagian sisa bermula di titik belah yang sama, jadi garisnya tidak terputus.
     */
    public function test_the_line_splits_where_the_hiker_has_reached(): void
    {
        $garis = [[110.0, -7.0], [110.0, -6.99]];

        [$tengah, $awal] = $this->hitung([['potongGaris', [$garis, 556]], ['potongGaris', [$garis, 0]]]);

        $this->assertCount(2, $tengah['dilalui']);
        $this->assertSame($tengah['dilalui'][1], $tengah['sisa'][0]);
        $this->assertEqualsWithDelta(-6.995, $tengah['sisa'][0][1], 0.0001);
        $this->assertSame([], $awal['dilalui']);
        $this->assertEquals($garis, $awal['sisa']);
    }

    public function test_time_and_accuracy_read_as_people_say_them(): void
```

Tulis ulang `tests/Feature/HikeModeCheckpointTest.php` utuh:

```php
<?php

namespace Tests\Feature;

use App\Enums\HikingSessionStatus;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Models\Checkpoint;
use App\Models\HikeTrackPoint;
use App\Models\HikingSession;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\Trips\HalamanMendaki;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Mode lapangan di server (spec v3 bagian 6.12; ruling W6-R5 sampai W6-R7).
 *
 * Sejak W6 posisi pendaki tidak dikirim ke server (PRD §82, §145.1): pos berikutnya, jarak,
 * dan tiba di pos dihitung di perangkat (FieldModeFiguresTest). Niat test lama tetap dijaga di
 * sini pada bentuk barunya: pos yang dicapai tercatat dan tidak pernah mundur (F-09, F-17),
 * masukan di luar jalur ditolak, pos tanpa koordinat diakui apa adanya, dan pengguna lain
 * ditolak. Rute posisi lama trips.hike.position dihapus beserta permintaannya.
 */
class HikeModeCheckpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_page_carries_the_trail_data_and_no_position(): void
    {
        $trip = $this->tripWithCheckpoints();

        $this->actingAs($trip->user)->get(route('trips.hike', $trip))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Trips/Hike')
                ->where('title', 'Mode lapangan')
                ->where('trip.berlangsung', true)
                ->where('trip.jedaDetik', 0)
                ->has('pos', 4)
                ->where('pos.2.urutan', 3)
                ->where('pos.2.nama', 'Pos 3')
                ->where('pos.2.lat', -7.993)
                ->where('tercapai', 0)
                ->where('radiusTiba', 75)
                ->where('sumber', 'daring')
                ->where('urls.tiba', route('trips.hike.arrive', $trip))
                ->missing('posisi')
                ->missing('berikutnya'));
    }

    public function test_arriving_records_the_checkpoint_and_aims_at_the_next(): void
    {
        $trip = $this->tripWithCheckpoints();

        $this->tiba($trip, 3)->assertNoContent();

        $sesi = $trip->hikingSession->fresh();

        $this->assertSame(3, $sesi->reached_checkpoint_sequence);
        $this->assertSame('Pos 4', $sesi->currentCheckpoint->name);
    }

    /**
     * Antrean perangkat dapat memutar ulang tiba di pos yang lebih awal sesudah yang lebih
     * akhir tercatat. Pos yang sudah dicapai tidak menjadi belum dicapai.
     */
    public function test_progress_is_not_lost_when_an_earlier_arrival_is_replayed(): void
    {
        $trip = $this->tripWithCheckpoints();

        $this->tiba($trip, 3);
        $this->tiba($trip, 1)->assertNoContent();

        $this->assertSame(3, $trip->hikingSession->fresh()->reached_checkpoint_sequence);
    }

    public function test_a_reload_keeps_the_recorded_progress(): void
    {
        $trip = $this->tripWithCheckpoints();
        $this->tiba($trip, 2);

        $this->get(route('trips.hike', $trip))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('tercapai', 2));
    }

    public function test_a_checkpoint_outside_the_trail_is_rejected(): void
    {
        $trip = $this->tripWithCheckpoints();
        $lain = Checkpoint::factory()->create(['sequence' => 9]);

        $this->tiba($trip, 9)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['urutan' => 'Pos itu tidak ada di jalur trip ini.']);

        $this->assertNull($trip->hikingSession->fresh()->reached_checkpoint_sequence);
        $this->assertNotSame($trip->trail_id, $lain->trail_id);
    }

    /**
     * Tanpa koordinat pos, data apa adanya: koordinatnya null dan perangkat tidak menebak
     * jarak (hitung.ts menulis "pos tanpa koordinat"). Tiba tetap dapat ditandai sendiri.
     */
    public function test_checkpoints_without_coordinates_are_admitted_not_guessed(): void
    {
        $trip = $this->tripWithCheckpoints(berkoordinat: false);

        $this->actingAs($trip->user)->get(route('trips.hike', $trip))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('pos.0.lat', null)
                ->where('pos.0.lng', null)
                ->where('garis', null));

        $this->tiba($trip, 1)->assertNoContent();
    }

    public function test_another_user_cannot_record_an_arrival(): void
    {
        $trip = $this->tripWithCheckpoints();

        $this->actingAs(User::factory()->create())
            ->postJson(route('trips.hike.arrive', $trip), ['urutan' => 2])
            ->assertForbidden();

        $this->assertNull($trip->hikingSession->fresh()->reached_checkpoint_sequence);
    }

    /**
     * Posisi tidak pernah sampai ke server lagi: rutenya tidak ada, dan tiba di pos tidak
     * menyentuh kolom lokasi sesi.
     */
    public function test_no_position_reaches_the_server(): void
    {
        $trip = $this->tripWithCheckpoints();

        $this->assertFalse(Route::has('trips.hike.position'));

        $this->actingAs($trip->user)
            ->postJson(route('trips.hike.arrive', $trip), ['urutan' => 2, 'latitude' => -7.99, 'longitude' => 112.95])
            ->assertNoContent();

        $this->assertNull($trip->hikingSession->fresh()->location_updated_at);
    }

    /**
     * Aksi lapangan hanya untuk pendakian yang berlangsung; antrean perangkat membuang aksi
     * yang ditolak 409 dan menyebut alasannya.
     */
    public function test_field_actions_only_apply_to_a_hike_in_progress(): void
    {
        $trip = $this->tripWithCheckpoints();
        $trip->update(['status' => TripStatus::COMPLETED->value]);

        $this->tiba($trip, 2)->assertStatus(409);
        $this->actingAs($trip->user)->postJson(route('trips.hike.pause', $trip))->assertStatus(409);
    }

    public function test_a_finished_trip_opens_its_summary_or_workspace_instead(): void
    {
        $selesai = $this->tripWithCheckpoints();
        $selesai->update(['status' => TripStatus::COMPLETED->value]);
        $batal = TripPlan::factory()->for($selesai->user)->create(['status' => TripStatus::CANCELLED->value]);

        $this->actingAs($selesai->user)->get(route('trips.hike', $selesai))->assertRedirect(route('history.summary', $selesai));
        $this->actingAs($selesai->user)->get(route('trips.hike', $batal))->assertRedirect(route('trips.show', $batal));
    }

    /**
     * Waktu bergerak (ruling W6-R7): jeda 30 menit tidak dihitung, dan menyelesaikan trip saat
     * jeda masih berjalan menutup jedanya.
     */
    public function test_pausing_and_resuming_count_only_moving_time(): void
    {
        $this->travelTo(Carbon::parse('2026-10-10 00:00:00', 'UTC'));
        $trip = $this->tripWithCheckpoints();

        $this->travelTo(Carbon::parse('2026-10-10 01:00:00', 'UTC'));
        $this->actingAs($trip->user)->postJson(route('trips.hike.pause', $trip))->assertNoContent();
        $this->assertSame(HikingSessionStatus::PAUSED, $trip->hikingSession->fresh()->status);

        $this->travelTo(Carbon::parse('2026-10-10 01:30:00', 'UTC'));
        $this->actingAs($trip->user)->postJson(route('trips.hike.resume', $trip))->assertNoContent();
        $this->assertSame(1800, $trip->hikingSession->fresh()->paused_seconds);

        $this->travelTo(Carbon::parse('2026-10-10 02:00:00', 'UTC'));
        $this->actingAs($trip->user)->postJson(route('trips.hike.pause', $trip))->assertNoContent();

        $this->travelTo(Carbon::parse('2026-10-10 02:10:00', 'UTC'));
        $this->actingAs($trip->user)->post(route('trips.complete', $trip), ['completion_state' => 'COMPLETED']);

        $sesi = $trip->hikingSession->fresh();

        $this->assertNull($sesi->paused_at);
        $this->assertSame(2400, $sesi->paused_seconds);
        $this->assertSame(5400, $sesi->detikBergerak());
    }

    /**
     * Jeda yang diputar ulang antrean memakai waktu ketukan di perangkat, dijepit di antara
     * mulai sesi dan sekarang; jeda yang sudah berjalan tidak dimulai ulang.
     */
    public function test_a_replayed_pause_uses_the_device_time_and_is_not_restarted(): void
    {
        $this->travelTo(Carbon::parse('2026-10-10 00:00:00', 'UTC'));
        $trip = $this->tripWithCheckpoints();
        $this->travelTo(Carbon::parse('2026-10-10 03:00:00', 'UTC'));

        $this->actingAs($trip->user)->postJson(route('trips.hike.pause', $trip), ['pada' => '2026-10-10T01:00:00Z'])->assertNoContent();
        $this->actingAs($trip->user)->postJson(route('trips.hike.pause', $trip), ['pada' => '2026-10-10T02:00:00Z'])->assertNoContent();
        $this->assertSame('2026-10-10 01:00:00', $trip->hikingSession->fresh()->paused_at->utc()->format('Y-m-d H:i:s'));

        $this->actingAs($trip->user)->postJson(route('trips.hike.resume', $trip), ['pada' => '2026-10-11T00:00:00Z'])->assertNoContent();
        $this->assertSame(7200, $trip->hikingSession->fresh()->paused_seconds, 'Waktu di masa depan dijepit ke sekarang.');

        $this->actingAs($trip->user)->postJson(route('trips.hike.pause', $trip), ['pada' => 'bukan-waktu'])->assertUnprocessable();
    }

    public function test_field_actions_are_rate_limited(): void
    {
        $trip = $this->tripWithCheckpoints();
        $batas = (int) config('hiking.rate_limits.personal_writes_per_minute');

        foreach (range(1, $batas) as $i) {
            $this->tiba($trip, 1)->assertNoContent();
        }

        $this->tiba($trip, 1)->assertStatus(429);
    }

    /**
     * Perkiraan per ruas adalah rentang teramati dari paling sedikit lima rekaman (DK-31);
     * tanpa itu null, dan perangkat menulis "belum ada data waktu tempuh".
     */
    public function test_the_segment_estimate_is_a_sourced_range_or_names_its_absence(): void
    {
        $trip = $this->tripWithCheckpoints();
        [$pos1, $pos2] = $trip->trail->checkpoints->sortBy('sequence')->values()->all();

        foreach ([40, 50, 60, 70, 80] as $menit) {
            $this->rekamRuas($trip->trail, $pos1, $pos2, $menit);
        }

        $this->actingAs($trip->user)->get(route('trips.hike', $trip))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('ruas.0', ['ke' => 2, 'perkiraan' => '40–80 menit', 'rekaman' => 5])
                ->where('ruas.1', ['ke' => 3, 'perkiraan' => null, 'rekaman' => null]));

        $this->assertSame(['45 menit', '1,5–2,5 jam', '2,5–3 jam', '3 jam'], [
            HalamanMendaki::perkiraan(45, 45),
            HalamanMendaki::perkiraan(90, 150),
            HalamanMendaki::perkiraan(150, 180),
            HalamanMendaki::perkiraan(170, 190),
        ]);
    }

    private function tiba(TripPlan $trip, int $urutan): TestResponse
    {
        return $this->actingAs($trip->user)->postJson(route('trips.hike.arrive', $trip), ['urutan' => $urutan]);
    }

    /**
     * Satu pendakian pendaki lain yang lewat $dari lalu $ke sekian menit kemudian
     * (pola CheckpointPaceTest).
     */
    private function rekamRuas(Trail $jalur, Checkpoint $dari, Checkpoint $ke, int $menit): void
    {
        $trip = TripPlan::factory()->for($jalur)->create();
        $sesi = HikingSession::create(['trip_plan_id' => $trip->id, 'user_id' => $trip->user_id, 'status' => HikingSessionStatus::COMPLETED->value, 'started_at' => Carbon::parse('2026-09-15 04:00:00')]);
        $mulai = Carbon::parse('2026-09-15 05:00:00');

        HikeTrackPoint::create(['hiking_session_id' => $sesi->id, 'recorded_at' => $mulai, 'latitude' => $dari->latitude, 'longitude' => $dari->longitude]);
        HikeTrackPoint::create(['hiking_session_id' => $sesi->id, 'recorded_at' => $mulai->copy()->addMinutes($menit), 'latitude' => $ke->latitude, 'longitude' => $ke->longitude]);
    }

    private function tripWithCheckpoints(bool $berkoordinat = true): TripPlan
    {
        $user = User::factory()->create();
        $mountain = Mountain::factory()->create();
        $trail = Trail::factory()->for($mountain)->create();

        // Empat pos berjajar ke utara, berjarak sekitar 220 m antar pos.
        foreach ([1 => -7.9970, 2 => -7.9950, 3 => -7.9930, 4 => -7.9910] as $sequence => $latitude) {
            Checkpoint::factory()->for($trail)->create([
                'sequence' => $sequence,
                'name' => 'Pos '.$sequence,
                'latitude' => $berkoordinat ? $latitude : null,
                'longitude' => $berkoordinat ? 112.9500 : null,
            ]);
        }

        $trip = TripPlan::create([
            'user_id' => $user->id,
            'trail_id' => $trail->id,
            'name' => 'Uji mode lapangan',
            'planned_date' => now()->toDateString(),
            'trip_type' => TripType::TEKTOK->value,
            'status' => TripStatus::IN_PROGRESS->value,
        ]);

        $trip->hikingSession()->create([
            'user_id' => $user->id,
            'status' => HikingSessionStatus::ACTIVE->value,
            'started_at' => now(),
        ]);

        return $trip->fresh();
    }
}
```

`tests/Feature/LocationPrivacyTest.php`:

1. Ganti:

```php
        $trip = TripPlan::factory()->create();

        // Mode pendakian kini React: permintaan lokasinya ada di hook yang hanya
        // dipakai halaman Trips/Hike.
        $this->actingAs($trip->user)
```

dengan:

```php
        $trip = TripPlan::factory()->create();

        // Mode lapangan W6: permintaan lokasinya ada di hook mode lapangan, yang hanya
        // dipakai keadaan mode lapangan di halaman Trips/Hike, dan posisinya tidak dikirim.
        $this->actingAs($trip->user)
```

2. Ganti:

```php
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Trips/Hike'));

        $this->assertStringContainsString('navigator.geolocation', File::get(resource_path('js/components/trips/usePendakian.ts')));
        $this->assertStringContainsString('usePosisiPendakian', File::get(resource_path('js/pages/Trips/Hike.tsx')));
    }
```

dengan:

```php
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Trips/Hike'));

        $hook = File::get(resource_path('js/components/mendaki/usePosisiLapangan.ts'));

        $this->assertStringContainsString('navigator.geolocation', $hook);
        $this->assertStringNotContainsString('fetch(', $hook);
        $this->assertStringContainsString('usePosisiLapangan(', File::get(resource_path('js/components/mendaki/useModeLapangan.ts')));
        $this->assertStringContainsString('useModeLapangan(data)', File::get(resource_path('js/pages/Trips/Hike.tsx')));
    }
```

3. Ganti:

```php
            $nama = str_replace('\\', '/', $berkas->getRelativePathname());

            if (in_array($nama, ['components/trips/usePendakian.ts', 'jejak.js'], true)) {
                continue;
```

dengan:

```php
            $nama = str_replace('\\', '/', $berkas->getRelativePathname());

            if (in_array($nama, ['components/mendaki/usePosisiLapangan.ts', 'jejak.js'], true)) {
                continue;
```

`tests/Feature/MapConfigTest.php`:

1. Ganti:

```php
        // Pendaki yang tidak dapat melihat peta tetap harus tahu urutan posnya.
        $this->hikeModeResponse()->assertInertia(fn (AssertableInertia $page) => $page
            ->where('checkpoints.0.name', 'Pos 1'));
        $this->assertStringContainsString('Daftar checkpoint', File::get(resource_path('js/pages/Trips/Hike.tsx')));
    }
```

dengan:

```php
        // Pendaki yang tidak dapat melihat peta tetap harus tahu urutan posnya.
        // W6: pos dikirim sebagai `pos`, dan daftar "Semua pos" mode lapangan adalah padanan
        // non-visual peta.
        $this->hikeModeResponse()->assertInertia(fn (AssertableInertia $page) => $page
            ->where('pos.0.nama', 'Pos 1'));
        $this->assertStringContainsString('Semua pos', File::get(resource_path('js/components/mendaki/ModeLapangan.tsx')));
    }
```

2. Ganti:

```php
        $this->assertStringContainsString('role="region"', $wadah);
        $this->assertStringNotContainsString('role="img"', $wadah);
    }
```

dengan:

```php
        $this->assertStringContainsString('role="region"', $wadah);
        $this->assertStringNotContainsString('role="img"', $wadah);
    }

    /**
     * Tema lapangan dapat dibalik di jalur (DK-49), dan peta dasar topografi diredupkan di tema
     * gelap (DK-18). Peta mode lapangan mengikuti atribut akar tema lapangan, bukan hanya tema
     * aplikasi saat peta dimuat: tanpa itu peta tetap terang di tema lapangan gelap pada malam hari.
     */
    public function test_the_field_map_follows_the_field_theme_toggle(): void
    {
        $peta = File::get(resource_path('js/components/trips/PetaPendakian.tsx'));
        $dasar = File::get(resource_path('js/components/peta/usePetaDasar.ts'));

        $this->assertStringContainsString("attributeFilter: ['data-tema-lapangan']", $peta);
        $this->assertStringContainsString("ikutkanTemaPetaDasar(map, akar.dataset.temaLapangan === 'gelap')", $peta);
        $this->assertStringContainsString('ikutkanTemaPetaDasar(petaIni, window.temaGelapAktif?.() ?? false)', $dasar);
        $this->assertStringContainsString("'raster-brightness-max', gelap ? 0.78 : 1", $dasar);
    }
```

`tests/Feature/TripOwnershipTest.php`:

1. Ganti:

```php
            route('trips.readiness.confirm', $trip) => [],
            route('trips.hike.position', $trip) => ['latitude' => -7.99, 'longitude' => 112.95],
        ];
```

dengan:

```php
            route('trips.readiness.confirm', $trip) => [],
            // W6: rute posisi lama dihapus; aksi mode lapangan menggantikannya.
            route('trips.hike.arrive', $trip) => ['urutan' => 1],
            route('trips.hike.pause', $trip) => [],
            route('trips.hike.resume', $trip) => [],
        ];
```

`tests/Feature/TripReactPagesTest.php`:

1. Ganti:

```php
            route('trips.readiness', $trip) => ['Trips/Readiness', 'Kesiapan Pendakian'],
            route('trips.hike', $trip) => ['Trips/Hike', 'Hike Mode'],
        ];
```

dengan:

```php
            route('trips.readiness', $trip) => ['Trips/Readiness', 'Kesiapan Pendakian'],
            route('trips.hike', $trip) => ['Trips/Hike', 'Mode lapangan'],
        ];
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='FieldModeFigures|HikeModeCheckpoint|LocationPrivacy|MapConfig|TripOwnership|TripReactPages'`
Expected: FAIL. Pada simulasi rencana: 56 test, 35 lulus, 10 gagal, 11 error. Yang merah: 14 test `HikeModeCheckpointTest` (rute `trips.hike.arrive`, `pause`, dan `resume` belum ada; halaman masih membawa posisi dan judul lama), `FieldModeFiguresTest::test_the_line_splits_where_the_hiker_has_reached` (`potongGaris` belum ada), dua test `LocationPrivacyTest` (geolokasi masih diminta `usePendakian.ts`; `usePosisiLapangan.ts` belum ada), dua test `MapConfigTest` (prop `pos` belum ada; peta lapangan belum mengikuti tema lapangan yang dibalik), `TripReactPagesTest` (judul "Mode lapangan"), dan `TripOwnershipTest` (rute aksi lapangan belum ada).

- [ ] **Step 3: Tulis implementasinya.** Buat kelas dan request dengan
`DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:class --no-interaction Services/Trips/HalamanMendaki`,
`... make:request --no-interaction Trips/RecordArrivalRequest`, `... make:request --no-interaction Trips/PauseHikeRequest`,
dan migrasi dengan `... make:migration --no-interaction add_pause_to_hiking_sessions_table` (ganti nama berkasnya), lalu
isinya diganti persis. Berkas TypeScript dibuat langsung.

Buat `app/Http/Requests/Trips/PauseHikeRequest.php`:

```php
<?php

namespace App\Http\Requests\Trips;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Jeda dan Lanjut (ruling W6-R7). `pada` hanya dikirim antrean perangkat yang memutar ulang
 * aksi sesudah sinyal kembali: waktu ketukan di perangkat, dijepit server di antara mulai sesi
 * dan sekarang.
 */
class PauseHikeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('trip')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pada' => ['nullable', 'date'],
        ];
    }
}
```

Buat `app/Http/Requests/Trips/RecordArrivalRequest.php`:

```php
<?php

namespace App\Http\Requests\Trips;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Tiba di pos: hanya urutan pos jalur trip ini (ruling W6-R6). Tanpa koordinat, karena posisi
 * pendaki tidak dikirim ke server (PRD §82, §145.1).
 */
class RecordArrivalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('trip')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'urutan' => [
                'required',
                'integer',
                Rule::exists('checkpoints', 'sequence')->where('trail_id', $this->route('trip')->trail_id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'urutan.exists' => 'Pos itu tidak ada di jalur trip ini.',
        ];
    }
}
```

Buat `app/Services/Trips/HalamanMendaki.php`:

```php
<?php

namespace App\Services\Trips;

use App\Enums\TripStatus;
use App\Models\Checkpoint;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\CheckpointPaceService;
use App\Services\Trails\CheckpointJourney;
use App\Services\Trails\SkalaProfil;
use Illuminate\Support\Collection;

/**
 * Data mode lapangan satu trip (spec v3 bagian 6.12, prototype 11; rencana induk W6 butir 2).
 *
 * Server hanya mengirim data jalur yang stabil dan keadaan sesi: pos berurutan dengan
 * koordinatnya, garis jalur, profil elevasi, perkiraan waktu per ruas dari rekaman pendaki,
 * laporan komunitas per segmen, dan kemajuan yang sudah tercatat. Posisi, sisa jarak, naik
 * lagi, dan tiba di pos dihitung di perangkat (resources/js/components/mendaki/hitung.ts),
 * jadi halaman ini tetap berguna tanpa sinyal dan posisi pendaki tidak pernah dikirim.
 *
 * Bentuk yang sama disimpan paket lapangan luring, sehingga mode lapangan adalah satu pohon
 * komponen dengan dua sumber data (spec v3 bagian 10.4).
 */
final class HalamanMendaki
{
    /** Profil yang lebih rapat dari ini dijarangkan: cukup untuk menghitung naik lagi per ruas. */
    private const MAKS_TITIK_PROFIL = 400;

    /** Laporan komunitas yang dibawa, terbaru lebih dulu (pola hiking.conditions.community_report_limit). */
    private const MAKS_LAPORAN = 10;

    public function __construct(private readonly CheckpointPaceService $pace) {}

    /**
     * @return array<string, mixed>
     */
    public function untuk(TripPlan $trip, User $pengguna): array
    {
        $trip->loadMissing('trail.mountain', 'trail.checkpoints', 'hikingSession');

        $jalur = $trip->trail;
        $sesi = $trip->hikingSession;
        $pos = $jalur->checkpoints->sortBy('sequence')->values();
        $terakhir = $pos->last();
        $garis = $jalur->readGeoJson('geometry');

        return [
            'trip' => [
                'id' => $trip->id,
                'nama' => $trip->name,
                'jalur' => $jalur->name,
                'gunung' => $jalur->mountain->name,
                'berlangsung' => $trip->status === TripStatus::IN_PROGRESS && $sesi !== null,
                'mulai' => $sesi?->started_at?->utc()->toIso8601String(),
                'jedaSejak' => $sesi?->paused_at?->utc()->toIso8601String(),
                'jedaDetik' => (int) ($sesi?->paused_seconds ?? 0),
            ],
            'pos' => $pos->map(fn (Checkpoint $p) => [
                'id' => $p->id,
                'urutan' => (int) $p->sequence,
                'nama' => $p->name,
                'jenis' => $p->checkpoint_type->label(),
                'aksen' => CheckpointJourney::aksen($p, $terakhir),
                'lng' => $p->longitude === null ? null : (float) $p->longitude,
                'lat' => $p->latitude === null ? null : (float) $p->latitude,
                'elevasi' => $p->elevation_m,
                'segmen' => $p->trail_segment_id,
            ])->all(),
            'garis' => ($garis['type'] ?? null) === 'LineString' ? $garis['coordinates'] : null,
            'profil' => self::profil($jalur->elevation_profile),
            'ruas' => $this->ruas($jalur, $pos),
            'tercapai' => (int) ($sesi?->reached_checkpoint_sequence ?? 0),
            'radiusTiba' => (int) config('hiking.hike_mode.checkpoint_arrival_radius_m'),
            'laporan' => $this->laporan($jalur),
            'rekamJejak' => (bool) $pengguna->preference?->record_track && $sesi !== null,
            'sesiId' => $sesi?->id,
            'sumber' => 'daring',
            'urls' => [
                'tiba' => route('trips.hike.arrive', $trip),
                'jeda' => route('trips.hike.pause', $trip),
                'lanjut' => route('trips.hike.resume', $trip),
                'ruangKerja' => route('trips.show', $trip),
                'jejak' => $sesi !== null ? route('hike.track', $sesi) : null,
            ],
        ];
    }

    /**
     * Perkiraan waktu satu ruas sebagai rentang teramati (DK-31): menit sampai dua jam, lalu
     * jam berkelipatan setengah. Tanda pisah pendek untuk rentang di data (spec v3 bagian 8.8).
     */
    public static function perkiraan(int $tercepat, int $terlama): string
    {
        if ($terlama <= 120) {
            return $tercepat === $terlama ? $tercepat.' menit' : $tercepat.'–'.$terlama.' menit';
        }

        $jam = fn (int $menit) => rtrim(rtrim(number_format(round($menit / 30) / 2, 1, ',', '.'), '0'), ',');

        return $jam($tercepat) === $jam($terlama) ? $jam($tercepat).' jam' : $jam($tercepat).'–'.$jam($terlama).' jam';
    }

    /**
     * Titik profil [km, m] yang sah, dijarangkan bila terlalu rapat; null bila tidak ada profil
     * (R-007): naik lagi tidak ditebak dari ketinggian GPS.
     *
     * @param  array<int, mixed>|null  $profil
     * @return array<int, array{0: float, 1: float}>|null
     */
    private static function profil(?array $profil): ?array
    {
        $titik = SkalaProfil::titik($profil);

        if ($titik === null) {
            return null;
        }

        $langkah = (int) ceil(count($titik) / self::MAKS_TITIK_PROFIL);

        return array_values(array_filter($titik, fn (array $t, int $i) => $i % $langkah === 0 || $i === count($titik) - 1, ARRAY_FILTER_USE_BOTH));
    }

    /**
     * Perkiraan waktu per ruas, dari rekaman pendaki (CheckpointPaceService, paling sedikit lima
     * rekaman per ruas). Ruas tanpa rekaman yang cukup bernilai null; perangkat menulis "belum
     * ada data waktu tempuh", tidak menaksir dengan rumus.
     *
     * @param  Collection<int, Checkpoint>  $pos
     * @return array<int, array{ke: int, perkiraan: string|null, rekaman: int|null}>
     */
    private function ruas(Trail $jalur, Collection $pos): array
    {
        $tempo = collect($this->pace->forTrail($jalur))->keyBy(fn (array $t) => $t['dari'].'-'.$t['ke']);
        $hasil = [];

        for ($i = 1; $i < $pos->count(); $i++) {
            $t = $tempo->get($pos[$i - 1]->id.'-'.$pos[$i]->id);

            $hasil[] = [
                'ke' => (int) $pos[$i]->sequence,
                'perkiraan' => $t === null ? null : self::perkiraan((int) $t['min_menit'], (int) $t['maks_menit']),
                'rekaman' => $t === null ? null : (int) $t['rekaman'],
            ];
        }

        return $hasil;
    }

    /**
     * Laporan komunitas yang lolos moderasi dalam jendela terbaru, per segmen (R-010): tampil
     * sebagai laporan pendaki, tidak pernah sebagai status jalur, dan tanpa nama pelapor.
     *
     * @return array<int, array{segmen: int|null, kondisi: string, catatan: string|null, waktu: string}>
     */
    private function laporan(Trail $jalur): array
    {
        return TrailConditionReport::query()
            ->where('trail_id', $jalur->id)
            ->visibleToPublic()
            ->dalamJendelaTerbaru()
            ->orderByDesc('hike_date')
            ->orderByDesc('id')
            ->limit(self::MAKS_LAPORAN)
            ->get(['id', 'trail_segment_id', 'condition_tags', 'note', 'hike_date'])
            ->map(fn (TrailConditionReport $l) => [
                'segmen' => $l->trail_segment_id,
                'kondisi' => implode(', ', array_map(fn ($tag) => $tag->label(), $l->tags())),
                'catatan' => $l->note,
                'waktu' => match ($hari = $l->daysSinceHike()) {
                    0 => 'hari ini',
                    1 => 'kemarin',
                    default => $hari.' hari lalu',
                },
            ])
            ->all();
    }
}
```

Buat `database/migrations/2026_10_07_000200_add_pause_to_hiking_sessions_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jeda dan Lanjut di mode lapangan (spec v3 bagian 6.12, ruling W6-R7).
 *
 * Waktu bergerak di ringkasan (spec v3 bagian 6.13) adalah lama sesi dikurangi jeda; tanpa
 * kolom ini, malam di tenda pada trip berkemah terhitung sebagai waktu bergerak. paused_at
 * terisi selama jeda berjalan; paused_seconds menjumlahkan jeda yang sudah selesai. Aditif:
 * baris lama mendapat 0 dan tanpa jeda berjalan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hiking_sessions', function (Blueprint $table) {
            $table->timestamp('paused_at')->nullable()->after('ended_at');
            $table->unsignedInteger('paused_seconds')->default(0)->after('paused_at');
        });
    }

    public function down(): void
    {
        Schema::table('hiking_sessions', function (Blueprint $table) {
            $table->dropColumn(['paused_at', 'paused_seconds']);
        });
    }
};
```

Buat `resources/js/components/mendaki/ModeLapangan.tsx`:

```tsx
import type { ReactNode } from 'react';
import { PetaPendakian } from '@/components/trips/PetaPendakian';
import { fokusTerlihat, umpanTekan } from '@/components/ui/gaya';
import { Icon } from '@/components/ui/Icon';
import { Sakelar } from '@/components/ui/Sakelar';
import { kelas } from '@/lib/kelas';
import { teksAkurasi, teksJarak, teksNaik, teksUmur } from './hitung';
import type { DataLapangan } from './types';
import type { ModeLapangan as KeadaanLapangan } from './useModeLapangan';
import { useLayarMenyala } from './useLayarMenyala';

/** Akurasi di atas ini diberi peringatan: jarak dan tiba di pos menjadi kurang pasti. */
const AKURASI_LEMAH_M = 50;

const tombolLapangan = kelas('inline-flex min-h-[60px] flex-1 items-center justify-center gap-2 rounded-tombol px-4 py-4 text-bagian font-bold', fokusTerlihat, umpanTekan);
const tombolKedua = kelas('inline-flex min-h-12 items-center justify-center rounded-tombol border border-control bg-surface px-4 py-3 text-isi font-bold text-primary', fokusTerlihat, umpanTekan);

/** Satu petak angka; tanpa angka, petaknya menyebut apa yang belum ada dengan huruf biasa. */
function Petak({ judul, nilai, keterangan }: { judul: string; nilai: string | null; keterangan: string }) {
    return (
        <div className="min-w-0 flex-1 rounded-kartu border border-subtle bg-surface p-3">
            <dt className="text-kecil text-secondary">{judul}</dt>
            <dd className={nilai === null ? 'mt-1 text-isi font-bold text-primary' : 'mt-1 break-words text-judul-kartu tabular-nums text-primary'}>{nilai ?? 'Belum ada'}</dd>
            <dd className="mt-1 text-meta text-secondary">{keterangan}</dd>
        </div>
    );
}

const kalimatLokasi: Record<string, string> = {
    mati: 'Lokasi berhenti diperbarui selama jeda.',
    menunggu: 'Menunggu posisi dari perangkat.',
    ditolak: 'Izin lokasi ditolak. Izinkan lokasi untuk peramban ini di pengaturan perangkat, atau tandai tiba di pos sendiri.',
    'tidak-didukung': 'Peramban ini tidak dapat membaca lokasi. Tandai tiba di pos sendiri.',
    galat: 'Posisi belum terbaca. Perangkat mencoba lagi.',
};

/**
 * Isi mode lapangan (spec v3 bagian 6.12, prototype 11): peta, pos berikutnya dengan namanya
 * besar, tiga petak (sisa jarak, naik lagi, perkiraan ruas), akurasi dan umur posisi, laporan
 * komunitas untuk segmen ini, layar tetap menyala, keadaan kiriman, lalu Jeda atau Lanjut dan
 * Selesai. Hanya yang dibutuhkan sambil bergerak (PRD §54); setiap angka menyebut sumber atau
 * caranya, dan yang tidak diketahui ditulis begitu (R-007).
 */
export function ModeLapangan({ data, mode, selesai }: { data: DataLapangan; mode: KeadaanLapangan; selesai: ReactNode }) {
    const layar = useLayarMenyala();
    const { tujuan, posisi } = mode;
    const laporan = tujuan ? data.laporan.find((l) => l.segmen !== null && l.segmen === tujuan.segmen) : undefined;
    const umur = posisi ? (Date.now() - posisi.waktu) / 1000 : null;

    return (
        <div className="space-y-4 pt-4">
            <PetaPendakian id="peta-lapangan" label={`Peta jalur ${data.trip.jalur}, pos, dan posisi Anda`} garis={data.garis} pos={data.pos} tercapai={mode.tercapai} dilaluiM={mode.dilaluiM} posisi={posisi} />

            <section aria-labelledby="judul-pos-berikutnya">
                {tujuan ? (
                    <>
                        <h2 id="judul-pos-berikutnya" className="text-kecil font-bold text-secondary">
                            Pos berikutnya, nomor {tujuan.urutan}
                        </h2>
                        <p key={mode.tercapai} className={kelas('mt-1 break-words text-tampilan text-primary', mode.pengumuman.startsWith('Tiba') ? 'denyut-tiba' : null)}>
                            {tujuan.nama}
                        </p>
                        <p className="text-kecil text-secondary">{tujuan.jenis}</p>
                    </>
                ) : (
                    <>
                        <h2 id="judul-pos-berikutnya" className="text-kecil font-bold text-secondary">
                            Pos berikutnya
                        </h2>
                        <p className="mt-1 text-judul-kartu text-primary">{data.pos.length === 0 ? 'Jalur ini belum punya pos tercatat.' : 'Semua pos jalur ini sudah dicapai.'}</p>
                    </>
                )}
            </section>

            <p role="status" aria-atomic="true" className="text-isi font-bold text-primary">
                {mode.pengumuman}
            </p>

            {mode.bertanya && tujuan ? (
                <div className="rounded-kartu border-2 border-primary bg-surface p-4">
                    <p className="text-bagian text-primary">Sudah tiba di {tujuan.nama}?</p>
                    <p className="mt-1 text-kecil text-secondary">Posisi Anda dekat, tetapi akurasinya belum cukup untuk mencatatnya otomatis.</p>
                    <div className="mt-3 flex flex-wrap gap-2">
                        <button type="button" onClick={() => mode.tiba(tujuan.urutan)} className={kelas(tombolKedua, 'bg-brand-700 text-on-brand')}>
                            Ya, sudah tiba
                        </button>
                        <button type="button" onClick={mode.tolakTiba} className={tombolKedua}>
                            Belum
                        </button>
                    </div>
                </div>
            ) : null}

            {tujuan ? (
                <dl className="flex gap-2">
                    <Petak
                        judul="Sisa jarak"
                        nilai={mode.sisa ? teksJarak(mode.sisa.meter) : null}
                        keterangan={mode.sisa ? (mode.sisa.cara === 'jalur' ? 'menyusuri jalur' : 'garis lurus') : tujuan.lat === null ? 'pos tanpa koordinat' : 'menunggu posisi'}
                    />
                    <Petak judul="Naik lagi" nilai={mode.naik ? teksNaik(mode.naik.meter) : null} keterangan={mode.naik?.sumber ?? 'profil elevasi belum ada'} />
                    <Petak judul="Perkiraan" nilai={mode.perkiraan?.teks ?? null} keterangan={mode.perkiraan ? `ruas ini, ${mode.perkiraan.rekaman} rekaman` : 'belum ada data waktu tempuh'} />
                </dl>
            ) : null}

            <div className="space-y-1">
                {posisi ? (
                    <p className="text-kecil text-secondary">
                        <span className="tabular-nums">{teksAkurasi(posisi.akurasi)}</span> · {umur !== null ? teksUmur(umur) : null}
                    </p>
                ) : (
                    <p className="text-kecil text-secondary">{kalimatLokasi[mode.lokasi] ?? kalimatLokasi.menunggu}</p>
                )}
                {posisi && posisi.akurasi > AKURASI_LEMAH_M ? (
                    <p className="flex items-start gap-2 text-kecil font-bold text-primary">
                        <Icon name="warning" size={16} className="mt-0.5 shrink-0" />
                        Akurasi rendah: jarak dan tiba di pos kurang pasti.
                    </p>
                ) : null}
                <p className="text-meta text-secondary">Lokasi diproses di perangkat ini dan tidak dikirim ke server. Rekam jejak {data.rekamJejak ? 'menyala' : 'mati'}.</p>
            </div>

            {laporan ? (
                <section aria-label="Laporan komunitas untuk segmen ini" className="rounded-kartu border-[1.5px] border-dashed border-community-500 bg-community-50 p-4">
                    <p className="text-isi text-community-900">
                        <span className="font-bold">Segmen ini dilaporkan: {laporan.kondisi.toLowerCase()}.</span> Laporan komunitas, {laporan.waktu}.
                    </p>
                    {laporan.catatan ? <p className="mt-1 break-words text-kecil text-community-900">{laporan.catatan}</p> : null}
                </section>
            ) : null}

            {tujuan && data.trip.berlangsung ? (
                <button type="button" onClick={() => mode.tiba(tujuan.urutan)} className={tombolKedua}>
                    Saya sudah di {tujuan.nama}
                </button>
            ) : null}

            {data.pos.length > 0 ? (
                <details className="rounded-kartu border border-subtle bg-surface">
                    <summary className={kelas('flex min-h-12 cursor-pointer items-center px-4 text-isi font-bold text-primary', fokusTerlihat)}>Semua pos ({data.pos.length})</summary>
                    <ol className="space-y-1 px-4 pb-4">
                        {data.pos.map((p) => (
                            <li key={p.id} aria-current={p.urutan === tujuan?.urutan ? 'step' : undefined} className="break-words text-isi text-primary">
                                <span className="tabular-nums">{p.urutan}.</span> {p.nama}
                                <span className="text-kecil text-secondary">
                                    {' '}
                                    · {p.urutan <= mode.tercapai ? 'sudah dicapai' : p.urutan === tujuan?.urutan ? 'berikutnya' : p.jenis}
                                </span>
                            </li>
                        ))}
                    </ol>
                </details>
            ) : null}

            {layar.didukung ? <Sakelar judul="Layar tetap menyala" keterangan="Mati bawaan: layar yang terus menyala menghabiskan baterai." nyala={layar.menyala} onUbah={layar.ubah} /> : null}

            {!mode.daring || mode.antrean.jumlah > 0 || mode.antrean.pesan ? (
                <p className="rounded-kecil border border-subtle bg-surface-sunken px-3 py-2 text-kecil text-primary">
                    {!mode.daring ? 'Luring. ' : null}
                    {mode.antrean.jumlah > 0 ? `${mode.antrean.jumlah} catatan menunggu sinyal untuk dikirim.` : null}
                    {mode.antrean.pesan ? ` ${mode.antrean.pesan}` : null}
                </p>
            ) : null}

            {data.trip.berlangsung ? (
                <div className="flex gap-3">
                    {mode.dijeda ? (
                        <button type="button" onClick={mode.lanjut} className={kelas(tombolLapangan, 'border border-control bg-surface text-primary')}>
                            Lanjut
                        </button>
                    ) : (
                        <button type="button" onClick={mode.jeda} className={kelas(tombolLapangan, 'border border-control bg-surface text-primary')}>
                            Jeda
                        </button>
                    )}
                    {selesai}
                </div>
            ) : null}
        </div>
    );
}

export { tombolLapangan };
```

Buat `resources/js/components/mendaki/antrean.ts`:

```ts
/**
 * Antrean aksi mode lapangan (ruling W6-R6): tiba di pos, jeda, dan lanjut.
 *
 * Setiap aksi dicatat dulu di perangkat lalu dikirim; tanpa sinyal ia menunggu di sini dan
 * diputar ulang saat peramban mengabarkan "online", saat aksi berikutnya ditambahkan, atau
 * tiap 30 detik selama halaman terbuka. Ketiga aksi aman diputar ulang menurut isinya (server
 * memakai UPDATE bersyarat), jadi tidak butuh kunci idempoten. Token XSRF dibaca dari cookie
 * pada saat kirim, bukan saat aksi dibuat, sehingga sesi yang diperbarui tetap diterima.
 *
 * Penyimpanannya memori bawaan; paket lapangan luring menggantinya dengan IndexedDB per
 * pengguna lewat pakaiPenyimpanan(), supaya aksi bertahan bila halaman luring ditutup.
 */

export interface AksiAntrean {
    id: string;
    url: string;
    isi: Record<string, unknown>;
    dibuat: number;
}

export interface PenyimpananAntrean {
    muat(): Promise<AksiAntrean[]>;
    simpan(aksi: AksiAntrean[]): Promise<void>;
}

export interface KeadaanAntrean {
    jumlah: number;
    /** Kalimat untuk pendaki bila satu aksi ditolak server atau sesi perlu masuk lagi. */
    pesan: string | null;
}

type Pendengar = (keadaan: KeadaanAntrean) => void;

function penyimpananMemori(): PenyimpananAntrean {
    let isi: AksiAntrean[] = [];

    return {
        muat: async () => isi,
        simpan: async (aksi) => {
            isi = aksi;
        },
    };
}

let penyimpanan: PenyimpananAntrean = penyimpananMemori();
let antrean: AksiAntrean[] = [];
let termuat: Promise<void> | null = null;
let mengirim = false;
let pesan: string | null = null;
const pendengar = new Set<Pendengar>();

export function pakaiPenyimpanan(baru: PenyimpananAntrean): void {
    penyimpanan = baru;
    termuat = null;
}

function tokenXsrf(): string {
    const cocok = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

    return cocok?.[1] ? decodeURIComponent(cocok[1]) : '';
}

async function pastikanTermuat(): Promise<void> {
    termuat ??= penyimpanan.muat().then((isi) => {
        antrean = isi;
    });

    await termuat;
}

function kabari(): void {
    const keadaan = { jumlah: antrean.length, pesan };
    pendengar.forEach((dengar) => dengar(keadaan));
}

export function dengarAntrean(dengar: Pendengar): () => void {
    pendengar.add(dengar);
    void pastikanTermuat().then(kabari);

    return () => {
        pendengar.delete(dengar);
    };
}

export async function kirimAksi(url: string, isi: Record<string, unknown>): Promise<void> {
    await pastikanTermuat();
    antrean.push({ id: globalThis.crypto?.randomUUID?.() ?? `${Date.now()}-${antrean.length}`, url, isi, dibuat: Date.now() });
    await penyimpanan.simpan(antrean);
    kabari();
    await putarAntrean();
}

async function pesanDari(respons: Response): Promise<string> {
    try {
        const isi = (await respons.json()) as { message?: string };

        return isi.message ? `Satu aksi tidak diterima: ${isi.message}` : 'Satu aksi tidak diterima server.';
    } catch {
        return 'Satu aksi tidak diterima server.';
    }
}

/**
 * Mengirim antrean dari depan. Galat jaringan, 429, dan 5xx menghentikan putaran dan aksinya
 * tetap menunggu; 401 dan 419 juga menunggu, dengan ajakan masuk lagi; penolakan lain (403,
 * 404, 409, 422) membuang aksi itu dan menyebutnya.
 */
export async function putarAntrean(): Promise<void> {
    await pastikanTermuat();

    if (mengirim || antrean.length === 0 || navigator.onLine === false) {
        return;
    }

    mengirim = true;

    try {
        while (antrean.length > 0) {
            const aksi = antrean[0] as AksiAntrean;
            let respons: Response;

            try {
                respons = await fetch(aksi.url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-XSRF-TOKEN': tokenXsrf() },
                    body: JSON.stringify(aksi.isi),
                });
            } catch {
                break;
            }

            if (respons.status === 401 || respons.status === 419) {
                pesan = 'Sesi Anda berakhir. Masuk lagi supaya aksi yang menunggu terkirim.';
                break;
            }

            if (respons.status === 429 || respons.status >= 500) {
                break;
            }

            pesan = respons.ok ? null : await pesanDari(respons);
            antrean.shift();
            await penyimpanan.simpan(antrean);
            kabari();
        }
    } finally {
        mengirim = false;
        kabari();
    }
}
```

Buat `resources/js/components/mendaki/types.ts`:

```ts
import type { Titik, TitikProfil } from './hitung';

/** Satu pos dari App\Services\Trips\HalamanMendaki. Koordinat null bila belum tercatat. */
export interface PosLapangan {
    id: number;
    urutan: number;
    nama: string;
    jenis: string;
    aksen: 'kemah' | 'tujuan' | null;
    lng: number | null;
    lat: number | null;
    /** Ketinggian pos tercatat, meter; null bila belum ada sumber. */
    elevasi: number | null;
    segmen: number | null;
}

/** Perkiraan satu ruas, berakhir di pos berurutan `ke`; null bila rekaman belum cukup. */
export interface RuasLapangan {
    ke: number;
    perkiraan: string | null;
    rekaman: number | null;
}

/** Laporan komunitas yang lolos moderasi, per segmen, tanpa nama pelapor (R-010). */
export interface LaporanLapangan {
    segmen: number | null;
    kondisi: string;
    catatan: string | null;
    waktu: string;
}

/**
 * Data mode lapangan. Bentuknya sama untuk props halaman (daring) dan isi paket lapangan
 * (luring), sehingga mode lapangan adalah satu pohon komponen dengan dua sumber data.
 */
export interface DataLapangan {
    trip: {
        id: number;
        nama: string;
        jalur: string;
        gunung: string;
        berlangsung: boolean;
        /** Instan mulai sesi, ISO 8601 UTC; null bila trip belum dimulai. */
        mulai: string | null;
        /** Instan jeda yang sedang berjalan dimulai; null bila tidak sedang jeda. */
        jedaSejak: string | null;
        /** Jumlah detik jeda yang sudah selesai. */
        jedaDetik: number;
    };
    pos: PosLapangan[];
    garis: Titik[] | null;
    profil: TitikProfil[] | null;
    ruas: RuasLapangan[];
    tercapai: number;
    radiusTiba: number;
    laporan: LaporanLapangan[];
    rekamJejak: boolean;
    sesiId: number | null;
    sumber: 'daring' | 'paket';
    urls: { tiba: string; jeda: string; lanjut: string; ruangKerja: string; jejak: string | null };
}

/** Posisi perangkat terakhir: bujur, lintang, akurasi dalam meter, dan waktunya (ms). */
export interface PosisiPerangkat {
    lng: number;
    lat: number;
    akurasi: number;
    waktu: number;
}
```

Buat `resources/js/components/mendaki/useLayarMenyala.ts`:

```ts
import { useEffect, useRef, useState } from 'react';

/**
 * Sakelar layar tetap menyala (spec v3 bagian 6.12 dan 10.8): mati bawaan, karena layar yang
 * terus menyala menghabiskan baterai yang di jalur lebih berharga daripada kenyamanan. Peramban
 * melepas kunci layar setiap kali tab tersembunyi, jadi kunci diminta lagi saat terlihat.
 * Peramban tanpa Wake Lock API tidak mendapat sakelarnya (DK-40).
 */
export function useLayarMenyala(): { didukung: boolean; menyala: boolean; ubah: (nilai: boolean) => void } {
    const didukung = typeof navigator !== 'undefined' && 'wakeLock' in navigator;
    const [menyala, setMenyala] = useState(false);
    const kunci = useRef<WakeLockSentinel | null>(null);

    useEffect(() => {
        if (!didukung || !menyala) {
            return;
        }

        let batal = false;

        const minta = () => {
            if (document.visibilityState !== 'visible' || kunci.current !== null) {
                return;
            }

            navigator.wakeLock
                .request('screen')
                .then((sentinel) => {
                    if (batal) {
                        void sentinel.release();

                        return;
                    }

                    kunci.current = sentinel;
                    sentinel.addEventListener('release', () => {
                        kunci.current = null;
                    });
                })
                .catch(() => setMenyala(false));
        };

        minta();
        document.addEventListener('visibilitychange', minta);

        return () => {
            batal = true;
            document.removeEventListener('visibilitychange', minta);
            void kunci.current?.release();
            kunci.current = null;
        };
    }, [didukung, menyala]);

    return { didukung, menyala, ubah: setMenyala };
}
```

Buat `resources/js/components/mendaki/useModeLapangan.ts`:

```ts
import { useEffect, useMemo, useRef, useState } from 'react';
import { dengarAntrean, kirimAksi, putarAntrean, type KeadaanAntrean } from './antrean';
import {
    detikBergerak,
    jarakMeter,
    kmPadaProfil,
    keputusanTiba,
    naikDiantara,
    panjangGaris,
    posBerikutnya,
    proyeksikan,
    sepanjangPos,
    sisaJarak,
    type KeputusanTiba,
    type Titik,
} from './hitung';
import type { DataLapangan, PosLapangan, PosisiPerangkat } from './types';
import { usePosisiLapangan, type KeadaanLokasi } from './usePosisiLapangan';

/** Pertanyaan "Sudah tiba?" yang dijawab "Belum" tidak diulang untuk pos yang sama selama ini. */
const JEDA_TANYA_MS = 120_000;

export interface ModeLapangan {
    tujuan: PosLapangan | null;
    tercapai: number;
    posisi: PosisiPerangkat | null;
    lokasi: KeadaanLokasi;
    sisa: { meter: number; cara: 'jalur' | 'garis lurus' } | null;
    naik: { meter: number; sumber: string } | null;
    perkiraan: { teks: string; rekaman: number } | null;
    keputusan: KeputusanTiba;
    bertanya: boolean;
    dijeda: boolean;
    detik: number;
    antrean: KeadaanAntrean;
    daring: boolean;
    pengumuman: string;
    /** Letak sepanjang garis yang sudah dilalui, untuk membelah garis di peta; null tanpa garis. */
    dilaluiM: number | null;
    tiba: (urutan: number) => void;
    tolakTiba: () => void;
    jeda: () => void;
    lanjut: () => void;
}

/**
 * Keadaan mode lapangan di perangkat (spec v3 bagian 6.12): pos berikutnya, angka yang
 * tergantung posisi, tiba di pos, jeda, dan antrean kiriman. Server hanya tahu urutan pos yang
 * dicapai dan waktu jeda; keduanya diperbarui di layar lebih dulu lalu dikirim lewat antrean,
 * jadi tanpa sinyal pun pendaki melihat kemajuannya sendiri.
 */
export function useModeLapangan(data: DataLapangan): ModeLapangan {
    const [tercapai, setTercapai] = useState(data.tercapai);
    const [jedaSejak, setJedaSejak] = useState<number | null>(data.trip.jedaSejak ? Date.parse(data.trip.jedaSejak) : null);
    const [jedaDetik, setJedaDetik] = useState(data.trip.jedaDetik);
    const [sekarang, setSekarang] = useState(() => Date.now());
    const [antrean, setAntrean] = useState<KeadaanAntrean>({ jumlah: 0, pesan: null });
    const [daring, setDaring] = useState(() => navigator.onLine);
    const [pengumuman, setPengumuman] = useState('');
    const ditanyaTolak = useRef<{ urutan: number; sampai: number } | null>(null);

    const dijeda = jedaSejak !== null;
    const { posisi, keadaan: lokasi } = usePosisiLapangan(data.trip.berlangsung && !dijeda);

    useEffect(() => {
        const pewaktu = window.setInterval(() => setSekarang(Date.now()), 5_000);
        const ulang = window.setInterval(() => void putarAntrean(), 30_000);
        const naik = () => {
            setDaring(true);
            void putarAntrean();
        };
        const turun = () => setDaring(false);
        const lepas = dengarAntrean(setAntrean);

        window.addEventListener('online', naik);
        window.addEventListener('offline', turun);

        return () => {
            window.clearInterval(pewaktu);
            window.clearInterval(ulang);
            window.removeEventListener('online', naik);
            window.removeEventListener('offline', turun);
            lepas();
        };
    }, []);

    const garis = data.garis;
    const panjang = useMemo(() => (garis ? panjangGaris(garis) : 0), [garis]);
    const sepanjang = useMemo(() => new Map(data.pos.map((p) => [p.urutan, sepanjangPos(garis, p.lng, p.lat)])), [data.pos, garis]);
    const tujuan = posBerikutnya(data.pos, tercapai);
    const titikPosisi: Titik | null = posisi ? [posisi.lng, posisi.lat] : null;
    const letak = garis && titikPosisi ? proyeksikan(garis, titikPosisi) : null;
    const sepanjangTujuan = tujuan ? (sepanjang.get(tujuan.urutan) ?? null) : null;
    const sepanjangTercapai = tercapai > 0 ? (sepanjang.get(tercapai) ?? null) : garis ? 0 : null;

    const sisa = tujuan && titikPosisi ? sisaJarak(garis, titikPosisi, { lng: tujuan.lng, lat: tujuan.lat, sepanjangM: sepanjangTujuan }) : null;

    // Naik lagi dari profil elevasi: dari letak pendaki di garis bila ia dekat garis, selain itu
    // dari pos terakhir yang dicapai, sampai pos tujuan. Tanpa profil atau garis, dari ketinggian
    // tercatat pos terakhir dan pos tujuan (selisih bersih, jadi paling sedikit sebesar itu).
    // Ketinggian GPS tidak pernah dipakai.
    const dari = sisa?.cara === 'jalur' && letak ? letak.sepanjangM : sepanjangTercapai;
    const naikProfil =
        data.profil && tujuan && sepanjangTujuan !== null && dari !== null
            ? naikDiantara(data.profil, kmPadaProfil(dari, panjang, data.profil) ?? 0, kmPadaProfil(sepanjangTujuan, panjang, data.profil) ?? 0)
            : null;
    const posLalu = data.pos.find((p) => p.urutan === tercapai);
    const naik =
        naikProfil !== null
            ? { meter: naikProfil, sumber: 'dari profil elevasi' }
            : tujuan?.elevasi != null && posLalu?.elevasi != null
              ? { meter: Math.max(0, tujuan.elevasi - posLalu.elevasi), sumber: 'dari ketinggian pos' }
              : null;

    const ruas = tujuan ? data.ruas.find((r) => r.ke === tujuan.urutan) : undefined;
    const perkiraan = ruas?.perkiraan ? { teks: ruas.perkiraan, rekaman: ruas.rekaman ?? 0 } : null;

    const keputusan: KeputusanTiba =
        posisi && tujuan && tujuan.lng !== null && tujuan.lat !== null && !dijeda
            ? keputusanTiba(jarakMeter([posisi.lng, posisi.lat], [tujuan.lng, tujuan.lat]), posisi.akurasi, data.radiusTiba)
            : 'belum';
    const tolak = ditanyaTolak.current;
    const bertanya = keputusan === 'tanya' && tujuan !== null && !(tolak && tolak.urutan === tujuan.urutan && tolak.sampai > sekarang);

    const tiba = (urutan: number) => {
        const sesudah = posBerikutnya(data.pos, urutan);
        const nama = data.pos.find((p) => p.urutan === urutan)?.nama ?? `pos ${urutan}`;

        setTercapai((lalu) => Math.max(lalu, urutan));
        setPengumuman(sesudah ? `Tiba di ${nama}. Berikutnya ${sesudah.nama}.` : `Tiba di ${nama}, pos terakhir jalur ini.`);

        if (typeof navigator.vibrate === 'function' && !window.matchMedia?.('(prefers-reduced-motion: reduce)').matches) {
            navigator.vibrate(200);
        }

        void kirimAksi(data.urls.tiba, { urutan });
    };

    // Tiba otomatis bila jarak ditambah akurasi masuk radius; sekali per pos.
    useEffect(() => {
        if (keputusan === 'tiba' && tujuan && data.trip.berlangsung) {
            tiba(tujuan.urutan);
        }
    }, [keputusan, tujuan?.urutan]);

    const jeda = () => {
        const waktu = Date.now();

        setJedaSejak(waktu);
        setPengumuman('Pendakian dijeda. Lokasi berhenti diperbarui sampai Anda melanjutkan.');
        void kirimAksi(data.urls.jeda, { pada: new Date(waktu).toISOString() });
    };

    const lanjut = () => {
        const waktu = Date.now();

        if (jedaSejak !== null) {
            setJedaDetik((lalu) => lalu + Math.max(0, Math.round((waktu - jedaSejak) / 1000)));
        }

        setJedaSejak(null);
        setPengumuman('Pendakian dilanjutkan.');
        void kirimAksi(data.urls.lanjut, { pada: new Date(waktu).toISOString() });
    };

    return {
        tujuan,
        tercapai,
        posisi,
        lokasi,
        sisa,
        naik,
        perkiraan,
        keputusan,
        bertanya,
        dijeda,
        detik: data.trip.mulai ? detikBergerak(Date.parse(data.trip.mulai), jedaDetik, jedaSejak, sekarang) : 0,
        antrean,
        daring,
        pengumuman,
        dilaluiM: sisa?.cara === 'jalur' && letak ? letak.sepanjangM : sepanjangTercapai,
        tiba,
        tolakTiba: () => {
            if (tujuan) {
                ditanyaTolak.current = { urutan: tujuan.urutan, sampai: Date.now() + JEDA_TANYA_MS };
                setSekarang(Date.now());
            }
        },
        jeda,
        lanjut,
    };
}
```

Buat `resources/js/components/mendaki/usePosisiLapangan.ts`:

```ts
import { useEffect, useState } from 'react';
import type { PosisiPerangkat } from './types';

export type KeadaanLokasi = 'mati' | 'menunggu' | 'aktif' | 'ditolak' | 'tidak-didukung' | 'galat';

/**
 * Posisi perangkat untuk mode lapangan, satu-satunya tempat aplikasi meminta lokasi (PRD §120,
 * LocationPrivacyTest). Posisi tinggal di perangkat: tidak ada yang dikirim ke server dari sini
 * (PRD §82, §145.1).
 *
 * GPS hanya berjalan selama layar terlihat dan pendakian tidak dijeda (spec v3 bagian 10.8):
 * pengawas posisi dilepas saat tab tersembunyi atau layar mati, dan dipasang lagi saat terlihat.
 */
export function usePosisiLapangan(aktif: boolean): { posisi: PosisiPerangkat | null; keadaan: KeadaanLokasi } {
    const [posisi, setPosisi] = useState<PosisiPerangkat | null>(null);
    const [keadaan, setKeadaan] = useState<KeadaanLokasi>(aktif ? 'menunggu' : 'mati');

    useEffect(() => {
        if (!aktif) {
            setKeadaan('mati');

            return;
        }

        if (!('geolocation' in navigator)) {
            setKeadaan('tidak-didukung');

            return;
        }

        let pengawas: number | null = null;

        const pasang = () => {
            if (pengawas !== null || document.visibilityState !== 'visible') {
                return;
            }

            setKeadaan((lalu) => (lalu === 'aktif' ? lalu : 'menunggu'));
            pengawas = navigator.geolocation.watchPosition(
                (p) => {
                    setPosisi({ lng: p.coords.longitude, lat: p.coords.latitude, akurasi: p.coords.accuracy, waktu: p.timestamp });
                    setKeadaan('aktif');
                },
                (galat) => setKeadaan(galat.code === galat.PERMISSION_DENIED ? 'ditolak' : 'galat'),
                { enableHighAccuracy: true, maximumAge: 5000, timeout: 30000 },
            );
        };

        const lepas = () => {
            if (pengawas !== null) {
                navigator.geolocation.clearWatch(pengawas);
                pengawas = null;
            }
        };

        const saatBerubah = () => (document.visibilityState === 'visible' ? pasang() : lepas());

        pasang();
        document.addEventListener('visibilitychange', saatBerubah);

        return () => {
            document.removeEventListener('visibilitychange', saatBerubah);
            lepas();
        };
    }, [aktif]);

    return { posisi, keadaan };
}
```

Tulis ulang `app/Http/Controllers/Trips/HikeModeController.php` utuh:

```php
<?php

namespace App\Http\Controllers\Trips;

use App\Enums\HikingSessionStatus;
use App\Enums\TripStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Trips\PauseHikeRequest;
use App\Http\Requests\Trips\RecordArrivalRequest;
use App\Models\HikingSession;
use App\Models\TripPlan;
use App\Services\Trips\HalamanMendaki;
use App\Services\Trips\HikeCheckpointResolver;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Mode lapangan (FR-15; spec v3 bagian 6.12, prototype 11).
 *
 * Halaman membawa data jalur dan keadaan sesi; posisi, sisa jarak, naik lagi, dan tiba di pos
 * dihitung di perangkat (HalamanMendaki). Lokasi diminta di halaman ini dan tidak di tempat
 * lain (PRD §120), dan tidak dikirim ke server (PRD §82, §145.1): server hanya menerima urutan
 * pos yang dicapai dan waktu jeda. Ketiga aksi itu dikirim antrean perangkat sebagai JSON dan
 * aman diputar ulang sesudah sinyal kembali (ruling W6-R6).
 */
class HikeModeController extends Controller
{
    public function show(Request $request, TripPlan $trip, HalamanMendaki $halaman): Response|RedirectResponse
    {
        $this->authorize('view', $trip);

        // Trip yang sudah usai tidak punya mode lapangan: ringkasannya, atau ruang kerjanya.
        if ($trip->status === TripStatus::COMPLETED) {
            return redirect()->route('history.summary', $trip);
        }

        if ($trip->status === TripStatus::CANCELLED) {
            return redirect()->route('trips.show', $trip);
        }

        return Inertia::render('Trips/Hike', [
            'title' => 'Mode lapangan',
            ...$halaman->untuk($trip, $request->user()),
        ]);
    }

    public function arrive(RecordArrivalRequest $request, TripPlan $trip, HikeCheckpointResolver $resolver): HttpResponse
    {
        $sesi = $this->sesiBerlangsung($trip);

        $resolver->tiba($sesi, $resolver->checkpoints($trip->trail), (int) $request->validated('urutan'));

        return response()->noContent();
    }

    /**
     * Jeda dimulai pada waktu perangkat bila dikirim dari antrean, dijepit di antara mulai sesi
     * dan sekarang. Jeda yang sudah berjalan tidak dimulai ulang.
     */
    public function pause(PauseHikeRequest $request, TripPlan $trip): HttpResponse
    {
        $sesi = $this->sesiBerlangsung($trip);

        HikingSession::query()->whereKey($sesi->id)->whereNull('paused_at')->update([
            'paused_at' => $this->waktu($request, $sesi),
            'status' => HikingSessionStatus::PAUSED->value,
            'updated_at' => now(),
        ]);

        return response()->noContent();
    }

    /**
     * Lanjut menjumlahkan lama jeda ke paused_seconds dalam satu UPDATE bersyarat, sehingga
     * kiriman ganda tidak menghitung jeda yang sama dua kali.
     */
    public function resume(PauseHikeRequest $request, TripPlan $trip): HttpResponse
    {
        $sesi = $this->sesiBerlangsung($trip);

        if ($sesi->paused_at !== null) {
            $detik = max(0, (int) $sesi->paused_at->diffInSeconds($this->waktu($request, $sesi), false));

            HikingSession::query()->whereKey($sesi->id)->whereNotNull('paused_at')->update([
                'paused_at' => null,
                'paused_seconds' => DB::raw('paused_seconds + '.$detik),
                'status' => HikingSessionStatus::ACTIVE->value,
                'updated_at' => now(),
            ]);
        }

        return response()->noContent();
    }

    /**
     * Aksi lapangan hanya untuk pendakian yang sedang berlangsung dan bersesi. Selain itu 409:
     * antrean perangkat membuang aksinya dan menyebut alasannya.
     */
    private function sesiBerlangsung(TripPlan $trip): HikingSession
    {
        $trip->loadMissing('hikingSession', 'trail.checkpoints');

        abort_unless($trip->status === TripStatus::IN_PROGRESS && $trip->hikingSession !== null, 409, 'Pendakian ini tidak sedang berlangsung.');

        return $trip->hikingSession;
    }

    private function waktu(PauseHikeRequest $request, HikingSession $sesi): CarbonImmutable
    {
        $sekarang = CarbonImmutable::now();
        $pada = $request->validated('pada');

        if ($pada === null) {
            return $sekarang;
        }

        $waktu = CarbonImmutable::parse($pada);

        return $waktu->max($sesi->started_at)->min($sekarang);
    }
}
```

`app/Models/HikingSession.php`:

1. Ganti:

```php
use App\Models\Concerns\HasSpatialColumns;
use Illuminate\Database\Eloquent\Attributes\Fillable;
```

dengan:

```php
use App\Models\Concerns\HasSpatialColumns;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
```

2. Ganti:

```php
    'trip_plan_id', 'user_id', 'current_checkpoint_id', 'reached_checkpoint_sequence', 'status',
    'started_at', 'ended_at', 'location_updated_at',
])]
```

dengan:

```php
    'trip_plan_id', 'user_id', 'current_checkpoint_id', 'reached_checkpoint_sequence', 'status',
    'started_at', 'ended_at', 'paused_at', 'paused_seconds', 'location_updated_at',
])]
```

3. Ganti:

```php
            'ended_at' => 'datetime',
            'location_updated_at' => 'datetime',
        ];
    }
```

dengan:

```php
            'ended_at' => 'datetime',
            'paused_at' => 'datetime',
            'paused_seconds' => 'integer',
            'location_updated_at' => 'datetime',
        ];
    }

    /**
     * Detik bergerak (ruling W6-R7): sejak mulai sampai selesai, atau sampai $sekarang selama
     * sesi berjalan, dikurangi jeda yang sudah selesai dan jeda yang sedang berjalan. Tidak
     * pernah negatif.
     */
    public function detikBergerak(?CarbonInterface $sekarang = null): ?int
    {
        if ($this->started_at === null) {
            return null;
        }

        $akhir = $this->ended_at ?? $sekarang ?? now();
        $jedaBerjalan = $this->paused_at === null ? 0 : max(0, (int) $this->paused_at->diffInSeconds($akhir, false));

        return max(0, (int) $this->started_at->diffInSeconds($akhir, false) - (int) $this->paused_seconds - $jedaBerjalan);
    }
```

Tulis ulang `app/Services/Trips/HikeCheckpointResolver.php` utuh:

```php
<?php

namespace App\Services\Trips;

use App\Models\Checkpoint;
use App\Models\HikingSession;
use App\Models\Trail;

/**
 * Kemajuan antarpos di mode lapangan (FR-15).
 *
 * Pos berikutnya dihitung di perangkat dari data yang sudah dimuat (spec v3 bagian 6.12,
 * resources/js/components/mendaki/hitung.ts): posisi pendaki tidak dikirim ke server (PRD
 * §82, §145.1). Yang diterima server hanya urutan pos yang sudah dicapai, lalu dicatat pada
 * sesi supaya strip pendakian, Beranda, dan ringkasan tahu sampai mana pendakian ini.
 *
 * Kemajuan tidak pernah mundur: pendaki yang turun kembali atau mengirim ulang tiba di pos
 * yang lebih awal (antrean luring yang diputar ulang) tidak menghapus pos yang sudah dicapai.
 */
class HikeCheckpointResolver
{
    /**
     * Koordinat dibaca dari kolom biasa, bukan diekstrak dari kolom geografi. Sumber
     * yang sama bekerja di mana pun, termasuk koneksi tanpa PostGIS.
     *
     * @return array<int, array{id: int, name: string, sequence: int, lat: float|null, lng: float|null}>
     */
    public function checkpoints(Trail $trail): array
    {
        return $trail->checkpoints
            ->sortBy('sequence')
            ->map(fn (Checkpoint $checkpoint) => [
                'id' => $checkpoint->id,
                'name' => $checkpoint->name,
                'sequence' => (int) $checkpoint->sequence,
                'lat' => $checkpoint->latitude === null ? null : (float) $checkpoint->latitude,
                'lng' => $checkpoint->longitude === null ? null : (float) $checkpoint->longitude,
            ])
            ->values()
            ->all();
    }

    /**
     * Pos yang tercatat pada sesi sebagai tujuan sekarang.
     *
     * @param  array<int, array{id: int, name: string, sequence: int, lat: float|null, lng: float|null}>  $checkpoints
     * @return array{id: int, name: string, sequence: int}|null
     */
    public function current(?HikingSession $session, array $checkpoints): ?array
    {
        $id = $session?->current_checkpoint_id;

        if ($id === null) {
            return null;
        }

        foreach ($checkpoints as $checkpoint) {
            if ($checkpoint['id'] === (int) $id) {
                return ['id' => $checkpoint['id'], 'name' => $checkpoint['name'], 'sequence' => $checkpoint['sequence']];
            }
        }

        return null;
    }

    /**
     * Mencatat pendaki tiba di pos berurutan $urutan. Satu UPDATE bersyarat: baris hanya berubah
     * bila urutan yang tercatat lebih kecil, jadi dua kiriman yang tiba bersamaan tidak saling
     * menurunkan kemajuan. Pos yang dituju menjadi pos pertama sesudahnya, atau pos terakhir
     * bila semua sudah dicapai.
     *
     * @param  array<int, array{id: int, name: string, sequence: int, lat: float|null, lng: float|null}>  $checkpoints
     */
    public function tiba(HikingSession $session, array $checkpoints, int $urutan): void
    {
        $tujuan = null;

        foreach ($checkpoints as $checkpoint) {
            if ($checkpoint['sequence'] > $urutan) {
                $tujuan = $checkpoint;
                break;
            }
        }

        $tujuan ??= end($checkpoints) ?: null;

        HikingSession::query()
            ->whereKey($session->id)
            ->where(fn ($kueri) => $kueri->whereNull('reached_checkpoint_sequence')->orWhere('reached_checkpoint_sequence', '<', $urutan))
            ->update([
                'reached_checkpoint_sequence' => $urutan,
                'current_checkpoint_id' => $tujuan['id'] ?? null,
                'updated_at' => now(),
            ]);
    }
}
```

`app/Services/Trips/TripLifecycle.php`:

1. Ganti:

```php
        ]);

        $trip->hikingSession?->update([
            'status' => HikingSessionStatus::COMPLETED->value,
            'ended_at' => now(),
        ]);
```

dengan:

```php
        ]);

        // Jeda yang masih berjalan ditutup lebih dulu, supaya waktu bergerak tidak ikut
        // menghitung jeda terakhir (ruling W6-R7).
        $sesi = $trip->hikingSession;

        $sesi?->update([
            'status' => HikingSessionStatus::COMPLETED->value,
            'ended_at' => now(),
            'paused_at' => null,
            'paused_seconds' => (int) $sesi->paused_seconds + ($sesi->paused_at === null ? 0 : max(0, (int) $sesi->paused_at->diffInSeconds(now(), false))),
        ]);
```

`resources/css/app.css`:

1. Ganti:

```css
    }

    ::placeholder {
```

dengan:

```css
    }

    /*
     * Akar mode lapangan (DK-49, titipan W1): teks, kursor, dan aksen kontrol bawaan mengikuti
     * tema lapangan, bukan tema aplikasi di <html>. Di lapangan terang centang dan titik radio
     * kembali putih di atas isian merek gelap, walau tema aplikasinya gelap.
     */
    [data-tema-lapangan] {
        color: rgb(var(--text-primary));
        caret-color: rgb(var(--text-primary));
        accent-color: rgb(var(--brand-700));
    }

    :root [data-tema-lapangan='terang'] [type='checkbox']:checked {
        background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 16 16' fill='white' xmlns='http://www.w3.org/2000/svg'%3e%3cpath d='M12.207 4.793a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0l-2-2a1 1 0 011.414-1.414L6.5 9.086l4.293-4.293a1 1 0 011.414 0z'/%3e%3c/svg%3e");
    }

    :root [data-tema-lapangan='terang'] [type='radio']:checked {
        background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 16 16' fill='white' xmlns='http://www.w3.org/2000/svg'%3e%3ccircle cx='8' cy='8' r='3'/%3e%3c/svg%3e");
    }

    ::placeholder {
```

2. Ganti:

```css
        animation: toast-naik var(--dur-cepat) var(--ease-gerak);
    }
}

@keyframes lembar-naik {
```

dengan:

```css
        animation: toast-naik var(--dur-cepat) var(--ease-gerak);
    }

    /* Tiba di pos (spec v3 bagian 8.6): satu denyut pada nama pos berikutnya, tanpa ulangan. */
    .denyut-tiba {
        animation: denyut-tiba var(--dur-sedang) var(--ease-gerak);
    }
}

@keyframes denyut-tiba {
    50% {
        transform: scale(1.03);
    }
}

@keyframes lembar-naik {
```

`resources/js/components/mendaki/hitung.ts`:

1. Ganti:

```ts
    return panjang;
}
```

dengan:

```ts
    return panjang;
}

/**
 * Garis dibelah pada jarak sepanjangM dari awalnya: bagian yang sudah dilalui dan yang tersisa,
 * untuk peta mode lapangan. Titik belahnya ikut di kedua bagian supaya garis tidak terputus.
 */
export function potongGaris(garis: Titik[], sepanjangM: number): { dilalui: Titik[]; sisa: Titik[] } {
    if (garis.length < 2 || sepanjangM <= 0) {
        return { dilalui: [], sisa: garis };
    }

    let jalan = 0;

    for (let i = 1; i < garis.length; i++) {
        const a = garis[i - 1] as Titik;
        const b = garis[i] as Titik;
        const ruasM = jarakMeter(a, b);

        if (jalan + ruasM >= sepanjangM) {
            const t = ruasM === 0 ? 0 : (sepanjangM - jalan) / ruasM;
            const belah: Titik = [a[0] + t * (b[0] - a[0]), a[1] + t * (b[1] - a[1])];

            return { dilalui: [...garis.slice(0, i), belah], sisa: [belah, ...garis.slice(i)] };
        }

        jalan += ruasM;
    }

    return { dilalui: garis, sisa: [] };
}
```

`resources/js/components/peta/usePetaDasar.ts`:

1. Ganti:

```ts
import type { PropsBersama } from '@/types';

export type Peta = import('maplibre-gl').Map;
```

dengan:

```ts
import type { PropsBersama } from '@/types';
import { warnaToken } from './warnaToken';

export type Peta = import('maplibre-gl').Map;
```

2. Ganti:

```ts
    saatSiap?: (peta: Peta, maplibregl: Pustaka) => void;
}
```

dengan:

```ts
    saatSiap?: (peta: Peta, maplibregl: Pustaka) => void;
    /** Kendali perbesar bawaan MapLibre (29 px). Mode lapangan memasang kendali 48 px sendiri. */
    kendaliBawaan?: boolean;
}
```

3. Ganti:

```ts
            attributionControl: false,
            style: konfigurasi.styleUrl || {
                version: 8,
                sources: {
                    basemap: {
                        type: 'raster',
                        tiles: konfigurasi.rasterTiles ?? [],
                        tileSize: 256,
                        maxzoom: konfigurasi.maxZoom ?? 17,
                        attribution: konfigurasi.attribution ?? '',
                    },
                },
                layers: [{ id: 'basemap', type: 'raster', source: 'basemap' }],
            },
            center: pusat ?? [113, -2],
```

dengan:

```ts
            attributionControl: false,
            // Tanpa sumber tile (paket lapangan luring tahap 1, spec v3 bagian 10.5) kanvasnya
            // polos: hanya latar, tanpa permintaan jaringan apa pun.
            style:
                konfigurasi.styleUrl ||
                ((konfigurasi.rasterTiles ?? []).length === 0
                    ? { version: 8, sources: {}, layers: [{ id: 'latar', type: 'background', paint: { 'background-color': warnaToken('--surface-sunken', 'rgb(238, 234, 225)') } }] }
                    : {
                          version: 8,
                          sources: {
                              basemap: {
                                  type: 'raster',
                                  tiles: konfigurasi.rasterTiles ?? [],
                                  tileSize: 256,
                                  maxzoom: konfigurasi.maxZoom ?? 17,
                                  attribution: konfigurasi.attribution ?? '',
                              },
                          },
                          layers: [{ id: 'basemap', type: 'raster', source: 'basemap' }],
                      }),
            center: pusat ?? [113, -2],
```

4. Ganti:

```ts
        pustaka.current = maplibregl;
        petaIni.addControl(new maplibregl.NavigationControl(), 'top-right');

        // Gaya dari URL yang gagal diambil (server mati, atau luring tanpa salinan) tidak
```

dengan:

```ts
        pustaka.current = maplibregl;

        if (opsi.kendaliBawaan ?? true) {
            petaIni.addControl(new maplibregl.NavigationControl(), 'top-right');
        }

        // Gaya dari URL yang gagal diambil (server mati, atau luring tanpa salinan) tidak
```

5. Ganti:

```ts
/** Satu titik [bujur, lintang], urutan GeoJSON. */
export type Titik = [number, number];
```

dengan:

```ts
/** Satu titik [bujur, lintang], urutan GeoJSON. */
export type Titik = [number, number];

/**
 * Peta dasar mengikuti tema: basemap topografi diredupkan di tema gelap, tidak dihitamkan
 * (DK-18, M1), karena kontur adalah informasi keselamatan; kanvas polos tanpa basemap (paket
 * luring) memakai warna latar tema. Dipakai saat peta dimuat dan saat tema lapangan dibalik
 * (DK-49).
 */
export function ikutkanTemaPetaDasar(peta: Peta, gelap: boolean): void {
    if (peta.getLayer('latar')) {
        peta.setPaintProperty('latar', 'background-color', warnaToken('--surface-sunken', 'rgb(238, 234, 225)'));
    }

    if (peta.getLayer('basemap')) {
        peta.setPaintProperty('basemap', 'raster-brightness-max', gelap ? 0.78 : 1);
        peta.setPaintProperty('basemap', 'raster-saturation', gelap ? -0.25 : 0);
    }
}
```

6. Ganti:

```ts
            if (petaIni.getLayer('basemap') && window.temaGelapAktif?.()) {
                petaIni.setPaintProperty('basemap', 'raster-brightness-max', 0.78);
                petaIni.setPaintProperty('basemap', 'raster-saturation', -0.25);
            }
```

dengan:

```ts
            ikutkanTemaPetaDasar(petaIni, window.temaGelapAktif?.() ?? false);
```

`resources/js/components/peta/warnaToken.ts`:

1. Ganti:

```ts
 * dipakai bila CSS belum terbaca.
 */
export function warnaToken(nama: string, cadangan: string): string {
    const nilai = getComputedStyle(document.documentElement).getPropertyValue(nama).trim().split(/\s+/);

    return nilai.length === 3 ? `rgb(${nilai.join(', ')})` : cadangan;
```

dengan:

```ts
 * dipakai bila CSS belum terbaca.
 *
 * Di mode lapangan tokennya dibaca dari akar tema lapangan (DK-49), yang dapat berbeda dari
 * tema aplikasi di <html>.
 */
export function warnaToken(nama: string, cadangan: string): string {
    const akar = document.querySelector<HTMLElement>('[data-tema-lapangan]') ?? document.documentElement;
    const nilai = getComputedStyle(akar).getPropertyValue(nama).trim().split(/\s+/);

    return nilai.length === 3 ? `rgb(${nilai.join(', ')})` : cadangan;
```

Tulis ulang `resources/js/components/trips/PetaPendakian.tsx` utuh:

```tsx
import { usePage } from '@inertiajs/react';
import { useEffect, useRef, type ReactNode } from 'react';
import { potongGaris, type Titik } from '@/components/mendaki/hitung';
import type { PosLapangan, PosisiPerangkat } from '@/components/mendaki/types';
import { ikutkanTemaPetaDasar, kotakDari, usePetaDasar, type Peta, type Pustaka } from '@/components/peta/usePetaDasar';
import { WadahPeta } from '@/components/peta/WadahPeta';
import { warnaToken } from '@/components/peta/warnaToken';
import { fokusTerlihat, umpanTekan } from '@/components/ui/gaya';
import { Icon } from '@/components/ui/Icon';
import { kelas } from '@/lib/kelas';
import type { PropsBersama } from '@/types';

interface PetaPendakianProps {
    id: string;
    label: string;
    garis: Titik[] | null;
    pos: PosLapangan[];
    tercapai: number;
    /** Letak sepanjang garis yang sudah dilalui, meter; null bila tidak diketahui. */
    dilaluiM: number | null;
    posisi: PosisiPerangkat | null;
}

/** Kendali 48 px di atas peta, pengganti kendali bawaan MapLibre yang hanya 29 px. */
function TombolPeta({ label, onClick, className, children }: { label?: string; onClick: () => void; className?: string; children: ReactNode }) {
    return (
        <button
            type="button"
            aria-label={label}
            onClick={onClick}
            className={kelas('pointer-events-auto inline-flex min-h-12 min-w-12 items-center justify-center rounded-full border border-control bg-surface px-3 text-kecil font-bold text-primary shadow-overlay', fokusTerlihat, umpanTekan, className)}
        >
            {children}
        </button>
    );
}

/** Lingkaran akurasi sebagai poligon bermeter: lingkaran MapLibre berukuran piksel, bukan meter. */
function lingkaran(pusat: Titik, meter: number): Titik[] {
    const lintang = (pusat[1] * Math.PI) / 180;
    const derajatY = meter / 111_195;
    const derajatX = derajatY / Math.max(0.01, Math.cos(lintang));

    return Array.from({ length: 37 }, (_, i) => {
        const sudut = (i / 36) * 2 * Math.PI;

        return [pusat[0] + derajatX * Math.cos(sudut), pusat[1] + derajatY * Math.sin(sudut)] as Titik;
    });
}

/**
 * Peta mode lapangan (prototype 11): bagian jalur yang sudah dilalui bergaris utuh redup, sisa
 * jalur bergaris putus-putus merek, pos bernomor, dan posisi dengan lingkaran akurasinya.
 * Padanan non-visualnya adalah pos berikutnya dan angka di bawah peta.
 *
 * Kendali 48 px di atas peta (perbesar, perkecil, ke posisi saya) menggantikan kendali bawaan
 * MapLibre yang hanya 29 px. Tanpa peta dasar (paket luring tahap 1, spec v3 bagian 10.5),
 * kanvasnya polos dan diberi label, sehingga garis dan pos tidak terbaca sebagai peta lengkap.
 */
export function PetaPendakian({ id, label, garis, pos, tercapai, dilaluiM, posisi }: PetaPendakianProps) {
    const { peta: konfigurasi } = usePage<PropsBersama>().props;
    const tanpaPetaDasar = !konfigurasi.styleUrl && konfigurasi.rasterTiles.length === 0;
    const wadah = useRef<HTMLDivElement>(null);
    const penanda = useRef<Array<import('maplibre-gl').Marker>>([]);
    const tandaPosisi = useRef<import('maplibre-gl').Marker | null>(null);
    const terbaru = useRef({ garis, pos, tercapai, dilaluiM, posisi });

    terbaru.current = { garis, pos, tercapai, dilaluiM, posisi };

    const gambar = (map: Peta, maplibregl: Pustaka) => {
        const { garis: g, pos: p, tercapai: t, dilaluiM: d, posisi: s } = terbaru.current;

        if (g && g.length > 1) {
            const belah = potongGaris(g, d ?? 0);
            const dilalui = { type: 'Feature' as const, properties: {}, geometry: { type: 'LineString' as const, coordinates: belah.dilalui.length > 1 ? belah.dilalui : [] } };
            const sisa = { type: 'Feature' as const, properties: {}, geometry: { type: 'LineString' as const, coordinates: belah.sisa.length > 1 ? belah.sisa : [] } };
            const sumberDilalui = map.getSource('jalur-dilalui') as import('maplibre-gl').GeoJSONSource | undefined;
            const sumberSisa = map.getSource('jalur-sisa') as import('maplibre-gl').GeoJSONSource | undefined;

            if (sumberDilalui && sumberSisa) {
                sumberDilalui.setData(dilalui);
                sumberSisa.setData(sisa);
            }
        }

        penanda.current.forEach((tanda) => tanda.remove());
        penanda.current = p
            .filter((c) => c.lng !== null && c.lat !== null)
            .map((c) => {
                const el = document.createElement('div');
                const berikutnya = c.urutan === p.find((x) => x.urutan > t)?.urutan;

                el.className = kelas(
                    'flex size-7 items-center justify-center rounded-full border-2 text-meta font-bold tabular-nums',
                    berikutnya ? 'border-primary bg-aksen-pos text-aksen-pos-teks' : c.urutan <= t ? 'border-surface bg-brand-700 text-on-brand' : 'border-brand-700 bg-surface text-primary',
                );
                el.textContent = String(c.urutan);
                el.title = `${c.urutan}. ${c.nama}`;

                return new maplibregl.Marker({ element: el }).setLngLat([c.lng as number, c.lat as number]).addTo(map);
            });

        const akurasi = map.getSource('akurasi') as import('maplibre-gl').GeoJSONSource | undefined;

        if (s) {
            akurasi?.setData({ type: 'Feature', properties: {}, geometry: { type: 'Polygon', coordinates: [lingkaran([s.lng, s.lat], s.akurasi)] } });

            if (tandaPosisi.current) {
                tandaPosisi.current.setLngLat([s.lng, s.lat]);
            } else {
                const el = document.createElement('div');
                el.className = 'size-5 rounded-full border-4 border-surface bg-primary shadow-overlay';
                el.title = 'Posisi Anda';
                tandaPosisi.current = new maplibregl.Marker({ element: el }).setLngLat([s.lng, s.lat]).addTo(map);
            }
        }
    };

    const pertama = pos.find((c) => c.lng !== null && c.lat !== null);
    const { peta, pustaka, siap, gagal } = usePetaDasar(wadah, {
        pusat: pertama ? [pertama.lng as number, pertama.lat as number] : posisi ? [posisi.lng, posisi.lat] : null,
        kendaliBawaan: false,
        saatDibuat: () => {
            tandaPosisi.current = null;
            penanda.current = [];
        },
        saatSiap: (map, maplibregl) => {
            const kosong = { type: 'Feature' as const, properties: {}, geometry: { type: 'LineString' as const, coordinates: [] as Titik[] } };
            const tepi = warnaToken('--surface', '#ffffff');

            map.addSource('akurasi', { type: 'geojson', data: { type: 'Feature', properties: {}, geometry: { type: 'Polygon', coordinates: [] } } });
            map.addLayer({ id: 'akurasi-isi', type: 'fill', source: 'akurasi', paint: { 'fill-color': warnaToken('--brand-700', 'rgb(35, 79, 50)'), 'fill-opacity': 0.15 } });
            map.addSource('jalur', { type: 'geojson', data: { ...kosong, geometry: { type: 'LineString', coordinates: terbaru.current.garis ?? [] } } });
            map.addSource('jalur-dilalui', { type: 'geojson', data: kosong });
            map.addSource('jalur-sisa', { type: 'geojson', data: kosong });
            map.addLayer({ id: 'garis-jalur-tepi', type: 'line', source: 'jalur', layout: { 'line-cap': 'round', 'line-join': 'round' }, paint: { 'line-color': tepi, 'line-width': 8 } });
            map.addLayer({ id: 'garis-dilalui', type: 'line', source: 'jalur-dilalui', layout: { 'line-cap': 'round', 'line-join': 'round' }, paint: { 'line-color': warnaToken('--text-muted', 'rgb(98, 105, 101)'), 'line-width': 4 } });
            map.addLayer({ id: 'garis-sisa', type: 'line', source: 'jalur-sisa', layout: { 'line-cap': 'round', 'line-join': 'round' }, paint: { 'line-color': warnaToken('--brand-700', 'rgb(35, 79, 50)'), 'line-width': 5, 'line-dasharray': [1.5, 1] } });

            const { garis: g, pos: p } = terbaru.current;
            const titik = [...(g ?? []), ...p.filter((c) => c.lng !== null && c.lat !== null).map((c) => [c.lng, c.lat] as Titik)];

            if (titik.length > 1) {
                map.fitBounds(kotakDari(maplibregl, titik), { padding: 48, duration: 0 });
            }

            gambar(map, maplibregl);
        },
    });

    useEffect(() => {
        if (siap && peta.current && pustaka.current) {
            gambar(peta.current, pustaka.current);
        }
    }, [siap, tercapai, dilaluiM, posisi?.lng, posisi?.lat, posisi?.akurasi]);

    // Tema lapangan dibalik sekali ketuk (DK-49) tanpa membangun ulang peta: peredupan peta
    // dasar dan warna lapisan dibaca ulang dari akar tema lapangan setiap kali atributnya
    // berubah. Penanda pos ikut sendiri karena kelasnya membaca token CSS.
    useEffect(() => {
        const akar = wadah.current?.closest<HTMLElement>('[data-tema-lapangan]');

        if (!siap || !akar) {
            return;
        }

        const ikutiTema = () => {
            const map = peta.current;

            if (!map) {
                return;
            }

            ikutkanTemaPetaDasar(map, akar.dataset.temaLapangan === 'gelap');
            map.setPaintProperty('akurasi-isi', 'fill-color', warnaToken('--brand-700', 'rgb(35, 79, 50)'));
            map.setPaintProperty('garis-jalur-tepi', 'line-color', warnaToken('--surface', '#ffffff'));
            map.setPaintProperty('garis-dilalui', 'line-color', warnaToken('--text-muted', 'rgb(98, 105, 101)'));
            map.setPaintProperty('garis-sisa', 'line-color', warnaToken('--brand-700', 'rgb(35, 79, 50)'));
        };

        ikutiTema();
        const pengamat = new MutationObserver(ikutiTema);
        pengamat.observe(akar, { attributes: true, attributeFilter: ['data-tema-lapangan'] });

        return () => pengamat.disconnect();
    }, [siap]);

    const keKeposisi = () => {
        if (peta.current && posisi) {
            peta.current.easeTo({ center: [posisi.lng, posisi.lat], zoom: Math.max(peta.current.getZoom(), 15), duration: 0 });
        }
    };

    return (
        <WadahPeta
            wadahRef={wadah}
            id={id}
            label={label}
            gagal={gagal}
            ukuran="h-[45dvh] min-h-64"
            keterangan={tanpaPetaDasar ? 'Peta dasar tidak tersedia luring; garis dan pos dari paket.' : undefined}
            cadangan="Pos berikutnya dan angka di bawah tetap dapat dipakai."
        >
            {tanpaPetaDasar ? <p className="pointer-events-none absolute left-3 top-3 rounded-kecil bg-surface px-2 py-1 text-meta font-bold text-secondary">Peta dasar tidak tersedia luring</p> : null}
            <div className="pointer-events-none absolute right-3 top-3 flex flex-col gap-2">
                <TombolPeta label="Perbesar peta" onClick={() => peta.current?.zoomIn({ duration: 0 })}>
                    <Icon name="plus" size={22} />
                </TombolPeta>
                <TombolPeta label="Perkecil peta" onClick={() => peta.current?.zoomOut({ duration: 0 })}>
                    <Icon name="minus" size={22} />
                </TombolPeta>
            </div>
            {posisi ? (
                <TombolPeta onClick={keKeposisi} className="absolute bottom-3 right-3">
                    Posisi saya
                </TombolPeta>
            ) : null}
        </WadahPeta>
    );
}
```

Tulis ulang `resources/js/components/trips/TataLetakPendakian.tsx` utuh:

```tsx
import { useState, type ReactNode } from 'react';
import { fokusTerlihat, umpanTekan } from '@/components/ui/gaya';
import { Icon } from '@/components/ui/Icon';
import { JudulHalaman } from '@/components/ui/JudulHalaman';
import { LencanaDataContoh } from '@/components/ui/LencanaDataContoh';
import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';

export type TemaLapangan = 'terang' | 'gelap';

interface TataLetakPendakianProps {
    /** Ruang kerja trip; null di paket luring, karena halaman lain tidak dapat dibuka tanpa sinyal. */
    kembali: string | null;
    /** Satu h1 dari halaman (PageHeaderContractTest). */
    judul: ReactNode;
    /** Keadaan dan lama berjalan, dibaca sekilas di bawah judul. */
    keterangan: string;
    children: ReactNode;
}

const tautanKembali = kelas('inline-flex h-12 min-w-12 shrink-0 items-center justify-center rounded-full border border-control bg-surface px-3 text-primary', fokusTerlihat, umpanTekan);

/**
 * Tata letak mode lapangan (spec v3 bagian 6.12, prototype 11). Tanpa bilah tab, strip, dan
 * navigasi lima permukaan: di jalur, dengan sarung tangan dan layar berkeringat, setiap tautan
 * yang tidak dibutuhkan adalah sasaran salah sentuh. Yang tersisa jalan kembali ke ruang kerja
 * trip (pendakian tetap berjalan dan dibuka lagi dari strip), judul, dan sakelar tema.
 *
 * Tema lapangan (DK-49) mengikuti tema pengguna lalu dapat dibalik sekali ketuk. Atributnya di
 * akar halaman ini, bukan di <html>, jadi tema aplikasi tidak berubah sesudah keluar dari mode
 * lapangan; warna teks, kursor, dan aksen kontrol diturunkan dari akar ini (app.css).
 */
export function TataLetakPendakian({ kembali, judul, keterangan, children }: TataLetakPendakianProps) {
    const [tema, setTema] = useState<TemaLapangan>(() => (window.temaGelapAktif?.() ? 'gelap' : 'terang'));

    return (
        <div data-tema-lapangan={tema} className="min-h-dvh bg-canvas text-primary">
            <JudulHalaman />
            <header className="sticky top-0 z-20 border-b border-subtle bg-surface">
                <div className="mx-auto flex max-w-3xl items-center gap-3 px-4 py-2 sm:px-6">
                    {kembali ? (
                        <Tautan href={kembali} aria-label="Kembali ke detail trip. Pendakian tetap berjalan." className={tautanKembali}>
                            <Icon name="arrow-left" size={22} />
                        </Tautan>
                    ) : null}
                    <div className="min-w-0 flex-1">
                        {judul}
                        <p className="text-kecil text-secondary">{keterangan}</p>
                        <LencanaDataContoh />
                    </div>
                    <button
                        type="button"
                        aria-label={tema === 'gelap' ? 'Ganti ke tema lapangan terang' : 'Ganti ke tema lapangan gelap'}
                        onClick={() => setTema(tema === 'gelap' ? 'terang' : 'gelap')}
                        className={kelas('inline-flex min-h-12 min-w-12 shrink-0 items-center justify-center rounded-full border border-control bg-surface px-3 text-kecil font-bold text-primary', fokusTerlihat, umpanTekan)}
                    >
                        {tema === 'gelap' ? 'Terang' : 'Gelap'}
                    </button>
                </div>
            </header>
            <main id="konten" tabIndex={-1} className="mx-auto max-w-3xl px-4 pb-8 sm:px-6">
                {children}
            </main>
        </div>
    );
}
```

`resources/js/components/trips/usePendakian.ts`:

1. Ganti:

```ts
import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

interface ModulJejak {
```

dengan:

```ts
import { useEffect } from 'react';

interface ModulJejak {
```

2. Ganti:

```ts
/**
 * Posisi diambil hanya ketika pendaki memintanya, dan hanya di halaman ini (PRD §120).
 * getCurrentPosition, bukan watchPosition: mode pendakian tidak melacak di latar
 * belakang (PRD §53-54). Posisinya dikirim ke server yang menentukan pos berikutnya.
 */
export function usePosisiPendakian(urlKirim: string) {
    const [pesan, setPesan] = useState('Izin lokasi belum diminta.');
    const [memproses, setMemproses] = useState(false);

    const minta = () => {
        if (!navigator.geolocation) {
            setPesan('Browser Anda tidak mendukung layanan lokasi.');
            return;
        }

        setMemproses(true);
        setPesan('Mengambil posisi...');

        navigator.geolocation.getCurrentPosition(
            (posisi) => {
                router.post(
                    urlKirim,
                    { latitude: posisi.coords.latitude, longitude: posisi.coords.longitude },
                    {
                        preserveScroll: true,
                        preserveState: true,
                        onSuccess: () => setPesan('Posisi diperbarui.'),
                        onError: (galat) => setPesan(galat.latitude ?? galat.longitude ?? 'Posisi tidak dapat disimpan.'),
                        // Di jalur sinyal sering hilang. Mengaku gagal lebih jujur daripada
                        // membiarkan jarak lama terbaca sebagai jarak sekarang.
                        onNetworkError: () => {
                            setPesan('Posisi belum terkirim karena tidak ada sinyal. Coba lagi saat sinyal kembali.');
                            return false;
                        },
                        onFinish: () => setMemproses(false),
                    },
                );
            },
            () => {
                setPesan('Posisi tidak dapat diambil. Periksa izin lokasi browser.');
                setMemproses(false);
            },
            { enableHighAccuracy: true, timeout: 15000 },
        );
    };

    return { pesan, memproses, minta };
}

/**
 * Perekaman jejak berjalan sendiri selama halaman ini terbuka, dan hanya bila pendaki
 * menyalakannya di profilnya (penjagaan yang sama ada di server).
 *
```

dengan:

```ts
/**
 * Perekaman jejak berjalan sendiri selama mode lapangan terbuka, dan hanya bila pendaki
 * menyalakannya di profilnya (penjagaan yang sama ada di server). Posisi mode lapangan sendiri
 * diminta di components/mendaki/usePosisiLapangan.ts dan tidak dikirim ke mana pun.
 *
```

Tulis ulang `resources/js/pages/Trips/Hike.tsx` utuh:

```tsx
import { usePage } from '@inertiajs/react';
import { teksLama } from '@/components/mendaki/hitung';
import { ModeLapangan, tombolLapangan } from '@/components/mendaki/ModeLapangan';
import type { DataLapangan } from '@/components/mendaki/types';
import { useModeLapangan } from '@/components/mendaki/useModeLapangan';
import { TataLetakPendakian } from '@/components/trips/TataLetakPendakian';
import { useJejakPendakian } from '@/components/trips/usePendakian';
import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';
import type { PropsBersama } from '@/types';

type Props = PropsBersama & DataLapangan;

/**
 * Mode lapangan (FR-15; spec v3 bagian 6.12, prototype 11). Satu pohon komponen untuk dua
 * sumber data: props server saat daring dan isi paket lapangan saat luring (`sumber`). Lokasi
 * diminta di halaman ini dan tidak di tempat lain (PRD §120); hitungannya di perangkat.
 */
export default function Hike() {
    const data = usePage<Props>().props;
    const mode = useModeLapangan(data);
    const daring = data.sumber === 'daring';

    useJejakPendakian(data.rekamJejak && data.trip.berlangsung, data.sesiId, data.urls.jejak);

    const keterangan = data.trip.berlangsung ? `${mode.dijeda ? 'Dijeda' : 'Berjalan'}, ${teksLama(mode.detik)} bergerak` : 'Belum dimulai';

    return (
        <TataLetakPendakian kembali={daring ? data.urls.ruangKerja : null} judul={<h1 className="break-words text-bagian text-primary">{data.trip.jalur}</h1>} keterangan={keterangan}>
            {data.trip.berlangsung ? null : (
                <p className="mt-4 rounded-kartu border border-subtle bg-surface p-4 text-isi text-primary">
                    Pendakian ini belum dimulai. Mulai dari tab Mendaki atau ruang kerja trip; sebelum itu peta dan pos dapat ditinjau di sini.
                </p>
            )}
            <ModeLapangan
                data={data}
                mode={mode}
                selesai={
                    daring ? (
                        <Tautan href={data.urls.ruangKerja} className={kelas(tombolLapangan, 'bg-brand-700 text-on-brand')}>
                            Selesai
                        </Tautan>
                    ) : null
                }
            />
        </TataLetakPendakian>
    );
}
```

`routes/web.php`:

1. Ganti:

```php
    Route::get('trips/{trip}/hike', [HikeModeController::class, 'show'])->name('trips.hike');
    Route::post('trips/{trip}/hike/position', [HikeModeController::class, 'position'])->name('trips.hike.position');

    /*
```

dengan:

```php
    Route::get('trips/{trip}/hike', [HikeModeController::class, 'show'])->name('trips.hike');
    // Aksi mode lapangan (W6): urutan pos dan waktu jeda, tanpa posisi. Dikirim antrean
    // perangkat dan aman diputar ulang, dengan hitungan mode-lapangan sendiri.
    Route::post('trips/{trip}/hike/tiba', [HikeModeController::class, 'arrive'])->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,mode-lapangan')->name('trips.hike.arrive');
    Route::post('trips/{trip}/hike/jeda', [HikeModeController::class, 'pause'])->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,mode-lapangan')->name('trips.hike.pause');
    Route::post('trips/{trip}/hike/lanjut', [HikeModeController::class, 'resume'])->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,mode-lapangan')->name('trips.hike.resume');

    /*
```

`tests/Pratinjau/HalamanPratinjauTest.php`:

1. Ganti:

```php
        $this->tulisPratinjau('dasbor-b3', $this->get(self::ASAL.'/dashboard'), 'Dashboard');
    }
```

dengan:

```php
        $this->tulisPratinjau('dasbor-b3', $this->get(self::ASAL.'/dashboard'), 'Dashboard');
    }

    /**
     * Mode lapangan (W6, prototype 11): Budi sedang mendaki Gede via Cibodas, sudah mencapai
     * pos pertama, berjalan 2 jam 15 menit. Posisi perangkat diberikan peramban saat dipotret.
     */
    public function test_mode_lapangan(): void
    {
        $budi = $this->sebagaiBudi();
        $trip = TripPlan::where('user_id', $budi->id)->where('status', TripStatus::PLANNED->value)->sole();
        $trip->update(['status' => TripStatus::IN_PROGRESS->value]);

        HikingSession::create([
            'trip_plan_id' => $trip->id,
            'user_id' => $budi->id,
            'reached_checkpoint_sequence' => 1,
            'current_checkpoint_id' => Checkpoint::where('trail_id', $trip->trail_id)->where('sequence', 2)->value('id'),
            'status' => HikingSessionStatus::ACTIVE->value,
            'started_at' => now()->subMinutes(135),
        ]);

        // Koordinat pos untuk pratinjau saja: DemoSeeder tidak mencatat koordinat pos, dan tanpa
        // koordinat peta dan sisa jarak tidak dapat dipotret. Enam pos berjajar ke barat daya.
        Checkpoint::where('trail_id', $trip->trail_id)->orderBy('sequence')->get()
            ->each(fn (Checkpoint $pos, int $i) => $pos->setCoordinates(-6.7400 - 0.0080 * $i, 107.0060 - 0.0030 * $i));

        $this->tulisPratinjau('mode-lapangan', $this->get(self::ASAL.'/trips/'.$trip->id.'/hike'), 'Trips/Hike');
    }
```

Hapus berkas `app/Http/Requests/Trips/UpdateHikePositionRequest.php`.

- [ ] **Step 4: Build dan tipe.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`
Expected: `✓ built`; keluaran Wayfinder memuat `trips.hike.arrive`, `pause`, `resume` dan tidak lagi `position`.

Run: `npx tsc --noEmit`
Expected: keluar tanpa keluaran galat.

- [ ] **Step 5: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Services/Trips/HalamanMendaki.php app/Http/Requests/Trips/RecordArrivalRequest.php app/Http/Requests/Trips/PauseHikeRequest.php app/Http/Controllers/Trips/HikeModeController.php app/Services/Trips/HikeCheckpointResolver.php app/Models/HikingSession.php app/Services/Trips/TripLifecycle.php routes/web.php database/migrations/2026_10_07_000200_add_pause_to_hiking_sessions_table.php tests/Feature/HikeModeCheckpointTest.php tests/Feature/FieldModeFiguresTest.php tests/Feature/LocationPrivacyTest.php tests/Feature/MapConfigTest.php tests/Feature/TripOwnershipTest.php tests/Feature/TripReactPagesTest.php tests/Pratinjau/HalamanPratinjauTest.php`, lalu `php artisan test --compact --filter='FieldModeFigures|HikeModeCheckpoint|LocationPrivacy|MapConfig|TripOwnership|TripReactPages'`
Expected: PASS. Pada simulasi rencana: 56 test lulus.

- [ ] **Step 6: Suite, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 1.992 test, 1.990 lulus, 2 dilewati (sebelum tugas: 1.984).

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `php artisan test --compact --testsuite=Pratinjau`
Expected: PASS; halaman baru `mode-lapangan`. Pada simulasi rencana: 23 test lulus.

- [ ] **Step 7: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 4: Pintu tab Mendaki

Spec bagian 6.12 ("Pintu tab"); induk W6 butir 1 (`TrackTabTest` diperluas); ruling W6-R3 dan W6-R14. Pendakian
berlangsung tetap langsung membuka mode lapangan. Tanpa itu, trip terdekat yang dapat dimulai mendapat pratinjau hari-H
(kapan, jalur, peserta, keadaan paket luring, cara lokasi dipakai, rekam jejak mati bawaan, cek kesiapan terakhir
tanpa kata vonis) dengan "Mulai pendakian" 66 px yang meminta konfirmasi sebaris dan menulis kalimat jujur bila sinyal
hilang (W2-5j); trip lain yang dapat dimulai didaftar di bawahnya. Selama sakelar paket mati, baris paket berbunyi
jujur tanpa tombol unduh.

**Files:**
- Create: `app/Services/Trips/PintuMendaki.php`
- Modify: `app/Http/Controllers/Trips/TrackController.php` (ditulis ulang utuh), `config/hiking.php`, `resources/js/pages/Track/Index.tsx` (ditulis ulang utuh)
- Test: `tests/Feature/TrackTabTest.php` (diperbarui; niat setiap test lama tetap)

**Interfaces:**
- Consumes: `PendakianAktif::untuk()` (Tugas 1); `KepalaTrip::untuk(TripPlan): array` (W4: `id`, `nama`, `jalur`, `gunung`, `jenis`, `hari`, `tanggal`, `jam`, `peserta`); `TripPlan::latestReadinessCheck`; rute `trips.start`, `trips.show`, `trips.readiness`, `trips.hike`, `profile`; primitif `Button`, `EmptyState`, `PageHeader` (`akar`), `Tautan`.
- Produces: `PintuMendaki::untuk(User $pengguna): array{hariH: ?array, lain: array, lokasi: string, jejak: array{menyala: bool, kalimat: string, url: string}, paket: array{aktif: bool, kalimat: string}}`; config `hiking.field_pack.enabled` (`FIELD_PACK_ENABLED`, bawaan `false`); props `Track/Index` di atas.

- [ ] **Step 1: Tulis test yang gagal.**

`tests/Feature/TrackTabTest.php`:

1. Ganti:

```php
namespace Tests\Feature;

use App\Enums\TripStatus;
use App\Models\TripPlan;
```

dengan:

```php
namespace Tests\Feature;

use App\Enums\ReadinessState;
use App\Enums\TripStatus;
use App\Models\ReadinessCheck;
use App\Models\Trail;
use App\Models\TripPlan;
```

2. Ganti:

```php
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;
```

dengan:

```php
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
```

3. Ganti:

```php
/**
 * Tab Mendaki (IA v2, DK-25): pintu mode lapangan.
 *
 * Pendaki yang mengetuk tab ini di jalur butuh mode pendakiannya, bukan daftar. Tanpa
 * pendakian berjalan, tab menampilkan trip yang benar-benar dapat dimulai.
 */
```

dengan:

```php
/**
 * Tab Mendaki (IA v2, DK-25; spec v3 bagian 6.12; rencana induk W6 butir 1): pintu mode lapangan.
 *
 * Pendaki yang mengetuk tab ini di jalur butuh mode lapangannya, bukan daftar. Tanpa pendakian
 * berlangsung, tab menampilkan pratinjau hari-H trip terdekat yang dapat dimulai (keadaan paket
 * luring, penjelasan izin lokasi, rekam jejak yang mati bawaan) dan trip lain yang dapat dimulai.
 * Status trip "Siap berangkat" dan kata keadaan kesiapan tidak tampil (ruling W4-C4 untuk
 * Mendaki, W6-R9).
 */
```

4. Ganti:

```php
    use RefreshDatabase;

    public function test_a_hike_in_progress_opens_straight_into_hike_mode(): void
    {
```

dengan:

```php
    use RefreshDatabase;

    public function test_a_hike_in_progress_opens_straight_into_field_mode(): void
    {
```

5. Ganti:

```php
    }

    public function test_only_trips_that_can_still_be_started_are_offered(): void
    {
```

dengan:

```php
    }

    public function test_the_nearest_startable_trip_gets_a_day_preview_and_the_rest_are_listed(): void
    {
```

6. Ganti:

```php
                ->where('title', 'Mendaki')
                ->has('trips', 2)
                ->where('trips.0.id', $siap->id)
                ->where('trips.1.id', $rencana->id)
                ->where('trips.0.urls.start', route('trips.start', $siap)));
    }
```

dengan:

```php
                ->where('title', 'Mendaki')
                ->where('hariH.id', $siap->id)
                ->where('hariH.hari', 'H-1')
                ->where('hariH.cek', 'Cek kesiapan belum dilakukan untuk trip ini.')
                ->where('hariH.urls.mulai', route('trips.start', $siap))
                ->where('hariH.urls.lapangan', route('trips.hike', $siap))
                ->has('lain', 1)
                ->where('lain.0.id', $rencana->id)
                ->where('lain.0.urls.mulai', route('trips.start', $rencana)));
    }

    /**
     * Pintu Mendaki tidak menyebut status trip ("Siap berangkat", "Direncanakan") maupun
     * vonis kesiapan: cek kesiapan disebut tanggalnya, dan tautannya membuka hasilnya (R-014).
     */
    public function test_no_status_word_or_verdict_reaches_the_tab(): void
    {
        $user = User::factory()->create();
        $trail = Trail::factory()->create();
        $trail->mountain->update(['timezone' => 'Asia/Makassar']);
        $trip = TripPlan::factory()->for($user)->for($trail)->create(['status' => TripStatus::READY_FOR_DEPARTURE->value, 'planned_date' => Carbon::now('Asia/Makassar')->addDays(3)->toDateString()]);

        ReadinessCheck::create([
            'trip_plan_id' => $trip->id,
            'computed_state' => ReadinessState::READY->value,
            'computed_at' => Carbon::parse('2026-10-06 18:30:00', 'UTC'),
        ]);

        $respons = $this->actingAs($user)->get(route('track'))->assertOk();

        $respons->assertInertia(fn (AssertableInertia $page) => $page->where('hariH.cek', 'Cek kesiapan terakhir 7 Okt 2026.'));

        foreach (['Siap berangkat', 'Direncanakan', ReadinessState::READY->label()] as $kata) {
            $this->assertStringNotContainsString($kata, json_encode($respons->viewData('page')['props'], JSON_UNESCAPED_UNICODE));
        }
    }

    public function test_location_and_track_recording_are_explained_before_starting(): void
    {
        $user = User::factory()->create();
        TripPlan::factory()->for($user)->create(['status' => TripStatus::PLANNED->value]);

        $this->actingAs($user)->get(route('track'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('lokasi', 'Lokasi diminta saat mode lapangan terbuka dan hanya dipakai di perangkat ini. Posisi Anda tidak dikirim ke server.')
                ->where('jejak.menyala', false)
                ->where('jejak.kalimat', 'Rekam jejak mati. Nyalakan di Profil bila Anda ingin menyimpan jejak pendakian ini.')
                ->where('jejak.url', route('profile')));

        $user->preference()->create(['record_track' => true]);

        $this->actingAs($user->fresh())->get(route('track'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('jejak.menyala', true)
                ->where('jejak.kalimat', 'Rekam jejak menyala: titik posisi disimpan di perangkat, dikirim sesudah sinyal kembali, dan dapat Anda hapus di ringkasan pendakian.'));
    }

    /**
     * Paket lapangan luring dibangun tetapi tidak dibuka sebelum pemilik memutus Q10 dan Q14
     * (ruling W6-R3): tanpa sakelar, tab tidak menawarkan unduhan dan mengatakannya.
     */
    public function test_the_field_pack_is_offered_only_when_switched_on(): void
    {
        $user = User::factory()->create();
        TripPlan::factory()->for($user)->create(['status' => TripStatus::PLANNED->value]);

        $this->actingAs($user)->get(route('track'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('paket.aktif', false)
                ->where('paket.kalimat', 'Paket lapangan luring belum dibuka. Buka mode lapangan selagi ada sinyal dan biarkan tetap terbuka, lalu bawa peta cetak atau perangkat lain sebagai cadangan.'));

        config(['hiking.field_pack.enabled' => true]);

        $this->actingAs($user)->get(route('track'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('paket.aktif', true)
                ->where('paket.kalimat', 'Paket luring membantu mengikuti jalur resmi. Bawa cadangan: peta cetak atau perangkat lain.'));
    }
```

7. Ganti:

```php
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->has('trips', 0));
    }
```

dengan:

```php
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('hariH', null)->has('lain', 0));
    }

    public function test_the_tab_costs_the_same_with_one_or_ten_trips(): void
    {
        $sedikit = User::factory()->create();
        TripPlan::factory()->for($sedikit)->create(['status' => TripStatus::PLANNED->value]);

        $banyak = User::factory()->create();
        TripPlan::factory()->count(10)->for($banyak)->create(['status' => TripStatus::PLANNED->value]);

        $this->assertSame($this->hitungKueri($sedikit), $this->hitungKueri($banyak));
    }
```

8. Ganti:

```php
    }
}
```

dengan:

```php
    }

    private function hitungKueri(User $user): int
    {
        $this->actingAs($user)->get(route('track'))->assertOk();

        $kueri = 0;
        DB::listen(function () use (&$kueri) {
            $kueri++;
        });

        $this->actingAs($user)->get(route('track'))->assertOk();
        DB::getEventDispatcher()->forget('Illuminate\Database\Events\QueryExecuted');

        return $kueri;
    }
}
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='TrackTab'`
Expected: FAIL. Pada simulasi rencana: 8 test, 3 lulus, 5 gagal. Yang merah: lima test `TrackTabTest` (props `hariH`, `lain`, `lokasi`, `jejak`, dan `paket` belum ada). Tiga yang lain sudah lulus karena menjaga perilaku yang tidak berubah: pendakian berlangsung langsung membuka mode lapangan, biaya kueri tab tetap, dan tamu diarahkan masuk.

- [ ] **Step 3: Tulis implementasinya.** Kelas dibuat dengan
`DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:class --no-interaction Services/Trips/PintuMendaki`,
lalu isinya diganti persis.

Buat `app/Services/Trips/PintuMendaki.php`:

```php
<?php

namespace App\Services\Trips;

use App\Enums\TripStatus;
use App\Models\TripPlan;
use App\Models\User;
use App\Support\Timezone;
use Illuminate\Support\Arr;

/**
 * Pintu tab Mendaki tanpa pendakian berlangsung (spec v3 bagian 6.12; rencana induk W6 butir 1).
 *
 * Trip terdekat yang dapat dimulai mendapat pratinjau hari-H: kapan, keadaan paket luring,
 * bagaimana lokasi dipakai, dan apakah rekam jejak menyala, sebelum satu tombol "Mulai
 * pendakian". Trip lain yang dapat dimulai didaftar di bawahnya. Status trip dan vonis kesiapan
 * tidak disebut (ruling W4-C4 untuk Mendaki, W6-R9): cek kesiapan hanya disebut tanggalnya.
 *
 * Kueri tetap berapa pun jumlah trip: satu kueri trip berbatas dengan relasi yang dimuat
 * sekaligus.
 */
final class PintuMendaki
{
    private const BATAS_TRIP = 10;

    public function __construct(private readonly KepalaTrip $kepala) {}

    /**
     * @return array{hariH: array<string, mixed>|null, lain: array<int, array<string, mixed>>, lokasi: string, jejak: array{menyala: bool, kalimat: string, url: string}, paket: array{aktif: bool, kalimat: string}}
     */
    public function untuk(User $pengguna): array
    {
        $trips = TripPlan::query()
            ->whereBelongsTo($pengguna)
            ->whereIn('status', [TripStatus::READY_FOR_DEPARTURE->value, TripStatus::PLANNED->value])
            ->whereDate('planned_date', '>=', Timezone::earliestDateInIndonesia())
            ->with('trail.mountain', 'latestReadinessCheck')
            ->orderBy('planned_date')
            ->orderByRaw('case when status = ? then 0 else 1 end', [TripStatus::READY_FOR_DEPARTURE->value])
            ->orderBy('id')
            ->limit(self::BATAS_TRIP)
            ->get();

        $rekam = (bool) $pengguna->preference?->record_track;
        $paket = (bool) config('hiking.field_pack.enabled');

        return [
            'hariH' => $trips->isEmpty() ? null : $this->pratinjau($trips->first()),
            'lain' => $trips->slice(1)->map(fn (TripPlan $trip) => [
                'id' => $trip->id,
                'nama' => $trip->name,
                'jalur' => $trip->trail->name,
                'tanggal' => KepalaTrip::rentang($trip->planned_date, $trip->end_date),
                'hari' => KepalaTrip::hariMenuju($trip->planned_date, Timezone::forTrail($trip->trail)),
                'urls' => ['mulai' => route('trips.start', $trip), 'ruangKerja' => route('trips.show', $trip)],
            ])->values()->all(),
            'lokasi' => 'Lokasi diminta saat mode lapangan terbuka dan hanya dipakai di perangkat ini. Posisi Anda tidak dikirim ke server.',
            'jejak' => [
                'menyala' => $rekam,
                'kalimat' => $rekam
                    ? 'Rekam jejak menyala: titik posisi disimpan di perangkat, dikirim sesudah sinyal kembali, dan dapat Anda hapus di ringkasan pendakian.'
                    : 'Rekam jejak mati. Nyalakan di Profil bila Anda ingin menyimpan jejak pendakian ini.',
                'url' => route('profile'),
            ],
            'paket' => [
                'aktif' => $paket,
                'kalimat' => $paket
                    ? 'Paket luring membantu mengikuti jalur resmi. Bawa cadangan: peta cetak atau perangkat lain.'
                    : 'Paket lapangan luring belum dibuka. Buka mode lapangan selagi ada sinyal dan biarkan tetap terbuka, lalu bawa peta cetak atau perangkat lain sebagai cadangan.',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function pratinjau(TripPlan $trip): array
    {
        $cek = $trip->latestReadinessCheck?->computed_at;

        return [
            ...Arr::only($this->kepala->untuk($trip), ['id', 'nama', 'jalur', 'gunung', 'jenis', 'hari', 'tanggal', 'jam', 'peserta']),
            'cek' => $cek === null
                ? 'Cek kesiapan belum dilakukan untuk trip ini.'
                : 'Cek kesiapan terakhir '.$cek->copy()->setTimezone(Timezone::forTrail($trip->trail))->translatedFormat('j M Y').'.',
            'urls' => [
                'mulai' => route('trips.start', $trip),
                'ruangKerja' => route('trips.show', $trip),
                'cek' => route('trips.readiness', $trip),
                'lapangan' => route('trips.hike', $trip),
            ],
        ];
    }
}
```

Tulis ulang `app/Http/Controllers/Trips/TrackController.php` utuh:

```php
<?php

namespace App\Http\Controllers\Trips;

use App\Http\Controllers\Controller;
use App\Services\Trips\PendakianAktif;
use App\Services\Trips\PintuMendaki;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pintu tab Mendaki (IA v2, DK-25; spec v3 bagian 6.12).
 *
 * Pendakian yang sedang berjalan dibuka langsung: pendaki yang mengetuk tab ini di jalur
 * butuh mode lapangannya, bukan daftar. Pemilihnya sama dengan strip dan Beranda
 * (PendakianAktif, ruling W6-R2). Tanpa pendakian berjalan, halaman menampilkan pratinjau
 * hari-H trip terdekat yang dapat dimulai (PintuMendaki). Semua kueri milik pengguna yang masuk.
 */
class TrackController extends Controller
{
    public function __invoke(Request $request, PendakianAktif $aktif, PintuMendaki $pintu): Response|RedirectResponse
    {
        $user = $request->user();
        $berjalan = $aktif->untuk($user);

        if ($berjalan !== null) {
            return redirect()->route('trips.hike', $berjalan['id']);
        }

        return Inertia::render('Track/Index', [
            'title' => 'Mendaki',
            ...$pintu->untuk($user),
        ]);
    }
}
```

`config/hiking.php`:

1. Ganti:

```php
        'earth_radius_m' => 6371000,
    ],
```

dengan:

```php
        'earth_radius_m' => 6371000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Paket lapangan luring (spec v3 bagian 10, DK-36; ruling W6-R3)
    |--------------------------------------------------------------------------
    |
    | Paket dibangun dan diuji, tetapi tidak dibuka untuk pengguna sampai pemilik memutus
    | Q10 (kebijakan data di perangkat) dan Q14 (telaah hukum). Selama mati, tab Mendaki dan
    | ruang kerja trip tidak menawarkan unduhan dan manifest paket menjawab 404.
    |
    */
    'field_pack' => [
        'enabled' => (bool) env('FIELD_PACK_ENABLED', false),
    ],
```

Tulis ulang `resources/js/pages/Track/Index.tsx` utuh:

```tsx
import { router, usePage } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { PageHeader } from '@/components/ui/PageHeader';
import { Tautan } from '@/components/ui/Tautan';
import { AppLayout } from '@/layouts/AppLayout';
import { kelas } from '@/lib/kelas';
import { create as goalsCreate } from '@/routes/goals';
import { index as tripsIndex } from '@/routes/trips';
import type { PropsBersama } from '@/types';

interface TripHariH {
    id: number;
    nama: string;
    jalur: string;
    gunung: string;
    jenis: string;
    hari: string | null;
    tanggal: string;
    jam: string | null;
    peserta: string | null;
    cek: string;
    urls: { mulai: string; ruangKerja: string; cek: string; lapangan: string };
}

interface TripLain {
    id: number;
    nama: string;
    jalur: string;
    tanggal: string;
    hari: string | null;
    urls: { mulai: string; ruangKerja: string };
}

interface Props extends PropsBersama {
    hariH: TripHariH | null;
    lain: TripLain[];
    lokasi: string;
    jejak: { menyala: boolean; kalimat: string; url: string };
    paket: { aktif: boolean; kalimat: string };
}

const tautanTeks = 'inline-flex min-h-11 items-center text-kecil font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600';

/**
 * Pintu tab Mendaki (spec v3 bagian 6.12; rencana induk W6 butir 1): pratinjau hari-H trip
 * terdekat yang dapat dimulai, dengan keadaan paket luring, cara lokasi dipakai, dan rekam
 * jejak, lalu satu tombol "Mulai pendakian" 66 px. Trip lain yang dapat dimulai di bawahnya.
 */
export default function TrackIndex() {
    const { hariH, lain, lokasi, jejak, paket } = usePage<Props>().props;

    return (
        <div className="py-6">
            <div className="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader akar title="Mendaki" description="Mode lapangan untuk hari pendakian: peta jalur, pos berikutnya, dan angka yang dihitung di perangkat Anda." />

                {hariH === null ? (
                    <EmptyState
                        jenis="pertama"
                        title="Belum ada trip yang dapat dimulai."
                        description="Mode lapangan dibuka dari trip yang sudah direncanakan. Rencanakan trip dulu, lalu kembali ke sini pada hari keberangkatan."
                    >
                        <Button href={tripsIndex.url()}>Buka trip saya</Button>
                        <Button variant="secondary" href={goalsCreate.url()}>Cari jalur yang cocok</Button>
                    </EmptyState>
                ) : (
                    <section aria-labelledby="judul-hari-h" className="rounded-utama border border-subtle bg-surface p-5">
                        <h2 id="judul-hari-h" className="break-words text-judul-kartu text-primary">
                            {hariH.nama}
                        </h2>
                        <p className="text-isi text-secondary">
                            {hariH.jalur}, {hariH.gunung}
                        </p>
                        <p className="text-kecil text-secondary">
                            {[hariH.hari, hariH.jenis, hariH.tanggal, hariH.jam ? `mulai ${hariH.jam}` : null, hariH.peserta].filter(Boolean).join(', ')}
                        </p>

                        <dl className="mt-4 space-y-3 border-t border-subtle pt-4">
                            <Butir judul="Paket luring">{paket.kalimat}</Butir>
                            <Butir judul="Lokasi">{lokasi}</Butir>
                            <Butir judul="Rekam jejak">
                                {jejak.kalimat}{' '}
                                <Tautan href={jejak.url} className={tautanTeks}>
                                    Atur di Profil
                                </Tautan>
                            </Butir>
                            <Butir judul="Cek kesiapan">
                                {hariH.cek}{' '}
                                <Tautan href={hariH.urls.cek} className={tautanTeks}>
                                    Buka cek kesiapan
                                </Tautan>
                            </Butir>
                        </dl>

                        <MulaiPendakian url={hariH.urls.mulai} besar />
                        <Tautan href={hariH.urls.lapangan} className={kelas(tautanTeks, 'mt-2')}>
                            Tinjau peta dan pos sebelum mulai
                        </Tautan>
                    </section>
                )}

                {lain.length > 0 ? (
                    <section aria-labelledby="judul-trip-lain" className="space-y-3">
                        <h2 id="judul-trip-lain" className="text-bagian text-primary">
                            Trip lain yang dapat dimulai
                        </h2>
                        <ul className="space-y-3">
                            {lain.map((trip) => (
                                <li key={trip.id} className="rounded-kartu border border-subtle bg-surface p-4">
                                    <Tautan href={trip.urls.ruangKerja} className="inline-flex min-h-11 items-center break-words text-subjudul text-primary underline underline-offset-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
                                        {trip.nama}
                                    </Tautan>
                                    <p className="text-kecil text-secondary">{[trip.jalur, trip.tanggal, trip.hari].filter(Boolean).join(', ')}</p>
                                    <MulaiPendakian url={trip.urls.mulai} />
                                </li>
                            ))}
                        </ul>
                    </section>
                ) : null}
            </div>
        </div>
    );
}

function Butir({ judul, children }: { judul: string; children: ReactNode }) {
    return (
        <div>
            <dt className="text-kecil font-bold text-primary">{judul}</dt>
            <dd className="text-kecil text-secondary">{children}</dd>
        </div>
    );
}

/**
 * Memulai tidak dapat dikembalikan ke rencana, jadi tombolnya meminta konfirmasi sebaris, bukan
 * dialog. Tanpa sinyal permintaan gagal di jaringan; pendaki diberi tahu pendakiannya belum
 * dimulai, bukan dibiarkan menunggu tombol yang berputar (titipan W2-5j).
 */
function MulaiPendakian({ url, besar = false }: { url: string; besar?: boolean }) {
    const [yakin, setYakin] = useState(false);
    const [memproses, setMemproses] = useState(false);
    const [pesan, setPesan] = useState<string | null>(null);

    const mulai = () => {
        setMemproses(true);
        setPesan(null);
        router.post(
            url,
            {},
            {
                onNetworkError: () => {
                    setPesan('Belum dimulai: tidak ada sinyal. Coba lagi saat sinyal ada.');

                    return false;
                },
                onFinish: () => setMemproses(false),
            },
        );
    };

    const ukuran = besar ? 'mt-5 min-h-[66px] w-full py-4 text-bagian' : 'mt-3';

    return (
        <div>
            {yakin ? (
                <div className={kelas('flex flex-wrap items-center gap-2', besar ? 'mt-5' : 'mt-3')}>
                    <Button onClick={mulai} processing={memproses} className={besar ? 'min-h-[66px] flex-1 py-4 text-bagian' : undefined}>
                        Ya, mulai sekarang
                    </Button>
                    <Button variant="secondary" onClick={() => setYakin(false)} disabled={memproses}>
                        Batal
                    </Button>
                    <p className="basis-full text-kecil text-secondary">Pendakian yang sudah dimulai tidak dapat dikembalikan menjadi rencana. Satu pendakian dapat berlangsung pada satu waktu.</p>
                </div>
            ) : (
                <Button onClick={() => setYakin(true)} className={ukuran} variant={besar ? 'primary' : 'secondary'}>
                    Mulai pendakian
                </Button>
            )}
            <p role="status" className="mt-2 text-kecil font-bold text-primary">
                {pesan}
            </p>
        </div>
    );
}

TrackIndex.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
```

- [ ] **Step 4: Build dan tipe.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `npx tsc --noEmit`
Expected: `✓ built`; tsc tanpa keluaran galat.

- [ ] **Step 5: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Services/Trips/PintuMendaki.php app/Http/Controllers/Trips/TrackController.php config/hiking.php tests/Feature/TrackTabTest.php`, lalu `php artisan test --compact --filter='TrackTab'`
Expected: PASS. Pada simulasi rencana: 8 test lulus.

- [ ] **Step 6: Suite, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 1.996 test, 1.994 lulus, 2 dilewati (sebelum tugas: 1.992).

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `php artisan test --compact --testsuite=Pratinjau`
Expected: PASS; halaman `mendaki` kini pratinjau hari-H. Pada simulasi rencana: 23 test lulus.

- [ ] **Step 7: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 5: Alasan pembatalan dan lembar Selesai

DK-50; spec bagian 6.13 (varian dibatalkan); induk W6 butir 9 (`TripCancellationReasonTest`); ruling W6-R11. Kolom
enum opsional `trip_plans.cancellation_reason` dengan daftar putih lima alasan (Cuaca, Status resmi, Kondisi tubuh,
Logistik, Lainnya). Alasan dicatat saat trip dibatalkan atau saat pendakian berbalik di jalur (ABANDONED), tidak pernah
untuk pendakian yang sampai tujuan, dan tidak dapat ditulis lewat formulir rencana. Konfirmasi batal pindah dari
`window.confirm` ke dialog dengan pilihan alasan; formulir Selesai dipakai bersama ruang kerja dan lembar Selesai mode
lapangan (60 px).

**Files:**
- Create: `app/Enums/CancellationReason.php`, `app/Http/Requests/Trips/CancelTripRequest.php`, `database/migrations/2026_10_07_000300_add_cancellation_reason_to_trip_plans_table.php`, `resources/js/components/trips/FormulirSelesai.tsx`, `resources/js/components/trips/BatalkanTrip.tsx`, `resources/js/components/mendaki/SelesaiLapangan.tsx`
- Modify: `app/Http/Controllers/Trips/TripController.php`, `app/Http/Controllers/Trips/TripLifecycleController.php`, `app/Http/Requests/Trips/CompleteTripRequest.php`, `app/Models/TripPlan.php`, `app/Services/Trips/HalamanMendaki.php`, `app/Services/Trips/TripLifecycle.php`, `resources/js/components/mendaki/types.ts`, `resources/js/pages/Trips/Hike.tsx`, `resources/js/pages/Trips/Show.tsx`
- Test: `tests/Feature/TripCancellationReasonTest.php` (baru), `tests/Feature/ButtonFeedbackTest.php`

**Interfaces:**
- Consumes: `CompletionState` (W0); `HalamanMendaki::untuk()` (Tugas 3); `TripLifecycle::refusal()`; primitif `Dialog`, `LembarBawah`, `ChipPilihan`, `KontrolSegmen`, `Textarea`, `InputError`, `Button`; `tombolLapangan` dari `ModeLapangan.tsx`.
- Produces: enum `App\Enums\CancellationReason` (`WEATHER`, `OFFICIAL_STATUS`, `PHYSICAL_CONDITION`, `LOGISTICS`, `OTHER`; `label()`, `pilihan(): array<int, array{value, label}>`); `TripLifecycle::complete(TripPlan, CompletionState, ?string, User, ?CancellationReason $alasan = null)`, `cancel(TripPlan, ?CancellationReason $alasan = null)`; `CancelTripRequest` (`alasan` opsional); `CompleteTripRequest` menerima `alasan` opsional; prop `alasanBatal` di `Trips/Show`; kunci `pilihan` (`hasil`, `alasan`) dan `urls.selesai` di `HalamanMendaki`; komponen `FormulirSelesai({ url, hasil, alasan, id })`, `BatalkanTrip({ url, alasan })`, `SelesaiLapangan({ data })`.

- [ ] **Step 1: Tulis test yang gagal.** `ButtonFeedbackTest` mengikuti konfirmasi batal yang pindah ke dialog
(niatnya tetap: aksi yang tidak dapat dikembalikan meminta konfirmasi dengan kalimat yang menyebut akibatnya).

Buat `tests/Feature/TripCancellationReasonTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\CancellationReason;
use App\Enums\CompletionState;
use App\Enums\HikingSessionStatus;
use App\Enums\TripStatus;
use App\Models\TripPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Alasan pembatalan trip (DK-50; rencana induk W6 butir 9): kolom opsional di trip_plans dengan
 * daftar putih, supaya Progres dan ringkasan dapat menulis "Dibatalkan · Cuaca". Membatalkan
 * karena cuaca adalah keputusan yang benar dan tidak pernah dihukum (PRODUCT.md), jadi alasannya
 * boleh kosong dan tidak pernah ditanya ulang.
 */
class TripCancellationReasonTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_trip_can_be_cancelled_with_a_reason_from_the_list(): void
    {
        $trip = TripPlan::factory()->create(['status' => TripStatus::PLANNED->value]);

        $this->actingAs($trip->user)
            ->post(route('trips.cancel', $trip), ['alasan' => CancellationReason::WEATHER->value])
            ->assertRedirect(route('trips.show', $trip))
            ->assertSessionHas('status', 'Trip dibatalkan.');

        $this->assertSame(TripStatus::CANCELLED, $trip->fresh()->status);
        $this->assertSame(CancellationReason::WEATHER, $trip->fresh()->cancellation_reason);
    }

    public function test_the_reason_is_optional(): void
    {
        $trip = TripPlan::factory()->create(['status' => TripStatus::PLANNED->value]);

        $this->actingAs($trip->user)->post(route('trips.cancel', $trip))->assertRedirect(route('trips.show', $trip));

        $this->assertSame(TripStatus::CANCELLED, $trip->fresh()->status);
        $this->assertNull($trip->fresh()->cancellation_reason);
    }

    public function test_a_reason_outside_the_list_is_refused_and_nothing_changes(): void
    {
        $trip = TripPlan::factory()->create(['status' => TripStatus::PLANNED->value]);

        $this->actingAs($trip->user)
            ->post(route('trips.cancel', $trip), ['alasan' => 'BOSAN'])
            ->assertSessionHasErrors(['alasan' => 'Pilih alasan dari daftar, atau kosongkan.']);

        $this->assertSame(TripStatus::PLANNED, $trip->fresh()->status);
    }

    /**
     * Berbalik di jalur adalah pendakian yang selesai dengan hasil "Dibatalkan di jalur":
     * alasannya disimpan bersama trip yang sama.
     */
    public function test_turning_back_on_the_trail_keeps_its_reason(): void
    {
        $trip = $this->berlangsung();

        $this->actingAs($trip->user)->post(route('trips.complete', $trip), [
            'completion_state' => CompletionState::ABANDONED->value,
            'alasan' => CancellationReason::PHYSICAL_CONDITION->value,
        ]);

        $this->assertSame(CompletionState::ABANDONED, $trip->fresh()->history->completion_state);
        $this->assertSame(CancellationReason::PHYSICAL_CONDITION, $trip->fresh()->cancellation_reason);
    }

    public function test_a_hike_that_reached_its_goal_keeps_no_reason(): void
    {
        $trip = $this->berlangsung();

        $this->actingAs($trip->user)->post(route('trips.complete', $trip), [
            'completion_state' => CompletionState::COMPLETED->value,
            'alasan' => CancellationReason::WEATHER->value,
        ]);

        $this->assertNull($trip->fresh()->cancellation_reason);
    }

    /**
     * Alasan hanya ditulis lewat batal atau selesai, tidak lewat formulir rencana.
     */
    public function test_the_reason_cannot_be_written_through_the_plan_form(): void
    {
        $trip = TripPlan::factory()->create(['status' => TripStatus::PLANNED->value]);

        $this->actingAs($trip->user)->put(route('trips.update', $trip), [
            'name' => 'Rencana baru',
            'planned_date' => now()->addWeeks(2)->toDateString(),
            'trip_type' => $trip->trip_type->value,
            'cancellation_reason' => CancellationReason::LOGISTICS->value,
        ]);

        $this->assertNull($trip->fresh()->cancellation_reason);
    }

    public function test_the_reasons_are_the_five_of_dk_50(): void
    {
        $this->assertSame(
            ['Cuaca', 'Status resmi', 'Kondisi tubuh', 'Logistik', 'Lainnya'],
            array_column(CancellationReason::pilihan(), 'label')
        );

        $trip = TripPlan::factory()->create(['status' => TripStatus::PLANNED->value]);

        $this->actingAs($trip->user)->get(route('trips.show', $trip))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('alasanBatal.0', ['value' => 'WEATHER', 'label' => 'Cuaca']));
    }

    /**
     * Mode lapangan membawa pilihan hasil dan alasan untuk lembar Selesai, beserta alamatnya.
     */
    public function test_field_mode_carries_the_finish_choices(): void
    {
        $trip = $this->berlangsung();

        $this->actingAs($trip->user)->get(route('trips.hike', $trip))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('urls.selesai', route('trips.complete', $trip))
                ->where('pilihan.hasil.2', ['value' => 'ABANDONED', 'label' => 'Dibatalkan di jalur'])
                ->where('pilihan.alasan.4', ['value' => 'OTHER', 'label' => 'Lainnya']));
    }

    private function berlangsung(): TripPlan
    {
        $trip = TripPlan::factory()->create(['status' => TripStatus::IN_PROGRESS->value]);
        $trip->hikingSession()->create(['user_id' => $trip->user_id, 'status' => HikingSessionStatus::ACTIVE->value, 'started_at' => now()]);

        return $trip;
    }
}
```

`tests/Feature/ButtonFeedbackTest.php`:

1. Ganti:

```php
            'arsipkan gunung' => ['pages/Admin/Mountains.tsx', "konfirmasiLalu(baris.archived ? null : 'Arsipkan gunung ini? Seluruh jalurnya ikut hilang dari pencarian pendaki.'"],
            'batalkan trip' => ['pages/Trips/Show.tsx', "window.confirm('Batalkan trip ini? Trip yang dibatalkan tidak dapat dimulai lagi.')"],
            'hapus jejak pendakian' => ['pages/Community/HikeSummary.tsx', "window.confirm('Hapus jejak pendakian ini? Pendakiannya sendiri tetap tersimpan.')"],
```

dengan:

```php
            'arsipkan gunung' => ['pages/Admin/Mountains.tsx', "konfirmasiLalu(baris.archived ? null : 'Arsipkan gunung ini? Seluruh jalurnya ikut hilang dari pencarian pendaki.'"],
            // W6: dialog BatalkanTrip (alasan opsional DK-50) menggantikan window.confirm.
            'batalkan trip' => ['components/trips/BatalkanTrip.tsx', 'keterangan="Trip yang dibatalkan tidak dapat dimulai lagi.'],
            'hapus jejak pendakian' => ['pages/Community/HikeSummary.tsx', "window.confirm('Hapus jejak pendakian ini? Pendakiannya sendiri tetap tersimpan.')"],
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='TripCancellationReason|ButtonFeedback'`
Expected: FAIL. Pada simulasi rencana: 21 test, 13 lulus, 2 gagal, 6 error. Yang merah: tujuh test `TripCancellationReasonTest` (enum `CancellationReason`, daftar putih alasan, dan pilihan selesai di mode lapangan belum ada) dan `ButtonFeedbackTest::test_a_destructive_action_asks_before_it_runs` (lembar `BatalkanTrip.tsx` belum ada). `test_the_reason_is_optional` sudah lulus karena batal tanpa alasan memang sudah berjalan.

- [ ] **Step 3: Tulis implementasinya.** Enum dengan
`DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:enum --no-interaction CancellationReason`, request
dengan `... make:request --no-interaction Trips/CancelTripRequest`, migrasi dengan
`... make:migration --no-interaction add_cancellation_reason_to_trip_plans_table` (ganti nama berkasnya), lalu isinya
diganti persis.

Buat `app/Enums/CancellationReason.php`:

```php
<?php

namespace App\Enums;

/**
 * Alasan pembatalan trip (DK-50): daftar putih yang sama untuk batal sebelum berangkat dan
 * berbalik di jalur, supaya Progres dapat menulis "Dibatalkan · Cuaca". Opsional: membatalkan
 * adalah keputusan yang benar, dan alasannya tidak pernah diwajibkan.
 */
enum CancellationReason: string
{
    case WEATHER = 'WEATHER';
    case OFFICIAL_STATUS = 'OFFICIAL_STATUS';
    case PHYSICAL_CONDITION = 'PHYSICAL_CONDITION';
    case LOGISTICS = 'LOGISTICS';
    case OTHER = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::WEATHER => 'Cuaca',
            self::OFFICIAL_STATUS => 'Status resmi',
            self::PHYSICAL_CONDITION => 'Kondisi tubuh',
            self::LOGISTICS => 'Logistik',
            self::OTHER => 'Lainnya',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function pilihan(): array
    {
        return array_map(fn (self $alasan) => ['value' => $alasan->value, 'label' => $alasan->label()], self::cases());
    }
}
```

Buat `app/Http/Requests/Trips/CancelTripRequest.php`:

```php
<?php

namespace App\Http\Requests\Trips;

use App\Enums\CancellationReason;
use App\Models\TripPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Membatalkan trip dengan alasan opsional dari daftar DK-50.
 */
class CancelTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        $trip = $this->route('trip');

        return $trip instanceof TripPlan && $this->user()?->can('update', $trip) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'alasan' => ['nullable', Rule::enum(CancellationReason::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'alasan' => 'Pilih alasan dari daftar, atau kosongkan.',
        ];
    }
}
```

Buat `database/migrations/2026_10_07_000300_add_cancellation_reason_to_trip_plans_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Alasan pembatalan trip (DK-50, spec v3 bagian 9.3): kolom opsional dengan daftar putih
 * App\Enums\CancellationReason yang ditegakkan validasi, bukan basis data. Aditif dan boleh
 * kosong: trip lama tanpa alasan tetap sah, dan kosong berarti tidak disebut, bukan "lainnya"
 * (R-007).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_plans', function (Blueprint $table) {
            $table->string('cancellation_reason', 20)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('trip_plans', function (Blueprint $table) {
            $table->dropColumn('cancellation_reason');
        });
    }
};
```

Buat `resources/js/components/mendaki/SelesaiLapangan.tsx`:

```tsx
import { useRef, useState } from 'react';
import { FormulirSelesai } from '@/components/trips/FormulirSelesai';
import { LembarBawah } from '@/components/ui/Lembar';
import { kelas } from '@/lib/kelas';
import { tombolLapangan } from './ModeLapangan';
import type { DataLapangan } from './types';

/**
 * Tombol Selesai mode lapangan (spec v3 bagian 6.12, prototype 11) dan lembarnya: hasil
 * pendakian, alasan bila berbalik (DK-50), dan catatan. Menyimpan butuh sinyal, jadi tombol ini
 * hanya dipasang halaman daring; paket luring menyebut bahwa pendakian diselesaikan saat ada
 * sinyal.
 */
export function SelesaiLapangan({ data }: { data: DataLapangan }) {
    const [terbuka, setTerbuka] = useState(false);
    const pemicu = useRef<HTMLButtonElement>(null);

    return (
        <>
            <button ref={pemicu} type="button" onClick={() => setTerbuka(true)} className={kelas(tombolLapangan, 'bg-brand-700 text-on-brand')}>
                Selesai
            </button>
            <LembarBawah terbuka={terbuka} onTutup={() => setTerbuka(false)} pemicu={pemicu} judul="Selesaikan pendakian" keterangan="Hasilnya tersimpan di riwayat Anda. Berbalik sebelum tujuan juga dicatat apa adanya.">
                <FormulirSelesai url={data.urls.selesai} hasil={data.pilihan.hasil} alasan={data.pilihan.alasan} id="selesai-lapangan" />
            </LembarBawah>
        </>
    );
}
```

Buat `resources/js/components/trips/BatalkanTrip.tsx`:

```tsx
import { router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/Button';
import { ChipPilihan } from '@/components/ui/Chip';
import { Dialog } from '@/components/ui/Lembar';
import type { Opsi } from './types';

/**
 * Membatalkan trip (PRD §35, DK-50): final, jadi ditanyakan dulu dalam dialog, dengan alasan
 * opsional dari daftar supaya Progres dapat menulis "Dibatalkan · Cuaca". Membatalkan karena
 * cuaca atau kondisi tubuh adalah keputusan yang benar; kalimatnya tidak menghakimi.
 */
export function BatalkanTrip({ url, alasan }: { url: string; alasan: Opsi[] }) {
    const [terbuka, setTerbuka] = useState(false);
    const [pilihan, setPilihan] = useState('');
    const [memproses, setMemproses] = useState(false);

    const batalkan = () => {
        setMemproses(true);
        router.post(url, { alasan: pilihan === '' ? null : pilihan }, { preserveScroll: true, onFinish: () => setMemproses(false), onSuccess: () => setTerbuka(false) });
    };

    return (
        <>
            <Button variant="secondary" size="sm" onClick={() => setTerbuka(true)}>
                Batalkan trip
            </Button>
            <Dialog
                terbuka={terbuka}
                onTutup={() => setTerbuka(false)}
                judul="Batalkan trip ini?"
                keterangan="Trip yang dibatalkan tidak dapat dimulai lagi. Alasannya opsional dan hanya untuk catatan Anda sendiri."
                aksi={
                    <>
                        <Button variant="secondary" onClick={() => setTerbuka(false)} disabled={memproses}>
                            Kembali
                        </Button>
                        <Button variant="danger" onClick={batalkan} processing={memproses}>
                            Batalkan trip
                        </Button>
                    </>
                }
            >
                <fieldset>
                    <legend className="text-isi font-bold text-primary">Alasan (opsional)</legend>
                    <div className="mt-2 flex flex-wrap gap-1">
                        {alasan.map((a) => (
                            <ChipPilihan key={a.value} dipilih={pilihan === a.value} onUbah={(dipilih) => setPilihan(dipilih ? a.value : '')}>
                                {a.label}
                            </ChipPilihan>
                        ))}
                    </div>
                </fieldset>
            </Dialog>
        </>
    );
}
```

Buat `resources/js/components/trips/FormulirSelesai.tsx`:

```tsx
import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/Button';
import { ChipPilihan } from '@/components/ui/Chip';
import { InputError, Label, Textarea } from '@/components/ui/Field';
import { KontrolSegmen } from '@/components/ui/KontrolSegmen';
import type { Opsi } from './types';

interface FormulirSelesaiProps {
    url: string;
    hasil: Opsi[];
    alasan: Opsi[];
    /** Awalan id supaya dua formulir di halaman yang berbeda tidak bertabrakan. */
    id: string;
}

/**
 * Menyelesaikan pendakian (spec v3 bagian 6.13, DK-50): hasilnya, alasan bila berbalik di
 * jalur, dan catatan pribadi. Alasan opsional dan hanya ditanyakan untuk "Dibatalkan di jalur":
 * berbalik adalah keputusan yang benar, bukan kegagalan yang perlu dijelaskan.
 */
export function FormulirSelesai({ url, hasil, alasan, id }: FormulirSelesaiProps) {
    const form = useForm<{ completion_state: string; alasan: string; personal_notes: string }>({ completion_state: 'COMPLETED', alasan: '', personal_notes: '' });
    const berbalik = form.data.completion_state === 'ABANDONED';

    const kirim = (e: FormEvent) => {
        e.preventDefault();
        form.transform((data) => ({ ...data, alasan: berbalik && data.alasan !== '' ? data.alasan : null }));
        form.post(url, { preserveScroll: true });
    };

    return (
        <form onSubmit={kirim} className="space-y-4">
            <div>
                <p aria-hidden="true" className="mb-2 text-isi font-bold text-primary">
                    Hasil pendakian
                </p>
                <KontrolSegmen
                    nama={`${id}-hasil`}
                    label="Hasil pendakian"
                    pilihan={hasil.map((h) => ({ nilai: h.value, label: h.label }))}
                    nilai={form.data.completion_state}
                    onUbah={(nilai) => form.setData('completion_state', nilai)}
                    penjelas={form.errors.completion_state ? `${id}-hasil-galat` : undefined}
                />
                <InputError id={`${id}-hasil-galat`} messages={form.errors.completion_state} className="mt-2" />
            </div>

            {berbalik ? (
                <fieldset aria-describedby={form.errors.alasan ? `${id}-alasan-galat` : undefined}>
                    <legend className="text-isi font-bold text-primary">Alasan berbalik (opsional)</legend>
                    <div className="mt-2 flex flex-wrap gap-1">
                        {alasan.map((a) => (
                            <ChipPilihan key={a.value} dipilih={form.data.alasan === a.value} onUbah={(dipilih) => form.setData('alasan', dipilih ? a.value : '')}>
                                {a.label}
                            </ChipPilihan>
                        ))}
                    </div>
                    <InputError id={`${id}-alasan-galat`} messages={form.errors.alasan} className="mt-2" />
                </fieldset>
            ) : null}

            <div>
                <Label htmlFor={`${id}-catatan`}>Catatan pribadi (opsional)</Label>
                <Textarea
                    id={`${id}-catatan`}
                    rows={3}
                    value={form.data.personal_notes}
                    onChange={(e) => form.setData('personal_notes', e.target.value)}
                    aria-invalid={form.errors.personal_notes ? true : undefined}
                    aria-describedby={form.errors.personal_notes ? `${id}-catatan-galat` : undefined}
                />
                <InputError id={`${id}-catatan-galat`} messages={form.errors.personal_notes} className="mt-2" />
            </div>

            <Button type="submit" processing={form.processing}>
                Simpan hasil pendakian
            </Button>
        </form>
    );
}
```

`app/Http/Controllers/Trips/TripController.php`:

1. Ganti:

```php
namespace App\Http\Controllers\Trips;

use App\Enums\CompletionState;
```

dengan:

```php
namespace App\Http\Controllers\Trips;

use App\Enums\CancellationReason;
use App\Enums\CompletionState;
```

2. Ganti:

```php
            'completionStates' => array_map(fn (CompletionState $s) => ['value' => $s->value, 'label' => $s->label()], CompletionState::cases()),
            'penolakan' => session('penolakan-trip'),
```

dengan:

```php
            'completionStates' => array_map(fn (CompletionState $s) => ['value' => $s->value, 'label' => $s->label()], CompletionState::cases()),
            'alasanBatal' => CancellationReason::pilihan(),
            'penolakan' => session('penolakan-trip'),
```

`app/Http/Controllers/Trips/TripLifecycleController.php`:

1. Ganti:

```php
namespace App\Http\Controllers\Trips;

use App\Enums\CompletionState;
```

dengan:

```php
namespace App\Http\Controllers\Trips;

use App\Enums\CancellationReason;
use App\Enums\CompletionState;
```

2. Ganti:

```php
use App\Http\Controllers\Controller;
use App\Http\Requests\Trips\CompleteTripRequest;
```

dengan:

```php
use App\Http\Controllers\Controller;
use App\Http\Requests\Trips\CancelTripRequest;
use App\Http\Requests\Trips\CompleteTripRequest;
```

3. Ganti:

```php
            $request->user(),
        );
```

dengan:

```php
            $request->user(),
            CancellationReason::tryFrom((string) $request->validated('alasan')),
        );
```

4. Ganti:

```php
    }

    public function cancel(TripPlan $trip, TripLifecycle $lifecycle): RedirectResponse
    {
        $this->authorize('update', $trip);

        if ($penolakan = $lifecycle->refusal($trip, TripStatus::CANCELLED)) {
```

dengan:

```php
    }

    public function cancel(CancelTripRequest $request, TripPlan $trip, TripLifecycle $lifecycle): RedirectResponse
    {
        if ($penolakan = $lifecycle->refusal($trip, TripStatus::CANCELLED)) {
```

5. Ganti:

```php
        }

        $lifecycle->cancel($trip);

        return redirect()->route('trips.show', $trip)->with('status', 'Trip dibatalkan.');
```

dengan:

```php
        }

        $lifecycle->cancel($trip, CancellationReason::tryFrom((string) $request->validated('alasan')));

        return redirect()->route('trips.show', $trip)->with('status', 'Trip dibatalkan.');
```

`app/Http/Requests/Trips/CompleteTripRequest.php`:

1. Ganti:

```php
namespace App\Http\Requests\Trips;

use App\Enums\CompletionState;
```

dengan:

```php
namespace App\Http\Requests\Trips;

use App\Enums\CancellationReason;
use App\Enums\CompletionState;
```

2. Ganti:

```php
            'personal_notes' => ['nullable', 'string', 'max:1000'],
        ];
```

dengan:

```php
            'personal_notes' => ['nullable', 'string', 'max:1000'],
            // DK-50: alasan berbalik di jalur, opsional; disimpan hanya bila hasilnya
            // "Dibatalkan di jalur" (TripLifecycle::complete).
            'alasan' => ['nullable', new Enum(CancellationReason::class)],
        ];
```

`app/Models/TripPlan.php`:

1. Ganti:

```php
namespace App\Models;

use App\Enums\OfficialStatusValue;
```

dengan:

```php
namespace App\Models;

use App\Enums\CancellationReason;
use App\Enums\OfficialStatusValue;
```

2. Ganti:

```php
    'start_time', 'participants_count', 'trip_type', 'notes', 'reminders_enabled',
    'status', 'completed_at',
])]
```

dengan:

```php
    'start_time', 'participants_count', 'trip_type', 'notes', 'reminders_enabled',
    'status', 'cancellation_reason', 'completed_at',
])]
```

3. Ganti:

```php
            'status' => TripStatus::class,
            'completed_at' => 'datetime',
```

dengan:

```php
            'status' => TripStatus::class,
            'cancellation_reason' => CancellationReason::class,
            'completed_at' => 'datetime',
```

`app/Services/Trips/HalamanMendaki.php`:

1. Ganti:

```php
namespace App\Services\Trips;

use App\Enums\TripStatus;
```

dengan:

```php
namespace App\Services\Trips;

use App\Enums\CancellationReason;
use App\Enums\CompletionState;
use App\Enums\TripStatus;
```

2. Ganti:

```php
            'sumber' => 'daring',
            'urls' => [
```

dengan:

```php
            'sumber' => 'daring',
            // Lembar Selesai (DK-50): hasil pendakian dan alasan berbalik, dari enum yang sama
            // dengan validasi server.
            'pilihan' => [
                'hasil' => array_map(fn (CompletionState $s) => ['value' => $s->value, 'label' => $s->label()], CompletionState::cases()),
                'alasan' => CancellationReason::pilihan(),
            ],
            'urls' => [
```

3. Ganti:

```php
                'lanjut' => route('trips.hike.resume', $trip),
                'ruangKerja' => route('trips.show', $trip),
```

dengan:

```php
                'lanjut' => route('trips.hike.resume', $trip),
                'selesai' => route('trips.complete', $trip),
                'ruangKerja' => route('trips.show', $trip),
```

`app/Services/Trips/TripLifecycle.php`:

1. Ganti:

```php
use App\Enums\AnalyticsEvent;
use App\Enums\CompletionState;
```

dengan:

```php
use App\Enums\AnalyticsEvent;
use App\Enums\CancellationReason;
use App\Enums\CompletionState;
```

2. Ganti:

```php
    }

    public function complete(TripPlan $trip, CompletionState $state, ?string $personalNotes, User $user): void
    {
```

dengan:

```php
    }

    /**
     * Alasan (DK-50) hanya disimpan untuk hasil "Dibatalkan di jalur"; pendakian yang sampai
     * tujuan atau sebagian tidak membawa alasan pembatalan.
     */
    public function complete(TripPlan $trip, CompletionState $state, ?string $personalNotes, User $user, ?CancellationReason $alasan = null): void
    {
```

3. Ganti:

```php
            'status' => TripStatus::COMPLETED->value,
            'completed_at' => now(),
```

dengan:

```php
            'status' => TripStatus::COMPLETED->value,
            'cancellation_reason' => $state === CompletionState::ABANDONED ? $alasan?->value : null,
            'completed_at' => now(),
```

4. Ganti:

```php
    }

    public function cancel(TripPlan $trip): void
    {
        $trip->update(['status' => TripStatus::CANCELLED->value]);
        $this->reminders->sinkronkan($trip);
```

dengan:

```php
    }

    public function cancel(TripPlan $trip, ?CancellationReason $alasan = null): void
    {
        $trip->update(['status' => TripStatus::CANCELLED->value, 'cancellation_reason' => $alasan?->value]);
        $this->reminders->sinkronkan($trip);
```

`resources/js/components/mendaki/types.ts`:

1. Ganti:

```ts
    sumber: 'daring' | 'paket';
    urls: { tiba: string; jeda: string; lanjut: string; ruangKerja: string; jejak: string | null };
}
```

dengan:

```ts
    sumber: 'daring' | 'paket';
    /** Pilihan lembar Selesai: hasil pendakian dan alasan berbalik di jalur (DK-50). */
    pilihan: { hasil: Array<{ value: string; label: string }>; alasan: Array<{ value: string; label: string }> };
    urls: { tiba: string; jeda: string; lanjut: string; selesai: string; ruangKerja: string; jejak: string | null };
}
```

`resources/js/pages/Trips/Hike.tsx`:

1. Ganti:

```tsx
import { teksLama } from '@/components/mendaki/hitung';
import { ModeLapangan, tombolLapangan } from '@/components/mendaki/ModeLapangan';
import type { DataLapangan } from '@/components/mendaki/types';
```

dengan:

```tsx
import { teksLama } from '@/components/mendaki/hitung';
import { ModeLapangan } from '@/components/mendaki/ModeLapangan';
import { SelesaiLapangan } from '@/components/mendaki/SelesaiLapangan';
import type { DataLapangan } from '@/components/mendaki/types';
```

2. Ganti:

```tsx
import { useJejakPendakian } from '@/components/trips/usePendakian';
import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';
import type { PropsBersama } from '@/types';
```

dengan:

```tsx
import { useJejakPendakian } from '@/components/trips/usePendakian';
import type { PropsBersama } from '@/types';
```

3. Ganti:

```tsx
            )}
            <ModeLapangan
                data={data}
                mode={mode}
                selesai={
                    daring ? (
                        <Tautan href={data.urls.ruangKerja} className={kelas(tombolLapangan, 'bg-brand-700 text-on-brand')}>
                            Selesai
                        </Tautan>
                    ) : null
                }
            />
        </TataLetakPendakian>
```

dengan:

```tsx
            )}
            <ModeLapangan data={data} mode={mode} selesai={daring ? <SelesaiLapangan data={data} /> : null} />
        </TataLetakPendakian>
```

`resources/js/pages/Trips/Show.tsx`:

1. Ganti:

```tsx
import { router, useForm, usePage } from '@inertiajs/react';
import { useState, type FormEvent, type ReactNode } from 'react';
import { FitTrip } from '@/components/trips/FitTrip';
import { KepalaTrip } from '@/components/trips/KepalaTrip';
```

dengan:

```tsx
import { router, usePage } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import { BatalkanTrip } from '@/components/trips/BatalkanTrip';
import { FitTrip } from '@/components/trips/FitTrip';
import { FormulirSelesai } from '@/components/trips/FormulirSelesai';
import { KepalaTrip } from '@/components/trips/KepalaTrip';
```

2. Ganti:

```tsx
import { Card } from '@/components/ui/Card';
import { InputError, Label, Select, Textarea } from '@/components/ui/Field';
import { PageHeader } from '@/components/ui/PageHeader';
```

dengan:

```tsx
import { Card } from '@/components/ui/Card';
import { PageHeader } from '@/components/ui/PageHeader';
```

3. Ganti:

```tsx
    completionStates: Opsi[];
    penolakan: string | null;
```

dengan:

```tsx
    completionStates: Opsi[];
    alasanBatal: Opsi[];
    penolakan: string | null;
```

4. Ganti:

```tsx
export default function Show() {
    const { kepala, trip, langkah, fit, penilaian, izin, hasilPendakian, completionStates, penolakan, pengingat, kelola, flash } = usePage<Props>().props;
    const [memulai, setMemulai] = useState(false);
    const [membatalkan, setMembatalkan] = useState(false);
    const selesai = useForm({ completion_state: 'COMPLETED', personal_notes: '' });

    const mulai = () => {
```

dengan:

```tsx
export default function Show() {
    const { kepala, trip, langkah, fit, penilaian, izin, hasilPendakian, completionStates, alasanBatal, penolakan, pengingat, kelola, flash } = usePage<Props>().props;
    const [memulai, setMemulai] = useState(false);

    const mulai = () => {
```

5. Ganti:

```tsx
        router.post(start.url(trip.id), {}, { onFinish: () => setMemulai(false) });
    };

    // Dibatalkan bersifat final (PRD §35), jadi ditanyakan dulu.
    const batalkan = () => {
        if (!window.confirm('Batalkan trip ini? Trip yang dibatalkan tidak dapat dimulai lagi.')) {
            return;
        }
        setMembatalkan(true);
        router.post(cancel.url(trip.id), {}, { preserveScroll: true, onFinish: () => setMembatalkan(false) });
    };

    const tandaiSelesai = (e: FormEvent) => {
        e.preventDefault();
        selesai.post(complete.url(trip.id), { preserveScroll: true });
    };
```

dengan:

```tsx
        router.post(start.url(trip.id), {}, { onFinish: () => setMemulai(false) });
    };
```

6. Ganti:

```tsx
                                    )}
                                    <Button variant="secondary" size="sm" onClick={batalkan} processing={membatalkan}>
                                        Batalkan trip
                                    </Button>
                                </div>
```

dengan:

```tsx
                                    )}
                                    <BatalkanTrip url={cancel.url(trip.id)} alasan={alasanBatal} />
                                </div>
```

7. Ganti:

```tsx
                            <Card title="Selesaikan trip" subtitle="Tandai trip selesai untuk menyimpannya ke riwayat dan melaporkan kondisi jalur.">
                                <form onSubmit={tandaiSelesai} className="space-y-4">
                                    <div>
                                        <Label htmlFor="completion_state">Hasil pendakian</Label>
                                        <Select
                                            id="completion_state"
                                            name="completion_state"
                                            required
                                            value={selesai.data.completion_state}
                                            onChange={(e) => selesai.setData('completion_state', e.target.value)}
                                            aria-invalid={selesai.errors.completion_state ? true : undefined}
                                            aria-describedby={selesai.errors.completion_state ? 'completion_state-error' : undefined}
                                        >
                                            <option value="">Pilih hasil</option>
                                            {completionStates.map((s) => (
                                                <option key={s.value} value={s.value}>
                                                    {s.label}
                                                </option>
                                            ))}
                                        </Select>
                                        <InputError id="completion_state-error" messages={selesai.errors.completion_state} className="mt-2" />
                                    </div>

                                    <div>
                                        <Label htmlFor="personal_notes">Catatan pribadi (opsional)</Label>
                                        <Textarea
                                            id="personal_notes"
                                            name="personal_notes"
                                            rows={3}
                                            value={selesai.data.personal_notes}
                                            onChange={(e) => selesai.setData('personal_notes', e.target.value)}
                                            aria-invalid={selesai.errors.personal_notes ? true : undefined}
                                            aria-describedby={selesai.errors.personal_notes ? 'personal_notes-error' : undefined}
                                        />
                                        <InputError id="personal_notes-error" messages={selesai.errors.personal_notes} className="mt-2" />
                                    </div>

                                    <Button type="submit" processing={selesai.processing}>
                                        Tandai selesai
                                    </Button>
                                </form>
                            </Card>
```

dengan:

```tsx
                            <Card title="Selesaikan trip" subtitle="Tandai trip selesai untuk menyimpannya ke riwayat dan melaporkan kondisi jalur.">
                                <FormulirSelesai url={complete.url(trip.id)} hasil={completionStates} alasan={alasanBatal} id="selesai-ruang-kerja" />
                            </Card>
```

- [ ] **Step 4: Build dan tipe.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `npx tsc --noEmit`
Expected: `✓ built`; tsc tanpa keluaran galat.

- [ ] **Step 5: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Enums/CancellationReason.php app/Http/Requests/Trips/CancelTripRequest.php app/Http/Requests/Trips/CompleteTripRequest.php app/Http/Controllers/Trips/TripController.php app/Http/Controllers/Trips/TripLifecycleController.php app/Models/TripPlan.php app/Services/Trips/HalamanMendaki.php app/Services/Trips/TripLifecycle.php database/migrations/2026_10_07_000300_add_cancellation_reason_to_trip_plans_table.php tests/Feature/TripCancellationReasonTest.php tests/Feature/ButtonFeedbackTest.php`, lalu `php artisan test --compact --filter='TripCancellationReason|ButtonFeedback'`
Expected: PASS. Pada simulasi rencana: 21 test lulus.

- [ ] **Step 6: Suite, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 2.004 test, 2.002 lulus, 2 dilewati (sebelum tugas: 1.996).

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `php artisan test --compact --testsuite=Pratinjau`
Expected: PASS. Pada simulasi rencana: 23 test lulus.

- [ ] **Step 7: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 6: Ringkasan dan laporan pendakian

Spec bagian 6.13 (prototype 12); induk W6 butir 9 (`HikeSummaryTest` diperluas); ruling W6-R11 sampai W6-R13. Dua
varian: selesai (angka, banding diri sendiri, pencapaian pribadi, BR-11, formulir laporan) dan dibatalkan (pendek,
dengan alasannya). Angka (jarak, tanjakan, waktu bergerak tanpa jeda, titik tertinggi) dari rekaman jejak bila ada,
dari data jalur dengan jarak pulang-pergi untuk pendakian yang tuntas, kosong untuk pendakian yang berbalik tanpa
jejak, atau dari pendaki lewat "Ubah angka"; sumbernya selalu disebut. Tangga memakai ukuran tangga Beranda (tanjakan
jalur, satu anak tangga per jalur). Formulir laporan kondisi di ringkasan dengan tag, segmen, catatan, foto (GPS
dibuang server), apa yang terbit dan tidak, tombol kirim aktif setelah satu kondisi dipilih, dan "Ubah sebelum
dimoderasi". Menyelesaikan trip kini membuka ringkasannya. "Persiapan 80%" hilang (R-005). Kabar, peringatan foto, dan
penolakan formulir dibacakan lewat wilayah yang terpasang sejak halaman atau formulirnya tampil (konvensi umpan balik
aksi `DESIGN.md`).

**Files:**
- Create: `app/Http/Controllers/Community/HikeNumbersController.php`, `app/Http/Requests/Community/UpdateHikeNumbersRequest.php`, `app/Http/Requests/UpdateConditionReportRequest.php`, `database/migrations/2026_10_07_000400_add_recorded_values_to_hiking_history_table.php`, `resources/js/components/community/ringkasan.ts`, `resources/js/components/community/AngkaPendakian.tsx`, `resources/js/components/community/BandingDiriSendiri.tsx`, `resources/js/components/community/LaporanPendakian.tsx`
- Modify: `app/Services/Community/HasilPendakian.php` (ditulis ulang utuh), `app/Http/Controllers/Community/HikeSummaryController.php` (ditulis ulang utuh), `app/Http/Controllers/Community/ConditionReportController.php`, `app/Http/Controllers/Trips/TripLifecycleController.php`, `app/Models/HikingHistory.php`, `routes/web.php`, `resources/js/pages/Community/HikeSummary.tsx` (ditulis ulang utuh)
- Test: `tests/Feature/HikeSummaryTest.php` (ditulis ulang utuh), `tests/Feature/HikeSummaryReportTest.php` (baru), `tests/Feature/TripLifecycleTest.php`, `tests/Pratinjau/HalamanPratinjauTest.php`

**Interfaces:**
- Consumes: `CancellationReason` dan `TripPlan::cancellation_reason` (Tugas 5); `HikingSession::detikBergerak()` (Tugas 3); `GpxTrack::lengthKm()`, `GpxTrack::gainLoss()`; `SkalaProfil::titik()`; `ProfilElevasiJalur::untuk()`; `CheckpointJourney::daftar()`; `Durasi::panjang()`; `Timezone::forTrail()`; `ConditionTag`, `ModerationStatus`, `TrailConditionReport::rejectionReason()`; `StoreConditionReportRequest`; komponen `PetaGaris`, `ProfilElevasi`, `DaftarPos`, `LembarBawah`, `ChipPilihan`, `Lencana`, `Tautan`; rute `hike.track.destroy`, `reports.store`, `profile`, `progress`, `trails.show`.
- Produces: `HasilPendakian::untuk(TripPlan $trip, User $pengguna): array` dengan kunci `jalur`, `varian` (`'selesai' | 'batal'`), `tanggal`, `hasil`, `posTerjauh`, `jejak`, `garisJalur`, `penandaPos`, `profil`, `pos`, `catatan`, `angka` (`jarak`, `naik`, `waktu`, `tertinggi`, `sumber` = `jejak | jalur | pendaki | kosong`, `keterangan`, `awal`, `ubah`), `tangga` (`baris`, `kalimat`), `banding`, `pencapaian`, `tingkat` (`kalimat`, `url`), `laporan` (`keadaan` = `belum | menunggu | terbit | ditolak`, `alasanTolak`, `privasi`, `formulir`); `HasilPendakian::durasiSesi(?HikingSession): ?string` tetap (riwayat); kolom `hiking_history.recorded_distance_m`, `recorded_gain_m`, `recorded_moving_minutes`, `recorded_highest_m`, `numbers_edited_at`; rute `history.numbers.update` (PUT `history/{trip}/angka`, berbatas laju `angka-pendakian`) dan `reports.update` (PUT `reports/{report}`, berbatas laju `ubah-laporan`); props tambahan `peringatanFoto`; TypeScript `AngkaRingkasan`, `TanggaRingkasan`, `LaporanRingkasan`; komponen `AngkaPendakian`, `BandingDiriSendiri`, `LaporanPendakian`.

- [ ] **Step 1: Tulis test yang gagal.** `HikeSummaryTest` ditulis ulang menurut niat barunya (angka bersumber,
banding diri sendiri, tanpa persen, varian dibatalkan); niat lama (isi dari data tersimpan, hanya pemilik, komponen
peta dan profil bersama, durasi dan pos terjauh dari sesi) tetap diuji. `TripLifecycleTest` mengikuti pengalihan
selesai ke ringkasan.

Buat `tests/Feature/HikeSummaryReportTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\CompletionState;
use App\Enums\ConditionTag;
use App\Enums\HikingSessionStatus;
use App\Enums\ModerationStatus;
use App\Enums\TripStatus;
use App\Models\HikingHistory;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\TrailSegment;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Laporan kondisi dari ringkasan pendakian (spec v3 bagian 5.3 dan 6.13, prototype 12): formulir
 * laporan pindah ke ringkasan, menyebut apa yang terbit dan tidak, dan laporan yang belum
 * dimoderasi dapat diubah pemiliknya. Laporan tetap masuk moderasi dan tidak pernah menjadi
 * status resmi (R-010).
 */
class HikeSummaryReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-09-20 08:00:00', 'UTC'));
    }

    /**
     * @return array{0: User, 1: TripPlan, 2: TrailSegment}
     */
    private function selesai(): array
    {
        $user = User::factory()->create();
        $trail = Trail::factory()->create(['name' => 'Gede via Cibodas']);
        $segmen = TrailSegment::create(['trail_id' => $trail->id, 'sequence' => 1, 'name' => 'Air Panas ke Kandang Badak']);
        $trip = TripPlan::factory()->for($user)->for($trail)->create(['status' => TripStatus::COMPLETED->value]);
        $trip->hikingSession()->create(['user_id' => $user->id, 'status' => HikingSessionStatus::COMPLETED->value, 'started_at' => now()->subDays(2), 'ended_at' => now()->subDays(2)->addHours(9)]);

        HikingHistory::create([
            'user_id' => $user->id,
            'trip_plan_id' => $trip->id,
            'trail_id' => $trail->id,
            'trip_type' => $trip->trip_type->value,
            'completion_state' => CompletionState::COMPLETED->value,
            'completed_at' => Carbon::parse('2026-09-18 09:00:00', 'UTC'),
        ]);

        return [$user, $trip->fresh(), $segmen];
    }

    public function test_the_summary_offers_the_report_form_and_says_what_is_published(): void
    {
        [$user, $trip, $segmen] = $this->selesai();

        $this->actingAs($user)->get(route('history.summary', $trip))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('laporan.keadaan', 'belum')
                ->where('laporan.formulir.tanggal', '2026-09-18')
                ->where('laporan.formulir.segmen', [['value' => $segmen->id, 'label' => '1. Air Panas ke Kandang Badak']])
                ->has('laporan.formulir.tags', count(ConditionTag::cases()))
                ->where('laporan.formulir.kirim', route('reports.store'))
                ->where('laporan.privasi', 'Yang terbit: tanggal, kondisi, segmen, catatan, foto, dan nama tampilan Anda. Tidak terbit: jejak, titik awal, dan titik akhir Anda.'));

        // Kabar GPS foto tinggal di isian foto, tempat keputusannya diambil (EXIF dibuang server, PRD §82).
        $this->assertStringContainsString('Lokasi GPS di dalam foto dihapus sebelum foto disimpan.', File::get(resource_path('js/components/community/LaporanPendakian.tsx')));
    }

    public function test_a_report_sent_from_the_summary_returns_to_it_and_waits_for_moderation(): void
    {
        [$user, $trip, $segmen] = $this->selesai();

        $this->actingAs($user)->post(route('reports.store'), [
            'trail_id' => $trip->trail_id,
            'trip_plan_id' => $trip->id,
            'trail_segment_id' => $segmen->id,
            'hike_date' => '2026-09-18',
            'condition_tags' => [ConditionTag::SLIPPERY->value, ConditionTag::CROWDED->value],
            'note' => 'Batu di aliran air panas licin.',
        ])->assertRedirect(route('history.summary', $trip));

        $laporan = TrailConditionReport::sole();

        $this->assertSame($laporan->id, $trip->history->fresh()->trail_condition_report_id);
        $this->assertSame(ModerationStatus::PENDING, $laporan->moderation_status);

        $this->actingAs($user)->get(route('history.summary', $trip))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('flash.status', 'Laporan terkirim dan menunggu moderasi sebelum tampil untuk pengguna lain.')
                ->where('laporan.keadaan', 'menunggu')
                ->where('laporan.formulir.ubah', route('reports.update', $laporan))
                ->where('laporan.formulir.awal', ['condition_tags' => ['SLIPPERY', 'CROWDED'], 'trail_segment_id' => $segmen->id, 'note' => 'Batu di aliran air panas licin.']));

        // Kabar dan penolakan dibacakan dari wilayah yang terpasang sejak halaman dimuat, bukan
        // Alert yang dipasang bersama isinya (DESIGN.md, umpan balik aksi; pola ReadinessDashboardTest).
        $halaman = File::get(resource_path('js/pages/Community/HikeSummary.tsx'));
        $this->assertStringContainsString("{flash.status ?? ''}", $halaman);
        $this->assertStringContainsString("{peringatanFoto ?? ''}", $halaman);
        $this->assertStringNotContainsString('{flash.status ? <Alert', $halaman);
        $this->assertStringContainsString("{galat.form ?? ''}", File::get(resource_path('js/components/community/LaporanPendakian.tsx')));
    }

    public function test_a_pending_report_can_be_changed_before_moderation(): void
    {
        [$user, $trip] = $this->selesai();
        $laporan = $this->laporanTertaut($user, $trip, ModerationStatus::PENDING);

        $this->actingAs($user)
            ->put(route('reports.update', $laporan), ['condition_tags' => [ConditionTag::DRY->value], 'trail_segment_id' => null, 'note' => 'Kering sepanjang jalur.'])
            ->assertRedirect(route('history.summary', $trip))
            ->assertSessionHas('status', 'Laporan diperbarui dan tetap menunggu moderasi.');

        $this->assertSame([ConditionTag::DRY->value], $laporan->fresh()->condition_tags);
        $this->assertSame('Kering sepanjang jalur.', $laporan->fresh()->note);
    }

    /**
     * Sesudah dimoderasi laporan tidak dapat diubah lagi: yang sudah terbit dibaca pendaki lain
     * apa adanya, dan yang ditolak menyebut alasannya.
     */
    public function test_a_moderated_report_can_no_longer_be_changed(): void
    {
        [$user, $trip] = $this->selesai();
        $laporan = $this->laporanTertaut($user, $trip, ModerationStatus::APPROVED);

        $this->actingAs($user)
            ->put(route('reports.update', $laporan), ['condition_tags' => [ConditionTag::DRY->value]])
            ->assertForbidden();

        $this->actingAs($user)->get(route('history.summary', $trip))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('laporan.keadaan', 'terbit')->where('laporan.formulir.ubah', null));
    }

    public function test_another_hiker_cannot_change_a_report(): void
    {
        [$user, $trip] = $this->selesai();
        $laporan = $this->laporanTertaut($user, $trip, ModerationStatus::PENDING);

        $this->actingAs(User::factory()->create())
            ->put(route('reports.update', $laporan), ['condition_tags' => [ConditionTag::DRY->value]])
            ->assertForbidden();
    }

    public function test_a_changed_report_is_validated_like_a_new_one(): void
    {
        [$user, $trip] = $this->selesai();
        $laporan = $this->laporanTertaut($user, $trip, ModerationStatus::PENDING);

        $this->actingAs($user)
            ->put(route('reports.update', $laporan), ['condition_tags' => [], 'note' => str_repeat('a', 1001)])
            ->assertSessionHasErrors(['condition_tags', 'note']);
    }

    /**
     * Menyelesaikan pendakian membuka ringkasannya, tempat formulir laporan sekarang berada.
     */
    public function test_finishing_a_hike_opens_its_summary(): void
    {
        $trip = TripPlan::factory()->create(['status' => TripStatus::IN_PROGRESS->value]);
        $trip->hikingSession()->create(['user_id' => $trip->user_id, 'status' => HikingSessionStatus::ACTIVE->value, 'started_at' => now()]);

        $this->actingAs($trip->user)
            ->post(route('trips.complete', $trip), ['completion_state' => CompletionState::COMPLETED->value])
            ->assertRedirect(route('history.summary', $trip));
    }

    private function laporanTertaut(User $user, TripPlan $trip, ModerationStatus $status): TrailConditionReport
    {
        $laporan = TrailConditionReport::factory()->for($trip->trail)->create([
            'user_id' => $user->id,
            'moderation_status' => $status->value,
            'condition_tags' => [ConditionTag::SLIPPERY->value],
            'hike_date' => '2026-09-18',
        ]);

        $trip->history->update(['trail_condition_report_id' => $laporan->id]);

        return $laporan;
    }
}
```

Tulis ulang `tests/Feature/HikeSummaryTest.php` utuh:

```php
<?php

namespace Tests\Feature;

use App\Enums\CancellationReason;
use App\Enums\CompletionState;
use App\Enums\ExperienceLevel;
use App\Enums\HikingSessionStatus;
use App\Enums\TripStatus;
use App\Models\Checkpoint;
use App\Models\HikeTrackPoint;
use App\Models\HikingHistory;
use App\Models\HikingSession;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Ringkasan pendakian (spec v3 bagian 6.13, prototype 12; rencana induk W6 butir 9).
 *
 * Peak-end rule mengatakan akhir sebuah pengalaman menentukan bagaimana seluruhnya diingat.
 * Sejak W6 halamannya punya dua varian (selesai dan dibatalkan, DK-50), angka dari rekaman
 * bila ada dan dari data jalur bila tidak, dengan "Ubah angka"; dibanding pendakian sendiri
 * saja (R-015); dan tanpa persen persiapan (R-005), yang sebelumnya tampil sebagai "80%".
 * Seluruh isinya berasal dari data yang sudah tersimpan.
 */
class HikeSummaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-09-20 08:00:00', 'UTC'));
    }

    /**
     * Budi menyelesaikan Jalur Selo (naik 1.000 m, 9,7 km sekali jalan) 15 September; sesi
     * mode lapangan pukul 04.00 sampai 12.30 UTC dengan jeda 30 menit.
     *
     * @return array{0: User, 1: TripPlan}
     */
    private function pendakianSelesai(
        CompletionState $hasil = CompletionState::COMPLETED,
        bool $denganSesi = true,
        ?int $posTercapai = 3,
        ?User $user = null,
        int $naik = 1000,
        string $nama = 'Jalur Selo',
        ?Trail $trail = null,
    ): array {
        $user ??= User::factory()->create();

        if ($trail === null) {
            $trail = Trail::factory()->create(['name' => $nama, 'elevation_gain_m' => $naik, 'distance_km' => 9.7]);

            foreach (range(1, 4) as $urutan) {
                Checkpoint::factory()->for($trail)->create([
                    'sequence' => $urutan,
                    'name' => 'Pos '.$urutan,
                    'elevation_m' => 1200 + $urutan * 200,
                    // Berkoordinat, karena tanpa PostGIS geometri jalurnya null dan pos
                    // adalah satu-satunya yang dapat menggambar peta.
                    'latitude' => -7.45 - $urutan * 0.01,
                    'longitude' => 110.44,
                ]);
            }
        }

        $trip = TripPlan::factory()->create([
            'user_id' => $user->id,
            'trail_id' => $trail->id,
            'status' => TripStatus::COMPLETED->value,
            'planned_date' => '2026-09-15',
        ]);

        if ($denganSesi) {
            HikingSession::create([
                'trip_plan_id' => $trip->id,
                'user_id' => $user->id,
                'status' => HikingSessionStatus::COMPLETED->value,
                'started_at' => Carbon::parse('2026-09-15 04:00:00', 'UTC'),
                'ended_at' => Carbon::parse('2026-09-15 12:30:00', 'UTC'),
                'paused_seconds' => 1800,
                'reached_checkpoint_sequence' => $posTercapai,
            ]);
        }

        HikingHistory::create([
            'user_id' => $user->id,
            'trip_plan_id' => $trip->id,
            'trail_id' => $trail->id,
            'trip_type' => $trip->trip_type->value,
            'completion_state' => $hasil->value,
            'preparation_completion_percent' => 80,
            'personal_notes' => 'Kabut turun sejak Pos 3.',
            'completed_at' => Carbon::parse('2026-09-15 12:30:00', 'UTC'),
        ]);

        return [$user, $trip->fresh()];
    }

    private function buka(User $user, TripPlan $trip): TestResponse
    {
        return $this->actingAs($user)->get(route('history.summary', $trip));
    }

    public function test_it_shows_what_the_hike_actually_was(): void
    {
        [$user, $trip] = $this->pendakianSelesai();

        $this->buka($user, $trip)->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Community/HikeSummary')
            ->where('title', 'Hasil pendakian Jalur Selo')
            ->where('varian', 'selesai')
            ->where('jalur.nama', 'Jalur Selo')
            ->where('hasil', 'Selesai sampai target')
            ->where('catatan', 'Kabut turun sejak Pos 3.'));
    }

    /**
     * Rekam jejak mati: angka dari data jalur, disebut begitu, dengan jarak pulang-pergi (9,7 km
     * sekali jalan menjadi 19,4 km), dan waktu bergerak dari sesi dikurangi jeda (8 jam 30 menit
     * dikurangi 30 menit).
     */
    public function test_without_a_track_the_numbers_come_from_the_trail_and_say_so(): void
    {
        [$user, $trip] = $this->pendakianSelesai();

        $this->buka($user, $trip)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('angka.jarak', '19,4 km')
            ->where('angka.naik', '↑1.000 m')
            ->where('angka.waktu', '8 jam')
            ->where('angka.tertinggi', '2.000 m')
            ->where('angka.sumber', 'jalur')
            ->where('angka.keterangan', 'Tanpa rekaman jejak, angka ini diisi dari data jalur, dengan jarak pulang-pergi lewat jalur yang sama. Ubah bila berbeda.')
            ->where('angka.awal', ['jarak_km' => 19.4, 'naik_m' => 1000, 'waktu_menit' => 480, 'tertinggi_m' => 2000]));
    }

    public function test_with_a_track_the_numbers_come_from_the_recording(): void
    {
        [$user, $trip] = $this->pendakianSelesai();

        foreach ([[-7.4500, 1500], [-7.4590, 1700], [-7.4680, 1650], [-7.4770, 1900]] as $i => [$lintang, $tinggi]) {
            HikeTrackPoint::create(['hiking_session_id' => $trip->hikingSession->id, 'recorded_at' => Carbon::parse('2026-09-15 05:00:00', 'UTC')->addMinutes($i * 20), 'latitude' => $lintang, 'longitude' => 110.44, 'elevation_m' => $tinggi]);
        }

        $this->buka($user, $trip)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('angka.jarak', '3,0 km')
            ->where('angka.naik', '↑450 m')
            ->where('angka.tertinggi', '1.900 m')
            ->where('angka.sumber', 'jejak')
            ->where('angka.keterangan', 'Dari rekaman jejak Anda. Ubah bila berbeda.'));
    }

    /**
     * "Ubah angka": angka pendaki menggantikan angka turunan dan disebut sumbernya. Hanya
     * pemilik, dengan rentang yang masuk akal, dan dibatasi laju.
     */
    public function test_the_hiker_can_correct_the_numbers(): void
    {
        [$user, $trip] = $this->pendakianSelesai();

        $this->actingAs($user)
            ->put(route('history.numbers.update', $trip), ['jarak_km' => 19.4, 'naik_m' => 1660, 'waktu_menit' => 665, 'tertinggi_m' => 2958])
            ->assertRedirect(route('history.summary', $trip))
            ->assertSessionHas('status', 'Angka pendakian diperbarui.');

        $this->buka($user, $trip)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('angka.jarak', '19,4 km')
            ->where('angka.naik', '↑1.660 m')
            ->where('angka.waktu', '11 jam 5 menit')
            ->where('angka.tertinggi', '2.958 m')
            ->where('angka.sumber', 'pendaki')
            ->where('angka.keterangan', 'Angka ini Anda ubah sendiri.'));
    }

    public function test_corrected_numbers_are_validated_and_owned(): void
    {
        [$user, $trip] = $this->pendakianSelesai();

        $this->actingAs($user)
            ->put(route('history.numbers.update', $trip), ['jarak_km' => -3, 'naik_m' => 99999, 'waktu_menit' => 'lama', 'tertinggi_m' => 7000])
            ->assertSessionHasErrors(['jarak_km', 'naik_m', 'waktu_menit', 'tertinggi_m']);

        $this->actingAs(User::factory()->create())
            ->put(route('history.numbers.update', $trip), ['jarak_km' => 5])
            ->assertForbidden();

        $this->assertNull($trip->history->fresh()->recorded_distance_m);
    }

    /**
     * Dibanding pendakian sendiri (R-015): tangga dari riwayat pendaki ini saja, kalimat
     * selisih dari tanjakan tertinggi sebelumnya, dan tidak pernah pendaki lain.
     */
    public function test_the_hike_is_compared_with_the_hikers_own_history_only(): void
    {
        $budi = User::factory()->create();
        [, $prau] = $this->pendakianSelesai(user: $budi, naik: 700, nama: 'Prau');
        [, $andong] = $this->pendakianSelesai(user: $budi, naik: 380, nama: 'Andong');
        $this->pendakianSelesai(naik: 2500, nama: 'Jalur orang lain');
        [, $trip] = $this->pendakianSelesai(user: $budi, naik: 1660, nama: 'Gede');

        $prau->history->update(['completed_at' => Carbon::parse('2026-08-02 09:00:00', 'UTC')]);
        $andong->history->update(['completed_at' => Carbon::parse('2026-07-05 09:00:00', 'UTC')]);

        $this->buka($budi, $trip)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('tangga.baris', [
                ['nama' => 'Andong', 'm' => 380, 'teks' => '380 m', 'ini' => false],
                ['nama' => 'Prau', 'm' => 700, 'teks' => '700 m', 'ini' => false],
                ['nama' => 'Gede', 'm' => 1660, 'teks' => '1.660 m', 'ini' => true],
            ])
            ->where('tangga.kalimat', '+960 m dari tanjakan tertinggi Anda sebelumnya (Prau, 700 m). Tidak ada pembanding dengan pendaki lain, dan tidak ada catatan kecepatan.')
            ->where('pencapaian', ['Tanjakan pertama di atas 1.000 m']));
    }

    /**
     * Tangga ringkasan memakai ukuran tangga Beranda: tanjakan jalur, satu anak tangga per jalur.
     * Angka yang diubah pendaki hanya mengganti panel angka, jadi Beranda dan ringkasan tidak
     * menggambar dua tangga yang berbeda untuk riwayat yang sama (ruling W6-R13).
     */
    public function test_the_ladder_uses_the_same_measure_as_the_home_ladder(): void
    {
        $budi = User::factory()->create();
        [, $prau] = $this->pendakianSelesai(user: $budi, naik: 700, nama: 'Prau');
        [, $prauLagi] = $this->pendakianSelesai(user: $budi, trail: $prau->trail);
        [, $trip] = $this->pendakianSelesai(user: $budi, naik: 1660, nama: 'Gede');

        $prau->history->update(['completed_at' => Carbon::parse('2026-07-05 09:00:00', 'UTC')]);
        $prauLagi->history->update(['completed_at' => Carbon::parse('2026-08-02 09:00:00', 'UTC')]);
        $trip->history->update(['recorded_gain_m' => 1720, 'numbers_edited_at' => now()]);

        $this->buka($budi, $trip)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('angka.naik', '↑1.720 m')
            ->where('tangga.baris', [
                ['nama' => 'Prau', 'm' => 700, 'teks' => '700 m', 'ini' => false],
                ['nama' => 'Gede', 'm' => 1660, 'teks' => '1.660 m', 'ini' => true],
            ]));
    }

    /**
     * Berbalik di jalur bukan tanjakan yang dituntaskan: tidak masuk tangga dan tidak memberi
     * pencapaian, dan kalimatnya tidak menghukum. Tanpa jejak, angka jalur penuh akan
     * melebih-lebihkan pendakian yang berbalik, jadi yang tidak diketahui tetap kosong (R-007).
     */
    public function test_an_abandoned_hike_is_named_with_its_reason_and_kept_out_of_the_ladder(): void
    {
        [$user, $trip] = $this->pendakianSelesai(hasil: CompletionState::ABANDONED, posTercapai: 2);
        $trip->update(['cancellation_reason' => CancellationReason::WEATHER->value]);

        $this->buka($user, $trip)->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->where('hasil', 'Dibatalkan di jalur · Cuaca')
            ->where('posTerjauh', 'Pos 2')
            ->where('angka.jarak', null)
            ->where('angka.naik', null)
            ->where('angka.tertinggi', null)
            ->where('angka.waktu', '8 jam')
            ->where('angka.sumber', 'kosong')
            ->where('tangga', null)
            ->where('pencapaian', [])
            ->where('banding', 'Pendakian yang berbalik sebelum tujuan tidak dihitung sebagai tanjakan yang dituntaskan. Keputusan berbalik tetap tercatat apa adanya.'));
    }

    /**
     * BR-11: riwayat tidak menaikkan tingkat pengalaman; pendaki sendiri yang mengubahnya.
     */
    public function test_the_experience_level_is_never_raised_by_the_history(): void
    {
        [$user, $trip] = $this->pendakianSelesai();
        $user->profile()->create(['experience_level' => ExperienceLevel::BEGINNER->value, 'completed_at' => now()]);

        $this->buka($user, $trip)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('tingkat.kalimat', 'Tingkat pengalaman Anda tetap '.ExperienceLevel::BEGINNER->label().' sampai Anda mengubahnya sendiri di Profil.')
            ->where('tingkat.url', route('profile')));
    }

    /**
     * R-005: tidak ada persen di ringkasan, termasuk persiapan yang dahulu tampil "80%".
     */
    public function test_no_percent_reaches_the_summary(): void
    {
        [$user, $trip] = $this->pendakianSelesai();

        $props = json_encode($this->buka($user, $trip)->viewData('page')['props'], JSON_UNESCAPED_UNICODE);

        $this->assertStringNotContainsString('%', $props);
    }

    /**
     * Varian dibatalkan (DK-50): trip yang dibatalkan sebelum mendaki punya ringkasan pendek
     * dengan alasannya, tanpa angka dan tanpa formulir laporan.
     */
    public function test_a_cancelled_trip_has_a_short_summary_with_its_reason(): void
    {
        $trip = TripPlan::factory()->create(['status' => TripStatus::CANCELLED->value, 'cancellation_reason' => CancellationReason::OFFICIAL_STATUS->value, 'planned_date' => '2026-09-25']);

        $this->buka($trip->user, $trip)->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->where('varian', 'batal')
            ->where('hasil', 'Dibatalkan · Status resmi')
            ->where('angka', null)
            ->where('laporan', null));
    }

    public function test_the_duration_and_furthest_post_still_read_from_the_session(): void
    {
        [$user, $trip] = $this->pendakianSelesai(posTercapai: 3);

        $this->buka($user, $trip)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('posTerjauh', 'Pos 3')
            ->where('angka.waktu', '8 jam'));
    }

    /**
     * Keadaan yang umum, bukan pinggiran: banyak pendaki tidak pernah membuka mode lapangan.
     * Ringkasannya tetap ada; waktu bergerak dan pos terjauh tidak tercatat.
     */
    public function test_a_trip_that_never_used_field_mode_still_has_a_summary(): void
    {
        [$user, $trip] = $this->pendakianSelesai(denganSesi: false, posTercapai: null);

        $this->buka($user, $trip)->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->where('angka.waktu', null)
            ->where('posTerjauh', null)
            ->where('jejak', null));
    }

    public function test_another_hiker_cannot_read_it(): void
    {
        [, $trip] = $this->pendakianSelesai();

        $this->actingAs(User::factory()->create())
            ->get(route('history.summary', $trip))
            ->assertForbidden();
    }

    /**
     * Trip yang belum selesai dan tidak dibatalkan belum punya hasil untuk diringkas.
     */
    public function test_a_trip_that_is_not_finished_has_nothing_to_summarise(): void
    {
        [$user, $trip] = $this->pendakianSelesai();

        $trip->history()->delete();
        $trip->update(['status' => TripStatus::PLANNED->value]);

        $this->buka($user, $trip->fresh())->assertNotFound();
    }

    /**
     * Profil elevasi dan peta pos dipakai ulang: server mengirim titiknya, komponen bersama
     * yang menggambarnya.
     */
    public function test_it_draws_the_trail_with_the_shared_components(): void
    {
        [$user, $trip] = $this->pendakianSelesai();

        $trip->trail->update([
            'elevation_profile' => [
                ['km' => 0.0, 'm' => 1200],
                ['km' => 2.0, 'm' => 1800],
                ['km' => 4.0, 'm' => 2400],
            ],
        ]);

        $this->buka($user, $trip)->assertInertia(fn (AssertableInertia $page) => $page
            ->has('profil.titik', 3)
            ->where('angka.tertinggi', '2.400 m')
            ->has('penandaPos', 4)
            ->where('penandaPos.0.label', '1. Pos 1')
            ->has('pos', 4)
            ->where('pos.1.antara', fn (?string $antara) => str_contains((string) $antara, 'naik 200 m')));

        $sumber = File::get(resource_path('js/pages/Community/HikeSummary.tsx'));
        $this->assertStringContainsString('<ProfilElevasi profil={profil} />', $sumber);
        $this->assertStringContainsString('<DaftarPos pos={pos} />', $sumber);
        $this->assertStringContainsString('<PetaGaris', $sumber);
    }
}
```

`tests/Feature/TripLifecycleTest.php`:

1. Ganti:

```php
        // Selesai mendaki langsung mengajak menulis laporan kondisi (loop §7: HIKE → REPORT).
        $this->actingAs($trip->user)
            ->post(route('trips.complete', $trip), ['completion_state' => CompletionState::COMPLETED->value], ['X-Inertia' => 'true'])
            ->assertRedirect(route('reports.create', ['trail' => $trip->trail_id, 'trip' => $trip->id]));

        $this->assertSame(TripStatus::COMPLETED, $trip->fresh()->status);
```

dengan:

```php
        // Selesai mendaki langsung mengajak menulis laporan kondisi (loop §7: HIKE → REPORT).
        // Sejak W6 formulirnya ada di ringkasan pendakian (spec v3 bagian 5.3 dan 6.13).
        $this->actingAs($trip->user)
            ->post(route('trips.complete', $trip), ['completion_state' => CompletionState::COMPLETED->value], ['X-Inertia' => 'true'])
            ->assertRedirect(route('history.summary', $trip));

        $this->assertSame(TripStatus::COMPLETED, $trip->fresh()->status);
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='HikeSummary|TripLifecycle|HikeTrackOnSummary|ConditionReport|HikingHistory'`
Expected: FAIL. Pada simulasi rencana: 55 test, 33 lulus, 16 gagal, 6 error. Yang merah: 14 test `HikeSummaryTest` (props `varian`, `angka`, `tangga`, dan `tingkat` belum ada; persen persiapan masih tampil), tujuh test `HikeSummaryReportTest` (formulir laporan di ringkasan, rute `reports.update`, dan selesai yang membuka ringkasan belum ada), dan `TripLifecycleTest::test_an_in_progress_trip_can_be_completed` (selesai masih menuju formulir laporan).

- [ ] **Step 3: Tulis implementasinya.** Controller dengan
`DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:controller --no-interaction Community/HikeNumbersController --invokable`,
request dengan `... make:request --no-interaction Community/UpdateHikeNumbersRequest` dan
`... make:request --no-interaction UpdateConditionReportRequest`, migrasi dengan
`... make:migration --no-interaction add_recorded_values_to_hiking_history_table` (ganti nama berkasnya), lalu isinya
diganti persis. Migrasi rintisan `2026_09_25_000919_add_source_and_recorded_values_to_hiking_history_table` (W7) tidak
disentuh.

Buat `app/Http/Controllers/Community/HikeNumbersController.php`:

```php
<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Http\Requests\Community\UpdateHikeNumbersRequest;
use App\Models\TripPlan;
use Illuminate\Http\RedirectResponse;

/**
 * "Ubah angka" ringkasan pendakian (spec v3 bagian 6.13): angka pendaki menggantikan angka yang
 * diturunkan dari rekaman atau data jalur, dan ringkasan menyebut bahwa angkanya diubah.
 * Pemilik dan trip dari rute, bukan dari isian.
 */
class HikeNumbersController extends Controller
{
    public function __invoke(UpdateHikeNumbersRequest $request, TripPlan $trip): RedirectResponse
    {
        $riwayat = $trip->history;

        abort_if($riwayat === null, 404);

        $jarak = $request->validated('jarak_km');

        $riwayat->update([
            'recorded_distance_m' => $jarak === null ? null : (int) round((float) $jarak * 1000),
            'recorded_gain_m' => $request->validated('naik_m'),
            'recorded_moving_minutes' => $request->validated('waktu_menit'),
            'recorded_highest_m' => $request->validated('tertinggi_m'),
            'numbers_edited_at' => now(),
        ]);

        return redirect()->route('history.summary', $trip)->with('status', 'Angka pendakian diperbarui.');
    }
}
```

Buat `app/Http/Requests/Community/UpdateHikeNumbersRequest.php`:

```php
<?php

namespace App\Http\Requests\Community;

use App\Models\TripPlan;
use Illuminate\Foundation\Http\FormRequest;

/**
 * "Ubah angka" di ringkasan pendakian (spec v3 bagian 6.13, ruling W6-R13). Setiap angka
 * opsional: kosong berarti tidak diketahui, bukan nol (R-007). Batasnya batas akal sehat
 * pendakian satu trip di Indonesia (puncak tertinggi 4.884 m), bukan penilaian.
 */
class UpdateHikeNumbersRequest extends FormRequest
{
    public function authorize(): bool
    {
        $trip = $this->route('trip');

        return $trip instanceof TripPlan && $this->user()?->can('update', $trip) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'jarak_km' => ['nullable', 'numeric', 'min:0.1', 'max:200'],
            'naik_m' => ['nullable', 'integer', 'min:0', 'max:9000'],
            'waktu_menit' => ['nullable', 'integer', 'min:1', 'max:7200'],
            'tertinggi_m' => ['nullable', 'integer', 'min:0', 'max:6000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'jarak_km' => 'Jarak diisi dalam kilometer, 0,1 sampai 200.',
            'naik_m' => 'Tanjakan diisi dalam meter, 0 sampai 9.000.',
            'waktu_menit' => 'Waktu bergerak diisi dalam menit, 1 sampai 7.200.',
            'tertinggi_m' => 'Titik tertinggi diisi dalam meter, 0 sampai 6.000.',
        ];
    }
}
```

Buat `app/Http/Requests/UpdateConditionReportRequest.php`:

```php
<?php

namespace App\Http\Requests;

use App\Enums\ConditionTag;
use App\Enums\ModerationStatus;
use App\Models\TrailConditionReport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * "Ubah sebelum dimoderasi" (spec v3 bagian 6.13): pemilik laporan, selama laporannya masih
 * menunggu moderasi. Sesudah dimoderasi, laporan dibaca pendaki lain apa adanya dan tidak dapat
 * diubah lagi. Aturan isiannya sama dengan laporan baru (StoreConditionReportRequest).
 */
class UpdateConditionReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $laporan = $this->route('report');

        return $laporan instanceof TrailConditionReport
            && $laporan->user_id === $this->user()?->id
            && $laporan->moderation_status === ModerationStatus::PENDING;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var TrailConditionReport $laporan */
        $laporan = $this->route('report');

        return [
            'condition_tags' => ['required', 'array', 'min:1'],
            'condition_tags.*' => [Rule::enum(ConditionTag::class)],
            'trail_segment_id' => ['nullable', Rule::exists('trail_segments', 'id')->where('trail_id', $laporan->trail_id)],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
```

Buat `database/migrations/2026_10_07_000400_add_recorded_values_to_hiking_history_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Angka pendakian yang ditulis pendaki lewat "Ubah angka" (spec v3 bagian 6.13, ruling W6-R13).
 *
 * Tanpa isian, ringkasan menurunkan angkanya dari rekaman jejak atau data jalur. Isian di sini
 * menggantikan turunan itu dan ditandai waktu ubahnya. Null berarti tidak ditulis, bukan nol
 * (R-007). Aditif dan boleh kosong untuk baris lama. Migrasi rintisan
 * 2026_09_25_000919_add_source_and_recorded_values_to_hiking_history_table tidak disentuh:
 * kolom asal catatan (FR-25) tetap milik W7, yang memakai kolom angka di sini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hiking_history', function (Blueprint $table) {
            $table->unsignedInteger('recorded_distance_m')->nullable()->after('personal_notes');
            $table->unsignedInteger('recorded_gain_m')->nullable()->after('recorded_distance_m');
            $table->unsignedInteger('recorded_moving_minutes')->nullable()->after('recorded_gain_m');
            $table->unsignedInteger('recorded_highest_m')->nullable()->after('recorded_moving_minutes');
            $table->timestamp('numbers_edited_at')->nullable()->after('recorded_highest_m');
        });
    }

    public function down(): void
    {
        Schema::table('hiking_history', function (Blueprint $table) {
            $table->dropColumn(['recorded_distance_m', 'recorded_gain_m', 'recorded_moving_minutes', 'recorded_highest_m', 'numbers_edited_at']);
        });
    }
};
```

Buat `resources/js/components/community/AngkaPendakian.tsx`:

```tsx
import { useForm } from '@inertiajs/react';
import { useRef, useState, type FormEvent } from 'react';
import { Button } from '@/components/ui/Button';
import { InputError, Label, TextInput } from '@/components/ui/Field';
import { LembarBawah } from '@/components/ui/Lembar';
import type { AngkaRingkasan } from './ringkasan';

const butir: Array<{ kunci: 'jarak' | 'naik' | 'waktu' | 'tertinggi'; judul: string }> = [
    { kunci: 'jarak', judul: 'Jarak' },
    { kunci: 'naik', judul: 'Tanjakan' },
    { kunci: 'waktu', judul: 'Waktu bergerak' },
    { kunci: 'tertinggi', judul: 'Titik tertinggi' },
];

/**
 * Angka pendakian (spec v3 bagian 6.13, prototype 12): empat angka pada satu permukaan, sumbernya
 * disebut di bawahnya, dan "Ubah angka" bila pendaki punya catatan yang lebih tepat. Yang belum
 * ada ditulis "Belum ada", tidak pernah nol (R-007).
 */
export function AngkaPendakian({ angka }: { angka: AngkaRingkasan }) {
    const [terbuka, setTerbuka] = useState(false);
    const pemicu = useRef<HTMLButtonElement>(null);
    const awalJam = angka.awal.waktu_menit === null ? '' : String(Math.floor(angka.awal.waktu_menit / 60));
    const awalMenit = angka.awal.waktu_menit === null ? '' : String(angka.awal.waktu_menit % 60);
    const form = useForm({
        jarak_km: angka.awal.jarak_km === null ? '' : String(angka.awal.jarak_km),
        naik_m: angka.awal.naik_m === null ? '' : String(angka.awal.naik_m),
        jam: awalJam,
        menit: awalMenit,
        tertinggi_m: angka.awal.tertinggi_m === null ? '' : String(angka.awal.tertinggi_m),
    });

    // Galat waktu_menit milik isian gabungan jam dan menit, jadi tidak punya kunci di data formulir.
    const galat = form.errors as Record<string, string | undefined>;

    const kirim = (e: FormEvent) => {
        e.preventDefault();
        form.transform((d) => ({
            jarak_km: d.jarak_km === '' ? null : d.jarak_km.replace(',', '.'),
            naik_m: d.naik_m === '' ? null : d.naik_m,
            waktu_menit: d.jam === '' && d.menit === '' ? null : Number(d.jam || 0) * 60 + Number(d.menit || 0),
            tertinggi_m: d.tertinggi_m === '' ? null : d.tertinggi_m,
        }));
        form.put(angka.ubah, { preserveScroll: true, onSuccess: () => setTerbuka(false) });
    };

    return (
        <section aria-labelledby="judul-angka" className="rounded-utama bg-surface-inverse p-5 text-inverse">
            <h2 id="judul-angka" className="sr-only">
                Angka pendakian
            </h2>
            <dl className="grid grid-cols-2 gap-x-6 gap-y-5">
                {butir.map((b) => (
                    <div key={b.kunci} className="min-w-0">
                        <dt className="text-kecil opacity-90">{b.judul}</dt>
                        <dd className={angka[b.kunci] === null ? 'mt-1 text-isi font-bold' : 'mt-1 break-words text-judul tabular-nums'}>{angka[b.kunci] ?? 'Belum ada'}</dd>
                    </div>
                ))}
            </dl>
            <div className="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-subtle pt-4">
                <p className="min-w-0 flex-1 text-kecil">{angka.keterangan}</p>
                <button
                    ref={pemicu}
                    type="button"
                    onClick={() => setTerbuka(true)}
                    className="inline-flex min-h-11 items-center rounded-tombol border border-current px-4 text-kecil font-bold focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2"
                >
                    Ubah angka
                </button>
            </div>

            <LembarBawah
                terbuka={terbuka}
                onTutup={() => setTerbuka(false)}
                pemicu={pemicu}
                judul="Ubah angka pendakian"
                keterangan="Isi dari catatan Anda sendiri, misalnya jam tangan atau aplikasi lain. Kosongkan yang tidak Anda ketahui."
            >
                <form onSubmit={kirim} className="space-y-4">
                    <Isian id="angka-jarak" label="Jarak (km)" nilai={form.data.jarak_km} onUbah={(v) => form.setData('jarak_km', v)} galat={form.errors.jarak_km} desimal />
                    <Isian id="angka-naik" label="Tanjakan (m)" nilai={form.data.naik_m} onUbah={(v) => form.setData('naik_m', v)} galat={form.errors.naik_m} />
                    <fieldset aria-describedby={galat.waktu_menit ? 'angka-waktu-galat' : undefined}>
                        <legend className="text-isi font-bold text-primary">Waktu bergerak</legend>
                        <div className="mt-2 flex gap-3">
                            <Isian id="angka-jam" label="Jam" nilai={form.data.jam} onUbah={(v) => form.setData('jam', v)} />
                            <Isian id="angka-menit" label="Menit" nilai={form.data.menit} onUbah={(v) => form.setData('menit', v)} />
                        </div>
                        <InputError id="angka-waktu-galat" messages={galat.waktu_menit} className="mt-2" />
                    </fieldset>
                    <Isian id="angka-tertinggi" label="Titik tertinggi (m)" nilai={form.data.tertinggi_m} onUbah={(v) => form.setData('tertinggi_m', v)} galat={form.errors.tertinggi_m} />
                    <Button type="submit" processing={form.processing}>
                        Simpan angka
                    </Button>
                </form>
            </LembarBawah>
        </section>
    );
}

function Isian({ id, label, nilai, onUbah, galat, desimal = false }: { id: string; label: string; nilai: string; onUbah: (nilai: string) => void; galat?: string; desimal?: boolean }) {
    return (
        <div className="min-w-0 flex-1">
            <Label htmlFor={id}>{label}</Label>
            <TextInput
                id={id}
                inputMode={desimal ? 'decimal' : 'numeric'}
                value={nilai}
                onChange={(e) => onUbah(e.target.value)}
                aria-invalid={galat ? true : undefined}
                aria-describedby={galat ? `${id}-galat` : undefined}
                className="mt-1 w-full tabular-nums"
            />
            <InputError id={`${id}-galat`} messages={galat} className="mt-2" />
        </div>
    );
}
```

Buat `resources/js/components/community/BandingDiriSendiri.tsx`:

```tsx
import { Lencana } from '@/components/ui/Lencana';
import { Tautan } from '@/components/ui/Tautan';
import { fokusTerlihat } from '@/components/ui/gaya';
import { kelas } from '@/lib/kelas';
import type { TanggaRingkasan } from './ringkasan';

interface BandingDiriSendiriProps {
    tangga: TanggaRingkasan | null;
    /** Kalimat pengganti tangga: pendakian yang berbalik, atau tanjakan jalur yang belum tercatat. */
    banding: string | null;
    pencapaian: string[];
    tingkat: { kalimat: string; url: string };
}

/**
 * Dibanding pendakian Anda sendiri (spec v3 bagian 6.13, prototype 12; R-015): tangga tanjakan
 * dengan ukuran yang sama dengan Beranda, satu kalimat selisih, pencapaian pribadi, dan BR-11.
 * Tanpa pendaki lain, peringkat, atau kecepatan.
 *
 * Satu seri dengan skala sama, jadi tanpa legenda: setiap anak tangga menulis nama dan
 * nilainya, sehingga daftar ini sekaligus tabelnya dan batangnya disembunyikan dari pembaca
 * layar. Pendakian ini dibedakan dengan isian penuh, huruf tebal, dan kata "ini" untuk pembaca
 * layar, bukan warna saja.
 */
export function BandingDiriSendiri({ tangga, banding, pencapaian, tingkat }: BandingDiriSendiriProps) {
    const puncak = Math.max(1, ...(tangga?.baris.map((b) => b.m) ?? []));

    return (
        <section aria-labelledby="judul-banding">
            <h2 id="judul-banding" className="text-bagian text-primary">
                Dibanding pendakian Anda sendiri
            </h2>
            <p className="mt-1 text-kecil text-secondary">Tangga diukur dari tanjakan jalur, bukan ketinggian puncak.</p>

            <div className="mt-3 rounded-kartu border border-subtle bg-surface p-4 sm:p-5">
                {tangga ? (
                    <>
                        <ol className="grid auto-cols-fr grid-flow-col items-start gap-2 sm:gap-3">
                            {tangga.baris.map((b) => (
                                <li key={`${b.nama}-${b.m}-${b.ini ? 'ini' : 'lalu'}`} className="flex min-w-0 flex-col text-center">
                                    {/* Nilai dan batang dalam tinggi tetap, sehingga semua batang berdiri di garis dasar yang sama walau nama jalurnya membungkus. */}
                                    <span className="flex h-36 flex-col justify-end">
                                        <span data-angka className={kelas('text-meta tabular-nums', b.ini ? 'font-bold text-primary' : 'text-secondary')}>
                                            {b.teks}
                                        </span>
                                        <span
                                            aria-hidden="true"
                                            className={kelas('mt-1 block w-full rounded-t-kecil', b.ini ? 'bg-brand-700' : 'bg-brand-200')}
                                            style={{ height: `${Math.max(6, Math.round((b.m / puncak) * 112))}px` }}
                                        />
                                    </span>
                                    <span className={kelas('mt-1.5 break-words text-meta', b.ini ? 'font-bold text-primary' : 'text-secondary')}>
                                        {b.nama}
                                        {b.ini ? <span className="sr-only">, pendakian ini</span> : null}
                                    </span>
                                </li>
                            ))}
                        </ol>
                        <p className="mt-4 text-isi text-primary">{tangga.kalimat}</p>
                    </>
                ) : (
                    <p className="text-isi text-secondary">{banding}</p>
                )}

                {pencapaian.length > 0 ? (
                    <ul aria-label="Pencapaian pribadi" className="mt-3 flex flex-wrap gap-2">
                        {pencapaian.map((p) => (
                            <li key={p}>
                                <Lencana nada="merek" bentuk="pil" ikon="tanjakan">
                                    {p}
                                </Lencana>
                            </li>
                        ))}
                    </ul>
                ) : null}

                <p className="mt-4 border-t border-subtle pt-3 text-kecil text-secondary">
                    {tingkat.kalimat}{' '}
                    <Tautan href={tingkat.url} className={kelas('font-bold text-brand-ink underline underline-offset-2', fokusTerlihat)}>
                        Buka Profil
                    </Tautan>
                </p>
            </div>
        </section>
    );
}
```

Buat `resources/js/components/community/LaporanPendakian.tsx`:

```tsx
import { useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import { Button } from '@/components/ui/Button';
import { ChipPilihan } from '@/components/ui/Chip';
import { InputError, Label, Select, Textarea } from '@/components/ui/Field';
import { Icon } from '@/components/ui/Icon';
import type { LaporanRingkasan } from './ringkasan';

interface Isian {
    trail_id: number;
    trip_plan_id: number;
    hike_date: string;
    condition_tags: string[];
    trail_segment_id: string;
    note: string;
    photo: File | null;
}

const tanggalPendek = (iso: string) => new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric', timeZone: 'UTC' }).format(new Date(`${iso}T00:00:00Z`));

/**
 * "Bantu pendaki berikutnya" (spec v3 bagian 6.13, prototype 12): laporan kondisi langsung dari
 * ringkasan, dengan tanggal pendakian yang sudah diketahui. Tombol kirim aktif setelah satu
 * kondisi dipilih, dan kalimat di bawahnya menyebut sebabnya. Laporan selalu melewati moderasi
 * dan tidak pernah mengubah status resmi (R-010); yang terbit dan tidak terbit ditulis sebelum
 * pendaki mengirim. Sesudah terkirim, laporan dapat diubah selama belum dimoderasi.
 */
export function LaporanPendakian({ laporan }: { laporan: LaporanRingkasan }) {
    const { formulir } = laporan;
    const [mengubah, setMengubah] = useState(false);
    const form = useForm<Isian>({
        trail_id: formulir.trailId,
        trip_plan_id: formulir.tripId,
        hike_date: formulir.tanggal,
        condition_tags: formulir.awal?.condition_tags ?? [],
        trail_segment_id: formulir.awal?.trail_segment_id ? String(formulir.awal.trail_segment_id) : '',
        note: formulir.awal?.note ?? '',
        photo: null,
    });

    // Galat per butir (condition_tags.0) dan galat batas laju tidak punya kunci di data formulir.
    const galat = form.errors as Record<string, string | undefined>;
    const galatKondisi = galat.condition_tags ?? Object.entries(galat).find(([k]) => k.startsWith('condition_tags.'))?.[1];
    const adaKondisi = form.data.condition_tags.length > 0;

    const ubahKondisi = (nilai: string, dipilih: boolean) =>
        form.setData('condition_tags', dipilih ? [...form.data.condition_tags, nilai] : form.data.condition_tags.filter((t) => t !== nilai));

    const kirim = (e: FormEvent) => {
        e.preventDefault();

        if (mengubah && formulir.ubah) {
            form.transform((d) => ({ condition_tags: d.condition_tags, trail_segment_id: d.trail_segment_id, note: d.note }));
            form.put(formulir.ubah, { preserveScroll: true, onSuccess: () => setMengubah(false) });

            return;
        }

        form.post(formulir.kirim, { forceFormData: true, preserveScroll: true });
    };

    return (
        <section aria-labelledby="judul-laporan">
            <h2 id="judul-laporan" className="text-bagian text-primary">
                Bantu pendaki berikutnya
            </h2>

            {laporan.keadaan !== 'belum' && !mengubah ? (
                <div className="mt-3 rounded-kartu border-[1.5px] border-dashed border-community-500 bg-community-50 p-4">
                    <p className="text-isi font-bold text-community-900">
                        {laporan.keadaan === 'menunggu'
                            ? 'Laporan Anda terkirim dan menunggu moderasi.'
                            : laporan.keadaan === 'terbit'
                              ? 'Laporan Anda sudah terbit untuk pendaki lain.'
                              : 'Laporan Anda tidak diterbitkan.'}
                    </p>
                    {laporan.keadaan === 'ditolak' && laporan.alasanTolak ? <p className="mt-1 text-kecil text-primary">Alasan moderator: {laporan.alasanTolak}</p> : null}
                    {laporan.keadaan === 'menunggu' ? (
                        <p className="mt-1 text-kecil text-primary">Laporan komunitas tampil sebagai kabar pendaki, terpisah dari status resmi jalur.</p>
                    ) : null}
                    {formulir.ubah ? (
                        <Button variant="secondary" className="mt-3" onClick={() => setMengubah(true)}>
                            Ubah sebelum dimoderasi
                        </Button>
                    ) : null}
                </div>
            ) : (
                <form onSubmit={kirim} className="mt-3 space-y-5 rounded-kartu border-[1.5px] border-dashed border-community-500 bg-community-50 p-4 sm:p-5">
                    <p className="flex flex-wrap items-baseline justify-between gap-x-4 text-kecil text-primary">
                        Tanggal pendakian
                        <span data-angka className="font-bold">
                            {tanggalPendek(formulir.tanggal)}
                        </span>
                    </p>

                    <fieldset aria-describedby={galatKondisi ? 'laporan-kondisi-galat' : 'laporan-kondisi-petunjuk'}>
                        <legend className="text-isi font-bold text-primary">
                            Kondisi yang Anda temui <span className="font-normal text-secondary">(pilih satu atau lebih)</span>
                        </legend>
                        <div className="mt-2 flex flex-wrap gap-x-1 gap-y-1">
                            {formulir.tags.map((t) => (
                                <ChipPilihan key={t.value} dipilih={form.data.condition_tags.includes(String(t.value))} onUbah={(dipilih) => ubahKondisi(String(t.value), dipilih)}>
                                    {t.label}
                                </ChipPilihan>
                            ))}
                        </div>
                        <InputError id="laporan-kondisi-galat" messages={galatKondisi} className="mt-2" />
                    </fieldset>

                    {formulir.segmen.length > 0 ? (
                        <div>
                            <Label htmlFor="laporan-segmen">
                                Segmen <span className="font-normal text-secondary">(opsional)</span>
                            </Label>
                            <Select
                                id="laporan-segmen"
                                value={form.data.trail_segment_id}
                                onChange={(e) => form.setData('trail_segment_id', e.target.value)}
                                aria-describedby={form.errors.trail_segment_id ? 'laporan-segmen-galat' : undefined}
                                className="mt-1 w-full"
                            >
                                <option value="">Seluruh jalur</option>
                                {formulir.segmen.map((s) => (
                                    <option key={s.value} value={s.value}>
                                        {s.label}
                                    </option>
                                ))}
                            </Select>
                            <InputError id="laporan-segmen-galat" messages={form.errors.trail_segment_id} className="mt-2" />
                        </div>
                    ) : null}

                    <div>
                        <Label htmlFor="laporan-catatan">
                            Catatan <span className="font-normal text-secondary">(opsional)</span>
                        </Label>
                        <Textarea
                            id="laporan-catatan"
                            rows={3}
                            value={form.data.note}
                            onChange={(e) => form.setData('note', e.target.value)}
                            aria-describedby={form.errors.note ? 'laporan-catatan-galat' : undefined}
                            className="mt-1 w-full"
                        />
                        <InputError id="laporan-catatan-galat" messages={form.errors.note} className="mt-2" />
                    </div>

                    {mengubah ? null : (
                        <div>
                            <Label htmlFor="laporan-foto">
                                Foto <span className="font-normal text-secondary">(opsional, paling besar 4 MB)</span>
                            </Label>
                            <input
                                id="laporan-foto"
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                onChange={(e) => form.setData('photo', e.target.files?.[0] ?? null)}
                                aria-describedby={form.errors.photo ? 'laporan-foto-galat laporan-foto-gps' : 'laporan-foto-gps'}
                                className="mt-1 block w-full text-kecil text-secondary file:mr-3 file:min-h-11 file:rounded-control file:border file:border-control file:bg-surface file:px-4 file:py-2 file:text-kecil file:font-bold file:text-primary"
                            />
                            <p id="laporan-foto-gps" className="mt-1 text-meta text-secondary">
                                Lokasi GPS di dalam foto dihapus sebelum foto disimpan.
                            </p>
                            <p role="status" className="mt-1 text-meta text-secondary empty:!mt-0">
                                {form.progress ? 'Mengunggah foto.' : ''}
                            </p>
                            <InputError id="laporan-foto-galat" messages={form.errors.photo} className="mt-2" />
                        </div>
                    )}

                    <p className="flex gap-2 rounded-control bg-surface px-3 py-2.5 text-kecil text-primary">
                        <Icon name="lock-simple" size={16} className="mt-0.5 shrink-0 text-secondary" />
                        <span>{laporan.privasi}</span>
                    </p>

                    {/* Penolakan server (batas laju, laporan ganda) dibacakan lewat wilayah yang terpasang
                        sejak formulir tampil (DESIGN.md, umpan balik aksi). */}
                    <p role="alert" className={galat.form ? 'rounded-kartu border border-danger-300 bg-danger-100 p-4 text-sm font-bold text-danger-900' : 'empty:!mt-0'}>
                        {galat.form ?? ''}
                    </p>

                    <div>
                        <Button type="submit" processing={form.processing} disabled={!adaKondisi} aria-describedby="laporan-kondisi-petunjuk" className="w-full">
                            {mengubah ? 'Simpan perubahan laporan' : 'Kirim untuk dimoderasi'}
                        </Button>
                        <p id="laporan-kondisi-petunjuk" className="mt-2 text-meta text-secondary">
                            {adaKondisi ? 'Laporan dibaca moderator sebelum tampil untuk pendaki lain.' : 'Pilih minimal satu kondisi untuk mengirim.'}
                        </p>
                    </div>
                </form>
            )}
        </section>
    );
}
```

Buat `resources/js/components/community/ringkasan.ts`:

```ts
import type { Opsi } from './types';

/** Angka ringkasan pendakian dari App\Services\Community\HasilPendakian; null berarti belum ada. */
export interface AngkaRingkasan {
    jarak: string | null;
    naik: string | null;
    waktu: string | null;
    tertinggi: string | null;
    sumber: 'jejak' | 'jalur' | 'pendaki' | 'kosong';
    keterangan: string;
    awal: { jarak_km: number | null; naik_m: number | null; waktu_menit: number | null; tertinggi_m: number | null };
    ubah: string;
}

/** Tangga tanjakan riwayat sendiri (R-015): pendakian ini ditandai `ini`. */
export interface TanggaRingkasan {
    baris: Array<{ nama: string; m: number; teks: string; ini: boolean }>;
    kalimat: string;
}

/** Laporan kondisi dari ringkasan dan keadaannya. */
export interface LaporanRingkasan {
    keadaan: 'belum' | 'menunggu' | 'terbit' | 'ditolak';
    alasanTolak: string | null;
    privasi: string;
    formulir: {
        kirim: string;
        ubah: string | null;
        tripId: number;
        trailId: number;
        tanggal: string;
        tags: Opsi[];
        segmen: Opsi[];
        awal: { condition_tags: string[]; trail_segment_id: number | null; note: string | null } | null;
    };
}
```

`app/Http/Controllers/Community/ConditionReportController.php`:

1. Ganti:

```php
use App\Http\Requests\StoreConditionReportRequest;
use App\Models\HikingHistory;
```

dengan:

```php
use App\Http\Requests\StoreConditionReportRequest;
use App\Http\Requests\UpdateConditionReportRequest;
use App\Models\HikingHistory;
```

2. Ganti:

```php
        ]);

        if (! empty($data['trip_plan_id'])) {
            HikingHistory::where('trip_plan_id', $data['trip_plan_id'])
                ->where('user_id', $user->id)
                ->update(['trail_condition_report_id' => $laporan->id]);
        }

        $analytics->record(AnalyticsEvent::CONDITION_REPORT_SUBMITTED, $user, ['trail_id' => (int) $data['trail_id']]);
```

dengan:

```php
        ]);

        $tertaut = ! empty($data['trip_plan_id']) && HikingHistory::where('trip_plan_id', $data['trip_plan_id'])
            ->where('user_id', $user->id)
            ->update(['trail_condition_report_id' => $laporan->id]) > 0;

        $analytics->record(AnalyticsEvent::CONDITION_REPORT_SUBMITTED, $user, ['trail_id' => (int) $data['trail_id']]);
```

3. Ganti:

```php
        $analytics->record(AnalyticsEvent::CONDITION_REPORT_SUBMITTED, $user, ['trail_id' => (int) $data['trail_id']]);

        $respons = redirect()->route('history')
            ->with('status', 'Laporan terkirim dan menunggu moderasi sebelum tampil untuk pengguna lain.');
```

dengan:

```php
        $analytics->record(AnalyticsEvent::CONDITION_REPORT_SUBMITTED, $user, ['trail_id' => (int) $data['trail_id']]);

        // Laporan dari ringkasan pendakian kembali ke ringkasannya (spec v3 bagian 6.13), tempat
        // keadaannya dan "Ubah sebelum dimoderasi" tampil.
        $respons = ($tertaut ? redirect()->route('history.summary', (int) $data['trip_plan_id']) : redirect()->route('history'))
            ->with('status', 'Laporan terkirim dan menunggu moderasi sebelum tampil untuk pengguna lain.');
```

4. Ganti:

```php
    }
}
```

dengan:

```php
    }

    /**
     * Mengubah laporan sebelum dimoderasi (spec v3 bagian 6.13): hanya pemiliknya dan hanya
     * selama menunggu moderasi (UpdateConditionReportRequest). Jalur, tanggal, dan foto tetap;
     * yang dapat diubah kondisi, segmen, dan catatan. Laporannya tetap menunggu moderasi.
     */
    public function update(UpdateConditionReportRequest $request, TrailConditionReport $report): RedirectResponse
    {
        $data = $request->validated();

        $report->update([
            'condition_tags' => $data['condition_tags'],
            'trail_segment_id' => $data['trail_segment_id'] ?? null,
            'note' => $data['note'] ?? null,
        ]);

        $trip = HikingHistory::where('trail_condition_report_id', $report->id)->where('user_id', $request->user()->id)->value('trip_plan_id');

        return ($trip !== null ? redirect()->route('history.summary', $trip) : redirect()->route('history'))
            ->with('status', 'Laporan diperbarui dan tetap menunggu moderasi.');
    }
}
```

Tulis ulang `app/Http/Controllers/Community/HikeSummaryController.php` utuh:

```php
<?php

namespace App\Http\Controllers\Community;

use App\Enums\TripStatus;
use App\Http\Controllers\Controller;
use App\Models\TripPlan;
use App\Services\Community\HasilPendakian;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Ringkasan satu pendakian (spec v3 bagian 6.13). Pemilik URL bukan pemilik trip (PRD §77):
 * policy yang sama dengan trip.
 */
class HikeSummaryController extends Controller
{
    public function __invoke(Request $request, TripPlan $trip, HasilPendakian $hasil): Response
    {
        $this->authorize('view', $trip);

        // Trip yang belum selesai dan tidak dibatalkan belum punya hasil untuk diringkas, dan
        // ringkasan kosong akan terbaca seolah pendakiannya sudah usai. Trip yang dibatalkan
        // mendapat varian pendek dengan alasannya (DK-50).
        abort_unless($trip->history()->exists() || $trip->status === TripStatus::CANCELLED, 404);

        $props = $hasil->untuk($trip, $request->user());

        return Inertia::render('Community/HikeSummary', [
            'title' => 'Hasil pendakian '.$props['jalur']['nama'],
            ...$props,
            // Laporan dari ringkasan kembali ke sini; foto yang gagal dibersihkan disebut di sini.
            'peringatanFoto' => session('foto-tidak-disertakan'),
        ]);
    }
}
```

`app/Http/Controllers/Trips/TripLifecycleController.php`:

1. Ganti:

```php
        );

        return redirect()->route('reports.create', ['trail' => $trip->trail_id, 'trip' => $trip->id]);
    }
```

dengan:

```php
        );

        // Ringkasan memuat formulir laporan kondisi (spec v3 bagian 5.3 dan 6.13).
        return redirect()->route('history.summary', $trip);
    }
```

`app/Models/HikingHistory.php`:

1. Ganti:

```php
    'completion_state', 'preparation_completion_percent', 'personal_notes', 'completed_at',
])]
```

dengan:

```php
    'completion_state', 'preparation_completion_percent', 'personal_notes', 'completed_at',
    'recorded_distance_m', 'recorded_gain_m', 'recorded_moving_minutes', 'recorded_highest_m', 'numbers_edited_at',
])]
```

2. Ganti:

```php
            'completed_at' => 'datetime',
        ];
```

dengan:

```php
            'completed_at' => 'datetime',
            'recorded_distance_m' => 'integer',
            'recorded_gain_m' => 'integer',
            'recorded_moving_minutes' => 'integer',
            'recorded_highest_m' => 'integer',
            'numbers_edited_at' => 'datetime',
        ];
```

Tulis ulang `app/Services/Community/HasilPendakian.php` utuh:

```php
<?php

namespace App\Services\Community;

use App\Enums\CompletionState;
use App\Enums\ConditionTag;
use App\Enums\ModerationStatus;
use App\Models\Checkpoint;
use App\Models\HikeTrackPoint;
use App\Models\HikingHistory;
use App\Models\HikingSession;
use App\Models\TrailSegment;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\Trails\CheckpointJourney;
use App\Services\Trails\ProfilElevasiJalur;
use App\Services\Trails\SkalaProfil;
use App\Support\Durasi;
use App\Support\GpxTrack;
use App\Support\Timezone;
use Illuminate\Support\Collection;

/**
 * Ringkasan satu pendakian (spec v3 bagian 6.13, prototype 12; rencana induk W6 butir 9).
 *
 * Dua varian (DK-50): selesai, dengan angka, banding diri sendiri, pencapaian, dan formulir
 * laporan; dan dibatalkan, pendek, dengan alasannya. Angka berasal dari rekaman jejak bila ada,
 * dari data jalur bila tidak, atau dari pendaki sendiri lewat "Ubah angka"; sumbernya selalu
 * disebut. Pembandingnya hanya riwayat pendaki ini (R-015), dan riwayat tidak mengubah tingkat
 * pengalaman (BR-11). Yang tidak diketahui ditulis null, bukan nol (R-007), dan tidak ada
 * persen (R-005).
 */
final class HasilPendakian
{
    /**
     * Tonggak "tanjakan pertama di atas N m": 500 m sebagai tonggak pertama, lalu kelipatan
     * 1.000 m, sesuai contoh prototype 12 (700 m lalu 1.660 m menjadi "di atas 1.000 m").
     */
    private const TONGGAK_TANJAKAN = [500, 1000, 2000, 3000];

    /** Anak tangga: pendakian ini dan tiga jalur tertinggi sebelumnya, sama dengan Beranda. */
    private const BARIS_TANGGA = 4;

    /**
     * @return array<string, mixed>
     */
    public function untuk(TripPlan $trip, User $pengguna): array
    {
        $trip->loadMissing('trail.mountain', 'trail.checkpoints', 'trail.segments', 'hikingSession', 'history.conditionReport.moderationActions');

        $riwayat = $trip->history;
        $jalur = $trip->trail;
        $sesi = $trip->hikingSession;
        $titik = $this->titikJejak($sesi);

        $dasar = [
            'jalur' => [
                'nama' => $jalur->name,
                'slug' => $jalur->slug,
                'wilayah' => implode(' · ', array_filter([$jalur->mountain?->name, $jalur->mountain?->province])),
            ],
            'varian' => $riwayat === null ? 'batal' : 'selesai',
            'tanggal' => ($riwayat?->completed_at ?? $trip->planned_date)->translatedFormat('l, j F Y'),
            'hasil' => $this->hasil($trip, $riwayat),
            'posTerjauh' => $this->posTerjauh($trip)?->name,
            'jejak' => $titik->count() < 2 ? null : [
                'geometri' => ['type' => 'LineString', 'coordinates' => $titik->map(fn (HikeTrackPoint $t) => [(float) $t->longitude, (float) $t->latitude])->all()],
                'sesi' => $sesi->id,
            ],
            'garisJalur' => $jalur->readGeoJson('geometry'),
            'penandaPos' => $jalur->checkpoints
                ->filter(fn (Checkpoint $p) => $p->latitude !== null && $p->longitude !== null)
                ->sortBy('sequence')
                ->map(fn (Checkpoint $p) => ['lng' => (float) $p->longitude, 'lat' => (float) $p->latitude, 'label' => $p->sequence.'. '.$p->name])
                ->values()
                ->all(),
            'profil' => ProfilElevasiJalur::untuk($jalur->elevation_profile, $jalur->checkpoints, []),
            'pos' => CheckpointJourney::daftar($jalur->checkpoints, [], []),
            'catatan' => $riwayat?->personal_notes,
        ];

        if ($riwayat === null) {
            return [...$dasar, 'angka' => null, 'tangga' => null, 'banding' => null, 'pencapaian' => [], 'tingkat' => null, 'laporan' => null];
        }

        $angka = $this->angka($trip, $riwayat, $titik);
        $tuntas = $riwayat->completion_state === CompletionState::COMPLETED;
        $tangga = $tuntas ? $this->tangga($pengguna, $trip, $riwayat) : null;

        return [
            ...$dasar,
            'angka' => $angka,
            'tangga' => $tangga['tangga'] ?? null,
            'banding' => match (true) {
                ! $tuntas => 'Pendakian yang berbalik sebelum tujuan tidak dihitung sebagai tanjakan yang dituntaskan. Keputusan berbalik tetap tercatat apa adanya.',
                $tangga === null => 'Tanjakan jalur ini belum tercatat, jadi belum dapat dibandingkan dengan riwayat Anda.',
                default => null,
            },
            'pencapaian' => $tangga['pencapaian'] ?? [],
            'tingkat' => [
                'kalimat' => ($tingkat = $pengguna->profile?->experience_level) !== null
                    ? 'Tingkat pengalaman Anda tetap '.$tingkat->label().' sampai Anda mengubahnya sendiri di Profil.'
                    : 'Riwayat pendakian tidak mengubah tingkat pengalaman Anda; Anda sendiri yang mengisinya di Profil.',
                'url' => route('profile'),
            ],
            'laporan' => $this->laporan($trip, $riwayat, $pengguna),
        ];
    }

    private function hasil(TripPlan $trip, ?HikingHistory $riwayat): string
    {
        $dasar = $riwayat?->completion_state->label() ?? 'Dibatalkan';
        $alasan = $trip->cancellation_reason?->label();

        return $alasan !== null && ($riwayat === null || $riwayat->completion_state === CompletionState::ABANDONED) ? $dasar.' · '.$alasan : $dasar;
    }

    /**
     * Angka pendakian dan sumbernya (ruling W6-R13): angka pendaki bila ia mengubahnya, rekaman
     * jejak bila ada, selain itu data jalur untuk pendakian yang tuntas, dengan jarak pulang-pergi
     * lewat jalur yang sama seperti prototype 12 (9,7 km menjadi 19,4 km). Pendakian yang
     * berbalik tanpa jejak tidak punya jarak, tanjakan, atau titik tertinggi yang diketahui, dan
     * angka jalur penuh akan melebih-lebihkannya (R-007). Waktu bergerak dari sesi mode lapangan
     * (dikurangi jeda).
     *
     * @param  Collection<int, HikeTrackPoint>  $titik
     * @return array<string, mixed>
     */
    private function angka(TripPlan $trip, HikingHistory $riwayat, Collection $titik): array
    {
        $jalur = $trip->trail;
        $menitSesi = self::menitBergerak($trip->hikingSession);

        if ($riwayat->numbers_edited_at !== null) {
            [$sumber, $jarakM, $naik, $menit, $tertinggi] = ['pendaki', $riwayat->recorded_distance_m, $riwayat->recorded_gain_m, $riwayat->recorded_moving_minutes, $riwayat->recorded_highest_m];
            $keterangan = 'Angka ini Anda ubah sendiri.';
        } elseif ($titik->count() >= 2) {
            $ketinggian = $titik->pluck('elevation_m')->map(fn ($m) => $m === null ? null : (float) $m)->all();
            $koordinat = $titik->map(fn (HikeTrackPoint $t) => [(float) $t->longitude, (float) $t->latitude])->all();
            $tinggi = array_filter($ketinggian, fn ($m) => $m !== null);
            [$sumber, $jarakM, $naik, $menit, $tertinggi] = ['jejak', (int) round(GpxTrack::lengthKm($koordinat) * 1000), GpxTrack::gainLoss($ketinggian)['gain'] ?? null, $menitSesi, $tinggi === [] ? null : (int) round(max($tinggi))];
            $keterangan = 'Dari rekaman jejak Anda. Ubah bila berbeda.';
        } elseif ($riwayat->completion_state !== CompletionState::COMPLETED) {
            [$sumber, $jarakM, $naik, $menit, $tertinggi] = ['kosong', null, null, $menitSesi, null];
            $keterangan = 'Tanpa rekaman jejak, jarak dan tanjakan pendakian yang berbalik tidak diketahui. Isi sendiri bila Anda mencatatnya.';
        } else {
            $profil = SkalaProfil::titik($jalur->elevation_profile);
            $tertinggiPos = $jalur->checkpoints->max('elevation_m');
            [$sumber, $jarakM, $naik, $menit, $tertinggi] = [
                'jalur',
                $jalur->distance_km === null ? null : (int) round((float) $jalur->distance_km * 2000),
                $jalur->elevation_gain_m,
                $menitSesi,
                $profil !== null ? (int) round(max(array_column($profil, 1))) : ($tertinggiPos === null ? null : (int) $tertinggiPos),
            ];
            $keterangan = 'Tanpa rekaman jejak, angka ini diisi dari data jalur, dengan jarak pulang-pergi lewat jalur yang sama. Ubah bila berbeda.';
        }

        $angka = fn (?int $m) => $m === null ? null : number_format($m, 0, ',', '.');

        return [
            'jarak' => $jarakM === null ? null : number_format($jarakM / 1000, 1, ',', '.').' km',
            'naik' => $naik === null ? null : '↑'.$angka($naik).' m',
            'waktu' => $menit === null ? null : Durasi::panjang($menit),
            'tertinggi' => $tertinggi === null ? null : $angka($tertinggi).' m',
            'sumber' => $sumber,
            'keterangan' => $keterangan,
            'awal' => [
                'jarak_km' => $jarakM === null ? null : round($jarakM / 1000, 1),
                'naik_m' => $naik,
                'waktu_menit' => $menit,
                'tertinggi_m' => $tertinggi,
            ],
            'ubah' => route('history.numbers.update', $trip),
        ];
    }

    /**
     * Tangga tanjakan dari riwayat pendaki ini saja (R-015), dengan ukuran yang sama dengan tangga
     * Beranda (App\Services\Dashboard\TanggaTanjakan): tanjakan jalur, satu anak tangga per
     * jalur, hanya pendakian yang tuntas. Isinya pendakian ini dan paling banyak tiga jalur
     * tertinggi yang dituntaskan sebelumnya, naik dari kiri; satu kalimat selisih; pencapaian
     * "tanjakan pertama di atas N m" untuk tonggak tertinggi yang baru dilewati. Null bila
     * tanjakan jalur ini tidak diketahui (R-007).
     *
     * @return array{tangga: array{baris: array<int, array{nama: string, m: int, teks: string, ini: bool}>, kalimat: string}, pencapaian: array<int, string>}|null
     */
    private function tangga(User $pengguna, TripPlan $trip, HikingHistory $riwayat): ?array
    {
        $meter = $trip->trail->elevation_gain_m;

        if ($meter === null) {
            return null;
        }

        $sebelumnya = HikingHistory::query()
            ->where('hiking_history.user_id', $pengguna->id)
            ->where('hiking_history.completion_state', CompletionState::COMPLETED->value)
            ->where('hiking_history.completed_at', '<', $riwayat->completed_at)
            ->join('trails', 'trails.id', '=', 'hiking_history.trail_id')
            ->whereNotNull('trails.elevation_gain_m')
            ->groupBy('trails.id', 'trails.name', 'trails.elevation_gain_m')
            ->orderByDesc('trails.elevation_gain_m')
            ->orderBy('trails.id')
            ->limit(self::BARIS_TANGGA - 1)
            ->toBase()
            ->get(['trails.name', 'trails.elevation_gain_m'])
            ->map(fn (object $t) => ['nama' => (string) $t->name, 'm' => (int) $t->elevation_gain_m, 'ini' => false]);

        $tertinggi = $sebelumnya->first();
        $angka = fn (int $m) => number_format($m, 0, ',', '.');

        $kalimat = match (true) {
            $tertinggi === null => 'Ini pendakian tuntas pertama yang tercatat di riwayat Anda.',
            $meter > $tertinggi['m'] => '+'.$angka($meter - $tertinggi['m']).' m dari tanjakan tertinggi Anda sebelumnya ('.$tertinggi['nama'].', '.$angka($tertinggi['m']).' m).',
            $meter === $tertinggi['m'] => 'Setara dengan tanjakan tertinggi Anda sebelumnya ('.$tertinggi['nama'].', '.$angka($tertinggi['m']).' m).',
            default => 'Tanjakan tertinggi Anda tetap '.$tertinggi['nama'].' ('.$angka($tertinggi['m']).' m).',
        };

        $tonggak = collect(self::TONGGAK_TANJAKAN)->filter(fn (int $m) => $meter >= $m && ($tertinggi['m'] ?? 0) < $m)->last();

        return [
            'tangga' => [
                'baris' => $sebelumnya
                    ->push(['nama' => $trip->trail->name, 'm' => $meter, 'ini' => true])
                    ->sortBy('m')
                    ->map(fn (array $h) => ['nama' => $h['nama'], 'm' => $h['m'], 'teks' => $angka($h['m']).' m', 'ini' => $h['ini']])
                    ->values()
                    ->all(),
                'kalimat' => $kalimat.' Tidak ada pembanding dengan pendaki lain, dan tidak ada catatan kecepatan.',
            ],
            'pencapaian' => $tonggak === null ? [] : ['Tanjakan pertama di atas '.$angka($tonggak).' m'],
        ];
    }

    /**
     * Formulir laporan kondisi di ringkasan (spec v3 bagian 6.13) dan keadaannya. Laporan yang
     * belum dimoderasi dapat diubah pemiliknya; sesudahnya tidak.
     *
     * @return array<string, mixed>
     */
    private function laporan(TripPlan $trip, HikingHistory $riwayat, User $pengguna): array
    {
        $laporan = $riwayat->conditionReport;
        $menunggu = $laporan !== null && in_array($laporan->moderation_status, [ModerationStatus::PENDING, ModerationStatus::FLAGGED], true);

        return [
            'keadaan' => match (true) {
                $laporan === null => 'belum',
                $menunggu => 'menunggu',
                $laporan->moderation_status === ModerationStatus::APPROVED => 'terbit',
                default => 'ditolak',
            },
            'alasanTolak' => $laporan?->rejectionReason(),
            'privasi' => 'Yang terbit: tanggal, kondisi, segmen, catatan, foto, dan nama tampilan Anda. Tidak terbit: jejak, titik awal, dan titik akhir Anda.',
            'formulir' => [
                'kirim' => route('reports.store'),
                'ubah' => $menunggu && $laporan->user_id === $pengguna->id && $laporan->moderation_status === ModerationStatus::PENDING ? route('reports.update', $laporan) : null,
                'tripId' => $trip->id,
                'trailId' => $trip->trail_id,
                'tanggal' => $riwayat->completed_at->copy()->setTimezone(Timezone::forTrail($trip->trail))->toDateString(),
                'tags' => array_map(fn (ConditionTag $t) => ['value' => $t->value, 'label' => $t->label()], ConditionTag::cases()),
                'segmen' => $trip->trail->segments->sortBy('sequence')->map(fn (TrailSegment $s) => ['value' => $s->id, 'label' => $s->sequence.'. '.$s->name])->values()->all(),
                'awal' => $laporan === null ? null : ['condition_tags' => $laporan->condition_tags, 'trail_segment_id' => $laporan->trail_segment_id, 'note' => $laporan->note],
            ],
        ];
    }

    /**
     * Lama bergerak menurut sesi mode lapangan (jeda tidak dihitung, ruling W6-R7), untuk daftar
     * riwayat. Null ketika mode lapangan tidak pernah dibuka atau tidak pernah diakhiri, dan itu
     * keadaan yang umum.
     */
    public static function durasiSesi(?HikingSession $sesi): ?string
    {
        $menit = self::menitBergerak($sesi);

        return $menit === null ? null : Durasi::panjang($menit);
    }

    private static function menitBergerak(?HikingSession $sesi): ?int
    {
        if ($sesi?->started_at === null || $sesi->ended_at === null) {
            return null;
        }

        return intdiv((int) $sesi->detikBergerak(), 60);
    }

    /**
     * Pos terjauh yang dicapai menurut sesi mode lapangan; null bila tidak tercatat.
     */
    private function posTerjauh(TripPlan $trip): ?Checkpoint
    {
        $urutan = $trip->hikingSession?->reached_checkpoint_sequence;

        return $urutan === null ? null : $trip->trail->checkpoints->firstWhere('sequence', $urutan);
    }

    /**
     * Titik jejak yang direkam pendaki, urut waktu rekam.
     *
     * @return Collection<int, HikeTrackPoint>
     */
    private function titikJejak(?HikingSession $sesi): Collection
    {
        if ($sesi === null) {
            return collect();
        }

        return HikeTrackPoint::query()
            ->where('hiking_session_id', $sesi->id)
            ->orderBy('recorded_at')
            ->get(['latitude', 'longitude', 'elevation_m']);
    }
}
```

Tulis ulang `resources/js/pages/Community/HikeSummary.tsx` utuh:

```tsx
import { router, usePage } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import { AngkaPendakian } from '@/components/community/AngkaPendakian';
import { BandingDiriSendiri } from '@/components/community/BandingDiriSendiri';
import { LaporanPendakian } from '@/components/community/LaporanPendakian';
import { PetaGaris } from '@/components/community/PetaGaris';
import type { AngkaRingkasan, LaporanRingkasan, TanggaRingkasan } from '@/components/community/ringkasan';
import type { GarisGeoJson } from '@/components/community/types';
import { ProfilElevasi } from '@/components/trails/ProfilElevasi';
import { DaftarPos } from '@/components/trails/rinci/DaftarPos';
import type { PosJalur, ProfilElevasiData } from '@/components/trails/rinci/types';
import type { Penanda } from '@/components/trails/types';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { Lencana } from '@/components/ui/Lencana';
import { PageHeader } from '@/components/ui/PageHeader';
import { AppLayout } from '@/layouts/AppLayout';
import { progress } from '@/routes';
import { destroy } from '@/routes/hike/track';
import { show as trailShow } from '@/routes/trails';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    jalur: { nama: string; slug: string; wilayah: string };
    varian: 'selesai' | 'batal';
    tanggal: string;
    hasil: string;
    posTerjauh: string | null;
    angka: AngkaRingkasan | null;
    tangga: TanggaRingkasan | null;
    banding: string | null;
    pencapaian: string[];
    tingkat: { kalimat: string; url: string } | null;
    laporan: LaporanRingkasan | null;
    jejak: { geometri: GarisGeoJson; sesi: number } | null;
    garisJalur: GarisGeoJson | null;
    penandaPos: Penanda[];
    profil: ProfilElevasiData | null;
    pos: PosJalur[];
    catatan: string | null;
    peringatanFoto: string | null;
}

/**
 * Ringkasan satu pendakian (spec v3 bagian 6.13, prototype 12). Nadanya mencatat, bukan
 * menilai: pendakian yang dibatalkan karena cuaca adalah keputusan yang benar dan tidak boleh
 * terbaca seperti kegagalan. Dua varian (DK-50): selesai, dengan angka, banding diri sendiri,
 * dan laporan; dibatalkan, pendek, dengan alasannya.
 *
 * Jejak pendaki dan garis jalur resmi tidak pernah tertukar. Jejak yang mirip jalur resmi
 * membuat pendaki mengira aplikasi ini memverifikasi bahwa ia berjalan di jalur yang benar,
 * dan aplikasi ini tidak melakukan itu.
 */
export default function HikeSummary() {
    const { flash, jalur, varian, tanggal, hasil, posTerjauh, angka, tangga, banding, pencapaian, tingkat, laporan, jejak, garisJalur, penandaPos, profil, pos, catatan, peringatanFoto } =
        usePage<Props>().props;
    const [menghapus, setMenghapus] = useState(false);

    // Hak menghapus berada di tempat jejaknya terlihat; rute dan policy-nya milik HikeTrackController.
    const hapusJejak = () => {
        if (!jejak || !window.confirm('Hapus jejak pendakian ini? Pendakiannya sendiri tetap tersimpan.')) {
            return;
        }

        router.delete(destroy.url(jejak.sesi), {
            preserveScroll: true,
            onStart: () => setMenghapus(true),
            onFinish: () => setMenghapus(false),
        });
    };

    return (
        <div className="py-6 sm:py-8">
            <div className="mx-auto max-w-3xl space-y-8 px-4 sm:px-6 lg:px-8">
                <PageHeader induk={{ href: progress.url(), label: 'Progres' }} title={jalur.nama} description={jalur.wilayah || undefined}>
                    <p className="mt-1 text-kecil text-secondary">{tanggal}</p>
                    <div className="mt-2 flex flex-wrap items-center gap-2">
                        <Lencana>{hasil}</Lencana>
                        {posTerjauh ? <span className="text-kecil text-secondary">Pos terjauh: {posTerjauh}</span> : null}
                    </div>
                    {/* Kabar dan peringatan dibacakan lewat wilayah yang terpasang sejak halaman dimuat
                        (DESIGN.md, umpan balik aksi); hanya isi dan bingkainya yang muncul. */}
                    <p role="status" className={flash.status ? 'mt-4 rounded-kartu border border-brand-300 bg-brand-100 p-4 text-sm text-brand-900' : undefined}>
                        {flash.status ?? ''}
                    </p>
                    <p role="alert" className={peringatanFoto ? 'mt-4 rounded-kartu border border-warn-300 bg-warn-100 p-4 text-sm font-bold text-warn-900' : undefined}>
                        {peringatanFoto ?? ''}
                    </p>
                </PageHeader>

                {varian === 'batal' ? (
                    <p className="max-w-prose text-isi text-primary">
                        Trip ini dibatalkan sebelum pendakian dimulai. Keputusan itu tercatat apa adanya di riwayat Anda.
                    </p>
                ) : null}

                {jejak ? (
                    <Card title="Jejak yang Anda tempuh" subtitle="Rekaman dari perangkat Anda, bukan penilaian apakah Anda berjalan di jalur yang benar.">
                        <PetaGaris id="peta-jejak" geometry={jejak.geometri} markers={penandaPos} label={`Jejak pendakian Anda di ${jalur.nama}`} height="h-80" />
                        <Button variant="secondary" size="sm" className="mt-4" onClick={hapusJejak} processing={menghapus}>
                            Hapus jejak ini
                        </Button>
                    </Card>
                ) : null}

                {angka ? <AngkaPendakian angka={angka} /> : null}

                {varian === 'selesai' && tingkat ? <BandingDiriSendiri tangga={tangga} banding={banding} pencapaian={pencapaian} tingkat={tingkat} /> : null}

                {laporan ? <LaporanPendakian laporan={laporan} /> : null}

                {catatan ? (
                    <section aria-labelledby="judul-catatan">
                        <h2 id="judul-catatan" className="text-bagian text-primary">
                            Catatan Anda
                        </h2>
                        <p className="mt-1 max-w-prose text-isi text-secondary">{catatan}</p>
                    </section>
                ) : null}

                {varian === 'selesai' ? (
                    <>
                        {garisJalur ? (
                            <Card title="Jalur yang ditempuh" subtitle="Garis ini adalah jalur resmi, bukan rekaman GPS perjalanan Anda.">
                                <PetaGaris id="peta-hasil" geometry={garisJalur} markers={penandaPos} label={`Peta jalur ${jalur.nama}`} />
                            </Card>
                        ) : penandaPos.length > 0 ? (
                            <Card title="Peta pos jalur ini">
                                <PetaGaris id="peta-hasil" markers={penandaPos} label={`Peta pos jalur ${jalur.nama}`} />
                            </Card>
                        ) : null}

                        {profil ? (
                            <Card title="Profil elevasi">
                                <ProfilElevasi profil={profil} />
                            </Card>
                        ) : null}

                        {pos.length > 0 ? (
                            <Card title="Daftar pos">
                                <DaftarPos pos={pos} />
                            </Card>
                        ) : null}
                    </>
                ) : null}

                <div className="flex flex-col gap-3 sm:flex-row">
                    <Button href={progress.url()} className="w-full sm:w-auto">
                        Lihat progres
                    </Button>
                    <Button variant="secondary" href={trailShow.url({ trail: jalur.slug })} className="w-full sm:w-auto">
                        Buka halaman jalur
                    </Button>
                </div>
            </div>
        </div>
    );
}

HikeSummary.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
```

`routes/web.php`:

1. Ganti:

```php
use App\Http\Controllers\Community\ConditionReportController;
use App\Http\Controllers\Community\HikerProgressController;
```

dengan:

```php
use App\Http\Controllers\Community\ConditionReportController;
use App\Http\Controllers\Community\HikeNumbersController;
use App\Http\Controllers\Community\HikerProgressController;
```

2. Ganti:

```php
    Route::post('reports', [ConditionReportController::class, 'store'])->name('reports.store');
    Route::get('reports/{report}/photo', ReportPhotoController::class)->name('reports.photo');
```

dengan:

```php
    Route::post('reports', [ConditionReportController::class, 'store'])->name('reports.store');
    // Ubah sebelum dimoderasi (W6, spec v3 bagian 6.13): pemilik, selama menunggu moderasi.
    Route::put('reports/{report}', [ConditionReportController::class, 'update'])->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,ubah-laporan')->name('reports.update');
    Route::get('reports/{report}/photo', ReportPhotoController::class)->name('reports.photo');
```

3. Ganti:

```php
    Route::get('history/{trip}', HikeSummaryController::class)->name('history.summary');

    // Terbuka untuk pemegang kredensial yang berlaku; controllernya menolak yang lain.
```

dengan:

```php
    Route::get('history/{trip}', HikeSummaryController::class)->name('history.summary');
    // "Ubah angka" ringkasan pendakian (W6, spec v3 bagian 6.13).
    Route::put('history/{trip}/angka', HikeNumbersController::class)->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,angka-pendakian')->name('history.numbers.update');

    // Terbuka untuk pemegang kredensial yang berlaku; controllernya menolak yang lain.
```

`tests/Pratinjau/HalamanPratinjauTest.php`:

1. Ganti:

```php
namespace Tests\Pratinjau;

use App\Enums\CompletionState;
```

dengan:

```php
namespace Tests\Pratinjau;

use App\Enums\CancellationReason;
use App\Enums\CompletionState;
```

2. Ganti:

```php
        $this->tulisPratinjau('mode-lapangan', $this->get(self::ASAL.'/trips/'.$trip->id.'/hike'), 'Trips/Hike');
    }
```

dengan:

```php
        $this->tulisPratinjau('mode-lapangan', $this->get(self::ASAL.'/trips/'.$trip->id.'/hike'), 'Trips/Hike');
    }

    /**
     * Ringkasan pendakian (W6, prototype 12): Budi menuntaskan Gede via Cibodas dua hari lalu
     * dengan mode lapangan 04.00 sampai 15.05 dan jeda 30 menit, tanpa rekam jejak. Riwayat
     * Andong, Batur, dan Prau dari DemoSeeder menjadi anak tangga sebelumnya.
     */
    public function test_ringkasan(): void
    {
        $budi = $this->sebagaiBudi();
        $trip = TripPlan::where('user_id', $budi->id)->where('status', TripStatus::PLANNED->value)->sole();
        $mulai = now()->subDays(2)->setTime(4, 0);
        $trip->update(['planned_date' => $mulai->toDateString(), 'status' => TripStatus::COMPLETED->value, 'completed_at' => $mulai->copy()->setTime(15, 5)]);

        HikingSession::create([
            'trip_plan_id' => $trip->id,
            'user_id' => $budi->id,
            'reached_checkpoint_sequence' => Checkpoint::where('trail_id', $trip->trail_id)->max('sequence'),
            'status' => HikingSessionStatus::COMPLETED->value,
            'started_at' => $mulai,
            'ended_at' => $mulai->copy()->setTime(15, 5),
            'paused_seconds' => 1800,
        ]);

        HikingHistory::create([
            'user_id' => $budi->id,
            'trip_plan_id' => $trip->id,
            'trail_id' => $trip->trail_id,
            'trip_type' => $trip->trip_type->value,
            'completion_state' => CompletionState::COMPLETED->value,
            'completed_at' => $mulai->copy()->setTime(15, 5),
        ]);

        $this->tulisPratinjau('ringkasan', $this->get(self::ASAL.route('history.summary', $trip, false)), 'Community/HikeSummary');
    }

    /**
     * Varian dibatalkan (DK-50): trip Pangrango Budi dari DemoSeeder, dibatalkan karena cuaca.
     */
    public function test_ringkasan_batal(): void
    {
        $budi = $this->sebagaiBudi();
        $trip = TripPlan::where('user_id', $budi->id)->where('status', TripStatus::CANCELLED->value)->sole();
        $trip->update(['cancellation_reason' => CancellationReason::WEATHER->value]);

        $this->tulisPratinjau('ringkasan-batal', $this->get(self::ASAL.route('history.summary', $trip, false)), 'Community/HikeSummary');
    }
```

- [ ] **Step 4: Build dan tipe.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `npx tsc --noEmit`
Expected: `✓ built`; tsc tanpa keluaran galat.

- [ ] **Step 5: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Services/Community/HasilPendakian.php app/Http/Controllers/Community/HikeSummaryController.php app/Http/Controllers/Community/HikeNumbersController.php app/Http/Controllers/Community/ConditionReportController.php app/Http/Controllers/Trips/TripLifecycleController.php app/Http/Requests/Community/UpdateHikeNumbersRequest.php app/Http/Requests/UpdateConditionReportRequest.php app/Models/HikingHistory.php routes/web.php database/migrations/2026_10_07_000400_add_recorded_values_to_hiking_history_table.php tests/Feature/HikeSummaryTest.php tests/Feature/HikeSummaryReportTest.php tests/Feature/TripLifecycleTest.php tests/Pratinjau/HalamanPratinjauTest.php`, lalu `php artisan test --compact --filter='HikeSummary|TripLifecycle|HikeTrackOnSummary|ConditionReport|HikingHistory'`
Expected: PASS. Pada simulasi rencana: 55 test lulus.

- [ ] **Step 6: Suite, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 2.018 test, 2.016 lulus, 2 dilewati (sebelum tugas: 2.004).

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `php artisan test --compact --testsuite=Pratinjau`
Expected: PASS; halaman baru `ringkasan` dan `ringkasan-batal`. Pada simulasi rencana: 25 test lulus.

- [ ] **Step 7: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 7: Kartu darurat, versi cetak, dan pesan rencana

Spec bagian 10.2 (kartu darurat), 10.8 (sistem kedua, versi cetak, pesan rencana dengan eskalasi) dan bagian 15 Q14
(fitur keselamatan, kolom nomor registrasi dan jam kembali); induk W6 butir 8 (`EmergencyCardTest`,
`TripPlanMessageTest`); ruling W6-R4. Ketiganya di balik `hiking.field_safety.enabled` (bawaan mati) dan middleware
`fitur` yang menjawab 404 sebelum otorisasi dan validasi. Kartu darurat satu ketukan dari mode lapangan: 112 dan
Basarnas 115 sebagai tautan telepon 66 px, kontak pengelola hanya bila badan berwenang terverifikasi dengan kontak
tercatat, kalimat tetap di tempat, dan blok lokasi yang dihitung di perangkat (koordinat, pos terdekat, ketinggian dari
profil, waktu, akurasi). Pesan rencana teks biasa dengan eskalasi satu jam sesudah jam kembali di zona gunung dan
pernyataan bahwa Memount tidak memantau perjalanan. Versi cetak: rencana, kartu darurat, sketsa rute tanpa skala,
tabel pos (bergulir di wadahnya sendiri di layar 320 px, bukan halamannya), aturan resmi bertanggal cek. Test Spatial
untuk kolom aditif W6 ikut di tugas ini.

**Files:**
- Create: `app/Http/Middleware/EnsureFeatureEnabled.php`, `app/Services/Trips/KartuDarurat.php`, `app/Services/Trips/PesanRencana.php`, `app/Services/Trips/CetakTrip.php`, `app/Http/Controllers/Trips/TripSafetyController.php`, `app/Http/Requests/Trips/UpdateTripReturnRequest.php`, `database/migrations/2026_10_07_000500_add_return_and_registration_to_trip_plans_table.php`, `resources/js/components/mendaki/KartuDarurat.tsx`, `resources/js/components/trips/KeselamatanTrip.tsx`, `resources/js/pages/Trips/Print.tsx`
- Modify: `.env.example`, `bootstrap/app.php`, `config/hiking.php`, `routes/web.php`, `app/Models/TripPlan.php`, `app/Services/Trips/HalamanMendaki.php`, `app/Http/Controllers/Trips/TripController.php`, `resources/js/components/mendaki/hitung.ts`, `resources/js/components/mendaki/types.ts`, `resources/js/components/mendaki/ModeLapangan.tsx`, `resources/js/pages/Trips/Show.tsx`
- Test: `tests/Feature/EmergencyCardTest.php` (baru), `tests/Feature/TripPlanMessageTest.php` (baru), `tests/Spatial/FieldModeColumnsSchemaTest.php` (baru, PostgreSQL), `tests/Feature/FieldModeFiguresTest.php`, `tests/Pratinjau/HalamanPratinjauTest.php`

**Interfaces:**
- Consumes: `Mountain::authorities()`, `AuthorityType::mayDeclareTrailStatus()`, `Authority::displayName()`; `PermitService::requirementFor()`, `PermitRequirement::summary()`; `CheckpointPaceService::forTrail()`; `CheckpointJourney::daftar()`; `TripPlan::ringkasan()`; `Timezone::forTrail()`, `label()`, `display()`; `hitung.ts` (`jarakMeter`, `kmPadaProfil`, `panjangGaris`, `teksAkurasi`, `teksJarak`); primitif `LembarBawah`, `Card`, `TombolSalin`, `Button` (`href`, `external`), `PageHeader` (`induk`), `JudulHalaman`, `LencanaDataContoh`.
- Produces: middleware alias `fitur` (`EnsureFeatureEnabled::handle(Request, Closure, string $fitur)`, hanya `field_pack` dan `field_safety`); config `hiking.field_safety.enabled` (`FIELD_SAFETY_ENABLED`); `KartuDarurat::untuk(Trail): array{nomor, pengelola, tetap}`, `KartuDarurat::telepon(string): ?string`; `PesanRencana::TENGGANG_JAM` (1), `PesanRencana::untuk(TripPlan, User): array{pesan, kurang, kembali, registrasi, zona, urls}`; `CetakTrip::untuk(TripPlan, User): array`; kolom `trip_plans.expected_return_at`, `registration_number`; rute `trips.print` (GET `trips/{trip}/cetak`) dan `trips.return.update` (PUT `trips/{trip}/kembali`, berbatas laju `jam-kembali`); kunci `darurat` di `HalamanMendaki` dan prop `keselamatan` di `Trips/Show` (keduanya null selama sakelar mati); `elevasiPada(profil, km)`, `teksKoordinat(lat, lng)` di `hitung.ts`; TypeScript `DaruratLapangan`, `KeselamatanHalaman`; komponen `KartuDarurat`, `KeselamatanTrip`; halaman `Trips/Print`.

- [ ] **Step 1: Tulis test yang gagal.**

Buat `tests/Feature/EmergencyCardTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\AuthorityType;
use App\Enums\HikingSessionStatus;
use App\Enums\TripStatus;
use App\Models\Authority;
use App\Models\HikingSession;
use App\Models\Mountain;
use App\Models\PermitRequirement;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Kartu darurat dan versi cetak (spec v3 bagian 10.2 dan 10.8; rencana induk W6 butir 8;
 * ruling W6-R4). Spec v3 bagian 15 mengaitkan alat keselamatan dengan Q14, jadi keduanya mati
 * sampai pemilik memutus; test ini menjaga kedua keadaan sakelar.
 */
class EmergencyCardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: TripPlan}
     */
    private function pendakianBerlangsung(?Mountain $gunung = null): array
    {
        $user = User::factory()->create();
        $trail = Trail::factory()->create(['name' => 'Gede via Cibodas', 'mountain_id' => ($gunung ?? Mountain::factory()->create(['name' => 'Gunung Gede', 'province' => 'Jawa Barat']))->id]);
        $trip = TripPlan::factory()->create(['user_id' => $user->id, 'trail_id' => $trail->id, 'status' => TripStatus::IN_PROGRESS->value]);

        HikingSession::create([
            'trip_plan_id' => $trip->id,
            'user_id' => $user->id,
            'status' => HikingSessionStatus::ACTIVE->value,
            'started_at' => now()->subHour(),
        ]);

        return [$user, $trip];
    }

    private function balai(Mountain $gunung, array $isian = []): Authority
    {
        $balai = Authority::create([
            'name' => 'Balai Besar Taman Nasional Gunung Gede Pangrango',
            'slug' => 'bbtnggp-'.$gunung->id,
            'abbreviation' => 'BBTNGGP',
            'type' => AuthorityType::NATIONAL_PARK->value,
            'contact' => '(0263) 512-776',
            'verified_at' => now()->subMonth(),
            ...$isian,
        ]);
        $balai->mountains()->attach($gunung);

        return $balai;
    }

    public function test_while_the_switch_is_off_there_is_no_card_and_no_print_version(): void
    {
        [$user, $trip] = $this->pendakianBerlangsung();

        $this->actingAs($user)->get(route('trips.hike', $trip))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('darurat', null));

        $this->actingAs($user)->get(route('trips.print', $trip))->assertNotFound();
        // Rute yang mati tidak membocorkan keberadaannya lewat 403 ke pengguna lain.
        $this->actingAs(User::factory()->create())->get(route('trips.print', $trip))->assertNotFound();
    }

    public function test_the_card_carries_112_and_115_and_tells_the_hiker_to_stay_put(): void
    {
        config(['hiking.field_safety.enabled' => true]);
        [$user, $trip] = $this->pendakianBerlangsung();

        $respons = $this->actingAs($user)->get(route('trips.hike', $trip));

        $respons->assertInertia(fn (AssertableInertia $page) => $page
            ->where('darurat.nomor', [['label' => 'Darurat', 'nomor' => '112'], ['label' => 'Basarnas', 'nomor' => '115']])
            ->where('darurat.pengelola', null)
            ->where('darurat.tetap', 'Setelah menelepon, tetap di tempat kecuali tempat itu berbahaya. Petugas mencari dari lokasi yang Anda sebutkan.'));

        // R-012/R-014: tanpa janji keselamatan.
        $this->assertDoesNotMatchRegularExpression('/\b(aman|dijamin|terjamin|pasti)\b/i', json_encode($respons->viewData('page')['props']['darurat'], JSON_UNESCAPED_UNICODE));
    }

    /**
     * Kontak pengelola hanya bila resmi diketahui: badan berwenang atas status jalur, sudah
     * diverifikasi, dan kontaknya tercatat. Nomor tidak pernah ditebak (R-007).
     */
    public function test_the_manager_contact_appears_only_when_it_is_officially_known(): void
    {
        config(['hiking.field_safety.enabled' => true]);
        $gunung = Mountain::factory()->create(['name' => 'Gunung Gede']);
        [$user, $trip] = $this->pendakianBerlangsung($gunung);
        $balai = $this->balai($gunung, ['verified_at' => null]);

        $this->actingAs($user)->get(route('trips.hike', $trip))->assertInertia(fn (AssertableInertia $page) => $page->where('darurat.pengelola', null));

        $balai->update(['verified_at' => now()->subMonth()]);

        $this->actingAs($user)->get(route('trips.hike', $trip))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('darurat.pengelola', ['nama' => 'Balai Besar Taman Nasional Gunung Gede Pangrango (BBTNGGP)', 'kontak' => '(0263) 512-776', 'telepon' => '0263512776']));

        $balai->update(['contact' => 'https://tnggp.go.id/kontak']);

        $this->actingAs($user)->get(route('trips.hike', $trip))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('darurat.pengelola.kontak', 'https://tnggp.go.id/kontak')
            ->where('darurat.pengelola.telepon', null));
    }

    /**
     * Lembaga sertifikasi mengesahkan orang, bukan keadaan jalur: kontaknya bukan kontak darurat.
     */
    public function test_a_certification_body_is_never_the_manager_contact(): void
    {
        config(['hiking.field_safety.enabled' => true]);
        $gunung = Mountain::factory()->create();
        [$user, $trip] = $this->pendakianBerlangsung($gunung);
        $this->balai($gunung, ['name' => 'Badan Nasional Sertifikasi Profesi', 'abbreviation' => 'BNSP', 'type' => AuthorityType::CERTIFICATION_BODY->value]);

        $this->actingAs($user)->get(route('trips.hike', $trip))->assertInertia(fn (AssertableInertia $page) => $page->where('darurat.pengelola', null));
    }

    /**
     * Versi cetak: pemilik saja, dengan aturan resmi bertanggal cek dan kartu darurat.
     */
    public function test_the_print_version_is_for_the_owner_and_dates_the_official_rules(): void
    {
        config(['hiking.field_safety.enabled' => true]);
        $this->travelTo(Carbon::parse('2026-10-07 03:00:00', 'UTC'));
        [$user, $trip] = $this->pendakianBerlangsung();
        PermitRequirement::factory()->create(['trail_id' => $trip->trail_id, 'authority' => 'BBTNGGP', 'verified_at' => Carbon::parse('2026-09-12 02:00:00', 'UTC')]);

        $this->get(route('trips.print', $trip))->assertRedirect(route('login'));

        $this->actingAs($user)->get(route('trips.print', $trip))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Trips/Print')
            ->where('jalur.nama', 'Gede via Cibodas')
            ->where('aturan.ringkasan', 'BBTNGGP · kuota 200 pendaki/hari · pemesanan ditutup H-2 · maksimal 2 hari')
            ->where('aturan.dicek', '12 September 2026 WIB')
            ->where('darurat.nomor.1.nomor', '115')
            ->where('dicetak', '7 Oktober 2026 10.00 WIB'));

        $this->actingAs(User::factory()->create())->get(route('trips.print', $trip))->assertForbidden();
    }

    public function test_without_recorded_rules_the_print_version_says_so(): void
    {
        config(['hiking.field_safety.enabled' => true]);
        [$user, $trip] = $this->pendakianBerlangsung();

        $this->actingAs($user)->get(route('trips.print', $trip))->assertInertia(fn (AssertableInertia $page) => $page->where('aturan', null));
        $this->assertStringContainsString('Aturan resmi jalur ini belum tercatat di Memount.', File::get(resource_path('js/pages/Trips/Print.tsx')));
    }

    /**
     * Blok lokasi dihitung di perangkat dari posisi yang tidak pernah dikirim ke server.
     */
    public function test_the_location_block_is_computed_on_the_device(): void
    {
        $kartu = File::get(resource_path('js/components/mendaki/KartuDarurat.tsx'));

        $this->assertStringContainsString('teksKoordinat(', $kartu);
        $this->assertStringContainsString('elevasiPada(', $kartu);
        $this->assertStringNotContainsString('fetch(', $kartu);
        $this->assertStringNotContainsString('router.', $kartu);
        $this->assertStringContainsString('href={`tel:${n.nomor}`}', $kartu);
    }
}
```

Buat `tests/Feature/TripPlanMessageTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\TripStatus;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Pesan rencana perjalanan untuk kontak (spec v3 bagian 10.8; rencana induk W6 butir 8; ruling
 * W6-R4): teks WhatsApp atau SMS dengan instruksi eskalasi satu jam sesudah jam kembali, dan
 * pernyataan bahwa Memount tidak memantau perjalanan. Mati sampai pemilik memutus Q14.
 */
class TripPlanMessageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-07 03:00:00', 'UTC'));
        config(['hiking.field_safety.enabled' => true]);
    }

    /**
     * Budi berencana camping di Gede via Cibodas 17 sampai 18 Oktober 2026, mulai 07.00, bertiga.
     *
     * @return array{0: User, 1: TripPlan}
     */
    private function rencana(string $zona = 'Asia/Jakarta', string $gunung = 'Gunung Gede', string $provinsi = 'Jawa Barat'): array
    {
        $user = User::factory()->create(['name' => 'Budi Santoso']);
        $trail = Trail::factory()->create([
            'name' => 'Gede via Cibodas',
            'mountain_id' => Mountain::factory()->create(['name' => $gunung, 'province' => $provinsi, 'timezone' => $zona])->id,
        ]);
        $trip = TripPlan::factory()->create([
            'user_id' => $user->id,
            'trail_id' => $trail->id,
            'name' => 'Gede akhir pekan',
            'planned_date' => '2026-10-17',
            'end_date' => '2026-10-18',
            'start_time' => '07:00',
            'participants_count' => 3,
            'status' => TripStatus::PLANNED->value,
        ]);

        return [$user, $trip];
    }

    private function simpanKembali(User $user, TripPlan $trip, array $isian = []): TestResponse
    {
        return $this->actingAs($user)->from(route('trips.show', $trip))->put(route('trips.return.update', $trip), [
            'kembali_tanggal' => '2026-10-18',
            'kembali_jam' => '15:00',
            'registrasi' => null,
            ...$isian,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function keselamatan(User $user, TripPlan $trip): array
    {
        return $this->actingAs($user)->get(route('trips.show', $trip))->viewData('page')['props']['keselamatan'];
    }

    public function test_without_a_return_time_there_is_no_message(): void
    {
        [$user, $trip] = $this->rencana();

        $this->assertNull($this->keselamatan($user, $trip)['pesan']);
        $this->assertSame('Isi perkiraan jam kembali dulu. Tanpa itu, kontak Anda tidak tahu kapan harus bertindak.', $this->keselamatan($user, $trip)['kurang']);
    }

    public function test_the_message_escalates_one_hour_after_the_return_time(): void
    {
        [$user, $trip] = $this->rencana();

        $this->simpanKembali($user, $trip)
            ->assertRedirect(route('trips.show', $trip))
            ->assertSessionHas('status', 'Jam kembali disimpan. Pesan rencana sudah memakai jam ini.');

        $this->assertSame('2026-10-18 08:00:00', $trip->fresh()->expected_return_at->utc()->format('Y-m-d H:i:s'));

        $keselamatan = $this->keselamatan($user, $trip);

        $this->assertSame(implode("\n", [
            'Rencana pendakian Budi Santoso',
            'Jalur: Gede via Cibodas, Gunung Gede, Jawa Barat',
            'Mulai: Sabtu, 17 Oktober 2026 pukul 07.00 WIB',
            'Peserta: 3 orang',
            'Perkiraan kembali: Minggu, 18 Oktober 2026 pukul 15.00 WIB',
            '',
            'Bila belum ada kabar dari saya sampai Minggu, 18 Oktober 2026 pukul 16.00 WIB, hubungi basecamp jalur ini, lalu Basarnas 115 atau 112. Sebutkan nama jalur, tanggal mulai, dan jumlah peserta.',
            '',
            'Memount tidak memantau perjalanan ini dan tidak membagikan lokasi saya.',
        ]), $keselamatan['pesan']);
        $this->assertSame(['tanggal' => '2026-10-18', 'jam' => '15:00'], $keselamatan['kembali']);
        $this->assertDoesNotMatchRegularExpression('/\b(aman|dijamin|terjamin|pasti)\b/i', $keselamatan['pesan']);
    }

    /**
     * Jam dibaca dalam zona gunung: 15.00 WITA di Rinjani adalah 07.00 UTC, dan pesannya
     * menulis WITA.
     */
    public function test_the_return_time_is_read_in_the_mountain_zone(): void
    {
        [$user, $trip] = $this->rencana('Asia/Makassar', 'Gunung Rinjani', 'Nusa Tenggara Barat');

        $this->simpanKembali($user, $trip);

        $this->assertSame('2026-10-18 07:00:00', $trip->fresh()->expected_return_at->utc()->format('Y-m-d H:i:s'));
        $this->assertStringContainsString('sampai Minggu, 18 Oktober 2026 pukul 16.00 WITA', $this->keselamatan($user, $trip)['pesan']);
    }

    public function test_the_registration_number_is_named_in_the_message(): void
    {
        [$user, $trip] = $this->rencana();

        $this->simpanKembali($user, $trip, ['registrasi' => 'GPP-1017-0042']);

        $pesan = $this->keselamatan($user, $trip)['pesan'];

        $this->assertStringContainsString("Nomor registrasi: GPP-1017-0042\n", $pesan);
        $this->assertStringContainsString('Sebutkan nama jalur, tanggal mulai, jumlah peserta, dan nomor registrasi.', $pesan);
    }

    public function test_the_return_time_is_validated_and_owned(): void
    {
        [$user, $trip] = $this->rencana();

        $this->simpanKembali($user, $trip, ['kembali_tanggal' => '2026-10-16', 'kembali_jam' => '25:61', 'registrasi' => str_repeat('A', 61)])
            ->assertSessionHasErrors([
                'kembali_tanggal' => 'Tanggal kembali tidak boleh sebelum tanggal mulai trip.',
                'kembali_jam' => 'Tulis jam kembali sebagai jam dan menit, misalnya 15:00.',
                'registrasi' => 'Nomor registrasi paling panjang 60 karakter.',
            ]);

        $this->simpanKembali(User::factory()->create(), $trip)->assertForbidden();

        $this->assertNull($trip->fresh()->expected_return_at);
    }

    public function test_while_the_switch_is_off_nothing_is_offered(): void
    {
        config(['hiking.field_safety.enabled' => false]);
        [$user, $trip] = $this->rencana();

        $this->actingAs($user)->get(route('trips.show', $trip))->assertInertia(fn (AssertableInertia $page) => $page->where('keselamatan', null));
        $this->simpanKembali($user, $trip)->assertNotFound();
        $this->simpanKembali(User::factory()->create(), $trip)->assertNotFound();
    }

    public function test_saving_the_return_time_is_rate_limited(): void
    {
        [$user, $trip] = $this->rencana();

        foreach (range(1, (int) config('hiking.rate_limits.personal_writes_per_minute')) as $ignored) {
            $this->simpanKembali($user, $trip)->assertRedirect();
        }

        $this->simpanKembali($user, $trip)->assertTooManyRequests();
    }
}
```

Buat `tests/Spatial/FieldModeColumnsSchemaTest.php`:

```php
<?php

namespace Tests\Spatial;

use Illuminate\Support\Facades\DB;

/**
 * Kolom aditif W6 di PostgreSQL (R-024): jeda sesi mode lapangan, alasan pembatalan DK-50,
 * angka ubahan ringkasan, serta jam kembali dan nomor registrasi pesan rencana. Suite SQLite
 * tidak membuktikan jenis, panjang, nilai bawaan, dan nullability kolom seperti di produksi.
 * Migrasinya tidak dijalankan di Supabase sebelum izin pemilik (spec v3 Q2).
 */
class FieldModeColumnsSchemaTest extends SpatialTestCase
{
    public function test_the_additive_columns_have_their_production_types(): void
    {
        $kolom = collect(DB::select(
            "SELECT table_name, column_name, data_type, is_nullable, column_default, character_maximum_length
             FROM information_schema.columns
             WHERE table_schema = current_schema() AND table_name IN ('hiking_sessions', 'trip_plans', 'hiking_history')"
        ))->keyBy(fn (object $k) => $k->table_name.'.'.$k->column_name);

        $harapan = [
            'hiking_sessions.paused_at' => ['timestamp without time zone', 'YES', null],
            'hiking_sessions.paused_seconds' => ['integer', 'NO', null],
            'trip_plans.cancellation_reason' => ['character varying', 'YES', 20],
            'trip_plans.expected_return_at' => ['timestamp without time zone', 'YES', null],
            'trip_plans.registration_number' => ['character varying', 'YES', 60],
            'hiking_history.recorded_distance_m' => ['integer', 'YES', null],
            'hiking_history.recorded_gain_m' => ['integer', 'YES', null],
            'hiking_history.recorded_moving_minutes' => ['integer', 'YES', null],
            'hiking_history.recorded_highest_m' => ['integer', 'YES', null],
            'hiking_history.numbers_edited_at' => ['timestamp without time zone', 'YES', null],
        ];

        foreach ($harapan as $nama => [$jenis, $boleh, $panjang]) {
            $this->assertTrue($kolom->has($nama), "Kolom {$nama} tidak ada.");
            $this->assertSame($jenis, $kolom[$nama]->data_type, $nama);
            $this->assertSame($boleh, $kolom[$nama]->is_nullable, $nama);
            $this->assertSame($panjang, $kolom[$nama]->character_maximum_length === null ? null : (int) $kolom[$nama]->character_maximum_length, $nama);
        }

        // Jeda yang belum pernah terjadi bernilai nol detik, bukan kosong, untuk baris lama juga.
        $this->assertSame('0', $kolom['hiking_sessions.paused_seconds']->column_default);
    }
}
```

`tests/Feature/FieldModeFiguresTest.php`:

1. Ganti:

```php
    /**
     * Memanggil fungsi hitung.ts di Node dan mengembalikan hasilnya berurutan.
```

dengan:

```php
    /**
     * Blok lokasi kartu darurat (spec v3 bagian 10.2; ruling W6-R4): koordinat dibacakan dengan
     * penanda arah Indonesia, dan ketinggian dibaca dari profil elevasi pada jarak yang sudah
     * ditempuh, bukan dari GPS. Tanpa profil, ketinggian tidak ditebak.
     */
    public function test_the_emergency_location_block_reads_as_it_is_spoken(): void
    {
        $profil = [[0, 1500], [2, 1900], [4, 2400]];

        $this->assertSame(
            ['6,78412° LS, 106,99031° BT', '0,50000° LU, 100,10000° BT', 1700, 2400, null, null],
            $this->hitung([
                ['teksKoordinat', [-6.784123, 106.990314]],
                ['teksKoordinat', [0.5, 100.1]],
                ['elevasiPada', [$profil, 1]],
                ['elevasiPada', [$profil, 9]],
                ['elevasiPada', [null, 1]],
                ['elevasiPada', [$profil, null]],
            ])
        );
    }

    /**
     * Memanggil fungsi hitung.ts di Node dan mengembalikan hasilnya berurutan.
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='EmergencyCard|TripPlanMessage|FieldModeFigures'`
Expected: FAIL. Pada simulasi rencana: 24 test, 9 lulus, 6 gagal, 9 error. Yang merah: tujuh test `EmergencyCardTest` (prop `darurat`, rute `trips.print`, dan `KartuDarurat.tsx` belum ada), tujuh test `TripPlanMessageTest` (prop `keselamatan` dan rute `trips.return.update` belum ada), dan `FieldModeFiguresTest::test_the_emergency_location_block_reads_as_it_is_spoken` (`teksKoordinat` belum ada).

- [ ] **Step 3: Tulis implementasinya.** Middleware dengan
`DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:middleware --no-interaction EnsureFeatureEnabled`,
kelas dengan `... make:class --no-interaction Services/Trips/KartuDarurat` (juga `PesanRencana`, `CetakTrip`),
controller dengan `... make:controller --no-interaction Trips/TripSafetyController`, request dengan
`... make:request --no-interaction Trips/UpdateTripReturnRequest`, migrasi dengan
`... make:migration --no-interaction add_return_and_registration_to_trip_plans_table` (ganti nama berkasnya), lalu
isinya diganti persis.

Buat `app/Http/Controllers/Trips/TripSafetyController.php`:

```php
<?php

namespace App\Http\Controllers\Trips;

use App\Http\Controllers\Controller;
use App\Http\Requests\Trips\UpdateTripReturnRequest;
use App\Models\TripPlan;
use App\Services\Trips\CetakTrip;
use App\Support\Timezone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Alat keselamatan trip (spec v3 bagian 10.8; rencana induk W6 butir 8; ruling W6-R4): versi
 * cetak dan jam kembali untuk pesan rencana. Pemilik trip saja (Policy yang sama dengan trip);
 * rutenya menjawab 404 selama sakelar hiking.field_safety.enabled mati (middleware fitur, Q14).
 */
class TripSafetyController extends Controller
{
    public function print(Request $request, TripPlan $trip, CetakTrip $cetak): Response
    {
        $this->authorize('view', $trip);

        return Inertia::render('Trips/Print', [
            'title' => 'Cetak '.$trip->name,
            ...$cetak->untuk($trip, $request->user()),
        ]);
    }

    public function updateReturn(UpdateTripReturnRequest $request, TripPlan $trip): RedirectResponse
    {
        $zona = Timezone::forTrail($trip->trail);

        $trip->update([
            'expected_return_at' => Carbon::createFromFormat('Y-m-d H:i', $request->validated('kembali_tanggal').' '.$request->validated('kembali_jam'), $zona)->utc(),
            'registration_number' => $request->validated('registrasi'),
        ]);

        return back()->with('status', 'Jam kembali disimpan. Pesan rencana sudah memakai jam ini.');
    }
}
```

Buat `app/Http/Middleware/EnsureFeatureEnabled.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rute fitur yang menunggu keputusan pemilik menjawab 404 selama sakelarnya mati (ruling W6-R3
 * dan W6-R4): paket lapangan luring (hiking.field_pack, Q10 dan Q14) dan alat keselamatan trip
 * (hiking.field_safety, Q14). Diperiksa sebelum otorisasi dan validasi, jadi rute yang mati
 * tidak membocorkan keberadaannya lewat 403 atau galat isian.
 */
class EnsureFeatureEnabled
{
    public function handle(Request $request, Closure $next, string $fitur): Response
    {
        abort_unless(in_array($fitur, ['field_pack', 'field_safety'], true) && config("hiking.{$fitur}.enabled") === true, 404);

        return $next($request);
    }
}
```

Buat `app/Http/Requests/Trips/UpdateTripReturnRequest.php`:

```php
<?php

namespace App\Http\Requests\Trips;

use App\Models\TripPlan;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Jam kembali dan nomor registrasi untuk pesan rencana (spec v3 bagian 10.8; ruling W6-R4).
 * Tanggal dan jam dibaca dalam zona gunung; tanggal kembali tidak boleh sebelum tanggal mulai.
 */
class UpdateTripReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        $trip = $this->route('trip');

        return $trip instanceof TripPlan && $this->user()?->can('update', $trip) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var TripPlan $trip */
        $trip = $this->route('trip');

        return [
            'kembali_tanggal' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$trip->planned_date->toDateString()],
            'kembali_jam' => ['required', 'date_format:H:i'],
            'registrasi' => ['nullable', 'string', 'max:60'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kembali_tanggal.required' => 'Isi tanggal perkiraan kembali.',
            'kembali_tanggal.after_or_equal' => 'Tanggal kembali tidak boleh sebelum tanggal mulai trip.',
            'kembali_tanggal.date_format' => 'Tulis tanggal kembali sebagai tanggal kalender.',
            'kembali_jam.required' => 'Isi jam perkiraan kembali.',
            'kembali_jam.date_format' => 'Tulis jam kembali sebagai jam dan menit, misalnya 15:00.',
            'registrasi.max' => 'Nomor registrasi paling panjang 60 karakter.',
        ];
    }
}
```

Buat `app/Services/Trips/CetakTrip.php`:

```php
<?php

namespace App\Services\Trips;

use App\Models\Checkpoint;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\CheckpointPaceService;
use App\Services\PermitService;
use App\Services\Trails\CheckpointJourney;
use App\Support\Timezone;

/**
 * Versi cetak satu trip (spec v3 bagian 10.8; rencana induk W6 butir 8; ruling W6-R4): sketsa
 * rute, tabel pos, aturan resmi bertanggal, kartu darurat, dan rencana. Cadangan kertas untuk
 * paket di ponsel, jadi isinya data stabil yang dimiliki pendaki dan tidak ada status terkini
 * yang dapat basi tanpa tanda (R-013): yang bertanggal menyebut tanggal ceknya.
 */
final class CetakTrip
{
    public function __construct(
        private readonly KartuDarurat $darurat,
        private readonly PesanRencana $pesan,
        private readonly PermitService $izin,
        private readonly CheckpointPaceService $pace,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function untuk(TripPlan $trip, User $pengguna): array
    {
        $trip->loadMissing('trail.mountain.authorities', 'trail.checkpoints');
        $jalur = $trip->trail;
        $zona = Timezone::forTrail($jalur);
        $izin = $this->izin->requirementFor($jalur);
        $garis = $jalur->readGeoJson('geometry');
        $rencana = $this->pesan->untuk($trip, $pengguna);

        return [
            'jalur' => [
                'nama' => $jalur->name,
                'wilayah' => implode(' · ', array_filter([$jalur->mountain?->name, $jalur->mountain?->province])),
            ],
            'rencana' => [
                'nama' => $trip->name,
                'tanggal' => $trip->ringkasan(),
                'jamMulai' => $trip->start_time ? str_replace(':', '.', substr((string) $trip->start_time, 0, 5)).' '.Timezone::label($zona) : null,
                'kembali' => Timezone::display($trip->expected_return_at, $zona, 'l, j F Y H.i'),
                'registrasi' => $trip->registration_number,
            ],
            'sketsa' => [
                'garis' => ($garis['type'] ?? null) === 'LineString' ? $garis['coordinates'] : null,
                'pos' => $jalur->checkpoints
                    ->filter(fn (Checkpoint $p) => $p->latitude !== null && $p->longitude !== null)
                    ->sortBy('sequence')
                    ->map(fn (Checkpoint $p) => ['urutan' => (int) $p->sequence, 'lng' => (float) $p->longitude, 'lat' => (float) $p->latitude])
                    ->values()
                    ->all(),
            ],
            'pos' => CheckpointJourney::daftar($jalur->checkpoints, $this->pace->forTrail($jalur), []),
            'aturan' => $izin === null ? null : [
                'ringkasan' => $izin->summary(),
                'catatan' => $izin->notes,
                'dicek' => Timezone::display($izin->verified_at, $zona, 'j F Y'),
            ],
            'darurat' => $this->darurat->untuk($jalur),
            'pesan' => $rencana['pesan'],
            'dicetak' => Timezone::display(now(), $zona, 'j F Y H.i'),
            'tripId' => $trip->id,
        ];
    }
}
```

Buat `app/Services/Trips/KartuDarurat.php`:

```php
<?php

namespace App\Services\Trips;

use App\Models\Authority;
use App\Models\Trail;

/**
 * Isi kartu darurat satu jalur (spec v3 bagian 10.2 dan 10.8; rencana induk W6 butir 8; ruling
 * W6-R4): 112 dan Basarnas 115 sebagai tautan telepon, kontak pengelola bila resmi diketahui,
 * dan kalimat tetap di tempat setelah menelepon. Blok lokasi untuk dibacakan dihitung di
 * perangkat dari posisi yang tidak pernah dikirim ke server.
 *
 * Kontak pengelola hanya dari badan berwenang atas status jalur yang sudah diverifikasi dan
 * kontaknya tercatat. Nomor tidak pernah ditebak (R-007).
 */
final class KartuDarurat
{
    /**
     * @return array{nomor: array<int, array{label: string, nomor: string}>, pengelola: array{nama: string, kontak: string, telepon: string|null}|null, tetap: string}
     */
    public function untuk(Trail $jalur): array
    {
        $jalur->loadMissing('mountain.authorities');

        $pengelola = $jalur->mountain?->authorities
            ->first(fn (Authority $a) => $a->type->mayDeclareTrailStatus() && $a->verified_at !== null && filled($a->contact));

        return [
            'nomor' => [
                ['label' => 'Darurat', 'nomor' => '112'],
                ['label' => 'Basarnas', 'nomor' => '115'],
            ],
            'pengelola' => $pengelola === null ? null : [
                'nama' => $pengelola->displayName(),
                'kontak' => (string) $pengelola->contact,
                'telepon' => self::telepon((string) $pengelola->contact),
            ],
            'tetap' => 'Setelah menelepon, tetap di tempat kecuali tempat itu berbahaya. Petugas mencari dari lokasi yang Anda sebutkan.',
        ];
    }

    /**
     * Nomor untuk tautan tel: bila kontaknya memang nomor telepon; selain itu null, dan kontak
     * ditampilkan sebagai teks.
     */
    public static function telepon(string $kontak): ?string
    {
        $nomor = preg_replace('/[\s().-]/', '', $kontak);

        return preg_match('/^\+?\d{3,15}$/', (string) $nomor) === 1 ? $nomor : null;
    }
}
```

Buat `app/Services/Trips/PesanRencana.php`:

```php
<?php

namespace App\Services\Trips;

use App\Models\TripPlan;
use App\Models\User;
use App\Support\Timezone;
use Illuminate\Support\Carbon;

/**
 * Pesan rencana perjalanan untuk kontak yang tidak ikut mendaki (spec v3 bagian 10.8; rencana
 * induk W6 butir 8; ruling W6-R4). Teks biasa yang dikirim pendaki sendiri lewat WhatsApp atau
 * SMS, dengan instruksi eskalasi: bila belum ada kabar sampai jam kembali ditambah satu jam,
 * hubungi basecamp atau pengelola, lalu Basarnas 115 atau 112. Pesan menyatakan bahwa Memount
 * tidak memantau perjalanan; ini bukan berbagi lokasi langsung.
 *
 * Tanpa jam kembali pesannya tidak dibuat: kontak tidak tahu kapan harus bertindak, dan jam
 * yang ditebak lebih berbahaya daripada tidak ada (R-007).
 */
final class PesanRencana
{
    /** Tenggang sebelum kontak bertindak, sesudah jam kembali (spec v3 bagian 10.8). */
    public const TENGGANG_JAM = 1;

    public function __construct(private readonly KartuDarurat $darurat) {}

    /**
     * @return array{pesan: string|null, kurang: string|null, kembali: array{tanggal: string|null, jam: string|null}, registrasi: string|null, zona: string, urls: array{simpan: string, cetak: string}}
     */
    public function untuk(TripPlan $trip, User $pengguna): array
    {
        $trip->loadMissing('trail.mountain.authorities');
        $zona = Timezone::forTrail($trip->trail);
        $kembali = $trip->expected_return_at?->copy()->setTimezone($zona);

        return [
            'pesan' => $kembali === null ? null : $this->teks($trip, $pengguna, $kembali, $zona),
            'kurang' => $kembali === null ? 'Isi perkiraan jam kembali dulu. Tanpa itu, kontak Anda tidak tahu kapan harus bertindak.' : null,
            'kembali' => ['tanggal' => $kembali?->toDateString(), 'jam' => $kembali?->format('H:i')],
            'registrasi' => $trip->registration_number,
            'zona' => Timezone::label($zona),
            'urls' => [
                'simpan' => route('trips.return.update', $trip),
                'cetak' => route('trips.print', $trip),
            ],
        ];
    }

    private function teks(TripPlan $trip, User $pengguna, Carbon $kembali, string $zona): string
    {
        $jalur = $trip->trail;
        $label = Timezone::label($zona);
        $waktu = fn (Carbon $t) => $t->translatedFormat('l, j F Y').' pukul '.$t->format('H.i').' '.$label;
        $jamMulai = $trip->start_time ? ' pukul '.str_replace(':', '.', substr((string) $trip->start_time, 0, 5)).' '.$label : '';
        $pengelola = $this->darurat->untuk($jalur)['pengelola'];
        $sebutkan = $trip->registration_number ? 'nama jalur, tanggal mulai, jumlah peserta, dan nomor registrasi' : 'nama jalur, tanggal mulai, dan jumlah peserta';

        $baris = array_filter([
            'Rencana pendakian '.$pengguna->name,
            'Jalur: '.$jalur->name.', '.implode(', ', array_filter([$jalur->mountain?->name, $jalur->mountain?->province])),
            'Mulai: '.$trip->planned_date->translatedFormat('l, j F Y').$jamMulai,
            $trip->participants_count ? 'Peserta: '.$trip->participants_count.' orang' : null,
            $trip->registration_number ? 'Nomor registrasi: '.$trip->registration_number : null,
            'Perkiraan kembali: '.$waktu($kembali),
        ]);

        return implode("\n", $baris)
            ."\n\n".'Bila belum ada kabar dari saya sampai '.$waktu($kembali->copy()->addHours(self::TENGGANG_JAM)).', hubungi '
            .($pengelola === null ? 'basecamp jalur ini' : 'pengelola jalur ('.$pengelola['nama'].', '.$pengelola['kontak'].')')
            .', lalu Basarnas 115 atau 112. Sebutkan '.$sebutkan.'.'
            ."\n\n".'Memount tidak memantau perjalanan ini dan tidak membagikan lokasi saya.';
    }
}
```

Buat `database/migrations/2026_10_07_000500_add_return_and_registration_to_trip_plans_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jam kembali dan nomor registrasi untuk pesan rencana ke kontak (spec v3 bagian 10.8 dan
 * bagian 15 Q14: "kolom nomor registrasi dan jam kembali"; ruling W6-R4).
 *
 * Jam kembali disimpan sebagai waktu UTC dan ditampilkan dalam zona gunung. Keduanya diisi
 * pendaki sendiri dan boleh kosong; kosong berarti belum disebut, bukan "tanpa batas" (R-007).
 * Aditif dan boleh kosong untuk baris lama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_plans', function (Blueprint $table) {
            $table->timestamp('expected_return_at')->nullable()->after('end_date');
            $table->string('registration_number', 60)->nullable()->after('expected_return_at');
        });
    }

    public function down(): void
    {
        Schema::table('trip_plans', function (Blueprint $table) {
            $table->dropColumn(['expected_return_at', 'registration_number']);
        });
    }
};
```

Buat `resources/js/components/mendaki/KartuDarurat.tsx`:

```tsx
import { useRef, useState } from 'react';
import { fokusTerlihat, umpanTekan } from '@/components/ui/gaya';
import { Icon } from '@/components/ui/Icon';
import { LembarBawah } from '@/components/ui/Lembar';
import { kelas } from '@/lib/kelas';
import { elevasiPada, jarakMeter, kmPadaProfil, panjangGaris, teksAkurasi, teksJarak, teksKoordinat } from './hitung';
import type { DataLapangan, DaruratLapangan, PosisiPerangkat } from './types';

interface KartuDaruratProps {
    darurat: DaruratLapangan;
    data: DataLapangan;
    posisi: PosisiPerangkat | null;
    /** Jarak yang sudah ditempuh menyusuri garis jalur, dalam meter; null bila tidak diketahui. */
    dilaluiM: number | null;
    tercapai: number;
}

const jam = new Intl.DateTimeFormat('id-ID', { hour: '2-digit', minute: '2-digit' });

/**
 * Kartu darurat (spec v3 bagian 10.2 dan 10.8; ruling W6-R4): satu ketukan dari mode lapangan.
 * Nomor 112 dan Basarnas 115 sebagai tautan telepon 66 px, kontak pengelola bila resmi
 * diketahui, kalimat tetap di tempat, dan blok lokasi untuk dibacakan kepada petugas.
 *
 * Blok lokasi dihitung di perangkat dari posisi terakhir yang tidak pernah dikirim ke server:
 * koordinat, pos terdekat, ketinggian dari profil jalur (bukan GPS), waktu, dan akurasi. Tanpa
 * posisi, kartu menyebut pos terakhir yang dicapai. Tidak ada janji keselamatan (R-012, R-014).
 */
export function KartuDarurat({ darurat, data, posisi, dilaluiM, tercapai }: KartuDaruratProps) {
    const [terbuka, setTerbuka] = useState(false);
    const pemicu = useRef<HTMLButtonElement>(null);
    const posTercapai = data.pos.find((p) => p.urutan === tercapai) ?? null;
    const terdekat = posisi
        ? data.pos
              .filter((p) => p.lng !== null && p.lat !== null)
              .map((p) => ({ nama: p.nama, meter: jarakMeter([posisi.lng, posisi.lat], [p.lng as number, p.lat as number]) }))
              .sort((a, b) => a.meter - b.meter)[0]
        : undefined;
    const km = data.garis && dilaluiM !== null && data.profil ? kmPadaProfil(dilaluiM, panjangGaris(data.garis), data.profil) : null;
    const ketinggian = elevasiPada(data.profil, km);

    return (
        <>
            <button
                ref={pemicu}
                type="button"
                onClick={() => setTerbuka(true)}
                className={kelas('inline-flex min-h-[60px] w-full items-center justify-center gap-2 rounded-tombol border-2 border-danger-600 bg-surface px-4 py-3 text-bagian font-bold text-danger-900', fokusTerlihat, umpanTekan)}
            >
                <Icon name="warning" size={20} />
                Kartu darurat
            </button>

            <LembarBawah terbuka={terbuka} onTutup={() => setTerbuka(false)} pemicu={pemicu} judul="Kartu darurat" keterangan={darurat.tetap}>
                <div className="space-y-5">
                    <ul className="grid grid-cols-2 gap-3">
                        {darurat.nomor.map((n) => (
                            <li key={n.nomor}>
                                <a
                                    href={`tel:${n.nomor}`}
                                    aria-label={`Telepon ${n.nomor}, ${n.label}`}
                                    className={kelas('flex min-h-[66px] flex-col items-center justify-center rounded-tombol bg-danger-600 px-3 py-2 text-white', fokusTerlihat, umpanTekan)}
                                >
                                    <span className="text-judul tabular-nums">{n.nomor}</span>
                                    <span className="text-kecil font-bold">{n.label}</span>
                                </a>
                            </li>
                        ))}
                    </ul>

                    <p className="text-isi text-primary">
                        {darurat.pengelola ? (
                            <>
                                Pengelola jalur: {darurat.pengelola.nama},{' '}
                                {darurat.pengelola.telepon ? (
                                    <a href={`tel:${darurat.pengelola.telepon}`} className={kelas('font-bold text-brand-ink underline underline-offset-2', fokusTerlihat)}>
                                        {darurat.pengelola.kontak}
                                    </a>
                                ) : (
                                    <span className="break-words font-bold">{darurat.pengelola.kontak}</span>
                                )}
                            </>
                        ) : (
                            'Kontak pengelola jalur ini belum tercatat di Memount.'
                        )}
                    </p>

                    <section aria-labelledby="judul-lokasi-darurat" className="rounded-kartu border border-subtle bg-surface-sunken p-4">
                        <h3 id="judul-lokasi-darurat" className="text-subjudul text-primary">
                            Bacakan kepada petugas
                        </h3>
                        <dl className="mt-2 space-y-2 text-isi text-primary">
                            <div>
                                <dt className="text-kecil text-secondary">Jalur</dt>
                                <dd className="font-bold">
                                    {data.trip.jalur}, {data.trip.gunung}
                                </dd>
                            </div>
                            {posisi ? (
                                <>
                                    <div>
                                        <dt className="text-kecil text-secondary">Koordinat</dt>
                                        <dd className="font-bold tabular-nums">{teksKoordinat(posisi.lat, posisi.lng)}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-kecil text-secondary">Diukur</dt>
                                        <dd className="tabular-nums">
                                            pukul {jam.format(posisi.waktu)}, akurasi {teksAkurasi(posisi.akurasi)}
                                        </dd>
                                    </div>
                                    {terdekat ? (
                                        <div>
                                            <dt className="text-kecil text-secondary">Pos terdekat</dt>
                                            <dd>
                                                {terdekat.nama}, sekitar {teksJarak(terdekat.meter)}
                                            </dd>
                                        </div>
                                    ) : null}
                                    {ketinggian !== null ? (
                                        <div>
                                            <dt className="text-kecil text-secondary">Ketinggian menurut profil jalur</dt>
                                            <dd className="tabular-nums">sekitar {ketinggian.toLocaleString('id-ID')} m</dd>
                                        </div>
                                    ) : null}
                                </>
                            ) : (
                                <div>
                                    <dt className="text-kecil text-secondary">Posisi</dt>
                                    <dd>
                                        Posisi belum didapat dari perangkat. {posTercapai ? `Pos terakhir yang dicapai: ${posTercapai.nama}.` : 'Belum ada pos yang tercatat dicapai.'}
                                    </dd>
                                </div>
                            )}
                        </dl>
                    </section>
                </div>
            </LembarBawah>
        </>
    );
}
```

Buat `resources/js/components/trips/KeselamatanTrip.tsx`:

```tsx
import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { InputError, Label, TextInput } from '@/components/ui/Field';
import { TombolSalin } from '@/components/ui/TombolSalin';

export interface KeselamatanHalaman {
    pesan: string | null;
    kurang: string | null;
    kembali: { tanggal: string | null; jam: string | null };
    registrasi: string | null;
    zona: string;
    urls: { simpan: string; cetak: string };
}

/**
 * Rencana untuk kontak (spec v3 bagian 10.8; ruling W6-R4): jam kembali dan nomor registrasi,
 * lalu pesan siap kirim lewat WhatsApp atau SMS dengan instruksi eskalasi, dan versi cetak.
 * Pesannya disusun server (App\Services\Trips\PesanRencana) dan dikirim pendaki sendiri; ini
 * bukan berbagi lokasi, dan Memount tidak memantau perjalanan.
 */
export function KeselamatanTrip({ keselamatan }: { keselamatan: KeselamatanHalaman }) {
    const form = useForm({
        kembali_tanggal: keselamatan.kembali.tanggal ?? '',
        kembali_jam: keselamatan.kembali.jam ?? '',
        registrasi: keselamatan.registrasi ?? '',
    });

    const simpan = (e: FormEvent) => {
        e.preventDefault();
        form.put(keselamatan.urls.simpan, { preserveScroll: true });
    };

    return (
        <Card title="Rencana untuk kontak" subtitle="Kirim rencana ini ke orang yang tidak ikut mendaki. Memount tidak memantau perjalanan dan tidak membagikan lokasi Anda.">
            <form onSubmit={simpan} className="space-y-4">
                <fieldset>
                    <legend className="text-isi font-bold text-primary">Perkiraan kembali ({keselamatan.zona})</legend>
                    <div className="mt-2 flex flex-wrap gap-3">
                        <div className="min-w-[10rem] flex-1">
                            <Label htmlFor="kembali-tanggal">Tanggal</Label>
                            <TextInput
                                id="kembali-tanggal"
                                type="date"
                                value={form.data.kembali_tanggal}
                                onChange={(e) => form.setData('kembali_tanggal', e.target.value)}
                                aria-invalid={form.errors.kembali_tanggal ? true : undefined}
                                aria-describedby={form.errors.kembali_tanggal ? 'kembali-tanggal-galat' : undefined}
                                className="mt-1 w-full"
                            />
                            <InputError id="kembali-tanggal-galat" messages={form.errors.kembali_tanggal} className="mt-2" />
                        </div>
                        <div className="min-w-[8rem] flex-1">
                            <Label htmlFor="kembali-jam">Jam</Label>
                            <TextInput
                                id="kembali-jam"
                                type="time"
                                value={form.data.kembali_jam}
                                onChange={(e) => form.setData('kembali_jam', e.target.value)}
                                aria-invalid={form.errors.kembali_jam ? true : undefined}
                                aria-describedby={form.errors.kembali_jam ? 'kembali-jam-galat' : undefined}
                                className="mt-1 w-full"
                            />
                            <InputError id="kembali-jam-galat" messages={form.errors.kembali_jam} className="mt-2" />
                        </div>
                    </div>
                </fieldset>
                <div>
                    <Label htmlFor="kembali-registrasi">
                        Nomor registrasi <span className="font-normal text-secondary">(opsional, dari tiket atau SIMAKSI)</span>
                    </Label>
                    <TextInput
                        id="kembali-registrasi"
                        value={form.data.registrasi}
                        onChange={(e) => form.setData('registrasi', e.target.value)}
                        aria-describedby={form.errors.registrasi ? 'kembali-registrasi-galat' : undefined}
                        className="mt-1 w-full"
                    />
                    <InputError id="kembali-registrasi-galat" messages={form.errors.registrasi} className="mt-2" />
                </div>
                <Button type="submit" variant="secondary" processing={form.processing}>
                    Simpan jam kembali
                </Button>
            </form>

            <div className="mt-6 border-t border-subtle pt-5">
                {keselamatan.pesan ? (
                    <>
                        <p className="text-kecil font-bold text-secondary">Pesan yang akan dikirim</p>
                        <p className="mt-2 whitespace-pre-line break-words rounded-control bg-surface-sunken p-4 text-kecil text-primary">{keselamatan.pesan}</p>
                        <div className="mt-4 flex flex-wrap items-start gap-3">
                            <Button href={`https://wa.me/?text=${encodeURIComponent(keselamatan.pesan)}`} external>
                                Kirim lewat WhatsApp
                            </Button>
                            <Button variant="secondary" href={`sms:?&body=${encodeURIComponent(keselamatan.pesan)}`} external>
                                Kirim lewat SMS
                            </Button>
                            <TombolSalin teks={keselamatan.pesan} label="Salin pesan" berhasil="Pesan disalin." />
                        </div>
                    </>
                ) : (
                    <p className="text-isi text-secondary">{keselamatan.kurang}</p>
                )}
                <p className="mt-4">
                    <Button variant="ghost" href={keselamatan.urls.cetak}>
                        Buka versi cetak
                    </Button>
                </p>
            </div>
        </Card>
    );
}
```

Buat `resources/js/pages/Trips/Print.tsx`:

```tsx
import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import type { PosJalur } from '@/components/trails/rinci/types';
import type { DaruratLapangan } from '@/components/mendaki/types';
import { Button } from '@/components/ui/Button';
import { JudulHalaman } from '@/components/ui/JudulHalaman';
import { LencanaDataContoh } from '@/components/ui/LencanaDataContoh';
import { PageHeader } from '@/components/ui/PageHeader';
import { show as tripShow } from '@/routes/trips';

interface Props {
    jalur: { nama: string; wilayah: string };
    rencana: { nama: string; tanggal: string; jamMulai: string | null; kembali: string | null; registrasi: string | null };
    sketsa: { garis: Array<[number, number]> | null; pos: Array<{ urutan: number; lng: number; lat: number }> };
    pos: PosJalur[];
    aturan: { ringkasan: string; catatan: string | null; dicek: string | null } | null;
    darurat: DaruratLapangan;
    pesan: string | null;
    dicetak: string;
    tripId: number;
    [key: string]: unknown;
}

const LEBAR = 1000;
const TINGGI_MAKS = 700;
const TEPI = 40;

/**
 * Titik [bujur, lintang] dipetakan ke kanvas sketsa dengan utara di atas. Bujur dikali kosinus
 * lintang tengah supaya bentuk rute tidak melebar ke timur-barat. Tanpa skala: sketsa ini
 * pengingat bentuk dan urutan pos, bukan peta navigasi.
 */
function proyeksi(titik: Array<[number, number]>) {
    const lintang = titik.map(([, lat]) => lat);
    const tengah = ((Math.min(...lintang) + Math.max(...lintang)) / 2) * (Math.PI / 180);
    const xs = titik.map(([lng]) => lng * Math.cos(tengah));
    const ys = titik.map(([, lat]) => -lat);
    const [x0, x1, y0, y1] = [Math.min(...xs), Math.max(...xs), Math.min(...ys), Math.max(...ys)];
    const skala = Math.min((LEBAR - 2 * TEPI) / Math.max(x1 - x0, 1e-9), (TINGGI_MAKS - 2 * TEPI) / Math.max(y1 - y0, 1e-9));
    const tinggi = Math.round((y1 - y0) * skala + 2 * TEPI);

    return {
        tinggi,
        titik: ([lng, lat]: [number, number]): [number, number] => [TEPI + (lng * Math.cos(tengah) - x0) * skala, TEPI + (-lat - y0) * skala],
    };
}

/**
 * Versi cetak trip (spec v3 bagian 10.8; rencana induk W6 butir 8; ruling W6-R4): sketsa rute,
 * tabel pos, aturan resmi bertanggal, kartu darurat, dan rencana, hitam di atas putih untuk
 * kertas A4. Cadangan untuk ponsel, bukan peta navigasi: tidak ada status terkini yang dapat
 * basi tanpa tanda, dan yang bertanggal menyebut tanggal ceknya (R-013).
 */
export default function Print() {
    const { jalur, rencana, sketsa, pos, aturan, darurat, pesan, dicetak, tripId } = usePage<Props>().props;
    const garis = sketsa.garis ?? (sketsa.pos.length >= 2 ? sketsa.pos.map((p) => [p.lng, p.lat] as [number, number]) : null);
    const peta = garis ? proyeksi([...garis, ...sketsa.pos.map((p) => [p.lng, p.lat] as [number, number])]) : null;

    // Kertas selalu terang: selama mencetak (tombol atau Ctrl+P) tema gelap dilepas, lalu
    // dipulihkan. Token tema gelap berlaku di bawah :root:not([data-theme='light']).
    useEffect(() => {
        const akar = document.documentElement;
        let sebelum: string | undefined;
        const mulaiCetak = () => {
            sebelum = akar.dataset.theme;
            akar.dataset.theme = 'light';
        };
        const selesaiCetak = () => {
            if (sebelum === undefined) {
                delete akar.dataset.theme;
            } else {
                akar.dataset.theme = sebelum;
            }
        };

        window.addEventListener('beforeprint', mulaiCetak);
        window.addEventListener('afterprint', selesaiCetak);

        return () => {
            window.removeEventListener('beforeprint', mulaiCetak);
            window.removeEventListener('afterprint', selesaiCetak);
        };
    }, []);

    return (
        <main id="konten" className="mx-auto max-w-3xl px-4 py-6 text-primary print:max-w-none print:p-0">
            <JudulHalaman />
            <div className="print:hidden">
                <PageHeader induk={{ href: tripShow.url(tripId), label: 'Trip' }} title="Versi cetak" description={rencana.nama}>
                    <Button className="mt-3" onClick={() => window.print()}>
                        Cetak halaman ini
                    </Button>
                </PageHeader>
            </div>

            <article className="space-y-6 print:space-y-4">
                <header className="border-b-2 border-current pb-3">
                    <p className="text-kecil">Memount · rencana pendakian · dicetak {dicetak}</p>
                    {/* Di layar lencananya dibawa kepala halaman; di kertas kepala itu tidak tercetak. */}
                    <div className="hidden print:block">
                        <LencanaDataContoh />
                    </div>
                    <h2 className="mt-1 text-judul">{jalur.nama}</h2>
                    <p className="text-isi">{jalur.wilayah}</p>
                </header>

                <section aria-labelledby="cetak-rencana">
                    <h3 id="cetak-rencana" className="text-bagian">
                        Rencana
                    </h3>
                    <dl className="mt-2 grid grid-cols-[auto_minmax(0,1fr)] gap-x-4 gap-y-1 text-isi">
                        <dt className="font-bold">Trip</dt>
                        <dd>{rencana.nama}</dd>
                        <dt className="font-bold">Tanggal</dt>
                        <dd>{rencana.tanggal}</dd>
                        <dt className="font-bold">Mulai</dt>
                        <dd>{rencana.jamMulai ?? 'Belum diisi'}</dd>
                        <dt className="font-bold">Kembali</dt>
                        <dd>{rencana.kembali ?? 'Belum diisi'}</dd>
                        <dt className="font-bold">Registrasi</dt>
                        <dd>{rencana.registrasi ?? 'Belum diisi'}</dd>
                    </dl>
                </section>

                <section aria-labelledby="cetak-darurat" className="break-inside-avoid rounded-kartu border-2 border-current p-4">
                    <h3 id="cetak-darurat" className="text-bagian">
                        Kartu darurat
                    </h3>
                    <p className="mt-2 text-judul-kartu tabular-nums">{darurat.nomor.map((n) => `${n.nomor} ${n.label}`).join(' · ')}</p>
                    <p className="mt-2 text-isi">{darurat.pengelola ? `Pengelola jalur: ${darurat.pengelola.nama}, ${darurat.pengelola.kontak}` : 'Kontak pengelola jalur ini belum tercatat di Memount.'}</p>
                    <p className="mt-2 text-isi font-bold">{darurat.tetap}</p>
                    <p className="mt-3 text-isi">Posisi terakhir, pos terdekat, dan jam (isi saat dibutuhkan):</p>
                    <p aria-hidden="true" className="mt-6 border-b border-current" />
                </section>

                <section aria-labelledby="cetak-sketsa" className="break-inside-avoid">
                    <h3 id="cetak-sketsa" className="text-bagian">
                        Sketsa rute
                    </h3>
                    {garis && peta ? (
                        <>
                            <svg
                                viewBox={`0 0 ${LEBAR} ${peta.tinggi}`}
                                role="img"
                                aria-label={`Sketsa rute ${jalur.nama} dengan ${sketsa.pos.length} pos bernomor`}
                                className="mt-2 h-auto w-full border border-current"
                            >
                                <polyline
                                    points={garis.map((t) => peta.titik(t).join(',')).join(' ')}
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth={5}
                                    strokeDasharray={sketsa.garis ? undefined : '14 10'}
                                    strokeLinejoin="round"
                                />
                                {sketsa.pos.length > 0
                                    ? sketsa.pos.map((p) => {
                                          const [x, y] = peta.titik([p.lng, p.lat]);

                                          return (
                                              <g key={p.urutan}>
                                                  <circle cx={x} cy={y} r={24} className="fill-surface" stroke="currentColor" strokeWidth={4} />
                                                  <text x={x} y={y + 10} textAnchor="middle" fontSize={28} fontWeight={700} fill="currentColor">
                                                      {p.urutan}
                                                  </text>
                                              </g>
                                          );
                                      })
                                    : null}
                                <text x={LEBAR - TEPI} y={TEPI} textAnchor="end" fontSize={30} fontWeight={700} fill="currentColor">
                                    U ↑
                                </text>
                            </svg>
                            <p className="mt-1 text-kecil">
                                {sketsa.garis ? 'Garis jalur dari data rute. ' : 'Garis lurus antarpos, bukan bentuk jalur sebenarnya. '}
                                {sketsa.pos.length === 0 ? 'Pos belum punya koordinat, jadi tidak digambar. ' : null}Tanpa skala; utara di atas. Bukan peta navigasi.
                            </p>
                        </>
                    ) : (
                        <p className="mt-2 text-isi">Sketsa tidak tersedia: jalur ini belum punya garis rute atau koordinat pos.</p>
                    )}
                </section>

                <section aria-labelledby="cetak-pos">
                    <h3 id="cetak-pos" className="text-bagian">
                        Pos
                    </h3>
                    {pos.length > 0 ? (
                        // Lima kolom tidak muat di layar 320 px: tabelnya bergulir di wadahnya sendiri,
                        // bukan halamannya (WCAG 1.4.10). Di kertas A4 lebarnya cukup.
                        <div className="relative mt-2 overflow-x-auto">
                            <table className="w-full border-collapse text-left text-kecil">
                                <thead>
                                    <tr className="border-b-2 border-current">
                                        <th scope="col" className="py-1 pr-2">
                                            No
                                        </th>
                                        <th scope="col" className="py-1 pr-2">
                                            Pos
                                        </th>
                                        <th scope="col" className="py-1 pr-2">
                                            Ketinggian
                                        </th>
                                        <th scope="col" className="py-1 pr-2">
                                            Dari pos sebelumnya
                                        </th>
                                        <th scope="col" className="py-1">
                                            Waktu dari awal
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {pos.map((p) => (
                                        <tr key={p.id} className="break-inside-avoid border-b border-current align-top">
                                            <td className="py-1 pr-2 tabular-nums">{p.urutan}</td>
                                            <td className="py-1 pr-2">
                                                <span className="font-bold">{p.nama}</span> <span>({p.jenis})</span>
                                            </td>
                                            <td className="py-1 pr-2 tabular-nums">{p.elevasi ?? 'Belum diketahui'}</td>
                                            <td className="py-1 pr-2">{p.antara ?? ''}</td>
                                            <td className="py-1">{p.waktu ?? ''}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <p className="mt-2 text-isi">Jalur ini belum punya pos tercatat.</p>
                    )}
                </section>

                <section aria-labelledby="cetak-aturan" className="break-inside-avoid">
                    <h3 id="cetak-aturan" className="text-bagian">
                        Aturan resmi
                    </h3>
                    {aturan ? (
                        <>
                            <p className="mt-2 text-isi">{aturan.ringkasan}</p>
                            {aturan.catatan ? <p className="mt-1 text-isi">{aturan.catatan}</p> : null}
                            <p className="mt-1 text-kecil">{aturan.dicek ? `Dicek Memount ${aturan.dicek}. Pastikan di pos registrasi.` : 'Tanggal cek tidak tercatat. Pastikan di pos registrasi.'}</p>
                        </>
                    ) : (
                        <p className="mt-2 text-isi">Aturan resmi jalur ini belum tercatat di Memount. Tanyakan di pos registrasi.</p>
                    )}
                </section>

                {pesan ? (
                    <section aria-labelledby="cetak-pesan" className="break-inside-avoid">
                        <h3 id="cetak-pesan" className="text-bagian">
                            Pesan untuk kontak
                        </h3>
                        <p className="mt-2 whitespace-pre-line break-words text-kecil">{pesan}</p>
                    </section>
                ) : null}

                <p className="border-t-2 border-current pt-3 text-kecil">Kertas ini cadangan untuk ponsel, bukan peta navigasi. Ikuti petunjuk petugas di jalur.</p>
            </article>
        </main>
    );
}
```

`.env.example`:

1. Ganti:

```
MAP_ATTRIBUTION="© OpenStreetMap contributors, SRTM | Tampilan: © OpenTopoMap (CC-BY-SA)"

```

dengan:

```
MAP_ATTRIBUTION="© OpenStreetMap contributors, SRTM | Tampilan: © OpenTopoMap (CC-BY-SA)"

# Paket lapangan luring dan alat keselamatan trip (spec v3 bagian 10; ruling W6-R3 dan W6-R4).
# Keduanya dibangun dan diuji, tetapi mati sampai pemilik memutus Q10 dan Q14.
FIELD_PACK_ENABLED=false
FIELD_SAFETY_ENABLED=false

```

`app/Http/Controllers/Trips/TripController.php`:

1. Ganti:

```php
use App\Services\Trips\KepalaTrip;
use App\Services\Trips\RencanaTrip;
```

dengan:

```php
use App\Services\Trips\KepalaTrip;
use App\Services\Trips\PesanRencana;
use App\Services\Trips\RencanaTrip;
```

2. Ganti:

```php
    }

    public function show(Request $request, TripPlan $trip, PermitService $permits, TrailFitService $trailFit, TripSteps $steps, TripLifecycle $lifecycle, TripReminderScheduler $pengingat, KepalaTrip $kepala): Response
    {
```

dengan:

```php
    }

    public function show(Request $request, TripPlan $trip, PermitService $permits, TrailFitService $trailFit, TripSteps $steps, TripLifecycle $lifecycle, TripReminderScheduler $pengingat, KepalaTrip $kepala, PesanRencana $pesan): Response
    {
```

3. Ganti:

```php
            'pengingat' => $pengingat->untukHalaman($trip),
            // Dijelaskan sebelum pendaki mencoba, bukan baru ditolak sesudahnya.
```

dengan:

```php
            'pengingat' => $pengingat->untukHalaman($trip),
            // Pesan rencana untuk kontak dan versi cetak (ruling W6-R4): null sampai pemilik memutus Q14.
            'keselamatan' => config('hiking.field_safety.enabled') && $trip->status->isActive() ? $pesan->untuk($trip, $user) : null,
            // Dijelaskan sebelum pendaki mencoba, bukan baru ditolak sesudahnya.
```

`app/Models/TripPlan.php`:

1. Ganti:

```php
    'user_id', 'trail_id', 'hiking_goal_id', 'name', 'planned_date', 'end_date',
    'start_time', 'participants_count', 'trip_type', 'notes', 'reminders_enabled',
```

dengan:

```php
    'user_id', 'trail_id', 'hiking_goal_id', 'name', 'planned_date', 'end_date',
    'expected_return_at', 'registration_number',
    'start_time', 'participants_count', 'trip_type', 'notes', 'reminders_enabled',
```

2. Ganti:

```php
            'end_date' => 'date',
            'participants_count' => 'integer',
```

dengan:

```php
            'end_date' => 'date',
            'expected_return_at' => 'datetime',
            'participants_count' => 'integer',
```

`app/Services/Trips/HalamanMendaki.php`:

1. Ganti:

```php
    private const MAKS_LAPORAN = 10;

    public function __construct(private readonly CheckpointPaceService $pace) {}

    /**
```

dengan:

```php
    private const MAKS_LAPORAN = 10;

    public function __construct(
        private readonly CheckpointPaceService $pace,
        private readonly KartuDarurat $darurat,
    ) {}

    /**
```

2. Ganti:

```php
            'sumber' => 'daring',
            // Lembar Selesai (DK-50): hasil pendakian dan alasan berbalik, dari enum yang sama
```

dengan:

```php
            'sumber' => 'daring',
            // Kartu darurat (ruling W6-R4): null selama alat keselamatan menunggu Q14.
            'darurat' => config('hiking.field_safety.enabled') ? $this->darurat->untuk($jalur) : null,
            // Lembar Selesai (DK-50): hasil pendakian dan alasan berbalik, dari enum yang sama
```

`bootstrap/app.php`:

1. Ganti:

```php
<?php

use App\Http\Middleware\EnsureUserHasRole;
```

dengan:

```php
<?php

use App\Http\Middleware\EnsureFeatureEnabled;
use App\Http\Middleware\EnsureUserHasRole;
```

2. Ganti:

```php
            'role' => EnsureUserHasRole::class,
        ]);
```

dengan:

```php
            'role' => EnsureUserHasRole::class,
            'fitur' => EnsureFeatureEnabled::class,
        ]);
```

`config/hiking.php`:

1. Ganti:

```php
        'enabled' => (bool) env('FIELD_PACK_ENABLED', false),
    ],
```

dengan:

```php
        'enabled' => (bool) env('FIELD_PACK_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Alat keselamatan trip (spec v3 bagian 10.8; rencana induk W6 butir 8; ruling W6-R4)
    |--------------------------------------------------------------------------
    |
    | Kartu darurat di mode lapangan, versi cetak, dan pesan rencana untuk kontak. Spec v3
    | bagian 15 mengaitkannya dengan Q14 (telaah hukum), jadi dibangun dan diuji tetapi mati
    | sampai pemilik memutus. Selama mati, mode lapangan dan ruang kerja tidak menampilkannya
    | dan rute cetak serta jam kembali menjawab 404.
    |
    */
    'field_safety' => [
        'enabled' => (bool) env('FIELD_SAFETY_ENABLED', false),
    ],
```

`resources/js/components/mendaki/ModeLapangan.tsx`:

1. Ganti:

```tsx
import { teksAkurasi, teksJarak, teksNaik, teksUmur } from './hitung';
import type { DataLapangan } from './types';
```

dengan:

```tsx
import { teksAkurasi, teksJarak, teksNaik, teksUmur } from './hitung';
import { KartuDarurat } from './KartuDarurat';
import type { DataLapangan } from './types';
```

2. Ganti:

```tsx
            ) : null}

            {data.trip.berlangsung ? (
```

dengan:

```tsx
            ) : null}

            {data.darurat ? <KartuDarurat darurat={data.darurat} data={data} posisi={posisi} dilaluiM={mode.dilaluiM} tercapai={mode.tercapai} /> : null}

            {data.trip.berlangsung ? (
```

`resources/js/components/mendaki/hitung.ts`:

1. Ganti:

```ts
const angkaSatuDesimal = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 });

const radian = (derajat: number) => (derajat * Math.PI) / 180;
```

dengan:

```ts
const angkaSatuDesimal = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 });
const angkaKoordinat = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 5, maximumFractionDigits: 5 });

const radian = (derajat: number) => (derajat * Math.PI) / 180;
```

2. Ganti:

```ts
/**
 * Tiba di pos (spec v3 bagian 6.12): otomatis bila jarak ditambah akurasi masih di dalam radius;
```

dengan:

```ts
/**
 * Ketinggian pada satu jarak di profil elevasi, untuk blok lokasi kartu darurat: dari profil,
 * bukan dari ketinggian GPS yang berderau puluhan meter. Null tanpa profil atau tanpa jarak.
 */
export function elevasiPada(profil: TitikProfil[] | null, km: number | null): number | null {
    if (profil === null || profil.length < 2 || km === null) {
        return null;
    }

    return Math.round(tinggiPada(profil, km));
}

/**
 * Koordinat untuk dibacakan kepada petugas (spec v3 bagian 10.2): derajat desimal lima angka,
 * sekitar satu meter, lintang lebih dulu, dengan penanda arah Indonesia.
 */
export function teksKoordinat(lat: number, lng: number): string {
    return `${angkaKoordinat.format(Math.abs(lat))}° ${lat < 0 ? 'LS' : 'LU'}, ${angkaKoordinat.format(Math.abs(lng))}° ${lng < 0 ? 'BB' : 'BT'}`;
}

/**
 * Tiba di pos (spec v3 bagian 6.12): otomatis bila jarak ditambah akurasi masih di dalam radius;
```

`resources/js/components/mendaki/types.ts`:

1. Ganti:

```ts
    sumber: 'daring' | 'paket';
    /** Pilihan lembar Selesai: hasil pendakian dan alasan berbalik di jalur (DK-50). */
```

dengan:

```ts
    sumber: 'daring' | 'paket';
    /** Kartu darurat (App\Services\Trips\KartuDarurat); null selama alat keselamatan menunggu Q14. */
    darurat: DaruratLapangan | null;
    /** Pilihan lembar Selesai: hasil pendakian dan alasan berbalik di jalur (DK-50). */
```

2. Ganti:

```ts
    urls: { tiba: string; jeda: string; lanjut: string; selesai: string; ruangKerja: string; jejak: string | null };
}
```

dengan:

```ts
    urls: { tiba: string; jeda: string; lanjut: string; selesai: string; ruangKerja: string; jejak: string | null };
}

/** Isi kartu darurat dari server; blok lokasinya dihitung di perangkat. */
export interface DaruratLapangan {
    nomor: Array<{ label: string; nomor: string }>;
    pengelola: { nama: string; kontak: string; telepon: string | null } | null;
    tetap: string;
}
```

`resources/js/pages/Trips/Show.tsx`:

1. Ganti:

```tsx
import { KepalaTrip } from '@/components/trips/KepalaTrip';
import { LangkahTrip } from '@/components/trips/LangkahTrip';
```

dengan:

```tsx
import { KepalaTrip } from '@/components/trips/KepalaTrip';
import { KeselamatanTrip, type KeselamatanHalaman } from '@/components/trips/KeselamatanTrip';
import { LangkahTrip } from '@/components/trips/LangkahTrip';
```

2. Ganti:

```tsx
    pengingat: PengingatHalaman;
    kelola: KelolaRencana;
```

dengan:

```tsx
    pengingat: PengingatHalaman;
    /** Rencana untuk kontak dan versi cetak (ruling W6-R4); null selama menunggu Q14. */
    keselamatan: KeselamatanHalaman | null;
    kelola: KelolaRencana;
```

3. Ganti:

```tsx
export default function Show() {
    const { kepala, trip, langkah, fit, penilaian, izin, hasilPendakian, completionStates, alasanBatal, penolakan, pengingat, kelola, flash } = usePage<Props>().props;
    const [memulai, setMemulai] = useState(false);
```

dengan:

```tsx
export default function Show() {
    const { kepala, trip, langkah, fit, penilaian, izin, hasilPendakian, completionStates, alasanBatal, penolakan, pengingat, keselamatan, kelola, flash } = usePage<Props>().props;
    const [memulai, setMemulai] = useState(false);
```

4. Ganti:

```tsx
                        {pengingat.dapatDiatur || pengingat.daftar.length > 0 ? <PengingatTrip tripId={trip.id} pengingat={pengingat} /> : null}

                        {trip.catatan ? (
```

dengan:

```tsx
                        {pengingat.dapatDiatur || pengingat.daftar.length > 0 ? <PengingatTrip tripId={trip.id} pengingat={pengingat} /> : null}

                        {keselamatan ? <KeselamatanTrip keselamatan={keselamatan} /> : null}

                        {trip.catatan ? (
```

`routes/web.php`:

1. Ganti:

```php
use App\Http\Controllers\Trips\TripReminderController;
use Illuminate\Support\Facades\Route;
```

dengan:

```php
use App\Http\Controllers\Trips\TripReminderController;
use App\Http\Controllers\Trips\TripSafetyController;
use Illuminate\Support\Facades\Route;
```

2. Ganti:

```php
    Route::post('trips/{trip}/hike/lanjut', [HikeModeController::class, 'resume'])->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,mode-lapangan')->name('trips.hike.resume');

    /*
```

dengan:

```php
    Route::post('trips/{trip}/hike/lanjut', [HikeModeController::class, 'resume'])->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,mode-lapangan')->name('trips.hike.resume');
    // Alat keselamatan trip (W6, ruling W6-R4): versi cetak dan jam kembali untuk pesan rencana.
    // Menjawab 404 sampai pemilik memutus Q14 (middleware fitur).
    Route::get('trips/{trip}/cetak', [TripSafetyController::class, 'print'])->middleware('fitur:field_safety')->name('trips.print');
    Route::put('trips/{trip}/kembali', [TripSafetyController::class, 'updateReturn'])->middleware(['fitur:field_safety', 'throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,jam-kembali'])->name('trips.return.update');

    /*
```

`tests/Pratinjau/HalamanPratinjauTest.php`:

1. Ganti:

```php
        $this->tulisPratinjau('mode-lapangan', $this->get(self::ASAL.'/trips/'.$trip->id.'/hike'), 'Trips/Hike');
    }
```

dengan:

```php
        $this->tulisPratinjau('mode-lapangan', $this->get(self::ASAL.'/trips/'.$trip->id.'/hike'), 'Trips/Hike');
    }

    /**
     * Kartu darurat di mode lapangan (W6, ruling W6-R4) dengan sakelar alat keselamatan
     * dinyalakan; keadaan pendakiannya sama dengan pratinjau mode-lapangan.
     */
    public function test_kartu_darurat(): void
    {
        config(['hiking.field_safety.enabled' => true]);
        $this->test_mode_lapangan();
        $trip = TripPlan::where('status', TripStatus::IN_PROGRESS->value)->sole();

        $this->tulisPratinjau('kartu-darurat', $this->get(self::ASAL.'/trips/'.$trip->id.'/hike'), 'Trips/Hike');
    }
```

2. Ganti:

```php
    /**
     * Rencanakan trip W4 (acuan 08): Gede via Cibodas, camping dua hari, lima belas hari lagi,
```

dengan:

```php
    /**
     * Alat keselamatan W6 dengan sakelar dinyalakan (ruling W6-R4): ruang kerja dengan rencana
     * untuk kontak berisi jam kembali dan nomor registrasi, lalu versi cetaknya. Bawaannya mati,
     * jadi pratinjau ruang-kerja di atas tetap memotret keadaan bawaan.
     */
    public function test_ruang_kerja_keselamatan(): void
    {
        config(['hiking.field_safety.enabled' => true]);
        $budi = $this->sebagaiBudi();
        $trip = TripPlan::where('user_id', $budi->id)->where('status', TripStatus::PLANNED->value)->sole();
        $trip->update(['expected_return_at' => $trip->end_date->copy()->setTime(15, 0)->shiftTimezone('Asia/Jakarta')->utc(), 'registration_number' => 'GPP-'.$trip->planned_date->format('md').'-0042']);

        $this->tulisPratinjau('ruang-kerja-keselamatan', $this->get(self::ASAL.'/trips/'.$trip->id), 'Trips/Show');
        // Koordinat pos untuk pratinjau saja, seperti mode-lapangan: tanpa itu sketsa rute kosong.
        Checkpoint::where('trail_id', $trip->trail_id)->orderBy('sequence')->get()
            ->each(fn (Checkpoint $pos, int $i) => $pos->setCoordinates(-6.7400 - 0.0080 * $i, 107.0060 - 0.0030 * $i));
        $this->tulisPratinjau('cetak', $this->get(self::ASAL.'/trips/'.$trip->id.'/cetak'), 'Trips/Print');
    }

    /**
     * Rencanakan trip W4 (acuan 08): Gede via Cibodas, camping dua hari, lima belas hari lagi,
```

- [ ] **Step 4: Build dan tipe.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `npx tsc --noEmit`
Expected: `✓ built`; tsc tanpa keluaran galat.

- [ ] **Step 5: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Http/Middleware/EnsureFeatureEnabled.php app/Services/Trips/KartuDarurat.php app/Services/Trips/PesanRencana.php app/Services/Trips/CetakTrip.php app/Http/Controllers/Trips/TripSafetyController.php app/Http/Requests/Trips/UpdateTripReturnRequest.php app/Models/TripPlan.php app/Services/Trips/HalamanMendaki.php app/Http/Controllers/Trips/TripController.php bootstrap/app.php config/hiking.php routes/web.php database/migrations/2026_10_07_000500_add_return_and_registration_to_trip_plans_table.php tests/Feature/EmergencyCardTest.php tests/Feature/TripPlanMessageTest.php tests/Spatial/FieldModeColumnsSchemaTest.php tests/Feature/FieldModeFiguresTest.php tests/Pratinjau/HalamanPratinjauTest.php`, lalu `php artisan test --compact --filter='EmergencyCard|TripPlanMessage|FieldModeFigures'`
Expected: PASS. Pada simulasi rencana: 24 test lulus.

- [ ] **Step 6: Suite, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 2.033 test, 2.031 lulus, 2 dilewati (sebelum tugas: 2.018).

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `php artisan test --compact --testsuite=Pratinjau`
Expected: PASS; halaman baru `kartu-darurat`, `ruang-kerja-keselamatan`, dan `cetak` (sakelar dinyalakan di test
pratinjau saja). Pada simulasi rencana: 27 test lulus.

- [ ] **Step 7: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 8: Manifest paket lapangan dan data dinamis

Spec bagian 10.2 (isi paket, hanya jalur terbit, tanpa GPX komunitas, AI, skor, peserta lain), 10.3 (data dinamis),
10.6 (pemilik saja, `no-store`); induk W6 butir 4 dan 6 (`FieldPackManifestTest`, `FieldPackStalenessTest`); ruling
W6-R3 dan W6-R8; Q9. Manifest `GET /trips/{trip}/paket` mendaftar tiga bagian (`lapangan`, `rencana`, `dinamis`)
dengan ukuran dan SHA-256 bita yang dikirim, versi berupa hash isinya (tanpa waktu unduh, tanpa keadaan sesi), ambang
Q9, dan atribusi. Bagian dinamis membawa status resmi bertanggal `verified_at` dengan waktu turun status Buka
(`hiking.field_pack.open_status_stale_after_hours`, bawaan 24), cuaca untuk tanggal trip atau kalimat belum terbit, dan
paling banyak sepuluh laporan komunitas tiga puluh hari dengan tanggal mendaki. Modul murni `kesegaran.ts` menurunkan
status di perangkat.

**Files:**
- Create: `app/Services/Trips/PaketLapangan.php`, `app/Http/Controllers/Trips/FieldPackController.php`, `resources/js/luring/kesegaran.ts`
- Modify: `config/hiking.php`, `routes/web.php`, `resources/js/lib/bukanHalamanReact.json`
- Test: `tests/Feature/FieldPackManifestTest.php` (baru), `tests/Feature/FieldPackStalenessTest.php` (baru)

**Interfaces:**
- Consumes: `HalamanMendaki::untuk()` (Tugas 3, 5, 7); `CetakTrip::untuk()` (Tugas 7); `OfficialStatusService::snapshotForTrail(Trail): array` (`status`, `status_label`, `source`, `source_type`, `reason`, `verified_at`, `expires_at`); `WeatherService::contextForTrail(Trail): array` (`available`, `reference_area`, `forecast`); `TrailConditionReport` scope `visibleToPublic()`, `dalamJendelaTerbaru()`; `config('hiking.conditions.community_report_limit')`; middleware `fitur` (Tugas 7).
- Produces: `PaketLapangan::BAGIAN`, `bolehDipaket(TripPlan): bool`, `manifest(TripPlan, User): array{versi, trip, bagian, ukuran, ambang: array{bukaJam: int}, atribusi}`, `teksBagian(TripPlan, User, string): string`; bagian `dinamis.status` (`nilai`, `label`, `sumber`, `jenisSumber`, `alasan`, `dicek`, `dicekTeks`, `berlaku`, `turunSejak`), `dinamis.cuaca` (`kalimat`, `baris`), `dinamis.laporan`; rute `trips.pack` (GET `trips/{trip}/paket`) dan `trips.pack.section` (GET `trips/{trip}/paket/{bagian}`), keduanya `private, no-store`, berbatas laju `paket-lapangan`, 404 selama sakelar mati; TypeScript `StatusPaket`, `KalimatStatus`, `kalimatStatusLuring(status, sekarangMs)`.

- [ ] **Step 1: Tulis test yang gagal.**

Buat `tests/Feature/FieldPackManifestTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\HikingSessionStatus;
use App\Enums\OfficialStatusValue;
use App\Enums\TripStatus;
use App\Models\Checkpoint;
use App\Models\HikingSession;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Manifest paket lapangan luring (spec v3 bagian 10.2 dan 10.6; rencana induk W6 butir 4; ruling
 * W6-R3): pemilik saja, jalur terbit saja, setiap bagian dengan ukuran dan SHA-256 yang cocok
 * dengan bita yang dikirim, versi berupa hash isi yang stabil, potret bertanggal verifikasi, dan
 * tanpa skor, keluaran AI, GPX, atau orang lain. Mati sampai pemilik memutus Q10 dan Q14.
 */
class FieldPackManifestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-07 03:00:00', 'UTC'));
        config(['hiking.field_pack.enabled' => true]);
    }

    /**
     * @return array{0: User, 1: TripPlan}
     */
    private function rencana(): array
    {
        $user = User::factory()->create(['name' => 'Budi Santoso']);
        $trail = Trail::factory()->published()->create(['name' => 'Gede via Cibodas']);

        foreach (['Pintu Cibodas', 'Telaga Biru', 'Kandang Badak'] as $i => $nama) {
            Checkpoint::factory()->for($trail)->create(['sequence' => $i + 1, 'name' => $nama, 'elevation_m' => 1400 + $i * 400, 'latitude' => -6.74 - $i * 0.01, 'longitude' => 107.0]);
        }

        $trip = TripPlan::factory()->create(['user_id' => $user->id, 'trail_id' => $trail->id, 'status' => TripStatus::PLANNED->value, 'planned_date' => '2026-10-22']);

        return [$user, $trip->fresh()];
    }

    /**
     * @return array<string, mixed>
     */
    private function manifest(User $user, TripPlan $trip): array
    {
        return $this->actingAs($user)->getJson(route('trips.pack', $trip))->assertOk()->json();
    }

    public function test_the_pack_belongs_to_the_trip_owner_only(): void
    {
        [$user, $trip] = $this->rencana();

        $this->get(route('trips.pack', $trip))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->getJson(route('trips.pack', $trip))->assertForbidden();
        $this->actingAs(User::factory()->create())->getJson(route('trips.pack.section', [$trip, 'rencana']))->assertForbidden();

        $respons = $this->actingAs($user)->getJson(route('trips.pack', $trip))->assertOk();

        $this->assertStringContainsString('no-store', $respons->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', $respons->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $this->actingAs($user)->get(route('trips.pack.section', [$trip, 'rencana']))->headers->get('Cache-Control'));
    }

    public function test_while_the_switch_is_off_the_pack_does_not_exist(): void
    {
        config(['hiking.field_pack.enabled' => false]);
        [$user, $trip] = $this->rencana();

        $this->actingAs($user)->getJson(route('trips.pack', $trip))->assertNotFound();
        $this->actingAs($user)->get(route('trips.pack.section', [$trip, 'lapangan']))->assertNotFound();
        $this->actingAs(User::factory()->create())->getJson(route('trips.pack', $trip))->assertNotFound();
    }

    /**
     * Perangkat hanya menyebut paket "Siap luring" bila setiap bagian cocok dengan manifest, jadi
     * ukuran dan hash di manifest harus sama dengan bita yang benar-benar dikirim.
     */
    public function test_each_section_matches_its_size_and_hash(): void
    {
        [$user, $trip] = $this->rencana();
        $manifest = $this->manifest($user, $trip);

        $this->assertSame(['lapangan', 'rencana', 'dinamis'], array_column($manifest['bagian'], 'nama'));

        foreach ($manifest['bagian'] as $bagian) {
            $isi = $this->actingAs($user)->get($bagian['url'])->assertOk()->assertHeader('Content-Type', 'application/json')->getContent();

            $this->assertSame($bagian['ukuran'], strlen($isi), $bagian['nama']);
            $this->assertSame($bagian['sha256'], hash('sha256', $isi), $bagian['nama']);
        }

        $this->assertSame(substr(hash('sha256', implode('|', array_column($manifest['bagian'], 'sha256'))), 0, 16), $manifest['versi']);
        $this->assertSame(array_sum(array_column($manifest['bagian'], 'ukuran')), $manifest['ukuran']);
        $this->assertSame(24, $manifest['ambang']['bukaJam']);
    }

    /**
     * Versi adalah hash isi: tidak berubah karena waktu unduh atau karena pendaki tiba di pos,
     * dan berubah ketika datanya berubah.
     */
    public function test_the_version_is_stable_until_the_data_changes(): void
    {
        [$user, $trip] = $this->rencana();
        $versi = $this->manifest($user, $trip)['versi'];

        $this->travel(3)->hours();
        $this->assertSame($versi, $this->manifest($user, $trip)['versi']);

        $trip->update(['status' => TripStatus::IN_PROGRESS->value]);
        HikingSession::create(['trip_plan_id' => $trip->id, 'user_id' => $user->id, 'status' => HikingSessionStatus::ACTIVE->value, 'started_at' => now(), 'reached_checkpoint_sequence' => 2]);
        $this->assertSame($versi, $this->manifest($user, $trip)['versi']);

        TrailConditionReport::factory()->approved()->create(['trail_id' => $trip->trail_id, 'hike_date' => '2026-10-06']);

        $this->assertNotSame($versi, $this->manifest($user, $trip)['versi']);
    }

    /**
     * R-008 dan R-022: jalur yang tidak terbit atau diarsipkan tidak dipaket, dan trip yang sudah
     * selesai atau dibatalkan tidak membutuhkan paket.
     */
    public function test_only_active_trips_on_published_trails_are_packed(): void
    {
        [$user, $trip] = $this->rencana();

        $trip->trail->forceFill(['is_published' => false])->saveQuietly();
        $this->actingAs($user)->getJson(route('trips.pack', $trip))->assertNotFound();

        $trip->trail->forceFill(['is_published' => true, 'archived_at' => now()])->saveQuietly();
        $this->actingAs($user)->getJson(route('trips.pack', $trip))->assertNotFound();

        $trip->trail->forceFill(['archived_at' => null])->saveQuietly();
        $trip->update(['status' => TripStatus::COMPLETED->value]);
        $this->actingAs($user)->getJson(route('trips.pack', $trip))->assertNotFound();
    }

    /**
     * Paket minimal (spec v3 bagian 10.6): tanpa skor atau persen, keluaran AI, GPX, nama
     * pelapor, atau rencana orang lain.
     */
    public function test_the_pack_carries_no_score_ai_gpx_or_other_people(): void
    {
        [$user, $trip] = $this->rencana();
        $orangLain = User::factory()->create(['name' => 'Sari Pelapor']);
        TrailConditionReport::factory()->approved()->create(['trail_id' => $trip->trail_id, 'user_id' => $orangLain->id, 'hike_date' => '2026-10-05']);
        TripPlan::factory()->create(['user_id' => $orangLain->id, 'trail_id' => $trip->trail_id, 'name' => 'Rencana Sari']);

        $semua = '';

        foreach ($this->manifest($user, $trip)['bagian'] as $bagian) {
            $semua .= $this->actingAs($user)->get($bagian['url'])->getContent();
        }

        foreach (['"skor"', '"score"', '"persen"', '"kecocokan"', '"fit"', 'gpx', 'Sari Pelapor', 'Rencana Sari', '%'] as $terlarang) {
            $this->assertStringNotContainsString($terlarang, $semua);
        }

        $this->assertStringContainsString('"sumber":"paket"', $semua);
    }

    /**
     * Waktu potret status adalah verified_at, bukan waktu unduh (spec v3 bagian 10.3).
     */
    public function test_the_status_snapshot_is_dated_by_its_verification(): void
    {
        [$user, $trip] = $this->rencana();
        $trip->trail->officialStatuses()->update(['status' => OfficialStatusValue::OPEN->value, 'verified_at' => Carbon::parse('2026-10-06 01:30:00', 'UTC')]);

        $dinamis = json_decode($this->actingAs($user)->get(route('trips.pack.section', [$trip, 'dinamis']))->getContent(), true);

        $this->assertSame('OPEN', $dinamis['status']['nilai']);
        $this->assertSame('2026-10-06T01:30:00+00:00', $dinamis['status']['dicek']);
        $this->assertSame('6 Okt 2026 08.30 WIB', $dinamis['status']['dicekTeks']);
    }
}
```

Buat `tests/Feature/FieldPackStalenessTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\OfficialStatusValue;
use App\Enums\TripStatus;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\TripPlan;
use App\Models\User;
use App\Models\WeatherSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * Data dinamis di paket lapangan (spec v3 bagian 10.3; rencana induk W6 butir 6; ruling W6-R8).
 * Server menghitung waktu turun status Buka (Q9, bawaan 24 jam dari verified_at) dan
 * mengirimnya di paket; perangkat (resources/js/luring/kesegaran.ts) menurunkan Buka menjadi
 * "belum dapat dipastikan" sesudah waktu itu, tanpa sinyal. Cuaca yang belum terbit disebut
 * begitu, dan laporan komunitas paling banyak sepuluh dalam tiga puluh hari.
 */
class FieldPackStalenessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-07 03:00:00', 'UTC'));
        config(['hiking.field_pack.enabled' => true]);
    }

    /**
     * @return array{0: User, 1: TripPlan}
     */
    private function rencana(): array
    {
        $user = User::factory()->create();
        $trail = Trail::factory()->published()->create(['weather_adm4_code' => '32.03.13.2001', 'weather_reference_area' => 'Cimacan']);
        $trip = TripPlan::factory()->create(['user_id' => $user->id, 'trail_id' => $trail->id, 'status' => TripStatus::PLANNED->value, 'planned_date' => '2026-10-08']);

        return [$user, $trip->fresh()];
    }

    /**
     * @return array<string, mixed>
     */
    private function dinamis(User $user, TripPlan $trip): array
    {
        return json_decode($this->actingAs($user)->get(route('trips.pack.section', [$trip, 'dinamis']))->assertOk()->getContent(), true);
    }

    public function test_an_open_status_carries_the_time_it_stops_being_trusted(): void
    {
        [$user, $trip] = $this->rencana();
        $trip->trail->officialStatuses()->update(['status' => OfficialStatusValue::OPEN->value, 'verified_at' => Carbon::parse('2026-10-06 01:30:00', 'UTC')]);

        $this->assertSame('2026-10-07T01:30:00+00:00', $this->dinamis($user, $trip)['status']['turunSejak']);

        config(['hiking.field_pack.open_status_stale_after_hours' => 6]);

        $this->assertSame('2026-10-06T07:30:00+00:00', $this->dinamis($user, $trip)['status']['turunSejak']);
    }

    /**
     * Tutup dan Dibatasi tidak pernah turun: tetap tampil dengan umurnya. Belum diketahui tetap
     * Belum diketahui (R-006).
     */
    public function test_closed_and_unknown_statuses_never_expire_into_something_else(): void
    {
        [$user, $trip] = $this->rencana();
        $trip->trail->officialStatuses()->update(['status' => OfficialStatusValue::CLOSED->value, 'reason' => 'Pemulihan ekosistem']);

        $status = $this->dinamis($user, $trip)['status'];
        $this->assertSame(['CLOSED', null, 'Pemulihan ekosistem'], [$status['nilai'], $status['turunSejak'], $status['alasan']]);

        $trip->trail->officialStatuses()->update(['status' => OfficialStatusValue::UNKNOWN->value]);
        $this->assertSame(['UNKNOWN', null], [$this->dinamis($user, $trip)['status']['nilai'], $this->dinamis($user, $trip)['status']['turunSejak']]);
    }

    /**
     * Perangkat menurunkan Buka sesudah waktunya, menulis kalimat R-012 sebelum itu, dan tidak
     * pernah menyebut Tutup atau Belum diketahui sebagai keadaan terkini.
     */
    public function test_the_device_downgrades_an_open_status_after_its_threshold(): void
    {
        $buka = ['nilai' => 'OPEN', 'label' => 'Buka', 'sumber' => 'Balai Besar TNGGP', 'jenisSumber' => 'OFFICIAL', 'alasan' => null, 'dicek' => '2026-10-06T01:30:00+00:00', 'dicekTeks' => '6 Okt 2026 08.30 WIB', 'berlaku' => null, 'turunSejak' => '2026-10-07T01:30:00+00:00'];
        $tutup = [...$buka, 'nilai' => 'CLOSED', 'label' => 'Tutup', 'alasan' => 'Pemulihan ekosistem', 'berlaku' => '31 Okt 2026 WIB', 'turunSejak' => null];
        $tanpaCek = [...$buka, 'dicek' => null, 'dicekTeks' => null, 'turunSejak' => null];

        [$sebelum, $sesudah, $tertutup, $tidakTercatat, $belum] = $this->kesegaran([
            [$buka, Carbon::parse('2026-10-07 01:29:00', 'UTC')->getTimestampMs()],
            [$buka, Carbon::parse('2026-10-07 01:30:00', 'UTC')->getTimestampMs()],
            [$tutup, Carbon::parse('2026-12-01 00:00:00', 'UTC')->getTimestampMs()],
            [$tanpaCek, Carbon::parse('2026-10-06 02:00:00', 'UTC')->getTimestampMs()],
            [[...$buka, 'nilai' => 'UNKNOWN', 'label' => 'Belum diketahui', 'turunSejak' => null], 0],
        ]);

        $this->assertSame(['judul' => 'Terakhir diketahui: Buka', 'kalimat' => 'Dicek 6 Okt 2026 08.30 WIB, Balai Besar TNGGP. Status resmi menjelaskan ketentuan pengelola, bukan jaminan keselamatan.', 'turun' => false], $sebelum);
        $this->assertSame(['judul' => 'Status belum dapat dipastikan', 'kalimat' => 'Status belum dapat dipastikan sejak 6 Okt 2026 08.30 WIB. Pastikan di pos registrasi.', 'turun' => true], $sesudah);
        $this->assertSame(['judul' => 'Terakhir diketahui: Tutup', 'kalimat' => 'Dicek 6 Okt 2026 08.30 WIB, Balai Besar TNGGP. Alasan: Pemulihan ekosistem. Berlaku sampai 31 Okt 2026 WIB.', 'turun' => false], $tertutup);
        $this->assertTrue($tidakTercatat['turun']);
        $this->assertSame('Belum diketahui', $belum['judul']);
    }

    public function test_weather_that_is_not_yet_published_is_named_as_such(): void
    {
        [$user, $trip] = $this->rencana();

        $this->assertSame('Data cuaca BMKG tidak tersedia saat paket dibuat.', $this->dinamis($user, $trip)['cuaca']['kalimat']);

        WeatherSnapshot::create(['adm4_code' => '32.03.13.2001', 'reference_area' => 'Cimacan', 'forecast_at' => Carbon::parse('2026-10-07 06:00:00', 'UTC'), 'local_datetime' => '2026-10-07 13:00:00', 'weather_description' => 'Berawan', 'temperature_c' => 19, 'analysis_date' => Carbon::parse('2026-10-07 00:00:00', 'UTC'), 'source' => 'BMKG', 'fetched_at' => now()]);

        $this->assertSame('Prakiraan BMKG untuk 8 Okt belum terbit saat paket dibuat.', $this->dinamis($user, $trip)['cuaca']['kalimat']);

        WeatherSnapshot::create(['adm4_code' => '32.03.13.2001', 'reference_area' => 'Cimacan', 'forecast_at' => Carbon::parse('2026-10-08 00:00:00', 'UTC'), 'local_datetime' => '2026-10-08 07:00:00', 'weather_description' => 'Hujan ringan', 'temperature_c' => 17, 'analysis_date' => Carbon::parse('2026-10-07 00:00:00', 'UTC'), 'source' => 'BMKG', 'fetched_at' => now()]);

        $cuaca = $this->dinamis($user, $trip)['cuaca'];
        $this->assertSame('Prakiraan area Cimacan (BMKG), analisis 7 Okt 2026 07.00 WIB.', $cuaca['kalimat']);
        $this->assertSame([['waktu' => '07.00', 'cuaca' => 'Hujan ringan', 'suhu' => '17 °C']], $cuaca['baris']);
    }

    /**
     * Laporan komunitas: yang lolos moderasi saja, paling banyak sepuluh dalam tiga puluh hari,
     * bertanggal mendaki (bukan "kemarin" yang basi esok hari), tanpa nama pelapor.
     */
    public function test_community_reports_are_dated_and_bounded(): void
    {
        [$user, $trip] = $this->rencana();
        TrailConditionReport::factory()->count(12)->approved()->create(['trail_id' => $trip->trail_id, 'hike_date' => '2026-10-05']);
        TrailConditionReport::factory()->approved()->create(['trail_id' => $trip->trail_id, 'hike_date' => '2026-08-01']);
        TrailConditionReport::factory()->create(['trail_id' => $trip->trail_id, 'hike_date' => '2026-10-06']);

        $laporan = $this->dinamis($user, $trip)['laporan'];

        $this->assertCount(10, $laporan);
        $this->assertSame('mendaki 5 Okt', $laporan[0]['waktu']);
        $this->assertSame(['segmen', 'kondisi', 'catatan', 'waktu'], array_keys($laporan[0]));
    }

    /**
     * Memanggil kalimatStatusLuring di Node dan mengembalikan hasilnya berurutan.
     *
     * @param  array<int, array{0: array<string, mixed>, 1: int}>  $kasus
     * @return array<int, array{judul: string, kalimat: string, turun: bool}>
     */
    private function kesegaran(array $kasus): array
    {
        $skrip = 'import { pathToFileURL } from "node:url";'
            .'const modul = await import(pathToFileURL('.json_encode(resource_path('js/luring/kesegaran.ts')).').href);'
            .'const kasus = '.json_encode($kasus).';'
            .'process.stdout.write(JSON.stringify(kasus.map(([status, ms]) => modul.kalimatStatusLuring(status, ms))));';

        $proses = new Process(['node', '--experimental-strip-types', '--input-type=module', '-'], base_path(), null, $skrip, 60);
        $proses->run();

        $this->assertTrue($proses->isSuccessful(), 'Node gagal menjalankan kesegaran.ts: '.mb_substr($proses->getErrorOutput(), 0, 600));

        return json_decode($proses->getOutput(), true, flags: JSON_THROW_ON_ERROR);
    }
}
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='FieldPackManifest|FieldPackStaleness|InertiaLinkRegistry'`
Expected: FAIL. Pada simulasi rencana: 13 test, 1 lulus, 1 gagal, 11 error. Yang merah: tujuh test `FieldPackManifestTest` dan lima test `FieldPackStalenessTest` (rute `trips.pack` dan `trips.pack.section` serta `kesegaran.ts` belum ada). `InertiaLinkRegistryTest` sudah lulus, karena pola rute paket baru diperlukan sesudah rutenya ada.

- [ ] **Step 3: Tulis implementasinya.** Kelas dengan
`DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:class --no-interaction Services/Trips/PaketLapangan`
dan controller dengan `... make:controller --no-interaction Trips/FieldPackController`, lalu isinya diganti persis.

Buat `app/Http/Controllers/Trips/FieldPackController.php`:

```php
<?php

namespace App\Http\Controllers\Trips;

use App\Http\Controllers\Controller;
use App\Models\TripPlan;
use App\Services\Trips\PaketLapangan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Manifest dan bagian paket lapangan luring (spec v3 bagian 10.2 dan 10.6; rencana induk W6
 * butir 4; ruling W6-R3). Pemilik trip saja (Policy yang sama dengan trip), hanya jalur terbit,
 * dan `Cache-Control: private, no-store`: rencana pribadi tidak boleh tersimpan di cache mana
 * pun selain toko paket pendaki sendiri. Rutenya menjawab 404 selama sakelar
 * hiking.field_pack.enabled mati (middleware fitur, Q10 dan Q14).
 */
class FieldPackController extends Controller
{
    private const TANPA_SIMPAN = ['Cache-Control' => 'private, no-store'];

    public function manifest(Request $request, TripPlan $trip, PaketLapangan $paket): JsonResponse
    {
        $this->authorize('view', $trip);
        abort_unless($paket->bolehDipaket($trip), 404);

        return response()->json($paket->manifest($trip, $request->user()), 200, self::TANPA_SIMPAN);
    }

    public function section(Request $request, TripPlan $trip, string $bagian, PaketLapangan $paket): Response
    {
        $this->authorize('view', $trip);
        abort_unless($paket->bolehDipaket($trip), 404);

        return response($paket->teksBagian($trip, $request->user(), $bagian), 200, ['Content-Type' => 'application/json', ...self::TANPA_SIMPAN]);
    }
}
```

Buat `app/Services/Trips/PaketLapangan.php`:

```php
<?php

namespace App\Services\Trips;

use App\Enums\OfficialStatusValue;
use App\Enums\PreparationStatus;
use App\Models\TrailConditionReport;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Models\User;
use App\Models\WeatherSnapshot;
use App\Services\OfficialStatusService;
use App\Services\PermitService;
use App\Services\WeatherService;
use App\Support\Timezone;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * Paket lapangan luring satu trip (spec v3 bagian 10.2 dan 10.3; rencana induk W6 butir 4 dan 6;
 * ruling W6-R3 dan W6-R8).
 *
 * Tiga bagian, masing-masing teks JSON yang di-hash dan dikirim apa adanya, sehingga perangkat
 * dapat membuktikan setiap bagian utuh sebelum menyebut paket "Siap luring":
 * - lapangan: data mode lapangan yang stabil (HalamanMendaki tanpa laporan dan tanpa keadaan
 *   sesi, yang disimpan perangkat sendiri);
 * - rencana: rencana pribadi pendaki, aturan resmi bertanggal cek, dan daftar persiapan;
 * - dinamis: potret status resmi, cuaca, dan laporan komunitas dengan waktu pengetahuannya.
 *
 * Versi paket adalah hash isi bagian, jadi stabil selama datanya tidak berubah: tidak ada waktu
 * unduh di dalamnya. Waktu pada label adalah waktu Memount terakhir mencocokkan data dengan
 * sumbernya (verified_at, tanggal analisis BMKG, tanggal mendaki), bukan waktu unduh (R-013).
 * Hanya jalur terbit (R-008, R-022); tanpa GPX komunitas, keluaran AI, skor, nama peserta lain,
 * atau jejak orang lain.
 */
final class PaketLapangan
{
    public const BAGIAN = ['lapangan', 'rencana', 'dinamis'];

    public function __construct(
        private readonly HalamanMendaki $mendaki,
        private readonly CetakTrip $cetak,
        private readonly OfficialStatusService $status,
        private readonly WeatherService $cuaca,
        private readonly PermitService $izin,
    ) {}

    /**
     * Paket hanya untuk trip yang masih berjalan atau akan datang di jalur yang terbit.
     */
    public function bolehDipaket(TripPlan $trip): bool
    {
        $trip->loadMissing('trail');

        return $trip->status->isActive() && $trip->trail->is_published && $trip->trail->archived_at === null;
    }

    /**
     * @return array<string, mixed>
     */
    public function manifest(TripPlan $trip, User $pengguna): array
    {
        $bagian = [];

        foreach (self::BAGIAN as $nama) {
            $teks = $this->teksBagian($trip, $pengguna, $nama);
            $bagian[] = [
                'nama' => $nama,
                'url' => route('trips.pack.section', ['trip' => $trip, 'bagian' => $nama]),
                'ukuran' => strlen($teks),
                'sha256' => hash('sha256', $teks),
            ];
        }

        return [
            'versi' => substr(hash('sha256', implode('|', array_column($bagian, 'sha256'))), 0, 16),
            'trip' => ['id' => $trip->id, 'nama' => $trip->name, 'jalur' => $trip->trail->name],
            'bagian' => $bagian,
            'ukuran' => array_sum(array_column($bagian, 'ukuran')),
            'ambang' => ['bukaJam' => (int) config('hiking.field_pack.open_status_stale_after_hours')],
            'atribusi' => [
                'Peta dasar tidak dibawa paket; garis jalur dan pos dari data rute Memount.',
                'Prakiraan cuaca: BMKG.',
            ],
        ];
    }

    /**
     * Teks JSON satu bagian; yang di-hash sama dengan yang dikirim.
     */
    public function teksBagian(TripPlan $trip, User $pengguna, string $nama): string
    {
        $isi = match ($nama) {
            'lapangan' => $this->lapangan($trip, $pengguna),
            'rencana' => $this->rencana($trip, $pengguna),
            'dinamis' => $this->dinamis($trip),
            default => throw new InvalidArgumentException("Bagian paket {$nama} tidak dikenal."),
        };

        return json_encode($isi, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /**
     * Data mode lapangan tanpa laporan dan tanpa keadaan sesi. Keadaan sesi (mulai, jeda, pos
     * tercapai) disimpan perangkat dari mode lapangan daring, supaya versi paket tidak berubah
     * setiap pendaki tiba di pos.
     *
     * @return array<string, mixed>
     */
    private function lapangan(TripPlan $trip, User $pengguna): array
    {
        $data = $this->mendaki->untuk($trip, $pengguna);
        unset($data['laporan']);

        return [
            ...$data,
            'trip' => [...$data['trip'], 'berlangsung' => false, 'mulai' => null, 'jedaSejak' => null, 'jedaDetik' => 0],
            'tercapai' => 0,
            'rekamJejak' => false,
            'sesiId' => null,
            'sumber' => 'paket',
            'urls' => [...$data['urls'], 'jejak' => null],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function rencana(TripPlan $trip, User $pengguna): array
    {
        $trip->loadMissing('preparationItems');
        $cetak = $this->cetak->untuk($trip, $pengguna);
        $butir = $trip->preparationItems->sortBy('id')->values();

        return [
            'rencana' => $cetak['rencana'],
            'aturan' => $cetak['aturan'],
            'persiapan' => [
                'dikonfirmasi' => $butir->filter(fn (TripPreparationItem $b) => $b->status === PreparationStatus::CONFIRMED)->count(),
                'jumlah' => $butir->count(),
                'butir' => $butir->map(fn (TripPreparationItem $b) => ['nama' => $b->label, 'kritis' => (bool) $b->is_critical, 'keadaan' => $b->status->label()])->all(),
            ],
        ];
    }

    /**
     * Potret data dinamis (spec v3 bagian 10.3). Status Buka membawa waktu turunnya (Q9,
     * hiking.field_pack.open_status_stale_after_hours dihitung dari verified_at), dan perangkat
     * menurunkannya menjadi "belum dapat dipastikan" sesudah waktu itu. Tutup dan Dibatasi tetap
     * tampil dengan umurnya; Belum diketahui tetap Belum diketahui (R-006).
     *
     * @return array<string, mixed>
     */
    private function dinamis(TripPlan $trip): array
    {
        $jalur = $trip->trail;
        $zona = Timezone::forTrail($jalur);
        $potret = $this->status->snapshotForTrail($jalur);
        $dicek = $potret['verified_at'] === null ? null : Carbon::parse($potret['verified_at']);
        $nilai = OfficialStatusValue::from($potret['status']);

        return [
            'status' => [
                'nilai' => $nilai->value,
                'label' => $potret['status_label'],
                'sumber' => $potret['source'],
                'jenisSumber' => $potret['source_type'],
                'alasan' => $potret['reason'],
                'dicek' => $dicek?->utc()->toIso8601String(),
                'dicekTeks' => Timezone::display($dicek, $zona, 'j M Y H.i'),
                'berlaku' => Timezone::display($potret['expires_at'] === null ? null : Carbon::parse($potret['expires_at']), $zona, 'j M Y'),
                'turunSejak' => $nilai === OfficialStatusValue::OPEN && $dicek !== null
                    ? $dicek->copy()->addHours((int) config('hiking.field_pack.open_status_stale_after_hours'))->utc()->toIso8601String()
                    : null,
            ],
            'cuaca' => $this->cuacaTrip($trip, $zona),
            'laporan' => TrailConditionReport::query()
                ->where('trail_id', $jalur->id)
                ->visibleToPublic()
                ->dalamJendelaTerbaru()
                ->orderByDesc('hike_date')
                ->orderByDesc('id')
                ->limit((int) config('hiking.conditions.community_report_limit'))
                ->get(['id', 'trail_segment_id', 'condition_tags', 'note', 'hike_date'])
                ->map(fn (TrailConditionReport $l) => [
                    'segmen' => $l->trail_segment_id,
                    'kondisi' => implode(', ', array_map(fn ($tag) => $tag->label(), $l->tags())),
                    'catatan' => $l->note,
                    'waktu' => 'mendaki '.$l->hike_date->translatedFormat('j M'),
                ])
                ->all(),
        ];
    }

    /**
     * Prakiraan BMKG untuk tanggal trip saat paket dibuat, atau kalimat bahwa prakiraannya belum
     * terbit. Tidak pernah cuaca karangan (R-013).
     *
     * @return array{kalimat: string, baris: array<int, array{waktu: string, cuaca: string, suhu: string}>}
     */
    private function cuacaTrip(TripPlan $trip, string $zona): array
    {
        $konteks = $this->cuaca->contextForTrail($trip->trail);
        $tanggal = $trip->planned_date->toDateString();
        $baris = array_values(array_filter(
            $konteks['forecast'],
            fn (WeatherSnapshot $s) => $s->forecast_at->copy()->setTimezone($zona)->toDateString() === $tanggal
        ));

        if (! $konteks['available']) {
            return ['kalimat' => 'Data cuaca BMKG tidak tersedia saat paket dibuat.', 'baris' => []];
        }

        if ($baris === []) {
            return ['kalimat' => 'Prakiraan BMKG untuk '.$trip->planned_date->translatedFormat('j M').' belum terbit saat paket dibuat.', 'baris' => []];
        }

        return [
            'kalimat' => 'Prakiraan area '.($konteks['reference_area'] ?? 'tidak dicatat').' (BMKG), analisis '
                .Timezone::display($baris[0]->analysis_date, $zona, 'j M Y H.i').'.',
            'baris' => array_map(fn (WeatherSnapshot $s) => [
                'waktu' => $s->forecast_at->copy()->setTimezone($zona)->format('H.i'),
                'cuaca' => $s->weather_description ?? 'Tidak dicatat',
                'suhu' => $s->temperature_c === null ? 'Tidak dicatat' : number_format((float) $s->temperature_c, 0, ',', '.').' °C',
            ], $baris),
        ];
    }
}
```

Buat `resources/js/luring/kesegaran.ts`:

```ts
/**
 * Data dinamis di paket lapangan (spec v3 bagian 10.3; rencana induk W6 butir 6; ruling W6-R8).
 * Semuanya "terakhir diketahui" dengan waktunya, tidak pernah tampil sebagai keadaan terkini
 * (R-013). Status Buka turun menjadi "belum dapat dipastikan" sesudah waktu yang dihitung server
 * (Q9); Tutup dan Dibatasi tetap tampil dengan umurnya; Belum diketahui tetap Belum diketahui
 * (R-006). Buka tidak pernah berarti aman (R-012).
 *
 * Modul ini sengaja tanpa impor: FieldPackStalenessTest menjalankannya di Node dari PHPUnit.
 */

export interface StatusPaket {
    nilai: 'OPEN' | 'CLOSED' | 'RESTRICTED' | 'UNKNOWN';
    label: string;
    sumber: string | null;
    jenisSumber: string | null;
    alasan: string | null;
    /** verified_at, ISO 8601 UTC; null bila tidak tercatat. */
    dicek: string | null;
    dicekTeks: string | null;
    berlaku: string | null;
    /** Waktu status Buka berhenti dapat dipercaya, ISO 8601 UTC; null selain Buka. */
    turunSejak: string | null;
}

export interface KalimatStatus {
    judul: string;
    kalimat: string;
    /** Buka yang sudah melewati ambangnya: tampil sebagai belum dapat dipastikan. */
    turun: boolean;
}

const R012 = 'Status resmi menjelaskan ketentuan pengelola, bukan jaminan keselamatan.';

export function kalimatStatusLuring(status: StatusPaket, sekarangMs: number): KalimatStatus {
    const dicek = status.dicekTeks ? `dicek ${status.dicekTeks}` : 'tanggal cek tidak tercatat';
    const sumber = status.sumber ? `, ${status.sumber}` : '';

    if (status.nilai === 'UNKNOWN') {
        return { judul: 'Belum diketahui', kalimat: 'Status resmi jalur ini belum diketahui saat paket dibuat. Pastikan di pos registrasi.', turun: false };
    }

    if (status.nilai === 'OPEN') {
        if (status.turunSejak === null || sekarangMs >= Date.parse(status.turunSejak)) {
            return {
                judul: 'Status belum dapat dipastikan',
                kalimat: status.dicekTeks
                    ? `Status belum dapat dipastikan sejak ${status.dicekTeks}. Pastikan di pos registrasi.`
                    : 'Status belum dapat dipastikan: tanggal cek tidak tercatat. Pastikan di pos registrasi.',
                turun: true,
            };
        }

        return { judul: `Terakhir diketahui: ${status.label}`, kalimat: `${capital(dicek)}${sumber}. ${R012}`, turun: false };
    }

    const berlaku = status.berlaku ? ` Berlaku sampai ${status.berlaku}.` : '';
    const alasan = status.alasan ? ` Alasan: ${status.alasan}.` : '';

    return { judul: `Terakhir diketahui: ${status.label}`, kalimat: `${capital(dicek)}${sumber}.${alasan}${berlaku}`, turun: false };
}

function capital(teks: string): string {
    return teks.charAt(0).toUpperCase() + teks.slice(1);
}
```

`config/hiking.php`:

1. Ganti:

```php
        'enabled' => (bool) env('FIELD_PACK_ENABLED', false),
    ],
```

dengan:

```php
        'enabled' => (bool) env('FIELD_PACK_ENABLED', false),

        // Q9 (keputusan pemilik, ruling W6-R8): status Buka di paket turun menjadi "belum dapat
        // dipastikan" sesudah sekian jam dari verified_at. Usul spec v3 bagian 15 Q9: 24 jam,
        // "diuji dulu"; angkanya sama dengan ambang usang cuaca (conditions.weather_aging_hours).
        // Dihitung server dan dikirim di paket.
        'open_status_stale_after_hours' => (int) env('FIELD_PACK_OPEN_STALE_HOURS', 24),
    ],
```

`resources/js/lib/bukanHalamanReact.json`:

1. Ganti:

```json
        "^/up$",
        "^/b/[^/]+$"
    ]
```

dengan:

```json
        "^/up$",
        "^/b/[^/]+$",
        "^/trips/[^/]+/paket(/|$)"
    ]
```

`routes/web.php`:

1. Ganti:

```php
use App\Http\Controllers\Trails\TrailIndexController;
use App\Http\Controllers\Trips\HikeModeController;
```

dengan:

```php
use App\Http\Controllers\Trails\TrailIndexController;
use App\Http\Controllers\Trips\FieldPackController;
use App\Http\Controllers\Trips\HikeModeController;
```

2. Ganti:

```php
    Route::put('trips/{trip}/kembali', [TripSafetyController::class, 'updateReturn'])->middleware(['fitur:field_safety', 'throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,jam-kembali'])->name('trips.return.update');

    /*
```

dengan:

```php
    Route::put('trips/{trip}/kembali', [TripSafetyController::class, 'updateReturn'])->middleware(['fitur:field_safety', 'throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,jam-kembali'])->name('trips.return.update');
    // Paket lapangan luring (W6, ruling W6-R3): manifest dan tiga bagiannya, dibaca halaman yang
    // mengunduh. Menjawab 404 sampai pemilik memutus Q10 dan Q14 (middleware fitur).
    Route::get('trips/{trip}/paket', [FieldPackController::class, 'manifest'])->middleware(['fitur:field_pack', 'throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,paket-lapangan'])->name('trips.pack');
    Route::get('trips/{trip}/paket/{bagian}', [FieldPackController::class, 'section'])->whereIn('bagian', ['lapangan', 'rencana', 'dinamis'])->middleware(['fitur:field_pack', 'throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,paket-lapangan'])->name('trips.pack.section');

    /*
```

- [ ] **Step 4: Build dan tipe.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `npx tsc --noEmit`
Expected: `✓ built`; tsc tanpa keluaran galat.

- [ ] **Step 5: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Services/Trips/PaketLapangan.php app/Http/Controllers/Trips/FieldPackController.php config/hiking.php routes/web.php tests/Feature/FieldPackManifestTest.php tests/Feature/FieldPackStalenessTest.php`, lalu `php artisan test --compact --filter='FieldPackManifest|FieldPackStaleness|InertiaLinkRegistry'`
Expected: PASS. Pada simulasi rencana: 13 test lulus.

- [ ] **Step 6: Suite dan format.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 2.045 test, 2.043 lulus, 2 dilewati (sebelum tugas: 2.033).

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Pratinjau tidak dijalankan: tidak ada perubahan layar.

- [ ] **Step 7: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 9: Service worker dan shell luring

Spec bagian 10.4; induk W6 butir 3 (`ProgressiveWebAppTest` dengan `test_activate_never_deletes_pack_caches` dan
`test_the_offline_shell_never_serves_server_content`); ruling W6-R9 dan W6-R16. Service worker ditulis ulang tangan:
`activate` hanya membuang cache `shell-*` lama, dokumen tetap jaringan lebih dulu dengan `/offline` sebagai cadangan,
dan hanya aset berversi (`/build/`, `/icons/`, `/fonts/`) yang disimpan; respons Inertia, JSON, manifest paket, dan foto
laporan selalu dari jaringan. `/offline` menjadi shell luring: selama paket mati tetap teks pengakuan seperti sebelum
W6, sesudah dibuka memuat entri Vite `resources/js/luring/shell.tsx` yang membaca paket "Siap luring" dari IndexedDB
per pengguna dan membuka halaman `Trips/Hike` yang sama dengan sumber `'paket'`, tanpa peta dasar. Daftar simpanan
pemasangan (`AsetLuring`) ikut sakelar. Label data dinamis "terakhir diketahui" memakai ikon jam baru di registri
Phosphor, garis putus-putus, dan katanya.

**Files:**
- Create: `app/Support/AsetLuring.php`, `resources/js/luring/simpan.ts`, `resources/js/luring/rakit.ts`, `resources/js/luring/DaftarPaket.tsx`, `resources/js/luring/shell.tsx`
- Modify: `resources/views/sw.blade.php` (ditulis ulang utuh), `resources/views/offline.blade.php`, `vite.config.js`, `app/Services/Trips/PaketLapangan.php`, `resources/js/components/ui/ikon/phosphor.ts`
- Test: `tests/Feature/ProgressiveWebAppTest.php`, `tests/Feature/OfflineShellTest.php` (baru), `tests/Pratinjau/HalamanPratinjauTest.php`

**Interfaces:**
- Consumes: manifest Vite `public/build/manifest.json` (`file`, `css`, `imports`, `dynamicImports`); `PaketLapangan` (Tugas 8); `HalamanMendaki` (bagian `lapangan`); `kalimatStatusLuring()` (Tugas 8); `pakaiPenyimpanan()`, `PenyimpananAntrean` (Tugas 3); halaman `Trips/Hike` (Tugas 3); `createInertiaApp({ id, page, resolve, setup })` dari `@inertiajs/react` 3.7.1; `PenjagaDataContoh::sedangDisajikan()`.
- Produces: `AsetLuring::ENTRI_SHELL`, `AsetLuring::daftar(array $manifest, bool $paketAktif): list<string>`; konstanta SW `SHELL` (`shell-<8 heks>`) dan `AWALAN_SHELL`; entri Vite kedua `resources/js/luring/shell.tsx`; `simpan.ts`: `AWALAN_BASIS_DATA` (`memount-luring-`), `KUNCI_PERANGKAT` (`memount-perangkat`), tipe `NamaBagian`, `BagianManifest`, `ManifestPaket`, `RekamanBagian`, `RekamanPaket`, `RekamanSesi`, fungsi `namaBasisData`, `kunciTersimpan`, `simpanKunci`, `lupakanKunci`, `buka`, `tulisBagian`, `bacaBagian`, `tulisPaket`, `semuaPaket`, `hapusPaket`, `tulisSesi`, `bacaSesi`, `penyimpananAntrean`, `hapusBasisData`, `sha256Hex`; `rakit.ts`: `rakitDataLapangan(lapangan, dinamis, sesi)`, `sesiDari(data, sekarang)`; komponen `DaftarPaket({ paket, sekarangMs, onBuka })`, `PaketDiPerangkat`; bagian `lapangan` paket membawa `dataContoh`; ikon `clock`.

- [ ] **Step 1: Tulis test yang gagal.** `ProgressiveWebAppTest` mendapat tiga test baru yang menjalankan service
worker di Node dengan `caches` tiruan; test lama tetap.

Buat `tests/Feature/OfflineShellTest.php`:

```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * Shell luring (spec v3 bagian 10.4; rencana induk W6 butir 3; ruling W6-R9): halaman /offline
 * yang disimpan service worker, dan mode lapangan yang dirakit di perangkat dari paket.
 */
class OfflineShellTest extends TestCase
{
    /**
     * Selama paket mati, halaman luring hanya teks pengakuan seperti sebelum W6. Sesudah dibuka,
     * halaman yang sama memuat entri shell dan wadah untuk daftar paket dan mode lapangan.
     */
    public function test_the_offline_page_loads_the_shell_only_when_the_pack_is_open(): void
    {
        $mati = $this->get('/offline')->assertOk()->getContent();

        $this->assertStringNotContainsString('shell', $mati);
        $this->assertStringContainsString('id="paket-luring"', $mati);

        config(['hiking.field_pack.enabled' => true]);
        $hidup = $this->get('/offline')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('#/build/assets/shell-[^"]+\.js#', $hidup);
        $this->assertStringContainsString('<div id="app"></div>', $hidup);
    }

    /**
     * Keadaan sesi berasal dari rekaman mode lapangan daring terakhir di perangkat; tanpa rekaman
     * itu pendakian tampil belum dimulai, bukan berjalan sejak waktu tebakan (R-007).
     */
    public function test_the_field_data_merges_the_last_known_session(): void
    {
        $lapangan = [
            'trip' => ['id' => 7, 'nama' => 'Gede akhir pekan', 'jalur' => 'Gede via Cibodas', 'gunung' => 'Gunung Gede', 'berlangsung' => false, 'mulai' => null, 'jedaSejak' => null, 'jedaDetik' => 0],
            'tercapai' => 0, 'sesiId' => null, 'rekamJejak' => false, 'sumber' => 'paket', 'laporan' => [],
            'urls' => ['tiba' => '/trips/7/hike/tiba', 'jejak' => null],
        ];
        $dinamis = ['laporan' => [['segmen' => 3, 'kondisi' => 'Licin', 'catatan' => null, 'waktu' => 'mendaki 5 Okt']]];
        $sesi = ['tripId' => 7, 'trip' => ['berlangsung' => true, 'mulai' => '2026-10-22T21:00:00Z', 'jedaSejak' => null, 'jedaDetik' => 600], 'tercapai' => 2, 'sesiId' => 41, 'rekamJejak' => true, 'jejak' => '/hike/41/track', 'diperbarui' => '2026-10-22T23:10:00Z'];

        [$tanpaSesi, $denganSesi] = $this->rakit([[$lapangan, $dinamis, null], [$lapangan, $dinamis, $sesi]]);

        $this->assertSame([false, 0, null, 'mendaki 5 Okt', 'paket'], [$tanpaSesi['trip']['berlangsung'], $tanpaSesi['tercapai'], $tanpaSesi['urls']['jejak'], $tanpaSesi['laporan'][0]['waktu'], $tanpaSesi['sumber']]);
        $this->assertSame([true, '2026-10-22T21:00:00Z', 600, 2, 41, true, '/hike/41/track', 'Gede via Cibodas'], [
            $denganSesi['trip']['berlangsung'], $denganSesi['trip']['mulai'], $denganSesi['trip']['jedaDetik'], $denganSesi['tercapai'],
            $denganSesi['sesiId'], $denganSesi['rekamJejak'], $denganSesi['urls']['jejak'], $denganSesi['trip']['jalur'],
        ]);
    }

    /**
     * Basis data paket per pengguna (spec v3 bagian 10.6) dan tanpa token di dalamnya.
     */
    public function test_the_pack_store_is_per_user_and_holds_no_tokens(): void
    {
        $simpan = File::get(resource_path('js/luring/simpan.ts'));

        $this->assertStringContainsString("export const AWALAN_BASIS_DATA = 'memount-luring-';", $simpan);
        $this->assertStringContainsString('return `${AWALAN_BASIS_DATA}${kunci}`;', $simpan);

        foreach (['XSRF', 'document.cookie'] as $terlarang) {
            $this->assertStringNotContainsString($terlarang, $simpan);
        }
    }

    /**
     * @param  array<int, array{0: array<string, mixed>, 1: array<string, mixed>, 2: array<string, mixed>|null}>  $kasus
     * @return array<int, array<string, mixed>>
     */
    private function rakit(array $kasus): array
    {
        $skrip = 'import { pathToFileURL } from "node:url";'
            .'const modul = await import(pathToFileURL('.json_encode(resource_path('js/luring/rakit.ts')).').href);'
            .'const kasus = '.json_encode($kasus).';'
            .'process.stdout.write(JSON.stringify(kasus.map(([l, d, s]) => modul.rakitDataLapangan(l, d, s))));';

        $proses = new Process(['node', '--experimental-strip-types', '--input-type=module', '-'], base_path(), null, $skrip, 60);
        $proses->run();

        $this->assertTrue($proses->isSuccessful(), 'Node gagal menjalankan rakit.ts: '.mb_substr($proses->getErrorOutput(), 0, 600));

        return json_decode($proses->getOutput(), true, flags: JSON_THROW_ON_ERROR);
    }
}
```

`tests/Feature/ProgressiveWebAppTest.php`:

1. Ganti:

```php
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
```

dengan:

```php
use App\Models\User;
use App\Support\AsetLuring;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Tests\TestCase;
```

2. Ganti:

```php
    }
}
```

dengan:

```php
    }

    /**
     * Regresi W6 (spec v3 bagian 10.4, rencana induk W6 butir 3): versi baru service worker hanya
     * membuang shell lama. Paket lapangan pendaki (pack-*) dan cache lain milik halaman tidak
     * boleh hilang diam-diam karena deploy. Dijalankan di Node dengan caches tiruan.
     */
    public function test_activate_never_deletes_pack_caches(): void
    {
        $sw = $this->get('/sw.js')->assertOk()->getContent();
        preg_match("/const SHELL = '(shell-[0-9a-f]{8})';/", $sw, $versi);

        $this->assertNotEmpty($versi, 'Nama cache shell tidak terbaca.');

        $hasil = $this->jalankanServiceWorker($sw, ['shell-0a1b2c3d', $versi[1], 'pack-12-9f8e7d6c5b4a3210', 'pack-12-ubin', 'memount-lain'], []);

        $this->assertSame(['shell-0a1b2c3d'], $hasil['dihapus']);
    }

    /**
     * Shell luring tidak pernah menyajikan isi server dari cache: dokumen yang gagal dimuat
     * jatuh ke /offline, sedangkan respons Inertia, JSON, manifest paket, dan foto laporan tidak
     * dijawab service worker sama sekali (selalu jaringan). Hanya aset berversi yang disimpan.
     */
    public function test_the_offline_shell_never_serves_server_content(): void
    {
        $sw = $this->get('/sw.js')->assertOk()->getContent();

        $hasil = $this->jalankanServiceWorker($sw, [], [
            ['http://localhost/trips/1', 'document'],
            ['http://localhost/trips/1', ''],
            ['http://localhost/trips/1/paket', ''],
            ['http://localhost/trips/1/paket/dinamis', ''],
            ['http://localhost/reports/5/photo', 'image'],
            ['http://localhost/build/assets/app.js', 'script'],
            ['https://tile.opentopomap.org/10/1/1.png', 'image'],
        ]);

        $this->assertSame([
            'http://localhost/trips/1 document' => 'cache:/offline',
            'http://localhost/trips/1 ' => 'jaringan',
            'http://localhost/trips/1/paket ' => 'jaringan',
            'http://localhost/trips/1/paket/dinamis ' => 'jaringan',
            'http://localhost/reports/5/photo image' => 'jaringan',
            'http://localhost/build/assets/app.js script' => 'cache atau jaringan',
            'https://tile.opentopomap.org/10/1/1.png image' => 'jaringan',
        ], $hasil['respons']);

        // Halaman luring sama untuk tamu dan pengguna masuk: tidak ada data pengguna di dalamnya.
        $tamu = $this->get('/offline')->assertOk()->getContent();
        $masuk = $this->actingAs(User::factory()->create(['name' => 'Nama Pendaki Rahasia']))->get('/offline')->assertOk()->getContent();

        $this->assertSame($tamu, $masuk);
        $this->assertStringNotContainsString('data-page', $masuk);

        // Shell membaca paket dari perangkat saja: tidak meminta apa pun ke server.
        $shell = File::get(resource_path('js/luring/shell.tsx'));

        foreach (['fetch(', 'router.', 'axios', 'XMLHttpRequest'] as $terlarang) {
            $this->assertStringNotContainsString($terlarang, $shell);
        }

        $this->assertStringContainsString('filter((p) => p.siap)', $shell);
    }

    /**
     * Shell luring dan asetnya disimpan saat pemasangan hanya bila paket lapangan dibuka
     * (ruling W6-R3): selama mati, daftar simpanan sama kecilnya dengan sebelum W6.
     */
    public function test_the_shell_is_precached_only_when_the_field_pack_is_open(): void
    {
        $daftar = fn () => json_decode(preg_match('/const SHELL_FILES = (\[.*?\]);/s', $this->get('/sw.js')->getContent(), $cocok) === 1 ? $cocok[1] : '[]', true);
        $manifest = json_decode(File::get(public_path('build/manifest.json')), true);
        $shell = '/build/'.$manifest[AsetLuring::ENTRI_SHELL]['file'];

        $mati = $daftar();
        $this->assertContains('/offline', $mati);
        $this->assertContains('/build/'.$manifest['resources/css/app.css']['file'], $mati);
        $this->assertNotContains($shell, $mati);

        config(['hiking.field_pack.enabled' => true]);
        $hidup = $daftar();

        $this->assertContains($shell, $hidup);
        $this->assertContains('/build/'.$manifest['node_modules/maplibre-gl/dist/maplibre-gl.js']['file'], $hidup);
        $this->assertContains('/fonts/plus-jakarta-sans/plus-jakarta-sans-latin-wght-normal.woff2', $hidup);
        $this->assertStringContainsString("'resources/js/luring/shell.tsx'", File::get(resource_path('views/offline.blade.php')));
    }

    /**
     * Menjalankan service worker di Node dengan self, caches, dan fetch tiruan (fetch selalu
     * gagal, seperti luring), memicu activate, lalu fetch untuk setiap permintaan.
     *
     * @param  list<string>  $kunci  nama cache yang sudah ada
     * @param  list<array{0: string, 1: string}>  $permintaan  URL dan destination
     * @return array{dihapus: list<string>, respons: array<string, string>}
     */
    private function jalankanServiceWorker(string $sw, array $kunci, array $permintaan): array
    {
        $skrip = 'const kejadian = {}; const dihapus = []; const respons = {};'
            .'globalThis.self = { location: { origin: "http://localhost" }, addEventListener: (n, f) => { kejadian[n] = f; }, skipWaiting: async () => {}, clients: { claim: async () => {} } };'
            .'globalThis.caches = { keys: async () => '.json_encode($kunci).', delete: async (k) => { dihapus.push(k); return true; }, open: async () => ({ addAll: async () => {}, put: async () => {} }), match: async (r) => (typeof r === "string" ? "cache:" + r : null) };'
            .'globalThis.fetch = async () => { throw new TypeError("luring"); };'
            .'new Function('.json_encode($sw).')();'
            .'const tunggu = []; kejadian.activate({ waitUntil: (p) => tunggu.push(p) }); await Promise.all(tunggu);'
            .'for (const [url, tujuan] of '.json_encode($permintaan).') {'
            .'let jawaban = null; kejadian.fetch({ request: { method: "GET", url, destination: tujuan }, respondWith: (p) => { jawaban = p; } });'
            .'respons[url + " " + tujuan] = jawaban === null ? "jaringan" : await jawaban.then((r) => (r === null ? "kosong" : r), () => "cache atau jaringan");'
            .'}'
            .'process.stdout.write(JSON.stringify({ dihapus, respons }));';

        $proses = new Process(['node', '--input-type=module', '-'], base_path(), null, $skrip, 60);
        $proses->run();

        $this->assertTrue($proses->isSuccessful(), 'Node gagal menjalankan service worker: '.mb_substr($proses->getErrorOutput(), 0, 600));

        return json_decode($proses->getOutput(), true, flags: JSON_THROW_ON_ERROR);
    }
}
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='ProgressiveWebApp|OfflineShell'`
Expected: FAIL. Pada simulasi rencana: 13 test, 7 lulus, 4 gagal, 2 error. Yang merah: tiga test `ProgressiveWebAppTest` (`activate` masih menghapus cache selain shell, service worker masih menyimpan isi server, `AsetLuring` belum ada) dan tiga test `OfflineShellTest` (halaman luring belum memuat shell; `rakit.ts` dan `simpan.ts` belum ada).

- [ ] **Step 3: Tulis implementasinya.** Kelas dengan
`DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:class --no-interaction Support/AsetLuring`, lalu
isinya diganti persis. Path ikon `clock` disalin dari `@phosphor-icons/core` 2.1.1 (`assets/regular/clock.svg` dan
`assets/bold/clock-bold.svg`) seperti tertulis di blok kode; tidak ada paket yang dipasang.

Buat `app/Support/AsetLuring.php`:

```php
<?php

namespace App\Support;

/**
 * Daftar berkas shell yang disimpan service worker saat dipasang (spec v3 bagian 10.4; rencana
 * induk W6 butir 3; ruling W6-R9).
 *
 * Selalu: halaman luring, ikon, dan app.css, seperti sebelum W6. Bila paket lapangan dibuka
 * (hiking.field_pack.enabled), ditambah entri shell luring beserta seluruh impornya, impor
 * dinamisnya (MapLibre dan perekam jejak), dan huruf, supaya mode lapangan dari paket dapat
 * terbuka tanpa satu pun permintaan jaringan. Hanya aset berversi; isi dari server tidak
 * pernah masuk daftar ini.
 */
final class AsetLuring
{
    public const ENTRI_SHELL = 'resources/js/luring/shell.tsx';

    private const HURUF = [
        '/fonts/plus-jakarta-sans/plus-jakarta-sans-latin-wght-normal.woff2',
        '/fonts/plus-jakarta-sans/plus-jakarta-sans-latin-ext-wght-normal.woff2',
    ];

    /**
     * @param  array<string, array<string, mixed>>  $manifest  isi public/build/manifest.json
     * @return list<string>
     */
    public static function daftar(array $manifest, bool $paketAktif): array
    {
        $berkas = ['/offline', '/icons/app-192.png', '/icons/app-512.png'];

        if (isset($manifest['resources/css/app.css']['file'])) {
            $berkas[] = '/build/'.$manifest['resources/css/app.css']['file'];
        }

        if (! $paketAktif || ! isset($manifest[self::ENTRI_SHELL])) {
            return $berkas;
        }

        $antre = [self::ENTRI_SHELL];
        $dikunjungi = [];

        while ($antre !== []) {
            $kunci = array_shift($antre);

            if (isset($dikunjungi[$kunci]) || ! isset($manifest[$kunci])) {
                continue;
            }

            $dikunjungi[$kunci] = true;
            $entri = $manifest[$kunci];
            $berkas[] = '/build/'.$entri['file'];

            foreach ($entri['css'] ?? [] as $css) {
                $berkas[] = '/build/'.$css;
            }

            array_push($antre, ...($entri['imports'] ?? []), ...($entri['dynamicImports'] ?? []));
        }

        return array_values(array_unique([...$berkas, ...self::HURUF]));
    }
}
```

Buat `resources/js/luring/DaftarPaket.tsx`:

```tsx
import { fokusTerlihat, umpanTekan } from '@/components/ui/gaya';
import { Icon } from '@/components/ui/Icon';
import { kelas } from '@/lib/kelas';
import { kalimatStatusLuring, type StatusPaket } from './kesegaran';
import type { RekamanPaket } from './simpan';

export interface PaketDiPerangkat {
    rekaman: RekamanPaket;
    rencana: { rencana: { tanggal: string; kembali: string | null } };
    dinamis: { status: StatusPaket; cuaca: { kalimat: string }; laporan: unknown[] };
}

interface DaftarPaketProps {
    paket: PaketDiPerangkat[];
    sekarangMs: number;
    onBuka: (paket: PaketDiPerangkat) => void;
}

const tanggal = new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });

/**
 * Paket lapangan di perangkat ini (spec v3 bagian 10.2, 10.3, dan 10.8), tampil di shell luring.
 * Setiap paket membawa potret dinamisnya sebagai "terakhir diketahui" dengan ikon jam dan garis
 * putus-putus, bukan warna saja, dan status Buka yang sudah melewati ambangnya tampil sebagai
 * belum dapat dipastikan. Bingkai sistem kedua ditulis sekali di sini, tanpa janji keselamatan.
 */
export function DaftarPaket({ paket, sekarangMs, onBuka }: DaftarPaketProps) {
    return (
        <section aria-labelledby="judul-paket-luring" className="mt-8 space-y-4">
            <h2 id="judul-paket-luring" className="text-bagian text-primary">
                Paket lapangan di perangkat ini
            </h2>
            <p className="text-isi text-primary">Paket luring membantu mengikuti jalur resmi. Bawa cadangan: peta cetak atau perangkat lain.</p>
            <ul className="space-y-4">
                {paket.map((p) => {
                    const status = kalimatStatusLuring(p.dinamis.status, sekarangMs);

                    return (
                        <li key={`${p.rekaman.tripId}-${p.rekaman.versi}`} className="rounded-kartu border border-subtle bg-surface p-4">
                            <p className="text-subjudul text-primary">{p.rekaman.manifest.trip.nama}</p>
                            <p className="text-kecil text-secondary">
                                {p.rekaman.manifest.trip.jalur} · {p.rencana.rencana.tanggal}
                            </p>
                            {p.rencana.rencana.kembali ? <p className="text-kecil text-secondary">Perkiraan kembali {p.rencana.rencana.kembali}</p> : null}

                            <div className={kelas('mt-3 rounded-kecil border-[1.5px] border-dashed p-3', status.turun ? 'border-warn-600 bg-warn-50' : 'border-control bg-surface-sunken')}>
                                <p className={kelas('flex items-center gap-2 text-isi font-bold', status.turun ? 'text-warn-900' : 'text-primary')}>
                                    <Icon name="clock" size={20} />
                                    {status.judul}
                                </p>
                                <p className={kelas('mt-1 text-kecil', status.turun ? 'text-warn-900' : 'text-secondary')}>{status.kalimat}</p>
                                <p className="mt-2 text-kecil text-secondary">{p.dinamis.cuaca.kalimat}</p>
                                <p className="mt-1 text-kecil text-secondary">
                                    {p.dinamis.laporan.length === 0
                                        ? 'Tidak ada laporan komunitas 30 hari terakhir saat paket dibuat.'
                                        : `${p.dinamis.laporan.length} laporan komunitas saat paket dibuat; tampil per segmen di mode lapangan.`}
                                </p>
                            </div>

                            <p className="mt-2 text-meta text-secondary">
                                Diperiksa utuh di perangkat ini {p.rekaman.diverifikasi ? tanggal.format(new Date(p.rekaman.diverifikasi)) : 'tanpa waktu tercatat'}.
                            </p>

                            <button
                                type="button"
                                onClick={() => onBuka(p)}
                                className={kelas('mt-3 inline-flex min-h-[66px] w-full items-center justify-center rounded-tombol bg-brand-700 px-4 py-4 text-bagian font-bold text-on-brand', fokusTerlihat, umpanTekan)}
                            >
                                Buka mode lapangan
                            </button>
                        </li>
                    );
                })}
            </ul>
        </section>
    );
}
```

Buat `resources/js/luring/rakit.ts`:

```ts
import type { DataLapangan, LaporanLapangan } from '@/components/mendaki/types';
import type { RekamanSesi } from './simpan';

/**
 * Data mode lapangan dari paket (spec v3 bagian 10.4: satu pohon komponen, dua sumber data).
 * Bagian lapangan membawa data jalur yang stabil tanpa keadaan sesi; keadaan sesi datang dari
 * rekaman terakhir mode lapangan daring di perangkat ini, bila ada. Tanpa rekaman itu pendakian
 * tampil belum dimulai, bukan berjalan sejak waktu tebakan (R-007). Laporan komunitas datang
 * dari potret dinamis dengan tanggal mendakinya.
 *
 * Tanpa impor nilai: OfflineShellTest menjalankannya di Node dari PHPUnit.
 */
export function rakitDataLapangan(lapangan: DataLapangan, dinamis: { laporan: LaporanLapangan[] }, sesi: RekamanSesi | null): DataLapangan {
    return {
        ...lapangan,
        sumber: 'paket',
        laporan: dinamis.laporan,
        trip: sesi ? { ...lapangan.trip, ...sesi.trip } : lapangan.trip,
        tercapai: sesi?.tercapai ?? lapangan.tercapai,
        sesiId: sesi?.sesiId ?? lapangan.sesiId,
        rekamJejak: sesi?.rekamJejak ?? lapangan.rekamJejak,
        urls: { ...lapangan.urls, jejak: sesi?.jejak ?? lapangan.urls.jejak },
    };
}

/** Rekaman sesi dari data mode lapangan daring, untuk disimpan ke paket trip yang sama. */
export function sesiDari(data: DataLapangan, sekarang: Date): RekamanSesi {
    return {
        tripId: data.trip.id,
        trip: { berlangsung: data.trip.berlangsung, mulai: data.trip.mulai, jedaSejak: data.trip.jedaSejak, jedaDetik: data.trip.jedaDetik },
        tercapai: data.tercapai,
        sesiId: data.sesiId,
        rekamJejak: data.rekamJejak,
        jejak: data.urls.jejak,
        diperbarui: sekarang.toISOString(),
    };
}
```

Buat `resources/js/luring/shell.tsx`:

```tsx
import '../dasar.js';
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { pakaiPenyimpanan } from '@/components/mendaki/antrean';
import type { DataLapangan } from '@/components/mendaki/types';
import Hike from '@/pages/Trips/Hike';
import { DaftarPaket, type PaketDiPerangkat } from './DaftarPaket';
import { rakitDataLapangan } from './rakit';
import { bacaBagian, bacaSesi, buka, kunciTersimpan, penyimpananAntrean, semuaPaket, type RekamanBagian, type RekamanPaket } from './simpan';

/*
 * Shell luring (spec v3 bagian 10.4; rencana induk W6 butir 3; ruling W6-R9). Dibuka service
 * worker dari cache ketika dokumen gagal dimuat, jadi isinya tidak boleh berasal dari server:
 * yang dibaca hanya paket yang diunduh pendaki sendiri, dari IndexedDB perangkat ini, dan
 * hanya paket yang sudah lolos pemeriksaan utuh ("Siap luring"). Tanpa paket, halaman luring
 * tetap teks pengakuan dari Blade dan modul ini tidak menggambar apa pun.
 *
 * Mode lapangan dari paket adalah halaman Trips/Hike yang sama dengan versi daring, dirender
 * dengan halaman Inertia yang dirakit di perangkat (sumber 'paket'), tanpa peta dasar.
 */

const TANPA_PETA_DASAR = { styleUrl: null, rasterTiles: [], attribution: 'data rute Memount', maxZoom: 17 };

function teksBagian(bagian: RekamanBagian[], nama: RekamanBagian['nama']): string {
    const ada = bagian.find((b) => b.nama === nama);

    if (!ada) {
        throw new Error(`Bagian ${nama} tidak ada di paket.`);
    }

    return ada.teks;
}

/** Satu versi per trip: yang terakhir lolos pemeriksaan. */
function terbaru(paket: RekamanPaket[]): RekamanPaket[] {
    const per = new Map<number, RekamanPaket>();

    for (const p of paket.filter((p) => p.siap)) {
        const ada = per.get(p.tripId);

        if (!ada || (p.diverifikasi ?? '') > (ada.diverifikasi ?? '')) {
            per.set(p.tripId, p);
        }
    }

    return [...per.values()];
}

async function bukaLapangan(db: IDBDatabase, paket: RekamanPaket): Promise<void> {
    const bagian = await bacaBagian(db, paket.tripId, paket.versi);
    const lapangan = JSON.parse(teksBagian(bagian, 'lapangan')) as DataLapangan & { dataContoh?: boolean };
    const data = rakitDataLapangan(lapangan, JSON.parse(teksBagian(bagian, 'dinamis')), await bacaSesi(db, paket.tripId));

    // Aksi yang dicatat tanpa sinyal bertahan di perangkat walau halaman ini ditutup.
    pakaiPenyimpanan(penyimpananAntrean(db));
    // Teks pengakuan luring diganti mode lapangan; elemennya dibuang karena kelas flex-nya
    // mengalahkan atribut hidden.
    document.getElementById('luring-pesan')?.remove();

    await createInertiaApp({
        id: 'app',
        page: {
            component: 'Trips/Hike',
            url: '/offline',
            version: null,
            rescuedProps: [],
            flash: {},
            rememberedState: {},
            props: { ...data, title: `Mode lapangan dari paket: ${data.trip.jalur}`, demo: lapangan.dataContoh ?? false, peta: TANPA_PETA_DASAR, errors: {} },
        },
        resolve: () => Hike,
        setup: ({ el, App, props }) => {
            if (el) {
                createRoot(el).render(<App {...props} />);
            }
        },
    });
}

async function mulai(): Promise<void> {
    const kunci = kunciTersimpan();
    const wadah = document.getElementById('paket-luring');

    if (!kunci || !wadah) {
        return;
    }

    const db = await buka(kunci);
    const paket: PaketDiPerangkat[] = await Promise.all(
        terbaru(await semuaPaket(db)).map(async (rekaman) => {
            const bagian = await bacaBagian(db, rekaman.tripId, rekaman.versi);

            return { rekaman, rencana: JSON.parse(teksBagian(bagian, 'rencana')), dinamis: JSON.parse(teksBagian(bagian, 'dinamis')) };
        }),
    );

    if (paket.length > 0) {
        createRoot(wadah).render(<DaftarPaket paket={paket} sekarangMs={Date.now()} onBuka={(p) => void bukaLapangan(db, p.rekaman)} />);
    }
}

mulai().catch(() => {
    // Basis data yang tidak dapat dibuka (mode privat, ruang dihapus peramban) berarti tidak ada
    // paket di perangkat ini; halaman luring tetap teks pengakuannya.
});
```

Buat `resources/js/luring/simpan.ts`:

```ts
import type { AksiAntrean, PenyimpananAntrean } from '@/components/mendaki/antrean';
import type { DataLapangan } from '@/components/mendaki/types';

/**
 * Penyimpanan paket lapangan di perangkat (spec v3 bagian 10.4 dan 10.6; rencana induk W6 butir
 * 5 dan 7; ruling W6-R9 dan W6-R10).
 *
 * Satu basis data IndexedDB per pengguna, `memount-luring-<kunci>`, dengan kunci HMAC id pengguna
 * dari server (App\Support\KunciPerangkat), sehingga akun lain di peramban yang sama tidak
 * membuka paket ini. Kunci disimpan di localStorage hanya supaya shell luring, yang tidak dapat
 * bertanya ke server, tahu basis data mana yang dibuka; keluar akun menghapus keduanya.
 *
 * Isi: bagian paket persis seperti bita dari server beserta SHA-256-nya, rekaman paket per
 * versi, keadaan sesi mode lapangan terakhir, dan antrean aksi. Tanpa token dan tanpa data orang
 * lain (spec v3 bagian 10.6).
 */

export const AWALAN_BASIS_DATA = 'memount-luring-';
export const KUNCI_PERANGKAT = 'memount-perangkat';

const VERSI_SKEMA = 1;
const BAGIAN = 'bagian';
const PAKET = 'paket';
const SESI = 'sesi';
const ANTREAN = 'antrean';

export type NamaBagian = 'lapangan' | 'rencana' | 'dinamis';

export interface BagianManifest {
    nama: NamaBagian;
    url: string;
    ukuran: number;
    sha256: string;
}

export interface ManifestPaket {
    versi: string;
    trip: { id: number; nama: string; jalur: string };
    bagian: BagianManifest[];
    ukuran: number;
    ambang: { bukaJam: number };
    atribusi: string[];
}

export interface RekamanBagian {
    tripId: number;
    versi: string;
    nama: NamaBagian;
    teks: string;
    sha256: string;
}

export interface RekamanPaket {
    tripId: number;
    versi: string;
    manifest: ManifestPaket;
    /** Benar hanya setelah setiap bagian dibaca ulang dari penyimpanan dan cocok dengan manifest. */
    siap: boolean;
    diverifikasi: string | null;
}

/** Keadaan sesi terakhir yang dilihat mode lapangan daring; paket sendiri tidak membawanya. */
export interface RekamanSesi {
    tripId: number;
    trip: Pick<DataLapangan['trip'], 'berlangsung' | 'mulai' | 'jedaSejak' | 'jedaDetik'>;
    tercapai: number;
    sesiId: number | null;
    rekamJejak: boolean;
    jejak: string | null;
    diperbarui: string;
}

export function namaBasisData(kunci: string): string {
    return `${AWALAN_BASIS_DATA}${kunci}`;
}

/** localStorage dapat menolak (mode privat, situs diblokir); kegagalannya berarti tanpa paket. */
export function kunciTersimpan(): string | null {
    try {
        return window.localStorage.getItem(KUNCI_PERANGKAT);
    } catch {
        return null;
    }
}

export function simpanKunci(kunci: string): void {
    try {
        window.localStorage.setItem(KUNCI_PERANGKAT, kunci);
    } catch {
        // Tanpa localStorage shell luring tidak menemukan paket; halaman daring tetap berjalan.
    }
}

export function lupakanKunci(): void {
    try {
        window.localStorage.removeItem(KUNCI_PERANGKAT);
    } catch {
        // Tidak ada yang perlu dilupakan.
    }
}

const terbuka = new Map<string, Promise<IDBDatabase>>();

export function buka(kunci: string): Promise<IDBDatabase> {
    const ada = terbuka.get(kunci);

    if (ada) {
        return ada;
    }

    const janji = new Promise<IDBDatabase>((selesai, gagal) => {
        const permintaan = indexedDB.open(namaBasisData(kunci), VERSI_SKEMA);

        permintaan.onupgradeneeded = () => {
            const db = permintaan.result;
            db.createObjectStore(BAGIAN, { keyPath: ['tripId', 'versi', 'nama'] });
            db.createObjectStore(PAKET, { keyPath: ['tripId', 'versi'] });
            db.createObjectStore(SESI, { keyPath: 'tripId' });
            db.createObjectStore(ANTREAN);
        };
        permintaan.onsuccess = () => selesai(permintaan.result);
        permintaan.onerror = () => gagal(permintaan.error);
    });

    terbuka.set(kunci, janji);

    return janji;
}

function minta<T>(permintaan: IDBRequest<T>): Promise<T> {
    return new Promise((selesai, gagal) => {
        permintaan.onsuccess = () => selesai(permintaan.result);
        permintaan.onerror = () => gagal(permintaan.error);
    });
}

function toko(db: IDBDatabase, nama: string, mode: IDBTransactionMode): IDBObjectStore {
    return db.transaction(nama, mode).objectStore(nama);
}

export async function tulisBagian(db: IDBDatabase, rekaman: RekamanBagian): Promise<void> {
    await minta(toko(db, BAGIAN, 'readwrite').put(rekaman));
}

export async function bacaBagian(db: IDBDatabase, tripId: number, versi: string): Promise<RekamanBagian[]> {
    const rentang = IDBKeyRange.bound([tripId, versi, ''], [tripId, versi, '￿']);

    return minta(toko(db, BAGIAN, 'readonly').getAll(rentang) as IDBRequest<RekamanBagian[]>);
}

export async function tulisPaket(db: IDBDatabase, rekaman: RekamanPaket): Promise<void> {
    await minta(toko(db, PAKET, 'readwrite').put(rekaman));
}

export async function semuaPaket(db: IDBDatabase): Promise<RekamanPaket[]> {
    return minta(toko(db, PAKET, 'readonly').getAll() as IDBRequest<RekamanPaket[]>);
}

/** Menghapus satu versi paket beserta bagiannya; dipakai setelah versi baru lolos verifikasi. */
export async function hapusPaket(db: IDBDatabase, tripId: number, versi: string): Promise<void> {
    const transaksi = db.transaction([PAKET, BAGIAN], 'readwrite');
    transaksi.objectStore(PAKET).delete([tripId, versi]);
    transaksi.objectStore(BAGIAN).delete(IDBKeyRange.bound([tripId, versi, ''], [tripId, versi, '￿']));

    await new Promise<void>((selesai, gagal) => {
        transaksi.oncomplete = () => selesai();
        transaksi.onerror = () => gagal(transaksi.error);
    });
}

export async function tulisSesi(db: IDBDatabase, rekaman: RekamanSesi): Promise<void> {
    await minta(toko(db, SESI, 'readwrite').put(rekaman));
}

export async function bacaSesi(db: IDBDatabase, tripId: number): Promise<RekamanSesi | null> {
    return (await minta(toko(db, SESI, 'readonly').get(tripId) as IDBRequest<RekamanSesi | undefined>)) ?? null;
}

/** Antrean aksi mode lapangan yang bertahan bila halaman luring ditutup (pakaiPenyimpanan). */
export function penyimpananAntrean(db: IDBDatabase): PenyimpananAntrean {
    return {
        muat: async () => (await minta(toko(db, ANTREAN, 'readonly').get('aksi') as IDBRequest<AksiAntrean[] | undefined>)) ?? [],
        simpan: async (aksi) => {
            await minta(toko(db, ANTREAN, 'readwrite').put(aksi, 'aksi'));
        },
    };
}

/** Menghapus seluruh basis data paket satu pengguna (keluar akun, ganti akun). */
export function hapusBasisData(kunci: string): Promise<void> {
    terbuka.delete(kunci);

    return new Promise((selesai, gagal) => {
        const permintaan = indexedDB.deleteDatabase(namaBasisData(kunci));
        permintaan.onsuccess = () => selesai();
        permintaan.onerror = () => gagal(permintaan.error);
        // Tab lain yang masih memegang basis data menunda penghapusan, bukan menggagalkannya.
        permintaan.onblocked = () => selesai();
    });
}

/** SHA-256 heksadesimal dari teks UTF-8, sama dengan hash('sha256', ...) di server. */
export async function sha256Hex(teks: string): Promise<string> {
    const hasil = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(teks));

    return Array.from(new Uint8Array(hasil), (b) => b.toString(16).padStart(2, '0')).join('');
}
```

`app/Services/Trips/PaketLapangan.php`:

1. Ganti:

```php
use App\Services\WeatherService;
use App\Support\Timezone;
```

dengan:

```php
use App\Services\WeatherService;
use App\Support\PenjagaDataContoh;
use App\Support\Timezone;
```

2. Ganti:

```php
            'urls' => [...$data['urls'], 'jejak' => null],
        ];
```

dengan:

```php
            'urls' => [...$data['urls'], 'jejak' => null],
            // Lencana Data contoh ikut ke shell luring, yang tidak menerima props bersama server.
            // Bukan bernama demo: nama itu milik prop bersama (ReminderSheetTest).
            'dataContoh' => PenjagaDataContoh::sedangDisajikan(),
        ];
```

`resources/js/components/ui/ikon/phosphor.ts`:

1. Ganti:

```ts
    | 'check'
    | 'house'
```

dengan:

```ts
    | 'check'
    | 'clock'
    | 'house'
```

2. Ganti:

```ts
    },
    'house': {
```

dengan:

```ts
    },
    'clock': {
        regular: 'M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Zm64-88a8,8,0,0,1-8,8H128a8,8,0,0,1-8-8V72a8,8,0,0,1,16,0v48h48A8,8,0,0,1,192,128Z',
        bold: 'M128,20A108,108,0,1,0,236,128,108.12,108.12,0,0,0,128,20Zm0,192a84,84,0,1,1,84-84A84.09,84.09,0,0,1,128,212Zm68-84a12,12,0,0,1-12,12H128a12,12,0,0,1-12-12V72a12,12,0,0,1,24,0v44h44A12,12,0,0,1,196,128Z',
    },
    'house': {
```

`resources/views/offline.blade.php`:

1. Ganti:

```php
        <x-brand.head :fonts="false" />

        @vite(['resources/css/app.css'])
    </head>
```

dengan:

```php
        <x-brand.head :fonts="false" />

        {{--
            Shell luring (spec v3 bagian 10.4; ruling W6-R9). Halaman ini disimpan service worker
            dan dibuka setiap kali dokumen gagal dimuat, jadi isinya sama untuk semua orang: tidak
            ada data pengguna, status, atau cuaca dari server di sini. Selama paket lapangan
            belum dibuka (hiking.field_pack.enabled), halaman ini hanya teks; sesudahnya entri
            shell membaca paket yang diunduh pendaki dari IndexedDB perangkat ini.
        --}}
        @if (config('hiking.field_pack.enabled'))
            @vite(['resources/css/app.css', 'resources/js/luring/shell.tsx'])
        @else
            @vite(['resources/css/app.css'])
        @endif
    </head>
```

2. Ganti:

```php
        --}}
        <main class="mx-auto flex min-h-dvh max-w-xl flex-col justify-center px-4 py-12 sm:px-6">
            <p class="text-sm font-medium text-muted">Luring</p>
```

dengan:

```php
        --}}
        <main id="luring-pesan" class="mx-auto flex min-h-dvh max-w-xl flex-col justify-center px-4 py-12 sm:px-6">
            <p class="text-sm font-medium text-muted">Luring</p>
```

3. Ganti:

```php
                <p class="rounded-control border border-warn-300 bg-warn-50 p-3 text-sm text-warn-900">
                    Aplikasi sengaja tidak menampilkan data lama saat luring. Status jalur dan
                    prakiraan cuaca berubah, dan salinan kemarin yang terlihat seperti keadaan
                    sekarang lebih berbahaya daripada tidak ada tampilan sama sekali.
```

dengan:

```php
                <p class="rounded-control border border-warn-300 bg-warn-50 p-3 text-sm text-warn-900">
                    Aplikasi sengaja tidak menampilkan salinan lama halaman saat luring. Status jalur
                    dan prakiraan cuaca berubah, dan salinan kemarin yang terlihat seperti keadaan
                    sekarang lebih berbahaya daripada tidak ada tampilan sama sekali.
```

4. Ganti:

```php
            </div>

            <div class="mt-8">
```

dengan:

```php
            </div>

            <div id="paket-luring"></div>

            <div class="mt-8">
```

5. Ganti:

```php
        </main>
    </body>
```

dengan:

```php
        </main>
        <div id="app"></div>
    </body>
```

Tulis ulang `resources/views/sw.blade.php` utuh:

```php
{{-- Versi cache mengikuti manifest build dan sakelar paket: keduanya mengubah isi shell. --}}
@php($manifest = is_file(public_path('build/manifest.json')) ? (json_decode((string) file_get_contents(public_path('build/manifest.json')), true) ?: []) : [])
@php($paketAktif = (bool) config('hiking.field_pack.enabled'))
const SHELL = 'shell-{{ substr(md5(json_encode([$manifest, $paketAktif])), 0, 8) }}';
const AWALAN_SHELL = 'shell-';

/*
 * Kebijakannya (spec v3 bagian 10.4): shell boleh disimpan; isi dari server tidak pernah
 * disajikan ulang dari cache; isi paket lapangan hanya berasal dari unduhan yang dipicu
 * pendaki, disimpan halaman di IndexedDB dan cache pack-*, dan selalu tampil dengan umurnya.
 *
 * Menyajikan halaman dari cache berarti menampilkan status jalur dan prakiraan cuaca lama
 * seolah kini. PRD §94 dan §95 melarang persis itu, dan pada produk keselamatan status "BUKA"
 * yang basi jauh lebih berbahaya daripada halaman yang gagal terbuka. Maka dokumen selalu
 * diambil dari jaringan; ketika jaringannya tidak ada, yang dibuka adalah shell luring.
 */
const SHELL_FILES = @json(\App\Support\AsetLuring::daftar($manifest, $paketAktif));

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(SHELL).then((cache) => cache.addAll(SHELL_FILES)).then(() => self.skipWaiting())
    );
});

// Hanya shell versi lama yang dibuang. Cache paket (pack-*) dan cache lain milik halaman tidak
// pernah disentuh deploy: paket hanya dihapus halaman (keluar akun, ganti akun, atau versi
// baru yang sudah lolos verifikasi).
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((kunci) => Promise.all(kunci.filter((k) => k.startsWith(AWALAN_SHELL) && k !== SHELL).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    if (request.method !== 'GET' || url.origin !== self.location.origin) {
        return;
    }

    // Halaman selalu dari jaringan. Gagal berarti luring, dan luring dijawab dengan shell
    // luring, bukan dengan salinan lama halaman itu.
    if (request.destination === 'document') {
        event.respondWith(
            fetch(request).catch(() => caches.match('/offline'))
        );

        return;
    }

    // Hanya aset berversi: build Vite (nama berhash), ikon, dan huruf. Respons Inertia, JSON,
    // manifest paket, dan foto laporan selalu dari jaringan.
    if (url.pathname.startsWith('/build/') || url.pathname.startsWith('/icons/') || url.pathname.startsWith('/fonts/')) {
        event.respondWith(
            caches.match(request).then((tersimpan) => tersimpan || fetch(request).then((respons) => {
                if (respons.ok) {
                    const salinan = respons.clone();
                    caches.open(SHELL).then((cache) => cache.put(request, salinan));
                }

                return respons;
            }))
        );
    }
});
```

`tests/Pratinjau/HalamanPratinjauTest.php`:

1. Ganti:

```php
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Route;
```

dengan:

```php
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
```

2. Ganti:

```php
    }
}
```

dengan:

```php
    }

    /**
     * Shell luring W6 (ruling W6-R9) dengan paket lapangan yang sudah lolos pemeriksaan di
     * perangkat: keadaan pendakian sama dengan pratinjau mode-lapangan, paket dirakit dari
     * manifest dan tiga bagiannya, lalu ditanam ke IndexedDB oleh skrip pratinjau sebelum shell
     * berjalan. Halaman ini memotret daftar paket dan mode lapangan dari paket tanpa server.
     */
    public function test_luring(): void
    {
        config(['hiking.field_pack.enabled' => true]);
        $this->test_mode_lapangan();
        $trip = TripPlan::where('status', TripStatus::IN_PROGRESS->value)->sole();

        $manifest = $this->getJson(self::ASAL.'/trips/'.$trip->id.'/paket')->assertOk()->json();
        $bagian = collect($manifest['bagian'])->mapWithKeys(fn (array $b) => [$b['nama'] => $this->get($b['url'])->assertOk()->getContent()])->all();
        $sesi = $trip->hikingSession;
        $benih = [
            'kunci' => 'pratinjau',
            'manifest' => $manifest,
            'bagian' => $bagian,
            'sesi' => [
                'tripId' => $trip->id,
                'trip' => ['berlangsung' => true, 'mulai' => $sesi->started_at->utc()->toIso8601String(), 'jedaSejak' => null, 'jedaDetik' => 0],
                'tercapai' => (int) $sesi->reached_checkpoint_sequence,
                'sesiId' => $sesi->id,
                'rekamJejak' => false,
                'jejak' => null,
                'diperbarui' => now()->utc()->toIso8601String(),
            ],
        ];

        $html = (string) $this->get(self::ASAL.'/offline')->assertOk()->getContent();
        $skrip = '<script>(() => { const b = '.json_encode($benih, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).';'
            .'localStorage.setItem("memount-perangkat", b.kunci);'
            .'const m = indexedDB.open("memount-luring-" + b.kunci, 1);'
            .'m.onupgradeneeded = () => { const d = m.result; d.createObjectStore("bagian", { keyPath: ["tripId", "versi", "nama"] }); d.createObjectStore("paket", { keyPath: ["tripId", "versi"] }); d.createObjectStore("sesi", { keyPath: "tripId" }); d.createObjectStore("antrean"); };'
            .'m.onsuccess = () => { const d = m.result; const t = d.transaction(["bagian", "paket", "sesi"], "readwrite");'
            .'for (const x of b.manifest.bagian) { t.objectStore("bagian").put({ tripId: b.manifest.trip.id, versi: b.manifest.versi, nama: x.nama, teks: b.bagian[x.nama], sha256: x.sha256 }); }'
            .'t.objectStore("paket").put({ tripId: b.manifest.trip.id, versi: b.manifest.versi, manifest: b.manifest, siap: true, diverifikasi: new Date().toISOString() });'
            .'t.objectStore("sesi").put(b.sesi); t.oncomplete = () => d.close(); };'
            .'})();</script>';

        File::ensureDirectoryExists(public_path('_pratinjau'));
        File::put(public_path('_pratinjau/luring.html'), str_replace('<head>', '<head>'.$skrip, $html));

        $this->assertStringContainsString('/build/assets/shell-', $html);
    }
}
```

`vite.config.js`:

1. Ganti:

```js
/*
 * Satu pintu masuk: app.tsx (Inertia + React). resources/js/dasar.js memegang service
 * worker, tema, dan pemuat MapLibre serta perekam jejak yang dimuat malas. Blade yang
 * tersisa (halaman publik, galat, luring) hanya memuat app.css.
 */
```

dengan:

```js
/*
 * Dua pintu masuk: app.tsx (Inertia + React) dan luring/shell.tsx, shell luring yang dibuka
 * service worker tanpa sinyal dan membaca paket lapangan dari IndexedDB (W6, ruling W6-R9).
 * resources/js/dasar.js memegang service worker, tema, dan pemuat MapLibre serta perekam
 * jejak yang dimuat malas. Blade yang tersisa (halaman publik dan galat) hanya memuat app.css.
 */
```

2. Ganti:

```js
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
```

dengan:

```js
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx', 'resources/js/luring/shell.tsx'],
            refresh: true,
```

- [ ] **Step 4: Build dan tipe.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `npx tsc --noEmit`
Expected: `✓ built` dengan entri `shell-<hash>.js`; tsc tanpa keluaran galat.

- [ ] **Step 5: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Support/AsetLuring.php app/Services/Trips/PaketLapangan.php tests/Feature/ProgressiveWebAppTest.php tests/Feature/OfflineShellTest.php tests/Pratinjau/HalamanPratinjauTest.php`, lalu `php artisan test --compact --filter='ProgressiveWebApp|OfflineShell|IconSystem|ReminderSheet'`
Expected: PASS. Pada simulasi rencana: 43 test lulus (filter merah ditambah `IconSystemTest` dan `ReminderSheetTest`).

- [ ] **Step 6: Suite, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 2.051 test, 2.049 lulus, 2 dilewati (sebelum tugas: 2.045).

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `php artisan test --compact --testsuite=Pratinjau`
Expected: PASS; halaman baru `luring` (shell dengan paket yang ditanam ke IndexedDB oleh skrip pratinjau). Pada simulasi rencana: 28 test lulus.

- [ ] **Step 7: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 10: Unduh, verifikasi, dan pakai paket

Spec bagian 10.2 ("Unduh dan verifikasi", "Pakai paket saja") dan 10.4 (`router.on('networkError')` menawarkan "Buka
paket luring"); induk W6 butir 5; ruling W6-R9 dan W6-R10. Prop bersama `perangkat` membawa kunci perangkat per
pengguna (HMAC id) dan sakelar paket. Pintu unduh di pratinjau hari-H dan ruang kerja trip: isi, ukuran menurut
manifest, dan sisa ruang (`navigator.storage.estimate()`) sebelum mengunduh; `persist()` pada ketukan; setiap bagian
di-hash sebelum disimpan dan dilewati bila sudah utuh (lanjut per bagian); "Siap luring" hanya setelah setiap bagian
dibaca ulang dari penyimpanan dan cocok, dan shell luring ada di cache service worker; versi lama dihapus sesudah versi
baru lolos; iPhone di tab Safari diminta memasang ke Layar Utama lebih dulu. Mode lapangan daring menyimpan keadaan
sesinya ke paket trip yang sama, dan sakelar "Pakai paket saja" membaca hanya dari paket. Pesan jaringan menawarkan
"Buka paket luring" bila sakelar menyala.

**Files:**
- Create: `app/Support/KunciPerangkat.php`, `resources/js/luring/periksa.ts`, `resources/js/luring/paket.ts`, `resources/js/luring/baca.ts`, `resources/js/luring/useLapanganPaket.ts`, `resources/js/components/mendaki/PintuPaket.tsx`
- Modify: `app/Http/Middleware/HandleInertiaRequests.php`, `resources/js/types/index.d.ts`, `resources/js/luring/shell.tsx`, `resources/js/components/mendaki/useModeLapangan.ts`, `resources/js/components/mendaki/ModeLapangan.tsx`, `resources/js/pages/Trips/Hike.tsx`, `resources/js/pages/Track/Index.tsx`, `resources/js/pages/Trips/Show.tsx`, `resources/js/layouts/PesanJaringan.tsx`
- Test: `tests/Feature/FieldPackDeviceTest.php` (baru), `tests/Feature/ShellFeedbackTest.php`, `tests/Pratinjau/HalamanPratinjauTest.php`

**Interfaces:**
- Consumes: `simpan.ts`, `rakit.ts`, `DaftarPaket` (Tugas 9); `kesegaran.ts`, rute `trips.pack` (Tugas 8, Wayfinder `pack` dari `@/routes/trips`); `ModeLapangan`, `useModeLapangan` (Tugas 3); `offline` dari `@/routes/pwa`; `Sakelar`, `Button`, `Card`.
- Produces: `KunciPerangkat::untuk(User): string` (20 heks); prop bersama `perangkat: { kunci: string; paket: boolean } | null`; `periksa.ts`: `bagianBelumUtuh(manifest, bacaan)`, `kalimatBelumLengkap(nama)`, `ruangCukup(ukuran, kuota, terpakai)`, `teksUkuran(bita)`; `paket.ts`: `GagalPaket`, `KemajuanPaket`, `ambilManifest(url)`, `cekRuang(manifest)`, `mintaMenetap()`, `unduhPaket(kunci, manifest, kabar)`, `tabSafariIphone()`; `baca.ts`: `DataPaket`, `teksBagian`, `terbaruPerTrip`, `paketSiap(kunci, tripId)`, `dataLapanganDariPaket(kunci, paket)`; `useLapanganPaket.ts`: `useDataPaket(kunci, aktif, tripId)`, `useSimpanSesiPaket(kunci, aktif, data, mode)`; `ModeLapangan` mendapat `jedaSejak` dan `jedaDetik`; komponen `ModeLapangan` menerima `pengaturan?: ReactNode`; komponen `PintuPaket({ tripId })`.

- [ ] **Step 1: Tulis test yang gagal.** `ShellFeedbackTest` mengikuti pesan jaringan yang kini juga membaca sakelar
paket (niatnya tetap: pemberitahuan versi baru memuat penuh, pesan jaringan tidak menyuruh mengirim ulang).

Buat `tests/Feature/FieldPackDeviceTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\KunciPerangkat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * Unduh dan verifikasi paket lapangan di perangkat (spec v3 bagian 10.2 dan 10.6; rencana induk
 * W6 butir 5; ruling W6-R9 dan W6-R10). Pemeriksaan keutuhan dan ruang dijalankan di Node dari
 * modul yang sama dengan peramban (resources/js/luring/periksa.ts); urutan "unduh, baca ulang,
 * baru siap, baru hapus versi lama" dijaga dari sumber karena butuh IndexedDB peramban, yang
 * diuji manual di tugas verifikasi akhir (Q17).
 */
class FieldPackDeviceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Kunci perangkat per pengguna: stabil untuk orang yang sama, berbeda antarakun, dan tidak
     * menyebut id pengguna. Tamu tidak mendapat kunci.
     */
    public function test_each_user_gets_a_stable_device_key_that_hides_the_id(): void
    {
        $budi = User::factory()->create();
        $sari = User::factory()->create();
        $kunci = KunciPerangkat::untuk($budi);

        $this->get(route('login'))->assertInertia(fn (AssertableInertia $page) => $page->where('perangkat', null));
        $this->actingAs($budi)->get(route('track'))->assertInertia(fn (AssertableInertia $page) => $page->where('perangkat', ['kunci' => $kunci, 'paket' => false]));

        config(['hiking.field_pack.enabled' => true]);
        $this->actingAs($budi)->get(route('track'))->assertInertia(fn (AssertableInertia $page) => $page->where('perangkat.paket', true));

        $this->assertSame($kunci, KunciPerangkat::untuk($budi->fresh()));
        $this->assertNotSame($kunci, KunciPerangkat::untuk($sari));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{20}$/', $kunci);
        $this->assertNotSame((string) $budi->id, $kunci);
    }

    /**
     * Paket "Siap luring" hanya bila setiap bagian manifest ada dan hash bacaan ulangnya cocok;
     * yang kurang disebut dengan namanya. Ruang dianggap cukup bila muat dua versi, karena versi
     * lama bertahan sampai versi baru lolos.
     */
    public function test_a_pack_is_ready_only_when_every_section_matches(): void
    {
        $manifest = ['bagian' => [['nama' => 'lapangan', 'sha256' => 'aa'], ['nama' => 'rencana', 'sha256' => 'bb'], ['nama' => 'dinamis', 'sha256' => 'cc']]];

        [$utuh, $kurang, $kalimat, $cukup, $sempit, $tanpaLaporan, $ukuranKecil, $ukuranBesar] = $this->periksa([
            ['bagianBelumUtuh', [$manifest, [['nama' => 'dinamis', 'sha256' => 'cc'], ['nama' => 'lapangan', 'sha256' => 'aa'], ['nama' => 'rencana', 'sha256' => 'bb']]]],
            ['bagianBelumUtuh', [$manifest, [['nama' => 'lapangan', 'sha256' => 'aa'], ['nama' => 'rencana', 'sha256' => 'zz']]]],
            ['kalimatBelumLengkap', [['rencana', 'dinamis']]],
            ['ruangCukup', [50_000, 1_000_000, 900_000]],
            ['ruangCukup', [50_000, 1_000_000, 950_000]],
            ['ruangCukup', [50_000, null, null]],
            ['teksUkuran', [48_300]],
            ['teksUkuran', [3_250_000]],
        ]);

        $this->assertSame([], $utuh);
        $this->assertSame(['rencana', 'dinamis'], $kurang);
        $this->assertSame('Belum lengkap: rencana, potret status dan cuaca. Coba lagi selagi ada sinyal.', $kalimat);
        $this->assertSame([true, false, true], [$cukup, $sempit, $tanpaLaporan]);
        $this->assertSame(['47 kB', '3,1 MB'], [$ukuranKecil, $ukuranBesar]);
    }

    /**
     * Urutan unduhan (spec v3 bagian 10.2): setiap bagian di-hash sebelum disimpan, paket dibaca
     * ulang dari penyimpanan sebelum ditulis siap, versi lama dihapus sesudahnya, dan persist()
     * diminta pada ketukan unduh.
     */
    public function test_the_download_verifies_before_it_replaces_the_old_version(): void
    {
        $paket = File::get(resource_path('js/luring/paket.ts'));
        $pintu = File::get(resource_path('js/components/mendaki/PintuPaket.tsx'));

        $this->assertStringContainsString('if (sha256 !== bagian.sha256) {', $paket);
        $this->assertLessThan(strpos($paket, 'await tulisPaket(db, rekaman);'), strpos($paket, 'bagianBelumUtuh(manifest, bacaan)'));
        $this->assertLessThan(strpos($paket, 'await hapusPaket(db, lama.tripId, lama.versi);'), strpos($paket, 'await tulisPaket(db, rekaman);'));
        $this->assertStringContainsString("caches.match('/offline')", $paket);
        $this->assertStringContainsString('navigator.storage?.estimate?.()', $paket);
        $this->assertStringContainsString('const menetap = await mintaMenetap();', $pintu);
        $this->assertStringContainsString('{paket.aktif ? <PintuPaket tripId={hariH.id} /> : null}', File::get(resource_path('js/pages/Track/Index.tsx')));
        $this->assertStringContainsString('{trip.aktif && perangkat?.paket ? (', File::get(resource_path('js/pages/Trips/Show.tsx')));
    }

    /**
     * @param  array<int, array{0: string, 1: array<int, mixed>}>  $panggilan
     * @return array<int, mixed>
     */
    private function periksa(array $panggilan): array
    {
        $skrip = 'import { pathToFileURL } from "node:url";'
            .'const modul = await import(pathToFileURL('.json_encode(resource_path('js/luring/periksa.ts')).').href);'
            .'const panggilan = '.json_encode($panggilan).';'
            .'process.stdout.write(JSON.stringify(panggilan.map(([nama, argumen]) => modul[nama](...argumen))));';

        $proses = new Process(['node', '--experimental-strip-types', '--input-type=module', '-'], base_path(), null, $skrip, 60);
        $proses->run();

        $this->assertTrue($proses->isSuccessful(), 'Node gagal menjalankan periksa.ts: '.mb_substr($proses->getErrorOutput(), 0, 600));

        return json_decode($proses->getOutput(), true, flags: JSON_THROW_ON_ERROR);
    }
}
```

`tests/Feature/ShellFeedbackTest.php`:

1. Ganti:

```php
        $this->assertMatchesRegularExpression('/onClick=\{\(\) => muatPenuhVersiBaru\(\)\}\s*>\s*Muat ulang halaman\s*<\/Button>/', $pesan, 'Tombol pemberitahuan memuat penuh halaman, bukan muat ulang parsial yang akan terkena konflik versi lagi.');
        $this->assertStringContainsString('const { merek } = usePage<PropsBersama>().props;', $pesan);
    }
```

dengan:

```php
        $this->assertMatchesRegularExpression('/onClick=\{\(\) => muatPenuhVersiBaru\(\)\}\s*>\s*Muat ulang halaman\s*<\/Button>/', $pesan, 'Tombol pemberitahuan memuat penuh halaman, bukan muat ulang parsial yang akan terkena konflik versi lagi.');
        // W6 (ruling W6-R9): pesan yang sama juga membaca sakelar paket untuk "Buka paket luring",
        // tautan dokumen ke /offline yang dijawab service worker, bukan kunjungan Inertia.
        $this->assertStringContainsString('const { merek, perangkat } = usePage<PropsBersama>().props;', $pesan);
        $this->assertStringContainsString('{perangkat?.paket ? (', $pesan);
        $this->assertStringContainsString('href={offline.url()} external>', $pesan);
    }
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='FieldPackDevice|ShellFeedback'`
Expected: FAIL. Pada simulasi rencana: 14 test, 10 lulus, 2 gagal, 2 error. Yang merah: tiga test `FieldPackDeviceTest` (`periksa.ts`, `KunciPerangkat`, dan `paket.ts` belum ada) dan `ShellFeedbackTest::test_the_new_version_notice_lets_the_hiker_choose_when_to_reload` (pesan jaringan belum menawarkan paket luring).

- [ ] **Step 3: Tulis implementasinya.** Kelas dengan
`DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:class --no-interaction Support/KunciPerangkat`,
lalu isinya diganti persis.

Buat `app/Support/KunciPerangkat.php`:

```php
<?php

namespace App\Support;

use App\Models\User;

/**
 * Kunci penyimpanan perangkat per pengguna (spec v3 bagian 10.4 dan 10.6; rencana induk W6 butir
 * 5 dan 7; ruling W6-R10): nama basis data paket lapangan `memount-luring-<kunci>` dan basis
 * data perekam jejak `jejak-pendakian-<kunci>`.
 *
 * HMAC id pengguna dengan kunci aplikasi, bukan id itu sendiri: nama basis data terbaca oleh
 * siapa pun yang memegang perangkat, dan id berurutan menyebut berapa banyak pengguna yang ada.
 * Stabil untuk pengguna yang sama, berbeda untuk akun lain di peramban yang sama.
 */
final class KunciPerangkat
{
    public static function untuk(User $pengguna): string
    {
        return substr(hash_hmac('sha256', 'perangkat:'.$pengguna->getKey(), (string) config('app.key')), 0, 20);
    }
}
```

Buat `resources/js/components/mendaki/PintuPaket.tsx`:

```tsx
import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { paketSiap } from '@/luring/baca';
import { ambilManifest, cekRuang, GagalPaket, mintaMenetap, tabSafariIphone, unduhPaket, type KemajuanPaket } from '@/luring/paket';
import type { ManifestPaket, RekamanPaket } from '@/luring/simpan';
import { Button } from '@/components/ui/Button';
import { pack } from '@/routes/trips';
import type { PropsBersama } from '@/types';

type Keadaan =
    | { tahap: 'memeriksa' }
    | { tahap: 'kosong' }
    | { tahap: 'pratinjau'; manifest: ManifestPaket; ruang: { cukup: boolean; kalimat: string } }
    | { tahap: 'mengunduh'; kemajuan: KemajuanPaket }
    | { tahap: 'siap'; paket: RekamanPaket; menetap: boolean | null; pesan: string | null }
    | { tahap: 'gagal'; pesan: string };

const waktu = new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });

/**
 * Pintu unduh paket lapangan (spec v3 bagian 10.2; rencana induk W6 butir 5; ruling W6-R9),
 * di pratinjau hari-H dan ruang kerja trip. Sebelum mengunduh: isi, ukuran menurut manifest,
 * dan sisa ruang. Unduhan berjalan di halaman, kemajuannya dihitung dari bagian yang sudah
 * terverifikasi, dan "Siap luring" baru ditulis setelah paket dibaca ulang dan cocok. Gagal
 * disebut spesifik dengan "Coba lagi". Hanya tampil bila sakelar paket menyala (Q10, Q14).
 */
export function PintuPaket({ tripId }: { tripId: number }) {
    const { perangkat } = usePage<PropsBersama>().props;
    const [keadaan, setKeadaan] = useState<Keadaan>({ tahap: 'memeriksa' });
    const kunci = perangkat?.kunci ?? null;

    useEffect(() => {
        if (!kunci) {
            return;
        }

        paketSiap(kunci, tripId)
            .then((paket) => setKeadaan(paket ? { tahap: 'siap', paket, menetap: null, pesan: null } : { tahap: 'kosong' }))
            .catch(() => setKeadaan({ tahap: 'kosong' }));
    }, [kunci, tripId]);

    if (!perangkat?.paket || !kunci) {
        return null;
    }

    if (tabSafariIphone()) {
        return (
            <p className="mt-2 text-kecil text-primary">
                Di iPhone, paket yang diunduh dari tab Safari tersimpan terpisah dari aplikasi di Layar Utama, dan aplikasi itulah yang Anda buka di jalur. Pasang Memount ke Layar Utama
                dulu (Bagikan, lalu Tambahkan ke Layar Utama), lalu unduh paket dari sana.
            </p>
        );
    }

    const siapkan = async () => {
        try {
            const manifest = await ambilManifest(pack.url(tripId));
            setKeadaan({ tahap: 'pratinjau', manifest, ruang: await cekRuang(manifest) });
        } catch (galat) {
            setKeadaan({ tahap: 'gagal', pesan: galat instanceof GagalPaket ? galat.message : 'Paket belum dapat disiapkan. Coba lagi selagi ada sinyal.' });
        }
    };

    const unduh = async (manifest: ManifestPaket) => {
        // persist() diminta pada ketukan ini: peramban mensyaratkan gestur pengguna.
        const menetap = await mintaMenetap();

        try {
            const paket = await unduhPaket(kunci, manifest, (kemajuan) => setKeadaan({ tahap: 'mengunduh', kemajuan }));
            setKeadaan({ tahap: 'siap', paket, menetap, pesan: null });
        } catch (galat) {
            setKeadaan({ tahap: 'gagal', pesan: galat instanceof GagalPaket ? galat.message : 'Unduhan terputus. Bagian yang sudah utuh disimpan; coba lagi selagi ada sinyal.' });
        }
    };

    const perbarui = async (lama: RekamanPaket) => {
        try {
            const manifest = await ambilManifest(pack.url(tripId));

            if (manifest.versi === lama.versi) {
                setKeadaan({ tahap: 'siap', paket: lama, menetap: null, pesan: 'Paket di perangkat ini sudah versi terbaru.' });

                return;
            }

            await unduh(manifest);
        } catch (galat) {
            setKeadaan({ tahap: 'gagal', pesan: galat instanceof GagalPaket ? galat.message : 'Paket belum dapat diperbarui. Paket lama tetap tersimpan.' });
        }
    };

    return (
        <div className="mt-2 space-y-2" aria-live="polite">
            {keadaan.tahap === 'memeriksa' ? <p className="text-kecil text-secondary">Memeriksa paket di perangkat ini.</p> : null}

            {keadaan.tahap === 'kosong' ? (
                <>
                    <p className="text-kecil text-secondary">Belum ada paket untuk trip ini di perangkat ini. Isinya: jalur dan pos, profil elevasi, rencana, dan potret status serta cuaca.</p>
                    <Button variant="secondary" size="sm" onClick={() => void siapkan()}>
                        Siapkan paket luring
                    </Button>
                </>
            ) : null}

            {keadaan.tahap === 'pratinjau' ? (
                <>
                    <p className="text-kecil text-primary">{keadaan.ruang.kalimat}</p>
                    {keadaan.ruang.cukup ? (
                        <Button size="sm" onClick={() => void unduh(keadaan.manifest)}>
                            Unduh paket
                        </Button>
                    ) : null}
                </>
            ) : null}

            {keadaan.tahap === 'mengunduh' ? (
                <p className="text-kecil text-primary">
                    Mengunduh dan memeriksa: {keadaan.kemajuan.selesai} dari {keadaan.kemajuan.jumlah} bagian utuh.
                </p>
            ) : null}

            {keadaan.tahap === 'siap' ? (
                <>
                    <p className="text-kecil font-bold text-primary">
                        Siap luring. Diperiksa utuh di perangkat ini {keadaan.paket.diverifikasi ? waktu.format(new Date(keadaan.paket.diverifikasi)) : ''}.
                    </p>
                    {keadaan.menetap === false ? (
                        <p className="text-kecil text-secondary">Peramban belum menjamin menyimpan paket ini; bila ruang menipis, peramban boleh menghapusnya. Buka paket sekali sebelum berangkat.</p>
                    ) : null}
                    {keadaan.pesan ? <p className="text-kecil text-secondary">{keadaan.pesan}</p> : null}
                    <Button variant="secondary" size="sm" onClick={() => void perbarui(keadaan.paket)}>
                        Perbarui paket
                    </Button>
                </>
            ) : null}

            {keadaan.tahap === 'gagal' ? (
                <>
                    <p className="text-kecil font-bold text-primary">{keadaan.pesan}</p>
                    <Button variant="secondary" size="sm" onClick={() => void siapkan()}>
                        Coba lagi
                    </Button>
                </>
            ) : null}
        </div>
    );
}
```

Buat `resources/js/luring/baca.ts`:

```ts
import type { DataLapangan } from '@/components/mendaki/types';
import { rakitDataLapangan } from './rakit';
import { bacaBagian, bacaSesi, buka, semuaPaket, type RekamanBagian, type RekamanPaket } from './simpan';

/**
 * Membaca paket dari perangkat saja, tanpa jaringan: dipakai shell luring dan sakelar "Pakai paket
 * saja" di mode lapangan daring (spec v3 bagian 10.2 dan 10.4).
 */

export type DataPaket = DataLapangan & { dataContoh?: boolean };

export function teksBagian(bagian: RekamanBagian[], nama: RekamanBagian['nama']): string {
    const ada = bagian.find((b) => b.nama === nama);

    if (!ada) {
        throw new Error(`Bagian ${nama} tidak ada di paket.`);
    }

    return ada.teks;
}

/** Satu versi per trip: yang terakhir lolos pemeriksaan. */
export function terbaruPerTrip(paket: RekamanPaket[]): RekamanPaket[] {
    const per = new Map<number, RekamanPaket>();

    for (const p of paket.filter((p) => p.siap)) {
        const ada = per.get(p.tripId);

        if (!ada || (p.diverifikasi ?? '') > (ada.diverifikasi ?? '')) {
            per.set(p.tripId, p);
        }
    }

    return [...per.values()];
}

export async function paketSiap(kunci: string, tripId: number): Promise<RekamanPaket | null> {
    const db = await buka(kunci);

    return terbaruPerTrip(await semuaPaket(db)).find((p) => p.tripId === tripId) ?? null;
}

/** Data mode lapangan dari satu paket, digabung dengan keadaan sesi terakhir di perangkat. */
export async function dataLapanganDariPaket(kunci: string, paket: RekamanPaket): Promise<DataPaket> {
    const db = await buka(kunci);
    const bagian = await bacaBagian(db, paket.tripId, paket.versi);
    const lapangan = JSON.parse(teksBagian(bagian, 'lapangan')) as DataPaket;

    return { ...rakitDataLapangan(lapangan, JSON.parse(teksBagian(bagian, 'dinamis')), await bacaSesi(db, paket.tripId)), dataContoh: lapangan.dataContoh };
}
```

Buat `resources/js/luring/paket.ts`:

```ts
import { bagianBelumUtuh, kalimatBelumLengkap, ruangCukup, teksUkuran } from './periksa';
import {
    bacaBagian,
    buka,
    hapusPaket,
    semuaPaket,
    sha256Hex,
    simpanKunci,
    tulisBagian,
    tulisPaket,
    type ManifestPaket,
    type RekamanPaket,
} from './simpan';

/**
 * Unduh dan verifikasi paket lapangan di halaman, bukan di service worker (spec v3 bagian 10.2;
 * rencana induk W6 butir 5; ruling W6-R9).
 *
 * Urutannya: manifest, cek ruang (`navigator.storage.estimate()`), lalu setiap bagian diunduh,
 * di-hash, dan disimpan; bagian yang sudah tersimpan dengan hash yang sama dilewati, jadi unduhan
 * yang putus dilanjutkan per bagian. "Siap luring" hanya setelah setiap bagian dibaca ulang dari
 * penyimpanan dan cocok dengan manifest, dan shell luring ada di cache service worker. Versi lama
 * dihapus hanya sesudah versi baru lolos.
 */

export class GagalPaket extends Error {}

export interface KemajuanPaket {
    /** Bagian yang sudah terverifikasi dari seluruh bagian manifest. */
    selesai: number;
    jumlah: number;
}

export async function ambilManifest(url: string): Promise<ManifestPaket> {
    const respons = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });

    if (!respons.ok) {
        throw new GagalPaket(respons.status === 404 ? 'Paket untuk trip ini tidak tersedia.' : 'Manifest paket tidak dapat dibaca. Coba lagi selagi ada sinyal.');
    }

    return (await respons.json()) as ManifestPaket;
}

/** Sisa ruang menurut peramban; null bila peramban tidak melaporkannya. */
export async function cekRuang(manifest: ManifestPaket): Promise<{ cukup: boolean; kalimat: string }> {
    const perkiraan = await navigator.storage?.estimate?.().catch(() => null);
    const cukup = ruangCukup(manifest.ukuran, perkiraan?.quota ?? null, perkiraan?.usage ?? null);

    return {
        cukup,
        kalimat: cukup
            ? `Ukuran paket ${teksUkuran(manifest.ukuran)}. Ruang perangkat cukup.`
            : `Ukuran paket ${teksUkuran(manifest.ukuran)}, dan ruang perangkat tidak cukup. Kosongkan ruang dulu.`,
    };
}

/**
 * Meminta penyimpanan menetap. Dipanggil pada ketukan unduh (peramban mensyaratkan gestur);
 * false berarti peramban boleh menghapus paket saat ruang menipis, dan pendaki diberi tahu.
 */
export async function mintaMenetap(): Promise<boolean> {
    return (await navigator.storage?.persist?.().catch(() => false)) ?? false;
}

export async function unduhPaket(kunci: string, manifest: ManifestPaket, kabar: (k: KemajuanPaket) => void): Promise<RekamanPaket> {
    const db = await buka(kunci);
    const { id } = manifest.trip;
    const tersimpan = new Map((await bacaBagian(db, id, manifest.versi)).map((b) => [b.nama, b.sha256]));
    let selesai = 0;

    kabar({ selesai, jumlah: manifest.bagian.length });

    for (const bagian of manifest.bagian) {
        if (tersimpan.get(bagian.nama) !== bagian.sha256) {
            const respons = await fetch(bagian.url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });

            if (!respons.ok) {
                throw new GagalPaket(kalimatBelumLengkap([bagian.nama]));
            }

            const teks = await respons.text();
            const sha256 = await sha256Hex(teks);

            if (sha256 !== bagian.sha256) {
                throw new GagalPaket(kalimatBelumLengkap([bagian.nama]));
            }

            await tulisBagian(db, { tripId: id, versi: manifest.versi, nama: bagian.nama, teks, sha256 });
        }

        selesai += 1;
        kabar({ selesai, jumlah: manifest.bagian.length });
    }

    // Dibaca ulang dari penyimpanan, bukan dari memori unduhan: yang dibuktikan adalah isi
    // yang akan dibuka shell luring nanti.
    const bacaan = await Promise.all((await bacaBagian(db, id, manifest.versi)).map(async (b) => ({ nama: b.nama, sha256: await sha256Hex(b.teks) })));
    const kurang = bagianBelumUtuh(manifest, bacaan);

    if (kurang.length > 0) {
        throw new GagalPaket(kalimatBelumLengkap(kurang));
    }

    if (!(await shellTersimpan())) {
        throw new GagalPaket('Belum lengkap: halaman luring belum tersimpan di peramban ini. Buka aplikasi sekali lagi selagi ada sinyal, lalu coba lagi.');
    }

    const rekaman: RekamanPaket = { tripId: id, versi: manifest.versi, manifest, siap: true, diverifikasi: new Date().toISOString() };
    await tulisPaket(db, rekaman);

    for (const lama of (await semuaPaket(db)).filter((p) => p.tripId === id && p.versi !== manifest.versi)) {
        await hapusPaket(db, lama.tripId, lama.versi);
    }

    simpanKunci(kunci);

    return rekaman;
}

async function shellTersimpan(): Promise<boolean> {
    if (!('caches' in window) || !navigator.serviceWorker?.controller) {
        return false;
    }

    return (await caches.match('/offline')) !== undefined;
}

/** iPhone yang membuka dari tab Safari menyimpan terpisah dari aplikasi di Layar Utama. */
export function tabSafariIphone(): boolean {
    const iphone = /iP(hone|ad|od)/.test(navigator.userAgent);
    const terpasang = window.matchMedia?.('(display-mode: standalone)').matches || (navigator as Navigator & { standalone?: boolean }).standalone === true;

    return iphone && !terpasang;
}
```

Buat `resources/js/luring/periksa.ts`:

```ts
/**
 * Pemeriksaan keutuhan paket (spec v3 bagian 10.2; rencana induk W6 butir 5): "Siap luring" hanya
 * bila setiap bagian manifest ada di penyimpanan dan hash bacaan ulangnya sama dengan manifest.
 * Gagal disebut spesifik ("Belum lengkap: ...").
 *
 * Tanpa impor nilai: FieldPackDeviceTest menjalankannya di Node dari PHPUnit.
 */

const NAMA: Record<string, string> = { lapangan: 'jalur dan pos', rencana: 'rencana', dinamis: 'potret status dan cuaca' };

/** Nama bagian yang belum utuh, dalam urutan manifest; kosong berarti paket utuh. */
export function bagianBelumUtuh(manifest: { bagian: Array<{ nama: string; sha256: string }> }, bacaan: Array<{ nama: string; sha256: string }>): string[] {
    const hash = new Map(bacaan.map((b) => [b.nama, b.sha256]));

    return manifest.bagian.filter((b) => hash.get(b.nama) !== b.sha256).map((b) => b.nama);
}

export function kalimatBelumLengkap(nama: string[]): string {
    return `Belum lengkap: ${nama.map((n) => NAMA[n] ?? n).join(', ')}. Coba lagi selagi ada sinyal.`;
}

/** Ruang yang dibutuhkan: dua kali ukuran manifest, karena versi lama bertahan sampai versi baru lolos. */
export function ruangCukup(ukuranPaket: number, kuota: number | null, terpakai: number | null): boolean {
    return kuota === null || terpakai === null || kuota - terpakai >= ukuranPaket * 2;
}

export function teksUkuran(bita: number): string {
    return bita < 1024 * 1024 ? `${Math.max(1, Math.round(bita / 1024))} kB` : `${(bita / (1024 * 1024)).toLocaleString('id-ID', { maximumFractionDigits: 1 })} MB`;
}
```

Buat `resources/js/luring/useLapanganPaket.ts`:

```ts
import { useEffect, useState } from 'react';
import type { DataLapangan } from '@/components/mendaki/types';
import type { ModeLapangan } from '@/components/mendaki/useModeLapangan';
import { dataLapanganDariPaket, paketSiap, type DataPaket } from './baca';
import { sesiDari } from './rakit';
import { buka, tulisSesi } from './simpan';

/**
 * Data mode lapangan dari paket trip ini di perangkat, untuk sakelar "Pakai paket saja" (spec v3
 * bagian 10.2): membaca hanya dari paket walau sinyal lemah, sekaligus uji luring yang benar.
 * Null bila sakelar paket mati, tanpa kunci perangkat, atau belum ada paket yang lolos.
 */
export function useDataPaket(kunci: string | null, aktif: boolean, tripId: number): DataPaket | null {
    const [data, setData] = useState<DataPaket | null>(null);

    useEffect(() => {
        if (!aktif || !kunci) {
            return;
        }

        let batal = false;

        paketSiap(kunci, tripId)
            .then((paket) => (paket ? dataLapanganDariPaket(kunci, paket) : null))
            .then((hasil) => {
                if (!batal) {
                    setData(hasil);
                }
            })
            .catch(() => undefined);

        return () => {
            batal = true;
        };
    }, [kunci, aktif, tripId]);

    return data;
}

/**
 * Mode lapangan daring menyimpan keadaan sesinya ke paket trip yang sama di perangkat ini, supaya
 * mode lapangan dari paket melanjutkan dari pos dan jeda terakhir, bukan dari keadaan saat paket
 * diunduh. Hanya bila paket trip ini ada di perangkat.
 */
export function useSimpanSesiPaket(kunci: string | null, aktif: boolean, data: DataLapangan, mode: ModeLapangan): void {
    const { tercapai, jedaSejak, jedaDetik } = mode;

    useEffect(() => {
        if (!aktif || !kunci || data.sumber !== 'daring') {
            return;
        }

        const sekarang = { ...data, tercapai, trip: { ...data.trip, jedaSejak: jedaSejak === null ? null : new Date(jedaSejak).toISOString(), jedaDetik } };

        paketSiap(kunci, data.trip.id)
            .then(async (paket) => {
                if (paket) {
                    await tulisSesi(await buka(kunci), sesiDari(sekarang, new Date()));
                }
            })
            .catch(() => undefined);
    }, [kunci, aktif, data, tercapai, jedaSejak, jedaDetik]);
}
```

`app/Http/Middleware/HandleInertiaRequests.php`:

1. Ganti:

```php
use App\Support\DisplayPreference;
use App\Support\PenjagaDataContoh;
```

dengan:

```php
use App\Support\DisplayPreference;
use App\Support\KunciPerangkat;
use App\Support\PenjagaDataContoh;
```

2. Ganti:

```php
            'demo' => PenjagaDataContoh::sedangDisajikan(),

            // Hitungan Simpanan di header layar lebar dan kepala Jelajah (spec v3 bagian 5.2):
```

dengan:

```php
            'demo' => PenjagaDataContoh::sedangDisajikan(),

            // Penyimpanan perangkat per pengguna (W6, ruling W6-R10): kunci basis data paket
            // lapangan dan perekam jejak, serta sakelar paket (Q10, Q14). Null untuk tamu.
            'perangkat' => $user === null ? null : [
                'kunci' => KunciPerangkat::untuk($user),
                'paket' => (bool) config('hiking.field_pack.enabled'),
            ],

            // Hitungan Simpanan di header layar lebar dan kepala Jelajah (spec v3 bagian 5.2):
```

`resources/js/components/mendaki/ModeLapangan.tsx`:

1. Ganti:

```tsx
 */
export function ModeLapangan({ data, mode, selesai }: { data: DataLapangan; mode: KeadaanLapangan; selesai: ReactNode }) {
    const layar = useLayarMenyala();
```

dengan:

```tsx
 */
export function ModeLapangan({ data, mode, selesai, pengaturan = null }: { data: DataLapangan; mode: KeadaanLapangan; selesai: ReactNode; pengaturan?: ReactNode }) {
    const layar = useLayarMenyala();
```

2. Ganti:

```tsx
            {layar.didukung ? <Sakelar judul="Layar tetap menyala" keterangan="Mati bawaan: layar yang terus menyala menghabiskan baterai." nyala={layar.menyala} onUbah={layar.ubah} /> : null}

            {!mode.daring || mode.antrean.jumlah > 0 || mode.antrean.pesan ? (
```

dengan:

```tsx
            {layar.didukung ? <Sakelar judul="Layar tetap menyala" keterangan="Mati bawaan: layar yang terus menyala menghabiskan baterai." nyala={layar.menyala} onUbah={layar.ubah} /> : null}
            {pengaturan}

            {!mode.daring || mode.antrean.jumlah > 0 || mode.antrean.pesan ? (
```

`resources/js/components/mendaki/useModeLapangan.ts`:

1. Ganti:

```ts
    dijeda: boolean;
    detik: number;
```

dengan:

```ts
    dijeda: boolean;
    /** Jeda yang sedang berjalan sejak (ms) dan detik jeda yang selesai; disimpan ke rekaman sesi paket. */
    jedaSejak: number | null;
    jedaDetik: number;
    detik: number;
```

2. Ganti:

```ts
        dijeda,
        detik: data.trip.mulai ? detikBergerak(Date.parse(data.trip.mulai), jedaDetik, jedaSejak, sekarang) : 0,
```

dengan:

```ts
        dijeda,
        jedaSejak,
        jedaDetik,
        detik: data.trip.mulai ? detikBergerak(Date.parse(data.trip.mulai), jedaDetik, jedaSejak, sekarang) : 0,
```

`resources/js/layouts/PesanJaringan.tsx`:

1. Ganti:

```tsx
import { TombolIkon } from '@/components/ui/TombolIkon';
import type { PropsBersama } from '@/types';
```

dengan:

```tsx
import { TombolIkon } from '@/components/ui/TombolIkon';
import { offline } from '@/routes/pwa';
import type { PropsBersama } from '@/types';
```

2. Ganti:

```tsx
 * mengirim ulang, sebab mengirim ulang formulir buat bisa menggandakan data; pesannya
 * hanya meminta memeriksa hasilnya lebih dulu (ruling W2-5d). Penawaran "Buka paket
 * luring" menyusul di W6.
 *
```

dengan:

```tsx
 * mengirim ulang, sebab mengirim ulang formulir buat bisa menggandakan data; pesannya
 * hanya meminta memeriksa hasilnya lebih dulu (ruling W2-5d). Bila paket lapangan dibuka (W6,
 * ruling W6-R9), pesan menawarkan "Buka paket luring": tautan dokumen biasa ke /offline, yang
 * dijawab service worker dengan shell luring dari cache; bukan kunjungan Inertia, yang pasti
 * gagal tanpa sinyal.
 *
```

3. Ganti:

```tsx
    const wadah = useRef<HTMLDivElement>(null);
    const { merek } = usePage<PropsBersama>().props;

    useEffect(() => {
```

dengan:

```tsx
    const wadah = useRef<HTMLDivElement>(null);
    const { merek, perangkat } = usePage<PropsBersama>().props;

    useEffect(() => {
```

4. Ganti:

```tsx
                            Muat ulang halaman
                        </Button>
                    </Alert>
                )}
```

dengan:

```tsx
                            Muat ulang halaman
                        </Button>
                        {perangkat?.paket ? (
                            <Button variant="ghost" size="sm" className="mt-3" href={offline.url()} external>
                                Buka paket luring
                            </Button>
                        ) : null}
                    </Alert>
                )}
```

`resources/js/luring/shell.tsx`:

1. Ganti:

```tsx
import { pakaiPenyimpanan } from '@/components/mendaki/antrean';
import type { DataLapangan } from '@/components/mendaki/types';
import Hike from '@/pages/Trips/Hike';
import { DaftarPaket, type PaketDiPerangkat } from './DaftarPaket';
import { rakitDataLapangan } from './rakit';
import { bacaBagian, bacaSesi, buka, kunciTersimpan, penyimpananAntrean, semuaPaket, type RekamanBagian, type RekamanPaket } from './simpan';

/*
```

dengan:

```tsx
import { pakaiPenyimpanan } from '@/components/mendaki/antrean';
import Hike from '@/pages/Trips/Hike';
import { dataLapanganDariPaket, teksBagian, terbaruPerTrip } from './baca';
import { DaftarPaket, type PaketDiPerangkat } from './DaftarPaket';
import { bacaBagian, buka, kunciTersimpan, penyimpananAntrean, semuaPaket, type RekamanPaket } from './simpan';

/*
```

2. Ganti:

```tsx
const TANPA_PETA_DASAR = { styleUrl: null, rasterTiles: [], attribution: 'data rute Memount', maxZoom: 17 };

function teksBagian(bagian: RekamanBagian[], nama: RekamanBagian['nama']): string {
    const ada = bagian.find((b) => b.nama === nama);

    if (!ada) {
        throw new Error(`Bagian ${nama} tidak ada di paket.`);
    }

    return ada.teks;
}

/** Satu versi per trip: yang terakhir lolos pemeriksaan. */
function terbaru(paket: RekamanPaket[]): RekamanPaket[] {
    const per = new Map<number, RekamanPaket>();

    for (const p of paket.filter((p) => p.siap)) {
        const ada = per.get(p.tripId);

        if (!ada || (p.diverifikasi ?? '') > (ada.diverifikasi ?? '')) {
            per.set(p.tripId, p);
        }
    }

    return [...per.values()];
}

async function bukaLapangan(db: IDBDatabase, paket: RekamanPaket): Promise<void> {
    const bagian = await bacaBagian(db, paket.tripId, paket.versi);
    const lapangan = JSON.parse(teksBagian(bagian, 'lapangan')) as DataLapangan & { dataContoh?: boolean };
    const data = rakitDataLapangan(lapangan, JSON.parse(teksBagian(bagian, 'dinamis')), await bacaSesi(db, paket.tripId));

    // Aksi yang dicatat tanpa sinyal bertahan di perangkat walau halaman ini ditutup.
    pakaiPenyimpanan(penyimpananAntrean(db));
    // Teks pengakuan luring diganti mode lapangan; elemennya dibuang karena kelas flex-nya
```

dengan:

```tsx
const TANPA_PETA_DASAR = { styleUrl: null, rasterTiles: [], attribution: 'data rute Memount', maxZoom: 17 };

async function bukaLapangan(kunci: string, paket: RekamanPaket): Promise<void> {
    const data = await dataLapanganDariPaket(kunci, paket);

    // Aksi yang dicatat tanpa sinyal bertahan di perangkat walau halaman ini ditutup.
    pakaiPenyimpanan(penyimpananAntrean(await buka(kunci)));
    // Teks pengakuan luring diganti mode lapangan; elemennya dibuang karena kelas flex-nya
```

3. Ganti:

```tsx
            rememberedState: {},
            props: { ...data, title: `Mode lapangan dari paket: ${data.trip.jalur}`, demo: lapangan.dataContoh ?? false, peta: TANPA_PETA_DASAR, errors: {} },
        },
```

dengan:

```tsx
            rememberedState: {},
            props: { ...data, title: `Mode lapangan dari paket: ${data.trip.jalur}`, demo: data.dataContoh ?? false, peta: TANPA_PETA_DASAR, errors: {} },
        },
```

4. Ganti:

```tsx
    const paket: PaketDiPerangkat[] = await Promise.all(
        terbaru(await semuaPaket(db)).map(async (rekaman) => {
            const bagian = await bacaBagian(db, rekaman.tripId, rekaman.versi);
```

dengan:

```tsx
    const paket: PaketDiPerangkat[] = await Promise.all(
        terbaruPerTrip((await semuaPaket(db)).filter((p) => p.siap)).map(async (rekaman) => {
            const bagian = await bacaBagian(db, rekaman.tripId, rekaman.versi);
```

5. Ganti:

```tsx
    if (paket.length > 0) {
        createRoot(wadah).render(<DaftarPaket paket={paket} sekarangMs={Date.now()} onBuka={(p) => void bukaLapangan(db, p.rekaman)} />);
    }
```

dengan:

```tsx
    if (paket.length > 0) {
        createRoot(wadah).render(<DaftarPaket paket={paket} sekarangMs={Date.now()} onBuka={(p) => void bukaLapangan(kunci, p.rekaman)} />);
    }
```

`resources/js/pages/Track/Index.tsx`:

1. Ganti:

```tsx
import { useState, type ReactNode } from 'react';
import { Button } from '@/components/ui/Button';
```

dengan:

```tsx
import { useState, type ReactNode } from 'react';
import { PintuPaket } from '@/components/mendaki/PintuPaket';
import { Button } from '@/components/ui/Button';
```

2. Ganti:

```tsx
                        <dl className="mt-4 space-y-3 border-t border-subtle pt-4">
                            <Butir judul="Paket luring">{paket.kalimat}</Butir>
                            <Butir judul="Lokasi">{lokasi}</Butir>
```

dengan:

```tsx
                        <dl className="mt-4 space-y-3 border-t border-subtle pt-4">
                            <Butir judul="Paket luring">
                                {paket.kalimat}
                                {paket.aktif ? <PintuPaket tripId={hariH.id} /> : null}
                            </Butir>
                            <Butir judul="Lokasi">{lokasi}</Butir>
```

`resources/js/pages/Trips/Hike.tsx`:

1. Ganti:

```tsx
import { usePage } from '@inertiajs/react';
import { teksLama } from '@/components/mendaki/hitung';
```

dengan:

```tsx
import { usePage } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import { teksLama } from '@/components/mendaki/hitung';
```

2. Ganti:

```tsx
import { useJejakPendakian } from '@/components/trips/usePendakian';
import type { PropsBersama } from '@/types';
```

dengan:

```tsx
import { useJejakPendakian } from '@/components/trips/usePendakian';
import { Sakelar } from '@/components/ui/Sakelar';
import { useDataPaket, useSimpanSesiPaket } from '@/luring/useLapanganPaket';
import type { PropsBersama } from '@/types';
```

3. Ganti:

```tsx
 * diminta di halaman ini dan tidak di tempat lain (PRD §120); hitungannya di perangkat.
 */
export default function Hike() {
    const data = usePage<Props>().props;
    const mode = useModeLapangan(data);
```

dengan:

```tsx
 * diminta di halaman ini dan tidak di tempat lain (PRD §120); hitungannya di perangkat.
 *
 * Bila paket trip ini ada di perangkat (sakelar paket menyala), mode lapangan daring menyimpan
 * keadaan sesinya ke paket, dan sakelar "Pakai paket saja" membaca hanya dari paket (spec v3
 * bagian 10.2). Mengganti sumber memasang ulang halaman, jadi keadaannya dibaca ulang dari
 * sumber yang baru.
 */
export default function Hike() {
    const props = usePage<Props>().props;
    const kunci = props.perangkat?.kunci ?? null;
    const paketAktif = props.sumber === 'daring' && props.perangkat?.paket === true;
    const dataPaket = useDataPaket(kunci, paketAktif, props.trip.id);
    const [hanyaPaket, setHanyaPaket] = useState(false);
    const data = hanyaPaket && dataPaket ? dataPaket : props;

    return (
        <Lapangan
            key={data.sumber}
            data={data}
            kunci={kunci}
            simpanSesi={paketAktif && !hanyaPaket}
            pengaturan={
                dataPaket ? (
                    <Sakelar
                        judul="Pakai paket saja"
                        keterangan="Membaca hanya dari paket di perangkat ini walau ada sinyal; cara menguji paket sebelum berangkat."
                        nyala={hanyaPaket}
                        onUbah={setHanyaPaket}
                    />
                ) : null
            }
        />
    );
}

function Lapangan({ data, kunci, simpanSesi, pengaturan }: { data: DataLapangan; kunci: string | null; simpanSesi: boolean; pengaturan: ReactNode }) {
    const mode = useModeLapangan(data);
```

4. Ganti:

```tsx
    const daring = data.sumber === 'daring';

    useJejakPendakian(data.rekamJejak && data.trip.berlangsung, data.sesiId, data.urls.jejak);
```

dengan:

```tsx
    const daring = data.sumber === 'daring';

    useSimpanSesiPaket(kunci, simpanSesi, data, mode);
    useJejakPendakian(data.rekamJejak && data.trip.berlangsung, data.sesiId, data.urls.jejak);
```

5. Ganti:

```tsx
            )}
            <ModeLapangan data={data} mode={mode} selesai={daring ? <SelesaiLapangan data={data} /> : null} />
        </TataLetakPendakian>
```

dengan:

```tsx
            )}
            {daring ? null : (
                <p className="mt-4 rounded-kartu border-[1.5px] border-dashed border-control bg-surface p-4 text-isi text-primary">
                    Dari paket luring. Paket luring membantu mengikuti jalur resmi. Bawa cadangan: peta cetak atau perangkat lain. Tiba di pos, jeda, dan
                    lanjut disimpan di perangkat sampai terkirim; selesaikan pendakian saat sinyal kembali.
                </p>
            )}
            <ModeLapangan data={data} mode={mode} selesai={daring ? <SelesaiLapangan data={data} /> : null} pengaturan={pengaturan} />
        </TataLetakPendakian>
```

`resources/js/pages/Trips/Show.tsx`:

1. Ganti:

```tsx
import { FormulirSelesai } from '@/components/trips/FormulirSelesai';
import { KepalaTrip } from '@/components/trips/KepalaTrip';
```

dengan:

```tsx
import { FormulirSelesai } from '@/components/trips/FormulirSelesai';
import { PintuPaket } from '@/components/mendaki/PintuPaket';
import { KepalaTrip } from '@/components/trips/KepalaTrip';
```

2. Ganti:

```tsx
export default function Show() {
    const { kepala, trip, langkah, fit, penilaian, izin, hasilPendakian, completionStates, alasanBatal, penolakan, pengingat, keselamatan, kelola, flash } = usePage<Props>().props;
    const [memulai, setMemulai] = useState(false);
```

dengan:

```tsx
export default function Show() {
    const { kepala, trip, langkah, fit, penilaian, izin, hasilPendakian, completionStates, alasanBatal, penolakan, pengingat, keselamatan, kelola, flash, perangkat } = usePage<Props>().props;
    const [memulai, setMemulai] = useState(false);
```

3. Ganti:

```tsx
                        {keselamatan ? <KeselamatanTrip keselamatan={keselamatan} /> : null}

                        {trip.catatan ? (
```

dengan:

```tsx
                        {keselamatan ? <KeselamatanTrip keselamatan={keselamatan} /> : null}

                        {trip.aktif && perangkat?.paket ? (
                            <Card title="Paket lapangan luring" subtitle="Paket luring membantu mengikuti jalur resmi. Bawa cadangan: peta cetak atau perangkat lain.">
                                <PintuPaket tripId={trip.id} />
                            </Card>
                        ) : null}

                        {trip.catatan ? (
```

`resources/js/types/index.d.ts`:

1. Ganti:

```ts
    peta: { styleUrl: string | null; rasterTiles: string[]; attribution: string; maxZoom: number };
    flash: {
```

dengan:

```ts
    peta: { styleUrl: string | null; rasterTiles: string[]; attribution: string; maxZoom: number };
    /** Kunci penyimpanan perangkat per pengguna dan sakelar paket lapangan (W6-R10); null untuk tamu. */
    perangkat: { kunci: string; paket: boolean } | null;
    flash: {
```

`tests/Pratinjau/HalamanPratinjauTest.php`:

1. Ganti:

```php
    }
}
```

dengan:

```php
    }

    /**
     * Tab Mendaki dengan sakelar paket menyala (W6, ruling W6-R3): pintu unduh paket di
     * pratinjau hari-H, sebelum ada paket di perangkat.
     */
    public function test_mendaki_paket(): void
    {
        config(['hiking.field_pack.enabled' => true]);
        $this->sebagaiBudi();

        $this->tulisPratinjau('mendaki-paket', $this->get(self::ASAL.'/mendaki'), 'Track/Index');
    }
}
```

- [ ] **Step 4: Build dan tipe.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `npx tsc --noEmit`
Expected: `✓ built`; tsc tanpa keluaran galat.

- [ ] **Step 5: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Support/KunciPerangkat.php app/Http/Middleware/HandleInertiaRequests.php tests/Feature/FieldPackDeviceTest.php tests/Feature/ShellFeedbackTest.php tests/Pratinjau/HalamanPratinjauTest.php`, lalu `php artisan test --compact --filter='FieldPackDevice|ShellFeedback|TrackTab|ReminderSheet'`
Expected: PASS. Pada simulasi rencana: 38 test lulus (filter merah ditambah `TrackTabTest` dan `ReminderSheetTest`).

- [ ] **Step 6: Suite, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 2.054 test, 2.052 lulus, 2 dilewati (sebelum tugas: 2.051).

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `php artisan test --compact --testsuite=Pratinjau`
Expected: PASS; halaman baru `mendaki-paket`. Pada simulasi rencana: 29 test lulus.

- [ ] **Step 7: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 11: Keluar dan pergantian akun

Spec bagian 10.6; induk W6 butir 7 (`LogoutClearsDeviceDataTest` dan test sumber nama basis data `jejak.js`); ruling
W6-R10. Keluar memeriksa titik jejak yang belum terkirim dan meminta keputusan pendaki, lalu menghapus basis data
paket, basis data jejak (milik pengguna dan basis data lama tanpa pemilik), cache `pack-*`, dan OPFS, baru memanggil
rute logout; respons logout mengirim `Clear-Site-Data: "storage"` sebagai lapis kedua. Basis data `jejak.js` kini
`jejak-pendakian-<kunci>` dan perekam menolak berjalan tanpa kunci. Halaman pertama akun lain di peramban yang sama
menghapus data perangkat akun sebelumnya. `encryptHistory` dan `clearHistory` tidak dibangun (izin akhir 9, Konflik
5).

**Files:**
- Create: `resources/js/luring/keluar.ts`
- Modify: `app/Http/Controllers/Auth/AuthenticatedSessionController.php`, `resources/js/jejak.js`, `resources/js/components/trips/usePendakian.ts`, `resources/js/pages/Trips/Hike.tsx`, `resources/js/layouts/MenuAkun.tsx`, `resources/js/components/akun/PintuAkun.tsx`, `resources/js/layouts/AppLayout.tsx`, `resources/js/components/trips/TataLetakPendakian.tsx`
- Test: `tests/Feature/LogoutClearsDeviceDataTest.php` (baru), `tests/Feature/PageHeaderContractTest.php`

**Interfaces:**
- Consumes: `hapusBasisData()`, `kunciTersimpan()`, `lupakanKunci()` (Tugas 9); prop `perangkat` (Tugas 10); `window.muatJejak()` (`resources/js/dasar.js`); rute `logout` (Wayfinder `logout` dari `@/routes`).
- Produces: `keluar.ts`: `bersihkanPerangkat(kunci)`, `titikBelumTerkirim(kunci)`, `keluar(kunci)`, `usePenjagaPerangkat()`; `jejak.js`: `AWALAN_DB` (`jejak-pendakian-`), `DB_LAMA` (`jejak-pendakian`), `aturPengguna(kunci)`, `jumlahTitik(nama)`, `hapusBasisData(nama)`; `useJejakPendakian(aktif, sesiId, urlKirim, kunci)`; header `Clear-Site-Data: "storage"` pada respons logout.

- [ ] **Step 1: Tulis test yang gagal.** `PageHeaderContractTest` mengikuti tombol Keluar yang kini memanggil
`keluar()` (niatnya tetap: Profil memuat pintu konsol dan Keluar).

Buat `tests/Feature/LogoutClearsDeviceDataTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Keluar dan berganti akun (spec v3 bagian 10.6; rencana induk W6 butir 7; ruling W6-R10).
 *
 * Halaman memeriksa jejak yang belum terkirim dan meminta keputusan pendaki, lalu menghapus
 * basis data paket, basis data jejak, cache pack-*, dan OPFS sebelum keluar; respons logout
 * mengirim Clear-Site-Data sebagai lapis kedua. Perilaku penghapusan di peramban butuh IndexedDB
 * sungguhan dan diuji manual di tugas verifikasi akhir (Q17); test ini menjaga server dan urutan
 * di sumbernya.
 */
class LogoutClearsDeviceDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_logout_asks_the_browser_to_clear_this_origins_storage(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('logout'))
            ->assertRedirect('/')
            ->assertHeader('Clear-Site-Data', '"storage"');

        $this->assertGuest();
    }

    /**
     * Jejak yang belum terkirim tidak dihapus diam-diam: pendaki ditanya lebih dulu, dan baru
     * sesudah setuju data perangkat dihapus lalu sesi diakhiri.
     */
    public function test_the_page_asks_about_unsent_tracks_before_clearing_the_device(): void
    {
        $keluar = File::get(resource_path('js/luring/keluar.ts'));

        $tanya = strpos($keluar, 'window.confirm(`Ada ${sisa} titik jejak pendakian yang belum terkirim.');
        $hapus = strpos($keluar, 'await bersihkanPerangkat(kunci);');
        $akhiri = strpos($keluar, 'router.post(logout.url());');

        $this->assertNotFalse($tanya);
        $this->assertLessThan($hapus, $tanya);
        $this->assertLessThan($akhiri, $hapus);

        foreach (['tugas.push(hapusBasisData(kunci));', 'jejak.hapusBasisData(jejak.DB_LAMA)', 'jejak.hapusBasisData(`${jejak.AWALAN_DB}${kunci}`)', "n.startsWith('pack-')", 'akar.removeEntry(nama, { recursive: true })', 'lupakanKunci();'] as $langkah) {
            $this->assertStringContainsString($langkah, $keluar);
        }

        foreach (['layouts/MenuAkun.tsx', 'components/akun/PintuAkun.tsx'] as $berkas) {
            $isi = File::get(resource_path('js/'.$berkas));

            $this->assertStringContainsString('onClick={() => void keluar(perangkat?.kunci ?? null)}', $isi, $berkas);
            $this->assertStringNotContainsString("router.post('/logout')", $isi, $berkas);
        }
    }

    /**
     * Celah lama jejak.js (spec v3 bagian 10.6): satu basis data untuk siapa pun yang masuk di
     * peramban itu. Kini namanya memakai kunci perangkat pengguna, dan perekam menolak berjalan
     * tanpa kunci.
     */
    public function test_the_track_database_is_bound_to_the_user(): void
    {
        $jejak = File::get(resource_path('js/jejak.js'));
        $kait = File::get(resource_path('js/components/trips/usePendakian.ts'));

        $this->assertStringContainsString("export const AWALAN_DB = 'jejak-pendakian-';", $jejak);
        $this->assertStringContainsString('const nama = `${AWALAN_DB}${kunci}`;', $jejak);
        $this->assertStringContainsString("return Promise.reject(new Error('Pengguna perangkat belum diatur untuk perekam jejak.'));", $jejak);
        $this->assertStringNotContainsString('indexedDB.open(NAMA_DB', $jejak);
        $this->assertLessThan(strpos($kait, 'berhenti = jejak.mulaiMerekam(sesiId);'), strpos($kait, 'jejak.aturPengguna(kunci);'));
    }

    /**
     * Berganti akun tanpa keluar (sesi kedaluwarsa, akun lain masuk): data perangkat akun
     * sebelumnya dihapus saat halaman pertama akun baru dibuka, termasuk di mode lapangan.
     */
    public function test_switching_accounts_clears_the_previous_users_device_data(): void
    {
        $keluar = File::get(resource_path('js/luring/keluar.ts'));

        $this->assertStringContainsString('if (kunci && lama !== null && lama !== kunci) {', $keluar);
        $this->assertStringContainsString('void bersihkanPerangkat(lama);', $keluar);

        foreach (['layouts/AppLayout.tsx', 'components/trips/TataLetakPendakian.tsx'] as $berkas) {
            $this->assertStringContainsString('usePenjagaPerangkat();', File::get(resource_path('js/'.$berkas)), $berkas);
        }
    }
}
```

`tests/Feature/PageHeaderContractTest.php`:

1. Ganti:

```php
        $this->assertStringContainsString('{pengguna.isAdmin ? <TautanMenu href="/admin/trails">Admin</TautanMenu> : null}', $pintu);
        $this->assertStringContainsString("onClick={() => router.post('/logout')}", $pintu);
        $this->assertMatchesRegularExpression('/>\s*Keluar\s*<\/button>/', $pintu);
```

dengan:

```php
        $this->assertStringContainsString('{pengguna.isAdmin ? <TautanMenu href="/admin/trails">Admin</TautanMenu> : null}', $pintu);
        // W6 (ruling W6-R10): Keluar membersihkan data perangkat lebih dulu lewat keluar(), yang
        // sesudahnya memanggil rute logout yang sama.
        $this->assertStringContainsString('onClick={() => void keluar(perangkat?.kunci ?? null)}', $pintu);
        $this->assertMatchesRegularExpression('/>\s*Keluar\s*<\/button>/', $pintu);
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='LogoutClearsDeviceData|PageHeaderContract'`
Expected: FAIL. Pada simulasi rencana: 9 test, 4 lulus, 3 gagal, 2 error. Yang merah: empat test `LogoutClearsDeviceDataTest` (respons keluar tanpa `Clear-Site-Data`, basis data jejak belum per pengguna, `keluar.ts` belum ada) dan `PageHeaderContractTest::test_the_profile_page_keeps_the_console_doors_and_sign_out` (tombol Keluar belum memanggil `keluar()`).

- [ ] **Step 3: Tulis implementasinya.**

Buat `resources/js/luring/keluar.ts`:

```ts
import { router, usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { logout } from '@/routes';
import type { PropsBersama } from '@/types';
import { hapusBasisData, kunciTersimpan, lupakanKunci } from './simpan';

/**
 * Data perangkat saat keluar atau berganti akun (spec v3 bagian 10.6; rencana induk W6 butir 7;
 * ruling W6-R10). Keluar memeriksa jejak yang belum terkirim dan meminta keputusan pendaki, lalu
 * menghapus basis data paket, basis data jejak, cache `pack-*`, dan OPFS. Respons logout server
 * mengirim `Clear-Site-Data: "storage"` sebagai lapis kedua. Berganti akun tanpa keluar (sesi
 * kedaluwarsa, akun lain masuk) menghapus data akun sebelumnya saat halaman pertama dibuka.
 */

interface ModulJejak {
    AWALAN_DB: string;
    DB_LAMA: string;
    jumlahTitik: (nama: string) => Promise<number>;
    hapusBasisData: (nama: string) => Promise<void>;
}

async function modulJejak(): Promise<ModulJejak | null> {
    return typeof window.muatJejak === 'function' ? ((await window.muatJejak()) as ModulJejak) : null;
}

async function kosongkanOpfs(): Promise<void> {
    const akar = (await navigator.storage?.getDirectory?.()) as (FileSystemDirectoryHandle & { keys(): AsyncIterable<string> }) | undefined;

    if (!akar) {
        return;
    }

    for await (const nama of akar.keys()) {
        await akar.removeEntry(nama, { recursive: true });
    }
}

/** Menghapus semua data perangkat milik satu kunci, ditambah data lama tanpa pemilik. */
export async function bersihkanPerangkat(kunci: string | null): Promise<void> {
    const jejak = await modulJejak().catch(() => null);
    const tugas: Array<Promise<unknown>> = [kosongkanOpfs()];

    if (kunci) {
        tugas.push(hapusBasisData(kunci));
    }

    if (jejak) {
        tugas.push(jejak.hapusBasisData(jejak.DB_LAMA));

        if (kunci) {
            tugas.push(jejak.hapusBasisData(`${jejak.AWALAN_DB}${kunci}`));
        }
    }

    if ('caches' in window) {
        tugas.push(caches.keys().then((nama) => Promise.all(nama.filter((n) => n.startsWith('pack-')).map((n) => caches.delete(n)))));
    }

    await Promise.allSettled(tugas);
    lupakanKunci();
}

/** Titik jejak yang belum terkirim milik pengguna ini, ditambah basis data lama tanpa pemilik. */
export async function titikBelumTerkirim(kunci: string | null): Promise<number> {
    const jejak = await modulJejak();

    if (!jejak) {
        return 0;
    }

    const lama = await jejak.jumlahTitik(jejak.DB_LAMA);

    return lama + (kunci ? await jejak.jumlahTitik(`${jejak.AWALAN_DB}${kunci}`) : 0);
}

export async function keluar(kunci: string | null): Promise<void> {
    const sisa = await titikBelumTerkirim(kunci).catch(() => 0);

    if (
        sisa > 0 &&
        !window.confirm(`Ada ${sisa} titik jejak pendakian yang belum terkirim. Keluar akan menghapusnya dari perangkat ini. Pilih Batal, lalu buka mode lapangan selagi ada sinyal untuk mengirimnya.`)
    ) {
        return;
    }

    await bersihkanPerangkat(kunci);
    router.post(logout.url());
}

/** Penjaga ganti akun: data perangkat akun lain di peramban ini dihapus sebelum dipakai. */
export function usePenjagaPerangkat(): void {
    const { perangkat } = usePage<PropsBersama>().props;
    const kunci = perangkat?.kunci ?? null;

    useEffect(() => {
        const lama = kunciTersimpan();

        if (kunci && lama !== null && lama !== kunci) {
            void bersihkanPerangkat(lama);
        }
    }, [kunci]);
}
```

`app/Http/Controllers/Auth/AuthenticatedSessionController.php`:

1. Ganti:

```php
        $logout();

        return redirect('/');
    }
```

dengan:

```php
        $logout();

        // Lapis kedua penghapusan data perangkat (spec v3 bagian 10.6; ruling W6-R10): halaman sudah
        // menghapus paket, jejak, cache paket, dan OPFS sebelum memanggil rute ini; peramban yang
        // mendukung Clear-Site-Data juga mengosongkan penyimpanan origin ini. Cookie tidak ikut.
        return redirect('/')->header('Clear-Site-Data', '"storage"');
    }
```

`resources/js/components/akun/PintuAkun.tsx`:

1. Ganti:

```tsx
import { router, usePage } from '@inertiajs/react';
import { Card } from '@/components/ui/Card';
import { TautanMenu, tombolMenu } from '@/layouts/NavLinks';
import type { PropsBersama } from '@/types';
```

dengan:

```tsx
import { usePage } from '@inertiajs/react';
import { Card } from '@/components/ui/Card';
import { TautanMenu, tombolMenu } from '@/layouts/NavLinks';
import { keluar } from '@/luring/keluar';
import type { PropsBersama } from '@/types';
```

2. Ganti:

```tsx
export function PintuAkun() {
    const { auth } = usePage<PropsBersama>().props;
    const pengguna = auth.user;
```

dengan:

```tsx
export function PintuAkun() {
    const { auth, perangkat } = usePage<PropsBersama>().props;
    const pengguna = auth.user;
```

3. Ganti:

```tsx
                {pengguna.isAdmin ? <TautanMenu href="/admin/trails">Admin</TautanMenu> : null}
                <button type="button" onClick={() => router.post('/logout')} className={tombolMenu}>
                    Keluar
```

dengan:

```tsx
                {pengguna.isAdmin ? <TautanMenu href="/admin/trails">Admin</TautanMenu> : null}
                <button type="button" onClick={() => void keluar(perangkat?.kunci ?? null)} className={tombolMenu}>
                    Keluar
```

`resources/js/components/trips/TataLetakPendakian.tsx`:

1. Ganti:

```tsx
import { kelas } from '@/lib/kelas';

export type TemaLapangan = 'terang' | 'gelap';
```

dengan:

```tsx
import { kelas } from '@/lib/kelas';
import { usePenjagaPerangkat } from '@/luring/keluar';

export type TemaLapangan = 'terang' | 'gelap';
```

2. Ganti:

```tsx
    const [tema, setTema] = useState<TemaLapangan>(() => (window.temaGelapAktif?.() ? 'gelap' : 'terang'));

    return (
```

dengan:

```tsx
    const [tema, setTema] = useState<TemaLapangan>(() => (window.temaGelapAktif?.() ? 'gelap' : 'terang'));

    usePenjagaPerangkat();

    return (
```

`resources/js/components/trips/usePendakian.ts`:

1. Ganti:

```ts
interface ModulJejak {
    mulaiMerekam: (sesi: number) => () => void;
```

dengan:

```ts
interface ModulJejak {
    aturPengguna: (kunci: string) => void;
    mulaiMerekam: (sesi: number) => () => void;
```

2. Ganti:

```ts
 * saat halaman dibuka untuk jejak pendakian sebelumnya yang belum sempat terkirim.
 */
export function useJejakPendakian(aktif: boolean, sesiId: number | null, urlKirim: string | null) {
    useEffect(() => {
        if (!aktif || sesiId === null || urlKirim === null || typeof window.muatJejak !== 'function') {
            return;
```

dengan:

```ts
 * saat halaman dibuka untuk jejak pendakian sebelumnya yang belum sempat terkirim.
 *
 * Basis data jejak diikat ke pengguna perangkat (W6, ruling W6-R10), jadi tanpa kunci perangkat
 * tidak ada yang direkam.
 */
export function useJejakPendakian(aktif: boolean, sesiId: number | null, urlKirim: string | null, kunci: string | null) {
    useEffect(() => {
        if (!aktif || sesiId === null || urlKirim === null || kunci === null || typeof window.muatJejak !== 'function') {
            return;
```

3. Ganti:

```ts
                const jejak = modul as ModulJejak;
                berhenti = jejak.mulaiMerekam(sesiId);
```

dengan:

```ts
                const jejak = modul as ModulJejak;
                jejak.aturPengguna(kunci);
                berhenti = jejak.mulaiMerekam(sesiId);
```

4. Ganti:

```ts
        };
    }, [aktif, sesiId, urlKirim]);
}
```

dengan:

```ts
        };
    }, [aktif, sesiId, urlKirim, kunci]);
}
```

`resources/js/jejak.js`:

1. Ganti:

```js
 * justru paling banyak dipakai.
 */

const NAMA_DB = 'jejak-pendakian';
const TOKO = 'titik';

let dbTerbuka = null;

function buka() {
    dbTerbuka ??= new Promise((selesai, gagal) => {
        const permintaan = indexedDB.open(NAMA_DB, 1);

        permintaan.onupgradeneeded = () => {
```

dengan:

```js
 * justru paling banyak dipakai.
 *
 * Basis datanya per pengguna sejak W6 (spec v3 bagian 10.6; ruling W6-R10):
 * `jejak-pendakian-<kunci>`, dengan kunci perangkat dari server (App\Support\KunciPerangkat).
 * Sebelumnya satu basis data `jejak-pendakian` dipakai siapa pun yang masuk di peramban itu.
 * Basis data lama itu tidak dibaca lagi untuk dikirim, karena pemiliknya tidak diketahui; ia
 * dihitung dalam peringatan sebelum keluar dan dihapus bersama data perangkat lainnya.
 */

export const AWALAN_DB = 'jejak-pendakian-';
export const DB_LAMA = 'jejak-pendakian';
const TOKO = 'titik';

let namaDb = null;
let dbTerbuka = null;

/** Mengikat perekam ke pengguna perangkat; dipanggil sebelum merekam atau mengirim. */
export function aturPengguna(kunci) {
    const nama = `${AWALAN_DB}${kunci}`;

    if (nama !== namaDb) {
        namaDb = nama;
        dbTerbuka = null;
    }
}

function bukaNama(nama) {
    return new Promise((selesai, gagal) => {
        const permintaan = indexedDB.open(nama, 1);

        permintaan.onupgradeneeded = () => {
```

2. Ganti:

```js
        permintaan.onerror = () => gagal(permintaan.error);
    });

    return dbTerbuka;
```

dengan:

```js
        permintaan.onerror = () => gagal(permintaan.error);
    });
}

function buka() {
    if (namaDb === null) {
        return Promise.reject(new Error('Pengguna perangkat belum diatur untuk perekam jejak.'));
    }

    dbTerbuka ??= bukaNama(namaDb);

    return dbTerbuka;
```

3. Ganti:

```js
        titik.forEach((t) => toko.delete([t.sesi, t.recorded_at]));
    }));
}

/**
```

dengan:

```js
        titik.forEach((t) => toko.delete([t.sesi, t.recorded_at]));
    }));
}

/**
 * Jumlah titik yang belum terkirim di perangkat ini untuk satu basis data, tanpa membuatnya bila
 * belum ada: peringatan sebelum keluar akun membaca ini (spec v3 bagian 10.6).
 */
export async function jumlahTitik(nama) {
    const ada = (await indexedDB.databases?.())?.some((d) => d.name === nama) ?? true;

    if (!ada) {
        return 0;
    }

    const db = await bukaNama(nama);

    try {
        if (!db.objectStoreNames.contains(TOKO)) {
            return 0;
        }

        return await new Promise((selesai, gagal) => {
            const permintaan = db.transaction(TOKO, 'readonly').objectStore(TOKO).count();
            permintaan.onsuccess = () => selesai(permintaan.result);
            permintaan.onerror = () => gagal(permintaan.error);
        });
    } finally {
        db.close();
    }
}

/** Menghapus basis data jejak satu nama; tab lain yang masih memegangnya menunda, bukan menggagalkan. */
export function hapusBasisData(nama) {
    if (nama === namaDb) {
        namaDb = null;
        dbTerbuka?.then((db) => db.close()).catch(() => {});
        dbTerbuka = null;
    }

    return new Promise((selesai, gagal) => {
        const permintaan = indexedDB.deleteDatabase(nama);
        permintaan.onsuccess = () => selesai();
        permintaan.onerror = () => gagal(permintaan.error);
        permintaan.onblocked = () => selesai();
    });
}

/**
```

`resources/js/layouts/AppLayout.tsx`:

1. Ganti:

```tsx
import { WadahToast, useToastUrungkan } from '@/components/ui/ToastUrungkan';
import type { PropsBersama } from '@/types';
```

dengan:

```tsx
import { WadahToast, useToastUrungkan } from '@/components/ui/ToastUrungkan';
import { usePenjagaPerangkat } from '@/luring/keluar';
import type { PropsBersama } from '@/types';
```

2. Ganti:

```tsx
    }, []);
    const layanan = useMemo<NilaiKerangka>(() => ({ tampilkanToastMilik: tampilkanMilik, tutupToastMilik: tutupMilik, bukaPengingat }), [tampilkanMilik, tutupMilik, bukaPengingat]);
```

dengan:

```tsx
    }, []);
    // Data perangkat akun lain di peramban ini dihapus sebelum dipakai (W6, ruling W6-R10).
    usePenjagaPerangkat();
    const layanan = useMemo<NilaiKerangka>(() => ({ tampilkanToastMilik: tampilkanMilik, tutupToastMilik: tutupMilik, bukaPengingat }), [tampilkanMilik, tutupMilik, bukaPengingat]);
```

`resources/js/layouts/MenuAkun.tsx`:

1. Ganti:

```tsx
import { kelas } from '@/lib/kelas';
import { profile } from '@/routes';
```

dengan:

```tsx
import { kelas } from '@/lib/kelas';
import { keluar } from '@/luring/keluar';
import { profile } from '@/routes';
```

2. Ganti:

```tsx
export function MenuAkun() {
    const { auth } = usePage<PropsBersama>().props;
    const menu = useRef<HTMLDivElement>(null);
```

dengan:

```tsx
export function MenuAkun() {
    const { auth, perangkat } = usePage<PropsBersama>().props;
    const menu = useRef<HTMLDivElement>(null);
```

3. Ganti:

```tsx
                        {pengguna.isAdmin ? <TautanMenu href="/admin/trails">Admin</TautanMenu> : null}
                        <button type="button" onClick={() => router.post('/logout')} className={tombolMenu}>
                            Keluar
```

dengan:

```tsx
                        {pengguna.isAdmin ? <TautanMenu href="/admin/trails">Admin</TautanMenu> : null}
                        <button type="button" onClick={() => void keluar(perangkat?.kunci ?? null)} className={tombolMenu}>
                            Keluar
```

`resources/js/pages/Trips/Hike.tsx`:

1. Ganti:

```tsx
import { Sakelar } from '@/components/ui/Sakelar';
import { useDataPaket, useSimpanSesiPaket } from '@/luring/useLapanganPaket';
```

dengan:

```tsx
import { Sakelar } from '@/components/ui/Sakelar';
import { kunciTersimpan } from '@/luring/simpan';
import { useDataPaket, useSimpanSesiPaket } from '@/luring/useLapanganPaket';
```

2. Ganti:

```tsx
    const props = usePage<Props>().props;
    const kunci = props.perangkat?.kunci ?? null;
    const paketAktif = props.sumber === 'daring' && props.perangkat?.paket === true;
```

dengan:

```tsx
    const props = usePage<Props>().props;
    // Di shell luring props bersama server tidak ada; kunci perangkat dibaca dari localStorage.
    const kunci = props.perangkat?.kunci ?? kunciTersimpan();
    const paketAktif = props.sumber === 'daring' && props.perangkat?.paket === true;
```

3. Ganti:

```tsx
    useSimpanSesiPaket(kunci, simpanSesi, data, mode);
    useJejakPendakian(data.rekamJejak && data.trip.berlangsung, data.sesiId, data.urls.jejak);

    const keterangan = data.trip.berlangsung ? `${mode.dijeda ? 'Dijeda' : 'Berjalan'}, ${teksLama(mode.detik)} bergerak` : 'Belum dimulai';
```

dengan:

```tsx
    useSimpanSesiPaket(kunci, simpanSesi, data, mode);
    useJejakPendakian(data.rekamJejak && data.trip.berlangsung, data.sesiId, data.urls.jejak, kunci);

    const keterangan = data.trip.berlangsung ? `${mode.dijeda ? 'Dijeda' : 'Berjalan'}, ${teksLama(mode.detik)} bergerak` : 'Belum dimulai';
```

- [ ] **Step 4: Build dan tipe.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `npx tsc --noEmit`
Expected: `✓ built`; tsc tanpa keluaran galat.

- [ ] **Step 5: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Http/Controllers/Auth/AuthenticatedSessionController.php tests/Feature/LogoutClearsDeviceDataTest.php tests/Feature/PageHeaderContractTest.php`, lalu `php artisan test --compact --filter='LogoutClearsDeviceData|PageHeaderContract|Authentication|LocationPrivacy'`
Expected: PASS. Pada simulasi rencana: 23 test lulus (filter merah ditambah `AuthenticationTest` dan `LocationPrivacyTest`).

- [ ] **Step 6: Suite, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 2.058 test, 2.056 lulus, 2 dilewati (sebelum tugas: 2.054).

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `php artisan test --compact --testsuite=Pratinjau`
Expected: PASS. Pada simulasi rencana: 29 test lulus.

- [ ] **Step 7: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 12: Dokumen dan governance record GR-07

Spec bagian 15 Q7 dan bagian 16; aturan R-021 dan PRD §130; ruling W6-R1 dan W6-R3. Amandemen PRD yang ditunjuk Q7
(§13, §53, §55, §121 butir 8, dan baris Mendaki §145.1) ditulis bersama governance record GR-07 di §146 dengan tujuh
butir §130 dan keputusan terbuka untuk pemilik, satu baris log keputusan, dan kalimat penunjuk "governance record
berikutnya GR-08". `DESIGN.md` mencatat ukuran di jalur dan label "terakhir diketahui"; `docs/ARCHITECTURE.md` mencatat
sakelar, modul hitungan, kebijakan service worker, dan lokasi yang tidak dikirim; `docs/fitur/06-pendakian-progres.md`
memperbarui status implementasinya. `docs/KEBIJAKAN-DATA-PRIBADI.md` tidak disunting: kebijakan data di perangkat
ditulis pemilik (Q10).

**Files:**
- Modify: `docs/PRD.md`, `DESIGN.md`, `docs/ARCHITECTURE.md`, `docs/fitur/06-pendakian-progres.md`

**Interfaces:**
- Consumes: seluruh keluaran Tugas 1 sampai 11 (nama rute, kelas, sakelar, dan test yang dirujuk dokumen).
- Produces: GR-07 di `docs/PRD.md` §146; baris log keputusan `DK-36 (Q7)`.

- [ ] **Step 1: Pastikan penjaga dokumen hijau sebelum menyunting.**

Run: `php artisan test --compact --filter='PrdReferenceIntegrity|DesignDirection|TeamDocsIndex|RepoHygiene'`
Expected: PASS. Pada simulasi rencana: 31 test lulus sebelum dokumen disunting.

- [ ] **Step 2: Sunting dokumen.** Setiap jangkar satu kalimat atau baris tabel yang sudah diperiksa di repo nyata
pada uji kering (Pra-cek butir 11).

`DESIGN.md`:

1. Ganti:

```markdown
tema lapangan dengan ambang tema aplikasi.

### Tipografi
```

dengan:

```markdown
tema lapangan dengan ambang tema aplikasi.

Ukuran di jalur (W6): "Mulai pendakian" di tab Mendaki 66 px, Jeda, Lanjut, Selesai, dan Kartu darurat 60 px, kontrol
peta 48 px. Nama pos berikutnya memakai `text-tampilan`, tiga petak angka menulis "Belum ada" dengan huruf biasa bila
angkanya tidak diketahui. Tiba di pos satu-satunya gerak (`denyut-tiba`, `--dur-sedang`), dan ia ikut dipangkas gerak
dikurangi. Data dinamis dari paket luring memakai label "Terakhir diketahui" dengan ikon jam dan garis putus-putus,
atau "Status belum dapat dipastikan" bila Buka melewati ambangnya: kata, ikon, dan garis, bukan warna saja (R-013).

### Tipografi
```

`docs/ARCHITECTURE.md`:

1. Ganti:

```markdown
| Radius kedatangan checkpoint | `config/hiking.php` → `hike_mode` |
| Ambang kesegaran cuaca / laporan komunitas | `config/hiking.php` → `conditions` |
```

dengan:

```markdown
| Radius kedatangan checkpoint | `config/hiking.php` → `hike_mode` |
| Paket lapangan luring dan alat keselamatan (sakelar, ambang turun status Buka) | `config/hiking.php` → `field_pack` (`enabled`, `open_status_stale_after_hours`) dan `field_safety.enabled`, atau `FIELD_PACK_ENABLED` dan `FIELD_SAFETY_ENABLED`; keduanya mati sampai pemilik memutus Q10 dan Q14. Rutenya dijaga middleware `fitur` (`EnsureFeatureEnabled`) dan menjawab 404 selama mati |
| Hitungan mode lapangan (pembulatan jarak, naik lagi, tiba di pos) | `resources/js/components/mendaki/hitung.ts`, modul tanpa impor yang diuji dari PHPUnit lewat Node (`FieldModeFiguresTest`) |
| Ambang kesegaran cuaca / laporan komunitas | `config/hiking.php` → `conditions` |
```

2. Ganti:

```markdown
5. **OPEN di level gunung tidak berarti seluruh jalurnya OPEN.** Pembatasan turun ke bawah; kelonggaran tidak. (§42)
6. **Lokasi diminta hanya di Hike Mode**, tidak di halaman lain. (§83)
7. **Makna tidak pernah disampaikan lewat warna saja.** (§87)
```

dengan:

```markdown
5. **OPEN di level gunung tidak berarti seluruh jalurnya OPEN.** Pembatasan turun ke bawah; kelonggaran tidak. (§42)
6. **Lokasi diminta hanya di Hike Mode**, tidak di halaman lain, dan tidak dikirim ke server: mode lapangan menghitungnya di perangkat; server hanya menerima urutan pos yang dicapai dan waktu jeda (GR-07). (§83)
7. **Makna tidak pernah disampaikan lewat warna saja.** (§87)
```

3. Ganti:

```markdown
**RLS menyala tanpa satu pun policy, dan itu memang disengaja.** Supabase memberi peran `anon` dan `authenticated` hak penuh atas tabel di schema `public` lalu mengeksposnya lewat REST API dengan kunci yang memang dirancang untuk dipublikasikan. Aplikasi ini tidak pernah memakai PostgREST; ia terhubung langsung sebagai `postgres`, pemilik tabelnya, yang melewati RLS. Jadi RLS tanpa policy berarti "tolak semua" bagi REST API sekaligus "tidak berpengaruh" bagi Laravel. Advisor Supabase akan menandainya sebagai `rls_enabled_no_policy` level INFO; itu keadaan yang benar di sini, bukan pekerjaan yang belum selesai. Jangan menambahkan policy untuk meredakan advisor. Migrasinya juga mencabut GRANT, karena **TRUNCATE tidak tunduk pada RLS** dan tanpa pencabutan itu `audit_logs` masih dapat dikosongkan.

**Pustaka peta dimuat sesuai kebutuhan.** `resources/js/app.js` hanya memuat sebuah fungsi `window.muatPeta()`; MapLibre yang 787 kB baru diunduh ketika mode pendakian benar-benar menggambar peta. Menambahkan `import 'maplibre-gl'` kembali ke `app.js` akan mengembalikan beban itu ke seluruh halaman.
```

dengan:

```markdown
**RLS menyala tanpa satu pun policy, dan itu memang disengaja.** Supabase memberi peran `anon` dan `authenticated` hak penuh atas tabel di schema `public` lalu mengeksposnya lewat REST API dengan kunci yang memang dirancang untuk dipublikasikan. Aplikasi ini tidak pernah memakai PostgREST; ia terhubung langsung sebagai `postgres`, pemilik tabelnya, yang melewati RLS. Jadi RLS tanpa policy berarti "tolak semua" bagi REST API sekaligus "tidak berpengaruh" bagi Laravel. Advisor Supabase akan menandainya sebagai `rls_enabled_no_policy` level INFO; itu keadaan yang benar di sini, bukan pekerjaan yang belum selesai. Jangan menambahkan policy untuk meredakan advisor. Migrasinya juga mencabut GRANT, karena **TRUNCATE tidak tunduk pada RLS** dan tanpa pencabutan itu `audit_logs` masih dapat dikosongkan.

**Service worker menyimpan shell, tidak pernah isi** (spec v3 bagian 10.4, `resources/views/sw.blade.php`). Dokumen selalu dari jaringan dan jatuh ke `/offline` saat luring; hanya `/build/`, `/icons/`, dan `/fonts/` yang disimpan. Versi baru hanya membuang cache `shell-*` lama dan tidak pernah menyentuh `pack-*` (`ProgressiveWebAppTest::test_activate_never_deletes_pack_caches`). Paket lapangan diunduh dan diverifikasi oleh halaman (`resources/js/luring/paket.ts`) ke IndexedDB per pengguna `memount-luring-<kunci>`; shell luring (`resources/js/luring/shell.tsx`) membacanya tanpa jaringan. Keluar akun menghapus paket, jejak, cache paket, dan OPFS, lalu respons logout mengirim `Clear-Site-Data: "storage"`.

**Pustaka peta dimuat sesuai kebutuhan.** `resources/js/app.js` hanya memuat sebuah fungsi `window.muatPeta()`; MapLibre yang 787 kB baru diunduh ketika mode pendakian benar-benar menggambar peta. Menambahkan `import 'maplibre-gl'` kembali ke `app.js` akan mengembalikan beban itu ke seluruh halaman.
```

`docs/PRD.md`:

1. Ganti:

```markdown
1. **Nomor § adalah ID permanen, bukan halaman.** Setiap bagian membawa ID-nya di judul. Urutan baca disusun untuk manusia yang membaca dari atas, sehingga boleh berbeda dari urutan nomor. Untuk mencari §N, buka Lampiran A.
2. **Tidak ada ID yang dihapus atau dipakai ulang.** Bagian yang dipadatkan ke bagian lain tetap tercatat sebagai alias di Lampiran A, misalnya §137 dipadatkan ke §1. ID baru: bagian mulai §147 (kini terpakai sampai §161), fitur mulai FR-22 (terpakai sampai FR-27), aturan bisnis baru memakai nomor setelah BR-16 (belum ada), governance record berikutnya GR-07.
3. **Rujukan di kode tidak disentuh.** Karena ID tetap, ratusan rujukan `§N`, `BR-N`, dan `FR-N` di kode, test, dan dokumen tetap menunjuk isi yang sama. `tests/Feature/PrdReferenceIntegrityTest.php` menjaganya: setiap ID yang dirujuk harus punya heading atau alias di sini, indeks tidak boleh melompat, tidak ada ID yang dipakai dua kali, PRD hanya ada di `docs/PRD.md`, dan setiap berkas `.md` yang disebut dokumen hidup benar-benar ada.
```

dengan:

```markdown
1. **Nomor § adalah ID permanen, bukan halaman.** Setiap bagian membawa ID-nya di judul. Urutan baca disusun untuk manusia yang membaca dari atas, sehingga boleh berbeda dari urutan nomor. Untuk mencari §N, buka Lampiran A.
2. **Tidak ada ID yang dihapus atau dipakai ulang.** Bagian yang dipadatkan ke bagian lain tetap tercatat sebagai alias di Lampiran A, misalnya §137 dipadatkan ke §1. ID baru: bagian mulai §147 (kini terpakai sampai §161), fitur mulai FR-22 (terpakai sampai FR-27), aturan bisnis baru memakai nomor setelah BR-16 (belum ada), governance record berikutnya GR-08.
3. **Rujukan di kode tidak disentuh.** Karena ID tetap, ratusan rujukan `§N`, `BR-N`, dan `FR-N` di kode, test, dan dokumen tetap menunjuk isi yang sama. `tests/Feature/PrdReferenceIntegrityTest.php` menjaganya: setiap ID yang dirujuk harus punya heading atau alias di sini, indeks tidak boleh melompat, tidak ada ID yang dipakai dua kali, PRD hanya ada di `docs/PRD.md`, dan setiap berkas `.md` yang disebut dokumen hidup benar-benar ada.
```

2. Ganti:

```markdown
## §13 Non-goals

MVP tidak bertujuan menjadi: pusat komando darurat; aplikasi medis; marketplace; platform open trip; pemesanan porter; tiruan media sosial; platform navigasi lanjutan; platform navigasi offline; platform wearable; sistem keselamatan AI otonom; CRUD gunung; direktori wisata generik; aplikasi GPS semata; aplikasi ulasan semata; tiruan AllTrails, Traveloka, atau Strava.

Tambahan: **pemesanan izin pendakian tidak direplikasi**; produk menautkan sistem resmi pengelola (§153). Monetisasi dan kompetisi sosial tidak ditambahkan tanpa keputusan pemilik (R-021).
```

dengan:

```markdown
## §13 Non-goals

MVP tidak bertujuan menjadi: pusat komando darurat; aplikasi medis; marketplace; platform open trip; pemesanan porter; tiruan media sosial; platform navigasi lanjutan; platform navigasi offline penuh (paket lapangan luring §53 adalah sistem kedua yang terbatas, GR-07); platform wearable; sistem keselamatan AI otonom; CRUD gunung; direktori wisata generik; aplikasi GPS semata; aplikasi ulasan semata; tiruan AllTrails, Traveloka, atau Strava.

Tambahan: **pemesanan izin pendakian tidak direplikasi**; produk menautkan sistem resmi pengelola (§153). Monetisasi dan kompetisi sosial tidak ditambahkan tanpa keputusan pemilik (R-021).
```

3. Ganti:

```markdown
| **Trip** (Trips) | "Bagaimana saya memilih, merencanakan, dan bersiap?" | Rekomendasi Route Fit, banding maksimal 3 jalur yang dipilih dari simpanan (DK-05, **diputuskan atas delegasi pemilik**), trip, detail rencana (tanggal selesai, peserta), persiapan, cek kesiapan, pengingat (FR-24), tautan bagikan read-only (FR-23) | Banding tidak menghasilkan pemenang (R-016, §33). Tautan bagikan read-only dan tidak pernah menulis ke simpanan siapa pun. Kesiapan adalah dukungan keputusan, bukan izin medis atau hukum (§40) |
| **Mendaki** (Track) | "Apa yang saya butuhkan di jalur sekarang?" | Mulai pendakian, peta langsung, posisi atas izin, pos berikutnya dan sisa jarak, posisi pada profil elevasi, selesai | Mode lapangan minimal (§54). Lokasi hanya diminta di sini, atas persetujuan di perangkat, dan tidak dikirim ke server di luar rekam jejak yang opsional (§82, §83). Tidak menjanjikan pelacakan latar, deteksi keluar jalur, navigasi offline, atau SOS (§53) |
| **Progres** (Progress) | "Bagaimana perkembangan saya?" | Riwayat pendakian, statistik, target pribadi (FR-25), pencapaian, catatan pendakian manual (FR-25) | Pembandingnya diri sendiri (R-015), tanpa papan peringkat antarpendaki; riwayat tidak menaikkan level pengalaman (BR-11); pencapaian tidak pernah memberi hadiah pada kecepatan (§156) |
```

dengan:

```markdown
| **Trip** (Trips) | "Bagaimana saya memilih, merencanakan, dan bersiap?" | Rekomendasi Route Fit, banding maksimal 3 jalur yang dipilih dari simpanan (DK-05, **diputuskan atas delegasi pemilik**), trip, detail rencana (tanggal selesai, peserta), persiapan, cek kesiapan, pengingat (FR-24), tautan bagikan read-only (FR-23) | Banding tidak menghasilkan pemenang (R-016, §33). Tautan bagikan read-only dan tidak pernah menulis ke simpanan siapa pun. Kesiapan adalah dukungan keputusan, bukan izin medis atau hukum (§40) |
| **Mendaki** (Track) | "Apa yang saya butuhkan di jalur sekarang?" | Mulai pendakian, peta langsung, posisi atas izin, pos berikutnya dan sisa jarak, posisi pada profil elevasi, selesai; paket lapangan luring, kartu darurat, dan pesan rencana untuk kontak di balik sakelar (GR-07) | Mode lapangan minimal (§54). Lokasi hanya diminta di sini, atas persetujuan di perangkat, dan tidak dikirim ke server di luar rekam jejak yang opsional (§82, §83). Tidak menjanjikan pelacakan latar, deteksi keluar jalur, navigasi offline penuh, atau SOS (§53); data dinamis di paket selalu berlabel "terakhir diketahui" (GR-07) |
| **Progres** (Progress) | "Bagaimana perkembangan saya?" | Riwayat pendakian, statistik, target pribadi (FR-25), pencapaian, catatan pendakian manual (FR-25) | Pembandingnya diri sendiri (R-015), tanpa papan peringkat antarpendaki; riwayat tidak menaikkan level pengalaman (BR-11); pencapaian tidak pernah memberi hadiah pada kecepatan (§156) |
```

4. Ganti:

```markdown
MVP **tidak menjanjikan**: pelacakan latar terus-menerus; deteksi keluar jalur tingkat lanjut; navigasi offline; pusat komando darurat.

Perekaman jejak bersifat pilihan, default mati, dan dapat dihapus pemiliknya (`user_preferences.record_track`, `hike_track_points`). Jejak direkam di perangkat dan dikirim setelah pendaki mendapat sinyal (§10). Di tab Mendaki (§145), peta mengikuti posisi hanya selama mode pendakian aktif dan atas persetujuan di perangkat. Tanpa geometri jalur dan koordinat pos, mode pendakian tidak dapat menentukan pos terdekat, dan ia menyatakannya, bukan menebak (§108).
```

dengan:

```markdown
MVP **tidak menjanjikan**: pelacakan latar terus-menerus; deteksi keluar jalur tingkat lanjut; navigasi offline; pusat komando darurat.

**Amandemen DK-36** (GR-07): pos berikutnya, sisa jarak, naik lagi, dan tiba di pos dihitung di perangkat dari data jalur yang sudah dimuat, dan posisi pendaki tidak dikirim ke server; server hanya mencatat urutan pos yang dicapai dan waktu jeda. Satu pengguna hanya punya satu pendakian berlangsung. Paket lapangan luring (spec v3 bagian 10) adalah sistem kedua, bukan navigasi offline penuh: jalur dan pos tanpa peta dasar, rencana, aturan resmi bertanggal cek, dan potret data dinamis yang selalu berlabel "terakhir diketahui". Kartu darurat, versi cetak, dan pesan rencana untuk kontak melengkapinya. Paket dan alat keselamatan dibangun di balik sakelar `hiking.field_pack.enabled` dan `hiking.field_safety.enabled` yang mati sampai pemilik memutus kebijakan data di perangkat (Q10) dan telaah hukum (Q14). Tetap tidak dijanjikan: pelacakan latar, deteksi keluar jalur, berbagi lokasi langsung, dan pusat komando darurat.

Perekaman jejak bersifat pilihan, default mati, dan dapat dihapus pemiliknya (`user_preferences.record_track`, `hike_track_points`). Jejak direkam di perangkat dan dikirim setelah pendaki mendapat sinyal (§10). Di tab Mendaki (§145), peta mengikuti posisi hanya selama mode pendakian aktif dan atas persetujuan di perangkat. Tanpa geometri jalur dan koordinat pos, mode pendakian tidak dapat menentukan pos terdekat, dan ia menyatakannya, bukan menebak (§108).
```

5. Ganti:

```markdown
- **Peta digerbang data** (DK-06, **diputuskan atas delegasi pemilik**): peta hanya menggambar jalur yang bergeometri; koordinat gunung tidak dipakai sebagai pengganti titik jalur (temuan F2).
- **Tema gelap** (DK-18): basemap topo tetap, diredupkan lewat `raster-brightness-max`. Peta gelap tanpa kontur hanya atas permintaan pemilik; self-host PMTiles jalur masa depan.
```

dengan:

```markdown
- **Peta digerbang data** (DK-06, **diputuskan atas delegasi pemilik**): peta hanya menggambar jalur yang bergeometri; koordinat gunung tidak dipakai sebagai pengganti titik jalur (temuan F2).
- **Paket lapangan tanpa peta dasar** (DK-36, GR-07): paket luring tahap 1 tidak membawa ubin; peta luring menggambar garis jalur dan pos di kanvas polos berlabel "Peta dasar tidak tersedia luring". Prefetch ubin menunggu izin tertulis penyedia atau ubin self-hosted (spec v3 bagian 15 Q8).
- **Tema gelap** (DK-18): basemap topo tetap, diredupkan lewat `raster-brightness-max`. Peta gelap tanpa kontur hanya atas permintaan pemilik; self-host PMTiles jalur masa depan.
```

6. Ganti:

```markdown
7. Dataset MVP dikurasi.
8. PWA tidak berarti navigasi offline penuh.

## §122 Risiko produk
```

dengan:

```markdown
7. Dataset MVP dikurasi.
8. PWA tidak berarti navigasi offline penuh; paket lapangan luring adalah sistem kedua yang terbatas dan selalu menyebut umur datanya (GR-07).

## §122 Risiko produk
```

7. Ganti:

```markdown
- Apakah trip lama yang pengingatnya sudah menyala perlu dijadwalkan ulang sekali saat rilis.

### Log keputusan
```

dengan:

```markdown
- Apakah trip lama yang pengingatnya sudah menyala perlu dijadwalkan ulang sekali saat rilis.

### GR-07: Mode lapangan di perangkat dan paket lapangan luring sebagai sistem kedua (gelombang W6)

**Requirement changed.**

- §13: "platform navigasi offline" menjadi "platform navigasi offline penuh"; paket lapangan luring adalah sistem kedua
  yang terbatas (spec v3 bagian 10, DK-36).
- §53 FR-15: pos berikutnya, sisa jarak, naik lagi, dan tiba di pos dihitung di perangkat; posisi pendaki tidak lagi
  dikirim ke server (rute `trips.hike.position` dihapus) dan server hanya mencatat urutan pos yang dicapai dan waktu
  jeda. Satu pengguna hanya punya satu pendakian berlangsung. Paket lapangan luring, kartu darurat, versi cetak, dan
  pesan rencana untuk kontak dibangun di balik sakelar yang mati.
- §55: paket tahap 1 tanpa peta dasar; prefetch ubin menunggu Q8.
- §121 butir 8 dan baris Mendaki §145.1: paket disebut sebagai sistem kedua dengan data "terakhir diketahui".
- §52 FR-14: ringkasan pendakian punya varian selesai dan dibatalkan (DK-50) dengan alasan pembatalan opsional dari
  daftar tetap, angka yang dapat diubah pendaki, dan laporan kondisi yang dapat diubah sebelum dimoderasi.

**Reason.** Spec v3 bagian 10 (DK-36), yang disetujui pemilik 25 September 2026, menetapkan paket lapangan sebagai sistem
kedua, sementara §13, §53, §55, §121 butir 8, dan baris Mendaki §145.1 masih menyebut navigasi luring di luar MVP
(spec v3 bagian 15 Q7). Menentukan pos berikutnya tidak membutuhkan posisi di server, dan mengirim posisi tiap beberapa
detik bertentangan dengan minimisasi lokasi (§82).

**Impact.** Mode lapangan tetap berguna tanpa sinyal dan tidak lagi menyimpan posisi pendaki di server. Pengguna tidak
melihat paket, kartu darurat, versi cetak, maupun pesan rencana sampai pemilik menyalakan sakelarnya; selama mati, tab
Mendaki menulis kalimat jujur tanpa tombol unduh.

**Affected modules.** Trip (siklus, mode lapangan, ringkasan, jam kembali), Mendaki, Community (laporan dari
ringkasan), PWA (service worker dan halaman luring), Akun (keluar).

**Security impact.** Rute baru memakai Policy pemilik trip dan batas laju `personal_writes_per_minute`: tiba, jeda,
lanjut, angka pendakian, ubah laporan sebelum dimoderasi, jam kembali, cetak, serta manifest dan bagian paket
(`Cache-Control: private, no-store`). Rute posisi dihapus. Basis data perangkat diberi kunci HMAC id pengguna; keluar
akun menghapus paket, jejak, cache paket, dan OPFS, lalu respons logout mengirim `Clear-Site-Data: "storage"`. Paket
tidak memuat skor, keluaran AI, GPX komunitas, nama pelapor, atau rencana orang lain.

**Data impact.** Migrasi aditif yang diuji di SQLite dan PostgreSQL CI dan tidak dijalankan di Supabase sebelum izin
(Q2): indeks unik `hiking_sessions.trip_plan_id` (periksa duplikat lebih dulu, kueri di docblock migrasinya),
`hiking_sessions.paused_at` dan `paused_seconds`, `trip_plans.cancellation_reason`, `hiking_history.recorded_*` dan
`numbers_edited_at`, `trip_plans.expected_return_at` dan `registration_number`. Kolom `hiking_sessions.last_known_location`
dan `location_updated_at` tidak lagi ditulis; isinya yang sudah ada tetap tersimpan sampai pemilik memutus
pembersihannya.

**Scope impact.** Feature gate §131 butir 4 (keterpakaian di lapangan) dan butir 3 (kesadaran kondisi). Batas MVP tidak
melebar ke navigasi offline penuh: tanpa peta dasar, tanpa pelacakan latar, tanpa deteksi keluar jalur, tanpa berbagi
lokasi langsung.

**Keputusan terbuka untuk pemilik.**

- Q7: amandemen ini sendiri, termasuk batas tertulis "paket lapangan, bukan navigasi luring penuh".
- Q9: ambang turun status Buka di paket, bawaan 24 jam dari `verified_at` (`hiking.field_pack.open_status_stale_after_hours`).
- Q10 dan Q14: kapan `hiking.field_pack.enabled` dan `hiking.field_safety.enabled` dinyalakan; kebijakan data di
  perangkat ditulis pemilik di `docs/KEBIJAKAN-DATA-PRIBADI.md` lebih dulu.
- Pembersihan posisi lama di `hiking_sessions` pada Supabase.

### Log keputusan
```

8. Ganti:

```markdown
| DK-29 | Batas banding diselaraskan ke 3 (DK-05); PRODUCT.md diperbaiki mengikutinya | Delegasi pemilik (mengikuti DK-05) | 25 Sep 2026 | §33, PRODUCT.md |

**Keputusan pemilik lain yang tercatat:**
```

dengan:

```markdown
| DK-29 | Batas banding diselaraskan ke 3 (DK-05); PRODUCT.md diperbaiki mengikutinya | Delegasi pemilik (mengikuti DK-05) | 25 Sep 2026 | §33, PRODUCT.md |
| DK-36 (Q7) | Paket lapangan luring sebagai sistem kedua dan mode lapangan yang dihitung di perangkat; paket dan alat keselamatan dibangun di balik sakelar mati sampai Q10 dan Q14 | Keputusan pemilik tertunda (Q7); ditulis agen menurut spec v3 yang disetujui (pola W3 P8) | 7 Okt 2026 | §13, §53, §55, §121, §145, GR-07 |

**Keputusan pemilik lain yang tercatat:**
```

`docs/fitur/06-pendakian-progres.md`:

1. Ganti:

```markdown
| Tidak ada trip yang dapat dimulai | Pintu tab menjelaskan dan menawarkan trip lain | Ada: `tests/Feature/TrackTabTest.php` |
| Izin lokasi ditolak | Kalimat jujur; peta dan pos tetap terbaca | Ada di `resources/js/components/trips/usePendakian.ts`; keadaan khusus prototype 11 menyusul W6 |
| Sinyal hilang | Data yang sudah dibuka tetap terbaca; jejak diantrekan | Sebagian: halaman yang sudah dimuat tetap terbaca, antrean jejak di `resources/js/jejak.js`; paket luring menyusul W6 (DK-36) |
| Riwayat kosong | Kalimat dan ajakan | Ada: prop `kosong` dari `HikerProgressController` |
```

dengan:

```markdown
| Tidak ada trip yang dapat dimulai | Pintu tab menjelaskan dan menawarkan trip lain | Ada: `tests/Feature/TrackTabTest.php` |
| Izin lokasi ditolak | Kalimat jujur; peta dan pos tetap terbaca | Ada: `resources/js/components/mendaki/usePosisiLapangan.ts` dan kalimat lokasi di `ModeLapangan.tsx`; tiba di pos dapat ditandai sendiri |
| Sinyal hilang | Data yang sudah dibuka tetap terbaca; jejak dan aksi diantrekan | Ada: hitungan di perangkat (`hitung.ts`), antrean aksi (`antrean.ts`), antrean jejak (`resources/js/jejak.js`); paket luring di balik sakelar (GR-07) |
| Riwayat kosong | Kalimat dan ajakan | Ada: prop `kosong` dari `HikerProgressController` |
```

2. Ganti:

```markdown
| Peta navigasi langsung | Ada | `app/Http/Controllers/Trips/HikeModeController.php`, `resources/js/pages/Trips/Hike.tsx`, `resources/js/components/trips/PetaPendakian.tsx`, `usePendakian.ts`, `resources/js/components/trips/TataLetakPendakian.tsx`, `tests/Feature/LocationPrivacyTest.php` |
| Titik berikutnya dan sisa waktu | Ada, sebagian | `app/Services/Trips/HikeCheckpointResolver.php`, `app/Services/CheckpointPaceService.php`, `tests/Feature/HikeModeCheckpointTest.php`; akurasi dan umur posisi, konfirmasi tiba di pos, dan rentang waktu bersumber menyusul W6 |
| Tab Mendaki | Ada | `app/Http/Controllers/Trips/TrackController.php`, `resources/js/pages/Track/Index.tsx`, `tests/Feature/TrackTabTest.php` |
```

dengan:

```markdown
| Peta navigasi langsung | Ada | `app/Http/Controllers/Trips/HikeModeController.php`, `resources/js/pages/Trips/Hike.tsx`, `resources/js/components/trips/PetaPendakian.tsx`, `usePendakian.ts`, `resources/js/components/trips/TataLetakPendakian.tsx`, `tests/Feature/LocationPrivacyTest.php` |
| Titik berikutnya dan sisa waktu | Ada | `app/Services/Trips/HalamanMendaki.php`, `app/Services/Trips/HikeCheckpointResolver.php`, `resources/js/components/mendaki/hitung.ts`, `tests/Feature/HikeModeCheckpointTest.php`, `tests/Feature/FieldModeFiguresTest.php` |
| Tab Mendaki | Ada | `app/Http/Controllers/Trips/TrackController.php`, `resources/js/pages/Track/Index.tsx`, `tests/Feature/TrackTabTest.php` |
```

3. Ganti:

```markdown
| Target pribadi dan catat manual (FR-25) | Belum ada | Migrasi rintisan kosong (lihat Data dan sumber); `tests/Feature/PersonalTargetTest.php`, `OwnedEquipmentTest.php`, `ManualHikeRecordTest.php` hanya membuka `/` dan tidak menguji fitur (W7) |
| Paket lapangan luring | Belum ada | DK-36, spec v3 bagian 10, W6 |

## Task NgodingPakeAI
```

dengan:

```markdown
| Target pribadi dan catat manual (FR-25) | Belum ada | Migrasi rintisan kosong (lihat Data dan sumber); `tests/Feature/PersonalTargetTest.php`, `OwnedEquipmentTest.php`, `ManualHikeRecordTest.php` hanya membuka `/` dan tidak menguji fitur (W7) |
| Paket lapangan luring | Ada, di balik sakelar mati (Q10, Q14) | `app/Services/Trips/PaketLapangan.php`, `resources/views/sw.blade.php`, `resources/js/luring/`, `tests/Feature/FieldPackManifestTest.php`, `tests/Feature/FieldPackStalenessTest.php`, `tests/Feature/OfflineShellTest.php`, `tests/Feature/LogoutClearsDeviceDataTest.php` |
| Kartu darurat, versi cetak, pesan rencana | Ada, di balik sakelar mati (Q14) | `app/Services/Trips/KartuDarurat.php`, `app/Services/Trips/PesanRencana.php`, `app/Services/Trips/CetakTrip.php`, `tests/Feature/EmergencyCardTest.php`, `tests/Feature/TripPlanMessageTest.php` |

## Task NgodingPakeAI
```

4. Ganti:

```markdown
| 7 | Buat halaman detail dan kelola catatan pendakian | `resources/js/pages/Community/HikeSummary.tsx` | Sebagian: ubah dan hapus catatan manual menunggu FR-25 |
| 8 | Tambahkan state offline dan keterangan data kosong | `resources/views/offline.blade.php`, `resources/js/jejak.js`, kalimat kosong per bagian | Sebagian (paket luring W6) |
| 9 | Buat skema dan migrasi sesi pendakian | `create_hiking_sessions_table`, `add_reached_sequence_to_hiking_sessions`, `create_hike_track_points_table` | Ada |
```

dengan:

```markdown
| 7 | Buat halaman detail dan kelola catatan pendakian | `resources/js/pages/Community/HikeSummary.tsx` | Sebagian: ubah dan hapus catatan manual menunggu FR-25 |
| 8 | Tambahkan state offline dan keterangan data kosong | `resources/views/offline.blade.php`, `resources/js/luring/shell.tsx`, `resources/js/jejak.js`, kalimat kosong per bagian | Ada (paket luring di balik sakelar) |
| 9 | Buat skema dan migrasi sesi pendakian | `create_hiking_sessions_table`, `add_reached_sequence_to_hiking_sessions`, `create_hike_track_points_table` | Ada |
```

5. Ganti:

```markdown
| 12 | Buat endpoint titik penting dan ketinggian jalur | Props mode lapangan dari `HikeModeController` | Ada, bentuk berbeda |
| 13 | Buat endpoint sesi pendakian dan posisi | Rute `trips.start`, `trips.hike.position`, `hike.track` | Ada |
| 14 | Buat endpoint CRUD catatan pendakian | Tidak ada | Belum ada (FR-25) |
```

dengan:

```markdown
| 12 | Buat endpoint titik penting dan ketinggian jalur | Props mode lapangan dari `HikeModeController` | Ada, bentuk berbeda |
| 13 | Buat endpoint sesi pendakian dan posisi | Rute `trips.start`, `trips.hike.arrive`, `trips.hike.pause`, `trips.hike.resume`, `hike.track`; posisi tidak dikirim (GR-07) | Ada, bentuk berbeda |
| 14 | Buat endpoint CRUD catatan pendakian | Tidak ada | Belum ada (FR-25) |
```

6. Ganti:

```markdown
## Keputusan dan konflik terkait

- **DK-07** FR-25 masuk cakupan; **DK-36** paket lapangan luring berisi data stabil; **DK-49** mode lapangan mengikuti tema pengguna; **DK-50** alasan pembatalan dicatat opsional (W7).
- **K13** target bulanan sebagai target pribadi terhadap diri sendiri; **K14** catat pendakian manual tidak menaikkan pengalaman.
```

dengan:

```markdown
## Keputusan dan konflik terkait

- **DK-07** FR-25 masuk cakupan; **DK-36** paket lapangan luring berisi data stabil; **DK-49** mode lapangan mengikuti tema pengguna; **DK-50** alasan pembatalan dicatat opsional dari daftar tetap (W6).
- **K13** target bulanan sebagai target pribadi terhadap diri sendiri; **K14** catat pendakian manual tidak menaikkan pengalaman.
```

- [ ] **Step 3: Jalankan penjaga dokumen dan suite.**

Run: `php artisan test --compact --filter='PrdReferenceIntegrity|DesignDirection|TeamDocsIndex|RepoHygiene'`
Expected: PASS. Pada simulasi rencana: 31 test lulus.

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 2.058 test, 2.056 lulus, 2 dilewati (sebelum tugas: 2.058).

Run: `LC_ALL=C grep -nP '\xE2\x80\x94' docs/PRD.md DESIGN.md docs/ARCHITECTURE.md docs/fitur/06-pendakian-progres.md`
Expected: tanpa keluaran (tanpa em dash).

- [ ] **Step 4: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 13: Verifikasi gelombang dan uji luring manual (koordinator)

Induk W6 "Selesai bila" (aturan penutup gelombang, ditambah uji luring di peramban: paket terunduh, jaringan dimatikan,
mode lapangan terbuka dari paket, service worker versi baru aktif tanpa menghapus paket); Q17 (tanpa Playwright, uji
manual di Chrome dengan tangkapan); ruling W6-R9, W6-R10, W6-R17. Dikerjakan koordinator sesudah Tugas 1 sampai 12
selesai dan lolos tinjauan per tugas. Setiap klaim di laporan disertai keluaran perintah, jalur berkas, atau tangkapan
(R-023). Angka "pada simulasi rencana" adalah hasil menjalankan kode rencana ini di salinan keadaan akhir simulasi W5,
bukan hasil di repo; selisih dari W5 dan gelombang perbaikan akhir W4 di repo nyata ditambahkan koordinator. Harness
pratinjau memakai port 8100 milik koordinator.

**Files:**
- Create: `docs/desain/tangkapan/w6/*.webp` (tangkapan), `docs/desain/tangkapan/w6/README.md` (perbandingan dan bukti uji luring)
- Modify: `HANDOVER.md` (bagian 0: baris "Terakhir diperbarui" dan subbagian W6), `.superpowers/sdd/2026-09-26-memount-v3-program.md` (baris W6; berkas ini diabaikan git)

**Interfaces:**
- Consumes: seluruh keluaran Tugas 1 sampai 12; harness pratinjau (diperluas Tugas 3, 6, 7, 9, 10); gambar acuan `docs/desain/prototype/acuan/11-mendaki.png` dan `12-ringkasan.png`.
- Produces: bukti akhir W6, hasil uji luring manual (atau catatan MENUNGGU IZIN), daftar izin akhir yang diperbarui, dan titik lanjut W7.

- [ ] **Step 1: Catat keadaan kerja.**

Run: `git status --short | wc -l; git diff --stat | tail -1`
Expected: selisih terhadap catatan akhir W5 cocok dengan gabungan daftar Files Tugas 1 sampai 12. Pada simulasi rencana, Tugas 1 sampai 12 menambah 65 berkas, mengubah 58, dan menghapus 1 (`app/Http/Requests/Trips/UpdateHikePositionRequest.php`) terhadap keadaan akhir simulasi W5.

- [ ] **Step 2: Suite penuh, tipe, format, dan paritas PostgreSQL.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 2.058 test, 2.056 lulus, 2 dilewati; di repo nyata ditambah test perbaikan akhir W4 dan residunya, yang belum ada di dasar simulasi.

Run: `php artisan test --compact`
Expected: 0 gagal. Pada simulasi rencana: 2.092 test, 2.056 lulus, 36 dilewati (34 test Spatial tanpa `SPATIAL_TEST_DSN` dan 2 yang sudah dilewati di dasar).

Run: `npx tsc --noEmit`
Expected: keluar tanpa keluaran galat.

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Paritas PostgreSQL (R-024): bila `SPATIAL_TEST_DSN` menunjuk PostGIS lokal (`docker-compose.yml`, DK-55; tidak pernah
Supabase), jalankan `php artisan test --compact --testsuite=Spatial` dan catat hasil `HikingSessionUniqueTest` dan
`FieldModeColumnsSchemaTest`. Tanpa itu, catat "belum dijalankan; menunggu CI PostGIS sesudah push yang diizinkan".

- [ ] **Step 3: Build dan ukuran bundel.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`
Expected: `✓ built` dengan entri `shell-<hash>.js` dan peringatan ukuran potongan maplibre-gl yang sama seperti W5. Pada simulasi rencana: entri `shell` 4,64 kB (gzip 2,00); entri `app` 10,70 kB ditambah potongan bersama `dist` 346,41 kB (gzip 3,18 dan 108,56), jumlah 357,11 kB lawan 355,97 kB di dasar; CSS 72,47 kB (gzip 14,22; dasar 69,69 dan 13,70). Simpanan pemasangan service worker: 4 berkas (81 KiB) dengan paket mati, 22 berkas (1.315 KiB) dengan paket nyala.

- [ ] **Step 4: Suite pratinjau dan server statis.**

Run: `php artisan test --compact --testsuite=Pratinjau`
Expected: PASS. Pada simulasi rencana: 29 test lulus (dasar 22), menulis 30 halaman `public/_pratinjau`.

Run (di latar belakang): `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php -S 127.0.0.1:8100 -t public tests/Pratinjau/statis.php`
Expected: server statis tanpa basis data. Buka `http://127.0.0.1:8100/_pratinjau/<nama>.html`. Halaman mode lapangan
mengganti alamat ke `/trips/<id>/hike`; untuk memuat ulang, buka lagi berkas `_pratinjau/<nama>.html`.

- [ ] **Step 5: Tangkapan.** Chrome lewat MCP chrome-devtools. 390 x 844 dengan emulasi seluler, sentuh, rasio
piksel 1; 1440 x 900 rasio 1; terang dan gelap. Untuk mode lapangan, posisi diberikan lewat skrip awal halaman yang
mengganti `navigator.geolocation` (lintang -6,7462, bujur 107,0040, akurasi 18 m, umur 35 detik), karena emulasi
geolokasi DevTools tanpa izin terbaca "ditolak". Simpan WebP di `docs/desain/tangkapan/w6/`:

1. `mode-lapangan` (acuan 11): 390 dan 1440, terang dan gelap, ditambah tema lapangan gelap lewat tombol "Gelap" di
   kepala halaman (`mode-lapangan-390-lapangan-gelap.webp`).
2. `jelajah-mendaki` (strip pendakian aktif W2 yang menuju mode lapangan): 390 terang dan gelap.
3. `kartu-darurat` dengan lembar "Kartu darurat" terbuka (ketuk tombolnya): 390 terang dan gelap.
4. `ringkasan` (acuan 12) dan `ringkasan-batal`: 390 dan 1440, terang dan gelap.
5. `mendaki` (sakelar paket mati), `mendaki-paket`, `cetak`, `ruang-kerja-keselamatan`, dan `luring` (ketuk "Buka mode
   lapangan"): 390 terang.

- [ ] **Step 6: Perbandingan tertulis.** Tulis `docs/desain/tangkapan/w6/README.md` dengan susunan README tangkapan W5:
paragraf cara pengambilan, satu paragraf cakupan W6, tabel
`| Tangkapan | Gambar acuan | Yang sudah sesuai | Yang belum sesuai dan gelombang yang mengubahnya |`, dan bagian
"Uji luring manual" (Step 9). Pemeriksaan perencanaan menemukan hal-hal di bawah ini. Pastikan masing-masing pada
tangkapan, jangan disalin tanpa melihat.

- **Sudah sesuai (mode lapangan 390 px, acuan 11).** Kepala dengan tombol kembali, nama jalur, "Berjalan, 2 jam 15 menit
  bergerak", lencana Data contoh, dan tombol tema; peta tanpa kontrol bawaan dengan pos bernomor (pos berikutnya kuning,
  yang sudah dicapai terisi), titik posisi dengan lingkaran akurasi, tombol peta 48 px; "Pos berikutnya, nomor 2"
  dengan nama besar; tiga petak (sisa jarak "garis lurus" karena data contoh tanpa geometri, naik lagi "dari ketinggian
  pos", perkiraan "Belum ada"); "±18 m · 35 detik lalu"; kalimat lokasi tidak dikirim; "Saya sudah di Telaga Biru";
  "Semua pos (6)"; Jeda dan Selesai 60 px.
- **Sudah sesuai (ringkasan 390 px, acuan 12).** Panel gelap empat angka dengan "Ubah angka"; "Dibanding pendakian Anda
  sendiri" dengan tangga Andong 380 m, Batur 600 m, Prau 700 m, Gede 1.650 m pada garis dasar yang sama, kalimat
  "+950 m dari tanjakan tertinggi Anda sebelumnya (Prau via Wates, 700 m)", pencapaian "Tanjakan pertama di atas 1.000 m",
  dan BR-11 dengan tautan Profil; "Bantu pendaki berikutnya" bergaris putus-putus komunitas dengan tanggal, chip kondisi,
  segmen, catatan, foto, kalimat terbit dan tidak terbit, tombol kirim nonaktif sampai satu kondisi dipilih; "Lihat
  progres".
- **Belum sesuai.**
  - Peta dasar mode lapangan di pratinjau kosong karena harness statis tanpa ubin; geometri jalur data contoh kosong,
    jadi garis yang dilalui dan sisa tidak tergambar (kurasi data W8).
  - Ikon orang di judul "Bantu pendaki berikutnya", ikon gunung dan tenda pada lencana pencapaian, dan ikon kamera foto
    prototype 12 tidak ditambahkan (registri ikon hanya bertambah jam, W6-R16); "Camping pertama di atas 2.000 m" tidak
    dibangun karena lokasi berkemah tidak tercatat (W6-R13).
  - Waktu bergerak ditulis "10 jam 35 menit", bukan "11 j 05 m" prototype (huruf "m" bertabrakan dengan meter).
  - Sakelar paket dan alat keselamatan mati bawaan: tangkapan `mendaki-paket`, `cetak`, `kartu-darurat`, dan
    `ruang-kerja-keselamatan` diambil dengan sakelar dinyalakan di test pratinjau saja.

- [ ] **Step 7: Ukur reflow, target, dan gerak.** Dengan `evaluate_script` di pratinjau:

1. `mode-lapangan`, `kartu-darurat`, `ringkasan`, `ringkasan-batal`, `mendaki`, `mendaki-paket`, `cetak`, dan `luring`
   di 320 x 568 dan 390 x 844: `document.documentElement.scrollWidth` sama dengan `clientWidth`. Pada simulasi rencana: sama untuk kesepuluh halaman (kedelapan di atas ditambah `jelajah-mendaki` dan `ruang-kerja-keselamatan`) di kedua ukuran, sesudah tabel pos `cetak` dibungkus wadah gulir (sebelumnya 378 lawan 320).
2. Tinggi kendali: "Mulai pendakian" (`mendaki`) dan tombol telepon kartu darurat 66 px; Jeda, Selesai, dan "Kartu
   darurat" 60 px; tombol peta 48 px. Pada simulasi rencana: "Mulai pendakian" 66 px, tombol telepon 70 px, Jeda, Selesai, dan "Kartu darurat" 60 px, tombol peta 48 px, "Saya sudah di Telaga Biru" 49 px.
3. Dengan `emulate` `prefers-reduced-motion: reduce`, tiba di pos tidak menganimasikan nama pos (durasi animasi `denyut-tiba`
   menjadi 1 ms, `MotionTest`).

- [ ] **Step 8: Jalur papan ketik** (e2e menunggu Playwright, Q17). Tanpa tetikus, 390 px:

1. **Mode lapangan.** Tab dari awal: tautan lewati, kembali ke detail trip, tombol tema, "Posisi saya", perbesar,
   perkecil, "Saya sudah di Telaga Biru", "Semua pos (6)", sakelar layar, Jeda, Selesai. Cincin fokus terlihat di tema
   lapangan terang dan gelap. Enter pada Selesai membuka lembar, Escape menutupnya dan fokus kembali ke Selesai.
2. **Ringkasan.** "Ubah angka" membuka lembar dengan fokus di isian pertama; chip kondisi dengan Enter dan Spasi
   (`aria-pressed` berubah); tombol kirim menjadi aktif sesudah satu chip.

Simpan satu tangkapan fokus di Jeda sebagai `fokus-jeda-390-lapangan.webp`. Hentikan server port 8100.

- [ ] **Step 9: Uji luring manual di Chrome (Q17).** Prasyarat: server Laravel lokal dengan basis data, yang hanya ada
sesudah izin Q1 pemilik (`memount:demo` membuat `database/demo.sqlite`). Tanpa izin itu, tulis "MENUNGGU IZIN Q1" di
README dan laporan, catat bukti pengganti yang tersedia (pratinjau `luring` Step 5 butir 5: shell membuka mode lapangan
dari paket di IndexedDB; `ProgressiveWebAppTest` tiga test Node; `FieldPackManifestTest`), dan lanjut ke Step 10. Bila
izin ada, jalankan persis, satu Chrome bersih (profil baru), DevTools terbuka:

1. Jalankan `DB_CONNECTION=demo DB_URL= FIELD_PACK_ENABLED=true FIELD_SAFETY_ENABLED=true php artisan serve --host=127.0.0.1 --port=8100`
   (bukan port 8000; tidak pernah dengan `.env` Supabase). Masuk sebagai `budi@contoh.test` dengan kata sandi yang
   dicetak `memount:demo`.
2. Buka `/mendaki` sekali, tunggu service worker aktif (Application, Service Workers: "activated and is running"), lalu
   muat ulang. Ketuk "Siapkan paket luring", catat kalimat ukurannya, ketuk "Unduh paket", tunggu "Siap luring.
   Diperiksa utuh di perangkat ini ...". Tangkapan `luring-1-siap.webp`. Di Application: IndexedDB
   `memount-luring-<kunci>` toko `bagian` 3 baris dan `paket` 1 baris `siap: true`; Cache Storage `shell-<8 heks>` memuat
   `/offline` dan berkas `shell-*.js`. Tangkapan `luring-2-penyimpanan.webp`.
3. Di Console jalankan `await caches.open('pack-uji').then((c) => c.put('/uji-paket', new Response('isi paket')))`
   untuk meniru cache ubin paket.
4. Network: pilih "Offline". Buka `http://127.0.0.1:8100/trips/<id trip>`: shell luring tampil dengan "Paket lapangan di
   perangkat ini", status "terakhir diketahui" atau "belum dapat dipastikan" dengan ikon jam. Ketuk "Buka mode
   lapangan": mode lapangan dari paket ("Dari paket luring ...", "Peta dasar tidak tersedia luring"). Ketuk "Saya sudah
   di <pos>": "Luring. 1 catatan menunggu sinyal untuk dikirim." Tangkapan `luring-3-lapangan-offline.webp`.
5. Network: kembali "No throttling". Dalam 30 detik antrean terkirim (baris antrean hilang). Muat ulang mode lapangan
   daring: pos itu tercatat dicapai.
6. Service worker versi baru: hentikan server, jalankan ulang dengan `FIELD_PACK_ENABLED=false` (isi `sw.js` berubah), di
   Application, Service Workers ketuk "Update" lalu muat ulang. Versi baru "activated"; Cache Storage hanya `shell-*`
   baru dan `pack-uji` masih ada dengan `/uji-paket`; IndexedDB `memount-luring-<kunci>` masih memuat paket. Tangkapan
   `luring-4-sw-baru.webp`. Hentikan dan jalankan ulang dengan `FIELD_PACK_ENABLED=true`.
7. Keluar dari menu avatar (1440 px) atau Profil: IndexedDB `memount-luring-<kunci>`, `jejak-pendakian-<kunci>`, cache
   `pack-uji`, dan `localStorage['memount-perangkat']` hilang; respons POST `/logout` membawa `Clear-Site-Data: "storage"`
   (Network, Headers). Tangkapan `luring-5-keluar.webp`. Hentikan server.

- [ ] **Step 10: Delivery Gate antislop, empat blok.** Tulis laporan PASS/FAIL, satu baris per butir, dan setiap PASS
disertai bukti:

- **Salinan:** R-02 tanpa em dash. Run: `LC_ALL=C grep -rlP '\xE2\x80\x94' app resources tests database config DESIGN.md docs/ARCHITECTURE.md docs/PRD.md docs/fitur`. Expected: tanpa keluaran. Teks berbahasa Indonesia dengan "Anda"; tanpa "aman", "sehat", "dijamin", "terjamin", atau "pasti" sebagai klaim di mode lapangan, kartu darurat, pesan rencana, versi cetak, paket, dan ringkasan (`EmergencyCardTest`, `TripPlanMessageTest`); setiap keadaan kosong menyebut siapa yang ditunggu.
- **Desain:** satu aksi penuh per layar lapangan (Jeda dan Selesai berpasangan, Kartu darurat di atasnya); hierarki lewat bobot; tanpa kartu di dalam kartu; komunitas bergaris putus-putus, resmi tidak; "terakhir diketahui" dengan kata, ikon, dan garis.
- **Interaksi:** R-26 setiap kendali berfungsi (tiba, jeda, lanjut, selesai, batalkan dengan alasan, ubah angka, kirim dan ubah laporan, telepon, simpan jam kembali, kirim WhatsApp atau SMS, salin, cetak, siapkan dan unduh paket, pakai paket saja, buka paket luring, keluar); R-27 keadaan pertama kali, kosong, menunggu, memproses, luring; R-32 jalur papan ketik Step 8.
- **Kebenaran:** tanpa skor atau persen; posisi tidak dikirim; status Buka turun sesudah ambang; cuaca tidak dikarang; laporan komunitas terpisah dari status resmi; jalur tidak terbit tidak dipaket; R-35 build dijalankan dan kendali diklik.

- [ ] **Step 11: HANDOVER, ledger, dan daftar izin akhir.** Di `HANDOVER.md` bagian 0, perbarui baris "Terakhir
diperbarui" dan tambahkan subbagian "W6 Mendaki dan paket luring (selesai <tanggal>, menunggu izin commit)" dengan
susunan subbagian W5:

- rujukan rencana ini, jumlah tugas, dan letak ledger;
- tabel `| Butir | Nilai | Bukti |` berisi suite Unit dan Feature, suite bawaan, Spatial (dijalankan atau menunggu CI),
  tsc, Pint, build dan entri shell, Pratinjau, tangkapan, reflow, target, jalur papan ketik, dan uji luring manual
  (hasil atau MENUNGGU IZIN Q1);
- ruling koordinator atas Konflik 1 sampai 13;
- OPEN ISSUE dari Bukti simulasi;
- daftar izin akhir: lima migrasi W6 (`2026_10_07_000100` sampai `000500`; indeks unik didahului pemeriksaan duplikat
  di docblock migrasinya), pembersihan `hiking_sessions.last_known_location` dan `location_updated_at` lama, sakelar
  `FIELD_PACK_ENABLED` (Q10 dan Q14) dan `FIELD_SAFETY_ENABLED` (Q14), ambang Q9, amandemen GR-07 (Q7), dan
  `encryptHistory` dengan `clearHistory` (izin akhir 9);
- titik lanjut W7 (Progres membaca `recorded_*` untuk total tanjakan dan jarak; kata "Siap berangkat" di riwayat).

Perbarui baris W6 di `.superpowers/sdd/2026-09-26-memount-v3-program.md`.

- [ ] **Step 12: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

## Tinjauan diri

### Cakupan induk W6 dan titipan

| Butir | Tugas | Bukti di rencana |
|---|---|---|
| Butir 1: pintu tab `/mendaki` (pratinjau hari-H, keadaan paket, izin lokasi, rekam jejak mati bawaan, "Mulai pendakian" 66 px); `TrackTabTest` diperluas | Tugas 4 | `TrackTabTest` (8 test, termasuk kedua keadaan sakelar paket dan anggaran kueri) |
| Butir 2: mode lapangan (isi spec bagian 6.12, tema lapangan DK-49, tiba di pos ≤ radius dan bertanya, pembulatan, naik dari profil, layar menyala mati bawaan); `HikeModeCheckpointTest` diperluas dan `FieldModeFiguresTest` | Tugas 2, 3 | `FieldModeFiguresTest` (10 test di Node), `HikeModeCheckpointTest` (14 test), `MapConfigTest::test_the_field_map_follows_the_field_theme_toggle` (peta dasar ikut tema lapangan saat dibalik). "Kecilkan ke strip" dipenuhi tombol kembali yang membiarkan pendakian berjalan dan strip W2 (Konflik 9) |
| Butir 3: service worker (`activate` hanya `shell-` usang, navigasi jaringan dulu dengan shell, endpoint dinamis hanya jaringan, entri `resources/js/luring/shell.tsx`); dua regresi `ProgressiveWebAppTest` | Tugas 9 | `ProgressiveWebAppTest::test_activate_never_deletes_pack_caches`, `test_the_offline_shell_never_serves_server_content`, `test_the_shell_is_precached_only_when_the_field_pack_is_open`; `OfflineShellTest` |
| Butir 4: manifest `GET /trips/{trip}/paket` (Policy pemilik, `no-store`, versi hash, bagian dengan ukuran dan SHA-256, potret dinamis, atribusi, jalur terbit, tanpa GPX, AI, skor, peserta lain); `FieldPackManifestTest` | Tugas 8 | `FieldPackManifestTest` (7 test) |
| Butir 5: unduh dan verifikasi di klien (IndexedDB per pengguna, cek ruang, `persist()` pada ketukan, lanjut per bagian, "Siap luring" sesudah dibaca ulang, versi lama bertahan, "Pakai paket saja"); bukti peramban | Tugas 9, 10, 13 | `FieldPackDeviceTest` (3 test), `OfflineShellTest`; pratinjau `luring`; Tugas 13 Step 9 (manual, Q17) |
| Butir 6: data dinamis luring ("terakhir diketahui" dengan ikon jam, Buka turun sesudah ambang Q9, cuaca belum terbit); `FieldPackStalenessTest` | Tugas 8, 9 | `FieldPackStalenessTest` (5 test, termasuk `kalimatStatusLuring` di Node); `DaftarPaket` |
| Butir 7: keluar dan ganti akun (jejak belum terkirim, hapus basis data paket, `pack-*`, OPFS, `Clear-Site-Data`, `jejak.js` terikat pengguna); `LogoutClearsDeviceDataTest` dan test sumber `jejak.js` | Tugas 11 | `LogoutClearsDeviceDataTest` (4 test) |
| Butir 8: keselamatan (kartu darurat 112 dan 115, kontak pengelola bila resmi, tetap di tempat, blok lokasi; cetak `/trips/{trip}/cetak`; pesan WhatsApp atau SMS dengan eskalasi dan pernyataan tidak memantau); `EmergencyCardTest` dan `TripPlanMessageTest` | Tugas 7 | `EmergencyCardTest` (7 test), `TripPlanMessageTest` (7 test), `FieldModeFiguresTest::test_the_emergency_location_block_reads_as_it_is_spoken` |
| Butir 9: ringkasan dan laporan (varian selesai dan dibatalkan, alasan DK-50 dengan daftar putih, angka dengan "Ubah angka", banding diri sendiri, laporan dengan foto tanpa GPS, "Ubah sebelum dimoderasi"); `HikeSummaryTest` dan `TripCancellationReasonTest` | Tugas 5, 6 | `TripCancellationReasonTest` (8 test), `HikeSummaryTest` (16 test), `HikeSummaryReportTest` (7 test) |
| Selesai bila: aturan penutup gelombang dan uji luring di peramban | Tugas 13 | Step 2 sampai 11 |
| Titipan izin akhir 8 (satu pendakian berlangsung, indeks unik, satu pemilih) | Tugas 1 | `SingleActiveHikeTest` (6 test), `HikingSessionUniqueTest` (PostgreSQL) |
| Titipan izin akhir 9 (`encryptHistory`, `clearHistory`) | Tidak dibangun | Konflik 5 |
| Titipan W4-C4 ("Siap berangkat") | Tugas 4 | `TrackTabTest::test_no_status_word_or_verdict_reaches_the_tab`; ruling W6-R14 |
| Titipan Konflik W5 nomor 8 (strip dan kartu B3) | Tidak ada kode | Ruling W6-R15 |

### Pemindaian placeholder

Tidak ada "TBD", "TODO", "implement later", atau "similar to Task N". Setiap langkah kode memuat kodenya: berkas baru
ditulis utuh, tulis ulang memuat isi utuh, dan perubahan kecil memakai blok "Ganti ... dengan ...". Blok-blok itu
dirakit dari rantai simulasi dan diperiksa dua hal: teks lamanya muncul tepat sekali pada keadaan sebelum blok itu,
dan menerapkan semua blok berurutan menghasilkan berkas akhir tugas persis. Sesudah dirakit, teks rencana ini diputar
ulang dari awal pada salinan dasar seperti implementer membacanya, dan setiap berkas sama dengan keadaan akhir tugasnya
(Bukti simulasi).

### Konsistensi nama dan tipe

- **Satu pendakian (Tugas 1):** `TripLifecycle::start(): ?string`; `PendakianAktif::untuk()`; indeks `hiking_sessions_trip_plan_id_unique`.
- **Hitungan (Tugas 2, 7):** `hitung.ts` (`teksJarak`, `naikDiantara`, `keputusanTiba`, `sisaJarak`, `elevasiPada`, `teksKoordinat`, dan lainnya di Interfaces Tugas 2).
- **Mode lapangan (Tugas 3, 5, 7, 10):** `HalamanMendaki::untuk()` dan kunci `trip`, `pos`, `garis`, `profil`, `ruas`, `tercapai`, `radiusTiba`, `laporan`, `rekamJejak`, `sesiId`, `sumber`, `pilihan`, `darurat`, `urls`; TypeScript `DataLapangan` dengan kunci yang sama; `useModeLapangan()` mengembalikan `ModeLapangan` (termasuk `jedaSejak`, `jedaDetik` sejak Tugas 10).
- **Selesai dan batal (Tugas 5):** `CancellationReason`, `FormulirSelesai`, `BatalkanTrip`, `SelesaiLapangan`; prop `alasanBatal`.
- **Ringkasan (Tugas 6):** `HasilPendakian::untuk()`; `AngkaRingkasan`, `TanggaRingkasan`, `LaporanRingkasan`; rute `history.numbers.update`, `reports.update`.
- **Keselamatan (Tugas 7):** `KartuDarurat`, `PesanRencana`, `CetakTrip`; props `darurat`, `keselamatan`; rute `trips.print`, `trips.return.update`; middleware `fitur`.
- **Paket (Tugas 8 sampai 10):** `PaketLapangan` dan bagian `lapangan`, `rencana`, `dinamis`; `ManifestPaket`, `RekamanPaket`, `RekamanBagian`, `RekamanSesi`; `kalimatStatusLuring()`; `rakitDataLapangan()`; `paketSiap()`, `dataLapanganDariPaket()`; `unduhPaket()`; prop bersama `perangkat`.
- **Keluar (Tugas 11):** `keluar()`, `bersihkanPerangkat()`, `usePenjagaPerangkat()`; `jejak.js` `aturPengguna()`.

tsc lulus sesudah setiap tugas pada simulasi dan putaran ulang.

### Review Focus

Kelima baris Review Focus punya test di tugas pemilik kodenya: dua pendakian dan aksi yang diputar ulang (Tugas 1, 3),
angka lapangan (Tugas 2, 7), data luring yang terbaca terkini (Tugas 8), paket dan data perangkat (Tugas 8 sampai 11),
janji keselamatan dan eskalasi (Tugas 7). Semua test itu lulus pada simulasi dan putaran ulang.

### Pemeriksaan teks rencana

- Rencana ini tanpa em dash. Tanda "§" hanya muncul saat mengutip ID PRD; dokumen lain dirujuk "bagian N".
- Teks antarmuka baru berbahasa Indonesia dengan sapaan "Anda".
- Tidak ada klaim "aman", "sehat", "dijamin", "terjamin", atau "pasti"; kata-kata itu hanya muncul di test yang
  memastikan teksnya tidak memakainya dan di kalimat R-012 yang menyangkalnya ("bukan jaminan keselamatan").
- Label kecocokan publik tidak disentuh.

### Pemeriksaan test (testing-best-practices, `rules/review.md`)

- Test baru menguji perilaku lewat HTTP atau service publik dan memakai pabrik; waktu dibekukan dengan `travelTo()`
  bila tanggal menentukan hasil (`HikeSummaryTest`, `TripPlanMessageTest`, `FieldPackManifestTest`,
  `FieldPackStalenessTest`, `EmergencyCardTest`).
- Logika murni di peramban (`hitung.ts`, `kesegaran.ts`, `rakit.ts`, `periksa.ts`, service worker) diuji sebagai
  perilaku di Node lewat `Symfony\Component\Process\Process`, bukan sebagai teks sumber (ruling W6-R17). Perilaku yang
  butuh IndexedDB, Cache Storage, atau OPFS sungguhan dijaga dari urutan sumbernya dan diuji manual (Tugas 13 Step 9).
- Setiap rute baru diuji untuk tamu, pengguna lain (403 atau 404 selama sakelar mati), validasi, dan batas laju bila
  menulis.
- Test yang diperbarui membawa alasannya: `HikeModeCheckpointTest`, `HikeSummaryTest`, `TrackTabTest`,
  `LocationPrivacyTest`, `MapConfigTest`, `TripOwnershipTest`, `TripReactPagesTest`, `ButtonFeedbackTest`,
  `TripLifecycleTest`, `ShellFeedbackTest`, `PageHeaderContractTest`, dan `ProgressiveWebAppTest`.
- Mutasi yang diperiksa pada simulasi: menghapus kunci baris pengguna di `start()` tidak tertangkap SQLite (penguncian
  tidak berlaku di sana) dan dijaga indeks unik plus `HikingSessionUniqueTest` di PostgreSQL; membalik `k !== SHELL`
  menjadi filter lama menggagalkan `test_activate_never_deletes_pack_caches`; menyimpan semua gambar ke cache
  menggagalkan `test_the_offline_shell_never_serves_server_content`; menghapus tambahan satu jam menggagalkan
  `test_the_message_escalates_one_hour_after_the_return_time`.

### Paritas PostgreSQL (R-024)

Suite di rencana ini memakai SQLite dalam memori, jadi hijaunya tidak membuktikan perilaku PostgreSQL. W6 menambah
lima migrasi aditif, satu indeks unik, satu penguncian baris, dan satu kueri `GROUP BY`. Yang dijaga test Spatial (lewat
`SPATIAL_TEST_DSN` di PostGIS CI atau lokal, tidak pernah Supabase): nama, keunikan, dan galat indeks
`hiking_sessions_trip_plan_id_unique` (`HikingSessionUniqueTest`), dan jenis, panjang, nilai bawaan, serta nullability
kesepuluh kolom baru (`FieldModeColumnsSchemaTest`). Yang diperiksa dengan membaca kueri:

- `TripLifecycle::start()` mengunci baris `users` dengan `SELECT ... FOR UPDATE` di dalam transaksi; di PostgreSQL dua
  permintaan bersamaan untuk pengguna yang sama berurutan, di SQLite klausa itu diabaikan dan indeks unik hanya
  menjaga satu sesi per trip. Uji konkurensi dua koneksi belum ada (OPEN ISSUE).
- Kueri tangga ringkasan memilih `trails.name` dan `trails.elevation_gain_m` yang semuanya ada di `GROUP BY`, sah di
  PostgreSQL, sama dengan tangga Beranda W5.
- Pengurutan pada kolom tak-NULL (`hiking_history.completed_at`, `trip_plans.planned_date`, `trail_condition_reports.hike_date`)
  atau yang disaring `whereNotNull` (`trails.elevation_gain_m`), sehingga urutan NULL yang berbeda tidak muncul.
- UPDATE bersyarat tiba di pos (`reached_checkpoint_sequence IS NULL OR < ?`) dan jeda (`paused_at IS NULL`) atomik
  di kedua basis data.

Rencana ini tidak menjalankan apa pun terhadap PostgreSQL. Tanpa PostGIS lokal, laporan Tugas 13 mencatat paritas W6
sebagai "test Spatial tertulis, menunggu CI".

## Lampiran A: Aturan eksekusi W6

Salin lampiran ini ke `.superpowers/sdd/<folder ledger W6>/aturan-eksekusi.md` sebelum Tugas 1. Isinya aturan
eksekusi W5 dengan nama gelombang, rencana, port, dan larangan baru W6.

### Aturan eksekusi W6 (dibaca setiap implementer sebelum mulai)

Proyek: Memount, Laravel 13.31 + Inertia 3 + React 19.3 + TypeScript 5.9, PHP 8.5.10, di Windows dengan Git Bash.
Root repo: `E:/praktik_aplikasi_web`. Branch `hardening`. Commit terakhir `ddc27e8`; pohon kerja memuat perubahan yang
belum di-commit dari W1 sampai W5 dan gelombang ini. Folder `.agents/skills/` yang belum dilacak (keputusan Q19)
jangan disentuh.

#### Larangan mutlak (arahan pemilik dan kontrak proyek)

1. **Jangan commit, push, `git stash`, `git checkout -- <berkas>`, `git reset`, atau `git clean`.** Pemilik
   memutuskan tidak ada commit sampai seluruh program W1 sampai W9 selesai. Lewati setiap langkah "Commit"; catat
   berkas yang berubah di laporan.
2. **Jangan menyentuh Supabase dan jangan membaca atau mencetak isi `.env`.** `.env` menunjuk basis data produksi.
3. **Jangan menjalankan `php artisan migrate` dalam bentuk apa pun, `db:seed`, `db:wipe`, tinker (termasuk
   `tinker --execute`), atau `php artisan memount:demo`.** Migrasi dan seeder hanya berjalan di dalam PHPUnit. Lima
   migrasi W6 ditulis dan diuji, tidak dijalankan.
4. **Setiap perintah Artisan yang mem-boot aplikasi di luar PHPUnit, `npm run build`, dan server statis `php -S`
   wajib diberi awalan** `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL=`. `php artisan test ...` tidak butuh awalan.
5. **Jangan menambah atau mengubah dependensi** (composer, npm), jangan mengubah versi apa pun (MapLibre tetap 4.7.x),
   jangan memasang Workbox, `idb`, framer-motion, atau Playwright, dan jangan menjalankan `npx shadcn init`. Satu-satunya
   ikon baru adalah `clock` dengan path yang tertulis di Tugas 9.
6. **Jangan menyalakan sakelar `FIELD_PACK_ENABLED` atau `FIELD_SAFETY_ENABLED`** di `.env` atau konfigurasi mana pun;
   test dan pratinjau menyalakannya lewat `config([...])` di dalam test saja. Menyalakan untuk pengguna adalah keputusan
   pemilik (Q10, Q14).
7. **Jangan menyunting berkas di luar daftar "Files" tugas Anda**, kecuali brief atau dispatch menyebutnya. Bila perlu
   menyentuh berkas lain, berhenti dan laporkan NEEDS_CONTEXT.
8. **Blok `<laravel-boost-guidelines>` di `AGENTS.md` dan `CLAUDE.md` serta blok NgodingPakeAI di `AGENTS.md` tidak
   pernah disunting.** `HANDOVER.md`, `CLAUDE.md`, dan `docs/KEBIJAKAN-DATA-PRIBADI.md` tidak disunting implementer.
9. **Jangan menjalankan subagen.**
10. **Server `php artisan serve` di 127.0.0.1:8000 milik pemilik (memakai Supabase).** Jangan dipakai, dihentikan, atau
    dipanggil. Port 8100 milik koordinator (harness pratinjau dan uji luring Tugas 13), dan port 8150 milik penulis
    rencana. Implementer W6 tidak menyalakan server apa pun.

#### Hook GateGuard

Lingkungan ini memasang hook "Fact-Forcing Gate". Perintah Bash pertama dan suntingan pertama setiap berkas bisa
ditolak dengan permintaan fakta. Bila itu terjadi, tulis faktanya di balasan Anda lalu ulangi panggilan yang sama:
- ringkasan permintaan: "Menjalankan Tugas N rencana W6 (docs/superpowers/plans/2026-10-07-memount-v3-w6-mendaki.md) atas arahan pemilik";
- untuk Bash: apa yang diverifikasi atau dihasilkan perintah itu;
- untuk suntingan: berkas yang mengimpor atau merujuk berkas itu (cari dengan Grep), fungsi atau kelas publik yang terdampak, bentuk data bila ada;
- untuk berkas baru: berkas yang akan memanggilnya, bukti tidak ada berkas lain dengan tujuan sama, dan bentuk data;
- untuk penghapusan atau pemindahan: jalur yang terdampak dan cara mengembalikannya;
- instruksi pemilik verbatim: "ya, selesaikan semuanya baru minta izin commit dan integrasi nya jika belum selesai semua jangan minta ke saya, jika anda meminta perizinan artinya semua tugas anda telah selesai. lanjutkan!"

#### Konvensi kerangka dari W2 sampai W5

- Komponen yang hidup di kerangka persisten memanggil `useKerangka()` untuk toast. Toast tertutup saat pemiliknya
  dilepas.
- Konfirmasi sesudah navigasi datang dari flash halaman tujuan (selesai mendarat di ringkasan, batal di ruang kerja,
  jam kembali dan angka pendakian di halaman yang sama dengan flash `status`).
- Kabar dan penolakan dibacakan lewat wilayah `role="status"` atau `role="alert"` yang terpasang sejak halaman atau
  formulirnya tampil; hanya isi dan bingkainya yang bersyarat (`{flash.status ?? ''}`), bukan `Alert` yang dipasang
  bersama isinya (pola `ReadinessDashboardTest`; konvensi "Umpan balik aksi di wilayah yang selalu terpasang" di
  `DESIGN.md` repo nyata sesudah W4 Tugas 7).
- Muat ulang penuh karena versi aset baru hanya lewat `muatPenuhVersiBaru()` atau `muatUlangMilikKerangka()` di
  `resources/js/layouts/Kerangka.tsx`. Jangan memanggil `window.location.reload()` sendiri.
- Mode lapangan memakai `TataLetakPendakian`, bukan `AppLayout`: tanpa bilah tab dan strip, tema lapangan di akarnya.
- Aksi mode lapangan dikirim lewat `kirimAksi()` (antrean), bukan `router.post`: tanpa sinyal ia menunggu di perangkat.
- Primitif dan prop baru memakai nama Indonesia (`nilai`, `onUbah`, `label`).

#### Cara kerja

- Isi berkas dan kode di brief adalah isi yang harus ditulis, persis. Bila teks yang harus diganti tidak ditemukan
  persis, jangan menebak: laporkan NEEDS_CONTEXT dengan kutipan berkas nyata. Nomor baris di brief adalah petunjuk;
  yang mengikat adalah teksnya.
- Ikuti urutan langkah brief: test gagal dulu (catat keluaran merahnya), implementasi, build bila tugas menyentuh
  TypeScript atau rute, test lulus (catat keluaran hijaunya). Angka "pada simulasi rencana" di brief adalah acuan; bila
  angka nyata berbeda, laporkan apa adanya.
- Test modul murni dijalankan di Node lewat PHPUnit; Node 22.6 atau lebih baru harus ada di PATH (`node --version`).
- Pint per berkas PHP tugas: `vendor/bin/pint --format agent <berkas>`. Setelah menyunting TypeScript: build lalu
  `npx tsc --noEmit`.
- Dokumen dan teks: bahasa Indonesia, sapaan "Anda", **tanpa karakter em dash**, tanpa kata "aman", "sehat",
  "dijamin", "terjamin", atau "pasti" sebagai klaim. Konvensi rujukan: "§N" hanya untuk ID di PRD; bagian dokumen lain
  ditulis "bagian N".
- Bila keluaran berbeda dari "Expected" di brief, berhenti dan selidiki sebelum lanjut; laporkan apa adanya. Jangan
  menulis angka yang tidak keluar dari perintah.

#### Laporan

Tulis laporan lengkap ke berkas laporan yang disebut di dispatch: yang dikerjakan, perintah dan keluarannya (RED lalu
GREEN), berkas yang berubah, temuan tinjauan diri, dan kekhawatiran. Balasan akhir paling banyak 15 baris: Status
(DONE | DONE_WITH_CONCERNS | BLOCKED | NEEDS_CONTEXT), "Commits: tidak ada (keputusan pemilik)", ringkasan test satu
baris, kekhawatiran, dan jalur berkas laporan.
