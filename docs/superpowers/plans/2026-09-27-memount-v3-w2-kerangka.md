# Memount v3 W2 Kerangka Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Semua halaman yang ada memakai satu kerangka ponsel dan layar lebar, sehingga W3 sampai W8 hanya mengisi
halaman. Setiap halaman membawa judul dan tempatnya: akar tab dengan avatar ke Profil, atau halaman turunan dengan
tombol kembali ke induk logisnya. Bilah tab mengambang dan navigasi segmen digambar dari satu daftar. Mulai 1024 px,
header 68 px membawa Simpanan berhitungan dan menu avatar. Kerangka juga memuat strip pendakian aktif, lembar Pengingat
dari lonceng Beranda, serta toast dan pesan jaringan yang dipasang sekali.

**Architecture:** `AppLayout` yang persisten di antara kunjungan Inertia menjadi satu-satunya kerangka. Di bawah 1024 px
tidak ada bilah atas global: kepala halaman datang dari `PageHeader` dengan `akar` atau `induk`, dan navigasi utamanya
`BilahTab`. Mulai 1024 px `KepalaLebar` 68 px menempel di atas. Kedua bentuk itu digambar dari `itemTab()` di
`NavLinks.tsx`. Data kerangka datang dari tiga props bersama di `HandleInertiaRequests`. `jumlahSimpanan` dan
`pendakianAktif` masing-masing satu kueri terindeks dalam closure malas yang dilewati muat ulang parsial.
`daftarPengingat` memakai `Inertia::optional` dan baru dimuat saat lembar dibuka. Jarak lapisan tetap diturunkan CSS
`:has()` dari elemen yang benar-benar ada, ditambah `scroll-padding` untuk fokus papan ketik.

**Tech Stack:** PHP 8.5.10, Laravel 13.31.0, Inertia Laravel 3.3.4, `@inertiajs/react` 3.7.1, React 19.3.0,
TypeScript 5.9.3, Vite 8.3.0, Tailwind CSS 3.4.19, PHPUnit 12.5.35, Pint, SQLite dalam memori untuk test, Phosphor
Icons core 2.1.1 (path ditanam, satu ikon baru). Tidak ada dependensi baru.

**Spec:** `docs/superpowers/specs/2026-09-25-memount-v3-prototype-design.md` (bagian 5.1 dan 5.2, 8.3 sampai 8.8;
DK-25, DK-33, DK-40), rencana induk `docs/superpowers/plans/2026-09-26-memount-v3-induk.md` (Global Constraints, Review
Focus, bagian W2 butir 1 sampai 6 dan "Selesai bila"), ruling koordinator W2-R1 sampai W2-R5, dan aturan eksekusi
`.superpowers/sdd/2026-09-26-memount-v3-w1-fondasi-desain/aturan-eksekusi.md` yang berlaku sama untuk W2. Keadaan
awalnya W1 akhir (27 September 2026, `HANDOVER.md` bagian 0).

## Global Constraints

- Versi dikunci: PHP 8.5, Laravel 13.31, Inertia Laravel 3.3.4, React 19.3, `@inertiajs/react` 3.7.1, Tailwind 3.4, Vite 8, TypeScript 5.9; tidak ada perubahan versi kerangka (R-020).
- Tidak ada dependensi baru, runtime maupun dev (DK-24, spec bagian 15 Q3 dan Q17). Tidak ada unduhan wajib: path Phosphor 2.1.1 `bookmark-simple` (regular dan bold) ditulis lengkap di Tugas 3, beserta URL dan SHA-256 berkas sumbernya untuk pemeriksaan opsional.
- Tidak ada commit, push, akses Supabase, atau migrasi di basis data nyata. Setiap langkah "Commit" berbunyi: "Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah."
- `php artisan migrate` dalam bentuk apa pun, `db:seed`, `db:wipe`, dan tinker tidak dipakai. Migrasi dan seeder hanya berjalan di dalam PHPUnit (SQLite dalam memori). `memount:demo` tidak dijalankan. W2 tidak menambah migrasi: `trip_reminders`, `permit_requirements`, `trail_considerations`, `hiking_sessions`, dan indeksnya sudah ada.
- Setiap perintah Artisan di luar PHPUnit, `npm run build`, dan `php -S` diberi awalan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL=` (Git Bash). Di PowerShell: `$env:DB_CONNECTION="sqlite"; $env:DB_DATABASE=":memory:"; $env:DB_URL="(empty)"; <perintah>`, lalu `Remove-Item Env:DB_CONNECTION, Env:DB_DATABASE, Env:DB_URL`. `DB_URL` di `.env` mengalahkan pengemudi koneksi. `php artisan test ...` tidak butuh awalan karena `phpunit.xml` memaksa koneksi uji. Jangan membaca atau mencetak `.env`.
- Port `127.0.0.1:8100` dicadangkan untuk harness pratinjau koordinator (Tugas 8). Tugas 1 sampai 7 tidak menyalakan server apa pun. Server `php artisan serve` pemilik di `127.0.0.1:8000` tidak pernah dipakai atau dihentikan.
- Bahasa Indonesia untuk teks antarmuka dan dokumen, sapaan "Anda", tanpa em dash (U+2014) di berkas mana pun, tanpa kata "aman" dan "layak berangkat" sebagai klaim (R-012, R-014). "§N" hanya untuk ID PRD; dokumen lain dirujuk "bagian N".
- Label kecocokan publik tetap "Cocok", "Perlu persiapan", "Kurang cocok", dan "Belum dinilai" untuk UNKNOWN (R-004, R-006); W2 tidak menyentuhnya.
- Token hanya di `resources/css/app.css`; tanpa warna mentah, `bg-white`, `text-black`, atau `dark:` di komponen. Kaca paling banyak dua elemen per layar: `.chrome` dipakai bilah tab dan bar aksi, sedangkan strip pendakian dan header layar lebar solid. Bayangan hanya `shadow-overlay` untuk lapisan melayang.
- Target sentuh kendali utama ≥44 px (avatar, tombol kembali, lonceng, Simpanan, baris menu akun); strip pendakian 48 px; item bilah tab 56 px.
- Props bersama kerangka: masing-masing paling banyak satu kueri terindeks dalam closure malas (dilewati muat ulang parsial), atau `Inertia::optional` bila hanya dibutuhkan saat diminta. Nama barunya tidak boleh sama dengan prop halaman mana pun, karena Inertia menimpa props bersama dengan props halaman. Dampaknya diukur `PageQueryBudgetTest` dan test props masing-masing.
- Test yang menyandikan keputusan lama diperbarui menurut niat baru dengan alasan tertulis di docblock-nya; tidak dihapus dan tidak dilonggarkan diam-diam (spec bagian 14).
- Tugas dikerjakan berurutan, satu implementer satu waktu, dan hanya menyunting berkas di daftar Files tugasnya. Berkas bersama (`resources/css/app.css`, `resources/js/layouts/AppLayout.tsx`, `resources/js/layouts/NavLinks.tsx`, `resources/js/types/index.d.ts`, `app/Http/Middleware/HandleInertiaRequests.php`) hanya disunting tugas yang menyebutnya. `HANDOVER.md` dan `CLAUDE.md` hanya disunting koordinator di Tugas 8.
- Test dibuat dengan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:test --phpunit --no-interaction <Nama>`, kelas layanan baru dengan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:class --no-interaction Services/Trips/<Nama>`, lalu isinya diganti persis dengan kode rencana. Setelah menyunting PHP jalankan `vendor/bin/pint --format agent <berkas>`; setelah menyunting TypeScript jalankan `npx tsc --noEmit`.
- Blok "Ganti ... dengan ..." diterapkan berurutan dengan alat Edit. Teks lamanya unik di berkasnya pada keadaan W1 akhir ditambah tugas-tugas sebelumnya, dan sudah diperiksa pada simulasi. Bila teks lama tidak ditemukan persis, berhenti dan laporkan NEEDS_CONTEXT; jangan mengarang padanannya.

## Review Focus

- **Teks Indonesia panjang di 360 px pada kerangka.** Nama jalur dan pos di strip pendakian, judul halaman turunan,
  Simpanan, menu akun, lembar Pengingat, dan pesan jaringan harus membungkus, tidak dipotong elipsis. Dijaga Tugas 2
  `LongInputLayoutTest::test_the_shell_wraps_long_labels_instead_of_cutting_them`, yang memeriksa `PageHeader`,
  `PintuAkun`, dan setiap berkas di `resources/js/layouts`. Tugas 4 menambah pengukuran tinggi strip
  (`ActiveHikeStripTest::test_the_strip_sits_above_the_tab_bar_and_opens_field_mode`), dan Tugas 8 memeriksanya di
  tangkapan 390 px.
- **Lapisan tetap menutupi isi atau fokus papan ketik.** Bilah tab, strip, bar aksi, dan toast di bawah serta header
  lebar di atas tidak boleh menutup baris terakhir isi atau elemen yang sedang difokus, dan urutan fokus mengikuti
  urutan baca. Dijaga Tugas 2 `InertiaLayoutTest::test_fixed_layers_leave_room_for_the_content_and_the_keyboard_focus`
  dan `test_the_focus_order_follows_the_reading_order`, ditambah Tugas 4 `ActiveHikeStripTest::test_the_strip_sits_above_the_tab_bar_and_opens_field_mode`.
- **Sinyal putus di tengah kunjungan atau saat lembar Pengingat memuat.** Pesan global tidak boleh mengaku tahu nasib
  aksi terakhir, dan lembar menangani galatnya sendiri tanpa pesan ganda. Dijaga Tugas 5
  `ShellFeedbackTest::test_a_network_error_shows_an_honest_message` dan Tugas 6
  `ReminderSheetTest::test_the_bell_opens_the_sheet_mounted_once_in_the_layout`.
- **Data pengguna lain lewat props bersama.** Hitungan simpanan, pendakian berlangsung, pengingat, dan tenggat izin
  hanya milik pengguna yang masuk, dan tamu mendapat null. Dijaga Tugas 3
  `SavedCountPropTest::test_the_count_matches_the_saved_page_and_belongs_to_its_owner` dan `test_a_guest_carries_no_count`,
  Tugas 4 `ActiveHikeStripTest::test_another_hiker_never_sees_it`, serta Tugas 6
  `ReminderSheetTest::test_the_sheet_lists_the_owners_scheduled_reminders_and_permit_deadlines` dan `test_a_guest_gets_nothing`.
- **Prop halaman yang bernama sama dengan prop bersama.** Karena props halaman menang, kerangka akan membaca data
  halaman dengan bentuk yang salah. Hal ini sempat terjadi saat simulasi: `Trips/Show` sudah punya prop `pengingat`.
  Dijaga Tugas 6 `ReminderSheetTest::test_shell_props_never_share_a_name_with_page_props`.

---

## Bukti simulasi rencana

Kode di rencana ini sudah dijalankan tugas demi tugas di dua salinan repo di scratchpad sesi perencanaan. Keduanya tanpa
`.env` dan tanpa riwayat git asli, dengan basis data hanya SQLite dalam memori milik PHPUnit, dan tanpa menjalankan
`memount:demo`, migrasi, atau seeder di luar PHPUnit.

1. **Salinan W1 akhir nyata.** Salinan baca-saja dari berkas terlacak dan belum terlacak repo pada 27 September 2026,
   sesudah W1 dinyatakan selesai. Salinan ini sumber semua kode dan angka di rencana: setiap blok "Ganti" diterapkan
   persis seperti implementer menerapkannya, lalu setiap tugas diuji merah terhadap keadaan tugas sebelumnya. Sesudahnya
   dijalankan suite Unit dan Feature penuh, `npx tsc --noEmit`, dan Pint `--test`.
2. **Salinan akhir simulasi W1.** Salinan ini dipakai lebih dulu untuk merancang kode dan untuk pemeriksaan visual di
   Chrome. Kode kerangkanya sama dengan salinan pertama, kecuali lonceng yang memberi `pemicu` ke `LembarBawah`
   (W2-K12). Perbaikan itu baru mungkin dengan primitif W1 akhir.

| Tugas | Merah: saringan tugas terhadap keadaan sebelumnya | Hijau: `php artisan test --compact --testsuite=Unit,Feature` |
|---|---|---|
| Dasar W1 akhir | | 1.559 test, 1.557 lulus, 2 dilewati; tsc bersih |
| 1 | 8 test: 2 lulus, 3 gagal, 3 error | 1.564 test, 1.562 lulus, 2 dilewati |
| 2 | 56 test: 45 lulus, 4 gagal, 7 error | 1.573 test, 1.571 lulus, 2 dilewati |
| 3 | 16 test: 11 lulus, 3 gagal, 2 error | 1.579 test, 1.577 lulus, 2 dilewati |
| 4 | 7 test: 0 lulus, 4 gagal, 3 error | 1.586 test, 1.584 lulus, 2 dilewati |
| 5 | 3 test: 0 lulus, 1 gagal, 2 error | 1.589 test, 1.587 lulus, 2 dilewati |
| 6 | 9 test: 1 lulus, 3 gagal, 5 error | 1.598 test, 1.596 lulus, 2 dilewati |
| 7 | 20 test: 18 lulus, 2 gagal | 1.600 test, 1.598 lulus, 2 dilewati |

Sesudah setiap tugas: `npx tsc --noEmit` tanpa galat, dan Pint `--test` lulus untuk berkas PHP tugas itu. Hasil
akhirnya:

- W2 menambah 41 test. Suite bawaan `php artisan test --compact` berisi 1.620 test (1.598 lulus, 22 dilewati).
- `vendor/bin/pint --test --format agent` untuk seluruh pohon lulus.
- Build berhasil, suite `Pratinjau` 9 test lulus, dan komentar lisensi Phosphor serta BSD tetap ada di bundel.
- 77 berkas berubah atau baru (+2.282 dan −276 baris).

**Anggaran kueri** diukur dengan data `DemoSeeder` W2 sebagai Budi. Uji ukurnya sementara, tidak masuk repo: kunjungan
kedua setiap halaman, kode W1 akhir dibanding kode W2.

| Halaman | W1 akhir | W2 | Selisih |
|---|---|---|---|
| `/dashboard` | 5 | 7 | +2 |
| `/trails` | 13 | 15 | +2 |
| `/trips` | 13 | 15 | +2 |
| `/mendaki` | 5 | 7 | +2 |
| `/progres` | 9 | 11 | +2 |
| `/profile` | 0 | 2 | +2 |

Tambahan dua kueri itu tetap: satu hitungan simpanan dan satu pencarian pendakian berlangsung. Muat ulang parsial yang
tidak memintanya tidak membayar keduanya. Isi lembar Pengingat paling banyak 5 kueri tetap dan hanya saat lembar
dibuka.

**Bundel:**

- `app-*.js` 355.096 → 354.780 bita (−316): navigasi lama terhapus dan kerangka baru ditambahkan.
- `app-*.css` +282 bita (62.699 → 62.981), dibandingkan pada himpunan view terkompilasi yang sama. Ukuran absolut CSS
  bergantung isi `storage/framework/views` (lihat Keadaan awal).

**Pemeriksaan visual** di salinan kedua, Chrome, lewat harness pratinjau salinan itu pada port terpisah dari 8100 milik
koordinator:

- Kepala ponsel 390 px cocok dengan susunan prototype 01 (lockup, lonceng, avatar), 02 (Jelajah, Simpanan 5, avatar),
  dan 13 (Progres, avatar).
- Detail jalur memakai tombol kembali tanpa bilah tab, seperti 04.
- Header 1440 px terang dan gelap cocok dengan susunan 15, dan muat di 1024 px. Lebar 768 px tetap kerangka ponsel.
- Menu avatar: Tab masuk ke Profil dengan cincin fokus di dalam baris, Escape menutup, dan fokus kembali ke avatar.
- Lembar Pengingat terbuka, pratinjau statis menampilkan keadaan galatnya, dan Escape mengembalikan fokus ke lonceng.
- Urutan Tab: tautan lewati, lonceng, avatar, isi, lalu bilah tab, dengan cincin terlihat.
- Strip pendakian membungkus nama panjang dan duduk di atas bilah tab; baki banding dan toast bergeser di atasnya.
- Perbedaan yang tersisa terhadap prototype dicatat di Tugas 8.

**Temuan di luar cakupan W2 (OPEN ISSUE):** `RecommendationExclusionTest::test_jelajah_shows_no_public_fit_label_for_an_incomplete_trail`
sekali gagal di suite penuh salinan kedua. Faker membuat nama "Jalur O'Hara"; `assertSee` mencari bentuk ter-escape
`O&#039;Hara`, sedangkan data halaman Inertia membawa `O'Hara`. Kegagalan ini bergantung pada nama acak, sudah ada
sebelum W2, dan lulus saat diulang.

## Keadaan awal yang terukur (FAKTA, 27 September 2026)

| Butir | Keadaan | Bukti |
|---|---|---|
| Suite W1 akhir | Unit dan Feature 1.559 test (1.557 lulus, 2 dilewati); suite bawaan 1.579 test (1.557 lulus, 22 dilewati); tsc bersih | Laporan koordinator 27 September; diulang di salinan W1 akhir |
| Kerangka W1 | `AppLayout` menggambar bilah atas global dengan menu profil dan menu garis tiga (Profil, Moderasi, Admin, Keluar), serta navigasi bawah lama `TabBawah` dari `NavLinks.tsx` yang labelnya dipotong `truncate`. `--nav-bawah` ditulis di div akar layout, `4rem` ditambah safe area di ponsel dan `0px` mulai `sm` | `resources/js/layouts/AppLayout.tsx:16-64,115,120-122`; `resources/js/layouts/NavLinks.tsx:103` |
| Bilah tab v3 | `BilahTab` sudah ada sejak W1 (pil, item `h-14`, `li` `min-w-11`), baru dipakai pameran, dan tersembunyi mulai `sm` | `resources/js/components/ui/BilahTab.tsx` |
| Kepala halaman | `PageHeader` menerima `title`, `description`, `aksi`, `children`, tanpa tempat halaman; dipakai 38 halaman | `resources/js/components/ui/PageHeader.tsx`; `git grep -l '<PageHeader' resources/js/pages` |
| Lapisan bawah W1 akhir | `BarAksi` menulis `--bar-aksi` dan memasang pengganjal setinggi itu; toast duduk di `--nav-bawah` ditambah `--bar-aksi` ditambah `1rem`; baki banding menempel di `--nav-bawah` | `BarAksi.tsx:32,44-45`; `ToastUrungkan.tsx:71`; `trails/ConsiderationTray.tsx:20` |
| Lembar W1 akhir | `LembarBawah` menerima `pemicu?: RefObject<HTMLElement \| null>`; body tidak dihitung sebagai pemicu karena Safari tidak memfokus tombol yang diklik | `resources/js/components/ui/Lembar.tsx:8-35` |
| Toast W1 akhir | `useToastUrungkan().tampilkan(pesan, onUrungkan, opsi = {})` dengan `OpsiToast { fokus? }`, `useFokusSesudahRender()`; wadahnya dipasang pameran sendiri | `ToastUrungkan.tsx:12-45`; `pages/Dev/Primitif.tsx:19,106,520` |
| Data kerangka | `trip_reminders` (`jenis`, `dijadwalkan_untuk` terindeks, `sent_at`, `skipped_at`, scope `menunggu()`); `PermitService::requirementsForTrails()` dan `bookingWindowFor()` dengan `BOOKING_BELUM_DIBUKA`, `BOOKING_TERBUKA`, `BOOKING_DITUTUP`; indeks FK `hiking_sessions` dan `trip_plans.user_id`; `trip_plans.status` terindeks; indeks unik `trail_considerations_unik (user_id, trail_id)` | `database/migrations/2026_09_25_000918_create_trip_reminders_table.php:24-33`; `app/Models/TripReminder.php:62`; `app/Services/PermitService.php:52,100-116`; `database/migrations/2026_09_20_000200_indeks_foreign_key_yang_belum_terindeks.php:53,66`; `database/migrations/2026_09_21_000100_create_trail_considerations_table.php:30` |
| Nama prop yang sudah dipakai | `TripController` mengirim prop halaman `pengingat` ke `Trips/Show` | `app/Http/Controllers/Trips/TripController.php:248` |
| Inertia 3 | `Inertia::optional()` menghasilkan `OptionalProp` yang dilewati kunjungan pertama; event global `networkError` dan callback kunjungan `onNetworkError` tersedia | `vendor/inertiajs/inertia-laravel/src/ResponseFactory.php:243`, `OptionalProp.php:5`; `node_modules/@inertiajs/core/types/types.d.ts:322,381` |
| Ukuran CSS | `tailwind.config.js` ikut memindai `./storage/framework/views/*.php`. Repo memuat 299 view terkompilasi, sebagian dari 25 Agustus. Dari sumber yang sama, `app-*.css` di repo 79.056 bita dan di salinan bersih 59.714 bita | `tailwind.config.js:19`; build di salinan |
| Ikon | `bookmark-simple` belum ada di registri | `resources/js/components/ui/ikon/phosphor.ts` |

## Keputusan rencana ini

| ID | Keputusan | Dasar |
|---|---|---|
| W2-K1 | `PageHeader` diperluas, bukan diganti komponen baru. Tipe union TypeScript mewajibkan tepat satu dari `akar` atau `induk={{ href, label }}`, jadi `npx tsc --noEmit` menolak halaman yang lupa tempatnya. Nama prop judul tetap `title` karena 38 halaman sudah memakainya | Induk W2 butir 1; DRY |
| W2-K2 | Tombol kembali (`TombolKembali`) adalah tautan ke induk logis, bukan `history.back()`. Halaman yang dibuka dari luar atau di tab baru tetap punya jalan pulang, dan nama aksesibelnya menyebut tujuan ("Kembali ke Jelajah") | Spec bagian 5.1 |
| W2-K3 | Avatar 44 px (`TautanProfil`) hanya di lima akar tab dan langsung membuka Profil. Moderasi, Admin, dan Keluar untuk ponsel pindah ke kartu "Akun" di Profil (`PintuAkun`), karena menu garis tiga hilang bersama bilah atas global | Ruling W2-R1; spec bagian 5.1 |
| W2-K4 | Judul h1 akar tab sama dengan label tabnya: "Jelajahi Jalur" menjadi "Jelajah", "Rencana Trip Saya" menjadi "Trip", "Progres Pendakian" menjadi "Progres"; Mendaki sudah sama. Beranda memberi `merek` (lockup) dan tetap memakai sapaan sebagai h1. Judul tab peramban tidak berubah | Prototype 02 dan 13; tab dan judul tidak berselisih |
| W2-K5 | Lencana Data contoh di ponsel duduk di bawah judul, bukan di baris atas. Baris 360 px tidak muat untuk judul, aksi, avatar, dan lencana, dan label di atas h1 terbaca sebagai eyebrow | antislop-ui "Eyebrow Badge Above the Headline"; R-09 |
| W2-K6 | Satu titik henti kerangka, `lg` (1024 px), untuk header lebar dan bilah tab. Lebar 640 sampai 1023 px tetap memakai kerangka ponsel | DK-33; spec bagian 5.2 |
| W2-K7 | Jarak lapisan tetap diturunkan `app.css` lewat `:has()` dari elemen yang ada (`--bilah-tab`, `--strip-pendakian`, `--kepala-atas`, `--nav-bawah`), ditambah `scroll-padding-top` dan `scroll-padding-bottom`, bukan nilai yang disalin ke setiap halaman | WCAG 2.4.11; satu sumber |
| W2-K8 | Header layar lebar menempel di atas (`sticky`, 68 px), dan peta Jelajah yang lengket digeser setinggi `--kepala-atas` | Spec bagian 5.2; prototype 15 |
| W2-K9 | Menu avatar layar lebar memakai popover asli (`popover="auto"`), tanpa peran ARIA menu karena isinya tautan dan satu tombol. Menutup dengan Escape dan klik di luar, pengembalian fokus, dan top layer datang dari peramban; menu ditutup saat kunjungan Inertia dimulai | Tanpa dependensi; WCAG 2.1.1 |
| W2-K10 | Toast, lembar Pengingat, dan pesan jaringan dipasang sekali di `AppLayout` yang persisten; halaman memintanya lewat `useKerangka()`. Tipe `tampilkanToast` diambil dari `useToastUrungkan`, jadi opsi W1 akhir (`fokus`) ikut tanpa disalin | Induk W2 butir 6 |
| W2-K11 | `daftarPengingat` adalah prop bersama opsional yang diminta lewat muat ulang parsial setiap kali lembar dibuka. Kunjungan biasa tidak membayarnya, dan lonceng tidak diberi titik atau angka yang belum dihitung | DK-40; antislop-ui "Decorative Status Dot" |
| W2-K12 | Lonceng memberi dirinya sebagai `pemicu` `LembarBawah` lewat layanan kerangka, jadi fokus kembali ke lonceng saat lembar ditutup, juga di Safari | W1 akhir: `Lembar.tsx` |
| W2-K13 | Strip pendakian menampilkan lama berjalan yang dihitung peramban dari instan mulai sesi (`mulai`, ISO 8601), tanpa pengumuman tiap menit. Isinya isian merek solid, bukan kaca, karena jatah dua kaca sudah dipakai bilah tab dan bar aksi. Strip mengukur tingginya sendiri, karena nama panjang membungkus | Spec bagian 5.1 ("waktu berjalan"); R-10 |
| W2-K14 | `pendakianAktif` satu kueri join (trip berlangsung milik pengguna, sesinya, dan pos yang dituju) dan null di rute mode lapangan. `jumlahSimpanan` memakai predikat yang sama dengan halaman Simpanan dan batas simpanan (jalur aktif saja) | Induk W2 butir 3 dan 4 |
| W2-K15 | Pesan jaringan memakai `Alert` (peran alert). Pesan itu tidak mengaku tahu nasib aksi terakhir, memberi "Muat ulang", dapat ditutup, dan hilang saat kunjungan berikutnya berhasil. "Buka paket luring" menyusul W6 | Ruling W2-R3 |
| W2-K16 | `DemoSeeder` diperluas: lima simpanan, pengingat H-3 dan H-1 trip Gede lewat `TripReminderScheduler`, dan satu aturan izin tingkat gunung bertanda DATA CONTOH. Semuanya lewat tabel dan layanan yang sama dengan fitur aslinya | Prototype 02 dan 15 ("Simpanan 5"); DK-35 |
| W2-K17 | Pratinjau W2 memotret kelima akar tab, detail jalur sebagai halaman tugas tanpa bilah tab, dan Jelajah saat mendaki untuk strip. Tanpa server, lembar Pengingat di pratinjau statis menampilkan keadaan galatnya | Ruling W2-R4 |
| W2-K18 | Satu ikon baru di registri, `bookmark-simple` (Phosphor 2.1.1 regular dan bold), untuk Simpanan | Spec bagian 8.3 |
| W2-K19 | `Trails/Show` dan `Trips/Create` sudah menyembunyikan bilah tab di W2, walau bar aksinya baru dibangun W3 dan W4; halaman penilaian kecocokan belum ada (W3) | Spec bagian 5.1; induk W2 butir 2 |

### Konflik yang perlu ruling koordinator

1. **Induk W2 butir 3 meminta `NavigationConsistencyTest::test_a_hiker_never_sees_the_admin_entry` "tetap lulus".**
   Sumber yang dibacanya (`AppLayout.tsx` dan menu garis tiga) hilang. Rekomendasi: nama dan niat test dipertahankan.
   Test itu kini membaca `MenuAkun.tsx` dan `PintuAkun.tsx`, dan alasannya ditulis di docblock (Tugas 2).
2. **Avatar ponsel langsung ke Profil (W2-R1), sehingga Moderasi, Admin, dan Keluar tidak lagi punya pintu di ponsel.**
   Rekomendasi: W2-K3, yaitu kartu "Akun" minimal di Profil sebelum W7 menata ulang Profil.
3. **Induk W2 butir 4 menulis "waktu mulai", spec bagian 5.1 menulis "waktu berjalan".** Rekomendasi: prop membawa
   instan mulai dan layar menampilkan lama berjalan (W2-K13). Keduanya terpenuhi.
4. **Prototype 01 dan 02 menaruh lencana Data contoh di baris kepala.** Rekomendasi: W2-K5, lencana di bawah judul
   pada ponsel dan di header mulai 1024 px.
5. **Bilah tab disembunyikan di detail jalur dan rencanakan trip sebelum bar aksinya ada.** Rekomendasi: sembunyikan
   sekarang sesuai spec (W2-K19), karena tombol kembali selalu ada. Alternatifnya menunda sampai W3 dan W4.
6. **Judul h1 tiga akar tab berganti menjadi label tabnya** (W2-K4). Ini perubahan teks yang terlihat pengguna.
   Rekomendasi: terima; deskripsi di bawah judul tetap menjelaskan isi halaman.
7. **`DemoSeeder` diperluas di W2**, walau induk W2 tidak menyebutnya. Tanpa perluasan ini, "Simpanan 5" dan isi
   lembar Pengingat tidak terlihat di tangkapan W2-R5. Rekomendasi: W2-K16.
8. **Induk menyebut props halaman `judul`, sedangkan kode memakai `title`.** Rekomendasi: W2-K1, karena mengganti
   nama menyentuh 38 halaman tanpa manfaat bagi pengguna.
9. **Jarak atas halaman masih `py-8`, prototype sekitar 16 px.** Isi halaman ditata ulang W3 sampai W7. Rekomendasi:
   biarkan, dan catat di perbandingan tangkapan Tugas 8.
10. **Ukuran CSS bergantung isi `storage/framework/views` (OPEN ISSUE, di luar W2).** Rekomendasi: putuskan terpisah
    antara membuang pola itu dari `tailwind.config.js`, karena `resources/views` sudah dipindai, atau menjalankan
    `view:clear` sebelum build rilis.

### Pra-cek terhadap W1 akhir

Rencana ditulis di atas asumsi pembaruan antarmuka W1 dari koordinator, lalu diperiksa ulang pada salinan W1 akhir
nyata. Semua blok di Tugas 1 sampai 7 berlaku di sana tanpa penyesuaian lanjutan. Langkah yang bergantung pada butir 3
sampai 5 pembaruan W1:

| Butir W1 akhir | Langkah W2 yang bergantung | Keadaan di rencana |
|---|---|---|
| 3: `BarAksi` menulis `--bar-aksi`, toast menambahkannya | Tugas 2 langkah `app.css`: `--nav-bawah` kini didefinisikan di `:root`, bukan di div akar `AppLayout`. Tugas 4: strip masuk ke `--nav-bawah`, jadi bar aksi dan toast naik di atasnya | Diterapkan; `DesignPrimitivesTest` W1 dan `ActiveHikeStripTest` hijau |
| 3: toast opsi `fokus` dan `useFokusSesudahRender` | Tugas 5: tipe `tampilkanToast` diambil dari hook; blok `Dev/Primitif.tsx` mempertahankan `useFokusSesudahRender` | Diterapkan; tsc bersih dengan pemanggilan tiga argumen |
| 3: `LembarBawah` menerima `pemicu` | Tugas 6: lonceng memberi pemicunya (W2-K12) | Diterapkan |
| 3: urutan lapisan di `DESIGN.md` bagian Material | Tugas 7: paragraf "Skala lapisan" dan "Urutan tumpuk" ditulis ulang dengan strip, header lebar, pesan jaringan, dan popover | Diterapkan pada teks W1 akhir |
| 4: UNKNOWN menjadi "Belum dinilai" | Tidak ada | |
| 5: lapisan tepi peta | Tugas 2 hanya menggeser peta Jelajah setinggi `--kepala-atas`; Tugas 8 memotret peta detail jalur dan Jelajah | Tidak ada blok yang bergantung |

