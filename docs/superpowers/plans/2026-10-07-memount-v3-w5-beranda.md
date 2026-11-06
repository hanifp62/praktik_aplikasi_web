# Memount v3 W5 Beranda Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Beranda berfase B0 sampai B4 dengan satu aksi utama per fase, rupa prototype 01: kartu perjalanan
berikutnya (B2) dengan empat langkah dan persiapan kritis, tenggat terdekat dengan penanda kalender, kabar gunung yang
diikuti dengan resmi dan komunitas terpisah, "Jalur lain untuk Anda", dan tangga tanjakan; ajakan mengisi profil
(B0), ajakan merencanakan (B1), mode lapangan (B3), dan ajakan melapor (B4). Tanpa skor atau persen, tanpa "Siap
berangkat" untuk trip yang belum dimulai, tanpa cuaca atau jendela izin karangan, dan aksi "Ingatkan saat dibuka" yang
benar-benar mengingatkan.

**Architecture:** Server memegang fase dan isi. `RingkasanDasbor` menghitung `KonteksBeranda` sekali per permintaan
(fase, trip mendatang beserta relasinya, aturan izin, riwayat yang belum dilaporkan, rencana yang tanggalnya lewat),
lalu lima kelas bagian di `app/Services/Dashboard/` (`RingkasanDasbor`, `TenggatTrip`, `KabarBeranda`,
`JalurUntukAnda`, `TanggaTanjakan`) masing-masing mengisi satu prop malas Inertia dari konteks yang sama, sehingga muat
ulang parsial lembar Pengingat tidak menghitung Beranda (W2-6b). Aturan domain tetap milik pemiliknya:
`PermitService` (jendela izin, lewat `Trips\TenggatIzin` yang juga dibaca lembar Pengingat), `PreparationService`
(butir kritis, terbit BMKG, butir izin diurus), `TrailFitService` (kecocokan dan tawaran, skor tetap di dalam),
`KabarJalur` dan `TrailNewsService` (kabar), `ProgressLadderService` (kalimat tangga). Pengingat hari pemesanan izin
dibuka memperluas `TripReminderScheduler` (FR-24) tanpa tabel, kolom, atau rute baru. React hanya memilih tampilan:
komponen di `resources/js/components/beranda/`. Satu rute dihapus (`trails.timbang`, titipan W3-F3). Tidak ada
migrasi, ikon, atau dependensi baru.

**Tech Stack:** PHP 8.5.10, Laravel 13.31.0, Inertia Laravel 3.3.4, `@inertiajs/react` 3.7.1, React 19.3.0,
TypeScript 5.9, Vite 8, Tailwind CSS 3.4, PHPUnit 12.5, Pint, SQLite dalam memori untuk test. Tidak ada dependensi
baru (framer-motion 14 tetap tidak diimpor; lihat Perbandingan skill) dan tidak ada migrasi.

**Spec:** `docs/superpowers/specs/2026-09-25-memount-v3-prototype-design.md` (bagian 6.1, 3.4 dan DK-31, 5.1, 7, 8;
DK-37, DK-45), rencana induk `docs/superpowers/plans/2026-09-26-memount-v3-induk.md` (bagian W5 butir 1 sampai 5 dan
"Selesai bila"), `HANDOVER.md` bagian 0 "Ke W5" (kartu di dalam kartu laporan komunitas, "Kecocokan dasar" berulang
dan "Kenapa?" tanpa nama jalur di `FitLine`, rute `trails.timbang` dan `toggle()`, urutan alasan kartu W3-C7), ledger
W4 `.superpowers/sdd/2026-10-06-memount-v3-w4-trip/progress.md` (ruling W4-R4, W4-C4, W4-1c, W4-4d, W4-C7b), acuan
`docs/desain/prototype/acuan/01-beranda.png`, dan aturan eksekusi di Lampiran A. Keadaan awalnya tree
`b7e54014b7c2481f909ec7fe037fa2553c64da65` (repo nyata saat W4 Tugas 6 selesai); W4 Tugas 7, Tugas 8, dan gelombang
perbaikan akhir W4 selesai sebelum Tugas 1 rencana ini dimulai (Pra-cek).

## Global Constraints

- Versi dikunci: PHP 8.5, Laravel 13.31, Inertia Laravel 3.3.4, React 19.3, `@inertiajs/react` 3.7.1, Tailwind 3.4, Vite 8, TypeScript 5.9. Tidak ada perubahan versi kerangka (R-020).
- Tidak ada dependensi baru, runtime maupun dev, tidak ada `npx shadcn init`, dan tidak ada ikon baru: registri Phosphor (`resources/js/components/ui/ikon/phosphor.ts`) tidak disunting. Ikon yang dipakai sudah ada: `check`, `warning`, `question`, `arrow-square-out`.
- Tidak ada commit, push, akses Supabase, atau migrasi di basis data mana pun. Setiap langkah "Commit" berbunyi: "Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah."
- W5 tidak menambah migrasi, tabel, atau kolom. `trip_reminders.jenis` (string 8) menerima nilai baru `IZIN`. Satu rute dihapus (`trails.timbang`); tidak ada rute baru. `php artisan migrate` dalam bentuk apa pun, `db:seed`, `db:wipe`, tinker (termasuk `tinker --execute`), dan `memount:demo` tidak dipakai; migrasi dan seeder hanya berjalan di dalam PHPUnit (SQLite dalam memori).
- Setiap perintah Artisan di luar PHPUnit, `npm run build`, dan `php -S` diberi awalan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL=` (Git Bash). Di PowerShell: `$env:DB_CONNECTION="sqlite"; $env:DB_DATABASE=":memory:"; $env:DB_URL="(empty)"; <perintah>`, lalu `Remove-Item Env:DB_CONNECTION, Env:DB_DATABASE, Env:DB_URL`. `php artisan test ...` tidak butuh awalan karena `phpunit.xml` memaksa koneksi uji. Jangan membaca atau mencetak `.env`.
- Port `127.0.0.1:8100` dicadangkan untuk harness pratinjau koordinator (Tugas 9). Tugas 1 sampai 8 tidak menyalakan server apa pun. Server `php artisan serve` pemilik di `127.0.0.1:8000` tidak pernah dipakai atau dihentikan. Port 8140 milik penulis rencana.
- Bahasa Indonesia untuk teks antarmuka dan dokumen, sapaan "Anda", tanpa em dash (U+2014) di berkas mana pun, tanpa kata "aman" dan "layak berangkat" sebagai klaim (R-012, R-014). "§N" hanya untuk ID PRD; dokumen lain dirujuk "bagian N".
- Label kecocokan publik hanya "Cocok", "Perlu persiapan", dan "Kurang cocok"; UNKNOWN tampil "Belum dinilai" dari server (`RouteFitLabel::textFor`), tidak pernah diketik di klien (`TrustGrammarTest`). Tidak ada skor, persen, atau bobot angka untuk kecocokan maupun kesiapan di props atau di layar (R-004, R-005, BR-09); persiapan ditulis sebagai hitungan ("3 dari 13").
- "Jalur lain untuk Anda" hanya menawarkan jalur terbit yang datanya lengkap (R-008, R-022), berlabel Cocok atau Perlu persiapan, dan bukan jalur trip pendaki itu yang belum selesai.
- Status trip "Siap berangkat" dan kata keadaan kesiapan tidak tampil di Beranda untuk trip yang belum dimulai (W4-R4, ruling W4-C4 untuk Beranda); `TripStatus::label()` tidak diubah.
- Setiap layar yang merender status resmi lewat komponen status memuat kalimat "Status resmi menjelaskan ketentuan pengelola, bukan jaminan keselamatan." (R-012, ruling W3-S5); sejak Tugas 4 Beranda termasuk (`OfficialStatusSafetySentenceTest`). Kalimat dukungan keputusan Beranda lama ("Sistem ini membantu pengambilan keputusan pendakian. ...") dipertahankan apa adanya.
- Hari dan jam ditulis dalam zona gunungnya dengan penanda WIB, WITA, atau WIT; "hari ini" untuk perbandingan hari memakai `App\Support\Timezone`, tidak pernah `now()` mentah (`TimestampZoneTest`). Prakiraan BMKG tidak pernah dikarang: Beranda hanya menyebut hari terbitnya (R-013).
- Data yang tidak ada tetap tidak diketahui (R-007): tanpa jendela izin, kuota, tanjakan, atau pos karangan. Memount menautkan sistem resmi dan tidak menjual izin.
- Token hanya di `resources/css/app.css`; tanpa warna mentah, `bg-white`, `text-black`, atau `dark:` di komponen. Target sentuh kendali utama paling sedikit 44 px. Tidak ada grid tiga kolom tanpa titik henti (`TapTargetTest`). Halaman tidak pernah menggulir menyamping di 320 px; daftar yang bergulir menyamping diberi `relative`.
- Nama props bersama `jumlahSimpanan`, `pendakianAktif`, dan `daftarPengingat` tidak dipakai ulang sebagai prop halaman (`ReminderSheetTest`). Setiap bagian Beranda adalah prop malas (closure).
- Setiap bagian baru punya test anggaran kueri yang tidak tumbuh bersama data (`PageQueryBudgetTest::test_the_home_does_not_grow_with_trails_trips_or_news`), dan isi Beranda tidak pernah memuat data pendaki lain.
- Test yang menyandikan keputusan lama diperbarui menurut niat baru dengan alasan tertulis di docblock atau komentarnya; tidak dihapus dan tidak dilonggarkan diam-diam (spec bagian 14).
- Tugas dikerjakan berurutan, satu implementer satu waktu, dan hanya menyunting berkas di daftar Files tugasnya. Berkas bersama (`resources/js/pages/Dashboard.tsx`, `resources/js/components/beranda/types.ts`, `app/Http/Controllers/DashboardController.php`, `app/Services/Dashboard/RingkasanDasbor.php`, `app/Services/Dashboard/KonteksBeranda.php`, `config/hiking.php`, `tests/Pratinjau/HalamanPratinjauTest.php`) hanya disunting tugas yang menyebutnya. `HANDOVER.md` dan `CLAUDE.md` hanya disunting koordinator di Tugas 9.
- Berkas baru dibuat seperti W2 sampai W4: test dengan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:test --phpunit --no-interaction <Nama>`, kelas PHP dengan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:class --no-interaction <Jalur>` (notifikasi dengan `make:notification`), lalu isinya diganti persis dengan kode rencana. Berkas TypeScript dibuat langsung. Setelah menyunting PHP jalankan `vendor/bin/pint --format agent <berkas>`; setelah menyunting TypeScript jalankan `npx tsc --noEmit`. Tugas 7 menghapus satu rute: `npm run build` membangkitkan ulang keluaran Wayfinder sebelum `npx tsc --noEmit`.
- Blok "Ganti ... dengan ..." diterapkan berurutan dengan alat Edit. Teks lamanya unik di berkasnya pada keadaan dasar ditambah tugas-tugas sebelumnya, dan sudah diperiksa pada simulasi dan putaran ulang. Bila teks lama tidak ditemukan persis, berhenti dan laporkan NEEDS_CONTEXT; jangan mengarang padanannya. "Tulis ulang ... utuh" berarti isi berkas diganti seluruhnya dengan blok kodenya.

## Review Focus

- **Fase dan aksi utama yang keliru.** Trip berlangsung yang kalah oleh trip mendatang, trip yang tanggalnya sudah
  lewat di zona gunungnya tampil sebagai trip mendatang, B4 yang tidak berakhir sesudah laporan dikirim, atau aksi B2
  yang tidak sesuai jendela waktu dan izin. Dijaga Tugas 2 `DashboardPhaseTest::test_the_phases_follow_their_order_of_importance`,
  `test_the_mountains_zone_decides_whether_the_trip_day_has_passed`,
  `test_a_plan_whose_date_passed_without_starting_is_named_but_never_next`,
  `test_a_report_already_sent_or_an_older_hike_does_not_ask_again`, empat test aksi B2, dan
  `DashboardRelevanceTest` yang diperbarui.
- **Pengingat dan surel izin.** Surel hari buka yang terkirim dua kali, terkirim walau izin sudah dikonfirmasi,
  memakai jam zona yang salah, menyebut "aman" atau sisa kuota, atau membocorkan HTML nama trip. Dijaga Tugas 1
  `PermitWindowReminderTest` (8 test) dan `ReminderSheetTest` yang tetap hijau sesudah `TenggatIzin` di Tugas 3.
- **Jaminan, skor, dan otoritas.** Skor atau persen di props, "Siap berangkat", laporan komunitas yang tampil sebagai
  status resmi, kalimat R-012 yang hilang, atau jalur tak lengkap yang ditawarkan. Dijaga Tugas 2
  `DashboardPhaseTest::test_no_status_word_score_or_percent_reaches_the_home`, Tugas 4
  `TrailNewsTest::test_the_home_summarises_followed_mountains_with_official_and_community_apart` dan
  `OfficialStatusSafetySentenceTest` (Beranda wajib), Tugas 5
  `RecommendationExclusionTest::test_the_home_never_offers_an_incomplete_or_already_planned_trail` dan
  `TrailFitServiceTest::test_the_top_offers_put_fitting_trails_first_and_leave_out_poor_fits`.
- **Data yang belum ada.** Jendela izin yang tidak tercatat, prakiraan yang sudah terbit, pendaki tanpa profil, tanpa
  riwayat, tanpa gunung yang diikuti, atau pos berikutnya yang belum diketahui. Halaman menyebutnya, tidak mengarang.
  Dijaga Tugas 3 `DashboardDeadlineTest::test_a_window_never_recorded_is_named_without_a_date`, Tugas 4
  `TrailNewsTest::test_the_home_names_several_followed_mountains`, Tugas 6
  `DashboardLadderTest::test_only_completed_hikes_with_a_known_gain_become_rungs_once_per_trail`, dan Tugas 2
  `DashboardPhaseTest::test_a_hike_in_progress_is_b3_and_names_the_next_checkpoint`.
- **Biaya kueri, muat ulang parsial, dan data milik pengguna lain.** Beranda yang tumbuh bersama jumlah jalur,
  trip, atau kabar; lembar Pengingat yang ikut menghitung Beranda; trip orang lain di props. Dijaga Tugas 5
  `PageQueryBudgetTest::test_the_home_does_not_grow_with_trails_trips_or_news`, Tugas 2
  `DashboardPhaseTest::test_the_reminder_sheet_reload_does_not_compute_the_home`, dan
  `DashboardRelevanceTest::test_another_users_trip_never_appears`.

---

## Bukti simulasi rencana

Kode di rencana ini dijalankan tugas demi tugas di salinan repo di scratchpad sesi perencanaan (`w5sim`). Salinan itu
dibuat dengan `git archive` dari tree `b7e54014` (pohon kerja repo nyata persis sesudah W4 Tugas 6), ditambah `.github/`
dari subtree-nya (`.gitattributes` mengecualikannya dari arsip), `vendor/` dan `node_modules/` yang disalin utuh (bukan
junction), `.env` dari `.env.example` dengan APP_KEY sementara, `git init` tanpa add dan tanpa commit, dan konstanta
`ASAL` harness pratinjau diarahkan ke port 8140. Repo nyata tidak pernah ditulis. Setiap tugas diuji merah dengan test
tugas itu terhadap kode tugas sebelumnya, lalu hijau, lalu suite Unit dan Feature penuh, `npx tsc --noEmit`, Pint,
build, dan suite Pratinjau. Sesudah dirakit, teks rencana ini diputar ulang dari salinan dasar yang bersih (`w5putar`,
dibuat dengan cara yang sama) memakai pengurai yang hanya membaca blok "Buat", "Tulis ulang", "Ganti", dan "Hapus
berkas" di rencana ini; angka di tabel adalah hasil putaran ulang itu.

| Tugas | Merah (filter tugas, sebelum implementasi) | Hijau (filter yang sama) | Suite Unit dan Feature | Pratinjau | Pohon sesudah tugas |
|---|---|---|---|---|---|
| Dasar `b7e54014` | | | 1.921 test, 1.919 lulus, 2 dilewati | 18 lulus | dasar putaran ulang |
| 1 Pengingat izin | 114 test: 108 lulus, 5 gagal, 1 error | 114 lulus | 1.929 test, 1.927 lulus, 2 dilewati | tidak dijalankan (tanpa perubahan layar) | identik dengan simulasi |
| 2 Fase Beranda | 112 test: 85 lulus, 12 gagal, 15 error | 112 lulus | 1.946 test, 1.944 lulus, 2 dilewati | 22 lulus | identik |
| 3 Tenggat | 55 test: 47 lulus, 8 error | 55 lulus | 1.954 test, 1.952 lulus, 2 dilewati | 22 lulus | identik |
| 4 Kabar | 60 test: 54 lulus, 1 gagal, 5 error | 60 lulus | 1.959 test, 1.957 lulus, 2 dilewati | 22 lulus | identik |
| 5 Jalur lain | 121 test: 117 lulus, 1 gagal, 3 error | 121 lulus | 1.964 test, 1.962 lulus, 2 dilewati | 22 lulus | identik |
| 6 Tangga | 38 test: 34 lulus, 4 error | 38 lulus | 1.968 test, 1.966 lulus, 2 dilewati | 22 lulus | identik |
| 7 Titipan W3 | 96 test: 93 lulus, 3 gagal | 96 lulus | 1.970 test, 1.968 lulus, 2 dilewati | 22 lulus | identik |
| 8 Dokumen | tidak ada test baru (102 test penjaga dokumen lulus sebelum dan sesudah) | 102 lulus | 1.970 test, 1.968 lulus, 2 dilewati | tidak dijalankan | identik |

Sesudah setiap tugas `npx tsc --noEmit` keluar tanpa galat dan `vendor/bin/pint --test --format agent` lulus;
`npm run build` lulus sesudah Tugas 2 sampai 7.

Angka di atas memakai SQLite dalam memori. Suite bawaan `php artisan test --compact` (Unit, Feature, dan Spatial
menurut `defaultTestSuite`) sesudah Tugas 8: 2.002 test, 1.968 lulus, 34 dilewati (32 test Spatial melewati dirinya
tanpa `SPATIAL_TEST_DSN`, ditambah 2 test yang sudah dilewati di dasar). Tidak ada test Spatial baru, karena W5 tidak
menambah migrasi, PostGIS, atau kueri spasial; pemeriksaan paritas PostgreSQL ada di Tinjauan diri.

**Putaran ulang.** Satu kali, dari salinan bersih dasar (`w5putar`, dibuat dengan cara yang sama dengan `w5sim`, `ASAL` port 8140) dengan pengurai yang hanya membaca blok rencana. Pengurai membaca 132 operasi (20 "Buat", 5 "Tulis ulang ... utuh", 107 blok "Ganti", tanpa "Hapus berkas"), dan setiap teks lama blok "Ganti" ditemukan tepat sekali saat diterapkan. Pohon sumber sesudah setiap tugas (tanpa `vendor`, `node_modules`, keluaran build, keluaran Wayfinder, dan `public/_pratinjau`) sama berkas demi berkas dengan potret simulasi tugas itu: 0 berkas baru, berubah, atau hilang untuk Tugas 1 sampai 8. Akhir putaran: Unit dan Feature 1.970 test (1.968 lulus, 2 dilewati), suite bawaan 2.002 test (1.968 lulus, 34 dilewati), `npx tsc --noEmit` tanpa galat, Pint lulus, build lulus, dan Pratinjau 22 lulus. Terhadap tree dasar, Tugas 1 sampai 8 menambah 20 berkas dan mengubah 35, tanpa berkas yang dihapus. Sesudah putaran, hanya teks di luar blok kode yang disunting (angka, Pra-cek butir 12, OPEN ISSUE, dan bagian paritas PostgreSQL); daftar operasi rencana final diperiksa sama dengan yang diputar (sidik SHA-256 daftar operasi `f05124a8...` sama).

**Uji kering terhadap repo nyata.** Hanya membaca, 7 Oktober 2026 siang, sesudah W4 Tugas 7: ke-132 operasi diterapkan berurutan di memori di atas pohon kerja repo nyata, tanpa menulis berkas apa pun. Hasilnya 0 masalah jangkar. Empat berkas yang disentuh rencana sudah berubah sejak dasar, semuanya dokumen W4 Tugas 7 (`DESIGN.md`, `docs/ARCHITECTURE.md`, `docs/PRD.md`, `docs/fitur/04-perjalanan-persiapan.md`), dan rencana menyunting keempatnya hanya dengan blok "Ganti" berjangkar satu baris di Tugas 8 (Pra-cek butir 12). Tidak ada berkas "Buat" atau "Tulis ulang" yang menimpa isi yang sudah berubah, dan tidak ada berkas ber-CRLF.

**Pengukuran di peramban** (Chrome lewat MCP chrome-devtools, pratinjau statis salinan di port 8140, emulasi seluler
dengan sentuh dan rasio piksel 1 untuk 320 dan 390 px, desktop 1440 px rasio 1):

- Reflow: `document.documentElement.scrollWidth` sama dengan `clientWidth` untuk `dasbor`, `dasbor-b0`, `dasbor-b1`,
  `dasbor-b3`, dan `dasbor-b4` di 320 x 568 maupun 390 x 844, dan tidak ada unsur di luar wadah gulir yang melewati tepi
  kanan.
- Langkah kartu B2: di 320 px empat langkah satu baris (lebar 47, 73, 62, dan 47 px pada `top` yang sama), tanpa
  kata terpotong; di 390 px masing-masing 75 px.
- Tinggi kartu (DK-31): B2 9 `article` dan `section`, B1 7, B4 7, B0 1, B3 1; tidak ada yang isinya terpotong
  (`scrollHeight` melebihi `clientHeight`) dan tidak ada kelas tinggi tetap.
- Tinggi halaman: di 390 px B2 2.955 px, B1 1.635, B4 1.739, B0 859, B3 844; di 320 px B2 3.306, B1 1.748, B4
  1.855, B0 932, B3 817.
- 1440 x 900: kolom utama 704 px dan kolom samping 352 px (22rem) pada B2, B1, dan B4; B0 dan B3 satu kolom.
- Tangkapan: 20 gambar (lima fase, 390 dan 1440, terang dan gelap) di scratchpad sesi perencanaan
  (`rencana/w5-dasbor*.png`), bukan di repo; tangkapan resmi diambil ulang di Tugas 9.
- Bundel (build salinan): `app` JS 355,91 menjadi 355,97 kB (gzip 111,87 menjadi 111,90), CSS 65,68 menjadi 69,69 kB
  (gzip 12,88 menjadi 13,70), potongan `Dashboard` 4,06 menjadi 18,00 kB (gzip 1,54 menjadi 4,89). Tanpa paket baru.
- Kueri: Beranda B2 lengkap 31 kueri per permintaan, sama untuk 4 dan 24 jalur; muat ulang parsial lembar Pengingat
  (`only: daftarPengingat`) 5 kueri, dan responsnya hanya membawa `errors` dan `daftarPengingat`.

**Cacat yang ditemukan simulasi dan sudah diperbaiki di kode rencana ini** (dicatat supaya peninjau tahu mengapa
bentuknya begini):

- `grid-cols-4` pada empat langkah kartu B2 ditolak `TapTargetTest`; langkah memakai `flex` dengan `min-w-max` per
  langkah, sehingga nama langkah tidak terpotong di tengah kata pada 320 px (sebelumnya "Renc/ana", "Persiapa/n").
- Kalimat B0 semula mengetik "Belum dinilai" di klien; `TrustGrammarTest` menolaknya, dan kalimatnya ditulis ulang.
- Teks sr-only (position absolute) di kartu "Jalur lain untuk Anda" melebarkan halaman 390 px menjadi 650 px
  (`scrollWidth`), karena daftar gulirnya bukan containing block; daftar itu kini `relative`.
- Judul kartu B3 dan B4 semula mengulang kalimat ringkasan di bawah sapaan; kartu B3 kini dibuka dengan pos
  berikutnya dan kartu B4 dengan nama trip.
- Tautan "Buka rencananya" bertinggi 44 px di dalam paragraf membuat baris terakhir paragraf setinggi 44 px; tautannya
  kini berdiri di bawah kalimatnya dengan label "Ubah tanggal atau batalkan".
- Ketiga kartu tawaran semula menulis kalimat yang sama ("Tingkat pengalaman Anda sesuai ..."); kartu Cocok berikutnya
  kini memakai kalimat `why_it_fits` berikutnya yang belum dipakai.
- Kalimat dukungan keputusan Beranda lama sempat terbuang saat halaman ditulis ulang; kalimatnya dikembalikan apa
  adanya dan dijaga `DashboardPhaseTest::test_the_home_still_says_it_supports_decisions_and_declares_nothing_safe`.

**Temuan di luar cakupan W5 (OPEN ISSUE):**

- Bilah tab W2 memotong label di tengah kata pada 320 px ("Berand/a", "Menda/ki", "Progre/s"); diukur di pratinjau,
  di luar berkas W5.
- `docs/fitur/05-kabar-kondisi.md` menyebut "redesain W5" untuk halaman Kabar, sedangkan rencana induk W5 hanya memuat
  kabar di Beranda; halaman `/kabar` tidak didesain ulang di rencana ini (Konflik 9).
- Kandidat "Jalur lain untuk Anda" dibatasi 500 jalur menurut id (`hiking.beranda.batas_kandidat`), sama dengan pola
  Jelajah; katalog nasional butuh pemilihan kandidat terindeks (W8). Di PostgreSQL `withPublishabilityData()` memilih
  `*` termasuk geometri (titipan W8 yang sudah tercatat: kolom eksplisit untuk kandidat Jelajah, rekomendasi, simpanan).
- Beranda B2 lengkap memakai 31 kueri per permintaan pada keadaan akhir simulasi: tetap terhadap jumlah data, tetapi
  ada pengulangan yang bisa dipangkas. `PendakianAktif::untuk()` berjalan dua kali (strip kerangka W2 dan penentu
  fase), daftar gunung yang diikuti dibaca dua kali (`TrailNewsService::followedMountains()` dan `cakupan()`),
  `effectiveStatusesForTrails()` dipanggil dua kali (jalur trip, lalu kandidat tawaran) dengan dua kueri per
  panggilan, dan `requirementFor()` memakai dua kueri (jalur, lalu gunung). Daftar kuerinya diambil dengan
  `DB::listen` pada skenario `PageQueryBudgetTest::test_the_home_does_not_grow_with_trails_trips_or_news`. Kandidat
  penyederhanaan untuk W9.
- `DemoSeeder` membuat status dan laporan contoh dengan `created_at` saat seeding, sehingga kabar pratinjau berbunyi
  "Dicatat 0 detik yang lalu".
- Trip lama yang pengingatnya sudah menyala baru mendapat jadwal hari buka izin pada sinkronisasi berikutnya (ubah
  rencana atau sakelar); tidak ada pengisian mundur (Konflik 7).

## Keadaan awal yang terukur (FAKTA, 7 Oktober 2026)

| Butir | Keadaan | Bukti |
|---|---|---|
| Suite dasar | Unit dan Feature 1.921 test (1.919 lulus, 2 dilewati), sama dengan angka koordinator untuk tree `b7e54014` | `php artisan test --compact --testsuite=Unit,Feature` di `w5sim` dan `w5putar` |
| Pratinjau dasar | 18 halaman | `php artisan test --compact --testsuite=Pratinjau` di salinan dasar |
| Beranda | Empat keadaan (`profil`, `berjalan`, `terdekat`, `mulai`), judul dokumen "Dasbor", sapaan "Selamat datang, {nama}", kartu trip terdekat dengan hitung mundur dan "Pemeriksaan terakhir: {kata kesiapan}", navigasi "Lanjutkan ke", dan kalimat dukungan keputusan; tanpa tenggat, kabar, tawaran jalur, atau tangga | `app/Services/Dashboard/RingkasanDasbor.php` (dibuat dengan `new RingkasanDasbor($user)`), `app/Http/Controllers/DashboardController.php`, `resources/js/pages/Dashboard.tsx` |
| Pengingat | H-3 dan H-1 pukul 07.00 zona gunung; tidak ada pengingat hari pemesanan izin dibuka | `app/Services/Trips/TripReminderScheduler.php` |
| Lembar Pengingat | Tenggat izin per trip mendatang ditulis sebagai kalimat (W2, ruling W2-6a #6 untuk jendela yang tidak tercatat) | `app/Services/Trips/DaftarPengingat.php`, `tests/Feature/ReminderSheetTest.php` |
| Alasan kartu | `TrailFitService::alasan()` mendahulukan faktor lemah untuk semua jalur yang lolos, termasuk yang berlabel Cocok (ruling W3-C7 sementara) | `app/Services/TrailFitService.php` |
| Kabar | `TrailNewsService::forUser()` menggabungkan status dan laporan dalam satu batas; `KabarJalur::untuk()` dengan cuaca untuk halaman `/kabar` | `app/Services/TrailNewsService.php`, `app/Services/Community/KabarJalur.php` |
| Rute simpan lama | `trails.timbang` (`ConsiderationController::toggle()`) hanya dipanggil test; tombol simpan memakai `trails.saved.store` dan `trails.saved.remove` | `routes/web.php` baris 120, `resources/js/components/trails/TombolSimpan.tsx` |
| Ikon | Registri Phosphor tanpa kalender; paket `@phosphor-icons/*` tidak terpasang | `resources/js/components/ui/ikon/phosphor.ts` |
| Data contoh | Trip Budi "Gede via Cibodas" PLANNED 15 hari lagi, pengingat menyala, tanpa butir persiapan; tiga pendakian tuntas (Andong 380 m, Batur 600 m, Prau via Wates 700 m); Budi tidak mengikuti gunung apa pun; aturan izin tingkat gunung Gede H-30 sampai H-3 tanpa tautan pemesanan; satu laporan komunitas lolos moderasi di Gede | `database/seeders/DemoSeeder.php` |

## Ruling rencana (W5-R1 sampai W5-R24)

Setiap ruling ditulis dengan format keputusan, alasan, dan akibat bila salah.

| ID | Keputusan | Alasan | Akibat bila salah |
|---|---|---|---|
| W5-R1 | Dasar rencana adalah tree `b7e54014` (W4 Tugas 6 selesai). Jangkar "Ganti" dihitung dari keadaan itu. Berkas yang akan diubah W4 Tugas 7, Tugas 8, dan gelombang perbaikan akhir W4 tidak dijadikan jangkar (`Trips/Preparation.tsx`, `KepalaTrip`, `TripSteps`, `TripLifecycle`, `KartuTrip`, `Trips/Index`, `Trips/Create`); W5 hanya memanggil API publiknya. Dokumen disunting di jangkar yang sama di tree dasar dan sesudah W4 Tugas 7 (diperiksa terhadap paket `review-task7-b7e5401..bdccf73.diff`) | Brief koordinator: repo nyata terus berubah selama W5 ditulis | Jangkar yang tertabrak perubahan W4 sesudah uji kering ditemukan Pra-cek; implementer berhenti NEEDS_CONTEXT |
| W5-R2 | Sembilan tugas: pengingat hari buka izin (1), fase Beranda (2), tenggat (3), kabar (4), jalur lain dan urutan alasan (5), tangga (6), titipan W3 (7), dokumen dan GR-06 (8), verifikasi koordinator (9). Pengingat lebih dulu karena aksi B2 "Ingatkan saat dibuka" bergantung padanya | Satu tugas satu unit tinjauan; Tugas 2 besar karena kerangka halaman dan kontrak props lahir di sana | Tugas 2 boleh ditinjau dua putaran |
| W5-R3 | Urutan fase: B3 (pendakian berlangsung), B2 (trip mendatang), B4 (pendakian tuntas paling lama tujuh hari yang belum dilaporkan), B0 (profil belum diisi), B1. Pendaki tanpa profil yang punya trip atau baru turun gunung melihat trip atau ajakan melapor lebih dulu | Spec bagian 6.1 menyebut syarat setiap fase tanpa urutan; yang terikat waktu lebih dulu, seperti dasbor W2 ("urutan kepentingan, bukan urutan alur") | Pendaki tanpa profil dengan trip melihat ajakan profil lewat "Jalur lain untuk Anda", bukan kartu utama (Konflik 3) |
| W5-R4 | Trip mendatang adalah trip belum dimulai (DRAFT, PLANNED, READY_FOR_DEPARTURE) yang tanggal mulainya belum lewat di zona gunungnya: kueri berlantai tanggal WIB, lalu tiga kandidat diperiksa dengan `KepalaTrip::hariMenuju()` di zona masing-masing | Trip WIT yang hari ini sudah lewat di Papua tidak boleh terbaca mendatang selama WIB masih di tanggal yang sama | Trip keempat terdekat dengan tanggal yang sama tidak diperiksa; terjadi hanya bila tiga rencana pertama semuanya lewat di zona timur |
| W5-R5 | Rencana yang tanggalnya lewat tanpa dimulai (W4-1c) tidak pernah menjadi kartu B2. Pada B0, B1, dan B4 Beranda menyebut yang terbaru dalam satu kalimat dengan tautan "Ubah tanggal atau batalkan" ke ruang kerja trip, tempat penanda "Tanggal rencana sudah lewat" dan "Ubah rencana" (W4-1c) berada. Pada B2 dan B3 rencana itu tidak disebut | Satu fokus per fase; ruang kerja dan indeks trip sudah membawa penandanya | Pendaki dengan trip mendatang tidak diingatkan rencana lama yang lewat di Beranda; tetap terlihat di tab Trip |
| W5-R6 | Aksi utama B2 dipilih menurut fase waktu trip lalu urusan yang tertunda: hari H "Mulai pendakian" (tab Mendaki; memulai milik W6); H-3 sampai H-1 "Cek kesiapan"; izin wajib belum diurus dengan jendela terbuka dan tautan resmi tercatat "Pesan di sistem resmi" (tautan keluar); jendela belum dibuka, pengingat mati, dan hari bukanya masih akan datang "Ingatkan saat dibuka"; butir kritis belum lengkap atau daftar belum ada "Lanjutkan persiapan"; selain itu "Buka ruang kerja" | Spec bagian 6.1: aksi "menurut fase trip". Dalam jendela prakiraan, Cek kesiapan memuat butir izin yang kurang beserta aksi "Pesan" (W4), jadi tidak ada urusan yang hilang | Pada H-3 jalur dengan izin belum dipesan dan jendela masih terbuka, pemesanan berjarak satu ketukan lewat Cek (Konflik 4) |
| W5-R7 | "Ingatkan saat dibuka" menyalakan pengingat trip lewat rute yang sudah ada (`PUT trips.reminders.update`, `aktif: true`), dan pengingat itu kini juga mengirim satu surel pada hari pemesanan dibuka pukul 07.00 zona gunung (jenis `IZIN`). Tidak ada sakelar, persetujuan, rute, atau tabel baru. Sesudah menyala pendaki dibawa ke ruang kerja trip, tempat jadwalnya tampil bersama pesan "Pengingat dinyalakan" | Spec bagian 6.1 menamai aksi "pengingat"; tanpa surel pada hari buka, aksinya hanya janji. Satu persetujuan surel per trip (keputusan W0) | FR-24 bertambah satu jenis pengingat: governance record GR-06 (Konflik 1) |
| W5-R8 | Butir izin dianggap diurus bila butir sistem berlabel `PreparationService::PERMIT_LABEL` (tanpa `preparation_item_id`) dikonfirmasi atau ditandai tidak berlaku: satu predikat `PreparationService::izinDiurus()` untuk penjadwal (surel tidak dikirim), aksi B2, dan baris tenggat | Satu aturan satu pemilik; kunci stabil butir sistem menunggu W8 (ruling W4-C7b) | Penggantian label sebelum W8 memutus pengenalan butir izin di empat tempat sekaligus, sama seperti sekarang |
| W5-R9 | Beranda tidak menulis status trip ("Siap berangkat") maupun kata keadaan kesiapan untuk trip yang belum dimulai. Kartu B2 menulis empat langkah dari `TripSteps` dan peringatan yang mengubah rencana: vonis basi atau jalur ditarik (dari langkah Cek), dan status resmi Tutup atau Dibatasi yang berlaku sekarang | R-014; ruling W4-C4 menyerahkan kata di Beranda ke W5; spec bagian 6.1 menyebut "kotak kesiapan" sebagai hitungan butir | `StaleReadinessTest` membaca peringatan dari props, bukan dari sumber halaman |
| W5-R10 | Fase B4: riwayat bertaut trip yang selesai paling lama `hiking.beranda.hari_ajakan_lapor` (7) hari lalu, belum bertaut laporan, dan pendakinya belum menulis laporan untuk jalur itu sejak selesai. Aksinya "Bagikan laporan kondisi" ke `reports.create` dengan jalur dan trip, sehingga laporannya bertaut ke riwayat itu | Spec bagian 6.1 ("selesai ≤7 hari, belum melapor"); kesegaran laporan diukur dari tanggal pendakian (§59) | Laporan yang dikirim lewat jalan lain untuk jalur yang sama juga menutup B4 |
| W5-R11 | Kartu B2 tanpa foto: foto berlisensi menunggu W8 (DK-37), jadi visual datanya garis profil jalur (`GarisProfil`) dan tiga angka. Empat langkah sebagai bilah dengan centang sesudah nama langkah yang selesai, langkah berikutnya bilah kuning pos dan `aria-current`; persiapan kritis sebagai ruas per butir dengan kalimat "N dari M" dan tiga nama butir yang belum | Prototype 01; DK-31 (tinggi mengikuti isi); R-005 | Selisih dengan acuan dicatat README tangkapan W5 |
| W5-R12 | Kartu B3 dibuka dengan "Pos berikutnya: {pos}" (atau "belum diketahui") dan satu aksi "Buka mode lapangan"; nama jalurnya ada di kalimat ringkasan. Strip pendakian aktif W2 tetap tampil di Beranda (`ActiveHikeStripTest`). Tenggat, kabar, tawaran, dan tangga tidak dikirim pada B3 | Satu fokus di lapangan | Dua jalan ke mode lapangan di satu layar (Konflik 8) |
| W5-R13 | Tenggat terdekat: jendela pemesanan izin dari `Trips\TenggatIzin` (diekstrak dari `DaftarPengingat`, yang kini mendelegasikan tanpa mengubah kalimatnya), hari prakiraan BMKG untuk tanggal mulai terbit (`PreparationService::terbitPrakiraan()`), dan surel H-3 dan H-1 yang masih menunggu; surel hari buka izin ditulis di baris hari bukanya. Penanda kalender seperti prototype 01 disembunyikan dari pembaca layar yang mendapat tanggal lengkapnya; zona disebut sekali ("Waktu gunung, WIB"); tahun bila bukan tahun berjalan (W3-1g); jendela yang tidak tercatat disebut tanpa tanggal di akhir (R-007) | Spec bagian 6.1; satu sumber kalimat jendela izin untuk lembar Pengingat dan Beranda | `ReminderSheetTest` menjaga kalimat lama tetap sama |
| W5-R14 | Kabar: `KabarJalur::ringkas()` (dua perubahan status resmi terbaru dan satu laporan komunitas terbaru dari kueri per jenis, `TrailNewsService::terbaru()`), hanya pada B1, B2, dan B4. Status bergaris padat merek bila bersumber OFFICIAL, garis rambut bila tidak; laporan memakai kartu `KondisiPendaki` halaman gunung; kalimat R-012 di samping status; tautan "Semua kabar" ke `/kabar`. Cuaca tidak ikut | R-009, R-010; prototype 01; cuaca dibaca dari snapshot di halaman Kabar | Laporan lama yang kalah oleh banyak status baru tetap tampil berkat kueri per jenis |
| W5-R15 | Pada B2, gunung tujuan trip yang belum diikuti ditawarkan untuk diikuti (`POST mountains.follow`, kembali ke Beranda) | Penutupan diumumkan untuk kawasan; tujuan trip adalah kawasan yang paling perlu diikuti | Satu tombol sekunder di bagian kabar |
| W5-R16 | "Jalur lain untuk Anda": paling banyak tiga dari `TrailFitService::teratas()`, Cocok lebih dulu lalu skor internal mesin (tidak keluar dari service), tanpa Kurang cocok dan tanpa jalur yang gagal syarat dasar. Kandidat: jalur terbit dengan data lengkap (lapis pertama R-008 dan R-022; `evaluate()` lapis kedua), tanpa jalur trip pendaki itu yang DRAFT, PLANNED, READY_FOR_DEPARTURE, atau IN_PROGRESS, dibatasi `hiking.beranda.batas_kandidat` (500). Hanya pada B1, B2, dan B4; pendaki tanpa profil mendapat ajakan mengisi profil | Spec bagian 6.1 dan induk butir 4; R-003 | Batas kandidat menurut id (OPEN ISSUE) |
| W5-R17 | Ruling W3-C7 diputuskan: untuk jalur berlabel Cocok, satu kalimat alasannya adalah kalimat pertama `why_it_fits` dari `RecommendationExplanationService`; label lain tetap mendahulukan faktor lemah. Berlaku di `TrailFitService::alasan()`, jadi Jelajah, rincian jalur, dan Beranda sepakat. Kartu tawaran Beranda tidak mengulang kalimat kartu sebelumnya | Kalimat perhatian di kartu Cocok terbaca bertentangan dengan labelnya; spec bagian 6.1 meminta alasan dari `RecommendationExplanationService`; faktor lemah tetap tertulis di "Kenapa?" (`perluDiperhatikan`) | Governance record GR-06 (Konflik 2) |
| W5-R18 | Tangga tanjakan: satu anak tangga per jalur dari pendakian yang tuntas (COMPLETED), empat tanjakan tertinggi, ditambah trip mendatang sebagai anak tangga rencana bergaris putus-putus; diurutkan naik. Kalimatnya dari `ProgressLadderService::bandingkanDenganRiwayat()` ditambah kalimat BR-02. Satu seri tanpa legenda, nilai tertulis di setiap baris, batang tanpa jalur latar. Catatan bulanan prototype ("September: 1 pendakian tercatat ...") tidak dibangun | Spec bagian 6.1 ("tangga bila ada riwayat"); R-015; skill dataviz; catatan bulanan tidak punya dasar di spec | Selisih dengan acuan dicatat README tangkapan W5 |
| W5-R19 | Susunan: urutan ponsel mengikuti prototype 01 (kartu fase, tenggat, kabar, jalur lain, tangga). Mulai 1024 px, jalur lain dan tangga pindah ke kolom samping 22rem; tanpa isi samping halaman tetap satu kolom selebar 3xl. "Jalur lain" bergulir menyamping dengan titik henti di ponsel dan bertumpuk di kolom samping | Prototype 01 hanya ponsel; dua kolom memberi kartu fase lebar baca yang wajar di desktop | Tinggi kolom samping bisa melebihi kolom utama di B1 |
| W5-R20 | Sapaan dihitung di klien dari jam perangkat (04.00 sampai 10.59 pagi, 11.00 sampai 14.59 siang, 15.00 sampai 17.59 sore, selain itu malam) dengan nama lengkap seperti sebelumnya. Judul dokumen "Beranda" (sebelumnya "Dasbor"), sama dengan label tabnya | Spec bagian 6.1; nama tab sudah "Beranda" sejak W2 | `IndonesianInterfaceTest` diperbarui |
| W5-R21 | Navigasi "Lanjutkan ke" dilepas: keempat tujuannya dijangkau tab (Jelajah, Trip, Progres yang menautkan riwayat) dan halaman Profil (`RingkasanProfilPendakian` di `pages/Profile/Edit.tsx` menautkan profil pendaki). Kalimat dukungan keputusan Beranda tetap, apa adanya | Satu titik fokus per layar; R-012 dan R-014 | Tautan cepat ke riwayat pindah satu ketukan lebih jauh |
| W5-R22 | Tanpa gerak baru: tidak ada framer-motion dan tidak ada View Transition. Umpan balik tekan memakai kelas yang sudah ada; `prefers-reduced-motion` sudah dijepit global di `resources/css/app.css` | Beranda tidak punya perpindahan keadaan di tempat yang butuh animasi; biaya framer-motion terukur (Perbandingan skill) | Bila pemilik menginginkan transisi antarhalaman, View Transition Inertia dipertimbangkan di W9 |
| W5-R23 | Titipan W3 ke W5 dikerjakan di Tugas 7: rute `trails.timbang` dan `ConsiderationController::toggle()` dihapus (Beranda tidak memakainya; `ConsiderationService::toggle()` tetap, dipakai test sebagai pembantu); laporan di kartu "Laporan kondisi dari komunitas" dipisah garis, bukan kartu di dalam kartu; sel kecocokan Bandingkan tidak mengulang "Kecocokan dasar" dan "Kenapa?" menyebut nama jalurnya untuk pembaca layar | `HANDOVER.md` bagian 0 "Ke W5" (empat butir); ruling W3-F3 | Test yang memakai rute lama dipindah ke `trails.saved.store` dan `trails.saved.remove` dengan niat yang sama |
| W5-R24 | Pratinjau bertambah empat halaman (`dasbor-b0`, `dasbor-b1`, `dasbor-b3`, `dasbor-b4`); `dasbor` menjadi B2 dengan daftar persiapan dibangkitkan (tiga butir kritis dikonfirmasi) dan Gunung Gede diikuti. Data contoh disiapkan di test pratinjau, bukan di `DemoSeeder` | Tangkapan Tugas 9 untuk kelima fase | `DemoSeeder` tetap, jadi Beranda pemilik B2 tanpa kabar sampai ia mengikuti gunung |

### Konflik yang perlu ruling koordinator

1. **FR-24 bertambah pengingat hari pemesanan izin dibuka (W5-R7, GR-06).** Ini perubahan requirement PRD (§149),
   bukan hanya tampilan. Rekomendasi: terima, karena spec bagian 6.1 yang disetujui pemilik menetapkan aksi
   "Ingatkan saat dibuka", dan pengingatnya berjalan di bawah persetujuan surel yang sama. Alternatif: aksi itu hanya
   membuka kartu pengingat ruang kerja tanpa jenis pengingat baru (tidak ada surel pada hari buka).
2. **Urutan alasan kartu W3-C7 diputuskan global (W5-R17, GR-06).** Kartu Jelajah dan rincian jalur ikut berubah
   untuk jalur Cocok. Rekomendasi: terima; mesin dan labelnya tidak berubah, faktor lemah tetap disebut di "Kenapa?".
3. **Urutan fase B3, B2, B4, B0, B1 (W5-R3).** Spec tidak menyebut urutan. Rekomendasi: terima. Alternatif: B0 di
   depan B2 dan B4 (profil lebih dulu), satu baris di `RingkasanDasbor::konteks()` dan satu test.
4. **Urutan aksi B2 menurut fase waktu (W5-R6).** Spec menulis aksi izin, pengingat, persiapan, cek, lalu hari H
   sebagai daftar, tanpa menyebut prioritas bila beberapa berlaku. Rekomendasi: terima. Alternatif: izin terbuka
   mengalahkan H-3 sampai H-1, satu pemindahan blok di `aksiPerjalanan()` dan satu test.
5. **Judul dokumen "Dasbor" menjadi "Beranda" (W5-R20).** Rekomendasi: terima; label tab sudah "Beranda".
6. **Navigasi "Lanjutkan ke" dilepas (W5-R21).** Rekomendasi: terima; tujuannya dijangkau tab dan Profil.
7. **Trip lama dengan pengingat menyala tidak mendapat jadwal hari buka izin sampai disinkronkan ulang.**
   Rekomendasi: catat di daftar izin akhir; bila pemilik ingin, satu perintah Artisan sekali jalan menyinkronkan trip
   PLANNED dan READY_FOR_DEPARTURE yang pengingatnya menyala (di luar W5, butuh izin karena menulis ke produksi).
8. **Strip pendakian aktif dan kartu B3 sama-sama menuju mode lapangan (W5-R12).** Rekomendasi: terima untuk W5;
   menyembunyikan strip di Beranda selama B3 mengubah keputusan W2 dan `ActiveHikeStripTest`.
9. **Titipan W3 di Tugas 7 dan halaman Kabar.** Prompt penulis menyebut titipan W3-C7 dan W3-F3; `HANDOVER.md` "Ke W5"
   juga memuat kartu di dalam kartu laporan komunitas dan sel `FitLine`, jadi keduanya ikut Tugas 7. Sebaliknya
   `docs/fitur/05-kabar-kondisi.md` menyebut "redesain W5" untuk halaman `/kabar` yang tidak ada di rencana induk W5;
   rekomendasi: pindahkan ke W8 atau W9 dan perbaiki dokumennya di gelombang itu.

### Pra-cek untuk koordinator

Rencana ditulis di atas tree `b7e54014`. Asumsi berikut diperiksa langsung di salinan itu; periksa ulang di repo nyata
sesudah W4 Tugas 7, Tugas 8, dan gelombang perbaikan akhir W4, sebelum Tugas 1.

| Asumsi | Tempat rencana bergantung | Keadaan di dasar |
|---|---|---|
| 1. Jangkar setiap blok "Ganti" ada tepat sekali | Semua tugas | Sesuai di dasar dan pada uji kering repo nyata (lihat Bukti simulasi). Jalankan `cek-jangkar.php` dari folder ledger W4 terhadap brief setiap tugas sebelum dispatch |
| 1a. Berkas yang ditulis ulang utuh belum berubah sejak dasar | Tugas 1 (`app/Services/Trips/TripReminderScheduler.php`, blob dasar `9a526a353fa6`), Tugas 2 (`app/Services/Dashboard/RingkasanDasbor.php` `dac04ac2017e`, `resources/js/pages/Dashboard.tsx` `990ce035762b`, `tests/Feature/DashboardRelevanceTest.php` `54d446c36d4a`), Tugas 5 (`Dashboard.tsx` sesudah Tugas 4) | Bila blob di repo nyata berbeda, perubahan W4 di berkas itu harus digabung manual; jangan menimpa |
| 1b. Suite repo nyata sesudah W4 | Angka setiap tugas | Dasar simulasi 1.921 test; selisih dari W4 Tugas 7, 8, dan perbaikan akhir ditambahkan koordinator ke setiap angka "pada simulasi rencana" |
| 2. W4-1c selesai: ruang kerja trip yang tanggalnya lewat menampilkan penanda "Tanggal rencana sudah lewat" dan "Ubah rencana" | Tugas 2 (tautan "Ubah tanggal atau batalkan") | Belum ada di dasar (butir wajib gelombang perbaikan akhir W4) |
| 3. W4-4d selesai: memindah tanggal trip mereset butir cuaca | Tugas 2 dan 3 (hitungan butir kritis, baris BMKG) | Belum ada di dasar (butir wajib gelombang perbaikan akhir W4) |
| 4. W4 M2: bila gelombang akhir menambah `TripStatus::belumDimulai()` | Tugas 2 (`RingkasanDasbor::BELUM_DIMULAI`) | Belum ada di dasar. Bila ada, pakai method itu untuk `belumDimulai()` dengan himpunan DRAFT, PLANNED, READY_FOR_DEPARTURE yang sama |
| 5. `KepalaTrip::untuk()` mengembalikan `jalur`, `gunung`, `jenis`, `hari`, `tanggal`, `jam`, `peserta`; `KepalaTrip::hariMenuju(CarbonInterface, string): ?string` dan `rentang()` statis | Tugas 2 | Sesuai di dasar; W4 akhir menyentuh `KepalaTrip` (M2) |
| 6. `TripSteps::for(TripPlan, ?OfficialStatusValue)` mengembalikan langkah berkunci `kunci`, `judul`, `keadaan`, `keterangan`, `peringatan` dengan kunci `jalur`, `rencana`, `persiapan`, `kesiapan` | Tugas 2 | Sesuai di dasar |
| 7. `PreparationService::kritis()` mengembalikan `dikonfirmasi`, `berlaku`, `belum`; `terbitPrakiraan(TripPlan): ?Carbon`; `PERMIT_LABEL` | Tugas 1 sampai 3 | Sesuai; W4 akhir M1 mungkin menyatukan jendela BMKG |
| 8. `PermitService::bookingWindowFor()` dengan keadaan `BELUM_DIBUKA`, `TERBUKA`, `DITUTUP`, dan H-closes masih terhitung terbuka | Tugas 2, 3 | Sesuai. Bila W4 akhir mengubah arti H-closes, kalimatnya ikut otomatis, tetapi tanggal di `DashboardDeadlineTest` diukur ulang |
| 9. `docs/PRD.md` baris "governance record berikutnya GR-05." | Tugas 8 | Sesuai di dasar. Bila W4 sudah mengubahnya menjadi GR-06, ganti GR-06 menjadi GR-07 di kalimat yang sama; GR-06 di rencana ini disisipkan sebelum "### Log keputusan", jadi jatuh sesudah GR-05 milik W4 |
| 10. `ReminderSheetTest` menguji kalimat `DaftarPengingat` lama | Tugas 3 | Sesuai; `TenggatIzin` mempertahankannya |
| 11. Pratinjau dasar 18 halaman dan `self::ASAL` | Tugas 2, 4 | Sesuai; rencana memakai `self::ASAL` apa adanya |
| 12. Jangkar "Ganti" di empat dokumen yang sudah diubah W4 Tugas 7 (fakta koordinator: sejak `b7e54014` repo nyata hanya berubah di `DESIGN.md`, `docs/ARCHITECTURE.md`, `docs/PRD.md` §146 GR-05, dan `docs/fitur/04-perjalanan-persiapan.md`, tanpa perubahan kode) | Tugas 8 Step 3, delapan blok | Setiap jangkar satu baris dan ditemukan tepat sekali di repo nyata pada uji kering 7 Oktober sesudah W4 Tugas 7 (nomor baris repo nyata): `docs/PRD.md` kalimat penunjuk "... governance record berikutnya GR-05." (27), "Pengguna dapat melihat pengingat yang akan datang beserta statusnya (menunggu atau terkirim)." (1077), dan judul "### Log keputusan" (2084, sesudah GR-05 milik W4); `DESIGN.md` "**Fokus tidak pernah jatuh ke body.**" (338, sesudah paragraf "Klaster Trip" W4); `docs/ARCHITECTURE.md` baris tabel `AuditLogService` dan `AnalyticsRecorder` (126, sesudah baris kelas halaman Trip W4) dan baris tabel "Sumber tile peta dan atribusinya" (90); `docs/fitur/04-perjalanan-persiapan.md` butir "Beranda" (18) dan baris tabel `trip_reminders` (51). Periksa ulang kedelapannya bila gelombang akhir W4 menyentuh dokumen yang sama. Catatan: kalimat penunjuk PRD di repo nyata masih menyebut GR-05 walau GR-05 sudah ada; Tugas 8 menggantinya menjadi GR-07 sesudah menambah GR-06. Bila gelombang akhir W4 lebih dulu membetulkannya menjadi GR-06, berlaku butir 9 |

## Perbandingan skill (DK-57)

Urutan wewenang sama dengan W4: arahan pemilik dan aturan kanonis, lalu `DESIGN.md` (arah) dan prototype 01
(patokan), lalu antislop, lalu saran skill. antislop berlaku mode 1 (selama pekerjaan) atas arahan pemilik yang sudah
berdiri; tidak ada pertanyaan baru kepada pemilik. Launcher impeccable tidak dijalankan karena dapat menulis berkas ke
`.claude/skills/` repo nyata; konteks dibaca langsung dari `DESIGN.md` dan acuan.

| Skill | Diambil | Ditolak dan alasannya |
|---|---|---|
| antislop (inti) | R-26: setiap kendali bekerja ("Ingatkan saat dibuka" benar-benar menjadwalkan surel, "Ikuti Gunung Gede", "Buka sistem resmi", "Semua kabar", "Lihat progres", "Ubah tanggal atau batalkan"). R-27: keadaan pertama kali (B0, B1), kosong (belum mengikuti gunung, belum ada tawaran, tanpa riwayat), menunggu (prakiraan belum terbit, jendela tidak tercatat), dan memuat (tombol "Ingatkan saat dibuka" dan "Ikuti" berstatus memproses). R-17 dan R-36: tanpa cuaca, kuota, atau pos karangan. Satu titik fokus per fase: satu tombol penuh, aksi lain berupa tautan teks | Tidak ada; antislop adalah saringan di atas `DESIGN.md` |
| antislop-copywriting | Aksi spesifik ("Isi profil pendaki", "Rencanakan trip", "Lanjutkan persiapan", "Pesan di sistem resmi", "Buka mode lapangan", "Bagikan laporan kondisi"); kalimat yang menyebut siapa yang ditunggu ("BMKG menerbitkan prakiraan untuk 3 hari ke depan ... Memount tidak menebaknya"); tanpa em dash dan tanpa "aman" sebagai klaim | Kalimat prototype "Tiga hal masih perlu Anda selesaikan" diganti hitungan dari data ("10 butir kritis belum dikonfirmasi"); "PERJALANAN BERIKUTNYA" kapital bertracking tidak dipakai |
| antislop-ui dan antislop-layoutmobile | Kebocoran gulir menyamping (R-03, R-35) dicari dengan `scrollWidth` dan ditemukan pada teks sr-only karusel, lalu ditutup; kolom yang runtuh ke satu kolom di ponsel; target 44 px; tanpa kartu di dalam kartu (laporan komunitas W3 dibereskan di Tugas 7) | Kaca untuk kartu Beranda (DESIGN.md: kaca hanya untuk lapisan chrome) |
| ui-ux-pro-max | Langkah dengan `aria-current="step"` dan kata keadaan sr-only; keterangan aksi terikat lewat `aria-describedby`; tanggal lengkap untuk pembaca layar di balik penanda kalender; tautan keluar diumumkan ", membuka situs resmi"; urutan heading h1 (sapaan), h2 per bagian, h3 di dalam kartu | Bilah persen kesiapan dan skor kecocokan (R-005, BR-09); keluaran `--design-system` (gradien, Lucide), sama dengan W1 sampai W4 |
| design-taste-frontend | Satu CTA per kartu dan tanpa duplikasi niat aksi; angka tabular; hierarki lewat bobot huruf. Skill ini menyatakan dirinya untuk halaman pemasaran, jadi hanya aturan yang cocok untuk halaman produk yang dipakai | Foto hero prototype (DK-37 menunggu W8); ekspresi halaman pemasaran |
| impeccable (Operate, `reference/craft-floor.md`) | Kosakata komponen yang sudah ada dipakai ulang (`Lencana`, `LabelKecocokan`, `LabelSumber`, `StatusAkses`, `KondisiPendaki`, `GarisProfil`, `AngkaRingkas`, `Tautan`, `Button`); setiap kendali punya keadaan fokus, tekan, dan memproses; tanpa eyebrow; lebar baca 3xl tanpa isi samping | Eyebrow "PERJALANAN BERIKUTNYA · CAMPING 2 HARI" diganti lencana jenis trip dan H-n |
| frontend-ui-engineering | Komposisi, bukan konfigurasi: `KartuFase` memilih komponen per jenis kartu, data datang terformat dari server; keadaan kosong dan galat per bagian; diperiksa di 320, 390, dan 1440 px | Skeleton memuat: halaman dirender server lewat Inertia, tanpa pengambilan data di klien |
| shadcn (rujukan saja, tanpa `npx shadcn init`) | Pola Card berkepala dan Empty dicocokkan dengan primitif proyek yang sudah ada | Memasang komponen shadcn (dependensi dan kosakata kedua); aturan "tanpa `space-y-*`" tidak dipakai karena kode proyek memakainya (konsistensi lebih dulu) |
| vercel-react-best-practices | `rendering-conditional-render` (ternary, bukan `&&`), `server-serialization` (props hanya yang digambar; `jalur` dibuang dari kartu B3 karena sudah di ringkasan), `rerender-no-inline-components` (`KartuPendek` di tingkat modul), `bundle-*` (tanpa framer-motion) | Aturan khusus Next.js dan RSC (dynamic import, `React.cache`) tidak berlaku untuk Inertia |
| vercel-composition-patterns | Varian eksplisit per jenis kartu alih-alih banyak prop boolean; anak, bukan render prop | Context provider: tidak ada keadaan bersama antarbagian |
| web-design-guidelines | Cincin fokus terlihat, `tabular-nums`, label tautan yang menyebut tujuannya, keadaan kosong yang menjelaskan dirinya | Panduan daring tidak diambil ulang di sesi ini; yang dipakai butir yang sudah dibaca |
| dataviz (tangga tanjakan) | Satu seri tanpa legenda, label langsung di setiap baris (nilai sebagai teks), teks memakai token teks, satu sumbu, ujung data membulat di sisi nilai, tanpa jalur latar | Lapisan tooltip: semua nilai sudah tertulis; validator palet: satu warna merek tanpa palet kategorikal |
| Gerak: CSS lawan framer-motion lawan vercel-react-view-transitions | CSS yang sudah ada: umpan tekan `umpanTekan` dan penjepit global `prefers-reduced-motion` di `resources/css/app.css` | framer-motion 14 (terpasang, tidak diimpor): paket ukur `size-rollup-motion.js` 123,5 kB mentah atau 39,2 kB gzip, `m` dengan `domAnimation` 74,7 kB atau 25,6 kB gzip, `animate` 53,0 kB atau 18,8 kB gzip, untuk Beranda yang tidak punya perpindahan keadaan di tempat. View Transition: React 19.3.0 mengekspor `ViewTransition` dan Inertia 3.7.1 punya opsi kunjungan `viewTransition`, tanpa biaya bundel, tetapi dukungan peramban sebagian (Chromium 125+, Safari 18.2+, Firefox 144+) dan tempatnya transisi antarhalaman di kerangka W2, bukan Beranda (W5-R22) |
| codebase-design (mattpocock) | Modul dalam: `KonteksBeranda` sebagai seam, setiap kelas bagian berantarmuka kecil `untuk(KonteksBeranda): ?array`; `TenggatIzin` diperdalam dari `DaftarPengingat` untuk dua pemanggil; `PreparationService::izinDiurus()` satu predikat untuk tiga pemanggil | Antarmuka atau adapter untuk setiap bagian: satu implementasi per bagian, jadi lapisan itu dangkal |
| archify | Tidak dipakai | Rencana ini tidak butuh diagram; fase Beranda ditulis sebagai tabel di GR-06 dan `DESIGN.md`. Skill itu menulis folder `.archify/` di direktori kerja |

**Pembacaan desain (antislop bagian 3):** Beranda aplikasi perencanaan pendakian untuk pendaki pemula Indonesia di
ponsel, mode Operate, bahasa visual editorial utilitarian yang hangat, dial ENERGY 2 / RHYTHM 2 / MOTION 2
(`DESIGN.md`). Setiap fase memberi satu keputusan: apa langkah berikutnya, kapan tenggatnya, dan apa yang berubah di
gunung yang diikuti, dengan sumber yang dapat diperiksa. Motif identitas: garis profil jalur dan bilah langkah.

**Inventaris skill untuk gelombang ini (CLAUDE.md bagian 7):**

- INTI: antislop, antislop-copywriting, antislop-ui, antislop-layoutmobile, impeccable, ui-ux-pro-max,
  design-taste-frontend (terbatas, lihat tabel), superpowers:writing-plans, testing-best-practices,
  laravel-best-practices (kelas halaman, Form Request yang sudah ada, kueri batch, notifikasi).
- PENDUKUNG: frontend-ui-engineering, vercel-react-best-practices, vercel-composition-patterns, web-design-guidelines,
  dataviz (tangga), codebase-design (batas modul), tailwindcss-development (grid `lg:grid-cols-[minmax(0,1fr)_22rem]`,
  scroll-snap), shadcn (rujukan pola saja), vercel-react-view-transitions (dibandingkan, tidak dipakai).
- LINTAS: superpowers:verification-before-completion dan Delivery Gate antislop; chrome-devtools MCP untuk pengukuran.
- N/A:
  - archify: tanpa diagram;
  - slides, banner-design, imagegen-frontend-web, imagegen-frontend-mobile, image-to-code: bukan artefak halaman;
  - brand dan brandkit: aset logo menunggu gerbang DK-16;
  - animate, improve-animations, emil-design-eng, find-animation-opportunities: tanpa gerak baru (W5-R22);
  - livewire-development dan volt-development: Livewire sudah dipensiunkan;
  - deploying-to-cloud dan deploy-to-vercel: tanpa rilis;
  - mobile-native dan write-swift: bukan aplikasi native; target sentuh diperiksa lewat antislop-layoutmobile;
  - skill caveman, ngodingpakeai, dan alur commit: tanpa commit (keputusan pemilik).

## File Structure

**Dibuat:**

- Tugas 1: `app/Notifications/PengingatIzinDibuka.php`, `tests/Feature/PermitWindowReminderTest.php`.
- Tugas 2: `app/Services/Dashboard/KonteksBeranda.php`, `resources/js/components/beranda/AksiUtama.tsx`, `resources/js/components/beranda/KartuFase.tsx`, `resources/js/components/beranda/KartuPerjalanan.tsx`, `resources/js/components/beranda/sapaan.ts`, `resources/js/components/beranda/types.ts`, `tests/Feature/DashboardPhaseTest.php`.
- Tugas 3: `app/Services/Dashboard/TenggatTrip.php`, `app/Services/Trips/TenggatIzin.php`, `resources/js/components/beranda/TenggatTerdekat.tsx`, `tests/Feature/DashboardDeadlineTest.php`.
- Tugas 4: `app/Services/Dashboard/KabarBeranda.php`, `resources/js/components/beranda/KabarDiikuti.tsx`.
- Tugas 5: `app/Services/Dashboard/JalurUntukAnda.php`, `resources/js/components/beranda/JalurUntukAnda.tsx`.
- Tugas 6: `app/Services/Dashboard/TanggaTanjakan.php`, `resources/js/components/beranda/TanggaTanjakan.tsx`, `tests/Feature/DashboardLadderTest.php`.

**Dihapus:** tidak ada berkas. Tugas 7 menghapus rute `trails.timbang` dan method `ConsiderationController::toggle()`.

**Diubah:**

- Tugas 1: `app/Services/PreparationService.php`, `app/Services/Trips/TripReminderScheduler.php`.
- Tugas 2: `app/Http/Controllers/DashboardController.php`, `app/Services/Dashboard/RingkasanDasbor.php`, `config/hiking.php`, `resources/js/pages/Dashboard.tsx`, `tests/Feature/DashboardRelevanceTest.php`, `tests/Feature/IndonesianInterfaceTest.php`, `tests/Feature/StaleReadinessTest.php`, `tests/Pratinjau/HalamanPratinjauTest.php`.
- Tugas 3: `app/Http/Controllers/DashboardController.php`, `app/Services/Dashboard/KonteksBeranda.php`, `app/Services/Dashboard/RingkasanDasbor.php`, `app/Services/Trips/DaftarPengingat.php`, `resources/js/components/beranda/types.ts`, `resources/js/pages/Dashboard.tsx`.
- Tugas 4: `app/Http/Controllers/DashboardController.php`, `app/Services/Community/KabarJalur.php`, `app/Services/TrailNewsService.php`, `resources/js/components/beranda/types.ts`, `resources/js/pages/Dashboard.tsx`, `tests/Feature/OfficialStatusSafetySentenceTest.php`, `tests/Feature/TrailNewsTest.php`, `tests/Pratinjau/HalamanPratinjauTest.php`.
- Tugas 5: `app/Http/Controllers/DashboardController.php`, `app/Services/TrailFitService.php`, `config/hiking.php`, `resources/js/components/beranda/types.ts`, `resources/js/pages/Dashboard.tsx`, `tests/Feature/PageQueryBudgetTest.php`, `tests/Feature/RecommendationExclusionTest.php`, `tests/Feature/TrailFitServiceTest.php`.
- Tugas 6: `app/Http/Controllers/DashboardController.php`, `resources/js/components/beranda/types.ts`, `resources/js/pages/Dashboard.tsx`.
- Tugas 7: `app/Http/Controllers/Trails/ConsiderationController.php`, `routes/web.php`, `resources/js/components/trails/FitLine.tsx`, `resources/js/components/trails/banding/TabelBanding.tsx`, `resources/js/components/trails/rinci/LaporanKomunitas.tsx`, `tests/Feature/CommunityPhotoTest.php`, `tests/Feature/ComparisonAsCostTest.php`, `tests/Feature/ConsiderationTest.php`, `tests/Feature/SavedRoutesTest.php`.
- Tugas 8: `CONTRIBUTING.md`, `DESIGN.md`, `docs/ARCHITECTURE.md`, `docs/PRD.md`, `docs/fitur/01-jelajah-rute.md`, `docs/fitur/04-perjalanan-persiapan.md`, `docs/fitur/05-kabar-kondisi.md`.

Tugas 9 (koordinator) membuat `docs/desain/tangkapan/w5/*.webp` dan `docs/desain/tangkapan/w5/README.md`, dan
memperbarui `HANDOVER.md` bagian 0 serta `.superpowers/sdd/2026-09-26-memount-v3-program.md`.

---

### Task 1: Pengingat hari pemesanan izin dibuka

Spec bagian 6.1 (aksi B2 "Ingatkan saat dibuka"); PRD §149 FR-24; ruling W5-R7 dan W5-R8. Bila aturan izin jalur
sebuah trip mencatat kapan pemesanan dibuka, pengingat trip yang menyala juga menjadwalkan satu surel pada hari itu
pukul 07.00 zona gunung (jenis `IZIN`), di bawah sakelar dan persetujuan yang sama dengan H-3 dan H-1. Surelnya
menautkan sistem resmi, tidak menjual izin, tidak menyebut kuota, dan tidak dikirim bila butir izin di persiapan sudah
diurus. Hari buka yang sudah lewat tidak dijadwalkan dan tidak disebut terlewat.

**Files:**
- Create: `app/Notifications/PengingatIzinDibuka.php`
- Modify: `app/Services/PreparationService.php`, `app/Services/Trips/TripReminderScheduler.php` (ditulis ulang utuh)
- Test: `tests/Feature/PermitWindowReminderTest.php` (baru)

**Interfaces:**
- Consumes: `PermitService::requirementFor(Trail): ?PermitRequirement`; `PermitRequirement` (`authority`, `booking_url`, `booking_opens_days_before`); `Timezone::forTrail()`, `Timezone::display()`; `TripReminder` (`jenis`, `dijadwalkan_untuk`, `keadaan()`); `config('hiking.reminders.local_hour')`; `PreparationService::PERMIT_LABEL`; rute `trips.reminders.update` dan `trips.show`; perintah `trips:kirim-pengingat`.
- Produces:
  - `App\Services\Trips\TripReminderScheduler::JENIS_IZIN = 'IZIN'`; konstruktor `__construct(PermitService $izin)` (di-resolve kontainer); `jadwal(TripPlan $trip): array<string, CarbonImmutable>` memuat `IZIN` bila hari bukanya tercatat; `bukaIzin(TripPlan $trip, PermitRequirement|false|null $aturan = false): ?CarbonImmutable` (`false` berarti belum dicari); `label('IZIN')` = "Saat pemesanan izin dibuka"; `dapatDiatur(TripPlan): bool` tetap;
  - `App\Services\PreparationService::izinDiurus(TripPlan $trip): bool` (statis; relasi `preparationItems` sudah dimuat);
  - `App\Notifications\PengingatIzinDibuka(TripPlan $trip, ?PermitRequirement $aturan)` (surel).

- [ ] **Step 1: Tulis test yang gagal.** Buat berkas test baru dengan `make:test` (Global Constraints), lalu ganti
isinya persis.

Buat `tests/Feature/PermitWindowReminderTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\PreparationCategory;
use App\Enums\PreparationStatus;
use App\Enums\TripStatus;
use App\Models\Mountain;
use App\Models\PermitRequirement;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Notifications\PengingatIzinDibuka;
use App\Notifications\TripReminderNotification;
use App\Services\PreparationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Pengingat hari pemesanan izin dibuka (spec v3 bagian 6.1, aksi B2 "Ingatkan saat dibuka";
 * rencana induk W5 butir 1 "pengingat jendela"). Pengingat ini ikut sakelar pengingat trip
 * (FR-24): menyalakan pengingat menjadwalkan H-3, H-1, dan hari pemesanan dibuka bila aturan
 * izin jalurnya mencatat kapan pemesanan dibuka.
 *
 * Waktu dibekukan pada Jumat 25 September 2026 pukul 10.00 WIB. Angka UTC dihitung tangan:
 * 07.00 WITA = 23.00 UTC hari sebelumnya.
 */
class PermitWindowReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-09-25 03:00:00', 'UTC'));
    }

    /**
     * Trip Rinjani (WITA) 20 November 2026 dengan aturan izin tingkat gunung: pemesanan dibuka
     * H-30, jadi hari bukanya Rabu 21 Oktober.
     *
     * @param  array<string, mixed>  $aturan
     */
    private function trip(array $aturan = [], string $tanggal = '2026-11-20'): TripPlan
    {
        $gunung = Mountain::factory()->create(['name' => 'Gunung Rinjani', 'timezone' => 'Asia/Makassar']);
        $jalur = Trail::factory()->for($gunung)->create(['name' => 'Rinjani via Sembalun']);

        PermitRequirement::factory()->create(array_merge([
            'mountain_id' => $gunung->id,
            'authority' => 'Balai Taman Nasional Gunung Rinjani',
            'booking_url' => 'https://contoh.test/erinjani',
            'booking_opens_days_before' => 30,
            'booking_closes_days_before' => 3,
        ], $aturan));

        return TripPlan::factory()->for($jalur)->create([
            'name' => 'Rinjani bersama Dimas',
            'planned_date' => $tanggal,
            'status' => TripStatus::PLANNED->value,
        ]);
    }

    private function nyalakan(TripPlan $trip, bool $aktif = true): void
    {
        $this->actingAs($trip->user)
            ->put(route('trips.reminders.update', $trip), ['aktif' => $aktif])
            ->assertRedirect(route('trips.show', $trip));
    }

    private function jadwalIzin(TripPlan $trip): ?string
    {
        return $trip->reminders()->where('jenis', 'IZIN')->first()?->dijadwalkan_untuk->utc()->format('Y-m-d H:i:s');
    }

    private function kirim(): int
    {
        return $this->artisan('trips:kirim-pengingat')->run();
    }

    public function test_the_opening_day_is_reminded_at_seven_in_the_mountains_zone_alongside_the_departure_reminders(): void
    {
        $trip = $this->trip();

        $this->nyalakan($trip);

        $this->assertSame('2026-10-20 23:00:00', $this->jadwalIzin($trip));
        $this->assertSame(['H-1', 'H-3', 'IZIN'], $trip->reminders()->orderBy('jenis')->pluck('jenis')->all());
    }

    /**
     * Tanpa catatan kapan pemesanan dibuka tidak ada tanggal untuk diingatkan, dan tanggalnya
     * tidak ditebak (R-007).
     */
    public function test_no_opening_reminder_without_a_recorded_opening_day(): void
    {
        $trip = $this->trip(['booking_opens_days_before' => null]);

        $this->nyalakan($trip);

        $this->assertNull($this->jadwalIzin($trip));
        $this->assertSame(['H-1', 'H-3'], $trip->reminders()->orderBy('jenis')->pluck('jenis')->all());
    }

    /**
     * Pemesanan yang sudah dibuka tidak dijadwalkan lagi, dan halaman trip tidak menyebutnya
     * pengingat yang terlewat: yang perlu dilakukan sekarang memesan, bukan menunggu surel.
     */
    public function test_an_opening_day_already_past_is_neither_scheduled_nor_reported_as_missed(): void
    {
        $trip = $this->trip(tanggal: '2026-10-10');

        $this->nyalakan($trip);

        $this->assertNull($this->jadwalIzin($trip));
        $this->get(route('trips.show', $trip))->assertInertia(fn (AssertableInertia $page) => $page
            ->has('pengingat.daftar', 2)
            ->where('pengingat.catatan', null));
    }

    public function test_moving_the_date_moves_the_opening_reminder_and_turning_reminders_off_cancels_it(): void
    {
        $trip = $this->trip();
        $this->nyalakan($trip);

        $this->actingAs($trip->user)->put(route('trips.update', $trip), [
            'name' => $trip->name,
            'planned_date' => '2026-11-27',
            'trip_type' => $trip->trip_type->value,
        ])->assertSessionHasNoErrors();

        $this->assertSame('2026-10-27 23:00:00', $this->jadwalIzin($trip));

        $this->nyalakan($trip, false);

        $this->assertNull($this->jadwalIzin($trip));
    }

    public function test_the_opening_reminder_sends_its_own_email_once(): void
    {
        Notification::fake();
        $trip = $this->trip();
        $this->nyalakan($trip);

        $this->travelTo(Carbon::parse('2026-10-20 23:00:00', 'UTC'));
        $this->kirim();
        $this->kirim();

        Notification::assertSentToTimes($trip->user, PengingatIzinDibuka::class, 1);
        Notification::assertNotSentTo($trip->user, TripReminderNotification::class);
        $this->assertNotNull($trip->reminders()->where('jenis', 'IZIN')->value('sent_at'));
    }

    /**
     * Izin yang sudah ditandai dikonfirmasi di daftar persiapan tidak diingatkan lagi; baris
     * pengingatnya dicatat dilewati, bukan dibiarkan menunggu selamanya.
     */
    public function test_a_permit_already_booked_is_not_reminded(): void
    {
        Notification::fake();
        $trip = $this->trip();
        $this->nyalakan($trip);
        TripPreparationItem::create([
            'trip_plan_id' => $trip->id,
            'category' => PreparationCategory::LOGISTICS->value,
            'label' => PreparationService::PERMIT_LABEL,
            'is_critical' => true,
            'status' => PreparationStatus::CONFIRMED->value,
        ]);

        $this->travelTo(Carbon::parse('2026-10-20 23:00:00', 'UTC'));
        $this->kirim();

        Notification::assertNothingSent();
        $this->assertNotNull($trip->reminders()->where('jenis', 'IZIN')->value('skipped_at'));
    }

    /**
     * Surelnya menyebut pengelola dan menautkan sistem resmi; Memount tidak menjual izin dan
     * tidak menyebut kuota tersisa (§43). Nama jalur dari basis data di-escape.
     */
    public function test_the_email_links_the_official_system_and_never_sells_the_permit(): void
    {
        $trip = $this->trip();
        $trip->trail->update(['name' => 'Rinjani <script>alert(1)</script>']);
        $aturan = PermitRequirement::query()->sole();

        $surel = (new PengingatIzinDibuka($trip->fresh(), $aturan))->toMail($trip->user);
        $isi = $surel->render();

        $this->assertSame('Pemesanan izin Rinjani <script>alert(1)</script> dibuka hari ini', $surel->subject);
        $this->assertStringContainsString('Balai Taman Nasional Gunung Rinjani', $isi);
        $this->assertStringContainsString('https://contoh.test/erinjani', $isi);
        $this->assertStringContainsString('Memount menautkan sistem resmi dan tidak menjual izin.', $isi);
        $this->assertStringContainsString('Jumat, 20 November 2026 (WITA)', $isi);
        $this->assertStringContainsString('&lt;script&gt;', $isi);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $isi);
        $this->assertDoesNotMatchRegularExpression('/\baman\b|sisa kuota/i', strip_tags($isi));

        $tanpaTautan = (new PengingatIzinDibuka($trip->fresh(), null))->toMail($trip->user)->render();

        $this->assertStringContainsString(route('trips.show', $trip), $tanpaTautan);
    }

    public function test_the_trip_page_lists_the_opening_reminder_with_its_zone(): void
    {
        $trip = $this->trip();
        $this->nyalakan($trip);

        $this->get(route('trips.show', $trip))->assertInertia(fn (AssertableInertia $page) => $page
            ->has('pengingat.daftar', 3)
            ->where('pengingat.daftar.0.label', 'Saat pemesanan izin dibuka')
            ->where('pengingat.daftar.0.waktu', 'Rabu, 21 Okt 2026 07:00 WITA')
            ->where('pengingat.daftar.0.keadaan', 'menunggu')
            ->where('pengingat.jadwal', 'Pengingat dikirim 3 hari dan sehari sebelum tanggal mulai, pukul 07:00 waktu setempat gunungnya (WIB, WITA, atau WIT). Bila aturan izin jalurnya mencatat kapan pemesanan dibuka, satu pengingat juga dikirim pada hari itu.'));
    }
}
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='PermitWindowReminder|TripReminder|ReminderSheet|TripWorkspace|TripLifecycle|TripPlanDetail|PreparationService|Scheduler'`
Expected: FAIL. Pada simulasi rencana: 114 test, 108 lulus, 5 gagal, 1 error. Yang merah: enam dari delapan test `PermitWindowReminderTest` (jadwal `IZIN` dan `PengingatIzinDibuka` belum ada; daftar pengingat di halaman trip masih dua baris). Dua test sudah lulus sebelum implementasi karena menjaga ketiadaan: `test_no_opening_reminder_without_a_recorded_opening_day` dan `test_an_opening_day_already_past_is_neither_scheduled_nor_reported_as_missed`.

- [ ] **Step 3: Tulis implementasinya.** Notifikasi dibuat dengan
`DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:notification --no-interaction PengingatIzinDibuka`
lalu isinya diganti persis.

Buat `app/Notifications/PengingatIzinDibuka.php`:

```php
<?php

namespace App\Notifications;

use App\Models\PermitRequirement;
use App\Models\TripPlan;
use App\Support\Timezone;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Surel hari pemesanan izin dibuka (FR-24 diperluas, GR-06; aksi Beranda "Ingatkan saat
 * dibuka", spec v3 bagian 6.1).
 *
 * Isinya hanya yang dibutuhkan untuk memesan: jalur, tanggal rencana dengan penanda zona
 * gunungnya, pengelola, dan tautan sistem resmi bila tercatat. Memount menautkan sistem
 * resmi dan tidak menjual izin, dan kuota tersisa hanya ada di sistem resmi (§43), jadi surel
 * ini tidak menyebut kuota. Catatan, peserta, nama, dan email pemilik tidak ikut, sama dengan
 * TripReminderNotification.
 *
 * Tidak memakai antrean: dikirim dari perintah terjadwal yang sudah berjalan di latar, dan
 * hasil kirimnya menentukan apakah pengingat ditandai terkirim.
 */
class PengingatIzinDibuka extends Notification
{
    use Queueable;

    public function __construct(
        public readonly TripPlan $trip,
        public readonly ?PermitRequirement $aturan,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $jalur = $this->trip->trail;
        $zona = Timezone::label(Timezone::forTrail($jalur));
        $tanggal = $this->trip->planned_date->translatedFormat('l, d F Y');
        $tempat = $this->aturan?->authority ? ' di sistem resmi '.$this->aturan->authority : '';

        $surel = (new MailMessage)
            ->subject(sprintf('Pemesanan izin %s dibuka hari ini', $jalur->name))
            ->greeting('Halo,')
            ->line(sprintf('Pemesanan izin pendakian untuk rencana Anda di %s, %s, tanggal %s (%s), dibuka hari ini%s.', $jalur->name, $jalur->mountain->name, $tanggal, $zona, $tempat))
            ->line('Memount menautkan sistem resmi dan tidak menjual izin. Kuota dan ketentuan terbaru hanya ada di sistem resmi.');

        $surel = $this->aturan?->booking_url
            ? $surel->action('Buka sistem resmi', $this->aturan->booking_url)
            : $surel->action('Buka rencana trip', route('trips.show', $this->trip));

        return $surel
            ->line('Anda menerima surel ini karena menyalakan pengingat untuk rencana ini. Pengingat dapat dimatikan di halaman trip.')
            ->salutation('Salam, '.config('app.name'));
    }
}
```

`app/Services/PreparationService.php`:

1. Ganti:

```php
    public function __construct(private readonly PermitService $permits) {}

    /**
```

dengan:

```php
    public function __construct(private readonly PermitService $permits) {}

    /**
     * Butir izin di daftar persiapan trip sudah dikonfirmasi atau ditandai tidak berlaku.
     * Butir itu dikenali lewat PERMIT_LABEL sampai kunci stabil butir sistem tersedia
     * (titipan W8, ruling W4-C7b). Membutuhkan relasi preparationItems sudah dimuat.
     */
    public static function izinDiurus(TripPlan $trip): bool
    {
        return $trip->preparationItems->contains(fn (TripPreparationItem $butir) => $butir->preparation_item_id === null
            && $butir->label === self::PERMIT_LABEL
            && $butir->status !== PreparationStatus::NOT_CONFIRMED);
    }

    /**
```

Tulis ulang `app/Services/Trips/TripReminderScheduler.php` utuh:

```php
<?php

namespace App\Services\Trips;

use App\Enums\TripStatus;
use App\Models\PermitRequirement;
use App\Models\TripPlan;
use App\Models\TripReminder;
use App\Notifications\PengingatIzinDibuka;
use App\Notifications\TripReminderNotification;
use App\Services\PermitService;
use App\Services\PreparationService;
use App\Support\Timezone;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Pengingat perjalanan H-3 dan H-1 (FR-24, features/04), ditambah hari pemesanan izin dibuka
 * (spec v3 bagian 6.1, aksi Beranda "Ingatkan saat dibuka", GR-06) bila aturan izin jalurnya
 * mencatat kapan pemesanan dibuka. Ketiganya ikut satu sakelar pengingat trip.
 *
 * Dua tugas. sinkronkan() menyelaraskan baris trip_reminders dengan keadaan rencana,
 * dipanggil setiap kali rencana dibuat, diubah, dimulai, diselesaikan, dibatalkan,
 * atau sakelarnya diubah. kirimYangJatuhTempo() mengirim yang sudah tiba waktunya,
 * dipanggil perintah terjadwal trips:kirim-pengingat setiap jam.
 *
 * Jaminan tidak terkirim dua kali ada di klaim atomik: baris diubah menjadi terkirim
 * dengan UPDATE bersyarat "belum terkirim dan belum dilewati" sebelum surel dikirim,
 * sehingga dua proses yang berjalan bersamaan tidak pernah sama-sama memegang baris
 * yang sama. Gagal kirim melepas klaimnya supaya jalan berikutnya mencoba lagi.
 *
 * Surel dikirim sinkron, bukan lewat antrean: perintah terjadwal ini sudah proses
 * latar, dan tanpa pekerja antrean yang berjalan, surel yang diantrekan akan tercatat
 * terkirim padahal tidak pernah berangkat.
 */
class TripReminderScheduler
{
    /**
     * Rencana yang masih menunggu keberangkatan. Trip yang sudah dimulai, selesai,
     * atau dibatalkan tidak diingatkan lagi.
     *
     * @var array<int, TripStatus>
     */
    private const STATUS_LAYAK = [TripStatus::PLANNED, TripStatus::READY_FOR_DEPARTURE];

    /** Trip per putaran pengiriman, supaya memori tidak tumbuh bersama antrean. */
    private const PER_PUTARAN = 100;

    private const FORMAT_WAKTU = 'l, d M Y H:i';

    /** Jenis pengingat hari pemesanan izin dibuka; muat di kolom jenis (8 karakter). */
    public const JENIS_IZIN = 'IZIN';

    public function __construct(private readonly PermitService $izin) {}

    /**
     * Instan UTC tiap jenis pengingat: pukul local_hour di zona gunung, sekian hari
     * sebelum tanggal mulai. Hari pemesanan izin dibuka ikut bila aturan izin jalurnya
     * mencatat kapan pemesanan dibuka; tanpa catatan itu tidak ada tanggal yang diingatkan,
     * dan tanggalnya tidak ditebak (R-007). Membutuhkan relasi trail sudah dimuat.
     *
     * @return array<string, CarbonImmutable> jenis => instan UTC
     */
    public function jadwal(TripPlan $trip): array
    {
        $zona = Timezone::forTrail($trip->trail);
        $jam = (int) config('hiking.reminders.local_hour');
        $hasil = [];

        foreach ($this->hariSebelum() as $hari) {
            $hasil[self::jenis($hari)] = CarbonImmutable::parse($trip->planned_date->toDateString(), $zona)
                ->subDays($hari)
                ->setTime($jam, 0)
                ->utc();
        }

        $buka = $this->bukaIzin($trip);

        if ($buka !== null) {
            $hasil[self::JENIS_IZIN] = $buka;
        }

        return $hasil;
    }

    /**
     * Instan UTC pengingat hari pemesanan izin dibuka, atau null bila aturan izin jalurnya
     * tidak mencatat kapan pemesanan dibuka. $aturan false berarti belum dicari dan null
     * berarti sudah dicari dan memang tidak ada, pola yang sama dengan
     * PermitService::bookingWindowFor(), supaya pemanggil yang sudah memegang aturannya
     * (Beranda) tidak mengueri ulang.
     */
    public function bukaIzin(TripPlan $trip, PermitRequirement|false|null $aturan = false): ?CarbonImmutable
    {
        $aturan = $aturan === false ? $this->izin->requirementFor($trip->trail) : $aturan;
        $hari = $aturan?->booking_opens_days_before;

        if ($hari === null) {
            return null;
        }

        return CarbonImmutable::parse($trip->planned_date->toDateString(), Timezone::forTrail($trip->trail))
            ->subDays($hari)
            ->setTime((int) config('hiking.reminders.local_hour'), 0)
            ->utc();
    }

    public function dapatDiatur(TripPlan $trip): bool
    {
        return in_array($trip->status, self::STATUS_LAYAK, true);
    }

    /**
     * Pengingat yang masih menunggu mengikuti rencana. Yang sudah terkirim atau
     * dilewati untuk jadwal yang sama dibiarkan, sehingga mematikan lalu menyalakan
     * kembali tidak pernah mengirim ulang. Jadwal yang bergeser (tanggal diubah)
     * menyebut tanggal lain, jadi pengingatnya dijadwalkan ulang dari awal.
     */
    public function sinkronkan(TripPlan $trip, ?CarbonInterface $sekarang = null): void
    {
        $sekarang ??= Carbon::now();
        $trip->loadMissing('trail.mountain');
        $trip->load('reminders');

        $aktif = $trip->reminders_enabled && $this->dapatDiatur($trip);
        $diinginkan = $this->jadwal($trip);
        $ada = $trip->reminders->keyBy('jenis');

        DB::transaction(function () use ($trip, $sekarang, $aktif, $diinginkan, $ada): void {
            foreach ($ada as $jenis => $pengingat) {
                if ($pengingat->keadaan() === TripReminder::MENUNGGU && (! $aktif || ! isset($diinginkan[$jenis]))) {
                    $pengingat->delete();
                }
            }

            if (! $aktif) {
                return;
            }

            foreach ($diinginkan as $jenis => $waktu) {
                $pengingat = $ada->get($jenis);
                $masihAkanDatang = $waktu->greaterThan($sekarang);

                if ($pengingat === null) {
                    if ($masihAkanDatang) {
                        TripReminder::createOrFirst(
                            ['trip_plan_id' => $trip->id, 'jenis' => $jenis],
                            ['dijadwalkan_untuk' => $waktu],
                        );
                    }

                    continue;
                }

                if ($pengingat->dijadwalkan_untuk->equalTo($waktu)) {
                    continue;
                }

                if ($masihAkanDatang) {
                    $pengingat->update(['dijadwalkan_untuk' => $waktu, 'sent_at' => null, 'skipped_at' => null]);
                } else {
                    $pengingat->delete();
                }
            }
        });

        $trip->unsetRelation('reminders');
    }

    /**
     * Mengirim pengingat yang sudah jatuh tempo, satu surel per trip per jalan.
     *
     * Bila beberapa pengingat satu trip jatuh tempo bersamaan (scheduler sempat mati),
     * hanya yang paling dekat dengan keberangkatan yang dikirim; sisanya dicatat
     * dilewati. Pada hari keberangkatan tidak ada pengingat yang dikirim lagi.
     *
     * @return array{terkirim: int, dilewati: int, gagal: int}
     */
    public function kirimYangJatuhTempo(CarbonInterface $sekarang): array
    {
        $hasil = ['terkirim' => 0, 'dilewati' => 0, 'gagal' => 0];
        $tripGagal = [];

        do {
            // Baris yang sudah diproses keluar dari himpunan jatuh tempo, jadi setiap
            // putaran cukup mengambil trip pertama yang tersisa. Trip yang gagal pada
            // jalan ini dikecualikan supaya putarannya berhenti.
            $tripIds = TripReminder::query()
                ->menunggu()
                ->where('dijadwalkan_untuk', '<=', $sekarang)
                ->whereNotIn('trip_plan_id', $tripGagal)
                ->distinct()
                ->orderBy('trip_plan_id')
                ->limit(self::PER_PUTARAN)
                ->pluck('trip_plan_id');

            $perTrip = TripReminder::query()
                ->menunggu()
                ->where('dijadwalkan_untuk', '<=', $sekarang)
                ->whereIn('trip_plan_id', $tripIds)
                ->with('tripPlan.trail.mountain', 'tripPlan.user', 'tripPlan.preparationItems')
                ->get()
                ->groupBy('trip_plan_id');

            foreach ($perTrip as $tripId => $pengingat) {
                if (! $this->prosesTrip($pengingat, $sekarang, $hasil)) {
                    $tripGagal[] = $tripId;
                }
            }
        } while ($tripIds->isNotEmpty());

        return $hasil;
    }

    /**
     * Isi untuk halaman trip: sakelar, jadwal yang berlaku, dan tiap pengingat beserta
     * keadaannya. Membutuhkan relasi reminders dan trail.mountain sudah dimuat.
     *
     * @return array{aktif: bool, dapatDiatur: bool, jadwal: string, daftar: array<int, array{jenis: string, label: string, waktu: string, keadaan: string, keadaanTeks: string}>, catatan: string|null}
     */
    public function untukHalaman(TripPlan $trip): array
    {
        $zona = Timezone::forTrail($trip->trail);
        $ada = $trip->reminders->sortBy('dijadwalkan_untuk')->values();

        $daftar = $ada->map(fn (TripReminder $p) => [
            'jenis' => $p->jenis,
            'label' => self::label($p->jenis),
            'waktu' => (string) Timezone::display($p->dijadwalkan_untuk, $zona, self::FORMAT_WAKTU),
            'keadaan' => $p->keadaan(),
            'keadaanTeks' => match ($p->keadaan()) {
                TripReminder::TERKIRIM => 'Terkirim '.Timezone::display($p->sent_at, $zona, self::FORMAT_WAKTU),
                TripReminder::DILEWATI => 'Tidak dikirim: waktunya terlewat',
                default => 'Menunggu',
            },
        ])->all();

        // Hari pemesanan izin yang sudah lewat tidak disebut terlewat: pemesanannya sudah
        // dibuka, dan yang perlu dilakukan sekarang memesan, bukan menunggu surel.
        $terlewat = $trip->reminders_enabled && $this->dapatDiatur($trip)
            ? array_values(array_diff(array_keys($this->jadwal($trip)), $ada->pluck('jenis')->all(), [self::JENIS_IZIN]))
            : [];

        return [
            'aktif' => (bool) $trip->reminders_enabled,
            'dapatDiatur' => $this->dapatDiatur($trip),
            'jadwal' => $this->keteranganJadwal(),
            'daftar' => $daftar,
            'catatan' => $terlewat === [] ? null : sprintf(
                'Pengingat %s tidak dijadwalkan karena waktunya sudah lewat.',
                implode(' dan ', array_map(fn (string $jenis) => mb_strtolower(self::label($jenis)), $terlewat)),
            ),
        ];
    }

    /**
     * Kalimat jadwal dari config, supaya formulir tidak menyebut jam yang berbeda dari
     * yang dijalankan.
     */
    public function keteranganJadwal(): string
    {
        $hari = array_map(fn (int $h) => $h === 1 ? 'sehari' : $h.' hari', $this->hariSebelum());

        return sprintf(
            'Pengingat dikirim %s sebelum tanggal mulai, pukul %02d:00 waktu setempat gunungnya (WIB, WITA, atau WIT). Bila aturan izin jalurnya mencatat kapan pemesanan dibuka, satu pengingat juga dikirim pada hari itu.',
            implode(' dan ', $hari),
            (int) config('hiking.reminders.local_hour'),
        );
    }

    public static function label(string $jenis): string
    {
        if ($jenis === self::JENIS_IZIN) {
            return 'Saat pemesanan izin dibuka';
        }

        $hari = (int) substr($jenis, 2);

        return $hari === 1 ? 'Sehari sebelum berangkat' : $hari.' hari sebelum berangkat';
    }

    /**
     * @param  Collection<int, TripReminder>  $pengingat  pengingat jatuh tempo milik satu trip
     * @param  array{terkirim: int, dilewati: int, gagal: int}  $hasil
     * @return bool false bila pengiriman gagal
     */
    private function prosesTrip(Collection $pengingat, CarbonInterface $sekarang, array &$hasil): bool
    {
        $trip = $pengingat->first()?->tripPlan;

        if ($trip === null) {
            return true;
        }

        $zona = Timezone::forTrail($trip->trail);
        $hariIni = CarbonImmutable::instance($sekarang)->setTimezone($zona)->toDateString();
        $keberangkatan = $trip->planned_date->toDateString();

        // Pemeriksaan ulang saat kirim, tidak hanya saat menjadwalkan: jalur mana pun
        // yang lupa menyelaraskan tetap tidak menghasilkan surel yang salah. Izin yang sudah
        // dikonfirmasi di daftar persiapan tidak diingatkan lagi.
        $terdekat = $pengingat->sortBy('dijadwalkan_untuk')->last();
        $layak = $trip->reminders_enabled && $this->dapatDiatur($trip) && $hariIni < $keberangkatan
            && ! ($terdekat->jenis === self::JENIS_IZIN && PreparationService::izinDiurus($trip));

        foreach ($pengingat as $satu) {
            if ((! $layak || $satu->isNot($terdekat)) && $this->lewati($satu, $sekarang)) {
                $hasil['dilewati']++;
            }
        }

        if (! $layak || ! $this->klaim($terdekat, $sekarang)) {
            return true;
        }

        $sisaHari = (int) CarbonImmutable::parse($hariIni)->diffInDays(CarbonImmutable::parse($keberangkatan));

        try {
            $trip->user->notify($terdekat->jenis === self::JENIS_IZIN
                ? new PengingatIzinDibuka($trip, $this->izin->requirementFor($trip->trail))
                : new TripReminderNotification($trip, $sisaHari));
        } catch (Throwable $e) {
            TripReminder::query()->whereKey($terdekat->id)->update(['sent_at' => null]);
            report($e);
            $hasil['gagal']++;

            return false;
        }

        $hasil['terkirim']++;

        return true;
    }

    private function klaim(TripReminder $pengingat, CarbonInterface $sekarang): bool
    {
        return TripReminder::query()->whereKey($pengingat->id)->menunggu()->update(['sent_at' => $sekarang]) === 1;
    }

    private function lewati(TripReminder $pengingat, CarbonInterface $sekarang): bool
    {
        return TripReminder::query()->whereKey($pengingat->id)->menunggu()->update(['skipped_at' => $sekarang]) === 1;
    }

    /**
     * @return array<int, int> hari sebelum keberangkatan, dari yang terjauh
     */
    private function hariSebelum(): array
    {
        $hari = array_filter(
            array_map(intval(...), (array) config('hiking.reminders.days_before')),
            fn (int $h) => $h > 0,
        );

        rsort($hari);

        return array_values(array_unique($hari));
    }

    private static function jenis(int $hari): string
    {
        return 'H-'.$hari;
    }
}
```

- [ ] **Step 4: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Notifications/PengingatIzinDibuka.php app/Services/PreparationService.php app/Services/Trips/TripReminderScheduler.php tests/Feature/PermitWindowReminderTest.php`, lalu `php artisan test --compact --filter='PermitWindowReminder|TripReminder|ReminderSheet|TripWorkspace|TripLifecycle|TripPlanDetail|PreparationService|Scheduler'`
Expected: PASS. Pada simulasi rencana: 114 test lulus.

- [ ] **Step 5: Suite, tipe, dan format.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 1.929 test, 1.927 lulus, 2 dilewati (dasar 1.921).

Run: `npx tsc --noEmit`
Expected: keluar tanpa keluaran galat (tugas ini tidak menyentuh TypeScript).

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Pratinjau tidak dijalankan: tidak ada perubahan layar.

- [ ] **Step 6: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 2: Fase Beranda B0 sampai B4 dan kartu perjalanan

Spec bagian 6.1 (tabel fase, aksi B2, sapaan), DK-45, DK-31, DK-37; induk W5 butir 1 dan 5; ruling W5-R3 sampai
W5-R12, W5-R20, W5-R21. `RingkasanDasbor` memilih fase B3, B2, B4, B0, atau B1 dan menyusun satu kartu dengan tepat
satu aksi utama; `KonteksBeranda` menyimpan hasilnya untuk bagian Beranda lain di tugas berikutnya. Halaman React
ditulis ulang: sapaan dari jam perangkat, ringkasan satu kalimat dari server, kartu fase, kalimat rencana yang
tanggalnya lewat, dan kalimat dukungan keputusan yang lama. Judul dokumen menjadi "Beranda".

**Files:**
- Create: `app/Services/Dashboard/KonteksBeranda.php`, `resources/js/components/beranda/AksiUtama.tsx`, `resources/js/components/beranda/KartuFase.tsx`, `resources/js/components/beranda/KartuPerjalanan.tsx`, `resources/js/components/beranda/sapaan.ts`, `resources/js/components/beranda/types.ts`
- Modify: `app/Http/Controllers/DashboardController.php`, `app/Services/Dashboard/RingkasanDasbor.php` (ditulis ulang utuh), `config/hiking.php`, `resources/js/pages/Dashboard.tsx` (ditulis ulang utuh)
- Test: `tests/Feature/DashboardPhaseTest.php` (baru), `tests/Feature/DashboardRelevanceTest.php` (ditulis ulang utuh, niat setiap test tetap), `tests/Feature/IndonesianInterfaceTest.php`, `tests/Feature/StaleReadinessTest.php`, `tests/Pratinjau/HalamanPratinjauTest.php`

**Interfaces:**
- Consumes: `PendakianAktif::untuk(User): ?array{id, jalur, posBerikutnya, mulai, url}`; `KepalaTrip::untuk(TripPlan): array` (kunci `jalur`, `gunung`, `jenis`, `hari`, `tanggal`, `jam`, `peserta`), `KepalaTrip::hariMenuju(CarbonInterface, string): ?string`, `KepalaTrip::rentang(CarbonInterface, ?CarbonInterface): string`; `TripSteps::for(TripPlan, ?OfficialStatusValue): array` (kunci `kunci`, `judul`, `keadaan`, `keterangan`, `peringatan`); `OfficialStatusService::effectiveStatusesForTrails(EloquentCollection): array`; `PermitService::requirementFor()`, `bookingWindowFor()`, `BOOKING_TERBUKA`, `BOOKING_BELUM_DIBUKA`; `TripReminderScheduler::bukaIzin()`, `dapatDiatur()` dan `PreparationService::izinDiurus()` (Tugas 1); `PreparationService::kritis()`; `SkalaProfil::naikBersama()`; `Durasi::pendek()`; `Timezone::earliestDateInIndonesia()`, `latestDateInIndonesia()`, `forTrail()`; komponen `PageHeader` (`akar`, `merek`, `aksi`), `TombolPengingat`, `Button` (`href`, `external`, `processing`), `Lencana`, `Tautan`, `Icon`, `GarisProfil`, `AngkaRingkas`; rute `track`, `trips.readiness`, `trips.preparation`, `trips.show`, `trips.reminders.update`, `history.summary`, `reports.create`, `onboarding`, `trips.create`, `trails.index`.
- Produces:
  - `App\Services\Dashboard\RingkasanDasbor` dengan konstanta `B0` sampai `B4`, `konteks(User $user): KonteksBeranda` (dihitung sekali per permintaan) dan `props(User $user): array{kunci: string, ringkasan: string, kartu: array, tripLewat: ?array{nama, tanggal, url}}`;
  - `App\Services\Dashboard\KonteksBeranda` (readonly): `fase`, `pengguna`, `profilLengkap`, `pendakian`, `trip` (B2, dengan `trail.mountain`, `preparationItems`, `latestReadinessCheck`), `aturanIzin`, `riwayat` (B4, dengan `tripPlan.trail.mountain`), `tripLewat`;
  - props `Dashboard`: `title` "Beranda", `fase` (closure);
  - config `hiking.beranda.hari_ajakan_lapor` = 7;
  - TypeScript `KunciFase`, `AksiFase`, `KartuPerjalananData`, `KartuFaseData`, `FaseBeranda`; komponen `KartuFase({ kartu })`, `KartuPerjalanan({ kartu })`, `AksiUtama({ aksi })`; fungsi `sapaan(sekarang: Date): string`.

- [ ] **Step 1: Tulis test yang gagal.** Buat berkas test baru dengan `make:test`, lalu ganti isinya persis. Test
lama yang diperbarui membawa alasannya di docblock atau komentarnya: `DashboardRelevanceTest` menjaga niat yang sama
pada bentuk props baru (`fase.kunci`, `fase.kartu`), dan `StaleReadinessTest` membaca peringatan vonis basi dari props.

Buat `tests/Feature/DashboardPhaseTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\CompletionState;
use App\Enums\ExperienceLevel;
use App\Enums\HikingSessionStatus;
use App\Enums\PreparationCategory;
use App\Enums\PreparationStatus;
use App\Enums\StatusScope;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Models\Checkpoint;
use App\Models\HikingHistory;
use App\Models\HikingSession;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\PermitRequirement;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Models\User;
use App\Services\PreparationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Beranda berfase B0 sampai B4 (spec v3 bagian 6.1, DK-45, rencana induk W5 butir 1).
 *
 * Fase dihitung server dari profil, trip, dan riwayat, dan setiap fase membawa tepat satu aksi
 * utama yang dipilih server. Waktu dibekukan pada Jumat 25 September 2026 pukul 10.00 WIB.
 */
class DashboardPhaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-09-25 03:00:00', 'UTC'));
    }

    private function pendaki(bool $profil = true): User
    {
        $user = User::factory()->create();

        if ($profil) {
            $user->profile()->create(['experience_level' => ExperienceLevel::INTERMEDIATE->value, 'completed_at' => now()]);
        }

        return $user->fresh();
    }

    /**
     * @param  array<string, mixed>  $atribut
     */
    private function trip(User $user, array $atribut = [], string $zona = 'Asia/Jakarta'): TripPlan
    {
        $gunung = Mountain::factory()->create(['name' => 'Gunung Gede', 'timezone' => $zona]);
        $jalur = Trail::factory()->for($gunung)->published()->create(['name' => 'Gede via Cibodas']);

        return TripPlan::factory()->for($user)->for($jalur)->create(array_merge([
            'name' => 'Gede bareng Dimas',
            'planned_date' => '2026-10-10',
            'end_date' => '2026-10-11',
            'start_time' => '07:00',
            'participants_count' => 3,
            'trip_type' => TripType::CAMPING->value,
            'status' => TripStatus::PLANNED->value,
        ], $atribut));
    }

    private function butir(TripPlan $trip, string $label, PreparationStatus $status, bool $kritis = true): TripPreparationItem
    {
        return TripPreparationItem::create([
            'trip_plan_id' => $trip->id,
            'category' => PreparationCategory::EQUIPMENT->value,
            'label' => $label,
            'is_critical' => $kritis,
            'status' => $status->value,
        ]);
    }

    private function izin(TripPlan $trip, array $atribut = []): PermitRequirement
    {
        return PermitRequirement::factory()->create(array_merge([
            'mountain_id' => $trip->trail->mountain_id,
            'authority' => 'Balai Besar TNGGP',
            'booking_url' => 'https://contoh.test/pemesanan',
            'booking_opens_days_before' => 30,
            'booking_closes_days_before' => 3,
        ], $atribut));
    }

    private function selesai(User $user, TripPlan $trip, Carbon $kapan): HikingHistory
    {
        $trip->update(['status' => TripStatus::COMPLETED->value, 'completed_at' => $kapan]);

        return HikingHistory::create([
            'user_id' => $user->id,
            'trip_plan_id' => $trip->id,
            'trail_id' => $trip->trail_id,
            'trip_type' => $trip->trip_type->value,
            'completion_state' => CompletionState::COMPLETED->value,
            'completed_at' => $kapan,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function fase(User $user): array
    {
        return $this->actingAs($user)->get(route('dashboard'))->assertOk()->viewData('page')['props']['fase'];
    }

    /**
     * Kartu perjalanan berikutnya (prototype 01): jenis, H-n, tanggal dengan kata "sampai",
     * jam dalam zona gunungnya, peserta, empat langkah, dan hitungan butir kritis dengan nama
     * yang belum dikonfirmasi. Ringkasan di bawah sapaan menyebut sisa hari dan butirnya.
     */
    public function test_the_next_trip_fills_b2_with_its_plan_steps_and_critical_count(): void
    {
        $user = $this->pendaki();
        $trip = $this->trip($user);
        $this->butir($trip, 'P3K', PreparationStatus::CONFIRMED);
        $this->butir($trip, 'Senter', PreparationStatus::CONFIRMED);
        $this->butir($trip, 'Jaket hujan', PreparationStatus::NOT_CONFIRMED);
        $this->butir($trip, 'Tenda', PreparationStatus::NOT_CONFIRMED);
        $this->butir($trip, 'Peta jalur', PreparationStatus::NOT_CONFIRMED);
        $this->butir($trip, 'Kopi', PreparationStatus::NOT_CONFIRMED, kritis: false);

        $fase = $this->fase($user);
        $kartu = $fase['kartu'];

        $this->assertSame('B2', $fase['kunci']);
        $this->assertSame('Gede bareng Dimas tinggal 15 hari lagi. 3 butir kritis belum dikonfirmasi.', $fase['ringkasan']);
        $this->assertSame('perjalanan', $kartu['jenis']);
        $this->assertSame(['Camping', 'H-15', 'Sab, 10 Okt sampai Min, 11 Okt 2026', '07.00 WIB', '3 orang'], [$kartu['jenisTrip'], $kartu['hari'], $kartu['tanggal'], $kartu['jam'], $kartu['peserta']]);
        $this->assertSame(['jalur', 'rencana', 'persiapan', 'kesiapan'], array_column($kartu['langkah'], 'kunci'));
        $this->assertSame(['dikonfirmasi' => 2, 'berlaku' => 5, 'belum' => ['Jaket hujan', 'Tenda', 'Peta jalur'], 'sisa' => 0], $kartu['persiapan']);
        $this->assertSame(route('trips.show', $trip), $kartu['url']);
    }

    public function test_a_hike_in_progress_is_b3_and_names_the_next_checkpoint(): void
    {
        $user = $this->pendaki();
        $trip = $this->trip($user, ['status' => TripStatus::IN_PROGRESS->value, 'planned_date' => '2026-09-25']);
        $pos = Checkpoint::factory()->for($trip->trail)->create(['sequence' => 2, 'name' => 'Kandang Badak']);
        HikingSession::create([
            'trip_plan_id' => $trip->id,
            'user_id' => $user->id,
            'current_checkpoint_id' => $pos->id,
            'status' => HikingSessionStatus::ACTIVE->value,
            'started_at' => now()->subHours(2),
        ]);

        $fase = $this->fase($user);

        $this->assertSame('B3', $fase['kunci']);
        $this->assertSame('Anda sedang mendaki Gede via Cibodas.', $fase['ringkasan']);
        $this->assertSame('Kandang Badak', $fase['kartu']['posBerikutnya']);
        $this->assertSame(['jenis' => 'tautan', 'label' => 'Buka mode lapangan', 'href' => '/trips/'.$trip->id.'/hike', 'keterangan' => null], $fase['kartu']['aksi']);
    }

    /**
     * Pendakian yang selesai pekan ini dan belum dilaporkan mengajak pendakinya melapor (B4).
     * Tautannya membawa jalur dan trip, sehingga laporannya ditautkan ke riwayat ini.
     */
    public function test_a_hike_finished_this_week_and_not_reported_is_b4(): void
    {
        $user = $this->pendaki();
        $trip = $this->trip($user, ['planned_date' => '2026-09-22']);
        $this->selesai($user, $trip, now()->subDays(2));

        $fase = $this->fase($user);

        $this->assertSame('B4', $fase['kunci']);
        $this->assertSame('Pendakian Gede bareng Dimas sudah selesai.', $fase['ringkasan']);
        $this->assertSame('Selesai sampai target', $fase['kartu']['hasil']);
        $this->assertSame('Rab, 23 Sep 2026', $fase['kartu']['selesai']);
        $this->assertSame(route('history.summary', $trip), $fase['kartu']['ringkasanUrl']);
        $this->assertSame('Bagikan laporan kondisi', $fase['kartu']['aksi']['label']);
        $this->assertSame(route('reports.create', ['trail' => $trip->trail_id, 'trip' => $trip->id]), $fase['kartu']['aksi']['href']);
    }

    /**
     * Laporan yang sudah dikirim untuk jalur itu sesudah pendakian selesai menutup B4, juga
     * laporan yang tidak ditautkan ke riwayatnya; B4 berakhir sesudah tujuh hari.
     */
    public function test_a_report_already_sent_or_an_older_hike_does_not_ask_again(): void
    {
        $user = $this->pendaki();
        $trip = $this->trip($user, ['planned_date' => '2026-09-22']);
        $this->selesai($user, $trip, now()->subDays(2));
        TrailConditionReport::factory()->for($trip->trail)->create(['user_id' => $user->id, 'created_at' => now()->subDay()]);

        $this->assertSame('B1', $this->fase($user)['kunci']);

        $lama = $this->pendaki();
        $tripLama = $this->trip($lama, ['planned_date' => '2026-09-15']);
        $this->selesai($lama, $tripLama, now()->subDays(8));

        $this->assertSame('B1', $this->fase($lama)['kunci']);
    }

    /**
     * Urutan kepentingan: pendakian berlangsung, trip mendatang, laporan yang ditunggu, lalu
     * profil. Pendaki tanpa profil yang baru turun gunung tetap diajak melapor lebih dulu.
     */
    public function test_the_phases_follow_their_order_of_importance(): void
    {
        $user = $this->pendaki(profil: false);
        $selesai = $this->trip($user, ['planned_date' => '2026-09-22']);
        $this->selesai($user, $selesai, now()->subDay());

        $this->assertSame('B4', $this->fase($user)['kunci']);

        $this->trip($user);

        $this->assertSame('B2', $this->fase($user)['kunci']);

        $this->trip($user, ['status' => TripStatus::IN_PROGRESS->value, 'planned_date' => '2026-09-25']);

        $this->assertSame('B3', $this->fase($user)['kunci']);
    }

    public function test_on_the_day_the_one_action_is_to_start_from_the_hike_tab(): void
    {
        $user = $this->pendaki();
        $this->trip($user, ['planned_date' => '2026-09-25', 'end_date' => null]);

        $fase = $this->fase($user);

        $this->assertSame('Gede bareng Dimas berangkat hari ini.', $fase['ringkasan']);
        $this->assertSame('Hari H', $fase['kartu']['hari']);
        $this->assertSame(['Mulai pendakian', route('track')], [$fase['kartu']['aksi']['label'], $fase['kartu']['aksi']['href']]);
    }

    public function test_from_three_days_before_the_one_action_is_the_readiness_check(): void
    {
        $user = $this->pendaki();
        $trip = $this->trip($user, ['planned_date' => '2026-09-27', 'end_date' => null]);
        $this->butir($trip, 'Tenda', PreparationStatus::NOT_CONFIRMED);

        $aksi = $this->fase($user)['kartu']['aksi'];

        $this->assertSame(['Cek kesiapan', route('trips.readiness', $trip)], [$aksi['label'], $aksi['href']]);
    }

    /**
     * Izin wajib yang belum dikonfirmasi dengan pemesanan terbuka mengarah ke sistem resmi
     * (tautan keluar); izin yang sudah dikonfirmasi tidak lagi menjadi aksi utama.
     */
    public function test_an_open_window_sends_the_hiker_to_book_on_the_official_system(): void
    {
        $user = $this->pendaki();
        $trip = $this->trip($user);
        $this->izin($trip);
        $this->butir($trip, 'Tenda', PreparationStatus::CONFIRMED);

        $aksi = $this->fase($user)['kartu']['aksi'];

        $this->assertSame(['luar', 'Pesan di sistem resmi', 'https://contoh.test/pemesanan'], [$aksi['jenis'], $aksi['label'], $aksi['href']]);
        $this->assertSame('Sistem resmi Balai Besar TNGGP. Memount menautkan sistem resmi dan tidak menjual izin.', $aksi['keterangan']);

        $this->butir($trip, PreparationService::PERMIT_LABEL, PreparationStatus::CONFIRMED);

        $this->assertSame('Buka ruang kerja', $this->fase($user)['kartu']['aksi']['label']);
    }

    /**
     * Pemesanan yang belum dibuka menawarkan pengingat selama pengingat trip belum menyala;
     * sesudah menyala, hari bukanya sudah terjadwal dan aksinya kembali ke persiapan.
     */
    public function test_a_window_not_yet_open_offers_the_reminder_until_reminders_are_on(): void
    {
        $user = $this->pendaki();
        $trip = $this->trip($user, ['planned_date' => '2026-11-20', 'end_date' => null]);
        $this->izin($trip);
        $this->butir($trip, 'Tenda', PreparationStatus::NOT_CONFIRMED);

        $aksi = $this->fase($user)['kartu']['aksi'];

        $this->assertSame(['pengingat', 'Ingatkan saat dibuka', route('trips.reminders.update', $trip)], [$aksi['jenis'], $aksi['label'], $aksi['href']]);

        $this->actingAs($user)->put($aksi['href'], ['aktif' => true])->assertRedirect(route('trips.show', $trip));

        $this->assertSame('Lanjutkan persiapan', $this->fase($user)['kartu']['aksi']['label']);
        $this->assertSame(1, $trip->reminders()->where('jenis', 'IZIN')->count());
    }

    public function test_without_a_permit_rule_the_action_follows_critical_preparation(): void
    {
        $user = $this->pendaki();
        $trip = $this->trip($user);

        $this->assertSame(['Lanjutkan persiapan', route('trips.preparation', $trip)], [$this->fase($user)['kartu']['aksi']['label'], $this->fase($user)['kartu']['aksi']['href']]);

        $this->butir($trip, 'Tenda', PreparationStatus::CONFIRMED);

        $this->assertSame(['Buka ruang kerja', route('trips.show', $trip)], [$this->fase($user)['kartu']['aksi']['label'], $this->fase($user)['kartu']['aksi']['href']]);
    }

    /**
     * W4-1c: rencana yang tanggalnya lewat tanpa dimulai tidak pernah menjadi trip mendatang,
     * tetapi disebut dengan tautan ke ruang kerjanya.
     */
    public function test_a_plan_whose_date_passed_without_starting_is_named_but_never_next(): void
    {
        $user = $this->pendaki();
        $lewat = $this->trip($user, ['planned_date' => '2026-09-20', 'end_date' => null]);

        $fase = $this->fase($user);

        $this->assertSame('B1', $fase['kunci']);
        $this->assertSame(['nama' => 'Gede bareng Dimas', 'tanggal' => 'Min, 20 Sep 2026', 'url' => route('trips.show', $lewat)], $fase['tripLewat']);
    }

    /**
     * Pukul 23.00 WIB Jumat 25 September sudah Sabtu 26 September di Papua. Trip WIT tanggal 25
     * sudah lewat, sedangkan trip WIB tanggal 25 masih hari H.
     */
    public function test_the_mountains_zone_decides_whether_the_trip_day_has_passed(): void
    {
        $this->travelTo(Carbon::parse('2026-09-25 16:00:00', 'UTC'));
        $papua = $this->pendaki();
        $this->trip($papua, ['planned_date' => '2026-09-25', 'end_date' => null], 'Asia/Jayapura');

        $this->assertSame('B1', $this->fase($papua)['kunci']);
        $this->assertNotNull($this->fase($papua)['tripLewat']);

        $jawa = $this->pendaki();
        $this->trip($jawa, ['planned_date' => '2026-09-25', 'end_date' => null]);

        $this->assertSame('Hari H', $this->fase($jawa)['kartu']['hari']);
    }

    public function test_a_closure_since_planning_is_a_warning_on_the_card(): void
    {
        $user = $this->pendaki();
        $trip = $this->trip($user);
        OfficialStatus::factory()->closed()->create([
            'statusable_type' => (new Trail)->getMorphClass(),
            'statusable_id' => $trip->trail_id,
            'scope' => StatusScope::TRAIL->value,
            'effective_at' => now()->subHour(),
        ]);

        $this->assertSame('Status resmi jalur sekarang: Tutup. Sumber dan waktunya ada di cek kesiapan.', $this->fase($user)['kartu']['peringatan']);
    }

    /**
     * R-014 dan R-005: trip yang sudah ditinjau tidak berbunyi "Siap berangkat" di Beranda, dan
     * tidak ada skor atau persen di props.
     */
    public function test_no_status_word_score_or_percent_reaches_the_home(): void
    {
        $user = $this->pendaki();
        $this->trip($user, ['status' => TripStatus::READY_FOR_DEPARTURE->value]);

        $json = json_encode($this->fase($user));

        $this->assertStringNotContainsString('Siap berangkat', $json);
        $this->assertStringNotContainsString('%', $json);
        $this->assertDoesNotMatchRegularExpression('/skor|score/i', $json);
    }

    /**
     * Ruling W2-6b: muat ulang parsial lembar Pengingat tidak menghitung Beranda. Kueri riwayat
     * dan sesi pendakian hanya milik Beranda, jadi keduanya tidak boleh berjalan.
     */
    public function test_the_reminder_sheet_reload_does_not_compute_the_home(): void
    {
        $user = $this->pendaki();
        $this->trip($user);
        $respons = $this->actingAs($user)->get(route('dashboard'));
        $kueri = [];
        DB::listen(function ($query) use (&$kueri) {
            $kueri[] = $query->sql;
        });

        $respons->assertInertia(fn (AssertableInertia $page) => $page
            ->reloadOnly('daftarPengingat', fn (AssertableInertia $muatUlang) => $muatUlang->missing('fase')));

        $this->assertSame([], array_values(array_filter($kueri, fn (string $sql) => str_contains($sql, 'hiking_history') || str_contains($sql, 'hiking_sessions'))));
    }

    /**
     * Kalimat dukungan keputusan Beranda sebelum W5 tetap ada (R-012, R-014): Beranda membantu
     * memutuskan, tidak menyatakan gunung atau jalur aman, dan tidak menggantikan informasi resmi.
     */
    public function test_the_home_still_says_it_supports_decisions_and_declares_nothing_safe(): void
    {
        $halaman = File::get(resource_path('js/pages/Dashboard.tsx'));

        $this->assertStringContainsString('Sistem ini membantu pengambilan keputusan pendakian.', $halaman);
        $this->assertStringContainsString('informasi resmi dari pengelola.', $halaman);
    }

    /**
     * Sapaan dihitung di klien dari jam perangkat (spec v3 bagian 6.1); halaman memilih satu
     * kartu menurut fase, dan setiap kartu memakai satu aksi utama dari server.
     */
    public function test_the_greeting_comes_from_the_device_clock_and_each_card_has_one_primary_action(): void
    {
        $halaman = File::get(resource_path('js/pages/Dashboard.tsx'));
        $sapaan = File::get(resource_path('js/components/beranda/sapaan.ts'));
        $kartu = File::get(resource_path('js/components/beranda/KartuFase.tsx'));

        $this->assertStringContainsString('title={`${sapaan(new Date())}, ${auth.user?.name ?? \'\'}`}', $halaman);
        $this->assertStringContainsString('<KartuFase kartu={fase.kartu} />', $halaman);
        $this->assertStringContainsString("return 'Selamat pagi';", $sapaan);
        $this->assertStringContainsString('if (jam >= 15 && jam < 18) {', $sapaan);
        $this->assertSame(4, substr_count($kartu, '<AksiUtama aksi={kartu.aksi} />'));
        $this->assertStringContainsString('<AksiUtama aksi={kartu.aksi} />', File::get(resource_path('js/components/beranda/KartuPerjalanan.tsx')));
    }
}
```

Tulis ulang `tests/Feature/DashboardRelevanceTest.php` utuh:

```php
<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Dasbor tidak pernah berubah setelah profil lengkap: pendaki yang tripnya berangkat
 * tiga hari lagi tetap disuruh membuat rencana baru. Yang paling menentukan hari itu,
 * yaitu trip terdekat dan kesiapannya, justru berjarak tiga ketukan.
 *
 * Halaman ini yang paling sering dibuka, jadi isinya harus mencerminkan posisi pengguna
 * dalam alur, bukan menampilkan langkah pertama selamanya.
 *
 * Sejak W5 halaman ini Beranda berfase (spec v3 bagian 6.1): server memilih fasenya
 * (`fase.kunci`, B0 sampai B4) dan mengirim kartu yang sudah diformat. Niat setiap test
 * di bawah tetap sama; rinciannya ada di DashboardPhaseTest.
 */
class DashboardRelevanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Jangkar sama seperti StaleReadinessTest: pukul 08:00 UTC, jauh dari 17:00 UTC
     * tempat tanggal WIB sudah berganti hari sementara tanggal UTC belum. Tanpa jangkar
     * ini, trip yang dibuat dengan now() bisa jatuh tepat di jendela tujuh jam itu
     * tergantung jam sungguhan saat suite berjalan.
     */
    private const HARI_DIUJI_UTC = '2026-09-10 08:00:00';

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse(self::HARI_DIUJI_UTC, 'UTC'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_a_user_without_a_profile_is_sent_to_fill_it_in(): void
    {
        $this->dasbor(User::factory()->create())->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard')
            ->where('title', 'Beranda')
            ->where('fase.kunci', 'B0')
            ->where('fase.kartu.jenis', 'profil')
            ->where('fase.kartu.aksi.href', route('onboarding')));
    }

    public function test_a_user_with_a_profile_but_no_trip_is_asked_to_plan(): void
    {
        $this->dasbor($this->pendaki())->assertInertia(fn (AssertableInertia $page) => $page
            ->where('fase.kunci', 'B1')
            ->where('fase.kartu.aksi.href', route('trips.create')));
    }

    /**
     * Tiga hari sebelum berangkat (jendela prakiraan BMKG), satu aksinya cek kesiapan.
     */
    public function test_an_upcoming_trip_takes_over_the_dashboard(): void
    {
        $pendaki = $this->pendaki();
        $trip = $this->trip($pendaki, now()->addDays(3), TripStatus::PLANNED);

        $this->dasbor($pendaki)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('fase.kunci', 'B2')
            ->where('fase.kartu.nama', $trip->name)
            ->where('fase.kartu.hari', 'H-3')
            ->where('fase.kartu.aksi.href', route('trips.readiness', $trip)));
    }

    public function test_a_trip_departing_today_says_so_in_words(): void
    {
        $pendaki = $this->pendaki();
        $trip = $this->trip($pendaki, now(), TripStatus::READY_FOR_DEPARTURE);

        $this->dasbor($pendaki)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('fase.kartu.hari', 'Hari H')
            ->where('fase.ringkasan', $trip->name.' berangkat hari ini.'));
    }

    /**
     * Pukul 18:00 UTC sudah pukul 01:00 keesokan harinya di WIB (UTC+7). Trip ini
     * direncanakan untuk tanggal yang, menurut WIB, adalah hari ini. Hitungan yang
     * memakai now() mentah masih membaca tanggal kemarin dan berkata "Berangkat besok".
     */
    public function test_the_countdown_does_not_lag_a_day_behind_the_wib_calendar(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-10 18:00:00', 'UTC'));

        $pendaki = $this->pendaki();
        $this->trip($pendaki, Carbon::parse('2026-09-11'), TripStatus::PLANNED);

        $this->dasbor($pendaki)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('fase.kartu.hari', 'Hari H'));
    }

    /**
     * Jangkar yang sama: Trip A sudah lewat menurut WIB tetapi tanggalnya sama dengan
     * tanggal UTC yang belum berganti. Query yang memakai UTC mentah meloloskannya, dan
     * karena diurutkan naik, trip basi itu menyembunyikan trip yang berangkat hari ini.
     */
    public function test_the_upcoming_trip_query_does_not_let_a_wib_past_trip_hide_todays_trip(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-10 18:00:00', 'UTC'));

        $pendaki = $this->pendaki();
        $this->trip($pendaki, Carbon::parse('2026-09-10'), TripStatus::PLANNED);
        $tripHariIni = $this->trip($pendaki, Carbon::parse('2026-09-11'), TripStatus::PLANNED);

        $this->dasbor($pendaki)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('fase.kartu.id', $tripHariIni->id));
    }

    /**
     * Pendakian yang sedang berlangsung mengalahkan apa pun. Pendaki yang membuka
     * aplikasi di jalur butuh mode pendakian, bukan ajakan merencanakan trip lain.
     */
    public function test_a_hike_in_progress_outranks_everything(): void
    {
        $pendaki = $this->pendaki();
        $this->trip($pendaki, now()->addDay(), TripStatus::PLANNED);
        $trip = $this->trip($pendaki, now(), TripStatus::IN_PROGRESS);
        $trip->hikingSession()->create([
            'user_id' => $pendaki->id,
            'status' => 'ACTIVE',
            'started_at' => now(),
        ]);

        $this->dasbor($pendaki)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('fase.kunci', 'B3')
            ->where('fase.kartu.aksi.href', route('trips.hike', $trip, false)));
    }

    /**
     * Trip selesai tanpa riwayat pendakian (bukan lewat mode lapangan) tidak mengajak
     * melapor dan tidak menjadi trip mendatang.
     */
    public function test_a_finished_trip_does_not_keep_taking_over(): void
    {
        $pendaki = $this->pendaki();
        $this->trip($pendaki, now()->subWeek(), TripStatus::COMPLETED);

        $this->dasbor($pendaki)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('fase.kunci', 'B1')
            ->where('fase.tripLewat', null));
    }

    public function test_a_cancelled_trip_is_not_shown_as_upcoming(): void
    {
        $pendaki = $this->pendaki();
        $this->trip($pendaki, now()->addDays(2), TripStatus::CANCELLED);

        $this->dasbor($pendaki)->assertInertia(fn (AssertableInertia $page) => $page->where('fase.kunci', 'B1'));
    }

    /**
     * Trip milik orang lain tidak boleh bocor ke Beranda siapa pun, termasuk lewat props
     * yang tidak digambar halaman.
     */
    public function test_another_users_trip_never_appears(): void
    {
        $trip = $this->trip($this->pendaki(), now()->addDays(2), TripStatus::PLANNED);

        $respons = $this->dasbor($this->pendaki());

        $respons->assertInertia(fn (AssertableInertia $page) => $page->where('fase.kunci', 'B1')->where('fase.tripLewat', null));
        $this->assertStringNotContainsString($trip->name, $respons->getContent());
    }

    public function test_the_dashboard_cost_does_not_grow_with_the_number_of_trips(): void
    {
        $pendaki = $this->pendaki();
        $this->actingAs($pendaki);

        $this->trip($pendaki, now()->addDays(2), TripStatus::PLANNED);
        $this->get('/dashboard');
        $sedikit = $this->hitungQuery();

        for ($i = 3; $i < 18; $i++) {
            $this->trip($pendaki, now()->addDays($i), TripStatus::PLANNED);
        }

        $this->assertSame($sedikit, $this->hitungQuery());
    }

    private function dasbor(User $user): TestResponse
    {
        return $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    private function hitungQuery(): int
    {
        $n = 0;
        DB::listen(function () use (&$n) {
            $n++;
        });

        $this->get('/dashboard');

        DB::getEventDispatcher()->forget('Illuminate\Database\Events\QueryExecuted');

        return $n;
    }

    private function trip(User $pendaki, $tanggal, TripStatus $status): TripPlan
    {
        return TripPlan::create([
            'user_id' => $pendaki->id,
            'trail_id' => Trail::factory()->create()->id,
            'name' => 'Pendakian '.$status->value.$tanggal->format('dHis'),
            'planned_date' => $tanggal->toDateString(),
            'trip_type' => TripType::CAMPING->value,
            'status' => $status->value,
        ]);
    }

    private function pendaki(): User
    {
        $user = User::factory()->create();
        $user->profile()->create([
            'experience_level' => ExperienceLevel::INTERMEDIATE->value,
            'completed_at' => now(),
        ]);

        return $user->fresh();
    }
}
```

`tests/Feature/IndonesianInterfaceTest.php`:

1. Ganti:

```php
        // klien, jadi labelnya diperiksa dari sumber layout.
        $this->assertStringContainsString('Dasbor', $isi);

        $layout = collect(File::files(resource_path('js/layouts')))->map(fn ($f) => $f->getContents())->implode("\n");
```

dengan:

```php
        // klien, jadi labelnya diperiksa dari sumber layout.
        $this->assertStringContainsString('Beranda', $isi);

        $layout = collect(File::files(resource_path('js/layouts')))->map(fn ($f) => $f->getContents())->implode("\n");
```

`tests/Feature/StaleReadinessTest.php`:

1. Ganti:

```php
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
```

dengan:

```php
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;
```

2. Ganti:

```php
    }

    public function test_the_dashboard_stops_presenting_a_superseded_verdict(): void
```

dengan:

```php
    }

    /**
     * Beranda W5 (spec v3 bagian 6.1) menulis vonis basi sebagai peringatan di kartu trip, dan
     * langkah Cek tidak tampak selesai. Kalimatnya datang dari TripSteps lewat props, jadi yang
     * diuji di sini props, bukan sumber halaman.
     */
    public function test_the_dashboard_stops_presenting_a_superseded_verdict(): void
```

3. Ganti:

```php
        $this->majuSehariLaluTutup($trip);

        // Dasbor kini React: server menandai vonisnya basi dan tidak mengirim labelnya.
        $this->actingAs($trip->user)->get(route('dashboard'))->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('trip.id', $trip->id)
                ->where('trip.catatan.jenis', 'basi')
                ->where('trip.catatan.label', null));

        $this->assertStringContainsString('Status jalur berubah, perlu dinilai ulang', File::get(resource_path('js/pages/Dashboard.tsx')));
    }
```

dengan:

```php
        $this->majuSehariLaluTutup($trip);

        $this->actingAs($trip->user)->get(route('dashboard'))->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('fase.kunci', 'B2')
                ->where('fase.kartu.id', $trip->id)
                ->where('fase.kartu.peringatan', 'Status jalur berubah, perlu dinilai ulang')
                ->where('fase.kartu.langkah.3.keadaan', fn (string $keadaan) => $keadaan !== 'selesai'));
    }
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='DashboardPhase|DashboardRelevance|StaleReadiness|IndonesianInterface|ReminderSheet|ActiveHikeStrip|PageHeaderContract|NoDeadViewData|EmptyStateCoverage|TapTarget|TrustGrammar|KeyboardAndScreenReader'`
Expected: FAIL. Pada simulasi rencana: 112 test, 85 lulus, 12 gagal, 15 error. Yang merah: 15 test `DashboardPhaseTest` (props `fase` dan `sapaan.ts` belum ada), sepuluh test `DashboardRelevanceTest` (props `fase.kunci` dan `fase.kartu` belum ada; judul masih "Dasbor"), `IndonesianInterfaceTest::test_the_navigation_offers_indonesian_labels` (judul dokumen masih "Dasbor"), dan `StaleReadinessTest::test_the_dashboard_stops_presenting_a_superseded_verdict`. Yang sudah lulus sebelum implementasi karena menjaga perilaku lama yang harus bertahan: `DashboardPhaseTest::test_the_reminder_sheet_reload_does_not_compute_the_home`, `DashboardPhaseTest::test_the_home_still_says_it_supports_decisions_and_declares_nothing_safe`, dan `DashboardRelevanceTest::test_the_dashboard_cost_does_not_grow_with_the_number_of_trips`.

- [ ] **Step 3: Tulis implementasinya.** `KonteksBeranda` dibuat dengan `make:class`; berkas TypeScript dibuat
langsung; `RingkasanDasbor` dan `Dashboard.tsx` ditulis ulang utuh.

Buat `app/Services/Dashboard/KonteksBeranda.php`:

```php
<?php

namespace App\Services\Dashboard;

use App\Models\HikingHistory;
use App\Models\PermitRequirement;
use App\Models\TripPlan;
use App\Models\User;

/**
 * Posisi pendaki dalam siklus trip untuk Beranda (spec v3 bagian 6.1, DK-45), dihitung sekali
 * per permintaan oleh RingkasanDasbor::konteks() dan dibaca setiap bagian Beranda: kartu fase,
 * tenggat, kabar, jalur untuk Anda, dan tangga tanjakan.
 *
 * Relasi yang sudah dimuat: trip (B2) membawa trail.mountain, preparationItems, dan
 * latestReadinessCheck; riwayat (B4) membawa tripPlan.trail.mountain; tripLewat membawa
 * trail.mountain.
 */
final readonly class KonteksBeranda
{
    /**
     * @param  string  $fase  salah satu konstanta RingkasanDasbor::B0 sampai B4
     * @param  array{id: int, jalur: string, posBerikutnya: string|null, mulai: string|null, url: string}|null  $pendakian  pendakian berlangsung (B3), dari PendakianAktif
     * @param  TripPlan|null  $trip  trip mendatang terdekat yang tanggalnya belum lewat di zona gunungnya (B2)
     * @param  PermitRequirement|null  $aturanIzin  aturan izin jalur trip itu, dicari sekali (PermitService::requirementFor)
     * @param  HikingHistory|null  $riwayat  pendakian selesai yang belum dilaporkan (B4)
     * @param  TripPlan|null  $tripLewat  rencana terbaru yang tanggalnya lewat tanpa dimulai (B0, B1, B4)
     */
    public function __construct(
        public string $fase,
        public User $pengguna,
        public bool $profilLengkap,
        public ?array $pendakian = null,
        public ?TripPlan $trip = null,
        public ?PermitRequirement $aturanIzin = null,
        public ?HikingHistory $riwayat = null,
        public ?TripPlan $tripLewat = null,
    ) {}
}
```

Buat `resources/js/components/beranda/AksiUtama.tsx`:

```tsx
import { router } from '@inertiajs/react';
import { useId, useState } from 'react';
import { Button } from '@/components/ui/Button';
import { Icon } from '@/components/ui/Icon';
import type { AksiFase } from './types';

/**
 * Satu aksi utama fase Beranda (spec v3 bagian 6.1), selebar kartu di ponsel. Jenisnya dari
 * server: tautan di aplikasi, tautan ke sistem resmi di situs lain (dengan ikon tautan keluar,
 * DESIGN.md bagian Ikon), atau sakelar pengingat trip yang dikirim lewat rute yang sama dengan
 * kartu pengingat ruang kerja. Keterangannya terikat ke tombol lewat aria-describedby.
 */
export function AksiUtama({ aksi }: { aksi: AksiFase }) {
    const idKeterangan = useId();
    const [memproses, setMemproses] = useState(false);
    const keterangan = aksi.keterangan ? idKeterangan : undefined;

    const nyalakanPengingat = () => {
        router.put(aksi.href, { aktif: true }, { onStart: () => setMemproses(true), onFinish: () => setMemproses(false) });
    };

    return (
        <div className="mt-5">
            {aksi.jenis === 'pengingat' ? (
                <Button className="w-full sm:w-auto" processing={memproses} aria-describedby={keterangan} onClick={nyalakanPengingat}>
                    {aksi.label}
                </Button>
            ) : (
                <Button href={aksi.href} external={aksi.jenis === 'luar'} className="w-full sm:w-auto" aria-describedby={keterangan}>
                    {aksi.label}
                    {aksi.jenis === 'luar' ? (
                        <>
                            <Icon name="arrow-square-out" size={20} />
                            <span className="sr-only">, membuka situs resmi</span>
                        </>
                    ) : null}
                </Button>
            )}
            {aksi.keterangan ? (
                <p id={idKeterangan} className="mt-2 text-meta text-secondary">
                    {aksi.keterangan}
                </p>
            ) : null}
        </div>
    );
}
```

Buat `resources/js/components/beranda/KartuFase.tsx`:

```tsx
import { useId, type ReactNode } from 'react';
import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';
import { index as trailsIndex } from '@/routes/trails';
import { AksiUtama } from './AksiUtama';
import { KartuPerjalanan } from './KartuPerjalanan';
import type { KartuFaseData } from './types';

const tautanTeks =
    'inline-flex min-h-11 items-center text-kecil font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600';

/**
 * Kartu fase Beranda (spec v3 bagian 6.1): satu kartu per fase, masing-masing dengan tepat satu
 * aksi utama yang dipilih server. Fase B2 punya kartunya sendiri (KartuPerjalanan); fase lain
 * berupa kartu pendek yang menyebut posisi pendaki dan alasannya.
 */
export function KartuFase({ kartu }: { kartu: KartuFaseData }) {
    switch (kartu.jenis) {
        case 'perjalanan':
            return <KartuPerjalanan kartu={kartu} />;
        case 'profil':
            return (
                <KartuPendek judul="Kenali pengalaman Anda">
                    <p className="mt-2 max-w-prose text-isi text-secondary">
                        Label kecocokan jalur dihitung dari profil pendaki yang Anda isi sendiri: tingkat pengalaman, rekam jejak pendakian, dan preferensi perjalanan.
                        Sebelum profil terisi, kecocokan setiap jalur belum dapat dinilai.
                    </p>
                    <AksiUtama aksi={kartu.aksi} />
                    <p className="mt-2 text-kecil text-secondary">
                        Jelajah tetap dapat dibuka sekarang.{' '}
                        <Tautan href={trailsIndex.url()} className={tautanTeks}>
                            Lihat jalur tanpa penilaian
                        </Tautan>
                    </p>
                </KartuPendek>
            );
        case 'mulai':
            return (
                <KartuPendek judul="Rencanakan pendakian berikutnya">
                    <p className="mt-2 max-w-prose text-isi text-secondary">
                        Pilih jalur dari simpanan atau dari Jelajah, lalu atur tanggal, jenis trip, dan peserta. Daftar persiapannya disusun dari jalur yang Anda pilih.
                    </p>
                    <AksiUtama aksi={kartu.aksi} />
                </KartuPendek>
            );
        case 'mendaki':
            return (
                <KartuPendek judul={kartu.posBerikutnya ? `Pos berikutnya: ${kartu.posBerikutnya}` : 'Pos berikutnya belum diketahui'} menonjol>
                    <p className="mt-2 text-isi text-primary">
                        {kartu.posBerikutnya
                            ? 'Perbarui posisi dan lihat peta jalur di mode lapangan.'
                            : 'Pos berikutnya tampil sesudah Anda memperbarui posisi di mode lapangan.'}
                    </p>
                    <AksiUtama aksi={kartu.aksi} />
                </KartuPendek>
            );
        case 'lapor':
            return (
                <KartuPendek judul={kartu.nama}>
                    <p className="mt-1 break-words text-kecil text-secondary">
                        {kartu.jalur}
                        {kartu.gunung ? `, ${kartu.gunung}` : null}
                    </p>
                    <dl className="mt-3 grid grid-cols-2 gap-3">
                        <div>
                            <dt className="text-meta text-secondary">Hasil</dt>
                            <dd className="text-isi font-bold text-primary">{kartu.hasil}</dd>
                        </div>
                        <div>
                            <dt className="text-meta text-secondary">Selesai</dt>
                            <dd className="text-isi font-bold text-primary">{kartu.selesai}</dd>
                        </div>
                    </dl>
                    <p className="mt-3 max-w-prose text-isi text-secondary">
                        Laporan kondisi Anda membantu pendaki yang berangkat sesudah Anda. Laporan tampil untuk pendaki lain sesudah lolos moderasi dan tidak mengubah status resmi.
                    </p>
                    <AksiUtama aksi={kartu.aksi} />
                    <Tautan href={kartu.ringkasanUrl} className={kelas(tautanTeks, 'mt-2')}>
                        Lihat ringkasan pendakian
                    </Tautan>
                </KartuPendek>
            );
    }
}

function KartuPendek({ judul, menonjol = false, children }: { judul: string; menonjol?: boolean; children: ReactNode }) {
    const idJudul = useId();

    return (
        <section aria-labelledby={idJudul} className={kelas('rounded-utama border p-5 sm:p-6', menonjol ? 'border-brand-300 bg-brand-50' : 'border-subtle bg-surface')}>
            <h2 id={idJudul} className="break-words text-judul-kartu text-primary">
                {judul}
            </h2>
            {children}
        </section>
    );
}
```

Buat `resources/js/components/beranda/KartuPerjalanan.tsx`:

```tsx
import { AngkaRingkas } from '@/components/trails/AngkaRingkas';
import { GarisProfil } from '@/components/trails/GarisProfil';
import type { KeadaanLangkah } from '@/components/trips/types';
import { Icon } from '@/components/ui/Icon';
import { Lencana } from '@/components/ui/Lencana';
import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';
import { AksiUtama } from './AksiUtama';
import type { KartuPerjalananData } from './types';

const keadaanLangkah: Record<KeadaanLangkah, { bilah: string; kata: string }> = {
    selesai: { bilah: 'bg-brand-700', kata: 'selesai' },
    berikutnya: { bilah: 'bg-aksen-pos', kata: 'langkah berikutnya' },
    belum: { bilah: 'bg-surface-sunken', kata: 'belum' },
};

/**
 * Kartu perjalanan berikutnya (fase B2, prototype 01). Tingginya mengikuti isinya (DK-31): tanpa
 * tinggi tetap dan tanpa foto, karena foto gunung berlisensi menunggu W8 (DK-37); visual datanya
 * garis profil jalur. Empat langkah memakai bilah per langkah: langkah selesai bilah merek dan
 * centang sesudah namanya, langkah berikutnya bilah kuning pos (satu aksen) dan huruf tebal
 * dengan aria-current, jadi keadaannya tidak dibawa warna saja. Setiap langkah selebar paling
 * sedikit namanya (min-w-max), jadi nama tidak terpotong di tengah kata pada 320 px. Persiapan ditulis sebagai hitungan
 * butir kritis dengan ruas per butir, tanpa persen (R-005).
 */
export function KartuPerjalanan({ kartu }: { kartu: KartuPerjalananData }) {
    const { persiapan } = kartu;

    return (
        <article aria-labelledby={`perjalanan-${kartu.id}`} className="rounded-utama border border-subtle bg-surface p-5 sm:p-6">
            <p className="flex flex-wrap items-center gap-2">
                <Lencana nada="merek">{kartu.jenisTrip}</Lencana>
                {kartu.hari ? (
                    <Lencana>
                        <span data-angka>{kartu.hari}</span>
                    </Lencana>
                ) : null}
            </p>

            <h2 id={`perjalanan-${kartu.id}`} className="mt-2 break-words text-judul-kartu text-primary">
                <Tautan href={kartu.url} className="rounded-sm underline-offset-2 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
                    {kartu.nama}
                </Tautan>
            </h2>
            <p className="mt-1 break-words text-kecil text-secondary">
                {kartu.jalur}, {kartu.gunung}
            </p>
            <p data-angka className="mt-0.5 flex flex-wrap gap-x-3 text-kecil text-secondary">
                <span>{kartu.tanggal}</span>
                {kartu.jam ? <span>Mulai {kartu.jam}</span> : null}
                {kartu.peserta ? <span>{kartu.peserta}</span> : null}
            </p>

            {kartu.garis ? <GarisProfil titik={kartu.garis} label={null} className="mt-4" /> : null}
            <AngkaRingkas jalur={kartu.angka} className="mt-2 text-secondary" />

            <ol aria-label="Langkah trip" className="mt-4 flex gap-1.5">
                {kartu.langkah.map((l) => (
                    <li key={l.kunci} aria-current={l.keadaan === 'berikutnya' ? 'step' : undefined} className="min-w-max flex-1">
                        <span aria-hidden="true" className={kelas('block h-1.5 rounded-full', keadaanLangkah[l.keadaan].bilah)} />
                        <span className={kelas('mt-1.5 flex items-center gap-0.5 text-meta', l.keadaan === 'belum' ? 'font-semibold text-secondary' : 'font-bold text-primary')}>
                            {l.judul}
                            {l.keadaan === 'selesai' ? <Icon name="check" size={16} className="text-brand-ink" /> : null}
                        </span>
                        <span className="sr-only">, {keadaanLangkah[l.keadaan].kata}</span>
                    </li>
                ))}
            </ol>

            {kartu.peringatan ? <p className="mt-3 text-kecil font-bold text-warn-900">{kartu.peringatan}</p> : null}

            <section aria-labelledby={`persiapan-${kartu.id}`} className="mt-4 border-t border-subtle pt-4">
                <h3 id={`persiapan-${kartu.id}`} className="text-kecil font-bold text-primary">
                    Persiapan kritis
                </h3>
                {persiapan.berlaku > 0 ? (
                    <>
                        <p data-angka className="mt-1 text-isi font-bold text-primary">
                            {persiapan.dikonfirmasi} dari {persiapan.berlaku} butir kritis dikonfirmasi
                        </p>
                        <div aria-hidden="true" className="mt-2 flex gap-1">
                            {Array.from({ length: persiapan.berlaku }, (_, i) => (
                                <span key={i} className={kelas('h-2 flex-1 rounded-full', i < persiapan.dikonfirmasi ? 'bg-brand-700' : 'bg-surface-sunken')} />
                            ))}
                        </div>
                        {persiapan.belum.length > 0 ? (
                            <p className="mt-2 break-words text-kecil text-primary">
                                Belum dikonfirmasi: {persiapan.belum.join(', ')}
                                {persiapan.sisa > 0 ? `, dan ${persiapan.sisa} lainnya` : null}.
                            </p>
                        ) : null}
                    </>
                ) : (
                    <p className="mt-1 text-kecil text-secondary">Daftar persiapan belum dibuat. Daftarnya disusun dari jalur ini saat halaman persiapan dibuka.</p>
                )}
            </section>

            <AksiUtama aksi={kartu.aksi} />
        </article>
    );
}
```

Buat `resources/js/components/beranda/sapaan.ts`:

```ts
/**
 * Sapaan waktu Beranda dari jam perangkat (spec v3 bagian 6.1): pagi sampai pukul 10.59, siang
 * sampai 14.59, sore sampai 17.59, malam sesudahnya sampai pukul 03.59. Jam perangkat, bukan zona
 * gunung: sapaan menyapa pendakinya di tempat ia membaca, sedangkan tanggal dan jam rencana
 * tetap memakai zona gunungnya dari server.
 */
export function sapaan(sekarang: Date): string {
    const jam = sekarang.getHours();

    if (jam >= 4 && jam < 11) {
        return 'Selamat pagi';
    }

    if (jam >= 11 && jam < 15) {
        return 'Selamat siang';
    }

    if (jam >= 15 && jam < 18) {
        return 'Selamat sore';
    }

    return 'Selamat malam';
}
```

Buat `resources/js/components/beranda/types.ts`:

```ts
import type { KeadaanLangkah } from '@/components/trips/types';

/** Fase Beranda (spec v3 bagian 6.1, App\Services\Dashboard\RingkasanDasbor). */
export type KunciFase = 'B0' | 'B1' | 'B2' | 'B3' | 'B4';

/**
 * Satu aksi utama fase, dipilih server: tautan di dalam aplikasi, tautan ke sistem resmi di
 * situs lain, atau sakelar pengingat trip (PUT trips.reminders.update dengan aktif true).
 */
export interface AksiFase {
    jenis: 'tautan' | 'luar' | 'pengingat';
    label: string;
    href: string;
    keterangan: string | null;
}

/** Kartu perjalanan berikutnya (fase B2): seluruh teks sudah diformat server. */
export interface KartuPerjalananData {
    jenis: 'perjalanan';
    id: number;
    nama: string;
    url: string;
    jalur: string;
    gunung: string;
    jenisTrip: string;
    hari: string | null;
    tanggal: string;
    jam: string | null;
    peserta: string | null;
    /** Format TrailRowResource: "-" bila angkanya belum ada. */
    angka: { jarak: string; tanjakan: string; durasi: string };
    /** Titik 0..1 garis profil jalur; null tanpa profil. */
    garis: Array<[number, number]> | null;
    langkah: Array<{ kunci: string; judul: string; keadaan: KeadaanLangkah }>;
    peringatan: string | null;
    persiapan: { dikonfirmasi: number; berlaku: number; belum: string[]; sisa: number };
    aksi: AksiFase;
}

export type KartuFaseData =
    | { jenis: 'profil'; aksi: AksiFase }
    | { jenis: 'mulai'; aksi: AksiFase }
    | KartuPerjalananData
    | { jenis: 'mendaki'; posBerikutnya: string | null; aksi: AksiFase }
    | { jenis: 'lapor'; nama: string; jalur: string; gunung: string | null; hasil: string; selesai: string; ringkasanUrl: string; aksi: AksiFase };

export interface FaseBeranda {
    kunci: KunciFase;
    ringkasan: string;
    kartu: KartuFaseData;
    /** Rencana terbaru yang tanggalnya lewat tanpa dimulai (W4-1c); null bila tidak ada. */
    tripLewat: { nama: string; tanggal: string; url: string } | null;
}
```

`app/Http/Controllers/DashboardController.php`:

1. Ganti:

```php
/**
 * Dasbor yang mengikuti posisi pengguna dalam alur: pendakian yang sedang berjalan,
 * trip terdekat, atau ajakan memulai. Isinya dihitung RingkasanDasbor; halaman React
 * hanya memilih cabang dari `keadaan`.
 */
```

dengan:

```php
/**
 * Beranda berfase (spec v3 bagian 6.1, DK-45). Isinya disusun RingkasanDasbor dan kelas
 * bagian Beranda; halaman React hanya memilih tampilan dari fasenya.
 *
 * Setiap bagian adalah prop malas (closure), jadi muat ulang parsial yang tidak memintanya,
 * misalnya lembar Pengingat yang hanya meminta daftarPengingat, tidak menghitung Beranda
 * (ruling W2-6b).
 */
```

2. Ganti:

```php
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Dashboard', [
            'title' => 'Dasbor',
            ...(new RingkasanDasbor($request->user()))->props(),
        ]);
```

dengan:

```php
{
    public function __invoke(Request $request, RingkasanDasbor $ringkasan): Response
    {
        $user = $request->user();

        return Inertia::render('Dashboard', [
            'title' => 'Beranda',
            'fase' => fn () => $ringkasan->props($user),
        ]);
```

Tulis ulang `app/Services/Dashboard/RingkasanDasbor.php` utuh:

```php
<?php

namespace App\Services\Dashboard;

use App\Enums\OfficialStatusValue;
use App\Enums\TripStatus;
use App\Models\HikingHistory;
use App\Models\PermitRequirement;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\OfficialStatusService;
use App\Services\PermitService;
use App\Services\PreparationService;
use App\Services\Trails\SkalaProfil;
use App\Services\Trips\KepalaTrip;
use App\Services\Trips\PendakianAktif;
use App\Services\Trips\TripReminderScheduler;
use App\Services\Trips\TripSteps;
use App\Support\Durasi;
use App\Support\Timezone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Beranda berfase (spec v3 bagian 6.1, DK-45, rencana induk W5 butir 1).
 *
 * Fase dipilih menurut urutan kepentingan, bukan urutan alur: pendakian yang sedang berjalan
 * (B3), trip mendatang (B2), pendakian selesai yang belum dilaporkan (B4), profil yang belum
 * diisi (B0), lalu ajakan merencanakan (B1). Setiap fase membawa tepat satu aksi utama yang
 * dipilih di sini, bukan di klien. Konteksnya dihitung sekali per permintaan dan dipakai ulang
 * bagian Beranda lain lewat konteks(). Tanggal dan hitung mundur diformat dalam zona gunungnya.
 *
 * Trip yang tanggalnya lewat tanpa dimulai (W4-1c) tidak pernah menjadi trip mendatang. Fase
 * B0, B1, dan B4 menyebut yang paling baru dengan tautan ke ruang kerjanya, tempat penanda
 * "Tanggal rencana sudah lewat" dan aksi "Ubah rencana" berada.
 *
 * Status trip tidak pernah ditulis untuk trip yang belum dimulai (W4-R4, ruling W4-C4): kata
 * "Siap berangkat" di samping trip mendatang terbaca sebagai jaminan (R-014).
 */
final class RingkasanDasbor
{
    public const B0 = 'B0';

    public const B1 = 'B1';

    public const B2 = 'B2';

    public const B3 = 'B3';

    public const B4 = 'B4';

    /** @var array<int, TripStatus> */
    private const BELUM_DIMULAI = [TripStatus::DRAFT, TripStatus::PLANNED, TripStatus::READY_FOR_DEPARTURE];

    /** Trip yang diperiksa untuk menemukan yang belum lewat di zona gunungnya. */
    private const KANDIDAT = 3;

    /** Butir kritis yang belum dikonfirmasi yang disebut namanya di kartu trip. */
    private const BUTIR_DISEBUT = 3;

    private ?KonteksBeranda $konteks = null;

    public function __construct(
        private readonly PendakianAktif $pendakianAktif,
        private readonly KepalaTrip $kepala,
        private readonly TripSteps $langkah,
        private readonly OfficialStatusService $status,
        private readonly PermitService $izin,
        private readonly TripReminderScheduler $pengingat,
    ) {}

    public function konteks(User $user): KonteksBeranda
    {
        if ($this->konteks !== null && $this->konteks->pengguna->is($user)) {
            return $this->konteks;
        }

        $profil = $user->hasCompletedProfile();
        $pendakian = $this->pendakianAktif->untuk($user);

        if ($pendakian !== null) {
            return $this->konteks = new KonteksBeranda(self::B3, $user, $profil, pendakian: $pendakian);
        }

        $trip = $this->tripMendatang($user);

        if ($trip !== null) {
            return $this->konteks = new KonteksBeranda(self::B2, $user, $profil, trip: $trip, aturanIzin: $this->izin->requirementFor($trip->trail));
        }

        $riwayat = $this->riwayatBelumDilaporkan($user);

        $fase = match (true) {
            $riwayat !== null => self::B4,
            ! $profil => self::B0,
            default => self::B1,
        };

        return $this->konteks = new KonteksBeranda($fase, $user, $profil, riwayat: $riwayat, tripLewat: $this->tripLewat($user));
    }

    /**
     * @return array{kunci: string, ringkasan: string, kartu: array<string, mixed>, tripLewat: array{nama: string, tanggal: string, url: string}|null}
     */
    public function props(User $user): array
    {
        $k = $this->konteks($user);

        return [
            'kunci' => $k->fase,
            'ringkasan' => $this->ringkasan($k),
            'kartu' => match ($k->fase) {
                self::B0 => ['jenis' => 'profil', 'aksi' => self::tautan('Isi profil pendaki', route('onboarding'))],
                self::B1 => ['jenis' => 'mulai', 'aksi' => self::tautan('Rencanakan trip', route('trips.create'))],
                self::B2 => $this->kartuPerjalanan($k->trip, $k->aturanIzin),
                self::B3 => $this->kartuMendaki($k->pendakian),
                default => $this->kartuLapor($k->riwayat),
            },
            'tripLewat' => $k->tripLewat === null ? null : [
                'nama' => $k->tripLewat->name,
                'tanggal' => KepalaTrip::rentang($k->tripLewat->planned_date, $k->tripLewat->end_date),
                'url' => route('trips.show', $k->tripLewat),
            ],
        ];
    }

    /**
     * Trip terdekat yang belum dimulai dan tanggalnya belum lewat di zona gunungnya. Lantai
     * kueri WIB (tanggal Indonesia yang paling akhir berganti hari), lalu beberapa kandidat
     * diperiksa di zona gunungnya masing-masing, supaya trip WIT yang hari ini sudah lewat
     * di Papua tidak terbaca mendatang selama WIB masih di tanggal yang sama.
     */
    private function tripMendatang(User $user): ?TripPlan
    {
        return $this->belumDimulai($user)
            ->whereDate('planned_date', '>=', Timezone::earliestDateInIndonesia())
            ->orderBy('planned_date')
            ->orderBy('id')
            ->limit(self::KANDIDAT)
            ->with('trail.mountain', 'preparationItems', 'latestReadinessCheck')
            ->get()
            ->first(fn (TripPlan $trip) => KepalaTrip::hariMenuju($trip->planned_date, Timezone::forTrail($trip->trail)) !== null);
    }

    /**
     * Rencana terbaru yang tanggalnya sudah lewat di zona gunungnya tanpa pernah dimulai.
     */
    private function tripLewat(User $user): ?TripPlan
    {
        return $this->belumDimulai($user)
            ->whereDate('planned_date', '<', Timezone::latestDateInIndonesia())
            ->orderByDesc('planned_date')
            ->orderByDesc('id')
            ->limit(self::KANDIDAT)
            ->with('trail.mountain')
            ->get()
            ->first(fn (TripPlan $trip) => KepalaTrip::hariMenuju($trip->planned_date, Timezone::forTrail($trip->trail)) === null);
    }

    /**
     * @return Builder<TripPlan>
     */
    private function belumDimulai(User $user): Builder
    {
        return TripPlan::query()
            ->whereBelongsTo($user)
            ->whereIn('status', array_map(fn (TripStatus $status) => $status->value, self::BELUM_DIMULAI));
    }

    /**
     * Pendakian selesai paling lama hiking.beranda.hari_ajakan_lapor hari lalu (fase B4) yang
     * belum dilaporkan: tidak ada laporan yang ditautkan ke riwayatnya, dan pendakinya belum
     * menulis laporan untuk jalur itu sesudah pendakian selesai lewat jalan lain.
     */
    private function riwayatBelumDilaporkan(User $user): ?HikingHistory
    {
        return HikingHistory::query()
            ->where('user_id', $user->id)
            ->whereNotNull('trip_plan_id')
            ->whereNull('trail_condition_report_id')
            ->where('completed_at', '>=', Carbon::now()->subDays((int) config('hiking.beranda.hari_ajakan_lapor')))
            ->whereNotExists(fn ($laporan) => $laporan->select(DB::raw(1))
                ->from('trail_condition_reports')
                ->whereColumn('trail_condition_reports.user_id', 'hiking_history.user_id')
                ->whereColumn('trail_condition_reports.trail_id', 'hiking_history.trail_id')
                ->whereColumn('trail_condition_reports.created_at', '>=', 'hiking_history.completed_at'))
            ->with('tripPlan.trail.mountain')
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->first();
    }

    private function ringkasan(KonteksBeranda $k): string
    {
        return match ($k->fase) {
            self::B0 => 'Isi profil pendaki supaya jalur dinilai menurut pengalaman Anda.',
            self::B1 => 'Belum ada trip mendatang.',
            self::B2 => $this->ringkasanPerjalanan($k->trip),
            self::B3 => 'Anda sedang mendaki '.$k->pendakian['jalur'].'.',
            default => 'Pendakian '.$k->riwayat->tripPlan->name.' sudah selesai.',
        };
    }

    private function ringkasanPerjalanan(TripPlan $trip): string
    {
        $kapan = match ($hari = self::hariLagi($trip)) {
            0 => $trip->name.' berangkat hari ini.',
            1 => $trip->name.' berangkat besok.',
            default => $trip->name.' tinggal '.$hari.' hari lagi.',
        };
        $belum = count(PreparationService::kritis($trip->preparationItems)['belum']);

        return $belum > 0 ? $kapan.' '.$belum.' butir kritis belum dikonfirmasi.' : $kapan;
    }

    /**
     * Kartu perjalanan berikutnya (prototype 01): kepala trip yang sama dengan ruang kerja
     * (KepalaTrip), empat langkah dari TripSteps, persiapan kritis sebagai hitungan (R-005),
     * dan satu aksi utama.
     *
     * @return array<string, mixed>
     */
    private function kartuPerjalanan(TripPlan $trip, ?PermitRequirement $aturan): array
    {
        $statusSekarang = $this->status->effectiveStatusesForTrails(new EloquentCollection([$trip->trail]))[$trip->trail_id] ?? null;
        $kepala = $this->kepala->untuk($trip);
        $langkah = $this->langkah->for($trip, $statusSekarang);
        $kritis = PreparationService::kritis($trip->preparationItems);
        $jalur = $trip->trail;

        return [
            'jenis' => 'perjalanan',
            'id' => $trip->id,
            'nama' => $trip->name,
            'url' => route('trips.show', $trip),
            'jalur' => $kepala['jalur'],
            'gunung' => $kepala['gunung'],
            'jenisTrip' => $kepala['jenis'],
            'hari' => $kepala['hari'],
            'tanggal' => $kepala['tanggal'],
            'jam' => $kepala['jam'],
            'peserta' => $kepala['peserta'],
            // Format yang sama dengan TrailRowResource ("-" bila tidak ada), supaya AngkaRingkas
            // yang membuang angka kosong dapat dipakai ulang.
            'angka' => [
                'jarak' => $jalur->distance_km !== null ? number_format((float) $jalur->distance_km, 1, ',', '.').' km' : '-',
                'tanjakan' => $jalur->elevation_gain_m !== null ? number_format($jalur->elevation_gain_m, 0, ',', '.').' m' : '-',
                'durasi' => Durasi::pendek($jalur->estimated_duration_minutes),
            ],
            'garis' => SkalaProfil::naikBersama([$jalur->id => $jalur->elevation_profile])['garis'][$jalur->id] ?? null,
            'langkah' => array_map(fn (array $l) => ['kunci' => $l['kunci'], 'judul' => $l['judul'], 'keadaan' => $l['keadaan']], $langkah),
            'peringatan' => $this->peringatan(collect($langkah)->firstWhere('kunci', 'kesiapan'), $statusSekarang),
            'persiapan' => [
                'dikonfirmasi' => $kritis['dikonfirmasi'],
                'berlaku' => $kritis['berlaku'],
                'belum' => array_slice($kritis['belum'], 0, self::BUTIR_DISEBUT),
                'sisa' => max(0, count($kritis['belum']) - self::BUTIR_DISEBUT),
            ],
            'aksi' => $this->aksiPerjalanan($trip, $aturan, $kritis),
        ];
    }

    /**
     * Vonis kesiapan yang basi atau jalur yang ditarik (dari langkah Cek, TripSteps), atau
     * status resmi Tutup dan Dibatasi yang berlaku sekarang. Status lengkap dengan sumbernya
     * ada di cek kesiapan; di sini hanya peringatan yang mengubah rencana.
     *
     * @param  array{keterangan: string, peringatan: bool}  $kesiapan
     */
    private function peringatan(array $kesiapan, ?OfficialStatusValue $status): ?string
    {
        if ($kesiapan['peringatan']) {
            return $kesiapan['keterangan'];
        }

        return in_array($status, [OfficialStatusValue::CLOSED, OfficialStatusValue::RESTRICTED], true)
            ? 'Status resmi jalur sekarang: '.$status->label().'. Sumber dan waktunya ada di cek kesiapan.'
            : null;
    }

    /**
     * Satu aksi utama fase B2 (spec v3 bagian 6.1), dipilih menurut urutan ini:
     * - hari H: "Mulai pendakian" lewat tab Mendaki, tempat memulai dikonfirmasi;
     * - H-3 sampai H-1 (jendela prakiraan BMKG): "Cek kesiapan";
     * - izin wajib belum dikonfirmasi, pemesanan terbuka, dan tautan resminya tercatat: "Pesan
     *   di sistem resmi" (tautan keluar; Memount tidak menjual izin);
     * - pemesanan belum dibuka dan pengingat trip belum menyala: "Ingatkan saat dibuka", yang
     *   menyalakan pengingat trip termasuk hari pemesanan dibuka;
     * - butir kritis belum lengkap: "Lanjutkan persiapan";
     * - selain itu: "Buka ruang kerja".
     *
     * @param  array{dikonfirmasi: int, berlaku: int, belum: array<int, string>}  $kritis
     * @return array{jenis: string, label: string, href: string, keterangan: string|null}
     */
    private function aksiPerjalanan(TripPlan $trip, ?PermitRequirement $aturan, array $kritis): array
    {
        $hari = self::hariLagi($trip);

        if ($hari === 0) {
            return self::tautan('Mulai pendakian', route('track'), 'Pendakian dimulai dari tab Mendaki sesudah Anda memastikannya.');
        }

        if ($hari <= (int) config('hiking.conditions.weather_forecast_days')) {
            return self::tautan('Cek kesiapan', route('trips.readiness', $trip));
        }

        if ($aturan !== null && ! PreparationService::izinDiurus($trip)) {
            $keadaan = $this->izin->bookingWindowFor($trip->trail, $trip->planned_date, $aturan)['state'] ?? null;

            if ($keadaan === PermitService::BOOKING_TERBUKA && $aturan->booking_url) {
                return ['jenis' => 'luar', 'label' => 'Pesan di sistem resmi', 'href' => $aturan->booking_url, 'keterangan' => 'Sistem resmi '.$aturan->authority.'. Memount menautkan sistem resmi dan tidak menjual izin.'];
            }

            $buka = $this->pengingat->bukaIzin($trip, $aturan);

            if ($keadaan === PermitService::BOOKING_BELUM_DIBUKA && ! $trip->reminders_enabled && $this->pengingat->dapatDiatur($trip) && $buka?->isFuture()) {
                return ['jenis' => 'pengingat', 'label' => 'Ingatkan saat dibuka', 'href' => route('trips.reminders.update', $trip), 'keterangan' => 'Menyalakan pengingat surel trip ini, termasuk pada hari pemesanan izin dibuka. Jadwalnya tampil di ruang kerja trip.'];
            }
        }

        if ($kritis['berlaku'] === 0 || $kritis['dikonfirmasi'] < $kritis['berlaku']) {
            return self::tautan('Lanjutkan persiapan', route('trips.preparation', $trip));
        }

        return self::tautan('Buka ruang kerja', route('trips.show', $trip));
    }

    /**
     * Nama jalurnya sudah disebut ringkasan fase, jadi kartunya dibuka dengan pos berikutnya.
     *
     * @param  array{id: int, jalur: string, posBerikutnya: string|null, mulai: string|null, url: string}  $pendakian
     * @return array<string, mixed>
     */
    private function kartuMendaki(array $pendakian): array
    {
        return [
            'jenis' => 'mendaki',
            'posBerikutnya' => $pendakian['posBerikutnya'],
            'aksi' => self::tautan('Buka mode lapangan', $pendakian['url']),
        ];
    }

    /**
     * Ringkasan singkat pendakian yang baru selesai dan ajakan melapor (fase B4). Tautan
     * laporan membawa trip, sehingga laporannya ditautkan ke riwayat ini dan fase B4 selesai.
     *
     * @return array<string, mixed>
     */
    private function kartuLapor(HikingHistory $riwayat): array
    {
        $trip = $riwayat->tripPlan;

        return [
            'jenis' => 'lapor',
            'nama' => $trip->name,
            'jalur' => $trip->trail->name,
            'gunung' => $trip->trail->mountain?->name,
            'hasil' => $riwayat->completion_state->label(),
            'selesai' => $riwayat->completed_at->copy()->setTimezone(Timezone::forTrail($trip->trail))->translatedFormat('D, j M Y'),
            'ringkasanUrl' => route('history.summary', $trip),
            'aksi' => self::tautan('Bagikan laporan kondisi', route('reports.create', ['trail' => $trip->trail_id, 'trip' => $trip->id])),
        ];
    }

    /**
     * @return array{jenis: string, label: string, href: string, keterangan: string|null}
     */
    private static function tautan(string $label, string $href, ?string $keterangan = null): array
    {
        return ['jenis' => 'tautan', 'label' => $label, 'href' => $href, 'keterangan' => $keterangan];
    }

    /**
     * Sisa hari ke tanggal mulai, dihitung dari tanggal hari ini di zona gunungnya; rumus yang
     * sama dengan KepalaTrip::hariMenuju(), dalam angka.
     */
    private static function hariLagi(TripPlan $trip): int
    {
        return (int) Carbon::parse(Carbon::now(Timezone::forTrail($trip->trail))->toDateString())
            ->diffInDays($trip->planned_date->copy()->startOfDay(), false);
    }
}
```

`config/hiking.php`:

1. Ganti:

```php
    |--------------------------------------------------------------------------
    | Simpanan dan Banding Jalur (DK-05, DK-29)
```

dengan:

```php
    |--------------------------------------------------------------------------
    | Beranda (spec v3 bagian 6.1, DK-45)
    |--------------------------------------------------------------------------
    */
    'beranda' => [
        // Fase B4: pendakian yang selesai paling lama sekian hari lalu dan belum dilaporkan
        // mengajak pendakinya berbagi laporan kondisi. Sesudahnya laporan itu kurang berguna
        // bagi pendaki lain, karena kesegaran laporan diukur dari tanggal pendakian (§59).
        'hari_ajakan_lapor' => 7,
    ],

    /*
    |--------------------------------------------------------------------------
    | Simpanan dan Banding Jalur (DK-05, DK-29)
```

Tulis ulang `resources/js/pages/Dashboard.tsx` utuh:

```tsx
import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { KartuFase } from '@/components/beranda/KartuFase';
import { sapaan } from '@/components/beranda/sapaan';
import type { FaseBeranda } from '@/components/beranda/types';
import { PageHeader } from '@/components/ui/PageHeader';
import { Tautan } from '@/components/ui/Tautan';
import { AppLayout } from '@/layouts/AppLayout';
import { TombolPengingat } from '@/layouts/Pengingat';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    fase: FaseBeranda;
}

/**
 * Beranda berfase (spec v3 bagian 6.1, DK-45, prototype 01). Server memilih fase dan menyusun
 * isinya (App\Services\Dashboard\RingkasanDasbor); halaman ini hanya memilih tampilan dari
 * fasenya dan tidak memuat aturan domain. Sapaan waktu dihitung dari jam perangkat.
 */
export default function Dashboard() {
    const { auth, fase } = usePage<Props>().props;

    return (
        <div className="py-6 lg:py-8">
            <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <PageHeader akar merek aksi={<TombolPengingat />} title={`${sapaan(new Date())}, ${auth.user?.name ?? ''}`} description={fase.ringkasan} />

                <div className="space-y-8">
                    <div className="space-y-4">
                        <KartuFase kartu={fase.kartu} />
                        {fase.tripLewat ? (
                            <div className="text-kecil text-secondary">
                                <p>
                                    Rencana <span className="font-bold text-primary">{fase.tripLewat.nama}</span> ({fase.tripLewat.tanggal}) sudah lewat tanggalnya
                                    tanpa dimulai.
                                </p>
                                <Tautan
                                    href={fase.tripLewat.url}
                                    className="inline-flex min-h-11 items-center font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                                >
                                    Ubah tanggal atau batalkan
                                </Tautan>
                            </div>
                        ) : null}
                    </div>
                </div>

                <p className="mt-8 border-t border-subtle pt-4 text-kecil text-secondary">
                    Sistem ini membantu pengambilan keputusan pendakian. Sistem tidak menyatakan bahwa suatu gunung atau jalur aman, dan tidak menggantikan
                    informasi resmi dari pengelola.
                </p>
            </div>
        </div>
    );
}

Dashboard.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
```

`tests/Pratinjau/HalamanPratinjauTest.php`:

1. Ganti:

```php
namespace Tests\Pratinjau;

use App\Enums\HikingSessionStatus;
```

dengan:

```php
namespace Tests\Pratinjau;

use App\Enums\CompletionState;
use App\Enums\HikingSessionStatus;
```

2. Ganti:

```php
use App\Models\Checkpoint;
use App\Models\HikingSession;
```

dengan:

```php
use App\Models\Checkpoint;
use App\Models\HikingHistory;
use App\Models\HikingSession;
```

3. Ganti:

```php
use App\Services\Trails\TautanBagikan;
use Illuminate\Support\Facades\Route;
```

dengan:

```php
use App\Services\Trails\TautanBagikan;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Route;
```

4. Ganti:

```php
 * saat Budi sedang mendaki supaya strip pendakian aktif ikut terpotret.
 */
```

dengan:

```php
 * saat Budi sedang mendaki supaya strip pendakian aktif ikut terpotret.
 *
 * Diperluas W5: Beranda di kelima fasenya (B0 sampai B4, spec v3 bagian 6.1).
 */
```

5. Ganti:

```php
    }

    public function test_dasbor(): void
    {
        $this->sebagaiBudi();

        $this->tulisPratinjau('dasbor', $this->get(self::ASAL.'/dashboard'), 'Dashboard');
    }
```

dengan:

```php
    }

    /**
     * Beranda fase B2 (acuan 01): trip camping Budi ke Gede via Cibodas lima belas hari lagi,
     * daftar persiapannya dibangkitkan dengan tiga butir kritis pertama dikonfirmasi.
     */
    public function test_dasbor(): void
    {
        $budi = $this->sebagaiBudi();
        $trip = TripPlan::where('user_id', $budi->id)->where('status', TripStatus::PLANNED->value)->sole();
        $butir = app(PreparationService::class)->generateFor($trip);
        $butir->where('is_critical', true)->take(3)->each(fn ($satu) => $satu->update(['status' => PreparationStatus::CONFIRMED->value]));

        $this->tulisPratinjau('dasbor', $this->get(self::ASAL.'/dashboard'), 'Dashboard');
    }

    /**
     * Beranda fase B0: akun baru yang belum mengisi profil pendaki.
     */
    public function test_dasbor_b0(): void
    {
        $this->seed(DemoSeeder::class);
        $this->actingAs(User::factory()->create(['name' => 'Budi Setiawan']));

        $this->tulisPratinjau('dasbor-b0', $this->get(self::ASAL.'/dashboard'), 'Dashboard');
    }

    /**
     * Beranda fase B1: tanggal trip Gede Budi sudah lewat tiga hari tanpa dimulai, jadi
     * Beranda mengajak merencanakan dan menyebut rencana yang lewat itu (W4-1c).
     */
    public function test_dasbor_b1(): void
    {
        $budi = $this->sebagaiBudi();
        $trip = TripPlan::where('user_id', $budi->id)->where('status', TripStatus::PLANNED->value)->sole();
        $trip->update(['planned_date' => now('Asia/Jakarta')->subDays(3)->toDateString(), 'end_date' => now('Asia/Jakarta')->subDays(2)->toDateString()]);

        $this->tulisPratinjau('dasbor-b1', $this->get(self::ASAL.'/dashboard'), 'Dashboard');
    }

    /**
     * Beranda fase B3: Budi sedang mendaki Gede via Cibodas, sesinya menuju pos kedua.
     */
    public function test_dasbor_b3(): void
    {
        $budi = $this->sebagaiBudi();
        $trip = TripPlan::where('user_id', $budi->id)->where('status', TripStatus::PLANNED->value)->sole();
        $trip->update(['status' => TripStatus::IN_PROGRESS->value]);

        HikingSession::create([
            'trip_plan_id' => $trip->id,
            'user_id' => $budi->id,
            'current_checkpoint_id' => Checkpoint::where('trail_id', $trip->trail_id)->where('sequence', 2)->value('id'),
            'status' => HikingSessionStatus::ACTIVE->value,
            'started_at' => now()->subMinutes(135),
        ]);

        $this->tulisPratinjau('dasbor-b3', $this->get(self::ASAL.'/dashboard'), 'Dashboard');
    }

    /**
     * Beranda fase B4: pendakian Gede Budi selesai dua hari lalu dan belum dilaporkan.
     */
    public function test_dasbor_b4(): void
    {
        $budi = $this->sebagaiBudi();
        $trip = TripPlan::where('user_id', $budi->id)->where('status', TripStatus::PLANNED->value)->sole();
        $selesai = now()->subDays(2);
        $trip->update([
            'planned_date' => $selesai->copy()->subDay()->toDateString(),
            'end_date' => $selesai->toDateString(),
            'status' => TripStatus::COMPLETED->value,
            'completed_at' => $selesai,
        ]);

        HikingHistory::create([
            'user_id' => $budi->id,
            'trip_plan_id' => $trip->id,
            'trail_id' => $trip->trail_id,
            'trip_type' => $trip->trip_type->value,
            'completion_state' => CompletionState::COMPLETED->value,
            'completed_at' => $selesai,
        ]);

        $this->tulisPratinjau('dasbor-b4', $this->get(self::ASAL.'/dashboard'), 'Dashboard');
    }
```

- [ ] **Step 4: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Http/Controllers/DashboardController.php app/Services/Dashboard/KonteksBeranda.php app/Services/Dashboard/RingkasanDasbor.php config/hiking.php tests/Feature/DashboardPhaseTest.php tests/Feature/DashboardRelevanceTest.php tests/Feature/IndonesianInterfaceTest.php tests/Feature/StaleReadinessTest.php tests/Pratinjau/HalamanPratinjauTest.php`, lalu `php artisan test --compact --filter='DashboardPhase|DashboardRelevance|StaleReadiness|IndonesianInterface|ReminderSheet|ActiveHikeStrip|PageHeaderContract|NoDeadViewData|EmptyStateCoverage|TapTarget|TrustGrammar|KeyboardAndScreenReader'`
Expected: PASS. Pada simulasi rencana: 112 test lulus.

- [ ] **Step 5: Suite, tipe, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 1.946 test, 1.944 lulus, 2 dilewati.

Run: `npx tsc --noEmit`
Expected: keluar tanpa keluaran galat.

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `php artisan test --compact --testsuite=Pratinjau`
Expected: build `✓ built`; Pratinjau lulus termasuk `test_dasbor`, `test_dasbor_b0`, `test_dasbor_b1`, `test_dasbor_b3`, dan `test_dasbor_b4`. Pada simulasi rencana: 22 test lulus (dasar 18).

- [ ] **Step 6: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 3: Tenggat terdekat

Spec bagian 6.1 ("Tenggat terdekat"), prototype 01; induk W5 butir 2; ruling W5-R13. Kalimat jendela pemesanan izin
satu trip diekstrak dari `DaftarPengingat` ke `Trips\TenggatIzin`, yang kini dibaca lembar Pengingat dan Beranda;
kalimat lembar Pengingat tidak berubah. `TenggatTrip` menyusun baris tenggat trip B2 dari yang terdekat: jendela
izin, hari prakiraan BMKG untuk tanggal mulai terbit, dan surel pengingat yang menunggu, dalam zona gunung.

**Files:**
- Create: `app/Services/Dashboard/TenggatTrip.php`, `app/Services/Trips/TenggatIzin.php`, `resources/js/components/beranda/TenggatTerdekat.tsx`
- Modify: `app/Http/Controllers/DashboardController.php`, `app/Services/Dashboard/KonteksBeranda.php`, `app/Services/Dashboard/RingkasanDasbor.php`, `app/Services/Trips/DaftarPengingat.php`, `resources/js/components/beranda/types.ts`, `resources/js/pages/Dashboard.tsx`
- Test: `tests/Feature/DashboardDeadlineTest.php` (baru)

**Interfaces:**
- Consumes: `KonteksBeranda::$trip` dan `$aturanIzin` (Tugas 2; relasi `reminders` dimuat sejak tugas ini); `PermitService::bookingWindowFor()` dan `BOOKING_DITUTUP`; `PreparationService::terbitPrakiraan(TripPlan): ?Carbon` dan `izinDiurus()`; `TripReminderScheduler::JENIS_IZIN` dan `label()` (Tugas 1); `TripReminder::MENUNGGU`; `Timezone::forTrail()`, `label()`; `Icon` (`warning`, `question`, `arrow-square-out`), `fokusTerlihat`.
- Produces:
  - `App\Services\Trips\TenggatIzin::untuk(TripPlan $trip, ?PermitRequirement $aturan): ?array{keadaan: string, judul: string, hari: ?int, tanggal: ?Carbon, catatan: ?string, kalimat: string}`;
  - `App\Services\Dashboard\TenggatTrip::untuk(KonteksBeranda $konteks): ?array{zona: string, baris: list<array{kapan: ?array{tanggal, bulan, tahun, lengkap}, judul, keterangan, tautan: ?array{label, href}, peringatan: bool}>}`;
  - konstruktor `DaftarPengingat(PermitService $izin, TenggatIzin $tenggat)`;
  - prop `tenggat` (closure); TypeScript `TenggatBeranda`; komponen `TenggatTerdekat({ tenggat })`.

- [ ] **Step 1: Tulis test yang gagal.**

Buat `tests/Feature/DashboardDeadlineTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\PreparationCategory;
use App\Enums\PreparationStatus;
use App\Enums\TripStatus;
use App\Models\Mountain;
use App\Models\PermitRequirement;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Models\User;
use App\Services\PreparationService;
use App\Services\Trips\TripReminderScheduler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Tenggat terdekat di Beranda (spec v3 bagian 6.1, prototype 01, rencana induk W5 butir 2):
 * jendela pemesanan izin, hari prakiraan BMKG untuk tanggal mulai terbit, dan pengingat surel,
 * dari yang terdekat, dalam zona gunungnya. Waktu dibekukan pada Jumat 25 September 2026
 * pukul 11.00 WITA.
 */
class DashboardDeadlineTest extends TestCase
{
    use RefreshDatabase;

    private const PENGELOLA = 'Balai Taman Nasional Gunung Rinjani';

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-09-25 03:00:00', 'UTC'));
    }

    /**
     * @param  array<string, mixed>|null  $izin  aturan izin tingkat gunung; null tanpa aturan
     */
    private function trip(string $tanggal, ?array $izin = null): TripPlan
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::INTERMEDIATE->value, 'completed_at' => now()]);
        $gunung = Mountain::factory()->create(['name' => 'Gunung Rinjani', 'timezone' => 'Asia/Makassar']);
        $jalur = Trail::factory()->for($gunung)->published()->create(['name' => 'Rinjani via Sembalun']);

        if ($izin !== null) {
            PermitRequirement::factory()->create(array_merge(['mountain_id' => $gunung->id, 'authority' => self::PENGELOLA, 'booking_url' => 'https://contoh.test/erinjani'], $izin));
        }

        return TripPlan::factory()->for($user->fresh())->for($jalur)->create([
            'name' => 'Rinjani bersama Dimas',
            'planned_date' => $tanggal,
            'status' => TripStatus::PLANNED->value,
        ]);
    }

    private function nyalakanPengingat(TripPlan $trip): void
    {
        $trip->update(['reminders_enabled' => true]);
        app(TripReminderScheduler::class)->sinkronkan($trip->fresh());
    }

    /**
     * @return array<string, mixed>|null
     */
    private function tenggat(TripPlan $trip): ?array
    {
        return $this->actingAs($trip->user)->get(route('dashboard'))->assertOk()->viewData('page')['props']['tenggat'];
    }

    /**
     * Prakiraan BMKG terbit di awal Rabu 7 Oktober, sebelum surel H-3 pukul 07.00; pemesanan
     * izin ditutup H-2 (Kamis) jatuh di antara dua surel. Semua dalam WITA.
     */
    public function test_the_deadlines_are_listed_nearest_first_in_the_mountains_zone(): void
    {
        $trip = $this->trip('2026-10-10', ['booking_opens_days_before' => 30, 'booking_closes_days_before' => 2]);
        $this->nyalakanPengingat($trip);

        $tenggat = $this->tenggat($trip);
        $baris = $tenggat['baris'];

        $this->assertSame('WITA', $tenggat['zona']);
        $this->assertSame([
            ['Prakiraan BMKG untuk 10 Okt mulai terbit', 'Rabu, 7 Oktober 2026'],
            ['Pengingat surel pukul 07.00', 'Rabu, 7 Oktober 2026'],
            ['Pemesanan izin ditutup (H-2)', 'Kamis, 8 Oktober 2026'],
            ['Pengingat surel pukul 07.00', 'Jumat, 9 Oktober 2026'],
        ], array_map(fn (array $b) => [$b['judul'], $b['kapan']['lengkap']], $baris));
        $this->assertSame(['tanggal' => '8', 'bulan' => 'Okt', 'tahun' => null, 'lengkap' => 'Kamis, 8 Oktober 2026'], $baris[2]['kapan']);
        $this->assertSame('Izin dipesan di sistem resmi '.self::PENGELOLA.'. Memount menautkan sistem resmi dan tidak menjual izin.', $baris[2]['keterangan']);
        $this->assertSame(['label' => 'Buka sistem resmi', 'href' => 'https://contoh.test/erinjani'], $baris[2]['tautan']);
        $this->assertSame('BMKG menerbitkan prakiraan untuk 3 hari ke depan. Sebelum itu belum ada prakiraan untuk tanggal Anda, dan Memount tidak menebaknya.', $baris[0]['keterangan']);
        $this->assertSame(['3 hari sebelum berangkat.', 'Sehari sebelum berangkat.'], [$baris[1]['keterangan'], $baris[3]['keterangan']]);
    }

    /**
     * Pemesanan yang belum dibuka menyebut hari bukanya; pengingat izin tidak menjadi baris
     * sendiri melainkan keterangan hari itu.
     */
    public function test_a_window_not_yet_open_names_its_opening_day_and_the_reminder_hour(): void
    {
        $trip = $this->trip('2026-11-20', ['booking_opens_days_before' => 30, 'booking_closes_days_before' => 3]);
        $this->nyalakanPengingat($trip);

        $baris = $this->tenggat($trip)['baris'];

        $this->assertSame(['Pemesanan izin dibuka (H-30)', 'Rabu, 21 Oktober 2026'], [$baris[0]['judul'], $baris[0]['kapan']['lengkap']]);
        $this->assertStringEndsWith('Pengingat surel dikirim pukul 07.00 hari itu.', $baris[0]['keterangan']);
        $this->assertCount(4, $baris);
    }

    public function test_a_permit_already_handled_drops_its_deadline(): void
    {
        $trip = $this->trip('2026-10-10', ['booking_opens_days_before' => 30, 'booking_closes_days_before' => 2]);
        TripPreparationItem::create([
            'trip_plan_id' => $trip->id,
            'category' => PreparationCategory::LOGISTICS->value,
            'label' => PreparationService::PERMIT_LABEL,
            'is_critical' => true,
            'status' => PreparationStatus::CONFIRMED->value,
        ]);

        $this->assertSame(['Prakiraan BMKG untuk 10 Okt mulai terbit'], array_column($this->tenggat($trip)['baris'], 'judul'));
    }

    /**
     * Pemesanan yang sudah ditutup sementara butir izinnya belum dikonfirmasi tetap ditulis,
     * sebagai peringatan; prakiraan yang sudah terbit tidak lagi menjadi tenggat.
     */
    public function test_a_closed_window_without_a_confirmed_permit_is_a_warning(): void
    {
        $trip = $this->trip('2026-09-27', ['booking_opens_days_before' => 30, 'booking_closes_days_before' => 3]);

        $baris = $this->tenggat($trip)['baris'];

        $this->assertCount(1, $baris);
        $this->assertSame(['Pemesanan izin sudah ditutup (H-3)', 'Kamis, 24 September 2026', true], [$baris[0]['judul'], $baris[0]['kapan']['lengkap'], $baris[0]['peringatan']]);
        $this->assertSame('Butir izin di persiapan belum dikonfirmasi. Bila izin belum dipesan, tanyakan langsung ke '.self::PENGELOLA.'.', $baris[0]['keterangan']);
    }

    /**
     * R-007: aturan izin tanpa jendela yang tercatat tetap disebut, tanpa tanggal, di akhir
     * daftar; tidak ditulis sedang dibuka.
     */
    public function test_a_window_never_recorded_is_named_without_a_date(): void
    {
        $trip = $this->trip('2026-10-10', ['booking_opens_days_before' => null, 'booking_closes_days_before' => null]);

        $akhir = $this->tenggat($trip)['baris'][1];

        $this->assertSame(['Jendela pemesanan izin belum tercatat', null], [$akhir['judul'], $akhir['kapan']]);
        $this->assertStringStartsWith('Periksa jendelanya di sistem resmi.', $akhir['keterangan']);
    }

    public function test_the_year_is_written_when_it_is_not_this_year(): void
    {
        $kapan = $this->tenggat($this->trip('2027-01-07'))['baris'][0]['kapan'];

        $this->assertSame(['tanggal' => '4', 'bulan' => 'Jan', 'tahun' => '2027', 'lengkap' => 'Senin, 4 Januari 2027'], $kapan);
    }

    /**
     * Penanda kalender hanya visual; pembaca layar mendapat tanggal lengkapnya, dan zona disebut
     * sekali di samping judul bagian.
     */
    public function test_the_calendar_marker_is_visual_and_the_full_date_is_read_aloud(): void
    {
        $sumber = File::get(resource_path('js/components/beranda/TenggatTerdekat.tsx'));

        $this->assertStringContainsString('{b.kapan ? <p className="sr-only">{b.kapan.lengkap}</p> : null}', $sumber);
        $this->assertSame(1, substr_count($sumber, 'aria-hidden="true"'));
        $this->assertStringContainsString('Waktu gunung, {tenggat.zona}', $sumber);
    }

    /**
     * Trip tanpa aturan izin, tanpa pengingat, dan yang prakiraannya sudah terbit tidak punya
     * tenggat; tanpa trip mendatang juga tidak.
     */
    public function test_deadlines_belong_to_an_upcoming_trip_only(): void
    {
        $trip = $this->trip('2026-09-27');

        $this->assertNull($this->tenggat($trip));

        $trip->update(['status' => TripStatus::CANCELLED->value, 'planned_date' => '2026-10-10']);

        $this->assertNull($this->tenggat($trip));
    }
}
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='DashboardDeadline|DashboardPhase|ReminderSheet|TimestampZone|PermitWindowReminder|NoDeadViewData'`
Expected: FAIL. Pada simulasi rencana: 55 test, 47 lulus, 8 error: kedelapan test `DashboardDeadlineTest` (prop `tenggat` dan `TenggatTerdekat.tsx` belum ada). `ReminderSheetTest`, `TimestampZoneTest`, dan `PermitWindowReminderTest` tetap lulus.

- [ ] **Step 3: Tulis implementasinya.** Kelas PHP baru dibuat dengan `make:class` lalu isinya diganti persis.

Buat `app/Services/Dashboard/TenggatTrip.php`:

```php
<?php

namespace App\Services\Dashboard;

use App\Models\PermitRequirement;
use App\Models\TripPlan;
use App\Models\TripReminder;
use App\Services\PermitService;
use App\Services\PreparationService;
use App\Services\Trips\TenggatIzin;
use App\Services\Trips\TripReminderScheduler;
use App\Support\Timezone;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Tenggat terdekat trip mendatang di Beranda (spec v3 bagian 6.1, fase B2, prototype 01;
 * rencana induk W5 butir 2): jendela pemesanan izin (TenggatIzin, sumber yang sama dengan
 * lembar Pengingat), hari prakiraan BMKG untuk tanggal mulai terbit, dan pengingat surel yang
 * masih menunggu, dari yang paling dekat.
 *
 * Hari dan jam menurut zona gunungnya; penandanya (WIB, WITA, atau WIT) dikirim sekali untuk
 * seluruh daftar. Tahun ditulis bila berbeda dari tahun berjalan (W3-1g). Tenggat izin hilang
 * begitu butir izin di persiapan dikonfirmasi atau ditandai tidak berlaku, dan pengingat hari
 * pemesanan dibuka ditulis di baris tenggat itu, bukan sebagai baris sendiri. Jendela yang
 * tidak tercatat tetap disebut, tanpa tanggal, di akhir daftar (R-007).
 */
final class TenggatTrip
{
    public function __construct(private readonly TenggatIzin $tenggatIzin) {}

    /**
     * @return array{zona: string, baris: list<array{kapan: array{tanggal: string, bulan: string, tahun: string|null, lengkap: string}|null, judul: string, keterangan: string, tautan: array{label: string, href: string}|null, peringatan: bool}>}|null
     */
    public function untuk(KonteksBeranda $konteks): ?array
    {
        $trip = $konteks->trip;

        if ($trip === null) {
            return null;
        }

        $zona = Timezone::forTrail($trip->trail);
        $tenggat = [...$this->izin($trip, $konteks->aturanIzin, $zona), ...$this->prakiraan($trip, $zona), ...$this->pengingat($trip, $zona)];

        if ($tenggat === []) {
            return null;
        }

        usort($tenggat, fn (array $a, array $b) => $a['urutan'] <=> $b['urutan']);

        return [
            'zona' => Timezone::label($zona),
            'baris' => array_map(fn (array $t) => array_diff_key($t, ['urutan' => true]), $tenggat),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function izin(TripPlan $trip, ?PermitRequirement $aturan, string $zona): array
    {
        $jendela = $aturan === null || PreparationService::izinDiurus($trip) ? null : $this->tenggatIzin->untuk($trip, $aturan);

        if ($jendela === null) {
            return [];
        }

        $resmi = 'Izin dipesan di sistem resmi '.$aturan->authority.'. Memount menautkan sistem resmi dan tidak menjual izin.';
        $surel = $trip->reminders->first(fn (TripReminder $p) => $p->jenis === TripReminderScheduler::JENIS_IZIN && $p->keadaan() === TripReminder::MENUNGGU);
        $ditutup = $jendela['keadaan'] === PermitService::BOOKING_DITUTUP;
        $hari = $jendela['tanggal'] === null ? null : CarbonImmutable::parse($jendela['tanggal']->toDateString(), $zona);

        return [[
            'urutan' => $hari?->getTimestamp() ?? PHP_INT_MAX,
            'kapan' => $hari === null ? null : self::kapan($hari, $zona),
            'judul' => $jendela['hari'] === null ? $jendela['judul'] : $jendela['judul'].' (H-'.$jendela['hari'].')',
            'keterangan' => match (true) {
                $ditutup => 'Butir izin di persiapan belum dikonfirmasi. Bila izin belum dipesan, tanyakan langsung ke '.$aturan->authority.'.',
                $jendela['catatan'] !== null => $jendela['catatan'].' '.$resmi,
                $surel !== null => $resmi.' Pengingat surel dikirim pukul '.$surel->dijadwalkan_untuk->copy()->setTimezone($zona)->format('H.i').' hari itu.',
                default => $resmi,
            },
            'tautan' => $aturan->booking_url ? ['label' => 'Buka sistem resmi', 'href' => $aturan->booking_url] : null,
            'peringatan' => $ditutup,
        ]];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function prakiraan(TripPlan $trip, string $zona): array
    {
        $terbit = PreparationService::terbitPrakiraan($trip);

        if ($terbit === null) {
            return [];
        }

        $hari = CarbonImmutable::parse($terbit->toDateString(), $zona);

        return [[
            'urutan' => $hari->getTimestamp(),
            'kapan' => self::kapan($hari, $zona),
            'judul' => 'Prakiraan BMKG untuk '.$trip->planned_date->translatedFormat('j M').' mulai terbit',
            'keterangan' => sprintf(
                'BMKG menerbitkan prakiraan untuk %d hari ke depan. Sebelum itu belum ada prakiraan untuk tanggal Anda, dan Memount tidak menebaknya.',
                (int) config('hiking.conditions.weather_forecast_days'),
            ),
            'tautan' => null,
            'peringatan' => false,
        ]];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function pengingat(TripPlan $trip, string $zona): array
    {
        return $trip->reminders
            ->filter(fn (TripReminder $p) => $p->jenis !== TripReminderScheduler::JENIS_IZIN && $p->keadaan() === TripReminder::MENUNGGU)
            ->map(function (TripReminder $p) use ($zona): array {
                $saat = CarbonImmutable::instance($p->dijadwalkan_untuk)->setTimezone($zona);

                return [
                    'urutan' => $saat->getTimestamp(),
                    'kapan' => self::kapan($saat, $zona),
                    'judul' => 'Pengingat surel pukul '.$saat->format('H.i'),
                    'keterangan' => TripReminderScheduler::label($p->jenis).'.',
                    'tautan' => null,
                    'peringatan' => false,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Tanggal untuk penanda kalender (tanggal dan bulan; tahun bila bukan tahun berjalan) dan
     * kalimat lengkapnya untuk pembaca layar.
     *
     * @return array{tanggal: string, bulan: string, tahun: string|null, lengkap: string}
     */
    private static function kapan(CarbonInterface $hari, string $zona): array
    {
        $setempat = CarbonImmutable::instance($hari)->setTimezone($zona);

        return [
            'tanggal' => $setempat->format('j'),
            'bulan' => $setempat->translatedFormat('M'),
            'tahun' => $setempat->year === CarbonImmutable::now($zona)->year ? null : $setempat->format('Y'),
            'lengkap' => $setempat->translatedFormat('l, j F Y'),
        ];
    }
}
```

Buat `app/Services/Trips/TenggatIzin.php`:

```php
<?php

namespace App\Services\Trips;

use App\Models\PermitRequirement;
use App\Models\TripPlan;
use App\Services\PermitService;
use Illuminate\Support\Carbon;

/**
 * Tenggat jendela pemesanan izin satu trip. Lembar Pengingat (DaftarPengingat) dan Tenggat
 * terdekat Beranda (TenggatTrip, W5) membaca judul, tanggal, dan kalimat yang sama dari sini,
 * supaya keduanya tidak pernah berbeda. Aritmetika jendelanya tetap milik PermitService; di
 * sini hanya dipilih tanggal mana yang disebut. Batas yang tidak tercatat disebut belum
 * tercatat, tidak ditebak (R-007).
 */
final class TenggatIzin
{
    private const FORMAT_TANGGAL = 'l, d M Y';

    public function __construct(private readonly PermitService $izin) {}

    /**
     * Null bila tidak ada aturan izin yang tercatat atau tanggal tripnya sudah lewat. Tanpa
     * tanggal, catatan menyebut apa yang belum tercatat.
     *
     * @return array{keadaan: string, judul: string, hari: int|null, tanggal: Carbon|null, catatan: string|null, kalimat: string}|null
     */
    public function untuk(TripPlan $trip, ?PermitRequirement $aturan): ?array
    {
        if ($trip->trail === null || $aturan === null) {
            return null;
        }

        $jendela = $this->izin->bookingWindowFor($trip->trail, $trip->planned_date, $aturan);

        if ($jendela === null) {
            return null;
        }

        $buka = $aturan->booking_opens_days_before;
        $tutup = $aturan->booking_closes_days_before;

        [$judul, $hari, $catatan] = match (true) {
            $jendela['state'] === PermitService::BOOKING_BELUM_DIBUKA && $buka !== null => ['Pemesanan izin dibuka', $buka, null],
            $jendela['state'] === PermitService::BOOKING_DITUTUP && $tutup !== null => ['Pemesanan izin sudah ditutup', $tutup, null],
            $tutup !== null => ['Pemesanan izin ditutup', $tutup, null],
            // Tanggal buka tercatat, sedang berjalan, hanya tutupnya yang belum tercatat:
            // itu fakta yang diketahui, bukan tebakan.
            $buka !== null => ['Pemesanan izin sedang dibuka', null, 'Tanggal penutupannya belum tercatat.'],
            // Tidak ada apa pun yang tercatat: mengklaim "sedang dibuka" di sini adalah
            // klaim tanpa data, bukan sekadar tanggal yang belum tercatat (R-007, Ruling
            // W2-6a #6). PermitService sendiri tidak diubah (diparkir W8, Ruling W2-6c).
            default => ['Jendela pemesanan izin belum tercatat', null, 'Periksa jendelanya di sistem resmi.'],
        };

        $tanggal = $hari === null ? null : $trip->planned_date->copy()->subDays($hari);

        return [
            'keadaan' => $jendela['state'],
            'judul' => $judul,
            'hari' => $hari,
            'tanggal' => $tanggal,
            'catatan' => $catatan,
            'kalimat' => match (true) {
                $tanggal !== null => sprintf('%s %s (H-%d).', $judul, $tanggal->translatedFormat(self::FORMAT_TANGGAL), $hari),
                $buka !== null => $judul.'. '.$catatan,
                default => $judul.'; periksa sistem resmi.',
            },
        ];
    }
}
```

Buat `resources/js/components/beranda/TenggatTerdekat.tsx`:

```tsx
import { fokusTerlihat } from '@/components/ui/gaya';
import { Icon } from '@/components/ui/Icon';
import { kelas } from '@/lib/kelas';
import type { TenggatBeranda } from './types';

/**
 * Tenggat terdekat trip mendatang (spec v3 bagian 6.1, prototype 01): satu baris per tenggat,
 * berurutan waktu, dengan penanda kalender di kiri. Zona gunung disebut sekali di samping judul.
 * Penanda kalender disembunyikan dari pembaca layar, yang mendapat tanggal lengkapnya. Tenggat
 * izin yang lewat tanpa diurus ditandai ikon peringatan dan kalimatnya, bukan warna saja.
 */
export function TenggatTerdekat({ tenggat }: { tenggat: TenggatBeranda }) {
    return (
        <section aria-labelledby="tenggat-terdekat">
            <div className="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                <h2 id="tenggat-terdekat" className="text-bagian text-primary">
                    Tenggat terdekat
                </h2>
                <p className="text-meta text-secondary">Waktu gunung, {tenggat.zona}</p>
            </div>
            <ol className="mt-3 divide-y divide-subtle overflow-hidden rounded-kartu border border-subtle bg-surface">
                {tenggat.baris.map((b) => (
                    <li key={`${b.judul} ${b.kapan?.lengkap ?? ''}`} className="flex gap-3 p-4">
                        <span
                            aria-hidden="true"
                            className={kelas(
                                'flex w-12 shrink-0 flex-col items-center self-start rounded-kecil py-1.5',
                                b.peringatan ? 'bg-warn-50 text-warn-900' : 'bg-surface-sunken text-primary',
                            )}
                        >
                            {b.kapan ? (
                                <>
                                    <span data-angka className="text-bagian leading-none">
                                        {b.kapan.tanggal}
                                    </span>
                                    <span className="text-meta">{b.kapan.bulan}</span>
                                    {b.kapan.tahun ? (
                                        <span data-angka className="text-meta">
                                            {b.kapan.tahun}
                                        </span>
                                    ) : null}
                                </>
                            ) : (
                                <Icon name="question" size={20} />
                            )}
                        </span>
                        <div className="min-w-0">
                            {b.kapan ? <p className="sr-only">{b.kapan.lengkap}</p> : null}
                            <p className={kelas('flex items-start gap-1.5 break-words text-isi font-bold', b.peringatan ? 'text-warn-900' : 'text-primary')}>
                                {b.peringatan ? <Icon name="warning" size={20} className="mt-0.5 shrink-0" /> : null}
                                <span className="min-w-0">{b.judul}</span>
                            </p>
                            <p className="mt-0.5 break-words text-kecil text-secondary">{b.keterangan}</p>
                            {b.tautan ? (
                                <a
                                    href={b.tautan.href}
                                    className={kelas('mt-1 inline-flex min-h-11 items-center gap-1 text-kecil font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900', fokusTerlihat)}
                                >
                                    {b.tautan.label}
                                    <Icon name="arrow-square-out" size={16} />
                                    <span className="sr-only">, membuka situs resmi</span>
                                </a>
                            ) : null}
                        </div>
                    </li>
                ))}
            </ol>
        </section>
    );
}
```

`app/Http/Controllers/DashboardController.php`:

1. Ganti:

```php
use App\Services\Dashboard\RingkasanDasbor;
use Illuminate\Http\Request;
```

dengan:

```php
use App\Services\Dashboard\RingkasanDasbor;
use App\Services\Dashboard\TenggatTrip;
use Illuminate\Http\Request;
```

2. Ganti:

```php
{
    public function __invoke(Request $request, RingkasanDasbor $ringkasan): Response
    {
```

dengan:

```php
{
    public function __invoke(Request $request, RingkasanDasbor $ringkasan, TenggatTrip $tenggat): Response
    {
```

3. Ganti:

```php
            'fase' => fn () => $ringkasan->props($user),
        ]);
```

dengan:

```php
            'fase' => fn () => $ringkasan->props($user),
            'tenggat' => fn () => $tenggat->untuk($ringkasan->konteks($user)),
        ]);
```

`app/Services/Dashboard/KonteksBeranda.php`:

1. Ganti:

```php
 *
 * Relasi yang sudah dimuat: trip (B2) membawa trail.mountain, preparationItems, dan
 * latestReadinessCheck; riwayat (B4) membawa tripPlan.trail.mountain; tripLewat membawa
 * trail.mountain.
 */
```

dengan:

```php
 *
 * Relasi yang sudah dimuat: trip (B2) membawa trail.mountain, preparationItems,
 * latestReadinessCheck, dan reminders; riwayat (B4) membawa tripPlan.trail.mountain; tripLewat
 * membawa trail.mountain.
 */
```

`app/Services/Dashboard/RingkasanDasbor.php`:

1. Ganti:

```php
            ->limit(self::KANDIDAT)
            ->with('trail.mountain', 'preparationItems', 'latestReadinessCheck')
            ->get()
```

dengan:

```php
            ->limit(self::KANDIDAT)
            ->with('trail.mountain', 'preparationItems', 'latestReadinessCheck', 'reminders')
            ->get()
```

`app/Services/Trips/DaftarPengingat.php`:

1. Ganti:

```php
    private const FORMAT_WAKTU = 'l, d M Y H:i';

    private const FORMAT_TANGGAL = 'l, d M Y';

    public function __construct(private PermitService $izin) {}

    /**
```

dengan:

```php
    private const FORMAT_WAKTU = 'l, d M Y H:i';

    public function __construct(private PermitService $izin, private TenggatIzin $tenggat) {}

    /**
```

2. Ganti:

```php
     * Satu kalimat tentang jendela pemesanan izin trip ini, atau null bila tidak ada aturan
     * izin yang tercatat. Aritmetika jendelanya tetap milik PermitService; di sini hanya
     * tanggal yang ditulis. Batas yang tidak tercatat disebut belum tercatat, tidak ditebak.
     *
```

dengan:

```php
     * Satu kalimat tentang jendela pemesanan izin trip ini, atau null bila tidak ada aturan
     * izin yang tercatat. Kalimat dan tanggal patokannya milik TenggatIzin, yang juga dibaca
     * Tenggat terdekat Beranda (W5).
     *
```

3. Ganti:

```php
    {
        if ($trip->trail === null || $aturan === null) {
            return null;
```

dengan:

```php
    {
        $jendela = $this->tenggat->untuk($trip, $aturan);

        if ($jendela === null || $aturan === null) {
            return null;
```

4. Ganti:

```php
        }

        $jendela = $this->izin->bookingWindowFor($trip->trail, $trip->planned_date, $aturan);

        if ($jendela === null) {
            return null;
        }

        $buka = $aturan->booking_opens_days_before;
        $tutup = $aturan->booking_closes_days_before;

        // Kalimat dan tanggal patokan urutan untuk "<awal> <tanggal> (H-<hari>).".
        $tulis = function (string $awal, int $hari) use ($trip): array {
            $tanggal = $trip->planned_date->copy()->subDays($hari);

            return [sprintf('%s %s (H-%d).', $awal, $tanggal->translatedFormat(self::FORMAT_TANGGAL), $hari), $tanggal];
        };

        [$kalimat, $patokan] = match (true) {
            $jendela['state'] === PermitService::BOOKING_BELUM_DIBUKA && $buka !== null => $tulis('Pemesanan izin dibuka', $buka),
            $jendela['state'] === PermitService::BOOKING_DITUTUP && $tutup !== null => $tulis('Pemesanan izin sudah ditutup', $tutup),
            $tutup !== null => $tulis('Pemesanan izin ditutup', $tutup),
            // Tanggal buka tercatat, sedang berjalan, hanya tutupnya yang belum tercatat:
            // itu fakta yang diketahui, bukan tebakan.
            $buka !== null => ['Pemesanan izin sedang dibuka. Tanggal penutupannya belum tercatat.', null],
            // Tidak ada apa pun yang tercatat: mengklaim "sedang dibuka" di sini adalah
            // klaim tanpa data, bukan sekadar tanggal yang belum tercatat (R-007, Ruling
            // W2-6a #6). PermitService sendiri tidak diubah (diparkir W8, Ruling W2-6c).
            default => ['Jendela pemesanan izin belum tercatat; periksa sistem resmi.', null],
        };

        return [
            'urutan' => $patokan?->getTimestamp(),
            'tripId' => $trip->id,
            'trip' => $trip->name,
            'keadaan' => $jendela['state'],
            'kalimat' => $kalimat,
            'pengelola' => $aturan->authority,
```

dengan:

```php
        }

        return [
            'urutan' => $jendela['tanggal']?->getTimestamp(),
            'tripId' => $trip->id,
            'trip' => $trip->name,
            'keadaan' => $jendela['keadaan'],
            'kalimat' => $jendela['kalimat'],
            'pengelola' => $aturan->authority,
```

`resources/js/components/beranda/types.ts`:

1. Ganti:

```ts
    | { jenis: 'lapor'; nama: string; jalur: string; gunung: string | null; hasil: string; selesai: string; ringkasanUrl: string; aksi: AksiFase };

export interface FaseBeranda {
```

dengan:

```ts
    | { jenis: 'lapor'; nama: string; jalur: string; gunung: string | null; hasil: string; selesai: string; ringkasanUrl: string; aksi: AksiFase };

/** Tenggat terdekat trip mendatang (fase B2, App\Services\Dashboard\TenggatTrip), dari yang terdekat. */
export interface TenggatBeranda {
    /** Penanda zona gunung untuk seluruh daftar: WIB, WITA, atau WIT. */
    zona: string;
    baris: Array<{
        /** Penanda kalender dan kalimat lengkapnya; null untuk jendela izin yang belum tercatat. */
        kapan: { tanggal: string; bulan: string; tahun: string | null; lengkap: string } | null;
        judul: string;
        keterangan: string;
        /** Tautan ke sistem resmi pengelola (situs lain). */
        tautan: { label: string; href: string } | null;
        peringatan: boolean;
    }>;
}

export interface FaseBeranda {
```

`resources/js/pages/Dashboard.tsx`:

1. Ganti:

```tsx
import { sapaan } from '@/components/beranda/sapaan';
import type { FaseBeranda } from '@/components/beranda/types';
import { PageHeader } from '@/components/ui/PageHeader';
```

dengan:

```tsx
import { sapaan } from '@/components/beranda/sapaan';
import { TenggatTerdekat } from '@/components/beranda/TenggatTerdekat';
import type { FaseBeranda, TenggatBeranda } from '@/components/beranda/types';
import { PageHeader } from '@/components/ui/PageHeader';
```

2. Ganti:

```tsx
    fase: FaseBeranda;
}
```

dengan:

```tsx
    fase: FaseBeranda;
    tenggat: TenggatBeranda | null;
}
```

3. Ganti:

```tsx
export default function Dashboard() {
    const { auth, fase } = usePage<Props>().props;

    return (
```

dengan:

```tsx
export default function Dashboard() {
    const { auth, fase, tenggat } = usePage<Props>().props;

    return (
```

4. Ganti:

```tsx
                    </div>
                </div>
```

dengan:

```tsx
                    </div>
                    {tenggat ? <TenggatTerdekat tenggat={tenggat} /> : null}
                </div>
```

- [ ] **Step 4: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Http/Controllers/DashboardController.php app/Services/Dashboard/KonteksBeranda.php app/Services/Dashboard/RingkasanDasbor.php app/Services/Dashboard/TenggatTrip.php app/Services/Trips/TenggatIzin.php app/Services/Trips/DaftarPengingat.php tests/Feature/DashboardDeadlineTest.php`, lalu `php artisan test --compact --filter='DashboardDeadline|DashboardPhase|ReminderSheet|TimestampZone|PermitWindowReminder|NoDeadViewData'`
Expected: PASS. Pada simulasi rencana: 55 test lulus.

- [ ] **Step 5: Suite, tipe, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 1.954 test, 1.952 lulus, 2 dilewati.

Run: `npx tsc --noEmit`
Expected: keluar tanpa keluaran galat.

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `php artisan test --compact --testsuite=Pratinjau`
Expected: build `✓ built`; Pratinjau lulus. Pada simulasi rencana: 22 test lulus.

- [ ] **Step 6: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 4: Kabar gunung yang diikuti

Spec bagian 6.1 (B1 "kabar gunung yang diikuti"), prototype 01; induk W5 butir 3; ruling W5-R14 dan W5-R15.
`TrailNewsService::terbaru()` mengambil status dan laporan dengan kueri per jenis; `KabarJalur::ringkas()` memformatnya
sama dengan halaman Kabar; `KabarBeranda` mengirimnya pada B1, B2, dan B4, dengan ajakan mengikuti gunung tujuan trip
B2. Beranda kini merender status resmi, jadi kalimat R-012 ikut di komponennya dan `OfficialStatusSafetySentenceTest`
mewajibkan Beranda terdeteksi sebagai halaman status.

**Files:**
- Create: `app/Services/Dashboard/KabarBeranda.php`, `resources/js/components/beranda/KabarDiikuti.tsx`
- Modify: `app/Http/Controllers/DashboardController.php`, `app/Services/Community/KabarJalur.php`, `app/Services/TrailNewsService.php`, `resources/js/components/beranda/types.ts`, `resources/js/pages/Dashboard.tsx`
- Test: `tests/Feature/TrailNewsTest.php`, `tests/Feature/OfficialStatusSafetySentenceTest.php`, `tests/Pratinjau/HalamanPratinjauTest.php`

**Interfaces:**
- Consumes: `TrailNewsService::followedMountains(User): Collection`, `status()` dan `laporan()` privat; formatter privat `KabarJalur::resmi()` dan `komunitas()`; `KonteksBeranda::$fase`, `$pengguna`, `$trip` (Tugas 2); komponen `KondisiPendaki({ laporan: LaporanGunung[] })`, `LabelSumber({ jenis })`, `StatusAkses({ nilai, label, cakupan })`, `Button`, `Tautan`; TypeScript `ButirResmi` (`components/community/types.ts`) dan `LaporanGunung` (`components/trails/gunung/types.ts`); rute `news` dan `mountains.follow`.
- Produces:
  - `App\Services\TrailNewsService::terbaru(User $user, int $resmi, int $laporan): array{resmi: Collection, laporan: Collection}`; `forUser()` memakai cakupan yang sama;
  - `App\Services\Community\KabarJalur::ringkas(User $user): ?array{gunung: array<int, string>, resmi: array, komunitas: array}` (null tanpa gunung yang diikuti);
  - `App\Services\Dashboard\KabarBeranda::untuk(KonteksBeranda $konteks): ?array{judul, mengikuti, diikuti, resmi, komunitas, ikuti: ?array{gunung, href}}`;
  - prop `kabar` (closure); TypeScript `KabarBerandaData`; komponen `KabarDiikuti({ kabar })`.

- [ ] **Step 1: Tulis test yang gagal.**

`tests/Feature/OfficialStatusSafetySentenceTest.php`:

1. Ganti:

```php
        // pengecualian; kalau tidak, test ini lulus tanpa memeriksa apa pun.
        foreach (['Trails/Index', 'Trails/Show', 'Trails/Compare', 'Trails/Fit', 'Mountains/Show', 'Community/TrailNews', 'Public/SharedComparison', 'Trips/Readiness', ...self::PENGECUALIAN] as $wajib) {
            $this->assertContains($wajib, $halamanStatus, "{$wajib} tidak terdeteksi sebagai halaman status.");
```

dengan:

```php
        // pengecualian; kalau tidak, test ini lulus tanpa memeriksa apa pun.
        foreach (['Trails/Index', 'Trails/Show', 'Trails/Compare', 'Trails/Fit', 'Mountains/Show', 'Community/TrailNews', 'Public/SharedComparison', 'Trips/Readiness', 'Dashboard', ...self::PENGECUALIAN] as $wajib) {
            $this->assertContains($wajib, $halamanStatus, "{$wajib} tidak terdeteksi sebagai halaman status.");
```

`tests/Feature/TrailNewsTest.php`:

1. Ganti:

```php
namespace Tests\Feature;

use App\Enums\ModerationStatus;
```

dengan:

```php
namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\ModerationStatus;
```

2. Ganti:

```php
use App\Enums\StatusScope;
use App\Models\DataSource;
```

dengan:

```php
use App\Enums\StatusScope;
use App\Enums\TripStatus;
use App\Models\DataSource;
```

3. Ganti:

```php
use App\Models\TrailConditionReport;
use App\Models\User;
```

dengan:

```php
use App\Models\TrailConditionReport;
use App\Models\TripPlan;
use App\Models\User;
```

4. Ganti:

```php
    }
}
```

dengan:

```php
    }

    private function pendakiBerprofil(): User
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::INTERMEDIATE->value, 'completed_at' => now()]);

        return $user->fresh();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function kabarBeranda(User $user): ?array
    {
        return $this->actingAs($user)->get(route('dashboard'))->assertOk()->viewData('page')['props']['kabar'];
    }

    /**
     * Ringkasan Beranda (W5) memakai kueri per jenis: laporan komunitas terbaru tetap ada
     * walau status resmi yang lebih baru lebih banyak dari batasnya.
     */
    public function test_the_latest_per_kind_keeps_a_report_that_newer_statuses_would_push_out(): void
    {
        [$gunung, $trail] = $this->gunungBerjalur('Merbabu');
        $user = User::factory()->create();
        $this->ikuti($user, $gunung);
        $this->laporan($trail, ModerationStatus::APPROVED);
        $this->travel(1)->hours();

        foreach (['Longsor di Pos 2.', 'Perbaikan jembatan.', 'Kebakaran lahan.'] as $alasan) {
            $this->tutup($trail, $alasan);
            $this->travel(1)->minutes();
        }

        $terbaru = app(TrailNewsService::class)->terbaru($user, 2, 1);

        $this->assertSame(['Kebakaran lahan.', 'Perbaikan jembatan.'], $terbaru['resmi']->pluck('alasan')->all());
        $this->assertCount(1, $terbaru['laporan']);
        $this->assertSame([TrailNewsService::STATUS, TrailNewsService::STATUS], app(TrailNewsService::class)->forUser($user, 2)->pluck('jenis')->all());
    }

    /**
     * Beranda meringkas kabar gunung yang diikuti dalam dua kelompok terpisah (R-010), dengan
     * kalimat R-012 di samping status resmi dan tautan ke halaman Kabar. Laporan yang belum
     * lolos moderasi tidak ikut.
     */
    public function test_the_home_summarises_followed_mountains_with_official_and_community_apart(): void
    {
        [$gunung, $trail] = $this->gunungBerjalur('Merbabu');
        $user = $this->pendakiBerprofil();
        $this->ikuti($user, $gunung);
        $this->tutup($trail, 'Kebakaran lahan.');
        $this->laporan($trail, ModerationStatus::APPROVED);
        $this->laporan($trail, ModerationStatus::PENDING, 'Belum diperiksa.');

        $kabar = $this->kabarBeranda($user);

        $this->assertSame(['Kabar Merbabu yang Anda ikuti', true, null, null], [$kabar['judul'], $kabar['diikuti'], $kabar['mengikuti'], $kabar['ikuti']]);
        $this->assertSame(['Jalur Merbabu', 'Tutup', 'Kebakaran lahan.'], [$kabar['resmi'][0]['judul'], $kabar['resmi'][0]['statusLabel'], $kabar['resmi'][0]['alasan']]);
        $this->assertSame(['Jalur Merbabu', 'Jembatan Pos 3 putus.'], [$kabar['komunitas'][0]['jalur'], $kabar['komunitas'][0]['catatan']]);
        $this->assertCount(1, $kabar['komunitas']);

        $sumber = File::get(resource_path('js/components/beranda/KabarDiikuti.tsx'));
        $this->assertStringContainsString('<KondisiPendaki laporan={kabar.komunitas} />', $sumber);
        $this->assertStringContainsString('href={news.url()}', $sumber);
    }

    public function test_the_home_names_several_followed_mountains(): void
    {
        [$merbabu] = $this->gunungBerjalur('Merbabu');
        [$sumbing] = $this->gunungBerjalur('Sumbing');
        $user = $this->pendakiBerprofil();
        $this->ikuti($user, $merbabu);
        $this->ikuti($user, $sumbing);

        $kabar = $this->kabarBeranda($user);

        $this->assertSame(['Kabar gunung yang Anda ikuti', 'Merbabu dan Sumbing'], [$kabar['judul'], $kabar['mengikuti']]);
        $this->assertSame([[], []], [$kabar['resmi'], $kabar['komunitas']]);
    }

    /**
     * Trip mendatang menuju gunung yang belum diikuti: Beranda menawarkan mengikutinya, dan
     * sesudah diikuti tawarannya hilang dan kabarnya masuk.
     */
    public function test_the_home_offers_to_follow_the_trip_mountain(): void
    {
        [$gunung, $trail] = $this->gunungBerjalur('Merbabu');
        $user = $this->pendakiBerprofil();
        TripPlan::factory()->for($user)->for($trail)->create(['planned_date' => now()->addDays(10)->toDateString(), 'status' => TripStatus::PLANNED->value]);

        $kabar = $this->kabarBeranda($user);

        $this->assertFalse($kabar['diikuti']);
        $this->assertSame(['gunung' => 'Merbabu', 'href' => route('mountains.follow', $gunung)], $kabar['ikuti']);

        $this->actingAs($user)->from(route('dashboard'))->post($kabar['ikuti']['href'], ['ya' => true])->assertRedirect(route('dashboard'));

        $sesudah = $this->kabarBeranda($user);
        $this->assertSame([true, null], [$sesudah['diikuti'], $sesudah['ikuti']]);
    }

    /**
     * Sebelum profil terisi Beranda punya satu fokus, mengisi profil; selama pendakian berjalan,
     * mode lapangan. Kabar tidak dikirim pada kedua fase itu.
     */
    public function test_the_home_leaves_news_out_before_a_profile_and_during_a_hike(): void
    {
        [$gunung, $trail] = $this->gunungBerjalur('Merbabu');
        $tanpaProfil = User::factory()->create();
        $this->ikuti($tanpaProfil, $gunung);

        $this->assertNull($this->kabarBeranda($tanpaProfil));

        $mendaki = $this->pendakiBerprofil();
        $this->ikuti($mendaki, $gunung);
        TripPlan::factory()->for($mendaki)->for($trail)->create(['planned_date' => now()->toDateString(), 'status' => TripStatus::IN_PROGRESS->value]);

        $this->assertNull($this->kabarBeranda($mendaki));
    }
}
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='TrailNews|OfficialStatusSafetySentence|DashboardPhase|NoDeadViewData|ReminderSheet'`
Expected: FAIL. Pada simulasi rencana: 60 test, 54 lulus, 1 gagal, 5 error. Yang merah: lima test baru `TrailNewsTest` (`terbaru()` dan prop `kabar` belum ada) dan `OfficialStatusSafetySentenceTest::test_every_reader_page_that_shows_an_official_status_says_it_is_not_a_safety_guarantee` ("Dashboard tidak terdeteksi sebagai halaman status").

- [ ] **Step 3: Tulis implementasinya.**

Buat `app/Services/Dashboard/KabarBeranda.php`:

```php
<?php

namespace App\Services\Dashboard;

use App\Services\Community\KabarJalur;

/**
 * Kabar gunung yang diikuti di Beranda (spec v3 bagian 6.1, prototype 01; rencana induk W5
 * butir 3): ringkasan KabarJalur, status resmi dan laporan komunitas dalam dua kelompok yang
 * terpisah (R-010). Tidak dikirim sebelum profil terisi (B0: satu fokus, profil) dan selama
 * pendakian berjalan (B3: satu fokus, mode lapangan).
 *
 * Selama trip mendatang (B2) menuju gunung yang belum diikuti, Beranda menawarkan mengikutinya:
 * penutupan diumumkan untuk kawasan, dan tujuan trip adalah kawasan yang paling perlu diikuti.
 * Laporan komunitas dikirim dalam bentuk kartu laporan halaman gunung (KondisiPendaki), supaya
 * lapisan komunitas tampil sama di kedua tempat.
 */
final class KabarBeranda
{
    public function __construct(private readonly KabarJalur $kabar) {}

    /**
     * @return array{judul: string, mengikuti: string|null, diikuti: bool, resmi: array<int, array<string, mixed>>, komunitas: array<int, array<string, mixed>>, ikuti: array{gunung: string, href: string}|null}|null
     */
    public function untuk(KonteksBeranda $konteks): ?array
    {
        if (in_array($konteks->fase, [RingkasanDasbor::B0, RingkasanDasbor::B3], true)) {
            return null;
        }

        $ringkas = $this->kabar->ringkas($konteks->pengguna);
        $gunung = $ringkas['gunung'] ?? [];
        $tujuan = $konteks->trip?->trail->mountain;

        return [
            'judul' => count($gunung) === 1 ? 'Kabar '.reset($gunung).' yang Anda ikuti' : 'Kabar gunung yang Anda ikuti',
            'mengikuti' => count($gunung) > 1 ? collect($gunung)->join(', ', ' dan ') : null,
            'diikuti' => $ringkas !== null,
            'resmi' => $ringkas['resmi'] ?? [],
            'komunitas' => array_map(fn (array $b) => [
                'id' => $b['id'],
                'jalur' => $b['judul'],
                'tags' => $b['tag'],
                'catatan' => $b['catatan'],
                'dilaporkan' => $b['waktu'],
                'tanggalPendakian' => (string) $b['tanggalPendakian'],
                'kesegaran' => $b['kesegaran'],
            ], $ringkas['komunitas'] ?? []),
            'ikuti' => $tujuan !== null && ! array_key_exists($tujuan->id, $gunung)
                ? ['gunung' => $tujuan->name, 'href' => route('mountains.follow', $tujuan)]
                : null,
        ];
    }
}
```

Buat `resources/js/components/beranda/KabarDiikuti.tsx`:

```tsx
import { router } from '@inertiajs/react';
import { useState } from 'react';
import { KondisiPendaki } from '@/components/trails/gunung/KondisiPendaki';
import { LabelSumber } from '@/components/trails/LabelSumber';
import { StatusAkses } from '@/components/trails/StatusAkses';
import { Button } from '@/components/ui/Button';
import { fokusTerlihat } from '@/components/ui/gaya';
import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';
import { news } from '@/routes';
import type { KabarBerandaData } from './types';

/**
 * Kabar gunung yang diikuti (spec v3 bagian 6.1, prototype 01). Status resmi dan laporan
 * komunitas tinggal di dua kelompok (R-009, R-010): catatan bersumber resmi bergaris padat
 * merek, catatan lain bergaris rambut, dan laporan komunitas bergaris putus-putus dengan kartu
 * yang sama seperti halaman gunung, jadi asalnya terbaca dari label dan garis, bukan warna
 * saja. Kalimat R-012 menyertai status resmi. Prakiraan cuaca dan kabar lengkap ada di halaman
 * Kabar.
 */
export function KabarDiikuti({ kabar }: { kabar: KabarBerandaData }) {
    const [memproses, setMemproses] = useState(false);
    const tujuan = kabar.ikuti;

    const ikuti = (href: string) => {
        router.post(href, { ya: true }, { preserveScroll: true, onStart: () => setMemproses(true), onFinish: () => setMemproses(false) });
    };

    return (
        <section aria-labelledby="kabar-diikuti">
            <div className="flex flex-wrap items-baseline justify-between gap-x-4">
                <h2 id="kabar-diikuti" className="text-bagian text-primary">
                    {kabar.judul}
                </h2>
                {kabar.diikuti ? (
                    <Tautan
                        href={news.url()}
                        className={kelas('inline-flex min-h-11 items-center text-kecil font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900', fokusTerlihat)}
                    >
                        Semua kabar
                    </Tautan>
                ) : null}
            </div>
            {kabar.mengikuti ? <p className="text-kecil text-secondary">Mengikuti {kabar.mengikuti}.</p> : null}

            {kabar.diikuti ? (
                <div className="mt-3 space-y-3">
                    {kabar.resmi.length === 0 ? (
                        <p className="max-w-prose text-kecil text-secondary">
                            Belum ada kabar status resmi dari gunung yang Anda ikuti. Tidak adanya kabar bukan pernyataan bahwa jalurnya terbuka.
                        </p>
                    ) : (
                        <>
                            <ul className="space-y-3">
                                {kabar.resmi.map((b) => (
                                    <li
                                        key={b.id}
                                        className={kelas('rounded-lg bg-surface p-4', b.jenisSumber === 'OFFICIAL' ? 'border-[1.5px] border-brand-ink' : 'border border-subtle')}
                                    >
                                        <div className="flex flex-wrap items-center gap-2">
                                            <LabelSumber jenis={b.jenisSumber} />
                                            <span className="break-words text-meta text-secondary">{b.sumber}</span>
                                        </div>
                                        <p className="mt-2 flex flex-wrap items-center gap-x-2">
                                            <StatusAkses nilai={b.status} label={b.statusLabel} cakupan={b.cakupan} />
                                            <span className="break-words text-kecil text-primary">{b.judul}</span>
                                        </p>
                                        {b.alasan ? <p className="mt-1 break-words text-kecil text-secondary">{b.alasan}</p> : null}
                                        <p className="mt-2 text-meta text-muted">
                                            Dicatat <time dateTime={b.waktuIso}>{b.waktu}</time>
                                        </p>
                                    </li>
                                ))}
                            </ul>
                            <p className="text-kecil text-secondary">Status resmi menjelaskan ketentuan pengelola, bukan jaminan keselamatan.</p>
                        </>
                    )}

                    {kabar.komunitas.length === 0 ? (
                        <p className="max-w-prose text-kecil text-secondary">Belum ada laporan kondisi terbaru dari komunitas.</p>
                    ) : (
                        <KondisiPendaki laporan={kabar.komunitas} />
                    )}
                </div>
            ) : (
                <p className="mt-2 max-w-prose text-isi text-secondary">
                    Belum ada gunung yang Anda ikuti. Perubahan status resmi dan laporan kondisi dari gunung yang Anda ikuti muncul di sini, juga ketika Anda sedang
                    tidak merencanakan apa pun.
                </p>
            )}

            {tujuan ? (
                <div className="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2">
                    <p className="text-kecil text-secondary">{tujuan.gunung}, tujuan trip Anda, belum Anda ikuti.</p>
                    <Button variant="secondary" processing={memproses} onClick={() => ikuti(tujuan.href)}>
                        Ikuti {tujuan.gunung}
                    </Button>
                </div>
            ) : null}
        </section>
    );
}
```

`app/Http/Controllers/DashboardController.php`:

1. Ganti:

```php
namespace App\Http\Controllers;

use App\Services\Dashboard\RingkasanDasbor;
```

dengan:

```php
namespace App\Http\Controllers;

use App\Services\Dashboard\KabarBeranda;
use App\Services\Dashboard\RingkasanDasbor;
```

2. Ganti:

```php
{
    public function __invoke(Request $request, RingkasanDasbor $ringkasan, TenggatTrip $tenggat): Response
    {
```

dengan:

```php
{
    public function __invoke(Request $request, RingkasanDasbor $ringkasan, TenggatTrip $tenggat, KabarBeranda $kabar): Response
    {
```

3. Ganti:

```php
            'tenggat' => fn () => $tenggat->untuk($ringkasan->konteks($user)),
        ]);
```

dengan:

```php
            'tenggat' => fn () => $tenggat->untuk($ringkasan->konteks($user)),
            'kabar' => fn () => $kabar->untuk($ringkasan->konteks($user)),
        ]);
```

`app/Services/Community/KabarJalur.php`:

1. Ganti:

```php
{
    public function __construct(
```

dengan:

```php
{
    /** Perubahan status resmi dan laporan komunitas yang diringkas di Beranda. */
    private const RINGKAS_RESMI = 2;

    private const RINGKAS_LAPORAN = 1;

    public function __construct(
```

2. Ganti:

```php
                ->all(),
        ];
```

dengan:

```php
                ->all(),
        ];
    }

    /**
     * Ringkasan Kabar untuk Beranda (spec v3 bagian 6.1, prototype 01): perubahan status resmi
     * terbaru dan laporan komunitas terbaru dari gunung yang diikuti, tetap terpisah menurut
     * asalnya (R-010) dan diformat sama dengan halaman Kabar. Cuaca tidak ikut; ia tinggal di
     * halaman Kabar. Null bila pendaki tidak mengikuti gunung apa pun.
     *
     * @return array{gunung: array<int, string>, resmi: array<int, array<string, mixed>>, komunitas: array<int, array<string, mixed>>}|null
     */
    public function ringkas(User $user): ?array
    {
        $gunung = $this->kabar->followedMountains($user);

        if ($gunung->isEmpty()) {
            return null;
        }

        $terbaru = $this->kabar->terbaru($user, self::RINGKAS_RESMI, self::RINGKAS_LAPORAN);

        return [
            'gunung' => $gunung->pluck('name', 'id')->all(),
            'resmi' => $terbaru['resmi']->map(fn (array $b) => $this->resmi($b))->values()->all(),
            'komunitas' => $terbaru['laporan']->map(fn (array $b) => $this->komunitas($b))->values()->all(),
        ];
```

`app/Services/TrailNewsService.php`:

1. Ganti:

```php
    {
        $gunung = MountainFollow::where('user_id', $user->id)->pluck('mountain_id');

        if ($gunung->isEmpty()) {
            return collect();
```

dengan:

```php
    {
        $cakupan = $this->cakupan($user);

        if ($cakupan === null) {
            return collect();
```

2. Ganti:

```php
        }

        // Hanya jalur terbit (R-022). Draf dan jalur terarsip tidak punya halaman publik,
        // dan status atau laporan tentangnya tidak boleh bocor lewat pintu paling ramai
        // ini. Status yang diumumkan untuk gunungnya sendiri tetap sampai.
        $jalur = Trail::query()->published()->whereIn('mountain_id', $gunung)->pluck('id');

        return $this->status($gunung, $jalur, $limit)
```

dengan:

```php
        }

        [$gunung, $jalur] = $cakupan;

        return $this->status($gunung, $jalur, $limit)
```

3. Ganti:

```php
            ->values();
    }
```

dengan:

```php
            ->values();
    }

    /**
     * Kabar terbaru per jenis untuk ringkasan Beranda (spec v3 bagian 6.1, W5): paling banyak
     * $resmi perubahan status dan $laporan laporan komunitas, masing-masing dari kuerinya
     * sendiri, sehingga laporan tidak terdesak keluar oleh status yang lebih baru.
     *
     * @return array{resmi: Collection<int, array<string, mixed>>, laporan: Collection<int, array<string, mixed>>}
     */
    public function terbaru(User $user, int $resmi, int $laporan): array
    {
        $cakupan = $this->cakupan($user);

        if ($cakupan === null) {
            return ['resmi' => collect(), 'laporan' => collect()];
        }

        [$gunung, $jalur] = $cakupan;

        return ['resmi' => $this->status($gunung, $jalur, $resmi), 'laporan' => $this->laporan($jalur, $laporan)];
    }

    /**
     * Gunung yang diikuti dan jalur terbitnya, atau null bila tidak mengikuti apa pun.
     *
     * Hanya jalur terbit (R-022). Draf dan jalur terarsip tidak punya halaman publik, dan
     * status atau laporan tentangnya tidak boleh bocor lewat pintu paling ramai ini. Status
     * yang diumumkan untuk gunungnya sendiri tetap sampai.
     *
     * @return array{0: Collection<int, int>, 1: Collection<int, int>}|null
     */
    private function cakupan(User $user): ?array
    {
        $gunung = MountainFollow::where('user_id', $user->id)->pluck('mountain_id');

        if ($gunung->isEmpty()) {
            return null;
        }

        return [$gunung, Trail::query()->published()->whereIn('mountain_id', $gunung)->pluck('id')];
    }
```

`resources/js/components/beranda/types.ts`:

1. Ganti:

```ts
import type { KeadaanLangkah } from '@/components/trips/types';
```

dengan:

```ts
import type { ButirResmi } from '@/components/community/types';
import type { LaporanGunung } from '@/components/trails/gunung/types';
import type { KeadaanLangkah } from '@/components/trips/types';
```

2. Ganti:

```ts
}

export interface FaseBeranda {
```

dengan:

```ts
}

/** Kabar gunung yang diikuti (App\Services\Dashboard\KabarBeranda); null pada fase B0 dan B3. */
export interface KabarBerandaData {
    judul: string;
    /** Nama gunung yang diikuti bila lebih dari satu; satu gunung sudah disebut judulnya. */
    mengikuti: string | null;
    diikuti: boolean;
    resmi: ButirResmi[];
    komunitas: LaporanGunung[];
    /** Gunung tujuan trip mendatang yang belum diikuti (fase B2). */
    ikuti: { gunung: string; href: string } | null;
}

export interface FaseBeranda {
```

`resources/js/pages/Dashboard.tsx`:

1. Ganti:

```tsx
import type { ReactNode } from 'react';
import { KartuFase } from '@/components/beranda/KartuFase';
```

dengan:

```tsx
import type { ReactNode } from 'react';
import { KabarDiikuti } from '@/components/beranda/KabarDiikuti';
import { KartuFase } from '@/components/beranda/KartuFase';
```

2. Ganti:

```tsx
import { TenggatTerdekat } from '@/components/beranda/TenggatTerdekat';
import type { FaseBeranda, TenggatBeranda } from '@/components/beranda/types';
import { PageHeader } from '@/components/ui/PageHeader';
```

dengan:

```tsx
import { TenggatTerdekat } from '@/components/beranda/TenggatTerdekat';
import type { FaseBeranda, KabarBerandaData, TenggatBeranda } from '@/components/beranda/types';
import { PageHeader } from '@/components/ui/PageHeader';
```

3. Ganti:

```tsx
    tenggat: TenggatBeranda | null;
}
```

dengan:

```tsx
    tenggat: TenggatBeranda | null;
    kabar: KabarBerandaData | null;
}
```

4. Ganti:

```tsx
export default function Dashboard() {
    const { auth, fase, tenggat } = usePage<Props>().props;

    return (
```

dengan:

```tsx
export default function Dashboard() {
    const { auth, fase, tenggat, kabar } = usePage<Props>().props;

    return (
```

5. Ganti:

```tsx
                    {tenggat ? <TenggatTerdekat tenggat={tenggat} /> : null}
                </div>
```

dengan:

```tsx
                    {tenggat ? <TenggatTerdekat tenggat={tenggat} /> : null}
                    {kabar ? <KabarDiikuti kabar={kabar} /> : null}
                </div>
```

`tests/Pratinjau/HalamanPratinjauTest.php`:

1. Ganti:

```php
use App\Models\HikingSession;
use App\Models\Trail;
```

dengan:

```php
use App\Models\HikingSession;
use App\Models\MountainFollow;
use App\Models\Trail;
```

2. Ganti:

```php
     * Beranda fase B2 (acuan 01): trip camping Budi ke Gede via Cibodas lima belas hari lagi,
     * daftar persiapannya dibangkitkan dengan tiga butir kritis pertama dikonfirmasi.
     */
```

dengan:

```php
     * Beranda fase B2 (acuan 01): trip camping Budi ke Gede via Cibodas lima belas hari lagi,
     * daftar persiapannya dibangkitkan dengan tiga butir kritis pertama dikonfirmasi, dan Budi
     * mengikuti Gunung Gede supaya kabarnya ikut terpotret.
     */
```

3. Ganti:

```php
        $butir = app(PreparationService::class)->generateFor($trip);
        $butir->where('is_critical', true)->take(3)->each(fn ($satu) => $satu->update(['status' => PreparationStatus::CONFIRMED->value]));

        $this->tulisPratinjau('dasbor', $this->get(self::ASAL.'/dashboard'), 'Dashboard');
```

dengan:

```php
        $butir = app(PreparationService::class)->generateFor($trip);
        $butir->where('is_critical', true)->take(3)->each(fn ($satu) => $satu->update(['status' => PreparationStatus::CONFIRMED->value]));
        MountainFollow::create(['user_id' => $budi->id, 'mountain_id' => $trip->trail->mountain_id]);

        $this->tulisPratinjau('dasbor', $this->get(self::ASAL.'/dashboard'), 'Dashboard');
```

- [ ] **Step 4: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Http/Controllers/DashboardController.php app/Services/Dashboard/KabarBeranda.php app/Services/Community/KabarJalur.php app/Services/TrailNewsService.php tests/Feature/OfficialStatusSafetySentenceTest.php tests/Feature/TrailNewsTest.php tests/Pratinjau/HalamanPratinjauTest.php`, lalu `php artisan test --compact --filter='TrailNews|OfficialStatusSafetySentence|DashboardPhase|NoDeadViewData|ReminderSheet'`
Expected: PASS. Pada simulasi rencana: 60 test lulus.

- [ ] **Step 5: Suite, tipe, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 1.959 test, 1.957 lulus, 2 dilewati.

Run: `npx tsc --noEmit`
Expected: keluar tanpa keluaran galat.

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `php artisan test --compact --testsuite=Pratinjau`
Expected: build `✓ built`; Pratinjau lulus. Pada simulasi rencana: 22 test lulus.

- [ ] **Step 6: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 5: Jalur lain untuk Anda dan urutan alasan kartu

Spec bagian 6.1 ("Jalur lain untuk Anda"), prototype 01; induk W5 butir 4; ruling W5-R16, W5-R17, dan W5-R19.
`TrailFitService` mendapat `teratas()` dan alasan yang mengikuti label (ruling W3-C7); `JalurUntukAnda` memilih
kandidat; halaman ditulis ulang dengan kolom samping 22rem mulai 1024 px. Anggaran kueri Beranda dikunci untuk
seluruh bagiannya.

**Files:**
- Create: `app/Services/Dashboard/JalurUntukAnda.php`, `resources/js/components/beranda/JalurUntukAnda.tsx`
- Modify: `app/Http/Controllers/DashboardController.php`, `app/Services/TrailFitService.php`, `config/hiking.php`, `resources/js/components/beranda/types.ts`, `resources/js/pages/Dashboard.tsx` (ditulis ulang utuh)
- Test: `tests/Feature/TrailFitServiceTest.php`, `tests/Feature/RecommendationExclusionTest.php`, `tests/Feature/PageQueryBudgetTest.php`

**Interfaces:**
- Consumes: `RouteFitService::evaluate()` dan `weights()`; `RouteFitResult` (`eligible`, `label`, `internalScore`, `explanation['why_it_fits']`, `strongFactors()`, `weakFactors()`); `Trail` scope `published()`, `dataComplete()`, `withPublishabilityData()`; `TrailRowResource`; `SkalaProfil::naikBersama()`; `KonteksBeranda::$fase`, `$profilLengkap`, `$pengguna`; komponen `LabelKecocokan`, `AngkaRingkas`, `GarisProfil`, `Tautan`; TypeScript `BarisJalur`; rute `onboarding` dan `trails.index`.
- Produces:
  - `App\Services\TrailFitService::teratas(User $user, Collection $trails, int $jumlah): array<int, TrailFitSummary>` (berkunci id jalur, dalam urutan tawaran; `$trails` koleksi Eloquent); `alasan(RouteFitResult): string` mengikuti label;
  - `App\Services\Dashboard\JalurUntukAnda::untuk(KonteksBeranda $konteks): ?array{tanpaProfil: bool, jalur: list<array>}`;
  - config `hiking.beranda.batas_kandidat` = 500;
  - prop `jalurLain` (closure); TypeScript `JalurLainData`; komponen `JalurUntukAnda({ data })`.

- [ ] **Step 1: Tulis test yang gagal.**

`tests/Feature/PageQueryBudgetTest.php`:

1. Ganti:

```php
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\TrailConsideration;
```

dengan:

```php
use App\Models\Mountain;
use App\Models\MountainFollow;
use App\Models\PermitRequirement;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\TrailConsideration;
```

2. Ganti:

```php
    }

    private function pendakiBerprofil(): User
```

dengan:

```php
    }

    /**
     * Beranda W5 dalam fase B2 dengan semua bagiannya: kartu trip, tenggat, kabar gunung yang
     * diikuti, dan "Jalur lain untuk Anda". Bebannya tidak tumbuh bersama jumlah jalur terbit
     * yang dinilai, jumlah trip pendakinya, maupun jumlah kabar.
     */
    public function test_the_home_does_not_grow_with_trails_trips_or_news(): void
    {
        $user = $this->pendakiBerprofil();
        $gunung = Mountain::factory()->create();
        $jalur = Trail::factory()->count(4)->for($gunung)->easy()->published()->create();
        PermitRequirement::factory()->create(['mountain_id' => $gunung->id]);
        MountainFollow::create(['user_id' => $user->id, 'mountain_id' => $gunung->id]);
        TripPlan::factory()->for($user)->for($jalur->first())->create(['planned_date' => now()->addDays(10)->toDateString(), 'reminders_enabled' => true]);
        TrailConditionReport::factory()->approved()->for($jalur->first())->create();

        $this->actingAs($user);
        $this->get('/dashboard')->assertOk();
        $sedikit = $this->countQueries('/dashboard');

        Trail::factory()->count(20)->for($gunung)->easy()->published()->create();
        TripPlan::factory()->count(5)->for($user)->for($jalur->last())->create(['planned_date' => now()->addDays(40)->toDateString()]);
        TrailConditionReport::factory()->count(5)->approved()->for($jalur->last())->create();

        $banyak = $this->countQueries('/dashboard');

        $this->assertSame($sedikit, $banyak, "Beban tumbuh: {$sedikit} query untuk 4 jalur, {$banyak} untuk 24 jalur.");
    }

    private function pendakiBerprofil(): User
```

`tests/Feature/RecommendationExclusionTest.php`:

1. Ganti:

```php
use App\Models\Trail;
use App\Models\User;
```

dengan:

```php
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
```

2. Ganti:

```php
    /**
     * Keadaan warisan yang ditiru dengan sadar: tayang tetapi kehilangan data kritis,
```

dengan:

```php
    /**
     * Beranda W5 ("Jalur lain untuk Anda", spec v3 bagian 6.1): jalur tak lengkap tidak pernah
     * ditawarkan, begitu pula jalur yang sedang direncanakan pendaki itu sendiri; yang
     * ditawarkan hanya berlabel Cocok atau Perlu persiapan.
     */
    public function test_the_home_never_offers_an_incomplete_or_already_planned_trail(): void
    {
        $user = $this->hiker();
        $lengkap = $this->completeTrail();
        $direncanakan = $this->completeTrail();
        $cacat = $this->legacyIncompletePublishedTrail();
        TripPlan::factory()->for($user)->for($direncanakan)->create(['planned_date' => now()->addDays(20)->toDateString()]);

        $jalur = $this->actingAs($user)->get(route('dashboard'))->assertOk()->viewData('page')['props']['jalurLain']['jalur'];
        $ids = array_column($jalur, 'id');

        $this->assertContains($lengkap->id, $ids);
        $this->assertNotContains($cacat->id, $ids);
        $this->assertNotContains($direncanakan->id, $ids);
        $this->assertSame([], array_diff(array_column(array_column($jalur, 'fit'), 'label'), ['COCOK', 'PERLU_PERSIAPAN']));
    }

    /**
     * Keadaan warisan yang ditiru dengan sadar: tayang tetapi kehilangan data kritis,
```

`tests/Feature/TrailFitServiceTest.php`:

1. Ganti:

```php
namespace Tests\Feature;

use App\Models\Mountain;
use App\Models\Profile;
use App\Models\Trail;
use App\Models\User;
use App\Models\UserExperience;
use App\Services\TrailFitService;
```

dengan:

```php
namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\NavigationExperience;
use App\Enums\OfficialStatusValue;
use App\Enums\PreferredDuration;
use App\Enums\RouteFitLabel;
use App\Enums\StatusScope;
use App\Enums\TripType;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Profile;
use App\Models\Trail;
use App\Models\TrailSegment;
use App\Models\User;
use App\Models\UserExperience;
use App\Models\UserPreference;
use App\Services\RouteFitService;
use App\Services\TrailFitService;
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
     * Pendaki menengah yang berpengalaman di hutan, navigasi cakap, dan memilih camping.
     */
    private function pendakiCamping(): User
    {
        $user = User::factory()->create();
        Profile::factory()->for($user)->create(['experience_level' => ExperienceLevel::INTERMEDIATE->value]);
        UserExperience::factory()->for($user)->create([
            'completed_hikes_count' => 8,
            'terrain_experience' => ['FOREST'],
            'navigation_experience' => NavigationExperience::COMPETENT->value,
            'highest_elevation_gain_m' => 1200,
            'longest_hike_duration_minutes' => 600,
        ]);
        UserPreference::factory()->for($user)->create([
            'preferred_duration' => PreferredDuration::ONE_DAY->value,
            'preferred_trip_type' => TripType::CAMPING->value,
        ]);

        return $user->fresh();
    }

    /**
     * Ruling W3-C7, diputuskan W5: jalur berlabel Cocok dijelaskan dengan alasan terkuatnya,
     * kalimat pertama why_it_fits dari RecommendationExplanationService, bukan dengan faktor
     * lemahnya. Faktor lemah itu tetap jujur disebut di perluDiperhatikan.
     */
    public function test_a_fitting_trail_is_explained_by_its_strongest_reason(): void
    {
        $jalur = Trail::factory()->easy()->published()->create(['camping_available' => false]);

        $ringkasan = app(TrailFitService::class)->forTrails($this->pendakiCamping(), Trail::whereKey($jalur->id)->get())[$jalur->id];

        $this->assertSame(RouteFitLabel::COCOK, $ringkasan->label);
        $this->assertSame('Tingkat pengalaman Anda sesuai dengan beban fisik jalur ini.', $ringkasan->alasan);
        $this->assertContains('Jalur ini tidak memiliki area camping resmi untuk tipe perjalanan yang Anda pilih.', $ringkasan->perluDiperhatikan);
    }

    /**
     * Tawaran Beranda: hanya Cocok dan Perlu persiapan yang lolos syarat dasar, Cocok lebih
     * dulu, lalu skor internal mesin; jumlahnya dibatasi dan skornya tidak ikut keluar.
     *
     * Jalur dengan segmen yang dibatasi turun ke Perlu persiapan walau skor internalnya paling
     * tinggi; urutan menurut skor saja akan menaruhnya di depan jalur Cocok.
     */
    public function test_the_top_offers_put_fitting_trails_first_and_leave_out_poor_fits(): void
    {
        $user = $this->pendakiCamping();
        $cocok = Trail::factory()->easy()->published()->create(['camping_available' => false]);
        $dibatasi = Trail::factory()->easy()->published()->create(['camping_available' => true]);
        $segmen = TrailSegment::factory()->create(['trail_id' => $dibatasi->id, 'sequence' => 1, 'name' => 'Puncak']);
        OfficialStatus::create([
            'statusable_type' => $segmen->getMorphClass(),
            'statusable_id' => $segmen->id,
            'scope' => StatusScope::SEGMENT->value,
            'status' => OfficialStatusValue::RESTRICTED->value,
            'source' => 'Balai Uji',
        ]);
        $teknis = Trail::factory()->highlyTechnical()->published()->create();
        $semua = Trail::whereKey([$dibatasi->id, $teknis->id, $cocok->id])->get();

        $ringkasan = app(TrailFitService::class)->forTrails($user, $semua);
        $tawaran = app(TrailFitService::class)->teratas($user, $semua, 3);

        $this->assertSame([RouteFitLabel::COCOK, RouteFitLabel::PERLU_PERSIAPAN], [$ringkasan[$cocok->id]->label, $ringkasan[$dibatasi->id]->label]);
        $this->assertGreaterThan(
            app(RouteFitService::class)->evaluate($user, null, $cocok)->internalScore,
            app(RouteFitService::class)->evaluate($user, null, $dibatasi)->internalScore,
        );
        $this->assertNotContains($ringkasan[$teknis->id]->label, [RouteFitLabel::COCOK, RouteFitLabel::PERLU_PERSIAPAN]);
        $this->assertSame([$cocok->id, $dibatasi->id], array_keys($tawaran));
        $this->assertSame([$cocok->id], array_keys(app(TrailFitService::class)->teratas($user, $semua, 1)));
        $this->assertFalse(property_exists($tawaran[$cocok->id], 'internalScore'));
    }

    /**
     * Tiga kartu tawaran yang bertuliskan kalimat yang sama tidak menjelaskan apa pun: kartu
     * Cocok berikutnya memakai kalimat why_it_fits berikutnya yang belum dipakai.
     */
    public function test_offered_trails_do_not_repeat_the_same_reason(): void
    {
        $user = $this->pendakiCamping();
        $pertama = Trail::factory()->easy()->published()->create(['camping_available' => true]);
        $kedua = Trail::factory()->easy()->published()->create(['camping_available' => true, 'elevation_gain_m' => 450]);
        $semua = Trail::whereKey([$pertama->id, $kedua->id])->get();

        $tawaran = array_values(app(TrailFitService::class)->teratas($user, $semua, 3));
        $kalimat = app(RouteFitService::class)->evaluate($user, null, $kedua)->explanation['why_it_fits'];

        $this->assertSame([RouteFitLabel::COCOK, RouteFitLabel::COCOK], [$tawaran[0]->label, $tawaran[1]->label]);
        $this->assertSame('Tingkat pengalaman Anda sesuai dengan beban fisik jalur ini.', $tawaran[0]->alasan);
        $this->assertNotSame($tawaran[0]->alasan, $tawaran[1]->alasan);
        $this->assertContains($tawaran[1]->alasan, $kalimat);
    }
}
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='TrailFitService|RecommendationExclusion|PageQueryBudget|DashboardPhase|NoDeadViewData|TrailCard|TrailDetailPage|FitAssessmentPage|AmbientFit|TapTarget'`
Expected: FAIL. Pada simulasi rencana: 121 test, 117 lulus, 1 gagal, 3 error. Yang merah: `TrailFitServiceTest::test_a_fitting_trail_is_explained_by_its_strongest_reason` (alasan jalur Cocok masih faktor lemah "Jalur ini tidak memiliki area camping resmi untuk tipe perjalanan yang Anda pilih."), dua test `TrailFitServiceTest` lain (`teratas()` belum ada), dan `RecommendationExclusionTest::test_the_home_never_offers_an_incomplete_or_already_planned_trail` (prop `jalurLain` belum ada). `PageQueryBudgetTest::test_the_home_does_not_grow_with_trails_trips_or_news` sudah lulus sebelum implementasi karena Beranda Tugas 4 juga tidak tumbuh; test itu menjaga kandidat tawaran (mutasi status per jalur menggagalkannya).

- [ ] **Step 3: Tulis implementasinya.**

Buat `app/Services/Dashboard/JalurUntukAnda.php`:

```php
<?php

namespace App\Services\Dashboard;

use App\Enums\TripStatus;
use App\Http\Resources\TrailRowResource;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Services\RouteFit\TrailFitSummary;
use App\Services\TrailFitService;
use App\Services\Trails\SkalaProfil;

/**
 * "Jalur lain untuk Anda" di Beranda (spec v3 bagian 6.1, prototype 01; rencana induk W5 butir
 * 4): paling banyak tiga jalur dari TrailFitService::teratas(), masing-masing dengan label
 * publik, garis profil pada skala yang sama, dan satu alasan (R-003, R-004, R-005).
 *
 * Kandidatnya jalur terbit yang datanya lengkap (R-008 dan R-022 lapis pertama; evaluate()
 * menjadi lapis kedua), tanpa jalur yang sedang direncanakan atau didaki pendaki ini. Dibatasi
 * hiking.beranda.batas_kandidat supaya penilaian di memori tetap terbatas; kueri tidak tumbuh
 * bersama jumlah jalur karena status, pembatasan, dan izin dimuat batch (§96).
 *
 * Fase B1, B2, dan B4 saja. Pendaki tanpa profil (B2 atau B4 sebelum profil terisi) mendapat
 * ajakan mengisi profil, karena tanpa profil tidak ada yang dapat dinilai.
 */
final class JalurUntukAnda
{
    private const JUMLAH = 3;

    /** Trip yang jalurnya tidak ditawarkan lagi: belum dimulai atau sedang berjalan. */
    private const DIRENCANAKAN = [TripStatus::DRAFT, TripStatus::PLANNED, TripStatus::READY_FOR_DEPARTURE, TripStatus::IN_PROGRESS];

    public function __construct(private readonly TrailFitService $fit) {}

    /**
     * @return array{tanpaProfil: bool, jalur: array<int, array<string, mixed>>}|null
     */
    public function untuk(KonteksBeranda $konteks): ?array
    {
        if (! in_array($konteks->fase, [RingkasanDasbor::B1, RingkasanDasbor::B2, RingkasanDasbor::B4], true)) {
            return null;
        }

        if (! $konteks->profilLengkap) {
            return ['tanpaProfil' => true, 'jalur' => []];
        }

        $kandidat = Trail::query()
            ->published()
            ->dataComplete()
            ->withPublishabilityData()
            ->with('mountain')
            ->whereNotIn('id', TripPlan::query()
                ->whereBelongsTo($konteks->pengguna)
                ->whereIn('status', array_map(fn (TripStatus $status) => $status->value, self::DIRENCANAKAN))
                ->select('trail_id'))
            ->orderBy('id')
            ->limit((int) config('hiking.beranda.batas_kandidat'))
            ->get()
            ->keyBy('id');

        $tawaran = $this->fit->teratas($konteks->pengguna, $kandidat, self::JUMLAH);
        $garis = SkalaProfil::naikBersama($kandidat->only(array_keys($tawaran))->map(fn (Trail $t) => $t->elevation_profile)->all())['garis'];

        return [
            'tanpaProfil' => false,
            'jalur' => array_values(array_map(fn (int $id, TrailFitSummary $fit) => [
                ...(new TrailRowResource($kandidat[$id], $fit))->resolve(),
                'garis' => $garis[$id] ?? null,
            ], array_keys($tawaran), $tawaran)),
        ];
    }
}
```

Buat `resources/js/components/beranda/JalurUntukAnda.tsx`:

```tsx
import { AngkaRingkas } from '@/components/trails/AngkaRingkas';
import { GarisProfil } from '@/components/trails/GarisProfil';
import { LabelKecocokan } from '@/components/trails/LabelKecocokan';
import { fokusTerlihat } from '@/components/ui/gaya';
import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';
import { onboarding } from '@/routes';
import { index as trailsIndex } from '@/routes/trails';
import type { JalurLainData } from './types';

const tautanTeks = kelas('inline-flex min-h-11 items-center text-kecil font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900', fokusTerlihat);

/**
 * "Jalur lain untuk Anda" (spec v3 bagian 6.1, prototype 01): paling banyak tiga jalur dengan
 * label kecocokan (ikon dan kata), tiga angka, garis profil pada skala yang sama, dan satu
 * alasan; tanpa skor atau persen (R-005). Di ponsel kartunya bergulir menyamping dengan titik
 * henti seperti prototype; mulai 1024 px kartunya berderet di kolom samping. Setiap kartu
 * punya tautan, jadi papan ketik menggulirnya lewat fokus. Daftar gulirnya `relative`, supaya
 * teks sr-only (position absolute) di kartu ikut terpotong di dalamnya dan tidak melebarkan
 * halaman. Status resmi tidak ditulis di sini; ia ada di halaman jalurnya.
 */
export function JalurUntukAnda({ data }: { data: JalurLainData }) {
    return (
        <section aria-labelledby="jalur-lain">
            <h2 id="jalur-lain" className="text-bagian text-primary">
                Jalur lain untuk Anda
            </h2>
            {data.tanpaProfil ? (
                <div className="mt-1">
                    <p className="max-w-prose text-kecil text-secondary">Jalur dinilai menurut profil pendaki Anda, dan profilnya belum terisi.</p>
                    <Tautan href={onboarding.url()} className={tautanTeks}>
                        Isi profil pendaki
                    </Tautan>
                </div>
            ) : data.jalur.length === 0 ? (
                <div className="mt-1">
                    <p className="max-w-prose text-kecil text-secondary">
                        Belum ada jalur lain yang dapat ditawarkan untuk profil Anda. Jelajah memuat semua jalur terbit beserta penilaiannya.
                    </p>
                    <Tautan href={trailsIndex.url()} className={tautanTeks}>
                        Buka Jelajah
                    </Tautan>
                </div>
            ) : (
                <>
                    <p className="mt-1 text-kecil text-secondary">Kecocokan dasar dengan profil Anda, selalu dengan alasannya.</p>
                    <ul className="relative -mx-4 mt-3 flex snap-x snap-mandatory scroll-px-4 gap-3 overflow-x-auto px-4 pb-2 sm:-mx-6 sm:scroll-px-6 sm:px-6 lg:mx-0 lg:flex-col lg:overflow-visible lg:px-0 lg:pb-0">
                        {data.jalur.map((j) => (
                            <li key={j.id} className="w-[17rem] shrink-0 snap-start lg:w-auto">
                                <article aria-labelledby={`jalur-lain-${j.id}`} className="h-full rounded-lg border border-subtle bg-surface p-4">
                                    {j.fit ? <LabelKecocokan nilai={j.fit.label} label={j.fit.labelText} /> : null}
                                    <h3 id={`jalur-lain-${j.id}`} className="mt-2 break-words text-isi font-bold text-primary">
                                        <Tautan href={j.url} className={kelas('rounded-sm underline-offset-2 hover:underline', fokusTerlihat)}>
                                            {j.name}
                                        </Tautan>
                                    </h3>
                                    {j.mountain ? (
                                        <p className="break-words text-meta text-secondary">
                                            {j.mountain}
                                            {j.province ? `, ${j.province}` : null}
                                        </p>
                                    ) : null}
                                    <AngkaRingkas jalur={j} className="mt-2 text-secondary" />
                                    {j.garis ? <GarisProfil titik={j.garis} label={j.fit?.label ?? null} className="mt-2" /> : null}
                                    {j.fit ? <p className="mt-2 break-words text-kecil text-primary">{j.fit.alasan}</p> : null}
                                </article>
                            </li>
                        ))}
                    </ul>
                </>
            )}
        </section>
    );
}
```

`app/Http/Controllers/DashboardController.php`:

1. Ganti:

```php
namespace App\Http\Controllers;

use App\Services\Dashboard\KabarBeranda;
```

dengan:

```php
namespace App\Http\Controllers;

use App\Services\Dashboard\JalurUntukAnda;
use App\Services\Dashboard\KabarBeranda;
```

2. Ganti:

```php
{
    public function __invoke(Request $request, RingkasanDasbor $ringkasan, TenggatTrip $tenggat, KabarBeranda $kabar): Response
    {
```

dengan:

```php
{
    public function __invoke(Request $request, RingkasanDasbor $ringkasan, TenggatTrip $tenggat, KabarBeranda $kabar, JalurUntukAnda $jalurLain): Response
    {
```

3. Ganti:

```php
            'kabar' => fn () => $kabar->untuk($ringkasan->konteks($user)),
        ]);
```

dengan:

```php
            'kabar' => fn () => $kabar->untuk($ringkasan->konteks($user)),
            'jalurLain' => fn () => $jalurLain->untuk($ringkasan->konteks($user)),
        ]);
```

`app/Services/TrailFitService.php`:

1. Ganti:

```php
namespace App\Services;

use App\Models\HikingGoal;
```

dengan:

```php
namespace App\Services;

use App\Enums\RouteFitLabel;
use App\Models\HikingGoal;
```

2. Ganti:

```php
{
    public function __construct(
```

dengan:

```php
{
    /** Urutan tawaran "Jalur lain untuk Anda": Cocok lebih dulu, Kurang cocok tidak ditawarkan. */
    private const URUTAN_TAWARAN = ['COCOK' => 0, 'PERLU_PERSIAPAN' => 1];

    public function __construct(
```

3. Ganti:

```php
    public function forTrails(User $user, Collection $trails, ?HikingGoal $goal = null): array
    {
```

dengan:

```php
    public function forTrails(User $user, Collection $trails, ?HikingGoal $goal = null): array
    {
        return array_map(fn (RouteFitResult $hasil) => $this->ringkasan($hasil, $goal), $this->hasil($user, $trails, $goal));
    }

    /**
     * Paling banyak $jumlah jalur untuk ditawarkan kepada pendaki ini ("Jalur lain untuk Anda"
     * di Beranda, spec v3 bagian 6.1): hanya yang lolos syarat dasar dan berlabel Cocok atau
     * Perlu persiapan, Cocok lebih dulu, lalu menurut skor internal mesin seperti recommend().
     * Skor itu tidak pernah keluar dari kelas ini (BR-09); yang dikembalikan hanya ringkasan,
     * berkunci id jalur, dalam urutan tawaran, dan kartu Cocok tidak mengulang alasan kartu
     * sebelumnya.
     *
     * @param  Collection<int, Trail>  $trails
     * @return array<int, TrailFitSummary>
     */
    public function teratas(User $user, Collection $trails, int $jumlah): array
    {
        $ditawarkan = array_filter(
            $this->hasil($user, $trails),
            fn (RouteFitResult $hasil) => $hasil->eligible && isset(self::URUTAN_TAWARAN[$hasil->label?->value]),
        );

        uasort($ditawarkan, fn (RouteFitResult $a, RouteFitResult $b) => [self::URUTAN_TAWARAN[$a->label->value], $b->internalScore]
            <=> [self::URUTAN_TAWARAN[$b->label->value], $a->internalScore]);

        $tawaran = [];
        $dipakai = [];

        foreach (array_slice($ditawarkan, 0, $jumlah, true) as $id => $hasil) {
            // Kalimat yang sudah menjelaskan tawaran sebelumnya tidak diulang di kartu
            // berikutnya: jalur Cocok memakai kalimat why_it_fits pertama yang belum dipakai.
            $alasan = $hasil->label === RouteFitLabel::COCOK
                ? collect($hasil->explanation['why_it_fits'] ?? [])->first(fn (string $kalimat) => ! in_array($kalimat, $dipakai, true))
                : null;

            $tawaran[$id] = $this->ringkasan($hasil, null, $alasan);
            $dipakai[] = $tawaran[$id]->alasan;
        }

        return $tawaran;
    }

    /**
     * Evaluasi seluruh koleksi dengan data batch, berkunci id jalur.
     *
     * @param  Collection<int, Trail>  $trails
     * @return array<int, RouteFitResult>
     */
    private function hasil(User $user, Collection $trails, ?HikingGoal $goal = null): array
    {
```

4. Ganti:

```php
        $permits = $this->permits->requirementsForTrails($trails);

        $ringkasan = [];

        foreach ($trails as $trail) {
            $hasil = $this->fit->evaluate(
                user: $user,
```

dengan:

```php
        $permits = $this->permits->requirementsForTrails($trails);

        $hasil = [];

        foreach ($trails as $trail) {
            $hasil[$trail->id] = $this->fit->evaluate(
                user: $user,
```

5. Ganti:

```php
            );

            // Isi "Kenapa?" di kartu Jelajah (spec v3 bagian 6.2): dua kalimat teratas dari
            // penjelasan mesin yang sama dengan halaman rekomendasi, bukan kalimat baru.
            $ringkasan[$trail->id] = new TrailFitSummary(
                label: $hasil->label,
                eligible: $hasil->eligible,
                alasan: $this->alasan($hasil),
                denganRencana: $goal !== null,
                mengapaSesuai: $hasil->eligible ? array_slice($hasil->explanation['why_it_fits'] ?? [], 0, 2) : [],
                perluDiperhatikan: array_slice($hasil->explanation['what_to_watch'] ?? [], 0, 2),
            );
        }

        return $ringkasan;
    }
```

dengan:

```php
            );
        }

        return $hasil;
    }

    /**
     * Isi "Kenapa?" di kartu Jelajah (spec v3 bagian 6.2): dua kalimat teratas dari penjelasan
     * mesin yang sama dengan halaman rekomendasi, bukan kalimat baru.
     */
    private function ringkasan(RouteFitResult $hasil, ?HikingGoal $goal, ?string $alasan = null): TrailFitSummary
    {
        return new TrailFitSummary(
            label: $hasil->label,
            eligible: $hasil->eligible,
            alasan: $alasan ?? $this->alasan($hasil),
            denganRencana: $goal !== null,
            mengapaSesuai: $hasil->eligible ? array_slice($hasil->explanation['why_it_fits'] ?? [], 0, 2) : [],
            perluDiperhatikan: array_slice($hasil->explanation['what_to_watch'] ?? [], 0, 2),
        );
    }
```

6. Ganti:

```php
     * Ketika sebuah jalur tersingkir, yang ingin ia tahu penyebabnya, bukan kelebihannya.
     * Ketika cocok, yang menolong justru alasan terkuatnya. Urutan ini yang membuat satu
     * kalimat cukup, dan §89 memang hanya menyediakan satu baris di tingkat daftar. Publik
     * karena rincian jalur memakai kalimat yang sama untuk penilaiannya (W3).
     */
```

dengan:

```php
     * Ketika sebuah jalur tersingkir, yang ingin ia tahu penyebabnya, bukan kelebihannya.
     * Ketika cocok, yang menolong justru alasan terkuatnya: kalimat pertama why_it_fits dari
     * RecommendationExplanationService (ruling W3-C7, diputuskan W5). Label lain mendahulukan
     * faktor lemah, karena yang perlu diketahui pendaki adalah apa yang membuatnya belum
     * Cocok. Urutan ini yang membuat satu kalimat cukup, dan §89 memang hanya menyediakan satu
     * baris di tingkat daftar. Publik karena rincian jalur memakai kalimat yang sama untuk
     * penilaiannya (W3).
     */
```

7. Ganti:

```php
            return $this->alasanTersingkir($hasil->failedRules[0] ?? null);
        }
```

dengan:

```php
            return $this->alasanTersingkir($hasil->failedRules[0] ?? null);
        }

        if ($hasil->label === RouteFitLabel::COCOK && $hasil->strongFactors() !== []) {
            return $hasil->explanation['why_it_fits'][0];
        }
```

`config/hiking.php`:

1. Ganti:

```php
        'hari_ajakan_lapor' => 7,
    ],
```

dengan:

```php
        'hari_ajakan_lapor' => 7,

        // "Jalur lain untuk Anda": kandidat yang dinilai di memori per permintaan, seperti
        // hiking.jelajah.batas_kandidat. Kueri tidak tumbuh bersama angka ini; waktu hitungnya ya.
        'batas_kandidat' => 500,
    ],
```

`resources/js/components/beranda/types.ts`:

1. Ganti:

```ts
import type { LaporanGunung } from '@/components/trails/gunung/types';
import type { KeadaanLangkah } from '@/components/trips/types';
```

dengan:

```ts
import type { LaporanGunung } from '@/components/trails/gunung/types';
import type { BarisJalur } from '@/components/trails/types';
import type { KeadaanLangkah } from '@/components/trips/types';
```

2. Ganti:

```ts
}

export interface FaseBeranda {
```

dengan:

```ts
}

/** "Jalur lain untuk Anda" (App\Services\Dashboard\JalurUntukAnda); null pada fase B0 dan B3. */
export interface JalurLainData {
    /** Profil pendaki belum terisi, jadi belum ada yang dapat dinilai. */
    tanpaProfil: boolean;
    jalur: Array<BarisJalur & { garis: Array<[number, number]> | null }>;
}

export interface FaseBeranda {
```

Tulis ulang `resources/js/pages/Dashboard.tsx` utuh:

```tsx
import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { JalurUntukAnda } from '@/components/beranda/JalurUntukAnda';
import { KabarDiikuti } from '@/components/beranda/KabarDiikuti';
import { KartuFase } from '@/components/beranda/KartuFase';
import { sapaan } from '@/components/beranda/sapaan';
import { TenggatTerdekat } from '@/components/beranda/TenggatTerdekat';
import type { FaseBeranda, JalurLainData, KabarBerandaData, TenggatBeranda } from '@/components/beranda/types';
import { PageHeader } from '@/components/ui/PageHeader';
import { Tautan } from '@/components/ui/Tautan';
import { AppLayout } from '@/layouts/AppLayout';
import { TombolPengingat } from '@/layouts/Pengingat';
import { kelas } from '@/lib/kelas';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    fase: FaseBeranda;
    tenggat: TenggatBeranda | null;
    kabar: KabarBerandaData | null;
    jalurLain: JalurLainData | null;
}

/**
 * Beranda berfase (spec v3 bagian 6.1, DK-45, prototype 01). Server memilih fase dan menyusun
 * isinya (App\Services\Dashboard\RingkasanDasbor dan kelas bagian di sebelahnya); halaman ini
 * hanya memilih tampilan dan tidak memuat aturan domain. Sapaan waktu dihitung dari jam
 * perangkat.
 *
 * Urutan di ponsel mengikuti prototype 01: kartu fase, tenggat, kabar, lalu bagian penemuan.
 * Mulai 1024 px bagian penemuan pindah ke kolom samping 22rem; tanpa isi samping halaman tetap
 * satu kolom selebar 3xl, supaya kartu fase tidak melebar tanpa isi.
 */
export default function Dashboard() {
    const { auth, fase, tenggat, kabar, jalurLain } = usePage<Props>().props;
    const samping = jalurLain !== null;

    return (
        <div className="py-6 lg:py-8">
            <div className={kelas('mx-auto max-w-3xl px-4 sm:px-6 lg:px-8', samping && 'lg:max-w-6xl')}>
                <PageHeader akar merek aksi={<TombolPengingat />} title={`${sapaan(new Date())}, ${auth.user?.name ?? ''}`} description={fase.ringkasan} />

                <div className={kelas(samping && 'lg:grid lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start lg:gap-8')}>
                    <div className="space-y-8">
                        <div className="space-y-4">
                            <KartuFase kartu={fase.kartu} />
                            {fase.tripLewat ? (
                                <div className="text-kecil text-secondary">
                                    <p>
                                        Rencana <span className="font-bold text-primary">{fase.tripLewat.nama}</span> ({fase.tripLewat.tanggal}) sudah lewat
                                        tanggalnya tanpa dimulai.
                                    </p>
                                    <Tautan
                                        href={fase.tripLewat.url}
                                        className="inline-flex min-h-11 items-center font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                                    >
                                        Ubah tanggal atau batalkan
                                    </Tautan>
                                </div>
                            ) : null}
                        </div>
                        {tenggat ? <TenggatTerdekat tenggat={tenggat} /> : null}
                        {kabar ? <KabarDiikuti kabar={kabar} /> : null}
                    </div>

                    {samping ? <div className="mt-8 space-y-8 lg:mt-0">{jalurLain ? <JalurUntukAnda data={jalurLain} /> : null}</div> : null}
                </div>

                <p className="mt-8 border-t border-subtle pt-4 text-kecil text-secondary">
                    Sistem ini membantu pengambilan keputusan pendakian. Sistem tidak menyatakan bahwa suatu gunung atau jalur aman, dan tidak menggantikan
                    informasi resmi dari pengelola.
                </p>
            </div>
        </div>
    );
}

Dashboard.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
```

- [ ] **Step 4: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Http/Controllers/DashboardController.php app/Services/Dashboard/JalurUntukAnda.php app/Services/TrailFitService.php config/hiking.php tests/Feature/PageQueryBudgetTest.php tests/Feature/RecommendationExclusionTest.php tests/Feature/TrailFitServiceTest.php`, lalu `php artisan test --compact --filter='TrailFitService|RecommendationExclusion|PageQueryBudget|DashboardPhase|NoDeadViewData|TrailCard|TrailDetailPage|FitAssessmentPage|AmbientFit|TapTarget'`
Expected: PASS. Pada simulasi rencana: 121 test lulus.

- [ ] **Step 5: Suite, tipe, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 1.964 test, 1.962 lulus, 2 dilewati.

Run: `npx tsc --noEmit`
Expected: keluar tanpa keluaran galat.

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `php artisan test --compact --testsuite=Pratinjau`
Expected: build `✓ built`; Pratinjau lulus. Pada simulasi rencana: 22 test lulus.

- [ ] **Step 6: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 6: Tangga tanjakan

Spec bagian 6.1 ("tangga bila ada riwayat"), prototype 01; induk W5 butir 5; ruling W5-R18. `TanggaTanjakan` menyusun
anak tangga dari pendakian yang tuntas dan trip mendatang; kalimatnya dari `ProgressLadderService`; komponennya satu
seri tanpa legenda di kolom samping.

**Files:**
- Create: `app/Services/Dashboard/TanggaTanjakan.php`, `resources/js/components/beranda/TanggaTanjakan.tsx`
- Modify: `app/Http/Controllers/DashboardController.php`, `resources/js/components/beranda/types.ts`, `resources/js/pages/Dashboard.tsx`
- Test: `tests/Feature/DashboardLadderTest.php` (baru)

**Interfaces:**
- Consumes: `ProgressLadderService::bandingkanDenganRiwayat(User, Trail): ?string`; `HikingHistory` (`completion_state`, `trail_id`); `KonteksBeranda::$fase`, `$pengguna`, `$trip`; `fokusTerlihat`, `Tautan`; rute `progress`.
- Produces: `App\Services\Dashboard\TanggaTanjakan::untuk(KonteksBeranda $konteks): ?array{anakTangga: list<array{jalur: string, m: int, teks: string, rencana: bool}>, kalimat: string}`; prop `tangga` (closure); TypeScript `TanggaData`; komponen `TanggaTanjakan({ tangga })`.

- [ ] **Step 1: Tulis test yang gagal.**

Buat `tests/Feature/DashboardLadderTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\CompletionState;
use App\Enums\ExperienceLevel;
use App\Enums\HikingSessionStatus;
use App\Enums\TripStatus;
use App\Models\HikingHistory;
use App\Models\HikingSession;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Tangga tanjakan Beranda (spec v3 bagian 6.1, prototype 01, rencana induk W5 butir 5): tanjakan
 * jalur yang pernah dituntaskan, dari yang terendah, dengan trip mendatang sebagai anak tangga
 * rencana. Diukur dari elevation gain, bukan ketinggian puncak (BR-02), dan dibandingkan dengan
 * pendakian pendakinya sendiri (R-015).
 */
class DashboardLadderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-09-25 03:00:00', 'UTC'));
    }

    private function pendaki(bool $profil = true): User
    {
        $user = User::factory()->create();

        if ($profil) {
            $user->profile()->create(['experience_level' => ExperienceLevel::INTERMEDIATE->value, 'completed_at' => now()]);
        }

        return $user->fresh();
    }

    private function tuntas(User $user, string $nama, ?int $tanjakan, CompletionState $hasil = CompletionState::COMPLETED, ?Trail $jalur = null): Trail
    {
        $jalur ??= Trail::factory()->create(['name' => $nama, 'elevation_gain_m' => $tanjakan]);
        $trip = TripPlan::factory()->for($user)->for($jalur)->create(['planned_date' => '2026-08-01', 'status' => TripStatus::COMPLETED->value, 'completed_at' => now()->subDays(50)]);

        HikingHistory::create([
            'user_id' => $user->id,
            'trip_plan_id' => $trip->id,
            'trail_id' => $jalur->id,
            'trip_type' => $trip->trip_type->value,
            'completion_state' => $hasil->value,
            'completed_at' => now()->subDays(50),
        ]);

        return $jalur;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function tangga(User $user): ?array
    {
        return $this->actingAs($user)->get(route('dashboard'))->assertOk()->viewData('page')['props']['tangga'];
    }

    public function test_the_ladder_climbs_from_the_lowest_completed_gain_to_the_planned_trip(): void
    {
        $user = $this->pendaki();
        $this->tuntas($user, 'Prau via Wates', 700);
        $this->tuntas($user, 'Andong via Sawit', 380);
        $this->tuntas($user, 'Batur via Toya Bungkah', 600);
        $gede = Trail::factory()->published()->create(['name' => 'Gede via Cibodas', 'elevation_gain_m' => 1650]);
        TripPlan::factory()->for($user)->for($gede)->create(['planned_date' => '2026-10-10', 'status' => TripStatus::PLANNED->value]);

        $tangga = $this->tangga($user);

        $this->assertSame([
            ['Andong via Sawit', '380 m', false],
            ['Batur via Toya Bungkah', '600 m', false],
            ['Prau via Wates', '700 m', false],
            ['Gede via Cibodas', '1.650 m', true],
        ], array_map(fn (array $a) => [$a['jalur'], $a['teks'], $a['rencana']], $tangga['anakTangga']));
        $this->assertSame(
            'Gede via Cibodas: jauh di atas tanjakan terberat yang pernah Anda tuntaskan. Tangga ini diukur dari tanjakan jalur, bukan dari ketinggian puncak.',
            $tangga['kalimat'],
        );
    }

    /**
     * Pendakian yang tidak tuntas tidak membuktikan tanjakannya dan tidak menjadi anak tangga;
     * jalur tanpa angka tanjakan tidak digambar sebagai nol (R-007); jalur yang didaki dua kali
     * tetap satu anak tangga, dan yang tampil paling banyak empat tanjakan tertinggi.
     */
    public function test_only_completed_hikes_with_a_known_gain_become_rungs_once_per_trail(): void
    {
        $user = $this->pendaki();
        $prau = $this->tuntas($user, 'Prau via Wates', 700);
        $this->tuntas($user, 'Prau via Wates', 700, jalur: $prau);
        $this->tuntas($user, 'Sindoro via Kledung', 1500, CompletionState::ABANDONED);
        $this->tuntas($user, 'Jalur tanpa data', null);

        foreach ([['Andong via Sawit', 380], ['Batur via Toya Bungkah', 600], ['Merbabu via Selo', 1100], ['Papandayan via Camp David', 610]] as [$nama, $meter]) {
            $this->tuntas($user, $nama, $meter);
        }

        $tangga = $this->tangga($user);

        $this->assertSame(['Batur via Toya Bungkah', 'Papandayan via Camp David', 'Prau via Wates', 'Merbabu via Selo'], array_column($tangga['anakTangga'], 'jalur'));
        $this->assertSame('Tangga ini diukur dari tanjakan jalur, bukan dari ketinggian puncak.', $tangga['kalimat']);
    }

    /**
     * Tanpa pendakian tuntas tidak ada tangga; sebelum profil terisi dan selama mendaki juga tidak.
     */
    public function test_the_ladder_needs_a_completed_hike_and_stays_out_before_a_profile_and_while_hiking(): void
    {
        $this->assertNull($this->tangga($this->pendaki()));

        $tanpaProfil = $this->pendaki(profil: false);
        $this->tuntas($tanpaProfil, 'Prau via Wates', 700);
        $this->assertNull($this->tangga($tanpaProfil));

        $mendaki = $this->pendaki();
        $this->tuntas($mendaki, 'Prau via Wates', 700);
        $trip = TripPlan::factory()->for($mendaki)->create(['planned_date' => '2026-09-25', 'status' => TripStatus::IN_PROGRESS->value]);
        HikingSession::create(['trip_plan_id' => $trip->id, 'user_id' => $mendaki->id, 'status' => HikingSessionStatus::ACTIVE->value, 'started_at' => now()]);
        $this->assertNull($this->tangga($mendaki));
    }

    /**
     * Satu seri tanpa legenda: setiap nilai tertulis, hanya batangnya yang disembunyikan dari
     * pembaca layar, dan anak tangga rencana dibedakan dengan kata dan garis, bukan warna saja.
     */
    public function test_the_ladder_writes_every_value_and_hides_only_the_bars(): void
    {
        $sumber = File::get(resource_path('js/components/beranda/TanggaTanjakan.tsx'));

        $this->assertSame(1, substr_count($sumber, 'aria-hidden="true"'));
        $this->assertStringContainsString('{a.teks}', $sumber);
        $this->assertStringContainsString(', rencana</span>', $sumber);
        $this->assertStringContainsString("'border-2 border-dashed border-brand-700'", $sumber);
    }
}
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='DashboardLadder|DashboardPhase|PageQueryBudget|NoDeadViewData|TapTarget'`
Expected: FAIL. Pada simulasi rencana: 38 test, 34 lulus, 4 error: keempat test `DashboardLadderTest` (prop `tangga` dan `TanggaTanjakan.tsx` belum ada).

- [ ] **Step 3: Tulis implementasinya.**

Buat `app/Services/Dashboard/TanggaTanjakan.php`:

```php
<?php

namespace App\Services\Dashboard;

use App\Enums\CompletionState;
use App\Models\HikingHistory;
use App\Services\ProgressLadderService;

/**
 * Tangga tanjakan di Beranda (spec v3 bagian 6.1 "tangga bila ada riwayat", prototype 01;
 * rencana induk W5 butir 5): tanjakan jalur yang pernah dituntaskan pendaki ini, ditambah trip
 * mendatang sebagai anak tangga rencana, dari yang terendah. Diukur dari elevation gain jalur,
 * bukan ketinggian puncak (BR-02, §18), dan pembandingnya diri sendiri (R-015).
 *
 * Satu anak tangga per jalur, paling banyak ANAK_TANGGA tanjakan tertinggi. Pendakian yang
 * tidak tuntas tidak menjadi anak tangga karena tidak membuktikan tanjakannya tertuntaskan
 * (aturan yang sama dengan ProgressLadderService), dan jalur tanpa angka tanjakan tidak
 * digambar sebagai nol (R-007). Kalimat perbandingannya milik ProgressLadderService, sama
 * dengan rincian jalur.
 */
final class TanggaTanjakan
{
    private const ANAK_TANGGA = 4;

    public function __construct(private readonly ProgressLadderService $tangga) {}

    /**
     * @return array{anakTangga: array<int, array{jalur: string, m: int, teks: string, rencana: bool}>, kalimat: string}|null
     */
    public function untuk(KonteksBeranda $konteks): ?array
    {
        if (! in_array($konteks->fase, [RingkasanDasbor::B1, RingkasanDasbor::B2, RingkasanDasbor::B4], true)) {
            return null;
        }

        $tuntas = HikingHistory::query()
            ->where('hiking_history.user_id', $konteks->pengguna->id)
            ->where('hiking_history.completion_state', CompletionState::COMPLETED->value)
            ->join('trails', 'trails.id', '=', 'hiking_history.trail_id')
            ->whereNotNull('trails.elevation_gain_m')
            ->groupBy('trails.id', 'trails.name', 'trails.elevation_gain_m')
            ->orderByDesc('trails.elevation_gain_m')
            ->orderBy('trails.id')
            ->limit(self::ANAK_TANGGA)
            ->toBase()
            ->get(['trails.name', 'trails.elevation_gain_m']);

        if ($tuntas->isEmpty()) {
            return null;
        }

        $anakTangga = $tuntas->map(fn (object $t) => self::anak((string) $t->name, (int) $t->elevation_gain_m, false))->all();
        $kalimat = 'Tangga ini diukur dari tanjakan jalur, bukan dari ketinggian puncak.';
        $rencana = $konteks->trip?->trail;

        if ($rencana !== null && $rencana->elevation_gain_m !== null) {
            $anakTangga[] = self::anak($rencana->name, $rencana->elevation_gain_m, true);
            $banding = $this->tangga->bandingkanDenganRiwayat($konteks->pengguna, $rencana);
            $kalimat = $banding === null ? $kalimat : $rencana->name.': '.lcfirst($banding).' '.$kalimat;
        }

        usort($anakTangga, fn (array $a, array $b) => $a['m'] <=> $b['m']);

        return ['anakTangga' => $anakTangga, 'kalimat' => $kalimat];
    }

    /**
     * @return array{jalur: string, m: int, teks: string, rencana: bool}
     */
    private static function anak(string $jalur, int $meter, bool $rencana): array
    {
        return ['jalur' => $jalur, 'm' => $meter, 'teks' => number_format($meter, 0, ',', '.').' m', 'rencana' => $rencana];
    }
}
```

Buat `resources/js/components/beranda/TanggaTanjakan.tsx`:

```tsx
import { fokusTerlihat } from '@/components/ui/gaya';
import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';
import { progress } from '@/routes';
import type { TanggaData } from './types';

/**
 * Tangga tanjakan (spec v3 bagian 6.1, prototype 01): satu seri, tanjakan jalur dari yang
 * terendah, dibandingkan dengan pendakian Anda sendiri (R-015). Satu seri, jadi tanpa legenda;
 * nilainya tertulis di setiap baris, sehingga daftar ini sekaligus tabelnya dan batangnya
 * disembunyikan dari pembaca layar. Anak tangga rencana bergaris putus-putus dan bertuliskan
 * "rencana", jadi bedanya tidak dibawa warna saja.
 */
export function TanggaTanjakan({ tangga }: { tangga: TanggaData }) {
    const puncak = Math.max(1, ...tangga.anakTangga.map((a) => a.m));

    return (
        <section aria-labelledby="tangga-tanjakan">
            <div className="flex flex-wrap items-baseline justify-between gap-x-4">
                <h2 id="tangga-tanjakan" className="text-bagian text-primary">
                    Tangga tanjakan Anda
                </h2>
                <Tautan
                    href={progress.url()}
                    className={kelas('inline-flex min-h-11 items-center text-kecil font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900', fokusTerlihat)}
                >
                    Lihat progres
                </Tautan>
            </div>
            <div className="mt-2 rounded-lg border border-subtle bg-surface p-4">
                <ol className="space-y-2.5">
                    {tangga.anakTangga.map((a) => (
                        <li key={`${a.jalur} ${a.rencana ? 'rencana' : 'tuntas'}`} className="grid grid-cols-[minmax(0,7rem)_minmax(0,1fr)_auto] items-center gap-3">
                            <span className={kelas('break-words text-kecil', a.rencana ? 'font-bold text-primary' : 'text-secondary')}>
                                {a.jalur}
                                {a.rencana ? <span className="font-semibold text-secondary">, rencana</span> : null}
                            </span>
                            <span aria-hidden="true" className="block h-2.5">
                                <span
                                    className={kelas('block h-full rounded-e', a.rencana ? 'border-2 border-dashed border-brand-700' : 'bg-brand-700')}
                                    style={{ width: `${Math.max(4, (a.m / puncak) * 100)}%` }}
                                />
                            </span>
                            <span data-angka className={kelas('text-right text-kecil text-primary', a.rencana && 'font-bold')}>
                                {a.teks}
                            </span>
                        </li>
                    ))}
                </ol>
                <p className="mt-3 border-t border-subtle pt-3 text-kecil text-secondary">{tangga.kalimat}</p>
            </div>
        </section>
    );
}
```

`app/Http/Controllers/DashboardController.php`:

1. Ganti:

```php
use App\Services\Dashboard\RingkasanDasbor;
use App\Services\Dashboard\TenggatTrip;
```

dengan:

```php
use App\Services\Dashboard\RingkasanDasbor;
use App\Services\Dashboard\TanggaTanjakan;
use App\Services\Dashboard\TenggatTrip;
```

2. Ganti:

```php
{
    public function __invoke(Request $request, RingkasanDasbor $ringkasan, TenggatTrip $tenggat, KabarBeranda $kabar, JalurUntukAnda $jalurLain): Response
    {
        $user = $request->user();
```

dengan:

```php
{
    public function __invoke(
        Request $request,
        RingkasanDasbor $ringkasan,
        TenggatTrip $tenggat,
        KabarBeranda $kabar,
        JalurUntukAnda $jalurLain,
        TanggaTanjakan $tangga,
    ): Response {
        $user = $request->user();
```

3. Ganti:

```php
            'jalurLain' => fn () => $jalurLain->untuk($ringkasan->konteks($user)),
        ]);
```

dengan:

```php
            'jalurLain' => fn () => $jalurLain->untuk($ringkasan->konteks($user)),
            'tangga' => fn () => $tangga->untuk($ringkasan->konteks($user)),
        ]);
```

`resources/js/components/beranda/types.ts`:

1. Ganti:

```ts
}

export interface FaseBeranda {
```

dengan:

```ts
}

/** Tangga tanjakan (App\Services\Dashboard\TanggaTanjakan); null tanpa pendakian tuntas, dan pada fase B0 dan B3. */
export interface TanggaData {
    anakTangga: Array<{ jalur: string; m: number; teks: string; rencana: boolean }>;
    kalimat: string;
}

export interface FaseBeranda {
```

`resources/js/pages/Dashboard.tsx`:

1. Ganti:

```tsx
import { sapaan } from '@/components/beranda/sapaan';
import { TenggatTerdekat } from '@/components/beranda/TenggatTerdekat';
import type { FaseBeranda, JalurLainData, KabarBerandaData, TenggatBeranda } from '@/components/beranda/types';
import { PageHeader } from '@/components/ui/PageHeader';
```

dengan:

```tsx
import { sapaan } from '@/components/beranda/sapaan';
import { TanggaTanjakan } from '@/components/beranda/TanggaTanjakan';
import { TenggatTerdekat } from '@/components/beranda/TenggatTerdekat';
import type { FaseBeranda, JalurLainData, KabarBerandaData, TanggaData, TenggatBeranda } from '@/components/beranda/types';
import { PageHeader } from '@/components/ui/PageHeader';
```

2. Ganti:

```tsx
    jalurLain: JalurLainData | null;
}
```

dengan:

```tsx
    jalurLain: JalurLainData | null;
    tangga: TanggaData | null;
}
```

3. Ganti:

```tsx
export default function Dashboard() {
    const { auth, fase, tenggat, kabar, jalurLain } = usePage<Props>().props;
    const samping = jalurLain !== null;

    return (
```

dengan:

```tsx
export default function Dashboard() {
    const { auth, fase, tenggat, kabar, jalurLain, tangga } = usePage<Props>().props;
    const samping = jalurLain !== null || tangga !== null;

    return (
```

4. Ganti:

```tsx
                    </div>

                    {samping ? <div className="mt-8 space-y-8 lg:mt-0">{jalurLain ? <JalurUntukAnda data={jalurLain} /> : null}</div> : null}
                </div>
```

dengan:

```tsx
                    </div>

                    {samping ? (
                        <div className="mt-8 space-y-8 lg:mt-0">
                            {jalurLain ? <JalurUntukAnda data={jalurLain} /> : null}
                            {tangga ? <TanggaTanjakan tangga={tangga} /> : null}
                        </div>
                    ) : null}
                </div>
```

- [ ] **Step 4: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Http/Controllers/DashboardController.php app/Services/Dashboard/TanggaTanjakan.php tests/Feature/DashboardLadderTest.php`, lalu `php artisan test --compact --filter='DashboardLadder|DashboardPhase|PageQueryBudget|NoDeadViewData|TapTarget'`
Expected: PASS. Pada simulasi rencana: 38 test lulus.

- [ ] **Step 5: Suite, tipe, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 1.968 test, 1.966 lulus, 2 dilewati.

Run: `npx tsc --noEmit`
Expected: keluar tanpa keluaran galat.

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `php artisan test --compact --testsuite=Pratinjau`
Expected: build `✓ built`; Pratinjau lulus. Pada simulasi rencana: 22 test lulus.

- [ ] **Step 6: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 7: Titipan W3 (rute simpan lama, laporan komunitas, sel kecocokan Bandingkan)

`HANDOVER.md` bagian 0 "Ke W5"; ruling W3-F3 dan W5-R23. Rute `trails.timbang` dan `ConsiderationController::toggle()`
dihapus karena Beranda tidak memakainya dan tombol simpan sudah memakai `trails.saved.store` serta
`trails.saved.remove`; test yang memakai rute lama dipindah ke rute itu dengan niat yang sama. Laporan di kartu
"Laporan kondisi dari komunitas" dipisah garis, bukan kartu di dalam kartu. Sel kecocokan Bandingkan tidak mengulang
"Kecocokan dasar" dan tombol "Kenapa?" menyebut nama jalurnya untuk pembaca layar.

**Files:**
- Modify: `app/Http/Controllers/Trails/ConsiderationController.php`, `routes/web.php`, `resources/js/components/trails/FitLine.tsx`, `resources/js/components/trails/banding/TabelBanding.tsx`, `resources/js/components/trails/rinci/LaporanKomunitas.tsx`
- Test: `tests/Feature/ConsiderationTest.php`, `tests/Feature/SavedRoutesTest.php`, `tests/Feature/ComparisonAsCostTest.php`, `tests/Feature/CommunityPhotoTest.php`

**Interfaces:**
- Consumes: rute `trails.saved.store` dan `trails.saved.remove`; `ConsiderationService::toggle()` tetap ada dan tetap dipakai test sebagai pembantu.
- Produces: rute `trails.timbang` tidak ada lagi (keluaran Wayfinder dibangkitkan ulang oleh build); `FitLine({ fit, trailId, nama })`.

- [ ] **Step 1: Tulis test yang gagal.**

`tests/Feature/CommunityPhotoTest.php`:

1. Ganti:

```php
        $this->assertStringNotContainsString('<img', file_get_contents(resource_path('js/pages/Trails/Show.tsx')));
    }
```

dengan:

```php
        $this->assertStringNotContainsString('<img', file_get_contents(resource_path('js/pages/Trails/Show.tsx')));
        // Titipan W3 ke W5: laporan di dalam kartu dipisah garis, bukan dibingkai kartu lagi.
        $this->assertStringContainsString('<ul className="divide-y divide-subtle">', $kartu);
        $this->assertStringNotContainsString('rounded-control border border-subtle px-3 py-2', $kartu);
    }
```

`tests/Feature/ComparisonAsCostTest.php`:

1. Ganti:

```php
    /**
     * Jalur berjarak nol tidak menjatuhkan halaman perbandingan.
```

dengan:

```php
    /**
     * Titipan W3 ke W5: "Kecocokan dasar" adalah kepala barisnya, jadi tidak diulang di setiap
     * sel, dan "Kenapa?" menyebut nama jalurnya untuk pembaca layar.
     */
    public function test_the_fit_cell_does_not_repeat_its_row_and_names_its_trail(): void
    {
        $sel = File::get(resource_path('js/components/trails/FitLine.tsx'));

        $this->assertStringNotContainsString("'Kecocokan dasar'", $sel);
        $this->assertStringContainsString('<span className="sr-only"> {nama}</span>', $sel);
        $this->assertStringContainsString('<FitLine fit={j.fit} trailId={j.id} nama={j.name} />', File::get(resource_path('js/components/trails/banding/TabelBanding.tsx')));
    }

    /**
     * Jalur berjarak nol tidak menjatuhkan halaman perbandingan.
```

`tests/Feature/ConsiderationTest.php`:

1. Ganti:

```php
use App\Enums\ConsiderationOutcome;
use App\Models\Mountain;
```

dengan:

```php
use App\Enums\ConsiderationOutcome;
use App\Http\Controllers\Trails\ConsiderationController;
use App\Models\Mountain;
```

2. Ganti:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
```

dengan:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;
```

3. Ganti:

```php
        return Trail::factory()->for(Mountain::factory()->create())->published()->create();
    }
```

dengan:

```php
        return Trail::factory()->for(Mountain::factory()->create())->published()->create();
    }

    /**
     * Titipan W3 (ruling W3-F3): sakelar simpan lama tidak lagi punya pemanggil sejak tombol
     * simpan memakai trails.saved.store dan trails.saved.remove, dan Beranda W5 tidak
     * memakainya. Rute dan aksinya dihapus supaya tidak ada jalan tulis kedua ke simpanan.
     */
    public function test_the_old_save_toggle_route_is_gone(): void
    {
        $this->assertFalse(Route::has('trails.timbang'));
        $this->assertFalse(method_exists(ConsiderationController::class, 'toggle'));
    }
```

4. Ganti:

```php
    }

    public function test_timbang_refuses_an_unpublished_trail(): void
    {
```

dengan:

```php
    }

    public function test_saving_refuses_an_unpublished_trail(): void
    {
```

5. Ganti:

```php
        $draft = Trail::factory()->for(Mountain::factory()->create())->unpublished()->create();

        $this->actingAs($user)->post(route('trails.timbang', $draft->id))->assertNotFound();

        $this->assertDatabaseMissing('trail_considerations', ['user_id' => $user->id, 'trail_id' => $draft->id]);
```

dengan:

```php
        $draft = Trail::factory()->for(Mountain::factory()->create())->unpublished()->create();

        $this->actingAs($user)->post(route('trails.saved.store', $draft->id))->assertNotFound();

        $this->assertDatabaseMissing('trail_considerations', ['user_id' => $user->id, 'trail_id' => $draft->id]);
```

6. Ganti:

```php
            ->from(route('trails.index'))
            ->post(route('trails.timbang', $ketiga->id))
            ->assertRedirect(route('trails.index'))
```

dengan:

```php
            ->from(route('trails.index'))
            ->post(route('trails.saved.store', $ketiga->id))
            ->assertRedirect(route('trails.index'))
```

7. Ganti:

```php
        $trail = Trail::factory()->for(Mountain::factory()->create())->published()->create(['name' => 'Jalur Kaliurang']);

        $this->actingAs($user)->from(route('trails.index'))->post(route('trails.timbang', $trail->id))->assertRedirect(route('trails.index'));

        $this->assertDatabaseHas('trail_considerations', ['user_id' => $user->id, 'trail_id' => $trail->id]);
```

dengan:

```php
        $trail = Trail::factory()->for(Mountain::factory()->create())->published()->create(['name' => 'Jalur Kaliurang']);

        $this->actingAs($user)->from(route('trails.index'))->post(route('trails.saved.store', $trail->id))->assertRedirect(route('trails.index'));

        $this->assertDatabaseHas('trail_considerations', ['user_id' => $user->id, 'trail_id' => $trail->id]);
```

8. Ganti:

```php
        app(ConsiderationService::class)->toggle($user, $trail);

        $this->actingAs($user)->from(route('trails.index'))->post(route('trails.timbang', $trail->id))->assertRedirect(route('trails.index'));

        $this->assertDatabaseMissing('trail_considerations', ['user_id' => $user->id, 'trail_id' => $trail->id]);
```

dengan:

```php
        app(ConsiderationService::class)->toggle($user, $trail);

        $this->actingAs($user)->from(route('trails.index'))->delete(route('trails.saved.remove', $trail->id))->assertRedirect(route('trails.index'));

        $this->assertDatabaseMissing('trail_considerations', ['user_id' => $user->id, 'trail_id' => $trail->id]);
```

`tests/Feature/SavedRoutesTest.php`:

1. Ganti:

```php
        $trail = $this->jalur(['name' => 'Jalur Wekas']);
        $this->actingAs($user)->post(route('trails.timbang', $trail->id));

        $this->actingAs($user)->post(route('logout'));
```

dengan:

```php
        $trail = $this->jalur(['name' => 'Jalur Wekas']);
        $this->actingAs($user)->post(route('trails.saved.store', $trail->id));

        $this->actingAs($user)->post(route('logout'));
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='Consideration|SavedRoutes|SavedUndo|ComparisonAsCost|CommunityPhoto|ReportThanks|TrailCard'`
Expected: FAIL. Pada simulasi rencana: 96 test, 93 lulus, 3 gagal: `ConsiderationTest::test_the_old_save_toggle_route_is_gone` (rute `trails.timbang` masih ada), `ComparisonAsCostTest::test_the_fit_cell_does_not_repeat_its_row_and_names_its_trail` (`FitLine` masih menulis "Kecocokan dasar"), dan `CommunityPhotoTest::test_the_photos_are_marked_as_community_input_not_official` (daftar laporan belum dipisah garis). Test yang dipindah ke `trails.saved.store` dan `trails.saved.remove` sudah lulus karena rutenya sudah ada.

- [ ] **Step 3: Tulis implementasinya.**

`app/Http/Controllers/Trails/ConsiderationController.php`:

1. Ganti:

```php
    private const KUNCI_PEMULIHAN = 'simpanan-pulihkan.';

    public function toggle(Request $request, int $trail, ConsiderationService $consideration): RedirectResponse
    {
        $jalur = Trail::published()->findOrFail($trail);

        $hasil = $consideration->toggle($request->user(), $jalur);

        if ($hasil === ConsiderationOutcome::DITOLAK) {
            return back()->with(
                'timbangan-penuh',
                ConsiderationOutcome::DITOLAK->label().' ('.$consideration->batasSimpanan().' jalur). Hapus satu dulu sebelum menyimpan jalur lain.'
            );
        }

        return back()->with('pengumuman', $jalur->name.' '.Str::lcfirst($hasil->label()).'.');
    }

    /**
```

dengan:

```php
    private const KUNCI_PEMULIHAN = 'simpanan-pulihkan.';

    /**
```

2. Ganti:

```php
     * Simpan idempoten: tombol simpan kartu dan bar aksi, dan Urungkan sesudah "Hapus dari
     * simpanan" yang mengirim kembali catatannya. Hanya jalur terbit, sama dengan toggle();
     * simpanan yang penuh ditolak dengan pesan yang sama. Baris yang sudah ada (misalnya
     * dari tab atau perangkat lain) di-flash terpisah lewat simpanan-sudah-ada, supaya
```

dengan:

```php
     * Simpan idempoten: tombol simpan kartu dan bar aksi, dan Urungkan sesudah "Hapus dari
     * simpanan" yang mengirim kembali catatannya. Hanya jalur terbit; simpanan yang penuh
     * ditolak dengan pesan yang dibaca TombolSimpan. Baris yang sudah ada (misalnya
     * dari tab atau perangkat lain) di-flash terpisah lewat simpanan-sudah-ada, supaya
```

`resources/js/components/trails/FitLine.tsx`:

1. Ganti:

```tsx
/**
 * Label kecocokan beserta satu alasan, untuk tingkat daftar (§89: Apa ini, Cocokkah,
 * Mengapa). Label tidak pernah berdiri sendiri: "Kenapa?" membuka alasannya di tempat,
 * tanpa bolak-balik ke server, karena alasannya sudah datang bersama barisnya.
 */
export function FitLine({ fit, trailId }: { fit: FitRingkas; trailId: number }) {
    const [terbuka, setTerbuka] = useState(false);
```

dengan:

```tsx
/**
 * Label kecocokan beserta satu alasan, untuk sel tabel Bandingkan (§89: Apa ini, Cocokkah,
 * Mengapa). Label tidak pernah berdiri sendiri: "Kenapa?" membuka alasannya di tempat,
 * tanpa bolak-balik ke server, karena alasannya sudah datang bersama barisnya.
 *
 * Kata "Kecocokan dasar" sudah menjadi kepala barisnya, jadi tidak diulang di setiap sel, dan
 * tombolnya menyebut nama jalurnya untuk pembaca layar, seperti kartu Jelajah (titipan W3 ke
 * W5).
 */
export function FitLine({ fit, trailId, nama }: { fit: FitRingkas; trailId: number; nama: string }) {
    const [terbuka, setTerbuka] = useState(false);
```

2. Ganti:

```tsx
            </span>
            <span className="text-xs text-muted">{fit.denganRencana ? 'untuk rencana ini' : 'Kecocokan dasar'}</span>
            <Button size="sm" variant="ghost" aria-controls={idAlasan} aria-expanded={terbuka} onClick={() => setTerbuka((v) => !v)}>
                {terbuka ? 'Sembunyikan alasan' : 'Kenapa?'}
            </Button>
```

dengan:

```tsx
            </span>
            <Button size="sm" variant="ghost" aria-controls={idAlasan} aria-expanded={terbuka} onClick={() => setTerbuka((v) => !v)}>
                {terbuka ? 'Sembunyikan alasan' : 'Kenapa?'}
                <span className="sr-only"> {nama}</span>
            </Button>
```

`resources/js/components/trails/banding/TabelBanding.tsx`:

1. Ganti:

```tsx
                            <td key={j.id} className={sel}>
                                {j.fit ? <FitLine fit={j.fit} trailId={j.id} /> : <LabelKecocokan nilai={null} label={kataBelumDinilai} />}
                            </td>
```

dengan:

```tsx
                            <td key={j.id} className={sel}>
                                {j.fit ? <FitLine fit={j.fit} trailId={j.id} nama={j.name} /> : <LabelKecocokan nilai={null} label={kataBelumDinilai} />}
                            </td>
```

`resources/js/components/trails/rinci/LaporanKomunitas.tsx`:

1. Ganti:

```tsx
 * pada laporannya sendiri. Foto tetap di dalam laporannya, bukan gambar utama halaman
 * (R-017).
 */
```

dengan:

```tsx
 * pada laporannya sendiri. Foto tetap di dalam laporannya, bukan gambar utama halaman
 * (R-017). Laporan dipisah garis, bukan dibingkai kartu di dalam kartu (titipan W3 ke W5).
 */
```

2. Ganti:

```tsx
            {komunitas.available ? (
                <ul className="space-y-3">
                    {komunitas.reports.map((r) => (
                        <li key={r.id} className="rounded-control border border-subtle px-3 py-2 text-sm">
                            <div className="flex flex-wrap gap-2">
```

dengan:

```tsx
            {komunitas.available ? (
                <ul className="divide-y divide-subtle">
                    {komunitas.reports.map((r) => (
                        <li key={r.id} className="py-3 text-sm first:pt-0 last:pb-0">
                            <div className="flex flex-wrap gap-2">
```

`routes/web.php`:

1. Ganti:

```php
    Route::get('trails', TrailIndexController::class)->name('trails.index');
    Route::post('trails/{trail}/timbang', [ConsiderationController::class, 'toggle'])->name('trails.timbang');
    Route::get('trails/saved', SavedRoutesController::class)->name('trails.saved');
```

dengan:

```php
    Route::get('trails', TrailIndexController::class)->name('trails.index');
    Route::get('trails/saved', SavedRoutesController::class)->name('trails.saved');
```

- [ ] **Step 4: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Http/Controllers/Trails/ConsiderationController.php routes/web.php tests/Feature/CommunityPhotoTest.php tests/Feature/ComparisonAsCostTest.php tests/Feature/ConsiderationTest.php tests/Feature/SavedRoutesTest.php`, lalu `php artisan test --compact --filter='Consideration|SavedRoutes|SavedUndo|ComparisonAsCost|CommunityPhoto|ReportThanks|TrailCard'`
Expected: PASS. Pada simulasi rencana: 96 test lulus.

- [ ] **Step 5: Suite, tipe, format, dan pratinjau.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build` (membangkitkan ulang rute Wayfinder tanpa `trails.timbang`), lalu `npx tsc --noEmit`
Expected: build `✓ built`; tsc keluar tanpa keluaran galat.

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 1.970 test, 1.968 lulus, 2 dilewati.

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `php artisan test --compact --testsuite=Pratinjau`
Expected: Pratinjau lulus termasuk `test_detail_jalur` dan `test_bandingkan`. Pada simulasi rencana: 22 test lulus.

- [ ] **Step 6: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 8: Dokumen dan governance record GR-06

Induk W5 "Selesai bila"; CLAUDE.md bagian 3 (perubahan `docs/PRD.md` wajib bergovernance record, `docs/PRD.md`
§130 dan §146); ruling W5-R1 (jangkar dokumen yang sama di tree dasar dan sesudah W4 Tugas 7). GR-06 mencatat FR-24
yang bertambah, Beranda berfase, dan urutan alasan kartu; §149 menyebut pengingat hari buka izin; `DESIGN.md`,
`docs/ARCHITECTURE.md`, `CONTRIBUTING.md`, dan tiga spesifikasi fitur menyebut keadaan sesudah W5.

**Files:**
- Modify: `CONTRIBUTING.md`, `DESIGN.md`, `docs/ARCHITECTURE.md`, `docs/PRD.md`, `docs/fitur/01-jelajah-rute.md`, `docs/fitur/04-perjalanan-persiapan.md`, `docs/fitur/05-kabar-kondisi.md`

**Interfaces:**
- Consumes: keluaran Tugas 1 sampai 7 (nama kelas, config, rute, test).
- Produces: GR-06 di `docs/PRD.md` §146 (sebelum "### Log keputusan"), kalimat FR-24 di §149, penunjuk "governance record berikutnya GR-07".

- [ ] **Step 1: Tidak ada test baru.** Tugas ini hanya dokumen. Test penjaga dokumen yang sudah ada dijalankan di
Step 2 dan Step 4 sebagai pembanding.

- [ ] **Step 2: Jalankan test penjaga dokumen sebelum menyunting.**

Run: `php artisan test --compact --filter='PrdReferenceIntegrity|DesignDirection|TeamDocsIndex|RepoHygiene|SkillGovernance|CiWorkflows|SemanticToken|ThirdPartyLicense|SecretsNotInRepo|LocationPrivacy'`
Expected: PASS (tidak ada test baru). Pada simulasi rencana: 102 test lulus sebelum dokumen disunting.

- [ ] **Step 3: Sunting dokumen.**

`docs/PRD.md`:

1. Ganti:

```markdown
aturan bisnis baru memakai nomor setelah BR-16 (belum ada), governance record berikutnya GR-05.
```

dengan:

```markdown
aturan bisnis baru memakai nomor setelah BR-16 (belum ada), governance record berikutnya GR-07.
```

2. Ganti:

```markdown
Pengguna dapat melihat pengingat yang akan datang beserta statusnya (menunggu atau terkirim).
```

dengan:

```markdown
Pengguna dapat melihat pengingat yang akan datang beserta statusnya (menunggu atau terkirim). Bila aturan izin jalurnya mencatat kapan pemesanan dibuka, pengingat yang sama juga mengirim satu surel pada hari itu, kecuali butir izin di persiapan sudah dikonfirmasi atau ditandai tidak berlaku; surel itu menautkan sistem resmi dan tidak menjual izin (GR-06).
```

3. Ganti:

```markdown
### Log keputusan
```

dengan:

```markdown
### GR-06: Beranda berfase dan pengingat hari pemesanan izin dibuka (gelombang W5)

**Requirement changed.**

- FR-24 (§149) bertambah satu pengingat: bila aturan izin jalurnya mencatat kapan pemesanan dibuka
  (`booking_opens_days_before`), pengingat trip yang menyala juga mengirim satu surel pada hari itu pukul 07.00 zona
  gunungnya. Surel itu tidak dikirim bila butir izin di persiapan sudah dikonfirmasi atau ditandai tidak berlaku,
  menautkan sistem resmi, dan tidak menjual izin. Hari buka yang sudah lewat tidak dijadwalkan dan tidak disebut
  terlewat. Sakelar dan persetujuannya tetap satu untuk ketiga pengingat.
- Beranda (§145) menjadi lima fase dengan satu aksi utama per fase (spec v3 bagian 6.1, DK-45): B0 profil belum
  diisi, B1 tanpa trip mendatang, B2 trip mendatang, B3 pendakian berlangsung, dan B4 pendakian selesai paling lama
  tujuh hari yang belum dilaporkan. Urutan kepentingannya B3, B2, B4, B0, lalu B1.
- Satu kalimat alasan kecocokan (§89) untuk jalur berlabel Cocok kini alasan terkuatnya, kalimat pertama
  `why_it_fits` dari `RecommendationExplanationService`; label lain tetap mendahulukan faktor lemah (ruling W3-C7,
  diputuskan W5). Kartu "Jalur lain untuk Anda" tidak mengulang kalimat yang sudah dipakai kartu sebelumnya.

**Reason.** Spec v3 bagian 6.1 menetapkan aksi "Ingatkan saat dibuka" untuk jendela pemesanan yang belum dibuka;
tanpa pengingat pada hari itu, aksinya hanya janji. Kalimat perhatian di kartu berlabel Cocok terbaca bertentangan
dengan labelnya (W3-C7).

**Impact.** Pendaki yang menyalakan pengingat dapat menerima satu surel tambahan per trip. Kartu Jelajah, rincian
jalur, dan Beranda menulis alasan positif untuk jalur Cocok; faktor lemahnya tetap disebut di "Kenapa?"
(`what_to_watch`).

**Affected modules.** Trip (pengingat), Beranda, dan pemilihan kalimat alasan Recommendation (mesinnya tidak berubah).
Tidak ada perubahan pada Condition maupun Administration.

**Security impact.** Tidak ada rute baru: "Ingatkan saat dibuka" memakai rute pengingat trip yang sudah ada (Policy
pemilik, batas laju `personal_writes_per_minute`). Rute sakelar simpan lama `trails.timbang` dihapus karena tidak
lagi punya pemanggil.

**Data impact.** Tidak ada perubahan skema dan tidak ada migrasi: `trip_reminders.jenis` menerima nilai `IZIN`
(kolom string 8). Trip yang pengingatnya sudah menyala sebelum rilis baru mendapat jadwal hari buka izin pada
sinkronisasi berikutnya (ubah rencana atau sakelar pengingat); tidak ada pengisian mundur otomatis.

**Scope impact.** Tidak ada fitur di luar spec v3 gelombang W5; batas MVP dan MVP+ tetap (§105, §106).

**Keputusan terbuka untuk pemilik.**

- Apakah pengingat hari pemesanan dibuka perlu sakelar sendiri, terpisah dari pengingat H-3 dan H-1.
- Apakah trip lama yang pengingatnya sudah menyala perlu dijadwalkan ulang sekali saat rilis.

### Log keputusan
```

`DESIGN.md`:

1. Ganti:

```markdown
**Fokus tidak pernah jatuh ke body.**
```

dengan:

```markdown
**Beranda** (W5, spec v3 bagian 6.1, prototype 01). Server memilih satu dari lima fase (B0 profil belum diisi, B1
tanpa trip mendatang, B2 trip mendatang, B3 sedang mendaki, B4 pendakian selesai yang belum dilaporkan), dan setiap
fase membawa tepat satu aksi utama selebar kartu di ponsel. Kartu perjalanan B2 memakai garis profil jalur sebagai
visual data selama foto berlisensi belum ada (DK-37), empat langkah sebagai bilah dengan centang sesudah namanya, dan
persiapan kritis sebagai ruas per butir ditambah kalimat "N dari M". Tenggat terdekat memakai penanda kalender yang
disembunyikan dari pembaca layar, dengan zona gunung disebut sekali di samping judulnya. Kabar memisah status resmi
(garis padat merek hanya untuk sumber OFFICIAL) dari laporan komunitas (kartu bergaris putus-putus yang sama dengan
halaman gunung). "Jalur lain untuk Anda" bergulir menyamping di ponsel dan pindah ke kolom samping 22rem mulai 1024 px
bersama tangga tanjakan; tanpa isi samping, halaman tetap satu kolom. Daftar yang bergulir menyamping diberi
`relative`, supaya teks sr-only di dalamnya tidak melebarkan halaman.

**Fokus tidak pernah jatuh ke body.**
```

`docs/ARCHITECTURE.md`:

1. Ganti:

```markdown
| `AuditLogService`, `AnalyticsRecorder` | Jejak audit dan funnel |
```

dengan:

```markdown
| `Dashboard\RingkasanDasbor`, `Dashboard\TenggatTrip`, `Dashboard\KabarBeranda`, `Dashboard\JalurUntukAnda`, `Dashboard\TanggaTanjakan` | Beranda berfase (W5): `RingkasanDasbor` memilih fase B0 sampai B4 sekali per permintaan (`KonteksBeranda`) dan menyusun kartu fase dengan satu aksi utama; kelas bagian lain membaca konteks yang sama. Setiap bagian prop malas, jadi muat ulang parsial lembar Pengingat tidak menghitung Beranda |
| `Trips\TenggatIzin` | Judul, tanggal, dan kalimat jendela pemesanan izin satu trip, dipakai lembar Pengingat dan Tenggat terdekat Beranda |
| `AuditLogService`, `AnalyticsRecorder` | Jejak audit dan funnel |
```

2. Ganti:

```markdown
| Sumber tile peta dan atribusinya |
```

dengan:

```markdown
| Lama ajakan melapor di Beranda dan batas kandidat "Jalur lain untuk Anda" | `config/hiking.php` → `beranda` (`hari_ajakan_lapor`, `batas_kandidat`) |
| Sumber tile peta dan atribusinya |
```

`docs/fitur/04-perjalanan-persiapan.md`:

1. Ganti:

```markdown
- **Beranda:** kartu perjalanan berikutnya dan tenggat (W5, DK-45).
```

dengan:

```markdown
- **Beranda:** kartu perjalanan berikutnya dengan satu aksi utama, tenggat terdekat, dan aksi "Ingatkan saat dibuka" untuk jendela izin yang belum dibuka (W5, DK-45, GR-06).
```

2. Ganti:

```markdown
| `trip_reminders` | `jenis` (H-3, H-1), `dijadwalkan_untuk`,
```

dengan:

```markdown
| `trip_reminders` | `jenis` (H-3, H-1, dan IZIN untuk hari pemesanan izin dibuka, GR-06), `dijadwalkan_untuk`,
```

`docs/fitur/01-jelajah-rute.md`:

1. Ganti:

```markdown
sakelar `trails.timbang` tidak lagi dipakai kartu (ditunda ke W5) |
```

dengan:

```markdown
sakelar `trails.timbang` dihapus di W5 karena tidak lagi punya pemanggil (ruling W3-F3) |
```

`docs/fitur/05-kabar-kondisi.md`:

1. Ganti:

```markdown
kabar gunung yang diikuti di Beranda (W5);
```

dengan:

```markdown
kabar gunung yang diikuti di Beranda (W5: perubahan status resmi terbaru dan satu laporan komunitas terbaru, terpisah menurut asalnya, `App\Services\Dashboard\KabarBeranda`);
```

`CONTRIBUTING.md`:

1. Ganti:

```markdown
`dasbor.html` (Beranda), 
```

dengan:

```markdown
`dasbor.html` (Beranda fase B2; fase lain di `dasbor-b0.html`, `dasbor-b1.html`, `dasbor-b3.html`, dan `dasbor-b4.html`), 
```

- [ ] **Step 4: Jalankan test penjaga dokumen lagi.**

Run: `php artisan test --compact --filter='PrdReferenceIntegrity|DesignDirection|TeamDocsIndex|RepoHygiene|SkillGovernance|CiWorkflows|SemanticToken|ThirdPartyLicense|SecretsNotInRepo|LocationPrivacy'`
Expected: PASS. Pada simulasi rencana: 102 test lulus.

Run: `LC_ALL=C grep -lP '\xE2\x80\x94' CONTRIBUTING.md DESIGN.md docs/ARCHITECTURE.md docs/PRD.md docs/fitur/01-jelajah-rute.md docs/fitur/04-perjalanan-persiapan.md docs/fitur/05-kabar-kondisi.md`
Expected: tanpa keluaran (tanpa em dash).

- [ ] **Step 5: Suite.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 1.970 test, 1.968 lulus, 2 dilewati.

- [ ] **Step 6: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 9: Verifikasi gelombang (koordinator)

Induk W5 "Selesai bila" (aturan penutup gelombang untuk kelima fase, masing-masing dengan tangkapan); ruling W5-R24.
Dikerjakan koordinator sesudah Tugas 1 sampai 8 selesai dan lolos tinjauan per tugas. Setiap klaim di laporan
disertai keluaran perintah, jalur berkas, atau tangkapan (R-023). Angka "pada simulasi rencana" adalah hasil
menjalankan kode rencana ini di salinan tree `b7e54014`, bukan hasil di repo, dan menjadi pembanding; selisih dari W4
Tugas 7, 8, dan gelombang perbaikan akhir W4 ditambahkan koordinator. Harness pratinjau memakai port 8100 milik
koordinator.

**Files:**
- Create: `docs/desain/tangkapan/w5/*.webp` (tangkapan), `docs/desain/tangkapan/w5/README.md` (perbandingan)
- Modify: `HANDOVER.md` (bagian 0: baris "Terakhir diperbarui" dan subbagian W5), `.superpowers/sdd/2026-09-26-memount-v3-program.md` (baris W5; berkas ini diabaikan git)

**Interfaces:**
- Consumes: seluruh keluaran Tugas 1 sampai 8; harness pratinjau (diperluas Tugas 2 dan 4); gambar acuan `docs/desain/prototype/acuan/01-beranda.png`.
- Produces: bukti akhir W5, daftar izin akhir yang diperbarui, dan titik lanjut W6.

- [ ] **Step 1: Catat keadaan kerja.**

Run: `git status --short | wc -l; git diff --stat | tail -1`
Expected: selisih terhadap catatan akhir W4 cocok dengan gabungan daftar Files Tugas 1 sampai 8. Pada simulasi rencana, Tugas 1 sampai 8 menambah 20 berkas dan mengubah 35 terhadap tree `b7e54014`; tidak ada berkas yang dihapus.

- [ ] **Step 2: Suite penuh, tipe, format.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 1.970 test, 1.968 lulus, 2 dilewati; di repo nyata ditambah selisih W4 Tugas 7, 8, dan gelombang perbaikan akhir W4.

Run: `php artisan test --compact`
Expected: 0 gagal. Pada simulasi rencana: 2.002 test, 1.968 lulus, 34 dilewati (32 test Spatial tanpa `SPATIAL_TEST_DSN` dan 2 yang sudah dilewati di dasar).

Run: `npx tsc --noEmit`
Expected: keluar tanpa keluaran galat.

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

- [ ] **Step 3: Build dan ukuran bundel.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`
Expected: `✓ built` dengan peringatan ukuran potongan maplibre-gl yang sama seperti W4. Pada simulasi rencana: `app` JS 355,97 kB (gzip 111,90; dasar 355,91 dan 111,87), CSS 69,69 kB (gzip 13,70; dasar 65,68 dan 12,88), potongan `Dashboard` 18,00 kB (gzip 4,89; dasar 4,06 dan 1,54).

- [ ] **Step 4: Suite pratinjau dan server statis.**

Run: `php artisan test --compact --testsuite=Pratinjau`
Expected: PASS. Pada simulasi rencana: 22 test lulus.

Run (di latar belakang): `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php -S 127.0.0.1:8100 -t public tests/Pratinjau/statis.php`
Expected: server statis tanpa basis data. Buka `http://127.0.0.1:8100/_pratinjau/<nama>.html`, dan hentikan server
sesudah Step 8.

- [ ] **Step 5: Tangkapan.** Pakai Chrome lewat MCP chrome-devtools. Ukuran 390 x 844 memakai emulasi perangkat
seluler dengan sentuh dan rasio piksel 1; untuk halaman lebih tinggi dari layar, atur tinggi viewport sama dengan
`document.documentElement.scrollHeight` supaya bilah tab tetap di dasar gambar. Ukuran 1440 x 900 rasio piksel 1,
tema terang dan gelap. Simpan sebagai WebP di `docs/desain/tangkapan/w5/`, masing-masing 390 dan 1440, terang dan
gelap, untuk `dasbor` (B2, acuan 01), `dasbor-b0`, `dasbor-b1`, `dasbor-b3`, dan `dasbor-b4` (tanpa acuan;
bandingkan susunannya dengan 01).

- [ ] **Step 6: Perbandingan tertulis.** Tulis `docs/desain/tangkapan/w5/README.md` dengan susunan README tangkapan
W4: paragraf cara pengambilan, satu paragraf cakupan W5, dan tabel
`| Tangkapan | Gambar acuan | Yang sudah sesuai | Yang belum sesuai dan gelombang yang mengubahnya |`. Pemeriksaan
perencanaan menemukan hal-hal di bawah ini. Pastikan masing-masing pada tangkapan, jangan disalin tanpa melihat.

- **Sudah sesuai (B2 390 px, acuan 01).** Sapaan waktu dengan nama, ringkasan "Gede via Cibodas tinggal 15 hari lagi.
  10 butir kritis belum dikonfirmasi.", kartu dengan lencana jenis dan H-15, nama trip, tanggal dengan "sampai", jam
  WIB, peserta, garis profil dan tiga angka, empat bilah langkah (Jalur dan Rencana selesai dengan centang, Persiapan
  bilah kuning pos, Cek belum), persiapan kritis "3 dari 13" dengan ruas dan tiga nama butir, satu tombol "Lanjutkan
  persiapan"; "Tenggat terdekat" dengan penanda kalender, "Waktu gunung, WIB", pemesanan izin ditutup (H-3), prakiraan
  BMKG untuk tanggal mulai trip (hari pengambilan ditambah 15 hari; "22 Okt" pada simulasi 7 Oktober), dan dua surel
  pengingat; "Kabar Gunung Gede yang Anda ikuti" dengan "Semua kabar", dua status
  dengan sumbernya, kalimat R-012, dan laporan komunitas bergaris putus-putus; "Jalur lain untuk Anda" tiga kartu
  bergulir dengan label, angka, dan alasan yang tidak berulang; "Tangga tanjakan Anda" Andong 380 m, Batur 600 m, Prau
  700 m, Gede 1.650 m rencana bergaris putus-putus, dengan kalimat perbandingan dan BR-02; kalimat dukungan keputusan.
- **Sudah sesuai (1440 px).** Kolom utama dengan kartu fase, tenggat, dan kabar; kolom samping 22rem dengan jalur lain
  dan tangga; B0 dan B3 satu kolom.
- **Belum sesuai.**
  - Foto hero kartu perjalanan menunggu foto berlisensi W8 (DK-37); visual datanya garis profil (W5-R11).
  - Avatar foto, titik lonceng berwarna, dan baris tanggal-jam kapital di atas sapaan prototype tidak dibangun; sapaan
    memakai nama lengkap (W5-R20).
  - Tangga tanpa jalur latar dan tanpa catatan bulanan (W5-R18).
  - Alasan kartu tawaran prototype ("Tanjakannya dekat dengan Prau via Patak Banteng (700 m) yang sudah Anda daki")
    membandingkan dengan riwayat; alasan W5 berasal dari faktor mesin (`why_it_fits`), tanpa perbandingan riwayat.
  - Kabar data contoh berbunyi "Dicatat 0 detik yang lalu" karena waktu seeding (OPEN ISSUE).
  - Bilah tab memotong label di 320 px (OPEN ISSUE W2).

- [ ] **Step 7: Ukur reflow dan lapisan.** Dengan `evaluate_script` di pratinjau:

1. `dasbor.html`, `dasbor-b0.html`, `dasbor-b1.html`, `dasbor-b3.html`, dan `dasbor-b4.html` di 320 x 568 dan 390 x
   844: `document.documentElement.scrollWidth` sama dengan `clientWidth`. Pada simulasi rencana: sama untuk kelima halaman di kedua ukuran.
2. Empat langkah kartu B2 di 320 px: lebar `ol[aria-label="Langkah trip"] li` muat dalam satu baris tanpa kata
   terpotong. Pada simulasi rencana: 47, 73, 62, dan 47 px dalam satu baris.
3. Tinggi kartu mengikuti isinya (DK-31): untuk setiap `article` dan `section` kartu Beranda, `scrollHeight` tidak
   melebihi `clientHeight` (tanpa isi terpotong) dan tidak ada `height` tetap. Pada simulasi rencana: B2 9 kartu, B1 7, B4 7, B0 1, dan B3 1, tanpa isi terpotong dan tanpa tinggi tetap.
4. Kolom samping 1440 px: lebar 352 px (22rem) dan kolom utama sisanya. Pada simulasi rencana: 704 dan 352 px pada B2, B1, dan B4; B0 dan B3 satu kolom.

- [ ] **Step 8: Jalur papan ketik** (e2e menunggu Playwright, Q17). Pada pratinjau, tanpa tetikus:

1. **390 px, B2.** Tab dari awal: tautan lewati, lonceng Pengingat, avatar, nama trip, "Lanjutkan persiapan", lalu
   "Buka sistem resmi" bila ada, "Semua kabar", "Ikuti Gunung Gede" bila tampil, tautan nama jalur di setiap kartu
   tawaran (kartunya bergulir masuk pandangan), dan "Lihat progres", dengan cincin fokus terlihat dan tidak tertutup
   bilah tab.
2. **390 px, B0, B1, B3, B4.** Satu tombol utama per fase tercapai sebagai fokus pertama di isi halaman sesudah
   kepala; "Lihat jalur tanpa penilaian", "Ubah tanggal atau batalkan", dan "Lihat ringkasan pendakian" tercapai
   sesudahnya.
3. **1440 px, B2.** Urutan fokus kolom utama lalu kolom samping.

Simpan satu tangkapan fokus di "Lanjutkan persiapan" sebagai `fokus-aksi-390-light.webp`. Hentikan server port 8100.

- [ ] **Step 9: Delivery Gate antislop, empat blok.** Tulis laporan PASS/FAIL, satu baris per butir, dan setiap PASS
disertai bukti:

- **Salinan:** R-02 tanpa em dash. Run: `LC_ALL=C grep -rlP '\xE2\x80\x94' app resources tests database config DESIGN.md CONTRIBUTING.md docs/ARCHITECTURE.md docs/PRD.md docs/fitur`. Expected: tanpa keluaran. Teks berbahasa Indonesia dengan "Anda", tanpa "aman" atau "layak berangkat" sebagai klaim, dan setiap keadaan kosong menyebut siapa yang ditunggu.
- **Desain:** satu tombol penuh per fase; hierarki lewat bobot; tanpa kartu di dalam kartu (laporan komunitas sudah dipisah garis); motif garis profil dan bilah langkah.
- **Interaksi:** R-26 setiap kendali berfungsi (setiap aksi utama kelima fase, "Ingatkan saat dibuka" yang menyalakan pengingat dan mendarat di ruang kerja dengan pesan, "Ikuti Gunung Gede", "Semua kabar", "Buka sistem resmi", kartu tawaran, "Lihat progres", "Ubah tanggal atau batalkan"); R-27 keadaan pertama kali, kosong, menunggu, dan memproses; R-32 jalur papan ketik Step 8.
- **Kebenaran:** tanpa skor, persen, atau bobot angka; tanpa "Siap berangkat"; status bersumber dengan kalimat R-012; prakiraan hanya disebut hari terbitnya; jam pengingat dalam zona gunung; R-35 build dijalankan dan kendali diklik.

- [ ] **Step 10: HANDOVER, ledger, dan daftar izin akhir.** Di `HANDOVER.md` bagian 0, perbarui baris "Terakhir
diperbarui" dan tambahkan subbagian "W5 Beranda (selesai <tanggal>, menunggu izin commit)" dengan susunan subbagian
W4:

- rujukan rencana ini, jumlah tugas, dan letak ledger;
- tabel `| Butir | Nilai | Bukti |` berisi suite Unit dan Feature, suite bawaan, tsc, Pint, build dan selisih bundel,
  suite Pratinjau, anggaran kueri Beranda, tangkapan, pengukuran reflow, dan jalur papan ketik;
- ruling koordinator atas Konflik 1 sampai 9;
- OPEN ISSUE: bilah tab 320 px, halaman Kabar di dokumen fitur 05, batas kandidat tawaran, 31 kueri Beranda B2, waktu
  data contoh, pengingat hari buka untuk trip lama;
- daftar izin akhir: tanpa migrasi W5; catat keputusan GR-06 (pengingat hari buka izin, Beranda berfase, urutan
  alasan kartu) sebagai informasi untuk pemilik, dan sinkronisasi ulang pengingat trip lama sebagai butir yang
  menunggu keputusan;
- titik lanjut W6 (pintu tab Mendaki memulai pendakian yang dirujuk aksi hari H Beranda; kata status di Mendaki).

Perbarui baris W5 di `.superpowers/sdd/2026-09-26-memount-v3-program.md`.

- [ ] **Step 11: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

## Tinjauan diri

### Cakupan induk W5 dan titipan

| Butir | Tugas | Bukti di rencana |
|---|---|---|
| Butir 1: fase B0 sampai B4 dengan aksi B2 menurut keadaan trip (izin, pengingat jendela, persiapan, cek kesiapan, mulai pendakian); `DashboardPhaseTest` (setiap fase dari pabrik, tepat satu aksi utama, tanpa skor) dan `DashboardRelevanceTest` diperbarui | Tugas 1, Tugas 2 | `DashboardPhaseTest` (17 test), `DashboardRelevanceTest` (11 test, niat tetap), `PermitWindowReminderTest` (8 test) |
| Butir 2: tenggat (jendela izin tutup, terbit prakiraan BMKG, pengingat terjadwal, zona WIB/WITA/WIT); `DashboardDeadlineTest` (urutan dan zona); `TimestampZoneTest` tetap lulus | Tugas 3 | `DashboardDeadlineTest` (8 test); `TimestampZoneTest` di filter Tugas 3 |
| Butir 3: kabar gunung yang diikuti dengan pemisahan resmi dan komunitas dari `KabarJalur`; `TrailNewsTest` diperluas | Tugas 4 | `TrailNewsTest` (+5), `OfficialStatusSafetySentenceTest` (Beranda wajib) |
| Butir 4: "Jalur lain untuk Anda" paling banyak tiga dari `TrailFitService`, tanpa jalur direncanakan dan tanpa jalur tidak lengkap, dengan label, garis profil, dan satu alasan; sapaan di klien; `RecommendationExclusionTest` diperluas; `PageQueryBudgetTest` untuk Beranda | Tugas 2 (sapaan), Tugas 5 | `TrailFitServiceTest` (+3), `RecommendationExclusionTest` (+1), `PageQueryBudgetTest` (+1) |
| Butir 5: susunan `Dashboard.tsx` dengan kartu bertinggi isi (DK-31) dan tangga bila ada riwayat | Tugas 2, 5, 6 | `DashboardLadderTest` (4 test); Tugas 9 Step 7 butir 3 |
| Selesai bila: aturan penutup gelombang untuk kelima fase dengan tangkapan | Tugas 9 | Step 2 sampai 10 |
| Titipan W3-C7 (urutan alasan kartu) | Tugas 5 | `TrailFitServiceTest::test_a_fitting_trail_is_explained_by_its_strongest_reason` |
| Titipan W3-F3 (rute `trails.timbang` dan `toggle()`) | Tugas 7 | `ConsiderationTest::test_the_old_save_toggle_route_is_gone` |
| Titipan W3 "Ke W5" lain (kartu di dalam kartu laporan komunitas; `FitLine`) | Tugas 7 | `CommunityPhotoTest`, `ComparisonAsCostTest::test_the_fit_cell_does_not_repeat_its_row_and_names_its_trail` |
| W4-C4 ("Siap berangkat" di Beranda) | Tugas 2 | `DashboardPhaseTest::test_no_status_word_score_or_percent_reaches_the_home` |
| W4-1c (rencana yang tanggalnya lewat) | Tugas 2 | `DashboardPhaseTest::test_a_plan_whose_date_passed_without_starting_is_named_but_never_next`, `test_the_mountains_zone_decides_whether_the_trip_day_has_passed` |
| W4-4d (reset butir cuaca) | Pra-cek butir 3 | Beranda membaca daftar persiapan apa adanya; perbaikannya milik gelombang akhir W4 |

### Pemindaian placeholder

Tidak ada "TBD", "TODO", "implement later", atau "similar to Task N". Setiap langkah kode memuat kodenya: berkas baru
ditulis utuh, tulis ulang memuat isi utuh, dan perubahan kecil memakai blok "Ganti ... dengan ...". Blok-blok itu
dirakit dari rantai simulasi dan diperiksa dua hal: teks lamanya muncul tepat sekali pada keadaan sebelum blok itu,
dan menerapkan semua blok berurutan menghasilkan berkas akhir tugas persis. Sesudah dirakit, teks rencana ini diputar
ulang dari awal pada dasar `b7e54014` seperti implementer membacanya, dan setiap berkas sama dengan keadaan akhir
tugasnya (Bukti simulasi).

### Konsistensi nama dan tipe

- **Pengingat (Tugas 1):** `TripReminderScheduler::JENIS_IZIN`, `bukaIzin()`, `jadwal()`, `label()`,
  `dapatDiatur()`; `PreparationService::izinDiurus()`; `PengingatIzinDibuka`.
- **Fase (Tugas 2):** `RingkasanDasbor::konteks()`, `props()`, `B0` sampai `B4`; `KonteksBeranda`; props `title`,
  `fase` (`kunci`, `ringkasan`, `kartu`, `tripLewat`); `FaseBeranda`, `KartuFaseData`, `KartuPerjalananData`,
  `AksiFase`; `KartuFase`, `KartuPerjalanan`, `AksiUtama`, `sapaan()`.
- **Tenggat (Tugas 3):** `TenggatIzin::untuk()`, `TenggatTrip::untuk()`; prop `tenggat`; `TenggatBeranda`;
  `TenggatTerdekat`.
- **Kabar (Tugas 4):** `TrailNewsService::terbaru()`, `KabarJalur::ringkas()`, `KabarBeranda::untuk()`; prop `kabar`;
  `KabarBerandaData`; `KabarDiikuti`.
- **Jalur lain (Tugas 5):** `TrailFitService::teratas()`, `alasan()`; `JalurUntukAnda::untuk()`; prop `jalurLain`;
  `JalurLainData`; komponen `JalurUntukAnda`.
- **Tangga (Tugas 6):** `TanggaTanjakan::untuk()`; prop `tangga`; `TanggaData`; komponen `TanggaTanjakan`.
- **Titipan (Tugas 7):** `FitLine({ fit, trailId, nama })`.

tsc lulus sesudah setiap tugas pada simulasi dan putaran ulang.

### Review Focus

Kelima baris Review Focus punya test di tugas pemilik kodenya: fase dan aksi (Tugas 2), pengingat dan surel (Tugas
1, 3), jaminan, skor, dan otoritas (Tugas 2, 4, 5), data yang belum ada (Tugas 2, 3, 4, 6), biaya kueri dan muat
ulang parsial (Tugas 2, 5). Semua test itu lulus pada simulasi dan putaran ulang.

### Pemeriksaan teks rencana

- Rencana ini tanpa em dash. Tanda "§" hanya muncul saat mengutip ID PRD; dokumen lain dirujuk "bagian N".
- Teks antarmuka baru berbahasa Indonesia dengan sapaan "Anda".
- Tidak ada klaim "aman" atau "layak berangkat"; kata "aman" hanya muncul di kalimat dukungan keputusan lama yang
  menyangkalnya dan di test yang memastikan surel tidak memakainya.
- Label kecocokan publik tidak berubah; "Belum dinilai" tidak diketik di klien.

### Pemeriksaan test (testing-best-practices, `rules/review.md`)

- Test baru menguji perilaku lewat HTTP atau service publik dan memakai pabrik; waktu dibekukan dengan `travelTo()`
  (`DashboardPhaseTest`, `DashboardDeadlineTest`, `DashboardLadderTest`, `PermitWindowReminderTest`). Tambahan di
  `TrailNewsTest`, `PageQueryBudgetTest`, `RecommendationExclusionTest`, dan `TrailFitServiceTest` mengikuti konvensi
  berkasnya (tanggal relatif `now()`), dan `DashboardRelevanceTest` mempertahankan `Carbon::setTestNow()` miliknya.
- Setiap aksi tulis yang diuji memeriksa respons dan keadaan sesudahnya (`test_a_window_not_yet_open_offers_the_reminder_until_reminders_are_on`, `test_the_home_offers_to_follow_the_trip_mountain`).
- Masukan pengguna yang dirender di surel diuji escaping-nya (`PermitWindowReminderTest::test_the_email_links_the_official_system_and_never_sells_the_permit`).
- Duplikasi yang dilaporkan, tidak dihapus: tiga test `ConsiderationTest` yang dipindah ke `trails.saved.store` dan
  `trails.saved.remove` sebagian tumpang tindih dengan `SavedUndoTest`; keduanya dipertahankan karena menjaga
  pengumuman di Jelajah, bukan Urungkan.
- Mutasi yang diperiksa pada simulasi: membuang pengurutan tenggat, urutan tawaran menurut skor saja, alasan lemah
  dulu untuk Cocok, tawaran tanpa penghindar kalimat berulang, dan status per jalur (N+1) masing-masing menggagalkan
  test pemiliknya. Pada mutasi status per jalur, `PageQueryBudgetTest` melaporkan 36 kueri untuk 4 jalur dan 74
  untuk 24 jalur (dua kueri per kandidat tawaran).

### Paritas PostgreSQL (R-024)

Suite di rencana ini memakai SQLite dalam memori, jadi hijaunya tidak membuktikan perilaku PostgreSQL. W5 tidak
menambah migrasi, kolom, indeks, kueri spasial, transaksi, atau penguncian, sehingga tidak ada test Spatial baru dan
suite bawaan tetap melewati 32 test Spatial tanpa `SPATIAL_TEST_DSN`. Kueri baru diperiksa untuk perbedaan perilaku
kedua basis data:

- Pengurutan hanya pada kolom tak-NULL atau yang sudah disaring tidak NULL: `trails.elevation_gain_m` dengan
  `whereNotNull` (tangga), `hiking_history.completed_at` (kolom wajib, B4), `trip_plans.planned_date` (kolom wajib),
  dan `id`. Urutan NULL yang berbeda antara SQLite dan PostgreSQL tidak bisa muncul.
- `whereNotIn('id', ...)` kandidat tawaran membaca `trip_plans.trail_id`, yang wajib menurut migrasi
  `2026_09_19_000410_create_trip_plans_table`, jadi `NOT IN` tidak bertemu NULL.
- Kolom `select` kueri tangga (`trails.name`, `trails.elevation_gain_m`) semuanya ada di `GROUP BY`, sah di
  PostgreSQL.
- Perbandingan tanggal memakai `whereDate()` dan `whereColumn()` atas kolom timestamp, dikompilasi grammar masing-masing.

Yang belum terbukti di PostgreSQL adalah biaya kueri kandidat `withPublishabilityData()` yang ikut memilih geometri
(OPEN ISSUE di Bukti simulasi). Rencana ini tidak menjalankan apa pun terhadap PostgreSQL: basis data uji PostGIS
lewat `SPATIAL_TEST_DSN` hanya dipakai bila pemilik sudah menyediakannya, dan tidak pernah Supabase produksi (R-019).
Tanpa itu, laporan Tugas 9 mencatat paritas PostgreSQL W5 sebagai "diperiksa dengan membaca kueri, belum dijalankan".

## Lampiran A: Aturan eksekusi W5

Salin lampiran ini ke `.superpowers/sdd/<folder ledger W5>/aturan-eksekusi.md` sebelum Tugas 1. Isinya aturan
eksekusi W4 dengan nama gelombang, rencana, dan port yang diperbarui.

### Aturan eksekusi W5 (dibaca setiap implementer sebelum mulai)

Proyek: Memount, Laravel 13.31 + Inertia 3 + React 19.3 + TypeScript 5.9, PHP 8.5.10, di Windows dengan Git Bash.
Root repo: `E:/praktik_aplikasi_web`. Branch `hardening`. Commit terakhir `ddc27e8`; pohon kerja memuat perubahan yang
belum di-commit dari W1 sampai W4 dan gelombang ini. Folder `.agents/skills/` yang belum dilacak (keputusan Q19)
jangan disentuh.

#### Larangan mutlak (arahan pemilik dan kontrak proyek)

1. **Jangan commit, push, `git stash`, `git checkout -- <berkas>`, `git reset`, atau `git clean`.** Pemilik
   memutuskan tidak ada commit sampai seluruh program W1 sampai W9 selesai. Lewati setiap langkah "Commit"; catat
   berkas yang berubah di laporan.
2. **Jangan menyentuh Supabase dan jangan membaca atau mencetak isi `.env`.** `.env` menunjuk basis data produksi.
3. **Jangan menjalankan `php artisan migrate` dalam bentuk apa pun, `db:seed`, `db:wipe`, tinker (termasuk
   `tinker --execute`), atau `php artisan memount:demo`.** Migrasi dan seeder hanya berjalan di dalam PHPUnit.
4. **Setiap perintah Artisan yang mem-boot aplikasi di luar PHPUnit, `npm run build`, dan server statis `php -S`
   wajib diberi awalan** `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL=`. `php artisan test ...` tidak butuh awalan.
5. **Jangan menambah atau mengubah dependensi** (composer, npm), jangan mengubah versi apa pun, jangan mengimpor
   framer-motion, jangan menjalankan `npx shadcn init`, dan jangan menambah ikon ke registri Phosphor. W5 tidak butuh
   unduhan apa pun. Jangan memasang alat.
6. **Jangan menyunting berkas di luar daftar "Files" tugas Anda**, kecuali brief atau dispatch menyebutnya. Bila perlu
   menyentuh berkas lain, berhenti dan laporkan NEEDS_CONTEXT.
7. **Blok `<laravel-boost-guidelines>` di `AGENTS.md` dan `CLAUDE.md` serta blok NgodingPakeAI di `AGENTS.md` tidak
   pernah disunting.** `HANDOVER.md` dan `CLAUDE.md` hanya disunting koordinator.
8. **Jangan menjalankan subagen.**
9. **Server `php artisan serve` di 127.0.0.1:8000 milik pemilik (memakai Supabase).** Jangan dipakai, dihentikan, atau
   dipanggil. Port 8100 milik koordinator (harness pratinjau), dan port 8140 milik penulis rencana. Implementer W5
   tidak menyalakan server apa pun.

#### Hook GateGuard

Lingkungan ini memasang hook "Fact-Forcing Gate". Perintah Bash pertama dan suntingan pertama setiap berkas bisa
ditolak dengan permintaan fakta. Bila itu terjadi, tulis faktanya di balasan Anda lalu ulangi panggilan yang sama:
- ringkasan permintaan: "Menjalankan Tugas N rencana W5 (docs/superpowers/plans/2026-10-07-memount-v3-w5-beranda.md) atas arahan pemilik";
- untuk Bash: apa yang diverifikasi atau dihasilkan perintah itu;
- untuk suntingan: berkas yang mengimpor atau merujuk berkas itu (cari dengan Grep), fungsi atau kelas publik yang terdampak, bentuk data bila ada;
- untuk berkas baru: berkas yang akan memanggilnya, bukti tidak ada berkas lain dengan tujuan sama, dan bentuk data;
- untuk penghapusan atau pemindahan: jalur yang terdampak dan cara mengembalikannya;
- instruksi pemilik verbatim: "ya, selesaikan semuanya baru minta izin commit dan integrasi nya jika belum selesai semua jangan minta ke saya, jika anda meminta perizinan artinya semua tugas anda telah selesai. lanjutkan!"

#### Konvensi kerangka dari W2 (ruling W2-5l, W2-5e, W2-F6, W2-6b)

- Komponen yang hidup di kerangka persisten memanggil `useKerangka()` untuk toast. Toast tertutup saat pemiliknya
  dilepas.
- Konfirmasi sesudah navigasi datang dari flash halaman tujuan ("Ingatkan saat dibuka" mendarat di ruang kerja trip
  dengan flash `status`).
- Props halaman Beranda adalah closure supaya muat ulang parsial lembar Pengingat (`only: daftarPengingat`) tidak
  menghitungnya; jangan mengubahnya menjadi nilai langsung.
- Muat ulang penuh karena versi aset baru hanya lewat `muatPenuhVersiBaru()` atau `muatUlangMilikKerangka()` di
  `resources/js/layouts/Kerangka.tsx`. Jangan memanggil `window.location.reload()` sendiri.
- Primitif dan prop baru memakai nama Indonesia (`nilai`, `onUbah`, `label`). W5 tidak menambah primitif.

#### Cara kerja

- Isi berkas dan kode di brief adalah isi yang harus ditulis, persis. Bila teks yang harus diganti tidak ditemukan
  persis, jangan menebak: laporkan NEEDS_CONTEXT dengan kutipan berkas nyata. Nomor baris di brief adalah petunjuk;
  yang mengikat adalah teksnya.
- Ikuti urutan langkah brief: test gagal dulu (catat keluaran merahnya), implementasi, test lulus (catat keluaran
  hijaunya). Angka "pada simulasi rencana" di brief adalah acuan; bila angka nyata berbeda, laporkan apa adanya.
- Pint per berkas PHP tugas: `vendor/bin/pint --format agent <berkas>`. Setelah menyunting TypeScript: `npx tsc --noEmit`.
  Sesudah Tugas 7 (rute dihapus) jalankan build sebelum tsc.
- Dokumen dan teks: bahasa Indonesia, sapaan "Anda", **tanpa karakter em dash**, tanpa kata "aman" dan "layak
  berangkat" sebagai klaim. Konvensi rujukan: "§N" hanya untuk ID di PRD; bagian dokumen lain ditulis "bagian N".
- Bila keluaran berbeda dari "Expected" di brief, berhenti dan selidiki sebelum lanjut; laporkan apa adanya. Jangan
  menulis angka yang tidak keluar dari perintah.

#### Laporan

Tulis laporan lengkap ke berkas laporan yang disebut di dispatch: yang dikerjakan, perintah dan keluarannya (RED lalu
GREEN), berkas yang berubah, temuan tinjauan diri, dan kekhawatiran. Balasan akhir paling banyak 15 baris: Status
(DONE | DONE_WITH_CONCERNS | BLOCKED | NEEDS_CONTEXT), "Commits: tidak ada (keputusan pemilik)", ringkasan test satu
baris, kekhawatiran, dan jalur berkas laporan.
