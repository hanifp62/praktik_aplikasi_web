# Memount v3 W4 Klaster Trip Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Lingkaran rencanakan, persiapan, dan cek kesiapan berfungsi dengan rupa prototype 08 sampai 10, ditambah
indeks trip dan ruang kerja trip yang tidak ada di prototype, di atas kerangka W2 dan tata bahasa kepercayaan W3.
Setiap layar menyebut keadaan kesiapan dengan kata DK-48, menulis kecocokan sebagai label dan alasan tanpa skor, dan
menyebut siapa yang ditunggu ketika data belum ada (prakiraan BMKG, aturan izin, profil pendaki).

**Architecture:** Server memegang data dan penilaian. Satu kelas halaman per layar di `app/Services/Trips/`
(`IndeksTrip`, `RencanaTrip`, `HalamanPersiapan`, `HalamanKesiapan`, ditambah `KepalaTrip` dan `TripSteps` untuk
ruang kerja) merakit props yang sudah terformat dengan kueri tetap. Kecocokan untuk rencana di halaman Rencanakan
dihitung ulang server lewat muat ulang parsial Inertia (`only: kecocokan, jadwal, izin`), bukan di klien. React
memegang interaksi: centang persiapan optimis dengan Urungkan milik halaman (konvensi W2), baris langkah ringkas
bersama untuk halaman 08 sampai 10, dan primitif W1 (`KontrolSegmen`, `Stepper`, `Sakelar`, `KotakCentang`,
`BarAksi`). Tidak ada tabel, rute, migrasi, ikon, atau dependensi baru.

**Tech Stack:** PHP 8.5.10, Laravel 13.31.0, Inertia Laravel 3.3.4, `@inertiajs/react` 3.7.1, React 19.3.0,
TypeScript 5.9.3, Vite 8.3.0, Tailwind CSS 3.4.19, PHPUnit 12.5.35, Pint, SQLite dalam memori untuk test. Tidak ada
dependensi baru dan tidak ada migrasi.

**Spec:** `docs/superpowers/specs/2026-09-25-memount-v3-prototype-design.md` (bagian 5.1 sampai 5.3, 6.8 sampai 6.11,
7, 8.4 sampai 8.8, 9.1 sampai 9.3; DK-31, DK-32, DK-40, DK-43, DK-48), rencana induk
`docs/superpowers/plans/2026-09-26-memount-v3-induk.md` (Global Constraints, Review Focus, bagian W4 butir 1 sampai 4
dan "Selesai bila"), ledger W3 `.superpowers/sdd/2026-09-27-memount-v3-w3-jelajah/progress.md` (ruling W3-C2, W3-C6,
W3-4b, W3-6a, W3-6b, W3-8b, W3-S5), dan aturan eksekusi di Lampiran A rencana ini. Keadaan awalnya repo nyata sesudah
W3 Tugas 9 lolos tinjauan (tree `ac88231`); W3 Tugas 10, Tugas 11, Tugas 12, dan gelombang perbaikan akhir W3 selesai
sebelum Tugas 1 rencana ini dimulai (Pra-cek).

## Global Constraints

- Versi dikunci: PHP 8.5, Laravel 13.31, Inertia Laravel 3.3.4, React 19.3, `@inertiajs/react` 3.7.1, Tailwind 3.4, Vite 8, TypeScript 5.9, MapLibre 4.7.x. Tidak ada perubahan versi kerangka (R-020).
- Tidak ada dependensi baru, runtime maupun dev (spec bagian 15 Q3 dan Q17), dan tidak ada ikon baru: registri Phosphor (`resources/js/components/ui/ikon/phosphor.ts`) tidak disunting W4, karena path ikon harus disalin dari paket yang tidak terpasang. Yang dipakai hanya ikon yang sudah ada (`check`, `warning`, `caret-right`, `arrow-square-out`).
- Tidak ada commit, push, akses Supabase, atau migrasi di basis data mana pun. Setiap langkah "Commit" berbunyi: "Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah."
- W4 tidak menambah migrasi, tabel, kolom, atau rute. `php artisan migrate` dalam bentuk apa pun, `db:seed`, `db:wipe`, tinker (termasuk `tinker --execute`), dan `memount:demo` tidak dipakai; migrasi dan seeder hanya berjalan di dalam PHPUnit (SQLite dalam memori).
- Setiap perintah Artisan di luar PHPUnit, `npm run build`, dan `php -S` diberi awalan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL=` (Git Bash). Di PowerShell: `$env:DB_CONNECTION="sqlite"; $env:DB_DATABASE=":memory:"; $env:DB_URL="(empty)"; <perintah>`, lalu `Remove-Item Env:DB_CONNECTION, Env:DB_DATABASE, Env:DB_URL`. `php artisan test ...` tidak butuh awalan karena `phpunit.xml` memaksa koneksi uji. Jangan membaca atau mencetak `.env`.
- Port `127.0.0.1:8100` dicadangkan untuk harness pratinjau koordinator (Tugas 8). Tugas 1 sampai 7 tidak menyalakan server apa pun. Server `php artisan serve` pemilik di `127.0.0.1:8000` tidak pernah dipakai atau dihentikan. Port 8130 sampai 8149 milik penulis rencana.
- Bahasa Indonesia untuk teks antarmuka dan dokumen, sapaan "Anda", tanpa em dash (U+2014) di berkas mana pun, tanpa kata "aman" dan "layak berangkat" sebagai klaim (R-012, R-014). "§N" hanya untuk ID PRD; dokumen lain dirujuk "bagian N".
- Label kecocokan publik hanya "Cocok", "Perlu persiapan", dan "Kurang cocok"; UNKNOWN tampil "Belum dinilai" dari server (`RouteFitLabel::textFor`). Keadaan kesiapan publik hanya kata DK-48: "Persiapan kritis lengkap", "Masih perlu persiapan", "Tidak disarankan dengan rencana ini". Tidak ada skor, persen, atau bobot angka untuk kecocokan maupun kesiapan di props atau di layar (R-004, R-005, R-006, BR-09); persiapan ditulis sebagai hitungan ("5 dari 8").
- Kesiapan adalah dukungan keputusan, bukan izin medis, izin hukum, atau jaminan keselamatan (R-014). Halaman Cek kesiapan memuat kalimat "Ini dukungan keputusan, bukan izin medis, izin hukum, atau jaminan keselamatan." Status trip "Siap berangkat" tidak pernah tampil di kepala trip atau kartu indeks untuk trip yang belum dimulai.
- Setiap layar W4 yang merender status resmi (`StatusAkses`, `BarisStatus`, atau komponen yang memakainya) memuat kalimat "Status resmi menjelaskan ketentuan pengelola, bukan jaminan keselamatan." (R-012, ruling W3-S5): `Trips/Create.tsx` dan `Trips/Readiness.tsx`.
- Status akses selalu dengan sumber dan waktunya; tanpa catatan yang berlaku, statusnya Belum diketahui, bukan Buka. Prakiraan cuaca selalu bersumber, bertanggal, dan untuk tanggal trip; sebelum BMKG menerbitkannya (H-3, `hiking.conditions.weather_forecast_days`) halaman menyebut tanggal terbitnya dan tidak menebak cuaca (R-013).
- Data yang tidak ada tetap tidak diketahui: tanpa angka, kuota, jendela izin, atau prakiraan karangan (R-007). Kuota hanya disebut sebagai aturan tercatat, tidak pernah sebagai sisa kuota (§43); Memount menautkan sistem resmi dan tidak menjual izin.
- Token hanya di `resources/css/app.css`; tanpa warna mentah, `bg-white`, `text-black`, atau `dark:` di komponen. Kaca paling banyak dua elemen per layar dan tidak pernah di atas kartu atau status resmi.
- Target sentuh kendali utama ≥44 px; halaman W4 memakai `KotakCentang`, bukan `Checkbox` 28 px. Primitif baru atau prop baru pada primitif memakai nama Indonesia (`nilai`, `onUbah`, `label`, `penjelas`). Tombol tanpa panah hiasan (DK-39).
- Konvensi kerangka W2: `useKerangka()` dipanggil di halaman, bukan di baris; aksi dengan Urungkan di halaman yang sama memakai `preserveState: true` dan `preserveScroll: true`; konfirmasi sesudah pindah halaman datang dari flash halaman tujuan; dialog pengirim formulir menangani `onNetworkError`. Halaman tugas memakai `<AppLayout bilahTab={false}>` dan `BarAksi` yang menulis `--bar-aksi`.
- Nama props bersama `jumlahSimpanan`, `pendakianAktif`, dan `daftarPengingat` tidak dipakai ulang sebagai prop halaman (`ReminderSheetTest`).
- Setiap halaman baru atau yang ditulis ulang punya test anggaran kueri yang tidak tumbuh bersama data, dan setiap halaman punya test akses tamu dan pengguna lain (Policy `TripPlanPolicy`); id di badan permintaan tidak pernah dipercaya.
- Test yang menyandikan keputusan lama diperbarui menurut niat baru dengan alasan tertulis di docblock atau komentarnya; tidak dihapus dan tidak dilonggarkan diam-diam (spec bagian 14).
- Tugas dikerjakan berurutan, satu implementer satu waktu, dan hanya menyunting berkas di daftar Files tugasnya. Berkas bersama (`resources/js/components/trips/types.ts`, `app/Http/Controllers/Trips/TripController.php`, `tests/Pratinjau/HalamanPratinjauTest.php`, `tests/Feature/TripReactPagesTest.php`, `config/hiking.php`, `resources/js/components/ui/KontrolSegmen.tsx`, `resources/js/components/ui/Stepper.tsx`) hanya disunting tugas yang menyebutnya. `HANDOVER.md` dan `CLAUDE.md` hanya disunting koordinator di Tugas 8.
- Berkas baru dibuat seperti W2 dan W3: test dengan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:test --phpunit --no-interaction <Nama>` (test unit memakai `--unit`), kelas PHP dengan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:class --no-interaction <Jalur>`, lalu isinya diganti persis dengan kode rencana. Berkas TypeScript dibuat langsung. Setelah menyunting PHP jalankan `vendor/bin/pint --format agent <berkas>`; setelah menyunting TypeScript jalankan `npx tsc --noEmit`. W4 tidak mengubah rute, jadi `wayfinder:generate` tidak perlu dijalankan ulang.
- Blok "Ganti ... dengan ..." diterapkan berurutan dengan alat Edit. Teks lamanya unik di berkasnya pada keadaan W3 selesai ditambah tugas-tugas sebelumnya, dan sudah diperiksa pada simulasi. Bila teks lama tidak ditemukan persis, berhenti dan laporkan NEEDS_CONTEXT; jangan mengarang padanannya. "Tulis ulang ... utuh" berarti isi berkas diganti seluruhnya dengan blok kodenya.

## Review Focus

- **Objek milik pengguna lain dan id di badan permintaan.** Trip, butir persiapan, pengingat, dan cek kesiapan milik
  pendaki lain ditolak di server; indeks tidak pernah memuat trip orang lain; trip, kritis, label, dan kategori yang
  diselipkan di badan permintaan butir persiapan tidak pernah ditulis. Dijaga Tugas 1
  `TripWorkspaceTest::test_a_guest_is_sent_to_login_and_another_hiker_is_refused`, Tugas 2
  `TripIndexPageTest::test_only_the_readers_own_trips_appear`, Tugas 4
  `PreparationUndoTest::test_another_hikers_item_is_refused_and_the_body_is_not_trusted`, dan test yang sudah ada
  `TripOwnershipTest::test_a_preparation_item_is_only_found_within_its_own_trip` serta
  `ReadinessDashboardTest::test_another_user_cannot_recompute_or_confirm`.
- **Kesiapan yang terbaca jaminan atau skor.** "Siap berangkat" di kepala atau kartu trip, kata "Siap" atau
  "Tidak direkomendasikan", persen persiapan, atau skor kecocokan di props. Dijaga Tugas 1
  `TripWorkspaceTest::test_an_upcoming_trip_never_reads_as_ready_to_go`, Tugas 2
  `TripIndexPageTest::test_no_status_reads_as_ready_and_no_score_reaches_the_index`, Tugas 3
  `TripPlanFitReloadTest::test_the_fit_for_the_plan_carries_only_a_label_and_its_reason`, Tugas 5
  `ReadinessDashboardTest::test_the_readiness_states_speak_in_the_dk48_words`, dan Tugas 6
  `ReadinessDashboardTest::test_the_page_shows_the_three_checks_with_their_next_actions`.
- **Data yang belum ada.** Prakiraan BMKG yang belum terbit atau gagal diambil, aturan izin tanpa jendela atau kuota,
  pendaki tanpa profil, pendaki tanpa simpanan atau tanpa trip. Halaman menyebut siapa yang ditunggu, tidak pernah
  mengarang. Dijaga Tugas 3 `TripPlanFitReloadTest::test_the_schedule_names_the_range_and_when_the_bmkg_forecast_appears`
  dan `test_a_hiker_without_a_profile_is_not_rated`, `TripPermitWindowTest::test_the_plan_page_never_invents_a_quota_or_a_window`,
  Tugas 4 `PreparationUndoTest::test_a_weather_item_waits_for_bmkg_and_cannot_be_confirmed_early`, Tugas 6
  `ReadinessDashboardTest::test_the_forecast_is_for_the_trip_date_and_never_invented`, dan Tugas 2
  `TripIndexPageTest::test_an_empty_index_offers_one_way_to_start`.
- **Urungkan, ketuk beruntun, dan keadaan yang berubah sejak terakhir dilihat.** Urungkan selalu mengirim status
  sebelumnya; vonis lama tidak disajikan sebagai keadaan sekarang; perubahan sejak cek terakhir disebut, termasuk yang
  tidak berubah. Dijaga Tugas 4 `PreparationUndoTest::test_undo_restores_the_previous_status` dan
  `test_the_page_ticks_optimistically_and_offers_undo`, Tugas 6
  `ReadinessSinceLastCheckTest::test_a_status_change_since_the_saved_check_is_named`, dan
  `StaleReadinessTest::test_the_trip_list_stops_presenting_a_superseded_verdict` yang diperbarui Tugas 2.
- **Masukan URL dan isian yang tidak sah, serta zona waktu.** Tanggal lampau atau salah bentuk di URL Rencanakan,
  jalur draf atau arsip, peserta di luar 1 sampai 12, jam mulai di gunung WITA atau WIT. Dijaga Tugas 3
  `TripPlanFitReloadTest::test_a_date_from_the_url_is_only_used_when_it_is_valid` dan
  `test_an_unpublished_or_archived_trail_is_not_offered_for_planning`,
  `TripPlanDetailTest::test_the_number_of_participants_stays_within_one_and_twelve`, dan Tugas 1
  `TripWorkspaceTest::test_the_header_names_the_type_days_left_and_the_plan_in_the_mountain_zone`.

---

## Bukti simulasi rencana

Kode di rencana ini dijalankan tugas demi tugas di salinan repo di scratchpad sesi perencanaan (`w4sim`). Salinan itu
dibuat koordinator dengan `git archive` dari tree `ac88231` (pohon kerja repo nyata persis sesudah W3 Tugas 9 lolos
tinjauan; 1.292 berkas), tanpa `.env` repo nyata. Penulis melengkapinya tanpa menulis ke repo nyata: `vendor/` dan
`node_modules/` berupa direktori nyata di salinan yang tiap entrinya junction ke repo nyata, kecuali
`vendor/composer`, `vendor/autoload.php`, `vendor/bin`, `vendor/phpunit/phpunit`, dan `node_modules/.bin` yang
disalin (PHP me-resolve junction pada `__DIR__`, jadi autoload yang ditautkan akan memuat `app/` repo nyata), `.env`
dari `.env.example` dengan APP_KEY sementara, keluaran Wayfinder, `.github/` disalin, `git init` tanpa add dan tanpa
commit (beberapa test membaca indeks git), dan konstanta `ASAL` harness pratinjau diarahkan ke port 8130. Setiap tugas
diuji merah dengan test tugas itu terhadap kode tugas sebelumnya, lalu hijau, lalu suite Unit dan Feature penuh,
`npx tsc --noEmit`, Pint, build, dan suite Pratinjau. Sesudah dirakit, teks rencana ini diputar ulang dari salinan
dasar yang bersih (salinan `w4sim` yang diambil sebelum disentuh) dengan pengurai yang hanya membaca blok "Buat",
"Tulis ulang", "Ganti", dan "Hapus berkas" di rencana ini; angka di tabel adalah hasil putaran ulang itu.

| Tugas | Merah (filter tugas, sebelum implementasi) | Hijau (filter yang sama) | Suite Unit dan Feature | Pratinjau | Pohon sesudah tugas |
|---|---|---|---|---|---|
| Dasar `ac88231` | | | 1.764 test, 1.762 lulus, 2 dilewati | 14 lulus | sama dengan salinan simulasi sebelum Tugas 1 |
| 1 Ruang kerja | 94 test: 84 lulus, 5 gagal, 5 error | 94 lulus | 1.773 test, 1.771 lulus, 2 dilewati | 15 lulus | identik dengan simulasi |
| 2 Indeks | 48 test: 39 lulus, 3 gagal, 6 error | 48 lulus | 1.782 test, 1.780 lulus, 2 dilewati | 15 lulus | identik |
| 3 Rencanakan | 130 test: 113 lulus, 9 gagal, 8 error | 130 lulus | 1.796 test, 1.794 lulus, 2 dilewati | 16 lulus | identik |
| 4 Persiapan | 55 test: 49 lulus, 2 gagal, 4 error | 55 lulus | 1.805 test, 1.803 lulus, 2 dilewati | 17 lulus | identik |
| 5 Kata DK-48 | 47 test: 45 lulus, 2 gagal | 47 lulus | 1.806 test, 1.804 lulus, 2 dilewati | tidak dijalankan (tanpa perubahan layar) | identik |
| 6 Cek kesiapan | 66 test: 52 lulus, 7 gagal, 7 error | 66 lulus | 1.811 test, 1.809 lulus, 2 dilewati | 18 lulus | identik |
| 7 Dokumen | tidak ada test baru (35 test dokumen lulus sebelum dan sesudah) | 35 lulus | 1.811 test, 1.809 lulus, 2 dilewati | tidak dijalankan | identik |

Sesudah setiap tugas `npx tsc --noEmit` keluar tanpa galat dan `vendor/bin/pint --test --format agent` lulus; `npm run build` lulus sesudah Tugas 1, 2, 3, 4, dan 6.

Angka di atas memakai SQLite dalam memori. Suite bawaan `php artisan test --compact` (Unit, Feature, dan Spatial menurut `defaultTestSuite`) sesudah Tugas 7: 1.838 test, 1.809 lulus, 29 dilewati (27 test Spatial melewati dirinya tanpa `SPATIAL_TEST_DSN`, ditambah 2 test yang sudah dilewati di dasar). Tidak ada test Spatial baru: W4 tidak menyentuh
PostGIS, migrasi, atau kueri spasial. `vendor/bin/pint --test` lulus untuk seluruh pohon.

**Putaran ulang.** Dua kali, keduanya dari salinan bersih dasar yang sama dan hanya dengan pengurai yang membaca blok rencana. Salinan dasar sama berkas demi berkas dengan salinan simulasi sebelum Tugas 1 (0 berkas berbeda); semua salinan memakai `ASAL` port 8130 di `tests/Pratinjau/PratinjauTestCase.php`, berkas yang tidak disentuh rencana.

1. Putaran pertama (`w4putar`) menjalankan langkah merah dan hijau setiap tugas; angka di tabel berasal dari putaran ini, dan pohon sesudah setiap tugas identik dengan potret simulasinya.
2. Sesudah itu, lima blok "Ganti" dipindah jangkarnya karena W3 Tugas 10, 11, dan gelombang perbaikan akhir mengubah teks di sekitar jangkar lama di repo nyata (empat di `tests/Pratinjau/HalamanPratinjauTest.php`, satu di `docs/ARCHITECTURE.md`). Isi berkas hasilnya tidak berubah.
3. Putaran kedua (`w4putar2`) memutar teks rencana final ini: 107 operasi (14 "Buat", 9 "Tulis ulang ... utuh", 83 blok "Ganti", dan 1 "Hapus berkas"), dan setiap teks lama blok "Ganti" ditemukan tepat sekali saat diterapkan. Pohon sumber sesudah setiap tugas sama berkas demi berkas dengan potret simulasi tugas itu: 0 berkas baru, diubah, atau hilang untuk ketujuh tugas. Perbandingan itu tidak memuat `vendor`, `node_modules`, `.git`, `.env`, `storage`, `bootstrap/cache`, `public/build`, `public/_pratinjau`, keluaran Wayfinder, dan `docs/desain/prototype`.
4. Akhir putaran kedua: Unit dan Feature 1.811 test (1.809 lulus, 2 dilewati), suite bawaan 1.838 test (1.809 lulus, 29 dilewati), `npx tsc --noEmit` tanpa galat, Pint lulus, build lulus, dan Pratinjau 18 lulus. Pohon akhirnya sama dengan pohon akhir putaran pertama. Catatan urutan: pada salinan yang belum pernah di-build, `npm run build` (yang juga membangkitkan rute Wayfinder) harus mendahului suite dan tsc.

**Uji kering terhadap repo nyata** (hanya membaca, 6 Oktober 2026 malam, sesudah W3 Tugas 10 dan 11 dan menjelang akhir gelombang perbaikan W3): ke-107 operasi diterapkan berurutan di memori di atas pohon kerja repo nyata, tanpa menulis berkas apa pun. Hasilnya 0 masalah. Empat berkas yang disentuh rencana sudah berubah sejak dasar (`DESIGN.md`, `docs/ARCHITECTURE.md`, `tests/Feature/AmbientFitTest.php`, `tests/Pratinjau/HalamanPratinjauTest.php`), dan keempatnya disunting lewat blok "Ganti", bukan ditulis ulang utuh, jadi perubahan W3 di dalamnya tidak tertimpa. Uji kering tidak menjalankan test (Pra-cek butir 1a).

**Pengukuran di peramban** (Chrome lewat MCP chrome-devtools, pratinjau statis salinan di port 8130, emulasi seluler
dengan sentuh dan rasio piksel 1):

- Bar aksi Rencanakan setinggi 106 px di 360 x 740 dan di 320 x 568 (ringkasan satu baris "Camping, 21 sampai 22 Okt
  (2 hari)" di atas "Simpan rencana" 48 px), tanpa gulir mendatar. Sebelum ringkasan dipendekkan, bar itu 126 px karena
  nama jalur dan rentang panjang membungkus dua baris (W4-R40).
- Baris langkah ringkas empat langkah, masing-masing 44 px, satu baris di 320 px.
- `scrollWidth` sama dengan `clientWidth` (320) untuk indeks trip, ruang kerja, Persiapan, dan Cek kesiapan di 320 x
  568.
- Tangkapan pemeriksaan penulis (390 terang dan gelap, 1440 terang) menemukan dan memperbaiki empat hal sebelum rencana
  dirakit: tombol "Tidak berlaku" yang semula satu baris sendiri di setiap butir (W4-R19), "Ubah rencana" yang muncul
  dua kali di ruang kerja (W4-R3), baris butir kurang di Cek kesiapan yang membungkus aksinya ke bawah, dan ringkasan
  bar aksi Rencanakan yang dua baris.

**Temuan di luar cakupan W4 (OPEN ISSUE):**

- Alasan mesin kesiapan (`ReadinessService::explain()`) memakai "TUTUP" kapital dan istilah "Route fit"; alasan itu
  kini tampil sebagai daftar di Cek kesiapan untuk keadaan selain lengkap. `RefusalMessagesTest` mengunci kata "TUTUP"
  pada penolakan, jadi kosakatanya diputus bersama butir W3-3c (kata status mesin) di gelombang perbaikan atau W9.
- Kalimat penolakan `PreDepartureCheck` masih memakai "Pre-departure check"; tombol di layar kini "Saya sudah meninjau
  semuanya" (W4-R28).
- Label `TripStatus::READY_FOR_DEPARTURE` "Siap berangkat" masih tampil di permukaan di luar W4 (pratinjau Mendaki,
  Beranda). W4 hanya menyembunyikannya di kepala trip dan indeks (W4-R4); W5 dan W6 menentukan kata di permukaannya.
- `DemoSeeder` membuat trip contoh tanpa butir persiapan, jadi indeks trip pratinjau menulis "Belum ada item
  persiapan." sampai halaman Persiapan dibuka sekali (yang membangkitkan daftarnya).

## Keadaan awal yang terukur (FAKTA, 6 Oktober 2026)

| Butir | Keadaan | Bukti |
|---|---|---|
| Suite dasar | Unit dan Feature 1.764 test (1.762 lulus, 2 dilewati), sama dengan angka ledger W3 sesudah Tugas 9 | `php artisan test --compact --testsuite=Unit,Feature` di salinan dasar; ledger W3 baris "Task 9: fix round 1 DONE" |
| Pratinjau dasar | 14 halaman | `php artisan test --compact --testsuite=Pratinjau` di salinan dasar |
| Indeks trip | Daftar berhalaman 10 trip (`TripController::index`, `TripRowResource`), tanpa kartu utama dan kelompok | `app/Http/Controllers/Trips/TripController.php`, `resources/js/pages/Trips/Index.tsx` |
| Ruang kerja | Enam langkah (Pilih jalur, Buat rencana, Persiapan, Cek kesiapan, Hike mode, Selesaikan trip); baris tanggal "17 Okt 2026 - 18 Okt 2026" dengan tanda hubung; status "Siap berangkat" tampil di bawah judul | `app/Services/Trips/TripSteps.php`, `resources/js/pages/Trips/Show.tsx` |
| Rencanakan | Formulir dengan `<select>` seluruh jalur terbit (seluruh katalog dimuat sebagai opsi), isian angka peserta 1 sampai 30, tanpa kecocokan, izin, atau prakiraan | `TripController::create`, `resources/js/pages/Trips/Create.tsx`, `config/hiking.php` (`trips.max_participants` 30) |
| Persiapan | Tiga tombol status per butir, hitungan semua butir, tanpa hitungan butir kritis, tanpa butir menunggu, tanpa Urungkan | `resources/js/pages/Trips/Preparation.tsx`, `app/Http/Controllers/Trips/PreparationController.php` |
| Cek kesiapan | Label "Siap", "Perlu persiapan", "Tidak direkomendasikan"; kalimat R-014 lama; tanpa prakiraan untuk tanggal trip, tanpa "Sejak cek terakhir"; status resmi tanpa kalimat R-012 (titipan W3-S5) | `app/Enums/ReadinessState.php`, `resources/js/pages/Trips/Readiness.tsx`, `app/Http/Resources/TripReadinessResource.php` |
| Kecocokan kesiapan | `ReadinessService::compute()` menilai dengan `$trip->hikingGoal` (null untuk trip tanpa tujuan), berbeda dari ruang kerja dan halaman penilaian W3 yang memakai `TripPlan::tujuanPenilaian()` | `app/Services/ReadinessService.php`, `app/Models/TripPlan.php` |
| Ikon | Registri Phosphor tanpa kalender dan jam; paket `@phosphor-icons/*` tidak terpasang | `resources/js/components/ui/ikon/phosphor.ts`, `node_modules` |
| Data contoh | Trip Budi "Gede via Cibodas" PLANNED 15 hari lagi tanpa butir persiapan; aturan izin tingkat gunung H-30 sampai H-3 tanpa tautan pemesanan | `database/seeders/DemoSeeder.php` |

## Ruling rencana (W4-R1 sampai W4-R40)

Setiap ruling ditulis dengan format keputusan, alasan, dan akibat bila salah.

| ID | Keputusan | Alasan | Akibat bila salah |
|---|---|---|---|
| W4-R1 | Dasar rencana adalah tree `ac88231` (W3 Tugas 9 selesai). Blok "Ganti" dihitung dari keadaan itu, lalu jangkar yang tertabrak perubahan W3 di repo nyata dipindah tanpa mengubah isi berkas hasilnya | Salinan baru dari repo nyata tidak stabil selama W3 Tugas 10, 11, dan gelombang perbaikan akhir berjalan (brief koordinator); uji kering hanya-baca terhadap pohon kerja repo nyata menemukan lima jangkar yang tertabrak | Jangkar yang berubah oleh perbaikan W3 sesudah uji kering tidak ditemukan; implementer berhenti NEEDS_CONTEXT dan koordinator mencocokkan ulang dengan `cek-jangkar.php` sebelum dispatch |
| W4-R2 | Delapan tugas: ruang kerja (1), indeks (2), Rencanakan (3), Persiapan (4), kata DK-48 (5), Cek kesiapan (6), dokumen (7), verifikasi koordinator (8). Ruang kerja lebih dulu karena `KepalaTrip` dan `TripSteps::aksi()` dipakai indeks | Tiap tugas satu unit tinjauan; DK-48 dipisah sebagai perubahan kata produk dengan governance record | Tugas 3 dan 6 besar untuk satu tinjauan; peninjau boleh meminta dua putaran |
| W4-R3 | Ruang kerja memakai empat langkah prototype (Jalur, Rencana, Persiapan, Cek). Mulai pendakian, selesaikan, dan batalkan tetap di kartu Tindakan. "Ubah rencana" hanya di kepala trip, tidak diulang di langkah Rencana | Spec bagian 6.9; satu aksi satu tempat (design-taste "no duplicate CTA intent") | `TripReactPagesTest::test_the_step_list_points_at_the_next_step` diperbarui dari 6 ke 4 langkah; mengembalikan langkah ke enam mengubah `TripSteps::for()` dan test itu |
| W4-R4 | Kepala trip dan kartu indeks tidak menyebut status untuk trip yang belum dimulai; status hanya untuk berlangsung, selesai, dan dibatalkan. Trip belum dimulai dibaca dari H-n | "Siap berangkat" di samping nama trip terbaca sebagai jaminan (R-014) | Pendaki yang mengandalkan kata status melihat H-n dan langkah Cek sebagai gantinya |
| W4-R5 | Kecocokan di ruang kerja memakai `TripPlan::tujuanPenilaian()` (W3) dan menautkan "Lihat penilaian lengkap" ke `trails.fit` dengan `?trip=` selama jalurnya terbit | Spec bagian 6.4 dan 6.9: kecocokan untuk rencana ini; halaman penilaian W3 menjawab 404 untuk jalur draf dan arsip | Trip lama tanpa tujuan kini dinilai untuk jenis dan tanggalnya, sama dengan halaman penilaian |
| W4-R6 | Indeks: kartu utama trip berlangsung, atau trip terdekat yang tanggalnya belum lewat (WIB). Mendatang memuat rencana yang belum dimulai, termasuk yang tanggalnya terlewat (tanpa H-n). Selesai memuat lima terbaru dan menunjuk Progres. Kueri tetap: satu untuk trip belum selesai, satu untuk lima terbaru, satu hitungan, relasi sekali, status dan kecocokan dalam batch. Tanpa paginasi | Spec bagian 6.9; anggaran kueri | Pendaki dengan ratusan rencana aktif memuat semuanya dalam satu halaman (memori, bukan kueri); paginasi kelompok Mendatang menyusul bila terukur |
| W4-R7 | Kartu indeks: hanya kartu utama yang membawa tombol penuh; kartu kelompok memakai tautan teks; kecocokan dasar hanya untuk trip yang belum selesai; peringatan kesiapan tidak diulang bila langkah berikutnya sudah Cek | Satu titik fokus per layar (antislop bagian 3) | Aksi kartu kelompok kurang menonjol; tetap 44 px |
| W4-R8 | Rencanakan menerima jalur lewat `?trail=` (hanya jalur terbit, R-022). Tanpa jalur, halaman menawarkan simpanan pendaki sendiri yang masih terbit dan jalan ke Jelajah; `<select>` seluruh katalog dilepas | Spec bagian 6.8 ("Jalur terpilih dan Ganti"); isi halaman tidak tumbuh bersama katalog nasional | Pendaki tanpa simpanan harus ke Jelajah dulu; `AccessibilityTest` tetap lulus karena halaman React |
| W4-R9 | Kalender rentang prototype diganti isian tanggal native untuk tanggal naik, dan tanggal turun hanya untuk Multi-hari; Tektok dan Camping mengisi tanggal turun dari lama jenisnya | Kontrol tanggal native dapat dipakai papan ketik dan pembaca layar di semua perangkat tanpa komponen baru; kalender grid kustom adalah komponen besar dengan risiko aksesibilitas | Satu komponen kalender ditambah kemudian; datanya tetap `planned_date` dan `end_date` |
| W4-R10 | Jam mulai berupa isian jam native dengan zona gunungnya, bukan tiga chip 05.00, 07.00, 09.00 seperti prototype | Jam mulai pendakian beragam (dini hari untuk puncak); tiga pilihan tetap mengarang batas | Pendaki mengetik jam; zona tetap disebut |
| W4-R11 | Peserta 1 sampai 12 lewat `hiking.trips.max_participants` = 12 (sebelumnya 30); `TripPlanDetailTest` diperbarui; governance record GR-05 | Spec bagian 6.8 yang disetujui pemilik | Rencana lama dengan lebih dari 12 peserta harus dikurangi saat diubah; satu angka config bila pemilik membatalkannya |
| W4-R12 | Stepper peserta mulai dari 1 ("Termasuk Anda") di Rencanakan; formulir Ubah tetap isian angka yang boleh kosong | Stepper butuh angka; 1 yang terlihat dan disimpan pendaki adalah pernyataannya sendiri | Rencana baru tidak lagi berpeserta kosong |
| W4-R13 | Pengingat surel di Rencanakan mati secara bawaan, walau prototype 08 menampilkannya menyala | Surel butuh persetujuan eksplisit (keputusan W0, `docs/KEBIJAKAN-DATA-PRIBADI.md`) | Lebih sedikit pendaki menerima pengingat sampai mereka menyalakannya |
| W4-R14 | Muat ulang parsial `router.reload({ only: ['kecocokan', 'jadwal', 'izin'], data: { jenis, tanggal, selesai }, replace: true })`; ketiganya closure di server sehingga hanya dihitung bila diminta; nilai URL hanya dipakai bila sah (tanggal Y-m-d tidak sebelum hari ini WIB, tanggal turun di dalam rentang) | Spec bagian 6.8 dan 9.2: kecocokan dihitung server; `replace` supaya riwayat tidak menumpuk; URL mempertahankan pilihan saat dimuat ulang | URL tidak dapat memaksakan tanggal lampau; riwayat peramban tidak berisi setiap tanggal yang dicoba |
| W4-R15 | Kotak izin menyebut tanggal buka dan tutup untuk tanggal yang dipilih (keadaan dari `PermitService::bookingWindowFor()`), pengelola, rentang H, dan "Memount menautkan sistem resmi dan tidak menjual izin". Aturan tanpa jendela ditulis "belum tercatat", tidak "sedang dibuka" | Spec bagian 6.8; R-007 (menutup W2-6c untuk halaman ini) | Kalimat `PermitService` lama tetap dipakai ruang kerja sampai W8 |
| W4-R16 | Kalimat prakiraan: "Prakiraan BMKG untuk ... terbit mulai ...; sebelum itu Memount tidak menebak cuacanya", dari `hiking.conditions.weather_forecast_days` | R-013; spec bagian 6.8 | Satu config untuk jendela terbit |
| W4-R17 | Jalur berstatus Tutup tetap dapat direncanakan untuk tanggal mendatang, dengan peringatan status sekarang dan kalimat R-012 | Spec bagian 6.8; BR-04 hanya mengatur rekomendasi | Pendaki merencanakan jalur yang belum tentu dibuka; peringatannya tertulis |
| W4-R18 | Tanpa ikon baru: kotak izin tanpa ikon kalender prototype (ikon peringatan hanya untuk jendela yang sudah tutup), butir menunggu memakai kotak putus-putus tanpa glyph jam | Tidak ada paket Phosphor terpasang dan tidak ada unduhan (Global Constraints) | Selisih kecil dengan acuan 08 sampai 10, dicatat README tangkapan W4 |
| W4-R19 | Persiapan memakai `KotakCentang` (Dikonfirmasi lawan Belum dikonfirmasi) dan tombol teks "Tidak berlaku" atau "Berlaku lagi" di kanan baris yang sama; tiga keadaan PRD §37 tetap | Prototype 09 hanya punya centang; "Tidak berlaku" tetap dibutuhkan (§37); satu baris per butir | Tombol teks kecil di setiap butir; 44 px |
| W4-R20 | Urungkan mengirim status sebelumnya lewat rute `trips.preparation.update` yang sama; tidak ada rute baru. Centang `async`, `preserveState`, `preserveScroll`; keadaan sementara dibuang di `onFinish`, jadi penolakan atau jaringan putus mengembalikan status tersimpan | Spec bagian 6.10 dan 9.1; konvensi W2 | Satu kunjungan per Urungkan |
| W4-R21 | Butir kategori Cuaca menunggu BMKG ("Menunggu BMKG: terbit mulai ...") sampai H-3, dan server menolak konfirmasinya sebelum itu (`PreparationService::penolakanKonfirmasi()`); "Tidak berlaku" tetap boleh | R-013: "sudah diperiksa" atas prakiraan yang belum ada adalah pernyataan kosong | Keadaan "Persiapan kritis lengkap" tidak tercapai sebelum H-3 untuk templat yang menandai butir cuaca kritis, dan itu sesuai maknanya |
| W4-R22 | Butir "Status resmi terbaru diperiksa" di Persiapan tidak menampilkan status hidup (prototype 09 menampilkannya); status hidup ada di Cek kesiapan bersama sumber, kesegaran, dan kalimat R-012 | Satu tempat status resmi per alur | Pendaki membuka Cek untuk status |
| W4-R23 | Perlengkapan pribadi yang mengisi awal persiapan (spec bagian 6.10) tidak dibangun di W4 | Fiturnya milik Profil W7 (spec bagian 6.15 dan 9.3) | Daftar persiapan tetap dari templat jalur |
| W4-R24 | Kata DK-48 tinggal di `ReadinessState::label()`, satu sumber untuk ruang kerja, indeks, Cek, Beranda, dan pratinjau Mendaki. "Masih perlu persiapan" sengaja berbeda dari label kecocokan "Perlu persiapan" | DK-48; R-004 | Governance record GR-05 di PRD §146 |
| W4-R25 | `ReadinessService::compute()` menilai kecocokan untuk rencana lewat `TripPlan::tujuanPenilaian()` | Spec bagian 6.11 ("kecocokan untuk rencana"); satu penilaian di ruang kerja, penilaian, dan Cek | Trip lama tanpa tujuan, Tektok di jalur bermalam, kini "Tidak disarankan dengan rencana ini"; GR-05 |
| W4-R26 | "Sejak cek terakhir" membandingkan penilaian sekarang dengan baris `readiness_checks` terakhir (dari "Simpan hasil cek ini" atau konfirmasi): status resmi, laporan komunitas yang masuk sesudahnya (dari sepuluh laporan terbaru 30 hari), butir persiapan dikonfirmasi, dan label kecocokan. Tanpa tabel baru | Spec bagian 6.11; induk W4 butir 4 | Lebih dari sepuluh laporan baru terhitung sepuluh |
| W4-R27 | `TripReadinessResource` dihapus; props Cek dari `HalamanKesiapan`. Semua alasan mesin dikirim sebagai `keadaan.alasan` untuk keadaan selain lengkap | Satu kelas halaman per layar (spec bagian 9.2). Simulasi menemukan bahwa alasan pertama saja menyembunyikan "ditarik dari katalog" di balik "status belum dapat dipastikan" | `ArchivedTrailWithActiveTripTest` membaca `keadaan.alasan` |
| W4-R28 | Kalimat R-014 prototype 10 menggantikan kalimat lama; flash "Hasil cek disimpan. Cek berikutnya dibandingkan dengan hasil ini." dan "Tinjauan Anda tercatat."; tombol "Saya sudah meninjau semuanya" dan "Selesaikan N butir". Penolakan `PreDepartureCheck` tidak diubah | Prototype 10; `RefusalMessagesTest` mengunci kalimat penolakan | `TripReactPagesTest`, `ReadinessDashboardTest`, dan `CoreJourneyTest` diperbarui dengan alasan tertulis |
| W4-R29 | Kartu gunung api prototype 10 tidak tampil sampai tabelnya ada (W8) | DK-43; sama dengan W3-R36 | Tanpa kartu PVMBG karangan |
| W4-R30 | Pesan hari keberangkatan selalu tampil di Cek, menyebut titik awal jalur (`starting_point`) bila ada | Spec bagian 6.11; status lama tidak ditampilkan dari simpanan | Kalimat tetap ketika titik awal kosong ("pos registrasi") |
| W4-R31 | Tanpa migrasi dan tanpa kolom baru. Halaman trip W4 membaca kolom yang sudah dibaca halaman trip sebelum W4 (`end_date`, `start_time`, `participants_count`, `trip_reminders`), jadi W4 tidak menambah ketergantungan skema baru di Supabase (bandingkan W3-8b) | Brief butir 5 | Ketergantungan lama pada `add_plan_details_to_trip_plans_table` dan `create_trip_reminders_table` tetap tercatat di Q22 |
| W4-R32 | `KontrolSegmen` dan `Stepper` menerima prop `penjelas` (id untuk `aria-describedby` pada grupnya); `DesignPrimitivesTest` diperbarui | `KeyboardAndScreenReaderTest` menuntut galat terikat ke kontrolnya | Dua primitif bersama berubah aditif |
| W4-R33 | Pratinjau bertambah empat halaman: `ruang-kerja`, `rencanakan`, `persiapan`, `cek-kesiapan` (14 menjadi 18) | Tangkapan Tugas 8 terhadap acuan 08 sampai 10 | Data persiapan pratinjau dibangkitkan di test pratinjau, bukan di `DemoSeeder` |
| W4-R34 | Dokumen: `docs/fitur/04-perjalanan-persiapan.md` ditulis ulang, `DESIGN.md` dan `docs/ARCHITECTURE.md` di jangkar yang tidak disentuh W3 Tugas 11, dan governance record GR-05 di `docs/PRD.md` §146 | Induk W4 butir 4 ("catatan tata kelola PRD §146 untuk label DK-48"); CLAUDE.md bagian 3 | Bila W3 gelombang perbaikan akhir menyentuh jangkar yang sama, Pra-cek menangkapnya |
| W4-R35 | Verifikasi gelombang oleh koordinator: tangkapan 390 dan 1440 px terang dan gelap terhadap acuan 08 sampai 10, ukur lapisan, jalur papan ketik, Delivery Gate empat blok, HANDOVER | Brief butir 2; aturan penutup gelombang induk | Selisih dengan acuan dicatat di README tangkapan W4 |
| W4-R36 | Kalimat R-012 di `Trips/Create.tsx` dan `Trips/Readiness.tsx` (titipan W3-S5); ruang kerja, indeks, dan Persiapan tidak merender status resmi | Test cakupan sumber gelombang perbaikan akhir W3 | Bila layar lain mulai merender status, kalimatnya ikut |
| W4-R37 | Kepala trip menulis tanggal dengan "sampai" dan tahun sekali ("Sab, 10 Okt sampai Min, 11 Okt 2026"), jam dengan zona ("07.00 WITA"), tanpa titik tengah; langkah Rencana memakai rentang yang sama | Spec bagian 8.8; satu cara menulis tanggal per halaman | `TripPlanDetailTest` diperbarui |
| W4-R38 | Rencanakan, Persiapan, dan Cek satu kolom `max-w-2xl` juga di layar lebar; ruang kerja dua kolom mulai 1024 px (langkah 18rem di kiri) | Spec bagian 5.2 menyebut dua kolom hanya untuk ruang kerja; prototype 08 sampai 10 hanya ponsel | Layar lebar menyisakan ruang kosong di kiri dan kanan |
| W4-R39 | Halaman Persiapan dan Cek kembali ke ruang kerja trip; Rencanakan kembali ke indeks Trip. Ketiganya menyalakan tab Trip | `NavigationConsistencyTest`: induk menyalakan tab yang sama | Tidak ada perubahan `NavLinks.tsx` |
| W4-R40 | Ringkasan bar aksi Rencanakan memakai rentang pendek dari server (`jadwal.ringkas`, misalnya "10 sampai 11 Okt") dan lama hari; nama jalur sudah di kartu atas | Anggaran tinggi lapisan bawah (W2-F5): 106 px, bukan 126 px, di 360 dan 320 px | Satu kunci tambahan di props `jadwal` |

### Konflik yang perlu ruling koordinator

1. **Batas peserta 30 menjadi 12 (W4-R11).** Spec bagian 6.8 yang disetujui pemilik menulis 1 sampai 12, sedangkan
   config sekarang 30. Rekomendasi: ikuti spec, catat di GR-05, dan sebut di permintaan izin akhir sebagai keputusan
   yang tercatat (bukan izin baru).
2. **Kecocokan kesiapan untuk rencana (W4-R25).** Mengubah hasil kesiapan trip lama tanpa tujuan. Rekomendasi: terima,
   karena ruang kerja dan halaman penilaian W3 sudah menilai dengan tujuan yang sama, dan dua jawaban berbeda untuk trip
   yang sama lebih membingungkan.
3. **Kosakata mesin di daftar alasan Cek kesiapan** ("TUTUP" kapital, "Route fit", "Pre-departure check").
   Rekomendasi: parkir ke gelombang perbaikan akhir W3 (butir W3-3c) atau W9, bersama `RefusalMessagesTest`.
4. **"Siap berangkat" di luar permukaan W4.** Rekomendasi: W5 (Beranda) dan W6 (Mendaki) memutuskan katanya; W4 tidak
   mengubah `TripStatus::label()` karena dipakai riwayat dan admin.
5. **Pemilih jalur Rencanakan dari simpanan saja (W4-R8).** Rekomendasi: terima; pendaki tanpa simpanan diarahkan ke
   Jelajah, sesuai spec bagian 6.9 ("Mulai dari jalur yang Anda simpan atau dari Jelajah").

### Pra-cek untuk koordinator

Rencana ditulis di atas tree `ac88231`. Asumsi berikut diperiksa langsung di salinan itu; periksa ulang di repo nyata
sesudah W3 Tugas 10, 11, 12, dan gelombang perbaikan akhir W3, sebelum Tugas 1.

| Asumsi | Tempat rencana bergantung | Keadaan di dasar |
|---|---|---|
| 1. Jangkar setiap blok "Ganti" ada tepat sekali | Semua tugas | Sesuai di dasar, dan sesuai di pohon kerja repo nyata pada uji kering 6 Oktober malam (0 dari 107 operasi bermasalah; lihat Bukti simulasi). Lima jangkar sudah dipindah karena W3 Tugas 10, 11, dan gelombang perbaikan akhir mengubah teks di sekitarnya. Jalankan `cek-jangkar.php` W3 (folder ledger W3) terhadap brief setiap tugas sebelum dispatch. Berkas yang disentuh rencana dan sudah berubah sejak dasar: `DESIGN.md`, `docs/ARCHITECTURE.md`, `tests/Feature/AmbientFitTest.php`, `tests/Pratinjau/HalamanPratinjauTest.php` |
| 1a. Suite hijau sesudah W3 selesai | Angka setiap tugas | Uji kering tidak menjalankan test. Sebelum Tugas 1, catat suite Unit dan Feature repo nyata sebagai dasar W4; perubahan W3 di berkas yang tidak disentuh rencana (misalnya test cakupan sumber kalimat R-012) dapat menambah test yang harus tetap hijau sesudah Tugas 3 dan 6 |
| 2. `TripPlan::tujuanPenilaian()` dan `ringkasan()` (W3 Tugas 6) | Tugas 1, 6 | Ada di `app/Models/TripPlan.php` |
| 3. `StatusJalurRingkas::untuk()` mengembalikan `status`, `label`, `cakupan`, `sumber`, `waktu`, `kesegaran`; `BarisStatus({ status })` | Tugas 3, 6 | Sesuai; bila W3-2c (awalan waktu) mengubah isi `waktu`, tampilan ikut tanpa perubahan kode W4 |
| 4. `ConsiderationService::simpanan()` publik dengan relasi `trail.mountain` | Tugas 3 | Sesuai |
| 5. `PermitRequirement::terverifikasiDulu` dan `PermitService::requirementFor()`, `bookingWindowFor()` | Tugas 3, 4, 6 | Sesuai (perbaikan W3-2b) |
| 6. `LabelKecocokan({ nilai, label })`, `LabelKomunitas`, `Lencana` nada `gelap`, `peringatan`, `merek`; `KotakCentang`, `Stepper`, `Sakelar`, `KontrolSegmen`, `BarAksi`, `EmptyState` | Tugas 1 sampai 6 | Sesuai |
| 7. `EmptyStateCoverageTest::DAFTAR_TETAP` memuat entri `pages/Trips/Readiness.tsx` `check.penjelasan` | Tugas 6 menggantinya | Sesuai di dasar |
| 8. Test cakupan sumber kalimat R-012 dari gelombang perbaikan akhir W3 | Tugas 3 dan 6 menambah kalimatnya | Belum ada di dasar; Tugas 3 dan 6 sudah memuat kalimat persisnya |
| 9. Pratinjau dasar 14 halaman dan `self::ASAL` | Tugas 1, 3, 4, 6 menambah halaman | Sesuai; rencana memakai `self::ASAL` apa adanya |
| 10. Suite nyata sesudah W3 | Angka hijau setiap tugas | Dasar simulasi 1.764 test; selisih dari W3 Tugas 10 sampai 12 dan perbaikan akhir ditambahkan koordinator ke setiap angka "pada simulasi rencana" |

## Perbandingan skill (DK-57)

Urutan wewenang: arahan pemilik dan aturan kanonis, lalu `DESIGN.md` (arah), lalu antislop, lalu saran skill. antislop
berlaku mode 1 (selama pekerjaan) atas arahan pemilik yang sudah berdiri, dan tidak ada pertanyaan baru kepada pemilik.
Kelima skill dimuat dan dibaca penuh sebelum komponen ditulis.

| Skill | Diambil | Ditolak dan alasannya |
|---|---|---|
| antislop (inti) | R-26: setiap kendali bekerja (Urungkan, Tidak berlaku, Simpan hasil cek, Ganti jalur); kendali yang belum berfungsi tidak dipasang (kalender kustom, chip jam). R-27: keadaan pertama kali (indeks kosong, tanpa simpanan), menunggu (BMKG, aturan izin tanpa jendela), memuat (kalimat "Menghitung ulang untuk pilihan baru" saat muat ulang parsial), dan galat (cuaca gagal, penolakan konfirmasi). R-17 dan R-36: tanpa kuota, jendela, atau prakiraan karangan. Satu titik fokus per layar: tombol penuh hanya di kartu utama indeks dan aksi utama Cek | Tidak ada; antislop adalah saringan di atas `DESIGN.md` |
| antislop-copywriting | Aksi spesifik ("Rencanakan trip", "Lanjutkan persiapan", "Pesan di sistem resmi", "Selesaikan 3 butir", "Saya sudah meninjau semuanya"); setiap kalimat kosong menyebut siapa yang ditunggu ("Menunggu BMKG", "Memount menautkan sistem resmi"); tanpa kapital sebagai penekanan di teks baru; tanpa em dash | "Hike mode" diganti "Mulai pendakian" dan "Buka mode lapangan"; kosakata mesin lama (TUTUP, Route fit) dicatat OPEN ISSUE, tidak ditulis ulang diam-diam |
| ui-ux-pro-max | Galat terikat ke isiannya (`aria-describedby`, prop `penjelas`); grup radio asli untuk jenis trip; kotak centang dengan label penuh sebagai target; Urungkan untuk aksi beruntun; tabel prakiraan bertajuk; tanggal dan jam native dengan keyboard yang tepat | Persen kesiapan dan bilah skor (R-005, BR-09); keluaran `--design-system` (gradien, Lucide), sama dengan W1 sampai W3 |
| design-taste-frontend | Tanpa duplikasi niat aksi ("Ubah rencana" satu tempat); angka tabular untuk tanggal, jam, dan hitungan; hierarki lewat bobot huruf; ruas hitungan tanpa jalur latar persen; satu aksen tidak dipakai di luar tempatnya | Kalender grid dan chip jam prototype ditunda (W4-R9, W4-R10) demi kontrol native yang berfungsi; foto atau ilustrasi tidak ditambah ke halaman tugas |
| impeccable (`reference/craft-floor.md`, `reference/operate.md`) | Mode Operate: keakraban dan konsistensi lebih penting daripada ekspresi; keadaan default, fokus, tekan, nonaktif, memuat, galat, dan kosong untuk setiap kendali; kosakata komponen yang sama di empat layar (kepala trip, baris langkah, kartu bergaris rambut); lebar baca ruang kerja dua kolom di layar lebar | Eyebrow di atas judul (dilarang craft-floor): "Keadaan kesiapan" ditulis sebagai label kalimat 13 px, bukan kapital bertracking; kartu bersarang dihindari kecuali kotak alasan di dalam kartu jalur Rencanakan |

**Pembacaan desain (antislop bagian 3):** klaster perencanaan trip untuk pendaki pemula Indonesia di ponsel, bahasa
visual editorial utilitarian yang hangat, dial ENERGY 2 / RHYTHM 2 / MOTION 2 (DESIGN.md). Setiap layar memberi satu
keputusan: apa langkah berikutnya, dan apa yang masih kurang, dengan sumber yang dapat diperiksa. Motif identitas:
garis rute berujung cincin pos pada langkah trip.

**Inventaris skill untuk gelombang ini (CLAUDE.md bagian 7):**

- INTI: antislop, antislop-copywriting, impeccable, ui-ux-pro-max, design-taste-frontend, superpowers:writing-plans,
  superpowers:test-driven-development, testing-best-practices, laravel-best-practices (kelas halaman, Form Request,
  Policy).
- PENDUKUNG: tailwindcss-development (grid `lg:grid-cols-[18rem_minmax(0,1fr)]`), mobile-native (target sentuh, isian
  tanggal native), ecc:frontend-a11y (`aria-describedby`, `aria-current="step"`), ecc:security-review (badan permintaan
  butir persiapan), ecc:react-patterns (muat ulang parsial, keadaan optimis).
- LINTAS: superpowers:verification-before-completion dan Delivery Gate antislop.
- N/A:
  - slides, banner-design, imagegen-frontend-web, imagegen-frontend-mobile: bukan artefak halaman;
  - brand dan brandkit: aset logo menunggu gerbang DK-16;
  - animate, improve-animations, emil-design-eng: gerak W4 memakai daftar tertutup W1 (tekan, toast, kenop sakelar);
  - livewire-development dan volt-development: Livewire sudah dipensiunkan;
  - deploying-to-cloud: tanpa rilis;
  - ecc:postgres-patterns dan ecc:database-migrations: W4 tanpa migrasi dan tanpa kueri spasial;
  - write-swift: bukan aplikasi native.

## File Structure

**Dibuat:**

- Tugas 1: `app/Services/Trips/KepalaTrip.php`, `resources/js/components/trips/KepalaTrip.tsx`, `tests/Feature/TripWorkspaceTest.php`.
- Tugas 2: `app/Services/Trips/IndeksTrip.php`, `resources/js/components/trips/KartuTrip.tsx`, `tests/Feature/TripIndexPageTest.php`.
- Tugas 3: `app/Services/Trips/RencanaTrip.php`, `resources/js/components/trips/LangkahRingkas.tsx`, `resources/js/components/trips/rencana/KotakIzin.tsx`, `tests/Feature/TripPlanFitReloadTest.php`.
- Tugas 4: `app/Services/Trips/HalamanPersiapan.php`, `tests/Feature/PreparationUndoTest.php`.
- Tugas 6: `app/Services/Trips/HalamanKesiapan.php`, `tests/Feature/ReadinessSinceLastCheckTest.php`.

**Dihapus:**

- Tugas 6: `app/Http/Resources/TripReadinessResource.php`.

**Diubah:**

- Tugas 1: `app/Enums/TripType.php`, `app/Http/Controllers/Trips/TripController.php`, `app/Services/Trips/TripSteps.php`, `resources/js/components/trips/LangkahTrip.tsx`, `resources/js/components/trips/types.ts`, `resources/js/pages/Trips/Show.tsx`, `tests/Feature/TripPlanDetailTest.php`, `tests/Feature/TripReactPagesTest.php`, `tests/Pratinjau/HalamanPratinjauTest.php`.
- Tugas 2: `app/Http/Controllers/Trips/TripController.php`, `app/Http/Resources/TripRowResource.php`, `resources/js/components/trips/types.ts`, `resources/js/pages/Trips/Index.tsx`, `tests/Feature/AmbientFitTest.php`, `tests/Feature/StaleReadinessTest.php`.
- Tugas 3: `app/Http/Controllers/Trips/TripController.php`, `config/hiking.php`, `resources/js/components/ui/KontrolSegmen.tsx`, `resources/js/components/ui/Stepper.tsx`, `resources/js/pages/Trips/Create.tsx`, `tests/Feature/DesignPrimitivesTest.php`, `tests/Feature/TripPermitWindowTest.php`, `tests/Feature/TripPlanDetailTest.php`, `tests/Feature/TripReactPagesTest.php`, `tests/Pratinjau/HalamanPratinjauTest.php`.
- Tugas 4: `app/Http/Controllers/Trips/PreparationController.php`, `app/Services/PreparationService.php`, `resources/js/pages/Trips/Preparation.tsx`, `tests/Pratinjau/HalamanPratinjauTest.php`, `tests/Unit/PreparationServiceTest.php`.
- Tugas 5: `app/Enums/ReadinessState.php`, `tests/Feature/ReadinessDashboardTest.php`.
- Tugas 6: `app/Http/Controllers/Trips/ReadinessController.php`, `app/Services/ReadinessService.php`, `resources/js/pages/Trips/Preparation.tsx`, `resources/js/pages/Trips/Readiness.tsx`, `tests/Feature/ArchivedTrailWithActiveTripTest.php`, `tests/Feature/CoreJourneyTest.php`, `tests/Feature/EmptyStateCoverageTest.php`, `tests/Feature/ReadinessDashboardTest.php`, `tests/Feature/TripReactPagesTest.php`, `tests/Pratinjau/HalamanPratinjauTest.php`.
- Tugas 7: `DESIGN.md`, `docs/ARCHITECTURE.md`, `docs/PRD.md`, `docs/fitur/04-perjalanan-persiapan.md`.

Tugas 8 (koordinator) membuat `docs/desain/tangkapan/w4/*.webp` dan `docs/desain/tangkapan/w4/README.md`, dan
memperbarui `HANDOVER.md` bagian 0 serta `.superpowers/sdd/2026-09-26-memount-v3-program.md`.

---

### Task 1: Ruang kerja trip (empat langkah dan kepala trip)

Spec bagian 6.9 dan 6.10 (kepala trip); induk W4 butir 3 (ruang kerja); ruling W4-R3 sampai W4-R5, W4-R37, W4-R38.
`/trips/{trip}` memakai empat langkah prototype (Jalur, Rencana, Persiapan, Cek) sebagai garis rute berujung cincin
pos, kepala trip yang menyebut jenis, sisa hari, tanggal, jam dalam zona gunung, dan peserta, kecocokan untuk rencana
dengan tautan ke penilaian lengkap, lalu izin, pengingat, catatan, dan kartu Tindakan. Status trip tidak ditulis untuk
trip yang belum dimulai. `TripSteps::aksi()` menyiapkan aksi berikutnya yang dipakai indeks di Tugas 2.

**Files:**
- Create: `app/Services/Trips/KepalaTrip.php`, `resources/js/components/trips/KepalaTrip.tsx`
- Modify: `app/Enums/TripType.php`, `app/Http/Controllers/Trips/TripController.php`, `app/Services/Trips/TripSteps.php`, `resources/js/components/trips/LangkahTrip.tsx`, `resources/js/components/trips/types.ts`, `resources/js/pages/Trips/Show.tsx`
- Test: `tests/Feature/TripWorkspaceTest.php` (baru), `tests/Feature/TripPlanDetailTest.php`, `tests/Feature/TripReactPagesTest.php`, `tests/Pratinjau/HalamanPratinjauTest.php`

**Interfaces:**
- Consumes: `TripPlan::tujuanPenilaian()` (W3 Tugas 6), `TrailFitService::forTrails()`, `TripLifecycle::editRefusal()`, `Timezone::forTrail()` dan `label()`, `PageHeader` dengan `induk`, `Lencana`, `Tautan`, `Icon` (`check`), rute `trails.fit` dan `trails.show` (W3).
- Produces:
  - `App\Services\Trips\KepalaTrip::untuk(TripPlan $trip): array{id, nama, jalur, gunung, jenis, hari, tanggal, jam, peserta, keadaan, ubah}`, `KepalaTrip::hariMenuju(CarbonInterface $tanggal, string $zona): ?string`, `KepalaTrip::rentang(CarbonInterface $mulai, ?CarbonInterface $selesai): string`;
  - `App\Enums\TripType::labelPendek(): string`, `TripType::lamaHari(): int`;
  - `App\Services\Trips\TripSteps::for(TripPlan $trip, ?OfficialStatusValue $statusSekarang = null): array` dengan kunci langkah `jalur`, `rencana`, `persiapan`, `kesiapan` (judul Jalur, Rencana, Persiapan, Cek), `TripSteps::aksi(TripPlan $trip, array $langkah): ?array{label, href}`; `TripSteps` tidak lagi punya konstruktor;
  - props `Trips/Show`: `kepala`, `trip` (`id`, `aktif`, `berlangsung`, `catatan`), `langkah`, `fit`, `penilaian`, `izin`, `hasilPendakian`, `completionStates`, `penolakan`, `pengingat`, `kelola`;
  - TypeScript `KepalaTripData` di `resources/js/components/trips/types.ts`; komponen `KepalaTrip({ kepala })` dan `LangkahTrip({ langkah })`.

- [ ] **Step 1: Tulis test yang gagal.** Buat berkas test baru dengan `make:test` (Global Constraints), lalu ganti
isinya persis. Test lama yang diperbarui membawa alasannya di komentar.

Buat `tests/Feature/TripWorkspaceTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\CompletionState;
use App\Enums\PreparationCategory;
use App\Enums\PreparationStatus;
use App\Enums\ReadinessState;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Models\HikingHistory;
use App\Models\Mountain;
use App\Models\ReadinessCheck;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Models\TripReminder;
use App\Models\User;
use App\Services\Trips\TripSteps;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Ruang kerja satu trip (spec v3 bagian 6.9, W4 Tugas 1): empat langkah prototype (Jalur,
 * Rencana, Persiapan, Cek) dari TripSteps, kepala trip yang menyebut jenis, sisa hari, dan
 * jam dalam zona gunungnya, serta aksi berikutnya yang dipakai indeks trip.
 *
 * Waktu dibekukan pada 25 September 2026 pukul 10.00 WIB, jadi trip 10 Oktober jatuh pada
 * H-15, sama dengan prototype 09.
 */
class TripWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-09-25 03:00:00', 'UTC'));
    }

    private function trip(array $atribut = [], ?Trail $trail = null): TripPlan
    {
        return TripPlan::factory()->create(array_merge([
            'trail_id' => ($trail ?? Trail::factory()->published()->create())->id,
            'name' => 'Gede bareng Dimas dan Sari',
            'planned_date' => '2026-10-10',
            'end_date' => '2026-10-11',
            'start_time' => '07:00',
            'participants_count' => 3,
            'trip_type' => TripType::CAMPING->value,
            'status' => TripStatus::PLANNED->value,
        ], $atribut));
    }

    private function butir(TripPlan $trip, PreparationStatus $status, bool $kritis = true): TripPreparationItem
    {
        return TripPreparationItem::create([
            'trip_plan_id' => $trip->id,
            'category' => PreparationCategory::EQUIPMENT->value,
            'label' => 'Butir '.fake()->unique()->word(),
            'is_critical' => $kritis,
            'status' => $status->value,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function props(TripPlan $trip): array
    {
        return $this->actingAs($trip->user)->get(route('trips.show', $trip))->assertOk()->viewData('page')['props'];
    }

    public function test_a_guest_is_sent_to_login_and_another_hiker_is_refused(): void
    {
        $trip = $this->trip();

        $this->get(route('trips.show', $trip))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('trips.show', $trip))->assertForbidden();
    }

    /**
     * Prototype 08 sampai 10 memakai empat langkah. "Hike mode" dan "Selesaikan trip" tetap
     * ada sebagai aksi di kartu Tindakan, bukan langkah perencanaan.
     */
    public function test_the_workspace_follows_the_four_prototype_steps(): void
    {
        $props = $this->props($this->trip());

        $this->assertSame(['jalur', 'rencana', 'persiapan', 'kesiapan'], array_column($props['langkah'], 'kunci'));
        $this->assertSame(['Jalur', 'Rencana', 'Persiapan', 'Cek'], array_column($props['langkah'], 'judul'));
    }

    /**
     * Kepala trip prototype 09: jenis, H-n, tanggal dengan kata "sampai", jam dalam zona
     * gunung beserta penandanya, dan peserta. Jam rencana tidak dikonversi: ia rencana
     * pendaki dalam waktu setempat gunungnya.
     */
    public function test_the_header_names_the_type_days_left_and_the_plan_in_the_mountain_zone(): void
    {
        $rinjani = Trail::factory()->for(Mountain::factory()->create(['timezone' => 'Asia/Makassar']))->published()->create();
        $trip = $this->trip([], $rinjani);

        $kepala = $this->props($trip)['kepala'];

        $this->assertSame('Camping', $kepala['jenis']);
        $this->assertSame('H-15', $kepala['hari']);
        $this->assertSame('Sab, 10 Okt sampai Min, 11 Okt 2026', $kepala['tanggal']);
        $this->assertSame('07.00 WITA', $kepala['jam']);
        $this->assertSame('3 orang', $kepala['peserta']);
        $this->assertSame(route('trips.edit', $trip), $kepala['ubah']);
        $this->assertNull($kepala['keadaan']);
    }

    public function test_details_left_empty_stay_empty_in_the_header(): void
    {
        $trip = $this->trip(['end_date' => null, 'start_time' => null, 'participants_count' => null, 'planned_date' => '2026-09-25', 'trip_type' => TripType::TEKTOK->value]);

        $kepala = $this->props($trip)['kepala'];

        $this->assertSame('Tektok', $kepala['jenis']);
        $this->assertSame('Hari H', $kepala['hari']);
        $this->assertSame('Jum, 25 Sep 2026', $kepala['tanggal']);
        $this->assertNull($kepala['jam']);
        $this->assertNull($kepala['peserta']);
    }

    /**
     * R-014: trip yang pre-departure check-nya sudah dikonfirmasi tidak menampilkan kata
     * status "Siap berangkat" di kepala; yang tampil hanya H-n dan rencananya.
     */
    public function test_an_upcoming_trip_never_reads_as_ready_to_go(): void
    {
        $trip = $this->trip(['status' => TripStatus::READY_FOR_DEPARTURE->value]);

        $props = $this->props($trip);

        $this->assertNull($props['kepala']['keadaan']);
        $this->assertStringNotContainsString('Siap berangkat', json_encode($props['kepala']));

        $berlangsung = $this->trip(['status' => TripStatus::IN_PROGRESS->value]);
        $kepala = $this->props($berlangsung)['kepala'];

        $this->assertSame('Sedang berlangsung', $kepala['keadaan']);
        $this->assertNull($kepala['hari'], 'Pendakian yang sudah dimulai tidak menghitung mundur.');
        $this->assertNull($kepala['ubah'], 'Rencana yang sudah dimulai tidak dapat diubah lagi.');
    }

    /**
     * Langkah Jalur menautkan rincian jalurnya selama jalur itu masih terbit; jalur yang
     * ditarik tidak ditautkan ke halaman yang menjawab 404.
     */
    public function test_the_trail_step_links_to_the_trail_only_while_it_is_published(): void
    {
        $trail = Trail::factory()->published()->create();
        $trip = $this->trip([], $trail);

        $this->assertSame(route('trails.show', $trail), $this->props($trip)['langkah'][0]['tautan']['href']);

        $trail->update(['archived_at' => now()]);

        $this->assertNull($this->props($trip)['langkah'][0]['tautan']);
    }

    /**
     * Aksi berikutnya (indeks trip, spec v3 bagian 6.9) mengikuti langkah pertama yang belum
     * selesai, atau keadaan pendakiannya.
     */
    public function test_the_next_action_follows_the_first_unfinished_step(): void
    {
        $aksi = function (TripPlan $trip): ?string {
            $trip->load('trail.mountain', 'preparationItems', 'latestReadinessCheck', 'history');

            return TripSteps::aksi($trip, app(TripSteps::class)->for($trip))['label'] ?? null;
        };

        $trip = $this->trip();
        $belum = $this->butir($trip, PreparationStatus::NOT_CONFIRMED);
        $this->assertSame('Lanjutkan persiapan', $aksi($trip));

        $belum->update(['status' => PreparationStatus::CONFIRMED->value]);
        $this->assertSame('Cek kesiapan', $aksi($trip->fresh()));

        ReadinessCheck::create([
            'trip_plan_id' => $trip->id,
            'computed_state' => ReadinessState::READY->value,
            'official_status_snapshot' => ['status' => 'UNKNOWN'],
            'pre_departure_confirmed' => true,
            'computed_at' => now(),
        ]);
        $this->assertSame('Buka ruang kerja', $aksi($trip->fresh()));

        $this->assertSame('Buka mode lapangan', $aksi($this->trip(['status' => TripStatus::IN_PROGRESS->value])));
        $this->assertNull($aksi($this->trip(['status' => TripStatus::CANCELLED->value])));

        $selesai = $this->trip(['status' => TripStatus::COMPLETED->value]);
        HikingHistory::create([
            'user_id' => $selesai->user_id,
            'trip_plan_id' => $selesai->id,
            'trail_id' => $selesai->trail_id,
            'trip_type' => TripType::CAMPING->value,
            'completion_state' => CompletionState::COMPLETED->value,
            'completed_at' => now(),
        ]);
        $this->assertSame('Lihat ringkasan', $aksi($selesai));
    }

    public function test_the_workspace_does_not_grow_with_preparation_items_or_reminders(): void
    {
        $trip = $this->trip();
        $this->actingAs($trip->user);
        $url = route('trips.show', $trip);

        $hitung = function () use ($url): int {
            $jumlah = 0;
            DB::listen(function () use (&$jumlah) {
                $jumlah++;
            });
            $this->get($url)->assertOk();
            DB::getEventDispatcher()->forget('Illuminate\Database\Events\QueryExecuted');

            return $jumlah;
        };

        $this->get($url);
        $sedikit = $hitung();

        for ($i = 0; $i < 12; $i++) {
            $this->butir($trip, $i % 2 ? PreparationStatus::CONFIRMED : PreparationStatus::NOT_CONFIRMED, $i % 3 === 0);
        }
        TripReminder::factory()->for($trip, 'tripPlan')->create(['jenis' => 'H-3']);
        TripReminder::factory()->for($trip, 'tripPlan')->create(['jenis' => 'H-1']);

        $this->assertSame($sedikit, $hitung(), 'Ruang kerja tumbuh bersama jumlah butir atau pengingat.');
    }

    /**
     * Ruang kerja dua kolom mulai 1024 px (spec v3 bagian 5.2): langkah di kiri, isi di kanan.
     * Langkah memakai motif garis rute berujung cincin pos (DESIGN.md bagian Motif identitas),
     * bukan panah.
     */
    public function test_the_workspace_page_keeps_the_steps_beside_the_content_on_wide_screens(): void
    {
        $halaman = File::get(resource_path('js/pages/Trips/Show.tsx'));
        $langkah = File::get(resource_path('js/components/trips/LangkahTrip.tsx'));

        $this->assertStringContainsString('lg:grid-cols-[18rem_minmax(0,1fr)]', $halaman);
        $this->assertStringContainsString('<KepalaTrip kepala={kepala} />', $halaman);
        $this->assertStringContainsString('<LangkahTrip langkah={langkah} />', $halaman);
        $this->assertStringNotContainsString('→', $langkah);
        $this->assertStringContainsString("aria-current={l.keadaan === 'berikutnya' ? 'step' : undefined}", $langkah);
    }
}
```

`tests/Feature/TripPlanDetailTest.php`:

1. Ganti:

```php
        $this->assertSame('Kumpul di basecamp.', $trip->notes);

        $this->get(route('trips.show', $trip))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('langkah.1.keterangan', '17 Okt 2026 - 18 Okt 2026 · Camping · 2 orang')
            ->where('trip.peserta', '2 orang')
            ->where('trip.sampai', '18 Okt 2026'));
    }
```

dengan:

```php
        $this->assertSame('Kumpul di basecamp.', $trip->notes);

        // Kepala ruang kerja W4 (spec v3 bagian 6.10) menggantikan baris tanggal lama: rentang
        // ditulis dengan "sampai" (spec v3 bagian 8.8), dan langkah Rencana memakai rentang yang
        // sama supaya satu halaman tidak menulis tanggal dengan dua cara.
        $this->get(route('trips.show', $trip))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('langkah.1.keterangan', 'Sab, 17 Okt sampai Min, 18 Okt 2026 · Camping · 2 orang')
            ->where('kepala.peserta', '2 orang')
            ->where('kepala.tanggal', 'Sab, 17 Okt sampai Min, 18 Okt 2026'));
    }
```

2. Ganti:

```php
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('trip.peserta', null)
                ->where('trip.sampai', null)
                ->where('kelola.bolehUbah', true)
```

dengan:

```php
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('kepala.peserta', null)
                ->where('kepala.tanggal', fn (string $tanggal) => ! str_contains($tanggal, 'sampai'))
                ->where('kelola.bolehUbah', true)
```

`tests/Feature/TripReactPagesTest.php`:

1. Ganti:

```php
    }

    public function test_the_step_list_points_at_the_next_step(): void
```

dengan:

```php
    }

    /**
     * Empat langkah prototype 08 sampai 10 (spec v3 bagian 6.9, W4 Tugas 1), bukan enam: "Hike
     * mode" dan "Selesaikan trip" pindah ke kartu Tindakan, jadi niat test ini (daftar
     * langkah menunjuk langkah berikutnya) tetap, hanya jumlahnya yang berubah.
     */
    public function test_the_step_list_points_at_the_next_step(): void
```

2. Ganti:

```php
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('langkah', 6)
                ->where('langkah.0.keadaan', 'selesai')
```

dengan:

```php
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('langkah', 4)
                ->where('langkah.0.keadaan', 'selesai')
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='TripWorkspace|TripReactPages|TripPlanDetail|StaleReadiness|AmbientFit|TripOwnership|TripReminder'`
Expected: FAIL. Pada simulasi rencana: 94 test, 84 lulus, 5 gagal, 5 error. Yang merah: tujuh test `TripWorkspaceTest` (props `kepala` dan `TripSteps::aksi()` belum ada, langkah masih enam, grid dua kolom belum ada), dua test `TripPlanDetailTest` (rentang masih "17 Okt 2026 - 18 Okt 2026", `kepala.peserta` belum ada), dan `TripReactPagesTest::test_the_step_list_points_at_the_next_step` (6 langkah, bukan 4).

- [ ] **Step 3: Tulis implementasinya.** Kelas PHP baru dibuat dengan `make:class` lalu isinya diganti persis;
berkas TypeScript dibuat langsung.

Buat `app/Services/Trips/KepalaTrip.php`:

```php
<?php

namespace App\Services\Trips;

use App\Enums\TripStatus;
use App\Models\TripPlan;
use App\Support\Timezone;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Kepala satu trip untuk ruang kerja, persiapan, dan cek kesiapan (spec v3 bagian 6.10,
 * prototype 09): jenis, sisa hari, nama, tanggal, jam, peserta, dan tautan "Ubah". Seluruh
 * teks diformat di sini, jadi ketiga halaman menulis rencana yang sama dengan cara yang sama.
 *
 * Status trip hanya disebut untuk trip yang sudah dimulai, selesai, atau dibatalkan. Trip
 * yang belum dimulai cukup dibaca dari H-n dan langkahnya: label "Siap berangkat" di samping
 * nama trip terbaca sebagai jaminan, dan itu yang dilarang R-014.
 */
final class KepalaTrip
{
    public function __construct(private readonly TripLifecycle $lifecycle) {}

    /**
     * Membutuhkan relasi trail.mountain sudah dimuat.
     *
     * @return array{id: int, nama: string, jalur: string, gunung: string, jenis: string, hari: string|null, tanggal: string, jam: string|null, peserta: string|null, keadaan: string|null, ubah: string|null}
     */
    public function untuk(TripPlan $trip): array
    {
        $zona = Timezone::forTrail($trip->trail);
        $belumDimulai = $trip->status->isActive() && $trip->status !== TripStatus::IN_PROGRESS;

        return [
            'id' => $trip->id,
            'nama' => $trip->name,
            'jalur' => $trip->trail->name,
            'gunung' => $trip->trail->mountain->name,
            'jenis' => $trip->trip_type->labelPendek(),
            'hari' => $belumDimulai ? self::hariMenuju($trip->planned_date, $zona) : null,
            'tanggal' => self::rentang($trip->planned_date, $trip->end_date),
            // Jam ini rencana pendaki dalam waktu setempat gunungnya, bukan instan UTC, jadi
            // tidak dikonversi; penandanya tetap disebut (§93).
            'jam' => $trip->start_time ? Carbon::parse($trip->start_time)->format('H.i').' '.Timezone::label($zona) : null,
            'peserta' => $trip->participants_count !== null ? $trip->participants_count.' orang' : null,
            'keadaan' => $belumDimulai ? null : $trip->status->label(),
            'ubah' => $this->lifecycle->editRefusal($trip) === null ? route('trips.edit', $trip) : null,
        ];
    }

    /**
     * "H-15", "Hari H", atau null untuk tanggal yang sudah lewat, dihitung dari tanggal hari ini
     * di zona gunungnya.
     */
    public static function hariMenuju(CarbonInterface $tanggal, string $zona): ?string
    {
        $hari = (int) Carbon::parse(Carbon::now($zona)->toDateString())->diffInDays($tanggal->copy()->startOfDay(), false);

        return match (true) {
            $hari < 0 => null,
            $hari === 0 => 'Hari H',
            default => 'H-'.$hari,
        };
    }

    /**
     * Tanggal rencana; rentang ditulis dengan kata "sampai" (spec v3 bagian 8.8), tahunnya
     * sekali di akhir.
     */
    public static function rentang(CarbonInterface $mulai, ?CarbonInterface $selesai): string
    {
        if ($selesai === null || $selesai->isSameDay($mulai)) {
            return $mulai->translatedFormat('D, j M Y');
        }

        return $mulai->translatedFormat($mulai->year === $selesai->year ? 'D, j M' : 'D, j M Y').' sampai '.$selesai->translatedFormat('D, j M Y');
    }
}
```

Buat `resources/js/components/trips/KepalaTrip.tsx`:

```tsx
import { Lencana } from '@/components/ui/Lencana';
import { Tautan } from '@/components/ui/Tautan';
import type { KepalaTripData } from './types';

/**
 * Isi kepala satu trip di bawah judul halaman (prototype 09): jenis dan sisa hari sebagai
 * lencana, jalur dan gunungnya, lalu tanggal, jam, dan peserta sebagai butir terpisah. Tanpa
 * titik tengah: baris ini meta, bukan baris ukuran padat (spec v3 bagian 8.8). Yang belum
 * diisi pendaki tidak ditulis. "Ubah rencana" hanya tampil selama server mengizinkannya.
 */
export function KepalaTrip({ kepala }: { kepala: KepalaTripData }) {
    return (
        <div className="mt-2 space-y-2">
            <p className="flex flex-wrap items-center gap-2">
                <Lencana nada="merek">{kepala.jenis}</Lencana>
                {kepala.hari ? (
                    <Lencana>
                        <span data-angka>{kepala.hari}</span>
                    </Lencana>
                ) : null}
                {kepala.keadaan ? <Lencana>{kepala.keadaan}</Lencana> : null}
            </p>
            <p className="text-isi text-secondary">
                {kepala.jalur}, {kepala.gunung}
            </p>
            <p className="flex flex-wrap gap-x-4 gap-y-1 text-kecil text-secondary">
                <span data-angka>{kepala.tanggal}</span>
                {kepala.jam ? <span data-angka>Mulai {kepala.jam}</span> : null}
                {kepala.peserta ? <span data-angka>{kepala.peserta}</span> : null}
            </p>
            {kepala.ubah ? (
                <Tautan
                    href={kepala.ubah}
                    className="inline-flex min-h-11 items-center text-kecil font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                >
                    Ubah rencana
                </Tautan>
            ) : null}
        </div>
    );
}
```

`app/Enums/TripType.php`:

1. Ganti:

```php
    }

    public function typicalMaxDurationMinutes(): int
```

dengan:

```php
    }

    /**
     * Nama pendek untuk kontrol segmen dan kepala trip (prototype 08 dan 09); lamanya ditulis
     * terpisah oleh lamaHari(), jadi "pulang hari" tidak diulang di samping "1 hari".
     */
    public function labelPendek(): string
    {
        return match ($this) {
            self::TEKTOK => 'Tektok',
            self::CAMPING => 'Camping',
            self::MULTI_DAY => 'Multi-hari',
        };
    }

    /**
     * Lama bawaan dalam hari (spec v3 bagian 6.8): mengisi tanggal selesai begitu tanggal
     * naik dipilih. Multi-hari boleh lebih panjang; pendaki memilih tanggal turunnya sendiri.
     */
    public function lamaHari(): int
    {
        return match ($this) {
            self::TEKTOK => 1,
            self::CAMPING => 2,
            self::MULTI_DAY => 3,
        };
    }

    public function typicalMaxDurationMinutes(): int
```

`app/Http/Controllers/Trips/TripController.php`:

1. Ganti:

```php
use App\Services\TrailFitService;
use App\Services\Trips\TripLifecycle;
```

dengan:

```php
use App\Services\TrailFitService;
use App\Services\Trips\KepalaTrip;
use App\Services\Trips\TripLifecycle;
```

2. Ganti:

```php
    }

    public function show(Request $request, TripPlan $trip, PermitService $permits, TrailFitService $trailFit, TripSteps $steps, TripLifecycle $lifecycle, TripReminderScheduler $pengingat): Response
    {
```

dengan:

```php
    }

    public function show(Request $request, TripPlan $trip, PermitService $permits, TrailFitService $trailFit, TripSteps $steps, TripLifecycle $lifecycle, TripReminderScheduler $pengingat, KepalaTrip $kepala): Response
    {
```

3. Ganti:

```php
        $user = $request->user();

        // Trip sudah punya goal, jadi kecocokannya yang tajam, bukan yang dasar.
        $fit = $user->hasCompletedProfile()
            ? ($trailFit->forTrails($user, EloquentCollection::make([$trip->trail]), $trip->hikingGoal)[$trip->trail->id] ?? null)
            : null;

        // Dihitung ulang setiap halaman dibuka, bukan disimpan: jawabannya berubah seiring
```

dengan:

```php
        $user = $request->user();

        // Kecocokan untuk rencana ini (spec v3 bagian 6.4): tujuan tersimpan trip, atau jenis
        // dan tanggal tripnya sebagai tujuan sementara, sama dengan halaman penilaian kecocokan.
        $fit = $user->hasCompletedProfile()
            ? ($trailFit->forTrails($user, EloquentCollection::make([$trip->trail]), $trip->tujuanPenilaian())[$trip->trail->id] ?? null)
            : null;
        $jalurTerbit = $trip->trail->is_published && $trip->trail->archived_at === null;

        // Dihitung ulang setiap halaman dibuka, bukan disimpan: jawabannya berubah seiring
```

4. Ganti:

```php
            'title' => $trip->name,
            'trip' => [
                'id' => $trip->id,
                'name' => $trip->name,
                'trail' => $trip->trail->name,
                'mountain' => $trip->trail->mountain->name,
                'tanggal' => $trip->planned_date->translatedFormat('d M Y'),
                'sampai' => $trip->end_date !== null && ! $trip->end_date->isSameDay($trip->planned_date)
                    ? $trip->end_date->translatedFormat('d M Y')
                    : null,
                'peserta' => $trip->participants_count !== null ? $trip->participants_count.' orang' : null,
                // Jam ini rencana pengguna, bukan instan UTC, jadi tidak dikonversi.
                // Penandanya tetap disebut: "mulai 06:00" ambigu bagi pendaki Jakarta
                // yang merencanakan Rinjani.
                'mulai' => $trip->start_time
                    ? Carbon::parse($trip->start_time)->format('H:i').' '.Timezone::label(Timezone::forTrail($trip->trail))
                    : null,
                'tipe' => $trip->trip_type->label(),
                'status' => $trip->status->label(),
                'aktif' => $trip->status->isActive(),
```

dengan:

```php
            'title' => $trip->name,
            'kepala' => $kepala->untuk($trip),
            'trip' => [
                'id' => $trip->id,
                'aktif' => $trip->status->isActive(),
```

5. Ganti:

```php
            ],
            // Nadanya mengikuti §43: sistem menyebut aturan dan penyelenggaranya, tidak
```

dengan:

```php
            ],
            // Penilaian lengkap hanya untuk jalur yang masih terbit (halaman itu menjawab 404
            // untuk jalur draf dan arsip).
            'penilaian' => $jalurTerbit ? route('trails.fit', ['trail' => $trip->trail, 'trip' => $trip->id]) : null,
            // Nadanya mengikuti §43: sistem menyebut aturan dan penyelenggaranya, tidak
```

Tulis ulang `app/Services/Trips/TripSteps.php` utuh:

```php
<?php

namespace App\Services\Trips;

use App\Enums\OfficialStatusValue;
use App\Enums\PreparationStatus;
use App\Enums\TripStatus;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use Illuminate\Support\Collection;

/**
 * Rencana sebagai empat langkah prototype 08 sampai 10 (spec v3 bagian 6.9): Jalur, Rencana,
 * Persiapan, Cek. Mana yang sudah selesai dan apa langkah berikutnya (features/04).
 *
 * Langkah adalah pemandu, bukan gerbang: pendaki tetap boleh memulai pendakian sebelum
 * persiapannya lengkap. Memulai, menyelesaikan, dan membatalkan trip adalah aksi di kartu
 * Tindakan ruang kerja, bukan langkah, supaya satu aksi punya satu tempat.
 *
 * Langkah "Cek" hanya dihitung selesai bila pre-departure check dikonfirmasi DAN vonisnya
 * masih mewakili keadaan. Tanda selesai di samping vonis basi akan terbaca sebagai "sudah
 * aman", dan itu persis yang dilarang R-014.
 */
class TripSteps
{
    public const SELESAI = 'selesai';

    public const BERIKUTNYA = 'berikutnya';

    public const BELUM = 'belum';

    /**
     * Membutuhkan relasi trail.mountain, preparationItems, dan latestReadinessCheck sudah
     * dimuat. Indeks trip mengoper status resmi sekarang yang dimuat sekali untuk semua trip,
     * supaya pemeriksaan vonis basi tidak menjadi satu kueri per trip.
     *
     * @return array<int, array{keadaan: string, kunci: string, judul: string, keterangan: string, catatan: string|null, peringatan: bool, tautan: array{href: string, teks: string}|null}>
     */
    public function for(TripPlan $trip, ?OfficialStatusValue $statusSekarang = null): array
    {
        $persiapan = self::hitungPersiapan($trip->preparationItems);
        $kesiapan = $this->kesiapan($trip, $statusSekarang);
        $jalurTerbit = $trip->trail->is_published && $trip->trail->archived_at === null;

        $langkah = [
            [
                'kunci' => 'jalur',
                'judul' => 'Jalur',
                'selesai' => true,
                'keterangan' => $trip->trail->name.' - '.$trip->trail->mountain->name,
                'catatan' => null,
                'peringatan' => false,
                // Jalur yang ditarik dari katalog tidak ditautkan ke halaman yang menjawab 404.
                'tautan' => $jalurTerbit ? ['href' => route('trails.show', $trip->trail), 'teks' => 'Lihat jalur'] : null,
            ],
            [
                'kunci' => 'rencana',
                'judul' => 'Rencana',
                'selesai' => true,
                'keterangan' => self::teksRencana($trip),
                'catatan' => null,
                'peringatan' => false,
                // "Ubah rencana" tinggal di kepala trip (KepalaTrip), satu tempat untuk satu
                // aksi, selama rencana masih dapat diubah.
                'tautan' => null,
            ],
            [
                'kunci' => 'persiapan',
                'judul' => 'Persiapan',
                'selesai' => $persiapan['berlaku'] > 0 && $persiapan['dikonfirmasi'] === $persiapan['berlaku'],
                'keterangan' => self::teksPersiapan($persiapan),
                'catatan' => null,
                'peringatan' => false,
                'tautan' => ['href' => route('trips.preparation', $trip), 'teks' => 'Buka daftar persiapan'],
            ],
            [
                'kunci' => 'kesiapan',
                'judul' => 'Cek',
                'selesai' => $kesiapan['selesai'],
                'keterangan' => $kesiapan['keterangan'],
                'catatan' => $kesiapan['catatan'],
                'peringatan' => $kesiapan['peringatan'],
                'tautan' => ['href' => route('trips.readiness', $trip), 'teks' => 'Cek kesiapan'],
            ],
        ];

        // Hanya trip yang belum dimulai yang punya langkah berikutnya. Trip yang berlangsung,
        // selesai, atau dibatalkan tidak menunjuk ke langkah perencanaan mana pun.
        $sudahDitunjuk = ! $trip->status->isActive() || $trip->status === TripStatus::IN_PROGRESS;
        $hasil = [];

        foreach ($langkah as $l) {
            $keadaan = self::BELUM;

            if ($l['selesai']) {
                $keadaan = self::SELESAI;
            } elseif (! $sudahDitunjuk) {
                $keadaan = self::BERIKUTNYA;
                $sudahDitunjuk = true;
            }

            unset($l['selesai']);
            $hasil[] = ['keadaan' => $keadaan] + $l;
        }

        return $hasil;
    }

    /**
     * Aksi utama berikutnya untuk kartu trip di indeks (spec v3 bagian 6.9): langkah pertama
     * yang belum selesai, atau keadaan pendakiannya. Trip selesai menautkan ringkasannya bila
     * riwayatnya ada (relasi history harus dimuat); trip batal tidak punya aksi berikutnya.
     *
     * @param  array<int, array{keadaan: string, kunci: string}>  $langkah  hasil for()
     * @return array{label: string, href: string}|null
     */
    public static function aksi(TripPlan $trip, array $langkah): ?array
    {
        if ($trip->status === TripStatus::IN_PROGRESS) {
            return ['label' => 'Buka mode lapangan', 'href' => route('trips.hike', $trip)];
        }

        if (! $trip->status->isActive()) {
            return $trip->history !== null ? ['label' => 'Lihat ringkasan', 'href' => route('history.summary', $trip)] : null;
        }

        return match (collect($langkah)->firstWhere('keadaan', self::BERIKUTNYA)['kunci'] ?? null) {
            'persiapan' => ['label' => 'Lanjutkan persiapan', 'href' => route('trips.preparation', $trip)],
            'kesiapan' => ['label' => 'Cek kesiapan', 'href' => route('trips.readiness', $trip)],
            default => ['label' => 'Buka ruang kerja', 'href' => route('trips.show', $trip)],
        };
    }

    /**
     * Ringkasan rencana: rentang tanggal, tipe, dan peserta. Yang belum disebut pendaki
     * tidak ditulis sama sekali, bukan diisi tebakan (R-007). Rentangnya ditulis sama dengan
     * kepala trip, supaya satu halaman tidak menulis tanggal dengan dua cara.
     */
    public static function teksRencana(TripPlan $trip): string
    {
        return implode(' · ', array_filter([
            KepalaTrip::rentang($trip->planned_date, $trip->end_date),
            $trip->trip_type->label(),
            $trip->participants_count !== null ? $trip->participants_count.' orang' : null,
        ]));
    }

    /**
     * Hitungan butir persiapan dari koleksi yang sudah dimuat. "Tidak berlaku" tidak
     * ikut dihitung, sama seperti TripPlan::preparationCompletionPercent().
     *
     * @param  Collection<int, TripPreparationItem>  $items
     * @return array{dikonfirmasi: int, berlaku: int}
     */
    public static function hitungPersiapan(Collection $items): array
    {
        $berlaku = $items->where('status', '!=', PreparationStatus::NOT_APPLICABLE);

        return [
            'dikonfirmasi' => $berlaku->where('status', PreparationStatus::CONFIRMED)->count(),
            'berlaku' => $berlaku->count(),
        ];
    }

    /**
     * Kemajuan sebagai hitungan, bukan persentase: "3 dari 8" terbaca sebagai sisa
     * pekerjaan, sedangkan "38%" mudah terbaca sebagai nilai kesiapan (R-005).
     *
     * @param  array{dikonfirmasi: int, berlaku: int}  $persiapan
     */
    public static function teksPersiapan(array $persiapan): string
    {
        if ($persiapan['berlaku'] === 0) {
            return 'Belum ada item persiapan.';
        }

        return sprintf('%d dari %d item dikonfirmasi', $persiapan['dikonfirmasi'], $persiapan['berlaku']);
    }

    /**
     * Vonis tersimpan tidak pernah disajikan sebagai keadaan sekarang ketika status
     * resmi jalurnya sudah berubah sejak penilaian. Menyebut label lamanya di samping
     * pengakuan itu memberi pendaki dua jawaban yang bertentangan, jadi labelnya
     * diganti, bukan didampingi.
     *
     * @return array{selesai: bool, keterangan: string, catatan: string|null, peringatan: bool}
     */
    private function kesiapan(TripPlan $trip, ?OfficialStatusValue $statusSekarang): array
    {
        if ($trip->trailIsWithdrawn()) {
            return [
                'selesai' => false,
                'keterangan' => 'Jalur ditarik dari katalog',
                'catatan' => 'Keterangan jalur ini tidak lagi diperbarui di sini. Tanyakan keadaannya langsung kepada pengelola kawasan.',
                'peringatan' => true,
            ];
        }

        if ($trip->readinessIsStale($statusSekarang)) {
            return [
                'selesai' => false,
                'keterangan' => 'Status jalur berubah, perlu dinilai ulang',
                'catatan' => 'Penilaian terakhir dibuat ketika status resminya masih berbeda.',
                'peringatan' => true,
            ];
        }

        $terakhir = $trip->latestReadinessCheck;
        $dikonfirmasi = (bool) $terakhir?->pre_departure_confirmed;

        return [
            'selesai' => $dikonfirmasi,
            'keterangan' => $terakhir?->computed_state->label() ?? 'Belum dinilai',
            'catatan' => $dikonfirmasi ? 'Pre-departure check sudah dikonfirmasi.' : null,
            'peringatan' => false,
        ];
    }
}
```

Tulis ulang `resources/js/components/trips/LangkahTrip.tsx` utuh:

```tsx
import { Icon } from '@/components/ui/Icon';
import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';
import type { KeadaanLangkah, Langkah } from './types';

const tanda: Record<KeadaanLangkah, { teks: string; cincin: string }> = {
    selesai: { teks: 'Selesai', cincin: 'border-brand-700 bg-brand-700 text-on-brand' },
    berikutnya: { teks: 'Langkah berikutnya', cincin: 'border-brand-700 bg-surface text-brand-ink' },
    belum: { teks: 'Belum', cincin: 'border-control bg-surface text-muted' },
};

/**
 * Empat langkah rencana sebagai garis rute berujung cincin pos (DESIGN.md bagian Motif
 * identitas): satu garis menurun yang menyambung cincin bernomor, dan cincin langkah yang
 * selesai terisi dengan centang. Keadaan tiap langkah dibaca dari bentuk dan kata, bukan dari
 * warna saja; langkah berikutnya diberi aria-current="step" dan kata "Langkah berikutnya".
 */
export function LangkahTrip({ langkah }: { langkah: Langkah[] }) {
    return (
        <ol>
            {langkah.map((l, i) => {
                const t = tanda[l.keadaan];
                const terakhir = i === langkah.length - 1;

                return (
                    <li key={l.kunci} aria-current={l.keadaan === 'berikutnya' ? 'step' : undefined} className="relative flex gap-4 pb-6 last:pb-0">
                        {!terakhir ? <span aria-hidden="true" className="absolute left-4 top-9 h-[calc(100%-2.25rem)] w-0.5 bg-brand-ink" /> : null}
                        <span aria-hidden="true" className={kelas('relative flex size-8 shrink-0 items-center justify-center rounded-full border-2 text-kecil font-bold tabular-nums', t.cincin)}>
                            {l.keadaan === 'selesai' ? <Icon name="check" size={16} /> : i + 1}
                        </span>
                        <div className="min-w-0 flex-1 pt-1">
                            <p className="flex flex-wrap items-baseline gap-x-2">
                                <span className="text-isi font-bold text-primary">{l.judul}</span>
                                <span className={kelas('text-meta', l.keadaan === 'berikutnya' ? 'font-bold text-brand-ink' : 'text-muted')}>{t.teks}</span>
                            </p>
                            <p className={kelas('mt-0.5 break-words text-kecil', l.peringatan ? 'font-bold text-warn-900' : 'text-secondary')}>{l.keterangan}</p>
                            {l.catatan ? <p className="mt-1 text-meta text-secondary">{l.catatan}</p> : null}
                            {l.tautan ? (
                                <Tautan
                                    href={l.tautan.href}
                                    className="mt-1 inline-flex min-h-11 items-center text-kecil font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                                >
                                    {l.tautan.teks}
                                </Tautan>
                            ) : null}
                        </div>
                    </li>
                );
            })}
        </ol>
    );
}
```

`resources/js/components/trips/types.ts`:

1. Ganti:

```ts
    fit: FitRingkas | null;
}
```

dengan:

```ts
    fit: FitRingkas | null;
}

/** Kepala satu trip dari App\Services\Trips\KepalaTrip: seluruh teks sudah diformat server. */
export interface KepalaTripData {
    id: number;
    nama: string;
    jalur: string;
    gunung: string;
    jenis: string;
    hari: string | null;
    tanggal: string;
    jam: string | null;
    peserta: string | null;
    keadaan: string | null;
    ubah: string | null;
}
```

Tulis ulang `resources/js/pages/Trips/Show.tsx` utuh:

```tsx
import { router, useForm, usePage } from '@inertiajs/react';
import { useState, type FormEvent, type ReactNode } from 'react';
import { FitTrip } from '@/components/trips/FitTrip';
import { KepalaTrip } from '@/components/trips/KepalaTrip';
import { LangkahTrip } from '@/components/trips/LangkahTrip';
import { PengingatTrip, type PengingatHalaman } from '@/components/trips/PengingatTrip';
import { HapusRencana, type KelolaRencana } from '@/components/trips/rencana/HapusRencana';
import type { FitRingkas, KepalaTripData, Langkah, Opsi } from '@/components/trips/types';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { InputError, Label, Select, Textarea } from '@/components/ui/Field';
import { PageHeader } from '@/components/ui/PageHeader';
import { Tautan } from '@/components/ui/Tautan';
import { AppLayout } from '@/layouts/AppLayout';
import { cancel, complete, hike, index as tripsIndex, start } from '@/routes/trips';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    kepala: KepalaTripData;
    trip: {
        id: number;
        aktif: boolean;
        berlangsung: boolean;
        catatan: string | null;
    };
    langkah: Langkah[];
    fit: FitRingkas | null;
    penilaian: string | null;
    izin: { ditutup: boolean; pesan: string; tautanPemesanan: string | null } | null;
    hasilPendakian: string | null;
    completionStates: Opsi[];
    penolakan: string | null;
    pengingat: PengingatHalaman;
    kelola: KelolaRencana;
}

const tautanTeks =
    'inline-flex min-h-11 items-center text-kecil font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600';

/**
 * Ruang kerja satu trip (spec v3 bagian 6.9): empat langkah prototype di kolom kiri mulai
 * 1024 px dan di atas isi di ponsel, lalu kecocokan untuk rencana ini, izin, pengingat, dan
 * aksi yang mengubah status trip. Langkah memandu, tidak mengunci.
 */
export default function Show() {
    const { kepala, trip, langkah, fit, penilaian, izin, hasilPendakian, completionStates, penolakan, pengingat, kelola, flash } = usePage<Props>().props;
    const [memulai, setMemulai] = useState(false);
    const [membatalkan, setMembatalkan] = useState(false);
    const selesai = useForm({ completion_state: 'COMPLETED', personal_notes: '' });

    const mulai = () => {
        setMemulai(true);
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

    return (
        <div className="py-6">
            <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <PageHeader induk={{ href: tripsIndex.url(), label: 'Trip' }} title={kepala.nama}>
                    <KepalaTrip kepala={kepala} />
                </PageHeader>

                {flash.status ? (
                    <Alert variant="success" className="mb-6">
                        {flash.status}
                    </Alert>
                ) : null}
                {penolakan ? (
                    <Alert variant="warning" className="mb-6">
                        {penolakan}
                    </Alert>
                ) : null}

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-[18rem_minmax(0,1fr)] lg:items-start">
                    <section aria-labelledby="langkah-trip" className="rounded-lg border border-subtle bg-surface p-5">
                        <h2 id="langkah-trip" className="text-bagian text-primary">
                            Langkah
                        </h2>
                        <div className="mt-4">
                            <LangkahTrip langkah={langkah} />
                        </div>
                    </section>

                    <div className="min-w-0 space-y-6">
                        {fit ? (
                            <Card title="Kecocokan jalur">
                                <FitTrip fit={fit} className="mb-6" />
                                {penilaian ? (
                                    <Tautan href={penilaian} className={tautanTeks}>
                                        Lihat penilaian lengkap
                                    </Tautan>
                                ) : null}
                            </Card>
                        ) : null}

                        {/*
                            Jendela pemesanan bergerak relatif terhadap tanggal yang sudah dipilih.
                            Nadanya mengikuti §43: menyebut aturan dan penyelenggaranya, tidak pernah
                            menyatakan izinnya sudah beres atau belum.
                        */}
                        {izin ? (
                            <Card title="Izin pendakian">
                                <p className={izin.ditutup ? 'text-isi font-bold text-warn-900' : 'text-isi text-secondary'}>{izin.pesan}</p>
                                {izin.tautanPemesanan ? (
                                    <a href={izin.tautanPemesanan} rel="noopener noreferrer" target="_blank" className={tautanTeks}>
                                        Pesan di sistem resmi
                                    </a>
                                ) : null}
                            </Card>
                        ) : null}

                        {/* FR-24. Trip yang sudah lewat tanpa pengingat apa pun tidak mendapat kartu:
                            sakelarnya pasti ditolak server dan daftarnya kosong. */}
                        {pengingat.dapatDiatur || pengingat.daftar.length > 0 ? <PengingatTrip tripId={trip.id} pengingat={pengingat} /> : null}

                        {trip.catatan ? (
                            <Card title="Catatan">
                                <p className="whitespace-pre-line break-words text-isi text-secondary">{trip.catatan}</p>
                            </Card>
                        ) : null}

                        {hasilPendakian ? (
                            <Card title="Pendakian ini sudah selesai">
                                <Button href={hasilPendakian}>Lihat hasil pendakian</Button>
                            </Card>
                        ) : null}

                        {trip.aktif ? (
                            <>
                                <Card title="Tindakan">
                                    <div className="flex flex-wrap gap-3">
                                        {trip.berlangsung ? (
                                            <Button href={hike.url(trip.id)}>Buka mode lapangan</Button>
                                        ) : (
                                            <Button onClick={mulai} processing={memulai}>
                                                Mulai pendakian
                                            </Button>
                                        )}
                                        <Button variant="secondary" size="sm" onClick={batalkan} processing={membatalkan}>
                                            Batalkan trip
                                        </Button>
                                    </div>
                                </Card>

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
                            </>
                        ) : null}

                        <HapusRencana tripId={trip.id} kelola={kelola} />
                    </div>
                </div>
            </div>
        </div>
    );
}

Show.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
```

`tests/Pratinjau/HalamanPratinjauTest.php`:

1. Ganti:

```php
        $this->tulisPratinjau('kecocokan', $this->get(self::ASAL.'/trails/gede-via-cibodas/kecocokan?trip='.$trip->id), 'Trails/Fit');
    }
```

dengan:

```php
        $this->tulisPratinjau('kecocokan', $this->get(self::ASAL.'/trails/gede-via-cibodas/kecocokan?trip='.$trip->id), 'Trails/Fit');
    }

    /**
     * Ruang kerja trip W4 (spec v3 bagian 6.9; tidak ada di prototype): trip camping Budi ke
     * Gede via Cibodas dengan kepala trip dan empat langkah.
     */
    public function test_ruang_kerja(): void
    {
        $budi = $this->sebagaiBudi();
        $trip = TripPlan::where('user_id', $budi->id)->where('status', TripStatus::PLANNED->value)->sole();

        $this->tulisPratinjau('ruang-kerja', $this->get(self::ASAL.'/trips/'.$trip->id), 'Trips/Show');
    }
```

- [ ] **Step 4: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Enums/TripType.php app/Services/Trips/KepalaTrip.php app/Services/Trips/TripSteps.php app/Http/Controllers/Trips/TripController.php tests/Feature/TripWorkspaceTest.php tests/Feature/TripReactPagesTest.php tests/Feature/TripPlanDetailTest.php tests/Pratinjau/HalamanPratinjauTest.php`, lalu `php artisan test --compact --filter='TripWorkspace|TripReactPages|TripPlanDetail|StaleReadiness|AmbientFit|TripOwnership|TripReminder'`
Expected: PASS. Pada simulasi rencana: 94 test lulus.

- [ ] **Step 5: Suite, tipe, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 1.773 test, 1.771 lulus, 2 dilewati (dasar 1.764).

Run: `npx tsc --noEmit`
Expected: keluar tanpa keluaran galat.

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `php artisan test --compact --testsuite=Pratinjau`
Expected: build `✓ built`; Pratinjau lulus termasuk `test_ruang_kerja`. Pada simulasi rencana: 15 test lulus (dasar 14).

- [ ] **Step 6: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 2: Indeks trip

Induk W4 butir 1; spec bagian 6.9; ruling W4-R6 dan W4-R7. `/trips` lewat kelas halaman `IndeksTrip`: kartu utama
trip berlangsung atau trip terdekat, dengan langkah dan aksi berikutnya, lalu kelompok Berlangsung, Mendatang, dan
Selesai (lima terbaru, sisanya di Progres). Keadaan kosong spec bagian 6.9 dengan satu aksi utama. Kueri tetap, hanya
trip milik pembacanya, tanpa skor dan tanpa status "Siap berangkat".

**Files:**
- Create: `app/Services/Trips/IndeksTrip.php`, `resources/js/components/trips/KartuTrip.tsx`
- Modify: `app/Http/Controllers/Trips/TripController.php`, `app/Http/Resources/TripRowResource.php`, `resources/js/components/trips/types.ts`, `resources/js/pages/Trips/Index.tsx`
- Test: `tests/Feature/TripIndexPageTest.php` (baru), `tests/Feature/AmbientFitTest.php`, `tests/Feature/StaleReadinessTest.php`

**Interfaces:**
- Consumes: `KepalaTrip::rentang()` dan `hariMenuju()`, `TripSteps::for()` dan `aksi()` (Tugas 1), `OfficialStatusService::effectiveStatusesForTrails()`, `TrailFitService::forTrails()`, `FitTrip`, `Lencana`, `EmptyState` jenis `pertama`, rute bertipe `trails.saved` dan `trips.create`.
- Produces:
  - `App\Services\Trips\IndeksTrip::props(User $user, Request $request): array{utama, kelompok, selesai}` dan konstanta `IndeksTrip::BATAS_SELESAI` (5);
  - `TripRowResource(TripPlan $trip, array $langkah, ?TrailFitSummary $fit = null, ?OfficialStatusValue $statusJalur = null)` dengan kunci `id`, `nama`, `url`, `jalur`, `gunung`, `jenis`, `tanggal`, `hari`, `keadaan`, `fase`, `aksi`, `kesiapan`, `fit`;
  - TypeScript `BarisTrip` (bentuk baru) dan `KelompokTrip`; komponen `KartuTrip({ kartu, utama })`.

- [ ] **Step 1: Tulis test yang gagal.**

Buat `tests/Feature/TripIndexPageTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\CompletionState;
use App\Enums\PreparationCategory;
use App\Enums\PreparationStatus;
use App\Enums\ReadinessState;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Models\HikingHistory;
use App\Models\ReadinessCheck;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Indeks trip (spec v3 bagian 6.9, rencana induk W4 butir 1, W4 Tugas 2): kartu utama trip
 * terdekat dengan fase dan aksi berikutnya, lalu kelompok Berlangsung, Mendatang, dan Selesai
 * (ke Progres). Hanya trip milik pembacanya; tanpa skor; kueri tidak tumbuh bersama jumlah
 * trip.
 *
 * Waktu dibekukan pada 25 September 2026 pukul 10.00 WIB.
 */
class TripIndexPageTest extends TestCase
{
    use RefreshDatabase;

    private User $pendaki;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-09-25 03:00:00', 'UTC'));
        $this->pendaki = User::factory()->create();
    }

    private function trip(string $tanggal, TripStatus $status = TripStatus::PLANNED, ?User $pemilik = null): TripPlan
    {
        return TripPlan::factory()->create([
            'user_id' => ($pemilik ?? $this->pendaki)->id,
            'trail_id' => Trail::factory()->published()->create()->id,
            'name' => 'Trip '.$tanggal.' '.$status->value,
            'planned_date' => $tanggal,
            'trip_type' => TripType::CAMPING->value,
            'status' => $status->value,
        ]);
    }

    private function butir(TripPlan $trip, PreparationStatus $status): void
    {
        TripPreparationItem::create([
            'trip_plan_id' => $trip->id,
            'category' => PreparationCategory::EQUIPMENT->value,
            'label' => 'Headlamp '.fake()->unique()->word(),
            'is_critical' => true,
            'status' => $status->value,
        ]);
    }

    private function riwayat(TripPlan $trip): void
    {
        HikingHistory::create([
            'user_id' => $trip->user_id,
            'trip_plan_id' => $trip->id,
            'trail_id' => $trip->trail_id,
            'trip_type' => TripType::CAMPING->value,
            'completion_state' => CompletionState::COMPLETED->value,
            'completed_at' => $trip->planned_date,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function props(): array
    {
        return $this->actingAs($this->pendaki)->get(route('trips.index'))->assertOk()->viewData('page')['props'];
    }

    /**
     * @param  array<string, mixed>  $props
     * @return array<string, array<int, int>>
     */
    private function idPerKelompok(array $props): array
    {
        return collect($props['kelompok'])->mapWithKeys(fn (array $k) => [$k['kunci'] => array_column($k['trip'], 'id')])->all();
    }

    public function test_a_guest_is_sent_to_login(): void
    {
        $this->get(route('trips.index'))->assertRedirect(route('login'));
    }

    /**
     * PRD §77: indeks hanya memuat trip milik pembacanya.
     */
    public function test_only_the_readers_own_trips_appear(): void
    {
        $milikSendiri = $this->trip('2026-10-10');
        $milikOrangLain = $this->trip('2026-10-01', TripStatus::PLANNED, User::factory()->create());

        $props = $this->props();
        $semua = array_merge([$props['utama']['id']], ...array_values($this->idPerKelompok($props)));

        $this->assertSame([$milikSendiri->id], $semua);
        $this->assertStringNotContainsString($milikOrangLain->name, json_encode($props));
    }

    /**
     * Trip berlangsung memimpin; trip terdekat yang belum dimulai menyusul. Kartu utama tidak
     * diulang di kelompoknya, dan kelompok tanpa trip tidak dikirim.
     */
    public function test_trips_are_grouped_into_in_progress_upcoming_and_finished(): void
    {
        $berlangsung = $this->trip('2026-09-24', TripStatus::IN_PROGRESS);
        $jauh = $this->trip('2026-11-20');
        $dekat = $this->trip('2026-10-10', TripStatus::READY_FOR_DEPARTURE);
        $selesaiLama = $this->trip('2026-06-01', TripStatus::COMPLETED);
        $batal = $this->trip('2026-08-15', TripStatus::CANCELLED);

        $props = $this->props();

        $this->assertSame($berlangsung->id, $props['utama']['id']);
        $this->assertSame([
            'mendatang' => [$dekat->id, $jauh->id],
            'selesai' => [$batal->id, $selesaiLama->id],
        ], $this->idPerKelompok($props));
        $this->assertSame(['Mendatang', 'Selesai'], array_column($props['kelompok'], 'judul'));
    }

    /**
     * Tanpa pendakian berlangsung, kartu utama adalah trip terdekat yang tanggalnya belum lewat.
     * Rencana yang tanggalnya sudah lewat tanpa pernah dimulai tetap di Mendatang dan tidak
     * menghitung mundur.
     */
    public function test_the_nearest_upcoming_trip_leads_when_nothing_is_in_progress(): void
    {
        $terlewat = $this->trip('2026-09-20');
        $dekat = $this->trip('2026-10-10');

        $props = $this->props();

        $this->assertSame($dekat->id, $props['utama']['id']);
        $this->assertSame('H-15', $props['utama']['hari']);
        $this->assertSame([$terlewat->id], $this->idPerKelompok($props)['mendatang']);
        $this->assertNull($props['kelompok'][0]['trip'][0]['hari']);
    }

    /**
     * Aksi berikutnya mengikuti fase: persiapan yang belum lengkap, cek kesiapan, mode
     * lapangan, atau ringkasan pendakian yang selesai.
     */
    public function test_each_phase_offers_its_own_next_action(): void
    {
        $persiapan = $this->trip('2026-10-10');
        $this->butir($persiapan, PreparationStatus::NOT_CONFIRMED);
        $cek = $this->trip('2026-10-20');
        $this->butir($cek, PreparationStatus::CONFIRMED);
        $selesai = $this->trip('2026-07-01', TripStatus::COMPLETED);
        $this->riwayat($selesai);
        $batal = $this->trip('2026-08-01', TripStatus::CANCELLED);

        $props = $this->props();
        $kartu = collect($props['kelompok'])->flatMap(fn (array $k) => $k['trip'])->keyBy('id');

        $this->assertSame(['label' => 'Lanjutkan persiapan', 'href' => route('trips.preparation', $persiapan)], $props['utama']['aksi']);
        $this->assertSame('Persiapan', $props['utama']['fase']['judul']);
        $this->assertSame('Cek kesiapan', $kartu[$cek->id]['aksi']['label']);
        $this->assertSame('Cek', $kartu[$cek->id]['fase']['judul']);
        $this->assertSame(route('history.summary', $selesai), $kartu[$selesai->id]['url']);
        $this->assertSame('Selesai', $kartu[$selesai->id]['keadaan']);
        $this->assertNull($kartu[$batal->id]['aksi']);
        $this->assertSame(route('trips.show', $batal), $kartu[$batal->id]['url']);
        $this->assertSame('Dibatalkan', $kartu[$batal->id]['keadaan']);

        $berlangsung = $this->trip('2026-09-25', TripStatus::IN_PROGRESS);
        $utama = $this->props()['utama'];

        $this->assertSame($berlangsung->id, $utama['id']);
        $this->assertSame(['label' => 'Buka mode lapangan', 'href' => route('trips.hike', $berlangsung)], $utama['aksi']);
        $this->assertSame('Sedang berlangsung', $utama['keadaan']);
    }

    /**
     * Kelompok Selesai menampilkan beberapa yang terbaru dan menunjuk Progres untuk sisanya.
     */
    public function test_the_finished_group_is_capped_and_points_to_progress(): void
    {
        for ($i = 1; $i <= 7; $i++) {
            $this->trip('2026-0'.$i.'-10', TripStatus::COMPLETED);
        }

        $props = $this->props();
        $selesai = collect($props['kelompok'])->firstWhere('kunci', 'selesai');

        $this->assertCount(5, $selesai['trip']);
        $this->assertSame(['jumlah' => 7, 'tautan' => route('progress')], $props['selesai']);
    }

    /**
     * R-014: trip yang pre-departure check-nya sudah dikonfirmasi tidak membawa kata status
     * "Siap berangkat"; R-005 dan BR-09: tanpa skor atau persen kecocokan dan kesiapan.
     */
    public function test_no_status_reads_as_ready_and_no_score_reaches_the_index(): void
    {
        $siap = $this->trip('2026-10-10', TripStatus::READY_FOR_DEPARTURE);
        $this->butir($siap, PreparationStatus::CONFIRMED);
        ReadinessCheck::create([
            'trip_plan_id' => $siap->id,
            'computed_state' => ReadinessState::READY->value,
            'official_status_snapshot' => ['status' => 'UNKNOWN'],
            'pre_departure_confirmed' => true,
            'computed_at' => now(),
        ]);

        $json = json_encode($this->props());

        $this->assertStringNotContainsString('Siap berangkat', $json);
        $this->assertStringNotContainsString('score', strtolower($json));
        $this->assertDoesNotMatchRegularExpression('/\d+\s?%/', $json);
    }

    public function test_the_index_does_not_grow_with_the_number_of_trips(): void
    {
        $tambah = function (int $jumlah): void {
            for ($i = 0; $i < $jumlah; $i++) {
                $trip = $this->trip('2026-1'.($i % 3).'-1'.($i % 9), $i % 4 === 0 ? TripStatus::COMPLETED : TripStatus::PLANNED);
                $this->butir($trip, $i % 2 ? PreparationStatus::CONFIRMED : PreparationStatus::NOT_CONFIRMED);
                ReadinessCheck::create(['trip_plan_id' => $trip->id, 'computed_state' => ReadinessState::NEEDS_PREPARATION->value, 'official_status_snapshot' => ['status' => 'UNKNOWN'], 'computed_at' => now()]);

                if ($i % 4 === 0) {
                    $this->riwayat($trip);
                }
            }
        };

        $hitung = function (): int {
            $jumlah = 0;
            DB::listen(function () use (&$jumlah) {
                $jumlah++;
            });
            $this->actingAs($this->pendaki)->get(route('trips.index'))->assertOk();
            DB::getEventDispatcher()->forget('Illuminate\Database\Events\QueryExecuted');

            return $jumlah;
        };

        $tambah(2);
        $this->actingAs($this->pendaki)->get(route('trips.index'));
        $sedikit = $hitung();

        $tambah(10);

        $this->assertSame($sedikit, $hitung(), 'Indeks trip tumbuh bersama jumlah trip.');
    }

    /**
     * Keadaan kosong spec v3 bagian 6.9: menyebut dari mana memulai dan satu aksi utama.
     */
    public function test_an_empty_index_offers_one_way_to_start(): void
    {
        $props = $this->props();

        $this->assertNull($props['utama']);
        $this->assertSame([], $props['kelompok']);

        $halaman = File::get(resource_path('js/pages/Trips/Index.tsx'));

        $this->assertStringContainsString('title="Belum ada trip"', $halaman);
        $this->assertStringContainsString('description="Mulai dari jalur yang Anda simpan atau dari Jelajah."', $halaman);
        $this->assertStringContainsString('Rencanakan trip', $halaman);
    }
}
```

`tests/Feature/AmbientFitTest.php`:

1. Ganti:

```php
     * tetap menampilkan "Kecocokan dasar" meski trip-nya sendiri punya goal.
     */
```

dengan:

```php
     * tetap menampilkan "Kecocokan dasar" meski trip-nya sendiri punya goal.
     *
     * Indeks trip W4 (spec v3 bagian 6.9) menaruh trip mendatang terdekat di kartu utama, jadi
     * kecocokannya dibaca dari `utama`, bukan `trips.data.0`; niatnya tidak berubah.
     */
```

2. Ganti:

```php
                ->component('Trips/Index')
                ->where('trips.data.0.fit.denganRencana', false)
                ->where('trips.data.0.fit.alasan', fn (?string $alasan) => filled($alasan)));

        $this->assertStringContainsString('Kecocokan dasar', File::get(resource_path('js/components/trips/FitTrip.tsx')));
```

dengan:

```php
                ->component('Trips/Index')
                ->where('utama.fit.denganRencana', false)
                ->where('utama.fit.alasan', fn (?string $alasan) => filled($alasan)));

        $this->assertStringContainsString('Kecocokan dasar', File::get(resource_path('js/components/trips/FitTrip.tsx')));
```

`tests/Feature/StaleReadinessTest.php`:

1. Ganti:

```php
    }

    public function test_the_trip_list_stops_presenting_a_superseded_verdict(): void
```

dengan:

```php
    }

    /**
     * Indeks trip W4 (spec v3 bagian 6.9) mengganti daftar berhalaman dengan kartu utama dan
     * kelompok: trip mendatang satu-satunya menjadi kartu utama, jadi vonisnya dibaca dari
     * `utama`, bukan `trips.data.0`. Niatnya tetap: vonis basi tidak disajikan sebagai keadaan.
     */
    public function test_the_trip_list_stops_presenting_a_superseded_verdict(): void
```

2. Ganti:

```php
                ->component('Trips/Index')
                ->where('trips.data.0.kesiapan.teks', 'Status jalur berubah, perlu dinilai ulang')
                ->where('trips.data.0.kesiapan.peringatan', true));
    }
```

dengan:

```php
                ->component('Trips/Index')
                ->where('utama.kesiapan.teks', 'Status jalur berubah, perlu dinilai ulang')
                ->where('utama.kesiapan.peringatan', true));
    }
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='TripIndexPage|StaleReadiness|AmbientFit|TripReactPages|PageTitle|PageSmoke'`
Expected: FAIL. Pada simulasi rencana: 48 test, 39 lulus, 3 gagal, 6 error. Yang merah: tujuh test `TripIndexPageTest` (props `utama` dan `kelompok` belum ada; props lama masih memuat "Siap berangkat"), `AmbientFitTest::test_the_trip_list_shows_the_ambient_fit_for_each_trip`, dan `StaleReadinessTest::test_the_trip_list_stops_presenting_a_superseded_verdict` (props `utama.*` belum ada).

- [ ] **Step 3: Tulis implementasinya.**

Buat `app/Services/Trips/IndeksTrip.php`:

```php
<?php

namespace App\Services\Trips;

use App\Enums\TripStatus;
use App\Http\Resources\TripRowResource;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\OfficialStatusService;
use App\Services\TrailFitService;
use App\Support\Timezone;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;

/**
 * Kelas halaman indeks trip (spec v3 bagian 6.9, rencana induk W4 butir 1): kartu utama trip
 * terdekat dengan fase dan aksi berikutnya, lalu kelompok Berlangsung, Mendatang, dan Selesai.
 * Kelompok Selesai memuat beberapa yang terbaru dan menunjuk Progres untuk riwayat lengkapnya.
 *
 * Kueri tetap, tidak tumbuh bersama jumlah trip: satu untuk trip yang belum selesai, satu untuk
 * beberapa trip selesai terbaru, satu hitungan, relasi dimuat sekali untuk semua kartu, lalu
 * status resmi dan kecocokan dasar dimuat dalam batch. Hanya trip milik pembacanya (PRD §77).
 */
final class IndeksTrip
{
    /** Trip selesai atau batal yang tampil di indeks; sisanya dibaca di Progres. */
    public const BATAS_SELESAI = 5;

    /** @var array<int, TripStatus> */
    private const SELESAI = [TripStatus::COMPLETED, TripStatus::CANCELLED];

    public function __construct(
        private readonly TripSteps $steps,
        private readonly OfficialStatusService $status,
        private readonly TrailFitService $trailFit,
    ) {}

    /**
     * @return array{utama: array<string, mixed>|null, kelompok: array<int, array{kunci: string, judul: string, trip: array<int, array<string, mixed>>}>, selesai: array{jumlah: int, tautan: string}|null}
     */
    public function props(User $user, Request $request): array
    {
        $selesai = array_map(fn (TripStatus $s) => $s->value, self::SELESAI);

        $berjalan = $user->tripPlans()->whereNotIn('status', $selesai)->orderBy('planned_date')->orderBy('id')->get();
        $terbaru = $user->tripPlans()->whereIn('status', $selesai)->orderByDesc('planned_date')->orderByDesc('id')->limit(self::BATAS_SELESAI)->get();
        $jumlahSelesai = $user->tripPlans()->whereIn('status', $selesai)->count();

        $semua = EloquentCollection::make($berjalan->concat($terbaru)->all())
            ->load('trail.mountain', 'preparationItems', 'latestReadinessCheck', 'history');

        // Bukan collect(): OfficialStatusService dan TrailFitService men-type-hint
        // Eloquent\Collection secara ketat.
        $jalur = EloquentCollection::make($semua->pluck('trail')->filter()->unique('id')->values()->all());
        $statusJalur = $this->status->effectiveStatusesForTrails($jalur);

        // Kecocokan dasar hanya untuk trip yang belum selesai, dan hanya bagi pendaki berprofil
        // lengkap: menilai tanpa profil menghasilkan label yang terlihat pasti tanpa dasar (§91).
        $jalurBerjalan = EloquentCollection::make($berjalan->pluck('trail')->filter()->unique('id')->values()->all());
        $fit = $user->hasCompletedProfile() ? $this->trailFit->forTrails($user, $jalurBerjalan) : [];

        $kartu = fn (TripPlan $trip): array => (new TripRowResource(
            $trip,
            $this->steps->for($trip, $statusJalur[$trip->trail_id] ?? null),
            $trip->status->isActive() ? ($fit[$trip->trail_id] ?? null) : null,
            $statusJalur[$trip->trail_id] ?? null,
        ))->resolve($request);

        $sedang = $berjalan->where('status', TripStatus::IN_PROGRESS)->values();
        $mendatang = $berjalan->where('status', '!=', TripStatus::IN_PROGRESS)->values();
        $utama = $sedang->first() ?? $mendatang->first(fn (TripPlan $trip) => $trip->planned_date->toDateString() >= Timezone::earliestDateInIndonesia());

        $kelompok = [];

        foreach ([
            'berlangsung' => ['Berlangsung', $sedang],
            'mendatang' => ['Mendatang', $mendatang],
            'selesai' => ['Selesai', $terbaru],
        ] as $kunci => [$judul, $trip]) {
            $sisa = $trip->reject(fn (TripPlan $t) => $t->is($utama))->values();

            if ($sisa->isNotEmpty()) {
                $kelompok[] = ['kunci' => $kunci, 'judul' => $judul, 'trip' => $sisa->map($kartu)->all()];
            }
        }

        return [
            'utama' => $utama === null ? null : $kartu($utama),
            'kelompok' => $kelompok,
            'selesai' => $jumlahSelesai === 0 ? null : ['jumlah' => $jumlahSelesai, 'tautan' => route('progress')],
        ];
    }
}
```

Buat `resources/js/components/trips/KartuTrip.tsx`:

```tsx
import { FitTrip } from '@/components/trips/FitTrip';
import { Button } from '@/components/ui/Button';
import { Lencana } from '@/components/ui/Lencana';
import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';
import type { BarisTrip } from './types';

/**
 * Satu trip di indeks (spec v3 bagian 6.9). Bentuk `utama` untuk trip terdekat: judul kartu,
 * langkah berikutnya, kecocokan dasar, dan satu aksi utama selebar kartu di ponsel. Bentuk
 * bawaan untuk kelompok: nama sebagai tautan, satu baris langkah berikutnya, dan aksinya
 * sebagai tautan teks, supaya hanya kartu utama yang membawa tombol penuh. Peringatan kesiapan
 * (vonis basi atau jalur ditarik) tidak diulang bila langkah berikutnya sudah menyebutnya.
 */
export function KartuTrip({ kartu, utama = false }: { kartu: BarisTrip; utama?: boolean }) {
    const peringatanKesiapan = kartu.kesiapan && kartu.fase?.kunci !== 'kesiapan' ? kartu.kesiapan : null;

    return (
        <article className={kelas('rounded-lg border border-subtle bg-surface', utama ? 'rounded-utama p-5 sm:p-6' : 'p-4')}>
            <p className="flex flex-wrap items-center gap-2">
                <Lencana nada={utama ? 'merek' : 'netral'}>{kartu.jenis}</Lencana>
                {kartu.hari ? (
                    <Lencana>
                        <span data-angka>{kartu.hari}</span>
                    </Lencana>
                ) : null}
                {kartu.keadaan ? <Lencana>{kartu.keadaan}</Lencana> : null}
            </p>

            <h3 className={kelas('mt-2 break-words text-primary', utama ? 'text-judul-kartu' : 'text-subjudul')}>
                <Tautan href={kartu.url} className="inline-flex min-h-11 items-center rounded-sm underline-offset-2 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
                    {kartu.nama}
                </Tautan>
            </h3>
            <p className="mt-1 break-words text-kecil text-secondary">
                {kartu.jalur}, {kartu.gunung}
            </p>
            <p data-angka className="mt-0.5 text-kecil text-secondary">
                {kartu.tanggal}
            </p>

            {kartu.fase ? (
                <p className={kelas('mt-3 text-kecil', kartu.fase.peringatan ? 'font-bold text-warn-900' : 'text-secondary')}>
                    <span className="font-bold text-primary">Langkah berikutnya: {kartu.fase.judul}.</span> {kartu.fase.keterangan}
                </p>
            ) : null}
            {peringatanKesiapan ? (
                <p className={kelas('mt-2 text-kecil', peringatanKesiapan.peringatan ? 'font-bold text-warn-900' : 'text-secondary')}>{peringatanKesiapan.teks}</p>
            ) : null}

            {utama && kartu.fit ? <FitTrip fit={kartu.fit} /> : null}

            {kartu.aksi ? (
                utama ? (
                    <Button href={kartu.aksi.href} className="mt-4 w-full sm:w-auto">
                        {kartu.aksi.label}
                    </Button>
                ) : (
                    <Tautan
                        href={kartu.aksi.href}
                        className="mt-2 inline-flex min-h-11 items-center text-kecil font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                    >
                        {kartu.aksi.label}
                    </Tautan>
                )
            ) : null}
        </article>
    );
}
```

`app/Http/Controllers/Trips/TripController.php`:

1. Ganti:

```php
use App\Enums\CompletionState;
use App\Enums\PreparationStatus;
use App\Enums\RouteFitLabel;
```

dengan:

```php
use App\Enums\CompletionState;
use App\Enums\RouteFitLabel;
```

2. Ganti:

```php
use App\Http\Requests\Trips\UpdateTripRequest;
use App\Http\Resources\TripRowResource;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Services\OfficialStatusService;
use App\Services\PermitService;
use App\Services\TrailFitService;
use App\Services\Trips\KepalaTrip;
```

dengan:

```php
use App\Http\Requests\Trips\UpdateTripRequest;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Services\PermitService;
use App\Services\TrailFitService;
use App\Services\Trips\IndeksTrip;
use App\Services\Trips\KepalaTrip;
```

3. Ganti:

```php
{
    public function index(Request $request, OfficialStatusService $officialStatus, TrailFitService $trailFit): Response
    {
        $user = $request->user();

        $trips = $user->tripPlans()
            ->with('trail.mountain', 'latestReadinessCheck')
            ->withCount([
                'preparationItems as item_berlaku' => fn ($q) => $q->where('status', '!=', PreparationStatus::NOT_APPLICABLE->value),
                'preparationItems as item_dikonfirmasi' => fn ($q) => $q->where('status', PreparationStatus::CONFIRMED->value),
            ])
            ->orderByDesc('planned_date')
            ->paginate(10);

        // Bukan collect(): OfficialStatusService dan PermitService (lewat forTrails())
        // men-type-hint Eloquent\Collection secara ketat dan melempar TypeError pada
        // Support\Collection biasa.
        $jalurDitampilkan = EloquentCollection::make(
            $trips->pluck('trail')->filter()->unique('id')->values()->all()
        );

        // Kecocokan dasar, tanpa goal. Pengguna tanpa profil lengkap tidak dinilai sama
        // sekali: menilai tanpa profil menghasilkan label yang terlihat pasti dan
        // berdasar ketiadaan (§91).
        $ringkasanFit = $user->hasCompletedProfile()
            ? $trailFit->forTrails($user, $jalurDitampilkan)
            : [];

        // Dimuat sekali untuk seluruh halaman, bukan satu query status per trip.
        $statusJalur = $officialStatus->effectiveStatusesForTrails($jalurDitampilkan);

        return Inertia::render('Trips/Index', [
            'title' => 'Trip Saya',
            'trips' => [
                'data' => $trips->getCollection()
                    ->map(fn (TripPlan $trip) => (new TripRowResource(
                        $trip,
                        $ringkasanFit[$trip->trail_id] ?? null,
                        $statusJalur[$trip->trail_id] ?? null,
                    ))->resolve($request))
                    ->values()
                    ->all(),
                'links' => $trips->linkCollection()->map(fn (array $l) => ['url' => $l['url'], 'label' => $l['label'], 'active' => $l['active']])->all(),
            ],
        ]);
```

dengan:

```php
{
    /**
     * Indeks trip (spec v3 bagian 6.9): kartu utama trip terdekat, lalu kelompok Berlangsung,
     * Mendatang, dan Selesai, disusun kelas halaman IndeksTrip.
     */
    public function index(Request $request, IndeksTrip $indeks): Response
    {
        return Inertia::render('Trips/Index', [
            'title' => 'Trip Saya',
            ...$indeks->props($request->user(), $request),
        ]);
```

`app/Http/Resources/TripRowResource.php`:

1. Ganti:

```php
use App\Enums\RouteFitLabel;
use App\Models\TripPlan;
use App\Services\RouteFit\TrailFitSummary;
use App\Services\Trips\TripSteps;
use Illuminate\Http\Request;
```

dengan:

```php
use App\Enums\RouteFitLabel;
use App\Enums\TripStatus;
use App\Models\TripPlan;
use App\Services\RouteFit\TrailFitSummary;
use App\Services\Trips\KepalaTrip;
use App\Services\Trips\TripSteps;
use App\Support\Timezone;
use Illuminate\Http\Request;
```

2. Ganti:

```php
/**
 * Baris daftar trip, sudah diformat di server.
 *
 * Kecocokan yang dibawa adalah kecocokan dasar tanpa goal: daftar trip adalah permukaan
 * pemindaian, dan kecocokan tajam yang sadar-rencana tinggal di halaman trip itu
 * sendiri. Skor internal tidak pernah ikut (BR-09).
 *
 * Kemajuan persiapan dibaca dari kolom hitung (withCount) supaya daftar tidak memuat
 * seluruh butir tiap trip.
 *
```

dengan:

```php
/**
 * Satu kartu indeks trip (spec v3 bagian 6.9), sudah diformat di server: jalur, jenis,
 * tanggal, sisa hari, langkah berikutnya, aksi berikutnya, dan peringatan kesiapan.
 *
 * Kecocokan yang dibawa adalah kecocokan dasar tanpa goal: indeks adalah permukaan
 * pemindaian, dan kecocokan untuk rencana tinggal di ruang kerja trip. Skor internal tidak
 * pernah ikut (BR-09). Status trip hanya ditulis untuk trip yang berlangsung, selesai, atau
 * dibatalkan; trip yang belum dimulai dibaca dari sisa harinya, karena "Siap berangkat" di
 * kartu terbaca sebagai jaminan (R-014).
 *
 * Membutuhkan relasi trail.mountain, preparationItems, latestReadinessCheck, dan history
 * sudah dimuat (IndeksTrip memuatnya sekali untuk semua kartu).
 *
```

3. Ganti:

```php
{
    public function __construct(
        TripPlan $trip,
        private ?TrailFitSummary $fit = null,
```

dengan:

```php
{
    /**
     * @param  array<int, array{keadaan: string, kunci: string, judul: string, keterangan: string, catatan: string|null, peringatan: bool, tautan: array{href: string, teks: string}|null}>  $langkah  hasil TripSteps::for()
     */
    public function __construct(
        TripPlan $trip,
        private array $langkah,
        private ?TrailFitSummary $fit = null,
```

4. Ganti:

```php
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'url' => route('trips.show', $this->resource),
            'trail' => $this->trail?->name,
            'mountain' => $this->trail?->mountain?->name,
            'tanggal' => $this->planned_date->translatedFormat('d M Y'),
            'tipe' => $this->trip_type->label(),
            'status' => $this->status->label(),
            'persiapan' => isset($this->item_berlaku) && (int) $this->item_berlaku > 0
                ? TripSteps::teksPersiapan([
                    'dikonfirmasi' => (int) $this->item_dikonfirmasi,
                    'berlaku' => (int) $this->item_berlaku,
                ])
                : null,
            'kesiapan' => $this->kesiapan(),
            'fit' => $this->fit === null ? null : [
```

dengan:

```php
    {
        $trip = $this->resource;
        $belumDimulai = $trip->status->isActive() && $trip->status !== TripStatus::IN_PROGRESS;
        $berikutnya = collect($this->langkah)->firstWhere('keadaan', TripSteps::BERIKUTNYA);

        return [
            'id' => $trip->id,
            'nama' => $trip->name,
            'url' => $trip->status === TripStatus::COMPLETED && $trip->history !== null
                ? route('history.summary', $trip)
                : route('trips.show', $trip),
            'jalur' => $trip->trail?->name,
            'gunung' => $trip->trail?->mountain?->name,
            'jenis' => $trip->trip_type->labelPendek(),
            'tanggal' => KepalaTrip::rentang($trip->planned_date, $trip->end_date),
            'hari' => $belumDimulai ? KepalaTrip::hariMenuju($trip->planned_date, Timezone::forTrail($trip->trail)) : null,
            'keadaan' => $belumDimulai ? null : $trip->status->label(),
            'fase' => $berikutnya === null ? null : [
                'kunci' => $berikutnya['kunci'],
                'judul' => $berikutnya['judul'],
                'keterangan' => $berikutnya['keterangan'],
                'peringatan' => $berikutnya['peringatan'],
            ],
            'aksi' => TripSteps::aksi($trip, $this->langkah),
            'kesiapan' => $trip->status->isActive() ? $this->kesiapan() : null,
            'fit' => $this->fit === null ? null : [
```

`resources/js/components/trips/types.ts`:

1. Ganti:

```ts
}

/** Baris daftar trip dari TripRowResource: seluruh teks sudah diformat server. */
export interface BarisTrip {
    id: number;
    name: string;
    url: string;
    trail: string | null;
    mountain: string | null;
    tanggal: string;
    tipe: string;
    status: string;
    persiapan: string | null;
    kesiapan: { teks: string; peringatan: boolean } | null;
    fit: FitRingkas | null;
}
```

dengan:

```ts
}

/** Satu kartu indeks trip dari TripRowResource: seluruh teks sudah diformat server. */
export interface BarisTrip {
    id: number;
    nama: string;
    url: string;
    jalur: string | null;
    gunung: string | null;
    jenis: string;
    tanggal: string;
    hari: string | null;
    keadaan: string | null;
    fase: { kunci: string; judul: string; keterangan: string; peringatan: boolean } | null;
    aksi: { label: string; href: string } | null;
    kesiapan: { teks: string; peringatan: boolean } | null;
    fit: FitRingkas | null;
}

/** Satu kelompok indeks trip (App\Services\Trips\IndeksTrip). */
export interface KelompokTrip {
    kunci: 'berlangsung' | 'mendatang' | 'selesai';
    judul: string;
    trip: BarisTrip[];
}
```

Tulis ulang `resources/js/pages/Trips/Index.tsx` utuh:

```tsx
import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { KartuTrip } from '@/components/trips/KartuTrip';
import type { BarisTrip, KelompokTrip } from '@/components/trips/types';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { PageHeader } from '@/components/ui/PageHeader';
import { Tautan } from '@/components/ui/Tautan';
import { AppLayout } from '@/layouts/AppLayout';
import { saved } from '@/routes/trails';
import { create } from '@/routes/trips';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    utama: BarisTrip | null;
    kelompok: KelompokTrip[];
    selesai: { jumlah: number; tautan: string } | null;
}

/**
 * Indeks trip (spec v3 bagian 6.9; tidak ada di prototype): trip terdekat sebagai kartu
 * utama dengan langkah dan aksi berikutnya, lalu kelompok Berlangsung, Mendatang, dan
 * Selesai. Kelompok Selesai menunjuk Progres untuk riwayat lengkapnya. Pendaki tanpa trip
 * mendapat satu aksi utama, bukan halaman kosong.
 */
export default function Index() {
    const { utama, kelompok, selesai } = usePage<Props>().props;

    return (
        <div className="py-6">
            <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <PageHeader
                    akar
                    title="Trip"
                    aksi={
                        utama !== null || kelompok.length > 0 ? (
                            <Button href={create.url()} size="sm">
                                Rencanakan trip
                            </Button>
                        ) : null
                    }
                />

                {utama === null && kelompok.length === 0 ? (
                    <EmptyState jenis="pertama" title="Belum ada trip" description="Mulai dari jalur yang Anda simpan atau dari Jelajah.">
                        <Button href={create.url()}>Rencanakan trip</Button>
                        <Button href={saved.url()} variant="secondary">
                            Lihat simpanan
                        </Button>
                    </EmptyState>
                ) : (
                    <div className="space-y-8">
                        {utama ? (
                            <section aria-label="Trip terdekat">
                                <KartuTrip kartu={utama} utama />
                            </section>
                        ) : null}

                        {kelompok.map((k) => (
                            <section key={k.kunci} aria-labelledby={`kelompok-${k.kunci}`}>
                                <h2 id={`kelompok-${k.kunci}`} className="text-bagian text-primary">
                                    {k.judul}
                                </h2>
                                <ul className="mt-3 space-y-3">
                                    {k.trip.map((t) => (
                                        <li key={t.id}>
                                            <KartuTrip kartu={t} />
                                        </li>
                                    ))}
                                </ul>
                                {k.kunci === 'selesai' && selesai ? (
                                    <Tautan
                                        href={selesai.tautan}
                                        className="mt-3 inline-flex min-h-11 items-center text-kecil font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                                    >
                                        Lihat riwayat lengkap di Progres
                                    </Tautan>
                                ) : null}
                            </section>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}

Index.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
```

- [ ] **Step 4: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Services/Trips/IndeksTrip.php app/Http/Resources/TripRowResource.php app/Http/Controllers/Trips/TripController.php tests/Feature/TripIndexPageTest.php tests/Feature/StaleReadinessTest.php tests/Feature/AmbientFitTest.php`, lalu `php artisan test --compact --filter='TripIndexPage|StaleReadiness|AmbientFit|TripReactPages|PageTitle|PageSmoke'`
Expected: PASS. Pada simulasi rencana: 48 test lulus.

- [ ] **Step 5: Suite, tipe, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 1.782 test, 1.780 lulus, 2 dilewati.

Run: `npx tsc --noEmit`
Expected: keluar tanpa keluaran galat.

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `php artisan test --compact --testsuite=Pratinjau`
Expected: build `✓ built`; Pratinjau lulus termasuk `test_trip`. Pada simulasi rencana: 15 test lulus.

- [ ] **Step 6: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 3: Rencanakan trip

Induk W4 butir 2; spec bagian 6.8; ruling W4-R8 sampai W4-R17, W4-R32, W4-R36, W4-R40. `/trips/create` lewat kelas
halaman `RencanaTrip`: jalur terpilih dan "Ganti", kecocokan untuk rencana yang dihitung ulang server lewat muat
ulang parsial, jenis trip (kontrol segmen), tanggal naik dan turun, rentang dan kalimat terbitnya prakiraan BMKG,
jendela izin dengan tanggalnya, jam mulai dalam zona gunung, peserta 1 sampai 12 (stepper), kuota yang belum
terverifikasi, nama, catatan, sakelar pengingat, dan ringkasan di bar aksi dengan "Simpan rencana". Tanpa jalur,
halaman menawarkan simpanan pendaki sendiri. Jalur berstatus Tutup tetap dapat direncanakan dengan peringatan.

**Files:**
- Create: `app/Services/Trips/RencanaTrip.php`, `resources/js/components/trips/LangkahRingkas.tsx`, `resources/js/components/trips/rencana/KotakIzin.tsx`
- Modify: `app/Http/Controllers/Trips/TripController.php`, `config/hiking.php`, `resources/js/components/ui/KontrolSegmen.tsx`, `resources/js/components/ui/Stepper.tsx`, `resources/js/pages/Trips/Create.tsx`
- Test: `tests/Feature/TripPlanFitReloadTest.php` (baru), `tests/Feature/DesignPrimitivesTest.php`, `tests/Feature/TripPermitWindowTest.php`, `tests/Feature/TripPlanDetailTest.php`, `tests/Feature/TripReactPagesTest.php`, `tests/Pratinjau/HalamanPratinjauTest.php`

**Interfaces:**
- Consumes: `StatusJalurRingkas::untuk()`, `BarisStatus`, `LabelKecocokan` (W3), `ConsiderationService::simpanan()` (W3), `PermitService::requirementFor()` dan `bookingWindowFor()`, `TrailFigures::ringkas()`, `TripReminderScheduler::keteranganJadwal()`, `KepalaTrip::rentang()` dan `TripType::labelPendek()`, `lamaHari()` (Tugas 1), `KontrolSegmen`, `Stepper`, `Sakelar`, `BarAksi`, `FormField`, `EmptyState`.
- Produces:
  - `App\Services\Trips\RencanaTrip::props(User $user, Request $request): array` dengan kunci `jalur`, `pilihanJalur`, `awal`, `jenisTrip`, `tanggalTerawal`, `batas`, `zona`, `jadwalPengingat`, dan closure `kecocokan` (`label`, `labelText`, `alasan`, `denganRencana`, `layak`), `jadwal` (`rentang`, `ringkas`, `lama`, `prakiraan`), `izin` (`keadaan`, `judul`, `keterangan`, `tautan`, `kuota`);
  - `LangkahRingkas({ kini, tautan })` dengan tipe `KunciLangkah` (dipakai Tugas 4 dan 6), `KotakIzin({ izin })` dengan tipe `IzinRencana`;
  - prop opsional `penjelas?: string` pada `KontrolSegmen` dan `Stepper`;
  - `config('hiking.trips.max_participants')` = 12; judul halaman "Rencanakan trip".

- [ ] **Step 1: Tulis test yang gagal.**

Buat `tests/Feature/TripPlanFitReloadTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\OfficialStatusValue;
use App\Enums\TripType;
use App\Models\Trail;
use App\Models\TrailConsideration;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Rencanakan trip (spec v3 bagian 6.8, prototype 08, W4 Tugas 3). Kecocokan untuk rencana
 * dihitung ulang server lewat muat ulang parsial Inertia saat jenis trip atau tanggal berubah,
 * dan yang sampai ke klien hanya label, kata, dan alasan: tanpa skor (BR-09, R-005).
 *
 * Waktu dibekukan pada 10 September 2026 pukul 15.00 WIB.
 */
class TripPlanFitReloadTest extends TestCase
{
    use RefreshDatabase;

    private const PARSIAL = ['kecocokan', 'jadwal', 'izin'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-09-10 08:00:00', 'UTC'));
    }

    private function pendaki(): User
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::ADVANCED->value, 'completed_at' => now()]);

        return $user->fresh();
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function halaman(User $user, array $query = []): TestResponse
    {
        return $this->actingAs($user)->get(route('trips.create', $query))->assertOk();
    }

    public function test_a_guest_is_sent_to_login(): void
    {
        $this->get(route('trips.create'))->assertRedirect(route('login'));
    }

    /**
     * Tanpa jalur, halaman meminta pendaki memilih dari simpanannya sendiri yang masih terbit,
     * bukan dari seluruh katalog (anggaran isi halaman) dan bukan dari simpanan orang lain.
     */
    public function test_without_a_trail_the_page_offers_the_readers_own_saved_trails(): void
    {
        $user = $this->pendaki();
        $milikSendiri = Trail::factory()->published()->create();
        $draf = Trail::factory()->unpublished()->create();
        $milikOrangLain = Trail::factory()->published()->create();
        TrailConsideration::create(['user_id' => $user->id, 'trail_id' => $milikSendiri->id]);
        TrailConsideration::create(['user_id' => $user->id, 'trail_id' => $draf->id]);
        TrailConsideration::create(['user_id' => User::factory()->create()->id, 'trail_id' => $milikOrangLain->id]);

        $props = $this->halaman($user)->viewData('page')['props'];

        $this->assertNull($props['jalur']);
        $this->assertNull($props['kecocokan']);
        $this->assertSame([route('trips.create', ['trail' => $milikSendiri->id])], array_column($props['pilihanJalur'], 'href'));
    }

    /**
     * R-022: jalur draf atau arsip tidak dapat direncanakan lewat URL.
     */
    public function test_an_unpublished_or_archived_trail_is_not_offered_for_planning(): void
    {
        $user = $this->pendaki();

        foreach ([Trail::factory()->unpublished()->create(), Trail::factory()->published()->create(['archived_at' => now()])] as $jalur) {
            $this->assertNull($this->halaman($user, ['trail' => $jalur->id])->viewData('page')['props']['jalur']);
        }
    }

    /**
     * Kecocokan untuk rencana: label, kata, alasan, penanda "untuk rencana ini", dan apakah
     * jalurnya lolos syarat dasar. Tidak ada kunci lain, tidak ada angka.
     */
    public function test_the_fit_for_the_plan_carries_only_a_label_and_its_reason(): void
    {
        $user = $this->pendaki();
        $jalur = Trail::factory()->published()->easy()->create();

        $respons = $this->halaman($user, ['trail' => $jalur->id, 'jenis' => TripType::CAMPING->value, 'tanggal' => '2026-10-10']);

        $respons->assertInertia(fn (AssertableInertia $page) => $page
            ->has('kecocokan', fn (AssertableInertia $k) => $k
                ->whereType('label', 'string|null')
                ->whereType('labelText', 'string')
                ->whereType('alasan', 'string')
                ->where('denganRencana', true)
                ->where('layak', true)));

        $this->assertDoesNotMatchRegularExpression('/score|"weight"|\d+\s?%/i', json_encode($respons->viewData('page')['props']['kecocokan']));
    }

    /**
     * Muat ulang parsial: hanya kecocokan, jadwal, dan izin yang dikirim ulang, dan
     * kecocokannya mengikuti jenis trip. Jalur bermalam yang direncanakan pulang hari gagal
     * syarat dasar dengan alasan mesin yang sama dengan rekomendasi.
     */
    public function test_a_partial_reload_recomputes_the_fit_when_the_trip_type_changes(): void
    {
        $user = $this->pendaki();
        $jalur = Trail::factory()->published()->highlyTechnical()->create();

        $this->halaman($user, ['trail' => $jalur->id, 'jenis' => TripType::TEKTOK->value, 'tanggal' => '2026-10-10'])
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->reloadOnly(self::PARSIAL, fn (AssertableInertia $muatUlang) => $muatUlang
                    ->missing('awal')
                    ->missing('jalur')
                    ->missing('pilihanJalur')
                    ->where('kecocokan.layak', false)
                    ->where('kecocokan.label', null)
                    ->where('kecocokan.alasan', 'Estimasi durasi jalur menuntut bermalam, sementara rencana Anda pulang hari.')));

        $this->halaman($user, ['trail' => $jalur->id, 'jenis' => TripType::MULTI_DAY->value, 'tanggal' => '2026-10-10'])
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->reloadOnly(self::PARSIAL, fn (AssertableInertia $muatUlang) => $muatUlang->where('kecocokan.layak', true)));
    }

    /**
     * Pendaki tanpa profil lengkap tidak dinilai: katanya "Belum dinilai" dari server, dan
     * alasannya menyebut apa yang ditunggu, bukan label yang tampak pasti tanpa dasar (§91).
     */
    public function test_a_hiker_without_a_profile_is_not_rated(): void
    {
        $jalur = Trail::factory()->published()->create();

        $kecocokan = $this->halaman(User::factory()->create(), ['trail' => $jalur->id])->viewData('page')['props']['kecocokan'];

        $this->assertSame(['label' => null, 'labelText' => 'Belum dinilai', 'alasan' => 'Isi profil pendaki supaya kecocokan jalur ini dapat dinilai untuk rencana Anda.', 'denganRencana' => false, 'layak' => true], $kecocokan);
    }

    /**
     * Rentang tanggal dan kalimat terbitnya prakiraan BMKG (R-013): prakiraan untuk tanggal
     * trip baru terbit tiga hari sebelumnya, dan sebelum itu tidak ada cuaca yang ditebak.
     */
    public function test_the_schedule_names_the_range_and_when_the_bmkg_forecast_appears(): void
    {
        $jalur = Trail::factory()->published()->create();

        $jadwal = $this->halaman($this->pendaki(), ['trail' => $jalur->id, 'jenis' => TripType::CAMPING->value, 'tanggal' => '2026-10-10', 'selesai' => '2026-10-11'])
            ->viewData('page')['props']['jadwal'];

        $this->assertSame('Sab, 10 Okt sampai Min, 11 Okt 2026', $jadwal['rentang']);
        $this->assertSame('10 sampai 11 Okt', $jadwal['ringkas']);
        $this->assertSame('2 hari', $jadwal['lama']);
        $this->assertSame('Prakiraan BMKG untuk Sab, 10 Okt terbit mulai Rab, 7 Okt. Sebelum itu Memount tidak menebak cuacanya.', $jadwal['prakiraan']);

        $dekat = $this->halaman($this->pendaki(), ['trail' => $jalur->id, 'tanggal' => '2026-09-12'])->viewData('page')['props']['jadwal'];
        $this->assertSame('Prakiraan BMKG untuk Sab, 12 Sep sudah dapat terbit. Prakiraannya tampil di cek kesiapan begitu tersedia dari BMKG.', $dekat['prakiraan']);
    }

    /**
     * Tanggal yang sudah lewat, salah bentuk, atau tanggal selesai sebelum tanggal mulai tidak
     * dipercaya dari URL: jadwal tidak dihitung dan isian awal tetap kosong.
     */
    public function test_a_date_from_the_url_is_only_used_when_it_is_valid(): void
    {
        $user = $this->pendaki();
        $jalur = Trail::factory()->published()->create();

        foreach (['2026-09-01', '10-10-2026', 'besok'] as $tanggal) {
            $props = $this->halaman($user, ['trail' => $jalur->id, 'tanggal' => $tanggal])->viewData('page')['props'];

            $this->assertNull($props['jadwal'], $tanggal);
            $this->assertSame('', $props['awal']['planned_date'], $tanggal);
        }

        $props = $this->halaman($user, ['trail' => $jalur->id, 'jenis' => 'MULTI_DAY', 'tanggal' => '2026-10-10', 'selesai' => '2026-10-01'])->viewData('page')['props'];
        $this->assertSame('2026-10-12', $props['awal']['end_date'], 'Tanpa tanggal selesai yang sah, tanggal selesai mengikuti lama jenis trip.');
        $this->assertSame('3 hari', $props['jadwal']['lama']);
    }

    /**
     * Spec v3 bagian 6.8: jalur berstatus Tutup tetap dapat direncanakan untuk tanggal
     * mendatang, dengan peringatan status saat ini dan kalimat R-012 di halaman.
     */
    public function test_a_closed_trail_can_still_be_planned_with_a_warning(): void
    {
        $user = $this->pendaki();
        $jalur = Trail::factory()->published()->create();
        $jalur->officialStatuses()->update(['status' => OfficialStatusValue::CLOSED->value, 'source' => 'Balai Taman Nasional']);

        $props = $this->halaman($user, ['trail' => $jalur->id])->viewData('page')['props'];

        $this->assertSame('CLOSED', $props['jalur']['status']['status']);
        $this->assertSame('Jalur ini sedang Tutup. Anda tetap dapat merencanakan tanggal mendatang; periksa statusnya lagi sebelum berangkat.', $props['jalur']['peringatan']);

        $this->actingAs($user)->post(route('trips.store'), [
            'trail_id' => $jalur->id,
            'name' => 'Menunggu dibuka',
            'planned_date' => '2026-11-20',
            'trip_type' => TripType::CAMPING->value,
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, TripPlan::count());
        $this->assertStringContainsString('Status resmi menjelaskan ketentuan pengelola, bukan jaminan keselamatan.', File::get(resource_path('js/pages/Trips/Create.tsx')));
    }

    /**
     * Isian awal membawa jenis dan tanggal dari URL, supaya memuat ulang halaman tidak
     * membuang pilihan pendaki. Pengingat surel tetap mati sampai pendaki menyalakannya.
     */
    public function test_the_initial_form_keeps_the_choices_in_the_url(): void
    {
        $jalur = Trail::factory()->published()->create(['name' => 'Gede via Cibodas']);

        $awal = $this->halaman($this->pendaki(), ['trail' => $jalur->id, 'jenis' => 'CAMPING', 'tanggal' => '2026-10-10', 'selesai' => '2026-10-11'])
            ->viewData('page')['props']['awal'];

        $this->assertSame('CAMPING', $awal['trip_type']);
        $this->assertSame('2026-10-10', $awal['planned_date']);
        $this->assertSame('2026-10-11', $awal['end_date']);
        $this->assertSame('Pendakian Gede via Cibodas', $awal['name']);
        $this->assertFalse($awal['reminders_enabled']);
    }

    public function test_the_page_does_not_grow_with_saved_or_published_trails(): void
    {
        $user = $this->pendaki();
        $jalur = Trail::factory()->published()->create();
        TrailConsideration::create(['user_id' => $user->id, 'trail_id' => $jalur->id]);

        $hitung = function (array $query) use ($user): int {
            $jumlah = 0;
            DB::listen(function () use (&$jumlah) {
                $jumlah++;
            });
            $this->actingAs($user)->get(route('trips.create', $query))->assertOk();
            DB::getEventDispatcher()->forget('Illuminate\Database\Events\QueryExecuted');

            return $jumlah;
        };

        $denganJalur = ['trail' => $jalur->id, 'jenis' => 'CAMPING', 'tanggal' => '2026-10-10'];
        $this->actingAs($user)->get(route('trips.create', $denganJalur));
        $this->actingAs($user)->get(route('trips.create'));
        $sedikitDengan = $hitung($denganJalur);
        $sedikitTanpa = $hitung([]);

        foreach (Trail::factory()->count(8)->published()->create() as $lain) {
            TrailConsideration::create(['user_id' => $user->id, 'trail_id' => $lain->id]);
        }

        $this->assertSame($sedikitDengan, $hitung($denganJalur), 'Halaman rencana tumbuh bersama jumlah jalur.');
        $this->assertSame($sedikitTanpa, $hitung([]), 'Pemilih jalur tumbuh bersama jumlah simpanan.');
    }

    /**
     * Halaman tugas prototype 08: kontrol segmen jenis trip, stepper peserta, sakelar
     * pengingat, ringkasan tetap di bar aksi, dan kecocokan yang dimuat ulang parsial.
     */
    public function test_the_page_uses_the_prototype_controls_and_reloads_only_the_fit(): void
    {
        $halaman = File::get(resource_path('js/pages/Trips/Create.tsx'));

        foreach (['<KontrolSegmen', '<Stepper', '<Sakelar', '<BarAksi', 'Simpan rencana', "only: ['kecocokan', 'jadwal', 'izin']", 'replace: true', '<AppLayout bilahTab={false}>'] as $penanda) {
            $this->assertStringContainsString($penanda, $halaman);
        }

        $this->assertStringNotContainsString('score', strtolower($halaman));
    }
}
```

`tests/Feature/DesignPrimitivesTest.php`:

1. Ganti:

```php
     * layar menyebut posisinya tanpa ARIA buatan. Terpilih ditandai permukaan, garis, dan
     * tebal huruf.
     */
```

dengan:

```php
     * layar menyebut posisinya tanpa ARIA buatan. Terpilih ditandai permukaan, garis, dan
     * tebal huruf. W4 Tugas 3 menambah aria-describedby opsional pada fieldset itu, supaya
     * galat validasi jenis trip di halaman Rencanakan terikat ke grupnya; grupnya tetap asli.
     */
```

2. Ganti:

```php
        $segmen = $this->sumber('components/ui/KontrolSegmen.tsx');

        $this->assertStringContainsString('<fieldset className={className}>', $segmen);
        $this->assertStringContainsString('<legend className="sr-only">{label}</legend>', $segmen);
```

dengan:

```php
        $segmen = $this->sumber('components/ui/KontrolSegmen.tsx');

        $this->assertStringContainsString('<fieldset className={className} aria-describedby={penjelas}>', $segmen);
        $this->assertStringContainsString('<legend className="sr-only">{label}</legend>', $segmen);
```

`tests/Feature/TripPermitWindowTest.php`:

1. Ganti:

```php
    }
}
```

dengan:

```php
    }

    /**
     * Halaman Rencanakan (spec v3 bagian 6.8, W4 Tugas 3) memakai keadaan jendela yang sama,
     * tetapi menyebut tanggalnya untuk hari yang dipilih: kapan dibuka dan kapan ditutup,
     * siapa pengelolanya, dan bahwa Memount hanya menautkan sistem resmi.
     */
    public function test_the_plan_page_names_the_booking_dates_for_the_chosen_day(): void
    {
        $trail = Trail::factory()->published()->easy()->create();
        PermitRequirement::factory()->create([
            'trail_id' => $trail->id,
            'authority' => 'Balai Besar TNGGP',
            'booking_url' => 'https://booking.example.test',
            'booking_opens_days_before' => 30,
            'booking_closes_days_before' => 3,
            'daily_quota' => null,
        ]);
        $user = User::factory()->create();
        $izin = fn (string $tanggal): ?array => $this->actingAs($user)
            ->get(route('trips.create', ['trail' => $trail->id, 'tanggal' => $tanggal]))
            ->viewData('page')['props']['izin'];

        $this->assertSame([
            'keadaan' => 'terbuka',
            'judul' => 'Pemesanan izin terbuka sampai Rab, 7 Okt',
            'keterangan' => 'Dibuka sejak Kam, 10 Sep di sistem resmi Balai Besar TNGGP (H-30 sampai H-3). Memount menautkan sistem resmi dan tidak menjual izin.',
            'tautan' => 'https://booking.example.test',
            'kuota' => 'Kuota harian jalur ini belum ada sumber terverifikasi. Pastikan saat memesan izin.',
        ], $izin('2026-10-10'));

        $this->assertSame(['belum', 'Pemesanan izin dibuka mulai Rab, 21 Okt'], [$izin('2026-11-20')['keadaan'], $izin('2026-11-20')['judul']]);
        $this->assertSame(['tutup', 'Pemesanan izin sudah tutup sejak Rab, 9 Sep'], [$izin('2026-09-12')['keadaan'], $izin('2026-09-12')['judul']]);

        $tanpaTanggal = $izin('');
        $this->assertSame('tanpaTanggal', $tanpaTanggal['keadaan']);
        $this->assertSame('Pilih tanggal naik untuk melihat kapan pemesanannya dibuka dan ditutup. Dikelola Balai Besar TNGGP (H-30 sampai H-3).', $tanpaTanggal['keterangan']);
    }

    /**
     * Kuota hanya disebut sebagai aturan yang tercatat, tidak pernah sebagai sisa kuota
     * (§43); kuota tanpa verifikasi disebut belum terverifikasi. Aturan tanpa jendela yang
     * tercatat tidak ditulis "sedang dibuka" (R-007), dan jalur tanpa aturan tidak mendapat
     * kotak izin sama sekali.
     */
    public function test_the_plan_page_never_invents_a_quota_or_a_window(): void
    {
        $user = User::factory()->create();
        $izin = function (array $aturan) use ($user): ?array {
            $trail = Trail::factory()->published()->easy()->create();

            if ($aturan !== []) {
                PermitRequirement::factory()->create(['trail_id' => $trail->id, 'authority' => 'Balai Taman Nasional'] + $aturan);
            }

            return $this->actingAs($user)->get(route('trips.create', ['trail' => $trail->id, 'tanggal' => '2026-10-10']))->viewData('page')['props']['izin'];
        };

        $terverifikasi = $izin(['daily_quota' => 600, 'verified_at' => now(), 'booking_opens_days_before' => 30, 'booking_closes_days_before' => 3]);
        $this->assertSame('Kuota harian tercatat 600 pendaki. Sisa kuota hanya ada di sistem resmi.', $terverifikasi['kuota']);

        $belumTerverifikasi = $izin(['daily_quota' => 600, 'verified_at' => null, 'booking_opens_days_before' => 30, 'booking_closes_days_before' => 3]);
        $this->assertSame('Kuota harian tercatat 600 pendaki, belum terverifikasi. Pastikan saat memesan izin.', $belumTerverifikasi['kuota']);

        $tanpaJendela = $izin(['booking_opens_days_before' => null, 'booking_closes_days_before' => null]);
        $this->assertSame('tanpaJendela', $tanpaJendela['keadaan']);
        $this->assertSame('Jalur ini mewajibkan izin', $tanpaJendela['judul']);
        $this->assertSame('Jendela pemesanannya belum tercatat di Memount. Periksa ke Balai Taman Nasional.', $tanpaJendela['keterangan']);

        $this->assertNull($izin([]));
    }
}
```

`tests/Feature/TripPlanDetailTest.php`:

1. Ganti:

```php
    }

    #[TestWith(['0'])]
    #[TestWith(['31'])]
    public function test_the_number_of_participants_stays_within_one_and_thirty(string $jumlah): void
    {
```

dengan:

```php
    }

    /**
     * Spec v3 bagian 6.8 (disetujui pemilik): peserta 1 sampai 12, batas yang sama dengan
     * stepper di halaman Rencanakan (W4 Tugas 3). Sebelumnya 30; niat test ini tetap, yaitu
     * jumlah di luar batas ditolak server dengan kalimat yang menyebut batasnya.
     */
    #[TestWith(['0'])]
    #[TestWith(['13'])]
    public function test_the_number_of_participants_stays_within_one_and_twelve(string $jumlah): void
    {
```

2. Ganti:

```php
            ->post(route('trips.store'), $this->isian(Trail::factory()->published()->create(), ['participants_count' => $jumlah]))
            ->assertSessionHasErrors(['participants_count' => 'Jumlah peserta harus antara 1 dan 30 orang.']);
    }
```

dengan:

```php
            ->post(route('trips.store'), $this->isian(Trail::factory()->published()->create(), ['participants_count' => $jumlah]))
            ->assertSessionHasErrors(['participants_count' => 'Jumlah peserta harus antara 1 dan 12 orang.']);
    }
```

`tests/Feature/TripReactPagesTest.php`:

1. Ganti:

```php
            route('trips.index') => ['Trips/Index', 'Trip Saya'],
            route('trips.create') => ['Trips/Create', 'Buat Rencana Trip'],
            route('trips.show', $trip) => ['Trips/Show', 'Pendakian Merbabu'],
```

dengan:

```php
            route('trips.index') => ['Trips/Index', 'Trip Saya'],
            route('trips.create') => ['Trips/Create', 'Rencanakan trip'],
            route('trips.show', $trip) => ['Trips/Show', 'Pendakian Merbabu'],
```

2. Ganti:

```php
        }

        // Pemilih pada formulir buat trip dibangun satu fungsi: label dan kontrolnya
        // memakai nama yang sama.
        $buat = File::get(resource_path('js/pages/Trips/Create.tsx'));
        $this->assertStringContainsString('<Label htmlFor={name}>', $buat);
        $this->assertStringContainsString('id={name}', $buat);
    }
```

dengan:

```php
        }

        // Halaman Rencanakan W4 (prototype 08) tidak lagi membangun pemilih jalur: jalurnya
        // datang dari ?trail=, dan isiannya memakai FormField yang mengikat label sendiri.
        // Niatnya tetap: setiap isian di formulir buat trip berlabel.
        $buat = File::get(resource_path('js/pages/Trips/Create.tsx'));
        $this->assertStringContainsString('<FormField', $buat);
        $this->assertStringContainsString('<Label htmlFor="notes">', $buat);
    }
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='TripPlanFitReload|TripPermitWindow|TripPlanDetail|TripReactPages|GoalInputIsUsed|IndonesianDateFloor|CoreJourney|NavigationConsistency|KeyboardAndScreenReader|DesignPrimitives'`
Expected: FAIL. Pada simulasi rencana: 130 test, 113 lulus, 9 gagal, 8 error. Yang merah: sepuluh test `TripPlanFitReloadTest` (props `jalur`, `kecocokan`, dan `jadwal` belum ada; halaman lama tanpa kontrol prototype dan muat ulang parsial), dua test `TripPermitWindowTest` (props `izin` belum ada), dua data set `TripPlanDetailTest::test_the_number_of_participants_stays_within_one_and_twelve` (batas masih 30), dua test `TripReactPagesTest` (judul "Rencanakan trip" dan label isian catatan), dan `DesignPrimitivesTest::test_the_segmented_control_is_a_native_radio_group` (prop `penjelas` belum ada).

- [ ] **Step 3: Tulis implementasinya.**

Buat `app/Services/Trips/RencanaTrip.php`:

```php
<?php

namespace App\Services\Trips;

use App\Enums\OfficialStatusValue;
use App\Enums\RouteFitLabel;
use App\Enums\TripType;
use App\Models\HikingGoal;
use App\Models\PermitRequirement;
use App\Models\Trail;
use App\Models\TrailConsideration;
use App\Models\User;
use App\Services\ConsiderationService;
use App\Services\PermitService;
use App\Services\TrailFitService;
use App\Services\Trails\StatusJalurRingkas;
use App\Services\Trails\TrailFigures;
use App\Support\Timezone;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Kelas halaman Rencanakan trip (spec v3 bagian 6.8, prototype 08, rencana induk W4 butir 2).
 *
 * Jalur datang dari ?trail= dan hanya jalur terbit yang dapat direncanakan (R-022). Tanpa
 * jalur, halaman menawarkan simpanan pendaki sendiri, bukan seluruh katalog. Jenis trip,
 * tanggal naik, dan tanggal turun dibaca dari URL dan hanya dipakai bila sah, supaya memuat
 * ulang halaman tidak membuang pilihan pendaki dan URL tidak dapat memaksakan tanggal lampau.
 *
 * Kecocokan untuk rencana, jadwal, dan jendela izin dikirim sebagai closure: muat ulang parsial
 * Inertia (only: kecocokan, jadwal, izin) menghitung ulang hanya ketiganya setiap jenis atau
 * tanggal berubah. Yang dikirim hanya label, kata, alasan, dan apakah jalur lolos syarat dasar;
 * skor internal berhenti di server (BR-09).
 */
final class RencanaTrip
{
    public function __construct(
        private readonly TrailFitService $trailFit,
        private readonly PermitService $permits,
        private readonly StatusJalurRingkas $statusJalur,
        private readonly ConsiderationService $simpanan,
        private readonly TripReminderScheduler $pengingat,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function props(User $user, Request $request): array
    {
        $trail = $request->integer('trail') ? Trail::published()->with('mountain')->find($request->integer('trail')) : null;
        $goal = $request->integer('goal') ? $user->hikingGoals()->find($request->integer('goal')) : $user->hikingGoals()->latest()->first();

        $jenis = TripType::tryFrom((string) $request->query('jenis'))
            ?? $goal?->trip_type
            ?? $user->preference?->preferred_trip_type;
        $tanggal = self::tanggalSah($request->query('tanggal'));
        $selesai = $this->selesai($tanggal, self::tanggalSah($request->query('selesai')), $jenis);

        return [
            'jalur' => $trail === null ? null : $this->jalur($trail),
            'pilihanJalur' => $trail === null ? $this->pilihanJalur($user) : [],
            'awal' => [
                'trail_id' => $trail !== null ? (string) $trail->id : '',
                'hiking_goal_id' => $goal?->id,
                'name' => $trail !== null ? 'Pendakian '.$trail->name : '',
                'planned_date' => $tanggal?->toDateString() ?? '',
                'end_date' => $selesai?->toDateString() ?? '',
                'start_time' => '',
                'participants_count' => '1',
                'trip_type' => $jenis?->value ?? '',
                'notes' => $goal?->notes ?? '',
                'reminders_enabled' => false,
            ],
            'jenisTrip' => array_map(fn (TripType $t) => ['nilai' => $t->value, 'label' => $t->labelPendek(), 'keterangan' => $t->lamaHari().' hari', 'lamaHari' => $t->lamaHari()], TripType::cases()),
            'tanggalTerawal' => Timezone::earliestDateInIndonesia(),
            'batas' => [
                'peserta' => (int) config('hiking.trips.max_participants'),
                'rentangHari' => (int) config('hiking.trips.max_span_days'),
            ],
            'zona' => $trail !== null ? Timezone::label(Timezone::forTrail($trail)) : null,
            'jadwalPengingat' => $this->pengingat->keteranganJadwal(),
            'kecocokan' => fn () => $trail === null ? null : $this->kecocokan($user, $trail, $goal, $jenis, $tanggal),
            'jadwal' => fn () => $tanggal === null ? null : $this->jadwal($tanggal, $selesai, $trail),
            'izin' => fn () => $trail === null ? null : $this->izin($trail, $tanggal),
        ];
    }

    /**
     * Tanggal Y-m-d dari URL, hanya bila bentuknya benar dan tidak sebelum hari ini menurut WIB
     * (lantai yang sama dengan StoreTripRequest, IndonesianDateFloorTest).
     */
    private static function tanggalSah(mixed $nilai): ?Carbon
    {
        if (! is_string($nilai) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $nilai) || $nilai < Timezone::earliestDateInIndonesia()) {
            return null;
        }

        $tanggal = Carbon::createFromFormat('!Y-m-d', $nilai);

        return $tanggal !== false && $tanggal->toDateString() === $nilai ? $tanggal : null;
    }

    /**
     * Tanggal turun: yang dipilih pendaki bila sah (tidak sebelum tanggal naik, tidak melebihi
     * rentang maksimum), atau lama bawaan jenis trip. Pulang hari tidak punya tanggal turun.
     */
    private function selesai(?Carbon $tanggal, ?Carbon $pilihan, ?TripType $jenis): ?Carbon
    {
        if ($tanggal === null) {
            return null;
        }

        $batas = $tanggal->copy()->addDays((int) config('hiking.trips.max_span_days'));

        if ($pilihan !== null && $pilihan->gte($tanggal) && $pilihan->lte($batas)) {
            return $pilihan->isSameDay($tanggal) ? null : $pilihan;
        }

        return $jenis !== null && $jenis->lamaHari() > 1 ? $tanggal->copy()->addDays($jenis->lamaHari() - 1) : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function jalur(Trail $trail): array
    {
        $status = $this->statusJalur->untuk(EloquentCollection::make([$trail]))[$trail->id];

        return [
            'id' => $trail->id,
            'nama' => $trail->name,
            'gunung' => $trail->mountain->name,
            'url' => route('trails.show', $trail),
            'ringkas' => TrailFigures::ringkas($trail),
            'status' => $status,
            'peringatan' => $status['status'] === OfficialStatusValue::CLOSED->value
                ? 'Jalur ini sedang Tutup. Anda tetap dapat merencanakan tanggal mendatang; periksa statusnya lagi sebelum berangkat.'
                : null,
            'ganti' => route('trips.create'),
        ];
    }

    /**
     * Simpanan pendaki yang masih terbit, sebagai pilihan jalur untuk direncanakan.
     *
     * @return array<int, array{nama: string, gunung: string|null, href: string}>
     */
    private function pilihanJalur(User $user): array
    {
        return $this->simpanan->simpanan($user)
            ->filter(fn (TrailConsideration $s) => $s->trail !== null && $s->trail->is_published && $s->trail->archived_at === null)
            ->map(fn (TrailConsideration $s) => [
                'nama' => $s->trail->name,
                'gunung' => $s->trail->mountain?->name,
                'href' => route('trips.create', ['trail' => $s->trail->id]),
            ])
            ->values()
            ->all();
    }

    /**
     * Kecocokan untuk rencana ini: tujuan pendaki (bila ada) dengan jenis dan tanggal yang
     * sedang dipilih. Tanpa jenis trip, kecocokan dasar. Pendaki tanpa profil lengkap tidak
     * dinilai sama sekali (§91).
     *
     * @return array{label: string|null, labelText: string, alasan: string, denganRencana: bool, layak: bool}
     */
    private function kecocokan(User $user, Trail $trail, ?HikingGoal $goal, ?TripType $jenis, ?Carbon $tanggal): array
    {
        if (! $user->hasCompletedProfile()) {
            return [
                'label' => null,
                'labelText' => RouteFitLabel::textFor(null),
                'alasan' => 'Isi profil pendaki supaya kecocokan jalur ini dapat dinilai untuk rencana Anda.',
                'denganRencana' => false,
                'layak' => true,
            ];
        }

        $tujuan = $jenis === null ? null : ($goal?->replicate() ?? new HikingGoal)->fill([
            'trip_type' => $jenis,
            'target_date' => $tanggal,
        ]);

        $fit = $this->trailFit->forTrails($user, EloquentCollection::make([$trail]), $tujuan)[$trail->id];

        return [
            'label' => $fit->label?->value,
            'labelText' => RouteFitLabel::textFor($fit->label),
            'alasan' => $fit->alasan,
            'denganRencana' => $fit->denganRencana,
            'layak' => $fit->eligible,
        ];
    }

    /**
     * Rentang tanggal dan kalimat terbitnya prakiraan BMKG (R-013): sebelum jendela terbit,
     * halaman menyebut kapan prakiraan untuk tanggal itu muncul dan tidak menebak cuaca.
     *
     * @return array{rentang: string, ringkas: string, lama: string, prakiraan: string}
     */
    private function jadwal(Carbon $tanggal, ?Carbon $selesai, ?Trail $trail): array
    {
        $hari = (int) config('hiking.conditions.weather_forecast_days');
        $terbit = $tanggal->copy()->subDays($hari);
        $hariIni = Carbon::now($trail !== null ? Timezone::forTrail($trail) : Timezone::DEFAULT)->toDateString();
        $lama = $selesai === null ? 1 : (int) $tanggal->diffInDays($selesai) + 1;

        return [
            'rentang' => KepalaTrip::rentang($tanggal, $selesai),
            // Bentuk pendek untuk ringkasan di bar aksi, supaya tetap satu baris di 360 px.
            'ringkas' => $selesai === null
                ? $tanggal->translatedFormat('j M')
                : $tanggal->translatedFormat($tanggal->month === $selesai->month ? 'j' : 'j M').' sampai '.$selesai->translatedFormat('j M'),
            'lama' => $lama.' hari',
            'prakiraan' => $terbit->toDateString() > $hariIni
                ? 'Prakiraan BMKG untuk '.$tanggal->translatedFormat('D, j M').' terbit mulai '.$terbit->translatedFormat('D, j M').'. Sebelum itu Memount tidak menebak cuacanya.'
                : 'Prakiraan BMKG untuk '.$tanggal->translatedFormat('D, j M').' sudah dapat terbit. Prakiraannya tampil di cek kesiapan begitu tersedia dari BMKG.',
        ];
    }

    /**
     * Jendela pemesanan izin untuk tanggal yang dipilih, dengan tanggal buka dan tutupnya
     * (spec v3 bagian 6.8). Keadaannya dari PermitService::bookingWindowFor(), satu-satunya
     * tempat aritmetika jendela; aturan tanpa jendela tercatat tidak pernah ditulis "sedang
     * dibuka" (R-007). Kuota hanya disebut sebagai aturan tercatat, tidak pernah sebagai sisa
     * kuota (§43).
     *
     * @return array{keadaan: string, judul: string, keterangan: string, tautan: string|null, kuota: string}|null
     */
    private function izin(Trail $trail, ?Carbon $tanggal): ?array
    {
        $aturan = $this->permits->requirementFor($trail);

        if ($aturan === null) {
            return null;
        }

        $buka = $aturan->booking_opens_days_before;
        $tutup = $aturan->booking_closes_days_before;
        $rentangH = match (true) {
            $buka !== null && $tutup !== null => "H-{$buka} sampai H-{$tutup}",
            $tutup !== null => "ditutup H-{$tutup}",
            default => "dibuka H-{$buka}",
        };
        $dasar = ['tautan' => $aturan->booking_url, 'kuota' => self::kuota($aturan)];

        if ($buka === null && $tutup === null) {
            return ['keadaan' => 'tanpaJendela', 'judul' => 'Jalur ini mewajibkan izin', 'keterangan' => 'Jendela pemesanannya belum tercatat di Memount. Periksa ke '.$aturan->authority.'.'] + $dasar;
        }

        if ($tanggal === null) {
            return ['keadaan' => 'tanpaTanggal', 'judul' => 'Jalur ini mewajibkan izin', 'keterangan' => 'Pilih tanggal naik untuk melihat kapan pemesanannya dibuka dan ditutup. Dikelola '.$aturan->authority.' ('.$rentangH.').'] + $dasar;
        }

        $jendela = $this->permits->bookingWindowFor($trail, $tanggal, $aturan);
        $tanggalBuka = $buka !== null ? self::hari($tanggal->copy()->subDays($buka)) : null;
        $tanggalTutup = $tutup !== null ? self::hari($tanggal->copy()->subDays($tutup)) : null;
        $penutup = ' Memount menautkan sistem resmi dan tidak menjual izin.';

        return match ($jendela['state'] ?? null) {
            PermitService::BOOKING_DITUTUP => [
                'keadaan' => 'tutup',
                'judul' => 'Pemesanan izin sudah tutup sejak '.$tanggalTutup,
                'keterangan' => 'Jendela pemesanan '.$aturan->authority.' untuk tanggal ini sudah lewat ('.$rentangH.'). Pilih tanggal lain atau tanyakan langsung ke pengelola.',
            ] + $dasar,
            PermitService::BOOKING_BELUM_DIBUKA => [
                'keadaan' => 'belum',
                'judul' => 'Pemesanan izin dibuka mulai '.$tanggalBuka,
                'keterangan' => 'Dibuka di sistem resmi '.$aturan->authority.' ('.$rentangH.').'.$penutup,
            ] + $dasar,
            default => [
                'keadaan' => 'terbuka',
                'judul' => $tanggalTutup !== null ? 'Pemesanan izin terbuka sampai '.$tanggalTutup : 'Pemesanan izin sedang terbuka',
                'keterangan' => ($tanggalBuka !== null ? 'Dibuka sejak '.$tanggalBuka.' di sistem resmi ' : 'Di sistem resmi ').$aturan->authority.' ('.$rentangH.').'.$penutup,
            ] + $dasar,
        };
    }

    private static function hari(CarbonInterface $tanggal): string
    {
        return $tanggal->translatedFormat('D, j M');
    }

    private static function kuota(PermitRequirement $aturan): string
    {
        if ($aturan->daily_quota === null) {
            return 'Kuota harian jalur ini belum ada sumber terverifikasi. Pastikan saat memesan izin.';
        }

        $jumlah = number_format($aturan->daily_quota, 0, ',', '.');

        return $aturan->verified_at !== null
            ? 'Kuota harian tercatat '.$jumlah.' pendaki. Sisa kuota hanya ada di sistem resmi.'
            : 'Kuota harian tercatat '.$jumlah.' pendaki, belum terverifikasi. Pastikan saat memesan izin.';
    }
}
```

Buat `resources/js/components/trips/LangkahRingkas.tsx`:

```tsx
import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';

export type KunciLangkah = 'jalur' | 'rencana' | 'persiapan' | 'kesiapan';

const urutan: ReadonlyArray<{ kunci: KunciLangkah; judul: string }> = [
    { kunci: 'jalur', judul: 'Jalur' },
    { kunci: 'rencana', judul: 'Rencana' },
    { kunci: 'persiapan', judul: 'Persiapan' },
    { kunci: 'kesiapan', judul: 'Cek' },
];

interface LangkahRingkasProps {
    /** Langkah halaman ini; diberi aria-current="step". */
    kini: KunciLangkah;
    /** Tujuan tiap langkah yang dapat dibuka dari halaman ini; tanpa tujuan, langkah hanya teks. */
    tautan?: Partial<Record<KunciLangkah, string>>;
}

/**
 * Empat langkah yang sama dengan ruang kerja trip, sebagai baris ringkas di atas halaman
 * Rencanakan, Persiapan, dan Cek (prototype 08 sampai 10). Langkah halaman ini ditandai bilah
 * merek di bawahnya, tebal huruf, dan aria-current; bukan warna saja. Langkah yang punya
 * tujuan menjadi tautan, sehingga pendaki dapat kembali ke langkah lain tanpa ke ruang kerja.
 */
export function LangkahRingkas({ kini, tautan = {} }: LangkahRingkasProps) {
    return (
        <nav aria-label="Langkah trip" className="mb-4">
            <ol className="flex gap-x-1">
                {urutan.map((l) => {
                    const aktif = l.kunci === kini;
                    const href = aktif ? undefined : tautan[l.kunci];
                    const isi = kelas(
                        'flex min-h-11 items-center justify-center break-words border-b-2 px-1 text-center text-kecil',
                        aktif ? 'border-brand-700 font-bold text-primary' : 'border-transparent font-semibold text-secondary',
                    );

                    return (
                        <li key={l.kunci} aria-current={aktif ? 'step' : undefined} className="min-w-0 flex-1">
                            {href ? (
                                <Tautan href={href} className={kelas(isi, 'hover:text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600')}>
                                    {l.judul}
                                </Tautan>
                            ) : (
                                <span className={isi}>{l.judul}</span>
                            )}
                        </li>
                    );
                })}
            </ol>
        </nav>
    );
}
```

Buat `resources/js/components/trips/rencana/KotakIzin.tsx`:

```tsx
import { fokusTerlihat, umpanTekan } from '@/components/ui/gaya';
import { Icon } from '@/components/ui/Icon';
import { kelas } from '@/lib/kelas';

/** Jendela izin untuk tanggal yang dipilih, dari App\Services\Trips\RencanaTrip::izin(). */
export interface IzinRencana {
    keadaan: 'terbuka' | 'belum' | 'tutup' | 'tanpaTanggal' | 'tanpaJendela';
    judul: string;
    keterangan: string;
    tautan: string | null;
    kuota: string;
}

/**
 * Kotak jendela pemesanan izin (prototype 08): judul yang menyebut tanggal buka atau tutup,
 * pengelola dan aturannya, dan tautan ke sistem resmi. Jendela yang sudah tutup memakai nada
 * dan ikon peringatan, dan judulnya berkata "sudah tutup", jadi bedanya terbaca dari ikon dan
 * kata, bukan warna saja. Memount tidak memesan izin.
 */
export function KotakIzin({ izin, className }: { izin: IzinRencana; className?: string }) {
    const tutup = izin.keadaan === 'tutup';

    return (
        <div className={kelas('rounded-lg border p-4', tutup ? 'border-warn-300 bg-warn-50' : 'border-brand-200 bg-brand-50', className)}>
            <div className="flex gap-3">
                {tutup ? <Icon name="warning" size={20} className="mt-0.5 text-warn-900" /> : null}
                <div className="min-w-0">
                    <p className={kelas('text-isi font-bold', tutup ? 'text-warn-900' : 'text-primary')}>{izin.judul}</p>
                    <p className="mt-1 break-words text-kecil text-secondary">{izin.keterangan}</p>
                    {izin.tautan ? (
                        <a
                            href={izin.tautan}
                            target="_blank"
                            rel="noopener noreferrer"
                            className={kelas('mt-2 inline-flex min-h-11 items-center gap-2 text-kecil font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900', fokusTerlihat, umpanTekan)}
                        >
                            Pesan di sistem resmi
                            <Icon name="arrow-square-out" size={16} />
                            <span className="sr-only">(membuka tab baru)</span>
                        </a>
                    ) : null}
                </div>
            </div>
        </div>
    );
}
```

`app/Http/Controllers/Trips/TripController.php`:

1. Ganti:

```php
use App\Http\Requests\Trips\UpdateTripRequest;
use App\Models\Trail;
use App\Models\TripPlan;
```

dengan:

```php
use App\Http\Requests\Trips\UpdateTripRequest;
use App\Models\TripPlan;
```

2. Ganti:

```php
use App\Services\Trips\KepalaTrip;
use App\Services\Trips\TripLifecycle;
```

dengan:

```php
use App\Services\Trips\KepalaTrip;
use App\Services\Trips\RencanaTrip;
use App\Services\Trips\TripLifecycle;
```

3. Ganti:

```php
    /**
     * Isian awal dibawa dari jalur yang dipilih (?trail=) dan dari rencana pendakian
     * (?goal=, atau rencana terakhir), termasuk catatannya, supaya konteks yang diketik
     * pendaki pada rencana tidak hilang.
     */
    public function create(Request $request, TripReminderScheduler $pengingat): Response
    {
        $user = $request->user();
        $trail = $request->integer('trail') ?: null;
        $goal = $request->integer('goal') ?: null;

        $dipilih = $trail ? Trail::published()->find($trail) : null;

        $goalTerakhir = $goal
            ? $user->hikingGoals()->find($goal)
            : $user->hikingGoals()->latest()->first();

        return Inertia::render('Trips/Create', [
            'title' => 'Buat Rencana Trip',
            'awal' => [
                'trail_id' => $trail !== null ? (string) $trail : '',
                'hiking_goal_id' => $goalTerakhir?->id,
                'name' => $dipilih ? 'Pendakian '.$dipilih->name : '',
                'planned_date' => $goalTerakhir?->target_date?->toDateString() ?? '',
                'end_date' => '',
                'start_time' => '',
                'participants_count' => '',
                'trip_type' => $goalTerakhir?->trip_type?->value ?? $user->preference?->preferred_trip_type?->value ?? '',
                'notes' => $goalTerakhir?->notes ?? '',
                'reminders_enabled' => false,
            ],
            'trails' => Trail::published()->with('mountain')->orderBy('name')->get()
                ->map(fn (Trail $t) => ['value' => (string) $t->id, 'label' => $t->name.' - '.$t->mountain?->name])
                ->all(),
            'tripTypes' => self::tipePerjalanan(),
            'tanggalTerawal' => Timezone::earliestDateInIndonesia(),
            'batas' => self::batasRencana(),
            'jadwalPengingat' => $pengingat->keteranganJadwal(),
        ]);
```

dengan:

```php
    /**
     * Rencanakan trip (spec v3 bagian 6.8): isian awal dari jalur yang dipilih (?trail=), dari
     * rencana pendakian (?goal=, atau rencana terakhir) termasuk catatannya, dan dari pilihan
     * jenis dan tanggal di URL, disusun kelas halaman RencanaTrip.
     */
    public function create(Request $request, RencanaTrip $rencana): Response
    {
        return Inertia::render('Trips/Create', [
            'title' => 'Rencanakan trip',
            ...$rencana->props($request->user(), $request),
        ]);
```

`config/hiking.php`:

1. Ganti:

```php
    'trips' => [
        // Batas peserta satu rencana. Rombongan yang lebih besar diurus lewat
        // pengelola kawasan, bukan lewat satu rencana pribadi.
        'max_participants' => 30,

        // Tanggal selesai paling lambat sekian hari setelah tanggal mulai. Rentang
```

dengan:

```php
    'trips' => [
        // Batas peserta satu rencana (spec v3 bagian 6.8: 1 sampai 12). Rombongan yang
        // lebih besar diurus lewat pengelola kawasan, bukan lewat satu rencana pribadi.
        'max_participants' => 12,

        // Tanggal selesai paling lambat sekian hari setelah tanggal mulai. Rentang
```

`resources/js/components/ui/KontrolSegmen.tsx`:

1. Ganti:

```tsx
    onUbah: (nilai: T) => void;
    className?: string;
```

dengan:

```tsx
    onUbah: (nilai: T) => void;
    /** id unsur yang menjelaskan grup ini, misalnya galat validasinya (aria-describedby). */
    penjelas?: string;
    className?: string;
```

2. Ganti:

```tsx
 */
export function KontrolSegmen<T extends string>({ nama, label, pilihan, nilai, onUbah, className }: KontrolSegmenProps<T>) {
    return (
        <fieldset className={className}>
            <legend className="sr-only">{label}</legend>
```

dengan:

```tsx
 */
export function KontrolSegmen<T extends string>({ nama, label, pilihan, nilai, onUbah, penjelas, className }: KontrolSegmenProps<T>) {
    return (
        <fieldset className={className} aria-describedby={penjelas}>
            <legend className="sr-only">{label}</legend>
```

`resources/js/components/ui/Stepper.tsx`:

1. Ganti:

```tsx
    onUbah: (nilai: number) => void;
    className?: string;
```

dengan:

```tsx
    onUbah: (nilai: number) => void;
    /** id unsur yang menjelaskan grup ini, misalnya galat validasinya (aria-describedby). */
    penjelas?: string;
    className?: string;
```

2. Ganti:

```tsx
 */
export function Stepper({ label, keterangan, nilai, min, max, onUbah, className }: StepperProps) {
    const id = useId();
```

dengan:

```tsx
 */
export function Stepper({ label, keterangan, nilai, min, max, onUbah, penjelas, className }: StepperProps) {
    const id = useId();
```

3. Ganti:

```tsx
    return (
        <div role="group" aria-labelledby={`${id}-label`} className={kelas('flex items-center justify-between gap-4', className)}>
            <div className="min-w-0">
```

dengan:

```tsx
    return (
        <div role="group" aria-labelledby={`${id}-label`} aria-describedby={penjelas} className={kelas('flex items-center justify-between gap-4', className)}>
            <div className="min-w-0">
```

Tulis ulang `resources/js/pages/Trips/Create.tsx` utuh:

```tsx
import { router, useForm, usePage } from '@inertiajs/react';
import { useState, type FormEvent, type ReactNode } from 'react';
import { BarisStatus } from '@/components/trails/BarisStatus';
import { LabelKecocokan, type LabelFit } from '@/components/trails/LabelKecocokan';
import type { StatusRingkas } from '@/components/trails/types';
import { LangkahRingkas } from '@/components/trips/LangkahRingkas';
import { KotakIzin, type IzinRencana } from '@/components/trips/rencana/KotakIzin';
import { BarAksi } from '@/components/ui/BarAksi';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { FormField, InputError, Label, Textarea } from '@/components/ui/Field';
import { Icon } from '@/components/ui/Icon';
import { KontrolSegmen } from '@/components/ui/KontrolSegmen';
import { PageHeader } from '@/components/ui/PageHeader';
import { Sakelar } from '@/components/ui/Sakelar';
import { Stepper } from '@/components/ui/Stepper';
import { Tautan } from '@/components/ui/Tautan';
import { AppLayout } from '@/layouts/AppLayout';
import { index as trailsIndex } from '@/routes/trails';
import { index as tripsIndex, store } from '@/routes/trips';
import type { PropsBersama } from '@/types';

interface Isian {
    trail_id: string;
    hiking_goal_id: number | null;
    name: string;
    planned_date: string;
    end_date: string;
    start_time: string;
    participants_count: string;
    trip_type: string;
    notes: string;
    reminders_enabled: boolean;
}

interface JalurRencana {
    id: number;
    nama: string;
    gunung: string;
    url: string;
    ringkas: string;
    status: StatusRingkas;
    peringatan: string | null;
    ganti: string;
}

interface Kecocokan {
    label: LabelFit;
    labelText: string;
    alasan: string;
    denganRencana: boolean;
    layak: boolean;
}

interface JenisTrip {
    nilai: string;
    label: string;
    keterangan: string;
    lamaHari: number;
}

interface Props extends PropsBersama {
    jalur: JalurRencana | null;
    pilihanJalur: Array<{ nama: string; gunung: string | null; href: string }>;
    awal: Isian;
    jenisTrip: JenisTrip[];
    tanggalTerawal: string;
    batas: { peserta: number; rentangHari: number };
    zona: string | null;
    jadwalPengingat: string;
    kecocokan: Kecocokan | null;
    jadwal: { rentang: string; ringkas: string; lama: string; prakiraan: string } | null;
    izin: IzinRencana | null;
}

const tautanTeks =
    'inline-flex min-h-11 items-center text-kecil font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600';

/** Tanggal Y-m-d ditambah sekian hari, dihitung di UTC supaya zona peramban tidak menggeser hari. */
function tambahHari(tanggal: string, hari: number): string {
    const d = new Date(`${tanggal}T00:00:00Z`);
    d.setUTCDate(d.getUTCDate() + hari);

    return d.toISOString().slice(0, 10);
}

/**
 * Rencanakan trip (spec v3 bagian 6.8, prototype 08). Kecocokan untuk rencana, jadwal, dan
 * jendela izin dihitung ulang server setiap jenis atau tanggal berubah, lewat muat ulang parsial
 * yang hanya meminta ketiganya; klien tidak pernah menilai sendiri. Jalur berstatus Tutup tetap
 * dapat direncanakan untuk tanggal mendatang, dengan peringatan statusnya sekarang. Halaman
 * tugas: bilah tab disembunyikan, dan ringkasan rencana menempel di bar aksi bersama "Simpan
 * rencana". Pengingat surel mati sampai pendaki menyalakannya sendiri.
 */
export default function Create() {
    const { jalur, pilihanJalur, awal, jenisTrip, tanggalTerawal, batas, zona, jadwalPengingat, kecocokan, jadwal, izin } = usePage<Props>().props;
    const form = useForm<Isian>(awal);
    const [memuat, setMemuat] = useState(false);

    const muatUlang = (jenis: string, tanggal: string, selesai: string) => {
        router.reload({
            only: ['kecocokan', 'jadwal', 'izin'],
            data: { jenis, tanggal, selesai },
            replace: true,
            onStart: () => setMemuat(true),
            onFinish: () => setMemuat(false),
        });
    };

    const selesaiUntuk = (jenis: string, tanggal: string) => {
        const lama = jenisTrip.find((j) => j.nilai === jenis)?.lamaHari ?? 1;

        return tanggal && lama > 1 ? tambahHari(tanggal, lama - 1) : '';
    };

    const ubahJenis = (jenis: string) => {
        const selesai = selesaiUntuk(jenis, form.data.planned_date);
        form.setData((d) => ({ ...d, trip_type: jenis, end_date: selesai }));
        muatUlang(jenis, form.data.planned_date, selesai);
    };

    const ubahTanggal = (tanggal: string) => {
        const selesai = selesaiUntuk(form.data.trip_type, tanggal);
        form.setData((d) => ({ ...d, planned_date: tanggal, end_date: selesai }));
        muatUlang(form.data.trip_type, tanggal, selesai);
    };

    const ubahSelesai = (selesai: string) => {
        form.setData('end_date', selesai);
        muatUlang(form.data.trip_type, form.data.planned_date, selesai);
    };

    const kirim = (e: FormEvent) => {
        e.preventDefault();
        form.post(store.url());
    };

    const jenisDipilih = jenisTrip.find((j) => j.nilai === form.data.trip_type);

    return (
        <div className="py-6">
            <div className="mx-auto max-w-2xl px-4 sm:px-6">
                <PageHeader
                    induk={{ href: tripsIndex.url(), label: 'Trip' }}
                    title="Rencanakan trip"
                    description={jalur === null ? 'Pilih jalur yang akan Anda rencanakan.' : undefined}
                />

                {jalur === null ? (
                    pilihanJalur.length === 0 ? (
                        <EmptyState jenis="pertama" title="Belum ada jalur tersimpan" description="Simpan jalur dari Jelajah, lalu rencanakan tripnya dari sini atau dari rincian jalur.">
                            <Button href={trailsIndex.url()}>Buka Jelajah</Button>
                        </EmptyState>
                    ) : (
                        <section aria-labelledby="dari-simpanan">
                            <h2 id="dari-simpanan" className="text-bagian text-primary">
                                Dari simpanan Anda
                            </h2>
                            <ul className="mt-3 divide-y divide-subtle rounded-lg border border-subtle bg-surface">
                                {pilihanJalur.map((j) => (
                                    <li key={j.href}>
                                        <Tautan
                                            href={j.href}
                                            className="flex min-h-11 items-center justify-between gap-3 px-4 py-3 hover:bg-surface-sunken focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-600"
                                        >
                                            <span className="min-w-0">
                                                <span className="block break-words text-isi font-bold text-primary">{j.nama}</span>
                                                {j.gunung ? <span className="block text-kecil text-secondary">{j.gunung}</span> : null}
                                            </span>
                                            <Icon name="caret-right" size={20} className="text-muted" />
                                        </Tautan>
                                    </li>
                                ))}
                            </ul>
                            <p className="mt-4 text-kecil text-secondary">
                                Jalur lain dapat dipilih dari{' '}
                                <Tautan href={trailsIndex.url()} className="font-bold text-brand-ink underline underline-offset-2">
                                    Jelajah
                                </Tautan>
                                .
                            </p>
                        </section>
                    )
                ) : (
                    <form onSubmit={kirim} className="space-y-8">
                        <LangkahRingkas kini="rencana" tautan={{ jalur: jalur.url }} />

                        <section aria-labelledby="jalur-rencana" className="rounded-utama border border-subtle bg-surface p-5">
                            <div className="flex items-start justify-between gap-3">
                                <div className="min-w-0">
                                    <h2 id="jalur-rencana" className="break-words text-subjudul text-primary">
                                        {jalur.nama}
                                    </h2>
                                    <p className="text-kecil text-secondary">{jalur.gunung}</p>
                                    <p data-angka className="mt-1 text-kecil text-secondary">
                                        {jalur.ringkas}
                                    </p>
                                </div>
                                <Tautan href={jalur.ganti} className={tautanTeks}>
                                    Ganti
                                </Tautan>
                            </div>

                            {kecocokan ? (
                                <div aria-live="polite" aria-busy={memuat || undefined} className="mt-4 rounded-tombol bg-surface-sunken p-4">
                                    <p className="flex flex-wrap items-center gap-2">
                                        <LabelKecocokan nilai={kecocokan.label} label={kecocokan.labelText} />
                                        <span className="text-meta text-secondary">{kecocokan.denganRencana ? 'untuk rencana ini' : 'Kecocokan dasar'}</span>
                                    </p>
                                    {!kecocokan.layak ? <p className="mt-2 text-kecil font-bold text-primary">Tidak memenuhi syarat dasar untuk rencana ini.</p> : null}
                                    <p className="mt-2 text-kecil text-primary">{kecocokan.alasan}</p>
                                    {memuat ? <p className="mt-2 text-meta text-muted">Menghitung ulang untuk pilihan baru.</p> : null}
                                </div>
                            ) : null}

                            <div className="mt-4">
                                <BarisStatus status={jalur.status} />
                                <p className="mt-1 text-meta text-secondary">Status resmi menjelaskan ketentuan pengelola, bukan jaminan keselamatan.</p>
                                {jalur.peringatan ? <p className="mt-2 text-kecil font-bold text-warn-900">{jalur.peringatan}</p> : null}
                            </div>
                        </section>

                        <section aria-labelledby="judul-jenis">
                            <h2 id="judul-jenis" className="text-bagian text-primary">
                                Jenis trip
                            </h2>
                            <KontrolSegmen
                                nama="trip_type"
                                label="Jenis trip"
                                pilihan={jenisTrip.map((j) => ({ nilai: j.nilai, label: j.label, keterangan: j.keterangan }))}
                                nilai={form.data.trip_type}
                                onUbah={ubahJenis}
                                penjelas={form.errors.trip_type ? 'trip_type-error' : undefined}
                                className="mt-3"
                            />
                            <InputError id="trip_type-error" messages={form.errors.trip_type} className="mt-2" />
                        </section>

                        <section aria-labelledby="judul-tanggal" className="space-y-4">
                            <h2 id="judul-tanggal" className="text-bagian text-primary">
                                Tanggal
                            </h2>
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <FormField
                                    name="planned_date"
                                    type="date"
                                    label="Tanggal naik"
                                    min={tanggalTerawal}
                                    required
                                    value={form.data.planned_date}
                                    onChange={(e) => ubahTanggal(e.target.value)}
                                    error={form.errors.planned_date}
                                />
                                {jenisDipilih && jenisDipilih.lamaHari > 2 ? (
                                    <FormField
                                        name="end_date"
                                        type="date"
                                        label="Tanggal turun"
                                        min={form.data.planned_date || tanggalTerawal}
                                        hint={`Paling lambat ${batas.rentangHari} hari setelah tanggal naik.`}
                                        value={form.data.end_date}
                                        onChange={(e) => ubahSelesai(e.target.value)}
                                        error={form.errors.end_date}
                                    />
                                ) : null}
                            </div>
                            {jadwal ? (
                                <p data-angka className="text-isi font-bold text-primary">
                                    {jadwal.rentang} ({jadwal.lama})
                                </p>
                            ) : (
                                <p className="text-kecil text-secondary">Pilih tanggal naik untuk melihat jendela izin dan jadwal prakiraan BMKG.</p>
                            )}
                            {izin ? <KotakIzin izin={izin} /> : null}
                            {jadwal ? <p className="text-kecil text-secondary">{jadwal.prakiraan}</p> : null}
                        </section>

                        <section aria-labelledby="judul-rombongan" className="space-y-5">
                            <h2 id="judul-rombongan" className="text-bagian text-primary">
                                Jam dan rombongan
                            </h2>
                            <FormField
                                name="start_time"
                                type="time"
                                label="Jam mulai (opsional)"
                                hint={zona ? `Waktu setempat gunung (${zona}).` : undefined}
                                value={form.data.start_time}
                                onChange={(e) => form.setData('start_time', e.target.value)}
                                error={form.errors.start_time}
                            />
                            <div>
                                <Stepper
                                    label="Peserta"
                                    keterangan="Termasuk Anda"
                                    nilai={Number(form.data.participants_count) || 1}
                                    min={1}
                                    max={batas.peserta}
                                    onUbah={(n) => form.setData('participants_count', String(n))}
                                    penjelas={form.errors.participants_count ? 'participants_count-error' : undefined}
                                    className="rounded-lg border border-subtle bg-surface p-4"
                                />
                                <InputError id="participants_count-error" messages={form.errors.participants_count} className="mt-2" />
                                {izin ? <p className="mt-2 text-kecil text-secondary">{izin.kuota}</p> : null}
                            </div>
                        </section>

                        <section aria-labelledby="judul-catatan" className="space-y-5">
                            <h2 id="judul-catatan" className="text-bagian text-primary">
                                Nama dan catatan
                            </h2>
                            <FormField
                                name="name"
                                label="Nama trip"
                                required
                                maxLength={120}
                                value={form.data.name}
                                onChange={(e) => form.setData('name', e.target.value)}
                                error={form.errors.name}
                            />
                            <div>
                                <Label htmlFor="notes">Catatan (opsional)</Label>
                                <Textarea
                                    id="notes"
                                    name="notes"
                                    rows={3}
                                    maxLength={1000}
                                    value={form.data.notes}
                                    onChange={(e) => form.setData('notes', e.target.value)}
                                    aria-invalid={form.errors.notes ? true : undefined}
                                    aria-describedby={form.errors.notes ? 'notes-error' : undefined}
                                />
                                <InputError id="notes-error" messages={form.errors.notes} className="mt-2" />
                            </div>
                            <Sakelar
                                judul="Ingatkan saya"
                                keterangan={jadwalPengingat}
                                nyala={form.data.reminders_enabled}
                                onUbah={(nyala) => form.setData('reminders_enabled', nyala)}
                                className="rounded-lg border border-subtle bg-surface px-4 py-2"
                            />
                        </section>

                        <BarAksi
                            label="Aksi rencana trip"
                            ringkasan={
                                // Nama jalur sudah di kartu atas; bar hanya membawa pilihan yang berubah,
                                // supaya tetap satu baris ringkasan di 360 px.
                                <p data-angka className="break-words">
                                    {jenisDipilih ? <span className="font-bold text-primary">{jenisDipilih.label}</span> : 'Pilih jenis trip'}
                                    {jadwal ? `, ${jadwal.ringkas} (${jadwal.lama})` : ', tanggal belum dipilih'}
                                </p>
                            }
                        >
                            <Button type="submit" processing={form.processing}>
                                Simpan rencana
                            </Button>
                        </BarAksi>
                    </form>
                )}
            </div>
        </div>
    );
}

Create.layout = (page: ReactNode) => <AppLayout bilahTab={false}>{page}</AppLayout>;
```

`tests/Pratinjau/HalamanPratinjauTest.php`:

1. Ganti:

```php
        $this->tulisPratinjau('ruang-kerja', $this->get(self::ASAL.'/trips/'.$trip->id), 'Trips/Show');
    }
```

dengan:

```php
        $this->tulisPratinjau('ruang-kerja', $this->get(self::ASAL.'/trips/'.$trip->id), 'Trips/Show');
    }

    /**
     * Rencanakan trip W4 (acuan 08): Gede via Cibodas, camping dua hari, lima belas hari lagi,
     * jadi jendela izin data contoh terbuka dan prakiraan BMKG belum terbit.
     */
    public function test_rencanakan(): void
    {
        $this->sebagaiBudi();
        $jalur = Trail::where('slug', 'gede-via-cibodas')->sole();
        $naik = now('Asia/Jakarta')->addDays(15);

        $this->tulisPratinjau('rencanakan', $this->get(self::ASAL.'/trips/create?trail='.$jalur->id.'&jenis=CAMPING&tanggal='.$naik->toDateString().'&selesai='.$naik->copy()->addDay()->toDateString()), 'Trips/Create');
    }
```

- [ ] **Step 4: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Services/Trips/RencanaTrip.php app/Http/Controllers/Trips/TripController.php config/hiking.php tests/Feature/TripPlanFitReloadTest.php tests/Feature/TripPermitWindowTest.php tests/Feature/TripPlanDetailTest.php tests/Feature/TripReactPagesTest.php tests/Feature/DesignPrimitivesTest.php tests/Pratinjau/HalamanPratinjauTest.php`, lalu `php artisan test --compact --filter='TripPlanFitReload|TripPermitWindow|TripPlanDetail|TripReactPages|GoalInputIsUsed|IndonesianDateFloor|CoreJourney|NavigationConsistency|KeyboardAndScreenReader|DesignPrimitives'`
Expected: PASS. Pada simulasi rencana: 130 test lulus.

- [ ] **Step 5: Suite, tipe, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 1.796 test, 1.794 lulus, 2 dilewati.

Run: `npx tsc --noEmit`
Expected: keluar tanpa keluaran galat.

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `php artisan test --compact --testsuite=Pratinjau`
Expected: build `✓ built`; Pratinjau lulus termasuk `test_rencanakan`. Pada simulasi rencana: 16 test lulus.

- [ ] **Step 6: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 4: Persiapan

Induk W4 butir 3; spec bagian 6.10; ruling W4-R19 sampai W4-R23. `/trips/{trip}/preparation` lewat kelas halaman
`HalamanPersiapan`: kepala trip, baris langkah, keadaan persiapan sebagai hitungan butir kritis dengan ruas per butir
dan nama butir yang belum dikonfirmasi, kelompok `PreparationCategory` dengan hitungannya dan tanda Kritis, butir
cuaca yang menunggu BMKG, tautan izin ke sistem resmi beserta tanggal tutupnya, centang optimis dengan Urungkan,
daftar pengingat, dan "Lanjut ke cek kesiapan". Server menolak konfirmasi butir cuaca sebelum prakiraan terbit.

**Files:**
- Create: `app/Services/Trips/HalamanPersiapan.php`
- Modify: `app/Http/Controllers/Trips/PreparationController.php`, `app/Services/PreparationService.php`, `resources/js/pages/Trips/Preparation.tsx`
- Test: `tests/Feature/PreparationUndoTest.php` (baru), `tests/Pratinjau/HalamanPratinjauTest.php`, `tests/Unit/PreparationServiceTest.php`

**Interfaces:**
- Consumes: `KepalaTrip`, `LangkahRingkas` (Tugas 1 dan 3), `PengingatTrip`, `TripSteps::hitungPersiapan()` dan `teksPersiapan()`, `PermitService::requirementFor()`, `TripReminderScheduler::untukHalaman()`, `useKerangka()` (W2), `KotakCentang` (W1).
- Produces:
  - `App\Services\PreparationService::kritis(Collection $items): array{dikonfirmasi: int, berlaku: int, belum: array<int, string>}`, `PreparationService::terbitPrakiraan(TripPlan $trip): ?Carbon`, `PreparationService::penolakanKonfirmasi(TripPlan $trip, TripPreparationItem $item, PreparationStatus $status): ?string` (dipakai Tugas 6);
  - `App\Services\Trips\HalamanPersiapan::props(TripPlan $trip): array` dengan kunci `kepala`, `tautanLangkah`, `kritis`, `kemajuan`, `kategori` (`kunci`, `nama`, `dikonfirmasi`, `berlaku`, `items`), `pengingat`; butir: `id`, `label`, `keterangan`, `kritis`, `status`, `menunggu`, `tautan`.

- [ ] **Step 1: Tulis test yang gagal.**

Buat `tests/Feature/PreparationUndoTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\PreparationCategory;
use App\Enums\PreparationStatus;
use App\Models\PermitRequirement;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Models\User;
use App\Services\PreparationService;
use Database\Seeders\PreparationTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Persiapan (spec v3 bagian 6.10, prototype 09, W4 Tugas 4): centang optimis dengan Urungkan
 * yang mengirim status sebelumnya, hitungan butir kritis, butir yang menunggu BMKG, dan
 * tautan izin ke sistem resmi. Butir trip orang lain ditolak, dan isi badan permintaan selain
 * status tidak pernah sampai ke basis data.
 *
 * Waktu dibekukan pada 25 September 2026 pukul 10.00 WIB; trip pada 10 Oktober (H-15).
 */
class PreparationUndoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-09-25 03:00:00', 'UTC'));
    }

    private function trip(string $tanggal = '2026-10-10'): TripPlan
    {
        return TripPlan::factory()->create(['trail_id' => Trail::factory()->published()->create()->id, 'planned_date' => $tanggal]);
    }

    private function butir(TripPlan $trip, PreparationCategory $kategori, string $label, bool $kritis = true, PreparationStatus $status = PreparationStatus::NOT_CONFIRMED): TripPreparationItem
    {
        return TripPreparationItem::create([
            'trip_plan_id' => $trip->id,
            'category' => $kategori->value,
            'label' => $label,
            'is_critical' => $kritis,
            'status' => $status->value,
        ]);
    }

    private function setel(TripPlan $trip, TripPreparationItem $butir, PreparationStatus $status, array $tambahan = []): TestResponse
    {
        return $this->actingAs($trip->user)->post(route('trips.preparation.update', ['trip' => $trip, 'item' => $butir->id]), ['status' => $status->value] + $tambahan);
    }

    /**
     * Urungkan mengirim status sebelumnya lewat rute yang sama: centang lalu urungkan
     * memulihkan "Belum dikonfirmasi", dan "Tidak berlaku" lalu urungkan memulihkan
     * "Dikonfirmasi".
     */
    public function test_undo_restores_the_previous_status(): void
    {
        $trip = $this->trip();
        $headlamp = $this->butir($trip, PreparationCategory::EQUIPMENT, 'Headlamp dan baterai cadangan');

        $this->setel($trip, $headlamp, PreparationStatus::CONFIRMED)->assertRedirect(route('trips.preparation', $trip));
        $this->assertSame(PreparationStatus::CONFIRMED, $headlamp->fresh()->status);

        $this->setel($trip, $headlamp, PreparationStatus::NOT_CONFIRMED)->assertRedirect(route('trips.preparation', $trip));
        $this->assertSame(PreparationStatus::NOT_CONFIRMED, $headlamp->fresh()->status);

        $this->setel($trip, $headlamp, PreparationStatus::CONFIRMED);
        $this->setel($trip, $headlamp, PreparationStatus::NOT_APPLICABLE);
        $this->setel($trip, $headlamp, PreparationStatus::CONFIRMED);
        $this->assertSame(PreparationStatus::CONFIRMED, $headlamp->fresh()->status);
    }

    /**
     * PRD §77: butir trip orang lain ditolak, dan pemilik, kritis, label, atau trip yang
     * diselipkan di badan permintaan tidak pernah ditulis (pertahanan mass assignment).
     */
    public function test_another_hikers_item_is_refused_and_the_body_is_not_trusted(): void
    {
        $trip = $this->trip();
        $butir = $this->butir($trip, PreparationCategory::EQUIPMENT, 'Jas hujan');
        $tripLain = $this->trip();

        $this->actingAs(User::factory()->create())
            ->post(route('trips.preparation.update', ['trip' => $trip, 'item' => $butir->id]), ['status' => PreparationStatus::CONFIRMED->value])
            ->assertForbidden();
        $this->assertSame(PreparationStatus::NOT_CONFIRMED, $butir->fresh()->status);

        $this->setel($trip, $butir, PreparationStatus::CONFIRMED, [
            'trip_plan_id' => $tripLain->id,
            'is_critical' => false,
            'label' => 'Diganti',
            'category' => PreparationCategory::WEATHER->value,
        ]);

        $segar = $butir->fresh();
        $this->assertSame(PreparationStatus::CONFIRMED, $segar->status);
        $this->assertSame($trip->id, $segar->trip_plan_id);
        $this->assertTrue($segar->is_critical);
        $this->assertSame('Jas hujan', $segar->label);
        $this->assertSame(PreparationCategory::EQUIPMENT, $segar->category);
    }

    /**
     * R-013: butir cuaca menunggu BMKG sampai prakiraan untuk tanggal trip terbit (H-3), dan
     * server menolak konfirmasinya sebelum itu, bukan hanya antarmukanya.
     */
    public function test_a_weather_item_waits_for_bmkg_and_cannot_be_confirmed_early(): void
    {
        $trip = $this->trip();
        $prakiraan = $this->butir($trip, PreparationCategory::WEATHER, 'Memeriksa prakiraan cuaca terbaru');

        $this->setel($trip, $prakiraan, PreparationStatus::CONFIRMED)
            ->assertSessionHasErrors(['status' => 'Prakiraan BMKG untuk tanggal trip ini belum terbit. Butir cuaca dapat dikonfirmasi mulai Rab, 7 Okt.']);
        $this->assertSame(PreparationStatus::NOT_CONFIRMED, $prakiraan->fresh()->status);

        $props = $this->actingAs($trip->user)->get(route('trips.preparation', $trip))->viewData('page')['props'];
        $cuaca = collect($props['kategori'])->firstWhere('kunci', PreparationCategory::WEATHER->value);
        $this->assertSame('Menunggu BMKG: terbit mulai Rab, 7 Okt', $cuaca['items'][0]['menunggu']);

        $this->setel($trip, $prakiraan, PreparationStatus::NOT_APPLICABLE)->assertSessionHasNoErrors();

        $this->travelTo(Carbon::parse('2026-10-07 01:00:00', 'UTC'));
        $this->setel($trip, $prakiraan, PreparationStatus::CONFIRMED)->assertSessionHasNoErrors();
        $this->assertSame(PreparationStatus::CONFIRMED, $prakiraan->fresh()->status);
    }

    /**
     * Keadaan persiapan prototype 09: hitungan butir kritis (bukan persen, R-005), butir kritis
     * yang belum dikonfirmasi disebut namanya, dan butir "Tidak berlaku" tidak dihitung.
     */
    public function test_the_page_counts_critical_items_and_names_the_unconfirmed_ones(): void
    {
        $trip = $this->trip();
        $this->butir($trip, PreparationCategory::EQUIPMENT, 'Headlamp dan baterai cadangan');
        $this->butir($trip, PreparationCategory::EQUIPMENT, 'Jas hujan', true, PreparationStatus::CONFIRMED);
        $this->butir($trip, PreparationCategory::LOGISTICS, 'Kontak darurat yang dapat dihubungi');
        $this->butir($trip, PreparationCategory::LOGISTICS, 'Rencana lokasi camping', true, PreparationStatus::NOT_APPLICABLE);
        $this->butir($trip, PreparationCategory::LOGISTICS, 'Transportasi menuju titik awal', false);

        $props = $this->actingAs($trip->user)->get(route('trips.preparation', $trip))->assertOk()->viewData('page')['props'];

        $this->assertSame(['dikonfirmasi' => 1, 'berlaku' => 3, 'belum' => ['Headlamp dan baterai cadangan', 'Kontak darurat yang dapat dihubungi']], $props['kritis']);
        $this->assertSame(['EQUIPMENT', 'LOGISTICS'], array_column($props['kategori'], 'kunci'));
        $this->assertSame([1, 0], array_column($props['kategori'], 'dikonfirmasi'));
        $this->assertSame([2, 2], array_column($props['kategori'], 'berlaku'));
        $this->assertDoesNotMatchRegularExpression('/\d+\s?%|score/i', json_encode($props));
        $this->assertSame($trip->id, $props['kepala']['id']);
    }

    /**
     * Butir izin menautkan sistem resmi beserta tanggal tutupnya untuk tanggal trip; Memount
     * tidak memesan izin (§43).
     */
    public function test_the_permit_item_links_the_official_booking_with_its_closing_date(): void
    {
        $this->seed(PreparationTemplateSeeder::class);
        $trip = $this->trip();
        PermitRequirement::factory()->create([
            'trail_id' => $trip->trail_id,
            'authority' => 'Balai Besar TNGGP',
            'booking_url' => 'https://booking.example.test',
            'booking_opens_days_before' => 30,
            'booking_closes_days_before' => 3,
        ]);
        app(PreparationService::class)->generateFor($trip);

        $props = $this->actingAs($trip->user)->get(route('trips.preparation', $trip))->viewData('page')['props'];
        $izin = collect($props['kategori'])->flatMap(fn (array $k) => $k['items'])->firstWhere('label', PreparationService::PERMIT_LABEL);

        $this->assertSame(['href' => 'https://booking.example.test', 'teks' => 'Pesan di sistem resmi, tutup Rab, 7 Okt'], $izin['tautan']);
        $this->assertStringNotContainsString('https://', (string) $izin['keterangan'], 'Alamat pemesanan tidak diulang sebagai teks mentah di keterangan.');
    }

    public function test_the_page_does_not_grow_with_the_number_of_items(): void
    {
        $trip = $this->trip();
        $this->butir($trip, PreparationCategory::EQUIPMENT, 'Butir awal');
        $url = route('trips.preparation', $trip);
        $this->actingAs($trip->user);

        $hitung = function () use ($url): int {
            $jumlah = 0;
            DB::listen(function () use (&$jumlah) {
                $jumlah++;
            });
            $this->get($url)->assertOk();
            DB::getEventDispatcher()->forget('Illuminate\Database\Events\QueryExecuted');

            return $jumlah;
        };

        $this->get($url);
        $sedikit = $hitung();

        foreach (PreparationCategory::cases() as $i => $kategori) {
            for ($j = 0; $j < 4; $j++) {
                $this->butir($trip, $kategori, "Butir {$i}-{$j}", $j % 2 === 0, $j === 3 ? PreparationStatus::CONFIRMED : PreparationStatus::NOT_CONFIRMED);
            }
        }

        $this->assertSame($sedikit, $hitung(), 'Halaman persiapan tumbuh bersama jumlah butir.');
    }

    /**
     * Centang optimis dengan Urungkan milik halaman (konvensi W2-5i): toast dari useKerangka di
     * halaman, kunjungan preserveState dan preserveScroll, baris kotak centang 44 px, dan
     * kalimat BR-10 di keadaan persiapan.
     */
    public function test_the_page_ticks_optimistically_and_offers_undo(): void
    {
        $halaman = File::get(resource_path('js/pages/Trips/Preparation.tsx'));

        foreach (['useKerangka()', 'tampilkanToast(', 'preserveState: true', 'preserveScroll: true', '<KotakCentang', '<LangkahRingkas kini="persiapan"', 'Butir yang belum dicentang bukan berarti tidak Anda miliki.', 'Lanjut ke cek kesiapan'] as $penanda) {
            $this->assertStringContainsString($penanda, $halaman);
        }
    }
}
```

`tests/Unit/PreparationServiceTest.php`:

1. Ganti:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
```

dengan:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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
     * Keadaan persiapan prototype 09 dan cek kesiapan prototype 10 (W4 Tugas 4): hitungan butir
     * kritis yang berlaku dan nama butir kritis yang belum dikonfirmasi. "Tidak berlaku" tidak
     * dihitung, sama seperti kemajuan persiapan.
     */
    public function test_the_critical_count_names_what_is_still_unconfirmed(): void
    {
        $trip = TripPlan::factory()->create();
        $service = app(PreparationService::class);
        $items = $service->generateFor($trip);
        $kritis = $items->where('is_critical', true)->values();

        $service->updateStatus($kritis[0], PreparationStatus::CONFIRMED);
        $service->updateStatus($kritis[1], PreparationStatus::NOT_APPLICABLE);

        $hitungan = PreparationService::kritis($trip->fresh()->preparationItems);

        $this->assertSame(1, $hitungan['dikonfirmasi']);
        $this->assertSame($kritis->count() - 1, $hitungan['berlaku']);
        $this->assertSame($kritis->slice(2)->pluck('label')->values()->all(), $hitungan['belum']);
    }

    /**
     * Prakiraan BMKG untuk tanggal trip terbit tiga hari sebelumnya (config
     * hiking.conditions.weather_forecast_days); sebelum itu tanggal terbitnya dikembalikan,
     * sesudahnya null.
     */
    public function test_the_forecast_publication_date_is_known_until_it_arrives(): void
    {
        $this->travelTo(Carbon::parse('2026-09-25 03:00:00', 'UTC'));

        $jauh = TripPlan::factory()->create(['planned_date' => '2026-10-10']);
        $dekat = TripPlan::factory()->create(['planned_date' => '2026-09-27']);

        $this->assertSame('2026-10-07', PreparationService::terbitPrakiraan($jauh)?->toDateString());
        $this->assertNull(PreparationService::terbitPrakiraan($dekat));
    }
}
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='PreparationUndo|PreparationServiceTest|TripReactPages|TripOwnership|LockedBusinessRules|EmptyStateCoverage|ShellFeedback|TapTarget'`
Expected: FAIL. Pada simulasi rencana: 55 test, 49 lulus, 2 gagal, 4 error. Yang merah: empat test `PreparationUndoTest` (props `kritis` dan `tautan` belum ada, butir cuaca masih dapat dikonfirmasi sebelum prakiraan terbit, halaman lama tanpa Urungkan) dan dua test `PreparationServiceTest` (`kritis()` dan `terbitPrakiraan()` belum ada).

- [ ] **Step 3: Tulis implementasinya.**

Buat `app/Services/Trips/HalamanPersiapan.php`:

```php
<?php

namespace App\Services\Trips;

use App\Enums\PreparationCategory;
use App\Enums\PreparationStatus;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Services\PermitService;
use App\Services\PreparationService;
use Illuminate\Support\Carbon;

/**
 * Kelas halaman Persiapan (spec v3 bagian 6.10, prototype 09, rencana induk W4 butir 3):
 * kepala trip, keadaan persiapan sebagai hitungan butir kritis dengan nama yang belum
 * dikonfirmasi, kelompok PreparationCategory dengan hitungannya, butir cuaca yang menunggu
 * BMKG, tautan izin ke sistem resmi, dan pengingat trip.
 *
 * Kueri tetap: butir, jalur, gunung, dan pengingat dimuat sekali oleh pemanggil; aturan izin
 * dicari sekali untuk butir izin. Tanpa persen (R-005) dan tanpa kata "tidak dimiliki" (BR-10).
 */
final class HalamanPersiapan
{
    public function __construct(
        private readonly KepalaTrip $kepala,
        private readonly PermitService $permits,
        private readonly TripReminderScheduler $pengingat,
    ) {}

    /**
     * Membutuhkan relasi trail.mountain, preparationItems, dan reminders sudah dimuat.
     *
     * @return array<string, mixed>
     */
    public function props(TripPlan $trip): array
    {
        $items = $trip->preparationItems->sortBy([['is_critical', 'desc'], ['id', 'asc']])->values();
        $hitungan = TripSteps::hitungPersiapan($items);
        $terbit = PreparationService::terbitPrakiraan($trip);
        $izin = $this->tautanIzin($trip);
        $perKategori = $items->groupBy(fn (TripPreparationItem $item) => $item->category->value);
        $jalurTerbit = $trip->trail->is_published && $trip->trail->archived_at === null;

        return [
            'kepala' => $this->kepala->untuk($trip),
            'tautanLangkah' => array_filter([
                'jalur' => $jalurTerbit ? route('trails.show', $trip->trail) : null,
                'rencana' => route('trips.show', $trip),
                'kesiapan' => route('trips.readiness', $trip),
            ]),
            'kritis' => PreparationService::kritis($items),
            'kemajuan' => [
                'dikonfirmasi' => $hitungan['dikonfirmasi'],
                'berlaku' => $hitungan['berlaku'],
                'teks' => TripSteps::teksPersiapan($hitungan),
            ],
            // Urutan kategori tetap, ditetapkan di kode; kategori kosong tidak tampil.
            'kategori' => collect(PreparationCategory::cases())
                ->filter(fn (PreparationCategory $k) => $perKategori->has($k->value))
                ->map(function (PreparationCategory $k) use ($perKategori, $terbit, $izin) {
                    $butir = $perKategori[$k->value];
                    $hitunganKategori = TripSteps::hitungPersiapan($butir);

                    return [
                        'kunci' => $k->value,
                        'nama' => $k->label(),
                        'dikonfirmasi' => $hitunganKategori['dikonfirmasi'],
                        'berlaku' => $hitunganKategori['berlaku'],
                        'items' => $butir->map(fn (TripPreparationItem $item) => $this->butir($item, $terbit, $izin))->values()->all(),
                    ];
                })
                ->values()
                ->all(),
            'pengingat' => $this->pengingat->untukHalaman($trip),
        ];
    }

    /**
     * @param  array{href: string, teks: string, keterangan: string}|null  $izin
     * @return array{id: int, label: string, keterangan: string|null, kritis: bool, status: string, menunggu: string|null, tautan: array{href: string, teks: string}|null}
     */
    private function butir(TripPreparationItem $item, ?Carbon $terbit, ?array $izin): array
    {
        $butirIzin = $item->preparation_item_id === null && $item->label === PreparationService::PERMIT_LABEL;

        return [
            'id' => $item->id,
            'label' => $item->label,
            // Butir izin menyebut aturannya tanpa alamat pemesanan mentah; alamatnya menjadi
            // tautan "Pesan di sistem resmi".
            'keterangan' => $butirIzin && $izin !== null ? $izin['keterangan'] : $item->description,
            'kritis' => (bool) $item->is_critical,
            'status' => $item->status->value,
            'menunggu' => $item->category === PreparationCategory::WEATHER && $item->status === PreparationStatus::NOT_CONFIRMED && $terbit !== null
                ? 'Menunggu BMKG: terbit mulai '.$terbit->translatedFormat('D, j M')
                : null,
            'tautan' => $butirIzin && $izin !== null ? ['href' => $izin['href'], 'teks' => $izin['teks']] : null,
        ];
    }

    /**
     * Tautan sistem pemesanan resmi untuk butir izin, dengan tanggal tutupnya untuk tanggal
     * trip bila aturannya mencatat jendela. Memount menautkan, tidak memesan (§43).
     *
     * @return array{href: string, teks: string, keterangan: string}|null
     */
    private function tautanIzin(TripPlan $trip): ?array
    {
        $punyaButirIzin = $trip->preparationItems->contains(fn (TripPreparationItem $item) => $item->preparation_item_id === null && $item->label === PreparationService::PERMIT_LABEL);

        if (! $punyaButirIzin) {
            return null;
        }

        $aturan = $this->permits->requirementFor($trip->trail);

        if ($aturan === null || blank($aturan->booking_url)) {
            return null;
        }

        $tutup = $aturan->booking_closes_days_before !== null
            ? ', tutup '.$trip->planned_date->copy()->subDays($aturan->booking_closes_days_before)->translatedFormat('D, j M')
            : '';

        return ['href' => $aturan->booking_url, 'teks' => 'Pesan di sistem resmi'.$tutup, 'keterangan' => $aturan->summary()];
    }
}
```

Tulis ulang `app/Http/Controllers/Trips/PreparationController.php` utuh:

```php
<?php

namespace App\Http\Controllers\Trips;

use App\Enums\PreparationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Trips\UpdatePreparationItemRequest;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Services\PreparationService;
use App\Services\Trips\HalamanPersiapan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * FR-09 daftar persiapan tiga keadaan. "Belum dikonfirmasi" tidak pernah berarti
 * "tidak dimiliki" (PRD §37, BR-10). Pengganti komponen PreparationChecklist.
 */
class PreparationController extends Controller
{
    public function show(TripPlan $trip, PreparationService $preparation, HalamanPersiapan $halaman): Response
    {
        $this->authorize('view', $trip);

        $trip->load('trail.mountain', 'preparationItems', 'reminders');

        if ($trip->preparationItems->isEmpty()) {
            $preparation->generateFor($trip);
            $trip->load('preparationItems');
        }

        return Inertia::render('Trips/Preparation', [
            'title' => 'Persiapan Pendakian',
            ...$halaman->props($trip),
        ]);
    }

    /**
     * Satu status butir, juga untuk Urungkan: halaman mengirim status sebelumnya lewat rute
     * yang sama. Hanya status yang diambil dari badan permintaan; trip, kritis, label, dan
     * kategori tidak pernah berubah di sini.
     */
    public function update(UpdatePreparationItemRequest $request, TripPlan $trip, int $item, PreparationService $preparation): RedirectResponse
    {
        // Dicari di dalam trip ini saja: butir milik trip lain menjawab 404, bukan
        // berubah karena id-nya ditebak (IDOR).
        $butir = TripPreparationItem::where('trip_plan_id', $trip->id)->findOrFail($item);
        $status = PreparationStatus::from($request->validated('status'));

        if ($penolakan = PreparationService::penolakanKonfirmasi($trip, $butir, $status)) {
            throw ValidationException::withMessages(['status' => $penolakan]);
        }

        $preparation->updateStatus($butir, $status);

        return redirect()->route('trips.preparation', $trip);
    }

    public function regenerate(TripPlan $trip, PreparationService $preparation): RedirectResponse
    {
        $this->authorize('update', $trip);

        $preparation->generateFor($trip);

        return redirect()->route('trips.preparation', $trip)
            ->with('status', 'Daftar persiapan diperbarui mengikuti karakteristik jalur.');
    }
}
```

`app/Services/PreparationService.php`:

1. Ganti:

```php
use App\Models\TripPreparationItem;
use Illuminate\Support\Collection;
```

dengan:

```php
use App\Models\TripPreparationItem;
use App\Support\Timezone;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
```

2. Ganti:

```php
    }

    public function updateStatus(TripPreparationItem $item, PreparationStatus $status): TripPreparationItem
```

dengan:

```php
    }

    /**
     * Hitungan butir kritis untuk keadaan persiapan (prototype 09) dan cek kesiapan
     * (prototype 10): berapa yang dikonfirmasi dari yang berlaku, dan nama butir kritis yang
     * belum dikonfirmasi. "Tidak berlaku" tidak dihitung. Hitungan, bukan persen (R-005).
     *
     * @param  Collection<int, TripPreparationItem>  $items
     * @return array{dikonfirmasi: int, berlaku: int, belum: array<int, string>}
     */
    public static function kritis(Collection $items): array
    {
        $kritis = $items->where('is_critical', true)->where('status', '!=', PreparationStatus::NOT_APPLICABLE);

        return [
            'dikonfirmasi' => $kritis->where('status', PreparationStatus::CONFIRMED)->count(),
            'berlaku' => $kritis->count(),
            'belum' => $kritis->where('status', PreparationStatus::NOT_CONFIRMED)->pluck('label')->values()->all(),
        ];
    }

    /**
     * Tanggal terbit prakiraan BMKG untuk tanggal trip (config
     * hiking.conditions.weather_forecast_days hari sebelumnya), selama tanggal itu belum tiba
     * menurut zona gunungnya; null sesudahnya. Butir cuaca menunggu sampai tanggal ini (R-013).
     */
    public static function terbitPrakiraan(TripPlan $trip): ?Carbon
    {
        $terbit = Carbon::parse($trip->planned_date->toDateString())->subDays((int) config('hiking.conditions.weather_forecast_days'));

        return $terbit->toDateString() > Carbon::now(Timezone::forTrail($trip->trail))->toDateString() ? $terbit : null;
    }

    /**
     * Butir cuaca tidak dapat dikonfirmasi sebelum prakiraan untuk tanggal trip terbit:
     * "sudah diperiksa" atas prakiraan yang belum ada adalah pernyataan kosong. Penolakannya
     * di server, bukan hanya di antarmuka.
     *
     * @return string|null kalimat penolakan, atau null bila status itu boleh ditulis
     */
    public static function penolakanKonfirmasi(TripPlan $trip, TripPreparationItem $item, PreparationStatus $status): ?string
    {
        if ($status !== PreparationStatus::CONFIRMED || $item->category !== PreparationCategory::WEATHER) {
            return null;
        }

        $terbit = self::terbitPrakiraan($trip);

        return $terbit === null
            ? null
            : 'Prakiraan BMKG untuk tanggal trip ini belum terbit. Butir cuaca dapat dikonfirmasi mulai '.$terbit->translatedFormat('D, j M').'.';
    }

    public function updateStatus(TripPreparationItem $item, PreparationStatus $status): TripPreparationItem
```

Tulis ulang `resources/js/pages/Trips/Preparation.tsx` utuh:

```tsx
import { router, usePage } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import { KepalaTrip } from '@/components/trips/KepalaTrip';
import { LangkahRingkas, type KunciLangkah } from '@/components/trips/LangkahRingkas';
import { PengingatTrip, type PengingatHalaman } from '@/components/trips/PengingatTrip';
import type { KepalaTripData } from '@/components/trips/types';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { KotakCentang } from '@/components/ui/KotakCentang';
import { Lencana } from '@/components/ui/Lencana';
import { PageHeader } from '@/components/ui/PageHeader';
import { kelas } from '@/lib/kelas';
import { AppLayout } from '@/layouts/AppLayout';
import { useKerangka } from '@/layouts/Kerangka';
import { readiness, show } from '@/routes/trips';
import { regenerate, update } from '@/routes/trips/preparation';
import type { PropsBersama } from '@/types';

type StatusButir = 'CONFIRMED' | 'NOT_CONFIRMED' | 'NOT_APPLICABLE';

interface Butir {
    id: number;
    label: string;
    keterangan: string | null;
    kritis: boolean;
    status: StatusButir;
    menunggu: string | null;
    tautan: { href: string; teks: string } | null;
}

interface Props extends PropsBersama {
    kepala: KepalaTripData;
    tautanLangkah: Partial<Record<KunciLangkah, string>>;
    kritis: { dikonfirmasi: number; berlaku: number; belum: string[] };
    kemajuan: { dikonfirmasi: number; berlaku: number; teks: string };
    kategori: Array<{ kunci: string; nama: string; dikonfirmasi: number; berlaku: number; items: Butir[] }>;
    pengingat: PengingatHalaman;
}

const kataStatus: Record<StatusButir, string> = {
    CONFIRMED: 'Dikonfirmasi',
    NOT_CONFIRMED: 'Belum dikonfirmasi',
    NOT_APPLICABLE: 'Tidak berlaku',
};

const tautanTeks =
    'mt-1 inline-flex min-h-11 items-center text-kecil font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600';

/**
 * Persiapan (spec v3 bagian 6.10, prototype 09). Keadaan persiapan sebagai hitungan butir
 * kritis dengan ruas per butir, lalu kelompok kategori. Centang optimis: pilihan langsung
 * terlihat, server menulis di latar, dan toast Urungkan milik halaman (W2-5i) mengirim status
 * sebelumnya. Bila server menolak atau jaringan putus, status kembali ke yang tersimpan.
 * "Belum dikonfirmasi" adalah pernyataan tentang catatan, bukan tuduhan (BR-10). Butir cuaca
 * menunggu BMKG sampai prakiraan tanggal trip terbit, dan tidak dapat dicentang sebelum itu.
 */
export default function Preparation() {
    const { kepala, tautanLangkah, kritis, kemajuan, kategori, pengingat, flash } = usePage<Props>().props;
    const { tampilkanToast } = useKerangka();
    const [sementara, setSementara] = useState<Record<number, StatusButir>>({});
    const [hanyaBelum, setHanyaBelum] = useState(false);
    const [memperbarui, setMemperbarui] = useState(false);

    const statusKini = (item: Butir): StatusButir => sementara[item.id] ?? item.status;

    // async supaya centang beruntun tidak saling membatalkan; preserveState dan preserveScroll
    // supaya halaman, toast, dan posisi gulir bertahan (konvensi W2).
    const setel = (item: Butir, status: StatusButir, papanKetik: boolean, denganToast = true) => {
        const sebelum = statusKini(item);
        setSementara((s) => ({ ...s, [item.id]: status }));
        router.post(
            update.url({ trip: kepala.id, item: item.id }),
            { status },
            {
                async: true,
                preserveScroll: true,
                preserveState: true,
                onFinish: () =>
                    setSementara((s) => {
                        const salinan = { ...s };
                        delete salinan[item.id];
                        return salinan;
                    }),
            },
        );

        if (denganToast) {
            tampilkanToast(`${item.label}: ${kataStatus[status].toLowerCase()}.`, () => setel(item, sebelum, false, false), { fokus: papanKetik });
        }
    };

    const perbarui = () => {
        setMemperbarui(true);
        router.post(regenerate.url(kepala.id), {}, { preserveScroll: true, onFinish: () => setMemperbarui(false) });
    };

    const belum = kemajuan.berlaku - kemajuan.dikonfirmasi;
    const kritisLengkap = kritis.berlaku > 0 && kritis.belum.length === 0;

    return (
        <div className="py-6">
            <div className="mx-auto max-w-2xl space-y-8 px-4 sm:px-6">
                <div>
                    <PageHeader induk={{ href: show.url(kepala.id), label: 'Ruang kerja trip' }} title={kepala.nama}>
                        <KepalaTrip kepala={kepala} />
                    </PageHeader>
                    <LangkahRingkas kini="persiapan" tautan={tautanLangkah} />
                </div>

                {flash.status ? <Alert variant="success">{flash.status}</Alert> : null}

                <section aria-labelledby="keadaan-persiapan" className={kelas('rounded-utama border p-5', kritisLengkap ? 'border-brand-200 bg-brand-50' : 'border-warn-300 bg-warn-50')}>
                    <h2 id="keadaan-persiapan" className="text-bagian text-primary">
                        Keadaan persiapan
                    </h2>
                    {kritis.berlaku > 0 ? (
                        <>
                            <p data-angka className="mt-2 text-isi font-bold text-primary">
                                {kritis.dikonfirmasi} dari {kritis.berlaku} butir kritis dikonfirmasi
                            </p>
                            <div aria-hidden="true" className="mt-3 flex gap-1">
                                {Array.from({ length: kritis.berlaku }, (_, i) => (
                                    <span key={i} className={kelas('h-2 flex-1 rounded-full', i < kritis.dikonfirmasi ? 'bg-brand-700' : 'bg-surface-sunken')} />
                                ))}
                            </div>
                        </>
                    ) : (
                        <p className="mt-2 text-isi text-primary">{kemajuan.teks}</p>
                    )}
                    {kritis.belum.length > 0 ? <p className="mt-3 text-kecil text-primary">Belum dikonfirmasi: {kritis.belum.join(', ')}.</p> : null}
                    <p className="mt-2 text-kecil text-secondary">Butir yang belum dicentang bukan berarti tidak Anda miliki.</p>
                    {belum > 0 ? (
                        <Button variant="ghost" size="sm" className="-ml-4 mt-2" aria-pressed={hanyaBelum} onClick={() => setHanyaBelum((v) => !v)}>
                            {hanyaBelum ? 'Tampilkan semua butir' : 'Tampilkan yang belum dikonfirmasi saja'}
                        </Button>
                    ) : null}
                </section>

                {kategori.map((k) => {
                    const items = k.items.filter((item) => !hanyaBelum || statusKini(item) === 'NOT_CONFIRMED');

                    if (items.length === 0) {
                        return null;
                    }

                    return (
                        <section key={k.kunci} aria-labelledby={`kategori-${k.kunci}`}>
                            <div className="flex items-baseline justify-between gap-3">
                                <h2 id={`kategori-${k.kunci}`} className="text-bagian text-primary">
                                    {k.nama}
                                </h2>
                                <span data-angka className="text-meta text-secondary">
                                    {k.dikonfirmasi} dari {k.berlaku}
                                </span>
                            </div>
                            <ul className="mt-3 divide-y divide-subtle rounded-lg border border-subtle bg-surface">
                                {items.map((item) => {
                                    const aktif = statusKini(item);
                                    const lencana = item.kritis ? <Lencana nada="gelap">Kritis</Lencana> : undefined;
                                    const keterangan = [kataStatus[aktif], item.keterangan].filter(Boolean).join('. ');

                                    return (
                                        <li key={item.id} className="px-4 py-2">
                                            <div className="flex items-start gap-2">
                                                <div className="min-w-0 flex-1">
                                                    {item.menunggu && aktif === 'NOT_CONFIRMED' ? (
                                                        <div className="flex min-h-11 items-start gap-3 py-2">
                                                            <span aria-hidden="true" className="mt-0.5 size-7 shrink-0 rounded-kecil border-2 border-dashed border-control" />
                                                            <span className="min-w-0">
                                                                <span className="flex flex-wrap items-center gap-2">
                                                                    <span className="break-words text-isi font-semibold text-primary">{item.label}</span>
                                                                    {lencana}
                                                                </span>
                                                                <span className="mt-0.5 block text-kecil text-secondary">{item.menunggu}</span>
                                                            </span>
                                                        </div>
                                                    ) : (
                                                        <KotakCentang
                                                            label={item.label}
                                                            keterangan={keterangan}
                                                            lencana={lencana}
                                                            checked={aktif === 'CONFIRMED'}
                                                            disabled={aktif === 'NOT_APPLICABLE'}
                                                            onChange={(e) => setel(item, e.currentTarget.checked ? 'CONFIRMED' : 'NOT_CONFIRMED', (e.nativeEvent as UIEvent).detail === 0)}
                                                            className={aktif === 'NOT_APPLICABLE' ? 'opacity-60' : undefined}
                                                        />
                                                    )}
                                                </div>
                                                <button
                                                    type="button"
                                                    onClick={(e) => setel(item, aktif === 'NOT_APPLICABLE' ? 'NOT_CONFIRMED' : 'NOT_APPLICABLE', e.detail === 0)}
                                                    className="inline-flex min-h-11 shrink-0 items-center px-1 text-meta font-semibold text-secondary underline underline-offset-2 hover:text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                                                >
                                                    {aktif === 'NOT_APPLICABLE' ? 'Berlaku lagi' : 'Tidak berlaku'}
                                                    <span className="sr-only">: {item.label}</span>
                                                </button>
                                            </div>
                                            {item.tautan ? (
                                                <a href={item.tautan.href} target="_blank" rel="noopener noreferrer" className={kelas(tautanTeks, 'ml-10')}>
                                                    {item.tautan.teks}
                                                    <span className="sr-only"> (membuka tab baru)</span>
                                                </a>
                                            ) : null}
                                        </li>
                                    );
                                })}
                            </ul>
                        </section>
                    );
                })}

                {pengingat.dapatDiatur || pengingat.daftar.length > 0 ? <PengingatTrip tripId={kepala.id} pengingat={pengingat} /> : null}

                <div className="flex flex-wrap items-center gap-3">
                    <Button href={readiness.url(kepala.id)}>Lanjut ke cek kesiapan</Button>
                    <Button variant="secondary" size="sm" onClick={perbarui} processing={memperbarui}>
                        Perbarui daftar
                    </Button>
                </div>
            </div>
        </div>
    );
}

Preparation.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
```

`tests/Pratinjau/HalamanPratinjauTest.php`:

1. Ganti:

```php
use App\Enums\HikingSessionStatus;
use App\Enums\TripStatus;
```

dengan:

```php
use App\Enums\HikingSessionStatus;
use App\Enums\PreparationStatus;
use App\Enums\TripStatus;
```

2. Ganti:

```php
use App\Services\Trails\TautanBagikan;
```

dengan:

```php
use App\Services\PreparationService;
use App\Services\Trails\TautanBagikan;
```

3. Ganti:

```php
        $this->tulisPratinjau('rencanakan', $this->get(self::ASAL.'/trips/create?trail='.$jalur->id.'&jenis=CAMPING&tanggal='.$naik->toDateString().'&selesai='.$naik->copy()->addDay()->toDateString()), 'Trips/Create');
    }
```

dengan:

```php
        $this->tulisPratinjau('rencanakan', $this->get(self::ASAL.'/trips/create?trail='.$jalur->id.'&jenis=CAMPING&tanggal='.$naik->toDateString().'&selesai='.$naik->copy()->addDay()->toDateString()), 'Trips/Create');
    }

    /**
     * Persiapan W4 (acuan 09): daftar dibangkitkan dari jalur Gede via Cibodas, tiga butir kritis
     * pertama dikonfirmasi supaya ruas, hitungan, dan butir yang belum terpotret bersama.
     */
    public function test_persiapan(): void
    {
        $budi = $this->sebagaiBudi();
        $trip = TripPlan::where('user_id', $budi->id)->where('status', TripStatus::PLANNED->value)->sole();
        $butir = app(PreparationService::class)->generateFor($trip);
        $butir->where('is_critical', true)->take(3)->each(fn ($satu) => $satu->update(['status' => PreparationStatus::CONFIRMED->value]));

        $this->tulisPratinjau('persiapan', $this->get(self::ASAL.'/trips/'.$trip->id.'/preparation'), 'Trips/Preparation');
    }
```

- [ ] **Step 4: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Services/PreparationService.php app/Services/Trips/HalamanPersiapan.php app/Http/Controllers/Trips/PreparationController.php tests/Feature/PreparationUndoTest.php tests/Unit/PreparationServiceTest.php tests/Pratinjau/HalamanPratinjauTest.php`, lalu `php artisan test --compact --filter='PreparationUndo|PreparationServiceTest|TripReactPages|TripOwnership|LockedBusinessRules|EmptyStateCoverage|ShellFeedback|TapTarget'`
Expected: PASS. Pada simulasi rencana: 55 test lulus.

- [ ] **Step 5: Suite, tipe, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 1.805 test, 1.803 lulus, 2 dilewati.

Run: `npx tsc --noEmit`
Expected: keluar tanpa keluaran galat.

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `php artisan test --compact --testsuite=Pratinjau`
Expected: build `✓ built`; Pratinjau lulus termasuk `test_persiapan`. Pada simulasi rencana: 17 test lulus.

- [ ] **Step 6: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 5: Kata keadaan kesiapan DK-48

Induk W4 butir 4 (label DK-48 pada `ReadinessState`); spec bagian 4 DK-48; ruling W4-R24. `ReadinessState::label()`
menjadi "Persiapan kritis lengkap", "Masih perlu persiapan", dan "Tidak disarankan dengan rencana ini"; kata itu
mengalir ke ruang kerja, indeks, Cek kesiapan, Beranda, dan pratinjau Mendaki tanpa perubahan lain. Governance record
GR-05 ditulis di Tugas 7.

**Files:**
- Modify: `app/Enums/ReadinessState.php`
- Test: `tests/Feature/ReadinessDashboardTest.php`

**Interfaces:**
- Consumes: tidak ada yang baru.
- Produces: `ReadinessState::label()` berkata DK-48 (dipakai Tugas 6 dan halaman di luar W4 lewat props yang sudah ada).

- [ ] **Step 1: Tulis test yang gagal.**

`tests/Feature/ReadinessDashboardTest.php`:

1. Ganti:

```php
use App\Enums\PreparationStatus;
use App\Enums\TripStatus;
```

dengan:

```php
use App\Enums\PreparationStatus;
use App\Enums\ReadinessState;
use App\Enums\TripStatus;
```

2. Ganti:

```php
                ->where('preDeparture.dikonfirmasi', false)
                ->where('check.label', fn (string $v) => in_array($v, ['Siap', 'Perlu persiapan', 'Tidak direkomendasikan'], true))
                ->where('bolehKonfirmasi', true)
```

dengan:

```php
                ->where('preDeparture.dikonfirmasi', false)
                ->where('check.label', fn (string $v) => in_array($v, ['Persiapan kritis lengkap', 'Masih perlu persiapan', 'Tidak disarankan dengan rencana ini'], true))
                ->where('bolehKonfirmasi', true)
```

3. Ganti:

```php
            ->where('check.persiapan', fn (string $v) => (bool) preg_match('/^\d+ dari \d+ item dikonfirmasi$/', $v)));
    }
```

dengan:

```php
            ->where('check.persiapan', fn (string $v) => (bool) preg_match('/^\d+ dari \d+ item dikonfirmasi$/', $v)));
    }

    /**
     * DK-48 (spec v3 bagian 4, prototype 09 dan 10, W4 Tugas 5): kata keadaan kesiapan
     * menyebut apa yang dinilai, bukan vonis atas pendakiannya. "Siap" terbaca sebagai izin
     * berangkat dan "Tidak direkomendasikan" sebagai penilaian atas orangnya (R-014, §39).
     * "Masih perlu persiapan" sengaja berbeda dari label kecocokan "Perlu persiapan" (R-004),
     * supaya kesiapan rencana tidak terbaca sebagai label Route Fit.
     */
    public function test_the_readiness_states_speak_in_the_dk48_words(): void
    {
        $this->assertSame(
            ['READY' => 'Persiapan kritis lengkap', 'NEEDS_PREPARATION' => 'Masih perlu persiapan', 'NOT_RECOMMENDED' => 'Tidak disarankan dengan rencana ini'],
            collect(ReadinessState::cases())->mapWithKeys(fn (ReadinessState $s) => [$s->value => $s->label()])->all(),
        );

        foreach (ReadinessState::cases() as $keadaan) {
            $this->assertDoesNotMatchRegularExpression('/\bsiap\b|\baman\b|\blayak\b|direkomendasikan/i', $keadaan->label());
        }
    }
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='ReadinessDashboard|StaleReadiness|TripWorkspace|TripIndexPage|DashboardRelevance|TrackTab'`
Expected: FAIL. Pada simulasi rencana: 47 test, 45 lulus, 2 gagal: `ReadinessDashboardTest::test_the_readiness_states_speak_in_the_dk48_words` dan `test_the_assessment_is_still_shown_without_writing` (label masih "Siap", "Perlu persiapan", "Tidak direkomendasikan").

- [ ] **Step 3: Tulis implementasinya.**

`app/Enums/ReadinessState.php`:

1. Ganti:

```php
namespace App\Enums;

enum ReadinessState: string
```

dengan:

```php
namespace App\Enums;

/**
 * Keadaan kesiapan sebuah rencana (PRD §39). Kata publiknya mengikuti DK-48 (prototype 09 dan
 * 10): menyebut apa yang dinilai, bukan vonis atas pendakian atau orangnya. Kesiapan adalah
 * dukungan keputusan, bukan izin medis, izin hukum, atau jaminan keselamatan (R-014).
 */
enum ReadinessState: string
```

2. Ganti:

```php
        return match ($this) {
            self::READY => 'Siap',
            self::NEEDS_PREPARATION => 'Perlu persiapan',
            self::NOT_RECOMMENDED => 'Tidak direkomendasikan',
        };
```

dengan:

```php
        return match ($this) {
            self::READY => 'Persiapan kritis lengkap',
            self::NEEDS_PREPARATION => 'Masih perlu persiapan',
            self::NOT_RECOMMENDED => 'Tidak disarankan dengan rencana ini',
        };
```

- [ ] **Step 4: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Enums/ReadinessState.php tests/Feature/ReadinessDashboardTest.php`, lalu `php artisan test --compact --filter='ReadinessDashboard|StaleReadiness|TripWorkspace|TripIndexPage|DashboardRelevance|TrackTab'`
Expected: PASS. Pada simulasi rencana: 47 test lulus.

- [ ] **Step 5: Suite, tipe, dan format.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 1.806 test, 1.804 lulus, 2 dilewati.

Run: `npx tsc --noEmit` dan `vendor/bin/pint --test --format agent`
Expected: tanpa galat; `{"tool":"pint","result":"passed"}`.

- [ ] **Step 6: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 6: Cek kesiapan

Induk W4 butir 4; spec bagian 6.11; ruling W4-R25 sampai W4-R30, W4-R36. `/trips/{trip}/readiness` lewat kelas
halaman `HalamanKesiapan`: keadaan DK-48 dengan kalimat dan alasannya, kalimat R-014, tiga hal yang diperiksa
(kecocokan untuk rencana dengan tautan penilaian, persiapan dengan butir kritis yang kurang dan aksinya, kondisi
terkini dengan status resmi bersumber, prakiraan untuk tanggal trip, dan laporan komunitas terbaru), "Sejak cek
terakhir" dari snapshot `readiness_checks`, pesan hari keberangkatan, lalu "Selesaikan N butir" atau "Saya sudah
meninjau semuanya". `TripReadinessResource` dihapus, dan `ReadinessService::compute()` menilai kecocokan untuk rencana.

**Files:**
- Create: `app/Services/Trips/HalamanKesiapan.php`
- Modify: `app/Http/Controllers/Trips/ReadinessController.php`, `app/Services/ReadinessService.php`, `resources/js/pages/Trips/Preparation.tsx`, `resources/js/pages/Trips/Readiness.tsx`
- Delete: `app/Http/Resources/TripReadinessResource.php`
- Test: `tests/Feature/ReadinessSinceLastCheckTest.php` (baru), `tests/Feature/ArchivedTrailWithActiveTripTest.php`, `tests/Feature/CoreJourneyTest.php`, `tests/Feature/EmptyStateCoverageTest.php`, `tests/Feature/ReadinessDashboardTest.php`, `tests/Feature/TripReactPagesTest.php`, `tests/Pratinjau/HalamanPratinjauTest.php`

**Interfaces:**
- Consumes: `ReadinessService::compute()`, `latestCheck()`, `record()`; `PreparationService::kritis()` dan `terbitPrakiraan()` (Tugas 4); `StatusJalurRingkas::untuk()`; `ConditionAggregatorService::reportFreshness()`; `TrailFitService::forTrails()` dan `TripPlan::tujuanPenilaian()`; `KepalaTrip`, `LangkahRingkas`, `BarisStatus`, `LabelKecocokan`, `LabelKomunitas`, `Lencana`.
- Produces:
  - `App\Services\Trips\HalamanKesiapan::props(TripPlan $trip, User $pembaca, ReadinessAssessment $penilaian, ?ReadinessCheck $terakhir): array` dengan kunci `kepala`, `tautanLangkah`, `keadaan` (`nilai`, `label`, `kalimat`, `alasan`, `dihitung`), `kecocokan`, `persiapan` (`dikonfirmasi`, `berlaku`, `teks`, `tautan`, `butir`), `kondisi` (`status`, `prakiraan`, `laporan`, `peringatan`), `sejakTerakhir`, `hariKeberangkatan`; controller menambah `title`, `bolehKonfirmasi`, `preDeparture`, `penolakan`;
  - flash `status`: "Hasil cek disimpan. Cek berikutnya dibandingkan dengan hasil ini." dan "Tinjauan Anda tercatat.";
  - jangkar `#butir-{id}` pada setiap butir di `Trips/Preparation.tsx`.

- [ ] **Step 1: Tulis test yang gagal.**

Buat `tests/Feature/ReadinessSinceLastCheckTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\ConditionTag;
use App\Enums\ExperienceLevel;
use App\Enums\OfficialStatusValue;
use App\Enums\PreparationCategory;
use App\Enums\PreparationStatus;
use App\Enums\StatusScope;
use App\Enums\TripType;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * "Sejak cek terakhir" (spec v3 bagian 6.11, prototype 10, W4 Tugas 6): halaman kesiapan
 * membandingkan penilaian sekarang dengan snapshot readiness_checks terakhir yang disimpan
 * pendaki, lalu menyebut apa yang berubah: status resmi, laporan komunitas baru, butir
 * persiapan yang dikonfirmasi, dan label kecocokan.
 *
 * Waktu dibekukan pada 25 September 2026 pukul 10.00 WIB.
 */
class ReadinessSinceLastCheckTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-09-25 03:00:00', 'UTC'));
    }

    private function trip(): TripPlan
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::ADVANCED->value, 'completed_at' => now()]);
        $trail = Trail::factory()->published()->easy()->create();

        $trip = TripPlan::factory()->create([
            'user_id' => $user->id,
            'trail_id' => $trail->id,
            'planned_date' => '2026-10-10',
            'trip_type' => TripType::CAMPING->value,
        ]);

        foreach (['Headlamp dan baterai cadangan', 'Jas hujan', 'Kontak darurat'] as $label) {
            TripPreparationItem::create([
                'trip_plan_id' => $trip->id,
                'category' => PreparationCategory::EQUIPMENT->value,
                'label' => $label,
                'is_critical' => true,
                'status' => PreparationStatus::NOT_CONFIRMED->value,
            ]);
        }

        return $trip->fresh();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function sejak(TripPlan $trip): ?array
    {
        return $this->actingAs($trip->user)->get(route('trips.readiness', $trip))->assertOk()->viewData('page')['props']['sejakTerakhir'];
    }

    public function test_there_is_nothing_to_compare_before_the_first_saved_check(): void
    {
        $this->assertNull($this->sejak($this->trip()));
    }

    /**
     * Prototype 10: "= Status resmi tidak berubah. + 1 laporan komunitas baru. + 2 butir
     * persiapan dikonfirmasi. = Label kecocokan tetap ...". Laporan yang dihitung hanya yang
     * lolos moderasi dan masuk sesudah cek terakhir.
     */
    public function test_the_comparison_names_what_changed_since_the_saved_check(): void
    {
        $trip = $this->trip();
        TrailConditionReport::factory()->approved()->create(['trail_id' => $trip->trail_id, 'created_at' => now()->subDay()]);

        $this->actingAs($trip->user)->post(route('trips.readiness.recompute', $trip))->assertRedirect(route('trips.readiness', $trip));

        $this->travel(2)->hours();
        $trip->preparationItems()->limit(2)->get()->each->update(['status' => PreparationStatus::CONFIRMED->value]);
        TrailConditionReport::factory()->approved()->create(['trail_id' => $trip->trail_id, 'hike_date' => '2026-09-24', 'condition_tags' => [ConditionTag::SLIPPERY->value]]);
        TrailConditionReport::factory()->create(['trail_id' => $trip->trail_id, 'hike_date' => '2026-09-24']);

        $sejak = $this->sejak($trip);

        $this->assertSame('25 Sep', $sejak['tanggal']);
        $this->assertSame(['sama', 'bertambah', 'bertambah', 'sama'], array_column($sejak['butir'], 'jenis'));
        $this->assertSame('Status resmi tidak berubah.', $sejak['butir'][0]['teks']);
        $this->assertSame('1 laporan komunitas baru.', $sejak['butir'][1]['teks']);
        $this->assertSame('2 butir persiapan dikonfirmasi.', $sejak['butir'][2]['teks']);
        $this->assertStringStartsWith('Label kecocokan tetap ', $sejak['butir'][3]['teks']);
    }

    /**
     * Penutupan sesudah cek terakhir disebut sebagai perubahan, dengan kata status lama dan
     * barunya; tidak ada yang berubah disebut tidak berubah, bukan didiamkan.
     */
    public function test_a_status_change_since_the_saved_check_is_named(): void
    {
        $trip = $this->trip();
        $trip->trail->officialStatuses()->update(['status' => OfficialStatusValue::OPEN->value]);

        $this->actingAs($trip->user)->post(route('trips.readiness.recompute', $trip));

        $this->travel(1)->days();
        OfficialStatus::create([
            'statusable_type' => $trip->trail->getMorphClass(),
            'statusable_id' => $trip->trail_id,
            'scope' => StatusScope::TRAIL->value,
            'status' => OfficialStatusValue::CLOSED->value,
            'source' => 'Balai Taman Nasional',
            'effective_at' => now(),
        ]);

        $sejak = $this->sejak($trip);

        $this->assertSame(['jenis' => 'berubah', 'teks' => 'Status resmi berubah dari Buka menjadi Tutup.'], $sejak['butir'][0]);
        $this->assertSame(['jenis' => 'sama', 'teks' => 'Persiapan tidak berubah.'], $sejak['butir'][2]);
    }
}
```

`tests/Feature/ArchivedTrailWithActiveTripTest.php`:

1. Ganti:

```php
    /**
     * @return array<int, string>
```

dengan:

```php
    /**
     * Alasan keadaan kesiapan yang tampil di halaman. Sejak W4 Tugas 6 (prototype 10) alasan
     * mesin dikirim sebagai `keadaan.alasan`, bukan kelompok `check.penjelasan`; yang diuji
     * tetap kalimat yang dibaca pendaki.
     *
     * @return array<int, string>
```

2. Ganti:

```php
        $props = $this->actingAs($trip->user)->get(route('trips.readiness', $trip))->assertOk()->viewData('page')['props'];

        return collect($props['check']['penjelasan'])->pluck('baris')->flatten()->all();
    }
```

dengan:

```php
        $props = $this->actingAs($trip->user)->get(route('trips.readiness', $trip))->assertOk()->viewData('page')['props'];

        return $props['keadaan']['alasan'];
    }
```

`tests/Feature/CoreJourneyTest.php`:

1. Ganti:

```php
        $this->post(route('trips.readiness.confirm', $trip))
            ->assertSessionHas('status', 'Pre-departure check tercatat.');

        $this->assertSame(TripStatus::READY_FOR_DEPARTURE, $trip->fresh()->status);
```

dengan:

```php
        $this->post(route('trips.readiness.confirm', $trip))
            ->assertSessionHas('status', 'Tinjauan Anda tercatat.');

        $this->assertSame(TripStatus::READY_FOR_DEPARTURE, $trip->fresh()->status);
```

`tests/Feature/EmptyStateCoverageTest.php`:

1. Ganti:

```php
        'pages/Trips/Readiness.tsx' => [
            'check.penjelasan' => 'kelompok tetap TripReadinessResource::penjelasan(); versi Blade melewatinya sebagai larik harfiah',
        ],
```

dengan:

```php
        'pages/Trips/Readiness.tsx' => [
            'sejakTerakhir.butir' => 'empat baris tetap HalamanKesiapan::sejak(); bagiannya hanya tampil bila ada cek tersimpan',
        ],
```

`tests/Feature/ReadinessDashboardTest.php`:

1. Ganti:

```php
use App\Models\User;
use App\Services\PreparationService;
```

dengan:

```php
use App\Models\User;
use App\Models\WeatherSnapshot;
use App\Services\PreparationService;
```

2. Ganti:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
```

dengan:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
```

3. Ganti:

```php
        $trip = $this->trip();

        $this->actingAs($trip->user)
            ->post(route('trips.readiness.recompute', $trip))
```

dengan:

```php
        $trip = $this->trip();

        // W4 Tugas 6: hasil cek yang disimpan menjadi pembanding "Sejak cek terakhir", jadi
        // kalimatnya menyebut itu; niatnya tetap satu baris per permintaan.
        $this->actingAs($trip->user)
            ->post(route('trips.readiness.recompute', $trip))
```

4. Ganti:

```php
            ->assertRedirect(route('trips.readiness', $trip))
            ->assertSessionHas('status', 'Kesiapan dihitung ulang.');

        $this->assertSame(1, ReadinessCheck::count());
```

dengan:

```php
            ->assertRedirect(route('trips.readiness', $trip))
            ->assertSessionHas('status', 'Hasil cek disimpan. Cek berikutnya dibandingkan dengan hasil ini.');

        $this->assertSame(1, ReadinessCheck::count());
```

5. Ganti:

```php
        $trip = $this->trip();

        $this->actingAs($trip->user)
            ->post(route('trips.readiness.confirm', $trip))
            ->assertSessionHas('status', 'Pre-departure check tercatat.');

        // Memuat ulang halaman: konfirmasinya harus tetap terbaca.
```

dengan:

```php
        $trip = $this->trip();

        // Aksinya kini "Saya sudah meninjau semuanya" (prototype 10), jadi kalimat konfirmasinya
        // berbahasa yang sama; yang dicatat tetap pre_departure_confirmed.
        $this->actingAs($trip->user)
            ->post(route('trips.readiness.confirm', $trip))
            ->assertSessionHas('status', 'Tinjauan Anda tercatat.');

        // Memuat ulang halaman: konfirmasinya harus tetap terbaca.
```

6. Ganti:

```php
                ->where('preDeparture.dikonfirmasi', false)
                ->where('check.label', fn (string $v) => in_array($v, ['Persiapan kritis lengkap', 'Masih perlu persiapan', 'Tidak disarankan dengan rencana ini'], true))
                ->where('bolehKonfirmasi', true)
                ->has('check.penjelasan'));

        $this->assertSame(0, ReadinessCheck::count());
```

dengan:

```php
                ->where('preDeparture.dikonfirmasi', false)
                ->where('keadaan.label', fn (string $v) => in_array($v, ['Persiapan kritis lengkap', 'Masih perlu persiapan', 'Tidak disarankan dengan rencana ini'], true))
                ->where('bolehKonfirmasi', true)
                ->has('kondisi'));

        $this->assertSame(0, ReadinessCheck::count());
```

7. Ganti:

```php
        $halaman->assertInertia(fn (AssertableInertia $page) => $page
            ->where('check.persiapan', fn (string $v) => (bool) preg_match('/^\d+ dari \d+ item dikonfirmasi$/', $v)));
    }
```

dengan:

```php
        $halaman->assertInertia(fn (AssertableInertia $page) => $page
            ->where('persiapan.teks', fn (string $v) => (bool) preg_match('/^\d+ dari \d+ item dikonfirmasi$/', $v)));
    }
```

8. Ganti:

```php
            $this->assertDoesNotMatchRegularExpression('/\bsiap\b|\baman\b|\blayak\b|direkomendasikan/i', $keadaan->label());
        }
```

dengan:

```php
            $this->assertDoesNotMatchRegularExpression('/\bsiap\b|\baman\b|\blayak\b|direkomendasikan/i', $keadaan->label());
        }
    }

    /**
     * Prakiraan untuk tanggal trip (R-013, spec v3 bagian 6.11): sebelum H-3 "Belum terbit"
     * dengan tanggal terbitnya; sesudahnya baris untuk tanggal itu saja beserta sumber dan
     * area referensinya; sumber hidup yang belum mencapai tanggal itu disebut belum tersedia;
     * tanpa data sama sekali, pesan kegagalannya, bukan cuaca karangan.
     */
    public function test_the_forecast_is_for_the_trip_date_and_never_invented(): void
    {
        $this->travelTo(Carbon::parse('2026-09-25 03:00:00', 'UTC'));
        $trip = $this->trip();
        $trip->update(['planned_date' => '2026-10-10']);
        $prakiraan = fn () => $this->actingAs($trip->user)->get(route('trips.readiness', $trip))->viewData('page')['props']['kondisi']['prakiraan'];

        $menunggu = $prakiraan();
        $this->assertSame('menunggu', $menunggu['keadaan']);
        $this->assertSame('Prakiraan untuk Sab, 10 Okt', $menunggu['judul']);
        $this->assertSame('Menunggu BMKG. Terbit mulai Rab, 7 Okt; Memount tidak menebak cuaca sebelum itu.', $menunggu['pesan']);

        $this->travelTo(Carbon::parse('2026-10-08 01:00:00', 'UTC'));
        $this->assertSame(['gagal', 'Data cuaca tidak tersedia.'], [$prakiraan()['keadaan'], $prakiraan()['pesan']]);

        WeatherSnapshot::factory()->create(['adm4_code' => $trip->trail->weather_adm4_code, 'forecast_at' => now()->addHours(6), 'fetched_at' => now()]);
        $this->assertSame('belumTersedia', $prakiraan()['keadaan']);

        WeatherSnapshot::factory()->create(['adm4_code' => $trip->trail->weather_adm4_code, 'forecast_at' => Carbon::parse('2026-10-10 00:00:00', 'UTC'), 'fetched_at' => now(), 'weather_description' => 'Hujan ringan']);
        $tersedia = $prakiraan();
        $this->assertSame('tersedia', $tersedia['keadaan']);
        $this->assertSame([['waktu' => '07.00', 'cuaca' => 'Hujan ringan', 'suhu' => '20,0 °C']], $tersedia['baris']);
        $this->assertSame('BMKG', $tersedia['sumber']);
        $this->assertSame('Area basecamp', $tersedia['area']);
    }

    /**
     * Tiga hal prototype 10: kecocokan untuk rencana, persiapan dengan butir kritis yang kurang
     * beserta aksinya, dan kondisi terkini. Keadaan menyebut berapa butir kritis yang belum,
     * pesan hari keberangkatan menyebut pos registrasinya, dan tidak ada skor atau persen.
     */
    public function test_the_page_shows_the_three_checks_with_their_next_actions(): void
    {
        $this->seed(PreparationTemplateSeeder::class);
        $trip = $this->trip();
        $trip->preparationItems()->where('is_critical', true)->orderBy('id')->get()->take(2)->each->update(['status' => PreparationStatus::NOT_CONFIRMED->value]);

        $props = $this->actingAs($trip->user)->get(route('trips.readiness', $trip))->viewData('page')['props'];

        $this->assertSame('NEEDS_PREPARATION', $props['keadaan']['nilai']);
        $this->assertSame('2 butir kritis belum dikonfirmasi.', $props['keadaan']['kalimat']);
        $this->assertCount(2, $props['persiapan']['butir']);
        $this->assertSame('Konfirmasi', $props['persiapan']['butir'][0]['aksi']['label']);
        $this->assertStringStartsWith(route('trips.preparation', $trip).'#butir-', $props['persiapan']['butir'][0]['aksi']['href']);
        $this->assertSame(true, $props['kecocokan']['denganRencana']);
        $this->assertSame(route('trails.fit', ['trail' => $trip->trail, 'trip' => $trip->id]), $props['kecocokan']['tautan']);
        $this->assertSame('Pastikan status langsung di pos registrasi Basecamp. Memount tidak menampilkan status dari simpanan lama, karena status "Buka" yang basi bisa menyesatkan.', $props['hariKeberangkatan']);
        $this->assertDoesNotMatchRegularExpression('/score|\d+\s?%/i', json_encode($props));

        $halaman = File::get(resource_path('js/pages/Trips/Readiness.tsx'));
        foreach (['Ini dukungan keputusan, bukan izin medis, izin hukum, atau jaminan keselamatan.', 'Status resmi menjelaskan ketentuan pengelola, bukan jaminan keselamatan.', 'Saya sudah meninjau semuanya', 'Selesaikan {persiapan.butir.length} butir', '<LangkahRingkas kini="kesiapan"', 'Sejak cek terakhir', 'Di hari keberangkatan'] as $penanda) {
            $this->assertStringContainsString($penanda, $halaman);
        }
```

`tests/Feature/TripReactPagesTest.php`:

1. Ganti:

```php
     * menyatakan sesuatu aman.
     */
```

dengan:

```php
     * menyatakan sesuatu aman.
     *
     * W4 Tugas 6 memakai kalimat prototype 10 ("bukan izin medis, izin hukum, atau jaminan
     * keselamatan"), yang menyebut ketiga batas R-014 dengan kata. Kalimat itu satu-satunya
     * tempat kata "medis" boleh muncul, jadi ia dikeluarkan dari sapuan seperti kalimat lama.
     */
```

2. Ganti:

```php
    {
        $kalimat = 'Penilaian ini menggabungkan kecocokan jalur, kelengkapan persiapan, dan kondisi terkini. Sistem tidak menyatakan bahwa suatu pendakian aman.';
        $this->assertStringContainsString($kalimat, File::get(resource_path('js/pages/Trips/Readiness.tsx')));
```

dengan:

```php
    {
        $kalimat = 'Ini dukungan keputusan, bukan izin medis, izin hukum, atau jaminan keselamatan.';
        $this->assertStringContainsString($kalimat, File::get(resource_path('js/pages/Trips/Readiness.tsx')));
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='ReadinessDashboard|ReadinessSinceLastCheck|StaleReadiness|TripReactPages|EmptyStateCoverage|PageQueryBudget|NoDeadViewData|RefusalMessages|TripOwnership|ArchivedTrailWithActiveTrip|CoreJourney'`
Expected: FAIL. Pada simulasi rencana: 66 test, 52 lulus, 7 gagal, 7 error. Yang merah: tiga test `ReadinessSinceLastCheckTest` (props `sejakTerakhir` belum ada), enam test `ReadinessDashboardTest` (props `keadaan`, `persiapan`, dan `kondisi` belum ada; flash lama), dua test `ArchivedTrailWithActiveTripTest` (props `keadaan` belum ada), `CoreJourneyTest::test_a_hiker_can_walk_the_whole_planning_journey` (flash lama), `EmptyStateCoverageTest::test_every_list_says_something_when_it_has_nothing` (daftar tetapnya kini memuat `sejakTerakhir.butir`, sedangkan halaman lama masih merender `check.penjelasan`), dan `TripReactPagesTest::test_readiness_is_decision_support_and_nothing_is_called_safe` (kalimat R-014 baru).

- [ ] **Step 3: Tulis implementasinya.**

Buat `app/Services/Trips/HalamanKesiapan.php`:

```php
<?php

namespace App\Services\Trips;

use App\Enums\FreshnessState;
use App\Enums\OfficialStatusValue;
use App\Enums\PreparationCategory;
use App\Enums\PreparationStatus;
use App\Enums\ReadinessState;
use App\Enums\RouteFitLabel;
use App\Models\ReadinessCheck;
use App\Models\TrailConditionReport;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Models\User;
use App\Models\WeatherSnapshot;
use App\Services\ConditionAggregatorService;
use App\Services\PermitService;
use App\Services\PreparationService;
use App\Services\Readiness\ReadinessAssessment;
use App\Services\TrailFitService;
use App\Services\Trails\StatusJalurRingkas;
use App\Support\Timezone;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;

/**
 * Kelas halaman Cek kesiapan (spec v3 bagian 6.11, prototype 10, rencana induk W4 butir 4).
 *
 * Keadaan DK-48 beserta kalimatnya, lalu tiga hal yang diperiksa: kecocokan untuk rencana,
 * persiapan (hitungan butir kritis dan butir yang kurang dengan aksinya), dan kondisi terkini
 * (status resmi dengan sumber dan kesegarannya, prakiraan untuk tanggal trip, laporan komunitas
 * terbaru). "Sejak cek terakhir" membandingkan penilaian sekarang dengan snapshot
 * readiness_checks terakhir yang disimpan pendaki.
 *
 * Penilaiannya dihitung ReadinessService::compute() setiap halaman dibuka dan tidak ditulis
 * (ReadinessDashboardTest). Kesiapan adalah dukungan keputusan, bukan izin medis, izin hukum,
 * atau jaminan keselamatan (R-014); tidak ada skor atau persen (R-005, BR-09).
 */
final class HalamanKesiapan
{
    public function __construct(
        private readonly KepalaTrip $kepala,
        private readonly TrailFitService $trailFit,
        private readonly PermitService $permits,
        private readonly StatusJalurRingkas $statusJalur,
        private readonly ConditionAggregatorService $kondisi,
    ) {}

    /**
     * Membutuhkan relasi trail.mountain dan preparationItems sudah dimuat.
     *
     * @return array<string, mixed>
     */
    public function props(TripPlan $trip, User $pembaca, ReadinessAssessment $penilaian, ?ReadinessCheck $terakhir): array
    {
        $zona = Timezone::forTrail($trip->trail);
        $kritis = PreparationService::kritis($trip->preparationItems);
        $jalurTerbit = $trip->trail->is_published && $trip->trail->archived_at === null;

        return [
            'kepala' => $this->kepala->untuk($trip),
            'tautanLangkah' => array_filter([
                'jalur' => $jalurTerbit ? route('trails.show', $trip->trail) : null,
                'rencana' => route('trips.show', $trip),
                'persiapan' => route('trips.preparation', $trip),
            ]),
            'keadaan' => [
                'nilai' => $penilaian->state->value,
                'label' => $penilaian->state->label(),
                'kalimat' => $this->kalimat($penilaian, count($kritis['belum'])),
                // Semua alasan mesin untuk keadaan selain lengkap, supaya alasan yang paling
                // menentukan (misalnya jalur ditarik dari katalog) tidak tertutup alasan lain.
                'alasan' => $penilaian->state === ReadinessState::READY ? [] : array_values(array_unique($penilaian->explanation['reasons'] ?? [])),
                // §93: jam dibaca dalam zona gunungnya, lengkap penandanya.
                'dihitung' => Timezone::display(now(), $zona),
            ],
            'kecocokan' => $this->kecocokan($trip, $pembaca, $jalurTerbit),
            'persiapan' => $this->persiapan($trip, $kritis),
            'kondisi' => [
                'status' => $this->statusJalur->untuk(EloquentCollection::make([$trip->trail]))[$trip->trail->id],
                'prakiraan' => $this->prakiraan($trip, $penilaian->conditionSnapshot['weather'] ?? [], $zona),
                'laporan' => $this->laporan($penilaian->conditionSnapshot['community'] ?? []),
                'peringatan' => array_values($penilaian->explanation['condition_warnings'] ?? []),
            ],
            'sejakTerakhir' => $terakhir === null ? null : $this->sejak($penilaian, $terakhir, $zona),
            'hariKeberangkatan' => 'Pastikan status langsung di pos registrasi'.($trip->trail->starting_point ? ' '.$trip->trail->starting_point : '')
                .'. Memount tidak menampilkan status dari simpanan lama, karena status "Buka" yang basi bisa menyesatkan.',
        ];
    }

    /**
     * Satu kalimat di bawah label keadaan: berapa butir kritis yang belum, atau ringkasan yang
     * menunjuk daftar alasan di bawahnya.
     */
    private function kalimat(ReadinessAssessment $penilaian, int $kritisBelum): string
    {
        return match ($penilaian->state) {
            ReadinessState::READY => 'Butir kritis sudah dikonfirmasi, dan kondisi terkini tidak menunjukkan penutupan atau peringatan resmi.',
            ReadinessState::NOT_RECOMMENDED => 'Rencana ini berbenturan dengan syarat dasar atau status resmi jalurnya.',
            ReadinessState::NEEDS_PREPARATION => $kritisBelum > 0
                ? $kritisBelum.' butir kritis belum dikonfirmasi.'
                : 'Masih ada yang perlu diperiksa sebelum berangkat.',
        };
    }

    /**
     * Kecocokan untuk rencana ini, dengan tujuan yang sama dengan ruang kerja trip dan halaman
     * penilaian (TripPlan::tujuanPenilaian). Pendaki tanpa profil lengkap tidak dinilai (§91).
     *
     * @return array{label: string|null, labelText: string, alasan: string, denganRencana: bool, layak: bool, tautan: string|null}
     */
    private function kecocokan(TripPlan $trip, User $pembaca, bool $jalurTerbit): array
    {
        $tautan = $jalurTerbit ? route('trails.fit', ['trail' => $trip->trail, 'trip' => $trip->id]) : null;

        if (! $pembaca->hasCompletedProfile()) {
            return [
                'label' => null,
                'labelText' => RouteFitLabel::textFor(null),
                'alasan' => 'Isi profil pendaki supaya kecocokan jalur ini dapat dinilai untuk rencana Anda.',
                'denganRencana' => false,
                'layak' => true,
                'tautan' => null,
            ];
        }

        $fit = $this->trailFit->forTrails($pembaca, EloquentCollection::make([$trip->trail]), $trip->tujuanPenilaian())[$trip->trail->id];

        return [
            'label' => $fit->label?->value,
            'labelText' => RouteFitLabel::textFor($fit->label),
            'alasan' => $fit->alasan,
            'denganRencana' => $fit->denganRencana,
            'layak' => $fit->eligible,
            'tautan' => $tautan,
        ];
    }

    /**
     * Hitungan persiapan dan butir kritis yang belum dikonfirmasi, masing-masing dengan aksinya:
     * butir izin menautkan sistem resmi, butir cuaca menunggu BMKG, butir lain dibuka di daftar
     * persiapan pada barisnya sendiri.
     *
     * @param  array{dikonfirmasi: int, berlaku: int, belum: array<int, string>}  $kritis
     * @return array<string, mixed>
     */
    private function persiapan(TripPlan $trip, array $kritis): array
    {
        $hitungan = TripSteps::hitungPersiapan($trip->preparationItems);
        $terbit = PreparationService::terbitPrakiraan($trip);
        $belum = $trip->preparationItems
            ->where('is_critical', true)
            ->where('status', PreparationStatus::NOT_CONFIRMED)
            ->sortBy('id')
            ->values();
        $aturan = $belum->contains(fn (TripPreparationItem $item) => $item->preparation_item_id === null && $item->label === PreparationService::PERMIT_LABEL)
            ? $this->permits->requirementFor($trip->trail)
            : null;

        return [
            'dikonfirmasi' => $kritis['dikonfirmasi'],
            'berlaku' => $kritis['berlaku'],
            'teks' => TripSteps::teksPersiapan($hitungan),
            'tautan' => route('trips.preparation', $trip),
            'butir' => $belum->map(function (TripPreparationItem $item) use ($trip, $terbit, $aturan) {
                $izin = $item->preparation_item_id === null && $item->label === PreparationService::PERMIT_LABEL;
                $menunggu = $item->category === PreparationCategory::WEATHER && $terbit !== null;

                return [
                    'id' => $item->id,
                    'label' => $item->label,
                    'menunggu' => $menunggu ? 'Menunggu BMKG: terbit mulai '.$terbit->translatedFormat('D, j M') : null,
                    'aksi' => match (true) {
                        $menunggu => null,
                        $izin && filled($aturan?->booking_url) => ['label' => 'Pesan', 'href' => $aturan->booking_url, 'luar' => true],
                        default => ['label' => 'Konfirmasi', 'href' => route('trips.preparation', $trip).'#butir-'.$item->id, 'luar' => false],
                    },
                ];
            })->all(),
        ];
    }

    /**
     * Prakiraan untuk tanggal trip (R-013): sebelum jendela terbit BMKG hanya tanggal terbitnya;
     * sesudahnya baris untuk tanggal itu saja, dengan sumber, area referensi, dan kesegaran.
     * Kegagalan mengambil data disebut, tidak diganti cuaca karangan.
     *
     * @param  array<string, mixed>  $cuaca  konteks WeatherService::contextForTrail()
     * @return array<string, mixed>
     */
    private function prakiraan(TripPlan $trip, array $cuaca, string $zona): array
    {
        $terbit = PreparationService::terbitPrakiraan($trip);
        $tanggal = $trip->planned_date->toDateString();
        $kesegaran = FreshnessState::tryFrom((string) ($cuaca['freshness'] ?? ''));
        $dasar = [
            'judul' => 'Prakiraan untuk '.$trip->planned_date->translatedFormat('D, j M'),
            'sumber' => $cuaca['source'] ?? 'BMKG',
            'area' => $cuaca['reference_area'] ?? null,
            'diperbarui' => isset($cuaca['fetched_at']) ? Timezone::display(Carbon::parse($cuaca['fetched_at']), $zona, 'j M H.i') : null,
            'kesegaran' => $kesegaran !== null && $kesegaran !== FreshnessState::UNKNOWN ? $kesegaran->label() : null,
        ];

        if ($terbit !== null) {
            return $dasar + ['keadaan' => 'menunggu', 'pesan' => 'Menunggu BMKG. Terbit mulai '.$terbit->translatedFormat('D, j M').'; Memount tidak menebak cuaca sebelum itu.', 'baris' => []];
        }

        if (! ($cuaca['available'] ?? false)) {
            return $dasar + ['keadaan' => 'gagal', 'pesan' => $cuaca['message'] ?? 'Data cuaca tidak tersedia.', 'baris' => []];
        }

        $baris = collect($cuaca['forecast'] ?? [])
            ->filter(fn (WeatherSnapshot $s) => $s->forecast_at->copy()->setTimezone($zona)->toDateString() === $tanggal)
            ->map(fn (WeatherSnapshot $s) => [
                'waktu' => $s->forecast_at->copy()->setTimezone($zona)->format('H.i'),
                'cuaca' => $s->weather_description ?? 'Tidak dicatat',
                'suhu' => $s->temperature_c !== null ? number_format((float) $s->temperature_c, 1, ',', '.').' °C' : 'Tidak dicatat',
            ])
            ->values()
            ->all();

        return $baris === []
            ? $dasar + ['keadaan' => 'belumTersedia', 'pesan' => 'Prakiraan untuk tanggal ini belum tersedia dari BMKG.', 'baris' => []]
            : $dasar + ['keadaan' => 'tersedia', 'pesan' => null, 'baris' => $baris];
    }

    /**
     * Laporan komunitas terbaru yang lolos moderasi, dengan kesegarannya dari tanggal
     * pendakian (§59). Komunitas tidak pernah menggantikan status resmi (R-010).
     *
     * @param  array<string, mixed>  $komunitas
     * @return array{ringkas: string, segmen: string|null, kapan: string, kesegaran: string}|null
     */
    private function laporan(array $komunitas): ?array
    {
        $terbaru = $komunitas['reports'][0] ?? null;

        if (! $terbaru instanceof TrailConditionReport) {
            return null;
        }

        $tanda = array_map(fn ($tag) => $tag->label(), $terbaru->tags());
        $hari = $terbaru->daysSinceHike();

        return [
            'ringkas' => $tanda === [] ? 'Tanpa tanda kondisi' : implode(', ', $tanda),
            'segmen' => $terbaru->segment?->name,
            'kapan' => match ($hari) {
                0 => 'pendakian hari ini',
                1 => 'pendakian kemarin',
                default => 'pendakian '.$hari.' hari lalu',
            },
            'kesegaran' => $this->kondisi->reportFreshness($terbaru)->label(),
        ];
    }

    /**
     * "Sejak cek terakhir" (prototype 10): status resmi, laporan komunitas yang masuk sesudah cek
     * itu, butir persiapan yang dikonfirmasi, dan label kecocokan. Setiap baris menyebut keadaannya,
     * juga ketika tidak ada yang berubah.
     *
     * @return array{tanggal: string|null, butir: array<int, array{jenis: string, teks: string}>}
     */
    private function sejak(ReadinessAssessment $kini, ReadinessCheck $lalu, string $zona): array
    {
        $statusLalu = OfficialStatusValue::tryFrom((string) ($lalu->official_status_snapshot['status'] ?? '')) ?? OfficialStatusValue::UNKNOWN;
        $statusKini = OfficialStatusValue::tryFrom((string) ($kini->officialStatusSnapshot['status'] ?? '')) ?? OfficialStatusValue::UNKNOWN;
        $laporanBaru = collect($kini->conditionSnapshot['community']['reports'] ?? [])
            ->filter(fn ($r) => $r instanceof TrailConditionReport && $lalu->computed_at !== null && $r->created_at?->gt($lalu->computed_at))
            ->count();
        $selisih = (int) ($kini->preparationState['confirmed'] ?? 0) - (int) ($lalu->preparation_state['confirmed'] ?? 0);
        $labelLalu = RouteFitLabel::textFor(RouteFitLabel::tryFrom((string) ($lalu->route_fit_snapshot['label'] ?? '')));
        $labelKini = RouteFitLabel::textFor(RouteFitLabel::tryFrom((string) ($kini->routeFitSnapshot['label'] ?? '')));

        return [
            'tanggal' => $lalu->computed_at?->copy()->setTimezone($zona)->translatedFormat('j M'),
            'butir' => [
                $statusLalu === $statusKini
                    ? ['jenis' => 'sama', 'teks' => 'Status resmi tidak berubah.']
                    : ['jenis' => 'berubah', 'teks' => 'Status resmi berubah dari '.$statusLalu->label().' menjadi '.$statusKini->label().'.'],
                $laporanBaru > 0
                    ? ['jenis' => 'bertambah', 'teks' => $laporanBaru.' laporan komunitas baru.']
                    : ['jenis' => 'sama', 'teks' => 'Tidak ada laporan komunitas baru.'],
                match (true) {
                    $selisih > 0 => ['jenis' => 'bertambah', 'teks' => $selisih.' butir persiapan dikonfirmasi.'],
                    $selisih < 0 => ['jenis' => 'berubah', 'teks' => abs($selisih).' butir persiapan kembali belum dikonfirmasi.'],
                    default => ['jenis' => 'sama', 'teks' => 'Persiapan tidak berubah.'],
                },
                $labelLalu === $labelKini
                    ? ['jenis' => 'sama', 'teks' => 'Label kecocokan tetap '.$labelKini.'.']
                    : ['jenis' => 'berubah', 'teks' => 'Label kecocokan berubah dari '.$labelLalu.' menjadi '.$labelKini.'.'],
            ],
        ];
    }
}
```

`app/Http/Controllers/Trips/ReadinessController.php`:

1. Ganti:

```php
use App\Http\Controllers\Controller;
use App\Http\Resources\TripReadinessResource;
use App\Models\ReadinessCheck;
use App\Models\TripPlan;
use App\Services\ReadinessService;
use App\Services\Trips\PreDepartureCheck;
```

dengan:

```php
use App\Http\Controllers\Controller;
use App\Models\TripPlan;
use App\Services\ReadinessService;
use App\Services\Trips\HalamanKesiapan;
use App\Services\Trips\PreDepartureCheck;
```

2. Ganti:

```php
/**
 * FR-10 kesiapan: route fit + persiapan + kondisi terkini (PRD §38-39). Dukungan
 * keputusan, bukan izin keselamatan (R-014). Pengganti komponen ReadinessDashboard.
 *
```

dengan:

```php
/**
 * FR-10 kesiapan: route fit + persiapan + kondisi terkini (PRD §38-39), disusun kelas halaman
 * HalamanKesiapan (spec v3 bagian 6.11). Dukungan keputusan, bukan izin keselamatan (R-014).
 *
```

3. Ganti:

```php
{
    public function show(Request $request, TripPlan $trip, ReadinessService $readiness): Response
    {
```

dengan:

```php
{
    public function show(Request $request, TripPlan $trip, ReadinessService $readiness, HalamanKesiapan $halaman): Response
    {
```

4. Ganti:

```php
        $this->authorize('view', $trip);

        $trip->load('trail.mountain');
        $zona = Timezone::forTrail($trip->trail);
```

dengan:

```php
        $this->authorize('view', $trip);

        $trip->load('trail.mountain', 'preparationItems');
        $zona = Timezone::forTrail($trip->trail);
```

5. Ganti:

```php
            'title' => 'Kesiapan Pendakian',
            'trip' => [
                'id' => $trip->id,
                'keterangan' => $trip->name.' - '.$trip->trail->name,
            ],
            // Tidak disimpan, hanya untuk ditampilkan.
            'check' => (new TripReadinessResource(new ReadinessCheck($assessment->toAttributes()), $zona))->resolve($request),
            'bolehKonfirmasi' => $assessment->state !== ReadinessState::NOT_RECOMMENDED,
```

dengan:

```php
            'title' => 'Kesiapan Pendakian',
            ...$halaman->props($trip, $request->user(), $assessment, $terakhir),
            'bolehKonfirmasi' => $assessment->state !== ReadinessState::NOT_RECOMMENDED,
```

6. Ganti:

```php
    }

    public function recompute(TripPlan $trip, ReadinessService $readiness): RedirectResponse
```

dengan:

```php
    }

    /**
     * Menyimpan hasil cek sebagai pembanding "Sejak cek terakhir"; membuka halaman tidak
     * pernah menulis (ReadinessDashboardTest).
     */
    public function recompute(TripPlan $trip, ReadinessService $readiness): RedirectResponse
```

7. Ganti:

```php
        $readiness->record($trip);

        return redirect()->route('trips.readiness', $trip)->with('status', 'Kesiapan dihitung ulang.');
    }
```

dengan:

```php
        $readiness->record($trip);

        return redirect()->route('trips.readiness', $trip)->with('status', 'Hasil cek disimpan. Cek berikutnya dibandingkan dengan hasil ini.');
    }
```

8. Ganti:

```php
            ? redirect()->route('trips.readiness', $trip)->with('penolakan-kesiapan', $penolakan)
            : redirect()->route('trips.readiness', $trip)->with('status', 'Pre-departure check tercatat.');
    }
```

dengan:

```php
            ? redirect()->route('trips.readiness', $trip)->with('penolakan-kesiapan', $penolakan)
            : redirect()->route('trips.readiness', $trip)->with('status', 'Tinjauan Anda tercatat.');
    }
```

`app/Services/ReadinessService.php`:

1. Ganti:

```php
        $trip->loadMissing(['trail.mountain', 'preparationItems', 'user.profile', 'user.experience', 'user.preference', 'hikingGoal']);

        $fit = $this->routeFit->evaluate($trip->user, $trip->hikingGoal, $trip->trail);
        $preparationState = $this->preparation->state($trip);
```

dengan:

```php
        $trip->loadMissing(['trail.mountain', 'preparationItems', 'user.profile', 'user.experience', 'user.preference', 'hikingGoal']);

        // Kecocokan untuk rencana ini (spec v3 bagian 6.11): tujuan tersimpan trip, atau jenis
        // dan tanggal tripnya sebagai tujuan sementara, sama dengan ruang kerja dan halaman
        // penilaian kecocokan. Tanpa itu trip lama tanpa tujuan dinilai dengan kecocokan dasar
        // dan "tektok" untuk jalur bermalam tidak pernah tertahan syarat dasar.
        $fit = $this->routeFit->evaluate($trip->user, $trip->tujuanPenilaian(), $trip->trail);
        $preparationState = $this->preparation->state($trip);
```

`resources/js/pages/Trips/Preparation.tsx`:

1. Ganti:

```tsx
                                    return (
                                        <li key={item.id} className="px-4 py-2">
                                            <div className="flex items-start gap-2">
```

dengan:

```tsx
                                    return (
                                        <li key={item.id} id={`butir-${item.id}`} className="scroll-mt-24 px-4 py-2">
                                            <div className="flex items-start gap-2">
```

Tulis ulang `resources/js/pages/Trips/Readiness.tsx` utuh:

```tsx
import { router, usePage } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import { BarisStatus } from '@/components/trails/BarisStatus';
import { LabelKecocokan, type LabelFit } from '@/components/trails/LabelKecocokan';
import { LabelKomunitas } from '@/components/trails/LabelSumber';
import type { StatusRingkas } from '@/components/trails/types';
import { KepalaTrip } from '@/components/trips/KepalaTrip';
import { LangkahRingkas, type KunciLangkah } from '@/components/trips/LangkahRingkas';
import type { KepalaTripData } from '@/components/trips/types';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Icon } from '@/components/ui/Icon';
import { Lencana } from '@/components/ui/Lencana';
import { PageHeader } from '@/components/ui/PageHeader';
import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';
import { AppLayout } from '@/layouts/AppLayout';
import { show } from '@/routes/trips';
import { confirm, recompute } from '@/routes/trips/readiness';
import type { PropsBersama } from '@/types';

type NilaiKeadaan = 'READY' | 'NEEDS_PREPARATION' | 'NOT_RECOMMENDED';

interface Prakiraan {
    judul: string;
    keadaan: 'menunggu' | 'gagal' | 'belumTersedia' | 'tersedia';
    pesan: string | null;
    baris: Array<{ waktu: string; cuaca: string; suhu: string }>;
    sumber: string;
    area: string | null;
    diperbarui: string | null;
    kesegaran: string | null;
}

interface Props extends PropsBersama {
    kepala: KepalaTripData;
    tautanLangkah: Partial<Record<KunciLangkah, string>>;
    keadaan: { nilai: NilaiKeadaan; label: string; kalimat: string; alasan: string[]; dihitung: string | null };
    kecocokan: { label: LabelFit; labelText: string; alasan: string; denganRencana: boolean; layak: boolean; tautan: string | null };
    persiapan: {
        dikonfirmasi: number;
        berlaku: number;
        teks: string;
        tautan: string;
        butir: Array<{ id: number; label: string; menunggu: string | null; aksi: { label: string; href: string; luar: boolean } | null }>;
    };
    kondisi: {
        status: StatusRingkas;
        prakiraan: Prakiraan;
        laporan: { ringkas: string; segmen: string | null; kapan: string; kesegaran: string } | null;
        peringatan: string[];
    };
    sejakTerakhir: { tanggal: string | null; butir: Array<{ jenis: 'sama' | 'bertambah' | 'berubah'; teks: string }> } | null;
    hariKeberangkatan: string;
    bolehKonfirmasi: boolean;
    preDeparture: { dikonfirmasi: boolean; dicatat: string | null };
    penolakan: string | null;
}

const gayaKeadaan: Record<NilaiKeadaan, string> = {
    READY: 'border-brand-200 bg-brand-50',
    NEEDS_PREPARATION: 'border-warn-300 bg-warn-50',
    NOT_RECOMMENDED: 'border-danger-300 bg-danger-50',
};

const lencanaPrakiraan: Record<Prakiraan['keadaan'], string | null> = {
    menunggu: 'Belum terbit',
    gagal: 'Tidak tersedia',
    belumTersedia: 'Belum ada',
    tersedia: null,
};

const tautanTeks =
    'inline-flex min-h-11 items-center text-kecil font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600';

/** Satu dari tiga hal yang diperiksa: nomor dalam cincin pos dan isinya. */
function HalDiperiksa({ nomor, judul, children }: { nomor: number; judul: string; children: ReactNode }) {
    return (
        <li className="flex gap-4 rounded-lg border border-subtle bg-surface p-5">
            <span aria-hidden="true" className="flex size-8 shrink-0 items-center justify-center rounded-full border-2 border-brand-700 text-kecil font-bold tabular-nums text-brand-ink">
                {nomor}
            </span>
            <div className="min-w-0 flex-1">
                <h3 className="text-subjudul text-primary">{judul}</h3>
                <div className="mt-2 space-y-3">{children}</div>
            </div>
        </li>
    );
}

/**
 * Cek kesiapan (spec v3 bagian 6.11, prototype 10): keadaan DK-48 dengan kalimatnya, tiga hal
 * yang diperiksa (kecocokan untuk rencana, persiapan, kondisi terkini), apa yang berubah sejak
 * cek terakhir, pesan hari keberangkatan, lalu satu aksi utama: menyelesaikan butir yang
 * kurang, atau menandai sudah meninjau semuanya. Dukungan keputusan, bukan izin medis, izin
 * hukum, atau jaminan keselamatan (R-014); tanpa skor (R-005).
 */
export default function Readiness() {
    const { kepala, tautanLangkah, keadaan, kecocokan, persiapan, kondisi, sejakTerakhir, hariKeberangkatan, bolehKonfirmasi, preDeparture, penolakan, flash } = usePage<Props>().props;
    const [menyimpan, setMenyimpan] = useState(false);
    const [mengonfirmasi, setMengonfirmasi] = useState(false);

    const simpanCek = () => {
        setMenyimpan(true);
        router.post(recompute.url(kepala.id), {}, { preserveScroll: true, onFinish: () => setMenyimpan(false) });
    };

    const konfirmasi = () => {
        setMengonfirmasi(true);
        router.post(confirm.url(kepala.id), {}, { preserveScroll: true, onFinish: () => setMengonfirmasi(false) });
    };

    const prakiraan = kondisi.prakiraan;
    const lencana = lencanaPrakiraan[prakiraan.keadaan];

    return (
        <div className="py-6">
            <div className="mx-auto max-w-2xl space-y-8 px-4 sm:px-6">
                <div>
                    <PageHeader induk={{ href: show.url(kepala.id), label: 'Ruang kerja trip' }} title={kepala.nama}>
                        <KepalaTrip kepala={kepala} />
                    </PageHeader>
                    <LangkahRingkas kini="kesiapan" tautan={tautanLangkah} />
                </div>

                {/* Penolakan tidak memakai alert hijau: kotak berhasil yang berisi kabar gagal
                    adalah pesan yang membantah dirinya sendiri. */}
                {penolakan ? <Alert variant="warning">{penolakan}</Alert> : null}
                {flash.status ? <Alert variant="success">{flash.status}</Alert> : null}
                {preDeparture.dikonfirmasi ? (
                    <Alert variant="success" title="Anda sudah menandai meninjau semuanya">
                        Dicatat {preDeparture.dicatat}. Kondisi di bawah tetap dihitung ulang setiap halaman dibuka, jadi periksa kembali sebelum berangkat.
                    </Alert>
                ) : null}

                <section aria-labelledby="keadaan-kesiapan" className={kelas('rounded-utama border p-5', gayaKeadaan[keadaan.nilai])}>
                    <p className="text-meta font-bold text-secondary">Keadaan kesiapan</p>
                    <h2 id="keadaan-kesiapan" className="mt-1 text-judul-kartu text-primary">
                        {keadaan.label}
                    </h2>
                    <p className="mt-2 text-isi text-primary">{keadaan.kalimat}</p>
                    {keadaan.alasan.length > 0 ? (
                        <ul className="mt-2 list-disc space-y-1 pl-5 text-kecil text-primary">
                            {keadaan.alasan.map((a) => (
                                <li key={a}>{a}</li>
                            ))}
                        </ul>
                    ) : null}
                    <p className="mt-3 border-t border-subtle pt-3 text-kecil text-secondary">Ini dukungan keputusan, bukan izin medis, izin hukum, atau jaminan keselamatan.</p>
                    {keadaan.dihitung ? <p className="mt-1 text-meta text-muted">Dihitung {keadaan.dihitung}</p> : null}
                </section>

                <section aria-labelledby="tiga-hal">
                    <h2 id="tiga-hal" className="text-bagian text-primary">
                        Tiga hal yang diperiksa
                    </h2>
                    <ol className="mt-3 space-y-3">
                        <HalDiperiksa nomor={1} judul="Kecocokan jalur">
                            <p className="flex flex-wrap items-center gap-2">
                                <LabelKecocokan nilai={kecocokan.label} label={kecocokan.labelText} />
                                <span className="text-meta text-secondary">{kecocokan.denganRencana ? 'untuk rencana ini' : 'Kecocokan dasar'}</span>
                            </p>
                            {!kecocokan.layak ? <p className="text-kecil font-bold text-primary">Tidak memenuhi syarat dasar untuk rencana ini.</p> : null}
                            <p className="text-kecil text-secondary">{kecocokan.alasan}</p>
                            {kecocokan.tautan ? (
                                <Tautan href={kecocokan.tautan} className={tautanTeks}>
                                    Lihat penilaian lengkap
                                </Tautan>
                            ) : null}
                        </HalDiperiksa>

                        <HalDiperiksa nomor={2} judul="Persiapan">
                            {persiapan.berlaku > 0 ? (
                                <>
                                    <p data-angka className="text-kecil font-bold text-primary">
                                        {persiapan.dikonfirmasi} dari {persiapan.berlaku} butir kritis dikonfirmasi
                                    </p>
                                    <div aria-hidden="true" className="flex gap-1">
                                        {Array.from({ length: persiapan.berlaku }, (_, i) => (
                                            <span key={i} className={kelas('h-2 flex-1 rounded-full', i < persiapan.dikonfirmasi ? 'bg-brand-700' : 'bg-surface-sunken')} />
                                        ))}
                                    </div>
                                </>
                            ) : (
                                <p className="text-kecil text-secondary">{persiapan.teks}</p>
                            )}
                            {persiapan.butir.length === 0 ? (
                                <p className="text-kecil text-secondary">Tidak ada butir kritis yang menunggu konfirmasi.</p>
                            ) : (
                                <ul className="space-y-2">
                                    {persiapan.butir.map((b) => (
                                        <li key={b.id} className="flex items-center gap-3 rounded-tombol bg-surface-sunken px-4 py-1">
                                            <span className="min-w-0 flex-1 py-2">
                                                <span className="block break-words text-kecil font-bold text-primary">{b.label}</span>
                                                {b.menunggu ? <span className="mt-0.5 block text-meta text-secondary">{b.menunggu}</span> : null}
                                            </span>
                                            {b.aksi && b.aksi.luar ? (
                                                <a href={b.aksi.href} target="_blank" rel="noopener noreferrer" className={kelas(tautanTeks, 'shrink-0 gap-1')}>
                                                    {b.aksi.label}
                                                    <Icon name="arrow-square-out" size={16} />
                                                    <span className="sr-only"> {b.label} di sistem resmi (membuka tab baru)</span>
                                                </a>
                                            ) : null}
                                            {b.aksi && !b.aksi.luar ? (
                                                <Tautan href={b.aksi.href} className={kelas(tautanTeks, 'shrink-0')}>
                                                    {b.aksi.label}
                                                    <span className="sr-only"> {b.label}</span>
                                                </Tautan>
                                            ) : null}
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </HalDiperiksa>

                        <HalDiperiksa nomor={3} judul="Kondisi terkini">
                            <div>
                                <p className="text-meta text-secondary">Status resmi</p>
                                <BarisStatus status={kondisi.status} className="mt-1" />
                                <p className="mt-1 text-meta text-secondary">Status resmi menjelaskan ketentuan pengelola, bukan jaminan keselamatan.</p>
                            </div>

                            <div className="border-t border-subtle pt-3">
                                <p className="flex flex-wrap items-center justify-between gap-2">
                                    <span className="text-meta text-secondary">{prakiraan.judul}</span>
                                    {lencana ? (
                                        <Lencana garis="putus">{lencana}</Lencana>
                                    ) : prakiraan.kesegaran ? (
                                        <Lencana nada="merek">{prakiraan.kesegaran}</Lencana>
                                    ) : null}
                                </p>
                                {prakiraan.pesan ? <p className="mt-1 text-kecil text-primary">{prakiraan.pesan}</p> : null}
                                {prakiraan.baris.length > 0 ? (
                                    <table className="mt-2 w-full text-left text-kecil">
                                        <caption className="sr-only">{prakiraan.judul}</caption>
                                        <thead className="text-meta text-secondary">
                                            <tr>
                                                <th scope="col" className="py-1 font-semibold">Jam</th>
                                                <th scope="col" className="py-1 font-semibold">Cuaca</th>
                                                <th scope="col" className="py-1 font-semibold">Suhu</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {prakiraan.baris.map((b) => (
                                                <tr key={b.waktu} className="border-t border-subtle">
                                                    <td className="py-1.5 tabular-nums">{b.waktu}</td>
                                                    <td className="py-1.5">{b.cuaca}</td>
                                                    <td className="py-1.5 tabular-nums">{b.suhu}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                ) : null}
                                <p className="mt-1 text-meta text-muted">
                                    Sumber: {prakiraan.sumber}
                                    {prakiraan.area ? `, area referensi ${prakiraan.area}` : ''}
                                    {prakiraan.diperbarui ? `, diperbarui ${prakiraan.diperbarui}` : ''}
                                </p>
                            </div>

                            {kondisi.laporan ? (
                                <div className="rounded-tombol border border-dashed border-community-500 bg-community-50 p-4">
                                    <p className="flex flex-wrap items-center justify-between gap-2">
                                        <LabelKomunitas />
                                        <Lencana>{kondisi.laporan.kesegaran}</Lencana>
                                    </p>
                                    <p className="mt-2 text-kecil font-bold text-primary">
                                        {kondisi.laporan.ringkas}
                                        {kondisi.laporan.segmen ? ` di ${kondisi.laporan.segmen}` : ''}
                                    </p>
                                    <p className="mt-1 text-meta text-secondary">{kondisi.laporan.kapan}</p>
                                </div>
                            ) : (
                                <p className="text-kecil text-secondary">Belum ada laporan pendaki yang lolos moderasi dalam 30 hari terakhir.</p>
                            )}

                            {kondisi.peringatan.length > 0 ? (
                                <ul className="list-disc space-y-1 pl-5 text-kecil text-primary">
                                    {kondisi.peringatan.map((p) => (
                                        <li key={p}>{p}</li>
                                    ))}
                                </ul>
                            ) : null}
                        </HalDiperiksa>
                    </ol>
                </section>

                <section aria-labelledby="sejak-cek">
                    <h2 id="sejak-cek" className="text-bagian text-primary">
                        Sejak cek terakhir {sejakTerakhir?.tanggal ? <span className="text-isi font-semibold text-secondary tabular-nums">{sejakTerakhir.tanggal}</span> : null}
                    </h2>
                    {sejakTerakhir ? (
                        <ul className="mt-3 space-y-2 rounded-lg border border-subtle bg-surface p-4">
                            {sejakTerakhir.butir.map((b) => (
                                <li key={b.teks} className="flex items-start gap-3 text-kecil text-primary">
                                    {b.jenis === 'berubah' ? (
                                        <Lencana nada="peringatan">Berubah</Lencana>
                                    ) : (
                                        <span aria-hidden="true" className="w-4 shrink-0 text-center font-bold text-secondary">
                                            {b.jenis === 'bertambah' ? '+' : '='}
                                        </span>
                                    )}
                                    <span className="min-w-0">{b.teks}</span>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <p className="mt-2 text-kecil text-secondary">Belum ada hasil cek yang disimpan. Simpan hasil cek ini supaya cek berikutnya dapat menyebut apa yang berubah.</p>
                    )}
                </section>

                <section aria-labelledby="hari-berangkat" className="rounded-lg bg-surface-sunken p-5">
                    <h2 id="hari-berangkat" className="text-subjudul text-primary">
                        Di hari keberangkatan
                    </h2>
                    <p className="mt-1 text-kecil text-secondary">{hariKeberangkatan}</p>
                </section>

                <div className="space-y-3">
                    {persiapan.butir.length > 0 ? (
                        <Button href={persiapan.tautan} className="w-full">
                            Selesaikan {persiapan.butir.length} butir
                        </Button>
                    ) : null}
                    {bolehKonfirmasi ? (
                        <Button variant={persiapan.butir.length > 0 ? 'secondary' : 'primary'} className="w-full" onClick={konfirmasi} processing={mengonfirmasi}>
                            Saya sudah meninjau semuanya
                        </Button>
                    ) : (
                        <p className="text-kecil text-secondary">Tinjauan belum dapat dicatat karena rencana ini tidak disarankan; alasannya tertulis di atas.</p>
                    )}
                    <Button variant="ghost" size="sm" className="-ml-4" onClick={simpanCek} processing={menyimpan}>
                        Simpan hasil cek ini
                    </Button>
                </div>
            </div>
        </div>
    );
}

Readiness.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
```

`tests/Pratinjau/HalamanPratinjauTest.php`:

1. Ganti:

```php
use App\Services\PreparationService;
use App\Services\Trails\TautanBagikan;
```

dengan:

```php
use App\Services\PreparationService;
use App\Services\ReadinessService;
use App\Services\Trails\TautanBagikan;
```

2. Ganti:

```php
        $this->tulisPratinjau('persiapan', $this->get(self::ASAL.'/trips/'.$trip->id.'/preparation'), 'Trips/Preparation');
    }
```

dengan:

```php
        $this->tulisPratinjau('persiapan', $this->get(self::ASAL.'/trips/'.$trip->id.'/preparation'), 'Trips/Preparation');
    }

    /**
     * Cek kesiapan W4 (acuan 10): daftar persiapan dibangkitkan dengan tiga butir kritis
     * pertama dikonfirmasi, lalu satu hasil cek disimpan sehari sebelumnya, supaya "Sejak cek
     * terakhir" ikut terpotret.
     */
    public function test_cek_kesiapan(): void
    {
        $budi = $this->sebagaiBudi();
        $trip = TripPlan::where('user_id', $budi->id)->where('status', TripStatus::PLANNED->value)->sole();
        $butir = app(PreparationService::class)->generateFor($trip);
        $butir->where('is_critical', true)->take(3)->each(fn ($satu) => $satu->update(['status' => PreparationStatus::CONFIRMED->value]));
        app(ReadinessService::class)->record($trip->fresh())->update(['computed_at' => now()->subDay()]);
        $butir->where('is_critical', true)->slice(3)->take(2)->each(fn ($satu) => $satu->update(['status' => PreparationStatus::CONFIRMED->value]));

        $this->tulisPratinjau('cek-kesiapan', $this->get(self::ASAL.'/trips/'.$trip->id.'/readiness'), 'Trips/Readiness');
    }
```

Hapus berkas `app/Http/Resources/TripReadinessResource.php`.

- [ ] **Step 4: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Services/Trips/HalamanKesiapan.php app/Services/ReadinessService.php app/Http/Controllers/Trips/ReadinessController.php tests/Feature/ReadinessSinceLastCheckTest.php tests/Feature/ReadinessDashboardTest.php tests/Feature/ArchivedTrailWithActiveTripTest.php tests/Feature/CoreJourneyTest.php tests/Feature/EmptyStateCoverageTest.php tests/Feature/TripReactPagesTest.php tests/Pratinjau/HalamanPratinjauTest.php`, lalu `php artisan test --compact --filter='ReadinessDashboard|ReadinessSinceLastCheck|StaleReadiness|TripReactPages|EmptyStateCoverage|PageQueryBudget|NoDeadViewData|RefusalMessages|TripOwnership|ArchivedTrailWithActiveTrip|CoreJourney'`
Expected: PASS. Pada simulasi rencana: 66 test lulus.

- [ ] **Step 5: Suite, tipe, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 1.811 test, 1.809 lulus, 2 dilewati.

Run: `npx tsc --noEmit`
Expected: keluar tanpa keluaran galat.

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `php artisan test --compact --testsuite=Pratinjau`
Expected: build `✓ built`; Pratinjau lulus termasuk `test_cek_kesiapan`. Pada simulasi rencana: 18 test lulus.

- [ ] **Step 6: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 7: Dokumen klaster Trip

Ruling W4-R24, W4-R25, W4-R34. Spesifikasi fitur 04 ditulis ulang menurut kode sesudah Tugas 1 sampai 6;
`DESIGN.md` menambah konvensi `penjelas` dan paragraf klaster Trip; `docs/ARCHITECTURE.md` memperbarui baris Trip,
Persiapan, dan Readiness di peta halaman serta menambah kelas halaman Trip; `docs/PRD.md` §146 mendapat governance
record GR-05 untuk kata DK-48, batas peserta, dan kecocokan untuk rencana pada kesiapan. Jangkar `DESIGN.md` dan
`docs/ARCHITECTURE.md` dipilih di luar teks yang diganti W3 Tugas 11. `HANDOVER.md` dan `CLAUDE.md` tidak disunting
di tugas ini.

**Files:**
- Modify: `DESIGN.md`, `docs/ARCHITECTURE.md`, `docs/PRD.md`, `docs/fitur/04-perjalanan-persiapan.md`

**Interfaces:**
- Consumes: nama kelas, komponen, dan test dari Tugas 1 sampai 6.
- Produces: dokumen yang cocok dengan kode W4 dan governance record GR-05.

- [ ] **Step 1: Tulis dokumennya.**

`DESIGN.md`:

1. Ganti:

```markdown
**Konvensi prop untuk primitif baru** (ruling W1-F3): kontrol berkeadaan memakai `nilai` dan `onUbah`, nama tampak
memakai `label`, dan nama prop berbahasa Indonesia; primitif yang sudah ada tidak diganti namanya.

**Fokus tidak pernah jatuh ke body.** Chip saringan yang dilepas menyerahkan fokus ke chip tetangga atau wadah grupnya;
```

dengan:

```markdown
**Konvensi prop untuk primitif baru** (ruling W1-F3): kontrol berkeadaan memakai `nilai` dan `onUbah`, nama tampak
memakai `label`, dan nama prop berbahasa Indonesia; primitif yang sudah ada tidak diganti namanya. Grup kontrol yang
dapat bergalat (`KontrolSegmen`, `Stepper`) menerima `penjelas`, id galat atau keterangan yang diikat lewat
`aria-describedby` pada grupnya (W4, `KeyboardAndScreenReaderTest`).

**Klaster Trip** (W4, spec v3 bagian 6.8 sampai 6.11). Ruang kerja trip menampilkan empat langkah (Jalur, Rencana,
Persiapan, Cek) sebagai garis rute berujung cincin pos: garis merek menyambung cincin bernomor, dan cincin langkah yang
selesai terisi dengan centang. Mulai 1024 px langkah menjadi kolom kiri 18rem di samping isinya. Halaman Rencanakan,
Persiapan, dan Cek membawa baris langkah ringkas (`LangkahRingkas`) dengan langkah halaman itu ditandai garis bawah
merek, tebal huruf, dan `aria-current`. Kepala trip (`KepalaTrip`) menyebut jenis dan sisa hari sebagai lencana,
tanggal dengan kata "sampai", jam dengan zona gunungnya, dan peserta, tanpa titik tengah; status "Siap berangkat" tidak
pernah tampil di kepala (R-014). Hitungan butir kritis berupa ruas per butir ditambah kalimat "N dari M", bukan
persen. Keadaan kesiapan memakai kata DK-48 dan kalimat "Ini dukungan keputusan, bukan izin medis, izin hukum, atau
jaminan keselamatan." Hanya kartu utama indeks trip yang membawa tombol penuh; kartu kelompok memakai tautan teks.

**Fokus tidak pernah jatuh ke body.** Chip saringan yang dilepas menyerahkan fokus ke chip tetangga atau wadah grupnya;
```

`docs/ARCHITECTURE.md`:

1. Ganti:

```markdown
| Trip | `Trips\TripForm`, `Trips\TripShow`, `Trips\TripIndex` | `/trips*` |
| Persiapan | `Trips\PreparationChecklist` | `/trips/{trip}/preparation` |
| Readiness | `Trips\ReadinessDashboard` | `/trips/{trip}/readiness` |
```

dengan:

```markdown
| Trip | **[React]** indeks `pages/Trips/Index.tsx` (`Trips\IndeksTrip`), rencanakan `pages/Trips/Create.tsx` (`Trips\RencanaTrip`), ruang kerja `pages/Trips/Show.tsx` | `/trips`, `/trips/create`, `/trips/{trip}` |
| Persiapan | **[React]** `pages/Trips/Preparation.tsx` (`Trips\HalamanPersiapan`) | `/trips/{trip}/preparation` |
| Readiness | **[React]** `pages/Trips/Readiness.tsx` (`Trips\HalamanKesiapan`) | `/trips/{trip}/readiness` |
```

2. Ganti:

```markdown
| `ReadinessService` | `compute()` menghitung tanpa efek samping, `record()` menyimpan |
| `AuditLogService`, `AnalyticsRecorder` | Jejak audit dan funnel |
```

dengan:

```markdown
| `ReadinessService` | `compute()` menghitung tanpa efek samping, `record()` menyimpan |
| `Trips\IndeksTrip`, `Trips\RencanaTrip`, `Trips\HalamanPersiapan`, `Trips\HalamanKesiapan` | Kelas halaman klaster Trip (W4): indeks berkelompok, rencanakan dengan muat ulang parsial kecocokan untuk rencana, persiapan dengan butir kritis dan butir yang menunggu BMKG, dan cek kesiapan dengan "Sejak cek terakhir"; kueri tetap, tanpa skor di props |
| `Trips\KepalaTrip`, `Trips\TripSteps` | Kepala satu trip dan empat langkahnya (Jalur, Rencana, Persiapan, Cek) beserta aksi berikutnya untuk indeks |
| `AuditLogService`, `AnalyticsRecorder` | Jejak audit dan funnel |
```

`docs/PRD.md`:

1. Ganti:

```markdown
**Scope impact.** Tidak ada; batas MVP dan MVP+ tetap (§105, §106). Keputusan DK-30 sampai DK-58 spec v3 masuk log keputusan di gelombang W9 (spec v3 bagian 16).

### Log keputusan
```

dengan:

```markdown
**Scope impact.** Tidak ada; batas MVP dan MVP+ tetap (§105, §106). Keputusan DK-30 sampai DK-58 spec v3 masuk log keputusan di gelombang W9 (spec v3 bagian 16).

### GR-05: Kata keadaan kesiapan, batas peserta, dan kecocokan untuk rencana (gelombang W4)

**Requirement changed.**

- Kata publik keadaan kesiapan §39 mengikuti DK-48 (spec v3 bagian 4): READY tampil "Persiapan kritis lengkap",
  NEEDS_PREPARATION "Masih perlu persiapan", dan NOT_RECOMMENDED "Tidak disarankan dengan rencana ini". Ketiga nilai
  enum dan syaratnya di §39 tidak berubah.
- Batas peserta satu rencana FR-08 (§35, amandemen K12) menjadi 1 sampai 12 orang, mengikuti spec v3 bagian 6.8;
  sebelumnya config mengizinkan 30.
- Dimensi Route Fit pada kesiapan (§38) menilai kecocokan untuk rencana: trip tanpa tujuan pendakian tersimpan memakai
  jenis dan tanggal tripnya sebagai tujuan sementara, sama dengan ruang kerja trip dan halaman penilaian kecocokan.
- Halaman kesiapan menyebut apa yang berubah sejak hasil cek terakhir yang disimpan, dari snapshot `readiness_checks`
  yang sudah ada.

**Reason.** Spec v3, disetujui pemilik 26 September 2026: DK-48 dan bagian 6.8 sampai 6.11. "Siap" terbaca sebagai
izin berangkat dan "Tidak direkomendasikan" sebagai penilaian atas orangnya, dan keduanya bertentangan dengan R-014
dan §40. "Masih perlu persiapan" sengaja berbeda dari label kecocokan "Perlu persiapan" (R-004).

**Impact.** Kata baru tampil di ruang kerja dan indeks trip, cek kesiapan, Beranda, dan pratinjau Mendaki. Rencana
lama dengan lebih dari 12 peserta harus dikurangi saat diubah. Trip lama tanpa tujuan yang direncanakan Tektok untuk
jalur bermalam kini tertahan syarat dasar dan terbaca "Tidak disarankan dengan rencana ini".

**Affected modules.** Readiness, Trip, Preparation, dan kata label di Beranda serta Mendaki. Tidak ada perubahan pada
Recommendation, Condition, maupun Administration.

**Security impact.** Tidak ada permukaan baru. Rute, Policy trip, dan validasi server tetap; status butir persiapan
tetap satu-satunya isian yang dibaca dari badan permintaan.

**Data impact.** Tidak ada perubahan skema dan tidak ada migrasi. `readiness_checks.computed_state` menyimpan nilai
enum, bukan kata, jadi baris lama tetap terbaca dengan kata baru.

**Scope impact.** Tidak ada fitur di luar spec v3 gelombang W4; batas MVP dan MVP+ tetap (§105, §106).

### Log keputusan
```

Tulis ulang `docs/fitur/04-perjalanan-persiapan.md` utuh:

```markdown
# 04 Perjalanan dan persiapan

**FR utama:** FR-03 Tujuan pendakian (§23), FR-08 Rencana perjalanan (§35), FR-09 Rencana persiapan (§36), FR-10 Model kesiapan (§38), FR-24 Pengingat perjalanan (§149).
**Bahan:** features/04-perjalanan-persiapan.md dari NgodingPakeAI (keluaran alat di root, diabaikan git), `docs/PRD.md`, spec v3 bagian 6.8 sampai 6.11.
**Diperiksa terhadap kode:** sesudah gelombang W4 (klaster Trip). Status adalah FAKTA dari berkas yang disebut (R-023).

## Tujuan dan pertanyaan pengguna

Pertanyaan pengguna: "Bagaimana saya memilih, merencanakan, dan bersiap?" (§145, tab Trip).

Pendaki menetapkan tanggal, peserta, dan catatan untuk satu jalur, lalu dituntun langkah demi langkah: persiapan, cek kesiapan, dan pengingat menjelang keberangkatan. Kesiapan adalah dukungan keputusan, bukan izin medis atau hukum (R-014, §40).

## Posisi di loop dan permukaan

- **Loop (§7):** Tujuan, lalu Rekomendasi, lalu rencana, Persiapan, dan Cek, sebelum Mendaki.
- **Tab:** Trip. Rute `/goals/create`, `/trips`, `/trips/create`, `/trips/{trip}`, `/trips/{trip}/edit`, `/trips/{trip}/preparation`, `/trips/{trip}/readiness`.
- **Langkah:** ruang kerja trip memakai empat langkah prototype (Jalur, Rencana, Persiapan, Cek); halaman Rencanakan, Persiapan, dan Cek membawa baris langkah yang sama.
- **Beranda:** kartu perjalanan berikutnya dan tenggat (W5, DK-45).

## Kriteria penerimaan

1. **Buat rencana.** Given jalur terbit, When pendaki memilih jenis trip, tanggal naik, jam mulai opsional, peserta 1 sampai 12, nama, dan catatan, Then rencana tersimpan; tanggal selesai sebelum tanggal mulai, rentang melebihi batas config, atau peserta di luar batas ditolak dengan pesan jelas.
2. **Kecocokan untuk rencana.** Given halaman Rencanakan, When jenis trip atau tanggal berubah, Then server menghitung ulang kecocokan untuk rencana itu lewat muat ulang parsial dan yang sampai ke layar hanya label, kata, dan alasannya, tanpa skor (R-005, BR-09).
3. **Langkah.** Given rencana ada, When ruang kerja trip dibuka, Then empat langkah menunjukkan yang sudah selesai dan yang berikutnya; langkah memandu, tidak mengunci.
4. **Indeks trip.** Given pendaki punya trip, When `/trips` dibuka, Then trip terdekat tampil sebagai kartu utama dengan langkah dan aksi berikutnya, lalu kelompok Berlangsung, Mendatang, dan Selesai (yang lengkap di Progres); hanya trip milik sendiri.
5. **Ubah dan hapus.** Given pemilik membuka rencananya, When mengubah atau menghapus, Then perubahan langsung terlihat; trip yang sudah berjalan menolak dengan alasan yang disebut sebelum pendaki mencoba; pendaki lain ditolak (§77).
6. **Persiapan.** Given daftar persiapan dibangkitkan dari templat jalur, When pendaki mencentang butir, Then centangnya langsung terlihat dengan Urungkan, keadaannya tersimpan, butir kritis ditandai, dan hitungan butir kritis terlihat; butir yang belum dicentang bukan berarti tidak dimiliki (BR-10).
7. **Butir yang menunggu.** Given prakiraan BMKG untuk tanggal trip belum terbit, When daftar persiapan dibuka, Then butir cuaca tampil "Menunggu BMKG: terbit mulai ..." dan server menolak konfirmasinya sebelum tanggal itu (R-013).
8. **Kesiapan.** Given cek kesiapan dibuka, When hasil tampil, Then keadaan kesiapan (DK-48) beserta kalimatnya, tiga hal yang diperiksa (kecocokan untuk rencana, persiapan dengan butir kritis yang kurang dan aksinya, kondisi terkini), dan hitungan "N dari M butir kritis dikonfirmasi", tanpa skor atau persen (R-005, R-014, K4).
9. **Sejak cek terakhir.** Given pendaki pernah menyimpan hasil cek, When halaman kesiapan dibuka lagi, Then perubahan status resmi, laporan komunitas baru, butir persiapan, dan label kecocokan disebut, termasuk yang tidak berubah.
10. **Konfirmasi pra-berangkat.** Given pendaki meninjau semuanya, When menekan "Saya sudah meninjau semuanya", Then konfirmasi dicatat dengan waktunya.
11. **Jendela izin.** Given jalur punya aturan izin, When halaman Rencanakan atau ruang kerja dibuka, Then jendela pemesanan relatif tanggal rencana ditulis (belum dibuka, terbuka sampai, sudah tutup) dengan tanggalnya dan tautan kanal resmi (§153); aturan tanpa jendela tercatat tidak ditulis "sedang dibuka".
12. **Pengingat.** Given trip belum dimulai, When pemilik menyalakan pengingat, Then surel dijadwalkan H-3 dan H-1 pukul 07:00 zona gunung, tidak pernah terkirim dua kali, dan tidak dikirim pada hari keberangkatan; ruang kerja dan halaman persiapan menampilkan setiap pengingat dengan waktu, zona, dan keadaan; isi surel tidak menyatakan jalur atau pendaki aman (R-012).
13. **Data pendukung kosong.** Given templat persiapan, status resmi, atau prakiraan belum ada, When bagiannya tampil, Then yang belum dinilai disebut jujur, bukan disembunyikan atau dikarang.
14. **Tujuan pendakian.** Given pendaki mengisi tujuan (tanggal, wilayah, jenis trip, durasi, tantangan), When rekomendasi atau rencana dibuat, Then tujuan itu menjadi masukan dan isian awalnya (FR-03).

## Aturan yang berlaku

- **Kanonis:** R-002, R-004, R-005, R-006, R-007, R-012, R-013, R-014.
- **Aturan bisnis:** BR-04, BR-05, BR-09, BR-10, BR-11.
- **PRD:** §23, §35, §36, §37, §38, §39, §40, §98 (penjadwal), §102 (matriks test kesiapan), §115 (kriteria penerimaan persiapan), §149, §153, §154 (penjadwal di Vercel).

## Data dan sumber

| Tabel atau config | Kolom atau kunci | Asal |
|---|---|---|
| `trip_plans` | `user_id`, `trail_id`, `hiking_goal_id`, `name`, `planned_date`, `end_date`, `start_time`, `participants_count`, `trip_type`, `notes`, `status`, `reminders_enabled`, `completed_at` | `create_trip_plans_table`, `add_plan_details_to_trip_plans_table` (kemungkinan belum dijalankan di Supabase, INFERENCE, menunggu Q22) |
| `preparation_templates`, `preparation_items` | templat per jalur: `category`, `label`, `description`, `is_critical`, `sort_order`, `applies_when` | `create_preparation_templates_table`, `create_preparation_items_table` |
| `trip_preparation_items` | `category`, `label`, `is_critical`, `status`, `status_updated_at` | `create_trip_preparation_items_table` |
| `readiness_checks` | `computed_state`, `route_fit_snapshot`, `preparation_state`, `official_status_snapshot`, `condition_snapshot`, `explanation`, `pre_departure_confirmed`, `computed_at`; snapshot terakhir menjadi pembanding "Sejak cek terakhir" | `create_readiness_checks_table` |
| `trip_reminders` | `jenis` (H-3, H-1), `dijadwalkan_untuk`, `sent_at`, `skipped_at` | `create_trip_reminders_table` (kemungkinan belum dijalankan di Supabase, INFERENCE, menunggu Q22) |
| `hiking_goals` | `target_date`, `region`, `trip_type`, `expected_duration_minutes`, `preferred_challenge`, `max_elevation_gain_m`, `notes` | `create_hiking_goals_table` |
| `permit_requirements` | jendela pemesanan, kuota tercatat, dan tautan resmi | `create_permit_requirements_table` |
| `scheduled_task_runs` | pemantauan `trips:kirim-pengingat` | `create_scheduled_task_runs_table` |
| `config/hiking.php` | `trips.max_participants` (12), `trips.max_span_days`, `conditions.weather_forecast_days`, `reminders.days_before`, `reminders.local_hour`, `rate_limits.personal_writes_per_minute` | config |

## State

| Keadaan | Perilaku yang dituntut | Keadaan sekarang |
|---|---|---|
| Belum ada trip | Kalimat yang menyebut dari mana memulai dan satu aksi utama | Ada: `EmptyState` "Belum ada trip" dengan "Rencanakan trip" di `resources/js/pages/Trips/Index.tsx` |
| Rencanakan tanpa jalur | Pilihan dari simpanan sendiri atau jalan ke Jelajah | Ada: `pilihanJalur` dari `app/Services/Trips/RencanaTrip.php`; tanpa simpanan, `EmptyState` "Belum ada jalur tersimpan" |
| Pendaki tanpa profil | Kecocokan "Belum dinilai" dengan alasannya | Ada: `RencanaTrip`, `HalamanKesiapan` |
| Trip tidak dapat diubah atau dihapus | Alasan disebut sebelum mencoba | Ada: `kelola` di `TripController::show()`, `resources/js/components/trips/rencana/HapusRencana.tsx` |
| Prakiraan belum terbit | Butir cuaca menunggu BMKG dengan tanggal terbitnya; cek kesiapan menulis "Belum terbit" | Ada: `PreparationService::terbitPrakiraan()`, `HalamanPersiapan`, `HalamanKesiapan` |
| Cuaca gagal diambil | Pesan kegagalan, tidak ada cuaca karangan | Ada: `HalamanKesiapan::prakiraan()` |
| Waktu pengingat sudah lewat | Kalimat jujur | Ada: `pengingat.catatan` dari `TripReminderScheduler::untukHalaman()` |
| Trip sudah dimulai | Sakelar pengingat tidak ditawarkan | Ada (W0): `resources/js/components/trips/PengingatTrip.tsx` |
| Kesiapan basi | Hasil lama ditandai | Ada: `tests/Feature/StaleReadinessTest.php` |
| Jaringan putus saat mencentang | Centang kembali ke keadaan tersimpan, pesan jaringan global | Ada: keadaan sementara dibuang di `onFinish` pada `resources/js/pages/Trips/Preparation.tsx` |
| Galat validasi rencana | Pesan per isian, terikat ke kontrolnya | Ada: `StoreTripRequest`, `UpdateTripRequest`; `penjelas` pada `KontrolSegmen` dan `Stepper` |

## Status implementasi

| Sub-fitur | Status | Bukti |
|---|---|---|
| Indeks trip | Ada (W4) | `app/Services/Trips/IndeksTrip.php`, `app/Http/Resources/TripRowResource.php`, `resources/js/pages/Trips/Index.tsx`, `resources/js/components/trips/KartuTrip.tsx`, `tests/Feature/TripIndexPageTest.php` |
| Rencanakan trip | Ada (W4) | `app/Services/Trips/RencanaTrip.php`, `resources/js/pages/Trips/Create.tsx`, `resources/js/components/trips/rencana/KotakIzin.tsx`, `tests/Feature/TripPlanFitReloadTest.php`, `tests/Feature/TripPermitWindowTest.php`, `tests/Feature/TripPlanDetailTest.php` |
| Ruang kerja dan langkah | Ada (W4) | `app/Services/Trips/TripSteps.php`, `app/Services/Trips/KepalaTrip.php`, `resources/js/pages/Trips/Show.tsx`, `resources/js/components/trips/LangkahTrip.tsx`, `resources/js/components/trips/LangkahRingkas.tsx`, `tests/Feature/TripWorkspaceTest.php` |
| Ubah dan hapus rencana | Ada | `app/Services/Trips/TripLifecycle.php`, `resources/js/pages/Trips/Edit.tsx`, `tests/Feature/TripPlanDetailTest.php`, `tests/Feature/TripOwnershipTest.php` |
| Persiapan | Ada (W4) | `app/Services/Trips/HalamanPersiapan.php`, `app/Services/PreparationService.php`, `app/Http/Controllers/Trips/PreparationController.php`, `resources/js/pages/Trips/Preparation.tsx`, `tests/Feature/PreparationUndoTest.php`, `tests/Unit/PreparationServiceTest.php`; isi awal dari perlengkapan pribadi menyusul W7 |
| Cek kesiapan | Ada (W4, label DK-48) | `app/Services/Trips/HalamanKesiapan.php`, `app/Http/Controllers/Trips/ReadinessController.php`, `app/Services/ReadinessService.php`, `app/Enums/ReadinessState.php`, `resources/js/pages/Trips/Readiness.tsx`, `tests/Feature/ReadinessDashboardTest.php`, `tests/Feature/ReadinessSinceLastCheckTest.php`, `tests/Feature/StaleReadinessTest.php` |
| Jendela izin | Ada | `app/Services/PermitService.php`, `RencanaTrip::izin()`, `tests/Feature/TripPermitWindowTest.php` |
| Pengingat perjalanan (FR-24) | Ada | `app/Services/Trips/TripReminderScheduler.php`, `app/Console/Commands/SendTripReminders.php` (tiap jam di `routes/console.php`), `app/Notifications/TripReminderNotification.php`, `app/Http/Controllers/Trips/TripReminderController.php`, `PengingatTrip.tsx`, `tests/Feature/TripReminderTest.php`; dipantau `SchedulerHealthService::TUGAS` (W0). Syarat produksi: penjadwal benar-benar berjalan dan driver surel nyata (K11, K24) |
| Tujuan pendakian (FR-03) | Ada | `app/Http/Controllers/Goals/GoalController.php`, `app/Http/Requests/StoreGoalRequest.php`, `resources/js/pages/Goals/Create.tsx`, `tests/Feature/GoalInputIsUsedTest.php` |

## Task NgodingPakeAI

| # | Task | Peta ke kode | Status kode |
|---|---|---|---|
| 1 | Bangun halaman utama rencana perjalanan | `resources/js/pages/Trips/Index.tsx`, `Show.tsx` | Ada |
| 2 | Buat form rencana tiket dengan mock | `resources/js/pages/Trips/Create.tsx` (data nyata; "tiket" dibaca sebagai rencana trip) | Ada |
| 3 | Validasi tanggal rencana mulai selesai | `ValidatesTripPlanDetails` | Ada |
| 4 | Tampilkan rencana sebagai langkah berurutan | `TripSteps`, `LangkahTrip.tsx`, `LangkahRingkas.tsx` | Ada |
| 5 | Buat tampilan checklist perlengkapan mock | `resources/js/pages/Trips/Preparation.tsx` | Ada |
| 6 | Interaksi centang perlengkapan dan hitung sisa | Rute `trips.preparation.update`, centang optimis dengan Urungkan | Ada |
| 7 | Bangun halaman skor kesiapan diri | `resources/js/pages/Trips/Readiness.tsx` | Berbeda: keadaan dan faktor, bukan skor (K4) |
| 8 | Rincian faktor kesiapan dan keterangan jujur | `app/Services/Trips/HalamanKesiapan.php` | Ada |
| 9 | Buat halaman pengingat perjalanan mock | `PengingatTrip.tsx` di ruang kerja dan persiapan, bukan halaman terpisah | Ada (W0) |
| 10 | Toggle pengingat dan daftar status | Rute `trips.reminders.update`, `PengingatTrip.tsx` | Ada (W0) |
| 11 | Buat tabel rencana perjalanan dan migrasi | `create_trip_plans_table`, `add_plan_details_to_trip_plans_table` | Ada |
| 12 | API CRUD rencana dan validasi tanggal | Rute `trips.*`, `TripLifecycle` | Ada |
| 13 | Skema tabel perlengkapan dan status siap | `create_trip_preparation_items_table` | Ada |
| 14 | API checklist perlengkapan perjalanan | `PreparationController` | Ada |
| 15 | Skema data kebugaran dan pengalaman pengguna | `create_user_experience_table` | Berbeda: pengalaman diisi sendiri; kebugaran tidak dinilai (K4) |
| 16 | Service hitung skor kesiapan dan faktor | `ReadinessService`, `ReadinessAssessment` | Berbeda: keadaan, bukan skor |
| 17 | API pengingat dan jadwal keberangkatan | `TripReminderScheduler::jadwal()` dan `untukHalaman()` | Ada |
| 18 | Job pengirim pengingat email dan notifikasi | `SendTripReminders` (surel sinkron) | Ada untuk surel; notifikasi dorong menunggu keputusan pemilik (spec v3 bagian 15 Q6) |
| 19 | Penanganan data pendukung belum tersedia | Kalimat jujur di `HalamanPersiapan`, `HalamanKesiapan`, dan `RencanaTrip` | Ada |

## Keputusan dan konflik terkait

- **DK-07** FR-24 dan detail rencana (tanggal selesai, peserta) masuk cakupan; **DK-45** Beranda berfase; **DK-48** label kesiapan "Persiapan kritis lengkap", "Masih perlu persiapan", "Tidak disarankan dengan rencana ini" (W4, governance record GR-05 di `docs/PRD.md` §146); **DK-50** alasan pembatalan (W7).
- **K4** keadaan kesiapan dan faktor, bukan skor kebugaran; **K11** pengingat butuh driver surel dan penjadwal produksi; **K12** peserta dan tanggal selesai sebagai kolom aditif, peserta 1 sampai 12 sejak W4; **K24** penjadwal di Vercel lewat Cron yang memanggil endpoint terautentikasi.
```

- [ ] **Step 2: Periksa dokumen.**

Run: `php artisan test --compact --filter='DesignDirection|PrdReferenceIntegrity|TeamDocsIndex|SkillGovernance|RepoHygiene'`
Expected: PASS. Pada simulasi rencana: 35 test lulus.

Run: `LC_ALL=C grep -rlP '\xE2\x80\x94' DESIGN.md docs/ARCHITECTURE.md docs/PRD.md docs/fitur/04-perjalanan-persiapan.md`
Expected: tanpa keluaran.

- [ ] **Step 3: Suite.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 1.811 test, 1.809 lulus, 2 dilewati.

- [ ] **Step 4: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 8: Verifikasi gelombang (koordinator)

Ruling W4-R35; "Selesai bila" induk W4. Dikerjakan koordinator sesudah Tugas 1 sampai 7 selesai dan lolos tinjauan
per tugas. Setiap klaim di laporan disertai keluaran perintah, jalur berkas, atau tangkapan (R-023). Angka "pada
simulasi rencana" adalah hasil menjalankan kode rencana ini di salinan repo `ac88231`, bukan hasil di repo, dan
menjadi pembanding; selisih dari W3 Tugas 10 sampai 12 dan gelombang perbaikan akhir W3 ditambahkan koordinator.
Harness pratinjau memakai port 8100 milik koordinator.

**Files:**
- Create: `docs/desain/tangkapan/w4/*.webp` (tangkapan), `docs/desain/tangkapan/w4/README.md` (perbandingan)
- Modify: `HANDOVER.md` (bagian 0: baris "Terakhir diperbarui" dan subbagian W4), `.superpowers/sdd/2026-09-26-memount-v3-program.md` (baris W4; berkas ini diabaikan git)

**Interfaces:**
- Consumes: seluruh keluaran Tugas 1 sampai 7; harness pratinjau (diperluas Tugas 1, 3, 4, 6); gambar acuan `docs/desain/prototype/acuan/08-rencanakan.png`, `09-persiapan.png`, `10-cek-kesiapan.png`.
- Produces: bukti akhir W4, daftar izin akhir yang diperbarui, dan titik lanjut W5.

- [ ] **Step 1: Catat keadaan kerja.**

Run: `git status --short | wc -l; git diff --stat | tail -1`
Expected: selisih terhadap catatan akhir W3 cocok dengan gabungan daftar Files Tugas 1 sampai 7. Pada simulasi rencana, Tugas 1 sampai 7 menambah 14 berkas, mengubah 35, dan menghapus 1 (`app/Http/Resources/TripReadinessResource.php`) terhadap keadaan sesudah W3 Tugas 9.

- [ ] **Step 2: Suite penuh, tipe, format.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 1.811 test, 1.809 lulus, 2 dilewati.

Run: `php artisan test --compact`
Expected: 0 gagal. Pada simulasi rencana: 1.838 test, 1.809 lulus, 29 dilewati (27 test Spatial tanpa `SPATIAL_TEST_DSN`).

Run: `npx tsc --noEmit`
Expected: keluar tanpa keluaran galat.

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

- [ ] **Step 3: Build dan ukuran bundel.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`
Expected: `✓ built` dengan peringatan ukuran potongan maplibre-gl yang sama seperti W3. Catat ukuran `app-*.js` dan
potongan halaman Trip. Pada simulasi rencana: `app-*.js` 355,67 kB menjadi 355,86 kB (gzip 111,78 menjadi 111,88 kB), `app-*.css` 65,57 kB menjadi 68,25 kB (gzip 12,82 menjadi 13,39 kB), dan total JavaScript 105 berkas 1.589,81 kB menjadi 109 berkas 1.610,05 kB. Potongan halaman Trip menurut `public/build/manifest.json`: `Trips/Index` 3,57 kB, `Trips/Show` 7,61 kB, `Trips/Create` 9,97 kB, `Trips/Preparation` 5,88 kB, `Trips/Readiness` 10,37 kB.

- [ ] **Step 4: Suite pratinjau dan server statis.**

Run: `php artisan test --compact --testsuite=Pratinjau`
Expected: PASS. Pada simulasi rencana: 18 test lulus. W4 menambah `test_ruang_kerja`, `test_rencanakan`, `test_persiapan`, dan `test_cek_kesiapan`.

Run (di latar belakang): `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php -S 127.0.0.1:8100 -t public tests/Pratinjau/statis.php`
Expected: server statis tanpa basis data. Buka `http://127.0.0.1:8100/_pratinjau/<nama>.html`, dan hentikan server
sesudah Step 8.

- [ ] **Step 5: Tangkapan.** Pakai Chrome lewat MCP chrome-devtools. Ukuran 390 x 844 memakai emulasi perangkat
seluler dengan sentuh dan rasio piksel 1, ukuran 1440 x 900 rasio piksel 1, dan tema terang serta gelap. Simpan sebagai
WebP di `docs/desain/tangkapan/w4/`, masing-masing 390 dan 1440, terang dan gelap:

- indeks trip (`trip.html`, tanpa acuan: bandingkan susunannya dengan 09);
- ruang kerja (`ruang-kerja.html`, tanpa acuan; 1440 menampilkan dua kolom);
- Rencanakan (`rencanakan.html`, acuan 08);
- Persiapan (`persiapan.html`, acuan 09);
- Cek kesiapan (`cek-kesiapan.html`, acuan 10).

- [ ] **Step 6: Perbandingan tertulis.** Tulis `docs/desain/tangkapan/w4/README.md` dengan susunan README tangkapan W3:
paragraf cara pengambilan, satu paragraf cakupan W4, dan tabel
`| Tangkapan | Gambar acuan | Yang sudah sesuai | Yang belum sesuai dan gelombang yang mengubahnya |`. Pemeriksaan
perencanaan menemukan hal-hal di bawah ini. Pastikan masing-masing pada tangkapan, jangan disalin tanpa melihat.

- **Sudah sesuai.**
  - Rencanakan 390 px (acuan 08): baris langkah dengan Rencana menyala, kartu jalur dengan "Ganti", angka padat,
    label kecocokan "untuk rencana ini" dengan alasannya di kotak, kontrol segmen tiga jenis dengan lamanya, rentang
    tanggal, kotak izin "Pemesanan izin terbuka sampai ..." dengan pengelola dan H, kalimat terbit prakiraan BMKG, zona
    gunung pada jam, stepper peserta "Termasuk Anda", kalimat kuota, nama, catatan, sakelar "Ingatkan saya", dan bar
    aksi "Simpan rencana".
  - Persiapan 390 px (acuan 09): kepala trip, baris langkah, keadaan persiapan dengan ruas per butir kritis dan nama
    yang belum, kalimat BR-10, kelompok dengan hitungan, lencana Kritis, butir cuaca "Menunggu BMKG", tautan izin, dan
    daftar pengingat.
  - Cek kesiapan 390 px (acuan 10): "Keadaan kesiapan" dengan kata DK-48 dan kalimat R-014, tiga hal bernomor, butir
    kurang dengan aksi "Konfirmasi" atau "Pesan", status resmi bersumber dengan kalimat R-012, "Prakiraan untuk ..."
    dengan "Belum terbit", laporan komunitas bergaris putus-putus, "Sejak cek terakhir" dengan tanda = dan +, kotak
    "Di hari keberangkatan", serta "Selesaikan N butir" dan "Saya sudah meninjau semuanya".
- **Belum sesuai.**
  - Rencanakan: kalender grid prototype diganti isian tanggal native (W4-R9); chip jam diganti isian jam (W4-R10);
    pengingat mati secara bawaan (W4-R13); kotak izin tanpa ikon kalender (W4-R18).
  - Persiapan: butir status resmi tanpa status hidup (W4-R22); butir menunggu memakai kotak putus-putus tanpa glyph jam
    (W4-R18); tombol "Tidak berlaku" di setiap baris (W4-R19).
  - Cek kesiapan: kartu gunung api menunggu W8 (W4-R29); daftar alasan mesin memakai kosakata mesin (OPEN ISSUE).
  - Indeks trip: data contoh tanpa butir persiapan, jadi langkah berikutnya menyebut "Belum ada item persiapan."
  - Jarak atas halaman `py-6`, sedangkan acuan sekitar 16 px (catatan yang sama dengan W2 dan W3).

- [ ] **Step 7: Ukur lapisan dan reflow.** Dengan `evaluate_script` di pratinjau:

1. `rencanakan.html` di 360 x 740 dan 320 x 568: tinggi `section[aria-label="Aksi rencana trip"] .chrome`. Pada simulasi
   rencana 106 px di keduanya, tanpa gulir mendatar.
2. Baris langkah `nav[aria-label="Langkah trip"] li` di 320 px: empat item 44 px dalam satu baris.
3. `trip.html`, `ruang-kerja.html`, `persiapan.html`, dan `cek-kesiapan.html` di 320 x 568: `scrollWidth` sama dengan
   `clientWidth`.
4. `persiapan.html` di 390 x 844: centang satu butir dengan papan ketik; toast Urungkan berada di atas bilah tab dan
   tidak menutup butir yang baru dicentang.

- [ ] **Step 8: Jalur papan ketik** (e2e menunggu Playwright, Q17). Pada pratinjau, tanpa tetikus:

1. **390 px, Rencanakan.** Tab dari awal: tautan lewati, kembali ke Trip, baris langkah (Jalur sebagai tautan),
   "Ganti", lalu grup jenis trip; panah kiri dan kanan berpindah jenis (kecocokan dimuat ulang, kalimat "Menghitung
   ulang untuk pilihan baru" terdengar), tanggal naik, jam, tombol kurangi dan tambah peserta (di batas, tombol tetap
   terfokus dengan `aria-disabled`), nama, catatan, sakelar (Spasi menyalakan), lalu "Simpan rencana" di bar aksi tanpa
   tertutup lapisan mana pun.
2. **390 px, Persiapan.** Spasi mencentang butir pertama; fokus pindah ke Urungkan di toast; Enter pada Urungkan
   mengembalikan centangnya. Tab mencapai "Tidak berlaku" di baris yang sama.
3. **390 px, Cek kesiapan.** Tab mencapai "Lihat penilaian lengkap", setiap "Konfirmasi" atau "Pesan", lalu "Selesaikan
   N butir" dan "Saya sudah meninjau semuanya" dengan cincin fokus terlihat.
4. **1440 px, ruang kerja.** Tab berurutan dari kepala trip ("Ubah rencana") ke kolom langkah, lalu kolom isi.

Simpan satu tangkapan fokus di Urungkan Persiapan sebagai `fokus-urungkan-390-light.webp`. Hentikan server port 8100.

- [ ] **Step 9: Delivery Gate antislop, empat blok.** Tulis laporan PASS/FAIL, satu baris per butir, dan setiap PASS
disertai bukti:

- **Salinan:** R-02 tanpa em dash. Run: `LC_ALL=C grep -rlP '\xE2\x80\x94' app resources tests database DESIGN.md docs/ARCHITECTURE.md docs/PRD.md docs/fitur`. Expected: tanpa keluaran. Teks berbahasa Indonesia dengan "Anda", tanpa "aman" atau "layak berangkat" sebagai klaim, dan setiap keadaan kosong menyebut siapa yang ditunggu.
- **Desain:** satu titik fokus per layar (tombol penuh hanya kartu utama indeks dan aksi utama Cek), hierarki lewat bobot, tanpa kartu di dalam kartu selain kotak kecocokan di kartu jalur Rencanakan, motif garis rute dan cincin pos di langkah.
- **Interaksi:** R-26 setiap kendali berfungsi (Rencanakan trip, Ganti, jenis trip, tanggal, stepper, sakelar, Simpan rencana, centang, Urungkan, Tidak berlaku, Perbarui daftar, Lanjut ke cek kesiapan, Konfirmasi, Pesan, Simpan hasil cek ini, Saya sudah meninjau semuanya, Selesaikan N butir); R-27 keadaan pertama kali, menunggu, memuat, dan galat; R-32 jalur papan ketik Step 8.
- **Kebenaran:** tanpa skor, persen, atau bobot angka; kata DK-48; "Siap berangkat" tidak tampil di kepala atau kartu trip yang belum dimulai; status bersumber dan bertanggal dengan kalimat R-012; prakiraan untuk tanggal trip atau "Belum terbit"; R-35 build dijalankan dan kendali diklik.

- [ ] **Step 10: HANDOVER, ledger, dan daftar izin akhir.** Di `HANDOVER.md` bagian 0, perbarui baris "Terakhir
diperbarui" dan tambahkan subbagian "W4 Klaster Trip (selesai <tanggal>, menunggu izin commit)" dengan susunan
subbagian W3:

- rujukan rencana ini, jumlah tugas, dan letak ledger;
- tabel `| Butir | Nilai | Bukti |` berisi suite Unit dan Feature, suite bawaan, tsc, Pint, build dan selisih bundel,
  suite Pratinjau, anggaran kueri per halaman, tangkapan, pengukuran lapisan, dan jalur papan ketik;
- ruling koordinator atas Konflik 1 sampai 5;
- OPEN ISSUE: kosakata mesin kesiapan, "Pre-departure check", "Siap berangkat" di luar W4, data contoh tanpa butir
  persiapan;
- daftar izin akhir: tanpa migrasi W4; catat keputusan yang tercatat di GR-05 (kata DK-48, peserta 1 sampai 12,
  kecocokan untuk rencana pada kesiapan) sebagai informasi untuk pemilik;
- titik lanjut W5.

Perbarui baris W4 di `.superpowers/sdd/2026-09-26-memount-v3-program.md`.

- [ ] **Step 11: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

## Tinjauan diri

### Cakupan induk W4 dan titipan

| Butir | Tugas | Bukti di rencana |
|---|---|---|
| Butir 1: indeks trip (kelas `IndeksTrip`, kartu trip terdekat dengan fase dan aksi berikutnya, kelompok Berlangsung, Mendatang, Selesai, keadaan kosong spec bagian 6.9; hanya milik sendiri, pengelompokan, aksi per fase, anggaran kueri) | Tugas 2 | `TripIndexPageTest` (9 test) |
| Butir 2: Rencanakan (kecocokan dihitung server lewat muat ulang parsial, jendela izin dari `permit_requirements`, kalimat terbit prakiraan BMKG, jam dalam zona gunung, peserta 1 sampai 12, kuota belum terverifikasi, pengingat H-3 dan H-1, ringkasan tetap, jalur Tutup dengan peringatan; props parsial hanya label dan alasan tanpa skor; `TripPermitWindowTest` diperluas; validasi peserta) | Tugas 3 | `TripPlanFitReloadTest` (12 test), `TripPermitWindowTest` (+2), `TripPlanDetailTest` (1 sampai 12) |
| Butir 3: ruang kerja dan persiapan (empat langkah dari `TripSteps`, hitungan N dari M butir kritis, kelompok `PreparationCategory` dengan tanda Kritis, butir menunggu BMKG, centang optimis dengan Urungkan, daftar pengingat; `PreparationServiceTest` diperluas, `PreparationUndoTest`) | Tugas 1, Tugas 4 | `TripWorkspaceTest` (9 test), `PreparationUndoTest` (7 test), `PreparationServiceTest` (+2) |
| Butir 4: cek kesiapan (label DK-48, kalimat R-014, tiga hal, "Sejak cek terakhir" dari snapshot `readiness_checks`, pesan hari keberangkatan, "Saya sudah meninjau semuanya" mencatat `pre_departure_confirmed`; `ReadinessDashboardTest` ke DK-48, `ReadinessSinceLastCheckTest`, `StaleReadinessTest`; catatan tata kelola §146) | Tugas 5, Tugas 6, Tugas 7 | `ReadinessDashboardTest` (+3), `ReadinessSinceLastCheckTest` (3 test), `StaleReadinessTest` (diperbarui Tugas 2), GR-05 |
| Selesai bila: aturan penutup gelombang untuk lima layar (indeks, Rencanakan, ruang kerja, Persiapan, Cek) | Tugas 8 | Step 2 sampai 10 |
| Titipan W3-S5: kalimat R-012 di `Trips/Readiness.tsx` | Tugas 6 | `ReadinessDashboardTest::test_the_page_shows_the_three_checks_with_their_next_actions` |
| Ruling W3-C2 (Banding milik tab Trip), W3-C6 (403 untuk trip orang lain), W3-6a (status mendatang PLANNED dan READY_FOR_DEPARTURE), W3-6b (prakiraan per tanggal trip), W3-4b (SUDAH_ADA) | Tugas 1 sampai 6 | Tidak diubah: tautan "Lihat penilaian lengkap" memakai `?trip=` yang menjawab 403 untuk trip orang lain; status mendatang mengikuti `TripStatus::isActive()` kecuali `IN_PROGRESS`; prakiraan Cek disaring per tanggal lokal gunung seperti W3-6b |
| Brief butir 4: anggaran kueri, akses tamu dan pengguna lain, keadaan kosong, UNKNOWN, tanpa skor, R-014, R-012, R-013 | Tugas 1 sampai 6 | Review Focus |
| Brief butir 5: migrasi | Tidak ada | W4-R31 |

### Pemindaian placeholder

Tidak ada "TBD", "TODO", "implement later", atau "similar to Task N". Setiap langkah kode memuat kodenya: berkas baru
ditulis utuh, tulis ulang memuat isi utuh, dan perubahan kecil memakai blok "Ganti ... dengan ...". Blok-blok itu
dirakit dari rantai simulasi dan diperiksa dua hal: teks lamanya muncul tepat sekali pada keadaan sebelum blok itu,
dan menerapkan semua blok berurutan menghasilkan berkas akhir tugas persis. Konteks blok tidak diawali atau diakhiri
baris kosong. Sesudah dirakit, teks rencana ini diputar ulang dari awal pada dasar `ac88231` seperti implementer
membacanya, dan setiap berkas sama dengan keadaan akhir tugasnya (Bukti simulasi).

### Konsistensi nama dan tipe

- **Ruang kerja (Tugas 1):** `KepalaTrip::untuk()`, `hariMenuju()`, `rentang()`; `TripType::labelPendek()`,
  `lamaHari()`; `TripSteps::for()`, `aksi()`, `hitungPersiapan()`, `teksPersiapan()`, `teksRencana()`; props
  `kepala`, `penilaian`; `KepalaTripData`; `KepalaTrip({ kepala })`, `LangkahTrip({ langkah })`.
- **Indeks (Tugas 2):** `IndeksTrip::props()`, `IndeksTrip::BATAS_SELESAI`; `TripRowResource`; `BarisTrip`,
  `KelompokTrip`; `KartuTrip({ kartu, utama })`.
- **Rencanakan (Tugas 3):** `RencanaTrip::props()`; props `jalur`, `pilihanJalur`, `awal`, `jenisTrip`, `zona`,
  `kecocokan`, `jadwal` (`ringkas`), `izin`; `LangkahRingkas({ kini, tautan })` dengan `KunciLangkah`;
  `KotakIzin({ izin })` dengan `IzinRencana`; prop `penjelas`.
- **Persiapan (Tugas 4):** `PreparationService::kritis()`, `terbitPrakiraan()`, `penolakanKonfirmasi()`;
  `HalamanPersiapan::props()`; props `tautanLangkah`, `kritis`, `kategori`.
- **DK-48 (Tugas 5):** `ReadinessState::label()`.
- **Cek kesiapan (Tugas 6):** `HalamanKesiapan::props()`; props `keadaan`, `kecocokan`, `persiapan`, `kondisi`,
  `sejakTerakhir`, `hariKeberangkatan`; jangkar `#butir-{id}`.

tsc lulus sesudah setiap tugas pada simulasi; tidak ada rute baru, jadi rute bertipe tidak perlu dibangkitkan ulang.

### Review Focus

Kelima baris Review Focus punya test di tugas pemilik kodenya: objek milik pengguna lain (Tugas 1, 2, 4), kesiapan
yang terbaca jaminan atau skor (Tugas 1, 2, 3, 5, 6), data yang belum ada (Tugas 2, 3, 4, 6), Urungkan dan keadaan
yang berubah (Tugas 2, 4, 6), masukan URL dan isian yang tidak sah (Tugas 1, 3). Semua test itu lulus pada simulasi.

### Pemeriksaan teks rencana

- Rencana ini tanpa em dash. Tanda "§" hanya muncul saat mengutip ID PRD; dokumen lain dirujuk "bagian N".
- Teks antarmuka baru berbahasa Indonesia dengan sapaan "Anda".
- Tidak ada klaim "aman" atau "layak berangkat"; kata itu hanya muncul saat mengutip aturan yang melarangnya.
- Label kecocokan publik tidak berubah; kata kesiapan publik mengikuti DK-48.

## Lampiran A: Aturan eksekusi W4

Salin lampiran ini ke `.superpowers/sdd/<folder ledger W4>/aturan-eksekusi.md` sebelum Tugas 1. Isinya aturan
eksekusi W3 dengan nama gelombang, rencana, dan port yang diperbarui.

### Aturan eksekusi W4 (dibaca setiap implementer sebelum mulai)

Proyek: Memount, Laravel 13.31 + Inertia 3 + React 19.3 + TypeScript 5.9, PHP 8.5.10, di Windows dengan Git Bash.
Root repo: `E:/praktik_aplikasi_web`. Branch `hardening`. Commit terakhir `ddc27e8`; pohon kerja memuat perubahan yang
belum di-commit dari W1, W2, W3, dan gelombang ini. Folder `.agents/skills/` yang belum dilacak (keputusan Q19) jangan
disentuh.

#### Larangan mutlak (arahan pemilik dan kontrak proyek)

1. **Jangan commit, push, `git stash`, `git checkout -- <berkas>`, `git reset`, atau `git clean`.** Pemilik
   memutuskan tidak ada commit sampai seluruh program W1 sampai W9 selesai. Lewati setiap langkah "Commit"; catat
   berkas yang berubah di laporan.
2. **Jangan menyentuh Supabase dan jangan membaca atau mencetak isi `.env`.** `.env` menunjuk basis data produksi.
3. **Jangan menjalankan `php artisan migrate` dalam bentuk apa pun, `db:seed`, `db:wipe`, tinker (termasuk
   `tinker --execute`), atau `php artisan memount:demo`.** Migrasi dan seeder hanya berjalan di dalam PHPUnit.
4. **Setiap perintah Artisan yang mem-boot aplikasi di luar PHPUnit, `npm run build`, dan server statis `php -S`
   wajib diberi awalan** `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL=`. `php artisan test ...` tidak butuh awalan.
5. **Jangan menambah atau mengubah dependensi** (composer, npm), jangan mengubah versi apa pun, dan jangan menambah
   ikon ke registri Phosphor. W4 tidak butuh unduhan apa pun. Jangan memasang alat.
6. **Jangan menyunting berkas di luar daftar "Files" tugas Anda**, kecuali brief atau dispatch menyebutnya. Bila perlu
   menyentuh berkas lain, berhenti dan laporkan NEEDS_CONTEXT.
7. **Blok `<laravel-boost-guidelines>` di `AGENTS.md` dan `CLAUDE.md` serta blok NgodingPakeAI di `AGENTS.md` tidak
   pernah disunting.** `HANDOVER.md` dan `CLAUDE.md` hanya disunting koordinator.
8. **Jangan menjalankan subagen.**
9. **Server `php artisan serve` di 127.0.0.1:8000 milik pemilik (memakai Supabase).** Jangan dipakai, dihentikan, atau
   dipanggil. Port 8100 milik koordinator (harness pratinjau), dan port 8130 sampai 8149 milik penulis rencana.
   Implementer W4 tidak menyalakan server apa pun.

#### Hook GateGuard

Lingkungan ini memasang hook "Fact-Forcing Gate". Perintah Bash pertama dan suntingan pertama setiap berkas bisa
ditolak dengan permintaan fakta. Bila itu terjadi, tulis faktanya di balasan Anda lalu ulangi panggilan yang sama:
- ringkasan permintaan: "Menjalankan Tugas N rencana W4 (docs/superpowers/plans/2026-10-06-memount-v3-w4-trip.md) atas arahan pemilik";
- untuk Bash: apa yang diverifikasi atau dihasilkan perintah itu;
- untuk suntingan: berkas yang mengimpor atau merujuk berkas itu (cari dengan Grep), fungsi atau kelas publik yang terdampak, bentuk data bila ada;
- untuk berkas baru: berkas yang akan memanggilnya, bukti tidak ada berkas lain dengan tujuan sama, dan bentuk data;
- untuk penghapusan atau pemindahan: jalur yang terdampak dan cara mengembalikannya;
- instruksi pemilik verbatim: "ya, selesaikan semuanya baru minta izin commit dan integrasi nya jika belum selesai semua jangan minta ke saya, jika anda meminta perizinan artinya semua tugas anda telah selesai. lanjutkan!"

#### Konvensi kerangka dari W2 (ruling W2-5l, W2-5e, W2-F6)

- Komponen yang hidup di kerangka persisten memanggil `useKerangka()` untuk toast. Toast tertutup saat pemiliknya
  dilepas.
- Aksi yang menawarkan Urungkan memakai `preserveState: true`.
- Konfirmasi sesudah navigasi datang dari flash halaman tujuan, dan urungkannya dikerjakan di server.
- Dialog yang mengirim formulir menangani `onNetworkError` di dalam dialog sebagai galat sebaris.
- Muat ulang penuh karena versi aset baru hanya lewat `muatPenuhVersiBaru()` atau `muatUlangMilikKerangka()` di
  `resources/js/layouts/Kerangka.tsx`. Jangan memanggil `window.location.reload()` sendiri. Muat ulang parsial milik
  halaman (`router.reload` di Rencanakan) tetap memanggil `router.reload()`.
- Lapisan tetap baru di tepi bawah menulis tingginya ke variabel CSS lewat ResizeObserver, dengan pola
  `StripPendakian.tsx`, lalu masuk ke `scroll-padding-bottom` di `resources/css/app.css`. W4 tidak menambah lapisan
  baru; Rencanakan memakai `BarAksi` yang sudah menulis `--bar-aksi`.
- Primitif baru memakai konvensi `nilai`, `onUbah`, dan `label` (W1-F3). Primitif lama tidak diganti namanya; prop
  baru pada primitif lama (`penjelas`) tetap berbahasa Indonesia.

#### Cara kerja

- Isi berkas dan kode di brief adalah isi yang harus ditulis, persis. Bila teks yang harus diganti tidak ditemukan
  persis, jangan menebak: laporkan NEEDS_CONTEXT dengan kutipan berkas nyata. Nomor baris di brief adalah petunjuk;
  yang mengikat adalah teksnya.
- Ikuti urutan langkah brief: test gagal dulu (catat keluaran merahnya), implementasi, test lulus (catat keluaran
  hijaunya). Angka "pada simulasi rencana" di brief adalah acuan; bila angka nyata berbeda, laporkan apa adanya.
- Pint per berkas PHP tugas: `vendor/bin/pint --format agent <berkas>`. Setelah menyunting TypeScript: `npx tsc --noEmit`.
- Dokumen dan teks: bahasa Indonesia, sapaan "Anda", **tanpa karakter em dash**, tanpa kata "aman" dan "layak
  berangkat" sebagai klaim. Konvensi rujukan: "§N" hanya untuk ID di PRD; bagian dokumen lain ditulis "bagian N".
- Bila keluaran berbeda dari "Expected" di brief, berhenti dan selidiki sebelum lanjut; laporkan apa adanya. Jangan
  menulis angka yang tidak keluar dari perintah.

#### Laporan

Tulis laporan lengkap ke berkas laporan yang disebut di dispatch: yang dikerjakan, perintah dan keluarannya (RED lalu
GREEN), berkas yang berubah, temuan tinjauan diri, dan kekhawatiran. Balasan akhir paling banyak 15 baris: Status
(DONE | DONE_WITH_CONCERNS | BLOCKED | NEEDS_CONTEXT), "Commits: tidak ada (keputusan pemilik)", ringkasan test satu
baris, kekhawatiran, dan jalur berkas laporan.