Perbedaan W1 akhir lain yang dilewati blok tanpa diubah: baris `'demo' => PenjagaDataContoh::sedangDisajikan()` di
`HandleInertiaRequests`, `li` `min-w-11` di `BilahTab`, `DemoSeeder` dan `DemoSeederTest` (test pagar sumber),
`HalamanPratinjauTest` dan `PratinjauHarnessTest`, serta `CONTRIBUTING.md` dengan awalan `DB_URL=`.

## Perbandingan skill (DK-57)

Urutan wewenang: arahan pemilik dan aturan kanonis, lalu `DESIGN.md` (arah), lalu antislop, lalu saran skill. antislop
berlaku mode 1 (selama pekerjaan) atas arahan pemilik yang sudah berdiri, dan tidak ada pertanyaan baru kepada pemilik.

| Skill | Diambil | Ditolak dan alasannya |
|---|---|---|
| antislop, antislop-ui, antislop-layoutmobile, antislop-copywriting | R-26: lonceng membuka daftar yang benar-benar ada, dan setiap tautan kerangka menuju halaman nyata. R-27: lembar Pengingat punya keadaan memuat, galat dengan "Coba lagi", kosong yang menyebut apa yang akan muncul, dan daftar. R-32: Escape dan fokus kembali di popover dan lembar. R-09: satu lencana, hanya "Data contoh". "Eyebrow Badge Above the Headline": lencana di bawah judul. "Decorative Status Dot": lonceng tanpa titik. R-10: strip solid. Pesan jaringan tidak mengarang hasil. Target 44 px | Tidak ada; antislop adalah saringan di atas `DESIGN.md` |
| ui-ux-pro-max | "Safe-area compliance" untuk header, bilah tab, dan bar aksi (`env(safe-area-inset-*)`). "Scroll and fixed element coexistence": `main` diberi ruang `--nav-bawah` dan `scroll-padding`. Hitungan Simpanan bernama lengkap untuk pembaca layar ("5 jalur tersimpan") tanpa wilayah live. Kerangka lima tab ikon dan label | Keluaran `--design-system` (gradien, jingga, Lucide), sama dengan W1; "toast 3 sampai 5 detik" (W1-K7) |
| design-taste-frontend | "Navigation MUST render on a single line on desktop": navigasi segmen satu baris di 1024 px, dengan label Simpanan hanya untuk pembaca layar di bawah `xl`. "The middle-dot is rationed": keterangan strip dipisah koma. "NO decorative colored status dots". Skala z-index tertulis di `DESIGN.md` | "move to a hamburger" bila navigasi tidak muat (spec bagian 5.1 dan 5.2 tanpa menu garis tiga; 1024 px terukur muat) |
| impeccable (`reference/adapt.md`, `reference/adapt.native.md`) | Navigasi bawah di ponsel, tombol kembali di alur navigasi, header lengket untuk konteks. "Restructure, don't stretch" dan "popovers where phones used sheets": menu avatar popover di layar lebar, Profil di ponsel. Tablet tetap berbilah tab | "Hamburger menu" sebagai pilihan navigasi ponsel; breadcrumbs di layar kecil (tombol kembali berlabel induk sudah memberi konteks tanpa memakan lebar 360 px) |

**Pembacaan desain (antislop bagian 3):** kerangka navigasi aplikasi perencanaan pendakian untuk pendaki pemula
Indonesia di ponsel, bahasa visual editorial utilitarian yang hangat, dial ENERGY 2 / RHYTHM 2 / MOTION 2. Kerangka itu
tenang dan hanya memuat unsur yang dipakai: lima tab, satu lencana keadaan, dan satu strip saat mendaki.

**Inventaris skill untuk gelombang ini (CLAUDE.md bagian 7):**

- INTI: antislop (dengan antislop-ui, antislop-layoutmobile, antislop-copywriting), impeccable, ui-ux-pro-max,
  design-taste-frontend, testing-best-practices, laravel-best-practices (middleware, layanan, props bersama),
  superpowers:test-driven-development.
- PENDUKUNG: tailwindcss-development (`:has()`, nilai arbitrer), apple-design (lembar dan tombol kembali), mobile-native
  (safe area, `dvh`), modern-web-guidance (popover, `<dialog>`, `scroll-padding`), ecc:frontend-a11y.
- LINTAS: superpowers:verification-before-completion dan antislop Delivery Gate.
- N/A:
  - slides dan banner-design: bukan artefak halaman;
  - brand dan brandkit: aset logo menunggu gerbang DK-16;
  - livewire-development dan volt-development: Livewire sudah dipensiunkan;
  - deploying-to-cloud: tanpa rilis;
  - write-swift: bukan aplikasi native.

## File Structure

**Dibuat:**

- Tugas 1: `resources/js/components/ui/TombolKembali.tsx`, `resources/js/components/ui/TautanProfil.tsx`,
  `resources/js/components/akun/PintuAkun.tsx`.
- Tugas 2: `resources/js/layouts/KepalaLebar.tsx` (header layar lebar dan navigasi segmen),
  `resources/js/layouts/MenuAkun.tsx`.
- Tugas 3: `resources/js/layouts/TautanSimpanan.tsx`.
- Tugas 4: `app/Services/Trips/PendakianAktif.php`, `resources/js/layouts/StripPendakian.tsx`.
- Tugas 5: `resources/js/layouts/Kerangka.tsx` (konteks layanan kerangka), `resources/js/layouts/PesanJaringan.tsx`.
- Tugas 6: `app/Services/Trips/DaftarPengingat.php`, `resources/js/layouts/Pengingat.tsx`.
- Test baru: `PageHeaderContractTest` (Tugas 1), `SavedCountPropTest` (Tugas 3), `ActiveHikeStripTest` (Tugas 4),
  `ShellFeedbackTest` (Tugas 5), `ReminderSheetTest` (Tugas 6).
- Tugas 8 (koordinator): `docs/desain/tangkapan/w2/*.webp` dan `docs/desain/tangkapan/w2/README.md`.

**Diubah:**

- Kerangka:
  - `resources/js/components/ui/PageHeader.tsx` (Tugas 1);
  - `resources/js/layouts/NavLinks.tsx`, `resources/js/components/ui/BilahTab.tsx` (Tugas 2);
  - `resources/css/app.css` (Tugas 2);
  - `resources/js/layouts/AppLayout.tsx` (Tugas 2, 4, 5, 6);
  - `resources/js/layouts/KepalaLebar.tsx` (Tugas 3);
  - `resources/js/layouts/Kerangka.tsx` (Tugas 6).
- Server dan tipe:
  - `app/Services/ConsiderationService.php` (Tugas 3);
  - `app/Http/Middleware/HandleInertiaRequests.php` dan `resources/js/types/index.d.ts` (Tugas 3, 4, 6);
  - `resources/js/components/ui/ikon/phosphor.ts` (Tugas 3).
- Halaman:
  - 38 halaman di `resources/js/pages` memberi `akar` atau `induk`, dan `Profile/Edit.tsx` memasang `PintuAkun`
    (Tugas 1);
  - `Trails/Show.tsx`, `Trips/Create.tsx`, `Trails/Index.tsx` (Tugas 2);
  - `Trails/Index.tsx` (Tugas 3), `Dev/Primitif.tsx` (Tugas 5), `Dashboard.tsx` (Tugas 6).
- Data contoh dan pratinjau (Tugas 7): `database/seeders/DemoSeeder.php`, `tests/Pratinjau/HalamanPratinjauTest.php`.
- Test yang diperbarui menurut niat baru:
  - `DemoBadgeTest` (Tugas 1 dan 2);
  - `NavigationConsistencyTest`, `IconSystemTest`, `InertiaLayoutTest`, `LongInputLayoutTest` (Tugas 2);
  - `PageQueryBudgetTest` (Tugas 3);
  - `DemoSeederTest`, `PratinjauHarnessTest` (Tugas 7).
- Dokumen:
  - `DESIGN.md` bagian Navigasi dan Material, `CONTRIBUTING.md`, `docs/ARCHITECTURE.md` (Tugas 7);
  - `HANDOVER.md` bagian 0 (Tugas 8, koordinator).

---

### Task 1: Kepala per halaman (judul, akar tab, dan induk logis)

Induk W2 butir 1 dan ruling W2-R1. Ponsel tidak lagi punya bilah atas global, jadi setiap halaman selain Auth dan
Welcome memberi `PageHeader` judul dan tempatnya:

- lima akar tab memberi `akar`, dengan h1 sama dengan label tabnya (W2-K4); Beranda menambah `merek`;
- halaman lain memberi `induk={{ href, label }}` ke induk logisnya (W2-K2).

Avatar di akar tab langsung ke Profil, jadi pintu Moderasi, Admin, dan Keluar untuk ponsel pindah ke kartu "Akun" di
Profil (W2-K3). Mulai 1024 px lockup, avatar, dan lencana di kepala halaman disembunyikan, karena header layar lebar
Tugas 2 membawanya. Sampai Tugas 2 selesai, bilah atas lama W1 masih tampil di atas kepala halaman; keadaan antara
itu tidak dipotret.

**Files:**
- Create: `resources/js/components/ui/TombolKembali.tsx`, `resources/js/components/ui/TautanProfil.tsx`, `resources/js/components/akun/PintuAkun.tsx`
- Modify: `resources/js/components/ui/PageHeader.tsx` (tulis ulang), dan 38 halaman:
  - `resources/js/pages/Admin/` Analytics, AuditLogs, Authorities, Checkpoints, Credentials, DataSources, Health, Mountains, OfficialStatuses, Permits, TrailGeometry, Trails, Usability;
  - `resources/js/pages/Community/` ConditionReport, HikeSummary, HikerProgress, HikingHistory, TrailContribution, TrailNews;
  - `resources/js/pages/` Dashboard, `Dev/Primitif`, `Goals/Create`, `Moderation/Queue`, `Onboarding/ProfileSetup`, `Profile/Edit`, `Recommendations/Show`, `Track/Index`;
  - `resources/js/pages/Trails/` Awaiting, Compare, Index, Saved, Show;
  - `resources/js/pages/Trips/` Create, Edit, Index, Preparation, Readiness, Show.
- Test: `tests/Feature/PageHeaderContractTest.php` (baru), `tests/Feature/DemoBadgeTest.php`

**Interfaces:**
- Consumes (W1): `Tautan`, `Avatar({ nama, ukuran? })`, `Lockup({ href?, size?, className? })`, `LencanaDataContoh`, `Card({ title?, subtitle?, className?, children })`, `fokusTerlihat` dan `umpanTekan` dari `@/components/ui/gaya`, `kelas` dari `@/lib/kelas`, `TautanMenu({ href, children })` dan `tombolMenu` dari `@/layouts/NavLinks`, rute Wayfinder (`dashboard`, `profile`, `progress`, `contribute` dari `@/routes`; `index`, `show`, `preparation` dari `@/routes/trips`; `index`, `saved` dari `@/routes/trails`; `trails` dari `@/routes/admin`), `PropsBersama.auth.user` (`name`, `isAdmin`, `isModerator`), trait test `SumberReact` (`berkasTsx()`, `tanpaKomentarJs()`, `tagPembuka()`, `tagBernama()`).
- Produces:
  - `PageHeader` dengan `title: string`, `description?: string`, `aksi?: ReactNode`, `children?: ReactNode`, ditambah tepat satu dari `{ akar: true; merek?: boolean }` atau `{ induk: Induk }`, dan `export interface Induk { href: string; label: string }`.
  - `TombolKembali({ href, label })`, `TautanProfil({ className? })`, `PintuAkun()`.
  - Tugas 3 memberi Jelajah `aksi={<TautanSimpanan className="lg:hidden" />}`, Tugas 6 memberi Beranda `aksi={<TombolPengingat />}`, dan Tugas 2 bergantung pada `lg:hidden` di kepala ini.

- [ ] **Step 1: Tulis test yang gagal.** Buat test dengan
`DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:test --phpunit --no-interaction PageHeaderContractTest`,
lalu ganti seluruh isi `tests/Feature/PageHeaderContractTest.php` dengan:

```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Header per halaman (spec v3 bagian 5.1; rencana induk W2 butir 1; ruling W2-R1).
 *
 * Ponsel tidak punya bilah atas global. Setiap halaman membawa tempatnya sendiri: akar tab
 * memberi judul dan avatar ke Profil, halaman turunan memberi judul dan tombol kembali ke
 * induk logisnya, bukan ke riwayat peramban. Halaman dirender di klien, jadi kontraknya
 * dijaga dari sumber; PageHeader juga mewajibkan `akar` atau `induk` lewat tipenya, sehingga
 * `npx tsc --noEmit` menolak halaman baru yang lupa menyebut tempatnya.
 */
class PageHeaderContractTest extends TestCase
{
    use SumberReact;

    /**
     * Lima akar tab pemilik (DK-25) dan label tabnya. Beranda memakai sapaan sebagai judul
     * dan lockup di baris atasnya (prototype 01), jadi judulnya tidak disamakan dengan tab.
     *
     * @var array<string, string|null>
     */
    private const AKAR = [
        'Dashboard.tsx' => null,
        'Trails/Index.tsx' => 'Jelajah',
        'Trips/Index.tsx' => 'Trip',
        'Track/Index.tsx' => 'Mendaki',
        'Community/HikerProgress.tsx' => 'Progres',
    ];

    public function test_every_page_names_its_title_and_its_place(): void
    {
        $pelanggar = [];
        $diperiksa = 0;

        foreach ($this->halamanBerkerangka() as $nama => $isi) {
            $diperiksa++;

            // Mode lapangan memakai tata letaknya sendiri (W6): judul h1 dan tombol kembali
            // ke rincian trip lewat prop kembali.
            if (str_contains($isi, '<TataLetakPendakian')) {
                if (! preg_match('/<TataLetakPendakian\s[^>]*\bkembali=\{/', $isi) || substr_count($isi, '<h1') !== 1) {
                    $pelanggar[] = "{$nama}: mode lapangan tanpa kembali= atau tanpa tepat satu h1";
                }

                continue;
            }

            $kepala = $this->tagBernama($isi, 'PageHeader');

            if (count($kepala) !== 1) {
                $pelanggar[] = "{$nama}: ".count($kepala).' PageHeader, seharusnya satu';

                continue;
            }

            [$tag] = $kepala[0];

            if (! preg_match('/\btitle=(?:"[^"]*\S[^"]*"|\{)/', $tag)) {
                $pelanggar[] = "{$nama}: PageHeader tanpa judul";
            }

            $akar = preg_match('/\sakar(?=[\s\/>])/', $tag) === 1;
            $induk = preg_match('/\sinduk=\{/', $tag) === 1;

            if ($akar === $induk) {
                $pelanggar[] = "{$nama}: PageHeader harus memberi tepat satu dari akar atau induk";
            }
        }

        $this->assertGreaterThan(35, $diperiksa, 'Sapuan tidak menemukan halaman untuk diperiksa.');
        $this->assertSame([], $pelanggar, implode("\n", $pelanggar));
    }

    /**
     * Hanya lima akar tab yang boleh menjadi akar. Judul akar tab sama dengan label tabnya,
     * supaya kata di bilah tab yang menyala dan kata di kepala halaman tidak berbeda.
     */
    public function test_only_the_five_tab_roots_are_roots_and_they_carry_their_tab_name(): void
    {
        $akar = [];

        foreach ($this->halamanBerkerangka() as $nama => $isi) {
            foreach ($this->tagBernama($isi, 'PageHeader') as [$tag]) {
                if (preg_match('/\sakar(?=[\s\/>])/', $tag)) {
                    $akar[$nama] = $tag;
                }
            }
        }

        $this->assertEqualsCanonicalizing(array_keys(self::AKAR), array_keys($akar));

        foreach (self::AKAR as $nama => $label) {
            if ($label === null) {
                $this->assertMatchesRegularExpression('/\smerek(?=[\s\/>])/', $akar[$nama], 'Beranda memasang lockup di baris atas (prototype 01).');

                continue;
            }

            $this->assertStringContainsString('title="'.$label.'"', $akar[$nama], "{$nama} berjudul label tabnya.");
        }
    }

    /**
     * Tombol kembali adalah tautan ke induk logis, bukan history.back(): halaman yang dibuka
     * dari tautan bagikan, surel, atau tab baru tidak punya riwayat. Lingkaran 44 px, ikon
     * arrow-left 20 px, dan nama "Kembali ke ..." bagi pembaca layar.
     */
    public function test_the_back_button_goes_to_the_logical_parent(): void
    {
        $tombol = $this->tanpaKomentarJs(File::get(resource_path('js/components/ui/TombolKembali.tsx')));
        $kepala = $this->tanpaKomentarJs(File::get(resource_path('js/components/ui/PageHeader.tsx')));

        $this->assertStringContainsString('<Tautan', $tombol);
        $this->assertStringContainsString('href={href}', $tombol);
        $this->assertStringContainsString('h-11 w-11', $tombol);
        $this->assertStringContainsString('<Icon name="arrow-left" size={20} />', $tombol);
        $this->assertStringContainsString('<span className="sr-only">Kembali ke {label}</span>', $tombol);
        $this->assertStringContainsString('<TombolKembali href={tempat.induk.href} label={tempat.induk.label} />', $kepala);
        $this->assertStringContainsString('type PageHeaderProps = DasarKepala & ({ akar: true; merek?: boolean; induk?: never } | { induk: Induk; akar?: never; merek?: never });', $kepala);

        foreach ([$tombol, $kepala] as $isi) {
            $this->assertDoesNotMatchRegularExpression('/history\.back|window\.history|router\.back/', $isi);
        }
    }

    /**
     * Profil dibuka dari avatar 44 px di setiap akar tab (ruling W2-R1), bukan dari tab
     * keenam. Mulai 1024 px menu avatar di header layar lebar yang mengambil alih, jadi
     * avatar di kepala halaman disembunyikan di sana.
     */
    public function test_a_tab_root_reaches_the_profile_from_its_avatar(): void
    {
        $tautan = $this->tanpaKomentarJs(File::get(resource_path('js/components/ui/TautanProfil.tsx')));
        $kepala = $this->tanpaKomentarJs(File::get(resource_path('js/components/ui/PageHeader.tsx')));

        $this->assertStringContainsString('href={profile.url()}', $tautan);
        $this->assertStringContainsString('h-11 w-11', $tautan);
        $this->assertStringContainsString('<Avatar nama={auth.user.name} ukuran={44} />', $tautan);
        $this->assertStringContainsString('<span className="sr-only">Profil {auth.user.name}</span>', $tautan);
        $this->assertStringContainsString('{tempat.akar ? <TautanProfil className="lg:hidden" /> : null}', $kepala);
        $this->assertStringContainsString('{tempat.merek ? <Lockup className="lg:hidden" /> : null}', $kepala);
    }

    /**
     * Menu hamburger ponsel dilepas W2, jadi Moderasi, Admin, dan Keluar harus tetap
     * terjangkau dari Profil: pintunya dijaga peran, Keluar untuk semua.
     */
    public function test_the_profile_page_keeps_the_console_doors_and_sign_out(): void
    {
        $pintu = $this->tanpaKomentarJs(File::get(resource_path('js/components/akun/PintuAkun.tsx')));

        $this->assertStringContainsString('<PintuAkun />', File::get(resource_path('js/pages/Profile/Edit.tsx')));
        $this->assertStringContainsString('{pengguna.isModerator ? <TautanMenu href="/moderation">Moderasi</TautanMenu> : null}', $pintu);
        $this->assertStringContainsString('{pengguna.isAdmin ? <TautanMenu href="/admin/trails">Admin</TautanMenu> : null}', $pintu);
        $this->assertStringContainsString("onClick={() => router.post('/logout')}", $pintu);
        $this->assertMatchesRegularExpression('/>\s*Keluar\s*<\/button>/', $pintu);
    }

    /**
     * Isi setiap halaman berkerangka: semua di resources/js/pages kecuali Auth (GuestLayout)
     * dan Welcome (halaman depan tanpa navigasi aplikasi).
     *
     * @return array<string, string>
     */
    private function halamanBerkerangka(): array
    {
        $hasil = [];

        foreach ($this->berkasTsx('pages') as $berkas) {
            $nama = str_replace('\\', '/', $berkas->getRelativePathname());

            if (str_starts_with($nama, 'Auth/') || $nama === 'Welcome.tsx') {
                continue;
            }

            $hasil[$nama] = $this->tanpaKomentarJs($berkas->getContents());
        }

        ksort($hasil);

        return $hasil;
    }
}
```

Di `tests/Feature/DemoBadgeTest.php`, lencana Data contoh kini juga dipasang `PageHeader` (ponsel, di bawah judul):

Ganti:

```php
    public function test_every_app_layout_mounts_the_badge(): void
    {
        foreach (['js/layouts/AppLayout.tsx', 'js/components/trips/TataLetakPendakian.tsx'] as $berkas) {
            $isi = File::get(resource_path($berkas));

            $this->assertStringContainsString("import { LencanaDataContoh } from '@/components/ui/LencanaDataContoh';", $isi, $berkas);
```

dengan:

```php
    /**
     * Diperbarui W2: kepala halaman ikut memasang lencana, karena mulai kerangka W2 ponsel
     * tidak punya bilah atas global dan kepala halaman adalah satu-satunya baris yang
     * selalu ada di atas isi. Niatnya tetap: lencana tampil di setiap halaman berkerangka.
     */
    public function test_every_app_layout_mounts_the_badge(): void
    {
        foreach (['js/layouts/AppLayout.tsx', 'js/components/trips/TataLetakPendakian.tsx', 'js/components/ui/PageHeader.tsx'] as $berkas) {
            $isi = File::get(resource_path($berkas));

            $this->assertMatchesRegularExpression("/import \{ LencanaDataContoh \} from '(?:@\/components\/ui|\.)\/LencanaDataContoh';/", $isi, $berkas);
```

- [ ] **Step 2: Jalankan dan pastikan gagal.**

Run: `php artisan test --compact --filter='PageHeaderContractTest|DemoBadgeTest'`
Expected: FAIL. Pada simulasi rencana: 8 test, 2 lulus, 3 gagal (halaman belum memberi `akar` atau `induk`, akar tab
belum berlabel tab, `PageHeader` belum memasang `LencanaDataContoh`), 3 error (`TombolKembali.tsx`,
`TautanProfil.tsx`, dan `PintuAkun.tsx` belum ada).

- [ ] **Step 3: Tombol kembali dan tautan profil.** Buat `resources/js/components/ui/TombolKembali.tsx`:

```tsx
import { kelas } from '@/lib/kelas';
import { fokusTerlihat, umpanTekan } from './gaya';
import { Icon } from './Icon';
import { Tautan } from './Tautan';

interface TombolKembaliProps {
    /** Alamat induk logis halaman ini, bukan riwayat peramban. */
    href: string;
    /** Nama induknya, dibacakan "Kembali ke ...". */
    label: string;
}

/**
 * Tombol kembali halaman turunan (spec v3 bagian 5.1): lingkaran 44 px yang menuju induk
 * logisnya. Sengaja tautan biasa, bukan history.back(): pendaki yang membuka detail jalur
 * dari tautan bagikan, notifikasi surel, atau tab baru tidak punya riwayat, dan tombol
 * kembali yang tidak membawa ke mana pun adalah kontrol mati.
 */
export function TombolKembali({ href, label }: TombolKembaliProps) {
    return (
        <Tautan
            href={href}
            className={kelas(
                'inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-subtle bg-surface text-primary hover:bg-surface-sunken',
                fokusTerlihat,
                umpanTekan,
            )}
        >
            <Icon name="arrow-left" size={20} />
            <span className="sr-only">Kembali ke {label}</span>
        </Tautan>
    );
}
```

Buat `resources/js/components/ui/TautanProfil.tsx`:

```tsx
import { usePage } from '@inertiajs/react';
import { kelas } from '@/lib/kelas';
import { profile } from '@/routes';
import type { PropsBersama } from '@/types';
import { Avatar } from './Avatar';
import { fokusTerlihat, umpanTekan } from './gaya';
import { Tautan } from './Tautan';

/**
 * Avatar 44 px menuju Profil (spec v3 bagian 5.1, ruling W2-R1): Profil dibuka dari avatar
 * di setiap akar tab, bukan dari tab keenam. Avatar sendiri hiasan; nama tautannya dibawa
 * teks pembaca layar.
 */
export function TautanProfil({ className }: { className?: string }) {
    const { auth } = usePage<PropsBersama>().props;

    if (!auth.user) {
        return null;
    }

    return (
        <Tautan href={profile.url()} className={kelas('inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full', fokusTerlihat, umpanTekan, className)}>
            <Avatar nama={auth.user.name} ukuran={44} />
            <span className="sr-only">Profil {auth.user.name}</span>
        </Tautan>
    );
}
```

- [ ] **Step 4: Kepala halaman.** Ganti seluruh isi `resources/js/components/ui/PageHeader.tsx` dengan kode berikut.
Satu baris flex yang membungkus, dan h1 hanya satu elemen yang posisinya diatur urutan flex. `PageHeader` tidak memuat
`<Icon` atau `<svg` (`IconSystemTest`).

```tsx
import type { ReactNode } from 'react';
import { Lockup } from '@/components/brand/Lockup';
import { kelas } from '@/lib/kelas';
import { LencanaDataContoh } from './LencanaDataContoh';
import { TautanProfil } from './TautanProfil';
import { TombolKembali } from './TombolKembali';

/** Induk logis halaman turunan: tujuan tombol kembali dan nama yang dibacakan. */
export interface Induk {
    href: string;
    label: string;
}

interface DasarKepala {
    title: string;
    description?: string;
    /** Aksi kontekstual di kanan baris kepala, misalnya lonceng Pengingat atau tautan Simpanan. */
    aksi?: ReactNode;
    children?: ReactNode;
}

/**
 * Akar tab memberi `akar` (Beranda menambah `merek`), halaman turunan memberi `induk`.
 * Salah satunya wajib, jadi TypeScript menolak halaman baru yang lupa menyebut tempatnya
 * di peta navigasi (PageHeaderContractTest menjaga siapa yang boleh menjadi akar).
 */
type PageHeaderProps = DasarKepala & ({ akar: true; merek?: boolean; induk?: never } | { induk: Induk; akar?: never; merek?: never });

/**
 * Header per halaman (spec v3 bagian 5.1). Ponsel tidak punya bilah atas global, jadi
 * baris ini yang membawa tempat pendaki:
 * - akar tab: h1 (28 px, bobot 800), aksi kontekstual, avatar 44 px ke Profil;
 * - Beranda (`merek`): lockup, aksi, dan avatar di baris atas, h1 sapaan di bawahnya;
 * - halaman turunan: tombol kembali 44 px ke induk logisnya dan aksi, h1 di bawahnya.
 * Satu baris flex yang membungkus, dan h1 hanya satu elemen: posisinya diatur urutan flex,
 * tidak digandakan. Mulai 1024 px header layar lebar membawa lockup, avatar, dan lencana
 * Data contoh, jadi ketiganya disembunyikan di sini dan sapaan Beranda naik ke baris atas.
 * Lencana Data contoh duduk di bawah judul, bukan di atasnya: label di atas h1 terbaca
 * sebagai eyebrow, dan baris atas di 360 px tidak muat untuk judul, aksi, avatar, dan
 * lencana sekaligus.
 */
export function PageHeader({ title, description, aksi, children, ...tempat }: PageHeaderProps) {
    const letakJudul = tempat.induk ? 'order-last basis-full' : tempat.merek ? 'order-last basis-full lg:order-first lg:basis-0 lg:flex-1' : 'flex-1';

    return (
        <header className="mb-6">
            <div className="flex flex-wrap items-center gap-x-3 gap-y-3">
                {tempat.induk ? <TombolKembali href={tempat.induk.href} label={tempat.induk.label} /> : null}
                {tempat.merek ? <Lockup className="lg:hidden" /> : null}
                <div className={kelas('min-w-0', letakJudul)}>
                    <h1 className="text-balance text-judul text-primary">{title}</h1>
                </div>
                {aksi || tempat.akar ? (
                    <div className="ms-auto flex shrink-0 items-center gap-2">
                        {aksi}
                        {tempat.akar ? <TautanProfil className="lg:hidden" /> : null}
                    </div>
                ) : null}
            </div>
            <div className="mt-2 empty:hidden lg:hidden">
                <LencanaDataContoh />
            </div>
            {description ? <p className="mt-2 max-w-prose text-isi text-secondary">{description}</p> : null}
            {children}
        </header>
    );
}
```

- [ ] **Step 5: Pintu akun di Profil.** Buat `resources/js/components/akun/PintuAkun.tsx`:

```tsx
import { router, usePage } from '@inertiajs/react';
import { Card } from '@/components/ui/Card';
import { TautanMenu, tombolMenu } from '@/layouts/NavLinks';
import type { PropsBersama } from '@/types';

/**
 * Pintu konsol sesuai peran dan Keluar (spec v3 bagian 6.15). Kerangka W2 melepas menu
 * hamburger ponsel, dan di ponsel avatar langsung membuka Profil, jadi Moderasi, Admin,
 * dan Keluar tinggal di sini; di layar lebar menu avatar menyediakan hal yang sama.
 * Peran hanya menentukan apa yang ditampilkan: middleware role dan Policy tetap menolak
 * di server.
 */
export function PintuAkun() {
    const { auth } = usePage<PropsBersama>().props;
    const pengguna = auth.user;

    if (!pengguna) {
        return null;
    }

    return (
        <Card title="Akun">
            <div className="-mx-2 flex flex-col">
                {pengguna.isModerator ? <TautanMenu href="/moderation">Moderasi</TautanMenu> : null}
                {pengguna.isAdmin ? <TautanMenu href="/admin/trails">Admin</TautanMenu> : null}
                <button type="button" onClick={() => router.post('/logout')} className={tombolMenu}>
                    Keluar
                </button>
            </div>
        </Card>
    );
}
```

- [ ] **Step 6: Setiap halaman menyebut tempatnya.** Tag pembuka `PageHeader` di tiap halaman berikut mendapat `akar`
atau `induk` sebagai atribut pertama, lalu impor rutenya bila belum ada. Pada tag satu baris, atribut disisipkan di
baris yang sama; pada tag banyak baris, atribut menjadi baris baru sebelum `title=`. Induk logisnya:

- Jelajah untuk detail jalur, Simpanan, dan halaman menunggu kurator.
- Simpanan untuk Bandingkan, tempat jalur dipilih untuk dibandingkan, walau tab yang menyala Trip; keputusan W1 itu
  dijaga `NavigationConsistencyTest`.
- Trip untuk rincian trip, rencanakan trip, target, dan rekomendasi. Rincian trip untuk ubah trip dan persiapan.
  Persiapan untuk cek kesiapan.
- Progres untuk riwayat, ringkasan pendakian, dan laporan kondisi.
- Beranda untuk kabar gunung, Profil, dan pameran primitif.
- Profil untuk konsol admin (pintu Admin ada di Profil dan di menu avatar), antrean moderasi, kontribusi data jalur,
  dan pengisian profil awal.
- Pos jalur dan geometri jalur: Kelola Jalur bagi admin, Kontribusi Data Jalur bagi kontributor.

