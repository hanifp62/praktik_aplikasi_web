# Memount v3 W7 Progres, Profil, dan Onboarding Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Tiga layar dengan rupa prototype 13, 14, dan 00. Progres: ringkasan tahun, grafik per pendakian dengan
pilihan Tanjakan atau Jarak, target pribadi bulanan (FR-25), pencapaian dari riwayat sendiri, riwayat dua sumber
dengan "Catat pendakian di luar trip" (FR-25), dan peta gunung yang sudah didaki, semuanya dibandingkan hanya dengan
diri sendiri (R-015). Profil: identitas dengan foto unggahan atau inisial, pengalaman dan preferensi yang diisi
sendiri termasuk wilayah minat (DK-42), perlengkapan pribadi yang mengisi awal daftar persiapan, tampilan tanpa
pilihan bahasa (DK-40), privasi dengan "Hapus semua jejak saya", akun, pintu konsol, dan keluar. Onboarding tiga
langkah (DK-41): tingkat pengalaman, jalur terberat dengan nilai turunan yang dikonfirmasi (atau isi sendiri, atau
"belum pernah mendaki"), lalu rencana dan wilayah, diakhiri "Dengan jawaban ini, N jalur berlabel Cocok"; "Nanti
saja" menuju Jelajah dengan label "Belum dinilai". Titipan W4 ke W7: catatan per butir persiapan dan isi awal dari
perlengkapan pribadi. Empat test rintisan (`PersonalTargetTest`, `OwnedEquipmentTest`, `ManualHikeRecordTest`,
`PersonalDataWriteGuardTest`) menjadi test perilaku.

**Architecture:** Satu kelas halaman per layar (spec v3 bagian 9.2): `App\Services\Community\HubProgres` (dengan
`PencapaianPribadi`) untuk Progres dan `App\Services\Profil\HalamanProfil` untuk Profil; onboarding dirakit
`ProfileSetupController` dengan `App\Services\Profil\TurunanPengalaman` sebagai satu-satunya aturan turunan. Skema
ditambah enam migrasi aditif `2026_10_08_000100` sampai `000600`; tiga rintisan 2026_09_25 (`000919`, `000920`,
`000921`) tidak disunting (ruling koordinator). Setiap tulisan data pribadi baru lewat Form Request, Policy pemilik
atau scope pemilik sesi, dan batas laju `personal_writes_per_minute` dengan kunci sendiri. Kabar tulisan memakai flash
per halaman (`kabar-progres`, `kabar-profil`) yang dibaca wilayah `role="status"` bagian yang bersangkutan. Foto profil
ditulis ulang tanpa EXIF sebelum disimpan di disk privat yang dapat diganti (`filesystems.avatars_disk`) dan hanya
disajikan kepada pemiliknya. Angka "N jalur berlabel Cocok" dihitung dengan `HalamanJelajah` dan saringan yang sama
dengan Jelajah.

**Tech Stack:** PHP 8.5.10, Laravel 13.31.0, Inertia Laravel 3.3.4, `@inertiajs/react` 3.7.1, React 19.3.0,
TypeScript 5.9.3, Vite 8.3, Tailwind CSS 3.4.19, Wayfinder 0.1.21, PHPUnit 12.5.35, Pint 1.32, GD dan exif (sudah ada
di PHP), SQLite dalam memori untuk test, PostGIS di CI untuk suite Spatial. Tidak ada dependensi baru.

**Spec:** `docs/superpowers/specs/2026-09-25-memount-v3-prototype-design.md` (bagian 6.10 untuk isi awal persiapan,
6.14, 6.15, 6.16, 7, 8, 9.2, 9.3 baris FR-25, perlengkapan, dan DK-42), rencana induk
`docs/superpowers/plans/2026-09-26-memount-v3-induk.md` (bagian "W7 Progres, Profil, onboarding" butir 1 sampai 7 dan
"Selesai bila"), `HANDOVER.md` bagian 0 subbagian W4 (titipan "Ke W7"), acuan
`docs/desain/prototype/acuan/00-onboarding.png`, `13-progres.png`, `14-profil.png`, dan aturan eksekusi di Lampiran
A. Keadaan awalnya keadaan akhir simulasi rencana W6 (`2026-10-07-memount-v3-w6-mendaki.md`); perbaikan akhir W4, W5,
dan W6 selesai di repo nyata sebelum Tugas 1 rencana ini dimulai (Pra-cek).

## Global Constraints

- Versi dikunci: PHP 8.5, Laravel 13.31, Inertia Laravel 3.3.4, React 19.3, `@inertiajs/react` 3.7.1, Tailwind 3.4, Vite 8, TypeScript 5.9. Tidak ada perubahan versi kerangka (R-020).
- Tidak ada dependensi baru, runtime maupun dev, dan tidak ada `npx <paket>` dari jaringan. Pengolahan gambar memakai GD dan exif yang sudah ada (`App\Support\ImageSanitizer`).
- Tidak ada commit, push, akses Supabase, atau migrasi di basis data mana pun. Setiap langkah "Commit" berbunyi: "Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah."
- Migrasi rintisan `2026_09_25_000919_add_source_and_recorded_values_to_hiking_history_table`, `2026_09_25_000920_create_personal_targets_table`, dan `2026_09_25_000921_create_user_owned_items_table` tidak disunting. Skema diisi enam migrasi aditif baru (`2026_10_08_000100` sampai `000600`) yang hanya berjalan di dalam PHPUnit (SQLite dalam memori) dan suite Spatial di PostGIS CI. `php artisan migrate` dalam bentuk apa pun, `db:seed`, `db:wipe`, tinker (termasuk `tinker --execute`), dan `memount:demo` tidak dipakai.
- Setiap perintah Artisan di luar PHPUnit, `npm run build`, dan `php -S` diberi awalan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL=` (Git Bash). Di PowerShell: `$env:DB_CONNECTION="sqlite"; $env:DB_DATABASE=":memory:"; $env:DB_URL="(empty)"; <perintah>`, lalu `Remove-Item Env:DB_CONNECTION, Env:DB_DATABASE, Env:DB_URL`. `php artisan test ...` tidak butuh awalan karena `phpunit.xml` memaksa koneksi uji. Jangan membaca atau mencetak `.env`.
- Port `127.0.0.1:8100` dicadangkan untuk harness pratinjau koordinator (Tugas 13). Tugas 1 sampai 12 tidak menyalakan server apa pun. Server `php artisan serve` pemilik di `127.0.0.1:8000` tidak pernah dipakai atau dihentikan. Port 8160 milik penulis rencana.
- Bahasa Indonesia untuk teks antarmuka dan dokumen, sapaan "Anda", tanpa em dash (U+2014). Tidak ada kata "aman", "sehat", "dijamin", "terjamin", atau "pasti" sebagai klaim (R-012, R-014). "§N" hanya untuk ID PRD; dokumen lain dirujuk "bagian N".
- Progres hanya membandingkan pendaki dengan dirinya sendiri (R-015): tanpa papan peringkat, rentetan (streak), teguran, catatan kecepatan, atau pendaki lain; target tidak mengubah label kecocokan dan tingkat pengalaman tidak naik karena riwayat atau target (BR-11). Tanpa skor atau persen kecocokan maupun target di layar (R-004, R-005). Angka yang tidak diketahui disebut tidak diketahui, tidak nol (R-006, R-007). Tanpa monetisasi atau kompetisi sosial (R-021).
- Onboarding yang dilewati tidak menyimpan apa pun: label tetap "Belum dinilai" (R-006). Wilayah minat tidak ikut menilai kecocokan; Route Fit tidak berubah (R-003, R-021).
- Data pribadi baru (target, catatan manual, perlengkapan, catatan butir, wilayah minat, foto profil) hanya dapat dibaca dan diubah pemiliknya: Policy (`HikingHistoryPolicy`, `UserOwnedItemPolicy`, `TripPlanPolicy`) atau scope pemilik sesi; id pemilik, asal catatan, dan keadaan selesai tidak pernah diambil dari isian. Penghapusan akun menghapus semuanya, termasuk berkas foto (`docs/KEBIJAKAN-DATA-PRIBADI.md` bagian 2 dan 4). Catatan manual tidak menyimpan koordinat.
- Setiap rute tulis baru dibatasi `throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,<kunci>'` dengan kunci sendiri (`catatan-pendakian`, `target-pribadi`, `perlengkapan`, `catatan-butir`, `foto-profil`, `hapus-jejak`). Validasi di Form Request. Unggahan foto dibatasi tipe isi, ekstensi, ukuran, dan dimensi, dan ditulis ulang tanpa EXIF sebelum disimpan.
- Pilihan bahasa tetap disembunyikan (DK-40). Tidak ada kendali yang belum berfungsi: tautan "Kebijakan data pribadi" prototype 14 tidak dipasang selama halaman publiknya belum ada (Konflik 6).
- Token warna hanya di `resources/css/app.css`; tanpa warna mentah, `bg-white`, `text-black`, atau `dark:` di komponen. Kendali paling sedikit 44 px. Halaman tidak menggulir menyamping di 320 px. Status tidak pernah lewat warna saja; grafik menulis nilainya dan punya padanan tabel bagi pembaca layar (WCAG 2.2 AA).
- Nama props bersama (`auth`, `jumlahSimpanan`, `pendakianAktif`, `daftarPengingat`, `demo`, `perangkat`, `flash`, dan lainnya di `HandleInertiaRequests`) tidak dipakai ulang sebagai kunci prop halaman (`ReminderSheetTest`). Prop bersama baru hanya `auth.user.foto`.
- Test yang menyandikan keputusan lama diperbarui menurut niat baru dengan alasan tertulis di docblock atau komentarnya; tidak dihapus dan tidak dilonggarkan diam-diam (spec bagian 14).
- Tugas dikerjakan berurutan, satu implementer satu waktu, dan hanya menyunting berkas di daftar Files tugasnya. Berkas bersama (`routes/web.php`, `config/hiking.php`, `config/filesystems.php`, `app/Http/Middleware/HandleInertiaRequests.php`, `resources/js/types/index.d.ts`, `app/Models/User.php`, `tests/Pratinjau/HalamanPratinjauTest.php`) hanya disunting tugas yang menyebutnya. `HANDOVER.md`, `CLAUDE.md`, dan `docs/PRD.md` tidak disunting tugas implementasi; koordinator memperbarui `HANDOVER.md` di Tugas 13.
- Berkas baru dibuat seperti W2 sampai W6: test dengan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:test --phpunit --no-interaction <Nama>` (test Spatial dibuat langsung di `tests/Spatial/`), migrasi dengan `make:migration`, model dengan `make:model`, factory dengan `make:factory`, policy dengan `make:policy`, Form Request dengan `make:request`, controller dengan `make:controller`, enum dengan `make:enum`, kelas PHP dengan `make:class` (semuanya `--no-interaction` dan awalan DB), lalu isinya diganti persis dengan kode rencana. Nama berkas migrasi memakai nama di rencana (ganti nama berkas hasil `make:migration`). Berkas TypeScript dibuat langsung. Setelah menyunting PHP jalankan `vendor/bin/pint --format agent <berkas>`; setelah menyunting TypeScript atau rute jalankan `npm run build` (membangkitkan ulang keluaran Wayfinder) lalu `npx tsc --noEmit`.
- Blok "Ganti ... dengan ..." diterapkan berurutan dengan alat Edit. Teks lamanya unik di berkasnya pada keadaan dasar ditambah tugas-tugas sebelumnya, sudah diperiksa pada simulasi dan putaran ulang, dan yang jangkarnya berasal dari dasar sudah dicocokkan ke repo nyata dengan Grep (Pra-cek butir 1). Bila teks lama tidak ditemukan persis, berhenti dan laporkan NEEDS_CONTEXT; jangan mengarang padanannya. "Tulis ulang ... utuh" berarti isi berkas diganti seluruhnya dengan blok kodenya, dan hanya boleh bila blob berkas di repo nyata sama dengan blob dasar di Pra-cek butir 1a.

## Review Focus

- **Data pribadi orang lain yang tersentuh.** Mengubah atau menghapus catatan pendakian, barang perlengkapan, atau
  catatan butir milik pendaki lain dengan menebak id; isian yang menyelundupkan `user_id`, `source`, `trip_plan_id`,
  atau `completion_state`; tamu yang menulis. Dijaga Tugas 9 `PersonalDataWriteGuardTest` (sapuan seluruh rute tulis
  W7: sesi, batas laju sendiri, mass assignment, IDOR), Tugas 1 `ManualHikeRecordTest`, Tugas 3 `PersonalTargetTest`,
  Tugas 5 `OwnedEquipmentTest`, Tugas 6 `PreparationNoteTest`.
- **Foto yang membocorkan lokasi atau berkas berbahaya.** EXIF yang lolos, foto yang dapat dibuka orang lain, berkas
  lama yang tertinggal, skrip atau SVG yang diterima. Dijaga Tugas 8 `ProfilePhotoTest` (EXIF dibuang dan dikecilkan,
  hanya pemilik, berkas lama dihapus, unggahan berbahaya ditolak tanpa jejak, hapus akun menghapus berkas, dan nilai
  lama kolom `avatar_path` yang tidak ditulis layanan foto tidak pernah disajikan atau dihapus).
- **Perbandingan dan angka yang menghukum atau mengarang.** Persen, rentetan, kata "gagal" atau "tertinggal", pendakian
  orang lain dihitung, angka kosong dibaca nol, pembatalan karena cuaca tidak dihitung. Dijaga Tugas 2
  `HikerProgressTest` dan `ProgressHierarchyTest`, Tugas 3 `PersonalTargetTest::test_an_unmet_target_is_stated_plainly_and_unknown_numbers_are_named`,
  Tugas 4 `PersonalAchievementTest`.
- **Onboarding yang menebak.** Pengalaman diambil dari isian klien padahal jalur katalog dipilih, navigasi turunan
  dengan tangga lain dari Route Fit, "belum pernah mendaki" dianggap tidak diketahui, profil tersimpan saat dilewati,
  atau angka "N jalur berlabel Cocok" berbeda dengan Jelajah. Dijaga Tugas 7 `OnboardingDerivationTest` dan
  `InterestRegionTest::test_regions_never_change_fit_labels`.
- **Daftar persiapan yang berubah diam-diam.** Barang pribadi masuk sebagai butir kritis atau sudah dikonfirmasi,
  digandakan saat "Perbarui daftar", atau daftar trip lama berubah ketika barang diganti nama atau dihapus. Dijaga Tugas
  5 `OwnedEquipmentTest::test_changing_the_profile_list_leaves_existing_trip_lists_alone` dan
  `PreparationServiceTest::test_regenerating_neither_duplicates_owned_items_nor_resets_them`.
- **Skema di PostgreSQL.** CHECK sumber catatan, indeks unik tanpa memandang huruf besar, kunci asing cascade dan set
  null, RLS, panjang kolom catatan. SQLite tidak membuktikan satu pun; dijaga suite Spatial (Tugas 1, 3, 5, 6, 7) yang
  berjalan di PostGIS CI (Tugas 13 Step 2).

---

## Bukti simulasi rencana

Kode di rencana ini dijalankan tugas demi tugas di salinan di scratchpad sesi perencanaan (`w7sim`). Salinan itu dibuat
dengan menyalin utuh `w6sim` (keadaan akhir simulasi rencana W6), termasuk `vendor/` dan `node_modules/` (salinan, bukan
tautan), dengan `git init` tanpa add dan tanpa commit, dan konstanta `ASAL` harness pratinjau diarahkan ke port 8160.
Repo nyata tidak pernah ditulis dan tidak pernah menjadi direktori kerja perintah; ia hanya dibaca dengan Read, Grep,
dan Glob (rincian pagar di catatan simulasi penulis). Setiap tugas diuji merah dengan test tugas itu terhadap kode
tugas sebelumnya, lalu hijau, lalu suite Unit dan Feature penuh, build, `npx tsc --noEmit`, Pint, dan suite Pratinjau.
Sesudah dirakit, teks rencana ini diputar ulang dari salinan dasar yang bersih (`w7putar2`, dibuat dengan cara yang
sama) memakai pengurai yang hanya membaca blok "Buat", "Tulis ulang", "Ganti", dan "Hapus berkas" di rencana ini; angka
di tabel adalah hasil putaran ulang itu.

| Tugas | Merah (filter tugas, sebelum implementasi) | Hijau | Suite Unit dan Feature | Pratinjau | Pohon sesudah tugas |
|---|---|---|---|---|---|
| Dasar (keadaan akhir W6) | | | 2.058 test, 2.056 lulus, 2 dilewati | 29 lulus | dasar putaran ulang |
| 1 Riwayat dua sumber | 21 test: 3 lulus, 14 error, 4 dilewati | 17 lulus, 4 dilewati | 2.071 test, 2.069 lulus, 2 dilewati | 29 lulus | identik dengan simulasi |
| 2 Hub Progres | 53 test: 31 lulus, 4 gagal, 18 error | 53 lulus | 2.073 test, 2.071 lulus, 2 dilewati | 30 lulus | identik dengan simulasi |
| 3 Target pribadi | 29 test: 17 lulus, 1 gagal, 9 error, 2 dilewati | 27 lulus, 2 dilewati | 2.081 test, 2.079 lulus, 2 dilewati | 30 lulus | identik dengan simulasi |
| 4 Pencapaian | 23 test: 17 lulus, 1 gagal, 5 error | 23 lulus | 2.086 test, 2.084 lulus, 2 dilewati | 30 lulus | identik dengan simulasi |
| 5 Perlengkapan pribadi | 37 test: 26 lulus, 8 error, 3 dilewati | 34 lulus, 3 dilewati | 2.093 test, 2.091 lulus, 2 dilewati | 30 lulus | identik dengan simulasi |
| 6 Catatan butir | 20 test: 14 lulus, 5 error, 1 dilewati | 19 lulus, 1 dilewati | 2.098 test, 2.096 lulus, 2 dilewati | 30 lulus | identik dengan simulasi |
| 7 Onboarding dan wilayah | 63 test: 42 lulus, 14 gagal, 6 error, 1 dilewati | 62 lulus, 1 dilewati | 2.114 test, 2.112 lulus, 2 dilewati | 34 lulus | identik dengan simulasi |
| 8 Foto profil | 12 test: 5 lulus, 1 gagal, 6 error | 12 lulus | 2.120 test, 2.118 lulus, 2 dilewati | 34 lulus | identik dengan simulasi |
| 9 Hapus jejak dan penjaga tulisan | 12 test: 8 lulus, 1 gagal, 3 error | 12 lulus | 2.126 test, 2.124 lulus, 2 dilewati | 34 lulus | identik dengan simulasi |
| 10 Hub Profil | 68 test: 64 lulus, 3 gagal, 1 dilewati | 67 lulus, 1 dilewati | 2.130 test, 2.128 lulus, 2 dilewati | 36 lulus | identik dengan simulasi |
| 11 Data contoh | 14 test: 13 lulus, 1 gagal | 14 lulus | 2.130 test, 2.128 lulus, 2 dilewati | 36 lulus | identik dengan simulasi |
| 12 Dokumen | tidak ada test baru (31 test penjaga dokumen lulus sebelum dan sesudah) | 31 lulus | 2.130 test, 2.128 lulus, 2 dilewati | tidak dijalankan (dokumen) | identik dengan simulasi |

Sesudah setiap tugas `npx tsc --noEmit` keluar tanpa galat dan `vendor/bin/pint --test --format agent` lulus;
`npm run build` dan suite Pratinjau dijalankan sesudah Tugas 1 sampai 11. Test yang "dilewati" di kolom merah dan
hijau adalah test Spatial tugas itu, yang melewati dirinya tanpa `SPATIAL_TEST_DSN`, kecuali Tugas 10: di sana satu
test yang sudah dilewati di dasar ikut tersaring.

Angka di atas memakai SQLite dalam memori. Suite bawaan `php artisan test --compact` (Unit, Feature, dan Spatial
menurut `defaultTestSuite`) sesudah Tugas 12: 2.175 test, 2.128 lulus, 47 dilewati (45 test Spatial
tanpa `SPATIAL_TEST_DSN`, 11 di antaranya baru di W7, ditambah 2 test yang sudah dilewati di dasar). Paritas
PostgreSQL ada di Tinjauan diri.

**Putaran ulang.** Putaran penuh pada teks final, 8 Oktober 04.19 sampai 04.44 WIB, di salinan dasar `w7putar2` (dibuat dengan menyalin `w6sim` utuh tanpa tautan, `ASAL` port 8160; 0 berkas beda dengan potret dasar) dengan pengurai yang hanya membaca blok rencana. Pengurai membaca 219 operasi (63 "Buat", 12 "Tulis ulang ... utuh", 141 blok "Ganti", 3 "Hapus berkas"), dan setiap teks lama blok "Ganti" ditemukan tepat sekali saat diterapkan. Pohon sumber sesudah setiap tugas (tanpa `vendor`, `node_modules`, keluaran build, keluaran Wayfinder, dan `public/_pratinjau`) sama berkas demi berkas dengan potret simulasi tugas itu: 0 berkas baru, berubah, atau hilang untuk Tugas 1 sampai 12. Akhir putaran: Unit dan Feature 2.130 test (2.128 lulus, 2 dilewati), `npx tsc --noEmit` tanpa galat, Pint lulus, build lulus, dan Pratinjau 36 lulus. Terhadap dasar, Tugas 1 sampai 12 menambah 63 berkas, mengubah 53, dan menghapus 3. Putaran pertama (`w7putar`, 03.55 sampai 04.18) lulus di setiap tugas, tetapi tujuh berkas berbeda dari potret karena potongan rencana belum dirakit ulang sesudah perbaikan terakhir (komentar migrasi dan enum, penjaga jalur foto); potongan dirakit ulang dan putaran diulang dari salinan baru. Sesudah putaran akhir hanya teks di luar blok kode yang disunting (angka, Bukti, Konflik, Tugas 13, Tinjauan diri); sidik SHA-256 daftar operasi rencana final sama dengan yang diputar (`541132a820730359...`).

**Uji kering terhadap repo nyata.** Hanya membaca, dengan alat Grep, 8 Oktober 2026 dini hari, saat W5 sedang dieksekusi di repo nyata dan sebelum W6. Rencana ini
memuat 105 blok "Ganti" yang teks lamanya berasal utuh dari dasar: 81 blok multi-baris (79 pola berbeda) dicocokkan
dengan Grep multiline yang mengizinkan `\r?\n` di antara baris, dan 24 blok satu baris atau sepenggal baris (dokumen
Tugas 12 dan rute Tugas 6) dicocokkan ulang sesudah teks final dirakit; masing-masing ditemukan tepat sekali. 24 blok
lain berjangkar teks tugas W7 sebelumnya; ketepatannya dibuktikan putaran ulang, bukan Grep. Kelima belas berkas yang
ditulis ulang utuh atau dihapus cocok jumlah barisnya dengan dasar, dan blob dasarnya dicatat di Pra-cek butir 1a untuk
diperiksa koordinator dengan `git hash-object`. Skrip yang membaca repo nyata tidak dijalankan, karena pagar penulis
hanya mengizinkan Read, Grep, dan Glob terhadap repo nyata.

**Pengukuran di peramban** (Chrome lewat MCP chrome-devtools, pratinjau statis salinan di port 8160, emulasi seluler
dengan sentuh dan rasio piksel 1 untuk 390 px, desktop 1440 px rasio 1; reflow, target, dan kontras diukur di iframe
selebar 320, 390, 768, dan 1440 px di dalam emulasi seluler 390 px, pada tema terang dan gelap):

| Ukuran | Hasil |
|---|---|
| Reflow 320 px | `scrollWidth` sama dengan `clientWidth` (320) di `progres`, `progres-kosong`, `profil`, `profil-kosong`, `onboarding`, `onboarding-pengalaman`, `onboarding-rencana`, `onboarding-selesai`, dan `persiapan`. Di Progres, 34 unsur yang melewati tepi kanan seluruhnya ada di dalam pembungkus `sr-only` tabel padanan (terpotong, tidak menggulir) |
| Reflow 390, 768, dan 1440 px | Progres 390 lawan 390 (sebelum perbaikan tabel padanan 502); kesembilan halaman 768 lawan 768 dan 1440 lawan 1440 |
| Target sentuh 390 px | Semua kendali di `main` ketujuh halaman (progres, profil, onboarding tiga langkah dan hasil, persiapan) paling sedikit 44 px, kecuali tiga kontrol bawaan MapLibre di peta gunung W2 (29 x 29 px, di atas 24 px WCAG 2.5.8; tidak disentuh W7). Contoh: Tanjakan dan Jarak 106 x 44, chip "Headlamp" 103 x 44, kartu tingkat 358 x 92, "Tambah catatan" 108 x 44 |
| Kontras teks | Terang: terendah 5,08:1 (Progres dan Profil), 6,38:1 (onboarding), 5,63:1 (Persiapan). Gelap: terendah 4,92:1 (Profil), 6,26:1 (Progres dan Persiapan), 8,41:1 (onboarding). Tidak ada teks di bawah 4,5:1 (warna teks dibandingkan dengan latar efektifnya, termasuk lapisan transparan) |
| Kontras non-teks | Isi batang grafik terhadap lintasannya 4,25:1 dan 7,82:1 (terang), 7,94:1 (gelap); garis putus-putus "batal" 3,65:1 (terang) dan 4,58:1 (gelap); batas chip perlengkapan 3,65:1 (terang) dan 4,58:1 (gelap); isi bulan target 7,82:1 dan 9,39:1 (terang). Lintasan batang (1,2:1 terang, 1,09:1 gelap) hanya hiasan: nilai dibaca dari panjang isi dan angka tertulis |
| Bilah tetap | Pada gulir terbawah, isi terakhir berada di atas bilah tab atau bar aksi di keenam halaman yang diukur (misalnya Progres 738 lawan 763 px, onboarding 722 lawan 759 px) |
| Papan ketik | Kartu tingkat dipilih dengan panah (Pemula ke Menengah) dengan cincin fokus terlihat (`w7-fokus-onboarding-390.png`); chip "Headlamp" dengan Enter membuka "Ubah barang" dengan fokus di "Tutup" (perilaku `showModal` di semua lembar), Tab berikutnya ke isian nama, dan Escape menutup lembar serta mengembalikan fokus ke chip |

Tangkapan di scratchpad: `w7-progres-390-terang.png`, `w7-progres-390-gelap.png`, `w7-progres-1440-terang.png`,
`w7-progres-1440-gelap.png`, `w7-profil-*` dan `w7-onboarding-pengalaman-*` dengan empat kombinasi yang sama,
`w7-onboarding-tingkat-390-terang.png`, `w7-onboarding-rencana-390-terang.png`, `w7-onboarding-selesai-390-terang.png`.
Tangkapan halaman penuh menggambar bilah tetap di posisi layar pertama; itu ciri alat tangkap, bukan tumpang tindih di
peramban.

**Cacat yang ditemukan simulasi dan sudah diperbaiki di kode rencana ini** (dicatat supaya peninjau tahu mengapa
bentuknya begini):

- Tabel padanan grafik per pendakian semula diberi `sr-only` langsung; kotak tabel tidak terpotong `overflow`, sehingga
  halaman Progres melebar ke 502 px di 390 px. Kini tabelnya di dalam pembungkus `sr-only` dan figurnya `relative`
  (`ProgressHierarchyTest::test_the_chart_writes_its_values_instead_of_only_drawing_them`).
- Kontrol segmen empat pilihan "Lama pendakian" di onboarding memotong "Setengah hari" di tengah kata pada 390 px; kini
  grup radio berbentuk chip (`PilihanKartu bentuk="chip"`) yang membungkus antarchip.
- Tombol "Ubah" catatan butir semula 41 px lebarnya; kini `min-w-11`. Baris "Tambah catatan" ditarik ke bantalan bawah
  kotak centang (`-mt-2`) tanpa mengecilkan targetnya.
- Wilayah `role="status"` semula diberi `empty:hidden`; wilayah yang tersembunyi saat isinya datang tidak andal
  dibacakan, jadi kini `empty:!mt-0` seperti konvensi kabar di repo nyata.
- Menghapus barang terakhir dari lembar perlengkapan menjatuhkan fokus ke body (pemicunya sudah hilang); fokus kini
  kembali ke "Tambah barang" atau ke judul bagian.
- Isian unik nama barang semula peka huruf besar di PostgreSQL; kini indeks ekspresi `(user_id, lower(name))` di kedua
  pengemudi, dan tabrakan kiriman bersamaan dikembalikan sebagai galat isian, bukan galat server.
- Rute catatan butir semula dijangkarkan ke baris rute `regenerate` yang di repo nyata sudah berbatas laju (perbaikan
  akhir W4); kini jangkarnya baris GET persiapan saja.
- `InertiaLinkRegistryTest` menuntut `/profile/foto` terdaftar sebagai rute bukan halaman React.
- Prop bersama Inertia memuat relasi `profile` pada instans pengguna yang sama di test; test sesudah menyimpan profil
  memakai `$user->fresh()`. Di produksi setiap permintaan memuat pengguna baru, jadi tidak ada perubahan kode.
- Kata "tertinggal" di komentar dan "Ayo" yang cocok dengan "layout" membuat penjaga kata menghukum salah tangkap; kini
  penjaga memakai batas kata dan komentar ditulis ulang.
- Jalur berkas foto yang disajikan dan dihapus berasal dari kolom lama `profiles.avatar_path`, yang sudah ada sebelum W7;
  kini hanya nama acak langsung di map `avatars` yang diterima (`FotoProfil::sah()`,
  `ProfilePhotoTest::test_a_path_not_written_by_the_photo_service_is_never_served_or_deleted`).
- Komentar migrasi dan enum memakai kata "aman" dan "akal sehat"; walau bukan klaim keselamatan, kata itu diganti supaya
  pemindaian kata terlarang tidak perlu pengecualian.

**Temuan di luar cakupan W7 (OPEN ISSUE):**

- Baris status program di `docs/PRD.md` (bagian peta implementasi, baris "8. Mode pendakian, progres, akun") masih
  menyebut target pribadi dan catatan manual "belum"; mengubah PRD butuh governance record (Konflik 9).
- Tautan "Kebijakan data pribadi" di Profil (prototype 14) menunggu halaman publik kebijakan (Konflik 6).
- Pencapaian "Camping pertama di atas 2.000 m" (prototype 13) tidak dibangun: lokasi berkemah tidak tercatat (W6-R13).
- Catatan butir hanya untuk Perlengkapan dan Logistik (W7-R9); kategori lain menunggu kebutuhan nyata.
- Perilaku unggahan sungguhan (tipe MIME dari isi berkas) dibuktikan dengan probe di simulasi Windows; test PHPUnit
  memakai berkas palsu Laravel yang tipenya ditebak dari ekstensi, dan berkas berisi skrip tetap ditolak aturan
  `dimensions` (Konflik 11).
- Foto profil hanya tampil bagi pemiliknya; tidak ada permukaan yang menampilkan foto pendaki lain.
- Kontrol bawaan MapLibre di peta gunung Progres (W2, `PetaGaris`) berukuran 29 x 29 px: lolos WCAG 2.5.8 (24 px), tetapi
  di bawah target proyek 44 px. W7 tidak menyentuhnya.
- Pratinjau statis tidak dapat menampilkan hasil pencarian jalur katalog di onboarding, karena muat ulang parsial butuh
  server. Jalur itu dibuktikan `OnboardingDerivationTest::test_the_search_sends_derived_values_for_confirmation` dan
  diperiksa manual sesudah izin Q1.

## Keadaan awal yang terukur (FAKTA, 8 Oktober 2026)

| Butir | Keadaan | Bukti |
|---|---|---|
| Suite dasar | Unit dan Feature 2.058 test (2.056 lulus, 2 dilewati), sama dengan angka akhir rencana W6; Pratinjau 29 test lulus | `php artisan test --compact --testsuite=Unit,Feature` dan `--testsuite=Pratinjau` di `w7sim`, `w7putar`, dan `w7putar2` sebelum Tugas 1 |
| Progres | `HikerProgressController` menyusun ringkasan dari `HikerProgressService::forUser`, grafik batang tegak tanjakan saja (`GrafikTanjakan`), dan peta gunung; tanpa tahun, target, pencapaian, riwayat, atau catat manual | `app/Http/Controllers/Community/HikerProgressController.php`, `resources/js/pages/Community/HikerProgress.tsx` |
| Riwayat | `/history` hanya pendakian dari trip; `hiking_history.trip_plan_id` dan `trail_id` wajib | `app/Http/Controllers/Community/HikingHistoryController.php`, `database/migrations/2026_09_19_000610_create_hiking_history_table.php` |
| Rintisan FR-25 | `personal_targets` dan `user_owned_items` hanya `id` dan `timestamps`; migrasi `2026_09_25_000919` kosong; empat test rintisan hanya membuka `/` | `database/migrations/2026_09_25_000919*`, `000920*`, `000921*`; `tests/Feature/PersonalTargetTest.php`, `OwnedEquipmentTest.php`, `ManualHikeRecordTest.php`, `PersonalDataWriteGuardTest.php` |
| Profil | Informasi akun, kata sandi, ringkasan profil pendakian empat baris (termasuk `region_preference` teks bebas), rekam jejak, tema, pintu konsol, hapus akun; tanpa foto, perlengkapan, wilayah minat, atau sakelar efek dan gerak (rute `tampilan.simpan` sudah ada tanpa kendali) | `resources/js/pages/Profile/Edit.tsx`, `app/Http/Controllers/Profile/ProfileController.php`, `app/Http/Controllers/DisplayPreferenceController.php` |
| Onboarding | Tiga langkah berisi select dan isian bebas (tingkat, wilayah teks, bio; jumlah pendakian, medan, navigasi, durasi dalam menit, tanjakan; durasi, jenis, tantangan, batas tanjakan), ringkasan untuk profil lengkap, simpan menuju `goals.create`, bilah tab tampil | `resources/js/pages/Onboarding/ProfileSetup.tsx`, `app/Http/Controllers/Onboarding/ProfileSetupController.php`, `app/Http/Requests/StoreProfileSetupRequest.php` |
| Persiapan | Butir tanpa catatan; daftar tanpa isi awal perlengkapan pribadi | `app/Services/PreparationService.php`, `resources/js/pages/Trips/Preparation.tsx` |
| Foto profil | Kolom `profiles.avatar_path` ada tanpa pemakai; avatar selalu inisial | `database/migrations/2026_09_19_000110_create_profiles_table.php`, `resources/js/components/ui/Avatar.tsx` |
| Data contoh | Budi tanpa target, perlengkapan, wilayah minat, atau catatan manual; trip Pangrango dibatalkan tanpa alasan | `database/seeders/DemoSeeder.php` |

## Ruling rencana (W7-R1 sampai W7-R21)

Setiap ruling ditulis dengan format keputusan, alasan, dan akibat bila salah.

| ID | Keputusan | Alasan | Akibat bila salah |
|---|---|---|---|
| W7-R1 | Dasar rencana adalah keadaan akhir simulasi W6 (`w6sim`). Perbaikan akhir W4, W5, dan W6 belum semuanya ada di pohon kerja repo nyata saat rencana ditulis; setiap jangkar "Ganti" yang berasal dari dasar sudah dicocokkan ke repo nyata dengan Grep, dan jangkar di berkas yang berbeda karena perbaikan akhir W4 (`PreparationService.php`, `HalamanPersiapan.php`, `Preparation.tsx`, `routes/web.php`, dokumen fitur 04) dibuat sependek mungkin. Tiga belas tugas: catat manual (1), hub Progres (2), target (3), pencapaian (4), perlengkapan (5), catatan butir (6), onboarding dan wilayah minat (7), foto profil (8), privasi dan penjaga tulisan (9), hub Profil (10), data contoh (11), dokumen (12), verifikasi koordinator (13) | Brief koordinator; satu tugas satu unit tinjauan, urutan mengikuti ketergantungan (Progres butuh dua sumber riwayat, Profil butuh perlengkapan, foto, dan wilayah) | Jangkar yang tertabrak ditemukan Pra-cek; implementer berhenti NEEDS_CONTEXT |
| W7-R2 | Skema diisi enam migrasi aditif baru, berurutan: `2026_10_08_000100_add_source_to_hiking_history_table` (sumber, nama jalur bebas, `trip_plan_id` dan `trail_id` boleh kosong, indeks pengguna dan tanggal, dua CHECK di PostgreSQL), `000200_complete_personal_targets_table` (pengguna, ukuran, nilai bulanan, unik per ukuran, RLS), `000300_complete_user_owned_items_table` (pengguna, nama, unik `lower(name)` per pengguna, RLS), `000400_add_owned_item_to_trip_preparation_items_table` (kunci asing `nullOnDelete`), `000500_add_note_to_trip_preparation_items_table` (`varchar(80)`), `000600_add_interest_regions_to_user_preferences_table` (JSON). Rintisan `2026_09_25_000919` sampai `000921` tidak disunting | Ruling koordinator W7; R-024 (setiap CHECK, indeks, kunci asing, dan RLS punya test Spatial) | Migrasi baru gagal di Supabase bila rintisan sudah berisi baris tanpa pemilik; kueri pemeriksaannya di docblock migrasi dan Konflik 1 |
| W7-R3 | Riwayat dua sumber: `hiking_history.source` `TRIP` atau `MANUAL` (`HikeRecordSource`). Catatan manual selalu `COMPLETED`, tanpa trip, dengan jalur katalog terbit atau nama jalur bebas, tanggal tidak di masa depan (hari ini menurut zona WIT, `Timezone::latestDateInIndonesia`), `completed_at` pukul 12.00 WIB tanggal itu, angka opsional di `recorded_*`. Hanya catatan manual pemiliknya yang dapat diubah dan dihapus (`HikingHistoryPolicy`); catatan dari trip tidak tersentuh | §150 FR-25; R-007 (angka kosong tetap kosong); lokasi presisi tidak dicatat | Pendakian berbalik di luar trip tidak dapat dicatat; dapat ditambah sebagai pilihan keadaan |
| W7-R4 | Angka satu pendakian dari satu aturan (`HubProgres::angka`): angka pendaki (catatan manual atau "Ubah angka" W6), lalu data jalur untuk yang tuntas (tanjakan jalur, jarak pulang-pergi, W6-R13), selain itu tidak diketahui. Rekaman jejak tidak dijumlahkan, dan halaman menyebutnya | Satu sumber untuk ringkasan, grafik, target, dan pencapaian | Angka Progres dan ringkasan pendakian berbeda bila W6-R13 diubah tanpa mengubah aturan ini |
| W7-R5 | Grafik per pendakian sebagai baris mendatar berlabel nama dan bulan dengan nilai tertulis di ujungnya, dua belas pendakian terbaru urut waktu, pilihan Tanjakan atau Jarak, batal bergaris putus-putus dengan kata "batal"; padanannya tabel bagi pembaca layar dan kalimat keterangan | Prototype 13 memakai batang tegak dengan nama di bawahnya, yang tidak muat di 320 px; WCAG 1.1.1 dan 1.4.1 | Bentuk berbeda dari acuan (Konflik 2) |
| W7-R6 | Peta "Gunung yang sudah Anda daki" dan dampak laporan kondisi tetap di Progres, sesudah riwayat | Fitur yang sudah ada (W2, `ReporterImpactTest`) tidak dibuang diam-diam; prototype 13 tidak menggambarnya | Halaman lebih panjang dari acuan (Konflik 3) |
| W7-R7 | Target pribadi satu set tanpa id (`PUT progres/target`): tanjakan per bulan (m), jarak per bulan (km), jumlah pendakian per bulan; isian kosong mencopot targetnya. Capaian bulan berjalan dari pendakian sendiri, tanpa persen di layar; strip dua belas bulan; pembatalan dihitung pada jumlah pendakian (prototype 13 "September berisi Sindoro yang dibatalkan karena cuaca, dan tetap dihitung") | §150; R-015; BR-11 | Pembatalan yang tidak ingin dihitung butuh pilihan baru |
| W7-R8 | Perlengkapan pribadi (paling banyak 40 barang, nama 2 sampai 60 karakter, unik tanpa memandang huruf besar) mengisi awal daftar persiapan setiap trip saat daftar dibangkitkan: butir Perlengkapan, tidak kritis, belum dikonfirmasi, bertaut `user_owned_item_id`; barang yang sudah bertaut atau yang namanya sama dengan butir yang ada dilewati, jadi "Perbarui daftar" tidak menggandakan. Mengganti nama atau menghapus barang tidak mengubah daftar trip yang sudah ada (kunci asing `nullOnDelete`) | Spec bagian 6.10 dan 6.15; BR-10 (memiliki tidak sama dengan membawa) | Barang baru masuk ke trip lama hanya lewat "Perbarui daftar" |
| W7-R9 | Catatan per butir persiapan (titipan W4): hanya butir Perlengkapan dan Logistik, paling panjang 80 karakter, hanya selama trip belum selesai atau dibatalkan, tidak mengubah status butir; tampil "Catatan: ..." di bawah butir dengan "Tambah catatan" atau "Ubah" | Prototype 09 "Dibawa Dimas"; catatan menjawab siapa membawa atau mengurus apa; menambah baris kendali di setiap butir memanjangkan daftar | Kategori lain tidak dapat diberi catatan (Konflik 5) |
| W7-R10 | Onboarding tiga langkah (DK-41): (1) tingkat dengan keterangan; (2) jalur terberat dari katalog dengan tanjakan, durasi, medan, dan navigasi turunan untuk dikonfirmasi, atau isi sendiri ("Jalurnya tidak ada di daftar", juga lewat "Ubah nilai"), atau "Saya belum pernah mendaki", ditambah jumlah pendakian dan durasi mendaki terlama dalam satu hari; (3) satu jenis trip, lama pendakian, wilayah minat, dan batas tanjakan yang nyaman (opsional). Untuk cara katalog, server menurunkan nilainya dari jalur (`TurunanPengalaman`), bukan dari isian. Sesudah simpan, halaman hasil menyebut "Dengan jawaban ini, N jalur berlabel Cocok", dihitung `HalamanJelajah` dengan saringan `kecocokan=COCOK`, dengan tautan ke Jelajah tersaring dan ke Profil. "Nanti saja" (hanya bila profil belum lengkap) menuju Jelajah tanpa menyimpan. Halaman tugas tanpa bilah tab | Spec bagian 6.16; DK-41; R-006; navigasi turunan memakai tangga `NavigationComplexity::expectedNavigationRank` yang sama dengan Route Fit | Jenis trip tetap satu (Konflik 4); kolom teks bebas lama tidak lagi diisi (Konflik 8) |
| W7-R11 | Wilayah minat (DK-42): delapan kelompok provinsi yang menutup Indonesia (`InterestRegion`), larik JSON di `user_preferences.interest_regions`, daftar putih di validasi, disimpan urut daftar, kosong disimpan null. Tidak dibaca Route Fit | Spec bagian 9.3 ("kode provinsi atau kelompok wilayah"); R-021 | Penyaring Jelajah per wilayah menunggu gelombang lain |
| W7-R12 | Foto profil: JPG, PNG, atau WebP paling besar 2 MB dan 64 sampai 8.000 piksel; ditulis ulang tanpa EXIF dan dikecilkan ke 512 px sebelum disimpan (berlaku juga untuk disk S3); nama berkas acak di `filesystems.avatars_disk` (`AVATARS_DISK`, bawaan disk privat lokal); disajikan hanya kepada pemilik sesi lewat `GET profile/foto` dengan `Cache-Control: private`; alamat di prop bersama `auth.user.foto` dengan penanda versi; berkas lama dihapus saat diganti, dihapus, atau akun dihapus | Induk butir 5; PRD §81, §82; kebijakan data pribadi bagian 2 dan 5 | Foto tidak dapat dipakai di permukaan publik mana pun tanpa rute baru |
| W7-R13 | "Hapus semua jejak saya" menghapus seluruh `hike_track_points` dari sesi pendakian pengguna, dengan dialog yang menyebut akibatnya; pendakian, riwayat, laporan, dan catatan tetap | Spec bagian 6.15; PRD §84; BR-13 | Tanpa urungkan (Konflik 15) |
| W7-R14 | Hub Profil (prototype 14) urut: identitas, pengalaman, preferensi, perlengkapan pribadi, tampilan (tema, kurangi efek transparan, kurangi gerak lewat `tampilan.simpan`, tanpa bahasa), perekaman jejak, lokasi dan jejak, informasi akun, kata sandi, pintu konsol dan Keluar, hapus akun. Formulir akun dan `FormRekamJejak` dipertahankan | Spec bagian 6.15; DK-40; formulir akun tidak ada di acuan tetapi fiturnya tidak boleh hilang | Halaman lebih panjang dari acuan |
| W7-R15 | Kabar tulisan per halaman: flash `kabar-progres` dan `kabar-profil` berisi `bagian` dan `teks`, dibaca wilayah `role="status"` yang selalu terpasang di bagian itu | Konvensi wilayah umpan balik di `DESIGN.md`; tidak memakai `kabar-aksi` repo nyata yang belum ada di dasar | Dua pola kabar berdampingan (Konflik 12) |
| W7-R16 | Pencapaian dari riwayat sendiri saja: tanjakan pertama di atas 500, 1.000, 2.000, dan 3.000 m (tonggak yang sama dengan `HasilPendakian::TONGGAK_TANJAKAN`), dan laporan kondisi pertama yang terbit beserta jumlah terima kasihnya | Prototype 13; R-015; induk butir 7 | Pencapaian lain butuh data yang belum tercatat |
| W7-R17 | `HikerProgressService` tinggal `dampakLaporan()` dan `petaGunung()`; `forUser()`, `tanjakanPerPendakian()`, dan `summitedMountains()` dicabut bersama `GrafikTanjakan.tsx`, karena isinya pindah ke `HubProgres` | Satu sumber angka (W7-R4); tidak ada pemanggil lain di repo nyata (Grep) | Kode luar yang memanggilnya gagal saat dimuat; Grep di Pra-cek butir 4 |
| W7-R18 | "Durasi mendaki terlama dalam satu hari" (jam bulat 1 sampai 24) mengisi `user_experience.longest_hike_duration_minutes` dalam menit | Prototype 00 ("Satu hari, tanpa istirahat bermalam"); kolom ini tidak dibaca Route Fit | Nilai lama di atas 24 jam terbaca 24 jam di onboarding dan tersimpan 24 jam bila profil disimpan ulang (Konflik 10) |
| W7-R19 | Data contoh W7: Budi membawa lima barang perlengkapan (prototype 14), target tanjakan 1.000 m dan satu pendakian per bulan, wilayah Jawa Barat dan Banten serta Jawa Tengah dan DI Yogyakarta, alasan batal cuaca untuk Pangrango, dan satu catatan manual Papandayan tiga hari sebelum hari ini | Pratinjau dapat dibandingkan dengan acuan 13 dan 14; DK-35 | Teks data contoh tanpa penanda hanya nama barang dan wilayah, yang tidak terbaca sebagai fakta |
| W7-R20 | Titik tengah di lencana riwayat ("Dibatalkan · Cuaca") dan keterangan pencapaian ("Batur via Toya Bungkah · Jul 2026") mengikuti prototype 13 | Patokan desain pemilik | Berbeda dengan aturan tipografi `DESIGN.md` (Konflik 7) |
| W7-R21 | Akses data milik pendaki lain mengikuti jawaban yang sudah berlaku di repo: catatan pendakian, barang perlengkapan, dan trip yang ditemukan lewat id lalu ditolak Policy menjawab 403 (`HikingHistoryPolicy`, `UserOwnedItemPolicy`, `TripPlanPolicy`), dan butir persiapan yang bukan milik trip di alamatnya menjawab 404 (dicari lewat relasi trip). Target, foto, dan "Hapus semua jejak saya" tidak memakai id sama sekali: pemiliknya selalu pengguna sesi | Spec v3 Q5 ("Tetap seperti sekarang sampai diputuskan"); skill testing-best-practices menyarankan 404 supaya keberadaan id tidak terbaca, tetapi konvensi proyek didahulukan | Bila Q5 diputus 404, tiga controller memakai pencarian lewat relasi pengguna dan asersi `assertForbidden` di test W7 menjadi `assertNotFound` |

### Konflik untuk koordinator

1. **Enam migrasi aditif W7 (butir izin akhir).** `2026_10_08_000100` sampai `000600` (W7-R2), diuji SQLite dan suite
   Spatial PostgreSQL, tidak dijalankan di Supabase. Urutan pasang: sesudah lima migrasi W6, satu batch, urut nama
   berkas (`000400` butuh `000300`); kembalikan dengan urutan terbalik. Bila rintisan `2026_09_25_000919` sampai `000921`
   belum berjalan di Supabase (Q22), ketiganya ikut batch yang sama lebih dulu karena urut nama berkas, dan migrasi W7
   mengisinya. Pemeriksaan sebelum pasang di Supabase:
   `SELECT count(*) FROM personal_targets` dan `SELECT count(*) FROM user_owned_items` harus 0 (kolom pemilik wajib
   tidak dapat dipasang di atas baris tanpa pemilik), dan `SELECT count(*) FROM hiking_history WHERE trip_plan_id IS
   NULL` harus 0 (CHECK sumber). Rekomendasi: masukkan ke daftar permintaan izin akhir bersama migrasi W6.
2. **Grafik baris mendatar (W7-R5).** Rekomendasi: terima; batang tegak prototype 13 tidak muat di 320 px dengan nama
   jalur yang panjang. Bila salah: satu komponen `GrafikPendakian.tsx`.
3. **Peta gunung dan dampak laporan tetap di Progres (W7-R6).** Rekomendasi: terima. Bila salah: dua bagian di
   `HikerProgress.tsx` dicabut bersama test `HikerProgressTest::test_the_map_draws_only_mountains_with_coordinates_from_completed_hikes`
   dan `ReporterImpactTest`.
4. **Satu jenis trip di onboarding (W7-R10).** Prototype 00 membolehkan beberapa jenis; `preferred_trip_type` adalah satu
   enum yang dibaca Route Fit (`CompatibilityScorer::tripPreference`), dan menjadikannya larik mengubah Route Fit (R-021).
   Rekomendasi: tetap satu sampai pemilik memutus aturan kecocokan untuk beberapa jenis.
5. **Catatan butir hanya Perlengkapan dan Logistik (W7-R9).** Rekomendasi: terima. Bila salah: ubah
   `CatatanButir::KATEGORI`.
6. **Tautan "Kebijakan data pribadi" di Profil.** Prototype 14 memuatnya, tetapi belum ada halaman publiknya;
   `docs/KEBIJAKAN-DATA-PRIBADI.md` adalah dokumen repo. Menurut DK-40, kendali yang belum berfungsi tidak ditampilkan.
   Rekomendasi: pemilik memutus halaman publik kebijakan (isi dan tempatnya) di gelombang berikut.
7. **Titik tengah sebagai pemisah meta (W7-R20).** `DESIGN.md` bagian Tipografi membatasi titik tengah pada baris ukuran
   yang padat; prototype 13 memakainya di lencana dan keterangan pencapaian. Rekomendasi: ikuti prototype dan tambah
   pengecualian ini di `DESIGN.md` bila pemilik setuju; bila tidak, ganti dengan koma di `HubProgres::lencana()` dan
   `PencapaianPribadi`.
8. **Kolom teks bebas lama.** `profiles.region_preference`, `profiles.bio`, `user_preferences.region_preference`, dan
   `user_preferences.preferred_challenge` tidak lagi diisi lewat antarmuka; nilainya dipertahankan dan tidak ditimpa
   (`InterestRegionTest::test_saving_keeps_the_old_free_text_preferences`). Rencana masih membaca
   `user_preferences.region_preference` untuk isi awal tujuan pendakian (`GoalController`). Rekomendasi: pemilik memutus
   apakah kolom itu dipindah ke wilayah minat atau dibuang (mengubah data, butuh izin).
9. **Baris status program di `docs/PRD.md` basi sesudah W7.** Baris "8. Mode pendakian, progres, akun" menyebut target
   pribadi dan catatan manual belum ada. Mengubah PRD butuh governance record (`CLAUDE.md` bagian 3), jadi rencana tidak
   menyentuhnya. Rekomendasi: koordinator memperbaruinya bersama GR berikutnya.
10. **Arti `longest_hike_duration_minutes` (W7-R18).** Rekomendasi: terima; kolom ini tidak dibaca Route Fit. Bila salah:
    batas 24 jam di `StoreProfileSetupRequest` dan isiannya.
11. **Foto profil: penangan unggahan baru dan kategori data pribadi baru (W7-R12).** Skill security-and-hardening
    menggolongkan keduanya "Ask First" (butuh persetujuan manusia). Dasarnya ada: spec bagian 6.15 dan induk W7 butir 5
    meminta foto profil dengan EXIF dibuang, dan `docs/KEBIJAKAN-DATA-PRIBADI.md` bagian 2 dan 5 sudah mengatur foto
    pribadi dan foto profil. Yang belum dilihat pemilik adalah kodenya: disk privat lokal sebagai bawaan
    (`AVATARS_DISK`), batas 2 MB dan 64 sampai 8.000 piksel, dan penyajian hanya kepada pemilik. Bukti penolakan berkas
    skrip: test memakai `UploadedFile::fake()`, yang tipenya ditebak dari ekstensi, jadi skrip berekstensi `.jpg`
    ditolak aturan `dimensions`, bukan `image`; probe di simulasi (unggahan sungguhan dari berkas sementara) menunjukkan
    aturan `image` menolaknya dengan "Berkas itu bukan foto", dan `ImageSanitizer` adalah lapis ketiga. Rekomendasi:
    pemilik mengonfirmasi bersama permintaan izin akhir; disk produksi (lokal atau S3) adalah keputusan deploy.
12. **Kabar per halaman (W7-R15).** Repo nyata membedakan `status` dan `kabar-aksi` (W4-FK N1). Rekomendasi: terima untuk
    W7; menyatukan pola kabar di seluruh aplikasi adalah pekerjaan tersendiri.
13. **Akhir onboarding.** Dulu menuju `goals.create` dengan kabar "Profil pendaki tersimpan."; kini halaman hasil dengan
    angka Cocok dan tautan ke Jelajah (spec bagian 6.16). Rute dan halaman tujuan pendakian tetap ada. Rekomendasi:
    terima.
14. **Riwayat `/history` memuat catatan manual (W7-R3).** Catatan manual tampil dengan "Dicatat manual", tanpa tautan
    trip dan ringkasan. Rekomendasi: terima.
15. **"Hapus semua jejak saya" tanpa urungkan (W7-R13).** Dialog menyebut akibatnya sebelum menghapus. Rekomendasi:
    terima.

### Pra-cek untuk koordinator

Rencana ditulis di atas keadaan akhir simulasi W6. Periksa ulang di repo nyata sesudah W6 selesai, sebelum Tugas 1.
Penulis rencana hanya memakai Read, Grep, dan Glob terhadap repo nyata.

| Asumsi | Tempat rencana bergantung | Keadaan |
|---|---|---|
| 1. Jangkar setiap blok "Ganti" ada tepat sekali | Semua tugas | Sesuai di dasar dan pada putaran ulang. Di pohon kerja repo nyata (Grep multiline dengan `\r?\n` di antara baris, 8 Oktober dini hari, saat W5 sedang dieksekusi dan sebelum W6): 105 dari 105 blok yang teks lamanya berasal dari dasar ditemukan tepat sekali; 24 blok berjangkar teks tugas W7 sebelumnya dibuktikan putaran ulang. Jangkar di berkas yang berbeda karena perbaikan akhir W4 sengaja satu baris (`routes/web.php` Tugas 6, dokumen Tugas 12) |
| 1a. Berkas yang ditulis ulang utuh atau dihapus belum berubah sejak dasar | Tugas 2 (`HikerProgressController.php`, `HikerProgressService.php`, `HikerProgress.tsx`, `HikerProgressTest.php`, `ProgressHierarchyTest.php`, `GrafikTanjakan.tsx` dihapus), Tugas 1, 3, 5, 9 (empat test rintisan), Tugas 7 (`ProfileSetupController.php`, `StoreProfileSetupRequest.php`, `ProfileSetup.tsx`), Tugas 10 (`RingkasanProfilPendakian.tsx` dan `akun/types.ts` dihapus) | Blob dasar (12 karakter pertama `git hash-object`): `HikerProgressController.php` 8f1f2c6e4601, `HikerProgressService.php` 306340b567ff, `HikerProgress.tsx` 6e5ce8f908f5, `HikerProgressTest.php` a57fc0033044, `ProgressHierarchyTest.php` 55445290ccfb, `GrafikTanjakan.tsx` cc790e772b9c, `ManualHikeRecordTest.php` 052509aef2f1, `OwnedEquipmentTest.php` 5e2441ed830d, `PersonalDataWriteGuardTest.php` 21c77a61f591, `PersonalTargetTest.php` b5e43ab1d39d, `ProfileSetupController.php` 9a1258aa6200, `StoreProfileSetupRequest.php` b7c9449c18b5, `ProfileSetup.tsx` e0d78c445504, `RingkasanProfilPendakian.tsx` 720d0f4f7165, `akun/types.ts` bb898f4f233a. Jumlah baris kelima belas berkas di repo nyata sama dengan dasar (Grep, 8 Oktober). Bila blob di repo nyata berbeda, perubahan itu digabung manual; jangan menimpa |
| 2. Kolom W6 di `hiking_history` (`recorded_gain_m`, `recorded_distance_m`, `recorded_moving_minutes`, `recorded_highest_m`, `numbers_edited_at`) dan `trip_plans.cancellation_reason` dengan enum `CancellationReason` | Tugas 1, 2, 11 | Dibuat W6 Tugas 5 dan 6 (`2026_10_07_000300`, `000400`); ada di dasar. Di repo nyata baru ada sesudah W6 |
| 3. `HasilPendakian::TONGGAK_TANJAKAN` dan `HasilPendakian::durasiSesi()` | Tugas 1, 4 | Ada di dasar (W6 Tugas 6) |
| 4. Tidak ada pemanggil lain `HikerProgressService::forUser()`, `tanjakanPerPendakian()`, `summitedMountains()`, `GrafikTanjakan`, `TitikTanjakan`, `RingkasanProfilPendakian`, `components/akun/types`, atau prop `profilPendakian` | Tugas 2, 10 | Grep repo nyata 8 Oktober: hanya `HikerProgressController.php`, `HikerProgress.tsx`, `GrafikTanjakan.tsx`, `community/types.ts`, `HikerProgressTest.php`, `ReporterImpactTest.php`, `ProgressHierarchyTest.php`, `Profile/Edit.tsx`, `ProfileController.php`, `ProfileTest.php`, dan berkas itu sendiri, semuanya disunting rencana ini; karena itu penciutan `HikerProgressService` (W7-R17) tidak dibawa sebagai konflik. Ulangi Grep sesudah W6 |
| 5. `HalamanJelajah::props(TrailSearch, User, Request)` mengembalikan `ringkasan.jumlah`; `TrailSearch::dariInput(['kecocokan' => 'COCOK'])` menyaring hanya untuk pembaca yang dinilai | Tugas 7 | Sesuai di dasar dan di repo nyata (baris `props()` sama, Grep) |
| 6. `TripStatus::isActive()`, `PreparationService::PERMIT_LABEL`, konstruktor `PreparationService` satu parameter, dan `HalamanPersiapan::props()` yang diakhiri baris `'pengingat'` | Tugas 5, 6 | Sesuai di dasar dan di repo nyata (Grep; repo nyata punya `penolakanDaftar` tambahan dari perbaikan akhir W4 yang tidak tertabrak) |
| 7. `Preparation.tsx` memuat baris `import { KepalaTrip }` dan blok `{item.tautan ? (...) : null}` tepat sebelum `</li>` | Tugas 6 | Sesuai di dasar dan di repo nyata (repo nyata 533 baris dengan `dapatDiubah`; komponen catatan membaca haknya sendiri dari prop `catatanButir`) |
| 8. Prop bersama `tampilan` dan rute `tampilan.simpan`; `ThemePicker`, `Sakelar`, `ChipPilihan`, `Stepper`, `LembarBawah`, `Dialog`, `Avatar` dengan prop `foto` | Tugas 7, 8, 10 | Sesuai di dasar |
| 9. `PintuAkun` versi W6 (Keluar lewat `keluar()`) dan `PageHeaderContractTest` versi W6 | Tugas 8, 10 | Tugas 8 hanya mengganti satu baris asersi avatar; Tugas 10 memakai `<PintuAkun />` apa adanya |
| 10. `config('hiking.rate_limits.personal_writes_per_minute')` bernilai 30 | Tugas 1, 3, 5, 6, 8, 9 | Sesuai di dasar dan di repo nyata |
| 11. `self::ASAL` di harness pratinjau | Tugas 2, 6, 7, 10, 11 | Rencana memakai `self::ASAL` apa adanya |
| 12. GD dan exif terpasang di PHP | Tugas 8 | `php -m` di simulasi: `gd`, `exif`, `fileinfo` ada |
| 13. Jangkar dokumen Tugas 12 | Tugas 12 | Dua puluh dua jangkar satu baris atau sepenggal baris, masing-masing ditemukan tepat sekali di repo nyata (Grep). Dokumen fitur 04 di repo nyata berbeda dengan dasar karena perbaikan akhir W4 (baris "Persiapan"), jadi jangkarnya penggalan "isi awal dari perlengkapan pribadi menyusul W" yang ada di keduanya |
| 14. Rintisan `2026_09_25_000919`, `000920`, dan `000921` tidak disunting | Tugas 1, 3, 5, 13 | Blob dasar (12 karakter pertama `git hash-object`): `000919` 8282331e8724 (28 baris), `000920` 66461dc7b252 (27 baris), `000921` 463cfc30975c (27 baris). Jumlah baris ketiganya di repo nyata sama (Grep, 8 Oktober). Koordinator mencatat blob repo nyata sebelum Tugas 1 dan membandingkannya di Tugas 13 Step 1 |

## Perbandingan skill (DK-57)

Urutan wewenang sama dengan W4 sampai W6: arahan pemilik dan aturan kanonis, lalu `DESIGN.md` (arah, dial ENERGY 2 /
RHYTHM 2 / MOTION 2 untuk layar aplikasi) dan prototype 00, 13, dan 14 (patokan), lalu antislop, lalu saran skill.
antislop berlaku mode 1 (selama pekerjaan) atas arahan pemilik yang sudah berdiri; tidak ada pertanyaan baru kepada
pemilik. Launcher impeccable tidak dijalankan: ia butuh direktori kerja repo nyata dan dapat mengunduh biner, keduanya di
luar pagar; konteksnya dibaca langsung dari `DESIGN.md`, `PRODUCT.md`, dan acuan. Skrip ui-ux-pro-max dijalankan dari
salinan skill di dalam salinan simulasi (`.agents/skills/ui-ux-pro-max`, direktori kerja salinan). Panduan
web-design-guidelines diambil sekali lewat WebFetch (teks panduan, bukan aset atau dependensi).

| Skill | Diambil | Ditolak dan alasannya |
|---|---|---|
| antislop (inti) | R-26: setiap kendali bekerja (catat, ubah, dan hapus catatan manual; simpan dan copot target; Tanjakan atau Jarak; tambah, ganti nama, dan hapus barang; tambah, ubah, dan kosongkan catatan butir; pasang, ganti, dan hapus foto; tema, kurangi efek transparan, kurangi gerak; hapus semua jejak; onboarding Lanjut, Kembali, Nanti saja, Ubah nilai, "Jalurnya tidak ada di daftar", "Saya belum pernah mendaki"; keluar). R-27: keadaan kosong yang mengajarkan langkahnya ("Belum ada target. Target bulanan untuk tanjakan, jarak, atau jumlah pendakian dibandingkan hanya dengan pendakian Anda sendiri.", "Belum ada barang. Barang yang Anda tambahkan masuk ke daftar persiapan trip berikutnya, belum dikonfirmasi."), memproses (tombol nonaktif dan kalimat di wilayah status), galat terikat isian. R-17 dan R-36: angka yang tidak diketahui ditulis "Belum ada", tidak nol. R-23: foto profil hanya dari unggahan pemiliknya, selain itu inisial; data contoh tidak membuat foto | Tidak ada; antislop adalah saringan di atas `DESIGN.md` |
| antislop-copywriting | Kalimat yang menyebut akibatnya ("Target tidak mengubah label kecocokan dan tidak menaikkan tingkat pengalaman Anda.", "\"Headlamp\" dihapus dari perlengkapan pribadi. Daftar persiapan trip yang sudah ada tidak berubah."); galat yang menyebut cara memperbaiki ("Jumlah pendakian paling sedikit 1. Bila belum pernah mendaki sampai selesai, pilih \"Saya belum pernah mendaki\"."); aksi spesifik ("Catat pendakian di luar trip", "Tambah barang", "Hapus semua jejak saya") | Kalimat kepala prototype 13 "Tidak ada papan peringkat dan tidak ada catatan kecepatan" berpola negasi berderet, tetapi dipertahankan: itu janji R-015 dari patokan pemilik, bukan hiasan |
| antislop-human, antislop-layoutmobile | Kontras teks dan non-teks diukur di peramban pada kedua tema, tidak ditebak; fokus `focus-visible` di setiap kendali baru; status tidak lewat warna saja (batal: garis putus-putus dan kata "batal"; tercapai: centang dan kata); reflow diukur di 320, 390, 768, dan 1440 px; target 44 px dengan jarak antartarget; fokus kembali ke "Tambah barang" atau judul bagian sesudah barang terakhir dihapus | Tidak ada |
| ui-ux-pro-max | Tiga pencarian: "multi-step onboarding skip" (`--domain ux`): indikator langkah ("Langkah 2 dari 3"), "Kembali" dan "Nanti saja" selalu terlihat; "file upload error recovery" (`--domain ux`): galat di bawah isian, dibacakan, dengan langkah pemulihan ("Pilih foto JPG, PNG, atau WebP."); "bar chart mobile labels" (`--domain chart`): batang untuk membandingkan sampai 15 butir, label nilai langsung, tabel padanan | Saran grafik "setiap batang warna berbeda" dan "urutkan menurun menurut nilai": urutan waktu adalah perbandingan diri sendiri (R-015), urutan menurun membuat peringkat, dan satu seri memakai satu warna merek |
| design-taste-frontend | Skill ini menyatakan dirinya bukan untuk dasbor, formulir bertahap, atau UI produk (bagian 13), jadi hanya bagian umumnya dipakai: pembacaan desain satu baris (0.B), keadaan interaktif lengkap (4.5), label di atas isian dan galat di bawahnya (4.6), gerak dikurangi (6.B), titik tengah dijatah satu per baris metadata kecuali baris ukuran padat yang diizinkan `DESIGN.md` (9.F, sejalan dengan Konflik 7), tanpa em dash (9.G) | Dial bawaan 8/6/4, pustaka Motion, gambar picsum, dan aturan hero: tidak cocok untuk tiga layar Operate |
| impeccable (Operate; `reference/operate.md`, `reference/onboard.md`) | Kosakata komponen yang sudah ada dipakai ulang (`PageHeader`, `LembarBawah`, `Dialog`, `Sakelar`, `ThemePicker`, `Avatar`, `BarAksi`, `Button`, `Tautan`, `InputError`); kendali baru hanya `PilihanKartu` (grup radio berbentuk kartu atau chip) dan `PemilihJalur`; formulir di lembar bawah seperti W4 sampai W6 dan dialog hanya untuk konfirmasi yang tidak dapat dibatalkan. Onboarding: dapat dilewati, indikator langkah, alasan untuk pertanyaan yang tidak jelas dari judulnya (langkah 1 dan 2, dan wilayah: "Wilayah tidak mengubah label kecocokan."), dan hasil nyata sebagai langkah terakhir ("Dengan jawaban ini, N jalur berlabel Cocok") dengan tautan berikutnya | Tur, tooltip, dan penanda "sudah dilihat" di localStorage: jawaban disimpan di server, dan "Nanti saja" hanya tampil selama profil belum lengkap |
| frontend-ui-engineering | Komponen per bagian layar (`components/progres`, `components/profil`, `components/onboarding`) dengan tipe di `types.ts` per folder; keadaan kosong dan galat per bagian; urutan judul satu `h1` per halaman; diperiksa di 320, 768, dan 1440 px | Batas 200 baris sebagai aturan keras: `ProfileSetup.tsx` 289 baris dan `PengalamanTerberat.tsx` 235 baris lebih kecil dari halaman setara di repo (`Trips/Create.tsx` 436, `Trips/Readiness.tsx` 489), dan memecah lagi menambah prop berlapis tanpa pemakai kedua. Skeleton memuat: halaman dirender server lewat Inertia |
| vercel-react-best-practices | `rerender-no-inline-components` (tidak ada komponen di dalam komponen), `rendering-conditional-render` (ternary, tanpa `&&` di JSX), debounce dan pembatalan: pencarian jalur ditunda 300 ms, permintaan lama dibatalkan, hasil usang diabaikan (`useCariJalur`); efek hanya untuk memindah fokus sesudah DOM berganti | Aturan khusus Next.js dan RSC (`next/dynamic`, `React.cache`) tidak berlaku untuk Inertia |
| vercel-composition-patterns | Varian eksplisit (`PilihanKartu bentuk="kartu" \| "chip"`, bukan boolean), isi sebagai anak (`BagianRincian`), tanpa `forwardRef` (React 19) | Context provider: keadaan setiap bagian milik bagian itu sendiri. Satu prop boolean tersisa (`labelTersembunyi` di `PilihanKartu`) hanya memilih kelas `sr-only` untuk legenda yang sudah diwakili judul langkah |
| web-design-guidelines (panduan diambil 8 Oktober 2026) | Ikon dekoratif `aria-hidden`; gambar dengan `width`, `height`, dan `alt`; `tabular-nums` untuk angka; `focus-visible` sebagai pengganti `outline`; konfirmasi sebelum aksi yang tidak dapat dibuat ulang (catatan pendakian, jejak, akun); galat di dekat isian | Elipsis "…" untuk teks memuat dan placeholder: konvensi repo adalah kalimat bertitik ("Memuat isi halaman."); Title Case: bahasa Indonesia memakai huruf kapital kalimat; konfirmasi untuk menghapus barang perlengkapan dan foto: keduanya dapat dipasang lagi dalam satu langkah, dan kabar sesudahnya menyebut akibatnya |
| dataviz (grafik per pendakian) | Bentuk dipilih dulu: batang mendatar satu seri pada satu sumbu, Tanjakan dan Jarak sebagai dua tampilan, bukan sumbu ganda; satu seri tanpa legenda (judulnya menamai); warna dari token dan teks memakai warna teks, bukan warna seri; tabel padanan untuk pembaca layar; batal ditandai garis dan kata, bukan warna saja; dirender dan dilihat di 320, 390, dan 1440 px | Lapisan tooltip saat hover: nilai sudah tertulis di setiap baris dan layar dipakai dengan sentuhan; "jangan menulis angka di setiap titik": paling banyak dua belas baris dibaca sebagai daftar berbatang, dan angka tertulis menggantikan tooltip; validator palet: tidak ada palet kategorikal |
| security-and-hardening | Model ancaman per batas. Unggahan foto: pemalsuan tipe dijaga `image`, `mimes`, `dimensions`, dan penulisan ulang GD; kebocoran lokasi dijaga EXIF dibuang; beban dijaga 2 MB dan 8.000 piksel; pembacaan orang lain dijaga rute tanpa id dan `Cache-Control: private`. Rute tulis data pribadi: IDOR dijaga Policy, mass assignment dijaga `validated()` dan pemilik dari sesi, laju dijaga kunci per fitur. Penghapusan: akun menghapus target, perlengkapan, catatan, wilayah, foto, dan jejak; "Hapus semua jejak saya" lewat dialog. Jalur berkas yang dihapus berasal dari basis data, jadi `FotoProfil::sah()` hanya menerima nama acak langsung di map `avatars`; nilai lain tidak disajikan dan tidak dihapus. Klasifikasi: foto wajah adalah data pribadi baru | Butir "Ask First" (penangan unggahan baru, kategori data pribadi baru) diteruskan ke pemilik sebagai Konflik 11, bukan diputus diam-diam |
| laravel-best-practices | Form Request untuk setiap tulisan; Policy atau scope pemilik sesi; migrasi aditif dengan `down()` yang jujur (catatan manual dihapus lebih dulu sebelum kolom trip kembali wajib); indeks mengikuti kueri (`user_id, completed_at`; unik ekspresi `lower(name)`); keunikan dijaga basis data dan tabrakan kiriman bersamaan dikembalikan sebagai galat isian; kueri halaman berbatas (`NewPageQueryBudgetTest`) | Antarmuka repository untuk data pribadi: satu implementasi, lapisan dangkal |
| testing-best-practices | Test perilaku lewat HTTP: tamu, pengguna lain, validasi beserta kalimat galatnya, dan batas laju untuk setiap rute baru; `setUp` hanya menyiapkan konfigurasi; waktu dibekukan bila tanggal menentukan hasil; test rintisan diganti test perilaku dengan alasan di docblock | 404 untuk akses lintas pengguna: konvensi proyek 403 didahulukan sampai Q5 diputus (W7-R21) |

**Pembacaan desain (antislop bagian 3):** Progres, Profil, dan onboarding aplikasi perencanaan pendakian untuk pendaki
pemula Indonesia di ponsel, mode Operate, dial ENERGY 2 / RHYTHM 2 / MOTION 2 (`DESIGN.md`), tanpa gerak baru. Progres
menjawab "apa yang sudah saya capai dibanding diri saya sendiri", Profil menjawab "apa yang Memount tahu tentang saya dan
bagaimana mengubahnya", dan onboarding menjawab "jalur mana yang cocok untuk saya" secepat mungkin. Motif identitas tetap
garis rute berujung cincin pos; di W7 ia muncul sebagai baris batang per pendakian pada satu garis dasar.

**Inventaris skill untuk gelombang ini (CLAUDE.md bagian 7):**

- INTI (CORE): superpowers:writing-plans, testing-best-practices, laravel-best-practices, security-and-hardening,
  antislop, antislop-copywriting, antislop-human, antislop-layoutmobile, impeccable, ui-ux-pro-max, dataviz.
- PENDUKUNG (SUPPORTING): design-taste-frontend (terbatas, lihat tabel), frontend-ui-engineering,
  vercel-react-best-practices, vercel-composition-patterns, web-design-guidelines.
- LINTAS (CROSS-CUTTING): superpowers:verification-before-completion dan Delivery Gate antislop; chrome-devtools MCP untuk
  pengukuran dan tangkapan.
- N/A:
  - antislop-ui dan antislop-code: tidak dimuat di sesi ini; butir visualnya diwakili antislop inti dan `DESIGN.md`, dan
    komentar kode mengikuti konvensi docblock repo;
  - shadcn: rujukan pola saja di W4 sampai W6; tidak ada komponen baru yang butuh pola di luar kosakata repo;
  - tailwindcss-development: tidak ada token, varian, atau konfigurasi Tailwind baru;
  - archify, slides, banner-design, brand, design, design-system, ui-styling: bukan artefak halaman, dan aset logo
    menunggu gerbang DK-16;
  - animate dan skill gerak lain: W7 tidak menambah gerak;
  - livewire-development dan volt-development: Livewire sudah dipensiunkan;
  - deploying-to-cloud dan deploy-to-vercel: tanpa rilis;
  - infer-conventions dan codebase-design: konvensi dibaca langsung dari berkas saudara;
  - ngodingpakeai, caveman, dan skill alur commit: tanpa commit (keputusan pemilik).

## File Structure

**Dibuat:**

- Tugas 1: `app/Enums/HikeRecordSource.php`, `app/Http/Controllers/Community/ManualHikeController.php`, `app/Http/Requests/Community/SaveManualHikeRequest.php`, `app/Policies/HikingHistoryPolicy.php`, `database/migrations/2026_10_08_000100_add_source_to_hiking_history_table.php`, `tests/Spatial/HikingHistorySourceSchemaTest.php`.
- Tugas 2: `app/Services/Community/HubProgres.php`, `app/Services/Trails/CariJalur.php`, `resources/js/components/progres/` (`types.ts`, `RingkasanTahun.tsx`, `GrafikPendakian.tsx`, `RiwayatProgres.tsx`, `FormulirCatatan.tsx`), `resources/js/components/trails/PemilihJalur.tsx`.
- Tugas 3: `app/Enums/PersonalTargetMetric.php`, `app/Http/Controllers/Community/PersonalTargetController.php`, `app/Http/Requests/Community/UpdatePersonalTargetsRequest.php`, `app/Models/PersonalTarget.php`, `database/factories/PersonalTargetFactory.php`, `database/migrations/2026_10_08_000200_complete_personal_targets_table.php`, `resources/js/components/progres/TargetPribadi.tsx`, `tests/Spatial/PersonalTargetSchemaTest.php`.
- Tugas 4: `app/Services/Community/PencapaianPribadi.php`, `resources/js/components/progres/Pencapaian.tsx`, `tests/Feature/PersonalAchievementTest.php`.
- Tugas 5: `app/Http/Controllers/Profile/OwnedItemController.php`, `app/Http/Requests/Profile/SaveOwnedItemRequest.php`, `app/Models/UserOwnedItem.php`, `app/Policies/UserOwnedItemPolicy.php`, `app/Services/Trips/IsiAwalPerlengkapan.php`, `database/factories/UserOwnedItemFactory.php`, `database/migrations/2026_10_08_000300_complete_user_owned_items_table.php`, `database/migrations/2026_10_08_000400_add_owned_item_to_trip_preparation_items_table.php`, `tests/Spatial/UserOwnedItemSchemaTest.php`.
- Tugas 6: `app/Http/Controllers/Trips/PreparationNoteController.php`, `app/Http/Requests/Trips/UpdatePreparationNoteRequest.php`, `app/Services/Trips/CatatanButir.php`, `database/migrations/2026_10_08_000500_add_note_to_trip_preparation_items_table.php`, `resources/js/components/trips/CatatanButir.tsx`, `tests/Feature/PreparationNoteTest.php`, `tests/Spatial/PreparationItemNoteSchemaTest.php`.
- Tugas 7: `app/Enums/InterestRegion.php`, `app/Services/Profil/TurunanPengalaman.php`, `database/migrations/2026_10_08_000600_add_interest_regions_to_user_preferences_table.php`, `resources/js/components/onboarding/` (`types.ts`, `PilihanKartu.tsx`, `PengalamanTerberat.tsx`), `tests/Feature/InterestRegionTest.php`, `tests/Feature/OnboardingDerivationTest.php`, `tests/Spatial/InterestRegionsSchemaTest.php`.
- Tugas 8: `app/Http/Controllers/Profile/ProfilePhotoController.php`, `app/Http/Requests/Profile/UpdateProfilePhotoRequest.php`, `app/Services/Profil/FotoProfil.php`, `tests/Feature/ProfilePhotoTest.php`.
- Tugas 9: `app/Http/Controllers/Profile/TrackDataController.php`, `tests/Feature/PersonalDataDeletionTest.php`.
- Tugas 10: `app/Services/Profil/HalamanProfil.php`, `resources/js/components/profil/` (`types.ts`, `IdentitasProfil.tsx`, `BagianRincian.tsx`, `PerlengkapanPribadi.tsx`, `TampilanProfil.tsx`, `DataLokasi.tsx`).

**Dihapus:** `resources/js/components/community/GrafikTanjakan.tsx` (Tugas 2), `resources/js/components/akun/RingkasanProfilPendakian.tsx` dan `resources/js/components/akun/types.ts` (Tugas 10).

**Diubah:**

- Tugas 1: `app/Http/Controllers/Community/HikingHistoryController.php`, `app/Models/HikingHistory.php`, `database/factories/HikingHistoryFactory.php`, `resources/js/pages/Community/HikingHistory.tsx`, `routes/web.php`, `tests/Feature/HikingHistoryPageTest.php`, `tests/Feature/ManualHikeRecordTest.php` (rintisan ditulis ulang utuh).
- Tugas 2: `app/Http/Controllers/Community/HikerProgressController.php`, `app/Services/HikerProgressService.php`, `resources/js/pages/Community/HikerProgress.tsx` (ketiganya ditulis ulang utuh), `resources/js/components/community/types.ts`, `tests/Feature/HikerProgressTest.php` dan `tests/Feature/ProgressHierarchyTest.php` (ditulis ulang utuh), `tests/Feature/ReporterImpactTest.php`, `tests/Pratinjau/HalamanPratinjauTest.php`.
- Tugas 3: `app/Models/User.php`, `app/Services/Community/HubProgres.php`, `resources/js/components/progres/types.ts`, `resources/js/pages/Community/HikerProgress.tsx`, `routes/web.php`, `tests/Feature/PersonalTargetTest.php` (rintisan ditulis ulang utuh), `tests/Feature/ProgressHierarchyTest.php`, `tests/Pratinjau/HalamanPratinjauTest.php`.
- Tugas 4: `app/Services/Community/HubProgres.php`, `resources/js/components/progres/types.ts`, `resources/js/pages/Community/HikerProgress.tsx`, `tests/Feature/ProgressHierarchyTest.php`.
- Tugas 5: `app/Models/TripPreparationItem.php`, `app/Models/User.php`, `app/Services/PreparationService.php`, `routes/web.php`, `tests/Feature/OwnedEquipmentTest.php` (rintisan ditulis ulang utuh), `tests/Unit/PreparationServiceTest.php`.
- Tugas 6: `app/Models/TripPreparationItem.php`, `app/Services/Trips/HalamanPersiapan.php`, `resources/js/pages/Trips/Preparation.tsx`, `routes/web.php`, `tests/Pratinjau/HalamanPratinjauTest.php`.
- Tugas 7: `app/Http/Controllers/Onboarding/ProfileSetupController.php`, `app/Http/Requests/StoreProfileSetupRequest.php`, `resources/js/pages/Onboarding/ProfileSetup.tsx` (ketiganya ditulis ulang utuh), `app/Enums/ExperienceLevel.php`, `app/Enums/NavigationExperience.php`, `app/Models/UserPreference.php`, `tests/Feature/EmptyStateCoverageTest.php`, `tests/Feature/NavigationConsistencyTest.php`, `tests/Feature/RecommendationFlowTest.php`, `tests/Pratinjau/HalamanPratinjauTest.php`.
- Tugas 8: `.env.example`, `app/Http/Controllers/Profile/ProfileController.php`, `app/Http/Middleware/HandleInertiaRequests.php`, `config/filesystems.php`, `config/hiking.php`, `resources/js/components/ui/TautanProfil.tsx`, `resources/js/layouts/MenuAkun.tsx`, `resources/js/lib/bukanHalamanReact.json`, `resources/js/types/index.d.ts`, `routes/web.php`, `tests/Feature/PageHeaderContractTest.php`.
- Tugas 9: `routes/web.php`, `tests/Feature/PersonalDataWriteGuardTest.php` (rintisan ditulis ulang utuh).
- Tugas 10: `app/Http/Controllers/Profile/ProfileController.php`, `resources/js/pages/Profile/Edit.tsx`, `tests/Feature/InterestRegionTest.php`, `tests/Feature/NewPageQueryBudgetTest.php`, `tests/Feature/ProfileTest.php`, `tests/Pratinjau/HalamanPratinjauTest.php`.
- Tugas 11: `database/seeders/DemoSeeder.php`, `tests/Feature/DemoSeederTest.php`, `tests/Pratinjau/HalamanPratinjauTest.php`.
- Tugas 12: `DESIGN.md`, `docs/ARCHITECTURE.md`, `docs/fitur/04-perjalanan-persiapan.md`, `docs/fitur/06-pendakian-progres.md`, `docs/fitur/07-akun-masuk.md`.

Tugas 13 (koordinator) membuat `docs/desain/tangkapan/w7/*.webp` dan `docs/desain/tangkapan/w7/README.md`, dan
memperbarui `HANDOVER.md` bagian 0 serta `.superpowers/sdd/2026-09-26-memount-v3-program.md`.

---

### Task 1: Riwayat dua sumber dan catat pendakian di luar trip

FR-25 (PRD §150), spec v3 bagian 6.14 dan 9.3, induk W7 butir 3; ruling W7-R2, W7-R3, dan W7-R21. `hiking_history`
mendapat kolom asal (`source`: `TRIP` atau `MANUAL`) dan nama jalur bebas, sedangkan `trip_plan_id` dan `trail_id` boleh
kosong untuk catatan manual. Migrasi aditif baru `2026_10_08_000100` mengisi rintisan `2026_09_25_000919` tanpa
menyuntingnya. Pendaki mencatat, memperbaiki, dan menghapus pendakian di luar trip lewat tiga rute berbatas laju
`catatan-pendakian`; catatan dari trip tidak dapat diubah lewat rute ini. Tanggal tidak boleh di masa depan menurut zona
WIT, angka yang dikosongkan tetap tidak diketahui (R-007), lokasi tidak dicatat, dan catatan tidak menaikkan tingkat
pengalaman (BR-11). Halaman `/history` memuat catatan manual dengan "Dicatat manual". Formulirnya dipasang di Progres
pada Tugas 2.

**Files:**
- Create: `app/Enums/HikeRecordSource.php`, `app/Http/Controllers/Community/ManualHikeController.php`, `app/Http/Requests/Community/SaveManualHikeRequest.php`, `app/Policies/HikingHistoryPolicy.php`, `database/migrations/2026_10_08_000100_add_source_to_hiking_history_table.php`
- Modify: `app/Http/Controllers/Community/HikingHistoryController.php`, `app/Models/HikingHistory.php`, `database/factories/HikingHistoryFactory.php`, `resources/js/pages/Community/HikingHistory.tsx`, `routes/web.php`
- Test: `tests/Feature/ManualHikeRecordTest.php` (rintisan ditulis ulang utuh), `tests/Feature/HikingHistoryPageTest.php`, `tests/Spatial/HikingHistorySourceSchemaTest.php` (baru, PostgreSQL)

**Interfaces:**
- Consumes: kolom W6 `hiking_history.recorded_gain_m`, `recorded_distance_m`, `recorded_moving_minutes`, `recorded_highest_m`, `numbers_edited_at`; `App\Enums\TripType`; `App\Support\Timezone::latestDateInIndonesia(): string` dan `Timezone::DEFAULT`; `config('hiking.rate_limits.personal_writes_per_minute')`; `Tests\Spatial\SpatialTestCase`.
- Produces:
  - enum `App\Enums\HikeRecordSource` (`TRIP`, `MANUAL`, `label(): string`); cast `HikingHistory::$source`, bawaan atribut `TRIP`; `HikingHistory::namaJalur(): string` (nama jalur katalog atau `trail_name`) dan `HikingHistory::manual(): bool`; state pabrik `HikingHistoryFactory::manual()`;
  - kolom `hiking_history.source` (string 10, bawaan `TRIP`), `trail_name` (string 120, boleh kosong); `trip_plan_id` dan `trail_id` boleh kosong; indeks `hiking_history_pengguna_tanggal` (`user_id`, `completed_at`); di PostgreSQL CHECK `hiking_history_sumber_sah` dan `hiking_history_jalur_tercatat`;
  - rute `progress.records.store` (POST `progres/catatan`), `progress.records.update` (PUT `progres/catatan/{riwayat}`), `progress.records.destroy` (DELETE `progres/catatan/{riwayat}`), berbatas laju `catatan-pendakian`, kembali ke `progress` dengan flash `kabar-progres` `{bagian: 'riwayat', teks: string}`;
  - `SaveManualHikeRequest` (isian `trail_id` atau `nama_jalur` 3 sampai 120 karakter, `tanggal` `Y-m-d` paling lambat hari ini di WIT, `jenis` `TripType`, `jarak_km` 0,1 sampai 200, `naik_m` 0 sampai 9.000, `waktu_menit` 1 sampai 7.200, `tertinggi_m` 0 sampai 6.000, `catatan` paling panjang `CATATAN_MAKS` 1.000) dengan `kolom(): array` (kolom `hiking_history` yang ditulis, `completed_at` pukul 12.00 WIB tanggal itu);
  - `HikingHistoryPolicy::update(User, HikingHistory): bool` dan `delete(User, HikingHistory): bool` (hanya catatan manual milik pengguna sendiri; lainnya 403).

- [ ] **Step 1: Tulis test yang gagal.** `ManualHikeRecordTest.php` sudah ada sebagai rintisan; ganti isinya utuh. Test
Spatial dibuat langsung di `tests/Spatial/`. `HikingHistoryPageTest` mendapat test baru untuk catatan manual di `/history`.

Buat `tests/Spatial/HikingHistorySourceSchemaTest.php`:

```php
<?php

namespace Tests\Spatial;

use App\Models\HikingHistory;
use App\Models\TripPlan;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Riwayat dua sumber di PostgreSQL (R-024, ruling W7-R3): kolom asal dan nama jalur, trip dan
 * jalur yang kini boleh kosong, indeks tahun, dan dua CHECK yang hanya ada di PostgreSQL. Suite
 * SQLite tidak membuktikan satu pun dari itu. Migrasinya tidak dijalankan di Supabase sebelum
 * izin pemilik (spec v3 Q2).
 */
class HikingHistorySourceSchemaTest extends SpatialTestCase
{
    public function test_the_columns_index_and_checks_have_their_production_shape(): void
    {
        $kolom = collect(DB::select(
            "SELECT column_name, data_type, is_nullable, column_default, character_maximum_length
             FROM information_schema.columns
             WHERE table_schema = current_schema() AND table_name = 'hiking_history'"
        ))->keyBy('column_name');

        $this->assertSame(['character varying', 'NO', 10], [$kolom['source']->data_type, $kolom['source']->is_nullable, (int) $kolom['source']->character_maximum_length]);
        $this->assertStringContainsString("'TRIP'", (string) $kolom['source']->column_default, 'Baris lama dari trip mendapat asal TRIP.');
        $this->assertSame(['character varying', 'YES', 120], [$kolom['trail_name']->data_type, $kolom['trail_name']->is_nullable, (int) $kolom['trail_name']->character_maximum_length]);
        $this->assertSame('YES', $kolom['trip_plan_id']->is_nullable);
        $this->assertSame('YES', $kolom['trail_id']->is_nullable);

        $cek = collect(DB::select("SELECT conname FROM pg_constraint WHERE conrelid = 'hiking_history'::regclass AND contype = 'c'"))->pluck('conname')->all();

        $this->assertContains('hiking_history_sumber_sah', $cek);
        $this->assertContains('hiking_history_jalur_tercatat', $cek);
        $this->assertNotNull(DB::selectOne("SELECT 1 FROM pg_indexes WHERE tablename = 'hiking_history' AND indexname = 'hiking_history_pengguna_tanggal'"));
    }

    public function test_a_manual_row_that_points_at_a_trip_is_refused(): void
    {
        $trip = TripPlan::factory()->create();

        $this->expectException(QueryException::class);
        HikingHistory::factory()->manual()->create(['user_id' => $trip->user_id, 'trip_plan_id' => $trip->id, 'trail_id' => $trip->trail_id]);
    }

    public function test_a_trip_row_without_its_trip_is_refused(): void
    {
        $this->expectException(QueryException::class);
        HikingHistory::factory()->create(['trip_plan_id' => null]);
    }

    public function test_a_row_without_any_trail_is_refused(): void
    {
        $this->expectException(QueryException::class);
        HikingHistory::factory()->manual()->create(['trail_id' => null, 'trail_name' => null]);
    }
}
```

`tests/Feature/HikingHistoryPageTest.php`:

1. Ganti:

```php
    }

    public function test_the_page_does_not_grow_with_the_number_of_hikes(): void
```

dengan:

```php
    }

    /**
     * Catatan manual (FR-25) tanpa trip: tanpa tautan ringkasan dan tanpa "Persiapan saat itu";
     * jarak dan lamanya angka yang ditulis pendaki sendiri.
     */
    public function test_a_manual_record_is_listed_with_its_own_numbers(): void
    {
        $user = User::factory()->create();
        HikingHistory::factory()->manual()->for($user)->create(['trail_id' => null, 'trail_name' => 'Lawu via Cemoro Sewu', 'recorded_distance_m' => 7200, 'recorded_moving_minutes' => 330]);

        $this->actingAs($user)->get(route('history'))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->where('entri.data.0.jalur', 'Lawu via Cemoro Sewu')
            ->where('entri.data.0.manual', true)
            ->where('entri.data.0.tripId', null)
            ->where('entri.data.0.trailId', null)
            ->where('entri.data.0.persiapan', null)
            ->where('entri.data.0.jarak', '7,2 km')
            ->where('entri.data.0.durasi', '5 jam 30 menit'));
    }

    public function test_the_page_does_not_grow_with_the_number_of_hikes(): void
```

Tulis ulang `tests/Feature/ManualHikeRecordTest.php` utuh:

```php
<?php

namespace Tests\Feature;

use App\Enums\CompletionState;
use App\Enums\ExperienceLevel;
use App\Enums\HikeRecordSource;
use App\Enums\NavigationExperience;
use App\Enums\TripType;
use App\Models\HikingHistory;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Catat pendakian di luar trip (FR-25, spec v3 bagian 6.14; rencana induk W7 butir 3).
 *
 * Menggantikan test rintisan yang hanya membuka `/` (spec v3 bagian 3.5). Catatan manual
 * ditandai diisi sendiri (`source` MANUAL), dapat diperbaiki dan dihapus pemiliknya saja, tidak
 * pernah di masa depan, angkanya dalam rentang yang masuk akal atau kosong bila tidak diketahui
 * (R-007), dan tidak menaikkan tingkat pengalaman (BR-11).
 */
class ManualHikeRecordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-08 09:00:00', 'Asia/Jakarta'));
    }

    /**
     * @param  array<string, mixed>  $ubah
     * @return array<string, mixed>
     */
    private function isian(array $ubah = []): array
    {
        return [
            'trail_id' => '',
            'nama_jalur' => 'Lawu via Cemoro Sewu',
            'tanggal' => '2026-05-23',
            'jenis' => TripType::TEKTOK->value,
            'jarak_km' => '7,2',
            'naik_m' => '600',
            'waktu_menit' => '330',
            'tertinggi_m' => '1717',
            'catatan' => 'Berangkat pukul 02.00 dari basecamp.',
            ...$ubah,
        ];
    }

    public function test_a_hiker_records_a_hike_from_the_catalog(): void
    {
        $user = User::factory()->create();
        $jalur = Trail::factory()->published()->easy()->create(['name' => 'Batur via Toya Bungkah']);

        $this->actingAs($user)
            ->post(route('progress.records.store'), $this->isian(['trail_id' => (string) $jalur->id, 'nama_jalur' => 'diabaikan']))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('progress', ['tahun' => 2026]))
            ->assertSessionHas('kabar-progres', ['bagian' => 'riwayat', 'teks' => 'Pendakian dicatat di riwayat Anda.']);

        $riwayat = HikingHistory::sole();

        $this->assertSame($user->id, $riwayat->user_id);
        $this->assertSame(HikeRecordSource::MANUAL, $riwayat->source);
        $this->assertNull($riwayat->trip_plan_id);
        $this->assertSame($jalur->id, $riwayat->trail_id);
        $this->assertNull($riwayat->trail_name, 'Nama ketikan tidak disimpan bila jalurnya dari katalog.');
        $this->assertSame(CompletionState::COMPLETED, $riwayat->completion_state);
        $this->assertSame(TripType::TEKTOK, $riwayat->trip_type);
        $this->assertSame([7200, 600, 330, 1717], [$riwayat->recorded_distance_m, $riwayat->recorded_gain_m, $riwayat->recorded_moving_minutes, $riwayat->recorded_highest_m]);
        $this->assertSame('2026-05-23 05:00:00', $riwayat->completed_at->utc()->toDateTimeString(), 'Tanggal disimpan pukul 12.00 WIB supaya tetap tanggal yang sama di WITA dan WIT.');
        $this->assertSame('Berangkat pukul 02.00 dari basecamp.', $riwayat->personal_notes);
    }

    public function test_a_trail_outside_the_catalog_is_recorded_by_its_name(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('progress.records.store'), $this->isian())->assertSessionHasNoErrors();

        $riwayat = HikingHistory::sole();

        $this->assertNull($riwayat->trail_id);
        $this->assertSame('Lawu via Cemoro Sewu', $riwayat->trail_name);
        $this->assertSame('Lawu via Cemoro Sewu', $riwayat->namaJalur());
    }

    /**
     * Angka yang tidak dicatat pendaki tetap tidak diketahui, bukan nol (R-007).
     */
    public function test_numbers_that_were_not_written_stay_unknown(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('progress.records.store'), $this->isian(['jarak_km' => '', 'naik_m' => '', 'waktu_menit' => '', 'tertinggi_m' => '', 'catatan' => '']))
            ->assertSessionHasNoErrors();

        $riwayat = HikingHistory::sole();

        $this->assertNull($riwayat->recorded_distance_m);
        $this->assertNull($riwayat->recorded_gain_m);
        $this->assertNull($riwayat->recorded_moving_minutes);
        $this->assertNull($riwayat->recorded_highest_m);
        $this->assertNull($riwayat->personal_notes);
    }

    /**
     * Pendakian yang dicatat sudah terjadi: tanggal sesudah hari ini di zona Indonesia yang paling
     * dahulu berganti hari (WIT) ditolak.
     */
    public function test_a_date_in_the_future_is_refused(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('progress.records.store'), $this->isian(['tanggal' => '2026-10-09']))
            ->assertSessionHasErrors(['tanggal' => 'Tanggal pendakian tidak boleh di masa depan.']);

        $this->actingAs($user)
            ->post(route('progress.records.store'), $this->isian(['tanggal' => '2026-10-08']))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, HikingHistory::count());
    }

    public function test_numbers_outside_their_ranges_and_a_missing_trail_are_refused(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('progress.records.store'), $this->isian(['nama_jalur' => '', 'jarak_km' => '0', 'naik_m' => '9001', 'waktu_menit' => '0', 'tertinggi_m' => '-1', 'jenis' => 'TERBANG']))
            ->assertSessionHasErrors([
                'nama_jalur' => 'Pilih jalur dari katalog atau tulis nama jalurnya.',
                'jarak_km' => 'Jarak diisi dalam kilometer, 0,1 sampai 200.',
                'naik_m' => 'Tanjakan diisi dalam meter, 0 sampai 9.000.',
                'waktu_menit' => 'Waktu bergerak diisi dalam menit, 1 sampai 7.200.',
                'tertinggi_m' => 'Titik tertinggi diisi dalam meter, 0 sampai 6.000.',
                'jenis' => 'Pilih jenis pendakian: tektok, camping, atau multi-hari.',
            ]);

        $this->assertSame(0, HikingHistory::count());
    }

    /**
     * Jalur yang belum terbit tidak dapat dipilih dari katalog (R-022); namanya tetap boleh ditulis.
     */
    public function test_an_unpublished_trail_cannot_be_chosen_from_the_catalog(): void
    {
        $belumTerbit = Trail::factory()->easy()->unpublished()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('progress.records.store'), $this->isian(['trail_id' => (string) $belumTerbit->id]))
            ->assertSessionHasErrors('trail_id');

        $this->assertSame(0, HikingHistory::count());
    }

    /**
     * BR-11: catatan pendakian berat tidak mengubah tingkat pengalaman maupun pengalaman yang
     * diisi sendiri.
     */
    public function test_a_hard_hike_does_not_raise_the_experience_the_hiker_filled_in(): void
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::BEGINNER->value, 'completed_at' => now()]);
        $user->experience()->create(['completed_hikes_count' => 2, 'terrain_experience' => ['FOREST'], 'navigation_experience' => NavigationExperience::BASIC->value, 'highest_elevation_gain_m' => 700]);

        $this->actingAs($user)->post(route('progress.records.store'), $this->isian(['naik_m' => '2600', 'jarak_km' => '22,5']))->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertSame(ExperienceLevel::BEGINNER, $user->profile->experience_level);
        $this->assertSame(2, $user->experience->completed_hikes_count);
        $this->assertSame(700, $user->experience->highest_elevation_gain_m);
    }

    public function test_the_owner_corrects_and_deletes_their_record(): void
    {
        $user = User::factory()->create();
        $riwayat = HikingHistory::factory()->manual()->for($user)->create(['trail_id' => null, 'trail_name' => 'Lawu via Cemoro Sewu', 'completed_at' => Carbon::parse('2025-08-17 05:00:00')]);

        $this->actingAs($user)
            ->put(route('progress.records.update', $riwayat), $this->isian(['nama_jalur' => 'Lawu via Candi Cetho', 'naik_m' => '1200', 'tanggal' => '2025-08-16']))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('progress', ['tahun' => 2025]))
            ->assertSessionHas('kabar-progres', ['bagian' => 'riwayat', 'teks' => 'Catatan pendakian diperbarui.']);

        $riwayat->refresh();

        $this->assertSame('Lawu via Candi Cetho', $riwayat->trail_name);
        $this->assertSame(1200, $riwayat->recorded_gain_m);
        $this->assertSame('2025-08-16', $riwayat->completed_at->setTimezone('Asia/Jakarta')->toDateString());
        $this->assertSame(HikeRecordSource::MANUAL, $riwayat->source);

        $this->actingAs($user)
            ->delete(route('progress.records.destroy', $riwayat))
            ->assertRedirect(route('progress', ['tahun' => 2025]))
            ->assertSessionHas('kabar-progres', ['bagian' => 'riwayat', 'teks' => 'Catatan pendakian dihapus dari riwayat Anda.']);

        $this->assertNull($riwayat->fresh());
    }

    public function test_another_hiker_cannot_correct_or_delete_the_record(): void
    {
        $pemilik = User::factory()->create();
        $riwayat = HikingHistory::factory()->manual()->for($pemilik)->create(['trail_id' => null, 'trail_name' => 'Lawu via Cemoro Sewu']);
        $orangLain = User::factory()->create();

        $this->actingAs($orangLain)->put(route('progress.records.update', $riwayat), $this->isian(['nama_jalur' => 'Diubah orang lain']))->assertForbidden();
        $this->actingAs($orangLain)->delete(route('progress.records.destroy', $riwayat))->assertForbidden();

        $this->assertSame('Lawu via Cemoro Sewu', $riwayat->fresh()->trail_name);
    }

    /**
     * Riwayat dari trip diubah lewat ringkasan pendakiannya ("Ubah angka"), bukan lewat rute
     * catatan manual, dan tidak dapat dihapus dari sini.
     */
    public function test_a_record_from_a_trip_is_not_edited_or_deleted_here(): void
    {
        $user = User::factory()->create();
        $trip = TripPlan::factory()->create(['user_id' => $user->id]);
        $riwayat = HikingHistory::factory()->for($user)->create(['trip_plan_id' => $trip->id, 'trail_id' => $trip->trail_id]);

        $this->actingAs($user)->put(route('progress.records.update', $riwayat), $this->isian())->assertForbidden();
        $this->actingAs($user)->delete(route('progress.records.destroy', $riwayat))->assertForbidden();

        $this->assertNotNull($riwayat->fresh());
        $this->assertSame(HikeRecordSource::TRIP, $riwayat->fresh()->source);
    }

    /**
     * Pemilik, asal, trip, dan keadaan pendakian tidak pernah dibaca dari isian (proteksi mass
     * assignment).
     */
    public function test_owner_source_and_trip_never_come_from_the_request(): void
    {
        $user = User::factory()->create();
        $orangLain = User::factory()->create();
        $tripOrangLain = TripPlan::factory()->create(['user_id' => $orangLain->id]);

        $this->actingAs($user)->post(route('progress.records.store'), $this->isian([
            'user_id' => $orangLain->id,
            'source' => HikeRecordSource::TRIP->value,
            'trip_plan_id' => $tripOrangLain->id,
            'completion_state' => CompletionState::ABANDONED->value,
        ]))->assertSessionHasNoErrors();

        $riwayat = HikingHistory::sole();

        $this->assertSame($user->id, $riwayat->user_id);
        $this->assertSame(HikeRecordSource::MANUAL, $riwayat->source);
        $this->assertNull($riwayat->trip_plan_id);
        $this->assertSame(CompletionState::COMPLETED, $riwayat->completion_state);
    }

    public function test_a_guest_is_sent_to_sign_in(): void
    {
        $riwayat = HikingHistory::factory()->manual()->create(['trail_id' => null, 'trail_name' => 'Lawu via Cemoro Sewu']);

        $this->post(route('progress.records.store'), $this->isian())->assertRedirect(route('login'));
        $this->put(route('progress.records.update', $riwayat), $this->isian())->assertRedirect(route('login'));
        $this->delete(route('progress.records.destroy', $riwayat))->assertRedirect(route('login'));

        $this->assertSame(1, HikingHistory::count());
    }

    public function test_recording_is_rate_limited(): void
    {
        $batas = (int) config('hiking.rate_limits.personal_writes_per_minute');
        $user = User::factory()->create();

        foreach (range(1, $batas) as $i) {
            $this->actingAs($user)->post(route('progress.records.store'), $this->isian())->assertRedirect();
        }

        $this->actingAs($user)->post(route('progress.records.store'), $this->isian())->assertTooManyRequests();
        $this->assertSame($batas, HikingHistory::count());
    }
}
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='ManualHikeRecord|HikingHistoryPage|HikingHistorySourceSchema'`
Expected: FAIL. Pada simulasi rencana: 21 test, 3 lulus, 14 error, 4 dilewati. Yang merah: ketiga belas test `ManualHikeRecordTest` (rute `progress.records.*` dan state pabrik `manual()` belum ada) dan `HikingHistoryPageTest::test_a_manual_record_is_listed_with_its_own_numbers`. 4 test Spatial melewati dirinya tanpa `SPATIAL_TEST_DSN`.

- [ ] **Step 3: Tulis implementasinya.** Buat berkas dengan
`DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:enum --no-interaction HikeRecordSource`,
`... make:controller --no-interaction Community/ManualHikeController`,
`... make:request --no-interaction Community/SaveManualHikeRequest`,
`... make:policy --no-interaction HikingHistoryPolicy --model=HikingHistory`, dan
`... make:migration --no-interaction add_source_to_hiking_history_table` (ganti nama berkasnya menjadi
`2026_10_08_000100_add_source_to_hiking_history_table.php`), lalu isinya diganti persis.

Buat `app/Enums/HikeRecordSource.php`:

```php
<?php

namespace App\Enums;

/**
 * Asal satu baris riwayat pendakian (FR-25, ruling W7-R3): TRIP bila tercatat lewat siklus trip,
 * MANUAL bila pendaki mencatat sendiri pendakian di luar trip. Catatan manual ditandai diisi
 * sendiri, dapat diperbaiki dan dihapus, dan tidak pernah menaikkan tingkat pengalaman (BR-11).
 */
enum HikeRecordSource: string
{
    case TRIP = 'TRIP';
    case MANUAL = 'MANUAL';

    public function label(): string
    {
        return match ($this) {
            self::TRIP => 'Dari trip',
            self::MANUAL => 'Dicatat manual',
        };
    }
}
```

Buat `app/Http/Controllers/Community/ManualHikeController.php`:

```php
<?php

namespace App\Http\Controllers\Community;

use App\Enums\CompletionState;
use App\Enums\HikeRecordSource;
use App\Http\Controllers\Controller;
use App\Http\Requests\Community\SaveManualHikeRequest;
use App\Models\HikingHistory;
use App\Support\Timezone;
use Illuminate\Http\RedirectResponse;

/**
 * Catat pendakian di luar trip (FR-25, spec v3 bagian 6.14; ruling W7-R3). Pemilik dari sesi dan
 * asal MANUAL dari kode, tidak pernah dari isian. Catatan manual adalah pendakian yang selesai,
 * diisi sendiri, dapat diperbaiki dan dihapus, dan tidak menyentuh profil pendaki (BR-11).
 * Sesudah menulis, Progres dibuka pada tahun catatan itu, dengan kabarnya di bagian riwayat.
 */
class ManualHikeController extends Controller
{
    public function store(SaveManualHikeRequest $request): RedirectResponse
    {
        $riwayat = $request->user()->hikingHistory()->create([
            ...$request->kolom(),
            'source' => HikeRecordSource::MANUAL->value,
            'trip_plan_id' => null,
            'completion_state' => CompletionState::COMPLETED->value,
        ]);

        return $this->keProgres($riwayat, 'Pendakian dicatat di riwayat Anda.');
    }

    public function update(SaveManualHikeRequest $request, HikingHistory $riwayat): RedirectResponse
    {
        $riwayat->update($request->kolom());

        return $this->keProgres($riwayat, 'Catatan pendakian diperbarui.');
    }

    public function destroy(HikingHistory $riwayat): RedirectResponse
    {
        $this->authorize('delete', $riwayat);

        $riwayat->delete();

        return $this->keProgres($riwayat, 'Catatan pendakian dihapus dari riwayat Anda.');
    }

    private function keProgres(HikingHistory $riwayat, string $kabar): RedirectResponse
    {
        return redirect()
            ->route('progress', ['tahun' => $riwayat->completed_at->copy()->setTimezone(Timezone::DEFAULT)->year])
            ->with('kabar-progres', ['bagian' => 'riwayat', 'teks' => $kabar]);
    }
}
```

Buat `app/Http/Requests/Community/SaveManualHikeRequest.php`:

```php
<?php

namespace App\Http\Requests\Community;

use App\Enums\TripType;
use App\Models\HikingHistory;
use App\Models\Trail;
use App\Support\Timezone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Catatan pendakian di luar trip (FR-25, spec v3 bagian 6.14; ruling W7-R3), untuk mencatat
 * maupun memperbaikinya. Jalurnya dari katalog terbit atau nama yang ditulis sendiri; tanggal
 * tidak di masa depan menurut zona Indonesia yang paling dahulu berganti hari; setiap angka
 * opsional dengan rentang yang sama dengan "Ubah angka" ringkasan, karena yang tidak dicatat
 * tidak diketahui, bukan nol (R-007). Pemilik dan asal baris tidak pernah dibaca dari isian.
 */
class SaveManualHikeRequest extends FormRequest
{
    public const CATATAN_MAKS = 1000;

    public function authorize(): bool
    {
        $riwayat = $this->route('riwayat');

        if ($riwayat === null) {
            return $this->user() !== null;
        }

        return $riwayat instanceof HikingHistory && $this->user()?->can('update', $riwayat) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'trail_id' => ['nullable', 'integer', Rule::exists('trails', 'id')->where('is_published', true)->whereNull('archived_at')],
            'nama_jalur' => ['required_without:trail_id', 'nullable', 'string', 'min:3', 'max:120'],
            'tanggal' => ['required', 'date_format:Y-m-d', 'after_or_equal:1950-01-01', 'before_or_equal:'.Timezone::latestDateInIndonesia()],
            'jenis' => ['required', Rule::enum(TripType::class)],
            'jarak_km' => ['nullable', 'numeric', 'min:0.1', 'max:200'],
            'naik_m' => ['nullable', 'integer', 'min:0', 'max:9000'],
            'waktu_menit' => ['nullable', 'integer', 'min:1', 'max:7200'],
            'tertinggi_m' => ['nullable', 'integer', 'min:0', 'max:6000'],
            'catatan' => ['nullable', 'string', 'max:'.self::CATATAN_MAKS],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'trail_id' => 'Jalur itu tidak ada di katalog. Tulis namanya sendiri bila jalurnya belum terdaftar.',
            'nama_jalur.required_without' => 'Pilih jalur dari katalog atau tulis nama jalurnya.',
            'nama_jalur' => 'Nama jalur 3 sampai 120 karakter.',
            'tanggal.required' => 'Isi tanggal pendakian.',
            'tanggal.date_format' => 'Tanggal pendakian tidak terbaca. Pilih tanggal dari kalender.',
            'tanggal.after_or_equal' => 'Tanggal pendakian paling awal 1 Januari 1950.',
            'tanggal.before_or_equal' => 'Tanggal pendakian tidak boleh di masa depan.',
            'jenis' => 'Pilih jenis pendakian: tektok, camping, atau multi-hari.',
            'jarak_km' => 'Jarak diisi dalam kilometer, 0,1 sampai 200.',
            'naik_m' => 'Tanjakan diisi dalam meter, 0 sampai 9.000.',
            'waktu_menit' => 'Waktu bergerak diisi dalam menit, 1 sampai 7.200.',
            'tertinggi_m' => 'Titik tertinggi diisi dalam meter, 0 sampai 6.000.',
            'catatan' => 'Catatan paling banyak '.number_format(self::CATATAN_MAKS, 0, ',', '.').' karakter.',
        ];
    }

    /**
     * Kolom riwayat dari isian yang sudah divalidasi. Tanggal disimpan sebagai pukul 12.00 WIB
     * dalam UTC, supaya terbaca tanggal yang sama di WIB, WITA, maupun WIT. Nama jalur hanya
     * dipakai bila jalurnya tidak dari katalog.
     *
     * @return array<string, mixed>
     */
    public function kolom(): array
    {
        $data = $this->validated();
        $jarak = $data['jarak_km'] ?? null;
        $jalur = isset($data['trail_id']) ? Trail::query()->find($data['trail_id']) : null;

        return [
            'trail_id' => $jalur?->id,
            'trail_name' => $jalur === null ? trim((string) $data['nama_jalur']) : null,
            'trip_type' => $data['jenis'],
            'completed_at' => Carbon::parse($data['tanggal'].' 12:00:00', Timezone::DEFAULT)->utc(),
            'recorded_distance_m' => $jarak === null ? null : (int) round((float) $jarak * 1000),
            'recorded_gain_m' => $data['naik_m'] ?? null,
            'recorded_moving_minutes' => $data['waktu_menit'] ?? null,
            'recorded_highest_m' => $data['tertinggi_m'] ?? null,
            'personal_notes' => filled($data['catatan'] ?? null) ? trim((string) $data['catatan']) : null,
        ];
    }

    /**
     * Isian kosong dari formulir React datang sebagai string kosong; aturan nullable dan angka
     * membacanya sebagai nilai, bukan ketiadaan. Koma desimal Indonesia diterima untuk jarak.
     */
    protected function prepareForValidation(): void
    {
        $kosong = fn (mixed $v): mixed => is_string($v) && trim($v) === '' ? null : $v;

        $this->merge([
            'trail_id' => $kosong($this->input('trail_id')),
            'nama_jalur' => $kosong($this->input('nama_jalur')),
            'jarak_km' => is_string($jarak = $kosong($this->input('jarak_km'))) ? str_replace(',', '.', $jarak) : $jarak,
            'naik_m' => $kosong($this->input('naik_m')),
            'waktu_menit' => $kosong($this->input('waktu_menit')),
            'tertinggi_m' => $kosong($this->input('tertinggi_m')),
            'catatan' => $kosong($this->input('catatan')),
        ]);
    }
}
```

Buat `app/Policies/HikingHistoryPolicy.php`:

```php
<?php

namespace App\Policies;

use App\Models\HikingHistory;
use App\Models\User;

/**
 * Riwayat pendakian milik satu pendaki (PRD §119, FR-25). Catatan manual dapat diperbaiki dan
 * dihapus pemiliknya; riwayat dari trip tidak lewat sini, karena angkanya diubah di ringkasan
 * pendakian ("Ubah angka") dan barisnya bergantung pada trip. Mengetahui id baris tidak sama
 * dengan memilikinya.
 */
class HikingHistoryPolicy
{
    public function update(User $user, HikingHistory $riwayat): bool
    {
        return $riwayat->user_id === $user->id && $riwayat->manual();
    }

    public function delete(User $user, HikingHistory $riwayat): bool
    {
        return $this->update($user, $riwayat);
    }
}
```

Buat `database/migrations/2026_10_08_000100_add_source_to_hiking_history_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Riwayat pendakian dari dua sumber (FR-25, spec v3 bagian 9.3; rencana induk W7 butir 3):
 * TRIP untuk pendakian yang tercatat lewat siklus trip, MANUAL untuk pendakian di luar trip yang
 * dicatat pendaki sendiri. Catatan manual tidak punya trip, dan jalurnya boleh di luar katalog
 * (trail_name), jadi trip_plan_id dan trail_id kini boleh kosong. Baris lama seluruhnya dari
 * trip, jadi nilai bawaan TRIP benar untuk mereka.
 *
 * Migrasi aditif ini menggantikan isi rintisan 2026_09_25_000919, yang sengaja tidak disunting
 * (ruling koordinator W7): berlaku sama, baik rintisan itu sudah berjalan di Supabase maupun belum.
 *
 * Di PostgreSQL dua CHECK menjaga bentuk baris di lapisan basis data: baris TRIP selalu punya
 * trip dan baris MANUAL tidak pernah punya, dan setiap baris punya jalur katalog atau nama
 * jalur. SQLite (test) mengandalkan Form Request yang sama; perilaku PostgreSQL diuji
 * tests/Spatial/HikingHistorySourceSchemaTest (R-024).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hiking_history', function (Blueprint $table) {
            $table->string('source', 10)->default('TRIP')->after('user_id');
            $table->string('trail_name', 120)->nullable()->after('trail_id');
        });

        Schema::table('hiking_history', function (Blueprint $table) {
            $table->unsignedBigInteger('trip_plan_id')->nullable()->change();
            $table->unsignedBigInteger('trail_id')->nullable()->change();
            // Progres membaca riwayat satu pendaki per tahun, urut tanggal.
            $table->index(['user_id', 'completed_at'], 'hiking_history_pengguna_tanggal');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE hiking_history ADD CONSTRAINT hiking_history_sumber_sah CHECK ((source = 'TRIP' AND trip_plan_id IS NOT NULL) OR (source = 'MANUAL' AND trip_plan_id IS NULL))");
            DB::statement('ALTER TABLE hiking_history ADD CONSTRAINT hiking_history_jalur_tercatat CHECK (trail_id IS NOT NULL OR trail_name IS NOT NULL)');
        }
    }

    /**
     * Catatan manual tidak punya trip, jadi tidak dapat hidup di skema lama: mengembalikan
     * migrasi ini menghapusnya lebih dulu.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE hiking_history DROP CONSTRAINT IF EXISTS hiking_history_sumber_sah');
            DB::statement('ALTER TABLE hiking_history DROP CONSTRAINT IF EXISTS hiking_history_jalur_tercatat');
        }

        DB::table('hiking_history')->where('source', 'MANUAL')->delete();

        Schema::table('hiking_history', function (Blueprint $table) {
            $table->dropIndex('hiking_history_pengguna_tanggal');
            $table->unsignedBigInteger('trip_plan_id')->nullable(false)->change();
            $table->unsignedBigInteger('trail_id')->nullable(false)->change();
        });

        Schema::table('hiking_history', function (Blueprint $table) {
            $table->dropColumn(['source', 'trail_name']);
        });
    }
};
```

`app/Http/Controllers/Community/HikingHistoryController.php`:

1. Ganti:

```php
use App\Services\Community\HasilPendakian;
use Illuminate\Http\Request;
```

dengan:

```php
use App\Services\Community\HasilPendakian;
use App\Support\Durasi;
use Illuminate\Http\Request;
```

2. Ganti:

```php
                    'trailId' => $h->trail_id,
                    'jalur' => $h->trail?->name,
                    'gunung' => $h->trail?->mountain?->name,
                    'tanggal' => $h->completed_at->translatedFormat('d M Y'),
```

dengan:

```php
                    'trailId' => $h->trail_id,
                    'jalur' => $h->namaJalur(),
                    'gunung' => $h->trail?->mountain?->name,
                    // Catatan manual (FR-25) tanpa trip: tidak punya ringkasan, persiapan, atau
                    // sesi; jarak dan lamanya angka yang ditulis pendaki sendiri.
                    'manual' => $h->manual(),
                    'tanggal' => $h->completed_at->translatedFormat('d M Y'),
```

3. Ganti:

```php
                    'hasil' => $h->completion_state?->label(),
                    'persiapan' => $h->preparation_completion_percent.'%',
                    // Jarak milik jalurnya, bukan rekaman langkah pendaki; yang belum
                    // dicatat disebut belum diketahui, bukan nol (§95).
                    'jarak' => $h->trail?->distance_km !== null
                        ? number_format((float) $h->trail->distance_km, 1, ',', '.').' km'
                        : 'belum diketahui',
                    'durasi' => HasilPendakian::durasiSesi($h->tripPlan?->hikingSession) ?? 'tidak tercatat',
                    'catatan' => $h->personal_notes,
```

dengan:

```php
                    'hasil' => $h->completion_state?->label(),
                    'persiapan' => $h->manual() ? null : $h->preparation_completion_percent.'%',
                    // Jarak milik jalurnya, bukan rekaman langkah pendaki; yang belum
                    // dicatat disebut belum diketahui, bukan nol (§95).
                    'jarak' => match (true) {
                        $h->manual() => $h->recorded_distance_m !== null ? number_format($h->recorded_distance_m / 1000, 1, ',', '.').' km' : 'belum diketahui',
                        $h->trail?->distance_km !== null => number_format((float) $h->trail->distance_km, 1, ',', '.').' km',
                        default => 'belum diketahui',
                    },
                    'durasi' => $h->manual()
                        ? ($h->recorded_moving_minutes !== null ? Durasi::panjang($h->recorded_moving_minutes) : 'tidak tercatat')
                        : (HasilPendakian::durasiSesi($h->tripPlan?->hikingSession) ?? 'tidak tercatat'),
                    'catatan' => $h->personal_notes,
```

`app/Models/HikingHistory.php`:

1. Ganti:

```php
use App\Enums\CompletionState;
use App\Enums\TripType;
```

dengan:

```php
use App\Enums\CompletionState;
use App\Enums\HikeRecordSource;
use App\Enums\TripType;
```

2. Ganti:

```php
#[Fillable([
    'user_id', 'trip_plan_id', 'trail_id', 'trail_condition_report_id', 'trip_type',
    'completion_state', 'preparation_completion_percent', 'personal_notes', 'completed_at',
```

dengan:

```php
#[Fillable([
    'user_id', 'source', 'trip_plan_id', 'trail_id', 'trail_name', 'trail_condition_report_id', 'trip_type',
    'completion_state', 'preparation_completion_percent', 'personal_notes', 'completed_at',
```

3. Ganti:

```php
    protected $table = 'hiking_history';

    protected function casts(): array
```

dengan:

```php
    protected $table = 'hiking_history';

    /**
     * Baris yang dibuat siklus trip tidak menyebut asalnya; bawaannya TRIP, sama dengan kolomnya.
     *
     * @var array<string, string>
     */
    protected $attributes = ['source' => 'TRIP'];

    protected function casts(): array
```

4. Ganti:

```php
        return [
            'trip_type' => TripType::class,
```

dengan:

```php
        return [
            'source' => HikeRecordSource::class,
            'trip_type' => TripType::class,
```

5. Ganti:

```php
    }
}
```

dengan:

```php
    }

    /**
     * Nama jalur untuk ditampilkan: jalur katalog bila ada, nama yang ditulis pendaki bila
     * jalurnya di luar katalog (catatan manual).
     */
    public function namaJalur(): string
    {
        return $this->trail?->name ?? (string) $this->trail_name;
    }

    public function manual(): bool
    {
        return $this->source === HikeRecordSource::MANUAL;
    }
}
```

`database/factories/HikingHistoryFactory.php`:

1. Ganti:

```php
use App\Enums\CompletionState;
use App\Enums\TripType;
```

dengan:

```php
use App\Enums\CompletionState;
use App\Enums\HikeRecordSource;
use App\Enums\TripType;
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
     * Pendakian di luar trip yang dicatat pendaki sendiri (FR-25): tanpa trip.
     */
    public function manual(): static
    {
        return $this->state(fn () => ['source' => HikeRecordSource::MANUAL->value, 'trip_plan_id' => null]);
    }
}
```

`resources/js/pages/Community/HikingHistory.tsx`:

1. Ganti:

```tsx
    id: number;
    tripId: number;
    trailId: number;
    jalur: string | null;
    gunung: string | null;
    tanggal: string;
```

dengan:

```tsx
    id: number;
    /** Null untuk catatan manual (FR-25): pendakian di luar trip tidak punya ringkasan. */
    tripId: number | null;
    /** Null bila jalurnya di luar katalog. */
    trailId: number | null;
    jalur: string;
    gunung: string | null;
    manual: boolean;
    tanggal: string;
```

2. Ganti:

```tsx
    hasil: string | null;
    persiapan: string;
    jarak: string;
```

dengan:

```tsx
    hasil: string | null;
    persiapan: string | null;
    jarak: string;
```

3. Ganti:

```tsx
                                            <h2 className="text-base font-semibold text-primary">
                                                <Tautan href={summary.url(e.tripId)} className="rounded-control py-1 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
                                                    {e.jalur}
                                                </Tautan>
                                            </h2>
```

dengan:

```tsx
                                            <h2 className="text-base font-semibold text-primary">
                                                {e.tripId !== null ? (
                                                    <Tautan href={summary.url(e.tripId)} className="rounded-control py-1 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
                                                        {e.jalur}
                                                    </Tautan>
                                                ) : (
                                                    e.jalur
                                                )}
                                            </h2>
```

4. Ganti:

```tsx
                                            <p className="mt-1 text-sm text-muted">
                                                Jarak jalur {e.jarak} &middot; Lama pendakian {e.durasi}
                                            </p>
                                        </div>
                                        <div className="text-right text-sm">
                                            <p className="text-muted">Persiapan saat itu</p>
                                            <p className="font-semibold text-primary">{e.persiapan}</p>
                                        </div>
                                    </div>
```

dengan:

```tsx
                                            <p className="mt-1 text-sm text-muted">
                                                {e.manual ? `Dicatat manual · Jarak ${e.jarak} · Lama pendakian ${e.durasi}` : `Jarak jalur ${e.jarak} · Lama pendakian ${e.durasi}`}
                                            </p>
                                        </div>
                                        {e.persiapan !== null ? (
                                            <div className="text-right text-sm">
                                                <p className="text-muted">Persiapan saat itu</p>
                                                <p className="font-semibold text-primary">{e.persiapan}</p>
                                            </div>
                                        ) : null}
                                    </div>
```

5. Ganti:

```tsx
                                    <div className="mt-2 flex flex-wrap gap-x-4 text-sm">
                                        <Tautan href={tripShow.url(e.tripId)} className={tautanAksi}>Lihat trip</Tautan>
                                        {e.laporan ? (
                                            <span className="inline-flex min-h-11 items-center text-secondary">Laporan kondisi: {e.laporan.status}</span>
                                        ) : (
                                            <Tautan href={reportsCreate.url({ query: { trail: e.trailId, trip: e.tripId } })} className={tautanAksi}>
                                                Kirim laporan kondisi
                                            </Tautan>
                                        )}
                                    </div>
```

dengan:

```tsx
                                    <div className="mt-2 flex flex-wrap gap-x-4 text-sm">
                                        {e.tripId !== null ? (
                                            <Tautan href={tripShow.url(e.tripId)} className={tautanAksi}>
                                                Lihat trip
                                            </Tautan>
                                        ) : null}
                                        {e.laporan ? (
                                            <span className="inline-flex min-h-11 items-center text-secondary">Laporan kondisi: {e.laporan.status}</span>
                                        ) : e.trailId !== null ? (
                                            <Tautan href={reportsCreate.url({ query: { trail: e.trailId, ...(e.tripId !== null ? { trip: e.tripId } : {}) } })} className={tautanAksi}>
                                                Kirim laporan kondisi
                                            </Tautan>
                                        ) : null}
                                    </div>
```

`routes/web.php`:

1. Ganti:

```php
use App\Http\Controllers\Community\HikingHistoryController;
use App\Http\Controllers\Community\TrailContributionController;
```

dengan:

```php
use App\Http\Controllers\Community\HikingHistoryController;
use App\Http\Controllers\Community\ManualHikeController;
use App\Http\Controllers\Community\TrailContributionController;
```

2. Ganti:

```php
    Route::get('progres', HikerProgressController::class)->name('progress');
    Route::get('history', HikingHistoryController::class)->name('history');
```

dengan:

```php
    Route::get('progres', HikerProgressController::class)->name('progress');
    // Catat pendakian di luar trip (FR-25, W7): pemilik dari sesi, catatan dari rute dan Policy.
    Route::post('progres/catatan', [ManualHikeController::class, 'store'])->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,catatan-pendakian')->name('progress.records.store');
    Route::put('progres/catatan/{riwayat}', [ManualHikeController::class, 'update'])->whereNumber('riwayat')->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,catatan-pendakian')->name('progress.records.update');
    Route::delete('progres/catatan/{riwayat}', [ManualHikeController::class, 'destroy'])->whereNumber('riwayat')->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,catatan-pendakian')->name('progress.records.destroy');
    Route::get('history', HikingHistoryController::class)->name('history');
```

- [ ] **Step 4: Build dan tipe.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `npx tsc --noEmit`
Expected: `✓ built`; keluaran Wayfinder memuat `progress.records.store`, `update`, dan `destroy`; tsc tanpa keluaran galat.

- [ ] **Step 5: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Enums/HikeRecordSource.php app/Http/Controllers/Community/ManualHikeController.php app/Http/Requests/Community/SaveManualHikeRequest.php app/Policies/HikingHistoryPolicy.php app/Http/Controllers/Community/HikingHistoryController.php app/Models/HikingHistory.php database/factories/HikingHistoryFactory.php database/migrations/2026_10_08_000100_add_source_to_hiking_history_table.php routes/web.php tests/Feature/ManualHikeRecordTest.php tests/Feature/HikingHistoryPageTest.php tests/Spatial/HikingHistorySourceSchemaTest.php`, lalu `php artisan test --compact --filter='ManualHikeRecord|HikingHistoryPage|HikingHistorySourceSchema'`
Expected: PASS. Pada simulasi rencana: 21 test, 17 lulus, 4 dilewati.

- [ ] **Step 6: Suite, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 2.071 test, 2.069 lulus, 2 dilewati (sebelum tugas: 2.058).

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `php artisan test --compact --testsuite=Pratinjau`
Expected: PASS. Pada simulasi rencana: 29 test lulus.

Test Spatial melewati dirinya tanpa `SPATIAL_TEST_DSN`; ia berjalan di PostGIS CI (Tugas 13 Step 2).

- [ ] **Step 7: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 2: Hub Progres

Spec v3 bagian 6.14 (prototype 13), induk W7 butir 1; ruling W7-R4, W7-R5, W7-R6, W7-R15, dan W7-R17. Satu kelas
halaman, `App\Services\Community\HubProgres`, menyusun Progres untuk satu tahun yang dapat dipilih: ringkasan tahun
(jumlah pendakian, tanjakan, jarak, dengan "Belum ada" untuk yang tidak diketahui), grafik per pendakian sebagai baris
mendatar dengan pilihan Tanjakan atau Jarak dan tabel padanan bagi pembaca layar, riwayat dua sumber dengan lencana
batal dan alasannya, tombol "Catat pendakian di luar trip" yang membuka formulir di lembar bawah (catat, ubah, dan hapus
dengan konfirmasi), dampak laporan kondisi, dan peta gunung yang sudah didaki. Angka satu pendakian datang dari satu
aturan (`HubProgres::angka`). Pencarian jalur katalog untuk formulir memakai muat ulang parsial `pilihanJalur`
(`CariJalur`, paling banyak 8 jalur terbit). `HikerProgressService` diciutkan menjadi dampak laporan dan peta, dan
`GrafikTanjakan.tsx` dihapus. Tidak ada perbandingan dengan pendaki lain, rentetan, atau persen (R-015, R-005).

**Files:**
- Create: `app/Services/Community/HubProgres.php`, `app/Services/Trails/CariJalur.php`, `resources/js/components/progres/types.ts`, `resources/js/components/progres/RingkasanTahun.tsx`, `resources/js/components/progres/GrafikPendakian.tsx`, `resources/js/components/progres/RiwayatProgres.tsx`, `resources/js/components/progres/FormulirCatatan.tsx`, `resources/js/components/trails/PemilihJalur.tsx`
- Modify: `app/Http/Controllers/Community/HikerProgressController.php` (ditulis ulang utuh), `app/Services/HikerProgressService.php` (ditulis ulang utuh), `resources/js/pages/Community/HikerProgress.tsx` (ditulis ulang utuh), `resources/js/components/community/types.ts`
- Delete: `resources/js/components/community/GrafikTanjakan.tsx`
- Test: `tests/Feature/HikerProgressTest.php` dan `tests/Feature/ProgressHierarchyTest.php` (ditulis ulang utuh; niat lama tetap: hanya riwayat sendiri, urutan bagian, tanpa kata yang menghukum), `tests/Feature/ReporterImpactTest.php`, `tests/Pratinjau/HalamanPratinjauTest.php`

**Interfaces:**
- Consumes: Tugas 1 (`HikeRecordSource`, `HikingHistory::namaJalur()`, `manual()`, rute `progress.records.*`, flash `kabar-progres`); `HikerProgressService::dampakLaporan(User): array` dan `petaGunung(User): array` (W2); `CancellationReason` (W6); `TrailFigures::ringkas(Trail)`; `TripType::labelPendek()`; primitif `PageHeader`, `LembarBawah`, `Dialog`, `KontrolSegmen`, `Lencana`, `Button`, `Field` (`Label`, `TextInput`, `Textarea`, `InputError`), `EmptyState`, `Tautan`, `Icon`, dan `PetaGaris`.
- Produces:
  - `HubProgres::untuk(User $pengguna, ?int $diminta, ?array $kabar = null): array` dengan kunci `kosong`, `tahun` (`dipilih`, `semua`), `ringkasan`, `grafik`, `target`, `pencapaian`, `riwayat`, `riwayatTerpotong`, `laporan` (`terbit`, `terimaKasih`), `gunung`, `keteranganPeta`, `gunungTanpaKoordinat`, `formulirCatatan` (`simpan`, `tanggalMaks`, `jenis`), `kabar`; `HubProgres::tahunKini(): int`; `HubProgres::angka(HikingHistory): array{naik: int|null, jarakM: int|null}` (`target` dan `pencapaian` diisi Tugas 3 dan 4; sampai itu `null` dan larik kosong);
  - `CariJalur::BATAS` (8), `CariJalur::untuk(string $kata): Collection`, `CariJalur::baris(Trail): array{id, nama, gunung, ringkas}`; prop opsional `pilihanJalur` di Progres (`?cari=`);
  - `HikerProgressService` tinggal `dampakLaporan()` dan `petaGunung()`;
  - TypeScript di `progres/types.ts`: `KabarProgres`, `RingkasanTahun`, `NilaiBatang`, `BatangPendakian`, `GrafikPendakianData`, `IsianCatatan`, `UbahCatatan`, `ButirRiwayat`, `FormulirCatatanData`; komponen `RingkasanTahun`, `GrafikPendakian`, `RiwayatProgres`, `FormulirCatatan`; `JalurKatalog`, `useCariJalur()`, dan `PemilihJalur` di `trails/PemilihJalur.tsx` (dipakai lagi di Tugas 7).

- [ ] **Step 1: Tulis test yang gagal.** `HikerProgressTest` dan `ProgressHierarchyTest` ditulis ulang utuh karena
susunan halamannya berganti; setiap niat lama tetap diuji dengan nama yang menyebutnya. `ReporterImpactTest` mengikuti
props baru.

Tulis ulang `tests/Feature/HikerProgressTest.php` utuh:

```php
<?php

namespace Tests\Feature;

use App\Enums\CancellationReason;
use App\Enums\CompletionState;
use App\Enums\TripStatus;
use App\Models\HikingHistory;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Progres pribadi pendaki (spec v3 bagian 6.14; rencana induk W7 butir 1).
 *
 * Dalam Self-Determination Theory inilah competence: melihat kemajuan diri sendiri. Diperbarui
 * W7: angkanya kini ringkasan satu tahun, grafik per pendakian, dan riwayat dari HubProgres,
 * dibaca lewat props halaman; niat setiap test lama tetap (pendakian yang dibatalkan terhitung
 * sebagai pendakian, yang tidak diketahui tidak menjadi nol, angka milik pemiliknya, peta hanya
 * gunung berkoordinat). Angkanya dihitung tangan di test ini, bukan disalin dari keluaran kode.
 */
class HikerProgressTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-08 09:00:00', 'Asia/Jakarta'));
    }

    private function riwayat(User $user, Trail $trail, string $tanggal = '2026-06-10', CompletionState $hasil = CompletionState::COMPLETED, ?CancellationReason $alasan = null): HikingHistory
    {
        $trip = TripPlan::factory()->create([
            'user_id' => $user->id,
            'trail_id' => $trail->id,
            'planned_date' => $tanggal,
            'status' => TripStatus::COMPLETED->value,
            'cancellation_reason' => $alasan?->value,
        ]);

        return HikingHistory::create([
            'user_id' => $user->id,
            'trip_plan_id' => $trip->id,
            'trail_id' => $trail->id,
            'trip_type' => $trip->trip_type->value,
            'completion_state' => $hasil->value,
            'preparation_completion_percent' => 90,
            'completed_at' => Carbon::parse($tanggal.' 16:00:00', 'Asia/Jakarta')->utc(),
        ]);
    }

    private function batal(User $user, Trail $trail, string $tanggal, ?CancellationReason $alasan = null): TripPlan
    {
        return TripPlan::factory()->create([
            'user_id' => $user->id,
            'trail_id' => $trail->id,
            'planned_date' => $tanggal,
            'status' => TripStatus::CANCELLED->value,
            'cancellation_reason' => $alasan?->value,
        ]);
    }

    private function jalur(?int $naik, ?float $km = 4.0, ?Mountain $gunung = null, string $nama = 'Jalur Uji'): Trail
    {
        return Trail::factory()->easy()->for($gunung ?? Mountain::factory()->create())->create(['name' => $nama, 'elevation_gain_m' => $naik, 'distance_km' => $km]);
    }

    /**
     * @return array<string, mixed>
     */
    private function props(User $user, array $query = []): array
    {
        return $this->actingAs($user)->get(route('progress', $query))->assertOk()->viewData('page')['props'];
    }

    public function test_the_year_summary_counts_hikes_and_sums_their_known_numbers(): void
    {
        $user = User::factory()->create();
        $this->riwayat($user, $this->jalur(900, 4.0));
        $this->riwayat($user, $this->jalur(1300, 6.5), '2026-08-02');

        $ringkasan = $this->props($user)['ringkasan'];

        $this->assertSame('Pendakian 2026', $ringkasan['judul']);
        $this->assertSame('2', $ringkasan['pendakian']);
        $this->assertSame('2 selesai', $ringkasan['rincian']);
        $this->assertSame('2.200', $ringkasan['tanjakan']);
        $this->assertSame('21,0', $ringkasan['jarak'], 'Jarak data jalur pulang-pergi: 2 x 4,0 + 2 x 6,5 km.');
        $this->assertNull($ringkasan['tanjakanCatatan']);
    }

    /**
     * Membatalkan karena cuaca adalah keputusan yang benar: tetap terhitung sebagai pendakian,
     * sebelum berangkat maupun di jalur, tetapi tanjakannya tidak ikut dijumlahkan.
     */
    public function test_a_cancelled_hike_counts_as_a_hike_but_not_as_a_climb(): void
    {
        $user = User::factory()->create();
        $this->riwayat($user, $this->jalur(900));
        $this->riwayat($user, $this->jalur(1500, nama: 'Jalur Berbalik'), '2026-07-04', CompletionState::ABANDONED, CancellationReason::PHYSICAL_CONDITION);
        $this->batal($user, $this->jalur(1700, nama: 'Jalur Batal'), '2026-09-13', CancellationReason::WEATHER);

        $props = $this->props($user);

        $this->assertSame('3', $props['ringkasan']['pendakian']);
        $this->assertSame('1 selesai, 2 batal', $props['ringkasan']['rincian']);
        $this->assertSame('900', $props['ringkasan']['tanjakan']);
        $this->assertSame(['Dibatalkan · Cuaca', 'Dibatalkan di jalur · Kondisi tubuh', 'Selesai'], array_column($props['riwayat'], 'lencana'));
        $this->assertSame('Keputusan yang baik tetap tercatat sebagai pendakian.', $props['riwayat'][0]['kalimatBatal']);
        $this->assertSame('batal sebelum pendakian dimulai', $props['riwayat'][0]['baris']);
    }

    /**
     * PRD §95: angka yang belum diketahui tidak menaikkan total dan tidak dihitung nol; jumlah
     * pendakian tanpa angka disebut supaya total tidak terbaca lengkap.
     */
    public function test_a_hike_without_numbers_is_admitted_not_counted_as_zero(): void
    {
        $user = User::factory()->create();
        $this->riwayat($user, $this->jalur(900, 4.0));
        $this->riwayat($user, $this->jalur(null, null), '2026-07-01');

        $ringkasan = $this->props($user)['ringkasan'];

        $this->assertSame('900', $ringkasan['tanjakan']);
        $this->assertSame('1 pendakian tanpa angka tanjakan', $ringkasan['tanjakanCatatan']);
        $this->assertSame('8,0', $ringkasan['jarak']);
        $this->assertSame('1 pendakian tanpa angka jarak', $ringkasan['jarakCatatan']);
    }

    /**
     * Angka yang ditulis pendaki sendiri ("Ubah angka" atau catatan manual) menggantikan data
     * jalur (ruling W7-R4).
     */
    public function test_numbers_the_hiker_wrote_replace_trail_data(): void
    {
        $user = User::factory()->create();
        $this->riwayat($user, $this->jalur(1650, 9.7))->update(['recorded_gain_m' => 1200, 'recorded_distance_m' => 18000, 'numbers_edited_at' => now()]);
        HikingHistory::factory()->manual()->for($user)->create(['trail_id' => null, 'trail_name' => 'Lawu via Cemoro Sewu', 'recorded_gain_m' => 1300, 'recorded_distance_m' => null, 'completed_at' => Carbon::parse('2026-05-23 05:00:00')]);

        $props = $this->props($user);

        $this->assertSame('2.500', $props['ringkasan']['tanjakan']);
        $this->assertSame('18,0', $props['ringkasan']['jarak']);
        $this->assertSame('1 pendakian tanpa angka jarak', $props['ringkasan']['jarakCatatan']);
        $this->assertSame(['teks' => 'Dicatat manual', 'nada' => 'redup'], $props['riwayat'][1]['catatan']);
    }

    /**
     * Progres dan ringkasan satu pendakian memakai aturan angka yang sama untuk pendakian tanpa
     * rekaman jejak (ruling W6-R13 dan W7-R4): jarak pulang-pergi dan tanjakan jalur.
     */
    public function test_progress_and_the_hike_summary_agree_on_a_hike_without_a_track(): void
    {
        $user = User::factory()->create();
        $riwayat = $this->riwayat($user, $this->jalur(1650, 9.7, nama: 'Gede via Cibodas'));

        $ringkasanPendakian = $this->actingAs($user)->get(route('history.summary', $riwayat->trip_plan_id))->assertOk()->viewData('page')['props']['angka'];
        $baris = $this->props($user)['riwayat'][0]['baris'];

        $this->assertSame('↑1.650 m', $ringkasanPendakian['naik']);
        $this->assertSame('19,4 km', $ringkasanPendakian['jarak']);
        $this->assertStringContainsString('↑1.650 m · 19,4 km', $baris);
    }

    public function test_the_numbers_belong_to_their_owner_alone(): void
    {
        $saya = User::factory()->create();
        $orangLain = User::factory()->create();

        $this->riwayat($saya, $this->jalur(900));
        $this->riwayat($orangLain, $this->jalur(2000));
        HikingHistory::factory()->manual()->for($orangLain)->create(['trail_id' => null, 'trail_name' => 'Rinjani', 'recorded_gain_m' => 2600, 'completed_at' => now()->subMonth()]);

        $props = $this->props($saya);

        $this->assertSame('1', $props['ringkasan']['pendakian']);
        $this->assertSame('900', $props['ringkasan']['tanjakan']);
        $this->assertCount(1, $props['riwayat']);
    }

    public function test_a_hiker_who_has_never_hiked_sees_the_first_time_state_rather_than_zeroes(): void
    {
        $props = $this->props(User::factory()->create());

        $this->assertTrue($props['kosong']);
        $this->assertNull($props['ringkasan'], 'Belum pernah mendaki bukan berarti tanjakannya nol.');
        $this->assertNull($props['grafik']);
        $this->assertSame([], $props['riwayat']);
        $this->assertSame(['dipilih' => 2026, 'semua' => [2026]], $props['tahun']);
    }

    /**
     * Tahun dibaca dari catatan pendaki sendiri; tahun di luar rentangnya kembali ke tahun ini.
     */
    public function test_the_year_is_chosen_from_the_hikers_own_records(): void
    {
        $user = User::factory()->create();
        $this->riwayat($user, $this->jalur(900, nama: 'Jalur 2025'), '2025-03-01');
        $this->riwayat($user, $this->jalur(700, nama: 'Jalur 2026'), '2026-03-01');

        $this->assertSame(['dipilih' => 2026, 'semua' => [2026, 2025]], $this->props($user)['tahun']);
        $this->assertSame(['Jalur 2025'], array_column($this->props($user, ['tahun' => 2025])['riwayat'], 'nama'));
        $this->assertSame(2026, $this->props($user, ['tahun' => 1990])['tahun']['dipilih']);
    }

    /**
     * Grafik dari pendakian paling awal; yang dibatalkan dan yang tanpa angka menyebut dirinya
     * dengan kata, dan keterangan kalimatnya menyebut nilai pertama dan terbaru tanpa menilai.
     */
    public function test_the_chart_is_chronological_and_names_what_is_unknown(): void
    {
        $user = User::factory()->create();
        $this->riwayat($user, $this->jalur(900, nama: 'Jalur A'), '2026-01-15');
        $this->riwayat($user, $this->jalur(null, nama: 'Jalur B'), '2026-03-15');
        $this->batal($user, $this->jalur(1200, nama: 'Jalur C'), '2026-04-12', CancellationReason::WEATHER);
        $this->riwayat($user, $this->jalur(1300, nama: 'Jalur D'), '2026-06-20');

        $grafik = $this->props($user)['grafik'];

        $this->assertSame(['Jalur A', 'Jalur B', 'Jalur C', 'Jalur D'], array_column($grafik['batang'], 'nama'));
        $this->assertSame(['900 m', 'belum ada', 'batal', '1.300 m'], array_column(array_column($grafik['batang'], 'tanjakan'), 'teks'));
        $this->assertSame([900, null, null, 1300], array_column(array_column($grafik['batang'], 'tanjakan'), 'nilai'));
        $this->assertSame([false, false, false, true], array_column($grafik['batang'], 'terbaru'));
        $this->assertSame(
            'Tanjakan naik dari 900 m (Jalur A, Jan) ke 1.300 m (Jalur D, Jun). Jalur C di April dibatalkan karena cuaca dan tetap tercatat. 1 pendakian belum punya angka tanjakan.',
            $grafik['keterangan']['tanjakan'],
        );
    }

    public function test_the_chart_keeps_only_the_latest_twelve_hikes(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 14) as $i) {
            $this->riwayat($user, $this->jalur($i * 100, nama: 'Jalur '.$i), Carbon::parse('2026-01-01')->addDays($i * 7)->toDateString());
        }

        $grafik = $this->props($user)['grafik'];

        $this->assertCount(12, $grafik['batang']);
        $this->assertSame('Jalur 3', $grafik['batang'][0]['nama'], 'Yang paling lama terpotong, urutannya tetap dari yang awal.');
        $this->assertSame('12 pendakian terakhir di 2026 dari 14; seluruhnya ada di riwayat di bawah.', $grafik['catatan']);
    }

    /**
     * Pencarian jalur katalog untuk catatan manual hanya dikirim lewat muat ulang parsial: hanya
     * jalur terbit (R-022), paling banyak delapan, tidak peka huruf besar; kata di bawah dua
     * huruf tidak mencari apa pun.
     */
    public function test_the_catalog_search_for_a_manual_record_offers_only_published_trails(): void
    {
        $user = User::factory()->create();
        $gede = Mountain::factory()->create(['name' => 'Gunung Gede']);
        Trail::factory()->published()->easy()->for($gede)->create(['name' => 'Gede via Cibodas']);
        Trail::factory()->easy()->unpublished()->for($gede)->create(['name' => 'Gede via Selabintana']);

        foreach (range(1, 9) as $i) {
            Trail::factory()->published()->easy()->create(['name' => 'Jalur Gede Lain '.$i]);
        }

        $this->actingAs($user)->get(route('progress', ['cari' => 'GEDE']))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->missing('pilihanJalur')
            ->reloadOnly('pilihanJalur', fn (AssertableInertia $muatUlang) => $muatUlang
                ->has('pilihanJalur', 8)
                ->where('pilihanJalur.0.nama', 'Gede via Cibodas')
                ->where('pilihanJalur.0.gunung', 'Gunung Gede')
                ->where('pilihanJalur.1.nama', 'Jalur Gede Lain 1')));

        $this->actingAs($user)->get(route('progress', ['cari' => 'g']))->assertInertia(fn (AssertableInertia $page) => $page
            ->reloadOnly('pilihanJalur', fn (AssertableInertia $muatUlang) => $muatUlang->where('pilihanJalur', [])));
    }

    /**
     * Gunung tanpa koordinat tidak ditempatkan di tengah laut demi melengkapi peta, dan
     * pendakian yang berbalik tidak menaruh gunungnya di peta.
     */
    public function test_the_map_draws_only_mountains_with_coordinates_from_completed_hikes(): void
    {
        $user = User::factory()->create();
        $berkoordinat = Mountain::factory()->create(['name' => 'Gunung Terpetakan', 'latitude' => -7.45, 'longitude' => 110.44]);
        $tanpa = Mountain::factory()->create(['name' => 'Gunung Tanpa Titik', 'latitude' => null, 'longitude' => null]);
        $berbalik = Mountain::factory()->create(['name' => 'Gunung Berbalik', 'latitude' => -7.2, 'longitude' => 110.1]);

        $this->riwayat($user, $this->jalur(900, gunung: $berkoordinat));
        $this->riwayat($user, $this->jalur(900, gunung: $tanpa));
        $this->riwayat($user, $this->jalur(900, gunung: $berbalik), hasil: CompletionState::ABANDONED);

        $props = $this->props($user);

        $this->assertSame(['Gunung Terpetakan'], array_column($props['gunung'], 'label'));
        $this->assertSame('1 gunung lainnya belum berkoordinat, jadi belum dapat digambar.', $props['gunungTanpaKoordinat']);
    }
}
```

Tulis ulang `tests/Feature/ProgressHierarchyTest.php` utuh:

```php
<?php

namespace Tests\Feature;

use App\Enums\CompletionState;
use App\Models\HikingHistory;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Hierarki halaman Progres (spec v3 bagian 6.14, prototype 13).
 *
 * Diperbarui W7: keputusan lama menaruh peta gunung paling depan sebagai muatan emosional
 * halaman. Prototype pemilik dan spec v3 menjawab "Bagaimana perkembangan saya?" lebih dulu
 * dengan ringkasan tahun, grafik per pendakian, dan riwayat; peta tetap ada sebagai bagian
 * sesudah riwayat (ruling W7-R6), jadi tidak ada yang dibuang. Niatnya tetap: urutan dokumen
 * adalah urutan yang dibaca mata dan pembaca layar, angka berbagi satu kelompok, grafik hanya
 * membandingkan pendaki dengan dirinya sendiri (R-015), dan satu pendakian mendapat kalimat
 * jujur alih-alih grafik.
 */
class ProgressHierarchyTest extends TestCase
{
    use RefreshDatabase;
    use SumberReact;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-08 09:00:00', 'Asia/Jakarta'));
    }

    private function riwayat(User $user, string $nama, int $naik, string $tanggal): void
    {
        $trail = Trail::factory()->easy()->create(['name' => $nama, 'elevation_gain_m' => $naik]);
        $trip = TripPlan::factory()->create(['user_id' => $user->id, 'trail_id' => $trail->id, 'planned_date' => $tanggal]);

        HikingHistory::create([
            'user_id' => $user->id,
            'trip_plan_id' => $trip->id,
            'trail_id' => $trail->id,
            'trip_type' => $trip->trip_type->value,
            'completion_state' => CompletionState::COMPLETED->value,
            'preparation_completion_percent' => 90,
            'completed_at' => Carbon::parse($tanggal.' 16:00:00', 'Asia/Jakarta')->utc(),
        ]);
    }

    private function halaman(): string
    {
        return $this->tanpaKomentarJs(File::get(resource_path('js/pages/Community/HikerProgress.tsx')));
    }

    public function test_the_year_summary_leads_and_the_map_follows_the_history(): void
    {
        $sumber = $this->halaman();
        $urutan = array_map(fn (string $tag) => strpos($sumber, $tag), ['<RingkasanTahun', '<GrafikPendakian', '<RiwayatProgres', 'id="progres-laporan"', '<PetaGaris']);

        $this->assertNotContains(false, $urutan, 'Setiap bagian Progres harus ada.');
        $this->assertSame($urutan, array_values(collect($urutan)->sort()->all()), 'Ringkasan tahun, grafik, riwayat, laporan, lalu peta.');
    }

    /**
     * Tiga petak setara tetap satu daftar, bukan tiga klaim yang bersaing.
     */
    public function test_the_year_figures_are_one_group(): void
    {
        $ringkasan = File::get(resource_path('js/components/progres/RingkasanTahun.tsx'));

        $this->assertMatchesRegularExpression('/<dl className="[^"]*grid[^"]*">.*<dt.*<dd.*<\/dl>/s', $ringkasan);
    }

    /**
     * R-015: grafiknya membandingkan pendaki dengan pendakiannya sendiri, dari yang paling awal.
     * Pendakian orang lain tidak ikut, jadi deretnya tepat dua butir.
     */
    public function test_the_chart_compares_the_hiker_with_their_own_earlier_hikes(): void
    {
        $user = User::factory()->create();
        $this->riwayat($user, 'Jalur Kedua', 1500, '2026-08-20');
        $this->riwayat($user, 'Jalur Pertama', 900, '2026-02-14');
        $this->riwayat(User::factory()->create(), 'Jalur Orang Lain', 3000, '2026-05-01');

        $this->actingAs($user)->get(route('progress'))->assertInertia(fn (AssertableInertia $page) => $page
            ->has('grafik.batang', 2)
            ->where('grafik.batang.0.nama', 'Jalur Pertama')
            ->where('grafik.batang.0.tanjakan', ['nilai' => 900, 'teks' => '900 m'])
            ->where('grafik.batang.1.nama', 'Jalur Kedua')
            ->where('grafik.batang.1.tanjakan.teks', '1.500 m'));
    }

    /**
     * Satu pendakian belum dapat dibandingkan dengan apa pun: kalimat jujur, bukan grafik satu
     * batang.
     */
    public function test_a_single_hike_gets_an_honest_note_instead_of_a_chart(): void
    {
        $user = User::factory()->create();
        $this->riwayat($user, 'Satu-satunya', 900, '2026-03-01');

        $this->actingAs($user)->get(route('progress'))->assertInertia(fn (AssertableInertia $page) => $page->where('grafik', null)->has('riwayat', 1));

        $this->assertStringContainsString('Grafik per pendakian muncul setelah ada dua pendakian', $this->halaman());
    }

    /**
     * Grafik dengan nilai tertulis: batang disembunyikan dari pembaca layar, tabel membawa angka
     * kedua ukuran, dan keterangannya kalimat. Tidak ada informasi yang hanya dibawa warna atau
     * garis putus-putus.
     */
    public function test_the_chart_writes_its_values_instead_of_only_drawing_them(): void
    {
        $grafik = $this->tanpaKomentarJs(File::get(resource_path('js/components/progres/GrafikPendakian.tsx')));

        $this->assertStringContainsString('<ol aria-hidden="true"', $grafik);
        // Tabelnya di dalam pembungkus sr-only: tabel ber-sr-only melebarkan halaman sempit.
        $this->assertStringContainsString('<div className="sr-only">', $grafik);
        $this->assertStringContainsString('<table>', $grafik);
        $this->assertStringContainsString('<td>{b.tanjakan.teks}</td>', $grafik);
        $this->assertStringContainsString('<td>{b.jarak.teks}</td>', $grafik);
        $this->assertStringContainsString('{angka.teks}', $grafik);
        $this->assertStringContainsString('<figcaption', $grafik);
    }

    /**
     * Tanpa papan peringkat, rentetan, atau teguran (R-015, induk W7 butir 2): kata-kata itu tidak
     * pernah muncul di halaman maupun komponennya.
     */
    public function test_no_ranking_streak_or_reprimand_words_reach_the_page(): void
    {
        $sumber = $this->halaman();

        foreach (File::files(resource_path('js/components/progres')) as $berkas) {
            $sumber .= $this->tanpaKomentarJs($berkas->getContents());
        }

        foreach (['streak', 'rentetan', 'beruntun', 'peringkat Anda', 'juara', 'kalah', 'tertinggal', 'gagal mencapai', 'ayo'] as $kata) {
            $this->assertDoesNotMatchRegularExpression('/'.preg_quote($kata, '/').'/iu', $sumber, "Kata \"{$kata}\" tidak boleh ada di Progres.");
        }
    }
}
```

`tests/Feature/ReporterImpactTest.php`:

1. Ganti:

```php
    }

    private function progres(User $user): array
    {
        return app(HikerProgressService::class)->forUser($user);
    }
```

dengan:

```php
    }

    /**
     * Diperbarui W7: dampak laporan kini satu metode tersendiri (HikerProgressService), karena
     * ringkasan tahun milik HubProgres; kunci dan niatnya sama.
     *
     * @return array{laporan_terbit: int, terima_kasih: int}
     */
    private function progres(User $user): array
    {
        return app(HikerProgressService::class)->dampakLaporan($user);
    }
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='HikerProgress|ProgressHierarchy|ReporterImpact|EmptyStateCoverage|KeyboardAndScreenReader|TapTarget|NoDeadViewData|PageQueryBudget'`
Expected: FAIL. Pada simulasi rencana: 53 test, 31 lulus, 4 gagal, 18 error. Yang merah: sebelas test `HikerProgressTest` (props `ringkasan`, `grafik`, `riwayat`, dan `pilihanJalur` belum ada), keenam test `ProgressHierarchyTest` (urutan bagian baru dan berkas `components/progres` belum ada), dan lima test `ReporterImpactTest` (`dampakLaporan()` masih privat).

- [ ] **Step 3: Tulis implementasinya.** Kelas dibuat dengan
`DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:class --no-interaction Services/Community/HubProgres`
dan `... make:class --no-interaction Services/Trails/CariJalur`, lalu isinya diganti persis. Berkas TypeScript dibuat
langsung.

Buat `app/Services/Community/HubProgres.php`:

```php
<?php

namespace App\Services\Community;

use App\Enums\CancellationReason;
use App\Enums\CompletionState;
use App\Enums\ModerationStatus;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Models\HikingHistory;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\HikerProgressService;
use App\Support\Timezone;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Hub Progres (spec v3 bagian 6.14, prototype 13; rencana induk W7 butir 1). Satu tahun dalam
 * satu halaman: ringkasan tahun, grafik per pendakian, riwayat dengan status laporannya, dan
 * catatan pendakian di luar trip; ditambah dampak laporan dan peta gunung yang sudah didaki.
 *
 * Pembandingnya hanya riwayat pendaki ini (R-015): tidak ada pendaki lain, peringkat, atau
 * catatan kecepatan. Pendakian yang dibatalkan, sebelum berangkat maupun di jalur, tetap
 * tercatat dan terhitung sebagai pendakian (PRD §156 "tidak pernah menghukum keputusan yang
 * benar"), dengan alasan DK-50 bila disebut.
 *
 * Angka satu pendakian (ruling W7-R4): angka yang ditulis pendaki sendiri (catatan manual atau
 * "Ubah angka") bila ada, data jalur untuk pendakian yang tuntas (tanjakan jalur, jarak
 * pulang-pergi lewat jalur yang sama, aturan W6-R13), selain itu tidak diketahui (R-007).
 * Rekaman jejak tidak dijumlahkan di sini, dan halaman mengatakannya.
 *
 * Kueri tetap berapa pun jumlah riwayatnya: satu kueri per tabel untuk tahun yang dibuka dengan
 * relasi dimuat bersama, satu kueri rentang tahun per tabel, dan kueri dampak laporan serta peta.
 */
final class HubProgres
{
    /** Batas baris yang dimuat per tabel untuk satu tahun; halaman menyebutnya bila terlampaui. */
    private const BATAS_TAHUN = 500;

    /** Pendakian terakhir yang digambar di grafik. */
    private const BATAS_GRAFIK = 12;

    public function __construct(private readonly HikerProgressService $progres) {}

    public static function tahunKini(): int
    {
        return Carbon::now(Timezone::DEFAULT)->year;
    }

    /**
     * @param  array{bagian: string, teks: string}|null  $kabar  kabar tulisan terakhir di halaman ini
     * @return array<string, mixed>
     */
    public function untuk(User $pengguna, ?int $diminta, ?array $kabar = null): array
    {
        [$pertama, $terakhir] = $this->rentang($pengguna);
        $kini = self::tahunKini();
        $tahun = $diminta !== null && $pertama !== null && $diminta >= $pertama && $diminta <= max($kini, (int) $terakhir) ? $diminta : $kini;

        [$riwayat, $batal] = $this->tahun($pengguna, $tahun);
        $butir = $this->butir($riwayat, $batal);
        $dampak = $this->progres->dampakLaporan($pengguna);
        $peta = $this->progres->petaGunung($pengguna);

        return [
            'kosong' => $pertama === null && $dampak['laporan_terbit'] === 0,
            'tahun' => [
                'dipilih' => $tahun,
                'semua' => $pertama === null ? [$kini] : range(max($kini, (int) $terakhir), min($pertama, $kini)),
            ],
            'ringkasan' => $butir->isEmpty() ? null : $this->ringkasan($butir, $tahun),
            'grafik' => $butir->count() < 2 ? null : $this->grafik($butir, $tahun),
            'riwayat' => $butir->map(fn (array $b) => $b['tampil'])->values()->all(),
            'riwayatTerpotong' => $riwayat->count() > self::BATAS_TAHUN || $batal->count() > self::BATAS_TAHUN
                ? 'Halaman ini memuat '.self::BATAS_TAHUN.' catatan terbaru per jenis untuk '.$tahun.'.'
                : null,
            'laporan' => ['terbit' => $dampak['laporan_terbit'], 'terimaKasih' => $dampak['terima_kasih']],
            'gunung' => $peta['penanda'],
            'keteranganPeta' => count($peta['penanda']) === 1 ? 'Satu gunung yang sudah Anda daki.' : count($peta['penanda']).' gunung yang sudah Anda daki.',
            'gunungTanpaKoordinat' => $peta['tanpaKoordinat'] > 0 ? $peta['tanpaKoordinat'].' gunung lainnya belum berkoordinat, jadi belum dapat digambar.' : null,
            'formulirCatatan' => [
                'simpan' => route('progress.records.store'),
                'tanggalMaks' => Timezone::latestDateInIndonesia(),
                'jenis' => array_map(fn (TripType $j) => ['nilai' => $j->value, 'label' => $j->labelPendek()], TripType::cases()),
            ],
            'kabar' => $kabar,
        ];
    }

    /**
     * Tahun pertama dan terakhir yang punya catatan, dari dua agregat MIN dan MAX yang sama di
     * SQLite dan PostgreSQL (tanpa fungsi tahun khusus mesin).
     *
     * @return array{0: int|null, 1: int|null}
     */
    private function rentang(User $pengguna): array
    {
        $riwayat = HikingHistory::query()->where('user_id', $pengguna->id)->toBase()->selectRaw('min(completed_at) as awal, max(completed_at) as akhir')->first();
        $batal = TripPlan::query()->where('user_id', $pengguna->id)->where('status', TripStatus::CANCELLED->value)->toBase()->selectRaw('min(planned_date) as awal, max(planned_date) as akhir')->first();

        $tahun = array_values(array_filter([
            $riwayat?->awal === null ? null : Carbon::parse($riwayat->awal, 'UTC')->setTimezone(Timezone::DEFAULT)->year,
            $riwayat?->akhir === null ? null : Carbon::parse($riwayat->akhir, 'UTC')->setTimezone(Timezone::DEFAULT)->year,
            $batal?->awal === null ? null : Carbon::parse($batal->awal)->year,
            $batal?->akhir === null ? null : Carbon::parse($batal->akhir)->year,
        ]));

        return $tahun === [] ? [null, null] : [min($tahun), max($tahun)];
    }

    /**
     * Riwayat dan trip yang dibatalkan sebelum berangkat untuk satu tahun (WIB), terbaru dulu.
     *
     * @return array{0: Collection<int, HikingHistory>, 1: Collection<int, TripPlan>}
     */
    private function tahun(User $pengguna, int $tahun): array
    {
        $awal = Carbon::create($tahun, 1, 1, 0, 0, 0, Timezone::DEFAULT)->utc();

        $riwayat = HikingHistory::query()
            ->where('user_id', $pengguna->id)
            ->where('completed_at', '>=', $awal)
            ->where('completed_at', '<', $awal->copy()->addYear())
            ->with([
                'trail:id,name,mountain_id,elevation_gain_m,distance_km',
                'tripPlan:id,planned_date,end_date,cancellation_reason',
                'conditionReport' => fn ($laporan) => $laporan->select(['id', 'moderation_status'])->withCount('thanks'),
            ])
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->limit(self::BATAS_TAHUN + 1)
            ->get();

        $batal = TripPlan::query()
            ->where('user_id', $pengguna->id)
            ->where('status', TripStatus::CANCELLED->value)
            ->whereDoesntHave('history')
            ->whereBetween('planned_date', [$tahun.'-01-01', $tahun.'-12-31'])
            ->with('trail:id,name')
            ->orderByDesc('planned_date')
            ->orderByDesc('id')
            ->limit(self::BATAS_TAHUN + 1)
            ->get();

        return [$riwayat->take(self::BATAS_TAHUN), $batal->take(self::BATAS_TAHUN)];
    }

    /**
     * Satu daftar untuk riwayat dan grafik, terbaru dulu. Setiap butir membawa angka mentahnya
     * (untuk ringkasan dan grafik) dan bentuk tampilnya (untuk daftar riwayat).
     *
     * @param  Collection<int, HikingHistory>  $riwayat
     * @param  Collection<int, TripPlan>  $batal
     * @return Collection<int, array<string, mixed>>
     */
    private function butir(Collection $riwayat, Collection $batal): Collection
    {
        $dariRiwayat = $riwayat->map(function (HikingHistory $h) {
            $tanggal = $h->completed_at->copy()->setTimezone(Timezone::DEFAULT);
            $keadaan = match ($h->completion_state) {
                CompletionState::COMPLETED => 'selesai',
                CompletionState::PARTIAL => 'sebagian',
                default => 'batal',
            };
            ['naik' => $naik, 'jarakM' => $jarakM] = self::angka($h);
            $alasan = $keadaan === 'batal' ? $h->tripPlan?->cancellation_reason : null;

            return [
                'urut' => $tanggal->format('Y-m-d H:i:s').sprintf('%012d', $h->id),
                'nama' => $h->namaJalur(),
                'bulan' => $tanggal->translatedFormat('M'),
                'bulanPanjang' => $tanggal->translatedFormat('F'),
                'keadaan' => $keadaan,
                'alasan' => $alasan,
                'naik' => $naik,
                'jarakM' => $jarakM,
                'tampil' => [
                    'kunci' => 'r'.$h->id,
                    'nama' => $h->namaJalur(),
                    'tanggal' => $h->tripPlan !== null ? self::tanggalTrip($h->tripPlan) : $tanggal->translatedFormat('j M'),
                    'baris' => self::barisAngka($naik, $jarakM, $h->trip_type, $keadaan),
                    'keadaan' => $keadaan,
                    'lencana' => self::lencana($keadaan, $alasan, berbalik: true),
                    'catatan' => self::catatanLaporan($h),
                    'kalimatBatal' => $keadaan === 'batal' ? 'Keputusan yang baik tetap tercatat sebagai pendakian.' : null,
                    'url' => $h->trip_plan_id !== null ? route('history.summary', $h->trip_plan_id) : null,
                    'ubah' => $h->manual() ? self::ubah($h, $tanggal) : null,
                ],
            ];
        });

        $dariTrip = $batal->map(function (TripPlan $t) {
            return [
                'urut' => $t->planned_date->format('Y-m-d').' 00:00:00'.sprintf('%012d', 0),
                'nama' => $t->trail?->name ?? $t->name,
                'bulan' => $t->planned_date->translatedFormat('M'),
                'bulanPanjang' => $t->planned_date->translatedFormat('F'),
                'keadaan' => 'batal',
                'alasan' => $t->cancellation_reason,
                'naik' => null,
                'jarakM' => null,
                'tampil' => [
                    'kunci' => 't'.$t->id,
                    'nama' => $t->trail?->name ?? $t->name,
                    'tanggal' => self::tanggalTrip($t),
                    'baris' => 'batal sebelum pendakian dimulai',
                    'keadaan' => 'batal',
                    'lencana' => self::lencana('batal', $t->cancellation_reason, berbalik: false),
                    'catatan' => null,
                    'kalimatBatal' => 'Keputusan yang baik tetap tercatat sebagai pendakian.',
                    'url' => route('history.summary', $t),
                    'ubah' => null,
                ],
            ];
        });

        return $dariRiwayat->concat($dariTrip)->sortByDesc('urut')->values();
    }

    /**
     * Angka satu pendakian (ruling W7-R4): angka pendaki, lalu data jalur untuk yang tuntas,
     * selain itu tidak diketahui. Jarak dari data jalur adalah pulang-pergi lewat jalur yang sama
     * (prototype 12, ruling W6-R13).
     *
     * @return array{naik: int|null, jarakM: int|null}
     */
    public static function angka(HikingHistory $riwayat): array
    {
        if ($riwayat->manual() || $riwayat->numbers_edited_at !== null) {
            return ['naik' => $riwayat->recorded_gain_m, 'jarakM' => $riwayat->recorded_distance_m];
        }

        if ($riwayat->completion_state === CompletionState::COMPLETED && $riwayat->trail !== null) {
            return [
                'naik' => $riwayat->trail->elevation_gain_m,
                'jarakM' => $riwayat->trail->distance_km === null ? null : (int) round((float) $riwayat->trail->distance_km * 2000),
            ];
        }

        return ['naik' => null, 'jarakM' => null];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $butir
     * @return array<string, mixed>
     */
    private function ringkasan(Collection $butir, int $tahun): array
    {
        // Trip yang batal sebelum berangkat tidak punya angka; pendakian yang berbalik ikut
        // dijumlahkan hanya bila pendakinya menulis angkanya sendiri.
        $naik = $butir->pluck('naik')->reject(fn (?int $m) => $m === null);
        $jarak = $butir->pluck('jarakM')->reject(fn (?int $m) => $m === null);
        $dijalani = $butir->reject(fn (array $b) => $b['keadaan'] === 'batal');
        $tanpaNaik = $dijalani->filter(fn (array $b) => $b['naik'] === null)->count();
        $tanpaJarak = $dijalani->filter(fn (array $b) => $b['jarakM'] === null)->count();

        $rincian = collect([
            'selesai' => $butir->where('keadaan', 'selesai')->count(),
            'sebagian' => $butir->where('keadaan', 'sebagian')->count(),
            'batal' => $butir->where('keadaan', 'batal')->count(),
        ])->filter()->map(fn (int $n, string $kata) => $n.' '.$kata)->implode(', ');

        return [
            'judul' => 'Pendakian '.$tahun,
            'pendakian' => (string) $butir->count(),
            'rincian' => $rincian,
            'tanjakan' => $naik->isEmpty() ? null : number_format((int) $naik->sum(), 0, ',', '.'),
            'tanjakanCatatan' => $tanpaNaik > 0 ? $tanpaNaik.' pendakian tanpa angka tanjakan' : null,
            'jarak' => $jarak->isEmpty() ? null : number_format($jarak->sum() / 1000, 1, ',', '.'),
            'jarakCatatan' => $tanpaJarak > 0 ? $tanpaJarak.' pendakian tanpa angka jarak' : null,
            'sumber' => 'Dari data jalur dan angka yang Anda isi sendiri; rekaman jejak tidak ikut dijumlahkan.',
        ];
    }

    /**
     * Grafik per pendakian, dari yang paling awal, paling banyak BATAS_GRAFIK pendakian terakhir.
     * Setiap batang menulis nilainya; yang dibatalkan dan yang tanpa angka menyebut dirinya,
     * tidak pernah digambar sebagai nol (R-007).
     *
     * @param  Collection<int, array<string, mixed>>  $butir
     * @return array<string, mixed>
     */
    private function grafik(Collection $butir, int $tahun): array
    {
        $digambar = $butir->take(self::BATAS_GRAFIK)->reverse()->values();
        $terbaru = $digambar->count() - 1;

        $nilai = fn (array $b, string $ukuran): array => match (true) {
            $b['keadaan'] === 'batal' && $b[$ukuran] === null => ['nilai' => null, 'teks' => 'batal'],
            $b[$ukuran] === null => ['nilai' => null, 'teks' => 'belum ada'],
            $ukuran === 'naik' => ['nilai' => $b['naik'], 'teks' => number_format($b['naik'], 0, ',', '.').' m'],
            default => ['nilai' => round($b['jarakM'] / 1000, 1), 'teks' => number_format($b['jarakM'] / 1000, 1, ',', '.').' km'],
        };

        return [
            'batang' => $digambar->map(fn (array $b, int $i) => [
                'kunci' => $b['tampil']['kunci'],
                'nama' => $b['nama'],
                'bulan' => $b['bulan'],
                'keadaan' => $b['keadaan'],
                'terbaru' => $i === $terbaru,
                'tanjakan' => $nilai($b, 'naik'),
                'jarak' => $nilai($b, 'jarakM'),
            ])->all(),
            'keterangan' => [
                'tanjakan' => $this->keterangan($digambar, 'naik', 'Tanjakan', fn (int $m) => number_format($m, 0, ',', '.').' m', 'tanjakan'),
                'jarak' => $this->keterangan($digambar, 'jarakM', 'Jarak', fn (int $m) => number_format($m / 1000, 1, ',', '.').' km', 'jarak'),
            ],
            'catatan' => $butir->count() > self::BATAS_GRAFIK ? self::BATAS_GRAFIK.' pendakian terakhir di '.$tahun.' dari '.$butir->count().'; seluruhnya ada di riwayat di bawah.' : null,
        ];
    }

    /**
     * Keterangan kalimat grafik (spec v3 bagian 6.14 dan 7): nilai pertama dan terbaru yang
     * diketahui tanpa menilai, pendakian yang dibatalkan, dan pendakian tanpa angka.
     *
     * @param  Collection<int, array<string, mixed>>  $batang
     */
    private function keterangan(Collection $batang, string $kunci, string $nama, callable $teks, string $kataAngka): string
    {
        $diketahui = $batang->filter(fn (array $b) => $b[$kunci] !== null)->values();
        $kalimat = [];

        if ($diketahui->isEmpty()) {
            $kalimat[] = 'Belum ada angka '.$kataAngka.' untuk pendakian di sini.';
        } elseif ($diketahui->count() === 1) {
            $satu = $diketahui->first();
            $kalimat[] = $nama.' satu-satunya pendakian dengan angka: '.$teks($satu[$kunci]).' ('.$satu['nama'].', '.$satu['bulan'].').';
        } else {
            $awal = $diketahui->first();
            $akhir = $diketahui->last();
            $sebut = fn (array $b) => $teks($b[$kunci]).' ('.$b['nama'].', '.$b['bulan'].')';

            $kalimat[] = match ($akhir[$kunci] <=> $awal[$kunci]) {
                1 => $nama.' naik dari '.$sebut($awal).' ke '.$sebut($akhir).'.',
                -1 => $nama.' pendakian pertama di sini '.$sebut($awal).'; yang terbaru '.$sebut($akhir).'.',
                default => $nama.' pendakian pertama dan terbaru sama, '.$teks($akhir[$kunci]).'.',
            };
        }

        $batal = $batang->where('keadaan', 'batal')->values();

        if ($batal->count() > 2) {
            $kalimat[] = $batal->count().' pendakian dibatalkan dan tetap tercatat.';
        } else {
            foreach ($batal as $b) {
                $karena = self::karena($b['alasan']);
                $kalimat[] = $b['nama'].' di '.$b['bulanPanjang'].' dibatalkan'.($karena === null ? '' : ' karena '.$karena).' dan tetap tercatat.';
            }
        }

        $tanpa = $batang->filter(fn (array $b) => $b['keadaan'] !== 'batal' && $b[$kunci] === null)->count();

        if ($tanpa > 0) {
            $kalimat[] = $tanpa.' pendakian belum punya angka '.$kataAngka.'.';
        }

        return implode(' ', $kalimat);
    }

    private static function karena(?CancellationReason $alasan): ?string
    {
        return match ($alasan) {
            null, CancellationReason::OTHER => null,
            default => mb_strtolower($alasan->label()),
        };
    }

    private static function tanggalTrip(TripPlan $trip): string
    {
        $mulai = $trip->planned_date;
        $selesai = $trip->end_date;

        if ($selesai === null || $selesai->isSameDay($mulai)) {
            return $mulai->translatedFormat('j M');
        }

        return $mulai->isSameMonth($selesai)
            ? $mulai->format('j').'–'.$selesai->translatedFormat('j M')
            : $mulai->translatedFormat('j M').'–'.$selesai->translatedFormat('j M');
    }

    private static function barisAngka(?int $naik, ?int $jarakM, ?TripType $jenis, string $keadaan): string
    {
        $bagian = array_values(array_filter([
            $keadaan === 'batal' ? 'berbalik sebelum tujuan' : null,
            $naik === null ? null : '↑'.number_format($naik, 0, ',', '.').' m',
            $jarakM === null ? null : number_format($jarakM / 1000, 1, ',', '.').' km',
            $jenis === null ? null : mb_strtolower($jenis->labelPendek()),
        ]));

        return implode(' · ', $bagian);
    }

    /**
     * Lencana keadaan dengan kata, tidak warna saja: alasan pembatalan DK-50 ditulis bila disebut.
     */
    private static function lencana(string $keadaan, ?CancellationReason $alasan, bool $berbalik): string
    {
        $dasar = match ($keadaan) {
            'selesai' => 'Selesai',
            'sebagian' => 'Selesai sebagian',
            default => $berbalik ? 'Dibatalkan di jalur' : 'Dibatalkan',
        };

        return $alasan === null ? $dasar : $dasar.' · '.$alasan->label();
    }

    /**
     * Keadaan laporan kondisi yang ditautkan ke pendakian ini, atau tanda catatan manual.
     *
     * @return array{teks: string, nada: string}|null
     */
    private static function catatanLaporan(HikingHistory $riwayat): ?array
    {
        if ($riwayat->manual()) {
            return ['teks' => 'Dicatat manual', 'nada' => 'redup'];
        }

        $laporan = $riwayat->conditionReport;

        return match (true) {
            $laporan === null => null,
            in_array($laporan->moderation_status, [ModerationStatus::PENDING, ModerationStatus::FLAGGED], true) => ['teks' => 'Laporan kondisi menunggu moderasi', 'nada' => 'komunitas'],
            $laporan->moderation_status === ModerationStatus::APPROVED => ['teks' => 'Laporan terbit'.($laporan->thanks_count > 0 ? ' · '.$laporan->thanks_count.' terima kasih' : ''), 'nada' => 'komunitas'],
            default => ['teks' => 'Laporan tidak diterbitkan moderator', 'nada' => 'redup'],
        };
    }

    /**
     * Isi awal formulir "Ubah catatan" untuk catatan manual milik pembaca.
     *
     * @return array<string, mixed>
     */
    private static function ubah(HikingHistory $riwayat, Carbon $tanggal): array
    {
        return [
            'simpan' => route('progress.records.update', $riwayat),
            'hapus' => route('progress.records.destroy', $riwayat),
            'jalur' => $riwayat->trail === null ? null : ['id' => $riwayat->trail->id, 'nama' => $riwayat->trail->name],
            'awal' => [
                'trail_id' => $riwayat->trail_id === null ? '' : (string) $riwayat->trail_id,
                'nama_jalur' => (string) $riwayat->trail_name,
                'tanggal' => $tanggal->toDateString(),
                'jenis' => $riwayat->trip_type?->value ?? TripType::TEKTOK->value,
                'jarak_km' => $riwayat->recorded_distance_m === null ? '' : str_replace('.', ',', (string) round($riwayat->recorded_distance_m / 1000, 1)),
                'naik_m' => $riwayat->recorded_gain_m === null ? '' : (string) $riwayat->recorded_gain_m,
                'jam' => $riwayat->recorded_moving_minutes === null ? '' : (string) intdiv($riwayat->recorded_moving_minutes, 60),
                'menit' => $riwayat->recorded_moving_minutes === null ? '' : (string) ($riwayat->recorded_moving_minutes % 60),
                'tertinggi_m' => $riwayat->recorded_highest_m === null ? '' : (string) $riwayat->recorded_highest_m,
                'catatan' => (string) $riwayat->personal_notes,
            ],
        ];
    }
}
```

Buat `app/Services/Trails/CariJalur.php`:

```php
<?php

namespace App\Services\Trails;

use App\Models\Trail;
use Illuminate\Support\Collection;

/**
 * Pencarian jalur katalog untuk isian yang menunjuk satu jalur (W7): catatan pendakian di luar
 * trip dan jalur terberat di profil pendaki. Hanya jalur terbit (R-022), dengan pencocokan kata
 * kunci milik Jelajah, yang tidak peka huruf besar di SQLite maupun PostgreSQL (TrailSearch,
 * R-024). Kata kunci di bawah dua huruf tidak mencari apa pun, jadi katalog tidak pernah
 * dikirim utuh; hasilnya paling banyak BATAS jalur, urut nama.
 */
final class CariJalur
{
    public const BATAS = 8;

    /**
     * @return Collection<int, Trail>
     */
    public function untuk(string $kata): Collection
    {
        $kata = trim($kata);

        if (mb_strlen($kata) < 2) {
            return collect();
        }

        return TrailSearch::dariInput(['search' => mb_substr($kata, 0, 80)])->kandidat(self::BATAS)->take(self::BATAS)->values();
    }

    /**
     * Satu baris hasil: nama, gunung, dan baris ukuran padat jalur itu.
     *
     * @return array{id: int, nama: string, gunung: string|null, ringkas: string}
     */
    public static function baris(Trail $jalur): array
    {
        return [
            'id' => $jalur->id,
            'nama' => $jalur->name,
            'gunung' => $jalur->mountain?->name,
            'ringkas' => TrailFigures::ringkas($jalur),
        ];
    }
}
```

Buat `resources/js/components/progres/FormulirCatatan.tsx`:

```tsx
import { router, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import { PemilihJalur } from '@/components/trails/PemilihJalur';
import { Button } from '@/components/ui/Button';
import { InputError, Label, Textarea, TextInput } from '@/components/ui/Field';
import { KontrolSegmen } from '@/components/ui/KontrolSegmen';
import { Dialog } from '@/components/ui/Lembar';
import type { FormulirCatatanData, IsianCatatan, UbahCatatan } from './types';

const kosong = (tanggal: string, jenis: string): IsianCatatan => ({
    trail_id: '',
    nama_jalur: '',
    tanggal,
    jenis,
    jarak_km: '',
    naik_m: '',
    jam: '',
    menit: '',
    tertinggi_m: '',
    catatan: '',
});

/**
 * Isi lembar "Catat pendakian di luar trip" dan "Ubah catatan pendakian" (FR-25, spec v3 bagian
 * 6.14). Jalurnya dari katalog atau ditulis sendiri; tanggal tidak boleh di masa depan; setiap
 * angka opsional dan yang dikosongkan tetap tidak diketahui (R-007). Pemilik dan asal catatan
 * ditentukan server. Hapus meminta konfirmasi yang menyebut akibatnya.
 */
export function FormulirCatatan({ formulir, ubah, onSelesai }: { formulir: FormulirCatatanData; ubah: UbahCatatan | null; onSelesai: () => void }) {
    const form = useForm<IsianCatatan>(ubah?.awal ?? kosong(formulir.tanggalMaks, formulir.jenis[0]?.nilai ?? 'TEKTOK'));
    const [jalur, setJalur] = useState<{ id: number; nama: string } | null>(ubah?.jalur ?? null);
    const [manual, setManual] = useState(ubah !== null && ubah.jalur === null);
    const [menghapus, setMenghapus] = useState(false);
    const [konfirmasiHapus, setKonfirmasiHapus] = useState(false);
    const galat = form.errors as Partial<Record<string, string>>;

    const kirim = (e: FormEvent) => {
        e.preventDefault();
        form.transform((d) => ({
            trail_id: manual || jalur === null ? '' : String(jalur.id),
            nama_jalur: manual ? d.nama_jalur : '',
            tanggal: d.tanggal,
            jenis: d.jenis,
            jarak_km: d.jarak_km,
            naik_m: d.naik_m,
            waktu_menit: d.jam === '' && d.menit === '' ? '' : String(Number(d.jam || 0) * 60 + Number(d.menit || 0)),
            tertinggi_m: d.tertinggi_m,
            catatan: d.catatan,
        }));

        const selesai = { preserveScroll: true, onSuccess: onSelesai };

        if (ubah) {
            form.put(ubah.simpan, selesai);
        } else {
            form.post(formulir.simpan, selesai);
        }
    };

    const hapus = () => {
        if (!ubah) {
            return;
        }

        router.delete(ubah.hapus, {
            preserveScroll: true,
            onStart: () => setMenghapus(true),
            onFinish: () => setMenghapus(false),
            onSuccess: () => {
                setKonfirmasiHapus(false);
                onSelesai();
            },
        });
    };

    return (
        <form onSubmit={kirim} noValidate className="space-y-5">
            <PemilihJalur
                label="Jalur"
                dipilih={manual ? null : jalur}
                onPilih={(j) => setJalur(j === null ? null : { id: j.id, nama: j.nama })}
                manual={{
                    aktif: manual,
                    label: 'Jalurnya tidak ada di katalog',
                    nama: form.data.nama_jalur,
                    onAktif: setManual,
                    onNama: (nama) => form.setData('nama_jalur', nama),
                }}
                galat={galat.trail_id ?? galat.nama_jalur}
            />

            <div>
                <Label htmlFor="catatan-tanggal">Tanggal pendakian</Label>
                <TextInput
                    id="catatan-tanggal"
                    type="date"
                    max={formulir.tanggalMaks}
                    value={form.data.tanggal}
                    onChange={(e) => form.setData('tanggal', e.target.value)}
                    aria-invalid={galat.tanggal ? true : undefined}
                    aria-describedby={galat.tanggal ? 'catatan-tanggal-galat' : undefined}
                />
                <InputError id="catatan-tanggal-galat" messages={galat.tanggal} className="mt-2" />
            </div>

            <KontrolSegmen nama="catatan-jenis" label="Jenis pendakian" pilihan={formulir.jenis} nilai={form.data.jenis} onUbah={(v) => form.setData('jenis', v)} penjelas={galat.jenis ? 'catatan-jenis-galat' : undefined} />
            <InputError id="catatan-jenis-galat" messages={galat.jenis} className="-mt-3" />

            <fieldset className="space-y-3">
                <legend className="text-isi font-bold text-primary">Angka pendakian</legend>
                <p className="text-kecil text-secondary">Isi dari catatan Anda sendiri, misalnya jam tangan atau aplikasi lain. Kosongkan yang tidak Anda ketahui.</p>
                <div className="grid grid-cols-2 gap-3">
                    <IsianAngka id="catatan-jarak" label="Jarak (km)" nilai={form.data.jarak_km} onUbah={(v) => form.setData('jarak_km', v)} galat={galat.jarak_km} desimal />
                    <IsianAngka id="catatan-naik" label="Tanjakan (m)" nilai={form.data.naik_m} onUbah={(v) => form.setData('naik_m', v)} galat={galat.naik_m} />
                </div>
                <fieldset aria-describedby={galat.waktu_menit ? 'catatan-waktu-galat' : undefined}>
                    <legend className="text-kecil font-bold text-primary">Waktu bergerak</legend>
                    <div className="mt-1 grid grid-cols-2 gap-3">
                        <IsianAngka id="catatan-jam" label="Jam" nilai={form.data.jam} onUbah={(v) => form.setData('jam', v)} />
                        <IsianAngka id="catatan-menit" label="Menit" nilai={form.data.menit} onUbah={(v) => form.setData('menit', v)} />
                    </div>
                    <InputError id="catatan-waktu-galat" messages={galat.waktu_menit} className="mt-2" />
                </fieldset>
                <IsianAngka id="catatan-tertinggi" label="Titik tertinggi (m)" nilai={form.data.tertinggi_m} onUbah={(v) => form.setData('tertinggi_m', v)} galat={galat.tertinggi_m} />
            </fieldset>

            <div>
                <Label htmlFor="catatan-isi">Catatan (opsional)</Label>
                <Textarea
                    id="catatan-isi"
                    rows={3}
                    maxLength={1000}
                    value={form.data.catatan}
                    onChange={(e) => form.setData('catatan', e.target.value)}
                    aria-invalid={galat.catatan ? true : undefined}
                    aria-describedby={galat.catatan ? 'catatan-isi-galat' : undefined}
                />
                <InputError id="catatan-isi-galat" messages={galat.catatan} className="mt-2" />
            </div>

            <p className="text-kecil text-secondary">Catatan ini ditandai diisi sendiri dan tidak menaikkan tingkat pengalaman Anda.</p>

            <div className="flex flex-wrap items-center gap-3">
                <Button type="submit" processing={form.processing}>
                    {ubah ? 'Simpan perubahan' : 'Simpan catatan'}
                </Button>
                {ubah ? (
                    <Button variant="secondary" onClick={() => setKonfirmasiHapus(true)}>
                        Hapus catatan
                    </Button>
                ) : null}
            </div>

            {ubah ? (
                <Dialog
                    terbuka={konfirmasiHapus}
                    onTutup={() => setKonfirmasiHapus(false)}
                    judul="Hapus catatan pendakian ini?"
                    keterangan="Catatan dan angkanya hilang dari riwayat dan progres Anda, dan tidak dapat dikembalikan."
                    aksi={
                        <>
                            <Button variant="secondary" onClick={() => setKonfirmasiHapus(false)}>
                                Batal
                            </Button>
                            <Button variant="danger" processing={menghapus} onClick={hapus}>
                                Hapus catatan
                            </Button>
                        </>
                    }
                >
                    <p className="text-isi text-primary">{jalur?.nama ?? form.data.nama_jalur}</p>
                </Dialog>
            ) : null}
        </form>
    );
}

function IsianAngka({ id, label, nilai, onUbah, galat, desimal = false }: { id: string; label: string; nilai: string; onUbah: (nilai: string) => void; galat?: string; desimal?: boolean }) {
    return (
        <div className="min-w-0">
            <Label htmlFor={id}>{label}</Label>
            <TextInput
                id={id}
                inputMode={desimal ? 'decimal' : 'numeric'}
                value={nilai}
                onChange={(e) => onUbah(e.target.value)}
                aria-invalid={galat ? true : undefined}
                aria-describedby={galat ? `${id}-galat` : undefined}
                className="tabular-nums"
            />
            <InputError id={`${id}-galat`} messages={galat} className="mt-2" />
        </div>
    );
}
```

Buat `resources/js/components/progres/GrafikPendakian.tsx`:

```tsx
import { useState } from 'react';
import { KontrolSegmen } from '@/components/ui/KontrolSegmen';
import { kelas } from '@/lib/kelas';
import type { BatangPendakian, GrafikPendakianData } from './types';

type Ukuran = 'tanjakan' | 'jarak';

const pilihanUkuran = [
    { nilai: 'tanjakan', label: 'Tanjakan' },
    { nilai: 'jarak', label: 'Jarak' },
] as const;

/**
 * Grafik per pendakian (spec v3 bagian 6.14, prototype 13), dibandingkan hanya dengan pendakian
 * pendaki ini sendiri (R-015). Satu baris per pendakian dari yang paling awal, dengan nilai
 * tertulis di setiap baris: dua belas kolom tegak seperti prototype tidak muat di 320 px bersama
 * nilainya (koreksi terukur, ruling W7-R5). Yang dibatalkan dan yang tanpa angka menyebut dirinya
 * dengan kata ("batal", "belum ada"), tidak lewat garis putus-putus atau warna saja, dan tidak
 * pernah digambar sebagai nol (R-007). Batang disembunyikan dari pembaca layar; tabel di
 * bawahnya membawa angka yang sama untuk kedua ukuran.
 */
export function GrafikPendakian({ grafik }: { grafik: GrafikPendakianData }) {
    const [ukuran, setUkuran] = useState<Ukuran>('tanjakan');
    const puncak = Math.max(1, ...grafik.batang.map((b) => b[ukuran].nilai ?? 0));

    return (
        <section aria-labelledby="judul-grafik-pendakian">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <h2 id="judul-grafik-pendakian" className="text-bagian text-primary">
                    Per pendakian
                </h2>
                <KontrolSegmen nama="ukuran-grafik" label="Ukuran pada grafik" pilihan={pilihanUkuran} nilai={ukuran} onUbah={setUkuran} className="w-56 max-w-full" />
            </div>
            <figure className="relative mt-3 rounded-kartu border border-subtle bg-surface p-4">
                <ol aria-hidden="true" className="space-y-3">
                    {grafik.batang.map((b) => (
                        <Baris key={b.kunci} batang={b} ukuran={ukuran} puncak={puncak} />
                    ))}
                </ol>
                {/* Pembungkus sr-only, bukan tabelnya: kotak tabel tidak terpotong overflow, jadi
                    tabel ber-sr-only melebarkan halaman di layar sempit. */}
                <div className="sr-only">
                    <table>
                        <caption>Tanjakan dan jarak setiap pendakian, dari yang paling awal</caption>
                        <thead>
                            <tr>
                                <th scope="col">Pendakian</th>
                                <th scope="col">Bulan</th>
                                <th scope="col">Tanjakan</th>
                                <th scope="col">Jarak</th>
                            </tr>
                        </thead>
                        <tbody>
                            {grafik.batang.map((b) => (
                                <tr key={b.kunci}>
                                    <th scope="row">
                                        {b.nama}
                                        {b.terbaru ? ' (terbaru)' : ''}
                                    </th>
                                    <td>{b.bulan}</td>
                                    <td>{b.tanjakan.teks}</td>
                                    <td>{b.jarak.teks}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                <figcaption className="mt-4 max-w-prose text-kecil text-primary">{grafik.keterangan[ukuran]}</figcaption>
                {grafik.catatan ? <p className="mt-2 text-meta text-muted">{grafik.catatan}</p> : null}
            </figure>
        </section>
    );
}

function Baris({ batang, ukuran, puncak }: { batang: BatangPendakian; ukuran: Ukuran; puncak: number }) {
    const angka = batang[ukuran];
    const lebar = angka.nilai === null ? 0 : Math.max(3, (angka.nilai / puncak) * 100);

    return (
        <li className="grid grid-cols-[minmax(0,1fr)_auto] items-baseline gap-x-3 gap-y-1">
            <span className="min-w-0 break-words text-kecil text-primary">
                <span className={batang.terbaru ? 'font-bold' : 'font-semibold'}>{batang.nama}</span> <span className="text-meta text-muted">{batang.bulan}</span>
            </span>
            <span className={kelas('text-kecil tabular-nums', angka.nilai === null ? 'text-muted' : 'text-primary', batang.terbaru && 'font-bold')}>{angka.teks}</span>
            <span
                className={kelas(
                    'col-span-2 block h-3 overflow-hidden rounded-full',
                    batang.keadaan === 'batal' && angka.nilai === null ? 'border border-dashed border-control' : 'bg-surface-sunken',
                )}
            >
                {angka.nilai !== null ? <span className={kelas('block h-full rounded-full', batang.terbaru ? 'bg-brand-700' : 'bg-brand-500')} style={{ width: `${lebar}%` }} /> : null}
            </span>
        </li>
    );
}
```

Buat `resources/js/components/progres/RingkasanTahun.tsx`:

```tsx
import type { RingkasanTahun as Ringkasan } from './types';

/**
 * Ringkasan satu tahun (spec v3 bagian 6.14, prototype 13): tiga petak dalam satu daftar, jadi
 * dibacakan sebagai satu kelompok, bukan tiga klaim setara. Angka yang tidak diketahui ditulis
 * "Belum ada", dan pendakian tanpa angka disebut jumlahnya, supaya total tidak terbaca lengkap
 * padahal tidak (R-007).
 */
export function RingkasanTahun({ ringkasan }: { ringkasan: Ringkasan }) {
    const petak = [
        { judul: ringkasan.judul, nilai: ringkasan.pendakian, satuan: ringkasan.rincian, catatan: null },
        { judul: 'Tanjakan', nilai: ringkasan.tanjakan, satuan: 'meter', catatan: ringkasan.tanjakanCatatan },
        { judul: 'Jarak', nilai: ringkasan.jarak, satuan: 'kilometer', catatan: ringkasan.jarakCatatan },
    ];

    return (
        <section aria-labelledby="judul-ringkasan-tahun">
            <h2 id="judul-ringkasan-tahun" className="sr-only">
                Ringkasan {ringkasan.judul.toLocaleLowerCase('id-ID')}
            </h2>
            <dl className="grid grid-cols-1 gap-2 min-[360px]:grid-cols-3">
                {petak.map((p) => (
                    <div key={p.judul} className="flex min-w-0 flex-col rounded-kartu border border-subtle bg-surface p-2.5 sm:p-3">
                        <dt className="text-label font-semibold text-secondary">{p.judul}</dt>
                        <dd className={p.nilai === null ? 'mt-1 text-kecil font-bold text-primary' : 'mt-0.5 break-words text-bagian font-extrabold text-primary'}>{p.nilai ?? 'Belum ada'}</dd>
                        {p.nilai === null ? null : <dd className="text-label text-secondary">{p.satuan}</dd>}
                        {p.catatan ? <dd className="mt-1 text-label text-muted">{p.catatan}</dd> : null}
                    </div>
                ))}
            </dl>
            <p className="mt-2 text-meta text-muted">{ringkasan.sumber}</p>
        </section>
    );
}
```

Buat `resources/js/components/progres/RiwayatProgres.tsx`:

```tsx
import { useState, type ReactNode } from 'react';
import { Icon } from '@/components/ui/Icon';
import { LembarBawah } from '@/components/ui/Lembar';
import { Lencana } from '@/components/ui/Lencana';
import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';
import { FormulirCatatan } from './FormulirCatatan';
import type { ButirRiwayat, FormulirCatatanData, KabarProgres, UbahCatatan } from './types';

const kartu = 'block w-full rounded-kartu border bg-surface p-3.5 text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600';

/**
 * Riwayat satu tahun (spec v3 bagian 6.14, prototype 13): setiap pendakian dengan keadaannya,
 * alasan pembatalan bila disebut (DK-50), dan keadaan laporan kondisinya. Pendakian dari trip
 * membuka ringkasannya; catatan manual membuka lembar ubah. Pendakian yang dibatalkan bergaris
 * putus-putus dan menyebut dirinya dengan kata, bukan garis saja. Kabar tulisan dibacakan di
 * wilayah status yang selalu terpasang di bagian ini (konvensi umpan balik aksi, DESIGN.md).
 */
export function RiwayatProgres({ tahun, riwayat, terpotong, formulir, kabar }: { tahun: number; riwayat: ButirRiwayat[]; terpotong: string | null; formulir: FormulirCatatanData; kabar: KabarProgres | null }) {
    const [sasaran, setSasaran] = useState<{ ubah: UbahCatatan | null } | null>(null);

    return (
        <section aria-labelledby="judul-riwayat">
            <div className="flex items-baseline justify-between gap-3">
                <h2 id="judul-riwayat" className="text-bagian text-primary">
                    Riwayat
                </h2>
                <span className="text-meta tabular-nums text-muted">{tahun}</span>
            </div>
            <p role="status" className={kabar?.bagian === 'riwayat' ? 'mt-2 rounded-kartu border border-brand-300 bg-brand-100 p-3 text-kecil text-brand-900' : undefined}>
                {kabar?.bagian === 'riwayat' ? kabar.teks : ''}
            </p>

            {riwayat.length === 0 ? (
                <p className="mt-2 text-isi text-secondary">Belum ada pendakian tercatat di {tahun}.</p>
            ) : (
                <ul className="mt-3 space-y-2">
                    {riwayat.map((b) => (
                        <li key={b.kunci}>
                            {b.url !== null ? (
                                <Tautan href={b.url} className={kelas(kartu, b.keadaan === 'batal' ? 'border-dashed border-control' : 'border-subtle', 'hover:bg-surface-sunken')}>
                                    <IsiButir butir={b} />
                                </Tautan>
                            ) : b.ubah !== null ? (
                                <button type="button" onClick={() => setSasaran({ ubah: b.ubah })} className={kelas(kartu, 'border-subtle hover:bg-surface-sunken')}>
                                    <IsiButir butir={b} />
                                    <span className="sr-only">, ubah catatan</span>
                                </button>
                            ) : (
                                <div className={kelas(kartu, 'border-subtle')}>
                                    <IsiButir butir={b} />
                                </div>
                            )}
                        </li>
                    ))}
                </ul>
            )}
            {terpotong ? <p className="mt-2 text-meta text-muted">{terpotong}</p> : null}

            <button
                type="button"
                onClick={() => setSasaran({ ubah: null })}
                className="mt-3 inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-tombol border-2 border-dashed border-brand-700 px-4 text-isi font-bold text-brand-ink hover:bg-brand-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2"
            >
                <Icon name="plus" size={20} />
                Catat pendakian di luar trip
            </button>

            <LembarBawah
                terbuka={sasaran !== null}
                onTutup={() => setSasaran(null)}
                judul={sasaran?.ubah ? 'Ubah catatan pendakian' : 'Catat pendakian di luar trip'}
                keterangan="Pendakian yang tidak direncanakan lewat trip di Memount."
            >
                {sasaran !== null ? <FormulirCatatan key={sasaran.ubah?.simpan ?? 'baru'} formulir={formulir} ubah={sasaran.ubah} onSelesai={() => setSasaran(null)} /> : null}
            </LembarBawah>
        </section>
    );
}

function IsiButir({ butir }: { butir: ButirRiwayat }): ReactNode {
    return (
        <>
            <span className="flex flex-wrap items-start justify-between gap-x-3 gap-y-1">
                <span className="min-w-0 break-words text-subjudul text-primary">{butir.nama}</span>
                <Lencana nada={butir.keadaan === 'selesai' ? 'merek' : 'netral'}>{butir.lencana}</Lencana>
            </span>
            <span className="mt-1 block text-meta tabular-nums text-secondary">{[butir.tanggal, butir.baris].filter(Boolean).join(' · ')}</span>
            {butir.catatan ? <span className={kelas('mt-1 block text-meta', butir.catatan.nada === 'komunitas' ? 'text-community-900' : 'text-muted')}>{butir.catatan.teks}</span> : null}
            {butir.kalimatBatal ? <span className="mt-1 block text-meta text-primary">{butir.kalimatBatal}</span> : null}
        </>
    );
}
```

Buat `resources/js/components/progres/types.ts`:

```ts
/** Kabar tulisan terakhir di halaman Progres, dibacakan di wilayah bagian asalnya. */
export interface KabarProgres {
    bagian: 'riwayat' | 'target';
    teks: string;
}

/** Ringkasan satu tahun (spec v3 bagian 6.14): hitungan pendakian, total tanjakan dan jarak. */
export interface RingkasanTahun {
    judul: string;
    pendakian: string;
    rincian: string;
    tanjakan: string | null;
    tanjakanCatatan: string | null;
    jarak: string | null;
    jarakCatatan: string | null;
    sumber: string;
}

/** Satu angka batang: nilai untuk panjang batang (null bila batal atau belum ada) dan teksnya. */
export interface NilaiBatang {
    nilai: number | null;
    teks: string;
}

export interface BatangPendakian {
    kunci: string;
    nama: string;
    bulan: string;
    keadaan: 'selesai' | 'sebagian' | 'batal';
    terbaru: boolean;
    tanjakan: NilaiBatang;
    jarak: NilaiBatang;
}

export interface GrafikPendakianData {
    batang: BatangPendakian[];
    keterangan: { tanjakan: string; jarak: string };
    catatan: string | null;
}

/** Isian formulir catatan pendakian di luar trip, sebagai teks seperti di isian. */
export interface IsianCatatan {
    trail_id: string;
    nama_jalur: string;
    tanggal: string;
    jenis: string;
    jarak_km: string;
    naik_m: string;
    jam: string;
    menit: string;
    tertinggi_m: string;
    catatan: string;
}

export interface UbahCatatan {
    simpan: string;
    hapus: string;
    jalur: { id: number; nama: string } | null;
    awal: IsianCatatan;
}

export interface ButirRiwayat {
    kunci: string;
    nama: string;
    tanggal: string;
    baris: string;
    keadaan: 'selesai' | 'sebagian' | 'batal';
    lencana: string;
    catatan: { teks: string; nada: 'komunitas' | 'redup' } | null;
    kalimatBatal: string | null;
    url: string | null;
    ubah: UbahCatatan | null;
}

export interface FormulirCatatanData {
    simpan: string;
    tanggalMaks: string;
    jenis: Array<{ nilai: string; label: string }>;
}
```

Buat `resources/js/components/trails/PemilihJalur.tsx`:

```tsx
import { router, usePage } from '@inertiajs/react';
import { useEffect, useId, useRef, useState, type ReactNode } from 'react';
import { Button } from '@/components/ui/Button';
import { InputError, Label, TextInput } from '@/components/ui/Field';
import { kelas } from '@/lib/kelas';

/** Satu jalur katalog hasil pencarian (App\Services\Trails\CariJalur::baris). */
export interface JalurKatalog {
    id: number;
    nama: string;
    gunung: string | null;
    ringkas: string;
}

/**
 * Pencarian jalur katalog lewat muat ulang parsial prop `pilihanJalur` (hanya jalur terbit,
 * paling banyak delapan). Ketikan ditunda 300 ms dan muat ulang yang lebih lama dibatalkan,
 * supaya hasil yang tiba terakhir selalu milik ketikan terakhir; URL halaman tidak berubah.
 */
export function useCariJalur<T extends JalurKatalog = JalurKatalog>() {
    const { pilihanJalur } = usePage<{ pilihanJalur?: T[] }>().props;
    const [kata, setKata] = useState('');
    const [memuat, setMemuat] = useState(false);
    const tunda = useRef<number | null>(null);
    const batal = useRef<{ cancel: () => void } | null>(null);
    const nomor = useRef(0);

    useEffect(
        () => () => {
            if (tunda.current !== null) {
                window.clearTimeout(tunda.current);
            }
            batal.current?.cancel();
        },
        [],
    );

    const cari = (baru: string) => {
        setKata(baru);

        if (tunda.current !== null) {
            window.clearTimeout(tunda.current);
        }

        if (baru.trim().length < 2) {
            return;
        }

        tunda.current = window.setTimeout(() => {
            batal.current?.cancel();
            const id = ++nomor.current;

            router.reload({
                only: ['pilihanJalur'],
                data: { cari: baru.trim() },
                preserveUrl: true,
                onCancelToken: (token) => {
                    batal.current = token;
                },
                onStart: () => setMemuat(true),
                onFinish: () => {
                    if (id === nomor.current) {
                        setMemuat(false);
                    }
                },
            });
        }, 300);
    };

    return { kata, cari, memuat, hasil: kata.trim().length < 2 ? [] : (pilihanJalur ?? []) };
}

interface PemilihJalurProps<T extends JalurKatalog> {
    /** Judul grup, misalnya "Jalur" atau "Jalur terberat yang pernah Anda selesaikan". */
    label: string;
    dipilih: Pick<JalurKatalog, 'id' | 'nama'> | null;
    onPilih: (jalur: T | null) => void;
    /** Pilihan "jalurnya tidak ada di katalog" beserta isiannya, bila pemanggil menawarkannya. */
    manual?: { aktif: boolean; label: string; nama: string; onAktif: (aktif: boolean) => void; onNama: (nama: string) => void };
    galat?: string;
    /** Isi tambahan di bawah jalur terpilih, misalnya nilai turunan untuk dikonfirmasi. */
    children?: ReactNode;
}

/**
 * Pemilih satu jalur katalog: cari, pilih dari hasil, atau tulis nama bila jalurnya tidak ada
 * di katalog. Setiap hasil tombol 44 px yang menyebut nama, gunung, dan angka jalurnya; jumlah
 * hasil dibacakan di wilayah status yang selalu terpasang. Sesudah memilih, fokus pindah ke
 * "Ganti jalur", jadi pengguna papan ketik tidak kehilangan tempat.
 */
export function PemilihJalur<T extends JalurKatalog>({ label, dipilih, onPilih, manual, galat, children }: PemilihJalurProps<T>) {
    const id = useId();
    const { kata, cari, memuat, hasil } = useCariJalur<T>();
    const [fokusKe, setFokusKe] = useState<'ganti' | 'cari' | null>(null);

    // Fokus dipindah sesudah unsur tujuannya digambar: "Ganti jalur" sesudah memilih, kotak
    // cari sesudah mengganti.
    useEffect(() => {
        if (fokusKe !== null) {
            document.getElementById(`${id}-${fokusKe}`)?.focus();
            setFokusKe(null);
        }
    }, [fokusKe, id]);

    const status = kata.trim().length < 2 ? 'Ketik paling sedikit dua huruf nama gunung atau jalur.' : memuat ? 'Mencari jalur.' : hasil.length === 0 ? 'Tidak ada jalur katalog yang cocok.' : `${hasil.length} jalur ditemukan.`;

    return (
        <fieldset aria-describedby={galat ? `${id}-galat` : undefined}>
            <legend className="text-isi font-bold text-primary">{label}</legend>

            {dipilih && !manual?.aktif ? (
                <div className="mt-2 rounded-tombol border border-brand-700 bg-brand-50 p-3">
                    <p className="break-words text-isi font-bold text-primary">{dipilih.nama}</p>
                    {children}
                    <Button
                        id={`${id}-ganti`}
                        variant="secondary"
                        size="sm"
                        className="mt-3"
                        onClick={() => {
                            onPilih(null);
                            setFokusKe('cari');
                        }}
                    >
                        Ganti jalur
                    </Button>
                </div>
            ) : (
                <>
                    {!manual?.aktif ? (
                        <div className="mt-2">
                            <Label htmlFor={`${id}-cari`}>Cari di katalog</Label>
                            <TextInput
                                id={`${id}-cari`}
                                type="search"
                                autoComplete="off"
                                placeholder="Nama gunung atau jalur"
                                value={kata}
                                onChange={(e) => cari(e.target.value)}
                                aria-describedby={`${id}-status`}
                            />
                            <p id={`${id}-status`} role="status" className="mt-1 text-kecil text-secondary">
                                {status}
                            </p>
                            {hasil.length > 0 ? (
                                <ul className="mt-2 space-y-2">
                                    {hasil.map((j) => (
                                        <li key={j.id}>
                                            <button
                                                type="button"
                                                onClick={() => {
                                                    onPilih(j);
                                                    setFokusKe('ganti');
                                                }}
                                                className={kelas(
                                                    'flex min-h-11 w-full flex-col items-start rounded-tombol border border-subtle bg-surface px-3 py-2 text-left hover:bg-surface-sunken',
                                                    'focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600',
                                                )}
                                            >
                                                <span className="break-words text-isi font-bold text-primary">{j.nama}</span>
                                                <span className="text-kecil text-secondary">{[j.gunung, j.ringkas].filter(Boolean).join(' · ')}</span>
                                            </button>
                                        </li>
                                    ))}
                                </ul>
                            ) : null}
                        </div>
                    ) : null}

                    {manual ? (
                        <div className="mt-3">
                            <label htmlFor={`${id}-manual`} className="flex min-h-11 cursor-pointer items-center gap-3 text-isi text-primary">
                                <input
                                    id={`${id}-manual`}
                                    type="checkbox"
                                    checked={manual.aktif}
                                    onChange={(e) => manual.onAktif(e.target.checked)}
                                    className="size-6 shrink-0 rounded-kecil border-2 border-control text-brand-700 focus:ring-brand-600"
                                />
                                {manual.label}
                            </label>
                            {manual.aktif ? (
                                <div className="mt-2">
                                    <Label htmlFor={`${id}-nama`}>Nama jalur</Label>
                                    <TextInput id={`${id}-nama`} value={manual.nama} onChange={(e) => manual.onNama(e.target.value)} maxLength={120} placeholder="Misalnya Lawu via Cemoro Sewu" />
                                </div>
                            ) : null}
                        </div>
                    ) : null}
                </>
            )}

            <InputError id={`${id}-galat`} messages={galat} className="mt-2" />
        </fieldset>
    );
}
```

Tulis ulang `app/Http/Controllers/Community/HikerProgressController.php` utuh:

```php
<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Models\Trail;
use App\Services\Community\HubProgres;
use App\Services\Trails\CariJalur;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Progres pendaki, dipantulkan kembali kepadanya sendiri (R-015; spec v3 bagian 6.14). Isinya
 * milik HubProgres; `?tahun=` memilih tahun yang dibuka. Hasil pencarian jalur katalog untuk
 * formulir catatan manual hanya dikirim lewat muat ulang parsial, saat pendaki mencari.
 */
class HikerProgressController extends Controller
{
    public function __invoke(Request $request, HubProgres $hub, CariJalur $cari): Response
    {
        $diminta = $request->integer('tahun');
        $kata = $request->query('cari');

        return Inertia::render('Community/HikerProgress', [
            'title' => 'Progres Pendakian',
            ...$hub->untuk($request->user(), $diminta > 0 ? $diminta : null, $request->session()->get('kabar-progres')),
            'pilihanJalur' => Inertia::optional(fn () => $cari->untuk(is_string($kata) ? $kata : '')->map(fn (Trail $t) => CariJalur::baris($t))->all()),
        ]);
    }
}
```

Tulis ulang `app/Services/HikerProgressService.php` utuh:

```php
<?php

namespace App\Services;

use App\Enums\CompletionState;
use App\Models\HikingHistory;
use App\Models\Mountain;
use App\Models\TrailConditionReport;
use App\Models\User;

/**
 * Apa yang sudah ditempuh seorang pendaki, dipantulkan kembali kepadanya.
 *
 * Dalam Self-Determination Theory inilah competence: melihat kemajuan diri sendiri.
 * Aplikasi ini sebelumnya hanya melayani autonomy, yaitu pendaki menentukan tujuannya
 * sendiri, sehingga tidak ada satu pun alasan membukanya di antara dua pendakian.
 *
 * Tidak ada perbandingan dengan pendaki lain, dan itu keputusan rancangan, bukan
 * kekurangan. Papan peringkat kecepatan mendorong mengejar waktu di medan yang
 * mematikan. Angka di sini hanya milik pemiliknya.
 *
 * Sejak W7 ringkasan tahun, grafik, dan riwayat milik App\Services\Community\HubProgres; yang
 * tersisa di sini dampak laporan dan peta gunung yang sudah didaki, untuk semua tahun.
 */
class HikerProgressService
{
    /**
     * Dampak laporan kondisi yang ditulis pendaki ini.
     *
     * Tanpa ini, menulis laporan terasa seperti mengisi formulir lalu menekan kirim:
     * pelapor tidak pernah tahu laporannya terbit, dibaca, atau menolong siapa pun, dan
     * perilaku yang tidak pernah mendapat umpan balik berhenti dengan sendirinya.
     *
     * Hanya laporan yang lolos moderasi yang terhitung. Laporan yang masih menunggu
     * belum terlihat siapa pun, dan menghitungnya sebagai terbit membuat pelapor
     * mengira sesuatu sudah menolong orang padahal belum.
     *
     * @return array{laporan_terbit: int, terima_kasih: int}
     */
    public function dampakLaporan(User $user): array
    {
        $terbit = TrailConditionReport::query()
            ->where('user_id', $user->id)
            ->visibleToPublic();

        return [
            'laporan_terbit' => (clone $terbit)->count(),
            'terima_kasih' => (int) (clone $terbit)->withCount('thanks')->get()->sum('thanks_count'),
        ];
    }

    /**
     * Gunung yang sudah didaki, untuk digambar di peta, dari pendakian yang tuntas di semua
     * tahun. Hanya gunung yang berkoordinat yang digambar; yang tidak berkoordinat dihitung,
     * supaya peta dapat menyebut berapa yang belum dapat digambar alih-alih menempatkannya di
     * tengah laut.
     *
     * @return array{penanda: array<int, array{lng: float, lat: float, label: string}>, tanpaKoordinat: int}
     */
    public function petaGunung(User $user): array
    {
        $gunung = HikingHistory::query()
            ->where('user_id', $user->id)
            ->where('completion_state', CompletionState::COMPLETED->value)
            ->whereNotNull('trail_id')
            ->with('trail.mountain:id,name,latitude,longitude')
            ->get()
            ->map(fn (HikingHistory $h) => $h->trail?->mountain)
            ->filter()
            ->unique('id');

        $berkoordinat = $gunung->filter(fn (Mountain $g) => $g->latitude !== null && $g->longitude !== null);

        return [
            'penanda' => $berkoordinat
                ->map(fn (Mountain $g) => ['lng' => (float) $g->longitude, 'lat' => (float) $g->latitude, 'label' => $g->name])
                ->values()
                ->all(),
            'tanpaKoordinat' => $gunung->count() - $berkoordinat->count(),
        ];
    }
}
```

`resources/js/components/community/types.ts`:

1. Ganti:

```ts
}

/** Satu batang grafik: tanjakan jalur pada satu pendakian yang tuntas. m null berarti belum dicatat, bukan nol. */
export interface TitikTanjakan {
    tanggal: string;
    jalur: string;
    m: number | null;
    teks: string;
}

export interface GarisGeoJson {
```

dengan:

```ts
}

export interface GarisGeoJson {
```

Tulis ulang `resources/js/pages/Community/HikerProgress.tsx` utuh:

```tsx
import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { PetaGaris } from '@/components/community/PetaGaris';
import { GrafikPendakian } from '@/components/progres/GrafikPendakian';
import { RingkasanTahun } from '@/components/progres/RingkasanTahun';
import { RiwayatProgres } from '@/components/progres/RiwayatProgres';
import type { ButirRiwayat, FormulirCatatanData, GrafikPendakianData, KabarProgres, RingkasanTahun as Ringkasan } from '@/components/progres/types';
import type { JalurKatalog } from '@/components/trails/PemilihJalur';
import type { Penanda } from '@/components/trails/types';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { PageHeader } from '@/components/ui/PageHeader';
import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';
import { AppLayout } from '@/layouts/AppLayout';
import { progress } from '@/routes';
import { create as reportsCreate } from '@/routes/reports';
import { index as trailsIndex } from '@/routes/trails';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    kosong: boolean;
    tahun: { dipilih: number; semua: number[] };
    ringkasan: Ringkasan | null;
    grafik: GrafikPendakianData | null;
    riwayat: ButirRiwayat[];
    riwayatTerpotong: string | null;
    laporan: { terbit: number; terimaKasih: number };
    gunung: Penanda[];
    keteranganPeta: string;
    gunungTanpaKoordinat: string | null;
    formulirCatatan: FormulirCatatanData;
    kabar: KabarProgres | null;
    /** Hasil pencarian jalur katalog, hanya lewat muat ulang parsial; dibaca PemilihJalur (useCariJalur). */
    pilihanJalur?: JalurKatalog[];
}

/**
 * Progres (spec v3 bagian 6.14, prototype 13): satu tahun dibandingkan dengan diri sendiri
 * (R-015). Urutannya mengikuti pertanyaan "Bagaimana perkembangan saya?": ringkasan tahun,
 * grafik per pendakian, riwayat beserta catatan di luar trip, lalu dampak laporan dan peta
 * gunung yang sudah didaki. Tidak ada papan peringkat, catatan kecepatan, atau teguran.
 */
export default function HikerProgress() {
    const { kosong, tahun, ringkasan, grafik, riwayat, riwayatTerpotong, laporan, gunung, keteranganPeta, gunungTanpaKoordinat, formulirCatatan, kabar } = usePage<Props>().props;
    // Pilihan tahun hanya bila ada lebih dari satu tahun yang dapat dibuka.
    const daftarTahun = tahun.semua.length > 1 ? tahun.semua : [];

    return (
        <div className="py-6 sm:py-8">
            <div className="mx-auto max-w-3xl space-y-8 px-4 sm:px-6 lg:px-8">
                <PageHeader akar title="Progres" description="Dibandingkan dengan diri Anda sendiri. Tidak ada papan peringkat dan tidak ada catatan kecepatan.">
                    {daftarTahun.length > 0 ? (
                        <nav aria-label="Tahun" className="mt-4">
                            <ul className="flex flex-wrap gap-2">
                                {daftarTahun.map((t) => (
                                    <li key={t}>
                                        <Tautan
                                            href={progress.url({ query: { tahun: t } })}
                                            aria-current={t === tahun.dipilih ? 'page' : undefined}
                                            className={kelas(
                                                'inline-flex min-h-11 min-w-11 items-center justify-center rounded-full border px-4 text-kecil tabular-nums focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600',
                                                t === tahun.dipilih ? 'border-brand-700 bg-brand-100 font-bold text-brand-900' : 'border-control bg-surface font-semibold text-primary hover:bg-surface-sunken',
                                            )}
                                        >
                                            {t}
                                        </Tautan>
                                    </li>
                                ))}
                            </ul>
                        </nav>
                    ) : null}
                </PageHeader>

                {kosong ? (
                    // Keadaan awal setiap pendaki: belum ada yang tercatat berarti belum mulai, bukan nilai rendah.
                    <EmptyState
                        jenis="pertama"
                        title="Belum ada pendakian yang tercatat"
                        description="Halaman ini terisi sendiri setelah Anda menyelesaikan pendakian lewat trip. Pendakian yang sudah pernah Anda lakukan di luar Memount dapat dicatat di bagian riwayat di bawah."
                    >
                        <Button variant="secondary" href={trailsIndex.url()}>
                            Telusuri jalur
                        </Button>
                    </EmptyState>
                ) : ringkasan ? (
                    <RingkasanTahun ringkasan={ringkasan} />
                ) : (
                    <p className="text-isi text-secondary">Belum ada pendakian tercatat di {tahun.dipilih}.</p>
                )}

                {grafik ? (
                    <GrafikPendakian grafik={grafik} />
                ) : riwayat.length === 1 ? (
                    <p className="max-w-prose rounded-kartu border border-dashed border-control bg-surface p-4 text-kecil text-secondary">
                        Grafik per pendakian muncul setelah ada dua pendakian di {tahun.dipilih}, karena tiap pendakian dibandingkan dengan pendakian Anda sendiri, bukan dengan pendaki lain.
                    </p>
                ) : null}

                <RiwayatProgres tahun={tahun.dipilih} riwayat={riwayat} terpotong={riwayatTerpotong} formulir={formulirCatatan} kabar={kabar} />

                {/* Dikalimatkan sebagai dampak pada orang lain, bukan sebagai skor. */}
                <section aria-labelledby="progres-laporan">
                    <h2 id="progres-laporan" className="text-bagian text-primary">
                        Laporan kondisi
                    </h2>
                    {laporan.terbit === 0 ? (
                        <>
                            <p className="mt-2 max-w-prose text-isi text-secondary">
                                Belum ada laporan kondisi dari Anda. Setelah turun, keterangan tentang jalur yang baru Anda lalui adalah hal yang paling dibutuhkan pendaki
                                berikutnya.
                            </p>
                            <Tautan href={reportsCreate.url()} className="mt-1 inline-flex min-h-11 items-center text-kecil font-bold text-brand-ink underline">
                                Tulis laporan kondisi
                            </Tautan>
                        </>
                    ) : (
                        <>
                            <p className="mt-2 text-isi text-secondary">
                                <span data-angka className="font-bold text-primary">
                                    {laporan.terbit}
                                </span>{' '}
                                laporan Anda sudah terbit dan dapat dibaca pendaki lain.
                            </p>
                            {laporan.terimaKasih > 0 ? <p className="mt-1 text-isi text-brand-900">{laporan.terimaKasih} pendaki menyatakan laporan Anda menolong mereka.</p> : null}
                        </>
                    )}
                </section>

                {gunung.length > 0 ? (
                    <section aria-labelledby="progres-peta">
                        <h2 id="progres-peta" className="text-bagian text-primary">
                            Gunung yang sudah Anda daki
                        </h2>
                        <figure className="mt-3">
                            <PetaGaris id="peta-progres" markers={gunung} label="Peta gunung yang sudah Anda daki" height="h-72 sm:h-[26rem]" />
                            <figcaption className="mt-3 text-kecil text-secondary">
                                {keteranganPeta}
                                {gunungTanpaKoordinat ? <span className="text-muted"> {gunungTanpaKoordinat}</span> : null}
                            </figcaption>
                        </figure>
                    </section>
                ) : null}
            </div>
        </div>
    );
}

HikerProgress.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
```

`tests/Pratinjau/HalamanPratinjauTest.php`:

1. Ganti:

```php
    }

    public function test_progres(): void
    {
        $this->sebagaiBudi();

        $this->tulisPratinjau('progres', $this->get(self::ASAL.'/progres'), 'Community/HikerProgress');
    }
```

dengan:

```php
    }

    /**
     * Progres W7 (acuan 13): riwayat Budi dari DemoSeeder (Andong, Batur, dan Prau tuntas;
     * Pangrango dibatalkan, di sini karena cuaca) ditambah satu catatan pendakian di luar trip di
     * Papandayan dengan angka yang ditulis Budi sendiri.
     */
    public function test_progres(): void
    {
        $budi = $this->sebagaiBudi();
        TripPlan::where('user_id', $budi->id)->where('status', TripStatus::CANCELLED->value)->update(['cancellation_reason' => CancellationReason::WEATHER->value]);
        HikingHistory::factory()->manual()->for($budi)->create([
            'trail_id' => Trail::where('slug', 'papandayan-via-camp-david')->value('id'),
            'trip_type' => 'TEKTOK',
            'recorded_gain_m' => 610,
            'recorded_distance_m' => 14800,
            'recorded_moving_minutes' => 330,
            'completed_at' => now('Asia/Jakarta')->subDays(150)->setTime(12, 0)->utc(),
        ]);

        $this->tulisPratinjau('progres', $this->get(self::ASAL.'/progres'), 'Community/HikerProgress');
    }

    /**
     * Progres pendaki baru tanpa catatan apa pun: keadaan pertama kali.
     */
    public function test_progres_kosong(): void
    {
        $this->seed(DemoSeeder::class);
        $this->actingAs(User::factory()->create(['name' => 'Budi Setiawan']));

        $this->tulisPratinjau('progres-kosong', $this->get(self::ASAL.'/progres'), 'Community/HikerProgress');
    }
```

Hapus berkas `resources/js/components/community/GrafikTanjakan.tsx`.

- [ ] **Step 4: Build dan tipe.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `npx tsc --noEmit`
Expected: `✓ built`; tsc tanpa keluaran galat.

- [ ] **Step 5: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Services/Community/HubProgres.php app/Services/Trails/CariJalur.php app/Http/Controllers/Community/HikerProgressController.php app/Services/HikerProgressService.php tests/Feature/HikerProgressTest.php tests/Feature/ProgressHierarchyTest.php tests/Feature/ReporterImpactTest.php tests/Pratinjau/HalamanPratinjauTest.php`, lalu `php artisan test --compact --filter='HikerProgress|ProgressHierarchy|ReporterImpact|EmptyStateCoverage|KeyboardAndScreenReader|TapTarget|NoDeadViewData|PageQueryBudget'`
Expected: PASS. Pada simulasi rencana: 53 test, 53 lulus.

- [ ] **Step 6: Suite, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 2.073 test, 2.071 lulus, 2 dilewati (sebelum tugas: 2.071).

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `php artisan test --compact --testsuite=Pratinjau`
Expected: PASS; halaman `progres` kini hub tahunan. Pada simulasi rencana: 30 test lulus.

- [ ] **Step 7: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 3: Target pribadi bulanan

FR-25 (PRD §150), spec v3 bagian 6.14, induk W7 butir 2; ruling W7-R2 dan W7-R7. Satu set target tanpa id untuk
pendaki yang masuk: tanjakan per bulan (m), jarak per bulan (km), dan jumlah pendakian per bulan. Isian kosong mencopot
targetnya. Migrasi aditif `2026_10_08_000200` mengisi rintisan `2026_09_25_000920` (pemilik, ukuran, nilai bulanan, unik
per ukuran, RLS). Progres menulis capaian bulan berjalan dari pendakian sendiri sebagai angka dan kalimat, dengan bilah
yang panjangnya tidak pernah ditulis sebagai persen; target jumlah pendakian memakai strip dua belas bulan, dan
pembatalan tetap dihitung. Target tidak mengubah label kecocokan dan tidak menaikkan tingkat (BR-11).

**Files:**
- Create: `app/Enums/PersonalTargetMetric.php`, `app/Http/Controllers/Community/PersonalTargetController.php`, `app/Http/Requests/Community/UpdatePersonalTargetsRequest.php`, `app/Models/PersonalTarget.php`, `database/factories/PersonalTargetFactory.php`, `database/migrations/2026_10_08_000200_complete_personal_targets_table.php`, `resources/js/components/progres/TargetPribadi.tsx`
- Modify: `app/Models/User.php`, `app/Services/Community/HubProgres.php`, `resources/js/components/progres/types.ts`, `resources/js/pages/Community/HikerProgress.tsx`, `routes/web.php`
- Test: `tests/Feature/PersonalTargetTest.php` (rintisan ditulis ulang utuh), `tests/Feature/ProgressHierarchyTest.php`, `tests/Pratinjau/HalamanPratinjauTest.php`, `tests/Spatial/PersonalTargetSchemaTest.php` (baru, PostgreSQL)

**Interfaces:**
- Consumes: `HubProgres` dan `HubProgres::angka()` (Tugas 2); flash `kabar-progres`; primitif `LembarBawah`, `TextInput`, `InputError`, `Button`.
- Produces:
  - enum `App\Enums\PersonalTargetMetric` (`GAIN`, `DISTANCE`, `HIKES`; `label()`, `satuan()`, `isian()` = `tanjakan_m`, `jarak_km`, `pendakian`; `maksimum()` = 20.000, 1.000, 31);
  - model `PersonalTarget` (`user_id`, `metric`, `monthly_value`), `User::personalTargets(): HasMany`, pabrik `PersonalTargetFactory`;
  - tabel `personal_targets` dengan unik `personal_targets_unik` (`user_id`, `metric`), kunci asing cascade, RLS di PostgreSQL;
  - rute `progress.targets.update` (PUT `progres/target`, berbatas laju `target-pribadi`); `UpdatePersonalTargetsRequest::nilai(): array<string, int|null>` (kunci nilai `PersonalTargetMetric`); flash `kabar-progres` `{bagian: 'target', teks: 'Target pribadi disimpan.'}`;
  - kunci `target` di `HubProgres::untuk()` (hanya tahun berjalan) dan TypeScript `BulanTarget`, `ButirTarget`, `IsianTarget`, `TargetPribadiData`; komponen `TargetPribadi`.

- [ ] **Step 1: Tulis test yang gagal.** `PersonalTargetTest.php` sudah ada sebagai rintisan; ganti isinya utuh. Test
Spatial dibuat langsung di `tests/Spatial/`.

Buat `tests/Spatial/PersonalTargetSchemaTest.php`:

```php
<?php

namespace Tests\Spatial;

use App\Models\PersonalTarget;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Tabel target pribadi di PostgreSQL (R-024, ruling W7-R2): kolom yang diisi migrasi aditif di
 * atas rintisan 2026_09_25_000920, kunci unik per pendaki per ukuran, kunci asing yang ikut
 * terhapus bersama akun, dan RLS yang menutup REST API. Suite SQLite tidak membuktikan satu pun
 * dari itu. Migrasinya tidak dijalankan di Supabase sebelum izin pemilik (spec v3 Q2).
 */
class PersonalTargetSchemaTest extends SpatialTestCase
{
    public function test_the_table_has_its_production_shape(): void
    {
        $kolom = collect(DB::select(
            "SELECT column_name, data_type, is_nullable, character_maximum_length
             FROM information_schema.columns
             WHERE table_schema = current_schema() AND table_name = 'personal_targets'"
        ))->keyBy('column_name');

        $this->assertSame(['bigint', 'NO'], [$kolom['user_id']->data_type, $kolom['user_id']->is_nullable]);
        $this->assertSame(['character varying', 'NO', 20], [$kolom['metric']->data_type, $kolom['metric']->is_nullable, (int) $kolom['metric']->character_maximum_length]);
        $this->assertSame(['integer', 'NO'], [$kolom['monthly_value']->data_type, $kolom['monthly_value']->is_nullable]);

        $this->assertTrue((bool) DB::selectOne("SELECT relrowsecurity FROM pg_class WHERE relname = 'personal_targets'")->relrowsecurity, 'RLS lapis kedua harus menyala.');
        $this->assertSame('c', DB::selectOne("SELECT confdeltype FROM pg_constraint WHERE conrelid = 'personal_targets'::regclass AND contype = 'f'")->confdeltype, 'Target ikut terhapus bersama akunnya.');
    }

    public function test_one_target_per_hiker_per_metric(): void
    {
        $user = User::factory()->create();
        PersonalTarget::factory()->for($user)->create(['metric' => 'GAIN']);

        $this->expectException(UniqueConstraintViolationException::class);
        PersonalTarget::factory()->for($user)->create(['metric' => 'GAIN']);
    }
}
```

Tulis ulang `tests/Feature/PersonalTargetTest.php` utuh:

```php
<?php

namespace Tests\Feature;

use App\Enums\CancellationReason;
use App\Enums\CompletionState;
use App\Enums\ExperienceLevel;
use App\Enums\NavigationExperience;
use App\Enums\PersonalTargetMetric;
use App\Enums\TripStatus;
use App\Models\HikingHistory;
use App\Models\PersonalTarget;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\TrailFitService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Target pribadi bulanan (FR-25, PRD §150; rencana induk W7 butir 2; ruling W7-R7).
 *
 * Menggantikan test rintisan yang hanya membuka `/` (spec v3 bagian 3.5). Target dipasang
 * sebagai satu set milik pendaki yang masuk, dibandingkan hanya dengan pendakiannya sendiri
 * pada bulan berjalan (R-015), tanpa persen, rentetan, atau teguran, dan tidak mengubah label
 * kecocokan maupun tingkat pengalaman (BR-11).
 */
class PersonalTargetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-15 09:00:00', 'Asia/Jakarta'));
    }

    /**
     * @param  array<string, string>  $ubah
     * @return array<string, string>
     */
    private function isian(array $ubah = []): array
    {
        return ['tanjakan_m' => '1000', 'jarak_km' => '', 'pendakian' => '1', ...$ubah];
    }

    private function pendakian(User $user, int $naik, string $tanggal, ?float $km = 9.7): HikingHistory
    {
        $jalur = Trail::factory()->easy()->create(['name' => 'Jalur '.$tanggal, 'elevation_gain_m' => $naik, 'distance_km' => $km]);
        $trip = TripPlan::factory()->create(['user_id' => $user->id, 'trail_id' => $jalur->id, 'planned_date' => $tanggal, 'status' => TripStatus::COMPLETED->value]);

        return HikingHistory::create([
            'user_id' => $user->id,
            'trip_plan_id' => $trip->id,
            'trail_id' => $jalur->id,
            'trip_type' => $trip->trip_type->value,
            'completion_state' => CompletionState::COMPLETED->value,
            'completed_at' => Carbon::parse($tanggal.' 16:00:00', 'Asia/Jakarta')->utc(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function target(User $user, array $query = []): ?array
    {
        return $this->actingAs($user)->get(route('progress', $query))->assertOk()->viewData('page')['props']['target'];
    }

    public function test_a_hiker_sets_monthly_targets_as_one_set(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('progress.targets.update'), $this->isian())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('progress'))
            ->assertSessionHas('kabar-progres', ['bagian' => 'target', 'teks' => 'Target pribadi disimpan.']);

        $this->assertEqualsCanonicalizing(
            [['GAIN', 1000], ['HIKES', 1]],
            $user->personalTargets()->get()->map(fn (PersonalTarget $t) => [$t->metric->value, $t->monthly_value])->all(),
        );

        $this->actingAs($user)->put(route('progress.targets.update'), $this->isian(['tanjakan_m' => '', 'jarak_km' => '40']))->assertSessionHasNoErrors();

        $this->assertEqualsCanonicalizing(
            [['DISTANCE', 40], ['HIKES', 1]],
            $user->personalTargets()->get()->map(fn (PersonalTarget $t) => [$t->metric->value, $t->monthly_value])->all(),
            'Isian yang dikosongkan mencopot targetnya; yang lain diperbarui di barisnya sendiri.',
        );
    }

    public function test_values_outside_their_ranges_and_missing_fields_are_refused(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('progress.targets.update'), ['tanjakan_m' => '0', 'jarak_km' => '1001', 'pendakian' => '2,5'])
            ->assertSessionHasErrors([
                'tanjakan_m' => 'Target tanjakan diisi dalam meter per bulan, 1 sampai 20.000, atau dikosongkan.',
                'jarak_km' => 'Target jarak diisi dalam kilometer bulat per bulan, 1 sampai 1.000, atau dikosongkan.',
                'pendakian' => 'Target pendakian diisi jumlah per bulan, 1 sampai 31, atau dikosongkan.',
            ]);

        $this->actingAs($user)->put(route('progress.targets.update'), ['tanjakan_m' => '1000'])->assertSessionHasErrors(['jarak_km', 'pendakian']);

        $this->assertSame(0, PersonalTarget::count());
    }

    /**
     * Capaian bulan berjalan dari pendakian pendaki ini sendiri; yang tercapai disebut
     * tercapai, dan pembatalan ikut terhitung pada jumlah pendakian (prototype 13).
     */
    public function test_this_months_progress_is_measured_against_the_hikers_own_hikes(): void
    {
        $user = User::factory()->create();
        $this->pendakian($user, 1660, '2026-10-04');
        TripPlan::factory()->create(['user_id' => $user->id, 'planned_date' => '2026-09-13', 'status' => TripStatus::CANCELLED->value, 'cancellation_reason' => CancellationReason::WEATHER->value, 'trail_id' => Trail::factory()->easy()->create(['name' => 'Sindoro via Kledung'])->id]);
        $user->personalTargets()->createMany([['metric' => 'GAIN', 'monthly_value' => 1000], ['metric' => 'HIKES', 'monthly_value' => 1]]);

        $target = $this->target($user);
        [$naik, $jumlah] = $target['daftar'];

        $this->assertSame(['GAIN', '1.660', '1.000 m', true, 'Oktober: tercapai', 1.0], [$naik['metrik'], $naik['capaian'], $naik['sasaran'], $naik['tercapai'], $naik['kalimat'], $naik['rasio']]);
        $this->assertSame(['HIKES', '1', '1', true, 'Oktober: tercapai'], [$jumlah['metrik'], $jumlah['capaian'], $jumlah['sasaran'], $jumlah['tercapai'], $jumlah['kalimat']]);
        $this->assertSame(
            ['kosong', 'kosong', 'kosong', 'kosong', 'kosong', 'kosong', 'kosong', 'kosong', 'batal', 'ada', 'nanti', 'nanti'],
            array_column($jumlah['bulan'], 'keadaan'),
        );
        $this->assertSame('September: 1 pendakian, 1 dibatalkan', $jumlah['bulan'][8]['nama'].': '.$jumlah['bulan'][8]['teks']);
        $this->assertSame('Januari sampai Desember 2026. September berisi Sindoro via Kledung yang dibatalkan karena cuaca, dan tetap dihitung.', $jumlah['keterangan']);
        $this->assertSame('Target tidak mengubah label kecocokan dan tidak menaikkan tingkat pengalaman Anda.', $target['aturan']);
    }

    /**
     * Target yang belum tercapai disebut angkanya, tanpa kata yang menghukum; angka yang belum
     * diketahui disebut, tidak dianggap nol.
     */
    public function test_an_unmet_target_is_stated_plainly_and_unknown_numbers_are_named(): void
    {
        $user = User::factory()->create();
        $this->pendakian($user, 600, '2026-10-04', km: null);
        $this->pendakian($user, 0, '2026-10-11')->trail->update(['elevation_gain_m' => null]);
        $user->personalTargets()->createMany([['metric' => 'GAIN', 'monthly_value' => 1000], ['metric' => 'DISTANCE', 'monthly_value' => 40]]);

        [$naik, $jarak] = $this->target($user)['daftar'];

        $this->assertSame('Oktober: 600 dari 1.000 m', $naik['kalimat']);
        $this->assertFalse($naik['tercapai']);
        $this->assertSame(0.6, $naik['rasio']);
        $this->assertSame('1 pendakian bulan ini belum punya angka tanjakan, jadi belum ikut dihitung.', $naik['catatan']);
        $this->assertSame('Oktober: 19,4 dari 40 km', $jarak['kalimat']);
        $this->assertSame('1 pendakian bulan ini belum punya angka jarak, jadi belum ikut dihitung.', $jarak['catatan']);

        foreach (['gagal', 'kurang', 'ayo', 'tertinggal', 'streak', '%'] as $kata) {
            $this->assertStringNotContainsStringIgnoringCase($kata, json_encode([$naik['kalimat'], $jarak['kalimat']], JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * BR-11 dan PRD §150: memasang target tidak mengubah profil maupun label kecocokan jalur.
     */
    public function test_targets_change_neither_the_profile_nor_fit_labels(): void
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::BEGINNER->value, 'completed_at' => now()]);
        $user->experience()->create(['completed_hikes_count' => 2, 'terrain_experience' => ['FOREST'], 'navigation_experience' => NavigationExperience::BASIC->value, 'highest_elevation_gain_m' => 700]);
        $user->preference()->create(['preferred_duration' => 'ONE_DAY', 'preferred_trip_type' => 'TEKTOK']);
        $jalur = Trail::factory()->published()->easy()->create(['elevation_gain_m' => 1600]);
        $label = fn () => app(TrailFitService::class)->forTrails($user->fresh(), new Collection([$jalur->fresh()]))[$jalur->id]->label;
        $sebelum = $label();

        $this->actingAs($user)->put(route('progress.targets.update'), $this->isian(['tanjakan_m' => '3000', 'pendakian' => '4']))->assertSessionHasNoErrors();

        $this->assertSame($sebelum, $label());
        $this->assertSame(ExperienceLevel::BEGINNER, $user->fresh()->profile->experience_level);
        $this->assertSame(700, $user->fresh()->experience->highest_elevation_gain_m);
    }

    /**
     * Pendakian orang lain tidak pernah dihitung, dan pemilik dari isian diabaikan: target orang
     * lain tidak tersentuh.
     */
    public function test_other_hikers_neither_count_nor_get_touched(): void
    {
        $saya = User::factory()->create();
        $orangLain = User::factory()->create();
        $orangLain->personalTargets()->create(['metric' => 'GAIN', 'monthly_value' => 500]);
        $this->pendakian($orangLain, 2000, '2026-10-05');

        $this->actingAs($saya)->put(route('progress.targets.update'), $this->isian(['user_id' => (string) $orangLain->id]))->assertSessionHasNoErrors();

        $this->assertSame(500, $orangLain->personalTargets()->sole()->monthly_value);
        $this->assertSame('0', $this->target($saya)['daftar'][0]['capaian']);
    }

    public function test_targets_appear_only_in_the_current_year(): void
    {
        $user = User::factory()->create();
        $this->pendakian($user, 900, '2025-06-01');
        $user->personalTargets()->create(['metric' => 'HIKES', 'monthly_value' => 1]);

        $this->assertNotNull($this->target($user));
        $this->assertNull($this->target($user, ['tahun' => 2025]));
    }

    public function test_a_guest_is_sent_to_sign_in(): void
    {
        $this->put(route('progress.targets.update'), $this->isian())->assertRedirect(route('login'));

        $this->assertSame(0, PersonalTarget::count());
    }

    public function test_setting_targets_is_rate_limited(): void
    {
        $batas = (int) config('hiking.rate_limits.personal_writes_per_minute');
        $user = User::factory()->create();

        foreach (range(1, $batas) as $i) {
            $this->actingAs($user)->put(route('progress.targets.update'), $this->isian(['tanjakan_m' => (string) (100 + $i)]))->assertRedirect();
        }

        $this->actingAs($user)->put(route('progress.targets.update'), $this->isian(['tanjakan_m' => '9999']))->assertTooManyRequests();
        $this->assertSame(100 + $batas, $user->personalTargets()->where('metric', PersonalTargetMetric::GAIN->value)->sole()->monthly_value);
    }
}
```

`tests/Feature/ProgressHierarchyTest.php`:

1. Ganti:

```php
        $sumber = $this->halaman();
        $urutan = array_map(fn (string $tag) => strpos($sumber, $tag), ['<RingkasanTahun', '<GrafikPendakian', '<RiwayatProgres', 'id="progres-laporan"', '<PetaGaris']);

        $this->assertNotContains(false, $urutan, 'Setiap bagian Progres harus ada.');
        $this->assertSame($urutan, array_values(collect($urutan)->sort()->all()), 'Ringkasan tahun, grafik, riwayat, laporan, lalu peta.');
    }
```

dengan:

```php
        $sumber = $this->halaman();
        $urutan = array_map(fn (string $tag) => strpos($sumber, $tag), ['<RingkasanTahun', '<GrafikPendakian', '<TargetPribadi', '<RiwayatProgres', 'id="progres-laporan"', '<PetaGaris']);

        $this->assertNotContains(false, $urutan, 'Setiap bagian Progres harus ada.');
        $this->assertSame($urutan, array_values(collect($urutan)->sort()->all()), 'Ringkasan tahun, grafik, target, riwayat, laporan, lalu peta (prototype 13).');
    }
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='PersonalTarget|ProgressHierarchy|HikerProgress'`
Expected: FAIL. Pada simulasi rencana: 29 test, 17 lulus, 1 gagal, 9 error, 2 dilewati. Yang merah: kesembilan test `PersonalTargetTest` (rute `progress.targets.update` dan relasi `personalTargets()` belum ada) dan `ProgressHierarchyTest::test_the_year_summary_leads_and_the_map_follows_the_history` (bagian Target belum ada). 2 test Spatial melewati dirinya tanpa `SPATIAL_TEST_DSN`.

- [ ] **Step 3: Tulis implementasinya.** Buat berkas dengan
`DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:enum --no-interaction PersonalTargetMetric`,
`... make:model --no-interaction PersonalTarget --factory`,
`... make:controller --no-interaction Community/PersonalTargetController`,
`... make:request --no-interaction Community/UpdatePersonalTargetsRequest`, dan
`... make:migration --no-interaction complete_personal_targets_table` (ganti nama berkasnya menjadi
`2026_10_08_000200_complete_personal_targets_table.php`), lalu isinya diganti persis. Rintisan
`2026_09_25_000920_create_personal_targets_table.php` tidak disunting.

Buat `app/Enums/PersonalTargetMetric.php`:

```php
<?php

namespace App\Enums;

/**
 * Ukuran target pribadi bulanan (FR-25, PRD §150): tanjakan dalam meter, jarak dalam kilometer,
 * atau jumlah pendakian. Setiap ukuran dibandingkan hanya dengan pendakian pendaki itu sendiri
 * pada bulan berjalan (R-015).
 */
enum PersonalTargetMetric: string
{
    case GAIN = 'GAIN';
    case DISTANCE = 'DISTANCE';
    case HIKES = 'HIKES';

    public function label(): string
    {
        return match ($this) {
            self::GAIN => 'Tanjakan per bulan',
            self::DISTANCE => 'Jarak per bulan',
            self::HIKES => 'Pendakian per bulan',
        };
    }

    /** Satuan yang ditulis di belakang angka; pendakian ditulis tanpa satuan. */
    public function satuan(): string
    {
        return match ($this) {
            self::GAIN => 'm',
            self::DISTANCE => 'km',
            self::HIKES => '',
        };
    }

    /** Nama isian formulir target. */
    public function isian(): string
    {
        return match ($this) {
            self::GAIN => 'tanjakan_m',
            self::DISTANCE => 'jarak_km',
            self::HIKES => 'pendakian',
        };
    }

    /** Batas atas yang masuk akal untuk satu bulan, bukan penilaian. */
    public function maksimum(): int
    {
        return match ($this) {
            self::GAIN => 20000,
            self::DISTANCE => 1000,
            self::HIKES => 31,
        };
    }
}
```

Buat `app/Http/Controllers/Community/PersonalTargetController.php`:

```php
<?php

namespace App\Http\Controllers\Community;

use App\Enums\PersonalTargetMetric;
use App\Http\Controllers\Controller;
use App\Http\Requests\Community\UpdatePersonalTargetsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * Target pribadi bulanan (FR-25, PRD §150; ruling W7-R7). Satu set untuk pendaki yang masuk:
 * tidak ada id target di rute maupun isian, jadi target orang lain tidak dapat dirujuk sama
 * sekali. Target tidak menyentuh profil, tingkat pengalaman, maupun penilaian kecocokan (BR-11).
 */
class PersonalTargetController extends Controller
{
    public function update(UpdatePersonalTargetsRequest $request): RedirectResponse
    {
        $pengguna = $request->user();

        DB::transaction(function () use ($pengguna, $request) {
            foreach ($request->nilai() as $ukuran => $nilai) {
                if ($nilai === null) {
                    $pengguna->personalTargets()->where('metric', $ukuran)->delete();

                    continue;
                }

                $pengguna->personalTargets()->updateOrCreate(['metric' => PersonalTargetMetric::from($ukuran)->value], ['monthly_value' => $nilai]);
            }
        });

        return redirect()->route('progress')->with('kabar-progres', ['bagian' => 'target', 'teks' => 'Target pribadi disimpan.']);
    }
}
```

Buat `app/Http/Requests/Community/UpdatePersonalTargetsRequest.php`:

```php
<?php

namespace App\Http\Requests\Community;

use App\Enums\PersonalTargetMetric;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Target pribadi bulanan (FR-25, PRD §150; ruling W7-R7), dikirim sebagai satu set: setiap
 * ukuran wajib hadir, angka memasang targetnya, dan kosong mencopotnya. Isian yang tidak dikirim
 * sama sekali bukan perintah mencopot, jadi ditolak. Pemilik selalu dari sesi; tidak ada id
 * target atau pengguna yang dibaca dari isian.
 */
class UpdatePersonalTargetsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $aturan = [];

        foreach (PersonalTargetMetric::cases() as $ukuran) {
            $aturan[$ukuran->isian()] = ['present', 'nullable', 'integer', 'min:1', 'max:'.$ukuran->maksimum()];
        }

        return $aturan;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tanjakan_m' => 'Target tanjakan diisi dalam meter per bulan, 1 sampai 20.000, atau dikosongkan.',
            'jarak_km' => 'Target jarak diisi dalam kilometer bulat per bulan, 1 sampai 1.000, atau dikosongkan.',
            'pendakian' => 'Target pendakian diisi jumlah per bulan, 1 sampai 31, atau dikosongkan.',
            '*.present' => 'Isian target tidak terkirim lengkap. Muat ulang halaman lalu coba lagi.',
        ];
    }

    /**
     * Nilai target per ukuran; null berarti dicopot.
     *
     * @return array<string, int|null>
     */
    public function nilai(): array
    {
        $nilai = [];

        foreach (PersonalTargetMetric::cases() as $ukuran) {
            $isian = $this->validated($ukuran->isian());
            $nilai[$ukuran->value] = $isian === null ? null : (int) $isian;
        }

        return $nilai;
    }

    protected function prepareForValidation(): void
    {
        $kosong = fn (mixed $v): mixed => is_string($v) && trim($v) === '' ? null : $v;
        $isian = [];

        foreach (PersonalTargetMetric::cases() as $ukuran) {
            if ($this->exists($ukuran->isian())) {
                $isian[$ukuran->isian()] = $kosong($this->input($ukuran->isian()));
            }
        }

        $this->merge($isian);
    }
}
```

Buat `app/Models/PersonalTarget.php`:

```php
<?php

namespace App\Models;

use App\Enums\PersonalTargetMetric;
use Database\Factories\PersonalTargetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Target pribadi bulanan seorang pendaki (FR-25). Satu baris per ukuran; pemiliknya selalu dari
 * sesi, tidak pernah dari isian (PersonalTargetController).
 */
#[Fillable(['metric', 'monthly_value'])]
class PersonalTarget extends Model
{
    /** @use HasFactory<PersonalTargetFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'metric' => PersonalTargetMetric::class,
            'monthly_value' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

Buat `database/factories/PersonalTargetFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Enums\PersonalTargetMetric;
use App\Models\PersonalTarget;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PersonalTarget>
 */
class PersonalTargetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'metric' => PersonalTargetMetric::GAIN->value,
            'monthly_value' => 1000,
        ];
    }
}
```

Buat `database/migrations/2026_10_08_000200_complete_personal_targets_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Target pribadi bulanan (FR-25, PRD §150; rencana induk W7 butir 2): satu baris per pendaki per
 * ukuran (tanjakan, jarak, atau jumlah pendakian per bulan). Dibandingkan hanya dengan pendakian
 * pendaki itu sendiri (R-015); tidak mengubah label kecocokan dan tidak menaikkan tingkat
 * pengalaman (BR-11). Hapus akun menghapus targetnya (cascade, kebijakan data pribadi bagian 2).
 *
 * Migrasi aditif ini mengisi tabel rintisan 2026_09_25_000920 yang hanya berisi id dan
 * timestamps; rintisan itu sengaja tidak disunting (ruling koordinator W7), jadi berlaku sama
 * baik ia sudah berjalan di Supabase maupun belum. Rintisan tidak pernah ditulis kode apa pun, jadi
 * tabelnya kosong; kolom user_id yang wajib akan gagal bila ternyata ada baris tanpa pemilik,
 * dan pemeriksaannya `SELECT count(*) FROM personal_targets` sebelum migrasi dijalankan.
 *
 * Lapis kedua penutupan PostgREST (2026_09_20_000100): RLS tanpa policy menolak REST API,
 * sedangkan Laravel sebagai pemilik tabel tidak terpengaruh. Rintisannya tidak menyalakannya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_targets', function (Blueprint $table) {
            $table->foreignId('user_id')->after('id')->constrained()->cascadeOnDelete();
            $table->string('metric', 20)->after('user_id');
            $table->unsignedInteger('monthly_value')->after('metric');

            $table->unique(['user_id', 'metric'], 'personal_targets_unik');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE public."personal_targets" ENABLE ROW LEVEL SECURITY');
        }
    }

    public function down(): void
    {
        Schema::table('personal_targets', function (Blueprint $table) {
            $table->dropUnique('personal_targets_unik');
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn(['metric', 'monthly_value']);
        });
    }
};
```

Buat `resources/js/components/progres/TargetPribadi.tsx`:

```tsx
import { useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import { Button } from '@/components/ui/Button';
import { InputError, Label, TextInput } from '@/components/ui/Field';
import { Icon } from '@/components/ui/Icon';
import { LembarBawah } from '@/components/ui/Lembar';
import { kelas } from '@/lib/kelas';
import type { BulanTarget, ButirTarget, IsianTarget, KabarProgres, TargetPribadiData } from './types';

const isianTarget: Array<{ kunci: keyof IsianTarget; label: string }> = [
    { kunci: 'tanjakan_m', label: 'Tanjakan per bulan (m)' },
    { kunci: 'jarak_km', label: 'Jarak per bulan (km)' },
    { kunci: 'pendakian', label: 'Pendakian per bulan' },
];

const kotakBulan: Record<BulanTarget['keadaan'], string> = {
    ada: 'bg-brand-700',
    batal: 'border-2 border-dashed border-brand-700',
    kosong: 'bg-surface-sunken',
    nanti: 'border border-subtle',
};

/**
 * Target pribadi bulanan (FR-25, spec v3 bagian 6.14, prototype 13): capaian bulan berjalan
 * dibanding target yang dipasang pendaki sendiri (R-015). Capaian ditulis sebagai angka "N / M",
 * tidak pernah persen; yang belum tercapai disebut angkanya tanpa teguran, dan tidak ada
 * rentetan. Target tidak mengubah label kecocokan maupun tingkat pengalaman (BR-11). Kabar
 * simpanan dibacakan di wilayah status yang selalu terpasang di bagian ini.
 */
export function TargetPribadi({ target, kabar }: { target: TargetPribadiData; kabar: KabarProgres | null }) {
    const [terbuka, setTerbuka] = useState(false);
    const form = useForm<IsianTarget>(target.awal);

    const buka = () => {
        form.setData(target.awal);
        form.clearErrors();
        setTerbuka(true);
    };

    const kirim = (e: FormEvent) => {
        e.preventDefault();
        form.put(target.ubah, { preserveScroll: true, onSuccess: () => setTerbuka(false) });
    };

    return (
        <section aria-labelledby="judul-target-pribadi">
            <div className="flex items-baseline justify-between gap-3">
                <h2 id="judul-target-pribadi" className="text-bagian text-primary">
                    Target pribadi
                </h2>
                <button
                    type="button"
                    onClick={buka}
                    className="inline-flex min-h-11 items-center px-1 text-kecil font-bold text-brand-ink underline-offset-2 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                >
                    {target.daftar.length > 0 ? 'Ubah target' : 'Pasang target'}
                </button>
            </div>
            <p role="status" className={kabar?.bagian === 'target' ? 'mt-2 rounded-kartu border border-brand-300 bg-brand-100 p-3 text-kecil text-brand-900' : undefined}>
                {kabar?.bagian === 'target' ? kabar.teks : ''}
            </p>

            <div className="mt-3 rounded-kartu border border-subtle bg-surface p-4">
                {target.daftar.length === 0 ? (
                    <p className="text-isi text-secondary">Belum ada target. Target bulanan untuk tanjakan, jarak, atau jumlah pendakian dibandingkan hanya dengan pendakian Anda sendiri.</p>
                ) : (
                    <ul className="space-y-5">
                        {target.daftar.map((t) => (
                            <ButirTargetBulan key={t.metrik} target={t} />
                        ))}
                    </ul>
                )}
                <p className="mt-4 border-t border-subtle pt-3 text-meta text-muted">{target.aturan}</p>
            </div>

            <LembarBawah
                terbuka={terbuka}
                onTutup={() => setTerbuka(false)}
                judul="Target pribadi"
                keterangan={`Per bulan, mulai ${target.bulan}. Kosongkan untuk tidak memasang target itu.`}
            >
                <form onSubmit={kirim} noValidate className="space-y-4">
                    {isianTarget.map((i) => (
                        <div key={i.kunci}>
                            <Label htmlFor={`target-${i.kunci}`}>{i.label}</Label>
                            <TextInput
                                id={`target-${i.kunci}`}
                                inputMode="numeric"
                                value={form.data[i.kunci]}
                                onChange={(e) => form.setData(i.kunci, e.target.value)}
                                aria-invalid={form.errors[i.kunci] ? true : undefined}
                                aria-describedby={form.errors[i.kunci] ? `target-${i.kunci}-galat` : undefined}
                                className="tabular-nums"
                            />
                            <InputError id={`target-${i.kunci}-galat`} messages={form.errors[i.kunci]} className="mt-2" />
                        </div>
                    ))}
                    <Button type="submit" processing={form.processing}>
                        Simpan target
                    </Button>
                </form>
            </LembarBawah>
        </section>
    );
}

function ButirTargetBulan({ target }: { target: ButirTarget }) {
    return (
        <li>
            <p className="flex flex-wrap items-baseline justify-between gap-x-3 text-kecil">
                <span className="font-bold text-primary">{target.judul}</span>
                <span className="tabular-nums text-primary">
                    {target.capaian} / {target.sasaran}
                </span>
            </p>
            {target.bulan ? (
                <ol aria-label="Pendakian per bulan" className="mt-2 flex gap-1">
                    {target.bulan.map((b) => (
                        <li key={b.nama} className="flex min-w-0 flex-1 flex-col items-center gap-1">
                            <span aria-hidden="true" className={kelas('block h-2.5 w-full rounded-lencana', kotakBulan[b.keadaan])} />
                            <span aria-hidden="true" className="text-label text-muted">
                                {b.pendek.charAt(0)}
                            </span>
                            <span className="sr-only">
                                {b.nama}: {b.teks}
                            </span>
                        </li>
                    ))}
                </ol>
            ) : (
                <span aria-hidden="true" className="mt-2 block h-2.5 overflow-hidden rounded-full bg-surface-sunken">
                    <span className="block h-full rounded-full bg-brand-700" style={{ width: `${Math.round(target.rasio * 100)}%` }} />
                </span>
            )}
            <p className={kelas('mt-2 inline-flex items-center gap-1 text-meta', target.tercapai ? 'font-bold text-brand-ink' : 'text-secondary')}>
                {target.kalimat}
                {target.tercapai ? <Icon name="check" size={16} /> : null}
            </p>
            {target.catatan ? <p className="mt-1 text-meta text-muted">{target.catatan}</p> : null}
            {target.keterangan ? <p className="mt-1 text-meta text-secondary">{target.keterangan}</p> : null}
        </li>
    );
}
```

`app/Models/User.php`:

1. Ganti:

```php
    }

    public function isAdmin(): bool
```

dengan:

```php
    }

    public function personalTargets(): HasMany
    {
        return $this->hasMany(PersonalTarget::class);
    }

    public function isAdmin(): bool
```

`app/Services/Community/HubProgres.php`:

1. Ganti:

```php
use App\Enums\ModerationStatus;
use App\Enums\TripStatus;
```

dengan:

```php
use App\Enums\ModerationStatus;
use App\Enums\PersonalTargetMetric;
use App\Enums\TripStatus;
```

2. Ganti:

```php
use App\Models\HikingHistory;
use App\Models\TripPlan;
```

dengan:

```php
use App\Models\HikingHistory;
use App\Models\PersonalTarget;
use App\Models\TripPlan;
```

3. Ganti:

```php
            'grafik' => $butir->count() < 2 ? null : $this->grafik($butir, $tahun),
            'riwayat' => $butir->map(fn (array $b) => $b['tampil'])->values()->all(),
```

dengan:

```php
            'grafik' => $butir->count() < 2 ? null : $this->grafik($butir, $tahun),
            // Target pribadi berlaku untuk bulan berjalan, jadi hanya tampil di tahun ini.
            'target' => $tahun === $kini ? $this->target($pengguna, $butir) : null,
            'riwayat' => $butir->map(fn (array $b) => $b['tampil'])->values()->all(),
```

4. Ganti:

```php
                'urut' => $tanggal->format('Y-m-d H:i:s').sprintf('%012d', $h->id),
                'nama' => $h->namaJalur(),
```

dengan:

```php
                'urut' => $tanggal->format('Y-m-d H:i:s').sprintf('%012d', $h->id),
                'bulanAngka' => $tanggal->month,
                'nama' => $h->namaJalur(),
```

5. Ganti:

```php
                'urut' => $t->planned_date->format('Y-m-d').' 00:00:00'.sprintf('%012d', 0),
                'nama' => $t->trail?->name ?? $t->name,
```

dengan:

```php
                'urut' => $t->planned_date->format('Y-m-d').' 00:00:00'.sprintf('%012d', 0),
                'bulanAngka' => $t->planned_date->month,
                'nama' => $t->trail?->name ?? $t->name,
```

6. Ganti:

```php
    }

    private static function karena(?CancellationReason $alasan): ?string
```

dengan:

```php
    }

    /**
     * Target pribadi bulan berjalan (FR-25; ruling W7-R7): capaian dari pendakian pendaki ini saja,
     * dengan aturan angka yang sama dengan ringkasan tahun. Pendakian yang dibatalkan ikut
     * terhitung pada target jumlah pendakian, sesuai prototype 13 ("tetap dihitung"). Tanpa
     * persen dan tanpa rentetan; target yang belum tercapai disebut angkanya, tanpa teguran.
     *
     * @param  Collection<int, array<string, mixed>>  $butir  butir tahun ini
     * @return array<string, mixed>
     */
    private function target(User $pengguna, Collection $butir): array
    {
        $sekarang = Carbon::now(Timezone::DEFAULT);
        $bulanIni = $butir->where('bulanAngka', $sekarang->month);
        $dijalani = $bulanIni->reject(fn (array $b) => $b['keadaan'] === 'batal');
        $target = $pengguna->personalTargets()->get()->keyBy(fn (PersonalTarget $t) => $t->metric->value);
        $angka = fn (int|float $n, int $desimal = 0): string => number_format($n, $desimal, ',', '.');

        $daftar = collect(PersonalTargetMetric::cases())
            ->filter(fn (PersonalTargetMetric $ukuran) => $target->has($ukuran->value))
            ->map(function (PersonalTargetMetric $ukuran) use ($target, $bulanIni, $dijalani, $sekarang, $butir, $angka) {
                $sasaran = $target[$ukuran->value]->monthly_value;
                $capaian = match ($ukuran) {
                    PersonalTargetMetric::GAIN => (int) $bulanIni->sum('naik'),
                    PersonalTargetMetric::DISTANCE => $bulanIni->sum('jarakM') / 1000,
                    PersonalTargetMetric::HIKES => $bulanIni->count(),
                };
                $tanpa = match ($ukuran) {
                    PersonalTargetMetric::GAIN => $dijalani->filter(fn (array $b) => $b['naik'] === null)->count(),
                    PersonalTargetMetric::DISTANCE => $dijalani->filter(fn (array $b) => $b['jarakM'] === null)->count(),
                    PersonalTargetMetric::HIKES => 0,
                };
                $teks = $ukuran === PersonalTargetMetric::DISTANCE ? $angka($capaian, 1) : $angka($capaian);
                $satuan = $ukuran->satuan() === '' ? '' : ' '.$ukuran->satuan();
                $tercapai = $capaian >= $sasaran;

                return [
                    'metrik' => $ukuran->value,
                    'judul' => $ukuran->label(),
                    'capaian' => $teks,
                    'sasaran' => $angka($sasaran).$satuan,
                    'tercapai' => $tercapai,
                    'kalimat' => $sekarang->translatedFormat('F').': '.($tercapai ? 'tercapai' : $teks.' dari '.$angka($sasaran).$satuan),
                    'rasio' => round(min(1, $capaian / max(1, $sasaran)), 3),
                    'catatan' => $tanpa > 0 ? $tanpa.' pendakian bulan ini belum punya angka '.($ukuran === PersonalTargetMetric::GAIN ? 'tanjakan' : 'jarak').', jadi belum ikut dihitung.' : null,
                    'bulan' => $ukuran === PersonalTargetMetric::HIKES ? $this->bulanTahun($butir, $sekarang) : null,
                    'keterangan' => $ukuran === PersonalTargetMetric::HIKES ? $this->keteranganBulan($butir, $sekarang) : null,
                ];
            })
            ->values()
            ->all();

        return [
            'daftar' => $daftar,
            'bulan' => $sekarang->translatedFormat('F Y'),
            'ubah' => route('progress.targets.update'),
            'awal' => collect(PersonalTargetMetric::cases())
                ->mapWithKeys(fn (PersonalTargetMetric $u) => [$u->isian() => $target->has($u->value) ? (string) $target[$u->value]->monthly_value : ''])
                ->all(),
            'aturan' => 'Target tidak mengubah label kecocokan dan tidak menaikkan tingkat pengalaman Anda.',
        ];
    }

    /**
     * Dua belas bulan tahun ini untuk target jumlah pendakian: ada pendakian, hanya pembatalan,
     * kosong, atau belum tiba. Bukan rentetan: tidak ada hitungan bulan berturut-turut.
     *
     * @param  Collection<int, array<string, mixed>>  $butir
     * @return array<int, array{nama: string, pendek: string, keadaan: string, teks: string}>
     */
    private function bulanTahun(Collection $butir, Carbon $sekarang): array
    {
        return collect(range(1, 12))->map(function (int $bulan) use ($butir, $sekarang) {
            $isi = $butir->where('bulanAngka', $bulan);
            $batal = $isi->where('keadaan', 'batal')->count();
            $jalan = $isi->count() - $batal;
            $tanggal = $sekarang->copy()->startOfYear()->month($bulan);
            $keadaan = match (true) {
                $bulan > $sekarang->month => 'nanti',
                $jalan > 0 => 'ada',
                $batal > 0 => 'batal',
                default => 'kosong',
            };

            return [
                'nama' => $tanggal->translatedFormat('F'),
                'pendek' => $tanggal->translatedFormat('M'),
                'keadaan' => $keadaan,
                'teks' => match ($keadaan) {
                    'nanti' => 'belum tiba',
                    'kosong' => 'tidak ada pendakian',
                    default => $isi->count().' pendakian'.($batal > 0 ? ', '.$batal.' dibatalkan' : ''),
                },
            ];
        })->all();
    }

    /**
     * Keterangan kalimat kotak bulan: rentangnya, dan bulan yang hanya berisi pembatalan.
     *
     * @param  Collection<int, array<string, mixed>>  $butir
     */
    private function keteranganBulan(Collection $butir, Carbon $sekarang): string
    {
        $kalimat = ['Januari sampai Desember '.$sekarang->year.'.'];
        $hanyaBatal = collect(range(1, $sekarang->month))
            ->filter(fn (int $bulan) => $butir->where('bulanAngka', $bulan)->isNotEmpty() && $butir->where('bulanAngka', $bulan)->every(fn (array $b) => $b['keadaan'] === 'batal'))
            ->take(2);

        foreach ($hanyaBatal as $bulan) {
            $b = $butir->where('bulanAngka', $bulan)->first();
            $karena = self::karena($b['alasan']);
            $kalimat[] = $b['bulanPanjang'].' berisi '.$b['nama'].' yang dibatalkan'.($karena === null ? '' : ' karena '.$karena).', dan tetap dihitung.';
        }

        return implode(' ', $kalimat);
    }

    private static function karena(?CancellationReason $alasan): ?string
```

`resources/js/components/progres/types.ts`:

1. Ganti:

```ts
    jenis: Array<{ nilai: string; label: string }>;
}
```

dengan:

```ts
    jenis: Array<{ nilai: string; label: string }>;
}

/** Satu bulan pada kotak target jumlah pendakian; bukan rentetan, tidak ada hitungan berturut-turut. */
export interface BulanTarget {
    nama: string;
    pendek: string;
    keadaan: 'ada' | 'batal' | 'kosong' | 'nanti';
    teks: string;
}

export interface ButirTarget {
    metrik: 'GAIN' | 'DISTANCE' | 'HIKES';
    judul: string;
    capaian: string;
    sasaran: string;
    tercapai: boolean;
    kalimat: string;
    /** 0 sampai 1, hanya untuk panjang bilah; tidak pernah ditulis sebagai persen. */
    rasio: number;
    catatan: string | null;
    bulan: BulanTarget[] | null;
    keterangan: string | null;
}

export interface IsianTarget {
    tanjakan_m: string;
    jarak_km: string;
    pendakian: string;
}

export interface TargetPribadiData {
    daftar: ButirTarget[];
    bulan: string;
    ubah: string;
    awal: IsianTarget;
    aturan: string;
}
```

`resources/js/pages/Community/HikerProgress.tsx`:

1. Ganti:

```tsx
import { RiwayatProgres } from '@/components/progres/RiwayatProgres';
import type { ButirRiwayat, FormulirCatatanData, GrafikPendakianData, KabarProgres, RingkasanTahun as Ringkasan } from '@/components/progres/types';
import type { JalurKatalog } from '@/components/trails/PemilihJalur';
```

dengan:

```tsx
import { RiwayatProgres } from '@/components/progres/RiwayatProgres';
import { TargetPribadi } from '@/components/progres/TargetPribadi';
import type { ButirRiwayat, FormulirCatatanData, GrafikPendakianData, KabarProgres, RingkasanTahun as Ringkasan, TargetPribadiData } from '@/components/progres/types';
import type { JalurKatalog } from '@/components/trails/PemilihJalur';
```

2. Ganti:

```tsx
    grafik: GrafikPendakianData | null;
    riwayat: ButirRiwayat[];
```

dengan:

```tsx
    grafik: GrafikPendakianData | null;
    /** Target pribadi bulan berjalan; null di tahun lain. */
    target: TargetPribadiData | null;
    riwayat: ButirRiwayat[];
```

3. Ganti:

```tsx
export default function HikerProgress() {
    const { kosong, tahun, ringkasan, grafik, riwayat, riwayatTerpotong, laporan, gunung, keteranganPeta, gunungTanpaKoordinat, formulirCatatan, kabar } = usePage<Props>().props;
    // Pilihan tahun hanya bila ada lebih dari satu tahun yang dapat dibuka.
```

dengan:

```tsx
export default function HikerProgress() {
    const { kosong, tahun, ringkasan, grafik, target, riwayat, riwayatTerpotong, laporan, gunung, keteranganPeta, gunungTanpaKoordinat, formulirCatatan, kabar } = usePage<Props>().props;
    // Pilihan tahun hanya bila ada lebih dari satu tahun yang dapat dibuka.
```

4. Ganti:

```tsx
                ) : null}

                <RiwayatProgres tahun={tahun.dipilih} riwayat={riwayat} terpotong={riwayatTerpotong} formulir={formulirCatatan} kabar={kabar} />
```

dengan:

```tsx
                ) : null}

                {target ? <TargetPribadi target={target} kabar={kabar} /> : null}

                <RiwayatProgres tahun={tahun.dipilih} riwayat={riwayat} terpotong={riwayatTerpotong} formulir={formulirCatatan} kabar={kabar} />
```

`routes/web.php`:

1. Ganti:

```php
use App\Http\Controllers\Community\ManualHikeController;
use App\Http\Controllers\Community\TrailContributionController;
```

dengan:

```php
use App\Http\Controllers\Community\ManualHikeController;
use App\Http\Controllers\Community\PersonalTargetController;
use App\Http\Controllers\Community\TrailContributionController;
```

2. Ganti:

```php
    Route::delete('progres/catatan/{riwayat}', [ManualHikeController::class, 'destroy'])->whereNumber('riwayat')->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,catatan-pendakian')->name('progress.records.destroy');
    Route::get('history', HikingHistoryController::class)->name('history');
```

dengan:

```php
    Route::delete('progres/catatan/{riwayat}', [ManualHikeController::class, 'destroy'])->whereNumber('riwayat')->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,catatan-pendakian')->name('progress.records.destroy');
    // Target pribadi bulanan (FR-25, W7): satu set milik pendaki yang masuk, tanpa id.
    Route::put('progres/target', [PersonalTargetController::class, 'update'])->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,target-pribadi')->name('progress.targets.update');
    Route::get('history', HikingHistoryController::class)->name('history');
```

`tests/Pratinjau/HalamanPratinjauTest.php`:

1. Ganti:

```php
     * Pangrango dibatalkan, di sini karena cuaca) ditambah satu catatan pendakian di luar trip di
     * Papandayan dengan angka yang ditulis Budi sendiri.
     */
```

dengan:

```php
     * Pangrango dibatalkan, di sini karena cuaca) ditambah satu catatan pendakian di luar trip di
     * Papandayan pada awal bulan ini dengan angka yang ditulis Budi sendiri, dan dua target bulanan:
     * tanjakan yang belum tercapai dan jumlah pendakian yang tercapai.
     */
```

2. Ganti:

```php
            'recorded_moving_minutes' => 330,
            'completed_at' => now('Asia/Jakarta')->subDays(150)->setTime(12, 0)->utc(),
        ]);

        $this->tulisPratinjau('progres', $this->get(self::ASAL.'/progres'), 'Community/HikerProgress');
```

dengan:

```php
            'recorded_moving_minutes' => 330,
            'completed_at' => now('Asia/Jakarta')->startOfMonth()->setTime(6, 0)->utc(),
        ]);
        $budi->personalTargets()->createMany([['metric' => 'GAIN', 'monthly_value' => 1000], ['metric' => 'HIKES', 'monthly_value' => 1]]);

        $this->tulisPratinjau('progres', $this->get(self::ASAL.'/progres'), 'Community/HikerProgress');
```

- [ ] **Step 4: Build dan tipe.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `npx tsc --noEmit`
Expected: `✓ built`; keluaran Wayfinder memuat `progress.targets.update`; tsc tanpa keluaran galat.

- [ ] **Step 5: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Enums/PersonalTargetMetric.php app/Http/Controllers/Community/PersonalTargetController.php app/Http/Requests/Community/UpdatePersonalTargetsRequest.php app/Models/PersonalTarget.php app/Models/User.php app/Services/Community/HubProgres.php database/factories/PersonalTargetFactory.php database/migrations/2026_10_08_000200_complete_personal_targets_table.php routes/web.php tests/Feature/PersonalTargetTest.php tests/Feature/ProgressHierarchyTest.php tests/Pratinjau/HalamanPratinjauTest.php tests/Spatial/PersonalTargetSchemaTest.php`, lalu `php artisan test --compact --filter='PersonalTarget|ProgressHierarchy|HikerProgress'`
Expected: PASS. Pada simulasi rencana: 29 test, 27 lulus, 2 dilewati.

- [ ] **Step 6: Suite, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 2.081 test, 2.079 lulus, 2 dilewati (sebelum tugas: 2.073).

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `php artisan test --compact --testsuite=Pratinjau`
Expected: PASS. Pada simulasi rencana: 30 test lulus.

- [ ] **Step 7: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 4: Pencapaian pribadi

Prototype 13, induk W7 butir 7; ruling W7-R16 dan W7-R20. `App\Services\Community\PencapaianPribadi` menurunkan
pencapaian dari riwayat pendaki itu sendiri, tanpa peringkat atau pembanding: tanjakan pertama di atas 500, 1.000, 2.000,
dan 3.000 m (tonggak yang sama dengan `HasilPendakian::TONGGAK_TANJAKAN`, dihitung dengan `HubProgres::angka()`), dan
laporan kondisi pertama yang terbit beserta jumlah terima kasihnya. Progres menampilkannya sesudah target; tanpa
pencapaian, bagian itu menyebut apa yang akan tercatat di sana, dan Progres yang masih kosong sama sekali tidak
menampilkannya.

**Files:**
- Create: `app/Services/Community/PencapaianPribadi.php`, `resources/js/components/progres/Pencapaian.tsx`
- Modify: `app/Services/Community/HubProgres.php`, `resources/js/components/progres/types.ts`, `resources/js/pages/Community/HikerProgress.tsx`
- Test: `tests/Feature/PersonalAchievementTest.php` (baru), `tests/Feature/ProgressHierarchyTest.php`

**Interfaces:**
- Consumes: `HubProgres::angka()` (Tugas 2); `HasilPendakian::TONGGAK_TANJAKAN` (W6); `TrailConditionReport` terbit dengan `withCount('thanks')`.
- Produces: `PencapaianPribadi::TONGGAK` (`[500, 1000, 2000, 3000]`), `PencapaianPribadi::untuk(User $pengguna): array` (daftar `kunci`, `jenis` `'tanjakan'` atau `'laporan'`, `judul`, `keterangan`); konstruktor `HubProgres` menerima `PencapaianPribadi`; kunci `pencapaian` di `HubProgres::untuk()`; TypeScript `ButirPencapaian`; komponen `Pencapaian`.

- [ ] **Step 1: Tulis test yang gagal.** Buat berkas test baru dengan `make:test`, lalu ganti isinya persis.

Buat `tests/Feature/PersonalAchievementTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\CompletionState;
use App\Enums\ModerationStatus;
use App\Models\HikingHistory;
use App\Models\ReportThank;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\Community\HasilPendakian;
use App\Services\Community\PencapaianPribadi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use ReflectionClassConstant;
use Tests\TestCase;

/**
 * Pencapaian turunan (spec v3 bagian 6.14; rencana induk W7 butir 7): diturunkan dari riwayat
 * pendaki sendiri, tanpa kompetisi. Data pendaki lain tidak pernah dipakai (R-015), pendakian
 * yang berbalik tidak membuka tonggak, dan angka yang tidak diketahui tidak dianggap nol.
 */
class PersonalAchievementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-15 09:00:00', 'Asia/Jakarta'));
    }

    private function pendakian(User $user, string $nama, ?int $naik, string $tanggal, CompletionState $hasil = CompletionState::COMPLETED): HikingHistory
    {
        $jalur = Trail::factory()->easy()->create(['name' => $nama, 'elevation_gain_m' => $naik]);
        $trip = TripPlan::factory()->create(['user_id' => $user->id, 'trail_id' => $jalur->id, 'planned_date' => $tanggal]);

        return HikingHistory::create([
            'user_id' => $user->id,
            'trip_plan_id' => $trip->id,
            'trail_id' => $jalur->id,
            'trip_type' => $trip->trip_type->value,
            'completion_state' => $hasil->value,
            'completed_at' => Carbon::parse($tanggal.' 16:00:00', 'Asia/Jakarta')->utc(),
        ]);
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function pencapaian(User $user): array
    {
        return $this->actingAs($user)->get(route('progress'))->assertOk()->viewData('page')['props']['pencapaian'];
    }

    public function test_each_milestone_names_the_first_completed_hike_that_crossed_it(): void
    {
        $user = User::factory()->create();
        $this->pendakian($user, 'Andong via Sawit', 380, '2026-02-14');
        $this->pendakian($user, 'Prau via Patak Banteng', 700, '2026-07-19');
        $this->pendakian($user, 'Gede via Cibodas', 1660, '2026-10-10');
        $this->pendakian($user, 'Merbabu via Selo', 1500, '2026-10-12');

        $this->assertSame([
            ['kunci' => 'tanjakan-1000', 'jenis' => 'tanjakan', 'judul' => 'Tanjakan pertama di atas 1.000 m', 'keterangan' => 'Gede via Cibodas · Okt 2026'],
            ['kunci' => 'tanjakan-500', 'jenis' => 'tanjakan', 'judul' => 'Tanjakan pertama di atas 500 m', 'keterangan' => 'Prau via Patak Banteng · Jul 2026'],
        ], $this->pencapaian($user));
    }

    /**
     * Berbalik di jalur tidak membuktikan tanjakannya tertuntaskan, dan jalur tanpa angka tidak
     * dianggap nol maupun tinggi. Angka yang ditulis pendaki sendiri ikut (aturan Progres).
     */
    public function test_abandoned_and_unknown_hikes_open_no_milestone_but_written_numbers_do(): void
    {
        $user = User::factory()->create();
        $this->pendakian($user, 'Rinjani via Sembalun', 2600, '2026-05-01', CompletionState::ABANDONED);
        $this->pendakian($user, 'Jalur Tanpa Angka', null, '2026-06-01');

        $this->assertSame([], $this->pencapaian($user));

        HikingHistory::factory()->manual()->for($user)->create(['trail_id' => null, 'trail_name' => 'Lawu via Cemoro Sewu', 'recorded_gain_m' => 1200, 'completed_at' => Carbon::parse('2026-08-17 05:00:00')]);

        $this->assertSame(['Tanjakan pertama di atas 1.000 m', 'Tanjakan pertama di atas 500 m'], array_column($this->pencapaian($user), 'judul'));
        $this->assertSame('Lawu via Cemoro Sewu · Agt 2026', $this->pencapaian($user)[0]['keterangan']);
    }

    /**
     * Laporan kondisi pertama yang lolos moderasi, dengan terima kasih yang diterimanya; laporan
     * yang masih menunggu belum terbit.
     */
    public function test_the_first_published_report_is_an_achievement_and_a_pending_one_is_not(): void
    {
        $user = User::factory()->create();
        $menunggu = TrailConditionReport::factory()->for(Trail::factory()->easy()->create(['name' => 'Jalur Menunggu']))->create(['user_id' => $user->id, 'moderation_status' => ModerationStatus::PENDING->value]);

        $this->assertSame([], $this->pencapaian($user));

        $terbit = TrailConditionReport::factory()->for(Trail::factory()->easy()->create(['name' => 'Prau via Patak Banteng']))->create(['user_id' => $user->id, 'moderation_status' => ModerationStatus::APPROVED->value]);

        foreach (range(1, 2) as $i) {
            ReportThank::create(['trail_condition_report_id' => $terbit->id, 'user_id' => User::factory()->create()->id]);
        }

        $this->assertSame([['kunci' => 'laporan-pertama', 'jenis' => 'laporan', 'judul' => 'Laporan kondisi pertama terbit', 'keterangan' => 'Prau via Patak Banteng · 2 terima kasih']], $this->pencapaian($user));
        $this->assertNotNull($menunggu->fresh());
    }

    /**
     * R-015: pencapaian tidak pernah memakai data pengguna lain, dan tidak membawa peringkat,
     * persentil, atau pembanding.
     */
    public function test_achievements_never_use_other_hikers_data(): void
    {
        $saya = User::factory()->create();
        $orangLain = User::factory()->create();
        $this->pendakian($saya, 'Andong via Sawit', 600, '2026-03-01');
        $this->pendakian($orangLain, 'Semeru via Ranu Pane', 3100, '2026-02-01');
        TrailConditionReport::factory()->for(Trail::factory()->easy()->create())->create(['user_id' => $orangLain->id, 'moderation_status' => ModerationStatus::APPROVED->value]);

        $pencapaian = $this->pencapaian($saya);

        $this->assertSame(['Tanjakan pertama di atas 500 m'], array_column($pencapaian, 'judul'));

        foreach (['peringkat', 'persentil', 'rank', 'pendaki lain', '%'] as $kata) {
            $this->assertStringNotContainsStringIgnoringCase($kata, json_encode($pencapaian, JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * Tonggak di Progres dan pencapaian di ringkasan satu pendakian (W6) memakai daftar yang sama.
     */
    public function test_progress_and_the_hike_summary_share_the_same_milestones(): void
    {
        $this->assertSame((new ReflectionClassConstant(HasilPendakian::class, 'TONGGAK_TANJAKAN'))->getValue(), PencapaianPribadi::TONGGAK);
    }
}
```

`tests/Feature/ProgressHierarchyTest.php`:

1. Ganti:

```php
        $sumber = $this->halaman();
        $urutan = array_map(fn (string $tag) => strpos($sumber, $tag), ['<RingkasanTahun', '<GrafikPendakian', '<TargetPribadi', '<RiwayatProgres', 'id="progres-laporan"', '<PetaGaris']);

        $this->assertNotContains(false, $urutan, 'Setiap bagian Progres harus ada.');
        $this->assertSame($urutan, array_values(collect($urutan)->sort()->all()), 'Ringkasan tahun, grafik, target, riwayat, laporan, lalu peta (prototype 13).');
    }
```

dengan:

```php
        $sumber = $this->halaman();
        $urutan = array_map(fn (string $tag) => strpos($sumber, $tag), ['<RingkasanTahun', '<GrafikPendakian', '<TargetPribadi', '<Pencapaian', '<RiwayatProgres', 'id="progres-laporan"', '<PetaGaris']);

        $this->assertNotContains(false, $urutan, 'Setiap bagian Progres harus ada.');
        $this->assertSame($urutan, array_values(collect($urutan)->sort()->all()), 'Ringkasan tahun, grafik, target, pencapaian, riwayat, laporan, lalu peta (prototype 13).');
    }
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='PersonalAchievement|ProgressHierarchy|HikerProgress'`
Expected: FAIL. Pada simulasi rencana: 23 test, 17 lulus, 1 gagal, 5 error. Yang merah: kelima test `PersonalAchievementTest` (prop `pencapaian` dan `PencapaianPribadi` belum ada) dan `ProgressHierarchyTest::test_the_year_summary_leads_and_the_map_follows_the_history` (bagian Pencapaian belum ada).

- [ ] **Step 3: Tulis implementasinya.** Kelas dibuat dengan
`DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:class --no-interaction Services/Community/PencapaianPribadi`,
lalu isinya diganti persis.

Buat `app/Services/Community/PencapaianPribadi.php`:

```php
<?php

namespace App\Services\Community;

use App\Enums\CompletionState;
use App\Models\HikingHistory;
use App\Models\TrailConditionReport;
use App\Models\User;
use App\Support\Timezone;

/**
 * Pencapaian yang diturunkan dari riwayat pendaki sendiri (spec v3 bagian 6.14, prototype 13;
 * rencana induk W7 butir 7). Hanya dua jenis yang datanya memang ada: tonggak tanjakan pertama
 * dari pendakian yang tuntas, dan laporan kondisi pertama yang terbit. Tidak ada pembanding
 * dengan pendaki lain, peringkat, persentil, atau hadiah kecepatan (R-015, PRD §145); setiap
 * kueri dibatasi pada pendaki ini. "Camping pertama di atas N m" tidak dibangun karena lokasi
 * berkemah tidak tercatat (ruling W6-R13, R-007).
 */
final class PencapaianPribadi
{
    /**
     * Tonggak "tanjakan pertama di atas N m", sama dengan ringkasan pendakian
     * (HasilPendakian::TONGGAK_TANJAKAN; kesamaannya dijaga PersonalAchievementTest).
     */
    public const TONGGAK = [500, 1000, 2000, 3000];

    /**
     * @return array<int, array{kunci: string, jenis: string, judul: string, keterangan: string}>
     */
    public function untuk(User $pengguna): array
    {
        return [...$this->tonggakTanjakan($pengguna), ...$this->laporanPertama($pengguna)];
    }

    /**
     * Pendakian tuntas pertama, urut tanggal, yang tanjakannya melewati setiap tonggak; angka
     * pendakian memakai aturan Progres (HubProgres::angka), jadi angka yang ditulis pendaki
     * sendiri ikut dan yang tidak diketahui tidak pernah dianggap nol.
     *
     * @return array<int, array{kunci: string, jenis: string, judul: string, keterangan: string}>
     */
    private function tonggakTanjakan(User $pengguna): array
    {
        $tuntas = HikingHistory::query()
            ->where('user_id', $pengguna->id)
            ->where('completion_state', CompletionState::COMPLETED->value)
            ->with('trail:id,name,elevation_gain_m,distance_km')
            ->orderBy('completed_at')
            ->orderBy('id')
            ->get();

        $hasil = [];

        foreach (self::TONGGAK as $meter) {
            $pertama = $tuntas->first(fn (HikingHistory $h) => (HubProgres::angka($h)['naik'] ?? 0) >= $meter);

            if ($pertama === null) {
                continue;
            }

            $hasil[] = [
                'kunci' => 'tanjakan-'.$meter,
                'jenis' => 'tanjakan',
                'judul' => 'Tanjakan pertama di atas '.number_format($meter, 0, ',', '.').' m',
                'keterangan' => $pertama->namaJalur().' · '.$pertama->completed_at->copy()->setTimezone(Timezone::DEFAULT)->translatedFormat('M Y'),
            ];
        }

        // Tonggak tertinggi lebih dulu: yang terbaru dicapai paling dekat dengan pembacanya.
        return array_reverse($hasil);
    }

    /**
     * Laporan kondisi pertama pendaki ini yang lolos moderasi, dengan ucapan terima kasih yang
     * diterimanya: dampak pada pendaki lain, bukan skor.
     *
     * @return array<int, array{kunci: string, jenis: string, judul: string, keterangan: string}>
     */
    private function laporanPertama(User $pengguna): array
    {
        $laporan = TrailConditionReport::query()
            ->where('user_id', $pengguna->id)
            ->visibleToPublic()
            ->with('trail:id,name')
            ->withCount('thanks')
            ->orderBy('created_at')
            ->orderBy('id')
            ->first();

        if ($laporan === null) {
            return [];
        }

        return [[
            'kunci' => 'laporan-pertama',
            'jenis' => 'laporan',
            'judul' => 'Laporan kondisi pertama terbit',
            'keterangan' => implode(' · ', array_filter([
                $laporan->trail?->name,
                $laporan->thanks_count > 0 ? $laporan->thanks_count.' terima kasih' : null,
            ])),
        ]];
    }
}
```

Buat `resources/js/components/progres/Pencapaian.tsx`:

```tsx
import { Icon } from '@/components/ui/Icon';
import { kelas } from '@/lib/kelas';
import type { ButirPencapaian } from './types';

/**
 * Pencapaian (spec v3 bagian 6.14, prototype 13): tonggak tanjakan pertama dan laporan kondisi
 * pertama yang terbit, diturunkan dari riwayat pendaki ini saja (R-015). Tidak ada peringkat,
 * lencana kecepatan, atau perbandingan dengan pendaki lain; ikon hanya menemani judul yang
 * sudah menyebut jenisnya, jadi disembunyikan dari pembaca layar.
 */
export function Pencapaian({ pencapaian }: { pencapaian: ButirPencapaian[] }) {
    return (
        <section aria-labelledby="judul-pencapaian">
            <h2 id="judul-pencapaian" className="text-bagian text-primary">
                Pencapaian
            </h2>
            {pencapaian.length === 0 ? (
                <p className="mt-2 max-w-prose text-isi text-secondary">
                    Belum ada pencapaian. Tanjakan pertama di atas 500 m dan laporan kondisi pertama yang terbit akan tercatat di sini, dari pendakian Anda sendiri.
                </p>
            ) : (
                <ul className="mt-3 divide-y divide-subtle rounded-kartu border border-subtle bg-surface px-4">
                    {pencapaian.map((p) => (
                        <li key={p.kunci} className="flex items-center gap-3 py-3">
                            <span
                                aria-hidden="true"
                                className={kelas(
                                    'inline-flex size-10 shrink-0 items-center justify-center rounded-control',
                                    p.jenis === 'laporan' ? 'bg-community-50 text-community-900' : 'bg-brand-100 text-brand-900',
                                )}
                            >
                                <Icon name={p.jenis === 'laporan' ? 'user' : 'tanjakan'} size={20} />
                            </span>
                            <span className="min-w-0">
                                <span className="block break-words text-kecil font-bold text-primary">{p.judul}</span>
                                <span className="block break-words text-meta text-secondary">{p.keterangan}</span>
                            </span>
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}
```

`app/Services/Community/HubProgres.php`:

1. Ganti:

```php
    private const BATAS_GRAFIK = 12;

    public function __construct(private readonly HikerProgressService $progres) {}

    public static function tahunKini(): int
```

dengan:

```php
    private const BATAS_GRAFIK = 12;

    public function __construct(
        private readonly HikerProgressService $progres,
        private readonly PencapaianPribadi $pencapaian,
    ) {}

    public static function tahunKini(): int
```

2. Ganti:

```php
            'target' => $tahun === $kini ? $this->target($pengguna, $butir) : null,
            'riwayat' => $butir->map(fn (array $b) => $b['tampil'])->values()->all(),
```

dengan:

```php
            'target' => $tahun === $kini ? $this->target($pengguna, $butir) : null,
            // Pencapaian milik seluruh riwayat, bukan satu tahun.
            'pencapaian' => $this->pencapaian->untuk($pengguna),
            'riwayat' => $butir->map(fn (array $b) => $b['tampil'])->values()->all(),
```

`resources/js/components/progres/types.ts`:

1. Ganti:

```ts
    awal: IsianTarget;
    aturan: string;
}
```

dengan:

```ts
    awal: IsianTarget;
    aturan: string;
}

/** Pencapaian yang diturunkan dari riwayat pendaki sendiri; tanpa peringkat atau pembanding. */
export interface ButirPencapaian {
    kunci: string;
    jenis: 'tanjakan' | 'laporan';
    judul: string;
    keterangan: string;
}
```

`resources/js/pages/Community/HikerProgress.tsx`:

1. Ganti:

```tsx
import { GrafikPendakian } from '@/components/progres/GrafikPendakian';
import { RingkasanTahun } from '@/components/progres/RingkasanTahun';
```

dengan:

```tsx
import { GrafikPendakian } from '@/components/progres/GrafikPendakian';
import { Pencapaian } from '@/components/progres/Pencapaian';
import { RingkasanTahun } from '@/components/progres/RingkasanTahun';
```

2. Ganti:

```tsx
import { TargetPribadi } from '@/components/progres/TargetPribadi';
import type { ButirRiwayat, FormulirCatatanData, GrafikPendakianData, KabarProgres, RingkasanTahun as Ringkasan, TargetPribadiData } from '@/components/progres/types';
import type { JalurKatalog } from '@/components/trails/PemilihJalur';
```

dengan:

```tsx
import { TargetPribadi } from '@/components/progres/TargetPribadi';
import type { ButirPencapaian, ButirRiwayat, FormulirCatatanData, GrafikPendakianData, KabarProgres, RingkasanTahun as Ringkasan, TargetPribadiData } from '@/components/progres/types';
import type { JalurKatalog } from '@/components/trails/PemilihJalur';
```

3. Ganti:

```tsx
    target: TargetPribadiData | null;
    riwayat: ButirRiwayat[];
```

dengan:

```tsx
    target: TargetPribadiData | null;
    pencapaian: ButirPencapaian[];
    riwayat: ButirRiwayat[];
```

4. Ganti:

```tsx
export default function HikerProgress() {
    const { kosong, tahun, ringkasan, grafik, target, riwayat, riwayatTerpotong, laporan, gunung, keteranganPeta, gunungTanpaKoordinat, formulirCatatan, kabar } = usePage<Props>().props;
    // Pilihan tahun hanya bila ada lebih dari satu tahun yang dapat dibuka.
```

dengan:

```tsx
export default function HikerProgress() {
    const { kosong, tahun, ringkasan, grafik, target, pencapaian, riwayat, riwayatTerpotong, laporan, gunung, keteranganPeta, gunungTanpaKoordinat, formulirCatatan, kabar } = usePage<Props>().props;
    // Pilihan tahun hanya bila ada lebih dari satu tahun yang dapat dibuka.
```

5. Ganti:

```tsx
                {target ? <TargetPribadi target={target} kabar={kabar} /> : null}

                <RiwayatProgres tahun={tahun.dipilih} riwayat={riwayat} terpotong={riwayatTerpotong} formulir={formulirCatatan} kabar={kabar} />
```

dengan:

```tsx
                {target ? <TargetPribadi target={target} kabar={kabar} /> : null}

                {kosong ? null : <Pencapaian pencapaian={pencapaian} />}

                <RiwayatProgres tahun={tahun.dipilih} riwayat={riwayat} terpotong={riwayatTerpotong} formulir={formulirCatatan} kabar={kabar} />
```

- [ ] **Step 4: Build dan tipe.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `npx tsc --noEmit`
Expected: `✓ built`; tsc tanpa keluaran galat.

- [ ] **Step 5: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Services/Community/PencapaianPribadi.php app/Services/Community/HubProgres.php tests/Feature/PersonalAchievementTest.php tests/Feature/ProgressHierarchyTest.php`, lalu `php artisan test --compact --filter='PersonalAchievement|ProgressHierarchy|HikerProgress'`
Expected: PASS. Pada simulasi rencana: 23 test, 23 lulus.

- [ ] **Step 6: Suite, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 2.086 test, 2.084 lulus, 2 dilewati (sebelum tugas: 2.081).

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `php artisan test --compact --testsuite=Pratinjau`
Expected: PASS. Pada simulasi rencana: 30 test lulus.

- [ ] **Step 7: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 5: Perlengkapan pribadi yang mengisi awal daftar persiapan

Spec v3 bagian 6.10 dan 6.15, induk W7 butir 4, titipan W4 "Ke W7"; ruling W7-R2, W7-R8, dan W7-R21. Migrasi aditif
`2026_10_08_000300` mengisi rintisan `2026_09_25_000921` (pemilik, nama, unik `lower(name)` per pemilik, RLS), dan
`000400` menautkan butir persiapan ke barang yang mengisinya (`nullOnDelete`). Pendaki menambah, mengganti nama, dan
menghapus barang lewat tiga rute berbatas laju `perlengkapan`: nama dirapikan, 2 sampai 60 karakter, tidak kembar tanpa
memandang huruf besar, bukan nama butir izin, paling banyak 40 barang (ganti nama tetap dapat di batas). Setiap kali
daftar persiapan trip dibangkitkan, barang pemilik trip masuk sebagai butir Perlengkapan yang tidak kritis dan belum
dikonfirmasi, dengan keterangan asalnya (BR-10: memiliki tidak sama dengan membawa). Barang yang sudah bertaut atau
namanya sudah ada dilewati, jadi "Perbarui daftar" tidak menggandakan, dan daftar trip yang sudah ada tidak berubah
ketika barang diganti nama atau dihapus. Lembar di Profil dipasang di Tugas 10.

**Files:**
- Create: `app/Http/Controllers/Profile/OwnedItemController.php`, `app/Http/Requests/Profile/SaveOwnedItemRequest.php`, `app/Models/UserOwnedItem.php`, `app/Policies/UserOwnedItemPolicy.php`, `app/Services/Trips/IsiAwalPerlengkapan.php`, `database/factories/UserOwnedItemFactory.php`, `database/migrations/2026_10_08_000300_complete_user_owned_items_table.php`, `database/migrations/2026_10_08_000400_add_owned_item_to_trip_preparation_items_table.php`
- Modify: `app/Models/TripPreparationItem.php`, `app/Models/User.php`, `app/Services/PreparationService.php`, `routes/web.php`
- Test: `tests/Feature/OwnedEquipmentTest.php` (rintisan ditulis ulang utuh), `tests/Unit/PreparationServiceTest.php`, `tests/Spatial/UserOwnedItemSchemaTest.php` (baru, PostgreSQL)

**Interfaces:**
- Consumes: `PreparationService::generateFor(TripPlan)` dan `PreparationService::PERMIT_LABEL`; `PreparationCategory::EQUIPMENT`, `PreparationStatus::NOT_CONFIRMED`; `HalamanPersiapan` (prop `kategori` dengan butir `label`, `status`, `kritis`, `keterangan`); `config('hiking.rate_limits.personal_writes_per_minute')`.
- Produces:
  - model `UserOwnedItem` (`user_id`, `name`; `BATAS` 40; `user(): BelongsTo`), `User::ownedItems(): HasMany`, pabrik `UserOwnedItemFactory`; kolom `trip_preparation_items.user_owned_item_id` (boleh kosong, `nullOnDelete`) di `$fillable` `TripPreparationItem`;
  - indeks unik ekspresi `user_owned_items_unik` (`user_id`, `lower(name)`) di kedua pengemudi; RLS di PostgreSQL;
  - `UserOwnedItemPolicy::update(User, UserOwnedItem): bool` dan `delete(User, UserOwnedItem): bool`;
  - rute `profile.items.store` (POST `profile/perlengkapan`), `profile.items.update` (PUT `profile/perlengkapan/{item}`), `profile.items.destroy` (DELETE `profile/perlengkapan/{item}`), berbatas laju `perlengkapan`, kembali ke `profile` dengan flash `kabar-profil` `{bagian: 'perlengkapan', teks: string}`; `SaveOwnedItemRequest::nama(): string` (nama yang sudah dirapikan);
  - `IsiAwalPerlengkapan::KETERANGAN` (`'Dari perlengkapan pribadi Anda. Tetap dikonfirmasi untuk trip ini.'`) dan `IsiAwalPerlengkapan::isi(TripPlan $trip): void`; konstruktor `PreparationService(PermitService $permits, IsiAwalPerlengkapan $perlengkapan)`.

- [ ] **Step 1: Tulis test yang gagal.** `OwnedEquipmentTest.php` sudah ada sebagai rintisan; ganti isinya utuh.
`PreparationServiceTest` membuat layanan dengan konstruktor dua parameter dan mendapat dua test baru (isi awal tidak
kritis dan belum dikonfirmasi; "Perbarui daftar" tidak menggandakan dan tidak mengembalikan status). Test Spatial dibuat
langsung di `tests/Spatial/`.

Buat `tests/Spatial/UserOwnedItemSchemaTest.php`:

```php
<?php

namespace Tests\Spatial;

use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Perlengkapan pribadi di PostgreSQL (R-024, ruling W7-R8): kolom yang diisi migrasi aditif di
 * atas rintisan 2026_09_25_000921, indeks unik ekspresi lower(name) per pendaki, kunci asing yang
 * ikut terhapus bersama akun, RLS yang menutup REST API, dan tautan butir persiapan yang dilepas
 * (bukan dihapus) ketika barangnya dihapus. Suite SQLite tidak membuktikan perilaku PostgreSQL
 * untuk satu pun dari itu. Migrasinya tidak dijalankan di Supabase sebelum izin pemilik.
 */
class UserOwnedItemSchemaTest extends SpatialTestCase
{
    public function test_the_table_has_its_production_shape(): void
    {
        $kolom = collect(DB::select(
            "SELECT column_name, data_type, is_nullable, character_maximum_length
             FROM information_schema.columns
             WHERE table_schema = current_schema() AND table_name = 'user_owned_items'"
        ))->keyBy('column_name');

        $this->assertSame(['bigint', 'NO'], [$kolom['user_id']->data_type, $kolom['user_id']->is_nullable]);
        $this->assertSame(['character varying', 'NO', 60], [$kolom['name']->data_type, $kolom['name']->is_nullable, (int) $kolom['name']->character_maximum_length]);
        $this->assertTrue((bool) DB::selectOne("SELECT relrowsecurity FROM pg_class WHERE relname = 'user_owned_items'")->relrowsecurity, 'RLS lapis kedua harus menyala.');
        $this->assertSame('c', DB::selectOne("SELECT confdeltype FROM pg_constraint WHERE conrelid = 'user_owned_items'::regclass AND contype = 'f'")->confdeltype, 'Barang ikut terhapus bersama akunnya.');
        $this->assertSame('n', DB::selectOne("SELECT confdeltype FROM pg_constraint WHERE conrelid = 'trip_preparation_items'::regclass AND contype = 'f' AND conname LIKE '%user_owned_item_id%'")->confdeltype, 'Butir trip tetap ada; tautannya menjadi null.');
    }

    public function test_one_name_per_hiker_regardless_of_case(): void
    {
        $user = User::factory()->create();
        $user->ownedItems()->create(['name' => 'Headlamp']);
        User::factory()->create()->ownedItems()->create(['name' => 'headlamp']);

        $this->expectException(UniqueConstraintViolationException::class);
        $user->ownedItems()->create(['name' => 'HEADLAMP']);
    }

    public function test_deleting_an_item_unlinks_trip_items_and_deleting_the_account_removes_items(): void
    {
        $user = User::factory()->create();
        $barang = $user->ownedItems()->create(['name' => 'Gaiter']);
        $trip = TripPlan::factory()->create(['user_id' => $user->id]);
        $butir = TripPreparationItem::create([
            'trip_plan_id' => $trip->id,
            'user_owned_item_id' => $barang->id,
            'category' => 'EQUIPMENT',
            'label' => 'Gaiter',
            'is_critical' => false,
            'status' => 'NOT_CONFIRMED',
        ]);

        $barang->delete();
        $this->assertNull($butir->fresh()->user_owned_item_id);

        $user->ownedItems()->create(['name' => 'Matras']);
        $user->delete();
        $this->assertSame(0, (int) DB::selectOne('SELECT count(*) AS n FROM user_owned_items')->n);
    }
}
```

Tulis ulang `tests/Feature/OwnedEquipmentTest.php` utuh:

```php
<?php

namespace Tests\Feature;

use App\Enums\PreparationCategory;
use App\Enums\PreparationStatus;
use App\Models\TripPlan;
use App\Models\User;
use App\Models\UserOwnedItem;
use App\Services\PreparationService;
use App\Services\Trips\IsiAwalPerlengkapan;
use Database\Seeders\PreparationTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Perlengkapan pribadi di Profil (spec v3 bagian 6.15; rencana induk W7 butir 4; ruling W7-R8).
 *
 * Menggantikan test rintisan yang hanya membuka `/` (spec v3 bagian 3.5). Daftarnya milik
 * pendaki yang masuk, dirapikan dan tidak kembar, dan mengisi awal daftar persiapan trip sebagai
 * butir yang tetap harus dikonfirmasi per trip (BR-10).
 */
class OwnedEquipmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_hiker_adds_renames_and_removes_an_item(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('profile.items.store'), ['nama' => '  Headlamp   cadangan '])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile'))
            ->assertSessionHas('kabar-profil', ['bagian' => 'perlengkapan', 'teks' => '"Headlamp cadangan" ditambahkan ke perlengkapan pribadi.']);
        $barang = $user->ownedItems()->sole();
        $this->assertSame('Headlamp cadangan', $barang->name);

        $this->actingAs($user)->put(route('profile.items.update', $barang), ['nama' => 'Headlamp'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('kabar-profil', ['bagian' => 'perlengkapan', 'teks' => 'Nama barang diganti menjadi "Headlamp".']);
        $this->assertSame('Headlamp', $barang->fresh()->name);

        $this->actingAs($user)->delete(route('profile.items.destroy', $barang))
            ->assertRedirect(route('profile'))
            ->assertSessionHas('kabar-profil', ['bagian' => 'perlengkapan', 'teks' => '"Headlamp" dihapus dari perlengkapan pribadi. Daftar persiapan trip yang sudah ada tidak berubah.']);
        $this->assertSame(0, UserOwnedItem::count());
    }

    public function test_duplicates_regardless_of_case_and_unusable_names_are_refused(): void
    {
        $user = User::factory()->create();
        $user->ownedItems()->create(['name' => 'Jas hujan']);
        $kaos = $user->ownedItems()->create(['name' => 'Kaos cadangan']);

        $this->actingAs($user)->post(route('profile.items.store'), ['nama' => 'jas  HUJAN'])
            ->assertSessionHasErrors(['nama' => '"jas HUJAN" sudah ada di perlengkapan pribadi Anda.']);
        $this->actingAs($user)->put(route('profile.items.update', $kaos), ['nama' => 'JAS HUJAN'])
            ->assertSessionHasErrors('nama');
        $this->actingAs($user)->put(route('profile.items.update', $kaos), ['nama' => 'kaos Cadangan'])
            ->assertSessionHasNoErrors();
        $this->actingAs($user)->post(route('profile.items.store'), ['nama' => PreparationService::PERMIT_LABEL])
            ->assertSessionHasErrors(['nama' => 'Nama itu dipakai butir izin di daftar persiapan. Pakai nama lain.']);
        $this->actingAs($user)->post(route('profile.items.store'), ['nama' => '   '])
            ->assertSessionHasErrors(['nama' => 'Tulis nama barangnya.']);
        $this->actingAs($user)->post(route('profile.items.store'), ['nama' => ' a '])
            ->assertSessionHasErrors(['nama' => 'Nama barang paling sedikit 2 karakter.']);
        $this->actingAs($user)->post(route('profile.items.store'), ['nama' => str_repeat('x', 61)])
            ->assertSessionHasErrors(['nama' => 'Nama barang paling banyak 60 karakter.']);

        $this->assertSame(['Jas hujan', 'kaos Cadangan'], $user->ownedItems()->orderBy('id')->pluck('name')->all());
    }

    /**
     * Daftar dibatasi untuk barang baru saja; mengganti nama tetap dapat di batas.
     */
    public function test_the_list_stops_at_its_limit(): void
    {
        $user = User::factory()->create();
        $user->ownedItems()->createMany(collect(range(1, UserOwnedItem::BATAS))->map(fn (int $i) => ['name' => 'Barang '.$i])->all());

        $this->actingAs($user)->post(route('profile.items.store'), ['nama' => 'Satu lagi'])
            ->assertSessionHasErrors(['nama' => 'Perlengkapan pribadi paling banyak 40 barang. Hapus yang tidak lagi Anda bawa.']);
        $this->actingAs($user)->put(route('profile.items.update', $user->ownedItems()->first()), ['nama' => 'Barang pertama'])
            ->assertSessionHasNoErrors();

        $this->assertSame(UserOwnedItem::BATAS, $user->ownedItems()->count());
    }

    /**
     * Pemilik dari sesi: user_id di isian diabaikan, dan barang orang lain tidak dapat diganti
     * atau dihapus walau id-nya diketahui (PRD §119).
     */
    public function test_items_belong_to_the_signed_in_hiker_only(): void
    {
        $saya = User::factory()->create();
        $orangLain = User::factory()->create();
        $miliknya = $orangLain->ownedItems()->create(['name' => 'Kompor lipat']);

        $this->actingAs($saya)->post(route('profile.items.store'), ['nama' => 'Matras', 'user_id' => $orangLain->id])->assertSessionHasNoErrors();
        $this->actingAs($saya)->put(route('profile.items.update', $miliknya), ['nama' => 'Diambil alih'])->assertForbidden();
        $this->actingAs($saya)->delete(route('profile.items.destroy', $miliknya))->assertForbidden();

        $this->assertSame(['Matras'], $saya->ownedItems()->pluck('name')->all());
        $this->assertSame(['Kompor lipat'], $orangLain->ownedItems()->pluck('name')->all());

        auth()->logout();
        $this->post(route('profile.items.store'), ['nama' => 'Tamu'])->assertRedirect(route('login'));
        $this->assertSame(2, UserOwnedItem::count());
    }

    /**
     * Ujung ke ujung lewat halaman Persiapan: daftar trip baru berisi perlengkapan pribadi
     * pemiliknya sebagai butir Perlengkapan yang belum dikonfirmasi dan tidak kritis, dengan
     * keterangan asalnya; perlengkapan orang lain tidak masuk.
     */
    public function test_owned_items_prefill_a_new_trip_preparation_list(): void
    {
        $this->seed(PreparationTemplateSeeder::class);
        $user = User::factory()->create();
        $user->ownedItems()->createMany([['name' => 'Trekking pole'], ['name' => 'Botol air 1 liter']]);
        User::factory()->create()->ownedItems()->create(['name' => 'Kompor lipat']);
        $trip = TripPlan::factory()->create(['user_id' => $user->id]);

        $kategori = $this->actingAs($user)->get(route('trips.preparation', $trip))->assertOk()->viewData('page')['props']['kategori'];

        $perlengkapan = collect($kategori)->firstWhere('kunci', PreparationCategory::EQUIPMENT->value)['items'];
        $milikSaya = collect($perlengkapan)->where('keterangan', IsiAwalPerlengkapan::KETERANGAN)->values();
        $this->assertEqualsCanonicalizing(['Trekking pole', 'Botol air 1 liter'], $milikSaya->pluck('label')->all());
        $this->assertTrue($milikSaya->every(fn (array $b) => $b['status'] === PreparationStatus::NOT_CONFIRMED->value && $b['kritis'] === false));
        $this->assertFalse(collect($kategori)->flatMap(fn (array $k) => $k['items'])->contains('label', 'Kompor lipat'));
    }

    /**
     * Daftar persiapan trip adalah catatan trip itu: mengganti nama atau menghapus barang di
     * Profil tidak mengubah butir yang sudah ada, dan "Perbarui daftar" tidak menambahkannya
     * kembali.
     */
    public function test_changing_the_profile_list_leaves_existing_trip_lists_alone(): void
    {
        $this->seed(PreparationTemplateSeeder::class);
        $user = User::factory()->create();
        $tenda = $user->ownedItems()->create(['name' => 'Tenda dua orang']);
        $trip = TripPlan::factory()->create(['user_id' => $user->id]);
        app(PreparationService::class)->generateFor($trip);
        $butir = $trip->preparationItems()->where('user_owned_item_id', $tenda->id)->sole();

        $this->actingAs($user)->put(route('profile.items.update', $tenda), ['nama' => 'Tenda ultralight'])->assertSessionHasNoErrors();
        $this->actingAs($user)->post(route('trips.preparation.regenerate', $trip))->assertRedirect();
        $this->assertSame('Tenda dua orang', $butir->fresh()->label);
        $this->assertSame(0, $trip->preparationItems()->where('label', 'Tenda ultralight')->count(), 'Barang yang sudah tertaut tidak ditambahkan lagi dengan nama barunya.');

        $this->actingAs($user)->delete(route('profile.items.destroy', $tenda))->assertRedirect();
        $this->actingAs($user)->post(route('trips.preparation.regenerate', $trip))->assertRedirect();
        $this->assertNull($butir->fresh()->user_owned_item_id, 'Butirnya tetap ada, tautannya dilepas.');
        $this->assertSame(1, $trip->preparationItems()->where('label', 'Tenda dua orang')->count());
    }
}
```

`tests/Unit/PreparationServiceTest.php`:

1. Ganti:

```php
namespace Tests\Unit;

use App\Enums\PreparationStatus;
```

dengan:

```php
namespace Tests\Unit;

use App\Enums\PreparationCategory;
use App\Enums\PreparationStatus;
```

2. Ganti:

```php
use App\Models\TripPlan;
use App\Services\PreparationService;
use Database\Seeders\PreparationTemplateSeeder;
```

dengan:

```php
use App\Models\TripPlan;
use App\Models\User;
use App\Services\PreparationService;
use App\Services\Trips\IsiAwalPerlengkapan;
use Database\Seeders\PreparationTemplateSeeder;
```

3. Ganti:

```php
    /**
     * Prakiraan BMKG untuk tanggal trip terbit tiga hari sebelumnya (config
```

dengan:

```php
    /**
     * Perlengkapan pribadi mengisi awal daftar sebagai butir Perlengkapan yang belum dikonfirmasi
     * dan tidak kritis (ruling W7-R8, BR-10), sehingga tidak menambah hitungan butir kritis.
     */
    public function test_owned_items_prefill_the_list_unconfirmed_and_not_critical(): void
    {
        $user = User::factory()->create();
        $user->ownedItems()->createMany([['name' => 'Trekking pole'], ['name' => 'Matras']]);
        $trip = TripPlan::factory()->create(['user_id' => $user->id]);

        $items = app(PreparationService::class)->generateFor($trip);
        $milik = $items->whereNotNull('user_owned_item_id')->values();

        $this->assertEqualsCanonicalizing(['Trekking pole', 'Matras'], $milik->pluck('label')->all());
        $this->assertTrue($milik->every(fn ($item) => $item->category === PreparationCategory::EQUIPMENT
            && $item->is_critical === false
            && $item->status === PreparationStatus::NOT_CONFIRMED
            && $item->description === IsiAwalPerlengkapan::KETERANGAN));
        $this->assertSame(
            $items->where('is_critical', true)->count(),
            PreparationService::kritis($trip->fresh()->preparationItems)['berlaku'],
        );
    }

    /**
     * Membuat ulang daftar tidak menggandakan barang, mempertahankan statusnya, menambahkan
     * barang yang baru dimiliki, dan melewati barang yang namanya sudah ada di daftar.
     */
    public function test_regenerating_neither_duplicates_owned_items_nor_resets_them(): void
    {
        $user = User::factory()->create();
        $pole = $user->ownedItems()->create(['name' => 'Trekking pole']);
        $user->ownedItems()->create(['name' => 'referensi NAVIGASI jalur']);
        $trip = TripPlan::factory()->create(['user_id' => $user->id]);
        $service = app(PreparationService::class);

        $service->generateFor($trip);
        $service->updateStatus($trip->preparationItems()->where('user_owned_item_id', $pole->id)->sole(), PreparationStatus::CONFIRMED);
        $user->ownedItems()->create(['name' => 'Gaiter']);
        $items = $service->generateFor($trip->fresh());

        $this->assertSame(1, $items->where('user_owned_item_id', $pole->id)->count());
        $this->assertSame(PreparationStatus::CONFIRMED, $items->firstWhere('user_owned_item_id', $pole->id)->status);
        $this->assertSame(1, $items->where('label', 'Gaiter')->count());
        $this->assertSame(0, $items->where('label', 'referensi NAVIGASI jalur')->count(), 'Nama yang sama dengan butir templat tidak ditambahkan dua kali.');
    }

    /**
     * Prakiraan BMKG untuk tanggal trip terbit tiga hari sebelumnya (config
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='OwnedEquipment|PreparationService|UserOwnedItemSchema|PreparationUndo'`
Expected: FAIL. Pada simulasi rencana: 37 test, 26 lulus, 8 error, 3 dilewati. Yang merah: keenam test `OwnedEquipmentTest` dan dua test baru `PreparationServiceTest` (rute `profile.items.*`, relasi `ownedItems()`, dan isi awal belum ada). `PreparationUndoTest` sudah lulus karena urungkan tidak berubah. 3 test Spatial melewati dirinya tanpa `SPATIAL_TEST_DSN`.

- [ ] **Step 3: Tulis implementasinya.** Buat berkas dengan
`DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:model --no-interaction UserOwnedItem --factory`,
`... make:policy --no-interaction UserOwnedItemPolicy --model=UserOwnedItem`,
`... make:controller --no-interaction Profile/OwnedItemController`,
`... make:request --no-interaction Profile/SaveOwnedItemRequest`,
`... make:class --no-interaction Services/Trips/IsiAwalPerlengkapan`,
`... make:migration --no-interaction complete_user_owned_items_table`, dan
`... make:migration --no-interaction add_owned_item_to_trip_preparation_items_table` (ganti nama kedua berkasnya menjadi
`2026_10_08_000300_complete_user_owned_items_table.php` dan `2026_10_08_000400_add_owned_item_to_trip_preparation_items_table.php`),
lalu isinya diganti persis. Rintisan `2026_09_25_000921_create_user_owned_items_table.php` tidak disunting.

Buat `app/Http/Controllers/Profile/OwnedItemController.php`:

```php
<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\SaveOwnedItemRequest;
use App\Models\UserOwnedItem;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

/**
 * Perlengkapan pribadi di Profil (spec v3 bagian 6.15; rencana induk W7 butir 4; ruling W7-R8).
 * Pemilik dari sesi, barang dari rute dan Policy. Daftar ini mengisi awal persiapan trip;
 * mengubah atau menghapus barang tidak mengubah daftar persiapan trip yang sudah ada.
 */
class OwnedItemController extends Controller
{
    public function store(SaveOwnedItemRequest $request): RedirectResponse
    {
        $nama = $request->nama();

        $this->tanpaKembar($nama, fn () => $request->user()->ownedItems()->create(['name' => $nama]));

        return $this->keProfil('"'.$nama.'" ditambahkan ke perlengkapan pribadi.');
    }

    public function update(SaveOwnedItemRequest $request, UserOwnedItem $item): RedirectResponse
    {
        $nama = $request->nama();

        $this->tanpaKembar($nama, fn () => $item->update(['name' => $nama]));

        return $this->keProfil('Nama barang diganti menjadi "'.$nama.'".');
    }

    public function destroy(UserOwnedItem $item): RedirectResponse
    {
        $this->authorize('delete', $item);

        $item->delete();

        return $this->keProfil('"'.$item->name.'" dihapus dari perlengkapan pribadi. Daftar persiapan trip yang sudah ada tidak berubah.');
    }

    /**
     * Dua kiriman bersamaan dengan nama yang sama lolos validasi berdua; kunci unik basis data
     * menolak yang kedua, dan penolakannya dikembalikan sebagai galat isian, bukan galat server.
     */
    private function tanpaKembar(string $nama, callable $tulis): void
    {
        try {
            $tulis();
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['nama' => '"'.$nama.'" sudah ada di perlengkapan pribadi Anda.']);
        }
    }

    private function keProfil(string $kabar): RedirectResponse
    {
        return redirect()->route('profile')->with('kabar-profil', ['bagian' => 'perlengkapan', 'teks' => $kabar]);
    }
}
```

Buat `app/Http/Requests/Profile/SaveOwnedItemRequest.php`:

```php
<?php

namespace App\Http\Requests\Profile;

use App\Models\UserOwnedItem;
use App\Services\PreparationService;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Satu barang perlengkapan pribadi (spec v3 bagian 6.15; ruling W7-R8), untuk menambah maupun
 * mengganti nama. Hanya `nama` yang dibaca, dirapikan spasinya, dan tidak boleh kembar dengan
 * barang lain milik pendaki yang sama tanpa memandang huruf besar. Pemilik selalu dari sesi;
 * barang orang lain ditolak Policy.
 */
class SaveOwnedItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $barang = $this->route('item');

        if ($barang === null) {
            return $this->user() !== null;
        }

        return $barang instanceof UserOwnedItem && $this->user()?->can('update', $barang) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'min:2', 'max:60', function (string $atribut, mixed $nilai, Closure $gagal): void {
                $nama = mb_strtolower((string) $nilai);

                if ($nama === mb_strtolower(PreparationService::PERMIT_LABEL)) {
                    $gagal('Nama itu dipakai butir izin di daftar persiapan. Pakai nama lain.');

                    return;
                }

                $barang = $this->route('item');
                $kembar = UserOwnedItem::query()
                    ->where('user_id', $this->user()->id)
                    ->when($barang instanceof UserOwnedItem, fn ($q) => $q->whereKeyNot($barang->id))
                    ->whereRaw('lower(name) = ?', [$nama])
                    ->exists();

                if ($kembar) {
                    $gagal('"'.$nilai.'" sudah ada di perlengkapan pribadi Anda.');
                }
            }],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Tulis nama barangnya.',
            'nama.min' => 'Nama barang paling sedikit 2 karakter.',
            'nama.max' => 'Nama barang paling banyak 60 karakter.',
        ];
    }

    /**
     * Batas daftar hanya berlaku untuk barang baru.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->route('item') === null && UserOwnedItem::query()->where('user_id', $this->user()->id)->count() >= UserOwnedItem::BATAS) {
                $validator->errors()->add('nama', 'Perlengkapan pribadi paling banyak '.UserOwnedItem::BATAS.' barang. Hapus yang tidak lagi Anda bawa.');
            }
        }];
    }

    public function nama(): string
    {
        return (string) $this->validated('nama');
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('nama'))) {
            $this->merge(['nama' => trim((string) preg_replace('/\s+/u', ' ', $this->input('nama')))]);
        }
    }
}
```

Buat `app/Models/UserOwnedItem.php`:

```php
<?php

namespace App\Models;

use Database\Factories\UserOwnedItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu barang perlengkapan pribadi (spec v3 bagian 6.15, ruling W7-R8). Pemiliknya selalu dari
 * sesi; hanya nama yang dapat diisi.
 */
#[Fillable(['name'])]
class UserOwnedItem extends Model
{
    /** @use HasFactory<UserOwnedItemFactory> */
    use HasFactory;

    /** Batas daftar per pendaki: cukup untuk satu ransel lengkap, dan menahan baris tanpa ujung. */
    public const BATAS = 40;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

Buat `app/Policies/UserOwnedItemPolicy.php`:

```php
<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserOwnedItem;

/**
 * Perlengkapan pribadi milik satu pendaki (rencana induk W7 butir 4). Mengetahui id barang tidak
 * sama dengan memilikinya (PRD §119).
 */
class UserOwnedItemPolicy
{
    public function update(User $user, UserOwnedItem $barang): bool
    {
        return $barang->user_id === $user->id;
    }

    public function delete(User $user, UserOwnedItem $barang): bool
    {
        return $this->update($user, $barang);
    }
}
```

Buat `app/Services/Trips/IsiAwalPerlengkapan.php`:

```php
<?php

namespace App\Services\Trips;

use App\Enums\PreparationCategory;
use App\Enums\PreparationStatus;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Models\UserOwnedItem;

/**
 * Perlengkapan pribadi mengisi awal daftar persiapan (spec v3 bagian 6.10 dan 6.15; ruling
 * W7-R8): setiap barang milik pemilik trip masuk sebagai butir Perlengkapan yang belum
 * dikonfirmasi dan tidak kritis, karena memiliki barang tidak sama dengan membawanya di trip ini
 * (BR-10). Barang yang sudah tertaut, atau yang namanya sama dengan butir yang sudah ada, tidak
 * ditambahkan lagi, jadi "Perbarui daftar" tidak menggandakan apa pun. Hanya barang milik pemilik
 * trip yang dibaca.
 */
final class IsiAwalPerlengkapan
{
    public const KETERANGAN = 'Dari perlengkapan pribadi Anda. Tetap dikonfirmasi untuk trip ini.';

    public function isi(TripPlan $trip): void
    {
        $barang = UserOwnedItem::query()->where('user_id', $trip->user_id)->orderBy('name')->orderBy('id')->get(['id', 'name']);

        if ($barang->isEmpty()) {
            return;
        }

        $ada = $trip->preparationItems()->get(['id', 'label', 'user_owned_item_id']);
        $tertaut = $ada->pluck('user_owned_item_id')->filter()->all();
        $label = $ada->map(fn (TripPreparationItem $b) => mb_strtolower($b->label))->all();

        foreach ($barang as $satu) {
            if (in_array($satu->id, $tertaut, true) || in_array(mb_strtolower($satu->name), $label, true)) {
                continue;
            }

            TripPreparationItem::create([
                'trip_plan_id' => $trip->id,
                'user_owned_item_id' => $satu->id,
                'category' => PreparationCategory::EQUIPMENT->value,
                'label' => $satu->name,
                'description' => self::KETERANGAN,
                'is_critical' => false,
                'status' => PreparationStatus::NOT_CONFIRMED->value,
            ]);
        }
    }
}
```

Buat `database/factories/UserOwnedItemFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserOwnedItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserOwnedItem>
 */
class UserOwnedItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->unique()->randomElement(['Headlamp', 'Jas hujan', 'Jaket hangat', 'Sepatu mendaki', 'Kompor lipat', 'Matras', 'Trekking pole', 'Botol air 1 liter']),
        ];
    }
}
```

Buat `database/migrations/2026_10_08_000300_complete_user_owned_items_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Perlengkapan pribadi (spec v3 bagian 6.15 dan 9.3; rencana induk W7 butir 4): daftar barang
 * milik pendaki yang mengisi awal daftar persiapan setiap trip barunya, tetap dikonfirmasi per
 * trip (spec bagian 6.10). Satu nama sekali per pendaki tanpa memandang huruf besar; hapus akun
 * menghapus daftarnya (cascade, kebijakan data pribadi bagian 2).
 *
 * Migrasi aditif ini mengisi tabel rintisan 2026_09_25_000921 yang hanya berisi id dan
 * timestamps; rintisan itu sengaja tidak disunting (ruling koordinator W7). Rintisan tidak pernah
 * ditulis kode apa pun, jadi tabelnya kosong; pemeriksaannya `SELECT count(*) FROM
 * user_owned_items` sebelum migrasi dijalankan, karena kolom user_id yang wajib gagal dipasang
 * di atas baris tanpa pemilik.
 *
 * Lapis kedua penutupan PostgREST (2026_09_20_000100): RLS tanpa policy menolak REST API,
 * sedangkan Laravel sebagai pemilik tabel tidak terpengaruh. Rintisannya tidak menyalakannya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_owned_items', function (Blueprint $table) {
            $table->foreignId('user_id')->after('id')->constrained()->cascadeOnDelete();
            $table->string('name', 60)->after('user_id');
        });

        // Satu nama sekali per pendaki tanpa memandang huruf besar: "Headlamp" dan "headlamp"
        // adalah barang yang sama. Indeks ekspresi ini berlaku di PostgreSQL maupun SQLite.
        DB::statement('CREATE UNIQUE INDEX user_owned_items_unik ON user_owned_items (user_id, lower(name))');

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE public."user_owned_items" ENABLE ROW LEVEL SECURITY');
        }
    }

    public function down(): void
    {
        DB::statement('DROP INDEX user_owned_items_unik');

        Schema::table('user_owned_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn('name');
        });
    }
};
```

Buat `database/migrations/2026_10_08_000400_add_owned_item_to_trip_preparation_items_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tautan butir persiapan ke perlengkapan pribadi yang mengisinya (ruling W7-R8): supaya
 * "Perbarui daftar" tidak menggandakan barang yang sama, dan supaya butirnya dapat menyebut
 * asalnya. Menghapus barang dari Profil tidak menghapus butir trip yang sudah ada (nullOnDelete):
 * daftar persiapan trip adalah catatan trip itu. Aditif dan boleh kosong untuk butir lama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_preparation_items', function (Blueprint $table) {
            $table->foreignId('user_owned_item_id')->nullable()->after('preparation_item_id')->constrained('user_owned_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('trip_preparation_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_owned_item_id');
        });
    }
};
```

`app/Models/TripPreparationItem.php`:

1. Ganti:

```php
#[Fillable([
    'trip_plan_id', 'preparation_item_id', 'category', 'label',
    'description', 'is_critical', 'status', 'status_updated_at',
```

dengan:

```php
#[Fillable([
    'trip_plan_id', 'preparation_item_id', 'user_owned_item_id', 'category', 'label',
    'description', 'is_critical', 'status', 'status_updated_at',
```

`app/Models/User.php`:

1. Ganti:

```php
    }

    public function isAdmin(): bool
```

dengan:

```php
    }

    public function ownedItems(): HasMany
    {
        return $this->hasMany(UserOwnedItem::class);
    }

    public function isAdmin(): bool
```

`app/Services/PreparationService.php`:

1. Ganti:

```php
use App\Models\TripPreparationItem;
use App\Support\Timezone;
```

dengan:

```php
use App\Models\TripPreparationItem;
use App\Services\Trips\IsiAwalPerlengkapan;
use App\Support\Timezone;
```

2. Ganti:

```php
    public const PERMIT_LABEL = 'Booking izin pendakian (SIMAKSI)';

    public function __construct(private readonly PermitService $permits) {}

    /**
```

dengan:

```php
    public const PERMIT_LABEL = 'Booking izin pendakian (SIMAKSI)';

    public function __construct(
        private readonly PermitService $permits,
        private readonly IsiAwalPerlengkapan $perlengkapan,
    ) {}

    /**
```

3. Ganti:

```php
            $this->addPermitItem($trip);

            return $trip->preparationItems()->get();
```

dengan:

```php
            $this->addPermitItem($trip);
            // Perlengkapan pribadi pemilik trip mengisi awal daftar, tetap belum dikonfirmasi (W7-R8).
            $this->perlengkapan->isi($trip);

            return $trip->preparationItems()->get();
```

`routes/web.php`:

1. Ganti:

```php
use App\Http\Controllers\Onboarding\ProfileSetupController;
use App\Http\Controllers\Profile\ProfileController;
```

dengan:

```php
use App\Http\Controllers\Onboarding\ProfileSetupController;
use App\Http\Controllers\Profile\OwnedItemController;
use App\Http\Controllers\Profile\ProfileController;
```

2. Ganti:

```php
    Route::delete('profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Profile -> goal -> route fit -> plan -> prepare -> check -> hike -> report -> history.
```

dengan:

```php
    Route::delete('profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    // Perlengkapan pribadi (W7): pemilik dari sesi, barang dari rute dan Policy.
    Route::post('profile/perlengkapan', [OwnedItemController::class, 'store'])->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,perlengkapan')->name('profile.items.store');
    Route::put('profile/perlengkapan/{item}', [OwnedItemController::class, 'update'])->whereNumber('item')->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,perlengkapan')->name('profile.items.update');
    Route::delete('profile/perlengkapan/{item}', [OwnedItemController::class, 'destroy'])->whereNumber('item')->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,perlengkapan')->name('profile.items.destroy');

    // Profile -> goal -> route fit -> plan -> prepare -> check -> hike -> report -> history.
```

- [ ] **Step 4: Build dan tipe.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `npx tsc --noEmit`
Expected: `✓ built`; keluaran Wayfinder memuat `profile.items.store`, `update`, dan `destroy`; tsc tanpa keluaran galat.

- [ ] **Step 5: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Http/Controllers/Profile/OwnedItemController.php app/Http/Requests/Profile/SaveOwnedItemRequest.php app/Models/UserOwnedItem.php app/Policies/UserOwnedItemPolicy.php app/Services/Trips/IsiAwalPerlengkapan.php app/Models/TripPreparationItem.php app/Models/User.php app/Services/PreparationService.php database/factories/UserOwnedItemFactory.php database/migrations/2026_10_08_000300_complete_user_owned_items_table.php database/migrations/2026_10_08_000400_add_owned_item_to_trip_preparation_items_table.php routes/web.php tests/Feature/OwnedEquipmentTest.php tests/Unit/PreparationServiceTest.php tests/Spatial/UserOwnedItemSchemaTest.php`, lalu `php artisan test --compact --filter='OwnedEquipment|PreparationService|UserOwnedItemSchema|PreparationUndo'`
Expected: PASS. Pada simulasi rencana: 37 test, 34 lulus, 3 dilewati.

- [ ] **Step 6: Suite, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 2.093 test, 2.091 lulus, 2 dilewati (sebelum tugas: 2.086).

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `php artisan test --compact --testsuite=Pratinjau`
Expected: PASS. Pada simulasi rencana: 30 test lulus.

- [ ] **Step 7: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 6: Catatan per butir persiapan

Titipan W4 "Ke W7" (prototype 09 "Dibawa Dimas"); ruling W7-R2, W7-R9, dan W7-R21. Migrasi aditif `000500` menambah
`trip_preparation_items.note` (`varchar(80)`). Pemilik trip menulis, merapikan, dan mengosongkan catatan satu butir
Perlengkapan atau Logistik selama trip belum selesai atau dibatalkan, lewat `PUT trips/{trip}/preparation/{item}/catatan`
berbatas laju `catatan-butir`. Hanya kolom catatan yang berubah; catatan tidak dihitung sebagai konfirmasi (BR-10). Butir
dicari di dalam trip di alamatnya, jadi butir trip lain menjawab 404. Di halaman Persiapan catatan tampil "Catatan: ..."
di bawah butirnya, dengan "Tambah catatan" atau "Ubah" yang membuka isian sebaris; fokus kembali ke pemicunya sesudah
disimpan atau dibatalkan. Komponen membaca prop `catatanButir` sendiri lewat `usePage`, jadi jangkar di `Preparation.tsx`
hanya baris impor dan satu sisipan sesudah tautan butir.

**Files:**
- Create: `app/Http/Controllers/Trips/PreparationNoteController.php`, `app/Http/Requests/Trips/UpdatePreparationNoteRequest.php`, `app/Services/Trips/CatatanButir.php`, `database/migrations/2026_10_08_000500_add_note_to_trip_preparation_items_table.php`, `resources/js/components/trips/CatatanButir.tsx`
- Modify: `app/Models/TripPreparationItem.php`, `app/Services/Trips/HalamanPersiapan.php`, `resources/js/pages/Trips/Preparation.tsx`, `routes/web.php`
- Test: `tests/Feature/PreparationNoteTest.php` (baru), `tests/Spatial/PreparationItemNoteSchemaTest.php` (baru, PostgreSQL), `tests/Pratinjau/HalamanPratinjauTest.php`

**Interfaces:**
- Consumes: `TripPlanPolicy` (`update`), `TripStatus::isActive()`, `PreparationCategory`, `HalamanPersiapan::props()` (berakhir dengan kunci `pengingat`), `Preparation.tsx` (baris `import { KepalaTrip }` dan blok `{item.tautan ? (...) : null}`); primitif `TextInput`, `InputError`, `Button`.
- Produces:
  - kolom `trip_preparation_items.note` (`varchar(80)`, boleh kosong) di `$fillable` `TripPreparationItem`;
  - `CatatanButir::BATAS` (80), `CatatanButir::KATEGORI` (`EQUIPMENT`, `LOGISTICS`), `CatatanButir::dapatDicatat(TripPreparationItem): bool`, `CatatanButir::penolakan(TripPlan, TripPreparationItem): ?string`, `CatatanButir::untukHalaman(TripPlan): array{dapatDiubah: bool, bisa: list<int>, isi: array<int, string>, batas: int}`;
  - prop `catatanButir` di halaman Persiapan; rute `trips.preparation.note` (berbatas laju `catatan-butir`), kembali ke `trips.preparation`; `UpdatePreparationNoteRequest::catatan(): ?string` (spasi dirapikan, kosong menjadi null);
  - TypeScript `CatatanButirData` dan komponen `CatatanButir({ itemId })` di `components/trips/CatatanButir.tsx`.

- [ ] **Step 1: Tulis test yang gagal.** Buat berkas test baru dengan `make:test`; test Spatial dibuat langsung di
`tests/Spatial/`. Ganti isinya persis.

Buat `tests/Feature/PreparationNoteTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\PreparationCategory;
use App\Enums\PreparationStatus;
use App\Enums\TripStatus;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Models\User;
use App\Services\PreparationService;
use Database\Seeders\PreparationTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Catatan per butir persiapan (titipan W4 ke W7; prototype 09 "Dibawa Dimas"; ruling W7-R9).
 * Hanya pemilik trip, hanya butir Perlengkapan dan Logistik, hanya selama trip aktif, dan hanya
 * kolom catatan yang berubah.
 */
class PreparationNoteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PreparationTemplateSeeder::class);
    }

    /**
     * Trip milik seorang pendaki dengan daftar persiapan yang sudah dibangkitkan.
     *
     * @return array{0: User, 1: TripPlan}
     */
    private function tripPendaki(): array
    {
        $pemilik = User::factory()->create();
        $trip = TripPlan::factory()->create(['user_id' => $pemilik->id]);
        app(PreparationService::class)->generateFor($trip);

        return [$pemilik, $trip];
    }

    private function butir(TripPlan $trip, PreparationCategory $kategori): TripPreparationItem
    {
        return $trip->preparationItems()->where('category', $kategori->value)->orderBy('id')->firstOrFail();
    }

    public function test_the_owner_writes_tidies_and_clears_a_note(): void
    {
        [$pemilik, $trip] = $this->tripPendaki();
        $tenda = $this->butir($trip, PreparationCategory::EQUIPMENT);

        $this->actingAs($pemilik)->put(route('trips.preparation.note', [$trip, $tenda->id]), ['catatan' => "  Dibawa   Dimas\n"])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('trips.preparation', $trip));
        $this->assertSame('Dibawa Dimas', $tenda->fresh()->note);

        $catatan = $this->actingAs($pemilik)->get(route('trips.preparation', $trip))->assertOk()->viewData('page')['props']['catatanButir'];
        $this->assertSame('Dibawa Dimas', $catatan['isi'][$tenda->id]);
        $this->assertTrue($catatan['dapatDiubah']);
        $this->assertSame(80, $catatan['batas']);

        $this->actingAs($pemilik)->put(route('trips.preparation.note', [$trip, $tenda->id]), ['catatan' => '   '])->assertSessionHasNoErrors();
        $this->assertNull($tenda->fresh()->note, 'Isian kosong menghapus catatan.');
    }

    /**
     * Hanya kolom catatan yang berubah: status, label, kritis, dan kategori dari isian diabaikan,
     * dan catatan tidak dihitung sebagai konfirmasi (BR-10).
     */
    public function test_a_note_changes_nothing_but_the_note(): void
    {
        [$pemilik, $trip] = $this->tripPendaki();
        $butir = $this->butir($trip, PreparationCategory::LOGISTICS);
        $sebelum = $butir->only(['label', 'status', 'is_critical', 'category']);

        $this->actingAs($pemilik)->put(route('trips.preparation.note', [$trip, $butir->id]), [
            'catatan' => 'Travel dari Bogor jam 04.00',
            'status' => PreparationStatus::CONFIRMED->value,
            'label' => 'Diganti',
            'is_critical' => false,
            'category' => PreparationCategory::WEATHER->value,
        ])->assertSessionHasNoErrors();

        $this->assertSame($sebelum, $butir->fresh()->only(['label', 'status', 'is_critical', 'category']));
        $this->assertSame('Travel dari Bogor jam 04.00', $butir->fresh()->note);
    }

    public function test_long_notes_other_categories_and_closed_trips_are_refused(): void
    {
        [$pemilik, $trip] = $this->tripPendaki();
        $perlengkapan = $this->butir($trip, PreparationCategory::EQUIPMENT);
        $cuaca = $this->butir($trip, PreparationCategory::WEATHER);

        $this->actingAs($pemilik)->put(route('trips.preparation.note', [$trip, $perlengkapan->id]), ['catatan' => str_repeat('a', 81)])
            ->assertSessionHasErrors(['catatan' => 'Catatan paling banyak 80 karakter.']);
        $this->actingAs($pemilik)->put(route('trips.preparation.note', [$trip, $perlengkapan->id]), [])
            ->assertSessionHasErrors(['catatan' => 'Isian catatan tidak terkirim. Muat ulang halaman lalu coba lagi.']);
        $this->actingAs($pemilik)->put(route('trips.preparation.note', [$trip, $cuaca->id]), ['catatan' => 'Cek Jumat malam'])
            ->assertSessionHasErrors(['catatan' => 'Catatan hanya untuk butir Perlengkapan dan Logistik.']);

        $trip->update(['status' => TripStatus::CANCELLED->value]);
        $this->actingAs($pemilik)->put(route('trips.preparation.note', [$trip, $perlengkapan->id]), ['catatan' => 'Dibawa Dimas'])
            ->assertSessionHasErrors(['catatan' => 'Catatan butir tidak dapat diubah lagi: trip berstatus Dibatalkan.']);

        $this->assertSame(0, TripPreparationItem::whereNotNull('note')->count());
        $catatan = $this->actingAs($pemilik)->get(route('trips.preparation', $trip))->viewData('page')['props']['catatanButir'];
        $this->assertFalse($catatan['dapatDiubah']);
    }

    /**
     * Hanya butir Perlengkapan dan Logistik yang ditawari catatan di halaman.
     */
    public function test_the_page_offers_notes_on_equipment_and_logistics_items_only(): void
    {
        [$pemilik, $trip] = $this->tripPendaki();

        $catatan = $this->actingAs($pemilik)->get(route('trips.preparation', $trip))->viewData('page')['props']['catatanButir'];

        $this->assertEqualsCanonicalizing(
            $trip->preparationItems()->whereIn('category', [PreparationCategory::EQUIPMENT->value, PreparationCategory::LOGISTICS->value])->pluck('id')->all(),
            $catatan['bisa'],
        );
        $this->assertSame([], $catatan['isi']);
    }

    /**
     * Trip orang lain menjawab 403 seperti rute trip lain (keputusan 403 atau 404 menunggu spec v3
     * Q5); butir milik trip lain menjawab 404 walau id-nya benar (IDOR); tamu diarahkan masuk.
     */
    public function test_only_the_trip_owner_can_write_notes_on_its_own_items(): void
    {
        [, $trip] = $this->tripPendaki();
        $butir = $this->butir($trip, PreparationCategory::EQUIPMENT);
        $orangLain = User::factory()->create();
        $tripLain = TripPlan::factory()->create(['user_id' => $orangLain->id]);

        $this->actingAs($orangLain)->put(route('trips.preparation.note', [$trip, $butir->id]), ['catatan' => 'Diambil'])->assertForbidden();
        $this->actingAs($orangLain)->put(route('trips.preparation.note', [$tripLain, $butir->id]), ['catatan' => 'Diambil'])->assertNotFound();

        auth()->logout();
        $this->put(route('trips.preparation.note', [$trip, $butir->id]), ['catatan' => 'Tamu'])->assertRedirect(route('login'));

        $this->assertNull($butir->fresh()->note);
    }
}
```

Buat `tests/Spatial/PreparationItemNoteSchemaTest.php`:

```php
<?php

namespace Tests\Spatial;

use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Kolom catatan butir persiapan di PostgreSQL (R-024, ruling W7-R9). PostgreSQL menegakkan
 * panjang varchar(80), sedangkan SQLite menerima teks berapa pun panjangnya; suite SQLite tidak
 * dapat membuktikan batas kolom ini.
 */
class PreparationItemNoteSchemaTest extends SpatialTestCase
{
    public function test_the_note_column_is_optional_and_bounded(): void
    {
        $kolom = DB::selectOne(
            "SELECT data_type, is_nullable, character_maximum_length
             FROM information_schema.columns
             WHERE table_schema = current_schema() AND table_name = 'trip_preparation_items' AND column_name = 'note'"
        );

        $this->assertSame(['character varying', 'YES', 80], [$kolom->data_type, $kolom->is_nullable, (int) $kolom->character_maximum_length]);

        $butir = TripPreparationItem::create([
            'trip_plan_id' => TripPlan::factory()->create()->id,
            'category' => 'EQUIPMENT',
            'label' => 'Tenda',
            'is_critical' => false,
            'status' => 'NOT_CONFIRMED',
        ]);

        $this->expectException(QueryException::class);
        $butir->update(['note' => str_repeat('a', 81)]);
    }
}
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='PreparationNote|PreparationItemNoteSchema|TapTarget|KeyboardAndScreenReader|NoDeadViewData'`
Expected: FAIL. Pada simulasi rencana: 20 test, 14 lulus, 5 error, 1 dilewati. Yang merah: kelima test `PreparationNoteTest` (rute `trips.preparation.note` dan prop `catatanButir` belum ada). `TapTargetTest`, `KeyboardAndScreenReaderTest`, dan `NoDeadViewDataTest` sudah lulus; ketiganya menjaga komponen baru sesudah komponen itu ada. 1 test Spatial melewati dirinya tanpa `SPATIAL_TEST_DSN`.

- [ ] **Step 3: Tulis implementasinya.** Buat berkas dengan
`DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:controller --no-interaction Trips/PreparationNoteController --invokable`,
`... make:request --no-interaction Trips/UpdatePreparationNoteRequest`,
`... make:class --no-interaction Services/Trips/CatatanButir`, dan
`... make:migration --no-interaction add_note_to_trip_preparation_items_table` (ganti nama berkasnya menjadi
`2026_10_08_000500_add_note_to_trip_preparation_items_table.php`), lalu isinya diganti persis. Berkas TypeScript dibuat
langsung.

Buat `app/Http/Controllers/Trips/PreparationNoteController.php`:

```php
<?php

namespace App\Http\Controllers\Trips;

use App\Http\Controllers\Controller;
use App\Http\Requests\Trips\UpdatePreparationNoteRequest;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Services\Trips\CatatanButir;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

/**
 * Menulis atau menghapus catatan satu butir persiapan (ruling W7-R9). Hanya kolom catatan yang
 * berubah: status, label, kritis, dan kategori butir tidak pernah diambil dari permintaan.
 */
class PreparationNoteController extends Controller
{
    public function __invoke(UpdatePreparationNoteRequest $request, TripPlan $trip, int $item): RedirectResponse
    {
        // Dicari di dalam trip ini saja: butir milik trip lain menjawab 404 (IDOR).
        $butir = TripPreparationItem::where('trip_plan_id', $trip->id)->findOrFail($item);

        if ($penolakan = CatatanButir::penolakan($trip, $butir)) {
            throw ValidationException::withMessages(['catatan' => $penolakan]);
        }

        $butir->update(['note' => $request->catatan()]);

        return redirect()->route('trips.preparation', $trip);
    }
}
```

Buat `app/Http/Requests/Trips/UpdatePreparationNoteRequest.php`:

```php
<?php

namespace App\Http\Requests\Trips;

use App\Models\TripPlan;
use App\Services\Trips\CatatanButir;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Catatan satu butir persiapan (ruling W7-R9). Hanya `catatan` yang dibaca; spasinya dirapikan
 * dan isian kosong menghapus catatan. Trip dari rute dan Policy; butirnya dicari di dalam trip
 * itu oleh pengendali.
 */
class UpdatePreparationNoteRequest extends FormRequest
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
            'catatan' => ['present', 'nullable', 'string', 'max:'.CatatanButir::BATAS],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'catatan.present' => 'Isian catatan tidak terkirim. Muat ulang halaman lalu coba lagi.',
            'catatan.string' => 'Catatan berupa teks.',
            'catatan.max' => 'Catatan paling banyak '.CatatanButir::BATAS.' karakter.',
        ];
    }

    public function catatan(): ?string
    {
        $catatan = $this->validated('catatan');

        return is_string($catatan) && $catatan !== '' ? $catatan : null;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('catatan'))) {
            $this->merge(['catatan' => trim((string) preg_replace('/\s+/u', ' ', $this->input('catatan')))]);
        }
    }
}
```

Buat `app/Services/Trips/CatatanButir.php`:

```php
<?php

namespace App\Services\Trips;

use App\Enums\PreparationCategory;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;

/**
 * Catatan singkat per butir persiapan (titipan W4 ke W7; prototype 09 "Dibawa Dimas"; ruling
 * W7-R9). Catatan menjawab siapa membawa atau mengurus apa, jadi hanya butir Perlengkapan dan
 * Logistik yang dapat diberi catatan; butir pemahaman jalur, cuaca, dan status resmi sudah
 * punya keterangan dari sistem. Catatan tidak mengubah status butir, tidak dihitung sebagai
 * konfirmasi (BR-10), dan berhenti dapat diubah ketika trip selesai atau dibatalkan, sama
 * seperti statusnya.
 */
final class CatatanButir
{
    public const BATAS = 80;

    /** @var list<PreparationCategory> */
    public const KATEGORI = [PreparationCategory::EQUIPMENT, PreparationCategory::LOGISTICS];

    public static function dapatDicatat(TripPreparationItem $item): bool
    {
        return in_array($item->category, self::KATEGORI, true);
    }

    /**
     * Kalimat penolakan menulis catatan, atau null bila boleh.
     */
    public static function penolakan(TripPlan $trip, TripPreparationItem $item): ?string
    {
        if (! $trip->status->isActive()) {
            return sprintf('Catatan butir tidak dapat diubah lagi: trip berstatus %s.', $trip->status->label());
        }

        if (! self::dapatDicatat($item)) {
            return 'Catatan hanya untuk butir Perlengkapan dan Logistik.';
        }

        return null;
    }

    /**
     * Membutuhkan relasi preparationItems sudah dimuat; tanpa kueri tambahan.
     *
     * @return array{dapatDiubah: bool, bisa: list<int>, isi: array<int, string>, batas: int}
     */
    public static function untukHalaman(TripPlan $trip): array
    {
        $butir = $trip->preparationItems;

        return [
            'dapatDiubah' => $trip->status->isActive(),
            'bisa' => $butir->filter(fn (TripPreparationItem $item) => self::dapatDicatat($item))->pluck('id')->values()->all(),
            'isi' => $butir->filter(fn (TripPreparationItem $item) => filled($item->note))
                ->mapWithKeys(fn (TripPreparationItem $item) => [$item->id => $item->note])
                ->all(),
            'batas' => self::BATAS,
        ];
    }
}
```

Buat `database/migrations/2026_10_08_000500_add_note_to_trip_preparation_items_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catatan singkat per butir persiapan (titipan W4 ke W7; prototype 09 "Dibawa Dimas"; ruling
 * W7-R9). Aditif dan boleh kosong: butir lama tetap tanpa catatan. Panjangnya dibatasi kolom,
 * bukan hanya validasi, supaya batas yang sama berlaku di PostgreSQL. Catatan ikut terhapus
 * bersama butir, trip, dan akun lewat cascade yang sudah ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_preparation_items', function (Blueprint $table) {
            $table->string('note', 80)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('trip_preparation_items', function (Blueprint $table) {
            $table->dropColumn('note');
        });
    }
};
```

Buat `resources/js/components/trips/CatatanButir.tsx`:

```tsx
import { useForm, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState, type FormEvent } from 'react';
import { fokusGalat } from '@/components/akun/fokusGalat';
import { Button } from '@/components/ui/Button';
import { InputError, Label, TextInput } from '@/components/ui/Field';
import { kelas } from '@/lib/kelas';
import { note } from '@/routes/trips/preparation';

/** Prop halaman Persiapan dari App\Services\Trips\CatatanButir::untukHalaman(). */
export interface CatatanButirData {
    dapatDiubah: boolean;
    bisa: number[];
    isi: Record<string, string>;
    batas: number;
}

/**
 * Catatan singkat satu butir persiapan (titipan W4 ke W7; prototype 09 "Dibawa Dimas"; ruling
 * W7-R9), misalnya siapa yang membawa barangnya. Hanya butir Perlengkapan dan Logistik yang
 * dapat diberi catatan, dan hanya selama trip belum selesai atau dibatalkan; catatan yang sudah
 * ada tetap terbaca sesudahnya. Catatan tidak mengubah status butir (BR-10).
 *
 * Fokus mengikuti pola catatan Simpanan (KartuSimpanan): membuka isian memindah fokus ke isian,
 * dan menutupnya (Batal, Escape, atau simpan sukses) mengembalikan fokus ke tombol pemicu, yang
 * namanya menyebut butirnya. Penolakan server tampil di bawah isian dan isian menerima fokus.
 */
export function CatatanButir({ tripId, itemId, label }: { tripId: number; itemId: number; label: string }) {
    const { catatanButir } = usePage<{ catatanButir: CatatanButirData }>().props;
    const catatan = catatanButir.isi[itemId] ?? null;
    const dapatDicatat = catatanButir.dapatDiubah && catatanButir.bisa.includes(itemId);
    const [menyunting, setMenyunting] = useState(false);
    const form = useForm({ catatan: catatan ?? '' });
    const pertama = useRef(true);

    const idIsian = `catatan-butir-${itemId}`;
    const idPemicu = `catatan-butir-${itemId}-pemicu`;
    const panjang = Array.from(form.data.catatan).length;

    // Render pertama tidak boleh mencuri fokus halaman.
    useEffect(() => {
        if (pertama.current) {
            pertama.current = false;

            return;
        }

        document.getElementById(menyunting ? idIsian : idPemicu)?.focus();
    }, [menyunting, idIsian, idPemicu]);

    if (!dapatDicatat && catatan === null) {
        return null;
    }

    const mulai = () => {
        form.setData('catatan', catatan ?? '');
        form.clearErrors();
        setMenyunting(true);
    };

    const batal = () => {
        form.clearErrors();
        setMenyunting(false);
    };

    const simpan = (e: FormEvent) => {
        e.preventDefault();
        form.put(note.url({ trip: tripId, item: itemId }), {
            preserveScroll: true,
            onSuccess: () => setMenyunting(false),
            onError: (galat) => fokusGalat(galat, [['catatan', idIsian]]),
        });
    };

    if (menyunting) {
        return (
            <form
                onSubmit={simpan}
                onKeyDown={(e) => {
                    if (e.key === 'Escape') {
                        e.preventDefault();
                        batal();
                    }
                }}
                noValidate
                className="ml-10 space-y-2 pb-2"
            >
                <Label htmlFor={idIsian}>
                    Catatan<span className="sr-only"> untuk {label}</span>
                </Label>
                <TextInput
                    id={idIsian}
                    name="catatan"
                    autoComplete="off"
                    value={form.data.catatan}
                    onChange={(e) => form.setData('catatan', e.target.value)}
                    aria-invalid={form.errors.catatan ? true : undefined}
                    aria-describedby={form.errors.catatan ? `${idIsian}-hint ${idIsian}-galat` : `${idIsian}-hint`}
                />
                <p id={`${idIsian}-hint`} className={kelas('text-meta', panjang > catatanButir.batas ? 'font-bold text-danger-900' : 'text-secondary')}>
                    Misalnya siapa yang membawanya. {panjang} dari {catatanButir.batas} karakter.{catatan !== null ? ' Kosongkan lalu simpan untuk menghapus catatan.' : ''}
                </p>
                <InputError id={`${idIsian}-galat`} messages={form.errors.catatan} />
                <div className="flex flex-wrap gap-2">
                    <Button type="submit" size="sm" processing={form.processing}>
                        Simpan catatan
                    </Button>
                    <Button variant="ghost" size="sm" onClick={batal}>
                        Batal
                    </Button>
                </div>
            </form>
        );
    }

    // -mt-2 menarik baris ini ke bantalan bawah kotak centang di atasnya; target tombolnya tetap
    // 44 px.
    return (
        <div className="-mt-2 ml-10 flex flex-wrap items-center gap-x-2">
            {catatan !== null ? (
                <p className="min-w-0 py-1 text-kecil text-primary [overflow-wrap:anywhere]">
                    <span className="font-semibold">Catatan:</span> {catatan}
                </p>
            ) : null}
            {dapatDicatat ? (
                <button
                    id={idPemicu}
                    type="button"
                    onClick={mulai}
                    className="-ml-1 inline-flex min-h-11 min-w-11 items-center px-1 text-meta font-semibold text-secondary underline underline-offset-2 hover:text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                >
                    {catatan !== null ? 'Ubah' : 'Tambah catatan'}
                    <span className="sr-only">{catatan !== null ? ` catatan: ${label}` : `: ${label}`}</span>
                </button>
            ) : null}
        </div>
    );
}
```

`app/Models/TripPreparationItem.php`:

1. Ganti:

```php
    'trip_plan_id', 'preparation_item_id', 'user_owned_item_id', 'category', 'label',
    'description', 'is_critical', 'status', 'status_updated_at',
])]
```

dengan:

```php
    'trip_plan_id', 'preparation_item_id', 'user_owned_item_id', 'category', 'label',
    'description', 'is_critical', 'status', 'status_updated_at', 'note',
])]
```

`app/Services/Trips/HalamanPersiapan.php`:

1. Ganti:

```php
            'pengingat' => $this->pengingat->untukHalaman($trip),
        ];
```

dengan:

```php
            'pengingat' => $this->pengingat->untukHalaman($trip),
            // Catatan per butir (ruling W7-R9) dibaca komponen CatatanButir lewat usePage.
            'catatanButir' => CatatanButir::untukHalaman($trip),
        ];
```

`resources/js/pages/Trips/Preparation.tsx`:

1. Ganti:

```tsx
import { useEffect, useRef, useState, type ReactNode } from 'react';
import { KepalaTrip } from '@/components/trips/KepalaTrip';
```

dengan:

```tsx
import { useEffect, useRef, useState, type ReactNode } from 'react';
import { CatatanButir } from '@/components/trips/CatatanButir';
import { KepalaTrip } from '@/components/trips/KepalaTrip';
```

2. Ganti:

```tsx
                                                ) : null}
                                            </li>
```

dengan:

```tsx
                                                ) : null}
                                                <CatatanButir tripId={kepala.id} itemId={item.id} label={item.label} />
                                            </li>
```

`routes/web.php`:

1. Ganti:

```php
use App\Http\Controllers\Trips\PreparationController;
```

dengan:

```php
use App\Http\Controllers\Trips\PreparationController;
use App\Http\Controllers\Trips\PreparationNoteController;
```

2. Ganti:

```php
    Route::get('trips/{trip}/preparation', [PreparationController::class, 'show'])->name('trips.preparation');
```

dengan:

```php
    Route::get('trips/{trip}/preparation', [PreparationController::class, 'show'])->name('trips.preparation');
    Route::put('trips/{trip}/preparation/{item}/catatan', PreparationNoteController::class)->whereNumber('item')->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,catatan-butir')->name('trips.preparation.note');
```

`tests/Pratinjau/HalamanPratinjauTest.php`:

1. Ganti:

```php
        $butir = app(PreparationService::class)->generateFor($trip);
        $butir->where('is_critical', true)->take(3)->each(fn ($satu) => $satu->update(['status' => PreparationStatus::CONFIRMED->value]));

        $this->tulisPratinjau('persiapan', $this->get(self::ASAL.'/trips/'.$trip->id.'/preparation'), 'Trips/Preparation');
```

dengan:

```php
        $butir = app(PreparationService::class)->generateFor($trip);
        $butir->where('is_critical', true)->take(3)->each(fn ($satu) => $satu->update(['status' => PreparationStatus::CONFIRMED->value]));
        // Catatan per butir (ruling W7-R9) seperti acuan 09 "Dibawa Dimas".
        $butir->firstWhere('label', 'Headlamp dan cadangan baterai')?->update(['note' => 'Baterai cadangan dibawa Dimas']);

        $this->tulisPratinjau('persiapan', $this->get(self::ASAL.'/trips/'.$trip->id.'/preparation'), 'Trips/Preparation');
```

- [ ] **Step 4: Build dan tipe.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `npx tsc --noEmit`
Expected: `✓ built`; keluaran Wayfinder memuat `trips.preparation.note`; tsc tanpa keluaran galat.

- [ ] **Step 5: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Http/Controllers/Trips/PreparationNoteController.php app/Http/Requests/Trips/UpdatePreparationNoteRequest.php app/Services/Trips/CatatanButir.php app/Models/TripPreparationItem.php app/Services/Trips/HalamanPersiapan.php database/migrations/2026_10_08_000500_add_note_to_trip_preparation_items_table.php routes/web.php tests/Feature/PreparationNoteTest.php tests/Spatial/PreparationItemNoteSchemaTest.php tests/Pratinjau/HalamanPratinjauTest.php`, lalu `php artisan test --compact --filter='PreparationNote|PreparationItemNoteSchema|TapTarget|KeyboardAndScreenReader|NoDeadViewData'`
Expected: PASS. Pada simulasi rencana: 20 test, 19 lulus, 1 dilewati.

- [ ] **Step 6: Suite, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 2.098 test, 2.096 lulus, 2 dilewati (sebelum tugas: 2.093).

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `php artisan test --compact --testsuite=Pratinjau`
Expected: PASS; halaman `persiapan` memuat catatan "Dibawa Dimas". Pada simulasi rencana: 30 test lulus.

- [ ] **Step 7: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 7: Onboarding tiga langkah dan wilayah minat

Spec v3 bagian 6.16 (prototype 00) dan 9.3, DK-41, DK-42, induk W7 butir 6; ruling W7-R2, W7-R10, W7-R11, dan W7-R18.
Langkah 1: tingkat pengalaman sebagai kartu pilihan dengan keterangan. Langkah 2: jalur terberat dari katalog, dengan
tanjakan, durasi, medan, dan navigasi yang diturunkan server dari jalur itu (`TurunanPengalaman`, tangga navigasi sama
dengan Route Fit) untuk dikonfirmasi, atau isi sendiri ("Jalurnya tidak ada di daftar" dan "Ubah nilai"), atau "Saya
belum pernah mendaki" (nol pendakian dan navigasi "Belum ada" sebagai jawaban yang diketahui, bukan UNKNOWN), ditambah
jumlah pendakian dan durasi mendaki terlama dalam satu hari. Langkah 3: satu jenis trip, lama pendakian, wilayah minat
(delapan kelompok provinsi, `InterestRegion`, migrasi aditif `000600`), dan batas tanjakan opsional. Setiap langkah
diperiksa server sebelum pindah (`langkah`). Sesudah disimpan, halaman hasil menulis "Dengan jawaban ini, N jalur
berlabel Cocok" yang dihitung `HalamanJelajah` dengan saringan Jelajah `kecocokan=COCOK`, dengan tautan ke Jelajah
tersaring dan ke Profil. "Nanti saja" (hanya bila profil belum lengkap) menuju Jelajah tanpa menyimpan, jadi label
tetap "Belum dinilai" (R-006). Halaman tugas tanpa bilah tab. Wilayah minat tidak dibaca Route Fit.

**Files:**
- Create: `app/Enums/InterestRegion.php`, `app/Services/Profil/TurunanPengalaman.php`, `database/migrations/2026_10_08_000600_add_interest_regions_to_user_preferences_table.php`, `resources/js/components/onboarding/types.ts`, `resources/js/components/onboarding/PilihanKartu.tsx`, `resources/js/components/onboarding/PengalamanTerberat.tsx`
- Modify: `app/Http/Controllers/Onboarding/ProfileSetupController.php`, `app/Http/Requests/StoreProfileSetupRequest.php`, `resources/js/pages/Onboarding/ProfileSetup.tsx` (ketiganya ditulis ulang utuh), `app/Enums/ExperienceLevel.php`, `app/Enums/NavigationExperience.php`, `app/Models/UserPreference.php`
- Test: `tests/Feature/OnboardingDerivationTest.php` (baru), `tests/Feature/InterestRegionTest.php` (baru), `tests/Spatial/InterestRegionsSchemaTest.php` (baru, PostgreSQL), `tests/Feature/EmptyStateCoverageTest.php`, `tests/Feature/NavigationConsistencyTest.php`, `tests/Feature/RecommendationFlowTest.php`, `tests/Pratinjau/HalamanPratinjauTest.php`

**Interfaces:**
- Consumes: `CariJalur` dan `PemilihJalur` (Tugas 2); `HalamanJelajah::props(TrailSearch, User, Request)` dan kunci `ringkasan.jumlah`; `TrailSearch::dariInput(array)`; `NavigationComplexity::expectedNavigationRank()`; `TrailFigures::meter()`, `durasi()`, `medan()`; `Trail::terrainTypes()`; `AnalyticsRecorder`; primitif `BarAksi`, `ChipPilihan`, `Stepper`, `Button`, `Field`, `Tautan`, `Icon`, dan `fokusDiDalam`.
- Produces:
  - enum `App\Enums\InterestRegion` (delapan kasus `JAWA_BARAT_BANTEN` sampai `MALUKU_PAPUA`, `label()`, `dari(?array $nilai): list<self>` dalam urutan daftar); kolom `user_preferences.interest_regions` (JSON, boleh kosong) dengan cast larik di `UserPreference`;
  - `ExperienceLevel::keterangan(): string` dan `NavigationExperience::keterangan(): string`;
  - `TurunanPengalaman::dariJalur(Trail): array{tanjakan_m: int|null, durasi_menit: int|null, medan: list<string>, navigasi: string|null}`, `TurunanPengalaman::navigasi(?NavigationComplexity): ?NavigationExperience`, `TurunanPengalaman::baris(Trail): array` (statis);
  - `StoreProfileSetupRequest::KATALOG`, `MANUAL`, `BELUM`; `langkahDiperiksa(): ?int`, `pengalaman(): array`, `wilayah(): ?array`;
  - props `Onboarding/ProfileSetup`: `sudahLengkap`, `langkahAwal`, `nilai`, `pilihan` (`tingkat`, `navigasi`, `medan`, `jenisTrip`, `durasi`, `wilayah`), `pilihanJalur` (opsional), `selesai` (`{cocok: int}` dari flash `onboarding-selesai`), `tautan` (`jelajah`, `cocok`, `profil`); `GET onboarding?langkah=1|2|3` membuka langkah itu (dipakai "Ubah" di Profil, Tugas 10);
  - TypeScript `CaraPengalaman`, `NilaiOnboarding`, `JalurTerberat`, `PilihanOnboarding`, `OpsiKartu`; komponen `PilihanKartu` (`bentuk` `'kartu'` atau `'chip'`) dan `PengalamanTerberat`.

- [ ] **Step 1: Tulis test yang gagal.** Buat berkas test baru dengan `make:test`; test Spatial dibuat langsung di
`tests/Spatial/`. Test lama yang diperbarui membawa alasannya: `NavigationConsistencyTest` mencatat onboarding sebagai
halaman tugas tanpa bilah tab, `EmptyStateCoverageTest` mendaftar pilihan tetap onboarding, dan `RecommendationFlowTest`
mengikuti isian baru.

Buat `tests/Feature/InterestRegionTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\InterestRegion;
use App\Enums\NavigationExperience;
use App\Models\Trail;
use App\Models\User;
use App\Services\TrailFitService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Wilayah minat DK-42 (spec v3 bagian 9.3; rencana induk W7 butir 5): larik JSON dari daftar
 * putih App\Enums\InterestRegion di preferensi pendaki, tanpa mengubah label kecocokan.
 */
class InterestRegionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $ubah
     * @return array<string, mixed>
     */
    private function isian(array $ubah = []): array
    {
        return [
            'experience_level' => ExperienceLevel::BEGINNER->value,
            'cara_pengalaman' => 'MANUAL',
            'completed_hikes_count' => 2,
            'terrain_experience' => ['FOREST'],
            'navigation_experience' => NavigationExperience::BASIC->value,
            'highest_elevation_gain_m' => '700',
            'durasi_terlama_jam' => '',
            'preferred_trip_type' => 'TEKTOK',
            'preferred_duration' => 'ONE_DAY',
            'interest_regions' => [],
            'max_elevation_gain_preference_m' => '',
            ...$ubah,
        ];
    }

    public function test_regions_are_stored_from_the_whitelist_in_list_order(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('onboarding.store'), $this->isian(['interest_regions' => ['SULAWESI', 'JAWA_BARAT_BANTEN']]))->assertSessionHasNoErrors();

        $this->assertSame(['JAWA_BARAT_BANTEN', 'SULAWESI'], $user->fresh()->preference->interest_regions);
        $this->assertSame(
            ['Jawa Barat dan Banten', 'Sulawesi'],
            array_map(fn (InterestRegion $w) => $w->label(), InterestRegion::dari($user->fresh()->preference->interest_regions)),
        );
    }

    public function test_unknown_or_repeated_regions_are_refused(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('onboarding.store'), $this->isian(['interest_regions' => ['MARS']]))
            ->assertSessionHasErrors(['interest_regions.0' => 'Pilih wilayah dari daftar yang tersedia.']);
        $this->actingAs($user)->post(route('onboarding.store'), $this->isian(['interest_regions' => ['SUMATERA', 'SUMATERA']]))
            ->assertSessionHasErrors('interest_regions.0');

        $this->assertNull($user->fresh()->preference);
    }

    /**
     * Tanpa pilihan disimpan null; mengganti wilayah tidak mengubah label kecocokan apa pun
     * (Route Fit tidak membaca wilayah minat, R-021).
     */
    public function test_regions_never_change_fit_labels(): void
    {
        $user = User::factory()->create();
        $jalur = Trail::factory()->published()->count(2)->sequence(['elevation_gain_m' => 500], ['elevation_gain_m' => 1900])->create();
        $label = fn () => collect(app(TrailFitService::class)->forTrails($user->fresh(), new Collection($jalur->map->fresh()->all())))->map(fn ($fit) => $fit->label)->all();

        $this->actingAs($user)->post(route('onboarding.store'), $this->isian())->assertSessionHasNoErrors();
        $this->assertNull($user->fresh()->preference->interest_regions);
        $sebelum = $label();

        $this->actingAs($user)->post(route('onboarding.store'), $this->isian(['interest_regions' => array_map(fn (InterestRegion $w) => $w->value, InterestRegion::cases())]))->assertSessionHasNoErrors();

        $this->assertCount(count(InterestRegion::cases()), $user->fresh()->preference->interest_regions);
        $this->assertSame($sebelum, $label());
    }

    /**
     * Kolom lama yang tidak lagi ditanyakan onboarding tidak dihapus diam-diam.
     */
    public function test_saving_keeps_the_old_free_text_preferences(): void
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => 'BEGINNER', 'region_preference' => 'Jawa Tengah', 'bio' => 'Suka pagi', 'completed_at' => now()]);
        $user->preference()->create(['preferred_duration' => 'ONE_DAY', 'preferred_trip_type' => 'TEKTOK', 'preferred_challenge' => 'RELAXED', 'region_preference' => 'Jawa Tengah']);

        $this->actingAs($user)->post(route('onboarding.store'), $this->isian(['interest_regions' => ['JAWA_TENGAH_DIY']]))->assertSessionHasNoErrors();

        $user = $user->fresh();
        $this->assertSame(['Jawa Tengah', 'Suka pagi'], [$user->profile->region_preference, $user->profile->bio]);
        $this->assertSame(['RELAXED', 'Jawa Tengah'], [$user->preference->preferred_challenge->value, $user->preference->region_preference]);
    }
}
```

Buat `tests/Feature/OnboardingDerivationTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\NavigationComplexity;
use App\Enums\NavigationExperience;
use App\Enums\TerrainCharacter;
use App\Models\Trail;
use App\Models\User;
use App\Services\Profil\TurunanPengalaman;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Onboarding DK-41 (spec v3 bagian 6.16; rencana induk W7 butir 6): pengalaman diturunkan dari
 * jalur terberat yang dikonfirmasi, isian sendiri disimpan apa adanya, "belum pernah mendaki"
 * adalah jawaban yang diketahui, dan yang dilewati tetap UNKNOWN, bukan rendah (R-006, R-007).
 * Akhirnya menyebut "N jalur berlabel Cocok" yang sama dengan Jelajah.
 */
class OnboardingDerivationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $ubah
     * @return array<string, mixed>
     */
    private function isian(array $ubah = []): array
    {
        return [
            'experience_level' => ExperienceLevel::INTERMEDIATE->value,
            'cara_pengalaman' => 'MANUAL',
            'jalur_terberat_id' => '',
            'completed_hikes_count' => 4,
            'terrain_experience' => ['FOREST'],
            'navigation_experience' => NavigationExperience::BASIC->value,
            'highest_elevation_gain_m' => '900',
            'durasi_terlama_jam' => '6',
            'preferred_trip_type' => 'TEKTOK',
            'preferred_duration' => 'ONE_DAY',
            'interest_regions' => [],
            'max_elevation_gain_preference_m' => '',
            ...$ubah,
        ];
    }

    /**
     * KATALOG: tanjakan, medan, dan navigasi dibaca server dari jalurnya; isian pengalaman yang
     * ikut terkirim diabaikan. Tingkat tetap pilihan pendaki (BR-11).
     */
    public function test_a_catalog_trail_derives_gain_terrain_and_navigation(): void
    {
        $user = User::factory()->create();
        $jalur = Trail::factory()->published()->create([
            'name' => 'Gede via Cibodas',
            'elevation_gain_m' => 1650,
            'terrain_character' => ['FOREST', 'ROCKY', 'STEEP_SLOPE'],
            'navigation_complexity' => NavigationComplexity::HIGH->value,
        ]);

        $this->actingAs($user)->post(route('onboarding.store'), $this->isian([
            'cara_pengalaman' => 'KATALOG',
            'jalur_terberat_id' => $jalur->id,
            'terrain_experience' => ['SAND'],
            'navigation_experience' => NavigationExperience::ADVANCED->value,
            'highest_elevation_gain_m' => '3000',
        ]))->assertSessionHasNoErrors()->assertRedirect(route('onboarding'));

        $pengalaman = $user->fresh()->experience;
        $this->assertSame(['FOREST', 'ROCKY', 'STEEP_SLOPE'], $pengalaman->terrain_experience);
        $this->assertSame(NavigationExperience::COMPETENT, $pengalaman->navigation_experience);
        $this->assertSame(1650, $pengalaman->highest_elevation_gain_m);
        $this->assertSame(4, $pengalaman->completed_hikes_count);
        $this->assertSame(360, $pengalaman->longest_hike_duration_minutes);
        $this->assertSame(ExperienceLevel::INTERMEDIATE, $user->fresh()->profile->experience_level);
    }

    /**
     * @return array<string, array{NavigationComplexity, NavigationExperience}>
     */
    public static function tanggaNavigasi(): array
    {
        return [
            'rendah' => [NavigationComplexity::LOW, NavigationExperience::NONE],
            'sedang' => [NavigationComplexity::MODERATE, NavigationExperience::BASIC],
            'tinggi' => [NavigationComplexity::HIGH, NavigationExperience::COMPETENT],
        ];
    }

    /**
     * Navigasi turunan memakai tangga yang sama dengan penilai kecocokan
     * (NavigationComplexity::expectedNavigationRank), bukan aturan baru.
     */
    #[DataProvider('tanggaNavigasi')]
    public function test_derived_navigation_follows_the_route_fit_ladder(NavigationComplexity $jalur, NavigationExperience $turunan): void
    {
        $this->assertSame($turunan, TurunanPengalaman::navigasi($jalur));
        $this->assertSame($jalur->expectedNavigationRank(), $turunan->rank());
    }

    /**
     * Angka jalur yang belum tercatat tetap tidak diketahui, tidak menjadi nol (R-007).
     */
    public function test_unknown_trail_numbers_stay_unknown(): void
    {
        $turunan = TurunanPengalaman::dariJalur(Trail::factory()->make(['elevation_gain_m' => null, 'estimated_duration_minutes' => 0, 'terrain_character' => null]));

        $this->assertNull($turunan['tanjakan_m']);
        $this->assertNull($turunan['durasi_menit']);
        $this->assertSame([], $turunan['medan']);
    }

    public function test_manual_values_are_stored_as_given(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('onboarding.store'), $this->isian(['highest_elevation_gain_m' => '', 'terrain_experience' => ['FOREST', 'MUD']]))
            ->assertSessionHasNoErrors();

        $pengalaman = $user->fresh()->experience;
        $this->assertSame(['FOREST', 'MUD'], $pengalaman->terrain_experience);
        $this->assertSame(NavigationExperience::BASIC, $pengalaman->navigation_experience);
        $this->assertNull($pengalaman->highest_elevation_gain_m, 'Tanjakan yang dikosongkan tetap tidak diketahui.');
    }

    /**
     * "Belum pernah mendaki" adalah jawaban yang diketahui: nol pendakian dan navigasi "Belum
     * ada", dan profilnya lengkap sehingga jalur dinilai.
     */
    public function test_never_having_hiked_is_a_known_answer(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('onboarding.store'), $this->isian(['cara_pengalaman' => 'BELUM', 'experience_level' => ExperienceLevel::BEGINNER->value, 'completed_hikes_count' => '']))
            ->assertSessionHasNoErrors();

        $pengalaman = $user->fresh()->experience;
        $this->assertSame([0, [], NavigationExperience::NONE, null, null], [
            $pengalaman->completed_hikes_count,
            $pengalaman->terrain_experience,
            $pengalaman->navigation_experience,
            $pengalaman->highest_elevation_gain_m,
            $pengalaman->longest_hike_duration_minutes,
        ]);
        // Instans baru: berkas bersama Inertia sudah memuat relasi profil kosong pada instans lama.
        $this->actingAs($user->fresh())->get(route('trails.index'))->assertInertia(fn (AssertableInertia $page) => $page->where('dinilai', true));
    }

    /**
     * "Nanti saja" dan pemeriksaan langkah tidak menyimpan apa pun: setiap jalur tetap "Belum
     * dinilai", tidak pernah dianggap rendah (R-006).
     */
    public function test_skipping_leaves_every_trail_unrated(): void
    {
        $user = User::factory()->create();
        Trail::factory()->published()->easy()->count(2)->create();

        $this->actingAs($user)->post(route('onboarding.store'), ['langkah' => 1, 'experience_level' => ExperienceLevel::BEGINNER->value])->assertRedirect(route('onboarding'));
        $this->actingAs($user)->post(route('onboarding.store'), ['langkah' => 2, ...$this->isian()])->assertSessionHasNoErrors();

        $this->assertFalse($user->fresh()->hasCompletedProfile());
        $this->actingAs($user)->get(route('trails.index'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('dinilai', false)
            ->where('kataBelumDinilai', 'Belum dinilai')
            ->where('atribut.0.kecocokan', null)
            ->where('atribut.1.kecocokan', null));
    }

    /**
     * Akibat langsung dihitung dengan saringan Jelajah, jadi N di akhir onboarding sama dengan
     * jumlah yang tampil ketika Jelajah dibuka dengan saringan Cocok.
     */
    public function test_the_final_count_matches_jelajah(): void
    {
        $user = User::factory()->create();
        Trail::factory()->published()->easy()->count(3)->create();
        Trail::factory()->published()->create(['elevation_gain_m' => 2400, 'estimated_duration_minutes' => 900, 'terrain_character' => ['EXPOSED_RIDGE', 'SCREE']]);

        $this->actingAs($user)->post(route('onboarding.store'), $this->isian())->assertSessionHasNoErrors();
        $cocok = session('onboarding-selesai')['cocok'];

        $this->assertGreaterThan(0, $cocok);
        $this->actingAs($user->fresh())->get(route('trails.index', ['kecocokan' => 'COCOK']))->assertInertia(fn (AssertableInertia $page) => $page->where('ringkasan.jumlah', $cocok));
        $this->actingAs($user->fresh())->withSession(['onboarding-selesai' => ['cocok' => $cocok]])->get(route('onboarding'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('selesai.cocok', $cocok)
                ->where('tautan.cocok', route('trails.index', ['kecocokan' => 'COCOK'])));
    }

    /**
     * Jawaban yang ditolak menyebut apa yang perlu diperbaiki, satu pesan per aturan, dan tidak ada
     * yang tersimpan.
     */
    public function test_each_refused_answer_says_what_to_fix(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('onboarding.store'), [
            'experience_level' => '',
            'cara_pengalaman' => 'MANUAL',
            'completed_hikes_count' => 0,
            'navigation_experience' => '',
            'highest_elevation_gain_m' => '9001',
            'durasi_terlama_jam' => '25',
            'preferred_trip_type' => '',
            'preferred_duration' => '',
            'max_elevation_gain_preference_m' => '0',
        ])->assertSessionHasErrors([
            'experience_level' => 'Pilih tingkat yang paling mendekati pengalaman Anda.',
            'completed_hikes_count' => 'Jumlah pendakian paling sedikit 1. Bila belum pernah mendaki sampai selesai, pilih "Saya belum pernah mendaki".',
            'navigation_experience' => 'Pilih pengalaman navigasi Anda.',
            'highest_elevation_gain_m' => 'Tanjakan diisi dalam meter, paling banyak 9.000, atau dikosongkan.',
            'durasi_terlama_jam' => 'Durasi diisi dalam jam bulat, 1 sampai 24, atau dikosongkan.',
            'preferred_trip_type' => 'Pilih jenis trip yang paling sering Anda rencanakan.',
            'preferred_duration' => 'Pilih lama pendakian yang Anda sukai.',
            'max_elevation_gain_preference_m' => 'Batas tanjakan diisi dalam meter, 1 sampai 9.000, atau dikosongkan.',
        ]);
        $this->actingAs($user)->post(route('onboarding.store'), ['experience_level' => ExperienceLevel::BEGINNER->value])
            ->assertSessionHasErrors(['cara_pengalaman' => 'Pilih jalur terberat Anda, isi sendiri, atau pilih "Saya belum pernah mendaki".']);

        $this->assertFalse($user->fresh()->hasCompletedProfile());
    }

    public function test_a_trail_outside_the_published_catalog_is_refused(): void
    {
        $user = User::factory()->create();
        $tersembunyi = Trail::factory()->unpublished()->create();

        $this->actingAs($user)->post(route('onboarding.store'), $this->isian(['cara_pengalaman' => 'KATALOG', 'jalur_terberat_id' => $tersembunyi->id]))
            ->assertSessionHasErrors(['jalur_terberat_id' => 'Jalur itu tidak ada di katalog terbit. Cari lagi, atau isi sendiri.']);
        $this->actingAs($user)->post(route('onboarding.store'), $this->isian(['cara_pengalaman' => 'KATALOG']))
            ->assertSessionHasErrors(['jalur_terberat_id' => 'Pilih jalur dari hasil pencarian, atau pilih "Jalurnya tidak ada di daftar".']);

        $this->assertFalse($user->fresh()->hasCompletedProfile());
    }

    /**
     * Pencarian jalur terberat mengirim nilai turunan untuk dikonfirmasi, hanya lewat muat
     * ulang parsial; kunjungan biasa tidak membayarnya.
     */
    public function test_the_search_sends_derived_values_for_confirmation(): void
    {
        $user = User::factory()->create();
        Trail::factory()->published()->easy()->create(['name' => 'Prau via Patak Banteng', 'elevation_gain_m' => 700, 'terrain_character' => ['FOREST', 'STEEP_SLOPE']]);

        $this->actingAs($user)->get(route('onboarding', ['cari' => 'prau']))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->missing('pilihanJalur')
            ->reloadOnly('pilihanJalur', fn (AssertableInertia $muatUlang) => $muatUlang
                ->where('pilihanJalur.0.nama', 'Prau via Patak Banteng')
                ->where('pilihanJalur.0.turunan.tanjakan_m', 700)
                ->where('pilihanJalur.0.turunan.medan', [TerrainCharacter::FOREST->value, TerrainCharacter::STEEP_SLOPE->value])
                ->where('pilihanJalur.0.turunan.navigasi', NavigationExperience::NONE->value)
                ->where('pilihanJalur.0.tulisan.tanjakan', '700 m')
                ->where('pilihanJalur.0.tulisan.medan', 'Hutan, Tanjakan curam')
                ->where('pilihanJalur.0.tulisan.navigasi', 'Belum ada: mengikuti jalur yang jelas atau rombongan.')));
    }
}
```

Buat `tests/Spatial/InterestRegionsSchemaTest.php`:

```php
<?php

namespace Tests\Spatial;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Wilayah minat di PostgreSQL (R-024, DK-42): kolom JSON yang boleh kosong, dan larik kode yang
 * tersimpan sebagai larik JSON sungguhan, bukan teks. SQLite menyimpan JSON sebagai teks, jadi
 * suite SQLite tidak membuktikan bentuk ini.
 */
class InterestRegionsSchemaTest extends SpatialTestCase
{
    public function test_the_column_holds_a_json_array_and_may_be_empty(): void
    {
        $kolom = DB::selectOne(
            "SELECT data_type, is_nullable FROM information_schema.columns
             WHERE table_schema = current_schema() AND table_name = 'user_preferences' AND column_name = 'interest_regions'"
        );

        $this->assertSame(['json', 'YES'], [$kolom->data_type, $kolom->is_nullable]);

        $user = User::factory()->create();
        $user->preference()->create(['preferred_duration' => 'ONE_DAY', 'preferred_trip_type' => 'TEKTOK', 'interest_regions' => ['JAWA_TIMUR', 'SULAWESI']]);

        $this->assertSame('array', DB::selectOne('SELECT json_typeof(interest_regions) AS jenis FROM user_preferences WHERE user_id = ?', [$user->id])->jenis);
        $this->assertSame(['JAWA_TIMUR', 'SULAWESI'], $user->fresh()->preference->interest_regions);
    }
}
```

`tests/Feature/EmptyStateCoverageTest.php`:

1. Ganti:

```php
        'pages/Onboarding/ProfileSetup.tsx' => [
            'ringkasan' => 'sebelas baris tetap ringkasan profil, tampil hanya bila profilnya lengkap',
        ],
```

dengan:

```php
        'pages/Onboarding/ProfileSetup.tsx' => [
            'pilihan.wilayah' => 'seluruh InterestRegion::cases(), delapan kelompok wilayah yang menutup Indonesia (DK-42)',
        ],
```

`tests/Feature/NavigationConsistencyTest.php`:

1. Ganti:

```php
     * Halaman tugas dengan bar aksi tetap menyembunyikan bilah tab (spec v3 bagian 5.1):
     * detail jalur, penilaian kecocokan (W3), dan rencanakan trip. Halaman lain tetap
     * membawa bilah tab.
     */
```

dengan:

```php
     * Halaman tugas dengan bar aksi tetap menyembunyikan bilah tab (spec v3 bagian 5.1):
     * detail jalur, penilaian kecocokan (W3), rencanakan trip, dan onboarding profil pendaki
     * (W7, prototype 00 tanpa bilah tab). Halaman lain tetap membawa bilah tab.
     */
```

2. Ganti:

```php
        sort($tanpaBilah);

        $this->assertSame(['Trails/Fit.tsx', 'Trails/Show.tsx', 'Trips/Create.tsx'], $tanpaBilah);
    }
```

dengan:

```php
        sort($tanpaBilah);

        $this->assertSame(['Onboarding/ProfileSetup.tsx', 'Trails/Fit.tsx', 'Trails/Show.tsx', 'Trips/Create.tsx'], $tanpaBilah);
    }
```

`tests/Feature/RecommendationFlowTest.php`:

1. Ganti:

```php
            'experience_level' => ExperienceLevel::BEGINNER->value,
            'region_preference' => '',
            'bio' => '',
            'completed_hikes_count' => 0,
            'terrain_experience' => [],
            'navigation_experience' => NavigationExperience::BASIC->value,
            'longest_hike_duration_minutes' => '',
            'highest_elevation_gain_m' => '',
            'preferred_duration' => 'ONE_DAY',
            'preferred_trip_type' => 'TEKTOK',
            'preferred_challenge' => '',
            'max_elevation_gain_preference_m' => '',
```

dengan:

```php
            'experience_level' => ExperienceLevel::BEGINNER->value,
            'cara_pengalaman' => 'MANUAL',
            'jalur_terberat_id' => '',
            'completed_hikes_count' => 1,
            'terrain_experience' => [],
            'navigation_experience' => NavigationExperience::BASIC->value,
            'highest_elevation_gain_m' => '',
            'durasi_terlama_jam' => '',
            'preferred_duration' => 'ONE_DAY',
            'preferred_trip_type' => 'TEKTOK',
            'interest_regions' => [],
            'max_elevation_gain_preference_m' => '',
```

2. Ganti:

```php
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('goals.create'))
            ->assertSessionHas('status', 'Profil pendaki tersimpan.');

        $this->assertTrue($user->fresh()->hasCompletedProfile());
```

dengan:

```php
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('onboarding'))
            ->assertSessionHas('onboarding-selesai', ['cocok' => 0]);

        $this->assertTrue($user->fresh()->hasCompletedProfile());
```

3. Ganti:

```php
            ->post(route('onboarding.store'), ['langkah' => 3, 'preferred_duration' => 'ONE_DAY', 'preferred_trip_type' => 'TEKTOK'])
            ->assertSessionHasErrors(['experience_level', 'navigation_experience']);

        $this->assertFalse($user->fresh()->hasCompletedProfile());
```

dengan:

```php
            ->post(route('onboarding.store'), ['langkah' => 3, 'preferred_duration' => 'ONE_DAY', 'preferred_trip_type' => 'TEKTOK'])
            ->assertSessionHasErrors(['experience_level', 'cara_pengalaman']);

        $this->assertFalse($user->fresh()->hasCompletedProfile());
```

4. Ganti:

```php
    /**
     * Halaman onboarding merangkap penyunting profil: pendaki yang profilnya sudah
     * lengkap melihat ringkasannya, dengan yang belum diisi disebut belum diisi.
     */
    public function test_a_completed_profile_is_summarised(): void
    {
```

dengan:

```php
    /**
     * Halaman onboarding merangkap penyunting profil (W7): profil yang sudah lengkap dibuka
     * sebagai isiannya sendiri, dengan yang belum diisi tetap kosong (R-007). Ringkasannya
     * kini di hub Profil.
     */
    public function test_a_completed_profile_opens_as_its_own_values(): void
    {
```

5. Ganti:

```php
                ->where('title', 'Profil Pendaki')
                ->where('nilai.experience_level', ExperienceLevel::INTERMEDIATE->value)
                ->where('ringkasan.0.nilai', ExperienceLevel::INTERMEDIATE->label())
                ->where('ringkasan.6.label', 'Elevation gain tertinggi')
                ->where('ringkasan.6.nilai', 'Belum diisi'));
    }

    public function test_an_incomplete_profile_gets_the_form_without_a_summary(): void
    {
```

dengan:

```php
                ->where('title', 'Profil Pendaki')
                ->where('sudahLengkap', true)
                ->where('nilai.experience_level', ExperienceLevel::INTERMEDIATE->value)
                ->where('nilai.cara_pengalaman', 'MANUAL')
                ->where('nilai.completed_hikes_count', 8)
                ->where('nilai.highest_elevation_gain_m', ''));
    }

    public function test_an_incomplete_profile_starts_from_the_catalog(): void
    {
```

6. Ganti:

```php
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('ringkasan', null)
                ->has('pilihan.terrainOptions', count(TerrainCharacter::cases())));
    }
```

dengan:

```php
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('sudahLengkap', false)
                ->where('selesai', null)
                ->where('nilai.cara_pengalaman', 'KATALOG')
                ->has('pilihan.medan', count(TerrainCharacter::cases())));
    }
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='OnboardingDerivation|InterestRegion|RecommendationFlow|NavigationConsistency|EmptyStateCoverage|PageHeaderContract|KeyboardAndScreenReader|TapTarget'`
Expected: FAIL. Pada simulasi rencana: 63 test, 42 lulus, 14 gagal, 6 error, 1 dilewati. Yang merah: sepuluh kasus `OnboardingDerivationTest` (`TurunanPengalaman` dan tiga cara langkah 2 belum ada), empat test `InterestRegionTest` (`InterestRegion` dan kolom `interest_regions` belum ada), empat test `RecommendationFlowTest` (isian dan props baru), `NavigationConsistencyTest::test_only_task_pages_hide_the_tab_bar` (onboarding belum halaman tugas), dan `EmptyStateCoverageTest` (daftar tetap kini memuat pilihan onboarding baru, bukan ringkasan lama). 1 test Spatial melewati dirinya tanpa `SPATIAL_TEST_DSN`.

- [ ] **Step 3: Tulis implementasinya.** Buat berkas dengan
`DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:enum --no-interaction InterestRegion`,
`... make:class --no-interaction Services/Profil/TurunanPengalaman`, dan
`... make:migration --no-interaction add_interest_regions_to_user_preferences_table` (ganti nama berkasnya menjadi
`2026_10_08_000600_add_interest_regions_to_user_preferences_table.php`), lalu isinya diganti persis. Berkas TypeScript
dibuat langsung.

Buat `app/Enums/InterestRegion.php`:

```php
<?php

namespace App\Enums;

/**
 * Wilayah minat pendaki (DK-42; spec v3 bagian 9.3, "kode provinsi atau kelompok wilayah"):
 * daftar putih kelompok provinsi yang menutup seluruh Indonesia, supaya tidak ada pendaki yang
 * wilayahnya tidak dapat dipilih. Disimpan sebagai larik JSON di user_preferences.interest_regions.
 * Wilayah minat tidak ikut menilai kecocokan jalur (Route Fit tidak berubah, R-021).
 */
enum InterestRegion: string
{
    case JAWA_BARAT_BANTEN = 'JAWA_BARAT_BANTEN';
    case JAWA_TENGAH_DIY = 'JAWA_TENGAH_DIY';
    case JAWA_TIMUR = 'JAWA_TIMUR';
    case BALI_NUSA_TENGGARA = 'BALI_NUSA_TENGGARA';
    case SUMATERA = 'SUMATERA';
    case KALIMANTAN = 'KALIMANTAN';
    case SULAWESI = 'SULAWESI';
    case MALUKU_PAPUA = 'MALUKU_PAPUA';

    public function label(): string
    {
        return match ($this) {
            self::JAWA_BARAT_BANTEN => 'Jawa Barat dan Banten',
            self::JAWA_TENGAH_DIY => 'Jawa Tengah dan DI Yogyakarta',
            self::JAWA_TIMUR => 'Jawa Timur',
            self::BALI_NUSA_TENGGARA => 'Bali dan Nusa Tenggara',
            self::SUMATERA => 'Sumatera',
            self::KALIMANTAN => 'Kalimantan',
            self::SULAWESI => 'Sulawesi',
            self::MALUKU_PAPUA => 'Maluku dan Papua',
        };
    }

    /**
     * Nilai tersimpan menjadi kasus enum dalam urutan daftar ini; nilai yang tidak dikenal
     * dilewati, bukan menjatuhkan halaman.
     *
     * @param  array<int, mixed>|null  $nilai
     * @return list<self>
     */
    public static function dari(?array $nilai): array
    {
        $dikenal = array_map('strval', array_filter($nilai ?? [], 'is_string'));

        return array_values(array_filter(self::cases(), fn (self $wilayah) => in_array($wilayah->value, $dikenal, true)));
    }
}
```

Buat `app/Services/Profil/TurunanPengalaman.php`:

```php
<?php

namespace App\Services\Profil;

use App\Enums\NavigationComplexity;
use App\Enums\NavigationExperience;
use App\Enums\TerrainCharacter;
use App\Models\Trail;
use App\Services\Trails\CariJalur;
use App\Services\Trails\TrailFigures;

/**
 * Turunan pengalaman dari jalur terberat yang pernah diselesaikan (DK-41; spec v3 bagian 6.16):
 * tanjakan, medan, dan navigasi jalur itu menjadi pengalaman yang dikonfirmasi pendaki. Angka
 * jalur yang belum tercatat tetap tidak diketahui, tidak menjadi nol (R-007). Tingkat
 * pengalaman tidak pernah diturunkan dari sini: tingkat diisi sendiri (BR-11).
 *
 * Navigasi yang diturunkan adalah navigasi yang dituntut jalur itu
 * (NavigationComplexity::expectedNavigationRank), satu-satunya tangga yang sama dengan penilai
 * kecocokan, jadi turunan ini tidak menambah aturan baru ke Route Fit.
 */
final class TurunanPengalaman
{
    /**
     * @return array{tanjakan_m: int|null, durasi_menit: int|null, medan: list<string>, navigasi: string|null}
     */
    public static function dariJalur(Trail $jalur): array
    {
        $durasi = $jalur->estimated_duration_minutes;

        return [
            'tanjakan_m' => $jalur->elevation_gain_m,
            'durasi_menit' => $durasi !== null && $durasi > 0 ? $durasi : null,
            'medan' => array_map(fn (TerrainCharacter $medan) => $medan->value, $jalur->terrainTypes()),
            'navigasi' => self::navigasi($jalur->navigation_complexity)?->value,
        ];
    }

    public static function navigasi(?NavigationComplexity $kompleksitas): ?NavigationExperience
    {
        if ($kompleksitas === null) {
            return null;
        }

        foreach (NavigationExperience::cases() as $navigasi) {
            if ($navigasi->rank() === $kompleksitas->expectedNavigationRank()) {
                return $navigasi;
            }
        }

        return null;
    }

    /**
     * Satu hasil pencarian jalur terberat: baris katalog, nilai turunan mentah (untuk "Ubah
     * nilai"), dan tulisannya untuk dikonfirmasi.
     *
     * @return array<string, mixed>
     */
    public static function baris(Trail $jalur): array
    {
        $turunan = self::dariJalur($jalur);
        $navigasi = self::navigasi($jalur->navigation_complexity);

        return [
            ...CariJalur::baris($jalur),
            'turunan' => $turunan,
            'tulisan' => [
                'tanjakan' => TrailFigures::meter($turunan['tanjakan_m']),
                'durasi' => TrailFigures::durasi($turunan['durasi_menit']),
                'medan' => $turunan['medan'] === [] ? 'Belum tercatat' : TrailFigures::medan($jalur),
                'navigasi' => $navigasi === null ? 'Belum tercatat' : $navigasi->label().': '.lcfirst($navigasi->keterangan()),
            ],
        ];
    }
}
```

Buat `database/migrations/2026_10_08_000600_add_interest_regions_to_user_preferences_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wilayah minat (DK-42; spec v3 bagian 9.3): larik JSON kode App\Enums\InterestRegion di
 * preferensi pendaki. Daftar putihnya ditegakkan validasi; kolomnya aditif dan boleh kosong,
 * karena kosong berarti pendaki belum memilih wilayah, bukan semua atau tidak satu pun.
 * Kolom teks bebas region_preference yang lama tidak disentuh.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_preferences', function (Blueprint $table) {
            $table->json('interest_regions')->nullable()->after('region_preference');
        });
    }

    public function down(): void
    {
        Schema::table('user_preferences', function (Blueprint $table) {
            $table->dropColumn('interest_regions');
        });
    }
};
```

Buat `resources/js/components/onboarding/PengalamanTerberat.tsx`:

```tsx
import { useEffect, useRef } from 'react';
import { PemilihJalur } from '@/components/trails/PemilihJalur';
import { Button } from '@/components/ui/Button';
import { ChipPilihan } from '@/components/ui/Chip';
import { InputError, Label, TextInput } from '@/components/ui/Field';
import { Stepper } from '@/components/ui/Stepper';
import { PilihanKartu } from './PilihanKartu';
import type { CaraPengalaman, JalurTerberat, NilaiOnboarding, PilihanOnboarding } from './types';

interface PengalamanTerberatProps {
    data: NilaiOnboarding;
    ubah: (perubahan: Partial<NilaiOnboarding>) => void;
    jalur: JalurTerberat | null;
    setJalur: (jalur: JalurTerberat | null) => void;
    pilihan: PilihanOnboarding;
    galat: Partial<Record<string, string>>;
}

const kalimatCara: Record<CaraPengalaman, string> = {
    KATALOG: 'Cari jalur terberat yang pernah Anda selesaikan.',
    MANUAL: 'Isi pengalaman Anda sendiri.',
    BELUM: 'Anda belum pernah mendaki sampai selesai.',
};

/**
 * Langkah 2 onboarding (DK-41; spec v3 bagian 6.16): jalur terberat dari katalog dengan nilai
 * turunan untuk dikonfirmasi (tanjakan, durasi, medan, navigasi), isian sendiri bila jalurnya
 * tidak ada di daftar atau nilainya perlu diubah, atau "belum pernah mendaki". Nilai turunan
 * ditampilkan dari server; yang disimpan pun diturunkan server dari jalur yang sama. Berganti
 * cara memindah fokus ke kalimat pembuka cara itu, jadi pengguna papan ketik tidak kehilangan
 * tempat ketika tombol yang ditekannya hilang.
 */
export function PengalamanTerberat({ data, ubah, jalur, setJalur, pilihan, galat }: PengalamanTerberatProps) {
    const cara = data.cara_pengalaman;
    const pertama = useRef(true);

    useEffect(() => {
        if (pertama.current) {
            pertama.current = false;

            return;
        }

        document.getElementById('pengalaman-cara')?.focus();
    }, [cara]);

    const keCara = (baru: CaraPengalaman) => ubah({ cara_pengalaman: baru });

    const pilihJalur = (j: JalurTerberat | null) => {
        setJalur(j);

        if (j === null) {
            ubah({ jalur_terberat_id: '' });

            return;
        }

        const durasi = j.turunan.durasi_menit;
        ubah({
            jalur_terberat_id: j.id,
            durasi_terlama_jam: data.durasi_terlama_jam === '' && durasi !== null && durasi <= 24 * 60 ? String(Math.ceil(durasi / 60)) : data.durasi_terlama_jam,
        });
    };

    // "Ubah nilai": isian sendiri dimulai dari nilai turunan jalur yang dipilih.
    const ubahNilai = () => {
        if (jalur === null) {
            keCara('MANUAL');

            return;
        }

        ubah({
            cara_pengalaman: 'MANUAL',
            terrain_experience: jalur.turunan.medan,
            navigation_experience: jalur.turunan.navigasi ?? '',
            highest_elevation_gain_m: jalur.turunan.tanjakan_m ?? '',
        });
    };

    const pindahCara = (
        <div role="group" aria-label="Cara lain mengisi pengalaman" aria-describedby={galat.cara_pengalaman ? 'pengalaman-cara-galat' : undefined} className="flex flex-wrap gap-x-2">
            {cara !== 'KATALOG' ? (
                <Button variant="ghost" size="sm" className="-ml-4" onClick={() => keCara('KATALOG')}>
                    {cara === 'BELUM' ? 'Saya pernah mendaki' : 'Cari di katalog'}
                </Button>
            ) : null}
            {cara === 'KATALOG' ? (
                <Button variant="ghost" size="sm" className="-ml-4" onClick={() => keCara('MANUAL')}>
                    Jalurnya tidak ada di daftar
                </Button>
            ) : null}
            {cara !== 'BELUM' ? (
                <Button variant="ghost" size="sm" className="-ml-4" onClick={() => keCara('BELUM')}>
                    Saya belum pernah mendaki
                </Button>
            ) : null}
        </div>
    );

    return (
        <div className="space-y-5">
            <p id="pengalaman-cara" tabIndex={-1} className="text-isi font-semibold text-primary focus:outline-none">
                {kalimatCara[cara]}
            </p>
            <InputError id="pengalaman-cara-galat" messages={galat.cara_pengalaman} />

            {cara === 'KATALOG' ? (
                <PemilihJalur<JalurTerberat> label="Jalur terberat" dipilih={jalur} onPilih={pilihJalur} galat={galat.jalur_terberat_id}>
                    {jalur ? (
                        <>
                            <dl className="mt-2 grid grid-cols-1 gap-x-4 gap-y-1 text-kecil min-[360px]:grid-cols-[auto_minmax(0,1fr)]">
                                <dt className="text-secondary">Tanjakan</dt>
                                <dd data-angka className="font-semibold text-primary">
                                    {jalur.tulisan.tanjakan}
                                </dd>
                                <dt className="text-secondary">Durasi</dt>
                                <dd data-angka className="font-semibold text-primary">
                                    {jalur.tulisan.durasi}
                                </dd>
                                <dt className="text-secondary">Medan</dt>
                                <dd className="font-semibold text-primary">{jalur.tulisan.medan}</dd>
                                <dt className="text-secondary">Navigasi</dt>
                                <dd className="font-semibold text-primary">{jalur.tulisan.navigasi}</dd>
                            </dl>
                            <p className="mt-2 text-kecil text-secondary">
                                Nilai ini dicatat sebagai pengalaman Anda. Bila tidak sesuai, misalnya karena Anda dipandu, ubah sendiri.
                            </p>
                            <Button variant="ghost" size="sm" className="-ml-4" onClick={ubahNilai}>
                                Ubah nilai
                            </Button>
                        </>
                    ) : null}
                </PemilihJalur>
            ) : null}

            {cara === 'MANUAL' ? (
                <div className="space-y-5">
                    <div>
                        <Label htmlFor="pengalaman-tanjakan">Tanjakan terbesar dalam satu pendakian (meter)</Label>
                        <TextInput
                            id="pengalaman-tanjakan"
                            type="number"
                            inputMode="numeric"
                            min={1}
                            max={9000}
                            value={data.highest_elevation_gain_m}
                            onChange={(e) => ubah({ highest_elevation_gain_m: e.target.value })}
                            aria-invalid={galat.highest_elevation_gain_m ? true : undefined}
                            aria-describedby={galat.highest_elevation_gain_m ? 'pengalaman-tanjakan-hint pengalaman-tanjakan-galat' : 'pengalaman-tanjakan-hint'}
                        />
                        <p id="pengalaman-tanjakan-hint" className="mt-1 text-kecil text-secondary">
                            Boleh dikosongkan bila tidak tahu; yang kosong tidak dianggap kecil.
                        </p>
                        <InputError id="pengalaman-tanjakan-galat" messages={galat.highest_elevation_gain_m} className="mt-2" />
                    </div>

                    <fieldset aria-describedby={galat.terrain_experience ? 'pengalaman-medan-galat' : undefined}>
                        <legend className="text-isi font-bold text-primary">Medan yang pernah Anda lalui</legend>
                        <div className="mt-2 flex flex-wrap gap-x-1">
                            {pilihan.medan.map((m) => (
                                <ChipPilihan
                                    key={m.nilai}
                                    dipilih={data.terrain_experience.includes(m.nilai)}
                                    onUbah={(dipilih) =>
                                        ubah({ terrain_experience: dipilih ? [...data.terrain_experience, m.nilai] : data.terrain_experience.filter((v) => v !== m.nilai) })
                                    }
                                >
                                    {m.label}
                                </ChipPilihan>
                            ))}
                        </div>
                        <InputError id="pengalaman-medan-galat" messages={galat.terrain_experience} className="mt-2" />
                    </fieldset>

                    <div>
                        <PilihanKartu
                            nama="navigation_experience"
                            label="Pengalaman navigasi"
                            pilihan={pilihan.navigasi}
                            nilai={data.navigation_experience}
                            onUbah={(nilai) => ubah({ navigation_experience: nilai })}
                            penjelas={galat.navigation_experience ? 'pengalaman-navigasi-galat' : undefined}
                        />
                        <InputError id="pengalaman-navigasi-galat" messages={galat.navigation_experience} className="mt-2" />
                    </div>
                </div>
            ) : null}

            {cara === 'BELUM' ? (
                <div className="rounded-tombol border border-subtle bg-surface p-4 text-kecil text-primary">
                    <p>Kami mencatat nol pendakian dan pengalaman navigasi "Belum ada". Itu jawaban yang Anda berikan, bukan nilai yang ditebak.</p>
                    <p className="mt-2 text-secondary">Kecocokan jalur dinilai dari tingkat yang Anda pilih. Ubah kapan saja di Profil sesudah pendakian pertama Anda.</p>
                </div>
            ) : null}

            {cara !== 'BELUM' ? (
                <div className="space-y-5 border-t border-subtle pt-5">
                    <div>
                        <Stepper
                            label="Pendakian yang sudah selesai"
                            keterangan={cara === 'KATALOG' ? 'Termasuk jalur terberat itu.' : 'Kira-kira saja.'}
                            nilai={data.completed_hikes_count}
                            min={1}
                            max={2000}
                            onUbah={(nilai) => ubah({ completed_hikes_count: nilai })}
                            penjelas={galat.completed_hikes_count ? 'pengalaman-jumlah-galat' : undefined}
                        />
                        <InputError id="pengalaman-jumlah-galat" messages={galat.completed_hikes_count} className="mt-2" />
                    </div>
                    <div>
                        <Label htmlFor="pengalaman-durasi">Durasi mendaki terlama dalam satu hari (jam)</Label>
                        <TextInput
                            id="pengalaman-durasi"
                            type="number"
                            inputMode="numeric"
                            min={1}
                            max={24}
                            value={data.durasi_terlama_jam}
                            onChange={(e) => ubah({ durasi_terlama_jam: e.target.value })}
                            aria-invalid={galat.durasi_terlama_jam ? true : undefined}
                            aria-describedby={galat.durasi_terlama_jam ? 'pengalaman-durasi-hint pengalaman-durasi-galat' : 'pengalaman-durasi-hint'}
                        />
                        <p id="pengalaman-durasi-hint" className="mt-1 text-kecil text-secondary">
                            Tanpa bermalam. Boleh dikosongkan.
                        </p>
                        <InputError id="pengalaman-durasi-galat" messages={galat.durasi_terlama_jam} className="mt-2" />
                    </div>
                </div>
            ) : null}

            {pindahCara}
        </div>
    );
}
```

Buat `resources/js/components/onboarding/PilihanKartu.tsx`:

```tsx
import { kelas } from '@/lib/kelas';
import { fokusDiDalam } from '@/components/ui/gaya';
import { Icon } from '@/components/ui/Icon';

/** Satu pilihan dari server: nilai enum, label, dan keterangan opsional. */
export interface OpsiKartu {
    nilai: string;
    label: string;
    keterangan?: string;
}

interface PilihanKartuProps {
    /** Nama grup radio, unik di satu halaman. */
    nama: string;
    /** Legend grup. Disembunyikan bila judul yang sama sudah tertulis di atasnya. */
    label: string;
    labelTersembunyi?: boolean;
    /** Kartu bertumpuk dengan keterangan, atau chip sebaris untuk pilihan pendek tanpa keterangan. */
    bentuk?: 'kartu' | 'chip';
    pilihan: OpsiKartu[];
    nilai: string;
    onUbah: (nilai: string) => void;
    /** id galat yang menjelaskan grup ini. */
    penjelas?: string;
}

/**
 * Grup radio satu pilihan (prototype 00). Bentuk kartu: satu baris 44 px atau lebih dengan label
 * dan keterangannya. Bentuk chip: label pendek yang membungkus antarchip, tidak di tengah kata,
 * seperti ChipPilihan tetapi radio. Pilihan aktif ditandai titik radio atau centang, garis, dan
 * latar, bukan warna saja (WCAG 1.4.1); tombol panah berpindah pilihan karena ini radio asli.
 */
export function PilihanKartu({ nama, label, labelTersembunyi = false, bentuk = 'kartu', pilihan, nilai, onUbah, penjelas }: PilihanKartuProps) {
    return (
        <fieldset aria-describedby={penjelas}>
            <legend className={labelTersembunyi ? 'sr-only' : 'text-isi font-bold text-primary'}>{label}</legend>
            <div className={kelas(bentuk === 'chip' ? 'flex flex-wrap gap-x-1' : 'space-y-2', !labelTersembunyi && 'mt-2')}>
                {pilihan.map((p) =>
                    bentuk === 'chip' ? (
                        <label key={p.nilai} className="inline-flex min-h-11 min-w-11 max-w-full cursor-pointer items-center">
                            <input type="radio" name={nama} value={p.nilai} checked={nilai === p.nilai} onChange={() => onUbah(p.nilai)} className="peer sr-only" />
                            <span
                                className={kelas(
                                    'inline-flex min-h-9 max-w-full items-center gap-1.5 rounded-full border px-3.5 py-1 text-kecil',
                                    'peer-focus-visible:ring-2 peer-focus-visible:ring-brand-600 peer-focus-visible:ring-offset-2',
                                    nilai === p.nilai ? 'border-brand-700 bg-brand-100 font-bold text-brand-900' : 'border-control bg-surface font-semibold text-primary hover:bg-surface-sunken',
                                )}
                            >
                                {nilai === p.nilai ? <Icon name="check" size={16} /> : null}
                                <span className="min-w-0 break-words">{p.label}</span>
                            </span>
                        </label>
                    ) : (
                        <label
                            key={p.nilai}
                            className={kelas(
                                'flex min-h-11 cursor-pointer items-start gap-3 rounded-tombol border border-subtle bg-surface px-4 py-3',
                                'has-[:checked]:border-brand-700 has-[:checked]:bg-brand-50',
                                fokusDiDalam,
                            )}
                        >
                            <input
                                type="radio"
                                name={nama}
                                value={p.nilai}
                                checked={nilai === p.nilai}
                                onChange={() => onUbah(p.nilai)}
                                className="mt-0.5 size-5 shrink-0 border-2 border-control text-brand-700 focus:ring-0 focus:ring-offset-0"
                            />
                            <span className="min-w-0">
                                <span className="block break-words text-isi font-bold text-primary">{p.label}</span>
                                {p.keterangan ? <span className="mt-0.5 block text-kecil text-secondary">{p.keterangan}</span> : null}
                            </span>
                        </label>
                    ),
                )}
            </div>
        </fieldset>
    );
}
```

Buat `resources/js/components/onboarding/types.ts`:

```ts
import type { JalurKatalog } from '@/components/trails/PemilihJalur';
import type { OpsiKartu } from './PilihanKartu';

/** Cara mengisi langkah 2 (App\Http\Requests\StoreProfileSetupRequest). */
export type CaraPengalaman = 'KATALOG' | 'MANUAL' | 'BELUM';

/** Isian onboarding, sama dengan kunci yang dibaca StoreProfileSetupRequest. */
export interface NilaiOnboarding {
    experience_level: string;
    cara_pengalaman: CaraPengalaman;
    jalur_terberat_id: number | '';
    completed_hikes_count: number;
    terrain_experience: string[];
    navigation_experience: string;
    highest_elevation_gain_m: number | string;
    durasi_terlama_jam: string;
    preferred_trip_type: string;
    preferred_duration: string;
    interest_regions: string[];
    max_elevation_gain_preference_m: number | string;
}

/** Hasil pencarian jalur terberat (App\Services\Profil\TurunanPengalaman::baris). */
export interface JalurTerberat extends JalurKatalog {
    turunan: { tanjakan_m: number | null; durasi_menit: number | null; medan: string[]; navigasi: string | null };
    tulisan: { tanjakan: string; durasi: string; medan: string; navigasi: string };
}

export interface PilihanOnboarding {
    tingkat: OpsiKartu[];
    navigasi: OpsiKartu[];
    medan: OpsiKartu[];
    jenisTrip: OpsiKartu[];
    durasi: OpsiKartu[];
    wilayah: OpsiKartu[];
}
```

`app/Enums/ExperienceLevel.php`:

1. Ganti:

```php
    /**
     * Ordinal position used for comparing hiker capability against route demand.
```

dengan:

```php
    /**
     * Penjelasan tingkat di onboarding dan Profil (prototype 00). Tingkat diisi sendiri dan
     * tidak naik otomatis karena riwayat (BR-11).
     */
    public function keterangan(): string
    {
        return match ($this) {
            self::BEGINNER => 'Baru beberapa kali, di jalur yang ramai dan bertanda.',
            self::INTERMEDIATE => 'Sudah beberapa kali, termasuk bermalam di gunung.',
            self::ADVANCED => 'Rutin mendaki jalur panjang dan membaca medan sendiri.',
            self::EXPERT => 'Navigasi mandiri, termasuk di jalur yang jarang dilalui.',
        };
    }

    /**
     * Ordinal position used for comparing hiker capability against route demand.
```

`app/Enums/NavigationExperience.php`:

1. Ganti:

```php
    }

    public function rank(): int
```

dengan:

```php
    }

    /**
     * Penjelasan pengalaman navigasi di onboarding dan Profil, dengan tingkatan yang sama dengan
     * tuntutan navigasi jalur (NavigationComplexity::expectedNavigationRank).
     */
    public function keterangan(): string
    {
        return match ($this) {
            self::NONE => 'Mengikuti jalur yang jelas atau rombongan.',
            self::BASIC => 'Membaca papan penunjuk dan jalur bertanda.',
            self::COMPETENT => 'Membaca peta dan memilih arah di percabangan sendiri.',
            self::ADVANCED => 'Navigasi mandiri di jalur yang jarang dilalui.',
        };
    }

    public function rank(): int
```

Tulis ulang `app/Http/Controllers/Onboarding/ProfileSetupController.php` utuh:

```php
<?php

namespace App\Http\Controllers\Onboarding;

use App\Enums\AnalyticsEvent;
use App\Enums\ExperienceLevel;
use App\Enums\InterestRegion;
use App\Enums\NavigationExperience;
use App\Enums\PreferredDuration;
use App\Enums\RouteFitLabel;
use App\Enums\TerrainCharacter;
use App\Enums\TripType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProfileSetupRequest;
use App\Models\Trail;
use App\Models\User;
use App\Services\AnalyticsRecorder;
use App\Services\Profil\TurunanPengalaman;
use App\Services\Trails\CariJalur;
use App\Services\Trails\HalamanJelajah;
use App\Services\Trails\TrailSearch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Onboarding profil pendaki (FR-02; DK-41, DK-42; spec v3 bagian 6.16; prototype 00), yang juga
 * menjadi penyunting pengalaman dan preferensi dari Profil. Tiga langkah: tingkat pengalaman,
 * jalur terberat dengan nilai turunannya, lalu rencana dan wilayah minat. Sesudah disimpan,
 * halaman menyebut akibat langsungnya, "N jalur berlabel Cocok", dihitung server dengan
 * saringan yang sama dengan Jelajah. "Nanti saja" menuju Jelajah tanpa menyimpan apa pun, jadi
 * label tetap "Belum dinilai" (R-006).
 */
class ProfileSetupController extends Controller
{
    public function show(Request $request, CariJalur $cari): Response
    {
        $user = $request->user()->loadMissing('profile', 'experience', 'preference');
        $kata = $request->query('cari');
        $langkah = $request->integer('langkah');

        return Inertia::render('Onboarding/ProfileSetup', [
            'title' => 'Profil Pendaki',
            'sudahLengkap' => $user->hasCompletedProfile(),
            'langkahAwal' => in_array($langkah, [1, 2, 3], true) ? $langkah : 1,
            'nilai' => $this->nilai($user),
            'pilihan' => [
                'tingkat' => array_map(fn (ExperienceLevel $t) => ['nilai' => $t->value, 'label' => $t->label(), 'keterangan' => $t->keterangan()], ExperienceLevel::cases()),
                'navigasi' => array_map(fn (NavigationExperience $n) => ['nilai' => $n->value, 'label' => $n->label(), 'keterangan' => $n->keterangan()], NavigationExperience::cases()),
                'medan' => array_map(fn (TerrainCharacter $m) => ['nilai' => $m->value, 'label' => $m->label()], TerrainCharacter::cases()),
                'jenisTrip' => array_map(fn (TripType $j) => ['nilai' => $j->value, 'label' => $j->labelPendek(), 'keterangan' => self::keteranganJenis($j)], TripType::cases()),
                'durasi' => array_map(fn (PreferredDuration $d) => ['nilai' => $d->value, 'label' => $d->label()], PreferredDuration::cases()),
                'wilayah' => array_map(fn (InterestRegion $w) => ['nilai' => $w->value, 'label' => $w->label()], InterestRegion::cases()),
            ],
            // Hanya lewat muat ulang parsial saat pendaki mencari jalur terberatnya.
            'pilihanJalur' => Inertia::optional(fn () => $cari->untuk(is_string($kata) ? $kata : '')->map(fn (Trail $t) => TurunanPengalaman::baris($t))->all()),
            'selesai' => $request->session()->get('onboarding-selesai'),
            'tautan' => [
                'jelajah' => route('trails.index'),
                'cocok' => route('trails.index', ['kecocokan' => RouteFitLabel::COCOK->value]),
                'profil' => route('profile'),
            ],
        ]);
    }

    public function store(StoreProfileSetupRequest $request, AnalyticsRecorder $analytics, HalamanJelajah $jelajah): RedirectResponse
    {
        // Pemeriksaan satu langkah: galat sudah dikembalikan FormRequest; lolos berarti
        // pendaki boleh pindah langkah. Tidak ada yang disimpan.
        if ($request->langkahDiperiksa() !== null) {
            return redirect()->route('onboarding');
        }

        $user = $request->user();
        $data = $request->validated();
        $pengalaman = $request->pengalaman();

        DB::transaction(function () use ($user, $data, $pengalaman, $request) {
            $user->profile()->updateOrCreate([], [
                'experience_level' => $data['experience_level'],
                'completed_at' => now(),
            ]);
            $user->experience()->updateOrCreate([], $pengalaman);
            $user->preference()->updateOrCreate([], [
                'preferred_trip_type' => $data['preferred_trip_type'],
                'preferred_duration' => $data['preferred_duration'],
                'max_elevation_gain_preference_m' => $data['max_elevation_gain_preference_m'] ?? null,
                'interest_regions' => $request->wilayah(),
            ]);
        });

        $analytics->record(AnalyticsEvent::PROFILE_COMPLETED, $user);

        // Saringan dan penilaian yang sama dengan Jelajah ?kecocokan=COCOK, jadi angka di sini
        // sama dengan jumlah yang tampil begitu pendaki membukanya.
        $cocok = $jelajah->props(TrailSearch::dariInput(['kecocokan' => RouteFitLabel::COCOK->value]), $user->fresh(), $request)['ringkasan']['jumlah'];

        return redirect()->route('onboarding')->with('onboarding-selesai', ['cocok' => $cocok]);
    }

    /**
     * Isian awal dari profil yang sudah ada. Pengalaman yang sudah tersimpan dibuka sebagai isian
     * sendiri (MANUAL), karena jalur terberatnya tidak disimpan; nol pendakian dibuka sebagai
     * "belum pernah mendaki". Yang belum diisi tetap kosong (R-007).
     *
     * @return array<string, mixed>
     */
    private function nilai(User $user): array
    {
        $pengalaman = $user->experience;
        $cara = match (true) {
            $pengalaman === null => StoreProfileSetupRequest::KATALOG,
            $pengalaman->completed_hikes_count === 0 => StoreProfileSetupRequest::BELUM,
            default => StoreProfileSetupRequest::MANUAL,
        };
        $durasi = $pengalaman?->longest_hike_duration_minutes;

        return [
            'experience_level' => $user->profile?->experience_level?->value ?? '',
            'cara_pengalaman' => $cara,
            'jalur_terberat_id' => '',
            'completed_hikes_count' => max(1, $pengalaman?->completed_hikes_count ?? 1),
            'terrain_experience' => array_values($pengalaman?->terrain_experience ?? []),
            'navigation_experience' => $pengalaman?->navigation_experience?->value ?? '',
            'highest_elevation_gain_m' => $pengalaman?->highest_elevation_gain_m ?? '',
            'durasi_terlama_jam' => $durasi !== null && $durasi > 0 ? (string) min(24, (int) ceil($durasi / 60)) : '',
            'preferred_trip_type' => $user->preference?->preferred_trip_type?->value ?? '',
            'preferred_duration' => $user->preference?->preferred_duration?->value ?? '',
            'interest_regions' => array_map(fn (InterestRegion $w) => $w->value, InterestRegion::dari($user->preference?->interest_regions)),
            'max_elevation_gain_preference_m' => $user->preference?->max_elevation_gain_preference_m ?? '',
        ];
    }

    private static function keteranganJenis(TripType $jenis): string
    {
        return match ($jenis) {
            TripType::TEKTOK => 'Naik dan turun di hari yang sama.',
            TripType::CAMPING => 'Bermalam satu kali di area kemah.',
            TripType::MULTI_DAY => 'Lebih dari dua hari, dengan logistik lebih banyak.',
        };
    }
}
```

Tulis ulang `app/Http/Requests/StoreProfileSetupRequest.php` utuh:

```php
<?php

namespace App\Http\Requests;

use App\Enums\ExperienceLevel;
use App\Enums\InterestRegion;
use App\Enums\NavigationExperience;
use App\Enums\PreferredDuration;
use App\Enums\TerrainCharacter;
use App\Enums\TripType;
use App\Models\Trail;
use App\Services\Profil\TurunanPengalaman;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * FR-02 profil pendaki dalam tiga langkah onboarding (DK-41, DK-42; spec v3 bagian 6.16).
 * Pengalaman yang diisi sendiri adalah masukan rekomendasi, bukan sertifikasi (PRD §22).
 *
 * Langkah 2 punya tiga cara: KATALOG (jalur terberat dari katalog; tanjakan, medan, dan
 * navigasi diturunkan server dari jalur itu, bukan dari isian), MANUAL (pendaki mengisi
 * sendiri, termasuk sesudah "Ubah nilai"), dan BELUM (belum pernah mendaki: nol pendakian dan
 * navigasi "Belum ada" adalah jawaban yang diketahui, bukan UNKNOWN yang dianggap rendah).
 *
 * Isian `langkah` (1 atau 2) meminta pemeriksaan satu langkah saja; tanpa `langkah`, atau
 * langkah 3, seluruh aturan berlaku dan profil disimpan. Kolom lama yang tidak lagi ditanyakan
 * (region_preference, bio, preferred_challenge) tidak disentuh.
 */
class StoreProfileSetupRequest extends FormRequest
{
    public const KATALOG = 'KATALOG';

    public const MANUAL = 'MANUAL';

    public const BELUM = 'BELUM';

    /** @var array<int, array<int, string>> */
    private const ISIAN_LANGKAH = [
        1 => ['experience_level'],
        2 => [
            'cara_pengalaman', 'jalur_terberat_id', 'completed_hikes_count', 'terrain_experience',
            'terrain_experience.*', 'navigation_experience', 'highest_elevation_gain_m', 'durasi_terlama_jam',
        ],
    ];

    /**
     * Langkah yang hanya diperiksa, bukan disimpan; null berarti simpan.
     */
    public function langkahDiperiksa(): ?int
    {
        $langkah = (int) $this->input('langkah', 3);

        return array_key_exists($langkah, self::ISIAN_LANGKAH) ? $langkah : null;
    }

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $bukanBelum = 'exclude_if:cara_pengalaman,'.self::BELUM;
        $hanyaManual = 'exclude_unless:cara_pengalaman,'.self::MANUAL;

        $semua = [
            'experience_level' => ['required', Rule::enum(ExperienceLevel::class)],
            'cara_pengalaman' => ['required', Rule::in([self::KATALOG, self::MANUAL, self::BELUM])],
            'jalur_terberat_id' => [
                'exclude_unless:cara_pengalaman,'.self::KATALOG,
                'required',
                'integer',
                Rule::exists('trails', 'id')->where(fn ($q) => $q->where('is_published', true)->whereNull('archived_at')),
            ],
            'completed_hikes_count' => [$bukanBelum, 'required', 'integer', 'min:1', 'max:2000'],
            'terrain_experience' => [$hanyaManual, 'array', 'max:'.count(TerrainCharacter::cases())],
            'terrain_experience.*' => [$hanyaManual, 'distinct', Rule::enum(TerrainCharacter::class)],
            'navigation_experience' => [$hanyaManual, 'required', Rule::enum(NavigationExperience::class)],
            'highest_elevation_gain_m' => [$hanyaManual, 'nullable', 'integer', 'min:1', 'max:9000'],
            'durasi_terlama_jam' => [$bukanBelum, 'nullable', 'integer', 'min:1', 'max:24'],
            'preferred_trip_type' => ['required', Rule::enum(TripType::class)],
            'preferred_duration' => ['required', Rule::enum(PreferredDuration::class)],
            'interest_regions' => ['array', 'max:'.count(InterestRegion::cases())],
            'interest_regions.*' => ['distinct', Rule::enum(InterestRegion::class)],
            'max_elevation_gain_preference_m' => ['nullable', 'integer', 'min:1', 'max:9000'],
        ];

        $langkah = $this->langkahDiperiksa();

        return $langkah === null
            ? $semua
            : array_intersect_key($semua, array_flip(self::ISIAN_LANGKAH[$langkah]));
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'experience_level.required' => 'Pilih tingkat yang paling mendekati pengalaman Anda.',
            'cara_pengalaman.required' => 'Pilih jalur terberat Anda, isi sendiri, atau pilih "Saya belum pernah mendaki".',
            'jalur_terberat_id.required' => 'Pilih jalur dari hasil pencarian, atau pilih "Jalurnya tidak ada di daftar".',
            'jalur_terberat_id.exists' => 'Jalur itu tidak ada di katalog terbit. Cari lagi, atau isi sendiri.',
            'completed_hikes_count.min' => 'Jumlah pendakian paling sedikit 1. Bila belum pernah mendaki sampai selesai, pilih "Saya belum pernah mendaki".',
            'navigation_experience.required' => 'Pilih pengalaman navigasi Anda.',
            'highest_elevation_gain_m.max' => 'Tanjakan diisi dalam meter, paling banyak 9.000, atau dikosongkan.',
            'highest_elevation_gain_m.min' => 'Tanjakan diisi dalam meter, paling sedikit 1, atau dikosongkan.',
            'durasi_terlama_jam.*' => 'Durasi diisi dalam jam bulat, 1 sampai 24, atau dikosongkan.',
            'preferred_trip_type.required' => 'Pilih jenis trip yang paling sering Anda rencanakan.',
            'preferred_duration.required' => 'Pilih lama pendakian yang Anda sukai.',
            'interest_regions.*' => 'Pilih wilayah dari daftar yang tersedia.',
            'max_elevation_gain_preference_m.*' => 'Batas tanjakan diisi dalam meter, 1 sampai 9.000, atau dikosongkan.',
        ];
    }

    /**
     * Kolom user_experience menurut cara langkah 2. KATALOG membaca jalurnya sekali di sini dan
     * menurunkan nilainya; isian pengalaman dari klien diabaikan untuk cara itu.
     *
     * @return array{completed_hikes_count: int, terrain_experience: list<string>, navigation_experience: string, highest_elevation_gain_m: int|null, longest_hike_duration_minutes: int|null}
     */
    public function pengalaman(): array
    {
        $data = $this->validated();
        $jam = $data['durasi_terlama_jam'] ?? null;
        $durasi = $jam === null ? null : (int) $jam * 60;

        return match ($data['cara_pengalaman']) {
            self::BELUM => [
                'completed_hikes_count' => 0,
                'terrain_experience' => [],
                'navigation_experience' => NavigationExperience::NONE->value,
                'highest_elevation_gain_m' => null,
                'longest_hike_duration_minutes' => null,
            ],
            self::KATALOG => (function () use ($data, $durasi): array {
                $turunan = TurunanPengalaman::dariJalur(Trail::query()->findOrFail($data['jalur_terberat_id']));

                return [
                    'completed_hikes_count' => (int) $data['completed_hikes_count'],
                    'terrain_experience' => $turunan['medan'],
                    'navigation_experience' => $turunan['navigasi'] ?? NavigationExperience::NONE->value,
                    'highest_elevation_gain_m' => $turunan['tanjakan_m'],
                    'longest_hike_duration_minutes' => $durasi,
                ];
            })(),
            default => [
                'completed_hikes_count' => (int) $data['completed_hikes_count'],
                'terrain_experience' => array_values($data['terrain_experience'] ?? []),
                'navigation_experience' => $data['navigation_experience'],
                'highest_elevation_gain_m' => $data['highest_elevation_gain_m'] ?? null,
                'longest_hike_duration_minutes' => $durasi,
            ],
        };
    }

    /**
     * Wilayah minat dalam urutan daftar putih; tanpa pilihan disimpan null, bukan larik kosong.
     *
     * @return list<string>|null
     */
    public function wilayah(): ?array
    {
        $wilayah = array_map(fn (InterestRegion $w) => $w->value, InterestRegion::dari($this->validated('interest_regions')));

        return $wilayah === [] ? null : $wilayah;
    }

    /**
     * Isian kosong dari formulir React datang sebagai string kosong; aturan nullable dan
     * integer membacanya sebagai nilai, bukan ketiadaan.
     */
    protected function prepareForValidation(): void
    {
        $kosong = fn (mixed $v): mixed => $v === '' ? null : $v;

        $this->merge([
            'highest_elevation_gain_m' => $kosong($this->input('highest_elevation_gain_m')),
            'durasi_terlama_jam' => $kosong($this->input('durasi_terlama_jam')),
            'max_elevation_gain_preference_m' => $kosong($this->input('max_elevation_gain_preference_m')),
            'terrain_experience' => array_values((array) $this->input('terrain_experience', [])),
            'interest_regions' => array_values((array) $this->input('interest_regions', [])),
        ]);
    }
}
```

`app/Models/UserPreference.php`:

1. Ganti:

```php
    'user_id', 'preferred_duration', 'preferred_trip_type', 'preferred_challenge',
    'max_elevation_gain_preference_m', 'region_preference', 'record_track',
])]
```

dengan:

```php
    'user_id', 'preferred_duration', 'preferred_trip_type', 'preferred_challenge',
    'max_elevation_gain_preference_m', 'region_preference', 'record_track', 'interest_regions',
])]
```

2. Ganti:

```php
            'record_track' => 'boolean',
        ];
```

dengan:

```php
            'record_track' => 'boolean',
            // Wilayah minat DK-42: larik kode App\Enums\InterestRegion, dibaca lewat InterestRegion::dari().
            'interest_regions' => 'array',
        ];
```

Tulis ulang `resources/js/pages/Onboarding/ProfileSetup.tsx` utuh:

```tsx
import { useForm, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState, type FormEvent, type ReactNode } from 'react';
import { PengalamanTerberat } from '@/components/onboarding/PengalamanTerberat';
import { PilihanKartu } from '@/components/onboarding/PilihanKartu';
import type { JalurTerberat, NilaiOnboarding, PilihanOnboarding } from '@/components/onboarding/types';
import { BarAksi } from '@/components/ui/BarAksi';
import { Button } from '@/components/ui/Button';
import { ChipPilihan } from '@/components/ui/Chip';
import { InputError, Label, TextInput } from '@/components/ui/Field';
import { PageHeader } from '@/components/ui/PageHeader';
import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';
import { AppLayout } from '@/layouts/AppLayout';
import { profile } from '@/routes';
import { store } from '@/routes/onboarding';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    sudahLengkap: boolean;
    langkahAwal: number;
    nilai: NilaiOnboarding;
    pilihan: PilihanOnboarding;
    pilihanJalur?: JalurTerberat[];
    selesai: { cocok: number } | null;
    tautan: { jelajah: string; cocok: string; profil: string };
}

const JUMLAH_LANGKAH = 3;

/** Judul langkah 1 sampai 3, berurutan (prototype 00). */
const JUDUL_LANGKAH = [
    {
        kecil: 'Kenali pengalaman Anda',
        judul: 'Seberapa jauh pengalaman mendaki Anda?',
        penjelas: 'Pilih yang paling mendekati. Ini membantu menjelaskan jalur mana yang realistis untuk Anda.',
    },
    {
        kecil: 'Pendakian terberat Anda',
        judul: 'Jalur mana yang paling menantang yang pernah Anda selesaikan?',
        penjelas: 'Kami memakai tanjakan dan durasinya, bukan tinggi puncaknya. Gunung 2.000 mdpl bisa lebih berat daripada 3.000 mdpl.',
    },
    {
        kecil: 'Rencana Anda',
        judul: 'Pendakian seperti apa yang ingin Anda lakukan?',
        penjelas: null,
    },
] as const;

/** Isian milik tiap langkah, untuk kembali ke langkah yang galatnya dilaporkan server. */
const isianLangkah: Record<number, string[]> = {
    1: ['experience_level'],
    2: ['cara_pengalaman', 'jalur_terberat_id', 'completed_hikes_count', 'terrain_experience', 'navigation_experience', 'highest_elevation_gain_m', 'durasi_terlama_jam'],
    3: ['preferred_trip_type', 'preferred_duration', 'interest_regions', 'max_elevation_gain_preference_m'],
};

function langkahGalat(galat: Record<string, string | undefined>): number | null {
    const kunci = Object.keys(galat).map((k) => k.split('.')[0]);

    for (const langkah of [1, 2, 3]) {
        if ((isianLangkah[langkah] ?? []).some((isian) => kunci.includes(isian))) {
            return langkah;
        }
    }

    return null;
}

/**
 * Onboarding profil pendaki (FR-02; DK-41, DK-42; spec v3 bagian 6.16; prototype 00), juga
 * penyunting pengalaman dan preferensi dari Profil (`?langkah=` membuka langkah tertentu).
 * Setiap "Lanjut" diperiksa server dengan aturan langkah itu saja; langkah terakhir menyimpan
 * dan halaman menyebut akibat langsungnya, "N jalur berlabel Cocok", dihitung server dengan
 * saringan Jelajah. "Nanti saja" menuju Jelajah tanpa menyimpan, jadi label tetap "Belum
 * dinilai" (R-006). Halaman tugas: bilah tab disembunyikan.
 */
export default function ProfileSetup() {
    const { sudahLengkap, langkahAwal, nilai, pilihan, selesai, tautan } = usePage<Props>().props;
    const form = useForm<NilaiOnboarding>(nilai);
    const [langkah, setLangkah] = useState(langkahAwal);
    const [jalur, setJalur] = useState<JalurTerberat | null>(null);
    const bagian = useRef<HTMLElement>(null);
    const pindah = useRef(false);

    // Fokus ke langkah yang baru, atau ke hasil sesudah menyimpan, supaya pembaca layar tahu
    // isinya berganti. Render pertama tidak mencuri fokus.
    useEffect(() => {
        if (pindah.current || selesai !== null) {
            bagian.current?.focus();
        }
        pindah.current = true;
    }, [langkah, selesai]);

    const ubah = (perubahan: Partial<NilaiOnboarding>) => form.setData((d) => ({ ...d, ...perubahan }));

    const kirim = (langkahDiperiksa: number | null) => {
        form.transform((data) => (langkahDiperiksa === null ? data : { ...data, langkah: langkahDiperiksa }));
        form.post(store.url(), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                if (langkahDiperiksa !== null) {
                    setLangkah(langkahDiperiksa + 1);
                }
            },
            onError: (galat) => {
                const tujuan = langkahGalat(galat);
                if (tujuan !== null && tujuan !== langkah) {
                    setLangkah(tujuan);
                }
            },
        });
    };

    const simpan = (e: FormEvent) => {
        e.preventDefault();
        kirim(langkah < JUMLAH_LANGKAH ? langkah : null);
    };

    const galat = form.errors as Partial<Record<string, string>>;
    const galatWilayah = Object.entries(galat).find(([k]) => k.startsWith('interest_regions'))?.[1];
    const judul = JUDUL_LANGKAH[langkah - 1] ?? JUDUL_LANGKAH[0];

    return (
        <div className="py-6">
            <div className="mx-auto max-w-xl px-4 sm:px-6">
                <PageHeader
                    induk={{ href: profile.url(), label: 'Profil' }}
                    title="Profil Pendaki"
                    aksi={
                        !sudahLengkap && selesai === null ? (
                            <Tautan
                                href={tautan.jelajah}
                                className="inline-flex min-h-11 items-center px-2 text-kecil font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                            >
                                Nanti saja
                            </Tautan>
                        ) : undefined
                    }
                />

                {selesai !== null ? (
                    <section ref={bagian} tabIndex={-1} aria-labelledby="hasil-profil" className="space-y-4 rounded-utama border border-brand-200 bg-brand-50 p-5 focus:outline-none">
                        <h2 id="hasil-profil" className="text-bagian text-primary">
                            Profil pendaki tersimpan
                        </h2>
                        <p data-angka className="text-judul-kartu text-primary">
                            {selesai.cocok > 0 ? `Dengan jawaban ini, ${selesai.cocok} jalur berlabel Cocok.` : 'Dengan jawaban ini, belum ada jalur berlabel Cocok.'}
                        </p>
                        <p className="text-kecil text-secondary">
                            {selesai.cocok > 0
                                ? 'Label menilai kecocokan dasar dengan jawaban Anda. Status resmi dan kondisi jalur tetap diperiksa sebelum berangkat.'
                                : 'Jalur berlabel Perlu persiapan menyebut apa yang perlu disiapkan lebih dulu.'}
                        </p>
                        <div className="flex flex-wrap gap-3">
                            <Button href={selesai.cocok > 0 ? tautan.cocok : tautan.jelajah}>{selesai.cocok > 0 ? `Lihat ${selesai.cocok} jalur Cocok` : 'Buka Jelajah'}</Button>
                            <Button variant="secondary" href={tautan.profil}>
                                Ke Profil
                            </Button>
                        </div>
                    </section>
                ) : (
                    <form id="formulir-profil" onSubmit={simpan} noValidate>
                        <p data-angka className="text-meta font-bold text-secondary">
                            Langkah {langkah} dari {JUMLAH_LANGKAH}
                        </p>
                        <div aria-hidden="true" className="mt-2 flex gap-1">
                            {Array.from({ length: JUMLAH_LANGKAH }, (_, i) => (
                                <span key={i} className={kelas('h-1.5 flex-1 rounded-full', i < langkah ? 'bg-brand-700' : 'bg-surface-sunken')} />
                            ))}
                        </div>

                        <section ref={bagian} tabIndex={-1} aria-labelledby="judul-langkah" className="mt-6 space-y-5 focus:outline-none">
                            <div>
                                <p className="text-meta font-bold text-brand-ink">{judul.kecil}</p>
                                <h2 id="judul-langkah" className="mt-1 text-judul-kartu text-primary">
                                    {judul.judul}
                                </h2>
                                {judul.penjelas ? <p className="mt-2 text-kecil text-secondary">{judul.penjelas}</p> : null}
                            </div>

                            {langkah === 1 ? (
                                <div>
                                    <PilihanKartu
                                        nama="experience_level"
                                        label="Tingkat pengalaman"
                                        labelTersembunyi
                                        pilihan={pilihan.tingkat}
                                        nilai={form.data.experience_level}
                                        onUbah={(v) => ubah({ experience_level: v })}
                                        penjelas={galat.experience_level ? 'tingkat-galat' : undefined}
                                    />
                                    <InputError id="tingkat-galat" messages={galat.experience_level} className="mt-2" />
                                </div>
                            ) : null}

                            {langkah === 2 ? <PengalamanTerberat data={form.data} ubah={ubah} jalur={jalur} setJalur={setJalur} pilihan={pilihan} galat={galat} /> : null}

                            {langkah === 3 ? (
                                <div className="space-y-6">
                                    <div>
                                        <PilihanKartu
                                            nama="preferred_trip_type"
                                            label="Jenis trip yang paling sering Anda rencanakan"
                                            pilihan={pilihan.jenisTrip}
                                            nilai={form.data.preferred_trip_type}
                                            onUbah={(v) => ubah({ preferred_trip_type: v })}
                                            penjelas={galat.preferred_trip_type ? 'jenis-galat' : undefined}
                                        />
                                        <InputError id="jenis-galat" messages={galat.preferred_trip_type} className="mt-2" />
                                    </div>

                                    <div>
                                        <PilihanKartu
                                            nama="preferred_duration"
                                            label="Lama pendakian yang Anda sukai"
                                            bentuk="chip"
                                            pilihan={pilihan.durasi}
                                            nilai={form.data.preferred_duration}
                                            onUbah={(v) => ubah({ preferred_duration: v })}
                                            penjelas={galat.preferred_duration ? 'durasi-galat' : undefined}
                                        />
                                        <InputError id="durasi-galat" messages={galat.preferred_duration} className="mt-2" />
                                    </div>

                                    <fieldset aria-describedby={galatWilayah ? 'wilayah-hint wilayah-galat' : 'wilayah-hint'}>
                                        <legend className="text-isi font-bold text-primary">Wilayah yang Anda minati</legend>
                                        <p id="wilayah-hint" className="mt-1 text-kecil text-secondary">
                                            Boleh lebih dari satu, atau dilewati. Wilayah tidak mengubah label kecocokan.
                                        </p>
                                        <div className="mt-2 flex flex-wrap gap-x-1">
                                            {pilihan.wilayah.map((w) => (
                                                <ChipPilihan
                                                    key={w.nilai}
                                                    dipilih={form.data.interest_regions.includes(w.nilai)}
                                                    onUbah={(dipilih) =>
                                                        ubah({ interest_regions: dipilih ? [...form.data.interest_regions, w.nilai] : form.data.interest_regions.filter((v) => v !== w.nilai) })
                                                    }
                                                >
                                                    {w.label}
                                                </ChipPilihan>
                                            ))}
                                        </div>
                                        <InputError id="wilayah-galat" messages={galatWilayah} className="mt-2" />
                                    </fieldset>

                                    <div>
                                        <Label htmlFor="batas-tanjakan">Tanjakan terbesar yang masih nyaman untuk Anda (meter)</Label>
                                        <TextInput
                                            id="batas-tanjakan"
                                            type="number"
                                            inputMode="numeric"
                                            min={1}
                                            max={9000}
                                            value={form.data.max_elevation_gain_preference_m}
                                            onChange={(e) => ubah({ max_elevation_gain_preference_m: e.target.value })}
                                            aria-invalid={galat.max_elevation_gain_preference_m ? true : undefined}
                                            aria-describedby={galat.max_elevation_gain_preference_m ? 'batas-tanjakan-hint batas-tanjakan-galat' : 'batas-tanjakan-hint'}
                                        />
                                        <p id="batas-tanjakan-hint" className="mt-1 text-kecil text-secondary">
                                            Opsional. Dipakai menilai kecocokan bila tanjakan terbesar Anda belum diisi.
                                        </p>
                                        <InputError id="batas-tanjakan-galat" messages={galat.max_elevation_gain_preference_m} className="mt-2" />
                                    </div>
                                </div>
                            ) : null}
                        </section>

                        <p className="mt-8 text-kecil text-secondary">
                            Yang Anda isi adalah masukan, bukan sertifikasi. Riwayat pendakian tidak menaikkan tingkat Anda secara otomatis. Ubah kapan saja di Profil.
                        </p>

                        <BarAksi label="Langkah profil pendaki">
                            {langkah > 1 ? (
                                <Button variant="secondary" disabled={form.processing} onClick={() => setLangkah((l) => Math.max(1, l - 1))}>
                                    Kembali
                                </Button>
                            ) : null}
                            <Button type="submit" processing={form.processing} className="flex-1">
                                {langkah < JUMLAH_LANGKAH ? 'Lanjut' : 'Simpan profil'}
                            </Button>
                        </BarAksi>
                    </form>
                )}
            </div>
        </div>
    );
}

ProfileSetup.layout = (page: ReactNode) => <AppLayout bilahTab={false}>{page}</AppLayout>;
```

`tests/Pratinjau/HalamanPratinjauTest.php`:

1. Ganti:

```php
use App\Services\ReadinessService;
use App\Services\Trails\TautanBagikan;
use Database\Seeders\DemoSeeder;
```

dengan:

```php
use App\Services\ReadinessService;
use App\Services\Trails\HalamanJelajah;
use App\Services\Trails\TautanBagikan;
use App\Services\Trails\TrailSearch;
use Database\Seeders\DemoSeeder;
```

2. Ganti:

```php
    /**
     * Halaman tugas: bilah tab disembunyikan dan tombol kembali menuju Jelajah (spec v3
```

dengan:

```php
    /**
     * Onboarding pertama kali (acuan 00): langkah 1 dengan "Nanti saja", tanpa bilah tab.
     */
    public function test_onboarding(): void
    {
        $this->seed(DemoSeeder::class);
        $this->actingAs(User::factory()->create(['name' => 'Budi Setiawan']));

        $this->tulisPratinjau('onboarding', $this->get(self::ASAL.'/onboarding'), 'Onboarding/ProfileSetup');
    }

    /**
     * Onboarding sebagai penyunting dari Profil: langkah 2 dengan pengalaman Budi yang diisi
     * sendiri, dan langkah 3 dengan wilayah minatnya.
     */
    public function test_onboarding_pengalaman(): void
    {
        $this->sebagaiBudi();

        $this->tulisPratinjau('onboarding-pengalaman', $this->get(self::ASAL.'/onboarding?langkah=2'), 'Onboarding/ProfileSetup');
    }

    public function test_onboarding_rencana(): void
    {
        $budi = $this->sebagaiBudi();
        $budi->preference->update(['interest_regions' => ['JAWA_BARAT_BANTEN', 'JAWA_TENGAH_DIY']]);

        $this->tulisPratinjau('onboarding-rencana', $this->get(self::ASAL.'/onboarding?langkah=3'), 'Onboarding/ProfileSetup');
    }

    /**
     * Akhir onboarding: akibat langsung dengan angka dari saringan Jelajah yang sama.
     */
    public function test_onboarding_selesai(): void
    {
        $budi = $this->sebagaiBudi();
        $cocok = app(HalamanJelajah::class)->props(TrailSearch::dariInput(['kecocokan' => 'COCOK']), $budi, request())['ringkasan']['jumlah'];

        $this->tulisPratinjau('onboarding-selesai', $this->withSession(['onboarding-selesai' => ['cocok' => $cocok]])->get(self::ASAL.'/onboarding'), 'Onboarding/ProfileSetup');
    }

    /**
     * Halaman tugas: bilah tab disembunyikan dan tombol kembali menuju Jelajah (spec v3
```

- [ ] **Step 4: Build dan tipe.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `npx tsc --noEmit`
Expected: `✓ built`; tsc tanpa keluaran galat.

- [ ] **Step 5: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Enums/InterestRegion.php app/Enums/ExperienceLevel.php app/Enums/NavigationExperience.php app/Services/Profil/TurunanPengalaman.php app/Http/Controllers/Onboarding/ProfileSetupController.php app/Http/Requests/StoreProfileSetupRequest.php app/Models/UserPreference.php database/migrations/2026_10_08_000600_add_interest_regions_to_user_preferences_table.php tests/Feature/OnboardingDerivationTest.php tests/Feature/InterestRegionTest.php tests/Feature/EmptyStateCoverageTest.php tests/Feature/NavigationConsistencyTest.php tests/Feature/RecommendationFlowTest.php tests/Pratinjau/HalamanPratinjauTest.php tests/Spatial/InterestRegionsSchemaTest.php`, lalu `php artisan test --compact --filter='OnboardingDerivation|InterestRegion|RecommendationFlow|NavigationConsistency|EmptyStateCoverage|PageHeaderContract|KeyboardAndScreenReader|TapTarget'`
Expected: PASS. Pada simulasi rencana: 63 test, 62 lulus, 1 dilewati.

- [ ] **Step 6: Suite, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 2.114 test, 2.112 lulus, 2 dilewati (sebelum tugas: 2.098).

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `php artisan test --compact --testsuite=Pratinjau`
Expected: PASS; halaman onboarding baru tiga langkah dan hasil. Pada simulasi rencana: 34 test lulus.

- [ ] **Step 7: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 8: Foto profil

Spec v3 bagian 6.15, induk W7 butir 5, PRD §81 dan §82, `docs/KEBIJAKAN-DATA-PRIBADI.md` bagian 2 dan 5; ruling W7-R12
(Konflik 11). Pendaki memasang, mengganti, dan menghapus foto lewat `POST` dan `DELETE profile/foto` berbatas laju
`foto-profil`. Foto JPG, PNG, atau WebP paling besar 2 MB dan 64 sampai 8.000 piksel ditulis ulang tanpa EXIF dan
dikecilkan ke 512 px (`ImageSanitizer`) sebelum disimpan dengan nama acak di disk yang dapat diganti
(`filesystems.avatars_disk`, `AVATARS_DISK`, bawaan disk privat lokal). `GET profile/foto` menyajikannya hanya kepada
pemilik sesi dengan `Cache-Control: private`; alamatnya dibagikan lewat prop bersama `auth.user.foto` dengan penanda
versi, dan avatar, menu akun, serta tautan profil memakainya. Berkas lama dihapus saat diganti, dihapus, atau akun
dihapus. Jalur yang dihapus atau disajikan berasal dari basis data, jadi hanya nama acak langsung di map `avatars`
yang diterima (`FotoProfil::sah()`); nilai lama kolom `profiles.avatar_path` yang tidak ditulis layanan ini tidak
disajikan dan tidak dihapus.

**Files:**
- Create: `app/Http/Controllers/Profile/ProfilePhotoController.php`, `app/Http/Requests/Profile/UpdateProfilePhotoRequest.php`, `app/Services/Profil/FotoProfil.php`
- Modify: `.env.example`, `app/Http/Controllers/Profile/ProfileController.php` (hapus akun menghapus berkas foto), `app/Http/Middleware/HandleInertiaRequests.php`, `config/filesystems.php`, `config/hiking.php`, `resources/js/components/ui/TautanProfil.tsx`, `resources/js/layouts/MenuAkun.tsx`, `resources/js/lib/bukanHalamanReact.json`, `resources/js/types/index.d.ts`, `routes/web.php`
- Test: `tests/Feature/ProfilePhotoTest.php` (baru), `tests/Feature/PageHeaderContractTest.php` (satu asersi avatar mengikuti prop `foto`)

**Interfaces:**
- Consumes: `App\Support\ImageSanitizer::sanitize(string $absolutePath, int $maxDimension): void`; `Avatar` dengan prop `foto`; `profiles.avatar_path` (kolom lama tanpa pemakai); `DestroyAccountRequest` dan alur hapus akun.
- Produces:
  - `FotoProfil::MAP` (`'avatars'`), `simpan(User, UploadedFile): void`, `hapus(User): void`, `hapusBerkas(?string): void`, `FotoProfil::sah(?string): bool`, `FotoProfil::url(User): ?string`, `FotoProfil::disk(): string` (tiga terakhir statis);
  - config `filesystems.avatars_disk` (`AVATARS_DISK`, bawaan `local`), `hiking.uploads.avatar_max_kb` (2048) dan `hiking.uploads.avatar_dimension` (512);
  - rute `profile.photo.show` (GET), `profile.photo.store` (POST), `profile.photo.destroy` (DELETE) di `profile/foto`, keduanya yang menulis berbatas laju `foto-profil`, kembali ke `profile` dengan flash `kabar-profil` `{bagian: 'foto', teks: string}`; `/profile/foto` terdaftar di `bukanHalamanReact.json`;
  - prop bersama `auth.user.foto: string | null` (`resources/js/types/index.d.ts`).

- [ ] **Step 1: Tulis test yang gagal.** Buat berkas test baru dengan `make:test`, lalu ganti isinya persis.
`PageHeaderContractTest` mengikuti avatar yang kini membaca prop `foto`.

Buat `tests/Feature/ProfilePhotoTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Profil\FotoProfil;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Foto profil (spec v3 bagian 6.15; rencana induk W7 butir 5): EXIF dibuang dan ukurannya
 * dibatasi sebelum disimpan, nama berkas acak di disk privat, hanya pemiliknya yang dapat
 * membukanya, berkas lama dibuang ketika diganti atau dihapus, dan unggahan yang bukan gambar
 * ditolak tanpa meninggalkan berkas (PRD §81, §82).
 */
class ProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(FotoProfil::disk());
    }

    /**
     * JPEG 1200 x 900 dengan segmen EXIF berisi tag Make (pola ImageSanitizerTest).
     */
    private function fotoDenganExif(string $nama = 'wajah-budi-setiawan.jpg'): UploadedFile
    {
        $gambar = imagecreatetruecolor(1200, 900);
        imagefill($gambar, 0, 0, imagecolorallocate($gambar, 40, 90, 60));
        ob_start();
        imagejpeg($gambar);
        $jpeg = (string) ob_get_clean();
        imagedestroy($gambar);

        $make = "Pendaki\0";
        $ifd = pack('v', 1).pack('vvVV', 0x010F, 2, strlen($make), 8 + 2 + 12 + 4).pack('V', 0).$make;
        $muatan = "Exif\0\0"."II\x2a\x00".pack('V', 8).$ifd;
        $exif = "\xFF\xE1".pack('n', strlen($muatan) + 2).$muatan;

        return UploadedFile::fake()->createWithContent($nama, substr($jpeg, 0, 2).$exif.substr($jpeg, 2));
    }

    public function test_an_uploaded_photo_is_stored_without_metadata_and_scaled_down(): void
    {
        $user = User::factory()->create(['name' => 'Budi Setiawan']);
        $unggahan = $this->fotoDenganExif();
        $this->assertNotFalse(@exif_read_data($unggahan->getRealPath()), 'Berkas uji harus membawa EXIF sebelum diunggah.');

        $this->actingAs($user)->post(route('profile.photo.store'), ['foto' => $unggahan])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile'))
            ->assertSessionHas('kabar-profil', ['bagian' => 'foto', 'teks' => 'Foto profil diganti.']);

        $path = $user->fresh()->profile->avatar_path;
        $this->assertStringStartsWith(FotoProfil::MAP.'/', $path);
        $this->assertStringNotContainsStringIgnoringCase('setiawan', $path, 'Nama berkas acak, bukan dari nama pengguna atau berkas asal.');

        $berkas = Storage::disk(FotoProfil::disk())->path($path);
        [$lebar, $tinggi] = getimagesize($berkas);
        $this->assertSame([512, 384], [$lebar, $tinggi]);
        $exif = @exif_read_data($berkas);
        $this->assertTrue($exif === false || ! isset($exif['Make']), 'Metadata tidak boleh tersisa.');
    }

    /**
     * Berkas disajikan hanya kepada pemilik sesi, dengan cache privat; alamatnya dibagikan ke
     * setiap halaman lewat auth.user.foto beserta penanda versi.
     */
    public function test_only_the_owner_is_served_the_photo(): void
    {
        $user = User::factory()->create();
        $orangLain = User::factory()->create();
        $this->actingAs($user)->post(route('profile.photo.store'), ['foto' => $this->fotoDenganExif()])->assertSessionHasNoErrors();
        $user = $user->fresh();

        $respons = $this->actingAs($user)->get(route('profile.photo.show'));
        $respons->assertOk();
        $this->assertStringContainsString('private', (string) $respons->headers->get('Cache-Control'));

        $this->actingAs($orangLain)->get(route('profile.photo.show'))->assertNotFound();
        auth()->logout();
        $this->get(route('profile.photo.show'))->assertRedirect(route('login'));

        $this->actingAs($user)->get(route('profile'))->assertInertia(fn (AssertableInertia $page) => $page->where('auth.user.foto', FotoProfil::url($user)));
        $this->assertStringContainsString('?v=', (string) FotoProfil::url($user));
        $this->actingAs($orangLain)->get(route('profile'))->assertInertia(fn (AssertableInertia $page) => $page->where('auth.user.foto', null));
    }

    public function test_replacing_or_removing_the_photo_deletes_the_old_file(): void
    {
        $user = User::factory()->create();
        $disk = Storage::disk(FotoProfil::disk());

        $this->actingAs($user)->post(route('profile.photo.store'), ['foto' => $this->fotoDenganExif()]);
        $pertama = $user->fresh()->profile->avatar_path;
        $this->actingAs($user->fresh())->post(route('profile.photo.store'), ['foto' => $this->fotoDenganExif('lain.jpg')]);
        $kedua = $user->fresh()->profile->avatar_path;

        $this->assertNotSame($pertama, $kedua);
        $this->assertFalse($disk->exists($pertama));
        $this->assertTrue($disk->exists($kedua));

        $this->actingAs($user->fresh())->delete(route('profile.photo.destroy'))
            ->assertRedirect(route('profile'))
            ->assertSessionHas('kabar-profil', ['bagian' => 'foto', 'teks' => 'Foto profil dihapus. Inisial nama Anda dipakai lagi.']);
        $this->assertNull($user->fresh()->profile->avatar_path);
        $this->assertFalse($disk->exists($kedua));
    }

    /**
     * Skrip yang dinamai .jpg, SVG, dan berkas yang terlalu besar ditolak sebelum disentuh, dan
     * tidak meninggalkan berkas apa pun.
     */
    public function test_malicious_or_oversized_uploads_are_refused_without_a_trace(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('profile.photo.store'), [])
            ->assertSessionHasErrors(['foto' => 'Pilih foto lebih dulu.']);
        // Skrip berekstensi .jpg. Unggahan nyata ditolak aturan image karena tipe MIME-nya dibaca
        // dari isi berkas; berkas palsu Laravel menebak tipe dari ekstensi, dan tetap ditolak
        // aturan dimensions karena isinya tidak terbaca sebagai gambar. Yang dijaga: ditolak
        // sebelum disimpan, apa pun lapisan yang menolaknya.
        $this->actingAs($user)->post(route('profile.photo.store'), ['foto' => UploadedFile::fake()->createWithContent('foto.jpg', "<?php system(\$_GET['c']); ?>\n")])
            ->assertSessionHasErrors('foto');
        $this->actingAs($user)->post(route('profile.photo.store'), ['foto' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf')])
            ->assertSessionHasErrors(['foto' => 'Berkas itu bukan foto. Pilih foto JPG, PNG, atau WebP.']);
        $this->actingAs($user)->post(route('profile.photo.store'), ['foto' => UploadedFile::fake()->createWithContent('foto.svg', '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"/>')])
            ->assertSessionHasErrors('foto');
        $this->actingAs($user)->post(route('profile.photo.store'), ['foto' => UploadedFile::fake()->image('besar.jpg', 1200, 900)->size(3000)])
            ->assertSessionHasErrors(['foto' => 'Ukuran foto paling besar 2 MB.']);
        $this->actingAs($user)->post(route('profile.photo.store'), ['foto' => UploadedFile::fake()->image('kecil.png', 20, 20)])
            ->assertSessionHasErrors(['foto' => 'Foto paling kecil 64 x 64 piksel dan paling besar 8000 piksel di sisi terpanjang.']);

        $this->assertSame([], Storage::disk(FotoProfil::disk())->allFiles());
        $this->assertNull($user->fresh()->profile);
    }

    /**
     * Kolom avatar_path sudah ada sebelum W7. Nilai yang tidak ditulis FotoProfil tidak pernah
     * disajikan, dibagikan sebagai alamat foto, atau dihapus, walau berkasnya ada di disk yang sama.
     */
    public function test_a_path_not_written_by_the_photo_service_is_never_served_or_deleted(): void
    {
        $user = User::factory()->create();
        $disk = Storage::disk(FotoProfil::disk());
        $disk->put('lain/catatan.txt', 'bukan foto');
        $user->profile()->create(['avatar_path' => 'lain/catatan.txt']);

        $this->actingAs($user)->get(route('profile.photo.show'))->assertNotFound();
        $this->assertNull(FotoProfil::url($user->fresh()));

        $this->actingAs($user->fresh())->post(route('profile.photo.store'), ['foto' => $this->fotoDenganExif()])->assertSessionHasNoErrors();

        $this->assertTrue($disk->exists('lain/catatan.txt'), 'Berkas di luar map foto tidak ikut dihapus.');
        $this->assertStringStartsWith(FotoProfil::MAP.'/', $user->fresh()->profile->avatar_path);
    }

    public function test_deleting_the_account_deletes_the_photo_file(): void
    {
        $user = User::factory()->create(['password' => 'kata-sandi-lama']);
        $this->actingAs($user)->post(route('profile.photo.store'), ['foto' => $this->fotoDenganExif()]);
        $path = $user->fresh()->profile->avatar_path;

        $this->actingAs($user->fresh())->delete(route('profile.destroy'), ['password' => 'kata-sandi-lama'])->assertRedirect('/');

        $this->assertNull(User::find($user->id));
        $this->assertFalse(Storage::disk(FotoProfil::disk())->exists($path));
    }
}
```

`tests/Feature/PageHeaderContractTest.php`:

1. Ganti:

```php
        $this->assertStringContainsString('h-11 w-11', $tautan);
        $this->assertStringContainsString('<Avatar nama={auth.user.name} ukuran={44} />', $tautan);
        $this->assertStringContainsString('<span className="sr-only">Profil {auth.user.name}</span>', $tautan);
```

dengan:

```php
        $this->assertStringContainsString('h-11 w-11', $tautan);
        $this->assertStringContainsString('<Avatar nama={auth.user.name} foto={auth.user.foto} ukuran={44} />', $tautan);
        $this->assertStringContainsString('<span className="sr-only">Profil {auth.user.name}</span>', $tautan);
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='ProfilePhoto|PageHeaderContract|InertiaLinkRegistry'`
Expected: FAIL. Pada simulasi rencana: 12 test, 5 lulus, 1 gagal, 6 error. Yang merah: keenam test `ProfilePhotoTest` (`FotoProfil`, rute `profile.photo.*`, dan disk foto belum ada) dan `PageHeaderContractTest::test_a_tab_root_reaches_the_profile_from_its_avatar` (avatar belum membaca prop `foto`). `InertiaLinkRegistryTest` sudah lulus; ia menuntut `/profile/foto` terdaftar sesudah rutenya ada.

- [ ] **Step 3: Tulis implementasinya.** Buat berkas dengan
`DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:controller --no-interaction Profile/ProfilePhotoController`,
`... make:request --no-interaction Profile/UpdateProfilePhotoRequest`, dan
`... make:class --no-interaction Services/Profil/FotoProfil`, lalu isinya diganti persis. Periksa dulu `php -m` memuat
`gd`, `exif`, dan `fileinfo`; bila tidak, berhenti dan laporkan NEEDS_CONTEXT.

Buat `app/Http/Controllers/Profile/ProfilePhotoController.php`:

```php
<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfilePhotoRequest;
use App\Services\Profil\FotoProfil;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Foto profil milik pengguna yang masuk (W7). Tidak ada id pengguna di rute: setiap aksi,
 * termasuk menyajikan berkasnya, bekerja pada pemilik sesi, jadi foto orang lain tidak dapat
 * dibuka dengan menebak alamat.
 */
class ProfilePhotoController extends Controller
{
    public function show(Request $request): StreamedResponse
    {
        $path = $request->user()->profile?->avatar_path;
        $disk = Storage::disk(FotoProfil::disk());

        abort_if(! FotoProfil::sah($path) || ! $disk->exists($path), 404);

        // Foto pribadi tidak boleh disimpan proxy bersama.
        return $disk->response($path, null, ['Cache-Control' => 'private, max-age=3600']);
    }

    public function store(UpdateProfilePhotoRequest $request, FotoProfil $foto): RedirectResponse
    {
        $foto->simpan($request->user(), $request->file('foto'));

        return redirect()->route('profile')->with('kabar-profil', ['bagian' => 'foto', 'teks' => 'Foto profil diganti.']);
    }

    public function destroy(Request $request, FotoProfil $foto): RedirectResponse
    {
        $foto->hapus($request->user());

        return redirect()->route('profile')->with('kabar-profil', ['bagian' => 'foto', 'teks' => 'Foto profil dihapus. Inisial nama Anda dipakai lagi.']);
    }
}
```

Buat `app/Http/Requests/Profile/UpdateProfilePhotoRequest.php`:

```php
<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Foto profil (W7). MIME dari isi berkas, ekstensi, ukuran, dan dimensi dibatasi bersama
 * (PRD §81): berkas yang bukan gambar, termasuk skrip yang dinamai .jpg, ditolak di sini
 * sebelum disentuh. SVG tidak diterima karena dapat membawa skrip.
 */
class UpdateProfilePhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'foto' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:'.(int) config('hiking.uploads.avatar_max_kb'),
                'dimensions:min_width=64,min_height=64,max_width=8000,max_height=8000',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'foto.required' => 'Pilih foto lebih dulu.',
            'foto.file' => 'Foto tidak terunggah dengan benar. Coba lagi.',
            'foto.image' => 'Berkas itu bukan foto. Pilih foto JPG, PNG, atau WebP.',
            'foto.mimes' => 'Pilih foto JPG, PNG, atau WebP.',
            'foto.max' => 'Ukuran foto paling besar '.((int) config('hiking.uploads.avatar_max_kb') / 1024).' MB.',
            'foto.dimensions' => 'Foto paling kecil 64 x 64 piksel dan paling besar 8000 piksel di sisi terpanjang.',
        ];
    }
}
```

Buat `app/Services/Profil/FotoProfil.php`:

```php
<?php

namespace App\Services\Profil;

use App\Models\User;
use App\Support\ImageSanitizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Foto profil (spec v3 bagian 6.15; rencana induk W7 butir 5). Berkas unggahan ditulis ulang
 * lebih dulu, sebelum disimpan: EXIF (termasuk koordinat GPS) hilang dan sisi terpanjangnya
 * dibatasi (ImageSanitizer, PRD §81 dan §82). Nama berkas acak di disk privat yang dapat diganti
 * (filesystems.avatars_disk), dan berkasnya hanya disajikan kepada pemiliknya lewat rute
 * profile.photo.show. Mengganti atau menghapus foto menghapus berkas lamanya.
 */
final class FotoProfil
{
    public const MAP = 'avatars';

    public function simpan(User $user, UploadedFile $berkas): void
    {
        try {
            ImageSanitizer::sanitize((string) $berkas->getRealPath(), (int) config('hiking.uploads.avatar_dimension'));
        } catch (RuntimeException) {
            throw ValidationException::withMessages(['foto' => 'Foto tidak dapat diproses. Pilih foto JPG, PNG, atau WebP yang lain.']);
        }

        $lama = $user->profile?->avatar_path;
        $baru = $berkas->store(self::MAP, self::disk());

        if ($baru === false) {
            throw ValidationException::withMessages(['foto' => 'Foto gagal disimpan. Coba lagi.']);
        }

        $user->profile()->updateOrCreate([], ['avatar_path' => $baru]);
        $this->hapusBerkas($lama);
    }

    public function hapus(User $user): void
    {
        $lama = $user->profile?->avatar_path;

        if ($lama === null) {
            return;
        }

        $user->profile()->update(['avatar_path' => null]);
        $this->hapusBerkas($lama);
    }

    public function hapusBerkas(?string $path): void
    {
        if (self::sah($path)) {
            Storage::disk(self::disk())->delete($path);
        }
    }

    /**
     * Hanya berkas yang ditulis layanan ini: satu nama acak langsung di dalam map avatars. Kolom
     * avatar_path sudah ada sebelum W7, jadi nilai lain (map lain, bertingkat, atau berisi "..")
     * tidak pernah disajikan atau dihapus, dan berkas lain di disk yang sama tidak tersentuh.
     */
    public static function sah(?string $path): bool
    {
        return $path !== null && preg_match('#^'.self::MAP.'/[A-Za-z0-9]+(\.[A-Za-z0-9]+)?$#', $path) === 1;
    }

    /**
     * Alamat foto pemiliknya, dengan penanda versi dari nama berkas supaya foto baru tidak
     * tertahan cache peramban; null bila belum ada foto (avatar memakai inisial).
     */
    public static function url(User $user): ?string
    {
        $path = $user->profile?->avatar_path;

        return self::sah($path) ? route('profile.photo.show', ['v' => substr(sha1($path), 0, 12)]) : null;
    }

    public static function disk(): string
    {
        return (string) config('filesystems.avatars_disk');
    }
}
```

`.env.example`:

1. Ganti:

```
REPORT_PHOTOS_DISK=local
SUPABASE_STORAGE_ENDPOINT=
```

dengan:

```
REPORT_PHOTOS_DISK=local
AVATARS_DISK=local
SUPABASE_STORAGE_ENDPOINT=
```

`app/Http/Controllers/Profile/ProfileController.php`:

1. Ganti:

```php
use App\Http\Requests\Profile\UpdateTrackPreferenceRequest;
use App\Support\PetunjukKataSandi;
```

dengan:

```php
use App\Http\Requests\Profile\UpdateTrackPreferenceRequest;
use App\Services\Profil\FotoProfil;
use App\Support\PetunjukKataSandi;
```

2. Ganti:

```php
     */
    public function destroy(DestroyAccountRequest $request, Logout $logout): RedirectResponse
    {
        tap($request->user(), $logout(...))->delete();

        return redirect('/');
```

dengan:

```php
     */
    public function destroy(DestroyAccountRequest $request, Logout $logout, FotoProfil $foto): RedirectResponse
    {
        $berkasFoto = $request->user()->profile?->avatar_path;

        tap($request->user(), $logout(...))->delete();

        // Berkas foto profil tidak ikut cascade basis data; dihapus sesudah barisnya hilang.
        $foto->hapusBerkas($berkasFoto);

        return redirect('/');
```

`app/Http/Middleware/HandleInertiaRequests.php`:

1. Ganti:

```php
use App\Services\DataFreshnessService;
use App\Services\Trips\DaftarPengingat;
```

dengan:

```php
use App\Services\DataFreshnessService;
use App\Services\Profil\FotoProfil;
use App\Services\Trips\DaftarPengingat;
```

2. Ganti:

```php
                    'hasCompletedProfile' => $user->hasCompletedProfile(),
                ],
```

dengan:

```php
                    'hasCompletedProfile' => $user->hasCompletedProfile(),
                    // Foto profil W7 atau null; avatar memakai inisial bila null.
                    'foto' => FotoProfil::url($user),
                ],
```

`config/filesystems.php`:

1. Ganti:

```php
    |--------------------------------------------------------------------------
    | Symbolic Links
```

dengan:

```php
    |--------------------------------------------------------------------------
    | Profile Photo Disk
    |--------------------------------------------------------------------------
    |
    | Disk untuk foto profil (W7). Bawaannya disk privat lokal; ganti ke "supabase"
    | bersama foto laporan. Berkasnya tidak pernah publik: selalu disajikan lewat
    | rute pemiliknya sendiri (profile.photo.show).
    |
    */

    'avatars_disk' => env('AVATARS_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
```

`config/hiking.php`:

1. Ganti:

```php
        'report_photo_max_dimension' => 2000,

        'gpx_max_kb' => 8192,
```

dengan:

```php
        'report_photo_max_dimension' => 2000,

        // Foto profil (W7): batas unggah, dan sisi terpanjang sesudah ditulis ulang tanpa EXIF.
        'avatar_max_kb' => 2048,
        'avatar_dimension' => 512,

        'gpx_max_kb' => 8192,
```

`resources/js/components/ui/TautanProfil.tsx`:

1. Ganti:

```tsx
        <Tautan href={profile.url()} className={kelas('inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full', fokusTerlihat, umpanTekan, className)}>
            <Avatar nama={auth.user.name} ukuran={44} />
            <span className="sr-only">Profil {auth.user.name}</span>
```

dengan:

```tsx
        <Tautan href={profile.url()} className={kelas('inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full', fokusTerlihat, umpanTekan, className)}>
            <Avatar nama={auth.user.name} foto={auth.user.foto} ukuran={44} />
            <span className="sr-only">Profil {auth.user.name}</span>
```

`resources/js/layouts/MenuAkun.tsx`:

1. Ganti:

```tsx
            <button type="button" popoverTarget="menu-akun" className={kelas('inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full', fokusTerlihat, umpanTekan)}>
                <Avatar nama={pengguna.name} ukuran={44} />
                <span className="sr-only">Menu akun {pengguna.name}</span>
```

dengan:

```tsx
            <button type="button" popoverTarget="menu-akun" className={kelas('inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full', fokusTerlihat, umpanTekan)}>
                <Avatar nama={pengguna.name} foto={pengguna.foto} ukuran={44} />
                <span className="sr-only">Menu akun {pengguna.name}</span>
```

`resources/js/lib/bukanHalamanReact.json`:

1. Ganti:

```json
        "^/reports/[^/]+/photo$",
        "^/storage/",
```

dengan:

```json
        "^/reports/[^/]+/photo$",
        "^/profile/foto$",
        "^/storage/",
```

`resources/js/types/index.d.ts`:

1. Ganti:

```ts
    hasCompletedProfile: boolean;
}
```

dengan:

```ts
    hasCompletedProfile: boolean;
    /** Alamat foto profil milik pengguna ini (W7), atau null bila memakai inisial. */
    foto: string | null;
}
```

`routes/web.php`:

1. Ganti:

```php
use App\Http\Controllers\Profile\ProfileController;
use App\Http\Controllers\ProgressiveWebAppController;
```

dengan:

```php
use App\Http\Controllers\Profile\ProfileController;
use App\Http\Controllers\Profile\ProfilePhotoController;
use App\Http\Controllers\ProgressiveWebAppController;
```

2. Ganti:

```php
    Route::delete('profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    // Perlengkapan pribadi (W7): pemilik dari sesi, barang dari rute dan Policy.
```

dengan:

```php
    Route::delete('profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    // Foto profil (W7): tanpa id pengguna di rute; berkasnya hanya untuk pemilik sesi.
    Route::get('profile/foto', [ProfilePhotoController::class, 'show'])->name('profile.photo.show');
    Route::post('profile/foto', [ProfilePhotoController::class, 'store'])->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,foto-profil')->name('profile.photo.store');
    Route::delete('profile/foto', [ProfilePhotoController::class, 'destroy'])->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,foto-profil')->name('profile.photo.destroy');
    // Perlengkapan pribadi (W7): pemilik dari sesi, barang dari rute dan Policy.
```

- [ ] **Step 4: Build dan tipe.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `npx tsc --noEmit`
Expected: `✓ built`; keluaran Wayfinder memuat `profile.photo.show`, `store`, dan `destroy`; tsc tanpa keluaran galat.

- [ ] **Step 5: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Http/Controllers/Profile/ProfilePhotoController.php app/Http/Requests/Profile/UpdateProfilePhotoRequest.php app/Services/Profil/FotoProfil.php app/Http/Controllers/Profile/ProfileController.php app/Http/Middleware/HandleInertiaRequests.php config/filesystems.php config/hiking.php routes/web.php tests/Feature/ProfilePhotoTest.php tests/Feature/PageHeaderContractTest.php`, lalu `php artisan test --compact --filter='ProfilePhoto|PageHeaderContract|InertiaLinkRegistry'`
Expected: PASS. Pada simulasi rencana: 12 test, 12 lulus.

- [ ] **Step 6: Suite, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 2.120 test, 2.118 lulus, 2 dilewati (sebelum tugas: 2.114).

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `php artisan test --compact --testsuite=Pratinjau`
Expected: PASS. Pada simulasi rencana: 34 test lulus.

- [ ] **Step 7: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 9: Hapus semua jejak dan penjaga tulisan data pribadi

Spec v3 bagian 6.15 (privasi), PRD §84, BR-13, `docs/KEBIJAKAN-DATA-PRIBADI.md` bagian 2 dan 4, `CLAUDE.md` bagian 4;
ruling W7-R13 dan W7-R21. `DELETE profile/jejak` (berbatas laju `hapus-jejak`) menghapus seluruh titik jejak dari sesi
pendakian pengguna yang masuk; pendakian, riwayat, laporan, dan catatan tetap ada, dan kabarnya menyebut jumlah titik
yang dihapus. `PersonalDataWriteGuardTest` (rintisan) ditulis ulang menjadi sapuan seluruh rute tulis data pribadi W7:
tamu diarahkan masuk, setiap rute punya kunci batas laju sendiri, isian yang menyelundupkan pemilik, asal, trip, atau
keadaan selesai diabaikan, dan data milik pendaki lain tidak dapat diubah. `PersonalDataDeletionTest` membuktikan
"Hapus semua jejak saya" hanya menyentuh titik jejak pemiliknya, dan penghapusan akun menghapus seluruh data pribadi
W7 (target, perlengkapan, catatan manual, catatan butir lewat trip, wilayah minat, foto, dan jejak).

**Files:**
- Create: `app/Http/Controllers/Profile/TrackDataController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/PersonalDataWriteGuardTest.php` (rintisan ditulis ulang utuh), `tests/Feature/PersonalDataDeletionTest.php` (baru)

**Interfaces:**
- Consumes: rute tulis Tugas 1, 3, 5, 6, dan 8 beserta kunci batas lajunya (`catatan-pendakian`, `target-pribadi`, `perlengkapan`, `catatan-butir`, `foto-profil`); `HikingSession` dan `HikeTrackPoint`; alur hapus akun `profile.destroy`.
- Produces: `TrackDataController::__invoke(Request): RedirectResponse`; rute `profile.tracks.destroy` (DELETE `profile/jejak`, berbatas laju `hapus-jejak`), kembali ke `profile` dengan flash `kabar-profil` `{bagian: 'privasi', teks: 'N titik jejak dihapus. Pendakian dan catatannya tetap ada.'}` atau `'Tidak ada jejak tersimpan untuk dihapus.'`.

- [ ] **Step 1: Tulis test yang gagal.** `PersonalDataWriteGuardTest.php` sudah ada sebagai rintisan; ganti isinya utuh.
Buat `PersonalDataDeletionTest` dengan `make:test`, lalu ganti isinya persis.

Buat `tests/Feature/PersonalDataDeletionTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\HikingSessionStatus;
use App\Models\HikeTrackPoint;
use App\Models\HikingHistory;
use App\Models\HikingSession;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\Profil\FotoProfil;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Data pribadi baru W7 pada penghapusan (docs/KEBIJAKAN-DATA-PRIBADI.md bagian 2 dan 4): target,
 * perlengkapan, catatan pendakian di luar trip, catatan butir, wilayah minat, foto profil, dan
 * jejak GPS ikut hilang bersama akun. "Hapus semua jejak saya" mencabut lokasi presisi saja,
 * tanpa menghapus pendakiannya, dan hanya milik pengguna itu.
 */
class PersonalDataDeletionTest extends TestCase
{
    use RefreshDatabase;

    private function jejak(User $user, int $titik): HikingSession
    {
        $trip = TripPlan::factory()->create(['user_id' => $user->id]);
        $sesi = HikingSession::create([
            'trip_plan_id' => $trip->id,
            'user_id' => $user->id,
            'status' => HikingSessionStatus::COMPLETED->value,
            'started_at' => now()->subHours(6),
            'ended_at' => now()->subHour(),
        ]);

        for ($i = 0; $i < $titik; $i++) {
            HikeTrackPoint::create([
                'hiking_session_id' => $sesi->id,
                'recorded_at' => now()->subHours(5)->addMinutes($i),
                'latitude' => -6.78 + $i / 10000,
                'longitude' => 106.98,
            ]);
        }

        return $sesi;
    }

    public function test_deleting_all_tracks_removes_only_my_gps_points_and_keeps_the_hikes(): void
    {
        $saya = User::factory()->create();
        $orangLain = User::factory()->create();
        $sesiSaya = $this->jejak($saya, 3);
        $this->jejak($orangLain, 2);
        HikingHistory::factory()->for($saya)->create(['trip_plan_id' => $sesiSaya->trip_plan_id]);

        $this->actingAs($saya)->delete(route('profile.tracks.destroy'))
            ->assertRedirect(route('profile'))
            ->assertSessionHas('kabar-profil', ['bagian' => 'privasi', 'teks' => '3 titik jejak dihapus. Pendakian dan catatannya tetap ada.']);

        $this->assertSame(0, HikeTrackPoint::where('hiking_session_id', $sesiSaya->id)->count());
        $this->assertSame(2, HikeTrackPoint::count(), 'Jejak orang lain tidak tersentuh.');
        $this->assertNotNull($sesiSaya->fresh());
        $this->assertSame(1, $saya->hikingHistory()->count());

        $this->actingAs($saya)->delete(route('profile.tracks.destroy'))
            ->assertSessionHas('kabar-profil', ['bagian' => 'privasi', 'teks' => 'Tidak ada jejak tersimpan untuk dihapus.']);
    }

    public function test_deleting_the_account_removes_every_w7_personal_record(): void
    {
        Storage::fake(FotoProfil::disk());
        $user = User::factory()->create(['password' => 'kata-sandi-lama']);
        $user->personalTargets()->create(['metric' => 'GAIN', 'monthly_value' => 1000]);
        $user->ownedItems()->create(['name' => 'Headlamp']);
        HikingHistory::factory()->manual()->for($user)->create(['personal_notes' => 'Catatan pribadi']);
        $user->preference()->create(['preferred_duration' => 'ONE_DAY', 'preferred_trip_type' => 'TEKTOK', 'interest_regions' => ['JAWA_TIMUR']]);
        $trip = TripPlan::factory()->create(['user_id' => $user->id]);
        $trip->preparationItems()->create(['category' => 'EQUIPMENT', 'label' => 'Tenda', 'is_critical' => false, 'status' => 'NOT_CONFIRMED', 'note' => 'Dibawa Dimas']);
        $this->jejak($user, 2);
        $this->actingAs($user)->post(route('profile.photo.store'), ['foto' => UploadedFile::fake()->image('wajah.jpg', 300, 300)])->assertSessionHasNoErrors();
        $foto = $user->fresh()->profile->avatar_path;

        $this->actingAs($user->fresh())->delete(route('profile.destroy'), ['password' => 'kata-sandi-lama'])->assertRedirect('/');

        foreach (['personal_targets', 'user_owned_items', 'hiking_history', 'user_preferences', 'trip_preparation_items', 'hike_track_points', 'profiles'] as $tabel) {
            $this->assertSame(0, DB::table($tabel)->count(), "{$tabel} masih menyimpan data akun yang dihapus.");
        }
        $this->assertFalse(Storage::disk(FotoProfil::disk())->exists($foto));
    }
}
```

Tulis ulang `tests/Feature/PersonalDataWriteGuardTest.php` utuh:

```php
<?php

namespace Tests\Feature;

use App\Enums\CompletionState;
use App\Enums\HikeRecordSource;
use App\Models\HikingHistory;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Penjaga setiap rute tulis data pribadi baru W7 (rencana induk W7; CLAUDE.md bagian 4).
 *
 * Menggantikan test rintisan yang hanya membuka `/` (spec v3 bagian 3.5). Sapuan ini menjaga
 * empat hal sekaligus untuk setiap rute: tamu diarahkan masuk, setiap rute dibatasi lajunya
 * dengan kunci sendiri, id pemilik dan kolom asal dari isian diabaikan (mass assignment), dan
 * data orang lain tidak dapat disentuh walau id-nya diketahui (IDOR).
 */
class PersonalDataWriteGuardTest extends TestCase
{
    use RefreshDatabase;

    /** Nama rute tulis W7 dan kunci batas lajunya. */
    private const RUTE = [
        'progress.records.store' => 'catatan-pendakian',
        'progress.records.update' => 'catatan-pendakian',
        'progress.records.destroy' => 'catatan-pendakian',
        'progress.targets.update' => 'target-pribadi',
        'profile.items.store' => 'perlengkapan',
        'profile.items.update' => 'perlengkapan',
        'profile.items.destroy' => 'perlengkapan',
        'profile.photo.store' => 'foto-profil',
        'profile.photo.destroy' => 'foto-profil',
        'profile.tracks.destroy' => 'hapus-jejak',
        'trips.preparation.note' => 'catatan-butir',
    ];

    public function test_every_w7_write_route_requires_a_session_and_is_rate_limited_on_its_own_key(): void
    {
        $batas = (int) config('hiking.rate_limits.personal_writes_per_minute');

        foreach (self::RUTE as $nama => $kunci) {
            $rute = Route::getRoutes()->getByName($nama);

            $this->assertNotNull($rute, "Rute {$nama} tidak terdaftar.");
            $this->assertContains('auth', $rute->gatherMiddleware(), "{$nama} terbuka untuk tamu.");
            $this->assertContains("throttle:{$batas},1,{$kunci}", $rute->gatherMiddleware(), "{$nama} tanpa batas laju sendiri.");
        }
    }

    public function test_a_guest_is_sent_to_sign_in_and_nothing_is_written(): void
    {
        $trip = TripPlan::factory()->create();

        $this->post(route('progress.records.store'), ['nama_jalur' => 'Lawu via Cemoro Sewu'])->assertRedirect(route('login'));
        $this->put(route('progress.targets.update'), ['tanjakan_m' => '1000', 'jarak_km' => '', 'pendakian' => ''])->assertRedirect(route('login'));
        $this->post(route('profile.items.store'), ['nama' => 'Headlamp'])->assertRedirect(route('login'));
        $this->delete(route('profile.photo.destroy'))->assertRedirect(route('login'));
        $this->delete(route('profile.tracks.destroy'))->assertRedirect(route('login'));
        $this->put(route('trips.preparation.note', [$trip, 1]), ['catatan' => 'Tamu'])->assertRedirect(route('login'));

        $this->assertDatabaseCount('personal_targets', 0);
        $this->assertDatabaseCount('user_owned_items', 0);
        $this->assertSame(0, HikingHistory::where('source', HikeRecordSource::MANUAL->value)->count());
    }

    /**
     * Batas laju bekerja sungguhan, bukan hanya tercantum: kiriman ke-31 dalam semenit ditolak,
     * dan kunci rute lain tidak ikut habis.
     */
    public function test_the_limit_refuses_the_request_after_the_budget(): void
    {
        $user = User::factory()->create();
        $batas = (int) config('hiking.rate_limits.personal_writes_per_minute');

        for ($i = 0; $i < $batas; $i++) {
            $this->actingAs($user)->delete(route('profile.tracks.destroy'))->assertRedirect(route('profile'));
        }

        $this->actingAs($user)->delete(route('profile.tracks.destroy'))->assertStatus(429);
        $this->actingAs($user)->post(route('profile.items.store'), ['nama' => 'Headlamp'])->assertRedirect(route('profile'));
    }

    /**
     * Pemilik, asal catatan, dan keadaan selesai ditentukan server, bukan isian.
     */
    public function test_owner_and_origin_fields_in_the_payload_are_ignored(): void
    {
        $saya = User::factory()->create();
        $orangLain = User::factory()->create();
        $tripOrangLain = TripPlan::factory()->create(['user_id' => $orangLain->id]);
        $jalur = Trail::factory()->published()->easy()->create();

        $this->actingAs($saya)->post(route('progress.records.store'), [
            'trail_id' => $jalur->id,
            'tanggal' => now('Asia/Jakarta')->subDay()->toDateString(),
            'jenis' => 'TEKTOK',
            'user_id' => $orangLain->id,
            'source' => HikeRecordSource::TRIP->value,
            'trip_plan_id' => $tripOrangLain->id,
            'completion_state' => CompletionState::ABANDONED->value,
        ])->assertSessionHasNoErrors();

        $catatan = HikingHistory::sole();
        $this->assertSame([$saya->id, HikeRecordSource::MANUAL, null, CompletionState::COMPLETED], [
            $catatan->user_id,
            $catatan->source,
            $catatan->trip_plan_id,
            $catatan->completion_state,
        ]);

        $this->actingAs($saya)->post(route('profile.items.store'), ['nama' => 'Matras', 'user_id' => $orangLain->id])->assertSessionHasNoErrors();
        $this->assertSame(0, $orangLain->ownedItems()->count());
    }

    /**
     * Data orang lain tidak tersentuh walau id-nya diketahui: catatan pendakian, barang, dan
     * catatan butir persiapan menjawab 403 atau 404, dan tidak ada yang berubah.
     */
    public function test_another_hikers_records_cannot_be_reached_by_id(): void
    {
        $saya = User::factory()->create();
        $orangLain = User::factory()->create();
        $catatan = HikingHistory::factory()->manual()->for($orangLain)->create(['personal_notes' => 'Milik orang lain']);
        $barang = $orangLain->ownedItems()->create(['name' => 'Kompor lipat']);
        $trip = TripPlan::factory()->create(['user_id' => $orangLain->id]);
        $butir = $trip->preparationItems()->create(['category' => 'EQUIPMENT', 'label' => 'Tenda', 'is_critical' => false, 'status' => 'NOT_CONFIRMED']);

        $this->actingAs($saya)->put(route('progress.records.update', $catatan), ['nama_jalur' => 'Diambil', 'tanggal' => '2026-01-01', 'jenis' => 'TEKTOK'])->assertForbidden();
        $this->actingAs($saya)->delete(route('progress.records.destroy', $catatan))->assertForbidden();
        $this->actingAs($saya)->put(route('profile.items.update', $barang), ['nama' => 'Diambil'])->assertForbidden();
        $this->actingAs($saya)->delete(route('profile.items.destroy', $barang))->assertForbidden();
        $this->actingAs($saya)->put(route('trips.preparation.note', [$trip, $butir->id]), ['catatan' => 'Diambil'])->assertForbidden();

        $this->assertSame('Milik orang lain', $catatan->fresh()->personal_notes);
        $this->assertSame('Kompor lipat', $barang->fresh()->name);
        $this->assertNull($butir->fresh()->note);
    }
}
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='PersonalDataWriteGuard|PersonalDataDeletion|AccountDeletionRetention'`
Expected: FAIL. Pada simulasi rencana: 12 test, 8 lulus, 1 gagal, 3 error. Yang merah: tiga test `PersonalDataWriteGuardTest` dan satu test `PersonalDataDeletionTest`, semuanya karena rute `profile.tracks.destroy` belum ada. Test penghapusan akun dan `AccountDeletionRetentionTest` sudah lulus, karena kunci asing cascade dan berkas foto sudah ditangani Tugas 1 sampai 8.

- [ ] **Step 3: Tulis implementasinya.** Controller dibuat dengan
`DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:controller --no-interaction Profile/TrackDataController --invokable`,
lalu isinya diganti persis.

Buat `app/Http/Controllers/Profile/TrackDataController.php`:

```php
<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\HikeTrackPoint;
use App\Models\HikingSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * "Hapus semua jejak saya" di Profil (spec v3 bagian 6.15; PRD §84, BR-13): seluruh titik GPS
 * dari sesi pendakian milik pengguna yang masuk dihapus sekaligus. Pendakian, riwayat, dan
 * catatannya tetap ada; yang dicabut hanya lokasi presisinya. Pemilik dari sesi, bukan dari
 * permintaan.
 */
class TrackDataController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $jumlah = HikeTrackPoint::query()
            ->whereIn('hiking_session_id', HikingSession::query()->where('user_id', $request->user()->id)->select('id'))
            ->delete();

        $teks = $jumlah > 0
            ? number_format($jumlah, 0, ',', '.').' titik jejak dihapus. Pendakian dan catatannya tetap ada.'
            : 'Tidak ada jejak tersimpan untuk dihapus.';

        return redirect()->route('profile')->with('kabar-profil', ['bagian' => 'privasi', 'teks' => $teks]);
    }
}
```

`routes/web.php`:

1. Ganti:

```php
use App\Http\Controllers\Profile\ProfilePhotoController;
use App\Http\Controllers\ProgressiveWebAppController;
```

dengan:

```php
use App\Http\Controllers\Profile\ProfilePhotoController;
use App\Http\Controllers\Profile\TrackDataController;
use App\Http\Controllers\ProgressiveWebAppController;
```

2. Ganti:

```php
    Route::delete('profile/foto', [ProfilePhotoController::class, 'destroy'])->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,foto-profil')->name('profile.photo.destroy');
    // Perlengkapan pribadi (W7): pemilik dari sesi, barang dari rute dan Policy.
```

dengan:

```php
    Route::delete('profile/foto', [ProfilePhotoController::class, 'destroy'])->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,foto-profil')->name('profile.photo.destroy');
    Route::delete('profile/jejak', TrackDataController::class)->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,hapus-jejak')->name('profile.tracks.destroy');
    // Perlengkapan pribadi (W7): pemilik dari sesi, barang dari rute dan Policy.
```

- [ ] **Step 4: Build dan tipe.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `npx tsc --noEmit`
Expected: `✓ built`; keluaran Wayfinder memuat `profile.tracks.destroy`; tsc tanpa keluaran galat.

- [ ] **Step 5: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Http/Controllers/Profile/TrackDataController.php routes/web.php tests/Feature/PersonalDataWriteGuardTest.php tests/Feature/PersonalDataDeletionTest.php`, lalu `php artisan test --compact --filter='PersonalDataWriteGuard|PersonalDataDeletion|AccountDeletionRetention'`
Expected: PASS. Pada simulasi rencana: 12 test, 12 lulus.

- [ ] **Step 6: Suite, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 2.126 test, 2.124 lulus, 2 dilewati (sebelum tugas: 2.120).

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `php artisan test --compact --testsuite=Pratinjau`
Expected: PASS. Pada simulasi rencana: 34 test lulus.

- [ ] **Step 7: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 10: Hub Profil

Spec v3 bagian 6.15 (prototype 14), DK-40, DK-42, induk W7 butir 5; ruling W7-R14 dan W7-R15. Satu kelas halaman,
`App\Services\Profil\HalamanProfil`, menyusun bagian pendakian Profil: identitas (foto unggahan atau inisial, "Pemula,
diisi sendiri", tanggal bergabung, pasang atau hapus foto), pengalaman dan preferensi yang diisi sendiri dengan "Ubah"
menuju langkah onboarding yang bersangkutan, wilayah minat, dan perlengkapan pribadi sebagai chip yang membuka lembar
ubah. Sesudahnya tampilan (tema, kurangi efek transparan, kurangi gerak lewat `tampilan.simpan`; tanpa pilihan bahasa),
perekaman jejak (`FormRekamJejak` tetap), lokasi dan jejak ("Hapus semua jejak saya" dengan dialog yang menyebut
akibatnya), informasi akun, kata sandi, pintu konsol sesuai peran dan Keluar (`PintuAkun` tetap), lalu hapus akun.
Setiap bagian yang menulis punya wilayah `role="status"` sendiri yang membaca flash `kabar-profil`. Ringkasan lama
`RingkasanProfilPendakian` dan `components/akun/types.ts` dihapus karena isinya pindah ke komponen Profil.

**Files:**
- Create: `app/Services/Profil/HalamanProfil.php`, `resources/js/components/profil/types.ts`, `resources/js/components/profil/IdentitasProfil.tsx`, `resources/js/components/profil/BagianRincian.tsx`, `resources/js/components/profil/PerlengkapanPribadi.tsx`, `resources/js/components/profil/TampilanProfil.tsx`, `resources/js/components/profil/DataLokasi.tsx`
- Modify: `app/Http/Controllers/Profile/ProfileController.php`, `resources/js/pages/Profile/Edit.tsx`
- Delete: `resources/js/components/akun/RingkasanProfilPendakian.tsx`, `resources/js/components/akun/types.ts`
- Test: `tests/Feature/ProfileTest.php`, `tests/Feature/InterestRegionTest.php`, `tests/Feature/NewPageQueryBudgetTest.php`, `tests/Pratinjau/HalamanPratinjauTest.php`

**Interfaces:**
- Consumes: `FotoProfil::url()` dan rute `profile.photo.*` (Tugas 8); rute `profile.items.*` dan `UserOwnedItem::BATAS` (Tugas 5); rute `profile.tracks.destroy` (Tugas 9); `InterestRegion::dari()`, `ExperienceLevel::keterangan()`, dan `GET onboarding?langkah=` (Tugas 7); prop bersama `tampilan` dan rute `tampilan.simpan`; `ThemePicker`, `Sakelar`, `Avatar`, `LembarBawah`, `Dialog`, `FormRekamJejak`, `PintuAkun`.
- Produces: `HalamanProfil::untuk(User $user, ?array $kabar): array` dengan kunci `identitas`, `pengalaman`, `preferensi`, `perlengkapan`, `kabar`; props `Profile/Edit` menggantikan `profilPendakian`; TypeScript `KabarProfil`, `RincianProfil`, `IdentitasProfilData`, `PengalamanProfil`, `PreferensiProfil`, `PerlengkapanProfil`; komponen `IdentitasProfil`, `BagianRincian`, `PerlengkapanPribadi`, `TampilanProfil`, `DataLokasi`.

- [ ] **Step 1: Tulis test yang gagal.** Test lama yang diperbarui membawa alasannya: `ProfileTest` mengikuti props
baru (niatnya tetap: profil milik pengguna sesi, ringkasan dari isian sendiri, yang belum diisi ditulis belum diisi),
`InterestRegionTest` mendapat test Profil, dan `NewPageQueryBudgetTest` mengukur Profil dengan data W7.

`tests/Feature/InterestRegionTest.php`:

1. Ganti:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
```

dengan:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;
```

2. Ganti:

```php
    /**
     * Kolom lama yang tidak lagi ditanyakan onboarding tidak dihapus diam-diam.
```

dengan:

```php
    /**
     * Profil menyebut wilayah dengan labelnya, dalam urutan daftar putih.
     */
    public function test_the_profile_lists_regions_by_label(): void
    {
        $user = User::factory()->create();
        $user->preference()->create(['preferred_duration' => 'ONE_DAY', 'preferred_trip_type' => 'TEKTOK', 'interest_regions' => ['SULAWESI', 'JAWA_TIMUR']]);

        $this->actingAs($user)->get(route('profile'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('preferensi.rincian.2', ['label' => 'Wilayah', 'nilai' => 'Jawa Timur, Sulawesi']));
    }

    /**
     * Kolom lama yang tidak lagi ditanyakan onboarding tidak dihapus diam-diam.
```

`tests/Feature/NewPageQueryBudgetTest.php`:

1. Ganti:

```php
    }

    private function simpanJalur(User $user, int $jumlah): void
```

dengan:

```php
    }

    /**
     * Hub Profil W7: perlengkapan pribadi dibaca satu kueri, berapa pun jumlah barangnya.
     */
    public function test_the_profile_page_does_not_grow_with_the_number_of_owned_items(): void
    {
        $user = User::factory()->create();
        $user->ownedItems()->create(['name' => 'Barang 0']);

        $this->actingAs($user);

        $this->tidakTumbuh(
            route('profile'),
            fn () => $user->ownedItems()->createMany(collect(range(1, 12))->map(fn (int $i) => ['name' => 'Barang '.$i])->all()),
            'Halaman Profil tumbuh bersama jumlah perlengkapan'
        );
    }

    private function simpanJalur(User $user, int $jumlah): void
```

`tests/Feature/ProfileTest.php`:

1. Ganti:

```php
    /**
     * Ringkasan profil pendakian memakai label dari enum, dan isian yang belum diisi
     * dikirim sebagai null, bukan ditebak (R-007).
     */
```

dengan:

```php
    /**
     * Ringkasan pengalaman memakai label dari enum, dan isian yang belum diisi dikirim null,
     * bukan ditebak (R-007).
     */
```

2. Ganti:

```php
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('profilPendakian.lengkap', true)
                ->where('profilPendakian.rincian.0', ['label' => 'Tingkat pengalaman', 'nilai' => 'Menengah'])
                ->where('profilPendakian.rincian.1', ['label' => 'Preferensi wilayah', 'nilai' => null])
                ->where('profilPendakian.rincian.2', ['label' => 'Durasi yang Anda sukai', 'nilai' => null]));
    }
```

dengan:

```php
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('pengalaman.lengkap', true)
                ->where('pengalaman.rincian.0', ['label' => 'Tingkat', 'nilai' => 'Menengah'])
                ->where('pengalaman.rincian.2', ['label' => 'Tanjakan tertinggi', 'nilai' => null])
                ->where('preferensi.rincian.1', ['label' => 'Lama pendakian', 'nilai' => null]));
    }

    /**
     * Hub Profil (spec v3 bagian 6.15, prototype 14): identitas dengan tingkat yang diisi
     * sendiri, pengalaman, preferensi termasuk wilayah minat, dan perlengkapan urut nama;
     * "Ubah" menuju langkah onboarding yang bersangkutan.
     */
    public function test_the_profile_hub_reads_the_hikers_own_answers(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 8));
        $user = User::factory()->create(['name' => 'Budi Setiawan', 'created_at' => '2026-02-10 03:00:00']);
        $user->profile()->create(['experience_level' => 'BEGINNER', 'completed_at' => now()]);
        $user->experience()->create(['completed_hikes_count' => 4, 'terrain_experience' => ['FOREST', 'ROCKY'], 'navigation_experience' => 'BASIC', 'highest_elevation_gain_m' => 1660]);
        $user->preference()->create(['preferred_duration' => 'TWO_DAY', 'preferred_trip_type' => 'CAMPING', 'interest_regions' => ['JAWA_TENGAH_DIY', 'JAWA_BARAT_BANTEN']]);
        $user->ownedItems()->createMany([['name' => 'Jas hujan'], ['name' => 'Headlamp']]);

        $this->actingAs($user)->get('/profile')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('identitas.nama', 'Budi Setiawan')
            ->where('identitas.tingkat', 'Pemula, diisi sendiri')
            ->where('identitas.bergabung', 'Bergabung Feb 2026')
            ->where('identitas.foto', null)
            ->where('pengalaman.rincian.1.nilai', '4')
            ->where('pengalaman.rincian.2.nilai', '1.660 m')
            ->where('pengalaman.rincian.3.nilai', 'Hutan, Berbatu')
            ->where('pengalaman.rincian.4.nilai', 'Dasar')
            ->where('pengalaman.ubah', route('onboarding'))
            ->where('preferensi.rincian.0.nilai', 'Camping')
            ->where('preferensi.rincian.1.nilai', '2 hari')
            ->where('preferensi.ubah', route('onboarding', ['langkah' => 3]))
            ->where('perlengkapan.daftar', [['id' => $user->ownedItems()->where('name', 'Headlamp')->value('id'), 'nama' => 'Headlamp'], ['id' => $user->ownedItems()->where('name', 'Jas hujan')->value('id'), 'nama' => 'Jas hujan']])
            ->where('perlengkapan.batas', 40)
            ->where('kabar', null));
    }

    /**
     * Pilihan bahasa disembunyikan sampai terjemahan Inggris ada (DK-40), dan tidak ada
     * tautan kebijakan ke halaman yang belum ada.
     */
    public function test_the_profile_offers_no_control_that_does_not_work_yet(): void
    {
        $sumber = collect([resource_path('js/pages/Profile/Edit.tsx'), ...glob(resource_path('js/components/profil/*.tsx'))])
            ->map(fn (string $berkas) => file_get_contents($berkas))
            ->implode("\n");

        foreach (['English', 'Bahasa', 'Kebijakan data pribadi'] as $kata) {
            $this->assertStringNotContainsString($kata, $sumber);
        }
    }
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='ProfileTest|InterestRegion|NewPageQueryBudget|AccountPagesSource|IndonesianInterface|TrackPreference|PageHeaderContract|KeyboardAndScreenReader|TapTarget|EmptyStateCoverage|NoDeadViewData'`
Expected: FAIL. Pada simulasi rencana: 68 test, 64 lulus, 3 gagal, 1 dilewati. Yang merah: dua test `ProfileTest` (props `identitas` dan `pengalaman` belum ada) dan `InterestRegionTest::test_the_profile_lists_regions_by_label`. `NewPageQueryBudgetTest` dan test sumber lain sudah lulus sebelum halaman ditulis ulang. Satu test yang sudah dilewati di dasar ikut tersaring.

- [ ] **Step 3: Tulis implementasinya.** Kelas dibuat dengan
`DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:class --no-interaction Services/Profil/HalamanProfil`,
lalu isinya diganti persis. Berkas TypeScript dibuat langsung.

Buat `app/Services/Profil/HalamanProfil.php`:

```php
<?php

namespace App\Services\Profil;

use App\Enums\InterestRegion;
use App\Enums\TerrainCharacter;
use App\Models\User;
use App\Models\UserOwnedItem;
use App\Support\Timezone;

/**
 * Kelas halaman Profil (spec v3 bagian 6.15; prototype 14; rencana induk W7 butir 5): identitas
 * dengan foto atau inisial, pengalaman dan preferensi yang diisi sendiri dengan jalan "Ubah" ke
 * langkah onboarding yang bersangkutan, dan perlengkapan pribadi. Nilai yang belum diisi dikirim
 * null dan tampil "Belum diisi", bukan ditebak (R-007). Tingkat disebut "diisi sendiri": ia tidak
 * naik karena riwayat (BR-11).
 *
 * Kueri tetap: profil, pengalaman, dan preferensi dimuat sekali; perlengkapan satu kueri.
 */
final class HalamanProfil
{
    /**
     * @param  array{bagian: string, teks: string}|null  $kabar
     * @return array<string, mixed>
     */
    public function untuk(User $user, ?array $kabar): array
    {
        $user->loadMissing('profile', 'experience', 'preference');
        $profil = $user->profile;
        $pengalaman = $user->experience;
        $preferensi = $user->preference;
        $lengkap = $user->hasCompletedProfile();

        $medan = collect($pengalaman?->terrain_experience ?? [])
            ->map(fn (mixed $nilai) => is_string($nilai) ? TerrainCharacter::tryFrom($nilai)?->label() : null)
            ->filter()
            ->implode(', ');
        $wilayah = implode(', ', array_map(fn (InterestRegion $w) => $w->label(), InterestRegion::dari($preferensi?->interest_regions)));
        $tertinggi = $pengalaman?->highest_elevation_gain_m;

        return [
            'identitas' => [
                'nama' => $user->name,
                'tingkat' => $profil?->experience_level !== null ? $profil->experience_level->label().', diisi sendiri' : null,
                'bergabung' => 'Bergabung '.$user->created_at->copy()->setTimezone(Timezone::DEFAULT)->translatedFormat('M Y'),
                'foto' => FotoProfil::url($user),
            ],
            'pengalaman' => [
                'lengkap' => $lengkap,
                'ubah' => route('onboarding'),
                'rincian' => [
                    ['label' => 'Tingkat', 'nilai' => $profil?->experience_level?->label()],
                    ['label' => 'Pendakian selesai', 'nilai' => $pengalaman !== null ? number_format($pengalaman->completed_hikes_count, 0, ',', '.') : null],
                    ['label' => 'Tanjakan tertinggi', 'nilai' => $tertinggi !== null ? number_format($tertinggi, 0, ',', '.').' m' : null],
                    ['label' => 'Medan', 'nilai' => $medan !== '' ? $medan : null],
                    ['label' => 'Navigasi', 'nilai' => $pengalaman?->navigation_experience?->label()],
                ],
            ],
            'preferensi' => [
                'ubah' => route('onboarding', ['langkah' => 3]),
                'rincian' => [
                    ['label' => 'Jenis trip', 'nilai' => $preferensi?->preferred_trip_type?->labelPendek()],
                    ['label' => 'Lama pendakian', 'nilai' => $preferensi?->preferred_duration?->label()],
                    ['label' => 'Wilayah', 'nilai' => $wilayah !== '' ? $wilayah : null],
                ],
            ],
            'perlengkapan' => [
                'daftar' => $user->ownedItems()->orderBy('name')->orderBy('id')->get(['id', 'name'])
                    ->map(fn (UserOwnedItem $barang) => ['id' => $barang->id, 'nama' => $barang->name])
                    ->all(),
                'batas' => UserOwnedItem::BATAS,
            ],
            'kabar' => $kabar,
        ];
    }
}
```

Buat `resources/js/components/profil/BagianRincian.tsx`:

```tsx
import type { ReactNode } from 'react';
import { Tautan } from '@/components/ui/Tautan';
import type { RincianProfil } from './types';

interface BagianRincianProps {
    id: string;
    judul: string;
    rincian: RincianProfil[];
    /** Tautan "Ubah" ke langkah onboarding yang bersangkutan; tanpa tautan bila belum dapat diubah. */
    ubah?: string | null;
    children?: ReactNode;
}

/**
 * Satu bagian ringkasan Profil (prototype 14): judul dengan "Ubah" di kanannya dan baris label
 * dan nilai. Nilai yang belum diisi ditulis "Belum diisi" dengan warna redup, bukan
 * disembunyikan atau ditebak (R-007).
 */
export function BagianRincian({ id, judul, rincian, ubah = null, children }: BagianRincianProps) {
    return (
        <section aria-labelledby={id} className="rounded-lg border border-subtle bg-surface p-5">
            <div className="flex items-center justify-between gap-3">
                <h2 id={id} className="text-bagian text-primary">
                    {judul}
                </h2>
                {ubah ? (
                    <Tautan
                        href={ubah}
                        className="-mr-2 inline-flex min-h-11 items-center px-2 text-kecil font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                    >
                        Ubah<span className="sr-only"> {judul.toLocaleLowerCase('id-ID')}</span>
                    </Tautan>
                ) : null}
            </div>
            <dl className="mt-2 divide-y divide-subtle">
                {rincian.map((r) => (
                    <div key={r.label} className="flex flex-wrap justify-between gap-x-4 gap-y-0.5 py-2.5">
                        <dt className="text-kecil text-secondary">{r.label}</dt>
                        <dd className={r.nilai ? 'min-w-0 break-words text-right text-kecil font-semibold text-primary' : 'text-kecil text-muted'}>{r.nilai ?? 'Belum diisi'}</dd>
                    </div>
                ))}
            </dl>
            {children}
        </section>
    );
}
```

Buat `resources/js/components/profil/DataLokasi.tsx`:

```tsx
import { router } from '@inertiajs/react';
import { useRef, useState } from 'react';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { Dialog } from '@/components/ui/Lembar';
import { destroy as hapusJejak } from '@/routes/profile/tracks';
import type { KabarProfil } from './types';

/**
 * Lokasi dan jejak di bagian privasi Profil (spec v3 bagian 6.15; PRD §82 sampai §84, BR-13):
 * kalimat tentang di mana lokasi diminta dan diproses, lalu "Hapus semua jejak saya" yang
 * menyebut akibatnya sebelum menghapus. Pendakian dan catatannya tetap ada; yang dicabut
 * hanya titik GPS-nya.
 */
export function DataLokasi({ kabar }: { kabar: KabarProfil | null }) {
    const [bertanya, setBertanya] = useState(false);
    const [menghapus, setMenghapus] = useState(false);
    const pemicu = useRef<HTMLElement | null>(null);

    const hapus = () => {
        setMenghapus(true);
        router.delete(hapusJejak.url(), {
            preserveScroll: true,
            onFinish: () => {
                setMenghapus(false);
                setBertanya(false);
            },
        });
    };

    return (
        <Card title="Lokasi dan jejak">
            <p className="text-kecil text-secondary">
                Lokasi hanya diminta di mode lapangan Mendaki dan dihitung di perangkat Anda. Jejak dikirim ke server hanya bila perekaman jejak menyala, sesudah Anda turun.
            </p>
            <Button
                variant="secondary"
                size="sm"
                className="mt-3"
                onClick={(e) => {
                    pemicu.current = e.currentTarget;
                    setBertanya(true);
                }}
            >
                Hapus semua jejak saya
            </Button>
            <p role="status" className="mt-2 text-kecil text-secondary empty:!mt-0">
                {kabar?.bagian === 'privasi' ? kabar.teks : ''}
            </p>

            <Dialog
                terbuka={bertanya}
                onTutup={() => setBertanya(false)}
                judul="Hapus semua jejak?"
                keterangan="Seluruh titik GPS dari pendakian Anda dihapus permanen dan tidak dapat dikembalikan."
                pemicu={pemicu}
                aksi={
                    <>
                        <Button variant="secondary" onClick={() => setBertanya(false)}>
                            Batal
                        </Button>
                        <Button variant="danger" processing={menghapus} onClick={hapus}>
                            Hapus jejak
                        </Button>
                    </>
                }
            >
                <p className="text-kecil text-primary">Pendakian, riwayat, laporan, dan catatannya tetap ada. Perekaman jejak untuk pendakian berikutnya tidak berubah.</p>
            </Dialog>
        </Card>
    );
}
```

Buat `resources/js/components/profil/IdentitasProfil.tsx`:

```tsx
import { router } from '@inertiajs/react';
import { useRef, useState, type ChangeEvent } from 'react';
import { Avatar } from '@/components/ui/Avatar';
import { Button } from '@/components/ui/Button';
import { InputError } from '@/components/ui/Field';
import { kelas } from '@/lib/kelas';
import { destroy as hapusFoto, store as simpanFoto } from '@/routes/profile/photo';
import type { IdentitasProfilData, KabarProfil } from './types';

/**
 * Identitas di kepala Profil (prototype 14): foto unggahan atau inisial, nama, tingkat yang
 * diisi sendiri, dan bulan bergabung. Foto dipilih lewat isian berkas asli yang tersembunyi di
 * balik labelnya, jadi papan ketik dan pembaca layar memakai kendali bawaan peramban; fokusnya
 * tampil di label. Server membuang metadata lokasi dan mengecilkan fotonya; galat dan kabarnya
 * dibacakan di wilayah status bagian ini.
 */
export function IdentitasProfil({ identitas, kabar }: { identitas: IdentitasProfilData; kabar: KabarProfil | null }) {
    const masukan = useRef<HTMLInputElement>(null);
    const [mengirim, setMengirim] = useState<'unggah' | 'hapus' | null>(null);
    const [galat, setGalat] = useState<string | null>(null);

    const unggah = (e: ChangeEvent<HTMLInputElement>) => {
        const berkas = e.target.files?.[0];

        if (!berkas) {
            return;
        }

        setGalat(null);
        setMengirim('unggah');
        router.post(
            simpanFoto.url(),
            { foto: berkas },
            {
                forceFormData: true,
                preserveScroll: true,
                onError: (g) => setGalat(g.foto ?? 'Foto tidak dapat disimpan.'),
                onFinish: () => {
                    setMengirim(null);

                    if (masukan.current) {
                        masukan.current.value = '';
                    }
                },
            },
        );
    };

    const hapus = () => {
        setGalat(null);
        setMengirim('hapus');
        router.delete(hapusFoto.url(), { preserveScroll: true, onFinish: () => setMengirim(null) });
    };

    const status = mengirim === 'unggah' ? 'Mengunggah foto.' : mengirim === 'hapus' ? 'Menghapus foto.' : kabar?.bagian === 'foto' ? kabar.teks : '';

    return (
        <section aria-label="Identitas" className="flex flex-wrap items-start gap-4">
            <Avatar nama={identitas.nama} foto={identitas.foto} ukuran={64} />
            <div className="min-w-0 flex-1">
                <p className="break-words text-judul-kartu text-primary">{identitas.nama}</p>
                <p className="mt-0.5 text-kecil text-secondary">{identitas.tingkat ?? 'Tingkat belum diisi'}</p>
                <p className="text-meta text-muted">{identitas.bergabung}</p>
                <div className="mt-2 flex flex-wrap items-center gap-x-2">
                    <input
                        ref={masukan}
                        id="foto-profil"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        onChange={unggah}
                        disabled={mengirim !== null}
                        aria-describedby={galat ? 'foto-profil-hint foto-profil-galat' : 'foto-profil-hint'}
                        className="peer sr-only"
                    />
                    <label
                        htmlFor="foto-profil"
                        className={kelas(
                            '-ml-1 inline-flex min-h-11 cursor-pointer items-center px-1 text-kecil font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900',
                            'peer-focus-visible:rounded-control peer-focus-visible:ring-2 peer-focus-visible:ring-brand-600',
                        )}
                    >
                        {identitas.foto ? 'Ganti foto' : 'Pasang foto'}
                    </label>
                    {identitas.foto ? (
                        <Button variant="ghost" size="sm" className="px-2" processing={mengirim === 'hapus'} onClick={hapus}>
                            Hapus foto
                        </Button>
                    ) : null}
                </div>
                <p id="foto-profil-hint" className="text-meta text-muted">
                    JPG, PNG, atau WebP, paling besar 2 MB. Metadata lokasi di foto dibuang.
                </p>
                <InputError id="foto-profil-galat" messages={galat} className="mt-1" />
                <p role="status" className="mt-1 text-kecil text-secondary empty:!mt-0">
                    {status}
                </p>
            </div>
        </section>
    );
}
```

Buat `resources/js/components/profil/PerlengkapanPribadi.tsx`:

```tsx
import { router, useForm } from '@inertiajs/react';
import { useRef, useState, type FormEvent } from 'react';
import { Button } from '@/components/ui/Button';
import { InputError, Label, TextInput } from '@/components/ui/Field';
import { Icon } from '@/components/ui/Icon';
import { LembarBawah } from '@/components/ui/Lembar';
import { kelas } from '@/lib/kelas';
import { destroy as hapusBarang, store as simpanBarang, update as ubahBarang } from '@/routes/profile/items';
import type { KabarProfil, PerlengkapanProfil } from './types';

const chip =
    'inline-flex min-h-11 max-w-full items-center gap-1.5 rounded-full border px-4 text-kecil font-semibold focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2';

/**
 * Perlengkapan pribadi (spec v3 bagian 6.15; ruling W7-R8): barang milik pendaki sebagai chip,
 * masing-masing membuka lembar ubah, dan "Tambah barang". Daftar ini mengisi awal daftar
 * persiapan setiap trip baru sebagai butir yang tetap dikonfirmasi per trip; mengubahnya tidak
 * mengubah daftar trip yang sudah ada. Kabar tulisan dibacakan di wilayah status bagian ini.
 */
export function PerlengkapanPribadi({ perlengkapan, kabar }: { perlengkapan: PerlengkapanProfil; kabar: KabarProfil | null }) {
    const [sasaran, setSasaran] = useState<{ id: number | null; nama: string } | null>(null);
    const [menghapus, setMenghapus] = useState(false);
    const form = useForm({ nama: '' });
    const pemicu = useRef<HTMLElement | null>(null);
    const penuh = perlengkapan.daftar.length >= perlengkapan.batas;

    const buka = (barang: { id: number | null; nama: string }, tombol: HTMLElement) => {
        pemicu.current = tombol;
        form.setData('nama', barang.nama);
        form.clearErrors();
        setSasaran(barang);
    };

    const kirim = (e: FormEvent) => {
        e.preventDefault();

        if (sasaran === null) {
            return;
        }

        const selesai = { preserveScroll: true, onSuccess: () => setSasaran(null), onError: () => document.getElementById('barang-nama')?.focus() };

        if (sasaran.id === null) {
            form.post(simpanBarang.url(), selesai);
        } else {
            form.put(ubahBarang.url(sasaran.id), selesai);
        }
    };

    const hapus = () => {
        if (sasaran?.id == null) {
            return;
        }

        setMenghapus(true);
        router.delete(hapusBarang.url(sasaran.id), {
            preserveScroll: true,
            onSuccess: () => {
                // Chip yang dihapus sudah hilang: fokus kembali ke "Tambah barang", atau ke judul
                // bagian bila tombol itu tidak ada, bukan jatuh ke body.
                pemicu.current = document.getElementById('tambah-barang') ?? document.getElementById('judul-perlengkapan');
                setSasaran(null);
            },
            onFinish: () => setMenghapus(false),
        });
    };

    return (
        <section aria-labelledby="judul-perlengkapan" className="rounded-lg border border-subtle bg-surface p-5">
            <h2 id="judul-perlengkapan" tabIndex={-1} className="text-bagian text-primary focus:outline-none">
                Perlengkapan pribadi
            </h2>
            <p className="mt-1 text-kecil text-secondary">Dipakai untuk mengisi awal daftar persiapan setiap trip baru. Tetap Anda konfirmasi di setiap trip.</p>

            {perlengkapan.daftar.length === 0 ? (
                <p className="mt-3 text-kecil text-muted">Belum ada barang. Barang yang Anda tambahkan masuk ke daftar persiapan trip berikutnya, belum dikonfirmasi.</p>
            ) : (
                <ul className="mt-3 flex flex-wrap gap-2">
                    {perlengkapan.daftar.map((b) => (
                        <li key={b.id} className="max-w-full">
                            <button type="button" onClick={(e) => buka(b, e.currentTarget)} className={kelas(chip, 'border-control bg-surface text-primary hover:bg-surface-sunken')}>
                                <span className="min-w-0 break-words">{b.nama}</span>
                                <span className="sr-only">, ubah</span>
                            </button>
                        </li>
                    ))}
                </ul>
            )}

            {penuh ? (
                <p className="mt-3 text-kecil text-secondary">Daftar sudah berisi {perlengkapan.batas} barang, batasnya. Hapus yang tidak lagi Anda bawa untuk menambah yang baru.</p>
            ) : (
                <button
                    id="tambah-barang"
                    type="button"
                    onClick={(e) => buka({ id: null, nama: '' }, e.currentTarget)}
                    className={kelas(chip, 'mt-3 border-dashed border-brand-700 font-bold text-brand-ink hover:bg-brand-50')}
                >
                    <Icon name="plus" size={16} />
                    Tambah barang
                </button>
            )}

            <p role="status" className="mt-2 text-kecil text-secondary empty:!mt-0">
                {kabar?.bagian === 'perlengkapan' ? kabar.teks : ''}
            </p>

            <LembarBawah
                terbuka={sasaran !== null}
                onTutup={() => setSasaran(null)}
                judul={sasaran?.id == null ? 'Tambah barang' : 'Ubah barang'}
                keterangan="Nama barang seperti yang Anda sebut sehari-hari, misalnya Headlamp atau Jas hujan."
                pemicu={pemicu}
                aksi={
                    <>
                        {sasaran?.id != null ? (
                            <Button variant="ghost" className="mr-auto text-danger-900" processing={menghapus} onClick={hapus}>
                                Hapus dari daftar
                            </Button>
                        ) : null}
                        <Button type="submit" form="formulir-barang" processing={form.processing}>
                            {sasaran?.id == null ? 'Tambah' : 'Simpan'}
                        </Button>
                    </>
                }
            >
                <form id="formulir-barang" onSubmit={kirim} noValidate>
                    <Label htmlFor="barang-nama">Nama barang</Label>
                    <TextInput
                        id="barang-nama"
                        value={form.data.nama}
                        onChange={(e) => form.setData('nama', e.target.value)}
                        maxLength={60}
                        autoComplete="off"
                        aria-invalid={form.errors.nama ? true : undefined}
                        aria-describedby={form.errors.nama ? 'barang-nama-galat' : undefined}
                    />
                    <InputError id="barang-nama-galat" messages={form.errors.nama} className="mt-2" />
                    {sasaran?.id != null ? <p className="mt-2 text-kecil text-secondary">Daftar persiapan trip yang sudah ada tidak berubah.</p> : null}
                </form>
            </LembarBawah>
        </section>
    );
}
```

Buat `resources/js/components/profil/TampilanProfil.tsx`:

```tsx
import { router, usePage } from '@inertiajs/react';
import { Card } from '@/components/ui/Card';
import { Sakelar } from '@/components/ui/Sakelar';
import { ThemePicker } from '@/components/ui/ThemePicker';
import { simpan } from '@/routes/tampilan';
import type { PropsBersama } from '@/types';

/**
 * Tampilan di Profil (spec v3 bagian 6.15): tema, efek transparan, dan gerak, ketiganya cookie
 * milik peramban ini (rute tema.simpan dan tampilan.simpan). Pilihan bahasa tidak ditampilkan
 * sampai terjemahan Inggris ada (DK-40). Sakelar langsung menyimpan, dan satu sakelar tidak
 * mengembalikan yang lain karena server hanya menulis pilihan yang dikirim.
 */
export function TampilanProfil() {
    const { tampilan } = usePage<PropsBersama>().props;

    const ubah = (data: { efek: 'otomatis' | 'solid' } | { gerak: 'sistem' | 'kurangi' }) => router.post(simpan.url(), data, { preserveScroll: true });

    return (
        <Card title="Tampilan" subtitle="Berlaku di peramban ini.">
            <ThemePicker />
            <div className="mt-4 divide-y divide-subtle border-t border-subtle">
                <Sakelar
                    judul="Kurangi efek transparan"
                    keterangan="Permukaan yang melayang memakai warna penuh, lebih mudah dibaca di bawah matahari."
                    nyala={tampilan.efek === 'solid'}
                    onUbah={(nyala) => ubah({ efek: nyala ? 'solid' : 'otomatis' })}
                />
                <Sakelar
                    judul="Kurangi gerak"
                    keterangan="Animasi dipendekkan di aplikasi ini, apa pun setelan perangkat Anda."
                    nyala={tampilan.gerak === 'kurangi'}
                    onUbah={(nyala) => ubah({ gerak: nyala ? 'kurangi' : 'sistem' })}
                />
            </div>
            <p className="mt-3 text-kecil text-secondary">Jam trip ditampilkan menurut zona gunungnya, dengan penanda WIB, WITA, atau WIT.</p>
        </Card>
    );
}
```

Buat `resources/js/components/profil/types.ts`:

```ts
/** Kabar tulisan di Profil (flash `kabar-profil`): bagian mana yang menyebutnya. */
export interface KabarProfil {
    bagian: 'perlengkapan' | 'foto' | 'privasi';
    teks: string;
}

/** Satu baris ringkasan; nilai null tampil "Belum diisi" (R-007). */
export interface RincianProfil {
    label: string;
    nilai: string | null;
}

/** Isi App\Services\Profil\HalamanProfil::untuk(). */
export interface IdentitasProfilData {
    nama: string;
    tingkat: string | null;
    bergabung: string;
    foto: string | null;
}

export interface PengalamanProfil {
    lengkap: boolean;
    ubah: string;
    rincian: RincianProfil[];
}

export interface PreferensiProfil {
    ubah: string;
    rincian: RincianProfil[];
}

export interface PerlengkapanProfil {
    daftar: Array<{ id: number; nama: string }>;
    batas: number;
}
```

`app/Http/Controllers/Profile/ProfileController.php`:

1. Ganti:

```php
use App\Services\Profil\FotoProfil;
use App\Support\PetunjukKataSandi;
```

dengan:

```php
use App\Services\Profil\FotoProfil;
use App\Services\Profil\HalamanProfil;
use App\Support\PetunjukKataSandi;
```

2. Ganti:

```php
/**
 * Halaman Profil: akun, profil pendakian, perekaman jejak, tampilan, dan penghapusan
 * akun. Setiap aksi bekerja pada pengguna yang sedang masuk; tidak ada id pengguna
 * yang diterima dari klien, jadi tidak ada akun orang lain yang dapat disentuh.
 */
```

dengan:

```php
/**
 * Halaman Profil (spec v3 bagian 6.15, prototype 14): identitas, pengalaman, preferensi,
 * perlengkapan pribadi, tampilan, privasi, akun, pintu konsol, dan penghapusan akun. Isi
 * pendakiannya disusun HalamanProfil. Setiap aksi bekerja pada pengguna yang sedang masuk;
 * tidak ada id pengguna yang diterima dari klien, jadi tidak ada akun orang lain yang dapat
 * disentuh.
 */
```

3. Ganti:

```php
{
    public function edit(Request $request): Response
    {
        $user = $request->user()->loadMissing(['profile', 'preference']);

        return Inertia::render('Profile/Edit', [
```

dengan:

```php
{
    public function edit(Request $request, HalamanProfil $halaman): Response
    {
        $user = $request->user()->loadMissing(['profile', 'experience', 'preference']);

        return Inertia::render('Profile/Edit', [
```

4. Ganti:

```php
            'petunjukSandi' => PetunjukKataSandi::teks(),

            // Ringkasan dari isian onboarding, dengan label yang sama. Yang belum diisi
            // dikirim null dan ditampilkan sebagai belum diisi, bukan ditebak (R-007).
            'profilPendakian' => [
                'lengkap' => $user->hasCompletedProfile(),
                'rincian' => [
                    ['label' => 'Tingkat pengalaman', 'nilai' => $user->profile?->experience_level?->label()],
                    ['label' => 'Preferensi wilayah', 'nilai' => $user->profile?->region_preference ?: null],
                    ['label' => 'Durasi yang Anda sukai', 'nilai' => $user->preference?->preferred_duration?->label()],
                    ['label' => 'Tipe perjalanan', 'nilai' => $user->preference?->preferred_trip_type?->label()],
                ],
            ],
        ]);
```

dengan:

```php
            'petunjukSandi' => PetunjukKataSandi::teks(),
            ...$halaman->untuk($user, $request->session()->get('kabar-profil')),
        ]);
```

`resources/js/pages/Profile/Edit.tsx`:

1. Ganti:

```tsx
import { PintuAkun } from '@/components/akun/PintuAkun';
import { RingkasanProfilPendakian } from '@/components/akun/RingkasanProfilPendakian';
import type { ProfilPendakian } from '@/components/akun/types';
import { Card } from '@/components/ui/Card';
import { PageHeader } from '@/components/ui/PageHeader';
import { ThemePicker } from '@/components/ui/ThemePicker';
import { AppLayout } from '@/layouts/AppLayout';
```

dengan:

```tsx
import { PintuAkun } from '@/components/akun/PintuAkun';
import { BagianRincian } from '@/components/profil/BagianRincian';
import { DataLokasi } from '@/components/profil/DataLokasi';
import { IdentitasProfil } from '@/components/profil/IdentitasProfil';
import { PerlengkapanPribadi } from '@/components/profil/PerlengkapanPribadi';
import { TampilanProfil } from '@/components/profil/TampilanProfil';
import type { IdentitasProfilData, KabarProfil, PengalamanProfil, PerlengkapanProfil, PreferensiProfil } from '@/components/profil/types';
import { Button } from '@/components/ui/Button';
import { PageHeader } from '@/components/ui/PageHeader';
import { AppLayout } from '@/layouts/AppLayout';
```

2. Ganti:

```tsx
    petunjukSandi: string;
    profilPendakian: ProfilPendakian;
}
```

dengan:

```tsx
    petunjukSandi: string;
    identitas: IdentitasProfilData;
    pengalaman: PengalamanProfil;
    preferensi: PreferensiProfil;
    perlengkapan: PerlengkapanProfil;
    kabar: KabarProfil | null;
}
```

3. Ganti:

```tsx
/**
 * Profil: akun lebih dulu (nama, email, kata sandi), lalu preferensi yang membentuk
 * pengalaman (profil pendakian, perekaman jejak, tampilan), dan penghapusan akun
 * terakhir, terpisah dari yang lain.
 */
export default function Edit() {
    const { status, perluVerifikasi, rekamJejak, pesanJejak, petunjukSandi, profilPendakian } = usePage<Props>().props;

    return (
        <div className="py-8">
            <div className="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader induk={{ href: dashboard.url(), label: 'Beranda' }} title="Profil" />

                <FormInformasiProfil perluVerifikasi={perluVerifikasi} status={status} />
```

dengan:

```tsx
/**
 * Profil (spec v3 bagian 6.15, prototype 14). Urutannya dari yang membentuk penilaian ke yang
 * jarang disentuh: identitas, pengalaman dan preferensi yang diisi sendiri, perlengkapan
 * pribadi, tampilan, privasi (perekaman jejak, lokasi, hapus jejak), akun (nama, email, kata
 * sandi), pintu konsol dan Keluar, lalu penghapusan akun terakhir, terpisah dari yang lain.
 * Pilihan bahasa tidak ditampilkan sampai terjemahan Inggris ada (DK-40).
 */
export default function Edit() {
    const { status, perluVerifikasi, rekamJejak, pesanJejak, petunjukSandi, identitas, pengalaman, preferensi, perlengkapan, kabar } = usePage<Props>().props;

    return (
        <div className="py-6">
            <div className="mx-auto max-w-2xl space-y-6 px-4 sm:px-6">
                <PageHeader induk={{ href: dashboard.url(), label: 'Beranda' }} title="Profil" />

                <IdentitasProfil identitas={identitas} kabar={kabar} />

                <BagianRincian id="judul-pengalaman" judul="Pengalaman" rincian={pengalaman.rincian} ubah={pengalaman.lengkap ? pengalaman.ubah : null}>
                    <p className="mt-3 text-kecil text-secondary">
                        Ini masukan, bukan sertifikasi. Riwayat dipakai sebagai konteks penilaian, tetapi tingkat Anda tidak naik otomatis. Ubah sendiri bila menurut Anda sudah berubah.
                    </p>
                    {pengalaman.lengkap ? null : (
                        <Button href={pengalaman.ubah} className="mt-3">
                            Isi profil pendaki
                        </Button>
                    )}
                </BagianRincian>

                <BagianRincian id="judul-preferensi" judul="Preferensi" rincian={preferensi.rincian} ubah={pengalaman.lengkap ? preferensi.ubah : null} />

                <PerlengkapanPribadi perlengkapan={perlengkapan} kabar={kabar} />

                <TampilanProfil />

                <FormRekamJejak rekamJejak={rekamJejak} pesan={pesanJejak} />
                <DataLokasi kabar={kabar} />

                <FormInformasiProfil perluVerifikasi={perluVerifikasi} status={status} />
```

4. Ganti:

```tsx
                <FormKataSandi petunjukSandi={petunjukSandi} />

                <RingkasanProfilPendakian profil={profilPendakian} />
                <FormRekamJejak rekamJejak={rekamJejak} pesan={pesanJejak} />

                <Card
                    title="Tampilan"
                    subtitle={'Tema berlaku di peramban ini. "Ikuti sistem" mengikuti setelan terang atau gelap perangkat Anda, termasuk saat ia berganti sendiri.'}
                >
                    <ThemePicker />
                </Card>

                <PintuAkun />
```

dengan:

```tsx
                <FormKataSandi petunjukSandi={petunjukSandi} />

                <PintuAkun />
```

`tests/Pratinjau/HalamanPratinjauTest.php`:

1. Ganti:

```php
    /**
     * Halaman tugas: bilah tab disembunyikan dan tombol kembali menuju Jelajah (spec v3
```

dengan:

```php
    /**
     * Profil (acuan 14): identitas dengan inisial, pengalaman dan preferensi Budi termasuk
     * wilayah minat, perlengkapan pribadi, tampilan, privasi, akun, dan pintu konsol.
     */
    public function test_profil(): void
    {
        $budi = $this->sebagaiBudi();
        $budi->preference->update(['interest_regions' => ['JAWA_BARAT_BANTEN', 'JAWA_TENGAH_DIY']]);
        $budi->ownedItems()->createMany([['name' => 'Headlamp'], ['name' => 'Jas hujan'], ['name' => 'Jaket hangat'], ['name' => 'Sepatu mendaki'], ['name' => 'Kompor lipat']]);

        $this->tulisPratinjau('profil', $this->get(self::ASAL.'/profile'), 'Profile/Edit');
    }

    /**
     * Profil pendaki baru: pengalaman belum diisi dengan jalan ke onboarding, tanpa barang.
     */
    public function test_profil_kosong(): void
    {
        $this->seed(DemoSeeder::class);
        $this->actingAs(User::factory()->create(['name' => 'Budi Setiawan']));

        $this->tulisPratinjau('profil-kosong', $this->get(self::ASAL.'/profile'), 'Profile/Edit');
    }

    /**
     * Halaman tugas: bilah tab disembunyikan dan tombol kembali menuju Jelajah (spec v3
```

Hapus berkas `resources/js/components/akun/RingkasanProfilPendakian.tsx`.

Hapus berkas `resources/js/components/akun/types.ts`.

- [ ] **Step 4: Build dan tipe.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `npx tsc --noEmit`
Expected: `✓ built`; tsc tanpa keluaran galat.

- [ ] **Step 5: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Services/Profil/HalamanProfil.php app/Http/Controllers/Profile/ProfileController.php tests/Feature/ProfileTest.php tests/Feature/InterestRegionTest.php tests/Feature/NewPageQueryBudgetTest.php tests/Pratinjau/HalamanPratinjauTest.php`, lalu `php artisan test --compact --filter='ProfileTest|InterestRegion|NewPageQueryBudget|AccountPagesSource|IndonesianInterface|TrackPreference|PageHeaderContract|KeyboardAndScreenReader|TapTarget|EmptyStateCoverage|NoDeadViewData'`
Expected: PASS. Pada simulasi rencana: 68 test, 67 lulus, 1 dilewati.

- [ ] **Step 6: Suite, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 2.130 test, 2.128 lulus, 2 dilewati (sebelum tugas: 2.126).

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `php artisan test --compact --testsuite=Pratinjau`
Expected: PASS; halaman `profil` kini hub prototype 14. Pada simulasi rencana: 36 test lulus.

- [ ] **Step 7: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 11: Data contoh W7

DK-35 dan ruling W7-R19. Budi (data contoh) membawa lima barang perlengkapan seperti prototype 14, target tanjakan
1.000 m dan satu pendakian per bulan, wilayah minat Jawa Barat dan Banten serta Jawa Tengah dan DI Yogyakarta, alasan
batal cuaca untuk trip Pangrango, dan satu catatan manual Papandayan tiga hari sebelum hari ini dengan angka yang
diisinya sendiri. Data contoh tetap ditandai `is_demo` pada baris yang punya penanda dan tidak membuat foto profil.
Harness pratinjau memakai data ini apa adanya.

**Files:**
- Modify: `database/seeders/DemoSeeder.php`
- Test: `tests/Feature/DemoSeederTest.php`, `tests/Pratinjau/HalamanPratinjauTest.php`

**Interfaces:**
- Consumes: model dan enum Tugas 1, 3, 5, dan 7 (`HikeRecordSource::MANUAL`, `PersonalTarget`, `PersonalTargetMetric`, `UserOwnedItem`, `InterestRegion`); `CancellationReason::WEATHER` (W6).
- Produces: data contoh W7 yang dibaca Progres, Profil, dan Persiapan di pratinjau; `memount:demo` tidak dijalankan di rencana ini.

- [ ] **Step 1: Tulis test yang gagal.**

`tests/Feature/DemoSeederTest.php`:

1. Ganti:

```php
        $this->assertSame(1, TripPlan::where('status', TripStatus::CANCELLED->value)->count());
        $this->assertSame(3, HikingHistory::where('user_id', $budi->id)->count());
    }
```

dengan:

```php
        $this->assertSame(1, TripPlan::where('status', TripStatus::CANCELLED->value)->count());
        $this->assertSame(3, HikingHistory::where('user_id', $budi->id)->where('source', 'TRIP')->count());
        // W7: satu catatan di luar trip, target, perlengkapan, wilayah minat, dan alasan batal.
        $this->assertSame(1, HikingHistory::where('user_id', $budi->id)->where('source', 'MANUAL')->count());
        $this->assertSame(['GAIN', 'HIKES'], $budi->personalTargets()->orderBy('metric')->pluck('metric')->map->value->all());
        $this->assertSame(5, $budi->ownedItems()->count());
        $this->assertSame(['JAWA_BARAT_BANTEN', 'JAWA_TENGAH_DIY'], $budi->preference->interest_regions);
        $this->assertSame('WEATHER', TripPlan::where('status', TripStatus::CANCELLED->value)->sole()->cancellation_reason->value);
    }
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='DemoSeeder|DemoBadge'`
Expected: FAIL. Pada simulasi rencana: 14 test, 13 lulus, 1 gagal. Yang merah: `DemoSeederTest::test_the_sample_tells_the_prototype_story` (data contoh W7 belum ada).

- [ ] **Step 3: Tulis implementasinya.**

`database/seeders/DemoSeeder.php`:

1. Ganti:

```php
namespace Database\Seeders;

use App\Enums\CheckpointType;
```

dengan:

```php
namespace Database\Seeders;

use App\Enums\CancellationReason;
use App\Enums\CheckpointType;
```

2. Ganti:

```php
use App\Enums\ExperienceLevel;
use App\Enums\ModerationStatus;
```

dengan:

```php
use App\Enums\ExperienceLevel;
use App\Enums\HikeRecordSource;
use App\Enums\InterestRegion;
use App\Enums\ModerationStatus;
```

3. Ganti:

```php
 * menunggu data); trip "Gede via Cibodas" lima belas hari lagi dengan pengingat surel H-3 dan
 * H-1; tiga pendakian selesai; satu trip batal; lima jalur tersimpan; dan satu aturan izin
 * tingkat gunung untuk Gunung Gede (jendela pesan H-30 sampai H-3). Simpanan, pengingat, dan
 * aturan izin mengisi kerangka W2 (hitungan Simpanan dan lembar Pengingat) lewat tabel dan
```

dengan:

```php
 * menunggu data); trip "Gede via Cibodas" lima belas hari lagi dengan pengingat surel H-3 dan
 * H-1; tiga pendakian selesai dan satu catatan pendakian di luar trip; satu trip batal karena
 * cuaca; lima jalur tersimpan; target pribadi, lima barang perlengkapan pribadi, dan dua wilayah
 * minat (W7, prototype 13 dan 14); dan satu aturan izin tingkat gunung untuk Gunung Gede
 * (jendela pesan H-30 sampai H-3). Simpanan, pengingat, dan
 * aturan izin mengisi kerangka W2 (hitungan Simpanan dan lembar Pengingat) lewat tabel dan
```

4. Ganti:

```php
            'region_preference' => 'Jawa Barat, Jawa Tengah',
            'record_track' => false,
        ]);

        $this->command?->info('Pendaki contoh: '.self::EMAIL_PENDAKI." dengan kata sandi {$kataSandi} (hanya untuk berkas lokal ini).");
```

dengan:

```php
            'region_preference' => 'Jawa Barat, Jawa Tengah',
            'interest_regions' => [InterestRegion::JAWA_BARAT_BANTEN->value, InterestRegion::JAWA_TENGAH_DIY->value],
            'record_track' => false,
        ]);

        // Prototype 14 dan 13 (W7): perlengkapan pribadi yang mengisi awal daftar persiapan, dan
        // target bulanan yang dibandingkan hanya dengan pendakian Budi sendiri.
        $budi->ownedItems()->createMany(array_map(fn (string $nama) => ['name' => $nama], ['Headlamp', 'Jas hujan', 'Jaket hangat', 'Sepatu mendaki', 'Kompor lipat']));
        $budi->personalTargets()->createMany([['metric' => 'GAIN', 'monthly_value' => 1000], ['metric' => 'HIKES', 'monthly_value' => 1]]);

        $this->command?->info('Pendaki contoh: '.self::EMAIL_PENDAKI." dengan kata sandi {$kataSandi} (hanya untuk berkas lokal ini).");
```

5. Ganti:

```php
            'status' => TripStatus::CANCELLED->value,
        ]);
```

dengan:

```php
            'status' => TripStatus::CANCELLED->value,
            'cancellation_reason' => CancellationReason::WEATHER->value,
        ]);

        // Pendakian di luar trip yang dicatat Budi sendiri (FR-25, W7): jalur katalog, angka
        // yang ia tulis, tanpa trip.
        HikingHistory::create([
            'user_id' => $budi->id,
            'source' => HikeRecordSource::MANUAL->value,
            'trip_plan_id' => null,
            'trail_id' => $jalur['papandayan-via-camp-david']->id,
            'trip_type' => TripType::TEKTOK->value,
            'completion_state' => CompletionState::COMPLETED->value,
            'completed_at' => $hariIni->copy()->subDays(3)->setTime(12, 0),
            'recorded_gain_m' => 610,
            'recorded_distance_m' => 14800,
            'recorded_moving_minutes' => 330,
        ]);
```

`tests/Pratinjau/HalamanPratinjauTest.php`:

1. Ganti:

```php
    /**
     * Progres W7 (acuan 13): riwayat Budi dari DemoSeeder (Andong, Batur, dan Prau tuntas;
     * Pangrango dibatalkan, di sini karena cuaca) ditambah satu catatan pendakian di luar trip di
     * Papandayan pada awal bulan ini dengan angka yang ditulis Budi sendiri, dan dua target bulanan:
     * tanjakan yang belum tercapai dan jumlah pendakian yang tercapai.
     */
```

dengan:

```php
    /**
     * Progres W7 (acuan 13), seluruhnya dari DemoSeeder: Andong, Batur, dan Prau tuntas;
     * Pangrango dibatalkan karena cuaca; satu catatan pendakian di luar trip di Papandayan
     * dengan angka yang ditulis Budi sendiri; dan dua target bulanan.
     */
```

2. Ganti:

```php
    {
        $budi = $this->sebagaiBudi();
        TripPlan::where('user_id', $budi->id)->where('status', TripStatus::CANCELLED->value)->update(['cancellation_reason' => CancellationReason::WEATHER->value]);
        HikingHistory::factory()->manual()->for($budi)->create([
            'trail_id' => Trail::where('slug', 'papandayan-via-camp-david')->value('id'),
            'trip_type' => 'TEKTOK',
            'recorded_gain_m' => 610,
            'recorded_distance_m' => 14800,
            'recorded_moving_minutes' => 330,
            'completed_at' => now('Asia/Jakarta')->startOfMonth()->setTime(6, 0)->utc(),
        ]);
        $budi->personalTargets()->createMany([['metric' => 'GAIN', 'monthly_value' => 1000], ['metric' => 'HIKES', 'monthly_value' => 1]]);

        $this->tulisPratinjau('progres', $this->get(self::ASAL.'/progres'), 'Community/HikerProgress');
```

dengan:

```php
    {
        $this->sebagaiBudi();

        $this->tulisPratinjau('progres', $this->get(self::ASAL.'/progres'), 'Community/HikerProgress');
```

3. Ganti:

```php
    public function test_onboarding_rencana(): void
    {
        $budi = $this->sebagaiBudi();
        $budi->preference->update(['interest_regions' => ['JAWA_BARAT_BANTEN', 'JAWA_TENGAH_DIY']]);

        $this->tulisPratinjau('onboarding-rencana', $this->get(self::ASAL.'/onboarding?langkah=3'), 'Onboarding/ProfileSetup');
```

dengan:

```php
    public function test_onboarding_rencana(): void
    {
        $this->sebagaiBudi();

        $this->tulisPratinjau('onboarding-rencana', $this->get(self::ASAL.'/onboarding?langkah=3'), 'Onboarding/ProfileSetup');
```

4. Ganti:

```php
    {
        $budi = $this->sebagaiBudi();
        $budi->preference->update(['interest_regions' => ['JAWA_BARAT_BANTEN', 'JAWA_TENGAH_DIY']]);
        $budi->ownedItems()->createMany([['name' => 'Headlamp'], ['name' => 'Jas hujan'], ['name' => 'Jaket hangat'], ['name' => 'Sepatu mendaki'], ['name' => 'Kompor lipat']]);

        $this->tulisPratinjau('profil', $this->get(self::ASAL.'/profile'), 'Profile/Edit');
```

dengan:

```php
    {
        $this->sebagaiBudi();

        $this->tulisPratinjau('profil', $this->get(self::ASAL.'/profile'), 'Profile/Edit');
```

- [ ] **Step 4: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent database/seeders/DemoSeeder.php tests/Feature/DemoSeederTest.php tests/Pratinjau/HalamanPratinjauTest.php`, lalu `php artisan test --compact --filter='DemoSeeder|DemoBadge'`
Expected: PASS. Pada simulasi rencana: 14 test, 14 lulus.

- [ ] **Step 5: Suite, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 2.130 test, 2.128 lulus, 2 dilewati (sebelum tugas: 2.130).

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `php artisan test --compact --testsuite=Pratinjau`
Expected: PASS; Progres, Profil, dan Persiapan di pratinjau memuat data contoh W7. Pada simulasi rencana: 36 test lulus.

- [ ] **Step 6: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 12: Dokumen

`DESIGN.md` (bagian pola layar: Progres, Profil, dan onboarding), `docs/ARCHITECTURE.md` (tempat mengubah disk foto dan
wilayah minat; kelas halaman W7), dan dokumen fitur 04, 06, dan 07 (status FAKTA dengan jalur berkas dan test, tanggal
periksa, ruling W7). Di dokumen fitur 04, keterangan DK-50 dikoreksi menjadi W6, gelombang yang membangunnya. `docs/PRD.md`
tidak disunting (Konflik 9), dan `HANDOVER.md` diperbarui koordinator di Tugas 13. Setiap jangkar dokumen satu baris atau
sepenggal baris, karena dokumen di repo nyata berbeda dengan dasar sesudah perbaikan akhir W4 (Pra-cek butir 13).

**Files:**
- Modify: `DESIGN.md`, `docs/ARCHITECTURE.md`, `docs/fitur/04-perjalanan-persiapan.md`, `docs/fitur/06-pendakian-progres.md`, `docs/fitur/07-akun-masuk.md`

**Interfaces:**
- Consumes: nama kelas, rute, kolom, dan test Tugas 1 sampai 11.
- Produces: dokumen yang menyebut keadaan W7 sebagai FAKTA dengan bukti berkas (R-023).

- [ ] **Step 1: Catat garis dasar.** Tidak ada test baru; test penjaga dokumen dijalankan sebelum dan sesudah suntingan.

- [ ] **Step 2: Jalankan test penjaga dokumen.**

Run: `php artisan test --compact --filter='PrdReferenceIntegrity|DesignDirection|TeamDocsIndex|RepoHygiene'`
Expected: PASS. Pada simulasi rencana: 31 test lulus sebelum dokumen disunting.

- [ ] **Step 3: Sunting dokumen.**

`DESIGN.md`:

1. Ganti:

```markdown
**Fokus tidak pernah jatuh ke body.**
```

dengan:

```markdown
**Progres, Profil, dan onboarding** (W7, spec v3 bagian 6.14 sampai 6.16, prototype 00, 13, dan 14). Grafik per
pendakian digambar sebagai baris mendatar dengan angka tertulis di ujungnya; baris itu disembunyikan dari pembaca layar
dan digantikan tabel di dalam pembungkus `sr-only`, karena tabel yang diberi `sr-only` sendiri melebarkan halaman
sempit. Pendakian batal bergaris putus-putus dan menyebut dirinya dengan kata. Target bulanan tanpa persen, rentetan,
atau teguran (R-015), dengan strip dua belas bulan yang ditulis ulang untuk pembaca layar. Onboarding adalah halaman
tugas tanpa bilah tab: satu pertanyaan per langkah, kartu radio berketerangan, chip radio untuk pilihan pendek, bar
aksi di bawah, dan layar hasil yang menyebut "N jalur berlabel Cocok". Profil menyusun bagian dari yang membentuk
penilaian ke yang jarang disentuh; perlengkapan pribadi berupa chip yang membuka lembar ubah. Setiap bagian yang
menulis punya wilayah `role="status"` sendiri yang selalu terpasang.

**Fokus tidak pernah jatuh ke body.**
```

`docs/ARCHITECTURE.md`:

1. Ganti:

```markdown
| Menambah tag kondisi jalur | `App\Enums\ConditionTag` |
```

dengan:

```markdown
| Menambah tag kondisi jalur | `App\Enums\ConditionTag` |
| Disk, batas ukuran, dan sisi foto profil | `config/filesystems.php` → `avatars_disk` (`AVATARS_DISK`), dan `config/hiking.php` → `uploads.avatar_max_kb` serta `uploads.avatar_dimension`; berkasnya hanya disajikan rute `profile.photo.show` kepada pemilik sesi |
| Daftar wilayah minat dan turunan pengalaman onboarding | `App\Enums\InterestRegion` (daftar putih DK-42) dan `App\Services\Profil\TurunanPengalaman` (tangga navigasi yang sama dengan `NavigationComplexity::expectedNavigationRank`) |
```

2. Ganti:

```markdown
| `Trails\ProfilElevasiJalur`, `Trails\PosSepanjangJalur` | Profil elevasi dengan ruas tercuram, dan km pos di sepanjang garis rute (PostGIS); tanpa geometri tidak ada km yang ditebak |
```

dengan:

```markdown
| `Trails\ProfilElevasiJalur`, `Trails\PosSepanjangJalur` | Profil elevasi dengan ruas tercuram, dan km pos di sepanjang garis rute (PostGIS); tanpa geometri tidak ada km yang ditebak |
| `Community\HubProgres`, `Community\PencapaianPribadi` | Kelas halaman Progres (W7): ringkasan tahun, grafik per pendakian, target bulanan, pencapaian dari riwayat sendiri, riwayat dua sumber (trip dan catatan manual), dan peta gunung; angka satu pendakian dari satu aturan (`HubProgres::angka`) |
| `Profil\HalamanProfil`, `Profil\FotoProfil`, `Profil\TurunanPengalaman` | Hub Profil (W7): identitas, pengalaman, preferensi, dan perlengkapan; foto profil yang ditulis ulang tanpa EXIF sebelum disimpan; nilai pengalaman yang diturunkan dari jalur terberat |
| `Trips\IsiAwalPerlengkapan`, `Trips\CatatanButir`, `Trails\CariJalur` | Perlengkapan pribadi yang mengisi awal daftar persiapan sebagai butir belum dikonfirmasi (W7-R8), catatan per butir Perlengkapan dan Logistik (W7-R9), dan pencarian jalur katalog untuk isian satu jalur |
```

`docs/fitur/04-perjalanan-persiapan.md`:

1. Ganti:

```markdown
| `trip_preparation_items` | `category`, `label`, `is_critical`, `status`, `status_updated_at` | `create_trip_preparation_items_table` |
```

dengan:

```markdown
| `trip_preparation_items` | `category`, `label`, `is_critical`, `status`, `status_updated_at`, `user_owned_item_id` (W7-R8), `note` (W7-R9, paling panjang 80) | `create_trip_preparation_items_table`, `add_owned_item_to_trip_preparation_items_table`, `add_note_to_trip_preparation_items_table` |
```

2. Ganti:

```markdown
isi awal dari perlengkapan pribadi menyusul W
```

dengan:

```markdown
isi awal dari perlengkapan pribadi dan catatan per butir ada sejak W7 (`app/Services/Trips/IsiAwalPerlengkapan.php`, `app/Services/Trips/CatatanButir.php`, `tests/Feature/PreparationNoteTest.php`), yang sebelumnya dijadwalkan menyusul W
```

3. Ganti:

```markdown
**DK-50** alasan pembatalan (W7).
```

dengan:

```markdown
**DK-50** alasan pembatalan (W6); **W7-R8** isi awal perlengkapan pribadi; **W7-R9** catatan per butir untuk Perlengkapan dan Logistik.
```

`docs/fitur/06-pendakian-progres.md`:

1. Ganti:

```markdown
**Diperiksa terhadap kode:** 26 September 2026, setelah gelombang W0. Status adalah FAKTA dari berkas yang disebut (R-023).
```

dengan:

```markdown
**Diperiksa terhadap kode:** 8 Oktober 2026 untuk baris yang disentuh W7; selebihnya 26 September 2026, setelah gelombang W0. Status adalah FAKTA dari berkas yang disebut (R-023).
```

2. Ganti:

```markdown
| `personal_targets`, `user_owned_items` | hanya `id` dan `timestamps` (rintisan kosong) | `create_personal_targets_table`, `create_user_owned_items_table` |
```

dengan:

```markdown
| `personal_targets` | `user_id`, `metric` (`GAIN`, `DISTANCE`, `HIKES`), `monthly_value`; unik per pendaki per ukuran; RLS di PostgreSQL | `create_personal_targets_table` (rintisan), `complete_personal_targets_table` |
```

3. Ganti:

```markdown
| `hiking_history` (sumber manual) | belum ada kolom; migrasi kosong | `add_source_and_recorded_values_to_hiking_history_table` |
```

dengan:

```markdown
| `hiking_history` (sumber manual) | `source` (`TRIP`, `MANUAL`), `trail_name` untuk jalur di luar katalog; `trip_plan_id` dan `trail_id` boleh kosong untuk catatan manual, dijaga CHECK di PostgreSQL | `add_source_to_hiking_history_table`; rintisan `add_source_and_recorded_values_to_hiking_history_table` tetap kosong |
```

4. Ganti:

```markdown
| Grafik kurang dari dua titik | Tidak digambar, dijelaskan | Ada: penjaga `titik.length < 2` di `resources/js/components/community/GrafikTanjakan.tsx` |
```

dengan:

```markdown
| Grafik kurang dari dua titik | Tidak digambar, dijelaskan | Ada: satu pendakian ditulis sebagai kalimat, bukan grafik (`app/Services/Community/HubProgres.php`, `resources/js/pages/Community/HikerProgress.tsx`) |
```

5. Ganti:

```markdown
`app/Services/HikerProgressService.php`, `app/Services/ProgressLadderService.php`, `GrafikTanjakan.tsx`, `tests/Feature/HikerProgressTest.php`,
```

dengan:

```markdown
`app/Services/HikerProgressService.php`, `app/Services/Community/HubProgres.php`, `app/Services/ProgressLadderService.php`, `resources/js/components/progres/GrafikPendakian.tsx`, `tests/Feature/HikerProgressTest.php`, `tests/Feature/ProgressHierarchyTest.php`,
```

6. Ganti:

```markdown
| Target pribadi dan catat manual (FR-25) | Belum ada | Migrasi rintisan kosong (lihat Data dan sumber); `tests/Feature/PersonalTargetTest.php`, `OwnedEquipmentTest.php`, `ManualHikeRecordTest.php` hanya membuka `/` dan tidak menguji fitur (W7) |
```

dengan:

```markdown
| Target pribadi dan catat manual (FR-25) | Ada (W7) | `app/Http/Controllers/Community/PersonalTargetController.php`, `app/Http/Controllers/Community/ManualHikeController.php`, `app/Policies/HikingHistoryPolicy.php`, `resources/js/components/progres/TargetPribadi.tsx`, `resources/js/components/progres/FormulirCatatan.tsx`, `tests/Feature/PersonalTargetTest.php`, `tests/Feature/ManualHikeRecordTest.php`, `tests/Feature/PersonalDataWriteGuardTest.php` |
| Pencapaian dari riwayat sendiri | Ada (W7) | `app/Services/Community/PencapaianPribadi.php`, `resources/js/components/progres/Pencapaian.tsx`, `tests/Feature/PersonalAchievementTest.php` |
```

7. Ganti:

```markdown
| Ada untuk trip; catat manual belum (FR-25) |
```

dengan:

```markdown
| Ada untuk trip; catat manual lewat "Catat pendakian di luar trip" di Progres (W7) |
```

8. Ganti:

```markdown
| Sebagian: ubah dan hapus catatan manual menunggu FR-25 |
```

dengan:

```markdown
| Ada: catatan manual diubah dan dihapus dari lembar di riwayat Progres (W7) |
```

9. Ganti:

```markdown
| `create_hiking_history_table`; kolom sumber manual belum ada | Sebagian |
```

dengan:

```markdown
| `create_hiking_history_table`, `add_source_to_hiking_history_table` | Ada |
```

10. Ganti:

```markdown
| 14 | Buat endpoint CRUD catatan pendakian | Tidak ada | Belum ada (FR-25) |
```

dengan:

```markdown
| 14 | Buat endpoint CRUD catatan pendakian | Rute `progress.records.store`, `progress.records.update`, `progress.records.destroy` | Ada (W7) |
```

11. Ganti:

```markdown
- **K13** target bulanan sebagai target pribadi terhadap diri sendiri; **K14** catat pendakian manual tidak menaikkan pengalaman.
```

dengan:

```markdown
- **K13** target bulanan sebagai target pribadi terhadap diri sendiri; **K14** catat pendakian manual tidak menaikkan pengalaman.
- **Ruling W7:** W7-R3 riwayat dua sumber; W7-R4 angka satu pendakian dari satu aturan; W7-R5 grafik sebagai baris mendatar; W7-R7 target sebagai satu set.
```

`docs/fitur/07-akun-masuk.md`:

1. Ganti:

```markdown
**Diperiksa terhadap kode:** 26 September 2026, setelah gelombang W0. Status adalah FAKTA dari berkas yang disebut (R-023).
```

dengan:

```markdown
**Diperiksa terhadap kode:** 8 Oktober 2026 untuk baris yang disentuh W7; selebihnya 26 September 2026, setelah gelombang W0. Status adalah FAKTA dari berkas yang disebut (R-023).
```

2. Ganti:

```markdown
| `profiles` | `experience_level`, `region_preference`, `bio`, `avatar_path`, `completed_at` | `create_profiles_table` |
```

dengan:

```markdown
| `profiles` | `experience_level`, `region_preference`, `bio`, `avatar_path` (foto profil W7; berkasnya di disk `avatars_disk`), `completed_at` | `create_profiles_table` |
```

3. Ganti:

```markdown
| `user_preferences` | `preferred_duration`, `preferred_trip_type`, `preferred_challenge`, `max_elevation_gain_preference_m`, `region_preference`, `record_track` | `create_user_preferences_table`, `add_track_recording_preference` |
```

dengan:

```markdown
| `user_preferences` | `preferred_duration`, `preferred_trip_type`, `preferred_challenge`, `max_elevation_gain_preference_m`, `region_preference`, `interest_regions` (DK-42), `record_track` | `create_user_preferences_table`, `add_track_recording_preference`, `add_interest_regions_to_user_preferences_table` |
| `user_owned_items` | `user_id`, `name`; unik per pendaki tanpa memandang huruf besar; RLS di PostgreSQL | `create_user_owned_items_table` (rintisan), `complete_user_owned_items_table` |
```

4. Ganti:

```markdown
`resources/js/components/akun/`, `tests/Feature/ProfileTest.php`; onboarding tiga langkah dengan turunan medan dan navigasi menyusul W7 (DK-41) |
```

dengan:

```markdown
`resources/js/components/akun/`, `resources/js/components/profil/`, `resources/js/components/onboarding/`, `app/Services/Profil/HalamanProfil.php`, `app/Services/Profil/TurunanPengalaman.php`, `tests/Feature/ProfileTest.php`, `tests/Feature/OnboardingDerivationTest.php`, `tests/Feature/InterestRegionTest.php`; onboarding tiga langkah dengan turunan medan dan navigasi (W7, DK-41) |
| Foto profil | Ada (W7) | `app/Http/Controllers/Profile/ProfilePhotoController.php`, `app/Services/Profil/FotoProfil.php`, `app/Http/Requests/Profile/UpdateProfilePhotoRequest.php`, `tests/Feature/ProfilePhotoTest.php` |
| Perlengkapan pribadi | Ada (W7) | `app/Http/Controllers/Profile/OwnedItemController.php`, `app/Policies/UserOwnedItemPolicy.php`, `resources/js/components/profil/PerlengkapanPribadi.tsx`, `tests/Feature/OwnedEquipmentTest.php` |
| Hapus semua jejak | Ada (W7) | `app/Http/Controllers/Profile/TrackDataController.php`, `resources/js/components/profil/DataLokasi.tsx`, `tests/Feature/PersonalDataDeletionTest.php` |
```

5. Ganti:

```markdown
**DK-41** onboarding menurunkan pengalaman medan dan navigasi (W7); **DK-42** wilayah minat di preferensi (W7).
```

dengan:

```markdown
**DK-41** onboarding menurunkan pengalaman medan dan navigasi (W7); **DK-42** wilayah minat di preferensi (W7).
- **Ruling W7:** W7-R8 perlengkapan pribadi mengisi awal persiapan; onboarding menyimpan satu jenis trip (enum Route Fit) walau prototype 00 memungkinkan beberapa; tautan kebijakan data pribadi di Profil menunggu halaman publiknya.
```

- [ ] **Step 4: Jalankan test penjaga dokumen lagi.**

Run: `php artisan test --compact --filter='PrdReferenceIntegrity|DesignDirection|TeamDocsIndex|RepoHygiene'`
Expected: PASS. Pada simulasi rencana: 31 test, 31 lulus.

Run: `LC_ALL=C grep -nP '\xE2\x80\x94' DESIGN.md docs/ARCHITECTURE.md docs/fitur/04-perjalanan-persiapan.md docs/fitur/06-pendakian-progres.md docs/fitur/07-akun-masuk.md`
Expected: tanpa keluaran (tanpa em dash).

- [ ] **Step 5: Suite.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 2.130 test, 2.128 lulus, 2 dilewati (sebelum tugas: 2.130).

- [ ] **Step 6: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 13: Verifikasi gelombang (koordinator)

Induk W7 "Selesai bila" (aturan penutup gelombang); ruling W7-R1 sampai W7-R21. Dikerjakan koordinator sesudah Tugas 1
sampai 12 selesai dan lolos tinjauan per tugas. Setiap klaim di laporan disertai keluaran perintah, jalur berkas, atau
tangkapan (R-023). Angka "pada simulasi rencana" adalah hasil menjalankan kode rencana ini di salinan keadaan akhir
simulasi W6, bukan hasil di repo; selisih dari W5, W6, dan perbaikan akhir W4 di repo nyata ditambahkan koordinator.
Harness pratinjau memakai port 8100 milik koordinator.

**Files:**
- Create: `docs/desain/tangkapan/w7/*.webp` (tangkapan), `docs/desain/tangkapan/w7/README.md` (perbandingan dengan acuan)
- Modify: `HANDOVER.md` (bagian 0: baris "Terakhir diperbarui" dan subbagian W7), `.superpowers/sdd/2026-09-26-memount-v3-program.md` (baris W7; berkas ini diabaikan git)

**Interfaces:**
- Consumes: seluruh keluaran Tugas 1 sampai 12; harness pratinjau (diperluas Tugas 2, 3, 6, 7, 10, dan 11); gambar acuan `docs/desain/prototype/acuan/00-onboarding.png`, `13-progres.png`, dan `14-profil.png`.
- Produces: bukti akhir W7, daftar izin akhir yang diperbarui (enam migrasi W7), dan titik lanjut W8.

- [ ] **Step 1: Catat keadaan kerja.**

Run: `git status --short | wc -l; git diff --stat | tail -1`
Expected: selisih terhadap catatan akhir W6 cocok dengan gabungan daftar Files Tugas 1 sampai 12. Pada simulasi rencana, Tugas 1 sampai 12 menambah 63 berkas, mengubah 53, dan menghapus 3 (`GrafikTanjakan.tsx`, `RingkasanProfilPendakian.tsx`, `components/akun/types.ts`) terhadap keadaan akhir simulasi W6.

Run: `ls database/migrations | grep -c '^2026_10_08_'` dan `git hash-object database/migrations/2026_09_25_000919_*.php database/migrations/2026_09_25_000920_*.php database/migrations/2026_09_25_000921_*.php`
Expected: 6 migrasi `2026_10_08_000100` sampai `000600` (ditambah migrasi W5 dan W6 bila ada yang bertanggal sama), dan
ketiga blob rintisan sama dengan yang dicatat koordinator sebelum Tugas 1 (rintisan belum dilacak git, jadi `git status`
tidak dapat membuktikannya).

- [ ] **Step 2: Suite penuh, tipe, format, dan paritas PostgreSQL.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 2.130 test, 2.128 lulus, 2 dilewati; di repo nyata angkanya bergeser oleh test perbaikan akhir W4 dan perbaikan putaran W5 dan W6 yang tidak ada di dasar simulasi.

Run: `php artisan test --compact`
Expected: 0 gagal. Pada simulasi rencana: 2.175 test, 2.128 lulus, 47 dilewati (45 test Spatial tanpa `SPATIAL_TEST_DSN` dan 2 yang sudah dilewati di dasar).

Run: `npx tsc --noEmit`
Expected: keluar tanpa keluaran galat.

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Paritas PostgreSQL (R-024): bila `SPATIAL_TEST_DSN` menunjuk PostGIS lokal (`docker-compose.yml`, DK-55; tidak pernah
Supabase), jalankan `php artisan test --compact --testsuite=Spatial` dan catat hasil kelima test Spatial W7
(`HikingHistorySourceSchemaTest`, `PersonalTargetSchemaTest`, `UserOwnedItemSchemaTest`,
`PreparationItemNoteSchemaTest`, `InterestRegionsSchemaTest`). Tanpa itu, catat "belum dijalankan; menunggu CI PostGIS
sesudah push yang diizinkan".

- [ ] **Step 3: Build dan ukuran bundel.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`
Expected: `✓ built`. Pada simulasi rencana: entri `app` 10,75 kB (gzip 3,22; dasar 10,70 dan 3,18), potongan bersama `dist` 346,41 kB tidak berubah, `shell` 4,64 kB tidak berubah; CSS 84,47 kB (gzip 16,04; dasar 72,47 dan 14,22). Potongan halaman: Progres 21,33 kB (gzip 5,88; dasar 6,02 dan 1,89), Profil 21,37 kB (gzip 6,22; dasar 10,34 dan 3,19), onboarding 16,05 kB (gzip 4,90; dasar 7,62 dan 2,57), Persiapan 10,07 kB (gzip 3,79; dasar 7,55 dan 2,95). Peringatan ukuran potongan maplibre-gl sama seperti W6.

- [ ] **Step 4: Suite pratinjau dan server statis.**

Run: `php artisan test --compact --testsuite=Pratinjau`
Expected: PASS. Pada simulasi rencana: 36 test lulus (dasar 29), termasuk halaman `progres`, `progres-kosong`, `profil`, `profil-kosong`, `onboarding`, `onboarding-pengalaman`, `onboarding-rencana`, dan `onboarding-selesai`.

Run (di latar belakang): `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php -S 127.0.0.1:8100 -t public tests/Pratinjau/statis.php`
Expected: server statis tanpa basis data. Buka `http://127.0.0.1:8100/_pratinjau/<nama>.html`.

- [ ] **Step 5: Tangkapan.** Chrome lewat MCP chrome-devtools. 390 x 844 dengan emulasi seluler, sentuh, rasio
piksel 1; 1440 x 900 rasio 1; terang dan gelap. Simpan WebP di `docs/desain/tangkapan/w7/`:

1. `progres` (acuan 13): 390 dan 1440, terang dan gelap; ditambah 390 terang dengan pilihan "Jarak" diketuk.
2. `profil` (acuan 14): 390 dan 1440, terang dan gelap; ditambah 390 terang dengan lembar perlengkapan terbuka (ketuk
   chip "Headlamp").
3. `onboarding` (acuan 00): langkah 1 (`onboarding`), langkah 2 (`onboarding-pengalaman`, isian sendiri dari profil Budi),
   langkah 3 (`onboarding-rencana`), dan hasil (`onboarding-selesai`), 390 terang; langkah 2 di 390 gelap dan 1440 terang
   dan gelap. Hasil pencarian jalur katalog tidak dapat dipotret di pratinjau statis (muat ulang parsial butuh server).
4. `persiapan` dengan catatan "Dibawa Dimas": 390 terang dan gelap.

- [ ] **Step 6: Perbandingan tertulis.** Tulis `docs/desain/tangkapan/w7/README.md` dengan susunan README tangkapan W6:
paragraf cara pengambilan, satu paragraf cakupan W7, dan tabel
`| Tangkapan | Gambar acuan | Yang sudah sesuai | Yang belum sesuai dan gelombang yang mengubahnya |`. Pemeriksaan
perencanaan menemukan hal-hal di bawah ini. Pastikan masing-masing pada tangkapan, jangan disalin tanpa melihat.

- **Sudah sesuai (progres 390 px, acuan 13).** Kepala "Progres" dengan kalimat "Dibandingkan dengan diri Anda sendiri"
  dan lencana Data contoh; pilihan tahun; ringkasan tiga petak (pendakian dengan rincian selesai dan batal, tanjakan,
  jarak) dengan sumber angkanya; grafik per pendakian dengan nilai tertulis, batal bergaris putus-putus dengan kata
  "batal", dan kalimat perbandingan diri sendiri; target "Tanjakan per bulan" dan strip dua belas bulan dengan
  "tercapai" bercentang; pencapaian "Tanjakan pertama di atas 500 m"; riwayat dengan "Dibatalkan · Cuaca", "Dicatat
  manual", dan "Catat pendakian di luar trip" bergaris putus-putus.
- **Sudah sesuai (profil 390 px, acuan 14).** Identitas dengan inisial, "Pemula, diisi sendiri", tanggal bergabung, dan
  "Pasang foto"; pengalaman dan preferensi dengan "Ubah" dan kalimat "Ini masukan, bukan sertifikasi"; perlengkapan
  pribadi sebagai chip dengan "Tambah barang"; tampilan tanpa bahasa, dengan dua sakelar; lokasi dan jejak dengan "Hapus
  semua jejak saya"; Keluar terpisah dari aksi lain.
- **Sudah sesuai (onboarding, acuan 00).** "Langkah N dari 3", kartu radio berketerangan, isian sendiri dengan chip medan
  dan kartu navigasi, "Cari di katalog" dan "Saya belum pernah mendaki", bar aksi "Kembali" dan "Lanjut", "Nanti saja" di
  kepala selama profil belum lengkap, dan hasil "Dengan jawaban ini, N jalur berlabel Cocok" dengan "Lihat N jalur Cocok"
  dan "Ke Profil".
- **Belum sesuai.**
  - Grafik baris mendatar, bukan batang tegak (Konflik 2); peta gunung dan dampak laporan tetap di bawah riwayat
    (Konflik 3).
  - Tautan "Kebijakan data pribadi" prototype 14 tidak dipasang (Konflik 6); pilihan bahasa disembunyikan (DK-40).
  - Pilihan jenis trip satu, bukan beberapa (Konflik 4).
  - Ikon lencana pencapaian dan ilustrasi prototype tidak ditambahkan; "Camping pertama di atas 2.000 m" tidak dibangun
    (W6-R13).

- [ ] **Step 7: Ukur reflow, target, dan kontras.** Dengan `evaluate_script` di pratinjau:

1. `progres`, `profil`, `onboarding` (setiap langkah), dan `persiapan` di 320 x 568, 390 x 844, dan 768 x 1024:
   `document.documentElement.scrollWidth` sama dengan `clientWidth`. Pada simulasi rencana: sama di kesembilan halaman (`progres`, `progres-kosong`, `profil`, `profil-kosong`, `onboarding`, `onboarding-pengalaman`, `onboarding-rencana`, `onboarding-selesai`, `persiapan`) di 320, 768, dan 1440 px, dan `progres` di 390 px (sebelum perbaikan tabel padanan: 502 lawan 390). Di Progres 320 px, 34 unsur yang melewati tepi kanan seluruhnya ada di dalam pembungkus `sr-only` tabel padanan, terpotong, dan tidak menggulirkan halaman.
2. Tinggi dan lebar kendali baru paling sedikit 44 px: pilihan tahun, Tanjakan dan Jarak, "Ubah target", chip
   perlengkapan, "Tambah barang", "Tambah catatan" dan "Ubah" catatan butir, kartu dan chip onboarding, "Pasang foto".
   Pada simulasi rencana: semua kendali di `main` ketujuh halaman paling sedikit 44 px, kecuali tiga kontrol bawaan MapLibre di peta gunung W2 (29 x 29 px; di atas 24 px WCAG 2.5.8, tidak disentuh W7). Contoh: Tanjakan dan Jarak 106 x 44, "Ubah target" 89 x 44, "Catat pendakian di luar trip" 358 x 48, kartu riwayat 358 x 98; "Pasang foto" 89 x 44, "Ubah" 52 x 44, chip "Headlamp" 103 x 44, "Tambah barang" 159 x 44, "Hapus semua jejak saya" 192 x 44; "Nanti saja" 82 x 44, kartu tingkat 358 x 92, "Lanjut" 333 x 48, chip medan 92 x 44, kartu jenis trip 358 x 72, chip wilayah 204 x 44; "Tambah catatan" 108 x 44 dan "Ubah" catatan 94 x 44.
3. Kontras teks nilai grafik, keterangan muted, dan batas chip terhadap permukaannya di kedua tema (4,5:1 untuk teks,
   3:1 untuk batas kendali).

- [ ] **Step 8: Jalur papan ketik** (e2e menunggu Playwright, Q17). Tanpa tetikus, 390 px:

1. **Onboarding.** Tab ke grup tingkat, panah memilih kartu dengan cincin fokus terlihat (dapat di pratinjau statis).
   Pindah langkah dan pencarian jalur butuh server lokal (izin Q1): Enter pada "Lanjut" memindah fokus ke judul langkah 2;
   cari "Gede", panah dan Enter memilih jalur; "Ubah nilai" memindah fokus ke kalimat pembuka isian sendiri; "Kembali"
   membuka langkah sebelumnya dengan jawaban yang tetap. Tanpa izin, tulis "MENUNGGU IZIN Q1" untuk bagian ini.
2. **Profil.** Chip "Headlamp" dengan Enter membuka lembar "Ubah barang" dengan fokus di "Tutup" (perilaku `showModal`
   di semua lembar), Tab berikutnya ke isian nama; Escape menutup dan fokus kembali ke chip; "Hapus dari daftar"
   mengembalikan fokus ke "Tambah barang" (butuh server; tanpa izin Q1 periksa dari kode `PerlengkapanPribadi.tsx`).
3. **Progres.** "Jarak" dengan panah atau Spasi mengganti grafik; "Ubah target" dan "Catat pendakian di luar trip"
   membuka lembar dengan fokus di "Tutup", Tab berikutnya ke isian pertama, dan Escape mengembalikan fokus ke pemicunya.

Simpan satu tangkapan fokus di kartu onboarding sebagai `fokus-onboarding-390.webp`. Hentikan server port 8100.

- [ ] **Step 9: Delivery Gate antislop, empat blok.** Tulis laporan PASS/FAIL, satu baris per butir, dan setiap PASS
disertai bukti:

- **Salinan:** R-02 tanpa em dash. Run: `LC_ALL=C grep -rlP '\xE2\x80\x94' app resources tests database config DESIGN.md docs/ARCHITECTURE.md docs/fitur`. Expected: tanpa keluaran. Teks berbahasa Indonesia dengan "Anda"; tanpa "aman", "sehat", "dijamin", "terjamin", atau "pasti" sebagai klaim; tanpa kata yang menghukum di Progres (`ProgressHierarchyTest`).
- **Desain:** satu aksi utama per bagian; hierarki lewat bobot; tanpa kartu di dalam kartu; komunitas bergaris putus-putus; status tidak lewat warna saja.
- **Interaksi:** R-26 setiap kendali berfungsi (daftar di tabel DK-57 baris antislop); R-27 keadaan kosong, memproses, dan galat di setiap bagian yang menulis; R-32 jalur papan ketik Step 8.
- **Kebenaran:** tanpa skor atau persen kecocokan maupun target; angka tidak diketahui ditulis "Belum ada"; onboarding yang dilewati tidak menyimpan apa pun; foto tanpa EXIF dan hanya untuk pemiliknya; R-35 build dijalankan dan kendali diklik.

- [ ] **Step 10: HANDOVER, ledger, dan daftar izin akhir.** Di `HANDOVER.md` bagian 0, perbarui baris "Terakhir
diperbarui" dan tambahkan subbagian "W7 Progres, Profil, dan onboarding (selesai <tanggal>, menunggu izin commit)"
dengan susunan subbagian W6:

- rujukan rencana ini, jumlah tugas, dan letak ledger;
- tabel `| Butir | Nilai | Bukti |` berisi suite Unit dan Feature, suite bawaan, Spatial (dijalankan atau menunggu CI),
  tsc, Pint, build, Pratinjau, tangkapan, reflow, target, dan jalur papan ketik;
- ruling koordinator atas Konflik 1 sampai 15;
- OPEN ISSUE dari Bukti simulasi;
- daftar izin akhir: enam migrasi W7 (`2026_10_08_000100` sampai `000600`, dipasang sesudah lima migrasi W6, satu batch
  urut nama berkas, didahului tiga kueri pemeriksaan di Konflik 1), disk foto produksi (`AVATARS_DISK`, Konflik 11), dan
  konfirmasi pemilik atas penangan unggahan foto dan kategori data pribadi barunya (Konflik 11);
- titik lanjut W8 (kurasi data jalur yang dibaca turunan onboarding; halaman publik kebijakan data pribadi, Konflik 6).

Perbarui baris W7 di `.superpowers/sdd/2026-09-26-memount-v3-program.md`.

- [ ] **Step 11: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

## Tinjauan diri

### Cakupan induk W7 dan titipan

| Butir | Tugas | Bukti di rencana |
|---|---|---|
| Butir 1: hub Progres (ringkasan tahun, grafik per pendakian Tanjakan atau Jarak, batal putus-putus, keterangan kalimat, riwayat dengan status laporan; `HikerProgressTest` dan `ProgressHierarchyTest` diperluas tanpa pembanding pendaki lain) | Tugas 2 | `HikerProgressTest` (12 test), `ProgressHierarchyTest` (6 test), `ReporterImpactTest`; pratinjau `progres` |
| Butir 2: target pribadi FR-25 (rute di bawah `/progres`, Form Request, pemilik saja, tanpa streak dan teguran; rintisan `PersonalTargetTest` diganti) | Tugas 3 | `PersonalTargetTest` (9 test), `PersonalTargetSchemaTest` (PostgreSQL) |
| Butir 3: catat pendakian di luar trip FR-25 (`source` manual, tanggal tidak di masa depan, rentang angka; rintisan `ManualHikeRecordTest` diganti) | Tugas 1, 2 | `ManualHikeRecordTest` (13 test), `HikingHistoryPageTest`, `HikingHistorySourceSchemaTest` (PostgreSQL); formulir di Progres Tugas 2 |
| Butir 4: perlengkapan pribadi (CRUD di Profil, isi awal persiapan yang tetap dikonfirmasi; rintisan `OwnedEquipmentTest` diganti; `PreparationServiceTest` untuk isi awal) | Tugas 5, 10 | `OwnedEquipmentTest` (6 test), `PreparationServiceTest` (2 test baru), `UserOwnedItemSchemaTest` (PostgreSQL); lembar di Profil Tugas 10 |
| Butir 5: hub Profil (avatar unggahan tanpa EXIF atau inisial, pengalaman, preferensi dengan wilayah minat DK-42, tampilan tanpa bahasa DK-40, privasi, pintu konsol, keluar; `ProfileTest` dan `InterestRegionTest`) | Tugas 7, 8, 9, 10 | `ProfilePhotoTest` (6 test), `PersonalDataDeletionTest` (2 test), `ProfileTest` (10 test, termasuk `test_the_profile_offers_no_control_that_does_not_work_yet`), `InterestRegionTest` (5 test), `PageHeaderContractTest` |
| Butir 6: onboarding DK-41 (turunan dengan konfirmasi, isian manual, "Belum pernah mendaki", "N jalur berlabel Cocok" dihitung server, "Nanti saja" dengan "Belum dinilai"; `OnboardingDerivationTest`) | Tugas 7 | `OnboardingDerivationTest` (10 test, termasuk `test_skipping_leaves_every_trail_unrated` dan `test_the_final_count_matches_jelajah`) |
| Butir 7: pencapaian turunan dari riwayat sendiri, tanpa kompetisi | Tugas 4 | `PersonalAchievementTest` (5 test, termasuk `test_achievements_never_use_other_hikers_data`) |
| Catatan skema: rintisan `000919` sampai `000921` diisi lewat migrasi baru, bukan disunting | Tugas 1, 3, 5 | Enam migrasi `2026_10_08_000100` sampai `000600`; Tugas 13 Step 1 memeriksa rintisan tidak berubah |
| Empat test rintisan menjadi test perilaku | Tugas 1, 3, 5, 9 | `ManualHikeRecordTest`, `PersonalTargetTest`, `OwnedEquipmentTest`, `PersonalDataWriteGuardTest` (5 test, sapuan sebelas rute tulis) |
| Titipan W4 "Ke W7": alasan pembatalan DK-50 | Tugas 2, 11 | Dibangun rencana W6; W7 menampilkannya di riwayat Progres ("Dibatalkan · Cuaca", `HikerProgressTest::test_a_cancelled_hike_counts_as_a_hike_but_not_as_a_climb`) dan data contoh Pangrango |
| Titipan W4 "Ke W7": catatan per butir persiapan | Tugas 6 | `PreparationNoteTest` (5 test), `PreparationItemNoteSchemaTest` (PostgreSQL); pratinjau `persiapan` |
| Titipan W4 "Ke W7": perlengkapan pribadi mengisi awal daftar persiapan | Tugas 5 | `OwnedEquipmentTest::test_owned_items_prefill_a_new_trip_preparation_list`, `test_changing_the_profile_list_leaves_existing_trip_lists_alone` |
| Selesai bila: aturan penutup gelombang | Tugas 13 | Step 1 sampai 10 |

### Pemindaian placeholder

Tidak ada "TBD", "TODO", "implement later", atau "similar to Task N". Setiap langkah kode memuat kodenya: berkas baru
ditulis utuh, tulis ulang memuat isi utuh, dan perubahan kecil memakai blok "Ganti ... dengan ...". Blok-blok itu
dirakit dari rantai simulasi dan diperiksa dua hal: teks lamanya muncul tepat sekali pada keadaan sebelum blok itu, dan
menerapkan semua blok berurutan menghasilkan berkas akhir tugas persis. Tidak ada blok yang diawali atau diakhiri baris
kosong. Sesudah dirakit, teks rencana ini diputar ulang dari awal pada salinan dasar seperti implementer membacanya, dan
setiap berkas sama dengan keadaan akhir tugasnya (Bukti simulasi).

### Konsistensi nama dan tipe

- **Riwayat (Tugas 1, 2, 4, 11):** `HikeRecordSource`, `HikingHistory::namaJalur()`, `manual()`, `HikingHistoryFactory::manual()`; rute `progress.records.*`; flash `kabar-progres` `{bagian, teks}`.
- **Progres (Tugas 2 sampai 4):** `HubProgres::untuk()` dan kuncinya (Interfaces Tugas 2), `HubProgres::angka()`, `CariJalur`, `PencapaianPribadi::TONGGAK`; TypeScript di `components/progres/types.ts` dengan nama yang sama dengan kunci server.
- **Target (Tugas 3, 11):** `PersonalTargetMetric` (`GAIN`, `DISTANCE`, `HIKES`; isian `tanjakan_m`, `jarak_km`, `pendakian`), `User::personalTargets()`, rute `progress.targets.update`.
- **Perlengkapan dan persiapan (Tugas 5, 6, 10, 11):** `UserOwnedItem::BATAS`, `User::ownedItems()`, `IsiAwalPerlengkapan::KETERANGAN`, `CatatanButir::BATAS`, `KATEGORI`, `untukHalaman()`; prop `catatanButir`; rute `profile.items.*` dan `trips.preparation.note`.
- **Onboarding (Tugas 7, 10):** `InterestRegion::dari()`, `TurunanPengalaman::dariJalur()`, `StoreProfileSetupRequest::KATALOG`, `MANUAL`, `BELUM`; `GET onboarding?langkah=`; komponen `PilihanKartu`, `PengalamanTerberat`, `PemilihJalur`.
- **Profil (Tugas 8 sampai 10):** `FotoProfil` (`MAP`, `sah()`, `url()`, `disk()`), `auth.user.foto`, `HalamanProfil::untuk()`, flash `kabar-profil` `{bagian: 'foto' | 'perlengkapan' | 'privasi', teks}`, rute `profile.photo.*` dan `profile.tracks.destroy`.

tsc lulus sesudah setiap tugas pada simulasi dan putaran ulang.

### Review Focus

Keenam baris Review Focus punya test di tugas pemilik kodenya: data pribadi orang lain (Tugas 1, 3, 5, 6, 9), foto
(Tugas 8, termasuk jalur berkas yang tidak ditulis layanan foto), angka yang menghukum atau mengarang (Tugas 2 sampai 4),
onboarding yang menebak (Tugas 7), daftar persiapan yang berubah diam-diam (Tugas 5), dan skema PostgreSQL (lima test
Spatial). Semua test itu lulus pada simulasi dan putaran ulang, kecuali test Spatial yang menunggu PostGIS.

### Pemeriksaan teks rencana

- Rencana ini tanpa em dash. Tanda "§" hanya muncul saat mengutip ID PRD; dokumen lain dirujuk "bagian N".
- Teks antarmuka baru berbahasa Indonesia dengan sapaan "Anda".
- Tidak ada klaim "aman", "sehat", "dijamin", "terjamin", atau "pasti"; kata-kata itu hanya muncul di aturan rencana ini
  dan di catatan bahwa dua komentar kode yang memakainya diganti (Bukti simulasi).
- Label kecocokan publik dan Route Fit tidak disentuh; wilayah minat dan target tidak dibaca penilaian.

### Pemeriksaan test (testing-best-practices, `rules/review.md`)

- Test baru menguji perilaku lewat HTTP atau layanan publik dan memakai pabrik; `setUp` hanya menyiapkan konfigurasi
  (`Storage::fake`, seeder templat persiapan); waktu dibekukan dengan `travelTo()` bila bulan atau tanggal menentukan
  hasil (`PersonalTargetTest`, `ManualHikeRecordTest`, `HikerProgressTest`).
- Setiap rute tulis baru diuji untuk tamu, pengguna lain (403 dari Policy atau 404 lewat relasi, W7-R21), validasi
  beserta kalimat galatnya, dan batas laju dengan kunci sendiri (`PersonalDataWriteGuardTest` menyapu kesebelas rute).
- Test yang diperbarui membawa alasannya: `HikerProgressTest`, `ProgressHierarchyTest`, `ReporterImpactTest`,
  `HikingHistoryPageTest`, `PreparationServiceTest`, `EmptyStateCoverageTest`, `NavigationConsistencyTest`,
  `RecommendationFlowTest`, `PageHeaderContractTest`, `ProfileTest`, `NewPageQueryBudgetTest`, dan `DemoSeederTest`.
- Unggahan berbahaya: berkas palsu Laravel menebak tipe dari ekstensi, jadi skrip berekstensi `.jpg` ditolak aturan
  `dimensions` di test; penolakan oleh aturan `image` untuk unggahan sungguhan dibuktikan probe simulasi (Konflik 11).
- Test sesudah menyimpan profil memakai `$user->fresh()`, karena prop bersama Inertia memuat relasi pada instans
  pengguna yang sama di dalam test.

### Paritas PostgreSQL (R-024)

Suite di rencana ini memakai SQLite dalam memori, jadi hijaunya tidak membuktikan perilaku PostgreSQL. W7 menambah enam
migrasi aditif, dua CHECK, satu indeks biasa, satu indeks unik, satu indeks unik ekspresi, tiga kunci asing, dua RLS,
dan satu kolom JSON. Yang dijaga test Spatial (lewat `SPATIAL_TEST_DSN` di PostGIS CI atau lokal, tidak pernah Supabase):

- `HikingHistorySourceSchemaTest`: nilai bawaan `TRIP`, CHECK sumber dan jalur, indeks pengguna dan tanggal;
- `PersonalTargetSchemaTest`: kolom, unik per ukuran, cascade, dan RLS;
- `UserOwnedItemSchemaTest`: unik `lower(name)` (huruf besar berbeda tetap ditolak), cascade, `nullOnDelete` tautan butir, dan RLS;
- `PreparationItemNoteSchemaTest`: `varchar(80)` menolak catatan yang lebih panjang;
- `InterestRegionsSchemaTest`: kolom JSON menyimpan dan membaca larik kode.

Yang diperiksa dengan membaca kueri: pengurutan riwayat pada `completed_at` (tak-NULL) dan `planned_date`; pencarian
jalur katalog (`CariJalur`) memakai `TrailSearch` yang sudah ada, yang membandingkan `lower(kolom) like ?` dengan pola
huruf kecil dan escape `!`, jadi hasilnya sama di kedua pengemudi; jumlah per bulan target dihitung di PHP dari baris tahun berjalan (paling banyak 500 per jenis,
`HubProgres::BATAS_TAHUN`), bukan `GROUP BY` khas satu pengemudi. Rencana ini tidak menjalankan apa pun terhadap
PostgreSQL. Tanpa PostGIS lokal, laporan Tugas 13 mencatat paritas W7 sebagai "test Spatial tertulis, menunggu CI".

## Lampiran A: Aturan eksekusi W7

Salin lampiran ini ke `.superpowers/sdd/<folder ledger W7>/aturan-eksekusi.md` sebelum Tugas 1. Isinya aturan
eksekusi W6 dengan nama gelombang, rencana, port, dan larangan baru W7.

### Aturan eksekusi W7 (dibaca setiap implementer sebelum mulai)

Proyek: Memount, Laravel 13.31 + Inertia 3 + React 19.3 + TypeScript 5.9, PHP 8.5.10, di Windows dengan Git Bash.
Root repo: `E:/praktik_aplikasi_web`. Branch `hardening`. Commit terakhir `ddc27e8`; pohon kerja memuat perubahan yang
belum di-commit dari W1 sampai W6 dan gelombang ini. Folder `.agents/skills/` yang belum dilacak (keputusan Q19)
jangan disentuh.

#### Larangan mutlak (arahan pemilik dan kontrak proyek)

1. **Jangan commit, push, `git stash`, `git checkout -- <berkas>`, `git reset`, atau `git clean`.** Pemilik
   memutuskan tidak ada commit sampai seluruh program W1 sampai W9 selesai. Lewati setiap langkah "Commit"; catat
   berkas yang berubah di laporan.
2. **Jangan menyentuh Supabase dan jangan membaca atau mencetak isi `.env`.** `.env` menunjuk basis data produksi.
3. **Jangan menjalankan `php artisan migrate` dalam bentuk apa pun, `db:seed`, `db:wipe`, tinker (termasuk
   `tinker --execute`), atau `php artisan memount:demo`.** Migrasi dan seeder hanya berjalan di dalam PHPUnit. Enam
   migrasi W7 ditulis dan diuji, tidak dijalankan. Migrasi rintisan `2026_09_25_000919`, `000920`, dan `000921` tidak
   disunting.
4. **Setiap perintah Artisan yang mem-boot aplikasi di luar PHPUnit, `npm run build`, dan server statis `php -S`
   wajib diberi awalan** `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL=`. `php artisan test ...` tidak butuh awalan.
5. **Jangan menambah atau mengubah dependensi** (composer, npm), jangan mengubah versi apa pun, dan jangan memasang
   pustaka gambar, Playwright, atau komponen shadcn. Pengolahan foto memakai GD dan exif yang sudah ada.
6. **Jangan mengubah `AVATARS_DISK` atau konfigurasi disk di `.env`.** Test memakai `Storage::fake`; disk produksi
   adalah keputusan pemilik (Konflik 11).
7. **Jangan menyunting berkas di luar daftar "Files" tugas Anda**, kecuali brief atau dispatch menyebutnya. Bila perlu
   menyentuh berkas lain, berhenti dan laporkan NEEDS_CONTEXT.
8. **Blok `<laravel-boost-guidelines>` di `AGENTS.md` dan `CLAUDE.md` serta blok NgodingPakeAI di `AGENTS.md` tidak
   pernah disunting.** `HANDOVER.md`, `CLAUDE.md`, `docs/PRD.md`, dan `docs/KEBIJAKAN-DATA-PRIBADI.md` tidak disunting
   implementer.
9. **Jangan menjalankan subagen.**
10. **Server `php artisan serve` di 127.0.0.1:8000 milik pemilik (memakai Supabase).** Jangan dipakai, dihentikan, atau
    dipanggil. Port 8100 milik koordinator (harness pratinjau Tugas 13), dan port 8160 milik penulis rencana.
    Implementer W7 tidak menyalakan server apa pun.

#### Hook GateGuard

Lingkungan ini memasang hook "Fact-Forcing Gate". Perintah Bash pertama dan suntingan pertama setiap berkas bisa
ditolak dengan permintaan fakta. Bila itu terjadi, tulis faktanya di balasan Anda lalu ulangi panggilan yang sama:
- ringkasan permintaan: "Menjalankan Tugas N rencana W7 (docs/superpowers/plans/2026-10-08-memount-v3-w7-progres-profil.md) atas arahan pemilik";
- untuk Bash: apa yang diverifikasi atau dihasilkan perintah itu;
- untuk suntingan: berkas yang mengimpor atau merujuk berkas itu (cari dengan Grep), fungsi atau kelas publik yang terdampak, bentuk data bila ada;
- untuk berkas baru: berkas yang akan memanggilnya, bukti tidak ada berkas lain dengan tujuan sama, dan bentuk data;
- untuk penghapusan atau pemindahan: jalur yang terdampak dan cara mengembalikannya;
- instruksi pemilik verbatim: "ya, selesaikan semuanya baru minta izin commit dan integrasi nya jika belum selesai semua jangan minta ke saya, jika anda meminta perizinan artinya semua tugas anda telah selesai. lanjutkan!"

#### Konvensi kerangka dari W2 sampai W6, ditambah W7

- Komponen yang hidup di kerangka persisten memanggil `useKerangka()` untuk toast. Toast tertutup saat pemiliknya
  dilepas.
- Konfirmasi sesudah navigasi datang dari flash halaman tujuan. Di W7, Progres membaca `kabar-progres` dan Profil
  membaca `kabar-profil`, masing-masing `{bagian, teks}`, dan hanya bagian yang namanya cocok yang menampilkannya.
- Kabar dan penolakan dibacakan lewat wilayah `role="status"` atau `role="alert"` yang terpasang sejak bagiannya tampil;
  hanya isi dan bingkainya yang bersyarat, dan wilayah kosong memakai `empty:!mt-0`, bukan `empty:hidden`.
- Sesudah sebuah kendali hilang karena aksinya sendiri (barang dihapus, catatan disimpan), fokus dipindah ke kendali
  terdekat yang tetap ada atau ke judul bagian; fokus tidak pernah jatuh ke `body`.
- Muat ulang penuh karena versi aset baru hanya lewat `muatPenuhVersiBaru()` atau `muatUlangMilikKerangka()` di
  `resources/js/layouts/Kerangka.tsx`. Jangan memanggil `window.location.reload()` sendiri.
- Primitif dan prop baru memakai nama Indonesia (`nilai`, `onUbah`, `label`).

#### Cara kerja

- Isi berkas dan kode di brief adalah isi yang harus ditulis, persis. Bila teks yang harus diganti tidak ditemukan
  persis, jangan menebak: laporkan NEEDS_CONTEXT dengan kutipan berkas nyata. Nomor baris di brief adalah petunjuk;
  yang mengikat adalah teksnya.
- Ikuti urutan langkah brief: test gagal dulu (catat keluaran merahnya), implementasi, build bila tugas menyentuh
  TypeScript atau rute, test lulus (catat keluaran hijaunya). Angka "pada simulasi rencana" di brief adalah acuan; bila
  angka nyata berbeda, laporkan apa adanya.
- Pint per berkas PHP tugas: `vendor/bin/pint --format agent <berkas>`. Setelah menyunting TypeScript atau rute: build
  lalu `npx tsc --noEmit`.
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