`Profile/Edit.tsx` juga memasang `<PintuAkun />` sesudah kartu Tampilan. Terapkan blok berikut per berkas:

`resources/js/pages/Admin/Analytics.tsx`:

1. Ganti:

```tsx
import { AppLayout } from '@/layouts/AppLayout';
```

dengan:

```tsx
import { AppLayout } from '@/layouts/AppLayout';
import { profile } from '@/routes';
```

2. Ganti:

```tsx
                <PageHeader title="Analitik" description="Funnel perencanaan pendakian, dari profil sampai laporan kondisi." />
```

dengan:

```tsx
                <PageHeader induk={{ href: profile.url(), label: 'Profil' }} title="Analitik" description="Funnel perencanaan pendakian, dari profil sampai laporan kondisi." />
```

`resources/js/pages/Admin/AuditLogs.tsx`:

1. Ganti:

```tsx
import { AppLayout } from '@/layouts/AppLayout';
```

dengan:

```tsx
import { AppLayout } from '@/layouts/AppLayout';
import { profile } from '@/routes';
```

2. Ganti:

```tsx
                <PageHeader
```

dengan:

```tsx
                <PageHeader
                    induk={{ href: profile.url(), label: 'Profil' }}
```

`resources/js/pages/Admin/Authorities.tsx`:

1. Ganti:

```tsx
import { AppLayout } from '@/layouts/AppLayout';
```

dengan:

```tsx
import { AppLayout } from '@/layouts/AppLayout';
import { profile } from '@/routes';
```

2. Ganti:

```tsx
                <PageHeader
```

dengan:

```tsx
                <PageHeader
                    induk={{ href: profile.url(), label: 'Profil' }}
```

`resources/js/pages/Admin/Checkpoints.tsx`:

Ganti:

```tsx
                <PageHeader title="Kelola Checkpoint" description={trail.name} />
```

dengan:

```tsx
                <PageHeader induk={admin ? { href: daftarJalur.url(), label: 'Kelola Jalur' } : { href: contribute.url(), label: 'Kontribusi Data Jalur' }} title="Kelola Checkpoint" description={trail.name} />
```

`resources/js/pages/Admin/Credentials.tsx`:

1. Ganti:

```tsx
import { AppLayout } from '@/layouts/AppLayout';
```

dengan:

```tsx
import { AppLayout } from '@/layouts/AppLayout';
import { profile } from '@/routes';
```

2. Ganti:

```tsx
                <PageHeader
```

dengan:

```tsx
                <PageHeader
                    induk={{ href: profile.url(), label: 'Profil' }}
```

`resources/js/pages/Admin/DataSources.tsx`:

1. Ganti:

```tsx
import { AppLayout } from '@/layouts/AppLayout';
```

dengan:

```tsx
import { AppLayout } from '@/layouts/AppLayout';
import { profile } from '@/routes';
```

2. Ganti:

```tsx
                <PageHeader title="Kelola Sumber Data" description="Setiap data penting harus memiliki sumber, tipe sumber, waktu pengambilan, dan status verifikasi." />
```

dengan:

```tsx
                <PageHeader induk={{ href: profile.url(), label: 'Profil' }} title="Kelola Sumber Data" description="Setiap data penting harus memiliki sumber, tipe sumber, waktu pengambilan, dan status verifikasi." />
```

`resources/js/pages/Admin/Health.tsx`:

1. Ganti:

```tsx
import { kelas } from '@/lib/kelas';
```

dengan:

```tsx
import { kelas } from '@/lib/kelas';
import { profile } from '@/routes';
```

2. Ganti:

```tsx
                <PageHeader
```

dengan:

```tsx
                <PageHeader
                    induk={{ href: profile.url(), label: 'Profil' }}
```

`resources/js/pages/Admin/Mountains.tsx`:

1. Ganti:

```tsx
import { AppLayout } from '@/layouts/AppLayout';
```

dengan:

```tsx
import { AppLayout } from '@/layouts/AppLayout';
import { profile } from '@/routes';
```

2. Ganti:

```tsx
                <PageHeader title="Kelola Gunung" description="Tambah, ubah, atau arsipkan data gunung. Setiap perubahan tercatat di audit log." />
```

dengan:

```tsx
                <PageHeader induk={{ href: profile.url(), label: 'Profil' }} title="Kelola Gunung" description="Tambah, ubah, atau arsipkan data gunung. Setiap perubahan tercatat di audit log." />
```

`resources/js/pages/Admin/OfficialStatuses.tsx`:

1. Ganti:

```tsx
import { AppLayout } from '@/layouts/AppLayout';
```

dengan:

```tsx
import { AppLayout } from '@/layouts/AppLayout';
import { profile } from '@/routes';
```

2. Ganti:

```tsx
                <PageHeader
```

dengan:

```tsx
                <PageHeader
                    induk={{ href: profile.url(), label: 'Profil' }}
```

`resources/js/pages/Admin/Permits.tsx`:

1. Ganti:

```tsx
import { AppLayout } from '@/layouts/AppLayout';
```

dengan:

```tsx
import { AppLayout } from '@/layouts/AppLayout';
import { profile } from '@/routes';
```

2. Ganti:

```tsx
                <PageHeader
```

dengan:

```tsx
                <PageHeader
                    induk={{ href: profile.url(), label: 'Profil' }}
```

`resources/js/pages/Admin/TrailGeometry.tsx`:

Ganti:

```tsx
                <PageHeader title="Impor Geometri Jalur" description={trail.name} />
```

dengan:

```tsx
                <PageHeader induk={admin ? { href: daftarJalur.url(), label: 'Kelola Jalur' } : { href: contribute.url(), label: 'Kontribusi Data Jalur' }} title="Impor Geometri Jalur" description={trail.name} />
```

`resources/js/pages/Admin/Trails.tsx`:

1. Ganti:

```tsx
import { AppLayout } from '@/layouts/AppLayout';
```

dengan:

```tsx
import { AppLayout } from '@/layouts/AppLayout';
import { profile } from '@/routes';
```

2. Ganti:

```tsx
                <PageHeader title="Kelola Jalur" description="Jalur hanya dapat dipublikasikan jika sumber data, karakteristik, checkpoint, dan status resmi sudah lengkap." />
```

dengan:

```tsx
                <PageHeader induk={{ href: profile.url(), label: 'Profil' }} title="Kelola Jalur" description="Jalur hanya dapat dipublikasikan jika sumber data, karakteristik, checkpoint, dan status resmi sudah lengkap." />
```

`resources/js/pages/Admin/Usability.tsx`:

1. Ganti:

```tsx
import { AppLayout } from '@/layouts/AppLayout';
```

dengan:

```tsx
import { AppLayout } from '@/layouts/AppLayout';
import { profile } from '@/routes';
```

2. Ganti:

```tsx
                <PageHeader
```

dengan:

```tsx
                <PageHeader
                    induk={{ href: profile.url(), label: 'Profil' }}
```

`resources/js/pages/Community/ConditionReport.tsx`:

1. Ganti:

```tsx
import { AppLayout } from '@/layouts/AppLayout';
```

dengan:

```tsx
import { AppLayout } from '@/layouts/AppLayout';
import { progress } from '@/routes';
```

2. Ganti:

```tsx
                <PageHeader
```

dengan:

```tsx
                <PageHeader
                    induk={{ href: progress.url(), label: 'Progres' }}
```

`resources/js/pages/Community/HikeSummary.tsx`:

1. Ganti:

```tsx
import { history } from '@/routes';
```

dengan:

```tsx
import { history, progress } from '@/routes';
```

2. Ganti:

```tsx
                <PageHeader title={jalur.nama} description={jalur.wilayah || undefined}>
```

dengan:

```tsx
                <PageHeader induk={{ href: progress.url(), label: 'Progres' }} title={jalur.nama} description={jalur.wilayah || undefined}>
```

`resources/js/pages/Community/HikerProgress.tsx`:

Ganti:

```tsx
                <PageHeader title="Progres Pendakian" description="Catatan perjalanan Anda sendiri. Tidak dibandingkan dengan siapa pun." />
```

dengan:

```tsx
                <PageHeader akar title="Progres" description="Catatan perjalanan Anda sendiri. Tidak dibandingkan dengan siapa pun." />
```

`resources/js/pages/Community/HikingHistory.tsx`:

1. Ganti:

```tsx
import { AppLayout } from '@/layouts/AppLayout';
```

dengan:

```tsx
import { AppLayout } from '@/layouts/AppLayout';
import { progress } from '@/routes';
```

2. Ganti:

```tsx
                <PageHeader title="Riwayat Pendakian" description="Catatan perjalanan Anda, kelengkapan persiapan saat itu, dan laporan kondisi yang Anda kirim." />
```

dengan:

```tsx
                <PageHeader induk={{ href: progress.url(), label: 'Progres' }} title="Riwayat Pendakian" description="Catatan perjalanan Anda, kelengkapan persiapan saat itu, dan laporan kondisi yang Anda kirim." />
```

`resources/js/pages/Community/TrailContribution.tsx`:

1. Ganti:

```tsx
import { contribute } from '@/routes';
```

dengan:

```tsx
import { contribute, profile } from '@/routes';
```

2. Ganti:

```tsx
                <PageHeader
```

dengan:

```tsx
                <PageHeader
                    induk={{ href: profile.url(), label: 'Profil' }}
```

`resources/js/pages/Community/TrailNews.tsx`:

1. Ganti:

```tsx
import { AppLayout } from '@/layouts/AppLayout';
```

dengan:

```tsx
import { AppLayout } from '@/layouts/AppLayout';
import { dashboard } from '@/routes';
```

2. Ganti:

```tsx
                <PageHeader title="Kabar Jalur" description="Yang berubah di gunung yang Anda ikuti. Berisi keadaan gunung, bukan aktivitas pendaki lain." />
```

dengan:

```tsx
                <PageHeader induk={{ href: dashboard.url(), label: 'Beranda' }} title="Kabar Jalur" description="Yang berubah di gunung yang Anda ikuti. Berisi keadaan gunung, bukan aktivitas pendaki lain." />
```

`resources/js/pages/Dashboard.tsx`:

Ganti:

```tsx
                <PageHeader
```

dengan:

```tsx
                <PageHeader
                    akar
                    merek
```

`resources/js/pages/Dev/Primitif.tsx`:

1. Ganti:

```tsx
import { permukaan } from '@/layouts/NavLinks';
```

dengan:

```tsx
import { permukaan } from '@/layouts/NavLinks';
import { dashboard } from '@/routes';
```

2. Ganti:

```tsx
                <PageHeader
```

dengan:

```tsx
                <PageHeader
                    induk={{ href: dashboard.url(), label: 'Beranda' }}
```

`resources/js/pages/Goals/Create.tsx`:

1. Ganti:

```tsx
import { store } from '@/routes/goals';
```

dengan:

```tsx
import { store } from '@/routes/goals';
import { index as tripsIndex } from '@/routes/trips';
```

2. Ganti:

```tsx
                <PageHeader
```

dengan:

```tsx
                <PageHeader
                    induk={{ href: tripsIndex.url(), label: 'Trip' }}
```

`resources/js/pages/Moderation/Queue.tsx`:

1. Ganti:

```tsx
import { AppLayout } from '@/layouts/AppLayout';
```

dengan:

```tsx
import { AppLayout } from '@/layouts/AppLayout';
import { profile } from '@/routes';
```

2. Ganti:

```tsx
                <PageHeader title="Moderasi Laporan" description="Laporan komunitas hanya tampil untuk publik setelah disetujui. Laporan tidak pernah mengubah status resmi." />
```

dengan:

```tsx
                <PageHeader induk={{ href: profile.url(), label: 'Profil' }} title="Moderasi Laporan" description="Laporan komunitas hanya tampil untuk publik setelah disetujui. Laporan tidak pernah mengubah status resmi." />
```

`resources/js/pages/Onboarding/ProfileSetup.tsx`:

1. Ganti:

```tsx
import { AppLayout } from '@/layouts/AppLayout';
```

dengan:

```tsx
import { AppLayout } from '@/layouts/AppLayout';
import { profile } from '@/routes';
```

2. Ganti:

```tsx
                <PageHeader
```

dengan:

```tsx
                <PageHeader
                    induk={{ href: profile.url(), label: 'Profil' }}
```

`resources/js/pages/Profile/Edit.tsx`:

1. Ganti:

```tsx
import { HapusAkun } from '@/components/akun/HapusAkun';
```

dengan:

```tsx
import { HapusAkun } from '@/components/akun/HapusAkun';
import { PintuAkun } from '@/components/akun/PintuAkun';
```

2. Ganti:

```tsx
import { AppLayout } from '@/layouts/AppLayout';
```

dengan:

```tsx
import { AppLayout } from '@/layouts/AppLayout';
import { dashboard } from '@/routes';
```

3. Ganti:

```tsx
                <PageHeader title="Profil" />
```

dengan:

```tsx
                <PageHeader induk={{ href: dashboard.url(), label: 'Beranda' }} title="Profil" />
```

4. Ganti:

```tsx
                <div className="border-t border-subtle pt-6">
```

dengan:

```tsx
                <PintuAkun />

                <div className="border-t border-subtle pt-6">
```

`resources/js/pages/Recommendations/Show.tsx`:

1. Ganti:

```tsx
import { compare, index as trailsIndex } from '@/routes/trails';
```

dengan:

```tsx
import { compare, index as trailsIndex } from '@/routes/trails';
import { index as tripsIndex } from '@/routes/trips';
```

2. Ganti:

```tsx
                <PageHeader
```

dengan:

```tsx
                <PageHeader
                    induk={{ href: tripsIndex.url(), label: 'Trip' }}
```

`resources/js/pages/Track/Index.tsx`:

Ganti:

```tsx
                <PageHeader
```

dengan:

```tsx
                <PageHeader
                    akar
```

`resources/js/pages/Trails/Awaiting.tsx`:

Ganti:

```tsx
                <PageHeader title={trail.name}>
```

dengan:

```tsx
                <PageHeader induk={{ href: trailsIndex.url(), label: 'Jelajah' }} title={trail.name}>
```

`resources/js/pages/Trails/Compare.tsx`:

Ganti:

```tsx
                <PageHeader
```

dengan:

```tsx
                <PageHeader
                    induk={{ href: saved.url(), label: 'Simpanan' }}
```

`resources/js/pages/Trails/Index.tsx`:

Ganti:

```tsx
                <PageHeader title="Jelajahi Jalur" description="Telusuri jalur secara manual. Gunakan ini jika Anda ingin membandingkan sendiri tanpa melalui rekomendasi." />
```

dengan:

```tsx
                <PageHeader akar title="Jelajah" description="Telusuri jalur secara manual. Gunakan ini jika Anda ingin membandingkan sendiri tanpa melalui rekomendasi." />
```

`resources/js/pages/Trails/Saved.tsx`:

Ganti:

```tsx
                <PageHeader
```

dengan:

```tsx
                <PageHeader
                    induk={{ href: trailsIndex.url(), label: 'Jelajah' }}
```

`resources/js/pages/Trails/Show.tsx`:

1. Ganti:

```tsx
import { compare, timbang } from '@/routes/trails';
```

dengan:

```tsx
import { compare, index as trailsIndex, timbang } from '@/routes/trails';
```

2. Ganti:

```tsx
                <PageHeader title={trail.name} description={trail.description ?? undefined}>
```

dengan:

```tsx
                <PageHeader induk={{ href: trailsIndex.url(), label: 'Jelajah' }} title={trail.name} description={trail.description ?? undefined}>
```

`resources/js/pages/Trips/Create.tsx`:

1. Ganti:

```tsx
import { store } from '@/routes/trips';
```

dengan:

```tsx
import { index as tripsIndex, store } from '@/routes/trips';
```

2. Ganti:

```tsx
                <PageHeader
```

dengan:

```tsx
                <PageHeader
                    induk={{ href: tripsIndex.url(), label: 'Trip' }}
```

`resources/js/pages/Trips/Edit.tsx`:

Ganti:

```tsx
                <PageHeader title="Ubah Rencana Trip" description={trip.jalur} />
```

dengan:

```tsx
                <PageHeader induk={{ href: show.url(trip.id), label: 'Rincian trip' }} title="Ubah Rencana Trip" description={trip.jalur} />
```

`resources/js/pages/Trips/Index.tsx`:

Ganti:

```tsx
                <PageHeader title="Rencana Trip Saya" description="Semua rencana pendakian Anda beserta status kesiapannya." />
```

dengan:

```tsx
                <PageHeader akar title="Trip" description="Semua rencana pendakian Anda beserta status kesiapannya." />
```

`resources/js/pages/Trips/Preparation.tsx`:

1. Ganti:

```tsx
import { readiness } from '@/routes/trips';
```

dengan:

```tsx
import { readiness, show } from '@/routes/trips';
```

2. Ganti:

```tsx
                <PageHeader title="Persiapan Pendakian" description={trip.keterangan} />
```

dengan:

```tsx
                <PageHeader induk={{ href: show.url(trip.id), label: 'Rincian trip' }} title="Persiapan Pendakian" description={trip.keterangan} />
```

`resources/js/pages/Trips/Readiness.tsx`:

Ganti:

```tsx
                <PageHeader title="Kesiapan Pendakian" description={trip.keterangan} />
```

dengan:

```tsx
                <PageHeader induk={{ href: preparation.url(trip.id), label: 'Persiapan' }} title="Kesiapan Pendakian" description={trip.keterangan} />
```

`resources/js/pages/Trips/Show.tsx`:

1. Ganti:

```tsx
import { cancel, complete, hike, start } from '@/routes/trips';
```

dengan:

```tsx
import { cancel, complete, hike, index as tripsIndex, start } from '@/routes/trips';
```

2. Ganti:

```tsx
                <PageHeader title={trip.name} description={`${trip.trail} - ${trip.mountain}`}>
```

dengan:

```tsx
                <PageHeader induk={{ href: tripsIndex.url(), label: 'Trip' }} title={trip.name} description={`${trip.trail} - ${trip.mountain}`}>
```

- [ ] **Step 7: Jalankan test tugas ini dan tetangganya sampai lulus.**

Run: `php artisan test --compact --filter='PageHeaderContractTest|DemoBadgeTest|PageTitleTest|InertiaLayoutTest|EveryRouteRendersTest|PageSmokeTest'`
Expected: PASS, 24 test.

- [ ] **Step 8: Gerbang tugas.**

Run: `vendor/bin/pint --format agent tests/Feature/PageHeaderContractTest.php tests/Feature/DemoBadgeTest.php`
Expected: `{"tool":"pint","result":"passed"}` atau berkas dirapikan tanpa perubahan makna.

Run: `npx tsc --noEmit`
Expected: keluar tanpa keluaran galat. Tipe `PageHeader` menolak halaman yang tidak memberi tepat satu dari `akar` atau
`induk`.

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal. Pada simulasi rencana: 1.564 test, 1.562 lulus, 2 dilewati.

- [ ] **Step 9: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 2: Kerangka ponsel dan layar lebar dari satu daftar tab

Induk W2 butir 2 dan 3 (bagian header), DK-33.

- Di bawah 1024 px, `AppLayout` tidak menggambar bilah atas global dan memasang bilah tab pil `BilahTab` dari W1.
- Mulai 1024 px, `KepalaLebar` 68 px menempel di atas: lockup, navigasi segmen lima tab di tengah, lencana Data contoh,
  dan menu avatar popover (W2-K9).
- Kedua bentuk digambar `itemTab()`, jadi daftar, urutan, dan tab aktifnya sama (spec bagian 5).
- Halaman tugas memberi `<AppLayout bilahTab={false}>` (W2-K19).
- `app.css` menurunkan jarak lapisan dari elemen yang ada dan memberi `scroll-padding` (W2-K7).

Menu garis tiga lama hilang, dan pintunya sudah dipindah Tugas 1.

**Files:**
- Create: `resources/js/layouts/KepalaLebar.tsx`, `resources/js/layouts/MenuAkun.tsx`
- Modify: `resources/js/layouts/NavLinks.tsx` (tulis ulang), `resources/js/layouts/AppLayout.tsx` (tulis ulang), `resources/js/components/ui/BilahTab.tsx`, `resources/css/app.css` (satu blok baru sesudah aturan `html:has(dialog[open])`), `resources/js/pages/Trails/Show.tsx`, `resources/js/pages/Trips/Create.tsx`, `resources/js/pages/Trails/Index.tsx`
- Test: `tests/Feature/NavigationConsistencyTest.php` (tulis ulang), `tests/Feature/IconSystemTest.php`, `tests/Feature/InertiaLayoutTest.php`, `tests/Feature/DemoBadgeTest.php`, `tests/Feature/LongInputLayoutTest.php`

**Interfaces:**
- Consumes: `BilahTab({ item, label?, mengambang? })` dan `ItemTab { label; href; ikon: NamaIkon; aktif }` (W1); `JudulHalaman`; `Lockup`; `Avatar`; `Icon({ name, size, isi? })`; `LencanaDataContoh`; `PageHeader` dan `PintuAkun` dari Tugas 1.
- Produces:
  - `NavLinks.tsx`: `permukaan` (lima entri, tidak berubah), `itemTab(jalur: string): ItemTab[]`, `tombolMenu`, `TautanMenu({ href, children })`.
  - `AppLayout({ bilahTab = true, children })`; `KepalaLebar({ item })`; `MenuAkun()`.
  - Variabel CSS di `:root`: `--bilah-tab`, `--strip-pendakian` (bawaan `0px`; Tugas 4 menulis nilainya), `--kepala-atas`, `--nav-bawah = --bilah-tab + --strip-pendakian`.
  - Penanda yang dibaca CSS: `nav[data-bilah-tab]`, `[data-kepala-lebar]`, dan `[data-strip-pendakian]` (Tugas 4).
  - Tugas 4, 5, dan 6 menyisipkan ke `AppLayout` hasil tugas ini.

- [ ] **Step 1: Tulis test yang gagal.** Ganti seluruh isi `tests/Feature/NavigationConsistencyTest.php` dengan kode
berikut. Test lama membaca menu dari `AppLayout.tsx` dan `NavLinks.tsx`, yang tidak lagi memuatnya. Niatnya dijaga di
sumber barunya, dengan alasan di docblock, termasuk `test_a_hiker_never_sees_the_admin_entry`.

```php
<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Menu ponsel dan menu layar lebar harus menyajikan hal yang sama dalam urutan yang sama.
 *
 * Pada navigasi Blade lama keduanya ditulis terpisah di berkas yang sama, dan item
 * kedua dan ketiga sempat tertukar: pengguna yang berpindah dari ponsel ke laptop
 * menemukan menunya di tempat berbeda. Navigasi React menutup kelas cacat itu dengan
 * satu daftar, `permukaan` di NavLinks.tsx, yang digambar oleh tab atas dan navigasi
 * bawah ponsel. Isi daftarnya mengikuti arsitektur informasi v2 pemilik (DK-25,
 * 25 September 2026): Home, Explore, Trips, Track, Progress; Profil dan Community bukan tab.
 *
 * Diperbarui W2 (spec v3 bagian 5, ruling W2-R1): kedua menu kini bilah tab pil ponsel dan
 * navigasi segmen header layar lebar, keduanya dari itemTab(). Menu hamburger ponsel
 * dilepas: Profil dibuka dari avatar, dan pintu Moderasi, Admin, serta Keluar tinggal di
 * menu avatar layar lebar dan di halaman Profil. Niat setiap test tetap sama.
 */
class NavigationConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_both_menus_list_the_same_destinations_in_the_same_order(): void
    {
        $layout = File::get(resource_path('js/layouts/AppLayout.tsx'));
        $kepala = File::get(resource_path('js/layouts/KepalaLebar.tsx'));

        $this->assertSame(1, substr_count($layout, 'itemTab('), 'Daftar tab dihitung sekali per halaman.');
        $this->assertStringContainsString('const tab = itemTab(jalur);', $layout);
        $this->assertStringContainsString('<KepalaLebar item={tab} />', $layout, 'Navigasi segmen layar lebar tidak lagi digambar dari daftar yang sama.');
        $this->assertStringContainsString('{bilahTab ? <BilahTab item={tab} /> : null}', $layout, 'Bilah tab ponsel tidak lagi digambar dari daftar yang sama.');
        $this->assertStringContainsString('<NavigasiSegmen item={item} />', $kepala);
        $this->assertStringContainsString('{item.map((t) => (', $kepala);

        foreach ([$layout, $kepala] as $isi) {
            $this->assertStringNotContainsString('permukaan.map(', $isi, 'Ada menu permukaan yang dirakit sendiri di luar itemTab().');
        }

        // Profil bukan tab: avatar di kepala akar tab (ponsel) dan menu avatar (layar lebar).
        $this->assertStringContainsString('<TautanMenu href={profile.url()}>Profil</TautanMenu>', File::get(resource_path('js/layouts/MenuAkun.tsx')));
        $this->assertStringContainsString('{tempat.akar ? <TautanProfil className="lg:hidden" /> : null}', File::get(resource_path('js/components/ui/PageHeader.tsx')));
    }

    /**
     * Urutan tab adalah urutan pemilik: Beranda dulu karena ia pintu personal, lalu
     * menemukan, merencanakan, mendaki, dan melihat perkembangan diri.
     */
    public function test_the_order_follows_the_owners_information_architecture(): void
    {
        $isi = $this->daftarPermukaan();

        preg_match_all('/href: ([A-Za-z]+)\.url\(\)/', $isi, $tujuan);

        $this->assertSame(['dashboard', 'trailsIndex', 'tripsIndex', 'track', 'progress'], $tujuan[1]);

        // Nama lokal di atas harus menunjuk rute yang dimaksud, bukan sekadar bernama mirip.
        $navigasi = File::get(resource_path('js/layouts/NavLinks.tsx'));
        $this->assertStringContainsString("import { dashboard, progress, track } from '@/routes';", $navigasi);
        $this->assertStringContainsString("import { index as trailsIndex } from '@/routes/trails';", $navigasi);
        $this->assertStringContainsString("import { index as tripsIndex } from '@/routes/trips';", $navigasi);
    }

    /**
     * Label berbahasa Indonesia untuk lima tab pemilik (spec 2026-09-25, asumsi A1).
     */
    public function test_the_react_navigation_lists_the_same_five_surfaces_in_order(): void
    {
        preg_match_all("/label: '([^']+)'/", $this->daftarPermukaan(), $cocok);

        $this->assertSame(['Beranda', 'Jelajah', 'Trip', 'Mendaki', 'Progres'], $cocok[1]);
    }

    /**
     * Banding milik tab Trip (TRIPS > Compare). Path-nya di bawah /trails, jadi tanpa
     * pengecualian Jelajah ikut menyala dan pembaca tidak tahu tab mana yang ia buka.
     */
    public function test_the_compare_page_lights_trip_not_jelajah(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('trails.compare'))
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Trails/Compare'));

        $this->assertStringContainsString("!p.startsWith('/trails/compare')", $this->aturan('Jelajah'), 'Jelajah ikut menyala di halaman banding.');
        $this->assertStringContainsString("p.startsWith('/trails/compare')", $this->aturan('Trip'), 'Tab Trip seharusnya menyala di halaman banding.');
    }

    /**
     * Daftar tersimpan milik Jelajah (EXPLORE > Saved), bukan tab Trip.
     */
    public function test_the_saved_list_belongs_to_jelajah(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('trails.saved'))
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Trails/Saved'));

        $this->assertStringNotContainsString('/trails/saved', $this->aturan('Jelajah'), 'Jelajah tidak boleh mengecualikan daftar tersimpan.');
        $this->assertStringNotContainsString('/trails/saved', $this->aturan('Trip'), 'Daftar tersimpan bukan bagian tab Trip.');
    }

    /**
     * Mode pendakian satu trip tinggal di /trips/{id}/hike, di bawah path tab Trip. Tanpa
     * pengecualian, dua tab menyala ketika pendaki sedang di jalur.
     */
    public function test_hike_mode_lights_mendaki_not_trip(): void
    {
        $this->assertStringContainsString('!modePendakian(p)', $this->aturan('Trip'));
        $this->assertStringContainsString('modePendakian(p)', $this->aturan('Mendaki'));
        $this->assertStringContainsString("p.startsWith('/mendaki')", $this->aturan('Mendaki'));
    }

    /**
     * Pendaki tidak pernah melihat pintu Admin maupun Moderasi.
     *
     * Menu React menampilkan keduanya hanya bila props bersama menyatakan perannya, jadi
     * yang dijaga adalah props yang dikirim server untuk pendaki biasa dan syarat di setiap
     * tempat pintunya muncul. Diperbarui W2: tempatnya kini menu avatar layar lebar
     * (MenuAkun.tsx) dan kartu Akun di Profil (PintuAkun.tsx), bukan dua menu di AppLayout.
     */
    public function test_a_hiker_never_sees_the_admin_entry(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee(route('admin.trails'), escape: false)
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('auth.user.isAdmin', false)
                ->where('auth.user.isModerator', false));

        $pintu = File::get(resource_path('js/layouts/MenuAkun.tsx')).File::get(resource_path('js/components/akun/PintuAkun.tsx'));

        preg_match_all('/\{([^{}]*)\?\s*<TautanMenu href="\/admin\/trails">Admin</', $pintu, $admin);
        preg_match_all('/\{([^{}]*)\?\s*<TautanMenu href="\/moderation">Moderasi</', $pintu, $moderasi);

        $this->assertSame(['pengguna.isAdmin ', 'pengguna.isAdmin '], $admin[1], 'Pintu Admin harus dijaga peran di menu avatar dan di Profil.');
        $this->assertSame(['pengguna.isModerator ', 'pengguna.isModerator '], $moderasi[1], 'Pintu Moderasi harus dijaga peran di menu avatar dan di Profil.');
    }

    /**
     * Menu avatar diuji per peran (rencana induk W2 butir 3): props peran yang menyalakan
     * pintunya dikirim server sesuai peran sebenarnya, dan admin juga moderator.
     *
     * @return array<string, array{0: UserRole, 1: bool, 2: bool}>
     */
    public static function peran(): array
    {
        return [
            'pendaki' => [UserRole::HIKER, false, false],
            'moderator' => [UserRole::MODERATOR, true, false],
            'admin' => [UserRole::ADMIN, true, true],
        ];
    }

    #[DataProvider('peran')]
    public function test_each_role_gets_exactly_its_console_doors(UserRole $peran, bool $moderasi, bool $admin): void
    {
        $this->actingAs(User::factory()->create(['role' => $peran->value]))
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('auth.user.isModerator', $moderasi)
                ->where('auth.user.isAdmin', $admin));
    }

    /**
     * Menu adalah perjalanan, bukan daftar tabel: TEPAT lima tab. Peran (Moderasi,
     * Admin) bukan tahap perjalanan dan tinggal di menu akun.
     */
    public function test_the_main_menu_is_five_surfaces_not_nine_nouns(): void
    {
        $isi = $this->daftarPermukaan();

        $this->assertSame(5, preg_match_all('/label: \'/', $isi), 'Menu utama wajib berisi tepat lima tab.');
        $this->assertStringNotContainsString('admin', $isi);
        $this->assertStringNotContainsString('moderation', $isi);

        // Menu akun tidak mengulang tab; dimulai dari Profil (diperbarui W2: menu avatar).
        $menuAkun = File::get(resource_path('js/layouts/MenuAkun.tsx'));

        $this->assertStringNotContainsString('itemTab(', $menuAkun);
        $this->assertStringNotContainsString('permukaan', $menuAkun);
        $this->assertMatchesRegularExpression('/^[^<]*(?:<(?!TautanMenu)[^<]*)*<TautanMenu href=\{profile\.url\(\)\}>Profil</s', $menuAkun, 'Entri pertama menu akun harus Profil.');
    }

    /**
     * Menu avatar adalah popover asli: Escape, klik di luar, dan kembalinya fokus ke avatar
     * ditangani peramban, dan elemen popover tidak memakai kelas display yang akan
     * mengalahkan aturan peramban yang menyembunyikannya.
     */
    public function test_the_wide_avatar_menu_is_a_native_popover(): void
    {
        $menu = File::get(resource_path('js/layouts/MenuAkun.tsx'));

        $this->assertStringContainsString('popoverTarget="menu-akun"', $menu);
        $this->assertMatchesRegularExpression('/<div ref=\{menu\} id="menu-akun" popover="auto" className="([^"]*)"/', $menu);

        preg_match('/<div ref=\{menu\} id="menu-akun" popover="auto" className="([^"]*)"/', $menu, $kelas);

        $this->assertDoesNotMatchRegularExpression('/(?<![\w:-])(?:flex|grid|block|inline-flex|inline-block|hidden)(?![\w-])/', $kelas[1], 'Kelas display pada popover membuatnya tampil walau tertutup.');
        $this->assertStringContainsString("router.on('start'", $menu, 'Menu harus tertutup ketika kunjungan Inertia dimulai.');
        $this->assertStringContainsString('hidePopover()', $menu);
        $this->assertStringContainsString('<span className="sr-only">Menu akun {pengguna.name}</span>', $menu);
        $this->assertStringNotContainsString('role="menu"', $menu);
    }

    /**
     * Halaman tugas dengan bar aksi tetap menyembunyikan bilah tab (spec v3 bagian 5.1):
     * detail jalur dan rencanakan trip (penilaian kecocokan menyusul di W3). Halaman lain
     * tetap membawa bilah tab.
     */
    public function test_only_task_pages_hide_the_tab_bar(): void
    {
        $tanpaBilah = [];

        foreach (File::allFiles(resource_path('js/pages')) as $berkas) {
            if (str_contains($berkas->getContents(), '<AppLayout bilahTab={false}>')) {
                $tanpaBilah[] = str_replace('\\', '/', $berkas->getRelativePathname());
            }
        }

        sort($tanpaBilah);

        $this->assertSame(['Trails/Show.tsx', 'Trips/Create.tsx'], $tanpaBilah);
    }

    /**
     * Blok `permukaan` di NavLinks.tsx saja, dari deklarasinya sampai penutup lariknya.
     */
    private function daftarPermukaan(): string
    {
        $isi = File::get(resource_path('js/layouts/NavLinks.tsx'));
        $mulai = strpos($isi, 'export const permukaan = [');

        $this->assertNotFalse($mulai, 'Daftar permukaan tidak ditemukan di NavLinks.tsx.');

        return substr($isi, $mulai, strpos($isi, "\n];", $mulai) - $mulai);
    }

    /**
     * Seluruh objek satu tab di daftar permukaan, dari labelnya sampai label berikutnya.
     */
    private function aturan(string $label): string
    {
        preg_match("/label: '".preg_quote($label, '/')."'(.*?)(?=label: '|$)/s", $this->daftarPermukaan(), $cocok);

        $this->assertNotEmpty($cocok, "Tab {$label} tidak ditemukan.");

        return $cocok[1];
    }
}
```

Di `tests/Feature/IconSystemTest.php`, ikon tab kini digambar `BilahTab` (22 px) dan navigasi segmen (20 px):

1. Ganti:

```php
     * varian isi untuk tab aktif (spec v3 bagian 8.3), bukan kelas h-6 w-6.
```

dengan:

```php
     * varian isi untuk tab aktif (spec v3 bagian 8.3), bukan kelas h-6 w-6.
     *
     * Diperbarui W2: tab tidak lagi digambar di NavLinks.tsx, melainkan oleh bilah tab ponsel
     * (BilahTab, 22 px) dan navigasi segmen layar lebar (KepalaLebar, 20 px). Aturannya sama:
     * ikon selalu berdampingan dengan labelnya dan memakai varian isi untuk tab aktif.
```

2. Ganti:

```php
        $tab = File::get(resource_path('js/layouts/NavLinks.tsx'));
        $this->assertMatchesRegularExpression('/<Icon name=\{ikon\} size=\{22\} isi=\{aktif\} \/>\s*<span[^>]*>\{children\}<\/span>/', $tab, 'Ikon navigasi bawah harus berdampingan dengan labelnya.');
```

dengan:

```php
        $bilah = File::get(resource_path('js/components/ui/BilahTab.tsx'));
        $this->assertMatchesRegularExpression('/<Icon name=\{t\.ikon\} size=\{22\} isi=\{t\.aktif\} \/>\s*<span[^>]*>\{t\.label\}<\/span>/', $bilah, 'Ikon bilah tab ponsel harus berdampingan dengan labelnya.');

        $segmen = File::get(resource_path('js/layouts/KepalaLebar.tsx'));
        $this->assertMatchesRegularExpression('/<Icon name=\{t\.ikon\} size=\{20\} isi=\{t\.aktif\} \/>\s*<span[^>]*>\{t\.label\}<\/span>/', $segmen, 'Ikon navigasi segmen harus berdampingan dengan labelnya.');
```

Di `tests/Feature/InertiaLayoutTest.php`, tambahkan tiga test kerangka tepat sebelum metode
`test_the_root_template_carries_the_skip_link_before_react_runs` yang sudah ada:

Ganti:

```php
    public function test_the_root_template_carries_the_skip_link_before_react_runs(): void
```

dengan:

```php
    /**
     * Kerangka W2 (spec v3 bagian 5, DK-33): di bawah 1024 px tidak ada bilah atas global,
     * mulai 1024 px header 68 px menempel di atas dan bilah tab ponsel menghilang. Titik
     * hentinya satu, lg, di kedua tempat; 640 sampai 1023 px (tablet) sengaja tetap memakai
     * kerangka ponsel.
     */
    public function test_the_shell_switches_at_1024px_without_a_global_top_bar_on_phones(): void
    {
        $layout = File::get(resource_path('js/layouts/AppLayout.tsx'));
        $kepala = File::get(resource_path('js/layouts/KepalaLebar.tsx'));
        $bilah = File::get(resource_path('js/components/ui/BilahTab.tsx'));

        $this->assertStringNotContainsString('<nav', $layout, 'AppLayout tidak lagi menggambar bilah atas atau navigasinya sendiri.');
        $this->assertDoesNotMatchRegularExpression('/sm:/', $layout);
        $this->assertStringContainsString('<header data-kepala-lebar="" className="sticky top-0 z-30 hidden h-[4.25rem] items-center gap-6 border-b border-subtle bg-surface px-6 lg:flex">', $kepala);
        $this->assertStringContainsString("px-4 pb-[max(env(safe-area-inset-bottom),0.75rem)] lg:hidden')", $bilah);
        $this->assertStringNotContainsString('sm:hidden', $bilah);
        $this->assertStringContainsString("data-bilah-tab={mengambang ? '' : undefined}", $bilah);
    }

    /**
     * Lapisan tetap tidak menutup isi maupun fokus papan ketik (WCAG 2.4.11): jaraknya
     * diturunkan dari elemen yang benar-benar ada di halaman, main memberi ruang setinggi
     * itu, dan scroll-padding menjaga elemen yang difokus tetap di luar bilah dan header.
     */
    public function test_fixed_layers_leave_room_for_the_content_and_the_keyboard_focus(): void
    {
        $css = File::get(resource_path('css/app.css'));

        $this->assertStringContainsString('--nav-bawah: calc(var(--bilah-tab) + var(--strip-pendakian));', $css);
        $this->assertStringContainsString('scroll-padding-top: calc(var(--kepala-atas) + 0.5rem);', $css);
        $this->assertStringContainsString('scroll-padding-bottom: calc(var(--nav-bawah) + 0.5rem);', $css);
        $this->assertMatchesRegularExpression('/:root:has\(nav\[data-bilah-tab\]\) \{\s*--bilah-tab: calc\(4\.375rem \+ max\(env\(safe-area-inset-bottom, 0px\), 0\.75rem\)\);/', $css);
        $this->assertMatchesRegularExpression('/@media \(min-width: 1024px\) \{\s*:root:has\(nav\[data-bilah-tab\]\) \{\s*--bilah-tab: env\(safe-area-inset-bottom, 0px\);\s*\}\s*:root:has\(\[data-kepala-lebar\]\) \{\s*--kepala-atas: 4\.25rem;/', $css);
        $this->assertStringContainsString('<main id="konten" tabIndex={-1} className="pb-[var(--nav-bawah)]">', File::get(resource_path('js/layouts/AppLayout.tsx')));
    }

    /**
     * Urutan fokus mengikuti urutan baca (WCAG 2.4.3): header layar lebar, isi halaman,
     * lalu bilah tab. Kepala setiap halaman ada di dalam main, jadi di ponsel fokus pertama
     * sesudah tautan lewati adalah aksi kepala halaman, bukan bilah tab.
     */
    public function test_the_focus_order_follows_the_reading_order(): void
    {
        $layout = File::get(resource_path('js/layouts/AppLayout.tsx'));
        $letak = [];

        foreach (['<KepalaLebar ', '<main id="konten"', '</main>', '<BilahTab '] as $penanda) {
            $posisi = strpos($layout, $penanda);
            $this->assertNotFalse($posisi, "{$penanda} tidak ada di AppLayout.");
            $letak[] = $posisi;
        }

        $urut = $letak;
        sort($urut);

        $this->assertSame($urut, $letak, 'Urutan kerangka: header layar lebar, main, lalu bilah tab.');
    }

    public function test_the_root_template_carries_the_skip_link_before_react_runs(): void
```

Di `tests/Feature/DemoBadgeTest.php`, header layar lebar yang kini membawa lencana, bukan `AppLayout`:

1. Ganti:

```php
     * selalu ada di atas isi. Niatnya tetap: lencana tampil di setiap halaman berkerangka.
```

dengan:

```php
     * selalu ada di atas isi; mulai 1024 px header layar lebar yang membawanya. Niatnya
     * tetap: lencana tampil di setiap halaman berkerangka, di setiap ukuran layar.
```

2. Ganti:

```php
        foreach (['js/layouts/AppLayout.tsx', 'js/components/trips/TataLetakPendakian.tsx', 'js/components/ui/PageHeader.tsx'] as $berkas) {
```

dengan:

```php
        foreach (['js/layouts/KepalaLebar.tsx', 'js/components/trips/TataLetakPendakian.tsx', 'js/components/ui/PageHeader.tsx'] as $berkas) {
```

Di `tests/Feature/LongInputLayoutTest.php`, tambahkan penjaga label kerangka sebelum
`test_the_showcase_exercises_long_indonesian_labels_at_360px` (Review Focus W2):

Ganti:

```php
    }

    /**
     * Pameran primitif memuat satu blok selebar 360 px yang menaruh untaian Indonesia
```

dengan:

```php
    }

    /**
     * Kerangka W2 juga membungkus, tidak memotong (Review Focus W2): judul halaman, nama
     * jalur dan pos di strip pendakian, Simpanan, menu akun, dan lembar Pengingat bertemu
     * nama jalur dan kalimat Indonesia yang panjang di 360 px. Setiap berkas di
     * resources/js/layouts ikut diperiksa, jadi bagian kerangka yang ditambahkan kemudian
     * tidak lolos diam-diam.
     */
    public function test_the_shell_wraps_long_labels_instead_of_cutting_them(): void
    {
        $berkas = [resource_path('js/components/ui/PageHeader.tsx'), resource_path('js/components/akun/PintuAkun.tsx')];

        foreach (File::files(resource_path('js/layouts')) as $tataLetak) {
            $berkas[] = $tataLetak->getPathname();
        }

        $pemotong = [];

        foreach ($berkas as $jalur) {
            $isi = (string) preg_replace('#/\*.*?\*/#s', ' ', File::get($jalur));

            if (preg_match('/\b(?:truncate|whitespace-nowrap|text-ellipsis|line-clamp-\d)\b/', $isi) === 1) {
                $pemotong[] = basename($jalur);
            }
        }

        $this->assertSame([], $pemotong, 'Kerangka memotong labelnya: '.implode(', ', $pemotong));
    }

    /**
     * Pameran primitif memuat satu blok selebar 360 px yang menaruh untaian Indonesia
```

- [ ] **Step 2: Jalankan dan pastikan gagal.**

Run: `php artisan test --compact --filter='NavigationConsistencyTest|IconSystemTest|InertiaLayoutTest|DemoBadgeTest|LongInputLayoutTest'`
Expected: FAIL. Pada simulasi rencana: 56 test, 45 lulus, 4 gagal, 7 error. Yang gagal: jarak lapisan belum ada di
`app.css`, `<KepalaLebar` belum dipasang, `NavLinks.tsx` masih memotong label dengan `truncate`, dan belum ada halaman
tugas tanpa bilah tab. Yang error: `KepalaLebar.tsx` dan `MenuAkun.tsx` belum ada.

- [ ] **Step 3: Satu daftar tab.** Ganti seluruh isi `resources/js/layouts/NavLinks.tsx` dengan:

```tsx
import type { ItemTab } from '@/components/ui/BilahTab';
import { Tautan } from '@/components/ui/Tautan';
import type { ReactNode } from 'react';
import { dashboard, progress, track } from '@/routes';
import { index as trailsIndex } from '@/routes/trails';
import { index as tripsIndex } from '@/routes/trips';

/** Mode pendakian satu trip: `/trips/{id}/hike`. Milik tab Mendaki, bukan tab Trip. */
const modePendakian = (p: string) => /^\/trips\/[^/]+\/hike/.test(p);

/**
 * Lima tab arsitektur informasi v2 (keputusan pemilik 25 September 2026, DK-25): Home,
 * Explore, Trips, Track, Progress, berlabel Indonesia. Profil bukan tab; ia dibuka dari
 * avatar (ruling W2-R1). Community bukan tab; ia lapisan di dalam halaman (laporan di
 * rincian jalur, bagikan laporan di Progres, kabar gunung yang diikuti di Beranda). Tab
 * aktif dihitung dari path, satu tab per halaman.
 */
export const permukaan = [
    { label: 'Beranda', ikon: 'house' as const, href: dashboard.url(), aktif: (p: string) => p === '/dashboard' || p.startsWith('/kabar') },
    {
        label: 'Jelajah',
        ikon: 'map-trifold' as const,
        href: trailsIndex.url(),
        aktif: (p: string) => (p === '/trails' || p.startsWith('/trails/')) && !p.startsWith('/trails/compare'),
    },
    {
        label: 'Trip',
        ikon: 'backpack' as const,
        href: tripsIndex.url(),
        aktif: (p: string) =>
            (p.startsWith('/trips') && !modePendakian(p)) || p.startsWith('/goals') || p.startsWith('/recommendations') || p.startsWith('/trails/compare'),
    },
    { label: 'Mendaki', ikon: 'person-simple-hike' as const, href: track.url(), aktif: (p: string) => p.startsWith('/mendaki') || modePendakian(p) },
    {
        label: 'Progres',
        ikon: 'chart-line-up' as const,
        href: progress.url(),
        aktif: (p: string) => p.startsWith('/progres') || p.startsWith('/history') || p.startsWith('/reports'),
    },
];

/**
 * Satu sumber untuk bilah tab ponsel dan navigasi segmen layar lebar (spec v3 bagian 5):
 * daftar, urutan, dan tab aktif yang sama di kedua ukuran, jadi pendaki yang berpindah dari
 * ponsel ke laptop menemukan tujuannya di tempat yang sama.
 */
export function itemTab(jalur: string): ItemTab[] {
    return permukaan.map((p) => ({ label: p.label, href: p.href, ikon: p.ikon, aktif: p.aktif(jalur) }));
}

/** Baris menu akun: 44 px, cincin fokus di dalam karena barisnya selebar menu. */
export const tombolMenu =
    'flex min-h-11 w-full items-center rounded-control px-3 py-2 text-start text-kecil font-semibold text-primary hover:bg-surface-sunken focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-600';

export function TautanMenu({ href, children }: { href: string; children: ReactNode }) {
    return (
        <Tautan href={href} className={tombolMenu}>
            {children}
        </Tautan>
    );
}
```

- [ ] **Step 4: Menu avatar dan header layar lebar.** Buat `resources/js/layouts/MenuAkun.tsx`:

```tsx
import { router, usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { Avatar } from '@/components/ui/Avatar';
import { fokusTerlihat, umpanTekan } from '@/components/ui/gaya';
import { kelas } from '@/lib/kelas';
import { profile } from '@/routes';
import type { PropsBersama } from '@/types';
import { TautanMenu, tombolMenu } from './NavLinks';

/**
 * Menu avatar layar lebar (spec v3 bagian 5.2): Profil; Moderasi dan Admin sesuai peran;
 * Keluar. Popover asli (popover="auto"): peramban menutupnya dengan Escape dan dengan klik
 * di luar, mengembalikan fokus ke avatar, dan menaruhnya di top layer sehingga tidak
 * terpotong wadah mana pun. Sengaja tanpa peran ARIA menu: isinya tautan dan satu tombol
 * yang dijelajahi dengan Tab, bukan menu aplikasi yang dijelajahi dengan tombol panah.
 *
 * Elemen popover sendiri tidak diberi kelas display. Aturan peramban yang menyembunyikan
 * popover tertutup berasal dari lembar gaya peramban, dan kelas penulis seperti flex selalu
 * mengalahkannya, sehingga menu akan tampil terus. Tata letak isinya ada di anak.
 */
export function MenuAkun() {
    const { auth } = usePage<PropsBersama>().props;
    const menu = useRef<HTMLDivElement>(null);
    const pengguna = auth.user;

    // Kunjungan Inertia tidak memuat ulang dokumen, jadi popover yang terbuka akan tetap
    // terbuka di halaman tujuan. Ia ditutup begitu kunjungan dimulai.
    useEffect(
        () =>
            router.on('start', () => {
                if (menu.current?.matches(':popover-open')) {
                    menu.current.hidePopover();
                }
            }),
        [],
    );

    if (!pengguna) {
        return null;
    }

    return (
        <>
            <button type="button" popoverTarget="menu-akun" className={kelas('inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full', fokusTerlihat, umpanTekan)}>
                <Avatar nama={pengguna.name} ukuran={44} />
                <span className="sr-only">Menu akun {pengguna.name}</span>
            </button>
            <div ref={menu} id="menu-akun" popover="auto" className="inset-auto right-6 top-[4.5rem] m-0 w-72 rounded-kartu border border-subtle bg-surface p-2 text-primary shadow-overlay">
                <div className="flex flex-col">
                    <p className="px-3 pb-2 pt-1">
                        <span className="block font-bold">{pengguna.name}</span>
                        <span className="block break-all text-kecil text-secondary">{pengguna.email}</span>
                    </p>
                    <div className="flex flex-col border-t border-subtle pt-1">
                        <TautanMenu href={profile.url()}>Profil</TautanMenu>
                        {pengguna.isModerator ? <TautanMenu href="/moderation">Moderasi</TautanMenu> : null}
                        {pengguna.isAdmin ? <TautanMenu href="/admin/trails">Admin</TautanMenu> : null}
                        <button type="button" onClick={() => router.post('/logout')} className={tombolMenu}>
                            Keluar
                        </button>
                    </div>
                </div>
            </div>
        </>
    );
}
```

Buat `resources/js/layouts/KepalaLebar.tsx`:

```tsx
import { Lockup } from '@/components/brand/Lockup';
import type { ItemTab } from '@/components/ui/BilahTab';
import { fokusTerlihat } from '@/components/ui/gaya';
import { Icon } from '@/components/ui/Icon';
import { LencanaDataContoh } from '@/components/ui/LencanaDataContoh';
import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';
import { dashboard } from '@/routes';
import { MenuAkun } from './MenuAkun';

/**
 * Navigasi segmen layar lebar (spec v3 bagian 5.2): lima tab yang sama dengan bilah tab
 * ponsel, ikon dan label 14 px. Tab aktif ditandai permukaan terangkat, garis, tebal huruf,
 * ikon varian isi, dan aria-current, bukan warna saja (WCAG 1.4.1).
 */
function NavigasiSegmen({ item }: { item: ReadonlyArray<ItemTab> }) {
    return (
        <nav aria-label="Navigasi utama" className="shrink-0">
            <ul className="flex gap-1 rounded-tombol bg-canvas p-1">
                {item.map((t) => (
                    <li key={t.label}>
                        <Tautan
                            href={t.href}
                            aria-current={t.aktif ? 'page' : undefined}
                            className={kelas(
                                'flex min-h-11 items-center gap-2 rounded-control border px-3 text-kecil xl:px-4',
                                fokusTerlihat,
                                t.aktif ? 'border-control bg-surface font-bold text-brand-ink' : 'border-transparent font-semibold text-secondary hover:bg-surface hover:text-primary',
                            )}
                        >
                            <Icon name={t.ikon} size={20} isi={t.aktif} />
                            <span>{t.label}</span>
                        </Tautan>
                    </li>
                ))}
            </ul>
        </nav>
    );
}

/**
 * Header layar lebar 68 px (DK-33; spec v3 bagian 5.2; prototype 15): lockup di kiri,
 * navigasi segmen di tengah, lencana Data contoh dan menu avatar di kanan. Hanya mulai
 * 1024 px; di bawahnya setiap halaman membawa kepalanya sendiri dan navigasi pindah ke
 * bilah tab. Menempel di atas, dan tingginya dibaca app.css lewat data-kepala-lebar
 * supaya jangkar dan fokus papan ketik tidak tertutup (scroll-padding-top).
 */
export function KepalaLebar({ item }: { item: ReadonlyArray<ItemTab> }) {
    return (
        <header data-kepala-lebar="" className="sticky top-0 z-30 hidden h-[4.25rem] items-center gap-6 border-b border-subtle bg-surface px-6 lg:flex">
            <div className="flex flex-1 items-center">
                <Lockup href={dashboard.url()} />
            </div>
            <NavigasiSegmen item={item} />
            <div className="flex flex-1 items-center justify-end gap-2.5">
                <LencanaDataContoh />
                <MenuAkun />
            </div>
        </header>
    );
}
```

- [ ] **Step 5: Bilah tab ikut titik henti 1024 px dan menandai dirinya.** Di `resources/js/components/ui/BilahTab.tsx`:

1. Ganti:

```tsx
 * Label membungkus bila panjang dan tidak pernah di bawah 12 px. W2 memasangnya di
 * AppLayout menggantikan navigasi bawah lama.
```

dengan:

```tsx
 * Label membungkus bila panjang dan tidak pernah di bawah 12 px. Dipasang AppLayout di
 * bawah 1024 px (DK-33); mulai 1024 px navigasi segmen di header layar lebar yang bekerja.
 * Atribut data-bilah-tab dibaca app.css untuk menghitung --nav-bawah, sehingga bar aksi,
 * toast, dan fokus papan ketik tidak pernah tertutup bilah ini.
```

2. Ganti:

```tsx
        <nav aria-label={label} className={kelas(mengambang && 'fixed inset-x-0 bottom-0 z-30 px-4 pb-[max(env(safe-area-inset-bottom),0.75rem)] sm:hidden')}>
```

dengan:

```tsx
        <nav
            aria-label={label}
            data-bilah-tab={mengambang ? '' : undefined}
            className={kelas(mengambang && 'fixed inset-x-0 bottom-0 z-30 px-4 pb-[max(env(safe-area-inset-bottom),0.75rem)] lg:hidden')}
        >
```

- [ ] **Step 6: Jarak lapisan dari elemen yang ada.** Di `resources/css/app.css`:

Ganti:

```css
}

/*
| Kurangi gerak dari pengaturan aplikasi (Profil, Tampilan), bukan hanya dari sistem.
```

dengan:

```css
}

/*
| Kerangka (spec v3 bagian 5; W2): jarak lapisan yang menempel di tepi layar. Nilainya
| diturunkan dari elemen yang benar-benar ada di halaman, bukan disalin ke setiap halaman:
| bilah tab ponsel (nav[data-bilah-tab], tersembunyi mulai 1024 px), strip pendakian aktif
| ([data-strip-pendakian]), dan header layar lebar ([data-kepala-lebar], mulai 1024 px).
| --nav-bawah dibaca bar aksi, toast, dan baki banding. scroll-padding menjaga elemen yang
| difokus papan ketik dan sasaran jangkar tidak tertutup lapisan tetap (WCAG 2.4.11).
| Bilah tab setinggi 70 px (item 56 px, padding 6 px, garis 1 px) ditambah jarak bawahnya.
*/
:root {
    --bilah-tab: env(safe-area-inset-bottom, 0px);
    --strip-pendakian: 0px;
    --kepala-atas: 0px;
    --nav-bawah: calc(var(--bilah-tab) + var(--strip-pendakian));
    scroll-padding-top: calc(var(--kepala-atas) + 0.5rem);
    scroll-padding-bottom: calc(var(--nav-bawah) + 0.5rem);
}

:root:has(nav[data-bilah-tab]) {
    --bilah-tab: calc(4.375rem + max(env(safe-area-inset-bottom, 0px), 0.75rem));
}

:root:has([data-strip-pendakian]) {
    --strip-pendakian: 3.5rem;
}

@media (min-width: 1024px) {
    :root:has(nav[data-bilah-tab]) {
        --bilah-tab: env(safe-area-inset-bottom, 0px);
    }

    :root:has([data-kepala-lebar]) {
        --kepala-atas: 4.25rem;
    }
}

/*
| Kurangi gerak dari pengaturan aplikasi (Profil, Tampilan), bukan hanya dari sistem.
```

- [ ] **Step 7: Kerangka.** Ganti seluruh isi `resources/js/layouts/AppLayout.tsx` dengan:

```tsx
import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { BilahTab } from '@/components/ui/BilahTab';
import { JudulHalaman } from '@/components/ui/JudulHalaman';
import { KepalaLebar } from './KepalaLebar';
import { itemTab } from './NavLinks';

interface AppLayoutProps {
    /**
     * Bawaan true. Halaman tugas yang membawa bar aksi tetap (detail jalur, penilaian
     * kecocokan, rencanakan trip) memberi false: bilah tab disembunyikan dan tombol kembali
     * di kepala halaman menjadi jalan keluarnya (spec v3 bagian 5.1).
     */
    bilahTab?: boolean;
    children: ReactNode;
}

/**
 * Kerangka aplikasi (spec v3 bagian 5; DK-33). Di bawah 1024 px tidak ada bilah atas
 * global: setiap halaman membawa kepalanya sendiri (PageHeader) dan navigasi utama adalah
 * bilah tab pil di bawah. Mulai 1024 px header 68 px dengan navigasi segmen menggantikan
 * bilah tab. Keduanya digambar dari satu daftar (itemTab), dan tab aktif dihitung dari URL
 * halaman Inertia, bukan dari window, supaya sama di server, pratinjau, dan peramban.
 */
export function AppLayout({ bilahTab = true, children }: AppLayoutProps) {
    const jalur = usePage().url.split('?')[0] ?? '';
    const tab = itemTab(jalur);

    return (
        <div className="min-h-dvh bg-canvas">
            <JudulHalaman />
            <KepalaLebar item={tab} />
            <main id="konten" tabIndex={-1} className="pb-[var(--nav-bawah)]">
                {children}
            </main>
            {bilahTab ? <BilahTab item={tab} /> : null}
        </div>
    );
}
```

- [ ] **Step 8: Halaman tugas dan peta Jelajah.** Detail jalur dan rencanakan trip menyembunyikan bilah tab. Peta
Jelajah yang lengket di layar lebar digeser setinggi header:

`resources/js/pages/Trails/Index.tsx`:

1. Ganti:

```tsx
                        <section aria-label="Peta jalur" className={kelas(tampilan === 'daftar' ? 'hidden lg:block' : 'block', 'lg:sticky lg:top-6 lg:self-start')}>
```

dengan:

```tsx
                        <section aria-label="Peta jalur" className={kelas(tampilan === 'daftar' ? 'hidden lg:block' : 'block', 'lg:sticky lg:top-[calc(var(--kepala-atas)+1.5rem)] lg:self-start')}>
```

2. Ganti:

```tsx
                                className="h-[calc(100dvh-var(--nav-bawah,0px)-14rem)] min-h-[22rem] lg:h-[calc(100dvh-3rem)]"
```

dengan:

```tsx
                                className="h-[calc(100dvh-var(--nav-bawah,0px)-14rem)] min-h-[22rem] lg:h-[calc(100dvh-var(--kepala-atas)-3rem)]"
```

`resources/js/pages/Trails/Show.tsx`:

Ganti:

```tsx
Show.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
```

dengan:

```tsx
Show.layout = (page: ReactNode) => <AppLayout bilahTab={false}>{page}</AppLayout>;
```

`resources/js/pages/Trips/Create.tsx`:

Ganti:

```tsx
Create.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
```

dengan:

```tsx
Create.layout = (page: ReactNode) => <AppLayout bilahTab={false}>{page}</AppLayout>;
```

- [ ] **Step 9: Jalankan test tugas ini dan tetangganya sampai lulus.**

Run: `php artisan test --compact --filter='NavigationConsistencyTest|IconSystemTest|InertiaLayoutTest|DemoBadgeTest|LongInputLayoutTest|TapTargetTest|PageHeaderContractTest|DesignPrimitivesTest'`
Expected: PASS, 107 test.

- [ ] **Step 10: Gerbang tugas.**

Run: `vendor/bin/pint --format agent tests/Feature/NavigationConsistencyTest.php tests/Feature/IconSystemTest.php tests/Feature/InertiaLayoutTest.php tests/Feature/DemoBadgeTest.php tests/Feature/LongInputLayoutTest.php`
Expected: `{"tool":"pint","result":"passed"}` atau berkas dirapikan tanpa perubahan makna.

Run: `npx tsc --noEmit`
Expected: keluar tanpa keluaran galat.

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal. Pada simulasi rencana: 1.573 test, 1.571 lulus, 2 dilewati.

- [ ] **Step 11: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 3: Hitungan Simpanan di header lebar dan kepala Jelajah

Induk W2 butir 3, bagian hitungan simpanan. Satu kueri hitung milik `ConsiderationService`, memakai predikat yang sama
dengan halaman Simpanan dan batas simpanan (W2-K14). Hasilnya dikirim sebagai prop bersama malas `jumlahSimpanan`.
`TautanSimpanan` menampilkan hitungan itu di header layar lebar dan di kepala Jelajah ponsel (prototype 02 dan 15).

**Files:**
- Create: `resources/js/layouts/TautanSimpanan.tsx`
- Modify: `app/Services/ConsiderationService.php`, `app/Http/Middleware/HandleInertiaRequests.php`, `resources/js/types/index.d.ts`, `resources/js/components/ui/ikon/phosphor.ts`, `resources/js/layouts/KepalaLebar.tsx`, `resources/js/pages/Trails/Index.tsx`
- Test: `tests/Feature/SavedCountPropTest.php` (baru), `tests/Feature/PageQueryBudgetTest.php`

**Interfaces:**
- Consumes: `TrailConsideration`, scope `Trail::active()`, `KepalaLebar` dari Tugas 2, `PageHeader` `aksi` dari Tugas 1, rute `saved` dari `@/routes/trails`.
- Produces: `ConsiderationService::jumlahSimpanan(User $user): int`; prop bersama `jumlahSimpanan: number | null` (null untuk tamu) di `PropsBersama`; `TautanSimpanan({ ringkas = false, className })`; nama ikon `'bookmark-simple'`.

- [ ] **Step 1: Tulis test yang gagal.** Buat test dengan
`DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:test --phpunit --no-interaction SavedCountPropTest`,
lalu ganti seluruh isinya dengan:

```php
<?php

namespace Tests\Feature;

use App\Models\Trail;
use App\Models\TrailConsideration;
use App\Models\User;
use App\Services\ConsiderationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Hitungan Simpanan di kerangka (spec v3 bagian 5.2; rencana induk W2 butir 3).
 *
 * Header layar lebar dan kepala Jelajah menampilkan "Simpanan N" di setiap halaman, jadi
 * hitungannya props bersama. Tiga hal yang dijaga: angkanya sama dengan daftar di halaman
 * Simpanan (hanya milik sendiri, jalur arsip tidak dihitung), biayanya satu kueri berapa pun
 * jumlah simpanan, dan muat ulang parsial yang tidak memintanya tidak membayarnya.
 */
class SavedCountPropTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_count_matches_the_saved_page_and_belongs_to_its_owner(): void
    {
        $budi = User::factory()->create();
        $sari = User::factory()->create();
        [$satu, $dua, $arsip] = Trail::factory()->count(3)->create()->all();
        $arsip->forceFill(['archived_at' => now()])->save();

        foreach ([$satu, $dua, $arsip] as $jalur) {
            TrailConsideration::create(['user_id' => $budi->id, 'trail_id' => $jalur->id]);
        }

        TrailConsideration::create(['user_id' => $sari->id, 'trail_id' => $satu->id]);

        $this->actingAs($budi)->get(route('trails.saved'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('jumlahSimpanan', 2)->has('trails', 2));

        $this->actingAs($sari)->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('jumlahSimpanan', 1));
    }

    public function test_a_guest_carries_no_count(): void
    {
        $this->get('/')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->where('jumlahSimpanan', null));
    }

    public function test_the_count_costs_one_query_whatever_the_number_of_saved_trails(): void
    {
        $budi = User::factory()->create();

        foreach (Trail::factory()->count(25)->create() as $jalur) {
            TrailConsideration::create(['user_id' => $budi->id, 'trail_id' => $jalur->id]);
        }

        $kueri = 0;
        DB::listen(function () use (&$kueri) {
            $kueri++;
        });

        $this->assertSame(25, app(ConsiderationService::class)->jumlahSimpanan($budi));
        $this->assertSame(1, $kueri);
    }

    /**
     * Saringan Jelajah memakai muat ulang parsial (only: trails, menunggu, ...). Hitungan
     * yang malas tidak ikut dihitung di sana.
     */
    public function test_a_partial_reload_that_does_not_ask_for_it_skips_it(): void
    {
        $this->actingAs(User::factory()->create())->get('/dashboard')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('jumlahSimpanan', 0)
                ->reloadOnly('title', fn (AssertableInertia $muatUlang) => $muatUlang->missing('jumlahSimpanan')));
    }

    /**
     * Header layar lebar memakai bentuk ringkas (ikon dan angka, kata dibacakan) di bawah
     * 1280 px; kepala Jelajah di ponsel memakai bentuk penuh seperti prototype 02 dan
     * menyembunyikannya mulai 1024 px karena header layar lebar sudah membawanya.
     */
    public function test_the_wide_header_and_the_jelajah_header_show_the_count(): void
    {
        $tautan = File::get(resource_path('js/layouts/TautanSimpanan.tsx'));

        $this->assertStringContainsString('href={saved.url()}', $tautan);
        $this->assertStringContainsString('min-h-11', $tautan);
        $this->assertStringContainsString('<Icon name="bookmark-simple" size={16} />', $tautan);
        $this->assertStringContainsString("<span className={kelas(ringkas && 'sr-only xl:not-sr-only')}>Simpanan</span>", $tautan);
        $this->assertStringContainsString('<span data-angka="">{jumlahSimpanan}</span>', $tautan);
        $this->assertStringContainsString('<span className="sr-only"> jalur tersimpan</span>', $tautan);
        $this->assertStringContainsString('<TautanSimpanan ringkas />', File::get(resource_path('js/layouts/KepalaLebar.tsx')));
        $this->assertStringContainsString('aksi={<TautanSimpanan className="lg:hidden" />}', File::get(resource_path('js/pages/Trails/Index.tsx')));
    }
}
```

Di `tests/Feature/PageQueryBudgetTest.php`, tambahkan test pertumbuhan kerangka:

1. Ganti:

```php
use App\Models\Trail;
```

dengan:

```php
use App\Models\Trail;
use App\Models\TrailConsideration;
```

2. Ganti:

```php
    private function pendakiBerprofil(): User
```

dengan:

```php
    /**
     * Kerangka W2 membawa hitungan Simpanan ke setiap halaman sebagai props bersama (satu
     * kueri terindeks, rencana induk W2 butir 3). Yang dijaga bukan angka mutlaknya,
     * melainkan bahwa halaman tidak tumbuh bersama jumlah simpanan pendakinya.
     */
    public function test_the_shell_does_not_grow_with_the_number_of_saved_trails(): void
    {
        $user = $this->pendakiBerprofil();
        $trails = $this->seedTrails(22);

        TrailConsideration::create(['user_id' => $user->id, 'trail_id' => $trails->first()->id]);

        $this->actingAs($user);
        $this->get('/progres')->assertOk();
        $sedikit = $this->countQueries('/progres');

        foreach ($trails->skip(1) as $trail) {
            TrailConsideration::create(['user_id' => $user->id, 'trail_id' => $trail->id]);
        }

        $banyak = $this->countQueries('/progres');

        $this->assertSame($sedikit, $banyak, "Beban tumbuh: {$sedikit} query untuk 1 simpanan, {$banyak} untuk 22.");
    }

    private function pendakiBerprofil(): User
```

- [ ] **Step 2: Jalankan dan pastikan gagal.**

Run: `php artisan test --compact --filter='SavedCountPropTest|PageQueryBudgetTest'`
Expected: FAIL. Pada simulasi rencana: 16 test, 11 lulus, 3 gagal (prop `jumlahSimpanan` belum ada), 2 error
(`ConsiderationService::jumlahSimpanan()` dan `TautanSimpanan.tsx` belum ada).
`test_the_shell_does_not_grow_with_the_number_of_saved_trails` sudah lulus sejak merah: test itu penjaga pertumbuhan,
bukan pendorong.

- [ ] **Step 3: Satu hitungan untuk batas dan kerangka.** Di `app/Services/ConsiderationService.php`:

1. Ganti:

```php
            $jumlahAktif = TrailConsideration::where('user_id', $user->id)
                ->whereHas('trail', fn ($query) => $query->active())
                ->count();

            if ($jumlahAktif >= $this->batasSimpanan()) {
```

dengan:

```php
            if ($this->jumlahSimpanan($user) >= $this->batasSimpanan()) {
```

2. Ganti:

```php
    }

    /**
     * Menghapus satu jalur dari simpanan, tanpa syarat.
```

dengan:

```php
    }

    /**
     * Jumlah jalur tersimpan yang masih aktif, sama dengan yang tampil di halaman Simpanan.
     *
     * Satu kueri hitung yang disaring lewat indeks unik trail_considerations (user_id,
     * trail_id) dan kunci utama trails, sehingga biayanya tetap berapa pun jumlah simpanan.
     * Dipakai batas simpanan dan hitungan Simpanan di kerangka (props bersama), jadi batas,
     * hitungan, dan daftar tidak pernah berbeda pendapat tentang jalur arsip.
     */
    public function jumlahSimpanan(User $user): int
    {
        return TrailConsideration::where('user_id', $user->id)
            ->whereHas('trail', fn ($query) => $query->active())
            ->count();
    }

    /**
     * Menghapus satu jalur dari simpanan, tanpa syarat.
```

- [ ] **Step 4: Prop bersama.** Di `app/Http/Middleware/HandleInertiaRequests.php`:

1. Ganti:

```php
use App\Services\DataFreshnessService;
```

dengan:

```php
use App\Services\ConsiderationService;
use App\Services\DataFreshnessService;
```

2. Ganti:

```php
            'tema' => ThemePreference::current($request),
```

dengan:

```php
            // Hitungan Simpanan di header layar lebar dan kepala Jelajah (spec v3 bagian 5.2):
            // satu kueri terindeks, malas, sehingga muat ulang parsial yang tidak memintanya
            // tidak membayarnya. Null untuk tamu.
            'jumlahSimpanan' => fn () => $user === null ? null : app(ConsiderationService::class)->jumlahSimpanan($user),

            'tema' => ThemePreference::current($request),
```

Di `resources/js/types/index.d.ts`:

Ganti:

```ts
    demo: boolean;
```

dengan:

```ts
    demo: boolean;
    /** Jalur tersimpan yang masih aktif; null untuk tamu. */
    jumlahSimpanan: number | null;
```

- [ ] **Step 5: Ikon Simpanan.** Path berikut disalin dari Phosphor Icons core 2.1.1 (MIT). Sumber regular:
`https://cdn.jsdelivr.net/npm/@phosphor-icons/core@2.1.1/assets/regular/bookmark-simple.svg` (273 bita, SHA-256
`493a836009ab98f8d4ca84f088cc03f2785cdee86daa1643e003865ff3168b41`). Sumber bold:
`https://cdn.jsdelivr.net/npm/@phosphor-icons/core@2.1.1/assets/bold/bookmark-simple-bold.svg` (277 bita, SHA-256
`10d2be281a40fb4042945403d5d6cda2d54bec06aace1aee795b05fa79dc946a`). Mengunduhnya untuk pemeriksaan bersifat opsional.
Di `resources/js/components/ui/ikon/phosphor.ts`:

1. Ganti:

```ts
    | 'bell'
```

dengan:

```ts
    | 'bell'
    | 'bookmark-simple'
```

2. Ganti:

```ts
        bold: 'M225.29,165.93C216.61,151,212,129.57,212,104a84,84,0,0,0-168,0c0,25.58-4.59,47-13.27,61.93A20.08,20.08,0,0,0,30.66,186,19.77,19.77,0,0,0,48,196H84.18a44,44,0,0,0,87.64,0H208a19.77,19.77,0,0,0,17.31-10A20.08,20.08,0,0,0,225.29,165.93ZM128,212a20,20,0,0,1-19.6-16h39.2A20,20,0,0,1,128,212ZM54.66,172C63.51,154,68,131.14,68,104a60,60,0,0,1,120,0c0,27.13,4.48,50,13.33,68Z',
```

dengan:

```ts
        bold: 'M225.29,165.93C216.61,151,212,129.57,212,104a84,84,0,0,0-168,0c0,25.58-4.59,47-13.27,61.93A20.08,20.08,0,0,0,30.66,186,19.77,19.77,0,0,0,48,196H84.18a44,44,0,0,0,87.64,0H208a19.77,19.77,0,0,0,17.31-10A20.08,20.08,0,0,0,225.29,165.93ZM128,212a20,20,0,0,1-19.6-16h39.2A20,20,0,0,1,128,212ZM54.66,172C63.51,154,68,131.14,68,104a60,60,0,0,1,120,0c0,27.13,4.48,50,13.33,68Z',
    },
    'bookmark-simple': {
        regular: 'M184,32H72A16,16,0,0,0,56,48V224a8,8,0,0,0,12.24,6.78L128,193.43l59.77,37.35A8,8,0,0,0,200,224V48A16,16,0,0,0,184,32Zm0,177.57-51.77-32.35a8,8,0,0,0-8.48,0L72,209.57V48H184Z',
        bold: 'M184,28H72A20,20,0,0,0,52,48V224a12,12,0,0,0,18.36,10.18l57.63-36,57.65,36A12,12,0,0,0,204,224V48A20,20,0,0,0,184,28Zm-4,174.35-45.65-28.53a12,12,0,0,0-12.72,0L76,202.35V52H180Z',
```

- [ ] **Step 6: Tautan Simpanan.** Buat `resources/js/layouts/TautanSimpanan.tsx`:

```tsx
import { usePage } from '@inertiajs/react';
import { fokusTerlihat, umpanTekan } from '@/components/ui/gaya';
import { Icon } from '@/components/ui/Icon';
import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';
import { saved } from '@/routes/trails';
import type { PropsBersama } from '@/types';

interface TautanSimpananProps {
    /**
     * Header layar lebar di bawah 1280 px: kata "Simpanan" hanya dibacakan, yang tampil ikon
     * dan hitungannya, supaya navigasi segmen tetap satu baris di 1024 px.
     */
    ringkas?: boolean;
    className?: string;
}

/**
 * Tautan Simpanan dengan hitungannya (spec v3 bagian 5.2; prototype 02 dan 15). Hitungan
 * datang dari props bersama jumlahSimpanan, jumlah yang sama dengan daftar di halaman
 * Simpanan. Nol tetap ditulis: nol adalah keterangan, bukan kekosongan.
 */
export function TautanSimpanan({ ringkas = false, className }: TautanSimpananProps) {
    const { jumlahSimpanan } = usePage<PropsBersama>().props;

    if (jumlahSimpanan === null) {
        return null;
    }

    return (
        <Tautan
            href={saved.url()}
            className={kelas(
                'inline-flex min-h-11 shrink-0 items-center gap-1.5 rounded-full border border-subtle bg-surface px-3.5 text-kecil font-bold text-primary hover:bg-surface-sunken',
                fokusTerlihat,
                umpanTekan,
                className,
            )}
        >
            <Icon name="bookmark-simple" size={16} />
            <span className={kelas(ringkas && 'sr-only xl:not-sr-only')}>Simpanan</span>
            <span data-angka="">{jumlahSimpanan}</span>
            <span className="sr-only"> jalur tersimpan</span>
        </Tautan>
    );
}
```

Di `resources/js/layouts/KepalaLebar.tsx`:

1. Ganti:

```tsx
import { MenuAkun } from './MenuAkun';
```

dengan:

```tsx
import { MenuAkun } from './MenuAkun';
import { TautanSimpanan } from './TautanSimpanan';
```

2. Ganti:

```tsx
 * navigasi segmen di tengah, lencana Data contoh dan menu avatar di kanan. Hanya mulai
 * 1024 px; di bawahnya setiap halaman membawa kepalanya sendiri dan navigasi pindah ke
 * bilah tab. Menempel di atas, dan tingginya dibaca app.css lewat data-kepala-lebar
 * supaya jangkar dan fokus papan ketik tidak tertutup (scroll-padding-top).
```

dengan:

```tsx
 * navigasi segmen di tengah, lalu lencana Data contoh, Simpanan dengan hitungannya, dan
 * menu avatar di kanan. Hanya mulai 1024 px; di bawahnya setiap halaman membawa kepalanya
 * sendiri dan navigasi pindah ke bilah tab. Menempel di atas, dan tingginya dibaca app.css
 * lewat data-kepala-lebar supaya jangkar dan fokus papan ketik tidak tertutup.
```

3. Ganti:

```tsx
                <LencanaDataContoh />
```

dengan:

```tsx
                <LencanaDataContoh />
                <TautanSimpanan ringkas />
```

Di kepala Jelajah ponsel:

`resources/js/pages/Trails/Index.tsx`:

1. Ganti:

```tsx
import { AppLayout } from '@/layouts/AppLayout';
```

dengan:

```tsx
import { AppLayout } from '@/layouts/AppLayout';
import { TautanSimpanan } from '@/layouts/TautanSimpanan';
```

2. Ganti:

```tsx
                <PageHeader akar title="Jelajah" description="Telusuri jalur secara manual. Gunakan ini jika Anda ingin membandingkan sendiri tanpa melalui rekomendasi." />
```

dengan:

```tsx
                <PageHeader akar title="Jelajah" aksi={<TautanSimpanan className="lg:hidden" />} description="Telusuri jalur secara manual. Gunakan ini jika Anda ingin membandingkan sendiri tanpa melalui rekomendasi." />
```

- [ ] **Step 7: Jalankan test tugas ini dan tetangganya sampai lulus.**

Run: `php artisan test --compact --filter='SavedCountPropTest|PageQueryBudgetTest|ConsiderationTest|SavedRoutesTest|NewPageQueryBudgetTest'`
Expected: PASS, 52 test.

- [ ] **Step 8: Gerbang tugas.**

Run: `vendor/bin/pint --format agent app/Services/ConsiderationService.php app/Http/Middleware/HandleInertiaRequests.php tests/Feature/SavedCountPropTest.php tests/Feature/PageQueryBudgetTest.php`
Expected: `{"tool":"pint","result":"passed"}` atau berkas dirapikan tanpa perubahan makna.

Run: `npx tsc --noEmit`
Expected: keluar tanpa keluaran galat.

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal. Pada simulasi rencana: 1.579 test, 1.577 lulus, 2 dilewati. Dampak pada anggaran kueri: +1 kueri
per kunjungan penuh halaman berkerangka, tetap berapa pun jumlah simpanan (`SavedCountPropTest`,
`PageQueryBudgetTest::test_the_shell_does_not_grow_with_the_number_of_saved_trails`).

- [ ] **Step 9: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 4: Strip pendakian aktif

Induk W2 butir 4 dan spec bagian 5.1. Selama trip milik pengguna berstatus berlangsung, setiap halaman berkerangka
membawa strip 48 px di atas bilah tab. Strip itu satu tautan ke mode lapangan dan memuat nama jalur, pos yang dituju,
lama berjalan, dan "Buka" (W2-K13). Datanya prop bersama malas `pendakianAktif`: satu kueri join, hanya milik pemilik,
dan null di mode lapangan itu sendiri (W2-K14). Strip mengukur tingginya dan menulisnya ke `--strip-pendakian`,
sehingga `--nav-bawah` dari Tugas 2 mengangkat bar aksi, baki banding, dan toast di atasnya.

**Files:**
- Create: `app/Services/Trips/PendakianAktif.php`, `resources/js/layouts/StripPendakian.tsx`
- Modify: `app/Http/Middleware/HandleInertiaRequests.php`, `resources/js/types/index.d.ts`, `resources/js/layouts/AppLayout.tsx`
- Test: `tests/Feature/ActiveHikeStripTest.php` (baru)

**Interfaces:**
- Consumes: `TripStatus::IN_PROGRESS`, `HikingSessionStatus`, tabel `trip_plans`, `trails`, `hiking_sessions`, `checkpoints`; rute bernama `trips.hike`; `AppLayout` dan `--nav-bawah` dari Tugas 2; `Tautan`, `Icon`, `fokusTerlihat`, `kelas`.
- Produces:
  - `PendakianAktif::untuk(User $user): ?array{id: int, jalur: string, posBerikutnya: string|null, mulai: string|null, url: string}`, dengan `mulai` ISO 8601 UTC.
  - Prop bersama `pendakianAktif: PendakianAktif | null`; `export interface PendakianAktif` di `@/types`.
  - `StripPendakian({ pendakian })`, `lamaBerjalan(mulai: string, sekarang: number): string`.
  - `--strip-pendakian` ditulis ke `:root` selama strip terpasang.

- [ ] **Step 1: Tulis test yang gagal.** Buat test dengan
`DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:test --phpunit --no-interaction ActiveHikeStripTest`,
lalu ganti seluruh isinya dengan:

```php
<?php

namespace Tests\Feature;

use App\Enums\HikingSessionStatus;
use App\Enums\TripStatus;
use App\Models\Checkpoint;
use App\Models\HikingSession;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\Trips\PendakianAktif;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Strip pendakian aktif (spec v3 bagian 5.1; rencana induk W2 butir 4).
 *
 * Selama trip berlangsung, setiap halaman berkerangka membawa strip di atas bilah tab yang
 * kembali ke mode lapangan. Datanya props bersama pendakianAktif: hanya milik pemiliknya,
 * tidak dihitung di mode lapangan itu sendiri, dan satu kueri berapa pun jumlah trip dan pos.
 */
class ActiveHikeStripTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_owner_carries_the_hike_on_every_page(): void
    {
        [$budi, $trip, $pos] = $this->pendakianBerlangsung();

        foreach (['/trails', '/dashboard', '/progres'] as $alamat) {
            $this->actingAs($budi)->get($alamat)
                ->assertOk()
                ->assertInertia(fn (AssertableInertia $page) => $page
                    ->where('pendakianAktif.id', $trip->id)
                    ->where('pendakianAktif.jalur', $trip->trail->name)
                    ->where('pendakianAktif.posBerikutnya', $pos->name)
                    ->where('pendakianAktif.mulai', '2026-09-27T00:30:00+00:00')
                    ->where('pendakianAktif.url', '/trips/'.$trip->id.'/hike'));
        }
    }

    public function test_another_hiker_never_sees_it(): void
    {
        $this->pendakianBerlangsung();

        $this->actingAs(User::factory()->create())->get('/trails')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('pendakianAktif', null));
    }

    public function test_field_mode_itself_does_not_compute_it(): void
    {
        [$budi, $trip] = $this->pendakianBerlangsung();

        $this->actingAs($budi)->get(route('trips.hike', $trip))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Trips/Hike')->where('pendakianAktif', null));
    }

    public function test_planned_finished_and_cancelled_trips_are_not_an_active_hike(): void
    {
        $budi = User::factory()->create();

        foreach ([TripStatus::PLANNED, TripStatus::READY_FOR_DEPARTURE, TripStatus::COMPLETED, TripStatus::CANCELLED] as $status) {
            TripPlan::factory()->for($budi)->create(['status' => $status->value]);
        }

        $this->actingAs($budi)->get('/trails')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('pendakianAktif', null));
    }

    /**
     * Trip dimulai tanpa sesi atau sebelum posisi pertama dikirim: strip tetap menyebut
     * jalurnya, dan yang belum diketahui tetap kosong, bukan ditebak.
     */
    public function test_a_hike_without_a_session_still_names_its_trail(): void
    {
        $budi = User::factory()->create();
        $trip = TripPlan::factory()->for($budi)->create(['status' => TripStatus::IN_PROGRESS->value]);

        $this->assertSame([
            'id' => $trip->id,
            'jalur' => $trip->trail->name,
            'posBerikutnya' => null,
            'mulai' => null,
            'url' => '/trips/'.$trip->id.'/hike',
        ], app(PendakianAktif::class)->untuk($budi));
    }

    public function test_the_lookup_costs_one_query_whatever_the_number_of_trips_and_checkpoints(): void
    {
        [$budi, $trip] = $this->pendakianBerlangsung();

        for ($i = 2; $i < 30; $i++) {
            Checkpoint::factory()->for($trip->trail)->create(['sequence' => $i]);
        }

        TripPlan::factory()->count(20)->for($budi)->create(['status' => TripStatus::COMPLETED->value]);

        $kueri = 0;
        DB::listen(function () use (&$kueri) {
            $kueri++;
        });

        $this->assertSame($trip->id, app(PendakianAktif::class)->untuk($budi)['id'] ?? null);
        $this->assertSame(1, $kueri);
    }

    /**
     * Strip di atas bilah tab, satu tautan 48 px ke mode lapangan, dan app.css tahu
     * keberadaannya untuk menggeser bar aksi dan toast di atasnya.
     */
    public function test_the_strip_sits_above_the_tab_bar_and_opens_field_mode(): void
    {
        $strip = File::get(resource_path('js/layouts/StripPendakian.tsx'));
        $layout = File::get(resource_path('js/layouts/AppLayout.tsx'));

        $this->assertStringContainsString('<div ref={wadah} data-strip-pendakian="" className="pointer-events-none fixed inset-x-0 bottom-[calc(var(--bilah-tab)+0.5rem)] z-30 px-4">', $strip);
        $this->assertStringContainsString("akar.style.setProperty('--strip-pendakian', `\${elemen.offsetHeight + 8}px`)", $strip, 'Tinggi strip yang membungkus diukur, bukan ditebak.');
        $this->assertStringContainsString('new ResizeObserver(ukur)', $strip);
        $this->assertStringContainsString("akar.style.removeProperty('--strip-pendakian');", $strip);
        $this->assertStringContainsString('href={pendakian.url}', $strip);
        $this->assertStringContainsString('min-h-12', $strip);
        $this->assertStringContainsString('<span className="sr-only">Pendakian berlangsung: </span>', $strip);
        $this->assertMatchesRegularExpression('/>Buka<\/span>/', $strip);
        $this->assertStringNotContainsString('aria-live', $strip, 'Waktu berjalan tidak diumumkan setiap menit.');
        $this->assertStringContainsString('{props.pendakianAktif ? <StripPendakian pendakian={props.pendakianAktif} /> : null}', $layout);
        $this->assertGreaterThan(strpos($layout, '</main>'), strpos($layout, '<StripPendakian '), 'Strip dijangkau sesudah isi halaman.');
        $this->assertLessThan(strpos($layout, '<BilahTab '), strpos($layout, '<StripPendakian '), 'Strip dijangkau sebelum bilah tab, sama dengan letaknya di layar.');
        $this->assertMatchesRegularExpression('/:root:has\(\[data-strip-pendakian\]\) \{\s*--strip-pendakian: 3\.5rem;/', File::get(resource_path('css/app.css')));
        $this->assertStringNotContainsString('StripPendakian', File::get(resource_path('js/components/trips/TataLetakPendakian.tsx')), 'Mode lapangan tidak memasang strip.');
    }

    /**
     * Budi sedang mendaki: trip IN_PROGRESS, sesi dimulai 27 September 2026 00.30 UTC, dan
     * sesi sedang menuju Pos 1.
     *
     * @return array{0: User, 1: TripPlan, 2: Checkpoint}
     */
    private function pendakianBerlangsung(): array
    {
        $budi = User::factory()->create();
        $trail = Trail::factory()->create(['name' => 'Gede via Cibodas']);
        $pos = Checkpoint::factory()->for($trail)->create(['sequence' => 1, 'name' => 'Telaga Biru']);
        $trip = TripPlan::factory()->for($budi)->for($trail)->create(['status' => TripStatus::IN_PROGRESS->value]);

        HikingSession::create([
            'trip_plan_id' => $trip->id,
            'user_id' => $budi->id,
            'current_checkpoint_id' => $pos->id,
            'status' => HikingSessionStatus::ACTIVE->value,
            'started_at' => Carbon::parse('2026-09-27 00:30:00', 'UTC'),
        ]);

        return [$budi, $trip, $pos];
    }
}
```

- [ ] **Step 2: Jalankan dan pastikan gagal.**

Run: `php artisan test --compact --filter=ActiveHikeStripTest`
Expected: FAIL. Pada simulasi rencana: 7 test, 0 lulus, 4 gagal (prop `pendakianAktif` belum ada), 3 error (kelas
`PendakianAktif` dan `StripPendakian.tsx` belum ada).

- [ ] **Step 3: Satu kueri untuk pendakian berlangsung.** Buat kelas dengan
`DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:class --no-interaction Services/Trips/PendakianAktif`,
lalu ganti seluruh isi `app/Services/Trips/PendakianAktif.php` dengan:

```php
<?php

namespace App\Services\Trips;

use App\Enums\TripStatus;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Pendakian yang sedang berlangsung milik seorang pendaki, untuk strip di atas bilah tab
 * (spec v3 bagian 5.1; rencana induk W2 butir 4).
 *
 * Satu kueri: trip IN_PROGRESS milik pengguna (indeks trip_plans.user_id dan
 * trip_plans.status), digabung ke jalurnya, sesi mode lapangannya, dan pos yang sedang dituju
 * sesi itu (hiking_sessions.current_checkpoint_id, ditulis HikeCheckpointResolver). Semua
 * gabungan lewat kunci utama atau kunci asing yang berindeks, jadi biayanya tetap berapa pun
 * jumlah trip, pos, dan sesi. Waktu mulai dikirim sebagai instan ISO 8601; waktu berjalan
 * dihitung peramban, sehingga tidak ada zona waktu yang ditebak di sini.
 */
class PendakianAktif
{
    /**
     * @return array{id: int, jalur: string, posBerikutnya: string|null, mulai: string|null, url: string}|null
     */
    public function untuk(User $user): ?array
    {
        $baris = DB::table('trip_plans')
            ->join('trails', 'trails.id', '=', 'trip_plans.trail_id')
            ->leftJoin('hiking_sessions', 'hiking_sessions.trip_plan_id', '=', 'trip_plans.id')
            ->leftJoin('checkpoints', 'checkpoints.id', '=', 'hiking_sessions.current_checkpoint_id')
            ->where('trip_plans.user_id', $user->id)
            ->where('trip_plans.status', TripStatus::IN_PROGRESS->value)
            ->orderBy('trip_plans.planned_date')
            ->orderBy('trip_plans.id')
            ->select(['trip_plans.id', 'trails.name as jalur', 'checkpoints.name as pos_berikutnya', 'hiking_sessions.started_at as mulai'])
            ->first();

        if ($baris === null) {
            return null;
        }

        return [
            'id' => (int) $baris->id,
            'jalur' => (string) $baris->jalur,
            'posBerikutnya' => $baris->pos_berikutnya === null ? null : (string) $baris->pos_berikutnya,
            'mulai' => $baris->mulai === null ? null : CarbonImmutable::parse((string) $baris->mulai, 'UTC')->toIso8601String(),
            'url' => route('trips.hike', ['trip' => $baris->id], false),
        ];
    }
}
```

- [ ] **Step 4: Prop bersama.** Di `app/Http/Middleware/HandleInertiaRequests.php`:

1. Ganti:

```php
use App\Services\DataFreshnessService;
```

dengan:

```php
use App\Services\DataFreshnessService;
use App\Services\Trips\PendakianAktif;
```

2. Ganti:

```php
            'tema' => ThemePreference::current($request),
```

dengan:

```php
            // Strip pendakian aktif di atas bilah tab (spec v3 bagian 5.1): satu kueri, malas.
            // Null untuk tamu dan di mode lapangan itu sendiri, tempat strip tidak digambar.
            'pendakianAktif' => fn () => $user === null || $request->routeIs('trips.hike') ? null : app(PendakianAktif::class)->untuk($user),

            'tema' => ThemePreference::current($request),
```

Di `resources/js/types/index.d.ts`:

1. Ganti:

```ts
export interface PropsBersama {
```

dengan:

```ts
/** Pendakian berlangsung milik pengguna, untuk strip di atas bilah tab. */
export interface PendakianAktif {
    id: number;
    jalur: string;
    /** Pos yang sedang dituju sesi mode lapangan; null bila posisi belum pernah dikirim. */
    posBerikutnya: string | null;
    /** Instan mulai sesi, ISO 8601; waktu berjalan dihitung peramban. */
    mulai: string | null;
    url: string;
}

export interface PropsBersama {
```

2. Ganti:

```ts
    jumlahSimpanan: number | null;
```

dengan:

```ts
    jumlahSimpanan: number | null;
    pendakianAktif: PendakianAktif | null;
```

- [ ] **Step 5: Strip.** Buat `resources/js/layouts/StripPendakian.tsx`:

```tsx
import { useEffect, useLayoutEffect, useRef, useState } from 'react';
import { fokusTerlihat } from '@/components/ui/gaya';
import { Icon } from '@/components/ui/Icon';
import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';
import type { PendakianAktif } from '@/types';

/**
 * Lama berjalan dalam jam dan menit, bentuk yang sama dengan Durasi::panjang di server
 * ("2 jam 15 menit"). Jam perangkat yang tertinggal dari server tidak pernah menghasilkan
 * waktu negatif.
 */
export function lamaBerjalan(mulai: string, sekarang: number): string {
    const menit = Math.max(0, Math.floor((sekarang - Date.parse(mulai)) / 60_000));
    const jam = Math.floor(menit / 60);
    const sisa = menit % 60;

    if (jam === 0) {
        return `${sisa} menit`;
    }

    return sisa === 0 ? `${jam} jam` : `${jam} jam ${sisa} menit`;
}

/**
 * Strip pendakian aktif (spec v3 bagian 5.1; riset D4): selama trip berlangsung, strip
 * 48 px di atas bilah tab di setiap halaman berkerangka membawa pendaki kembali ke mode
 * lapangan dengan satu ketukan. Seluruh strip satu tautan; "Buka" hanya penanda bentuknya.
 * Isian merek yang solid, bukan kaca: bilah tab dan bar aksi sudah memakai dua jatah kaca
 * per layar. Waktu berjalan diperbarui tiap menit tanpa diumumkan, supaya pembaca layar
 * tidak diganggu setiap menit.
 *
 * Nama jalur dan pos yang panjang membungkus, jadi tinggi strip tidak tetap. Strip mengukur
 * dirinya dan menulis tingginya (ditambah jarak 8 px) ke --strip-pendakian di :root, sehingga
 * bar aksi, baki banding, dan toast selalu duduk di atasnya; nilai 3,5rem di app.css hanya
 * cadangan sebelum skrip berjalan.
 */
export function StripPendakian({ pendakian }: { pendakian: PendakianAktif }) {
    const [sekarang, setSekarang] = useState(() => Date.now());
    const wadah = useRef<HTMLDivElement>(null);

    useLayoutEffect(() => {
        const elemen = wadah.current;
        const akar = document.documentElement;

        if (!elemen) {
            return;
        }

        const ukur = () => akar.style.setProperty('--strip-pendakian', `${elemen.offsetHeight + 8}px`);
        const pengamat = new ResizeObserver(ukur);

        ukur();
        pengamat.observe(elemen);

        return () => {
            pengamat.disconnect();
            akar.style.removeProperty('--strip-pendakian');
        };
    }, []);

    useEffect(() => {
        const pewaktu = window.setInterval(() => setSekarang(Date.now()), 60_000);

        return () => window.clearInterval(pewaktu);
    }, []);

    const keterangan = [
        pendakian.posBerikutnya ? `Menuju ${pendakian.posBerikutnya}` : 'Pos berikutnya belum tercatat',
        pendakian.mulai ? `berjalan ${lamaBerjalan(pendakian.mulai, sekarang)}` : null,
    ]
        .filter(Boolean)
        .join(', ');

    return (
        <div ref={wadah} data-strip-pendakian="" className="pointer-events-none fixed inset-x-0 bottom-[calc(var(--bilah-tab)+0.5rem)] z-30 px-4">
            <Tautan
                href={pendakian.url}
                className={kelas('pointer-events-auto mx-auto flex min-h-12 max-w-md items-center gap-3 rounded-tombol bg-brand-700 px-4 py-1.5 text-on-brand shadow-overlay', fokusTerlihat)}
            >
                <Icon name="person-simple-hike" size={22} />
                <span className="min-w-0 flex-1">
                    <span className="sr-only">Pendakian berlangsung: </span>
                    <span className="block break-words text-kecil font-bold">{pendakian.jalur}</span>
                    <span className="block text-meta">{keterangan}</span>
                </span>
                <span className="shrink-0 text-kecil font-bold">Buka</span>
            </Tautan>
        </div>
    );
}
```

Di `resources/js/layouts/AppLayout.tsx`, strip dipasang sesudah `main` dan sebelum bilah tab, sama dengan letaknya di
layar:

1. Ganti:

```tsx
import { KepalaLebar } from './KepalaLebar';
import { itemTab } from './NavLinks';
```

dengan:

```tsx
import type { PropsBersama } from '@/types';
import { KepalaLebar } from './KepalaLebar';
import { itemTab } from './NavLinks';
import { StripPendakian } from './StripPendakian';
```

2. Ganti:

```tsx
    const jalur = usePage().url.split('?')[0] ?? '';
```

dengan:

```tsx
    const { url, props } = usePage<PropsBersama>();
    const jalur = url.split('?')[0] ?? '';
```

3. Ganti:

```tsx
            </main>
```

dengan:

```tsx
            </main>
            {props.pendakianAktif ? <StripPendakian pendakian={props.pendakianAktif} /> : null}
```

- [ ] **Step 6: Jalankan test tugas ini dan tetangganya sampai lulus.**

Run: `php artisan test --compact --filter='ActiveHikeStripTest|HikeModeCheckpointTest|PageQueryBudgetTest|TripLifecycleTest'`
Expected: PASS, 39 test.

- [ ] **Step 7: Gerbang tugas.**

Run: `vendor/bin/pint --format agent app/Services/Trips/PendakianAktif.php app/Http/Middleware/HandleInertiaRequests.php tests/Feature/ActiveHikeStripTest.php`
Expected: `{"tool":"pint","result":"passed"}` atau berkas dirapikan tanpa perubahan makna.

Run: `npx tsc --noEmit`
Expected: keluar tanpa keluaran galat.

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal. Pada simulasi rencana: 1.586 test, 1.584 lulus, 2 dilewati. Dampak pada anggaran kueri: +1 kueri
per kunjungan penuh, tetap berapa pun jumlah trip dan pos (`test_the_lookup_costs_one_query_whatever_the_number_of_trips_and_checkpoints`).

- [ ] **Step 8: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 5: Toast dan pesan jaringan dipasang sekali di kerangka

Induk W2 butir 6 dan ruling W2-R3. `AppLayout` persisten di antara kunjungan Inertia, jadi wadah toast dipasang di sana
sekali. Hasilnya satu wilayah pengumuman untuk seluruh aplikasi, dan toast yang muncul tepat sebelum pindah halaman
tidak hilang. Halaman memintanya lewat `useKerangka()`, dan tipe `tampilkanToast` diambil dari `useToastUrungkan`,
sehingga opsi W1 akhir ikut (W2-K10). `router.on('networkError')` memunculkan pesan jujur lewat `Alert` (W2-K15).
Lembar bawah milik halaman tidak butuh wadah, karena `LembarBawah` adalah `<dialog>` asli di top layer.

**Files:**
- Create: `resources/js/layouts/Kerangka.tsx`, `resources/js/layouts/PesanJaringan.tsx`
- Modify: `resources/js/layouts/AppLayout.tsx`, `resources/js/pages/Dev/Primitif.tsx`
- Test: `tests/Feature/ShellFeedbackTest.php` (baru)

**Interfaces:**
- Consumes: `useToastUrungkan()` dan `WadahToast({ toast, onTutup })` (W1 akhir; `tampilkan(pesan, onUrungkan, opsi?)`), `useFokusSesudahRender()`, `Alert({ variant, title, className?, children })`, `Button`, `TombolIkon({ ikon, label, varian?, ... })`, `router` dari `@inertiajs/react`.
- Produces:
  - `interface LayananKerangka { tampilkanToast: ReturnType<typeof useToastUrungkan>['tampilkan'] }`; Tugas 6 menambah `bukaPengingat`.
  - `KonteksKerangka` dan `useKerangka(): LayananKerangka`, yang melempar galat di luar `AppLayout`.
  - `PesanJaringan()`.

- [ ] **Step 1: Tulis test yang gagal.** Buat test dengan
`DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:test --phpunit --no-interaction ShellFeedbackTest`,
lalu ganti seluruh isinya dengan:

```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Umpan balik kerangka (rencana induk W2 butir 6; ruling W2-R3): wadah toast dipasang
 * sekali di AppLayout, dan kunjungan yang gagal karena jaringan menampilkan pesan jujur.
 *
 * Satu wadah berarti satu wilayah pengumuman untuk pembaca layar dan satu tempat toast di
 * layar; wadah per halaman menggandakan pengumuman dan hilang ketika halaman berganti.
 */
class ShellFeedbackTest extends TestCase
{
    use SumberReact;

    public function test_the_toast_container_is_mounted_once_in_the_layout(): void
    {
        $wadah = [];
        $kaitan = [];

        foreach ($this->berkasTsx() as $berkas) {
            $nama = str_replace('\\', '/', $berkas->getRelativePathname());

            if ($nama === 'components/ui/ToastUrungkan.tsx') {
                continue;
            }

            $isi = $this->tanpaKomentarJs($berkas->getContents());

            if (preg_match('/<WadahToast\b/', $isi)) {
                $wadah[] = $nama;
            }

            if (str_contains($isi, 'useToastUrungkan(')) {
                $kaitan[] = $nama;
            }
        }

        $this->assertSame(['layouts/AppLayout.tsx'], $wadah, 'Wadah toast dipasang di luar AppLayout.');
        $this->assertSame(['layouts/AppLayout.tsx'], $kaitan, 'Keadaan toast dipegang di luar AppLayout.');

        $layout = File::get(resource_path('js/layouts/AppLayout.tsx'));

        $this->assertStringContainsString('<KonteksKerangka value={layanan}>', $layout);
        $this->assertStringContainsString('<WadahToast toast={toast} onTutup={tutup} />', $layout);
        $this->assertStringContainsString('const { tampilkanToast: tampilkan } = useKerangka();', File::get(resource_path('js/pages/Dev/Primitif.tsx')));
    }

    /**
     * Halaman di luar AppLayout tidak punya kerangka; memakai useKerangka di sana adalah
     * galat pemrograman yang harus terlihat, bukan toast yang diam-diam tidak muncul.
     */
    public function test_the_shell_hook_refuses_to_work_outside_the_shell(): void
    {
        $kerangka = $this->tanpaKomentarJs(File::get(resource_path('js/layouts/Kerangka.tsx')));

        $this->assertStringContainsString('export const KonteksKerangka = createContext<LayananKerangka | null>(null);', $kerangka);
        $this->assertMatchesRegularExpression('/if \(layanan === null\) \{\s*throw new Error\(/', $kerangka);
    }

    /**
     * Pesan jaringan tidak mengaku tahu nasib aksi terakhir (yang putus bisa jawabannya),
     * diumumkan sebagai peringatan, memberi satu langkah untuk memastikan, dapat ditutup,
     * dan hilang sendiri ketika kunjungan berikutnya berhasil. Mengembalikan false mencegah
     * janji yang ditolak tanpa penangan.
     */
    public function test_a_network_error_shows_an_honest_message(): void
    {
        $pesan = $this->tanpaKomentarJs(File::get(resource_path('js/layouts/PesanJaringan.tsx')));

        $this->assertMatchesRegularExpression("/router\.on\('networkError', \(\) => \{\s*setTampil\(true\);\s*return false;\s*\}\)/", $pesan);
        $this->assertStringContainsString("router.on('success', () => setTampil(false))", $pesan);
        $this->assertStringContainsString('<Alert variant="warning" title="Tidak tersambung ke server"', $pesan, 'Peringatan lewat komponen Alert (role alert), bukan blok pesan rakitan sendiri.');
        $this->assertStringContainsString('onClick={() => router.reload()}', $pesan);
        $this->assertMatchesRegularExpression('/>\s*Muat ulang\s*<\/Button>/', $pesan);
        $this->assertStringContainsString('<TombolIkon ikon="x" label="Tutup pesan"', $pesan);
        $this->assertDoesNotMatchRegularExpression('/\b(?:aman|gagal disimpan|tidak tersimpan|sudah tersimpan)\b/i', $pesan);
        $this->assertStringContainsString('<PesanJaringan />', File::get(resource_path('js/layouts/AppLayout.tsx')));
    }
}
```

- [ ] **Step 2: Jalankan dan pastikan gagal.**

Run: `php artisan test --compact --filter=ShellFeedbackTest`
Expected: FAIL. Pada simulasi rencana: 3 test, 0 lulus, 1 gagal (wadah toast masih dipasang pameran), 2 error
(`Kerangka.tsx` dan `PesanJaringan.tsx` belum ada).

- [ ] **Step 3: Layanan kerangka.** Buat `resources/js/layouts/Kerangka.tsx`:

```tsx
import { createContext, useContext } from 'react';
import type { useToastUrungkan } from '@/components/ui/ToastUrungkan';

/**
 * Layanan kerangka yang dipasang sekali di AppLayout dan dipakai halaman (rencana induk W2
 * butir 6): toast urungkan dengan satu wilayah pengumuman untuk seluruh aplikasi, bukan satu
 * per halaman. Lembar bawah milik halaman tidak perlu wadah: LembarBawah adalah dialog asli
 * yang tinggal di top layer peramban.
 */
export interface LayananKerangka {
    /**
     * Toast urungkan primitif W1 (bertahan 8 detik, berhenti selama disentuh atau difokus,
     * W1-K7). Tanda tangannya diambil dari useToastUrungkan, jadi opsi yang ditambah W1 ikut
     * tanpa disalin di sini.
     */
    tampilkanToast: ReturnType<typeof useToastUrungkan>['tampilkan'];
}

export const KonteksKerangka = createContext<LayananKerangka | null>(null);

/**
 * Hanya di halaman yang memakai AppLayout. Halaman tamu dan mode lapangan tidak punya
 * kerangka ini, dan galat yang menyebutnya lebih jujur daripada toast yang diam-diam hilang.
 */
export function useKerangka(): LayananKerangka {
    const layanan = useContext(KonteksKerangka);

    if (layanan === null) {
        throw new Error('useKerangka hanya dapat dipakai di halaman yang memakai AppLayout.');
    }

    return layanan;
}
```

- [ ] **Step 4: Pesan jaringan.** Buat `resources/js/layouts/PesanJaringan.tsx`:

```tsx
import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { TombolIkon } from '@/components/ui/TombolIkon';

/**
 * Pesan jujur ketika kunjungan Inertia gagal karena jaringan (rencana induk W2 butir 6,
 * ruling W2-R3). Aplikasi tidak tahu apakah permintaan terakhir sempat sampai ke server:
 * yang putus bisa jawabannya, bukan permintaannya. Karena itu pesannya tidak mengaku apa
 * pun tentang nasib aksi terakhir, dan memberi satu langkah untuk memastikannya. Penawaran
 * "Buka paket luring" menyusul di W6.
 *
 * Mengembalikan false dari penangan networkError mencegah Inertia melempar ulang galatnya
 * sebagai janji yang ditolak tanpa penangan. Pesan hilang sendiri begitu kunjungan
 * berikutnya berhasil.
 */
export function PesanJaringan() {
    const [tampil, setTampil] = useState(false);

    useEffect(() => {
        const lepasGalat = router.on('networkError', () => {
            setTampil(true);

            return false;
        });
        const lepasBerhasil = router.on('success', () => setTampil(false));

        return () => {
            lepasGalat();
            lepasBerhasil();
        };
    }, []);

    if (!tampil) {
        return null;
    }

    return (
        <div className="pointer-events-none fixed inset-x-0 top-[calc(var(--kepala-atas)+env(safe-area-inset-top,0px)+0.5rem)] z-40 px-4">
            <div className="pointer-events-auto relative mx-auto max-w-xl">
                <Alert variant="warning" title="Tidak tersambung ke server" className="pe-14 shadow-overlay">
                    <p>
                        Permintaan terakhir tidak mendapat jawaban. Bila Anda baru menyimpan atau mengubah sesuatu, muat ulang setelah sinyal kembali untuk
                        memastikan perubahannya tercatat.
                    </p>
                    <Button variant="secondary" size="sm" className="mt-3" onClick={() => router.reload()}>
                        Muat ulang
                    </Button>
                </Alert>
                <TombolIkon ikon="x" label="Tutup pesan" varian="polos" className="absolute end-1 top-1" onClick={() => setTampil(false)} />
            </div>
        </div>
    );
}
```

- [ ] **Step 5: Pasang sekali di layout.** Di `resources/js/layouts/AppLayout.tsx`:

1. Ganti:

```tsx
import type { ReactNode } from 'react';
import { BilahTab } from '@/components/ui/BilahTab';
import { JudulHalaman } from '@/components/ui/JudulHalaman';
import type { PropsBersama } from '@/types';
import { KepalaLebar } from './KepalaLebar';
import { itemTab } from './NavLinks';
```

dengan:

```tsx
import { useMemo, type ReactNode } from 'react';
import { BilahTab } from '@/components/ui/BilahTab';
import { JudulHalaman } from '@/components/ui/JudulHalaman';
import { WadahToast, useToastUrungkan } from '@/components/ui/ToastUrungkan';
import type { PropsBersama } from '@/types';
import { KepalaLebar } from './KepalaLebar';
import { KonteksKerangka, type LayananKerangka } from './Kerangka';
import { itemTab } from './NavLinks';
import { PesanJaringan } from './PesanJaringan';
```

2. Ganti:

```tsx
 * halaman Inertia, bukan dari window, supaya sama di server, pratinjau, dan peramban.
```

dengan:

```tsx
 * halaman Inertia, bukan dari window, supaya sama di server, pratinjau, dan peramban.
 *
 * Layout ini persisten di antara kunjungan Inertia, jadi wadah toast dan pesan jaringan
 * dipasang di sini sekali: toast yang muncul tepat sebelum pindah halaman tidak hilang, dan
 * halaman memintanya lewat useKerangka(), bukan memasang wadahnya sendiri.
```

3. Ganti:

```tsx
    const tab = itemTab(jalur);

    return (
        <div className="min-h-dvh bg-canvas">
            <JudulHalaman />
            <KepalaLebar item={tab} />
            <main id="konten" tabIndex={-1} className="pb-[var(--nav-bawah)]">
                {children}
            </main>
            {props.pendakianAktif ? <StripPendakian pendakian={props.pendakianAktif} /> : null}
            {bilahTab ? <BilahTab item={tab} /> : null}
        </div>
```

dengan:

```tsx
    const tab = itemTab(jalur);
    const { toast, tampilkan, tutup } = useToastUrungkan();
    const layanan = useMemo<LayananKerangka>(() => ({ tampilkanToast: tampilkan }), [tampilkan]);

    return (
        <KonteksKerangka value={layanan}>
            <div className="min-h-dvh bg-canvas">
                <JudulHalaman />
                <KepalaLebar item={tab} />
                <PesanJaringan />
                <main id="konten" tabIndex={-1} className="pb-[var(--nav-bawah)]">
                    {children}
                </main>
                {props.pendakianAktif ? <StripPendakian pendakian={props.pendakianAktif} /> : null}
                {bilahTab ? <BilahTab item={tab} /> : null}
                <WadahToast toast={toast} onTutup={tutup} />
            </div>
        </KonteksKerangka>
```

- [ ] **Step 6: Pameran memakai toast kerangka.** Di `resources/js/pages/Dev/Primitif.tsx`, keadaan dan wadah toast
milik pameran diganti layanan kerangka; `useFokusSesudahRender` tetap dipakai:

`resources/js/pages/Dev/Primitif.tsx`:

1. Ganti:

```tsx
import { WadahToast, useFokusSesudahRender, useToastUrungkan } from '@/components/ui/ToastUrungkan';
import { AppLayout } from '@/layouts/AppLayout';
```

dengan:

```tsx
import { useFokusSesudahRender } from '@/components/ui/ToastUrungkan';
import { AppLayout } from '@/layouts/AppLayout';
import { useKerangka } from '@/layouts/Kerangka';
```

2. Ganti:

```tsx
    const { toast, tampilkan, tutup } = useToastUrungkan();
```

dengan:

```tsx
    const { tampilkanToast: tampilkan } = useKerangka();
```

3. Ganti:

```tsx
            </Dialog>
            <WadahToast toast={toast} onTutup={tutup} />
```

dengan:

```tsx
            </Dialog>
```

- [ ] **Step 7: Jalankan test tugas ini dan tetangganya sampai lulus.**

Run: `php artisan test --compact --filter='ShellFeedbackTest|DevPrimitivesRouteTest|DesignPrimitivesTest|DesignSystemTest'`
Expected: PASS, 60 test.

- [ ] **Step 8: Gerbang tugas.**

Run: `vendor/bin/pint --format agent tests/Feature/ShellFeedbackTest.php`
Expected: `{"tool":"pint","result":"passed"}`

Run: `npx tsc --noEmit`
Expected: keluar tanpa keluaran galat. Pemanggilan `tampilkan(pesan, onUrungkan, { fokus })` di pameran lolos, karena
tipenya diambil dari hook W1.

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal. Pada simulasi rencana: 1.589 test, 1.587 lulus, 2 dilewati.

- [ ] **Step 9: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 6: Lembar Pengingat dari lonceng Beranda

Induk W2 butir 5, ruling W2-R2, dan DK-40. Lonceng di kepala Beranda membuka lembar "Pengingat" yang dipasang sekali
di `AppLayout`. Isinya dua daftar milik pengguna sendiri:

- pengingat surel H-3 dan H-1 yang masih menunggu, dari `trip_reminders`;
- tenggat pemesanan izin trip mendatang, dari `PermitService`.

Isinya prop bersama opsional `daftarPengingat`, yang diminta dengan muat ulang parsial setiap kali lembar dibuka
(W2-K11). Lembar punya empat keadaan: memuat, galat, kosong yang menyebut apa yang akan muncul, dan daftar. Notifikasi
dorong di luar cakupan (Q6), dan keterangan lembar mengatakannya. Lonceng memberi dirinya sebagai pemicu lembar
(W2-K12).

**Files:**
- Create: `app/Services/Trips/DaftarPengingat.php`, `resources/js/layouts/Pengingat.tsx`
- Modify: `app/Http/Middleware/HandleInertiaRequests.php`, `resources/js/types/index.d.ts`, `resources/js/layouts/Kerangka.tsx`, `resources/js/layouts/AppLayout.tsx`, `resources/js/pages/Dashboard.tsx`
- Test: `tests/Feature/ReminderSheetTest.php` (baru)

**Interfaces:**
- Consumes: `TripReminder` (scope `menunggu()`), `TripReminderScheduler::label()` dan `sinkronkan()`, `PermitService::requirementsForTrails()` dan `bookingWindowFor()`, konstanta `PermitService::BOOKING_*`, `Timezone`, `LembarBawah({ terbuka, onTutup, judul, keterangan?, pemicu?, children })` (W1 akhir), `EmptyState`, `Skeleton`, `Alert`, `Button`, `TombolIkon`, `Tautan`, `useKerangka()` dari Tugas 5.
- Produces:
  - `DaftarPengingat::untuk(User $user): array{pengingat: list<array{id, trip, jenis, waktu, url}>, tenggatIzin: list<array{tripId, trip, keadaan, kalimat, pengelola, url}>}`.
  - Prop bersama opsional `daftarPengingat?: DaftarPengingat | null` dan `export interface DaftarPengingat` di `@/types`.
  - `LayananKerangka.bukaPengingat(pemicu: HTMLElement): void`.
  - `TombolPengingat()` dan `LembarPengingat({ terbuka, onTutup, pemicu })`.

- [ ] **Step 1: Tulis test yang gagal.** Buat test dengan
`DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:test --phpunit --no-interaction ReminderSheetTest`,
lalu ganti seluruh isinya dengan:

```php
<?php

namespace Tests\Feature;

use App\Enums\TripStatus;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Mountain;
use App\Models\PermitRequirement;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\TripReminder;
use App\Models\User;
use App\Services\Trips\DaftarPengingat;
use App\Services\Trips\TripReminderScheduler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Lembar Pengingat dari lonceng Beranda (spec v3 bagian 5.1; rencana induk W2 butir 5;
 * ruling W2-R2): pengingat surel terjadwal dan tenggat izin milik pengguna sendiri, dimuat
 * hanya ketika lembar dibuka. Notifikasi dorong di luar cakupan (Q6).
 */
class ReminderSheetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 27 September 2026 10.00 WIB. Trip Budi 12 Oktober: H-3 jatuh Jumat 9 Oktober.
        Carbon::setTestNow('2026-09-27 03:00:00');
    }

    public function test_a_normal_visit_never_pays_for_the_reminders(): void
    {
        [$budi] = $this->budiDenganTrip();

        $this->actingAs($budi)->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->missing('daftarPengingat'));
    }

    public function test_the_sheet_lists_the_owners_scheduled_reminders_and_permit_deadlines(): void
    {
        [$budi, $trip] = $this->budiDenganTrip();
        [$sari] = $this->budiDenganTrip('sari@contoh.test', 'Trip Sari');

        $this->actingAs($budi)->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->reloadOnly('daftarPengingat', fn (AssertableInertia $muatUlang) => $muatUlang
                ->has('daftarPengingat.pengingat', 2)
                ->where('daftarPengingat.pengingat.0.trip', 'Gede via Cibodas')
                ->where('daftarPengingat.pengingat.0.jenis', '3 hari sebelum berangkat')
                ->where('daftarPengingat.pengingat.0.waktu', 'Jumat, 09 Okt 2026 07:00 WIB')
                ->where('daftarPengingat.pengingat.0.url', '/trips/'.$trip->id)
                ->where('daftarPengingat.pengingat.1.jenis', 'Sehari sebelum berangkat')
                ->has('daftarPengingat.tenggatIzin', 1)
                ->where('daftarPengingat.tenggatIzin.0.tripId', $trip->id)
                ->where('daftarPengingat.tenggatIzin.0.keadaan', 'TERBUKA')
                ->where('daftarPengingat.tenggatIzin.0.kalimat', 'Pemesanan izin ditutup Jumat, 09 Okt 2026 (H-3).')
                ->where('daftarPengingat.tenggatIzin.0.pengelola', 'Balai Besar TNGGP')));

        $this->actingAs($sari)->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->reloadOnly('daftarPengingat', fn (AssertableInertia $muatUlang) => $muatUlang
                ->has('daftarPengingat.pengingat', 2)
                ->where('daftarPengingat.pengingat.0.trip', 'Trip Sari')
                ->where('daftarPengingat.pengingat.1.trip', 'Trip Sari')));
    }

    /**
     * Yang sudah terkirim, yang dilewati, dan yang waktunya sudah lewat bukan lagi pengingat
     * terjadwal; trip yang sudah lewat tanggalnya atau dibatalkan tidak punya tenggat.
     */
    public function test_only_what_is_still_ahead_is_listed(): void
    {
        [$budi, $trip] = $this->budiDenganTrip();

        $trip->reminders()->where('jenis', 'H-3')->update(['sent_at' => now()]);
        TripPlan::factory()->for($budi)->create(['status' => TripStatus::CANCELLED->value, 'planned_date' => '2026-10-20']);
        TripPlan::factory()->for($budi)->create(['status' => TripStatus::PLANNED->value, 'planned_date' => '2026-09-20']);

        $daftar = app(DaftarPengingat::class)->untuk($budi);

        $this->assertSame(['Sehari sebelum berangkat'], array_column($daftar['pengingat'], 'jenis'));
        $this->assertSame([$trip->id], array_column($daftar['tenggatIzin'], 'tripId'));
    }

    /**
     * Pemesanan yang belum dibuka menyebut tanggal bukanya; aturan tanpa tanggal tutup tidak
     * mengarang tanggal (R-007).
     */
    public function test_a_window_not_yet_open_names_its_opening_and_a_missing_closing_date_is_not_invented(): void
    {
        [$budi, $trip] = $this->budiDenganTrip();
        $trip->update(['planned_date' => '2026-12-15']);
        PermitRequirement::query()->update(['booking_opens_days_before' => 30]);

        $this->assertSame('Pemesanan izin dibuka Minggu, 15 Nov 2026 (H-30).', app(DaftarPengingat::class)->untuk($budi)['tenggatIzin'][0]['kalimat']);

        PermitRequirement::query()->update(['booking_opens_days_before' => null, 'booking_closes_days_before' => null]);

        $this->assertSame('Pemesanan izin sedang dibuka. Tanggal penutupannya belum tercatat.', app(DaftarPengingat::class)->untuk($budi)['tenggatIzin'][0]['kalimat']);
    }

    public function test_an_empty_sheet_says_what_will_appear_there(): void
    {
        $budi = User::factory()->create();

        $this->assertSame(['pengingat' => [], 'tenggatIzin' => []], app(DaftarPengingat::class)->untuk($budi));

        $lembar = File::get(resource_path('js/layouts/Pengingat.tsx'));

        $this->assertStringContainsString('title="Belum ada pengingat"', $lembar);
        $this->assertStringContainsString('description="Pengingat surel H-3 dan H-1 untuk trip yang pengingatnya Anda nyalakan, dan tenggat pemesanan izin untuk trip mendatang, akan muncul di sini."', $lembar);
    }

    public function test_a_guest_gets_nothing(): void
    {
        $this->get('/')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->reloadOnly('daftarPengingat', fn (AssertableInertia $muatUlang) => $muatUlang->where('daftarPengingat', null)));
    }

    public function test_the_list_costs_a_fixed_number_of_queries(): void
    {
        [$budi] = $this->budiDenganTrip();

        $sedikit = $this->hitungKueri($budi);

        for ($i = 0; $i < 8; $i++) {
            $trip = TripPlan::factory()->for($budi)->create(['planned_date' => '2026-10-'.(13 + $i), 'reminders_enabled' => true]);
            app(TripReminderScheduler::class)->sinkronkan($trip);
        }

        $this->assertSame($sedikit, $this->hitungKueri($budi));
        $this->assertLessThanOrEqual(5, $sedikit);
    }

    /**
     * Lonceng di kepala Beranda membuka lembar yang dipasang sekali di AppLayout; lembar
     * memuat isinya dengan muat ulang parsial dan menangani sendiri kegagalannya. Lonceng
     * memberi dirinya sebagai pemicu, jadi fokus kembali ke sana saat lembar ditutup, juga di
     * Safari yang tidak memfokus tombol yang diklik.
     */
    public function test_the_bell_opens_the_sheet_mounted_once_in_the_layout(): void
    {
        $lembar = File::get(resource_path('js/layouts/Pengingat.tsx'));
        $layout = File::get(resource_path('js/layouts/AppLayout.tsx'));

        $this->assertStringContainsString('aksi={<TombolPengingat />}', File::get(resource_path('js/pages/Dashboard.tsx')));
        $this->assertStringContainsString('<TombolIkon ikon="bell" label="Pengingat" onClick={(e) => bukaPengingat(e.currentTarget)} />', $lembar);
        $this->assertStringContainsString('<LembarBawah terbuka={terbuka} onTutup={onTutup} pemicu={pemicu} judul="Pengingat"', $lembar);
        $this->assertStringContainsString("only: ['daftarPengingat'],", $lembar);
        $this->assertSame(2, substr_count($lembar, 'return false;'), 'Galat jaringan dan jawaban bukan halaman ditangani lembar sendiri.');
        $this->assertStringContainsString('<Skeleton rows={4} />', $lembar);
        $this->assertStringContainsString('<Alert variant="warning" title="Pengingat belum dapat dimuat">', $lembar);
        $this->assertStringContainsString('<LembarPengingat terbuka={pengingatTerbuka} onTutup={() => setPengingatTerbuka(false)} pemicu={pemicuPengingat} />', $layout);
        $this->assertSame(1, substr_count($layout, '<LembarPengingat'));
    }

    /**
     * Props bersama bergabung dengan props halaman, dan props halaman menang. Nama yang sama
     * di keduanya membuat kerangka membaca data halaman dengan bentuk yang salah; tabrakan
     * itu sempat terjadi ketika lembar ini dibangun (Trips/Show sudah punya prop `pengingat`).
     */
    public function test_shell_props_never_share_a_name_with_page_props(): void
    {
        $bersama = array_keys(app(HandleInertiaRequests::class)->share(Request::create('/')));
        $tabrakan = [];

        foreach (['Http/Controllers', 'Services'] as $folder) {
            foreach (File::allFiles(app_path($folder)) as $berkas) {
                foreach ($bersama as $nama) {
                    if (preg_match("/'".preg_quote($nama, '/')."' =>/", $berkas->getContents())) {
                        $tabrakan[] = $berkas->getRelativePathname().': '.$nama;
                    }
                }
            }
        }

        $this->assertContains('daftarPengingat', $bersama);
        $this->assertSame([], $tabrakan, 'Prop halaman bernama sama dengan prop bersama: '.implode(', ', $tabrakan));
    }

    /**
     * Budi dengan trip mendatang Gede via Cibodas (12 Oktober 2026), pengingat H-3 dan H-1
     * menyala, dan aturan izin tingkat gunung: dibuka H-30, ditutup H-3.
     *
     * @return array{0: User, 1: TripPlan}
     */
    private function budiDenganTrip(string $surel = 'budi@contoh.test', string $nama = 'Gede via Cibodas'): array
    {
        $budi = User::factory()->create(['email' => $surel]);
        $gunung = Mountain::factory()->create(['timezone' => 'Asia/Jakarta']);
        $jalur = Trail::factory()->for($gunung)->create(['name' => 'Gede via Cibodas']);

        PermitRequirement::create([
            'mountain_id' => $gunung->id,
            'authority' => 'Balai Besar TNGGP',
            'booking_opens_days_before' => 30,
            'booking_closes_days_before' => 3,
        ]);

        $trip = TripPlan::factory()->for($budi)->for($jalur)->create([
            'name' => $nama,
            'planned_date' => '2026-10-12',
            'reminders_enabled' => true,
        ]);

        app(TripReminderScheduler::class)->sinkronkan($trip);

        $this->assertSame(2, TripReminder::where('trip_plan_id', $trip->id)->count());

        return [$budi, $trip];
    }

    private function hitungKueri(User $user): int
    {
        $kueri = 0;
        DB::listen(function () use (&$kueri) {
            $kueri++;
        });

        app(DaftarPengingat::class)->untuk($user);

        DB::getEventDispatcher()->forget('Illuminate\Database\Events\QueryExecuted');

        return $kueri;
    }
}
```

- [ ] **Step 2: Jalankan dan pastikan gagal.**

Run: `php artisan test --compact --filter=ReminderSheetTest`
Expected: FAIL. Pada simulasi rencana: 9 test, 1 lulus, 3 gagal (prop `daftarPengingat` belum ada), 5 error (kelas
`DaftarPengingat` dan `Pengingat.tsx` belum ada). `test_a_normal_visit_never_pays_for_the_reminders` lulus sejak merah
karena prop itu belum ada sama sekali; sesudah tugas ini ia menjaga sifat opsionalnya.

- [ ] **Step 3: Daftar pengingat dan tenggat izin.** Buat kelas dengan
`DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:class --no-interaction Services/Trips/DaftarPengingat`,
lalu ganti seluruh isi `app/Services/Trips/DaftarPengingat.php` dengan:

```php
<?php

namespace App\Services\Trips;

use App\Enums\TripStatus;
use App\Models\PermitRequirement;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\PermitService;
use App\Support\Timezone;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;

/**
 * Isi lembar Pengingat dari lonceng Beranda (spec v3 bagian 5.1; rencana induk W2 butir 5;
 * ruling W2-R2): pengingat surel H-3 dan H-1 yang masih terjadwal, dan tenggat pemesanan izin,
 * keduanya untuk trip mendatang milik pengguna sendiri. Notifikasi dorong tidak ada (Q6), jadi
 * lembar ini daftar, bukan kotak masuk.
 *
 * Biayanya tetap: trip mendatang beserta jalur, gunung, dan pengingat yang menunggu (empat
 * kueri eager), ditambah satu kueri aturan izin untuk semua jalurnya sekaligus
 * (PermitService::requirementsForTrails), untuk paling banyak TRIP_MAKS trip terdekat. Waktu
 * pengingat ditulis dalam zona gunungnya dengan penanda WIB, WITA, atau WIT; tanggal izin
 * dihitung dari tanggal rencana, jadi tidak bergantung zona.
 */
class DaftarPengingat
{
    private const TRIP_MAKS = 10;

    private const FORMAT_WAKTU = 'l, d M Y H:i';

    private const FORMAT_TANGGAL = 'l, d M Y';

    public function __construct(private PermitService $izin) {}

    /**
     * @return array{
     *     pengingat: list<array{id: int, trip: string, jenis: string, waktu: string, url: string}>,
     *     tenggatIzin: list<array{tripId: int, trip: string, keadaan: string, kalimat: string, pengelola: string, url: string}>
     * }
     */
    public function untuk(User $user): array
    {
        $sekarang = Carbon::now();

        $trips = TripPlan::query()
            ->whereBelongsTo($user)
            ->whereIn('status', [TripStatus::PLANNED->value, TripStatus::READY_FOR_DEPARTURE->value])
            ->whereDate('planned_date', '>=', Timezone::earliestDateInIndonesia())
            ->with([
                'trail.mountain',
                'reminders' => fn ($query) => $query->menunggu()->where('dijadwalkan_untuk', '>', $sekarang)->orderBy('dijadwalkan_untuk'),
            ])
            ->orderBy('planned_date')
            ->orderBy('id')
            ->limit(self::TRIP_MAKS)
            ->get();

        $aturan = $this->izin->requirementsForTrails(new EloquentCollection($trips->pluck('trail')->filter()->unique('id')->values()->all()));

        $pengingat = [];
        $tenggat = [];

        foreach ($trips as $trip) {
            $zona = Timezone::forTrail($trip->trail);
            $url = route('trips.show', $trip, false);

            foreach ($trip->reminders as $satu) {
                $pengingat[] = [
                    'urutan' => $satu->dijadwalkan_untuk->getTimestamp(),
                    'id' => $satu->id,
                    'trip' => $trip->name,
                    'jenis' => TripReminderScheduler::label($satu->jenis),
                    'waktu' => (string) Timezone::display($satu->dijadwalkan_untuk, $zona, self::FORMAT_WAKTU),
                    'url' => $url,
                ];
            }

            $butir = $this->tenggatIzin($trip, $aturan[$trip->trail_id] ?? null, $url);

            if ($butir !== null) {
                $tenggat[] = $butir;
            }
        }

        usort($pengingat, fn (array $a, array $b) => $a['urutan'] <=> $b['urutan']);
        usort($tenggat, fn (array $a, array $b) => ($a['urutan'] ?? PHP_INT_MAX) <=> ($b['urutan'] ?? PHP_INT_MAX));

        return [
            'pengingat' => array_map(fn (array $p) => array_diff_key($p, ['urutan' => true]), $pengingat),
            'tenggatIzin' => array_map(fn (array $t) => array_diff_key($t, ['urutan' => true]), $tenggat),
        ];
    }

    /**
     * Satu kalimat tentang jendela pemesanan izin trip ini, atau null bila tidak ada aturan
     * izin yang tercatat. Aritmetika jendelanya tetap milik PermitService; di sini hanya
     * tanggal yang ditulis. Batas yang tidak tercatat disebut belum tercatat, tidak ditebak.
     *
     * @return array{urutan: int|null, tripId: int, trip: string, keadaan: string, kalimat: string, pengelola: string, url: string}|null
     */
    private function tenggatIzin(TripPlan $trip, ?PermitRequirement $aturan, string $url): ?array
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

        // Kalimat dan tanggal patokan urutan untuk "<awal> <tanggal> (H-<hari>).".
        $tulis = function (string $awal, int $hari) use ($trip): array {
            $tanggal = $trip->planned_date->copy()->subDays($hari);

            return [sprintf('%s %s (H-%d).', $awal, $tanggal->translatedFormat(self::FORMAT_TANGGAL), $hari), $tanggal];
        };

        [$kalimat, $patokan] = match (true) {
            $jendela['state'] === PermitService::BOOKING_BELUM_DIBUKA && $buka !== null => $tulis('Pemesanan izin dibuka', $buka),
            $jendela['state'] === PermitService::BOOKING_DITUTUP && $tutup !== null => $tulis('Pemesanan izin sudah ditutup', $tutup),
            $tutup !== null => $tulis('Pemesanan izin ditutup', $tutup),
            default => ['Pemesanan izin sedang dibuka. Tanggal penutupannya belum tercatat.', null],
        };

        return [
            'urutan' => $patokan?->getTimestamp(),
            'tripId' => $trip->id,
            'trip' => $trip->name,
            'keadaan' => $jendela['state'],
            'kalimat' => $kalimat,
            'pengelola' => $aturan->authority,
            'url' => $url,
        ];
    }
}
```

- [ ] **Step 4: Prop bersama opsional.** Di `app/Http/Middleware/HandleInertiaRequests.php`:

1. Ganti:

```php
use App\Services\DataFreshnessService;
```

dengan:

```php
use App\Services\DataFreshnessService;
use App\Services\Trips\DaftarPengingat;
```

2. Ganti:

```php
use Illuminate\Http\Request;
```

dengan:

```php
use Illuminate\Http\Request;
use Inertia\Inertia;
```

3. Ganti:

```php
            'tema' => ThemePreference::current($request),
```

dengan:

```php
            // Isi lembar Pengingat (spec v3 bagian 5.1): opsional, jadi tidak pernah ikut
            // kunjungan biasa. Lembar memintanya dengan muat ulang parsial saat dibuka.
            'daftarPengingat' => Inertia::optional(fn () => $user === null ? null : app(DaftarPengingat::class)->untuk($user)),

            'tema' => ThemePreference::current($request),
```

Di `resources/js/types/index.d.ts`:

1. Ganti:

```ts
export interface PropsBersama {
```

dengan:

```ts
/** Isi lembar Pengingat: pengingat surel terjadwal dan tenggat izin trip mendatang. */
export interface DaftarPengingat {
    pengingat: Array<{ id: number; trip: string; jenis: string; waktu: string; url: string }>;
    tenggatIzin: Array<{ tripId: number; trip: string; keadaan: 'BELUM_DIBUKA' | 'TERBUKA' | 'DITUTUP'; kalimat: string; pengelola: string; url: string }>;
}

export interface PropsBersama {
```

2. Ganti:

```ts
    pendakianAktif: PendakianAktif | null;
```

dengan:

```ts
    pendakianAktif: PendakianAktif | null;
    /**
     * Hanya ada sesudah muat ulang parsial only: ['daftarPengingat']; null untuk tamu. Bukan
     * `pengingat`: nama itu sudah prop halaman Trips/Show (kartu pengingat satu trip).
     */
    daftarPengingat?: DaftarPengingat | null;
```

- [ ] **Step 5: Layanan membuka lembar.** Di `resources/js/layouts/Kerangka.tsx`:

1. Ganti:

```tsx
 * butir 6): toast urungkan dengan satu wilayah pengumuman untuk seluruh aplikasi, bukan satu
 * per halaman. Lembar bawah milik halaman tidak perlu wadah: LembarBawah adalah dialog asli
 * yang tinggal di top layer peramban.
```

dengan:

```tsx
 * butir 5 dan 6): toast urungkan dengan satu wilayah pengumuman untuk seluruh aplikasi, dan
 * lembar Pengingat milik kerangka. Lembar bawah milik halaman tidak perlu wadah: LembarBawah
 * adalah dialog asli yang tinggal di top layer peramban.
```

2. Ganti:

```tsx
    tampilkanToast: ReturnType<typeof useToastUrungkan>['tampilkan'];
```

dengan:

```tsx
    tampilkanToast: ReturnType<typeof useToastUrungkan>['tampilkan'];
    /**
     * Buka lembar Pengingat; isinya dimuat saat itu juga. Pemanggil memberi tombol pemicunya
     * supaya fokus kembali ke sana saat lembar ditutup, juga di Safari yang tidak memfokus
     * tombol yang diklik.
     */
    bukaPengingat: (pemicu: HTMLElement) => void;
```

- [ ] **Step 6: Lonceng dan lembar.** Buat `resources/js/layouts/Pengingat.tsx`:

```tsx
import { router } from '@inertiajs/react';
import { useCallback, useEffect, useState, type RefObject } from 'react';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { LembarBawah } from '@/components/ui/Lembar';
import { Skeleton } from '@/components/ui/Skeleton';
import { Tautan } from '@/components/ui/Tautan';
import { TombolIkon } from '@/components/ui/TombolIkon';
import { index as tripsIndex } from '@/routes/trips';
import type { DaftarPengingat, PropsBersama } from '@/types';
import { useKerangka } from './Kerangka';

type Keadaan = { jenis: 'memuat' } | { jenis: 'galat' } | { jenis: 'siap'; daftar: DaftarPengingat };

const baris = 'flex min-h-11 flex-col justify-center gap-0.5 rounded-control px-2 py-2 hover:bg-surface-sunken focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-600';

/**
 * Lonceng Beranda (spec v3 bagian 5.1; DK-40): membuka daftar pengingat yang benar-benar
 * ada. Tanpa titik atau angka di atasnya: isinya baru dimuat saat lembar dibuka, dan lonceng
 * yang menjanjikan jumlah yang belum dihitung akan berbohong.
 */
export function TombolPengingat() {
    const { bukaPengingat } = useKerangka();

    return <TombolIkon ikon="bell" label="Pengingat" onClick={(e) => bukaPengingat(e.currentTarget)} />;
}

/**
 * Lembar Pengingat, dipasang sekali di AppLayout (rencana induk W2 butir 5 dan 6; ruling
 * W2-R2). Isinya props bersama opsional `daftarPengingat` yang diminta lewat muat ulang parsial
 * setiap kali lembar dibuka, jadi kunjungan biasa tidak pernah membayarnya dan isinya selalu
 * terbaru. Empat keadaan: memuat, galat (jaringan putus atau jawaban bukan halaman), kosong
 * yang menyebut apa yang akan muncul di sini, dan daftar. Galatnya ditangani di sini dan
 * tidak diteruskan ke pesan jaringan global, supaya satu kegagalan tidak dilaporkan dua kali.
 */
export function LembarPengingat({ terbuka, onTutup, pemicu }: { terbuka: boolean; onTutup: () => void; pemicu: RefObject<HTMLElement | null> }) {
    const [keadaan, setKeadaan] = useState<Keadaan>({ jenis: 'memuat' });

    const muat = useCallback(() => {
        setKeadaan({ jenis: 'memuat' });
        router.reload({
            only: ['daftarPengingat'],
            onSuccess: (halaman) => {
                const daftar = (halaman.props as Partial<PropsBersama>).daftarPengingat;
                setKeadaan(daftar ? { jenis: 'siap', daftar } : { jenis: 'galat' });
            },
            onNetworkError: () => {
                setKeadaan({ jenis: 'galat' });

                return false;
            },
            onHttpException: () => {
                setKeadaan({ jenis: 'galat' });

                return false;
            },
        });
    }, []);

    useEffect(() => {
        if (terbuka) {
            muat();
        }
    }, [terbuka, muat]);

    return (
        <LembarBawah terbuka={terbuka} onTutup={onTutup} pemicu={pemicu} judul="Pengingat" keterangan="Pengingat surel terjadwal dan tenggat izin untuk trip Anda. Memount belum mengirim notifikasi ke ponsel.">
            {keadaan.jenis === 'memuat' ? (
                <Skeleton rows={4} />
            ) : keadaan.jenis === 'galat' ? (
                <Alert variant="warning" title="Pengingat belum dapat dimuat">
                    <p>Sambungan ke server terputus atau server tidak menjawab. Daftar ini tidak ditebak dari data lama.</p>
                    <Button variant="secondary" size="sm" className="mt-3" onClick={muat}>
                        Coba lagi
                    </Button>
                </Alert>
            ) : keadaan.daftar.pengingat.length === 0 && keadaan.daftar.tenggatIzin.length === 0 ? (
                <EmptyState
                    jenis="pertama"
                    title="Belum ada pengingat"
                    description="Pengingat surel H-3 dan H-1 untuk trip yang pengingatnya Anda nyalakan, dan tenggat pemesanan izin untuk trip mendatang, akan muncul di sini."
                >
                    <Button variant="secondary" href={tripsIndex.url()}>
                        Lihat trip Anda
                    </Button>
                </EmptyState>
            ) : (
                <div className="space-y-6">
                    {keadaan.daftar.pengingat.length > 0 ? (
                        <section aria-labelledby="pengingat-surel">
                            <h3 id="pengingat-surel" className="text-subjudul text-primary">
                                Pengingat surel terjadwal
                            </h3>
                            <ul className="mt-2 divide-y divide-subtle">
                                {keadaan.daftar.pengingat.map((p) => (
                                    <li key={p.id}>
                                        <Tautan href={p.url} className={baris}>
                                            <span className="font-bold text-primary">{p.trip}</span>
                                            <span className="text-kecil text-secondary">
                                                {p.jenis}: {p.waktu}
                                            </span>
                                        </Tautan>
                                    </li>
                                ))}
                            </ul>
                        </section>
                    ) : null}
                    {keadaan.daftar.tenggatIzin.length > 0 ? (
                        <section aria-labelledby="pengingat-izin">
                            <h3 id="pengingat-izin" className="text-subjudul text-primary">
                                Tenggat izin
                            </h3>
                            <ul className="mt-2 divide-y divide-subtle">
                                {keadaan.daftar.tenggatIzin.map((t) => (
                                    <li key={t.tripId}>
                                        <Tautan href={t.url} className={baris}>
                                            <span className="font-bold text-primary">{t.trip}</span>
                                            <span className="text-kecil text-primary">{t.kalimat}</span>
                                            <span className="text-kecil text-secondary">Pengelola: {t.pengelola}</span>
                                        </Tautan>
                                    </li>
                                ))}
                            </ul>
                            <p className="mt-2 text-kecil text-secondary">Memount menautkan sistem resmi dan tidak menjual izin.</p>
                        </section>
                    ) : null}
                </div>
            )}
        </LembarBawah>
    );
}
```

Di `resources/js/layouts/AppLayout.tsx`:

1. Ganti:

```tsx
import { useMemo, type ReactNode } from 'react';
```

dengan:

```tsx
import { useCallback, useMemo, useRef, useState, type ReactNode } from 'react';
```

2. Ganti:

```tsx
import { itemTab } from './NavLinks';
```

dengan:

```tsx
import { itemTab } from './NavLinks';
import { LembarPengingat } from './Pengingat';
```

3. Ganti:

```tsx
 * Layout ini persisten di antara kunjungan Inertia, jadi wadah toast dan pesan jaringan
 * dipasang di sini sekali: toast yang muncul tepat sebelum pindah halaman tidak hilang, dan
 * halaman memintanya lewat useKerangka(), bukan memasang wadahnya sendiri.
```

dengan:

```tsx
 * Layout ini persisten di antara kunjungan Inertia, jadi wadah toast, lembar Pengingat, dan
 * pesan jaringan dipasang di sini sekali: toast yang muncul tepat sebelum pindah halaman
 * tidak hilang, dan halaman memintanya lewat useKerangka(), bukan memasang wadahnya sendiri.
```

4. Ganti:

```tsx
    const layanan = useMemo<LayananKerangka>(() => ({ tampilkanToast: tampilkan }), [tampilkan]);
```

dengan:

```tsx
    const [pengingatTerbuka, setPengingatTerbuka] = useState(false);
    const pemicuPengingat = useRef<HTMLElement | null>(null);
    const bukaPengingat = useCallback((pemicu: HTMLElement) => {
        pemicuPengingat.current = pemicu;
        setPengingatTerbuka(true);
    }, []);
    const layanan = useMemo<LayananKerangka>(() => ({ tampilkanToast: tampilkan, bukaPengingat }), [tampilkan, bukaPengingat]);
```

5. Ganti:

```tsx
                <WadahToast toast={toast} onTutup={tutup} />
```

dengan:

```tsx
                <WadahToast toast={toast} onTutup={tutup} />
                <LembarPengingat terbuka={pengingatTerbuka} onTutup={() => setPengingatTerbuka(false)} pemicu={pemicuPengingat} />
```

Di kepala Beranda:

`resources/js/pages/Dashboard.tsx`:

1. Ganti:

```tsx
import { AppLayout } from '@/layouts/AppLayout';
```

dengan:

```tsx
import { AppLayout } from '@/layouts/AppLayout';
import { TombolPengingat } from '@/layouts/Pengingat';
```

2. Ganti:

```tsx
                    merek
```

dengan:

```tsx
                    merek
                    aksi={<TombolPengingat />}
```

- [ ] **Step 7: Jalankan test tugas ini dan tetangganya sampai lulus.**

Run: `php artisan test --compact --filter='ReminderSheetTest|TripReminderTest|TripPermitWindowTest|PermitVisibilityTest'`
Expected: PASS, 47 test.

- [ ] **Step 8: Gerbang tugas.**

Run: `vendor/bin/pint --format agent app/Services/Trips/DaftarPengingat.php app/Http/Middleware/HandleInertiaRequests.php tests/Feature/ReminderSheetTest.php`
Expected: `{"tool":"pint","result":"passed"}` atau berkas dirapikan tanpa perubahan makna.

Run: `npx tsc --noEmit`
Expected: keluar tanpa keluaran galat.

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal. Pada simulasi rencana: 1.598 test, 1.596 lulus, 2 dilewati. Kunjungan biasa tidak berubah
biayanya (`test_a_normal_visit_never_pays_for_the_reminders`); membuka lembar paling banyak 5 kueri tetap
(`test_the_list_costs_a_fixed_number_of_queries`).

- [ ] **Step 9: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 7: Data contoh, pratinjau, dan dokumen kerangka

Ruling W2-R4 dan W2-K16, W2-K17. `DemoSeeder` mendapat isi yang dibutuhkan kerangka:

- lima simpanan Budi, seperti "Simpanan 5" di prototype 02 dan 15;
- pengingat H-3 dan H-1 trip Gede lewat penjadwal yang sama dengan halaman trip;
- satu aturan izin tingkat gunung yang bertanda DATA CONTOH dan tidak meniru pengelola sungguhan.

Harness pratinjau memotret kelima akar tab, detail jalur sebagai halaman tugas, dan Jelajah saat Budi mendaki.
`DESIGN.md`, `CONTRIBUTING.md`, dan `docs/ARCHITECTURE.md` mencatat kerangka baru.

**Files:**
- Modify: `database/seeders/DemoSeeder.php`, `tests/Pratinjau/HalamanPratinjauTest.php`, `DESIGN.md` (bagian Material dan Navigasi, keduanya di bawah Turunan yang tidak dipatok hash), `CONTRIBUTING.md`, `docs/ARCHITECTURE.md`
- Test: `tests/Feature/DemoSeederTest.php`, `tests/Feature/PratinjauHarnessTest.php`

**Interfaces:**
- Consumes: `DemoSeeder::PENANDA` dan `DemoSeeder::EMAIL_PENDAKI`, `TrailConsideration`, `PermitRequirement`, `TripReminderScheduler::sinkronkan()`, `ConsiderationService::jumlahSimpanan()` (Tugas 3), `PratinjauTestCase::tulisPratinjau($nama, $jawaban, $komponen)`, `self::ASAL`, dan `sebagaiBudi()` (W1).
- Produces: berkas pratinjau `trip.html`, `mendaki.html`, `progres.html`, `detail-jalur.html`, `jelajah-mendaki.html`, di samping `dasbor.html`, `jelajah.html`, `jelajah-kosong.html`, dan `primitif.html` dari W1. Tugas 8 memotretnya.

- [ ] **Step 1: Tulis test yang gagal.** Di `tests/Feature/DemoSeederTest.php`:

1. Ganti:

```php
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
```

dengan:

```php
use App\Models\PermitRequirement;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\TripReminder;
use App\Models\User;
use App\Services\ConsiderationService;
```

2. Ganti:

```php
    }

    /**
     * Setiap teks yang dapat terbaca sebagai fakta membawa penanda DATA CONTOH.
```

dengan:

```php
    }

    /**
     * Diperluas W2: kerangka menampilkan "Simpanan N" dan lonceng Pengingat, jadi data contoh
     * membawa lima simpanan (prototype 02 dan 15), pengingat H-3 dan H-1 trip Gede, dan satu
     * aturan izin tingkat gunung, semuanya lewat tabel yang sama dengan fitur aslinya.
     */
    public function test_the_sample_fills_the_shell(): void
    {
        $this->seed(DemoSeeder::class);

        $budi = User::where('email', DemoSeeder::EMAIL_PENDAKI)->firstOrFail();
        $trip = TripPlan::where('user_id', $budi->id)->where('status', TripStatus::PLANNED->value)->sole();

        $this->assertSame(5, app(ConsiderationService::class)->jumlahSimpanan($budi));
        $this->assertSame(['H-1', 'H-3'], TripReminder::where('trip_plan_id', $trip->id)->menunggu()->orderBy('jenis')->pluck('jenis')->all());
        $this->assertSame(1, PermitRequirement::where('mountain_id', $trip->trail->mountain_id)->count());
    }

    /**
     * Setiap teks yang dapat terbaca sebagai fakta membawa penanda DATA CONTOH.
```

3. Ganti:

```php
            ...TripPlan::whereNotNull('notes')->pluck('notes')->reject(fn (string $t): bool => str_starts_with($t, DemoSeeder::PENANDA.'.'))->all(),
```

dengan:

```php
            ...TripPlan::whereNotNull('notes')->pluck('notes')->reject(fn (string $t): bool => str_starts_with($t, DemoSeeder::PENANDA.'.'))->all(),
            ...PermitRequirement::pluck('authority')->reject(fn (string $t): bool => str_starts_with($t, DemoSeeder::PENANDA))->all(),
            ...PermitRequirement::whereNotNull('notes')->pluck('notes')->reject(fn (string $t): bool => str_starts_with($t, DemoSeeder::PENANDA.'.'))->all(),
```

Di `tests/Feature/PratinjauHarnessTest.php`:

Ganti:

```php
    public function test_the_router_script_never_boots_the_application(): void
```

dengan:

```php
    /**
     * Pratinjau W2 (ruling W2-R4): kelima akar tab dan satu halaman tugas yang
     * menyembunyikan bilah tab ikut dipotret, ditambah Jelajah dengan strip pendakian aktif,
     * supaya tangkapan 390 px dan 1440 px menunjukkan kerangka baru.
     */
    public function test_the_preview_covers_every_tab_root_and_a_task_page(): void
    {
        $halaman = File::get(base_path('tests/Pratinjau/HalamanPratinjauTest.php'));

        foreach (['/dashboard', '/trails', '/trips', '/mendaki', '/progres', '/trails/gede-via-cibodas'] as $alamat) {
            $this->assertStringContainsString("self::ASAL.'{$alamat}'", $halaman, "{$alamat} tidak dipotret.");
        }

        $this->assertStringContainsString("'jelajah-mendaki'", $halaman);
    }

    public function test_the_router_script_never_boots_the_application(): void
```

- [ ] **Step 2: Jalankan dan pastikan gagal.**

Run: `php artisan test --compact --filter='DemoSeederTest|PratinjauHarnessTest'`
Expected: FAIL. Pada simulasi rencana: 20 test, 18 lulus, 2 gagal (`test_the_sample_fills_the_shell`: 0 simpanan, bukan
5; `test_the_preview_covers_every_tab_root_and_a_task_page`: `/trips` belum dipotret).

- [ ] **Step 3: Data contoh untuk kerangka.** Di `database/seeders/DemoSeeder.php`:

1. Ganti:

```php
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
```

dengan:

```php
use App\Models\PermitRequirement;
use App\Models\Trail;
use App\Models\TrailConsideration;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\Trips\TripReminderScheduler;
```

2. Ganti:

```php
        $this->trip($budi, $jalur);
```

dengan:

```php
        $this->trip($budi, $jalur);
        $this->simpanan($budi, $jalur);
        $this->izin($sumber, $jalur);
```

3. Ganti:

```php
        $hariIni = Carbon::parse(Timezone::earliestDateInIndonesia());

        TripPlan::create([
            'user_id' => $budi->id,
```

dengan:

```php
        $hariIni = Carbon::parse(Timezone::earliestDateInIndonesia());

        $gede = TripPlan::create([
            'user_id' => $budi->id,
```

4. Ganti:

```php
        ]);

        foreach ([['andong-via-sawit', 120, TripType::TEKTOK], ['batur-via-toya-bungkah', 75, TripType::TEKTOK], ['prau-via-wates', 40, TripType::CAMPING]] as [$slug, $hariLalu, $jenis]) {
```

dengan:

```php
        ]);

        // Pengingat H-3 dan H-1 dijadwalkan lewat penjadwal yang sama dengan halaman trip,
        // supaya lembar Pengingat di kerangka W2 berisi jadwal sungguhan, bukan tempelan.
        app(TripReminderScheduler::class)->sinkronkan($gede);

        foreach ([['andong-via-sawit', 120, TripType::TEKTOK], ['batur-via-toya-bungkah', 75, TripType::TEKTOK], ['prau-via-wates', 40, TripType::CAMPING]] as [$slug, $hariLalu, $jenis]) {
```

5. Ganti:

```php
    }

    /**
     * Sebelas jalur. Jalur dengan status null belum punya pos dan status resmi, jadi tetap
```

dengan:

```php
    }

    /**
     * Lima jalur tersimpan, seperti "Simpanan 5" di prototype 02 dan 15, lewat tabel yang sama
     * dengan tombol Simpan (W2).
     *
     * @param  array<string, Trail>  $jalur
     */
    private function simpanan(User $budi, array $jalur): void
    {
        foreach (['batu-via-jonggol', 'gede-via-cibodas', 'gede-via-gunung-putri', 'pangrango-via-cibodas', 'gede-via-selabintana'] as $slug) {
            TrailConsideration::create(['user_id' => $budi->id, 'trail_id' => $jalur[$slug]->id]);
        }
    }

    /**
     * Aturan izin tingkat gunung untuk Gunung Gede, jendela pesan H-30 sampai H-3, supaya
     * tenggat izin di lembar Pengingat punya isi (W2). Nama pengelolanya diberi penanda, tidak
     * meniru pengelola sungguhan, dan tautan pemesanan sengaja kosong.
     *
     * @param  array<string, Trail>  $jalur
     */
    private function izin(DataSource $sumber, array $jalur): void
    {
        PermitRequirement::create([
            'mountain_id' => $jalur['gede-via-cibodas']->mountain_id,
            'authority' => self::PENANDA.', bukan pengelola resmi',
            'booking_opens_days_before' => 30,
            'booking_closes_days_before' => 3,
            'notes' => self::PENANDA.'. Jendela pemesanan dibuat untuk peninjauan tampilan.',
            'data_source_id' => $sumber->id,
            'source' => self::PENANDA.', bukan sumber resmi',
        ]);
    }

    /**
     * Sebelas jalur. Jalur dengan status null belum punya pos dan status resmi, jadi tetap
```

- [ ] **Step 4: Halaman pratinjau W2.** Di `tests/Pratinjau/HalamanPratinjauTest.php`:

1. Ganti:

```php
use App\Models\User;
```

dengan:

```php
use App\Enums\HikingSessionStatus;
use App\Enums\TripStatus;
use App\Models\Checkpoint;
use App\Models\HikingSession;
use App\Models\TripPlan;
use App\Models\User;
```

2. Ganti:

```php
 * keadaan basis data pemilik hari ini (Review Focus W1).
```

dengan:

```php
 * keadaan basis data pemilik hari ini (Review Focus W1).
 *
 * Diperluas W2 (ruling W2-R4): kelima akar tab dengan isinya sekarang (Dasbor adalah
 * Beranda), detail jalur sebagai halaman tugas yang menyembunyikan bilah tab, dan Jelajah
 * saat Budi sedang mendaki supaya strip pendakian aktif ikut terpotret.
```

3. Ganti:

```php
    public function test_jelajah_tanpa_jalur_terbit(): void
```

dengan:

```php
    public function test_trip(): void
    {
        $this->sebagaiBudi();

        $this->tulisPratinjau('trip', $this->get(self::ASAL.'/trips'), 'Trips/Index');
    }

    public function test_mendaki(): void
    {
        $this->sebagaiBudi();

        $this->tulisPratinjau('mendaki', $this->get(self::ASAL.'/mendaki'), 'Track/Index');
    }

    public function test_progres(): void
    {
        $this->sebagaiBudi();

        $this->tulisPratinjau('progres', $this->get(self::ASAL.'/progres'), 'Community/HikerProgress');
    }

    /**
     * Halaman tugas: bilah tab disembunyikan dan tombol kembali menuju Jelajah (spec v3
     * bagian 5.1).
     */
    public function test_detail_jalur(): void
    {
        $this->sebagaiBudi();

        $this->tulisPratinjau('detail-jalur', $this->get(self::ASAL.'/trails/gede-via-cibodas'), 'Trails/Show');
    }

    /**
     * Budi sedang mendaki Gede via Cibodas sejak 2 jam 15 menit lalu, sesinya menuju pos
     * kedua; strip pendakian aktif tampil di atas bilah tab.
     */
    public function test_jelajah_saat_mendaki(): void
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

        $this->tulisPratinjau('jelajah-mendaki', $this->get(self::ASAL.'/trails'), 'Trails/Index');
    }

    public function test_jelajah_tanpa_jalur_terbit(): void
```

- [ ] **Step 5: Jalankan test tugas ini dan tetangganya sampai lulus.**

Run: `php artisan test --compact --filter='DemoSeederTest|PratinjauHarnessTest|DemoCommandGuardTest|DesignDirectionTest|PrdReferenceIntegrityTest'`
Expected: PASS. Pada simulasi rencana: 51 test, 50 lulus, 1 dilewati (sudah dilewati sebelum W2).

- [ ] **Step 6: Suite pratinjau.** Suite ini membaca aset hasil build dan menulis HTML ke `public/_pratinjau/`
(diabaikan git), tanpa menyalakan server.

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`
Expected: `✓ built`.

Run: `php artisan test --compact --testsuite=Pratinjau`
Expected: PASS, 9 test (`dasbor`, `detail-jalur`, `jelajah`, `jelajah-kosong`, `jelajah-mendaki`, `mendaki`,
`primitif`, `progres`, `trip`).

- [ ] **Step 7: Dokumen.** Di `DESIGN.md`, bagian "Material, elevasi, dan lapisan" lalu bagian "Navigasi":

1. Ganti:

```markdown
Skala lapisan: bar aksi dan bilah tab `z-30`, toast `z-40`. Lembar bawah dan dialog adalah `<dialog>` asli yang dibuka
`showModal()`, jadi tinggal di top layer peramban tanpa z-index, dengan tirai token `scrim`; halaman di belakangnya
berhenti bergulir, Escape menutup, dan fokus kembali ke pemicunya, atau ke `#konten` bila pemicunya tidak ada (W1-K6).

Urutan tumpuk di tepi bawah layar, dari bawah ke atas: bilah tab (tingginya `--nav-bawah`), bar aksi 0,75rem di atasnya,
lalu toast 1rem di atas navigasi ditambah tinggi bar aksi. Selama terpasang, bar aksi menulis tingginya ke `--bar-aksi`
di `<html>`; pengganjal di bawah isi halaman dan jarak bawah toast membacanya, jadi toast tidak pernah menutupi tombol
bar, juga ketika bar membungkus ke dua baris di layar sempit (`DesignPrimitivesTest`).
```

dengan:

```markdown
Skala lapisan: bar aksi, bilah tab, strip pendakian aktif, dan header layar lebar `z-30`; toast dan pesan jaringan
`z-40`. Lembar bawah dan dialog adalah `<dialog>` asli yang dibuka `showModal()`, dan menu avatar adalah popover asli,
jadi ketiganya tinggal di top layer peramban tanpa z-index. Lembar dan dialog memakai tirai token `scrim`; halaman di
belakangnya berhenti bergulir, Escape menutup, dan fokus kembali ke pemicunya, atau ke `#konten` bila pemicunya tidak
ada (W1-K6).

Urutan tumpuk di tepi bawah layar, dari bawah ke atas: bilah tab, strip pendakian aktif selama trip berlangsung, bar
aksi 0,75rem di atasnya, lalu toast 1rem di atas navigasi ditambah tinggi bar aksi. `--nav-bawah` adalah jumlah tinggi
bilah tab dan strip, diturunkan `app.css` dari elemen yang benar-benar ada di halaman (W2). Selama terpasang, bar aksi
menulis tingginya ke `--bar-aksi` dan strip menulis tingginya ke `--strip-pendakian` di `<html>`; pengganjal di bawah
isi halaman dan jarak bawah toast membacanya, jadi toast tidak pernah menutupi tombol bar, juga ketika bar atau strip
membungkus ke dua baris di layar sempit (`DesignPrimitivesTest`, `ActiveHikeStripTest`).
```

2. Ganti:

```markdown
Lima tab Beranda, Jelajah, Trip, Mendaki, Progres (DK-25). Kerangka v3 (bilah tab pil mengambang di ponsel, header
dengan navigasi segmen di layar lebar, halaman tugas yang menyembunyikan bilah tab, strip pendakian aktif) ditetapkan
spec v3 bagian 5 dan dibangun W2 dengan `BilahTab`. Sampai W2, navigasi bawah lama (`NavLinks.tsx`) tetap dipakai
dengan ikon tab 22 px dan varian isi untuk tab aktif. Jelajah berbasis peta tetap mengikuti keputusan F2 (peta
menggambar bentuk jalur, bukan titik gunung) sampai W3 menulis ulang klasternya.
```

dengan:

```markdown
Lima tab Beranda, Jelajah, Trip, Mendaki, Progres (DK-25), dari satu daftar (`itemTab()` di
`resources/js/layouts/NavLinks.tsx`) untuk dua bentuk kerangka yang dibangun W2 (spec v3 bagian 5):

- **Di bawah 1024 px** tidak ada bilah atas global. Setiap halaman membawa kepalanya lewat `PageHeader`: akar tab
  memberi `akar` (h1, aksi kontekstual, avatar 44 px ke Profil; Beranda menambah `merek` untuk lockup), halaman turunan
  memberi `induk` (tombol kembali 44 px ke induk logisnya, bukan riwayat peramban). Navigasi utama adalah bilah tab pil
  mengambang (`BilahTab`: item 56 px, label 12 px, ikon 22 px, varian isi dan `aria-current` untuk tab aktif). Halaman
  tugas dengan bar aksi tetap (detail jalur, rencanakan trip; penilaian kecocokan menyusul di W3) memasang
  `<AppLayout bilahTab={false}>`. Lebar 640 sampai 1023 px sengaja tetap memakai kerangka ponsel.
- **Mulai 1024 px** (DK-33) header 68 px menempel di atas: lockup, navigasi segmen lima tab di tengah (ikon 20 px, label
  14 px), lalu lencana Data contoh, Simpanan dengan hitungannya, dan menu avatar (popover asli: Profil; Moderasi dan
  Admin sesuai peran; Keluar). Di ponsel, Moderasi, Admin, dan Keluar tinggal di kartu Akun halaman Profil.
- **Strip pendakian aktif**: selama trip berlangsung, strip isian merek di atas bilah tab membawa pendaki kembali ke
  mode lapangan dengan satu ketukan; mode lapangan sendiri tidak memasangnya.
- **Lonceng Beranda** membuka lembar Pengingat (pengingat surel terjadwal dan tenggat izin milik sendiri), tanpa
  notifikasi dorong (Q6).
- Jarak lapisan tetap diturunkan dari elemen yang ada lewat `:has()` di `app.css` (`--bilah-tab`, `--strip-pendakian`,
  `--kepala-atas`, `--nav-bawah`) ditambah `scroll-padding`, supaya bar aksi, toast, dan fokus papan ketik tidak pernah
  tertutup (WCAG 2.4.11).

Jelajah berbasis peta tetap mengikuti keputusan F2 (peta menggambar bentuk jalur, bukan titik gunung) sampai W3
menulis ulang klasternya.
```

Di `CONTRIBUTING.md`:

1. Ganti:

```markdown
   `Halaman.layout = (page) => <AppLayout>{page}</AppLayout>`, tepat satu `<h1>`, memakai
   komponen dari `resources/js/components/ui`.
```

dengan:

```markdown
   `Halaman.layout = (page) => <AppLayout>{page}</AppLayout>` (halaman tugas dengan bar aksi
   tetap memakai `<AppLayout bilahTab={false}>`), dan tepat satu `<PageHeader>` yang memberi
   `title` serta `induk={{ href, label }}` ke induk logisnya; hanya lima akar tab yang memberi
   `akar`. TypeScript menolak `PageHeader` tanpa salah satunya, dan `PageHeaderContractTest`
   menjaga siapa akarnya. Memakai komponen dari `resources/js/components/ui`.
```

2. Ganti:

```markdown
| Setiap halaman mengirim judul tab | `PageTitleTest`, `InertiaLayoutTest` |
```

dengan:

```markdown
| Setiap halaman mengirim judul tab | `PageTitleTest`, `InertiaLayoutTest` |
| Setiap halaman memberi judul dan tempatnya (akar tab atau induk) | `PageHeaderContractTest` |
| Props bersama kerangka tidak bernama sama dengan props halaman | `ReminderSheetTest` |
```

3. Ganti:

```markdown
Lalu buka `http://127.0.0.1:8100/_pratinjau/primitif.html`, `dasbor.html`, `jelajah.html`, atau `jelajah-kosong.html`.
```

dengan:

```markdown
Lalu buka `http://127.0.0.1:8100/_pratinjau/primitif.html`, `dasbor.html` (Beranda), `jelajah.html`, `trip.html`,
`mendaki.html`, `progres.html`, `detail-jalur.html` (halaman tugas tanpa bilah tab), `jelajah-mendaki.html` (strip
pendakian aktif), atau `jelajah-kosong.html`.
```

4. Ganti:

```markdown
  ke terang dengan `tema=light`, atau hapus cookie itu untuk mengikuti sistem.
```

dengan:

```markdown
  ke terang dengan `tema=light`, atau hapus cookie itu untuk mengikuti sistem.
- Lembar Pengingat memuat isinya dari server saat dibuka, jadi di pratinjau statis ia menampilkan keadaan galatnya.
  Isi daftarnya dijaga `ReminderSheetTest`.
```

Di `docs/ARCHITECTURE.md`, dua baris peta kode sesudah baris "Warna, radius, huruf, gaya tombol":

Ganti:

```markdown
| Warna, radius, huruf, gaya tombol | Token di `resources/css/app.css`, pemetaan kelas di `tailwind.config.js`, primitif React di `resources/js/components/ui/`, komponen Blade halaman publik di `resources/views/components/ui/` |
```

dengan:

```markdown
| Warna, radius, huruf, gaya tombol | Token di `resources/css/app.css`, pemetaan kelas di `tailwind.config.js`, primitif React di `resources/js/components/ui/`, komponen Blade halaman publik di `resources/views/components/ui/` |
| Tab navigasi, kerangka ponsel dan layar lebar, strip pendakian, lembar Pengingat | `resources/js/layouts/` (daftar tab `NavLinks.tsx`, kerangka `AppLayout.tsx`); kepala setiap halaman lewat `PageHeader` dengan `akar` atau `induk` |
| Props bersama kerangka (hitungan Simpanan, pendakian aktif, daftar pengingat) | `app/Http/Middleware/HandleInertiaRequests.php`, satu kueri terindeks per prop; nama barunya tidak boleh sama dengan prop halaman |
```

- [ ] **Step 8: Gerbang tugas.**

Run: `vendor/bin/pint --format agent database/seeders/DemoSeeder.php tests/Feature/DemoSeederTest.php tests/Feature/PratinjauHarnessTest.php tests/Pratinjau/HalamanPratinjauTest.php`
Expected: `{"tool":"pint","result":"passed"}` atau berkas dirapikan tanpa perubahan makna.

Run: `npx tsc --noEmit`
Expected: keluar tanpa keluaran galat.

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal. Pada simulasi rencana: 1.600 test, 1.598 lulus, 2 dilewati. `DesignDirectionTest` tetap hijau:
bagian Arah dan aturan canonical `DESIGN.md` yang dipatok hash tidak disentuh.

- [ ] **Step 9: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 8: Verifikasi gelombang (koordinator)

Ruling W2-R5 dan "Selesai bila" induk W2. Dikerjakan koordinator sesudah Tugas 1 sampai 7 selesai dan lolos tinjauan
per tugas. Setiap klaim di laporan disertai keluaran perintah, jalur berkas, atau tangkapan (R-023). Angka "pada
simulasi rencana" adalah hasil menjalankan kode rencana ini di salinan W1 akhir di scratchpad, bukan hasil di repo, dan
menjadi pembanding. Harness pratinjau memakai port 8100 milik koordinator.

**Files:**
- Create: `docs/desain/tangkapan/w2/*.webp` (tangkapan), `docs/desain/tangkapan/w2/README.md` (perbandingan)
- Modify: `HANDOVER.md` (bagian 0: baris "Terakhir diperbarui" dan subbagian W2), `.superpowers/sdd/2026-09-26-memount-v3-program.md` (baris W2; berkas ini diabaikan git). `docs/README.md` sudah mengindeks `desain/tangkapan/`, jadi tidak diubah.

**Interfaces:**
- Consumes: seluruh keluaran Tugas 1 sampai 7; harness pratinjau (W1 Tugas 10, diperluas Tugas 7); gambar acuan `docs/desain/prototype/acuan/01-beranda.png`, `02-jelajah.png`, `04-detail-jalur.png`, `11-mendaki.png`, `13-progres.png`, `15-jelajah-lebar.png`.
- Produces: bukti akhir W2 dan titik lanjut untuk W3.

- [ ] **Step 1: Catat keadaan kerja.**

Run: `git status --short | wc -l; git diff --stat | tail -1`
Expected: selisih terhadap catatan akhir W1 cocok dengan gabungan daftar Files Tugas 1 sampai 7. Pada simulasi
rencana: 77 berkas berubah atau baru karena W2 (+2.282 dan −276 baris), yaitu 17 berkas baru dan 60 berkas yang
diubah.

- [ ] **Step 2: Suite penuh, tipe, format.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 1.600 test, 1.598 lulus, 2 dilewati (W1 akhir: 1.559 test).
`RecommendationExclusionTest::test_jelajah_shows_no_public_fit_label_for_an_incomplete_trail` bisa gagal bila faker
membuat nama berapostrof. Kegagalan itu sudah ada sebelum W2: ulangi suite, lalu catat di OPEN ISSUE.

Run: `php artisan test --compact`
Expected: pada simulasi rencana 1.620 test, 1.598 lulus, 22 dilewati. Suite Spatial melewati dirinya tanpa
`SPATIAL_TEST_DSN`.

Run: `npx tsc --noEmit`
Expected: keluar tanpa keluaran galat.

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

- [ ] **Step 3: Build dan ukuran bundel.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`
Expected: `✓ built` dengan peringatan yang sama seperti W1: dua `/fonts/plus-jakarta-sans/... didn't resolve at build
time` dan ukuran potongan maplibre-gl. Catat ukuran `app-*.css` dan `app-*.js`. Pada simulasi rencana, `app-*.js`
354.780 bita (W1 akhir 355.096). `app-*.css` +282 bita terhadap W1 pada himpunan view terkompilasi yang sama. Ukuran
absolut CSS ikut isi `storage/framework/views` (Konflik 10), jadi yang dilaporkan adalah selisih, bukan angka mutlak.

Run: `grep -l "Copyright (c) 2023 Phosphor Icons" public/build/assets/*.js | wc -l; grep -l "@license 3-Clause BSD" public/build/assets/*.js | wc -l`
Expected: dua baris, masing-masing paling sedikit `1`.

- [ ] **Step 4: Suite pratinjau dan server statis.**

Run: `php artisan test --compact --testsuite=Pratinjau`
Expected: PASS, 9 test.

Run (di latar belakang): `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php -S 127.0.0.1:8100 -t public tests/Pratinjau/statis.php`
Expected: server statis tanpa basis data. Buka `http://127.0.0.1:8100/_pratinjau/<nama>.html`, dan hentikan server
sesudah Step 7.

- [ ] **Step 5: Tangkapan.** Pakai Chrome lewat MCP chrome-devtools. Ukuran 390 x 844 memakai emulasi perangkat
seluler dengan sentuh dan rasio piksel 1, ukuran 1440 x 900 rasio piksel 1, dan tema dipilih lewat cookie `tema`
seperti W1. Simpan sebagai WebP di `docs/desain/tangkapan/w2/`:

- Beranda: `dasbor-390-light.webp`, `dasbor-390-dark.webp`, `dasbor-1440-light.webp`, `dasbor-1440-dark.webp`.
- Jelajah: `jelajah-390-light.webp`, `jelajah-390-dark.webp`, `jelajah-1440-light.webp`, `jelajah-1440-dark.webp`.
- Trip: `trip-390-light.webp`, `trip-1440-light.webp`.
- Mendaki: `mendaki-390-light.webp`, `mendaki-1440-light.webp`.
- Progres: `progres-390-light.webp`, `progres-1440-light.webp`.
- Halaman tugas tanpa bilah tab: `detail-jalur-390-light.webp`.
- Strip pendakian: `jelajah-mendaki-390-light.webp`.
- Header muat satu baris di 1024 x 768: `kepala-1024-light.webp`.
- Popover terbuka sesudah Enter pada avatar: `menu-avatar-1440-light.webp`.
- Lembar terbuka dari lonceng: `pengingat-390-light.webp`. Pratinjau statis menampilkan keadaan galatnya (W2-K17).

- [ ] **Step 6: Perbandingan tertulis.** Tulis `docs/desain/tangkapan/w2/README.md` dengan susunan
`docs/desain/tangkapan/w1/README.md`:

- paragraf cara pengambilan: tanggal, harness, versi Chrome, emulasi, cookie tema;
- satu paragraf cakupan W2;
- tabel `| Tangkapan | Gambar acuan | Yang sudah sesuai | Yang belum sesuai dan gelombang yang mengubahnya |` untuk
  setiap baris tangkapan, dengan acuan 01 untuk Beranda, 02 dan 15 untuk Jelajah, 11 untuk Mendaki, 13 untuk Progres,
  dan 04 untuk detail jalur. Trip tidak punya layar acuan daftar, jadi yang dibandingkan hanya pola kepala akar tabnya
  dengan 01 dan 13.

Pemeriksaan perencanaan menemukan hal-hal di bawah ini. Pastikan masing-masing pada tangkapan, jangan disalin tanpa
melihat.

- **Sudah sesuai.**
  - Ponsel: kepala per halaman tanpa bilah atas global. Beranda memuat lockup, lonceng, dan avatar; Jelajah memuat
    judul, Simpanan berhitungan, dan avatar; Progres memuat judul dan avatar.
  - Bilah tab pil mengambang dengan tab aktif berikon isi dan berlabel tebal.
  - Detail jalur memakai tombol kembali tanpa bilah tab.
  - Layar lebar: header 68 px dengan lockup di kiri, navigasi segmen di tengah, dan lencana Data contoh, Simpanan
    berhitungan, serta avatar di kanan.
- **Belum sesuai.**
  - Jarak atas halaman `py-8`, sedangkan acuan sekitar 16 px (W3 sampai W7).
  - Lencana Data contoh di bawah judul pada ponsel (W2-K5, disengaja).
  - Segmen aktif ditandai garis dan permukaan terangkat; sesuaikan dengan acuan bila acuan memakai bayangan.
  - Tanda lockup mengikuti W1.
  - Isi setiap halaman di bawah kepalanya masih isi lama (W3 sampai W7).

- [ ] **Step 7: Jalur papan ketik.** Pada pratinjau, tanpa tetikus:

1. **390 px, Beranda.** Tab dari awal: tautan lewati, lonceng, avatar, isi, lalu kelima tab, masing-masing dengan
   cincin fokus terlihat. Enter pada lonceng membuka lembar Pengingat dengan fokus di dalamnya; Escape menutupnya, dan
   fokus kembali ke lonceng.
2. **390 px, detail jalur.** Tab pertama sesudah tautan lewati berhenti di tombol "Kembali ke Jelajah", dan tidak ada
   bilah tab.
3. **1440 px, Jelajah.** Tab dari awal: tautan lewati, lockup, lima segmen, Simpanan, lalu avatar. Enter membuka menu;
   Tab melewati Profil, Moderasi dan Admin sesuai peran, lalu Keluar, dengan cincin di dalam baris. Escape menutup
   menu, dan fokus kembali ke avatar.

Simpan satu tangkapan fokus pada tab bilah sesudah langkah 1 sebagai `fokus-kerangka-390-light.webp`. Hentikan server
port 8100.

- [ ] **Step 8: Delivery Gate antislop.** Tulis laporan PASS/FAIL, satu baris per butir, dan setiap PASS disertai
bukti:

- R-02: tanpa em dash. Run: `LC_ALL=C grep -rlP '\xE2\x80\x94' app resources tests database DESIGN.md CONTRIBUTING.md docs/ARCHITECTURE.md`. Expected: tanpa keluaran.
- R-09: satu lencana, hanya "Data contoh".
- R-10: `.chrome` hanya di bilah tab dan bar aksi; strip dan header solid.
- R-26: setiap kendali kerangka berfungsi. Lonceng membuka lembar, avatar membuka Profil atau menu, Simpanan membuka
  Simpanan, strip membuka mode lapangan, dan tombol kembali membuka induknya.
- R-27: keadaan memuat, galat, kosong, dan daftar di lembar Pengingat (`ReminderSheetTest`).
- R-32: jalur papan ketik Step 7.
- R-35: build dijalankan dan kendali diklik.
- Teks: bahasa Indonesia dengan sapaan "Anda", tanpa "aman" atau "layak berangkat".

- [ ] **Step 9: HANDOVER dan ledger.** Di `HANDOVER.md` bagian 0, perbarui baris "Terakhir diperbarui". Tambahkan
subbagian "W2 Kerangka (selesai <tanggal>, menunggu izin commit)" dengan susunan subbagian W1:

- rujukan rencana ini, jumlah tugas, dan letak ledger;
- tabel `| Butir | Nilai | Bukti |` berisi suite Unit dan Feature, suite bawaan, tsc, Pint, build dan selisih bundel,
  suite Pratinjau, anggaran kueri (+2 per kunjungan penuh), tangkapan, dan jalur papan ketik;
- ruling koordinator atas Konflik 1 sampai 10;
- OPEN ISSUE: test yang bergantung nama acak faker dan ukuran CSS yang bergantung view terkompilasi;
- titik lanjut W3.

Perbarui baris W2 di `.superpowers/sdd/2026-09-26-memount-v3-program.md`.

- [ ] **Step 10: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

## Tinjauan diri

### Cakupan induk W2 dan ruling

| Butir | Tugas | Bukti di rencana |
|---|---|---|
| Butir 1: header per halaman, akar tab dengan h1, aksi, avatar 44 px ke Profil; turunan dengan tombol kembali 44 px ke induk logis dari prop `induk`; semua halaman memberi judul dan induk | Tugas 1 (kepala, 38 halaman), Tugas 2 (tanpa bilah atas global) | `PageHeaderContractTest`, `InertiaLayoutTest::test_the_shell_switches_at_1024px_without_a_global_top_bar_on_phones` |
| Butir 2: satu sumber daftar tab untuk bilah tab ponsel dan navigasi segmen, lima tab, item ≥52 px (56 px), label 12 px, ikon 22 px, varian isi dan `aria-current`, safe area, tersembunyi di halaman tugas | Tugas 2 | `NavigationConsistencyTest` (termasuk `test_only_task_pages_hide_the_tab_bar`), `IconSystemTest`, `TapTargetTest` |
| Butir 3: header 68 px, lockup, navigasi segmen, Simpanan berhitungan, menu avatar per peran, hitungan satu kueri terindeks dan dampaknya diukur, `test_a_hiker_never_sees_the_admin_entry` tetap lulus | Tugas 2 (header, menu), Tugas 3 (hitungan) | `NavigationConsistencyTest::test_each_role_gets_exactly_its_console_doors`, `test_a_hiker_never_sees_the_admin_entry`, `SavedCountPropTest`, `PageQueryBudgetTest::test_the_shell_does_not_grow_with_the_number_of_saved_trails` |
| Butir 4: `pendakianAktif` (nama jalur, pos berikutnya, waktu mulai), strip 48 px di atas bilah tab kecuali mode lapangan; tampil untuk pemilik, tidak untuk pengguna lain, tidak di `/trips/{trip}/hike`, anggaran kueri | Tugas 4 | `ActiveHikeStripTest` (7 test) |
| Butir 5: lembar Pengingat dari lonceng Beranda, pengingat terjadwal dan tenggat izin milik sendiri, keadaan kosong menyebut apa yang akan muncul | Tugas 6 | `ReminderSheetTest` (9 test) |
| Butir 6: wadah toast dan lembar dipasang sekali di layout; `router.on('networkError')` pesan jujur | Tugas 5 (toast, pesan jaringan), Tugas 6 (lembar Pengingat sekali) | `ShellFeedbackTest`, `ReminderSheetTest::test_the_bell_opens_the_sheet_mounted_once_in_the_layout` |
| Selesai bila: tangkapan 390 px dan 1440 px setiap akar tab disandingkan dengan prototype 01, 02, 13, 15; jalur papan ketik melewati header, isi, dan bilah tab dengan fokus terlihat | Tugas 7 (pratinjau), Tugas 8 (tangkapan, perbandingan, jalur papan ketik) | Tugas 8 Step 5 sampai 7 |
| W2-R1: Profil lewat avatar, lima tab | Tugas 1, Tugas 2 | `PageHeaderContractTest::test_a_tab_root_reaches_the_profile_from_its_avatar`, `NavigationConsistencyTest::test_the_main_menu_is_five_surfaces_not_nine_nouns` |
| W2-R2: notifikasi dorong di luar cakupan; lembar memuat pengingat terjadwal dan tenggat izin milik pengguna; keadaan kosong menyebut apa yang akan muncul | Tugas 6 | `ReminderSheetTest::test_an_empty_sheet_says_what_will_appear_there`; keterangan lembar "Memount belum mengirim notifikasi ke ponsel." |
| W2-R3: `router.on('networkError')` dengan pesan jujur; "Buka paket luring" di W6 | Tugas 5 | `ShellFeedbackTest::test_a_network_error_shows_an_honest_message` |
| W2-R4: pratinjau diperluas dengan akar tab dan satu halaman tugas tanpa bilah tab | Tugas 7 | `PratinjauHarnessTest::test_the_preview_covers_every_tab_root_and_a_task_page`, suite `Pratinjau` 9 test |
| W2-R5: tugas terakhir verifikasi koordinator (suite, tsc, build, Pint, pratinjau, tangkapan 390 dan 1440 dibanding 01, 02, 13, 15, jalur papan ketik, Delivery Gate, `HANDOVER.md` bagian 0) | Tugas 8 | Step 2 sampai 9 |

### Pemindaian placeholder

Tidak ada "TBD", "TODO", "implement later", atau "similar to Task N". Setiap langkah kode memuat kodenya:

- berkas baru ditulis utuh;
- tulis ulang memuat isi utuh;
- perubahan kecil memakai blok "Ganti ... dengan ...".

Blok-blok itu dirakit dari rantai simulasi dan diperiksa dua hal. Pertama, teks lamanya muncul tepat sekali pada
keadaan sebelum tugas. Kedua, menerapkan semua blok berurutan menghasilkan berkas akhir tugas persis. Setiap berkas
yang berubah di simulasi disebut di tugas yang sama, dan tidak ada berkas yang disebut tanpa berubah. Sesudah dirakit,
teks rencana ini diputar ulang dari awal pada ekspor W1 akhir seperti implementer membacanya. Hasilnya ke-77 berkas akhir
simulasi, tanpa beda.

### Konsistensi nama dan tipe

Nama yang dipakai lintas tugas, dengan tugas yang membuatnya:

- **Kepala halaman (Tugas 1):** `PageHeader` dengan `akar`, `merek`, `induk`; `Induk { href; label }`;
  `TombolKembali({ href, label })`; `TautanProfil({ className })`; `PintuAkun()`.
- **Kerangka (Tugas 2):** `itemTab(jalur)`; `permukaan`; `tombolMenu`; `TautanMenu`;
  `AppLayout({ bilahTab, children })`; `KepalaLebar({ item })`; `MenuAkun()`; variabel CSS `--bilah-tab`,
  `--strip-pendakian`, `--kepala-atas`, `--nav-bawah`; penanda `data-bilah-tab`, `data-kepala-lebar`,
  `data-strip-pendakian`.
- **Simpanan (Tugas 3):** `ConsiderationService::jumlahSimpanan()`; prop `jumlahSimpanan`;
  `TautanSimpanan({ ringkas, className })`; ikon `'bookmark-simple'`.
- **Strip (Tugas 4):** `PendakianAktif::untuk()`; `interface PendakianAktif` dengan `id`, `jalur`, `posBerikutnya`,
  `mulai`, `url`; prop `pendakianAktif`; `StripPendakian({ pendakian })`; `lamaBerjalan()`.
- **Layanan kerangka (Tugas 5):** `LayananKerangka.tampilkanToast`; `KonteksKerangka`; `useKerangka()`;
  `PesanJaringan()`.
- **Pengingat (Tugas 6):** `DaftarPengingat::untuk()`; `interface DaftarPengingat { pengingat; tenggatIzin }`; prop
  opsional `daftarPengingat`; `LayananKerangka.bukaPengingat(pemicu)`; `TombolPengingat()`;
  `LembarPengingat({ terbuka, onTutup, pemicu })`.

Nama prop bersama dijaga tidak sama dengan prop halaman oleh
`ReminderSheetTest::test_shell_props_never_share_a_name_with_page_props`, dan tsc lulus sesudah setiap tugas pada
simulasi.

### Review Focus

Kelima baris Review Focus punya test di tugas pemilik kodenya:

- teks panjang: Tugas 2 dan Tugas 4;
- lapisan dan fokus: Tugas 2 dan Tugas 4;
- sinyal putus: Tugas 5 dan Tugas 6;
- data pengguna lain: Tugas 3, 4, dan 6;
- tabrakan nama prop: Tugas 6.

Semua test itu lulus pada simulasi.

### Pemeriksaan teks rencana

- Rencana ini tanpa em dash. Tanda "§" hanya muncul saat mengutip aturannya; dokumen lain dirujuk "bagian N".
- Teks antarmuka baru berbahasa Indonesia dengan sapaan "Anda".
- Tidak ada klaim "aman" atau "layak berangkat".
- Label kecocokan publik tidak berubah.
